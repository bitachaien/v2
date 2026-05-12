<template>
  <van-popup 
    v-model:show="visible" 
    position="center" 
    round 
    :style="{ width: '92%', height: '60vh' }"
    class="history-popup" :class="theme ? `theme-${theme}` : ''"
    :overlay-style="{ background: 'rgba(0,0,0,0.5)' }"
  >
    <div class="view-history">
      <div class="history-header">
        <div class="history-title">存款记录</div>
      </div>

      
      <div class="filter-bar">
        <div class="filter-item" @click="toggleTimePicker" :class="{ active: showTimePicker }">
          <span>{{ getTimeText(timeFilter) }}</span>
          <van-icon name="arrow-down" size="12" :class="{ rotate: showTimePicker }" />
        </div>
        <div class="filter-item" @click="toggleStatusPicker" :class="{ active: showStatusPicker }">
          <span>{{ getStatusFilterText(statusFilter) }}</span>
          <van-icon name="arrow-down" size="12" :class="{ rotate: showStatusPicker }" />
        </div>
      </div>

      
      <div v-if="showTimePicker" class="dropdown-menu dropdown-time" @click.stop>
        <div 
          v-for="item in timeActions" 
          :key="item.value" 
          class="dropdown-item"
          :class="{ selected: timeFilter === item.value }"
          @click="onTimeSelect(item)"
        >
          {{ item.name }}
          <van-icon name="success" v-if="timeFilter === item.value" />
        </div>
      </div>

      <div v-if="showStatusPicker" class="dropdown-menu dropdown-status" @click.stop>
        <div 
          v-for="item in statusActions" 
          :key="item.value" 
          class="dropdown-item"
          :class="{ selected: statusFilter === item.value }"
          @click="onStatusSelect(item)"
        >
          {{ item.name }}
          <van-icon name="success" v-if="statusFilter === item.value" />
        </div>
      </div>

      
      <div v-if="showTimePicker || showStatusPicker" class="dropdown-overlay" @click="closeDropdowns"></div>

      
      <div class="record-list">
        <div 
          v-for="record in filteredRecords" 
          :key="record.id"
          class="record-item"
          @click="$emit('view-detail', record)"
        >
          <div class="record-left">
            <div class="record-icon-img">
              <div class="usdt-icon-css">T</div>
            </div>
            <div class="record-info">
              <div class="record-type">{{ getPayTypeName(record.paytype) }}</div>
              <div class="record-time">{{ record.createTime }}</div>
              <div class="record-order">
                <span class="order-no-text">{{ record.orderNo }}</span>
                <van-icon name="description-o" class="copy-mini" @click.stop="copyText(record.orderNo)" />
              </div>
            </div>
          </div>
          <div class="record-right">
            <div class="record-amount">{{ record.amount }}</div>
            <div class="record-status" :class="getStatusClass(record.status)">
              {{ getStatusText(record.status) }}
            </div>
          </div>
          <van-icon name="arrow" class="record-arrow" />
        </div>

        
        <div v-if="filteredRecords.length === 0" class="empty-state">
          <img src="/assets/img/img_none_sj.avif" class="empty-img" />
          <div class="empty-text">
            <span>暂无存款记录</span>
            <span class="view-all" @click="loadAllRecords">查看更多</span>
          </div>
        </div>
      </div>
    </div>
    
    
    <div class="close-circle-outer" @click="visible = false">
      <van-icon name="cross" size="18" />
    </div>
  </van-popup>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { showToast } from 'vant'
import { rechargeApi } from '@/api/recharge'

const props = defineProps({
  show: { type: Boolean, default: false },
  theme: { type: String, default: '' }
})

const emit = defineEmits(['update:show', 'view-detail'])

const visible = computed({
  get: () => props.show,
  set: (val) => emit('update:show', val)
})

const showTimePicker = ref(false)
const showStatusPicker = ref(false)
const timeFilter = ref('today')
const statusFilter = ref('all')
const records = ref([])
const loading = ref(false)

const timeActions = [
  { name: '今日', value: 'today' },
  { name: '昨日', value: 'yesterday' },
  { name: '近7日', value: 'week' },
  { name: '近30日', value: 'month' },
  { name: '全部', value: 'all' }
]

const statusActions = [
  { name: '全部状态', value: 'all' },
  { name: '确认中', value: 'confirming' },
  { name: '等待付款', value: 'pending' },
  { name: '存款超时', value: 'timeout' },
  { name: '存款失败', value: 'failed' },
  { name: '存款取消', value: 'cancelled' },
  { name: '存款成功', value: 'success' }
]

const stateToStatus = {
  '0': 'pending',
  '1': 'confirming',
  '2': 'success',
  '3': 'failed',
  '4': 'cancelled',
  '5': 'timeout'
}

const filteredRecords = computed(() => {
  let result = [...records.value]
  if (statusFilter.value !== 'all') {
    result = result.filter(r => {
      const status = stateToStatus[r.state] || r.status || r.state
      return status === statusFilter.value
    })
  }
  return result
})

const getTimeText = (val) => timeActions.find(t => t.value === val)?.name || '今日'
const getStatusFilterText = (val) => statusActions.find(s => s.value === val)?.name || '全部状态'

const toggleTimePicker = () => {
  showStatusPicker.value = false
  showTimePicker.value = !showTimePicker.value
}

const toggleStatusPicker = () => {
  showTimePicker.value = false
  showStatusPicker.value = !showStatusPicker.value
}

const closeDropdowns = () => {
  showTimePicker.value = false
  showStatusPicker.value = false
}

const onTimeSelect = (action) => {
  timeFilter.value = action.value
  showTimePicker.value = false
}

const onStatusSelect = (action) => {
  statusFilter.value = action.value
  showStatusPicker.value = false
}

const copyText = async (text) => {
  try {
    await navigator.clipboard.writeText(text)
    showToast('已复制')
  } catch (e) {
    showToast('复制失败')
  }
}

const formatTime = (time) => {
  if (!time) return ''
  let date
  if (typeof time === 'number' && time > 1000000000000) {
    date = new Date(time)
  } else if (typeof time === 'number' && time > 1000000000) {
    date = new Date(time * 1000)
  } else if (typeof time === 'string' && time.includes('-')) {
    return time
  } else {
    date = new Date(time)
  }
  if (isNaN(date.getTime())) return time
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  const hours = String(date.getHours()).padStart(2, '0')
  const minutes = String(date.getMinutes()).padStart(2, '0')
  return `${year}-${month}-${day} ${hours}:${minutes}`
}

const getDateRange = (type) => {
  const now = new Date()
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())
  switch (type) {
    case 'today':
      return { startTime: today.getTime(), endTime: now.getTime() }
    case 'yesterday':
      const yesterday = new Date(today)
      yesterday.setDate(yesterday.getDate() - 1)
      return { startTime: yesterday.getTime(), endTime: today.getTime() - 1 }
    case 'week':
      const weekAgo = new Date(today)
      weekAgo.setDate(weekAgo.getDate() - 7)
      return { startTime: weekAgo.getTime(), endTime: now.getTime() }
    case 'month':
      const monthAgo = new Date(today)
      monthAgo.setDate(monthAgo.getDate() - 30)
      return { startTime: monthAgo.getTime(), endTime: now.getTime() }
    case 'all':
      return {}
    default:
      return {}
  }
}

const loadRecords = async () => {
  try {
    loading.value = true
    const params = { page: 1, pageSize: 50 }
    const dateRange = getDateRange(timeFilter.value)
    if (dateRange.startTime) params.startTime = dateRange.startTime
    if (dateRange.endTime) params.endTime = dateRange.endTime
    
    if (statusFilter.value !== 'all') {
      const statusToState = {
        'pending': 0, 'confirming': 1, 'success': 2,
        'failed': 3, 'cancelled': 4, 'timeout': 5
      }
      if (statusToState[statusFilter.value] !== undefined) {
        params.state = statusToState[statusFilter.value]
      }
    }
    
    const res = await rechargeApi.getRecords(params)
    if (res.code === 0 && res.data) {
      const list = res.data.list || res.data || []
      records.value = list.map(item => ({
        id: item.id || item.trano,
        orderNo: item.trano || item.orderNo || item.order_no,
        amount: item.amount || item.money,
        status: stateToStatus[item.state] || item.status || item.state,
        state: item.state,
        createTime: formatTime(item.createTime || item.addtime || item.create_time),
        chain: item.chain || 'TRC-20',
        paytype: item.paytype || 'USDT'
      }))
    }
  } catch (e) {
  } finally {
    loading.value = false
  }
}

const getStatusClass = (status) => {
  const map = {
    'pending': 'status-pending',
    'confirming': 'status-pending',
    'success': 'status-success',
    'failed': 'status-failed',
    'timeout': 'status-timeout',
    'cancelled': 'status-cancelled'
  }
  return map[status] || ''
}

const getStatusText = (status) => {
  const map = {
    'pending': '等待付款',
    'confirming': '确认中',
    'success': '存款成功',
    'failed': '存款失败',
    'timeout': '存款超时',
    'cancelled': '存款取消'
  }
  return map[status] || status
}

const getPayTypeName = (type) => {
  const map = {
    'USDT': 'USDT',
    'alipay': '支付宝',
    'weixin': '微信',
    'linepay': '银行转账'
  }
  return map[type] || type || 'USDT'
}

const loadAllRecords = () => {
  timeFilter.value = 'all'
}

watch([timeFilter, statusFilter], () => {
  if (props.show) loadRecords()
})

watch(() => props.show, (val) => {
  if (val) loadRecords()
})

defineExpose({ loadRecords })
</script>

<style scoped>
.history-popup {
  overflow: visible !important;
  background: transparent !important;
}

.view-history {
  background: #fff;
  display: flex;
  flex-direction: column;
  border-radius: 12px;
  position: relative;
  height: 100%;
  padding-bottom: 20px;
}

.history-header {
  padding: 24px 0 15px;
  text-align: center;
}

.history-title {
  font-size: 18px;
  font-weight: 500;
  color: #333;
}

.filter-bar {
  padding: 0 20px 15px;
  display: flex;
  gap: 12px;
  position: relative;
  z-index: 10;
}

.filter-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 12px;
  height: 32px;
  background: #fff;
  border: 1px solid #dcdee0;
  border-radius: 16px;
  min-width: 80px;
  cursor: pointer;
  transition: all 0.2s;
}

.filter-item.active {
  border-color: #26A17B;
  color: #26A17B;
}

.filter-item .van-icon {
  color: #969799;
  font-size: 12px;
  margin-left: 4px;
  transition: transform 0.2s;
}

.filter-item .van-icon.rotate {
  transform: rotate(180deg);
}

.dropdown-menu {
  position: absolute;
  top: 105px;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  z-index: 20;
  padding: 4px 0;
  border: 1px solid #ebedf0;
}

.dropdown-time {
  left: 20px;
  width: 120px;
}

.dropdown-status {
  left: 112px;
  width: 120px;
}

.dropdown-item {
  padding: 10px 16px;
  font-size: 14px;
  color: #323233;
  display: flex;
  align-items: center;
  justify-content: space-between;
  cursor: pointer;
}

.dropdown-item.selected {
  color: #26A17B;
  font-weight: 500;
}

.dropdown-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 5;
}

.record-list {
  flex: 1;
  overflow-y: auto;
  padding: 0 20px 20px;
}

.record-item {
  display: flex;
  align-items: center;
  padding: 16px 0;
  border-bottom: 1px solid #f5f6f7;
  cursor: pointer;
}

.record-left {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  flex: 1;
  min-width: 0;
}

.record-icon-img {
  width: 40px;
  height: 40px;
  flex-shrink: 0;
}

.usdt-icon-css {
  width: 100%;
  height: 100%;
  background: #26A17B;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 22px;
  font-weight: bold;
}

.record-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.record-type {
  font-size: 15px;
  color: #323233;
  font-weight: 600;
}

.record-time {
  font-size: 11px;
  color: #969799;
}

.record-order {
  font-size: 11px;
  color: #969799;
  display: flex;
  align-items: center;
  gap: 6px;
}

.order-no-text {
  word-break: break-all;
}

.copy-mini {
  color: #26A17B;
  font-size: 12px;
  cursor: pointer;
}

.record-right {
  text-align: right;
  margin-left: 10px;
}

.record-amount {
  font-size: 17px;
  color: #323233;
  font-weight: 600;
}

.record-status {
  font-size: 13px;
  margin-top: 6px;
}

.record-status.status-pending { color: #ff976a; }
.record-status.status-success { color: #26A17B; }
.record-status.status-failed,
.record-status.status-cancelled,
.record-status.status-timeout { color: #ee0a24; }

.record-arrow {
  color: #c8c9cc;
  font-size: 14px;
}

.close-circle-outer {
  position: absolute;
  bottom: -60px;
  left: 50%;
  transform: translateX(-50%);
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: 1.5px solid rgba(255, 255, 255, 0.9);
  display: flex;
  align-items: center;
  justify-content: center;
  color: rgba(255, 255, 255, 0.9);
  cursor: pointer;
  z-index: 9999;
}

.empty-state {
  text-align: center;
  padding: 60px 0;
}

.empty-img {
  width: 120px;
  opacity: 0.6;
}

.empty-text {
  margin-top: 15px;
  font-size: 14px;
  color: #999;
}

.view-all {
  color: #26A17B;
  margin-left: 8px;
  cursor: pointer;
}

.theme-lottery .filter-item.active {
  border-color: #5691fe;
  color: #5691fe;
}

.theme-lottery .dropdown-item.selected {
  color: #5691fe;
}

.theme-lottery .copy-mini {
  color: #5691fe;
}

.theme-lottery .record-status.status-success {
  color: #5691fe;
}

.theme-lottery .view-all {
  color: #5691fe;
}
</style>
