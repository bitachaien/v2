<template>
  <div class="betting-footer-cyber">
    
    
    <div v-if="isDoubleSide" class="footer-double-side">
       <div class="quick-amount-row">
          <div class="amount-label">金额</div>
          <div class="quick-amounts">
             <div 
               v-for="amt in quickAmounts" 
               :key="amt" 
               class="quick-btn"
               @click="setAmount(amt)"
             >
               {{ amt }}
             </div>
          </div>
       </div>
       <div class="custom-input-row">
          <input type="number" v-model.number="customAmount" class="custom-amount-input" placeholder="输入金额" @input="onCustomAmountChange" />
          <span class="unit">元</span>
       </div>

       <div class="action-row">
          <div class="bet-info">
             已选 <span class="highlight">{{ betCount }}</span> 注
          </div>
          <div class="btn-group">
             <button class="cyber-btn secondary" @click="$emit('clear')">重置</button>
             <button class="cyber-btn primary" @click="$emit('submit')">立即投注</button>
          </div>
       </div>
    </div>

    
    <div v-else class="footer-standard">
      
      <div class="footer-settings">
        <div class="setting-group full-width">
          <span class="label">单注</span>
          <div class="unit-tabs">
            <span 
              v-for="opt in denominationOptions" 
              :key="opt.value" 
              :class="['unit-tab', { active: !isCustomDenom && parseFloat(mode) === opt.value }]"
              @click="selectDenomination(opt.value)"
            >
              {{ opt.text }}
            </span>
            <span 
              :class="['unit-tab', 'custom', { active: isCustomDenom }]"
              @click="enableCustomDenom"
            >
              自定义
            </span>
          </div>
          <div v-if="isCustomDenom" class="custom-amount-input">
            <input 
              type="number" 
              v-model.number="customDenomValue" 
              @input="onCustomDenomChange" 
              placeholder="输入金额"
              class="amount-input"
              step="1"
              min="1"
            />
            <span class="unit-text">元</span>
          </div>
        </div>
        
        <div class="setting-group">
          <span class="label">倍数</span>
          <div class="multiplier-control">
            <van-icon name="minus" class="control-icon" @click="decreaseMultiplier" />
            <input type="number" :value="multiple" @input="onMultiplierInput" class="num-input" />
            <van-icon name="plus" class="control-icon" @click="increaseMultiplier" />
          </div>
        </div>
      </div>

      
      <div class="footer-actions">
        <div class="summary-info">
          <div class="info-row">
            <span>已选 <span class="highlight">{{ betCount }}</span> 注</span>
            <span class="separator">|</span>
            <span>共 <span class="highlight">{{ totalMoney }}</span> 元</span>
          </div>
        </div>
        
        <div class="action-buttons">
          <div class="icon-btn clear" @click="$emit('clear')">
            <van-icon name="delete-o" />
            <span>清空</span>
          </div>
          <div class="icon-btn cart" @click="$emit('open-cart')">
            <van-icon name="shopping-cart-o" />
            <span>购彩篮</span>
            <span v-if="cartCount > 0" class="cart-badge">{{ cartCount }}</span>
          </div>
          <div class="btn-group">
            <button class="cyber-btn secondary" @click="$emit('add-to-cart')">加入购彩篮</button>
            <button class="cyber-btn gradient" @click="$emit('open-hemai')">发起合买</button>
            <button class="cyber-btn primary" @click="$emit('submit')">立即投注</button>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { Icon as VanIcon } from 'vant';

const props = defineProps({
  betCount: { type: Number, default: 0 },
  totalMoney: { type: [Number, String], default: 0 },
  mode: { type: [Number, String], default: 2 }, // Default 2 Yuan
  multiple: { type: [Number, String], default: 1 },
  isDoubleSide: { type: Boolean, default: false },
  cartCount: { type: Number, default: 0 }
});

const emit = defineEmits(['update:mode', 'update:multiple', 'clear', 'add-to-cart', 'open-hemai', 'submit', 'open-cart']);

const denominationOptions = [
  { text: '2元', value: 2 },
  { text: '5元', value: 5 },
  { text: '10元', value: 10 },
];

const isCustomDenom = ref(false);
const customDenomValue = ref(2);

const selectDenomination = (val) => {
  isCustomDenom.value = false;
  emit('update:mode', val);
};

const enableCustomDenom = () => {
  isCustomDenom.value = true;
  customDenomValue.value = props.mode;
};

const onCustomDenomChange = () => {
  const val = parseFloat(customDenomValue.value) || 0;
  emit('update:mode', val);
};

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

const quickAmounts = [10, 50, 100, 500, 1000];
const customAmount = ref('');

const setAmount = (amt) => {
  customAmount.value = amt;
  emit('update:multiple', amt); 
}

const onCustomAmountChange = () => {
  const val = parseInt(customAmount.value) || 0;
  emit('update:multiple', val);
}

watch(() => props.multiple, (v) => {
  if (props.isDoubleSide) customAmount.value = v;
});
</script>

<style lang="less" scoped>
@import "@/assets/theme.less";

.betting-footer-cyber {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: rgba(18, 24, 37, 0.95);
  backdrop-filter: blur(20px);
  border-top: 1px solid rgba(0, 255, 154, 0.1);
  padding: 12px 16px 24px;
  z-index: 100;
  box-shadow: 0 -4px 30px rgba(0, 0, 0, 0.5);
}

.footer-double-side {
    display: flex;
    flex-direction: column;
    gap: 12px;
    
    .quick-amount-row {
        display: flex; align-items: center; gap: 8px;
        overflow-x: auto; padding-bottom: 4px;
        .amount-label { font-size: 12px; color: rgba(255,255,255,0.5); font-weight: 600; white-space: nowrap; }
        .quick-amounts { display: flex; gap: 6px; }
        .quick-btn {
            background: rgba(255,255,255,0.1); 
            border: 1px solid rgba(255,255,255,0.1);
            padding: 4px 10px; border-radius: 4px;
            font-size: 12px; color: rgba(255,255,255,0.8); 
            min-width: 40px; text-align: center;
            cursor: pointer; transition: all 0.2s;
            &:active { background: rgba(0,255,154,0.2); color: #00FF9A; border-color: #00FF9A; }
        }
    }
    
    .custom-input-row {
        display: flex; align-items: center; gap: 8px;
        background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 6px;
        padding: 0 12px; height: 36px;
        
        .custom-amount-input {
            flex: 1; background: transparent; border: none;
            font-size: 14px; color: #00FF9A; font-weight: bold;
            &:focus { outline: none; }
            &::placeholder { color: rgba(255,255,255,0.2); font-weight: normal; }
        }
        .unit { font-size: 12px; color: rgba(255,255,255,0.4); }
    }
    
    .action-row {
        display: flex; justify-content: space-between; align-items: center;
        .bet-info { font-size: 13px; color: rgba(255,255,255,0.6); .highlight { color: #00FF9A; font-weight: bold; font-size: 16px; margin: 0 4px; } }
        .btn-group {
            display: flex; gap: 10px;
            .cyber-btn {
                height: 36px; padding: 0 16px; border-radius: 8px; border: none;
                font-size: 13px; font-weight: bold; cursor: pointer;
                &.secondary { background: rgba(255,255,255,0.1); color: rgba(255,255,255,0.8); border: 1px solid rgba(255,255,255,0.1); }
                &.primary { background: linear-gradient(135deg, #00FF9A, #00cc7a); color: #000; box-shadow: 0 2px 10px rgba(0,255,154,0.3); }
            }
        }
    }
}

.footer-standard {
    display: flex; flex-direction: column; gap: 16px; // K3 gap is 16px

    .footer-settings {
      display: flex; justify-content: space-between; align-items: flex-start;
      gap: 12px;
      
      .setting-group {
        display: flex; align-items: center; gap: 8px;
        &.full-width { flex: 1; flex-wrap: wrap; }
        
        .label { font-size: 12px; color: rgba(255,255,255,0.5); font-weight: 600; white-space: nowrap; }
      }
    }
    
    .unit-tabs {
        display: flex; background: rgba(0,0,0,0.2); border-radius: 6px; padding: 3px; gap: 4px; border: 1px solid rgba(255,255,255,0.05);
        .unit-tab {
            padding: 4px 10px; font-size: 12px; color: rgba(255,255,255,0.6); cursor: pointer; border-radius: 4px; transition: all 0.3s; white-space: nowrap;
            &.active { background: rgba(0,255,154,0.15); color: #00FF9A; font-weight: bold; text-shadow: 0 0 8px rgba(0, 255, 154, 0.4); }
            &.custom { padding: 4px 8px; }
        }
    }
    
    .custom-amount-input {
      display: flex; align-items: center; gap: 6px;
      margin-top: 8px; width: 100%;
      
      .amount-input {
        flex: 1; height: 32px; background: rgba(0, 0, 0, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 6px;
        color: #00FF9A; padding: 0 10px; font-size: 14px; font-weight: bold;
        transition: all 0.3s;
        &:focus { outline: none; border-color: #00FF9A; box-shadow: 0 0 10px rgba(0, 255, 154, 0.1); }
        &::placeholder { color: rgba(255, 255, 255, 0.2); font-weight: normal; }
      }
      .unit-text { font-size: 12px; color: rgba(255, 255, 255, 0.5); }
    }

    .multiplier-control {
        display: flex; align-items: center; background: rgba(0,0,0,0.2); border-radius: 6px; height: 32px; border: 1px solid rgba(255,255,255,0.05);
        .control-icon { padding: 0 10px; color: rgba(255,255,255,0.6); font-size: 14px; cursor: pointer; transition: color 0.2s; &:active { color: #00FF9A; } }
        .num-input {
            width: 44px; background: transparent; border: none; text-align: center; color: #fff; font-weight: bold; font-size: 14px;
            border-left: 1px solid rgba(255,255,255,0.05); border-right: 1px solid rgba(255,255,255,0.05); height: 18px; line-height: 18px; padding: 0;
            &:focus { outline: none; color: #00FF9A; }
        }
    }
    
    .footer-actions {
        display: flex; flex-direction: column; gap: 16px;
    }
    
    .summary-info {
        display: flex; justify-content: center;
        .info-row {
            font-size: 13px; color: rgba(255,255,255,0.6); background: rgba(255,255,255,0.03);
            padding: 4px 16px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.05);
            .highlight { color: #00FF9A; font-weight: bold; font-size: 15px; margin: 0 4px; }
            .separator { margin: 0 10px; opacity: 0.2; }
        }
    }
    
    .action-buttons {
        display: flex; gap: 16px; align-items: center;
        
        .icon-btn {
            display: flex; flex-direction: column; align-items: center; gap: 4px; color: rgba(255,255,255,0.5); font-size: 10px; cursor: pointer; padding: 0 4px; position: relative; transition: all 0.2s;
            .van-icon { font-size: 22px; color: rgba(255,255,255,0.4); transition: color 0.2s; }
            &.clear:active { color: #ff4757; .van-icon { color: #ff4757; } }
            &.cart {
                &:active { color: #00FF9A; .van-icon { color: #00FF9A; } }
                .cart-badge {
                    position: absolute; top: -6px; right: -6px; background: #ff4757; color: #fff;
                    font-size: 10px; font-weight: bold; padding: 0 4px; border-radius: 10px;
                    min-width: 16px; height: 16px; display: flex; align-items: center; justify-content: center;
                    box-shadow: 0 2px 6px rgba(255, 71, 87, 0.4);
                }
            }
        }
        
        .btn-group {
            flex: 1; display: flex; gap: 10px;
            
            .cyber-btn {
                flex: 1; height: 44px; border: none; border-radius: 10px; font-size: 14px; font-weight: bold; cursor: pointer; transition: all 0.2s;
                &.secondary {
                    background: rgba(0,255,154,0.05); color: #00FF9A; border: 1px solid rgba(0,255,154,0.2);
                    &:active { background: rgba(0,255,154,0.1); }
                }
                &.gradient {
                    background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: #fff; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
                    &:active { filter: brightness(1.1); }
                }
                &.primary {
                    background: linear-gradient(135deg, #00FF9A, #00cc7a); color: #000; box-shadow: 0 4px 15px rgba(0, 255, 154, 0.3);
                    &:active { filter: brightness(1.1); transform: translateY(1px); }
                }
            }
        }
    }
}
</style>
