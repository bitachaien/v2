
import request from './request'

function formatBetCode(betData) {
  if (!betData || !betData.selections || betData.selections.length === 0) {
    return ''
  }

  return betData.selections.map(s => s.value).join(',')
}

export const xy28Api = {

  
  getCurrentExpect(lotteryCode = 'xy28') {
    return request({
      url: `/v1/lottery/${lotteryCode}/expect`,
      method: 'get'
    })
  },

  
  getLastResult(lotteryCode = 'xy28') {
    return request({
      url: `/v1/lottery/${lotteryCode}/last-result`,
      method: 'get'
    })
  },

  
  getHistory(lotteryCode = 'xy28', params = {}) {
    return request({
      url: `/v1/lottery/${lotteryCode}/history`,
      method: 'get',
      params: {
        page: 1,
        pageSize: 20,
        ...params
      }
    })
  },

  
  getPlayTypes(lotteryCode = 'xy28') {
    return request({
      url: `/v1/lottery/${lotteryCode}/play-types`,
      method: 'get'
    })
  },

  
  getHotCold(lotteryCode = 'xy28', limit = 100) {
    return request({
      url: `/v1/lottery/${lotteryCode}/hot-cold`,
      method: 'get',
      params: { limit }
    })
  },

  
  getUserSettings() {
    return request({
      url: '/v1/user/settings',
      method: 'get'
    })
  },

  
  saveUserSetting(key, value) {
    return request({
      url: '/v1/user/settings',
      method: 'post',
      data: { key, value }
    })
  },

  
  getBoseConfig(lotteryCode = 'xy28') {
    return request({
      url: `/v1/lottery/${lotteryCode}/bose-config`,
      method: 'get'
    })
  },

  
  submitBet(data) {

    const selections = data.betData.selections || []

    if (data.playType === 'xy28_combined') {

      const betItems = selections.map(s => ({
        playid: s.value,
        label: s.label,
        amount: s.amount || data.unitPrice
      }))

      const postData = {
        lotteryname: data.lotteryCode,
        expect: data.expect,
        playid: 'xy28_combined',
        tzcode: JSON.stringify(betItems),  // JSON 格式
        amount: data.totalAmount,
        beishu: 1,
        mode: 1,
        itemcount: selections.length,
        yjf: '元',
        ishemai: 0
      }

      return request({
        url: '/v1/bet/submit',
        method: 'post',
        data: postData
      })
    }

    const firstSelection = selections[0]
    const playId = firstSelection?.value || firstSelection?.playId || data.playType

    const tzcode = formatBetCode(data.betData)

    const postData = {
      lotteryname: data.lotteryCode,           // lotteryCode → lotteryname
      expect: data.expect,                      // 期号保持不变
      playid: playId,                           // 使用具体的玩法ID，如 yfxy28_tm_08
      tzcode: tzcode,                           // betData → tzcode (转换为字符串)
      amount: data.totalAmount,                 // totalAmount → amount
      beishu: data.multiplier,                  // multiplier → beishu
      mode: 1,                                  // 固定为1
      itemcount: data.betCount,                 // betCount → itemcount
      yjf: '元',                                // 固定为"元"
      ishemai: 0,                               // 固定为0（非合买）

      nums: data.betCount                       // 号码个数（与itemcount相同）
    }

    return request({
      url: '/v1/bet/submit',
      method: 'post',
      data: postData
    })
  },

  
  submitChase(data) {

    const chaseConfig = data.chaseConfig || {}
    const selections = data.betData?.selections || []
    

    const multipliers = chaseConfig.multipliers || []
    const plans = []
    const startExpect = chaseConfig.startExpect || ''
    

    for (let i = 0; i < (chaseConfig.periods || 1); i++) {
      const expectNum = parseInt(startExpect) + i
      plans.push({
        expect: String(expectNum),
        beishu: multipliers[i] || data.multiplier || 1
      })
    }

    let tzcode = ''
    let playid = data.playType || ''
    
    if (selections.length > 0) {

      if (selections.length > 1 || data.playType === 'xy28_combined') {
        playid = 'xy28_combined'
        tzcode = JSON.stringify(selections.map(s => ({
          playid: s.value || s.playId,
          label: s.label,
          amount: s.amount || data.unitPrice
        })))
      } else {
        playid = selections[0]?.value || data.playType
        tzcode = selections[0]?.label || selections[0]?.value || ''
      }
    }

    const postData = {
      lotteryname: data.lotteryCode,
      playid: playid,
      tzcode: tzcode,
      plans: plans,
      mode: 1,
      itemcount: selections.length || 1,
      amount: data.unitPrice || 1,
      yjf: '元',
      stopOnWin: chaseConfig.stopOnWin ? 1 : 0
    }

    return request({
      url: '/v1/bet/chase',
      method: 'post',
      data: postData
    })
  },

  
  quickBet(data) {
    return request({
      url: '/v1/bet/quick',
      method: 'post',
      data
    })
  },

  
  getBetRecords(params = {}) {
    return request({
      url: '/v1/bet/records',
      method: 'get',
      params: {
        page: 1,
        pageSize: 20,
        ...params
      }
    })
  },

  
  getBetDetail(orderId) {
    return request({
      url: `/v1/bet/records/${orderId}`,
      method: 'get'
    })
  },

  
  getChaseRecords(params = {}) {
    return request({
      url: '/v1/chase/records',
      method: 'get',
      params: {
        page: 1,
        pageSize: 20,
        ...params
      }
    })
  },

  
  getChaseDetail(chaseNo) {
    return request({
      url: `/v1/chase/detail/${chaseNo}`,
      method: 'get'
    })
  },

  
  cancelChase(chaseNo) {
    return request({
      url: `/v1/chase/cancel/${chaseNo}`,
      method: 'post'
    })
  },

  
  getTrendData(lotteryCode = 'xy28', params = {}) {
    return request({
      url: `/v1/lottery/${lotteryCode}/trend`,
      method: 'get',
      params: {
        periods: 30,
        trendType: 'sum',
        ...params
      }
    })
  },

  
  getHotNumbers(lotteryCode = 'xy28', params = {}) {
    return request({
      url: `/v1/lottery/${lotteryCode}/hot-numbers`,
      method: 'get',
      params: {
        periods: 100,
        ...params
      }
    })
  },

  
  getMissingData(lotteryCode = 'xy28') {
    return request({
      url: `/v1/lottery/${lotteryCode}/missing`,
      method: 'get'
    })
  },

  
  createHemai(data) {
    return request({
      url: '/v1/hemai/create',
      method: 'post',
      data
    })
  },

  
  getHemaiList(params = {}) {
    return request({
      url: '/v1/hemai/list',
      method: 'get',
      params: {
        page: 1,
        pageSize: 20,
        ...params
      }
    })
  },

  
  joinHemai(hemaiId, data) {
    return request({
      url: `/v1/hemai/${hemaiId}/join`,
      method: 'post',
      data
    })
  },

  
  getHemaiDetail(hemaiId) {
    return request({
      url: `/v1/hemai/${hemaiId}`,
      method: 'get'
    })
  },

  
  getMyCreatedHemai(params = {}) {
    return request({
      url: '/v1/hemai/my-created',
      method: 'get',
      params: {
        page: 1,
        pageSize: 20,
        ...params
      }
    })
  },

  
  getMyJoinedHemai(params = {}) {
    return request({
      url: '/v1/hemai/my-joined',
      method: 'get',
      params: {
        page: 1,
        pageSize: 20,
        ...params
      }
    })
  },

  
  getUserBalance() {
    return request({
      url: '/v1/user/balance',
      method: 'get'
    })
  },

  
  getBetStatistics(params = {}) {
    return request({
      url: '/v1/user/bet-statistics',
      method: 'get',
      params
    })
  },

  
  getAnnouncements(params = {}) {
    return request({
      url: '/v1/lottery/announcements',
      method: 'get',
      params
    })
  },

  
  submitChatBet(data) {
    return request({
      url: '/v1/lottery/chat-bet',
      method: 'post',
      data
    })
  },

  
  getChatMessages(lotteryCode, params = {}) {
    return request({
      url: `/v1/lottery/${lotteryCode}/chat-messages`,
      method: 'get',
      params: {
        page: 1,
        pageSize: 50,
        ...params
      }
    })
  },

  
  getChatOnlineCount(lotteryCode) {
    return request({
      url: `/v1/lottery/${lotteryCode}/chat-online`,
      method: 'get'
    })
  },

  
  getBetHistory(params = {}) {
    return request({
      url: `/v1/lottery/${params.lotteryCode || 'xy28'}/bet-history`,
      method: 'get',
      params: {
        timeRange: params.timeRange || 'today',
        page: params.page || 1,
        pageSize: params.pageSize || 50
      }
    })
  },

  
  getIssueBetStats(lotteryCode, issue = '') {
    return request({
      url: `/v1/lottery/${lotteryCode}/bet-stats`,
      method: 'get',
      params: { issue }
    })
  },

  
  getIssueBets(lotteryCode, issue) {
    return request({
      url: `/v1/lottery/${lotteryCode}/issue-bets`,
      method: 'get',
      params: { issue }
    })
  },

  
  cancelBet(betId) {
    return request({
      url: `/v1/lottery/bet/${betId}/cancel`,
      method: 'post'
    })
  },

  
  modifyBet(betId, amount, perAmount) {
    return request({
      url: `/v1/lottery/bet/${betId}/modify`,
      method: 'post',
      data: { amount, perAmount }
    })
  }
}

export default xy28Api
