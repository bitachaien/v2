import request from './request'

export const rechargeApi = {
  getMethods() {
    return request({
      url: '/v1/recharge/methods',
      method: 'get'
    })
  },

  getConfig(type) {
    return request({
      url: `/v1/recharge/config/${type}`,
      method: 'get'
    })
  },

  submit(data) {
    return request({
      url: '/v1/recharge/submit',
      method: 'post',
      data
    })
  },

  confirm(trano) {
    return request({
      url: '/v1/recharge/confirm',
      method: 'post',
      data: { trano }
    })
  },

  getStatus(trano) {
    return request({
      url: `/v1/recharge/status/${trano}`,
      method: 'get'
    })
  },

  getRecords(params) {
    return request({
      url: '/v1/recharge/records',
      method: 'get',
      params
    })
  }
}

export default rechargeApi
