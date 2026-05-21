<template>
  <div class="notice-detail-page">
    <div class="nav-header">
      <div class="nav-back" @click="$router.back()">
        <van-icon name="arrow-left" />
      </div>
      <div class="nav-title">Chi Tiết Thông Báo</div>
      <div class="nav-right"></div>
    </div>

    <div v-if="loading" class="loading-wrap">
      <van-loading size="24" />
    </div>

    <div v-else class="detail-content">
      <div class="detail-header">
        <h1 class="detail-title">{{ notice.title }}</h1>
        <div class="detail-meta">
          <span class="type-tag" :class="notice.type">{{ getTypeName(notice.type) }}</span>
          <span class="detail-time">{{ formatTime(notice.createdAt) }}</span>
        </div>
      </div>
      <div class="detail-body" v-html="notice.content"></div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { showToast } from 'vant'
import { noticeApi } from '@/api/notice'

const route = useRoute()
const loading = ref(true)
const notice = ref({
  title: '',
  content: '',
  type: '',
  createdAt: 0
})

const getTypeName = (type) => {
  const map = { system: 'Thông báo hệ thống', activity: 'Hoạt động ưu đãi', update: 'Cập nhật phiên bản' }
  return map[type] || 'Thông báo'
}

const formatTime = (ts) => {
  if (!ts) return ''
  const d = new Date(ts)
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')} ${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`
}

onMounted(async () => {
  const id = route.params.id
  try {
    const res = await noticeApi.getNoticeList({ page: 1, pageSize: 100 })
    if (res.code === 0 && res.data?.list) {
      const found = res.data.list.find(item => String(item.id) === String(id))
      if (found) {
        notice.value = found
        loading.value = false
        return
      }
    }
  } catch (e) {
  }
  try {
    const res = await noticeApi.getNoticeDetail(id)
    if (res.code === 0 && res.data) {
      notice.value = res.data
    } else {
      showToast(res.msg || 'Thông báo không tồn tại')
    }
  } catch (error) {
    showToast('Tải thất bại')
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.notice-detail-page {
  min-height: 100vh;
  background: #f5f5f5;
}

.nav-header {
  position: sticky;
  top: 0;
  z-index: 100;
  height: 50px;
  background: #fff;
  display: flex;
  align-items: center;
  padding: 0 15px;
  border-bottom: 1px solid #eee;
}

.nav-back {
  width: 40px;
  display: flex;
  align-items: center;
  font-size: 20px;
  color: #333;
}

.nav-title {
  flex: 1;
  text-align: center;
  font-size: 17px;
  font-weight: 600;
  color: #333;
}

.nav-right {
  width: 40px;
}

.loading-wrap {
  display: flex;
  justify-content: center;
  padding: 50px;
}

.detail-content {
  padding: 15px;
}

.detail-header {
  background: #fff;
  border-radius: 10px;
  padding: 20px;
  margin-bottom: 15px;
}

.detail-title {
  font-size: 18px;
  font-weight: 600;
  color: #333;
  margin: 0 0 15px 0;
  line-height: 1.4;
}

.detail-meta {
  display: flex;
  align-items: center;
  gap: 10px;
}

.type-tag {
  font-size: 12px;
  padding: 2px 8px;
  border-radius: 4px;
  background: #f0f0f0;
  color: #666;
}

.type-tag.system {
  background: rgba(33, 150, 243, 0.1);
  color: #2196f3;
}

.type-tag.activity {
  background: rgba(255, 152, 0, 0.1);
  color: #ff9800;
}

.type-tag.update {
  background: rgba(76, 175, 80, 0.1);
  color: #4caf50;
}

.detail-time {
  font-size: 12px;
  color: #999;
}

.detail-body {
  background: #fff;
  border-radius: 10px;
  padding: 20px;
  font-size: 14px;
  color: #333;
  line-height: 1.8;
}

.detail-body :deep(img) {
  max-width: 100%;
  height: auto;
}
</style>

