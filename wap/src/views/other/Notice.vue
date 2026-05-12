<template>
  <div class="notice-pro">
    <div class="bg-glow"></div>
    <van-nav-bar
      fixed
      :border="false"
      class="glass-nav"
      z-index="100"
    >
      <template #left>
        <van-icon name="arrow-left" size="22" color="#fff" @click="router.back()" />
      </template>
      <template #title>
        <span class="nav-title">消息中心</span>
      </template>
      <template #right>
        <div class="filter-btn" @click="showFilter = true" v-if="mainTab === 'notice'">
          <van-icon name="filter-o" size="20" />
        </div>
      </template>
    </van-nav-bar>
    <div class="main-tabs-wrapper">
      <div class="main-tabs">
        <div 
          class="main-tab-item" 
          :class="{ active: mainTab === 'notice' }"
          @click="switchMainTab('notice')"
        >
          <span>公告</span>
          <span v-if="noticeUnreadCount > 0" class="main-tab-badge">{{ noticeUnreadCount > 99 ? '99+' : noticeUnreadCount }}</span>
        </div>
        <div 
          class="main-tab-item" 
          :class="{ active: mainTab === 'message' }"
          @click="switchMainTab('message')"
        >
          <span>站内信</span>
          <span v-if="messageUnreadCount > 0" class="main-tab-badge">{{ messageUnreadCount > 99 ? '99+' : messageUnreadCount }}</span>
        </div>
        <div class="main-tab-indicator" :style="{ transform: `translateX(${mainTab === 'notice' ? 0 : 100}%)` }"></div>
      </div>
    </div>
    <div class="sticky-header-group" v-if="mainTab === 'notice'">
      <div class="tabs-scroll-wrapper">
        <div class="tabs-scroll-content" ref="scrollContainer">
          <div 
            v-for="tab in tabs" 
            :key="tab.value"
            class="tab-capsule-item"
            :class="{ active: activeTab === tab.value }"
            @click="switchTab(tab.value)"
          >
            <van-icon :name="tab.icon" class="t-icon" />
            <span class="t-text">{{ tab.label }}</span>
            <span v-if="tab.value === 'unread' && unreadCount > 0" class="t-badge"></span>
            <div class="active-glow" v-if="activeTab === tab.value"></div>
          </div>
        </div>
        <div class="scroll-mask-right"></div>
      </div>
    </div>
    <div class="content-area" :class="{ 'no-sub-tabs': mainTab === 'message' }" v-show="mainTab === 'notice'">
      <van-pull-refresh v-model="refreshing" @refresh="onRefresh" success-text="刷新成功">
        <van-list
          v-model:loading="loading"
          :finished="finished"
          finished-text="没有更多了"
          @load="onLoad"
        >
          <template v-if="groupedNotices.length > 0">
            <div v-for="(group, gIndex) in groupedNotices" :key="gIndex" class="date-group">
              <div class="date-header">
                <span>{{ group.date }}</span>
              </div>
              <transition-group name="list-anim" tag="div">
                <div 
                  v-for="(item, index) in group.items" 
                  :key="item.id" 
                  class="notice-card"
                  :class="{ 'is-read': item.isRead }"
                  @click="openDetail(item)"
                  :style="{ '--delay': `${index * 0.05}s` }"
                >
                  <div class="card-glow-border"></div>
                  <div class="card-inner">
                    <div class="card-left">
                      <div class="icon-box" :class="item.type">
                        <van-icon :name="getTypeIcon(item.type)" />
                      </div>
                    </div>
                    <div class="card-body">
                      <div class="header-row">
                        <div class="title-wrap">
                          <span class="type-tag" :class="item.type">{{ getTypeName(item.type) }}</span>
                          <h3 class="title">{{ item.title }}</h3>
                        </div>
                        <div class="status-dot" v-if="!item.isRead"></div>
                      </div>
                      <p class="summary">{{ item.summary }}</p>
                      <div class="footer-row">
                        <span class="time">{{ formatTime(item.createdAt) }}</span>
                        <van-icon name="arrow" class="arrow" />
                      </div>
                    </div>
                  </div>
                </div>
              </transition-group>
            </div>
          </template>
          <van-empty 
            v-else-if="!loading" 
            image="search" 
            description="暂无公告" 
            class="custom-empty"
          />
        </van-list>
      </van-pull-refresh>
    </div>
    <div class="content-area no-sub-tabs" v-show="mainTab === 'message'">
      <van-pull-refresh v-model="msgRefreshing" @refresh="onMsgRefresh" success-text="刷新成功">
        <van-list
          v-model:loading="msgLoading"
          :finished="msgFinished"
          finished-text="没有更多了"
          @load="onMsgLoad"
        >
          <template v-if="groupedMessages.length > 0">
            <div v-for="(group, gIndex) in groupedMessages" :key="gIndex" class="date-group">
              <div class="date-header">
                <span>{{ group.date }}</span>
              </div>
              <transition-group name="list-anim" tag="div">
                <div 
                  v-for="(item, index) in group.items" 
                  :key="item.id" 
                  class="notice-card message-card"
                  :class="{ 'is-read': item.isRead }"
                  @click="openMsgDetail(item)"
                  :style="{ '--delay': `${index * 0.05}s` }"
                >
                  <div class="card-glow-border message"></div>
                  <div class="card-inner">
                    <div class="card-left">
                      <div class="icon-box message">
                        <van-icon name="envelop-o" />
                      </div>
                    </div>
                    <div class="card-body">
                      <div class="header-row">
                        <div class="title-wrap">
                          <span class="type-tag message">站内信</span>
                          <h3 class="title">{{ item.title }}</h3>
                        </div>
                        <div class="status-dot" v-if="!item.isRead"></div>
                      </div>
                      <p class="summary">{{ item.summary }}</p>
                      <div class="footer-row">
                        <span class="time">{{ formatTime(item.sentTime) }}</span>
                        <van-icon name="arrow" class="arrow" />
                      </div>
                    </div>
                  </div>
                </div>
              </transition-group>
            </div>
          </template>
          <van-empty 
            v-else-if="!msgLoading" 
            image="search" 
            description="暂无站内信" 
            class="custom-empty"
          />
        </van-list>
      </van-pull-refresh>
    </div>
    <van-popup
      v-model:show="showFilter"
      position="bottom"
      round
      class="filter-popup glass-panel"
    >
      <div class="popup-header">
        <h3>筛选公告</h3>
        <van-icon name="cross" @click="showFilter = false" />
      </div>
      <div class="filter-grid">
        <div 
          v-for="opt in filterOptions" 
          :key="opt.value"
          class="filter-item"
          :class="{ active: activeTab === opt.value }"
          @click="selectFilter(opt.value)"
        >
          <div class="icon-wrapper">
            <van-icon :name="opt.icon" />
          </div>
          <span>{{ opt.label }}</span>
        </div>
      </div>
    </van-popup>
    <van-popup
      v-model:show="showDetail"
      position="right"
      class="detail-popup"
      :style="{ width: '100%', height: '100%' }"
    >
      <div class="detail-container" v-if="currentNotice">
        <van-nav-bar
          fixed
          :border="false"
          class="glass-nav"
        >
          <template #left>
            <van-icon name="arrow-left" size="22" color="#fff" @click="showDetail = false" />
          </template>
          <template #title>
            <span class="nav-title">公告详情</span>
          </template>
        </van-nav-bar>
        <div class="detail-content">
          <div class="detail-header">
            <h1 class="big-title">{{ currentNotice.title }}</h1>
            <div class="meta-row">
              <span class="type-badge" :class="currentNotice.type">{{ getTypeName(currentNotice.type) }}</span>
              <span class="time">{{ formatFullTime(currentNotice.createdAt) }}</span>
            </div>
          </div>
          <div class="article-card">
            <div class="article-body" v-safe-html="currentNotice.content"></div>
          </div>
        </div>
        <div class="detail-footer glass-panel">
          <div class="nav-btns">
            <div class="nav-btn prev" :class="{ disabled: !hasPrev }" @click="goPrev">
              <van-icon name="arrow-left" /> 上一条
            </div>
            <div class="divider"></div>
            <div class="nav-btn next" :class="{ disabled: !hasNext }" @click="goNext">
              下一条 <van-icon name="arrow" />
            </div>
          </div>
        </div>
      </div>
    </van-popup>
    <van-popup
      v-model:show="showMsgDetail"
      position="right"
      class="detail-popup"
      :style="{ width: '100%', height: '100%' }"
    >
      <div class="detail-container" v-if="currentMessage">
        <van-nav-bar
          fixed
          :border="false"
          class="glass-nav"
        >
          <template #left>
            <van-icon name="arrow-left" size="22" color="#fff" @click="showMsgDetail = false" />
          </template>
          <template #title>
            <span class="nav-title">站内信详情</span>
          </template>
          <template #right>
            <div class="delete-btn" @click="deleteCurrentMessage">
              <van-icon name="delete-o" size="20" />
            </div>
          </template>
        </van-nav-bar>
        <div class="detail-content">
          <div class="detail-header">
            <h1 class="big-title">{{ currentMessage.title }}</h1>
            <div class="meta-row">
              <span class="type-badge message">站内信</span>
              <span class="sender" v-if="currentMessage.senderName">来自: {{ currentMessage.senderName }}</span>
              <span class="time">{{ formatFullTime(currentMessage.sentTime) }}</span>
            </div>
          </div>
          <div class="article-card">
            <div class="article-body" v-safe-html="currentMessage.content"></div>
          </div>
        </div>
      </div>
    </van-popup>
  </div>
</template>
<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { showToast, showLoadingToast, closeToast, showConfirmDialog } from 'vant';
import { noticeApi } from '@/api/notice';
import { messageApi } from '@/api/message';
import { sanitizeHtml } from '@/utils/sanitize';
const router = useRouter();
interface Notice {
  id: number;
  title: string;
  summary: string;
  content: string;
  type: 'system' | 'activity' | 'update';
  createdAt: number;
  isRead: boolean;
  prevId?: number;
  nextId?: number;
}
interface Message {
  id: number;
  title: string;
  summary: string;
  content: string;
  senderName: string;
  sentTime: number;
  isRead: boolean;
}
const mainTab = ref('notice');
const activeTab = ref('all');
const refreshing = ref(false);
const loading = ref(false);
const finished = ref(false);
const showFilter = ref(false);
const showDetail = ref(false);
const currentNotice = ref<Notice | null>(null);
const notices = ref<Notice[]>([]);
const currentPage = ref(0);
const pageSize = ref(20);
const totalCount = ref(0);
const unreadCountValue = ref(0);
const msgRefreshing = ref(false);
const msgLoading = ref(false);
const msgFinished = ref(false);
const showMsgDetail = ref(false);
const currentMessage = ref<Message | null>(null);
const messages = ref<Message[]>([]);
const msgCurrentPage = ref(0);
const msgTotalCount = ref(0);
const messageUnreadCount = ref(0);
const noticeUnreadCount = ref(0);

const tabs = [
  { label: '全部', value: 'all', icon: 'apps-o' },
  { label: '未读', value: 'unread', icon: 'bell' },
  { label: '活动', value: 'activity', icon: 'gift-o' },
  { label: '系统', value: 'system', icon: 'volume-o' },
  { label: '更新', value: 'update', icon: 'upgrade' },
];
const filterOptions = [
  { label: '全部公告', value: 'all', icon: 'apps-o' },
  { label: '未读消息', value: 'unread', icon: 'bell' },
  { label: '活动福利', value: 'activity', icon: 'gift-o' },
  { label: '系统通知', value: 'system', icon: 'volume-o' },
  { label: '版本更新', value: 'update', icon: 'upgrade' },
];
let isLoadingData = false;
let isMsgLoadingData = false;
const loadNoticeList = async () => {
  if (isLoadingData) {
    return;
  }
  try {
    isLoadingData = true;
    const params: any = {
      page: currentPage.value,
      pageSize: pageSize.value,
    };
    if (activeTab.value === 'unread') {
      params.unreadOnly = true;
    } else if (activeTab.value !== 'all') {
      params.type = activeTab.value;
    }
    if (currentPage.value === 1 && !refreshing.value) {
      showLoadingToast({ message: '加载中...', forbidClick: true, duration: 0 });
    }
    const res = await noticeApi.getNoticeList(params);
    closeToast();
    if (res && (res.code === 0 || res.code === 200)) {
      const newList = res.data?.list || [];
      if (refreshing.value) {
        notices.value = newList;
      } else {
        notices.value.push(...newList);
      }
      totalCount.value = res.data?.total || 0;
      unreadCountValue.value = res.data?.unreadCount || 0;
      if (notices.value.length >= totalCount.value || newList.length === 0) {
        finished.value = true;
      }
    } else {
      closeToast();
      showToast(res?.message || '加载失败');
      finished.value = true;
    }
  } catch (error) {
    closeToast();
    showToast('网络请求失败');
    finished.value = true;
  } finally {
    loading.value = false;
    refreshing.value = false;
    isLoadingData = false;
  }
};
const onLoad = () => {
  if (refreshing.value) {
    notices.value = [];
    currentPage.value = 0;
  }
  currentPage.value++;
  loadNoticeList();
};
const onRefresh = () => {
  finished.value = false;
  loading.value = true;
  currentPage.value = 0;
  refreshing.value = true;
  onLoad();
};
const loadUnreadCount = async () => {
  try {
    const res = await noticeApi.getUnreadCount();
    if (res && (res.code === 0 || res.code === 200)) {
      unreadCountValue.value = res.data?.unreadCount || 0;
      noticeUnreadCount.value = unreadCountValue.value;
    }
  } catch (error) {
  }
};
const loadMsgUnreadCount = async () => {
  try {
    const res = await messageApi.getUnreadCount();
    if (res && (res.code === 0 || res.code === 200)) {
      messageUnreadCount.value = res.data?.unreadCount || 0;
    }
  } catch (error) {
  }
};
const loadMessageList = async () => {
  if (isMsgLoadingData) {
    return;
  }
  try {
    isMsgLoadingData = true;
    const params = {
      page: msgCurrentPage.value,
      pageSize: pageSize.value,
    };
    if (msgCurrentPage.value === 1 && !msgRefreshing.value) {
      showLoadingToast({ message: '加载中...', forbidClick: true, duration: 0 });
    }
    const res = await messageApi.getMessageList(params);
    closeToast();
    if (res && (res.code === 0 || res.code === 200)) {
      const newList = res.data?.list || [];
      if (msgRefreshing.value) {
        messages.value = newList;
      } else {
        messages.value.push(...newList);
      }
      msgTotalCount.value = res.data?.total || 0;
      messageUnreadCount.value = res.data?.unreadCount || 0;
      if (messages.value.length >= msgTotalCount.value || newList.length === 0) {
        msgFinished.value = true;
      }
    } else {
      closeToast();
      showToast(res?.message || '加载失败');
      msgFinished.value = true;
    }
  } catch (error) {
    closeToast();
    showToast('网络请求失败');
    msgFinished.value = true;
  } finally {
    msgLoading.value = false;
    msgRefreshing.value = false;
    isMsgLoadingData = false;
  }
};
const onMsgLoad = () => {
  if (msgRefreshing.value) {
    messages.value = [];
    msgCurrentPage.value = 0;
  }
  msgCurrentPage.value++;
  loadMessageList();
};
const onMsgRefresh = () => {
  msgFinished.value = false;
  msgLoading.value = true;
  msgCurrentPage.value = 0;
  msgRefreshing.value = true;
  onMsgLoad();
};
const groupedMessages = computed(() => {
  const groups: { date: string; items: Message[] }[] = [];
  const today = new Date().setHours(0,0,0,0);
  const yesterday = today - 86400000;
  const sortedMessages = [...messages.value].sort((a, b) => b.sentTime - a.sentTime);
  sortedMessages.forEach(item => {
    const d = new Date(item.sentTime).setHours(0,0,0,0);
    let label = '';
    if (d === today) label = '今天';
    else if (d === yesterday) label = '昨天';
    else label = new Date(item.sentTime).toLocaleDateString();
    const lastGroup = groups[groups.length - 1];
    if (lastGroup && lastGroup.date === label) {
      lastGroup.items.push(item);
    } else {
      groups.push({ date: label, items: [item] });
    }
  });
  return groups;
});
const openMsgDetail = async (item: Message) => {
  showMsgDetail.value = true;
  await loadMsgDetailById(item.id);
};
const loadMsgDetailById = async (id: number) => {
  try {
    showLoadingToast({ message: '加载中...', forbidClick: true, duration: 0 });
    const res = await messageApi.getMessageDetail(id);
    if (res && (res.code === 0 || res.code === 200)) {
      currentMessage.value = res.data;
      const index = messages.value.findIndex(n => n.id === id);
      if (index !== -1) {
        messages.value[index].isRead = true;
      }
      loadMsgUnreadCount().catch(() => {});
      closeToast();
    } else {
      closeToast();
      showToast(res?.message || '加载失败');
    }
  } catch (error) {
    closeToast();
    showToast('网络请求失败');
  }
};
const deleteCurrentMessage = async () => {
  if (!currentMessage.value) return;
  try {
    await showConfirmDialog({
      title: '删除确认',
      message: '确定要删除这条站内信吗？',
    });
    showLoadingToast({ message: '删除中...', forbidClick: true, duration: 0 });
    const res = await messageApi.deleteMessage([currentMessage.value.id]);
    closeToast();
    if (res && (res.code === 0 || res.code === 200)) {
      showToast('删除成功');
      showMsgDetail.value = false;
      const index = messages.value.findIndex(n => n.id === currentMessage.value!.id);
      if (index !== -1) {
        messages.value.splice(index, 1);
      }
      currentMessage.value = null;
      loadMsgUnreadCount().catch(() => {});
    } else {
      showToast(res?.message || '删除失败');
    }
  } catch (error) {
    if (error !== 'cancel') {
      closeToast();
      showToast('删除失败');
    }
  }
};
const switchMainTab = (tab: string) => {
  if (mainTab.value === tab) return;
  mainTab.value = tab;
  if (tab === 'message' && messages.value.length === 0 && !msgLoading.value) {
    msgCurrentPage.value = 0;
    msgFinished.value = false;
    msgLoading.value = true;
    onMsgLoad();
  }
};
const unreadCount = computed(() => unreadCountValue.value);
const filteredNotices = computed(() => {
  return notices.value.sort((a, b) => b.createdAt - a.createdAt);
});
const groupedNotices = computed(() => {
  const groups: { date: string; items: Notice[] }[] = [];
  const today = new Date().setHours(0,0,0,0);
  const yesterday = today - 86400000;
  filteredNotices.value.forEach(item => {
    const d = new Date(item.createdAt).setHours(0,0,0,0);
    let label = '';
    if (d === today) label = '今天';
    else if (d === yesterday) label = '昨天';
    else label = new Date(item.createdAt).toLocaleDateString();
    const lastGroup = groups[groups.length - 1];
    if (lastGroup && lastGroup.date === label) {
      lastGroup.items.push(item);
    } else {
      groups.push({ date: label, items: [item] });
    }
  });
  return groups;
});
const hasPrev = computed(() => {
  return currentNotice.value?.prevId ? true : false;
});
const hasNext = computed(() => {
  return currentNotice.value?.nextId ? true : false;
});
const goPrev = async () => {
  if (!currentNotice.value?.prevId) return;
  await loadDetailById(currentNotice.value.prevId);
};
const goNext = async () => {
  if (!currentNotice.value?.nextId) return;
  await loadDetailById(currentNotice.value.nextId);
};
const loadDetailById = async (id: number) => {
  try {
    showLoadingToast({ message: '加载中...', forbidClick: true, duration: 0 });
    const res = await noticeApi.getNoticeDetail(id);
    if (res && (res.code === 0 || res.code === 200)) {
      currentNotice.value = res.data;
      const index = notices.value.findIndex(n => n.id === id);
      if (index !== -1) {
        notices.value[index].isRead = true;
      }
      loadUnreadCount().catch(() => {});
      closeToast();
    } else {
      closeToast();
      showToast(res?.message || '加载失败');
    }
  } catch (error) {
    closeToast();
    showToast('网络请求失败');
  }
};
const openDetail = async (item: Notice) => {
  showDetail.value = true;
  await loadDetailById(item.id);
};
const selectFilter = (val: string) => {
  if (activeTab.value === val) return;
  activeTab.value = val;
  showFilter.value = false;
  notices.value = [];
  currentPage.value = 0;
  finished.value = false;
  loading.value = true;
  isLoadingData = false;
  onLoad();
};
const switchTab = (val: string) => {
  if (activeTab.value === val) return;
  activeTab.value = val;
  notices.value = [];
  currentPage.value = 0;
  finished.value = false;
  loading.value = true;
  isLoadingData = false;
  onLoad();
};
const getTypeIcon = (type: string) => {
  const map: Record<string, string> = { system: 'volume-o', activity: 'gift-o', update: 'upgrade' };
  return map[type] || 'bell';
};
const getTypeName = (type: string) => {
  const map: Record<string, string> = { system: '系统公告', activity: '活动福利', update: '版本更新' };
  return map[type] || '公告';
};
const formatTime = (ts: number) => {
  const d = new Date(ts);
  return `${d.getHours().toString().padStart(2, '0')}:${d.getMinutes().toString().padStart(2, '0')}`;
};
const formatFullTime = (ts: number) => new Date(ts).toLocaleString();
onMounted(() => {
  loadUnreadCount().catch(() => {});
  loadMsgUnreadCount().catch(() => {});
});
</script>
<style scoped lang="scss">
$bg-dark: #050814;
$card-bg: #151925;
$card-bg-active: #1A1E2C;
$text-primary: #FFFFFF;
$text-secondary: #8890A4;
$gold: #F0C930;
$gold-glow: rgba(240, 201, 48, 0.4);
$neon-blue: #00F0FF;
$neon-pink: #FF0055;
$border-dark: rgba(255, 255, 255, 0.08);
.notice-pro {
  min-height: 100vh;
  background-color: $bg-dark;
  color: $text-primary;
  padding-top: calc(46px + env(safe-area-inset-top) + 58px);
  position: relative;
}
.bg-glow {
  position: fixed;
  top: -20%;
  left: -20%;
  width: 80%;
  height: 60%;
  background: radial-gradient(circle, rgba(24, 32, 50, 0.8) 0%, transparent 70%);
  z-index: 0;
  pointer-events: none;
}
.glass-nav {
  background: rgba(5, 8, 20, 0.7) !important;
  backdrop-filter: blur(12px);
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  :deep(.van-nav-bar__title) {
    color: #fff;
    font-weight: 600;
    letter-spacing: 1px;
  }
}
.filter-btn {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  color: $gold;
  &:active { opacity: 0.7; }
}
.delete-btn {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: $neon-pink;
  &:active { opacity: 0.7; }
}
.main-tabs-wrapper {
  position: fixed;
  top: calc(46px + env(safe-area-inset-top));
  left: 0;
  width: 100%;
  z-index: 11;
  background: rgba(5, 8, 20, 0.95);
  backdrop-filter: blur(12px);
  padding: 12px 16px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}
.main-tabs {
  position: relative;
  display: flex;
  background: rgba(255, 255, 255, 0.06);
  border-radius: 24px;
  padding: 4px;
  max-width: 220px;
  margin: 0 auto;
}
.main-tab-item {
  flex: 1;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 10px 0;
  font-size: 14px;
  font-weight: 500;
  color: $text-secondary;
  transition: all 0.3s;
  z-index: 2;
  cursor: pointer;
  &.active {
    color: $bg-dark;
    font-weight: 600;
  }
  &:active {
    opacity: 0.7;
  }
}
.main-tab-badge {
  font-size: 10px;
  padding: 2px 6px;
  background: $neon-pink;
  color: #fff;
  border-radius: 10px;
  min-width: 18px;
  text-align: center;
}
.main-tab-indicator {
  position: absolute;
  top: 4px;
  left: 4px;
  width: calc(50% - 4px);
  height: calc(100% - 8px);
  background: $gold;
  border-radius: 20px;
  transition: transform 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
  z-index: 1;
}
.sticky-header-group {
  position: fixed;
  top: calc(46px + env(safe-area-inset-top) + 58px);
  left: 0;
  width: 100%;
  z-index: 10;
  background: rgba(5, 8, 20, 0.95); 
  backdrop-filter: blur(12px);
  padding: 10px 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.03);
  touch-action: pan-y;
}
.tabs-scroll-wrapper {
  position: relative;
  width: 100%;
  overflow: hidden;
}
.tabs-scroll-content {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 0 16px;
  overflow-x: auto;
  scroll-behavior: smooth;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
  &::-webkit-scrollbar { display: none; }
  &::after {
    content: '';
    display: block;
    width: 20px;
    flex-shrink: 0;
  }
}
.tab-capsule-item {
  position: relative;
  flex: 0 0 auto;
  height: 34px;
  padding: 0 16px;
  border-radius: 20px;
  display: flex;
  align-items: center;
  gap: 6px;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.05);
  color: $text-secondary;
  transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
  cursor: pointer;
  overflow: hidden;
  .t-icon { font-size: 15px; transition: color 0.3s; }
  .t-text { font-size: 13px; font-weight: 500; white-space: nowrap; transition: color 0.3s; }
  .t-badge {
    position: absolute;
    top: 6px;
    right: 6px;
    width: 6px;
    height: 6px;
    background: $neon-pink;
    border-radius: 50%;
    box-shadow: 0 0 5px $neon-pink;
    z-index: 2;
  }
  &.active {
    background: transparent;
    border-color: rgba($gold, 0.3);
    color: $gold;
    transform: scale(1.02);
    .t-text { font-weight: bold; text-shadow: 0 0 8px rgba($gold, 0.3); }
  }
  &:active { transform: scale(0.96); }
  .active-glow {
    position: absolute;
    top: 0; left: 0; width: 100%; height: 100%;
    background: linear-gradient(135deg, rgba($gold, 0.2), rgba($gold, 0.05));
    z-index: -1;
  }
}
.scroll-mask-right {
  position: absolute;
  top: 0;
  right: 0;
  width: 30px;
  height: 100%;
  background: linear-gradient(to left, $bg-dark, transparent);
  pointer-events: none;
  z-index: 2;
}
.content-area {
  position: relative;
  z-index: 1;
  padding: 10px 16px;
  padding-top: 64px;
  padding-bottom: 40px;
  &.no-sub-tabs {
    padding-top: 10px;
  }
}
.date-group {
  margin-bottom: 24px;
}
.date-header {
  position: relative;
  padding: 16px 4px 12px;
  font-size: 18px;
  font-weight: 800;
  color: $text-primary;
  text-shadow: 0 2px 4px rgba(0,0,0,0.3);
  span {
    display: inline-block;
    position: relative;
    z-index: 1;
    &::after {
      content: '';
      position: absolute;
      bottom: 2px;
      left: 0;
      width: 100%;
      height: 6px;
      background: rgba($gold, 0.15);
      border-radius: 2px;
      z-index: -1;
    }
  }
}
.notice-card {
  position: relative;
  background: $card-bg;
  border-radius: 18px;
  margin-bottom: 16px;
  overflow: hidden;
  transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
  border: 1px solid $border-dark;
  &:active {
    transform: scale(0.98);
    background: $card-bg-active;
    border-color: rgba($gold, 0.2);
  }
  &.is-read {
    opacity: 0.7;
    .title { color: $text-secondary; }
    .icon-box { filter: grayscale(0.8); opacity: 0.5; }
  }
  .card-glow-border {
    position: absolute;
    top: 0; left: 0; width: 4px; height: 100%;
    background: linear-gradient(to bottom, $gold, transparent);
    opacity: 0.5;
    &.message {
      background: linear-gradient(to bottom, $neon-blue, transparent);
    }
  }
  .card-inner {
    padding: 16px;
    display: flex;
    align-items: flex-start;
  }
  .card-left {
    margin-right: 14px;
    .icon-box {
      width: 44px;
      height: 44px;
      border-radius: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 22px;
      &.system { background: rgba($gold, 0.1); color: $gold; box-shadow: 0 0 10px rgba($gold, 0.1); }
      &.activity { background: rgba($neon-pink, 0.1); color: $neon-pink; box-shadow: 0 0 10px rgba($neon-pink, 0.1); }
      &.update { background: rgba($neon-blue, 0.1); color: $neon-blue; box-shadow: 0 0 10px rgba($neon-blue, 0.1); }
      &.message { background: rgba($neon-blue, 0.1); color: $neon-blue; box-shadow: 0 0 10px rgba($neon-blue, 0.1); }
    }
  }
  .card-body {
    flex: 1;
    min-width: 0;
    .header-row {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 6px;
      .title-wrap {
        display: flex;
        align-items: center;
        gap: 8px;
        .type-tag {
          font-size: 10px;
          padding: 2px 6px;
          border-radius: 4px;
          border: 1px solid rgba(255,255,255,0.1);
          white-space: nowrap;
          &.system { color: $gold; border-color: rgba($gold, 0.3); }
          &.activity { color: $neon-pink; border-color: rgba($neon-pink, 0.3); }
          &.update { color: $neon-blue; border-color: rgba($neon-blue, 0.3); }
          &.message { color: $neon-blue; border-color: rgba($neon-blue, 0.3); }
        }
        .title {
          font-size: 16px;
          font-weight: bold;
          color: #fff;
          line-height: 1.3;
          margin: 0;
          overflow: hidden;
          text-overflow: ellipsis;
          white-space: nowrap;
          max-width: 160px;
        }
      }
      .status-dot {
        width: 8px;
        height: 8px;
        background: $neon-pink;
        border-radius: 50%;
        box-shadow: 0 0 6px $neon-pink;
      }
    }
    .summary {
      font-size: 13px;
      color: $text-secondary;
      line-height: 1.5;
      margin-bottom: 12px;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    .footer-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 12px;
      .time { color: rgba(255,255,255,0.3); font-family: monospace; }
      .arrow { color: rgba(255,255,255,0.2); }
    }
  }
}
.glass-panel {
  background: rgba(21, 25, 37, 0.95) !important;
  backdrop-filter: blur(20px);
}
.popup-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  h3 { margin: 0; color: #fff; font-size: 18px; }
  .van-icon { color: $text-secondary; font-size: 20px; padding: 4px; }
}
.filter-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
  padding: 0 20px 40px;
  .filter-item {
    background: rgba(255,255,255,0.03);
    border-radius: 12px;
    padding: 16px 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    border: 1px solid transparent;
    transition: all 0.2s;
    .icon-wrapper {
      font-size: 24px;
      margin-bottom: 8px;
      color: $text-secondary;
    }
    span { font-size: 12px; color: $text-secondary; }
    &.active {
      background: rgba($gold, 0.1);
      border-color: rgba($gold, 0.3);
      .icon-wrapper { color: $gold; text-shadow: 0 0 10px $gold; }
      span { color: $gold; font-weight: bold; }
    }
  }
}
.detail-popup {
  background: $bg-dark;
}
.detail-container {
  height: 100%;
  display: flex;
  flex-direction: column;
}
.detail-content {
  flex: 1;
  overflow-y: auto;
  padding: 20px;
  padding-top: 60px;
}
.detail-header {
  margin-bottom: 24px;
  .big-title {
    font-size: 24px;
    line-height: 1.4;
    color: #fff;
    margin-bottom: 16px;
  }
  .meta-row {
    display: flex;
    align-items: center;
    .type-badge {
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 12px;
      margin-right: 12px;
      &.system { background: rgba($gold, 0.1); color: $gold; }
      &.activity { background: rgba($neon-pink, 0.1); color: $neon-pink; }
      &.update { background: rgba($neon-blue, 0.1); color: $neon-blue; }
      &.message { background: rgba($neon-blue, 0.1); color: $neon-blue; }
    }
    .sender {
      color: $text-secondary;
      font-size: 13px;
      margin-right: 12px;
    }
    .time { color: $text-secondary; font-size: 13px; font-family: monospace; }
  }
}
.article-card {
  background: $card-bg;
  border-radius: 16px;
  padding: 20px;
  font-size: 16px;
  line-height: 1.8;
  color: #C4C9D4;
  border: 1px solid $border-dark;
  :deep(p) { margin-bottom: 16px; }
  :deep(ul) { list-style: disc; padding-left: 20px; margin-bottom: 16px; }
  :deep(li) { margin-bottom: 8px; }
  :deep(.highlight) { color: $gold; font-weight: bold; }
}
.detail-footer {
  padding: 16px 20px;
  padding-bottom: calc(16px + env(safe-area-inset-bottom));
  .nav-btns {
    display: flex;
    align-items: center;
    background: rgba(255,255,255,0.05);
    border-radius: 24px;
    padding: 4px;
    .nav-btn {
      flex: 1;
      text-align: center;
      padding: 12px;
      font-size: 14px;
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      &:active { opacity: 0.7; }
      &.disabled { opacity: 0.3; pointer-events: none; }
    }
    .divider {
      width: 1px;
      height: 20px;
      background: rgba(255,255,255,0.1);
    }
  }
}
.list-anim-enter-active {
  transition: all 0.5s ease;
  transition-delay: var(--delay);
}
.list-anim-enter-from {
  opacity: 0;
  transform: translateY(30px);
}
.custom-empty {
  :deep(.van-empty__description) { color: $text-secondary; }
}
</style>
