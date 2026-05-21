<template>
  <div class="faq-list">
    <div 
      v-for="item in items" 
      :key="item.id" 
      class="faq-item"
      @click="openDetail(item)"
    >
      <div class="faq-icon" :class="getIconClass(item.category)">
        <van-icon :name="getIconName(item.category)" />
      </div>
      <div class="faq-content">
        <div class="faq-title">{{ item.title }}</div>
      </div>
      <van-icon name="arrow" class="faq-arrow" />
    </div>

    
    <van-popup
      v-model:show="showDetail"
      position="bottom"
      round
      class="faq-detail-popup"
      :style="{ height: '70%' }"
    >
      <div class="popup-content" v-if="currentItem">
        <div class="popup-header">
          <h3>{{ currentItem.title }}</h3>
        </div>
        <div class="popup-body">
          <div class="answer-content" v-safe-html="currentItem.content"></div>
        </div>
        <div class="popup-footer">
          <van-button 
            block 
            class="solve-btn" 
            @click="handleSolved"
          >
            Đã giải quyết
          </van-button>
          <van-button 
            block 
            class="service-btn" 
            plain 
            @click="handleContactService"
          >
            Vẫn cần trợ giúp, liên hệ CSKH
          </van-button>
        </div>
      </div>
    </van-popup>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { showToast } from 'vant';
import { sanitizeHtml } from '@/utils/sanitize';

interface FaqItem {
  id: number;
  category: string;
  title: string;
  content: string;
  tag?: string;
}

const props = defineProps<{
  items: FaqItem[]
}>();

const emit = defineEmits(['contact-service', 'detail-toggle']);

const showDetail = ref(false);
const currentItem = ref<FaqItem | null>(null);

watch(showDetail, (val) => {
  emit('detail-toggle', val);
});

const getIconName = (category: string) => {
  const map: Record<string, string> = {
    'newbie': 'smile-o',
    'wallet': 'gold-coin-o',
    'account': 'shield-o',
    'activity': 'gift-o'
  };
  return map[category] || 'question-o';
};

const getIconClass = (category: string) => {
  return `icon-${category}`;
};

const openDetail = (item: FaqItem) => {
  currentItem.value = item;
  showDetail.value = true;
};

const handleSolved = () => {
  showDetail.value = false;
  showToast({
    message: 'Cảm ơn phản hồi của bạn',
    icon: 'success'
  });
};

const handleContactService = () => {
  showDetail.value = false;

  emit('contact-service');
};
</script>

<style scoped lang="scss">

$card-bg: #151821;
$text-primary: #FFFFFF;
$text-secondary: #C4C9D4;
$text-tertiary: #7E8495;
$accent-color: #F0C930; // 金色
$border-color: rgba(255, 255, 255, 0.05);

.faq-list {
  padding: 10px 16px;
  padding-bottom: 80px; 
}

.faq-item {
  display: flex;
  align-items: center;
  background: $card-bg;
  border-radius: 16px;
  padding: 16px;
  margin-bottom: 12px;
  transition: transform 0.1s, background-color 0.2s;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);

  &:active {
    transform: scale(0.98);
    background: lighten($card-bg, 2%);
  }

  .faq-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.05);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 12px;
    flex-shrink: 0;
    font-size: 20px;

    &.icon-newbie { color: #4AD2C8; background: rgba(74, 210, 200, 0.1); }
    &.icon-wallet { color: #FFB74D; background: rgba(255, 183, 77, 0.1); }
    &.icon-account { color: #62A5F6; background: rgba(98, 165, 246, 0.1); }
    &.icon-activity { color: #FF5252; background: rgba(255, 82, 82, 0.1); }
  }

  .faq-content {
    flex: 1;
    margin-right: 12px;
    
    .faq-title {
      color: $text-primary;
      font-size: 15px;
      line-height: 1.4;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
  }

  .faq-arrow {
    color: $text-tertiary;
    font-size: 16px;
  }
}

.faq-detail-popup {
  background: linear-gradient(180deg, #1A1E29 0%, #0D1019 100%);
  
  .popup-content {
    height: 100%;
    display: flex;
    flex-direction: column;
  }

  .popup-header {
    padding: 24px 20px 16px;
    text-align: center;
    
    h3 {
      margin: 0;
      color: $text-primary;
      font-size: 18px;
      font-weight: 600;
      line-height: 1.4;
    }
  }

  .popup-body {
    flex: 1;
    padding: 0 20px;
    overflow-y: auto;
    
    .answer-content {
      color: $text-secondary;
      font-size: 15px;
      line-height: 1.6;
      
      :deep(p) {
        margin-bottom: 12px;
      }
      
      :deep(strong) {
        color: $accent-color;
      }
    }
  }

  .popup-footer {
    padding: 20px;
    background: rgba(13, 16, 25, 0.9);
    backdrop-filter: blur(10px);
    
    .solve-btn {
      background: linear-gradient(90deg, #F0C930 0%, #D4AF37 100%);
      border: none;
      color: #000;
      font-weight: bold;
      border-radius: 24px;
      margin-bottom: 12px;
    }

    .service-btn {
      background: transparent;
      border: 1px solid rgba(255, 255, 255, 0.2);
      color: $text-secondary;
      border-radius: 24px;
    }
  }
}
</style>
