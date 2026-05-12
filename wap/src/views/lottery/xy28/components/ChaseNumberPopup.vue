<template>
  <van-popup 
    v-model:show="visible" 
    position="bottom" 
    round
    :style="{ maxHeight: '90vh' }"
    teleport="body"
  >
    <div class="chase-popup">
      
      <div class="chase-header">
        <div class="collapse-btn" @click="close">
          <van-icon name="arrow-down" />
          <span>收起</span>
        </div>
        <div class="title">追号</div>
        <div class="placeholder"></div>
      </div>

      
      <div class="chase-body">
        
        <div class="chase-row">
          <span class="label">起始期号</span>
          <div class="value issue-selector" @click="showIssuePicker = true">
            <span class="tag" v-if="startIssueOffset === 0">当前期</span>
            <span class="issue">{{ selectedStartIssue }}</span>
            <van-icon name="arrow-up" class="toggle-icon" :class="{ down: !showIssuePicker }" />
          </div>
        </div>
        
        
        <van-action-sheet 
          v-model:show="showIssuePicker" 
          :actions="issueOptions"
          cancel-text="取消"
          @select="onIssueSelect"
        />

        
        <div class="chase-row">
          <span class="label">追号期数</span>
          <div class="value">
            <div class="stepper-wrap">
              <button class="stepper-btn" @click="decreasePeriod">－</button>
              <input type="number" v-model.number="chasePeriod" class="stepper-input" />
              <button class="stepper-btn" @click="increasePeriod">＋</button>
            </div>
          </div>
        </div>
        <div class="quick-btns">
          <button 
            v-for="p in periodOptions" 
            :key="p" 
            :class="['quick-btn', { active: chasePeriod === p }]"
            @click="chasePeriod = p"
          >{{ p }}期</button>
        </div>

        
        <div class="chase-row">
          <span class="label">追号类型</span>
          <div class="value">
            <div class="type-btns">
              <button 
                :class="['type-btn', { active: chaseType === 'flat' }]"
                @click="chaseType = 'flat'"
              >平倍追号</button>
              <button 
                :class="['type-btn', { active: chaseType === 'double' }]"
                @click="chaseType = 'double'"
              >翻倍追号</button>
            </div>
          </div>
        </div>

        
        <div class="chase-row">
          <span class="label">起始倍数</span>
          <div class="value">
            <div class="stepper-wrap">
              <button class="stepper-btn" @click="decreaseMultiple">－</button>
              <input type="number" v-model.number="startMultiple" class="stepper-input" />
              <button class="stepper-btn" @click="increaseMultiple">＋</button>
            </div>
          </div>
        </div>

        
        <div class="chase-row double-rule" v-if="chaseType === 'double'">
          <span class="label-inline">每隔</span>
          <div class="stepper-wrap small">
            <button class="stepper-btn" @click="decreaseInterval">－</button>
            <input type="number" v-model.number="doubleInterval" class="stepper-input" />
            <button class="stepper-btn" @click="increaseInterval">＋</button>
          </div>
          <span class="label-inline">期 金额翻</span>
          <div class="stepper-wrap small">
            <button class="stepper-btn" @click="decreaseDoubleRate">－</button>
            <input type="number" v-model.number="doubleRate" class="stepper-input" />
            <button class="stepper-btn" @click="increaseDoubleRate">＋</button>
          </div>
          <span class="label-inline">倍</span>
        </div>

        
        <div class="chase-row">
          <van-checkbox v-model="stopOnWin" icon-size="18px">
            <span class="checkbox-label">中奖即停</span>
          </van-checkbox>
        </div>

        
        <div class="detail-toggle" @click="showDetail = !showDetail">
          <span class="detail-title">追号详情</span>
          <van-icon :name="showDetail ? 'arrow-up' : 'arrow-down'" color="#2196F3" />
        </div>

        <div class="detail-table" v-show="showDetail">
          <div class="table-header">
            <span class="col">期数</span>
            <span class="col">期号</span>
            <span class="col">倍数</span>
            <span class="col">投注金额（元）</span>
          </div>
          <div class="table-body">
            <div class="table-row" v-for="(item, index) in chaseList" :key="index">
              <span class="col">第{{ index + 1 }}期</span>
              <span class="col">{{ item.issue }}</span>
              <span class="col">
                <span class="multiple-tag">{{ item.multiple }}倍</span>
              </span>
              <span class="col">{{ item.amount }}</span>
            </div>
          </div>
        </div>
      </div>

      
      <div class="chase-footer">
        <div class="summary">
          <div class="summary-line">
            共 <span class="highlight">{{ chasePeriod }}</span>单，合计 <span class="highlight">{{ totalAmount }}</span>元
          </div>
          <div class="balance-line">
            余额: <span class="balance">{{ balance }}</span>
          </div>
        </div>
        <button class="confirm-btn" @click="confirmChase">追号确认</button>
      </div>
    </div>
  </van-popup>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { showToast, showLoadingToast, closeToast } from 'vant'
import { xy28Api } from '@/api'

const props = defineProps({
  show: Boolean,
  currentIssue: { type: String, default: '202512100535' },
  betAmount: { type: Number, default: 10 },
  balance: { type: [String, Number], default: '0.00' },
  lotteryCode: { type: String, default: 'yfxy28' },
  betData: { type: Object, default: () => ({}) } // 投注内容
})

const emit = defineEmits(['update:show', 'confirm', 'success'])

const visible = computed({
  get: () => props.show,
  set: (val) => emit('update:show', val)
})

const showIssuePicker = ref(false)
const startIssueOffset = ref(0) // 0=当前期, 1=下一期, 2=下下期...

const issueOptions = computed(() => {
  const baseIssue = parseInt(props.currentIssue) || 0
  const options = []
  for (let i = 0; i < 10; i++) {
    options.push({
      name: i === 0 ? `当前期 ${baseIssue + i}` : String(baseIssue + i),
      value: i
    })
  }
  return options
})

const selectedStartIssue = computed(() => {
  const baseIssue = parseInt(props.currentIssue) || 0
  return String(baseIssue + startIssueOffset.value)
})

function onIssueSelect(action) {
  startIssueOffset.value = action.value
  showIssuePicker.value = false
}

const chasePeriod = ref(20)
const periodOptions = [5, 10, 15, 20]

const chaseType = ref('flat')

const startMultiple = ref(1)

const doubleInterval = ref(1) // 每隔X期
const doubleRate = ref(2)     // 金额翻X倍

const stopOnWin = ref(true)

const showDetail = ref(false)

const chaseList = computed(() => {
  const list = []
  let issueNum = parseInt(selectedStartIssue.value)
  let currentMultiple = startMultiple.value
  
  for (let i = 0; i < chasePeriod.value; i++) {
    if (chaseType.value === 'flat') {

      currentMultiple = startMultiple.value
    } else {

      if (i === 0) {
        currentMultiple = startMultiple.value
      } else if (i % doubleInterval.value === 0) {
        currentMultiple = currentMultiple * doubleRate.value
      }
    }
    
    list.push({
      issue: String(issueNum + i),
      multiple: currentMultiple,
      amount: props.betAmount * currentMultiple
    })
  }
  return list
})

const totalAmount = computed(() => {
  return chaseList.value.reduce((sum, item) => sum + item.amount, 0)
})

function decreasePeriod() {
  if (chasePeriod.value > 1) chasePeriod.value--
}

function increasePeriod() {
  if (chasePeriod.value < 100) chasePeriod.value++
}

function decreaseMultiple() {
  if (startMultiple.value > 1) startMultiple.value--
}

function increaseMultiple() {
  if (startMultiple.value < 100) startMultiple.value++
}

function decreaseInterval() {
  if (doubleInterval.value > 1) doubleInterval.value--
}

function increaseInterval() {
  if (doubleInterval.value < 20) doubleInterval.value++
}

function decreaseDoubleRate() {
  if (doubleRate.value > 2) doubleRate.value--
}

function increaseDoubleRate() {
  if (doubleRate.value < 10) doubleRate.value++
}

function close() {
  emit('update:show', false)
}

async function confirmChase() {
  const chaseData = {
    period: chasePeriod.value,
    type: chaseType.value,
    multiple: startMultiple.value,
    stopOnWin: stopOnWin.value,
    list: chaseList.value,
    total: totalAmount.value
  }
  

  emit('confirm', chaseData)
  

  if (Object.keys(props.betData).length > 0) {
    try {
      showLoadingToast({ message: '提交中...', duration: 0 })
      const res = await xy28Api.submitChase({
        lotteryCode: props.lotteryCode,
        playType: props.betData.playType,
        betData: props.betData,
        unitPrice: props.betAmount,
        multiplier: startMultiple.value,
        chaseConfig: {
          startExpect: selectedStartIssue.value,
          periods: chasePeriod.value,
          stopOnWin: stopOnWin.value,
          multipliers: chaseList.value.map(item => item.multiple)
        }
      })
      closeToast()
      if (res.code === 0) {
        showToast({ type: 'success', message: '追号成功' })
        emit('success', res.data)
        emit('update:show', false)
      } else {
        showToast(res.message || '追号失败')
      }
    } catch (e) {
      closeToast()
      showToast('网络错误')
    }
  }
}
</script>

<style lang="less" scoped>
.chase-popup {
  background: #fff;
  display: flex;
  flex-direction: column;
  max-height: 90vh;
  overflow: hidden;
}

.chase-body {
  flex: 1;
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
}

.chase-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px;
  border-bottom: 1px solid #f0f0f0;
  
  .collapse-btn {
    display: flex;
    align-items: center;
    gap: 4px;
    color: #2196F3;
    font-size: 14px;
  }
  
  .title {
    font-size: 16px;
    font-weight: 600;
    color: #333;
  }
  
  .placeholder {
    width: 60px;
  }
}

.chase-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 16px;
  border-bottom: 1px solid #f5f5f5;
  
  .label {
    font-size: 14px;
    color: #333;
  }
  
  .value {
    display: flex;
    align-items: center;
    gap: 8px;
    
    .tag {
      background: #E3F2FD;
      color: #2196F3;
      font-size: 12px;
      padding: 4px 10px;
      border-radius: 4px;
    }
    
    .issue {
      font-size: 14px;
      color: #333;
      font-weight: 500;
    }
    
    .toggle-icon {
      color: #2196F3;
      transition: transform 0.2s;
      
      &.down {
        transform: rotate(180deg);
      }
    }
    
    &.issue-selector {
      cursor: pointer;
      
      &:active {
        opacity: 0.7;
      }
    }
  }
}

.stepper-wrap {
  display: flex;
  align-items: center;
  border: 1px solid #e0e0e0;
  border-radius: 4px;
  overflow: hidden;
  
  .stepper-btn {
    width: 36px;
    height: 32px;
    background: #f5f5f5;
    border: none;
    font-size: 18px;
    color: #666;
    
    &:active {
      background: #e0e0e0;
    }
  }
  
  .stepper-input {
    width: 50px;
    height: 32px;
    border: none;
    text-align: center;
    font-size: 14px;
    color: #333;
    
    &::-webkit-inner-spin-button,
    &::-webkit-outer-spin-button {
      -webkit-appearance: none;
    }
  }
  
  &.small {
    .stepper-btn {
      width: 28px;
      height: 28px;
      font-size: 16px;
    }
    .stepper-input {
      width: 40px;
      height: 28px;
      font-size: 13px;
    }
  }
}

.double-rule {
  justify-content: flex-start;
  gap: 8px;
  flex-wrap: wrap;
  
  .label-inline {
    font-size: 14px;
    color: #333;
  }
}

.quick-btns {
  display: flex;
  gap: 10px;
  padding: 0 16px 14px;
  
  .quick-btn {
    flex: 1;
    height: 32px;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    background: #fff;
    font-size: 13px;
    color: #666;
    
    &.active {
      background: #2196F3;
      border-color: #2196F3;
      color: #fff;
    }
  }
}

.type-btns {
  display: flex;
  gap: 10px;
  
  .type-btn {
    padding: 6px 16px;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    background: #fff;
    font-size: 13px;
    color: #666;
    
    &.active {
      background: #2196F3;
      border-color: #2196F3;
      color: #fff;
    }
  }
}

.checkbox-label {
  font-size: 14px;
  color: #333;
}

.detail-toggle {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 12px;
  
  .detail-title {
    font-size: 14px;
    color: #2196F3;
  }
}

.detail-table {
  margin: 0 16px 16px;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  overflow: hidden;
  
  .table-header {
    display: flex;
    background: #f5f5f5;
    padding: 10px 0;
    
    .col {
      flex: 1;
      text-align: center;
      font-size: 12px;
      color: #666;
    }
  }
  
  .table-body {
    max-height: 200px;
    overflow-y: auto;
  }
  
  .table-row {
    display: flex;
    padding: 10px 0;
    border-top: 1px solid #f0f0f0;
    
    .col {
      flex: 1;
      text-align: center;
      font-size: 13px;
      color: #333;
    }
    
    .multiple-tag {
      display: inline-block;
      padding: 2px 12px;
      border: 1px solid #e0e0e0;
      border-radius: 4px;
      font-size: 12px;
    }
  }
}

.chase-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  padding-bottom: calc(12px + env(safe-area-inset-bottom));
  background: #f5f5f5;
  flex-shrink: 0;
  border-top: 1px solid #e0e0e0;
  
  .summary {
    .summary-line {
      font-size: 14px;
      color: #333;
      
      .highlight {
        color: #2196F3;
        font-weight: 600;
      }
    }
    
    .balance-line {
      font-size: 12px;
      color: #666;
      margin-top: 4px;
      
      .balance {
        color: #ff5722;
      }
    }
  }
  
  .confirm-btn {
    padding: 12px 32px;
    background: #2196F3;
    border: none;
    border-radius: 24px;
    color: #fff;
    font-size: 15px;
    font-weight: 500;
    
    &:active {
      background: #1976D2;
    }
  }
}
</style>
