
import request from './request'

export const levelRewardApi = {
  
  getInfo() {
    return request({
      url: '/v1/level-rewards',
      method: 'get'
    })
  },

  
  claim() {
    return request({
      url: '/v1/level-rewards',
      method: 'post'
    })
  },

  
  getRecords(params = {}) {
    return request({
      url: '/v1/level-rewards/records',
      method: 'get',
      params: {
        pageIndex: params.pageIndex || params.page || 1,
        pageSize: params.pageSize || 20
      }
    })
  },

  
  getConfigs() {
    return request({
      url: '/v1/level-rewards/configs',
      method: 'get'
    })
  }
}

export default levelRewardApi
