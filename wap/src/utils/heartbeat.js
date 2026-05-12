import { authApi } from '@/api/auth'
import { getToken, isTokenExpiringSoon, refreshAccessToken } from '@/utils/auth'

const HEARTBEAT_INTERVAL = 30 * 1000
const TOKEN_CHECK_INTERVAL = 5 * 60 * 1000
const MAX_FAIL_COUNT = 3

let heartbeatTimer = null
let tokenCheckTimer = null
let isRunning = false
let failCount = 0
let hasSucceededOnce = false

export const heartbeatService = {
  start(delay = 500) {
    if (isRunning) return
    
    const token = getToken()
    if (!token) return
    
    isRunning = true
    
    setTimeout(() => {
      if (isRunning && getToken()) {
        this.send()
      }
    }, delay)
    
    heartbeatTimer = setInterval(() => {
      this.send()
    }, HEARTBEAT_INTERVAL)
    
    tokenCheckTimer = setInterval(() => {
      this.checkAndRefreshToken()
    }, TOKEN_CHECK_INTERVAL)
    
    this.checkAndRefreshToken()
  },

  stop() {
    if (heartbeatTimer) {
      clearInterval(heartbeatTimer)
      heartbeatTimer = null
    }
    if (tokenCheckTimer) {
      clearInterval(tokenCheckTimer)
      tokenCheckTimer = null
    }
    isRunning = false
  },

  async send() {
    const token = getToken()
    if (!token) {
      this.stop()
      return
    }

    try {
      const res = await authApi.heartbeat()
      if (res.code === 0 || res.code === 200) {
        failCount = 0
        hasSucceededOnce = true
        const serverTime = res.data?.serverTime
        if (serverTime) {
          localStorage.setItem('serverTimeDiff', Date.now() - serverTime * 1000)
        }
      }
    } catch (error) {
      failCount++
    }
  },

  restart() {
    this.stop()
    failCount = 0
    hasSucceededOnce = false
    this.start()
  },

  isActive() {
    return isRunning
  },
  
  async checkAndRefreshToken() {
    const token = getToken()
    if (!token) return
    
    if (isTokenExpiringSoon()) {
      try {
        await refreshAccessToken()
      } catch (error) {
        console.error('Token refresh failed:', error)
      }
    }
  }
}

if (typeof document !== 'undefined') {
  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible' && hasSucceededOnce && !isRunning) {
      if (getToken()) {
        heartbeatService.start()
      }
    }
  })
}

export default heartbeatService
