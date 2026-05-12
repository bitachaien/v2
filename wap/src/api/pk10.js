
import request from './request'

export const PK10_LOTTERIES = {
  'bjpk10': '北京PK10',
  'dfpk10': '台湾PK10',
  'pk101': '1分赛车',
  'pk103': '3分赛车',
  'pk105': '5分赛车',
  'xyft': '幸运飞艇'
}

const PLAY_ID_MAP = {

  'gy_da': 'pk10gyhd',
  'gy_xiao': 'pk10gyhx',
  'gy_dan': 'pk10gyhdd',
  'gy_shuang': 'pk10gyhss',

  'gy_3': 'pk10gyhz3',
  'gy_4': 'pk10gyhz4',
  'gy_5': 'pk10gyhz5',
  'gy_6': 'pk10gyhz6',
  'gy_7': 'pk10gyhz7',
  'gy_8': 'pk10gyhz8',
  'gy_9': 'pk10gyhz9',
  'gy_10': 'pk10gyhz10',
  'gy_11': 'pk10gyhz11',
  'gy_12': 'pk10gyhz12',
  'gy_13': 'pk10gyhz13',
  'gy_14': 'pk10gyhz14',
  'gy_15': 'pk10gyhz15',
  'gy_16': 'pk10gyhz16',
  'gy_17': 'pk10gyhz17',
  'gy_18': 'pk10gyhz18',
  'gy_19': 'pk10gyhz19',
}

function convertPlayId(frontendId) {

  if (PLAY_ID_MAP[frontendId]) {
    return PLAY_ID_MAP[frontendId]
  }
  

  const gyhzMatch = frontendId.match(/^gyhz_(\d+)$/)
  if (gyhzMatch) {
    return `pk10gyhz${gyhzMatch[1]}`
  }
  

  const mcMatch = frontendId.match(/^(\d+)_(da|xiao|dan|shuang|long|hu)$/)
  if (mcMatch) {
    return `pk10mc${mcMatch[1]}${mcMatch[2]}`
  }
  

  const numMatch = frontendId.match(/^(\d+)_(\d+)$/)
  if (numMatch) {
    return `pk10mc${numMatch[1]}hm${numMatch[2]}`
  }
  

  if (['qian5', 'qian4', 'qian3', 'qian2', 'qian1'].includes(frontendId)) {
    return `bjpk10${frontendId}`
  }
  

  return frontendId
}

export const pk10Api = {

  
  getInfo(lotteryCode = 'bjpk10') {
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
            closeSeconds: 10
          }
        }
      }
      return res
    })
  },

  
  getLastResult(lotteryCode = 'bjpk10') {
    return request({
      url: `/v1/lottery/${lotteryCode}/last-result`,
      method: 'get'
    })
  },

  
  getHistory(lotteryCode = 'bjpk10', params = {}) {
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

        const list = Array.isArray(res.data) ? res.data : (res.data.list || res.data.data || []);
        return {
          code: 0,
          data: list.map(item => {

            let opencode = item.opencode || item.openCode || item.kjhm || '';
            if (Array.isArray(opencode)) {
              opencode = opencode.join(',');
            }
            return {
              expect: item.expect || item.qihao || '',
              opencode: opencode,
              opentime_format: item.opentime || item.openTime || item.kaijiang_time || ''
            };
          })
        }
      }
      return res
    })
  },

  
  getPlays(lotteryCode = 'bjpk10') {
    return request({
      url: `/v1/lottery/${lotteryCode}/play-types`,
      method: 'get'
    })
  },

  
  submitBet(data) {
    const backendPlayId = convertPlayId(data.playId)
    
    const postData = {
      lotteryname: data.lotteryCode,
      expect: data.expect,
      playid: backendPlayId,
      tzcode: data.tzcode || data.selections?.join(',') || '',
      amount: data.totalAmount || data.amount,
      beishu: data.multiplier || 1,
      mode: 1,
      itemcount: data.betCount || 1,
      yjf: '元',
      ishemai: 0,
      nums: data.betCount || 1
    }
    
    
    return request({
      url: '/v1/bet/submit',
      method: 'post',
      data: postData
    })
  },

  
  async submitBets(lotteryCode, bets) {

    let expect = ''
    try {
      const expectRes = await this.getInfo(lotteryCode)
      if (expectRes.code === 0 && expectRes.data) {
        expect = expectRes.data.currFullExpect || expectRes.data.expect || ''
      }
    } catch (e) {
      console.error('获取期号失败:', e)
    }
    
    if (!expect) {
      return { code: 400, msg: '无法获取当前期号' }
    }
    

    const results = []
    let successCount = 0
    let failCount = 0
    
    for (const bet of bets) {
      try {
        const res = await this.submitBet({
          lotteryCode,
          expect,
          playId: bet.playId,
          tzcode: bet.tzcode,
          totalAmount: bet.amount,
          multiplier: 1,  // 信用玩法固定为1
          betCount: 1
        })
        if (res.code === 0) {
          successCount++
        } else {
          failCount++
        }
        results.push({ success: res.code === 0, bet, res })
      } catch (err) {
        failCount++
        results.push({ success: false, bet, error: err })
      }
    }
    

    if (failCount === 0) {
      return { code: 0, msg: `投注成功，共${successCount}注`, data: results }
    } else if (successCount === 0) {
      return { code: 500, msg: results[0]?.res?.msg || '投注失败', data: results }
    } else {
      return { code: 200, msg: `部分成功：${successCount}成功，${failCount}失败`, data: results }
    }
  },

  
  getUserBalance() {
    return request({
      url: '/v1/user/balance',
      method: 'get'
    })
  }
}

export default pk10Api
