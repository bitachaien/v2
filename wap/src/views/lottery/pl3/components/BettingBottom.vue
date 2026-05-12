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
        <van-stepper v-model="multiplier" min="1" max="999" integer input-width="40px" button-size="22px" theme="round" />
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
          <van-icon name="shopping-cart-o" size="22" />
          <div class="badge" v-if="cartCount > 0">{{ cartCount }}</div>
        </div>
        <van-button plain type="primary" size="small" class="action-btn add-btn" @click="$emit('add-to-cart')">加入号码篮</van-button>
        <van-button type="primary" size="normal" class="submit-btn action-btn" @click="onSubmit">立即投注</van-button>
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
  box-shadow: 0 -4px 20px rgba(0,0,0,0.08);
  z-index: 99;
  padding-bottom: env(safe-area-inset-bottom);
  border-radius: 16px 16px 0 0;
}

.footer-settings {
  padding: 12px 16px;
  background: #f8faff;
  border-bottom: 1px solid #edf2f7;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-radius: 16px 16px 0 0;

  .setting-item {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
    color: #666;
    
    .label {
      font-weight: 500;
    }
  }

  .unit-select {
    display: flex;
    border: 1px solid #e6e6e6;
    border-radius: 20px;
    overflow: hidden;
    background: #fff;

    .unit-btn {
      padding: 4px 12px;
      cursor: pointer;
      background: #fff;
      border-right: 1px solid #eee;
      font-size: 12px;
      min-width: 32px;
      text-align: center;
      transition: all 0.2s;

      &:last-child {
        border-right: none;
      }

      &.active {
        background: #1890ff;
        color: #fff;
      }
      
      &:hover:not(.active) {
        background: #f0f2f5;
      }
    }
  }
}

.footer-actions {
  padding: 10px 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #fff;
}

.info-section {
  font-size: 12px;
  color: #333;

  .summary {
    margin-bottom: 4px;
    font-size: 13px;
  }

  .highlight {
    color: #1890ff;
    font-weight: bold;
    font-size: 15px;
    font-family: 'DIN Alternate', sans-serif;
  }
  
  .balance {
    color: #999;
    font-size: 12px;
  }
}

.buttons {
  display: flex;
  gap: 10px;
  align-items: center;
  
  .cart-btn-wrapper {
    position: relative;
    padding: 8px;
    margin-right: 4px;
    color: #333;
    cursor: pointer;
    transition: transform 0.2s;
    
    &:active {
      transform: scale(0.9);
    }
    
    .badge {
      position: absolute;
      top: 0;
      right: 0;
      background: #ff4d4f;
      color: #fff;
      font-size: 10px;
      border-radius: 10px;
      min-width: 16px;
      height: 16px;
      line-height: 16px;
      text-align: center;
      padding: 0 4px;
      box-shadow: 0 2px 4px rgba(255, 77, 79, 0.3);
    }
  }
  
  .action-btn {
    height: 38px;
    padding: 0 16px;
    border-radius: 19px;
    font-weight: 500;
    
    &.add-btn {
      border-color: #1890ff;
      color: #1890ff;
      background: #f0f9ff;
    }
  }
  
  .submit-btn {
    font-weight: bold;
    background: linear-gradient(135deg, #1890ff 0%, #096dd9 100%);
    border: none;
    box-shadow: 0 4px 12px rgba(24, 144, 255, 0.3);
    
    &:active {
      opacity: 0.9;
    }
  }
}
</style>
