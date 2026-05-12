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
      <div class="draw-header">
        <div class="last-issue-label">
          第 <span class="issue-num">{{ showExpect.lastFullExpect }}</span> 期开奖
        </div>
        <div class="history-btn">
          <span>历史</span>
          <van-icon name="arrow" />
        </div>
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
              <div class="ball pl3-ball">{{ num }}</div>
            </div>
          </div>
        </template>
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
  if (!props.lastOpenCode || props.lastOpenCode.length < 3) {
    return ['?', '?', '?']
  }
  return props.lastOpenCode.slice(0, 3)
})
</script>

<style lang="less" scoped>
.lottery-info {
  background: #fff;
  padding: 16px;
  border-radius: 0 0 20px 20px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.05);
  position: relative;
  z-index: 1;
}

.info-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding-bottom: 16px;
  border-bottom: 1px solid #f5f7fa;

  .issue-info {
    font-size: 14px;
    color: #333;
    display: flex;
    align-items: center;
    gap: 6px;
    
    .label {
      color: #999;
      font-size: 13px;
    }

    .highlight {
      color: #1890ff;
      font-weight: 700;
      font-size: 17px;
      font-family: 'DIN Alternate', sans-serif;
      letter-spacing: 0.5px;
    }
  }

  .countdown-info {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #f0f9ff;
    padding: 4px 10px;
    border-radius: 20px;
    
    .label {
      color: #1890ff;
      font-size: 12px;
      font-weight: 500;
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
    width: 22px;
    height: 22px;
    background: #1890ff;
    color: #fff;
    border-radius: 4px;
    font-size: 13px;
    font-weight: bold;
    font-family: 'DIN Alternate', sans-serif;
  }
  
  .colon {
    margin: 0 2px;
    color: #1890ff;
    font-weight: bold;
    animation: blink 1s infinite;
  }
}

@keyframes blink {
  50% { opacity: 0.5; }
}

.info-bottom {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.draw-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  
  .last-issue-label {
    font-size: 13px;
    color: #666;
    
    .issue-num {
      color: #333;
      font-weight: 600;
      margin: 0 2px;
    }
  }
  
  .history-btn {
    display: flex;
    align-items: center;
    font-size: 12px;
    color: #999;
    gap: 2px;
    padding: 2px 8px;
    background: #f5f7fa;
    border-radius: 12px;
    
    &:active {
      opacity: 0.7;
    }
  }
}

.lottery-balls {
  display: flex;
  justify-content: center;
  padding: 10px 0;
  background: #f8faff;
  border-radius: 12px;
  border: 1px dashed #e6f7ff;
}

.ball-group {
  display: flex;
  align-items: center;
  gap: 24px;
}

.ball-wrapper {
  position: relative;
}

.ball {
  width: 48px;
  height: 48px;
  line-height: 48px;
  text-align: center;
  border-radius: 50%;
  font-size: 24px;
  font-weight: bold;
  position: relative;
  
  &.pl3-ball {
    background: linear-gradient(135deg, #ffffff 0%, #f0f2f5 100%);
    color: #1890ff;
    border: 2px solid #1890ff;
    box-shadow: 
      0 4px 10px rgba(24, 144, 255, 0.2),
      inset 0 2px 4px rgba(255,255,255,1);
      
    text-shadow: 0 1px 2px rgba(0,0,0,0.1);
  }
}

.drawing-text {
  color: #1890ff;
  font-weight: bold;
  font-size: 16px;
  display: flex;
  align-items: center;
  gap: 8px;
  animation: pulse 1.5s infinite;
  
  .drawing-icon {
    font-size: 20px;
  }
}

@keyframes pulse {
  0% { transform: scale(1); opacity: 1; }
  50% { transform: scale(1.02); opacity: 0.8; }
  100% { transform: scale(1); opacity: 1; }
}
</style>
