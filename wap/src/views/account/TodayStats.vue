<template>
  <div class="today-stats-page">
    
    <div class="nav-header glass-panel">
      <div class="nav-left" @click="goBack">
        <van-icon name="arrow-left" class="neon-icon" />
      </div>
      <div class="nav-title">今日统计</div>
      <div class="nav-right" @click="onHelp">
        <van-icon name="question-o" class="neon-icon" />
      </div>
    </div>

    
    <div class="date-filter-bar">
      <div 
        v-for="filter in dateFilters" 
        :key="filter.value"
        class="filter-pill"
        :class="{ active: currentFilter === filter.value }"
        @click="currentFilter = filter.value"
      >
        {{ filter.label }}
      </div>
    </div>

    <div class="content-scroll">
      
      <div class="asset-overview-card glass-card animate-up">
        
        <div class="wallet-tabs">
          <div 
            class="tab-item" 
            :class="{ active: currentWallet === 'main' }"
            @click="currentWallet = 'main'"
          >
            主钱包
            <div class="active-indicator"></div>
          </div>
          <div 
            class="tab-item" 
            :class="{ active: currentWallet === 'game' }"
            @click="currentWallet = 'game'"
          >
            娱乐钱包
            <div class="active-indicator"></div>
          </div>
        </div>

        <div class="user-row">
          <span class="username">{{ username }}</span>
          <div class="vip-badge">VIP {{ vipLevel }}</div>
        </div>

        <div class="balance-main">
          <div class="label">当前余额 (元)</div>
          <div class="value">¥{{ formatAmount(currentWalletData.balance) }}</div>
        </div>

        <div class="pl-row">
          <div class="label">今日盈亏</div>
          <div 
            class="value" 
            :class="currentWalletData.pl >= 0 ? 'text-green' : 'text-red'"
          >
            {{ currentWalletData.pl >= 0 ? '+' : '' }}{{ formatAmount(currentWalletData.pl) }}
          </div>
        </div>

        <div class="divider"></div>

        <div class="stats-row-bottom">
          <div class="stat-col">
            <span class="lbl">今日充值</span>
            <span class="val text-blue">{{ formatAmount(currentWalletData.recharge) }}</span>
          </div>
          <div class="stat-col center-border">
            <span class="lbl">今日提现</span>
            <span class="val text-orange">{{ formatAmount(currentWalletData.withdraw) }}</span>
          </div>
          <div class="stat-col">
            <span class="lbl">有效投注</span>
            <span class="val text-purple">{{ formatAmount(currentWalletData.bet) }}</span>
          </div>
        </div>
      </div>

      
      <div class="stats-grid">
        <div 
          v-for="(card, index) in statCards" 
          :key="card.type"
          class="grid-card glass-card animate-up"
          :style="{ animationDelay: `${0.1 + index * 0.05}s` }"
          @click="openDetail(card)"
        >
          <div class="card-top">
            <div class="icon-box" :class="card.iconClass">
              <van-icon :name="card.iconName" />
            </div>
            <span class="card-title">{{ card.title }}</span>
          </div>
          <div class="card-val-big">
            <span class="symbol">¥</span>
            {{ formatAmount(card.amount) }}
          </div>
          <div class="card-desc">{{ card.desc }}</div>
          <div class="card-bg-glow" :class="card.glowColor"></div>
        </div>
      </div>
    </div>

    
    <div class="bottom-actions glass-panel">
      <button class="action-btn btn-recharge" @click="goToRecharge">
        <van-icon name="gold-coin" /> 立即充值
        <div class="btn-glow"></div>
      </button>
      <button class="action-btn btn-withdraw" @click="goToWithdraw">
        <van-icon name="card" /> 申请提现
        <div class="btn-glow"></div>
      </button>
    </div>

    
    <van-popup
      v-model:show="showDetailPopup"
      position="bottom"
      round
      class="tech-popup"
      :style="{ height: '60%' }"
    >
      <div class="popup-drag-bar"></div>
      <div class="popup-header">
        <div class="popup-title" v-if="selectedStat">
          <div class="icon-box" :class="selectedStat.iconClass" style="width: 24px; height: 24px;">
            <van-icon :name="selectedStat.iconName" size="14" />
          </div>
          {{ selectedStat.title }}详情
        </div>
        <van-icon name="cross" class="popup-close" @click="showDetailPopup = false" />
      </div>

      <div class="popup-content" v-if="selectedStat">
        <div class="popup-summary-card">
          <div class="label">今日{{ selectedStat.title }}总额</div>
          <div class="value" :class="selectedStat.valueColor">
            <span style="font-size: 20px;">¥</span>{{ formatAmount(selectedStat.amount) }}
          </div>
          <div class="desc">{{ selectedStat.desc }}</div>
        </div>

        <div class="recent-list-title">最近记录</div>
        <div class="record-item" v-for="rec in transactionList" :key="rec.id">
          <div class="left">
            <div class="record-type">{{ rec.title }}</div>
            <div class="record-time">{{ rec.time }}</div>
          </div>
          <div class="amount" :class="rec.amount > 0 ? 'text-green' : 'text-red'">
            {{ rec.amount > 0 ? '+' : '' }}{{ formatAmount(rec.amount) }}
          </div>
        </div>
        <div v-if="listLoading" class="no-data">加载中...</div>
        <div v-else-if="transactionList.length === 0" class="no-data">暂无记录</div>

        <div class="view-all-btn" @click="goToBillRecord">
          查看全部记录 <van-icon name="arrow" />
        </div>
      </div>
    </van-popup>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { showToast, showLoadingToast, closeToast } from 'vant'
import { accountApi } from '@/api/account'
import { checkFundPasswordAndNavigate } from '@/utils/withdrawCheck'

const router = useRouter()

const userinfo = ref({
  username: (window as any).__VUE_INJECT_DATA__?.userinfo?.username || 'Guest',
  face: (window as any).__VUE_INJECT_DATA__?.userinfo?.face || '/assets/images/common/rui-face.png',
  balance: (window as any).__VUE_INJECT_DATA__?.userinfo?.balance || '0.00',
  groupid: (window as any).__VUE_INJECT_DATA__?.userinfo?.groupid || '1'
})

const username = computed(() => userinfo.value.username)
const vipLevel = computed(() => userinfo.value.groupid)

const currentFilter = ref('today')
const currentWallet = ref('main')
const showDetailPopup = ref(false)
const selectedStat = ref<any>(null)
const loading = ref(false)

const transactionList = ref<any[]>([])
const listLoading = ref(false)

const dateFilters = [
  { label: '今天', value: 'today' },
  { label: '昨天', value: 'yesterday' },
  { label: '本周', value: 'week' },
  { label: '本月', value: 'month' }
]

const currentWalletData = ref({
  balance: parseFloat(userinfo.value.balance),
  pl: 0.00,
  recharge: 0.00,
  withdraw: 0.00,
  bet: 0.00,
  payout: 0.00,
  rebate: 0.00,
  bonus: 0.00,
  transferIn: 0.00,
  transferOut: 0.00
})

const statCards = computed(() => {
  return [
    { 
      type: 'payout', 
      title: '派彩收入', 
      amount: currentWalletData.value.payout, 
      desc: '中奖及活动派彩', 
      iconName: 'gold-coin', 
      iconClass: 'icon-payout',
      glowColor: 'glow-cyan',
      valueColor: 'text-green'
    },
    { 
      type: 'bet', 
      title: '投注支出', 
      amount: currentWalletData.value.bet, 
      desc: '游戏投注总额', 
      iconName: 'fire', 
      iconClass: 'icon-bet',
      glowColor: 'glow-pink',
      valueColor: 'text-red'
    },
    { 
      type: 'rebate', 
      title: '返点金额', 
      amount: currentWalletData.value.rebate, 
      desc: '下级返点及自身返水', 
      iconName: 'refund-o', 
      iconClass: 'icon-rebate',
      glowColor: 'glow-gold',
      valueColor: 'text-orange'
    },
    { 
      type: 'bonus', 
      title: '活动礼金', 
      amount: currentWalletData.value.bonus, 
      desc: '签到、晋级等活动', 
      iconName: 'gift', 
      iconClass: 'icon-bonus',
      glowColor: 'glow-pink',
      valueColor: 'text-green'
    },
    { 
      type: 'in', 
      title: '充值/转入', 
      amount: currentWalletData.value.recharge, // 暂时合并显示，或根据接口区分
      desc: '在线充值及转账', 
      iconName: 'card', 
      iconClass: 'icon-in',
      glowColor: 'glow-blue',
      valueColor: 'text-blue'
    },
    { 
      type: 'out', 
      title: '提现/转出', 
      amount: currentWalletData.value.withdraw, 
      desc: '提现及转出资金', 
      iconName: 'share', 
      iconClass: 'icon-out',
      glowColor: 'glow-orange',
      valueColor: 'text-orange'
    }
  ]
})

const goBack = () => router.go(-1)
const onHelp = () => showToast('统计数据说明：\n盈亏 = 派彩 + 返点 + 礼金 - 投注')

const formatAmount = (val: number) => {
  return (val || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",")
}

const fetchStats = async () => {
  if (loading.value) return
  loading.value = true

  
  try {
    const res: any = await accountApi.getProfitLossStats({
      timeRange: currentFilter.value,
      walletType: currentWallet.value
    })
    
    if (res.code === 0 && res.data) {
      currentWalletData.value = {
        balance: parseFloat(res.data.balance || 0),
        pl: parseFloat(res.data.profit || 0),
        recharge: parseFloat(res.data.recharge || 0),
        withdraw: parseFloat(res.data.withdraw || 0),
        bet: parseFloat(res.data.bet || 0),
        payout: parseFloat(res.data.payout || 0),
        rebate: parseFloat(res.data.rebate || 0),
        bonus: parseFloat(res.data.bonus || 0),
        transferIn: parseFloat(res.data.transferIn || 0),
        transferOut: parseFloat(res.data.transferOut || 0)
      }
    }
  } catch (error) {
    console.error(error)

  } finally {
    loading.value = false

  }
}

const fetchRecords = async (type: string) => {
  listLoading.value = true
  try {
    const res: any = await accountApi.getTransactionList({
      timeRange: currentFilter.value,
      type: type,
      page: 1,
      pageSize: 5 // 只取少量显示
    })
    if (res.code === 0 && res.data?.list) {
      transactionList.value = res.data.list
    } else {
      transactionList.value = []
    }
  } catch (error) {
    console.error(error)
    transactionList.value = []
  } finally {
    listLoading.value = false
  }
}

const openDetail = (card: any) => {
  selectedStat.value = card
  showDetailPopup.value = true

  let typeParam = ''
  switch(card.type) {
    case 'payout': typeParam = 'payout'; break;
    case 'bet': typeParam = 'bet'; break;
    case 'rebate': typeParam = 'rebate'; break;
    case 'bonus': typeParam = 'bonus'; break;
    case 'in': typeParam = 'recharge'; break; // 充值+转入
    case 'out': typeParam = 'withdraw'; break; // 提现+转出
  }
  if (typeParam) {
    fetchRecords(typeParam)
  } else {
    transactionList.value = []
  }
}

const goToRecharge = () => router.push('/payment/deposit')
const goToWithdraw = () => checkFundPasswordAndNavigate(router)
const goToBillRecord = () => router.push('/userCenter/billRecord')

watch([currentFilter, currentWallet], () => {
  fetchStats()
})

onMounted(() => {
  fetchStats()
})
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap');

.today-stats-page {
  min-height: 100vh;
  background: linear-gradient(to bottom, #05070E, #0B0E15);
  color: #fff;
  font-family: -apple-system, BlinkMacSystemFont, 'Helvetica Neue', Helvetica, Segoe UI, Arial, Roboto, 'PingFang SC', sans-serif;
  padding-bottom: 100px;
}

.nav-header {
  height: 50px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 16px;
  position: sticky;
  top: 0;
  z-index: 100;
}
.glass-panel {
  background: rgba(16, 24, 40, 0.85);
  backdrop-filter: blur(18px);
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}
.nav-title {
  font-size: 18px;
  font-weight: 600;
  color: #EAC26E;
  text-shadow: 0 0 10px rgba(234, 194, 110, 0.3);
}
.neon-icon {
  font-size: 20px;
  color: #EAC26E;
}

.date-filter-bar {
  display: flex;
  gap: 8px;
  padding: 12px 16px;
  overflow-x: auto;
}
.filter-pill {
  padding: 6px 16px;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 20px;
  font-size: 13px;
  color: rgba(255, 255, 255, 0.6);
  white-space: nowrap;
  transition: all 0.3s;
  border: 1px solid transparent;
}
.filter-pill.active {
  background: rgba(234, 194, 110, 0.15);
  color: #EAC26E;
  border-color: rgba(234, 194, 110, 0.5);
  box-shadow: 0 0 10px rgba(234, 194, 110, 0.2);
}

.content-scroll {
  padding: 10px 16px;
}

.glass-card {
  background: rgba(16, 24, 40, 0.72);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 16px;
  backdrop-filter: blur(18px);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
  margin-bottom: 16px;
  position: relative;
  overflow: hidden;
}

.asset-overview-card {
  padding: 20px;
}

.wallet-tabs {
  display: flex;
  gap: 20px;
  margin-bottom: 20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
  padding-bottom: 10px;
}
.tab-item {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.5);
  position: relative;
  padding-bottom: 8px;
  cursor: pointer;
  transition: all 0.3s;
}
.tab-item.active {
  color: #fff;
  font-weight: 600;
}
.active-indicator {
  position: absolute;
  bottom: -11px;
  left: 50%;
  transform: translateX(-50%);
  width: 0;
  height: 3px;
  background: #EAC26E;
  border-radius: 2px;
  transition: all 0.3s;
  box-shadow: 0 0 8px #EAC26E;
}
.tab-item.active .active-indicator {
  width: 20px;
}

.user-row {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 10px;
}
.username {
  font-size: 16px;
  color: #fff;
  font-weight: bold;
}
.vip-badge {
  background: linear-gradient(90deg, #FFD700, #FFA500);
  color: #000;
  font-size: 10px;
  font-weight: bold;
  padding: 2px 8px;
  border-radius: 10px;
}

.balance-main {
  text-align: center;
  margin-bottom: 20px;
}
.balance-main .label {
  font-size: 12px;
  color: rgba(255, 255, 255, 0.5);
  margin-bottom: 4px;
}
.balance-main .value {
  font-family: 'Orbitron', sans-serif;
  font-size: 36px;
  font-weight: bold;
  color: #EAC26E;
  text-shadow: 0 0 20px rgba(234, 194, 110, 0.4);
}

.pl-row {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 10px;
  margin-bottom: 20px;
}
.pl-row .label {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.6);
}
.pl-row .value {
  font-family: 'Orbitron', sans-serif;
  font-size: 18px;
  font-weight: bold;
}

.stats-row-bottom {
  display: flex;
  justify-content: space-between;
  padding-top: 16px;
}
.stat-col {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
}
.stat-col.center-border {
  border-left: 1px solid rgba(255, 255, 255, 0.06);
  border-right: 1px solid rgba(255, 255, 255, 0.06);
}
.stat-col .lbl { font-size: 11px; color: rgba(255, 255, 255, 0.5); }
.stat-col .val { font-family: 'Orbitron', sans-serif; font-size: 14px; font-weight: bold; }

.stats-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}
.grid-card {
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  cursor: pointer;
  transition: all 0.3s;
}
.grid-card:active {
  transform: scale(0.98);
  background: rgba(255, 255, 255, 0.08);
}

.card-top {
  display: flex;
  align-items: center;
  gap: 8px;
}
.icon-box {
  width: 28px;
  height: 28px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.05);
}
.emoji-icon { font-size: 16px; }

.icon-payout { color: #30E0FF; box-shadow: 0 0 8px rgba(48, 224, 255, 0.3); }
.icon-bet { color: #FF5C7A; box-shadow: 0 0 8px rgba(255, 92, 122, 0.3); }
.icon-rebate { color: #EAC26E; box-shadow: 0 0 8px rgba(234, 194, 110, 0.3); }
.icon-bonus { color: #FF80AB; box-shadow: 0 0 8px rgba(255, 128, 171, 0.3); }
.icon-in { color: #30E0FF; }
.icon-out { color: #FFCF7A; }

.card-title {
  font-size: 13px;
  color: rgba(255, 255, 255, 0.8);
}
.card-val-big {
  font-family: 'Orbitron', sans-serif;
  font-size: 18px;
  font-weight: bold;
  color: #fff;
}
.card-val-big .symbol { font-size: 12px; margin-right: 2px; color: rgba(255,255,255,0.5); }
.card-desc {
  font-size: 10px;
  color: rgba(255, 255, 255, 0.4);
}
.card-bg-glow {
  position: absolute;
  right: -20px;
  bottom: -20px;
  width: 80px;
  height: 80px;
  border-radius: 50%;
  filter: blur(30px);
  opacity: 0.15;
}
.glow-gold { background: #EAC26E; }
.glow-purple { background: #bc13fe; }
.glow-cyan { background: #30E0FF; }
.glow-pink { background: #FF80AB; }
.glow-blue { background: #30E0FF; }
.glow-orange { background: #FFA500; }

.bottom-actions {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  height: 80px;
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 0 20px;
  z-index: 90;
}
.action-btn {
  flex: 1;
  height: 48px;
  border: none;
  border-radius: 999px;
  font-size: 16px;
  font-weight: bold;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  color: #0B0E15;
  cursor: pointer;
  position: relative;
  overflow: hidden;
  transition: all 0.3s;
}
.btn-recharge {
  background: linear-gradient(135deg, #30E0FF, #0099FF);
  box-shadow: 0 0 15px rgba(48, 224, 255, 0.4);
}
.btn-withdraw {
  background: linear-gradient(135deg, #EAC26E, #FFA500);
  box-shadow: 0 0 15px rgba(234, 194, 110, 0.4);
}
.action-btn:active {
  transform: scale(0.96);
  filter: brightness(0.9);
}
.btn-glow {
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background: radial-gradient(circle, rgba(255,255,255,0.4) 0%, transparent 60%);
  opacity: 0;
  transform: scale(0.5);
  transition: opacity 0.5s;
}
.action-btn:active .btn-glow {
  opacity: 0.3;
  transform: scale(1);
  transition: 0s;
}

.tech-popup {
  background: #151925 !important;
}
.popup-drag-bar {
  width: 40px;
  height: 4px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 2px;
  margin: 12px auto;
}
.popup-header {
  padding: 10px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.popup-title {
  font-size: 16px;
  font-weight: bold;
  color: #fff;
  display: flex;
  align-items: center;
  gap: 8px;
}
.popup-close {
  font-size: 20px;
  color: rgba(255, 255, 255, 0.5);
  padding: 8px;
}

.popup-content {
  padding: 20px;
  height: calc(100% - 60px);
  overflow-y: auto;
}

.popup-summary-card {
  background: rgba(0, 0, 0, 0.2);
  border-radius: 12px;
  padding: 20px;
  text-align: center;
  margin-bottom: 24px;
  border: 1px solid rgba(255, 255, 255, 0.05);
}
.popup-summary-card .label { font-size: 12px; color: rgba(255, 255, 255, 0.5); margin-bottom: 8px; }
.popup-summary-card .value { font-family: 'Orbitron', sans-serif; font-size: 32px; font-weight: bold; margin-bottom: 4px; }
.popup-summary-card .desc { font-size: 12px; color: rgba(255, 255, 255, 0.3); }

.recent-list-title { font-size: 14px; color: rgba(255, 255, 255, 0.6); margin-bottom: 12px; }
.record-item {
  display: flex;
  justify-content: space-between;
  padding: 12px 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}
.record-type { font-size: 14px; color: #fff; margin-bottom: 4px; }
.record-time { font-size: 12px; color: rgba(255, 255, 255, 0.4); }
.record-item .amount { font-family: 'DIN', sans-serif; font-size: 16px; font-weight: bold; }

.no-data {
  text-align: center;
  padding: 30px 0;
  color: rgba(255, 255, 255, 0.3);
  font-size: 14px;
}

.view-all-btn {
  margin-top: 20px;
  text-align: center;
  font-size: 13px;
  color: #EAC26E;
  padding: 12px;
  background: rgba(234, 194, 110, 0.1);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
}

.text-green { color: #29F3C3 !important; text-shadow: 0 0 10px rgba(41, 243, 195, 0.3); }
.text-red { color: #FF5C7A !important; text-shadow: 0 0 10px rgba(255, 92, 122, 0.3); }
.text-blue { color: #30E0FF !important; }
.text-orange { color: #FFA500 !important; }
.text-purple { color: #bc13fe !important; }
.divider { height: 1px; background: rgba(255, 255, 255, 0.06); margin: 16px 0; }

.animate-up {
  animation: slideUp 0.6s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
  opacity: 0;
  transform: translateY(30px);
}
@keyframes slideUp {
  to { opacity: 1; transform: translateY(0); }
}
</style>
