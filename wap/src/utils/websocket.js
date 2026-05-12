

import { WEBSOCKET } from '@/constants/lottery'
import { showWarning } from '@/utils/errorHandler'
import { trackWsStatus } from '@/utils/tracker'

class LotteryWebSocket {
  constructor() {
    this.ws = null
    this.url = import.meta.env.VITE_WS_URL || 'ws://localhost:8789'
    this.reconnectAttempts = 0
    this.maxReconnectAttempts = WEBSOCKET.MAX_RECONNECT_ATTEMPTS
    this.reconnectDelay = WEBSOCKET.RECONNECT_DELAY
    this.maxReconnectDelay = WEBSOCKET.MAX_RECONNECT_DELAY
    this.heartbeatInterval = null
    this.pongTimeout = null
    this.subscriptions = new Set()
    this.listeners = new Map()
    this.isConnecting = false
    this.lastPongTime = 0
  }

  
  connect() {
    if (this.ws?.readyState === WebSocket.OPEN || this.isConnecting) {
      return Promise.resolve()
    }

    this.isConnecting = true

    return new Promise((resolve, reject) => {
      try {
        this.ws = new WebSocket(this.url)

        this.ws.onopen = () => {
          this.isConnecting = false
          this.reconnectAttempts = 0
          this.reconnectDelay = WEBSOCKET.RECONNECT_DELAY // 重置重连延迟
          this.lastPongTime = Date.now()
          

          this.send('ping')
          this.startHeartbeat()
          trackWsStatus('connect')
          

          this.subscriptions.forEach(code => {
            this.send('subscribe', { lotteryCode: code })
          })
          

          const token = localStorage.getItem('token')
          if (token) {
            this.send('auth', { token })
          }
          
          resolve()
        }

        this.ws.onmessage = (event) => {
          try {
            const { type, data } = JSON.parse(event.data)
            this.handleMessage(type, data)
          } catch (e) {
            console.error('[WS] 消息解析失败:', e)
          }
        }

        this.ws.onclose = (event) => {
          console.warn('[WS] 连接关闭:', event.code, event.reason, 'wasClean:', event.wasClean)
          this.isConnecting = false
          this.stopHeartbeat()
          trackWsStatus('disconnect', { code: event.code, reason: event.reason })
          

          this.handleMessage('close', { code: event.code, reason: event.reason })
          
          this.tryReconnect()
        }

        this.ws.onerror = (error) => {
          console.error('[WS] 连接错误:', error)
          this.isConnecting = false
          reject(error)
        }
      } catch (error) {
        this.isConnecting = false
        reject(error)
      }
    })
  }

  
  handleMessage(type, data) {

    if (type === 'pong') {
      this.lastPongTime = Date.now()
      this.clearPongTimeout()
      return
    }
    

    const callbacks = this.listeners.get(type) || []
    callbacks.forEach(cb => {
      try {
        cb(data)
      } catch (e) {
        console.error('[WS] 回调执行失败:', e)
      }
    })

    const allCallbacks = this.listeners.get('*') || []
    allCallbacks.forEach(cb => {
      try {
        cb(type, data)
      } catch (e) {
        console.error('[WS] 通用回调执行失败:', e)
      }
    })
  }

  
  send(type, data = {}) {
    if (this.ws?.readyState === WebSocket.OPEN) {
      this.ws.send(JSON.stringify({ type, data }))
    } else {
      console.warn('[WS] 连接未就绪，无法发送消息')
    }
  }

  
  subscribe(lotteryCode) {
    this.subscriptions.add(lotteryCode)
    if (this.ws?.readyState === WebSocket.OPEN) {
      this.send('subscribe', { lotteryCode })
    }
  }

  
  unsubscribe(lotteryCode) {
    this.subscriptions.delete(lotteryCode)
    if (this.ws?.readyState === WebSocket.OPEN) {
      this.send('unsubscribe', { lotteryCode })
    }
  }

  
  on(type, callback) {
    if (!this.listeners.has(type)) {
      this.listeners.set(type, [])
    }
    this.listeners.get(type).push(callback)

    return () => {
      const callbacks = this.listeners.get(type) || []
      const index = callbacks.indexOf(callback)
      if (index > -1) {
        callbacks.splice(index, 1)
      }
    }
  }

  
  off(type, callback) {
    if (callback) {
      const callbacks = this.listeners.get(type) || []
      const index = callbacks.indexOf(callback)
      if (index > -1) {
        callbacks.splice(index, 1)
      }
    } else {
      this.listeners.delete(type)
    }
  }

  
  startHeartbeat() {
    this.stopHeartbeat()
    this.heartbeatInterval = setInterval(() => {
      if (this.ws?.readyState === WebSocket.OPEN) {
        this.send('ping')
        this.startPongTimeout()
      }
    }, WEBSOCKET.HEARTBEAT_INTERVAL)
  }

  stopHeartbeat() {
    if (this.heartbeatInterval) {
      clearInterval(this.heartbeatInterval)
      this.heartbeatInterval = null
    }
    this.clearPongTimeout()
  }
  
  
  startPongTimeout() {
    this.clearPongTimeout()
    this.pongTimeout = setTimeout(() => {
      console.warn('[WS] Pong 超时，连接可能已断开')
      trackWsStatus('error', { reason: 'pong_timeout' })

      if (this.ws) {
        this.ws.close()
      }
    }, WEBSOCKET.PONG_TIMEOUT)
  }
  
  
  clearPongTimeout() {
    if (this.pongTimeout) {
      clearTimeout(this.pongTimeout)
      this.pongTimeout = null
    }
  }

  
  tryReconnect() {
    if (this.reconnectAttempts >= this.maxReconnectAttempts) {
      console.error('[WS] 达到最大重连次数，停止重连')
      trackWsStatus('error', { reason: 'max_retries_exceeded' })
      

      showWarning('实时连接已断开，请刷新页面重试')
      

      this.handleMessage('max_retries', { attempts: this.reconnectAttempts })
      return
    }

    this.reconnectAttempts++
    

    const delay = Math.min(
      this.reconnectDelay * Math.pow(2, this.reconnectAttempts - 1),
      this.maxReconnectDelay
    )
    
    trackWsStatus('reconnect', { attempt: this.reconnectAttempts, delay })

    setTimeout(() => {
      this.connect().catch(() => {

      })
    }, delay)
  }
  
  
  resetReconnect() {
    this.reconnectAttempts = 0
    this.reconnectDelay = WEBSOCKET.RECONNECT_DELAY
  }

  
  disconnect() {
    this.stopHeartbeat()
    this.subscriptions.clear()
    if (this.ws) {
      this.ws.close()
      this.ws = null
    }
  }

  
  get isConnected() {
    return this.ws?.readyState === WebSocket.OPEN
  }
}

export const lotteryWS = new LotteryWebSocket()

export const wsEvents = {

  onBetCreated: (callback) => lotteryWS.on('bet_created', callback),

  onBetSettled: (callback) => lotteryWS.on('bet_settled', callback),

  onBalanceUpdate: (callback) => lotteryWS.on('balance_update', callback),

  onNotification: (callback) => lotteryWS.on('notification', callback),

  onLotteryStatus: (callback) => lotteryWS.on('lottery_status', callback),

  onCountdown: (callback) => lotteryWS.on('countdown', callback),

  onDrawResult: (callback) => lotteryWS.on('draw_result', callback),

  onYuebaoInterest: (callback) => lotteryWS.on('yuebao_interest', callback),

  onYuebaoBalance: (callback) => lotteryWS.on('yuebao_balance', callback),

  onLotteryChatMessage: (callback) => lotteryWS.on('lottery_chat_message', callback),

  onLotteryChatOnline: (callback) => lotteryWS.on('lottery_chat_online', callback),

  onLotteryChatSent: (callback) => lotteryWS.on('lottery_chat_sent', callback),
}

export default lotteryWS
