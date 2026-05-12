
export function getShengXiao(num) {
  if (!num || num === '?') return ''
  const n = parseInt(num)

  const animals = ['龙', '兔', '虎', '牛', '鼠', '猪', '狗', '鸡', '猴', '羊', '马', '蛇']

  const index = (n - 1) % 12
  return animals[index]
}

export function getNumberColorClass(num) {
  if (!num || num === '?' || num === '--') return ''
  const n = parseInt(num)
  if (isNaN(n) || n < 1 || n > 49) return ''
  
  const redNums = [1, 2, 7, 8, 12, 13, 18, 19, 23, 24, 29, 30, 34, 35, 40, 45, 46]
  const blueNums = [3, 4, 9, 10, 14, 15, 20, 25, 26, 31, 36, 37, 41, 42, 47, 48]
  const greenNums = [5, 6, 11, 16, 17, 21, 22, 27, 28, 32, 33, 38, 39, 43, 44, 49]
  
  if (redNums.includes(n)) return 'ssc-fang-hong'
  if (blueNums.includes(n)) return 'ssc-fang-lan'
  if (greenNums.includes(n)) return 'ssc-fang-lv'
  return ''
}

export function getLiangMianOptions(type) {
  const options = [
    { label: '大', value: '大', rate: '1.98', colorClass: '' },
    { label: '小', value: '小', rate: '1.98', colorClass: '' },
    { label: '单', value: '单', rate: '1.98', colorClass: '' },
    { label: '双', value: '双', rate: '1.98', colorClass: '' },
    { label: '大单', value: '大单', rate: '3.96', colorClass: '' },
    { label: '大双', value: '大双', rate: '3.96', colorClass: '' },
    { label: '小单', value: '小单', rate: '3.96', colorClass: '' },
    { label: '小双', value: '小双', rate: '3.96', colorClass: '' },
    { label: '合大', value: '合大', rate: '1.98', colorClass: '' },
    { label: '合小', value: '合小', rate: '1.98', colorClass: '' },
    { label: '合单', value: '合单', rate: '1.98', colorClass: '' },
    { label: '合双', value: '合双', rate: '1.98', colorClass: '' },
    { label: '尾大', value: '尾大', rate: '1.98', colorClass: '' },
    { label: '尾小', value: '尾小', rate: '1.98', colorClass: '' },
    { label: '家禽', value: '家禽', rate: '1.98', colorClass: '' },
    { label: '野兽', value: '野兽', rate: '1.98', colorClass: '' },
    { label: '红波', value: '红波', rate: '1.98', colorClass: 'ssc-fang-hong' },
    { label: '绿波', value: '绿波', rate: '1.98', colorClass: 'ssc-fang-lv' },
    { label: '蓝波', value: '蓝波', rate: '1.98', colorClass: 'ssc-fang-lan' },
  ]
  
  if (type === 'tmlm') {
    const playIds = ['tmlmda', 'tmlmxiao', 'tmlmdan', 'tmlmshuang', 'tmlmdadan', 'tmlmdashuang', 
                     'tmlmxiaodan', 'tmlmxiaoshuang', 'tmlmheda', 'tmlmhexiao', 'tmlmhedan', 'tmlmheshuang',
                     'tmlmweida', 'tmlmweixiao', 'tmlmjiaqin', 'tmlmyeshou', 'tmlmhongbo', 'tmlmlvbo', 'tmlmlanbo']
    return options.map((opt, index) => ({
      ...opt,
      playId: playIds[index]
    }))
  } else {

    return options
  }
}

export function getBanBoOptions() {
  return [
    { label: '红大', value: '红大', rate: '1.98', numbers: '29 30 34 35 40 45 46', colorClass: 'ssc-fang-hong', playId: 'hongda' },
    { label: '红小', value: '红小', rate: '1.98', numbers: '01 02 07 08 12 13 18 19 23 24', colorClass: 'ssc-fang-hong', playId: 'hongxiao' },
    { label: '红单', value: '红单', rate: '1.98', numbers: '01 07 13 19 23 29 35 45', colorClass: 'ssc-fang-hong', playId: 'hongdan' },
    { label: '红双', value: '红双', rate: '1.98', numbers: '02 08 12 18 24 30 34 40 46', colorClass: 'ssc-fang-hong', playId: 'hongshuang' },
    { label: '红合单', value: '红合单', rate: '1.98', numbers: '01 07 12 18 23 29 30 34 35', colorClass: 'ssc-fang-hong', playId: 'honghedan' },
    { label: '红合双', value: '红合双', rate: '1.98', numbers: '02 08 13 19 24 35 40 46', colorClass: 'ssc-fang-hong', playId: 'hongheshuang' },
    { label: '绿大', value: '绿大', rate: '1.98', numbers: '27 28 32 33 38 39 43 44', colorClass: 'ssc-fang-lv', playId: 'lvda' },
    { label: '绿小', value: '绿小', rate: '1.98', numbers: '05 06 11 16 17 21 22', colorClass: 'ssc-fang-lv', playId: 'lvxiao' },
    { label: '绿单', value: '绿单', rate: '1.98', numbers: '05 11 17 21 27 33 39 43', colorClass: 'ssc-fang-lv', playId: 'lvdan' },
    { label: '绿双', value: '绿双', rate: '1.98', numbers: '06 16 22 28 32 38 44', colorClass: 'ssc-fang-lv', playId: 'lvshuang' },
    { label: '绿合单', value: '绿合单', rate: '1.98', numbers: '05 16 21 27 32 38 43', colorClass: 'ssc-fang-lv', playId: 'lvhedan' },
    { label: '绿合双', value: '绿合双', rate: '1.98', numbers: '06 11 17 22 28 33 39 44', colorClass: 'ssc-fang-lv', playId: 'lvheshuang' },
    { label: '蓝大', value: '蓝大', rate: '1.98', numbers: '25 26 31 36 37 41 42 47 48', colorClass: 'ssc-fang-lan', playId: 'landa' },
    { label: '蓝小', value: '蓝小', rate: '1.98', numbers: '03 04 09 10 14 15 20', colorClass: 'ssc-fang-lan', playId: 'lanxiao' },
    { label: '蓝单', value: '蓝单', rate: '1.98', numbers: '03 09 15 25 31 37 41 47', colorClass: 'ssc-fang-lan', playId: 'landan' },
    { label: '蓝双', value: '蓝双', rate: '1.98', numbers: '04 10 14 20 26 36 42 48', colorClass: 'ssc-fang-lan', playId: 'lanshuang' },
    { label: '蓝合单', value: '蓝合单', rate: '1.98', numbers: '03 09 10 14 25 36 41 47', colorClass: 'ssc-fang-lan', playId: 'lanhedan' },
    { label: '蓝合双', value: '蓝合双', rate: '1.98', numbers: '04 15 20 26 31 37 42 48', colorClass: 'ssc-fang-lan', playId: 'lanheshuang' },
  ]
}

export function getShengXiaoOptions(type) {
  const shengXiao = [
    { label: '鼠', value: '鼠', numbers: '11 23 35 47' },
    { label: '牛', value: '牛', numbers: '10 22 34 46' },
    { label: '虎', value: '虎', numbers: '09 21 33 45' },
    { label: '兔', value: '兔', numbers: '08 20 32 44' },
    { label: '龙', value: '龙', numbers: '07 19 31 43' },
    { label: '蛇', value: '蛇', numbers: '06 18 30 42' },
    { label: '马', value: '马', numbers: '05 17 29 41' },
    { label: '羊', value: '羊', numbers: '04 16 28 40' },
    { label: '猴', value: '猴', numbers: '03 15 27 39' },
    { label: '鸡', value: '鸡', numbers: '02 14 26 38' },
    { label: '狗', value: '狗', numbers: '01 13 25 37 49' },
    { label: '猪', value: '猪', numbers: '12 24 36 48' },
  ]
  
  const playIdMap = {
    'sxtx': { prefix: 'sxtx', hasRate: true },
    'sx1x': { prefix: 'sx1x', hasRate: true },
    'sxzx': { prefix: 'sxzx', hasRate: true },
    'sxhx': { prefix: 'sxhx', hasRate: true },
    'sx2xl': { prefix: 'sx2xl', hasRate: false },
    'sx3xl': { prefix: 'sx3xl', hasRate: false },
    'sx4xl': { prefix: 'sx4xl', hasRate: false },
  }
  
  const config = playIdMap[type] || { prefix: type, hasRate: false }
  
  return shengXiao.map((item, index) => ({
    ...item,
    rate: config.hasRate ? '1.98' : undefined,
    playId: `${config.prefix}${['shu', 'niu', 'hu', 'tu', 'long', 'she', 'ma', 'yang', 'hou', 'ji', 'gou', 'zhu'][index]}`
  }))
}

export function getWeiShuOptions(type) {
  if (type === 'wstw') {
    return [
      { label: '0头', value: '0头', rate: '1.98', playId: 'lingtou' },
      { label: '1头', value: '1头', rate: '1.98', playId: 'yitou' },
      { label: '2头', value: '2头', rate: '1.98', playId: 'ertou' },
      { label: '3头', value: '3头', rate: '1.98', playId: 'santou' },
      { label: '4头', value: '4头', rate: '1.98', playId: 'sitou' },
      { label: '0尾', value: '0尾', rate: '1.98', playId: 'lingwei' },
      { label: '1尾', value: '1尾', rate: '1.98', playId: 'yiwei' },
      { label: '2尾', value: '2尾', rate: '1.98', playId: 'erwei' },
      { label: '3尾', value: '3尾', rate: '1.98', playId: 'sanwei' },
      { label: '4尾', value: '4尾', rate: '1.98', playId: 'siwei' },
      { label: '5尾', value: '5尾', rate: '1.98', playId: 'wuwei' },
      { label: '6尾', value: '6尾', rate: '1.98', playId: 'liuwei' },
      { label: '7尾', value: '7尾', rate: '1.98', playId: 'qiwei' },
      { label: '8尾', value: '8尾', rate: '1.98', playId: 'bawei' },
      { label: '9尾', value: '9尾', rate: '1.98', playId: 'jiuwei' },
    ]
  } else {

    return [
      { label: '0尾', value: '0尾', playId: type },
      { label: '1尾', value: '1尾', playId: type },
      { label: '2尾', value: '2尾', playId: type },
      { label: '3尾', value: '3尾', playId: type },
      { label: '4尾', value: '4尾', playId: type },
      { label: '5尾', value: '5尾', playId: type },
      { label: '6尾', value: '6尾', playId: type },
      { label: '7尾', value: '7尾', playId: type },
      { label: '8尾', value: '8尾', playId: type },
      { label: '9尾', value: '9尾', playId: type },
    ]
  }
}

export function getSeBoOptions() {
  return [
    { label: '红波', value: '红波', rate: '2.9', colorClass: 'ssc-fang-hong', playId: 'hongbo' },
    { label: '绿波', value: '绿波', rate: '2.9', colorClass: 'ssc-fang-lv', playId: 'lvbo' },
    { label: '蓝波', value: '蓝波', rate: '2.9', colorClass: 'ssc-fang-lan', playId: 'lanbo' }
  ]
}

export function getWuXingOptions() {
  return [
    { label: '金', value: '金', rate: '4.8', playId: 'jin' },
    { label: '木', value: '木', rate: '4.8', playId: 'mu' },
    { label: '水', value: '水', rate: '4.8', playId: 'shui' },
    { label: '火', value: '火', rate: '4.8', playId: 'huo' },
    { label: '土', value: '土', rate: '4.8', playId: 'tu' }
  ]
}

export function getZongXiaoOptions() {
  return [
    { label: '2肖', value: '2肖', rate: '10', playId: '2xiao' },
    { label: '3肖', value: '3肖', rate: '10', playId: '3xiao' },
    { label: '4肖', value: '4肖', rate: '10', playId: '4xiao' },
    { label: '5肖', value: '5肖', rate: '10', playId: '5xiao' },
    { label: '6肖', value: '6肖', rate: '10', playId: '6xiao' },
    { label: '7肖', value: '7肖', rate: '10', playId: '7xiao' },
    { label: '总肖单', value: '总肖单', rate: '1.98', playId: 'zxdan' },
    { label: '总肖双', value: '总肖双', rate: '1.98', playId: 'zxshuang' }
  ]
}
