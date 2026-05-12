<template>
  <div class="ball-selector">
    <div v-for="(row, rowIndex) in layout" :key="rowIndex" class="selector-row">
      <div class="row-title" v-if="row.title">
        <div class="title-text">{{ row.title }}</div>
        <div class="row-actions" v-if="showRowActions">
          <span @click="handleQuickSelect(rowIndex, 'all')">全</span>
          <span @click="handleQuickSelect(rowIndex, 'big')">大</span>
          <span @click="handleQuickSelect(rowIndex, 'small')">小</span>
          <span @click="handleQuickSelect(rowIndex, 'odd')">单</span>
          <span @click="handleQuickSelect(rowIndex, 'even')">双</span>
          <span @click="handleQuickSelect(rowIndex, 'clear')">清</span>
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
          <span class="ball-content">{{ opt.label }}</span>
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
  padding: 0 8px;
}

.selector-row {
  margin-bottom: 16px;
  background: #fff;
  border-radius: 8px;
  padding: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.02);
}

.row-title {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
  padding-bottom: 8px;
  border-bottom: 1px dashed #eee;
  
  .title-text {
    font-weight: bold;
    color: #333;
    font-size: 14px;
    border-left: 3px solid #e1251b;
    padding-left: 8px;
  }
  
  .row-actions {
    display: flex;
    gap: 8px;
    
    span {
      font-size: 12px;
      color: #666;
      padding: 2px 6px;
      border-radius: 4px;
      background: #f5f5f5;
      cursor: pointer;
      
      &:active {
        background: #e0e0e0;
      }
    }
  }
}

.balls-container {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 12px;
  justify-items: center;
}

.ball-item {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: 1px solid #e0e0e0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  background: #fff;
  transition: all 0.2s;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
  
  .ball-content {
    font-size: 16px;
    font-weight: 500;
    color: #e1251b;
  }
  
  .ball-odds {
    font-size: 10px;
    color: #999;
    margin-top: -2px;
  }
  
  &.active {
    background: #e1251b;
    border-color: #e1251b;
    box-shadow: 0 4px 8px rgba(225, 37, 27, 0.3);
    transform: translateY(-2px);
    
    .ball-content, .ball-odds {
      color: #fff;
    }
  }
  
  &.wide {
    width: auto;
    min-width: 40px;
    padding: 0 8px;
    border-radius: 20px;
  }
  
  &:active {
    transform: scale(0.95);
  }
}
</style>
