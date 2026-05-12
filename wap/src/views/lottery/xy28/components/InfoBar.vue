<template>
  <div class="info-bar" v-show="show">
    <div class="issue-info">
      <div class="issue-label">第 <span class="issue-num">{{ currentIssue }}</span>期开奖</div>
      <div class="countdown-box" :class="countdownStatus">{{ countdownText }}</div>
    </div>
    <div class="balance-info">
      <div class="balance-label">
        <span>账户余额</span>
        <img 
          src="/assets/img/comm_icon_sx1.svg" 
          class="refresh-icon" 
          :class="{ spinning: refreshing }" 
          @click="$emit('refresh')" 
        />
      </div>
      <div class="balance-row">
        <span class="balance-value">{{ balance }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  show: Boolean,
  currentIssue: String,
  countdownStatus: String,
  countdownText: String,
  balance: String,
  refreshing: Boolean
})

defineEmits(['refresh'])
</script>

<style lang="less" scoped>
.info-bar {
  height: 60px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #fff;
  border-bottom: 1px solid #f0f0f0;
  position: relative;
  z-index: 13;
  padding: 0 10px;
  
  .issue-info, .balance-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    height: 100%;
    gap: 4px;
    
    .issue-label {
      font-size: 13px;
      color: #333;
      .issue-num { color: #5691fe; margin: 0 2px; }
    }
    
    .countdown-box {
      background: #5c6275;
      color: #fff;
      font-size: 14px;
      padding: 2px 12px;
      border-radius: 4px;
      font-family: Arial, sans-serif;
      font-weight: bold;
      
      &.drawing {
        background: #f5222d;
      }
      
      &.sealed {
        background: #faad14;
      }
    }
    
    .balance-label {
      font-size: 13px;
      color: #333;
      display: flex;
      align-items: center;
      gap: 4px;
      
      .refresh-icon {
        width: 14px;
        height: 14px;
        cursor: pointer;
        
        &.spinning {
          animation: spin 1s linear infinite;
        }
      }
    }
    
    .balance-row {
      display: flex;
      align-items: center;
      
      .balance-value {
        color: #f5222d;
        font-size: 16px;
        font-weight: 500;
      }
    }
  }
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>
