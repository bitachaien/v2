<template>
  <div class="v5-phone-bind">
    <van-nav-bar
      title="Số điện thoại"
      left-arrow
      @click-left="onClickLeft"
      class="custom-nav"
    />

    <div class="content">
      <div class="page-label">Liên kết số điện thoại</div>
      
      <div class="phone-input-container">
        <div class="country-selector" @click="showCountryPicker = true">
          <span class="current-flag">{{ currentCountry.emoji }}</span>
          <span class="current-code">+{{ currentCountry.code }}</span>
          <van-icon name="arrow-up" size="12" color="#999" class="arrow-icon" />
        </div>
        <div class="divider"></div>
        <input 
          type="tel" 
          v-model="phone" 
          placeholder="Nhập số điện thoại"
          class="phone-input"
        />
      </div>

      
      <div v-if="codeSent" class="code-input-container">
        <van-icon name="shield-o" size="20" color="#999" class="prefix-icon" />
        <input 
          type="text" 
          v-model="code" 
          placeholder="Nhập mã xác minh"
          class="code-input"
          maxlength="6"
        />
        <span class="countdown" v-if="countdown > 0">{{ countdown }}s</span>
        <span class="resend" v-else @click="sendCode">Gửi lại</span>
      </div>
    </div>

    <div class="bottom-area">
      <van-button v-if="!codeSent" block color="#009688" class="submit-btn" @click="sendCode" :loading="sending">Gửi mã xác minh</van-button>
      <van-button v-else block color="#009688" class="submit-btn" @click="onSubmit" :loading="submitting">Xác nhận liên kết</van-button>
    </div>

    
    <van-popup 
      v-model:show="showCountryPicker" 
      position="bottom" 
      round
      :style="{ height: '60%' }"
      class="country-popup"
    >
      <div class="popup-content">
        <div 
          v-for="(item, index) in countryList" 
          :key="index" 
          class="country-item"
          :class="{ active: currentCountry.code === item.code }"
          @click="selectCountry(item)"
        >
          <span class="flag">{{ item.emoji }}</span>
          <span class="name">{{ item.name }}</span>
          <span class="code">(+{{ item.code }})</span>
        </div>
      </div>
    </van-popup>
  </div>
</template>

<script setup>
import { ref, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { showToast } from 'vant'
import { securityApi } from '@/api/security'

const router = useRouter()
const phone = ref('')
const code = ref('')
const showCountryPicker = ref(false)
const codeSent = ref(false)
const countdown = ref(0)
const sending = ref(false)
const submitting = ref(false)
let timer = null

const countryList = [
  { name: 'Trung Quốc', code: '86', emoji: '🇨🇳' },
  { name: 'San Marino', code: '378', emoji: '🇸🇲' },
  { name: 'Qatar', code: '974', emoji: '🇶🇦' },
  { name: 'Saint Pierre và Miquelon', code: '508', emoji: '🇵🇲' },
  { name: 'Canada', code: '1', emoji: '🇨🇦' },
  { name: 'Brazil', code: '55', emoji: '🇧🇷' },
  { name: 'Palestine', code: '970', emoji: '🇵🇸' },
  { name: 'Mỹ', code: '1', emoji: '🇺🇸' },
  { name: 'Anh', code: '44', emoji: '🇬🇧' },
  { name: 'Nhật Bản', code: '81', emoji: '🇯🇵' },
]

const currentCountry = ref(countryList[0])

const onClickLeft = () => {
  router.back()
}

const selectCountry = (item) => {
  currentCountry.value = item
  showCountryPicker.value = false
}

const startCountdown = () => {
  countdown.value = 60
  timer = setInterval(() => {
    countdown.value--
    if (countdown.value <= 0) clearInterval(timer)
  }, 1000)
}

const sendCode = async () => {
  if (!phone.value) {
    showToast('Vui lòng nhập số điện thoại')
    return
  }
  if (phone.value.length < 5 || phone.value.length > 15 || !/^\d+$/.test(phone.value)) {
    showToast('Vui lòng nhập số điện thoại hợp lệ')
    return
  }
  
  sending.value = true
  try {
    const res = await securityApi.sendPhoneCode(phone.value)
    if (res.code === 0) {
      showToast('Đã gửi mã xác minh')
      codeSent.value = true
      startCountdown()
    } else {
      showToast(res.message || 'Gửi thất bại')
    }
  } catch (e) {
    showToast(e.message || 'Gửi thất bại')
  } finally {
    sending.value = false
  }
}

const onSubmit = async () => {
  if (!code.value || code.value.length !== 6) {
    showToast('Vui lòng nhập mã 6 chữ số')
    return
  }
  
  submitting.value = true
  try {
    const res = await securityApi.bindPhone({ phone: phone.value, code: code.value })
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

onUnmounted(() => { if (timer) clearInterval(timer) })
</script>

<style scoped>
.v5-phone-bind {
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
}

.page-label {
  font-size: 14px;
  color: #333;
  margin-bottom: 12px;
}

.phone-input-container {
  background: #fff;
  border-radius: 8px;
  border: 1px solid #e0e0e0;
  display: flex;
  align-items: center;
  height: 48px;
  padding: 0 12px;
}

.country-selector {
  display: flex;
  align-items: center;
  cursor: pointer;
  gap: 6px;
  padding-right: 10px;
}

.current-flag { font-size: 20px; }
.current-code { font-size: 15px; color: #333; }
.arrow-icon { transition: transform 0.3s; }

.divider {
  width: 1px;
  height: 20px;
  background: #eee;
  margin-right: 12px;
}

.phone-input {
  flex: 1;
  border: none;
  outline: none;
  font-size: 15px;
  color: #333;
}
.phone-input::placeholder { color: #ccc; }

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

.code-input-container {
  background: #fff;
  border-radius: 8px;
  border: 1px solid #e0e0e0;
  display: flex;
  align-items: center;
  height: 48px;
  padding: 0 12px;
  margin-top: 12px;
}
.prefix-icon { margin-right: 10px; }
.code-input {
  flex: 1;
  border: none;
  outline: none;
  font-size: 15px;
  color: #333;
}
.code-input::placeholder { color: #ccc; }
.countdown { color: #999; font-size: 14px; }
.resend { color: #009688; font-size: 14px; cursor: pointer; }

.country-item {
  display: flex;
  align-items: center;
  padding: 12px 20px;
  font-size: 15px;
  color: #333;
  border-bottom: 1px solid #f9f9f9;
  cursor: pointer;
}
.country-item:active { background: #f5f5f5; }
.country-item.active { color: #009688; }

.country-item .flag { font-size: 24px; margin-right: 12px; }
.country-item .name { margin-right: 8px; }
.country-item .code { color: #999; }
</style>
