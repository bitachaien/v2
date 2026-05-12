import request from './request'

export const hemaiApi = {
  
  async getList(params = {}) {
    return request({ url: '/v1/hemai/list', method: 'get', params })
  },

  
  async getDetail(id) {
    return request({ url: `/v1/hemai/detail/${id}`, method: 'get' })
  },

  
  async getUsers(id) {
    return request({ url: `/v1/hemai/users/${id}`, method: 'get' })
  },

  
  async buy(data) {
    return request({ url: '/v1/hemai/buy', method: 'post', data })
  },

  
  async create(data) {
    return request({ url: '/v1/hemai/create', method: 'post', data })
  },

  
  async cancelJoin(data) {
    return request({ url: '/v1/hemai/cancel-join', method: 'post', data })
  },

  
  async getShareInfo(id) {
    return request({ url: `/v1/hemai/share/${id}`, method: 'get' })
  },

  
  async getMyRecords(params) {
    return request({ url: '/v1/hemai/my-records', method: 'get', params })
  },

  
  async cancel(id) {
    return request({ url: `/v1/hemai/cancel/${id}`, method: 'post' })
  },

  
  async getLotteryList() {
    return request({ url: '/v1/lottery/list', method: 'get' })
  },

  
  async getNextIssue(code) {
    return request({ url: `/v1/lottery/next-issue/${code}`, method: 'get' })
  }
}
