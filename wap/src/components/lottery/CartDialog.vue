<template>
  
  <div class="dialog linearTop animated" :style="{ display: visible ? 'block' : 'none', zIndex: 201 }">
    <div class="dialog-container">
      <div class="CartMain">
        <div class="top">
          <span>{{ lotteryName }} - 购彩篮</span> 
          <span class="rbtn" id="orderlist_clear" @click="handleClear">清空所有</span>
        </div> 
        <div class="middle">
          <div class="">
            <div class="jixuan" v-if="showRandomSelect">
              <span class="random1" @click="$emit('random-select', 1)"><i class="iconfont">&#xe6cc; </i>机选一注</span>
              <span class="random5" @click="$emit('random-select', 5)"><i class="iconfont">&#xe6cc; </i>机选五注</span>
            </div>
            <div class="gouclanwu yBettingLists">
              <div v-if="cartItems.length === 0" class="empty-cart">
                <p style="text-align: center; padding: 0.3rem; color: #999;">购彩篮为空</p>
              </div>
              <div 
                v-for="(item, index) in cartItems" 
                :key="index" 
                class="yBettingList"
              >
                <div class="left">{{ item.playName }} [{{ item.playType }}] {{ item.number }}</div>
                <div class="right">{{ item.zhushu }}注 {{ item.multiple }}倍 {{ item.money.toFixed(3) }}元</div>
                <div class="delete" @click="handleDelete(index)">删除</div>
              </div>
            </div> 
          </div>
        </div> 
        <div class="foot border_top_1px">
          <span class="border_right_1px" @click="handleContinue"><i class="iconfont">&#xe62c;</i>继续选号</span> 
          <span id="f_submit_order" @click="handleSubmit">提交订单</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  },
  lotteryName: {
    type: String,
    default: '彩票'
  },
  cartItems: {
    type: Array,
    default: () => []
  },
  showRandomSelect: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits([
  'close',
  'clear',
  'delete',
  'submit',
  'random-select',
  'update:visible'
])

const handleClear = () => {
  if (props.cartItems.length === 0) {
    return
  }
  if (confirm('确定清空所有投注吗？')) {
    emit('clear')
  }
}

const handleDelete = (index) => {
  emit('delete', index)
}

const handleContinue = () => {
  emit('update:visible', false)
  emit('close')
}

const handleSubmit = () => {
  if (props.cartItems.length === 0) {
    alert('请先选择投注号码')
    return
  }
  emit('submit')
}
</script>

<style scoped>

</style>

