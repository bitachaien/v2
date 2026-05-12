

import { createRouter, createWebHistory } from 'vue-router'
import { useConfigStore } from '@/stores/config'

import authRoutes from './modules/auth'
import lotteryRoutes from './modules/lottery'
import memberRoutes from './modules/member'
import paymentRoutes from './modules/payment'
import activityRoutes from './modules/activity'
import serviceRoutes from './modules/service'
import imRoutes from './modules/im'
import legacyRoutes from './modules/legacy'
import gameRoutes from './modules/game'

const baseRoutes = [
  {
    path: '/',
    name: 'Home',
    component: () => import('@/views/home/HomeV5.vue'),
    meta: { title: '首页' }
  },
  {
    path: '/home-new',
    name: 'HomeNew',
    component: () => import('@/views/home/HomeV5.vue'),
    meta: { title: '首页' }
  }
]

const errorRoutes = [
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    redirect: '/'
  }
]

const routes = [
  ...baseRoutes,
  ...authRoutes,
  ...lotteryRoutes,
  ...memberRoutes,
  ...paymentRoutes,
  ...activityRoutes,
  ...serviceRoutes,
  ...imRoutes,
  ...gameRoutes,
  ...legacyRoutes, // 旧路径兼容，放在最后
  ...errorRoutes   // 404必须放在最后
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to, from, savedPosition) {

    if (savedPosition) {
      return savedPosition
    } else {
      return { top: 0 }
    }
  }
})

router.beforeEach((to, from, next) => {

  const configStore = useConfigStore()
  document.title = configStore.siteName || '官方平台'
  

  const token = localStorage.getItem('token')
  if (to.meta.requiresAuth && !token) {
    next({ name: 'Home' })
    return
  }
  

  if (to.meta.guest && token) {
    next({ name: 'Home' })
    return
  }
  
  next()
})

router.afterEach((to, from) => {

})
export default router
