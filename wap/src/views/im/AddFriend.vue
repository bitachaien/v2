<template>
  <div class="add-friend-page">
    <van-nav-bar
      title="添加朋友"
      left-arrow
      @click-left="router.back()"
    />
    <van-search
      v-model="keyword"
      placeholder="搜索用户ID/昵称"
      show-action
      @search="onSearch"
    >
      <template #action>
        <div @click="onSearch">搜索</div>
      </template>
    </van-search>

    <div class="search-result" v-if="hasSearched">
      <van-empty v-if="users.length === 0" description="未找到用户" />
      <van-cell-group v-else title="搜索结果">
        <van-cell
          v-for="user in users"
          :key="user.userId"
          :title="user.nickname || user.username"
          :label="user.signature || '暂无个性签名'"
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
            >已添加</van-button>
            <van-button
              v-else
              type="primary"
              size="small"
              @click="openRequestDialog(user)"
            >添加到通讯录</van-button>
          </template>
        </van-cell>
      </van-cell-group>
    </div>

    <van-dialog
      v-model:show="showRequestDialog"
      title="发送好友申请"
      show-cancel-button
      @confirm="sendRequest"
    >
      <van-field
        v-model="requestMessage"
        placeholder="我是..."
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
      showToast('搜索失败')
    }
  }
}

const openRequestDialog = (user) => {
  currentTarget.value = user
  requestMessage.value = '我是'
  showRequestDialog.value = true
}

const sendRequest = async () => {
  if (!currentTarget.value) return
  const targetUserId = currentTarget.value.userId
  if (!targetUserId) {
    showToast('用户ID无效')
    return
  }
  try {
    await sendFriendRequest({
      userId: targetUserId,
      remark: requestMessage.value
    })
    showToast('已发送申请')
  } catch (e) {
    showToast(e.message || '发送失败')
  }
}
</script>

<style scoped lang="less">
.add-friend-page {
  min-height: 100vh;
  background: #f5f5f5;
}
</style>
