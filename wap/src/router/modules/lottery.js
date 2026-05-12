

export default [

  {
    path: '/lottery/ssc/:code?',
    name: 'LotterySsc',
    component: () => import('@/views/lottery/ssc/Index.vue'),
    meta: { 
      title: '时时彩',
      requiresAuth: false,
      keepAlive: true
    }
  },

  {
    path: '/lottery/x5/:code?',
    name: 'LotteryX5',
    component: () => import('@/views/lottery/x5/Index.vue'),
    meta: { 
      title: '11选5',
      requiresAuth: false,
      keepAlive: true
    }
  },

  {
    path: '/lottery/kl8/:code?',
    name: 'LotteryKl8',
    component: () => import('@/views/lottery/kl8/Index.vue'),
    meta: { 
      title: '快乐8',
      requiresAuth: false,
      keepAlive: true
    }
  },

  {
    path: '/lottery/kl10/:code?',
    name: 'LotteryKl10',
    component: () => import('@/views/lottery/kl10/Index.vue'),
    meta: { 
      title: '快乐十分',
      requiresAuth: false,
      keepAlive: true
    }
  },

  {
    path: '/lottery/k3/:code?',
    name: 'LotteryK3',
    component: () => import('@/views/lottery/k3/K3.vue'),
    meta: { 
      title: '快三',
      requiresAuth: false,
      keepAlive: true
    }
  },
  {
    path: '/lottery/k3-double/:code?',
    name: 'LotteryK3Double',
    component: () => import('@/views/lottery/k3/K3Double.vue'),
    meta: { title: '快三双面盘' }
  },

  {
    path: '/lottery/pk10/:code?',
    name: 'LotteryPk10',
    component: () => import('@/views/lottery/pk10/Pk10.vue'),
    meta: { 
      title: 'PK10',
      requiresAuth: false,
      keepAlive: true
    }
  },
  {
    path: '/lottery/pk10-animal/:code?',
    name: 'LotteryPk10Animal',
    component: () => import('@/views/lottery/pk10/Pk10Animal.vue'),
    meta: { title: '趣味动物' }
  },

  {
    path: '/lottery/fc3d/:code?',
    name: 'LotteryFc3d',
    component: () => import('@/views/lottery/fc3d/Fc3d.vue'),
    meta: { title: '福彩3D' }
  },

  {
    path: '/lottery/pl3/:code?',
    name: 'LotteryPl3',
    component: () => import('@/views/lottery/pl3/Pl3.vue'),
    meta: { title: '排列三' }
  },

  {
    path: '/lottery/hn7xc/:code?',
    name: 'LotteryHn7xc',
    component: () => import('@/views/lottery/hn7xc/Hn7xc.vue'),
    meta: { title: '海南七星彩' }
  },
  {
    path: '/lottery/hn7xc-new',
    name: 'LotteryHn7xcNew',
    component: () => import('@/views/lottery/hn7xc/components/1.vue'),
    meta: { title: '海南七星彩(新版)' }
  },

  {
    path: '/lottery/xy28-chat/:code?',
    name: 'Xy28Chat',
    component: () => import('@/views/lottery/xy28/Xy28Chat.vue'),
    meta: { title: '28聊天室' }
  },

  {
    path: '/lottery/xy28/:code?',
    redirect: to => ({ path: `/lottery/xy28-chat/${to.params.code || 'yfxy28'}` })
  },
  {
    path: '/lottery/xy28/:code/:mode',
    redirect: to => ({ path: `/lottery/xy28-chat/${to.params.code || 'yfxy28'}` })
  },
  {
    path: '/lottery/xy28-help',
    name: 'Xy28Help',
    component: () => import('@/views/lottery/xy28/Xy28Help.vue'),
    meta: { title: '桌投说明' }
  },

  {
    path: '/lottery/xy28-history/:code?',
    name: 'Xy28History',
    component: () => import('@/views/lottery/xy28/Xy28History.vue'),
    meta: { title: '历史开奖' }
  },

  {
    path: '/lottery/room-settings/:code?',
    name: 'RoomSettings',
    component: () => import('@/views/lottery/xy28/RoomSettings.vue'),
    meta: { title: '房间设置' }
  },

  {
    path: '/lottery/room-members/:code?',
    name: 'RoomMembers',
    component: () => import('@/views/lottery/xy28/RoomMembers.vue'),
    meta: { title: '房间成员' }
  },

  {
    path: '/lottery/lhc/:code?',
    name: 'LotteryLhc',
    component: () => import('@/views/lottery/lhc/Lhc.vue'),
    meta: { title: '六合彩' }
  },
  {
    path: '/lottery/lhc/:code/:mode',
    name: 'LotteryLhcHot',
    component: () => import('@/views/lottery/lhc/Lhc.vue'),
    meta: { title: '六合彩-双面模式' }
  },

  {
    path: '/lottery/hall',
    name: 'LotteryHall',
    component: () => import('@/views/game/LotteryHall.vue'),
    meta: { title: '彩票大厅' }
  },
  {
    path: '/lottery/hall/hemai',
    name: 'LotteryHemaiHall',
    component: () => import('@/views/game/LotteryAllHemaiTech.vue'),
    meta: { title: '合买大厅' }
  },
  {
    path: '/hemai/records',
    name: 'LotteryHemaiRecords',
    component: () => import('@/views/game/LotteryHemaiRecords.vue'),
    meta: { title: '我的合买' }
  },
  {
    path: '/hemai/create',
    name: 'LotteryHemaiCreate',
    component: () => import('@/views/game/LotteryHemaiCreate.vue'),
    meta: { title: '发起合买' }
  },
  {
    path: '/hemai/detail',
    name: 'LotteryHemaiDetail',
    component: () => import('@/views/game/LotteryHemaiDetail.vue'),
    meta: { title: '合买详情' }
  },

  {
    path: '/lottery/signin',
    name: 'LotterySignIn',
    component: () => import('@/views/game/LotterySignIn.vue'),
    meta: { title: '每日签到', requiresAuth: true }
  },
  {
    path: '/lottery/trend/:type?',
    name: 'LotteryTrend',
    component: () => import('@/views/game/Trend.vue'),
    meta: { title: '开奖走势' }
  },
  {
    path: '/lottery/details/:id?',
    name: 'LotteryDetails',
    component: () => import('@/views/game/GameDetails.vue'),
    meta: { title: '游戏详情' }
  }
]
