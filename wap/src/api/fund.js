import request from './request'

export const fundApi = {
  
  getTodayStats() {
    return request({
      url: '/v1/user/today-stats',
      method: 'get'
    })
  },

  
  getTodayStatsDetail(params) {
    return request({
      url: '/v1/user/today-stats/detail',
      method: 'get',
      params
    })
  },

  
  getBetRecords(params) {
    return request({
      url: '/v1/bet/records',
      method: 'get',
      params
    })
  },

  
  getBetRecordDetail(trano) {
    return request({
      url: `/v1/bet/records/${trano}`,
      method: 'get'
    })
  },

  
  getTransactionRecords(params) {
    return request({
      url: '/v1/account/transaction-records',
      method: 'get',
      params
    })
  },

  
  getTransactionTypes() {
    return request({
      url: '/v1/account/transaction-types',
      method: 'get'
    })
  },

  
  getBillRecords(params) {
    return request({
      url: '/v1/account/bill-records',
      method: 'get',
      params
    })
  },

  
  getRechargeRecords(params) {
    return request({
      url: '/v1/account/recharge-records',
      method: 'get',
      params
    })
  },

  
  getWithdrawRecords(params) {
    return request({
      url: '/v1/account/withdraw-records',
      method: 'get',
      params
    })
  },

  
  getRebateRecords(params) {
    return request({
      url: '/v1/account/rebate-records',
      method: 'get',
      params
    })
  },

  
  submitBet(data) {
    return request({
      url: '/v1/game/bet',
      method: 'post',
      data
    })
  }
}
