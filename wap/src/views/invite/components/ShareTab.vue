<template>
  <div class="share-tab">
    <div class="invite-section">
      <div class="section-header">
        <span class="title">Mời Bạn Bè</span>
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
          <button class="save-qr-btn" @click="$emit('saveQrcode')">Lưu mã mời</button>
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
  </div>
</template>

<script setup>
import { handleShare } from '../useInvite'

const props = defineProps({
  inviteInfo: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['copy', 'saveQrcode'])

const onShare = () => handleShare(props.inviteInfo.inviteLink, emit)
</script>

<style scoped>
.share-tab {
  display: flex;
  flex-direction: column;
  gap: 12px;
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
  font-size: 12px;
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
</style>
