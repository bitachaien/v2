<template>
  <div class="slot-page">
    <header class="slot-header">
      <div class="back" @click="router.back()">
        <van-icon name="arrow-left" size="20" color="#333" />
      </div>
      <div class="title">{{ typeName }}</div>
      <div class="right-action"></div>
    </header>

    <div class="search-bar">
      <div class="search-input-box">
        <input 
          v-model="keyword" 
          type="text" 
          placeholder="Tìm kiếm trò chơi"
          class="search-input"
          @input="onSearchInput"
        >
        <div class="search-btn" @click="onSearchInput">
          <van-icon name="search" color="#26A17B" size="18" />
        </div>
      </div>
    </div>

    <div class="slot-layout">
      <aside class="platform-sidebar custom-scrollbar">
        <div 
          class="platform-item" 
          :class="{ active: activePlatform === 'ALL' }"
          @click="switchPlatform('ALL')"
        >
          <div class="p-icon">
            <img 
              :src="typeIcon" 
              class="dz-icon"
              :class="{ 'dz-icon-active': activePlatform === 'ALL' }"
            />
          </div>
          <span class="p-name">{{ typeName }}</span>
        </div>

        <div
          v-for="p in platforms"
          :key="p.code"
          class="platform-item"
          :class="{ active: activePlatform === p.code }"
          @click="switchPlatform(p.code)"
        >
          <div class="p-icon">
            <img :src="getPlatformIcon(p.code)" class="p-img" />
          </div>
          <span class="p-name">{{ p.name }}</span>
        </div>
      </aside>

      <main class="slot-main">
        <div class="filter-tabs">
          <div 
            v-for="tab in tabs" 
            :key="tab.key"
            :class="['tab-item', { active: activeTab === tab.key }]"
            @click="switchTab(tab.key)"
          >
            {{ tab.name }}
          </div>
        </div>

        <div class="game-scroll-area custom-scrollbar" ref="scrollRef">
          <van-skeleton title :row="10" :loading="loading" animate style="margin-top: 20px;">
            <div v-if="visibleGames.length > 0" class="game-grid">
              <div
                v-for="g in visibleGames"
                :key="g.uniqueId"
                class="game-card"
                @click="enter(g)"
              >
                <div class="game-cover-box">
                  <img 
                    v-if="g.cover"
                    :src="g.cover" 
                    class="game-img"
                    @error="onImgError($event)"
                  />
                  <div v-else class="game-img-placeholder">{{ g.name?.[0] || '?' }}</div>
                  
                  <div class="badge-fav" @click.stop="toggleFav(g)">
                    <van-icon :name="isFav(g) ? 'star' : 'star-o'" :color="isFav(g) ? '#ffd21e' : '#fff'" size="14" />
                  </div>

                  <div class="badge-platform">
                    <img :src="getPlatformIcon(g.platformCode)" class="badge-platform-icon" />
                  </div>
                </div>
                <div class="game-name">{{ g.name }}</div>
              </div>
            </div>
            
            <div v-else class="empty-box">
              <van-empty description="Không có trò chơi liên quan" image="search" />
            </div>
          </van-skeleton>
        </div>
        
        <div class="pagination-fixed" v-if="totalPages > 0">
          <div 
            class="page-item" 
            :class="{ disabled: currentPage === 1 }"
            @click="goPage(currentPage - 1)"
          >
            <van-icon name="arrow-left" />
          </div>
          <template v-for="p in pageNumbers" :key="p">
            <div v-if="p === '...'" class="page-dots">...</div>
            <div 
              v-else
              class="page-item"
              :class="{ active: currentPage === p }"
              @click="goPage(p)"
            >
              {{ p }}
            </div>
          </template>
          <div 
            class="page-item" 
            :class="{ disabled: currentPage === totalPages }"
            @click="goPage(currentPage + 1)"
          >
            <van-icon name="arrow" />
          </div>
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { showToast } from 'vant'
import { gameApi } from '@/api/game'

const router = useRouter()
const route = useRoute()

const SCROLL_KEY = 'slot_hall_scroll'
const STATE_KEY = 'slot_hall_state'

const urlPlatform = computed(() => route.query.platform || '')
const urlType = computed(() => route.meta?.type || route.query.type || 'slot')

const typeNameMap = {
  slot: '电子',
  live: '真人',
  chess: '棋牌',
  fish: '捕鱼',
  fishing: '捕鱼',
  sport: '体育',
  lottery: '彩票',
  esport: '电竞',
  blockchain: '区块链'
}

const typeIconMap = {
  slot: '/assets/img/icon_dtfl_dz_0.svg',
  live: '/assets/img/icon_dtfl_zr_0.svg',
  chess: '/assets/img/icon_dtfl_qp_0.svg',
  fish: '/assets/img/icon_dtfl_by_0.svg',
  fishing: '/assets/img/icon_dtfl_by_0.svg',
  sport: '/assets/img/icon_dtfl_ty_0.svg',
  lottery: '/assets/img/icon_dtfl_cp_0.svg',
  esport: '/assets/img/icon_dtfl_dj_0.svg',
  blockchain: '/assets/img/icon_dtfl_qkl_0.svg'
}

const typeName = computed(() => typeNameMap[urlType.value] || '电子')
const typeIcon = computed(() => typeIconMap[urlType.value] || '/assets/img/icon_dtfl_dz_0.svg')

const keyword = ref('')
const activePlatform = ref('ALL')
const activeTab = ref('hot')
const loading = ref(false)
const loadingMore = ref(false)

const pageSize = 30
const currentPage = ref(1)

const platforms = ref([])
const allGamesCache = ref([])
const displayList = ref([])

const tabs = computed(() => {
  if (activePlatform.value === 'ALL') {
    return [
      { key: 'hot', name: '热门' },
      { key: 'recent', name: '最近' },
      { key: 'fav', name: '收藏' }
    ]
  } else {
    return [
      { key: 'all', name: '全部' },
      { key: 'hot', name: '热门' },
      { key: 'recent', name: '最近' },
      { key: 'fav', name: '收藏' }
    ]
  }
})

const USE_MOCK = false

const GAME_NAMES = [
  '多福多财', '麻将胡了', '超级水果', '寻龙夺宝', '水浒传', 
  '连环夺宝', '糖果派对', '巨富鸟', '金钱豹', '海龟先生',
  '旺财狗', '猕猴寻宝', '桑巴舞会', '世界杯', '龙争虎斗',
  '财富密码', '赏金大对决', '寻宝黄金城', '赏金女王', '招财喵',
  '赏金船长', '少林足球', '亡灵大盗', '三倍金刚', '富豪哥'
]

onMounted(async () => {
  document.body.style.backgroundColor = '#f5f5f5'
  
  await Promise.all([
    loadPlatforms(),
    loadFavorites(),
    loadRecentGames()
  ])
  
  const restored = restoreScrollState()
  
  if (!restored) {
    if (urlPlatform.value) {
      activePlatform.value = urlPlatform.value
      activeTab.value = 'all'
    } else {
      activeTab.value = 'hot'
    }
  }
  
  await loadGames()
})

watch(() => route.path, async (newPath) => {
  if (newPath.startsWith('/game/') && newPath !== '/game/search') {
    await Promise.all([
      loadFavorites(),
      loadRecentGames()
    ])
    if (activeTab.value === 'fav' || activeTab.value === 'recent') {
      filterAndSort()
    }
  }
}, { immediate: false })

onUnmounted(() => {
})

function saveScrollState() {
  const scrollEl = document.querySelector('.slot-main')
  const scrollTop = scrollEl ? scrollEl.scrollTop : 0
  
  const state = {
    scrollTop,
    activePlatform: activePlatform.value,
    activeTab: activeTab.value,
    currentPage: currentPage.value,
    timestamp: Date.now()
  }
  sessionStorage.setItem(STATE_KEY, JSON.stringify(state))
}

function restoreScrollState() {
  const stateStr = sessionStorage.getItem(STATE_KEY)
  if (!stateStr) return false
  
  try {
    const state = JSON.parse(stateStr)

    if (Date.now() - state.timestamp > 5 * 60 * 1000) {
      sessionStorage.removeItem(STATE_KEY)
      return false
    }
    

    if (state.activePlatform) activePlatform.value = state.activePlatform
    if (state.activeTab) activeTab.value = state.activeTab
    if (state.currentPage) currentPage.value = state.currentPage
    

    nextTick(() => {
      setTimeout(() => {
        const scrollEl = document.querySelector('.slot-main')
        if (scrollEl && state.scrollTop) {
          scrollEl.scrollTop = state.scrollTop
        }
      }, 300)
    })
    

    sessionStorage.removeItem(STATE_KEY)
    return true
  } catch (e) {
    return false
  }
}

watch(activeTab, (newVal) => {
  filterAndSort()
})

async function loadPlatforms() {
  try {

    const res = await gameApi.getPlatforms({ type: urlType.value })
    const list = res?.data?.list || res?.data || []
    platforms.value = list.map(x => ({
      code: x.code,
      name: x.name || x.code,
      icon: x.icon || `/assets/img/provider-${x.code.toLowerCase()}.png`
    }))
  } catch (e) {
    platforms.value = []
  }
}

async function loadGames() {
  loading.value = true
  displayList.value = []
  
  let rawList = []
  
  try {
    if (activePlatform.value === 'ALL') {

      const res = await gameApi.getGameList({ 
        type: urlType.value,
        page: 1, 
        limit: 500 
      })
      const list = res?.data?.list || res?.list || []
      rawList = list.map(g => ({
        id: g.id,
        uniqueId: g.gameId || g.game_id || `game_${g.id}`,
        gameId: g.gameId || g.game_id,
        name: g.name,
        platformCode: g.platform,
        cover: g.cover || g.icon || '',
        hot: g.hot ? 1 : 0
      }))
    } else {

      const res = await gameApi.getGameList({ 
        platform: activePlatform.value,
        type: urlType.value,  // 必须传 type，否则会混入其他类型的游戏
        page: 1, 
        limit: 200 
      })
      const list = res?.data?.list || res?.list || []
      rawList = list.map(g => ({
        id: g.id,
        uniqueId: g.gameId || g.game_id || `game_${g.id}`,
        gameId: g.gameId || g.game_id,
        name: g.name,
        platformCode: g.platform,
        cover: g.cover || g.icon || '',
        hot: g.hot ? 1 : 0
      }))
    }
  } catch (e) {
    rawList = []
  }

  allGamesCache.value = rawList
  filterAndSort()
  loading.value = false
}

const totalPages = computed(() => Math.ceil(displayList.value.length / pageSize))

const totalGames = computed(() => displayList.value.length)

const visibleGames = computed(() => {
  const start = (currentPage.value - 1) * pageSize
  const end = start + pageSize
  return displayList.value.slice(start, end)
})

const pageNumbers = computed(() => {
  const total = totalPages.value
  const current = currentPage.value
  const pages = []
  
  if (total <= 5) {

    for (let i = 1; i <= total; i++) pages.push(i)
  } else {

    pages.push(1)
    
    if (current > 3) pages.push('...')
    

    const start = Math.max(2, current - 1)
    const end = Math.min(total - 1, current + 1)
    for (let i = start; i <= end; i++) pages.push(i)
    
    if (current < total - 2) pages.push('...')
    

    pages.push(total)
  }
  
  return pages
})

function goPage(page) {
  if (page < 1 || page > totalPages.value || page === currentPage.value) return
  currentPage.value = page
}

async function filterAndSort() {
  let res = [...allGamesCache.value]
  

  if (keyword.value) {
    const k = keyword.value.toLowerCase()
    res = res.filter(g => g.name.includes(k) || g.platformCode.toLowerCase().includes(k))
  }

  const currentTab = activeTab.value
  
  if (currentTab === 'all') {

  } else if (currentTab === 'fav') {

    try {
      const favRes = await gameApi.getFavorites({ type: urlType.value })
      if (favRes.code === 0 && favRes.data) {
        let list = Array.isArray(favRes.data) ? favRes.data : (favRes.data.list || [])
        

        if (activePlatform.value !== 'ALL') {
          list = list.filter(g => (g.platform || '').toUpperCase() === activePlatform.value.toUpperCase())
        }
        
        res = list.map(g => ({
          id: 0,
          uniqueId: g.gameId || g.game_id,
          gameId: g.gameId || g.game_id,
          name: g.name || g.game_name,
          platformCode: g.platform,
          cover: g.cover || g.icon || '',
          hot: 0
        }))
      } else {
        res = []
      }
    } catch (e) {
      res = []
    }
    displayList.value = res
    currentPage.value = 1
    return
  } else if (currentTab === 'recent') {

    try {
      const recentRes = await gameApi.getRecent({ type: urlType.value, limit: 50 })
      if (recentRes.code === 0 && recentRes.data) {
        let list = Array.isArray(recentRes.data) ? recentRes.data : (recentRes.data.list || [])
        

        if (activePlatform.value !== 'ALL') {
          list = list.filter(g => (g.platform || '').toUpperCase() === activePlatform.value.toUpperCase())
        }
        
        res = list.map(g => ({
          id: 0,
          uniqueId: g.gameId || g.game_id,
          gameId: g.gameId || g.game_id,
          name: g.name || g.game_name,
          platformCode: g.platform,
          cover: g.cover || g.icon || '',
          hot: 0
        }))
      } else {
        res = []
      }
    } catch (e) {
      res = []
    }
    displayList.value = res
    currentPage.value = 1
    return
  } else if (currentTab === 'hot') {

    res = res.filter(g => g.hot === 1 || g.hot === true)
  }
  
  displayList.value = [...res] // 强制创建新数组触发响应式
  currentPage.value = 1 // 重置分页
}

function generateMockGames(platformCode, count) {
  return Array.from({ length: count }, (_, i) => {
    const nameIdx = Math.floor(Math.random() * GAME_NAMES.length)
    return {
      uniqueId: `${platformCode}_${i}`,
      gameId: `${platformCode}_g${i}`,
      name: GAME_NAMES[nameIdx] + (i > 0 ? ` ${i}` : ''),
      platformCode: platformCode,
      cover: `https://picsum.photos/seed/${platformCode}${i}/200/200`, // 随机图
      hot: Math.floor(Math.random() * 1000),
      isFav: false
    }
  })
}

function getPlatformIcon(code) {
  const iconMap = {
    'PG': '/assets/img/14_N_PG_LOGO.avif',
    'FC': '/assets/img/24_N_FC_LOGO.avif',
    'PP': '/assets/img/37_N_PP_LOGO.avif',
    'JDB': '/assets/img/310_N_JDB_LOGO.avif',
    'CQ9': '/assets/img/imgi_5_3_N_CQ9_LOGO.avif',
    'KA': '/assets/img/imgi_10_27_N_KA_LOGO.avif',
    'WL': '/assets/img/imgi_12_52_N_WL_LOGO.avif',
    'MG': '/assets/img/imgi_9_313_N_MG_LOGO.avif',
    'AG': '/assets/img/10_N_AG_LOGO.avif',
    'BBIN': '/assets/img/8_N_BBIN_LOGO.avif',
  }
  return iconMap[code] || '/assets/img/14_N_PG_LOGO.avif'
}

function switchPlatform(code) {
  if (activePlatform.value === code) return
  activePlatform.value = code

  activeTab.value = code === 'ALL' ? 'hot' : 'all'
  keyword.value = ''
  loadGames()
}

function switchTab(key) {
  if (activeTab.value === key) return
  activeTab.value = key

}

function onSearchInput() {
  filterAndSort()
}

function onImgError(e) {
  e.target.style.display = 'none'  // 隐藏加载失败的图片
}

const favSet = ref(new Set())

async function loadFavorites() {
  try {

    const res = await gameApi.getFavorites({ type: urlType.value })
    if (res.code === 0 && res.data) {
      let list = Array.isArray(res.data) ? res.data : (res.data.list || [])

      favSet.value = new Set(list.map(g => g.gameId))
    }
  } catch (e) {
  }
}

function isFav(g) {

  return favSet.value.has(g.gameId)
}

async function toggleFav(g) {
  const isFavorited = favSet.value.has(g.gameId)
  
  try {
    if (isFavorited) {
      await gameApi.removeFavorite({
        platform: g.platformCode,
        gameId: g.gameId
      })
      favSet.value.delete(g.gameId)
      showToast('已取消收藏')
    } else {
      await gameApi.addFavorite({
        platform: g.platformCode,
        gameId: g.gameId,
        type: urlType.value  // 传入游戏类型
      })
      favSet.value.add(g.gameId)
      showToast('已收藏')
    }
    

    if (activeTab.value === 'fav') {
      filterAndSort()
    }
  } catch (err) {
    showToast('操作失败')
  }
}

const recentGames = ref(new Set())

async function loadRecentGames() {
  try {
    const res = await gameApi.getRecent({ type: urlType.value, limit: 50 })
    if (res.code === 0 && res.data) {
      let list = Array.isArray(res.data) ? res.data : (res.data.list || [])
      recentGames.value = new Set(list.map(g => g.gameId))
    }
  } catch (e) {
  }
}

async function saveRecentGame(platform, gameId) {
  try {
    const res = await gameApi.addRecent({ 
      platform, 
      gameId,
      type: urlType.value  // 传入游戏类型
    })
    if (res.code === 0) {
      recentGames.value.add(gameId)
    }
  } catch (e) {
  }
}

async function enter(g) {

  const token = localStorage.getItem('token')
  if (!token) {
    showToast('请先登录')
    setTimeout(() => router.push('/home-new?auth=login'), 1500)
    return
  }
  
  showToast({ message: `正在进入 ${g.name}...`, icon: 'loading' })
  
  try {

    saveRecentGame(g.platformCode, g.gameId).catch(() => {})
    
    const res = await gameApi.enterGame({
      platform: g.platformCode,
      gameId: g.gameId || g.uniqueId,
      device: 'mobile'
    })
    
    const isSuccess = res.code === 0 || res.code === 10000 || res.code === 200
    const gameUrl = res.data?.url
    const openType = res.data?.openType || 'external'  // 默认外部跳转
    
    if (isSuccess && gameUrl) {

      saveScrollState()
      

      if (openType === 'internal') {

        router.push(gameUrl)
      } else {

        window.location.href = gameUrl
      }
    } else {
      showToast(res.message || res.msg || '进入游戏失败')
    }
  } catch (error) {
    const errorMsg = error.message || '网络错误，请稍后重试'
    if (errorMsg.includes('未登录') || errorMsg.includes('Token')) {
      showToast('登录已过期，请重新登录')
      setTimeout(() => router.push('/home-new?auth=login'), 1500)
    } else {
      showToast(errorMsg)
    }
  }
}

</script>

<style scoped>

.slot-page {
  --primary-color: #26A17B; 
  --bg-color: #f5f5f5;
  --sidebar-bg: #ffffff;
  --text-main: #333333;
  --text-sub: #999999;
  --border-color: #eeeeee;
  
  min-height: 100vh;
  width: 100%;
  max-width: 100vw; 
  position: absolute;
  top: 0;
  left: 0;
  z-index: 1;
  display: flex;
  flex-direction: column;
  background-color: var(--bg-color) !important;
  overflow-x: hidden; 
  color: var(--text-main);
  font-family: -apple-system, BlinkMacSystemFont, 'Helvetica Neue', Helvetica, Segoe UI, Arial, Roboto, 'PingFang SC', 'miui', 'Hiragino Sans GB', 'Microsoft Yahei', sans-serif;
}

.slot-header {
  width: 100%;
  height: 50.59px;
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 12px;
  font-size: 18px;
  font-weight: 500;
  color: #000;
  position: relative;
  z-index: 10;
  border-radius: 0; 
}
.back { width: 40px; height: 100%; display: flex; align-items: center; justify-content: flex-start; padding-left: 4px; color: #333; }
.right-action { width: 40px; }

.search-bar {
  background: #f5f5f5; 
  padding: 8px 12px;
  margin-top: 2px; 
}
.search-input-box {
  background: #fff; 
  border-radius: 14px;
  max-width: 407.09px;
  height: 28.66px;
  display: flex;
  align-items: center;
  padding: 0 12px;
  border: 1px solid #eee;
}
.search-btn { 
  width: 30px; 
  height: 100%; 
  display: flex; 
  align-items: center; 
  justify-content: center;
  cursor: pointer;
}
.search-input {
  flex: 1;
  border: none;
  background: transparent;
  font-size: 14px;
  color: #333;
}
.search-input::placeholder { color: #ccc; }

.slot-layout {
  flex: 1;
  display: flex;
  overflow: hidden;
  background: transparent;
}

.platform-sidebar {
  width: 96px;
  background: transparent;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  padding-top: 5px; 
  padding-bottom: 10px;
  align-items: center;
}

.platform-item {
  width: 86px;
  height: 40.13px;
  margin: 4px 0;
  display: flex;
  flex-direction: row; 
  align-items: center;
  justify-content: flex-start;
  padding-left: 8px;
  transition: all 0.2s;
  
  
  background-image: url('/assets/img/btn_zc1_2.avif');
  background-size: 100% 100%;
  background-repeat: no-repeat;
  background-color: transparent; 
  border: none;
}

.platform-item.active {
  background-image: url('/assets/img/btn_zc1_1.avif');
  color: #fff;
  box-shadow: none; 
}

.platform-item.active .p-name { color: #fff; font-weight: bold; margin-top: 0; }

.platform-item .p-name { 
  font-size: 13.76px; 
  margin-top: 0; 
  margin-left: 15px;
  color: #666;
}

.p-icon {
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.p-img { width: 27.52px; height: 27.52px; object-fit: contain; }
.dz-icon { width: 27.52px; height: 20.91px; transition: filter 0.2s; }
.dz-icon-active { filter: brightness(0) invert(1); }
.p-text-icon { font-weight: bold; color: #20bd81; font-size: 14px; } 

.slot-main {
  flex: 1;
  display: flex;
  flex-direction: column;
  background: transparent;
  margin-left: 2px;
  overflow: hidden;
}

.filter-tabs {
  display: flex;
  padding: 10px 12px;
  gap: 8px;
  background: #f5f5f5;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none; 
}
.filter-tabs::-webkit-scrollbar {
  display: none; 
}
.tab-item {
  min-width: 60px;
  flex-shrink: 0; 
  height: 29.53px !important;
  flex: none; 
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  font-size: 13px;
  border: 1px solid #ddd;
  color: #666;
  background: #fff;
  transition: all 0.2s;
}
.tab-item.active {
  background: var(--primary-color);
  color: #fff;
  border-color: var(--primary-color);
  font-weight: 500;
}

.game-scroll-area {
  
  height: calc((74px + 20px + 24px) * 6);  
  max-height: calc((74px + 20px + 24px) * 6);
  overflow-y: auto;
  padding: 4px 12px 12px;
  -webkit-overflow-scrolling: touch;
}

.game-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px 10px;  
  justify-items: center;
}

.game-card {
  width: 74px;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.game-cover-box {
  width: 74px;
  height: 74px;
  position: relative;
  border-radius: 18px;
  overflow: hidden;
  background: #fff;
  margin-bottom: 6px;
  box-shadow: 0 4px 8px rgba(0,0,0,0.05);
}

.game-img { width: 100%; height: 100%; object-fit: cover; display: block; }
.img-placeholder { width: 100%; height: 100%; background: #f0f0f0; }
.game-img-placeholder {
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 24px;
  font-weight: bold;
}

.badge-fav {
  position: absolute;
  top: 2px;
  right: 2px;
  padding: 2px;
  border-radius: 50%;
  background: rgba(0,0,0,0.2);
}

.badge-platform {
  position: absolute;
  bottom: 2px;
  right: 2px;
  background: transparent;
}
.badge-platform-icon {
  width: 28px;
  height: 28px;
  object-fit: contain;
  display: block;
}

.badge-hot {
  position: absolute;
  top: 2px;
  left: 2px;
  background: #ff4d4f;
  color: #fff;
  width: 14px;
  height: 14px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
}

.game-name {
  font-size: 12px;
  color: #333;
  text-align: center;
  width: 100%;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  line-height: 1.4;
}

.empty-box {
  padding-top: 60px;
  display: flex;
  justify-content: center;
}

.pagination-fixed {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 8px;
  padding: 16px 0 20px;
  margin-top: 12px;
  background: #f5f5f5;
}
.page-item {
  min-width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 13px;
  color: #666;
  background: #fff;
  cursor: pointer;
  transition: all 0.2s;
}
.page-item:active {
  transform: scale(0.95);
}
.page-item.active {
  background: var(--primary-color);
  color: #fff;
  border-color: var(--primary-color);
}
.page-item.disabled {
  color: #ccc;
  cursor: not-allowed;
  pointer-events: none;
}
.page-dots {
  color: #999;
  font-size: 14px;
}

.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #eee; border-radius: 2px; }
</style>
