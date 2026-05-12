<template>
  <div class="kl10-info">
    
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
            <span class="badge-icon">⏱️</span>
            <span class="badge-text">快乐十分</span>
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
        <div v-if="lastOpenCode.length < 8" class="waiting-skeleton">
          <div class="skeleton-row">
            <div class="sk-ball" v-for="j in 8" :key="j"></div>
          </div>
          <div class="waiting-text">等待开奖...</div>
        </div>
        
        <div v-else class="ball-list">
          <div 
            v-for="(num, index) in lastOpenCode.slice(0, 8)" 
            :key="index" 
            class="ball-item"
            :class="getBallColor(num)"
          >
            {{ num }}
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
          <div class="stat-item" :class="getStatColor(stats.dragonTiger)">{{ stats.dragonTiger }}</div>
          <div class="stat-item" :class="getStatColor(stats.tailBigSmall)">尾{{ stats.tailBigSmall }}</div>
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

const getBallColor = (num) => {
  const n = parseInt(num)
  if (n === 19 || n === 20) return 'is-red'
  return 'is-blue'
}

const stats = computed(() => {
  if (!props.lastOpenCode || props.lastOpenCode.length < 8) return null
  
  const nums = props.lastOpenCode.map(Number)
  const sum = nums.reduce((a, b) => a + b, 0)
  

  const dragonTiger = nums[0] > nums[7] ? '龙' : '虎'
  

  const tail = sum % 10
  const tailBigSmall = tail >= 5 ? '大' : '小'

  return {
    totalSum: sum,
    bigSmall: sum > 84 ? '大' : (sum < 84 ? '小' : '和'), // 20选8 和值中值84
    oddEven: sum % 2 !== 0 ? '单' : '双',
    dragonTiger,
    tailBigSmall
  }
})

const getStatColor = (val) => {
  if (['大', '双', '龙'].includes(val)) return 'is-red'
  if (['小', '单', '虎'].includes(val)) return 'is-blue'
  return 'is-green' 
}
</script>

<style lang="less" scoped>
.kl10-info {
  position: relative;
  background: #f7f8fa;
  overflow: hidden;
  padding-bottom: 12px;
  z-index: 1;
}

.header-card {
  position: relative;
  z-index: 0;

  background: #fff;
  margin: 0;
  padding: 16px;
  border-bottom-left-radius: 24px;
  border-bottom-right-radius: 24px;
  overflow: hidden;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  
  .bg-decoration,
  .bg-decoration-2 {
    display: none;
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
      color: #666;
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
      color: #333;
      font-family: 'DIN Alternate', sans-serif;
      letter-spacing: 1px;
    }
  }
  
  .lottery-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #f5f5f5;
    padding: 4px 10px;
    border-radius: 20px;
    
    .badge-icon { font-size: 14px; }
    .badge-text { font-size: 12px; color: #333; font-weight: 600; }
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
      .clock-icon { font-size: 12px; color: #2563eb; }
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
        .digit { font-size: 24px; font-weight: 800; font-family: 'DIN Alternate'; color: #333; line-height: 1; }
        .unit { font-size: 10px; color: #999; font-weight: 500; }
      }
      .separator { font-size: 20px; font-weight: 700; color: #2563eb; margin: 0 2px; animation: blink 1s infinite; }
    }
    
    &.is-urgent {
      background: linear-gradient(135deg, #fff5f5 0%, #fff 100%);
      border: 1px solid rgba(255, 77, 79, 0.3);
      .countdown-digits .digit-group .digit, .countdown-digits .separator { color: #ff4d4f; }
    }
    
    &.is-drawing {
      background: linear-gradient(135deg, #fff7e6 0%, #fff 100%);
      border: 1px solid rgba(250, 173, 20, 0.3);
    }
    
    .drawing-state {
      display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 4px 0;
      .drawing-animation {
        display: flex; gap: 4px;
        .dot { width: 8px; height: 8px; background: #faad14; border-radius: 50%; animation: bounce-dot 1.4s infinite ease-in-out; }
      }
      .drawing-text { font-size: 12px; color: #faad14; font-weight: 600; }
    }
  }
}

@keyframes pulse-dot { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.5; transform: scale(0.8); } }
@keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }
@keyframes bounce-dot { 0%, 80%, 100% { transform: translateY(0); } 40% { transform: translateY(-8px); } }

.lottery-result-card {
  position: relative;
  margin: 0 12px;
  background: #f8f9fa;
  border-radius: 16px;
  padding: 12px;
  z-index: 1;
  border: 1px solid rgba(0,0,0,0.02);
  
  .card-top {
    display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; font-size: 12px;
    .last-expect { color: #666; b { color: #333; font-weight: 600; font-family: 'DIN Alternate'; margin: 0 2px; font-size: 13px; } }
    .history-btn { color: #999; display: flex; align-items: center; gap: 2px; font-size: 11px; }
  }
}

.balls-area {
  margin-bottom: 12px;
  
  .waiting-skeleton {
    background: #fff; border-radius: 10px; padding: 15px; display: flex; flex-direction: column; align-items: center; gap: 10px;
    .skeleton-row { display: flex; gap: 6px; .sk-ball { width: 24px; height: 24px; border-radius: 50%; background: #f0f0f0; animation: pulse 1.5s infinite ease-in-out; } }
    .waiting-text { color: #999; font-size: 12px; }
  }
  
  .ball-list {
    display: flex; justify-content: center; gap: 6px; background: #fff; border-radius: 12px; padding: 12px 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.02); flex-wrap: wrap;
    
    .ball-item {
      width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
      font-size: 15px; font-family: 'DIN Alternate'; font-weight: 700; position: relative; box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      
      &::before {
        content: ''; position: absolute; top: 1px; left: 50%; transform: translateX(-50%);
        width: 20px; height: 10px; background: linear-gradient(180deg, rgba(255,255,255,0.6) 0%, rgba(255,255,255,0) 100%);
        border-radius: 50%;
      }
      
      &.is-blue { background: radial-gradient(circle at 30% 30%, #74c0fc, #1c7ed6); color: #fff; text-shadow: 0 1px 1px rgba(0,0,0,0.1); }
      &.is-red { background: radial-gradient(circle at 30% 30%, #ff8787, #e03131); color: #fff; text-shadow: 0 1px 1px rgba(0,0,0,0.1); }
    }
  }
}

.stats-dashboard {
  display: flex; align-items: center; gap: 10px;
  
  .stat-main {
    background: #fff; padding: 6px 12px; border-radius: 8px; display: flex; flex-direction: column; align-items: center; min-width: 60px; box-shadow: 0 2px 6px rgba(0,0,0,0.03);
    .stat-label { font-size: 10px; color: #999; }
    .stat-val { font-size: 16px; font-weight: 700; color: #333; font-family: 'DIN Alternate'; line-height: 1.2; }
  }
  
  .stat-grid {
    flex: 1; display: flex; justify-content: space-between; background: #fff; padding: 8px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.03);
    .stat-item {
      font-size: 12px; font-weight: 600; padding: 2px 8px; border-radius: 6px;
      &.is-red { color: #e03131; background: rgba(255, 241, 240, 1); }
      &.is-blue { color: #1971c2; background: rgba(230, 247, 255, 1); }
      &.is-green { color: #099268; background: rgba(246, 255, 237, 1); }
    }
  }
}

@keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }
</style>
