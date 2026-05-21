<template>
  <div class="my-commission-tab">
    <div class="settlement-bar">
      <div class="info-item">
        <span class="label">Chu kỳ thanh toán</span>
        <span class="value">Tháng</span>
      </div>
      <div class="countdown">
        Đến lần thanh toán tiếp theo <em>{{ countdown }}</em>
      </div>
    </div>

    <div class="time-filter">
      <div class="time-select" @click="showDropdown = !showDropdown">
        <span>{{ currentTimeLabel }}</span>
        <van-icon :name="showDropdown ? 'arrow-up' : 'arrow-down'" />
      </div>
      <div class="dropdown-list" v-show="showDropdown">
        <div 
          v-for="item in timeOptions" 
          :key="item.value"
          class="dropdown-item"
          :class="{ active: currentTime === item.value }"
          @click="selectTime(item.value)"
        >
          {{ item.label }}
        </div>
      </div>
    </div>

    <van-list
      v-if="list.length > 0"
      v-model:loading="loading"
      :finished="finished"
      finished-text="Không còn nữa"
      @load="onLoad"
      class="commission-list"
    >
      <div class="commission-card" v-for="item in list" :key="item.id">
        <div class="item-header">
          <span class="date">{{ item.date }}</span>
          <span class="status" :class="item.status">{{ item.statusText }}</span>
        </div>
        <div class="item-body">
          <div class="detail-row">
            <span class="label">Số tiền thành tích:</span>
            <span class="value">{{ formatNumber(item.performance) }}</span>
          </div>
          <div class="detail-row">
            <span class="label">Tỷ lệ hoa hồng:</span>
            <span class="value">{{ item.rate }}%</span>
          </div>
          <div class="detail-row">
            <span class="label">Số tiền hoa hồng:</span>
            <span class="value green">{{ formatNumber(item.commission) }}</span>
          </div>
        </div>
        <div class="item-footer" v-if="item.status === 'pending'">
          <button class="claim-btn" @click="$emit('claim')">Nhận</button>
        </div>
      </div>
    </van-list>

    <div class="empty-state" v-if="!loading && list.length === 0">
      <img src="/assets/img/img_none_sj.avif" class="empty-icon" />
      <p>Chưa có bản ghi</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { agentApi } from '@/api/agent'
import { timeOptionsWithAll, formatNumber } from '../useInvite'

const emit = defineEmits(['claim'])

const timeOptions = timeOptionsWithAll
const currentTime = ref('today')
const showDropdown = ref(false)
const loading = ref(false)
const finished = ref(false)
const list = ref([])
const page = ref(1)
const countdown = ref('')

const currentTimeLabel = computed(() => {
  const item = timeOptions.find(t => t.value === currentTime.value)
  return item ? item.label : 'Hôm nay'
})

const selectTime = (value) => {
  currentTime.value = value
  showDropdown.value = false
  page.value = 1
  list.value = []
  finished.value = false
  onLoad()
}

const onLoad = async () => {
  if (finished.value) return
  loading.value = true
  
  try {
    const res = await agentApi.getMyCommission({
      dateType: currentTime.value,
      page: page.value,
      pageSize: 20
    })
    
    if (res.code === 0 && res.data) {
      if (res.data.countdown) {
        countdown.value = res.data.countdown
      }
      
      if (page.value === 1) {
        list.value = res.data.list || []
      } else {
        list.value.push(...(res.data.list || []))
      }
      
      if (!res.data.list || res.data.list.length < 20) {
        finished.value = true
      } else {
        page.value++
      }
    } else {
      finished.value = true
    }
  } catch (e) {
    finished.value = true
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  onLoad()
})
</script>

<style scoped>
.my-commission-tab {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.settlement-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #fff;
  padding: 12px;
  margin: -12px -12px 0 -12px;
  border-bottom: 1px solid #eee;
}

.info-item {
  display: flex;
  gap: 8px;
  font-size: 13px;
}

.info-item .label {
  color: #999;
}

.info-item .value {
  color: #333;
  font-weight: 500;
}

.countdown {
  font-size: 12px;
  color: #999;
}

.countdown em {
  font-style: normal;
  color: #26A17B;
}

.time-filter {
  position: relative;
  margin-bottom: 0;
}

.time-select {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 15px;
  background: #fff;
  border-radius: 15px;
  font-size: 13px;
  color: #333;
}

.time-select .van-icon {
  font-size: 12px;
  color: #999;
}

.dropdown-list {
  position: absolute;
  top: 100%;
  left: 0;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.1);
  z-index: 100;
  min-width: 120px;
  overflow: hidden;
}

.dropdown-item {
  padding: 12px 15px;
  font-size: 13px;
  color: #333;
  border-bottom: 1px solid #f5f5f5;
}

.dropdown-item:last-child {
  border-bottom: none;
}

.dropdown-item.active {
  color: #26A17B;
  background: #f0fff8;
}

.commission-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.commission-list :deep(.van-list__finished-text) {
  background: transparent;
  color: #999;
}

.commission-card {
  background: #fff;
  border-radius: 12px;
  padding: 15px;
}

.item-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.item-header .date {
  font-size: 14px;
  font-weight: 600;
  color: #333;
}

.item-header .status {
  font-size: 12px;
  padding: 2px 8px;
  border-radius: 10px;
}

.item-header .status.pending {
  background: #fff3e0;
  color: #ff9800;
}

.item-header .status.claimed {
  background: #e8f5e9;
  color: #26A17B;
}

.item-body {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  font-size: 13px;
}

.detail-row .label {
  color: #999;
}

.detail-row .value {
  color: #333;
}

.detail-row .value.green {
  color: #26A17B;
}

.item-footer {
  margin-top: 12px;
  text-align: right;
}

.claim-btn {
  padding: 6px 20px;
  background: #26A17B;
  border: none;
  border-radius: 15px;
  color: #fff;
  font-size: 13px;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  color: #999;
}

.empty-icon {
  width: 120px;
  height: auto;
  margin-bottom: 15px;
}

.empty-state p {
  font-size: 14px;
  margin: 0;
}
</style>
