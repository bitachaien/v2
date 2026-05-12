<template>
  <van-popup v-model:show="visible" round class="add-alipay-popup" :style="{ width: '92%' }">
    <div class="popup-content">
      <div class="popup-header">
        <div class="popup-title">添加支付宝</div>
      </div>
      <div class="popup-body">
        <div class="alipay-tip-bar">
          <van-icon name="info-o" color="#26A17B" />
          <span>只支持实名与平台账号姓名一致的支付宝账户</span>
        </div>
        
        <div class="form-item">
          <div class="input-box">
            <input 
              type="text" 
              v-model="alipayAccount" 
              placeholder="请输入支付宝账号（手机号/邮箱）" 
              class="alipay-input" 
            />
          </div>
        </div>
        
        <div class="upload-section">
          <van-uploader 
            v-model="alipayQrFile" 
            :max-count="1" 
            :after-read="onAlipayQrRead"
            :preview-size="120"
            accept="image/*"
            class="alipay-uploader"
          >
            <div class="upload-placeholder-small">
              <van-icon name="qr" class="qr-upload-icon-small" />
              <p>上传收款码</p>
            </div>
          </van-uploader>
        </div>
        
        <div class="tip-msg">
          温馨提示：请填写支付宝账号并上传收款码
        </div>
        <div class="popup-btn" :class="{ disabled: !alipayAccount || alipayQrFile.length === 0 }" @click="handleSubmit">确 定</div>
      </div>
    </div>
    
    <div class="close-circle-wrapper">
      <div class="close-circle" @click="visible = false">
        <van-icon name="cross" color="#fff" size="20" />
      </div>
    </div>
  </van-popup>
</template>

<script setup>
import { ref, watch } from 'vue'
import { showToast, showLoadingToast, closeToast } from 'vant'
import { withdrawApi } from '@/api/withdraw'

const props = defineProps({
  modelValue: Boolean,
  fundPassword: String
})

const emit = defineEmits(['update:modelValue', 'success'])

const visible = ref(false)
const alipayAccount = ref('')
const alipayQrFile = ref([])
const uploadedQrUrl = ref('')

watch(() => props.modelValue, (val) => {
  visible.value = val
  if (val) {
    alipayAccount.value = ''
    alipayQrFile.value = []
    uploadedQrUrl.value = ''
  }
})

watch(visible, (val) => {
  emit('update:modelValue', val)
})

const onAlipayQrRead = async (file) => {
  showLoadingToast({ message: '上传中...', forbidClick: true })
  try {
    const res = await withdrawApi.uploadQrCode(file.file)
    closeToast()
    if (res.code === 0) {
      uploadedQrUrl.value = res.data.url
    } else {
      showToast(res.message || '上传失败')
      alipayQrFile.value = []
    }
  } catch (e) {
    closeToast()
    showToast('上传失败')
    alipayQrFile.value = []
  }
}

const handleSubmit = async () => {
  if (!alipayAccount.value) {
    showToast('请输入支付宝账号')
    return
  }
  if (!uploadedQrUrl.value) {
    showToast('请上传支付宝收款码')
    return
  }
  
  try {
    const res = await withdrawApi.addAccount({
      type: 'alipay',
      account: alipayAccount.value,
      qrCode: uploadedQrUrl.value,
      fundPassword: props.fundPassword
    })
    
    if (res.code === 0) {
      showToast('添加成功')
      visible.value = false
      emit('success')
    } else {
      showToast(res.message || '添加失败')
    }
  } catch (e) {
    showToast('添加失败')
  }
}
</script>

<style scoped>
.add-alipay-popup {
  background: transparent !important;
  overflow: visible !important;
}
.popup-content {
  background: #fff;
  border-radius: 12px;
  overflow: hidden;
}
.popup-header {
  padding: 20px 0 10px;
  text-align: center;
}
.popup-title {
  font-size: 18px;
  font-weight: 500;
  color: #333;
}
.popup-body {
  padding: 10px 20px 25px;
}
.alipay-tip-bar {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: #26A17B;
  background: rgba(38, 161, 123, 0.1);
  padding: 10px 12px;
  border-radius: 6px;
  margin-bottom: 15px;
}
.form-item {
  margin-bottom: 12px;
}
.input-box {
  background: #fff;
  border: 1px solid #eee;
  border-radius: 4px;
  height: 44px;
  display: flex;
  align-items: center;
  padding: 0 12px;
}
.alipay-input {
  width: 100%;
  background: transparent;
  border: none;
  outline: none;
  font-size: 14px;
  color: #333;
}
.alipay-input::placeholder {
  color: #999;
}
.upload-section {
  display: flex;
  justify-content: center;
  margin-bottom: 15px;
}
.upload-placeholder-small {
  width: 120px;
  height: 120px;
  border: 2px dashed #ddd;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: #f7f8fa;
  color: #666;
  cursor: pointer;
}
.upload-placeholder-small p {
  margin: 0;
  font-size: 12px;
}
.qr-upload-icon-small {
  font-size: 30px;
  color: #26A17B;
  margin-bottom: 6px;
}
.tip-msg {
  font-size: 12px;
  color: #666;
  line-height: 1.5;
  margin: 15px 0 20px;
}
.popup-btn {
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  border-radius: 6px;
  background: #26A17B;
  color: #fff;
  font-weight: 500;
  cursor: pointer;
}
.popup-btn.disabled {
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

:deep(.alipay-uploader) {
  display: flex;
  justify-content: center;
}
:deep(.alipay-uploader .van-uploader__upload) {
  margin: 0;
}
:deep(.alipay-uploader .van-uploader__preview) {
  margin: 0;
}
:deep(.alipay-uploader .van-uploader__preview-image) {
  width: 120px;
  height: 120px;
  border-radius: 8px;
}
</style>
