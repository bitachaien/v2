<template>
  <div class="dxds">
    <div class="dxds-container" v-for="(pos, idx) in positions" :key="idx">
      <div class="section-header">
        <div class="title">{{ pos }}</div>
        <div class="quick-tools">
          <div class="tool-btn" @click="selectAll(idx)">全</div>
          <div class="tool-btn tool-clear" @click="clearOne(idx)">清</div>
        </div>
      </div>
      
      <div class="dxds-grid">
        <div 
          v-for="op in options" 
          :key="op.val"
          class="dxds-item"
          :class="{ selected: isSelected(idx, op.val) }"
          @click="toggle(idx, op.val)">
          {{ op.text }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'

const emits = defineEmits(['update:betCount', 'update:selectedData'])
const props = defineProps({ positions: { type: Array, default: () => ['十位','个位'] } })
const positions = ref(props.positions && props.positions.length > 0 ? props.positions : ['十位','个位'])
const options = ref([
  { text: '大', val: 'big' },
  { text: '小', val: 'small' },
  { text: '单', val: 'odd' },
  { text: '双', val: 'even' }
])
const selected = ref(positions.value.map(() => []))

function isSelected(idx, v) { return selected.value[idx]?.includes(v) }
function toggle(idx, v) {
  const arr = selected.value[idx]
  const i = arr.indexOf(v)
  if (i > -1) {
    arr.splice(i, 1)
  } else {
    arr.push(v)
  }
  sync()
}
function selectAll(idx) { selected.value[idx] = options.value.map(o => o.val); sync() }
function clearOne(idx) { selected.value[idx] = []; sync() }

function sync() {
  let count = 1
  for (const arr of selected.value) {
    const len = arr.length
    if (len === 0) { count = 0; break }
    count *= len
  }
  emits('update:betCount', count)
  emits('update:selectedData', selected.value.map(a => [...a]))
}

function clear() { selected.value = positions.value.map(() => []); sync() }
defineExpose({ clear })

onMounted(sync)
watch(() => props.positions, (v) => { positions.value = v; clear() })
</script>

<style lang="less" scoped>
@import "@/assets/theme.less";

.dxds {
  padding: 0;
}

.dxds-container {
  background: rgba(23, 30, 46, 0.6);
  border-radius: 12px;
  padding: 16px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  backdrop-filter: blur(10px);
  margin-bottom: 12px;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  
  .title {
    font-size: 14px;
    font-weight: 600;
    color: #00FF9A;
    display: flex;
    align-items: center;
    &::before {
      content: '';
      display: block;
      width: 4px;
      height: 14px;
      background: #00FF9A;
      border-radius: 2px;
      margin-right: 8px;
      box-shadow: 0 0 8px #00FF9A;
    }
  }
  
  .quick-tools {
    display: flex;
    gap: 6px;
  }
}

.tool-btn {
  font-size: 11px;
  color: rgba(255, 255, 255, 0.7);
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.1);
  padding: 4px 10px;
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.2s;
  font-weight: 500;
  
  &:active {
    background: rgba(0, 255, 154, 0.2);
    color: #00FF9A;
    transform: scale(0.95);
  }
  
  &.tool-clear {
    color: #ff6b6b;
    background: rgba(255, 107, 107, 0.1);
    border: 1px solid rgba(255, 107, 107, 0.2);
  }
}

.dxds-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 10px;
}

.dxds-item {
  padding: 12px 0;
  text-align: center;
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  color: #fff;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  user-select: none;
  
  &:active {
    transform: scale(0.98);
  }
  
  &.selected {
    background: rgba(0, 255, 154, 0.15);
    border-color: #00FF9A;
    color: #00FF9A;
    box-shadow: 0 0 15px rgba(0, 255, 154, 0.3);
    transform: translateY(-1px);
  }
}
</style>


