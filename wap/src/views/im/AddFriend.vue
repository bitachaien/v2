<template>
  <div class="add-friend-page">
    <van-nav-bar
      title="Thêm bạn"
      left-arrow
      @click-left="router.back()"
    />
    <van-search
      v-model="keyword"
      placeholder="Tìm ID/Biệt danh người dùng"
      show-action
      @search="onSearch"
    >
      <template #action>
        <div @click="onSearch">Tìm kiếm</div>
      </template>
    </van-search>

    <div class="search-result" v-if="hasSearched">
      <van-empty v-if="users.length === 0" description="Không tìm thấy người dùng" />
      <van-cell-group v-else title="Kết quả tìm kiếm">
        <van-cell
          v-for="user in users"
          :key="user.userId"
          :title="user.nickname || user.username"
          :label="user.signature || 'Chưa có chữ ký'"
          center
        >
          <template #icon>
            <van-image
              round
              width="40"
              height="40"
              :src="getFullAvatarUrl(user.avatar)"
              style="margin-right: 10px"
            />
          </template>
          <template #right-icon>
            <van-button
              v-if="user.isFriend"
              size="small"
              disabled
            >Đã thêm</van-button>
            <van-button
              v-else
              type="primary"
              size="small"
              @click="openRequestDialog(user)"
            >Thêm vào danh bạ</van-button>
          </template>
        </van-cell>
      </van-cell-group>
    </div>

    <van-dialog
      v-model:show="showRequestDialog"
      title="Gửi yêu cầu kết bạn"
      show-cancel-button
      @confirm="sendRequest"
    >
      <van-field
        v-model="requestMessage"
        placeholder="Tôi là..."
        maxlength="50"
        show-word-limit
        type="textarea"
        rows="2"
      />
    </van-dialog>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { showToast } from 'vant'
import { searchUser, sendFriendRequest } from '@/api/im'

const router = useRouter()
const keyword = ref('')
const users = ref([])
const hasSearched = ref(false)
const defaultAvatar = '/assets/img/cat.jpeg'

const showRequestDialog = ref(false)
const requestMessage = ref('')
const currentTarget = ref(null)

const getFullAvatarUrl = (avatar) => {
  if (!avatar) return defaultAvatar
  if (avatar.startsWith('http://') || avatar.startsWith('https://') || avatar.startsWith('/')) {
    return avatar
  }
  return '/' + avatar
}

const onSearch = async () => {
  if (!keyword.value.trim()) return
  try {
    const res = await searchUser(keyword.value)
    if (res?.data) {
      users.value = Array.isArray(res.data) ? res.data : [res.data]
    } else {
      users.value = []
    }
    hasSearched.value = true
  } catch (e) {
    users.value = []
    hasSearched.value = true
    if (!e.message?.includes('404') && !e.message?.includes('Not Found')) {
      console.error(e)
      showToast('Tìm kiếm thất bại')
    }
  }
}

const openRequestDialog = (user) => {
  currentTarget.value = user
  requestMessage.value = 'Tôi là'
  showRequestDialog.value = true
}

const sendRequest = async () => {
  if (!currentTarget.value) return
  const targetUserId = currentTarget.value.userId
  if (!targetUserId) {
    showToast('ID người dùng không hợp lệ')
    return
  }
  try {
    await sendFriendRequest({
      userId: targetUserId,
      remark: requestMessage.value
    })
    showToast('Đã gửi yêu cầu')
  } catch (e) {
    showToast(e.message || 'Gửi thất bại')
  }
}
</script>

<style scoped lang="less">
.add-friend-page {
  min-height: 100vh;
  background: #f5f5f5;
}
</style>
