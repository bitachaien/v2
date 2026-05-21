<template>
  <div class="hemai-create-tech">
    <div class="bg-overlay"></div>
    <div class="tech-bg-grid"></div>

    <van-nav-bar
      title="Phát hành hợp mãi"
      left-arrow
      fixed
      placeholder
      @click-left="router.back()"
      class="tech-nav"
    />

    <div class="create-content custom-scrollbar">
      
      <div class="glass-card-form">
        <div class="form-header">
          <span class="title">Chọn loại xổ số</span>
        </div>
        <div class="lottery-selector" @click="showLotteryPicker = true">
          <div class="ls-left" v-if="selectedLottery">
            <img :src="getGameIconPath(selectedLottery.name)" class="ls-icon" />
            <div class="ls-info">
              <span class="ls-name">{{ selectedLottery.title }}</span>
              <span class="ls-expect">No.{{ currentExpect || '获取中...' }}</span>
            </div>
          </div>
          <div class="ls-left" v-else>
            <span class="ls-placeholder">Vui lòng chọn loại xổ số</span>
          </div>
          <van-icon name="arrow" color="#64748b" />
        </div>
        
        <div class="countdown-row" v-if="selectedLottery">
          <span class="label">Đếm ngược hết hạn</span>
          <van-count-down :time="3600000" format="HH:mm:ss" class="timer-red" />
        </div>
      </div>

      <div class="glass-card-form">
        <div class="form-header">
          <span class="title">Nội dung phương án</span>
          <span class="action-link" @click="mockReselect">Chọn lại số</span>
        </div>
        <div class="code-area">
          <van-field
            v-model="formData.tzcode"
            rows="4"
            autosize
            type="textarea"
            placeholder="Chưa có số, vui lòng quay lại trang chơi để chọn số"
            class="tech-input-area"
          />
        </div>
        <div class="stats-line">
          Tổng 0 vé, tiền thưởng lý thuyết 0.00 đ
        </div>
      </div>

      <div class="glass-card-form">
        <div class="form-header">
          <span class="title">Cài đặt số tiền</span>
        </div>
        
        <div class="input-row">
          <span class="label">Tổng tiền phương án</span>
          <div class="input-wrapper">
            <input type="number" v-model.number="formData.amount" placeholder="0" class="tech-input" />
            <span class="unit">đ</span>
          </div>
        </div>

        <div class="input-row">
          <span class="label">Tiền mỗi phần</span>
          <div class="input-wrapper">
            <input type="number" v-model.number="formData.unitPrice" placeholder="1" class="tech-input" />
            <span class="unit">đ</span>
          </div>
        </div>

        <div class="error-tip" v-if="formData.amount > 0 && formData.unitPrice > 0 && formData.amount % formData.unitPrice !== 0">
          <van-icon name="info-o" /> Số tiền phương án phải là bội số nguyên của tiền mỗi phần
        </div>

        <div class="calc-result">
          <span class="label">Tổng số phần</span>
          <span class="val-tag">{{ totalShares }} phần</span>
        </div>
      </div>

      <div class="glass-card-form">
        <div class="form-header">
          <span class="title">Cài đặt mua</span>
        </div>

        <div class="stepper-row">
          <span class="label">Tôi muốn mua</span>
          <van-stepper v-model="formData.num" :min="1" :max="maxBuy" theme="round" button-size="22" class="tech-stepper" integer />
        </div>
        
        <div class="stepper-row">
          <span class="label">Số phần bảo đảm</span>
          <van-stepper v-model="formData.baodi" :min="0" :max="maxBaodi" theme="round" button-size="22" class="tech-stepper" integer />
        </div>

        <div class="stepper-row">
          <span class="label">Hoa hồng trúng thưởng</span>
          <div class="right-input">
            <van-stepper v-model="formData.commission" :min="0" :max="10" theme="round" button-size="22" class="tech-stepper" integer />
            <span class="suffix">%</span>
          </div>
        </div>

        <div class="warn-tip" v-if="totalShares > 0 && (formData.baodi / totalShares) < 0.2">
          Bảo đảm dưới 20%, phương án này sẽ không xuất hiện trong sảnh hot, chỉ có thể tham gia qua chia sẻ
        </div>

        <div class="pay-row">
          <span class="label">Cần thanh toán</span>
          <span class="val-gold">{{ currentPay }} đ</span>
        </div>
      </div>

      <div class="glass-card-form">
        <div class="form-header">
          <span class="title">Cài đặt khác</span>
        </div>
        <van-field
          v-model="formData.content"
          rows="2"
          autosize
          type="textarea"
          placeholder="Tuyên ngôn hợp mãi: Theo tôi mua, biệt thự ven biển!"
          class="tech-input-area"
        />
        
        <div class="radio-group-col">
          <span class="label-sm">Công khai phương án</span>
          <van-radio-group v-model="formData.public" direction="horizontal" class="tech-radio">
            <van-radio :name="0">Công khai</van-radio>
            <van-radio :name="1">Sau khi hết hạn</van-radio>
            <van-radio :name="2">Người theo mới thấy</van-radio>
            <van-radio :name="3">Bảo mật</van-radio>
          </van-radio-group>
        </div>
      </div>

      <div class="submit-bar">
        <div class="total-info">
          Cần thanh toán: <span class="pay-money">{{ currentPay }}</span> đ
        </div>
        <button class="submit-btn" :class="{ disabled: !canSubmit }" @click="handleSubmit">Phát hành ngay</button>
      </div>

    </div>

    <van-popup v-model:show="showLotteryPicker" round position="bottom" class="tech-popup">
      <van-picker
        :columns="pickerColumns"
        @confirm="onLotteryConfirm"
        @cancel="showLotteryPicker = false"
        :columns-field-names="{ text: 'title', value: 'name' }"
        class="tech-picker"
      />
    </van-popup>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { showToast, showLoadingToast, closeToast, showSuccessToast } from 'vant'
import { hemaiApi } from '@/api/hemai'
import { getGameIconPath, DEFAULT_FULL_GAMES } from '@/config/gameConfig'

const router = useRouter()
const route = useRoute()

const showLotteryPicker = ref(false)
const selectedLottery = ref(null)
const currentExpect = ref('') 
const lotteryList = ref([])

const formData = ref({
  tzcode: '',
  amount: '',      // 总金额
  unitPrice: 1,    // 每份金额
  num: 1,          // 我认购份数
  baodi: 0,        // 保底份数
  commission: 0,   // 提成比例
  content: '',
  public: 0
})

const totalShares = computed(() => {
  const amt = parseFloat(formData.value.amount)
  const price = parseFloat(formData.value.unitPrice)
  if (amt > 0 && price > 0) {

    if (Math.abs(amt % price) < 0.001 || Math.abs(amt % price - price) < 0.001) {
      return Math.round(amt / price)
    }
  }
  return 0
})

const currentPay = computed(() => {
  const num = parseInt(formData.value.num) || 0
  const baodi = parseInt(formData.value.baodi) || 0
  const price = parseFloat(formData.value.unitPrice) || 0
  return ((num + baodi) * price).toFixed(2)
})

const maxBuy = computed(() => {
  const total = totalShares.value
  const baodi = formData.value.baodi || 0
  return Math.max(1, total - baodi)
})

const maxBaodi = computed(() => {
  const total = totalShares.value
  const num = formData.value.num || 1
  return Math.max(0, total - num)
})

const canSubmit = computed(() => {
  const d = formData.value
  if (!selectedLottery.value) return false
  if (!d.tzcode) return false
  if (d.amount <= 0 || d.unitPrice <= 0) return false
  if (totalShares.value <= 0) return false // 未整除时 totalShares 为 0
  if (d.num < 1) return false
  if ((d.num + d.baodi) > totalShares.value) return false
  return true
})

const pickerColumns = computed(() => {
  if (!lotteryList.value || lotteryList.value.length === 0) return []
  return lotteryList.value.map(l => ({ text: l.title, value: l.name, ...l }))
})

const onLotteryConfirm = async ({ selectedOptions }) => {
  if (!selectedOptions || selectedOptions.length === 0) return
  const opt = selectedOptions[0]
  selectedLottery.value = {
    name: opt.value,
    title: opt.text,
    ...opt
  }
  showLotteryPicker.value = false
  

  showLoadingToast({ message: 'Đang lấy kỳ số...', forbidClick: true, duration: 0 })
  try {
    const res = await hemaiApi.getNextIssue(opt.value)
    
    if (res.code === 0 && res.data) {
      currentExpect.value = res.data.issue

    } else {
      throw new Error('Lấy kỳ số thất bại')
    }
    
    closeToast()
  } catch (e) {
    closeToast()

    const now = new Date()
    const dateStr = `${now.getFullYear()}${(now.getMonth()+1).toString().padStart(2,'0')}${now.getDate().toString().padStart(2,'0')}`
    currentExpect.value = `${dateStr}001` 
  }
}

const mockReselect = () => {
  showToast('Đã mô phỏng quay lại trang chọn số')
}

const loadLotteryList = async () => {
  try {
    const res = await hemaiApi.getLotteryList()
    if (res.code === 0 && res.data?.list && res.data.list.length > 0) {
      lotteryList.value = res.data.list
    } else {
      fallbackLotteryList()
    }
  } catch (e) {
    fallbackLotteryList()
  }
}

const fallbackLotteryList = () => {
  const list = []
  Object.values(DEFAULT_FULL_GAMES).forEach(g => list.push(...g))
  lotteryList.value = list.map(item => ({
    name: item.name, 
    title: item.title
  }))
}

const handleSubmit = async () => {
  if (!canSubmit.value) return
  
  showLoadingToast({ message: 'Đang phát hành...', forbidClick: true })
  
  try {
    const data = {
      lottery: selectedLottery.value.name,
      expect: currentExpect.value,
      playtitle: 'Đặt cược phức hợp', // 如有 playid 也需传入
      tzcode: formData.value.tzcode,
      totalAmount: parseFloat(formData.value.amount),
      perShare: parseFloat(formData.value.unitPrice),
      totalShares: totalShares.value,
      num: formData.value.num,
      baodi: formData.value.baodi || 0,
      commission: formData.value.commission || 0,
      public: formData.value.public,
      content: formData.value.content
    }
    
    const res = await hemaiApi.create(data)
    
    if (res.code === 0) {
      closeToast()
      showSuccessToast('Phương án hợp mãi đã phát hành')

      setTimeout(() => {
        router.replace({ name: 'LotteryHemaiDetail', query: { id: res.data?.id || 'mock_id' } })
      }, 1500)
    } else {
      closeToast()
      showToast(res.message || 'Phát hành thất bại')
    }
  } catch (e) {
    closeToast()
    showToast(e.message || 'Lỗi mạng')
  }
}

onMounted(async () => {
  await loadLotteryList()
  

  const { cpname, cptitle, tzcode, amount, unitPrice, content } = route.query
  if (cpname) {

    const found = lotteryList.value.find(l => l.name === cpname)
    if (found) {

      onLotteryConfirm({ selectedOptions: [{ text: found.title, value: found.name, ...found }] })
    } else if (cptitle) {

      selectedLottery.value = { name: cpname, title: cptitle }

      currentExpect.value = '2025' + Math.floor(Math.random()*10000)
    }
  }
  
  if (tzcode) formData.value.tzcode = tzcode
  if (amount) formData.value.amount = amount
  if (unitPrice) formData.value.unitPrice = unitPrice
  if (content) formData.value.content = content
  if (route.query.commission) formData.value.commission = route.query.commission
})
</script>

<style scoped>
.hemai-create-tech {
  min-height: 100vh;
  background: #0B0E15;
  background: linear-gradient(180deg, #111118 0%, #050508 100%);
  color: #fff;
  font-family: 'PingFang SC', sans-serif;
  padding-top: 46px;
  padding-bottom: 80px;
}

.bg-overlay {
  position: fixed; top: 0; left: 0; width: 100%; height: 100%;
  background: radial-gradient(circle at 50% 0%, rgba(30, 41, 59, 0.4) 0%, transparent 70%);
  pointer-events: none; z-index: 0;
}
.tech-bg-grid {
  position: fixed; top: 0; left: 0; width: 100%; height: 100%;
  background-image: 
    linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
  background-size: 40px 40px;
  pointer-events: none; z-index: 0;
}

.tech-nav {
  --van-nav-bar-background: rgba(11, 14, 21, 0.85);
  --van-nav-bar-title-text-color: #fff;
  --van-nav-bar-icon-color: #fff;
  backdrop-filter: blur(10px);
  border-bottom: 1px solid rgba(255,255,255,0.05);
}

.create-content {
  padding: 15px; position: relative; z-index: 1;
}

.glass-card-form {
  background: rgba(30, 35, 50, 0.4);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 12px;
  padding: 15px; margin-bottom: 15px;
  backdrop-filter: blur(10px);
}

.form-header {
  margin-bottom: 12px; padding-left: 8px; border-left: 3px solid #EAC26E;
  display: flex; justify-content: space-between; align-items: center;
}
.title { font-size: 15px; font-weight: bold; color: #fff; }
.action-link { font-size: 12px; color: #60a5fa; }

.lottery-selector {
  display: flex; justify-content: space-between; align-items: center;
  background: rgba(0,0,0,0.2); padding: 12px; border-radius: 8px;
  border: 1px solid rgba(255,255,255,0.05);
}
.ls-left { display: flex; align-items: center; gap: 10px; }
.ls-icon { width: 36px; height: 36px; background: rgba(255,255,255,0.05); padding: 4px; border-radius: 6px; }
.ls-info { display: flex; flex-direction: column; }
.ls-name { font-size: 15px; font-weight: bold; color: #fff; }
.ls-expect { font-size: 12px; color: #94a3b8; }
.ls-placeholder { color: #64748b; font-size: 14px; }

.countdown-row { margin-top: 12px; display: flex; align-items: center; gap: 10px; font-size: 12px; color: #64748b; }
.timer-red { color: #ef4444; font-weight: bold; }

.tech-input-area {
  background: rgba(0,0,0,0.2); border-radius: 8px; padding: 10px;
  color: #fff; font-size: 14px; border: 1px solid rgba(255,255,255,0.05);
  --van-field-placeholder-text-color: #475569;
}
.stats-line { text-align: right; font-size: 11px; color: #64748b; margin-top: 6px; }

.input-row {
  display: flex; justify-content: space-between; align-items: center;
  margin-top: 15px;
}
.input-row:first-child { margin-top: 5px; }
.input-row .label { font-size: 14px; color: #cbd5e1; }
.input-wrapper {
  display: flex; align-items: center; background: rgba(0,0,0,0.2);
  border-radius: 6px; padding: 0 10px; width: 140px; height: 36px;
  border: 1px solid rgba(255,255,255,0.05);
}
.tech-input {
  flex: 1; background: transparent; border: none; color: #fff; font-size: 14px; text-align: right; padding-right: 6px;
  width: 100%;
}
.unit { color: #64748b; font-size: 13px; }

.error-tip { color: #ef4444; font-size: 11px; margin-top: 8px; display: flex; align-items: center; gap: 4px; }

.calc-result { display: flex; justify-content: space-between; margin-top: 15px; font-size: 13px; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 10px; }
.calc-result .label { color: #94a3b8; }
.val-tag { background: rgba(255,255,255,0.1); padding: 2px 8px; border-radius: 4px; color: #cbd5e1; }

.stepper-row { display: flex; justify-content: space-between; align-items: center; margin-top: 15px; }
.stepper-row:first-child { margin-top: 5px; }
.stepper-row .label { font-size: 14px; color: #cbd5e1; }
.tech-stepper {
  --van-stepper-background: rgba(255,255,255,0.1);
  --van-stepper-button-icon-color: #fff;
  --van-stepper-input-text-color: #fff;
}

.warn-tip { color: #64748b; font-size: 11px; margin-top: 8px; background: rgba(255,255,255,0.03); padding: 6px; border-radius: 4px; }

.pay-row { display: flex; justify-content: space-between; align-items: center; margin-top: 15px; font-size: 14px; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 10px; }
.pay-row .label { color: #cbd5e1; }
.val-gold { color: #EAC26E; font-weight: bold; font-family: monospace; }

.radio-group-col { display: flex; flex-direction: column; gap: 10px; margin-top: 10px; }
.label-sm { font-size: 12px; color: #94a3b8; }
.tech-radio {
  --van-radio-label-color: #cbd5e1;
  --van-radio-checked-icon-color: #EAC26E;
}
:deep(.van-radio__label) { font-size: 12px; }

.submit-bar {
  position: fixed; bottom: 0; left: 0; width: 100%;
  background: rgba(11, 14, 21, 0.95); backdrop-filter: blur(10px);
  padding: 10px 20px; border-top: 1px solid rgba(255,255,255,0.05);
  display: flex; justify-content: space-between; align-items: center;
  padding-bottom: calc(10px + constant(safe-area-inset-bottom));
  padding-bottom: calc(10px + env(safe-area-inset-bottom));
  z-index: 100;
}
.total-info { font-size: 14px; color: #cbd5e1; }
.pay-money { font-size: 20px; color: #EAC26E; font-weight: bold; font-family: monospace; margin: 0 4px; }

.submit-btn {
  background: linear-gradient(135deg, #F0C930 0%, #D19611 100%);
  border: none; color: #000; font-weight: bold; font-size: 15px;
  padding: 10px 30px; border-radius: 24px;
  box-shadow: 0 4px 15px rgba(240, 201, 48, 0.3);
  transition: all 0.2s;
}
.submit-btn:active { transform: scale(0.95); }
.submit-btn.disabled { background: #334155; color: #94a3b8; box-shadow: none; cursor: not-allowed; }

.tech-popup { background: #1e1e2f; }
.tech-picker {
  --van-picker-background: #1e1e2f;
  --van-picker-option-text-color: #fff;
  --van-picker-toolbar-action-color: #EAC26E;
  --van-picker-mask-color: linear-gradient(180deg, rgba(30,30,47,0.9), rgba(30,30,47,0.4)), linear-gradient(0deg, rgba(30,30,47,0.9), rgba(30,30,47,0.4));
}
</style>
