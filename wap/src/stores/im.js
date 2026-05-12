
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { imWS, IM_MSG_TYPES, TARGET_TYPE, MSG_TYPE } from '@/utils/imWebSocket'
import {
  fetchConversations as apiGetConversations,
  fetchMessages as apiGetMessages,
  fetchUnreadCount as apiGetUnread,
  markAsRead as apiMarkRead,
  setConversationTop as apiSetTop,
  deleteConversation as apiDeleteConv,
  setConversationMute as apiSetMute
} from '@/api/im'

export const useImStore = defineStore('im', () => {
  
  
  const conversations = ref([])
  
  
  const notifications = ref([])
  
  
  const onlineUsers = ref(new Set())
  
  
  const mutedConversations = ref(new Set())
  
  
  const currentMessages = ref([])
  
  
  const currentChatId = ref(null)
  
  
  const wsInitialized = ref(false)
  
  
  let initPromise = null
  
  
  const typingUsers = ref({})
  
  
  const friendRequestCount = ref(0)

  
  
  const totalUnreadCount = computed(() => {
    return conversations.value.reduce((sum, conv) => sum + (conv.unreadCount || 0), 0)
  })
  
  
  const unreadNotifyCount = computed(() => {
    return notifications.value.filter(n => !n.isRead).length
  })

  
  
  async function initIM() {
    if (wsInitialized.value && imWS.isAuthenticated) {
      return Promise.resolve()
    }
    
    if (initPromise) {
      return initPromise
    }
    
    initPromise = (async () => {
      if (!wsInitialized.value) {
        imWS.on(IM_MSG_TYPES.MESSAGE, handleWsMessage)
        
        imWS.on(IM_MSG_TYPES.SENT, handleWsSent)
        
        imWS.on('im_error', handleWsError)
        
        imWS.on(IM_MSG_TYPES.NOTICE, handleWsNotice)
        
        imWS.on(IM_MSG_TYPES.CONVERSATION_UPDATE, handleConversationUpdate)
        
        imWS.on(IM_MSG_TYPES.UNREAD_UPDATE, handleUnreadUpdate)
        
        imWS.on(IM_MSG_TYPES.PRESENCE, handlePresence)
        
        imWS.on(IM_MSG_TYPES.TYPING, handleTyping)
        
        imWS.on(IM_MSG_TYPES.RECALL, handleRecall)
        
        imWS.on(IM_MSG_TYPES.FRIEND_REQUEST, handleFriendRequest)
        
        imWS.on(IM_MSG_TYPES.FRIEND_ACCEPTED, handleFriendAccepted)
        
        imWS.on(IM_MSG_TYPES.GROUP_NOTICE, handleGroupNotice)
        
        wsInitialized.value = true
      }
      
      try {
        await imWS.connect()
      } catch (e) {
        console.error('[IM Store] WebSocket 连接失败:', e)
        throw e  
      } finally {
        initPromise = null
      }
    })()
    
    return initPromise
  }
  
  
  async function loadConversations() {
    try {
      const res = await apiGetConversations()
      const list = Array.isArray(res?.data) ? res.data : (Array.isArray(res?.data?.list) ? res.data.list : [])
      conversations.value = list
    } catch (e) {
      console.error('加载会话列表失败:', e)
      conversations.value = []
    }
  }
  
  async function loadUnreadCount() {
    try {
      const res = await apiGetUnread()
      if (res.data?.list) {
        res.data.list.forEach(item => {
          const conv = conversations.value.find(
            c => c.targetType === item.targetType && c.targetId === item.targetId
          )
          if (conv) {
            conv.unreadCount = item.unreadCount
          }
        })
      }
      return res.data?.total || 0
    } catch (e) {
      console.error('加载未读数失败:', e)
      return 0
    }
  }
  
  
  async function loadMessages(targetType, targetId, params = {}) {
    currentChatId.value = `${targetType}_${targetId}`
    try {
      const res = await apiGetMessages({
        targetType,
        targetId,
        ...params
      })
      const messages = Array.isArray(res?.data) ? res.data : []
      if (params.lastMsgId) {
        currentMessages.value.unshift(...messages)
      } else {
        currentMessages.value = messages
      }
      return messages
    } catch (e) {
      if (!e.message?.includes('404')) {
        console.error('加载消息失败:', e)
      }
      if (!params.lastMsgId) {
        currentMessages.value = []
      }
      return []
    }
  }
  
  
  function sendMessage(targetType, targetId, content, msgType = MSG_TYPE.TEXT) {
    const tempId = `temp_${Date.now()}`
    const message = {
      id: tempId,
      targetType,
      targetId,
      content,
      msgType,
      sendTime: new Date().toISOString(),
      status: 'sending',
      isSelf: true
    }
    
    currentMessages.value.push(message)
    
    imWS.send(IM_MSG_TYPES.SEND, {
      targetType,
      targetId,
      content,
      msgType,
      tempId
    })
    
    return tempId
  }
  
  
  function handleWsMessage(data) {
    if (!data) return
    
    let targetType, targetId, message
    
    if (data.message) {
      targetType = data.targetType
      targetId = data.targetId
      message = data.message
    } else if (data.msgId || data.content !== undefined) {
      const currentUserId = Number(localStorage.getItem('userId'))
      targetType = data.targetType || TARGET_TYPE.PRIVATE
      targetId = data.fromUid === currentUserId ? data.toUid : data.fromUid
      message = {
        id: data.msgId,
        content: data.content,
        msgType: data.msgType || 1,
        sendTime: data.createdAt || Date.now(),
        fromId: data.fromUid,
        sender: data.sender || {
          userId: data.fromUid,
          nickname: data.fromName,
          avatar: data.fromAvatar
        }
      }
    } else {
      console.warn('[IM Store] 无法解析的消息格式:', data)
      return
    }
    
    const chatId = `${targetType}_${targetId}`
    const sender = message.sender || {}
    
    const conv = conversations.value.find(
      c => c.targetType === targetType && c.targetId === targetId
    )
    if (conv) {
      conv.lastMessage = message.content || ''
      conv.lastTime = message.sendTime
      
      if (currentChatId.value !== chatId) {
        conv.unreadCount = (conv.unreadCount || 0) + 1
      }
      
      if (!conv.isTop) {
        const index = conversations.value.indexOf(conv)
        conversations.value.splice(index, 1)
        const insertIndex = conversations.value.findIndex(c => !c.isTop)
        conversations.value.splice(insertIndex === -1 ? conversations.value.length : insertIndex, 0, conv)
      }
    } else {
      conversations.value.unshift({
        targetType,
        targetId,
        targetName: sender.nickname || sender.name || '未知用户',
        targetAvatar: sender.avatar || '',
        lastMessage: message.content || '',
        lastTime: message.sendTime,
        unreadCount: currentChatId.value !== chatId ? 1 : 0
      })
    }
    
    if (currentChatId.value === chatId) {
      currentMessages.value.push({
        ...message,
        isSelf: false
      })
      
      sendReadReceipt(targetType, targetId)
    }
  }
  
  
  function handleWsSent(data) {
    const { tempId, messageId, msgId } = data
    const finalMessageId = messageId || msgId  // 兼容两种字段名
    
    if (!tempId) return
    
    const msg = currentMessages.value.find(m => m.id === tempId)
    if (msg) {
      msg.id = finalMessageId
      msg.status = 'sent'
    }
  }
  
  
  function handleWsError(data) {
    const { tempId, message } = data
    
    if (tempId) {
      const msg = currentMessages.value.find(m => m.id === tempId)
      if (msg) {
        msg.status = 'failed'
      }
    }
    
    console.warn('[IM Store] 发送失败:', message)
  }
  
  
  function handleWsNotice(data) {
    notifications.value.unshift({
      ...data,
      isRead: false,
      receiveTime: new Date().toISOString()
    })
  }
  
  
  function handleConversationUpdate(data) {
    if (!data) return
    if (Array.isArray(data.list)) {
      conversations.value = data.list
    } else if (data.conversation) {
      const conv = data.conversation
      const index = conversations.value.findIndex(
        c => c.targetType === conv.targetType && c.targetId === conv.targetId
      )
      if (index > -1) {
        conversations.value[index] = { ...conversations.value[index], ...conv }
      } else {
        conversations.value.unshift(conv)
      }
    }
  }
  
  
  function handleUnreadUpdate(data) {
  }
  
  
  function handlePresence(data) {
    if (!data) return
    const { userId, isOnline } = data
    if (isOnline) {
      onlineUsers.value.add(userId)
    } else {
      onlineUsers.value.delete(userId)
    }
  }
  
  
  function handleTyping(data) {
    if (!data) return
    const { targetType, targetId, fromId } = data
    const chatId = `${targetType}_${targetId}`
    
    typingUsers.value[chatId] = {
      userId: fromId,
      timestamp: Date.now()
    }
    
    setTimeout(() => {
      if (typingUsers.value[chatId]?.timestamp <= Date.now() - 5000) {
        delete typingUsers.value[chatId]
      }
    }, 5000)
  }
  
  
  function handleRecall(data) {
    if (!data) return
    const { msgId, targetType, targetId } = data
    
    const msg = currentMessages.value.find(m => m.id === msgId)
    if (msg) {
      msg.isRecalled = true
      msg.content = '消息已撤回'
    }
    
    const conv = conversations.value.find(
      c => c.targetType === targetType && c.targetId === targetId
    )
    if (conv && conv.lastMsgId === msgId) {
      conv.lastMessage = '消息已撤回'
    }
  }
  
  
  function handleFriendRequest(data) {
    if (!data) return
    friendRequestCount.value++
    notifications.value.unshift({
      id: `fr_${Date.now()}`,
      type: 'friend_request',
      title: '新的好友请求',
      content: `${data.nickname || '用户'} 请求添加你为好友`,
      data,
      isRead: false,
      receiveTime: new Date().toISOString()
    })
  }
  
  
  function handleFriendAccepted(data) {
    if (!data) return
    notifications.value.unshift({
      id: `fa_${Date.now()}`,
      type: 'friend_accepted',
      title: '好友添加成功',
      content: `${data.nickname || '用户'} 已同意你的好友请求`,
      data,
      isRead: false,
      receiveTime: new Date().toISOString()
    })
  }
  
  
  function handleGroupNotice(data) {
    if (!data) return
    const { action, groupId, groupName } = data
    let content = ''
    switch (action) {
      case 'invited':
        content = `你已被邀请加入群聊「${groupName || groupId}」`
        break
      case 'kicked':
        content = `你已被移出群聊「${groupName || groupId}」`
        break
      case 'dissolved':
        content = `群聊「${groupName || groupId}」已解散`
        break
      default:
        content = data.content || '群组通知'
    }
    notifications.value.unshift({
      id: `gn_${Date.now()}`,
      type: 'group_notice',
      title: '群组通知',
      content,
      data,
      isRead: false,
      receiveTime: new Date().toISOString()
    })
  }
  
  
  function sendTyping(targetType, targetId) {
    imWS.send(IM_MSG_TYPES.TYPING, { targetType, targetId })
  }
  
  
  function recallMessage(msgId) {
    imWS.send(IM_MSG_TYPES.RECALL, { msgId })
  }
  
  
  function isTyping(chatId) {
    const typing = typingUsers.value[chatId]
    return typing && (Date.now() - typing.timestamp < 5000)
  }
  
  
  function updateOnlineStatus(userIds) {
    onlineUsers.value = new Set(userIds)
  }
  
  
  function isUserOnline(userId) {
    return onlineUsers.value.has(userId)
  }
  
  
  async function toggleTopConversation(chatId) {
    const [targetType, targetId] = chatId.split('_').map(Number)
    const conv = conversations.value.find(
      c => c.targetType === targetType && c.targetId === targetId
    )
    if (!conv) return
    
    const newTop = !conv.isTop
    
    try {
      await apiSetTop({ targetType, targetId, isTop: newTop })
      conv.isTop = newTop
      
      conversations.value.sort((a, b) => {
        if (a.isTop !== b.isTop) return b.isTop ? 1 : -1
        return new Date(b.lastTime) - new Date(a.lastTime)
      })
    } catch (e) {
      console.error('置顶失败:', e)
    }
  }
  
  
  async function deleteConversation(chatId) {
    const [targetType, targetId] = chatId.split('_').map(Number)
    
    try {
      await apiDeleteConv({ targetType, targetId })
      const index = conversations.value.findIndex(
        c => c.targetType === targetType && c.targetId === targetId
      )
      if (index > -1) {
        conversations.value.splice(index, 1)
      }
    } catch (e) {
      console.error('删除会话失败:', e)
    }
  }
  
  
  function markNotifyRead(notifyId) {
    const notify = notifications.value.find(n => n.id === notifyId)
    if (notify) {
      notify.isRead = true
    }
  }
  
  
  async function markConversationRead(targetType, targetId) {
    const conv = conversations.value.find(
      c => c.targetType === targetType && c.targetId === targetId
    )
    if (conv) {
      conv.unreadCount = 0
    }
    try {
      await apiMarkRead({ targetType, targetId })
    } catch (e) {
      console.error('标记已读失败:', e)
    }
  }
  
  
  function sendReadReceipt(targetType, targetId) {
    imWS.send(IM_MSG_TYPES.READ, {
      targetType,
      targetId
    })
  }
  
  
  async function toggleMute(chatId) {
    const [targetType, targetId] = chatId.split('_').map(Number)
    const isMutedNow = mutedConversations.value.has(chatId)
    const newMute = !isMutedNow
    
    try {
      await apiSetMute({ targetType, targetId, isMuted: newMute })
      if (newMute) {
        mutedConversations.value.add(chatId)
      } else {
        mutedConversations.value.delete(chatId)
      }
    } catch (e) {
      console.error('静音设置失败:', e)
    }
  }
  
  
  function isMuted(chatId) {
    return mutedConversations.value.has(chatId)
  }
  
  
  function clearCurrentChat() {
    currentChatId.value = null
    currentMessages.value = []
  }
  
  
  function cleanup() {
    imWS.off(IM_MSG_TYPES.MESSAGE)
    imWS.off(IM_MSG_TYPES.SENT)
    imWS.off(IM_MSG_TYPES.NOTICE)
    imWS.off(IM_MSG_TYPES.CONVERSATION_UPDATE)
    imWS.off(IM_MSG_TYPES.UNREAD_UPDATE)
    imWS.off(IM_MSG_TYPES.PRESENCE)
    imWS.off(IM_MSG_TYPES.TYPING)
    imWS.off(IM_MSG_TYPES.RECALL)
    imWS.off(IM_MSG_TYPES.FRIEND_REQUEST)
    imWS.off(IM_MSG_TYPES.FRIEND_ACCEPTED)
    imWS.off(IM_MSG_TYPES.GROUP_NOTICE)
    imWS.off('im_error')
    imWS.disconnect()
    wsInitialized.value = false
    conversations.value = []
    notifications.value = []
    onlineUsers.value = new Set()
    mutedConversations.value = new Set()
    currentMessages.value = []
    currentChatId.value = null
  }

  return {
    conversations,
    notifications,
    onlineUsers,
    currentMessages,
    currentChatId,
    typingUsers,
    friendRequestCount,
    
    totalUnreadCount,
    unreadNotifyCount,
    
    initIM,
    loadConversations,
    loadUnreadCount,
    loadMessages,
    sendMessage,
    sendTyping,
    recallMessage,
    isTyping,
    handleWsMessage,
    handleWsNotice,
    updateOnlineStatus,
    isUserOnline,
    toggleTopConversation,
    deleteConversation,
    markNotifyRead,
    markConversationRead,
    sendReadReceipt,
    toggleMute,
    isMuted,
    clearCurrentChat,
    cleanup
  }
})

export default useImStore
