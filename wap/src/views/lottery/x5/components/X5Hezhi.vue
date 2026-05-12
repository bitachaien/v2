<template>
  <div class="x5-hezhi">
    <div class="group-box">
      <div class="box-header">
        <div class="title">和值选择</div>
        <div class="desc">猜中开奖号码之和</div>
      </div>
      
      <div class="hezhi-grid">
        <div 
          v-for="item in hezhiList" 
          :key="item.val" 
          class="hezhi-item"
          :class="{ active: selectedList.includes(item.val) }"
          @click="toggleSelector(item.val)"
        >
          <div class="val">{{ item.val }}</div>
          <div class="odds">赔{{ item.odds }}</div>
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
import { ref, computed, watch, onMounted } from 'vue'

const props = defineProps({

  playType: { type: String, default: 'q2_zhix_hz' } 
})

const emit = defineEmits(['update:betCount', 'update:selectedData'])

const selectedList = ref([])

const config = computed(() => {
  if (props.playType.includes('_hw')) {
    return { min: 0, max: 9, mid: 5 } // HeWei 0-9
  }
  if (props.playType.startsWith('q2') || props.playType.startsWith('h2')) {
    return { min: 3, max: 21, mid: 12 } // 1+2=3, 10+11=21
  } else {
    return { min: 6, max: 30, mid: 18 } // 1+2+3=6, 9+10+11=30
  }
})

const hezhiList = computed(() => {
    const { min, max, mid } = config.value
    const arr = []
    for (let i = min; i <= max; i++) {

        const dist = Math.abs(i - mid)
        const baseOdds = 2.0
        const odds = (baseOdds + dist * 1.5).toFixed(1)
        
        arr.push({
            val: i,
            odds,
            omission: Math.floor(Math.random() * 20)
        })
    }
    return arr
})

function toggleSelector(n) {
  const i = selectedList.value.indexOf(n)
  if (i > -1) selectedList.value.splice(i, 1)
  else selectedList.value.push(n)
  selectedList.value.sort((a,b) => a-b)
  sync()
}

function filter(type) {
  const { min, max, mid } = config.value
  const all = []
  for (let i = min; i <= max; i++) all.push(i)
  
  let res = []
  switch(type) {
    case 'all': res = all; break;
    case 'big': res = all.filter(n => n >= mid); break;
    case 'small': res = all.filter(n => n < mid); break;
    case 'odd': res = all.filter(n => n % 2 !== 0); break;
    case 'even': res = all.filter(n => n % 2 === 0); break;
    case 'clear': res = []; break;
  }
  selectedList.value = res
  sync()
}

function getBetCountForSum(sum, type) {
  let count = 0
  const nums = [1,2,3,4,5,6,7,8,9,10,11]
  
  const isZux = type.includes('zux')
  const isQ2 = type.startsWith('q2') || type.startsWith('h2')
  
  if (isQ2) {

    for (let i of nums) {
      for (let j of nums) {
        if (i === j) continue
        if (i + j === sum) {
          if (isZux) {
             if (i < j) count++
          } else {
             count++
          }
        }
      }
    }
  } else {

    for (let i of nums) {
      for (let j of nums) {
        if (i === j) continue
        for (let k of nums) {
           if (k === i || k === j) continue
           if (i + j + k === sum) {
             if (isZux) {
                if (i < j && j < k) count++
             } else {
                count++
             }
           }
        }
      }
    }
  }
  return count
}

function sync() {
  let totalCount = 0
  
  if (props.playType.includes('_hw')) {

      const minSum = (props.playType.startsWith('q2') || props.playType.startsWith('h2')) ? 3 : 6
      const maxSum = (props.playType.startsWith('q2') || props.playType.startsWith('h2')) ? 21 : 30
      
      selectedList.value.forEach(tail => {
          for (let s = minSum; s <= maxSum; s++) {
              if (s % 10 === tail) {
                  totalCount += getBetCountForSum(s, props.playType)
              }
          }
      })
  } else {
      selectedList.value.forEach(sum => {
        totalCount += getBetCountForSum(sum, props.playType)
      })
  }
  
  const dataStr = selectedList.value.join(',')
  emit('update:betCount', totalCount)
  emit('update:selectedData', dataStr)
}

function clear() {
  selectedList.value = []
  sync()
}

defineExpose({ clear })

watch(() => props.playType, () => {
    clear()
})
onMounted(sync)
</script>

<style lang="less" scoped>
@import "@/assets/theme.less";

.x5-hezhi {
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
  .desc { font-size: 12px; color: #999; }
}

.hezhi-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 10px;
  justify-items: center;
  margin-bottom: 16px;
}

.hezhi-item {
  width: 100%;
  background: #fff;
  border: 1px solid #ebedf0;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 8px 0;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: 0 2px 4px rgba(0,0,0,0.02);
  
  .val { font-size: 18px; font-weight: 700; color: #333; line-height: 1.2; }
  .odds { font-size: 11px; color: #ff976a; margin-top: 2px; }
  .omission { font-size: 10px; color: #c8c9cc; margin-top: 2px; }
  
  &:active {
      transform: scale(0.96);
  }

  &.active {
    background: linear-gradient(135deg, #ff6b4e, #ee0a24);
    border-color: transparent;
    box-shadow: 0 4px 12px rgba(238, 10, 36, 0.3);
    
    .val { color: #fff; }
    .odds { color: rgba(255,255,255,0.9); }
    .omission { color: rgba(255,255,255,0.7); }
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
