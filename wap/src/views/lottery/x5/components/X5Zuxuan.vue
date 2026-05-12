<template>
  <div class="x5-zuxuan">
    <div class="group-box">
      <div class="box-header">
        <div class="title">组选号码</div>
        <div class="desc">至少选择{{ min }}个号码 <span class="odds">赔率: 19.6</span></div>
      </div>
      
      <div class="ball-grid">
        <div 
          v-for="item in ballList" 
          :key="item.val" 
          class="ball-wrapper"
          @click="toggleSelector(item.val)"
        >
            <div class="ball-item" :class="{ active: selectedList.includes(item.val) }">
              {{ item.label }}
            </div>
            <div class="omission">遗{{ item.omission }}</div>
        </div>
      </div>
      
      <div class="filter-bar">
        <div class="tool-btn" @click="filter('all')">全</div>
        <div class="tool-btn" @click="filter('big')">大</div>
        <div class="tool-btn" @click="filter('small')">小</div>
        <div class="tool-btn" @click="filter('odd')">奇</div>
        <div class="tool-btn" @click="filter('even')">偶</div>
        <div class="tool-btn tool-clear" @click="clear">清</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, computed } from 'vue'

const props = defineProps({
  min: { type: Number, default: 2 },
  type: { type: String, default: 'zux2' }
})

const emit = defineEmits(['update:betCount', 'update:selectedData'])

const selectedList = ref([])

const ballList = computed(() => {
    return Array.from({length: 11}, (_, i) => {
        const n = i + 1
        return {
            val: n,
            label: n < 10 ? '0'+n : ''+n,
            omission: Math.floor(Math.random() * 15)
        }
    })
})

function toggleSelector(n) {
  const i = selectedList.value.indexOf(n)
  if (i > -1) selectedList.value.splice(i, 1)
  else selectedList.value.push(n)
  selectedList.value.sort((a,b) => a-b)
  sync()
}

function filter(type) {
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
  selectedList.value = res
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
  const n = selectedList.value.length
  const k = props.min
  let count = 0
  
  if (props.type === 'bdw') {
      count = n
  } else {
      count = comb(n, k)
  }
  
  const dataStr = selectedList.value.map(num => num < 10 ? '0' + num : '' + num).join(',')
  
  emit('update:betCount', count)
  emit('update:selectedData', dataStr)
}

function clear() {
  selectedList.value = []
  sync()
}

defineExpose({ clear })

watch(() => props.min, sync)
onMounted(sync)
</script>

<style lang="less" scoped>
@import "@/assets/theme.less";

.x5-zuxuan {
  padding: 12px 16px;
}

.group-box {
  background: #fff;
  border-radius: 12px;
  padding: 16px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
  margin-bottom: 12px;
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
