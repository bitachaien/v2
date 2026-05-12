import request from './request'

export const withdrawApi = {
  getConfig() {
    return request({ url: '/v1/withdraw/config', method: 'get' })
  },
  getAccounts() {
    return request({ url: '/v1/withdraw/accounts', method: 'get' })
  },
  submit(data) {
    return request({ url: '/v1/withdraw/submit', method: 'post', data })
  },
  getRecords(params) {
    return request({ url: '/v1/withdraw/records', method: 'get', params })
  },
  cancel(data) {
    return request({ url: '/v1/withdraw/cancel', method: 'post', data })
  },
  addAccount(data) {
    return request({ url: '/v1/withdraw/account/add', method: 'post', data })
  },
  setDefaultAccount(id) {
    return request({ url: '/v1/withdraw/account/default', method: 'post', data: { id } })
  },
  deleteAccount(id) {
    return request({ url: '/v1/withdraw/account/delete', method: 'post', data: { id } })
  },
  changePassword(data) {
    return request({ url: '/v1/auth/change-password', method: 'post', data })
  },
  changeFundPassword(data) {
    return request({ url: '/v1/auth/change-fund-password', method: 'post', data })
  },
  setFundPassword(data) {
    return request({ url: '/v1/auth/set-fund-password', method: 'post', data })
  },
  uploadQrCode(file) {
    const formData = new FormData()
    formData.append('file', file)
    return request({
      url: '/v1/withdraw/upload-qrcode',
      method: 'post',
      data: formData,
      headers: { 'Content-Type': 'multipart/form-data' }
    })
  }
}
