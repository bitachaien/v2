<template>
  
  <div>
    
    <div class="hsycms-model-mask" :style="{ display: visible ? 'block' : 'none' }" id="mask_hemai" @click="handleClose"></div>
    
    
    <div class="faqihemai animated linearTop" :style="{ display: visible ? 'block' : 'none', zIndex: 1001 }">
      <div class="faqihemai_title">Tạo mua chung</div>
      <div class="faqihemai_notice">
        <div class="faqihemai_notice_div">
          <div class="leixing" style="border-bottom: 1px solid #7777771f;">
            <div 
              v-for="(item, index) in hemaiTypes" 
              :key="index"
              class="faqihemai_leix iconfont"
              :class="{ active: selectedType === index }"
              :data="item.data"
              :num="index"
              @click="selectedType = index"
            >
              <span>{{ item.label }}</span>
            </div>
          </div>
          <div class="faqihemai_f">
            <span>Chia thành:</span><input type="text" id="fsInput" name="allnum" v-model.number="fenShu" @input="updateFenShuMoney"><span> phần,</span><span>mỗi phần <i style="color: rgb(255, 170, 13);" id="fsMoney">{{ fenShuMoney.toFixed(2) }}</i>đ,</span><span>tối thiểu 1đ/phần.</span>
          </div>
          <div class="faqihemai_r">
            <span>Tôi mua:</span><input type="text" id="rgInput" name="buynum" v-model.number="renGou" @input="updateRenGouPercent"><span> phần,</span><span><span>{{ renGouPercent }}</span>%</span>
            <div class="tips" id="rgErr" :style="{ display: rgErr ? 'block' : 'none' }">{{ rgErr }}</div>
          </div>
          <div class="faqihemai_b">
            <div 
              class="wybd iconfont" 
              id="isbaodi"
              :class="{ active: isBaoDi }"
              :data="isBaoDi ? 'yes' : 'no'"
              :num="isBaoDi ? 1 : 0"
              @click="isBaoDi = !isBaoDi"
            ></div>
            <span>Tôi bảo đảm:</span><input type="text" id="bdInput" name="baodinum" v-model.number="baoDiFenShu" :disabled="!isBaoDi" @input="updateBaoDi"><span> phần,</span><span><span id="bdMoney" style="color: rgb(255, 170, 13);">{{ baoDiMoney.toFixed(2) }}</span>đ（<span id="bdScale">{{ baoDiPercent }}</span>%）</span>
            <div class="tips" id="bdErr" :style="{ display: bdErr ? 'block' : 'none' }">{{ bdErr }}</div>
          </div>
        </div>
      </div>
      <div class="bottom">
        <button class="noe" @click="handleClose">Hủy</button><button class="two" id="buy_hemai" @click="handleConfirm">Tạo mua chung</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  },
  totalAmount: {
    type: Number,
    default: 0
  }
})

const emit = defineEmits(['close', 'confirm', 'update:visible'])

const hemaiTypes = [
  { label: 'Công khai', data: 'yes' },
  { label: 'Công khai sau quay', data: 'no' },
  { label: 'Chỉ người tham gia', data: 'no' },
  { label: 'Hoàn toàn bí mật', data: 'no' }
]

const selectedType = ref(0)

const fenShu = ref(1)
const fenShuMoney = ref(0)

const renGou = ref(1)
const renGouPercent = ref(0)
const rgErr = ref('')

const isBaoDi = ref(false)
const baoDiFenShu = ref(0)
const baoDiMoney = ref(0)
const baoDiPercent = ref(0)
const bdErr = ref('')

watch(() => props.totalAmount, () => {
  updateFenShuMoney()
}, { immediate: true })

watch(isBaoDi, (newVal) => {
  if (!newVal) {
    baoDiFenShu.value = 0
    updateBaoDi()
  }
})

const updateFenShuMoney = () => {
  if (fenShu.value > 0) {
    fenShuMoney.value = props.totalAmount / fenShu.value

    if (fenShuMoney.value < 1) {
      fenShu.value = Math.floor(props.totalAmount)
      fenShuMoney.value = fenShu.value > 0 ? props.totalAmount / fenShu.value : 0
    }
  } else {
    fenShu.value = 1
    fenShuMoney.value = props.totalAmount
  }
  updateRenGouPercent()
  updateBaoDi()
}

const updateRenGouPercent = () => {
  rgErr.value = ''
  if (renGou.value < 1) {
    renGou.value = 1
  }
  if (renGou.value > fenShu.value) {
    renGou.value = fenShu.value
    rgErr.value = 'Số phần mua không được vượt quá tổng'
  }
  renGouPercent.value = fenShu.value > 0 ? Math.floor((renGou.value / fenShu.value) * 100) : 0
  

  if (isBaoDi.value) {
    const maxBaoDi = renGou.value
    if (baoDiFenShu.value > maxBaoDi) {
      baoDiFenShu.value = maxBaoDi
      updateBaoDi()
    }
  }
}

const updateBaoDi = () => {
  bdErr.value = ''
  if (!isBaoDi.value) {
    baoDiMoney.value = 0
    baoDiPercent.value = 0
    return
  }
  
  if (baoDiFenShu.value < 0) {
    baoDiFenShu.value = 0
  }
  if (baoDiFenShu.value > renGou.value) {
    baoDiFenShu.value = renGou.value
    bdErr.value = 'Số phần bảo đảm không được vượt quá số mua'
  }
  
  baoDiMoney.value = baoDiFenShu.value * fenShuMoney.value
  baoDiPercent.value = renGou.value > 0 ? Math.floor((baoDiFenShu.value / renGou.value) * 100) : 0
}

const handleClose = () => {
  emit('update:visible', false)
  emit('close')
}

const handleConfirm = () => {

  if (props.totalAmount <= 0) {
    alert('Vui lòng chọn số cược trước')
    return
  }
  
  if (fenShu.value < 1) {
    alert('Số phần không được nhỏ hơn 1')
    return
  }
  
  if (fenShuMoney.value < 1) {
    alert('Mỗi phần không được nhỏ hơn 1đ')
    return
  }
  
  if (renGou.value < 1 || renGou.value > fenShu.value) {
    alert('Số phần mua không đúng')
    return
  }
  
  if (isBaoDi.value && baoDiFenShu.value > renGou.value) {
    alert('Số phần bảo đảm không được vượt quá số mua')
    return
  }
  

  const hemaiData = {
    type: selectedType.value,
    typeName: hemaiTypes[selectedType.value].label,
    fenShu: fenShu.value,
    fenShuMoney: fenShuMoney.value,
    renGou: renGou.value,
    renGouPercent: renGouPercent.value,
    isBaoDi: isBaoDi.value,
    baoDiFenShu: baoDiFenShu.value,
    baoDiMoney: baoDiMoney.value,
    baoDiPercent: baoDiPercent.value,
    totalAmount: props.totalAmount
  }
  
  emit('confirm', hemaiData)
  handleClose()
}
</script>

<style scoped>

</style>
