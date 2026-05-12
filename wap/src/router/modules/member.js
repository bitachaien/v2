export default [
  {
    path: '/member/records',
    name: 'MemberRecords',
    component: () => import('@/views/member/MemberRecords.vue'),
    meta: { 
      title: '会员记录',
      requiresAuth: true
    }
  },
  {
    path: '/member',
    name: 'MemberCenter',
    component: () => import('@/views/member/MemberIndexNew.vue'),
    meta: { 
      title: '个人中心',
      requiresAuth: true
    }
  },
  {
    path: '/security',
    name: 'SecurityCenter',
    component: () => import('@/views/security/V5SecurityCenter.vue'),
    meta: { 
      title: '安全中心',
      requiresAuth: true
    }
  },
  {
    path: '/security/realname',
    name: 'SecurityRealName',
    component: () => import('@/views/security/RealName.vue'),
    meta: { title: '实名认证', requiresAuth: true }
  },
  {
    path: '/security/phone',
    name: 'SecurityPhone',
    component: () => import('@/views/security/V5PhoneBind.vue'),
    meta: { title: '密保手机', requiresAuth: true }
  },
  {
    path: '/security/email',
    name: 'SecurityEmail',
    component: () => import('@/views/security/V5EmailBind.vue'),
    meta: { title: '密保邮箱', requiresAuth: true }
  },
  {
    path: '/security/google',
    name: 'SecurityGoogle',
    component: () => import('@/views/security/V5GoogleBind.vue'),
    meta: { title: '绑定Google验证器', requiresAuth: true }
  },
  {
    path: '/security/question',
    name: 'SecurityQuestion',
    component: () => import('@/views/security/V5SecurityQuestion.vue'),
    meta: { title: '密保问题', requiresAuth: true }
  },
  {
    path: '/security/login-pwd',
    name: 'SecurityLoginPwd',
    component: () => import('@/views/security/V5LoginPwd.vue'),
    meta: { title: '修改登录密码', requiresAuth: true }
  },
  {
    path: '/security/fund-pwd',
    name: 'SecurityFundPwd',
    component: () => import('@/views/security/V5FundPwd.vue'),
    meta: { title: '修改资金密码', requiresAuth: true }
  },
  {
    path: '/security/devices',
    name: 'LoginDevice',
    component: () => import('@/views/security/LoginDevice.vue'),
    meta: { title: '登录设备', requiresAuth: true }
  },
  {
    path: '/member/history',
    name: 'MemberGameHistory',
    component: () => import('@/views/member/GameHistory.vue'),
    meta: { 
      title: '游戏记录',
      requiresAuth: true
    }
  },
  {
    path: '/member/invite',
    name: 'MemberInvite',
    component: () => import('@/views/invite/ShareEarn.vue'),
    meta: { 
      title: '分享赚钱',
      requiresAuth: true
    }
  },
  {
    path: '/account/change-password',
    name: 'ChangePassword',
    component: () => import('@/views/account/ChangePassword.vue'),
    meta: { 
      title: '修改密码',
      requiresAuth: true
    }
  },
  {
    path: '/member/recover-balance',
    name: 'RecoverBalance',
    component: () => import('@/views/member/RecoverBalance.vue'),
    meta: { 
      title: '找回余额',
      requiresAuth: true
    }
  }
]
