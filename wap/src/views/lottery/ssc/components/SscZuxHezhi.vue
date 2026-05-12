<template>
  <div class="zux-hezhi">
    <div class="position-label">组选和值</div>
    <div class="numbers-grid">
      <div v-for="s in sumList" :key="s" class="number-item" :class="{selected: selected.includes(s)}" @click="toggle(s)">{{ s }}</div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

const props = defineProps({ digits: { type: Number, default: 3 } })
const emits = defineEmits(['update:betCount','update:selectedData'])

const sumList = computed(() => {
  if (props.digits === 2) return Array.from({length:17}, (_,i)=>i+1) // 1..17
  return Array.from({length:26}, (_,i)=>i+1) // 1..26
})
const selected = ref([])

function toggle(s){
  const i = selected.value.indexOf(s)
  if (i > -1) {
    selected.value.splice(i, 1)
  } else {
    selected.value.push(s)
  }
  sync()
}

function countForSum2(sum){

  const arr = [0,1,1,2,2,3,3,4,4,5,4,4,3,3,2,2,1,1]
  return arr[sum] || 0
}

function countForSum3(sum){

  const arr = [1,2,2,4,5,6,8,10,11,13,14,14,15,15,14,14,13,11,10,8,6,5,4,2,2,1]
  return (sum>=1 && sum<=26) ? arr[sum-1] : 0
}

function sync(){
  let total = 0
  for(const s of selected.value){
    total += props.digits===2 ? countForSum2(s) : countForSum3(s)
  }
  emits('update:betCount', total)
  emits('update:selectedData', [...selected.value])
}

function clear(){ selected.value=[]; sync() }
defineExpose({ clear })
onMounted(sync)
</script>

<style lang="less" scoped>
@import "@/assets/theme.less";

.zux-hezhi {
  background: rgba(23, 30, 46, 0.6);
  border-radius: 12px;
  padding: 16px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  backdrop-filter: blur(10px);
}

.position-label { 
  width: auto;
  padding: 4px 12px;
  margin: 0 auto 14px;
  background: rgba(0, 255, 154, 0.1); 
  border: 1px solid rgba(0, 255, 154, 0.2); 
  border-radius: 16px;
  display: flex;
  align-items: center; 
  justify-content: center; 
  color: #00FF9A; 
  font-size: 12px;
  font-weight: 600;
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
  display: flex; 
  align-items: center; 
  justify-content: center; 
  background: rgba(255, 255, 255, 0.08); 
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 50%; 
  color: #fff;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;

  &:active {
    transform: scale(0.9);
  }
}

.number-item.selected { 
  background: linear-gradient(145deg, #00FF9A, #00cc7a); 
  color: #000; 
  border-color: transparent;
  box-shadow: 0 4px 12px rgba(0, 255, 154, 0.4);
}
</style>


