<template>
  <div class="history-draws">
    <div class="history-header">
      <div class="header-title">最近开奖</div>
    </div>
    <div class="history-list custom-scrollbar">
      <div v-for="draw in history" :key="draw.issue" class="history-item">
        <div class="item-left">
          <div class="issue-number">第 {{ draw.issue }} 期</div>
          <div class="sum-info">
            <span class="label">和值</span>
            <span class="value">{{ draw.sum }}</span>
            <span class="tag" :class="draw.sum > 10 ? 'big' : 'small'">{{ draw.sum > 10 ? '大' : '小' }}</span>
            <span class="tag" :class="draw.sum % 2 === 0 ? 'even' : 'odd'">{{ draw.sum % 2 === 0 ? '双' : '单' }}</span>
          </div>
        </div>
        <div class="item-right">
          <div class="dice-group">
            <div v-for="(num, index) in draw.numbers" :key="index" :class="`dice dice-${num}`"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onActivated, watch } from 'vue';
import { useRoute } from 'vue-router';
import { k3Api } from '@/api';

const props = defineProps({
  code: {
    type: String,
    default: ''
  },

  visible: {
    type: Boolean,
    default: false
  }
});

const route = useRoute();
const history = ref([]);
const loading = ref(false);
let lastFetchTime = 0;
const MIN_FETCH_INTERVAL = 3000; // 最小请求间隔 3 秒

const getCurrentCode = () => {
  return props.code || route.params.code || 'jsk3';
};

const fetchHistory = async (force = false) => {

  const now = Date.now();
  if (loading.value) return;
  if (!force && now - lastFetchTime < MIN_FETCH_INTERVAL) {
    return;
  }
  
  loading.value = true;
  lastFetchTime = now;
  try {
    const res = await k3Api.getHistory(getCurrentCode(), 20);
    if (res.code === 0 && res.data) {

      history.value = res.data.map(item => ({
        issue: item.issue,
        numbers: item.openCode,
        sum: item.sum
      }));
    }
  } catch (error) {
    console.error('获取历史开奖失败:', error);

    history.value = [
      { issue: '----', numbers: [1, 2, 3], sum: 6 }
    ];
  } finally {
    loading.value = false;
  }
};

watch(() => props.visible, (newVal) => {
  if (newVal) {
    fetchHistory();
  }
});

onMounted(() => {

  fetchHistory(true);
});

onActivated(() => {
  fetchHistory();
});

defineExpose({
  refresh: fetchHistory
});
</script>

<style lang="less" scoped>
.history-draws {
  height: 100%;
  display: flex;
  flex-direction: column;
  background: #0C0F17;
  color: #fff;
}

.history-header {
  padding: 16px 20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  background: rgba(18, 24, 37, 0.8);
  backdrop-filter: blur(10px);
  display: flex;
  align-items: center;
  justify-content: center;
  
  .header-title {
    font-size: 18px;
    font-weight: bold;
    color: #00FF9A;
    letter-spacing: 1px;
  }
}

.history-list {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.history-item {
  background: rgba(23, 30, 46, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 12px;
  padding: 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  
  .item-left {
    display: flex;
    flex-direction: column;
    gap: 8px;
    
    .issue-number {
      font-size: 14px;
      color: rgba(255, 255, 255, 0.6);
      font-family: 'DIN Alternate', sans-serif;
    }
    
    .sum-info {
      display: flex;
      align-items: center;
      gap: 8px;
      
      .label {
        font-size: 12px;
        color: rgba(255, 255, 255, 0.4);
      }
      
      .value {
        font-size: 18px;
        font-weight: bold;
        color: #00FF9A;
        font-family: 'DIN Alternate', sans-serif;
      }
      
      .tag {
        font-size: 10px;
        padding: 2px 6px;
        border-radius: 4px;
        background: rgba(255, 255, 255, 0.1);
        color: rgba(255, 255, 255, 0.8);
        
        &.big { background: rgba(255, 71, 87, 0.2); color: #ff4757; }
        &.small { background: rgba(30, 144, 255, 0.2); color: #1e90ff; }
        &.odd { background: rgba(46, 204, 113, 0.2); color: #2ecc71; }
        &.even { background: rgba(255, 165, 2, 0.2); color: #ffa502; }
      }
    }
  }
  
  .item-right {
    .dice-group {
      display: flex;
      gap: 8px;
      
      .dice {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        background-color: #fff;
        box-shadow: inset 0 0 4px rgba(0,0,0,0.1), 0 2px 6px rgba(0,0,0,0.3);
        background-repeat: no-repeat;
      }
      
      
      
      
      .dice-1 {
        background-image: radial-gradient(circle at 50% 50%, #ff4757 24%, rgba(0,0,0,0) 25%);
      }
      
      .dice-2 {
        background-image: 
          radial-gradient(circle at 30% 30%, #333 18%, rgba(0,0,0,0) 19%),
          radial-gradient(circle at 70% 70%, #333 18%, rgba(0,0,0,0) 19%);
      }
      
      .dice-3 {
        background-image: 
          radial-gradient(circle at 25% 25%, #333 18%, rgba(0,0,0,0) 19%),
          radial-gradient(circle at 50% 50%, #333 18%, rgba(0,0,0,0) 19%),
          radial-gradient(circle at 75% 75%, #333 18%, rgba(0,0,0,0) 19%);
      }
      
      .dice-4 {
        background-image: 
          radial-gradient(circle at 28% 28%, #ff4757 18%, rgba(0,0,0,0) 19%),
          radial-gradient(circle at 72% 28%, #ff4757 18%, rgba(0,0,0,0) 19%),
          radial-gradient(circle at 28% 72%, #ff4757 18%, rgba(0,0,0,0) 19%),
          radial-gradient(circle at 72% 72%, #ff4757 18%, rgba(0,0,0,0) 19%);
      }
      
      .dice-5 {
        background-image: 
          radial-gradient(circle at 25% 25%, #333 18%, rgba(0,0,0,0) 19%),
          radial-gradient(circle at 75% 25%, #333 18%, rgba(0,0,0,0) 19%),
          radial-gradient(circle at 50% 50%, #333 18%, rgba(0,0,0,0) 19%),
          radial-gradient(circle at 25% 75%, #333 18%, rgba(0,0,0,0) 19%),
          radial-gradient(circle at 75% 75%, #333 18%, rgba(0,0,0,0) 19%);
      }
      
      .dice-6 {
        background-image: 
          radial-gradient(circle at 28% 25%, #333 16%, rgba(0,0,0,0) 17%),
          radial-gradient(circle at 28% 50%, #333 16%, rgba(0,0,0,0) 17%),
          radial-gradient(circle at 28% 75%, #333 16%, rgba(0,0,0,0) 17%),
          radial-gradient(circle at 72% 25%, #333 16%, rgba(0,0,0,0) 17%),
          radial-gradient(circle at 72% 50%, #333 16%, rgba(0,0,0,0) 17%),
          radial-gradient(circle at 72% 75%, #333 16%, rgba(0,0,0,0) 17%);
      }
    }
  }
}
</style>
