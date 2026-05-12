<template>
  <div class="tab-content tab-records">
    <div class="filter-header">
      <div class="filters">
        <div class="filter-dropdown-wrapper">
          <div class="capsule-dropdown" @click="toggleTimeDropdown">
            {{ getTimeFilterText() }} <van-icon :name="showTimeDropdown ? 'arrow-up' : 'arrow-down'" />
          </div>
          <div class="filter-dropdown-overlay" v-if="showTimeDropdown || showDatePicker" @click="closeDateDropdown"></div>
          <transition name="dropdown-fade">
            <div class="filter-dropdown-list" v-if="showTimeDropdown && !showDatePicker">
              <div 
                class="filter-dropdown-item" 
                v-for="(opt, idx) in timeOptions" 
                :key="opt.value"
                :class="{ active: timeFilter === idx }"
                @click="selectTimeFilter(idx)"
              >
                {{ opt.text }}
              </div>
            </div>
          </transition>
          <transition name="dropdown-fade">
            <div class="date-picker-panel" v-if="showDatePicker">
              <div class="date-picker-panel-header">
                <span class="back-btn" @click="backToTimeOptions">&lt; 返回</span>
                <span class="panel-title">选择日期范围</span>
                <span class="placeholder-btn"></span>
              </div>
              <div class="date-input-row">
                <div class="date-input-item" :class="{ active: currentDateType === 'start' }" @click="currentDateType = 'start'">
                  <span class="date-label">开始</span>
                  <span class="date-value">{{ customStartDate || '请选择' }}</span>
                </div>
                <span class="date-sep">至</span>
                <div class="date-input-item" :class="{ active: currentDateType === 'end' }" @click="currentDateType = 'end'">
                  <span class="date-label">结束</span>
                  <span class="date-value">{{ customEndDate || '请选择' }}</span>
                </div>
              </div>
              <van-date-picker
                v-model="currentPickerDate"
                :min-date="minDate"
                :max-date="maxDate"
                @change="onDateChange"
                :show-toolbar="false"
                :visible-item-count="5"
              />
              <div class="date-picker-panel-footer">
                <div class="date-confirm-btn" :class="{ disabled: !customStartDate || !customEndDate }" @click="confirmDateRange">确定</div>
              </div>
            </div>
          </transition>
        </div>
        <div class="filter-dropdown-wrapper">
          <div class="capsule-dropdown" @click="toggleStatusDropdown">
            {{ getStatusText(statusFilter) }} <van-icon :name="showStatusDropdown ? 'arrow-up' : 'arrow-down'" />
          </div>
          <div class="filter-dropdown-overlay" v-if="showStatusDropdown" @click="showStatusDropdown = false"></div>
          <transition name="dropdown-fade">
            <div class="filter-dropdown-list" v-if="showStatusDropdown">
              <div 
                class="filter-dropdown-item" 
                v-for="opt in statusOptions" 
                :key="opt.value"
                :class="{ active: statusFilter === opt.value }"
                @click="selectStatusFilter(opt.value)"
              >
                {{ opt.text }}
              </div>
            </div>
          </transition>
        </div>
      </div>
      <div class="total-stat">
        <span class="stat-label">累计提现</span>
        <span class="stat-amount">{{ totalWithdraw.toFixed(2) }} USDT <img src="/assets/img/comm_icon_retry.svg" class="refresh-record-icon" :class="{ spinning: isRecordsLoading }" @click="refreshRecords" /></span>
      </div>
    </div>

    <div v-if="records.length > 0" class="record-list">
        <div class="record-item" v-for="(item, index) in records" :key="`${item.id}-${index}`" @click="showRecordDetail(item)">
          <div class="record-left">
            <div class="record-type">{{ getRecordTypeName(item) }}</div>
            <div class="record-time">{{ item.createTime }}</div>
          </div>
          <div class="record-right">
            <div class="record-amount" :class="{ 'refund': item.state === 2 || item.state === 3 }">{{ (item.state === 2 || item.state === 3) ? '+' : '-' }}{{ Number(item.amount).toFixed(2) }}</div>
            <div class="record-status-row">
              <span class="record-status" :class="'status-' + item.state">{{ getRecordStatusText(item.state) }}</span>
              <span v-if="item.state === 0" class="cancel-btn" @click.stop="cancelWithdraw(item)">取消</span>
            </div>
          </div>
        </div>
        <div v-if="hasMoreRecords" class="load-more" @click="loadMoreRecords">
          <span>查看更多</span>
          <van-icon name="arrow-down" />
        </div>
        <div v-else-if="records.length >= 10" class="no-more">没有更多了</div>
    </div>
    <div v-else class="empty-list">
      <img src="/assets/img/img_none_sj.avif" class="empty-img" />
      <div class="empty-text">暂无提现记录，可<span class="green-link" @click="showAllHistory">查看更多</span></div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { showToast } from 'vant'
import { useWithdraw } from './useWithdraw'

const emit = defineEmits(['show-detail'])

const {
  records,
  totalWithdraw,
  isRecordsLoading,
  hasMoreRecords,
  timeFilter,
  statusFilter,
  showAllHistoryMode,
  timeOptions,
  statusOptions,
  customStartDate,
  customEndDate,
  loadRecords,
  refreshRecords,
  loadMoreRecords,
  cancelWithdraw,
  getRecordTypeName,
  getRecordStatusText,
  getStatusText,
  getTimeFilterText,
  showAllHistory,
  formatDate
} = useWithdraw()

const showTimeDropdown = ref(false)
const showStatusDropdown = ref(false)
const showDatePicker = ref(false)
const currentDateType = ref('')
const minDate = new Date(2024, 0, 1)
const maxDate = new Date()
const currentPickerDate = ref([new Date().getFullYear().toString(), (new Date().getMonth() + 1).toString().padStart(2, '0'), new Date().getDate().toString().padStart(2, '0')])

const toggleTimeDropdown = () => {
  showTimeDropdown.value = !showTimeDropdown.value
  showStatusDropdown.value = false
}

const toggleStatusDropdown = () => {
  showStatusDropdown.value = !showStatusDropdown.value
  showTimeDropdown.value = false
}

const selectTimeFilter = (idx) => {
  timeFilter.value = idx
  showAllHistoryMode.value = false
  if (timeOptions[idx].value === 'custom') {
    showTimeDropdown.value = false
    const today = formatDate(new Date())
    if (!customStartDate.value) customStartDate.value = today
    if (!customEndDate.value) customEndDate.value = today
    currentDateType.value = 'start'
    const dateParts = customStartDate.value.split('-')
    currentPickerDate.value = dateParts
    showDatePicker.value = true
  } else {
    showTimeDropdown.value = false
    loadRecords(false)
  }
}

const onDateChange = ({ selectedValues }) => {
  const dateStr = selectedValues.join('-')
  if (currentDateType.value === 'start') {
    customStartDate.value = dateStr
    if (customEndDate.value && dateStr > customEndDate.value) {
      customEndDate.value = dateStr
    }
  } else {
    customEndDate.value = dateStr
    if (customStartDate.value && dateStr < customStartDate.value) {
      customStartDate.value = dateStr
    }
  }
}

const confirmDateRange = () => {
  if (customStartDate.value && customEndDate.value) {
    showDatePicker.value = false
    loadRecords(false)
  } else {
    showToast('请选择完整的日期范围')
  }
}

const backToTimeOptions = () => {
  showDatePicker.value = false
  if (!customStartDate.value || !customEndDate.value) {
    timeFilter.value = 0
    showAllHistoryMode.value = false
  }
  showTimeDropdown.value = true
}

const closeDateDropdown = () => {
  showTimeDropdown.value = false
  showDatePicker.value = false
}

watch(currentDateType, (newType) => {
  if (newType === 'start' && customStartDate.value) {
    currentPickerDate.value = customStartDate.value.split('-')
  } else if (newType === 'end' && customEndDate.value) {
    currentPickerDate.value = customEndDate.value.split('-')
  }
})

const selectStatusFilter = (val) => {
  statusFilter.value = val
  showAllHistoryMode.value = false
  showStatusDropdown.value = false
  loadRecords()
}

const showRecordDetail = (item) => {
  emit('show-detail', item)
}
</script>

<style scoped>
.tab-records {
  background: #fff;
  flex: 1;
  display: flex;
  flex-direction: column;
}
.filter-header {
  padding: 10px 15px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #fff;
  border-bottom: 1px solid #f5f5f5;
}
.filters {
  display: flex;
  gap: 10px;
}
.filter-dropdown-wrapper {
  position: relative;
}
.capsule-dropdown {
  background: #fff;
  padding: 6px 12px;
  border-radius: 16px;
  font-size: 13px;
  color: #333;
  display: flex;
  align-items: center;
  gap: 4px;
  border: 1px solid #e5e5e5;
  cursor: pointer;
  user-select: none;
}
.capsule-dropdown .van-icon {
  font-size: 10px;
  color: #999;
  transition: transform 0.2s;
}
.filter-dropdown-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 99;
}
.filter-dropdown-list {
  position: absolute;
  top: calc(100% + 4px);
  left: 0;
  background: #fff;
  border: 1px solid #e5e5e5;
  border-radius: 8px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
  z-index: 100;
  min-width: 100px;
  overflow: hidden;
}
.filter-dropdown-item {
  padding: 10px 16px;
  font-size: 13px;
  color: #333;
  white-space: nowrap;
  cursor: pointer;
  transition: background 0.2s;
}
.filter-dropdown-item:hover {
  background: #f7f8fa;
}
.filter-dropdown-item.active {
  color: #26A17B;
  background: #f0faf7;
}
.filter-dropdown-item:not(:last-child) {
  border-bottom: 1px solid #f5f5f5;
}

.dropdown-fade-enter-active {
  animation: dropdown-in 0.2s ease-out;
}
.dropdown-fade-leave-active {
  animation: dropdown-out 0.15s ease-in;
}
@keyframes dropdown-in {
  0% { opacity: 0; transform: scaleY(0.8); }
  100% { opacity: 1; transform: scaleY(1); }
}
@keyframes dropdown-out {
  0% { opacity: 1; transform: scaleY(1); }
  100% { opacity: 0; transform: scaleY(0.8); }
}

.date-picker-panel {
  position: absolute;
  top: calc(100% + 4px);
  left: 0;
  background: #fff;
  border: 1px solid #e5e5e5;
  border-radius: 16px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
  z-index: 100;
  width: 280px;
  overflow: hidden;
}
.date-picker-panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  border-bottom: 1px solid #f5f5f5;
}
.back-btn {
  font-size: 13px;
  color: #26A17B;
  cursor: pointer;
}
.panel-title {
  font-size: 14px;
  color: #333;
  font-weight: 500;
}
.placeholder-btn {
  width: 40px;
}
.date-input-row {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 12px 16px;
  gap: 10px;
}
.date-input-item {
  flex: 1;
  text-align: center;
  padding: 8px 12px;
  background: #f7f8fa;
  border-radius: 8px;
  cursor: pointer;
  border: 1px solid transparent;
  transition: all 0.2s;
}
.date-input-item.active {
  border-color: #26A17B;
  background: #f0faf7;
}
.date-label {
  display: block;
  font-size: 11px;
  color: #999;
  margin-bottom: 2px;
}
.date-value {
  font-size: 13px;
  color: #333;
}
.date-input-item.active .date-value {
  color: #26A17B;
}
.date-sep {
  font-size: 13px;
  color: #999;
}
.date-picker-panel :deep(.van-picker) {
  background: transparent;
  height: 150px !important;
}
.date-picker-panel :deep(.van-picker__columns) {
  height: 150px !important;
}
.date-picker-panel :deep(.van-picker-column) {
  height: 150px !important;
}
.date-picker-panel :deep(.van-picker-column__wrapper) {
  max-height: 150px;
}
.date-picker-panel :deep(.van-picker-column__item) {
  height: 30px;
  line-height: 30px;
  font-size: 14px;
  color: #999;
}
.date-picker-panel :deep(.van-picker-column__item--selected) {
  color: #333;
  font-weight: 500;
}
.date-picker-panel :deep(.van-picker__mask) {
  display: none;
}
.date-picker-panel :deep(.van-picker__frame) {
  height: 30px;
  top: 50%;
  transform: translateY(-50%);
}
.date-picker-panel-footer {
  padding: 12px 16px;
  border-top: 1px solid #f5f5f5;
}
.date-confirm-btn {
  background: #26A17B;
  color: #fff;
  text-align: center;
  padding: 10px;
  border-radius: 20px;
  font-size: 14px;
  cursor: pointer;
}
.date-confirm-btn.disabled {
  background: #ccc;
  cursor: not-allowed;
}

.total-stat {
  font-size: 12px;
  color: #666;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  line-height: 1.3;
}
.stat-amount {
  color: #333;
  font-weight: 500;
  display: flex;
  align-items: center;
}
.refresh-record-icon {
  width: 14px;
  height: 14px;
  margin-left: 4px;
  cursor: pointer;
  filter: invert(48%) sepia(79%) saturate(374%) hue-rotate(115deg) brightness(93%) contrast(88%);
  transition: transform 0.3s;
}
.refresh-record-icon.spinning {
  animation: spin 1s linear infinite;
}
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.empty-list {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding-bottom: 100px;
}
.empty-img { 
  width: 120px; 
  height: auto;
  margin-bottom: 15px; 
}
.empty-text { font-size: 13px; color: #999; }
.green-link { color: #26A17B; margin-left: 2px; cursor: pointer; }

.load-more {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  padding: 15px;
  color: #26A17B;
  font-size: 13px;
  cursor: pointer;
}
.load-more .van-icon {
  font-size: 12px;
}
.no-more {
  text-align: center;
  padding: 15px;
  color: #ccc;
  font-size: 12px;
}

.record-list {
  flex: 1;
  padding: 0 15px;
}
.record-item {
  padding: 15px 0;
  border-bottom: 1px solid #f5f5f5;
  display: flex;
  justify-content: space-between;
  align-items: center;
  cursor: pointer;
}
.record-left {
  flex: 1;
}
.record-type {
  font-size: 15px;
  color: #333;
  font-weight: 500;
}
.record-time {
  font-size: 12px;
  color: #999;
  margin-top: 4px;
}
.record-right {
  text-align: right;
}
.record-amount {
  font-size: 16px;
  color: #333;
  font-weight: 600;
}
.record-amount.refund {
  color: #26A17B;
}
.record-status-row {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 8px;
  margin-top: 4px;
}
.record-status {
  font-size: 12px;
}
.record-status.status-0 { color: #f0ad4e; }
.record-status.status-1 { color: #26A17B; }
.record-status.status-2 { color: #d9534f; }
.record-status.status-3 { color: #999; }
.record-status.status--1 { color: #d9534f; }
.cancel-btn {
  font-size: 12px;
  color: #26A17B;
  padding: 2px 8px;
  border: 1px solid #26A17B;
  border-radius: 10px;
  cursor: pointer;
}
.cancel-btn:active {
  background: #f0faf7;
}
</style>
