<template>
  <div class="activity-new-page">
    <div class="main-content">
      <router-view v-slot="{ Component }">
        <transition :name="tabTransition">
          <component :is="Component || currentComponent" :key="route.path" class="tab-page" />
        </transition>
      </router-view>
    </div>

    <DepositPopup v-model:show="showDepositPopup" />
  </div>
</template>

<script setup>
defineOptions({ name: 'Activity' })

import { ref, computed, markRaw, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import DepositPopup from '@/components/deposit/DepositPopup.vue'
import TabActivity from './components/TabActivity.vue'
import TabVip from './components/TabVip.vue'
import TabRebate from './components/TabRebate.vue'
import TabPending from './components/TabPending.vue'
import TabInterest from './components/TabInterest.vue'
import TabRecord from './components/TabRecord.vue'

const router = useRouter()
const route = useRoute()
const showDepositPopup = ref(false)
const tabTransition = ref('tab-slide-left')

const topTabs = [
  { name: 'Hoạt động', path: '/activity', component: markRaw(TabActivity) },
  { name: 'VIP', path: '/vip', component: markRaw(TabVip) },
  { name: 'Hoàn trả', path: '/cashback', component: markRaw(TabRebate) },
  { name: 'Chờ nhận', path: '/pending', component: markRaw(TabPending) },
  { name: 'Lãi suất', path: '/interest', component: markRaw(TabInterest) },
  { name: 'Lịch sử nhận', path: '/reward-record', component: markRaw(TabRecord) },
]

const getTabIndex = (path) => topTabs.findIndex(t => t.path === path)

const activeTopTab = computed(() => {
  const idx = getTabIndex(route.path)
  return idx >= 0 ? idx : 0
})

const currentComponent = computed(() => topTabs[activeTopTab.value].component)

watch(() => route.path, (to, from) => {
  const toIdx = getTabIndex(to)
  const fromIdx = getTabIndex(from)
  if (toIdx >= 0 && fromIdx >= 0) {
    tabTransition.value = toIdx > fromIdx ? 'tab-slide-left' : 'tab-slide-right'
  }
})

const switchTab = (index) => {
  const tab = topTabs[index]
  if (tab.path && route.path !== tab.path) {
    router.push(tab.path)
  }
}
</script>

<style scoped>
.activity-new-page {
  height: 100%;
  display: flex;
  flex-direction: column;
  background: var(--skin-bg-1);
  font-family: -apple-system, BlinkMacSystemFont, "PingFang SC", "Helvetica Neue", Arial, sans-serif;
}

.main-content {
  flex: 1;
  position: relative;
  overflow: hidden;
}

.tab-page {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
  background: var(--skin-bg-1);
}

.tab-slide-left-enter-active,
.tab-slide-left-leave-active {
  transition: transform 0.5s cubic-bezier(0.25, 0.1, 0.25, 1);
}
.tab-slide-left-enter-from {
  transform: translateX(100%);
}
.tab-slide-left-enter-to {
  transform: translateX(0);
}
.tab-slide-left-leave-from {
  transform: translateX(0);
}
.tab-slide-left-leave-to {
  transform: translateX(-100%);
}

.tab-slide-right-enter-active,
.tab-slide-right-leave-active {
  transition: transform 0.5s cubic-bezier(0.25, 0.1, 0.25, 1);
}
.tab-slide-right-enter-from {
  transform: translateX(-100%);
}
.tab-slide-right-enter-to {
  transform: translateX(0);
}
.tab-slide-right-leave-from {
  transform: translateX(0);
}
.tab-slide-right-leave-to {
  transform: translateX(100%);
}
</style>
