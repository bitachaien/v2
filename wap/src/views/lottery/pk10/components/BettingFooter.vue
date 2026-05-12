<template>
  <div class="betting-footer">
    
    
    
    
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
          <input type="number" v-model.number="customAmount" class="custom-amount-input" placeholder="输入" @input="onCustomAmountChange" />
       </div>

       
       <div class="action-row">
          <div class="bet-info">
             已选 <span>{{ betStore.totalBets }}</span> 注
          </div>
          <div class="btn-group">
             <van-button class="btn-reset" plain @click="betStore.clearAllBets()">重置</van-button>
             <van-button class="btn-submit" type="primary" @click="onSubmit">立即投注</van-button>
          </div>
       </div>
    </div>

    
    
    
    <div v-else class="footer-standard">
      <div class="footer-row top-row">
        <van-dropdown-menu class="unit-selector">
          <van-dropdown-item v-model="denomination" :options="denominationOptions" @change="onDenominationChange" />
        </van-dropdown-menu>
        <div class="multiplier">
          <van-button icon="minus" @click="decreaseMultiplier" class="stepper-btn" round />
          <input type="number" v-model.number="multiplier" @change="onMultiplierChange" class="multiplier-input" />
          <van-button icon="plus" @click="increaseMultiplier" class="stepper-btn" round />
        </div>
      </div>

      <div class="footer-row bottom-row">
        <div class="bet-info">
          已选：{{ betStore.totalBets }} 注，{{ totalAmount }}元
        </div>
        <div class="actions">
          <van-button size="small" plain @click="betStore.clearAllBets()">清空</van-button>
          <van-button size="small" type="primary" plain @click="addToCart">加入购彩篮</van-button>
          <van-button size="small" plain @click="onHemaiClick">发起合买</van-button>
          <van-button size="small" type="primary" class="submit-btn" @click="onSubmit">立即投注</van-button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, watch, computed, inject } from 'vue';
import { useRoute } from 'vue-router';
import { Button as VanButton, DropdownMenu as VanDropdownMenu, DropdownItem as VanDropdownItem, showDialog, showConfirmDialog, showToast, showLoadingToast, closeToast } from 'vant';
import { usePk10BetStore } from '@/stores/pk10Bet';
import { pk10Api } from '@/api';

const route = useRoute();
const betStore = usePk10BetStore();
const emit = defineEmits(['submit', 'add-to-cart', 'open-hemai']);

const lotteryCode = inject('lotteryCode', ref('bjpk10'));

const currentExpect = inject('currentExpect', ref(''));

const isDoubleSide = computed(() => {
    return route.query.mode !== 'standard';
});

const quickAmounts = [10, 50, 100, 500, 1000, 5000];
const customAmount = ref('');

const setAmount = (val) => {
    customAmount.value = val;
    updateStoreAmount(val);
};

const onCustomAmountChange = () => {
    updateStoreAmount(customAmount.value);
};

const updateStoreAmount = (val) => {

    if (!val || val < 0) val = 0;
    betStore.setDenomination(1);
    betStore.setMultiplier(Number(val));
    multiplier.value = Number(val); // Sync local state
};

const denomination = ref(betStore.denomination);
const denominationOptions = [
  { text: '2元', value: 2 },
  { text: '1元', value: 1 },
  { text: '0.2元', value: 0.2 },
  { text: '0.1元', value: 0.1 },
];

const onDenominationChange = (value) => {
  betStore.setDenomination(value);
};

const multiplier = ref(betStore.betMultiplier);

const onMultiplierChange = () => {
  if (typeof multiplier.value !== 'number' || multiplier.value < 1) {
    multiplier.value = 1;
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
  if (isDoubleSide.value) {
      customAmount.value = newVal;
  } else {
      multiplier.value = newVal;
  }
});

const totalAmount = computed(() => betStore.totalAmount.toFixed(2));
const isSubmitting = ref(false);

const onHemaiClick = () => {
  emit('open-hemai');
};

const addToCart = () => {
  if (betStore.totalBets === 0) {
      showDialog({ title: '提示', message: '请至少选择一注' });
      return;
  }
  showDialog({ title: '成功', message: `已添加 ${betStore.totalBets} 注到购彩篮` });
  betStore.clearAllBets();
};

const onSubmit = async () => {
  if (betStore.totalBets === 0) {
    showDialog({ title: '提示', message: '请至少选择一注' });
    return;
  }
  
  try {
    await showConfirmDialog({
      title: '投注确认',
      message: `彩种: PK10\n总计: ${betStore.totalBets}注, ${totalAmount.value}元`,
    });
    
    isSubmitting.value = true;
    showLoadingToast({ message: '投注中...', forbidClick: true, duration: 0 });
    

    const bets = betStore.buildBetData();
    
    if (bets.length === 0) {
      closeToast();
      showDialog({ title: '提示', message: '没有有效的投注数据' });
      isSubmitting.value = false;
      return;
    }
    

    const res = await pk10Api.submitBets(lotteryCode.value, bets);
    
    closeToast();
    
    if (res.code === 0) {
      showDialog({ title: '成功', message: '投注成功！' });
      betStore.clearAllBets();
    } else {
      showDialog({ title: '投注失败', message: res.msg || '投注失败，请重试' });
    }
  } catch (e) {
    closeToast();
    if (e !== 'cancel') {
      console.error('投注失败:', e);
      showDialog({ title: '投注失败', message: e.message || '网络错误，请重试' });
    }
  } finally {
    isSubmitting.value = false;
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
  padding: 8px 16px 16px;
  background: rgba(255, 255, 255, 0.95);
  border-top: 1px solid #ebedf0;
  box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05);
  z-index: 100;
}

.footer-double-side {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.quick-amount-row {
    display: flex;
    align-items: center;
    gap: 8px;
    overflow-x: auto;
    white-space: nowrap;
    padding-bottom: 4px;
    
    .amount-label {
        font-size: 14px;
        font-weight: bold;
        color: #333;
    }
    
    .quick-amounts {
        display: flex;
        gap: 6px;
    }
    
    .quick-btn {
        background: #f5f5f5;
        border: 1px solid #eee;
        padding: 6px 10px;
        border-radius: 4px;
        font-size: 12px;
        color: #666;
        cursor: pointer;
        min-width: 40px;
        text-align: center;
        
        &:active {
            background: #e6e6e6;
        }
    }
    
    .custom-amount-input {
        width: 60px;
        padding: 5px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 13px;
        text-align: center;
    }
}

.action-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    
    .bet-info {
        font-size: 14px;
        color: #333;
        span {
            color: @primary-color;
            font-weight: bold;
            font-size: 16px;
            margin: 0 2px;
        }
    }
    
    .btn-group {
        display: flex;
        gap: 8px;
        
        .btn-reset {
            height: 36px;
            padding: 0 16px;
            border-radius: 4px;
        }
        
        .btn-submit {
            height: 36px;
            padding: 0 24px;
            border-radius: 4px;
            font-weight: bold;
            background: linear-gradient(to right, #ff6034, #ee0a24);
            border: none;
        }
    }
}

.footer-standard {
    .footer-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .top-row {
      margin-bottom: 8px;
    }

    .bottom-row {
      .bet-info {
        font-size: 12px;
        color: #666;
      }
      .actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        justify-content: flex-end;
        .submit-btn {
          background: linear-gradient(to right, #ff6034, #ee0a24);
          border: none;
        }
      }
    }
    
    .unit-selector {
      :deep(.van-dropdown-menu__bar) {
        background: transparent;
        box-shadow: none;
        height: 28px;
      }
      :deep(.van-dropdown-menu__title) {
        font-size: 12px;
        color: @text-color-secondary;
      }
    }

    .multiplier {
      display: flex;
      align-items: center;
      .stepper-btn {
        width: 28px;
        height: 28px;
      }
      .multiplier-input {
        width: 40px;
        height: 28px;
        text-align: center;
        border: none;
        background-color: #f2f3f5;
        margin: 0 4px;
        border-radius: 4px;
        font-size: 14px;
        font-weight: 600;
      }
    }
}
</style>
