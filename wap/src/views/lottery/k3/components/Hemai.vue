<template>
  <div v-if="show" class="hemai-wrapper">
    <div class="hemai-mask" @click="close"></div>
    <div class="hemai-container animated linearTop">
      <div class="hemai-title">发起合买</div>
      
      <div class="hemai-content">
        <div class="hemai-options">
          <div class="option-title">公开类型</div>
          <div class="option-group">
            <div 
              v-for="item in privacyOptions" 
              :key="item.value" 
              :class="['option-item', { active: formData.showtype === item.value }]" 
              @click="formData.showtype = item.value">
              {{ item.label }}
            </div>
          </div>
        </div>

        <div class="hemai-input-group">
          <div class="input-row">
            <span>我要分为：</span>
            <input type="number" v-model.number="formData.fenshu" placeholder="份数" @input="onFenshuChange">
            <span>份，每份 <i class="money">￥{{ perMoney.toFixed(2) }}</i> 元</span>
          </div>
          <div class="input-row">
            <span>我要认购：</span>
            <input type="number" v-model.number="formData.rengou" placeholder="份数" @input="onRengouChange">
            <span>份，共 <i class="money">￥{{ rengouMoney.toFixed(2) }}</i> 元 ({{ rengouPercent }}%)</span>
          </div>
          <div class="input-row baodi-row">
            <div :class="['baodi-check', { active: formData.isbaodi }]" @click="toggleBaodi"></div>
            <span>我要保底：</span>
            <input type="number" v-model.number="formData.baodi" placeholder="份数" :disabled="!formData.isbaodi" @input="onBaodiChange">
            <span>份，共 <i class="money">￥{{ baodiMoney.toFixed(2) }}</i> 元 ({{ baodiPercent }}%)</span>
          </div>
        </div>

        <div class="hemai-summary">
          总金额: <span class="total-money">￥{{ totalPayment.toFixed(2) }}</span>
        </div>
      </div>

      <div class="hemai-footer">
        <button class="btn-cancel" @click="close">取消</button>
        <button class="btn-submit" @click="submitHemai">确认发起</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { handleValidationError } from '@/utils/errorHandler';

const props = defineProps({
  show: Boolean,
  totalAmount: {
    type: Number,
    default: 0
  }
});

const emit = defineEmits(['close', 'submit']);

const formData = ref({
  showtype: 0, // 公开类型: 0完全公开, 1开奖后公开, 2仅跟单人可看, 3完全保密
  fenshu: 1,   // 总份数
  rengou: 1,   // 认购份数
  isbaodi: false, // 是否保底
  baodi: 0      // 保底份数
});

const privacyOptions = [
  { label: '完全公开', value: 0 },
  { label: '开奖后公开', value: 1 },
  { label: '仅跟单人', value: 2 },
  { label: '完全保密', value: 3 },
];

const perMoney = computed(() => {
  if (!formData.value.fenshu || formData.value.fenshu <= 0) return 0;
  return props.totalAmount / formData.value.fenshu;
});

const rengouMoney = computed(() => {
  return perMoney.value * (formData.value.rengou || 0);
});

const baodiMoney = computed(() => {
  if (!formData.value.isbaodi) return 0;
  return perMoney.value * (formData.value.baodi || 0);
});

const totalPayment = computed(() => {
  return rengouMoney.value + baodiMoney.value;
});

const rengouPercent = computed(() => {
  if (!formData.value.fenshu || formData.value.fenshu <= 0) return 0;
  return ((formData.value.rengou / formData.value.fenshu) * 100).toFixed(2);
});

const baodiPercent = computed(() => {
  if (!formData.value.isbaodi || !formData.value.fenshu || formData.value.fenshu <= 0) return 0;
  return ((formData.value.baodi / formData.value.fenshu) * 100).toFixed(2);
});

const onFenshuChange = () => {
  if (formData.value.fenshu < 1) formData.value.fenshu = 1;
  if (formData.value.rengou > formData.value.fenshu) {
    formData.value.rengou = formData.value.fenshu;
  }
  updateBaodi();
};

const onRengouChange = () => {
  if (formData.value.rengou < 1) formData.value.rengou = 1;
  if (formData.value.rengou > formData.value.fenshu) {
    formData.value.rengou = formData.value.fenshu;
  }
  updateBaodi();
};

const onBaodiChange = () => {
  const maxBaodi = formData.value.fenshu - formData.value.rengou;
  if (formData.value.baodi > maxBaodi) {
    formData.value.baodi = maxBaodi;
  }
  if (formData.value.baodi < 0) {
    formData.value.baodi = 0;
  }
};

const toggleBaodi = () => {
  formData.value.isbaodi = !formData.value.isbaodi;
  updateBaodi();
};

const updateBaodi = () => {
  if (formData.value.isbaodi) {
    formData.value.baodi = formData.value.fenshu - formData.value.rengou;
  } else {
    formData.value.baodi = 0;
  }
};

const close = () => {
  emit('close');
};

const submitHemai = () => {

  if (formData.value.fenshu <= 0) {
    handleValidationError('份数必须大于0');
    return;
  }
  if (formData.value.rengou <= 0) {
    handleValidationError('认购份数必须大于0');
    return;
  }
  if (props.totalAmount <= 0) {
    handleValidationError('总金额必须大于0');
    return;
  }
  if (perMoney.value < 1) {
    handleValidationError('每份金额不能低于1元');
    return;
  }

  const hemaiData = {
    ...formData.value,
    isbaodi: formData.value.isbaodi ? 1 : 0,
    totalAmount: props.totalAmount,
    perMoney: perMoney.value
  };
  emit('submit', hemaiData);
};

watch(() => props.show, (newVal) => {
  if (newVal) {
    formData.value.fenshu = Math.floor(props.totalAmount) || 1;
    formData.value.rengou = 1;
    updateBaodi();
  }
});

</script>

<style scoped lang="less">
.hemai-wrapper {
  position: fixed;
  inset: 0;
  z-index: 2000;
  display: flex;
  align-items: center;
  justify-content: center;
}

.hemai-mask {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.8);
  backdrop-filter: blur(4px);
  transition: opacity 0.3s;
}

.hemai-container {
  position: relative;
  width: 90%;
  max-width: 400px;
  background: #121825;
  border-radius: 16px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  
  &::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 2px;
    background: linear-gradient(90deg, #00FF9A, #00F0FF);
  }
}

.hemai-title {
  padding: 20px;
  font-size: 18px;
  font-weight: bold;
  text-align: center;
  color: #fff;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  letter-spacing: 1px;
}

.hemai-content {
  padding: 24px 20px;
}

.hemai-options {
  margin-bottom: 24px;
  
  .option-title {
    font-size: 14px;
    margin-bottom: 12px;
    color: rgba(255, 255, 255, 0.6);
  }
  
  .option-group {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
  }
}

.option-item {
  padding: 10px;
  text-align: center;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  cursor: pointer;
  font-size: 13px;
  color: rgba(255, 255, 255, 0.6);
  transition: all 0.3s;
  background: rgba(255, 255, 255, 0.02);
  
  &.active {
    background: rgba(0, 255, 154, 0.1);
    color: #00FF9A;
    border-color: #00FF9A;
    box-shadow: 0 0 10px rgba(0, 255, 154, 0.1);
  }
}

.hemai-input-group {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.input-row {
  display: flex;
  align-items: center;
  font-size: 14px;
  color: rgba(255, 255, 255, 0.8);
  
  input {
    width: 80px;
    margin: 0 10px;
    padding: 8px;
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 6px;
    text-align: center;
    color: #fff;
    font-weight: bold;
    
    &:focus {
      outline: none;
      border-color: #00FF9A;
    }
  }
  
  .money {
    color: #00FF9A;
    font-family: monospace;
    margin: 0 2px;
  }
}

.baodi-check {
  width: 18px;
  height: 18px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 4px;
  margin-right: 10px;
  cursor: pointer;
  position: relative;
  transition: all 0.2s;
  
  &.active {
    background: #00FF9A;
    border-color: #00FF9A;
    
    &::after {
      content: '';
      position: absolute;
      top: 2px;
      left: 5px;
      width: 4px;
      height: 8px;
      border: solid #000;
      border-width: 0 2px 2px 0;
      transform: rotate(45deg);
    }
  }
}

.hemai-summary {
  margin-top: 20px;
  text-align: right;
  font-size: 14px;
  color: rgba(255, 255, 255, 0.6);
  
  .total-money {
    font-size: 20px;
    font-weight: bold;
    color: #00FF9A;
    margin-left: 8px;
    text-shadow: 0 0 10px rgba(0, 255, 154, 0.3);
  }
}

.hemai-footer {
  display: flex;
  padding: 20px;
  gap: 16px;
  border-top: 1px solid rgba(255, 255, 255, 0.05);
  
  button {
    flex: 1;
    padding: 12px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.2s;
  }
  
  .btn-cancel {
    background: rgba(255, 255, 255, 0.05);
    color: rgba(255, 255, 255, 0.6);
    
    &:active {
      background: rgba(255, 255, 255, 0.1);
    }
  }
  
  .btn-submit {
    background: linear-gradient(135deg, #00FF9A, #00CC7A);
    color: #000;
    box-shadow: 0 4px 15px rgba(0, 255, 154, 0.3);
    
    &:active {
      transform: scale(0.98);
    }
  }
}

.animated {
  animation-duration: 0.3s;
  animation-fill-mode: both;
}

@keyframes slideUpFade {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.linearTop {
  animation-name: slideUpFade;
}
</style>
