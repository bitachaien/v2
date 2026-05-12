<template>
  <div class="group-five">
    <div v-if="type==='120'" class="section">
      <div class="position-label">任选5个号码</div>
      <div class="numbers-grid">
        <div v-for="n in numbers" :key="n" class="number-item" :class="{selected: selAll.includes(n)}" @click="toggle(selAll,n)">{{ n }}</div>
      </div>
    </div>
    <div v-else-if="type==='60'" class="section">
      <div class="position-label">二重号</div>
      <div class="numbers-grid">
        <div v-for="n in numbers" :key="'d'+n" class="number-item" :class="{selected: selDouble.includes(n)}" @click="toggle(selDouble,n)">{{ n }}</div>
      </div>
      <div class="position-label">单号(任选3个)</div>
      <div class="numbers-grid">
        <div v-for="n in numbers" :key="'s'+n" class="number-item" :class="{selected: selSingle.includes(n)}" @click="toggle(selSingle,n)">{{ n }}</div>
      </div>
    </div>
    <div v-else-if="type==='30'" class="section">
      <div class="position-label">二重号(选2个)</div>
      <div class="numbers-grid">
        <div v-for="n in numbers" :key="'d2'+n" class="number-item" :class="{selected: selDouble.includes(n)}" @click="toggle(selDouble,n)">{{ n }}</div>
      </div>
      <div class="position-label">单号(选1个)</div>
      <div class="numbers-grid">
        <div v-for="n in numbers" :key="'s1'+n" class="number-item" :class="{selected: selSingle.includes(n)}" @click="toggle(selSingle,n)">{{ n }}</div>
      </div>
    </div>
    <div v-else-if="type==='20'" class="section">
      <div class="position-label">三重号</div>
      <div class="numbers-grid">
        <div v-for="n in numbers" :key="'t'+n" class="number-item" :class="{selected: selTriple.includes(n)}" @click="toggle(selTriple,n)">{{ n }}</div>
      </div>
      <div class="position-label">单号(选2个)</div>
      <div class="numbers-grid">
        <div v-for="n in numbers" :key="'s2'+n" class="number-item" :class="{selected: selSingle.includes(n)}" @click="toggle(selSingle,n)">{{ n }}</div>
      </div>
    </div>
    <div v-else-if="type==='10'" class="section">
      <div class="position-label">三重号</div>
      <div class="numbers-grid">
        <div v-for="n in numbers" :key="'t1'+n" class="number-item" :class="{selected: selTriple.includes(n)}" @click="toggle(selTriple,n)">{{ n }}</div>
      </div>
      <div class="position-label">二重号</div>
      <div class="numbers-grid">
        <div v-for="n in numbers" :key="'d1'+n" class="number-item" :class="{selected: selDouble.includes(n)}" @click="toggle(selDouble,n)">{{ n }}</div>
      </div>
    </div>
    <div v-else-if="type==='5'" class="section">
      <div class="position-label">四重号</div>
      <div class="numbers-grid">
        <div v-for="n in numbers" :key="'q'+n" class="number-item" :class="{selected: selQuad.includes(n)}" @click="toggle(selQuad,n)">{{ n }}</div>
      </div>
      <div class="position-label">单号(选1个)</div>
      <div class="numbers-grid">
        <div v-for="n in numbers" :key="'s11'+n" class="number-item" :class="{selected: selSingle.includes(n)}" @click="toggle(selSingle,n)">{{ n }}</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'

const props = defineProps({ type: { type: String, default: '120' } })
const emits = defineEmits(['update:betCount','update:selectedData'])

const numbers = ref([0,1,2,3,4,5,6,7,8,9])
const selAll = ref([])       // 120
const selDouble = ref([])    // 二重
const selSingle = ref([])
const selTriple = ref([])
const selQuad = ref([])

function toggle(list, n){
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
  let cnt = 0
  if(props.type==='120'){
    cnt = comb(selAll.value.length,5)
  }else if(props.type==='60'){

    const doubles = selDouble.value
    const singles = selSingle.value
    for(const d of doubles){
      const k = singles.filter(s=>s!==d).length
      cnt += comb(k,3)
    }
  }else if(props.type==='30'){

    const doubles = selDouble.value
    const singles = selSingle.value
    for(let i=0;i<doubles.length;i++){
      for(let j=i+1;j<doubles.length;j++){
        const k = singles.filter(s=>s!==doubles[i] && s!==doubles[j]).length
        cnt += k
      }
    }
  }else if(props.type==='20'){

    const triples = selTriple.value
    const singles = selSingle.value
    for(const t of triples){
      const k = singles.filter(s=>s!==t).length
      cnt += comb(k,2)
    }
  }else if(props.type==='10'){

    const triples = selTriple.value
    const doubles = selDouble.value
    for(const t of triples){
      cnt += doubles.filter(d=>d!==t).length
    }
  }else if(props.type==='5'){

    const quads = selQuad.value
    const singles = selSingle.value
    for(const q of quads){
      cnt += singles.filter(s=>s!==q).length
    }
  }
  emits('update:betCount', cnt)
  emits('update:selectedData', {
    all: selAll.value, double: selDouble.value, single: selSingle.value, triple: selTriple.value, quad: selQuad.value
  })
}

function clear(){ selAll.value=[]; selDouble.value=[]; selSingle.value=[]; selTriple.value=[]; selQuad.value=[]; sync() }
defineExpose({ clear })
watch(()=>props.type, clear)
onMounted(sync)
</script>

<style lang="less" scoped>
@import "@/assets/theme.less";

.group-five {
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


