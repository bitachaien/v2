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
          label="原密码"
          placeholder="请输入原密码"
          required
          :rules="[{ required: true, message: '请输入原密码' }]"
        />

        
        <van-field
          v-model="form.newPassword"
          type="password"
          :label="isFirstSet ? '设置密码' : '新密码'"
          :placeholder="isFirstSet ? '请设置密码' : '请输入新密码'"
          required
          :rules="passwordRules"
        />

        
        <van-field
          v-model="form.confirmPassword"
          type="password"
          label="确认密码"
          placeholder="请再次输入密码"
          required
          :rules="[
            { required: true, message: '请再次输入密码' },
            { validator: validateConfirm, message: '两次密码不一致' }
          ]"
        />

        
        <div class="tips">
          <div class="tips-title">密码规则：</div>
          <div class="tips-item" v-if="type === 'login'">
            • 长度6-20位，支持字母、数字、特殊字符
          </div>
          <div class="tips-item" v-else>
            • 6位纯数字
          </div>
        </div>

        
        <div class="submit-button">
          <van-button block type="primary" native-type="submit">
            {{ isFirstSet ? '确认设置' : '确认修改' }}
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
    return type.value === 'login' ? '设置登录密码' : '设置资金密码'
  }
  return type.value === 'login' ? '修改登录密码' : '修改资金密码'
})

const passwordRules = computed(() => {
  if (type.value === 'login') {
    return [
      { required: true, message: '请输入密码' },
      { pattern: /^.{6,20}$/, message: '密码长度为6-20位' }
    ]
  } else {
    return [
      { required: true, message: '请输入密码' },
      { pattern: /^\d{6}$/, message: '资金密码必须为6位数字' }
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
    message: '提交中...',
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
        message: isFirstSet.value ? '设置成功' : '修改成功',
        icon: 'success',
        onClose: () => {
          router.go(-1)
        }
      })
    } else {
      showToast(res.message || '操作失败')
    }
  } catch (error) {
    closeToast()
    console.error('密码操作失败:', error)
    showToast('网络错误')
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
