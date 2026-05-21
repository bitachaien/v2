<template>
  <div class="tab-record">
    <div class="filter-bar">
      <div class="filter-btn" @click="showTimeDropdown = !showTimeDropdown">
        <span>{{ currentTimeText }}</span>
        <van-icon name="arrow-down" size="12" :class="{ rotated: showTimeDropdown }" />
      </div>
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
    
    <van-pull-refresh v-model="refreshing" @refresh="onRefresh">
      <van-loading v-if="loading" type="spinner" size="24" style="margin: 40px auto;">Đang tải...</van-loading>
      
      <div class="record-empty" v-else-if="recordList.length === 0">
        <van-icon name="orders-o" size="60" color="#ccc" />
        <p>Chưa có lịch sử nhận</p>
      </div>
      
      <div class="record-list" v-else>
        <div class="record-item" v-for="item in recordList" :key="item.id">
          <div class="item-left">
            <span class="item-title">{{ item.activityTitle || getRewardTypeName(item.rewardType) }}</span>
            <span class="item-time">{{ formatTime(item.applyTime) }}</span>
          </div>
          <div class="item-right">
            <span class="item-amount" :class="getStatusClass(item.status)">{{ item.status === 1 ? '+' : '' }}¥ {{ item.rewardAmount }}</span>
            <span class="item-status" :class="getStatusClass(item.status)">{{ item.statusText }}</span>
          </div>
        </div>
      </div>
    </van-pull-refresh>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { showToast } from 'vant'
import { activityApi } from '@/api/activity'

const loading = ref(false)
const refreshing = ref(false)
const recordList = ref([])
const showTimeDropdown = ref(false)
const timeFilter = ref(7)

const timeOptions = [
  { text: 'Hôm nay', value: 0 },
  { text: 'Hôm qua', value: 1 },
  { text: '7 ngày gần đây', value: 7 },
  { text: '30 ngày gần đây', value: 30 }
]

const currentTimeText = computed(() => {
  const item = timeOptions.find(o => o.value === timeFilter.value)
  return item ? item.text : '7 ngày gần đây'
})

const selectTime = (val) => {
  timeFilter.value = val
  showTimeDropdown.value = false
}

const loadRecords = async () => {
  loading.value = true
  try {
    const params = { page: 1, pageSize: 50 }
    if (timeFilter.value === 0) params.date_range = 'today'
    else if (timeFilter.value === 1) params.date_range = 'yesterday'
    else if (timeFilter.value === 7) params.date_range = 'week'
    else if (timeFilter.value === 30) params.date_range = 'month'
    
    const res = await activityApi.getParticipationHistory(params)
    if (res.code === 0 && res.data) {
      recordList.value = res.data.list || []
    }
  } catch (e) {
    showToast('Tải thất bại')
  } finally {
    loading.value = false
    refreshing.value = false
  }
}

const onRefresh = () => {
  loadRecords()
}

const formatTime = (timestamp) => {
  if (!timestamp) return ''
  const d = new Date(timestamp * 1000)
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')} ${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`
}

const getRewardTypeName = (type) => {
  const map = {
    'lucky_order': 'Đơn may mắn',
    'loss_rescue': 'Cứu trợ thua lỗ',
    'weekly_salary': 'Lương tuần',
    'monthly_salary': 'Lương tháng',
    'pg_betting_king': 'Vua cược',
    'deposit_bonus': 'Thưởng nạp tiền'
  }
  return map[type] || 'Thưởng hoạt động'
}

const getStatusClass = (status) => {
  return status === 1 ? 'success' : status === 2 ? 'rejected' : 'pending'
}

watch(timeFilter, () => {
  loadRecords()
})

onMounted(() => {
  loadRecords()
})
</script>

<style scoped>
.tab-record {
  flex: 1;
  overflow-y: auto;
  background: #f7f8fa;
  min-height: 300px;
  position: relative;
}
.filter-bar {
  padding: 10px 15px;
  background: #fff;
  border-bottom: 1px solid #f0f0f0;
}
.filter-btn {
  display: inline-flex;
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
.filter-btn .van-icon { transition: transform 0.2s; }
.filter-btn .van-icon.rotated { transform: rotate(180deg); }
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
.dropdown-item:last-child { border-bottom: none; }
.dropdown-item:active { background: #f5f5f5; }
.dropdown-item.active { color: #26A17B; }
.record-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 300px;
  color: #999;
}
.record-empty p { margin-top: 15px; font-size: 14px; }
.record-list { background: #fff; margin: 10px 15px; border-radius: 12px; overflow: hidden; }
.record-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px;
  border-bottom: 1px solid #f0f0f0;
}
.record-item:last-child { border-bottom: none; }
.item-left { display: flex; flex-direction: column; gap: 4px; }
.item-title { font-size: 14px; color: #333; }
.item-time { font-size: 12px; color: #999; }
.item-right { display: flex; flex-direction: column; align-items: flex-end; gap: 4px; }
.item-amount { font-size: 16px; font-weight: bold; }
.item-amount.success { color: #52c41a; }
.item-amount.rejected { color: #999; text-decoration: line-through; }
.item-amount.pending { color: #ff9800; }
.item-status { font-size: 12px; }
.item-status.success { color: #52c41a; }
.item-status.rejected { color: #f44336; }
.item-status.pending { color: #ff9800; }
</style>
