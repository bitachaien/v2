<template>
  <div class="panel-standard">
    
    <div class="play-types-wrapper">
      <div class="play-types">
        <div 
          class="play-item" 
          v-for="play in playTypes" 
          :key="play.id"
          :class="{ active: currentPlay.id === play.id }"
          @click="switchPlay(play)"
        >
          <div class="name">{{ play.name }}</div>
          <div class="odds">{{ play.odds }}</div>
        </div>
      </div>
    </div>

    
    <div class="play-instruction">
      <div class="icon"><van-icon name="info-o" /></div>
      <div class="text">
        <span class="highlight">{{ currentPlay.name }}</span>：
        {{ currentPlay.desc }}
      </div>
    </div>

    
    <div class="number-area">
      
      <div class="section-group top-section">
        <div class="section-header">
          <div class="left">
            <span class="title">上盘 (01-40)</span>
            <span class="badge">小数</span>
          </div>
          <div class="right-op" @click="clear" v-if="selectedNums.size > 0">
             <van-icon name="delete-o" /> 清空
          </div>
        </div>
        <div class="number-grid">
          <div 
            class="ball-wrapper" 
            v-for="n in 40" 
            :key="n"
            @click="toggleNum(n)"
          >
            <div class="ball-item blue" :class="{ active: selectedNums.has(n) }">
              <div class="num">{{ n }}</div>
              <transition name="scale-fade">
                <div class="check-mark" v-if="selectedNums.has(n)"><van-icon name="success" /></div>
              </transition>
            </div>
            <div class="miss-text" :class="{ 'is-hot': getMissValue(n) > 10, 'is-cold': getMissValue(n) <= 3 }">
              {{ getMissValue(n) }}
            </div>
          </div>
        </div>
      </div>

      
      <div class="section-divider">
        <div class="line"></div>
        <div class="divider-tag">
          <span>快乐8</span>
          <span class="sub">80选20</span>
        </div>
        <div class="line"></div>
      </div>

      
      <div class="section-group bottom-section">
        <div class="section-header">
          <div class="left">
            <span class="title">下盘 (41-80)</span>
            <span class="badge red">大数</span>
          </div>
        </div>
        <div class="number-grid">
          <div 
            class="ball-wrapper" 
            v-for="n in 40" 
            :key="n + 40"
            @click="toggleNum(n + 40)"
          >
            <div class="ball-item red" :class="{ active: selectedNums.has(n + 40) }">
              <div class="num">{{ n + 40 }}</div>
              <transition name="scale-fade">
                <div class="check-mark" v-if="selectedNums.has(n + 40)"><van-icon name="success" /></div>
              </transition>
            </div>
            <div class="miss-text" :class="{ 'is-hot': getMissValue(n + 40) > 10, 'is-cold': getMissValue(n + 40) <= 3 }">
              {{ getMissValue(n + 40) }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { showToast } from 'vant'

const emit = defineEmits(['update:betCount', 'update:selectedData', 'clear'])

const playTypes = [
  { id: 'rx1', name: '任选一', min: 1, odds: '4.6', desc: '至少选1个号，猜中1个号即中奖' },
  { id: 'rx2', name: '任选二', min: 2, odds: '19', desc: '至少选2个号，猜中2个号即中奖' },
  { id: 'rx3', name: '任选三', min: 3, odds: '53', desc: '至少选3个号，猜中3个号即中奖' },
  { id: 'rx4', name: '任选四', min: 4, odds: '113', desc: '至少选4个号，猜中4个号即中奖' },
  { id: 'rx5', name: '任选五', min: 5, odds: '260', desc: '至少选5个号，猜中5个号即中奖' },
  { id: 'rx6', name: '任选六', min: 6, odds: '90', desc: '至少选6个号，猜中6个号即中奖' },
  { id: 'rx7', name: '任选七', min: 7, odds: '26', desc: '至少选7个号，猜中7个号即中奖' },
  { id: 'rx8', name: '任选八', min: 8, odds: '9', desc: '至少选8个号，猜中8个号即中奖' },
  { id: 'rx9', name: '任选九', min: 9, odds: '4.8', desc: '至少选9个号，猜中9个号即中奖' },
  { id: 'rx10', name: '任选十', min: 10, odds: '3.8', desc: '至少选10个号，猜中10个号即中奖' }
]

const currentPlay = ref(playTypes[0])
const selectedNums = ref(new Set())

const getMissValue = (n) => {
  return Math.floor(Math.random() * 20)
}

function switchPlay(play) {
  if (currentPlay.value.id === play.id) return
  currentPlay.value = play
  selectedNums.value.clear()
  updateParent()
}

function toggleNum(n) {
  if (selectedNums.value.has(n)) {
    selectedNums.value.delete(n)
  } else {
    if (selectedNums.value.size >= 80) {
      showToast('最多选择80个号码')
      return
    }
    selectedNums.value.add(n)
  }
  updateParent()
}

function clear() {
  selectedNums.value.clear()
  updateParent()
}

function randomSelect(count) {

  const results = []
  
  for (let c = 0; c < count; c++) {
    const need = currentPlay.value.min
    const pool = Array.from({ length: 80 }, (_, i) => i + 1)
    const nums = []
    
    for (let i = 0; i < need; i++) {
      const idx = Math.floor(Math.random() * pool.length)
      nums.push(pool[idx])
      pool.splice(idx, 1)
    }
    nums.sort((a, b) => a - b)
    
    results.push({
      playId: currentPlay.value.id,
      playName: currentPlay.value.name,
      numbers: nums,
      tzcode: nums.join(','),
      betCount: 1, // 单式1注
      money: 2, // 默认2元
      multiple: 1
    })
  }
  
  return results
}

function combination(n, m) {
  if (m > n) return 0
  if (m === n) return 1
  let res = 1
  for (let i = 0; i < m; i++) {
    res = res * (n - i) / (i + 1)
  }
  return Math.round(res)
}

function updateParent() {
  const nums = Array.from(selectedNums.value).sort((a, b) => a - b)
  const count = nums.length
  const min = currentPlay.value.min
  
  let betCount = 0
  if (count >= min) {
    betCount = combination(count, min)
  }
  
  emit('update:betCount', betCount)
  emit('update:selectedData', {
    playId: currentPlay.value.id,
    playName: currentPlay.value.name,
    numbers: nums,
    tzcode: nums.join(',')
  })
}

defineExpose({ clear, randomSelect })
</script>

<style lang="less" scoped>
.panel-standard {
  background: #fff;
  padding-bottom: 20px;
}

.play-types-wrapper {
  position: sticky;
  top: 86px; 
  z-index: 10;
  background: #fff;
  padding: 10px 0;
  box-shadow: 0 2px 6px rgba(0,0,0,0.02);
}

.play-types {
  display: flex;
  overflow-x: auto;
  gap: 12px;
  padding: 0 16px;
  -webkit-overflow-scrolling: touch;
  
  &::-webkit-scrollbar { display: none; }
  
  .play-item {
    flex: 0 0 auto;
    min-width: 70px;
    height: 48px;
    background: #f5f7fa;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    border: 1px solid transparent;
    
    .name { font-size: 13px; color: #666; font-weight: 500; }
    .odds { font-size: 10px; color: #999; margin-top: 2px; }
    
    &.active {
      background: #e6f7ff;
      border-color: #1890ff;
      box-shadow: 0 4px 12px rgba(24, 144, 255, 0.15);
      transform: translateY(-1px);
      
      .name { color: #1890ff; font-weight: 700; }
      .odds { color: #1890ff; opacity: 0.8; }
    }
  }
}

.play-instruction {
  margin: 10px 16px;
  padding: 10px 12px;
  background: #fff9c4; 
  border-radius: 6px;
  display: flex;
  gap: 8px;
  font-size: 12px;
  color: #666;
  line-height: 1.5;
  
  .icon { color: #f57f17; font-size: 14px; margin-top: 1px; }
  .highlight { color: #333; font-weight: 600; }
}

.number-area {
  padding: 0 12px;
  
  .section-group {
    .section-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 16px;
      
      .left {
        display: flex;
        align-items: center;
        padding-left: 8px;
        border-left: 4px solid;
        height: 16px;
        
        .title { font-weight: 800; font-size: 16px; color: #333; margin-right: 8px; letter-spacing: 0.5px; }
        .badge { 
          font-size: 10px; padding: 2px 6px; border-radius: 4px; background: #e6f7ff; color: #1890ff; font-weight: 600;
          &.red { background: #fff1f0; color: #f5222d; }
        }
      }
      
      .right-op {
        font-size: 12px; color: #999; display: flex; align-items: center; gap: 4px;
        padding: 4px 8px; background: #f5f5f5; border-radius: 12px;
        &:active { background: #e6e6e6; color: #333; }
      }
    }
    
    &.top-section .section-header .left { border-left-color: #1890ff; }
    &.bottom-section .section-header .left { border-left-color: #ff4d4f; }
  }
  
  .section-divider {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 24px 0;
    
    .line { flex: 1; height: 1px; background: #f0f0f0; }
    .divider-tag { 
      display: flex; flex-direction: column; align-items: center;
      color: #ccc; font-size: 12px; font-weight: 600;
      .sub { font-size: 10px; font-weight: 400; transform: scale(0.9); }
    }
  }
  
  .number-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr); 
    gap: 16px 10px;
    
    .ball-wrapper {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 6px;
      
      .ball-item {
        width: 46px;
        height: 46px;
        border-radius: 50%;
        background: #fff;
        border: 1px solid #f0f0f0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 17px;
        font-weight: 700;
        color: #333;
        font-family: 'DIN Alternate';
        box-shadow: 0 4px 8px rgba(0,0,0,0.04);
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); 
        position: relative;
        
        
        .check-mark {
           position: absolute;
           bottom: 0;
           right: 0;
           width: 14px;
           height: 14px;
           background: #fff;
           border-radius: 50%;
           display: flex; align-items: center; justify-content: center;
           color: #1890ff;
           font-size: 10px;
           box-shadow: -1px -1px 2px rgba(0,0,0,0.1);
        }

        &.blue.active {
          background: linear-gradient(135deg, #4dabf7, #1890ff);
          border-color: transparent;
          color: #fff;
          transform: scale(1.08);
          box-shadow: 0 6px 16px rgba(24, 144, 255, 0.4);
          .check-mark { color: #1890ff; }
        }
        
        &.red.active {
          background: linear-gradient(135deg, #ff7875, #f5222d);
          border-color: transparent;
          color: #fff;
          transform: scale(1.08);
          box-shadow: 0 6px 16px rgba(245, 34, 45, 0.4);
          .check-mark { color: #f5222d; }
        }
      }
      
      .miss-text {
        font-size: 10px;
        color: #bbb;
        font-family: 'DIN Alternate';
        transition: color 0.2s;
        
        &.is-hot { color: #ff4d4f; font-weight: 600; }
        &.is-cold { color: #40a9ff; }
      }
    }
  }
}

.scale-fade-enter-active,
.scale-fade-leave-active {
  transition: all 0.2s ease;
}
.scale-fade-enter-from,
.scale-fade-leave-to {
  opacity: 0;
  transform: scale(0);
}
</style>
