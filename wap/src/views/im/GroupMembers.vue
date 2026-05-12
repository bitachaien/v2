<template>
  <div class="group-members-page">
    <van-nav-bar
      :title="`群成员(${members.length})`"
      left-arrow
      @click-left="$router.back()"
    >
      <template #right>
        <van-icon 
          v-if="isAdmin" 
          name="plus" 
          size="18" 
          @click="showInvite = true" 
        />
      </template>
    </van-nav-bar>

    <van-search
      v-model="searchKeyword"
      placeholder="搜索群成员"
    />

    <van-list class="member-list">
      <van-cell
        v-for="member in filteredMembers"
        :key="member.userId"
        :title="member.nickname"
        :label="getRoleLabel(member.role)"
        is-link
        @click="goToProfile(member)"
      >
        <template #icon>
          <div class="member-avatar">
            <van-image
              round
              :src="member.avatar || defaultAvatar"
              width="40"
              height="40"
              fit="cover"
            />
            <span 
              v-if="imStore.isUserOnline(member.userId)"
              class="online-dot"
            ></span>
          </div>
        </template>
        <template #right-icon>
          <van-tag v-if="member.role === 'owner'" type="danger" size="medium">群主</van-tag>
          <van-tag v-else-if="member.role === 'admin'" type="primary" size="medium">管理员</van-tag>
        </template>
      </van-cell>
    </van-list>

    <van-empty
      v-if="filteredMembers.length === 0 && searchKeyword"
      description="未找到成员"
      image="search"
    />

    <van-popup
      v-model:show="showInvite"
      position="bottom"
      round
      :style="{ height: '70%' }"
    >
      <div class="invite-popup">
        <div class="popup-header">
          <span class="title">邀请成员</span>
          <van-icon name="cross" @click="showInvite = false" />
        </div>
        
        <van-search
          v-model="inviteSearch"
          placeholder="搜索好友"
        />
        
        <div class="friend-list">
          <van-checkbox-group v-model="selectedFriends">
            <van-cell-group>
              <van-cell
                v-for="friend in filteredFriends"
                :key="friend.userId"
                :title="friend.nickname"
                clickable
                @click="toggleFriend(friend.userId)"
              >
                <template #icon>
                  <van-image
                    round
                    :src="friend.avatar || defaultAvatar"
                    width="36"
                    height="36"
                    fit="cover"
                    style="margin-right: 12px"
                  />
                </template>
                <template #right-icon>
                  <van-checkbox
                    :name="friend.userId"
                    :disabled="memberIds.includes(friend.userId)"
                  />
                </template>
              </van-cell>
            </van-cell-group>
          </van-checkbox-group>
        </div>
        
        <div class="popup-footer">
          <van-button 
            type="primary" 
            block 
            :disabled="selectedFriends.length === 0"
            @click="inviteMembers"
          >
            邀请 {{ selectedFriends.length > 0 ? `(${selectedFriends.length})` : '' }}
          </van-button>
        </div>
      </div>
    </van-popup>

    <van-action-sheet
      v-model:show="showMemberAction"
      :actions="memberActions"
      cancel-text="取消"
      @select="handleMemberAction"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { showToast, showConfirmDialog } from 'vant'
import { useImStore } from '@/stores/im'
import { 
  fetchGroupMembers, inviteToGroup, kickFromGroup, setGroupAdmin,
  fetchContacts
} from '@/api/im'

const route = useRoute()
const router = useRouter()
const imStore = useImStore()

const groupId = Number(route.params.groupId)
const defaultAvatar = '/assets/img/cat.jpeg'

const members = ref([])
const friends = ref([])
const searchKeyword = ref('')
const inviteSearch = ref('')
const selectedFriends = ref([])
const showInvite = ref(false)
const showMemberAction = ref(false)
const selectedMember = ref(null)
const currentUserId = ref(Number(localStorage.getItem('userId')) || 0)
const currentUserRole = ref('member')

const isAdmin = computed(() => {
  return ['owner', 'admin'].includes(currentUserRole.value)
})

const memberIds = computed(() => members.value.map(m => m.userId))

const filteredMembers = computed(() => {
  if (!searchKeyword.value) return members.value
  const keyword = searchKeyword.value.toLowerCase()
  return members.value.filter(m => 
    (m.nickname || '').toLowerCase().includes(keyword)
  )
})

const filteredFriends = computed(() => {
  let list = friends.value.filter(f => !memberIds.value.includes(f.userId))
  if (inviteSearch.value) {
    const keyword = inviteSearch.value.toLowerCase()
    list = list.filter(f => (f.nickname || '').toLowerCase().includes(keyword))
  }
  return list
})

const memberActions = computed(() => {
  if (!selectedMember.value) return []
  
  const actions = []
  
  actions.push({ name: '查看资料', action: 'profile' })
  
  actions.push({ name: '@ TA', action: 'mention' })
  
  if (selectedMember.value.userId !== currentUserId.value) {
    actions.push({ name: '发消息', action: 'chat' })
  }
  
  if (isAdmin.value && selectedMember.value.userId !== currentUserId.value) {
    if (selectedMember.value.role !== 'owner') {
      if (currentUserRole.value === 'owner') {
        actions.push({
          name: selectedMember.value.role === 'admin' ? '取消管理员' : '设为管理员',
          action: 'toggleAdmin'
        })
      }
      actions.push({ name: '移出群聊', action: 'kick', color: '#ee0a24' })
    }
  }
  
  return actions
})

function getRoleLabel(role) {
  const map = {
    owner: '群主',
    admin: '管理员',
    member: ''
  }
  return map[role] || ''
}

function toggleFriend(friendId) {
  const index = selectedFriends.value.indexOf(friendId)
  if (index > -1) {
    selectedFriends.value.splice(index, 1)
  } else {
    selectedFriends.value.push(friendId)
  }
}

function goToProfile(member) {
  if (isAdmin.value && member.userId !== currentUserId.value) {
    selectedMember.value = member
    showMemberAction.value = true
  } else {
    router.push({
      name: 'UserProfile',
      params: { userId: member.userId }
    })
  }
}

async function handleMemberAction(action) {
  if (!selectedMember.value) return
  
  switch (action.action) {
    case 'profile':
      router.push({
        name: 'UserProfile',
        params: { userId: selectedMember.value.userId }
      })
      break
      
    case 'mention':
      router.push({
        name: 'ImChat',
        params: { chatId: `group_${groupId}` },
        query: { mention: selectedMember.value.nickname }
      })
      break
      
    case 'chat':
      router.push({
        name: 'ImChat',
        params: { chatId: `private_${selectedMember.value.userId}` }
      })
      break
      
    case 'toggleAdmin':
      await toggleAdmin()
      break
      
    case 'kick':
      await kickMember()
      break
  }
  
  showMemberAction.value = false
}

async function toggleAdmin() {
  try {
    const member = selectedMember.value
    const newIsAdmin = member.role !== 'admin'
    await setGroupAdmin(groupId, {
      userId: member.userId,
      isAdmin: newIsAdmin
    })
    
    member.role = newIsAdmin ? 'admin' : 'member'
    showToast(newIsAdmin ? '已设为管理员' : '已取消管理员')
  } catch (e) {
    console.error('操作失败:', e)
    showToast('操作失败')
  }
}

async function kickMember() {
  try {
    await showConfirmDialog({
      title: '移出群聊',
      message: `确定要将 ${selectedMember.value.nickname} 移出群聊吗？`
    })
    
    await kickFromGroup(groupId, { userId: selectedMember.value.userId })
    
    const index = members.value.findIndex(m => m.userId === selectedMember.value.userId)
    if (index > -1) {
      members.value.splice(index, 1)
    }
    showToast('已移出群聊')
  } catch (e) {
    if (e?.name !== 'cancel') {
      showToast('操作失败')
    }
  }
}

async function inviteMembers() {
  if (selectedFriends.value.length === 0) return
  
  try {
    await inviteToGroup(groupId, { userIds: selectedFriends.value })
    showToast(`已邀请 ${selectedFriends.value.length} 人`)
    showInvite.value = false
    selectedFriends.value = []
    loadData()
  } catch (e) {
    console.error('邀请失败:', e)
    showToast('邀请失败')
  }
}

async function loadData() {
  try {
    const [membersRes, contactsRes] = await Promise.allSettled([
      fetchGroupMembers(groupId),
      fetchContacts()
    ])

    if (membersRes.status === 'fulfilled') {
      members.value = Array.isArray(membersRes.value?.data) ? membersRes.value.data : []
      const me = members.value.find(m => m.userId === currentUserId.value)
      if (me) {
        currentUserRole.value = me.role
      }
    }
    
    if (contactsRes.status === 'fulfilled') {
      friends.value = Array.isArray(contactsRes.value?.data) ? contactsRes.value.data : []
    }
  } catch (e) {
    console.error('加载失败:', e)
  }
}

onMounted(() => {
  loadData()
})
</script>

<style scoped lang="less">
.group-members-page {
  min-height: 100vh;
  background: #f5f5f5;
  
  .member-list {
    .member-avatar {
      position: relative;
      margin-right: 12px;
      
      .online-dot {
        position: absolute;
        bottom: 2px;
        right: 2px;
        width: 10px;
        height: 10px;
        background: #07c160;
        border-radius: 50%;
        border: 2px solid #fff;
      }
    }
  }
  
  .invite-popup {
    height: 100%;
    display: flex;
    flex-direction: column;
    
    .popup-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 16px;
      border-bottom: 1px solid #eee;
      
      .title {
        font-size: 16px;
        font-weight: 500;
      }
    }
    
    .friend-list {
      flex: 1;
      overflow-y: auto;
    }
    
    .popup-footer {
      padding: 12px 16px;
      border-top: 1px solid #eee;
    }
  }
}
</style>
