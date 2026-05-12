

export const TrackEvents = {

  PAGE_VIEW: 'page_view',
  PAGE_LEAVE: 'page_leave',
  

  BET_SUBMIT: 'bet_submit',
  BET_SUCCESS: 'bet_success',
  BET_FAIL: 'bet_fail',
  BET_CANCEL: 'bet_cancel',
  

  NUMBER_SELECT: 'number_select',
  NUMBER_CLEAR: 'number_clear',
  PLAY_MODE_SWITCH: 'play_mode_switch',
  

  LOTTERY_SWITCH: 'lottery_switch',
  

  WS_CONNECT: 'ws_connect',
  WS_DISCONNECT: 'ws_disconnect',
  WS_RECONNECT: 'ws_reconnect',
  WS_ERROR: 'ws_error',
  

  USER_LOGIN: 'user_login',
  USER_LOGOUT: 'user_logout',
  USER_REGISTER: 'user_register',
  

  ERROR_OCCUR: 'error_occur',
  API_ERROR: 'api_error',
  

  API_SLOW: 'api_slow',
  RENDER_SLOW: 'render_slow',
}

const config = {
  enabled: true,                // 是否启用
  debug: import.meta.env?.DEV || false,  // 是否输出调试日志
  sampleRate: 1,               // 采样率 (0-1)
  batchSize: 10,               // 批量发送阈值
  flushInterval: 30000,        // 自动发送间隔（毫秒）
}

let eventQueue = []
let flushTimer = null

export function initTracker(options = {}) {
  Object.assign(config, options)
  
  if (!config.enabled) return
  

  if (flushTimer) clearInterval(flushTimer)
  flushTimer = setInterval(flush, config.flushInterval)
  

  window.addEventListener('beforeunload', flush)
  

  trackPageView()
  
}

export function track(event, data = {}) {
  if (!config.enabled) return
  

  if (Math.random() > config.sampleRate) return
  
  const trackData = {
    event,
    timestamp: Date.now(),
    url: window.location.href,
    path: window.location.pathname,
    referrer: document.referrer,
    userAgent: navigator.userAgent,
    screenWidth: window.screen.width,
    screenHeight: window.screen.height,
    ...data
  }
  

  try {
    const userInfo = localStorage.getItem('userInfo')
    if (userInfo) {
      const user = JSON.parse(userInfo)
      trackData.userId = user.id || user.userId
      trackData.username = user.username
    }
  } catch (e) {

  }
  

  eventQueue.push(trackData)
  
  

  if (eventQueue.length >= config.batchSize) {
    flush()
  }
}

export function flush() {
  if (eventQueue.length === 0) return
  
  const events = [...eventQueue]
  eventQueue = []
  

  
}

export function trackPageView() {
  track(TrackEvents.PAGE_VIEW, {
    title: document.title,
  })
}

export function trackBetSubmit(betInfo) {
  track(TrackEvents.BET_SUBMIT, {
    lotteryCode: betInfo.lotteryCode,
    issue: betInfo.issue,
    totalAmount: betInfo.totalAmount,
    betCount: betInfo.betCount,
  })
}

export function trackBetSuccess(result) {
  track(TrackEvents.BET_SUCCESS, {
    orderId: result.orderId,
    amount: result.amount,
  })
}

export function trackBetFail(error) {
  track(TrackEvents.BET_FAIL, {
    errorCode: error.code,
    errorMessage: error.message,
  })
}

export function trackLotterySwitch(from, to) {
  track(TrackEvents.LOTTERY_SWITCH, { from, to })
}

export function trackPlayModeSwitch(playMode) {
  track(TrackEvents.PLAY_MODE_SWITCH, { playMode })
}

export function trackWsStatus(status, extra = {}) {
  const eventMap = {
    connect: TrackEvents.WS_CONNECT,
    disconnect: TrackEvents.WS_DISCONNECT,
    reconnect: TrackEvents.WS_RECONNECT,
    error: TrackEvents.WS_ERROR,
  }
  track(eventMap[status] || status, extra)
}

export function trackApiError(error) {
  track(TrackEvents.API_ERROR, {
    url: error.url,
    status: error.status,
    message: error.message,
  })
}

export function trackSlowApi(url, duration) {
  track(TrackEvents.API_SLOW, { url, duration })
}

export function destroyTracker() {
  flush()
  if (flushTimer) {
    clearInterval(flushTimer)
    flushTimer = null
  }
  window.removeEventListener('beforeunload', flush)
}

export default {
  TrackEvents,
  initTracker,
  track,
  flush,
  trackPageView,
  trackBetSubmit,
  trackBetSuccess,
  trackBetFail,
  trackLotterySwitch,
  trackPlayModeSwitch,
  trackWsStatus,
  trackApiError,
  trackSlowApi,
  destroyTracker,
}
