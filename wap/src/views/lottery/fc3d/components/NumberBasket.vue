<template>
  <div class="number-basket">
    <div class="basket-header">
      <span>号码篮</span>
      <van-icon name="cross" @click="$emit('close')" class="close-btn" />
    </div>
    <div class="basket-list">
      <div v-if="list.length === 0" class="empty-tip">
        暂无投注记录
      </div>
      <div v-else v-for="(item, index) in list" :key="index" class="basket-item">
        <div class="item-info">
          <div class="item-play">[{{ item.playName }}]</div>
          <div class="item-nums">{{ item.numbers }}</div>
          <div class="item-detail">
            {{ item.notes }}注 {{ item.amount }}元
          </div>
        </div>
        <div class="item-delete" @click="$emit('delete', index)">
          <van-icon name="delete-o" />
        </div>
      </div>
    </div>
    <div class="basket-footer">
      <div class="basket-summary">
        <div class="summary-left" @click="$emit('clear')">
          <van-icon name="delete" /> 清空
        </div>
        <div class="summary-right">
          共 <span class="highlight">{{ totalNotes }}</span> 注，
          <span class="highlight">{{ totalAmount }}</span> 元
        </div>
      </div>
      <van-button type="danger" block round @click="$emit('submit')">确认投注</van-button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Button as VanButton, Icon as VanIcon } from 'vant'

const props = defineProps({
  list: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['close', 'delete', 'clear', 'submit'])

const totalNotes = computed(() => {
  return props.list.reduce((acc, item) => acc + item.notes, 0)
})

const totalAmount = computed(() => {
  return props.list.reduce((acc, item) => acc + parseFloat(item.amount), 0).toFixed(2)
})
</script>

<style lang="less" scoped>
.number-basket {
  display: flex;
  flex-direction: column;
  height: 100%;
  background: #fff;
}

.basket-header {
  padding: 16px;
  text-align: center;
  font-weight: bold;
  font-size: 16px;
  border-bottom: 1px solid #eee;
  position: relative;
  
  .close-btn {
    position: absolute;
    right: 16px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 20px;
    color: #999;
    cursor: pointer;
  }
}

.basket-list {
  flex: 1;
  overflow-y: auto;
  padding: 12px;
  background: #f7f8fa;
}

.empty-tip {
  text-align: center;
  padding: 40px 0;
  color: #999;
  font-size: 14px;
}

.basket-item {
  background: #fff;
  border-radius: 8px;
  padding: 12px;
  margin-bottom: 12px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 2px 6px rgba(0,0,0,0.02);
  
  .item-info {
    flex: 1;
    
    .item-play {
      color: #666;
      font-size: 12px;
      margin-bottom: 4px;
    }
    
    .item-nums {
      font-size: 16px;
      color: #e1251b;
      font-weight: bold;
      margin-bottom: 6px;
      word-break: break-all;
    }
    
    .item-detail {
      color: #999;
      font-size: 12px;
    }
  }
  
  .item-delete {
    padding: 8px;
    color: #999;
    font-size: 20px;
    
    &:active {
      color: #333;
    }
  }
}

.basket-footer {
  padding: 12px;
  border-top: 1px solid #eee;
  background: #fff;
  
  .basket-summary {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
    font-size: 14px;
    color: #666;
    
    .summary-left {
      display: flex;
      align-items: center;
      gap: 4px;
      cursor: pointer;
    }
    
    .highlight {
      color: #e1251b;
      font-weight: bold;
    }
  }
}
</style>
