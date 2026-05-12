<template>
  <div class="user-transfer-page">
    
    <div class="nav-header glass-panel">
      <div class="nav-left" @click="goBack">
        <van-icon name="arrow-left" class="neon-icon" />
      </div>
      <div class="nav-title">用户转账</div>
      <div class="nav-right" @click="onHelp">
        <van-icon name="service-o" class="neon-icon" />
      </div>
    </div>

    <div class="content-scroll">
      
      <div class="transfer-card glass-card">
        
        <div class="card-section">
          <div class="section-header">
            <span class="title">收款人信息</span>
          </div>
          <div class="input-wrapper">
            <van-field
              v-model="receiverAccount"
              placeholder="请输入对方 ID / 手机号 / 邀请码"
              class="tech-field"
              :border="false"
              @blur="validateReceiver"
              clearable
            >
              <template #left-icon>
                <van-icon name="manager-o" class="field-icon" />
              </template>
              <template #right-icon>
                <div class="contact-btn" @click="showContacts = true">
                  <van-icon name="friends-o" />
                </div>
              </template>
            </van-field>
          </div>
          
          
          <div class="validation-msg" v-if="receiverAccount">
            <div v-if="validating" class="status-text checking">
              <van-loading type="spinner" size="12px" /> 正在核对...
            </div>
            <div v-else-if="receiverValid" class="status-text success animate-fade-in">
              <van-icon name="checked" /> 收款人：{{ receiverName }} (ID: {{ mockReceiverId }})
            </div>
            <div v-else class="status-text error animate-fade-in">
              <van-icon name="clear" /> 未找到该用户，请确认账号
            </div>
          </div>
        </div>

        <div class="divider"></div>

        
        <div class="card-section">
          <div class="section-header">
            <span class="title">转账金额</span>
          </div>
          <div class="input-wrapper amount-wrapper">
            <van-field
              v-model="amount"
              type="number"
              placeholder="请输入转账金额"
              class="tech-field amount-field"
              :border="false"
            >
              <template #left-icon>
                <span class="currency-symbol">¥</span>
              </template>
            </van-field>
          </div>
          
          <div class="balance-row">
            <span class="balance-text">当前可用余额：¥{{ formatAmount(balance) }}</span>
            <span class="max-btn" @click="fillMaxAmount">全部</span>
          </div>
          
          
          <div class="validation-msg" v-if="amount && !validAmount">
            <div class="status-text error" v-if="Number(amount) < minAmount">
              单笔最低转账 {{ minAmount }} 元
            </div>
            <div class="status-text error" v-else-if="Number(amount) > balance">
              余额不足
            </div>
          </div>
        </div>

        <div class="divider"></div>

        
        <div class="card-section">
          <div class="section-header">
            <span class="title">资金密码</span>
          </div>
          <div class="input-wrapper">
            <van-field
              v-model="password"
              type="password"
              placeholder="请输入资金密码"
              class="tech-field"
              :border="false"
              clearable
            >
              <template #left-icon>
                <van-icon name="lock" class="field-icon" />
              </template>
            </van-field>
          </div>
          <div class="forgot-pwd-row">
            <span class="link-text" @click="onForgotPwd">忘记资金密码？</span>
          </div>
        </div>

        <div class="divider"></div>

        
        <div class="card-section">
          <div class="section-header">
            <span class="title">备注说明 <span class="subtitle">(可选)</span></span>
          </div>
          <div class="input-wrapper">
            <van-field
              v-model="remark"
              type="text"
              maxlength="20"
              placeholder="给对方留言（最多20字，可不填）"
              class="tech-field"
              :border="false"
              show-word-limit
            />
          </div>
        </div>
      </div>

      
      <div class="security-tips">
        <p>• 请务必确认收款账号是否正确，转账成功后将无法撤回。</p>
        <p>• 若非本人操作，请立即修改资金密码并联系客服。</p>
      </div>
    </div>

    
    <div class="bottom-bar glass-panel">
      <div class="summary-info">
        <div v-if="canSubmitInfo" class="summary-content">
          <div class="summary-label">向 {{ receiverName || '对方' }} 转账</div>
          <div class="summary-amount">¥{{ formatAmount(Number(amount)) }}</div>
        </div>
        <div v-else class="summary-placeholder">请先填写完整转账信息</div>
      </div>
      
      <div class="action-btn-wrapper">
        <button 
          class="tech-btn-primary" 
          :class="{ disabled: !canSubmit }" 
          @click="onConfirmClick"
          :disabled="!canSubmit"
        >
          确认转账
          <div class="btn-glow"></div>
        </button>
      </div>
    </div>

    
    <van-popup 
      v-model:show="showContacts" 
      position="bottom" 
      round 
      class="tech-popup"
    >
      <div class="popup-header">
        <span>常用联系人</span>
        <van-icon name="cross" @click="showContacts = false" />
      </div>
      <div class="contact-list">
        <div 
          v-for="contact in recentContacts" 
          :key="contact.id" 
          class="contact-item"
          @click="selectContact(contact)"
        >
          <div class="avatar">{{ contact.name.charAt(0) }}</div>
          <div class="info">
            <div class="name">{{ contact.name }}</div>
            <div class="account">ID: {{ contact.account }}</div>
          </div>
          <van-icon name="arrow" class="arrow" />
        </div>
      </div>
    </van-popup>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { showToast, showDialog } from 'vant'

const router = useRouter()

const balance = ref(1288.88)
const receiverAccount = ref('')
const receiverName = ref('')
const mockReceiverId = ref('')
const receiverValid = ref(false)
const validating = ref(false)

const amount = ref<string>('')
const minAmount = 10
const password = ref('')
const remark = ref('')

const showContacts = ref(false)

const recentContacts = [
  { id: 1, name: '李四', account: '10010' },
  { id: 2, name: '王五', account: '10086' },
  { id: 3, name: '赵六', account: '12345' }
]

const validAmount = computed(() => {
  const val = Number(amount.value)
  return val >= minAmount && val <= balance.value
})

const canSubmitInfo = computed(() => {
  return receiverValid.value && validAmount.value
})

const canSubmit = computed(() => {
  return receiverValid.value && validAmount.value && password.value.length > 0 && !validating.value
})

const goBack = () => router.go(-1)
const onHelp = () => showToast('正在连接客服...')
const onForgotPwd = () => showToast('请联系客服重置资金密码')

const formatAmount = (val: number) => {
  return val.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",")
}

const validateReceiver = () => {
  if (!receiverAccount.value) {
    receiverValid.value = false
    receiverName.value = ''
    return
  }

  validating.value = true
  receiverValid.value = false
  

  setTimeout(() => {
    validating.value = false

    if (receiverAccount.value.length > 3) {
      receiverValid.value = true
      receiverName.value = '张三' // Mock Name
      mockReceiverId.value = receiverAccount.value
    } else {
      receiverValid.value = false
      receiverName.value = ''
    }
  }, 800)
}

const fillMaxAmount = () => {
  amount.value = balance.value.toString()
}

const selectContact = (contact: any) => {
  receiverAccount.value = contact.account
  showContacts.value = false
  validateReceiver()
}

const onConfirmClick = () => {
  if (!canSubmit.value) return

  const transferAmt = Number(amount.value)
  const remaining = balance.value - transferAmt

  showDialog({
    title: `确认向 ${receiverName.value} 转账？`,
    message: `转账金额：¥${formatAmount(transferAmt)}\n转账后余额：¥${formatAmount(remaining)}`,
    theme: 'round-button',
    confirmButtonColor: '#EAC26E',
    cancelButtonColor: '#1a1a2e',
    className: 'tech-dialog',
    showCancelButton: true,
  }).then(() => {
    submitTransfer()
  }).catch(() => {

  })
}

const submitTransfer = () => {
  showToast({
    type: 'loading',
    message: '转账处理中...',
    forbidClick: true,
    duration: 0
  })

  setTimeout(() => {
    showToast({
      type: 'success',
      message: '转账成功',
      onClose: () => {

        balance.value -= Number(amount.value)
        amount.value = ''
        password.value = ''

      }
    })
  }, 1500)
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap');

.user-transfer-page {
  min-height: 100vh;
  background: linear-gradient(to bottom, #05070E, #0B0E15);
  color: #fff;
  font-family: -apple-system, BlinkMacSystemFont, 'Helvetica Neue', Helvetica, Segoe UI, Arial, Roboto, 'PingFang SC', sans-serif;
  padding-bottom: 100px; 
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

.content-scroll {
  padding: 20px 16px;
}

.transfer-card {
  padding: 24px 20px;
  position: relative;
  overflow: hidden;
}

.glass-card {
  background: rgba(15, 20, 40, 0.72);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 18px;
  backdrop-filter: blur(16px);
  box-shadow: 0 0 24px rgba(0, 0, 0, 0.65);
}

.card-section {
  margin-bottom: 16px;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  margin-bottom: 10px;
}
.section-header .title {
  font-size: 15px;
  color: #E5E8FF;
  font-weight: 500;
}
.section-header .subtitle {
  font-size: 12px;
  color: rgba(255, 255, 255, 0.4);
}

.input-wrapper {
  background: rgba(0, 0, 0, 0.25);
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  transition: all 0.3s;
  display: flex;
  align-items: center;
  overflow: hidden;
}
.input-wrapper:focus-within {
  border-color: #2D6BFF;
  box-shadow: 0 0 12px rgba(45, 107, 255, 0.25);
}

.tech-field {
  background: transparent !important;
  padding: 12px 14px;
}
:deep(.van-field__control) {
  color: #fff;
  font-size: 15px;
}
:deep(.van-field__control::placeholder) {
  color: rgba(255, 255, 255, 0.3);
}

.field-icon {
  font-size: 18px;
  color: #EAC26E;
  margin-right: 8px;
}

.contact-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  background: rgba(234, 194, 110, 0.1);
  border-radius: 50%;
  color: #EAC26E;
  font-size: 18px;
  cursor: pointer;
}

.amount-field :deep(.van-field__control) {
  font-size: 24px;
  font-weight: bold;
  font-family: 'Orbitron', sans-serif;
  color: #30E0FF;
}
.currency-symbol {
  font-size: 20px;
  color: #EAC26E;
  margin-right: 8px;
  font-family: 'Orbitron', sans-serif;
}

.balance-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 8px;
  font-size: 12px;
}
.balance-text {
  color: rgba(255, 255, 255, 0.55);
}
.max-btn {
  color: #EAC26E;
  cursor: pointer;
  padding: 2px 6px;
  background: rgba(234, 194, 110, 0.1);
  border-radius: 4px;
}

.forgot-pwd-row {
  text-align: right;
  margin-top: 6px;
}
.link-text {
  font-size: 12px;
  color: #2D6BFF;
  cursor: pointer;
}

.divider {
  height: 1px;
  background: rgba(255, 255, 255, 0.06);
  margin: 20px 0;
}

.validation-msg {
  margin-top: 8px;
  font-size: 12px;
}
.status-text {
  display: flex;
  align-items: center;
  gap: 4px;
}
.status-text.checking { color: #30E0FF; }
.status-text.success { color: #29F3C3; }
.status-text.error { color: #FF5C7A; }

.animate-fade-in {
  animation: fadeIn 0.3s ease;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-5px); }
  to { opacity: 1; transform: translateY(0); }
}

.security-tips {
  margin-top: 24px;
  padding: 0 12px;
  font-size: 12px;
  color: rgba(255, 255, 255, 0.45);
  line-height: 1.6;
}

.bottom-bar {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  height: 80px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 20px;
  z-index: 99;
  box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.3);
}

.summary-info {
  display: flex;
  flex-direction: column;
}
.summary-placeholder {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.4);
}
.summary-label {
  font-size: 12px;
  color: rgba(255, 255, 255, 0.6);
}
.summary-amount {
  font-size: 20px;
  color: #EAC26E;
  font-weight: bold;
  font-family: 'Orbitron', sans-serif;
}

.action-btn-wrapper {
  width: 140px;
}

.tech-btn-primary {
  width: 100%;
  height: 44px;
  background: linear-gradient(135deg, #EAC26E, #FFCF7A);
  border: none;
  border-radius: 22px;
  color: #0B0E15;
  font-size: 16px;
  font-weight: bold;
  position: relative;
  overflow: hidden;
  cursor: pointer;
  transition: all 0.3s;
  box-shadow: 0 0 15px rgba(234, 194, 110, 0.3);
}
.tech-btn-primary.disabled {
  background: rgba(255, 255, 255, 0.1);
  color: rgba(255, 255, 255, 0.3);
  box-shadow: none;
  cursor: not-allowed;
}
.tech-btn-primary:not(.disabled):active {
  transform: scale(0.96);
  filter: brightness(0.9);
}

.btn-glow {
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background: radial-gradient(circle, rgba(255,255,255,0.4) 0%, transparent 60%);
  opacity: 0;
  transform: scale(0.5);
  transition: opacity 0.5s;
}
.tech-btn-primary:active .btn-glow {
  opacity: 0.3;
  transform: scale(1);
  transition: 0s;
}

.tech-popup {
  background: #131620 !important;
  max-height: 50%;
  display: flex;
  flex-direction: column;
}
.popup-header {
  padding: 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 16px;
  color: #EAC26E;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}
.contact-list {
  padding: 10px 0;
  overflow-y: auto;
}
.contact-item {
  display: flex;
  align-items: center;
  padding: 12px 20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.03);
  cursor: pointer;
}
.contact-item:active {
  background: rgba(255, 255, 255, 0.05);
}
.contact-item .avatar {
  width: 40px;
  height: 40px;
  background: linear-gradient(135deg, #2D6BFF, #8A4BFF);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  font-weight: bold;
  margin-right: 12px;
}
.contact-item .info {
  flex: 1;
}
.contact-item .name { font-size: 15px; color: #fff; }
.contact-item .account { font-size: 12px; color: rgba(255, 255, 255, 0.5); }
.contact-item .arrow { color: rgba(255, 255, 255, 0.3); }

:deep(.tech-dialog) {
  background: #1a1a2e;
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: #fff;
}
:deep(.van-dialog__header) {
  color: #EAC26E;
}
:deep(.van-dialog__message) {
  color: rgba(255, 255, 255, 0.8);
  white-space: pre-line;
}
:deep(.van-dialog__cancel) {
  background: transparent;
  color: rgba(255, 255, 255, 0.6);
}
:deep(.van-dialog__confirm) {
  background: #EAC26E;
  color: #000;
}
</style>
