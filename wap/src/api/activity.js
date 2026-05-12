
import request from './request'

export const activityApi = {
  
  getActivityList(params = {}) {
    return request({
      url: '/v1/activity/list',
      method: 'get',
      params
    })
  },

  
  getCategoryList() {
    return request({
      url: '/v1/activity/categories',
      method: 'get'
    })
  },

  
  getActivityDetail(id) {
    return request({
      url: `/v1/activity/detail/${id}`,
      method: 'get'
    })
  },

  
  checkReward(activityId) {
    return request({
      url: `/v1/activity/check-reward/${activityId}`,
      method: 'get'
    })
  },

  
  claimReward(data) {
    return request({
      url: '/v1/activity/claim-reward',
      method: 'post',
      data
    })
  },

  getParticipationHistory(params = {}) {
    return request({
      url: '/v1/activity/participation-history',
      method: 'get',
      params
    })
  },

  getPendingRewards() {
    return request({
      url: '/v1/activity/pending-rewards',
      method: 'get'
    })
  }
}
