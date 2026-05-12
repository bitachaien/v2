<template>
  <div class="x5-page">
    
    <Header :cptitle="lotteryInfo.cptitle" />

    
    <div class="scroll-content">
      <X5LotteryInfo
        :showExpect="showExpect"
        :lastOpenCode="openNumbers"
        :gametimes="{ ms: remainMs }"
        :is-drawing="isDrawing"
        @show-history="showHistory = true"
      />

      
      <div class="page-content">
        
        <PanelStandard
          ref="panelRef"
          @update:betCount="onBetCount"
          @update:selectedData="onSelectedData"
          @clear="onClear"
        />
      </div>
      
      <div class="footer-placeholder"></div>
    </div>

    
    <BettingFooter
      :betCount="betCount"
      :totalMoney="totalMoney"
      v-model:multiple="multiple"
      @clear="onClear"
      @add-to-cart="addToCart"
      @open-hemai="openHemai"
      @submit="quickBet"
    />

    
    
    <van-popup v-model:show="showHistory" position="bottom" round :style="{ height: '60%' }">
      <div class="history-popup">
        <div class="history-title">最近开奖</div>
        <van-empty v-if="historyList.length === 0" description="暂无数据" />
        <div v-else class="history-list">
          <div class="history-item" v-for="(row, idx) in historyList" :key="idx">
            <div class="history-row-top">
              <div class="expect-info">
                <div class="expect">第 {{ row.expect }} 期</div>
                <div class="opentime">{{ row.opentime }}</div>
              </div>
              <div class="nums">
                <span v-for="(n, i) in row.codes" :key="i" class="ball">{{ n }}</span>
              </div>
            </div>
            <div class="history-row-stats">
              <span class="stat-tag sum">和:{{ row.sum }}</span>
              <span class="stat-tag">{{ row.bigSmall }}</span>
              <span class="stat-tag">{{ row.oddEven }}</span>
              <span class="stat-tag">{{ row.dragon }}</span>
            </div>
          </div>
        </div>
      </div>
    </van-popup>

    
    <van-popup v-model:show="showCart" position="bottom" round :style="{ height: '70%' }">
      <div class="cart-container">
        <div class="cart-header">购彩篮</div>
        <van-empty v-if="cartItems.length === 0" description="购彩篮是空的" />
        <div v-else class="cart-list">
          <van-swipe-cell v-for="(item, index) in cartItems" :key="index">
            <van-cell :title="item.playName" :label="`${item.betCount}注 x ${item.multiple}倍 = ${item.money.toFixed(2)}元`" />
            <template #right>
              <van-button square type="danger" text="删除" style="height: 100%" @click="cartItems.splice(index, 1)" />
            </template>
          </van-swipe-cell>
        </div>
        <div v-if="cartItems.length > 0" class="cart-footer">
          <div class="cart-total">总计: <span class="total-money">{{ cartTotal.toFixed(2) }}</span> 元</div>
          <van-button type="primary" size="large" block @click="submitCart">提交订单 ({{ cartItems.length }})</van-button>
        </div>
      </div>
    </van-popup>
    
    
    <HemaiDialog
      v-model:show="showHemai"
      :totalMoney="Number(totalMoney)"
      @submit="onHemaiSubmit"
    />

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, reactive, provide, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useIntervalFn } from '@vueuse/core'
import { Popup as VanPopup, Empty as VanEmpty, SwipeCell as VanSwipeCell, Cell as VanCell, Button as VanButton, showToast, showSuccessToast, showFailToast, showConfirmDialog } from 'vant'
import { x5Api } from '@/api'
import { lotteryWS } from '@/utils/websocket'

import Header from '../k3/components/Header.vue'
import X5LotteryInfo from './components/X5LotteryInfo.vue'
import PanelStandard from './components/PanelStandard.vue'
import BettingFooter from './components/BettingFooter.vue'
import HemaiDialog from './components/HemaiDialog.vue'

const route = useRoute()
const router = useRouter()
const lotteryCode = computed(() => route.params.code || 'gd11x5')

const X5_TITLES = {
  'gd11x5': '广东11选5',
  'yf11x5': '一分11选5',
  'jx11x5': '江西11选5',
  'sd11x5': '山东11选5',
  'sh11x5': '上海11选5',
  'ah11x5': '安徽11选5',
  'cq11x5': '重庆11选5'
}

const lotteryInfo = reactive({
  cptitle: '11选5'
})

let currentSubscription = null

provide('lotteryCode', lotteryCode)

const showExpect = reactive({
  currFullExpect: '---',
  lastFullExpect: '---'
})
const openNumbers = ref(['-', '-', '-', '-', '-'])
const countdown = ref(0)
const isDrawing = ref(false)
const nextOpenTime = ref(null)

const remainMs = computed(() => countdown.value * 1000)

const betCount = ref(0)
const selectedData = ref(null)
const betMode = ref(1) // 1, 0.1, 0.01
const multiple = ref(1)
const panelRef = ref(null)

const showHistory = ref(false)
const historyList = ref([])

const showCart = ref(false)
const cartItems = ref([])
const showHemai = ref(false)

const totalMoney = computed(() => {
  return (betCount.value * parseFloat(betMode.value) * parseInt(multiple.value) * 2).toFixed(3)
})
const cartTotal = computed(() => cartItems.value.reduce((s, i) => s + parseFloat(i.money), 0))

function onBetCount(n) { betCount.value = n }
function onSelectedData(d) { selectedData.value = d }
function onClear() {
  if (panelRef.value?.clear) panelRef.value.clear()
  betCount.value = 0
  selectedData.value = null
}

function addToCart() {
  if (betCount.value === 0) {
    if (cartItems.value.length > 0) {
        showCart.value = true
        return
    }
    return window.alert('请先选择号码')
  }
  cartItems.value.push({
    playName: '标准玩法',
    data: selectedData.value,
    betCount: betCount.value,
    multiple: multiple.value,
    money: parseFloat(totalMoney.value)
  })
  onClear()
  showCart.value = true
}

function openHemai() {
  if (betCount.value === 0) return window.alert('请先选择号码')
  showHemai.value = true
}

async function quickBet() {
  if (betCount.value === 0) {
    showToast('请先选择号码')
    return
  }
  
  try {
    await showConfirmDialog({
      title: '投注确认',
      message: `注数: ${betCount.value}\n金额: ${totalMoney.value}元`
    })
    

    const betData = selectedData.value
    if (!betData || !betData.playId) {
      showFailToast('投注数据无效')
      return
    }
    
    const res = await x5Api.submitBet({
      lotteryCode: lotteryCode.value,
      expect: showExpect.currFullExpect,  // 添加期号
      playId: betData.playId,
      tzcode: betData.tzcode || betData.numbers?.join(',') || '',
      amount: parseFloat(totalMoney.value),
      multiplier: multiple.value,
      mode: betMode.value,
      betCount: betCount.value
    })
    
    if (res.code === 0) {
      showSuccessToast('投注成功')
      onClear()
    } else {
      showFailToast(res.msg || '投注失败')
    }
  } catch (e) {
    if (e !== 'cancel') {
      console.error('投注失败:', e)
      showFailToast(e.message || '投注失败')
    }
  }
}

function submitCart() {
  window.alert(`提交成功，共 ${cartItems.value.length} 项`)
  cartItems.value = []
  showCart.value = false
}

function onHemaiSubmit(data) {

    window.alert('合买发起成功')
    showHemai.value = false
    onClear()
}

const fetchLotteryInfo = () => {
  lotteryInfo.cptitle = X5_TITLES[lotteryCode.value] || '11选5'
}

const fetchExpectInfo = async () => {
  try {
    const res = await x5Api.getInfo(lotteryCode.value)
    if (res.code === 0 && res.data) {
      showExpect.currFullExpect = res.data.currFullExpect || res.data.expect || '---'
      if (res.data.lastFullExpect) {
        showExpect.lastFullExpect = res.data.lastFullExpect
      }

      if (res.data.remainTime) {
        countdown.value = res.data.remainTime
        nextOpenTime.value = Date.now() + res.data.remainTime * 1000
      }
    }
  } catch (e) {
    console.error('获取期号信息失败:', e)
  }
}

const fetchLastResult = async () => {
  try {
    const res = await x5Api.getLastResult(lotteryCode.value)
    if (res.code === 0 && res.data) {
      showExpect.lastFullExpect = res.data.expect || showExpect.lastFullExpect
      
      let opencode = res.data.opencode || ''
      if (opencode) {
        openNumbers.value = opencode.split(',')
      }
    }
  } catch (e) {
    console.error('获取上期开奖失败:', e)
  }
}

const fetchHistory = async () => {
  try {
    const res = await x5Api.getHistory(lotteryCode.value, { pageSize: 20 })
    if (res.code === 0 && res.data) {
      const list = res.data.list || []
      historyList.value = list.map(it => {
        const codes = (it.opencode || '01,02,03,04,05').split(',')
        const nums = codes.map(n => parseInt(n))
        const sum = nums.reduce((a, b) => a + b, 0)

        let opentime = it.opentime || ''
        if (opentime && opentime.includes(' ')) {
          opentime = opentime.split(' ')[1] || opentime
        }
        return {
          expect: it.expect || '',
          codes,
          opentime,
          sum,
          bigSmall: sum > 30 ? '大' : sum < 30 ? '小' : '和',
          oddEven: sum % 2 === 0 ? '双' : '单',
          dragon: nums[0] > nums[4] ? '龙' : nums[0] < nums[4] ? '虎' : '和'
        }
      })
    }
  } catch (e) {
    console.error('获取历史记录失败:', e)
  }
}

const updateCountdown = () => {
  if (!nextOpenTime.value) {
    countdown.value = 0
    return
  }
  const now = Date.now()
  const diff = Math.max(0, Math.floor((nextOpenTime.value - now) / 1000))
  countdown.value = diff
  
  if (diff <= 0 && !isDrawing.value) {
    isDrawing.value = true

    setTimeout(() => {
      fetchLastResult()
      fetchExpectInfo()
      fetchHistory()
      isDrawing.value = false
    }, 5000)
  }
}

useIntervalFn(updateCountdown, 1000)

const setupWebSocket = async () => {
  await lotteryWS.connect()
  

  if (currentSubscription) {
    lotteryWS.unsubscribe(currentSubscription)
  }
  currentSubscription = lotteryCode.value
  lotteryWS.subscribe(lotteryCode.value)
  

  lotteryWS.on('draw_result', (data) => {
    if (data.code === lotteryCode.value) {
      showExpect.lastFullExpect = data.expect || ''
      if (data.opencode) {
        openNumbers.value = data.opencode.split(',')
      }

      setTimeout(() => {
        fetchExpectInfo()
        fetchHistory()
        isDrawing.value = false
      }, 1000)
    }
  })
  

  lotteryWS.on('new_expect', (data) => {
    if (data.code === lotteryCode.value) {
      showExpect.currFullExpect = data.expect || ''
      if (data.opentime) {
        nextOpenTime.value = new Date(data.opentime).getTime()
        updateCountdown()
      }
    }
  })
}

const switchLottery = async (newCode) => {
  

  showExpect.currFullExpect = '---'
  showExpect.lastFullExpect = '---'
  openNumbers.value = ['-', '-', '-', '-', '-']
  countdown.value = 0
  nextOpenTime.value = null
  historyList.value = []
  isDrawing.value = false
  

  fetchLotteryInfo()
  

  if (currentSubscription) {
    lotteryWS.unsubscribe(currentSubscription)
  }
  currentSubscription = newCode
  lotteryWS.subscribe(newCode)
  

  await Promise.all([
    fetchExpectInfo(),
    fetchLastResult(),
    fetchHistory()
  ])
}

watch(lotteryCode, (newCode, oldCode) => {
  if (newCode && newCode !== oldCode) {
    switchLottery(newCode)
  }
})

onMounted(async () => {
  fetchLotteryInfo() // 设置彩种名称
  await Promise.all([
    fetchExpectInfo(),
    fetchLastResult(),
    fetchHistory()
  ])
  setupWebSocket()
})

onUnmounted(() => {
  if (currentSubscription) {
    lotteryWS.unsubscribe(currentSubscription)
  }
})
</script>

<style lang="less" scoped>
@import "@/assets/theme.less";

.x5-page {
  background-color: #f5f5f5;
  min-height: 100vh;
  padding-bottom: 100px; 
}

.scroll-content {
  
}

.footer-placeholder {
  height: 20px;
}

.history-popup { height: 100%; display: flex; flex-direction: column; padding: 15px; }
.history-title { text-align: center; font-weight: 700; margin-bottom: 10px; }
.history-list { flex: 1; overflow-y: auto; }
.history-item { padding: 10px 0; border-bottom: 1px solid #f0f0f0; }
.history-row-top { display: flex; align-items: center; justify-content: space-between; }
.history-row-stats { display: flex; gap: 6px; margin-top: 6px; }
.history-item .expect-info { min-width: 100px; }
.history-item .expect { font-size: 13px; color: #333; font-weight: 500; }
.history-item .opentime { font-size: 11px; color: #999; margin-top: 2px; }
.history-item .nums { display: flex; gap: 5px; }
.history-item .ball { width: 22px; height: 22px; background: linear-gradient(135deg, #ff6034, #ee0a24); color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold; }
.stat-tag { font-size: 11px; padding: 2px 8px; border-radius: 3px; background: #f5f5f5; color: #666; }
.stat-tag.sum { background: #fff3e0; color: #e65100; }

.cart-container { height: 100%; display: flex; flex-direction: column; padding: 15px; }
.cart-header { font-size: 16px; font-weight: 700; text-align: center; margin-bottom: 10px; }
.cart-list { flex: 1; overflow-y: auto; }
.cart-footer { border-top: 1px solid #eee; padding-top: 10px; }
.cart-total { text-align: right; margin-bottom: 10px; }
.total-money { color: #ff6b6b; font-weight: 700; font-size: 18px; }
</style>
