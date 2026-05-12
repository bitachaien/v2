<template>
  <div class="ssc-fushi">
    <div class="position-row" v-for="(pos, idx) in positions" :key="pos">
      <div class="row-top">
        <div class="pos-badge">{{ pos }}位</div>
        <div class="quick-tools">
          <div class="tool-btn" @click="quickSelect(idx, 'all')">全</div>
          <div class="tool-btn" @click="quickSelect(idx, 'big')">大</div>
          <div class="tool-btn" @click="quickSelect(idx, 'small')">小</div>
          <div class="tool-btn" @click="quickSelect(idx, 'odd')">奇</div>
          <div class="tool-btn" @click="quickSelect(idx, 'even')">偶</div>
          <div class="tool-btn tool-clear" @click="quickSelect(idx, 'clear')">清</div>
        </div>
      </div>
      
      <div class="numbers-container">
        <div 
          v-for="num in 10" 
          :key="num-1" 
          class="ball-item"
          :class="{ active: isSelected(idx, num-1) }"
          @click="toggle(idx, num-1)">
          {{ num-1 }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'

const props = defineProps({
  positions: { 
    type: Array, 
    default: () => ['万','千','百','十','个']
  },
  isSum: {
    type: Boolean,
    default: false
  }
})
const emits = defineEmits(['update:betCount', 'update:selectedData'])

const selectedRows = ref([])

function init() {
  const positions = props.positions && props.positions.length > 0 
    ? props.positions 
    : ['万','千','百','十','个']
  selectedRows.value = positions.map(() => [])
}

function isSelected(rowIdx, num) {
  return selectedRows.value[rowIdx]?.includes(num)
}

function toggle(rowIdx, num) {
  if (!selectedRows.value[rowIdx]) selectedRows.value[rowIdx] = []
  const arr = selectedRows.value[rowIdx]
  const i = arr.indexOf(num)
  if (i > -1) arr.splice(i, 1)
  else {
    arr.push(num)
    arr.sort((a,b) => a-b)
  }
  sync()
}

function quickSelect(rowIdx, type) {
  if (!selectedRows.value[rowIdx]) selectedRows.value[rowIdx] = []
  const all = [0,1,2,3,4,5,6,7,8,9]
  let res = []
  switch(type) {
    case 'all': res = all; break;
    case 'big': res = all.filter(n => n >= 5); break;
    case 'small': res = all.filter(n => n <= 4); break;
    case 'odd': res = all.filter(n => n % 2 !== 0); break;
    case 'even': res = all.filter(n => n % 2 === 0); break;
    case 'clear': res = []; break;
  }
  selectedRows.value[rowIdx] = res
  sync()
}

function calcBetCount() {
  if (selectedRows.value.length === 0) return 0
  
  if (props.isSum) {

    let count = 0
    for (let i = 0; i < selectedRows.value.length; i++) {
      count += selectedRows.value[i].length
    }
    return count
  }

  let count = 1
  for (let i = 0; i < selectedRows.value.length; i++) {
    const len = selectedRows.value[i].length
    if (len === 0) return 0
    count *= len
  }
  return count
}

function sync() {
  const count = calcBetCount()
  const dataStr = selectedRows.value.map(row => row.join(',')).join('|')
  emits('update:betCount', count)
  emits('update:selectedData', dataStr)
}

function clear() {
  init()
  sync()
}

defineExpose({ clear })

watch(() => props.positions, init)
onMounted(() => {
  init()
})
</script>

<style lang="less" scoped>
@import "@/assets/theme.less";

.ssc-fushi {
  padding: 0;
}

.position-row {
  background: rgba(23, 30, 46, 0.5);
  border-radius: 10px;
  padding: 10px 12px;
  margin-bottom: 8px;
  border: 1px solid rgba(255, 255, 255, 0.06);
}

.row-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
}

.pos-badge {
  font-size: 12px;
  font-weight: 600;
  color: #00FF9A;
  background: rgba(0, 255, 154, 0.1);
  padding: 2px 10px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  &::before {
    content: '';
    display: block;
    width: 3px;
    height: 10px;
    background: #00FF9A;
    border-radius: 2px;
    margin-right: 5px;
  }
}

.quick-tools {
  display: flex;
  gap: 4px;
}

.tool-btn {
  font-size: 10px;
  color: rgba(255, 255, 255, 0.7);
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.1);
  padding: 3px 6px;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
  font-weight: 500;
  
  &:active {
    background: rgba(0, 255, 154, 0.2);
    color: #00FF9A;
  }
  
  &.tool-clear {
    color: #ff6b6b;
    background: rgba(255, 107, 107, 0.1);
  }
}

.numbers-container {
  display: flex;
  justify-content: space-between;
  gap: 6px;
}

.ball-item {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.15);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  font-weight: 600;
  color: #fff;
  cursor: pointer;
  transition: all 0.15s;
  user-select: none;
  flex: 1;
  max-width: 32px;
  
  &:active {
    transform: scale(0.9);
  }
  
  &.active {
    background: linear-gradient(145deg, #00FF9A, #00cc7a);
    border-color: transparent;
    color: #000;
    box-shadow: 0 2px 8px rgba(0, 255, 154, 0.4);
  }
}
</style>
