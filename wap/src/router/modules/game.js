

export default [
  {
    path: '/game/search',
    name: 'GameSearch',
    component: () => import('@/views/game/SearchGame.vue'),
    meta: { title: '搜索游戏' }
  },
  {
    path: '/game/slot',
    name: 'GameSlot',
    component: () => import('@/views/game/SlotHall.vue'),
    meta: { title: '电子', type: 'slot' }
  },
  {
    path: '/game/chess',
    name: 'GameChess',
    component: () => import('@/views/game/SlotHall.vue'),
    meta: { title: '棋牌', type: 'chess' }
  },
  {
    path: '/game/live',
    name: 'GameLive',
    component: () => import('@/views/game/SlotHall.vue'),
    meta: { title: '真人', type: 'live' }
  },
  {
    path: '/game/fish',
    name: 'GameFish',
    component: () => import('@/views/game/SlotHall.vue'),
    meta: { title: '捕鱼', type: 'fish' }
  },
  {
    path: '/game/lottery',
    name: 'GameLottery',
    component: () => import('@/views/game/SlotHall.vue'),
    meta: { title: '彩票', type: 'lottery' }
  },
  {
    path: '/game/sport',
    name: 'GameSport',
    component: () => import('@/views/game/SlotHall.vue'),
    meta: { title: '体育', type: 'sport' }
  },
  {
    path: '/game/esport',
    name: 'GameEsport',
    component: () => import('@/views/game/SlotHall.vue'),
    meta: { title: '电竞', type: 'esport' }
  },
  {
    path: '/game/blockchain',
    name: 'GameBlockchain',
    component: () => import('@/views/game/SlotHall.vue'),
    meta: { title: '区块链', type: 'blockchain' }
  }
]
