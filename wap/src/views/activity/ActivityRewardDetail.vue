<template>
  <div class="activity-reward-detail">
    <van-nav-bar
      :title="activityTitle"
      left-arrow
      fixed
      placeholder
      @click-left="goBack"
    >
      <template #right>
        <span class="nav-right-link" @click="goToRecords">Lịch sử nhận</span>
      </template>
    </van-nav-bar>

    <div class="top-bar">
      <div class="task-info">
        <span class="task-title">Nhiệm vụ cược hàng tuần</span>
        <span class="task-tip">Mỗi mốc đều có thể nhận thưởng</span>
      </div>
      <div class="refresh-area">
        <van-button 
          type="success" 
          size="small" 
          round
          :loading="isRefreshing"
          @click="refreshReward"
        >
          <van-icon name="replay" v-if="!isRefreshing" />
          Làm mới
        </van-button>
      </div>
    </div>

    <div class="countdown-bar" v-if="countdown">
      <van-count-down :time="countdown" format="DD天HH:mm:ss" />
      <span class="countdown-text">sau sẽ reset</span>
    </div>
    
    <div class="content">
      <div class="activity-info">
        <h3 class="title">{{ activityTitle }}</h3>
        <p class="desc">{{ activityDesc }}</p>
        <p class="time">Thời gian: {{ activityTime }}</p>
      </div>

      <div v-if="rewardType === 'lucky_order'" class="reward-section">
        <div class="section-header">
          <span>Số lần trúng hôm nay: {{ todayOrders }} lần</span>
          <van-button type="primary" size="small" @click="refreshReward">Làm mới</van-button>
        </div>
        
        <div class="reward-levels">
          <div 
            v-for="(item, index) in rewardLevels" 
            :key="index"
            class="level-item"
          >
            <div class="level-info">
              <span class="level-name">{{ item.levelName }}</span>
              <span class="level-reward">{{ item.rewardAmount }}</span>
            </div>
            <van-button 
              v-if="item.canClaim"
              type="primary" 
              size="small"
              @click="handleClaim(item)"
            >
              Nhận
            </van-button>
            <span v-else class="level-status">{{ item.orderNo ? 'Đã nhận' : '-' }}</span>
          </div>
        </div>
      </div>

      <div v-if="rewardType === 'loss_rescue'" class="reward-section">
        <div class="stats-card">
          <div class="stat-item">
            <span class="label">Cược hôm nay</span>
            <span class="value">{{ todayBet }}</span>
          </div>
          <div class="stat-item">
            <span class="label">Trúng hôm nay</span>
            <span class="value">{{ todayWin }}</span>
          </div>
          <div class="stat-item loss">
            <span class="label">Lỗ hôm nay</span>
            <span class="value">{{ todayLoss }}</span>
          </div>
        </div>

        <div class="reward-levels">
          <div 
            v-for="(item, index) in rewardLevels" 
            :key="index"
            class="level-item"
            :class="{ 'is-matched': item.isMatched }"
          >
            <div class="level-info">
              <span class="level-name">{{ item.levelName }}</span>
              <span class="level-reward">{{ item.rewardAmount }}</span>
            </div>
            <van-button 
              v-if="item.isMatched && canClaim"
              type="primary" 
              size="small"
              @click="handleClaim(item)"
            >
              Nhận
            </van-button>
            <span v-else class="level-status">
              {{ item.isMatched ? 'Đã nhận' : '-' }}
            </span>
          </div>
        </div>
      </div>

      <div v-if="rewardType === 'weekly_salary'" class="reward-section weekly-section">
        <div class="reward-levels">
          <div 
            v-for="(item, index) in rewardLevels" 
            :key="index"
            class="level-item-weekly"
          >
            <div class="weekly-icon">
              <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Ccircle cx='50' cy='50' r='45' fill='%2307c160'/%3E%3Ctext x='50' y='65' font-size='40' fill='white' text-anchor='middle' font-weight='bold'%3E%E2%9C%93%3C/text%3E%3C/svg%3E" class="icon-check" />
            </div>
            <div class="weekly-content">
              <div class="weekly-header">
                <span class="weekly-text">{{ item.levelName.replace('累计投注≥', '') }}，奖励</span>
                <span class="weekly-amount">{{ item.rewardAmount }}</span>
              </div>
              <div class="weekly-progress-bar">
                <div class="progress-bg">
                  <div class="progress-fill" :style="{ width: item.progress + '%' }">
                    <span class="progress-label">{{ Math.floor(item.progress) }}/{{ item.conditionMin.replace(/,/g, '') }}</span>
                  </div>
                </div>
              </div>
            </div>
            <van-button 
              class="btn-go-bet"
              type="success" 
              size="small"
              round
              @click="goToBet"
            >
              Đi cược
            </van-button>
          </div>
        </div>
      </div>

      <div class="rules-section">
        <h4>Hướng dẫn hoạt động</h4>
        <div class="rules-content" v-html="activityRules"></div>
      </div>

      <div class="related-activities" v-if="relatedActivities.length > 0">
        <div 
          v-for="(act, index) in relatedActivities" 
          :key="index"
          class="related-item"
          :class="{ 'active': act.id === activityId }"
          @click="goToActivity(act)"
        >
          <img :src="act.banner || act.image" class="related-img" />
          <span class="related-title" :class="{ 'title-active': act.id === activityId }">{{ act.title }}</span>
        </div>
      </div>
    </div>

    <div class="bottom-bar">
      <van-button class="btn-back" plain @click="goBack">Quay lại</van-button>
      <van-button 
        class="btn-claim-all" 
        type="primary"
        :disabled="!hasClaimableRewards"
        @click="handleClaimAll"
      >
        Nhận tất cả
      </van-button>
    </div>

    <van-dialog
      v-model:show="showClaimDialog"
      title="Nhận thưởng"
      show-cancel-button
      :before-close="beforeClose"
      @confirm="confirmClaim"
    >
      <div class="claim-info">
        <p>Mốc: {{ currentReward?.levelName }}</p>
        <p>Thưởng: <strong style="color: #ee0a24;">{{ currentReward?.rewardAmount }}</strong></p>
        <van-field
          v-if="needOrderNo"
          v-model="claimForm.orderNo"
          label="Mã đơn"
          placeholder="Nhập mã đơn cược"
        />
      </div>
    </van-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { showToast, showConfirmDialog } from 'vant'
import { activityApi } from '@/api/activity'

const router = useRouter()
const route = useRoute()

const activityId = computed(() => Number(route.params.id))
const activityTitle = ref('Chi tiết hoạt động')
const activityDesc = ref('')
const activityTime = ref('')
const activityRules = ref('')
const activityCategory = ref([])
const rewardType = ref('')
const loading = ref(false)
const isRefreshing = ref(false)
const showClaimDialog = ref(false)
const currentReward = ref(null)
const needOrderNo = ref(false)
const countdown = ref(0)
const relatedActivities = ref([])

const todayOrders = ref(0)
const todayBet = ref('0.00')
const todayWin = ref('0.00')
const todayLoss = ref('0.00')
const weekBetAmount = ref('0.00')
const canClaim = ref(false)
const rewardLevels = ref([])

const claimForm = reactive({
  orderNo: ''
})

const hasClaimableRewards = computed(() => {
  return rewardLevels.value.some(item => item.canClaim || (item.isMatched && canClaim.value))
})

onMounted(() => {
  loadActivityDetail()
  loadRewardData()
  calculateCountdown()
})

const goBack = () => router.replace('/activity')

const goToRecords = () => {
  router.push('/reward-record')
}

const goToActivity = (activity) => {
  router.replace(`/activity/reward/${activity.id}`)
}

const goToBet = () => {
  router.push('/game')
}

const loadActivityDetail = async () => {
  try {
    const res = await activityApi.getActivityDetail(activityId.value)
    if (res.code === 0) {
      activityTitle.value = res.data.title
      activityDesc.value = res.data.desc
      activityTime.value = `${res.data.startDate} - ${res.data.endDate}`
      activityRules.value = res.data.content || res.data.rules
      activityCategory.value = res.data.category || []
      loadRelatedActivities()
    }
  } catch (error) {
  }
}

const loadRewardData = async () => {
  loading.value = true
  try {
    const res = await activityApi.checkReward(activityId.value)
    if (res.code === 0) {
      const data = res.data
      rewardType.value = data.rewardType
      
      if (data.rewardType === 'lucky_order') {
        todayOrders.value = data.todayOrders || 0
        rewardLevels.value = data.matchedRewards || []
      } else if (data.rewardType === 'loss_rescue') {
        todayBet.value = data.todayBet
        todayWin.value = data.todayWin
        todayLoss.value = data.todayLoss
        canClaim.value = data.canClaim
        rewardLevels.value = data.allLevels || []
      } else if (data.rewardType === 'weekly_salary') {
        weekBetAmount.value = data.weekBetAmount
        canClaim.value = data.canClaim
        rewardLevels.value = data.allLevels || []
        calculateCountdown()
      }
    }
  } catch (error) {
    showToast('Tải thất bại')
  } finally {
    loading.value = false
  }
}

const refreshReward = async () => {
  if (isRefreshing.value) return
  isRefreshing.value = true
  try {
    await loadRewardData()
    showToast('Làm mới thành công')
  } catch (error) {
    showToast('Làm mới thất bại')
  } finally {
    isRefreshing.value = false
  }
}

const loadRelatedActivities = async () => {
  try {
    const res = await activityApi.getActivityList()
    if (res.code === 0 && res.data && res.data.list) {
      const currentCategories = activityCategory.value
      
      let filteredList = res.data.list
      
      if (currentCategories.length > 0 && !currentCategories.includes('general')) {
        filteredList = res.data.list.filter(item => {
          const itemCategories = Array.isArray(item.category) ? item.category : []
          return itemCategories.some(cat => currentCategories.includes(cat))
        })
      }
      
      relatedActivities.value = filteredList
        .sort((a, b) => (b.sort || 0) - (a.sort || 0))
        .map(item => ({
          id: item.id,
          title: item.title,
          banner: item.banner,
          type: item.type_code || item.type,
          category: item.category || []
        }))
    }
  } catch (error) {
  }
}

const calculateCountdown = () => {
  if (rewardType.value === 'weekly_salary') {
    const now = new Date()
    const endOfWeek = new Date(now)
    endOfWeek.setDate(now.getDate() + (7 - now.getDay()))
    endOfWeek.setHours(23, 59, 59, 999)
    countdown.value = endOfWeek.getTime() - now.getTime()
  } else if (rewardType.value === 'daily') {
    const now = new Date()
    const endOfDay = new Date(now)
    endOfDay.setHours(23, 59, 59, 999)
    countdown.value = endOfDay.getTime() - now.getTime()
  }
}

const handleClaim = (reward) => {
  currentReward.value = reward
  needOrderNo.value = rewardType.value === 'lucky_order'
  
  if (needOrderNo.value) {
    claimForm.orderNo = reward.orderNo || ''
    showClaimDialog.value = true
  } else {
    confirmClaim()
  }
}

const handleClaimAll = async () => {
  const claimableRewards = rewardLevels.value.filter(item => 
    item.canClaim || (item.isMatched && canClaim.value)
  )
  
  if (claimableRewards.length === 0) {
    showToast('Chưa có thưởng để nhận')
    return
  }
  
  if (claimableRewards.length === 1) {
    handleClaim(claimableRewards[0])
    return
  }
  
  showConfirmDialog({
    title: 'Nhận tất cả',
    message: `Xác nhận nhận ${claimableRewards.length} phần thưởng?`,
  }).then(async () => {
    let successCount = 0
    for (const reward of claimableRewards) {
      try {
        const data = {
          activity_id: activityId.value,
          reward_id: reward.rewardId,
          condition_value: reward.orderNo || ''
        }
        
        const res = await activityApi.claimReward(data)
        if (res.code === 0) {
          successCount++
        }
      } catch (error) {
      }
    }
    
    if (successCount > 0) {
      showToast(`Đã nhận thành công ${successCount} phần thưởng`)
      setTimeout(() => {
        loadRewardData()
      }, 1000)
    } else {
      showToast('Nhận thất bại')
    }
  }).catch(() => {
  })
}

const confirmClaim = async () => {
  if (needOrderNo.value && !claimForm.orderNo) {
    showToast('Vui lòng nhập mã đơn cược')
    return false
  }
  
  try {
    const data = {
      activity_id: activityId.value,
      reward_id: currentReward.value.rewardId,
      condition_value: needOrderNo.value ? claimForm.orderNo : ''
    }
    
    const res = await activityApi.claimReward(data)
    
    if (res.code === 0) {
      showToast(res.message || 'Nhận thành công')
      showClaimDialog.value = false
      setTimeout(() => {
        loadRewardData()
      }, 1000)
    } else {
      showToast(res.message || 'Nhận thất bại')
    }
  } catch (error) {
    showToast('Nhận thất bại')
  }
}

const beforeClose = (action) => {
  if (action === 'confirm') {
    return confirmClaim()
  }
  return true
}
</script>

<style scoped>
.activity-reward-detail {
  min-height: 100vh;
  background: #f5f5f5;
  padding-bottom: 80px; 
}

.nav-right-link {
  color: #07c160;
  font-size: 14px;
  cursor: pointer;
}

.top-bar {
  background: #fff;
  padding: 12px 15px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #f0f0f0;
}

.task-info {
  display: flex;
  flex-direction: column;
}

.task-title {
  font-size: 16px;
  font-weight: bold;
  color: #333;
}

.task-tip {
  font-size: 12px;
  color: #999;
  margin-top: 2px;
}

.refresh-area {
  flex-shrink: 0;
}

.countdown-bar {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: #fff;
  padding: 8px 15px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  gap: 5px;
}

.countdown-text {
  margin-left: 5px;
}

.content {
  padding: 12px;
}

.activity-info {
  background: #fff;
  border-radius: 8px;
  padding: 15px;
  margin-bottom: 12px;
}

.activity-info .title {
  font-size: 18px;
  font-weight: bold;
  color: #333;
  margin: 0 0 10px 0;
}

.activity-info .desc {
  font-size: 14px;
  color: #666;
  margin: 0 0 8px 0;
}

.activity-info .time {
  font-size: 12px;
  color: #999;
  margin: 0;
}

.reward-section {
  background: #fff;
  border-radius: 8px;
  padding: 15px;
  margin-bottom: 12px;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
  font-size: 14px;
  color: #666;
}

.stats-card {
  display: flex;
  justify-content: space-around;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 15px;
}

.stat-item {
  text-align: center;
  color: #fff;
}

.stat-item .label {
  display: block;
  font-size: 12px;
  opacity: 0.9;
  margin-bottom: 5px;
}

.stat-item .value {
  display: block;
  font-size: 18px;
  font-weight: bold;
}

.stat-item.loss .value {
  color: #ff6b6b;
}

.week-info {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  border-radius: 8px;
  padding: 15px;
  color: #fff;
  margin-bottom: 15px;
}

.week-info .week-bet {
  font-size: 16px;
  margin: 0 0 8px 0;
}

.week-info .tip {
  font-size: 12px;
  opacity: 0.9;
  margin: 0;
}

.reward-levels {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.level-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #f7f8fa;
  border-radius: 8px;
  padding: 12px;
  border: 2px solid transparent;
  transition: all 0.3s;
}

.level-item.is-matched {
  border-color: #07c160;
  background: #f0f9ff;
}

.level-item-vertical {
  flex-direction: column;
  align-items: stretch;
}

.level-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.level-name {
  font-size: 14px;
  color: #333;
  font-weight: 500;
}

.level-reward {
  font-size: 16px;
  color: #ee0a24;
  font-weight: bold;
}

.level-status {
  font-size: 12px;
  color: #999;
}

.level-status.success {
  color: #07c160;
  font-weight: bold;
}

.level-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 10px;
}

.level-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 10px;
}

.progress-text {
  font-size: 12px;
  color: #666;
}

.rules-section {
  background: #fff;
  border-radius: 8px;
  padding: 15px;
}

.rules-section h4 {
  font-size: 16px;
  color: #333;
  margin: 0 0 10px 0;
}

.rules-content {
  font-size: 14px;
  color: #666;
  line-height: 1.6;
}

.claim-info {
  padding: 20px;
}

.claim-info p {
  margin: 0 0 10px 0;
  font-size: 14px;
  color: #333;
}

.related-activities {
  display: flex;
  flex-wrap: nowrap;
  overflow-x: auto;
  gap: 8px;
  padding: 8px 10px;
  background: #fff;
  border-radius: 6px;
  margin-top: 10px;
  scrollbar-width: none; 
  -ms-overflow-style: none; 
}

.related-activities::-webkit-scrollbar {
  display: none; 
}

.related-item {
  flex-shrink: 0;
  width: 120px;
  cursor: pointer;
  text-align: center;
}

.related-item:active .related-img {
  transform: scale(0.98);
}

.related-item.active .related-img {
  border-color: #26A17B;
}

.related-img {
  width: 120px;
  height: 54px;
  border-radius: 6px;
  object-fit: cover;
  display: block;
  background: #f5f5f5;
  border: 2px solid transparent;
  box-sizing: border-box;
  transition: all 0.2s;
}

.related-title {
  display: block;
  font-size: 12px;
  color: #333;
  margin-top: 4px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  line-height: 1.3;
}

.related-title.title-active {
  color: #26A17B;
  font-weight: 600;
}

.bottom-bar {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: #fff;
  padding: 10px 15px;
  box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.08);
  z-index: 100;
  display: flex;
  gap: 10px;
}

.btn-back {
  flex: 1;
  height: 44px;
  border: 1px solid #ddd;
  color: #333;
  font-size: 16px;
}

.btn-claim-all {
  flex: 2;
  height: 44px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  font-size: 16px;
  font-weight: bold;
}

.btn-claim-all:disabled {
  background: #ddd;
  opacity: 0.6;
}

.weekly-section {
  background: transparent;
  padding: 0;
}

.weekly-section .reward-levels {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.level-item-weekly {
  display: flex;
  align-items: center;
  background: #fff;
  border-radius: 12px;
  padding: 12px;
  gap: 12px;
}

.weekly-icon {
  width: 50px;
  height: 50px;
  flex-shrink: 0;
}

.icon-check {
  width: 100%;
  height: 100%;
}

.weekly-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.weekly-header {
  display: flex;
  align-items: baseline;
  gap: 5px;
}

.weekly-text {
  font-size: 14px;
  color: #333;
}

.weekly-amount {
  font-size: 18px;
  font-weight: bold;
  color: #ff9800;
}

.weekly-progress-bar {
  width: 100%;
}

.progress-bg {
  height: 24px;
  background: #f0f0f0;
  border-radius: 12px;
  overflow: hidden;
  position: relative;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #07c160 0%, #38d39f 100%);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 24px;
  transition: width 0.3s ease;
}

.progress-label {
  font-size: 11px;
  color: #fff;
  font-weight: bold;
  white-space: nowrap;
  padding: 0 8px;
}

.btn-go-bet {
  flex-shrink: 0;
  width: 70px;
  height: 36px;
  font-size: 13px;
}
</style>

