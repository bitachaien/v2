

export default [
  {
    path: '/activity',
    name: 'ActivityIndex',
    component: () => import('@/views/activity/ActivityIndexNew.vue'),
    meta: { title: '活动中心', tabIndex: 0 }
  },
  {
    path: '/vip',
    name: 'VipCenter',
    component: () => import('@/views/activity/ActivityIndexNew.vue'),
    meta: { title: 'VIP中心', tabIndex: 1 }
  },
  {
    path: '/cashback',
    name: 'Cashback',
    component: () => import('@/views/activity/ActivityIndexNew.vue'),
    meta: { title: '返水', tabIndex: 2 }
  },
  {
    path: '/pending',
    name: 'PendingReward',
    component: () => import('@/views/activity/ActivityIndexNew.vue'),
    meta: { title: '待领取', tabIndex: 3 }
  },
  {
    path: '/interest',
    name: 'InterestTreasure',
    component: () => import('@/views/activity/ActivityIndexNew.vue'),
    meta: { title: '利息宝', tabIndex: 4 }
  },
  {
    path: '/reward-record',
    name: 'RewardRecord',
    component: () => import('@/views/activity/ActivityIndexNew.vue'),
    meta: { title: '领取记录', tabIndex: 5 }
  },
  {
    path: '/activity/detail/:id',
    name: 'ActivityDetail',
    component: () => import('@/views/activity/ActivityDetailNew.vue'),
    meta: { title: '活动详情' }
  },

  {
    path: '/activity/lucky-order/:id',
    name: 'LuckyOrderActivity',
    component: () => import('@/views/activity/LuckyOrderActivity.vue'),
    meta: { title: '幸运注单' }
  },
  {
    path: '/activity/loss-rescue/:id',
    name: 'LossRescueActivity',
    component: () => import('@/views/activity/LossRescueActivity.vue'),
    meta: { title: '亏损救援金' }
  },
  {
    path: '/activity/weekly-salary/:id',
    name: 'WeeklySalaryActivity',
    component: () => import('@/views/activity/WeeklySalaryActivity.vue'),
    meta: { title: '周俸禄' }
  },
  {
    path: '/activity/pg-betting-king/:id',
    name: 'PgBettingKingActivity',
    component: () => import('@/views/activity/PgBettingKingActivity.vue'),
    meta: { title: 'PG打码王' }
  },

  {
    path: '/activity/reward/:id',
    name: 'ActivityReward',
    component: () => import('@/views/activity/ActivityRewardDetail.vue'),
    meta: { title: '活动奖励' }
  }
]
