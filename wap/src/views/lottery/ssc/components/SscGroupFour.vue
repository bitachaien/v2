<template>
  <div class="group-four">
    <div v-if="type==='24'" class="section">
      <div class="position-label">任选4个号码</div>
      <div class="numbers-grid">
        <div v-for="n in numbers" :key="n" class="number-item" :class="{selected: selAll.includes(n)}" @click="toggle(selAll,n)">{{ n }}</div>
      </div>
    </div>
    <div v-else-if="type==='12'" class="section">
      <div class="position-label">二重号</div>
      <div class="numbers-grid">
        <div v-for="n in numbers" :key="'d'+n" class="number-item" :class="{selected: selDouble.includes(n)}" @click="toggle(selDouble,n)">{{ n }}</div>
      </div>
      <div class="position-label">单号(选2个)</div>
      <div class="numbers-grid">
        <div v-for="n in numbers" :key="'s'+n" class="number-item" :class="{selected: selSingle.includes(n)}" @click="toggle(selSingle,n)">{{ n }}</div>
      </div>
    </div>
    <div v-else-if="type==='6'" class="section">
      <div class="position-label">二重号(选2个)</div>
      <div class="numbers-grid">
        <div v-for="n in numbers" :key="'d2'+n" class="number-item" :class="{selected: selDouble.includes(n)}" @click="toggle(selDouble,n)">{{ n }}</div>
      </div>
    </div>
    <div v-else-if="type==='4'" class="section">
      <div class="position-label">三重号</div>
      <div class="numbers-grid">
        <div v-for="n in numbers" :key="'t'+n" class="number-item" :class="{selected: selTriple.includes(n)}" @click="toggle(selTriple,n)">{{ n }}</div>
      </div>
      <div class="position-label">单号(选1个)</div>
      <div class="numbers-grid">
        <div v-for="n in numbers" :key="'s1'+n" class="number-item" :class="{selected: selSingle.includes(n)}" @click="toggle(selSingle,n)">{{ n }}</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
const props = defineProps({ type: { type: String, default: '24' } })
const emits = defineEmits(['update:betCount','update:selectedData'])

const numbers = ref([0,1,2,3,4,5,6,7,8,9])
const selAll = ref([])
const selDouble = ref([])
const selSingle = ref([])
const selTriple = ref([])

function toggle(list,n){
  const i = list.indexOf(n)
  if (i > -1) {
    list.splice(i, 1)
  } else {
    list.push(n)
  }
  sync()
}
function comb(n,k){ if(k<=0||n<k) return 0; let r=1; for(let i=1;i<=k;i++){ r=r*(n-i+1)/i } return Math.floor(r) }

function sync(){
  let cnt=0
  if(props.type==='24'){
    cnt = comb(selAll.value.length,4)
  }else if(props.type==='12'){
    for(const d of selDouble.value){
      const k = selSingle.value.filter(s=>s!==d).length
      cnt += comb(k,2)
    }
  }else if(props.type==='6'){
    cnt = comb(selDouble.value.length,2)
  }else if(props.type==='4'){
    for(const t of selTriple.value){
      cnt += selSingle.value.filter(s=>s!==t).length
    }
  }
  emits('update:betCount', cnt)
  emits('update:selectedData', { all: selAll.value, double: selDouble.value, single: selSingle.value, triple: selTriple.value })
}

function clear(){ selAll.value=[]; selDouble.value=[]; selSingle.value=[]; selTriple.value=[]; sync() }
defineExpose({ clear })
watch(()=>props.type, clear)
onMounted(sync)
</script>

<style lang="less" scoped>
@import "@/assets/theme.less";

.group-four {
  padding: 0;
}

.section {
  background: rgba(23, 30, 46, 0.6);
  border-radius: 12px;
  padding: 16px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  backdrop-filter: blur(10px);
  margin-bottom: 12px;
}

.position-label { 
  width: auto;
  padding: 4px 12px;
  margin: 8px auto 12px;
  background: rgba(0, 255, 154, 0.1); 
  border: 1px solid rgba(0, 255, 154, 0.2); 
  border-radius: 16px;
  display: inline-flex;
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
  padding: 0;
  margin-bottom: 8px;
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


