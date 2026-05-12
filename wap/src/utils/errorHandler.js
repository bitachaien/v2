

import { showToast, showDialog, showNotify } from 'vant'

export const ErrorLevel = {
  TOAST: 'toast',      // 轻提示，自动消失
  DIALOG: 'dialog',    // 弹窗，需用户确认
  NOTIFY: 'notify',    // 通知栏提示
  SILENT: 'silent'     // 静默，仅日志
}

export const ErrorType = {
  NETWORK: 'network',      // 网络错误
  AUTH: 'auth',            // 认证错误
  VALIDATION: 'validation', // 验证错误
  BUSINESS: 'business',    // 业务错误
  SYSTEM: 'system'         // 系统错误
}

export function reportError(error, context = {}) {
  const errorInfo = {
    message: error?.message || error,
    stack: error?.stack,
    type: context.type || ErrorType.SYSTEM,
    component: context.component,
    action: context.action,
    timestamp: new Date().toISOString(),
    url: window.location.href,
    userAgent: navigator.userAgent,
    ...context.extra
  }
  

  console.error('[ErrorReport]', errorInfo)
  

}

export function handleError(error, options = {}) {
  const {
    level = ErrorLevel.TOAST,
    title = '提示',
    type = ErrorType.BUSINESS,
    report = true,
    onConfirm = null
  } = options
  
  const message = error?.message || error || '操作失败，请稍后重试'
  

  if (report) {
    reportError(error, { type, ...options })
  }
  

  switch (level) {
    case ErrorLevel.DIALOG:
      return showDialog({
        title,
        message,
        confirmButtonColor: '#00FF9A',
      }).then(() => {
        onConfirm?.()
      })
    
    case ErrorLevel.NOTIFY:
      showNotify({
        type: 'danger',
        message,
        duration: 3000
      })
      return Promise.resolve()
    
    case ErrorLevel.TOAST:
      showToast({
        message,
        position: 'middle',
        duration: 2000
      })
      return Promise.resolve()
    
    case ErrorLevel.SILENT:
    default:
      console.error('[Error]', message, error)
      return Promise.resolve()
  }
}

export function handleNetworkError(error) {
  const message = error?.message?.includes('timeout')
    ? '请求超时，请检查网络'
    : error?.message?.includes('Network')
      ? '网络连接失败，请检查网络设置'
      : '网络请求失败'
  
  return handleError(message, {
    level: ErrorLevel.TOAST,
    type: ErrorType.NETWORK
  })
}

export function handleAuthError(message = '登录已过期', options = {}) {
  return handleError(message, {
    level: ErrorLevel.DIALOG,
    title: '认证失败',
    type: ErrorType.AUTH,
    ...options
  })
}

export function handleValidationError(message) {
  return handleError(message, {
    level: ErrorLevel.TOAST,
    type: ErrorType.VALIDATION,
    report: false  
  })
}

export function handleBusinessError(message, options = {}) {
  return handleError(message, {
    level: ErrorLevel.DIALOG,
    title: '提示',
    type: ErrorType.BUSINESS,
    ...options
  })
}

export function showSuccess(message = '操作成功') {
  showToast({
    type: 'success',
    message,
    duration: 1500
  })
}

export function showWarning(message) {
  showNotify({
    type: 'warning',
    message,
    duration: 3000
  })
}

export default {
  ErrorLevel,
  ErrorType,
  handleError,
  handleNetworkError,
  handleAuthError,
  handleValidationError,
  handleBusinessError,
  showSuccess,
  showWarning,
  reportError
}
