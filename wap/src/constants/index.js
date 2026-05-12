

export const LOTTERY_TYPES = {
  SSC: 'ssc',        // 时时彩
  LHC: 'lhc',        // 六合彩
  K3: 'k3',          // 快3
  X5: 'x5',          // 11选5
  PK10: 'pk10',      // PK10
  XY28: 'xy28',      // 幸运28
  FC3D: 'fc3d'       // 福彩3D
}

export const BET_MODES = {
  YUAN: '1',         // 元
  JIAO: '0.1',       // 角
  FEN: '0.01'        // 分
}

export const ORDER_STATUS = {
  PENDING: 0,        // 待开奖
  WIN: 1,            // 已中奖
  LOSE: 2,           // 未中奖
  CANCELLED: 3       // 已取消
}

export const RECHARGE_STATUS = {
  PENDING: 0,        // 待支付
  SUCCESS: 1,        // 成功
  FAILED: 2,         // 失败
  CANCELLED: 3       // 已取消
}

export const WITHDRAW_STATUS = {
  PENDING: 0,        // 待审核
  PROCESSING: 1,     // 处理中
  SUCCESS: 2,        // 成功
  REJECTED: 3        // 已拒绝
}

export const PAYMENT_METHODS = {
  ALIPAY: 'alipay',     // 支付宝
  WECHAT: 'wechat',     // 微信
  BANK: 'bank',         // 银行卡
  UNION: 'union'        // 云闪付
}

export const HEMAI_TYPES = {
  PUBLIC: 0,            // 完全公开
  AFTER_OPEN: 1,        // 开奖后公开
  FOLLOWER_ONLY: 2,     // 仅跟单人可看
  PRIVATE: 3            // 完全保密
}

export const ROUTES = {
  HOME: '/',
  HOME_NEW: '/home-new',
  LOGIN: '/home-new?auth=login',
  REGISTER: '/home-new?auth=register',
  LOTTERY_HALL: '/lottery-hall',
  BET_RECORD: '/bet-record',
  DEPOSIT: '/payment/deposit',
  WITHDRAW: '/payment/withdraw'
}

export const STORAGE_KEYS = {
  TOKEN: 'token',
  USER_INFO: 'userInfo',
  LANGUAGE: 'language',
  THEME: 'theme'
}
