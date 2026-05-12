

export default [

  {
    path: '/payment/deposit',
    name: 'PaymentDeposit',
    component: () => import('@/views/payment/DepositNew.vue'),
    meta: {
      title: '存款',
      requiresAuth: true
    }
  },

  {
    path: '/payment/withdraw',
    name: 'PaymentWithdraw',
    component: () => import('@/views/payment/WithdrawNew.vue'),
    meta: {
      title: '申请提现',
      requiresAuth: true
    }
  },

  {
    path: '/account/transfer',
    name: 'AccountTransfer',
    component: () => import('@/views/account/WalletTransfer.vue'),
    meta: {
      title: '额度转换',
      requiresAuth: true
    }
  },
  {
    path: '/account/user-transfer',
    name: 'AccountUserTransfer',
    component: () => import('@/views/account/UserTransfer.vue'),
    meta: {
      title: '用户转账',
      requiresAuth: true
    }
  },
  {
    path: '/account/fanshui',
    name: 'AccountFanshui',
    component: () => import('@/views/account/Fanshui.vue'),
    meta: {
      title: '返水',
      requiresAuth: true
    }
  },
  {
    path: '/account/fanshui-rate',
    name: 'AccountFanshuiRate',
    component: () => import('@/views/account/FanshuiRate.vue'),
    meta: {
      title: '返水比例',
      requiresAuth: false
    }
  },

  {
    path: '/account/today-stats',
    name: 'AccountTodayStats',
    component: () => import('@/views/account/TodayStats.vue'),
    meta: {
      title: '今日统计',
      requiresAuth: true
    }
  },
  {
    path: '/account/bill',
    name: 'AccountBill',
    component: () => import('@/views/account/TimiBillRecord.vue'),
    meta: {
      title: '交易记录',
      requiresAuth: true
    }
  },
]
