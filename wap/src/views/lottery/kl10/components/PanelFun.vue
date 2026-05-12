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
        <span class="val">1.95</span>
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
          <div class="shine-effect" v-if="selectedValue === item.value"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const emit = defineEmits(['update:betCount', 'update:selectedData', 'clear'])

const tabs = [
  {
    id: 'sum',
    name: '两面盘',
    desc: '猜总和大小、单双、尾数大小',
    options: [
      { label: '总和大', value: 'sum_big', odds: '1.95', styleClass: 'is-red' },
      { label: '总和小', value: 'sum_small', odds: '1.95', styleClass: 'is-blue' },
      { label: '总和单', value: 'sum_odd', odds: '1.95', styleClass: 'is-red' },
      { label: '总和双', value: 'sum_even', odds: '1.95', styleClass: 'is-blue' },
      { label: '尾大', value: 'tail_big', odds: '1.95', styleClass: 'is-red' },
      { label: '尾小', value: 'tail_small', odds: '1.95', styleClass: 'is-blue' },
      { label: '龙', value: 'dragon', odds: '1.95', styleClass: 'is-red' },
      { label: '虎', value: 'tiger', odds: '1.95', styleClass: 'is-blue' }
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
    isFun: true
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
      transition: all 0.3s;
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(0,0,0,0.03);
      
      .label { font-size: 16px; color: #333; font-weight: 700; margin-bottom: 4px; }
      .odds { font-size: 13px; color: #999; font-family: 'DIN Alternate'; }
      
      .shine-effect {
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        background: linear-gradient(120deg, rgba(255,255,255,0) 30%, rgba(255,255,255,0.2) 50%, rgba(255,255,255,0) 70%);
        animation: shine 1.5s infinite;
        pointer-events: none;
      }
      
      &.active {
        border-color: transparent;
        transform: translateY(-2px) scale(1.02);
        box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        .label, .odds { color: #fff; text-shadow: 0 1px 2px rgba(0,0,0,0.1); }
        
        &.is-red { background: linear-gradient(135deg, #ff7875, #f5222d); }
        &.is-blue { background: linear-gradient(135deg, #69c0ff, #1890ff); }
        &.is-green { background: linear-gradient(135deg, #95de64, #52c41a); }
      }
    }
  }
}

@keyframes shine {
  0% { transform: translateX(-100%); }
  100% { transform: translateX(100%); }
}
</style>
