<template>
  <div class="tab-rebate">
    <div class="rebate-header">
      <span class="label">今日可领取</span>
      <span class="amount">{{ totalClaimable.toFixed(2) }}</span>
    </div>

    <div class="rebate-main">
      <div class="sidebar">
        <div class="sidebar-scroll">
          <div 
            v-for="(cat, index) in categories" 
            :key="index"
            class="sidebar-item"
            :class="{ active: activeCategoryIndex === index }"
            @click="selectCategory(index)"
          >
            <div class="sidebar-item-content">
              <img :src="cat.icon" :alt="cat.name" class="sidebar-icon-img" />
              <span class="sidebar-text">{{ cat.name }}</span>
            </div>
          </div>
          
          <div class="sidebar-footer-inline">
            <div class="sidebar-btn primary" @click="claimAll">
              <span class="btn-text">一键领取</span>
            </div>
            <div class="sidebar-btn" @click="goToRecord">
              <span class="btn-text">领取记录</span>
            </div>
            <div class="sidebar-btn outline" @click="refreshData">
              <van-icon name="replay" :class="{ spinning: isRefreshing }" />
              <span class="btn-text">刷新奖励</span>
            </div>
          </div>
        </div>
      </div>

      <div class="vendor-list">
        <div 
          v-for="(vendor, index) in currentVendors" 
          :key="index"
          class="vendor-item"
          @click="goToVendorDetail(vendor)"
        >
          <div class="vendor-left">
            <div class="vendor-top-row">
              <img :src="vendor.logo" :alt="vendor.name" class="vendor-logo" />
              <div class="bet-row">
                <span class="label">有效投注</span>
                <span class="value">{{ vendor.validBet.toFixed(2) }}</span>
              </div>
            </div>
            <div class="progress-bar">
              <div class="progress-fill" :style="{ width: vendor.progress + '%' }"></div>
              <span class="progress-text">再投注{{ vendor.nextTarget }}{{ vendor.nextRate }}</span>
            </div>
          </div>
          <div class="vendor-right">
            <div class="rate-row">
              <span class="label">返水比例</span>
              <span class="value">{{ vendor.rate }}</span>
            </div>
            <div class="claim-row">
              <span class="label">可领取</span>
              <span class="value highlight">{{ vendor.claimable.toFixed(2) }}</span>
            </div>
          </div>
          <van-icon name="arrow" class="arrow-icon" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { showToast, showLoadingToast, closeToast, showDialog } from 'vant'
import request from '@/api/request'

const router = useRouter()

const totalClaimable = ref(0)

const userLevel = ref('')

const activeCategoryIndex = ref(0)

const isRefreshing = ref(false)

const categoryIcons = {
  slot: '/assets/img/icon_dtfl_dz_0.svg',
  live: '/assets/img/icon_dtfl_zr_0.svg',
  fish: '/assets/img/icon_dtfl_by_0.svg',
  chess: '/assets/img/icon_dtfl_qp_0.svg',
  lottery: '/assets/img/icon_dtfl_cp_0.svg',
  blockchain: '/assets/img/icon_dtfl_qkl_0.svg'
}

const categories = ref([])

const vendorData = ref({})

const currentVendors = computed(() => {
  const code = categories.value[activeCategoryIndex.value]?.code || 'slot'
  return vendorData.value[code] || []
})

const selectCategory = (index) => {
  activeCategoryIndex.value = index
}

const fetchRebateData = async () => {
  try {
    const res = await request.get('/v1/activity/rebate-by-vendor')
    if (res.code === 0 && res.data) {
      totalClaimable.value = res.data.totalClaimable || 0
      userLevel.value = res.data.userLevel || ''
      
      const apiCategories = res.data.categories || []
      categories.value = apiCategories.map(cat => ({
        name: cat.name,
        code: cat.code,
        icon: categoryIcons[cat.code] || '/assets/img/icon_dtfl_dz_0.svg'
      }))
      
      const newVendorData = {}
      apiCategories.forEach(cat => {
        newVendorData[cat.code] = (cat.vendors || []).map(v => ({
          code: v.code,
          name: v.name,
          logo: v.logo || '/assets/img/default_vendor.avif',
          validBet: v.validBet || 0,
          rate: v.rate || '0%',
          claimable: v.claimable || 0,
          progress: v.progress || 0,
          nextTarget: v.nextTarget || '1万',
          nextRate: v.nextRate || '0.1%',
          claimed: v.claimed || false
        }))
      })
      vendorData.value = newVendorData
    }
  } catch (err) {
  }
}

const claimAll = async () => {
  if (totalClaimable.value <= 0) {
    showToast('暂无可领取的返水')
    return
  }
  showLoadingToast({ message: '领取中...', forbidClick: true })
  try {
    const res = await request.post('/v1/activity/claim-vendor-rebate', {
      claim_all: true
    })
    closeToast()
    
    if (res.code === 0) {
      const claimed = res.data?.totalClaimed || 0
      showDialog({
        title: '领取成功',
        message: `成功领取 ${claimed.toFixed(2)} 元返水！`
      })
      await fetchRebateData()
    } else {
      showToast(res.message || '领取失败')
    }
  } catch (e) {
    closeToast()
    showToast('网络错误，请重试')
  }
}

const claimVendorRebate = async (vendor) => {
  if (vendor.claimable <= 0 || vendor.claimed) {
    showToast('暂无可领取的返水')
    return
  }
  
  const currentCategory = categories.value[activeCategoryIndex.value]
  if (!currentCategory) return
  
  showLoadingToast({ message: '领取中...', forbidClick: true })
  try {
    const res = await request.post('/api/v1/activity/claim-vendor-rebate', {
      category_code: currentCategory.code,
      vendor_code: vendor.code
    })
    closeToast()
    
    if (res.code === 0) {
      const claimed = res.data?.totalClaimed || vendor.claimable
      showToast(`领取成功 ${claimed.toFixed(2)} 元`)
      await fetchRebateData()
    } else {
      showToast(res.message || '领取失败')
    }
  } catch (e) {
    closeToast()
    showToast('网络错误，请重试')
  }
}

const goToRecord = () => {
  router.push('/reward-record')
}

const refreshData = async () => {
  if (isRefreshing.value) return
  isRefreshing.value = true
  
  try {
    await fetchRebateData()
    showToast({ message: '刷新成功', position: 'middle' })
  } catch (e) {
    showToast('刷新失败')
  } finally {
    isRefreshing.value = false
  }
}

const goToVendorDetail = (vendor) => {
  const currentCategory = categories.value[activeCategoryIndex.value]
  router.push({
    path: '/account/fanshui-rate',
    query: {
      category: currentCategory?.code || 'slot',
      vendor: vendor.code
    }
  })
}

onMounted(() => {
  fetchRebateData()
})
</script>

<style scoped>
.tab-rebate {
  height: 100%;
  display: flex;
  flex-direction: column;
  background: #f7f8fa;
  overflow: hidden;
}

.rebate-header {
  padding: 12px 15px;
  background: #fff;
  display: flex;
  align-items: center;
  gap: 8px;
  border-bottom: 1px solid #f0f0f0;
}
.rebate-header .label {
  font-size: 14px;
  color: #333;
}
.rebate-header .amount {
  font-size: 16px;
  font-weight: 600;
  color: #f5a623;
}

.rebate-main {
  flex: 1;
  display: flex;
  overflow: hidden;
}

.sidebar {
  width: 90px;
  background: transparent;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.sidebar-scroll {
  flex: 1;
  overflow-y: auto;
  padding-top: 10px;
  width: 100%;
}

.sidebar-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 5px 0;
  cursor: pointer;
  width: 100%;
  position: relative;
  z-index: 1;
}

.sidebar-item.active {
  z-index: 10;
}

.sidebar-item.active .sidebar-item-content {
  background-image: url('/assets/img/btn_zc1_1.avif');
  color: #009688;
}

.sidebar-item.active .sidebar-text {
  color: #fff;
  font-weight: 600;
}

.sidebar-item.active .sidebar-icon-img {
  filter: brightness(0) invert(1);
}

.sidebar-item-content {
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: flex-end;
  padding: 0 8px 0 30px;
  position: relative;
  border-radius: 8px;
  width: 86px;
  height: 40px;
  box-sizing: border-box;
  transition: all 0.3s;
  background-image: url('/assets/img/btn_zc1_2.avif');
  background-size: 100% 100%;
  background-repeat: no-repeat;
  background-color: transparent;
  color: #666;
  overflow: visible;
  pointer-events: none;
}

.sidebar-icon-img {
  position: absolute;
  left: 2px;
  top: 50%;
  transform: translateY(-50%);
  width: 28px;
  height: 28px;
  object-fit: contain;
  z-index: 1;
  pointer-events: none;
}

.sidebar-text {
  font-size: 13.76px;
  font-weight: 500;
  color: #999;
  white-space: nowrap;
  position: relative;
  z-index: 2;
  pointer-events: none;
}

.sidebar-footer-inline {
  padding: 10px 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}

.sidebar-btn {
  font-size: 11px;
  border-radius: 8px;
  text-align: center;
  color: #009688;
  border: 1px solid #009688;
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  width: 76px;
  height: 30px;
  padding: 0;
  box-sizing: border-box;
  cursor: pointer;
  transition: all 0.2s;
}

.sidebar-btn.primary {
  background: linear-gradient(135deg, #4CAF50 0%, #43A047 100%);
  color: #fff;
  border: none;
  font-weight: 600;
}

.sidebar-btn.primary:active {
  transform: scale(0.96);
}

.sidebar-btn .btn-text {
  font-size: 12px;
  font-weight: 500;
}

.sidebar-btn .van-icon {
  font-size: 14px;
  transition: transform 0.3s;
}

.sidebar-btn .van-icon.spinning {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.vendor-list {
  flex: 1;
  overflow-y: auto;
  background: #f7f8fa;
  padding: 10px;
  -webkit-overflow-scrolling: touch;
}

.vendor-item {
  display: flex;
  align-items: center;
  background: #fff;
  padding: 12px 12px;
  margin-bottom: 10px;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.15s;
  box-shadow: 0 1px 2px rgba(0,0,0,0.02);
}

.vendor-item:active {
  background: #f9f9f9;
  transform: scale(0.995);
}

.vendor-left {
  display: flex;
  flex-direction: column;
  gap: 6px;
  flex: 1;
  min-width: 0;
}

.vendor-top-row {
  display: flex;
  align-items: center;
  gap: 8px;
}

.vendor-logo {
  width: 28.66px;
  height: 28.66px;
  object-fit: contain;
  flex-shrink: 0;
}

.bet-row {
  display: flex;
  align-items: center;
  gap: 6px;
}

.bet-row .label {
  font-size: 11px;
  color: #999;
}

.bet-row .value {
  font-size: 13px;
  color: #333;
  font-weight: 600;
  font-family: DINAlternate-Bold, -apple-system, Helvetica, Arial, sans-serif;
}

.progress-bar {
  width: 149.06px;
  height: 11.45px;
  background: #e8e8e8;
  border-radius: 5.7px;
  position: relative;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #FFD54F 0%, #FFC107 100%);
  border-radius: 5.7px;
  transition: width 0.3s ease;
}

.progress-text {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 9px;
  color: #666;
  white-space: nowrap;
  z-index: 1;
}

.vendor-right {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  margin-right: 8px;
  justify-content: center;
  gap: 4px;
}

.rate-row,
.claim-row {
  display: flex;
  align-items: center;
  gap: 4px;
}

.rate-row .label,
.claim-row .label {
  font-size: 11px;
  color: #999;
}

.rate-row .value {
  font-size: 12px;
  color: #333;
  font-weight: 500;
}

.claim-row {
  margin-top: 2px;
}

.claim-row .value {
  font-size: 14px;
  color: #333;
  font-weight: 600;
  font-family: DINAlternate-Bold, -apple-system, Helvetica, Arial, sans-serif;
}

.claim-row .value.highlight {
  color: #f57c00;
}

.arrow-icon {
  color: #ddd;
  font-size: 14px;
  flex-shrink: 0;
}

.sidebar-scroll::-webkit-scrollbar,
.vendor-list::-webkit-scrollbar {
  width: 0;
  display: none;
}
</style>
