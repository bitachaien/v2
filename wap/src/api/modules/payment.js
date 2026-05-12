
import request from '../request'

export default {

  getPaymentMethods() {
    return request({
      url: '/v1/payment/methods',
      method: 'get'
    })
  },

  createRecharge(data) {
    return request({
      url: '/v1/recharge/create',
      method: 'post',
      data
    })
  },

  getRechargeStatus(orderId) {
    return request({
      url: `/v1/recharge/status/${orderId}`,
      method: 'get'
    })
  },

  getRechargeRecord(params) {
    return request({
      url: '/v1/recharge/record',
      method: 'get',
      params
    })
  },

  getBankCards() {
    return request({
      url: '/v1/user/bankcards',
      method: 'get'
    })
  },

  addBankCard(data) {
    return request({
      url: '/v1/user/bankcard',
      method: 'post',
      data
    })
  }
}

