<template>
  <div class="hemai-records-tech">
    <div class="bg-overlay"></div>
    <div class="tech-bg-grid"></div>

    <van-nav-bar
      title="Hợp mãi của tôi"
      left-arrow
      fixed
      placeholder
      @click-left="router.back()"
      class="tech-nav"
    />

    <div class="tech-tabs-wrapper">
      <van-tabs 
        v-model:active="activeTab" 
        background="transparent"
        color="#EAC26E" 
        title-active-color="#EAC26E" 
        title-inactive-color="#64748b"
        line-width="20px"
        line-height="3px"
        :border="false"
      >
        <van-tab title="Tôi phát hành" name="create"></van-tab>
        <van-tab title="Tôi tham gia" name="join"></van-tab>
      </van-tabs>
    </div>

    <div class="content-area custom-scrollbar">
      <van-pull-refresh v-model="refreshing" @refresh="onRefresh" class="pull-refresh-box">
        <van-list
          v-model:loading="loading"
          :finished="finished"
          finished-text="Không còn nữa"
          @load="onLoad"
        >
          <div v-if="list.length === 0 && !loading" class="empty-box">
            <van-empty 
              image="search" 
              description="Chưa có bản ghi liên quan"
            />
          </div>

          <div 
            v-for="item in list" 
            :key="item.id" 
            class="glass-card-record"
            @click="showDetail(item)"
          >
            <div class="rc-header">
              <div class="rch-left">
                <img :src="getGameIconPath(item.cpname)" class="cp-icon" />
                <div class="cp-info">
                  <div class="cp-title">{{ item.cptitle }}</div>
                  <div class="cp-expect">Kỳ {{ item.expect }}</div>
                </div>
              </div>
              <div class="status-badge" :class="getStatusClass(item.status)">
                {{ getStatusText(item.status) }}
              </div>
            </div>

            <div class="rc-divider"></div>

            <div class="rc-body">
              <div class="data-row">
                <div class="data-col">
                  <span class="lbl">Loại chơi</span>
                  <span class="val">{{ item.playtitle || 'Đặt cược phức hợp' }}</span>
                </div>
                <div class="data-col text-right">
                  <span class="lbl">Tiền phương án</span>
                  <span class="val gold">{{ item.amount }}</span>
                </div>
              </div>
              
              <div class="data-row mt-10">
                <div class="data-col">
                  <span class="lbl">{{ activeTab === 'join' ? 'Số phần mua' : 'Tổng số phần' }}</span>
                  <span class="val">{{ activeTab === 'join' ? (item.my_buy_num || item.num) : item.buytotal }}</span>
                </div>
                <div class="data-col text-right">
                  <span class="lbl">Thời gian tham gia</span>
                  <span class="val time">{{ formatTime(item.createtime) }}</span>
                </div>
              </div>
            </div>

            <div class="rc-footer" v-if="item.is_win == 1 || item.win_amount > 0">
              <div class="win-bar">
                <van-icon name="gift" color="#ef4444" />
                <span class="win-text">Chúc mừng trúng thưởng</span>
                <span class="win-money">{{ item.win_amount }}đ</span>
              </div>
            </div>

            <div class="rc-actions" @click.stop>
               <van-button 
                 v-if="activeTab === 'create' && item.status === 0"
                 size="mini" plain class="btn-action" 
                 @click="handleCancel(item)"
               >Hủy đơn</van-button>

               <van-button 
                 v-if="activeTab === 'join' && item.status === 0"
                 size="mini" plain class="btn-action" 
                 @click="handleCancelJoin(item)"
               >Rút vốn</van-button>
               
               <van-button 
                 v-if="item.status === 3 || item.is_win == 1"
                 size="mini" plain class="btn-action" 
                 @click="handleCopy(item)"
               >Đặt lại</van-button>

               <van-button size="mini" plain class="btn-action highlight" @click="showDetail(item)">Xem chi tiết</van-button>
            </div>
          </div>
        </van-list>
      </van-pull-refresh>
    </div>

    <LotteryHemaiDetail 
      v-model:visible="detailVisible"
      :detail="currentDetail"
      :is-component="true"
      @buy="onBuySuccess"
    />
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { showDialog, showToast } from 'vant'
import { hemaiApi } from '@/api/hemai'
import { getGameIconPath } from '@/config/gameConfig'
import LotteryHemaiDetail from './LotteryHemaiDetail.vue'

const router = useRouter()
const activeTab = ref('create')
const list = ref([])
const loading = ref(false)
const finished = ref(false)
const refreshing = ref(false)
const page = ref(1)
const detailVisible = ref(false)
const currentDetail = ref({})

const onLoad = async () => {
  if (refreshing.value) {
    list.value = []
    page.value = 1
    refreshing.value = false
  }

  try {
    const res = await hemaiApi.getMyRecords({
      type: activeTab.value,
      page: page.value,
      pageSize: 20
    })

    if (res.code === 0) {
      const data = res.data.list || []
      if (page.value === 1) {
        list.value = data
      } else {
        list.value.push(...data)
      }
      
      if (list.value.length >= (res.data.total || 0) || data.length < 20) {
        finished.value = true
      } else {
        page.value++
      }
    } else {
      finished.value = true
    }
  } catch (e) {
    finished.value = true
  } finally {
    loading.value = false
  }
}

const onRefresh = () => {
  finished.value = false
  loading.value = true
  onLoad()
}

watch(activeTab, () => {
  onRefresh()
})

const showDetail = (item) => {
  currentDetail.value = item
  detailVisible.value = true
}

const onBuySuccess = () => {

  onRefresh()
}

const handleCancel = (item) => {
  showDialog({
    title: 'Xác nhận hủy đơn',
    message: 'Bạn có chắc muốn hủy phương án hợp mãi này? Sau khi hủy, tiền sẽ được hoàn trả.',
    showCancelButton: true
  }).then(async () => {
    const res = await hemaiApi.cancel(item.id)
    if (res.code === 0) {
      showToast('Hủy đơn thành công')
      onRefresh()
    } else {
      showToast(res.message || 'Hủy đơn thất bại')
    }
  }).catch(() => {})
}

const handleCancelJoin = (item) => {
  showDialog({
    title: 'Xác nhận rút vốn',
    message: 'Bạn có chắc muốn hủy mua này? Không thể rút vốn trong vòng 10 phút trước khi hết hạn.',
    showCancelButton: true
  }).then(async () => {
    const res = await hemaiApi.cancelJoin({ id: item.id })
    if (res.code === 0) {
      showToast('Rút vốn thành công')
      onRefresh()
    } else {
      showToast(res.message || 'Rút vốn thất bại')
    }
  }).catch(() => {})
}

const handleCopy = (item) => {
  router.push({
    name: 'LotteryHemaiCreate',
    query: {
      cpname: item.cpname,
      cptitle: item.cptitle,
      tzcode: item.tzcode || '1,2,3', // 如果没有号码，传默认或为空
      amount: item.amount,
      unitPrice: item.hemaipic,
      content: item.content
    }
  })
}

const getStatusClass = (status) => {
  const map = { 0: 'process', 1: 'full', 2: 'cancel', 3: 'end' }
  return map[status] || 'end'
}

const getStatusText = (status) => {
  const map = { 0: 'Đang theo', 1: 'Đã đủ người', 2: 'Đã hủy', 3: 'Đã hết hạn' }
  return map[status] || 'Không rõ'
}

const formatTime = (ts) => {
  if (!ts) return ''
  const date = new Date(ts * 1000)
  return `${date.getMonth()+1}-${date.getDate()} ${date.getHours().toString().padStart(2,'0')}:${date.getMinutes().toString().padStart(2,'0')}`
}
</script>

<style scoped>
.hemai-records-tech {
  min-height: 100vh;
  background: #0B0E15;
  background: linear-gradient(180deg, #111118 0%, #050508 100%);
  color: #fff;
  font-family: 'PingFang SC', sans-serif;
  padding-top: 46px;
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

.tech-tabs-wrapper {
  background: rgba(11, 14, 21, 0.6);
  backdrop-filter: blur(8px);
  position: sticky; top: 46px; z-index: 10;
  border-bottom: 1px solid rgba(255,255,255,0.03);
}

.content-area {
  position: relative; z-index: 1; padding: 15px;
  min-height: calc(100vh - 90px);
}

.glass-card-record {
  background: rgba(30, 35, 50, 0.4);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 12px;
  margin-bottom: 15px;
  padding: 15px;
  backdrop-filter: blur(10px);
  transition: all 0.2s;
}
.glass-card-record:active { transform: scale(0.98); background: rgba(30, 35, 50, 0.6); }

.rc-header { display: flex; justify-content: space-between; align-items: center; }
.rch-left { display: flex; align-items: center; gap: 10px; }
.cp-icon { width: 36px; height: 36px; border-radius: 8px; background: rgba(255,255,255,0.05); padding: 4px; }
.cp-info { display: flex; flex-direction: column; gap: 2px; }
.cp-title { font-size: 15px; font-weight: bold; color: #fff; }
.cp-expect { font-size: 11px; color: #64748b; }

.status-badge { font-size: 11px; padding: 2px 8px; border-radius: 4px; font-weight: 500; }
.status-badge.process { background: rgba(16, 185, 129, 0.15); color: #10b981; }
.status-badge.full { background: rgba(59, 130, 246, 0.15); color: #60a5fa; }
.status-badge.end { background: rgba(148, 163, 184, 0.15); color: #94a3b8; }
.status-badge.cancel { background: rgba(239, 68, 68, 0.15); color: #ef4444; }

.rc-divider { height: 1px; background: rgba(255,255,255,0.04); margin: 12px 0; }

.rc-body { display: flex; flex-direction: column; }
.data-row { display: flex; justify-content: space-between; align-items: center; font-size: 13px; }
.mt-10 { margin-top: 10px; }
.data-col { display: flex; flex-direction: column; gap: 4px; }
.text-right { align-items: flex-end; text-align: right; }

.lbl { color: #64748b; font-size: 11px; }
.val { color: #e2e8f0; font-weight: 500; }
.val.gold { color: #EAC26E; font-family: monospace; font-size: 14px; }
.val.time { color: #64748b; font-size: 12px; }

.rc-footer {
  margin-top: 12px; padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.04);
}
.win-bar {
  background: linear-gradient(90deg, rgba(239, 68, 68, 0.1), transparent);
  border-left: 3px solid #ef4444;
  padding: 6px 10px; border-radius: 0 4px 4px 0;
  display: flex; align-items: center; gap: 6px;
}
.win-text { color: #ef4444; font-weight: bold; font-size: 12px; }
.win-money { color: #ef4444; font-weight: bold; font-size: 14px; margin-left: auto; }

.empty-box { padding: 40px 0; }
:deep(.van-empty__description) { color: #64748b; }

.rc-actions {
  border-top: 1px solid rgba(255,255,255,0.05);
  padding: 10px 15px;
  display: flex; justify-content: flex-end; gap: 10px;
}
.btn-action {
  background: transparent; border: 1px solid #64748b; color: #94a3b8;
  border-radius: 14px; padding: 0 12px; height: 24px; line-height: 22px;
}
.btn-action.highlight {
  border-color: #EAC26E; color: #EAC26E;
}

:deep(.hemai-detail-page) {
  z-index: 3000 !important;
}
</style>
