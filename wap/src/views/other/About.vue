<template>
  <div class="about-page">
    <van-nav-bar
      title="关于我们"
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
        <h1 class="app-name">极速娱乐</h1>
        <p class="version">Version 2.0.1 (Build 202311)</p>
      </div>

      <div class="info-card">
        <div class="card-title">平台简介</div>
        <div class="card-text">
          <p>极速娱乐致力于为用户提供安全、稳定、流畅的在线娱乐体验。我们采用顶尖的加密技术保护用户隐私，并引入全球领先的游戏系统，确保公平公正。</p>
          <p>作为行业领先者，我们拥有24小时专业客服团队，随时为您解答疑问，处理突发状况，让您无后顾之忧。</p>
        </div>
      </div>

      <div class="menu-list">
        <div class="menu-item" @click="showToast('已是最新版本')">
          <span>检查更新</span>
          <van-icon name="arrow" />
        </div>
        <div class="menu-item">
          <span>用户协议</span>
          <van-icon name="arrow" />
        </div>
        <div class="menu-item">
          <span>隐私政策</span>
          <van-icon name="arrow" />
        </div>
      </div>
      
      <div class="footer-copy">
        <p>Copyright © 2023-2025 极速娱乐 All Rights Reserved.</p>
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
