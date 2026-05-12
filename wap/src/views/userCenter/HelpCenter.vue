<template>
  <div class="help-center-page">
    
    <div class="header-section">
      <van-nav-bar
        title="帮助中心"
        left-arrow
        @click-left="onClickLeft"
        :border="false"
        class="custom-nav"
      />
      
      
      <div class="search-wrapper">
        <div class="search-bar-glass">
          <van-icon name="search" class="search-icon" />
          <input 
            v-model="searchValue" 
            type="text" 
            placeholder="搜索问题..." 
            class="search-input"
          />
        </div>
      </div>
    </div>

    
    <div class="content-section">
      
      <van-tabs 
        v-model:active="activeTab" 
        background="transparent"
        line-width="20px"
        line-height="3px"
        color="#F0C930"
        title-active-color="#F0C930"
        title-inactive-color="#969799"
        class="custom-tabs"
        sticky
        offset-top="110px"
      >
        <van-tab v-for="tab in tabs" :key="tab.key" :title="tab.title" :name="tab.key">
          <div class="tab-content">
            <faq-list 
              :items="filteredItems" 
              @detail-toggle="onDetailToggle"
              @contact-service="onContactService"
            />
            
            
            <van-empty 
              v-if="filteredItems.length === 0" 
              description="未找到相关问题"
              image="search"
            />
          </div>
        </van-tab>
      </van-tabs>
    </div>

    
    <transition name="fade">
      <div 
        v-show="showFloatingBtn" 
        class="floating-service"
        @click="onContactService"
      >
        <div class="service-icon-circle">
          <van-icon name="service" />
        </div>
        <span class="service-text">联系客服</span>
      </div>
    </transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import FaqList from '@/components/help/FaqList.vue';

const router = useRouter();

const activeTab = ref('all');
const searchValue = ref('');
const showFloatingBtn = ref(true);

const tabs = [
  { key: 'all', title: '全部' },
  { key: 'newbie', title: '新手' },
  { key: 'wallet', title: '充提' },
  { key: 'account', title: '账户' },
  { key: 'activity', title: '活动' },
];

const allFaqItems = [
  { id: 1, category: 'newbie', title: '如何注册新账号？', content: '点击首页右下角的"我的"，然后点击"注册"按钮。填写手机号、验证码和设置密码即可完成注册。<br><br><strong>注意：</strong>请确保手机号真实有效。' },
  { id: 2, category: 'wallet', title: '充值未到账怎么办？', content: '一般情况下充值会在1-5分钟内到账。如果超过10分钟未到账，请保留转账截图，点击下方的"联系客服"按钮进行处理。' },
  { id: 3, category: 'account', title: '忘记登录密码如何找回？', content: '在登录页面点击"忘记密码"，通过注册时的手机号验证即可重置密码。' },
  { id: 4, category: 'activity', title: '新用户有什么福利？', content: '新用户注册即送体验金，首充更有高额返利。具体请查看"活动中心"页面。' },
  { id: 5, category: 'wallet', title: '提现需要手续费吗？', content: '每日首笔提现免手续费，后续提现将收取少量手续费，具体费率以提现页面显示为准。' },
  { id: 6, category: 'account', title: '如何修改绑定银行卡？', content: '进入"我的" -> "账户安全" -> "银行卡管理"，可以添加或解绑银行卡。为了资金安全，解绑需要验证支付密码。' },
  { id: 7, category: 'newbie', title: 'APP下载安装教程', content: '安卓用户请直接下载APK安装包；iOS用户请下载描述文件并信任企业证书。' },
];

const filteredItems = computed(() => {
  let items = allFaqItems;
  
  if (activeTab.value !== 'all') {
    items = items.filter(item => item.category === activeTab.value);
  }
  
  if (searchValue.value.trim()) {
    const keyword = searchValue.value.toLowerCase();
    items = items.filter(item => 
      item.title.toLowerCase().includes(keyword) || 
      item.content.toLowerCase().includes(keyword)
    );
  }
  
  return items;
});

const onClickLeft = () => {
  router.back();
};

const onContactService = () => {
  router.push('/yue-bao/service');
};

const onDetailToggle = (isOpen: boolean) => {
  showFloatingBtn.value = !isOpen;
};
</script>

<style scoped lang="scss">
$bg-color: #050814;
$card-bg: #151821;
$accent-color: #F0C930;
$text-primary: #FFFFFF;
$text-secondary: #969799;

.help-center-page {
  min-height: 100vh;
  background-color: $bg-color;
  display: flex;
  flex-direction: column;
  
  &::before {
    content: '';
    position: fixed;
    top: -20%;
    left: -20%;
    width: 140%;
    height: 140%;
    background: radial-gradient(circle at 50% 30%, rgba(21, 32, 60, 0.4) 0%, transparent 60%);
    pointer-events: none;
    z-index: 0;
  }
}

.header-section {
  position: sticky;
  top: 0;
  z-index: 10;
  background: rgba(5, 8, 20, 0.8);
  backdrop-filter: blur(10px);
  padding-bottom: 10px;
}

.custom-nav {
  background: transparent;
  
  :deep(.van-nav-bar__title) {
    color: $text-primary;
    font-weight: 600;
  }
  
  :deep(.van-icon) {
    color: $text-primary;
  }
}

.search-wrapper {
  padding: 0 16px;
  margin-top: 0;
}

.search-bar-glass {
  display: flex;
  align-items: center;
  height: 44px;
  background: rgba(255, 255, 255, 0.08);
  border-radius: 22px;
  padding: 0 16px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  transition: all 0.3s ease;
  
  &:focus-within {
    background: rgba(255, 255, 255, 0.12);
    border-color: rgba(240, 201, 48, 0.3);
    box-shadow: 0 0 10px rgba(240, 201, 48, 0.1);
  }
  
  .search-icon {
    font-size: 18px;
    color: $text-secondary;
    margin-right: 8px;
  }
  
  .search-input {
    flex: 1;
    background: transparent;
    border: none;
    color: $text-primary;
    font-size: 14px;
    
    &::placeholder {
      color: rgba(255, 255, 255, 0.3);
    }
  }
}

.content-section {
  flex: 1;
  position: relative;
  z-index: 1;
  
  :deep(.van-tabs__wrap) {
    height: 44px;
  }
  
  .tab-content {
    min-height: calc(100vh - 160px);
  }
}

.floating-service {
  position: fixed;
  bottom: 30px;
  right: 20px;
  z-index: 99;
  display: flex;
  flex-direction: column;
  align-items: center;
  cursor: pointer;
  
  .service-icon-circle {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #F0C930 0%, #D4AF37 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: #000;
    box-shadow: 0 4px 15px rgba(240, 201, 48, 0.3);
    margin-bottom: 6px;
    transition: transform 0.2s;
    
    &:active {
      transform: scale(0.95);
    }
  }
  
  .service-text {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.8);
    text-shadow: 0 1px 2px rgba(0,0,0,0.5);
  }
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(10px);
}
</style>
