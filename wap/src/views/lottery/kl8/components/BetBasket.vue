<template>
  <van-popup 
    v-model:show="visible" 
    position="bottom" 
    round 
    :style="{ height: '75%' }"
    @closed="emit('closed')"
  >
    <div class="bet-basket">
      
      <div class="basket-header">
        <div class="title">号码篮 ({{ items.length }})</div>
        <div class="clear-btn" @click="onClearBasket">
          <van-icon name="delete-o" /> 清空
        </div>
      </div>

      
      <div class="basket-content" v-if="items.length === 0">
        <div class="empty-placeholder">
          <div class="icon-box">
            <van-icon name="shopping-cart-o" />
          </div>
          <div class="text">暂无投注号码</div>
          <div class="sub-text">祝君好运，中大奖</div>
          <van-button class="go-bet-btn" plain type="primary" round size="small" @click="visible = false">去选号</van-button>
        </div>
      </div>

      
      <div class="basket-content" v-else>
        <div class="basket-list">
          <van-swipe-cell v-for="(item, index) in items" :key="index" class="list-item-wrapper">
            <div class="list-item">
              <div class="left-info">
                <div class="play-name">
                  <span class="tag">{{ item.isFun ? '趣味' : '标准' }}</span>
                  {{ item.playName }}
                </div>
                <div class="nums">
                  {{ formatNums(item.numbers) }}
                </div>
              </div>
              <div class="right-info">
                <div class="amount">{{ item.betCount }}注 {{ item.money }}元</div>
              </div>
            </div>
            <template #right>
              <van-button square type="danger" text="删除" class="delete-btn" @click="deleteItem(index)" />
            </template>
          </van-swipe-cell>
        </div>
      </div>

      
      <div class="basket-footer">
        
        <div class="random-area">
          <div class="btn-random" @click="randomAdd(1)">+ 机选1注</div>
          <div class="btn-random" @click="randomAdd(5)">+ 机选5注</div>
        </div>

        
        <div class="submit-bar">
          <div class="total-info">
            <div class="money">
              <span class="currency">¥</span>
              <span class="val">{{ totalMoney }}</span>
            </div>
            <div class="count">共 {{ items.length }} 单</div>
          </div>
          <div class="submit-btn" @click="onSubmit">
            立即投注
          </div>
        </div>
      </div>
    </div>
  </van-popup>
</template>

<script setup>
import { computed } from 'vue'
import { showConfirmDialog } from 'vant'

const props = defineProps({
  show: Boolean,
  items: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['update:show', 'clear', 'delete', 'random', 'submit', 'closed'])

const visible = computed({
  get: () => props.show,
  set: (val) => emit('update:show', val)
})

const totalMoney = computed(() => {
  return props.items.reduce((sum, item) => sum + parseFloat(item.money || 0), 0).toFixed(2)
})

function formatNums(nums) {
  if (!nums) return ''
  if (Array.isArray(nums)) {
    return nums.join(' ')
  }
  return nums
}

function deleteItem(index) {
  emit('delete', index)
}

function onClearBasket() {
  if (props.items.length === 0) return
  showConfirmDialog({
    title: '清空提示',
    message: '确定要清空号码篮吗？',
  }).then(() => {
    emit('clear')
  }).catch(() => {})
}

function randomAdd(count) {
  emit('random', count)
}

function onSubmit() {
  emit('submit')
}
</script>

<style lang="less" scoped>
.bet-basket {
  display: flex;
  flex-direction: column;
  height: 100%;
  background: #f7f8fa;
}

.basket-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  background: #fff;
  border-bottom: 1px solid #f5f5f5;
  
  .title { font-size: 16px; font-weight: 700; color: #333; }
  .clear-btn { font-size: 13px; color: #999; display: flex; align-items: center; gap: 4px; }
}

.basket-content {
  flex: 1;
  overflow-y: auto;
  position: relative;
  
  .empty-placeholder {
    position: absolute;
    top: 40%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
    
    .icon-box { 
      font-size: 48px; color: #ddd; margin-bottom: 16px; 
      width: 80px; height: 80px; background: #fff; border-radius: 50%; 
      display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;
    }
    .text { font-size: 15px; color: #666; font-weight: 500; margin-bottom: 4px; }
    .sub-text { font-size: 12px; color: #999; margin-bottom: 20px; }
    .go-bet-btn { padding: 0 30px; }
  }
  
  .basket-list {
    padding: 12px;
    
    .list-item-wrapper {
      margin-bottom: 10px;
      border-radius: 12px;
      overflow: hidden;
      
      .list-item {
        background: #fff;
        padding: 12px 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        
        .left-info {
          flex: 1;
          .play-name { 
            font-size: 14px; color: #333; font-weight: 600; margin-bottom: 6px; display: flex; align-items: center; gap: 6px;
            .tag { font-size: 10px; color: #1890ff; background: #e6f7ff; padding: 1px 4px; border-radius: 4px; font-weight: 400; }
          }
          .nums { 
            font-size: 13px; color: #ff4d4f; font-family: 'DIN Alternate'; font-weight: 500; line-height: 1.4;
            word-break: break-all;
          }
        }
        
        .right-info {
          text-align: right;
          margin-left: 12px;
          .amount { font-size: 12px; color: #666; }
        }
      }
      
      .delete-btn { height: 100%; }
    }
  }
}

.basket-footer {
  background: #fff;
  padding-bottom: env(safe-area-inset-bottom);
  box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
  
  .random-area {
    display: flex;
    gap: 12px;
    padding: 12px 16px;
    border-bottom: 1px solid #f5f5f5;
    
    .btn-random {
      flex: 1;
      text-align: center;
      padding: 8px 0;
      border: 1px solid #eee;
      border-radius: 8px;
      font-size: 13px;
      color: #666;
      background: #fff;
      
      &:active { background: #f5f5f5; border-color: #ddd; }
    }
  }
  
  .submit-bar {
    padding: 10px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    
    .total-info {
      .money { 
        color: #ff4d4f; font-weight: 700; display: flex; align-items: baseline; 
        .currency { font-size: 14px; margin-right: 2px; }
        .val { font-size: 24px; font-family: 'DIN Alternate'; }
      }
      .count { font-size: 11px; color: #999; margin-top: 2px; }
    }
    
    .submit-btn {
      background: linear-gradient(135deg, #ff4d4f 0%, #ff7875 100%);
      color: #fff;
      padding: 10px 32px;
      border-radius: 24px;
      font-size: 16px;
      font-weight: 600;
      box-shadow: 0 4px 12px rgba(255, 77, 79, 0.3);
      
      &:active { transform: scale(0.98); }
    }
  }
}
</style>
