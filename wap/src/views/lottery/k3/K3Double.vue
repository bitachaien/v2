<template>
  <div class="k3-lottery-page">
    
    <Header :cptitle="lotteryinfo.cptitle" @switch-lottery="handleSwitchLottery" />

    
    <LotteryInfo 
      :showExpect="showExpect" 
      :lastOpenCode="lastOpenCode"
      :gametimes="gametimes"
      :is-drawing="isDrawing"
      @show-history="handleShowHistory"
      @open-recent-draws="showHistoryPopup = true"
    />

    
    <div class="page-content">
      <PlayGridDouble ref="playGridRef" />
    </div>

    
    <div class="footer-placeholder"></div>
    <BettingFooterCt 
      @reset="handleReset"
      @confirm="handleConfirm"
    />

    
    <FloatingButtons 
      toggle-text="标准"
      @toggle-mode="navigateToStandard"
      @show-history="handleShowHistory"
    />

    
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
  </div>
</template>

<script setup>
import { reactive, computed, ref, watch, onMounted, onUnmounted } from 'vue';
import { useIntervalFn } from '@vueuse/core';
import Header from './components/Header.vue';
import LotteryInfo from './components/LotteryInfo.vue';
import PlayGridDouble from './components/PlayGridDouble.vue';
import BettingFooterCt from './components/BettingFooterCt.vue';
import FloatingButtons from './components/FloatingButtons.vue';
import HistoryDraws from './components/HistoryDraws.vue';
import { useRouter, useRoute } from 'vue-router';
import { showToast, showLoadingToast, closeToast, ActionSheet as VanActionSheet, Popup as VanPopup } from 'vant';
import { k3Api } from '@/api';
import { lotteryWS } from '@/utils/websocket';
import { COUNTDOWN, STATUS, ERROR_MESSAGES, ERROR_CODES } from '@/constants/lottery';
import { handleValidationError, handleBusinessError } from '@/utils/errorHandler';

const router = useRouter();
const route = useRoute();

const currentCode = computed(() => route.params.code || 'jsk3');

const lotteryinfo = reactive({
  cptitle: '快三',  // 默认标题，由 WS 推送更新
});

let wsCleanups = [];

watch(() => route.params.code, (newCode, oldCode) => {
  if (newCode && newCode !== oldCode) {

    handleReset();

    if (oldCode) lotteryWS.unsubscribe(oldCode);
    lotteryWS.subscribe(newCode);

    fetchCurrentIssue();
  }
});

const playGridRef = ref(null);
const showHistoryActionSheet = ref(false);
const showHistoryPopup = ref(false);
const isSubmitting = ref(false);

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

const showExpect = reactive({
  currFullExpect: '----',
  lastFullExpect: '----',
});
const lastOpenCode = ref([]);
const countdown = ref(0);
const isDrawing = ref(false);
const canBet = ref(true);
const status = ref(STATUS.OPEN); // 0=休市, 1=可投注, 2=封盘
let refreshTimer = null;

const setCountdown = (value) => {
  countdown.value = Math.max(
    COUNTDOWN.MIN_VALID_VALUE,
    Math.min(COUNTDOWN.MAX_VALID_VALUE, value || 0)
  );
};

const gametimes = computed(() => ({ ms: countdown.value * 1000 }));

const parseOpenCode = (code) => {
  if (!code) return [];
  if (Array.isArray(code)) return code.map(Number);
  if (typeof code === 'string') return code.split(',').map(Number);
  return [];
};

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
};

const initWebSocket = async () => {
  try {
    await lotteryWS.connect();
    
    const unsubSubscribed = lotteryWS.on('subscribed', handleWsSubscribed);
    wsCleanups.push(unsubSubscribed);
    
    const unsubCountdown = lotteryWS.on('countdown', handleWsCountdown);
    wsCleanups.push(unsubCountdown);
    
    const unsubDrawResult = lotteryWS.on('draw_result', handleWsDrawResult);
    wsCleanups.push(unsubDrawResult);
    
    lotteryWS.subscribe(currentCode.value);
  } catch (error) {
    console.warn('[K3Double] WebSocket 连接失败，使用 HTTP 回退:', error);
    fetchCurrentIssue();
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
    fetchCurrentIssue();
  }
};

const { pause, resume } = useIntervalFn(lotteryCycleCallback, 1000);

const startRefreshTimer = () => {
  refreshTimer = setInterval(() => {
    if (!lotteryWS.isConnected) {
      fetchCurrentIssue();
    }
  }, 60000);
};

const navigateToStandard = () => {
  router.push(`/lottery/k3/${currentCode.value}`);
};

const handleSwitchLottery = (item) => {

  lotteryinfo.cptitle = item.name;
  router.push(`/lottery/k3-double/${item.code}`);
};

const handleReset = () => {
  playGridRef.value?.clearSelection();
};

const handleConfirm = async (amount) => {
  const selectedBets = playGridRef.value?.getSelectedItems() || [];
  
  if (selectedBets.length === 0) {
    handleValidationError('请至少选择一注');
    return;
  }
  
  if (!canBet.value) {
    handleValidationError('当前期已封盘，请等待下一期');
    return;
  }
  
  if (isSubmitting.value) return;
  isSubmitting.value = true;
  
  showLoadingToast({ message: '投注中...', forbidClick: true });
  
  try {

    const bets = selectedBets.map(item => ({
      playId: item.playId || item.id,
      content: item.name,
      amount: amount
    }));
    
    const totalAmount = amount * selectedBets.length;
    
    const res = await k3Api.submitBet({
      lotteryCode: currentCode.value,
      issue: showExpect.currFullExpect,
      bets,
      totalAmount
    });
    
    closeToast();
    
    if (res.code === 0) {
      showToast({
        message: `投注成功\n订单号: ${res.data.orderId}\n共 ${selectedBets.length} 注，${totalAmount} 元`,
        type: 'success',
        duration: 3000
      });
      handleReset();

      window.dispatchEvent(new CustomEvent('bet-success'));

      try {
        const channel = new BroadcastChannel('bet-success-channel');
        channel.postMessage({ type: 'bet-success', time: Date.now() });
        channel.close();
      } catch (e) {
        localStorage.setItem('bet-success-event', Date.now().toString());
      }
    } else {

      const msg = ERROR_MESSAGES[res.code] || res.message || '投注失败';
      handleBusinessError(msg);
      
      if (res.code === ERROR_CODES.NEED_LOGIN || res.code === ERROR_CODES.LOGIN_EXPIRED) {
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

onMounted(() => {
  initWebSocket();
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

.k3-lottery-page {
  background-color: @background-color;
  min-height: 100vh;
  padding-bottom: 120px; // Space for the footer
}

.page-content {
  padding: @padding-md;
}

.footer-placeholder {
  height: 120px; // Placeholder to prevent content from being hidden by the fixed footer
}
</style>
