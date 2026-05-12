<template>
  <div class="chat-container">
    
    <van-nav-bar fixed placeholder :border="false" class="chat-navbar">
      <template #left>
        <van-icon name="arrow-left" @click="$router.back()" class="nav-icon" />
      </template>
      <template #title>
        <div class="nav-title">
          <span class="service-name">在线客服</span>
          <div class="service-status">
            <span class="status-dot"></span>
            <span class="status-text">在线</span>
          </div>
        </div>
      </template>
      <template #right>
        <van-popover v-model:show="showMorePopover" placement="bottom-end" theme="dark" :actions="moreActions" @select="onSelectMore">
          <template #reference>
            <van-icon name="ellipsis" class="nav-icon" />
          </template>
        </van-popover>
      </template>
    </van-nav-bar>

    
    <div class="quick-action-bar" v-if="!showMorePopover">
      <div class="action-item" v-for="(item, index) in quickActions" :key="index" @click="handleQuickAction(item)">
        {{ item.text }}
      </div>
    </div>

    
    <div class="chat-content" ref="chatContentRef">
      <div class="messages-container">
        <div v-for="(group, gIndex) in messageGroups" :key="gIndex">
          <div class="time-divider">
            <span>{{ group.time }}</span>
          </div>
          
          <div v-for="msg in group.messages" :key="msg.id" class="message-item" :class="msg.type">
            
            <div v-if="msg.type === 'system'" class="system-msg">
              <span>{{ msg.content }}</span>
            </div>

            
            <div v-else class="bubble-wrapper" :class="{ 'is-me': msg.type === 'user' }">
              <div class="avatar-container service-avatar" v-if="msg.type === 'service'">
                <van-icon name="service" />
              </div>
              
              <div class="bubble-content">
                
                <div v-if="msg.contentType === 'text'" class="text-msg">{{ msg.content }}</div>
                
                
                <div v-if="msg.contentType === 'image'" class="image-msg">
                  <van-image :src="msg.content" fit="cover" radius="8" @click="previewImage(msg.content)" />
                </div>

                
                <div v-if="msg.contentType === 'card'" class="card-msg">
                  <div class="card-title">{{ msg.title }}</div>
                  <div class="card-body" v-safe-html="msg.content"></div>
                  <div class="card-actions" v-if="msg.actions">
                    <div v-for="(action, aIndex) in msg.actions" :key="aIndex" class="card-btn" @click="handleCardAction(action)">
                      {{ action.text }}
                    </div>
                  </div>
                </div>

                 
                 <div v-if="msg.embedded" class="embedded-module">
                    <div class="risk-warning" v-if="msg.embedded.type === 'risk'">
                      <van-icon name="warning-o" /> {{ msg.embedded.text }}
                    </div>
                    <div class="quick-link" v-if="msg.embedded.type === 'link'" @click="handleQuickAction({action: msg.embedded.action})">
                      {{ msg.embedded.text }} <van-icon name="arrow" />
                    </div>
                 </div>
              </div>

              <div class="avatar-container user-avatar" v-if="msg.type === 'user'">
                 <van-icon name="manager" />
              </div>
            </div>
          </div>
        </div>

        
        <div v-if="isTyping" class="message-item service">
           <div class="bubble-wrapper">
              <div class="avatar-container service-avatar">
                <van-icon name="service" />
              </div>
              <div class="bubble-content glass-bubble">
                 <div class="typing-indicator">
                   <span></span><span></span><span></span>
                 </div>
              </div>
           </div>
        </div>

        
        <div v-if="sessionEnded" class="session-ended">
          <div class="ended-notice">客服已下线，本次会话结束</div>
          <div class="ended-actions">
            <van-button round size="small" class="ended-btn" @click="restartChat">重新发起</van-button>
            <van-button round size="small" class="ended-btn" @click="$router.push('/userCenter/help')">帮助中心</van-button>
          </div>
        </div>
      </div>
    </div>

    
    <div class="chat-footer">
      <div class="toolbar">
        <div class="tool-btn" @click="togglePanel">
          <van-icon name="plus" />
        </div>
        <div class="input-box">
          <input type="text" v-model="inputText" placeholder="请输入您的问题..." @keyup.enter="sendMessage" />
        </div>
        <div class="send-btn" @click="sendMessage" :class="{ 'active': inputText.length > 0 }">
          <van-icon name="guide-o" />
        </div>
      </div>

      
      <transition name="van-slide-up">
        <div v-show="showPanel" class="function-panel">
          <div class="panel-grid">
            <div class="panel-item" v-for="(tool, index) in panelTools" :key="index" @click="handleTool(tool)">
              <div class="panel-icon">
                <van-icon :name="tool.icon" />
              </div>
              <span class="panel-text">{{ tool.text }}</span>
            </div>
          </div>
        </div>
      </transition>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, nextTick, computed } from 'vue';
import { useRouter } from 'vue-router';
import { showToast, showImagePreview } from 'vant';

const router = useRouter();
const chatContentRef = ref(null);
const inputText = ref('');
const showPanel = ref(false);
const showMorePopover = ref(false);
const isTyping = ref(false);
const sessionEnded = ref(false);

const moreActions = [
  { text: '清空记录', icon: 'delete-o', action: 'clear' },
  { text: '复制会话ID', icon: 'records', action: 'copyId' },
  { text: '投诉客服', icon: 'warn-o', action: 'complain' },
];

const quickActions = [
  { text: '我的订单', action: 'order' },
  { text: '提款帮助', action: 'withdraw_help' },
  { text: '存款帮助', action: 'deposit_help' },
  { text: '转人工', action: 'human' },
  { text: '常见问题', action: 'faq' },
];

const panelTools = [
  { text: '相册', icon: 'photo-o', action: 'upload_img' },
  { text: '截图', icon: 'photograph', action: 'screenshot' },
  { text: '订单号', icon: 'bill-o', action: 'send_order' },
  { text: '常见问题', icon: 'question-o', action: 'faq_list' },
];

const messageGroups = reactive([
  {
    time: '昨天',
    messages: [
      { id: 1, type: 'system', content: '系统提示：您已进入人工客服队列，请稍候...' },
      { id: 2, type: 'service', contentType: 'text', content: '您好，请问有什么可以帮您？' }
    ]
  },
  {
    time: '今天',
    messages: [
      { id: 3, type: 'user', contentType: 'text', content: '我刚才提现了一笔款项，还没有到账。' },
      { 
        id: 4, 
        type: 'service', 
        contentType: 'card', 
        title: '提现状态查询', 
        content: '请提供您的订单号，或者点击下方按钮直接查询最近一笔提现记录。',
        actions: [{ text: '查询最近提现', action: 'check_last_withdraw' }]
      },
      {
         id: 5,
         type: 'service',
         contentType: 'text',
         content: '通常提现会在 5-10 分钟内处理完毕，请耐心等待。',
         embedded: { type: 'risk', text: '请勿向陌生人透露您的支付密码' }
      }
    ]
  }
]);

const scrollToBottom = () => {
  nextTick(() => {
    if (chatContentRef.value) {
      chatContentRef.value.scrollTop = chatContentRef.value.scrollHeight;
    }
  });
};

const sendMessage = () => {
  if (!inputText.value.trim()) return;
  

  const lastGroup = messageGroups[messageGroups.length - 1];
  lastGroup.messages.push({
    id: Date.now(),
    type: 'user',
    contentType: 'text',
    content: inputText.value
  });
  
  inputText.value = '';
  showPanel.value = false;
  scrollToBottom();

  setTimeout(() => { isTyping.value = true; scrollToBottom(); }, 500);
  setTimeout(() => {
    isTyping.value = false;
    lastGroup.messages.push({
      id: Date.now() + 1,
      type: 'service',
      contentType: 'text',
      content: '收到，我正在为您查询，请稍候。'
    });
    scrollToBottom();
  }, 2000);
};

const togglePanel = () => {
  showPanel.value = !showPanel.value;
  if (showPanel.value) scrollToBottom();
};

const onSelectMore = (action) => {
  if (action.action === 'clear') {
    messageGroups.length = 0; 
    showToast('记录已清空');
  } else if (action.action === 'copyId') {
    showToast('会话ID已复制');
  } else if (action.action === 'complain') {
    showToast('投诉页面开发中');
  }
};

const handleQuickAction = (item) => {
  inputText.value = item.text; 
  sendMessage();
};

const handleTool = (tool) => {
  showToast(`点击了${tool.text}`);
};

const handleCardAction = (action) => {
    showToast(`执行操作: ${action.text}`);
};

const previewImage = (url) => {
  showImagePreview([url]);
};

const restartChat = () => {
  sessionEnded.value = false;
  messageGroups.push({
    time: '刚刚',
    messages: [{ id: Date.now(), type: 'service', contentType: 'text', content: '欢迎回来，请问有什么可以帮您？' }]
  });
  scrollToBottom();
};

onMounted(() => {
  scrollToBottom();
});

</script>

<style lang="scss" scoped>

$bg-color: #0C0F1A;
$card-bg: #1A1F2B;
$gold-start: #FFD970;
$gold-end: #F5B544;
$text-color: #ffffff;
$text-secondary: #86909c;
$bubble-radius: 18px;

.chat-container {
  display: flex;
  flex-direction: column;
  height: 100vh;
  background-color: $bg-color;
  color: $text-color;
  font-family: 'PingFang SC', sans-serif;
  overflow: hidden;
}

.chat-navbar {
  :deep(.van-nav-bar) {
    background: rgba(12, 15, 26, 0.9);
    backdrop-filter: blur(10px);
  }
  :deep(.van-nav-bar__title) {
    max-width: 70%;
  }
  .nav-icon {
    color: $text-color;
    font-size: 22px;
  }
  .nav-title {
    display: flex;
    flex-direction: column;
    align-items: center;
    line-height: 1.2;
    
    .service-name {
      font-size: 16px;
      font-weight: 600;
    }
    .service-status {
      display: flex;
      align-items: center;
      font-size: 10px;
      color: #4ade80; 
      margin-top: 2px;
      
      .status-dot {
        width: 6px;
        height: 6px;
        background-color: #4ade80;
        border-radius: 50%;
        margin-right: 4px;
      }
    }
  }
}

.quick-action-bar {
  position: fixed;
  top: 46px; // Height of navbar
  left: 0;
  right: 0;
  z-index: 90;
  display: flex;
  gap: 10px;
  padding: 10px 15px;
  overflow-x: auto;
  background: linear-gradient(to bottom, rgba(12,15,26,0.9) 0%, rgba(12,15,26,0) 100%);
  
  &::-webkit-scrollbar {
    display: none;
  }
  
  .action-item {
    flex-shrink: 0;
    padding: 6px 12px;
    background: rgba(255, 255, 255, 0.08);
    border-radius: 20px;
    font-size: 12px;
    color: #ddd;
    border: 1px solid rgba(255, 255, 255, 0.05);
    transition: all 0.2s;
    
    &:active {
      background: rgba(255, 255, 255, 0.15);
    }
  }
}

.chat-content {
  flex: 1;
  overflow-y: auto;
  padding: 60px 15px 20px; 
  display: flex;
  flex-direction: column;
  gap: 20px;
  scroll-behavior: smooth;
  
  .time-divider {
    text-align: center;
    margin: 10px 0;
    span {
      font-size: 12px;
      color: rgba(255, 255, 255, 0.3);
      background: rgba(0, 0, 0, 0.2);
      padding: 2px 10px;
      border-radius: 10px;
    }
  }
  
    .messages-container {
    animation: slideUpFade 0.5s ease-out;
  }
    
  .message-item {
    width: 100%;
    margin-bottom: 15px;
    
    &.system {
      text-align: center;
      .system-msg {
        display: inline-block;
        font-size: 12px;
        color: #5F677B;
        background: rgba(255, 255, 255, 0.05);
        padding: 4px 12px;
        border-radius: 4px;
      }
    }
    
    .bubble-wrapper {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      max-width: 85%;
      
      &.is-me {
        flex-direction: row-reverse;
        margin-left: auto;
        
        .bubble-content {
          background: linear-gradient(135deg, $gold-start 0%, $gold-end 100%);
          color: #333; 
          box-shadow: 0 4px 15px rgba(245, 181, 68, 0.2);
          border-top-right-radius: 4px;
        }
      }
      
      &:not(.is-me) {
         .bubble-content {
           background: $card-bg;
           color: #fff;
           border: 1px solid rgba(255, 255, 255, 0.1);
           backdrop-filter: blur(10px);
           border-top-left-radius: 4px;
           box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
         }
      }
    }
    
    .avatar-container {
      width: 36px;
      height: 36px;
      flex-shrink: 0;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.3);
      
      &.service-avatar {
        background: #1A1F2B;
        border: 1px solid rgba(255,255,255,0.1);
        color: #4ade80;
      }
      
      &.user-avatar {
        background: linear-gradient(135deg, $gold-start 0%, $gold-end 100%);
        color: #333;
      }
    }
    
    .bubble-content {
      padding: 12px 16px;
      border-radius: $bubble-radius;
      font-size: 14px;
      line-height: 1.5;
      position: relative;
      word-break: break-word;
      
      .text-msg {
        white-space: pre-wrap;
      }
      
      .image-msg {
        max-width: 200px;
        img {
          display: block;
        }
      }
      
      .card-msg {
        min-width: 200px;
        .card-title {
           font-weight: bold;
           margin-bottom: 8px;
           font-size: 15px;
           border-bottom: 1px solid rgba(255,255,255,0.1);
           padding-bottom: 8px;
        }
        .card-body {
           font-size: 13px;
           opacity: 0.9;
           margin-bottom: 10px;
        }
        .card-actions {
           display: flex;
           flex-direction: column;
           gap: 8px;
           .card-btn {
              background: rgba(255,255,255,0.1);
              text-align: center;
              padding: 6px;
              border-radius: 6px;
              font-size: 12px;
              cursor: pointer;
              &:active { background: rgba(255,255,255,0.2); }
           }
        }
      }

      .embedded-module {
         margin-top: 8px;
         padding-top: 8px;
         border-top: 1px solid rgba(255,255,255,0.1);
         font-size: 12px;
         
         .risk-warning {
            color: #F5B544;
            display: flex;
            align-items: center;
            gap: 4px;
         }
         .quick-link {
            color: #60A5FA; 
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
         }
      }
    }
    
    .typing-indicator {
      display: flex;
      gap: 4px;
      padding: 5px 0;
      span {
        width: 6px;
        height: 6px;
        background: rgba(255, 255, 255, 0.6);
        border-radius: 50%;
        animation: bounce 1.4s infinite ease-in-out both;
        &:nth-child(1) { animation-delay: -0.32s; }
        &:nth-child(2) { animation-delay: -0.16s; }
      }
    }
  }
  
  .session-ended {
     text-align: center;
     padding: 20px;
     margin-top: 20px;
     .ended-notice {
        color: #888;
        font-size: 14px;
        margin-bottom: 15px;
     }
     .ended-actions {
        display: flex;
        justify-content: center;
        gap: 15px;
        .ended-btn {
           background: rgba(255,255,255,0.1);
           border: none;
           color: #fff;
           padding: 0 20px;
        }
     }
  }
}

.chat-footer {
  background: #161922;
  padding-bottom: env(safe-area-inset-bottom);
  border-top: 1px solid rgba(255, 255, 255, 0.05);
  
  .toolbar {
    display: flex;
    align-items: center;
    padding: 10px 15px;
    gap: 10px;
    
    .tool-btn {
      width: 36px;
      height: 36px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      color: #888;
      transition: transform 0.2s;
      &:active { transform: scale(0.9); }
    }
    
    .input-box {
      flex: 1;
      height: 40px;
      background: rgba(255, 255, 255, 0.08);
      border-radius: 20px;
      display: flex;
      align-items: center;
      padding: 0 15px;
      border: 1px solid rgba(255, 255, 255, 0.05);
      
      input {
        width: 100%;
        background: transparent;
        border: none;
        color: #fff;
        font-size: 14px;
        &::placeholder { color: #555; }
      }
    }
    
    .send-btn {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.1);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #888;
      transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
      
      &.active {
        background: linear-gradient(135deg, $gold-start 0%, $gold-end 100%);
        color: #333;
        box-shadow: 0 0 10px rgba(245, 181, 68, 0.4);
        
        &:active {
          transform: scale(0.92);
          filter: brightness(1.2);
        }
      }
    }
  }
  
  .function-panel {
    height: 200px;
    background: #161922;
    border-top: 1px solid rgba(255, 255, 255, 0.05);
    padding: 20px;
    
    .panel-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 20px;
      
      .panel-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        
        .panel-icon {
          width: 56px;
          height: 56px;
          background: rgba(255, 255, 255, 0.05);
          border-radius: 16px;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 24px;
          color: #ddd;
          transition: background 0.2s;
          
          &:active {
            background: rgba(255, 255, 255, 0.1);
          }
        }
        
        .panel-text {
          font-size: 12px;
          color: #888;
        }
      }
    }
  }
}

@keyframes bounce {
  0%, 80%, 100% { transform: scale(0); }
  40% { transform: scale(1); }
}
</style>
