<template>
  <div class="tab-activity">
    <div class="sidebar">
      <div class="sidebar-scroll">
        <div 
          v-for="(item, index) in categories" 
          :key="index"
          class="sidebar-item"
          :class="{ active: activeCategory === index }"
          @click="selectCategory(index)"
        >
          <div class="sidebar-item-content">
            <img 
              :src="item.iconImg" 
              class="sidebar-icon-img" 
            />
            <span class="sidebar-text">{{ item.text }}</span>
          </div>
        </div>
        
        <div class="sidebar-footer-inline">
          <div class="sidebar-btn" @click="goRecord">
            <span class="btn-text">Lịch sử nhận</span>
          </div>
          <div class="sidebar-btn outline" @click="refreshReward">
            <van-icon name="replay" :class="{ spinning: isRefreshing }" />
            <span class="btn-text">Làm mới</span>
          </div>
        </div>
      </div>
    </div>

    <div class="content-list" ref="contentListRef">
      <van-loading v-if="loading" type="spinner" size="24" style="margin: 40px auto;">Đang tải...</van-loading>
      
      <div 
        class="activity-card" 
        v-for="(act, index) in activities" 
        :key="index"
        @click="goDetail(act)"
      >
        <img :src="act.image" class="card-img" />
      </div>
      
      <div v-if="!loading && activities.length === 0" class="empty-text">Chưa có hoạt động</div>
    </div>

    <CheckinPopup v-model:show="showCheckinPopup" :activity-id="checkinActivityId" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onActivated, nextTick } from 'vue'
import { showToast } from 'vant'
import { useRouter } from 'vue-router'
import { activityApi } from '@/api/activity'
import CheckinPopup from '@/components/checkin/CheckinPopup.vue'

const router = useRouter()
const contentListRef = ref(null)

const showCheckinPopup = ref(false)
const checkinActivityId = ref(0)

const categories = ref([])
const activeCategory = ref(0)

const loadCategories = async () => {
  try {
    const res = await activityApi.getCategoryList()
    if (res.code === 0 && res.data && res.data.length > 0) {
      categories.value = res.data.map(item => ({
        text: item.name,
        iconImg: item.icon || '/assets/img/icon_dtfl_zh_0.svg',
        code: item.code
      }))
    } else {
      categories.value = [
        { text: 'Tổng hợp', iconImg: '/assets/img/icon_dtfl_zh_0.svg', code: 'general' },
        { text: 'Điện tử', iconImg: '/assets/img/icon_dtfl_dz_0.svg', code: 'electronic' },
        { text: 'Live Casino', iconImg: '/assets/img/icon_dtfl_zr_0.svg', code: 'live' },
        { text: 'Cờ bài', iconImg: '/assets/img/icon_dtfl_qp_0.svg', code: 'chess' },
        { text: 'Bắn cá', iconImg: '/assets/img/icon_dtfl_by_0.svg', code: 'fishing' },
        { text: 'Xổ số', iconImg: '/assets/img/icon_dtfl_cp_0.svg', code: 'lottery' },
        { text: 'Thể thao', iconImg: '/assets/img/icon_dtfl_ty_0.svg', code: 'sports' },
      ]
    }
  } catch (error) {
    categories.value = [
      { text: 'Tổng hợp', iconImg: '/assets/img/icon_dtfl_zh_0.svg', code: 'general' },
      { text: 'Điện tử', iconImg: '/assets/img/icon_dtfl_dz_0.svg', code: 'electronic' },
      { text: 'Live Casino', iconImg: '/assets/img/icon_dtfl_zr_0.svg', code: 'live' },
      { text: 'Cờ bài', iconImg: '/assets/img/icon_dtfl_qp_0.svg', code: 'chess' },
      { text: 'Bắn cá', iconImg: '/assets/img/icon_dtfl_by_0.svg', code: 'fishing' },
      { text: 'Xổ số', iconImg: '/assets/img/icon_dtfl_cp_0.svg', code: 'lottery' },
      { text: 'Thể thao', iconImg: '/assets/img/icon_dtfl_ty_0.svg', code: 'sports' },
    ]
  }
}

const selectCategory = (index) => {
  activeCategory.value = index
}

const loading = ref(false)
const allActivities = ref([])

const loadActivities = async () => {
  loading.value = true
  try {
    const res = await activityApi.getActivityList()
    if (res.code === 0 && res.data && res.data.list) {
      allActivities.value = res.data.list.map(item => ({
        id: item.id,
        title: item.title,
        tag: getActivityTag(item.type_code || item.type),
        tagClass: getActivityTagClass(item.type_code || item.type),
        image: item.banner || '/assets/img/default-activity.png',
        type: item.type_code || item.type,
        category: item.category || [],
        status: item.status,
        jumpType: item.jump_type || 0,
        jumpUrl: item.jump_url || ''
      }))
    }
  } catch (error) {
    allActivities.value = []
  } finally {
    loading.value = false
    restoreScrollPosition()
  }
}

const getActivityTag = (type) => {
  const tagMap = {
    'lucky_order': 'May mắn',
    'loss_rescue': 'Cứu trợ',
    'weekly_salary': 'Lương tuần',
    'monthly_salary': 'Lương tháng',
    'pg_betting_king': 'Vua cược',
    'deposit': 'Nạp tiền',
    'deposit_bonus': 'Nạp tiền',
    'other': 'Hot'
  }
  return tagMap[type] || 'Hoạt động'
}

const getActivityTagClass = (type) => {
  const rewardTypes = ['lucky_order', 'loss_rescue', 'weekly_salary', 'monthly_salary', 'pg_betting_king']
  return rewardTypes.includes(type) ? 'tag-pink' : 'tag-orange'
}

const activities = computed(() => {
  if (!categories.value || categories.value.length === 0) return allActivities.value
  
  const selectedCategory = categories.value[activeCategory.value]
  if (!selectedCategory) return allActivities.value
  
  if (selectedCategory.code === 'general' || activeCategory.value === 0) {
    return allActivities.value
  }
  
  return allActivities.value.filter(act => {
    if (Array.isArray(act.category)) {
      return act.category.includes(selectedCategory.code)
    }
    return act.category === selectedCategory.code
  })
})

const goRecord = () => {
  router.push('/reward-record')
}

const goDetail = (act) => {
  if (contentListRef.value) {
    sessionStorage.setItem('activity_scroll_top', contentListRef.value.scrollTop)
    sessionStorage.setItem('activity_category', activeCategory.value)
  }
  
  if (act.jumpType === 1) {
    checkinActivityId.value = act.id
    showCheckinPopup.value = true
    return
  }
  
  if (act.jumpType === 2 && act.jumpUrl) {
    if (act.jumpUrl.startsWith('http')) {
      window.location.href = act.jumpUrl
    } else {
      router.replace(act.jumpUrl)
    }
    return
  }
  
  const typeRouteMap = {
    'lucky_order': `/activity/lucky-order/${act.id}`,
    'loss_rescue': `/activity/loss-rescue/${act.id}`,
    'weekly_salary': `/activity/weekly-salary/${act.id}`,
    'pg_betting_king': `/activity/pg-betting-king/${act.id}`,
    'monthly_salary': `/activity/reward/${act.id}`,
    'deposit_bonus': `/activity/reward/${act.id}`
  }
  
  const targetRoute = typeRouteMap[act.type] || `/activity/detail/${act.id}`
  router.replace(targetRoute)
}

onMounted(() => {
  const savedCategory = sessionStorage.getItem('activity_category')
  if (savedCategory !== null) {
    activeCategory.value = parseInt(savedCategory, 10)
  }
  loadCategories()
  loadActivities()
})

const restoreScrollPosition = () => {
  nextTick(() => {
    const savedScroll = sessionStorage.getItem('activity_scroll_top')
    if (savedScroll && contentListRef.value) {
      setTimeout(() => {
        if (contentListRef.value) {
          contentListRef.value.scrollTop = parseInt(savedScroll, 10)
        }
      }, 100)
    }
  })
}

const isRefreshing = ref(false)
const refreshReward = async () => {
  if (isRefreshing.value) return
  isRefreshing.value = true
  
  try {
    await loadActivities()
    showToast({ message: 'Làm mới thành công', position: 'middle' })
  } catch (error) {
    showToast({ message: 'Làm mới thất bại', position: 'middle' })
  } finally {
    isRefreshing.value = false
  }
}
</script>

<style scoped>
.tab-activity {
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
.sidebar-item.active { z-index: 10; }
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
  padding: 5px 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
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
}
.sidebar-btn .btn-text { font-size: 12px; font-weight: 500; }
.sidebar-btn .van-icon { font-size: 14px; transition: transform 0.3s; }
.sidebar-btn .van-icon.spinning {
  animation: spin 1s linear infinite;
}
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.content-list {
  flex: 1;
  overflow-y: auto;
  padding: 15px;
  padding-bottom: 80px;
  background: #f7f8fa;
}
.activity-card {
  background: #fff;
  border-radius: 12px;
  margin-bottom: 15px;
  position: relative;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  width: 100%;
  aspect-ratio: 309.59 / 139.89;
  cursor: pointer;
  transition: transform 0.15s, box-shadow 0.15s;
}
.activity-card:active {
  transform: scale(0.98);
  box-shadow: 0 1px 4px rgba(0,0,0,0.1);
}
.card-img { width: 100%; height: 100%; display: block; object-fit: cover; }
.card-tag {
  position: absolute;
  top: 0;
  left: 0;
  padding: 4px 12px;
  border-bottom-right-radius: 12px;
  color: #fff;
  font-size: 12px;
  font-weight: bold;
  z-index: 2;
}
.tag-orange { background: linear-gradient(135deg, #ff9800, #f57c00); }
.tag-pink { background: linear-gradient(135deg, #e91e63, #c2185b); }

.empty-text {
  text-align: center;
  padding: 60px 20px;
  color: #999;
  font-size: 14px;
}
</style>
