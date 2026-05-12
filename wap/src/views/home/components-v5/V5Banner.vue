<template>
  <div class="v5-banner">
    <van-swipe :autoplay="4000" indicator-color="#009688" class="banner-swipe">
      <van-swipe-item v-for="(item, index) in banners" :key="index">
        <van-image :src="getImageUrl(item.image)" fit="cover" class="banner-img" />
      </van-swipe-item>
    </van-swipe>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { homeApi } from '@/api/home'

const banners = ref([])

const getImageUrl = (img) => {
  if (!img) return ''
  if (img.startsWith('http')) return img
  return img.startsWith('/') ? img : '/' + img
}

onMounted(async () => {
  try {
    const res = await homeApi.getBanners()
    if (res.code === 0 && res.data) banners.value = res.data
  } catch(e){}
})
</script>

<style lang="scss" scoped>
.v5-banner {
  padding: 12px;
  background: transparent;
}

.banner-swipe {
  border-radius: 8px;
  overflow: hidden;
  height: 140px;
}

.banner-img {
  width: 100%;
  height: 100%;
  display: block;
}
</style>
