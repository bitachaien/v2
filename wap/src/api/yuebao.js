import request from './request'

export const yueBaoApi = {
  
  getDashboardInfo() {
    return request({
      url: '/v1/yuebao/info',
      method: 'get'
    })
  },

  
  getProducts() {
    return request({
      url: '/v1/yuebao/products',
      method: 'get'
    })
  },

  
  transferIn(data) {
    return request({
      url: '/v1/yuebao/transfer-in',
      method: 'post',
      data
    })
  },

  
  transferOut(data) {
    return request({
      url: '/v1/yuebao/transfer-out',
      method: 'post',
      data
    })
  },

  
  getRecords(params) {
    return request({
      url: '/v1/yuebao/records',
      method: 'get',
      params
    })
  },

  
  getAnalysisData(params = { days: 7 }) {
    return request({
      url: '/v1/yuebao/analysis',
      method: 'get',
      params
    })
  },

  
  exportBill(data) {
    return request({
      url: '/v1/yuebao/export',
      method: 'post',
      data
    })
  },

  
  getFixedHoldings(params) {
    return request({
      url: '/v1/yuebao/holdings',
      method: 'get',
      params
    })
  },

  
  getConfig() {
    return request({
      url: '/v1/yuebao/config',
      method: 'get'
    })
  },

  
  claimInterest() {
    return request({
      url: '/v1/yuebao/claim',
      method: 'post'
    })
  }
}
