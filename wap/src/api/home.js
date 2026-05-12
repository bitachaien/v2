import request from './request'

export const homeApi = {
  getIndexData() {
    return request({
      url: '/v1/index',
      method: 'get'
    })
  },

  getBanners() {
    return request({
      url: '/v1/banners',
      method: 'get'
    })
  },

  getNotices(params = {}) {
    return request({
      url: '/v1/notices',
      method: 'get',
      params
    })
  },

  getRealtimeDraws(params = {}) {
    return request({
      url: '/v1/home/realtime-draws',
      method: 'get',
      params
    })
  },

  getLotteryCurrentInfo(code) {
    return request({
      url: '/v1/lottery/current',
      method: 'get',
      params: { code }
    })
  },

  getLotteryHall() {
    return request({
      url: '/v1/lottery-hall',
      method: 'get'
    })
  },

  getConfig() {
    return request({
      url: '/v1/config',
      method: 'get'
    })
  }
}

export default homeApi
