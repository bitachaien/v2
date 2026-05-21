<template>
  
  <div class="lottery-bottom selectMultiple">
    <div class="lottery-bottom-1" id="selectMultipleTId">
      <div class="bottom-moshi">
        <select class="selectMultipleCon" v-model="localBetMode" @change="$emit('update:betMode', localBetMode)">
          <option v-for="option in modeOptions" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </select>
      </div>
      <div class="bottom-beishu selectMultipleNumber">
        <i class="iconfont reduce" @click="handleReduce">&#xe796;</i>
        <span><input type="tel" class="selectMultipInput" :value="localMultiple" @input="handleInput" @keypress="validateNumber"> lần </span>
        <i class="iconfont add" @click="handleAdd">&#xe795;</i>
      </div>
    </div>
    <div class="lottery-bottom-2">
      <div>
        <span class="select_zhushu">Đã chọn <em class="zhushu" style="color: rgb(67, 67, 84); font-weight: bold;">{{ selectedCount }}</em> vé</span>,
        <span class="selectMultipleOld">Tổng <em class="selectMultipleOldMoney" style="color: rgb(67, 67, 84); font-weight: bold;">{{ totalMoney.toFixed(3) }}</em>đ</span>
        <span class="hemaijiner" :style="{ display: showHemai ? 'inline' : 'none' }"> Mua chung <em class="hemaijinerMoney" style="color: rgb(67, 67, 84); font-weight: bold;">{{ hemaiMoney.toFixed(3) }}</em>đ </span>
      </div>
      <span>Số dư: <em class="wrapRefreshShow" style="color: rgb(67, 67, 84); font-weight: bold;">{{ userBalance }}</em> đ</span>
    </div>
    <div class="lottery-bottom-3">
      <div class="hemai" @click="$emit('open-hemai')">Mua chung</div>
      <div class="bygcl goucai" style="color: rgb(166, 143, 76);" @click="$emit('open-cart')">
        <i class="lanIconNumber" id="lanIconNumbere">{{ cartCount }}</i><i class="iconfont">&#xe60e;</i> Giỏ
      </div>
      <div class="bygcl addtobetbtn" @click="$emit('add-to-cart')"><i class="iconfont">&#xe676;</i> Giỏ </div>
      <div class="kuaijie" @click="$emit('quick-bet')">Cược nhanh</div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({

  modeOptions: {
    type: Array,
    default: () => [
      { value: '1', label: '元' },
      { value: '0.1', label: '角' },
      { value: '0.01', label: '分' }
    ]
  },

  betMode: {
    type: [String, Number],
    default: '1'
  },

  multiple: {
    type: [String, Number],
    default: 1
  },

  selectedCount: {
    type: Number,
    default: 0
  },

  totalMoney: {
    type: Number,
    default: 0
  },

  showHemai: {
    type: Boolean,
    default: false
  },

  hemaiMoney: {
    type: Number,
    default: 0
  },

  userBalance: {
    type: [String, Number],
    default: '1000.00'
  },

  cartCount: {
    type: Number,
    default: 0
  },

  minMultiple: {
    type: Number,
    default: 1
  },

  maxMultiple: {
    type: Number,
    default: 9999
  }
})

const emit = defineEmits([
  'update:betMode',
  'update:multiple',
  'open-hemai',
  'open-cart',
  'add-to-cart',
  'quick-bet'
])

const localBetMode = ref(props.betMode)
const localMultiple = ref(props.multiple)

watch(() => props.betMode, (newVal) => {
  localBetMode.value = newVal
})

watch(() => props.multiple, (newVal) => {
  localMultiple.value = newVal
})

const handleReduce = () => {
  const current = parseInt(localMultiple.value) || props.minMultiple
  if (current > props.minMultiple) {
    localMultiple.value = current - 1
    emit('update:multiple', localMultiple.value)
  }
}

const handleAdd = () => {
  const current = parseInt(localMultiple.value) || props.minMultiple
  if (current < props.maxMultiple) {
    localMultiple.value = current + 1
    emit('update:multiple', localMultiple.value)
  }
}

const handleInput = (event) => {
  let value = event.target.value.replace(/[^\d]/g, '')
  if (value === '') {
    value = props.minMultiple
  } else {
    value = parseInt(value)
    if (value < props.minMultiple) value = props.minMultiple
    if (value > props.maxMultiple) value = props.maxMultiple
  }
  localMultiple.value = value
  emit('update:multiple', value)
}

const validateNumber = (event) => {
  const charCode = event.keyCode || event.which
  const charStr = String.fromCharCode(charCode)
  if (!/[\d]/.test(charStr)) {
    event.preventDefault()
    return false
  }
  return true
}
</script>

<style scoped>

</style>

