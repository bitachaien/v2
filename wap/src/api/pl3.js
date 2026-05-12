
import request from './request'

const oddsCache = new Map() // key: code, value: { data, ts }
const ODDS_TTL = 30 * 1000

export const PL3_LOTTERIES = {
  'pl3': '排列三'
}

const PLAY_ID_MAP = {

  'pl3zxfs': 'pl3zxfs',           // 三星直选复式
  'pl3zxds': 'pl3zxds',           // 三星直选单式
  

  'pl3zx3fs': 'pl3zuxzs',         // 组三复式
  'pl3zx6fs': 'pl3zuxzl',         // 组六复式
  'pl3zxhz': 'pl3zxhz',           // 组选和值
  

  'pl3qx2fs': 'pl3q2zxfs',        // 前二直选复式
  'pl3hx2fs': 'pl3h2zxfs',        // 后二直选复式
  

  'pl3qx2zxfs': 'pl3q2zxzs',      // 前二组选复式
  'pl3hx2zxfs': 'pl3h2zxzs',      // 后二组选复式
  

  'pl3dwdfs': 'pl3dwd',           // 定位胆
  

  'dxdsq2': 'pl3dxdsq2',          // 前二大小单双
  'dxdsh2': 'pl3dxdsh2',          // 后二大小单双
}

const DOUBLE_SIDE_MAP = {

  'bw_d': 'pl3bwd',               // 百位大
  'bw_x': 'pl3bwx',               // 百位小
  'bw_dd': 'pl3bwdd',             // 百位单
  'bw_ss': 'pl3bwss',             // 百位双

  'sw_d': 'pl3swd',
  'sw_x': 'pl3swx',
  'sw_dd': 'pl3swdd',
  'sw_ss': 'pl3swss',

  'gw_d': 'pl3gwd',
  'gw_x': 'pl3gwx',
  'gw_dd': 'pl3gwdd',
  'gw_ss': 'pl3gwss',

  'zh_d': 'pl3zhd',               // 总和大
  'zh_x': 'pl3zhx',               // 总和小
  'zh_dd': 'pl3zhdd',             // 总和单
  'zh_ss': 'pl3zhss',             // 总和双
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

export const pl3Api = {

  
  getInfo(lotteryCode = 'pl3') {
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

  
  getLastResult(lotteryCode = 'pl3') {
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

  
  getHistory(lotteryCode = 'pl3', params = {}) {
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
      lotteryCode = 'pl3',
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
      lotteryCode = 'pl3',
      expect,
      selections = [], // [{playId: 'bw_d', amount: 10}, ...]
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

  
  getOdds(lotteryCode = 'pl3') {
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
      lotteryCode = 'pl3',
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
        lotteryType: params.lotteryType || 'pl3',
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

export default pl3Api
