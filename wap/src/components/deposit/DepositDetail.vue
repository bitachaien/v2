<template>
  <van-popup 
    v-model:show="visible" 
    position="center" 
    round 
    :style="{ width: '90%', maxHeight: '80%' }"
    class="detail-popup"
  >
    <div class="view-detail">
      <div class="detail-header">
        <div class="header-left" @click="visible = false">
          <van-icon name="arrow-left" size="20" />
        </div>
        <div class="header-title">存款详情</div>
        <div class="header-right">
          <img src="/assets/img/style_3_icon_top_kf.svg" class="header-icon" @click="toService" />
        </div>
      </div>

      <div class="detail-body" v-if="record">
        <div class="status-section">
          <div class="status-icon" :class="getStatusClass(record.status)">
            <van-icon :name="getStatusIcon(record.status)" size="40" />
          </div>
          <div class="status-text" :class="getStatusClass(record.status)">
            {{ getStatusText(record.status) }}
          </div>
        </div>

        <div class="amount-display">
          <span class="amount-value">{{ record.amount }}</span>
          <span class="amount-unit">USDT</span>
          <van-icon name="description-o" class="copy-icon" @click="copyAmount" />
        </div>

        <div class="divider-dashed"></div>

        <div class="detail-info-list">
          <div class="info-row">
            <span class="info-label">交易类型</span>
            <span class="info-value">充值</span>
          </div>
          <div class="info-row">
            <span class="info-label">存款方式</span>
            <span class="info-value">
              <div class="usdt-icon-tiny">₮</div>
              USDT
            </span>
          </div>
          <div class="info-row">
            <span class="info-label">存款通道</span>
            <span class="info-value">USDT—{{ record.chain || 'TRC-20' }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">创建时间</span>
            <span class="info-value">{{ record.createTime }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">订单号码</span>
            <span class="info-value order-no">
              {{ record.orderNo }}
              <van-icon name="description-o" class="copy-icon-small" @click="copyOrderNo" />
            </span>
          </div>
        </div>
      </div>

      <div class="detail-footer" v-if="record && (record.status === 'pending' || record.status === 'confirming')">
        <div class="continue-btn" @click="$emit('continue-pay', record)">继续支付</div>
      </div>
    </div>
  </van-popup>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { showToast } from 'vant'

const props = defineProps({
  show: { type: Boolean, default: false },
  record: { type: Object, default: null }
})

const emit = defineEmits(['update:show', 'continue-pay'])
const router = useRouter()

const visible = computed({
  get: () => props.show,
  set: (val) => emit('update:show', val)
})

const toService = () => router.push('/service')

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

const getStatusIcon = (status) => {
  const map = {
    pending: 'clock-o',
    confirming: 'clock-o',
    success: 'checked',
    failed: 'cross',
    timeout: 'clock-o',
    cancelled: 'cross'
  }
  return map[status] || 'info-o'
}

const copyAmount = async () => {
  if (props.record) {
    try {
      await navigator.clipboard.writeText(String(props.record.amount))
      showToast('金额已复制')
    } catch (e) {
      showToast('复制失败')
    }
  }
}

const copyOrderNo = async () => {
  if (props.record) {
    try {
      await navigator.clipboard.writeText(props.record.orderNo)
      showToast('订单号已复制')
    } catch (e) {
      showToast('复制失败')
    }
  }
}
</script>

<style scoped>
.detail-popup {
  overflow: visible;
}

.view-detail {
  background: #fff;
  border-radius: 12px;
  height: 100%;
  display: flex;
  flex-direction: column;
}

.detail-header {
  height: 50px;
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 15px;
  border-radius: 12px 12px 0 0;
}

.header-left,
.header-right {
  width: 60px;
  display: flex;
  align-items: center;
}

.header-right {
  justify-content: flex-end;
}

.header-title {
  font-size: 17px;
  font-weight: 600;
  color: #333;
}

.header-icon {
  width: 22px;
  height: 22px;
}

.detail-body {
  flex: 1;
  padding: 30px 20px;
  overflow-y: auto;
}

.status-section {
  text-align: center;
  margin-bottom: 20px;
}

.status-icon {
  width: 70px;
  height: 70px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 12px;
}

.status-icon.status-pending {
  background: rgba(255, 149, 0, 0.1);
  color: #ff9500;
}

.status-icon.status-success {
  background: rgba(4, 190, 2, 0.1);
  color: #04BE02;
}

.status-icon.status-failed,
.status-icon.status-cancelled,
.status-icon.status-timeout {
  background: rgba(238, 10, 36, 0.1);
  color: #ee0a24;
}

.status-text {
  font-size: 16px;
  font-weight: 500;
}

.status-text.status-pending { color: #ff9500; }
.status-text.status-success { color: #04BE02; }
.status-text.status-failed,
.status-text.status-cancelled,
.status-text.status-timeout { color: #ee0a24; }

.amount-display {
  text-align: center;
  margin-bottom: 25px;
  display: flex;
  align-items: baseline;
  justify-content: center;
  gap: 5px;
}

.amount-value {
  font-size: 32px;
  font-weight: 700;
  color: #333;
}

.amount-unit {
  font-size: 16px;
  color: #666;
}

.copy-icon {
  color: #26A17B;
  margin-left: 8px;
  cursor: pointer;
}

.divider-dashed {
  border-top: 1px dashed #e8e8e8;
  margin-bottom: 20px;
}

.detail-info-list {
  background: #f9f9f9;
  border-radius: 10px;
  padding: 15px;
}

.info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 0;
  border-bottom: 1px solid #eee;
}

.info-row:last-child {
  border-bottom: none;
}

.info-label {
  font-size: 14px;
  color: #999;
}

.info-value {
  font-size: 14px;
  color: #333;
  display: flex;
  align-items: center;
  gap: 5px;
}

.usdt-icon-tiny {
  width: 18px;
  height: 18px;
  background: #26A17B;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 10px;
  font-weight: bold;
}

.order-no {
  font-family: monospace;
}

.copy-icon-small {
  color: #26A17B;
  font-size: 14px;
  cursor: pointer;
}

.detail-footer {
  padding: 15px 20px 30px;
}

.continue-btn {
  height: 48px;
  background: #26A17B;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
}
</style>
