<template>
  
  <div class="dialog linearTop animated" :style="{ display: visible ? 'block' : 'none', zIndex: 201 }">
    <div class="dialog-container">
      <div class="CartMain">
        <div class="top">
          <span>{{ lotteryName }} - Giỏ cược</span>
          <span class="rbtn" id="orderlist_clear" @click="handleClear">Xóa tất cả</span>
        </div> 
        <div class="middle">
          <div class="">
            <div class="jixuan" v-if="showRandomSelect">
              <span class="random1" @click="$emit('random-select', 1)"><i class="iconfont">&#xe6cc; </i>Chọn ngẫu nhiên 1</span>
              <span class="random5" @click="$emit('random-select', 5)"><i class="iconfont">&#xe6cc; </i>Chọn ngẫu nhiên 5</span>
            </div>
            <div class="gouclanwu yBettingLists">
              <div v-if="cartItems.length === 0" class="empty-cart">
                <p style="text-align: center; padding: 0.3rem; color: #999;">Giỏ cược trống</p>
              </div>
              <div 
                v-for="(item, index) in cartItems" 
                :key="index" 
                class="yBettingList"
              >
                <div class="left">{{ item.playName }} [{{ item.playType }}] {{ item.number }}</div>
                <div class="right">{{ item.zhushu }}vé {{ item.multiple }}lần {{ item.money.toFixed(3) }}đ</div>
                <div class="delete" @click="handleDelete(index)">Xóa</div>
              </div>
            </div> 
          </div>
        </div> 
        <div class="foot border_top_1px">
          <span class="border_right_1px" @click="handleContinue"><i class="iconfont">&#xe62c;</i>Tiếp tục chọn</span>
          <span id="f_submit_order" @click="handleSubmit">Gửi đơn</span>
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
  if (confirm('Bạn có chắc muốn xóa tất cả?')) {
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
    alert('Vui lòng chọn số cược trước')
    return
  }
  emit('submit')
}
</script>

<style scoped>

</style>

