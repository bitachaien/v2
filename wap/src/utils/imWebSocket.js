

class IMWebSocket {
  constructor() {
    this.ws = null
    this.url = import.meta.env.VITE_IM_WS_URL || 'ws://localhost:8789'
    this.reconnectAttempts = 0
    this.maxReconnectAttempts = 10
    this.reconnectDelay = 3000
    this.maxReconnectDelay = 30000
    this.heartbeatInterval = null
    this.pongTimeout = null
    this.listeners = new Map()
    this.isConnecting = false
    this.isAuthenticated = false  // 是否已认证
    this.messageQueue = [] // 离线消息队列
    this.connectPromise = null   // 当前连接 Promise
    this.connectResolve = null   // 连接 Promise resolve
    this.connectReject = null    // 连接 Promise reject
  }

  
  connect() {
    if (this.ws?.readyState === WebSocket.OPEN && this.isAuthenticated) {
      return Promise.resolve()
    }
    
    if (this.isConnecting && this.connectPromise) {
      return this.connectPromise
    }

    this.isConnecting = true

    this.connectPromise = new Promise((resolve, reject) => {
      this.connectResolve = resolve
      this.connectReject = reject
      
      try {
        this.ws = new WebSocket(this.url)

        this.ws.onopen = () => {
          this.isConnecting = false
          this.reconnectAttempts = 0
          this.reconnectDelay = 3000
          
          this.ws.send(JSON.stringify({ type: 'ping', data: {} }))
          
          const token = localStorage.getItem('token')
          if (token) {
            this.ws.send(JSON.stringify({ type: 'auth', data: { token } }))
          } else {
            console.warn('[IM-WS] 未找到 token，无法认证')
            this.connectReject?.(new Error('No token'))
            this.connectResolve = null
            this.connectReject = null
          }
        }

        this.ws.onmessage = (event) => {
          try {
            const { type, data } = JSON.parse(event.data)
            this.handleMessage(type, data)
          } catch (e) {
            console.error('[IM-WS] 消息解析失败:', e)
          }
        }

        this.ws.onclose = (event) => {
          console.warn('[IM-WS] 连接关闭:', event.code)
          this.isConnecting = false
          this.isAuthenticated = false  // 重置认证状态
          this.stopHeartbeat()
          this.handleMessage('disconnect', { code: event.code })
          this.tryReconnect()
        }

        this.ws.onerror = (error) => {
          console.error('[IM-WS] 连接错误:', error)
          this.isConnecting = false
          reject(error)
        }
      } catch (error) {
        this.isConnecting = false
        this.connectPromise = null
        reject(error)
      }
    })
    
    return this.connectPromise
  }

  
  handleMessage(type, data) {
    if (type === 'auth_success') {
      this.isAuthenticated = true
      this.isConnecting = false
      this.startHeartbeat()
      this.flushMessageQueue()
      this.connectResolve?.()
      this.connectResolve = null
      this.connectReject = null
      this.connectPromise = null
      return
    }
    
    if (type === 'auth_fail' || type === 'auth_error' || type === 'auth_failed') {
      console.error('[IM-WS] 认证失败:', data)
      this.isAuthenticated = false
      this.isConnecting = false
      this.connectReject?.(new Error(data?.message || '认证失败'))
      this.connectResolve = null
      this.connectReject = null
      this.connectPromise = null
      return
    }
    
    if (type === 'pong') {
      this.clearPongTimeout()
      return
    }
    
    const callbacks = this.listeners.get(type) || []
    callbacks.forEach(cb => {
      try {
        cb(data)
      } catch (e) {
        console.error('[IM-WS] 回调执行失败:', e)
      }
    })

    const allCallbacks = this.listeners.get('*') || []
    allCallbacks.forEach(cb => {
      try {
        cb(type, data)
      } catch (e) {
        console.error('[IM-WS] 通用回调执行失败:', e)
      }
    })
  }

  
  send(type, data = {}) {
    const message = JSON.stringify({ type, data })
    
    const noAuthRequired = ['ping', 'auth']
    
    if (this.ws?.readyState === WebSocket.OPEN) {
      if (this.isAuthenticated || noAuthRequired.includes(type)) {
        this.ws.send(message)
      } else {
        this.messageQueue.push({ type, data, time: Date.now() })
        console.warn('[IM-WS] 尚未认证，消息已加入队列, type:', type)
      }
    } else {
      this.messageQueue.push({ type, data, time: Date.now() })
      console.warn('[IM-WS] 连接未就绪，消息已加入队列, wsState:', this.ws?.readyState, 'type:', type)
    }
  }

  
  flushMessageQueue() {
    while (this.messageQueue.length > 0) {
      const { type, data } = this.messageQueue.shift()
      this.send(type, data)
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
    }, 20000)
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
      console.warn('[IM-WS] Pong 超时')
      if (this.ws) {
        this.ws.close()
      }
    }, 10000)
  }
  
  clearPongTimeout() {
    if (this.pongTimeout) {
      clearTimeout(this.pongTimeout)
      this.pongTimeout = null
    }
  }

  
  tryReconnect() {
    if (this.reconnectAttempts >= this.maxReconnectAttempts) {
      console.error('[IM-WS] 达到最大重连次数')
      this.handleMessage('max_retries', { attempts: this.reconnectAttempts })
      return
    }

    this.reconnectAttempts++
    const delay = Math.min(
      this.reconnectDelay * Math.pow(2, this.reconnectAttempts - 1),
      this.maxReconnectDelay
    )
    

    setTimeout(() => {
      this.connect().catch(() => {})
    }, delay)
  }

  
  disconnect() {
    this.stopHeartbeat()
    if (this.ws) {
      this.ws.close()
      this.ws = null
    }
  }

  
  get isConnected() {
    return this.ws?.readyState === WebSocket.OPEN
  }
}

export const imWS = new IMWebSocket()

export const IM_MSG_TYPES = {
  SEND: 'im_send',                    // 发送消息
  READ: 'im_read',                    // 标记已读
  TYPING: 'im_typing',                // 正在输入
  RECALL: 'im_recall',                // 撤回消息
  
  SENT: 'im_sent',                    // 发送成功确认
  MESSAGE: 'im_message',              // 收到新消息
  NOTICE: 'im_notice',                // 系统通知
  CONVERSATION_UPDATE: 'im_conversation_update',  // 会话列表更新
  UNREAD_UPDATE: 'im_unread_update',  // 未读数变更
  FRIEND_REQUEST: 'im_friend_request', // 收到好友申请
  FRIEND_ACCEPTED: 'im_friend_accepted', // 好友申请通过
  PRESENCE: 'im_presence',            // 在线状态变更
  GROUP_NOTICE: 'im_group_notice',    // 群组通知
}

export const TARGET_TYPE = {
  PRIVATE: 1,  // 私聊
  GROUP: 2,    // 群聊
}

export const MSG_TYPE = {
  TEXT: 1,       // 文本
  IMAGE: 2,      // 图片
  VOICE: 3,      // 语音
  VIDEO: 4,      // 视频
  FILE: 5,       // 文件
  REDPACKET: 6,  // 红包
}

export default imWS
