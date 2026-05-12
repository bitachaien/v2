<template>
  <div class="lucky-order-activity">
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
      <div class="status-card">
        <div class="status-row">
          <span class="status-text">今日暂无中奖注单</span>
          <div class="refresh-btn" @click="refreshData">
            <van-icon name="replay" class="refresh-icon" :class="{ 'rotating': isRefreshing }" />
            <span>刷新奖励</span>
          </div>
        </div>
        <div class="limit-row">
          今日领取上限3次 <span class="limit-green">(还剩{{ remainingClaims }}次)</span>
        </div>
        
        <div class="reward-table">
          <div class="table-header">
            <div class="th col-1">注单尾号</div>
            <div class="th col-2">奖金倍数</div>
            <div class="th col-3">奖金上限</div>
          </div>
          <div class="table-body">
            <div 
              v-for="(item, index) in rewards" 
              :key="index"
              class="table-row"
              :class="{ 'row-light': index % 2 !== 0 }"
            >
              <div class="td col-1">{{ item.tailNum }}</div>
              <div class="td col-2">{{ item.multiplier }}</div>
              <div class="td col-3">{{ item.limit }}</div>
            </div>
          </div>
        </div>
        <div class="table-footer-note">
          奖励金额=该笔注单的有效投注金额 x 奖金倍数，且不超过上限
        </div>
      </div>

      <div class="info-card">
        <div class="ribbon-title">
          <span>活动时间 (UTC+8)</span>
          <div class="ribbon-arrow"></div>
        </div>
        <div class="time-content">
          {{ activityTime }}
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

      <div class="related-section" v-if="relatedActivities.length > 0">
        <div 
          v-for="(act, index) in relatedActivities" 
          :key="index"
          class="related-card"
          :class="{ 'active': act.id === Number(route.params.id) }"
          @click="goToActivity(act)"
        >
          <img :src="act.banner" class="related-img" />
          <div class="related-title" :class="{ 'title-active': act.id === Number(route.params.id) }">{{ act.title }}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { showToast } from 'vant'
import { activityApi } from '@/api/activity'

const router = useRouter()
const route = useRoute()
const isRefreshing = ref(false)
const remainingClaims = ref(3)
const activityTitle = ref('加载中...')
const activityTime = ref('加载中...')
const activityContent = ref('<p>加载中...</p>')

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

onMounted(async () => {
  const activityId = route.params.id
  if (activityId) {
    try {
      const res = await activityApi.getActivityDetail(activityId)
      if (res.code === 0 && res.data) {
        const data = res.data
        activityTitle.value = data.title || '活动详情'
        activityContent.value = data.content || '<p>暂无活动说明</p>'
        
        if (data.startDate && data.endDate) {
          activityTime.value = `${data.startDate} 00:00:00 - ${data.endDate} 23:59:59`
        } else {
          activityTime.value = '长期有效'
        }
        
        if (data.rewards && data.rewards.length > 0) {
          rewards.value = data.rewards.map(reward => ({
            tailNum: reward.conditionValue || '',
            multiplier: reward.levelName || '',
            limit: parseFloat(reward.rewardAmount || 0).toFixed(2)
          }))
        }
      }
    } catch (error) {
      activityTitle.value = '活动详情'
      activityTime.value = '加载失败'
      activityContent.value = '<p>加载失败，请稍后重试</p>'
    }
  } else {
    activityTitle.value = '活动详情'
    activityTime.value = '参数错误'
    activityContent.value = '<p>活动参数错误</p>'
  }
  
  loadRelatedActivities()
})

const goBack = () => router.replace('/activity')

const goToRecords = () => {
  router.push('/reward-record')
}

const refreshData = () => {
  if (isRefreshing.value) return
  isRefreshing.value = true
  setTimeout(() => {
    isRefreshing.value = false
    showToast('刷新成功')
  }, 1000)
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
</script>

<style scoped>
.lucky-order-activity {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
  background: #f7f8fa;
  padding-bottom: 20px;
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

.status-card {
  background: #fff;
  border-radius: 8px;
  padding: 15px 0; 
  margin-bottom: 15px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.02);
}

.status-row {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 10px;
  margin-bottom: 12px;
  font-size: 14px;
  color: #999;
}

.refresh-btn {
  display: flex;
  align-items: center;
  color: #26A17B;
  cursor: pointer;
}

.refresh-icon {
  margin-right: 4px;
  font-size: 16px;
}

.rotating {
  animation: rotate 1s linear infinite;
}

@keyframes rotate {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.limit-row {
  text-align: center;
  font-size: 14px;
  color: #666;
  margin-bottom: 20px;
}

.limit-green {
  color: #26A17B;
}

.reward-table {
  width: 100%;
}

.table-header {
  display: flex;
  background: #f9fafb;
  padding: 12px 0;
  font-size: 13px;
  color: #333;
  font-weight: 500;
}

.table-row {
  display: flex;
  padding: 15px 0;
  font-size: 13px;
  color: #666;
  background: #fff;
}

.row-light {
  background: #fcfcfc; 
  background: #f9f9f9; 
}

.table-row:not(.row-light) {
  background: #fff;
}

.th, .td {
  flex: 1;
  text-align: center;
  display: flex;
  align-items: center;
  justify-content: center;
}

.col-1 { flex: 0.8; }
.col-2 { flex: 1.2; }
.col-3 { flex: 1; }

.td.col-3 {
  color: #f59e0b; 
  font-weight: 500;
}

.table-footer-note {
  text-align: center;
  font-size: 11px;
  color: #999;
  padding: 15px 15px 0;
  line-height: 1.4;
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

.time-content {
  font-size: 13px;
  color: #666;
  padding-left: 5px;
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

.related-card:active .related-img {
  transform: scale(0.98);
}

.related-card.active .related-img {
  border-color: #26A17B;
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
</style>
