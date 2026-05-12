<template>
  <div class="user-profile-page">
    <van-nav-bar
      title="用户资料"
      left-arrow
      @click-left="$router.back()"
    />

    <div class="profile-card" v-if="user">
      <div class="user-info">
        <div class="avatar-wrapper" @click="isSelf && selectAvatar()">
          <van-image
            round
            :src="getFullAvatarUrl(user.avatar)"
            width="72"
            height="72"
            fit="cover"
          />
          <span v-if="isSelf" class="edit-badge">编辑</span>
          <span 
            v-else-if="imStore.isUserOnline(user.id)"
            class="online-badge"
          >在线</span>
        </div>
        <div class="info">
          <div class="nickname" @click="isSelf && editNickname()">
            {{ user.nickname }}
            <van-icon v-if="isSelf" name="edit" class="edit-icon" />
            <van-icon 
              v-else-if="user.gender === 1" 
              name="smile-o" 
              color="#1989fa" 
            />
            <van-icon 
              v-else-if="user.gender === 2" 
              name="smile-o" 
              color="#ff6b81" 
            />
          </div>
          <div class="user-id">ID: {{ user.id }}</div>
        </div>
      </div>
      
      <div class="signature" v-if="user.signature">
        "{{ user.signature }}"
      </div>
    </div>

    <van-cell-group class="info-group" v-if="user">
      <van-cell title="地区" :value="user.region || '未设置'" />
      <van-cell title="注册时间" :value="formatDate(user.createdAt || user.created_at || user.registerTime || user.regTime)" />
    </van-cell-group>

    <van-cell-group class="info-group" v-if="fromGroup">
      <van-cell 
        title="来源"
        :value="`群聊: ${fromGroup.name}`"
        is-link
        @click="goToGroup"
      />
    </van-cell-group>

    <div class="action-buttons" v-if="user && !isSelf">
      <template v-if="isFriend">
        <van-button 
          type="primary" 
          block 
          @click="goToChat"
        >
          发消息
        </van-button>
        <van-button 
          plain 
          block 
          @click="showFriendAction = true"
        >
          更多
        </van-button>
      </template>
      
      <template v-else>
        <van-button 
          type="primary" 
          block 
          @click="addFriend"
          :loading="addingFriend"
        >
          添加好友
        </van-button>
      </template>
    </div>

    <van-action-sheet
      v-model:show="showFriendAction"
      :actions="friendActions"
      cancel-text="取消"
      @select="handleFriendAction"
    />

    <van-dialog
      v-model:show="showRemarkDialog"
      title="设置备注"
      show-cancel-button
      @confirm="saveRemark"
    >
      <van-field
        v-model="remarkInput"
        placeholder="请输入备注名"
        class="remark-input"
      />
    </van-dialog>

    <van-loading v-if="loading" class="loading" />
    
    <van-dialog
      v-model:show="showNicknameDialog"
      title="修改昵称"
      show-cancel-button
      @confirm="saveNickname"
    >
      <van-field
        v-model="nicknameInput"
        placeholder="请输入昵称"
        maxlength="20"
        class="nickname-input"
      />
    </van-dialog>
    
    <input 
      ref="avatarInputRef" 
      type="file" 
      accept="image/*" 
      style="display: none"
      @change="onAvatarSelected"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { showToast, showConfirmDialog, showLoadingToast, closeToast } from 'vant'
import { useImStore } from '@/stores/im'
import { 
  fetchUserInfo, sendFriendRequest, setFriendRemark, 
  blockUser, deleteFriend, uploadFile 
} from '@/api/im'
import { authApi } from '@/api/auth'
import dayjs from 'dayjs'

const route = useRoute()
const router = useRouter()
const imStore = useImStore()

const userId = Number(route.params.userId)
const defaultAvatar = '/assets/img/cat.jpeg'

const getFullAvatarUrl = (avatar) => {
  if (!avatar) return defaultAvatar
  if (avatar.startsWith('http://') || avatar.startsWith('https://') || avatar.startsWith('/')) {
    return avatar
  }
  return '/' + avatar
}

const loading = ref(false)
const user = ref(null)
const isFriend = ref(false)
const isSelf = ref(false)
const fromGroup = ref(null)
const addingFriend = ref(false)
const showFriendAction = ref(false)
const showRemarkDialog = ref(false)
const remarkInput = ref('')
const showNicknameDialog = ref(false)
const nicknameInput = ref('')
const avatarInputRef = ref(null)

const friendActions = [
  { name: '设置备注', action: 'remark' },
  { name: '加入黑名单', action: 'block' },
  { name: '删除好友', action: 'delete', color: '#ee0a24' }
]

function formatDate(date) {
  if (!date) return '未知'
  const ts = typeof date === 'number' && date < 10000000000 ? date * 1000 : date
  return dayjs(ts).format('YYYY-MM-DD')
}

function goToChat() {
  router.push({
    name: 'ImChat',
    params: { chatId: `private_${userId}` },
    query: { name: user.value?.remark || user.value?.nickname }
  })
}

function goToGroup() {
  if (fromGroup.value) {
    router.push({
      name: 'ImChat',
      params: { chatId: `group_${fromGroup.value.id}` }
    })
  }
}

async function addFriend() {
  addingFriend.value = true
  try {
    await sendFriendRequest({ userId })
    showToast('好友请求已发送')
  } catch (e) {
    console.error('添加好友失败:', e)
    showToast('发送失败')
  } finally {
    addingFriend.value = false
  }
}

async function handleFriendAction(action) {
  switch (action.action) {
    case 'remark':
      remarkInput.value = user.value?.remark || ''
      showRemarkDialog.value = true
      break
      
    case 'block':
      await handleBlockUser()
      break
      
    case 'delete':
      await handleDeleteFriend()
      break
  }
  showFriendAction.value = false
}

async function saveRemark() {
  try {
    await setFriendRemark({
      userId,
      remark: remarkInput.value
    })
    
    if (user.value) {
      user.value.remark = remarkInput.value
    }
    showToast('备注已保存')
  } catch (e) {
    console.error('保存失败:', e)
    showToast('保存失败')
  }
}

async function handleBlockUser() {
  try {
    await showConfirmDialog({
      title: '加入黑名单',
      message: '加入黑名单后，你将不再收到对方的消息'
    })
    await blockUser({ userId })
    showToast('已加入黑名单')
  } catch (e) {
  }
}

async function handleDeleteFriend() {
  try {
    await showConfirmDialog({
      title: '删除好友',
      message: '确定要删除该好友吗？删除后聊天记录将被清空'
    })
    await deleteFriend({ userId })
    showToast('已删除好友')
    router.back()
  } catch (e) {
  }
}

function editNickname() {
  nicknameInput.value = user.value?.nickname || ''
  showNicknameDialog.value = true
}

async function saveNickname() {
  if (!nicknameInput.value.trim()) {
    showToast('昵称不能为空')
    return
  }
  
  showLoadingToast({ message: '保存中...', forbidClick: true })
  try {
    await authApi.updateProfile({ nickname: nicknameInput.value.trim() })
    user.value.nickname = nicknameInput.value.trim()
    const cached = JSON.parse(localStorage.getItem('userInfo') || '{}')
    cached.nickname = nicknameInput.value.trim()
    localStorage.setItem('userInfo', JSON.stringify(cached))
    closeToast()
    showToast('昵称已更新')
  } catch (e) {
    closeToast()
    showToast('保存失败')
  }
}

function selectAvatar() {
  avatarInputRef.value?.click()
}

async function onAvatarSelected(e) {
  const file = e.target.files?.[0]
  if (!file) return
  
  showLoadingToast({ message: '上传中...', forbidClick: true })
  try {
    const formData = new FormData()
    formData.append('file', file)
    
    const uploadRes = await uploadFile(formData)
    const avatarUrl = uploadRes.data?.url
    
    if (!avatarUrl) throw new Error('上传失败')
    
    await authApi.updateProfile({ avatar: avatarUrl })
    user.value.avatar = avatarUrl
    const cached = JSON.parse(localStorage.getItem('userInfo') || '{}')
    cached.avatar = avatarUrl
    localStorage.setItem('userInfo', JSON.stringify(cached))
    closeToast()
    showToast('头像已更新')
  } catch (e) {
    closeToast()
    showToast('上传失败')
  } finally {
    e.target.value = ''
  }
}

async function loadUserProfile() {
  loading.value = true
  try {
    const res = await fetchUserInfo(userId)
    const data = res.data || res || {}
    
    let userData = null
    if (data.user) {
      userData = data.user
    } else if (data.nickname || data.username || data.userId || data.id) {
      userData = data
    }
    
    if (userData) {
      userData.id = userData.id || userData.userId
      user.value = userData
      isFriend.value = data.isFriend ?? false
    }
    
    const currentUserId = Number(localStorage.getItem('userId'))
    isSelf.value = userId === currentUserId
    
    if (data.fromGroup) {
      fromGroup.value = data.fromGroup
    } else if (route.query.fromGroupId) {
      fromGroup.value = { id: route.query.fromGroupId, name: '群聊' }
    }
  } catch (e) {
    showToast('加载失败')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadUserProfile()
})
</script>

<style scoped lang="less">
.user-profile-page {
  min-height: 100vh;
  background: #f5f5f5;
  
  .profile-card {
    background: #fff;
    padding: 20px 16px;
    margin-bottom: 10px;
    
    .user-info {
      display: flex;
      align-items: center;
      
      .avatar-wrapper {
        position: relative;
        margin-right: 16px;
        cursor: pointer;
        
        .edit-badge {
          position: absolute;
          bottom: 0;
          left: 50%;
          transform: translateX(-50%);
          background: rgba(0, 0, 0, 0.6);
          color: #fff;
          font-size: 10px;
          padding: 2px 8px;
          border-radius: 8px;
        }
        
        .online-badge {
          position: absolute;
          bottom: 0;
          left: 50%;
          transform: translateX(-50%);
          background: #07c160;
          color: #fff;
          font-size: 10px;
          padding: 1px 6px;
          border-radius: 8px;
        }
      }
      
      .info {
        flex: 1;
        
        .nickname {
          font-size: 18px;
          font-weight: 600;
          display: flex;
          align-items: center;
          gap: 6px;
          cursor: pointer;
          
          .edit-icon {
            font-size: 14px;
            color: #999;
          }
        }
        
        .user-id {
          font-size: 12px;
          color: #999;
          margin-top: 4px;
        }
      }
    }
    
    .signature {
      margin-top: 12px;
      padding: 10px;
      background: #f8f8f8;
      border-radius: 8px;
      font-size: 13px;
      color: #666;
      font-style: italic;
    }
  }
  
  .info-group {
    margin-bottom: 10px;
  }
  
  .action-buttons {
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 10px;
  }
  
  .remark-input,
  .nickname-input {
    padding: 10px 16px;
  }
  
  .loading {
    display: flex;
    justify-content: center;
    padding: 40px;
  }
}
</style>
