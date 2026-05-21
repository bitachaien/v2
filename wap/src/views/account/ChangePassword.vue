<template>
  <div class="change-password-page">
    
    <van-nav-bar
      :title="pageTitle"
      left-arrow
      @click-left="goBack"
      class="nav-bar"
    />

    
    <div class="form-container">
      <van-form @submit="onSubmit">
        
        <van-field
          v-if="!isFirstSet"
          v-model="form.oldPassword"
          type="password"
          label="Mật khẩu cũ"
          placeholder="Vui lòng nhập mật khẩu cũ"
          required
          :rules="[{ required: true, message: 'Vui lòng nhập mật khẩu cũ' }]"
        />

        
        <van-field
          v-model="form.newPassword"
          type="password"
          :label="isFirstSet ? 'Đặt mật khẩu' : 'Mật khẩu mới'"
          :placeholder="isFirstSet ? 'Vui lòng đặt mật khẩu' : 'Vui lòng nhập mật khẩu mới'"
          required
          :rules="passwordRules"
        />

        
        <van-field
          v-model="form.confirmPassword"
          type="password"
          label="Xác nhận mật khẩu"
          placeholder="Vui lòng nhập lại mật khẩu"
          required
          :rules="[
            { required: true, message: 'Vui lòng nhập lại mật khẩu' },
            { validator: validateConfirm, message: 'Hai mật khẩu không khớp' }
          ]"
        />

        
        <div class="tips">
          <div class="tips-title">Quy tắc mật khẩu:</div>
          <div class="tips-item" v-if="type === 'login'">
            • Độ dài 6-20 ký tự, hỗ trợ chữ cái, số, ký tự đặc biệt
          </div>
          <div class="tips-item" v-else>
            • 6 chữ số
          </div>
        </div>

        
        <div class="submit-button">
          <van-button block type="primary" native-type="submit">
            {{ isFirstSet ? 'Xác nhận đặt' : 'Xác nhận thay đổi' }}
          </van-button>
        </div>
      </van-form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { showToast, showLoadingToast, closeToast } from 'vant'
import { withdrawApi } from '@/api/withdraw'

const router = useRouter()
const route = useRoute()

const type = ref(route.query.type || 'login')

const isFirstSet = ref(route.query.firstSet === 'true')

const form = ref({
  oldPassword: '',
  newPassword: '',
  confirmPassword: ''
})

const pageTitle = computed(() => {
  if (isFirstSet.value) {
    return type.value === 'login' ? 'Đặt mật khẩu đăng nhập' : 'Đặt mật khẩu rút tiền'
  }
  return type.value === 'login' ? 'Đổi mật khẩu đăng nhập' : 'Đổi mật khẩu rút tiền'
})

const passwordRules = computed(() => {
  if (type.value === 'login') {
    return [
      { required: true, message: 'Vui lòng nhập mật khẩu' },
      { pattern: /^.{6,20}$/, message: 'Độ dài mật khẩu từ 6-20 ký tự' }
    ]
  } else {
    return [
      { required: true, message: 'Vui lòng nhập mật khẩu' },
      { pattern: /^\d{6}$/, message: 'Mật khẩu rút tiền phải là 6 chữ số' }
    ]
  }
})

const validateConfirm = (val) => {
  return val === form.value.newPassword
}

const goBack = () => {
  router.go(-1)
}

const onSubmit = async () => {
  const toast = showLoadingToast({
    message: 'Đang gửi...',
    forbidClick: true,
    duration: 0
  })

  try {
    let res
    
    if (type.value === 'login') {

      res = await withdrawApi.changePassword({
        oldPassword: form.value.oldPassword,
        newPassword: form.value.newPassword,
        confirmPassword: form.value.confirmPassword
      })
    } else {

      if (isFirstSet.value) {

        res = await withdrawApi.setFundPassword({
          password: form.value.newPassword,
          confirmPassword: form.value.confirmPassword
        })
      } else {

        res = await withdrawApi.changeFundPassword({
          oldPassword: form.value.oldPassword,
          newPassword: form.value.newPassword,
          confirmPassword: form.value.confirmPassword
        })
      }
    }

    closeToast()

    if (res.code === 0) {
      showToast({
        message: isFirstSet.value ? 'Đặt thành công' : 'Thay đổi thành công',
        icon: 'success',
        onClose: () => {
          router.go(-1)
        }
      })
    } else {
      showToast(res.message || 'Thao tác thất bại')
    }
  } catch (error) {
    closeToast()
    console.error('Thao tác mật khẩu thất bại:', error)
    showToast('Lỗi mạng')
  }
}
</script>

<style scoped>
.change-password-page {
  min-height: 100vh;
  background: #f5f5f5;
}

.nav-bar {
  background: #fff;
}

.form-container {
  padding: 16px;
}

.tips {
  background: #fff3cd;
  border: 1px solid #ffc107;
  border-radius: 8px;
  padding: 12px;
  margin: 16px 0;
}

.tips-title {
  font-size: 14px;
  font-weight: bold;
  color: #856404;
  margin-bottom: 8px;
}

.tips-item {
  font-size: 13px;
  color: #856404;
  line-height: 1.6;
}

.submit-button {
  margin-top: 24px;
}
</style>
