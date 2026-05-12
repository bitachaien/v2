<template>
  <div class="v5-footer">
    <div 
      class="tab-item" 
      v-for="(item, index) in navItems" 
      :key="index"
      :class="{ active: activePath === item.path }"
      @click="handleClick(item)"
    >
      <van-icon :name="activePath === item.path ? item.activeIcon : item.icon" size="28.66px" />
      <span>{{ item.name }}</span>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { isLoggedIn } from '@/utils/auth'
import { checkFundPasswordAndNavigate } from '@/utils/withdrawCheck'
import { setTransitionDirection } from '@/stores/transition'

const router = useRouter()
const route = useRoute()
const activePath = computed(() => {
  if (route.path === '/') return '/home-new'
  const activityPaths = ['/activity', '/vip', '/cashback', '/pending', '/interest', '/reward-record']
  if (activityPaths.includes(route.path)) return '/activity'
  return route.path
})
const isLogin = ref(false)

const emit = defineEmits(['open-auth', 'open-deposit'])

onMounted(() => {
  isLogin.value = isLoggedIn()
})

const handleClick = async (item) => {
  if (item.path === '/login') {
    emit('open-auth', 'login')
  } else if (item.path === '/register') {
    emit('open-auth', 'register')
  } else if (item.action === 'deposit') {
    emit('open-deposit')
  } else if (item.action === 'withdraw') {
  } else {
    setTransitionDirection(route.path, item.path)
    router.push(item.path)
  }
}

const navItems = computed(() => {
  if (isLogin.value) {
    return [
      { name: '首页', icon: 'wap-home-o', activeIcon: 'wap-home', path: '/home-new' },
      { name: '优惠', icon: 'gift-o', activeIcon: 'gift', path: '/activity' },
      { name: '聊天室', icon: 'chat-o', activeIcon: 'chat', path: '/im' },
      { name: '存款', icon: 'gold-coin-o', activeIcon: 'gold-coin', path: '', action: 'deposit' },
      { name: '我的', icon: 'user-o', activeIcon: 'user', path: '/member' }
    ]
  } else {
    return [
      { name: '首页', icon: 'wap-home-o', activeIcon: 'wap-home', path: '/home-new' },
      { name: '优惠', icon: 'gift-o', activeIcon: 'gift', path: '/activity' },
      { name: '聊天室', icon: 'chat-o', activeIcon: 'chat', path: '/im' },
      { name: '登录', icon: 'manager-o', activeIcon: 'manager', path: '/login' },
      { name: '我的', icon: 'user-o', activeIcon: 'user', path: '/member' }
    ]
  }
})
</script>

<style lang="scss" scoped>
.v5-footer {
  height: 71px;
  background: #fff;
  border-top: 1px solid #f5f5f5;
  display: flex;
  justify-content: space-around;
  align-items: center;
  padding-bottom: env(safe-area-inset-bottom);
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 100;
}

.tab-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  color: #999;

  span {
    font-size: 12px;
    margin-top: 2px;
    font-weight: 500;
  }

  &.active {
    color: #26A17B;
  }
}
</style>
