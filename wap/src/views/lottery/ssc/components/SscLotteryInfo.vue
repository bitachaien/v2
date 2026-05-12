<template>
  <div class="info-card">
    
    <div class="row row-top">
      <div class="issue-text">第 <span>{{ showExpect.currFullExpect }}</span> 期</div>
      <div class="status-pill" :class="{ drawing: isDrawing }">
        <span class="dot"></span>
        <span>{{ isDrawing ? '开奖中' : '投注中' }}</span>
      </div>
    </div>

    
    <div class="row row-middle" @click="$emit('show-history')">
      <div class="last-info">
        <div class="label-row">
          <span class="label">上期 {{ showExpect.lastFullExpect }}</span>
          <van-icon name="arrow" size="12" color="rgba(255,255,255,0.4)" />
        </div>
        
        <div class="result-area-wrapper">
          <transition name="fade" mode="out-in">
            <div v-if="!isDrawing" key="result" class="result-area">
               <div class="ball-row">
                  <div v-for="(num, index) in lastOpenCode" :key="`${showExpect.lastFullExpect}-${index}`" class="ball ssc-ball">{{ num }}</div>
               </div>
            </div>
            <div v-else key="drawing" class="drawing-state">
              <div class="ball-row">
                 <div v-for="(n, i) in 5" :key="i" class="ball rolling-ball">{{ rollingNumbers[i] }}</div>
              </div>
            </div>
          </transition>
        </div>
      </div>
      
      
      <div class="stats-info" v-if="!isDrawing && lastOpenCode && lastOpenCode.length >= 5">
        <div class="stat-item sum">
          <span class="key">和值</span>
          <span class="val">{{ sumValue }}</span>
        </div>
        <div class="stat-grid">
          <span class="tag" :class="bigSmall === '大' ? 'red' : 'blue'">{{ bigSmall }}</span>
          <span class="tag" :class="oddEven === '双' ? 'green' : 'purple'">{{ oddEven }}</span>
          <span class="tag" :class="dragonTiger === '龙' ? 'red' : (dragonTiger === '虎' ? 'blue' : 'gray')">{{ dragonTiger }}</span>
        </div>
      </div>
    </div>

    
    <div class="row row-bottom">
      <span class="countdown-label">距离封盘</span>
      <van-count-down :time="gametimes.ms" format="mm:ss" class="countdown-number" />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onUnmounted } from 'vue';
import { CountDown as VanCountDown, Icon as VanIcon } from 'vant';

const props = defineProps({
  showExpect: Object,
  lastOpenCode: Array,
  gametimes: Object,
  isDrawing: Boolean,
});

defineEmits(['show-history']);

const rollingNumbers = ref([0,0,0,0,0]);
let rollTimer = null;

watch(() => props.isDrawing, (val) => {
  if (val) {
    startRolling();
  } else {
    stopRolling();
  }
});

function startRolling() {
  if (rollTimer) return;
  rollTimer = setInterval(() => {
    rollingNumbers.value = rollingNumbers.value.map(() => Math.floor(Math.random() * 10));
  }, 100);
}

function stopRolling() {
  if (rollTimer) {
    clearInterval(rollTimer);
    rollTimer = null;
  }
}

onUnmounted(() => stopRolling());

const sumValue = computed(() => {
    if (!props.lastOpenCode || props.lastOpenCode.length < 5) return '-';
    return props.lastOpenCode.reduce((a, b) => parseInt(a) + parseInt(b), 0);
});

const bigSmall = computed(() => {
    const sum = parseInt(sumValue.value);
    if (isNaN(sum)) return '-';
    return sum >= 23 ? '大' : '小';
});

const oddEven = computed(() => {
    const sum = parseInt(sumValue.value);
    if (isNaN(sum)) return '-';
    return sum % 2 === 0 ? '双' : '单';
});

const dragonTiger = computed(() => {
    if (!props.lastOpenCode || props.lastOpenCode.length < 5) return '-';
    const v1 = parseInt(props.lastOpenCode[0]);
    const v5 = parseInt(props.lastOpenCode[4]);
    if (v1 > v5) return '龙';
    if (v1 < v5) return '虎';
    return '和';
});

</script>

<style lang="less" scoped>
@import "@/assets/theme.less";

.info-card {
  background: rgba(23, 30, 46, 0.6);
  border-radius: 16px;
  margin: 12px 12px 0;
  padding: 12px 16px;
  position: relative;
  border: 1px solid rgba(255, 255, 255, 0.08);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
  backdrop-filter: blur(12px);
  display: flex;
  flex-direction: column;
  gap: 10px;
  overflow: hidden;

  
  &::after {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(0, 255, 154, 0.5), transparent);
  }
}

.row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.row-top {
  .issue-text {
    font-size: 14px;
    font-weight: 600;
    color: #fff;
    font-family: 'DIN Alternate', sans-serif;
    span { color: #00FF9A; margin: 0 2px; }
  }

  .status-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 99px;
    background: rgba(0, 255, 154, 0.1);
    border: 1px solid rgba(0, 255, 154, 0.2);
    font-size: 11px;
    color: #00FF9A;
    font-weight: bold;

    .dot {
      width: 6px; height: 6px; border-radius: 50%;
      background: #00FF9A;
      box-shadow: 0 0 8px #00FF9A;
      animation: pulse 2s infinite;
    }

    &.drawing {
      background: rgba(255, 200, 87, 0.1);
      border-color: rgba(255, 200, 87, 0.2);
      color: #FFC857;
      .dot { background: #FFC857; box-shadow: 0 0 8px #FFC857; }
    }
  }
}

.row-middle {
  align-items: flex-start;
  gap: 12px;
  cursor: pointer;
  padding: 4px 0;

  .last-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 6px;
    
    .label-row {
      display: flex; align-items: center; gap: 4px;
      .label { font-size: 12px; color: rgba(255, 255, 255, 0.4); }
    }
  }

  .stats-info {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 4px;
    min-width: 80px;
    
    .stat-item.sum {
      display: flex; align-items: center; gap: 6px;
      background: rgba(255, 255, 255, 0.05);
      padding: 2px 8px; border-radius: 6px;
      .key { font-size: 11px; color: rgba(255,255,255,0.5); }
      .val { font-size: 16px; font-weight: bold; color: #00FF9A; font-family: 'DIN Alternate', sans-serif; }
    }
    
    .stat-grid {
      display: flex; gap: 4px;
      .tag {
        font-size: 11px; padding: 1px 4px; border-radius: 3px;
        background: rgba(255,255,255,0.1); color: rgba(255,255,255,0.8);
        
        &.red { color: #ff4757; background: rgba(255, 71, 87, 0.1); }
        &.blue { color: #1e90ff; background: rgba(30, 144, 255, 0.1); }
        &.green { color: #2ed573; background: rgba(46, 213, 115, 0.1); }
        &.purple { color: #a29bfe; background: rgba(162, 155, 254, 0.1); }
        &.gray { color: #a4b0be; }
      }
    }
  }
}

.ball-row {
  display: flex; gap: 6px; flex-wrap: wrap;
}

.ball {
  width: 28px; height: 28px;
  line-height: 28px; text-align: center;
  border-radius: 50%;
  font-size: 15px; font-weight: bold;
  color: #fff;
  background: linear-gradient(145deg, #ff6b81, #ff4757);
  box-shadow: 0 2px 6px rgba(255, 71, 87, 0.3);
  font-family: 'DIN Alternate', sans-serif;
  
  &.rolling-ball {
    background: #ffa502;
    animation: bounce 0.5s infinite alternate;
  }
}

.row-bottom {
  border-top: 1px solid rgba(255, 255, 255, 0.05);
  padding-top: 8px;
  
  .countdown-label {
    font-size: 12px; color: rgba(255, 255, 255, 0.5);
  }
  
  .countdown-number {
    font-family: 'DIN Alternate', sans-serif;
    font-size: 24px; font-weight: bold;
    color: #FF4757;
    letter-spacing: 1px;
    text-shadow: 0 0 10px rgba(255, 71, 87, 0.4);
  }
}

@keyframes pulse {
  0% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.7; transform: scale(1.1); }
  100% { opacity: 1; transform: scale(1); }
}

@keyframes bounce {
  from { transform: translateY(0); }
  to { transform: translateY(-3px); }
}

.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
