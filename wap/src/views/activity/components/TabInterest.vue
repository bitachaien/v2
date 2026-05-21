<template>
  <div class="tab-interest">
    <div class="top-section">
      <div class="info-header">
        <div class="deposit-info">
          <div class="label">Đã gửi <span class="val">{{ deposited }}</span></div>
        </div>
        <div class="action-group-top">
          <van-button size="small" class="btn-transfer-in" @click="showTransferIn = true">Chuyển vào</van-button>
          <van-button size="small" class="btn-transfer-out" @click="handleTransferOut">Chuyển ra</van-button>
        </div>
      </div>
      
      <div class="info-cycle">
        Chu kỳ thanh toán {{ interestConfig.settle_cycle }} (Tối đa {{ formatMaxInterest }})
      </div>

      <div class="info-reward">
        <div class="reward-text">
          Chờ nhận<span class="highlight">{{ pendingReward }}</span> <span class="gray-sub">(Đã nhận {{ receivedReward }})</span>
          <img src="/assets/img/comm_icon_sx.svg" class="refresh-icon" :class="{ spinning: isDataRefreshing }" @click="refreshData" />
        </div>
        <van-button size="small" class="btn-claim" :class="{ active: canClaim }" :disabled="!canClaim" @click="handleClaim">Nhận</van-button>
      </div>
    </div>

    <div class="custom-tabs">
      <div 
        class="tab-item" 
        :class="{ active: activeTab === 'rules' }"
        @click="activeTab = 'rules'"
      >
        Quy tắc lãi suất
        <div class="active-line" v-if="activeTab === 'rules'"></div>
      </div>
      <div 
        class="tab-item" 
        :class="{ active: activeTab === 'records' }"
        @click="activeTab = 'records'"
      >
        Chi tiết giao dịch
        <div class="active-line" v-if="activeTab === 'records'"></div>
      </div>
    </div>

    <div v-if="activeTab === 'rules'" class="tab-content rules-content">
      <div class="rule-item">
        <div class="rule-title">1. Giới thiệu lợi nhuận:</div>
        <div class="rule-desc">Số tiền gửi vào phải đủ ít nhất một chu kỳ hoàn chỉnh mới sinh lãi. Nếu rút ra giữa chừng thì chu kỳ đó không tính lợi nhuận. Ví dụ: Chu kỳ thanh toán hiện tại là {{ interestConfig.settle_cycle }}, số tiền chuyển vào sẽ sinh lãi chu kỳ đầu tiên vào chu kỳ tiếp theo;</div>
      </div>
      <div class="rule-item">
        <div class="rule-title">2. Chu kỳ thanh toán:</div>
        <div class="rule-desc">Chu kỳ thanh toán lãi suất hiện tại là {{ interestConfig.settle_cycle }};</div>
      </div>
      <div class="rule-item">
        <div class="rule-title">3. Lãi suất năm:</div>
        <div class="rule-desc">Lãi suất năm hiện tại là {{ interestConfig.annual_rate }};</div>
      </div>
      <div class="rule-item">
        <div class="rule-title">4. Công thức tính:</div>
        <div class="rule-desc">Lãi mỗi chu kỳ = Số tiền gửi × Lãi suất ngày × (Số giờ chu kỳ / 24);</div>
      </div>
      <div class="rule-item">
        <div class="rule-title">5. Ví dụ minh họa:</div>
        <div class="rule-desc">Ví dụ gửi 10,000, lãi suất ngày 0.03%, chu kỳ thanh toán {{ interestConfig.settle_cycle }}, thì lãi mỗi giờ ≈ {{ exampleInterest }};</div>
      </div>
      <div class="rule-item">
        <div class="rule-title">6. Ngưỡng chuyển vào:</div>
        <div class="rule-desc">Mỗi lần chuyển vào phải ≥ {{ interestConfig.min_amount }}, không giới hạn số tiền chuyển vào, càng nhiều lợi nhuận càng cao;</div>
      </div>
      <div class="rule-item">
        <div class="rule-title">7. Lãi tối đa:</div>
        <div class="rule-desc">Lãi tối đa hiện tại là {{ formatMaxInterest }}, nhớ thường xuyên nhận lợi nhuận để không bỏ lỡ thu nhập nhé;</div>
      </div>
      <div class="rule-item">
        <div class="rule-title">8. Thời gian nhận:</div>
        <div class="rule-desc">Hiện tại là {{ interestConfig.claim_time }}, tức lãi phát sinh trong ngày phải đợi đến 0h ngày hôm sau mới nhận được;</div>
      </div>
      <div class="rule-item">
        <div class="rule-title">9. Yêu cầu vòng cược:</div>
        <div class="rule-desc">Yêu cầu vòng cược hiện tại là {{ interestConfig.audit_multiple }}x, tức lãi phát sinh cần hoàn thành {{ interestConfig.audit_multiple }}x vòng cược mới rút được;</div>
      </div>
    </div>

    <div v-else class="tab-content records-content">
      <div class="filter-bar">
        <div class="filter-btn-group">
          <div class="filter-btn" @click="showTimeDropdown = !showTimeDropdown; showTypeDropdown = false">
            <span>{{ currentTimeText }}</span>
            <van-icon name="arrow-down" size="12" :class="{ rotated: showTimeDropdown }" />
          </div>
          <div class="filter-btn" @click="showTypeDropdown = !showTypeDropdown; showTimeDropdown = false">
            <span>{{ currentTypeText }}</span>
            <van-icon name="arrow-down" size="12" :class="{ rotated: showTypeDropdown }" />
          </div>
        </div>
        <div class="total-income">Tổng thu nhập <span class="gold">{{ totalIncome }}</span></div>
      </div>

      <div v-if="showTimeDropdown" class="dropdown-overlay" @click="showTimeDropdown = false"></div>
      <div v-if="showTimeDropdown" class="dropdown-list">
        <div 
          v-for="item in timeOptions" 
          :key="item.value" 
          class="dropdown-item"
          :class="{ active: timeFilter === item.value }"
          @click="selectTime(item.value)"
        >
          <span>{{ item.text }}</span>
          <van-icon v-if="timeFilter === item.value" name="success" color="#26A17B" />
        </div>
      </div>

      <div v-if="showTypeDropdown" class="dropdown-overlay" @click="showTypeDropdown = false"></div>
      <div v-if="showTypeDropdown" class="dropdown-list dropdown-type">
        <div 
          v-for="item in typeOptions" 
          :key="item.value" 
          class="dropdown-item"
          :class="{ active: typeFilter === item.value }"
          @click="selectType(item.value)"
        >
          <span>{{ item.text }}</span>
          <van-icon v-if="typeFilter === item.value" name="success" color="#26A17B" />
        </div>
      </div>

      <div class="list-header">
        <span class="col-time">Thời gian</span>
        <span class="col-type">Loại</span>
        <span class="col-amount">Số tiền</span>
      </div>

      <div class="list-body">
        <van-loading v-if="loading" class="loading-spinner" color="#009688" />
        <div v-else-if="records.length === 0" class="empty-state">
          <img src="/assets/img/img_none_sj.avif" class="empty-img" />
          <div class="empty-text">Chưa có giao dịch</div>
        </div>
        <div v-else class="record-list">
          <div v-for="item in records" :key="item.id" class="record-item">
            <span class="col-time">{{ item.create_time }}</span>
            <span class="col-type">{{ item.title || getTypeText(item.type) }}</span>
            <span class="col-amount" :class="{ 'positive': isPositiveType(item.type), 'negative': item.type === 'withdraw' }">
              {{ item.type === 'withdraw' ? '-' : '+' }}{{ item.amount }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <van-popup 
      v-model:show="showTransferIn" 
      round 
      position="center" 
      class="transfer-popup"
      :style="{ width: '90%', padding: '20px' }"
    >
      <div class="popup-title">Chuyển Vào</div>
      
      <div class="popup-info-row">
        <span class="balance-row">Số dư tài khoản&nbsp;&nbsp;<span class="gold balance-val" :class="{ 'balance-flash': isRefreshing }">{{ userBalance }}</span> <img src="/assets/img/comm_icon_sx.svg" class="refresh-svg" :class="{ spinning: isRefreshing }" @click="refreshBalance" /></span>
        <span>Chu kỳ {{ interestConfig.settle_cycle }}</span>
      </div>

      <div class="popup-label-row">
        <span>Số tiền chuyển vào</span>
        <span class="gray-text">Thời gian hiện tại {{ currentTime }}</span>
      </div>

      <div class="input-wrapper">
        <span class="prefix">U</span>
        <input type="number" v-model="transferAmount" :placeholder="`Tối thiểu U${interestConfig.min_amount}, chỉ số nguyên`" />
        <span class="suffix-btn" @click="transferAmount = Math.floor(userBalance)">Tất cả</span>
      </div>

      <div class="popup-tip">
        Thời gian sinh lãi lần đầu: {{ nextInterestTime }}
      </div>

      <van-button block :color="canTransfer ? '#009688' : '#999'" class="confirm-btn" :disabled="!canTransfer" @click="handleTransferIn">Xác nhận chuyển vào</van-button>
      
      <div class="close-btn-wrapper" @click="showTransferIn = false">
        <van-icon name="cross" class="close-icon" />
      </div>
    </van-popup>

    <van-popup 
      v-model:show="showTransferOut" 
      round 
      position="center" 
      class="transfer-popup"
      :style="{ width: '90%', padding: '20px' }"
    >
      <div class="popup-title">Chuyển Ra</div>
      
      <div class="popup-info-row">
        <span>Có thể chuyển <span class="gold">{{ deposited }}</span></span>
        <span>Nhận ngay</span>
      </div>

      <div class="popup-label-row">
        <span>Số tiền chuyển ra</span>
      </div>

      <div class="input-wrapper">
        <span class="prefix">U</span>
        <input type="number" v-model="transferOutAmount" placeholder="Nhập số tiền chuyển ra" />
        <span class="suffix-btn" @click="transferOutAmount = deposited">Tất cả</span>
      </div>

      <div class="popup-label-row">
        <span>Mật khẩu rút tiền</span>
      </div>

      <div class="input-wrapper">
        <input type="password" v-model="fundPassword" placeholder="Nhập mật khẩu rút tiền" maxlength="20" />
      </div>

      <van-button block :color="canTransferOut ? '#009688' : '#999'" class="confirm-btn" :disabled="!canTransferOut" @click="confirmTransferOut">Xác nhận chuyển ra</van-button>
      
      <div class="close-btn-wrapper" @click="showTransferOut = false">
        <van-icon name="cross" class="close-icon" />
      </div>
    </van-popup>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { showToast, showLoadingToast, showSuccessToast } from 'vant'
import { yueBaoApi } from '@/api/yuebao'
import { authApi } from '@/api/auth'
import { lotteryWS, wsEvents } from '@/utils/websocket'

const infoLoading = ref(true)
const deposited = ref('--')
const pendingReward = ref('--')
const receivedReward = ref('--')
const activeTab = ref('records')
const showTransferIn = ref(false)
const showTransferOut = ref(false)
const userBalance = ref('--')
const transferAmount = ref('')
const transferOutAmount = ref('')
const fundPassword = ref('')
const currentTime = ref('')
const nextInterestTime = ref('')
const loading = ref(false)
const totalIncome = ref('--')
const isRefreshing = ref(false)
const isDataRefreshing = ref(false)
const showTimeDropdown = ref(false)
const showTypeDropdown = ref(false)

const interestConfig = ref({
  settle_cycle: '1 giờ',
  settle_cycle_hours: 1,
  annual_rate: '4%',
  annual_rate_value: 0.04,
  min_amount: 20,
  max_interest: 'Không giới hạn',
  claim_time: 'Nhận ngày hôm sau',
  audit_multiple: 1
})

const timeFilter = ref(0)
const typeFilter = ref(0)
const timeOptions = [
  { text: 'Hôm nay', value: 0 },
  { text: 'Hôm qua', value: 1 },
  { text: '7 ngày gần đây', value: 7 }
]
const typeOptions = [
  { text: 'Tất cả', value: 0 },
  { text: 'Chuyển vào', value: 1 },
  { text: 'Chuyển ra', value: 2 },
  { text: 'Nhận lợi nhuận', value: 3 }
]

const records = ref([])

const currentTimeText = computed(() => {
  const item = timeOptions.find(o => o.value === timeFilter.value)
  return item ? item.text : 'Hôm nay'
})
const currentTypeText = computed(() => {
  const item = typeOptions.find(o => o.value === typeFilter.value)
  return item ? item.text : 'Tất cả'
})

const selectTime = (val) => {
  timeFilter.value = val
  showTimeDropdown.value = false
}

const selectType = (val) => {
  typeFilter.value = val
  showTypeDropdown.value = false
}

const canTransfer = computed(() => {
  const minAmount = interestConfig.value.min_amount || 20
  return transferAmount.value && Number(transferAmount.value) >= minAmount && Number(transferAmount.value) <= Number(userBalance.value)
})

const canTransferOut = computed(() => {
  return transferOutAmount.value && Number(transferOutAmount.value) > 0 && Number(transferOutAmount.value) <= Number(deposited.value) && fundPassword.value.length >= 4
})

const canClaim = computed(() => {
  return Number(pendingReward.value) > 0
})

const formatMaxInterest = computed(() => {
  const val = interestConfig.value.max_interest
  if (val === 0 || val === '0' || val === '不限制') return 'Không giới hạn'
  return val
})

const exampleInterest = computed(() => {
  const hours = interestConfig.value.settle_cycle_hours || 1
  const interest = 10000 * 0.0003 * (hours / 24)
  return interest.toFixed(4)
})

const isApiSuccess = (res) => res && (res.code === 0 || res.code === 200)

const isPositiveType = (type) => {
  return ['income', 'deposit', 'interest', 'claim', 'transfer_in'].includes(type)
}

const fetchConfig = async () => {
  try {
    const res = await yueBaoApi.getConfig()
    if (isApiSuccess(res) && res.data) {
      interestConfig.value = { ...interestConfig.value, ...res.data }
    }
  } catch (e) {
  }
}

const fetchData = async () => {
  try {
    const infoRes = await yueBaoApi.getDashboardInfo()
    if (isApiSuccess(infoRes)) {
      const d = infoRes.data
      deposited.value = d.current_amount || '0.00'
      receivedReward.value = d.total_interest || '0.00'
      pendingReward.value = d.pending_interest || '0.00'
      const received = Number(d.total_interest || 0)
      const pending = Number(d.pending_interest || 0)
      totalIncome.value = (received + pending).toFixed(2)
    }
    
    const profileRes = await authApi.getProfile()
    if (isApiSuccess(profileRes)) {
      const u = profileRes.data?.user || profileRes.data || {}
      userBalance.value = u.balance || '0.00'
    }
  } catch (e) {
    deposited.value = '0.00'
    pendingReward.value = '0.00'
    receivedReward.value = '0.00'
    totalIncome.value = '0.00'
    userBalance.value = '0.00'
  } finally {
    infoLoading.value = false
  }
}

const fetchRecords = async () => {
  loading.value = true
  try {
    const params = {
      page: 1,
      page_size: 50
    }
    
    if (timeFilter.value === 0) params.date_range = 'today'
    else if (timeFilter.value === 1) params.date_range = 'yesterday'
    else if (timeFilter.value === 7) params.date_range = 'week'
    
    if (typeFilter.value === 1) params.type = 'deposit'
    else if (typeFilter.value === 2) params.type = 'withdraw'
    else if (typeFilter.value === 3) params.type = 'claim'
    
    const res = await yueBaoApi.getRecords(params)
    if (isApiSuccess(res)) {
      records.value = res.data?.list || []
    }
  } catch (e) {
  } finally {
    loading.value = false
  }
}

const handleTransferIn = async () => {
  if (!canTransfer.value) return
  
  const toast = showLoadingToast({ message: 'Đang xử lý...', forbidClick: true, duration: 0 })
  try {
    const productsRes = await yueBaoApi.getProducts()
    let productId = 1
    if (isApiSuccess(productsRes) && productsRes.data?.length > 0) {
      const currentProduct = productsRes.data.find(p => p.type === 'current')
      if (currentProduct) productId = currentProduct.id
    }
    
    const res = await yueBaoApi.transferIn({
      product_id: productId,
      amount: Number(transferAmount.value)
    })
    
    toast.close()
    if (isApiSuccess(res)) {
      showSuccessToast('Chuyển vào thành công')
      showTransferIn.value = false
      transferAmount.value = ''
      fetchData()
      fetchRecords()
    } else {
      showToast(res?.msg || res?.message || 'Chuyển vào thất bại')
    }
  } catch (e) {
    toast.close()
    showToast('Lỗi mạng')
  }
}

const handleTransferOut = () => {
  if (Number(deposited.value) <= 0) {
    return showToast('Chưa có số tiền để chuyển ra')
  }
  showTransferOut.value = true
}

const confirmTransferOut = async () => {
  if (!canTransferOut.value) return
  
  const toast = showLoadingToast({ message: 'Đang xử lý...', forbidClick: true, duration: 0 })
  try {
    const res = await yueBaoApi.transferOut({
      amount: Number(transferOutAmount.value),
      password: fundPassword.value
    })
    
    toast.close()
    if (isApiSuccess(res)) {
      showSuccessToast('Chuyển ra thành công')
      showTransferOut.value = false
      transferOutAmount.value = ''
      fundPassword.value = ''
      fetchData()
      fetchRecords()
    } else {
      showToast(res?.msg || res?.message || 'Chuyển ra thất bại')
    }
  } catch (e) {
    toast.close()
    showToast('Lỗi mạng')
  }
}

const handleClaim = async () => {
  if (!canClaim.value) return
  
  const toast = showLoadingToast({ message: 'Đang xử lý...', forbidClick: true, duration: 0 })
  try {
    const res = await yueBaoApi.claimInterest()
    toast.close()
    if (isApiSuccess(res)) {
      showSuccessToast(`Nhận thành công, +${res.data?.amount || 0}`)
      fetchData()
      fetchRecords()
    } else {
      showToast(res?.msg || res?.message || 'Nhận thất bại')
    }
  } catch (e) {
    toast.close()
    showToast('Lỗi mạng')
  }
}

const getTypeText = (type) => {
  const map = {
    'deposit': 'Chuyển vào',
    'withdraw': 'Chuyển ra',
    'income': 'Thu nhập',
    'interest': 'Lãi suất',
    'claim': 'Nhận lợi nhuận',
    'transfer_in': 'Chuyển vào',
    'transfer_out': 'Chuyển ra'
  }
  return map[type] || type
}

const updateTime = () => {
  const now = new Date()
  currentTime.value = `${String(now.getMonth()+1).padStart(2,'0')}/${String(now.getDate()).padStart(2,'0')} ${String(now.getHours()).padStart(2,'0')}:${String(now.getMinutes()).padStart(2,'0')}:${String(now.getSeconds()).padStart(2,'0')}`
  
  const hours = interestConfig.value.settle_cycle_hours || 1
  const next = new Date(now.getTime() + hours * 60 * 60 * 1000)
  nextInterestTime.value = `${String(next.getMonth()+1).padStart(2,'0')}/${String(next.getDate()).padStart(2,'0')} ${String(next.getHours()).padStart(2,'0')}:${String(next.getMinutes()).padStart(2,'0')}:${String(next.getSeconds()).padStart(2,'0')}`
}

const refreshBalance = async () => {
  if (isRefreshing.value) return
  isRefreshing.value = true
  try {
    const profileRes = await authApi.getProfile()
    if (isApiSuccess(profileRes)) {
      const u = profileRes.data?.user || profileRes.data || {}
      userBalance.value = u.balance || '0.00'
      showToast('Đã làm mới')
    }
  } catch (e) {
  } finally {
    setTimeout(() => { isRefreshing.value = false }, 500)
  }
}

const refreshData = async () => {
  if (isDataRefreshing.value) return
  isDataRefreshing.value = true
  try {
    await fetchData()
    showToast('Đã làm mới')
  } finally {
    setTimeout(() => { isDataRefreshing.value = false }, 500)
  }
}

watch([timeFilter, typeFilter], () => {
  fetchRecords()
})

let unsubInterest = null
let unsubBalance = null
let timeInterval = null

onMounted(() => {
  fetchConfig()
  fetchData()
  fetchRecords()
  updateTime()
  timeInterval = setInterval(updateTime, 1000)
  
  lotteryWS.connect().catch(() => {})
  
  unsubInterest = wsEvents.onYuebaoInterest((data) => {
    if (data.pendingInterest) {
      pendingReward.value = data.pendingInterest
    }
    if (data.totalInterest) {
      receivedReward.value = data.totalInterest
    }
    const received = Number(data.totalInterest || receivedReward.value || 0)
    const pending = Number(data.pendingInterest || pendingReward.value || 0)
    totalIncome.value = (received + pending).toFixed(2)
    if (data.nextSettleTime) {
      nextInterestTime.value = data.nextSettleTime
    }
    if (data.addedInterest && Number(data.addedInterest) > 0) {
      showToast(`Lãi +${data.addedInterest}`)
    }
  })
  
  unsubBalance = wsEvents.onYuebaoBalance((data) => {
    if (data.currentAmount) {
      deposited.value = data.currentAmount
    }
    if (data.pendingInterest) {
      pendingReward.value = data.pendingInterest
    }
    if (data.totalInterest) {
      receivedReward.value = data.totalInterest
    }
  })
})

onUnmounted(() => {
  if (unsubInterest) unsubInterest()
  if (unsubBalance) unsubBalance()
  if (timeInterval) clearInterval(timeInterval)
})
</script>

<style scoped>
.tab-interest {
  flex: 1;
  background: #fff;
  display: flex;
  flex-direction: column;
  height: 100%;
  overflow: hidden;
}

.top-section {
  padding: 15px 15px 0 15px;
  background: #fff;
  
}
.info-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start; 
  margin-bottom: 5px;
}
.deposit-info {
  display: flex;
  align-items: baseline;
}
.deposit-info .label {
  font-size: 14px;
  color: #666;
  margin-right: 5px;
}
.deposit-info .val {
  font-size: 20px; 
  color: #333;
  font-weight: 500;
  font-family: -apple-system, BlinkMacSystemFont, Roboto, "Helvetica Neue", sans-serif;
}
.action-group-top {
  display: flex;
  gap: 8px;
}
.btn-transfer-in, .btn-transfer-out {
  border: none;
  border-radius: 15px; 
  height: 26px; 
  line-height: 26px;
  padding: 0 18px;
  font-size: 13px;
}
.btn-transfer-in {
  background: #FFAA09;
  color: #fff;
}
.btn-transfer-out {
  background: #999; 
  color: #fff;
}

.info-cycle {
  font-size: 12px;
  color: #999;
  margin-bottom: 12px;
}

.info-reward {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px; 
}
.reward-text {
  font-size: 13px;
  color: #666;
  display: flex;
  align-items: center;
}
.reward-text .highlight {
  color: #FFAA09;
  font-weight: bold;
  margin: 0 4px;
  font-size: 15px;
}
.reward-text .gray-sub {
  color: #999;
  font-size: 12px;
}
.refresh-icon {
  width: 14px;
  height: 14px;
  margin-left: 8px;
  cursor: pointer;
  filter: invert(48%) sepia(79%) saturate(2476%) hue-rotate(130deg) brightness(90%) contrast(85%);
  transition: transform 0.3s ease;
}
.refresh-icon.spinning {
  animation: spin 0.5s linear infinite;
}
.btn-claim {
  background: #999;
  color: #fff;
  border: none;
  border-radius: 15px; 
  height: 26px;
  padding: 0 18px;
  font-size: 13px;
}
.btn-claim.active {
  background: #26A17B;
}
.btn-claim:disabled {
  opacity: 1;
}

.custom-tabs {
  display: flex;
  border-bottom: 1px solid #f5f5f5;
  background: #fff;
  padding-bottom: 0;
}
.tab-item {
  margin-right: 30px; 
  text-align: center;
  padding: 10px 0;
  font-size: 15px;
  color: #333;
  position: relative;
  cursor: pointer;
  font-weight: 500;
}
.tab-item:first-child {
  margin-left: 15px; 
}
.tab-item.active {
  color: #009688;
  font-weight: bold;
}
.active-line {
  position: absolute;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 20px; 
  height: 3px;
  background: #009688;
  border-radius: 2px;
}

.records-content {
  position: relative;
}
.filter-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #fff;
  padding: 10px 15px;
}

.filter-btn-group {
  display: flex;
  gap: 10px;
}
.filter-btn {
  display: flex;
  align-items: center;
  gap: 4px;
  background: #fff;
  border: 1px solid #ddd;
  border-radius: 16px;
  padding: 6px 12px;
  font-size: 13px;
  color: #333;
  cursor: pointer;
}
.filter-btn .van-icon {
  transition: transform 0.2s;
}
.filter-btn .van-icon.rotated {
  transform: rotate(180deg);
}

.dropdown-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 99;
}
.dropdown-list {
  position: absolute;
  left: 15px;
  top: 45px;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.12);
  z-index: 100;
  min-width: 120px;
  overflow: hidden;
}
.dropdown-list.dropdown-type {
  left: 90px;
}
.dropdown-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 15px;
  font-size: 14px;
  color: #333;
  cursor: pointer;
  border-bottom: 1px solid #f5f5f5;
}
.dropdown-item:last-child {
  border-bottom: none;
}
.dropdown-item:active {
  background: #f5f5f5;
}
.dropdown-item.active {
  color: #26A17B;
}

.total-income {
  font-size: 12px;
  color: #999;
}
.total-income .gold {
  color: #FFAA09;
}

.list-header {
  display: flex;
  background: #fff;
  padding: 12px 15px;
  font-size: 13px;
  color: #333;
  border-top: 1px solid #f5f5f5;
  border-bottom: 1px solid #f5f5f5;
}

.col-time { width: 40%; text-align: left; }
.col-type { width: 30%; text-align: center; }
.col-amount { width: 30%; text-align: right; }

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding-top: 50px;
}
.empty-img {
  width: 100px; 
  height: auto;
  margin-bottom: 15px;
  opacity: 0.8;
}
.empty-text {
  font-size: 13px;
  color: #999;
}
.green-link {
  color: #009688;
  margin-left: 2px;
  text-decoration: underline;
  cursor: pointer;
}

.record-list {
  padding: 0 15px;
}
.record-item {
  display: flex;
  padding: 12px 0;
  font-size: 13px;
  border-bottom: 1px solid #f5f5f5;
}
.record-item .col-time {
  width: 40%;
  text-align: left;
  color: #666;
}
.record-item .col-type {
  width: 30%;
  text-align: center;
  color: #333;
}
.record-item .col-amount {
  width: 30%;
  text-align: right;
  font-weight: 500;
}
.record-item .col-amount.positive {
  color: #009688;
}
.record-item .col-amount.negative {
  color: #f44336;
}

.loading-spinner {
  display: flex;
  justify-content: center;
  padding: 30px 0;
}

.transfer-popup {
  padding-bottom: 30px !important;
}
.popup-title {
  text-align: center;
  font-size: 16px;
  font-weight: bold;
  margin-bottom: 20px;
}
.popup-info-row {
  display: flex;
  justify-content: space-between;
  font-size: 13px;
  color: #666;
  margin-bottom: 15px;
}
.balance-row {
  display: flex;
  align-items: center;
}
.balance-val {
  font-size: 18px;
  font-weight: 500;
  transition: opacity 0.2s ease;
}
.balance-flash {
  animation: flash 0.5s ease-in-out;
}
@keyframes flash {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.3; }
}
.refresh-svg {
  width: 16px;
  height: 16px;
  margin-left: 8px;
  cursor: pointer;
  filter: invert(48%) sepia(79%) saturate(2476%) hue-rotate(130deg) brightness(90%) contrast(85%);
  transition: transform 0.3s ease;
}
.refresh-svg.spinning {
  animation: spin 0.5s linear infinite;
}
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
.gold { color: #FFAA09; }

.popup-label-row {
  display: flex;
  justify-content: space-between;
  font-size: 13px;
  color: #333;
  margin-bottom: 10px;
}
.gray-text { color: #999; font-size: 12px; }

.input-wrapper {
  display: flex;
  align-items: center;
  border: 1px solid #eee;
  border-radius: 4px;
  padding: 0 10px;
  height: 44px;
  margin-bottom: 15px;
}
.prefix {
  font-size: 14px;
  color: #333;
  margin-right: 10px;
}
.input-wrapper input {
  flex: 1;
  border: none;
  outline: none;
  font-size: 14px;
}
.suffix-btn {
  color: #009688;
  font-size: 13px;
  cursor: pointer;
}

.popup-tip {
  font-size: 12px;
  color: #999;
  margin-bottom: 25px;
}

.confirm-btn {
  border-radius: 4px;
  font-size: 15px;
}

.close-btn-wrapper {
  margin-top: 20px;
  text-align: center;
}
.close-icon {
  font-size: 24px;
  color: #ccc;
  border: 1px solid #ccc;
  border-radius: 50%;
  padding: 4px;
}
</style>
