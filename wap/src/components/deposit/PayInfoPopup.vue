<template>
  <van-popup 
    v-model:show="visible" 
    position="center" 
    round 
    :style="{ width: '92%', maxHeight: '85vh' }"
    class="pay-info-popup"
    :overlay-style="{ background: 'rgba(0,0,0,0.5)' }"
  >
    <div class="pay-info-view">
      <div class="pay-info-header">
        <div class="pay-info-title">{{ order?.paytypeName || 'Nạp tiền' }}</div>
      </div>

      <div class="pay-info-body">
        <div class="pay-amount-display">
          <div class="pay-amount-label">Số tiền nạp</div>
          <div class="pay-amount-value">
            <span class="currency">{{ order?.paytype === 'USDT' ? '₮' : '¥' }}</span>
            <span class="num">{{ order?.amount }}</span>
          </div>
          <div class="pay-status-tag">Chờ thanh toán</div>
        </div>

        <div class="info-cell-group">
          <div class="info-cell">
            <span class="cell-label">Mã đơn</span>
            <div class="cell-value">
              {{ order?.trano }}
              <van-icon name="description-o" class="copy-icon" @click="copyText(order?.trano)" />
            </div>
          </div>
          <div class="info-cell" v-if="order?.fuyanma">
            <span class="cell-label">Mã ghi chú</span>
            <div class="cell-value highlight-red">
              {{ order?.fuyanma }}
              <van-icon name="description-o" class="copy-icon" @click="copyText(order?.fuyanma)" />
            </div>
          </div>
        </div>

        <div class="pay-content-area">
          <div class="chain-selector" v-if="order?.paytype === 'USDT'">
            <div 
              class="chain-option" 
              :class="{ active: selectedChain === 'TRC20' }"
              @click="selectedChain = 'TRC20'"
            >
              TRC20
            </div>
            <div 
              class="chain-option" 
              :class="{ active: selectedChain === 'ERC20' }"
              @click="selectedChain = 'ERC20'"
            >
              ERC20
            </div>
          </div>

          <div class="qr-container" v-if="currentPayQrCode">
            <div class="qr-wrapper">
              <img :src="currentPayQrCode" class="qr-image" />
            </div>
            <div class="qr-text">Quét mã thanh toán</div>
          </div>

          <div class="pay-details-box" v-if="order?.paytype === 'linepay' && order.bankInfo">
            <div class="bank-card">
              <div class="bank-row">
                <span class="bank-label">Ngân hàng</span>
                <span class="bank-val">{{ order.bankInfo.bankName }}</span>
                <span class="copy-btn-text" @click="copyText(order.bankInfo.bankName)">Sao chép</span>
              </div>
              <div class="bank-row" v-if="order.bankInfo.bankBranch">
                <span class="bank-label">Chi nhánh</span>
                <span class="bank-val">{{ order.bankInfo.bankBranch }}</span>
                <span class="copy-btn-text" @click="copyText(order.bankInfo.bankBranch)">Sao chép</span>
              </div>
              <div class="bank-row">
                <span class="bank-label">Họ tên</span>
                <span class="bank-val">{{ order.bankInfo.accountName }}</span>
                <span class="copy-btn-text" @click="copyText(order.bankInfo.accountName)">Sao chép</span>
              </div>
              <div class="bank-row">
                <span class="bank-label">Số thẻ</span>
                <span class="bank-val bank-code">{{ order.bankInfo.bankCode }}</span>
                <span class="copy-btn-text" @click="copyText(order.bankInfo.bankCode)">Sao chép</span>
              </div>
            </div>
          </div>

          <div class="pay-details-box" v-else-if="currentPayAddress">
            <div class="address-display">
              <div class="address-label">
                {{ order?.paytype === 'USDT' ? 'Địa chỉ nạp' : 'Tài khoản nhận' }}
              </div>
              <div class="address-content">
                <div class="address-text">{{ currentPayAddress }}</div>
              </div>
              <div class="address-actions">
                <div class="action-btn" @click="copyAddress">
                  <van-icon name="description" /> Sao chép
                </div>
              </div>
            </div>
            <div class="payee-name" v-if="order?.accountName">
              Người nhận: {{ order.accountName }}
            </div>
          </div>

          <div class="no-address-tip" v-else-if="order?.paytype === 'USDT' && !currentPayAddress">
            <van-icon name="warning-o" size="40" color="#ff976a" />
            <div class="tip-text">Địa chỉ nhận chưa được cấu hình</div>
            <div class="tip-sub">Vui lòng liên hệ CSKH để lấy địa chỉ nạp</div>
          </div>
        </div>

        <div class="pay-footer-section">
          <div class="warning-tips">
            <van-icon name="info-o" />
            <span v-if="order?.paytype === 'USDT'">Vui lòng chuyển qua mạng {{ selectedChain }}, nếu không sẽ mất tiền</span>
            <span v-else>Vui lòng xác nhận thông tin trước khi chuyển</span>
          </div>
          
          <div class="confirm-check-row">
            <van-checkbox v-model="payConfirmed" checked-color="#04BE02" icon-size="16px">
              Tôi đã chuyển khoản
            </van-checkbox>
          </div>
          
          <div 
            class="submit-pay-btn" 
            :class="{ disabled: !payConfirmed }"
            @click="confirmPayment"
          >
            Xác nhận
          </div>
        </div>
      </div>
    </div>
    
    <div class="close-circle-outer" @click="visible = false">
      <van-icon name="cross" size="18" />
    </div>
  </van-popup>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { showToast, showLoadingToast, closeToast } from 'vant'
import { rechargeApi } from '@/api/recharge'

const props = defineProps({
  show: { type: Boolean, default: false },
  order: { type: Object, default: null },
  usdtConfig: { type: Object, default: () => ({ trc20Address: '', erc20Address: '', rate: 7.2 }) }
})

const emit = defineEmits(['update:show', 'confirmed'])

const visible = computed({
  get: () => props.show,
  set: (val) => emit('update:show', val)
})

const selectedChain = ref('TRC20')
const payConfirmed = ref(false)

const currentPayAddress = computed(() => {
  if (!props.order) return ''
  const type = props.order.paytype
  
  if (type === 'USDT') {
    if (selectedChain.value === 'TRC20') {
      return props.usdtConfig.trc20Address || props.order.address || ''
    }
    return props.usdtConfig.erc20Address || ''
  }
  
  if (type === 'alipay' || type === 'weixin') {
    return props.order.account || ''
  }
  
  return ''
})

const currentPayQrCode = computed(() => {
  if (!props.order) return ''
  const type = props.order.paytype
  
  if (type === 'USDT') {
    const addr = currentPayAddress.value
    if (!addr) return ''
    return `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${addr}&color=000000&bgcolor=ffffff`
  }
  
  if (type === 'alipay' || type === 'weixin') {
    return props.order.qrcode || ''
  }
  
  return ''
})

const copyText = async (text) => {
  if (!text) return
  try {
    await navigator.clipboard.writeText(text)
    showToast('Đã sao chép')
  } catch (e) {
    showToast('Sao chép thất bại')
  }
}

const copyAddress = async () => {
  await copyText(currentPayAddress.value)
  showToast('Đã sao chép địa chỉ')
}

const confirmPayment = async () => {
  if (!payConfirmed.value) {
    showToast('Vui lòng xác nhận đã chuyển khoản')
    return
  }
  
  if (!props.order?.trano) {
    showToast('Thông tin đơn hàng không tồn tại')
    return
  }
  
  try {
    showLoadingToast({ message: 'Đang gửi...', forbidClick: true, duration: 0 })
    const res = await rechargeApi.confirm(props.order.trano)
    closeToast()
    
    if (res.code === 0) {
      showToast({ type: 'success', message: 'Xác nhận thành công, vui lòng chờ duyệt' })
      visible.value = false
      emit('confirmed')
    } else {
      showToast(res.message || 'Xác nhận thất bại')
    }
  } catch (e) {
    closeToast()
    showToast('Lỗi mạng, vui lòng thử lại')
  }
}

watch(visible, (val) => {
  if (!val) {
    payConfirmed.value = false
  }
})
</script>

<style scoped>
.pay-info-popup {
  overflow: visible !important;
  background: transparent !important;
}

.pay-info-view {
  background: #f9f9f9;
  border-radius: 16px;
  display: flex;
  flex-direction: column;
  max-height: 85vh;
  overflow: hidden;
}

.pay-info-header {
  background: #fff;
  padding: 15px;
  text-align: center;
  border-bottom: 1px solid #f0f0f0;
}

.pay-info-title {
  font-size: 16px;
  font-weight: 600;
  color: #333;
}

.pay-info-body {
  flex: 1;
  overflow-y: auto;
  max-height: calc(85vh - 50px);
  padding-bottom: 20px;
}

.pay-amount-display {
  background: #fff;
  padding: 25px 0 20px;
  text-align: center;
}

.pay-amount-label {
  font-size: 13px;
  color: #999;
  margin-bottom: 8px;
}

.pay-amount-value {
  display: flex;
  align-items: baseline;
  justify-content: center;
  color: #333;
  font-weight: 600;
}

.pay-amount-value .currency {
  font-size: 20px;
  margin-right: 4px;
}

.pay-amount-value .num {
  font-size: 32px;
  font-family: DIN Alternate, sans-serif;
}

.pay-status-tag {
  display: inline-block;
  margin-top: 8px;
  background: rgba(255, 149, 0, 0.1);
  color: #ff9500;
  font-size: 12px;
  padding: 2px 10px;
  border-radius: 10px;
}

.info-cell-group {
  background: #fff;
  padding: 0 15px 15px;
  margin-bottom: 10px;
}

.info-cell {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 0;
  border-top: 1px solid #f5f5f5;
  font-size: 14px;
}

.cell-label {
  color: #999;
}

.cell-value {
  color: #333;
  display: flex;
  align-items: center;
  gap: 5px;
}

.highlight-red {
  color: #ff4d4f;
  font-weight: 600;
}

.copy-icon {
  color: #26A17B;
  font-size: 14px;
  cursor: pointer;
}

.pay-content-area {
  background: #fff;
  margin: 0 15px;
  border-radius: 12px;
  padding: 20px 15px;
}

.chain-selector {
  display: flex;
  background: #f5f5f5;
  padding: 4px;
  border-radius: 8px;
  margin-bottom: 20px;
}

.chain-option {
  flex: 1;
  text-align: center;
  padding: 8px 0;
  font-size: 14px;
  color: #666;
  border-radius: 6px;
  transition: all 0.3s;
  cursor: pointer;
}

.chain-option.active {
  background: #fff;
  color: #04BE02;
  font-weight: 600;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.qr-container {
  text-align: center;
  margin-bottom: 20px;
}

.qr-wrapper {
  width: 160px;
  height: 160px;
  margin: 0 auto 10px;
  padding: 8px;
  border: 1px solid #eee;
  border-radius: 8px;
}

.qr-image {
  width: 100%;
  height: 100%;
}

.qr-text {
  font-size: 12px;
  color: #999;
}

.address-display {
  background: #f9f9f9;
  border-radius: 8px;
  padding: 15px;
  text-align: center;
}

.address-label {
  font-size: 12px;
  color: #999;
  margin-bottom: 8px;
}

.address-text {
  font-size: 13px;
  color: #333;
  word-break: break-all;
  line-height: 1.4;
  font-weight: 500;
  margin-bottom: 12px;
}

.address-actions {
  display: flex;
  justify-content: center;
}

.action-btn {
  background: #26A17B;
  color: #fff;
  font-size: 12px;
  padding: 6px 20px;
  border-radius: 20px;
  display: flex;
  align-items: center;
  gap: 4px;
  cursor: pointer;
}

.payee-name {
  margin-top: 10px;
  text-align: center;
  font-size: 13px;
  color: #666;
}

.bank-card {
  background: linear-gradient(135deg, #f9f9f9 0%, #f0f0f0 100%);
  border: 1px solid #e8e8e8;
  border-radius: 10px;
  padding: 15px;
}

.bank-row {
  display: flex;
  align-items: center;
  margin-bottom: 10px;
  font-size: 14px;
}

.bank-row:last-child {
  margin-bottom: 0;
}

.bank-label {
  color: #999;
  width: 50px;
}

.bank-val {
  color: #333;
  font-weight: 500;
  flex: 1;
}

.bank-code {
  font-family: monospace;
  letter-spacing: 1px;
}

.copy-btn-text {
  color: #26A17B;
  font-size: 12px;
  padding: 2px 8px;
  border: 1px solid #26A17B;
  border-radius: 4px;
  cursor: pointer;
}

.no-address-tip {
  background: #fff;
  border-radius: 8px;
  padding: 30px 15px;
  text-align: center;
}

.no-address-tip .tip-text {
  font-size: 15px;
  color: #333;
  font-weight: 500;
  margin-top: 12px;
}

.no-address-tip .tip-sub {
  font-size: 13px;
  color: #999;
  margin-top: 6px;
}

.pay-footer-section {
  padding: 20px 15px;
}

.warning-tips {
  background: #fffbe8;
  color: #ed6a0c;
  font-size: 12px;
  padding: 10px;
  border-radius: 8px;
  margin-bottom: 15px;
  display: flex;
  align-items: flex-start;
  gap: 6px;
  line-height: 1.4;
}

.confirm-check-row {
  display: flex;
  justify-content: center;
  margin-bottom: 15px;
}

.submit-pay-btn {
  height: 44px;
  background: #26A17B;
  color: #fff;
  font-size: 16px;
  font-weight: 600;
  border-radius: 22px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(38, 161, 123, 0.3);
}

.submit-pay-btn.disabled {
  background: #ccc;
  box-shadow: none;
  cursor: not-allowed;
}

.close-circle-outer {
  position: absolute;
  bottom: -60px;
  left: 50%;
  transform: translateX(-50%);
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: 1.5px solid rgba(255, 255, 255, 0.9);
  display: flex;
  align-items: center;
  justify-content: center;
  color: rgba(255, 255, 255, 0.9);
  cursor: pointer;
  z-index: 9999;
}
</style>
