<template>
  <van-popup 
    :show="show" 
    @update:show="$emit('update:show', $event)" 
    position="bottom" 
    round 
    class="chase-records-popup"
  >
    
    <div class="popup-header">
      <div class="title-bar">
        <div class="close-btn" @click="$emit('update:show', false)">
          <van-icon name="arrow-down" />
          <span>收起</span>
        </div>
        <span class="title">追号记录</span>
        <div class="filter-btn" @click="showFilter = true">
          <span>记录筛选</span>
        </div>
      </div>
      <div class="tab-switch">
        <div 
          class="tab-btn" 
          :class="{ active: currentTab === 'all' }" 
          @click="currentTab = 'all'"
        >全部</div>
        <div 
          class="tab-btn" 
          :class="{ active: currentTab === 'ongoing' }" 
          @click="currentTab = 'ongoing'"
        >进行中</div>
        <div 
          class="tab-btn" 
          :class="{ active: currentTab === 'finished' }" 
          @click="currentTab = 'finished'"
        >已结束</div>
      </div>
    </div>

    
    <div class="popup-content">
      <div class="records-list" v-if="filteredRecords.length > 0">
        <div class="record-item" v-for="item in filteredRecords" :key="item.id">
          <div class="record-header">
            <span class="record-issue">{{ item.startIssue }} - {{ item.endIssue }}</span>
            <span class="record-status" :class="item.status">{{ getStatusText(item.status) }}</span>
          </div>
          <div class="record-body">
            <div class="record-info">
              <span class="label">追号期数:</span>
              <span class="value">{{ item.periods }}期</span>
            </div>
            <div class="record-info">
              <span class="label">投注内容:</span>
              <span class="value">{{ item.betContent }}</span>
            </div>
            <div class="record-info">
              <span class="label">总金额:</span>
              <span class="value amount">¥{{ item.totalAmount }}</span>
            </div>
          </div>
          <div class="record-footer">
            <span class="record-time">{{ item.createTime }}</span>
            <span class="record-action" v-if="item.status === 'ongoing'" @click="cancelChase(item)">取消追号</span>
          </div>
        </div>
      </div>
      
      
      <div class="empty-state" v-else>
        <div class="empty-icon">
          <van-icon name="search" size="48" color="#ddd" />
        </div>
        <p>目前暂无数据</p>
      </div>
    </div>
  </van-popup>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { showToast, showConfirmDialog } from 'vant'
import { xy28Api } from '@/api'

const props = defineProps({
  show: Boolean,
  lotteryCode: String
})

const emit = defineEmits(['update:show'])

const currentTab = ref('ongoing')
const showFilter = ref(false)
const loading = ref(false)
const records = ref([])

const filteredRecords = computed(() => {
  if (currentTab.value === 'all') return records.value
  if (currentTab.value === 'finished') {

    return records.value.filter(r => r.status === 'finished' || r.status === 'cancelled')
  }
  return records.value.filter(r => r.status === currentTab.value)
})

function getStatusText(status) {
  const map = {
    'ongoing': '进行中',
    'finished': '已结束',
    'cancelled': '已取消'
  }
  return map[status] || status
}

async function loadRecords() {
  loading.value = true
  try {
    const params = {
      lotteryCode: props.lotteryCode || ''
    }
    const res = await xy28Api.getChaseRecords(params)
    if (res.code === 0 && res.data?.list) {
      records.value = res.data.list.map(item => ({
        id: item.id || item.chaseNo,
        chaseNo: item.chaseNo || item.id,
        startIssue: item.startIssue || item.start_issue,
        endIssue: item.endIssue || item.end_issue,
        periods: item.periods || item.totalPeriods,
        drawnPeriods: item.drawnPeriods || 0,
        pendingPeriods: item.pendingPeriods || 0,
        betContent: item.betContent || item.bet_content || item.tzcode,
        totalAmount: item.totalAmount || item.total_amount,
        totalWin: item.totalWin || '0.00',
        status: item.status,
        createTime: item.createTime || item.created_at
      }))
    } else {
      records.value = [] 
    }
  } catch (e) {
    console.error('加载追号记录失败:', e)
    records.value = []
  }
  loading.value = false
}

async function cancelChase(item) {
  try {
    await showConfirmDialog({
      title: '确认取消',
      message: `确定要取消追号 ${item.chaseNo || item.id} 吗？剩余${item.pendingPeriods || '?'}期将退款。`
    })
    

    const chaseNo = item.chaseNo || item.id
    const res = await xy28Api.cancelChase(chaseNo)
    if (res.code === 0) {
      showToast({ type: 'success', message: `已取消，退款 ¥${res.data?.refundAmount || '0'}` })
      loadRecords()
    } else {
      showToast(res.message || '取消失败')
    }
  } catch {

  }
}

watch(() => props.show, (val) => {
  if (val) {
    loadRecords()
  }
})
</script>

<style lang="less" scoped>
.chase-records-popup {
  height: 70vh !important;
  max-height: 80vh;
  background: #f7f8fa;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.popup-header {
  flex-shrink: 0;
  background: #fff;
  padding: 12px 14px;
  border-radius: 16px 16px 0 0;
  box-shadow: 0 1px 2px rgba(0,0,0,0.05);
  
  .title-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
    
    .title {
      font-size: 16px;
      font-weight: 600;
      color: #333;
    }
    
    .close-btn {
      display: flex;
      align-items: center;
      gap: 4px;
      color: #5691fe;
      font-size: 14px;
      font-weight: 500;
      min-width: 60px;
    }
    
    .filter-btn {
      color: #666;
      font-size: 14px;
      min-width: 60px;
      text-align: right;
    }
  }
  
  .tab-switch {
    display: flex;
    background: #f0f2f5;
    border-radius: 8px;
    padding: 4px;
    
    .tab-btn {
      flex: 1;
      text-align: center;
      padding: 8px 0;
      font-size: 14px;
      font-weight: 500;
      color: #666;
      border-radius: 6px;
      transition: all 0.3s;
      
      &.active {
        background: linear-gradient(135deg, #ff7e5f, #feb47b);
        color: #fff;
        font-weight: 600;
        box-shadow: 0 2px 8px rgba(255, 126, 95, 0.3);
      }
    }
  }
}

.popup-content {
  flex: 1;
  overflow-y: auto;
  min-height: 0;
  padding: 12px;
  padding-bottom: calc(60px + env(safe-area-inset-bottom)); 
  -webkit-overflow-scrolling: touch;
  
  .records-list {
    .record-item {
      background: #fff;
      border-radius: 12px;
      padding: 12px;
      margin-bottom: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.02);
      
      .record-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
        padding-bottom: 10px;
        border-bottom: 1px solid #f5f5f5;
        
        .record-issue {
          font-size: 14px;
          font-weight: 600;
          color: #333;
        }
        
        .record-status {
          font-size: 12px;
          padding: 2px 8px;
          border-radius: 4px;
          
          &.ongoing {
            background: #e6f7ff;
            color: #1890ff;
          }
          
          &.finished {
            background: #f6ffed;
            color: #52c41a;
          }
          
          &.cancelled {
            background: #f5f5f5;
            color: #999;
          }
        }
      }
      
      .record-body {
        .record-info {
          display: flex;
          justify-content: space-between;
          margin-bottom: 6px;
          font-size: 13px;
          
          .label {
            color: #999;
          }
          
          .value {
            color: #333;
            
            &.amount {
              color: #f5222d;
              font-weight: 600;
            }
          }
        }
      }
      
      .record-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px solid #f5f5f5;
        
        .record-time {
          font-size: 12px;
          color: #999;
        }
        
        .record-action {
          font-size: 13px;
          color: #f5222d;
        }
      }
    }
  }
  
  .empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 300px;
    color: #999;
    
    .empty-icon {
      width: 80px;
      height: 80px;
      background: #f5f5f5;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 16px;
    }
    
    p {
      font-size: 14px;
      color: #999;
    }
  }
}
</style>
