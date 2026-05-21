<template>
  <div class="invite-reward-page">
    <div class="cyber-bg">
      <div class="grid-overlay"></div>
      <div class="particle-container">
        <div v-for="i in 20" :key="i" class="particle" :style="particleStyle(i)"></div>
      </div>
    </div>

    <header class="invite-header">
      <div class="header-back" @click="$router.back()">
        <i class="iconfont">&#xe602;</i>
      </div>
      <div class="header-center">
        <h1 class="header-title">Mời Bạn Nhận Quà</h1>
        <p class="header-subtitle">Mời bạn bè cùng chơi, dễ dàng kiếm thưởng</p>
      </div>
      <div class="header-actions">
        <i class="iconfont" @click="showRules = true">&#xe63f;</i>
      </div>
    </header>

    <div class="invite-content">
      <div class="banner-card-enhanced">
        <div class="banner-scan-line"></div>
        <div class="banner-particles">
          <div v-for="i in 8" :key="i" class="banner-particle" :style="bannerParticleStyle(i)"></div>
        </div>
        <div class="banner-content">
          <div class="banner-left">
            <h2 class="banner-title">Mời Bạn Bè · Thu Nhập Kép</h2>
            <p class="banner-desc">Bạn bè cược, bạn nhận thưởng<br/>Hoàn trả tối đa <span class="highlight-glow">8%</span></p>
            <div class="banner-stats-mini">
              <div class="mini-stat">
                <i class="iconfont">&#xe625;</i>
                <span>{{ stats.totalInvited }}人</span>
              </div>
              <div class="mini-stat">
                <i class="iconfont">&#xe61e;</i>
                <span>¥{{ stats.totalReward }}</span>
              </div>
            </div>
          </div>
          <div class="banner-right">
            <div class="tech-gift-icon">
              <div class="gift-core">
                <svg viewBox="0 0 100 100" class="gift-svg">
                  <circle cx="50" cy="50" r="35" fill="url(#techGradient)" opacity="0.2"/>
                  <rect x="30" y="45" width="40" height="30" rx="4" fill="none" stroke="url(#techGradient)" stroke-width="2"/>
                  <line x1="50" y1="35" x2="50" y2="75" stroke="#00f2ff" stroke-width="3"/>
                  <ellipse cx="50" cy="35" rx="18" ry="10" fill="#ff9f43" opacity="0.8"/>
                  <circle cx="35" cy="55" r="3" fill="#00f260"/>
                  <circle cx="65" cy="65" r="3" fill="#00f260"/>
                  <defs>
                    <linearGradient id="techGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                      <stop offset="0%" stop-color="#00f2ff"/>
                      <stop offset="100%" stop-color="#00f260"/>
                    </linearGradient>
                  </defs>
                </svg>
              </div>
              <div class="gift-ring ring-1"></div>
              <div class="gift-ring ring-2"></div>
              <div class="gift-glow-ball"></div>
            </div>
          </div>
        </div>
        <div class="banner-border-glow"></div>
      </div>

      <div class="overview-card">
        <div class="card-title-bar">
          <span class="card-title">Lời Mời Của Tôi</span>
          <div class="title-line"></div>
        </div>

        <div class="user-info-row">
          <div class="user-avatar">
            <img :src="userInfo.avatar || 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%22 height=%22100%22%3E%3Ccircle cx=%2250%22 cy=%2250%22 r=%2240%22 fill=%22%2300f2ff%22 opacity=%220.2%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 font-size=%2240%22 fill=%22%2300f2ff%22%3E%E7%94%A8%3C/text%3E%3C/svg%3E'" alt="avatar"/>
            <div class="avatar-ring"></div>
          </div>
          <div class="user-name">{{ userInfo.username || 'Guest' }}</div>
        </div>

        <div class="invite-code-section">
          <div class="code-row">
            <span class="code-label">Mã mời</span>
            <span class="code-value">{{ inviteCode }}</span>
            <button class="copy-btn" @click="copyText(inviteCode)">
              <i class="iconfont">&#xe61f;</i>
              <span>Sao chép</span>
            </button>
          </div>
          <div class="code-row">
            <span class="code-label">Link riêng</span>
            <span class="code-value link">{{ inviteLink }}</span>
            <button class="copy-btn" @click="copyText(inviteLink)">
              <i class="iconfont">&#xe61f;</i>
              <span>Sao chép</span>
            </button>
          </div>
        </div>

        <div class="qrcode-section" @click="showQRCode = true">
          <div class="qrcode-box">
            <div class="qr-placeholder">
              <i class="iconfont">&#xe62c;</i>
              <span>Nhấn xem mã QR</span>
            </div>
            <div class="qr-glow"></div>
          </div>
        </div>

        <div class="stats-grid">
          <div class="stat-item">
            <div class="stat-value">{{ stats.totalInvited }}</div>
            <div class="stat-label">Đã mời bạn bè</div>
          </div>
          <div class="stat-item">
            <div class="stat-value">{{ stats.activeInvited }}</div>
            <div class="stat-label">Bạn bè hợp lệ</div>
          </div>
          <div class="stat-item">
            <div class="stat-value highlight">¥{{ stats.totalReward }}</div>
            <div class="stat-label">Tổng thưởng</div>
          </div>
        </div>
      </div>

      <div class="steps-section-enhanced">
        <div class="section-title-cyber">
          <span>Các Bước Mời</span>
          <div class="title-line"></div>
        </div>
        <div class="steps-flow-container">
          <div class="step-card-enhanced" v-for="(step, index) in steps" :key="index">
            <div class="step-number-glow">{{ index + 1 }}</div>
            <div class="step-icon-ball">
              <div class="icon-ball-core">
                <svg class="icon-svg-tech" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                  <path :d="step.iconPath"/>
                </svg>
              </div>
              <div class="ball-pulse"></div>
            </div>
            <h3 class="step-title-tech">{{ step.title }}</h3>
            <p class="step-desc-tech">{{ step.desc }}</p>
            <div class="step-corner tl"></div>
            <div class="step-corner br"></div>
            <div class="step-particle-line" v-if="index < steps.length - 1">
              <div v-for="j in 3" :key="j" class="flow-particle" :style="{animationDelay: `${j * 0.3}s`}"></div>
            </div>
          </div>
        </div>
      </div>

      <div class="rules-section-enhanced">
        <div class="section-title-cyber">
          <span>Quy Tắc Thưởng</span>
          <div class="title-line"></div>
        </div>
        <div class="rules-card-tech">
          <div class="rule-item-tech" v-for="(rule, index) in rules" :key="index">
            <div class="rule-number-dot">
              <span>{{ index + 1 }}</span>
              <div class="dot-glow"></div>
            </div>
            <span class="rule-text-tech">{{ rule }}</span>
          </div>
          <button class="view-full-rules-btn-glow" @click="showRules = true">
            <span>Xem quy tắc đầy đủ</span>
            <i class="iconfont">&#xe656;</i>
            <div class="btn-border-glow"></div>
          </button>
        </div>
      </div>

      <div class="friends-section">
        <div class="section-title-cyber">
          <span>Bạn Bè Của Tôi</span>
          <div class="title-line"></div>
        </div>
        <div class="friends-list-tech">
          <div class="friend-card-tech" v-for="friend in friendsList" :key="friend.id">
            <div class="friend-avatar-ball">
              <div class="avatar-ball-inner">
                <i class="iconfont">&#xe625;</i>
              </div>
              <div class="avatar-ball-ring"></div>
            </div>
            <div class="friend-info-tech">
              <div class="friend-name-tech">{{ friend.username }}</div>
              <div class="friend-meta-row">
                <span class="meta-item">Đăng ký: {{ friend.registerTime }}</span>
              </div>
              <div class="friend-data-row">
                <span class="data-item">Cược: <em>¥{{ friend.totalBet }}</em></span>
                <span class="data-item reward">Thưởng: <em>¥{{ friend.reward }}</em></span>
              </div>
            </div>
            <div class="friend-status-badge" :class="friend.status">
              <span>{{ friend.status === 'active' ? 'Hợp lệ' : 'Chưa kích hoạt' }}</span>
              <div class="badge-glow"></div>
            </div>
          </div>
          <div class="empty-state-tech" v-if="friendsList.length === 0">
            <div class="empty-icon-ball">
              <i class="iconfont">&#xe625;</i>
            </div>
            <p>Chưa có dữ liệu bạn bè</p>
            <span class="empty-tip">Mời bạn bè để nhận thưởng</span>
          </div>
        </div>
      </div>

      <div class="share-section">
        <div class="section-title-cyber">
          <span>Chia Sẻ Lời Mời</span>
          <div class="title-line"></div>
        </div>
        <div class="share-grid">
          <div class="share-btn" v-for="share in shareOptions" :key="share.type" @click="handleShare(share.type)">
            <div class="share-icon-wrapper">
              <svg class="share-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path :d="share.iconPath"/>
              </svg>
              <div class="share-glow"></div>
            </div>
            <span class="share-label">{{ share.label }}</span>
          </div>
        </div>
      </div>
    </div>

    <FooterNav />

    <van-popup v-model:show="showQRCode" round :style="{ background: 'transparent' }">
      <div class="qr-dialog">
        <div class="qr-dialog-content">
          <h3>Quét Mã Mời</h3>
          <div class="qr-code-large">
            <div class="qr-placeholder-large">
              <i class="iconfont">&#xe62c;</i>
            </div>
          </div>
          <p class="qr-tip">Bạn bè quét mã để đăng ký</p>
          <button class="qr-close-btn" @click="showQRCode = false">Đóng</button>
        </div>
      </div>
    </van-popup>

    <van-popup v-model:show="showRules" round position="bottom" :style="{ height: '70%' }">
      <div class="rules-dialog">
        <div class="rules-dialog-header">
          <h3>Quy Tắc Đầy Đủ</h3>
          <i class="iconfont" @click="showRules = false">&#xe60a;</i>
        </div>
        <div class="rules-dialog-content">
          <div class="rule-detail" v-for="(rule, index) in fullRules" :key="index">
            <h4>{{ rule.title }}</h4>
            <p>{{ rule.content }}</p>
          </div>
        </div>
      </div>
    </van-popup>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { showToast } from 'vant'

const router = useRouter()

const userInfo = ref({
  username: 'Guest',
  avatar: ''
})

const inviteCode = ref('ABCD88')
const inviteLink = ref('https://example.com/invite/ABCD88')

const stats = ref({
  totalInvited: 12,
  activeInvited: 8,
  totalReward: '1,288.00'
})

const steps = ref([
  {
    title: 'Chia sẻ mã/link mời',
    desc: 'Gửi cho bạn bè hoặc chia sẻ lên mạng xã hội',
    iconPath: 'M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8M16 6l-4-4-4 4M12 2v13'
  },
  {
    title: 'Bạn bè đăng ký và nạp tiền',
    desc: 'Sử dụng mã mời của bạn để hoàn tất đăng ký',
    iconPath: 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM22 11l-3-3m0 0l-3 3m3-3v8'
  },
  {
    title: 'Bạn bè cược, bạn nhận thưởng',
    desc: 'Mỗi lần bạn bè cược, bạn đều nhận hoàn trả',
    iconPath: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z'
  }
])

const rules = ref([
  'Bạn bè đăng ký qua link riêng hoặc mã mời của bạn sẽ được tính là người dùng mời của bạn.',
  'Mỗi lần bạn bè cược hợp lệ, bạn nhận 5% hoa hồng hoàn trả.',
  'Hoàn trả được thanh toán ngay vào [Số dư thưởng mời], có thể chuyển vào tài khoản chính bất cứ lúc nào.',
  'Cùng thiết bị, cùng thông tin danh tính chỉ được mời một lần, nghiêm cấm tạo tài khoản ảo, lợi dụng.',
  'Nền tảng có quyền đóng băng, xóa số dư các tài khoản bất thường.'
])

const fullRules = ref([
  { title: 'Điều kiện mời', content: 'Tất cả người dùng đã đăng ký đều có thể tham gia hoạt động mời bạn, mời bạn bè đăng ký và cược để nhận thưởng.' },
  { title: 'Tính thưởng', content: '5% số tiền cược hợp lệ của bạn bè sẽ được hoàn trả hoa hồng cho người mời, thanh toán ngay lập tức.' },
  { title: 'Quy tắc rút tiền', content: 'Số dư thưởng mời có thể chuyển vào tài khoản chính bất cứ lúc nào, không yêu cầu doanh thu.' },
  { title: 'Xử lý vi phạm', content: 'Nghiêm cấm sử dụng thông tin giả, tạo tài khoản ảo để lợi dụng, một khi phát hiện sẽ đóng băng tài khoản và xóa thưởng.' }
])

const friendsList = ref([
  { id: 1, username: 'Người dùng***123', registerTime: '2024-11-20', totalBet: '5,200', reward: '260.00', status: 'active' },
  { id: 2, username: 'Người dùng***456', registerTime: '2024-11-19', totalBet: '3,800', reward: '190.00', status: 'active' },
  { id: 3, username: 'Người dùng***789', registerTime: '2024-11-18', totalBet: '0', reward: '0.00', status: 'inactive' }
])

const shareOptions = ref([
  { type: 'wechat', label: 'Bạn WeChat', iconPath: 'M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z' },
  { type: 'moments', label: 'Moments', iconPath: 'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm0 18a8 8 0 1 1 0-16 8 8 0 0 1 0 16z' },
  { type: 'qq', label: 'QQ', iconPath: 'M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z' },
  { type: 'copy', label: 'Sao chép link', iconPath: 'M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2' },
  { type: 'poster', label: 'Lưu poster', iconPath: 'M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3' }
])

const showQRCode = ref(false)
const showRules = ref(false)

const copyText = (text) => {
  navigator.clipboard.writeText(text).then(() => {
    showToast('Sao chép thành công')
  }).catch(() => {
    showToast('Sao chép thất bại')
  })
}

const handleShare = (type) => {
  showToast(`Chia sẻ đến ${type}`)
}

const particleStyle = (index) => {
  return {
    left: `${Math.random() * 100}%`,
    top: `${Math.random() * 100}%`,
    animationDelay: `${Math.random() * 3}s`,
    animationDuration: `${3 + Math.random() * 2}s`
  }
}

const bannerParticleStyle = (index) => {
  return {
    left: `${10 + Math.random() * 80}%`,
    top: `${10 + Math.random() * 80}%`,
    animationDelay: `${index * 0.2}s`,
    animationDuration: `${2 + Math.random()}s`
  }
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;700&display=swap');

:root {
  --neon-blue: #00f2ff;
  --neon-green: #00f260;
  --neon-purple: #bc13fe;
  --dark-bg: #0a0a12;
}

.invite-reward-page {
  min-height: 100vh;
  background: #0a0a12;
  padding-bottom: 80px;
  position: relative;
}

.cyber-bg {
  position: fixed;
  inset: 0;
  z-index: 0;
  pointer-events: none;
}

.grid-overlay {
  position: absolute;
  inset: 0;
  background-image: 
    repeating-linear-gradient(0deg, transparent 0, transparent 19px, rgba(0, 242, 255, 0.03) 20px),
    repeating-linear-gradient(90deg, transparent 0, transparent 19px, rgba(0, 242, 255, 0.03) 20px);
  opacity: 0.3;
}

.particle-container {
  position: absolute;
  inset: 0;
  overflow: hidden;
}

.particle {
  position: absolute;
  width: 2px;
  height: 2px;
  background: var(--neon-blue);
  border-radius: 50%;
  animation: float linear infinite;
  opacity: 0.6;
}

@keyframes float {
  0% { transform: translateY(0) translateX(0); opacity: 0; }
  50% { opacity: 0.6; }
  100% { transform: translateY(-100vh) translateX(50px); opacity: 0; }
}

.invite-header {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  height: 60px;
  background: rgba(10, 10, 18, 0.95);
  backdrop-filter: blur(20px);
  border-bottom: 1px solid rgba(0, 242, 255, 0.2);
  display: flex;
  align-items: center;
  padding: 0 15px;
  z-index: 100;
}

.header-back {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.header-back .iconfont {
  font-size: 20px;
  color: var(--neon-blue);
}

.header-center {
  flex: 1;
  text-align: center;
}

.header-title {
  font-size: 18px;
  font-weight: 900;
  color: #fff;
  margin: 0;
  text-shadow: 0 0 10px var(--neon-blue);
}

.header-subtitle {
  font-size: 10px;
  color: rgba(255, 255, 255, 0.5);
  margin: 2px 0 0;
}

.header-actions {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.header-actions .iconfont {
  font-size: 20px;
  color: var(--neon-blue);
  cursor: pointer;
}

.invite-content {
  margin-top: 70px;
  padding: 15px;
  position: relative;
  z-index: 1;
}

.banner-card-enhanced {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(0, 242, 255, 0.3);
  border-radius: 16px;
  padding: 25px 20px;
  margin-bottom: 20px;
  position: relative;
  overflow: hidden;
  backdrop-filter: blur(15px);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
}

.banner-scan-line {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 2px;
  background: linear-gradient(90deg, transparent, var(--neon-blue), transparent);
  animation: scan-horizontal 3s linear infinite;
}

@keyframes scan-horizontal {
  0% { transform: translateX(-100%); }
  100% { transform: translateX(100%); }
}

.banner-particles {
  position: absolute;
  inset: 0;
  pointer-events: none;
}

.banner-particle {
  position: absolute;
  width: 3px;
  height: 3px;
  background: var(--neon-green);
  border-radius: 50%;
  animation: banner-particle-float ease-in-out infinite;
  opacity: 0.6;
}

@keyframes banner-particle-float {
  0%, 100% { transform: translate(0, 0); opacity: 0.3; }
  50% { transform: translate(10px, -10px); opacity: 0.8; }
}

.banner-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  position: relative;
  z-index: 1;
}

.banner-left {
  flex: 1;
}

.banner-title {
  font-size: 20px;
  font-weight: 900;
  color: #fff;
  margin: 0 0 10px;
  text-shadow: 0 0 15px var(--neon-blue);
  letter-spacing: 1px;
}

.banner-desc {
  font-size: 13px;
  color: rgba(255, 255, 255, 0.8);
  line-height: 1.8;
  margin-bottom: 12px;
}

.highlight-glow {
  font-size: 24px;
  font-weight: 900;
  color: var(--neon-green);
  text-shadow: 0 0 20px var(--neon-green), 0 0 40px var(--neon-green);
  font-family: 'Rajdhani', sans-serif;
  display: inline-block;
  animation: glow-pulse 2s infinite;
}

@keyframes glow-pulse {
  0%, 100% { text-shadow: 0 0 15px var(--neon-green); }
  50% { text-shadow: 0 0 25px var(--neon-green), 0 0 40px var(--neon-green); }
}

.banner-stats-mini {
  display: flex;
  gap: 15px;
  margin-top: 10px;
}

.mini-stat {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: var(--neon-blue);
  font-family: 'Rajdhani', sans-serif;
}

.mini-stat .iconfont {
  font-size: 14px;
}

.banner-right {
  width: 100px;
  height: 100px;
}

.tech-gift-icon {
  position: relative;
  width: 100%;
  height: 100%;
}

.gift-core {
  position: absolute;
  inset: 15px;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2;
}

.gift-svg {
  width: 100%;
  height: 100%;
  filter: drop-shadow(0 0 8px var(--neon-blue));
  animation: float-gift 3s ease-in-out infinite;
}

@keyframes float-gift {
  0%, 100% { transform: translateY(0) rotate(0deg); }
  50% { transform: translateY(-8px) rotate(5deg); }
}

.gift-ring {
  position: absolute;
  inset: 0;
  border-radius: 50%;
  border: 2px solid;
  animation: spin 4s linear infinite;
}

.gift-ring.ring-1 {
  border-color: var(--neon-blue) transparent var(--neon-blue) transparent;
  animation-duration: 3s;
}

.gift-ring.ring-2 {
  border-color: transparent var(--neon-green) transparent var(--neon-green);
  animation-duration: 4s;
  animation-direction: reverse;
}

.gift-glow-ball {
  position: absolute;
  inset: -10px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(0, 242, 255, 0.2), transparent 70%);
  filter: blur(15px);
  animation: breathe 3s infinite;
}

.banner-border-glow {
  position: absolute;
  inset: -1px;
  border-radius: 16px;
  background: linear-gradient(135deg, var(--neon-blue), var(--neon-green));
  opacity: 0;
  transition: opacity 0.3s;
  z-index: 0;
}

.banner-card-enhanced:hover .banner-border-glow {
  opacity: 0.1;
}

.overview-card {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 16px;
  padding: 20px;
  margin-bottom: 20px;
  backdrop-filter: blur(10px);
}

.card-title-bar {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 20px;
}

.card-title {
  font-size: 16px;
  font-weight: bold;
  color: #fff;
  white-space: nowrap;
}

.title-line {
  flex: 1;
  height: 1px;
  background: linear-gradient(to right, var(--neon-blue), transparent);
}

.user-info-row {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 20px;
  padding-bottom: 15px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.user-avatar {
  position: relative;
  width: 50px;
  height: 50px;
}

.user-avatar img {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  border: 2px solid var(--neon-blue);
}

.avatar-ring {
  position: absolute;
  inset: -4px;
  border-radius: 50%;
  border: 2px solid var(--neon-blue);
  border-top-color: transparent;
  animation: spin 3s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.user-name {
  font-size: 16px;
  font-weight: bold;
  color: #fff;
}

.invite-code-section {
  margin-bottom: 20px;
}

.code-row {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px;
  background: rgba(0, 0, 0, 0.3);
  border: 1px solid rgba(0, 242, 255, 0.1);
  border-radius: 8px;
  margin-bottom: 10px;
}

.code-label {
  font-size: 12px;
  color: rgba(255, 255, 255, 0.6);
  width: 70px;
}

.code-value {
  flex: 1;
  font-size: 14px;
  color: var(--neon-blue);
  font-family: 'Rajdhani', sans-serif;
  font-weight: bold;
}

.code-value.link {
  font-size: 11px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.copy-btn {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 6px 12px;
  background: rgba(0, 242, 255, 0.1);
  border: 1px solid var(--neon-blue);
  border-radius: 12px;
  color: var(--neon-blue);
  font-size: 11px;
  cursor: pointer;
  transition: all 0.3s;
}

.copy-btn:active {
  transform: scale(0.95);
  background: rgba(0, 242, 255, 0.2);
}

.qrcode-section {
  margin-bottom: 20px;
  cursor: pointer;
}

.qrcode-box {
  width: 120px;
  height: 120px;
  margin: 0 auto;
  background: rgba(255, 255, 255, 0.05);
  border: 2px solid var(--neon-blue);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  transition: all 0.3s;
}

.qrcode-box:active {
  transform: scale(0.95);
  box-shadow: 0 0 20px rgba(0, 242, 255, 0.4);
}

.qr-placeholder {
  text-align: center;
  color: rgba(255, 255, 255, 0.5);
}

.qr-placeholder .iconfont {
  font-size: 40px;
  display: block;
  margin-bottom: 5px;
}

.qr-placeholder span {
  font-size: 10px;
}

.qr-glow {
  position: absolute;
  inset: -4px;
  border-radius: 12px;
  background: radial-gradient(circle, rgba(0, 242, 255, 0.2), transparent);
  filter: blur(8px);
  opacity: 0;
  transition: opacity 0.3s;
}

.qrcode-box:hover .qr-glow {
  opacity: 1;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 15px;
}

.stat-item {
  text-align: center;
}

.stat-value {
  font-size: 20px;
  font-weight: bold;
  color: #fff;
  font-family: 'Rajdhani', sans-serif;
  margin-bottom: 5px;
}

.stat-value.highlight {
  color: var(--neon-green);
  text-shadow: 0 0 15px var(--neon-green);
}

.stat-label {
  font-size: 11px;
  color: rgba(255, 255, 255, 0.6);
}

.steps-section-enhanced {
  margin-bottom: 20px;
}

.section-title-cyber {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 15px;
}

.section-title-cyber span {
  font-size: 16px;
  font-weight: bold;
  color: #fff;
  white-space: nowrap;
}

.steps-flow-container {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
  position: relative;
}

.step-card-enhanced {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(0, 242, 255, 0.2);
  border-radius: 12px;
  padding: 20px 12px;
  text-align: center;
  position: relative;
  overflow: visible;
  backdrop-filter: blur(10px);
  transition: all 0.3s;
}

.step-card-enhanced:active {
  transform: translateY(-6px);
  border-color: var(--neon-blue);
  box-shadow: 0 8px 25px rgba(0, 242, 255, 0.4);
}

.step-number-glow {
  position: absolute;
  top: -8px;
  left: 50%;
  transform: translateX(-50%);
  width: 28px;
  height: 28px;
  background: linear-gradient(135deg, var(--neon-blue), var(--neon-green));
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  font-weight: 900;
  color: #000;
  box-shadow: 0 0 15px var(--neon-blue);
  z-index: 2;
}

.step-icon-ball {
  width: 50px;
  height: 50px;
  margin: 15px auto 12px;
  position: relative;
}

.icon-ball-core {
  position: absolute;
  inset: 8px;
  background: rgba(0, 242, 255, 0.1);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2;
}

.icon-svg-tech {
  width: 24px;
  height: 24px;
  color: var(--neon-blue);
  filter: drop-shadow(0 0 8px var(--neon-blue));
}

.ball-pulse {
  position: absolute;
  inset: 0;
  border-radius: 50%;
  border: 2px solid var(--neon-blue);
  animation: pulse-ring 2s infinite;
}

@keyframes pulse-ring {
  0% { transform: scale(1); opacity: 1; }
  100% { transform: scale(1.4); opacity: 0; }
}

.step-title-tech {
  font-size: 13px;
  font-weight: bold;
  color: #fff;
  margin: 0 0 8px;
}

.step-desc-tech {
  font-size: 10px;
  color: rgba(255, 255, 255, 0.6);
  line-height: 1.5;
}

.step-corner {
  position: absolute;
  width: 10px;
  height: 10px;
  border: 1px solid var(--neon-blue);
}

.step-corner.tl {
  top: 0;
  left: 0;
  border-right: none;
  border-bottom: none;
}

.step-corner.br {
  bottom: 0;
  right: 0;
  border-left: none;
  border-top: none;
}

.step-particle-line {
  position: absolute;
  top: 50%;
  right: -12px;
  width: 12px;
  height: 2px;
  z-index: 1;
}

.flow-particle {
  position: absolute;
  width: 4px;
  height: 4px;
  background: var(--neon-green);
  border-radius: 50%;
  animation: flow-particle 1.5s linear infinite;
}

@keyframes flow-particle {
  0% { left: 0; opacity: 0; }
  50% { opacity: 1; }
  100% { left: 100%; opacity: 0; }
}

.rules-section-enhanced {
  margin-bottom: 20px;
}

.rules-card-tech {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(0, 242, 255, 0.2);
  border-radius: 12px;
  padding: 20px;
  backdrop-filter: blur(10px);
  position: relative;
}

.rule-item-tech {
  display: flex;
  gap: 12px;
  align-items: flex-start;
  margin-bottom: 15px;
  font-size: 12px;
  color: rgba(255, 255, 255, 0.75);
  line-height: 1.7;
}

.rule-number-dot {
  position: relative;
  flex-shrink: 0;
  width: 24px;
  height: 24px;
  background: rgba(0, 242, 255, 0.15);
  border: 2px solid var(--neon-blue);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: 900;
  color: var(--neon-blue);
}

.dot-glow {
  position: absolute;
  inset: -4px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(0, 242, 255, 0.4), transparent);
  filter: blur(6px);
  animation: breathe 2s infinite;
}

.rule-text-tech {
  flex: 1;
  padding-top: 2px;
}

.view-full-rules-btn-glow {
  width: 100%;
  padding: 14px;
  background: rgba(0, 242, 255, 0.08);
  border: 2px solid var(--neon-blue);
  border-radius: 12px;
  color: var(--neon-blue);
  font-size: 13px;
  font-weight: bold;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  cursor: pointer;
  margin-top: 20px;
  position: relative;
  overflow: hidden;
  transition: all 0.3s;
}

.btn-border-glow {
  position: absolute;
  inset: -2px;
  border-radius: 12px;
  background: linear-gradient(135deg, var(--neon-blue), var(--neon-green));
  opacity: 0;
  transition: opacity 0.3s;
  z-index: -1;
}

.view-full-rules-btn-glow:active {
  transform: scale(0.97);
  background: rgba(0, 242, 255, 0.15);
  box-shadow: 0 0 25px rgba(0, 242, 255, 0.4);
}

.view-full-rules-btn-glow:active .btn-border-glow {
  opacity: 0.3;
}

.friends-section {
  margin-bottom: 20px;
}

.friends-list-tech {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.friend-card-tech {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(0, 242, 255, 0.15);
  border-radius: 12px;
  padding: 15px;
  display: flex;
  align-items: center;
  gap: 12px;
  backdrop-filter: blur(10px);
  transition: all 0.3s;
}

.friend-card-tech:active {
  transform: translateX(4px);
  border-color: var(--neon-blue);
  box-shadow: 0 4px 20px rgba(0, 242, 255, 0.2);
}

.friend-avatar-ball {
  position: relative;
  width: 45px;
  height: 45px;
  flex-shrink: 0;
}

.avatar-ball-inner {
  position: absolute;
  inset: 4px;
  background: rgba(0, 242, 255, 0.1);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2;
}

.avatar-ball-inner .iconfont {
  font-size: 20px;
  color: var(--neon-blue);
}

.avatar-ball-ring {
  position: absolute;
  inset: 0;
  border-radius: 50%;
  border: 2px solid var(--neon-blue);
  border-top-color: transparent;
  animation: spin 3s linear infinite;
}

.friend-info-tech {
  flex: 1;
  min-width: 0;
}

.friend-name-tech {
  font-size: 14px;
  font-weight: bold;
  color: #fff;
  margin-bottom: 6px;
}

.friend-meta-row {
  margin-bottom: 4px;
}

.meta-item {
  font-size: 10px;
  color: rgba(255, 255, 255, 0.5);
}

.friend-data-row {
  display: flex;
  gap: 12px;
  font-size: 11px;
}

.data-item {
  color: rgba(255, 255, 255, 0.6);
}

.data-item em {
  font-style: normal;
  color: var(--neon-blue);
  font-family: 'Rajdhani', sans-serif;
  font-weight: bold;
}

.data-item.reward em {
  color: var(--neon-green);
}

.friend-status-badge {
  position: relative;
  padding: 6px 12px;
  border-radius: 12px;
  font-size: 10px;
  font-weight: bold;
  flex-shrink: 0;
}

.friend-status-badge.active {
  background: rgba(0, 242, 96, 0.15);
  color: var(--neon-green);
  border: 1px solid var(--neon-green);
}

.friend-status-badge.inactive {
  background: rgba(255, 255, 255, 0.05);
  color: rgba(255, 255, 255, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.15);
}

.badge-glow {
  position: absolute;
  inset: -2px;
  border-radius: 12px;
  background: radial-gradient(circle, currentColor, transparent);
  opacity: 0;
  filter: blur(8px);
  transition: opacity 0.3s;
}

.friend-status-badge.active .badge-glow {
  opacity: 0.3;
}

.empty-state-tech {
  text-align: center;
  padding: 50px 20px;
  color: rgba(255, 255, 255, 0.4);
}

.empty-icon-ball {
  width: 60px;
  height: 60px;
  margin: 0 auto 15px;
  background: rgba(0, 242, 255, 0.05);
  border: 2px solid rgba(0, 242, 255, 0.2);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.empty-icon-ball .iconfont {
  font-size: 30px;
  color: rgba(0, 242, 255, 0.4);
}

.empty-state-tech p {
  font-size: 14px;
  margin: 0 0 8px;
}

.empty-tip {
  font-size: 11px;
  color: rgba(255, 255, 255, 0.3);
}

.share-section {
  margin-bottom: 20px;
}

.share-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 15px;
}

.share-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  cursor: pointer;
}

.share-icon-wrapper {
  width: 50px;
  height: 50px;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(0, 242, 255, 0.3);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  transition: all 0.3s;
}

.share-btn:active .share-icon-wrapper {
  transform: scale(0.9);
  border-color: var(--neon-blue);
  box-shadow: 0 0 20px rgba(0, 242, 255, 0.4);
}

.share-icon {
  width: 24px;
  height: 24px;
  color: var(--neon-blue);
  filter: drop-shadow(0 0 4px var(--neon-blue));
}

.share-glow {
  position: absolute;
  inset: -4px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(0, 242, 255, 0.3), transparent);
  opacity: 0;
  transition: opacity 0.3s;
}

.share-btn:active .share-glow {
  opacity: 1;
}

.share-label {
  font-size: 11px;
  color: rgba(255, 255, 255, 0.7);
}

.qr-dialog {
  padding: 20px;
}

.qr-dialog-content {
  background: rgba(10, 10, 18, 0.95);
  border: 1px solid var(--neon-blue);
  border-radius: 16px;
  padding: 30px;
  text-align: center;
  backdrop-filter: blur(20px);
}

.qr-dialog-content h3 {
  font-size: 18px;
  color: #fff;
  margin: 0 0 20px;
}

.qr-code-large {
  width: 200px;
  height: 200px;
  margin: 0 auto 20px;
  background: #fff;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.qr-placeholder-large .iconfont {
  font-size: 80px;
  color: var(--neon-blue);
}

.qr-tip {
  font-size: 12px;
  color: rgba(255, 255, 255, 0.6);
  margin-bottom: 20px;
}

.qr-close-btn {
  width: 100%;
  padding: 12px;
  background: var(--neon-blue);
  border: none;
  border-radius: 12px;
  color: #000;
  font-size: 14px;
  font-weight: bold;
  cursor: pointer;
}

.rules-dialog {
  background: rgba(10, 10, 18, 0.98);
  height: 100%;
  display: flex;
  flex-direction: column;
}

.rules-dialog-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.rules-dialog-header h3 {
  font-size: 18px;
  color: #fff;
  margin: 0;
}

.rules-dialog-header .iconfont {
  font-size: 24px;
  color: var(--neon-blue);
  cursor: pointer;
}

.rules-dialog-content {
  flex: 1;
  overflow-y: auto;
  padding: 20px;
}

.rule-detail {
  margin-bottom: 20px;
}

.rule-detail h4 {
  font-size: 14px;
  color: var(--neon-blue);
  margin: 0 0 10px;
}

.rule-detail p {
  font-size: 13px;
  color: rgba(255, 255, 255, 0.7);
  line-height: 1.6;
  margin: 0;
}
</style>
