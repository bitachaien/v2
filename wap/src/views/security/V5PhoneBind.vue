<template>
  <div class="v5-phone-bind">
    <van-nav-bar
      title="手机"
      left-arrow
      @click-left="onClickLeft"
      class="custom-nav"
    />

    <div class="content">
      <div class="page-label">绑定手机</div>
      
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
          placeholder="请输入手机号" 
          class="phone-input"
        />
      </div>

      
      <div v-if="codeSent" class="code-input-container">
        <van-icon name="shield-o" size="20" color="#999" class="prefix-icon" />
        <input 
          type="text" 
          v-model="code" 
          placeholder="请输入验证码" 
          class="code-input"
          maxlength="6"
        />
        <span class="countdown" v-if="countdown > 0">{{ countdown }}s</span>
        <span class="resend" v-else @click="sendCode">重新发送</span>
      </div>
    </div>

    <div class="bottom-area">
      <van-button v-if="!codeSent" block color="#009688" class="submit-btn" @click="sendCode" :loading="sending">发送验证码</van-button>
      <van-button v-else block color="#009688" class="submit-btn" @click="onSubmit" :loading="submitting">确定绑定</van-button>
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
  { name: '中国', code: '86', emoji: '🇨🇳' },
  { name: '圣马力诺', code: '378', emoji: '🇸🇲' },
  { name: '卡塔尔', code: '974', emoji: '🇶🇦' },
  { name: '圣彼埃尔和密克隆岛', code: '508', emoji: '🇵🇲' },
  { name: '加拿大', code: '1', emoji: '🇨🇦' },
  { name: '巴西', code: '55', emoji: '🇧🇷' },
  { name: '巴勒斯坦', code: '970', emoji: '🇵🇸' },
  { name: '美国', code: '1', emoji: '🇺🇸' },
  { name: '英国', code: '44', emoji: '🇬🇧' },
  { name: '日本', code: '81', emoji: '🇯🇵' },
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
    showToast('请输入手机号')
    return
  }
  if (phone.value.length < 5 || phone.value.length > 15 || !/^\d+$/.test(phone.value)) {
    showToast('请输入正确的手机号')
    return
  }
  
  sending.value = true
  try {
    const res = await securityApi.sendPhoneCode(phone.value)
    if (res.code === 0) {
      showToast('验证码已发送')
      codeSent.value = true
      startCountdown()
    } else {
      showToast(res.message || '发送失败')
    }
  } catch (e) {
    showToast(e.message || '发送失败')
  } finally {
    sending.value = false
  }
}

const onSubmit = async () => {
  if (!code.value || code.value.length !== 6) {
    showToast('请输入6位验证码')
    return
  }
  
  submitting.value = true
  try {
    const res = await securityApi.bindPhone({ phone: phone.value, code: code.value })
    if (res.code === 0) {
      showToast('绑定成功')
      setTimeout(() => router.back(), 1000)
    } else {
      showToast(res.message || '绑定失败')
    }
  } catch (e) {
    showToast(e.message || '绑定失败')
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
