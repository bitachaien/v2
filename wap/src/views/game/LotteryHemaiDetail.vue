<template>
  <transition name="slide-up">
    <div class="hemai-detail-page" v-if="isVisible">
      <van-nav-bar
        title="合买详情"
        left-arrow
        fixed
        placeholder
        @click-left="close"
        class="detail-nav"
      >
        <template #right>
          <van-icon name="share-o" size="20" color="#EAC26E" @click="handleShare" />
        </template>
      </van-nav-bar>

      <div class="detail-content custom-scrollbar">
        <div v-if="loadingDetail && !displayDetail.id" class="skeleton-wrapper">
          <div class="glass-card"><van-skeleton title avatar :row="2" /></div>
          <div class="glass-card"><van-skeleton title :row="4" /></div>
          <div class="glass-card"><van-skeleton title :row="3" /></div>
        </div>

        <div v-else>
          <div class="glass-card header-card">
          <div class="card-top">
            <div class="lottery-icon-box">
              <img :src="getLotteryIcon(displayDetail.cptitle)" class="lottery-icon" />
            </div>
            <div class="lottery-main">
              <div class="l-title">{{ displayDetail.cptitle }}</div>
              <div class="l-expect">第 {{ displayDetail.expect }} 期</div>
            </div>
            <div class="status-tag" :class="getStatusClass(displayDetail.isdraw)">
              {{ displayDetail.isdraw }}
            </div>
          </div>
          <div class="divider"></div>
          <div class="initiator-row">
            <div class="user-info">
              <div class="avatar-large" :style="{ background: getAvatarColor(displayDetail.username) }">
                {{ displayDetail.username?.[0]?.toUpperCase() || 'U' }}
                <div class="level-badge-abs">V{{ displayDetail.level || 1 }}</div>
              </div>
              <div class="user-meta">
                <div class="nickname-row">
                  <span class="nickname">{{ displayDetail.username || '匿名用户' }}</span>
                  <span class="win-tag-small" v-if="displayDetail.winRate">近5红{{ displayDetail.winRate }}</span>
                  <span class="commission-tag" v-if="displayDetail.commission > 0">提成{{ displayDetail.commission }}%</span>
                </div>
                <div class="slogan-text" @click="toggleSlogan">
                   {{ displayDetail.content || '暂无合买宣言，大家一起中大奖！' }}
                   <van-icon :name="isSloganExpanded ? 'arrow-up' : 'arrow-down'" class="expand-icon" />
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="glass-card core-data-card">
          <div class="left-chart">
            <div class="chart-container">
              <div class="circle-progress-ring" :style="progressRingStyle"></div>
              <div class="inner-circle">
                <span class="percent">{{ displayDetail.progress }}<small>%</small></span>
                <span class="label">保底{{ displayDetail.baodi || '0' }}%</span>
              </div>
            </div>
          </div>
          <div class="right-data">
            <div class="data-grid-2">
              <div class="data-item">
                <span class="label">方案金额</span>
                <span class="value gold">{{ displayDetail.amount }}</span>
              </div>
              <div class="data-item">
                <span class="label">每份金额</span>
                <span class="value">{{ displayDetail.hemaipic }}</span>
              </div>
              <div class="data-item">
                <span class="label">剩余份数</span>
                <span class="value red">{{ displayDetail.buyhave }}</span>
              </div>
              <div class="data-item">
                <span class="label">截止时间</span>
                <van-count-down :time="getRemainingTime(displayDetail.endtime)" format="HH:mm:ss" class="time-count" />
              </div>
            </div>
          </div>
        </div>

        <div class="glass-card info-card">
          <div class="card-header" @click="showCode = !showCode">
            <span class="title">方案内容</span>
            <span class="subtitle">
              {{ showCode ? '收起' : '展开' }}
              <van-icon :name="showCode ? 'arrow-up' : 'arrow-down'" />
            </span>
          </div>
          <transition name="fade-slide">
            <div class="code-content" v-if="showCode">
               <div class="play-type-row">
                 <span class="pt-label">玩法：</span>
                 <span class="pt-val">{{ displayDetail.playtitle }}</span>
               </div>
               <div class="code-box">
                 <div class="code-scroll" v-html="formatCode(displayDetail.tzcode)"></div>
               </div>
            </div>
          </transition>
          <div class="draw-result" v-if="displayDetail.opencode">
            <div class="result-header">
              <span>开奖号码</span>
              <span class="win-status" v-if="displayDetail.isWin">中奖 {{ displayDetail.winAmount }}元</span>
            </div>
            <div class="ball-list">
              <span v-for="(n, i) in displayDetail.opencode.split(',')" :key="i" class="ball">{{ n }}</span>
            </div>
          </div>
        </div>

        <div class="glass-card user-list-card">
          <div class="card-header">
            <div class="ch-left">
              <span class="title">参与用户</span>
              <span class="count-badge">{{ userList.length }}</span>
            </div>
            <span class="subtitle">共认购 <span class="gold">{{ totalBought }}</span> 份</span>
          </div>
          
          <div class="user-list-body">
            <transition-group name="list-anim">
              <div class="user-row-pro" v-for="(user, idx) in userList" :key="idx">
                <div class="u-idx">{{ idx + 1 }}</div>
                <div class="u-avatar" :style="{ background: getAvatarColor(user.username) }">
                  {{ user.username?.[0]?.toUpperCase() }}
                </div>
                <div class="u-info">
                  <div class="u-top">
                    <span class="u-name">{{ maskName(user.username) }}</span>
                    <span class="u-num">认购 <span class="highlight">{{ user.num }}</span> 份</span>
                  </div>
                  <div class="u-bottom">
                    <span class="u-time">{{ formatTime(user.time) }}</span>
                    <span class="u-money">{{ user.amount }}元</span>
                  </div>
                </div>
                <div class="u-medal" v-if="idx < 3">
                  <span class="rank-dot" :class="`rank-${idx+1}`"></span>
                </div>
              </div>
            </transition-group>
          </div>
        </div>
        </div>
      </div>

      <div class="bottom-action-bar-pro">
        <div class="bar-left">
          <div class="remain-info">
            <span class="lbl">剩余</span>
            <span class="val">{{ displayDetail.buyhave }}</span>
            <span class="unit">份</span>
          </div>
        </div>
        
        <div class="bar-right">
          <div class="stepper-capsule">
            <div class="btn-step" @click="updateBuyNum(-1)" :class="{ disabled: buyNum <= 1 }">
              <van-icon name="minus" />
            </div>
            <div class="input-step" @click="showKeyboard = true">{{ buyNum }}</div>
            <div class="btn-step" @click="updateBuyNum(1)" :class="{ disabled: buyNum >= displayDetail.buyhave }">
              <van-icon name="plus" />
            </div>
          </div>
          
          <button 
            class="buy-btn-large" 
            :class="{ disabled: !canBuy }" 
            @click="handleBuy"
          >
            <span class="btn-text">{{ getBtnText() }}</span>
            <span class="btn-sub" v-if="canBuy">{{ (buyNum * parseFloat(displayDetail.hemaipic || 0)).toFixed(2) }}元</span>
          </button>
        </div>
      </div>

      <van-number-keyboard
        v-model="buyNumStr"
        :show="showKeyboard"
        @blur="showKeyboard = false"
        :maxlength="4"
        theme="custom"
        close-button-text="完成"
        @close="showKeyboard = false"
      />

    </div>
  </transition>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { showToast, showSuccessToast, showFailToast, showLoadingToast, closeToast } from 'vant'
import { getGameIconPath } from '@/config/gameConfig'
import { hemaiApi } from '@/api/hemai'

const route = useRoute()
const router = useRouter()

const props = defineProps({
  visible: { type: Boolean, default: false },
  detail: { type: Object, default: () => ({}) },
  isComponent: { type: Boolean, default: false }
})

const emit = defineEmits(['update:visible', 'buy'])

const showCode = ref(true)
const showKeyboard = ref(false)
const buyNumStr = ref('')
const isSloganExpanded = ref(false)
const fullDetail = ref({})
const userList = ref([])
const loadingDetail = ref(false)

const isRouteMode = computed(() => !props.isComponent && route.name === 'LotteryHemaiDetail')

const isVisible = computed(() => {
  if (isRouteMode.value) return true
  return props.visible
})

const displayDetail = computed(() => {
  return { ...props.detail, ...fullDetail.value }
})

const buyNum = computed(() => {
  const val = parseInt(buyNumStr.value)
  return isNaN(val) || val < 1 ? 1 : val
})

const canBuy = computed(() => {
  const d = displayDetail.value
  return d.isdraw === '进行中' && d.buyhave > 0
})

const totalBought = computed(() => {
  const d = displayDetail.value
  if (d.buytotal !== undefined && d.buyhave !== undefined) {
    return d.buytotal - d.buyhave
  }
  return userList.value.reduce((acc, u) => acc + (parseInt(u.num) || 0), 0)
})

const progressRingStyle = computed(() => {
  const p = displayDetail.value.progress || 0
  const color = p > 80 ? '#10b981' : '#EAC26E'
  return {
    background: `conic-gradient(${color} ${p}%, rgba(255,255,255,0.1) ${p}% 100%)`
  }
})

watch(() => props.visible, (val) => {
  if (val) initData()
})

onMounted(() => {
  if (isRouteMode.value) {
    if (route.query.id) {
      fullDetail.value.id = route.query.id
      initData()
    }
  }
})

const initData = () => {
  buyNumStr.value = ''
  showCode.value = true
  loadFullData()
}

const loadFullData = async () => {
  const id = fullDetail.value.id || props.detail.id
  if (!id) return
  
  loadingDetail.value = true
  try {
    const [detailRes, usersRes] = await Promise.all([
      hemaiApi.getDetail(id),
      hemaiApi.getUsers(id)
    ])
    
    if (detailRes.code === 0 && detailRes.data) {
      fullDetail.value = { ...fullDetail.value, ...detailRes.data }
    }
    
    if (usersRes.code === 0 && usersRes.data?.list) {
      userList.value = usersRes.data.list
    }
  } catch (e) {
    showToast('加载详情失败')
  } finally {
    loadingDetail.value = false
  }
}

const close = () => {
  if (isRouteMode.value) {
    router.back()
  } else {
    emit('update:visible', false)
  }
}

const getLotteryIcon = (name) => getGameIconPath(name)

const getStatusClass = (status) => {
  if (status === '进行中') return 'status-green'
  if (status === '已截止') return 'status-gray'
  if (status === '已开奖') return 'status-red'
  return ''
}

const getAvatarColor = (name) => {
  const colors = ['#3b82f6', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981']
  let hash = 0
  if (!name) return colors[0]
  for (let i = 0; i < name.length; i++) {
    hash = name.charCodeAt(i) + ((hash << 5) - hash)
  }
  return colors[Math.abs(hash) % colors.length]
}

const maskName = (name) => name ? name.substr(0, 2) + '***' : '***'

const formatTime = (ts) => {
  if (!ts) return '--'
  const date = new Date(parseInt(ts))
  const m = (date.getMonth() + 1).toString().padStart(2, '0')
  const d = date.getDate().toString().padStart(2, '0')
  const h = date.getHours().toString().padStart(2, '0')
  const min = date.getMinutes().toString().padStart(2, '0')
  return `${m}-${d} ${h}:${min}`
}

const getRemainingTime = (endtime) => {
  if (!endtime) return 0
  const now = Date.now()
  const diff = parseInt(endtime) - now
  return diff > 0 ? diff : 0
}

const toggleSlogan = () => {
  isSloganExpanded.value = !isSloganExpanded.value
}

const formatCode = (code) => {
  if (!code) return '暂无号码'
  return code.split('|').map((line, idx) => {
    return `<div class="code-line"><span class="line-idx">[${idx+1}]</span> ${line}</div>`
  }).join('')
}

const updateBuyNum = (delta) => {
  let newVal = buyNum.value + delta
  const max = displayDetail.value.buyhave || 0
  
  if (newVal < 1) newVal = 1
  if (newVal > max) newVal = max
  
  buyNumStr.value = newVal.toString()
}

const getBtnText = () => {
  const d = displayDetail.value
  if (d.isdraw === '已截止') return '已截止'
  if (d.isdraw === '已开奖') return '已开奖'
  if (d.buyhave <= 0) return '已满员'
  return '立即认购'
}

const handleBuy = async () => {
  if (!canBuy.value) return
  
  const num = buyNum.value
  const id = displayDetail.value.id
  
  showLoadingToast({ message: '提交中...', forbidClick: true })
  
  try {
    const res = await hemaiApi.buy({ id, num })
    
    if (res.code === 0) {
      closeToast()
      showSuccessToast('认购成功')
      emit('buy', { id, num })
      loadFullData()
    } else {
      closeToast()
      showFailToast(res.message || '认购失败')
    }
  } catch (e) {
    closeToast()
    showFailToast(e.message || '网络错误')
  }
}

const handleShare = () => {
  const url = window.location.href
  if (navigator.clipboard) {
    navigator.clipboard.writeText(url).then(() => {
      showToast('链接已复制，快去分享吧')
    }).catch(() => {
      showToast('复制失败，请手动复制')
    })
  } else {
    const input = document.createElement('input')
    input.value = url
    document.body.appendChild(input)
    input.select()
    document.execCommand('copy')
    document.body.removeChild(input)
    showToast('链接已复制')
  }
}
</script>

<style scoped>

.slide-up-enter-active, .slide-up-leave-active { transition: transform 0.3s cubic-bezier(0.25, 1, 0.5, 1), opacity 0.3s; }
.slide-up-enter-from, .slide-up-leave-to { transform: translateY(100%); opacity: 0; }

.fade-slide-enter-active, .fade-slide-leave-active { transition: all 0.3s ease; max-height: 200px; opacity: 1; }
.fade-slide-enter-from, .fade-slide-leave-to { max-height: 0; opacity: 0; transform: translateY(-10px); }

.list-anim-enter-active { transition: all 0.4s ease; }
.list-anim-enter-from { opacity: 0; transform: translateX(-20px); }

.hemai-detail-page {
  position: fixed; top: 0; left: 0; width: 100%; height: 100%;
  background: #0B0E15;
  background: linear-gradient(180deg, #161623 0%, #000000 100%);
  z-index: 2000;
  display: flex; flex-direction: column;
  color: #fff; font-family: 'PingFang SC', sans-serif;
}

.detail-nav {
  --van-nav-bar-background: rgba(22, 22, 35, 0.9);
  --van-nav-bar-title-text-color: #fff;
  --van-nav-bar-icon-color: #fff;
  --van-border-color: rgba(255,255,255,0.05);
  backdrop-filter: blur(10px);
}

.detail-content { flex: 1; overflow-y: auto; padding: 15px; padding-bottom: 100px; }

.glass-card {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 16px; padding: 15px; margin-bottom: 15px;
  box-shadow: 0 8px 24px rgba(0,0,0,0.2);
  position: relative; overflow: hidden;
}

.card-top { display: flex; align-items: center; gap: 12px; }
.lottery-icon-box {
  width: 44px; height: 44px; background: rgba(255,255,255,0.05);
  border-radius: 10px; padding: 4px;
}
.lottery-icon { width: 100%; height: 100%; }
.lottery-main { flex: 1; }
.l-title { font-size: 17px; font-weight: bold; color: #fff; margin-bottom: 4px; }
.l-expect { font-size: 11px; color: #94a3b8; }
.status-tag { font-size: 11px; padding: 3px 8px; border-radius: 4px; font-weight: bold; }
.status-green { background: rgba(16, 185, 129, 0.15); color: #10b981; }
.status-red { background: rgba(239, 68, 68, 0.15); color: #ef4444; }
.status-gray { background: rgba(148, 163, 184, 0.15); color: #94a3b8; }

.divider { height: 1px; background: rgba(255,255,255,0.04); margin: 12px 0; }

.initiator-row { display: flex; align-items: flex-start; }
.user-info { display: flex; gap: 12px; width: 100%; }
.avatar-large {
  width: 40px; height: 40px; border-radius: 50%; 
  display: flex; align-items: center; justify-content: center;
  font-size: 16px; font-weight: bold; position: relative;
  box-shadow: 0 4px 10px rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1);
}
.level-badge-abs {
  position: absolute; bottom: -2px; right: -4px;
  background: #000; color: #EAC26E; font-size: 9px;
  padding: 0 4px; border-radius: 4px; border: 1px solid #EAC26E;
}

.user-meta { flex: 1; display: flex; flex-direction: column; justify-content: center; }
.nickname-row { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; }
.nickname { font-size: 14px; font-weight: bold; color: #fff; }
.win-tag-small { font-size: 9px; color: #f87171; background: rgba(239, 68, 68, 0.1); padding: 0 4px; border-radius: 2px; }
.commission-tag { font-size: 9px; color: #EAC26E; border: 1px solid #EAC26E; padding: 0 4px; border-radius: 2px; margin-left: 4px; }

.slogan-text { font-size: 12px; color: #94a3b8; display: flex; align-items: center; gap: 4px; cursor: pointer; line-height: 1.4; }
.expand-icon { font-size: 10px; }

.core-data-card { display: flex; gap: 15px; align-items: center; padding: 20px 15px; }
.left-chart { position: relative; width: 80px; height: 80px; flex-shrink: 0; }
.chart-container { position: relative; width: 100%; height: 100%; }
.circle-progress-ring {
  width: 100%; height: 100%; border-radius: 50%;
  position: absolute; inset: 0;
  mask: radial-gradient(transparent 58%, black 60%);
  -webkit-mask: radial-gradient(transparent 58%, black 60%);
  animation: spin-slow 10s linear infinite;
}
.inner-circle { 
  position: absolute; inset: 0; display: flex; flex-direction: column; 
  align-items: center; justify-content: center; 
}
.percent { font-size: 18px; font-weight: bold; color: #fff; text-shadow: 0 0 10px rgba(234, 194, 110, 0.3); }
.percent small { font-size: 10px; }
.inner-circle .label { font-size: 9px; color: #64748b; }

.right-data { flex: 1; }
.data-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.data-item { display: flex; flex-direction: column; gap: 2px; }
.data-item .label { font-size: 10px; color: #64748b; }
.data-item .value { font-size: 13px; font-weight: 600; color: #e2e8f0; font-family: monospace; }
.data-item .gold { color: #EAC26E; }
.data-item .red { color: #ef4444; font-size: 14px; }
.time-count { color: #ef4444; font-size: 13px; font-weight: bold; }

.info-card .card-header { 
  display: flex; justify-content: space-between; align-items: center;
  padding-bottom: 10px; border-bottom: 1px solid rgba(255,255,255,0.05);
  margin-bottom: 10px;
}
.title { font-size: 14px; font-weight: bold; color: #fff; }
.subtitle { font-size: 11px; color: #64748b; display: flex; align-items: center; gap: 4px; }

.play-type-row { margin-top: 10px; margin-bottom: 8px; font-size: 12px; display: flex; }
.pt-label { color: #64748b; }
.pt-val { color: #e2e8f0; }

.code-box { background: rgba(0,0,0,0.2); padding: 8px; border-radius: 6px; }
.code-scroll { 
  max-height: 100px; overflow-y: auto; font-family: monospace; 
  display: flex; flex-wrap: wrap; gap: 6px;
}
:deep(.code-tag) {
  background: rgba(255,255,255,0.05); padding: 2px 6px; border-radius: 4px;
  color: #EAC26E; font-size: 13px;
}

.draw-result { margin-top: 15px; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 12px; }
.result-header { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 12px; color: #94a3b8; }
.win-status { color: #ef4444; font-weight: bold; }
.ball-list { display: flex; gap: 6px; }
.ball { 
  width: 24px; height: 24px; background: linear-gradient(135deg, #ef4444, #b91c1c);
  border-radius: 50%; display: flex; align-items: center; justify-content: center;
  font-weight: bold; font-size: 12px; box-shadow: 0 2px 6px rgba(239, 68, 68, 0.4);
}

.user-list-card .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.ch-left { display: flex; align-items: center; gap: 6px; }
.count-badge { 
  background: rgba(255,255,255,0.1); padding: 0 6px; border-radius: 10px; 
  font-size: 10px; color: #cbd5e1;
}
.gold { color: #EAC26E; }

.user-list-body { display: flex; flex-direction: column; gap: 10px; }
.user-row-pro {
  display: flex; align-items: center; gap: 10px;
  padding: 10px; background: rgba(255,255,255,0.02); border-radius: 8px;
  position: relative;
}
.u-idx { font-size: 12px; color: #475569; width: 16px; text-align: center; }
.u-avatar {
  width: 32px; height: 32px; border-radius: 50%; 
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; font-weight: bold; color: #fff; text-shadow: 0 1px 2px rgba(0,0,0,0.3);
}
.u-info { flex: 1; display: flex; flex-direction: column; gap: 4px; }
.u-top { display: flex; justify-content: space-between; font-size: 13px; }
.u-name { color: #e2e8f0; font-weight: 500; }
.u-num { color: #94a3b8; font-size: 12px; }
.u-num .highlight { color: #EAC26E; font-weight: bold; }

.u-bottom { display: flex; justify-content: space-between; font-size: 11px; }
.u-time { color: #64748b; }
.u-money { color: #94a3b8; }

.u-medal { position: absolute; top: 0; left: 0; }
.rank-dot { 
  position: absolute; top: 12px; left: 4px; width: 4px; height: 4px; border-radius: 50%; 
}
.rank-1 { background: #fbbf24; box-shadow: 0 0 6px #fbbf24; }
.rank-2 { background: #94a3b8; box-shadow: 0 0 6px #94a3b8; }
.rank-3 { background: #b45309; box-shadow: 0 0 6px #b45309; }

.bottom-action-bar-pro {
  position: absolute; bottom: 0; left: 0; width: 100%; 
  min-height: 70px; height: auto;
  background: rgba(18, 18, 28, 0.98); backdrop-filter: blur(20px);
  border-top: 1px solid rgba(255,255,255,0.05);
  display: flex; align-items: center; padding: 10px 20px; 
  justify-content: space-between;
  padding-bottom: calc(10px + constant(safe-area-inset-bottom));
  padding-bottom: calc(10px + env(safe-area-inset-bottom));
  z-index: 100;
  box-shadow: 0 -4px 20px rgba(0,0,0,0.3);
}

.bar-left { display: flex; flex-direction: column; justify-content: center; }
.remain-info { display: flex; align-items: baseline; gap: 2px; }
.bar-left .lbl { font-size: 11px; color: #94a3b8; }
.bar-left .val { font-size: 18px; font-weight: bold; color: #ef4444; font-family: monospace; }
.bar-left .unit { font-size: 11px; color: #64748b; }

.bar-right { display: flex; align-items: center; gap: 12px; flex: 1; justify-content: flex-end; }
.stepper-capsule {
  display: flex; align-items: center; background: rgba(255,255,255,0.05);
  border-radius: 20px; padding: 2px; border: 1px solid rgba(255,255,255,0.1);
  height: 36px;
}
.btn-step {
  width: 32px; height: 100%; display: flex; align-items: center; justify-content: center;
  font-size: 16px; color: #fff; cursor: pointer; transition: opacity 0.2s;
}
.btn-step:active { opacity: 0.5; }
.btn-step.disabled { color: #475569; cursor: not-allowed; }
.input-step { 
  width: 36px; text-align: center; font-size: 15px; font-weight: bold; color: #EAC26E; 
}

.buy-btn-large {
  flex: 1;
  height: 48px; border-radius: 24px; border: none;
  background: linear-gradient(135deg, #F0C930 0%, #D19611 100%);
  color: #2c1802;
  box-shadow: 
    0 4px 15px rgba(240, 201, 48, 0.4),
    inset 0 1px 1px rgba(255, 255, 255, 0.4);
  transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
  display: flex; flex-direction: column;
  align-items: center; justify-content: center; 
  min-width: 140px;
  position: relative; overflow: hidden;
}
.buy-btn-large::after {
  content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%;
  background: linear-gradient(to bottom, rgba(255,255,255,0.2) 0%, transparent 50%);
  pointer-events: none;
}
.buy-btn-large .btn-text { font-size: 16px; font-weight: 800; letter-spacing: 1px; }
.buy-btn-large .btn-sub { font-size: 11px; font-weight: 600; opacity: 0.8; margin-top: -2px; }

.buy-btn-large:active { transform: scale(0.96); box-shadow: 0 2px 8px rgba(240, 201, 48, 0.3); }
.buy-btn-large.disabled { 
  background: #334155; color: #94a3b8; box-shadow: none; cursor: not-allowed; 
}
.buy-btn-large.disabled::after { display: none; }

@keyframes spin-slow { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>
