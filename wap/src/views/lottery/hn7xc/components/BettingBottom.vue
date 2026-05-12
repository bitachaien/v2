<template>
  <div class="betting-footer">
    
    <div class="footer-settings" v-if="showSettings">
      <div class="setting-item">
        <span class="label">模式:</span>
        <div class="unit-select">
          <div 
            v-for="mode in modes" 
            :key="mode.value" 
            class="unit-btn" 
            :class="{ active: currentMode === mode.value }"
            @click="currentMode = mode.value"
          >
            {{ mode.label }}
          </div>
        </div>
      </div>
      <div class="setting-item">
        <span class="label">倍数:</span>
        <van-stepper v-model="multiplier" min="1" max="999" integer input-width="40px" button-size="22px" />
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
        <div class="cart-btn-wrapper" @click="$emit('view-cart')">
          <van-icon name="shopping-cart-o" size="20" />
          <div class="badge" v-if="cartCount > 0">{{ cartCount }}</div>
        </div>
        <van-button plain type="danger" size="small" class="action-btn" @click="$emit('add-to-cart')">加入号码篮</van-button>
        <van-button type="danger" size="normal" class="submit-btn action-btn" @click="onSubmit">立即投注</van-button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Stepper as VanStepper, Button as VanButton, Icon as VanIcon } from 'vant'

const props = defineProps({
  betCount: {
    type: Number,
    default: 0
  },
  balance: {
    type: String,
    default: '0.00'
  },
  cartCount: {
    type: Number,
    default: 0
  }
})

const emit = defineEmits(['clear', 'submit', 'add-to-cart', 'view-cart', 'update:mode', 'update:multiplier'])

const showSettings = ref(true)
const multiplier = ref(1)
const currentMode = ref(1)

const modes = [
  { label: '元', value: 1 },
  { label: '角', value: 0.1 },
  { label: '分', value: 0.01 }
]

const totalMoney = computed(() => {
  return (props.betCount * currentMode.value * multiplier.value).toFixed(2)
})

const onSubmit = () => {
  emit('submit', {
    mode: currentMode.value,
    multiplier: multiplier.value,
    totalMoney: totalMoney.value
  })
}

watch(currentMode, (val) => emit('update:mode', val))
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
  padding-bottom: env(safe-area-inset-bottom);
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
      padding: 2px 8px;
      cursor: pointer;
      background: #fff;
      border-right: 1px solid #ddd;
      font-size: 12px;
      min-width: 30px;
      text-align: center;

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
  
  .cart-btn-wrapper {
    position: relative;
    padding: 8px;
    margin-right: 4px;
    color: #666;
    
    .badge {
      position: absolute;
      top: 0;
      right: 0;
      background: #e1251b;
      color: #fff;
      font-size: 10px;
      border-radius: 10px;
      min-width: 16px;
      height: 16px;
      line-height: 16px;
      text-align: center;
      padding: 0 4px;
    }
  }
  
  .action-btn {
    height: 36px;
    padding: 0 12px;
  }
  
  .submit-btn {
    font-weight: bold;
    background: linear-gradient(to right, #ff6034, #ee0a24);
    border: none;
  }
}
</style>
