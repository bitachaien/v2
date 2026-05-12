<template>
  <div class="last-result-wrapper" :class="{ expanded: isExpanded }">
    
    <div class="last-result">
      <span class="result-label">第 <span class="result-issue">{{ lastIssue }}</span>期开奖</span>
      <div class="result-balls">
        <span class="ball ball-1">{{ lastCode[0] }}</span>
        <span class="plus">+</span>
        <span class="ball ball-2">{{ lastCode[1] }}</span>
        <span class="plus">+</span>
        <span class="ball ball-3">{{ lastCode[2] }}</span>
        <span class="equal">=</span>
        <span class="ball ball-sum" :class="sumColorClass">{{ lastSum }}</span>
      </div>
      <span class="result-type">{{ lastResultType }}</span>
      <van-icon name="play" class="expand-icon" :class="{ expanded: isExpanded }" @click="toggleExpand" />
    </div>
    
    
    <transition name="slide-down">
      <div class="history-dropdown" v-if="isExpanded">
        
        <div class="history-header">
          <span>期号</span>
          <span>开奖时间</span>
          <span>开奖结果</span>
        </div>
        
        
        <div class="history-scroll-content">
          <div class="history-item" v-for="item in historyList" :key="item.issue">
            <span class="history-issue">{{ item.issue }}</span>
            <span class="history-time">{{ item.time || '--' }}</span>
            <div class="history-result">
              <div class="history-balls">
                <span class="ball">{{ item.code[0] }}</span>
                <span class="plus">+</span>
                <span class="ball">{{ item.code[1] }}</span>
                <span class="plus">+</span>
                <span class="ball">{{ item.code[2] }}</span>
                <span class="equal">=</span>
                <span class="ball sum" :class="getSumColor(item.sum)">{{ item.sum }}</span>
              </div>
              <span class="history-type">{{ item.type }}</span>
            </div>
          </div>
        </div>
        
        
        <div class="history-more" @click="$emit('more')">
          <van-icon name="search" />
          <span>查看更多开奖记录</span>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  lastIssue: String,
  lastCode: {
    type: Array,
    default: () => [0, 0, 0]
  },
  historyList: {
    type: Array,
    default: () => []
  },
  isExpanded: Boolean
})

const emit = defineEmits(['update:isExpanded', 'more'])

const lastSum = computed(() => props.lastCode.reduce((a, b) => a + b, 0))

const lastResultType = computed(() => {
  const sum = lastSum.value
  if (isNaN(sum)) return ''
  let text = ''
  text += sum >= 14 ? '大' : '小'
  text += sum % 2 === 0 ? '双' : '单'
  return text
})

const redNums = [3, 6, 9, 12, 15, 18, 21, 24]
const greenNums = [1, 4, 7, 10, 16, 19, 22, 25]
const blueNums = [2, 5, 8, 11, 13, 14, 17, 20, 23, 26, 27]

function getSumColor(sum) {
  if (redNums.includes(sum)) return 'sum-red'
  if (greenNums.includes(sum)) return 'sum-green'
  if (blueNums.includes(sum)) return 'sum-blue'
  return 'sum-red'
}

const sumColorClass = computed(() => getSumColor(lastSum.value))

function toggleExpand() {
  emit('update:isExpanded', !props.isExpanded)
}
</script>

<style lang="less" scoped>
.last-result-wrapper {
  position: relative;
  background: #fff;
  z-index: 12;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  
  &.expanded {
    box-shadow: none;
    border-bottom: 1px solid #f0f0f0;
  }
}

.last-result {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 12px 16px;
  background: #fff;
  cursor: pointer;
  user-select: none;
  transition: all 0.3s ease;
  
  .result-label {
    font-size: 13px;
    color: #666;
    flex-shrink: 0;
    
    .result-issue {
      color: #5691fe;
      font-weight: 500;
    }
  }
  
  .result-balls {
    display: flex;
    align-items: center;
    gap: 6px;
    flex: 1;
    justify-content: center;
    
    .ball {
      width: 26px;
      height: 26px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 13px;
      font-weight: 600;
      color: #fff;
      background: #5691fe;
      font-family: Din, 'DIN Alternate', monospace;
    }
    
    .ball-sum {
      background: #eb5358;
      
      &.sum-red { background: #f5222d; }
      &.sum-green { background: #52c41a; }
      &.sum-blue { background: #1890ff; }
    }
    
    .plus, .equal {
      font-size: 14px;
      color: #999;
      font-weight: 500;
    }
  }
  
  .result-type {
    font-size: 13px;
    font-weight: 600;
    color: #333;
  }
  
  .expand-icon {
    color: #999;
    font-size: 16px;
    transition: transform 0.3s ease;
    transform: rotate(90deg);
    cursor: pointer;
    padding: 4px;
    
    &.expanded {
      transform: rotate(-90deg);
    }
  }
}

.history-dropdown {
  position: absolute;
  left: 0;
  right: 0;
  top: 100%;
  z-index: 11;
  background: #fff;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  display: flex;
  flex-direction: column;
  
  .history-header {
    display: flex;
    align-items: center;
    padding: 10px 0;
    background: #f0f2f5;
    font-size: 13px;
    color: #666;
    flex-shrink: 0;
    
    span {
      flex: 1;
      text-align: center;
    }
  }
  
  .history-scroll-content {
    max-height: 276px;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    
    .history-item {
      display: flex;
      align-items: center;
      padding: 13px 0;
      font-size: 14px;
      
      &:nth-child(even) {
        background: #f7f8fa;
      }
      
      .history-issue {
        flex: 1;
        text-align: center;
        color: #1989fa;
        font-family: Din;
        font-size: 14px;
      }
      
      .history-time {
        flex: 1;
        text-align: center;
        color: #333;
        font-size: 13px;
      }
      
      .history-result {
        flex: 1.4;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        
        .history-balls {
          display: flex;
          align-items: center;
          gap: 3px;
          
          .ball {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: #fff;
            background: #0091ff;
            font-family: Din;
            
            &.sum {
              background: #f5222d;
              
              &.sum-red { background: #f5222d; }
              &.sum-green { background: #52c41a; }
              &.sum-blue { background: #1890ff; }
            }
          }
          
          .plus, .equal {
            font-size: 12px;
            color: #999;
            transform: scale(0.9);
          }
        }
        
        .history-type {
          font-size: 13px;
          color: #666;
          margin-left: 2px;
        }
      }
    }
  }
  
  .history-more {
    padding: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    font-size: 14px;
    color: #5691fe;
    cursor: pointer;
    background: #fff;
    border-top: 1px solid #f0f0f0;
    flex-shrink: 0;
    
    &:active {
      background: #f5f5f5;
    }
  }
}

.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.3s ease;
  max-height: 500px;
  opacity: 1;
  overflow: hidden;
}

.slide-down-enter-from,
.slide-down-leave-to {
  max-height: 0;
  opacity: 0;
}
</style>
