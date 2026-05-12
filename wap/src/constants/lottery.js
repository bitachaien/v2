

export const COUNTDOWN = {
  WARNING_THRESHOLD: 10,      // 倒计时警告阈值（秒）- 显示红色
  CRITICAL_THRESHOLD: 5,      // 倒计时紧急阈值（秒）- 闪烁
  MAX_VALID_VALUE: 1200,      // 最大有效倒计时值（20分钟）
  MIN_VALID_VALUE: 0,         // 最小有效倒计时值
}

export const BET_AMOUNT = {
  MIN: 0.01,                  // 最小投注金额（元）
  MAX: 10000,                 // 最大单注金额（元）
  MAX_TOTAL: 100000,          // 单期最大投注总额（元）
  DEFAULT_DENOMINATION: 2,    // 默认单注金额
  QUICK_OPTIONS: [2, 5, 10],  // 快捷金额选项
}

export const MULTIPLIER = {
  MIN: 1,                     // 最小倍数
  MAX: 9999,                  // 最大倍数
  DEFAULT: 1,                 // 默认倍数
}

export const CACHE = {
  ODDS_TTL: 5 * 60 * 1000,    // 赔率缓存时间（5分钟）
  ISSUE_TTL: 30 * 1000,       // 期号缓存时间（30秒）
  HISTORY_TTL: 60 * 1000,     // 历史记录缓存时间（1分钟）
}

export const API = {
  MIN_FETCH_INTERVAL: 5000,   // 最小请求间隔（毫秒）
  REQUEST_TIMEOUT: 30000,     // 请求超时时间（毫秒）
}

export const WEBSOCKET = {
  RECONNECT_DELAY: 3000,      // 初始重连延迟（毫秒）
  MAX_RECONNECT_DELAY: 30000, // 最大重连延迟（毫秒）
  MAX_RECONNECT_ATTEMPTS: 10, // 最大重连次数
  HEARTBEAT_INTERVAL: 20000,  // 心跳间隔（毫秒）- 缩短以适应代理超时
  PONG_TIMEOUT: 10000,        // pong 响应超时（毫秒）
}

export const STATUS = {
  REST: 0,                    // 休市
  OPEN: 1,                    // 可投注
  CLOSED: 2,                  // 封盘
}

export const ERROR_CODES = {

  SUCCESS: 0,                 // 成功
  SYSTEM_ERROR: 500,          // 系统错误
  INVALID_PARAMS: 400,        // 参数错误
  

  UNAUTHORIZED: 401,          // 未登录
  FORBIDDEN: 403,             // 无权限
  TOKEN_EXPIRED: 10001,       // Token已过期
  TOKEN_INVALID: 10002,       // Token无效或已被注销
  TOKEN_MISSING: 10003,       // Token缺失
  

  USER_NOT_FOUND: 2001,       // 用户不存在
  INSUFFICIENT_BALANCE: 2002, // 余额不足
  

  BET_INSUFFICIENT_BALANCE: 1001,  // 投注余额不足
  BET_EXPECT_CLOSED: 1002,    // 期号已截止
  BET_INVALID_DATA: 1003,     // 投注数据无效
  BET_OVER_LIMIT: 1004,       // 超出限额
}

export const ERROR_MESSAGES = {
  [ERROR_CODES.SUCCESS]: '操作成功',
  [ERROR_CODES.SYSTEM_ERROR]: '系统错误，请稍后重试',
  [ERROR_CODES.INVALID_PARAMS]: '参数错误',
  [ERROR_CODES.UNAUTHORIZED]: '未登录',
  [ERROR_CODES.FORBIDDEN]: '无权限访问',
  [ERROR_CODES.TOKEN_EXPIRED]: 'Token已过期，请重新登录',
  [ERROR_CODES.TOKEN_INVALID]: 'Token无效或已被注销',
  [ERROR_CODES.TOKEN_MISSING]: '缺少认证信息',
  [ERROR_CODES.USER_NOT_FOUND]: '用户不存在',
  [ERROR_CODES.INSUFFICIENT_BALANCE]: '余额不足',
  [ERROR_CODES.BET_INSUFFICIENT_BALANCE]: '投注余额不足',
  [ERROR_CODES.BET_EXPECT_CLOSED]: '该期已截止，请稍后重试',
  [ERROR_CODES.BET_INVALID_DATA]: '投注数据无效',
  [ERROR_CODES.BET_OVER_LIMIT]: '超出限额',
}

export function isAuthError(code) {
  return [ERROR_CODES.UNAUTHORIZED, ERROR_CODES.TOKEN_EXPIRED, ERROR_CODES.TOKEN_INVALID, ERROR_CODES.TOKEN_MISSING].includes(code)
}

export const K3_PLAY_MODES = [
  { id: 'hezhi', name: '和值', tips: '从3-18猜中开奖的3个骰子总和' },
  { id: 'daxiao', name: '大小', tips: '和值3-10为小，11-18为大' },
  { id: 'danshuang', name: '单双', tips: '和值为奇数为单，偶数为双' },
  { id: 'santonghaotx', name: '三同号通选', tips: '三个骰子相同，通选所有豹子' },
  { id: 'santonghaodx', name: '三同号单选', tips: '选择一个具体的豹子号码' },
  { id: 'sanlianhao', name: '三连号', tips: '开出连续的三个数字' },
  { id: 'ertonghaofx', name: '二同号复选', tips: '选一对重复号和一个不同号' },
  { id: 'ertonghaodx', name: '二同号单选', tips: '精确选择对子和不同号' },
  { id: 'erbutonghao', name: '二不同号', tips: '选2个不同号，包含即中奖' },
  { id: 'sanbutonghao', name: '三不同号', tips: '选3个不同号，全中即中奖' },
  { id: 'hongheima', name: '红黑码', tips: '红码(345)黑码(126)玩法' },
]

export default {
  COUNTDOWN,
  BET_AMOUNT,
  MULTIPLIER,
  CACHE,
  API,
  WEBSOCKET,
  STATUS,
  ERROR_CODES,
  ERROR_MESSAGES,
  K3_PLAY_MODES,
  isAuthError,
}
