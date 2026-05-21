<template>
  <div class="v5-google-bind">
    <van-nav-bar
      title="Liên kết Google Authenticator"
      left-arrow
      @click-left="onClickLeft"
      class="custom-nav"
    />

    <div class="content">
      
      <div class="step-block">
        <div class="step-title">Bước 1: Tải Google Authenticator</div>
        <div class="step-desc">
          Tìm kiếm trong cửa hàng ứng dụng
          <span class="google-icon"><van-icon name="scan" class="colorful-icon" /> Google Authenticator</span>
          hoặc nhấp vào <a href="#" class="link" @click.prevent="openLink">link tải xuống</a>
        </div>
      </div>

      
      <div class="step-block">
        <div class="step-title">Bước 2: Quét mã QR hoặc thêm khóa thủ công</div>
        <div class="qr-container">
        <div class="qr-box">
            <canvas ref="qrCanvas" v-show="qrcode"></canvas>
            <van-loading v-if="loading" size="40" />
          </div>
          
          <div class="secret-area">
            <div v-if="!showSecret" class="show-secret-btn" @click="showSecret = true">
              Không quét được? Sao chép khóa để thêm thủ công <span class="green-text">Hiện khóa</span>
            </div>
            <div v-else class="secret-box">
              <span class="secret-text">{{ secretKey }}</span>
              <span class="copy-btn" @click="copySecret">Sao chép</span>
            </div>
          </div>
        </div>
      </div>

      
      <div class="step-block">
        <div class="step-title">Bước 3: Lấy mã 6 chữ số từ Google Authenticator</div>
        <div class="input-container">
          <van-icon name="scan" class="input-icon colorful-icon" />
          <input 
            type="tel" 
            v-model="code" 
            placeholder="Nhập mã xác minh 6 chữ số"
            class="code-input"
            maxlength="6"
          />
          <span class="paste-btn" @click="pasteCode">Dán</span>
        </div>
      </div>
    </div>

    <div class="bottom-area">
      <van-button block color="#009688" class="submit-btn" @click="onSubmit" :loading="submitting">Xác nhận</van-button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { showToast } from 'vant'
import { securityApi } from '@/api/security'
import QRCode from 'qrcode'

const router = useRouter()
const code = ref('')
const showSecret = ref(false)
const secretKey = ref('')
const qrcode = ref('')
const loading = ref(false)
const submitting = ref(false)
const qrCanvas = ref(null)

const generateQR = async (text) => {
  await nextTick()
  if (qrCanvas.value && text) {
    try {
      await QRCode.toCanvas(qrCanvas.value, text, {
        width: 160,
        margin: 1,
        color: { dark: '#333333', light: '#ffffff' }
      })
    } catch (e) {
    }
  }
}

const loadSecret = async () => {
  loading.value = true
  try {
    const res = await securityApi.getGoogleSecret()
    if (res.code === 0 && res.data) {
      secretKey.value = res.data.secret
      qrcode.value = res.data.qrcode
      generateQR(res.data.qrcode)
    } else {
      showToast(res.message || 'Lấy khóa thất bại')
    }
  } catch (e) {
    showToast(e.message || 'Lấy khóa thất bại')
  } finally {
    loading.value = false
  }
}

onMounted(() => loadSecret())

const onClickLeft = () => {
  router.back()
}

const openLink = () => {
  window.open('https://apps.apple.com/us/app/google-authenticator/id388497605', '_blank')
}

const copySecret = () => {
  navigator.clipboard.writeText(secretKey.value).then(() => {
    showToast('Sao chép thành công')
  }).catch(() => {
    showToast('Sao chép thất bại')
  })
}

const pasteCode = async () => {
  try {
    const text = await navigator.clipboard.readText()
    if (text && /^\d{6}$/.test(text)) {
      code.value = text
    } else {
      showToast('Clipboard không có mã hợp lệ')
    }
  } catch (e) {
    showToast('Dán thất bại, vui lòng nhập thủ công')
  }
}

const onSubmit = async () => {
  if (!code.value || code.value.length !== 6) {
    showToast('Vui lòng nhập mã 6 chữ số')
    return
  }
  
  submitting.value = true
  try {
    const res = await securityApi.bindGoogle(code.value)
    if (res.code === 0) {
      showToast('Liên kết thành công')
      setTimeout(() => router.back(), 1000)
    } else {
      showToast(res.message || 'Liên kết thất bại')
    }
  } catch (e) {
    showToast(e.message || 'Liên kết thất bại')
  } finally {
    submitting.value = false
  }
}
</script>

<style scoped>
.v5-google-bind {
  min-height: 100vh;
  background: #f7f8fa;
  font-family: -apple-system, BlinkMacSystemFont, "PingFang SC", "Helvetica Neue", Arial, sans-serif;
  display: flex;
  flex-direction: column;
}

.custom-nav {
  background: #fff;
}
:deep(.van-nav-bar__title) {
  font-weight: 500;
  font-size: 17px;
}
:deep(.van-icon-arrow-left) {
  color: #333;
  font-size: 20px;
}

.content {
  padding: 20px 16px;
  flex: 1;
  background: #fff;
}

.step-block {
  margin-bottom: 30px;
}

.step-title {
  font-size: 15px;
  font-weight: bold;
  color: #333;
  margin-bottom: 10px;
}

.step-desc {
  font-size: 13px;
  color: #666;
  line-height: 1.6;
}

.google-icon {
  display: inline-flex;
  align-items: center;
  font-weight: 500;
  color: #333;
}
.colorful-icon {
  color: #EA4335;
  margin-right: 4px;
  font-size: 16px;
}

.link {
  color: #009688;
  text-decoration: none;
  word-break: break-all;
}

.qr-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-top: 15px;
}

.qr-box {
  width: 160px;
  height: 160px;
  border: 1px solid #eee;
  padding: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 15px;
  background: #fff;
}

.secret-area {
  font-size: 13px;
  color: #666;
  text-align: center;
}

.green-text {
  color: #009688;
  cursor: pointer;
  margin-left: 4px;
}

.secret-box {
  display: flex;
  align-items: center;
  gap: 10px;
  background: #f5f5f5;
  padding: 8px 12px;
  border-radius: 4px;
}
.secret-text {
  font-family: monospace;
  color: #333;
  font-size: 16px;
  letter-spacing: 1px;
}
.copy-btn {
  color: #009688;
  cursor: pointer;
}

.input-container {
  display: flex;
  align-items: center;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  padding: 0 12px;
  height: 48px;
}
.input-icon {
  font-size: 20px;
  margin-right: 10px;
}
.code-input {
  flex: 1;
  border: none;
  outline: none;
  font-size: 16px;
  color: #333;
}
.paste-btn {
  color: #009688;
  font-size: 14px;
  cursor: pointer;
  padding-left: 10px;
  border-left: 1px solid #eee;
}

.bottom-area {
  padding: 20px 16px;
  background: #fff;
  border-top: 1px solid #f5f5f5;
}

.submit-btn {
  height: 44px;
  font-size: 16px;
  border-radius: 6px;
}
</style>
