<template>
  <div class="bottom-bar">
    <div class="top-row">
      <div class="left">
        <van-icon name="delete" class="trash" />
        <span>{{ betCount }}注 {{ totalMoney.toFixed(2) }}元</span>
      </div>
      <div class="right">
        <div class="mode">
          <van-dropdown-menu>
            <van-dropdown-item v-model="innerMode" :options="modeOptions" />
          </van-dropdown-menu>
        </div>
        <div class="multiple-stepper">
          <van-icon name="minus" @click="decrease" />
          <span class="multiple-text">{{ innerMultiple }} 倍</span>
          <van-icon name="plus" @click="increase" />
        </div>
      </div>
    </div>
    <div class="summary-row">
      <div>已选 <b>{{ betCount }}</b> 注, 共计 <b>{{ totalMoney.toFixed(3) }}</b> 元</div>
      <div>余额: <b class="balance">{{ userBalance }}</b> 元</div>
    </div>
    <div class="btn-row">
      <button class="btn btn-hemai" @click="$emit('open-cart')">发起合买</button>
      <button class="btn btn-view" @click="$emit('open-cart')">
        购彩篮
        <span v-if="Number(cartCount) > 0" class="badge">{{ cartCount }}</span>
      </button>
      <button class="btn btn-add" @click="$emit('add-to-cart')">添加购彩篮</button>
      <button class="btn btn-submit" @click="$emit('quick-bet')">快速投注</button>
    </div>
  </div>
  
</template>

<script setup>
import { computed, watch, ref } from 'vue'

const props = defineProps({
  betCount: { type: Number, default: 0 },
  userBalance: { type: [String, Number], default: '0.000' },
  modelValue: { type: Object },
  mode: { type: String, default: '1' },
  multiple: { type: [String, Number], default: 1 },
  cartCount: { type: [String, Number], default: 0 }
})

const emits = defineEmits(['update:mode', 'update:multiple', 'open-cart', 'add-to-cart', 'quick-bet'])

const innerMode = ref(String(props.mode))
const innerMultiple = ref(parseInt(props.multiple) || 1)

watch(() => props.mode, v => innerMode.value = String(v))
watch(() => props.multiple, v => innerMultiple.value = parseInt(v) || 1)

watch(innerMode, v => emits('update:mode', v))
watch(innerMultiple, v => emits('update:multiple', v))

const modeOptions = [
  { text: '元', value: '1' },
  { text: '角', value: '0.1' },
  { text: '分', value: '0.01' }
]

const totalMoney = computed(() => (props.betCount || 0) * parseFloat(innerMode.value || '1') * (innerMultiple.value || 1) * 2)

function decrease() { if (innerMultiple.value > 1) innerMultiple.value-- }
function increase() { if (innerMultiple.value < 9999) innerMultiple.value++ }
</script>

<style scoped>
.bottom-bar { position: fixed; left: 0; right: 0; bottom: 0; background: #fff; z-index: 999; padding-bottom: env(safe-area-inset-bottom); border-top: 1px solid #eaeaea; }

.top-row { display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; color: #666; font-size: 14px; background: #f8f8f8; }
.left { display: flex; align-items: center; gap: 8px; }
.trash { color: #ffaf36; }
.right { display: flex; align-items: center; gap: 10px; }
.mode :deep(.van-dropdown-menu) { background: transparent; }
.mode :deep(.van-dropdown-menu__bar) { background: transparent; box-shadow: none; }
.mode :deep(.van-dropdown-menu__title) { color: #666; }
.multiple-stepper { display: flex; align-items: center; gap: 10px; }
.multiple-stepper .van-icon { width: 26px; height: 26px; display: flex; align-items: center; justify-content: center; background: #f0f0f0; border-radius: 50%; color: #666; }
.multiple-text { min-width: 56px; text-align: center; font-weight: 500; color: #333; }

.summary-row { display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; font-size: 12px; color: #666; border-top: 1px solid #f0f0f0; }
.balance { color: #434354; font-weight: 700; }

.btn-row { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 0; }
.btn { border: none; border-radius: 0; padding: 12px 0; font-size: 14px; font-weight: 600; height: 40px; }
.btn-hemai { background: rgba(67, 67, 84, 0.5); color: #fff; }
.btn-view { background: #fff; color: #434354; position: relative; }
.btn-add { background: #3a3a3a; color: #fff; }
.btn-submit { background: rgba(166, 143, 76, 0.9); color: #fff; }
.badge { position: absolute; right: 10px; top: -6px; background: #f15e22; color: #fff; border-radius: 999px; padding: 0 6px; height: 18px; line-height: 18px; font-size: 12px; }
</style>


