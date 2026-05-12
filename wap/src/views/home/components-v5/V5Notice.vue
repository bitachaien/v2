<template>
  <div class="v5-notice">
    <img src="/assets/img/icon_dt_pmd.webp" class="vol-icon" />
    <div class="notice-content">
      <div class="marquee-wrap">
        <div class="marquee-text">{{ noticeText }}</div>
      </div>
    </div>
    <div class="mail-box" @click="router.push('/notice')">
      <div class="mail-icon-wrapper">
        <img :src="unreadCount > 0 ? '/assets/img/icon_dt_1xx_wd.avif' : '/assets/img/icon_dt_1xx.avif'" class="mail-icon" />
        <div class="badge" v-if="unreadCount > 0">{{ unreadCount }}</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { homeApi } from '@/api/home'
import { noticeApi } from '@/api/notice'
import { messageApi } from '@/api/message'

const router = useRouter()
const unreadCount = ref(0)
const noticeText = ref('')

const loadData = async () => {
  try {
    const res = await homeApi.getNotices({ limit: 5 })
    if (res.code === 0 && res.data && res.data.length > 0) {
      const titles = res.data.map(n => n.title).join('        ')
      noticeText.value = `${titles}        ${titles}`
    } else {
      noticeText.value = '暂无公告'
    }
  } catch (e) {
    noticeText.value = '暂无公告'
  }
  
  try {
    const [noticeRes, msgRes] = await Promise.all([
      noticeApi.getUnreadCount(),
      messageApi.getUnreadCount()
    ])
    const noticeUnread = noticeRes?.data?.unreadCount || 0
    const msgUnread = msgRes?.data?.unreadCount || 0
    unreadCount.value = noticeUnread + msgUnread
  } catch (e) {
    unreadCount.value = 0
  }
}

onMounted(() => {
  loadData()
})
</script>

<style lang="scss" scoped>
.v5-notice {
  display: flex;
  align-items: center;
  padding: 0 12px;
  height: 36px;
  background: transparent;
}

.vol-icon {
  width: 16px;
  height: 16px;
  margin-right: 8px;
  flex-shrink: 0;
  object-fit: contain;
}

.notice-content {
  flex: 1;
  overflow: hidden;
  height: 36px;
  position: relative;
}

.marquee-wrap {
  width: 100%;
  height: 100%;
  overflow: hidden;
  position: relative;
}

.marquee-text {
  position: absolute;
  white-space: nowrap;
  line-height: 36px;
  font-size: 13px;
  color: #333;
  animation: marquee 20s linear infinite;
}

@keyframes marquee {
  0% { left: 100%; transform: translateX(0); }
  100% { left: 0; transform: translateX(-100%); }
}

.mail-box {
  display: flex;
  align-items: center;
  gap: 4px;
  margin-left: 8px;
  flex-shrink: 0;
}

.mail-icon-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.mail-icon {
  width: 33px;
  height: 29px;
  object-fit: contain;
}

.badge {
  position: absolute;
  top: -6px;
  right: -6px;
  background: #E60012;
  color: #fff;
  font-size: 10px;
  width: 14px;
  height: 14px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}
</style>
