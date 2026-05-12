<template>
  <div class="info-card">
    <div class="row row-top">
      <div class="issue-text">第 {{ showExpect.currFullExpect }} 期</div>
      <div class="status-pill" :class="{ drawing: isDrawing }">
        <span class="dot"></span>
        <span>{{ isDrawing ? '开奖中' : '投注中' }}</span>
      </div>
    </div>

    <div class="row row-middle" @click="$emit('open-recent-draws')">
      <div class="last-info">
        <span class="label">上期 {{ showExpect.lastFullExpect }}</span>
        <div v-if="lastOpenCode && lastOpenCode.length" class="dice-mini" :class="{ shaking: isDrawing }">
          <span 
            v-for="(num, index) in lastOpenCode.slice(0, 3)" 
            :key="`${showExpect.lastFullExpect}-${index}`" 
            :class="`pip pip-${num}`"
          ></span>
        </div>
        <div v-else class="waiting">
          <van-icon name="clock-o" />
          <span>等待开奖</span>
        </div>
      </div>
      <div class="sum-info" v-if="lastOpenCode && lastOpenCode.length">
        和值 <span class="value">{{ calculateSum(lastOpenCode) }}</span>
      </div>
    </div>

    <div class="row row-bottom">
      <span class="countdown-label">距离封盘</span>
      <van-count-down :time="gametimes.ms" format="mm:ss" class="countdown-number" />
    </div>
  </div>
</template>

<script setup>
import { CountDown as VanCountDown, Icon as VanIcon } from 'vant';

defineProps({
  showExpect: Object,
  lastOpenCode: Array,
  gametimes: Object,
  isDrawing: Boolean,
});

defineEmits(['show-history', 'open-recent-draws']);

const calculateSum = (codes) => {
  if (!codes || !codes.length) return 0;
  return codes.reduce((sum, num) => sum + parseInt(num), 0);
};
</script>

<style lang="less" scoped>
.info-card {
  background: rgba(23, 30, 46, 0.6);
  border-radius: 16px;
  margin: 8px 12px 0;
  padding: 12px 16px;
  position: relative;
  border: 1px solid rgba(255, 255, 255, 0.08);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
  backdrop-filter: blur(12px);
  min-height: 110px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  overflow: hidden;

  
  &::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
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
  font-size: 13px;
  color: rgba(255, 255, 255, 0.6);

  .issue-text {
    font-size: 14px;
    font-weight: 600;
    font-family: 'DIN Alternate', sans-serif;
    color: #fff;
    letter-spacing: 0.5px;
    
    span {
      color: #00FF9A;
      margin: 0 4px;
    }
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
      width: 6px;
      height: 6px;
      border-radius: 50%;
      background: #00FF9A;
      box-shadow: 0 0 8px #00FF9A;
      animation: pulse 2s infinite;
    }

    &.drawing {
      background: rgba(255, 200, 87, 0.1);
      border-color: rgba(255, 200, 87, 0.2);
      color: #FFC857;
      
      .dot {
        background: #FFC857;
        box-shadow: 0 0 8px #FFC857;
      }
    }
  }
}

.row-middle {
  margin: 4px 0;
  cursor: pointer;
  
  .last-info {
    display: flex;
    align-items: center;
    gap: 12px;
    
    .label {
      font-size: 12px;
      color: rgba(255, 255, 255, 0.4);
    }

    .dice-mini {
      display: flex;
      gap: 6px;

      .pip {
        width: 28px;
        height: 28px;
        border-radius: 6px;
        background-color: #fff;
        box-shadow: inset 0 0 4px rgba(0,0,0,0.1), 0 2px 6px rgba(0,0,0,0.3);
        background-repeat: no-repeat;
      }
      
      
      
      
      .pip-1 {
        background-image: radial-gradient(circle at 50% 50%, #ff4757 24%, rgba(0,0,0,0) 25%);
      }
      
      .pip-2 {
        background-image: 
          radial-gradient(circle at 30% 30%, #333 18%, rgba(0,0,0,0) 19%),
          radial-gradient(circle at 70% 70%, #333 18%, rgba(0,0,0,0) 19%);
      }
      
      .pip-3 {
        background-image: 
          radial-gradient(circle at 25% 25%, #333 18%, rgba(0,0,0,0) 19%),
          radial-gradient(circle at 50% 50%, #333 18%, rgba(0,0,0,0) 19%),
          radial-gradient(circle at 75% 75%, #333 18%, rgba(0,0,0,0) 19%);
      }
      
      .pip-4 {
        background-image: 
          radial-gradient(circle at 28% 28%, #ff4757 18%, rgba(0,0,0,0) 19%),
          radial-gradient(circle at 72% 28%, #ff4757 18%, rgba(0,0,0,0) 19%),
          radial-gradient(circle at 28% 72%, #ff4757 18%, rgba(0,0,0,0) 19%),
          radial-gradient(circle at 72% 72%, #ff4757 18%, rgba(0,0,0,0) 19%);
      }
      
      .pip-5 {
        background-image: 
          radial-gradient(circle at 25% 25%, #333 18%, rgba(0,0,0,0) 19%),
          radial-gradient(circle at 75% 25%, #333 18%, rgba(0,0,0,0) 19%),
          radial-gradient(circle at 50% 50%, #333 18%, rgba(0,0,0,0) 19%),
          radial-gradient(circle at 25% 75%, #333 18%, rgba(0,0,0,0) 19%),
          radial-gradient(circle at 75% 75%, #333 18%, rgba(0,0,0,0) 19%);
      }
      
      .pip-6 {
        background-image: 
          radial-gradient(circle at 28% 25%, #333 16%, rgba(0,0,0,0) 17%),
          radial-gradient(circle at 28% 50%, #333 16%, rgba(0,0,0,0) 17%),
          radial-gradient(circle at 28% 75%, #333 16%, rgba(0,0,0,0) 17%),
          radial-gradient(circle at 72% 25%, #333 16%, rgba(0,0,0,0) 17%),
          radial-gradient(circle at 72% 50%, #333 16%, rgba(0,0,0,0) 17%),
          radial-gradient(circle at 72% 75%, #333 16%, rgba(0,0,0,0) 17%);
      }
    }
    
    &.shaking .pip {
      animation: dice-shake 0.2s infinite;
    }
  }

  .sum-info {
    display: flex;
    flex-direction: column;
    align-items: center;
    background: rgba(255, 255, 255, 0.05);
    padding: 2px 10px;
    border-radius: 8px;
    
    span:first-child {
      font-size: 10px;
      color: rgba(255, 255, 255, 0.4);
    }
    
    .value {
      font-size: 18px;
      font-family: 'DIN Alternate', sans-serif;
      color: #00FF9A;
      font-weight: bold;
      text-shadow: 0 0 8px rgba(0, 255, 154, 0.3);
    }
  }
}

.row-bottom {
  border-top: 1px solid rgba(255, 255, 255, 0.05);
  padding-top: 8px;
  
  .countdown-label {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.5);
  }

  .countdown-number {
    font-family: 'DIN Alternate', sans-serif;
    font-size: 24px;
    font-weight: bold;
    color: #FF4757; 
    text-shadow: 0 0 10px rgba(255, 71, 87, 0.4);
    letter-spacing: 1px;
  }
}

@keyframes pulse {
  0% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.7; transform: scale(1.1); }
  100% { opacity: 1; transform: scale(1); }
}

@keyframes dice-shake {
  0% { transform: translate(1px, 1px) rotate(0deg); }
  10% { transform: translate(-1px, -2px) rotate(-1deg); }
  20% { transform: translate(-3px, 0px) rotate(1deg); }
  30% { transform: translate(3px, 2px) rotate(0deg); }
  40% { transform: translate(1px, -1px) rotate(1deg); }
  50% { transform: translate(-1px, 2px) rotate(-1deg); }
  60% { transform: translate(-3px, 1px) rotate(0deg); }
  70% { transform: translate(3px, 1px) rotate(-1deg); }
  80% { transform: translate(-1px, -1px) rotate(1deg); }
  90% { transform: translate(1px, 2px) rotate(0deg); }
  100% { transform: translate(1px, -2px) rotate(-1deg); }
}
</style>
