import request from './request'

export const securityApi = {
  getInfo() {
    return request({ url: '/v1/security/info', method: 'get' })
  },
  sendPhoneCode(phone) {
    return request({ url: '/v1/security/phone/send-code', method: 'post', data: { phone } })
  },
  bindPhone(data) {
    return request({ url: '/v1/security/phone/bind', method: 'post', data })
  },
  sendEmailCode(email) {
    return request({ url: '/v1/security/email/send-code', method: 'post', data: { email } })
  },
  bindEmail(data) {
    return request({ url: '/v1/security/email/bind', method: 'post', data })
  },
  getGoogleSecret() {
    return request({ url: '/v1/security/google/secret', method: 'get' })
  },
  bindGoogle(code) {
    return request({ url: '/v1/security/google/bind', method: 'post', data: { code } })
  },
  getQuestionList() {
    return request({ url: '/v1/security/question/list', method: 'get' })
  },
  setQuestion(data) {
    return request({ url: '/v1/security/question/set', method: 'post', data })
  },
  setFundPassword(data) {
    return request({ url: '/v1/auth/set-fund-password', method: 'post', data })
  },
  changeFundPassword(data) {
    return request({ url: '/v1/auth/change-fund-password', method: 'post', data })
  },
  changePassword(data) {
    return request({ url: '/v1/auth/change-password', method: 'post', data })
  },
  verifyFundPwd(data) {
    return request({ url: '/v1/security/verify-fund-pwd', method: 'post', data })
  }
}
