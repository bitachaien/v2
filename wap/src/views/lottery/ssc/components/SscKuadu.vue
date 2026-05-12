<template>
  <div class="kuadu">
    <div class="position-label">跨度</div>
    <div class="numbers-grid">
      <div v-for="k in spans" :key="k" class="number-item" :class="{selected: selected.includes(k)}" @click="toggle(k)">{{ k }}</div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

const props = defineProps({ digits: { type: Number, default: 3 } })
const emits = defineEmits(['update:betCount','update:selectedData'])

const spans = ref([0,1,2,3,4,5,6,7,8,9])
const selected = ref([])

function toggle(k){
  const i = selected.value.indexOf(k)
  if (i > -1) {
    selected.value.splice(i, 1)
  } else {
    selected.value.push(k)
  }
  sync()
}

function countForSpan(span){
  const d = props.digits
  let cnt = 0
  if(d===2){
    if(span===0) return 10
    return 2*(10-span)
  }

  const map3 = [10,54,96,126,144,150,144,126,96,54]
  return map3[span] || 0
}

function sync(){
  const total = selected.value.reduce((acc,k)=>acc+countForSpan(k),0)
  emits('update:betCount', total)
  emits('update:selectedData', [...selected.value])
}

function clear(){ selected.value=[]; sync() }
defineExpose({ clear })
onMounted(sync)
</script>

<style lang="less" scoped>
@import "@/assets/theme.less";

.kuadu {
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
  grid-template-columns: repeat(5, 1fr); 
  gap: 14px 10px;
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
  color: #fff;
  font-size: 17px;
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
  box-shadow: 0 4px 15px rgba(0, 255, 154, 0.4);
}
</style>


