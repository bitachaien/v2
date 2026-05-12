<template>
  <div class="create-account-tab">
    <div class="form-card">
      <div class="form-item">
        <div class="input-box">
          <van-icon name="user-o" class="input-icon" />
          <van-field 
            v-model="form.username" 
            placeholder="*请输入账号"
            :border="false"
            maxlength="20"
          />
        </div>
      </div>

      <div class="form-item">
        <div class="input-box">
          <van-icon name="lock" class="input-icon" />
          <van-field 
            v-model="form.password" 
            :type="showPassword ? 'text' : 'password'"
            placeholder="*请输入密码"
            :border="false"
            maxlength="20"
          >
            <template #right-icon>
              <van-icon 
                :name="showPassword ? 'eye-o' : 'closed-eye'" 
                @click="showPassword = !showPassword"
              />
            </template>
          </van-field>
        </div>
      </div>

      <div class="password-strength">
        <span class="label">密码强度</span>
        <div class="strength-bars">
          <div 
            v-for="i in 5" 
            :key="i" 
            class="bar"
            :class="{ active: passwordStrength >= i }"
          ></div>
        </div>
        <span class="strength-text" :class="strengthClass">{{ strengthRequirement }}</span>
      </div>

      <div class="password-requirements">
        <div class="req-item" :class="{ valid: hasUpperCase }">
          <van-icon :name="hasUpperCase ? 'success' : 'cross'" />
          <span>大写字母</span>
        </div>
        <div class="req-item" :class="{ valid: hasLowerCase }">
          <van-icon :name="hasLowerCase ? 'success' : 'cross'" />
          <span>小写字母</span>
        </div>
        <div class="req-item" :class="{ valid: hasNumber }">
          <van-icon :name="hasNumber ? 'success' : 'cross'" />
          <span>数字</span>
        </div>
        <div class="req-item" :class="{ valid: hasSymbol }">
          <van-icon :name="hasSymbol ? 'success' : 'cross'" />
          <span>符号</span>
        </div>
        <div class="req-item" :class="{ valid: hasValidLength }">
          <van-icon :name="hasValidLength ? 'success' : 'cross'" />
          <span>8-16位</span>
        </div>
      </div>
    </div>

    <button 
      class="submit-btn" 
      :disabled="!canSubmit"
      @click="handleSubmit"
    >
      提交开户
    </button>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { showToast, showSuccessToast, showLoadingToast, closeToast } from 'vant'
import { agentApi } from '@/api/agent'

const form = reactive({
  username: '',
  password: ''
})

const showPassword = ref(false)

const hasUpperCase = computed(() => /[A-Z]/.test(form.password))
const hasLowerCase = computed(() => /[a-z]/.test(form.password))
const hasNumber = computed(() => /[0-9]/.test(form.password))
const hasSymbol = computed(() => /[!@#$%^&*(),.?":{}|<>]/.test(form.password))
const hasValidLength = computed(() => form.password.length >= 8 && form.password.length <= 16)

const passwordStrength = computed(() => {
  let strength = 0
  if (hasUpperCase.value) strength++
  if (hasLowerCase.value) strength++
  if (hasNumber.value) strength++
  if (hasSymbol.value) strength++
  if (hasValidLength.value) strength++
  return strength
})

const strengthClass = computed(() => {
  if (passwordStrength.value <= 2) return 'weak'
  if (passwordStrength.value <= 4) return 'medium'
  return 'strong'
})

const strengthRequirement = computed(() => {
  if (passwordStrength.value === 0) return '8-16位'
  if (passwordStrength.value <= 2) return '弱'
  if (passwordStrength.value <= 4) return '中'
  return '强'
})

const canSubmit = computed(() => {
  return form.username.length >= 4 && 
         hasValidLength.value &&
         (hasUpperCase.value || hasLowerCase.value) &&
         hasNumber.value
})

const handleSubmit = async () => {
  if (!form.username) {
    showToast('请输入账号')
    return
  }
  if (form.username.length < 4) {
    showToast('账号至少4位')
    return
  }
  if (!hasValidLength.value) {
    showToast('密码长度需8-16位')
    return
  }
  if (!hasUpperCase.value && !hasLowerCase.value) {
    showToast('密码需包含字母')
    return
  }
  if (!hasNumber.value) {
    showToast('密码需包含数字')
    return
  }

  showLoadingToast({
    message: '提交中...',
    forbidClick: true
  })

  try {
    const res = await agentApi.createSubAccount({
      username: form.username,
      password: form.password
    })

    closeToast()

    if (res.code === 0) {
      showSuccessToast('开户成功')
      form.username = ''
      form.password = ''
    } else {
      showToast(res.msg || '开户失败')
    }
  } catch (e) {
    closeToast()
    showToast('开户失败')
  }
}
</script>

<style scoped>
.create-account-tab {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-card {
  background: #fff;
  border-radius: 12px;
  padding: 20px;
}

.form-item {
  margin-bottom: 15px;
}

.input-box {
  display: flex;
  align-items: center;
  background: #fff;
  border: 1px solid #e0e0e0;
  border-radius: 25px;
  padding: 0 15px;
}

.input-icon {
  font-size: 18px;
  color: #999;
  margin-right: 10px;
}

.input-box :deep(.van-field) {
  flex: 1;
  background: transparent;
  padding: 12px 0;
}

.input-box :deep(.van-field__control) {
  color: #333;
}

.input-box :deep(.van-field__control::placeholder) {
  color: #999;
}

.input-box :deep(.van-icon) {
  color: #26A17B;
  font-size: 18px;
}

.password-strength {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 15px;
  flex-wrap: wrap;
}

.password-strength .label {
  font-size: 13px;
  color: #666;
}

.strength-bars {
  display: flex;
  gap: 4px;
}

.bar {
  width: 30px;
  height: 4px;
  background: #e0e0e0;
  border-radius: 2px;
  transition: all 0.3s;
}

.bar.active {
  background: #26A17B;
}

.strength-text {
  font-size: 12px;
  margin-left: auto;
}

.strength-text.weak {
  color: #f44336;
}

.strength-text.medium {
  color: #ff9800;
}

.strength-text.strong {
  color: #26A17B;
}

.password-requirements {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.req-item {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  color: #f44336;
}

.req-item .van-icon {
  font-size: 14px;
}

.req-item.valid {
  color: #26A17B;
}

.submit-btn {
  width: 100%;
  height: 50px;
  background: #26A17B;
  border: none;
  border-radius: 25px;
  color: #fff;
  font-size: 16px;
  font-weight: 600;
  transition: all 0.3s;
}

.submit-btn:disabled {
  background: #ccc;
  color: #999;
}

.submit-btn:active:not(:disabled) {
  transform: scale(0.98);
  opacity: 0.9;
}
</style>
