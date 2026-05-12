<template>
  <div class="panel-fun">
    
    <div class="sub-nav">
      <div 
        class="nav-item" 
        v-for="tab in tabs" 
        :key="tab.id"
        :class="{ active: currentTab.id === tab.id }"
        @click="switchTab(tab)"
      >
        {{ tab.name }}
      </div>
    </div>

    
    <div class="fun-area">
      
      <div class="odds-tip-bar">
        <span class="label">最高赔率</span>
        <span class="val">108.00</span>
        <div class="sep"></div>
        <span class="desc">{{ currentTab.desc }}</span>
      </div>

      <div class="bet-grid">
        <div 
          class="bet-item" 
          v-for="item in currentTab.options" 
          :key="item.value"
          :class="{ active: selectedValue === item.value, [item.styleClass || '']: true }"
          @click="toggleOption(item)"
        >
          <div class="content-inner">
            <div class="label">{{ item.label }}</div>
            <div class="odds">{{ item.odds }}</div>
          </div>
          
          
          <div class="bg-text">{{ item.label.slice(-1) }}</div>
          
          
          <div class="shine-effect" v-if="selectedValue === item.value"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'

const emit = defineEmits(['update:betCount', 'update:selectedData', 'clear'])

const tabs = [
  {
    id: 'sum',
    name: '和值',
    desc: '猜开奖号码总和的大小、单双、五行属性',
    options: [
      { label: '总和大', value: 'sum_big', odds: '1.95', styleClass: 'is-red' },
      { label: '总和小', value: 'sum_small', odds: '1.95', styleClass: 'is-blue' },
      { label: '总和单', value: 'sum_odd', odds: '1.95', styleClass: 'is-red' },
      { label: '总和双', value: 'sum_even', odds: '1.95', styleClass: 'is-blue' },
      { label: '总和810', value: 'sum_810', odds: '108.00', styleClass: 'is-green' },

      { label: '金', value: 'gold', odds: '9.20', styleClass: 'is-gold' },
      { label: '木', value: 'wood', odds: '4.60', styleClass: 'is-wood' },
      { label: '水', value: 'water', odds: '2.40', styleClass: 'is-water' },
      { label: '火', value: 'fire', odds: '4.60', styleClass: 'is-fire' },
      { label: '土', value: 'earth', odds: '9.20', styleClass: 'is-earth' }
    ]
  },
  {
    id: 'updown',
    name: '上下盘',
    desc: '猜开奖号码中，上盘(01-40)与下盘(41-80)哪个号码更多',
    options: [
      { label: '上盘', value: 'up', odds: '2.30', styleClass: 'is-blue' },
      { label: '中盘', value: 'middle', odds: '4.30', styleClass: 'is-green' },
      { label: '下盘', value: 'down', odds: '2.30', styleClass: 'is-red' }
    ]
  },
  {
    id: 'oddeven',
    name: '奇偶盘',
    desc: '猜开奖号码中，奇数与偶数哪个更多',
    options: [
      { label: '奇盘', value: 'odd_more', odds: '2.30', styleClass: 'is-red' },
      { label: '和盘', value: 'balance', odds: '4.30', styleClass: 'is-green' },
      { label: '偶盘', value: 'even_more', odds: '2.30', styleClass: 'is-blue' }
    ]
  }
]

const currentTab = ref(tabs[0])
const selectedValue = ref(null)
const selectedItem = ref(null)

function switchTab(tab) {
  if (currentTab.value.id === tab.id) return
  currentTab.value = tab
  clear()
}

function toggleOption(item) {
  if (selectedValue.value === item.value) {
    clear()
  } else {
    selectedValue.value = item.value
    selectedItem.value = item
    updateParent()
  }
}

function clear() {
  selectedValue.value = null
  selectedItem.value = null
  updateParent()
}

function updateParent() {
  if (!selectedValue.value) {
    emit('update:betCount', 0)
    emit('update:selectedData', null)
    return
  }

  emit('update:betCount', 1)
  emit('update:selectedData', {
    playId: `${currentTab.value.id}_${selectedValue.value}`,
    playName: `${currentTab.value.name}-${selectedItem.value.label}`,
    numbers: [selectedValue.value],
    tzcode: selectedValue.value,
    isFun: true // 标记为趣味玩法
  })
}

defineExpose({ clear })
</script>

<style lang="less" scoped>
.panel-fun {
  background: #fff;
  padding-bottom: 20px;
}

.sub-nav {
  position: sticky;
  top: 86px; 
  z-index: 10;
  background: #fff;
  box-shadow: 0 2px 6px rgba(0,0,0,0.02);
  display: flex;
  padding: 12px 16px;
  gap: 10px;
  border-bottom: 1px solid #f5f5f5;
  overflow-x: auto;
  
  .nav-item {
    padding: 6px 16px;
    background: #f5f7fa;
    border-radius: 20px;
    font-size: 13px;
    color: #666;
    white-space: nowrap;
    transition: all 0.3s;
    
    &.active {
      background: #333;
      color: #ffd700;
      font-weight: 600;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
  }
}

.fun-area {
  padding: 16px 12px;
  
  .odds-tip-bar {
    display: flex;
    align-items: center;
    background: #fff9c4;
    color: #856404;
    padding: 8px 12px;
    border-radius: 8px;
    margin-bottom: 16px;
    font-size: 12px;
    
    .label { font-weight: 600; margin-right: 4px; }
    .val { color: #ff4d4f; font-weight: 700; font-family: 'DIN Alternate'; font-size: 14px; }
    .sep { width: 1px; height: 10px; background: rgba(0,0,0,0.1); margin: 0 8px; }
    .desc { color: #856404; opacity: 0.8; }
  }
  
  .bet-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    
    .bet-item {
      position: relative;
      height: 72px;
      background: #fff;
      border: 1px solid #f0f0f0;
      border-radius: 12px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(0,0,0,0.03);
      
      .content-inner {
        position: relative;
        z-index: 2;
        display: flex;
        flex-direction: column;
        align-items: center;
      }
      
      .label { font-size: 16px; color: #333; font-weight: 700; letter-spacing: 1px; margin-bottom: 4px; }
      .odds { font-size: 13px; color: #999; font-weight: 500; font-family: 'DIN Alternate'; }
      
      .bg-text {
        position: absolute;
        right: -5px;
        bottom: -10px;
        font-size: 56px;
        color: #000;
        opacity: 0.02;
        font-weight: 900;
        z-index: 1;
        pointer-events: none;
        font-family: sans-serif;
      }
      
      
      .shine-effect {
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        background: linear-gradient(120deg, rgba(255,255,255,0) 30%, rgba(255,255,255,0.2) 50%, rgba(255,255,255,0) 70%);
        animation: shine 1.5s infinite;
        z-index: 3;
        pointer-events: none;
      }
      
      &.active {
        border-color: transparent;
        transform: translateY(-2px) scale(1.02);
        box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        
        .label { color: #fff; text-shadow: 0 1px 2px rgba(0,0,0,0.1); }
        .odds { color: rgba(255,255,255,0.9); }
        .bg-text { opacity: 0.15; color: #fff; }
        
        &.is-red { background: linear-gradient(135deg, #ff7875, #f5222d); }
        &.is-blue { background: linear-gradient(135deg, #69c0ff, #1890ff); }
        &.is-green { background: linear-gradient(135deg, #95de64, #52c41a); }
        &.is-gold { background: linear-gradient(135deg, #ffec3d, #faad14); .label { color: #7a4c05; } .odds { color: #7a4c05; } }
        &.is-wood { background: linear-gradient(135deg, #d9f7be, #73d13d); .label { color: #237804; } .odds { color: #237804; } }
        &.is-water { background: linear-gradient(135deg, #bae7ff, #40a9ff); .label { color: #0050b3; } .odds { color: #0050b3; } }
        &.is-fire { background: linear-gradient(135deg, #ffadd2, #f759ab); }
        &.is-earth { background: linear-gradient(135deg, #efdbff, #9254de); }
      }
    }
  }
}

@keyframes shine {
  0% { transform: translateX(-100%); }
  100% { transform: translateX(100%); }
}
</style>
