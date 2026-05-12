<template>
  <div class="home-v5">
    <div class="main-scroll">
      <V5Banner />
      <V5Notice />
      <V5AuthQuick @open-auth="openAuth" />
      <V5GameContainer />
      <div style="height: 20px"></div>
    </div>

    <V5AuthModal v-model="showAuthModal" :initial-tab="authTab" />
    <DepositPopup v-model:show="showDepositPopup" />
  </div>
</template>

<script setup>
defineOptions({ name: 'HomeV5' })

import { ref, provide, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import V5Banner from './components-v5/V5Banner.vue'
import V5Notice from './components-v5/V5Notice.vue'
import V5AuthQuick from './components-v5/V5AuthQuick.vue'
import V5GameContainer from './components-v5/V5GameContainer.vue'
import V5AuthModal from './components-v5/V5AuthModal.vue'
import DepositPopup from '@/components/deposit/DepositPopup.vue'

const route = useRoute()
const router = useRouter()

const showAuthModal = ref(false)
const authTab = ref('login')
const showDepositPopup = ref(false)

const openAuth = (tab = 'login') => {
  authTab.value = tab
  showAuthModal.value = true
}

onMounted(() => {
  const authParam = route.query.auth
  if (authParam === 'login' || authParam === 'register') {
    openAuth(authParam)
    router.replace({ query: {} })
  }
})

provide('openAuth', openAuth)
</script>

<style lang="scss" scoped>
.home-v5 {
  display: flex;
  flex-direction: column;
  height: 100%;
  background-color: #fff;
  background-image: url("/assets/img/bg_pattern_tile_0_95.png");
  background-size: 160px 160px;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'PingFang SC', 'Hiragino Sans GB', 'Microsoft YaHei', 'Helvetica Neue', Helvetica, Arial, sans-serif;
}

.main-scroll {
  flex: 1;
  overflow-y: auto;
  background: transparent;
}
</style>
