<template>
  <div class="im-mine">
    <van-nav-bar title="我的" />
    
    <div class="user-card" @click="goProfile">
      <div class="avatar">
        <van-image 
          :src="getFullAvatarUrl(userInfo.avatar)" 
          width="64" 
          height="64" 
          round 
          fit="cover"
        />
      </div>
      <div class="info">
        <div class="name">
          {{ userInfo.nickname || userInfo.username || '未登录' }}
          <van-tag type="primary" v-if="userInfo.id">ID: {{ userInfo.id }}</van-tag>
        </div>
        <div class="signature">{{ userInfo.signature || '设置个性签名' }}</div>
      </div>
      <van-icon name="arrow" class="arrow" />
    </div>

    <van-cell-group class="menu-group" inset>
      <van-cell title="在线客服" is-link icon="service-o" @click="goCustomerService" />
      <van-cell title="消息通知" is-link icon="volume-o" />
      <van-cell title="隐私设置" is-link icon="shield-o" />
      <van-cell title="通用" is-link icon="setting-o" />
    </van-cell-group>

    <van-cell-group class="menu-group" inset>
      <van-cell title="表情" is-link icon="smile-o" />
      <van-cell title="收藏" is-link icon="star-o" />
      <van-cell title="文件" is-link icon="orders-o" />
    </van-cell-group>

    <van-cell-group class="menu-group" inset>
      <van-cell title="退出登录" is-link class="logout-cell" @click="handleLogout" />
    </van-cell-group>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { contactCustomerService } from '@/api/im'
import { authApi } from '@/api/auth'
import { showConfirmDialog, showToast } from 'vant'

const router = useRouter()
const defaultAvatar = '/assets/img/cat.jpeg'

const getFullAvatarUrl = (avatar) => {
  if (!avatar) return defaultAvatar
  if (avatar.startsWith('http://') || avatar.startsWith('https://') || avatar.startsWith('/')) {
    return avatar
  }
  return '/' + avatar
}

const getCachedUser = () => {
  try {
    const cached = localStorage.getItem('userInfo')
    return cached ? JSON.parse(cached) : {}
  } catch {
    return {}
  }
}

const userInfo = ref(getCachedUser())

const loadUser = async () => {
  try {
    const res = await authApi.getProfile()
    let userData = {}
    if (res?.data?.user) {
      userData = res.data.user
    } else if (res?.data?.nickname || res?.data?.username) {
      userData = res.data
    } else if (res?.nickname || res?.username) {
      userData = res
    } else {
      userData = res?.data || {}
    }
    
    userData.id = userData.id || userData.userId
    userInfo.value = userData
    localStorage.setItem('userInfo', JSON.stringify(userData))
    if (userData.id) {
      localStorage.setItem('userId', String(userData.id))
    }
  } catch (e) {
    console.error('loadUser error:', e)
  }
}

const goProfile = () => {
  const uid = userInfo.value.id || userInfo.value.userId
  if (uid) {
    router.push({ name: 'UserProfile', params: { userId: uid } })
  }
}

const goCustomerService = async () => {
  try {
    const res = await contactCustomerService()
    const csUserId = res.data?.userId
    if (csUserId) {
      router.push({
        name: 'ImChat',
        params: { chatId: `private_${csUserId}` },
        query: { name: '在线客服' }
      })
    } else {
      showToast('暂无客服在线')
    }
  } catch (e) {
    showToast('获取客服失败')
  }
}

const handleLogout = () => {
  showConfirmDialog({
    title: '提示',
    message: '确定要退出登录吗？'
  }).then(() => {
    localStorage.removeItem('token')
    localStorage.removeItem('userId')
    showToast('已退出')
    router.push('/home-new')
  })
}

onMounted(() => {
  loadUser()
})
</script>

<style lang="less" scoped>
.im-mine {
  min-height: 100vh;
  background: #f7f8fa;
  
  .user-card {
    display: flex;
    align-items: center;
    background: #fff;
    padding: 30px 20px;
    margin-bottom: 12px;
    
    .avatar {
      margin-right: 16px;
      border: 2px solid #fff;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
      border-radius: 50%;
    }
    
    .info {
      flex: 1;
      
      .name {
        font-size: 20px;
        font-weight: 600;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 8px;
      }
      
      .signature {
        color: #999;
        font-size: 14px;
      }
    }
    
    .arrow {
      color: #ccc;
    }
  }
  
  .menu-group {
    margin-top: 12px;
    
    :deep(.van-cell) {
      align-items: center;
      padding: 16px;
    }
    
    :deep(.van-icon) {
      font-size: 20px;
      margin-right: 8px;
    }
  }
  
  .logout-cell {
    color: #ee0a24;
    text-align: center;
    :deep(.van-cell__title) {
      text-align: center;
    }
  }
}
</style>
