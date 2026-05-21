<template>
  <div class="lottery-hall">
    <header class="hall-header">
      <div class="header-left" @click="router.back()">
        <van-icon name="arrow-left" size="22" color="#333" />
      </div>
      <h1 class="header-title">Sảnh xổ số</h1>
      <div class="header-right">
        <van-icon name="service-o" size="22" color="#333" @click="router.push('/service/online')" />
      </div>
    </header>

    <div class="hall-layout">
      <aside class="category-sidebar">
        <div 
          v-for="cat in categories" 
          :key="cat.code"
          class="cat-item"
          :class="{ active: activeCat === cat.code }"
          @click="activeCat = cat.code"
        >
          <div class="cat-icon">
            <img :src="cat.icon" :class="{ 'icon-active': activeCat === cat.code }" />
          </div>
          <span class="cat-name">{{ cat.name }}</span>
        </div>
      </aside>

      <main class="game-main">
        <van-loading v-if="loading" type="spinner" color="#26A17B" class="loading" />
        
        <div v-else class="game-grid">
          <div 
            v-for="game in filteredGames" 
            :key="game.name"
            class="game-card"
            @click="navigateToGame(game)"
          >
            <div class="card-cover">
              <img :src="getGameIcon(game)" :alt="game.name" @error="onImageError" />
              <div class="card-badge" v-if="game.hot">Hot</div>
            </div>
            <div class="card-info">
              <span class="card-title">{{ game.name }}</span>
            </div>
          </div>
        </div>

        <van-empty v-if="!loading && filteredGames.length === 0" description="Chưa có trò chơi" />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { showToast } from 'vant'
import { gameApi } from '@/api/game'

const router = useRouter()
const loading = ref(true)
const activeCat = ref('all')
const games = ref([])

const categories = [
  { code: 'all', name: 'Xổ số', icon: '/assets/img/icon_dtfl_cp_0.svg' },
  { code: 'ssc', name: 'SSC', icon: '/assets/img/icon_dtfl_cp_0.svg' },
  { code: 'pk10', name: 'PK10', icon: '/assets/img/icon_dtfl_cp_0.svg' },
  { code: 'k3', name: 'K3', icon: '/assets/img/icon_dtfl_cp_0.svg' },
  { code: 'x5', name: '11x5', icon: '/assets/img/icon_dtfl_cp_0.svg' },
  { code: 'lhc', name: 'Lục hợp', icon: '/assets/img/icon_dtfl_cp_0.svg' },
  { code: 'xy28', name: 'XY28', icon: '/assets/img/icon_dtfl_cp_0.svg' },
  { code: 'keno', name: 'Keno', icon: '/assets/img/icon_dtfl_cp_0.svg' },
  { code: 'dpc', name: 'Tần suất thấp', icon: '/assets/img/icon_dtfl_cp_0.svg' },
]

const defaultIcon = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI2NCIgaGVpZ2h0PSI2NCIgdmlld0JveD0iMCAwIDY0IDY0Ij48cmVjdCB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIGZpbGw9IiNlOGY1ZjAiLz48dGV4dCB4PSIzMiIgeT0iMzYiIGZvbnQtc2l6ZT0iMjQiIGZpbGw9IiMyNkExN0IiIHRleHQtYW5jaG9yPSJtaWRkbGUiPuW9qTwvdGV4dD48L3N2Zz4='

const getGameIcon = (game) => {
  if (game.icon && !game.icon.includes('undefined')) return game.icon
  if (game.cover && !game.cover.includes('undefined')) return game.cover
  return defaultIcon
}

const onImageError = (e) => {
  if (!e.target.dataset.fallback) {
    e.target.dataset.fallback = '1'
    e.target.src = defaultIcon
  }
}

const filteredGames = computed(() => {
  if (activeCat.value === 'all') return games.value
  return games.value.filter(g => g.type === activeCat.value || g.typeid === activeCat.value)
})

const navigateToGame = (game) => {
  if (game.route) {
    let route = game.route
    const typeRouteMap = {
      '/keno/': '/kl8/',           // keno -> kl8
      '/pcdd/': '/xy28-chat/',     // pcdd -> xy28聊天室
      '/xy28/': '/xy28-chat/',     // xy28 -> xy28聊天室
    }
    for (const [from, to] of Object.entries(typeRouteMap)) {
      if (route.startsWith(from)) {
        route = route.replace(from, to)
        break
      }
    }
    if (route.startsWith('/lottery/')) {
      router.push(route)
    } else {
      router.push('/lottery' + route)
    }
    return
  }
  const typeCode = game.type || game.typeid || 'ssc'
  const routeMap = {
    ssc: '/lottery/ssc/',
    pk10: '/lottery/pk10/',
    k3: '/lottery/k3/',
    x5: '/lottery/x5/',
    lhc: '/lottery/lhc/',
    xy28: '/lottery/xy28-chat/',
    pcdd: '/lottery/xy28-chat/',
    keno: '/lottery/kl8/',
    kl8: '/lottery/kl8/',
    kl10: '/lottery/kl10/',
    dpc: '/lottery/dpc/',
    dwc: '/lottery/dwc/',
    fc3d: '/lottery/fc3d/',
    pl3: '/lottery/pl3/',
    hn7xc: '/lottery/hn7xc/',
  }
  const basePath = routeMap[typeCode] || '/lottery/ssc/'
  const code = game.code || game.gameId || ''
  router.push(basePath + code)
}

const loadGames = async () => {
  loading.value = true
  try {
    const res = await gameApi.getGameList({ platform: 'BYLOT', limit: 200 })
    if (res.code === 0) {
      games.value = res.data?.list || []
    } else {
      games.value = res?.list || []
    }
  } catch (e) {
  }
  loading.value = false
}

onMounted(() => {
  loadGames()
})
</script>

<style scoped>
.lottery-hall {
  display: flex;
  flex-direction: column;
  height: 100vh;
  background-color: #f5f5f5;
  background-image: url("/assets/img/bg_pattern_tile_0_95.png");
  background-size: 160px 160px;
}

.hall-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  background: #fff;
  box-shadow: 0 1px 4px rgba(0,0,0,0.05);
  flex-shrink: 0;
}
.header-title {
  font-size: 18px;
  font-weight: 600;
  color: #333;
}
.header-left, .header-right {
  width: 40px;
  display: flex;
  align-items: center;
}
.header-right { justify-content: flex-end; }

.hall-layout {
  flex: 1;
  display: flex;
  overflow: hidden;
}

.category-sidebar {
  width: 72px;
  background: #fff;
  overflow-y: auto;
  flex-shrink: 0;
  padding: 8px 0;
  box-shadow: 1px 0 4px rgba(0,0,0,0.03);
}
.category-sidebar::-webkit-scrollbar { display: none; }

.cat-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 10px 4px;
  cursor: pointer;
  transition: all 0.2s;
  position: relative;
}
.cat-item.active {
  background: linear-gradient(135deg, #26A17B 0%, #1a7a5c 100%);
  border-radius: 0 12px 12px 0;
  margin-right: 4px;
}
.cat-item.active::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 3px;
  background: #26A17B;
}

.cat-icon {
  width: 28px;
  height: 28px;
  margin-bottom: 4px;
}
.cat-icon img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  transition: filter 0.2s;
}
.cat-icon img.icon-active {
  filter: brightness(0) invert(1);
}

.cat-name {
  font-size: 11px;
  color: #666;
  text-align: center;
  line-height: 1.2;
}
.cat-item.active .cat-name {
  color: #fff;
}

.game-main {
  flex: 1;
  overflow-y: auto;
  padding: 12px;
}

.loading {
  display: flex;
  justify-content: center;
  padding: 40px;
}

.game-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
}

.game-card {
  background: #fff;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0,0,0,0.06);
  transition: transform 0.2s;
}
.game-card:active {
  transform: scale(0.97);
}

.card-cover {
  position: relative;
  padding-top: 70%;
  background: linear-gradient(145deg, #e8f5f0 0%, #d4ede4 100%);
}
.card-cover img {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 50%;
  height: 50%;
  object-fit: contain;
}
.card-badge {
  position: absolute;
  top: 6px;
  right: 6px;
  padding: 2px 6px;
  background: linear-gradient(135deg, #ff6b6b, #ee5a5a);
  color: #fff;
  font-size: 10px;
  border-radius: 4px;
  font-weight: 500;
}

.card-info {
  padding: 10px 6px;
  text-align: center;
  background: #fff;
}
.card-title {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: #333;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  line-height: 1.3;
}
</style>
