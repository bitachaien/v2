<template>
  <div class="lottery-page-cyber">
    
    <div class="cyber-bg">
      <div class="grid-overlay"></div>
    </div>

    
    <Header :cptitle="lotteryTitle" class="cyber-header" />

    
    <div class="fixed-info-container">
      <SscLotteryInfo
        :showExpect="issueInfo"
        :lastOpenCode="openNumbers"
        :gametimes="{ ms: remainMs }"
        :is-drawing="isDrawing"
        @show-history="showHistory = true"
      />
    </div>

    
    <div class="page-content custom-scrollbar">
      
      <PanelStandard
          v-if="!isDoubleSide"
          ref="panelRef"
          @update:betCount="onBetCount"
          @update:selectedData="onSelectedData"
          @clear="onClear"
        />
      
      <PanelDoubleSide
        v-else
        ref="panelDoubleRef"
        @update:betCount="onBetCount"
        @update:selectedData="onSelectedData"
        @clear="onClear"
      />
      
      
      <div class="footer-spacing"></div>
    </div>

    
    <BettingFooter
      :betCount="betCount"
      :totalMoney="totalMoney"
      :isDoubleSide="isDoubleSide"
      :cartCount="cartItems.length"
      v-model:mode="betMode"
      v-model:multiple="multiple"
      @clear="onClear"
      @add-to-cart="addToCart"
      @open-hemai="openHemai"
      @open-cart="showCart = true"
      @submit="quickBet"
    />

    
    <van-popup v-model:show="showHistory" position="bottom" round :style="{ height: '80%' }" class="cyber-popup">
      <div class="history-container">
        <div class="history-header">
          <div class="history-title">历史开奖</div>
          <van-icon name="cross" size="20" class="close-btn" @click="showHistory = false" />
        </div>
        <van-empty v-if="historyList.length === 0" description="暂无数据" />
        <div v-else class="history-list">
          <div class="history-item" v-for="(row, idx) in historyList" :key="idx">
            <div class="item-row">
              <span class="expect-label">第 {{ row.expect }} 期</span>
              <span class="opentime-label">{{ row.opentime }}</span>
            </div>
            <div class="result-row">
              <div class="nums">
                <span v-for="(n, i) in row.codes" :key="i" class="ball">{{ n }}</span>
              </div>
              <div class="stats">
                <span class="stat-tag sum">和{{ row.sum }}</span>
                <span class="stat-tag" :class="row.bigSmall === '大' ? 'big' : 'small'">{{ row.bigSmall }}</span>
                <span class="stat-tag" :class="row.oddEven === '单' ? 'odd' : 'even'">{{ row.oddEven }}</span>
                <span class="stat-tag" :class="row.dragonTiger === '龙' ? 'dragon' : (row.dragonTiger === '虎' ? 'tiger' : '')">{{ row.dragonTiger }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </van-popup>

    
    <van-popup v-model:show="showCart" position="bottom" round :style="{ height: '70%' }" class="cyber-popup cart-popup">
      <div class="cart-container">
        <div class="cart-header">
          <h3>购彩篮</h3>
          <van-icon name="cross" @click="showCart = false" />
        </div>
        <div class="cart-content custom-scrollbar">
          <van-empty v-if="cartItems.length === 0" description="购彩篮是空的" image="search" />
          <div v-else class="cart-items">
            <div v-for="(item, index) in cartItems" :key="index" class="cart-item">
              <div class="item-header">
                <span class="play-name">{{ item.playName }}</span>
                <van-icon name="delete-o" @click="cartItems.splice(index, 1)" class="delete-icon" />
              </div>
              <div class="item-content">
                <div class="selected-numbers">
                  <span class="number-chip">{{ item.data.tzcode || item.data }}</span>
                </div>
                <div class="item-info">
                  <span>{{ item.betCount }} 注</span>
                  <span class="separator">|</span>
                  <span>{{ item.multiple }}倍</span>
                  <span class="separator">|</span>
                  <span class="amount">{{ item.money.toFixed(2) }}元</span>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div v-if="cartItems.length > 0" class="cart-footer">
          <div class="total-info">
            <span>共 {{ cartItems.length }} 单</span>
            <span class="total-amount">合计: {{ cartTotal.toFixed(2) }}元</span>
          </div>
          <div class="cart-actions">
            <button class="cyber-btn secondary" @click="cartItems = []">清空</button>
            <button class="cyber-btn primary" @click="submitCart">立即投注</button>
          </div>
        </div>
      </div>
    </van-popup>
    
    
    <Hemai
      :show="showHemai"
      :totalAmount="Number(totalMoney)"
      @close="showHemai = false"
      @submit="onHemaiSubmit"
    />

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, reactive, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Popup as VanPopup, Empty as VanEmpty, SwipeCell as VanSwipeCell, Cell as VanCell, Button as VanButton, Icon as VanIcon, showToast, showConfirmDialog, showSuccessToast, showFailToast } from 'vant'
import sscApi from '@/api/ssc'
import { lotteryWS } from '@/utils/websocket'

import Header from '../k3/components/Header.vue'
import SscLotteryInfo from './components/SscLotteryInfo.vue'
import PanelStandard from './components/PanelStandard.vue'
import PanelDoubleSide from './components/PanelDoubleSide.vue'
import BettingFooter from './components/BettingFooter.vue'
import Hemai from '../k3/components/Hemai.vue'  // 复用K3合买组件

const route = useRoute()
const router = useRouter()
const code = computed(() => route.params.code || 'cqssc')

const lotteryNames = {
  cqssc: '重庆时时彩',
  xjssc: '新疆时时彩',
  tjssc: '天津时时彩',
  txssc: '腾讯分分彩',
  ssc1fc: '大发分分彩',
  ssc3fc: '3分彩',
  ssc5fc: '5分彩',
  dfssc: '大发2分彩',
  ssctw5fc: '台湾5分彩'
}
const lotteryTitle = computed(() => lotteryNames[code.value] || code.value.toUpperCase())

const issueInfo = reactive({
  currFullExpect: '---',
  lastFullExpect: '---'
})
const openNumbers = ref(['-', '-', '-', '-', '-'])
const remainMs = ref(0)
const isDrawing = ref(false)
let timer = null

const betCount = ref(0)
const selectedData = ref(null)
const betMode = ref(2) // Default 2 Yuan
const multiple = ref(1)
const panelRef = ref(null)
const panelDoubleRef = ref(null)

const isDoubleSide = computed(() => route.query.mode === 'double')

const showHistory = ref(false)
const historyList = ref([])

const showCart = ref(false)
const cartItems = ref([])
const showHemai = ref(false)

const totalMoney = computed(() => {
  if (isDoubleSide.value) {

    return (betCount.value * parseInt(multiple.value)).toFixed(2)
  }

  return (betCount.value * parseFloat(betMode.value) * parseInt(multiple.value)).toFixed(2)
})
const cartTotal = computed(() => cartItems.value.reduce((s, i) => s + parseFloat(i.money), 0))

function onBetCount(n) { betCount.value = n }
function onSelectedData(d) { selectedData.value = d }
function onClear() {
  if (panelRef.value?.clear) panelRef.value.clear()
  if (panelDoubleRef.value?.clear) panelDoubleRef.value.clear()
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
    playName: isDoubleSide.value ? '双面玩法' : '标准玩法',
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
  if (betCount.value === 0) return showToast('请先选择号码')
  
  try {
    await showConfirmDialog({
      title: '确认投注',
      message: `注数: ${betCount.value}\n金额: ${totalMoney.value}元`,
      confirmButtonText: '确认投注',
      cancelButtonText: '取消'
    })
  } catch {
    return // 用户取消
  }

  try {

    const expectRes = await sscApi.getInfo(code.value)
    if (expectRes.code !== 0) {
      showToast('获取期号失败')
      return
    }
    const expect = expectRes.data.currFullExpect
    
    if (isDoubleSide.value) {

      const selections = selectedData.value?.selections || []
      if (selections.length === 0) {
        showToast('请选择号码')
        return
      }
      
      const res = await sscApi.submitDoubleSideBet({
        lotteryCode: code.value,
        expect: expect,
        selections: selections.map(sel => ({
          playId: sel.playId,
          value: sel.value,
          amount: sel.amount || parseInt(multiple.value)
        }))
      })
      
      if (res.code === 0) {
        showSuccessToast('投注成功')
        onClear()
      } else {
        showFailToast(res.msg || '投注失败')
      }
    } else {

      const playType = selectedData.value?.playType || panelRef.value?.getCurrentPlayType?.()
      const tzcode = selectedData.value?.tzcode || selectedData.value
      
      if (!playType) {
        showFailToast('获取玩法失败')
        return
      }
      
      const res = await sscApi.submitBet({
        lotteryCode: code.value,
        expect: expect,
        playId: playType,
        tzcode: tzcode,
        amount: parseFloat(betMode.value), // Pass unit price (denomination)
        multiplier: parseInt(multiple.value),
        mode: 1, // Always 1 for standard mode if using direct amount
        betCount: betCount.value
      })
      
      if (res.code === 0) {
        showSuccessToast('投注成功')
        onClear()
      } else {
        showFailToast(res.msg || '投注失败')
      }
    }
  } catch (e) {
    console.error('投注失败:', e)
    showFailToast(e.message || '投注失败')
  }
}

async function submitCart() {
  if (cartItems.value.length === 0) {
    showToast('购彩篮是空的')
    return
  }
  
  try {

    const expectRes = await sscApi.getInfo(code.value)
    if (expectRes.code !== 0) {
      showToast('获取期号失败')
      return
    }
    const expect = expectRes.data.currFullExpect
    
    let successCount = 0
    let failCount = 0
    
    for (const item of cartItems.value) {
      try {
        const res = await sscApi.submitBet({
          lotteryCode: code.value,
          expect: expect,
          playId: item.data?.playType || 'wxzhixfs',
          tzcode: item.data?.tzcode || item.data,
          amount: item.money / item.betCount / item.multiple, // Extract unit price
          multiplier: item.multiple,
          mode: 1,
          betCount: item.betCount
        })
        if (res.code === 0) successCount++
        else failCount++
      } catch (e) {
        failCount++
      }
    }
    
    if (failCount === 0) {
      showToast(`提交成功，共 ${successCount} 项`)
      cartItems.value = []
      showCart.value = false
    } else {
      showToast(`部分成功: ${successCount}成功, ${failCount}失败`)
    }
  } catch (e) {
    console.error('提交购彩篮失败:', e)
    showToast('提交失败')
  }
}

async function onHemaiSubmit(hemaiData) {
  try {

    const expectRes = await sscApi.getInfo(code.value)
    if (expectRes.code !== 0) {
      showToast('获取期号失败')
      return
    }
    const expect = expectRes.data.currFullExpect
    const playType = selectedData.value?.playType || panelRef.value?.getCurrentPlayType?.()
    const tzcode = selectedData.value?.tzcode || selectedData.value
    

    const res = await sscApi.submitHemai({
      lotteryCode: code.value,
      expect: expect,
      playId: playType,
      tzcode: tzcode,
      totalAmount: hemaiData.totalAmount || parseFloat(totalMoney.value),
      multiplier: multiple.value,
      mode: betMode.value,
      betCount: betCount.value,

      hemaiConfig: {
        showtype: hemaiData.showtype,
        fenshu: hemaiData.fenshu,
        rengou: hemaiData.rengou,
        isbaodi: hemaiData.isbaodi,
        baodi: hemaiData.baodi
      }
    })
    
    if (res.code === 0) {
      showSuccessToast('合买发起成功')
      showHemai.value = false
      onClear()
    } else {
      showFailToast(res.msg || '合买失败')
    }
  } catch (e) {
    console.error('合买失败:', e)
    showFailToast('合买失败')
  }
}

function startTimer() {
  if (timer) clearInterval(timer)
  timer = setInterval(() => {
    if (remainMs.value > 0) {
        remainMs.value -= 1000
    } else {
        refreshAll()
    }
  }, 1000)
}

async function refreshAll() {
  try {
    const cur = await sscApi.getInfo(code.value)
    if (cur.code === 0 && cur.data) {
      issueInfo.currFullExpect = cur.data.currFullExpect || '---'
      issueInfo.lastFullExpect = cur.data.lastFullExpect || '---'
      remainMs.value = (cur.data.remainTime || 0) * 1000
      
      if (Array.isArray(cur.data.openCodes)) {
        openNumbers.value = cur.data.openCodes
      }
    }

    const his = await sscApi.getHistory(code.value, { pageSize: 20 })
    if (his.code === 0 && his.data?.list) {
      historyList.value = his.data.list.map(it => {
        const codes = (it.opencode || '0,0,0,0,0').toString().split(',')
        const nums = codes.map(n => parseInt(n) || 0)
        const sum = nums.reduce((a, b) => a + b, 0)
        return {
          expect: it.expect || '',
          codes: codes,
          opentime: it.opentime || '',
          sum: sum,
          bigSmall: sum >= 23 ? '大' : '小',
          oddEven: sum % 2 === 0 ? '双' : '单',
          dragonTiger: nums[0] > nums[4] ? '龙' : (nums[0] < nums[4] ? '虎' : '和')
        }
      })
    }

    startTimer()
  } catch (e) {
    console.error('刷新数据失败:', e)
  }
}

function setupWS() {
  lotteryWS.subscribe(code.value, (data) => {
    if (data.type === 'draw_result') {

      if (data.opencode) {
        openNumbers.value = data.opencode.split(',')
      }
      if (data.expect) {
        issueInfo.lastFullExpect = data.expect
      }

      refreshAll()
    } else if (data.type === 'new_expect') {

      if (data.expect) {
        issueInfo.currFullExpect = data.expect
      }
      if (data.remainTime) {
        remainMs.value = data.remainTime * 1000
      }
    }
  })
}

function cleanupWS() {
  lotteryWS.unsubscribe(code.value)
}

watch(code, (newCode, oldCode) => {
  if (oldCode) {
    lotteryWS.unsubscribe(oldCode)
  }
  onClear()
  refreshAll()
  if (newCode) {
    setupWS()
  }
})

onMounted(() => {
  refreshAll()
  setupWS()
})
onUnmounted(() => { 
  if (timer) clearInterval(timer)
  cleanupWS()
})
</script>

<style lang="less" scoped>
@import "@/assets/theme.less";

.lottery-page-cyber {
  --bg-color: #05080F;
  --card-bg: rgba(23, 30, 46, 0.6);
  --neon-green: #00FF9A;
  --text-main: #ffffff;
  --text-sub: rgba(255, 255, 255, 0.6);
  --glass-border: rgba(255, 255, 255, 0.08);

  background-color: var(--bg-color);
  height: 100vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  color: var(--text-main);
  position: relative;
}

.cyber-bg {
  position: absolute;
  inset: 0;
  z-index: 0;
  overflow: hidden;
  pointer-events: none;
  background: linear-gradient(180deg, #05080F 0%, #090E1A 100%);
}
.grid-overlay {
  position: absolute;
  inset: 0;
  background-image: 
    linear-gradient(rgba(0, 255, 154, 0.03) 1px, transparent 1px),
    linear-gradient(90deg, rgba(0, 255, 154, 0.03) 1px, transparent 1px);
  background-size: 30px 30px;
  mask-image: radial-gradient(circle at 50% 30%, black 40%, transparent 90%);
}

.fixed-info-container {
  position: sticky;
  top: 0;
  z-index: 90;
  background: rgba(5, 8, 15, 0.85);
  backdrop-filter: blur(16px);
  border-bottom: 1px solid var(--glass-border);
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding-bottom: 8px;
}

.page-content {
  flex: 1;
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
  padding: 16px;
  z-index: 1;
  position: relative;
}

.footer-spacing {
  height: 160px;
}

:deep(.van-nav-bar) {
  background-color: transparent !important;
}
:deep(.van-nav-bar__title) {
  color: #fff !important;
  font-weight: bold;
  letter-spacing: 1px;
}
:deep(.van-icon) {
  color: #fff !important;
}
:deep(.van-hairline--bottom::after) {
  display: none;
}

.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 2px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}

:deep(.cyber-popup) {
  background: #0C0F17;
}

.history-container {
  height: 100%;
  display: flex;
  flex-direction: column;
  background: #0C0F17;
}
.history-header {
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  padding: 16px 20px;
  background: rgba(18, 24, 37, 0.8);
  backdrop-filter: blur(10px);
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}
.history-title { 
  font-size: 18px;
  font-weight: bold;
  color: #00FF9A;
}
.close-btn {
  position: absolute;
  right: 16px;
  color: rgba(255, 255, 255, 0.6);
  cursor: pointer;
  &:active { color: #fff; }
}
.history-list { 
  flex: 1; 
  overflow-y: auto; 
  padding: 12px;
}
.history-item { 
  background: rgba(18, 24, 37, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  padding: 12px;
  margin-bottom: 10px;
  backdrop-filter: blur(10px);
}
.item-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}
.expect-label {
  font-size: 14px;
  font-weight: bold;
  color: #00FF9A;
}
.opentime-label {
  font-size: 12px;
  color: rgba(255, 255, 255, 0.5);
}
.result-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.result-row .nums { 
  display: flex; 
  gap: 6px; 
}
.result-row .stats { 
  display: flex; 
  gap: 4px; 
}
.history-item .ball { 
  width: 28px; 
  height: 28px; 
  background: linear-gradient(135deg, #00FF9A, #00cc7a); 
  color: #000; 
  border-radius: 50%; 
  display: flex; 
  align-items: center; 
  justify-content: center; 
  font-size: 14px;
  font-weight: bold;
  box-shadow: 0 2px 8px rgba(0, 255, 154, 0.3);
}
.stat-tag {
  font-size: 10px;
  padding: 3px 6px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 4px;
  color: rgba(255, 255, 255, 0.7);
  font-weight: 500;
}
.stat-tag.sum {
  background: rgba(255, 193, 7, 0.2);
  color: #FFC107;
}
.stat-tag.big, .stat-tag.dragon {
  background: rgba(244, 67, 54, 0.2);
  color: #ff6b6b;
}
.stat-tag.small, .stat-tag.tiger {
  background: rgba(33, 150, 243, 0.2);
  color: #64b5f6;
}
.stat-tag.odd {
  background: rgba(233, 30, 99, 0.2);
  color: #f48fb1;
}
.stat-tag.even {
  background: rgba(76, 175, 80, 0.2);
  color: #81c784;
}

.cart-popup {
  background: #0C0F17;
  
  .cart-container {
    height: 100%;
    display: flex;
    flex-direction: column;
    background: #0C0F17;
  }
  
  .cart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(18, 24, 37, 0.8);
    backdrop-filter: blur(10px);
    
    h3 {
      font-size: 18px;
      font-weight: bold;
      color: #00FF9A;
      margin: 0;
    }
    
    .van-icon {
      font-size: 20px;
      color: rgba(255, 255, 255, 0.6);
      cursor: pointer;
    }
  }
  
  .cart-content {
    flex: 1;
    overflow-y: auto;
    padding: 12px;
    
    .cart-items {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }
    
    .cart-item {
      background: rgba(18, 24, 37, 0.6);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 8px;
      padding: 12px;
      backdrop-filter: blur(10px);
      
      .item-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
        
        .play-name {
          font-size: 14px;
          font-weight: bold;
          color: #00FF9A;
        }
        
        .delete-icon {
          font-size: 18px;
          color: rgba(255, 255, 255, 0.4);
          cursor: pointer;
        }
      }
      
      .item-content {
        .selected-numbers {
          display: flex;
          flex-wrap: wrap;
          gap: 6px;
          margin-bottom: 10px;
          
          .number-chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 4px 10px;
            background: rgba(0, 255, 154, 0.1);
            border: 1px solid rgba(0, 255, 154, 0.3);
            border-radius: 4px;
            color: #00FF9A;
            font-size: 12px;
            font-weight: bold;
            word-break: break-all;
          }
        }
        
        .item-info {
          display: flex;
          align-items: center;
          gap: 8px;
          font-size: 12px;
          color: rgba(255, 255, 255, 0.6);
          
          .separator { opacity: 0.3; }
          .amount { color: #00FF9A; font-weight: bold; }
        }
      }
    }
  }
  
  .cart-footer {
    padding: 16px 20px 24px;
    background: rgba(18, 24, 37, 0.95);
    backdrop-filter: blur(20px);
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    
    .total-info {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 12px;
      font-size: 14px;
      color: rgba(255, 255, 255, 0.6);
      
      .total-amount {
        font-size: 16px;
        font-weight: bold;
        color: #00FF9A;
      }
    }
    
    .cart-actions {
      display: flex;
      gap: 12px;
      
      .cyber-btn {
        flex: 1;
        height: 44px;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: bold;
        cursor: pointer;
        
        &.secondary {
          background: rgba(255, 255, 255, 0.1);
          color: rgba(255, 255, 255, 0.8);
          border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        &.primary {
          background: linear-gradient(135deg, #00FF9A, #00cc7a);
          color: #000;
          box-shadow: 0 4px 15px rgba(0, 255, 154, 0.3);
        }
      }
    }
  }
}
</style>
