
import request from './request'

function formatBetCode(selections) {
  if (!selections || selections.length === 0) {
    return ''
  }

  return selections.map(s => typeof s === 'object' ? s.value : s).join(',')
}

export const lhcApi = {

  
  getInfo(lotteryCode = 'dflhc') {

    return request({
      url: `/v1/lottery/${lotteryCode}/expect`,
      method: 'get'
    }).then(res => {

      if (res.code === 0 && res.data) {
        return {
          code: 0,
          data: {
            currFullExpect: res.data.currFullExpect,
            lastFullExpect: res.data.lastFullExpect,
            remainTime: Math.floor(res.data.remainMs / 1000),
            closeSeconds: 10,
            lastOpencode: '' // 需要单独调用 lastResult
          }
        }
      }
      return res
    })
  },

  
  getLastResult(lotteryCode = 'dflhc') {
    return request({
      url: `/v1/lottery/${lotteryCode}/last-result`,
      method: 'get'
    })
  },

  
  getHistory(lotteryCode = 'dflhc', params = {}) {
    return request({
      url: `/v1/lottery/${lotteryCode}/history`,
      method: 'get',
      params: {
        page: 1,
        pageSize: params.limit || 20,
        ...params
      }
    }).then(res => {

      if (res.code === 0 && res.data && res.data.list) {
        return {
          code: 0,
          data: res.data.list.map(item => ({
            expect: item.expect,
            opencode: item.openCode ? item.openCode.join(',') : '',
            opentime_format: item.openTime
          }))
        }
      }
      return res
    })
  },

  
  getPlays(lotteryCode = 'dflhc') {
    return request({
      url: `/v1/lottery/${lotteryCode}/play-types`,
      method: 'get'
    })
  },

  
  submitBet(data) {
    const postData = {
      lotteryname: data.lotteryCode,
      expect: data.expect,
      playid: data.playId,
      tzcode: formatBetCode(data.selections),
      amount: data.totalAmount,
      beishu: data.multiplier,
      mode: 1,
      itemcount: data.betCount,
      yjf: '元',
      ishemai: 0,
      nums: data.betCount
    }
    
    
    return request({
      url: '/v1/bet/submit',
      method: 'post',
      data: postData
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

  
  getBetDetail(trano) {
    return request({
      url: `/v1/bet/records/${trano}`,
      method: 'get'
    })
  },

  
  getUserBalance() {
    return request({
      url: '/v1/user/balance',
      method: 'get'
    })
  }
}

export default lhcApi
