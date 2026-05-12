

export const GAME_ICON_MAP = {
  'cqssc': 'game-1.png',
  'bjpk10': 'game-2.png',
  'xjssc': 'game-3.png',
  'tjssc': 'game-3.png', 
  'gd11x5': 'game-6.png',
  'fc3d': 'game-9.png',
  'pl3': 'game-10.png',
  'sh11x5': 'game-11.png',
  'dflhc': 'game-lhc.png',
  'animal': 'game-53.png',
  'hn7xc': 'game-72.png',
  'xyft': 'game-xyft.png',
  'azxy5': 'game-azxy5.png',
  'jsk3': 'game-jsk3.png'
}

export const getGameIconPath = (name) => {
  if (GAME_ICON_MAP[name]) {
    return `/assets/images/lottery/icons/${GAME_ICON_MAP[name]}`
  }
  return `/assets/images/lottery/icons/game-${name}.png`
}

export const GAME_CATEGORIES = [
  { title: '热门', code: 'remen', color: '#f53b57' },
  { title: '时时彩', code: 'ssc', color: '#ffa801' },
  { title: 'PK10', code: 'pk10', color: '#3c40c6' },
  { title: '11选5', code: 'x5', color: '#0be881' },
  { title: '快三', code: 'k3', color: '#ff5e57' },
  { title: 'PC蛋蛋', code: 'xy28', color: '#575fcf' },
  { title: '六合彩', code: 'lhc', color: '#05c46b' },
  { title: '快乐8', code: 'keno', color: '#e17055' },
  { title: '低频彩', code: 'dpc', color: '#00b894' },
  { title: '动物彩', code: 'dwc', color: '#e84393' }
]

export const DEFAULT_HOT_GAMES = [
  { name: 'cqssc', title: '重庆时时彩', typeid: 'ssc' },
  { name: 'ssc1fc', title: '1分时时彩', typeid: 'ssc' },
  { name: 'txssc', title: '腾讯分分彩', typeid: 'ssc' },
  { name: 'bjpk10', title: '北京PK10', typeid: 'pk10' },
  { name: 'xyft', title: '幸运飞艇', typeid: 'pk10' },
  { name: 'gd11x5', title: '广东11选5', typeid: 'x5' },
  { name: 'yf11x5', title: '一分11选5', typeid: 'x5' },
  { name: 'jsk3', title: '江苏快3', typeid: 'k3' },
  { name: 'jisk3', title: '极速快3', typeid: 'k3' },
  { name: 'f1k3', title: '1分快3', typeid: 'k3' },
  { name: 'dflhc', title: '六合彩', typeid: 'lhc' },
  { name: 'fc3d', title: '福彩3D', typeid: 'ssc' },
  { name: 'pl3', title: '排列3', typeid: 'ssc' },
  { name: 'xy28', title: '幸运28', typeid: 'xy28' },
  { name: 'yfxy28', title: '一分幸运28', typeid: 'xy28' },
  { name: 'animal', title: '动物彩', typeid: 'pk10' },
  { name: 'hn7xc', title: '海南七星彩', typeid: 'ssc' }
]

export const DEFAULT_FULL_GAMES = {
  ssc: [
    { name: 'cqssc', title: '重庆时时彩', typeid: 'ssc' },
    { name: 'ssc1fc', title: '1分时时彩', typeid: 'ssc' },
    { name: 'txssc', title: '腾讯分分彩', typeid: 'ssc' },
    { name: 'hn7xc', title: '海南七星彩', typeid: 'ssc' }
  ],
  dpc: [
    { name: 'pl3', title: '排列三', typeid: 'dpc' },
    { name: 'fc3d', title: '福彩3D', typeid: 'dpc' }
  ],
  pk10: [
    { name: 'bjpk10', title: '北京PK10', typeid: 'pk10' },
    { name: 'xyft', title: '幸运飞艇', typeid: 'pk10' },
    { name: 'animal', title: '动物彩', typeid: 'pk10' }
  ],
  x5: [
    { name: 'gd11x5', title: '广东11选5', typeid: 'x5' },
    { name: 'yf11x5', title: '一分11选5', typeid: 'x5' }
  ],
  k3: [
    { name: 'jsk3', title: '江苏快3', typeid: 'k3' },
    { name: 'jisk3', title: '极速快3', typeid: 'k3' },
    { name: 'f1k3', title: '1分快3', typeid: 'k3' }
  ],
  xy28: [
    { name: 'xy28', title: '幸运28', typeid: 'xy28' },
    { name: 'yfxy28', title: '一分幸运28', typeid: 'xy28' }
  ],
  lhc: [
    { name: 'dflhc', title: '六合彩', typeid: 'lhc' }
  ],
  dwc: [
    { name: 'yfdwc', title: '一分动物彩', typeid: 'dwc' }
  ]
}

export const getGameUrl = (game, mode = 'standard') => {

  if (!game || !game.name) {
    console.warn('getGameUrl: 无效的游戏对象', game)
    return null
  }
  
  const { name, typeid } = game
  

  if (name === 'animal') return '/lottery/pk10-animal/animal'
  if (name === 'yfdwc') return '/lottery/pk10-animal/yfdwc'
  if (typeid === 'dwc') return `/lottery/pk10-animal/${name}`
  if (name === 'fc3d') return `/lottery/fc3d/fc3d`
  if (name === 'pl3') return `/lottery/pl3/pl3`
  if (name === 'xy28' || name === 'yfxy28') return `/lottery/xy28-chat/${name}`
  if (name === 'hn7xc') return `/lottery/hn7xc-new`
  

  if (typeid === 'dpc') {
    if (name === 'pl3') return `/lottery/pl3/pl3`
    if (name === 'fc3d') return `/lottery/fc3d/fc3d`
    return `/lottery/${name}/${name}`
  }

  if (typeid === 'k3' || ['jsk3', 'jisk3', 'f1k3'].includes(name)) {
    return mode === 'double' ? '/lottery/k3-double' : `/lottery/k3/${name}`
  }

  if (typeid === 'lhc' || name.includes('lhc')) {
    return mode === 'double' ? `/lottery/lhc/${name}?mode=double` : `/lottery/lhc/${name}`
  }

  if (mode === 'standard') {
     if (typeid === 'ssc') return `/ssc/${name}`
     if (typeid === 'pk10') return `/pk10/${name}`
     if (typeid === 'x5') return `/x5/${name}`
     if (typeid === 'keno') return `/lottery/keno/${name}`
     

     if (!typeid) {
       console.warn(`getGameUrl: 游戏 ${name} 缺少 typeid，使用通用路径`)
       return `/lottery/${name}`
     }
     
     return `/lottery/${typeid}/${name}`
  } else {

    if (typeid === 'ssc') return `/ssc/${name}?mode=double`
    if (typeid === 'pk10') return `/pk10/${name}?mode=double`
    if (typeid === 'x5') return `/x5/${name}?mode=double`
    if (typeid === 'xy28') return `/lottery/xy28-chat/${name}`
    

    if (!typeid) {
      console.warn(`getGameUrl: 游戏 ${name} 缺少 typeid (双面盘模式)`)
      return null
    }
    
    return `/lotterys_hot/${typeid}/${name}/1`
  }
}
