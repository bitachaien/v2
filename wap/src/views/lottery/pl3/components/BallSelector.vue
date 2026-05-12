<template>
  <div class="ball-selector">
    <div v-for="(row, rowIndex) in layout" :key="rowIndex" class="selector-row">
      <div class="row-header" v-if="row.title">
        <div class="title-box">
          <div class="title-indicator"></div>
          <div class="title-text">{{ row.title }}</div>
        </div>
        <div class="row-actions" v-if="showRowActions">
          <span class="action-btn" @click="handleQuickSelect(rowIndex, 'all')">全</span>
          <span class="action-btn" @click="handleQuickSelect(rowIndex, 'big')">大</span>
          <span class="action-btn" @click="handleQuickSelect(rowIndex, 'small')">小</span>
          <span class="action-btn" @click="handleQuickSelect(rowIndex, 'odd')">单</span>
          <span class="action-btn" @click="handleQuickSelect(rowIndex, 'even')">双</span>
          <span class="action-btn clear" @click="handleQuickSelect(rowIndex, 'clear')">清</span>
        </div>
      </div>
      <div class="balls-container">
        <div 
          v-for="opt in row.options" 
          :key="opt.value" 
          class="ball-item"
          :class="{ active: isSelected(rowIndex, opt.value), wide: isWide(opt.label) }"
          @click="toggleSelection(rowIndex, opt.value)"
        >
          <div class="ball-inner">
            <span class="ball-content">{{ opt.label }}</span>
          </div>
          <span class="ball-odds" v-if="opt.odds">{{ opt.odds }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  layout: {
    type: Array,
    required: true,

  },
  selected: {
    type: Object,
    default: () => ({})

  },
  showRowActions: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['update:selected'])

const isSelected = (rowIndex, value) => {
  const rowSelected = props.selected[rowIndex] || []
  return rowSelected.includes(value)
}

const isWide = (label) => {
  return String(label).length > 1
}

const toggleSelection = (rowIndex, value) => {
  const newSelected = { ...props.selected }
  if (!newSelected[rowIndex]) {
    newSelected[rowIndex] = []
  }
  
  const index = newSelected[rowIndex].indexOf(value)
  if (index > -1) {
    newSelected[rowIndex].splice(index, 1)
  } else {
    newSelected[rowIndex].push(value)
  }
  
  emit('update:selected', newSelected)
}

const handleQuickSelect = (rowIndex, type) => {
  const row = props.layout[rowIndex]
  if (!row) return
  
  let newSelection = []
  const options = row.options
  
  switch (type) {
    case 'all':
      newSelection = options.map(o => o.value)
      break
    case 'big':

      newSelection = options.filter(o => {
        const v = parseInt(o.value)
        return !isNaN(v) && v >= 5
      }).map(o => o.value)
      break
    case 'small':
      newSelection = options.filter(o => {
        const v = parseInt(o.value)
        return !isNaN(v) && v < 5
      }).map(o => o.value)
      break
    case 'odd':
      newSelection = options.filter(o => {
        const v = parseInt(o.value)
        return !isNaN(v) && v % 2 !== 0
      }).map(o => o.value)
      break
    case 'even':
      newSelection = options.filter(o => {
        const v = parseInt(o.value)
        return !isNaN(v) && v % 2 === 0
      }).map(o => o.value)
      break
    case 'clear':
      newSelection = []
      break
  }
  
  const newSelected = { ...props.selected }
  newSelected[rowIndex] = newSelection
  emit('update:selected', newSelected)
}
</script>

<style lang="less" scoped>
.ball-selector {
  padding: 4px 0;
}

.selector-row {
  margin-bottom: 20px;
  background: transparent;
  
  &:last-child {
    margin-bottom: 0;
  }
}

.row-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  padding: 0 4px;
  
  .title-box {
    display: flex;
    align-items: center;
    gap: 8px;
    
    .title-indicator {
      width: 4px;
      height: 16px;
      background: #1890ff;
      border-radius: 2px;
    }
    
    .title-text {
      font-weight: 600;
      color: #333;
      font-size: 15px;
    }
  }
  
  .row-actions {
    display: flex;
    gap: 6px;
    
    .action-btn {
      font-size: 12px;
      color: #666;
      width: 26px;
      height: 26px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      background: #f5f7fa;
      cursor: pointer;
      transition: all 0.2s;
      
      &:hover {
        background: #e6f7ff;
        color: #1890ff;
      }
      
      &:active {
        transform: scale(0.9);
      }
      
      &.clear {
        background: #fff1f0;
        color: #ff4d4f;
        
        &:hover {
          background: #ffccc7;
        }
      }
    }
  }
}

.balls-container {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 16px 12px;
  justify-items: center;
}

.ball-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  -webkit-tap-highlight-color: transparent;
  
  .ball-inner {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: #f0f2f5;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);
    border: 1px solid transparent;
    
    .ball-content {
      font-size: 18px;
      font-weight: 600;
      color: #333;
      font-family: 'DIN', sans-serif;
    }
  }
  
  .ball-odds {
    font-size: 11px;
    color: #999;
    margin-top: 6px;
    transition: color 0.3s;
  }
  
  &.active {
    transform: translateY(-4px);
    
    .ball-inner {
      background: linear-gradient(135deg, #1890ff 0%, #096dd9 100%);
      box-shadow: 0 6px 16px rgba(24, 144, 255, 0.3);
      
      .ball-content {
        color: #fff;
      }
    }
    
    .ball-odds {
      color: #1890ff;
      font-weight: 500;
    }
  }
  
  &.wide {
    width: 100%;
    max-width: 80px;
    
    .ball-inner {
      width: 100%;
      border-radius: 8px;
      height: 40px;
    }
  }
  
  &:active {
    transform: scale(0.92);
  }
}
</style>
