<template>
  <div class="im-layout">
    <router-view v-slot="{ Component }">
      <keep-alive>
        <component :is="Component" />
      </keep-alive>
    </router-view>

    <van-tabbar v-model="active" route fixed placeholder safe-area-inset-bottom>
      <van-tabbar-item name="chats" to="/im/chats" icon="chat-o" badge-max="99" :badge="unreadTotal > 0 ? unreadTotal : ''">
        消息
      </van-tabbar-item>
      <van-tabbar-item name="contacts" to="/im/contacts" icon="friends-o" :badge="requestCount > 0 ? requestCount : ''">
        通讯录
      </van-tabbar-item>
      <van-tabbar-item name="mine" to="/im/mine" icon="user-o">
        我的
      </van-tabbar-item>
    </van-tabbar>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useImStore } from '@/stores/im'
import { fetchFriendRequests } from '@/api/im'

const imStore = useImStore()
const active = ref('chats')

const unreadTotal = computed(() => imStore.totalUnreadCount)

const requestCount = ref(0)

onMounted(async () => {
  imStore.initIM()
  
  try {
    const res = await fetchFriendRequests()
    const list = Array.isArray(res?.data) ? res.data : []
    requestCount.value = list.filter(r => r.status === 'pending').length
  } catch (e) {
    requestCount.value = 0
  }
})
</script>

<style lang="less" scoped>
.im-layout {
  height: 100%;
  background: #f7f8fa;
  padding-bottom: 50px; 
}

:deep(.van-tabbar-item--active) {
  color: #07c160; 
  font-weight: 500;
}
</style>
