<template>
  <div class="lottery-hemai-tech">
    <div class="bg-overlay"></div>
    <div class="tech-bg-grid"></div>

    <div class="tech-header">
      <div class="header-content">
        <div class="h-left" @click="goBack">
          <van-icon name="arrow-left" />
        </div>
        <div class="h-title">合买大厅</div>
          <div class="h-right">
          <div class="icon-btn" @click="showFilter = true">
            <van-icon name="filter-o" />
          </div>
          <div class="icon-btn" @click="toggleViewMode">
            <van-icon :name="viewMode === 'list' ? 'apps-o' : 'bars'" />
          </div>
        </div>
      </div>
    </div>

    <div class="filter-tabs">
      <div 
        class="tab-item" 
        v-for="tab in tabs" 
        :key="tab.key"
        :class="{ active: activeTab === tab.key }"
        @click="changeTab(tab.key)"
      >
        {{ tab.name }}
        <div class="active-bar" v-if="activeTab === tab.key"></div>
      </div>
    </div>

    <div class="hemai-content custom-scrollbar">
      <van-pull-refresh v-model="refreshing" @refresh="onRefresh" class="tech-pull-refresh">
        
        <div v-if="loading && list.length === 0" class="skeleton-list">
          <div class="glass-card-pro skeleton-item" v-for="i in 5" :key="i">
            <van-skeleton title avatar :row="2" class="custom-skeleton" />
          </div>
        </div>

        <van-list
          v-show="list.length > 0 || !loading"
          v-model:loading="loading"
          :finished="finished"
          finished-text="没有更多了"
          @load="onLoad"
          class="tech-list"
          :immediate-check="false"
        >
          <van-empty v-if="list.length === 0 && !loading" description="暂无合买方案" />

          <div 
            v-for="item in list" 
            :key="item.id" 
            class="glass-card-pro"
            :class="{ 'grid-mode': viewMode === 'grid' }"
            @click="showHemaiDetail(item)"
          >
            <div class="card-left">
              <div class="cp-icon-box">
                <img :src="getLotteryIcon(item.cpname)" />
              </div>
              <div class="cp-texts">
                <div class="cp-name">{{ item.cptitle }}</div>
                <div class="cp-issue">No.{{ item.expect }}</div>
                <div class="cp-tag">{{ item.playtitle || '复式' }}</div>
              </div>
            </div>

            <div class="card-center" v-if="viewMode === 'list'">
              <div class="row-1">
                <span class="label">剩余</span>
                <span class="val-red">{{ item.buyhave }}份</span>
              </div>
              <div class="row-2">
                <span>总额 {{ item.amount }}</span>
                <span class="divider">|</span>
                <span>单价 {{ item.hemaipic }}</span>
              </div>
              <div class="row-3">
                <div class="avatar-tiny">
                  {{ item.username?.[0]?.toUpperCase() }}
                </div>
                <span class="user-name">{{ maskName(item.username) }}</span>
                <span class="level-tag" v-if="item.level">V{{ item.level }}</span>
                <span class="win-tag" v-if="item.winRate">{{ item.winRate }}</span>
              </div>
              <div class="row-4">
                 <div class="stack-avatars">
                   <div class="s-avatar" v-for="n in 3" :key="n"></div>
                   <span class="s-count">{{ (item.buytotal - item.buyhave) }}+</span>
                 </div>
              </div>
            </div>

            <div class="card-right">
              <div class="countdown-capsule" v-if="item.remainingTime > 0">
                <van-count-down :time="item.remainingTime" format="HH:mm:ss" />
              </div>
              <div class="status-text" v-else>已截止</div>

              <div class="progress-circle-box">
                <van-circle
                  v-model:current-rate="item.currentRate"
                  :rate="item.progress"
                  :color="progressGradient"
                  layer-color="rgba(255,255,255,0.1)"
                  :stroke-width="60"
                  size="56px"
                  :text="item.progress + '%'"
                />
                <div class="baodi-mini" v-if="item.baodi > 0">保{{ calcBaodiRate(item) }}%</div>
              </div>

              <van-button 
                round 
                size="small" 
                class="btn-join"
                @click.stop="quickJoin(item)"
                :disabled="item.status !== 0"
              >
                {{ item.status === 0 ? '立即跟单' : '已截止' }}
              </van-button>
            </div>
          </div>
        </van-list>
      </van-pull-refresh>
    </div>

    <van-popup 
      v-model:show="showFilter" 
      position="bottom" 
      round 
      class="tech-popup-bottom"
    >
      <div class="popup-header">选择彩种</div>
      <div class="lottery-grid">
        <div 
          class="lottery-tag" 
          :class="{ active: selectedLottery === 'all' }"
          @click="selectLottery('all')"
        >全部彩种</div>
        <div 
          v-for="cp in lotteryList" 
          :key="cp.name"
          class="lottery-tag"
          :class="{ active: selectedLottery === cp.name }"
          @click="selectLottery(cp.name)"
        >
          {{ cp.title }}
        </div>
      </div>
    </van-popup>

    <div class="fab-create" @click="router.push('/hemai/create')">
      <van-icon name="plus" />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { showToast, showLoadingToast, closeToast, Circle as VanCircle } from 'vant'
import { hemaiApi } from '@/api/hemai'
import { getGameIconPath, DEFAULT_FULL_GAMES } from '@/config/gameConfig'

const router = useRouter()

const viewMode = ref('list') // list | grid
const loading = ref(false)
const finished = ref(false)
const refreshing = ref(false)
const list = ref([])
const page = ref(1)
const pageSize = ref(10)
const activeTab = ref('all')
const showFilter = ref(false)
const selectedLottery = ref('all')
const lotteryList = ref([])

const tabs = [
  { key: 'all', name: '全部' },
  { key: 'soon', name: '即将截止' },
  { key: 'hot', name: '热门方案' },
  { key: 'mine', name: '我发起的' },
  { key: 'joined', name: '我参与的' }
]

const progressGradient = {
  '0%': '#10b981',
  '100%': '#EAC26E',
}

const goBack = () => router.go(-1)
const toggleViewMode = () => viewMode.value = viewMode.value === 'list' ? 'grid' : 'list'

const changeTab = (key) => {
  activeTab.value = key
}

const selectLottery = (name) => {
  selectedLottery.value = name
  showFilter.value = false
  onRefresh()
}

const calcBaodiRate = (item) => {
  if (!item.buytotal || !item.baodi) return 0
  return Math.floor((item.baodi / item.buytotal) * 100)
}

const onLoad = async () => {
  if (refreshing.value) {
    list.value = []
    page.value = 1
    refreshing.value = false
  }

  try {

    const params = {
      status: activeTab.value,
      lottery: selectedLottery.value,
      page: page.value,
      pageSize: pageSize.value
    }
    
    const res = await hemaiApi.getList(params)
    
    if (res.code === 0) {
      const data = res.data.list || []
      const total = res.data.total || 0
      
      const formatted = data.map(item => {

        let endTs = parseInt(item.endtime)
        if (endTs < 10000000000) endTs *= 1000
        const remainingTime = Math.max(0, endTs - Date.now())

        let progress = 0
        if (item.buytotal > 0) {
           progress = Math.floor(((item.buytotal - item.buyhave) / item.buytotal) * 100)
        }

        return {
          ...item,
          currentRate: 0, // 用于 circle 动画
          progress,
          remainingTime
        }
      })

      if (page.value === 1) {
        list.value = formatted
      } else {
        list.value.push(...formatted)
      }
      

      setTimeout(() => {
        list.value.forEach(item => item.currentRate = item.progress)
      }, 100)

      if (list.value.length >= total || data.length < pageSize.value) {
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

watch(activeTab, onRefresh)

const showHemaiDetail = (item) => {

  router.push({
    name: 'LotteryHemaiDetail',
    query: { id: item.id }
  })
}

const quickJoin = (item) => {

  showHemaiDetail(item)
}

const getLotteryIcon = (name) => getGameIconPath(name)
const maskName = (name) => name ? name.substr(0, 2) + '***' : '***'

const loadLotteryListConfig = async () => {

  const cached = sessionStorage.getItem('lottery_list_cache')
  if (cached) {
    lotteryList.value = JSON.parse(cached)
  } else {
     try {
        const res = await hemaiApi.getLotteryList()
        if (res.code === 0) {
          lotteryList.value = res.data.list
          sessionStorage.setItem('lottery_list_cache', JSON.stringify(res.data.list))
        }
     } catch(e) {

       const l = []
       Object.values(DEFAULT_FULL_GAMES).forEach(g => l.push(...g))
       lotteryList.value = l
     }
  }
}

onMounted(() => {
  loading.value = true
  loadLotteryListConfig()
  onLoad()
})
</script>

<style scoped>
.lottery-hemai-tech {
  min-height: 100vh;
  background: linear-gradient(180deg, #111118 0%, #050508 100%);
  color: #fff;
  font-family: 'PingFang SC', sans-serif;
  padding-top: 54px;
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

.tech-header {
  position: fixed; top: 0; left: 0; width: 100%; height: 54px;
  background: rgba(11, 14, 21, 0.85); backdrop-filter: blur(12px);
  z-index: 100; border-bottom: 1px solid rgba(255,255,255,0.05);
}
.header-content {
  display: flex; align-items: center; justify-content: space-between;
  height: 100%; padding: 0 15px;
}
.h-title { font-size: 18px; font-weight: bold; color: #fff; }
.h-right { display: flex; gap: 15px; }
.icon-btn { font-size: 20px; color: #EAC26E; }

.filter-tabs {
  display: flex; justify-content: space-around;
  padding: 12px 0; background: rgba(11, 14, 21, 0.7);
  position: sticky; top: 54px; z-index: 90;
  backdrop-filter: blur(8px);
}
.tab-item {
  font-size: 14px; color: #64748b; position: relative; padding-bottom: 6px;
  transition: all 0.3s;
}
.tab-item.active { color: #EAC26E; font-weight: bold; transform: scale(1.05); }
.active-bar {
  position: absolute; bottom: -2px; left: 50%; transform: translateX(-50%);
  width: 16px; height: 3px; background: #EAC26E; border-radius: 2px;
  box-shadow: 0 0 8px rgba(234, 194, 110, 0.6);
}

.hemai-content { padding: 15px 12px; position: relative; z-index: 1; }

.glass-card-pro {
  background: rgba(30, 35, 50, 0.4);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 12px; margin-bottom: 12px;
  backdrop-filter: blur(10px);
  display: flex; padding: 12px;
  transition: all 0.2s;
}
.glass-card-pro:active { transform: scale(0.99); background: rgba(30, 35, 50, 0.6); }

.card-left { width: 80px; display: flex; flex-direction: column; align-items: center; border-right: 1px solid rgba(255,255,255,0.05); padding-right: 8px; }
.cp-icon-box { width: 40px; height: 40px; background: rgba(255,255,255,0.05); border-radius: 8px; padding: 5px; margin-bottom: 6px; }
.cp-icon-box img { width: 100%; height: 100%; }
.cp-texts { text-align: center; width: 100%; }
.cp-name { font-size: 12px; font-weight: bold; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.cp-issue { font-size: 10px; color: #64748b; margin: 2px 0; }
.cp-tag { font-size: 9px; color: #60a5fa; background: rgba(96, 165, 250, 0.1); padding: 1px 4px; border-radius: 2px; display: inline-block; }

.card-center { flex: 1; padding: 0 12px; display: flex; flex-direction: column; justify-content: center; }
.row-1 { display: flex; align-items: baseline; gap: 6px; margin-bottom: 4px; }
.row-1 .label { font-size: 11px; color: #94a3b8; }
.row-1 .val-red { color: #ef4444; font-weight: bold; font-size: 14px; }
.row-2 { font-size: 11px; color: #cbd5e1; display: flex; gap: 6px; margin-bottom: 8px; }
.divider { color: rgba(255,255,255,0.1); }
.row-3 { display: flex; align-items: center; gap: 6px; margin-bottom: 4px; }
.avatar-tiny { width: 16px; height: 16px; border-radius: 50%; background: #EAC26E; color: #000; font-size: 9px; display: flex; align-items: center; justify-content: center; font-weight: bold; }
.user-name { font-size: 11px; color: #cbd5e1; }
.level-tag { font-size: 9px; color: #EAC26E; border: 1px solid #EAC26E; padding: 0 2px; border-radius: 2px; }
.win-tag { font-size: 9px; color: #f87171; background: rgba(248, 113, 113, 0.1); padding: 0 2px; border-radius: 2px; }

.stack-avatars { display: flex; align-items: center; margin-top: 4px; }
.s-avatar { width: 14px; height: 14px; border-radius: 50%; background: #475569; border: 1px solid #1e293b; margin-left: -4px; }
.s-avatar:first-child { margin-left: 0; }
.s-count { font-size: 9px; color: #64748b; margin-left: 4px; }

.card-right { width: 70px; display: flex; flex-direction: column; align-items: center; justify-content: space-between; }
.countdown-capsule { background: rgba(239, 68, 68, 0.1); border-radius: 10px; padding: 2px 6px; margin-bottom: 6px; }
.countdown-capsule :deep(.van-count-down) { color: #ef4444; font-size: 10px; font-weight: bold; }
.status-text { font-size: 10px; color: #64748b; }

.progress-circle-box { position: relative; width: 56px; height: 56px; margin: 4px 0; }
.baodi-mini { position: absolute; bottom: 10px; left: 0; width: 100%; text-align: center; font-size: 8px; color: #64748b; transform: scale(0.8); }
:deep(.van-circle__text) { color: #fff; font-weight: bold; font-size: 12px; }

.btn-join {
  height: 24px; line-height: 22px; font-size: 11px;
  background: linear-gradient(135deg, #F0C930, #d97706);
  border: none; color: #000; font-weight: bold;
  width: 100%; padding: 0;
}

.glass-card-pro.grid-mode {
  flex-direction: column; width: 48%; display: inline-flex; margin-right: 2%; vertical-align: top;
}
.glass-card-pro.grid-mode:nth-child(2n) { margin-right: 0; }
.glass-card-pro.grid-mode .card-left { width: 100%; flex-direction: row; border-right: none; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 8px; margin-bottom: 8px; }
.glass-card-pro.grid-mode .cp-texts { text-align: left; margin-left: 8px; }
.glass-card-pro.grid-mode .card-right { width: 100%; flex-direction: row; margin-top: 8px; }
.glass-card-pro.grid-mode .progress-circle-box { transform: scale(0.8); }

.skeleton-list { padding: 0; }
.skeleton-item { margin-bottom: 12px; padding: 12px; background: rgba(255,255,255,0.02); }
.custom-skeleton { --van-skeleton-paragraph-background: rgba(255,255,255,0.05); background: transparent; }

.fab-create {
  position: fixed; bottom: 30px; right: 20px;
  width: 56px; height: 56px; border-radius: 50%;
  background: linear-gradient(135deg, #F0C930, #b45309);
  box-shadow: 0 4px 20px rgba(240, 201, 48, 0.4);
  display: flex; align-items: center; justify-content: center;
  color: #000; font-size: 28px; z-index: 999;
  transition: all 0.3s;
}
.fab-create:active { transform: scale(0.9); }

.tech-popup-bottom { background: #1e1e2f; border-top: 1px solid rgba(255,255,255,0.1); }
.popup-header { text-align: center; padding: 15px; font-size: 16px; color: #fff; border-bottom: 1px solid rgba(255,255,255,0.05); }
.lottery-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; padding: 20px; }
.lottery-tag {
  background: rgba(255,255,255,0.05); color: rgba(255,255,255,0.6);
  padding: 10px; text-align: center; border-radius: 6px; font-size: 14px;
}
.lottery-tag.active {
  background: rgba(234, 194, 110, 0.1); color: #EAC26E; border: 1px solid rgba(234, 194, 110, 0.3);
}
</style>
