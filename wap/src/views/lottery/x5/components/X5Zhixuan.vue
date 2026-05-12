<template>
  <div class="x5-zhixuan">
    <div 
      v-for="(row, index) in rowList" 
      :key="index" 
      class="group-box"
    >
      <div class="box-header">
        <div class="title">{{ row.name }}</div>
        <div class="desc">选择号码 <span class="odds">赔率: 19.6</span></div>
      </div>
      
      <div class="ball-grid">
        <div 
          v-for="item in row.balls" 
          :key="item.val" 
          class="ball-wrapper"
          @click="toggleSelector(index, item.val)"
        >
            <div class="ball-item" :class="{ active: selectedRows[index].includes(item.val) }">
              {{ item.label }}
            </div>
            <div class="omission">遗{{ item.omission }}</div>
        </div>
      </div>
      
      <div class="filter-bar">
        <div class="tool-btn" @click="filterRow(index, 'all')">全</div>
        <div class="tool-btn" @click="filterRow(index, 'big')">大</div>
        <div class="tool-btn" @click="filterRow(index, 'small')">小</div>
        <div class="tool-btn" @click="filterRow(index, 'odd')">奇</div>
        <div class="tool-btn" @click="filterRow(index, 'even')">偶</div>
        <div class="tool-btn tool-clear" @click="clearRow(index)">清</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, computed } from 'vue'

const props = defineProps({
  rows: { type: Array, default: () => ['万位', '千位'] },
  isSum: { type: Boolean, default: false }
})

const emit = defineEmits(['update:betCount', 'update:selectedData'])

const selectedRows = ref([])

const rowList = computed(() => {
    return props.rows.map(name => ({
        name,
        balls: Array.from({length: 11}, (_, i) => {
            const n = i + 1
            return {
                val: n,
                label: n < 10 ? '0'+n : ''+n,
                omission: Math.floor(Math.random() * 15)
            }
        })
    }))
})

function init() {
  selectedRows.value = props.rows.map(() => [])
  sync()
}

function toggleSelector(rowIndex, n) {
  const list = selectedRows.value[rowIndex]
  const i = list.indexOf(n)
  if (i > -1) list.splice(i, 1)
  else list.push(n)
  list.sort((a,b) => a-b)
  sync()
}

function filterRow(rowIndex, type) {
  const all = [1,2,3,4,5,6,7,8,9,10,11]
  let res = []
  switch(type) {
    case 'all': res = all; break;
    case 'big': res = all.filter(n => n >= 6); break;
    case 'small': res = all.filter(n => n <= 5); break;
    case 'odd': res = all.filter(n => n % 2 !== 0); break;
    case 'even': res = all.filter(n => n % 2 === 0); break;
    case 'clear': res = []; break;
  }
  selectedRows.value[rowIndex] = res
  sync()
}

function clearRow(rowIndex) {
  selectedRows.value[rowIndex] = []
  sync()
}

function clear() {
  init()
}
defineExpose({ clear })

function sync() {
  let count = 0
  if (props.isSum) {
    count = selectedRows.value.reduce((sum, row) => sum + row.length, 0)
  } else {
    count = calcZhixuanCount(selectedRows.value)
  }

  const dataStr = selectedRows.value.map(row => {
      return row.map(n => n < 10 ? '0'+n : ''+n).join(',')
  }).join('|')
  
  emit('update:betCount', count)
  emit('update:selectedData', dataStr)
}

function calcZhixuanCount(rows) {
   if (rows.some(r => r.length === 0)) return 0
   
   return countPermutations(rows, [])
}

function countPermutations(rows, currentPath) {
    if (currentPath.length === rows.length) return 1
    
    const currentRowIndex = currentPath.length
    const candidates = rows[currentRowIndex]
    let sum = 0
    
    for (const num of candidates) {
        if (!currentPath.includes(num)) {
            sum += countPermutations(rows, [...currentPath, num])
        }
    }
    return sum
}

watch(() => props.rows, init, { immediate: true })
</script>

<style lang="less" scoped>
@import "@/assets/theme.less";

.x5-zhixuan {
  padding: 12px 16px;
}

.group-box {
  background: #fff;
  border-radius: 12px;
  padding: 16px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
  margin-bottom: 12px;
}

.box-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  .title {
    font-size: 14px;
    font-weight: 600;
    color: #333;
    &::before {
      content: '';
      display: inline-block;
      width: 4px;
      height: 12px;
      background: @primary-color;
      border-radius: 2px;
      margin-right: 6px;
    }
  }
  .desc { 
      font-size: 12px; color: #999; 
      .odds { color: #ff976a; margin-left: 6px; font-weight: 500; }
  }
}

.ball-grid {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 16px 8px;
  justify-items: center;
  margin-bottom: 16px;
}

.ball-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    cursor: pointer;
    
    .omission {
        font-size: 10px;
        color: #c8c9cc;
        transform: scale(0.9);
    }
}

.ball-item {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #fff;
  border: 1px solid #ebedf0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 17px;
  font-family: 'Roboto', sans-serif;
  font-weight: 600;
  color: #323233;
  box-shadow: 
    0 2px 6px rgba(0,0,0,0.06),
    inset 0 1px 2px rgba(255,255,255,0.8);
  transition: all 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  user-select: none;
  position: relative;
  
  &::after {
    content: '';
    position: absolute;
    top: 4px;
    left: 8px;
    width: 14px;
    height: 8px;
    background: linear-gradient(180deg, rgba(255,255,255,0.8) 0%, rgba(255,255,255,0) 100%);
    border-radius: 50%;
    opacity: 0.6;
  }

  &:active {
    transform: scale(0.92);
  }
  
  &.active {
    background: linear-gradient(135deg, #ff6b4e, #ee0a24);
    border-color: transparent;
    color: #fff;
    box-shadow: 
      0 4px 12px rgba(238, 10, 36, 0.3),
      inset 0 2px 4px rgba(255,255,255,0.2);
    transform: translateY(-2px);
    
    &::after {
      background: linear-gradient(180deg, rgba(255,255,255,0.4) 0%, rgba(255,255,255,0) 100%);
    }
  }
}

.filter-bar {
  display: flex;
  justify-content: center;
  gap: 10px;
  padding-top: 16px;
  border-top: 1px solid #f5f6f7;
}

.tool-btn {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  color: #646566;
  background: #fff;
  border: 1px solid #ebedf0;
  border-radius: 50%;
  cursor: pointer;
  transition: all 0.2s;
  font-weight: 500;
  box-shadow: 0 1px 2px rgba(0,0,0,0.02);
  
  &:active {
    background: #f2f3f5;
    transform: scale(0.92);
  }
  
  &.tool-clear {
    color: @danger-color;
    border-color: rgba(238, 10, 36, 0.2);
    background: rgba(238, 10, 36, 0.02);
    
    &:active {
        background: rgba(238, 10, 36, 0.08);
    }
  }
}
</style>
