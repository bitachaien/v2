<template>
  <div class="recover-balance-page">
    <van-nav-bar title="找回余额" left-arrow @click-left="router.back()" />

    <div class="balance-card">
      <div class="balance-info">
        <span class="label">当前余额</span>
        <span class="amount">{{ formatMoney(currentBalance) }}</span>
        <van-icon name="replay" class="refresh-icon" @click="refreshBalance" :class="{ spinning: refreshing }" />
        <span v-if="refreshing && refreshProgress" class="refresh-progress">{{ refreshProgress }}</span>
      </div>
      <van-button type="primary" size="small" class="recover-btn" @click="recoverAll" :loading="recovering">
        一键找回
      </van-button>
      <div class="tip">只能找回余额的整数倍(即不含小数点)</div>
    </div>

    <div class="main-content">
      <aside class="platform-sidebar">
        <div 
          v-for="(cat, index) in categories" 
          :key="cat.code"
          class="platform-item"
          :class="{ active: activeCategory === index }"
          @click="activeCategory = index"
        >
          <div class="p-icon">
            <img :src="cat.iconImg" class="p-img" v-if="cat.iconImg" />
            <van-icon :name="cat.icon || cat.vanIcon || 'apps-o'" v-else style="font-size: 20px; color: #26A17B;" />
          </div>
          <span class="p-name">{{ cat.name }}</span>
        </div>
      </aside>

      <div class="platform-list">
        <div class="search-box">
          <van-field v-model="searchKey" placeholder="平台搜索" left-icon="search" clearable />
        </div>

        <div class="platform-grid" v-if="!loading">
          <div 
            v-for="plat in filteredPlatforms" 
            :key="plat.code"
            class="platform-card"
            @click="recoverSingle(plat)"
          >
            <img :src="plat.icon || getDefaultIcon(plat.code)" class="plat-icon" />
            <span class="plat-name">{{ plat.name || plat.code }}</span>
            <span class="plat-balance">{{ formatMoney(plat.balance) }}</span>
          </div>
        </div>

        <van-loading v-else class="loading" />

        <van-empty v-if="!loading && filteredPlatforms.length === 0" description="暂无平台" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { showToast, showSuccessToast } from 'vant'
import { gameApi } from '@/api/game'
import { lotteryWS } from '@/utils/websocket'

const router = useRouter()

const currentBalance = ref(0)
const platforms = ref([])
const categories = ref([
  { code: 'all', name: '全部', vanIcon: 'apps-o' }
])
const activeCategory = ref(0)
const searchKey = ref('')
const loading = ref(false)
const refreshing = ref(false)
const recovering = ref(false)

const filteredPlatforms = computed(() => {
  let list = platforms.value
  
  const cat = categories.value[activeCategory.value]
  if (cat && cat.code !== 'all') {
    list = list.filter(p => p.type === cat.code)
  }
  
  if (searchKey.value) {
    const key = searchKey.value.toLowerCase()
    list = list.filter(p => 
      p.code.toLowerCase().includes(key) || 
      (p.name && p.name.toLowerCase().includes(key))
    )
  }
  
  return list
})

const formatMoney = (val) => {
  const num = parseFloat(val) || 0
  return num.toFixed(2)
}

const getDefaultIcon = (code) => {
  return `/assets/img/platform/${(code || '').toLowerCase()}.png`
}

const categoryIconMap = {
  all: '/assets/img/icon_dtfl_dz_0.svg',
  slot: '/assets/img/icon_dtfl_dz_0.svg',
  live: '/assets/img/icon_dtfl_zr_0.svg',
  fish: '/assets/img/icon_dtfl_by_0.svg',
  fishing: '/assets/img/icon_dtfl_by_0.svg',
  chess: '/assets/img/icon_dtfl_qp_0.svg',
  lottery: '/assets/img/icon_dtfl_cp_0.svg',
  sport: '/assets/img/icon_dtfl_ty_0.svg',
  esport: '/assets/img/icon_dtfl_dj_0.svg',
  blockchain: '/assets/img/icon_dtfl_qkl_0.svg',
}

const loadCategories = async () => {
  try {
    const res = await gameApi.getCategories()
    if (res.code === 0 && res.data?.list) {
      categories.value = [
        { code: 'all', name: '全部', iconImg: categoryIconMap['all'] },
        ...res.data.list
          .filter(c => c.code !== 'hot')
          .map(c => ({
            code: c.code,
            name: c.name,
            iconImg: categoryIconMap[c.code] || '/assets/img/icon_dtfl_dz_0.svg'
          }))
      ]
    }
  } catch {
  }
}

const loadPlatformBalances = async () => {
  loading.value = true
  try {
    const res = await gameApi.getPlatformBalances()
    if (res.code === 0 && res.data) {
      platforms.value = res.data.platforms || []
      currentBalance.value = res.data.mainBalance || 0
    }
  } catch {
  } finally {
    loading.value = false
  }
}

const refreshBalance = () => {
  if (refreshing.value) return
  
  if (!lotteryWS.isConnected) {
    lotteryWS.connect().then(() => {
      doRefresh()
    }).catch(() => {
      showToast('连接服务器失败，请稍后重试')
    })
    return
  }
  
  doRefresh()
}

const doRefresh = () => {
  refreshing.value = true
  refreshProgress.value = ''
  showToast({ message: '正在同步余额...', icon: 'loading', duration: 0 })
  lotteryWS.send('refresh_platform_balances')
}

const refreshProgress = ref('')

let offPlatformBalance = null
let offPlatformBalanceComplete = null

const setupWsListener = () => {
  offPlatformBalance = lotteryWS.on('platform_balance', (data) => {
    const idx = platforms.value.findIndex(p => p.code === data.code)
    if (idx > -1) {
      platforms.value[idx].balance = data.balance
    }
    refreshProgress.value = data.progress || ''
  })
  
  offPlatformBalanceComplete = lotteryWS.on('platform_balance_complete', (data) => {
    currentBalance.value = data.mainBalance || currentBalance.value
    showToast('刷新完成')
    refreshing.value = false
    refreshProgress.value = ''
  })
}

const cleanupWsListener = () => {
  if (offPlatformBalance) offPlatformBalance()
  if (offPlatformBalanceComplete) offPlatformBalanceComplete()
}

const recoverAll = async () => {
  const totalBalance = platforms.value.reduce((sum, p) => sum + (parseFloat(p.balance) || 0), 0)
  if (totalBalance < 1) {
    showToast('没有可找回的余额')
    return
  }
  
  recovering.value = true
  try {
    const res = await gameApi.recoverAllBalance()
    if (res.code === 0) {
      showSuccessToast(`成功找回 ${formatMoney(res.data?.amount || totalBalance)} 元`)
      await loadPlatformBalances()
    } else {
      showToast(res.message || '找回失败')
    }
  } catch (e) {
    showToast(e.message || '网络错误')
  } finally {
    recovering.value = false
  }
}

const recoverSingle = async (plat) => {
  const balance = parseFloat(plat.balance) || 0
  if (balance < 1) {
    showToast('余额不足1元，无法找回')
    return
  }
  
  showToast({ message: `正在找回 ${plat.code}...`, icon: 'loading' })
  try {
    const res = await gameApi.recoverPlatformBalance({ platform: plat.code })
    if (res.code === 0) {
      showSuccessToast(`成功找回 ${formatMoney(res.data?.amount || balance)} 元`)
      await loadPlatformBalances()
    } else {
      showToast(res.message || '找回失败')
    }
  } catch (e) {
    showToast(e.message || '网络错误')
  }
}

onMounted(() => {
  loadCategories()
  loadPlatformBalances()
  setupWsListener()
})

onUnmounted(() => {
  cleanupWsListener()
})
</script>

<style lang="scss" scoped>
.recover-balance-page {
  height: 100vh;
  background: #f7f8fa;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.balance-card {
  margin: 12px;
  padding: 15px;
  background: #fff;
  border-radius: 12px;
  position: relative;
}

.balance-info {
  display: flex;
  align-items: center;
  gap: 8px;
  
  .label {
    color: #666;
    font-size: 14px;
  }
  
  .amount {
    color: #26A17B;
    font-size: 20px;
    font-weight: 700;
  }
}

.refresh-icon {
  color: #26A17B;
  font-size: 18px;
  cursor: pointer;
  
  &.spinning {
    animation: spin 1s linear infinite;
  }
}

.refresh-progress {
  font-size: 12px;
  color: #26A17B;
  margin-left: 4px;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.recover-btn {
  position: absolute;
  right: 15px;
  top: 50%;
  transform: translateY(-50%);
  background: #26A17B;
  border: none;
  border-radius: 999px;
  padding: 0 20px;
}

.tip {
  margin-top: 8px;
  font-size: 12px;
  color: #999;
}

.main-content {
  display: flex;
  margin: 0 12px 12px;
  background: #fff;
  border-radius: 12px;
  flex: 1;
  overflow: hidden;
}

.platform-sidebar {
  width: 96px;
  background: transparent;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  padding-top: 5px;
  padding-bottom: 10px;
  align-items: center;
}

.platform-item {
  width: 86px;
  height: 40.13px;
  margin: 4px 0;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
  background-image: url('/assets/img/btn_zc1_2.avif');
  background-size: 100% 100%;
  background-repeat: no-repeat;
  background-color: transparent;
  border: none;
  cursor: pointer;
  
  &.active {
    background-image: url('/assets/img/btn_zc1_1.avif');
    color: #fff;
    
    .p-name {
      color: #fff;
      font-weight: 700;
    }
  }
  
  .p-name {
    font-size: 12px;
    margin-left: 4px;
    color: #666;
  }
}

.p-icon {
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.p-img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.platform-list {
  flex: 1;
  padding: 12px;
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
}

.search-box {
  margin-bottom: 12px;
  
  :deep(.van-field) {
    background: #f5f5f5;
    border-radius: 999px;
  }
}

.platform-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}

.platform-card {
  background: #f5f5f5;
  border-radius: 8px;
  padding: 12px;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  cursor: pointer;
  
  &:active {
    background: #ebebeb;
  }
}

.plat-icon {
  width: 32px;
  height: 32px;
  margin-bottom: 8px;
  border-radius: 6px;
}

.plat-name {
  font-size: 14px;
  font-weight: 500;
  color: #333;
}

.plat-balance {
  font-size: 13px;
  color: #999;
  margin-top: 4px;
}

.loading {
  display: flex;
  justify-content: center;
  padding: 40px;
}
</style>
