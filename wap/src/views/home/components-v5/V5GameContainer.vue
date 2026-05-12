<template>
  <div class="v5-game-container">
    <div class="sidebar">
      <div 
        class="side-item" 
        v-for="(tab, index) in sidebarTabs" 
        :key="index"
        :class="{ active: activeSide === index }"
        @click="handleSideClick(index)"
      >
        <span class="side-icon-box">
          <img :src="tab.iconImg" class="side-icon-img" />
        </span>
        <div class="side-text">{{ tab.name }}</div>
      </div>
    </div>

    <div class="content-area" ref="contentRef" @scroll="onContentScroll">
      <div 
        v-for="(tab, index) in sidebarTabs" 
        :key="tab.code"
        :id="`cat-${index}`"
        class="category-section"
      >
        <div class="category-header" v-if="tab.code !== 'hot'">
          <img :src="tab.iconImg" class="cat-icon" />
          <span class="cat-title">{{ getCategoryTitle(tab) }}</span>
          <span class="cat-all" @click="goToAllPlatforms(tab)">全部</span>
        </div>
        
        <div class="sub-tabs-wrapper" v-else>
          <div class="sub-tabs">
            <div class="sub-tab" :class="{ active: hotSubTab === 'hot' }" @click="switchHotSubTab('hot')">热门</div>
            <div class="sub-tab" :class="{ active: hotSubTab === 'recent' }" @click="switchHotSubTab('recent')">最近</div>
            <div class="sub-tab" :class="{ active: hotSubTab === 'favorite' }" @click="switchHotSubTab('favorite')">收藏</div>
            <div class="sub-tab-indicator" :style="{ transform: `translateX(${hotSubTabIndex * 100}%)` }"></div>
          </div>
        </div>

          <div class="section-content">
            <div class="platform-loading" v-if="getCategoryState(tab.code).loading">
              <van-loading type="spinner" color="#009688" size="24px" />
              <span style="margin-top:8px;font-size:12px">加载中...</span>
            </div>

            <template v-else-if="tab.code === 'hot'">
              <transition :name="'tab-' + slideDirection">
              <div class="game-grid" :key="hotSubTab">
                <div 
                  class="game-card" 
                  v-for="(game, idx) in getHotVisibleItems()" 
                  :key="idx" 
                  @click="enterHotGame(game)"
                >
                  <div class="img-box">
                    <van-image :src="game.image" class="g-img" fit="cover" loading="lazy" v-if="game.image" />
                    <div class="g-placeholder" v-else :style="{background: game.color}">{{ game.name[0] }}</div>
                  </div>
                  <div class="g-name">{{ game.name }}</div>
                  <div class="like-icon" @click.stop="toggleFavorite(game)">
                    <van-icon :name="isFavorited(game) ? 'star' : 'star-o'" :color="isFavorited(game) ? '#FFD700' : ''" />
                  </div>
                </div>
              </div>
              </transition>
            </template>

            <template v-else>
              <div class="platform-list" v-if="getCategoryState(tab.code).list.length > 0">
                <div 
                  class="platform-card" 
                  v-for="(platform, idx) in getVisibleItems(tab.code)" 
                  :key="platform.code"
                  @click="enterPlatform(platform, tab.code)"
                >
                  <img 
                    :src="platform.banner || `/assets/img/platform-${platform.code.toLowerCase()}.png`" 
                    class="platform-banner-full" 
                    loading="lazy"
                    @error="(e) => { if (!e.target.dataset.fallback) { e.target.dataset.fallback = '1'; e.target.src = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMDAiIGhlaWdodD0iODAiPjxyZWN0IHdpZHRoPSIyMDAiIGhlaWdodD0iODAiIGZpbGw9IiNmMGY1ZjMiLz48dGV4dCB4PSIxMDAiIHk9IjQ1IiBmb250LXNpemU9IjE0IiBmaWxsPSIjMjZBMTdCIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIj7muLjmiI/lubPlj7A8L3RleHQ+PC9zdmc+' } }"
                  />
                  <div class="platform-overlay">
                    <span class="platform-name-overlay">{{ platform.name }}</span>
                  </div>
                </div>
              </div>
              
              <div class="platform-empty" v-if="!getCategoryState(tab.code).loading && getCategoryState(tab.code).list.length === 0">
                <span style="font-size:12px;color:#ccc">暂无数据</span>
              </div>
            </template>

            <div class="load-more" v-if="tab.code === 'hot'">
              <template v-if="getHotCurrentList().length > 0">
                <span v-if="getHotCurrentList().length > hotLimit">正在显示 {{ getHotCurrentList().length }} 款{{ getHotSubTabName() }}中的 {{ getHotVisibleItems().length }} 款</span>
                <span v-else>已加载完成</span>
                <div class="more-btn" v-if="getHotCurrentList().length > hotLimit" @click="hotLimit += 9">加载更多 <van-icon name="arrow-down" /></div>
              </template>
              <template v-else>
                <span style="color:#999">暂无{{ getHotSubTabName() }}</span>
              </template>
            </div>
            <div class="load-more" v-else-if="getCategoryState(tab.code).list.length > 0">
               <span v-if="hasMoreItems(tab.code)">正在显示 {{ getCategoryState(tab.code).list.length }} 款{{ getCategoryTitle(tab) }}中的 {{ getVisibleItems(tab.code).length }} 款</span>
               <span v-else>已加载完成</span>
               <div class="more-btn" v-if="hasMoreItems(tab.code)" @click="handleLoadMore(tab.code)">加载更多 <van-icon name="arrow-down" /></div>
            </div>
          </div>
        </div>
      
      <div style="height: 100px;"></div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, reactive, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { showToast } from 'vant'
import { gameApi } from '@/api/game'
import { isLoggedIn } from '@/utils/auth'

const router = useRouter()
const activeSide = ref(0)
const contentRef = ref(null)
let isAutoScrolling = false

const hotSubTab = ref('hot')
const hotLimit = ref(9)

const hotSubTabIndex = computed(() => {
  const tabs = ['hot', 'recent', 'favorite']
  return tabs.indexOf(hotSubTab.value)
})

const slideDirection = ref('slide-left')
let prevTabIndex = 0
const recentGames = ref([])
const favoriteGames = ref([])
const favoriteIds = ref(new Set())

const defaultSidebarTabs = [
  { code: 'hot', name: '热 门', iconImg: '/assets/img/icon_dtfl_rm_1.avif' },
  { code: 'slot', name: '电 子', iconImg: '/assets/img/icon_dtfl_dz_1.avif' },
  { code: 'live', name: '真 人', iconImg: '/assets/img/icon_dtfl_zr_1.avif' },
  { code: 'fish', name: '捕 鱼', iconImg: '/assets/img/icon_dtfl_by_1.avif' },
  { code: 'chess', name: '棋 牌', iconImg: '/assets/img/icon_dtfl_qp_1.avif' },
  { code: 'lottery', name: '彩 票', iconImg: '/assets/img/icon_dtfl_cp_1.avif' },
  { code: 'blockchain', name: '区块链', iconImg: '/assets/img/icon_dtfl_qkl_1.avif' }
]

const categoryMap = reactive({})

const iconMap = {
  hot: '/assets/img/icon_dtfl_rm_1.avif',
  slot: '/assets/img/icon_dtfl_dz_1.avif',
  live: '/assets/img/icon_dtfl_zr_1.avif',
  fish: '/assets/img/icon_dtfl_by_1.avif',
  chess: '/assets/img/icon_dtfl_qp_1.avif',
  lottery: '/assets/img/icon_dtfl_cp_1.avif',
  sport: '/assets/img/icon_dtfl_ty_1.avif',
  esport: '/assets/img/icon_dtfl_dj_1.avif',
  blockchain: '/assets/img/icon_dtfl_qkl_1.avif'
}

const categoryNameMap = {
  hot: '热门游戏',
  slot: '电子游戏',
  live: '真人视讯',
  fish: '捕鱼游戏',
  chess: '棋牌游戏',
  lottery: '彩票游戏',
  esport: '电子竞技',
  sport: '体育竞技',
  blockchain: '区块链游戏'
}

const sidebarTabs = ref([...defaultSidebarTabs])

const currentTab = computed(() => sidebarTabs.value[activeSide.value] || sidebarTabs.value[0])

const initCategoryState = (code) => {
  if (!categoryMap[code]) {
    categoryMap[code] = {
      list: [],
      loading: false,
      limit: code === 'hot' ? 9 : 6,
      loaded: false
    }
  }
}

const getCategoryState = (code) => {
  if (!categoryMap[code]) initCategoryState(code)
  return categoryMap[code]
}

const getVisibleItems = (code) => {
  const state = getCategoryState(code)
  return state.list.slice(0, state.limit)
}

const hasMoreItems = (code) => {
  const state = getCategoryState(code)
  return state.list.length > state.limit
}

const handleLoadMore = (code) => {
  const state = getCategoryState(code)
  state.limit += (code === 'hot' ? 9 : 6)
}

const getCategoryTitle = (tab) => {
  return categoryNameMap[tab.code] || tab.name.replace(/\s/g, '') + '游戏'
}

const loadCategories = async () => {
  try {
    const res = await gameApi.getCategories()
    if (res.code === 0 && res.data?.list?.length > 0) {
      const tabs = res.data.list.map(item => ({
        code: item.code,
        name: item.name.length <= 2 ? item.name.split('').join(' ') : item.name,
        iconImg: iconMap[item.code] || '/assets/img/icon_dtfl_rm_1.avif',
        path: item.path
      }))
      sidebarTabs.value = tabs
    }
  } catch (e) {}
}

const loadHotGames = async () => {
  const state = getCategoryState('hot')
  state.loading = true
  try {
    const res = await gameApi.getHotGames({ limit: 30 })
    if (res.code === 0 && res.data?.list?.length > 0) {
      state.list = res.data.list.map((g, i) => ({
        name: g.name || g.title,
        code: g.gameId || g.code || g.game_id,
        platform: g.platform,
        type: g.type,
        path: g.path || `/game/play?platform=${g.platform}&gameId=${g.gameId || g.code || g.game_id}`,
        image: g.icon || g.cover || g.image,
        color: ['#a18cd1', '#fbc2eb', '#84fab0', '#ff9a9e'][i % 4],
      }))
      state.loaded = true
    }
  } catch(e) {} finally {
    state.loading = false
  }
}

const loadRecentGames = async () => {
  if (!isLoggedIn()) return
  try {
    const res = await gameApi.getRecent({ limit: 30 })
    if (res.code === 0 && res.data) {
      const list = Array.isArray(res.data) ? res.data : (res.data.list || [])
      recentGames.value = list.map((g, i) => ({
        name: g.name || g.game_name,
        code: g.gameId || g.game_id || g.code,
        platform: g.platform,
        type: g.type,
        path: `/game/play?platform=${g.platform}&gameId=${g.gameId || g.game_id || g.code}`,
        image: g.icon || g.cover || g.image,
        color: ['#a18cd1', '#fbc2eb', '#84fab0', '#ff9a9e'][i % 4],
      }))
    }
  } catch (e) {}
}

const loadFavoriteGames = async () => {
  if (!isLoggedIn()) return
  try {
    const res = await gameApi.getFavorites({ limit: 30 })
    if (res.code === 0 && res.data) {
      const list = Array.isArray(res.data) ? res.data : (res.data.list || [])
      favoriteGames.value = list.map((g, i) => ({
        name: g.name || g.game_name,
        code: g.gameId || g.game_id || g.code,
        platform: g.platform,
        type: g.type,
        path: `/game/play?platform=${g.platform}&gameId=${g.gameId || g.game_id || g.code}`,
        image: g.icon || g.cover || g.image,
        color: ['#a18cd1', '#fbc2eb', '#84fab0', '#ff9a9e'][i % 4],
      }))
      favoriteIds.value = new Set(list.map(g => `${g.platform}_${g.gameId || g.game_id || g.code}`))
    }
  } catch (e) {}
}

const switchHotSubTab = (tab) => {
  const tabs = ['hot', 'recent', 'favorite']
  const newIndex = tabs.indexOf(tab)
  
  slideDirection.value = newIndex > prevTabIndex ? 'slide-left' : 'slide-right'
  prevTabIndex = newIndex
  
  hotSubTab.value = tab
  hotLimit.value = 9
  if (tab === 'recent' && recentGames.value.length === 0) {
    loadRecentGames()
  } else if (tab === 'favorite' && favoriteGames.value.length === 0) {
    loadFavoriteGames()
  }
}

const getHotSubTabName = () => {
  const names = { hot: '热门游戏', recent: '最近游戏', favorite: '收藏游戏' }
  return names[hotSubTab.value] || '热门游戏'
}

const getHotCurrentList = () => {
  if (hotSubTab.value === 'recent') return recentGames.value
  if (hotSubTab.value === 'favorite') return favoriteGames.value
  return getCategoryState('hot').list
}

const getHotVisibleItems = () => {
  return getHotCurrentList().slice(0, hotLimit.value)
}

const enterHotGame = async (game) => {
  if (!isLoggedIn()) {
    showToast('请先登录')
    return
  }
  
  showToast({ message: `正在进入 ${game.name}...`, icon: 'loading' })
  
  try {
    gameApi.addRecent({
      platform: game.platform,
      gameId: game.code,
      type: game.type
    }).catch(() => {})
    
    const res = await gameApi.enterGame({
      platform: game.platform,
      gameId: game.code,
      device: 'mobile'
    })
    
    if ((res.code === 0 || res.code === 10000) && res.data?.url) {
      if (res.data.openType === 'internal') {
        router.push(res.data.url)
      } else {
        window.location.href = res.data.url
      }
    } else {
      showToast(res.message || res.msg || '进入游戏失败')
    }
  } catch (e) {
    showToast(e.message || '网络错误')
  }
}

const isFavorited = (game) => {
  const id = `${game.platform}_${game.code}`
  return favoriteIds.value.has(id)
}

const toggleFavorite = async (game) => {
  if (!isLoggedIn()) {
    showToast('请先登录')
    return
  }
  const id = `${game.platform}_${game.code}`
  try {
    if (favoriteIds.value.has(id)) {
      await gameApi.removeFavorite({ platform: game.platform, gameId: game.code })
      favoriteIds.value.delete(id)
      favoriteGames.value = favoriteGames.value.filter(g => `${g.platform}_${g.code}` !== id)
      showToast('已取消收藏')
    } else {
      await gameApi.addFavorite({ platform: game.platform, gameId: game.code, gameName: game.name, type: game.type, icon: game.image })
      favoriteIds.value.add(id)
      favoriteGames.value.unshift(game)
      showToast('收藏成功')
    }
  } catch (e) {
    showToast('操作失败')
  }
}

const loadCategoryPlatforms = async (code) => {
  const state = getCategoryState(code)
  if (state.loaded) return
  
  state.loading = true
  try {
    const res = await gameApi.getPlatforms({ type: code })
    if (res.code === 0 && res.data?.list) {
      state.list = res.data.list.map(p => ({
        ...p,
        banner: p.mobile_banner || p.banner || `/assets/img/platform-banner-${p.code.toLowerCase()}.png`,
        isLocal: p.kind === 'local' || p.code === 'BYLOT'
      }))
      state.loaded = true
    }
  } catch (e) {} finally {
    state.loading = false
  }
}

const initAllData = async () => {
  await loadCategories()
  
  sidebarTabs.value.forEach(tab => {
    initCategoryState(tab.code)
    if (tab.code === 'hot') {
      loadHotGames()
    } else {
      loadCategoryPlatforms(tab.code)
    }
  })
  
  if (isLoggedIn()) {
    loadRecentGames()
    loadFavoriteGames()
  }
}

const handleSideClick = (index) => {
  activeSide.value = index
  isAutoScrolling = true
  
  const el = document.getElementById(`cat-${index}`)
  const scrollContainer = document.querySelector('.main-scroll') || contentRef.value
  
  if (el && scrollContainer) {
    const containerRect = scrollContainer.getBoundingClientRect()
    const elRect = el.getBoundingClientRect()
    const currentScroll = scrollContainer.scrollTop
    const targetTop = currentScroll + elRect.top - containerRect.top - 10
    
    scrollContainer.scrollTo({
      top: Math.max(0, targetTop),
      behavior: 'smooth'
    })
  }
  
  setTimeout(() => {
    isAutoScrolling = false
  }, 600)
}

const onContentScroll = (e) => {
  if (isAutoScrolling) return
  
  const scrollContainer = document.querySelector('.main-scroll') || e.target
  const scrollTop = scrollContainer.scrollTop
  const containerRect = scrollContainer.getBoundingClientRect()
  
  const categoryEls = sidebarTabs.value.map((_, i) => document.getElementById(`cat-${i}`))
  
  for (let i = 0; i < categoryEls.length; i++) {
    const el = categoryEls[i]
    if (el) {
      const elRect = el.getBoundingClientRect()
      if (elRect.top <= containerRect.top + 100 && elRect.bottom > containerRect.top + 50) {
        activeSide.value = i
        break
      }
    }
  }
}

onMounted(() => {
  initAllData()
  
  nextTick(() => {
    const mainScroll = document.querySelector('.main-scroll')
    if (mainScroll) {
      mainScroll.addEventListener('scroll', onContentScroll)
    }
  })
})

onUnmounted(() => {
  const mainScroll = document.querySelector('.main-scroll')
  if (mainScroll) {
    mainScroll.removeEventListener('scroll', onContentScroll)
  }
})

const enterPlatform = async (platform, type) => {
  if (type === 'lottery') {
    if (platform.code === 'BYLOT' || platform.isLocal) {
      router.push('/lotteryMore')
      return
    }
    enterGameCommon(platform)
    return
  }
  
  if (type === 'live' || type === 'sport' || type === 'esport') {
    enterGameCommon(platform)
    return
  }
  
  const pathMap = {
    slot: '/game/slot',
    live: '/game/live',
    chess: '/game/chess',
    fish: '/game/fish',
    fishing: '/game/fish'
  }
  const path = pathMap[type] || '/game/slot'
  
  router.push({
    path: path,
    query: { platform: platform.code }
  })
}

const enterGameCommon = async (platform) => {
  showToast({ message: `正在进入 ${platform.name}...`, icon: 'loading' })
  try {
    const res = await gameApi.enterGame({
      platform: platform.code,
      gameId: 'lobby',
      device: 'mobile'
    })
    if ((res.code === 0 || res.code === 10000) && res.data?.url) {
      if (res.data.openType === 'internal') {
        router.push(res.data.url)
      } else {
        window.location.href = res.data.url
      }
    } else {
      showToast(res.message || res.msg || '进入游戏失败')
    }
  } catch (e) {
    showToast(e.message || '网络错误')
  }
}

const goToAllPlatforms = (tab) => {
  const pathMap = {
    slot: '/game/slot',
    live: '/game/live',
    fish: '/game/fish',
    chess: '/game/chess',
    lottery: '/game/lottery',
    sport: '/game/sport',
    esport: '/game/esport',
    blockchain: '/game/blockchain'
  }
  const path = pathMap[tab.code] || tab?.path || `/game/${tab.code}`
  router.push(path)
}

</script>

<style lang="scss" scoped>
.v5-game-container {
  display: flex;
  min-height: 500px;
  background: transparent;
  position: relative;
}

.sidebar {
  width: 86px;
  background: transparent;
  display: flex;
  flex-direction: column;
  position: sticky;
  top: 0;
  height: calc(100vh - 50px - 71px);
  overflow-y: auto;
  -ms-overflow-style: none;
  scrollbar-width: none;
}

.sidebar::-webkit-scrollbar {
  display: none;
}

.side-item {
  width: 86px;
  height: 50px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  margin-bottom: 4px;
  border-radius: 0 12px 12px 0;
  transition: all 0.2s ease;
  cursor: pointer;
  flex-shrink: 0;
}

.side-item.active {
  background: #fff;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
}

.side-item.active .side-text {
  color: #26A17B;
  font-weight: 700;
}

.side-icon-box {
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.side-icon-img {
  width: 24px;
  height: 24px;
  object-fit: contain;
}

.side-text {
  font-size: 14px;
  color: #666;
  font-weight: 600;
  white-space: nowrap;
}

.content-area {
  flex: 1;
  padding: 0 12px 60px;
  overflow-y: auto;
  background: transparent;
  min-height: 100vh;
  scroll-behavior: smooth;
}

.category-section {
  margin-bottom: 15px;
  padding-top: 12px;
}

.section-content {
  overflow: hidden;
  position: relative;
  min-height: 280px;
}

.section-content .game-grid {
  width: 100%;
}

.tab-slide-left-leave-active,
.tab-slide-right-leave-active {
  position: absolute !important;
  top: 0;
  left: 0;
  width: 100%;
}

.sub-tabs-wrapper {
  padding-top: 4px;
  margin-bottom: 12px;
  border-bottom: 1px solid #f5f5f5;
}

.sub-tabs {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  padding-bottom: 8px;
  position: relative;
}

.sub-tab {
  font-size: 16px;
  color: #666;
  position: relative;
  padding-bottom: 4px;
  text-align: center;
  display: flex;
  justify-content: center;
  cursor: pointer;
  transition: color 0.2s ease;
}

.sub-tab.active {
  color: #26A17B;
  font-weight: 700;
}

.sub-tab-indicator {
  position: absolute;
  bottom: 0;
  left: 0;
  width: calc(100% / 3);
  height: 3px;
  display: flex;
  justify-content: center;
  transition: transform 0.2s ease;
}

.sub-tab-indicator::after {
  content: '';
  width: 20px;
  height: 3px;
  background: #26A17B;
  border-radius: 2px;
}

.game-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 15px 8px;
  justify-items: center;
}

.game-card {
  width: 100%;
  max-width: 100px;
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
  background: transparent;
}

.img-box {
  width: 74px;
  height: 74px;
  border-radius: 18px;
  overflow: hidden;
  margin-bottom: 8px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
  background: #fff;
}

.g-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.g-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 24px;
  font-weight: 700;
}

.g-name {
  font-size: 12px;
  color: #333;
  text-align: center;
  width: 100%;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  line-height: 1.4;
}

.like-icon {
  position: absolute;
  top: 4px;
  right: 4px;
  color: #fff;
  opacity: 0.8;
  text-shadow: 0 1px 2px rgba(0,0,0,0.5);
}

.load-more {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  margin: 20px 0 12px;
  padding-bottom: 15px;
  border-bottom: 1px solid #f5f5f5;
  color: #999;
  font-size: 12px;
}

.more-btn {
  display: flex;
  align-items: center;
  gap: 4px;
  margin-top: 4px;
  cursor: pointer;
}

.category-header {
  display: flex;
  align-items: center;
  padding: 5px;
  margin-bottom: 12px;
}

.cat-icon {
  width: 24px;
  height: 24px;
  margin-right: 8px;
  object-fit: contain;
}

.cat-title {
  font-size: 16px;
  font-weight: 700;
  color: #333;
  flex: 1;
}

.cat-all {
  font-size: 14px;
  color: #999;
  cursor: pointer;
}

.content-wrapper {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.current-category-bar {
  display: flex;
  align-items: center;
  padding: 8px 12px;
  background: #fff;
  border-bottom: 1px solid #f5f5f5;
  flex-shrink: 0;
}

.bar-icon {
  width: 22px;
  height: 22px;
  margin-right: 8px;
  object-fit: contain;
}

.bar-title {
  font-size: 16px;
  font-weight: 700;
  color: #333;
  flex: 1;
}

.bar-all {
  font-size: 13px;
  color: #f60;
  cursor: pointer;
}

.platform-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.platform-card {
  position: relative;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  cursor: pointer;
  transition: all 0.2s ease;
  aspect-ratio: 298 / 116;
  background: linear-gradient(135deg, #e0eafc 0%, #cfdef3 100%);
}

.platform-card:active {
  transform: scale(0.98);
}

.platform-banner-full {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.platform-overlay {
  position: absolute;
  left: 48px;
  bottom: 42%;
  transform: translateY(50%);
}

.platform-name-overlay {
  font-size: 16px;
  font-weight: 700;
  color: #fff;
  text-shadow: 0 1px 3px rgba(0,0,0,0.8);
}

.platform-loading,
.platform-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 20px 0;
  color: #999;
}
</style>
