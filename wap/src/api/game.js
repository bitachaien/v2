import request from './request'

export const gameApi = {
  getPlatforms(params = {}) {
    return request({
      url: '/v1/game/platforms',
      method: 'get',
      params
    })
  },

  getGameList(params) {
    return request({
      url: '/v1/game/list',
      method: 'get',
      params
    })
  },

  enterGame(data) {
    return request({
      url: '/v1/game/enter',
      method: 'post',
      data
    })
  },

  getBalance(platform) {
    return request({
      url: `/v1/game/balance/${platform}`,
      method: 'get'
    })
  },

  transferIn(data) {
    return request({
      url: '/v1/game/transfer/in',
      method: 'post',
      data
    })
  },

  transferOut(data) {
    return request({
      url: '/v1/game/transfer/out',
      method: 'post',
      data
    })
  },

  recallAll() {
    return request({
      url: '/v1/game/transfer/recall-all',
      method: 'post'
    })
  },

  getPlatformBalances() {
    return request({
      url: '/v1/game/platform-balances',
      method: 'get'
    })
  },

  refreshPlatformBalances() {
    return request({
      url: '/v1/game/refresh-platform-balances',
      method: 'post'
    })
  },

  recoverAllBalance() {
    return request({
      url: '/v1/game/recover-all',
      method: 'post'
    })
  },

  recoverPlatformBalance(data) {
    return request({
      url: '/v1/game/recover',
      method: 'post',
      data
    })
  },

  getRecords(params = {}) {
    return request({
      url: '/v1/game/records',
      method: 'get',
      params
    })
  },

  getHotGames(params = {}) {
    return request({
      url: '/v1/game/hot',
      method: 'get',
      params
    })
  },

  search(params) {
    return request({
      url: '/v1/game/search',
      method: 'get',
      params
    })
  },

  getCategories() {
    return request({
      url: '/v1/game/categories',
      method: 'get'
    })
  },

  getLotteryCategories() {
    return request({
      url: '/v1/lottery/categories',
      method: 'get'
    })
  },

  addFavorite(data) {
    return request({
      url: '/v1/game/favorite/add',
      method: 'post',
      data
    })
  },

  removeFavorite(data) {
    return request({
      url: '/v1/game/favorite/remove',
      method: 'post',
      data
    })
  },

  getFavorites(params = {}) {
    return request({
      url: '/v1/game/favorites',
      method: 'get',
      params
    })
  },

  addRecent(data) {
    return request({
      url: '/v1/game/recent/add',
      method: 'post',
      data
    })
  },

  getRecent(params = {}) {
    return request({
      url: '/v1/game/recent',
      method: 'get',
      params
    })
  }
}
