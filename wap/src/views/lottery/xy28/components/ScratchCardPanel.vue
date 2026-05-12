<template>
  <div class="scratch-card-panel" v-show="show">
    <div class="panel-left">
      <div class="panel-text">第<span class="blue-text">{{ currentIssue }}</span>期</div>
      <div class="status-btn" :class="countdownStatus">
        <template v-if="countdownStatus === 'sealed'">封盘中</template>
        <template v-else-if="countdownStatus === 'drawing'">开奖中</template>
        <template v-else>{{ countdownText }}</template>
      </div>
      <div class="refresh-text" @click="resetScratchCard">点击刷新</div>
    </div>
    <div class="panel-right">
      <div class="panel-text">第<span class="blue-text">{{ lastIssue }}</span>期开奖结果</div>
      <div class="scratch-area" ref="scratchAreaRef">
        
        <div class="scratch-result">
          <div class="result-balls-simple">
            <span class="ball">{{ lastCode[0] }}</span>+<span class="ball">{{ lastCode[1] }}</span>+<span class="ball">{{ lastCode[2] }}</span>=<span class="ball red">{{ lastSum }}</span>
          </div>
          <div class="result-text-overlay">{{ lastResultText }}</div>
        </div>
        
        <canvas ref="scratchCanvas" class="scratch-canvas"
          @mousedown="startScratch"
          @mousemove="scratches"
          @mouseup="stopScratch"
          @touchstart="startScratch"
          @touchmove="scratches"
          @touchend="stopScratch"
        ></canvas>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'

const props = defineProps({
  show: Boolean,
  currentIssue: String,
  lastIssue: String,
  lastCode: {
    type: Array,
    default: () => [0, 0, 0]
  },
  countdownStatus: {
    type: String,
    default: 'countdown'  
  },
  countdownText: {
    type: String,
    default: '--:--'
  }
})

const emit = defineEmits(['update:show'])

const lastSum = computed(() => props.lastCode.reduce((a, b) => a + b, 0))

const lastResultText = computed(() => {
  const val = lastSum.value
  if (isNaN(val)) return ''
  let text = ''
  text += val >= 14 ? '大' : '小'
  text += val % 2 === 0 ? '双' : '单'
  return text
})

const scratchCanvas = ref(null)
const scratchAreaRef = ref(null)
let isScratching = false
let ctx = null

function initScratchCard() {
  if (!scratchCanvas.value) return
  const canvas = scratchCanvas.value
  const parent = scratchAreaRef.value
  if (!parent) return
  

  canvas.width = parent.offsetWidth
  canvas.height = parent.offsetHeight
  
  ctx = canvas.getContext('2d')
  

  ctx.globalCompositeOperation = 'source-over'
  ctx.fillStyle = '#b3b5c3'
  ctx.fillRect(0, 0, canvas.width, canvas.height)
  

  ctx.font = 'bold 14px Arial'
  ctx.fillStyle = '#fff'
  ctx.textAlign = 'center'
  ctx.textBaseline = 'middle'
  ctx.fillText('已有结果请刮开涂层', canvas.width / 2, canvas.height / 2)
  

  ctx.globalCompositeOperation = 'destination-out'
}

function getPos(e) {
  const canvas = scratchCanvas.value
  const rect = canvas.getBoundingClientRect()
  let x, y
  if (e.changedTouches) {
    x = e.changedTouches[0].clientX - rect.left
    y = e.changedTouches[0].clientY - rect.top
  } else {
    x = e.clientX - rect.left
    y = e.clientY - rect.top
  }
  return { x, y }
}

function startScratch(e) {
  isScratching = true
  scratches(e)
}

function scratches(e) {
  if (!isScratching || !ctx) return
  
  const { x, y } = getPos(e)
  
  ctx.beginPath()
  ctx.arc(x, y, 15, 0, Math.PI * 2)
  ctx.fill()
}

function stopScratch() {
  isScratching = false
}

function resetScratchCard() {
  initScratchCard()
}

watch(() => props.show, (newVal) => {
  if (newVal) {
    setTimeout(() => {
      initScratchCard()
    }, 100)
  }
})

onMounted(() => {
  if (props.show) {
    setTimeout(() => {
      initScratchCard()
    }, 500)
  }
})

defineExpose({
  resetScratchCard
})
</script>

<style lang="less" scoped>
.scratch-card-panel {
  margin: 0;
  background: #fff;
  border-radius: 0;
  display: flex;
  padding: 15px 10px;
  gap: 10px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  position: relative;
  z-index: 20;
  border-bottom: 1px solid #f0f0f0;
  
  .panel-left {
    flex: 0 0 40%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border-right: 1px solid #f0f0f0;
    gap: 8px;
    
    .panel-text {
      font-size: 13px;
      color: #333;
      .blue-text { color: #5691fe; }
    }
    
    .status-btn {
      color: #fff;
      font-size: 16px;
      font-weight: bold;
      padding: 6px 20px;
      border-radius: 4px;
      min-width: 80px;
      text-align: center;
      

      background: linear-gradient(135deg, #5691fe, #1e88e5);
      

      &.sealed {
        background: linear-gradient(135deg, #ff9800, #f57c00);
      }
      

      &.drawing {
        background: linear-gradient(135deg, #5c6275, #424755);
      }
    }
    
    .refresh-text {
      color: #5691fe;
      font-size: 13px;
      cursor: pointer;
    }
  }
  
  .panel-right {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    
    .panel-text {
      font-size: 13px;
      color: #333;
      .blue-text { color: #5691fe; }
    }
    
    .scratch-area {
      width: 100%;
      height: 60px;
      position: relative;
      background: #eee;
      border-radius: 6px;
      overflow: hidden;
      
      .scratch-result {
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 4px;
        
        .result-balls-simple {
          display: flex;
          align-items: center;
          gap: 2px;
          font-weight: bold;
          color: #333;
          
          .ball {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            background: #5691fe;
            color: #fff;
            border-radius: 50%;
            font-size: 12px;
            
            &.red {
              background: #f5222d;
            }
          }
        }
        
        .result-text-overlay {
          font-size: 14px;
          font-weight: bold;
          background: #e6f7ff;
          padding: 2px 8px;
          border-radius: 4px;
          color: #5691fe;
        }
      }
      
      .scratch-canvas {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 2;
        cursor: crosshair;
      }
    }
  }
}
</style>
