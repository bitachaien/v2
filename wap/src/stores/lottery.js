
import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useApi } from '@/composables/useApi'

export const useLotteryStore = defineStore('lottery', () => {
  const api = useApi()

  const currFullExpect = ref('---')
  const lastFullExpect = ref('---')
  const openCodes = ref([0, 0, 0]) // [百, 十, 个]
  const remainSeconds = ref(600) // 倒计时秒数

  const history = ref([])

  const init = async (lotteryName) => {
    try {
      const res = await api.get(`/Game/current?code=${lotteryName}`)
      currFullExpect.value = res.data.currExpect || '---'
      lastFullExpect.value = res.data.lastExpect || '---'
      openCodes.value = res.data.openCode ? res.data.openCode.split(',').map(Number) : [0,0,0]
      remainSeconds.value = res.data.remainSeconds || 600
    } catch (e) {
      console.error('Lottery init failed:', e)
    }
  }

  const fetchCurrent = async (lotteryName) => {
    await init(lotteryName)
  }

  const fetchHistory = async (lotteryName) => {
    try {
      const res = await api.get(`/Game/history?code=${lotteryName}&limit=20`)
      history.value = res.data || []
    } catch (e) {
      console.error('History fetch failed:', e)
      history.value = []
    }
  }

  return {
    currFullExpect,
    lastFullExpect,
    openCodes,
    remainSeconds,
    history,
    init,
    fetchCurrent,
    fetchHistory
  }
})