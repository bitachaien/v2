<template>
  <div class="lottery-page-cyber">
    
    <div class="cyber-bg">
      <div class="grid-overlay"></div>
    </div>

    
    <Header 
      :cptitle="lotteryinfo.cptitle" 
      class="cyber-header"
      @switch-lottery="handleSwitchLottery"
    />

    
    <div class="fixed-info-container">
      
      <LotteryInfo 
        :showExpect="showExpect" 
        :lastOpenCode="lastOpenCode"
        :gametimes="gametimes"
        :is-drawing="isDrawing"
        @show-history="handleShowHistory"
        @open-recent-draws="showHistoryPopup = true"
      />

      
      <div class="tabs-container">
        <van-tabs 
          v-model:active="activeTab" 
          animated 
          swipeable 
          @change="handlePlayChange" 
          class="cyber-tabs" 
          background="transparent" 
          line-height="0"
        >
          <van-tab v-for="mode in playModes" :title="mode.name" :name="mode.id" :key="mode.id" />
        </van-tabs>
      </div>
    </div>

    
    <div class="page-content custom-scrollbar">
      <div v-if="isLoading" class="loading-container">
        <van-loading size="36px" vertical color="#00FF9A">加载中...</van-loading>
      </div>
      <template v-else>
        <transition :name="transitionName" mode="out-in">
          <keep-alive>
            <component :is="activePanelComponent" :key="activeTab" />
          </keep-alive>
        </transition>
      </template>
      
      
      <div class="footer-spacing"></div>
    </div>

    
    <BettingFooter 
      :cart-count="cartItems.length"
      :lottery-code="currentCode"
      :current-issue="showExpect.currFullExpect"
      :can-bet="canBet"
      @open-hemai="openHemaiDialog"
      @add-to-cart="handleAddToCart"
      @open-cart="showCart = true"
      @issue-expired="handleIssueExpired"
      class="cyber-footer" 
    />
    
    <Hemai 
      :show="showHemai" 
      :total-amount="betStore.totalCost"
      @close="showHemai = false"
      @submit="handleHemaiSubmit"
    />

    
    <van-popup 
      v-model:show="showCart" 
      position="bottom" 
      :style="{ height: '70%' }" 
      round 
      class="cyber-popup cart-popup"
    >
      <div class="cart-container">
        <div class="cart-header">
          <h3>购彩篮</h3>
          <van-icon name="cross" @click="showCart = false" />
        </div>
        
        <div class="cart-content custom-scrollbar">
          <div v-if="cartItems.length === 0" class="empty-cart">
            <van-icon name="shopping-cart-o" size="60" />
            <p>购彩篮为空</p>
          </div>
          
          <div v-else class="cart-items">
            <div 
              v-for="(item, index) in cartItems" 
              :key="index" 
              class="cart-item"
            >
              <div class="item-header">
                <span class="play-name">{{ getPlayName(item.playId) }}</span>
                <van-icon name="delete-o" @click="removeCartItem(index)" class="delete-icon" />
              </div>
              <div class="item-content">
                <div class="selected-numbers">
                  <span 
                    v-for="(bet, idx) in item.items" 
                    :key="idx" 
                    class="number-chip"
                  >
                    {{ bet.name }}
                  </span>
                </div>
                <div class="item-info">
                  <span>{{ item.items.length }} 注</span>
                  <span class="separator">|</span>
                  <span>{{ item.denomination }}元/注</span>
                  <span class="separator">|</span>
                  <span>{{ item.multiplier }}倍</span>
                  <span class="separator">|</span>
                  <span class="amount">{{ (item.items.length * item.denomination * item.multiplier).toFixed(2) }}元</span>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="cart-footer" v-if="cartItems.length > 0">
          <div class="total-info">
            <span>共 {{ totalCartBets }} 注</span>
            <span class="total-amount">合计: {{ totalCartAmount }}元</span>
          </div>
          <div class="cart-actions">
            <button class="cyber-btn secondary" @click="clearCart">清空</button>
            <button class="cyber-btn primary" @click="submitCart">立即投注</button>
          </div>
        </div>
      </div>
    </van-popup>

    
    <van-action-sheet
      v-model:show="showHistoryActionSheet"
      :actions="historyActions"
      cancel-text="取消"
      close-on-click-action
      @select="onHistoryActionSelect"
      class="cyber-action-sheet"
    />
    <van-popup v-model:show="showHistoryPopup" position="bottom" :style="{ height: '80%' }" round class="cyber-popup">
      <HistoryDraws :visible="showHistoryPopup" />
    </van-popup>

    
    <FloatingButtons 
      toggle-text="双面"
      @show-history="handleShowHistory" 
      @toggle-mode="navigateToDouble"
    />
  </div>
</template>

<script setup>
import { reactive, computed, ref, watch, onMounted, onUnmounted, provide } from 'vue';
import { useIntervalFn } from '@vueuse/core';
import { useRouter, useRoute } from 'vue-router';
import { Tabs as VanTabs, Tab as VanTab, Icon as VanIcon, ActionSheet as VanActionSheet, Popup as VanPopup, Loading as VanLoading, showToast, showLoadingToast, closeToast } from 'vant';
import { k3Api } from '@/api';
import { lotteryWS } from '@/utils/websocket';
import { COUNTDOWN, STATUS } from '@/constants/lottery';
import Header from './components/Header.vue';
import LotteryInfo from './components/LotteryInfo.vue';
import Hemai from './components/Hemai.vue';
import PanelHezhi from './components/PanelHezhi.vue';
import PanelSantonghaoTx from './components/PanelSantonghaoTx.vue';
import PanelSanbutonghao from './components/PanelSanbutonghao.vue';
import PanelSantonghaoDx from './components/PanelSantonghaoDx.vue';
import PanelSanlianhaoTx from './components/PanelSanlianhaoTx.vue';
import PanelErtonhaoFx from './components/PanelErtonhaoFx.vue';
import PanelErtonhaoDx from './components/PanelErtonhaoDx.vue';
import PanelErbutonghao from './components/PanelErbutonghao.vue';
import PanelHongheima from './components/PanelHongheima.vue';
import { useK3BetStore } from '@/stores/k3Bet';
import BettingFooter from './components/BettingFooter.vue';
import HistoryDraws from './components/HistoryDraws.vue';
import FloatingButtons from './components/FloatingButtons.vue';

const router = useRouter();
const route = useRoute();

const currentCode = computed(() => route.params.code || 'jsk3');

const lotteryinfo = reactive({
  cptitle: '快三',  // 默认标题，由 WS 推送更新
});

let wsCleanups = [];

watch(() => route.params.code, (newCode, oldCode) => {
  if (newCode && newCode !== oldCode) {

    betStore.clearAllBets();
    cartItems.value = [];

    if (oldCode) lotteryWS.unsubscribe(oldCode);
    lotteryWS.subscribe(newCode);

    fetchCurrentIssue();

    initData();
  }
}, { immediate: false });

const isLoading = ref(false);
const showExpect = reactive({
  currFullExpect: '----',
  lastFullExpect: '----',
});
const lastOpenCode = ref([]);
const countdown = ref(0);
const isDrawing = ref(false);
const canBet = ref(true);
const status = ref(STATUS.OPEN); // 0=休市, 1=可投注, 2=封盘

const setCountdown = (value) => {

  countdown.value = Math.max(
    COUNTDOWN.MIN_VALID_VALUE,
    Math.min(COUNTDOWN.MAX_VALID_VALUE, value || 0)
  );
};

const parseOpenCode = (code) => {
  if (!code) return [];
  if (Array.isArray(code)) return code.map(Number);
  if (typeof code === 'string') return code.split(',').map(Number);
  return [];
};
let refreshTimer = null;
let lastFetchTime = 0; // 防止倒计时为0时重复请求
const MIN_FETCH_INTERVAL = 5000; // 最小请求间隔 5 秒

const gametimes = computed(() => ({ ms: countdown.value * 1000 }));

const fetchCurrentIssue = async () => {
  try {
    const res = await k3Api.getCurrentIssue(currentCode.value);
    if (res.code === 0 && res.data) {
      const data = res.data;
      showExpect.currFullExpect = data.currentIssue;
      showExpect.lastFullExpect = data.lastIssue;
      lastOpenCode.value = parseOpenCode(data.lastOpenCode);
      setCountdown(data.countdown);
      status.value = data.status ?? STATUS.OPEN;
      isDrawing.value = data.status === STATUS.CLOSED;
      canBet.value = data.status === STATUS.OPEN && data.countdown > COUNTDOWN.WARNING_THRESHOLD;
      
      if (data.lotteryName) {
        lotteryinfo.cptitle = data.lotteryName;
      }
    }
  } catch (error) {
    console.error('获取期号信息失败:', error);
  }
};

const handleWsSubscribed = (data) => {
  if (data.lotteryCode !== currentCode.value) return;
  
  const current = data.current;
  if (current) {
    showExpect.currFullExpect = current.currentIssue || '----';
    showExpect.lastFullExpect = current.lastIssue || '----';
    setCountdown(current.countdown);
    status.value = current.status ?? STATUS.OPEN;
    isDrawing.value = current.status === STATUS.CLOSED;
    canBet.value = current.status === STATUS.OPEN && current.countdown > COUNTDOWN.WARNING_THRESHOLD;
    
    if (current.lotteryName) {
      lotteryinfo.cptitle = current.lotteryName;
    }
    

    if (current.lastOpenCode) {
      lastOpenCode.value = parseOpenCode(current.lastOpenCode);
    }
  }
};

const handleWsCountdown = (data) => {
  if (data.lotteryCode !== currentCode.value) return;
  

  showExpect.currFullExpect = data.currentIssue;
  setCountdown(data.countdown);
  status.value = data.status ?? STATUS.OPEN;
  isDrawing.value = data.status === STATUS.CLOSED;
  canBet.value = data.status === STATUS.OPEN && data.countdown > COUNTDOWN.WARNING_THRESHOLD;
  

  if (data.lotteryName) {
    lotteryinfo.cptitle = data.lotteryName;
  }
  

  if (data.lastIssue) {
    showExpect.lastFullExpect = data.lastIssue;
  }
  if (data.lastOpenCode) {
    lastOpenCode.value = parseOpenCode(data.lastOpenCode);
  }
};

const handleWsDrawResult = (data) => {
  if (data.lotteryCode !== currentCode.value) return;
  
  showExpect.lastFullExpect = data.issue;
  lastOpenCode.value = parseOpenCode(data.openCode);
  isDrawing.value = false;
  

  notifyGameHistoryRefresh('draw-result');
};

const handleWsBetSettled = (data) => {

  const statusText = data.status === 'win' ? '中奖' : '未中奖';
  const amountText = data.status === 'win' ? `+${data.winAmount}` : `-${data.betAmount}`;
  showToast({
    message: `${data.issue}期 ${statusText}\n${amountText}元`,
    duration: 3000
  });
  

  notifyGameHistoryRefresh('bet-settled');
};

const notifyGameHistoryRefresh = (type) => {
  window.dispatchEvent(new CustomEvent(type));
  try {
    const channel = new BroadcastChannel('bet-success-channel');
    channel.postMessage({ type, time: Date.now() });
    channel.close();
  } catch (e) {
    localStorage.setItem('bet-success-event', Date.now().toString());
  }
};

const lotteryCycleCallback = () => {

  if (lotteryWS.isConnected) return;
  
  if (countdown.value > 0) {
    countdown.value--;
    if (countdown.value <= COUNTDOWN.WARNING_THRESHOLD) {
      canBet.value = false;
    }
  } else {
    isDrawing.value = true;
    canBet.value = false;

    const now = Date.now();
    if (now - lastFetchTime >= MIN_FETCH_INTERVAL) {
      lastFetchTime = now;
      fetchCurrentIssue();
    }
  }
};

const { pause, resume } = useIntervalFn(lotteryCycleCallback, 1000);

const initWebSocket = async () => {
  try {
    await lotteryWS.connect();
    

    const unsubSubscribed = lotteryWS.on('subscribed', handleWsSubscribed);
    wsCleanups.push(unsubSubscribed);
    

    const unsubCountdown = lotteryWS.on('countdown', handleWsCountdown);
    wsCleanups.push(unsubCountdown);
    

    const unsubDrawResult = lotteryWS.on('draw_result', handleWsDrawResult);
    wsCleanups.push(unsubDrawResult);
    

    const unsubBetSettled = lotteryWS.on('bet_settled', handleWsBetSettled);
    wsCleanups.push(unsubBetSettled);
    

    lotteryWS.subscribe(currentCode.value);
    
  } catch (error) {
    console.warn('[K3] WebSocket 连接失败，使用 HTTP 回退:', error);

    fetchCurrentIssue();
  }
};

const startRefreshTimer = () => {
  refreshTimer = setInterval(() => {

    if (!lotteryWS.isConnected) {
      fetchCurrentIssue();
    }
  }, 60000);
};

const oddsData = ref(null);

const fetchOdds = async () => {
  try {
    const res = await k3Api.getOdds(currentCode.value);
    if (res.code === 0 && res.data) {
      oddsData.value = res.data;

      window.__k3Odds = res.data;
    }
  } catch (error) {
    console.error('获取玩法赔率失败:', error);
  }
};

provide('oddsData', oddsData);
provide('lotteryCode', currentCode);

const playModes = [
  { id: 'k3hzzx', name: '和值', tips: '猜3个开奖号码相加的和' },
  { id: 'k3sthtx', name: '三同号通选', tips: '选1个三同号(111,222..),猜中开奖的3个相同号码即中奖' },
  { id: 'k3sthdx', name: '三同号单选', tips: '猜中指定的3个相同号码即中奖(111,222...)' },
  { id: 'k3sbthbz', name: '三不同号', tips: '从1-6中任选3个或多个号码' },
  { id: 'k3slhtx', name: '三连号通选', tips: '对所有3个相连的号码(123,234,345,456)进行投注' },
  { id: 'k3ethfx', name: '二同号复选', tips: '选1个对子,猜中开奖的对子即中奖' },
  { id: 'k3ethdx', name: '二同号单选', tips: '选择1个对子和1个单号进行投注' },
  { id: 'k3ebthbz', name: '二不同号', tips: '从1-6中任选2个或多个号码' },
  { id: 'k3hhm', name: '红黑码', tips: '根据开奖号码猜红黑' },
];
const activeTab = ref('k3hzzx');
const panelComponents = {
  k3hzzx: PanelHezhi,
  k3sthtx: PanelSantonghaoTx,
  k3sthdx: PanelSantonghaoDx,
  k3sbthbz: PanelSanbutonghao,
  k3slhtx: PanelSanlianhaoTx,
  k3ethfx: PanelErtonhaoFx,
  k3ethdx: PanelErtonhaoDx,
  k3ebthbz: PanelErbutonghao,
  k3hhm: PanelHongheima,
};
const activePanelComponent = computed(() => panelComponents[activeTab.value] || null);
const showHistoryActionSheet = ref(false);
const showHistoryPopup = ref(false);
const historyActions = [
  { name: '最近开奖' },
  { name: '我的投注' },
];
const handleShowHistory = () => {
  showHistoryActionSheet.value = true;
};
const onHistoryActionSelect = (action) => {
  if (action.name === '最近开奖') {
    showHistoryPopup.value = true;
  } else if (action.name === '我的投注') {
    router.push('/userCenter/betRecord');
  }
};
const transitionName = ref('slide-left');
const handlePlayChange = (name) => {
  const newIndex = playModes.findIndex(p => p.id === activeTab.value);
  const oldIndex = playModes.findIndex(p => p.id === name);
  transitionName.value = newIndex > oldIndex ? 'slide-left' : 'slide-right';
};

const betStore = useK3BetStore();
const showHemai = ref(false);
const showCart = ref(false);
const cartItems = ref([]);

const isSubmitting = ref(false);

const openHemaiDialog = () => {
  if (betStore.totalBets === 0) {
    showToast('请先选择投注号码');
    return;
  }
  showHemai.value = true;
};

const handleAddToCart = () => {
  if (betStore.totalBets === 0) {
    showToast('请先选择投注号码');
    return;
  }
  

  betStore.selections.forEach(selection => {
    if (selection.items.length > 0) {
      cartItems.value.push({
        playId: selection.playId,
        items: [...selection.items],
        denomination: betStore.denomination,
        multiplier: betStore.betMultiplier,
        timestamp: Date.now()
      });
    }
  });
  

  betStore.clearAllBets();
  
  showToast(`已添加到购彩篮，当前共 ${cartItems.value.length} 项`);
};

const getPlayName = (playId) => {
  const play = playModes.find(p => p.id === playId);
  return play ? play.name : playId;
};

const removeCartItem = (index) => {
  cartItems.value.splice(index, 1);
};

const clearCart = () => {
  cartItems.value = [];
};

const submitCart = async () => {
  if (cartItems.value.length === 0) {
    showToast('购彩篮为空');
    return;
  }
  
  if (!canBet.value) {
    showToast('当前期已封盘，请等待下一期');
    return;
  }
  
  if (isSubmitting.value) return;
  isSubmitting.value = true;
  

  const bets = [];
  cartItems.value.forEach(cartItem => {
    cartItem.items.forEach(item => {
      bets.push({
        playId: item.playid, // 使用每个选项的后端 playId，而不是前端组 ID
        content: item.name,
        amount: cartItem.denomination * cartItem.multiplier
      });
    });
  });
  
  const totalAmount = cartItems.value.reduce((sum, item) => {
    return sum + (item.items.length * item.denomination * item.multiplier);
  }, 0);
  
  showLoadingToast({ message: '投注中...', forbidClick: true });
  
  try {
    const res = await k3Api.submitBet({
      lotteryCode: currentCode.value,
      issue: showExpect.currFullExpect,
      bets,
      totalAmount
    });
    
    closeToast();
    
    if (res.code === 0) {
      showToast({
        message: `投注成功\n订单号: ${res.data.orderId}`,
        type: 'success',
        duration: 3000
      });
      clearCart();
      showCart.value = false;

      window.dispatchEvent(new CustomEvent('bet-success'));

      try {
        const channel = new BroadcastChannel('bet-success-channel');
        channel.postMessage({ type: 'bet-success', time: Date.now() });
        channel.close();
      } catch (e) {
        localStorage.setItem('bet-success-event', Date.now().toString());
      }
    } else {
      const errorMessages = {
        1002: '期号已截止，请等待下一期',
        1003: '余额不足，请先充值',
        2001: '请先登录',
        2002: '登录已过期'
      };
      showToast(errorMessages[res.code] || res.message || '投注失败');
      if (res.code === 2001 || res.code === 2002) {
        router.push('/home-new?auth=login');
      }
    }
  } catch (error) {
    closeToast();
    showToast(error.message || '投注失败，请稍后重试');
  } finally {
    isSubmitting.value = false;
  }
};

const totalCartBets = computed(() => {
  return cartItems.value.reduce((sum, item) => {
    return sum + item.items.length * item.multiplier;
  }, 0);
});

const totalCartAmount = computed(() => {
  return cartItems.value.reduce((sum, item) => {
    return sum + (item.items.length * item.denomination * item.multiplier);
  }, 0).toFixed(2);
});

const handleHemaiSubmit = async (hemaiData) => {
  const orderList = betStore.selections.map(selection => ({
    playid: selection.playId,
    items: selection.items
  }));
  
  if (!orderList || orderList.length === 0) {
    showToast('投注篮为空，无法发起合买');
    return;
  }
  
  if (!canBet.value) {
    showToast('当前期已封盘，请等待下一期');
    return;
  }
  
  showLoadingToast({ message: '发起合买中...', forbidClick: true });

  try {

    const bets = [];
    orderList.forEach(order => {
      order.items.forEach(item => {
        bets.push({
          playId: order.playid,
          content: item.name,
          amount: betStore.denomination * betStore.betMultiplier
        });
      });
    });
    
    const totalAmount = bets.reduce((sum, b) => sum + b.amount, 0);
    
    const res = await k3Api.submitHemai({
      lotteryCode: currentCode.value,
      issue: showExpect.currFullExpect,
      bets,
      totalAmount,
      hemaiConfig: {
        totalShares: hemaiData.totalShares || 10,
        minBuyShares: hemaiData.minBuyShares || 1,
        selfShares: hemaiData.selfShares || 1,
        commission: hemaiData.commission || 10,
        isPublic: hemaiData.isPublic !== false,
        description: hemaiData.description || ''
      }
    });
    
    closeToast();
    
    if (res.code === 0) {
      showToast({
        message: `合买发起成功\n合买号: ${res.data.hemaiId}`,
        type: 'success',
        duration: 3000
      });
      betStore.clearAllBets();

      window.dispatchEvent(new CustomEvent('bet-success'));

      try {
        const channel = new BroadcastChannel('bet-success-channel');
        channel.postMessage({ type: 'bet-success', time: Date.now() });
        channel.close();
      } catch (e) {
        localStorage.setItem('bet-success-event', Date.now().toString());
      }
    } else {
      showToast(res.message || '合买发起失败');
    }
  } catch (error) {
    closeToast();
    console.error('发起合买请求失败:', error);
    showToast(error.message || '合买发起失败，请稍后重试');
  }

  showHemai.value = false;
};

const handleSwitchLottery = (item) => {

  lotteryinfo.cptitle = item.name;
  router.push(`/lottery/k3/${item.code}`);
};

const navigateToDouble = () => {
  router.push(`/lottery/k3-double/${currentCode.value}`);
};

const handleIssueExpired = (newIssue) => {
  if (newIssue) {

    showExpect.currFullExpect = newIssue;
    canBet.value = true; // 新期号应该可以投注
  }

  lastFetchTime = 0;
  fetchCurrentIssue();
};

const initData = async () => {
  isLoading.value = true;
  try {

    await fetchOdds();
  } catch (error) {
    console.error('初始化数据失败:', error);
  } finally {
    isLoading.value = false;
  }
};

onMounted(() => {

  initWebSocket();
  initData();
  startRefreshTimer();
});

onUnmounted(() => {

  wsCleanups.forEach(cleanup => cleanup());
  wsCleanups = [];
  lotteryWS.unsubscribe(currentCode.value);
  
  if (refreshTimer) {
    clearInterval(refreshTimer);
  }
  pause();
});
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
  height: 140px; // ensure content is not hidden behind fixed footer
}

:deep(.cart-popup) {
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
      
      &:active {
        color: #fff;
      }
    }
  }
  
  .cart-content {
    flex: 1;
    overflow-y: auto;
    padding: 12px;
    
    .empty-cart {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      height: 100%;
      color: rgba(255, 255, 255, 0.3);
      
      .van-icon {
        margin-bottom: 16px;
        opacity: 0.3;
      }
      
      p {
        font-size: 14px;
      }
    }
    
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
          
          &:active {
            color: #ff4757;
          }
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
          }
        }
        
        .item-info {
          display: flex;
          align-items: center;
          gap: 8px;
          font-size: 12px;
          color: rgba(255, 255, 255, 0.6);
          
          .separator {
            opacity: 0.3;
          }
          
          .amount {
            color: #00FF9A;
            font-weight: bold;
          }
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
        transition: all 0.2s;
        
        &.secondary {
          background: rgba(255, 255, 255, 0.1);
          color: rgba(255, 255, 255, 0.8);
          border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        &.primary {
          background: #e1251b;
          color: #fff;
          box-shadow: 0 4px 12px rgba(225, 37, 27, 0.4);
        }
        
        &:active {
          transform: scale(0.96);
        }
      }
    }
  }
}

.tabs-container {
  padding: 0 16px;
}

:deep(.cyber-tabs) {
  .van-tabs__nav {
    background: transparent;
  }
  .van-tab {
    color: var(--text-sub);
    font-weight: 500;
    transition: all 0.3s;
    padding: 0 12px;
    margin-right: 4px;
    
    &--active {
      color: var(--neon-green);
      font-weight: bold;
      background: rgba(0, 255, 154, 0.05);
      border-radius: 16px;
      
      .van-tab__text {
        text-shadow: 0 0 8px rgba(0, 255, 154, 0.4);
      }
    }
  }
  .van-tabs__line {
    display: none; // removing default line, using active state style
  }
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

.loading-container {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 300px;
  width: 100%;
}
</style>
