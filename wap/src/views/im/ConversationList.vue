<template>
  <div class="conversation-list-page" :class="{ 'page-exit': isExiting }">
    <div class="im-header">
      <div class="header-left" @click="goBack">
        <van-icon name="arrow-left" />
      </div>
      <div class="header-title">Tin nhắn</div>
      <div class="header-right">
        <van-icon name="add-o" size="24" @click="showActions = true" />
      </div>
    </div>

    <van-search v-model="searchKey" placeholder="Tìm kiếm" shape="round" background="#ededed" />

    <van-tabs v-model:active="activeTab" class="im-tabs" sticky animated swipeable>
      <van-tab title="Hội thoại" name="chat">
        <div class="list-content">
          <van-pull-refresh v-model="refreshing" @refresh="onRefresh">
            <van-empty v-if="filteredConversations.length === 0 && !loading" description="Chưa có tin nhắn" />
            
            <van-swipe-cell v-for="conv in filteredConversations" :key="`${conv.targetType}_${conv.targetId}`">
              <div class="conversation-item" @click="openChat(conv)">
                <div class="avatar-wrap">
                  <van-badge :content="conv.unreadCount || ''" :max="99">
                    <van-image 
                      :src="getFullAvatarUrl(conv.targetAvatar || conv.avatar)" 
                      width="48" 
                      height="48"
                      fit="cover"
                      class="conv-avatar"
                    />
                  </van-badge>
                  <span v-if="conv.online" class="online-dot"></span>
                </div>
                <div class="conv-content">
                  <div class="conv-top">
                    <span class="conv-name">{{ conv.targetName || conv.nickname || conv.name || 'Người dùng không xác định' }}</span>
                    <span class="conv-time">{{ formatTime(conv.lastTime) }}</span>
                  </div>
                  <div class="conv-bottom">
                    <span class="conv-msg">
                      {{ conv.lastContent || conv.lastMessage || conv.lastMsg || '' }}
                    </span>
                    <van-icon v-if="imStore.isMuted(`${conv.targetType}_${conv.targetId}`)" name="bell-o" class="muted-icon" />
                  </div>
                </div>
              </div>
              <template #right>
                <van-button square type="primary" :text="conv.isTop ? 'Hủy' : 'Ghim'" @click.stop="toggleTop(conv)" />
                <van-button square type="danger" text="Xóa" @click.stop="deleteConv(conv)" />
              </template>
            </van-swipe-cell>
          </van-pull-refresh>
        </div>
      </van-tab>

      <van-tab title="Thông báo" name="notify" :badge="notifyUnread || ''">
        <div class="list-content">
          <van-empty v-if="notifications.length === 0" description="Chưa có thông báo" />
          
          <div 
            v-for="notify in notifications" 
            :key="notify.id" 
            class="notify-item"
            :class="{ unread: !notify.read }"
            @click="readNotify(notify)"
          >
            <div class="notify-icon" :class="notify.type">
              <van-icon :name="getNotifyIcon(notify.type)" />
            </div>
            <div class="notify-content">
              <div class="notify-title">{{ notify.title }}</div>
              <div class="notify-desc">{{ notify.content }}</div>
              <div class="notify-time">{{ formatTime(notify.time) }}</div>
            </div>
          </div>
        </div>
      </van-tab>
    </van-tabs>

    <van-action-sheet 
      v-model:show="showActions" 
      :actions="actions" 
      cancel-text="Hủy"
      @select="onActionSelect"
    />

    <van-popup v-model:show="showGroupCreate" position="bottom" round :style="{ height: '80%' }">
      <div class="group-create">
        <van-nav-bar title="Tạo nhóm chat" left-arrow @click-left="showGroupCreate = false" />
        <van-search v-model="userSearchKey" placeholder="Tìm liên hệ" />
        <div class="user-select-list">
          <van-checkbox-group v-model="selectedUsers">
            <van-cell-group inset>
              <van-cell 
                v-for="user in filteredUsers" 
                :key="user.userId"
                clickable
                @click="toggleUser(user.userId)"
              >
                <template #icon>
                  <van-image :src="getFullAvatarUrl(user.avatar)" round width="40" height="40" style="margin-right: 12px" />
                </template>
                <template #title>
                  <span class="user-name">{{ user.remark || user.nickname }}</span>
                </template>
                <template #right-icon>
                  <van-checkbox :name="user.userId" />
                </template>
              </van-cell>
            </van-cell-group>
          </van-checkbox-group>
        </div>
        <div class="popup-footer">
          <van-button type="primary" block round :disabled="selectedUsers.length < 2" @click="createGroup">
            Tạo nhóm ({{ selectedUsers.length }})
          </van-button>
        </div>
      </div>
    </van-popup>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { 
  showToast, showConfirmDialog
} from 'vant'
import { useImStore } from '@/stores/im'
import { createGroup as apiCreateGroup, fetchContacts } from '@/api/im'
import { TARGET_TYPE } from '@/utils/imWebSocket'
import dayjs from 'dayjs'
import relativeTime from 'dayjs/plugin/relativeTime'
import 'dayjs/locale/zh-cn'

dayjs.extend(relativeTime)
dayjs.locale('zh-cn')

const router = useRouter()
const imStore = useImStore()

const activeTab = ref('chat')
const refreshing = ref(false)
const loading = ref(false)
const showActions = ref(false)
const showGroupCreate = ref(false)
const isExiting = ref(false)
const searchKey = ref('')
const userSearchKey = ref('')
const selectedUsers = ref([])
const users = ref([])

const defaultAvatar = '/assets/img/cat.jpeg'

const getFullAvatarUrl = (avatar) => {
  if (!avatar) return defaultAvatar
  if (avatar.startsWith('http://') || avatar.startsWith('https://')) {
    return avatar
  }
  if (avatar.startsWith('/')) return avatar
  return '/' + avatar
}

const conversations = computed(() => imStore.conversations)
const notifications = computed(() => imStore.notifications)
const notifyUnread = computed(() => imStore.unreadNotifyCount)

const filteredConversations = computed(() => {
  if (!searchKey.value) return conversations.value
  const k = searchKey.value.toLowerCase()
  return conversations.value.filter(c => 
    (c.nickname || c.name || '').toLowerCase().includes(k)
  )
})

const filteredUsers = computed(() => {
  if (!userSearchKey.value) return users.value
  const k = userSearchKey.value.toLowerCase()
  return users.value.filter(u => 
    (u.remark || u.nickname || '').toLowerCase().includes(k)
  )
})

const actions = [
  { name: 'Trò chuyện riêng', value: 'private' },
  { name: 'Tạo nhóm chat', value: 'group' },
  { name: 'Thêm bạn', value: 'add_friend' },
  { name: 'Tham gia nhóm', value: 'join' }
]

const goBack = () => {
  isExiting.value = true
  setTimeout(() => {
    router.push('/')
  }, 250)
}

const formatTime = (timestamp) => {
  if (!timestamp) return ''
  const ts = timestamp < 10000000000 ? timestamp * 1000 : timestamp
  const time = dayjs(ts)
  const now = dayjs()
  
  if (now.isSame(time, 'day')) {
    return time.format('HH:mm')
  }
  if (now.subtract(1, 'day').isSame(time, 'day')) {
    return 'Hôm qua'
  }
  if (now.isSame(time, 'week')) {
    const weekDays = ['Chủ nhật', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7']
    return weekDays[time.day()]
  }
  if (now.isSame(time, 'year')) {
    return time.format('D/M')
  }
  return time.format('YYYY/M/D')
}

const getNotifyIcon = (type) => {
  const icons = { win: 'gold-coin-o', system: 'volume-o', activity: 'gift-o', security: 'shield-o' }
  return icons[type] || 'info-o'
}

const openChat = (conv) => {
  const chatId = conv.targetType === TARGET_TYPE.GROUP 
    ? `group_${conv.targetId}` 
    : `private_${conv.targetId}`
  router.push({
    name: 'ImChat',
    params: { chatId },
    query: { name: conv.nickname || conv.name }
  })
}

const toggleTop = async (conv) => {
  const chatId = `${conv.targetType}_${conv.targetId}`
  const wasTop = conv.isTop
  await imStore.toggleTopConversation(chatId)
  showToast(wasTop ? 'Đã bỏ ghim' : 'Đã ghim')
}

const deleteConv = async (conv) => {
  try {
    await showConfirmDialog({ title: 'Xác nhận xóa hội thoại?' })
    const chatId = `${conv.targetType}_${conv.targetId}`
    imStore.deleteConversation(chatId)
    showToast('Đã xóa')
  } catch {}
}

const readNotify = (notify) => {
  imStore.markNotifyRead(notify.id)
  if (notify.link) router.push(notify.link)
}

const onActionSelect = (action) => {
  if (action.value === 'private') {
    router.push('/im/contacts')
  } else if (action.value === 'group') {
    showToast('Chức năng chưa mở')
  } else if (action.value === 'add_friend') {
    router.push('/im/add-friend')
  } else {
    showToast('Chức năng chưa mở')
  }
}

const loadContacts = async () => {
  try {
    const res = await fetchContacts()
    users.value = Array.isArray(res?.data) ? res.data : []
  } catch (e) {
    users.value = []
  }
}

const toggleUser = (userId) => {
  const idx = selectedUsers.value.indexOf(userId)
  if (idx > -1) selectedUsers.value.splice(idx, 1)
  else selectedUsers.value.push(userId)
}

const createGroup = async () => {
  if (selectedUsers.value.length < 2) return showToast('Vui lòng chọn ít nhất 2 người')
  try {
    const res = await apiCreateGroup({ name: 'Nhóm mới', members: selectedUsers.value })
    const groupId = res.data?.groupId
    showToast('Tạo nhóm thành công')
    showGroupCreate.value = false
    if (groupId) {
      router.push({
        name: 'ImChat',
        params: { chatId: `group_${groupId}` },
        query: { name: 'Nhóm mới' }
      })
    }
  } catch (e) {
    showToast('Tạo nhóm thất bại')
  }
}

const onRefresh = async () => {
  await imStore.loadConversations()
  await imStore.loadUnreadCount()
  refreshing.value = false
}

onMounted(async () => {
  loading.value = true
  imStore.initIM()
  await imStore.loadConversations()
  await imStore.loadUnreadCount()
  loading.value = false
})
</script>

<style lang="less" scoped>
.conversation-list-page {
  min-height: 100vh;
  background: #ededed;
  animation: pageEnter 0.3s ease-out;
  transform-origin: center center;
  
  &.page-exit {
    animation: pageExit 0.25s ease-in forwards;
  }
}

@keyframes pageEnter {
  from {
    opacity: 0;
    transform: scale(1.05);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

@keyframes pageExit {
  from {
    opacity: 1;
    transform: scale(1);
  }
  to {
    opacity: 0;
    transform: scale(0.9);
  }
}

.im-header {
  display: flex;
  align-items: center;
  padding: 12px 16px;
  background: #ededed;
  
  .header-left {
    width: 40px;
    display: flex;
    align-items: center;
    
    .van-icon {
      font-size: 22px;
      color: #000;
    }
    
    &:active {
      opacity: 0.6;
    }
  }
  
  .header-title {
    flex: 1;
    font-size: 18px;
    font-weight: 600;
    color: #000;
    text-align: center;
  }
  
  .header-right {
    width: 40px;
    display: flex;
    justify-content: flex-end;
    
    .van-icon {
      color: #000;
      font-size: 22px;
    }
    
    &:active {
      opacity: 0.6;
    }
  }
}

.im-tabs {
  :deep(.van-tabs__wrap) {
    background: #ededed;
  }
  
  :deep(.van-tabs__nav) {
    background: #ededed;
  }
  
  :deep(.van-tab) {
    color: #000;
  }
  
  :deep(.van-tabs__line) {
    background: #07c160;
  }
}

.list-content {
  background: #fff;
  min-height: calc(100vh - 200px);
}

.conversation-item {
  display: flex;
  align-items: center;
  padding: 12px 16px;
  background: #fff;
  
  &:active {
    background: #ececec;
  }
  
  .avatar-wrap {
    position: relative;
    margin-right: 12px;
    flex-shrink: 0;
    
    .conv-avatar {
      border-radius: 6px !important;
      overflow: hidden;
      display: block;
    }
    
    :deep(.van-badge) {
      background: #fa5151;
    }
    
    :deep(.van-image) {
      border-radius: 6px;
      overflow: hidden;
    }
    
    .online-dot {
      position: absolute;
      right: 0;
      bottom: 0;
      width: 10px;
      height: 10px;
      background: #07c160;
      border: 2px solid #fff;
      border-radius: 50%;
    }
  }
  
  .conv-content {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    justify-content: center;
    height: 48px;
    
    .conv-top {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 4px;
      
      .conv-name {
        font-size: 16px;
        font-weight: 400;
        color: #000;
        flex: 1;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        margin-right: 10px;
        line-height: 1.3;
      }
      
      .conv-time {
        font-size: 12px;
        color: #b2b2b2;
        flex-shrink: 0;
      }
    }
    
    .conv-bottom {
      display: flex;
      align-items: center;
      
      .conv-msg {
        flex: 1;
        font-size: 13px;
        color: #b2b2b2;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        margin-right: 8px;
        line-height: 1.3;
      }
      
      .muted-icon {
        font-size: 14px;
        color: #b2b2b2;
      }
    }
  }
}

.notify-item {
  display: flex;
  padding: 16px 20px;
  align-items: center;
  background: #fff;
  border-bottom: 1px solid #f5f5f5;
  
  &.unread {
    background: #f0f9ff;
  }
  
  .notify-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 16px;
    color: #fff;
    font-size: 24px;
    
    &.win { background: linear-gradient(135deg, #ff9a9e, #ff6b81); }
    &.system { background: linear-gradient(135deg, #a1c4fd, #c2e9fb); }
    &.activity { background: linear-gradient(135deg, #84fab0, #8fd3f4); }
    &.security { background: linear-gradient(135deg, #cfd9df, #e2ebf0); }
  }
  
  .notify-content {
    flex: 1;
    
    .notify-title {
      font-size: 16px;
      font-weight: 500;
      margin-bottom: 4px;
    }
    .notify-desc {
      font-size: 13px;
      color: #666;
      margin-bottom: 4px;
    }
    .notify-time {
      font-size: 12px;
      color: #999;
    }
  }
}

.popup-footer {
  padding: 20px;
}

.user-select-list {
  flex: 1;
  overflow-y: auto;
  padding-top: 10px;
}

.group-create {
  height: 100%;
  display: flex;
  flex-direction: column;
}
</style>
