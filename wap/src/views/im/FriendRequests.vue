<template>
  <div class="friend-requests-page">
    <van-nav-bar
      title="新朋友"
      left-arrow
      @click-left="router.back()"
    >
       <template #right>
         <span @click="router.push('/im/add-friend')">添加朋友</span>
       </template>
    </van-nav-bar>

    <van-loading v-if="loading" class="loading" />

    <van-empty v-else-if="requests.length === 0" description="暂无好友通知" />
    
    <div class="request-list" v-else>
      <div 
        v-for="req in requests" 
        :key="req.id" 
        class="request-item"
      >
        <van-image
          round
          width="40"
          height="40"
          :src="getFullAvatarUrl(req.avatar)"
          class="avatar"
        />
        <div class="content">
          <div class="name">{{ req.nickname }}</div>
          <div class="msg">{{ req.remark || '请求添加你为好友' }}</div>
        </div>
        <div class="action">
           <template v-if="req.status === 'pending'">
             <van-button size="small" type="primary" @click="handle(req, 'accept')">接受</van-button>
           </template>
           <span v-else class="status-text">{{ req.status === 'accepted' ? '已添加' : '已拒绝' }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { showToast } from 'vant'
import { fetchFriendRequests, handleFriendRequest } from '@/api/im'

const router = useRouter()
const requests = ref([])
const loading = ref(false)
const defaultAvatar = '/assets/img/cat.jpeg'

const getFullAvatarUrl = (avatar) => {
  if (!avatar) return defaultAvatar
  if (avatar.startsWith('http://') || avatar.startsWith('https://') || avatar.startsWith('/')) {
    return avatar
  }
  return '/' + avatar
}

const loadData = async () => {
  loading.value = true
  try {
    const res = await fetchFriendRequests()
    requests.value = Array.isArray(res?.data) ? res.data : []
  } catch (e) {
    requests.value = []
  } finally {
    loading.value = false
  }
}

const handle = async (req, action) => {
  try {
    await handleFriendRequest({ requestId: req.id, action })
    req.status = action === 'accept' ? 'accepted' : 'rejected'
    showToast(action === 'accept' ? '已添加' : '已拒绝')
  } catch (e) {}
}

onMounted(loadData)
</script>

<style scoped lang="less">
.friend-requests-page {
  min-height: 100vh;
  background: #fff;
}
.loading {
  display: flex;
  justify-content: center;
  padding: 20px;
}
.request-item {
  display: flex;
  align-items: center;
  padding: 16px;
  border-bottom: 1px solid #f5f5f5;
  
  .avatar {
    margin-right: 12px;
  }
  .content {
    flex: 1;
    .name { font-size: 16px; font-weight: 500; }
    .msg { font-size: 12px; color: #999; margin-top: 4px; }
  }
  .action {
    .status-text { color: #999; font-size: 14px; }
  }
}
</style>
