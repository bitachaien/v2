<template>
  <div class="v5-login-pwd">
    <van-nav-bar
      title="Mật khẩu đăng nhập"
      left-arrow
      @click-left="onClickLeft"
      class="custom-nav"
    />

    <div class="content">
      
      <div class="label">Mật khẩu cũ</div>
      <div class="input-container">
        <van-icon name="lock" class="prefix-icon" />
        <input 
          :type="showOldPwd ? 'text' : 'password'" 
          v-model="oldPassword" 
          placeholder="Nhập mật khẩu cũ"
          class="custom-input"
        />
        <van-icon 
          :name="showOldPwd ? 'eye-o' : 'closed-eye'" 
          class="suffix-icon" 
          @click="showOldPwd = !showOldPwd" 
        />
      </div>

      
      <div class="label mt-20">Mật khẩu mới</div>
      <div class="input-container">
        <van-icon name="lock" class="prefix-icon" />
        <input 
          :type="showPwd ? 'text' : 'password'" 
          v-model="password" 
          placeholder="Nhập mật khẩu mới"
          class="custom-input"
        />
        <van-icon 
          :name="showPwd ? 'eye-o' : 'closed-eye'" 
          class="suffix-icon" 
          @click="showPwd = !showPwd" 
        />
      </div>

      
      <div class="strength-section">
        <div class="strength-row">
          <span>Độ mạnh mật khẩu</span>
          <div class="bars">
            <div class="bar" :class="{ filled: strengthScore >= 1 }"></div>
            <div class="bar" :class="{ filled: strengthScore >= 2 }"></div>
            <div class="bar" :class="{ filled: strengthScore >= 3 }"></div>
            <div class="bar" :class="{ filled: strengthScore >= 4 }"></div>
          </div>
        </div>
        
        <div class="rules-row">
          <div class="rule-tag" :class="{ valid: isLengthValid }">
            <van-icon :name="isLengthValid ? 'checked' : 'clear'" /> 8-16 ký tự
          </div>
          <div class="rule-tag" :class="{ valid: hasUpper }">
            <van-icon :name="hasUpper ? 'checked' : 'clear'" /> Chữ hoa
          </div>
          <div class="rule-tag" :class="{ valid: hasLower }">
            <van-icon :name="hasLower ? 'checked' : 'clear'" /> Chữ thường
          </div>
          <div class="rule-tag" :class="{ valid: hasNumber }">
            <van-icon :name="hasNumber ? 'checked' : 'clear'" /> Số
          </div>
          <div class="rule-tag" :class="{ valid: hasSymbol }">
            <van-icon :name="hasSymbol ? 'checked' : 'clear'" /> Ký tự đặc biệt
          </div>
        </div>
      </div>

      
      <div class="label mt-20">Xác nhận mật khẩu mới</div>
      <div class="input-container">
        <van-icon name="lock" class="prefix-icon" />
        <input 
          :type="showConfirmPwd ? 'text' : 'password'" 
          v-model="confirmPassword" 
          placeholder="Nhập lại mật khẩu"
          class="custom-input"
        />
        <van-icon 
          :name="showConfirmPwd ? 'eye-o' : 'closed-eye'" 
          class="suffix-icon" 
          @click="showConfirmPwd = !showConfirmPwd" 
        />
      </div>
    </div>

    <div class="bottom-area">
      <van-button block color="#009688" class="submit-btn" @click="onSubmit" :loading="submitting">Xác nhận</van-button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { showToast } from 'vant'
import { securityApi } from '@/api/security'
import { logout } from '@/utils/auth'

const router = useRouter()
const oldPassword = ref('')
const password = ref('')
const confirmPassword = ref('')
const showOldPwd = ref(false)
const showPwd = ref(false)
const showConfirmPwd = ref(false)
const submitting = ref(false)

const isLengthValid = computed(() => password.value.length >= 8 && password.value.length <= 16)
const hasUpper = computed(() => /[A-Z]/.test(password.value))
const hasLower = computed(() => /[a-z]/.test(password.value))
const hasNumber = computed(() => /\d/.test(password.value))
const hasSymbol = computed(() => /[^A-Za-z0-9]/.test(password.value))

const strengthScore = computed(() => {
  let score = 0
  if (isLengthValid.value) score++
  if (hasUpper.value) score++
  if (hasLower.value) score++
  if (hasNumber.value) score++
  if (hasSymbol.value) score++
  return Math.min(score, 4)
})

const onClickLeft = () => {
  router.back()
}

const onSubmit = async () => {
  if (!oldPassword.value) {
    showToast('Vui lòng nhập mật khẩu cũ')
    return
  }
  if (!isLengthValid.value) {
    showToast('Mật khẩu mới cần 8-16 ký tự')
    return
  }
  if (password.value !== confirmPassword.value) {
    showToast('Hai lần nhập mật khẩu không khớp')
    return
  }
  
  submitting.value = true
  try {
    const res = await securityApi.changePassword({
      oldPassword: oldPassword.value,
      newPassword: password.value,
      confirmPassword: confirmPassword.value
    })
    if (res.code === 0) {
      showToast('Đổi mật khẩu thành công, vui lòng đăng nhập lại')
      setTimeout(() => {
        logout({ router, redirectUrl: '/home-new' })
      }, 1500)
    } else {
      showToast(res.message || 'Đổi mật khẩu thất bại')
    }
  } catch (e) {
    showToast(e.message || 'Đổi mật khẩu thất bại')
  } finally {
    submitting.value = false
  }
}
</script>

<style scoped>
.v5-login-pwd {
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

.label {
  font-size: 14px;
  color: #333;
  margin-bottom: 12px;
}
.mt-20 { margin-top: 24px; }

.input-container {
  background: #fff;
  border-radius: 8px;
  border: 1px solid #e0e0e0;
  display: flex;
  align-items: center;
  height: 48px;
  padding: 0 12px;
}

.prefix-icon {
  font-size: 20px;
  color: #999;
  margin-right: 10px;
}

.suffix-icon {
  font-size: 20px;
  color: #ccc;
  padding: 4px;
  cursor: pointer;
}

.custom-input {
  flex: 1;
  border: none;
  outline: none;
  font-size: 15px;
  color: #333;
}
.custom-input::placeholder { color: #ccc; }

.strength-section {
  margin-top: 16px;
}

.strength-row {
  display: flex;
  align-items: center;
  font-size: 13px;
  color: #333;
  margin-bottom: 12px;
}

.bars {
  display: flex;
  gap: 6px;
  margin-left: 12px;
  flex: 1;
  max-width: 200px;
}

.bar {
  height: 6px;
  flex: 1;
  background: #e0e0e0;
  border-radius: 3px;
  transition: background 0.3s;
}
.bar.filled { background: #009688; }

.rules-row {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

.rule-tag {
  display: flex;
  align-items: center;
  font-size: 12px;
  color: #f44336; 
  gap: 4px;
}
.rule-tag.valid {
  color: #009688;
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
