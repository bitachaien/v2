<template>
  <div class="hn7xc-page">
    <LotteryHeader 
      :title="cpTitle" 
      :subtitle="currentPlayName"
      @toggle-menu="showPlayMenu = true"
      @click-right="showGameList = true"
    />
    
    <div class="page-content">
      <div class="fixed-info">
        <LotteryInfo 
          :showExpect="issueInfo" 
          :lastOpenCode="lastOpenCode"
          :gametimes="{ ms: remainMs }"
          :is-drawing="isDrawing"
          @show-history="showHistory = true"
        />
      </div>

      <div class="scroll-area">
        <div class="play-panel">
          <div class="play-tips" v-if="currentPlayTip">
            <van-icon name="bulb-o" class="tip-icon" />
            <span class="tip-text">{{ currentPlayTip }}</span>
          </div>

          <div class="panel-content">
            <BallSelector
              :layout="currentLayout"
              :selected="selectedNumbers"
              @update:selected="handleNumberSelect"
            />
          </div>
        </div>
      </div>
    </div>

    <BettingBottom 
      :bet-count="currentBetCount"
      :balance="userBalance"
      :cart-count="cartList.length"
      v-model:mode="betMode"
      v-model:multiplier="betMultiplier"
      @submit="handleDirectBet"
      @add-to-cart="addToCart"
      @view-cart="showCart = true"
    />

    
    <van-popup v-model:show="showPlayMenu" position="left" :style="{ width: '80%', height: '100%' }">
      <PlaySelector 
        v-model:currentMainPlay="currentMainPlay"
        v-model:currentSubPlay="currentSubPlay"
        @change="handlePlayChange"
        @close="showPlayMenu = false"
      />
    </van-popup>

    <van-popup v-model:show="showCart" position="bottom" round :style="{ height: '70%' }">
      <NumberBasket 
        :list="cartList" 
        @close="showCart = false"
        @delete="removeFromCart"
        @clear="clearCart"
        @submit="submitCart"
      />
    </van-popup>
    
    <van-popup v-model:show="showHistory" position="bottom" round :style="{ height: '70%' }">
      <div class="history-placeholder" style="padding: 20px; text-align: center;">
        历史开奖记录
      </div>
    </van-popup>
  </div>
</template>

<script setup>
import { ref, computed, reactive, onMounted, onUnmounted } from 'vue'
import { Popup as VanPopup, Icon as VanIcon, Toast } from 'vant'
import LotteryHeader from './components/LotteryHeader.vue'
import LotteryInfo from './components/LotteryInfo.vue'
import BettingBottom from './components/BettingBottom.vue'
import PlaySelector from './components/PlaySelector.vue'
import BallSelector from './components/BallSelector.vue'
import NumberBasket from './components/NumberBasket.vue'

const cpTitle = ref('海南七星彩')
const userBalance = ref('10000.00')

const showPlayMenu = ref(false)
const showCart = ref(false)
const showHistory = ref(false)
const showGameList = ref(false)

const currentMainPlay = ref('hn7xc_4x')
const currentSubPlay = ref('qxc4zxfs')
const currentPlayName = ref('直选复式')

const selectedNumbers = ref({}) // { 0: [1,2], 1: [3] }
const betMultiplier = ref(1)
const betMode = ref(1) // 1, 0.1, 0.01
const cartList = ref([])

const issueInfo = reactive({ currFullExpect: '2023001', lastFullExpect: '2023000' })
const lastOpenCode = ref(['1', '2', '3', '6', '5', '8', '9'])
const remainMs = ref(60000)
const isDrawing = ref(false)

const currentLayout = computed(() => {
  const code = currentSubPlay.value
  const standardOptions = Array.from({ length: 10 }, (_, i) => ({ label: String(i), value: i }))
  
  if (code === 'qxc4zxfs') { // 4 Star Direct
    return [
      { title: '千位', options: standardOptions },
      { title: '百位', options: standardOptions },
      { title: '十位', options: standardOptions },
      { title: '个位', options: standardOptions }
    ]
  } else if (code === 'qxc3q3') { // Front 3 Direct
    return [
      { title: '千位', options: standardOptions },
      { title: '百位', options: standardOptions },
      { title: '十位', options: standardOptions }
    ]
  } else if (code === 'qxc3h3') { // Back 3 Direct
    return [
      { title: '百位', options: standardOptions },
      { title: '十位', options: standardOptions },
      { title: '个位', options: standardOptions }
    ]
  } else if (code === 'qxc2q2') { // Front 2 Direct
    return [
      { title: '千位', options: standardOptions },
      { title: '百位', options: standardOptions }
    ]
  } else if (code === 'qxc2h2') { // Back 2 Direct
    return [
      { title: '十位', options: standardOptions },
      { title: '个位', options: standardOptions }
    ]
  } else if (code === 'qxcdwd') { // Positioning (1 Star)
    return [
      { title: '千位', options: standardOptions },
      { title: '百位', options: standardOptions },
      { title: '十位', options: standardOptions },
      { title: '个位', options: standardOptions }
    ]
  } else if (code === 'qxc2mbdw') { // 2 codes non-positioning
    return [{ title: '二码不定位', options: standardOptions }]
  } else if (code === 'qxc3mbdw') { // 3 codes non-positioning
    return [{ title: '三码不定位', options: standardOptions }]
  }
  

  return [{ title: '号码', options: standardOptions }]
})

const currentPlayTip = computed(() => {
  const tips = {
    'qxc4zxfs': '从千位、百位、十位、个位中至少选择1个号码',
    'qxc3q3': '从千位、百位、十位中至少选择1个号码',
    'qxc3h3': '从百位、十位、个位中至少选择1个号码',
    'qxc2q2': '从千位、百位中至少选择1个号码',
    'qxc2h2': '从十位、个位中至少选择1个号码',
    'qxcdwd': '在任意位置选择1个号码',
    'qxc2mbdw': '从0-9中至少选择2个号码',
    'qxc3mbdw': '从0-9中至少选择3个号码'
  }
  return tips[currentSubPlay.value] || '请选择号码'
})

const handleNumberSelect = (val) => {
  selectedNumbers.value = val
}

const handlePlayChange = (play) => {
  currentPlayName.value = `${play.name}`
  selectedNumbers.value = {}
}

const combinations = (n, k) => {
  if (k > n) return 0
  if (k === n) return 1
  if (k === 1) return n
  let res = 1
  for (let i = 1; i <= k; i++) {
    res = res * (n - i + 1) / i
  }
  return res
}

const currentBetCount = computed(() => {
  const sel = selectedNumbers.value
  const code = currentSubPlay.value
  
  const getCount = (row) => (sel[row] || []).length
  
  if (code === 'qxc4zxfs') {
    return getCount(0) * getCount(1) * getCount(2) * getCount(3)
  } else if (code === 'qxc3q3' || code === 'qxc3h3') {
    return getCount(0) * getCount(1) * getCount(2)
  } else if (code === 'qxc2q2' || code === 'qxc2h2') {
    return getCount(0) * getCount(1)
  } else if (code === 'qxcdwd') {

    let sum = 0
    for(let i=0; i<4; i++) sum += getCount(i)
    return sum
  } else if (code === 'qxc2mbdw') {
    const n = getCount(0)
    return combinations(n, 2)
  } else if (code === 'qxc3mbdw') {
    const n = getCount(0)
    return combinations(n, 3)
  }
  
  return 0
})

const formatNumbers = () => {
  const parts = []
  const layout = currentLayout.value
  
  for (let i = 0; i < layout.length; i++) {
    const rowNums = selectedNumbers.value[i] || []
    if (rowNums.length > 0) {
      parts.push(rowNums.join(','))
    } else {
      parts.push('-')
    }
  }
  return parts.join('|')
}

const addToCart = () => {
  if (currentBetCount.value === 0) {
    Toast('请至少选择一注')
    return
  }
  
  cartList.value.push({
    playName: currentPlayName.value,
    numbers: formatNumbers(),
    notes: currentBetCount.value,
    amount: (currentBetCount.value * betMode.value * betMultiplier.value).toFixed(2),
    multiplier: betMultiplier.value,
    mode: betMode.value
  })
  

  selectedNumbers.value = {}
  Toast.success('已加入号码篮')
}

const removeFromCart = (index) => {
  cartList.value.splice(index, 1)
}

const clearCart = () => {
  cartList.value = []
}

const handleDirectBet = () => {
  if (currentBetCount.value === 0) {
    Toast('请至少选择一注')
    return
  }
  
  Toast.loading('正在投注...')
  setTimeout(() => {
    Toast.success('投注成功')
    selectedNumbers.value = {}
  }, 500)
}

const submitCart = () => {
  if (cartList.value.length === 0) return
  
  Toast.loading('正在投注...')
  setTimeout(() => {
    Toast.success('投注成功')
    cartList.value = []
    showCart.value = false
  }, 500)
}

let timer = null
onMounted(() => {
  timer = setInterval(() => {
    if (remainMs.value > 0) remainMs.value -= 1000
  }, 1000)
})

onUnmounted(() => {
  if (timer) clearInterval(timer)
})
</script>

<style lang="less" scoped>
.hn7xc-page {
  background: #f2f3f5;
  min-height: 100vh;
  padding-top: 46px;
  padding-bottom: 140px;
}

.fixed-info {
  position: sticky;
  top: 46px;
  z-index: 90;
}

.scroll-area {
  padding: 16px 12px;
}

.play-panel {
  background: #fff;
  border-radius: 16px;
  overflow: hidden;
  min-height: 300px;
  padding: 16px 12px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.03);
}

.play-tips {
  padding: 12px;
  background: linear-gradient(90deg, #fff1f0 0%, #fff 100%);
  color: #e1251b;
  font-size: 13px;
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 20px;
  border-radius: 8px;
  border-left: 4px solid #e1251b;
  
  .tip-icon {
    font-size: 16px;
  }
  
  .tip-text {
    flex: 1;
  }
}

:deep(.van-popup) {
  max-width: 100%;
}
</style>
