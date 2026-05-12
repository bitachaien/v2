<template>
  <div class="x5-fun">
    <div class="group-box">
      <div class="box-header">
        <div class="title">{{ title }}</div>
        <div class="desc">{{ desc }}</div>
      </div>
      
      <div class="ball-grid" :class="gridClass">
        <div 
          v-for="item in options" 
          :key="item.val" 
          class="ball-item"
          :class="{ active: selectedList.includes(item.val), 'rect-item': isRect }"
          @click="toggle(item.val)"
        >
          <div class="val">{{ item.label }}</div>
          <div class="odds">赔{{ item.odds }}</div>
          <div class="omission">遗{{ item.omission }}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'

const props = defineProps({

  playType: { type: String, default: '' } 
})

const emit = defineEmits(['update:betCount', 'update:selectedData'])

const selectedList = ref([])

const playConfig = computed(() => {

  const genMock = (list) => list.map(item => ({
      ...item,
      odds: (1.95 + Math.random() * 0.5).toFixed(2),
      omission: Math.floor(Math.random() * 20)
  }))

  switch(props.playType) {
    case 'czw': // 猜中位 03-09
      return {
        title: '猜中位',
        desc: '猜中开奖号码的中间位（第3位）',
        options: genMock([3,4,5,6,7,8,9].map(n => ({ val: n<10?'0'+n:''+n, label: n<10?'0'+n:''+n }))),
        isRect: false
      }
    case 'bbc': // 必不出 01-11
      return {
        title: '必不出',
        desc: '选择1个必不出号码',
        options: genMock([1,2,3,4,5,6,7,8,9,10,11].map(n => ({ val: n<10?'0'+n:''+n, label: n<10?'0'+n:''+n }))),
        isRect: false
      }
    case 'lh_touwei': 
    case 'lh_q2':
    case 'lh_h2':
      return {
        title: '龙虎斗',
        desc: '龙 > 虎',
        options: genMock([
          { val: '龙', label: '龙' },
          { val: '虎', label: '虎' }
        ]),
        isRect: true
      }
    case 'dds': // 定单双
      return {
        title: '定单双',
        desc: '猜开奖号码中单双数的个数',
        options: genMock([
          { val: '5d0s', label: '5单0双' },
          { val: '4d1s', label: '4单1双' },
          { val: '3d2s', label: '3单2双' },
          { val: '2d3s', label: '2单3双' },
          { val: '1d4s', label: '1单4双' },
          { val: '0d5s', label: '0单5双' }
        ]),
        isRect: true
      }
    case 'niuniu':
        return {
            title: '牛牛',
            desc: '猜牛几',
            options: genMock([
                { val: 'nn', label: '牛牛' },
                { val: 'n1', label: '牛一' },
                { val: 'n2', label: '牛二' },
                { val: 'n3', label: '牛三' },
                { val: 'n4', label: '牛四' },
                { val: 'n5', label: '牛五' },
                { val: 'n6', label: '牛六' },
                { val: 'n7', label: '牛七' },
                { val: 'n8', label: '牛八' },
                { val: 'n9', label: '牛九' },
                { val: 'mn', label: '没牛' }
            ]),
            isRect: true
        }
    default:
      return { title: '', desc: '', options: [], isRect: false }
  }
})

const title = computed(() => playConfig.value.title)
const desc = computed(() => playConfig.value.desc)
const options = computed(() => playConfig.value.options)
const isRect = computed(() => playConfig.value.isRect)
const gridClass = computed(() => isRect.value ? 'grid-rect' : 'grid-ball')

function toggle(val) {
  const i = selectedList.value.indexOf(val)
  if (i > -1) selectedList.value.splice(i, 1)
  else selectedList.value.push(val)
  sync()
}

function sync() {
  const count = selectedList.value.length
  const data = selectedList.value.join(',')
  emit('update:betCount', count)
  emit('update:selectedData', data)
}

function clear() {
  selectedList.value = []
  sync()
}

defineExpose({ clear })

watch(() => props.playType, clear)
</script>

<style lang="less" scoped>
@import "@/assets/theme.less";

.x5-fun {
  padding: 12px 16px;
}

.group-box {
  background: #fff;
  border-radius: 12px;
  padding: 16px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

.box-header {
  margin-bottom: 16px;
  .title { font-size: 14px; font-weight: 600; color: #333; margin-bottom: 4px; }
  .desc { font-size: 12px; color: #999; }
}

.ball-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  justify-content: center;
}

.ball-item {
  background: #fff;
  border: 1px solid #ebedf0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  color: #333;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
  cursor: pointer;
  transition: all 0.2s;
  position: relative;
  
  .val { font-size: 16px; line-height: 1; }
  .odds { font-size: 10px; color: #ff976a; margin-top: 2px; transform: scale(0.9); }
  .omission { font-size: 10px; color: #c8c9cc; margin-top: 1px; transform: scale(0.85); }
  
  &.active {
    background: linear-gradient(135deg, #ff6b4e, #ee0a24);
    border-color: transparent;
    color: #fff;
    box-shadow: 0 4px 12px rgba(238, 10, 36, 0.3);
    transform: translateY(-2px);
    
    .odds { color: rgba(255,255,255,0.9); }
    .omission { color: rgba(255,255,255,0.7); }
  }
}

.grid-ball {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 16px 8px;
    
    .ball-item {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        
        
        
        
        width: 48px; height: 48px; // Slightly larger to fit text
    }
}

.grid-rect {
    display: grid;
    grid-template-columns: repeat(3, 1fr); // 3 columns for rects
    gap: 10px;
    
    .ball-item.rect-item {
        width: 100%;
        height: auto;
        padding: 10px 0;
        border-radius: 8px;
        
        .val { font-size: 15px; }
    }
}
</style>
