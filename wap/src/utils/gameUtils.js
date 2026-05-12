

const chars = ['0','1','2','3','4','5','6','7','8','9','A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z']

export function generateMixed(n) {
  let res = ""
  for(let i = 0; i < n ; i ++) {
    const id = Math.ceil(Math.random()*35)
    res += chars[id]
  }
  return res
}

export function generateExpect() {
  const now = new Date()
  const year = now.getFullYear()
  const month = String(now.getMonth() + 1).padStart(2, '0')
  const date = String(now.getDate()).padStart(2, '0')
  const hour = String(now.getHours()).padStart(2, '0')
  const minute = Math.floor(now.getMinutes() / 10)
  
  return `${year}${month}${date}${hour}${minute}`
}

export function generateK3Code() {
  return [
    Math.floor(Math.random() * 6) + 1,
    Math.floor(Math.random() * 6) + 1,
    Math.floor(Math.random() * 6) + 1
  ]
}

export function generateSSCCode(length = 5) {
  return Array.from({ length }, () => Math.floor(Math.random() * 10))
}

export function createCountdown(duration, callback) {
  let seconds = duration
  
  const update = () => {
    const h = Math.floor(seconds / 3600)
    const m = Math.floor((seconds % 3600) / 60)
    const s = seconds % 60
    
    callback({
      h: String(h).padStart(2, '0'),
      m: String(m).padStart(2, '0'),
      s: String(s).padStart(2, '0')
    })
    
    if (seconds > 0) {
      seconds--
    } else {
      seconds = duration
    }
  }
  
  update()
  const timer = setInterval(update, 1000)
  
  return () => clearInterval(timer)
}

export function generateMockRates(playids) {
  const rates = {}
  playids.forEach(playid => {
    rates[playid] = {
      playid,
      title: playid,
      maxjj: (1.90 + Math.random() * 0.15).toFixed(2),
      minjj: 1.80,
      minxf: 1,
      maxzs: 10000,
      totalzs: 1
    }
  })
  return rates
}

