<template>
  <van-popup v-model:show="visible" round class="record-detail-popup" :style="{ width: '92%' }">
    <div class="popup-content" v-if="record">
      <div class="popup-header">
        <div class="popup-title">提现详情</div>
      </div>
      <div class="detail-body">
        <div class="detail-row">
          <span class="detail-label">提现类型</span>
          <span class="detail-value">{{ getRecordTypeName(record) }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">提现金额</span>
          <span class="detail-value amount" :class="{ 'refund': record.state === 2 || record.state === 3 }">{{ (record.state === 2 || record.state === 3) ? '+' : '-' }}{{ Number(record.amount).toFixed(2) }} USDT</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">提现状态</span>
          <span class="detail-value" :class="'status-' + record.state">{{ getRecordStatusText(record.state) }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">申请时间</span>
          <span class="detail-value">{{ record.createTime }}</span>
        </div>
        <div class="detail-row" v-if="record.updateTime && record.state !== 0">
          <span class="detail-label">处理时间</span>
          <span class="detail-value">{{ record.updateTime }}</span>
        </div>
        <div class="detail-row" v-if="record.orderNo">
          <span class="detail-label">订单编号</span>
          <span class="detail-value order-no">{{ record.orderNo }}</span>
        </div>
        <div class="detail-row" v-if="record.address">
          <span class="detail-label">提现地址</span>
          <span class="detail-value address">{{ record.address }}</span>
        </div>
        <div class="detail-row" v-if="record.remark">
          <span class="detail-label">备注</span>
          <span class="detail-value">{{ record.remark }}</span>
        </div>
      </div>
      <div class="detail-footer" v-if="record.state === 0">
        <div class="detail-btn cancel-btn" @click="handleCancel">取消提现</div>
      </div>
    </div>
    <div class="close-circle-wrapper">
      <div class="close-circle" @click="visible = false">
        <van-icon name="cross" color="#fff" size="20" />
      </div>
    </div>
  </van-popup>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useWithdraw } from './useWithdraw'

const props = defineProps({
  modelValue: Boolean,
  record: Object
})

const emit = defineEmits(['update:modelValue', 'cancel'])

const { getRecordTypeName, getRecordStatusText } = useWithdraw()

const visible = ref(false)

watch(() => props.modelValue, (val) => {
  visible.value = val
})

watch(visible, (val) => {
  emit('update:modelValue', val)
})

const handleCancel = () => {
  visible.value = false
  emit('cancel', props.record)
}
</script>

<style scoped>
.record-detail-popup {
  background: transparent !important;
  overflow: visible !important;
}
.popup-content {
  background: #fff;
  border-radius: 12px;
  overflow: hidden;
}
.popup-header {
  padding: 20px 0 15px;
  text-align: center;
  border-bottom: 1px solid #f5f5f5;
}
.popup-title {
  font-size: 18px;
  font-weight: 500;
  color: #333;
}
.detail-body {
  padding: 15px 20px;
}
.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 12px 0;
  border-bottom: 1px solid #f5f5f5;
}
.detail-row:last-child {
  border-bottom: none;
}
.detail-label {
  font-size: 14px;
  color: #999;
  flex-shrink: 0;
}
.detail-value {
  font-size: 14px;
  color: #333;
  text-align: right;
  word-break: break-all;
  max-width: 65%;
}
.detail-value.amount {
  color: #333;
  font-weight: 600;
}
.detail-value.amount.refund {
  color: #26A17B;
}
.detail-value.status-0 { color: #f0ad4e; }
.detail-value.status-1 { color: #26A17B; }
.detail-value.status-2 { color: #d9534f; }
.detail-value.status-3 { color: #999; }
.detail-value.order-no {
  font-size: 12px;
  color: #666;
}
.detail-value.address {
  font-size: 12px;
  color: #666;
}
.detail-footer {
  padding: 15px 20px 20px;
}
.detail-footer .detail-btn {
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  border-radius: 6px;
  cursor: pointer;
}
.detail-footer .cancel-btn {
  background: #fff;
  border: 1px solid #26A17B;
  color: #26A17B;
}
.close-circle-wrapper {
  margin-top: 20px;
  display: flex;
  justify-content: center;
}
.close-circle {
  width: 36px;
  height: 36px;
  border: 2px solid #fff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}
</style>
