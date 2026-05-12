import request from './request'

export const noticeApi = {
  getNoticeList(params) {
    return request({
      url: '/v1/notice/list',
      method: 'get',
      params
    })
  },

  getNoticeDetail(id) {
    return request({
      url: `/v1/notice/detail/${id}`,
      method: 'get'
    })
  },

  markRead(data) {
    return request({
      url: '/v1/notice/mark-read',
      method: 'post',
      data
    })
  },

  getUnreadCount() {
    return request({
      url: '/v1/notice/unread-count',
      method: 'get'
    })
  }
}

export default noticeApi
