import { showConfirmDialog, showToast, showLoadingToast, closeToast } from 'vant'
import { securityApi } from '@/api/security'

export async function checkFundPasswordAndNavigate(router, options = {}) {
  const toast = showLoadingToast({
    message: '检查中...',
    forbidClick: true,
    duration: 0
  })

  try {
    const res = await securityApi.getInfo()
    closeToast()

    if (res.code === 0 && res.data) {
      const { hasFundPwd } = res.data

      if (!hasFundPwd) {
        try {
          await showConfirmDialog({
            title: '提示',
            message: '您还未设置资金密码，请先设置资金密码后再进行提现操作',
            confirmButtonText: '去设置',
            cancelButtonText: '取消',
            confirmButtonColor: '#00bfa5'
          })
          router.push('/security/fund-pwd')
        } catch {
        }
        return false
      }

      const path = options.path || '/payment/withdraw'
      const query = options.query || {}
      router.push({ path, query })
      return true
    } else {
      showToast(res.message || '获取安全信息失败')
      return false
    }
  } catch {
    closeToast()
    showToast('网络错误，请稍后重试')
    return false
  }
}
