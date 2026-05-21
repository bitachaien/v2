<template>
  <div class="activity-detail-base">
    <van-nav-bar
      :title="title"
      left-arrow
      @click-left="goBack"
      fixed
      placeholder
    />
    
    <div class="content-wrapper">
      
      <div class="banner-section" v-if="$slots.banner">
        <slot name="banner"></slot>
      </div>
      
      
      <div class="main-content">
        <slot></slot>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'

defineProps({
  title: {
    type: String,
    default: 'Chi tiết sự kiện'
  }
})

const router = useRouter()

const goBack = () => {
  router.back()
}
</script>

<style scoped>
.activity-detail-base {
  min-height: 100vh;
  background: linear-gradient(135deg, #0B0E15 0%, #1a1d2e 100%);
}

.content-wrapper {
  padding-bottom: 20px;
}

.banner-section {
  margin-bottom: 16px;
}

.main-content {
  padding: 0 16px;
}

:deep(.van-nav-bar) {
  background: linear-gradient(135deg, #1a1d2e 0%, #0B0E15 100%);
}

:deep(.van-nav-bar__title) {
  color: #fff;
  font-weight: 600;
}

:deep(.van-nav-bar .van-icon) {
  color: #fff;
}

:deep(.promotion_img) {
  width: 100%;
  overflow: hidden;
}

:deep(.promotion_img img) {
  width: 100%;
  height: auto;
  display: block;
}

:deep(.glass-card) {
  background: rgba(255, 255, 255, 0.05);
  backdrop-filter: blur(10px);
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  padding: 16px;
  margin-bottom: 16px;
}

:deep(.stats-card) {
  display: flex;
  align-items: center;
  gap: 12px;
}

:deep(h1, h2, h3, h4, h5, h6) {
  color: #fff;
  margin: 16px 0 12px;
}

:deep(p) {
  color: rgba(255, 255, 255, 0.8);
  line-height: 1.6;
  margin-bottom: 12px;
}

:deep(ul) {
  list-style: disc;
  padding-left: 20px;
  color: rgba(255, 255, 255, 0.8);
}

:deep(li) {
  margin-bottom: 8px;
  line-height: 1.6;
}

:deep(table) {
  width: 100%;
  border-collapse: collapse;
  margin: 16px 0;
}

:deep(th),
:deep(td) {
  border: 1px solid rgba(255, 255, 255, 0.1);
  padding: 8px;
  color: rgba(255, 255, 255, 0.8);
  text-align: left;
}

:deep(th) {
  background: rgba(255, 255, 255, 0.05);
  font-weight: 600;
  color: #fff;
}

:deep(.highlight) {
  color: #F0C930;
  font-weight: 600;
}

:deep(.banner-area) {
  padding: 16px;
}
</style>
