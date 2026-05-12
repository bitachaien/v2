<template>
  <div class="zuxuan">
    <div class="zuxuan-container">
      <div class="section-header">
        <div class="title">选号</div>
      </div>
      <div class="numbers-grid">
        <div 
          v-for="n in numbers" 
          :key="n" 
          class="number-item"
          :class="{ selected: selectedList.includes(n) }"
          @click="toggle(n)">{{ n }}</div>
      </div>
      <div class="filter-bar">
        <div class="tool-btn" @click="selectAll">全</div>
        <div class="tool-btn" @click="selectBig">大</div>
        <div class="tool-btn" @click="selectSmall">小</div>
        <div class="tool-btn" @click="selectOdd">奇</div>
        <div class="tool-btn" @click="selectEven">偶</div>
        <div class="tool-btn tool-clear" @click="clear">清</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'

const props = defineProps({ type: { type: String, default: 'z3' } })
const emits = defineEmits(['update:betCount', 'update:selectedData'])

const numbers = ref([0,1,2,3,4,5,6,7,8,9])
const selectedList = ref([])

function toggle(n) {
  const i = selectedList.value.indexOf(n)
  if (i > -1) selectedList.value.splice(i,1)
  else selectedList.value.push(n)
  sync()
}

function comb(n,k){ if(k<=0||n<k) return 0; let r=1; for(let i=1;i<=k;i++){ r=r*(n-i+1)/i } return Math.floor(r) }

function sync(){
  let count = 0
  const m = selectedList.value.length
  if (props.type==='z6') {

    count = comb(m,3)
  } else if (props.type === 'z2') {

    count = comb(m,2)
  } else {

    count = comb(m,2) * 2
  }
  emits('update:betCount', count)
  emits('update:selectedData', [...selectedList.value])
}

function selectAll(){ selectedList.value = [...numbers.value]; sync() }
function selectBig(){ selectedList.value = numbers.value.filter(n=>n>=5); sync() }
function selectSmall(){ selectedList.value = numbers.value.filter(n=>n<=4); sync() }
function selectOdd(){ selectedList.value = numbers.value.filter(n=>n%2!==0); sync() }
function selectEven(){ selectedList.value = numbers.value.filter(n=>n%2===0); sync() }
function clear(){ selectedList.value = []; sync() }

defineExpose({ clear })
watch(() => props.type, sync)
onMounted(sync)
</script>

<style lang="less" scoped>
@import "@/assets/theme.less";

.zuxuan {
  padding: 0;
}

.zuxuan-container {
  background: rgba(23, 30, 46, 0.6);
  border-radius: 12px;
  padding: 16px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  backdrop-filter: blur(10px);
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
}

.numbers-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 14px 10px;
  justify-items: center;
  margin-bottom: 20px;
}

.number-item {
  width: 42px;
  height: 42px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.15);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 17px;
  font-weight: 600;
  color: #fff;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.18, 0.89, 0.32, 1.28);
  user-select: none;
  
  &:active {
    transform: scale(0.9);
  }
  
  &.selected {
    background: linear-gradient(145deg, #00FF9A, #00cc7a);
    border-color: transparent;
    color: #000;
    box-shadow: 0 4px 15px rgba(0, 255, 154, 0.4);
    transform: translateY(-2px);
  }
}

.filter-bar {
  display: flex;
  justify-content: center;
  gap: 6px;
  padding-top: 16px;
  border-top: 1px dashed rgba(255, 255, 255, 0.1);
}

.tool-btn {
  font-size: 11px;
  color: rgba(255, 255, 255, 0.7);
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.1);
  padding: 6px 12px;
  border-radius: 14px;
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
</style>


