<template>
  <div class="bdw">
    <div class="position-row">
      <div class="position-label">不定位</div>
      <div class="numbers-grid">
        <div 
          v-for="num in numbers" 
          :key="num"
          class="number-item"
          :class="{ selected: selected.includes(num) }"
          @click="toggle(num)">
          {{ num }}
        </div>
      </div>
      <div class="quick-btns">
        <button class="quick-btn" @click="selectAll">全</button>
        <button class="quick-btn" @click="selectBig">大</button>
        <button class="quick-btn" @click="selectSmall">小</button>
        <button class="quick-btn" @click="selectOdd">奇</button>
        <button class="quick-btn" @click="selectEven">偶</button>
        <button class="quick-btn" @click="clearOne">清</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'

const props = defineProps({ minSelect: { type: Number, default: 1 } })
const emits = defineEmits(['update:betCount', 'update:selectedData'])

const numbers = ref([0,1,2,3,4,5,6,7,8,9])
const selected = ref([])

function toggle(num) {
  const i = selected.value.indexOf(num)
  if (i > -1) selected.value.splice(i, 1) 
  else selected.value.push(num)
  sync()
}
function selectAll() { selected.value = [...numbers.value]; sync() }
function selectBig() { selected.value = numbers.value.filter(n => n >= 5); sync() }
function selectSmall() { selected.value = numbers.value.filter(n => n <= 4); sync() }
function selectOdd() { selected.value = numbers.value.filter(n => n % 2 !== 0); sync() }
function selectEven() { selected.value = numbers.value.filter(n => n % 2 === 0); sync() }
function clearOne() { selected.value = []; sync() }

function comb(n, k) {
  if (k <= 0 || n < k) return 0
  let res = 1
  for (let i = 1; i <= k; i++) res = res * (n - i + 1) / i
  return Math.floor(res)
}

function sync() {
  const n = selected.value.length
  const k = Math.max(1, props.minSelect)
  const count = comb(n, k)
  emits('update:betCount', count)
  emits('update:selectedData', [...selected.value])
}

function clear() { selected.value = []; sync() }
defineExpose({ clear })

watch(() => props.minSelect, sync)
onMounted(sync)
</script>

<style lang="less" scoped>
@import "@/assets/theme.less";

.bdw {
  padding: 0;
}

.position-row { 
  background: rgba(23, 30, 46, 0.6);
  border-radius: 12px;
  padding: 16px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  backdrop-filter: blur(10px);
}

.position-label { 
  width: 80px; 
  height: 32px; 
  margin: 0 auto 14px; 
  background: rgba(0, 255, 154, 0.1); 
  border: 1px solid rgba(0, 255, 154, 0.2); 
  border-radius: 6px; 
  display: flex; 
  align-items: center; 
  justify-content: center; 
  color: #00FF9A; 
  font-size: 13px;
  font-weight: 600;
}

.numbers-grid { 
  display: grid; 
  grid-template-columns: repeat(5, 1fr); 
  gap: 14px 10px; 
  padding: 0; 
  margin-bottom: 16px;
  justify-items: center;
}

.number-item { 
  width: 42px;
  height: 42px;
  display: flex; 
  align-items: center; 
  justify-content: center; 
  background: rgba(255, 255, 255, 0.08); 
  border: 1px solid rgba(255, 255, 255, 0.15); 
  border-radius: 50%; 
  font-size: 17px; 
  font-weight: 600;
  color: #fff; 
  cursor: pointer;
  transition: all 0.2s;

  &:active {
    transform: scale(0.9);
  }
}

.number-item.selected { 
  background: linear-gradient(145deg, #00FF9A, #00cc7a); 
  border-color: transparent; 
  color: #000; 
  box-shadow: 0 4px 15px rgba(0, 255, 154, 0.4);
}

.quick-btns { 
  display: grid; 
  grid-template-columns: repeat(6, 1fr); 
  gap: 6px; 
}

.quick-btn { 
  padding: 6px 0; 
  background: rgba(255, 255, 255, 0.08); 
  border: 1px solid rgba(255, 255, 255, 0.1); 
  border-radius: 6px; 
  font-size: 11px; 
  color: rgba(255, 255, 255, 0.7);
  cursor: pointer;
  transition: all 0.2s;

  &:active {
    background: rgba(0, 255, 154, 0.2);
    color: #00FF9A;
  }
}
</style>










