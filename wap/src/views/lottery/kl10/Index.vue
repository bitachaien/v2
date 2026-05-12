<template>
  <div class="kl10-container">
    
    <van-nav-bar
      title="快乐十分"
      left-arrow
      fixed
      placeholder
      @click-left="$router.back()"
      class="nav-bar-glass"
    >
      <template #right>
        <van-icon name="ellipsis" size="18" />
      </template>
    </van-nav-bar>

    
    <kl10-lottery-info 
      :show-expect="mockExpect"
      :last-open-code="mockOpenCode"
      :gametimes="mockTime"
    />

    
    <div class="mode-switch-bar">
      <div class="switch-container">
        <div 
          class="mode-item" 
          :class="{ active: playMode === 'standard' }"
          @click="playMode = 'standard'"
        >
          标准玩法
        </div>
        <div 
          class="mode-item" 
          :class="{ active: playMode === 'fun' }"
          @click="playMode = 'fun'"
        >
          趣味玩法
        </div>
        <div class="slider" :class="{ right: playMode === 'fun' }"></div>
      </div>
    </div>

    
    <div class="game-content">
      <component 
        :is="currentPanelComponent"
        ref="panelRef"
        @update:betCount="onBetCountChange"
        @update:selectedData="onSelectionChange"
      />
    </div>

    
    <betting-footer
      v-model:mode="betMode"
      v-model:multiple="multiple"
      :bet-count="currentBetCount"
      :total-money="totalMoney"
      :basket-count="basketItems.length"
      @clear="clearPanel"
      @add-to-cart="addToBasket"
      @open-hemai="openHemai"
      @submit="handleMainBet"
      @open-basket="showBasket = true"
    />

    
    <bet-basket
      v-model:show="showBasket"
      :items="basketItems"
      @clear="clearBasket"
      @delete="deleteBasketItem"
      @random="handleRandomBet"
      @submit="submitOrder"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { showToast, showLoadingToast, closeToast } from 'vant'
import Kl10LotteryInfo from './components/Kl10LotteryInfo.vue'
import PanelStandard from './components/PanelStandard.vue'
import PanelFun from './components/PanelFun.vue'
import BettingFooter from './components/BettingFooter.vue'
import BetBasket from './components/BetBasket.vue'

const playMode = ref('standard') // 'standard' | 'fun'
const showBasket = ref(false)
const basketItems = ref([])

const panelRef = ref(null)
const currentBetCount = ref(0)
const currentSelection = ref(null)

const multiple = ref(1)
const betMode = ref(1) // 1=元, 0.1=角, 0.01=分

const mockExpect = ref({ currFullExpect: '2023001', lastFullExpect: '2023000' })

const mockOpenCode = ref(['01', '05', '08', '12', '15', '18', '19', '20'])
const mockTime = ref({ ms: 300000 })
let timer = null

const currentPanelComponent = computed(() => {
  return playMode.value === 'standard' ? PanelStandard : PanelFun
})

const totalMoney = computed(() => {
  return (currentBetCount.value * 2 * multiple.value * betMode.value).toFixed(2)
})

onMounted(() => {
  window.scrollTo(0, 0)
  startTimer()
})

onUnmounted(() => {
  if (timer) clearInterval(timer)
})

function startTimer() {
  timer = setInterval(() => {
    if (mockTime.value.ms > 0) {
      mockTime.value.ms -= 1000
    } else {
      mockTime.value.ms = 300000 // Reset for demo
    }
  }, 1000)
}

function onBetCountChange(count) {
  currentBetCount.value = count
}

function onSelectionChange(data) {
  currentSelection.value = data
}

function clearPanel() {
  if (panelRef.value && panelRef.value.clear) {
    panelRef.value.clear()
  }
  currentBetCount.value = 0
  currentSelection.value = null
}

function openHemai() {
  showToast('合买功能开发中')
}

function addToBasket() {
  if (currentBetCount.value === 0 || !currentSelection.value) {
    if (basketItems.value.length > 0) {
       showBasket.value = true
       return
    }
    showToast('请先选择号码')
    return
  }
  
  const betItem = {
    ...currentSelection.value,
    betCount: currentBetCount.value,
    multiple: multiple.value,
    mode: betMode.value,
    money: totalMoney.value, 
    isFun: playMode.value === 'fun',
    timestamp: Date.now()
  }
  
  basketItems.value.unshift(betItem)
  
  showToast('已加入号码篮')
  clearPanel()
}

function handleMainBet() {
  if (currentBetCount.value > 0) {
    addToBasket()
    showBasket.value = true
  } else {
    if (basketItems.value.length === 0) {
       showToast('请先选择号码')
       return
    }
    showBasket.value = true
  }
}

function clearBasket() {
  basketItems.value = []
}

function deleteBasketItem(index) {
  basketItems.value.splice(index, 1)
}

function handleRandomBet(count) {

  if (playMode.value === 'standard' && panelRef.value && panelRef.value.randomSelect) {
    try {
      const newBets = panelRef.value.randomSelect(count)
      if (newBets && newBets.length > 0) {
        basketItems.value.unshift(...newBets)
        showToast(`机选 ${count} 注成功`)
        return
      }
    } catch (e) {
      console.error('Random select error:', e)
    }
  }

  const newBets = generateRandomBets(count)
  basketItems.value.unshift(...newBets)
  showToast(`机选 ${count} 注成功`)
}

function generateRandomBets(count) {
  const results = []

  const playId = 'r5'
  const playName = '任选五'
  const min = 5
  
  for (let c = 0; c < count; c++) {
    const pool = Array.from({ length: 20 }, (_, i) => i + 1)
    const nums = []
    for (let i = 0; i < min; i++) {
      const idx = Math.floor(Math.random() * pool.length)
      nums.push(pool[idx])
      pool.splice(idx, 1)
    }
    nums.sort((a, b) => a - b)
    
    results.push({
      playId,
      playName,
      numbers: nums,
      tzcode: nums.join(','),
      betCount: 1,
      money: '2.00',
      isFun: false
    })
  }
  return results
}

function submitOrder() {
  if (basketItems.value.length === 0) return
  
  const loading = showLoadingToast({
    message: '投注中...',
    forbidClick: true,
  })
  

  setTimeout(() => {
    loading.close()
    showToast({
      type: 'success',
      message: '投注成功',
      onClose: () => {
        basketItems.value = []
        showBasket.value = false
      }
    })
  }, 1000)
}

</script>

<style lang="less" scoped>
.kl10-container {
  min-height: 100vh;
  background: #f7f8fa;
  padding-bottom: 100px; // Footer height

}

:deep(.van-nav-bar) {
  z-index: 1000 !important;
}

.nav-bar-glass {
  position: relative;
  z-index: 1000 !important;
  
  :deep(.van-nav-bar__content) {
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(10px);
  }
  :deep(.van-nav-bar__title) {
    font-weight: 600;
  }
}

.mode-switch-bar {
  padding: 12px 16px;
  background: #fff;
  position: sticky;
  top: 46px;
  z-index: 5;
  
  .switch-container {
    position: relative;
    display: flex;
    background: #f5f5f5;
    border-radius: 20px;
    padding: 4px;
    height: 40px;
    box-sizing: border-box;
    
    .mode-item {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 14px;
      font-weight: 500;
      color: #666;
      z-index: 2;
      transition: color 0.3s;
      cursor: pointer;
      
      &.active {
        color: #333;
        font-weight: 600;
      }
    }
    
    .slider {
      position: absolute;
      top: 4px;
      left: 4px;
      width: calc(50% - 4px);
      height: calc(100% - 8px);
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      z-index: 1;
      
      &.right {
        transform: translateX(100%);
      }
    }
  }
}

.game-content {
  min-height: 300px;
}
</style>
