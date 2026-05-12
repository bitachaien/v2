<template>
  <div class="tab-pending">
    <van-pull-refresh v-model="refreshing" @refresh="onRefresh">
      <van-loading v-if="loading" type="spinner" size="24" style="margin: 40px auto;">加载中...</van-loading>
      
      <div class="pending-empty" v-else-if="pendingList.length === 0">
        <van-icon name="gift-o" size="60" color="#ccc" />
        <p>暂无待领取奖励</p>
      </div>
      
      <div class="pending-list" v-else>
        <div class="pending-item" v-for="item in pendingList" :key="item.id">
          <div class="item-info">
            <span class="item-title">{{ item.activityTitle || item.rewardType }}</span>
            <span class="item-time">{{ formatTime(item.applyTime) }}</span>
          </div>
          <div class="item-right">
            <span class="item-amount">¥ {{ item.rewardAmount }}</span>
            <span class="item-status pending">待审核</span>
          </div>
        </div>
      </div>
    </van-pull-refresh>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { showToast } from 'vant'
import { activityApi } from '@/api/activity'

const loading = ref(false)
const refreshing = ref(false)
const pendingList = ref([])

const loadPendingRewards = async () => {
  loading.value = true
  try {
    const res = await activityApi.getParticipationHistory({ status: 0 })
    if (res.code === 0 && res.data) {
      pendingList.value = res.data.list || []
    }
  } catch (e) {
    showToast('加载失败')
  } finally {
    loading.value = false
    refreshing.value = false
  }
}

const onRefresh = () => {
  loadPendingRewards()
}

const formatTime = (timestamp) => {
  if (!timestamp) return ''
  const d = new Date(timestamp * 1000)
  return `${d.getMonth() + 1}/${d.getDate()} ${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`
}

onMounted(() => {
  loadPendingRewards()
})
</script>

<style scoped>
.tab-pending {
  flex: 1;
  overflow-y: auto;
  padding: 15px;
  background: #f7f8fa;
  min-height: 300px;
}
.pending-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 300px;
  color: #999;
}
.pending-empty p { margin-top: 15px; font-size: 14px; }
.pending-list { background: #fff; border-radius: 12px; overflow: hidden; }
.pending-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px;
  border-bottom: 1px solid #f0f0f0;
}
.pending-item:last-child { border-bottom: none; }
.item-info { display: flex; flex-direction: column; gap: 4px; }
.item-title { font-size: 14px; color: #333; }
.item-time { font-size: 12px; color: #999; }
.item-right { display: flex; flex-direction: column; align-items: flex-end; gap: 4px; }
.item-amount { font-size: 16px; font-weight: bold; color: #ff6b00; }
.item-status { font-size: 12px; padding: 2px 8px; border-radius: 10px; }
.item-status.pending { background: #fff3e0; color: #ff9800; }
</style>
