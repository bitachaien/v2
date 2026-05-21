<template>
  <div class="rebate-ratio-tab">
    <div class="ratio-table-card">
      <div class="table-header">
        <div class="th">
          Số người hợp lệ
          <van-icon name="question-o" @click="showTip" />
        </div>
        <div class="th">Thành tích<br/>(Đơn vị: cái)</div>
        <div class="th">Số tiền hoàn hoa hồng<br/>(Tỷ lệ)</div>
      </div>
      <div class="table-body">
        <div class="tr" v-for="(item, index) in ratioList" :key="index">
          <div class="td">{{ item.effectiveCount }}</div>
          <div class="td">{{ formatNumber(item.performance) }}</div>
          <div class="td green">
            {{ formatNumber(item.commission) }}<br/>
            <span class="rate">({{ item.rate }}%)</span>
          </div>
        </div>
      </div>
    </div>

    <van-popup 
      v-model:show="showCalculator" 
      round 
      position="center"
      :style="{ width: '90%', maxWidth: '400px' }"
    >
      <div class="calculator-dialog">
        <div class="dialog-header">
          <span>Máy tính mô phỏng hoa hồng</span>
        </div>
        <van-icon name="cross" class="dialog-close" @click="showCalculator = false" />
        
        <div class="calc-form">
          <div class="form-item">
            <label>Số người hợp lệ</label>
            <van-field v-model="calcForm.effectiveCount" type="digit" placeholder="0" />
          </div>
          <div class="form-item">
            <label>Thắng thua trực thuộc</label>
            <van-field v-model="calcForm.directWinLoss" type="number" placeholder="0.00" />
          </div>
          <div class="form-item">
            <label>Nhận ưu đãi</label>
            <van-field v-model="calcForm.claimBonus" type="number" placeholder="0.00" />
          </div>
        </div>

        <button class="calc-submit-btn" @click="calculateCommission">Tính hoa hồng</button>

        <div class="calc-result">
          <div class="result-title">Kết quả tính toán</div>
          <div class="result-row">
            <span class="label">Tỷ lệ hoàn hoa hồng</span>
            <span class="value">{{ calcResult.rate || '-' }}</span>
          </div>
          <div class="result-row">
            <span class="label">Hoa hồng dự kiến</span>
            <span class="value green">{{ calcResult.commission || '-' }}</span>
          </div>
        </div>
      </div>
    </van-popup>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { showToast } from 'vant'
import { agentApi } from '@/api/agent'

const ratioList = ref([])
const showCalculator = ref(false)

const calcForm = reactive({
  effectiveCount: '',
  directWinLoss: '',
  claimBonus: ''
})

const calcResult = reactive({
  rate: '',
  commission: ''
})

const formatNumber = (num) => {
  if (num === undefined || num === null) return '0.00'
  return parseFloat(num).toLocaleString('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  })
}

const showTip = () => {
  showToast({
    message: 'Điều kiện hợp lệ: Cấp dưới nạp tiền ≥ 100 và cược hợp lệ ≥ 100',
    duration: 3000
  })
}

const fetchRatioList = async () => {
  try {
    const res = await agentApi.getCommissionRates()
    if (res.code === 0 && res.data) {
      ratioList.value = res.data.list || []
    }
  } catch (e) {
  }
}

const calculateCommission = async () => {
  if (!calcForm.effectiveCount) {
    showToast('Vui lòng nhập số người hợp lệ')
    return
  }

  try {
    const res = await agentApi.calculateCommission({
      effectiveCount: parseInt(calcForm.effectiveCount) || 0,
      directWinLoss: parseFloat(calcForm.directWinLoss) || 0,
      claimBonus: parseFloat(calcForm.claimBonus) || 0
    })
    
    if (res.code === 0 && res.data) {
      calcResult.rate = res.data.rate + '%'
      calcResult.commission = '¥' + formatNumber(res.data.commission)
    } else {
      const count = parseInt(calcForm.effectiveCount) || 0
      let rate = 0
      if (count >= 5) rate = 20
      else if (count >= 4) rate = 15
      else if (count >= 3) rate = 10
      else if (count >= 2) rate = 5
      else if (count >= 1) rate = 1

      const winLoss = parseFloat(calcForm.directWinLoss) || 0
      const bonus = parseFloat(calcForm.claimBonus) || 0
      const commission = (winLoss - bonus) * rate / 100

      calcResult.rate = rate + '%'
      calcResult.commission = '¥' + formatNumber(Math.max(0, commission))
    }
  } catch (e) {
    showToast('Tính toán thất bại')
  }
}

onMounted(() => {
  fetchRatioList()
})
</script>

<style scoped>
.rebate-ratio-tab {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.ratio-table-card {
  background: #fff;
  border-radius: 12px;
  overflow: hidden;
}

.table-header {
  display: flex;
  background: #f5f5f5;
}

.th {
  flex: 1;
  padding: 12px 8px;
  text-align: center;
  font-size: 12px;
  color: #666;
  line-height: 1.4;
}

.th .van-icon {
  color: #26A17B;
  margin-left: 4px;
  vertical-align: middle;
}

.table-body {
  max-height: 400px;
  overflow-y: auto;
}

.tr {
  display: flex;
  border-bottom: 1px solid #f0f0f0;
}

.tr:last-child {
  border-bottom: none;
}

.td {
  flex: 1;
  padding: 15px 8px;
  text-align: center;
  font-size: 14px;
  color: #333;
}

.td.green {
  color: #26A17B;
}

.td .rate {
  font-size: 12px;
  opacity: 0.8;
}

.calculator-dialog {
  padding: 20px;
  position: relative;
}

.dialog-header {
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  font-weight: 600;
  color: #333;
  margin-bottom: 20px;
}

.dialog-close {
  position: absolute;
  top: 15px;
  right: 15px;
  font-size: 20px;
  color: #999;
}

.calc-form {
  margin-bottom: 20px;
}

.form-item {
  margin-bottom: 15px;
}

.form-item label {
  display: block;
  font-size: 14px;
  color: #666;
  margin-bottom: 8px;
}

.form-item :deep(.van-field) {
  background: #f5f5f5;
  border-radius: 8px;
}

.form-item :deep(.van-field__control) {
  color: #26A17B;
}

.calc-submit-btn {
  width: 100%;
  height: 44px;
  background: #26A17B;
  border: none;
  border-radius: 22px;
  color: #fff;
  font-size: 16px;
  font-weight: 600;
  margin-bottom: 20px;
}

.calc-result {
  background: #f5f5f5;
  border-radius: 8px;
  padding: 15px;
}

.result-title {
  font-size: 14px;
  font-weight: 600;
  color: #333;
  margin-bottom: 10px;
}

.result-row {
  display: flex;
  justify-content: space-between;
  font-size: 13px;
  margin-bottom: 8px;
}

.result-row:last-child {
  margin-bottom: 0;
}

.result-row .label {
  color: #999;
}

.result-row .value {
  color: #333;
}

.result-row .value.green {
  color: #26A17B;
  font-weight: 600;
}
</style>
