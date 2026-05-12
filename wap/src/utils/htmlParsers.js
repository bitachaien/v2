

import { DEFAULT_HOT_GAMES, DEFAULT_FULL_GAMES } from '@/config/gameConfig'

export function parseMemberData(html, defaultInfo = {}) {
  const parser = new DOMParser()
  const doc = parser.parseFromString(html, 'text/html')
  const info = { ...defaultInfo }
  const meta = {}

  const usernameEl = doc.querySelector('.hometop-name-id em')
  if (usernameEl) info.username = usernameEl.textContent.trim()

  const balanceEl = doc.querySelector('#smallmoney')
  if (balanceEl) info.balance = parseFloat(balanceEl.textContent.trim()) || 0

  const faceEl = doc.querySelector('.hometop-name-img img')
  if (faceEl) {
    const src = faceEl.getAttribute('src') || ''
    info.face = src.replace('__ROOT__', '')
  }

  const aqjibieEl = doc.querySelector('.homeziliao li a span')
  if (aqjibieEl && aqjibieEl.textContent.includes('安全级别为')) {
    const match = aqjibieEl.textContent.match(/安全级别为\s*(\S+)/)
    if (match) meta.aqjibie = match[1]
  }

  const ccssddEls = doc.querySelectorAll('.ccssdd div')
  ccssddEls.forEach(el => {
    const text = el.textContent.trim()
    if (text.includes('会员类型')) {
      const match = text.match(/会员类型：\s*(.+)/)
      if (match) {
        const type = match[1].trim()
        if (type === '代理') info.groupname = '代理'
        else info.touhan = type
      }
    }
    if (text.includes('上次登录时间')) {
      const match = text.match(/上次登录时间：\s*(.+)/)
      if (match) meta.lasttime = match[1].trim()
    }
    if (text.includes('上次登录IP')) {
      const match = text.match(/上次登录IP：\s*(.+?)(?:\s|$)/)
      if (match) meta.lastip = match[1].trim()
    }
  })

  return { userinfo: info, meta }
}

export function parseLotteryHallData(html) {
  const parser = new DOMParser()
  const doc = parser.parseFromString(html, 'text/html')
  
  const hotGames = []
  const games = JSON.parse(JSON.stringify(DEFAULT_FULL_GAMES))

  const hotGameItems = doc.querySelectorAll('.sacn_remen .gameitem, .sacnsm_remen .gameitem')
  const seen = new Set()
  
  hotGameItems.forEach(item => {
    const href = item.getAttribute('href') || ''
    const titleDiv = item.querySelector('div:last-child')
    

    let match = href.match(/\/(?:lottery|lotterys_hot|k3-refactored)\/(\w+)\/(\w+)/)
    
    if (match && titleDiv) {
      const typeid = match[1]
      const name = match[2]
      const key = `${typeid}_${name}`
      
      if (!seen.has(key)) {
        seen.add(key)
        hotGames.push({ 
          name, 
          title: titleDiv.textContent.trim(), 
          typeid 
        })
      }
    }
  })

  if (hotGames.length > 0) {
    const existingNames = new Set(hotGames.map(g => g.name))
    DEFAULT_HOT_GAMES.forEach(defGame => {
      if (!existingNames.has(defGame.name)) {
        hotGames.push(defGame)
      }
    })
  } else {
    hotGames.push(...DEFAULT_HOT_GAMES)
  }

  const categoryMaps = { 
    ssc: 'sacn_ssc', 
    pk10: 'sacn_pk10', 
    '11x5': 'sacn_11x5', 
    k3: 'sacn_k3', 
    xy28: 'sacn_xy28', 
    lhc: 'sacn_lhc' 
  }

  Object.keys(categoryMaps).forEach(typeKey => {

    
    const className = categoryMaps[typeKey]
    const items = doc.querySelectorAll(`.${className} .gameitem`)
    

    const categoryGames = []

    items.forEach(item => {
      const href = item.getAttribute('href') || ''
      const titleDiv = item.querySelector('div:last-child')
      let match = href.match(/\/(?:lottery|lotterys_hot|k3-refactored)\/(\w+)\/(\w+)/)
      
      if (match && titleDiv) {
         categoryGames.push({ 
           name: match[2], 
           title: titleDiv.textContent.trim(), 
           typeid: match[1] 
         })
      }
    })
    

    if (DEFAULT_FULL_GAMES[typeKey]) {
       const existingNames = new Set(categoryGames.map(g => g.name))
       DEFAULT_FULL_GAMES[typeKey].forEach(defGame => {
          if (!existingNames.has(defGame.name)) {
             categoryGames.push(defGame)
          }
       })
    }

    if (categoryGames.length > 0) {
        games[typeKey] = categoryGames
    }
  })

  return { hotGames, games }
}
