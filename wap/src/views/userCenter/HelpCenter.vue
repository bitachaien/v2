<template>
  <div class="help-center-page">
    
    <div class="header-section">
      <van-nav-bar
        title="Trung Tâm Trợ Giúp"
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
            placeholder="Tìm kiếm câu hỏi..."
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
              description="Không tìm thấy câu hỏi liên quan"
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
        <span class="service-text">Liên hệ CSKH</span>
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
  { key: 'all', title: 'Tất cả' },
  { key: 'newbie', title: 'Người mới' },
  { key: 'wallet', title: 'Nạp/Rút' },
  { key: 'account', title: 'Tài khoản' },
  { key: 'activity', title: 'Hoạt động' },
];

const allFaqItems = [
  { id: 1, category: 'newbie', title: 'Làm thế nào để đăng ký tài khoản mới?', content: 'Nhấn vào "Của tôi" ở góc dưới bên phải trang chủ, sau đó nhấn nút "Đăng ký". Điền số điện thoại, mã xác thực và đặt mật khẩu để hoàn tất đăng ký.<br><br><strong>Lưu ý:</strong> Vui lòng đảm bảo số điện thoại thật và hợp lệ.' },
  { id: 2, category: 'wallet', title: 'Nạp tiền chưa vào tài khoản phải làm sao?', content: 'Thông thường nạp tiền sẽ vào tài khoản trong 1-5 phút. Nếu quá 10 phút chưa vào, vui lòng giữ ảnh chụp màn hình chuyển khoản và nhấn nút "Liên hệ CSKH" bên dưới để xử lý.' },
  { id: 3, category: 'account', title: 'Quên mật khẩu đăng nhập phải làm sao?', content: 'Tại trang đăng nhập nhấn "Quên mật khẩu", xác thực qua số điện thoại đã đăng ký để đặt lại mật khẩu.' },
  { id: 4, category: 'activity', title: 'Người dùng mới có ưu đãi gì?', content: 'Người dùng mới đăng ký nhận ngay tiền trải nghiệm, nạp lần đầu còn có hoàn trả cao. Chi tiết xem tại trang "Trung tâm hoạt động".' },
  { id: 5, category: 'wallet', title: 'Rút tiền có mất phí không?', content: 'Lần rút đầu tiên mỗi ngày miễn phí, các lần rút sau sẽ thu phí nhỏ, mức phí cụ thể theo hiển thị tại trang rút tiền.' },
  { id: 6, category: 'account', title: 'Làm thế nào để sửa thẻ ngân hàng đã liên kết?', content: 'Vào "Của tôi" -> "Bảo mật tài khoản" -> "Quản lý thẻ ngân hàng", có thể thêm hoặc gỡ liên kết thẻ. Để bảo mật tiền, gỡ liên kết cần xác thực mật khẩu thanh toán.' },
  { id: 7, category: 'newbie', title: 'Hướng dẫn tải và cài đặt APP', content: 'Người dùng Android vui lòng tải trực tiếp file APK; người dùng iOS vui lòng tải file mô tả và tin tưởng chứng chỉ doanh nghiệp.' },
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
