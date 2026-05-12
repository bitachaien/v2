
import request from '../request'

export default {

  getLotteryList() {
    return request({
      url: '/lottery/list',
      method: 'get'
    })
  },

  getCurrentExpect(lotteryCode) {
    return request({
      url: `/lottery/${lotteryCode}/expect`,
      method: 'get'
    })
  },

  getOpenHistory(lotteryCode, params) {
    return request({
      url: `/lottery/${lotteryCode}/history`,
      method: 'get',
      params
    })
  },

  submitBet(data) {
    return request({
      url: '/bet/submit',
      method: 'post',
      data
    })
  },

  submitHemai(data) {
    return request({
      url: '/hemai/create',
      method: 'post',
      data
    })
  },

  getBetRecord(params) {
    return request({
      url: '/bet/record',
      method: 'get',
      params
    })
  },

  getHemaiList(params) {
    return request({
      url: '/hemai/list',
      method: 'get',
      params
    })
  }
}

