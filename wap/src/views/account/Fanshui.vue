<template>
  <ActivityDetailBase title="每日反水">
    <template #banner>
      <div class="banner-area">
        <div class="glass-card stats-card">
          <div class="stats-icon">
            <i class="iconfont icon-fanshui"></i>
          </div>
          <div class="stats-info">
            <div class="label">当前可领返水</div>
            <div class="value">{{ jljine }}</div>
          </div>
          <van-button 
            class="claim-btn" 
            :disabled="!canClaim || jljine <= 0"
            :loading="loading"
            @click="claimFanshui"
          >
            立即领取
          </van-button>
        </div>
      </div>
    </template>

    <div class="glass-card list-card" v-if="lqlist.length > 0">
      <div class="card-header">
        <span class="title">领取记录</span>
      </div>
      <div class="table-responsive">
        <table class="cyber-table">
          <thead>
            <tr>
              <th>时间</th>
              <th>投注额</th>
              <th>比例</th>
              <th>金额</th>
              <th>状态</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in lqlist" :key="item.id">
              <td>{{ formatTime(item.oddtime) }}</td>
              <td>{{ item.touzhuedu }}</td>
              <td>{{ item.bili }}</td>
              <td class="gold">{{ item.amount }}</td>
              <td>
                <span v-if="item.shenhe === 0" class="status-audit">审核中</span>
                <span v-else class="status-pass">通过</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    
    <div v-else class="empty-state">
      <van-empty description="暂无反水记录" />
    </div>

    <div class="rules-section">
      <div class="rule-title">活动说明</div>
      <ul class="rule-list">
        <li>每天限领取一次反水佣金。</li>
        <li>反水金额根据您的有效投注额和会员等级计算。</li>
        <li>领取后系统将自动审核，审核通过后即刻到账。</li>
      </ul>
    </div>

  </ActivityDetailBase>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { showToast, showDialog } from 'vant'
import ActivityDetailBase from '@/components/activity/ActivityDetailBase.vue'
import request from '@/api/request'

const router = useRouter()

const loading = ref(false)
const canClaim = ref(false)
const jljine = ref(0)
const countamount = ref(0) // 昨日投注额
const fanshuibili = ref('0') // 反水比例
const userLevel = ref('')
const lqlist = ref([])

const formatTime = (timestamp) => {

  if (typeof timestamp === 'string') {
    return timestamp.slice(5, 16).replace(' ', ' ')
  }
  const date = new Date(timestamp * 1000)
  const m = String(date.getMonth() + 1).padStart(2, '0')
  const d = String(date.getDate()).padStart(2, '0')
  const h = String(date.getHours()).padStart(2, '0')
  const min = String(date.getMinutes()).padStart(2, '0')
  return `${m}-${d} ${h}:${min}`
}

const fetchRebateInfo = async () => {
  try {
    loading.value = true

    const res = await request.get('/api/v1/rebate/summary')
    if (res.code === 0 && res.data) {
      jljine.value = parseFloat(res.data.claimable) || 0
      countamount.value = parseFloat(res.data.totalBet) || 0
      fanshuibili.value = res.data.rate || '0'
      userLevel.value = res.data.vipLevel || ''
      canClaim.value = jljine.value > 0
    }
  } catch (err) {
    console.error('获取反水信息失败:', err)

    try {
      const res = await request.get('/api/v1/activity/daily-reward')
      if (res.code === 0 && res.data) {
        jljine.value = parseFloat(res.data.jiajiang) || 0
        countamount.value = parseFloat(res.data.countamount) || 0
        fanshuibili.value = res.data.fanshuibili || '0'
        userLevel.value = res.data.userLevel || ''
        canClaim.value = res.data.canClaim || false
      }
    } catch (e) {
      console.error('降级获取反水信息失败:', e)
    }
  } finally {
    loading.value = false
  }
}

const fetchRebateRecords = async () => {
  try {
    const res = await request.get('/api/v1/rebate/list', {
      params: { page: 1, pageSize: 20 }
    })
    if (res.code === 0 && res.data) {
      lqlist.value = res.data.list || []
    }
  } catch (err) {
    console.error('获取反水记录失败:', err)
    lqlist.value = []
  }
}

const claimFanshui = async () => {
  if (!canClaim.value || jljine.value <= 0) {
    showToast('暂无可领取的反水')
    return
  }
  
  try {
    showToast({ type: 'loading', message: '领取中...', forbidClick: true })
    

    const res = await request.post('/api/v1/rebate/claim', { type: 1 })
    
    if (res.code === 0) {
      showDialog({ 
        title: '领取成功', 
        message: `成功领取 ${res.data?.amount || jljine.value} 元！` 
      })
      jljine.value = 0
      canClaim.value = false

      await fetchRebateInfo()
      await fetchRebateRecords()
    } else {
      showToast(res.message || '领取失败')
    }

  } catch (err) {
    showToast('网络错误，请重试')
  }
}

onMounted(() => {
  fetchRebateInfo()
  fetchRebateRecords()
})
</script>


<style scoped>
.banner-area {
  padding: 10px 0;
}

.glass-card {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 16px;
  padding: 20px;
  backdrop-filter: blur(10px);
  margin-bottom: 20px;
}

.stats-card {
  display: flex;
  align-items: center;
  background: linear-gradient(135deg, rgba(255,255,255,0.05) 0%, rgba(255,255,255,0.02) 100%);
}

.stats-icon {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background: rgba(234, 194, 110, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 15px;
}

.stats-icon .iconfont {
  font-size: 24px;
  color: #EAC26E;
}

.stats-info {
  flex: 1;
}

.stats-info .label {
  font-size: 12px;
  color: #94a3b8;
  margin-bottom: 4px;
}

.stats-info .value {
  font-size: 24px;
  font-weight: bold;
  color: #EAC26E;
  font-family: monospace;
}

.claim-btn {
  background: linear-gradient(90deg, #EAC26E, #b45309);
  border: none;
  color: #fff;
  font-weight: bold;
  border-radius: 8px;
  height: 36px;
  padding: 0 20px;
}

.list-card {
  padding: 0;
  overflow: hidden;
}

.card-header {
  padding: 15px;
  border-bottom: 1px solid rgba(255,255,255,0.05);
}

.card-header .title {
  font-size: 15px;
  font-weight: bold;
  color: #fff;
  border-left: 3px solid #EAC26E;
  padding-left: 10px;
}

.table-responsive {
  overflow-x: auto;
}

.cyber-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 12px;
  text-align: center;
}

.cyber-table th {
  color: #94a3b8;
  padding: 12px;
  font-weight: normal;
  border-bottom: 1px solid rgba(255,255,255,0.05);
  white-space: nowrap;
}

.cyber-table td {
  color: #e2e8f0;
  padding: 12px;
  border-bottom: 1px solid rgba(255,255,255,0.02);
  white-space: nowrap;
}

.gold { color: #EAC26E; font-weight: bold; }
.status-audit { color: #94a3b8; }
.status-pass { color: #10b981; }

.rules-section {
  padding: 0 10px;
  margin-top: 30px;
}

.rule-title {
  font-size: 14px;
  color: #EAC26E;
  margin-bottom: 10px;
  font-weight: bold;
}

.rule-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.rule-list li {
  font-size: 12px;
  color: #64748b;
  margin-bottom: 8px;
  line-height: 1.5;
  position: relative;
  padding-left: 12px;
}

.rule-list li::before {
  content: '';
  position: absolute;
  left: 0;
  top: 6px;
  width: 4px;
  height: 4px;
  background: #64748b;
  border-radius: 50%;
}
</style>
