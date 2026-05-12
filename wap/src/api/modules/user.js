
import request from '../request'

export default {

  getUserInfo() {
    return request({
      url: '/v1/user/info',
      method: 'get'
    })
  },

  getBalance() {
    return request({
      url: '/v1/user/balance',
      method: 'get'
    })
  },

  changePassword(data) {
    return request({
      url: '/v1/user/password',
      method: 'put',
      data
    })
  },

  uploadFile(formData) {
    return request({
      url: '/v1/upload',
      method: 'post',
      data: formData,
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
  },

  updateProfile(data) {
    return request({
      url: '/v1/user/profile',
      method: 'put',
      data
    })
  }
}

