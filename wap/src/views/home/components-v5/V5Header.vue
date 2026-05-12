<template>
  <header class="v5-header">
    <div class="left">
      <img src="/assets/images/user/avatars/logo.png" class="logo-img" />
      <span class="brand-name" v-html="formattedTitle"></span>
    </div>
    <div class="right">
      <div class="search-box" @click="router.push('/game/search')">
        <img src="/assets/img/icon_dt_1ss.avif" class="search-icon" />
        <span class="search-text">搜索</span>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useConfigStore } from '@/stores/config'

const router = useRouter()
const configStore = useConfigStore()

const formattedTitle = computed(() => {
  const title = configStore.siteName || ''
  if (!title) return ''
  const match = title.match(/^(.+?)(\d+\.[a-z]+)$/i)
  if (match) {
    return `${match[1]}<span class="highlight">${match[2]}</span>`
  }
  return title
})

onMounted(() => {
  configStore.fetchConfig()
})
</script>

<style lang="scss" scoped>
.v5-header {
  height: 50px;
  background: #fff;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0 12px;
  border-bottom: 1px solid #f5f5f5;
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 100;
}

.left {
  display: flex;
  align-items: center;
  gap: 8px;
}

.logo-img {
  width: 32px;
  height: 32px;
}

.brand-name {
  font-size: 16px;
  font-weight: 800;
  color: #333;
}

:deep(.highlight) {
  color: #E60012;
}

.right {
  display: flex;
  align-items: center;
}

.search-box {
  display: flex;
  flex-direction: column;
  align-items: center;
  line-height: 1;
  cursor: pointer;
}

.search-icon {
  width: 31px;
  height: 27px;
  object-fit: contain;
}

.search-text {
  font-size: 12px;
  color: #666;
  margin-top: 2px;
}
</style>