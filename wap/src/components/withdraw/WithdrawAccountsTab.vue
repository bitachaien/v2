<template>
  <div class="tab-content tab-accounts">
    <div class="bound-card" v-if="savedAccounts.length > 0">
      <div class="account-header">
        <div class="header-left">
          Tài khoản nhận ({{ savedAccounts.length }}/4)
          <img src="/assets/img/comm_icon_retry.svg" class="refresh-icon-green" :class="{ spinning: isRefreshing }" @click="handleRefresh" />
        </div>
        <img :src="showAccountNumbers ? '/assets/img/comm_icon_show.svg' : '/assets/img/comm_icon_hide.svg'" class="toggle-eye" :class="{ active: showAccountNumbers }" @click="showAccountNumbers = !showAccountNumbers" />
      </div>
      
      <div class="bound-account-list">
        <div 
          class="bound-account-item" 
          v-for="acc in savedAccounts" 
          :key="acc.id"
          :class="{ 'is-default': acc.isDefault }"
          @click="selectAccount(acc)"
        >
          <img v-if="acc.type === 'usdt'" src="/assets/img/icon_szhb_normal.png" class="acc-usdt-icon" />
          <img v-else-if="acc.type === 'bank'" src="/assets/img/yhk.svg" class="acc-type-icon" />
          <img v-else-if="acc.type === 'alipay'" src="/assets/img/zfb.svg" class="acc-type-icon" />
          <img v-else-if="acc.type === 'wechat'" src="/assets/img/wx.svg" class="acc-type-icon" />
          <div v-else class="acc-icon-wrapper" :class="getAccountIconClass(acc.type)">
            <span v-if="acc.type === 'huiwang'" class="icon-text">Huiwang</span>
          </div>
          <div class="acc-info">
            <div class="acc-name">
              <template v-if="acc.type === 'usdt'">
                USDT<span class="acc-network">({{ acc.network || 'TRC20' }})</span>
              </template>
              <template v-else>
                {{ acc.bankName }}
              </template>
            </div>
            <div class="acc-number" @click.stop="showAccountNumbers && copyAddress(getAccountAddress(acc))">
              {{ showAccountNumbers ? truncateAddress(getAccountAddress(acc)) : maskAccountNumber(getAccountAddress(acc)) }}
              <van-icon v-if="acc.type === 'usdt' && showAccountNumbers" name="records-o" class="copy-icon" />
            </div>
          </div>
          <div class="acc-action">
            <span v-if="acc.isDefault" class="default-text">Mặc định</span>
            <span v-else class="set-default-btn" @click.stop="setDefaultAccount(acc)">Đặt mặc định</span>
          </div>
          <div v-if="acc.isDefault" class="corner-icon-wrapper">
            <img src="/assets/img/comm_img_corner.svg" class="corner-bg" />
            <img src="/assets/img/comm_icon_gou.svg" class="corner-check" />
          </div>
        </div>
      </div>
    </div>

    <div class="account-header empty-header" v-else>
       <div class="header-left">
          Tài khoản nhận (0/4)
          <img src="/assets/img/comm_icon_retry.svg" class="refresh-icon-green" :class="{ spinning: isRefreshing }" @click="handleRefresh" />
        </div>
    </div>

    <div class="add-account-section" v-if="unboundAccountTypes.length > 0">
      <div class="add-account-card">
        <div class="account-option-item" v-if="!hasAccountType('usdt')" @click="$emit('add-crypto')">
          <img src="/assets/img/icon_szhb_normal.png" class="opt-usdt-icon" />
          <div class="opt-info">
            <div class="opt-name">数字货币(USDT)</div>
          </div>
          <div class="opt-add-btn">Thêm <van-icon name="arrow" /></div>
        </div>

        <div class="account-option-item" v-if="!hasAccountType('bank')" @click="$emit('add-bank')">
          <img src="/assets/img/yhk.svg" class="opt-type-icon" />
          <div class="opt-info">
            <div class="opt-name">Thẻ ngân hàng</div>
          </div>
          <div class="opt-add-btn">Thêm <van-icon name="arrow" /></div>
        </div>

        <div class="account-option-item" v-if="!hasAccountType('alipay')" @click="$emit('add-alipay')">
          <img src="/assets/img/zfb.svg" class="opt-type-icon" />
          <div class="opt-info">
            <div class="opt-name">Alipay</div>
          </div>
          <div class="opt-add-btn">Thêm <van-icon name="arrow" /></div>
        </div>

        <div class="account-option-item" v-if="!hasAccountType('wechat')" @click="$emit('add-wechat')">
          <img src="/assets/img/wx.svg" class="opt-type-icon" />
          <div class="opt-info">
            <div class="opt-name">WeChat</div>
          </div>
          <div class="opt-add-btn">Thêm <van-icon name="arrow" /></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useWithdraw } from './useWithdraw'

const emit = defineEmits(['add-crypto', 'add-bank', 'add-alipay', 'add-wechat', 'select-account'])

const {
  savedAccounts,
  selectedAccount,
  isRefreshing,
  unboundAccountTypes,
  handleRefresh,
  hasAccountType,
  getAccountIconClass,
  getAccountAddress,
  maskAccountNumber,
  truncateAddress,
  setDefaultAccount,
  copyAddress
} = useWithdraw()

const showAccountNumbers = ref(false)

const selectAccount = (acc) => {
  selectedAccount.value = acc
  emit('select-account', acc)
}
</script>

<style scoped>
.tab-accounts {
  padding-top: 10px;
  display: flex;
  flex-direction: column;
  flex: 1;
}

.bound-card {
  width: 407px;
  margin: 0 auto 15px;
  background: #fff;
  border-radius: 8px;
  border: 1px solid #eee;
  overflow: hidden;
}

.account-header {
  padding: 12px 15px;
  font-size: 12.613px;
  color: #666;
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: #fff;
  border-bottom: 1px solid #f5f5f5;
  margin: 0;
}

.account-header.empty-header {
  margin: 0 15px 15px;
  border-radius: 8px;
  border: 1px solid #eee;
  border-bottom: 1px solid #eee;
}

.header-left {
  display: flex;
  align-items: center;
}
.refresh-icon-green {
  width: 16px;
  height: 16px;
  margin-left: 8px;
  cursor: pointer;
  filter: invert(48%) sepia(79%) saturate(374%) hue-rotate(115deg) brightness(93%) contrast(88%);
  transition: transform 0.3s ease;
}
.refresh-icon-green.spinning {
  animation: spin 0.6s linear infinite;
}
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
.toggle-eye {
  width: 20.63px;
  height: 20.63px;
  cursor: pointer;
  filter: brightness(0) saturate(100%) invert(70%) sepia(0%) saturate(0%) hue-rotate(0deg) brightness(90%) contrast(90%);
}
.toggle-eye.active {
  filter: invert(48%) sepia(79%) saturate(374%) hue-rotate(115deg) brightness(93%) contrast(88%);
}

.bound-account-list {
  margin: 0;
  background: #fff;
  border: none;
  border-radius: 0;
}
.bound-account-item {
  width: 384px;
  height: 45px;
  padding: 0 12px;
  display: flex;
  align-items: center;
  background: #fff;
  position: relative;
  transition: all 0.2s;
  margin: 8px auto;
  border-radius: 8px;
  border: 1px solid #eee;
  box-sizing: border-box;
}
.bound-account-item.is-default {
  border: 1px solid #26A17B;
}
.bound-account-item:first-child {
  margin-top: 8px;
}
.bound-account-item:last-child {
  margin-bottom: 8px;
}
.acc-usdt-icon {
  width: 34.39px;
  height: 34.39px;
  border-radius: 4px;
  margin-right: 10px;
  flex-shrink: 0;
}
.bound-account-item .acc-type-icon {
  width: 34.39px;
  height: 34.39px;
  border-radius: 4px;
  margin-right: 10px;
  flex-shrink: 0;
}
.acc-icon-wrapper {
  width: 34.39px;
  height: 34.39px;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 10px;
  color: #fff;
  flex-shrink: 0;
}
.acc-icon-wrapper.crypto {
  background: #26A17B;
}
.acc-icon-wrapper.crypto .icon-text {
  font-size: 16px;
  font-weight: bold;
}
.acc-icon-wrapper.huiwang {
  background: #fff;
  border: 1px solid #eee;
  padding: 2px;
}
.acc-icon-wrapper.huiwang .icon-text {
  font-size: 11px;
  color: #E64A19;
  font-weight: bold;
  transform: scale(0.9);
}
.acc-icon-wrapper.bank {
  background: #1677FF;
}
.acc-icon-wrapper.alipay {
  background: #1677FF;
}
.acc-icon-wrapper.wechat {
  background: #07C160;
}
.acc-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
  min-width: 0;
}
.acc-name {
  font-size: 13.76px;
  color: #333;
  font-weight: 500;
  line-height: 1.3;
  display: flex;
  align-items: center;
}
.acc-network {
  color: #999999;
  font-size: 13.76px;
  font-weight: normal;
  margin-left: 1px;
}
.acc-number {
  font-size: 13.76px;
  color: #999999;
  display: flex;
  align-items: center;
  line-height: 1.3;
  margin-top: 1px;
}
.copy-icon {
  margin-left: 4px;
  width: 13.75px;
  height: 13.75px;
  font-size: 13.75px;
  color: #26A17B;
  cursor: pointer;
}
.acc-action {
  margin-left: 10px;
}
.default-text {
  font-size: 14px;
  color: #999;
}
.set-default-btn {
  font-size: 14px;
  color: #26A17B;
  cursor: pointer;
}
.corner-icon-wrapper {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 17.19px;
  height: 17.19px;
}
.corner-bg {
  width: 17.19px;
  height: 17.19px;
  filter: invert(48%) sepia(79%) saturate(374%) hue-rotate(115deg) brightness(93%) contrast(88%);
}
.corner-check {
  position: absolute;
  bottom: 2px;
  right: 2px;
  width: 8px;
  height: 8px;
  filter: brightness(0) invert(1);
}

.add-account-section {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-height: 0;
}
.add-account-card {
  background: #fff;
  border-top: 1px solid #E3E3E3;
  border-left: none;
  border-right: none;
  border-bottom: none;
  border-top-left-radius: 8px;
  border-top-right-radius: 8px;
  padding: 15px;
  flex: 1;
  margin: 0;
  min-height: calc(100vh - 200px);
}
.account-option-item {
  width: 407.09px;
  height: 39.81px;
  padding: 0 15px;
  display: flex;
  align-items: center;
  background: #fff;
  margin-bottom: 12px;
  box-sizing: border-box;
  border: 1px solid #E3E3E3;
  border-radius: 6px;
  cursor: pointer;
}
.opt-usdt-icon {
  width: 34.39px;
  height: 34.39px;
  border-radius: 4px;
  margin-right: 12px;
  flex-shrink: 0;
}
.opt-type-icon {
  width: 34.39px;
  height: 34.39px;
  border-radius: 4px;
  margin-right: 12px;
  flex-shrink: 0;
}
.opt-info { flex: 1; }
.opt-info .opt-name { font-size: 15px; font-weight: 500; color: #333; }

.opt-add-btn {
  color: #26A17B;
  font-size: 14px;
  display: flex;
  align-items: center;
  gap: 2px;
}
</style>
