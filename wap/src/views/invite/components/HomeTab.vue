<template>
  <div class="home-tab">
    <div class="agent-card">
      <div class="agent-row">
        <div class="agent-item">
          <span class="label">Tài khoản đại lý</span>
          <span class="value">{{ agentInfo.agentId }}</span>
        </div>
        <div class="agent-item">
          <span class="label">Chế độ đại lý</span>
          <span class="value mode-link">{{ agentInfo.agentMode }}</span>
        </div>
      </div>
      <div class="agent-row">
        <div class="agent-item">
          <span class="label">Bội số kiểm tra</span>
          <span class="value">{{ agentInfo.auditMultiple }}</span>
        </div>
        <div class="agent-item">
          <span class="label">Ngày thanh toán</span>
          <span class="value">{{ agentInfo.settlementDate }}</span>
        </div>
      </div>
    </div>

    <div class="invite-section">
      <div class="section-header">
        <span class="title">Mời bạn bè</span>
        <span class="invite-code">
          Mã mời của tôi <em>{{ inviteInfo.inviteCode }}</em>
          <van-icon name="description-o" class="copy-icon" @click="$emit('copy', inviteInfo.inviteCode)" />
        </span>
      </div>

      <div class="invite-content">
        <div class="qrcode-area">
          <div class="qrcode-box">
            <img v-if="inviteInfo.qrcodeUrl" :src="inviteInfo.qrcodeUrl" alt="Mã QR mời" />
            <div v-else class="qr-placeholder">
              <van-icon name="qr" />
            </div>
          </div>
          <button class="save-qr-btn" @click="$emit('copy', inviteInfo.inviteCode)">Lưu mã mời</button>
        </div>

        <div class="link-area">
          <div class="link-label">Link mời</div>
          <div class="link-input">
            <van-field 
              v-model="inviteInfo.inviteLink" 
              readonly
              :border="false"
            >
              <template #right-icon>
                <van-icon name="description-o" @click="$emit('copy', inviteInfo.inviteLink)" />
              </template>
            </van-field>
          </div>

          <div class="share-buttons">
            <div class="share-btn" @click="onShare">
              <div class="share-icon system-icon">
                <van-icon name="share-o" />
              </div>
              <span>Chia sẻ</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="stats-row">
      <div class="stat-item">
        <span class="label">Tổng thu nhập</span>
        <span class="value orange">{{ formatNumber(overview.totalEarned) }}</span>
      </div>
      <div class="stat-item">
        <span class="label">Tổng số người mời</span>
        <span class="value">{{ overview.totalInvited }}</span>
      </div>
    </div>

    <div class="commission-card">
      <div class="commission-header">
        <span class="title">Hoa hồng của tôi</span>
        <span class="countdown">(Thanh toán tiếp theo {{ overview.nextSettlement }})</span>
        <van-icon name="arrow" class="arrow-icon" />
      </div>
      <div class="commission-body">
        <div class="commission-grid">
          <div class="commission-item">
            <span class="label">Thành tích trực thuộc hôm qua</span>
            <span class="value">{{ formatNumber(overview.yesterdayPerformance) }}</span>
          </div>
          <div class="commission-item">
            <span class="label">Tổng hoa hồng</span>
            <span class="value">{{ formatNumber(overview.totalCommission) }}</span>
          </div>
          <div class="commission-item">
            <span class="label">Đã nhận</span>
            <span class="value">{{ formatNumber(overview.claimed) }}</span>
          </div>
          <div class="commission-item">
            <span class="label">Chờ nhận</span>
            <span class="value orange">{{ formatNumber(overview.pending) }}</span>
          </div>
        </div>
        <button 
          class="claim-btn" 
          :class="{ disabled: !overview.pending || overview.pending <= 0 }"
          @click="$emit('claim')"
        >Nhận</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { handleShare, formatNumber } from '../useInvite'

const props = defineProps({
  agentInfo: {
    type: Object,
    default: () => ({})
  },
  inviteInfo: {
    type: Object,
    default: () => ({})
  },
  overview: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['copy', 'claim'])

const onShare = () => handleShare(props.inviteInfo.inviteLink, emit)
</script>

<style scoped>
.home-tab {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.agent-card {
  background: #FFFFFF;
  border-radius: 12px;
  padding: 15px;
}

.agent-row {
  display: flex;
  margin-bottom: 12px;
}

.agent-row:last-child {
  margin-bottom: 0;
}

.agent-item {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 8px;
}

.agent-item .label {
  font-size: 12px;
  color: #666;
  white-space: nowrap;
}

.agent-item .value {
  font-size: 14px;
  color: #333;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 4px;
}

.agent-item .value.mode-link {
  color: #26A17B;
}

.invite-section {
  background: #fff;
  border-radius: 12px;
  padding: 15px;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
}

.section-header .title {
  font-size: 15px;
  font-weight: 600;
  color: #333;
}

.invite-code {
  font-size: 12px;
  color: #666;
}

.invite-code em {
  font-style: normal;
  color: #26A17B;
  font-weight: 600;
}

.copy-icon {
  color: #26A17B;
  margin-left: 4px;
}

.invite-content {
  display: flex;
  gap: 15px;
}

.qrcode-area {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
}

.qrcode-box {
  width: 100px;
  height: 100px;
  border: 1px solid #eee;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.qrcode-box img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.qr-placeholder {
  font-size: 50px;
  color: #ccc;
}

.save-qr-btn {
  width: 100%;
  padding: 8px 12px;
  background: #26A17B;
  border: none;
  border-radius: 4px;
  color: #fff;
  font-size: 12px;
}

.link-area {
  flex: 1;
}

.link-label {
  font-size: 12px;
  color: #999;
  margin-bottom: 8px;
}

.link-input {
  background: #f5f5f5;
  border-radius: 8px;
  margin-bottom: 15px;
}

.link-input :deep(.van-field) {
  background: transparent;
}

.link-input :deep(.van-field__control) {
  font-size: 12px;
  color: #333;
}

.link-input :deep(.van-icon) {
  color: #26A17B;
}

.share-buttons {
  display: flex;
  justify-content: space-between;
}

.share-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  cursor: pointer;
}

.share-icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.icon-text {
  color: #fff;
  font-size: 11px;
  font-weight: bold;
}

.system-icon {
  background: #f0f0f0;
  font-size: 20px;
  color: #666;
}

.line-icon {
  background: #00b900;
}

.facebook-icon {
  background: #1877f2;
  font-size: 20px;
}

.whatsapp-icon {
  background: #25d366;
  color: #fff;
  font-size: 20px;
}

.telegram-icon {
  background: #0088cc;
  color: #fff;
  font-size: 20px;
}

.share-btn span {
  font-size: 11px;
  color: #666;
}

.stats-row {
  display: flex;
  background: #fff;
  border-radius: 12px;
  padding: 0 15px;
  height: 43px;
  align-items: center;
}

.stat-item {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 8px;
}

.stat-item .label {
  font-size: 12px;
  color: #999;
}

.stat-item .value {
  font-size: 16px;
  font-weight: 600;
  color: #333;
}

.stat-item .value.orange {
  color: #FFAA09;
}

.commission-card {
  background: #fff;
  border-radius: 12px;
  padding: 10px 15px;
}

.commission-header {
  display: flex;
  align-items: center;
  margin-bottom: 10px;
}

.commission-header .title {
  font-size: 15px;
  font-weight: 600;
  color: #333;
}

.commission-header .countdown {
  font-size: 12px;
  color: #999;
  margin-left: 8px;
  flex: 1;
}

.arrow-icon {
  color: #999;
}

.commission-body {
  display: flex;
  align-items: flex-end;
  gap: 12px;
}

.commission-grid {
  flex: 1;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px 16px;
}

.commission-item {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 8px;
}

.commission-item .label {
  font-size: 12px;
  color: #999;
}

.commission-item .value {
  font-size: 14px;
  font-weight: 500;
  color: #333;
}

.commission-item .value.orange {
  color: #FFAA09;
}

.claim-btn {
  width: 68px;
  height: 28px;
  background: #26A17B;
  border: none;
  border-radius: 4px;
  color: #fff;
  font-size: 13px;
  font-weight: 500;
  flex-shrink: 0;
}

.claim-btn.disabled {
  background: #ccc;
  cursor: not-allowed;
}
</style>
