
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { lotteryWS } from '@/utils/websocket'
import { showNotify, showToast } from 'vant'

export const useUserStore = defineStore('user', () => {
  const balance = ref(0)  // 默认余额 0
  const unreadCount = ref(0) // 未读消息数
  const recentBets = ref([]) // 最近投注（WS 推送）

  const setBalance = (val) => {
    balance.value = Number(val) || 0
  }

  const updateBalance = (data) => {
    const { balance: newBalance, change, reason } = data
    balance.value = Number(newBalance) || 0
    

    if (change && change !== 0) {
      const reasonText = {
        bet_win: '投注中奖',
        bet_place: '投注扣款',
        bet_refund: '投注退款',
        recharge: '充值到账',
        withdraw: '提现扣款',
        withdraw_reject: '提现退回',
        activity: '活动奖励',
        rebate: '返水'
      }[reason] || '余额变动'
      
      const changeText = change > 0 ? `+${Number(change).toFixed(2)}` : Number(change).toFixed(2)
      
      showNotify({
        type: change > 0 ? 'success' : 'warning',
        message: `${reasonText}: ${changeText}元`,
        duration: 3000
      })
    }
  }

  const handleBetCreated = (data) => {
    const { orderNo, betAmount, balanceAfter } = data
    

    balance.value = Number(balanceAfter) || balance.value
    

    recentBets.value.unshift({
      orderNo,
      betAmount,
      time: Date.now()
    })

    if (recentBets.value.length > 10) {
      recentBets.value.pop()
    }
    

    showToast({
      message: `投注成功\n扣款: ${betAmount}元`,
      type: 'success',
      duration: 2000
    })
  }

  let wsCleanups = []
  

  const initWsListeners = () => {

    const unsubBalance = lotteryWS.on('balance_update', updateBalance)
    wsCleanups.push(unsubBalance)
    

    const unsubBetCreated = lotteryWS.on('bet_created', handleBetCreated)
    wsCleanups.push(unsubBetCreated)
    

    const unsubNotification = lotteryWS.on('notification', (data) => {
      const typeConfig = {
        info: { type: 'primary', duration: 5000 },
        warning: { type: 'warning', duration: 8000 },
        error: { type: 'danger', duration: 10000 }
      }
      const config = typeConfig[data.level] || typeConfig.info
      
      showNotify({
        type: config.type,
        message: `${data.title}\n${data.content}`,
        duration: config.duration
      })
      
      unreadCount.value++
    })
    wsCleanups.push(unsubNotification)
  }
  

  const cleanupWsListeners = () => {
    wsCleanups.forEach(cleanup => cleanup())
    wsCleanups = []
  }

  const init = () => {

  }

  return { 
    balance, 
    unreadCount,
    recentBets,
    setBalance, 
    updateBalance,
    handleBetCreated,
    initWsListeners,
    cleanupWsListeners,
    init 
  }
})
