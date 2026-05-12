<template>
  <div class="xy28-history">
    
    <van-nav-bar
      title="历史开奖"
      left-arrow
      @click-left="onClickLeft"
    />

    
    <div class="top-tabs">
      <div class="tab-item" :class="{ active: activeTab === 'history' }" @click="activeTab = 'history'">历史开奖</div>
      <div class="tab-item" :class="{ active: activeTab === 'trend' }" @click="activeTab = 'trend'">开奖走势</div>
    </div>

    
    <div class="filter-bar">
      <div class="date-trigger" @click="showStartDatePicker = true">
        <span>{{ startDate }}</span>
        <van-icon name="arrow-down" />
      </div>
      <span class="separator">至</span>
      <div class="date-trigger" @click="showEndDatePicker = true">
        <span>{{ endDate }}</span>
        <van-icon name="arrow-down" />
      </div>
      <div class="search-btn" @click="onSearch">
        <van-icon name="search" />
        <span>查找数据</span>
      </div>
    </div>

    
    <div class="content-area">
      
      <div v-show="activeTab === 'history'" class="history-list">
        <div class="history-card" v-for="item in historyList" :key="item.issue">
          <div class="card-row top">
            <div class="balls">
              <span class="ball blue">{{ item.code[0] }}</span>
              <span class="symbol">+</span>
              <span class="ball blue">{{ item.code[1] }}</span>
              <span class="symbol">+</span>
              <span class="ball blue">{{ item.code[2] }}</span>
              <span class="symbol">=</span>
              <span class="ball red">{{ item.sum }}</span>
            </div>
            <div class="result-text">{{ item.type }}</div>
          </div>
          <div class="card-row bottom">
            <span class="issue">第{{ item.issue }}期开奖</span>
            <span class="time">{{ item.time }}</span>
          </div>
        </div>
      </div>

      
      <div v-show="activeTab === 'trend'" class="trend-table-wrapper">
        <div class="trend-header">
          <span class="col-issue">期号</span>
          <span class="col-sum">和值</span>
          <span class="col-attr">大/小</span>
          <span class="col-attr">单/双</span>
          <span class="col-attr">大/小单</span>
          <span class="col-attr">大/小双</span>
        </div>
        <div class="trend-body">
          <div class="trend-row" v-for="item in historyList" :key="item.issue">
            <span class="col-issue">{{ item.issue }}</span>
            <span class="col-sum"><span class="sum-box">{{ item.sum }}</span></span>
            
            <span class="col-attr">
              <span class="attr-box" :class="getAttrClass(item.bigSmall)">{{ item.bigSmall }}</span>
            </span>
            
            <span class="col-attr">
              <span class="attr-box" :class="getAttrClass(item.oddEven)">{{ item.oddEven }}</span>
            </span>
            
            <span class="col-attr">
              <span class="attr-box" :class="getAttrClass(item.bsOdd)">{{ item.bsOdd }}</span>
            </span>
            
            <span class="col-attr">
              <span class="attr-box" :class="getAttrClass(item.bsEven)">{{ item.bsEven }}</span>
            </span>
          </div>
        </div>
      </div>
    </div>

    
    <van-popup v-model:show="showStartDatePicker" position="bottom">
      <van-date-picker
        v-model="currentDate"
        title="选择开始日期"
        @confirm="onConfirmStartDate"
        @cancel="showStartDatePicker = false"
      />
    </van-popup>
    <van-popup v-model:show="showEndDatePicker" position="bottom">
      <van-date-picker
        v-model="currentDate"
        title="选择结束日期"
        @confirm="onConfirmEndDate"
        @cancel="showEndDatePicker = false"
      />
    </van-popup>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { xy28Api } from '@/api'
import dayjs from 'dayjs'

const router = useRouter()
const route = useRoute()

const lotteryCode = route.params.code || 'yfxy28'

const activeTab = ref(route.query.tab || 'history')
const startDate = ref(dayjs().subtract(7, 'day').format('YYYY-MM-DD'))
const endDate = ref(dayjs().format('YYYY-MM-DD'))
const showStartDatePicker = ref(false)
const showEndDatePicker = ref(false)
const currentDate = ref([dayjs().format('YYYY'), dayjs().format('MM'), dayjs().format('DD')])
const loading = ref(false)

const historyList = ref([])

async function loadHistory() {
  loading.value = true
  try {
    const res = await xy28Api.getHistory(lotteryCode, {
      page: 1,
      pageSize: 50,
      startDate: startDate.value,
      endDate: endDate.value
    })
    if (res.code === 0 && res.data?.list) {
      historyList.value = res.data.list.map(item => {
        const codes = item.openCode || [0, 0, 0]
        const sum = codes.reduce((a, b) => a + b, 0)
        const bigSmall = sum >= 14 ? '大' : '小'
        const oddEven = sum % 2 === 1 ? '单' : '双'
        const bsOdd = sum % 2 === 1 ? (sum >= 14 ? '大单' : '小单') : '-'
        const bsEven = sum % 2 === 0 ? (sum >= 14 ? '大双' : '小双') : '-'
        return {
          issue: item.expect,
          code: codes,
          sum: sum,
          type: bigSmall + oddEven,
          time: item.openTime ? dayjs(item.openTime).format('MM-DD HH:mm') : '',
          bigSmall,
          oddEven,
          bsOdd,
          bsEven
        }
      })
    }
  } catch (e) {
    console.error('加载历史数据失败:', e)
  }
  loading.value = false
}

onMounted(() => {
  loadHistory()
})

function onClickLeft() {
  router.back()
}

function onSearch() {
  loadHistory()
}

function onConfirmStartDate({ selectedValues }) {
  startDate.value = selectedValues.join('-')
  showStartDatePicker.value = false
}

function onConfirmEndDate({ selectedValues }) {
  endDate.value = selectedValues.join('-')
  showEndDatePicker.value = false
}

function getAttrClass(val) {
  if (val === '-') return 'bg-grey'
  if (val === '大' || val === '单') return 'bg-blue'
  if (val === '小' || val === '双') return 'bg-red'

  if (val.startsWith('大')) return 'bg-blue'
  if (val.startsWith('小')) return 'bg-red'
  return ''
}
</script>

<style lang="less" scoped>
.xy28-history {
  height: 100vh;
  overflow: hidden;
  background: #f5f5f5;
  display: flex;
  flex-direction: column;
}

.top-tabs {
  display: flex;
  justify-content: center;
  padding: 10px;
  background: #fff; 
  
  .tab-item {
    flex: 1;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 34px;
    font-size: 14px;
    color: #666;
    background: #f5f6fa;
    margin: 0 10px; 
    border-radius: 4px;
    

    margin: 0;
    
    &:first-child {
      border-radius: 6px 0 0 6px;
    }
    
    &:last-child {
      border-radius: 0 6px 6px 0;
    }
    
    &.active {
      background: #5691fe;
      color: #fff;
    }
  }
}

.filter-bar {
  display: flex;
  align-items: center;
  justify-content: space-between; 
  padding: 12px 16px;
  background: #f7f8fa; 
  font-size: 14px;
  color: #5691fe;
  
  .date-trigger {
    display: flex;
    align-items: center;
    gap: 4px;
    cursor: pointer;
  }
  
  .separator {
    color: #5691fe; 
    margin: 0 4px;
    font-weight: bold;
  }
  
  .search-btn {
    display: flex;
    align-items: center;
    gap: 4px;
    cursor: pointer;
    font-weight: 500;
  }
}

.content-area {
  flex: 1;
  overflow-y: auto;
  padding: 0; 
}

.history-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding: 10px;
  
  .history-card {
    background: #fff;
    border-radius: 8px;
    padding: 16px; 
    display: flex;
    flex-direction: column;
    gap: 16px; 
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    
    .card-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      
      &.top {
        .balls {
          display: flex;
          align-items: center;
          gap: 8px; 
          
          .ball {
            width: 32px; 
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px; 
            color: #fff;
            font-family: Arial, sans-serif;
            
            &.blue { background: #5691fe; }
            &.red { background: #f5222d; }
          }
          
          .symbol { 
              color: #333; 
              font-weight: bold; 
              font-size: 18px;
          }
        }
        
        .result-text {
          font-size: 13px;
          color: #999;
          text-align: right;
        }
      }
      
      &.bottom {
        font-size: 13px;
        color: #999;
        display: flex;
        justify-content: space-between;
        width: 100%;
        
        .issue { font-family: Din; }
        .time { font-family: Din; }
      }
    }
  }
}

.trend-table-wrapper {
  background: #fff;
  
  .trend-header {
    display: flex;
    background: #f7f8fa; 
    height: 40px; 
    align-items: center;
    border-bottom: 1px solid #eee;
    
    span {
      flex: 1;
      text-align: center;
      font-size: 13px;
      color: #666;
      
      &.col-issue { flex: 1.8; } 
    }
  }
  
  .trend-body {
    .trend-row {
      display: flex;
      height: 50px; // 1.33333rem approx 50px
      border-bottom: 1px solid #e1e1e1; 
      background-color: #fff;
      align-items: center;
      
      span {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
        
        &.col-issue { 
            flex: 1.8; 
            font-family: Din; 
            font-size: 13px; 
            color: #333;
        }
      }
      
      .sum-box {
        background: #eee; 
        width: 51.59px;
        height: 34.39px;
        min-width: 51.59px; 
        min-height: 34.39px; 
        flex: none; 
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
        color: #333;
        font-size: 13px;
      }
      
      .col-attr {
        font-size: 13px;
        color: #333; 
        
        .attr-box {
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            font-size: 13px;
            

            width: 51.59px;
            height: 34.39px;
            min-width: 51.59px; 
            min-height: 34.39px; 
            flex: none; 
            
            &.bg-blue {
                background: #5691fe;
                color: #fff;
            }
            
            &.bg-red {
                background: #f5222d;
                color: #fff;
            }
            
            &.bg-grey {
                background: #eee;
                color: #999;
            }
        }
      }
    }
  }
}
</style>
