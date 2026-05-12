<template>
  <div class="kl8-info">
    
    <div class="header-card">
      
      <div class="bg-decoration"></div>
      <div class="bg-decoration-2"></div>
      
      
      <div class="header-content">
        
        <div class="expect-section">
          <div class="current-expect">
            <div class="expect-label">
              <span class="icon-dot"></span>
              <span>当前期</span>
            </div>
            <div class="expect-number">{{ showExpect.currFullExpect }}</div>
          </div>
          <div class="lottery-badge">
            <span class="badge-icon">🎱</span>
            <span class="badge-text">快乐8</span>
            <span class="badge-tag">官方</span>
          </div>
        </div>
        
        
        <div class="countdown-section">
          <div class="countdown-box" :class="{ 'is-drawing': isDrawing, 'is-urgent': isUrgent }">
            <template v-if="!isDrawing">
              <div class="countdown-header">
                <van-icon name="clock-o" class="clock-icon" />
                <span>距截止</span>
              </div>
              <div class="countdown-digits">
                <div class="digit-group">
                  <span class="digit">{{ formatTimeObj(gametimes.ms).m }}</span>
                  <span class="unit">分</span>
                </div>
                <span class="separator">:</span>
                <div class="digit-group">
                  <span class="digit">{{ formatTimeObj(gametimes.ms).s }}</span>
                  <span class="unit">秒</span>
                </div>
              </div>
            </template>
            <template v-else>
              <div class="drawing-state">
                <div class="drawing-animation">
                  <span class="dot"></span>
                  <span class="dot"></span>
                  <span class="dot"></span>
                </div>
                <span class="drawing-text">开奖中...</span>
              </div>
            </template>
          </div>
        </div>
      </div>
    </div>
    
    
    <div class="lottery-result-card">
      <div class="card-top">
        <div class="last-expect">
          第 <b>{{ showExpect.lastFullExpect }}</b> 期开奖结果
        </div>
        <div class="history-btn" @click="$emit('show-history')">
          历史 <van-icon name="arrow" />
        </div>
      </div>

      
      <div class="balls-area">
        <div v-if="lastOpenCode.length < 20" class="waiting-skeleton">
          <div class="skeleton-row" v-for="i in 2" :key="i">
            <div class="sk-ball" v-for="j in 10" :key="j"></div>
          </div>
          <div class="waiting-text">等待开奖...</div>
        </div>
        
        <div v-else class="ball-grid">
          
          <div class="grid-row">
            <div class="row-label blue">上</div>
            <div class="balls">
              <div 
                v-for="(num, index) in lastOpenCode.slice(0, 10)" 
                :key="`top-${index}`" 
                class="ball-wrapper"
              >
                <div class="ball is-blue">{{ num }}</div>
              </div>
            </div>
          </div>
          
          <div class="divider-line"></div>

          
          <div class="grid-row">
            <div class="row-label red">下</div>
            <div class="balls">
              <div 
                v-for="(num, index) in lastOpenCode.slice(10, 20)" 
                :key="`bottom-${index}`" 
                class="ball-wrapper"
              >
                <div class="ball is-red">{{ num }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      
      <div class="stats-dashboard" v-if="stats">
        <div class="stat-main">
          <div class="stat-label">和值</div>
          <div class="stat-val">{{ stats.totalSum }}</div>
        </div>
        <div class="stat-grid">
          <div class="stat-item" :class="getStatColor(stats.bigSmall)">{{ stats.bigSmall }}</div>
          <div class="stat-item" :class="getStatColor(stats.oddEven)">{{ stats.oddEven }}</div>
          <div class="stat-item" :class="getStatColor(stats.upDown)">{{ stats.upDown }}盘</div>
          <div class="stat-item" :class="getStatColor(stats.fiveElements)">{{ stats.fiveElements }}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

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

defineEmits(['show-history'])

const isUrgent = computed(() => {
  return props.gametimes.ms > 0 && props.gametimes.ms <= 60000
})

const formatTimeObj = (ms) => {
  if (ms <= 0) return { m: '00', s: '00' }
  const s = Math.floor(ms / 1000)
  const m = Math.floor(s / 60)
  const ss = s % 60
  return {
    m: m.toString().padStart(2, '0'),
    s: ss.toString().padStart(2, '0')
  }
}

const stats = computed(() => {
  if (!props.lastOpenCode || props.lastOpenCode.length < 20) return null
  
  const nums = props.lastOpenCode.map(Number)
  const sum = nums.reduce((a, b) => a + b, 0)
  

  const upCount = nums.filter(n => n <= 40).length
  const downCount = nums.filter(n => n >= 41).length
  
  let five = '金'
  if (sum >= 696 && sum <= 763) five = '木'
  else if (sum >= 764 && sum <= 855) five = '水'
  else if (sum >= 856 && sum <= 923) five = '火'
  else if (sum >= 924) five = '土'
  
  return {
    totalSum: sum,
    bigSmall: sum > 810 ? '大' : (sum < 810 ? '小' : '和'),
    oddEven: sum % 2 !== 0 ? '单' : '双',
    upDown: upCount > downCount ? '上' : (downCount > upCount ? '下' : '中'),
    fiveElements: five
  }
})

const getStatColor = (val) => {
  if (['大', '双', '下', '火', '土'].includes(val)) return 'is-red'
  if (['小', '单', '上', '木', '水'].includes(val)) return 'is-blue'
  return 'is-green' 
}
</script>

<style lang="less" scoped>
.kl8-info {
  position: relative;
  background: #f7f8fa;
  overflow: hidden;
  padding-bottom: 12px;
  z-index: 1;
}

.header-card {
  position: relative;
  z-index: 0;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
  margin: 0;
  padding: 16px;
  border-bottom-left-radius: 24px;
  border-bottom-right-radius: 24px;
  overflow: hidden;
  box-shadow: 0 8px 32px rgba(102, 126, 234, 0.35);
  
  .bg-decoration {
    position: absolute;
    top: -40px;
    right: -40px;
    width: 160px;
    height: 160px;
    background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, rgba(255,255,255,0) 70%);
    border-radius: 50%;
    z-index: 0;
  }
  
  .bg-decoration-2 {
    position: absolute;
    bottom: -60px;
    left: -30px;
    width: 120px;
    height: 120px;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
    border-radius: 50%;
    z-index: 0;
  }
  
  .header-content {
    position: relative;
    z-index: 1;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
}

.expect-section {
  .current-expect {
    margin-bottom: 8px;
    
    .expect-label {
      display: flex;
      align-items: center;
      gap: 4px;
      font-size: 11px;
      color: rgba(255,255,255,0.8);
      margin-bottom: 4px;
      
      .icon-dot {
        width: 6px;
        height: 6px;
        background: #4ade80;
        border-radius: 50%;
        animation: pulse-dot 2s infinite;
      }
    }
    
    .expect-number {
      font-size: 22px;
      font-weight: 800;
      color: #fff;
      font-family: 'DIN Alternate', sans-serif;
      letter-spacing: 1px;
      text-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
  }
  
  .lottery-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(255,255,255,0.2);
    backdrop-filter: blur(10px);
    padding: 4px 10px;
    border-radius: 20px;
    
    .badge-icon {
      font-size: 14px;
    }
    
    .badge-text {
      font-size: 12px;
      color: #fff;
      font-weight: 600;
    }
    
    .badge-tag {
      font-size: 9px;
      background: #ffd700;
      color: #333;
      padding: 1px 4px;
      border-radius: 4px;
      font-weight: 700;
    }
  }
}

.countdown-section {
  .countdown-box {
    background: rgba(255,255,255,0.95);
    border-radius: 16px;
    padding: 10px 14px;
    min-width: 110px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
    
    .countdown-header {
      display: flex;
      align-items: center;
      gap: 4px;
      font-size: 11px;
      color: #666;
      margin-bottom: 4px;
      
      .clock-icon {
        font-size: 12px;
        color: #667eea;
      }
    }
    
    .countdown-digits {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 2px;
      
      .digit-group {
        display: flex;
        align-items: baseline;
        gap: 1px;
        
        .digit {
          font-size: 24px;
          font-weight: 800;
          font-family: 'DIN Alternate', sans-serif;
          color: #333;
          line-height: 1;
        }
        
        .unit {
          font-size: 10px;
          color: #999;
          font-weight: 500;
        }
      }
      
      .separator {
        font-size: 20px;
        font-weight: 700;
        color: #667eea;
        margin: 0 2px;
        animation: blink 1s infinite;
      }
    }
    
    &.is-urgent {
      background: linear-gradient(135deg, #fff5f5 0%, #fff 100%);
      border: 1px solid rgba(255, 77, 79, 0.3);
      
      .countdown-digits .digit-group .digit {
        color: #ff4d4f;
      }
      .countdown-digits .separator {
        color: #ff4d4f;
      }
    }
    
    &.is-drawing {
      background: linear-gradient(135deg, #fff7e6 0%, #fff 100%);
      border: 1px solid rgba(250, 173, 20, 0.3);
    }
    
    .drawing-state {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 6px;
      padding: 4px 0;
      
      .drawing-animation {
        display: flex;
        gap: 4px;
        
        .dot {
          width: 8px;
          height: 8px;
          background: #faad14;
          border-radius: 50%;
          animation: bounce-dot 1.4s infinite ease-in-out;
          
          &:nth-child(1) { animation-delay: 0s; }
          &:nth-child(2) { animation-delay: 0.2s; }
          &:nth-child(3) { animation-delay: 0.4s; }
        }
      }
      
      .drawing-text {
        font-size: 12px;
        color: #faad14;
        font-weight: 600;
      }
    }
  }
}

@keyframes pulse-dot {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.5; transform: scale(0.8); }
}

@keyframes blink {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.3; }
}

@keyframes bounce-dot {
  0%, 80%, 100% { transform: translateY(0); }
  40% { transform: translateY(-8px); }
}

.lottery-result-card {
  position: relative;
  margin: 0 12px;
  background: #f8f9fa;
  border-radius: 16px;
  padding: 12px;
  z-index: 1;
  border: 1px solid rgba(0,0,0,0.02);
  
  .card-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    font-size: 12px;
    
    .last-expect {
      color: #666;
      b { color: #333; font-weight: 600; font-family: 'DIN Alternate'; margin: 0 2px; font-size: 13px; }
    }
    
    .history-btn {
      color: #999;
      display: flex;
      align-items: center;
      gap: 2px;
      font-size: 11px;
    }
  }
}

.balls-area {
  margin-bottom: 12px;
  
  .waiting-skeleton {
    background: #fff;
    border-radius: 10px;
    padding: 15px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    
    .skeleton-row {
      display: flex;
      gap: 6px;
      .sk-ball {
        width: 24px; height: 24px;
        border-radius: 50%;
        background: #f0f0f0;
        animation: pulse 1.5s infinite ease-in-out;
      }
    }
    .waiting-text { color: #999; font-size: 12px; margin-top: 5px; }
  }
  
  .ball-grid {
    display: flex;
    flex-direction: column;
    gap: 8px;
    background: #fff;
    border-radius: 12px;
    padding: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.02);
    
    .divider-line {
      height: 1px;
      background: #f5f5f5;
      margin: 0 10px;
    }
    
    .grid-row {
      display: flex;
      align-items: center;
      gap: 8px;
      
      .row-label {
        width: 20px;
        height: 20px;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        color: #fff;
        font-weight: 600;
        flex-shrink: 0;
        
        &.blue { background: #4dabf7; }
        &.red { background: #ff6b6b; }
      }
      
      .balls {
        flex: 1;
        display: grid;
        grid-template-columns: repeat(10, 1fr);
        gap: 4px;
        
        .ball-wrapper {
          display: flex;
          justify-content: center;
        }
        
        .ball {
          width: 24px;
          height: 24px;
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 12px;
          font-family: 'DIN Alternate';
          font-weight: 700;
          position: relative;
          box-shadow: 0 2px 4px rgba(0,0,0,0.1);
          
          
          &::before {
            content: '';
            position: absolute;
            top: 1px; left: 50%; transform: translateX(-50%);
            width: 16px; height: 8px;
            background: linear-gradient(180deg, rgba(255,255,255,0.6) 0%, rgba(255,255,255,0) 100%);
            border-radius: 50%;
          }

          &.is-blue {
            background: radial-gradient(circle at 30% 30%, #74c0fc, #1c7ed6);
            color: #fff;
            text-shadow: 0 1px 1px rgba(0,0,0,0.1);
          }
          
          &.is-red {
            background: radial-gradient(circle at 30% 30%, #ff8787, #e03131);
            color: #fff;
            text-shadow: 0 1px 1px rgba(0,0,0,0.1);
          }
        }
      }
    }
  }
}

.stats-dashboard {
  display: flex;
  align-items: center;
  gap: 10px;
  
  .stat-main {
    background: #fff;
    padding: 6px 12px;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    align-items: center;
    min-width: 60px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.03);
    
    .stat-label { font-size: 10px; color: #999; }
    .stat-val { 
      font-size: 16px; font-weight: 700; color: #333; 
      font-family: 'DIN Alternate';
      line-height: 1.2;
    }
  }
  
  .stat-grid {
    flex: 1;
    display: flex;
    justify-content: space-between;
    background: #fff;
    padding: 8px;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.03);
    
    .stat-item {
      font-size: 12px;
      font-weight: 600;
      padding: 2px 8px;
      border-radius: 6px;
      
      &.is-red { color: #e03131; background: rgba(255, 241, 240, 1); }
      &.is-blue { color: #1971c2; background: rgba(230, 247, 255, 1); }
      &.is-green { color: #099268; background: rgba(246, 255, 237, 1); }
    }
  }
}

@keyframes pulse {
  0% { opacity: 1; }
  50% { opacity: 0.5; }
  100% { opacity: 1; }
}
</style>
