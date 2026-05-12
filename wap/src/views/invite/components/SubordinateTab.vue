<template>
  <div class="subordinate-tab">
    <div class="filter-row">
      <div class="filter-item">
        <div class="filter-select" @click="showTimeDropdown = !showTimeDropdown">
          <span>{{ currentTimeLabel }}</span>
          <van-icon :name="showTimeDropdown ? 'arrow-up' : 'arrow-down'" />
        </div>
        <div class="dropdown-list" v-show="showTimeDropdown">
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

      <div class="filter-item">
        <div class="filter-select" @click="showSortDropdown = !showSortDropdown">
          <span>{{ currentSortLabel }}</span>
          <van-icon :name="showSortDropdown ? 'arrow-up' : 'arrow-down'" />
        </div>
        <div class="dropdown-list" v-show="showSortDropdown">
          <div 
            v-for="item in sortOptions" 
            :key="item.value"
            class="dropdown-item"
            :class="{ active: currentSort === item.value }"
            @click="selectSort(item.value)"
          >
            {{ item.label }}
          </div>
        </div>
      </div>

      <div class="search-box">
        <van-field 
          v-model="searchId" 
          placeholder="会员ID"
          :border="false"
        >
          <template #right-icon>
            <van-icon name="search" @click="handleSearch" />
          </template>
        </van-field>
      </div>
    </div>

    <div class="list-container">
      <van-list
        v-model:loading="loading"
        :finished="finished"
        finished-text="没有更多了"
        @load="onLoad"
      >
        <template v-if="type === 'info'">
          <div class="sub-item" v-for="item in list" :key="item.id">
            <div class="item-header">
              <span class="member-id">{{ item.memberId }}</span>
              <span class="register-time">{{ item.registerTime }}</span>
            </div>
            <div class="item-body">
              <div class="detail-row">
                <span class="label">充值金额：</span>
                <span class="value">{{ formatNumber(item.rechargeAmount) }}</span>
              </div>
              <div class="detail-row">
                <span class="label">有效投注：</span>
                <span class="value">{{ formatNumber(item.validBet) }}</span>
              </div>
              <div class="detail-row">
                <span class="label">最后登录：</span>
                <span class="value">{{ item.lastLogin }}</span>
              </div>
            </div>
          </div>
        </template>

        <template v-if="type === 'bets'">
          <div class="sub-item" v-for="item in list" :key="item.id">
            <div class="item-header">
              <span class="member-id">{{ item.memberId }}</span>
              <span class="amount green">{{ formatNumber(item.validBet) }}</span>
            </div>
            <div class="item-body">
              <div class="detail-row">
                <span class="label">投注次数：</span>
                <span class="value">{{ item.betCount }}</span>
              </div>
              <div class="detail-row">
                <span class="label">输赢金额：</span>
                <span class="value" :class="item.winLoss >= 0 ? 'green' : 'red'">
                  {{ item.winLoss >= 0 ? '+' : '' }}{{ formatNumber(item.winLoss) }}
                </span>
              </div>
            </div>
          </div>
        </template>

        <template v-if="type === 'finance'">
          <div class="sub-item" v-for="item in list" :key="item.id">
            <div class="item-header">
              <span class="member-id">{{ item.memberId }}</span>
              <span class="amount">{{ formatNumber(item.rechargeAmount) }}</span>
            </div>
            <div class="item-body">
              <div class="detail-row">
                <span class="label">充值次数：</span>
                <span class="value">{{ item.rechargeCount }}</span>
              </div>
              <div class="detail-row">
                <span class="label">提现金额：</span>
                <span class="value">{{ formatNumber(item.withdrawAmount) }}</span>
              </div>
              <div class="detail-row">
                <span class="label">提现次数：</span>
                <span class="value">{{ item.withdrawCount }}</span>
              </div>
            </div>
          </div>
        </template>

        <template v-if="type === 'claims'">
          <div class="sub-item" v-for="item in list" :key="item.id">
            <div class="item-header">
              <span class="member-id">{{ item.memberId }}</span>
              <span class="amount green">{{ formatNumber(item.claimAmount) }}</span>
            </div>
            <div class="item-body">
              <div class="detail-row">
                <span class="label">领取类型：</span>
                <span class="value">{{ item.claimType }}</span>
              </div>
              <div class="detail-row">
                <span class="label">领取时间：</span>
                <span class="value">{{ item.claimTime }}</span>
              </div>
            </div>
          </div>
        </template>
      </van-list>

      <div class="empty-state" v-if="!loading && list.length === 0">
        <img src="/assets/img/img_none_sj.avif" class="empty-icon" />
        <p>暂无记录</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { agentApi } from '@/api/agent'
import { timeOptionsWithAll } from '../useInvite'

const props = defineProps({
  type: {
    type: String,
default: 'info'
  }
})

const timeOptions = timeOptionsWithAll

const sortOptionsMap = {
  info: [
    { label: '登录日期排序', value: 'loginDate' },
    { label: '下级人数排序', value: 'subCount' },
    { label: '充值金额排序', value: 'rechargeAmount' },
    { label: '有效投注排序', value: 'validBet' }
  ],
  bets: [
    { label: '有效投注排序', value: 'validBet' },
    { label: '累计输赢排序', value: 'winLoss' }
  ],
  finance: [
    { label: '充值金额排序', value: 'rechargeAmount' },
    { label: '充值次数排序', value: 'rechargeCount' },
    { label: '提现金额排序', value: 'withdrawAmount' },
    { label: '提现次数排序', value: 'withdrawCount' },
    { label: '余额排序', value: 'balance' }
  ],
  claims: [
    { label: '合计领取排序', value: 'totalClaim' },
    { label: '返水领取排序', value: 'rebateClaim' },
    { label: '活动领取排序', value: 'activityClaim' },
    { label: '任务领取排序', value: 'taskClaim' },
    { label: '代理佣金排序', value: 'agentCommission' }
  ]
}

const sortOptions = computed(() => sortOptionsMap[props.type] || sortOptionsMap.info)

const currentTime = ref('today')
const currentSort = ref('')
const searchId = ref('')
const showTimeDropdown = ref(false)
const showSortDropdown = ref(false)
const loading = ref(false)
const finished = ref(false)
const list = ref([])
const page = ref(1)

const currentTimeLabel = computed(() => {
  const item = timeOptions.find(t => t.value === currentTime.value)
  return item ? item.label : '今日'
})

const currentSortLabel = computed(() => {
  if (!currentSort.value) {
    return sortOptions.value[0]?.label || '排序'
  }
  const item = sortOptions.value.find(s => s.value === currentSort.value)
  return item ? item.label : '排序'
})

const formatNumber = (num) => {
  const n = parseFloat(num) || 0
  return n.toFixed(2)
}

const selectTime = (value) => {
  currentTime.value = value
  showTimeDropdown.value = false
  resetAndLoad()
}

const selectSort = (value) => {
  currentSort.value = value
  showSortDropdown.value = false
  resetAndLoad()
}

const handleSearch = () => {
  resetAndLoad()
}

const resetAndLoad = () => {
  page.value = 1
  list.value = []
  finished.value = false
  onLoad()
}

const getApiMethod = () => {
  const apiMap = {
    info: agentApi.getSubordinateList,
    bets: agentApi.getSubordinateBets,
    finance: agentApi.getSubordinateFinance,
    claims: agentApi.getSubordinateClaims
  }
  return apiMap[props.type] || agentApi.getSubordinateList
}

const onLoad = async () => {
  if (finished.value) return
  loading.value = true
  
  try {
    const apiMethod = getApiMethod()
    const res = await apiMethod({
      dateType: currentTime.value,
      sortBy: currentSort.value || sortOptions.value[0]?.value,
      memberId: searchId.value,
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
  currentSort.value = sortOptions.value[0]?.value || ''
  onLoad()
})

watch(() => props.type, () => {
  currentSort.value = sortOptions.value[0]?.value || ''
  resetAndLoad()
})
</script>

<style scoped>
.subordinate-tab {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.filter-row {
  display: flex;
  gap: 10px;
  align-items: flex-start;
}

.filter-item {
  position: relative;
}

.filter-select {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 12px;
  background: #fff;
  border-radius: 15px;
  font-size: 12px;
  color: #333;
  white-space: nowrap;
}

.filter-select .van-icon {
  font-size: 12px;
  color: #999;
}

.dropdown-list {
  position: absolute;
  top: 100%;
  left: 0;
  margin-top: 4px;
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
}

.search-box {
  flex: 1;
  min-width: 80px;
  background: #fff;
  border-radius: 15px;
  overflow: hidden;
}

.search-box :deep(.van-field) {
  padding: 4px 12px;
}

.search-box :deep(.van-field__control) {
  font-size: 12px;
}

.search-box :deep(.van-icon) {
  color: #999;
}

.list-container {
  background: #fff;
  border-radius: 12px;
  padding: 15px;
  min-height: 300px;
}

.sub-item {
  padding: 15px 0;
  border-bottom: 1px solid #f0f0f0;
}

.sub-item:last-child {
  border-bottom: none;
}

.item-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.item-header .member-id {
  font-size: 14px;
  font-weight: 600;
  color: #333;
}

.item-header .register-time {
  font-size: 12px;
  color: #999;
}

.item-header .amount {
  font-size: 16px;
  font-weight: 600;
  color: #333;
}

.item-header .amount.green {
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
