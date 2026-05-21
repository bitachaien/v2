<template>
  <div class="about-page">
    <van-nav-bar
      title="Về Chúng Tôi"
      left-arrow
      @click-left="$router.back()"
      class="glass-nav"
      fixed
      :border="false"
    />

    <div class="content-wrapper">
      <div class="logo-section">
        <div class="logo-box">
          <div class="logo-inner">
            <span class="logo-text">LOGO</span>
          </div>
          <div class="logo-glow"></div>
        </div>
        <h1 class="app-name">Giải Trí Tốc Độ</h1>
        <p class="version">Version 2.0.1 (Build 202311)</p>
      </div>

      <div class="info-card">
        <div class="card-title">Giới Thiệu Nền Tảng</div>
        <div class="card-text">
          <p>Giải Trí Tốc Độ cam kết mang đến trải nghiệm giải trí trực tuyến an toàn, ổn định và mượt mà cho người dùng. Chúng tôi sử dụng công nghệ mã hóa hàng đầu để bảo vệ quyền riêng tư người dùng và áp dụng hệ thống game tiên tiến toàn cầu, đảm bảo công bằng và minh bạch.</p>
          <p>Là người dẫn đầu trong ngành, chúng tôi có đội ngũ chăm sóc khách hàng chuyên nghiệp 24/7, sẵn sàng giải đáp thắc mắc và xử lý các tình huống phát sinh, giúp bạn hoàn toàn yên tâm.</p>
        </div>
      </div>

      <div class="menu-list">
        <div class="menu-item" @click="showToast('Đã là phiên bản mới nhất')">
          <span>Kiểm tra cập nhật</span>
          <van-icon name="arrow" />
        </div>
        <div class="menu-item">
          <span>Thỏa thuận người dùng</span>
          <van-icon name="arrow" />
        </div>
        <div class="menu-item">
          <span>Chính sách bảo mật</span>
          <van-icon name="arrow" />
        </div>
      </div>
      
      <div class="footer-copy">
        <p>Copyright © 2023-2025 Giải Trí Tốc Độ All Rights Reserved.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { showToast } from 'vant';
</script>

<style scoped lang="scss">
.about-page {
  min-height: 100vh;
  background: #0a0a12;
  color: #fff;
  padding-top: 46px;
}

.glass-nav {
  background: rgba(10, 10, 18, 0.9) !important;
  backdrop-filter: blur(10px);
  
  :deep(.van-nav-bar__title) {
    color: #fff;
    font-weight: bold;
  }
  
  :deep(.van-icon) {
    color: #fff;
  }
}

.content-wrapper {
  padding: 30px 20px;
  display: flex;
  flex-direction: column;
  gap: 30px;
}

.logo-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 15px;
}

.logo-box {
  width: 80px;
  height: 80px;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
}

.logo-inner {
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, #00f2ff, #0066ff);
  border-radius: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2;
  box-shadow: 0 0 20px rgba(0, 242, 255, 0.3);
}

.logo-text {
  font-weight: bold;
  font-size: 18px;
}

.logo-glow {
  position: absolute;
  inset: -10px;
  background: radial-gradient(circle, rgba(0, 242, 255, 0.4), transparent 70%);
  filter: blur(10px);
  z-index: 1;
  animation: pulse 3s infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 0.6; transform: scale(1); }
  50% { opacity: 1; transform: scale(1.1); }
}

.app-name {
  font-size: 24px;
  font-weight: bold;
  margin: 0;
  background: linear-gradient(to right, #fff, #00f2ff);
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
}

.version {
  color: rgba(255, 255, 255, 0.4);
  font-size: 12px;
}

.info-card {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 16px;
  padding: 20px;
}

.card-title {
  font-size: 16px;
  font-weight: bold;
  color: #00f2ff;
  margin-bottom: 12px;
  padding-left: 10px;
  border-left: 3px solid #00f2ff;
  line-height: 1;
}

.card-text {
  font-size: 13px;
  line-height: 1.6;
  color: rgba(255, 255, 255, 0.7);
  
  p {
    margin-bottom: 10px;
    &:last-child { margin-bottom: 0; }
  }
}

.menu-list {
  background: rgba(255, 255, 255, 0.03);
  border-radius: 16px;
  overflow: hidden;
}

.menu-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  font-size: 14px;
  color: #fff;
  
  &:last-child { border-bottom: none; }
  &:active { background: rgba(255, 255, 255, 0.08); }
  
  .van-icon {
    color: rgba(255, 255, 255, 0.3);
  }
}

.footer-copy {
  text-align: center;
  color: rgba(255, 255, 255, 0.2);
  font-size: 10px;
  margin-top: 20px;
}
</style>
