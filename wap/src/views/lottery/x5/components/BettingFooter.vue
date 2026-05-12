<template>
  <div class="betting-footer">
    
    <div class="footer-standard">
      <div class="footer-row top-row">
        <div class="settings-group">
          <van-dropdown-menu class="unit-selector">
            <van-dropdown-item :modelValue="mode" :options="modeOptions" @update:modelValue="$emit('update:mode', $event)" />
          </van-dropdown-menu>
          <div class="multiplier">
            <div class="stepper-btn" @click="decreaseMultiplier"><van-icon name="minus" size="12" /></div>
            <input type="number" :value="multiple" @input="onMultiplierInput" class="multiplier-input" />
            <div class="stepper-btn" @click="increaseMultiplier"><van-icon name="plus" size="12" /></div>
            <span class="mul-label">倍</span>
          </div>
        </div>
        <div class="bet-stats">
           <span>{{ betCount }}</span> 注 <span>{{ totalMoney }}</span> 元
        </div>
      </div>

      <div class="footer-row bottom-row">
        <div class="actions-left">
           <div class="action-icon-btn" @click="$emit('clear')">
             <van-icon name="delete-o" size="18" />
             <span>清空</span>
           </div>
           <div class="action-icon-btn" @click="$emit('open-hemai')">
             <van-icon name="friends-o" size="18" />
             <span>合买</span>
           </div>
        </div>
        <div class="actions-right">
          <van-button class="btn-cart" plain type="primary" @click="$emit('add-to-cart')">
            加入购彩篮
          </van-button>
          <van-button class="btn-submit" type="primary" @click="$emit('submit')">
            立即投注
          </van-button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { Icon as VanIcon, Button as VanButton, DropdownMenu as VanDropdownMenu, DropdownItem as VanDropdownItem } from 'vant';

const props = defineProps({
  betCount: { type: Number, default: 0 },
  totalMoney: { type: [Number, String], default: 0 },
  mode: { type: [Number, String], default: 1 },
  multiple: { type: [Number, String], default: 1 }
});

const emit = defineEmits(['update:mode', 'update:multiple', 'clear', 'add-to-cart', 'open-hemai', 'submit']);

const modeOptions = [
  { text: '元', value: 1 },
  { text: '角', value: 0.1 },
  { text: '分', value: 0.01 },
];

const onMultiplierInput = (e) => {
  const val = parseInt(e.target.value) || 1;
  emit('update:multiple', val);
};

const increaseMultiplier = () => {
  emit('update:multiple', (parseInt(props.multiple) || 1) + 1);
};

const decreaseMultiplier = () => {
  const val = parseInt(props.multiple) || 1;
  if (val > 1) {
    emit('update:multiple', val - 1);
  }
};
</script>

<style lang="less" scoped>
@import "@/assets/theme.less";

.betting-footer {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 12px 16px 20px; 
  background: rgba(255, 255, 255, 0.98);
  border-top: 1px solid rgba(0,0,0,0.05);
  box-shadow: 0 -4px 16px rgba(0, 0, 0, 0.06);
  z-index: 100;
  backdrop-filter: blur(10px);
}

.footer-standard {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.top-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  
  .settings-group {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #f5f6f7;
    padding: 4px 8px;
    border-radius: 24px;
  }
  
  .bet-stats {
    font-size: 13px;
    color: #666;
    font-weight: 500;
    span {
        color: @danger-color;
        font-weight: bold;
        font-size: 16px;
        margin: 0 2px;
    }
  }
}

.unit-selector {
  height: 28px;
  :deep(.van-dropdown-menu__bar) {
    background: transparent;
    box-shadow: none;
    height: 28px;
  }
  :deep(.van-dropdown-menu__title) {
    font-size: 13px;
    color: #333;
    padding: 0 8px 0 0;
  }
  :deep(.van-dropdown-menu__title::after) {
      right: -2px;
  }
}

.multiplier {
  display: flex;
  align-items: center;
  gap: 4px;
  padding-left: 8px;
  border-left: 1px solid #e0e0e0;
  
  .stepper-btn {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fff;
    border-radius: 50%;
    color: #333;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    cursor: pointer;
    &:active { transform: scale(0.9); }
  }
  
  .multiplier-input {
    width: 36px;
    text-align: center;
    border: none;
    background: transparent;
    font-size: 15px;
    font-weight: 600;
    color: #333;
    padding: 0;
  }
  
  .mul-label {
      font-size: 12px; color: #999; margin-left: 2px;
  }
}

.bottom-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.actions-left {
    display: flex;
    gap: 16px;
    padding-left: 4px;
    
    .action-icon-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 2px;
        color: #646566;
        cursor: pointer;
        
        span { font-size: 10px; }
        &:active { color: #333; }
    }
}

.actions-right {
    display: flex;
    gap: 8px;
    
    .btn-cart, .btn-submit {
        height: 40px;
        border-radius: 20px;
        font-weight: 600;
        padding: 0 20px;
        font-size: 14px;
    }
    
    .btn-cart {
        border-color: @primary-color;
        color: @primary-color;
        background: rgba(25, 137, 250, 0.05);
    }
    
    .btn-submit {
        background: linear-gradient(135deg, #ff6034, #ee0a24);
        border: none;
        box-shadow: 0 4px 12px rgba(238, 10, 36, 0.3);
    }
}
</style>
