<template>
  
  <div>
    
    <div class="hsycms-model-mask" :style="{ display: visible ? 'block' : 'none' }" id="mask_hemai" @click="handleClose"></div>
    
    
    <div class="faqihemai animated linearTop" :style="{ display: visible ? 'block' : 'none', zIndex: 1001 }">
      <div class="faqihemai_title">发起合买</div>
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
            <span>我要分为：</span><input type="text" id="fsInput" name="allnum" v-model.number="fenShu" @input="updateFenShuMoney"><span> 份，</span><span>每份<i style="color: rgb(255, 170, 13);" id="fsMoney">￥{{ fenShuMoney.toFixed(2) }}</i>元，</span><span>每份最低1元。</span>
          </div>
          <div class="faqihemai_r">
            <span>我要认购：</span><input type="text" id="rgInput" name="buynum" v-model.number="renGou" @input="updateRenGouPercent"><span> 份，</span><span><span>{{ renGouPercent }}</span>%</span>
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
            <span>我要保底：</span><input type="text" id="bdInput" name="baodinum" v-model.number="baoDiFenShu" :disabled="!isBaoDi" @input="updateBaoDi"><span> 份，</span><span><span id="bdMoney" style="color: rgb(255, 170, 13);">￥{{ baoDiMoney.toFixed(2) }}</span>元（<span id="bdScale">{{ baoDiPercent }}</span>%）</span>
            <div class="tips" id="bdErr" :style="{ display: bdErr ? 'block' : 'none' }">{{ bdErr }}</div>
          </div>
        </div>
      </div>
      <div class="bottom">
        <button class="noe" @click="handleClose">取 消</button><button class="two" id="buy_hemai" @click="handleConfirm">发起合买</button>
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
  { label: '完全公开', data: 'yes' },
  { label: '开奖后公开', data: 'no' },
  { label: '仅跟单人可看', data: 'no' },
  { label: '完全保密', data: 'no' }
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
    rgErr.value = '认购份数不能超过总份数'
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
    bdErr.value = '保底份数不能超过认购份数'
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
    alert('请先选择投注号码')
    return
  }
  
  if (fenShu.value < 1) {
    alert('份数不能小于1')
    return
  }
  
  if (fenShuMoney.value < 1) {
    alert('每份金额不能小于1元')
    return
  }
  
  if (renGou.value < 1 || renGou.value > fenShu.value) {
    alert('认购份数不正确')
    return
  }
  
  if (isBaoDi.value && baoDiFenShu.value > renGou.value) {
    alert('保底份数不能超过认购份数')
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
