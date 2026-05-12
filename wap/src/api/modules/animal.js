import request from '@/api/request'

export default {
  
  getGames() {
    return request({
      url: '/v1/animal/games',
      method: 'get'
    })
  },

  
  getInfo(name = 'yfdwc') {
    return request({
      url: `/v1/animal/${name}/info`,
      method: 'get'
    })
  },

  
  getResult(name, issueNo) {
    return request({
      url: `/v1/animal/${name}/result`,
      method: 'get',
      params: { issueNo }
    })
  },

  
  getHistory(name = 'yfdwc', date = '', page = 1, limit = 20) {
    return request({
      url: `/v1/animal/${name}/history`,
      method: 'get',
      params: { date, page, limit }
    })
  },

  
  getPlays(name = 'yfdwc') {
    return request({
      url: `/v1/animal/${name}/plays`,
      method: 'get'
    })
  },

  
  submitBet(data) {
    return request({
      url: '/v1/animal/bet',
      method: 'post',
      data
    })
  },

  
  getMyBets(name = 'yfdwc', page = 1, limit = 20) {
    return request({
      url: `/v1/animal/${name}/my-bets`,
      method: 'get',
      params: { page, limit }
    })
  },

  
  
  getLotteryPlan(name = 'yfdwc') {
    return this.getInfo(name)
  },

  
  getLotteryResult(issueNo, name = 'yfdwc') {
    return this.getResult(name, issueNo)
  },

  
  getLotteryRecord(date, pageNo = 1, pageSize = 10, name = 'yfdwc') {
    return this.getHistory(name, date, pageNo, pageSize)
  },

  
  getOpenGame() {
    return this.getGames()
  }
}
