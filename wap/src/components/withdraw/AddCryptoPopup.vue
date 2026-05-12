<template>
  <van-popup v-model:show="visible" round class="add-crypto-popup" :style="{ width: '92%' }">
    <div class="popup-content">
      <div class="popup-header">
        <div class="popup-title">添加数字货币</div>
      </div>
      <div class="popup-body">
        <div class="form-item">
          <div class="input-box disabled">
            <van-icon name="gold-coin" class="input-icon" />
            <span>USDT</span>
          </div>
        </div>
        <div class="form-item">
          <div class="input-box disabled">
            <van-icon name="description" class="input-icon" />
            <span>TRC-20</span>
          </div>
        </div>
        <div class="form-item">
          <div class="input-box">
            <input type="text" v-model="cryptoAddress" placeholder="请输入数字货币地址" class="address-input" />
            <span class="paste-btn" @click="pasteAddress">粘贴</span>
          </div>
        </div>
        <div class="tip-msg">
          温馨提示：请使用钱包地址，不要使用交易所地址，避免风控
        </div>
        <div class="popup-btn" :class="{ disabled: !cryptoAddress }" @click="handleSubmit">确 定</div>
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
import { showToast } from 'vant'
import { withdrawApi } from '@/api/withdraw'

const props = defineProps({
  modelValue: Boolean,
  fundPassword: String
})

const emit = defineEmits(['update:modelValue', 'success'])

const visible = ref(false)
const cryptoAddress = ref('')

watch(() => props.modelValue, (val) => {
  visible.value = val
  if (val) {
    cryptoAddress.value = ''
  }
})

watch(visible, (val) => {
  emit('update:modelValue', val)
})

const pasteAddress = async () => {
  try {
    const text = await navigator.clipboard.readText()
    cryptoAddress.value = text
  } catch (e) {
    showToast('粘贴失败，请手动输入')
  }
}

const handleSubmit = async () => {
  if (!cryptoAddress.value) {
    showToast('请输入USDT地址')
    return
  }
  try {
    const res = await withdrawApi.addAccount({
      type: 'usdt',
      network: 'TRC20',
      address: cryptoAddress.value,
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
.add-crypto-popup {
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
.input-box.disabled {
  background: #f9f9f9;
}
.input-icon {
  font-size: 18px;
  color: #999;
  margin-right: 10px;
}
.address-input {
  background: transparent;
  border: none;
  flex: 1;
  outline: none;
  font-size: 14px;
}
.paste-btn {
  color: #26A17B;
  font-size: 14px;
  padding-left: 12px;
  border-left: 1px solid #eee;
  margin-left: 5px;
  white-space: nowrap;
  cursor: pointer;
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
</style>
