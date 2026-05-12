import request from './request'

export const accountApi = {
  getProfitLossStats(params) {
    return request({ url: '/v1/account/stats/profit-loss', method: 'get', params })
  },

  getTransactionList(params) {
    return request({ url: '/v1/account/transaction/list', method: 'get', params })
  },

  getTransactionRecords(params) {
    return request({ url: '/v1/account/transaction-records', method: 'get', params })
  },

  getRechargeRecords(params) {
    return request({ url: '/v1/account/recharge-records', method: 'get', params })
  },

  getWithdrawRecords(params) {
    return request({ url: '/v1/account/withdraw-records', method: 'get', params })
  },

  getReceiveStats() {
    return request({ url: '/v1/account/receive-stats', method: 'get' })
  },

  getBetRecords(params) {
    return request({ url: '/v1/account/bet-records', method: 'get', params })
  },

  getBetStats(params) {
    return request({ url: '/v1/account/bet-stats', method: 'get', params })
  },

  getProfitLoss(params) {
    return request({ url: '/v1/account/profit-loss', method: 'get', params })
  },

  getTransactionTypes() {
    return request({ url: '/v1/account/transaction-types', method: 'get' })
  },

  getRecordFilterOptions() {
    return request({ url: '/v1/account/record-filter-options', method: 'get' })
  }
}
