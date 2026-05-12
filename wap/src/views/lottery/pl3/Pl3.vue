<template>
  <div class="pl3-page">
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
        <div class="play-panel slide-up">
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
import { ref, computed, reactive, onMounted, onUnmounted, watch } from 'vue'
import { Popup as VanPopup, Icon as VanIcon, Toast } from 'vant'
import LotteryHeader from './components/LotteryHeader.vue'
import LotteryInfo from './components/LotteryInfo.vue'
import BettingBottom from './components/BettingBottom.vue'
import PlaySelector from './components/PlaySelector.vue'
import BallSelector from './components/BallSelector.vue'
import NumberBasket from './components/NumberBasket.vue'
import { pl3Api } from '@/api'

const cpTitle = ref('排列三')
const userBalance = ref('0.00')
const lotteryCode = ref('pl3') // 彩种代码

const showPlayMenu = ref(false)
const showCart = ref(false)
const showHistory = ref(false)
const showGameList = ref(false)
const isLoading = ref(false)

const currentMainPlay = ref('pl3_3x')
const currentSubPlay = ref('pl3zxfs')
const currentPlayName = ref('三星直选复式')

const selectedNumbers = ref({}) // { 0: [1,2], 1: [3] }
const betMultiplier = ref(1)
const betMode = ref(1) // 1, 0.1, 0.01
const cartList = ref([])

const issueInfo = reactive({ currFullExpect: '', lastFullExpect: '' })
const lastOpenCode = ref([]) // PL3 usually has 3 numbers
const remainMs = ref(0)
const isDrawing = ref(false)
const closeSeconds = ref(10) // 封盘时间

const currentLayout = computed(() => {

  const code = currentSubPlay.value
  
  const standardOptions = Array.from({ length: 10 }, (_, i) => ({ label: String(i), value: i }))
  const dsdsOptions = [
    { label: '大', value: '大' },
    { label: '小', value: '小' },
    { label: '单', value: '单' },
    { label: '双', value: '双' }
  ]

  if (code === 'pl3zxfs') { // 3 Star Direct
    return [
      { title: '百位', options: standardOptions },
      { title: '十位', options: standardOptions },
      { title: '个位', options: standardOptions }
    ]
  } else if (code === 'pl3qx2fs') { // Front 2 Direct
    return [
      { title: '百位', options: standardOptions },
      { title: '十位', options: standardOptions }
    ]
  } else if (code === 'pl3hx2fs') { // Back 2 Direct
    return [
      { title: '十位', options: standardOptions },
      { title: '个位', options: standardOptions }
    ]
  } else if (code === 'pl3dwdfs') { // 1 Star
    return [
      { title: '百位', options: standardOptions },
      { title: '十位', options: standardOptions },
      { title: '个位', options: standardOptions }
    ]
  } else if (code === 'dxdsq2') {
    return [
      { title: '百位', options: dsdsOptions },
      { title: '十位', options: dsdsOptions }
    ]
  } else if (code === 'dxdsh2') {
    return [
      { title: '十位', options: dsdsOptions },
      { title: '个位', options: dsdsOptions }
    ]
  }
  

  return [{ title: '号码', options: standardOptions }]
})

const currentPlayTip = computed(() => {
  const tips = {
    'pl3zxfs': '从百位、十位、个位中至少选择1个号码',
    'pl3qx2fs': '从百位、十位中至少选择1个号码',
    'pl3hx2fs': '从十位、个位中至少选择1个号码',
    'dxdsq2': '选择百位、十位的大小单双',
    'dxdsh2': '选择十位、个位的大小单双'
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

const currentBetCount = computed(() => {
  const sel = selectedNumbers.value
  const code = currentSubPlay.value
  
  const getCount = (row) => (sel[row] || []).length
  
  if (code === 'pl3zxfs') {
    return getCount(0) * getCount(1) * getCount(2)
  } else if (code === 'pl3qx2fs') {
    return getCount(0) * getCount(1)
  } else if (code === 'pl3hx2fs') {
    return getCount(0) * getCount(1)
  } else if (code === 'dxdsq2') {
    return getCount(0) * getCount(1)
  } else if (code === 'dxdsh2') {
    return getCount(0) * getCount(1)
  } else if (code === 'pl3dwdfs') {

    return getCount(0) + getCount(1) + getCount(2)
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

const fetchIssueInfo = async () => {
  try {
    const res = await pl3Api.getInfo(lotteryCode.value)
    if (res.code === 0 && res.data) {
      issueInfo.currFullExpect = res.data.currFullExpect
      issueInfo.lastFullExpect = res.data.lastFullExpect
      remainMs.value = res.data.remainTime * 1000 // 转换为毫秒
      closeSeconds.value = res.data.closeSeconds || 10
      

      if (remainMs.value <= closeSeconds.value * 1000) {
        isDrawing.value = true
      } else {
        isDrawing.value = false
      }
    }
  } catch (e) {
    console.error('[PL3] 获取期号信息失败:', e)
  }
}

const fetchLastResult = async () => {
  try {
    const res = await pl3Api.getLastResult(lotteryCode.value)
    if (res.code === 0 && res.data) {
      const codes = res.data.opencode
      if (typeof codes === 'string') {
        lastOpenCode.value = codes.split(',')
      } else if (Array.isArray(codes)) {
        lastOpenCode.value = codes
      }
    }
  } catch (e) {
    console.error('[PL3] 获取开奖结果失败:', e)
  }
}

const fetchUserBalance = async () => {
  try {
    const res = await pl3Api.getUserBalance()
    if (res.code === 0 && res.data) {
      userBalance.value = String(res.data.balance || res.data.amount || '0.00')
    }
  } catch (e) {
    console.error('[PL3] 获取余额失败:', e)
  }
}

const buildTzcode = () => {
  const layout = currentLayout.value
  const parts = []
  
  for (let i = 0; i < layout.length; i++) {
    const rowNums = selectedNumbers.value[i] || []
    if (rowNums.length > 0) {
      parts.push(rowNums.join(''))
    } else {
      parts.push('-')
    }
  }
  return parts.join('|')
}

const handleDirectBet = async () => {
  if (currentBetCount.value === 0) {
    Toast('请至少选择一注')
    return
  }
  
  if (isDrawing.value) {
    Toast('当前期已封盘，请等待下期')
    return
  }
  
  const amount = currentBetCount.value * betMode.value * betMultiplier.value * 2 // 排列三基础单价2元
  
  isLoading.value = true
  Toast.loading({ message: '正在投注...', forbidClick: true })
  
  try {
    const res = await pl3Api.submitBet({
      lotteryCode: lotteryCode.value,
      expect: issueInfo.currFullExpect,
      playId: currentSubPlay.value,
      tzcode: buildTzcode(),
      amount: amount,
      multiplier: betMultiplier.value,
      mode: betMode.value,
      betCount: currentBetCount.value
    })
    
    if (res.code === 0) {
      Toast.success('投注成功')
      selectedNumbers.value = {}

      fetchUserBalance()
    } else {
      Toast.fail(res.msg || res.message || '投注失败')
    }
  } catch (e) {
    console.error('[PL3] 投注失败:', e)
    Toast.fail(e.message || '投注失败')
  } finally {
    isLoading.value = false
  }
}

const submitCart = async () => {
  if (cartList.value.length === 0) return
  
  if (isDrawing.value) {
    Toast('当前期已封盘，请等待下期')
    return
  }
  
  isLoading.value = true
  Toast.loading({ message: '正在投注...', forbidClick: true })
  
  let successCount = 0
  let failCount = 0
  
  try {
    for (const item of cartList.value) {
      const amount = item.notes * item.mode * item.multiplier * 2
      
      const res = await pl3Api.submitBet({
        lotteryCode: lotteryCode.value,
        expect: issueInfo.currFullExpect,
        playId: currentSubPlay.value,
        tzcode: item.numbers.replace(/,/g, ''),
        amount: amount,
        multiplier: item.multiplier,
        mode: item.mode,
        betCount: item.notes
      })
      
      if (res.code === 0) {
        successCount++
      } else {
        failCount++
      }
    }
    
    if (failCount === 0) {
      Toast.success(`投注成功，共${successCount}笔`)
      cartList.value = []
      showCart.value = false
      fetchUserBalance()
    } else if (successCount === 0) {
      Toast.fail('投注失败')
    } else {
      Toast(`部分成功：${successCount}成功，${failCount}失败`)
      fetchUserBalance()
    }
  } catch (e) {
    console.error('[PL3] 号码篮投注失败:', e)
    Toast.fail(e.message || '投注失败')
  } finally {
    isLoading.value = false
  }
}

let timer = null
let refreshTimer = null

watch(remainMs, (val) => {
  if (val <= closeSeconds.value * 1000 && val > 0) {
    isDrawing.value = true
  } else if (val <= 0) {

    isDrawing.value = true
    setTimeout(() => {
      fetchIssueInfo()
      fetchLastResult()
    }, 3000) // 3秒后刷新
  }
})

onMounted(async () => {

  await Promise.all([
    fetchIssueInfo(),
    fetchLastResult(),
    fetchUserBalance()
  ])
  

  timer = setInterval(() => {
    if (remainMs.value > 0) {
      remainMs.value -= 1000
    }
  }, 1000)
  

  refreshTimer = setInterval(() => {
    fetchIssueInfo()
  }, 30000)
})

onUnmounted(() => {
  if (timer) clearInterval(timer)
  if (refreshTimer) clearInterval(refreshTimer)
})
</script>

<style lang="less" scoped>
.pl3-page {
  background: #f0f2f5;
  min-height: 100vh;
  padding-top: 46px;
  padding-bottom: 140px;
  

  background-image: linear-gradient(to bottom, #ffffff 0%, #f0f2f5 300px);
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
  box-shadow: 0 4px 16px rgba(0,0,0,0.04);
  transition: all 0.3s ease;
}

.play-tips {
  padding: 12px 16px;
  background: linear-gradient(90deg, #e6f7ff 0%, #fff 100%);
  color: #1890ff; // Changed to blueish for PL3 distinction
  font-size: 13px;
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 20px;
  border-radius: 8px;
  border-left: 4px solid #1890ff;
  box-shadow: 0 2px 8px rgba(24, 144, 255, 0.05);
  
  .tip-icon {
    font-size: 16px;
  }
  
  .tip-text {
    flex: 1;
    font-weight: 500;
  }
}

:deep(.van-popup) {
  max-width: 100%;
}

.slide-up {
  animation: slideUp 0.4s ease-out;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
