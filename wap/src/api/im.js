
import request from './request'

export function fetchConversations() {
  return request.get('/im/conversations')
}

export function fetchMessages(params) {
  return request.get('/im/messages', { params })
}

export function sendMessage(data) {
  return request.post('/im/send', data)
}

export function fetchUnreadCount() {
  return request.get('/im/unread')
}

export function markAsRead(data) {
  return request.post('/im/read', data)
}

export function fetchContacts() {
  return request.get('/im/contacts')
}

export function fetchFriendRequests() {
  return request.get('/im/friend-requests')
}

export function sendFriendRequest(data) {
  return request.post('/im/friend-request', data)
}

export function handleFriendRequest(data) {
  return request.post('/im/friend-request/handle', data)
}

export function setFriendRemark(data) {
  return request.post('/im/friend/remark', data)
}

export function blockUser(data) {
  return request.post('/im/friend/block', data)
}

export function deleteFriend(data) {
  return request.post('/im/friend/delete', data)
}

export function fetchGroups() {
  return request.get('/im/groups')
}

export function createGroup(data) {
  return request.post('/im/group/create', data)
}

export function fetchGroupMembers(groupId) {
  return request.get(`/im/group/${groupId}/members`)
}

export function inviteToGroup(groupId, data) {
  return request.post(`/im/group/${groupId}/invite`, data)
}

export function kickFromGroup(groupId, data) {
  return request.post(`/im/group/${groupId}/kick`, data)
}

export function setGroupAdmin(groupId, data) {
  return request.post(`/im/group/${groupId}/admin`, data)
}

export function quitGroup(groupId) {
  return request.post(`/im/group/${groupId}/quit`)
}

export function setConversationTop(data) {
  return request.post('/im/conversation/top', data)
}

export function deleteConversation(data) {
  return request.post('/im/conversation/delete', data)
}

export function setConversationMute(data) {
  return request.post('/im/conversation/mute', data)
}

export function uploadFile(formData) {
  return request({
    url: '/im/upload',
    method: 'post',
    data: formData
  })
}

export function searchUser(keyword) {
  return request.get('/im/user/search', { params: { keyword } })
}

export function fetchUserInfo(userId) {
  return request.get('/im/user', { params: { userId } })
}

export function updateUserAvatar(avatar) {
  return request.post('/im/user/avatar', { avatar })
}

export function contactCustomerService() {
  return request.get('/im/customer-service')
}
