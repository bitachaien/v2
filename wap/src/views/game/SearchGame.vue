<template>
  <div class="search-page">
    <header class="search-header">
      <div class="back" @click="router.back()">
        <van-icon name="arrow-left" size="20" color="#333" />
      </div>
      <div class="title">Tìm kiếm trò chơi</div>
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

    <div class="filter-tabs">
      <div 
        v-for="tab in tabs" 
        :key="tab.key"
        :class="['tab-item', { active: activeTab === tab.key }]"
        @click="switchTab(tab.key)"
      >
        <img :src="tab.icon" class="tab-icon" />
        {{ tab.name }}
      </div>
    </div>

    <div class="game-scroll-area" ref="scrollRef">
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
                :src="g.cover" 
                class="game-img"
                @error="onImgError($event)"
              />
              
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

        <div v-else-if="!loading" class="empty-state">
          <van-empty description="Chưa có trò chơi" image="/assets/img/img_none_sj.avif" />
        </div>
      </van-skeleton>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { showToast } from 'vant'
import { gameApi } from '@/api/game'
import { getGameIconPath } from '@/config/gameConfig'
import { isLoggedIn } from '@/utils/auth'

const router = useRouter()

const keyword = ref('')
const activeTab = ref('result')
const loading = ref(false)
const searchResults = ref([])
const hotGames = ref([])
const favorites = ref([])
const recentGames = ref([])
let searchTimer = null

const tabs = [
  { key: 'result', name: 'Kết quả', icon: '/assets/img/icon_dtfl_2ss_1.avif' },
  { key: 'all', name: 'Tất cả Hot', icon: '/assets/img/icon_dtfl_2rm_1.avif' },
  { key: 'recent', name: 'Gần đây', icon: '/assets/img/icon_dtfl_2zj_1.avif' },
  { key: 'fav', name: 'Yêu thích', icon: '/assets/img/icon_dtfl_2sc_1.avif' }
]

const visibleGames = computed(() => {
  if (activeTab.value === 'result') {
    return searchResults.value
  } else if (activeTab.value === 'all') {
    return hotGames.value
  } else if (activeTab.value === 'recent') {
    return recentGames.value
  } else if (activeTab.value === 'fav') {
    return favorites.value
  }
  return []
})

const switchTab = (key) => {
  activeTab.value = key
}

const onSearchInput = () => {
  if (searchTimer) clearTimeout(searchTimer)
  
  if (!keyword.value.trim()) {
    searchResults.value = []
    return
  }
  
  activeTab.value = 'result'
  searchTimer = setTimeout(() => {
    doSearch()
  }, 300)
}

const doSearch = async () => {
  if (!keyword.value.trim()) return
  
  loading.value = true
  try {
    const res = await gameApi.search({ keyword: keyword.value.trim(), limit: 50 })
    if (res.code === 0 && res.data) {
      let list = Array.isArray(res.data) ? res.data : (res.data.list || [])
      searchResults.value = list.map(g => ({
        gameId: g.gameId || g.game_id,
        name: g.name,
        platform: g.platform,
        platformCode: g.platform,
        type: g.type || 'slot',
        icon: g.icon,
        cover: g.cover || g.icon,
        uniqueId: `${g.platform}_${g.gameId || g.game_id}`
      }))
    }
  } catch (e) {
    showToast('Tìm kiếm thất bại')
  } finally {
    loading.value = false
  }
}

const loadInitialData = async () => {
  loading.value = true
  try {
    const res = await gameApi.getHotGames({ limit: 50 })
    if (res.code === 0 && res.data) {
      let hotList = Array.isArray(res.data) ? res.data : (res.data.list || [])
      hotGames.value = hotList.map(g => ({
        gameId: g.gameId || g.game_id,
        name: g.name,
        platform: g.platform,
        platformCode: g.platform,
        type: g.type || 'slot',
        icon: g.icon,
        cover: g.cover || g.icon,
        uniqueId: `${g.platform}_${g.gameId || g.game_id}`
      }))
    }
    
    if (isLoggedIn()) {
      loadFavorites()
      loadRecent()
    }
  } catch (e) {
    showToast('Tải thất bại')
  } finally {
    loading.value = false
  }
}

const loadFavorites = async () => {
  try {
    const res = await gameApi.getFavorites()
    if (res.code === 0 && res.data) {
      let list = Array.isArray(res.data) ? res.data : (res.data.list || [])
      favorites.value = list.map(g => ({
        gameId: g.gameId,
        name: g.name,
        platform: g.platform,
        platformCode: g.platform,
        cover: g.cover || g.icon,
        uniqueId: `${g.platform}_${g.gameId}`
      }))
    }
  } catch (e) {}
}

const loadRecent = async () => {
  try {
    const res = await gameApi.getRecent({ limit: 20 })
    if (res.code === 0 && res.data) {
      let list = Array.isArray(res.data) ? res.data : (res.data.list || [])
      recentGames.value = list.map(g => ({
        gameId: g.gameId,
        name: g.name,
        platform: g.platform,
        platformCode: g.platform,
        cover: g.cover || g.icon,
        uniqueId: `${g.platform}_${g.gameId}`
      }))
    }
  } catch (e) {}
}

const enter = async (game) => {
  if (!isLoggedIn()) {
    showToast('Vui lòng đăng nhập trước')
    return
  }
  
  try {
    gameApi.addRecent({
      platform: game.platformCode,
      gameId: game.gameId,
      type: game.type || 'slot'
    }).catch(() => {})
    
    const res = await gameApi.enterGame({
      platform: game.platformCode,
      gameId: game.gameId,
      device: 'mobile'
    })
    
    if ((res.code === 0 || res.code === 10000) && res.data?.url) {
      if (res.data.openType === 'internal') {
        router.push(res.data.url)
      } else {
        window.location.href = res.data.url
      }
    } else {
      showToast(res.message || 'Vào game thất bại')
    }
  } catch (e) {
    showToast('Vào game thất bại')
  }
}

const getPlatformIcon = (code) => {
  return getGameIconPath(code)
}

const onImgError = (e) => {
  if (e.target.dataset.errorHandled) return
  e.target.dataset.errorHandled = 'true'
  e.target.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"%3E%3Crect fill="%23f0f0f0" width="100" height="100"/%3E%3Ctext x="50" y="55" text-anchor="middle" fill="%23999" font-size="12"%3EChưa có ảnh%3C/text%3E%3C/svg%3E'
}

const isFav = (game) => {
  return favorites.value.some(f => f.uniqueId === game.uniqueId)
}

const toggleFav = async (game) => {
  if (!isLoggedIn()) {
    showToast('Vui lòng đăng nhập trước')
    return
  }
  
  const idx = favorites.value.findIndex(f => f.uniqueId === game.uniqueId)
  
  try {
    if (idx > -1) {
      await gameApi.removeFavorite({
        platform: game.platformCode,
        gameId: game.gameId
      })
      favorites.value.splice(idx, 1)
      showToast('Đã hủy yêu thích')
    } else {
      await gameApi.addFavorite({
        platform: game.platformCode,
        gameId: game.gameId,
        type: game.type || 'slot'
      })
      favorites.value.push({
        uniqueId: game.uniqueId,
        gameId: game.gameId,
        name: game.name,
        platform: game.platform,
        platformCode: game.platformCode,
        cover: game.cover
      })
      showToast('Đã thêm yêu thích')
    }
  } catch (err) {
    showToast('Thao tác thất bại')
  }
}

onMounted(() => {
  loadInitialData()
})
</script>

<style scoped>
.search-page {
  height: 100vh;
  background: #E3E3E3;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.search-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 44px;
  padding: 0 12px;
  background: #fff;
  border-bottom: 1px solid #eee;
}

.back {
  width: 40px;
  display: flex;
  align-items: center;
}

.title {
  flex: 1;
  text-align: center;
  font-size: 16px;
  font-weight: 600;
  color: #333;
}

.right-action {
  width: 40px;
}

.search-bar {
  padding: 10px 12px;
  background: #F8F8F8;
  border-bottom: 1px solid #eee;
  display: flex;
  justify-content: center;
}

.search-input-box {
  display: flex;
  align-items: center;
  background: #FFFFFF;
  border-radius: 14px;
  padding: 0 12px;
  width: 100%;
  max-width: 407.09px;
  height: 28.66px;
}

.search-input {
  flex: 1;
  border: none;
  background: transparent;
  font-size: 14px;
  outline: none;
}

.search-btn {
  padding: 4px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.search-btn :deep(.van-icon) {
  pointer-events: none;
}

.filter-tabs {
  display: flex;
  background: #F8F8F8;
  padding: 10px 12px;
  gap: 20px;
  width: 407.09px;
  height: 39.13px;
  max-width: 100%;
  margin: 0 auto;
  align-items: center;
  position: relative;
}

.filter-tabs::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 1px;
  background: #eee;
}

.tab-item {
  font-size: 13.76px;
  color: #666;
  cursor: pointer;
  padding: 5px 0;
  display: flex;
  align-items: center;
  gap: 6px;
  position: relative;
  padding-bottom: 8px;
}

.tab-item.active {
  color: #26A17B;
  font-weight: 600;
}

.tab-item.active::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 100%;
  height: 2px;
  background: #26A17B;
}

.tab-icon {
  width: 20.63px;
  height: 20.63px;
  object-fit: contain;
}

.game-scroll-area {
  flex: 1;
  overflow-y: auto;
  padding: 12px;
  background: #F8F8F8;
}

.game-grid {
  display: grid;
  grid-template-columns: repeat(4, 74.53px);
  gap: 10px;
  justify-content: space-between;
}

.game-card {
  cursor: pointer;
  width: 74.53px;
}

.game-cover-box {
  position: relative;
  width: 74.53px;
  height: 74.53px;
  border-radius: 12px;
  overflow: hidden;
  background: #fff;
}

.game-img {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.badge-fav {
  position: absolute;
  top: 4px;
  right: 4px;
  width: 24px;
  height: 24px;
  background: rgba(0,0,0,0.3);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2;
}

.badge-platform {
  position: absolute;
  bottom: 4px;
  right: 4px;
  width: 20px;
  height: 20px;
  background: rgba(255,255,255,0.9);
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2px;
}

.badge-platform-icon {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.game-name {
  margin-top: 6px;
  font-size: 12px;
  color: #333;
  text-align: center;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.empty-state {
  padding: 60px 0;
}
</style>

