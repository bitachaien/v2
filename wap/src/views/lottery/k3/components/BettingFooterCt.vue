<template>
  <div class="betting-footer-ct">
    <div class="money-input-area">
      <div class="input-wrapper">
        <span>金额(元)</span>
        <input v-model="betAmount" type="tel" class="bet-money-input" placeholder="请输入金额" />
      </div>
      <div class="quick-chips">
        <span v-for="chip in quickChips" :key="chip" @click="setBetAmount(chip)" class="chip">{{ chip }}</span>
      </div>
    </div>
    <div class="action-buttons">
      <van-button class="reset-btn" @click="resetSelection">重置选择</van-button>
      <van-button class="confirm-btn" @click="confirmBet">确认注单</van-button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Button as VanButton } from 'vant';
import { BET_AMOUNT } from '@/constants/lottery';
import { handleValidationError } from '@/utils/errorHandler';

const betAmount = ref('');
const quickChips = [1, 5, 10, 20, 50, 100, 300];

const setBetAmount = (amount) => {
  betAmount.value = amount;
};

const emit = defineEmits(['reset', 'confirm']);

const resetSelection = () => {
  emit('reset');
};

const confirmBet = () => {
  const amount = +betAmount.value;
  if (!betAmount.value || amount <= 0) {
    handleValidationError('请输入有效的金额');
    return;
  }
  if (amount < BET_AMOUNT.MIN) {
    handleValidationError(`最小投注金额为 ${BET_AMOUNT.MIN} 元`);
    return;
  }
  if (amount > BET_AMOUNT.MAX) {
    handleValidationError(`单注最大金额为 ${BET_AMOUNT.MAX} 元`);
    return;
  }
  emit('confirm', amount);
  betAmount.value = '';
};
</script>

<style lang="less" scoped>
@import "@/assets/theme.less";

.betting-footer-ct {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background-color: rgba(18, 24, 37, 0.95);
  backdrop-filter: blur(10px);
  box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.4);
  z-index: 100;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.money-input-area {
  padding: 12px 16px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.input-wrapper {
  display: flex;
  align-items: center;
  margin-bottom: 12px;

  span {
    font-size: 14px;
    color: rgba(255, 255, 255, 0.8);
    margin-right: 12px;
  }

  .bet-money-input {
    flex: 1;
    height: 40px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(0, 0, 0, 0.2);
    border-radius: 8px;
    padding: 0 16px;
    font-size: 16px;
    color: #00FF9A;
    font-family: 'DIN Alternate', sans-serif;
    letter-spacing: 1px;

    &::placeholder {
      color: rgba(255, 255, 255, 0.2);
    }

    &:focus {
      border-color: #00FF9A;
      box-shadow: 0 0 10px rgba(0, 255, 154, 0.2);
      outline: none;
    }
  }
}

.quick-chips {
  display: flex;
  justify-content: space-between;
  gap: 8px;
  overflow-x: auto;
  padding-bottom: 4px;

  .chip {
    flex: 1;
    min-width: 48px;
    text-align: center;
    padding: 6px 0;
    font-size: 13px;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.8);
    background-color: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
    font-family: 'DIN Alternate', sans-serif;

    &:hover, &:active {
      background-color: rgba(0, 255, 154, 0.1);
      border-color: #00FF9A;
      color: #00FF9A;
      transform: translateY(-1px);
    }
  }
}

.action-buttons {
  display: flex;

  .van-button {
    flex: 1;
    height: 54px;
    border: none;
    border-radius: 0;
    font-size: 16px;
    font-weight: bold;
    letter-spacing: 2px;
  }

  .reset-btn {
    background-color: rgba(30, 35, 45, 0.9);
    color: rgba(255, 255, 255, 0.6);
    border-top: 1px solid rgba(255, 255, 255, 0.05);
    
    &:active {
      background-color: rgba(40, 45, 55, 0.9);
    }
  }

  .confirm-btn {
    background: linear-gradient(90deg, #00FF9A, #00F0F0);
    color: #000;
    text-shadow: 0 1px 2px rgba(255, 255, 255, 0.4);
    
    &:active {
      opacity: 0.9;
    }
  }
}
</style>
