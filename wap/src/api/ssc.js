
import request from './request'

const oddsCache = new Map() // key: code, value: { data, ts }
const ODDS_TTL = 30 * 1000

export const SSC_LOTTERIES = {
  'cqssc': '重庆时时彩',
  'xjssc': '新疆时时彩',
  'tjssc': '天津时时彩',
  'txssc': '腾讯分分彩',
  'ssc1fc': '大发分分彩',
  'ssc3fc': '3分彩',
  'ssc5fc': '5分彩',
  'dfssc': '大发2分彩',
  'ssctw5fc': '台湾5分彩'
}

const PLAY_ID_MAP = {

  'sxzxfsq3': 'sxzuxzsq',   // 前三组三
  'sxzxfsq6': 'sxzuxzlq',   // 前三组六
  

  'sxzxfsz3': 'sxzuxzsz',   // 中三组三
  'sxzxfsz6': 'sxzuxzlz',   // 中三组六
  

  'sxzxfsh3': 'sxzuxzsh',   // 后三组三
  'sxzxfsh6': 'sxzuxzlh',   // 后三组六
  

  'lhwq_long': 'lhwql',     // 龙虎万千-龙
  'lhwq_hu': 'lhwqhu',      // 龙虎万千-虎
  'lhwq_he': 'lhwqhe',      // 龙虎万千-和
}

const DOUBLE_SIDE_MAP = {

  'zhd': 'zhlhzhd',         // 总和大
  'zhx': 'zhlhzhx',         // 总和小
  'zhdd': 'zhlhzhdd',       // 总和单
  'zhss': 'zhlhzhss',       // 总和双

  'lh_l': 'zhlhl',          // 龙
  'lh_h': 'zhlhh',          // 虎
  'lh_he': 'zhlhhe',        // 和

  'ww_d': 'sscwwd',         // 万位大
  'ww_x': 'sscwwx',         // 万位小
  'ww_dd': 'sscwwdd',       // 万位单
  'ww_ss': 'sscwwss',       // 万位双

  'qw_d': 'sscqwd',
  'qw_x': 'sscqwx',
  'qw_dd': 'sscqwdd',
  'qw_ss': 'sscqwss',

  'bw_d': 'sscbwd',
  'bw_x': 'sscbwx',
  'bw_dd': 'sscbwdd',
  'bw_ss': 'sscbwss',

  'sw_d': 'sscswd',
  'sw_x': 'sscswx',
  'sw_dd': 'sscswdd',
  'sw_ss': 'sscswss',

  'gw_d': 'sscgwd',
  'gw_x': 'sscgwx',
  'gw_dd': 'sscgwdd',
  'gw_ss': 'sscgwss',
  

  'ssc_q3_豹子': 'sscq3bz',
  'ssc_q3_顺子': 'sscq3sz',
  'ssc_q3_对子': 'sscq3dz',
  'ssc_q3_半顺': 'sscq3bs',
  'ssc_q3_杂六': 'sscq3zl',

  'ssc_z3_豹子': 'sscz3bz',
  'ssc_z3_顺子': 'sscz3sz',
  'ssc_z3_对子': 'sscz3dz',
  'ssc_z3_半顺': 'sscz3bs',
  'ssc_z3_杂六': 'sscz3zl',

  'ssc_h3_豹子': 'ssch3bz',
  'ssc_h3_顺子': 'ssch3sz',
  'ssc_h3_对子': 'ssch3dz',
  'ssc_h3_半顺': 'ssch3bs',
  'ssc_h3_杂六': 'ssch3zl',
}

function convertPlayId(frontendId) {

  if (PLAY_ID_MAP[frontendId]) {
    return PLAY_ID_MAP[frontendId]
  }
  if (DOUBLE_SIDE_MAP[frontendId]) {
    return DOUBLE_SIDE_MAP[frontendId]
  }

  return frontendId
}

export const sscApi = {

  
  getInfo(lotteryCode = 'cqssc') {
    return request({
      url: `/v1/lottery/${lotteryCode}/expect`,
      method: 'get'
    }).then(res => {
      if (res.code === 0 && res.data) {
        return {
          code: 0,
          data: {
            currFullExpect: res.data.currFullExpect || res.data.expect || '',
            lastFullExpect: res.data.lastFullExpect || '',
            remainTime: Math.floor((res.data.remainMs || res.data.remainTime * 1000 || 0) / 1000),
            closeSeconds: res.data.closeSeconds || 10,
            openCodes: res.data.openCodes || (res.data.opencode ? res.data.opencode.split(',') : [])
          }
        }
      }
      return res
    })
  },

  
  getLastResult(lotteryCode = 'cqssc') {
    return request({
      url: `/v1/lottery/${lotteryCode}/last-result`,
      method: 'get'
    }).then(res => {
      if (res.code === 0 && res.data) {
        let opencode = res.data.opencode || res.data.openCode || res.data.kjhm || ''
        if (Array.isArray(opencode)) {
          opencode = opencode.join(',')
        }
        return {
          code: 0,
          data: {
            expect: res.data.expect || res.data.qihao || '',
            opencode: opencode,
            opentime: res.data.opentime || res.data.openTime || ''
          }
        }
      }
      return res
    })
  },

  
  getHistory(lotteryCode = 'cqssc', params = {}) {
    return request({
      url: `/v1/lottery/${lotteryCode}/history`,
      method: 'get',
      params: {
        page: params.page || 1,
        pageSize: params.pageSize || 20,
        ...params
      }
    }).then(res => {
      if (res.code === 0 && res.data) {
        const list = Array.isArray(res.data) ? res.data : (res.data.list || res.data.data || [])
        return {
          code: 0,
          data: {
            list: list.map(item => {
              let opencode = item.opencode || item.openCode || item.kjhm || ''
              if (Array.isArray(opencode)) {
                opencode = opencode.join(',')
              }
              return {
                expect: item.expect || item.qihao || '',
                opencode: opencode,
                opentime: item.opentime || item.openTime || ''
              }
            })
          }
        }
      }
      return res
    })
  },

  
  async submitBet(data) {
    const { 
      lotteryCode = 'cqssc',
      expect,
      playId,
      tzcode,
      amount,
      multiplier = 1,
      mode = 1,
      betCount = 1,
      isHemai = false
    } = data
    

    const backendPlayId = convertPlayId(playId)
    

    let tzcodeStr = tzcode
    if (Array.isArray(tzcode)) {
      tzcodeStr = tzcode.join(' ')
    } else if (typeof tzcode === 'object' && tzcode !== null) {
      tzcodeStr = JSON.stringify(tzcode)
    }
    
    const postData = {
      lotteryname: lotteryCode,
      expect: expect,
      playid: backendPlayId,
      tzcode: tzcodeStr,
      amount: amount,
      beishu: multiplier,
      mode: mode,
      itemcount: betCount,
      yjf: mode === 1 ? '元' : (mode === 0.1 ? '角' : '分'),
      ishemai: isHemai ? 1 : 0,
      nums: betCount
    }
    
    
    return request({
      url: '/v1/bet/submit',
      method: 'post',
      data: postData
    })
  },

  
  async submitDoubleSideBet(data) {
    const {
      lotteryCode = 'cqssc',
      expect,
      selections = [], // [{playId: 'ww_d', amount: 10}, ...]
      isHemai = false
    } = data
    
    let successCount = 0
    let failCount = 0
    let lastError = null
    
    for (const sel of selections) {
      const backendPlayId = convertPlayId(sel.playId)
      
      const postData = {
        lotteryname: lotteryCode,
        expect: expect,
        playid: backendPlayId,
        tzcode: sel.value || sel.playId,
        amount: sel.amount,
        beishu: 1,
        mode: 1,
        itemcount: 1,
        yjf: '元',
        ishemai: isHemai ? 1 : 0,
        nums: 1
      }
      
      
      try {
        const res = await request({
          url: '/v1/bet/submit',
          method: 'post',
          data: postData
        })
        if (res.code === 0) successCount++
        else {
          failCount++
          lastError = res
        }
      } catch (e) {
        failCount++
        lastError = { code: 500, msg: e.message }
      }
    }
    
    if (failCount === 0) {
      return { code: 0, msg: `投注成功，共${successCount}注` }
    } else if (successCount === 0) {
      return lastError || { code: 500, msg: '投注失败' }
    } else {
      return { code: 0, msg: `部分成功：${successCount}成功，${failCount}失败` }
    }
  },

  
  getUserBalance() {
    return request({
      url: '/v1/user/balance',
      method: 'get'
    })
  },

  
  getOdds(lotteryCode = 'cqssc') {
    const now = Date.now()
    const cached = oddsCache.get(lotteryCode)
    if (cached && (now - cached.ts < ODDS_TTL)) {
      return Promise.resolve({ code: 0, data: cached.data })
    }
    return request({
      url: `/v1/lottery/${lotteryCode}/odds`,
      method: 'get'
    }).then(res => {
      if (res && res.code === 0) {
        oddsCache.set(lotteryCode, { data: res.data, ts: Date.now() })
      }
      return res
    })
  },

  
  submitHemai(data) {
    const {
      lotteryCode = 'cqssc',
      expect,
      playId,
      tzcode,
      totalAmount,
      betCount = 1,
      multiplier = 1,
      mode = 1,
      hemaiConfig = {}
    } = data
    
    const backendPlayId = convertPlayId(playId)
    
    let tzcodeStr = tzcode
    if (Array.isArray(tzcode)) {
      tzcodeStr = tzcode.join(' ')
    } else if (typeof tzcode === 'object' && tzcode !== null) {
      tzcodeStr = JSON.stringify(tzcode)
    }
    
    const postData = {
      lotteryname: lotteryCode,
      expect: expect,
      playid: backendPlayId,
      tzcode: tzcodeStr,
      amount: totalAmount,
      beishu: multiplier,
      mode: mode,
      itemcount: betCount,
      nums: betCount,
      ishemai: 1,

      showtype: hemaiConfig.showtype || 0,
      fenshu: hemaiConfig.fenshu || 1,
      rengou: hemaiConfig.rengou || 1,
      isbaodi: hemaiConfig.isbaodi || 0,
      baodi: hemaiConfig.baodi || 0
    }
    
    

    return request({
      url: '/v1/bet/submit',
      method: 'post',
      data: postData
    })
  },

  
  getBetRecords(params = {}) {
    return request({
      url: '/v1/user/bet-records',
      method: 'get',
      params: {
        lotteryType: params.lotteryType || 'ssc',
        status: params.status || 'all',
        page: params.page || 1,
        pageSize: params.pageSize || 20
      }
    })
  },

  
  
  calculateFushiBetCount(positions) {

    return positions.reduce((acc, pos) => {
      return acc * (pos.length || 1)
    }, 1)
  },
  
  
  calculateZuxuanBetCount(type, count) {

    switch(type) {
      case 'z3': return count * (count - 1)        // n * (n-1)
      case 'z6': return combination(count, 3)      // C(n,3)
      case 'z2': return combination(count, 2)      // C(n,2)
      case '120': return combination(count, 5)     // 五星组选120
      case '60': return count >= 5 ? combination(count - 1, 3) * count : 0
      case '30': return count >= 4 ? combination(count - 2, 1) * combination(count, 2) : 0
      case '20': return count >= 4 ? combination(count - 1, 2) * count : 0
      case '10': return count >= 3 ? combination(count - 1, 1) * count : 0
      case '5': return count
      default: return count
    }
  },
  
  
  buildTzcode(type, selections, options = {}) {

    if (type === 'fushi') {
      return selections.map(pos => pos.join('')).join('|')
    }
    

    if (type === 'danshi') {
      return selections.join(' ')
    }
    

    if (type === 'zuxuan') {
      return selections.join(',')
    }
    

    if (type === 'dantuo') {
      const dan = options.dan || []
      const tuo = options.tuo || []
      return `${dan.join(',')}$${tuo.join(',')}`
    }
    
    return selections.join(',')
  }
}

function combination(n, r) {
  if (r > n || r < 0) return 0
  if (r === 0 || r === n) return 1
  
  let result = 1
  for (let i = 0; i < r; i++) {
    result = result * (n - i) / (i + 1)
  }
  return Math.round(result)
}

export default sscApi
