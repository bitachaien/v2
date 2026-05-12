<template>
  <div class="lottery-info">
    
    <div class="info-top">
      <div class="issue-info">
        <span class="label">第</span>
        <span class="highlight">{{ showExpect.currFullExpect }}</span>
        <span class="label">期</span>
      </div>
      <div class="countdown-info">
        <span class="label">截止</span>
        <van-count-down :time="gametimes.ms" format="HH:mm:ss" class="countdown" @finish="$emit('timeout')">
          <template #default="timeData">
            <span class="time-block">{{ timeData.hours < 10 ? '0' + timeData.hours : timeData.hours }}</span>
            <span class="colon">:</span>
            <span class="time-block">{{ timeData.minutes < 10 ? '0' + timeData.minutes : timeData.minutes }}</span>
            <span class="colon">:</span>
            <span class="time-block">{{ timeData.seconds < 10 ? '0' + timeData.seconds : timeData.seconds }}</span>
          </template>
        </van-count-down>
      </div>
    </div>

    
    <div class="info-bottom" @click="$emit('show-history')">
      <div class="last-issue-label">
        <span>第{{ showExpect.lastFullExpect }}期开奖</span>
      </div>
      <div class="lottery-balls">
        <template v-if="isDrawing">
          <div class="drawing-text">
            <van-icon name="fire-o" class="drawing-icon" />
            正在开奖中...
          </div>
        </template>
        <template v-else>
          <div class="ball-group">
            <div class="ball-wrapper" v-for="(num, index) in displayNumbers" :key="index">
              <div class="ball normal">{{ num }}</div>
            </div>
          </div>
        </template>
      </div>
      <div class="history-btn">
        <span>历史</span>
        <van-icon name="arrow" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { CountDown as VanCountDown, Icon as VanIcon } from 'vant'

const props = defineProps({
  showExpect: {
    type: Object,
    default: () => ({ currFullExpect: '---', lastFullExpect: '---' })
  },
  lastOpenCode: {
    type: Array,
    default: () => []
  },
  gametimes: {
    type: Object,
    default: () => ({ ms: 0 })
  },
  isDrawing: {
    type: Boolean,
    default: false
  }
})

defineEmits(['show-history', 'timeout'])

const displayNumbers = computed(() => {
  if (!props.lastOpenCode || props.lastOpenCode.length === 0) {

    return ['?', '?', '?', '?', '?', '?', '?']
  }
  return props.lastOpenCode
})
</script>

<style lang="less" scoped>
.lottery-info {
  background: linear-gradient(135deg, #ffffff 0%, #f8faff 100%);
  padding: 16px;
  border-radius: 0 0 16px 16px;
  box-shadow: 0 4px 16px rgba(0,0,0,0.04);
  position: relative;
  overflow: hidden;
  
  &::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 2px;
    background: linear-gradient(90deg, #ff6b6b, #4dabf7, #69db7c);
  }
}

.info-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;

  .issue-info {
    font-size: 14px;
    color: #333;
    display: flex;
    align-items: center;
    gap: 4px;
    
    .label {
      color: #666;
    }

    .highlight {
      color: #333;
      font-weight: 700;
      font-size: 16px;
      font-family: 'DIN Alternate', sans-serif;
    }
  }

  .countdown-info {
    display: flex;
    align-items: center;
    gap: 6px;
    
    .label {
      color: #999;
      font-size: 12px;
    }
  }
}

.countdown {
  display: flex;
  align-items: center;
  
  .time-block {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    background: #333;
    color: #fff;
    border-radius: 4px;
    font-size: 14px;
    font-weight: bold;
    font-family: 'DIN Alternate', sans-serif;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  }
  
  .colon {
    margin: 0 2px;
    color: #333;
    font-weight: bold;
    animation: blink 1s infinite;
  }
}

@keyframes blink {
  50% { opacity: 0; }
}

.info-bottom {
  background: #fff;
  border-radius: 12px;
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.02);
  border: 1px solid #f0f0f0;
  
  &:active {
    background: #f9f9f9;
  }
}

.last-issue-label {
  font-size: 12px;
  color: #999;
}

.lottery-balls {
  display: flex;
  justify-content: center;
  padding: 4px 0;
}

.ball-group {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
  justify-content: center;
}

.ball-wrapper {
  position: relative;
  
  &::after {
    content: '';
    position: absolute;
    bottom: -4px;
    left: 50%;
    transform: translateX(-50%);
    width: 80%;
    height: 4px;
    background: radial-gradient(ellipse at center, rgba(0,0,0,0.2) 0%, rgba(0,0,0,0) 70%);
    border-radius: 50%;
  }
}

.ball {
  width: 32px;
  height: 32px;
  line-height: 32px;
  text-align: center;
  border-radius: 50%;
  font-size: 16px;
  font-weight: bold;
  color: #e1251b;
  position: relative;
  background: #fff;
  box-shadow: 
    inset -2px -2px 6px rgba(0,0,0,0.1),
    inset 2px 2px 6px rgba(255,255,255,0.8);
  border: 1px solid #eee;
  
  &.normal {
    background: radial-gradient(circle at 30% 30%, #fff, #f5f5f5);
    text-shadow: 0 1px 0 #fff;
  }
}

.drawing-text {
  color: #e1251b;
  font-weight: bold;
  font-size: 16px;
  display: flex;
  align-items: center;
  gap: 6px;
  animation: pulse 1.5s infinite;
  
  .drawing-icon {
    font-size: 20px;
  }
}

.history-btn {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  font-size: 12px;
  color: #999;
  gap: 2px;
}

@keyframes pulse {
  0% { transform: scale(1); opacity: 1; }
  50% { transform: scale(1.02); opacity: 0.8; }
  100% { transform: scale(1); opacity: 1; }
}
</style>
