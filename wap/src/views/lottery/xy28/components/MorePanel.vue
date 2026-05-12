<template>
  <transition name="expand">
    <div class="more-panel-inline" v-show="show">
      <van-swipe class="more-swipe" indicator-color="#007aff">
        <van-swipe-item v-for="(page, pIndex) in moreMenuPages" :key="pIndex">
          <div class="more-grid">
            <div class="more-item" v-for="(item, iIndex) in page" :key="iIndex" @click="handleAction(item.action)">
               <div class="item-icon-img">
                  <img :src="item.icon" />
               </div>
               <span>{{ item.label }}</span>
            </div>
          </div>
        </van-swipe-item>
      </van-swipe>
    </div>
  </transition>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  show: Boolean
})

const emit = defineEmits(['update:show', 'action'])

const moreMenuPages = [
  [
    { label: '客服', icon: '/assets/img/28/imgi_11_default.png', action: 'service' },
    { label: '充值', icon: '/assets/img/28/imgi_12_default.png', action: 'recharge' },
    { label: '提现', icon: '/assets/img/28/imgi_13_default.png', action: 'withdraw' },
    { label: '开奖走势', icon: '/assets/img/28/imgi_14_default.png', action: 'trend' },
    { label: '发起追号', icon: '/assets/img/28/imgi_15_default.png', action: 'chase' },
    { label: '追号记录', icon: '/assets/img/28/imgi_16_default.png', action: 'chaseRecords' },
    { label: '路子图', icon: '/assets/img/28/imgi_17_default.png', action: 'roadMap' },
    { label: '冷热遗漏', icon: '/assets/img/28/imgi_1181_default.png', action: 'hotCold' }
  ],
  [
    { label: '玩法规则', icon: '/assets/img/28/imgi_1182_default.png', action: 'playRules' }
  ]
]

function handleAction(action) {
  emit('action', action)
}
</script>

<style lang="less" scoped>
.more-panel-inline {
  width: 100%;
  background: #fff;
  overflow: hidden;
  border-top: 1px solid #f0f0f0;
  max-height: 220px; 
  
  .more-swipe {
    padding-bottom: 25px; 
    
    :deep(.van-swipe__indicator) {
        width: 6px;
        height: 6px;
        border-radius: 3px; 
        background-color: #ebedf0;
        opacity: 1;
        transition: all 0.3s;
        
        &.van-swipe__indicator--active {
            width: 12px; 
            border-radius: 3px;
            background-color: #007aff;
        }
    }
    
    :deep(.van-swipe__indicators) {
        bottom: 8px;
    }
  }

  .more-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    padding: 15px 10px;
    gap: 15px 10px;
    
    .more-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 6px;
      
      .item-icon-img {
          width: 40.13px;
          height: 40.13px;
          display: flex;
          justify-content: center;
          align-items: center;
          
          img {
              width: 100%;
              height: 100%;
              object-fit: contain;
          }
      }
      
      span {
        font-size: 12px;
        color: #333;
      }
      
      &:active {
        opacity: 0.8;
      }
    }
  }
}

.expand-enter-active,
.expand-leave-active {
  transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1), 
              opacity 0.3s ease;
  overflow: hidden;
}

.expand-enter-from,
.expand-leave-to {
  max-height: 0 !important;
  opacity: 0;
}

.expand-enter-to,
.expand-leave-from {
  max-height: 220px;
  opacity: 1;
}
</style>
