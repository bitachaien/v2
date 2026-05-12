<template>
  <van-popup v-model:show="visible" round class="add-wechat-popup" :style="{ width: '92%' }">
    <div class="popup-content">
      <div class="popup-header">
        <div class="popup-title">添加微信</div>
      </div>
      <div class="popup-body">
        <div class="wechat-tip-bar">
          <van-icon name="info-o" color="#26A17B" />
          <span>仅支持本人实名微信绑定的收款码</span>
        </div>
        
        <div class="upload-section">
          <van-uploader 
            v-model="wechatQrFile" 
            :max-count="1" 
            :after-read="onWechatQrRead"
            :preview-size="160"
            accept="image/*"
            class="wechat-uploader"
          >
            <div class="upload-placeholder">
              <van-icon name="qr" class="qr-upload-icon" />
              <p>点击上传微信收款码</p>
              <span class="upload-sub-tip">请确保二维码清晰可见</span>
            </div>
          </van-uploader>
        </div>
        
        <div class="tip-msg">
          温馨提示：请上传您本人微信的收款二维码
        </div>
        <div class="popup-btn" :class="{ disabled: wechatQrFile.length === 0 }" @click="handleSubmit">确 定</div>
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
const wechatQrFile = ref([])
const uploadedQrUrl = ref('')

watch(() => props.modelValue, (val) => {
  visible.value = val
  if (val) {
    wechatQrFile.value = []
    uploadedQrUrl.value = ''
  }
})

watch(visible, (val) => {
  emit('update:modelValue', val)
})

const onWechatQrRead = async (file) => {
  showLoadingToast({ message: '上传中...', forbidClick: true })
  try {
    const res = await withdrawApi.uploadQrCode(file.file)
    closeToast()
    if (res.code === 0) {
      uploadedQrUrl.value = res.data.url
    } else {
      showToast(res.message || '上传失败')
      wechatQrFile.value = []
    }
  } catch (e) {
    closeToast()
    showToast('上传失败')
    wechatQrFile.value = []
  }
}

const handleSubmit = async () => {
  if (!uploadedQrUrl.value) {
    showToast('请上传微信收款码')
    return
  }
  
  try {
    const res = await withdrawApi.addAccount({
      type: 'wechat',
      account: 'wechat_qr',
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
.add-wechat-popup {
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
.wechat-tip-bar {
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
.upload-section {
  display: flex;
  justify-content: center;
  margin-bottom: 15px;
}
.upload-placeholder {
  width: 160px;
  height: 160px;
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
.upload-placeholder p {
  margin: 0;
  font-size: 14px;
  pointer-events: none;
}
.qr-upload-icon {
  font-size: 40px;
  color: #26A17B;
  margin-bottom: 10px;
  pointer-events: none;
}
.upload-sub-tip {
  font-size: 12px;
  color: #999;
  margin-top: 4px;
  pointer-events: none;
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

:deep(.wechat-uploader) {
  display: flex;
  justify-content: center;
}
:deep(.wechat-uploader .van-uploader__upload) {
  width: 160px;
  height: 160px;
  margin: 0;
  background: #f7f8fa;
  border: 2px dashed #ddd;
  border-radius: 8px;
  flex-direction: column;
}
:deep(.wechat-uploader .van-uploader__upload-icon) {
  font-size: 40px;
  color: #26A17B;
}
:deep(.wechat-uploader .van-uploader__upload-text) {
  margin-top: 8px;
  font-size: 14px;
  color: #666;
}
:deep(.wechat-uploader .van-uploader__preview) {
  margin: 0;
}
:deep(.wechat-uploader .van-uploader__preview-image) {
  width: 160px;
  height: 160px;
  border-radius: 8px;
}
</style>
