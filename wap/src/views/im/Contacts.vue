<template>
  <div class="contacts-page">
    <van-nav-bar
      title="Danh bạ"
    >
      <template #right>
        <van-icon name="add-o" size="18" style="margin-right: 16px" @click="router.push('/im/add-friend')" />
        <van-icon name="search" size="18" @click="showSearch = true" />
      </template>
    </van-nav-bar>

    <van-search
      v-if="showSearch"
      v-model="searchKeyword"
      placeholder="Tìm liên hệ"
      show-action
      @cancel="showSearch = false; searchKeyword = ''"
    />

    <van-index-bar :index-list="indexList">
      <div class="special-items">
        <van-cell
          title="Bạn mới"
          is-link
          to="/im/friend-requests"
          class="special-cell"
        >
          <template #icon>
            <div class="special-icon new-friend">
              <van-icon name="friends-o" />
            </div>
          </template>
          <template #value>
            <van-badge v-if="friendRequestCount > 0" :content="friendRequestCount" />
          </template>
        </van-cell>
        <van-cell
          title="Nhóm chat"
          is-link
          to="/im/groups"
          class="special-cell"
        >
          <template #icon>
            <div class="special-icon groups">
              <van-icon name="friends" />
            </div>
          </template>
          <template #value>
            <span class="group-count">{{ groups.length }} nhóm</span>
          </template>
        </van-cell>
      </div>

      <template v-for="letter in indexList" :key="letter">
        <van-index-anchor :index="letter" />
        <van-cell
          v-for="contact in getContactsByLetter(letter)"
          :key="contact.userId"
          :title="contact.remark || contact.nickname"
          :label="contact.signature"
          is-link
          @click="goToChat(contact)"
        >
          <template #icon>
            <div class="contact-avatar">
              <van-image
                round
                :src="getFullAvatarUrl(contact.avatar)"
                width="40"
                height="40"
                fit="cover"
              />
              <span 
                v-if="imStore.isUserOnline(contact.userId)"
                class="online-dot"
              ></span>
            </div>
          </template>
        </van-cell>
      </template>
    </van-index-bar>

    <van-empty
      v-if="filteredContacts.length === 0 && !loading"
      description="Chưa có liên hệ"
      image="search"
    />

    <van-loading v-if="loading" class="loading" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useImStore } from '@/stores/im'
import { fetchContacts, fetchGroups, fetchFriendRequests } from '@/api/im'

const router = useRouter()
const imStore = useImStore()

const loading = ref(false)
const showSearch = ref(false)
const searchKeyword = ref('')
const contacts = ref([])
const groups = ref([])
const friendRequestCount = ref(0)
const defaultAvatar = '/assets/img/cat.jpeg'

const getFullAvatarUrl = (avatar) => {
  if (!avatar) return defaultAvatar
  if (avatar.startsWith('http://') || avatar.startsWith('https://') || avatar.startsWith('/')) {
    return avatar
  }
  return '/' + avatar
}

const indexList = computed(() => {
  const letters = new Set()
  filteredContacts.value.forEach(c => {
    const letter = (c.pinyin?.[0] || '#').toUpperCase()
    letters.add(/[A-Z]/.test(letter) ? letter : '#')
  })
  return Array.from(letters).sort((a, b) => {
    if (a === '#') return 1
    if (b === '#') return -1
    return a.localeCompare(b)
  })
})

const filteredContacts = computed(() => {
  if (!searchKeyword.value) return contacts.value
  const keyword = searchKeyword.value.toLowerCase()
  return contacts.value.filter(c => 
    (c.nickname || '').toLowerCase().includes(keyword) ||
    (c.pinyin || '').toLowerCase().includes(keyword)
  )
})

function getContactsByLetter(letter) {
  return filteredContacts.value.filter(c => {
    const firstLetter = (c.pinyin?.[0] || '#').toUpperCase()
    const actualLetter = /[A-Z]/.test(firstLetter) ? firstLetter : '#'
    return actualLetter === letter
  })
}

function goToChat(contact) {
  router.push({
    name: 'ImChat',
    params: { chatId: `private_${contact.userId}` },
    query: { name: contact.remark || contact.nickname }
  })
}

async function loadData() {
  loading.value = true
  try {
    const [contactsRes, groupsRes, requestsRes] = await Promise.allSettled([
      fetchContacts(),
      fetchGroups(),
      fetchFriendRequests()
    ])

    if (contactsRes.status === 'fulfilled') {
      contacts.value = Array.isArray(contactsRes.value?.data) ? contactsRes.value.data : []
    }
    
    if (groupsRes.status === 'fulfilled') {
      groups.value = Array.isArray(groupsRes.value?.data) ? groupsRes.value.data : []
    }
    
    if (requestsRes.status === 'fulfilled') {
      const requests = Array.isArray(requestsRes.value?.data) ? requestsRes.value.data : []
      friendRequestCount.value = requests.filter(r => r.status === 'pending').length
    }
  } catch (e) {
    console.error('Tải danh bạ thất bại:', e)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  imStore.initIM()
  loadData()
})
</script>

<style scoped lang="less">
.contacts-page {
  min-height: 100vh;
  background: #f5f5f5;
  
  .special-items {
    background: #fff;
    margin-bottom: 10px;
    
    .special-cell {
      .special-icon {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 12px;
        
        .van-icon {
          font-size: 20px;
          color: #fff;
        }
        
        &.new-friend {
          background: linear-gradient(135deg, #ff9a56, #ff6b35);
        }
        
        &.groups {
          background: linear-gradient(135deg, #56ccf2, #2f80ed);
        }
      }
      
      .group-count {
        color: #999;
        font-size: 12px;
      }
    }
  }
  
  .contact-avatar {
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
  
  :deep(.van-index-anchor) {
    background: #f5f5f5;
    padding: 4px 16px;
    font-size: 12px;
    color: #999;
  }
  
  :deep(.van-index-bar__sidebar) {
    right: 4px;
  }
  
  .loading {
    display: flex;
    justify-content: center;
    padding: 40px;
  }
}
</style>
