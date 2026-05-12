<template>
  <div>
    
    <transition name="fade">
      <div class="quick-overlay" v-show="show" @click="close"></div>
    </transition>
    
    
    <transition name="slide-up">
      <div class="quick-keyboard" v-show="show">
        
        <div class="input-area">
          <div class="input-wrapper">
            <div class="input-display" :class="{ placeholder: !localValue }">
              {{ localValue || '请编辑下注内容' }}
            </div>
            <button class="clear-btn" v-if="localValue" @click="updateValue('')">
              <van-icon name="cross" />
            </button>
          </div>
        </div>
        
        
        <div class="scroll-area">
          <button class="scroll-arrow left" @click="scrollList(-200)">
            <van-icon name="arrow-left" />
          </button>
          <div class="scroll-list" ref="scrollRef">
            <button 
              class="scroll-item" 
              v-for="item in scrollItems" 
              :key="item.num"
              @click.prevent="appendPlay(String(item.num))"
            >
              <span class="num">{{ item.num }}</span>
              <span class="odds">{{ item.odds }}</span>
            </button>
          </div>
          <button class="scroll-arrow right" @click="scrollList(200)">
            <van-icon name="arrow" />
          </button>
        </div>
        
        
        <div class="keypad-grid">
          
          <button class="key-btn utility" @click="close">取消下注</button>
          <button class="key-btn" @click="appendPlay('豹子')"><span>豹子</span><span class="sub">{{ getOdds('baozi') }}</span></button>
          <button class="key-btn" @click="appendPlay('顺子')"><span>顺子</span><span class="sub">{{ getOdds('shunzi') }}</span></button>
          <button class="key-btn" @click="appendPlay('对子')"><span>对子</span><span class="sub">{{ getOdds('duizi') }}</span></button>
          <button class="key-btn utility" @click="backspace"><van-icon name="revoke" /></button>

          
          <button class="key-btn" @click="appendPlay('大')"><span>大</span><span class="sub">{{ getOdds('big') }}</span></button>
          <button class="key-btn number" @click="appendText('1')">1</button>
          <button class="key-btn number" @click="appendText('2')">2</button>
          <button class="key-btn number" @click="appendText('3')">3</button>
          <button class="key-btn" @click="appendPlay('大单')"><span>大单</span><span class="sub">{{ getOdds('big_odd') }}</span></button>

          
          <button class="key-btn" @click="appendPlay('小')"><span>小</span><span class="sub">{{ getOdds('small') }}</span></button>
          <button class="key-btn number" @click="appendText('4')">4</button>
          <button class="key-btn number" @click="appendText('5')">5</button>
          <button class="key-btn number" @click="appendText('6')">6</button>
          <button class="key-btn" @click="appendPlay('大双')"><span>大双</span><span class="sub">{{ getOdds('big_even') }}</span></button>

          
          <button class="key-btn" @click="appendPlay('单')"><span>单</span><span class="sub">{{ getOdds('odd') }}</span></button>
          <button class="key-btn number" @click="appendText('7')">7</button>
          <button class="key-btn number" @click="appendText('8')">8</button>
          <button class="key-btn number" @click="appendText('9')">9</button>
          <button class="key-btn" @click="appendPlay('小单')"><span>小单</span><span class="sub">{{ getOdds('small_odd') }}</span></button>

          
          <button class="key-btn" @click="appendPlay('双')"><span>双</span><span class="sub">{{ getOdds('even') }}</span></button>
          <button class="key-btn" @click="appendPlay('极大')"><span>极大</span><span class="sub">{{ getOdds('jida') }}</span></button>
          <button class="key-btn number" @click="appendText('0')">0</button>
          <button class="key-btn" @click="appendPlay('极小')"><span>极小</span><span class="sub">{{ getOdds('jixiao') }}</span></button>
          <button class="key-btn" @click="appendPlay('小双')"><span>小双</span><span class="sub">{{ getOdds('small_even') }}</span></button>

          
          <button class="key-btn utility" @click="appendText(' ')">空格</button>
          <button class="key-btn" @click="appendPlay('龙')"><span>龙</span><span class="sub">{{ getOdds('long') }}</span></button>
          <button class="key-btn" @click="appendPlay('虎')"><span>虎</span><span class="sub">{{ getOdds('hu') }}</span></button>
          <button class="key-btn" @click="appendPlay('豹')"><span>豹</span><span class="sub">{{ getOdds('bao') }}</span></button>
          <button class="key-btn submit" :class="{ disabled: isSealed }" @click="submit">{{ isSealed ? '封盘中' : '确认下注' }}</button>
        </div>
    </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, computed } from 'vue'
import { showToast } from 'vant'
import { xy28Api } from '@/api'

const props = defineProps({
  show: Boolean,
  modelValue: { type: String, default: '' },
  isSealed: { type: Boolean, default: false },
  lotteryCode: { type: String, default: '' }
})

const apiOddsMap = ref({})

function getOdds(playid) {
  return apiOddsMap.value[playid]?.toFixed(2) || '--'
}

async function loadPlayTypes() {
  if (!props.lotteryCode) return
  try {
    const res = await xy28Api.getPlayTypes(props.lotteryCode)
    if (res.code === 0 && res.data?.playTypes) {
      const oddsData = {}
      res.data.playTypes.forEach(play => {
        oddsData[play.playType] = play.odds
      })
      apiOddsMap.value = oddsData
    }
  } catch (e) {
    console.error('[QuickBetKeyboard] 加载玩法赔率失败:', e)
  }
}

const scrollItems = computed(() => {
  return Array.from({ length: 28 }, (_, i) => ({
    num: i,
    odds: getOdds(`tm_${i}`)
  }))
})

onMounted(() => {
  loadPlayTypes()
})

watch(() => props.lotteryCode, () => {
  loadPlayTypes()
})

const emit = defineEmits(['update:show', 'update:modelValue', 'submit'])

const scrollRef = ref(null)

const localValue = ref(props.modelValue)

watch(() => props.modelValue, (val) => {
  if (val !== localValue.value) {
    localValue.value = val
  }
})

function close() {
  emit('update:show', false)
}

function updateValue(val) {
  localValue.value = val
  emit('update:modelValue', val)
}

function appendText(str) {
  const newVal = localValue.value + str
  localValue.value = newVal
  emit('update:modelValue', newVal)
}

function appendPlay(play) {
  let current = localValue.value

  if (current && /\d$/.test(current)) {
    current += '|'
  }

  const suffix = /^\d+$/.test(play) ? '点:' : ':'
  const newVal = current + play + suffix
  localValue.value = newVal
  emit('update:modelValue', newVal)
}

function backspace() {
  if (localValue.value.length > 0) {
    const newVal = localValue.value.slice(0, -1)
    localValue.value = newVal
    emit('update:modelValue', newVal)
  }
}

function submit() {

  if (props.isSealed) {
    showToast('封盘中，暂停投注')
    return
  }
  emit('submit')
}

function scrollList(amount) {
  if (scrollRef.value) {
    scrollRef.value.scrollBy({ left: amount, behavior: 'smooth' })
  }
}
</script>

<style lang="less" scoped>
.quick-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 2000;
}

.quick-keyboard {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 2001;
  background: #dfe2e6;
  height: 480px;
  padding-bottom: env(safe-area-inset-bottom, 0px);
  will-change: transform; 
}

.input-area {
  padding: 12px 8px;
  background: #dfe2e6;
  display: flex;
  justify-content: center;
  
  .input-wrapper {
    position: relative;
    width: 417px;
    max-width: 100%;
    
    .input-display {
      width: 100%;
      height: 44px;
      padding: 0 40px 0 16px;
      font-size: 18px;
      border-radius: 22px;
      border: 1px solid #d1d5db;
      background: #fff;
      color: #374151;
      display: flex;
      align-items: center;
      box-sizing: border-box;
      box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);
      white-space: nowrap;
      overflow: hidden;
      
      &.placeholder { color: #d1d5db; }
    }
    
    .clear-btn {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      width: 24px;
      height: 24px;
      border-radius: 50%;
      background: #9ca3af;
      color: #fff;
      border: none;
      display: flex;
      align-items: center;
      justify-content: center;
      
      &:active { background: #6b7280; }
      :deep(.van-icon) { font-size: 14px; }
    }
  }
}

.scroll-area {
  position: relative;
  margin-bottom: 8px;
  padding: 0 4px;
  
  .scroll-arrow {
    position: absolute;
    top: 0;
    bottom: 0;
    width: 24px;
    z-index: 10;
    display: flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    border: none;
    color: #6b7280;
    
    &.left { left: 0; background: linear-gradient(to right, #dfe2e6, transparent); }
    &.right { right: 0; background: linear-gradient(to left, #dfe2e6, transparent); }
  }
  
  .scroll-list {
    display: flex;
    gap: 6px;
    overflow-x: auto;
    padding: 0 24px;
    -webkit-overflow-scrolling: touch;
    
    &::-webkit-scrollbar { display: none; }
    
    .scroll-item {
      flex-shrink: 0;
      width: 46.78px;
      height: 41.27px;
      background: #fff;
      border: none;
      border-radius: 8px;
      border-bottom: 2px solid #e5e7eb;
      box-shadow: 0 1px 2px rgba(0,0,0,0.05);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      
      .num { font-size: 16px; font-weight: 700; color: #1f2937; line-height: 1; }
      .odds { font-size: 10px; color: #6b7280; margin-top: 4px; }
      
      &:active { transform: scale(0.95); }
    }
  }
}

.keypad-grid {
  display: grid;
  grid-template-columns: 80.27px 80.27px 80.27px 80.27px 80.27px;
  grid-template-rows: repeat(6, auto);
  gap: 4px;
  padding: 0 8px 8px;
  justify-content: center;
  
  .key-btn {
    width: 80.27px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: #fff;
    border: none;
    border-radius: 8px;
    border-bottom: 2px solid #e5e7eb;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    transition: all 0.1s;
    height: 45.36px;
    
    span { font-size: 14px; font-weight: 500; color: #1f2937; }
    .sub { font-size: 10px; color: #6b7280; }
    
    &:active {
      transform: translateY(2px);
      border-bottom: 0;
    }
    
    &.number {
      font-size: 24px;
      font-weight: 500;
      color: #4da6ff;
    }
    
    &.utility {
      background: #9ca3af;
      border-bottom-color: #808794;
      color: #374151;
      font-size: 12px;
      font-weight: 700;
      
      :deep(.van-icon) { font-size: 20px; color: #374151; }
    }
    
    &.submit {
      background: #4da6ff;
      border-bottom-color: #3d8cdb;
      
      span { color: #fff; font-size: 14px; }
      
      &.disabled {
        background: #999;
        border-bottom-color: #777;
        pointer-events: none;
        cursor: not-allowed;
      }
    }
  }
  
  .key-btn:nth-child(5n+1), .key-btn:nth-child(5n) {
    height: 41.27px;
  }
}

.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.slide-up-enter-active,
.slide-up-leave-active {
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.slide-up-enter-from,
.slide-up-leave-to {
  transform: translateY(100%);
}

.slide-up-enter-to,
.slide-up-leave-from {
  transform: translateY(0);
}
</style>
