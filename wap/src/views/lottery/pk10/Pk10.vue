<template>
  <div class="pk10-lottery-page">
    
    <Header :cptitle="lotteryinfo.cptitle" />

    
    <Pk10LotteryInfo
      :showExpect="showExpect"
      :lastOpenCode="lastOpenCode"
      :gametimes="gametimes"
      :is-drawing="isDrawing"
      @show-history="handleShowHistory"
    />

    
    
    
    <div v-if="isStandardMode" class="mode-container standard-mode">
       <div class="mode-switch-header">
          <span class="title">标准玩法</span>
          <span class="switch-btn" @click="switchMode('double')">
             [切换到双面] <van-icon name="exchange" />
          </span>
       </div>
       
       <div class="page-content">
          <PanelStandard />
       </div>
    </div>

    
    <div v-else class="mode-container double-mode">
       <div class="mode-switch-header">
          <span class="title">信用玩法 - 双面盘</span>
          <span class="switch-btn" @click="switchMode('standard')">
             [切换到标准] <van-icon name="exchange" />
          </span>
       </div>

       
       

       <div class="page-content">
          <PanelDoubleSide activeTab="shuangmian" />
       </div>
    </div>

    
    <div class="footer-placeholder"></div>
    <BettingFooter @open-hemai="openHemaiDialog" />

    <Hemai
      :show="showHemai"
      :total-amount="betStore.totalCost"
      @close="showHemai = false"
      @submit="handleHemaiSubmit"
    />

    
    <van-action-sheet
      v-model:show="showHistoryActionSheet"
      :actions="historyActions"
      cancel-text="取消"
      close-on-click-action
      @select="onHistoryActionSelect"
    />
    <van-popup v-model:show="showHistoryPopup" position="bottom" :style="{ height: '80%' }" round>
      <HistoryDraws />
    </van-popup>

    
    <FloatingButtons
      :toggle-text="isStandardMode ? '双面' : '标准'"
      @show-history="handleShowHistory"
      @toggle-mode="() => switchMode(isStandardMode ? 'double' : 'standard')"
    />

  </div>
</template>

<script setup>
import { reactive, computed, ref, watch, onMounted, onUnmounted, provide } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useIntervalFn } from '@vueuse/core';
import { Tabs as VanTabs, Tab as VanTab, ActionSheet as VanActionSheet, Popup as VanPopup, Icon as VanIcon, showToast } from 'vant';

import Header from '../k3/components/Header.vue';
import Pk10LotteryInfo from './components/Pk10LotteryInfo.vue';
import PanelDoubleSide from './components/PanelDoubleSide.vue';
import PanelStandard from './components/PanelStandard.vue';
import BettingFooter from './components/BettingFooter.vue';
import HistoryDraws from './components/HistoryDraws.vue';
import Hemai from '../k3/components/Hemai.vue';
import FloatingButtons from '../k3/components/FloatingButtons.vue';

import { usePk10BetStore } from '@/stores/pk10Bet';
import { pk10Api } from '@/api';
import { lotteryWS } from '@/utils/websocket';

const router = useRouter();
const route = useRoute();
const betStore = usePk10BetStore();

const lotteryCode = computed(() => route.params.code || 'bjpk10');

const lotteryinfo = reactive({
  cptitle: '北京PK10',
});

const showExpect = reactive({
  currFullExpect: '',
  lastFullExpect: '',
});

const lastOpenCode = ref([]);
const countdown = ref(0);
const isDrawing = ref(false);
const serverTimeOffset = ref(0);
const nextOpenTime = ref(null);

const gametimes = computed(() => ({ ms: countdown.value * 1000 }));

provide('lotteryCode', lotteryCode);

const PK10_TITLES = {
  'bjpk10': '北京PK10',
  'dfpk10': '台湾PK10',
  'pk101': '1分赛车',
  'pk103': '3分赛车',
  'pk105': '5分赛车',
  'xyft': '幸运飞艇'
};

const fetchLotteryInfo = () => {
  lotteryinfo.cptitle = PK10_TITLES[lotteryCode.value] || 'PK10';
};

const fetchExpectInfo = async () => {
  try {
    const res = await pk10Api.getInfo(lotteryCode.value);
    if (res.code === 0 && res.data) {
      showExpect.currFullExpect = res.data.currFullExpect || res.data.expect || '';
      

      if (res.data.remainTime) {
        countdown.value = res.data.remainTime;
        nextOpenTime.value = Date.now() + res.data.remainTime * 1000;
      }
    }
  } catch (e) {
    console.error('获取期号信息失败:', e);
  }
};

const fetchLastResult = async () => {
  try {
    const res = await pk10Api.getLastResult(lotteryCode.value);
    if (res.code === 0 && res.data) {
      showExpect.lastFullExpect = res.data.expect || res.data.qihao || '';
      

      let opencode = res.data.opencode || res.data.openCode || res.data.kjhm || '';
      
      if (opencode) {

        if (Array.isArray(opencode)) {
          lastOpenCode.value = opencode.map(n => parseInt(n, 10));
        } else {

          lastOpenCode.value = opencode.split(',').map(n => parseInt(n, 10));
        }
      }
    }
  } catch (e) {
    console.error('获取上期开奖失败:', e);
  }
};

const updateCountdown = () => {
  if (!nextOpenTime.value) {
    countdown.value = 0;
    return;
  }
  const now = Date.now() + serverTimeOffset.value;
  const diff = Math.max(0, Math.floor((nextOpenTime.value - now) / 1000));
  countdown.value = diff;
  
  if (diff <= 0 && !isDrawing.value) {
    isDrawing.value = true;

    setTimeout(() => {
      fetchLastResult();
      fetchExpectInfo();
      isDrawing.value = false;
    }, 5000);
  }
};

useIntervalFn(updateCountdown, 1000);

const setupWebSocket = async () => {
  await lotteryWS.connect();
  lotteryWS.subscribe(lotteryCode.value);
  
  lotteryWS.on('draw_result', (data) => {
    if (data.code === lotteryCode.value) {
      showExpect.lastFullExpect = data.expect || '';
      if (data.opencode) {
        lastOpenCode.value = data.opencode.split(',').map(n => parseInt(n, 10));
      }

      setTimeout(() => {
        fetchExpectInfo();
        isDrawing.value = false;
      }, 1000);
    }
  });
  
  lotteryWS.on('new_expect', (data) => {
    if (data.code === lotteryCode.value) {
      showExpect.currFullExpect = data.expect || '';
      if (data.opentime) {
        nextOpenTime.value = new Date(data.opentime).getTime();
        updateCountdown();
      }
    }
  });
};

onMounted(async () => {
  fetchLotteryInfo(); // Sync call
  await Promise.all([
    fetchExpectInfo(),
    fetchLastResult()
  ]);
  setupWebSocket();
});

onUnmounted(() => {
  lotteryWS.unsubscribe(lotteryCode.value);
});

const isStandardMode = computed(() => route.query.mode === 'standard');

const switchMode = (mode) => {
    router.replace({ 
        name: route.name, 
        params: { code: route.params.code }, 
        query: { mode }
    });
    betStore.clearAllBets();
};

const activeTab = ref('shuangmian');
const showPlayMenu = ref(false); // For standard mode dropdown (mock)

const showHistoryActionSheet = ref(false);
const showHistoryPopup = ref(false);
const historyActions = [
  { name: '最近开奖' },
  { name: '我的投注' },
];

const handleShowHistory = () => {

  showHistoryPopup.value = true;
};

const onHistoryActionSelect = (action) => {
  if (action.name === '最近开奖') {
    showHistoryPopup.value = true;
  } else if (action.name === '我的投注') {
    router.push('/userCenter/betRecord');
  }
};

const showHemai = ref(false);
const openHemaiDialog = () => {
    if (betStore.totalBets === 0) return;
    showHemai.value = true;
};

const handleHemaiSubmit = (hemaiData) => {
    alert('合买发起成功 (模拟)');
    showHemai.value = false;
    betStore.clearAllBets();
};

</script>

<style lang="less" scoped>
@import "@/assets/theme.less";

.pk10-lottery-page {
  background-color: @background-color;
  min-height: 100vh;
  padding-bottom: 100px;
}

.page-content {
  padding: 0;
}

.play-select-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 16px;
    background: #fff;
    border-bottom: 1px solid #eee;
    
    .current-play {
        display: flex;
        flex-direction: column;
        .label { font-size: 12px; color: #999; }
        .value { font-size: 16px; font-weight: bold; color: #333; display: flex; align-items: center; gap: 4px; }
    }
    .switch-btn {
        font-size: 14px;
        color: @primary-color;
        display: flex;
        align-items: center;
        gap: 4px;
        cursor: pointer;
    }
}

.mode-switch-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 16px;
    background: #f5f5f5;
    
    .title { font-weight: bold; font-size: 14px; }
    .switch-btn { font-size: 13px; color: @primary-color; cursor: pointer; display: flex; align-items: center; gap: 2px;}
}

:deep(.van-tabs__nav) {
  background-color: @card-background-color;
}
:deep(.van-tab--active) {
  color: @primary-color;
  font-weight: bold;
}
:deep(.van-tabs__line) {
  background-color: @primary-color;
}

.footer-placeholder {
    height: 80px;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
