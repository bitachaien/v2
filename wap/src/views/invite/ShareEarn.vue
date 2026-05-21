<template>
  <div class="share-earn-page">
    <div class="nav-header">
      <div class="nav-back" @click="$router.back()">
        <van-icon name="arrow-left" />
      </div>
      <div class="nav-title">Chia sẻ kiếm tiền</div>
      <div class="nav-right" @click="showCalculator = true" v-if="activeTab === 'rebateRatio'">
        <span class="calc-btn">Máy tính hoa hồng</span>
      </div>
      <div class="nav-right" v-else></div>
    </div>

    <div class="tabs-wrapper">
      <div class="tabs-scroll" ref="tabsScrollRef">
        <div 
          v-for="tab in tabs" 
          :key="tab.key"
          class="tab-item"
          :class="{ active: activeTab === tab.key }"
          @click="switchTab(tab.key)"
          :ref="el => setTabRef(tab.key, el)"
        >
          {{ tab.label }}
        </div>
      </div>
    </div>

    <div v-show="activeTab === 'myData'" class="time-filter-fixed">
      <div 
        v-for="item in timeOptions" 
        :key="item.value"
        class="filter-btn"
        :class="{ active: currentTime === item.value }"
        @click="changeTime(item.value)"
      >
        {{ item.label }}
      </div>
    </div>

    <div class="tab-content">
      <transition :name="tabTransition">
        <div :key="activeTab" class="tab-pane">
          <HomeTab 
            v-if="activeTab === 'home'"
            :agentInfo="agentInfo"
            :inviteInfo="inviteInfo"
            :overview="overview"
            @copy="handleCopy"
            @claim="handleClaim"
          />
          <ShareTab 
            v-else-if="activeTab === 'share'"
            :inviteInfo="inviteInfo"
            @copy="handleCopy"
            @saveQrcode="handleSaveQrcode"
          />
          <MyDataTab 
            v-else-if="activeTab === 'myData'"
            ref="myDataTabRef" 
          />
          <MyPerformanceTab v-else-if="activeTab === 'myPerformance'" />
          <MyCommissionTab v-else-if="activeTab === 'myCommission'" @claim="handleClaim" />
          <SubordinateTab v-else-if="activeTab === 'subInfo'" type="info" />
          <SubordinateTab v-else-if="activeTab === 'subBets'" type="bets" />
          <SubordinateTab v-else-if="activeTab === 'subFinance'" type="finance" />
          <SubordinateTab v-else-if="activeTab === 'subClaims'" type="claims" />
          <CreateAccountTab v-else-if="activeTab === 'createAccount'" />
          <RebateRatioTab v-else-if="activeTab === 'rebateRatio'" />
        </div>
      </transition>
    </div>

    <van-popup
      v-model:show="showCalculator" 
      round 
      position="center"
      :style="{ width: '90%', maxWidth: '400px' }"
    >
      <div class="calculator-dialog">
        <div class="dialog-header">
          <span>Máy tính mô phỏng hoa hồng</span>
        </div>
        <van-icon name="cross" class="dialog-close" @click="showCalculator = false" />
        
        <div class="calc-form">
          <div class="form-item">
            <label>Số người hợp lệ</label>
            <van-field v-model="calcForm.effectiveCount" type="digit" placeholder="0" />
          </div>
          <div class="form-item">
            <label>Thắng/Thua trực thuộc</label>
            <van-field v-model="calcForm.directWinLoss" type="number" placeholder="0.00" />
          </div>
          <div class="form-item">
            <label>Nhận ưu đãi</label>
            <van-field v-model="calcForm.claimBonus" type="number" placeholder="0.00" />
          </div>
        </div>

        <button class="calc-submit-btn" @click="calculateCommission">Tính hoa hồng</button>

        <div class="calc-result">
          <div class="result-title">Kết quả tính toán</div>
          <div class="result-row">
            <span class="label">Tỷ lệ hoa hồng -</span>
            <span class="value">Hoa hồng dự kiến -</span>
          </div>
        </div>
      </div>
    </van-popup>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, nextTick, watch } from 'vue'
import { useRouter } from 'vue-router'
import { showToast, showSuccessToast } from 'vant'
import { agentApi } from '@/api/agent'
import { timeOptions } from './useInvite'

import HomeTab from './components/HomeTab.vue'
import ShareTab from './components/ShareTab.vue'
import MyDataTab from './components/MyDataTab.vue'
import MyPerformanceTab from './components/MyPerformanceTab.vue'
import MyCommissionTab from './components/MyCommissionTab.vue'
import SubordinateTab from './components/SubordinateTab.vue'
import CreateAccountTab from './components/CreateAccountTab.vue'
import RebateRatioTab from './components/RebateRatioTab.vue'

const router = useRouter()
const myDataTabRef = ref(null)

const currentTime = ref('today')

const changeTime = (value) => {
  currentTime.value = value
  if (myDataTabRef.value?.changeTime) {
    myDataTabRef.value.changeTime(value)
  }
}

const tabs = [
  { key: 'home', label: 'Trang chủ' },
  { key: 'share', label: 'Chia sẻ quảng bá' },
  { key: 'myData', label: 'Dữ liệu của tôi' },
  { key: 'myPerformance', label: 'Thành tích của tôi' },
  { key: 'myCommission', label: 'Hoa hồng của tôi' },
  { key: 'subInfo', label: 'Thông tin cấp dưới' },
  { key: 'subBets', label: 'Cược cấp dưới' },
  { key: 'subFinance', label: 'Tài chính cấp dưới' },
  { key: 'subClaims', label: 'Nhận cấp dưới' },
  { key: 'createAccount', label: 'Mở tài khoản trực thuộc' },
  { key: 'rebateRatio', label: 'Tỷ lệ hoa hồng' }
]

const activeTab = ref(sessionStorage.getItem('inviteActiveTab') || 'home')
const tabsScrollRef = ref(null)
const tabRefs = {}
const tabTransition = ref('tab-slide-left')
const prevTabIndex = ref(0)

const tabIndexMap = {
  'home': 0, 'share': 1, 'myData': 2, 'myPerformance': 3, 'myCommission': 4,
  'subInfo': 5, 'subBets': 6, 'subFinance': 7, 'subClaims': 8,
  'createAccount': 9, 'rebateRatio': 10
}

watch(activeTab, (val, oldVal) => {
  sessionStorage.setItem('inviteActiveTab', val)
  const newIndex = tabIndexMap[val] || 0
  const oldIndex = tabIndexMap[oldVal] || 0
  tabTransition.value = newIndex > oldIndex ? 'tab-slide-left' : 'tab-slide-right'
})

const agentInfo = ref({
  agentId: '',
  agentMode: 'Lợi nhuận ròng cấp 1',
  auditMultiple: 0,
  settlementDate: ''
})

const inviteInfo = ref({
  inviteCode: '',
  inviteLink: '',
  qrcodeUrl: ''
})

const overview = ref({
  totalEarned: 0,
  totalInvited: 0,
  nextSettlement: '',
  yesterdayPerformance: 0,
  totalCommission: 0,
  claimed: 0,
  pending: 0
})

const showCalculator = ref(false)
const calcForm = reactive({
  effectiveCount: '',
  directWinLoss: '',
  claimBonus: ''
})

const setTabRef = (key, el) => {
  if (el) tabRefs[key] = el
}

const switchTab = (key) => {
  activeTab.value = key
  nextTick(() => {
    scrollTabIntoView(key)
  })
}

const scrollTabIntoView = (key) => {
  const tabEl = tabRefs[key]
  const scrollEl = tabsScrollRef.value
  if (tabEl && scrollEl) {
    const tabRect = tabEl.getBoundingClientRect()
    const scrollRect = scrollEl.getBoundingClientRect()
    const scrollLeft = tabEl.offsetLeft - (scrollRect.width / 2) + (tabRect.width / 2)
    scrollEl.scrollTo({ left: scrollLeft, behavior: 'smooth' })
  }
}

const handleCopy = async (text) => {
  if (!text) {
    showToast('Chưa có nội dung')
    return
  }
  try {
    if (navigator.clipboard && window.isSecureContext) {
      await navigator.clipboard.writeText(text)
    } else {
      const textarea = document.createElement('textarea')
      textarea.value = text
      textarea.style.position = 'fixed'
      textarea.style.left = '-9999px'
      document.body.appendChild(textarea)
      textarea.select()
      document.execCommand('copy')
      document.body.removeChild(textarea)
    }
    showSuccessToast('Sao chép thành công')
  } catch (e) {
    showToast('Sao chép thất bại, vui lòng sao chép thủ công')
  }
}

const handleSaveQrcode = () => {
  showToast('Nhấn giữ mã QR để lưu ảnh')
}

const handleClaim = async () => {
  try {
    const res = await agentApi.claimCommission()
    if (res.code === 0) {
      showSuccessToast('Nhận thành công')
      fetchOverview()
    } else {
      showToast(res.msg || 'Nhận thất bại')
    }
  } catch {
    showToast('Nhận thất bại')
  }
}

const calculateCommission = async () => {
  try {
    const res = await agentApi.calculateCommission({
      effectiveCount: calcForm.effectiveCount || 0,
      directWinLoss: calcForm.directWinLoss || 0,
      claimBonus: calcForm.claimBonus || 0
    })
    if (res.code === 0) {
      showToast(`Hoa hồng dự kiến: ¥${res.data.commission}`)
    }
  } catch {
    showToast('Tính toán thất bại')
  }
}

const fetchAgentInfo = async () => {
  try {
    const res = await agentApi.getAgentInfo()
    if (res.code === 0 && res.data) {
      agentInfo.value = res.data
    }
  } catch (e) {
    showToast('Lấy thất bại')
  }
}

const fetchInviteInfo = async () => {
  try {
    const res = await agentApi.getInviteInfo()
    if (res.code === 0 && res.data) {
      inviteInfo.value = res.data
    }
  } catch (e) {
    showToast('Lấy thất bại')
  }
}

const fetchOverview = async () => {
  try {
    const res = await agentApi.getOverview()
    if (res.code === 0 && res.data) {
      overview.value = res.data
    }
  } catch (e) {
    showToast('Lấy thất bại')
  }
}

onMounted(() => {
  fetchAgentInfo()
  fetchInviteInfo()
  fetchOverview()
})
</script>

<style scoped>
.share-earn-page {
  min-height: 100vh;
  background: #F8F8F8;
  padding-bottom: calc(20px + env(safe-area-inset-bottom));
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
}

.nav-header {
  position: sticky;
  top: 0;
  z-index: 100;
  height: 50px;
  background: #fff;
  display: flex;
  align-items: center;
  padding: 0 15px;
  border-bottom: 1px solid #eee;
}

.nav-back {
  width: 40px;
  display: flex;
  align-items: center;
  font-size: 20px;
  color: #333;
}

.nav-title {
  flex: 1;
  text-align: center;
  font-size: 17px;
  font-weight: 600;
  color: #333;
}

.nav-right {
  width: 100px;
  text-align: right;
}

.calc-btn {
  font-size: 12px;
  color: #26A17B;
}

.tabs-wrapper {
  position: sticky;
  top: 50px;
  z-index: 99;
  background: #fff;
  border-bottom: 1px solid #eee;
}

.time-filter-fixed {
  position: sticky;
  top: 94px;
  z-index: 98;
  display: flex;
  gap: 8px;
  background: #fff;
  padding: 12px;
  overflow-x: auto;
  border-bottom: 1px solid #eee;
}

.time-filter-fixed .filter-btn {
  width: 58px;
  height: 29px;
  border-radius: 15px;
  font-size: 12px;
  color: #666;
  background: #f5f5f5;
  border: 1px solid transparent;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.time-filter-fixed .filter-btn.active {
  background: #26A17B;
  color: #fff;
  border-color: #26A17B;
}

.tabs-scroll {
  display: flex;
  overflow-x: auto;
  padding: 0 10px;
  -webkit-overflow-scrolling: touch;
}

.tabs-scroll::-webkit-scrollbar {
  display: none;
}

.tab-item {
  flex-shrink: 0;
  padding: 12px 15px;
  font-size: 14px;
  color: #666;
  position: relative;
  white-space: nowrap;
}

.tab-item.active {
  color: #26A17B;
  font-weight: 600;
}

.tab-item.active::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 20px;
  height: 3px;
  background: #26A17B;
  border-radius: 2px;
}

.tab-content {
  flex: 1;
  position: relative;
}

.tab-pane {
  padding: 12px;
}

.tab-slide-left-enter-active,
.tab-slide-left-leave-active {
  transition: transform 0.3s ease-out;
}

.tab-slide-left-enter-from {
  transform: translateX(100%);
}

.tab-slide-left-leave-to {
  transform: translateX(-100%);
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
}

.tab-slide-right-enter-active,
.tab-slide-right-leave-active {
  transition: transform 0.3s ease-out;
}

.tab-slide-right-enter-from {
  transform: translateX(-100%);
}

.tab-slide-right-leave-to {
  transform: translateX(100%);
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
}

.calculator-dialog {
  padding: 20px;
  position: relative;
}

.dialog-header {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-size: 16px;
  font-weight: 600;
  color: #333;
  margin-bottom: 20px;
}

.dialog-close {
  position: absolute;
  top: 15px;
  right: 15px;
  font-size: 20px;
  color: #999;
}

.calc-form {
  margin-bottom: 20px;
}

.form-item {
  margin-bottom: 15px;
}

.form-item label {
  display: block;
  font-size: 14px;
  color: #666;
  margin-bottom: 8px;
}

.form-item :deep(.van-field) {
  background: #f5f5f5;
  border-radius: 8px;
}

.form-item :deep(.van-field__control) {
  color: #26A17B;
}

.calc-submit-btn {
  width: 100%;
  height: 44px;
  background: #26A17B;
  border: none;
  border-radius: 22px;
  color: #fff;
  font-size: 16px;
  font-weight: 600;
  margin-bottom: 20px;
}

.calc-result {
  background: #f5f5f5;
  border-radius: 8px;
  padding: 15px;
}

.result-title {
  font-size: 14px;
  font-weight: 600;
  color: #333;
  margin-bottom: 10px;
}

.result-row {
  display: flex;
  justify-content: space-between;
  font-size: 13px;
}

.result-row .label {
  color: #999;
}

.result-row .value {
  color: #999;
}
</style>
