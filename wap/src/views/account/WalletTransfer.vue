<template>
  <div class="wallet-transfer-page">
    
    <div class="nav-header glass-panel">
      <div class="nav-left" @click="goBack">
        <van-icon name="arrow-left" class="neon-icon" />
      </div>
      <div class="nav-title">额度转换</div>
      <div class="nav-right">
        <van-icon name="question-o" class="neon-icon" />
      </div>
    </div>

    <div class="content-area">
      
      <div class="balance-group">
        
        <div class="balance-card glass-card animate-up" style="animation-delay: 0.1s">
          <div class="card-header">
            <div class="icon-box wallet">
              <van-icon name="paid" />
            </div>
            <span class="card-label">中心钱包余额</span>
          </div>
          <div class="card-value">
            <span class="currency">¥</span>
            <span class="number">{{ formatAmount(balances.wallet) }}</span>
          </div>
          <div class="card-bg-deco"></div>
        </div>

        
        <div class="balance-card glass-card animate-up" style="animation-delay: 0.2s">
          <div class="card-header">
            <div class="icon-box game">
              <van-icon name="play-circle-o" />
            </div>
            <span class="card-label">娱乐钱包余额</span>
          </div>
          <div class="card-value game-color">
            <span class="currency">¥</span>
            <span class="number">{{ formatAmount(balances.game) }}</span>
          </div>
          <div class="card-bg-deco"></div>
        </div>
      </div>

      
      <div class="transfer-form glass-card animate-up" style="animation-delay: 0.3s">
        <div class="form-title">
          <span class="neon-bar"></span>
          转换操作
        </div>

        
        <div class="form-item">
          <div class="label">转换方向</div>
          <div class="tech-selector" @click="showDirectionPicker = true">
            <div class="selected-val">
              <div class="direction-display">
                <span :class="{ active: form.direction === 'wallet_to_game' }">中心钱包</span>
                <van-icon name="exchange" class="exchange-icon" />
                <span :class="{ active: form.direction === 'game_to_wallet' }">娱乐钱包</span>
              </div>
            </div>
            <van-icon name="arrow-down" class="arrow" />
          </div>
        </div>

        
        <div class="form-item">
          <div class="label">转换金额</div>
          <div class="tech-input-box" :class="{ focused: isInputFocused }">
            <span class="prefix">¥</span>
            <input 
              type="number" 
              v-model="form.amount" 
              placeholder="请输入转换金额"
              @focus="isInputFocused = true"
              @blur="isInputFocused = false"
            />
            <span class="max-btn" @click="setMaxAmount">最大</span>
          </div>
        </div>

        
        <div class="action-group">
          <button class="btn-reset" @click="resetForm">
            <van-icon name="replay" /> 重置
          </button>
          <button class="btn-confirm" @click="handleTransfer">
            <van-icon name="success" /> 立即转换
            <div class="btn-glow"></div>
          </button>
        </div>
      </div>
    </div>

    
    <van-action-sheet 
      v-model:show="showDirectionPicker" 
      :actions="directionActions" 
      @select="onSelectDirection"
      cancel-text="取消"
      class="tech-sheet"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { showToast, showDialog } from 'vant'

const router = useRouter()

const balances = reactive({
  wallet: 938.88,
  game: 12580.00
})

const form = reactive({
  direction: 'wallet_to_game', // 'wallet_to_game' | 'game_to_wallet'
  amount: ''
})

const isInputFocused = ref(false)
const showDirectionPicker = ref(false)

const directionActions = [
  { name: '中心钱包 → 娱乐钱包', value: 'wallet_to_game', className: 'tech-action' },
  { name: '娱乐钱包 → 中心钱包', value: 'game_to_wallet', className: 'tech-action' }
]

const goBack = () => router.go(-1)

const formatAmount = (val: number) => {
  return val.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",")
}

const setMaxAmount = () => {
  if (form.direction === 'wallet_to_game') {
    form.amount = Math.floor(balances.wallet).toString()
  } else {
    form.amount = Math.floor(balances.game).toString()
  }
}

const resetForm = () => {
  form.amount = ''
  form.direction = 'wallet_to_game'
}

const onSelectDirection = (action: any) => {
  form.direction = action.value
  showDirectionPicker.value = false
}

const handleTransfer = () => {
  const amt = parseFloat(form.amount)
  

  if (!amt || amt <= 0) {
    showToast('请输入有效金额')
    return
  }

  const max = form.direction === 'wallet_to_game' ? balances.wallet : balances.game
  if (amt > max) {
    showToast('余额不足')
    return
  }

  showToast({
    type: 'loading',
    message: '转换中...',
    forbidClick: true,
    duration: 1000,
    onClose: () => {

      if (form.direction === 'wallet_to_game') {
        balances.wallet -= amt
        balances.game += amt
      } else {
        balances.game -= amt
        balances.wallet += amt
      }
      
      showDialog({
        title: '转换成功',
        message: `成功转换 ${amt} 元`,
        theme: 'round-button',
        confirmButtonColor: '#EAC26E'
      })
      
      form.amount = ''
    }
  })
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap');

.wallet-transfer-page {
  min-height: 100vh;
  background: linear-gradient(to bottom, #05070E, #0B0E15);
  color: #fff;
  font-family: -apple-system, BlinkMacSystemFont, 'Helvetica Neue', Helvetica, Segoe UI, Arial, Roboto, 'PingFang SC', sans-serif;
  padding-bottom: 40px;
}

.nav-header {
  height: 50px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 16px;
  position: sticky;
  top: 0;
  z-index: 100;
}
.glass-panel {
  background: rgba(16, 24, 40, 0.72);
  backdrop-filter: blur(18px);
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}
.nav-title {
  font-size: 18px;
  font-weight: 600;
  color: #EAC26E;
  text-shadow: 0 0 10px rgba(234, 194, 110, 0.3);
}
.neon-icon {
  font-size: 20px;
  color: #EAC26E;
}

.content-area {
  padding: 20px 16px;
}

.balance-group {
  display: flex;
  flex-direction: column;
  gap: 16px;
  margin-bottom: 24px;
}

.balance-card {
  position: relative;
  padding: 20px;
  overflow: hidden;
}

.glass-card {
  background: rgba(16, 24, 40, 0.72);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 16px;
  backdrop-filter: blur(18px);
  box-shadow: 0 18px 40px rgba(0, 0, 0, 0.2);
}

.card-header {
  display: flex;
  align-items: center;
  margin-bottom: 12px;
  z-index: 2;
  position: relative;
}

.icon-box {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 12px;
  font-size: 20px;
}

.icon-box.wallet {
  background: rgba(234, 194, 110, 0.1);
  color: #EAC26E;
  box-shadow: 0 0 10px rgba(234, 194, 110, 0.2);
}

.icon-box.game {
  background: rgba(41, 243, 195, 0.1);
  color: #29F3C3;
  box-shadow: 0 0 10px rgba(41, 243, 195, 0.2);
}

.card-label {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.6);
}

.card-value {
  font-family: 'Orbitron', sans-serif;
  color: #EAC26E;
  text-shadow: 0 0 15px rgba(234, 194, 110, 0.3);
  z-index: 2;
  position: relative;
}
.card-value.game-color {
  color: #29F3C3;
  text-shadow: 0 0 15px rgba(41, 243, 195, 0.3);
}

.card-value .currency { font-size: 18px; margin-right: 4px; }
.card-value .number { font-size: 32px; font-weight: bold; }

.card-bg-deco {
  position: absolute;
  right: -20px;
  top: -20px;
  width: 100px;
  height: 100px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(255,255,255,0.03) 0%, transparent 70%);
  z-index: 1;
}

.transfer-form {
  padding: 24px 20px;
}

.form-title {
  font-size: 16px;
  font-weight: 600;
  margin-bottom: 24px;
  display: flex;
  align-items: center;
}
.neon-bar {
  width: 4px;
  height: 16px;
  background: #EAC26E;
  margin-right: 10px;
  border-radius: 2px;
  box-shadow: 0 0 8px #EAC26E;
}

.form-item {
  margin-bottom: 24px;
}

.form-item .label {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.5);
  margin-bottom: 10px;
}

.tech-selector {
  background: rgba(0, 0, 0, 0.3);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  height: 50px;
  padding: 0 16px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  cursor: pointer;
  transition: all 0.3s;
}
.tech-selector:active {
  border-color: #EAC26E;
  background: rgba(234, 194, 110, 0.05);
}

.direction-display {
  display: flex;
  align-items: center;
  font-size: 15px;
  color: rgba(255, 255, 255, 0.4);
}
.direction-display span.active {
  color: #fff;
  font-weight: bold;
  text-shadow: 0 0 8px rgba(255,255,255,0.5);
}
.exchange-icon {
  margin: 0 12px;
  color: #EAC26E;
  font-size: 18px;
}
.arrow { color: rgba(255, 255, 255, 0.3); }

.tech-input-box {
  background: rgba(0, 0, 0, 0.3);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  height: 50px;
  padding: 0 16px;
  display: flex;
  align-items: center;
  transition: all 0.3s;
}

.tech-input-box.focused {
  border-color: #7C4DFF;
  box-shadow: 0 0 15px rgba(124, 77, 255, 0.3);
}

.tech-input-box .prefix {
  font-size: 20px;
  color: #EAC26E;
  margin-right: 10px;
  font-family: 'Orbitron', sans-serif;
}

.tech-input-box input {
  flex: 1;
  background: transparent;
  border: none;
  color: #fff;
  font-size: 18px;
  font-weight: bold;
  outline: none;
}

.max-btn {
  color: #3BB8FF;
  font-size: 14px;
  cursor: pointer;
  padding: 4px 8px;
}

.action-group {
  display: flex;
  gap: 16px;
  margin-top: 40px;
}

.btn-reset, .btn-confirm {
  height: 48px;
  border-radius: 24px;
  font-size: 16px;
  font-weight: bold;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-reset {
  flex: 1;
  background: transparent;
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: rgba(255, 255, 255, 0.7);
}
.btn-reset:active {
  background: rgba(255, 255, 255, 0.1);
  transform: scale(0.98);
}

.btn-confirm {
  flex: 2;
  background: linear-gradient(135deg, #EAC26E, #C9A050);
  border: none;
  color: #0B0E15;
  box-shadow: 0 0 20px rgba(234, 194, 110, 0.3);
  position: relative;
  overflow: hidden;
}
.btn-confirm:active {
  transform: scale(0.98);
  box-shadow: 0 0 10px rgba(234, 194, 110, 0.2);
}

.animate-up {
  animation: slideUp 0.6s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
  opacity: 0;
  transform: translateY(30px);
}

@keyframes slideUp {
  to { opacity: 1; transform: translateY(0); }
}

:deep(.tech-sheet) {
  background: #1a1a2e;
  color: #fff;
}
:deep(.van-action-sheet__item) {
  background: transparent;
  color: #fff;
}
:deep(.van-action-sheet__cancel) {
  background: #151525;
  color: rgba(255, 255, 255, 0.6);
}
</style>
