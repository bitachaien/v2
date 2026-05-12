<template>
  <div class="app-wrapper">
    <HomeHeader v-if="showHomeHeader" />
    <ActivityHeader v-if="showActivityHeader" />
    
    <router-view v-slot="{ Component, route: currentRoute }">
      <transition :name="transitionName">
        <div 
          class="page-container"
          :key="currentRoute.path"
          :style="{ 
            paddingBottom: isImPage(currentRoute.path) ? '0' : '',
            top: getHeaderHeight(currentRoute.path)
          }"
        >
          <component :is="Component" />
        </div>
      </transition>
    </router-view>
    
    <FooterNav v-if="showFooter" @open-deposit="showDepositPopup = true" />
    <DepositPopup v-model:show="showDepositPopup" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import FooterNav from '@/views/home/components-v5/V5Footer.vue'
import HomeHeader from '@/views/home/components-v5/V5Header.vue'
import ActivityHeader from '@/components/headers/ActivityHeader.vue'
import DepositPopup from '@/components/deposit/DepositPopup.vue'
import { lotteryWS } from '@/utils/websocket'
import { useUserStore } from '@/stores/user'
import { heartbeatService } from '@/utils/heartbeat'
import { transitionDirection, setTransitionDirection } from '@/stores/transition'

const route = useRoute()
const router = useRouter()
const userStore = useUserStore()
const showDepositPopup = ref(false)
const isRouterReady = ref(false)

const homePaths = ['/', '/home-new']
const footerPaths = ['/', '/home-new', '/activity', '/vip', '/cashback', '/pending', '/interest', '/reward-record', '/member']
const activityPaths = ['/activity', '/vip', '/cashback', '/pending', '/interest', '/reward-record']

const isImPage = (path) => path.startsWith('/im')

const getHeaderHeight = (path) => {
  if (homePaths.includes(path)) return '50px'
  if (activityPaths.includes(path)) return '50.59px'
  return '0'
}

const transitionName = transitionDirection

const getPageKey = (route) => {
  if (activityPaths.includes(route.path)) return 'ActivityGroup'
  return route.path
}

router.isReady().then(() => {
  setTimeout(() => {
    isRouterReady.value = true
  }, 50)
})

router.beforeEach((to, from) => {
  setTransitionDirection(from.path, to.path)
  const isActivityDetailPage = (path) => path.startsWith('/activity/') && path !== '/activity'
  if (activityPaths.includes(to.path) && !activityPaths.includes(from.path) && !isActivityDetailPage(from.path)) {
    if (from.path !== '/' && from.path !== '/home-new') {
      sessionStorage.setItem('activity_back_path', from.path)
    } else {
      sessionStorage.removeItem('activity_back_path')
    }
  }
})

const showFooter = computed(() => isRouterReady.value && footerPaths.includes(route.path))
const showHomeHeader = computed(() => isRouterReady.value && homePaths.includes(route.path))
const showActivityHeader = computed(() => isRouterReady.value && activityPaths.includes(route.path))

onMounted(() => {
  lotteryWS.connect().catch(() => {})
  userStore.initWsListeners()
  
  const token = localStorage.getItem('token')
  if (token) {
    heartbeatService.start(1000)
  }
})

onUnmounted(() => {
  userStore.cleanupWsListeners()
  lotteryWS.disconnect()
  heartbeatService.stop()
})
</script>

<style>
.app-wrapper {
  position: relative;
  width: 100%;
  height: 100vh;
  overflow: hidden;
  background: #fff;
}

.page-container {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: #fff;
  overflow-x: hidden;
  overflow-y: auto;
  will-change: transform;
  -webkit-overflow-scrolling: touch;
  padding-bottom: calc(71px + env(safe-area-inset-bottom));
}

.page-container > * {
  background: #fff;
  min-height: 100%;
}

.none-enter-active,
.none-leave-active {
  transition: none;
}
.none-enter-from,
.none-leave-to {
  opacity: 1;
}

.slide-left-enter-active {
  position: absolute !important;
  left: 0;
  width: 100%;
  height: 100%;
  background: #fff;
  transition: transform 0.3s ease-out;
  z-index: 10;
}
.slide-left-leave-active {
  position: absolute !important;
  left: 0;
  width: 100%;
  height: 100%;
  background: #fff;
  transition: transform 0.3s ease-out;
  z-index: 1;
}
.slide-left-enter-from { transform: translateX(100%); }
.slide-left-enter-to { transform: translateX(0); }
.slide-left-leave-from { transform: translateX(0); }
.slide-left-leave-to { transform: translateX(-100%); }

.slide-right-enter-active {
  position: absolute !important;
  left: 0;
  width: 100%;
  height: 100%;
  background: #fff;
  transition: transform 0.3s ease-out;
  z-index: 10;
}
.slide-right-leave-active {
  position: absolute !important;
  left: 0;
  width: 100%;
  height: 100%;
  background: #fff;
  transition: transform 0.3s ease-out;
  z-index: 1;
}
.slide-right-enter-from { transform: translateX(-100%); }
.slide-right-enter-to { transform: translateX(0); }
.slide-right-leave-from { transform: translateX(0); }
.slide-right-leave-to { transform: translateX(100%); }

.tab-slide-left-enter-active,
.tab-slide-left-leave-active {
  transition: transform 0.3s ease-out, opacity 0.3s ease-out;
}
.tab-slide-left-enter-from { transform: translateX(100%); opacity: 0; }
.tab-slide-left-enter-to { transform: translateX(0); opacity: 1; }
.tab-slide-left-leave-from { transform: translateX(0); opacity: 1; }
.tab-slide-left-leave-to { transform: translateX(-100%); opacity: 0; }

.tab-slide-right-enter-active,
.tab-slide-right-leave-active {
  transition: transform 0.3s ease-out, opacity 0.3s ease-out;
}
.tab-slide-right-enter-from { transform: translateX(-100%); opacity: 0; }
.tab-slide-right-enter-to { transform: translateX(0); opacity: 1; }
.tab-slide-right-leave-from { transform: translateX(0); opacity: 1; }
.tab-slide-right-leave-to { transform: translateX(100%); opacity: 0; }
</style>
