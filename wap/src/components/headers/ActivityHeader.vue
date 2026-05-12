<template>
  <div class="activity-header">
    <div class="nav-left" @click="goHome">
      <van-icon name="arrow-left" size="20" />
    </div>
    <div class="nav-tabs">
      <div 
        v-for="(tab, index) in topTabs" 
        :key="index"
        class="nav-tab-item"
        :class="{ active: activeTopTab === index }"
        @click="switchTab(index)"
      >
        <span>{{ tab.name }}</span>
        <div class="active-line" v-if="activeTopTab === index"></div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { setTransitionDirection } from '@/stores/transition'

const router = useRouter()
const route = useRoute()

const topTabs = [
  { name: '活动', path: '/activity' },
  { name: 'VIP', path: '/vip' },
  { name: '返水', path: '/cashback' },
  { name: '待领取', path: '/pending' },
  { name: '利息宝', path: '/interest' },
  { name: '领取记录', path: '/reward-record' },
]

const activeTopTab = computed(() => {
  const idx = topTabs.findIndex(t => t.path === route.path)
  return idx >= 0 ? idx : 0
})

const switchTab = (index) => {
  const tab = topTabs[index]
  if (tab.path && route.path !== tab.path) {

    setTransitionDirection(route.path, tab.path)
    router.replace(tab.path)
  }
}

const goHome = () => {
  sessionStorage.removeItem('activity_back_path')
  setTransitionDirection(route.path, '/')
  router.replace('/')
}
</script>

<style scoped>
.activity-header {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  width: 100%;
  height: 50.59px;
  background: #fff;
  display: flex;
  align-items: center;
  padding: 0 10px;
  border-bottom: 1px solid #eee;
  z-index: 999;
}

.nav-left {
  width: 30px;
  display: flex;
  align-items: center;
}

.nav-tabs {
  flex: 1;
  display: flex;
  overflow-x: auto;
  height: 100%;
  align-items: center;
  gap: 20px;
  padding-left: 10px;
  scrollbar-width: none; 
}
.nav-tabs::-webkit-scrollbar { display: none; }

.nav-tab-item {
  position: relative;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 15px;
  color: #666;
  flex-shrink: 0;
  font-weight: 500;
  cursor: pointer;
}
.nav-tab-item.active {
  color: #009688;
  font-weight: bold;
}
.active-line {
  position: absolute;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 20px;
  height: 3px;
  background: #009688;
  border-radius: 2px;
}
</style>
