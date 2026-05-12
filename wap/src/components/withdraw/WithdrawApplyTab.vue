<template>
  <div class="tab-content tab-apply">
    <div class="balance-bar">
      <span>余额 <span class="gold">{{ balance.toFixed(2) }}</span></span>
      <img src="/assets/img/comm_icon_sx.svg" class="refresh-icon" :class="{ spinning: isBalanceRefreshing }" @click="handleBalanceRefresh" />
    </div>

    <div class="apply-group">
      <div class="account-selector-row">
        <div class="account-selector-wrapper">
          <div class="account-selector card-item" :class="{ 'is-open': showAccountDropdown }" @click="toggleAccountDropdown">
            <div class="left-icon">
              <transition name="icon-switch" mode="out-in">
                <img src="/assets/img/img_tx_tjzh.svg" class="account-icon" v-if="!selectedAccount" key="default" />
                <img v-else-if="selectedAccount.type === 'usdt'" src="/assets/img/icon_szhb_normal.png" class="usdt-icon" :key="'usdt-' + selectedAccount.id" />
                <img v-else-if="selectedAccount.type === 'bank'" src="/assets/img/yhk.svg" class="acc-type-icon" :key="selectedAccount.id" />
                <img v-else-if="selectedAccount.type === 'alipay'" src="/assets/img/zfb.svg" class="acc-type-icon" :key="selectedAccount.id" />
                <img v-else-if="selectedAccount.type === 'wechat'" src="/assets/img/wx.svg" class="acc-type-icon" :key="selectedAccount.id" />
              </transition>
            </div>
          <div class="center-text" :class="{ 'has-value': selectedAccount }">
            <transition name="text-switch" mode="out-in">
              <span v-if="selectedAccount" :key="selectedAccount.id" v-html="getSelectedAccountText(selectedAccount)"></span>
              <span v-else key="default">添加提现账户</span>
            </transition>
          </div>
            <van-icon :name="showAccountDropdown ? 'arrow-up' : 'arrow-down'" class="arrow-icon" />
          </div>
        
          <div class="dropdown-overlay" v-if="showAccountDropdown" @click="showAccountDropdown = false"></div>
          <transition name="dropdown-fade">
          <div class="account-dropdown" v-if="showAccountDropdown">
            <div class="dropdown-section" v-if="savedAccounts.length > 0">
              <div
                class="dropdown-item"
                v-for="acc in savedAccounts"
                :key="acc.id"
                :class="{ active: selectedAccount && selectedAccount.id === acc.id }"
                @click="selectDropdownAccount(acc)"
              >
                <img v-if="acc.type === 'usdt'" src="/assets/img/icon_szhb_normal.png" class="dropdown-usdt-icon" />
                <img v-else-if="acc.type === 'bank'" src="/assets/img/yhk.svg" class="dropdown-type-icon" />
                <img v-else-if="acc.type === 'alipay'" src="/assets/img/zfb.svg" class="dropdown-type-icon" />
                <img v-else-if="acc.type === 'wechat'" src="/assets/img/wx.svg" class="dropdown-type-icon" />
                <div class="dropdown-item-info">
                  <span class="dropdown-item-text" :class="{ 'is-selected': selectedAccount && selectedAccount.id === acc.id }" v-html="getSelectedAccountText(acc)"></span>
                </div>
              </div>
            </div>
            <div class="dropdown-section" v-if="unboundAccountTypes.length > 0">
              <div
                class="dropdown-item add-item"
                v-for="typeItem in unboundAccountTypes"
                :key="typeItem.type"
                @click="handleAddNewAccount(typeItem.type)"
              >
                <img v-if="typeItem.type === 'usdt'" src="/assets/img/icon_szhb_normal.png" class="dropdown-usdt-icon" />
                <img v-else-if="typeItem.type === 'bank'" src="/assets/img/yhk.svg" class="dropdown-type-icon" />
                <img v-else-if="typeItem.type === 'alipay'" src="/assets/img/zfb.svg" class="dropdown-type-icon" />
                <img v-else-if="typeItem.type === 'wechat'" src="/assets/img/wx.svg" class="dropdown-type-icon" />
                <div class="dropdown-item-info">
                  <div class="dropdown-item-name">{{ typeItem.name }}</div>
                </div>
                <span class="add-text">添加</span>
                <van-icon name="arrow" class="add-arrow" />
              </div>
            </div>
          </div>
          </transition>
        </div>
        <div class="account-card-icon" @click="$emit('go-accounts')">
          <img src="/assets/img/icon_tx_txgl.svg" class="card-manage-icon" />
        </div>
      </div>

      <div class="amount-input card-item">
        <div class="currency-symbol">U</div>
        <input 
          type="number" 
          v-model="amount"
          :placeholder="selectedAccount ? `最低${minAmount}元，最高${maxAmount}元` : '请先添加提现账户才能提现'" 
          :disabled="!selectedAccount" 
          class="input-field" 
        />
        <span class="all-btn" @click="fillAllBalance">全部</span>
      </div>
      <div class="amount-divider"></div>
      <div class="withdraw-pwd-section" v-if="savedAccounts.length > 0">
        <div class="pwd-label-row">
          <span class="pwd-label">验证提现密码</span>
          <img :src="showPwdVisible ? '/assets/img/comm_icon_show.svg' : '/assets/img/comm_icon_hide.svg'" class="pwd-eye-icon" :class="{ active: showPwdVisible }" @click="showPwdVisible = !showPwdVisible" />
        </div>
        <div class="pwd-boxes" @click="$emit('show-keyboard')">
          <div class="pwd-box" v-for="i in 6" :key="i">
            <template v-if="withdrawPassword.length >= i">
              <span v-if="showPwdVisible" class="pwd-num">{{ withdrawPassword[i-1] }}</span>
              <span v-else class="pwd-dot">●</span>
            </template>
          </div>
        </div>
        <div class="pwd-tip">温馨提示：即日起：汇旺上分的请汇旺下分，免手续费，秒到账</div>
      </div>
    </div>

    <div class="action-buttons">
      <div class="btn btn-interest" @click="goToYueBao">赚取利息</div>
      <div class="btn btn-submit" :class="{ active: canSubmit }" @click="handleSubmit">确定提现</div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { showToast, showConfirmDialog } from 'vant'
import { withdrawApi } from '@/api/withdraw'
import { useWithdraw } from './useWithdraw'

const router = useRouter()
const emit = defineEmits(['go-accounts', 'show-keyboard', 'add-account'])

const {
  balance,
  minAmount,
  maxAmount,
  hasFundPassword,
  savedAccounts,
  selectedAccount,
  isBalanceRefreshing,
  unboundAccountTypes,
  handleBalanceRefresh,
  getSelectedAccountText,
  checkHasFundPassword,
  loadConfig,
  loadAccounts
} = useWithdraw()

const amount = ref('')
const withdrawPassword = ref('')
const showPwdVisible = ref(false)
const showAccountDropdown = ref(false)

const canSubmit = computed(() => {
  const amt = Number(amount.value)
  return selectedAccount.value && amt >= minAmount.value && amt <= maxAmount.value && amt <= balance.value
})

defineExpose({
  withdrawPassword,
  clearPassword: () => { withdrawPassword.value = '' },
  appendPassword: (key) => {
    if (withdrawPassword.value.length < 6) {
      withdrawPassword.value += key
    }
  },
  deletePassword: () => {
    withdrawPassword.value = withdrawPassword.value.slice(0, -1)
  }
})

const toggleAccountDropdown = () => {
  if (!hasFundPassword.value) {
    showConfirmDialog({
      title: '提示',
      message: '您还未设置资金密码，请先设置资金密码',
      confirmButtonText: '去设置',
      cancelButtonText: '取消',
      confirmButtonColor: '#26A17B'
    }).then(() => {
      router.push('/security/fund-pwd')
    }).catch(() => {})
    return
  }
  showAccountDropdown.value = !showAccountDropdown.value
}

const selectDropdownAccount = (acc) => {
  selectedAccount.value = acc
  showAccountDropdown.value = false
}

const handleAddNewAccount = (type) => {
  showAccountDropdown.value = false
  emit('add-account', type)
}

const fillAllBalance = () => {
  if (!selectedAccount.value) {
    showToast('请先选择提现账户')
    return
  }
  amount.value = balance.value
}

const goToYueBao = () => {
  router.push('/yue-bao')
}

const handleSubmit = async () => {
  if (!canSubmit.value) return
  if (!checkHasFundPassword()) return
  
  if (!withdrawPassword.value) {
    showToast('请输入资金密码')
    return
  }
  
  try {
    const res = await withdrawApi.submit({
      accountId: selectedAccount.value.id,
      amount: Number(amount.value),
      fundPassword: withdrawPassword.value
    })
    if (res.code === 0) {
      showToast('提现申请已提交')
      amount.value = ''
      withdrawPassword.value = ''
      loadConfig()
    } else {
      if (res.data?.needSetFundPwd) {
        checkHasFundPassword()
      } else {
        showToast(res.message || '提现失败')
      }
    }
  } catch (e) {
    showToast('提现失败')
  }
}
</script>

<style scoped>
.tab-apply {
  padding-bottom: 30px;
}

.balance-bar {
  display: flex;
  align-items: center;
  font-size: 14px;
  color: #333;
  margin-bottom: 10px;
  padding: 15px;
  background: #fff;
}
.balance-bar .gold { color: #FFAA09; margin-left: 5px; font-weight: 600; font-size: 16px; margin-right: 6px; }
.refresh-icon { 
  width: 16px; 
  height: 16px; 
  filter: invert(48%) sepia(79%) saturate(374%) hue-rotate(115deg) brightness(93%) contrast(88%);
  transition: transform 0.3s ease;
  cursor: pointer;
}
.refresh-icon.spinning {
  animation: spin 0.6s linear infinite;
}
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.apply-group {
  padding: 0 15px;
  margin-bottom: 30px;
}
.card-item {
  background: #fff;
  border-radius: 6px;
  margin-bottom: 10px;
  border: 1px solid #eaeaea;
}

.account-selector-row {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 10px;
}

.account-selector-wrapper {
  position: relative;
}

.account-selector {
  width: 368px;
  height: 40px;
  padding: 0 15px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-sizing: border-box;
  margin-bottom: 0;
}
.account-selector.is-open {
  border-color: #26A17B;
}

.account-card-icon {
  width: 27.52px;
  height: 27.52px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}
.card-manage-icon {
  width: 27.52px;
  height: 27.52px;
  filter: invert(48%) sepia(79%) saturate(374%) hue-rotate(115deg) brightness(93%) contrast(88%);
}

.left-icon {
  margin-right: 12px;
  display: flex;
  align-items: center;
}
.account-icon {
  width: 24px;
  height: 24px;
  filter: invert(48%) sepia(79%) saturate(374%) hue-rotate(115deg) brightness(93%) contrast(88%);
}
.usdt-icon {
  width: 27.52px;
  height: 27.52px;
  border-radius: 4px;
}
.acc-type-icon {
  width: 27.52px;
  height: 27.52px;
  border-radius: 4px;
}

.center-text { flex: 1; font-size: 12.61px; color: #ccc; }
.center-text.has-value { color: #333; }
.center-text :deep(.network-text) { color: #999999; }
.arrow-icon { color: #ccc; font-size: 16px; }

.dropdown-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 199;
}

.account-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: #fff;
  border: 1px solid #eaeaea;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  z-index: 200;
  margin-top: 4px;
  max-height: 320px;
  overflow-y: auto;
  transform-origin: top center;
}

.dropdown-fade-enter-active {
  animation: dropdown-in 0.2s ease-out;
}
.dropdown-fade-leave-active {
  animation: dropdown-out 0.15s ease-in;
}
@keyframes dropdown-in {
  0% { opacity: 0; transform: scaleY(0.8); }
  100% { opacity: 1; transform: scaleY(1); }
}
@keyframes dropdown-out {
  0% { opacity: 1; transform: scaleY(1); }
  100% { opacity: 0; transform: scaleY(0.8); }
}

.icon-switch-enter-active,
.icon-switch-leave-active {
  transition: all 0.2s ease;
}
.icon-switch-enter-from { opacity: 0; transform: scale(0.8); }
.icon-switch-leave-to { opacity: 0; transform: scale(0.8); }

.text-switch-enter-active,
.text-switch-leave-active {
  transition: all 0.2s ease;
}
.text-switch-enter-from { opacity: 0; transform: translateY(-5px); }
.text-switch-leave-to { opacity: 0; transform: translateY(5px); }

.dropdown-section {
  padding: 8px 0;
}
.dropdown-section:last-child {
  padding-top: 4px;
}
.dropdown-section:not(:last-child) {
  border-bottom: 1px solid #f5f5f5;
}

.dropdown-item {
  display: flex;
  align-items: center;
  padding: 10px 15px;
  cursor: pointer;
  transition: background 0.2s;
}
.dropdown-item:active {
  background: #f7f8fa;
}
.dropdown-item.active {
  background: transparent;
}

.dropdown-usdt-icon {
  width: 27.52px;
  height: 27.52px;
  margin-right: 10px;
  border-radius: 4px;
}
.dropdown-type-icon {
  width: 27.52px;
  height: 27.52px;
  margin-right: 10px;
  border-radius: 4px;
}

.dropdown-item-info {
  flex: 1;
}
.dropdown-item-text {
  font-size: 13.76px;
  color: #333;
}
.dropdown-item-text.is-selected {
  color: #26A17B;
}
.dropdown-item-text :deep(.network-text) {
  color: #999999;
}
.dropdown-item-name {
  font-size: 14px;
  color: #333;
  font-weight: 500;
}

.add-text {
  color: #26A17B;
  font-size: 14px;
}
.add-arrow {
  color: #999;
  font-size: 14px;
  margin-left: 2px;
}

.dropdown-item.add-item {
  width: 336.66px;
  height: 39.81px;
  border: 1px solid #E3E3E3;
  border-radius: 6px;
  margin: 4px auto;
  padding: 0 12px;
  box-sizing: border-box;
}
.dropdown-item.add-item:first-child {
  margin-top: 0;
}

.amount-input {
  width: 407.09px;
  height: 40.13px;
  padding: 0 15px;
  display: flex;
  align-items: center;
  box-sizing: border-box;
}
.currency-symbol { font-size: 18px; color: #333; margin-right: 10px; font-weight: 500; }
.input-field {
  flex: 1;
  font-size: 15px;
  border: none;
  outline: none;
  background: transparent;
  font-weight: 400;
  color: #333;
}
.input-field::placeholder {
  font-size: 14px;
  color: #ccc;
  font-weight: normal;
}
.all-btn {
  color: #26A17B;
  font-size: 14px;
  cursor: pointer;
}

.amount-divider {
  width: 407.09px;
  height: 1px;
  background: #eaeaea;
  margin: 10px 0;
}

.withdraw-pwd-section {
  margin-top: 15px;
}
.pwd-label-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 10px;
}
.pwd-label {
  font-size: 14px;
  color: #333;
}
.pwd-eye-icon {
  width: 18px;
  height: 18px;
  cursor: pointer;
  filter: brightness(0) saturate(100%) invert(70%) sepia(0%) saturate(0%) hue-rotate(0deg) brightness(90%) contrast(90%);
}
.pwd-eye-icon.active {
  filter: invert(48%) sepia(79%) saturate(374%) hue-rotate(115deg) brightness(93%) contrast(88%);
}
.pwd-boxes {
  display: flex;
  gap: 8px;
}
.pwd-box {
  width: 68px;
  height: 55px;
  background: #fff;
  border: 1px solid #eaeaea;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}
.pwd-dot {
  font-size: 24px;
  color: #333;
}
.pwd-num {
  font-size: 20px;
  color: #333;
  font-weight: 500;
}
.pwd-tip {
  font-size: 12px;
  color: #999;
  margin-top: 10px;
}

.action-buttons {
  display: flex;
  gap: 12px;
  padding: 0 15px;
}
.btn {
  border-radius: 6px;
  font-size: 15px;
  font-weight: 500;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-interest {
  width: 195px;
  height: 38px;
  border: 1px solid #26A17B;
  background: #fff;
  color: #26A17B;
}
.btn-submit {
  width: 197px;
  height: 40px;
  background: #999;
  color: #fff;
}
.btn-submit.active {
  background: #26A17B;
}
</style>
