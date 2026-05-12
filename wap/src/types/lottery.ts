/**
 * 彩票模块 TypeScript 类型定义
 * 用于提供类型安全和代码提示
 */

// ==================== 基础类型 ====================

/**
 * 彩票状态枚举
 */
export enum LotteryStatus {
  REST = 0,     // 休市
  OPEN = 1,     // 可投注
  CLOSED = 2,   // 封盘
}

/**
 * 错误码枚举
 */
export enum ErrorCode {
  SUCCESS = 0,
  ISSUE_CLOSED = 1002,
  INSUFFICIENT_BALANCE = 1003,
  PLAY_PAUSED = 1004,
  AMOUNT_EXCEEDED = 1005,
  NEED_LOGIN = 2001,
  LOGIN_EXPIRED = 2002,
}

// ==================== API 响应类型 ====================

/**
 * 通用 API 响应结构
 */
export interface ApiResponse<T = unknown> {
  code: number
  message: string
  data: T
}

/**
 * 期号信息
 */
export interface LotteryIssue {
  /** 当前期号 */
  issue: string
  /** 当前期号（别名） */
  currentIssue?: string
  /** 上期期号 */
  lastIssue: string
  /** 上期开奖号码 */
  lastOpenCode: number[] | string
  /** 剩余秒数 */
  countdown: number
  /** 状态: 0休市, 1可投注, 2封盘 */
  status: LotteryStatus
  /** 彩种名称 */
  lotteryName?: string
}

/**
 * 开奖结果
 */
export interface DrawResult {
  /** 期号 */
  issue: string
  /** 开奖号码 */
  openCode: number[]
  /** 号码和值 */
  sum: number
  /** 开奖时间 */
  drawTime?: string
}

// ==================== 玩法相关类型 ====================

/**
 * 单个玩法定义
 */
export interface PlayMode {
  /** 玩法 ID */
  playId: string
  /** 玩法名称 */
  name: string
  /** 赔率 */
  odds: number
  /** 显示类型 */
  display?: 'button' | 'number-picker' | 'grid'
  /** 最小选择数 */
  minPick?: number
  /** 最大选择数 */
  maxPick?: number
}

/**
 * 玩法分组
 */
export interface PlayGroup {
  /** 分组 ID */
  groupId: string
  /** 分组标题 */
  title: string
  /** 分组内的玩法 */
  plays: PlayMode[]
}

/**
 * 赔率数据（API 返回）
 */
export interface OddsData {
  /** 彩种代码 */
  lotteryCode: string
  /** 玩法分组列表 */
  groups: PlayGroup[]
  /** 更新时间 */
  updateTime?: string
}

// ==================== 投注相关类型 ====================

/**
 * 单个投注项
 */
export interface BetItem {
  /** 玩法 ID */
  playId: string
  /** 投注内容（如 "大", "11", "1,2,3"） */
  content: string
  /** 投注金额 */
  amount: number
  /** 赔率（可选，用于前端显示） */
  odds?: number
}

/**
 * 投注请求参数
 */
export interface BetRequest {
  /** 彩种代码 */
  lotteryCode: string
  /** 期号 */
  issue: string
  /** 投注项列表 */
  bets: BetItem[]
  /** 总金额 */
  totalAmount: number
}

/**
 * 投注响应
 */
export interface BetResponse {
  /** 订单 ID */
  orderId: string
  /** 剩余余额 */
  balance: number
  /** 投注时间 */
  betTime?: string
}

/**
 * Store 中的选择项
 */
export interface StoreSelection {
  /** 玩法 ID */
  playId: string
  /** 选择的项目 */
  items: Array<{
    playid: string
    name: string
    rate: number
  }>
}

// ==================== WebSocket 消息类型 ====================

/**
 * WebSocket 消息基类
 */
export interface WsMessage<T = unknown> {
  type: string
  data: T
}

/**
 * 订阅成功消息
 */
export interface WsSubscribedData {
  lotteryCode: string
  current?: LotteryIssue
  currentIssue?: string
  lastIssue?: string
  lastOpenCode?: number[] | string
  countdown?: number
  status?: LotteryStatus
}

/**
 * 倒计时消息
 */
export interface WsCountdownData {
  lotteryCode: string
  currentIssue: string
  countdown: number
  status: LotteryStatus
}

/**
 * 开奖结果消息
 */
export interface WsDrawResultData {
  lotteryCode: string
  issue: string
  openCode: number[] | string
  nextIssue?: string
  nextCountdown?: number
}

/**
 * 投注结算消息
 */
export interface WsBetSettledData {
  lotteryCode: string
  issue: string
  orderId: string
  status: 'win' | 'lose'
  betAmount: number
  winAmount?: number
}

// ==================== 组件 Props 类型 ====================

/**
 * LotteryInfo 组件 Props
 */
export interface LotteryInfoProps {
  showExpect: {
    currFullExpect: string
    lastFullExpect: string
  }
  lastOpenCode: number[]
  gametimes: {
    ms: number
  }
  isDrawing: boolean
}

/**
 * BettingFooter 组件 Props
 */
export interface BettingFooterProps {
  cartCount: number
  lotteryCode: string
  currentIssue: string
  canBet: boolean
}

// ==================== Composable 返回类型 ====================

/**
 * useK3Lottery composable 返回类型
 */
export interface UseK3LotteryReturn {
  // 状态
  lotteryCode: import('vue').Ref<string>
  currentIssue: import('vue').Ref<string>
  lastIssue: import('vue').Ref<string>
  lastOpenCode: import('vue').Ref<number[]>
  countdown: import('vue').Ref<number>
  status: import('vue').Ref<LotteryStatus>
  isLoading: import('vue').Ref<boolean>
  wsConnected: import('vue').Ref<boolean>
  
  // 计算属性
  formattedCountdown: import('vue').ComputedRef<string>
  canBet: import('vue').ComputedRef<boolean>
  isCountdownWarning: import('vue').ComputedRef<boolean>
  isCountdownCritical: import('vue').ComputedRef<boolean>
  
  // 玩法
  playModes: PlayMode[]
  activePlayId: import('vue').Ref<string>
  currentPlayMode: import('vue').ComputedRef<PlayMode | undefined>
  
  // 方法
  parseOpenCode: (code: string | number[]) => number[]
  fetchCurrentIssue: () => Promise<void>
  switchLottery: (newCode: string) => void
  switchPlayMode: (playId: string) => void
  setCountdown: (value: number) => void
  subscribeWs: () => void
  unsubscribeWs: () => void
}

// ==================== 工具类型 ====================

/**
 * 可空类型
 */
export type Nullable<T> = T | null

/**
 * 可能是 Promise 的类型
 */
export type MaybePromise<T> = T | Promise<T>
