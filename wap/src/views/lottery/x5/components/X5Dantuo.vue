<template>
  <div class="x5-dantuo">
    
    <div class="group-box mb-3">
      <div class="box-header">
        <div class="title">胆码区</div>
        <div class="desc">请选择胆码 ({{ danLen }}个) <span class="odds">赔率: 19.6</span></div>
      </div>
      <div class="ball-grid">
        <div 
          v-for="item in danListComputed" 
          :key="item.val" 
          class="ball-wrapper"
          @click="toggleDan(item.val)"
        >
          <div class="ball-item" :class="{ active: danList.includes(item.val) }">
            {{ item.label }}
          </div>
          <div class="omission">遗{{ item.omission }}</div>
        </div>
      </div>
    </div>

    
    <div class="group-box">
      <div class="box-header">
        <div class="title">拖码区</div>
        <div class="desc">请选择拖码 ({{ tuoLen }}个)</div>
      </div>
      <div class="ball-grid">
        <div 
          v-for="item in tuoListComputed" 
          :key="item.val" 
          class="ball-wrapper"
          @click="toggleTuo(item.val)"
        >
          <div class="ball-item" :class="{ active: tuoList.includes(item.val) }">
            {{ item.label }}
          </div>
          <div class="omission">遗{{ item.omission }}</div>
        </div>
      </div>
      
      
      <div class="filter-bar">
        <div class="tool-btn" @click="tuoFilter('all')">全</div>
        <div class="tool-btn" @click="tuoFilter('big')">大</div>
        <div class="tool-btn" @click="tuoFilter('small')">小</div>
        <div class="tool-btn" @click="tuoFilter('odd')">奇</div>
        <div class="tool-btn" @click="tuoFilter('even')">偶</div>
        <div class="tool-btn tool-clear" @click="clear">清</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed, onMounted } from 'vue'

const props = defineProps({

  minTotal: { type: Number, default: 2 },

  maxDan: { type: Number, default: 1 } 
})
const emit = defineEmits(['update:betCount', 'update:selectedData'])

const danList = ref([])
const tuoList = ref([])

const danListComputed = computed(() => {
  return Array.from({length: 11}, (_, i) => {
    const n = i + 1
    return {
      val: n,
      label: n < 10 ? '0'+n : ''+n,
      omission: Math.floor(Math.random() * 15)
    }
  })
})

const tuoListComputed = computed(() => danListComputed.value)

const danLen = computed(() => danList.value.length)
const tuoLen = computed(() => tuoList.value.length)

function toggleDan(n) {
  const idx = danList.value.indexOf(n)
  if (idx > -1) {
    danList.value.splice(idx, 1)
  } else {

    if (danList.value.length >= props.maxDan) {

      danList.value.pop() 
    }
    danList.value.push(n)

    const tIdx = tuoList.value.indexOf(n)
    if (tIdx > -1) tuoList.value.splice(tIdx, 1)
  }
  danList.value.sort((a,b) => a-b)
  sync()
}

function toggleTuo(n) {
  const idx = tuoList.value.indexOf(n)
  if (idx > -1) {
    tuoList.value.splice(idx, 1)
  } else {
    tuoList.value.push(n)

    const dIdx = danList.value.indexOf(n)
    if (dIdx > -1) danList.value.splice(dIdx, 1)
  }
  tuoList.value.sort((a,b) => a-b)
  sync()
}

function tuoFilter(type) {
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

  res = res.filter(n => !danList.value.includes(n))
  tuoList.value = res
  sync()
}

function comb(n, k) {
  if (k < 0 || n < k) return 0
  let res = 1
  for (let i = 1; i <= k; i++) {
    res = res * (n - i + 1) / i
  }
  return Math.floor(res)
}

function sync() {
  const dLen = danList.value.length
  const tLen = tuoList.value.length
  

  let count = 0
  if (dLen > 0 && (dLen + tLen) >= props.minTotal) {
      count = comb(tLen, props.minTotal - dLen)
  }

  const dStr = danList.value.map(n => n<10?'0'+n:''+n).join(',')
  const tStr = tuoList.value.map(n => n<10?'0'+n:''+n).join(',')
  const dataStr = count > 0 ? `${dStr}#${tStr}` : ''
  
  emit('update:betCount', count)
  emit('update:selectedData', dataStr)
}

function clear() {
  danList.value = []
  tuoList.value = []
  sync()
}

defineExpose({ clear })

watch(() => props.minTotal, clear) // Clear when switching play type
onMounted(sync)
</script>

<style lang="less" scoped>
@import "@/assets/theme.less";

.x5-dantuo {
  padding: 12px 16px;
}

.mb-3 { margin-bottom: 12px; }

.group-box {
  background: #fff;
  border-radius: 12px;
  padding: 16px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
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
