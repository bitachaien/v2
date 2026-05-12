
import request from './request'

const oddsCache = new Map() // key: code, value: { data, ts }
const ODDS_TTL = 30 * 1000

export default {
  
  getLotteryList() {
    return request({
      url: '/lottery/list',
      method: 'get',
      params: { type: 'k3' }
    })
  },

  
  getCurrentIssue(code) {
    return request({
      url: '/lottery/k3/current',
      method: 'get',
      params: { code }
    })
  },

  
  getHistory(code, limit = 20) {
    return request({
      url: '/lottery/k3/history',
      method: 'get',
      params: { code, limit }
    })
  },

  
  getOdds(code) {
    const now = Date.now()
    const cached = oddsCache.get(code)
    if (cached && (now - cached.ts < ODDS_TTL)) {

      return Promise.resolve({ code: 0, data: cached.data })
    }
    return request({
      url: '/lottery/k3/odds',
      method: 'get',
      params: { code }
    }).then(res => {
      if (res && res.code === 0) {
        oddsCache.set(code, { data: res.data, ts: Date.now() })
      }
      return res
    })
  },

  
  submitBet(data) {
    return request({
      url: '/lottery/k3/bet',
      method: 'post',
      data: {
        ...data,
        source: data.source || 'h5'
      }
    })
  },

  
  submitHemai(data) {
    return request({
      url: '/lottery/k3/hemai',
      method: 'post',
      data: {
        ...data,
        source: data.source || 'h5'
      }
    })
  },

  
  getBalance() {
    return request({
      url: '/user/balance',
      method: 'get'
    })
  },

  
  getBetRecords(params = {}) {
    return request({
      url: '/user/bets',
      method: 'get',
      params: {
        lotteryType: params.lotteryType || 'k3',
        status: params.status || 'all',
        page: params.page || 1,
        pageSize: params.pageSize || 20
      }
    })
  }
}
