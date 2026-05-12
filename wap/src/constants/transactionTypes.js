

export const TRANSACTION_TYPES = {

  order: '代购',
  cancel: '撤单',
  reward: '返奖',
  rollback: '后台撤单',
  

  yeb_dq: '余额宝定期',
  yeb_lixi: '余额宝利息',
  yeb_hq: '余额宝活期',
  yuebao_claim: '利息宝收益',
  

  fanshui: '每日加奖',
  jinjishenhe: '晋级奖励',
  '晋级奖励': '晋级奖励',
  yongjinshenhe: '代理返点',
  '代理佣金': '代理佣金',
  xima: '洗码',
  '返水': '返水',
  point: '积分',
  

  activity_bindcard: '绑定银行赠送活动',
  activity_cz: '充值活动',
  activity_czzs: '充值赠送活动',
  activity_rxf: '日消费赠送活动',
  activity_rks: '日亏损赠送活动',
  activity_yxf: '月消费赠送活动',
  activity_yks: '月亏损赠送活动',
  '活动奖励': '活动奖励',
  '签到奖励': '签到奖励',
  

  withdraw: '提现',
  withdraw_return: '提款退回',
  withdraw_reject: '提现退回',
  withdraw_cancel: '提现取消',
  adminadd: '管理员加',
  adminjian: '管理员减',
  recharge: '充值',
  transfer_in: '转账入款',
  transfer_out: '转账出款'
}

export const EXPENSE_TYPES = [
  'order',        // 代购
  'xima',         // 洗码
  'withdraw',     // 提款
  'yeb_dq',       // 余额宝定期
  'yeb_hq',       // 余额宝活期
  'adminjian',    // 管理员减
  'transfer_out'  // 转账出款
]

export const INCOME_TYPES = [
  'cancel',              // 撤单
  'reward',              // 返奖
  'fanshui',             // 每日加奖
  'jinjishenhe',         // 晋级奖励
  '晋级奖励',            // 晋级奖励(旧)
  'yongjinshenhe',       // 代理返点
  '代理佣金',            // 代理佣金
  'yeb_lixi',            // 余额宝利息
  'yuebao_claim',        // 利息宝收益
  'point',               // 积分
  '返水',                // 返水
  'activity_bindcard',   // 绑卡赠送
  'activity_cz',         // 充值活动
  'activity_czzs',       // 充值赠送
  'activity_rxf',        // 日消费赠送
  'activity_rks',        // 日亏损赠送
  'activity_yxf',        // 月消费赠送
  'activity_yks',        // 月亏损赠送
  '活动奖励',            // 活动奖励
  '签到奖励',            // 签到奖励
  'recharge',            // 充值
  'withdraw_return',     // 提款退回
  'withdraw_reject',     // 提现退回
  'withdraw_cancel',     // 提现取消
  'adminadd',            // 管理员加
  'transfer_in',         // 转账入款
  'rollback'             // 后台撤单
]

export const TYPE_CATEGORIES = {
  lottery: {
    label: '彩票相关',
    types: ['order', 'cancel', 'reward', 'rollback']
  },
  yuebao: {
    label: '余额宝',
    types: ['yeb_dq', 'yeb_lixi', 'yeb_hq']
  },
  reward: {
    label: '奖励返利',
    types: ['fanshui', 'jinjishenhe', 'yongjinshenhe', 'xima', 'point']
  },
  activity: {
    label: '活动奖励',
    types: [
      'activity_bindcard',
      'activity_cz',
      'activity_czzs',
      'activity_rxf',
      'activity_rks',
      'activity_yxf',
      'activity_yks'
    ]
  },
  fund: {
    label: '资金操作',
    types: ['withdraw', 'withdraw_return', 'adminadd', 'adminjian', 'recharge', 'transfer_in', 'transfer_out']
  }
}

export const TIME_FILTERS = [
  { label: '全部', value: '' },
  { label: '今日', value: '1' },
  { label: '昨日', value: '2' },
  { label: '最近七日', value: '3' }
]

export const TYPE_FILTER_OPTIONS = [
  { value: '', label: '全部类型' },
  { value: 'order', label: '代购' },
  { value: 'cancel', label: '撤单' },
  { value: 'reward', label: '返奖' },
  { value: 'fanshui', label: '每日加奖' },
  { value: 'jinjishenhe', label: '晋级奖励' },
  { value: 'yongjinshenhe', label: '代理返点' },
  { value: '代理佣金', label: '代理佣金' },
  { value: 'xima', label: '洗码' },
  { value: '返水', label: '返水' },
  { value: 'yeb_lixi', label: '余额宝利息' },
  { value: 'yeb_dq', label: '余额宝定期' },
  { value: 'yeb_hq', label: '余额宝活期' },
  { value: 'yuebao_claim', label: '利息宝收益' },
  { value: 'withdraw', label: '提现' },
  { value: 'withdraw_reject', label: '提现退回' },
  { value: 'withdraw_cancel', label: '提现取消' },
  { value: 'recharge', label: '充值' },
  { value: 'adminadd', label: '管理员加' },
  { value: 'adminjian', label: '管理员减' },
  { value: 'rollback', label: '后台撤单' },
  { value: 'point', label: '积分' },
  { value: 'activity_cz', label: '充值活动' },
  { value: 'activity_czzs', label: '充值赠送' },
  { value: 'activity_bindcard', label: '绑卡赠送' },
  { value: '活动奖励', label: '活动奖励' },
  { value: '签到奖励', label: '签到奖励' }
]

export const TYPE_ICONS = {

  order: '💰',
  cancel: '↩️',
  reward: '🎁',
  rollback: '🔄',
  

  yeb_dq: '📊',
  yeb_lixi: '📈',
  yeb_hq: '💹',
  

  fanshui: '💧',
  jinjishenhe: '⭐',
  yongjinshenhe: '💼',
  xima: '🎯',
  point: '🏆',
  

  activity_bindcard: '🎊',
  activity_cz: '🎉',
  activity_czzs: '🎁',
  activity_rxf: '🎖️',
  activity_rks: '💝',
  activity_yxf: '🏅',
  activity_yks: '🎪',
  

  withdraw: '💳',
  withdraw_return: '↪️',
  adminadd: '➕',
  adminjian: '➖',
  recharge: '💵',
  transfer_in: '📥',
  transfer_out: '📤'
}

export const TYPE_COLORS = {

  order: '#4B6CFF',
  cancel: '#7C4DFF',
  reward: '#20E3B2',
  rollback: '#A5B4FC',
  

  yeb_dq: '#9D4EDD',
  yeb_lixi: '#C77DFF',
  yeb_hq: '#E0AAFF',
  

  fanshui: '#EAC26E',
  jinjishenhe: '#F9E6A8',
  yongjinshenhe: '#FFD700',
  xima: '#FFA500',
  point: '#FF8C00',
  

  activity_bindcard: '#FF6B81',
  activity_cz: '#FF3B3B',
  activity_czzs: '#FF7A7A',
  activity_rxf: '#FFA07A',
  activity_rks: '#FF69B4',
  activity_yxf: '#FFB6C1',
  activity_yks: '#FFC0CB',
  

  withdraw: '#29F3C3',
  withdraw_return: '#1DD1A1',
  adminadd: '#10AC84',
  adminjian: '#FF6B81',
  recharge: '#00D2FF',
  transfer_in: '#48DBFB',
  transfer_out: '#0ABDE3'
}

export function getTransactionTypeName(type) {
  return TRANSACTION_TYPES[type] || type || '未知类型'
}

export function isExpenseType(type) {
  return EXPENSE_TYPES.includes(type)
}

export function isIncomeType(type) {
  return INCOME_TYPES.includes(type)
}

export function getTypeIcon(type) {
  return TYPE_ICONS[type] || '📋'
}

export function getTypeColor(type) {
  return TYPE_COLORS[type] || '#A0AEC0'
}

export function formatAmountByType(amount, type) {
  const absAmount = Math.abs(amount)
  const formatted = absAmount.toFixed(2)
  

  if (type === 'withdraw_return') {
    return `+${formatted}`
  }
  

  return isExpenseType(type) ? `-${formatted}` : `+${formatted}`
}

export function getAmountClass(amount, type) {

  if (type === 'withdraw_return') {
    return 'income'
  }
  
  return isExpenseType(type) ? 'expense' : 'income'
}

export function getTypesByCategory(category) {
  const cat = TYPE_CATEGORIES[category]
  if (!cat) return []
  
  return cat.types.map(type => ({
    value: type,
    label: TRANSACTION_TYPES[type]
  }))
}

export function getAllCategoriesWithTypes() {
  return Object.entries(TYPE_CATEGORIES).map(([key, cat]) => ({
    label: cat.label,
    options: cat.types.map(type => ({
      value: type,
      label: TRANSACTION_TYPES[type],
      icon: TYPE_ICONS[type],
      color: TYPE_COLORS[type]
    }))
  }))
}

export function parseTimeFilter(atime) {
  const now = new Date()
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())
  
  switch (atime) {
    case '1': // 今日
      return {
        startDate: formatDate(today),
        endDate: formatDate(now)
      }
    
    case '2': // 昨日
      const yesterday = new Date(today.getTime() - 24 * 60 * 60 * 1000)
      return {
        startDate: formatDate(yesterday),
        endDate: formatDate(new Date(yesterday.getTime() + 24 * 60 * 60 * 1000 - 1000))
      }
    
    case '3': // 最近七日
      const sevenDaysAgo = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000)
      return {
        startDate: formatDate(sevenDaysAgo),
        endDate: formatDate(now)
      }
    
    default:
      return null
  }
}

function formatDate(date) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  const hours = String(date.getHours()).padStart(2, '0')
  const minutes = String(date.getMinutes()).padStart(2, '0')
  const seconds = String(date.getSeconds()).padStart(2, '0')
  
  return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`
}

export function searchTypes(keyword) {
  if (!keyword) return TYPE_FILTER_OPTIONS
  
  const lowerKeyword = keyword.toLowerCase()
  return TYPE_FILTER_OPTIONS.filter(option => 
    option.label.toLowerCase().includes(lowerKeyword) ||
    option.value.toLowerCase().includes(lowerKeyword)
  )
}

export default {

  TRANSACTION_TYPES,
  EXPENSE_TYPES,
  INCOME_TYPES,
  TYPE_CATEGORIES,
  TIME_FILTERS,
  TYPE_FILTER_OPTIONS,
  TYPE_ICONS,
  TYPE_COLORS,
  

  getTransactionTypeName,
  isExpenseType,
  isIncomeType,
  getTypeIcon,
  getTypeColor,
  formatAmountByType,
  getAmountClass,
  getTypesByCategory,
  getAllCategoriesWithTypes,
  parseTimeFilter,
  searchTypes
}
