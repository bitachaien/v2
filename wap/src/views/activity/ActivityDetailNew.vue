<template>
  <div class="activity-detail-page">
    <van-nav-bar
      :title="activityData.title || '活动详情'"
      left-arrow
      fixed
      placeholder
      @click-left="goBack"
      class="custom-nav"
    />
    
    <div v-if="loading" class="loading-container">
      <van-loading type="spinner" size="24" color="#26A17B">加载中...</van-loading>
    </div>
    
    <div v-else class="detail-content">
      <div class="rich-content" v-html="activityData.content"></div>
    </div>

    <div class="related-bar" v-if="relatedActivities.length > 0">
      <div class="bar-scroll">
        <div 
          class="bar-item" 
          v-for="item in relatedActivities" 
          :key="item.id"
          :class="{ active: isCurrentActivity(item.id) }"
          @click="goToActivity(item)"
        >
          <div class="img-wrapper">
            <img :src="item.banner || '/assets/img/ActiveImg10298044979829357.avif'" class="item-img" />
            <div class="active-badge" v-if="isCurrentActivity(item.id)">
              <van-icon name="success" size="10" color="#fff" />
            </div>
          </div>
          <div class="item-name" :class="{ 'name-active': isCurrentActivity(item.id) }">
            {{ item.title }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { showToast } from 'vant'
import { activityApi } from '@/api/activity'

const router = useRouter()
const route = useRoute()

const goBack = () => router.replace('/activity')

const loading = ref(true)
const activityData = ref({
  id: 0,
  title: '',
  banner: '',
  content: '',
  startDate: '',
  endDate: '',
  type: 'other',
  category: 'general'
})

const relatedActivities = ref([])

const isCurrentActivity = (id) => {
  return String(id) === String(activityData.value.id)
}

const decodeHtml = (html) => {
  const txt = document.createElement('textarea')
  txt.innerHTML = html
  return txt.value
}

const loadActivityDetail = async () => {
  const activityId = route.params.id
  if (!activityId) {
    showToast('活动参数错误')
    return
  }
  
  loading.value = true
  try {
    const res = await activityApi.getActivityDetail(activityId)
    if (res.code === 0 && res.data) {
      const data = res.data
      let content = data.content || '<p>暂无活动说明</p>'
      
      if (content.includes('&lt;') || content.includes('&gt;')) {
        content = decodeHtml(content)
      }
      
      activityData.value = {
        id: data.id,
        title: data.title || '活动详情',
        banner: data.banner || '',
        content: content,
        startDate: data.startDate || '',
        endDate: data.endDate || '',
        type: data.type_code || data.type || 'other',
        category: data.category || 'general'
      }
      
      await loadRelatedActivities()
    } else {
      showToast(res.message || '加载失败')
      activityData.value.content = '<p>加载失败，请稍后重试</p>'
    }
  } catch (error) {
    showToast('加载失败')
    activityData.value.content = '<p>加载失败，请稍后重试</p>'
  } finally {
    loading.value = false
  }
}

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
    relatedActivities.value = []
  }
}

const goToActivity = (item) => {
  const typeRouteMap = {
    'lucky_order': `/activity/lucky-order/${item.id}`,
    'loss_rescue': `/activity/loss-rescue/${item.id}`,
    'weekly_salary': `/activity/weekly-salary/${item.id}`,
    'pg_betting_king': `/activity/pg-betting-king/${item.id}`
  }
  
  const targetRoute = typeRouteMap[item.type] || `/activity/detail/${item.id}`
  
  if (String(item.id) === String(activityData.value.id)) {
    return 
  }
  
  router.replace(targetRoute).then(() => {
    if (targetRoute.includes('/activity/detail/')) {
      loadActivityDetail()
    }
  })
}

onMounted(() => {
  loadActivityDetail()
})
</script>

<style scoped>
.activity-detail-page {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  
  background: #c9cbd0; 
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
  padding-bottom: 110px; 
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}

.custom-nav :deep(.van-nav-bar__content) {
  background-color: #fff;
}

.custom-nav :deep(.van-nav-bar__title) {
  font-weight: 600;
  color: #333;
}

.loading-container {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 100px 0;
}

.detail-content {
  padding: 0 15px 15px 15px;
}

.rich-content {
  font-size: 14px;
  color: #333;
  line-height: 1.7;
  padding-top: 10px; 
  padding-bottom: 100px; 
}

.rich-content :deep(*) {
  font-size: inherit !important;
  min-height: auto;
}

.rich-content :deep(span),
.rich-content :deep(font),
.rich-content :deep(p),
.rich-content :deep(div) {
  font-size: 14px !important;
  line-height: 1.7 !important;
}

.rich-content > :deep(*) {
  margin-top: 0 !important;
  padding-top: 0 !important;
}

.rich-content > :deep(*:first-child) {
  margin-top: 0 !important;
  padding-top: 0 !important;
}

.rich-content :deep(p) {
  margin: 0 0 8px 0;
  line-height: 1.7;
  color: #333;
}

.rich-content :deep(img) {
  max-width: 100%;
  height: auto;
  display: block;
  margin: 12px auto;
  border-radius: 0;
}

.rich-content :deep(table) {
  width: 100%;
  border-collapse: collapse;
  margin: 12px 0;
  background-color: #fff;
  border: 1px solid #000; 
}

.rich-content :deep(table th),
.rich-content :deep(table td) {
  border: 1px solid #000; 
  padding: 8px 6px;
  text-align: center;
  font-size: 13px !important;
  color: #333;
}

.rich-content :deep(table th) {
  
  background: linear-gradient(180deg, #52a85f 0%, #409250 100%);
  color: #fff;
  font-weight: 500;
}

.rich-content :deep(table td) {
  background-color: #fff;
}

.rich-content :deep(.text-green),
.rich-content :deep(span[style*="color: rgb(0, 176, 80)"]),
.rich-content :deep(span[style*="color:#00b050"]),
.rich-content :deep(span[style*="color: #00b050"]),
.rich-content :deep(font[color="#00b050"]) {
  color: #00b050 !important;
  font-weight: bold;
}

.rich-content :deep(a) {
  color: #00b050;
  text-decoration: none;
}

.rich-content :deep(h1),
.rich-content :deep(h2),
.rich-content :deep(h3) {
  font-size: 16px !important;
  margin: 10px 0 8px 0;
  font-weight: bold;
  color: #333;
}

.rich-content :deep(h4),
.rich-content :deep(h5),
.rich-content :deep(h6) {
  font-size: 15px !important;
  margin: 8px 0 6px 0;
  font-weight: bold;
  color: #333;
}

.rich-content :deep(div) {
  margin-top: 0;
  margin-bottom: 0;
}

.rich-content :deep(p:empty),
.rich-content :deep(div:empty),
.rich-content :deep(br:first-child) {
  display: none;
}

.rich-content :deep(div[class*="person"]),
.rich-content :deep(div[class*="cusomize"]) {
  margin: 0;
  padding: 0;
}

.rich-content :deep(ol),
.rich-content :deep(ul) {
  padding-left: 0;
  margin: 10px 0;
  list-style-position: inside;
}

.rich-content :deep(li) {
  margin-bottom: 8px;
  line-height: 1.7;
  color: #333;
}

.rich-content :deep(strong),
.rich-content :deep(b) {
  font-weight: 600;
  color: #333;
}

.related-bar {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: #fff;
  padding: 12px 0 20px 0;
  box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.08);
  z-index: 100;
}

.bar-scroll {
  display: flex;
  overflow-x: auto;
  padding: 0 15px;
  gap: 15px;
  scrollbar-width: none;
  -webkit-overflow-scrolling: touch;
}

.bar-scroll::-webkit-scrollbar {
  display: none;
}

.bar-item {
  flex-shrink: 0;
  width: 150px;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.img-wrapper {
  position: relative;
  width: 150px;
  height: 90px;
  border-radius: 8px;
  overflow: hidden;
  
  border: 2px solid transparent; 
  box-sizing: border-box;
  transition: all 0.2s;
  background: #f5f5f5;
}

.item-img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  display: block;
}

.bar-item.active .img-wrapper {
  border-color: #26A17B;
}

.active-badge {
  position: absolute;
  right: 0;
  bottom: 0;
  width: 0;
  height: 0;
  border-style: solid;
  border-width: 0 0 24px 24px;
  border-color: transparent transparent #26A17B transparent;
  z-index: 2;
}

.active-badge .van-icon {
  position: absolute;
  right: -1px;
  bottom: -24px;
  transform: translate(0, -3px);
}

.item-name {
  font-size: 13px;
  color: #333;
  margin-top: 8px;
  text-align: center;
  width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  line-height: 1.4;
}

.item-name.name-active {
  color: #26A17B;
  font-weight: 600;
}
</style>
