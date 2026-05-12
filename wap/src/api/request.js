import axios from 'axios'
import { refreshAccessToken, removeToken } from '@/utils/auth'
import { ERROR_CODES, isAuthError } from '@/constants/lottery'
import { decrypt, encrypt, isEncrypted } from '@/utils/crypto'

const ENABLE_ENCRYPTION = true

let isLoggingOut = false
let hasAuthenticatedOnce = false
let isRefreshing = false
let refreshSubscribers = []

const subscribeTokenRefresh = (callback) => {
  refreshSubscribers.push(callback)
}

const onTokenRefreshed = (newToken) => {
  refreshSubscribers.forEach(callback => callback(newToken))
  refreshSubscribers = []
}

const API_BASE_URL = import.meta.env?.VITE_API_BASE_URL || '/api'

const request = axios.create({
  baseURL: API_BASE_URL,
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json'
  }
})

request.interceptors.request.use(
  config => {
    config.metadata = { startTime: Date.now() }

    const noAuthUrls = ['/auth/login', '/register', '/auth/refresh', '/v1/config']
    const skipAuth = noAuthUrls.some(path => config.url?.includes(path))
    
    if (!skipAuth) {
      const token = localStorage.getItem('token')
      if (token) {
        config.headers.Authorization = `Bearer ${token}`
      }
    }
    
    if (ENABLE_ENCRYPTION && config.data && ['post', 'put', 'patch'].includes(config.method)) {
      if (!(config.data instanceof FormData)) {
        const encrypted = encrypt(config.data)
        if (encrypted) {
          config.data = encrypted
          config.headers['Content-Type'] = 'text/plain'
          config.headers['X-Encrypted'] = '1'
        }
      }
    }
    
    return config
  },
  error => Promise.reject(error)
)

request.interceptors.response.use(
  response => {
    let res = response.data
    const isEncryptedResponse = response.headers?.['x-encrypted'] === '1'
    if (ENABLE_ENCRYPTION && isEncryptedResponse && typeof res === 'string') {
      try {
        res = decrypt(res)
        response.data = res
      } catch (e) {}
    }
    
    if (typeof res?.code !== 'undefined') {
      const silentFailUrls = ['/heartbeat', '/profile', '/v1/notice', '/v1/message']
      const isSilentFail = silentFailUrls.some(u => response.config.url?.includes(u))
      
      if (isAuthError(res.code)) {
        if (!isSilentFail && hasAuthenticatedOnce) {
          handleAuthFailure(res.message)
        }
        return Promise.reject(new Error(res.message || '登录已失效'))
      }
      
      if (res.code === 0 || res.code === 200) {
        hasAuthenticatedOnce = true
      }
      
      if (res.code !== 200 && res.code !== 0) {
        const errorMsg = res.message || res.msg || '请求失败'
        return Promise.reject(new Error(errorMsg))
      }
      
      return res
    }
    return res
  },
  async error => {
    if (error.response?.status === 401) {
      const silentFailUrls = ['/heartbeat', '/profile', '/v1/notice', '/v1/message']
      const isSilentFail = silentFailUrls.some(u => error.config?.url?.includes(u))
      
      if (isRefreshing) {
        return new Promise((resolve, reject) => {
          subscribeTokenRefresh((newToken) => {
            if (newToken) {
              error.config.headers.Authorization = `Bearer ${newToken}`
              resolve(request(error.config))
            } else {
              reject(new Error('登录已失效'))
            }
          })
        })
      }
      
      isRefreshing = true
      
      try {
        const newToken = await refreshAccessToken()
        isRefreshing = false
        
        if (newToken) {
          onTokenRefreshed(newToken)
          error.config.headers.Authorization = `Bearer ${newToken}`
          return request(error.config)
        } else {
          onTokenRefreshed(null)
          if (!isSilentFail && hasAuthenticatedOnce) {
            handleAuthFailure('登录状态已失效')
          }
          return Promise.reject(new Error('登录已失效'))
        }
      } catch (refreshError) {
        isRefreshing = false
        onTokenRefreshed(null)
        return Promise.reject(new Error('登录已失效'))
      }
    }
    
    if (error.response?.status === 403) {
      return Promise.reject(new Error('没有访问权限'))
    }
    
    if (error.code === 'ECONNABORTED' || error.message?.includes('timeout')) {
      return Promise.reject(new Error('请求超时，请检查网络'))
    }
    
    if (error.message?.includes('Network Error')) {
      return Promise.reject(new Error('网络连接失败，请检查网络设置'))
    }
    
    return Promise.reject(error)
  }
)

function handleAuthFailure(message) {
  if (isLoggingOut) return
  
  isLoggingOut = true
  removeToken()
  
  setTimeout(() => {
    isLoggingOut = false
    hasAuthenticatedOnce = false
    window.location.href = '/home-new'
  }, 300)
}

export function resetAuthState() {
  hasAuthenticatedOnce = false
  isLoggingOut = false
}

export default request
