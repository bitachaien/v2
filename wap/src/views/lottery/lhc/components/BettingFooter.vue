<template>
  <div class="betting-footer">
    
    <div class="footer-settings" v-if="showSettings">
      <div class="setting-item">
        <span class="label">单注:</span>
        <div class="unit-select">
          <div 
            v-for="amt in [1, 2, 5, 10]" 
            :key="amt" 
            class="unit-btn" 
            :class="{ active: unitPrice === amt }"
            @click="unitPrice = amt"
          >
            {{ amt }}
          </div>
        </div>
      </div>
      <div class="setting-item">
        <span class="label">倍数:</span>
        <van-stepper v-model="multiplier" min="1" max="999" integer />
      </div>
    </div>

    
    <div class="footer-actions">
      <div class="info-section">
        <div class="summary">
          已选 <span class="highlight">{{ betCount }}</span> 注，
          共 <span class="highlight">{{ totalMoney }}</span> 元
        </div>
        <div class="balance">余额: {{ balance }}</div>
      </div>
      
      <div class="buttons">
        
        <van-button plain type="danger" size="small" @click="$emit('clear')">清空</van-button>
        <van-button type="danger" size="normal" class="submit-btn" @click="onSubmit">立即投注</van-button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Stepper as VanStepper, Button as VanButton } from 'vant'

const props = defineProps({
  betCount: {
    type: Number,
    default: 0
  },
  balance: {
    type: String,
    default: '0.00'
  }
})

const emit = defineEmits(['clear', 'submit', 'add-to-cart', 'update:unitPrice', 'update:multiplier'])

const showSettings = ref(true)
const unitPrice = ref(1)
const multiplier = ref(1)

const totalMoney = computed(() => {
  return (props.betCount * unitPrice.value * multiplier.value).toFixed(2)
})

const onSubmit = () => {
  emit('submit', {
    unitPrice: unitPrice.value,
    multiplier: multiplier.value,
    totalMoney: totalMoney.value
  })
}

watch(unitPrice, (val) => emit('update:unitPrice', val))
watch(multiplier, (val) => emit('update:multiplier', val))

</script>

<style lang="less" scoped>
.betting-footer {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: #fff;
  box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
  z-index: 99;
}

.footer-settings {
  padding: 8px 12px;
  background: #f9f9f9;
  border-bottom: 1px solid #eee;
  display: flex;
  justify-content: space-between;
  align-items: center;

  .setting-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: #666;
  }

  .unit-select {
    display: flex;
    border: 1px solid #ddd;
    border-radius: 4px;
    overflow: hidden;

    .unit-btn {
      padding: 4px 8px;
      cursor: pointer;
      background: #fff;
      border-right: 1px solid #ddd;
      font-size: 12px;

      &:last-child {
        border-right: none;
      }

      &.active {
        background: #e1251b;
        color: #fff;
      }
    }
  }
}

.footer-actions {
  padding: 8px 12px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.info-section {
  font-size: 12px;
  color: #333;

  .summary {
    margin-bottom: 2px;
  }

  .highlight {
    color: #e1251b;
    font-weight: bold;
    font-size: 14px;
  }
  
  .balance {
    color: #999;
    transform: scale(0.9);
    transform-origin: left;
  }
}

.buttons {
  display: flex;
  gap: 8px;
  align-items: center;
  
  .submit-btn {
    min-width: 80px;
    font-weight: bold;
    background: linear-gradient(to right, #ff6034, #ee0a24);
    border: none;
  }
}
</style>
