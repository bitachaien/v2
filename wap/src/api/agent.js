import request from './request'

export const agentApi = {
  
  getAgentInfo() {
    return request({
      url: '/v1/agent/info',
      method: 'get'
    })
  },

  
  getInviteInfo() {
    return request({
      url: '/v1/agent/invite-info',
      method: 'get'
    })
  },

  
  getOverview() {
    return request({
      url: '/v1/agent/overview',
      method: 'get'
    })
  },

  
  getMyStats(params) {
    return request({
      url: '/v1/agent/my-stats',
      method: 'get',
      params
    })
  },

  
  getMyPerformance(params) {
    return request({
      url: '/v1/agent/performance',
      method: 'get',
      params
    })
  },

  
  getMyCommission(params) {
    return request({
      url: '/v1/agent/commission',
      method: 'get',
      params
    })
  },

  
  claimCommission() {
    return request({
      url: '/v1/agent/claim-commission',
      method: 'post'
    })
  },

  
  getSubordinateList(params) {
    return request({
      url: '/v1/agent/subordinate/list',
      method: 'get',
      params
    })
  },

  
  getSubordinateBets(params) {
    return request({
      url: '/v1/agent/subordinate/bets',
      method: 'get',
      params
    })
  },

  
  getSubordinateFinance(params) {
    return request({
      url: '/v1/agent/subordinate/finance',
      method: 'get',
      params
    })
  },

  
  getSubordinateClaims(params) {
    return request({
      url: '/v1/agent/subordinate/claims',
      method: 'get',
      params
    })
  },

  
  createSubAccount(data) {
    return request({
      url: '/v1/agent/create-account',
      method: 'post',
      data
    })
  },

  
  getCommissionRates() {
    return request({
      url: '/v1/agent/commission-rates',
      method: 'get'
    })
  },

  
  calculateCommission(params) {
    return request({
      url: '/v1/agent/calculate-commission',
      method: 'get',
      params
    })
  }
}
