<template>
  <div class="my-performance-tab">
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
      finished-text="没有更多了"
      @load="onLoad"
      class="performance-list"
    >
      <div class="performance-card" v-for="item in list" :key="item.id">
        <div class="item-header">
          <span class="date">{{ item.date }}</span>
          <span class="amount">{{ formatNumber(item.amount) }}</span>
        </div>
        <div class="item-body">
          <div class="detail-row">
            <span class="label">有效投注：</span>
            <span class="value">{{ formatNumber(item.validBet) }}</span>
          </div>
          <div class="detail-row">
            <span class="label">输赢金额：</span>
            <span class="value" :class="item.winLoss >= 0 ? 'green' : 'red'">
              {{ item.winLoss >= 0 ? '+' : '' }}{{ formatNumber(item.winLoss) }}
            </span>
          </div>
        </div>
      </div>
    </van-list>

    <div class="empty-state" v-if="!loading && list.length === 0">
      <img src="/assets/img/img_none_sj.avif" class="empty-icon" />
      <p>暂无记录</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { agentApi } from '@/api/agent'
import { timeOptionsWithAll, formatNumber } from '../useInvite'

const timeOptions = timeOptionsWithAll
const currentTime = ref('today')
const showDropdown = ref(false)
const loading = ref(false)
const finished = ref(false)
const list = ref([])
const page = ref(1)

const currentTimeLabel = computed(() => {
  const item = timeOptions.find(t => t.value === currentTime.value)
  return item ? item.label : '今日'
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
    const res = await agentApi.getMyPerformance({
      dateType: currentTime.value,
      page: page.value,
      pageSize: 20
    })
    
    if (res.code === 0 && res.data) {
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
.my-performance-tab {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.time-filter {
  position: relative;
  margin-bottom: 12px;
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
  left: 12px;
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

.performance-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.performance-list :deep(.van-list__finished-text) {
  background: transparent;
  color: #999;
}

.performance-card {
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

.item-header .amount {
  font-size: 16px;
  font-weight: 600;
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

.detail-row .value.red {
  color: #f44336;
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
