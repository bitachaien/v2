<template>
  <div class="v5-email-bind">
    <van-nav-bar
      title="邮箱"
      left-arrow
      @click-left="onClickLeft"
      class="custom-nav"
    />

    <div class="content">
      <div class="page-label">绑定邮箱</div>
      
      <div class="email-input-container">
        <van-icon name="envelop-o" size="20" color="#999" class="prefix-icon" />
        <input 
          type="text" 
          v-model="email" 
          placeholder="请输入邮箱" 
          class="email-input"
          @input="onInput"
          @focus="showSuggestions = true"
          @blur="onBlur"
        />
        <van-icon 
          v-if="email" 
          name="clear" 
          size="16" 
          color="#ccc" 
          class="clear-icon" 
          @click="clearEmail"
        />

        
        <div v-if="showSuggestions && suggestions.length > 0" class="suggestion-list">
          <div 
            v-for="(item, index) in suggestions" 
            :key="index" 
            class="suggestion-item"
            @mousedown="selectSuggestion(item)"
          >
            <span class="item-text">{{ item }}</span>
          </div>
        </div>
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
  </div>
</template>

<script setup>
import { ref, computed, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { showToast } from 'vant'
import { securityApi } from '@/api/security'

const router = useRouter()
const email = ref('')
const code = ref('')
const showSuggestions = ref(false)
const codeSent = ref(false)
const countdown = ref(0)
const sending = ref(false)
const submitting = ref(false)
let timer = null

const commonSuffixes = ['@gmail.com', '@qq.com', '@163.com', '@126.com', '@sina.com', '@outlook.com']

const suggestions = computed(() => {
  if (!email.value) return []
  
  const match = email.value.match(/^(.*)(@.*)$/)
  let prefix = email.value
  let suffix = ''
  
  if (match) {
    prefix = match[1]
    suffix = match[2]
  } else if (email.value.includes('@')) {
    const parts = email.value.split('@')
    prefix = parts[0]
    suffix = '@' + (parts[1] || '')
  }

  const filteredSuffixes = commonSuffixes.filter(s => s.startsWith(suffix))
  return filteredSuffixes.map(s => prefix + s)
})

const onClickLeft = () => {
  router.back()
}

const onInput = () => {
  showSuggestions.value = true
}

const onBlur = () => {
  setTimeout(() => {
    showSuggestions.value = false
  }, 200)
}

const selectSuggestion = (val) => {
  email.value = val
  showSuggestions.value = false
}

const clearEmail = () => {
  email.value = ''
  showSuggestions.value = false
}

const startCountdown = () => {
  countdown.value = 60
  timer = setInterval(() => {
    countdown.value--
    if (countdown.value <= 0) {
      clearInterval(timer)
    }
  }, 1000)
}

const sendCode = async () => {
  if (!email.value) {
    showToast('请输入邮箱')
    return
  }
  if (!/.+@.+\..+/.test(email.value)) {
    showToast('邮箱格式不正确')
    return
  }
  
  sending.value = true
  try {
    const res = await securityApi.sendEmailCode(email.value)
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
    const res = await securityApi.bindEmail({ email: email.value, code: code.value })
    if (res.code === 0) {
      showToast('绑定成功')
      setTimeout(() => {
        router.back()
      }, 1000)
    } else {
      showToast(res.message || '绑定失败')
    }
  } catch (e) {
    showToast(e.message || '绑定失败')
  } finally {
    submitting.value = false
  }
}

onUnmounted(() => {
  if (timer) clearInterval(timer)
})
</script>

<style scoped>
.v5-email-bind {
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

.email-input-container {
  background: #fff;
  border-radius: 8px;
  border: 1px solid #e0e0e0;
  display: flex;
  align-items: center;
  height: 48px;
  padding: 0 12px;
  position: relative; 
}

.prefix-icon {
  margin-right: 10px;
}

.email-input {
  flex: 1;
  border: none;
  outline: none;
  font-size: 15px;
  color: #333;
}
.email-input::placeholder { color: #ccc; }

.clear-icon {
  cursor: pointer;
  margin-left: 8px;
}

.suggestion-list {
  position: absolute;
  top: 100%; 
  left: 0;
  width: 100%;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  margin-top: 8px;
  z-index: 10;
  overflow: hidden;
}

.suggestion-item {
  padding: 12px 16px;
  font-size: 14px;
  color: #666;
  cursor: pointer;
  border-bottom: 1px solid #f9f9f9;
}
.suggestion-item:last-child { border-bottom: none; }
.suggestion-item:hover { background: #f5f5f5; }

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

.code-input {
  flex: 1;
  border: none;
  outline: none;
  font-size: 15px;
  color: #333;
}
.code-input::placeholder { color: #ccc; }

.countdown {
  color: #999;
  font-size: 14px;
}

.resend {
  color: #009688;
  font-size: 14px;
  cursor: pointer;
}
</style>
