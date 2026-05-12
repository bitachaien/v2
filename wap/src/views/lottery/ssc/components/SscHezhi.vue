<template>
  <div class="hezhi">
    <div class="hezhi-container">
      <div class="section-header">
        <div class="title">和值</div>
      </div>
      <div class="numbers-grid">
        <div 
          v-for="s in sums" 
          :key="s" 
          class="number-item" 
          :class="{ selected: selected.includes(s) }"
          @click="toggle(s)">{{ s }}</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

const props = defineProps({
  hezhiRange: { type: Array, default: () => [0, 27] },
  countMap: { type: Array, default: null }
})
const emits = defineEmits(['update:betCount', 'update:selectedData'])

const sums = computed(() => {
  const [a,b] = props.hezhiRange
  const arr = []
  for (let i=a;i<=b;i++) arr.push(i)
  return arr
})
const selected = ref([])

const defaultMap2 = [1,2,3,4,5,6,7,8,9,10,9,8,7,6,5,4,3,2,1]
const defaultMap3 = [1,3,6,10,15,21,28,36,45,55,63,69,73,75,75,73,69,63,55,45,36,28,21,15,10,6,3,1]

const map = computed(() => {
  if (props.countMap) return props.countMap
  const max = props.hezhiRange[1]
  if (max === 18) return defaultMap2
  return defaultMap3
})

function toggle(s) {
  const i = selected.value.indexOf(s)
  if (i > -1) {
    selected.value.splice(i, 1)
  } else {
    selected.value.push(s)
  }
  sync()
}

function sync(){
  const count = selected.value.reduce((acc, s) => acc + (map.value[s] || 0), 0)
  emits('update:betCount', count)
  emits('update:selectedData', [...selected.value])
}

function clear(){ selected.value=[]; sync() }
defineExpose({ clear })
onMounted(sync)
</script>

<style lang="less" scoped>
@import "@/assets/theme.less";

.hezhi {
  padding: 0;
}

.hezhi-container {
  background: rgba(23, 30, 46, 0.6);
  border-radius: 12px;
  padding: 16px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  backdrop-filter: blur(10px);
}

.section-header {
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
}

.numbers-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 10px 6px;
  justify-items: center;
}

.number-item {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.15);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  font-weight: 600;
  color: #fff;
  cursor: pointer;
  transition: all 0.2s;
  user-select: none;
  
  &:active {
    transform: scale(0.9);
  }
  
  &.selected {
    background: linear-gradient(145deg, #00FF9A, #00cc7a);
    border-color: transparent;
    color: #000;
    box-shadow: 0 4px 12px rgba(0, 255, 154, 0.4);
    transform: translateY(-1px);
  }
}
</style>


