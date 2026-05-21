<template>
  <div class="lottery-sign-in">
    <div class="bg-layer">
      <div class="planet-bg"></div>
      <div class="starry-bg">
        <div class="star" v-for="i in 30" :key="i" :style="getStarStyle(i)"></div>
      </div>
      <div class="glow-orb top-center"></div>
    </div>

    <div class="fixed-header">
      <van-nav-bar
        title="Điểm danh hàng ngày"
        left-arrow
        @click-left="goBack"
        class="signin-nav"
      />
      
      <div class="header-content">
        <div class="balance-card">
          <div class="bc-row">
            <div class="bc-label">Số dư hiện tại</div>
            <div class="bc-user">
              <div class="u-avatar">{{ username[0] }}</div>
              <span class="u-name">{{ username }}</span>
            </div>
          </div>
          <div class="bc-value">
            <span class="currency">¥</span>
            <span class="num">{{ balance }}</span>
          </div>
        </div>
        
        <div class="date-badge">
          <div class="db-week">{{ currentWeekday }}</div>
          <div class="db-day">{{ todayDay }}</div>
          <div class="db-month">{{ currentMonthStr }}</div>
        </div>
      </div>
    </div>

    <div class="scroll-body custom-scrollbar">
      <div class="content-wrapper">
        
        <div class="streak-card-premium">
          <div class="scp-header">
            <span class="title">Đã điểm danh liên tục <span class="gold-num">{{ consecutiveDays }}</span> ngày</span>
            <span class="sub">Mục tiêu 7 ngày</span>
          </div>
          
          <div class="streak-track-3d">
            <div class="track-groove">
              <div class="track-fill-bar" :style="{ width: (consecutiveDays / 7 * 100) + '%' }">
                <div class="bar-shine"></div>
                <div class="bar-head-glow"></div>
              </div>
            </div>
            
            <div class="track-nodes">
              <div 
                v-for="i in 7" 
                :key="i" 
                class="node-item"
                :class="{ 'active': i <= consecutiveDays, 'target': i === 7 }"
                :style="{ left: ((i - 1) / 6 * 100) + '%' }"
              >
                <div class="node-dot">
                  <van-icon name="success" v-if="i <= consecutiveDays" />
                </div>
                <div class="node-label" v-if="i === 7">Quà lớn</div>
                <div class="node-label" v-else-if="i === consecutiveDays + 1">Ngày mai</div>
              </div>
            </div>
            
            <div class="end-chest" :class="{ 'open': consecutiveDays >= 7 }">
              <div class="chest-icon-wrapper">
                <van-icon name="gift" class="chest-icon" />
              </div>
              <div class="chest-glow"></div>
            </div>
          </div>
          
          <div class="scp-tip">
            Điểm danh thêm <span class="gold">{{ 7 - consecutiveDays }}</span> ngày nữa để nhận <span class="cyan">Quà bí ẩn lớn</span>
          </div>
        </div>

        <div class="calendar-container">
          <div class="cal-decor-line"></div>
          <div class="cal-tools">
            <div class="month-switcher">
              <span class="ms-text">{{ currentDateStr }}</span>
            </div>
            <div class="signed-stat">
              Tháng này đã điểm danh <span class="gold">{{ totalSignedDays }}</span> lần
            </div>
          </div>

          <div class="cal-body">
            <div class="week-header">
              <div v-for="day in ['日','一','二','三','四','五','六']" :key="day" class="wh-item">{{ day }}</div>
            </div>

            <div class="date-grid">
              <div 
                v-for="(item, index) in calendarDays" 
                :key="index"
                class="date-cell"
                :class="getCalDayClass(item)"
                @click="handleDateClick(item)"
              >
                <div class="cell-inner">
                  <span class="cell-num">{{ item.day }}</span>
                  
                  <div class="status-layer" v-if="item.status === 'signed'">
                    <div class="stamp-signed">
                      <van-icon name="success" />
                    </div>
                  </div>
                  <div class="status-layer" v-else-if="item.status === 'missed'">
                    <div class="tag-miss">补</div>
                  </div>

                  <div class="today-glow" v-if="isToday(item) && !item.isSigned"></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="decor-circle circle-1"></div>
        <div class="decor-circle circle-2"></div>

      </div>
    </div>

    <div class="fixed-footer">
      <button 
        class="btn-sign-gold" 
        :class="{ 'signed': signedIn, 'loading': loading }"
        @click="handleSignIn"
      >
        <template v-if="!signedIn">
          <div class="btn-main-text">Điểm danh ngay</div>
          <div class="btn-sub-text">Nhận thưởng hôm nay</div>
        </template>
        <template v-else>
          <div class="btn-main-text">Hôm nay đã điểm danh</div>
          <div class="btn-sub-text">Ngày mai tiếp tục nhé</div>
        </template>
        
        <div class="btn-shine"></div>
        <div class="particles-box" v-if="showParticles">
          <div class="p-dot" v-for="n in 10" :key="n" :style="getParticleStyle(n)"></div>
        </div>
      </button>
    </div>

    <van-overlay :show="showSuccessModal" z-index="2000" class="success-overlay">
      <div class="success-modal-premium" @click.stop>
        <div class="bg-glow"></div>
        <div class="modal-content">
          <div class="medal-visual">
            <div class="medal-icon-wrapper">
              <van-icon name="medal" />
            </div>
            <div class="medal-shine"></div>
          </div>
          <div class="sm-title">Điểm danh thành công</div>
          <div class="sm-reward">
            <span class="plus">+</span>
            <span class="val">{{ todayRewardPoints }}</span>
            <span class="unit">điểm</span>
          </div>
          <div class="sm-balance">Số dư hiện tại: {{ (parseFloat(balance.replace(/,/g, '')) + todayRewardPoints).toFixed(2) }}</div>
          <button class="sm-btn" @click="showSuccessModal = false">Nhận ngay</button>
        </div>
      </div>
    </van-overlay>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { showToast } from 'vant'
import dayjs from 'dayjs'
import 'dayjs/locale/zh-cn'

dayjs.locale('zh-cn')

const router = useRouter()

const username = ref('Momo_User')
const level = ref(3)
const balance = ref('8,888.00')
const signedIn = ref(false)
const loading = ref(false)
const showSuccessModal = ref(false)
const showParticles = ref(false)
const todayRewardPoints = ref(20)

const now = dayjs()
const currentDateStr = now.format('YYYY年MM月')
const currentWeekday = now.format('周dd')
const currentMonthStr = now.format('MM月')
const todayDay = now.date()
const daysInMonth = now.daysInMonth()
const firstDayWeekday = now.startOf('month').day() // 0-6

const calendarDays = ref([])
const consecutiveDays = ref(3) 
const totalSignedDays = ref(12)

const generateCalendar = () => {
  const days = []
  for (let i = 0; i < firstDayWeekday; i++) {
    days.push({ day: '', type: 'empty' })
  }
  
  for (let i = 1; i <= daysInMonth; i++) {
    let status = 'future'
    let isSigned = false
    
    if (i < todayDay) {
      const rand = Math.random()
      if (rand > 0.2) { 
        status = 'signed'
        isSigned = true
      } else {
        status = 'missed'
      }
    } else if (i === todayDay) {
      status = signedIn.value ? 'signed' : 'today'
      isSigned = signedIn.value
    }
    
    days.push({ day: i, type: 'day', status, isSigned })
  }
  calendarDays.value = days
}

const goBack = () => router.go(-1)
const isToday = (item) => item.day === todayDay

const getCalDayClass = (item) => {
  if (item.type === 'empty') return 'empty'
  const classes = []
  if (item.day === todayDay) classes.push('is-today')
  classes.push(`status-${item.status}`)
  return classes.join(' ')
}

const handleDateClick = (item) => {
  if (item.type !== 'day') return
  if (item.day === todayDay && !signedIn.value) {
    handleSignIn()
  } else if (item.status === 'missed') {
    showToast({ message: 'Chức năng điểm danh bù sắp ra mắt', icon: 'clock-o' })
  }
}

const handleSignIn = () => {
  if (signedIn.value) return
  loading.value = true
  
  setTimeout(() => {
    loading.value = false
    signedIn.value = true
    const todayItem = calendarDays.value.find(d => d.day === todayDay)
    if (todayItem) {
      todayItem.status = 'signed'
      todayItem.isSigned = true
    }
    totalSignedDays.value += 1
    consecutiveDays.value += 1
    
    showParticles.value = true
    setTimeout(() => {
      showSuccessModal.value = true
      showParticles.value = false
    }, 500)
    
    if (navigator.vibrate) navigator.vibrate([50, 30, 50])
  }, 800)
}

const getStarStyle = (i) => ({
  top: Math.random() * 100 + '%',
  left: Math.random() * 100 + '%',
  animationDelay: Math.random() * 4 + 's',
  opacity: Math.random() * 0.7 + 0.3,
  transform: `scale(${Math.random() * 0.5 + 0.5})`
})

const getParticleStyle = (n) => {
  const angle = Math.random() * 360
  const dist = 60 + Math.random() * 60
  return {
    '--tx': `${Math.cos(angle * Math.PI / 180) * dist}px`,
    '--ty': `${Math.sin(angle * Math.PI / 180) * dist}px`,
    animationDelay: `${n * 0.05}s`
  }
}

onMounted(() => {
  generateCalendar()
})
</script>

<style scoped>
.lottery-sign-in {
  height: 100vh;
  background: #0B0E15;
  background: radial-gradient(circle at 50% 0%, #1a1f35 0%, #050508 100%);
  color: #fff; font-family: 'PingFang SC', sans-serif;
  position: relative; overflow: hidden;
  display: flex; flex-direction: column;
}

.bg-layer { position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 0; }
.planet-bg {
  position: absolute; top: -10%; right: -20%; width: 80%; padding-bottom: 80%;
  background: radial-gradient(circle, #3b82f6 0%, transparent 70%);
  opacity: 0.1; border-radius: 50%; filter: blur(60px);
}
.starry-bg { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }
.star {
  position: absolute; width: 2px; height: 2px; background: #fff; border-radius: 50%;
  animation: twinkle 4s infinite ease-in-out;
}
@keyframes twinkle { 0%, 100% { opacity: 0.2; } 50% { opacity: 1; } }

.fixed-header { z-index: 10; flex-shrink: 0; background: rgba(11,14,21,0.3); backdrop-filter: blur(5px); }
.signin-nav { --van-nav-bar-background: transparent; --van-nav-bar-title-text-color: #fff; --van-nav-bar-icon-color: #fff; --van-border-color: transparent; }

.header-content { padding: 0 20px 15px; display: flex; gap: 15px; }
.balance-card {
  flex: 1; background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.02) 100%);
  border: 1px solid rgba(255,255,255,0.05);
  border-radius: 16px; padding: 15px;
  display: flex; flex-direction: column; justify-content: space-between;
}
.bc-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
.bc-label { font-size: 12px; color: #94a3b8; }
.bc-user { display: flex; align-items: center; gap: 6px; background: rgba(0,0,0,0.3); padding: 2px 8px 2px 2px; border-radius: 12px; }
.u-avatar { width: 16px; height: 16px; background: #EAC26E; color: #000; border-radius: 50%; font-size: 10px; display: flex; align-items: center; justify-content: center; font-weight: bold; }
.u-name { font-size: 10px; color: #cbd5e1; }

.bc-value { display: flex; align-items: baseline; }
.currency { font-size: 16px; color: #EAC26E; margin-right: 4px; }
.num { 
  font-size: 28px; font-weight: bold; color: #fff; 
  background: linear-gradient(90deg, #fff, #EAC26E); 
  -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; 
  font-family: 'DIN Alternate', sans-serif;
}

.date-badge {
  width: 80px; background: rgba(255,255,255,0.05); border-radius: 16px;
  border: 1px solid rgba(255,255,255,0.1);
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  padding: 10px 0;
}
.db-week { font-size: 10px; color: #EAC26E; margin-bottom: 2px; }
.db-day { font-size: 28px; font-weight: bold; color: #fff; line-height: 1; }
.db-month { font-size: 10px; color: #94a3b8; margin-top: 2px; }

.scroll-body { flex: 1; overflow-y: auto; position: relative; z-index: 1; padding-top: 10px; }
.content-wrapper { padding: 0 20px 20px; }

.streak-card-premium {
  background: rgba(20, 20, 30, 0.6); border-radius: 20px;
  border: 1px solid rgba(255,255,255,0.08);
  padding: 20px; position: relative; overflow: visible;
  box-shadow: 0 8px 24px rgba(0,0,0,0.2); backdrop-filter: blur(10px);
  margin-bottom: 25px;
}
.scp-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
.scp-header .title { font-size: 15px; font-weight: bold; color: #cbd5e1; }
.scp-header .gold-num { color: #EAC26E; font-size: 18px; font-family: 'DIN Alternate'; }
.scp-header .sub { font-size: 12px; color: #64748b; }

.streak-track-3d { position: relative; height: 40px; margin-bottom: 15px; padding: 0 20px; }
.track-groove {
  position: absolute; top: 16px; left: 0; width: 100%; height: 8px;
  background: #0f172a; border-radius: 4px;
  box-shadow: inset 0 2px 4px rgba(0,0,0,0.5), 0 1px 0 rgba(255,255,255,0.1);
}
.track-fill-bar {
  height: 100%; background: linear-gradient(90deg, #EAC26E, #F59E0B);
  border-radius: 4px; position: relative; box-shadow: 0 0 10px rgba(234, 194, 110, 0.4);
  transition: width 0.5s ease-out;
}
.bar-shine {
  position: absolute; top: 0; left: 0; width: 100%; height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.6), transparent);
  opacity: 0.6; animation: shine 2s infinite;
}
.bar-head-glow {
  position: absolute; right: -4px; top: 50%; transform: translateY(-50%);
  width: 12px; height: 12px; background: #fff; border-radius: 50%;
  box-shadow: 0 0 10px #fff, 0 0 20px #EAC26E;
}

.track-nodes { position: absolute; top: 0; left: 20px; right: 20px; height: 100%; pointer-events: none; }
.node-item { position: absolute; top: 50%; transform: translate(-50%, -50%); width: 20px; display: flex; flex-direction: column; align-items: center; }
.node-dot {
  width: 16px; height: 16px; border-radius: 50%; background: #334155;
  border: 2px solid #1e293b; box-shadow: 0 2px 4px rgba(0,0,0,0.3);
  display: flex; align-items: center; justify-content: center; font-size: 10px; color: #fff;
  transition: all 0.3s; z-index: 2;
}
.node-item.active .node-dot { background: #EAC26E; border-color: #fff; color: #2c1802; box-shadow: 0 0 8px rgba(234, 194, 110, 0.6); }
.node-item.target .node-dot { opacity: 0; } 

.node-label { position: absolute; top: 24px; font-size: 10px; color: #94a3b8; white-space: nowrap; font-weight: bold; text-shadow: 0 1px 2px rgba(0,0,0,0.5); }
.node-item.active .node-label { color: #EAC26E; }

.end-chest {
  position: absolute; right: 0; top: 50%; transform: translate(50%, -50%);
  width: 40px; height: 40px; z-index: 3;
}
.chest-glow {
  position: absolute; inset: -10px; background: radial-gradient(circle, rgba(234,194,110,0.4) 0%, transparent 70%);
  opacity: 0; transition: opacity 0.3s; animation: pulse 2s infinite;
}
.end-chest.open .chest-glow { opacity: 1; }

.scp-tip { text-align: center; font-size: 12px; color: #94a3b8; margin-top: 5px; }
.cyan { color: #22D3EE; font-weight: bold; }

.calendar-container {
  background: rgba(20, 20, 30, 0.6); border-radius: 20px;
  border: 1px solid rgba(255,255,255,0.08);
  padding: 20px; position: relative; overflow: hidden;
  box-shadow: 0 8px 24px rgba(0,0,0,0.2); backdrop-filter: blur(10px);
}
.cal-decor-line { position: absolute; top: 0; left: 0; width: 100%; height: 2px; background: linear-gradient(90deg, transparent, #EAC26E, transparent); opacity: 0.5; }

.cal-tools { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
.ms-text { font-size: 16px; font-weight: bold; color: #fff; }
.signed-stat { font-size: 12px; color: #94a3b8; }
.gold { color: #EAC26E; font-weight: bold; }

.week-header { display: grid; grid-template-columns: repeat(7, 1fr); margin-bottom: 12px; }
.wh-item { text-align: center; font-size: 12px; color: #64748b; }

.date-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 8px; }
.date-cell { aspect-ratio: 1; position: relative; }
.cell-inner {
  width: 100%; height: 100%; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  position: relative; transition: all 0.3s; background: rgba(255,255,255,0.02);
}
.cell-num { font-size: 13px; color: #94a3b8; font-weight: 500; z-index: 1; }

.date-cell.empty { pointer-events: none; }
.date-cell.is-today .cell-inner { background: rgba(234, 194, 110, 0.1); border: 1px solid rgba(234, 194, 110, 0.3); }
.date-cell.is-today .cell-num { color: #EAC26E; font-weight: bold; }

.status-layer { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; z-index: 2; }
.stamp-signed {
  width: 28px; height: 28px; border-radius: 50%;
  background: linear-gradient(135deg, #EAC26E, #B45309);
  color: #fff; display: flex; align-items: center; justify-content: center;
  font-size: 14px; box-shadow: 0 2px 5px rgba(0,0,0,0.3);
}
.tag-miss { font-size: 10px; color: #ef4444; background: rgba(239, 68, 68, 0.15); padding: 1px 4px; border-radius: 4px; }

.today-glow { position: absolute; inset: 0; border-radius: 10px; box-shadow: 0 0 10px rgba(234, 194, 110, 0.2); animation: pulse-border 2s infinite; pointer-events: none; }

.decor-coin { position: absolute; width: 40px; height: 40px; background: url('/assets/images/ui/icons/coin.png') no-repeat center/contain; opacity: 0.1; pointer-events: none; }
.coin-1 { top: 100px; right: -10px; transform: rotate(15deg); }
.coin-2 { bottom: 20px; left: -10px; transform: rotate(-15deg) scale(0.8); }

.chest-icon-wrapper {
  width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;
  transition: transform 0.3s;
}
.chest-icon {
  font-size: 24px; color: #F59E0B;
  filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
}
.end-chest.open .chest-icon-wrapper { transform: scale(1.2); }
.end-chest.open .chest-icon { color: #EAC26E; filter: drop-shadow(0 0 10px rgba(234, 194, 110, 0.6)); }

.medal-icon-wrapper {
  font-size: 60px; color: #EAC26E;
  display: flex; align-items: center; justify-content: center;
  position: relative; z-index: 2;
  filter: drop-shadow(0 4px 8px rgba(234, 194, 110, 0.3));
}

.decor-circle {
  position: absolute; border-radius: 50%;
  background: radial-gradient(circle, rgba(234,194,110,0.1) 0%, transparent 70%);
  pointer-events: none;
}
.circle-1 { width: 100px; height: 100px; top: 80px; right: -20px; opacity: 0.6; }
.circle-2 { width: 60px; height: 60px; bottom: 40px; left: -10px; opacity: 0.4; }

.fixed-footer {
  flex-shrink: 0; padding: 15px 24px; padding-bottom: calc(15px + env(safe-area-inset-bottom));
  background: rgba(11, 14, 21, 0.95); backdrop-filter: blur(10px);
  border-top: 1px solid rgba(255,255,255,0.05);
  z-index: 20; display: flex; justify-content: center;
}
.btn-sign-gold {
  width: 100%; height: 54px; border-radius: 27px; border: none;
  background: linear-gradient(180deg, #F0C930 0%, #D19611 100%);
  color: #2c1802; display: flex; flex-direction: column; align-items: center; justify-content: center;
  box-shadow: 0 6px 20px rgba(217, 119, 6, 0.3), inset 0 1px 1px rgba(255,255,255,0.4);
  position: relative; overflow: visible; transition: all 0.2s;
}
.btn-sign-gold:active { transform: scale(0.98); }
.btn-sign-gold.signed { background: #334155; color: #94a3b8; box-shadow: none; }
.btn-main-text { font-size: 17px; font-weight: 900; letter-spacing: 1px; line-height: 1.1; }
.btn-sub-text { font-size: 10px; opacity: 0.8; font-weight: 600; }

.btn-shine {
  position: absolute; top: 0; left: -100%; width: 50%; height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
  transform: skewX(-20deg); animation: shine 3s infinite; pointer-events: none;
}
.btn-sign-gold.signed .btn-shine { display: none; }

.success-overlay {
  display: flex; align-items: center; justify-content: center;
}
.success-modal-premium {
  width: 280px; background: #1e1b4b; border-radius: 24px; padding: 30px 20px;
  text-align: center; position: relative; box-shadow: 0 20px 50px rgba(0,0,0,0.7);
  border: 1px solid rgba(234, 194, 110, 0.2); overflow: hidden;
  animation: pop-up 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.bg-glow { position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(234,194,110,0.15) 0%, transparent 60%); animation: rotate 10s linear infinite; pointer-events: none; }
.medal-visual { position: relative; width: 80px; height: 80px; margin: 0 auto 15px; }
.medal-shine { position: absolute; inset: -20px; background: radial-gradient(circle, rgba(234,194,110,0.6) 0%, transparent 70%); z-index: 1; animation: pulse 2s infinite; }
.sm-title { font-size: 22px; font-weight: 900; color: #EAC26E; margin-bottom: 10px; }
.sm-reward { font-size: 36px; font-weight: bold; color: #fff; display: flex; align-items: baseline; justify-content: center; gap: 2px; }
.sm-reward .plus { font-size: 24px; color: #EAC26E; }
.sm-reward .unit { font-size: 12px; color: #94a3b8; font-weight: normal; }
.sm-balance { font-size: 12px; color: #64748b; margin-top: 4px; margin-bottom: 24px; font-family: monospace; }
.sm-btn { width: 100%; height: 44px; border-radius: 22px; border: none; background: linear-gradient(90deg, #EAC26E, #D97706); color: #2c1802; font-weight: bold; font-size: 15px; box-shadow: 0 4px 12px rgba(217, 119, 6, 0.3); }

@keyframes bounce { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-5px); } }
@keyframes pulse-border { 0% { box-shadow: 0 0 0 0 rgba(234, 194, 110, 0.4); } 70% { box-shadow: 0 0 0 6px rgba(234, 194, 110, 0); } 100% { box-shadow: 0 0 0 0 rgba(234, 194, 110, 0); } }
@keyframes shine { 0%, 50% { left: 150%; } 100% { left: 150%; } }
@keyframes pop-up { from { opacity: 0; transform: scale(0.8); } to { opacity: 1; transform: scale(1); } }
@keyframes rotate { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

.scale-fade-enter-active { transition: all 0.3s ease; }
.scale-fade-enter-from { opacity: 0; transform: scale(0.5); }
.particles-box { position: absolute; inset: 0; pointer-events: none; }
.p-dot { position: absolute; width: 6px; height: 6px; background: #FFD700; border-radius: 50%; left: 50%; top: 50%; animation: fly-out 0.5s ease-out forwards; }
@keyframes fly-out { 100% { transform: translate(var(--tx), var(--ty)) scale(0); opacity: 0; } }
</style>
