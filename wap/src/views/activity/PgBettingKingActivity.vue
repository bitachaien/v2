<template>
  <div class="pg-betting-king-activity">
    <van-nav-bar
      :title="activityTitle"
      left-arrow
      fixed
      placeholder
      @click-left="goBack"
      class="custom-nav"
    >
      <template #right>
        <span class="nav-right-link" @click="goToRecords">领取记录</span>
      </template>
    </van-nav-bar>

    <div class="main-container">
      <div class="task-header-card">
        <div class="task-header-top">
          <div class="header-left">
            <div class="title">每日投注任务</div>
            <div class="subtitle">只能领取最高一档的奖励</div>
          </div>
          <div class="header-right">
            <div class="refresh-row" @click="loadData">
              <van-icon name="replay" class="refresh-icon" :class="{ 'rotating': isRefreshing }" />
              <span class="refresh-text">刷新奖励</span>
            </div>
            <div class="countdown-row">
              <span class="countdown-timer">{{ countdownText }}</span>
              <span class="countdown-suffix">后重置</span>
            </div>
          </div>
        </div>

        <div class="reward-list">
          <div 
            v-for="(item, index) in rewards" 
            :key="index"
            class="reward-item"
          >
            <div class="reward-icon-wrapper">
              <img src="/assets/img/dm_icon_dm.avif" class="reward-icon-img" alt="" />
            </div>
            
            <div class="reward-content">
              <div class="reward-condition">
                累计有效投注 ≥ {{ formatNumber(item.conditionMin) }}，奖励 <span class="amount">{{ item.rewardAmount }}</span>
              </div>
              <div class="progress-box">
                <div class="progress-bg">
                  <div class="progress-fill" :style="{ width: calculatePercentage(item.currentProgress, item.conditionMin) + '%' }"></div>
                  <div class="progress-text">{{ formatNumber(item.currentProgress || 0) }}/{{ formatNumber(item.conditionMin) }}</div>
                </div>
              </div>
            </div>

            <van-button 
              class="action-btn"
              :class="{ 'btn-claim': item.isMatched, 'btn-bet': !item.isMatched }"
              size="small"
              @click="item.isMatched ? handleClaim(item) : goToBet()"
            >
              {{ item.isMatched ? '领取' : '去投注' }}
            </van-button>
          </div>
        </div>
      </div>

      <div class="info-card">
        <div class="ribbon-title">
          <span>活动说明</span>
          <div class="ribbon-arrow"></div>
        </div>
        <div class="rules-content" v-html="activityContent">
        </div>
      </div>

      <div class="info-card" v-if="activityTerms">
        <div class="ribbon-title ribbon-orange">
          <span>条款及细则</span>
          <div class="ribbon-arrow"></div>
        </div>
        <div class="rules-content terms-content">{{ activityTerms }}</div>
      </div>

      <div class="related-section" v-if="relatedActivities.length > 0">
        <div 
          v-for="(act, index) in relatedActivities" 
          :key="index"
          class="related-card"
          :class="{ 'active': act.id === Number(route.params.id) }"
          @click="goToActivity(act)"
        >
          <img :src="act.banner || '/default-banner.png'" class="related-img" />
          <div class="related-title" :class="{ 'title-active': act.id === Number(route.params.id) }">{{ act.title }}</div>
        </div>
      </div>
    </div>

    <div class="bottom-bar">
      <div class="btn-back" @click="goBack">返回</div>
      <div 
        class="btn-claim-all" 
        :class="{ disabled: !hasClaimableRewards }"
        @click="handleClaimAll"
      >
        一键领取
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { showToast, showConfirmDialog } from 'vant'
import { activityApi } from '@/api/activity'

const router = useRouter()
const route = useRoute()

const activityId = computed(() => Number(route.params.id) || 1)
const activityTitle = ref('加载中...')
const activityContent = ref('<p>加载中...</p>')
const activityTerms = ref('')
const isRefreshing = ref(false)
const countdownText = ref('00:00:00')
const todayBetAmount = ref(0)
let countdownTimer = null

const rewards = ref([])

const relatedActivities = ref([])

const loadRelatedActivities = async () => {
  try {
    const res = await activityApi.getActivityList()
    if (res.code === 0 && res.data && res.data.list) {
      relatedActivities.value = res.data.list
        .sort((a, b) => (b.sort || 0) - (a.sort || 0))
        .map(item => ({
          id: item.id,
          title: item.title,
          banner: item.banner,
          type: item.type_code || item.type,
          category: item.category
        }))
    }
  } catch (error) {
  }
}

const hasClaimableRewards = computed(() => {
  return rewards.value.some(item => item.isMatched)
})

onMounted(() => {
  loadData()
  loadRelatedActivities()
})

onUnmounted(() => {
  if (countdownTimer) {
    clearInterval(countdownTimer)
  }
})

const goBack = () => router.replace('/activity')

const goToRecords = () => {
  router.push('/reward-record')
}

const goToBet = () => {
  router.push('/game')
}

const goToActivity = (activity) => {
  const typeRouteMap = {
    'lucky_order': `/activity/lucky-order/${activity.id}`,
    'loss_rescue': `/activity/loss-rescue/${activity.id}`,
    'weekly_salary': `/activity/weekly-salary/${activity.id}`,
    'welcome': `/activity/welcome/${activity.id}`,
    'pg_betting_king': `/activity/pg-betting-king/${activity.id}`
  }
  
  const targetRoute = typeRouteMap[activity.type] || `/activity/detail/${activity.id}`
  router.replace(targetRoute)
}

const loadData = async () => {
  if (isRefreshing.value) return
  isRefreshing.value = true
  try {
    try {
      const detailRes = await activityApi.getActivityDetail(activityId.value)
      if (detailRes.code === 0 && detailRes.data) {
        activityTitle.value = detailRes.data.title || '活动详情'
        activityContent.value = detailRes.data.content || '<p>暂无活动说明</p>'
        activityTerms.value = detailRes.data.terms || ''
      } else if (detailRes.code === 404) {
        showToast('活动不存在或已下架')
        setTimeout(() => router.push('/activity'), 1500)
        return
      }
    } catch (error) {
      if (error?.message?.includes('404')) {
        showToast('活动不存在或已下架')
        setTimeout(() => router.push('/activity'), 1500)
        return
      }
      activityTitle.value = '活动详情'
      activityContent.value = '<p>加载失败</p>'
    }
    
    try {
      const res = await activityApi.checkReward(activityId.value)
      if (res.code === 0 && res.data) {
        todayBetAmount.value = parseFloat(res.data.todayBetAmount) || 0
        
        if (res.data.resetCountdown) {
          startCountdown(res.data.resetCountdown)
        }
        
        if (res.data.allLevels && res.data.allLevels.length > 0) {
          rewards.value = res.data.allLevels.map(item => ({
            conditionMin: parseFloat(item.conditionMin) || 0,
            rewardAmount: item.rewardAmount || '0.00',
            currentProgress: todayBetAmount.value,
            isMatched: item.isMatched || false,
            rewardId: item.rewardId
          }))
        }
      }
    } catch (error) {
      rewards.value = []
    }
  } catch (error) {
  } finally {
    setTimeout(() => {
      isRefreshing.value = false
    }, 500)
  }
}

const formatNumber = (num) => {
  return Number(num).toLocaleString()
}

const startCountdown = (seconds) => {
  if (countdownTimer) {
    clearInterval(countdownTimer)
  }
  
  let remaining = seconds
  
  const updateCountdown = () => {
    if (remaining <= 0) {
      countdownText.value = '00:00:00'
      clearInterval(countdownTimer)
      return
    }
    
    const hours = Math.floor(remaining / 3600)
    const mins = Math.floor((remaining % 3600) / 60)
    const secs = remaining % 60
    
    countdownText.value = `${String(hours).padStart(2, '0')}:${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`
    remaining--
  }
  
  updateCountdown()
  countdownTimer = setInterval(updateCountdown, 1000)
}

const calculatePercentage = (current, total) => {
  if (!total) return 0
  const percent = (current / total) * 100
  return Math.min(100, Math.max(0, percent))
}

const handleClaim = async (item) => {
    handleClaimAll()
}

const handleClaimAll = async () => {
  const claimableRewards = rewards.value.filter(item => item.isMatched)
  
  if (claimableRewards.length === 0) {
    showToast('暂无可领取的奖励')
    return
  }
  
  showConfirmDialog({
    title: '一键领取',
    message: `确定领取${claimableRewards.length}个奖励吗？`,
  }).then(async () => {
    let successCount = 0
    for (const reward of claimableRewards) {
      try {
        const data = {
          activity_id: activityId.value,
          reward_id: reward.rewardId,
          condition_value: ''
        }
        
        const res = await activityApi.claimReward(data)
        if (res.code === 0) {
          successCount++
        }
      } catch (error) {
      }
    }
    
    if (successCount > 0) {
      showToast(`成功领取${successCount}个奖励`)
      setTimeout(() => loadData(), 1000)
    } else {
      showToast('领取失败')
    }
  }).catch(() => {})
}
</script>

<style scoped>
.pg-betting-king-activity {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
  background: #f7f8fa;
  padding-bottom: 80px;
  font-family: -apple-system, BlinkMacSystemFont, 'Helvetica Neue', Helvetica, Segoe UI, Arial, Roboto, 'PingFang SC', 'miui', 'Hiragino Sans GB', 'Microsoft Yahei', sans-serif;
}

:deep(.van-nav-bar__title) {
  font-weight: 500;
  color: #333;
}

.nav-right-link {
  color: #26A17B;
  font-size: 14px;
}

.main-container {
  padding: 10px 15px;
}

.task-header-card {
  background: #fff;
  border-radius: 8px;
  padding: 15px;
  margin-bottom: 15px;
}

.task-header-top {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 20px;
}

.header-left .title {
  font-size: 16px;
  font-weight: bold;
  color: #333;
  margin-bottom: 4px;
}

.header-left .subtitle {
  font-size: 12px;
  color: #999;
}

.header-right {
  text-align: right;
}

.refresh-row {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  color: #26A17B;
  font-size: 14px;
  margin-bottom: 4px;
  cursor: pointer;
}

.refresh-icon {
  margin-right: 4px;
}

.rotating {
  animation: rotate 1s linear infinite;
}

@keyframes rotate {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.countdown-row {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  color: #ff4d4f;
  font-size: 12px;
}

.countdown-timer {
  color: #ff4d4f;
  font-size: 12px;
  margin-right: 2px;
}

.reward-list {
  display: flex;
  flex-direction: column;
}

.reward-item {
  display: flex;
  align-items: center;
  padding: 15px 0;
  border-bottom: 1px solid #f5f5f5;
}

.reward-item:last-child {
  border-bottom: none;
}

.reward-icon-wrapper {
  margin-right: 12px;
  flex-shrink: 0;
}

.reward-icon-img {
  width: 48px;
  height: 48px;
  object-fit: contain;
}

.reward-content {
  flex: 1;
  margin-right: 10px;
}

.reward-condition {
  font-size: 13px;
  color: #333;
  margin-bottom: 8px;
  line-height: 1.4;
}

.amount {
  color: #f59e0b;
  font-weight: bold;
}

.progress-box {
  width: 100%;
}

.progress-bg {
  height: 16px;
  background: #e5e7eb;
  border-radius: 8px;
  position: relative;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: #26A17B;
  border-radius: 8px;
  transition: width 0.3s ease;
}

.progress-text {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  color: #fff;
  text-shadow: 0 0 2px rgba(0,0,0,0.3);
  z-index: 1;
}

.action-btn {
  width: 76px;
  height: 32px;
  border-radius: 4px;
  font-size: 13px;
  padding: 0;
  border: none;
}

.btn-bet {
  background: #26A17B;
  color: #fff;
}

.btn-claim {
  background: #f59e0b;
  color: #fff;
}

.info-card {
  background: #fff;
  border-radius: 8px;
  padding: 15px;
  margin-bottom: 15px;
  overflow: visible;
}

.ribbon-title {
  display: inline-flex;
  align-items: center;
  background: #26A17B;
  color: #fff;
  padding: 4px 12px;
  font-size: 14px;
  border-top-right-radius: 100px;
  border-bottom-right-radius: 100px;
  margin-bottom: 15px;
  position: relative;
  left: -15px;
  box-shadow: 2px 2px 5px rgba(38, 161, 123, 0.2);
}

.rules-content {
  padding-left: 5px;
}

.rules-content p {
  font-size: 13px;
  color: #666;
  line-height: 1.6;
  margin-bottom: 8px;
}

.ribbon-orange {
  background: #f97316;
}

.terms-content {
  font-size: 13px;
  color: #666;
  line-height: 1.8;
  white-space: pre-wrap;
}

.related-section {
  display: flex;
  gap: 15px;
  overflow-x: auto;
  padding-bottom: 10px;
  scrollbar-width: none;
  -webkit-overflow-scrolling: touch;
}

.related-section::-webkit-scrollbar {
  display: none;
}

.related-card {
  flex-shrink: 0;
  width: 150px;
  position: relative;
  cursor: pointer;
}

.related-card:active .related-img {
  transform: scale(0.98);
}

.related-card.active .related-img {
  border-color: #26A17B;
}

.related-img {
  width: 100%;
  height: 90px;
  object-fit: contain;
  border-radius: 8px;
  display: block;
  background: #f5f5f5;
  border: 2px solid transparent;
  box-sizing: border-box;
  transition: all 0.2s;
}

.related-title {
  font-size: 13px;
  color: #333;
  margin-top: 8px;
  text-align: center;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  line-height: 1.4;
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
  display: flex;
  gap: 15px;
  box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05);
  z-index: 100;
}

.btn-back {
  flex: 1;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid #26A17B;
  color: #26A17B;
  border-radius: 4px;
  font-size: 16px;
  background: #fff;
  cursor: pointer;
}

.btn-claim-all {
  flex: 1;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #9ca3af;
  color: #fff;
  border-radius: 4px;
  font-size: 16px;
  cursor: pointer;
}

.btn-claim-all:not(.disabled) {
  background: #26A17B;
}
</style>
