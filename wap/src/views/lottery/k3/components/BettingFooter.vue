<template>
  <div class="betting-footer-cyber">
    
    <div class="footer-settings">
      <div class="setting-group full-width">
        <span class="label">单注</span>
        <div class="unit-tabs">
          <span 
            v-for="opt in denominationOptions" 
            :key="opt.value" 
            :class="['unit-tab', { active: denomination === opt.value && !isCustomAmount }]"
            @click="selectPresetAmount(opt.value)"
          >
            {{ opt.text }}
          </span>
          <span 
            :class="['unit-tab', 'custom', { active: isCustomAmount }]"
            @click="enableCustomAmount"
          >
            自定义
          </span>
        </div>
        <div v-if="isCustomAmount" class="custom-amount-input">
          <input 
            type="number" 
            v-model.number="customAmount" 
            @change="onCustomAmountChange"
            placeholder="输入金额"
            class="amount-input"
            step="0.01"
            min="0.01"
          />
          <span class="unit-text">元</span>
        </div>
      </div>
      
      <div class="setting-group">
        <span class="label">倍数</span>
        <div class="multiplier-control">
          <van-icon name="minus" class="control-icon" @click="decreaseMultiplier" />
          <input type="number" v-model.number="multiplier" @change="onMultiplierChange" class="num-input" />
          <van-icon name="plus" class="control-icon" @click="increaseMultiplier" />
        </div>
      </div>
    </div>

    
    <div class="footer-actions">
      <div class="summary-info">
        <div class="info-row">
          <span>已选 <span class="highlight">{{ betStore.totalBets }}</span> 注</span>
          <span class="separator">|</span>
          <span>共 <span class="highlight">{{ totalAmount }}</span> 元</span>
        </div>
      </div>
      
      <div class="action-buttons">
        <div class="icon-btn clear" @click="betStore.clearAllBets()">
          <van-icon name="delete-o" />
          <span>清空</span>
        </div>
        <div class="icon-btn cart" @click="openCart">
          <van-icon name="shopping-cart-o" />
          <span>购彩篮</span>
          <span v-if="cartCount > 0" class="cart-badge">{{ cartCount }}</span>
        </div>
        <div class="btn-group">
          <button class="cyber-btn secondary" @click="addToCart">加入购彩篮</button>
          <button class="cyber-btn gradient" @click="onHemaiClick">发起合买</button>
          <button class="cyber-btn primary" @click="onSubmit">立即投注</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed, inject } from 'vue';
import { Icon as VanIcon, showDialog, showConfirmDialog, showLoadingToast, closeToast, showToast } from 'vant';
import { useRouter } from 'vue-router';
import { useK3BetStore } from '@/stores/k3Bet';
import { k3Api } from '@/api';
import { BET_AMOUNT, MULTIPLIER, ERROR_MESSAGES, ERROR_CODES } from '@/constants/lottery';
import { handleValidationError, handleBusinessError } from '@/utils/errorHandler';

const props = defineProps({
  cartCount: {
    type: Number,
    default: 0
  },
  lotteryCode: {
    type: String,
    default: 'jsk3'
  },
  currentIssue: {
    type: String,
    default: ''
  },
  canBet: {
    type: Boolean,
    default: true
  }
});

const router = useRouter();
const betStore = useK3BetStore();
const emit = defineEmits(['submit', 'add-to-cart', 'open-hemai', 'open-cart', 'bet-success', 'issue-expired']);

const denomination = ref(betStore.denomination);
const isCustomAmount = ref(false);
const customAmount = ref(BET_AMOUNT.DEFAULT_DENOMINATION);

const denominationOptions = BET_AMOUNT.QUICK_OPTIONS.map(v => ({ text: `${v}元`, value: v }));

const selectPresetAmount = (value) => {
  isCustomAmount.value = false;
  denomination.value = value;
  betStore.setDenomination(value);
};

const enableCustomAmount = () => {
  isCustomAmount.value = true;
  if (customAmount.value) {
    betStore.setDenomination(customAmount.value);
  }
};

const onCustomAmountChange = () => {

  if (typeof customAmount.value !== 'number' || customAmount.value < BET_AMOUNT.MIN) {
    customAmount.value = BET_AMOUNT.MIN;
    handleValidationError(`最小投注金额为 ${BET_AMOUNT.MIN} 元`);
    return;
  }

  if (customAmount.value > BET_AMOUNT.MAX) {
    customAmount.value = BET_AMOUNT.MAX;
    handleValidationError(`单注最大金额为 ${BET_AMOUNT.MAX} 元`);
    return;
  }

  customAmount.value = Math.round(customAmount.value * 100) / 100;
  betStore.setDenomination(customAmount.value);
};

const isSubmitting = ref(false);
const multiplier = ref(betStore.betMultiplier);

const onMultiplierChange = () => {
  if (typeof multiplier.value !== 'number' || multiplier.value < MULTIPLIER.MIN) {
    multiplier.value = MULTIPLIER.MIN;
  }
  if (multiplier.value > MULTIPLIER.MAX) {
    multiplier.value = MULTIPLIER.MAX;
    handleValidationError(`最大倍数为 ${MULTIPLIER.MAX}`);
  }
  betStore.setMultiplier(multiplier.value);
};

const increaseMultiplier = () => {
  multiplier.value++;
  onMultiplierChange();
};

const decreaseMultiplier = () => {
  if (multiplier.value > 1) {
    multiplier.value--;
    onMultiplierChange();
  }
};

watch(() => betStore.betMultiplier, (newVal) => {
  multiplier.value = newVal;
});

const totalAmount = computed(() => betStore.totalAmount.toFixed(2));

const onHemaiClick = () => {
  emit('open-hemai');
};

const openCart = () => {
  emit('open-cart');
};

const addToCart = () => {
  if (betStore.totalBets === 0) {
    handleValidationError('请至少选择一注');
    return;
  }

  if (parseFloat(totalAmount.value) > BET_AMOUNT.MAX_TOTAL) {
    handleValidationError(`单期投注总额不能超过 ${BET_AMOUNT.MAX_TOTAL} 元`);
    return;
  }
  emit('add-to-cart');
};

const onSubmit = async () => {
  if (betStore.totalBets === 0) {
    handleValidationError('请至少选择一注');
    return;
  }
  
  if (!props.canBet) {
    handleValidationError('当前期已封盘，请等待下一期');
    return;
  }
  

  const total = parseFloat(totalAmount.value);
  if (total > BET_AMOUNT.MAX_TOTAL) {
    handleValidationError(`单期投注总额不能超过 ${BET_AMOUNT.MAX_TOTAL} 元`);
    return;
  }
  
  try {
    await showConfirmDialog({
      title: '投注确认',
      message: `总计: ${betStore.totalBets}注, ${totalAmount.value}元`,
      confirmButtonColor: '#00FF9A',
    });
  } catch {
    return; // 用户取消
  }
  
  if (isSubmitting.value) return;
  isSubmitting.value = true;
  
  showLoadingToast({ message: '投注中...', forbidClick: true });
  
  try {

    const currentIssue = props.currentIssue;
    

    const bets = [];
    betStore.selections.forEach(selection => {
      selection.items.forEach(item => {
        bets.push({
          playId: item.playid, // 使用每个选项的后端 playId
          content: item.name,
          amount: betStore.denomination * betStore.betMultiplier
        });
      });
    });
    
    const res = await k3Api.submitBet({
      lotteryCode: props.lotteryCode,
      issue: currentIssue, // 使用最新获取的期号
      bets,
      totalAmount: parseFloat(totalAmount.value)
    });
    
    closeToast();
    
    if (res.code === 0) {
      showDialog({ 
        title: '投注成功', 
        message: `订单号: ${res.data.orderId}\n余额: ${res.data.balance}元` 
      });
      betStore.clearAllBets();
      emit('bet-success', res.data);

      window.dispatchEvent(new CustomEvent('bet-success'));

      try {
        const channel = new BroadcastChannel('bet-success-channel');
        channel.postMessage({ type: 'bet-success', time: Date.now() });
        channel.close();
      } catch (e) {

        localStorage.setItem('bet-success-event', Date.now().toString());
      }
    } else {

      if (res.code === ERROR_CODES.ISSUE_CLOSED && res.data?.currentIssue) {
        emit('issue-expired', res.data.currentIssue);
        showToast('期号已更新，请重新投注');
      } else {
        const msg = ERROR_MESSAGES[res.code] || res.message || '投注失败';
        handleBusinessError(msg);
      }
      if (res.code === ERROR_CODES.NEED_LOGIN || res.code === ERROR_CODES.LOGIN_EXPIRED) {
        router.push('/home-new?auth=login');
      }
    }
  } catch (error) {
    closeToast();
    showDialog({ title: '提示', message: error.message || '投注失败，请稍后重试' });
  } finally {
    isSubmitting.value = false;
  }
};
</script>

<style lang="less" scoped>
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

.footer-settings {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 16px;
  gap: 12px;
  
  .setting-group {
    display: flex;
    align-items: center;
    gap: 8px;
    
    &.full-width {
      flex: 1;
      flex-wrap: wrap;
    }
    
    .label {
      font-size: 12px;
      color: rgba(255, 255, 255, 0.5);
      white-space: nowrap;
      font-weight: 600;
    }
  }
}

.unit-tabs {
  display: flex;
  background: rgba(0, 0, 0, 0.2);
  border-radius: 6px;
  padding: 3px;
  gap: 4px;
  border: 1px solid rgba(255, 255, 255, 0.05);
  
  .unit-tab {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.6);
    padding: 4px 10px;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.3s;
    white-space: nowrap;
    
    &.active {
      background: rgba(0, 255, 154, 0.15);
      color: #00FF9A;
      font-weight: bold;
      text-shadow: 0 0 8px rgba(0, 255, 154, 0.4);
    }
    
    &.custom {
      padding: 4px 8px;
    }
  }
}

.custom-amount-input {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 8px;
  width: 100%;
  
  .amount-input {
    flex: 1;
    height: 32px;
    background: rgba(0, 0, 0, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 6px;
    color: #00FF9A;
    padding: 0 10px;
    font-size: 14px;
    font-weight: bold;
    transition: all 0.3s;
    
    &:focus {
      outline: none;
      border-color: #00FF9A;
      box-shadow: 0 0 10px rgba(0, 255, 154, 0.1);
    }
    
    &::placeholder {
      color: rgba(255, 255, 255, 0.2);
      font-weight: normal;
    }
  }
  
  .unit-text {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.5);
  }
}

.multiplier-control {
  display: flex;
  align-items: center;
  background: rgba(0, 0, 0, 0.2);
  border-radius: 6px;
  height: 32px;
  border: 1px solid rgba(255, 255, 255, 0.05);
  
  .control-icon {
    padding: 0 10px;
    color: rgba(255, 255, 255, 0.6);
    font-size: 14px;
    cursor: pointer;
    transition: color 0.2s;
    
    &:active {
      color: #00FF9A;
    }
  }
  
  .num-input {
    width: 44px;
    background: transparent;
    border: none;
    color: #fff;
    text-align: center;
    font-size: 14px;
    font-weight: bold;
    border-left: 1px solid rgba(255, 255, 255, 0.05);
    border-right: 1px solid rgba(255, 255, 255, 0.05);
    height: 18px;
    line-height: 18px;
    padding: 0;
    
    &:focus {
      outline: none;
      color: #00FF9A;
    }
  }
}

.footer-actions {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.summary-info {
  display: flex;
  justify-content: center;
  
  .info-row {
    font-size: 13px;
    color: rgba(255, 255, 255, 0.6);
    background: rgba(255, 255, 255, 0.03);
    padding: 4px 16px;
    border-radius: 20px;
    border: 1px solid rgba(255, 255, 255, 0.05);
    
    .highlight {
      color: #00FF9A;
      font-weight: bold;
      margin: 0 4px;
      font-size: 15px;
    }
    
    .separator {
      margin: 0 10px;
      opacity: 0.2;
    }
  }
}

.action-buttons {
  display: flex;
  align-items: center;
  gap: 16px;
  
  .icon-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    color: rgba(255, 255, 255, 0.5);
    font-size: 10px;
    cursor: pointer;
    padding: 0 4px;
    position: relative;
    transition: all 0.2s;
    
    .van-icon {
      font-size: 22px;
      color: rgba(255, 255, 255, 0.4);
      transition: color 0.2s;
    }
    
    &.clear:active {
      color: #ff4757;
      .van-icon { color: #ff4757; }
    }
    
    &.cart {
      &:active {
        color: #00FF9A;
        .van-icon { color: #00FF9A; }
      }
      
      .cart-badge {
        position: absolute;
        top: -6px;
        right: -6px;
        background: #ff4757;
        color: #fff;
        font-size: 10px;
        font-weight: bold;
        padding: 0 4px;
        border-radius: 10px;
        min-width: 16px;
        height: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 6px rgba(255, 71, 87, 0.4);
      }
    }
  }
  
  .btn-group {
    flex: 1;
    display: flex;
    gap: 10px;
    
    .cyber-btn {
      flex: 1;
      height: 44px;
      border: none;
      border-radius: 10px;
      font-size: 14px;
      font-weight: bold;
      cursor: pointer;
      transition: all 0.2s;
      position: relative;
      overflow: hidden;
      
      &.secondary {
        background: rgba(0, 255, 154, 0.05);
        color: #00FF9A;
        border: 1px solid rgba(0, 255, 154, 0.2);
        
        &:active {
          background: rgba(0, 255, 154, 0.1);
        }
      }
      
      &.gradient {
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        color: #fff;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        
        &:active {
          filter: brightness(1.1);
        }
      }
      
      &.primary {
        background: linear-gradient(135deg, #00FF9A, #00cc7a);
        color: #000; 
        box-shadow: 0 4px 15px rgba(0, 255, 154, 0.3);
        text-shadow: none;
        
        &:active {
          filter: brightness(1.1);
          transform: translateY(1px);
        }
      }
    }
  }
}
</style>
