<template>
  <div class="message-center">
    <div class="nav-header">
      <div class="nav-back" @click="$router.back()">
        <van-icon name="arrow-left" />
      </div>
      <div class="nav-title">Trung Tâm Tin Nhắn</div>
      <div class="nav-right" @click="showToast('Chức năng cài đặt đang phát triển')">
        <van-icon name="setting-o" />
      </div>
    </div>

    <div class="tabs-wrapper">
      <div class="tabs-scroll">
        <div 
          v-for="tab in tabs" 
          :key="tab.key"
          class="tab-item"
          :class="{ active: activeTab === tab.key }"
          @click="switchTab(tab.key)"
        >
          {{ tab.label }}
        </div>
      </div>
    </div>

    <div class="filter-row" v-if="activeTab === 'notice'">
      <div class="filter-item" @click="showStatusDropdown = !showStatusDropdown">
        <span>{{ currentStatusLabel }}</span>
        <van-icon :name="showStatusDropdown ? 'arrow-up' : 'arrow-down'" />
        <div class="dropdown-list" v-show="showStatusDropdown">
          <div 
            v-for="item in statusOptions" 
            :key="item.value"
            class="dropdown-item"
            :class="{ active: currentStatus === item.value }"
            @click.stop="selectStatus(item.value)"
          >
            {{ item.label }}
          </div>
        </div>
      </div>

      <div class="search-box">
        <van-field 
          v-model="searchKeyword" 
          placeholder="Tìm kiếm"
          :border="false"
        >
          <template #right-icon>
            <van-icon name="search" class="search-icon" @click="handleSearch" />
          </template>
        </van-field>
      </div>
    </div>

    <div class="content-area">
      <div v-if="activeTab === 'service'" class="tab-content">
        <div class="empty-state">
          <img src="/assets/img/img_none_sj.avif" class="empty-icon" />
          <p>Chưa có tin nhắn</p>
        </div>
      </div>

      <div v-else-if="activeTab === 'announcement'" class="tab-content">
        <van-list
          v-model:loading="loading"
          :finished="finished"
          :immediate-check="false"
          finished-text=""
          @load="loadAnnouncements"
        >
          <div v-if="announcements.length > 0" class="message-list">
            <div 
              v-for="item in announcements" 
              :key="item.id" 
              class="message-card"
              @click="openDetail(item)"
            >
              <div class="message-title">{{ item.title }}</div>
              <div class="message-summary">{{ item.summary }}</div>
              <div class="message-time">{{ formatTime(item.createdAt) }}</div>
            </div>
          </div>
          <div v-else-if="!loading" class="empty-state">
            <img src="/assets/img/img_none_sj.avif" class="empty-icon" />
            <p>Chưa có tin nhắn</p>
          </div>
        </van-list>
      </div>

      <div v-else-if="activeTab === 'notice'" class="tab-content">
        <van-list
          v-model:loading="messageLoading"
          :finished="messageFinished"
          :immediate-check="false"
          finished-text=""
          @load="loadMessages"
        >
          <div v-if="messages.length > 0" class="message-list">
            <div 
              v-for="item in messages" 
              :key="item.id" 
              class="message-card"
              :class="{ unread: !item.isRead }"
              @click="openMessageDetail(item)"
            >
              <div class="message-title">
                <span class="unread-dot" v-if="!item.isRead"></span>
                {{ item.title }}
              </div>
              <div class="message-summary">{{ item.summary }}</div>
              <div class="message-time">{{ formatTime(item.sentTime) }}</div>
            </div>
          </div>
          <div v-else-if="!messageLoading" class="empty-state">
            <img src="/assets/img/img_none_sj.avif" class="empty-icon" />
            <p>暂无消息</p>
          </div>
        </van-list>
      </div>

      <div v-else-if="activeTab === 'marquee'" class="tab-content">
        <van-list
          v-model:loading="marqueeLoading"
          :finished="marqueeFinished"
          finished-text=""
          @load="loadMarquees"
        >
          <div v-if="marquees.length > 0" class="message-list">
            <div 
              v-for="item in marquees" 
              :key="item.id" 
              class="message-card"
            >
              <div class="message-content">{{ item.content }}</div>
              <div class="message-time">{{ formatTime(item.createdAt) }}</div>
            </div>
          </div>
          <div v-else-if="!marqueeLoading" class="empty-state">
            <img src="/assets/img/img_none_sj.avif" class="empty-icon" />
            <p>暂无消息</p>
          </div>
        </van-list>
      </div>

      <div v-else-if="activeTab === 'feedback'" class="tab-content">
        <div class="sub-tabs-row">
          <div class="sub-tabs">
            <div 
              class="sub-tab-item" 
              :class="{ active: feedbackSubTab === 'create' }"
              @click="feedbackSubTab = 'create'"
            >
              创建反馈
            </div>
            <div 
              class="sub-tab-item" 
              :class="{ active: feedbackSubTab === 'my' }"
              @click="feedbackSubTab = 'my'"
            >
              我的反馈
            </div>
          </div>
          <div class="reward-claim">
            <div class="pending-amount">
              <span class="label">待领取</span>
              <span class="amount">{{ pendingReward.toFixed(2) }}</span>
            </div>
            <button 
              class="claim-btn" 
              :class="{ disabled: pendingReward <= 0 }"
              @click="claimReward"
            >
              一键领取
            </button>
          </div>
        </div>

        <div v-if="feedbackSubTab === 'create'" class="feedback-form">
          <div class="form-item">
            <div class="form-label">反馈类型<span class="required">*</span></div>
            <div class="form-select-wrap">
              <div class="form-select" @click="showFeedbackType = !showFeedbackType">
                <span :class="{ placeholder: !feedbackForm.type }">
                  {{ feedbackForm.type || '请选择反馈类型' }}
                </span>
                <van-icon :name="showFeedbackType ? 'arrow-up' : 'arrow-down'" />
              </div>
              <div class="dropdown-list feedback-dropdown" v-show="showFeedbackType">
                <div 
                  v-for="item in feedbackTypes" 
                  :key="item.name"
                  class="dropdown-item"
                  :class="{ active: feedbackForm.type === item.name }"
                  @click="selectFeedbackType(item)"
                >
                  {{ item.name }}
                </div>
              </div>
            </div>
          </div>

          <div class="form-item">
            <div class="form-label">Nội dung phản hồi (Bạn góp ý, chúng tôi cải thiện)<span class="required">*</span></div>
            <div class="form-textarea">
              <textarea
                v-model="feedbackForm.content"
                placeholder="Mọi ý kiến của bạn đều rất quan trọng với chúng tôi. Những ý kiến có giá trị sẽ được áp dụng và tùy theo mức độ quan trọng sẽ nhận được phần thưởng tiền mặt khác nhau. Hãy thoải mái chia sẻ!"
                maxlength="1000"
              ></textarea>
              <div class="textarea-count">{{ feedbackForm.content.length }}/1000</div>
            </div>
          </div>

          <div class="form-item">
            <div class="form-label">Hình ảnh minh chứng<span class="tip">(Dễ được chấp nhận hơn)</span></div>
            <div class="upload-area">
              <div class="upload-box" v-for="(img, idx) in feedbackForm.images" :key="idx">
                <img :src="img" class="preview-img" />
                <van-icon name="cross" class="remove-btn" @click="removeImage(idx)" />
              </div>
              <div class="upload-box add" v-if="feedbackForm.images.length < 3" @click="triggerUpload">
                <van-icon name="plus" />
              </div>
              <input 
                type="file" 
                ref="feedbackImageInput" 
                accept="image/*" 
                style="display: none" 
                @change="handleFeedbackImage"
              />
            </div>
            <div class="upload-tip">Hỗ trợ tải lên hình ảnh và video, kích thước ảnh không quá 2MB, video không quá 20MB</div>
          </div>

          <div class="reward-rules">
            <div class="rules-title">Quy tắc thưởng</div>
            <div class="rules-content">
              Chúng tôi đã thiết lập quỹ thưởng lớn để thu thập ý kiến phản hồi, giúp chúng tôi tối ưu hóa hệ thống và tính năng, mang đến trải nghiệm tốt hơn cho bạn! Một khi được chấp nhận, sẽ nhận thưởng tùy theo mức độ quan trọng (không áp dụng cho ý kiến không được chấp nhận).
            </div>
          </div>

          <div class="submit-area">
            <button class="submit-btn" @click="submitFeedback">Gửi Phản Hồi</button>
          </div>
        </div>

        <div v-else class="my-feedback">
          <div v-if="myFeedbacks.length > 0" class="feedback-list">
            <div v-for="item in myFeedbacks" :key="item.id" class="feedback-card">
              <div class="feedback-type">{{ item.type }}</div>
              <div class="feedback-content">{{ item.content }}</div>
              <div class="feedback-footer">
                <span class="feedback-time">{{ formatTime(item.createdAt) }}</span>
                <span class="feedback-status" :class="item.status">{{ getStatusText(item.status) }}</span>
              </div>
            </div>
          </div>
          <div v-else class="empty-state">
            <img src="/assets/img/img_none_sj.avif" class="empty-icon" />
            <p>Chưa có phản hồi</p>
          </div>
        </div>
      </div>
    </div>

    <van-popup
      v-model:show="showMessagePopup"
      position="center"
      round
      :style="{ width: '90%', maxHeight: '70%' }"
    >
      <div class="message-detail-popup" v-if="currentMessage">
        <div class="popup-header">
          <span class="popup-title">Chi Tiết Tin Nhắn</span>
          <van-icon name="cross" @click="showMessagePopup = false" />
        </div>
        <div class="popup-body">
          <h3 class="detail-title">{{ currentMessage.title }}</h3>
          <div class="detail-meta">
            <span class="detail-time">{{ formatTime(currentMessage.sentTime) }}</span>
          </div>
          <div class="detail-content" v-html="currentMessage.content || currentMessage.summary"></div>
        </div>
      </div>
    </van-popup>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { showToast } from 'vant'
import { noticeApi } from '@/api/notice'
import { messageApi } from '@/api/message'

const router = useRouter()
const route = useRoute()

const tabs = [
  { key: 'service', label: '客服' },
  { key: 'announcement', label: '公告' },
  { key: 'notice', label: '通知' },
  { key: 'marquee', label: '跑马灯' },
  { key: 'feedback', label: '有奖反馈' }
]

const activeTab = ref('notice')

const statusOptions = [
  { label: '全部', value: 'all' },
  { label: '未读', value: 'unread' },
  { label: '已读', value: 'read' }
]

const currentStatus = ref('all')
const showStatusDropdown = ref(false)
const searchKeyword = ref('')

const currentStatusLabel = computed(() => {
  return statusOptions.find(o => o.value === currentStatus.value)?.label || '全部'
})

const announcements = ref([])
const loading = ref(false)
const finished = ref(false)
const page = ref(1)

const messages = ref([])
const messageLoading = ref(false)
const messageFinished = ref(false)
const messagePage = ref(1)
const showMessagePopup = ref(false)
const currentMessage = ref(null)

const marquees = ref([])
const marqueeLoading = ref(false)
const marqueeFinished = ref(false)
const marqueePage = ref(1)

const feedbackSubTab = ref('create')
const showFeedbackType = ref(false)
const feedbackForm = ref({
  type: '',
  content: '',
  images: []
})
const feedbackTypes = [
  { name: '游戏问题' },
  { name: '登录注册' },
  { name: '活动问题' },
  { name: '代理问题' },
  { name: '充值问题' },
  { name: '提现问题' },
  { name: '优化建议' }
]
const myFeedbacks = ref([])
const feedbackImageInput = ref(null)
const pendingReward = ref(0)

const switchTab = (key) => {
  activeTab.value = key
}

const selectStatus = (value) => {
  currentStatus.value = value
  showStatusDropdown.value = false
  resetAndLoad()
}

const handleSearch = () => {
  resetAndLoad()
}

const resetAndLoad = () => {
  messagePage.value = 1
  messages.value = []
  messageFinished.value = false
  loadMessages()
}

const loadAnnouncements = async () => {
  if (finished.value) {
    loading.value = false
    return
  }
  
  try {
    const res = await noticeApi.getNoticeList({
      page: page.value,
      pageSize: 20
    })
    
    if (res.code === 0 && res.data) {
      const list = res.data.list || []
      const total = res.data.total || 0
      
      if (list.length > 0) {
        announcements.value.push(...list)
        page.value++
      }
      
      if (list.length === 0 || announcements.value.length >= total) {
        finished.value = true
      }
    } else {
      finished.value = true
    }
  } catch (e) {
    finished.value = true
  } finally {
    loading.value = false
  }
}

const loadMessages = async () => {
  if (messageFinished.value) {
    messageLoading.value = false
    return
  }
  
  try {
    const params = {
      page: messagePage.value,
      pageSize: 20
    }
    
    if (currentStatus.value === 'unread') {
      params.unreadOnly = true
    }
    if (searchKeyword.value) {
      params.keyword = searchKeyword.value
    }
    
    const res = await messageApi.getMessageList(params)
    
    if (res && res.code === 0 && res.data) {
      const list = res.data.list || []
      const total = res.data.total || 0
      
      if (list.length > 0) {
        messages.value.push(...list)
        messagePage.value++
      }
      
      if (list.length === 0 || messages.value.length >= total) {
        messageFinished.value = true
      }
    } else {
      messageFinished.value = true
    }
  } catch (e) {
    messageFinished.value = true
  } finally {
    messageLoading.value = false
  }
}

const loadMarquees = async () => {
  marqueeFinished.value = true
  marqueeLoading.value = false
}

const openDetail = async (item) => {
  if (!item.isRead) {
    try {
      await noticeApi.markRead({ id: item.id })
      item.isRead = true
    } catch (e) {}
  }
  router.push(`/notice/${item.id}`)
}

const openMessageDetail = async (item) => {
  if (!item.isRead) {
    try {
      await messageApi.markRead([item.id])
      item.isRead = true
    } catch (e) {}
  }
  currentMessage.value = item
  showMessagePopup.value = true
}

const selectFeedbackType = (action) => {
  feedbackForm.value.type = action.name
  showFeedbackType.value = false
}

const triggerUpload = () => {
  feedbackImageInput.value?.click()
}

const handleFeedbackImage = async (e) => {
  const file = e.target.files[0]
  if (!file) return
  
  if (!file.type.startsWith('image/')) {
    showToast('Vui lòng chọn file hình ảnh')
    return
  }
  
  if (file.size > 2 * 1024 * 1024) {
    showToast('Kích thước ảnh không được vượt quá 2MB')
    return
  }
  
  const reader = new FileReader()
  reader.onload = (event) => {
    feedbackForm.value.images.push(event.target.result)
  }
  reader.readAsDataURL(file)
  
  e.target.value = ''
}

const removeImage = (idx) => {
  feedbackForm.value.images.splice(idx, 1)
}

const submitFeedback = () => {
  if (!feedbackForm.value.type) {
    showToast('请选择反馈类型')
    return
  }
  if (!feedbackForm.value.content.trim()) {
    showToast('请输入反馈内容')
    return
  }
  
  showToast('提交成功')
  feedbackForm.value = { type: '', content: '', images: [] }
}

const getStatusText = (status) => {
  const map = {
    pending: '待处理',
    processing: '处理中',
    resolved: '已采纳',
    rejected: '未采纳'
  }
  return map[status] || status
}

const claimReward = () => {
  if (pendingReward.value <= 0) {
    showToast('暂无可领取奖励')
    return
  }
  showToast('领取成功')
  pendingReward.value = 0
}

const formatTime = (ts) => {
  if (!ts) return ''
  const d = new Date(ts)
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')} ${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`
}

onMounted(() => {
  const tab = route.query.tab
  if (tab && tabs.some(t => t.key === tab)) {
    activeTab.value = tab
  }
  if (activeTab.value === 'notice') {
    messageLoading.value = true
    loadMessages()
  } else if (activeTab.value === 'announcement') {
    loading.value = true
    loadAnnouncements()
  }
})

watch(activeTab, (newTab) => {
  if (newTab === 'announcement' && announcements.value.length === 0 && !loading.value && !finished.value) {
    loading.value = true
    loadAnnouncements()
  }
  if (newTab === 'notice' && messages.value.length === 0 && !messageLoading.value && !messageFinished.value) {
    messageLoading.value = true
    loadMessages()
  }
})
</script>

<style scoped>
.message-center {
  min-height: 100vh;
  background: #f5f5f5;
}

.nav-header {
  position: sticky;
  top: 0;
  z-index: 100;
  height: 50px;
  background: #fff;
  display: flex;
  align-items: center;
  padding: 0 15px;
  border-bottom: 1px solid #eee;
}

.nav-back {
  width: 40px;
  display: flex;
  align-items: center;
  font-size: 20px;
  color: #333;
}

.nav-title {
  flex: 1;
  text-align: center;
  font-size: 17px;
  font-weight: 600;
  color: #333;
}

.nav-right {
  width: 40px;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  font-size: 20px;
  color: #666;
}

.tabs-wrapper {
  position: sticky;
  top: 50px;
  z-index: 99;
  background: #fff;
  border-bottom: 1px solid #eee;
}

.tabs-scroll {
  display: flex;
  padding: 0 10px;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

.tabs-scroll::-webkit-scrollbar {
  display: none;
}

.tab-item {
  flex-shrink: 0;
  padding: 15px 20px;
  font-size: 14px;
  color: #666;
  position: relative;
}

.tab-item.active {
  color: #26A17B;
  font-weight: 600;
}

.tab-item.active::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 20px;
  height: 3px;
  background: #26A17B;
  border-radius: 2px;
}

.filter-row {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 15px;
  background: #fff;
}

.filter-item {
  position: relative;
  display: flex;
  align-items: center;
  gap: 5px;
  padding: 8px 12px;
  background: #fff;
  border: 1px solid #e0e0e0;
  border-radius: 20px;
  font-size: 13px;
  color: #333;
}

.filter-item .van-icon {
  font-size: 12px;
  color: #999;
}

.dropdown-list {
  position: absolute;
  top: 100%;
  left: 0;
  margin-top: 4px;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.1);
  z-index: 100;
  min-width: 100px;
  overflow: hidden;
}

.dropdown-item {
  padding: 12px 15px;
  font-size: 13px;
  color: #333;
  border-bottom: 1px solid #f5f5f5;
}

.dropdown-item:last-child {
  border-bottom: none;
}

.dropdown-item.active {
  color: #26A17B;
}

.search-box {
  flex: 1;
  background: #fff;
  border: 1px solid #e0e0e0;
  border-radius: 20px;
  overflow: hidden;
}

.search-box :deep(.van-field) {
  padding: 4px 12px;
}

.search-box :deep(.van-field__control) {
  font-size: 13px;
}

.search-icon {
  color: #26A17B;
}

.content-area {
  padding: 10px;
}

.tab-content {
  min-height: calc(100vh - 150px);
}

.message-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.message-card {
  background: #fff;
  border-radius: 10px;
  padding: 15px;
}

.message-card.unread {
  border-left: 3px solid #26A17B;
}

.message-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 15px;
  font-weight: 600;
  color: #333;
  margin-bottom: 8px;
}

.unread-dot {
  width: 8px;
  height: 8px;
  background: #26A17B;
  border-radius: 50%;
}

.message-summary {
  font-size: 13px;
  color: #666;
  line-height: 1.5;
  margin-bottom: 10px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.message-content {
  font-size: 14px;
  color: #333;
  line-height: 1.6;
  margin-bottom: 10px;
}

.message-time {
  font-size: 12px;
  color: #999;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 100px 20px;
  color: #999;
}

.empty-icon {
  width: 150px;
  height: auto;
  margin-bottom: 20px;
}

.empty-state p {
  font-size: 14px;
  margin: 0;
}

.sub-tabs-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 15px;
}

.sub-tabs {
  display: flex;
  gap: 10px;
}

.reward-claim {
  display: flex;
  align-items: center;
  gap: 10px;
}

.pending-amount {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
}

.pending-amount .label {
  font-size: 12px;
  color: #999;
}

.pending-amount .amount {
  font-size: 16px;
  font-weight: 600;
  color: #ff9800;
}

.claim-btn {
  padding: 8px 15px;
  border-radius: 20px;
  font-size: 13px;
  border: none;
  background: #26A17B;
  color: #fff;
}

.claim-btn.disabled {
  background: #ccc;
  color: #999;
}

.sub-tab-item {
  padding: 8px 20px;
  border-radius: 20px;
  font-size: 13px;
  color: #666;
  background: #f5f5f5;
  border: 1px solid #e0e0e0;
}

.sub-tab-item.active {
  color: #26A17B;
  background: rgba(38, 161, 123, 0.1);
  border-color: #26A17B;
}

.feedback-form {
  background: #fff;
  border-radius: 10px;
  padding: 15px;
}

.form-item {
  margin-bottom: 20px;
}

.form-label {
  font-size: 14px;
  color: #333;
  margin-bottom: 10px;
}

.form-label .required {
  color: #ff4d4f;
  margin-left: 2px;
}

.form-label .tip {
  color: #999;
  font-size: 12px;
}

.form-select-wrap {
  position: relative;
}

.form-select {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 15px;
  background: #fff;
  border: 1px solid #26A17B;
  border-radius: 8px;
  font-size: 14px;
}

.form-select .placeholder {
  color: #999;
}

.form-select .van-icon {
  color: #999;
}

.feedback-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  margin-top: 4px;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.1);
  z-index: 100;
  max-height: 300px;
  overflow-y: auto;
}

.feedback-dropdown .dropdown-item {
  padding: 15px;
  font-size: 14px;
  color: #333;
  border-bottom: 1px solid #f5f5f5;
}

.feedback-dropdown .dropdown-item:last-child {
  border-bottom: none;
}

.feedback-dropdown .dropdown-item.active {
  color: #26A17B;
}

.form-textarea {
  position: relative;
  background: #f5f5f5;
  border-radius: 8px;
  padding: 12px 15px;
}

.form-textarea textarea {
  width: 100%;
  min-height: 150px;
  border: none;
  background: transparent;
  font-size: 14px;
  color: #333;
  resize: none;
  outline: none;
}

.form-textarea textarea::placeholder {
  color: #999;
}

.textarea-count {
  text-align: right;
  font-size: 12px;
  color: #999;
  margin-top: 5px;
}

.upload-area {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  margin-bottom: 10px;
}

.upload-box {
  width: 80px;
  height: 80px;
  border-radius: 8px;
  overflow: hidden;
  position: relative;
}

.upload-box.add {
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px dashed #ccc;
  background: #f5f5f5;
}

.upload-box.add .van-icon {
  font-size: 24px;
  color: #999;
}

.upload-box .preview-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.upload-box .remove-btn {
  position: absolute;
  top: 2px;
  right: 2px;
  width: 18px;
  height: 18px;
  background: rgba(0, 0, 0, 0.5);
  border-radius: 50%;
  color: #fff;
  font-size: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.upload-tip {
  font-size: 12px;
  color: #999;
  line-height: 1.5;
}

.reward-rules {
  margin-top: 20px;
  padding-top: 15px;
  border-top: 1px solid #f0f0f0;
}

.rules-title {
  font-size: 14px;
  font-weight: 600;
  color: #333;
  margin-bottom: 10px;
}

.rules-content {
  font-size: 13px;
  color: #666;
  line-height: 1.6;
}

.submit-area {
  margin-top: 30px;
  padding: 0 15px 20px;
}

.submit-btn {
  width: 100%;
  height: 50px;
  background: #26A17B;
  border: none;
  border-radius: 25px;
  color: #fff;
  font-size: 16px;
  font-weight: 600;
}

.my-feedback {
  padding-top: 10px;
}

.feedback-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.feedback-card {
  background: #fff;
  border-radius: 10px;
  padding: 15px;
}

.feedback-type {
  font-size: 14px;
  font-weight: 600;
  color: #333;
  margin-bottom: 8px;
}

.feedback-content {
  font-size: 13px;
  color: #666;
  line-height: 1.5;
  margin-bottom: 10px;
}

.feedback-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.feedback-time {
  font-size: 12px;
  color: #999;
}

.feedback-status {
  font-size: 12px;
  padding: 2px 8px;
  border-radius: 10px;
}

.feedback-status.pending {
  color: #ff9800;
  background: rgba(255, 152, 0, 0.1);
}

.feedback-status.processing {
  color: #2196f3;
  background: rgba(33, 150, 243, 0.1);
}

.feedback-status.resolved {
  color: #26A17B;
  background: rgba(38, 161, 123, 0.1);
}

.feedback-status.rejected {
  color: #999;
  background: #f5f5f5;
}

.message-detail-popup {
  height: 100%;
  display: flex;
  flex-direction: column;
}

.popup-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 15px 20px;
  border-bottom: 1px solid #eee;
}

.popup-title {
  font-size: 16px;
  font-weight: 600;
  color: #333;
}

.popup-header .van-icon {
  font-size: 20px;
  color: #999;
}

.popup-body {
  flex: 1;
  padding: 20px;
  overflow-y: auto;
}

.popup-body .detail-title {
  font-size: 18px;
  font-weight: 600;
  color: #333;
  margin: 0 0 15px 0;
  line-height: 1.4;
}

.popup-body .detail-meta {
  margin-bottom: 20px;
}

.popup-body .detail-time {
  font-size: 13px;
  color: #999;
}

.popup-body .detail-content {
  font-size: 14px;
  color: #333;
  line-height: 1.8;
}

.popup-body .detail-content :deep(img) {
  max-width: 100%;
  height: auto;
}
</style>
