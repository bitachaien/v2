<template>
  <div class="xy28-chat">
    
    <header class="chat-header">
      <div class="header-left" @click="goBack">
        <van-icon name="arrow-left" size="20" />
      </div>
      <div class="header-title">
        <span class="title-text">{{ lotteryName }}</span>
        <span class="online-count">({{ onlineCount }})</span>
        <van-icon name="play" class="title-arrow" :class="{ collapsed: !isHeaderInfoVisible }" @click.stop="toggleHeaderInfo" />
      </div>
      <div class="header-right">
        <img :src="showScratchPanel ? '/assets/img/zk.png' : '/assets/img/aac.png'" class="header-icon" @click="toggleScratchMode" />
        <van-icon name="bars" size="22" @click="goRoomSettings" />
      </div>
    </header>

    
    <ScratchCardPanel 
      ref="scratchCardRef"
      :show="isHeaderInfoVisible && showScratchPanel"
      :currentIssue="currentIssue"
      :lastIssue="lastIssue"
      :lastCode="lastCode"
      :countdownStatus="countdownStatus"
      :countdownText="countdownText"
    />

    
    <InfoBar 
      :show="isHeaderInfoVisible && !showScratchPanel"
      :currentIssue="currentIssue"
      :countdownStatus="countdownStatus"
      :countdownText="countdownText"
      :balance="userBalance"
      :refreshing="refreshingBalance"
      @refresh="refreshBalance"
    />

    
    <LastResultHeader 
      v-show="isHeaderInfoVisible && !showScratchPanel"
      :lastIssue="lastIssue"
      :lastCode="lastCode"
      :historyList="historyList"
      v-model:isExpanded="isResultExpanded"
      @more="goHistory"
    />

    
    <transition name="fade">
      <div class="history-overlay" v-if="isResultExpanded" @click="isResultExpanded = false"></div>
    </transition>

    
    <ChatMessages 
      ref="chatMessagesRef"
      :messages="messages"
      :hasMore="hasMore"
      :loadingMore="loadingMore"
      :defaultAvatar="defaultAvatar"
      @loadMore="loadMoreMessages"
      @detail="openBetDetail"
      @scroll="onScroll"
    />

    
    <div class="chat-input-area" :class="{ 'sealed-area': countdownStatus === 'sealed' }">
      <div class="input-wrapper">
        <input 
          type="text" 
          v-model="inputText" 
          :placeholder="countdownStatus === 'sealed' ? '封盘中，暂停投注' : '输入文字指令下注'"
          class="bet-input"
          :disabled="countdownStatus === 'sealed'"
          @keyup.enter="sendBet"
        />
        <button class="send-btn" @click="sendBet" :disabled="!inputText.trim() || countdownStatus === 'sealed'">
          {{ countdownStatus === 'sealed' ? '封盘' : '发送' }}
        </button>
      </div>
    </div>

    
    <TableBetPanel 
      v-model:show="showTableBet" 
      :lotteryCode="lotteryCode"
      :currentIssue="currentIssue"
      :isSealed="countdownStatus === 'sealed'"
      :wsBetStats="wsBetStats"
      @help="goTableHelp"
      @submit="handleTableBetSubmit"
    />

    
    <CreditBetPanel 
      v-model:show="showCreditBet" 
      :currentIssue="currentIssue"
      :balance="userBalance"
      :lotteryCode="lotteryCode"
      :isSealed="countdownStatus === 'sealed'"
      @submit="handleCreditBetSubmit"
    />

    
    <div class="bottom-nav" :class="{ hidden: showTableBet || showCreditBet || showQuickBet }">
      <div class="nav-item" :class="{ active: currentTab === 'credit', disabled: countdownStatus === 'sealed' }" @click="switchTab('credit')">
        <img src="/assets/img/xy.png" class="nav-icon" />
        <span>信用</span>
      </div>
      <div class="nav-item" :class="{ active: currentTab === 'table', disabled: countdownStatus === 'sealed' }" @click="switchTab('table')">
        <img src="/assets/img/zt.png" class="nav-icon" />
        <span>桌投</span>
      </div>
      <div class="nav-item" :class="{ active: currentTab === 'quick', disabled: countdownStatus === 'sealed' }" @click="switchTab('quick')">
        <img src="/assets/img/kt.png" class="nav-icon" />
        <span>快投</span>
      </div>
      <div class="nav-item" :class="{ active: showMorePanel }" @click="showMorePanel = !showMorePanel">
        <img src="/assets/img/gd.png" class="nav-icon" />
        <span>更多</span>
      </div>
    </div>

    
    
    <MorePanel 
      v-model:show="showMorePanel" 
      @action="onMoreAction" 
    />

    
    <van-popup v-model:show="showLotteryPicker" position="top" round>
      <div class="lottery-picker">
        <div class="picker-header">
          <span>选择彩种</span>
          <van-icon name="cross" @click="showLotteryPicker = false" />
        </div>
        <div class="lottery-list">
          <div 
            v-for="item in lotteryList" 
            :key="item.code"
            class="lottery-item"
            :class="{ active: lotteryCode === item.code }"
            @click="switchLottery(item.code)"
          >
            {{ item.name }}
          </div>
        </div>
      </div>
    </van-popup>

    
    <van-popup v-model:show="showHistory" position="bottom" round :style="{ height: '60%' }">
      <div class="history-popup">
        <div class="popup-header">
          <span>开奖历史</span>
          <van-icon name="cross" @click="showHistory = false" />
        </div>
        <div class="history-list">
          <div class="history-item" v-for="item in historyList" :key="item.issue">
            <span class="h-issue">{{ item.issue }}</span>
            <div class="h-balls">
              <span class="h-ball">{{ item.code[0] }}</span>
              <span class="h-plus">+</span>
              <span class="h-ball">{{ item.code[1] }}</span>
              <span class="h-plus">+</span>
              <span class="h-ball">{{ item.code[2] }}</span>
              <span class="h-equal">=</span>
              <span class="h-ball h-sum" :class="getSumColorClass(item.sum)">{{ item.sum }}</span>
            </div>
            <span class="h-type">{{ item.type }}</span>
          </div>
        </div>
      </div>
    </van-popup>

    
    <QuickBetKeyboard 
      v-model:show="showQuickBet" 
      v-model="quickBetText"
      :isSealed="countdownStatus === 'sealed'"
      :lotteryCode="lotteryCode"
      @submit="submitQuickBet"
    />


    
    <van-dialog v-model:show="showDetailPopup" :show-confirm-button="false" class="bet-detail-dialog" width="300px">
        <div class="detail-content">
            <div class="detail-header">
                <span class="detail-title">{{ currentDetail.name }}下注详情</span>
                <span class="detail-total">{{ currentDetail.total }}元</span>
            </div>
            <div class="detail-list">
                <div class="detail-item" v-for="(bet, idx) in currentDetail.bets" :key="idx">
                    <div class="bet-icon">{{ bet.label }}</div>
                    <div class="bet-amount">{{ bet.amount }}</div>
                </div>
            </div>
            <div class="detail-footer" @click="showDetailPopup = false">
                我知道了
            </div>
        </div>
    </van-dialog>
    
    <van-popup v-model:show="showRulesPopup" round position="center" :style="{ width: '80%', maxHeight: '70%' }">
        <div class="rules-popup-content">
            <div class="rules-header">XY28玩法规则</div>
            <div class="rules-body">
                <p>1. 幸运28采用北京28开奖数据，开奖结果为3个号码相加之和。</p>
                <p>2. 号码范围：0-27。</p>
                <p>3. 玩法包括：大小单双、组合、极值、波色等。</p>
                <p>4. 赔率说明详见赔率表。</p>
                <p>5. 每5分钟一期，全天开奖。</p>
            </div>
            <div class="rules-footer" @click="showRulesPopup = false">
                关闭
            </div>
        </div>
    </van-popup>

    <RoadMapPopup v-model:show="showRoadMapPopup" :historyList="historyList" />
    <HotColdPopup v-model:show="showHotColdPopup" :historyList="historyList" :lotteryCode="lotteryCode" />
    <ChaseRecordsPopup v-model:show="showChaseRecordsPopup" :lotteryCode="lotteryCode" />
    <DepositPopup v-model:show="showDepositPopup" theme="lottery" />
    
    
    <transition name="win-animation">
      <div class="win-overlay" v-if="showWinAnimation" @click="closeWinAnimation">
        <div class="win-content" @click.stop>
          <div class="win-light"></div>
          <div class="win-icon">🎉</div>
          <div class="win-title">恭喜中奖！</div>
          <div class="win-issue">第 {{ winData.issue }} 期</div>
          <div class="win-amount">+{{ winData.profit?.toFixed(2) }}</div>
          <div class="win-detail">
            <span>投注: {{ winData.totalBet?.toFixed(2) }}</span>
            <span>中奖: {{ winData.totalWin?.toFixed(2) }}</span>
          </div>
          <button class="win-btn" @click="closeWinAnimation">太棒了</button>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { showToast, showLoadingToast, closeToast } from 'vant'
import { xy28Api } from '@/api'
import { lotteryWS } from '@/utils/websocket'
import { useUserStore } from '@/stores/user'
import dayjs from 'dayjs'
import RoadMapPopup from './components/RoadMapPopup.vue'
import HotColdPopup from './components/HotColdPopup.vue'
import ChaseRecordsPopup from './components/ChaseRecordsPopup.vue'
import DepositPopup from '@/components/deposit/DepositPopup.vue'
import TableBetPanel from './components/TableBetPanel.vue'
import CreditBetPanel from './components/CreditBetPanel.vue'
import MorePanel from './components/MorePanel.vue'
import QuickBetKeyboard from './components/QuickBetKeyboard.vue'
import ScratchCardPanel from './components/ScratchCardPanel.vue'
import LastResultHeader from './components/LastResultHeader.vue'
import InfoBar from './components/InfoBar.vue'
import ChatMessages from './components/ChatMessages.vue'

const router = useRouter()
const route = useRoute()
const userStore = useUserStore()

const defaultAvatar = '/assets/images/user/avatars/default.png'

const lotteryList = [
  { code: 'ffxy28', apiCode: 'yfxy28' },
  { code: 'yfxy28', apiCode: 'yfxy28' },
  { code: 'jndpcdd', apiCode: 'jndpcdd' },
  { code: 'cqpcdd', apiCode: 'cqpcdd' }
]

const routeCode = ref(route.params.code || 'yfxy28')

const lotteryCode = computed(() => {
  const item = lotteryList.find(l => l.code === routeCode.value)
  return item?.apiCode || routeCode.value
})

const lotteryTitle = ref('')
const lotteryName = computed(() => {
  return lotteryTitle.value || '分分28'
})
const onlineCount = ref(4974)
const userBalance = ref('0.00')
const refreshingBalance = ref(false)

const currentIssue = ref('202512091378')
const lastIssue = ref('202512091377')
const remainMs = ref(26000)
const lastCode = ref([6, 9, 0])
const lastSum = computed(() => lastCode.value.reduce((a, b) => a + b, 0))

function getSumColorClass(sum) {

  const redNums = [3, 6, 9, 12, 15, 18, 21, 24]
  const greenNums = [1, 4, 7, 10, 16, 19, 22, 25]
  const blueNums = [2, 5, 8, 11, 13, 14, 17, 20, 23, 26, 27]
  
  if (redNums.includes(sum)) return 'sum-red'
  if (greenNums.includes(sum)) return 'sum-green'
  if (blueNums.includes(sum)) return 'sum-blue'
  return 'sum-red'
}

const countdownStatus = ref('countdown')

const countdownText = computed(() => {
  if (countdownStatus.value === 'sealed') return '封盘中'
  if (countdownStatus.value === 'drawing') return '开奖中'
  const seconds = Math.floor(remainMs.value / 1000)
  const mins = Math.floor(seconds / 60)
  const secs = seconds % 60
  return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`
})

const messages = ref([])
const chatMessagesRef = ref(null)
const hasMore = ref(true)
const loadingMore = ref(false)

const inputText = ref('')
const currentTab = ref('credit')

const showLotteryPicker = ref(false)
const showHistory = ref(false)
const showMorePanel = ref(false)  // 更多功能面板（内联展开）
const showQuickBet = ref(false)
const showTableBet = ref(false)   // 桌投面板
const showCreditBet = ref(false)  // 信用投注面板
const isResultExpanded = ref(false) // 开奖历史是否展开

const historyList = ref([])

const quickBetText = ref('')

function goBack() {
  router.back()
}

function goRoomSettings() {
  router.push(`/lottery/room-settings/${lotteryCode.value}`)
}

const showScratchPanel = ref(false)
const scratchCardRef = ref(null)

function toggleScratchMode() {
  showScratchPanel.value = !showScratchPanel.value
  
  if (showScratchPanel.value) {
    isHeaderInfoVisible.value = true
    isResultExpanded.value = false
  }
}

const isHeaderInfoVisible = ref(true)

function toggleHeaderInfo(e) {
  isHeaderInfoVisible.value = !isHeaderInfoVisible.value

  if (!isHeaderInfoVisible.value) {
    isResultExpanded.value = false
  }
}

async function refreshBalance() {
  refreshingBalance.value = true
  try {
    const res = await xy28Api.getUserBalance()
    if (res.code === 0) {
      userBalance.value = res.data?.available || res.data?.balance || '0.00'
    }
  } catch (e) {
    console.error('刷新余额失败:', e)
  }
  refreshingBalance.value = false
}

function switchLottery(code) {
  if (code === lotteryCode.value) {
    showLotteryPicker.value = false
    return
  }
  lotteryCode.value = code
  showLotteryPicker.value = false

  loadChatData()
}

function switchTab(tab) {

  if (countdownStatus.value === 'sealed' && ['credit', 'table', 'quick'].includes(tab)) {
    showToast('封盘中，暂停投注')
    return
  }
  
  currentTab.value = tab
  showMorePanel.value = false  // 关闭更多面板
  

  requestAnimationFrame(() => {
      if (tab === 'quick') {
        showQuickBet.value = true
        showTableBet.value = false
        showCreditBet.value = false
      } else if (tab === 'table') {
        showTableBet.value = true
        showQuickBet.value = false
        showCreditBet.value = false
      } else if (tab === 'credit') {
        showCreditBet.value = true
        showTableBet.value = false
        showQuickBet.value = false
      } else {
        showQuickBet.value = false
        showTableBet.value = false
        showCreditBet.value = false
      }
  })
}

const handleTableBetSubmit = async (val) => {
   inputText.value = val
   showTableBet.value = false 
   await sendBet()
}

function goTableHelp() {
  router.push('/lottery/xy28-help')
}

function goHistory() {
  router.push(`/lottery/xy28-history/${lotteryCode.value}`)
}

const handleCreditBetSubmit = async (val) => {
   inputText.value = val
   showCreditBet.value = false
   await sendBet()
}

async function submitQuickBet() {
  if (!quickBetText.value.trim()) {
    showToast('请输入投注内容')
    return
  }
  
  inputText.value = quickBetText.value
  showQuickBet.value = false
  await sendBet()
  quickBetText.value = ''
}

async function sendBet() {
  const text = inputText.value.trim()
  if (!text) return
  

  if (countdownStatus.value === 'sealed') {
    showToast('封盘中，暂停投注')
    return
  }
  
  try {
    showLoadingToast({ message: '投注中...', duration: 0 })
    

    const res = await xy28Api.submitChatBet({
      lotteryCode: lotteryCode.value,
      issue: currentIssue.value,
      betText: text
    })
    
    closeToast()
    
    if (res.code === 0) {

      inputText.value = ''
      scrollToBottom()
      

      if (res.data?.balance) {
        userBalance.value = res.data.balance
      }
      
      showToast({ type: 'success', message: '投注成功' })
    } else {
      showToast(res.message || '投注失败')
    }
  } catch (e) {
    closeToast()
    showToast('网络错误')
  }
}

const showDetailPopup = ref(false)
const showRulesPopup = ref(false)
const showRoadMapPopup = ref(false)
const showHotColdPopup = ref(false)
const showChaseRecordsPopup = ref(false)
const showDepositPopup = ref(false)
const currentDetail = ref({})

const showWinAnimation = ref(false)
const winData = ref({
  issue: '',
  totalBet: 0,
  totalWin: 0,
  profit: 0,
  items: []
})

const wsBetStats = ref({})

function closeWinAnimation() {
  showWinAnimation.value = false
}

function openBetDetail(item) {
  currentDetail.value = item
  showDetailPopup.value = true
}

function onMoreAction(action) {

  const popupActions = ['roadMap', 'hotCold', 'playRules', 'chaseRecords', 'recharge']
  const keepOpen = popupActions.includes(action)
  
  if (!keepOpen) {
    showMorePanel.value = false
  }
  
  switch (action) {
    case 'service':
      router.push('/service/online')
      break
    case 'recharge':
      showDepositPopup.value = true
      break
    case 'withdraw':
      if (userStore?.userInfo?.is_guest === 1) {
         showToast('请先登录')
         return
      }
      router.push('/payment/withdraw')
      break
    case 'trend':
      router.push(`/lottery/xy28-history/${lotteryCode.value}?tab=trend`)
      break
    case 'chase':
      switchTab('credit')
      break
    case 'chaseRecords':
      showChaseRecordsPopup.value = true
      break
    case 'roadMap':
      showRoadMapPopup.value = true
      break
    case 'hotCold':
      showHotColdPopup.value = true
      break
    case 'playRules':
      showRulesPopup.value = true
      break
    default:
      break
  }
}

function scrollToBottom() {
  if (chatMessagesRef.value) {
    chatMessagesRef.value.scrollToBottom()
  }
}

function onScroll() {

}

async function loadMoreMessages() {
  if (loadingMore.value || !hasMore.value) return
  loadingMore.value = true
  
  await new Promise(resolve => setTimeout(resolve, 1000))
  
  hasMore.value = false
  loadingMore.value = false
}

async function loadChatData() {

  try {
    const chatRes = await xy28Api.getChatMessages(lotteryCode.value, { page: 1, pageSize: 50 })
    if (chatRes.code === 0 && chatRes.data?.list) {
      messages.value = chatRes.data.list.map(msg => {
        const chatMsg = {
          id: msg.id,
          userId: msg.userId,
          userName: msg.userName,
          avatar: msg.avatar,
          honorLevel: msg.honorLevel || 0,
          content: msg.content,
          time: msg.time,
          isSystem: msg.isSystem || false,
          isWin: msg.isWin || false,
          messageType: msg.messageType || 'text'
        }
        

        if (msg.messageType === 'result') {
          chatMsg.issue = msg.issue
          chatMsg.code = msg.code || []
          chatMsg.sum = msg.sum
          chatMsg.type = msg.type
        }
        

        if (msg.messageType === 'bill') {
          chatMsg.issue = msg.issue
          chatMsg.details = msg.details || []
        }
        
        return chatMsg
      })
    } else {
      messages.value = [] // 清空，等待 WebSocket 推送
    }
  } catch (e) {
    console.error('[聊天] 加载失败:', e)
    messages.value = []
  }
  

  try {
    const res = await xy28Api.getHistory(lotteryCode.value, { page: 1, pageSize: 20 })
    if (res.code === 0 && res.data?.list) {

      historyList.value = res.data.list
        .filter(item => item.openCode && item.openCode.length >= 3 && item.openCode[0] !== '')
        .map(item => ({
          issue: item.expect,
          code: item.openCode,
          sum: item.sum || 0,
          type: getResultType(item.sum),
          time: item.openTime ? dayjs(item.openTime).format('MM-DD HH:mm') : ''
        }))
      

      if (historyList.value.length > 0) {
        const latest = historyList.value[0]
        lastIssue.value = latest.issue
        lastCode.value = latest.code
      }
    }
  } catch (e) {
    console.error('[历史] 加载失败:', e)
  }
  
  scrollToBottom()
}

function getResultType(sum) {
  const isBig = sum >= 14
  const isOdd = sum % 2 === 1
  if (isBig && isOdd) return '大单'
  if (isBig && !isOdd) return '大双'
  if (!isBig && isOdd) return '小单'
  return '小双'
}

let wsCleanups = []

function initWebSocket() {
  try {
    lotteryWS.connect()
    

    const unsubCountdown = lotteryWS.on('countdown', (data) => {
      if (data.lotteryCode !== lotteryCode.value) return
      currentIssue.value = data.currentIssue || currentIssue.value
      remainMs.value = (data.countdown || 0) * 1000
      

      if (data.status === 'drawing' || data.status === 3) {
        countdownStatus.value = 'drawing'
      } else if (data.status === 2 || data.status === 'sealed' || data.status === 'closing') {
        countdownStatus.value = 'sealed'
      } else {
        countdownStatus.value = 'countdown'
      }
    })
    wsCleanups.push(unsubCountdown)
    

    const unsubResult = lotteryWS.on('draw_result', (data) => {
      if (data.lotteryCode !== lotteryCode.value) return
      

      lastIssue.value = data.issue
      let codes = []
      if (data.openCode) {
        codes = Array.isArray(data.openCode) ? data.openCode : data.openCode.split(',').map(Number)
        lastCode.value = codes.slice(0, 3)  // 只取前3个号码
      }
      

      const sum = codes.slice(0, 3).reduce((a, b) => a + b, 0)
      const newRecord = {
        issue: data.issue,
        code: codes.slice(0, 3),
        sum: sum,
        type: data.type || getResultType(sum),
        time: dayjs().format('MM-DD HH:mm')
      }

      if (!historyList.value.find(h => h.issue === data.issue)) {
        historyList.value.unshift(newRecord)

        if (historyList.value.length > 20) {
          historyList.value.pop()
        }
      }
      

      if (scratchCardRef.value) {
        scratchCardRef.value.resetScratchCard()
      }
      

    })
    wsCleanups.push(unsubResult)
    

    const unsubChat = lotteryWS.on('lottery_chat_message', (data) => {
      if (data.lotteryCode !== lotteryCode.value) return
      const msg = data.message || data
      

      const chatMsg = {
        id: msg.id || Date.now(),
        userName: msg.userName,
        avatar: msg.avatar,
        honorLevel: msg.honorLevel || 0,
        content: msg.content || '',
        time: msg.time || dayjs().format('HH:mm:ss'),
        isSystem: msg.isSystem || false,
        isWin: msg.isWin || false,
        messageType: msg.messageType || 'text'
      }
      

      if (msg.messageType === 'result') {
        chatMsg.issue = msg.issue
        chatMsg.code = msg.code || msg.openCode
        chatMsg.sum = msg.sum
        chatMsg.type = msg.type
        chatMsg.resultData = msg.resultData || null
      }
      

      if (msg.messageType === 'bill') {
        chatMsg.issue = msg.issue
        chatMsg.details = msg.details || []
      }
      

      if (!messages.value.find(m => m.id === chatMsg.id)) {
        messages.value.push(chatMsg)

      }
    })
    wsCleanups.push(unsubChat)
    

    const unsubOnline = lotteryWS.on('lottery_chat_online', (data) => {
      if (data.lotteryCode !== lotteryCode.value) return
      onlineCount.value = data.count || 0
    })
    wsCleanups.push(unsubOnline)
    

    const unsubWin = lotteryWS.on('lottery_win', (data) => {
      if (data.lotteryCode !== lotteryCode.value) return

      winData.value = {
        issue: data.issue,
        totalBet: data.totalBet || 0,
        totalWin: data.totalWin || 0,
        profit: data.profit || 0,
        items: data.items || []
      }
      showWinAnimation.value = true
      

      refreshBalance()
    })
    wsCleanups.push(unsubWin)
    

    const unsubBetStats = lotteryWS.on('bet_stats_update', (data) => {
      if (data.lotteryCode !== lotteryCode.value) return
      if (data.issue === currentIssue.value) {
        wsBetStats.value = data.stats || {}
      }
    })
    wsCleanups.push(unsubBetStats)
    

    const unsubRecall = lotteryWS.on('message_recall', (data) => {
      if (data.lotteryCode !== lotteryCode.value) return
      const msgId = data.messageId
      if (msgId) {

        const idx = messages.value.findIndex(m => m.id === msgId)
        if (idx !== -1) {
          messages.value.splice(idx, 1)
        }
      }
    })
    wsCleanups.push(unsubRecall)
    

    const unsubUpdate = lotteryWS.on('message_update', (data) => {
      if (data.lotteryCode !== lotteryCode.value) return
      const msgId = data.messageId
      const newContent = data.content
      if (msgId && newContent) {
        const msg = messages.value.find(m => m.id === msgId)
        if (msg) {
          msg.content = newContent
        }
      }
    })
    wsCleanups.push(unsubUpdate)
    

    lotteryWS.subscribe(lotteryCode.value)
    
  } catch (e) {
    console.error('WebSocket初始化失败:', e)
  }
}

let timer = null

function startTimer() {
  timer = setInterval(() => {
    if (remainMs.value > 0) {
      remainMs.value -= 1000
    }
  }, 1000)
}

async function loadCurrentIssue() {
  try {
    const res = await xy28Api.getCurrentExpect(lotteryCode.value)
    if (res.code === 0 && res.data) {
      currentIssue.value = res.data.currFullExpect || res.data.currentIssue || currentIssue.value
      remainMs.value = res.data.remainMs || (res.data.remainTime || 0) * 1000
      

      if (res.data.lotteryTitle) {
        lotteryTitle.value = res.data.lotteryTitle
      }
      

      if (res.data.openCodes && res.data.openCodes.length > 0) {
        lastCode.value = res.data.openCodes.map(Number)
      }
      if (res.data.lastFullExpect) {
        lastIssue.value = res.data.lastFullExpect
      }
    }
  } catch (e) {
    console.error('[期号] 加载失败:', e)
  }
}

onMounted(() => {
  loadCurrentIssue()
  loadChatData()
  refreshBalance()
  initWebSocket()
  startTimer()
})

onUnmounted(() => {
  wsCleanups.forEach(fn => fn())
  lotteryWS.unsubscribe(lotteryCode.value)
  if (timer) clearInterval(timer)
})

watch(() => route.params.code, (newCode) => {
  if (newCode && newCode !== lotteryCode.value) {
    lotteryWS.unsubscribe(lotteryCode.value)
    lotteryCode.value = newCode
    lotteryWS.subscribe(newCode)
    loadChatData()
  }
})
</script>

<style lang="less" scoped>
.xy28-chat {
  display: flex;
  flex-direction: column;
  height: 100vh;
  background: #f5f5f5;
  position: relative;
}

.chat-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 57.33px;
  padding: 0 16px;
  background: linear-gradient(90deg, #01d1ff, #0aabff);
  color: #fff;
  flex-shrink: 0;
  
  .header-left {
    width: 40px;
  }
  
  .header-title {
    display: flex;
    align-items: center;
    gap: 4px;
    
    .title-text {
      font-size: 19px; 
      font-weight: 600;
    }
    
    .online-count {
      font-size: 15px;
      opacity: 0.9;
    }
    
    .title-arrow {
      font-size: 18px; 
      transform: rotate(-90deg); 
      margin-left: 4px;
      transition: transform 0.3s ease;
      padding: 4px;
      
      &.collapsed {
        transform: rotate(90deg); 
      }
    }
  }
  
  .header-right {
    display: flex;
    align-items: center;
    gap: 16px;
    
    .header-icon {
      width: 22px;
      height: 22px;
      object-fit: contain;
    }
  }
}

.bet-detail-dialog {
    .detail-content {
        padding: 0;
        
        .detail-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            
            .detail-title {
                font-size: 16px;
                font-weight: bold;
                color: #333;
            }
            
            .detail-total {
                font-size: 16px;
                color: #333;
            }
        }
        
        .detail-list {
            padding: 10px 20px;
            max-height: 200px;
            overflow-y: auto;
            
            .detail-item {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 10px 0;
                border-bottom: 1px dashed #f0f0f0;
                
                &:last-child { border-bottom: none; }
                
                .bet-icon {
                    min-width: 40px;
                    padding: 4px 10px;
                    background: #29b6f6;
                    color: #fff;
                    border-radius: 4px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 13px;
                    white-space: nowrap;
                }
                
                .bet-amount {
                    color: #f5222d;
                    font-weight: bold;
                    font-size: 14px;
                }
            }
        }
        
        .detail-footer {
            padding: 15px;
            text-align: center;
            font-size: 16px;
            color: #333;
            border-top: 1px solid #eee;
            cursor: pointer;
            font-weight: 500;
            
            &:active {
                background: #f9f9f9;
            }
        }
    }
}

.rules-popup-content {
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    
    .rules-header {
        background: #007aff;
        color: #fff;
        padding: 12px;
        text-align: center;
        font-size: 16px;
        font-weight: bold;
    }
    
    .rules-body {
        padding: 20px;
        font-size: 14px;
        color: #333;
        line-height: 1.6;
        
        p {
            margin-bottom: 8px;
        }
    }
    
    .rules-footer {
        border-top: 1px solid #eee;
        padding: 12px;
        text-align: center;
        color: #666;
        font-size: 15px;
        cursor: pointer;
        
        &:active {
            background: #f5f5f5;
        }
    }
}

.chat-input-area {
  padding: 10px 12px;
  padding-bottom: calc(10px + env(safe-area-inset-bottom));
  background: #fff;
  border-top: 1px solid #eee;
  
  .input-wrapper {
    display: flex;
    gap: 10px;
    
    .bet-input {
      flex: 1;
      height: 40px;
      padding: 0 14px;
      border: 1px solid #e0e0e0;
      border-radius: 20px;
      font-size: 14px;
      background: #f5f5f5;
      outline: none;
      
      &:focus {
        border-color: #1e88e5;
        background: #fff;
      }
      
      &::placeholder {
        color: #bbb;
      }
    }
    
    .send-btn {
      width: 64px;
      height: 40px;
      border: none;
      border-radius: 20px;
      background: linear-gradient(135deg, #ff7875, #f5222d);
      color: #fff;
      font-size: 14px;
      font-weight: 500;
      cursor: pointer;
      
      &:disabled {
        background: #999;
        cursor: not-allowed;
      }
      
      &:active:not(:disabled) {
        opacity: 0.9;
      }
    }
  }
  

  &.sealed-area {
    .bet-input {
      background: #f0f0f0;
      color: #999;
      cursor: not-allowed;
      
      &::placeholder {
        color: #999;
      }
    }
  }
}

.bottom-nav {
  display: flex;
  background: #fff;
  border-top: 1px solid #eee;
  padding-bottom: env(safe-area-inset-bottom);
  flex-shrink: 0; 
  

  &.hidden {
    visibility: hidden;
    pointer-events: none;
  }
  
  .nav-item {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 8px 0;
    color: #666;
    font-size: 11px;
    gap: 2px;
    transition: opacity 0.2s;
    
    .nav-icon {
      width: 24px;
      height: 24px;
      object-fit: contain;
    }
    

    &.disabled {
      opacity: 0.4;
      pointer-events: none;
    }
  }
}

.lottery-picker {
  padding: 16px;
  
  .picker-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 12px;
    border-bottom: 1px solid #eee;
    font-size: 16px;
    font-weight: 600;
  }
  
  .lottery-list {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    padding-top: 16px;
    
    .lottery-item {
      padding: 12px;
      text-align: center;
      background: #f5f5f5;
      border-radius: 8px;
      font-size: 14px;
      
      &.active {
        background: #e6f7ff;
        color: #1e88e5;
        font-weight: 600;
      }
    }
  }
}

.history-popup {
  height: 100%;
  display: flex;
  flex-direction: column;
  
  .popup-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px;
    border-bottom: 1px solid #eee;
    font-size: 16px;
    font-weight: 600;
  }
  
  .history-list {
    flex: 1;
    overflow-y: auto;
    padding: 12px 16px;
    
    .history-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 0;
      border-bottom: 1px solid #f5f5f5;
      
      .h-issue {
        font-size: 12px;
        color: #999;
        width: 100px;
      }
      
      .h-balls {
        display: flex;
        align-items: center;
        gap: 4px;
        flex: 1;
        
        .h-ball {
          width: 22px;
          height: 22px;
          border-radius: 50%;
          background: #1e88e5;
          color: #fff;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 11px;
        }
        
        .h-sum {
          width: 26px;
          height: 26px;
          font-size: 12px;
          
          &.sum-red { background: #f5222d; }
          &.sum-green { background: #52c41a; }
          &.sum-blue { background: #1890ff; }
        }
        
        .h-plus, .h-equal {
          font-size: 11px;
          color: #999;
        }
      }
      
      .h-type {
        font-size: 13px;
        font-weight: 600;
        color: #333;
      }
    }
  }
}

.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.3s ease;
  will-change: transform;
}

.slide-up-enter-from,
.slide-up-leave-to {
  transform: translateY(100%);
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.quick-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 1000;
}

.history-overlay {
  position: fixed;
  top: 160px;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 10;
}

.win-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.7);
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  
  .win-content {
    position: relative;
    width: 280px;
    background: linear-gradient(180deg, #fff5e6 0%, #ffffff 100%);
    border-radius: 20px;
    padding: 30px 20px;
    text-align: center;
    box-shadow: 0 10px 40px rgba(255, 150, 0, 0.3);
    overflow: hidden;
    
    .win-light {
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: conic-gradient(
        from 0deg,
        transparent 0deg,
        rgba(255, 215, 0, 0.3) 60deg,
        transparent 120deg,
        rgba(255, 215, 0, 0.3) 180deg,
        transparent 240deg,
        rgba(255, 215, 0, 0.3) 300deg,
        transparent 360deg
      );
      animation: win-rotate 3s linear infinite;
      z-index: 0;
    }
    
    .win-icon {
      position: relative;
      font-size: 60px;
      margin-bottom: 10px;
      animation: win-bounce 0.6s ease-out;
      z-index: 1;
    }
    
    .win-title {
      position: relative;
      font-size: 24px;
      font-weight: bold;
      background: linear-gradient(135deg, #ff6b00, #ff9500);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 8px;
      z-index: 1;
    }
    
    .win-issue {
      position: relative;
      font-size: 14px;
      color: #999;
      margin-bottom: 15px;
      z-index: 1;
    }
    
    .win-amount {
      position: relative;
      font-size: 36px;
      font-weight: bold;
      color: #f5222d;
      margin-bottom: 15px;
      text-shadow: 0 2px 4px rgba(245, 34, 45, 0.2);
      animation: win-scale 0.5s ease-out 0.3s both;
      z-index: 1;
    }
    
    .win-detail {
      position: relative;
      display: flex;
      justify-content: center;
      gap: 20px;
      font-size: 13px;
      color: #666;
      margin-bottom: 20px;
      z-index: 1;
    }
    
    .win-btn {
      position: relative;
      width: 100%;
      height: 44px;
      border: none;
      border-radius: 22px;
      background: linear-gradient(135deg, #ff7875, #f5222d);
      color: #fff;
      font-size: 16px;
      font-weight: 500;
      cursor: pointer;
      z-index: 1;
      
      &:active {
        opacity: 0.9;
        transform: scale(0.98);
      }
    }
  }
}

.win-animation-enter-active {
  animation: win-fadeIn 0.3s ease-out;
  
  .win-content {
    animation: win-popIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  }
}

.win-animation-leave-active {
  animation: win-fadeOut 0.2s ease-in;
  
  .win-content {
    animation: win-popOut 0.2s ease-in;
  }
}

@keyframes win-rotate {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

@keyframes win-bounce {
  0% { transform: scale(0) rotate(-30deg); }
  50% { transform: scale(1.2) rotate(10deg); }
  100% { transform: scale(1) rotate(0deg); }
}

@keyframes win-scale {
  0% { transform: scale(0); opacity: 0; }
  50% { transform: scale(1.2); }
  100% { transform: scale(1); opacity: 1; }
}

@keyframes win-fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes win-fadeOut {
  from { opacity: 1; }
  to { opacity: 0; }
}

@keyframes win-popIn {
  0% { transform: scale(0.5); opacity: 0; }
  100% { transform: scale(1); opacity: 1; }
}

@keyframes win-popOut {
  0% { transform: scale(1); opacity: 1; }
  100% { transform: scale(0.5); opacity: 0; }
}

</style>

