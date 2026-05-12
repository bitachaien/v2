<template>
  <div class="room-settings">
    
    <van-nav-bar
      title="房间设置"
      left-arrow
      @click-left="goBack"
    />

    
    <div class="section">
      <div class="section-header">
        <span class="section-title">房间成员</span>
        <span class="section-more" @click="goMembers">查看更多成员 <van-icon name="arrow" /></span>
      </div>
      <div class="member-list">
        <div class="member-item" v-for="member in displayMembers" :key="member.id">
          <div class="member-avatar">
            <img :src="member.avatar" alt="" />
            <span class="member-tag" :class="member.role">{{ member.roleText }}</span>
          </div>
          <span class="member-name">{{ member.name }}</span>
        </div>
      </div>
    </div>

    
    <div class="section">
      <div class="section-header">
        <span class="section-title">房间应用</span>
      </div>
      <div class="app-grid">
        <div class="app-item" v-for="app in roomApps" :key="app.key" @click="handleAppClick(app.key)">
          <div class="app-icon">
            <img :src="app.icon" alt="" />
          </div>
          <span class="app-name">{{ app.name }}</span>
        </div>
      </div>
    </div>

    
    <div class="section settings-section">
      <div class="section-header">
        <span class="section-title">聊天设置</span>
      </div>
      
      <div class="setting-item">
        <span class="setting-label">游戏音乐</span>
        <van-switch v-model="gameMusic" size="24" />
      </div>
      
      <div class="setting-item">
        <span class="setting-label">冷热统计期数</span>
        <div class="period-options">
          <span 
            class="period-btn" 
            :class="{ active: hotColdPeriod === 30 }"
            @click="hotColdPeriod = 30"
          >30</span>
          <span 
            class="period-btn" 
            :class="{ active: hotColdPeriod === 50 }"
            @click="hotColdPeriod = 50"
          >50</span>
          <span 
            class="period-btn" 
            :class="{ active: hotColdPeriod === 100 }"
            @click="hotColdPeriod = 100"
          >100</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { showToast } from 'vant'
import { xy28Api } from '@/api'

const router = useRouter()
const route = useRoute()
const lotteryCode = computed(() => route.params.code || 'yfxy28')
const settingsLoaded = ref(false)

const members = ref([
  { id: 1, name: '在线问题客服', avatar: '/assets/img/avatar1.png', role: 'kefu', roleText: '客服' },
  { id: 2, name: '充值/提现客服', avatar: '/assets/img/avatar2.png', role: 'kefu', roleText: '客服' },
  { id: 3, name: '代理开户', avatar: '/assets/img/avatar3.png', role: 'kefu', roleText: '客服' },
  { id: 4, name: 'Am', avatar: '/assets/img/avatar4.png', role: 'member', roleText: '成员' }
])

const displayMembers = computed(() => members.value.slice(0, 4))

const roomApps = [
  { key: 'kefu', name: '客服', icon: '/assets/img/28/imgi_11_default.png' },
  { key: 'recharge', name: '充值', icon: '/assets/img/28/imgi_12_default.png' },
  { key: 'withdraw', name: '提现', icon: '/assets/img/28/imgi_13_default.png' },
  { key: 'trend', name: '开奖走势', icon: '/assets/img/28/imgi_14_default.png' },
  { key: 'rules', name: '玩法规则', icon: '/assets/img/28/imgi_1182_default.png' },
  { key: 'chase', name: '发起追号', icon: '/assets/img/28/imgi_15_default.png' },
  { key: 'chaseRecord', name: '追号记录', icon: '/assets/img/28/imgi_16_default.png' }
]

const gameMusic = ref(true)
const hotColdPeriod = ref(50)

async function loadSettings() {

  const localMusic = localStorage.getItem('xy28_game_music')
  const localPeriod = localStorage.getItem('xy28_hot_cold_period')
  if (localMusic !== null) gameMusic.value = localMusic !== 'false'
  if (localPeriod) hotColdPeriod.value = parseInt(localPeriod) || 50
  
  try {
    const res = await xy28Api.getUserSettings()
    if (res.code === 0 && res.data) {
      gameMusic.value = res.data.game_music !== false
      hotColdPeriod.value = res.data.hot_cold_period || 50
    }
  } catch (e) {
    console.error('加载设置失败，使用本地缓存:', e)
  }
  settingsLoaded.value = true
}

watch(gameMusic, async (val) => {
  if (!settingsLoaded.value) return
  localStorage.setItem('xy28_game_music', val.toString())
  try {
    await xy28Api.saveUserSetting('game_music', val)
  } catch (e) {
    console.error('保存设置失败:', e)
  }
})

watch(hotColdPeriod, async (val) => {
  if (!settingsLoaded.value) return
  localStorage.setItem('xy28_hot_cold_period', val.toString())
  try {
    await xy28Api.saveUserSetting('hot_cold_period', val)
    showToast('设置已保存')
  } catch (e) {
    console.error('保存设置失败:', e)
    showToast('设置已保存（本地）')
  }
})

onMounted(() => {
  loadSettings()
})

function goBack() {
  router.back()
}

function goMembers() {
  router.push(`/lottery/room-members/${lotteryCode.value}`)
}

function handleAppClick(key) {
  switch (key) {
    case 'kefu':

      router.push('/service')
      break
    case 'recharge':
      router.push('/payment/recharge')
      break
    case 'withdraw':
      router.push('/payment/withdraw')
      break
    case 'trend':
      router.push(`/lottery/xy28-history/${lotteryCode.value}?tab=trend`)
      break
    case 'rules':

      router.push(`/lottery/xy28-help/${lotteryCode.value}`)
      break
    case 'chase':

      router.back()
      break
    case 'chaseRecord':

      router.push(`/lottery/chase-records/${lotteryCode.value}`)
      break
  }
}
</script>

<style lang="less" scoped>
.room-settings {
  min-height: 100vh;
  background: #f5f5f5;
  
  .section {
    background: #fff;
    margin-bottom: 10px;
    padding: 16px;
    
    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 16px;
      
      .section-title {
        font-size: 16px;
        font-weight: 600;
        color: #333;
      }
      
      .section-more {
        font-size: 13px;
        color: #999;
        display: flex;
        align-items: center;
        gap: 2px;
      }
    }
    
    .member-list {
      display: flex;
      gap: 20px;
      
      .member-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        width: 60px;
        
        .member-avatar {
          position: relative;
          width: 50px;
          height: 50px;
          
          img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
          }
          
          .member-tag {
            position: absolute;
            bottom: -4px;
            left: 50%;
            transform: translateX(-50%);
            padding: 1px 6px;
            border-radius: 8px;
            font-size: 10px;
            color: #fff;
            white-space: nowrap;
            
            &.kefu {
              background: linear-gradient(90deg, #ff6b6b, #ee5a24);
            }
            
            &.member {
              background: #52c41a;
            }
          }
        }
        
        .member-name {
          margin-top: 8px;
          font-size: 12px;
          color: #666;
          text-align: center;
          width: 100%;
          overflow: hidden;
          text-overflow: ellipsis;
          white-space: nowrap;
        }
      }
    }
    
    .app-grid {
      display: grid;
      grid-template-columns: repeat(5, 1fr);
      gap: 12px 8px;
      
      .app-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        
        .app-icon {
          width: 28.66px;
          height: 28.66px;
          
          img {
            width: 100%;
            height: 100%;
            object-fit: contain;
          }
        }
        
        .app-name {
          margin-top: 6px;
          font-size: 12px;
          color: #666;
        }
      }
    }
  }
  
  .settings-section {
    .setting-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 12px 0;
      border-bottom: 1px solid #f5f5f5;
      
      &:last-child {
        border-bottom: none;
      }
      
      .setting-label {
        font-size: 15px;
        color: #333;
      }
      
      .period-options {
        display: flex;
        gap: 10px;
        
        .period-btn {
          padding: 6px 16px;
          border: 1px solid #ddd;
          border-radius: 4px;
          font-size: 14px;
          color: #666;
          
          &.active {
            border-color: #5691fe;
            color: #5691fe;
            background: #f0f7ff;
          }
        }
      }
    }
  }
}
</style>
