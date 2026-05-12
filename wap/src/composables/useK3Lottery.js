

import { ref, computed, watch, onMounted, onUnmounted, onActivated } from 'vue'
import { useRoute } from 'vue-router'
import wsService from '@/utils/websocket'
import { k3Api } from '@/api'
import { COUNTDOWN, STATUS, K3_PLAY_MODES, WEBSOCKET } from '@/constants/lottery'
import { trackLotterySwitch, trackWsStatus } from '@/utils/tracker'

export function useK3Lottery(options = {}) {
  const route = useRoute()
  const {
    defaultCode = 'jsk3',
    onDrawResult = null,
    onCountdownEnd = null,
  } = options

  const lotteryCode = ref(route.params.code || defaultCode)
  const currentIssue = ref('')
  const lastIssue = ref('')
  const lastOpenCode = ref([])
  const countdown = ref(0)
  const status = ref(STATUS.OPEN)
  const isLoading = ref(false)
  const wsConnected = ref(false)
  

  const countdownTimer = ref(null)
  const isCountdownWarning = computed(() => countdown.value <= COUNTDOWN.WARNING_THRESHOLD && countdown.value > 0)
  const isCountdownCritical = computed(() => countdown.value <= COUNTDOWN.CRITICAL_THRESHOLD && countdown.value > 0)
  

  const playModes = K3_PLAY_MODES
  const activePlayId = ref('hezhi')
  const currentPlayMode = computed(() => 
    playModes.find(p => p.id === activePlayId.value) || playModes[0]
  )

  const formattedCountdown = computed(() => {
    const s = Math.max(0, countdown.value)
    const minutes = Math.floor(s / 60)
    const seconds = s % 60
    return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`
  })

  const canBet = computed(() => status.value === STATUS.OPEN && countdown.value > 0)

  
  
  function parseOpenCode(openCode) {
    if (!openCode) return []
    if (Array.isArray(openCode)) return openCode.map(Number)
    if (typeof openCode === 'string') {
      return openCode.split(',').map(s => parseInt(s.trim(), 10)).filter(n => !isNaN(n))
    }
    return []
  }

  
  function handleWsSubscribed(data) {
    if (data.lotteryCode !== lotteryCode.value) return
    
    currentIssue.value = data.currentIssue || data.issue || ''
    

    if (data.lastIssue) {
      lastIssue.value = data.lastIssue
    }
    if (data.lastOpenCode) {
      lastOpenCode.value = parseOpenCode(data.lastOpenCode)
    }
    

    if (typeof data.countdown === 'number') {
      setCountdown(data.countdown)
    }
    

    if (typeof data.status === 'number') {
      status.value = data.status
    }
  }

  
  function handleWsCountdown(data) {
    if (data.lotteryCode !== lotteryCode.value) return
    

    if (data.issue && data.issue !== currentIssue.value) {
      currentIssue.value = data.issue
    }
    

    if (typeof data.countdown === 'number') {
      setCountdown(data.countdown)
    }
    

    if (typeof data.status === 'number') {
      status.value = data.status
    }
  }

  
  function handleWsDrawResult(data) {
    if (data.lotteryCode !== lotteryCode.value) return
    
    lastIssue.value = data.issue || ''
    lastOpenCode.value = parseOpenCode(data.openCode)
    

    if (data.nextIssue) {
      currentIssue.value = data.nextIssue
    }
    if (typeof data.nextCountdown === 'number') {
      setCountdown(data.nextCountdown)
    }
    

    onDrawResult?.(data)
  }

  
  function setCountdown(value) {

    const clampedValue = Math.max(
      COUNTDOWN.MIN_VALID_VALUE,
      Math.min(COUNTDOWN.MAX_VALID_VALUE, value)
    )
    countdown.value = clampedValue
    startCountdownTimer()
  }

  
  function startCountdownTimer() {
    stopCountdownTimer()
    
    countdownTimer.value = setInterval(() => {
      if (countdown.value > 0) {
        countdown.value--
        

        if (countdown.value === 0) {
          status.value = STATUS.CLOSED
          onCountdownEnd?.()
        }
      }
    }, 1000)
  }

  
  function stopCountdownTimer() {
    if (countdownTimer.value) {
      clearInterval(countdownTimer.value)
      countdownTimer.value = null
    }
  }

  
  function subscribeWs() {

    wsService.on('subscribed', handleWsSubscribed)
    wsService.on('countdown', handleWsCountdown)
    wsService.on('draw_result', handleWsDrawResult)
    

    wsService.on('open', () => {
      wsConnected.value = true
      trackWsStatus('connect')

      wsService.subscribe(lotteryCode.value)
    })
    
    wsService.on('close', () => {
      wsConnected.value = false
      trackWsStatus('disconnect')
    })
    

    wsService.subscribe(lotteryCode.value)
  }

  
  function unsubscribeWs() {
    wsService.off('subscribed', handleWsSubscribed)
    wsService.off('countdown', handleWsCountdown)
    wsService.off('draw_result', handleWsDrawResult)
    wsService.unsubscribe(lotteryCode.value)
  }

  
  async function fetchCurrentIssue() {
    if (isLoading.value) return
    
    isLoading.value = true
    try {
      const res = await k3Api.getCurrentIssue(lotteryCode.value)
      if (res.code === 0 && res.data) {
        const data = res.data
        
        currentIssue.value = data.issue || data.currentIssue || ''
        
        if (data.lastIssue) {
          lastIssue.value = data.lastIssue
        }
        if (data.lastOpenCode) {
          lastOpenCode.value = parseOpenCode(data.lastOpenCode)
        }
        if (typeof data.countdown === 'number') {
          setCountdown(data.countdown)
        }
        if (typeof data.status === 'number') {
          status.value = data.status
        }
      }
    } catch (error) {
      console.error('[useK3Lottery] fetchCurrentIssue error:', error)
    } finally {
      isLoading.value = false
    }
  }

  
  function switchLottery(newCode) {
    if (newCode === lotteryCode.value) return
    
    const oldCode = lotteryCode.value
    

    wsService.unsubscribe(oldCode)
    

    lotteryCode.value = newCode
    

    currentIssue.value = ''
    countdown.value = 0
    status.value = STATUS.OPEN
    

    wsService.subscribe(newCode)
    

    fetchCurrentIssue()
    

    trackLotterySwitch(oldCode, newCode)
  }

  
  function switchPlayMode(playId) {
    if (playId && playId !== activePlayId.value) {
      activePlayId.value = playId
    }
  }

  watch(
    () => route.params.code,
    (newCode) => {
      if (newCode && newCode !== lotteryCode.value) {
        switchLottery(newCode)
      }
    }
  )

  onMounted(() => {
    subscribeWs()
    fetchCurrentIssue()
  })

  onActivated(() => {
    if (!wsConnected.value) {
      subscribeWs()
    }
    fetchCurrentIssue()
  })

  onUnmounted(() => {
    stopCountdownTimer()
    unsubscribeWs()
  })

  return {

    lotteryCode,
    currentIssue,
    lastIssue,
    lastOpenCode,
    countdown,
    status,
    isLoading,
    wsConnected,
    

    formattedCountdown,
    canBet,
    isCountdownWarning,
    isCountdownCritical,
    

    playModes,
    activePlayId,
    currentPlayMode,
    

    parseOpenCode,
    fetchCurrentIssue,
    switchLottery,
    switchPlayMode,
    setCountdown,
    

    subscribeWs,
    unsubscribeWs,
  }
}

export default useK3Lottery
