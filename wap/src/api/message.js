import request from './request'

export const messageApi = {
  getMessageList(params) {
    return request({
      url: '/v1/message/list',
      method: 'get',
      params
    })
  },

  getMessageDetail(id) {
    return request({
      url: `/v1/message/detail/${id}`,
      method: 'get'
    })
  },

  markRead(ids) {
    return request({
      url: '/v1/message/mark-read',
      method: 'post',
      data: { ids }
    })
  },

  deleteMessage(ids) {
    return request({
      url: '/v1/message/delete',
      method: 'post',
      data: { ids }
    })
  },

  getUnreadCount() {
    return request({
      url: '/v1/message/unread-count',
      method: 'get'
    })
  }
}

export default messageApi
