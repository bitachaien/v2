<template>
  
  <van-popup 
    v-model:show="showPopup" 
    position="bottom" 
    round 
    :style="{ height: '90%' }"
    class="deposit-popup" :class="theme ? `theme-${theme}` : ''"
    @close="handleClose"
  >
    <div class="view-main">
      
      <div class="popup-header">
        <div class="header-left" @click="handleClose">
          <van-icon name="arrow-left" size="20" />
        </div>
        <div class="header-title">存款</div>
        <div class="header-right">
          <img src="/assets/img/comm_icon_cz_kf.svg" class="header-icon kf-icon" @click="toService" />
          <div class="divider-line"></div>
          <div class="history-icon-wrapper" @click="showHistory = true">
            <img src="/assets/img/comm_icon_cz_jl.svg" class="header-icon kf-icon" />
            <div class="red-dot" v-if="hasPendingOrder"></div>
          </div>
        </div>
      </div>

      
      <div class="popup-body">
        
        <div class="section-row">
          <span class="section-label">支付方式</span>
          <div class="balance-info">
            <div class="usdt-icon-small">₮</div>
            <span class="balance-val" :class="{ updating: isRefreshing }">{{ balance }}</span>
            <img src="/assets/img/comm_icon_sx.svg" class="refresh-icon" :class="{ spinning: isRefreshing }" @click="refreshBalance" />
          </div>
        </div>

        
        <div class="pay-method-list">
          <div 
            v-for="method in payMethods" 
            :key="method.type"
            class="pay-method-item" 
            :class="{ active: selectedMethod === method.type }"
            @click="selectMethod(method)"
          >
            <div class="method-icon" :class="getMethodIconClass(method.type)">
              {{ getMethodIconText(method.type) }}
            </div>
            <span class="method-name">{{ method.title }}</span>
            <div class="hot-tag" v-if="method.type === 'USDT'">火热</div>
          </div>
        </div>

        
        <div class="amount-section">
          <div class="section-label">存款金额</div>
          <div class="amount-grid">
            <div 
              v-for="amt in amountOptions" 
              :key="amt"
              class="amount-item"
              :class="{ active: selectedAmount === amt }"
              @click="selectAmount(amt)"
            >
              {{ formatAmount(amt) }}
            </div>
          </div>

          <div class="custom-input-box">
            <span class="input-prefix">U</span>
            <input 
              type="number" 
              v-model="customAmount" 
              :placeholder="`最低${minAmount}~最高${formatAmount(maxAmount)}`"
              class="custom-input"
              @input="handleCustomInput"
            />
            <van-icon 
              v-if="customAmount" 
              name="clear" 
              class="clear-icon" 
              @click="clearAmount" 
            />
          </div>
        </div>

        <div class="tip-text" v-if="currentMethodConfig?.remark">
          {{ currentMethodConfig.remark }}
        </div>
        <div class="tip-text" v-else-if="selectedMethod === 'USDT'">
          温馨提示：只支持Trc 不支持Erc（赠送能量请联系客服）
        </div>

        <div class="submit-btn" :class="{ disabled: !canSubmit }" @click="handleSubmit">
          立即存款
        </div>
      </div>
    </div>
  </van-popup>

  <DepositHistory
    ref="historyRef"
    v-model:show="showHistory"
    :theme="theme"
    @view-detail="viewDetail"
  />

  <DepositDetail
    v-model:show="showDetail"
    :record="currentRecord"
    @continue-pay="continuePay"
  />

  <PayInfoPopup
    v-model:show="showPayInfo"
    :order="currentOrder"
    :usdt-config="usdtConfig"
    @confirmed="onPayConfirmed"
  />
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { showToast, showLoadingToast, closeToast } from 'vant'
import { rechargeApi } from '@/api/recharge'
import { authApi } from '@/api/auth'
import DepositHistory from './DepositHistory.vue'
import DepositDetail from './DepositDetail.vue'
import PayInfoPopup from './PayInfoPopup.vue'

const props = defineProps({
  show: { type: Boolean, default: false },
  theme: { type: String, default: '' }
})

const emit = defineEmits(['update:show'])
const router = useRouter()

const showPopup = computed({
  get: () => props.show,
  set: (val) => emit('update:show', val)
})

const showHistory = ref(false)
const showDetail = ref(false)
const showPayInfo = ref(false)
const historyRef = ref(null)

const selectedMethod = ref('USDT')
const selectedAmount = ref(null)
const customAmount = ref('')
const balance = ref('0.00')
const hasPendingOrder = ref(false)
const isRefreshing = ref(false)
const minAmount = ref(20)
const maxAmount = ref(1000000)
const amountOptions = ref([108, 518, 1008, 3168, 5068, 10018, 51688, 99999])

const payMethods = ref([])
const currentMethodConfig = ref(null)

const usdtConfig = ref({
  trc20Address: '',
  erc20Address: '',
  rate: 7.2
})

const currentOrder = ref(null)
const currentRecord = ref(null)

const finalAmount = computed(() => {
  if (customAmount.value) return Number(customAmount.value)
  return selectedAmount.value || 0
})

const canSubmit = computed(() => {
  return finalAmount.value >= minAmount.value && finalAmount.value <= maxAmount.value
})

const handleClose = () => { showPopup.value = false }
const toService = () => router.push('/service')

const formatAmount = (num) => {
  return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',')
}

const selectAmount = (amt) => {
  selectedAmount.value = amt
  customAmount.value = amt
}

const handleCustomInput = () => {
  if (customAmount.value) selectedAmount.value = null
}

const clearAmount = () => {
  customAmount.value = ''
  selectedAmount.value = null
}

const getMethodIconClass = (type) => {
  const map = { 'USDT': 'usdt', 'alipay': 'alipay', 'weixin': 'weixin', 'linepay': 'bank' }
  return map[type] || 'default'
}

const getMethodIconText = (type) => {
  const map = { 'USDT': '₮', 'alipay': '支', 'weixin': '微', 'linepay': '银' }
  return map[type] || '付'
}

const selectMethod = async (method) => {
  selectedMethod.value = method.type
  await loadMethodConfig(method.type)
}

const refreshBalance = async () => {
  if (isRefreshing.value) return
  isRefreshing.value = true
  try {
    const res = await authApi.getProfile()
    if (res.code === 0 && res.data?.user) {
      balance.value = parseFloat(res.data.user.balance || 0).toFixed(2)
      showToast('刷新成功')
    }
  } catch (e) {
    showToast('刷新失败')
  } finally {
    setTimeout(() => { isRefreshing.value = false }, 500)
  }
}

const loadBalance = async () => {
  try {
    const res = await authApi.getProfile()
    if (res.code === 0 && res.data?.user) {
      balance.value = parseFloat(res.data.user.balance || 0).toFixed(2)
    }
  } catch (e) {
  }
}

const loadMethods = async () => {
  try {
    const res = await rechargeApi.getMethods()
    if (res.code === 0 && res.data) {
      payMethods.value = res.data
      if (payMethods.value.length > 0 && !selectedMethod.value) {
        selectedMethod.value = payMethods.value[0].type
      }
      if (selectedMethod.value) {
        await loadMethodConfig(selectedMethod.value)
      }
    }
  } catch (e) {
  }
}

const loadMethodConfig = async (type) => {
  try {
    const res = await rechargeApi.getConfig(type)
    if (res.code === 0 && res.data) {
      currentMethodConfig.value = res.data
      if (res.data.minAmount) minAmount.value = res.data.minAmount
      if (res.data.maxAmount) maxAmount.value = res.data.maxAmount
      
      if (type === 'USDT') {
        if (res.data.trc20Address) usdtConfig.value.trc20Address = res.data.trc20Address
        if (res.data.erc20Address) usdtConfig.value.erc20Address = res.data.erc20Address
        if (res.data.rate) usdtConfig.value.rate = res.data.rate
      }
    }
  } catch (e) {
  }
}

const handleSubmit = async () => {
  if (!canSubmit.value) {
    showToast(`请输入${minAmount.value}~${formatAmount(maxAmount.value)}之间的金额`)
    return
  }

  try {
    showLoadingToast({ message: '提交中...', forbidClick: true, duration: 0 })
    
    const res = await rechargeApi.submit({
      paytype: selectedMethod.value,
      amount: finalAmount.value,
      chain: selectedMethod.value === 'USDT' ? 'TRC20' : ''
    })
    
    closeToast()
    
    if (res.code === 0 && res.data) {
      const data = res.data
      currentOrder.value = {
        trano: data.trano,
        amount: data.amount,
        paytype: data.paytype,
        paytypeName: currentMethodConfig.value?.title || '充值',
        fuyanma: data.fuyanma,
        address: data.address || usdtConfig.value.trc20Address,
        qrcode: data.qrcode,
        account: currentMethodConfig.value?.account,
        accountName: currentMethodConfig.value?.accountName,
        bankInfo: data.paytype === 'linepay' ? {
          bankName: data.bankName,
          bankCode: data.bankCode,
          accountName: data.accountName,
          bankBranch: data.bankBranch
        } : null
      }
      showPayInfo.value = true
      selectedAmount.value = null
      customAmount.value = ''
      hasPendingOrder.value = true
    } else {
      showToast(res.message || '提交失败')
    }
  } catch (e) {
    closeToast()
    showToast('网络错误，请重试')
  }
}

const viewDetail = (record) => {
  currentRecord.value = record
  showDetail.value = true
}

const continuePay = (record) => {
  showDetail.value = false
  currentOrder.value = {
    trano: record.orderNo,
    amount: record.amount,
    paytype: record.paytype,
    paytypeName: record.paytype,
    address: usdtConfig.value.trc20Address
  }
  if (record.paytype) loadMethodConfig(record.paytype)
  showPayInfo.value = true
}

const onPayConfirmed = () => {
  loadBalance()
  hasPendingOrder.value = false
  if (historyRef.value) {
    historyRef.value.loadRecords()
  }
}

watch(() => props.show, (val) => {
  if (val) {
    loadMethods()
    loadBalance()
  }
})

onMounted(() => {
  if (props.show) {
    loadMethods()
    loadBalance()
  }
})
</script>

<style scoped>
.deposit-popup {
  background: transparent;
}

.view-main {
  background: #fff;
  height: 100%;
  display: flex;
  flex-direction: column;
  border-radius: 12px 12px 0 0;
}

.popup-header {
  height: 50px;
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 15px;
  border-radius: 12px 12px 0 0;
}

.header-left,
.header-right {
  width: 60px;
  display: flex;
  align-items: center;
}

.header-right {
  justify-content: flex-end;
  gap: 8px;
}

.header-title {
  font-size: 17px;
  font-weight: 600;
  color: #333;
}

.header-icon {
  width: 22px;
  height: 22px;
}

.kf-icon {
  filter: invert(45%) sepia(69%) saturate(456%) hue-rotate(118deg) brightness(94%) contrast(87%);
}

.header-icon-van {
  color: #333;
}

.divider-line {
  width: 1px;
  height: 16px;
  background: #ddd;
}

.history-icon-wrapper {
  position: relative;
}

.red-dot {
  position: absolute;
  top: -2px;
  right: -2px;
  width: 8px;
  height: 8px;
  background: #ff4d4f;
  border-radius: 50%;
}

.popup-body {
  flex: 1;
  padding: 15px;
  overflow-y: auto;
}

.section-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
}

.section-label {
  font-size: 14px;
  color: #333;
  font-weight: 500;
}

.balance-info {
  display: flex;
  align-items: center;
  gap: 5px;
}

.usdt-icon-small {
  width: 20px;
  height: 20px;
  background: #26A17B;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 12px;
  font-weight: bold;
}

.balance-val {
  color: #26A17B;
  font-weight: 600;
  text-decoration: underline;
  transition: opacity 0.3s, transform 0.3s;
}

.balance-val.updating {
  opacity: 0.5;
  transform: scale(0.95);
}

.refresh-icon {
  width: 16px;
  height: 16px;
  cursor: pointer;
  filter: invert(52%) sepia(84%) saturate(367%) hue-rotate(118deg) brightness(92%) contrast(89%);
  transition: transform 0.3s;
}

.refresh-icon.spinning {
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.pay-method-list {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 8px;
  margin-bottom: 20px;
}

.pay-method-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 10px 4px;
  border: 1px solid #e8e8e8;
  border-radius: 8px;
  position: relative;
  cursor: pointer;
  min-height: 74px;
  transition: all 0.2s;
}

.pay-method-item.active {
  border-color: #26A17B;
  background: rgba(38, 161, 123, 0.05);
}

.method-icon {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-weight: bold;
}

.method-icon.usdt { background: #26A17B; }
.method-icon.alipay { background: #1677FF; }
.method-icon.weixin { background: #07C160; }
.method-icon.bank { background: #FF6B35; }
.method-icon.default { background: #999; }

.method-name {
  font-size: 12px;
  color: #333;
  font-weight: 500;
  text-align: center;
}

.hot-tag {
  position: absolute;
  top: -8px;
  right: -5px;
  background: linear-gradient(135deg, #ff6b6b, #ff8e53);
  color: #fff;
  font-size: 10px;
  padding: 2px 6px;
  border-radius: 8px;
}

.amount-section {
  margin-bottom: 15px;
}

.amount-section .section-label {
  margin-bottom: 12px;
  font-weight: 600;
}

.amount-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 10px;
  margin-bottom: 12px;
}

.amount-item {
  height: 40px;
  background: #f5f5f5;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  color: #333;
  cursor: pointer;
  transition: all 0.2s;
}

.amount-item.active {
  background: #fff;
  color: #26A17B;
  border: 2px solid #26A17B;
}

.custom-input-box {
  display: flex;
  align-items: center;
  background: #f5f5f5;
  border-radius: 6px;
  padding: 0 12px;
  height: 44px;
}

.input-prefix {
  font-size: 16px;
  color: #999;
  margin-right: 10px;
}

.custom-input {
  flex: 1;
  background: transparent;
  border: none;
  outline: none;
  font-size: 14px;
  color: #333;
}

.custom-input::placeholder {
  color: #bbb;
}

.clear-icon {
  color: #c8c9cc;
  font-size: 18px;
  cursor: pointer;
}

.tip-text {
  font-size: 12px;
  color: #999;
  margin-bottom: 20px;
}

.submit-btn {
  height: 48px;
  background: #26A17B;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
}

.submit-btn.disabled {
  background: #ccc;
  cursor: not-allowed;
}

.theme-lottery :deep(.balance-val) {
  color: #f5222d;
}

.theme-lottery :deep(.kf-icon) {
  filter: invert(42%) sepia(93%) saturate(1352%) hue-rotate(200deg) brightness(100%) contrast(95%) !important;
}

.theme-lottery :deep(.refresh-icon) {
  filter: invert(42%) sepia(93%) saturate(1352%) hue-rotate(200deg) brightness(100%) contrast(95%) !important;
}

.theme-lottery :deep(.amount-item.active) {
  color: #5691fe;
  border-color: #5691fe;
}

.theme-lottery :deep(.pay-method-item.active) {
  border-color: #5691fe;
}

.theme-lottery :deep(.pay-method-item.active)::after {
  border-color: #5691fe;
}

.theme-lottery :deep(.submit-btn) {
  background: linear-gradient(90deg, #5691fe, #4378e8);
}

.theme-lottery :deep(.hot-tag) {
  background: #5691fe;
}
</style>
