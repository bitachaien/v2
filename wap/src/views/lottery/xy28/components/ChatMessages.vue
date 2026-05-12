<template>
  <div class="chat-messages-wrapper">
    <div class="chat-messages" ref="containerRef" @scroll="onScroll">
      <div class="message-item" v-for="msg in messages" :key="msg.id" :class="{ 'is-system': msg.isSystem }">
        
        <template v-if="msg.isSystem">
          <div class="system-text-msg">
            {{ msg.content }}
          </div>
        </template>
        
        
        <template v-else>
          <div class="user-avatar-col">
            <img :src="getAvatarUrl(msg.avatar)" class="avatar-img" />
            <div class="honor-badge" v-if="msg.honorLevel">荣耀{{ msg.honorLevel }}</div>
          </div>
          <div class="user-content-col">
            <div class="user-info">
              <span class="user-name">{{ msg.userName }}</span>
              <span class="msg-time">{{ msg.time }}</span>
            </div>
            
            
            <div class="system-card" v-if="msg.messageType === 'result'">
              <div class="card-header">第{{ msg.issue }}期开奖结果</div>
              <div class="card-body">
                <div class="balls">
                  <span class="ball blue">{{ msg.code && msg.code[0] }}</span>
                  <span class="symbol">+</span>
                  <span class="ball blue">{{ msg.code && msg.code[1] }}</span>
                  <span class="symbol">+</span>
                  <span class="ball blue">{{ msg.code && msg.code[2] }}</span>
                  <span class="symbol">=</span>
                  <span class="ball red">{{ msg.sum }}</span>
                </div>
                <div class="result-type">{{ msg.type }}</div>
              </div>
            </div>
            
            
            <div class="bill-list-card" v-else-if="msg.messageType === 'bill'">
              <div class="bill-header">第 {{ msg.issue }} 期玩家投注账单</div>
              <div class="bill-items">
                <div class="bill-item" v-for="(item, idx) in msg.details" :key="idx" @click="$emit('detail', item)">
                  <span class="name">{{ item.name }}</span>
                  <van-icon name="arrow" color="#999" size="14" />
                </div>
              </div>
            </div>

            
            <div class="msg-bubble" v-else>
              {{ msg.content }}
            </div>
          </div>
        </template>
      </div>
    </div>
    
    
    <div class="go-bottom-btn" @click="goToBottom">
      <van-icon name="arrow-down" />
      <span class="new-count" v-if="newMsgCount > 0">{{ newMsgCount }}</span>
    </div>
  </div>
</template>

<script setup>
import { ref, nextTick, computed, watch } from 'vue'

const props = defineProps({
  messages: {
    type: Array,
    default: () => []
  },
  hasMore: Boolean,
  loadingMore: Boolean,
  defaultAvatar: {
    type: String,
    default: '/assets/images/user/avatars/default.png'
  }
})

const apiBaseUrl = import.meta.env.VITE_PROXY_TARGET || ''

function getAvatarUrl(avatar) {
  if (!avatar) return props.defaultAvatar

  if (avatar.startsWith('http://') || avatar.startsWith('https://')) {
    return avatar
  }

  return apiBaseUrl + avatar
}

const emit = defineEmits(['loadMore', 'detail', 'scroll'])

const containerRef = ref(null)
const isAtBottom = ref(true) // 是否在底部
const newMsgCount = ref(0)   // 新消息数量

function checkIsAtBottom() {
  if (!containerRef.value) return true
  const { scrollTop, scrollHeight, clientHeight } = containerRef.value
  return scrollHeight - scrollTop - clientHeight < 50
}

function onScroll(e) {
  isAtBottom.value = checkIsAtBottom()
  

  if (isAtBottom.value) {
    newMsgCount.value = 0
  }
  
  emit('scroll', e)
}

watch(() => props.messages.length, (newLen, oldLen) => {
  if (newLen > oldLen) {

    if (isAtBottom.value) {

      scrollToBottom()
    } else {

      newMsgCount.value += (newLen - oldLen)
    }
  }
})

function scrollToBottom() {
  nextTick(() => {
    if (containerRef.value) {
      containerRef.value.scrollTop = containerRef.value.scrollHeight
    }
    newMsgCount.value = 0
    isAtBottom.value = true
  })
}

function goToBottom() {
  scrollToBottom()
}

defineExpose({
  scrollToBottom,
  containerRef
})
</script>

<style lang="less" scoped>
.chat-messages-wrapper {
  flex: 1;
  position: relative;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.chat-messages {
  flex: 1;
  background: #ecedf1;
  overflow-y: auto;
  padding: 10px;
  -webkit-overflow-scrolling: touch;
  
  @keyframes msgSlideIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
  }

  .message-item {
    margin-bottom: 15px;
    display: flex;
    animation: msgSlideIn 0.3s ease-out forwards;
    
    &.is-system {
      justify-content: center;
      margin: 20px 0;
    }
    
    .system-card {
      background: #fff;
      border-radius: 8px;
      overflow: hidden;
      width: 80%;
      max-width: 300px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
      
      .card-header {
        background: #5eb3fd;
        color: #fff;
        text-align: center;
        padding: 8px;
        font-size: 14px;
      }
      
      .card-body {
        padding: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        
        .balls {
          display: flex;
          align-items: center;
          gap: 4px;
          
          .ball {
            width: 24px;
            height: 24px;
            background: #5691fe;
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            
            &.red { background: #f5222d; }
            &.blue { background: #5691fe; }
          }
          
          .symbol { color: #333; font-weight: bold; }
        }
        
        .result-type {
          font-size: 16px;
          color: #333;
          font-weight: bold;
        }
      }
    }
    
    .system-text-msg {
      background: rgba(0,0,0,0.06);
      color: #888;
      padding: 5px 10px;
      border-radius: 4px;
      font-size: 12px;
    }
    
    .user-avatar-col {
      display: flex;
      flex-direction: column;
      align-items: center;
      margin-right: 10px;
      position: relative;
      flex-shrink: 0;
      
      .avatar-img {
        width: 44px;
        height: 44px;
        border-radius: 6px;
      }
      
      .honor-badge {
        margin-top: -8px;
        background: linear-gradient(90deg, #9c27b0, #673ab7);
        color: #fff;
        font-size: 10px;
        padding: 1px 6px;
        border-radius: 8px;
        z-index: 1;
        white-space: nowrap;
        border: 1px solid #fff;
      }
    }
    
    .user-content-col {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      min-width: 0;
      
      .user-info {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 6px;
        
        .user-name {
          font-size: 13px;
          color: #576b95;
          font-weight: 500;
        }
        
        .msg-time {
          font-size: 12px;
          color: #b2b2b2;
        }
      }
      
      .msg-bubble {
        background: #fff;
        padding: 10px 14px;
        border-radius: 4px;
        font-size: 16px;
        line-height: 1.5;
        color: #333;
        box-shadow: 0 1px 1px rgba(0,0,0,0.04);
        word-break: break-all;
        position: relative;
        max-width: 85%;
        

        &::before {
          content: '';
          position: absolute;
          left: -8px;
          top: 12px;
          width: 0;
          height: 0;
          border-top: 6px solid transparent;
          border-bottom: 6px solid transparent;
          border-right: 8px solid #fff;
        }
      }
    }

    .bill-list-card {
      background: #fff;
      border-radius: 8px;
      padding: 10px;
      width: 85%;
      max-width: 320px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.05);
      
      .bill-header {
        font-size: 14px;
        color: #333;
        margin-bottom: 10px;
        font-weight: bold;
      }
      
      .bill-items {
        display: flex;
        flex-direction: column;
        gap: 8px;
        
        .bill-item {
          background: #f5f6fa;
          padding: 10px 15px;
          border-radius: 6px;
          display: flex;
          justify-content: space-between;
          align-items: center;
          cursor: pointer;
          
          .name {
            font-size: 14px;
            color: #333;
          }
        }
      }
    }
  }
  
}

.go-bottom-btn {
  position: absolute;
  right: 12px;
  bottom: 12px;
  width: 40px;
  height: 40px;
  background: linear-gradient(135deg, #1e88e5, #1565c0);
  color: #fff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 3px 10px rgba(30, 136, 229, 0.4);
  cursor: pointer;
  z-index: 10;
  
  &:active {
    transform: scale(0.9);
    opacity: 0.9;
  }
  
  .van-icon {
    font-size: 18px;
  }
  

  .new-count {
    position: absolute;
    top: -5px;
    right: -5px;
    min-width: 18px;
    height: 18px;
    background: #f5222d;
    color: #fff;
    border-radius: 9px;
    font-size: 11px;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 4px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
  }
}
</style>
