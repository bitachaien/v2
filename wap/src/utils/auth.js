import { decrypt, encrypt } from './crypto'

const TOKEN_KEY = 'token'
const ENABLE_ENCRYPTION = false
const REFRESH_TOKEN_KEY = 'refresh_token'
const TOKEN_EXPIRE_KEY = 'token_expire_time'
const USER_INFO_KEY = 'userInfo'

let refreshingPromise = null

export function getToken() {
  return localStorage.getItem(TOKEN_KEY)
}

export function setToken(token, expiresIn = 7200) {
  if (!token) return
  localStorage.setItem(TOKEN_KEY, token)
  const expireTime = Date.now() + (expiresIn * 1000)
  localStorage.setItem(TOKEN_EXPIRE_KEY, expireTime.toString())
}

export function setRefreshToken(refreshToken) {
  if (!refreshToken) return
  localStorage.setItem(REFRESH_TOKEN_KEY, refreshToken)
}

export function getRefreshToken() {
  return localStorage.getItem(REFRESH_TOKEN_KEY)
}

export function removeToken() {
  localStorage.removeItem(TOKEN_KEY)
  localStorage.removeItem(REFRESH_TOKEN_KEY)
  localStorage.removeItem(TOKEN_EXPIRE_KEY)
  localStorage.removeItem(USER_INFO_KEY)
}

export async function refreshAccessToken() {
  const refreshToken = getRefreshToken()
  if (!refreshToken) return null
  
  if (refreshingPromise) return refreshingPromise
  
  refreshingPromise = (async () => {
    try {
      const headers = ENABLE_ENCRYPTION 
        ? { 'Content-Type': 'text/plain', 'X-Encrypted': '1' }
        : { 'Content-Type': 'application/json' }
      
      const body = ENABLE_ENCRYPTION
        ? encrypt({ refreshToken })
        : JSON.stringify({ refreshToken })
      
      const response = await fetch('/api/v1/auth/refresh', {
        method: 'POST',
        headers,
        body
      })
      
      let res
      if (ENABLE_ENCRYPTION && response.headers.get('x-encrypted') === '1') {
        const text = await response.text()
        res = decrypt(text)
      } else {
        res = await response.json()
      }
      
      if (res && res.code === 0 && res.data?.token) {
        setToken(res.data.token, res.data.expiresIn || 7200)
        if (res.data.refreshToken) {
          setRefreshToken(res.data.refreshToken)
        }
        return res.data.token
      }
      return null
    } catch (error) {
      return null
    } finally {
      refreshingPromise = null
    }
  })()
  
  return refreshingPromise
}

export function isTokenExpiringSoon() {
  const expireTime = localStorage.getItem(TOKEN_EXPIRE_KEY)
  if (!expireTime) return true
  const now = Date.now()
  const fiveMinutes = 5 * 60 * 1000
  return now > (parseInt(expireTime) - fiveMinutes)
}

export function isLoggedIn() {
  return !!getToken()
}

export async function logout(options = {}) {
  const {
    redirectUrl = '/home-new',
    router = null,
    logoutApi = null,
    showMessage = false
  } = options
  
  try {
    if (logoutApi && typeof logoutApi === 'function') {
      try {
        await logoutApi()
      } catch (error) {}
    }
    
    removeToken()
    
    if (router && typeof router.replace === 'function') {
      return router.replace(redirectUrl).then(() => {
        if (showMessage) {
          return { success: true, message: '已退出登录' }
        }
      }).catch(() => {
        window.location.href = redirectUrl
      })
    } else {
      window.location.href = redirectUrl
      return Promise.resolve()
    }
  } catch (error) {
    removeToken()
    throw error
  }
}

export function requireAuth(redirectUrl = '/home-new?auth=login') {
  if (!isLoggedIn()) {
    setTimeout(() => {
      window.location.href = redirectUrl
    }, 100)
    return false
  }
  return true
}

export function getUserInfo() {
  const userInfoStr = localStorage.getItem(USER_INFO_KEY)
  if (userInfoStr) {
    try {
      return JSON.parse(userInfoStr)
    } catch (e) {
      return null
    }
  }
  return null
}

export function setUserInfo(userInfo) {
  if (!userInfo || typeof userInfo !== 'object') return
  try {
    localStorage.setItem(USER_INFO_KEY, JSON.stringify(userInfo))
  } catch (e) {}
}

export function handleLoginSuccess(data, options = {}) {
  const { token, refreshToken, user, expiresIn } = data
  if (!token) return false
  
  try {
    setToken(token, expiresIn || 7200)
    if (refreshToken) setRefreshToken(refreshToken)
    if (user) setUserInfo(user)
    return true
  } catch (error) {
    return false
  }
}
