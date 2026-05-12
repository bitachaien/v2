<template>
  <van-popup v-model:show="visible" round class="fund-pwd-popup" :style="{ width: '92%' }" :lock-scroll="false" :z-index="2000">
    <div class="pwd-popup-content">
      <div class="pwd-popup-title">输入密码</div>
      
      <div class="pwd-input-wrapper">
        <div class="pwd-input-header">
          <div class="pwd-input-label">提现密码</div>
          <img :src="showPwdVisible ? '/assets/img/comm_icon_show.svg' : '/assets/img/comm_icon_hide.svg'" class="pwd-visible-icon" :class="{ active: showPwdVisible }" @click="showPwdVisible = !showPwdVisible" />
        </div>
        <van-password-input
          :value="password"
          :focused="keyboardVisible"
          :mask="!showPwdVisible"
          @focus="keyboardVisible = true"
        />
      </div>
      
      <div class="pwd-tip-row">
        <span class="pwd-tip-text">为了您的账户安全，请输入提现密码</span>
        <span class="pwd-forget-link" @click="handleForgetPwd">忘记密码？</span>
      </div>
      
      <div class="pwd-popup-btn" :class="{ disabled: password.length < 6 }" @click="handleConfirm">下一步</div>
    </div>
    <div class="close-circle-wrapper">
      <div class="close-circle" @click="handleClose">
        <van-icon name="cross" color="#fff" size="20" />
      </div>
    </div>
  </van-popup>
  
  <van-number-keyboard
    :show="keyboardVisible"
    @input="onKeyboardInput"
    @delete="onKeyboardDelete"
    @blur="keyboardVisible = false"
    :z-index="2010"
  />
</template>

<script setup>
import { ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { showToast, showConfirmDialog } from 'vant'
import { securityApi } from '@/api/security'

const router = useRouter()

const props = defineProps({
  modelValue: Boolean
})

const emit = defineEmits(['update:modelValue', 'confirm'])

const visible = ref(false)
const password = ref('')
const keyboardVisible = ref(false)
const showPwdVisible = ref(false)

watch(() => props.modelValue, (val) => {
  visible.value = val
  if (val) {
    password.value = ''
    keyboardVisible.value = true
  }
})

watch(visible, (val) => {
  emit('update:modelValue', val)
  if (!val) {
    keyboardVisible.value = false
  }
})

const onKeyboardInput = (key) => {
  if (password.value.length < 6) {
    password.value += key
  }
}

const onKeyboardDelete = () => {
  password.value = password.value.slice(0, -1)
}

const handleClose = () => {
  visible.value = false
  keyboardVisible.value = false
  password.value = ''
}

const handleConfirm = async () => {
  if (password.value.length < 6) {
    showToast('请输入完整的资金密码')
    return
  }
  
  try {
    const res = await securityApi.verifyFundPwd({ password: password.value })
    if (res.code === 0) {
      keyboardVisible.value = false
      visible.value = false
      emit('confirm', password.value)
      password.value = ''
    } else {
      showToast(res.message || '密码错误')
      password.value = ''
    }
  } catch (e) {
    showToast('验证失败')
    password.value = ''
  }
}

const handleForgetPwd = () => {
  showConfirmDialog({
    title: '温馨提示',
    message: '请联系客服修改密码!',
    confirmButtonText: '客 服',
    cancelButtonText: '取 消',
    confirmButtonColor: '#26A17B',
    className: 'forget-pwd-dialog'
  }).then(() => {
    handleClose()
    router.push('/service/chat')
  }).catch(() => {})
}
</script>

<style scoped>
.fund-pwd-popup {
  background: transparent !important;
  overflow: visible !important;
}
.pwd-popup-content {
  background: #fff;
  border-radius: 12px;
  padding: 20px;
  position: relative;
  box-sizing: border-box;
}
.pwd-popup-title {
  font-size: 18px;
  font-weight: 600;
  color: #333;
  text-align: center;
  margin-bottom: 15px;
}
.pwd-input-wrapper {
  position: relative;
  margin-top: 10px;
}
.pwd-input-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}
.pwd-input-label {
  font-size: 12.61px;
  color: #333;
}
.pwd-visible-icon {
  width: 18.44px;
  height: 17.07px;
  cursor: pointer;
  filter: brightness(0) saturate(100%) invert(70%) sepia(0%) saturate(0%) hue-rotate(0deg) brightness(90%) contrast(90%);
}
.pwd-visible-icon.active {
  filter: invert(48%) sepia(79%) saturate(374%) hue-rotate(115deg) brightness(93%) contrast(88%);
}
.pwd-tip-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 15px;
  padding: 0 5px;
}
.pwd-tip-text {
  font-size: 12.61px;
  color: #333333;
}
.pwd-forget-link {
  font-size: 12.61px;
  color: #26A17B;
  cursor: pointer;
}
.pwd-popup-btn {
  width: 359.22px;
  height: 38.13px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  border-radius: 6px;
  background: #26A17B;
  color: #fff;
  font-weight: 500;
  cursor: pointer;
  margin: 20px auto 0;
}
.pwd-popup-btn.disabled {
  background: #ccc;
}
.close-circle-wrapper {
  margin-top: 20px;
  display: flex;
  justify-content: center;
}
.close-circle {
  width: 36px;
  height: 36px;
  border: 2px solid #fff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

:deep(.van-password-input) {
  margin: 0;
}
:deep(.van-password-input__security) {
  height: 44px;
  background: transparent;
}
:deep(.van-password-input__security::after),
:deep(.van-password-input__security.van-hairline--surround::after) {
  display: none !important;
  border: none !important;
}
:deep(.van-password-input__item) {
  background: #fff !important;
  border: 1px solid #ddd !important;
  border-radius: 6px;
}
:deep(.van-password-input__item--focus) {
  border-color: #26A17B !important;
}
:deep(.van-password-input__cursor) {
  background-color: #26A17B;
}
</style>

<style>
.forget-pwd-dialog {
  width: 340px !important;
  border-radius: 12px;
  overflow: hidden;
}
.forget-pwd-dialog .van-dialog__header {
  padding-top: 24px;
  padding-bottom: 12px;
  font-size: 18px;
  font-weight: 600;
  color: #333;
}
.forget-pwd-dialog .van-dialog__message {
  padding: 12px 24px 28px;
  font-size: 15px;
  color: #666;
}
.forget-pwd-dialog .van-dialog__footer {
  padding: 0 20px 20px;
  gap: 16px;
}
.forget-pwd-dialog .van-dialog__cancel,
.forget-pwd-dialog .van-dialog__confirm {
  width: 147px !important;
  height: 38px !important;
  flex: none !important;
  border-radius: 6px !important;
  font-size: 15px !important;
}
.forget-pwd-dialog .van-dialog__cancel {
  background: #fff !important;
  border: 1px solid #26A17B !important;
  color: #26A17B !important;
}
.forget-pwd-dialog .van-dialog__confirm {
  background: #26A17B !important;
  border: none !important;
  color: #fff !important;
}
</style>
