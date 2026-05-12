<template>
  <div class="lottery-info">
    
    <div class="info-top">
      <div class="issue-info">
        <span>第 <span class="highlight">{{ showExpect.currFullExpect }}</span> 期</span>
      </div>
      <div class="countdown-info">
        <span class="label">截止时间</span>
        <van-count-down :time="gametimes.ms" format="HH:mm:ss" class="countdown" @finish="onFinish">
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
      <div class="last-issue">
        <span>第 {{ showExpect.lastFullExpect }} 期</span>
      </div>
      <div class="lottery-balls">
        <template v-if="isDrawing">
          <div class="drawing-text">正在开奖中...</div>
        </template>
        <template v-else>
          
          <div 
            v-for="(num, index) in normalNumbers" 
            :key="'norm-'+index"
            class="ball-wrapper"
          >
            <div class="ball" :class="getColorClass(num)">
              {{ num }}
            </div>
            <div class="shengxiao">{{ getShengXiaoLocal(num) }}</div>
          </div>

          
          <div class="plus-sign">+</div>

          
          <div class="ball-wrapper special-wrapper">
            <div class="ball special" :class="getColorClass(specialNumber)">
              {{ specialNumber }}
            </div>
            <div class="shengxiao">{{ getShengXiaoLocal(specialNumber) }}</div>
          </div>
        </template>
      </div>
      <van-icon name="arrow" class="arrow-icon" />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { CountDown as VanCountDown, Icon as VanIcon } from 'vant'
import { getNumberColorClass, getShengXiao } from '@/utils/lhcData'

const props = defineProps({
  showExpect: {
    type: Object,
    required: true,
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

const emit = defineEmits(['show-history', 'timeout'])

const normalNumbers = computed(() => {
  if (!props.lastOpenCode || props.lastOpenCode.length < 6) {
    return ['?', '?', '?', '?', '?', '?']
  }
  return props.lastOpenCode.slice(0, 6)
})

const specialNumber = computed(() => {
  if (!props.lastOpenCode || props.lastOpenCode.length < 7) {
    return '?'
  }
  return props.lastOpenCode[6]
})

const getColorClass = (num) => {
  if (num === '?') return ''
  const n = parseInt(num)
  return getNumberColorClass(n)
}

const getShengXiaoLocal = (num) => {
  if (num === '?') return '-'
  return getShengXiao(num)
}

const onFinish = () => {
  emit('timeout')
}
</script>

<style lang="less" scoped>
.lottery-info {
  background: #fff;
  padding: 12px;
  border-bottom: 1px solid #eee;
  box-shadow: 0 2px 8px rgba(0,0,0,0.02);
}

.info-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
  font-size: 14px;
  color: #333;

  .issue-info {
    font-weight: 500;
  }

  .highlight {
    color: #e1251b;
    font-weight: bold;
    font-family: Monaco, monospace;
  }

  .countdown-info {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #f7f7f7;
    padding: 4px 8px;
    border-radius: 12px;
    
    .label {
      color: #666;
      font-size: 12px;
    }
  }
}

.countdown {
  display: flex;
  align-items: center;
  
  .time-block {
    display: inline-block;
    min-width: 22px;
    height: 22px;
    line-height: 22px;
    text-align: center;
    background-color: #333; 
    color: #fff;
    border-radius: 4px;
    font-size: 13px;
    font-weight: bold;
  }
  
  .colon {
    margin: 0 2px;
    color: #333;
    font-weight: bold;
  }
}

.info-bottom {
  display: flex;
  align-items: flex-start; 
  cursor: pointer;
  padding-top: 4px;
}

.last-issue {
  font-size: 12px;
  color: #999;
  margin-right: 8px;
  white-space: nowrap;
  margin-top: 8px; 
}

.lottery-balls {
  flex: 1;
  display: flex;
  align-items: flex-start;
  justify-content: flex-start;
  flex-wrap: nowrap;
  gap: 2px;
  overflow-x: auto;
}

.ball-wrapper {
  display: flex;
  flex-direction: column;
  align-items: center;
  flex-shrink: 0;
}

.ball {
  width: 28px;
  height: 28px;
  line-height: 28px;
  text-align: center;
  border-radius: 50%;
  background-color: #e0e0e0;
  color: #fff;
  font-size: 15px;
  font-weight: bold;
  box-shadow: inset 0 -2px 4px rgba(0,0,0,0.2), 0 2px 4px rgba(0,0,0,0.15);
  text-shadow: 0 1px 1px rgba(0,0,0,0.3);
  
  &.ssc-fang-hong {
    background: radial-gradient(circle at 35% 35%, #ff6b6b, #e1251b);
  }
  
  &.ssc-fang-lan {
    background: radial-gradient(circle at 35% 35%, #4dabf7, #1971c2);
  }
  
  &.ssc-fang-lv {
    background: radial-gradient(circle at 35% 35%, #69db7c, #2f9e44);
  }
}

.plus-sign {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 20px;
  height: 32px;
  font-size: 20px;
  color: #999;
  font-weight: 300;
  margin: 0 2px;
}

.special-wrapper {
  position: relative;
  
  &::after {
    content: '特';
    position: absolute;
    top: -6px;
    right: -4px;
    font-size: 10px;
    background: #fbbf24;
    color: #fff;
    padding: 1px 3px;
    border-radius: 4px;
    line-height: 1;
    transform: scale(0.8);
  }
}

.shengxiao {
  font-size: 12px;
  color: #666;
  margin-top: 2px;
  height: 16px;
  line-height: 16px;
}

.drawing-text {
  color: #e1251b;
  font-weight: bold;
  font-size: 16px;
  padding: 6px 0;
  animation: pulse 1.5s infinite;
}

@keyframes pulse {
  0% { opacity: 1; }
  50% { opacity: 0.5; }
  100% { opacity: 1; }
}

.arrow-icon {
  color: #ccc;
  margin-left: auto;
  margin-top: 8px;
}
</style>
