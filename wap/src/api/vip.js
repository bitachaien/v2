import request from './request'

export const vipApi = {
  
  getLevelConfigs() {
    return request({
      url: '/v1/level-rewards/configs',
      method: 'get'
    })
  },

  
  getRewardInfo() {
    return request({
      url: '/v1/level-rewards',
      method: 'get'
    })
  },

  
  claimReward() {
    return request({
      url: '/v1/level-rewards',
      method: 'post'
    })
  },

  
  getRecords(params) {
    return request({
      url: '/v1/level-rewards/records',
      method: 'get',
      params
    })
  }
}
