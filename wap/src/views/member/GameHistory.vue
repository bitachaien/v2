<template>
  <div class="game-history-page">
    <div class="nav-header glass-panel">
      <div class="nav-left" @click="goBack">
        <van-icon name="arrow-left" class="neon-icon" />
      </div>
      <div class="nav-title">游戏记录</div>
      <div class="nav-right" @click="showFilter = true">
        <van-icon name="filter-o" class="neon-icon filter-icon" />
      </div>
    </div>

    <div class="tabs-container">
      <van-tabs 
        v-model:active="activeTab" 
        background="transparent" 
        line-width="20px" 
        line-height="3px"
        :border="false"
        class="tech-tabs"
      >
        <van-tab title="全部" name="all"></van-tab>
        <van-tab title="已中" name="win"></van-tab>
        <van-tab title="未中" name="lose"></van-tab>
        <van-tab title="待开" name="pending"></van-tab>
      </van-tabs>
    </div>

    <div class="history-list">
      <div 
        v-for="(item, index) in filteredList" 
        :key="item.id" 
        class="history-card glass-card animate-up"
        :style="{ animationDelay: `${index * 0.05}s` }"
        @click="openDetail(item)"
      >
        
        <div class="card-icon-wrapper">
          <div class="game-badge" :class="getGameClass(item.gameName)">
            <span class="emoji">{{ getGameIcon(item.gameName) }}</span>
          </div>
        </div>

        
        <div class="card-main">
          <div class="row-1">
            <span class="game-name">{{ item.gameName }}</span>
            <span class="issue">{{ item.issue }}</span>
          </div>
          <div class="row-2">
            <span class="bet-info">玩法: {{ item.playType }} | 投注: {{ formatBetContent(item) }}</span>
          </div>
          <div class="row-3">
            <span class="time">{{ item.betTime }}</span>
          </div>
        </div>

        
        <div class="card-right">
          <div class="amount" :class="item.status">
            {{ formatListAmount(item) }}
          </div>
          <div class="status-tag" :class="item.status">
            {{ getStatusText(item.status) }}
          </div>
        </div>
      </div>

      <div v-if="loading" class="loading-state">
        <van-loading color="#EAC26E" />
      </div>

      <div v-else-if="filteredList.length === 0" class="empty-state">
        <van-icon name="description" />
        <p>暂无游戏记录</p>
      </div>
    </div>

    <div class="pagination-container" v-if="totalPages > 0">
      <div class="pagination-info">共 {{ totalCount }} 条，{{ totalPages }} 页</div>
      <div class="pagination-controls">
        <div 
          class="page-btn" 
          :class="{ disabled: currentPage === 1 }"
          @click="goToPage(currentPage - 1)"
        >
          <van-icon name="arrow-left" />
        </div>
        <div class="page-numbers">
          <div 
            v-for="page in displayPages" 
            :key="page"
            class="page-num"
            :class="{ active: page === currentPage, ellipsis: page === '...' }"
            @click="page !== '...' && goToPage(page)"
          >
            {{ page }}
          </div>
        </div>
        <div 
          class="page-btn" 
          :class="{ disabled: currentPage === totalPages }"
          @click="goToPage(currentPage + 1)"
        >
          <van-icon name="arrow" />
        </div>
      </div>
    </div>

    <van-popup
      v-model:show="showDetailPopup" 
      position="bottom" 
      round 
      class="tech-popup detail-popup"
    >
      <div class="popup-drag-bar"></div>
      <div class="detail-content" v-if="currentItem">
        <div class="detail-header">
          <div class="header-top">
            <span class="game-title">{{ currentItem.gameName }}</span>
            <span class="header-issue">{{ currentItem.issue }}</span>
          </div>
          <div class="big-amount" :class="currentItem.status">
            {{ formatDetailAmount(currentItem) }}
          </div>
          <div class="detail-status-badge" :class="currentItem.status">
            {{ getStatusText(currentItem.status) }}
          </div>
        </div>

        <div class="detail-list-view">
          <div class="detail-row">
            <span class="label">玩法类型</span>
            <span class="value">{{ currentItem.playType }}</span>
          </div>
          <div class="detail-row">
            <span class="label">投注内容</span>
            <span class="value highlight">{{ formatBetContent(currentItem) }}</span>
          </div>
          <div class="detail-row">
            <span class="label">投注金额</span>
            <span class="value">{{ formatMoney(currentItem.betAmount) }}</span>
          </div>
          <div class="detail-row" v-if="currentItem.status === 'win'">
            <span class="label">中奖金额</span>
            <span class="value win-color">+{{ formatMoney(currentItem.winAmount) }}</span>
          </div>
          <div class="detail-row" v-if="currentItem.status !== 'pending'">
            <span class="label">本单盈亏</span>
            <span class="value" :class="getProfitClass(currentItem)">
              {{ formatProfit(currentItem) }}
            </span>
          </div>
          <div class="detail-row">
            <span class="label">投注时间</span>
            <span class="value">{{ currentItem.betTime }}</span>
          </div>
          <div class="detail-row" v-if="currentItem.drawTime">
            <span class="label">开奖时间</span>
            <span class="value">{{ currentItem.drawTime }}</span>
          </div>
          <div class="detail-row" v-if="currentItem.resultNumbers">
            <span class="label">开奖号码</span>
            <span class="value result-nums">{{ currentItem.resultNumbers }}</span>
          </div>
          <div class="detail-row">
            <span class="label">订单编号</span>
            <div class="value-copy">
              <span>{{ currentItem.orderNo }}</span>
              <div class="copy-btn" @click.stop="copyText(currentItem.orderNo)">复制</div>
            </div>
          </div>
          <div class="detail-row" v-if="currentItem.balanceAfter">
            <span class="label">变动后余额</span>
            <span class="value">{{ formatMoney(currentItem.balanceAfter) }}</span>
          </div>
        </div>

        <div class="detail-actions">
          <button class="action-btn outline" @click="copyText(currentItem.orderNo)">
            <van-icon name="orders-o" /> 复制订单号
          </button>
          <button class="action-btn primary" @click="rebet(currentItem)">
            <van-icon name="replay" /> 再次投注
          </button>
        </div>
      </div>
    </van-popup>

    <van-popup 
      v-model:show="showFilter"
      position="bottom" 
      round 
      class="tech-popup filter-popup"
    >
      <div class="filter-container">
        <div class="filter-header">筛选记录</div>
        
        <div class="filter-group">
          <div class="group-title">时间范围</div>
          <div class="tag-grid">
            <div 
              v-for="t in timeOptions" 
              :key="t.value"
              class="filter-tag"
              :class="{ active: filterForm.timeRange === t.value }"
              @click="filterForm.timeRange = t.value"
            >
              {{ t.label }}
            </div>
          </div>
          <div class="custom-date-row" v-if="filterForm.timeRange === 'custom'">
             <div class="date-input" @click="showStartDatePicker = true">
               {{ customStartDate || '开始日期' }}
             </div>
             <span class="date-sep">-</span>
             <div class="date-input" @click="showEndDatePicker = true">
               {{ customEndDate || '结束日期' }}
             </div>
          </div>
        </div>

        <div class="filter-group">
          <div class="group-title">彩种选择</div>
          <div class="tag-grid">
            <div 
              v-for="g in gameOptions" 
              :key="g"
              class="filter-tag"
              :class="{ active: filterForm.gameName === g }"
              @click="filterForm.gameName = g"
            >
              {{ g }}
            </div>
          </div>
        </div>

        <div class="filter-group">
          <div class="group-title">状态</div>
          <div class="tag-grid">
            <div 
              v-for="s in statusOptions" 
              :key="s.value"
              class="filter-tag"
              :class="{ active: filterForm.status === s.value }"
              @click="filterForm.status = s.value"
            >
              {{ s.label }}
            </div>
          </div>
        </div>

        <div class="filter-actions-bar">
          <button class="btn-reset" @click="resetFilter">重置</button>
          <button class="btn-confirm" @click="applyFilter">确定</button>
        </div>
      </div>
    </van-popup>

    <van-popup v-model:show="showStartDatePicker" position="bottom" round>
      <van-date-picker
        title="选择开始日期"
        :min-date="getMinDate()"
        :max-date="getMaxDate()"
        @confirm="onStartDateConfirm"
        @cancel="showStartDatePicker = false"
      />
    </van-popup>

    <van-popup v-model:show="showEndDatePicker" position="bottom" round>
      <van-date-picker
        title="选择结束日期"
        :min-date="getMinDate()"
        :max-date="getMaxDate()"
        @confirm="onEndDateConfirm"
        @cancel="showEndDatePicker = false"
      />
    </van-popup>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { showToast, showLoadingToast, closeToast } from 'vant'
import { fundApi } from '@/api/fund'
import { gameApi } from '@/api/game'
import { lotteryWS } from '@/utils/websocket'

const router = useRouter()
const route = useRoute()

let lastLoadTime = 0
const REFRESH_INTERVAL = 5000

let betSuccessChannel: BroadcastChannel | null = null

interface GameRecord {
  id: number
  gameName: string
  lotteryCode: string
  issue: string
  playType: string
  betContent: string
  betAmount: number
  winAmount: number
  profit?: number
  status: 'win' | 'lose' | 'pending'
  betTime: string
  drawTime?: string
  resultNumbers?: string
  orderNo: string
  balanceAfter: number
}

const activeTab = ref('all')
const showDetailPopup = ref(false)
const showFilter = ref(false)
const currentItem = ref<GameRecord | null>(null)

const filterForm = reactive({
  timeRange: 'all',
  gameName: '全部',
  status: 'all'
})

const timeOptions = [
  { label: '全部', value: 'all' },
  { label: '今日', value: 'today' },
  { label: '昨日', value: 'yesterday' },
  { label: '近7日', value: 'week' },
  { label: '本月', value: 'month' },
  { label: '自定义', value: 'custom' }
]

const gameOptions = ref<string[]>(['全部'])
const lotteryCodeMap = ref<Record<string, string>>({})
const lotteryTypeMap = ref<Record<string, string>>({})

const statusOptions = [
  { label: '全部', value: 'all' },
  { label: '已中奖', value: 'win' },
  { label: '未中奖', value: 'lose' },
  { label: '待开奖', value: 'pending' }
]

const rawList = ref<GameRecord[]>([])
const loading = ref(false)

const currentPage = ref(1)
const pageSize = 8
const totalCount = ref(0)

const totalPages = computed(() => Math.ceil(totalCount.value / pageSize))

const displayPages = computed(() => {
  const pages: (number | string)[] = []
  const total = totalPages.value
  const current = currentPage.value
  
  if (total <= 5) {
    for (let i = 1; i <= total; i++) pages.push(i)
  } else {
    if (current <= 3) {
      pages.push(1, 2, 3, 4, '...', total)
    } else if (current >= total - 2) {
      pages.push(1, '...', total - 3, total - 2, total - 1, total)
    } else {
      pages.push(1, '...', current - 1, current, current + 1, '...', total)
    }
  }
  return pages
})

const customStartDate = ref('')
const customEndDate = ref('')
const showStartDatePicker = ref(false)
const showEndDatePicker = ref(false)

const loadLotteryCategories = async () => {
  try {
    const res = await gameApi.getLotteryCategories()
    if (res && (res.code === 0 || res.code === 1 || res.code === 200) && res.data) {
      const options = ['全部']
      const codeMap: Record<string, string> = {}
      const { games, hotGames } = res.data
      const typeMap: Record<string, string> = {}
      
      if (games && typeof games === 'object') {
        Object.keys(games).forEach((typeId: string) => {
          const lotteries = games[typeId]
          if (Array.isArray(lotteries)) {
            lotteries.forEach((lot: any) => {
              const displayName = lot.title || lot.name
              const code = lot.name
              if (displayName && !options.includes(displayName)) {
                options.push(displayName)
                if (code) {
                  codeMap[displayName] = code
                  typeMap[displayName] = typeId
                }
              }
            })
          }
        })
      }
      
      gameOptions.value = options
      lotteryCodeMap.value = codeMap
      lotteryTypeMap.value = typeMap
    }
  } catch {
  }
}

const formatDateStr = (date: Date) => {
  const y = date.getFullYear()
  const m = String(date.getMonth() + 1).padStart(2, '0')
  const d = String(date.getDate()).padStart(2, '0')
  return `${y}-${m}-${d}`
}

const getDateRange = () => {
  const now = new Date()
  let startDate = ''
  let endDate = formatDateStr(now)
  
  switch (filterForm.timeRange) {
    case 'today':
      startDate = formatDateStr(now)
      break
    case 'yesterday':
      const yesterday = new Date(now)
      yesterday.setDate(yesterday.getDate() - 1)
      startDate = formatDateStr(yesterday)
      endDate = formatDateStr(yesterday)
      break
    case 'week':
      const weekAgo = new Date(now)
      weekAgo.setDate(weekAgo.getDate() - 7)
      startDate = formatDateStr(weekAgo)
      break
    case 'month':
      const monthStart = new Date(now.getFullYear(), now.getMonth(), 1)
      startDate = formatDateStr(monthStart)
      break
    case 'custom':
      startDate = customStartDate.value
      endDate = customEndDate.value
      break
    default:
      return { startDate: '', endDate: '' }
  }
  
  return { startDate, endDate }
}

const loadBetRecords = async () => {
  if (loading.value) return
  
  loading.value = true

  try {
    const { startDate, endDate } = getDateRange()
    const lotteryCode = filterForm.gameName !== '全部' ? lotteryCodeMap.value[filterForm.gameName] : ''
    const statusFilter = activeTab.value !== 'all' ? activeTab.value :
                         (filterForm.status !== 'all' ? filterForm.status : undefined)
    
    const res = await fundApi.getBetRecords({
      page: currentPage.value,
      pageSize: pageSize,
      startDate: startDate || undefined,
      endDate: endDate || undefined,
      lotteryCode: lotteryCode || undefined,
      status: statusFilter
    })

    const isSuccess = res && (res.code === 0 || res.code === 1 || res.code === 200 || res.code === '0' || res.code === '1' || res.code === '200')
    
    if (isSuccess && res.data) {
      let list = []
      let total = 0
      
      if (Array.isArray(res.data)) {
        list = res.data
        total = res.data.length
      } else if (Array.isArray(res.data.list)) {
        list = res.data.list
        total = res.data.total || res.data.count || list.length
      } else if (Array.isArray(res.data.data)) {
        list = res.data.data
        total = res.data.total || res.data.count || list.length
      }
      
      totalCount.value = total
      
      rawList.value = list.map((item: any) => {
        let status: 'win' | 'lose' | 'pending' = 'pending'
        
        if (item.status) {
          status = item.status as 'win' | 'lose' | 'pending'
        } else {
          const winAmount = parseFloat(item.winAmount || item.okamount || '0')
          
          if (item.isdraw == 1) {
            status = winAmount > 0 ? 'win' : 'lose'
          } else if (item.isdraw == -1) {
            status = 'lose'
          } else if (item.isdraw == 0) {
            status = 'pending'
          }
        }

        return {
          id: item.id || 0,
          gameName: item.gameName || item.cptitle || '',
          lotteryCode: item.lotteryCode || item.cpname || '',
          issue: item.issue || item.expect || '',
          playType: item.playCategory || item.playType || item.playtitle || '标准玩法',
          betContent: item.betContent || item.tzcode || '',
          betAmount: parseFloat(item.betAmount || item.amount || '0'),
          winAmount: parseFloat(item.winAmount || item.okamount || '0'),
          profit: parseFloat(item.profit || '0'),
          status: status,
          betTime: item.betTime || item.oddtime || '',
          drawTime: item.drawTime || '',
          resultNumbers: item.resultNumbers || item.drawNumber || item.opencode || '',
          orderNo: item.orderNo || item.trano || '',
          balanceAfter: 0
        }
      })

    } else {
      rawList.value = []
    }
  } catch {
    rawList.value = []
  } finally {
    loading.value = false
  }
}

const goToPage = (page: number | string) => {
  if (typeof page === 'string') return
  if (page < 1 || page > totalPages.value) return
  if (page === currentPage.value) return
  
  currentPage.value = page
  loadBetRecords()
}

const filteredList = computed(() => rawList.value)

watch(activeTab, () => {
  currentPage.value = 1
  loadBetRecords()
})

const goBack = () => router.go(-1)

const getGameIcon = (name: string) => {
  if (name.includes('时时彩')) return '🎲'
  if (name.includes('快3')) return '⚀'
  if (name.includes('赛车') || name.includes('PK10')) return '🏎'
  if (name.includes('快乐8') || name.includes('六合')) return '🎯'
  return '🎮'
}

const getGameClass = (name: string) => {
  if (name.includes('时时彩')) return 'game-ssc'
  if (name.includes('快3')) return 'game-k3'
  if (name.includes('赛车')) return 'game-pk10'
  return 'game-default'
}

const getStatusText = (status: string) => {
  switch(status) {
    case 'win': return '已中奖'
    case 'lose': return '未中奖'
    case 'pending': return '待开奖'
    default: return status
  }
}

const animalNames: Record<string, string> = {
  '1': '饿小宝',
  '2': '盒马',
  '3': '票票',
  '4': '虾仔',
  '5': '支小宝',
  '6': '欢猩'
}

const rankNames: Record<string, string> = {
  'rank1': '冠军',
  'rank2': '亚军',
  'rank3': '季军',
  'rank4': '第四',
  'rank5': '第五',
  'rank6': '第六'
}

const parseAnimalBetContent = (tzcode: string): string => {
  if (!tzcode || !tzcode.includes('rank')) return tzcode
  
  try {
    const parts = tzcode.split('|')
    const readable = parts.map(part => {
      const [rankId, animalId] = part.split(':')
      const rankName = rankNames[rankId] || rankId
      const animalName = animalNames[animalId] || animalId
      return `${rankName}:「${animalName}」`
    })
    return readable.join(', ')
  } catch {
    return tzcode
  }
}

const formatBetContent = (item: GameRecord): string => {
  const content = item.betContent || ''
  const code = item.lotteryCode || ''
  
  if (code.includes('dwc') || content.includes('rank')) {
    return parseAnimalBetContent(content)
  }
  
  return content
}

const formatListAmount = (item: GameRecord) => {
  if (item.status === 'win') return `+${item.winAmount.toFixed(2)}`
  return `-${item.betAmount.toFixed(2)}`
}

const formatDetailAmount = (item: GameRecord) => {
  if (item.status === 'win') return `+${item.winAmount.toFixed(2)}`
  if (item.status === 'pending') return `-${item.betAmount.toFixed(2)}`
  return `-${item.betAmount.toFixed(2)}`
}

const formatMoney = (val: number) => val.toFixed(2)

const formatProfit = (item: GameRecord) => {
  if (item.status === 'pending') return '0.00'
  const profit = item.winAmount - item.betAmount
  return (profit >= 0 ? '+' : '') + profit.toFixed(2)
}

const getProfitClass = (item: GameRecord) => {
  const profit = item.winAmount - item.betAmount
  return profit >= 0 ? 'win-color' : 'lose-color'
}

const openDetail = (item: GameRecord) => {
  currentItem.value = item
  showDetailPopup.value = true
}

const copyText = (text: string) => {
  if (!text) {
    showToast('无内容可复制')
  return
  }
  
  if (navigator.clipboard && window.isSecureContext) {
    navigator.clipboard.writeText(text).then(() => {
      showToast('复制成功')
    }).catch(() => {
      fallbackCopy(text)
    })
  } else {
    fallbackCopy(text)
  }
}

const fallbackCopy = (text: string) => {
  const textarea = document.createElement('textarea')
  textarea.value = text
  textarea.style.cssText = 'position:fixed;top:0;left:0;width:2em;height:2em;padding:0;border:none;outline:none;box-shadow:none;background:transparent;'
  document.body.appendChild(textarea)
  textarea.focus()
  textarea.select()
  
  try {
    const success = document.execCommand('copy')
    showToast(success ? '复制成功' : '复制失败')
  } catch (err) {
    showToast('复制失败')
  }
  
  document.body.removeChild(textarea)
}

const rebet = (item: GameRecord) => {
  showDetailPopup.value = false
  const code = item.lotteryCode || ''
  const gameName = item.gameName || ''
  
  if (!code) {
    showToast('无法获取彩种信息')
    router.push('/lottery/hall')
    return
  }
  
  let typeId = ''
  if (code.includes('dwc') || gameName.includes('动物彩')) {
    typeId = 'pk10-animal'
  } else {
    typeId = lotteryTypeMap.value[gameName] || ''
  }
  
  if (!typeId) {
    if (code.includes('ssc') || gameName.includes('时时彩')) {
      typeId = 'ssc'
    } else if (code.includes('k3') || gameName.includes('快3') || gameName.includes('快三')) {
      typeId = 'k3'
    } else if (code.includes('pk10') || gameName.includes('赛车') || gameName.includes('PK10')) {
      typeId = 'pk10'
    } else if (code.includes('x5') || code.includes('11x5') || gameName.includes('11选5')) {
      typeId = 'x5'
    } else if (code.includes('kl8') || code.includes('keno') || gameName.includes('快乐8')) {
      typeId = 'keno'
    } else if (code.includes('lhc') || gameName.includes('六合彩')) {
      typeId = 'lhc'
    } else if (code.includes('28') || code.includes('pc') || gameName.includes('幸运') || gameName.includes('PC')) {
      typeId = 'xy28'
    } else if (code.includes('fc3d') || code.includes('3d') || gameName.includes('3D')) {
      typeId = 'fc3d'
    } else if (code.includes('pl3') || gameName.includes('排列三')) {
      typeId = 'pl3'
    } else {
      typeId = 'ssc'
    }
  }
  
  const routePath = `/lottery/${typeId}/${code}`
  router.push(routePath)
}

const applyFilter = () => {
  showFilter.value = false
  currentPage.value = 1
  loadBetRecords()
}

const resetFilter = () => {
  filterForm.timeRange = 'all'
  filterForm.gameName = '全部'
  filterForm.status = 'all'
  customStartDate.value = ''
  customEndDate.value = ''
  currentPage.value = 1
  loadBetRecords()
}

const onStartDateConfirm = ({ selectedValues }: { selectedValues: string[] }) => {
  customStartDate.value = selectedValues.join('-')
  showStartDatePicker.value = false
}

const onEndDateConfirm = ({ selectedValues }: { selectedValues: string[] }) => {
  customEndDate.value = selectedValues.join('-')
  showEndDatePicker.value = false
}

const getMinDate = () => {
  const date = new Date()
  date.setMonth(date.getMonth() - 3)
  return date
}

const getMaxDate = () => new Date()

let lastVisibleTime = Date.now()
const handleVisibilityChange = () => {
  if (document.visibilityState === 'visible') {
    const now = Date.now()
    if (now - lastVisibleTime > 30000) {
      loadBetRecords()
    }
  } else {
    lastVisibleTime = Date.now()
  }
}

const handleWsBetCreated = (data: any) => {
  if (!data || !data.orderNo) return
  const exists = rawList.value.some(item => item.orderNo === data.orderNo)
  if (exists) return
  rawList.value.unshift({
    id: Date.now(),
    gameName: data.gameName || '',
    issue: data.issue || '',
    playType: data.playCategory || data.playType || '',
    betContent: data.betContent || '',
    betAmount: parseFloat(data.betAmount || '0'),
    winAmount: 0,
    status: 'pending',
    betTime: data.betTime || new Date().toLocaleString(),
    orderNo: data.orderNo,
    balanceAfter: parseFloat(data.balanceAfter || '0')
  })
}

const handleWsBetSettled = (data: any) => {
  if (!data) return
  const { orderNo, trano, status, winAmount, okamount, drawNumber, opencode } = data
  const targetOrderNo = orderNo || trano
  
  if (!targetOrderNo) {
    loadBetRecords()
    return
  }
  
  const index = rawList.value.findIndex(item => item.orderNo === targetOrderNo)
  
  if (index !== -1) {
    const record = rawList.value[index]
    const newWinAmount = parseFloat(winAmount || okamount || '0')
    const newStatus = status || (newWinAmount > 0 ? 'win' : 'lose')
    const newDrawNumber = drawNumber || opencode || ''
    rawList.value[index] = {
      ...record,
      status: newStatus as 'win' | 'lose' | 'pending',
      winAmount: newWinAmount,
      resultNumbers: newDrawNumber || record.resultNumbers
    }
    if (newStatus === 'win' && newWinAmount > 0) {
      showToast({
        message: `订单 ${targetOrderNo.slice(-6)} 中奖 +${newWinAmount.toFixed(2)}`,
        type: 'success'
      })
    }
  }
}

onMounted(() => {
  loadLotteryCategories()
  loadBetRecords()
  document.addEventListener('visibilitychange', handleVisibilityChange)
  lotteryWS.on('bet_created', handleWsBetCreated)
  lotteryWS.on('bet_settled', handleWsBetSettled)
})

onUnmounted(() => {
  document.removeEventListener('visibilitychange', handleVisibilityChange)
  lotteryWS.off('bet_created', handleWsBetCreated)
  lotteryWS.off('bet_settled', handleWsBetSettled)
  
  if (betSuccessChannel) {
    betSuccessChannel.close()
    betSuccessChannel = null
  }
})
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap');

.game-history-page {
  --bg-dark: #05070E;
  --bg-darker: #0B0E15;
  --gold: #EAC26E;
  --green: #34F5A3;
  --blue: #3BA7FF;
  --purple: #7C4DFF;
  --red: #FF4D6A;
  --text-light: #E6EBFF;
  --text-gray: #9CA4C7;
  --border: rgba(255, 255, 255, 0.06);

  min-height: 100vh;
  background: linear-gradient(to bottom, var(--bg-dark), var(--bg-darker));
  color: var(--text-light);
  font-family: -apple-system, BlinkMacSystemFont, 'PingFang SC', sans-serif;
  padding-bottom: 40px;
}

.nav-header {
  position: sticky;
  top: 0;
  z-index: 100;
  height: 50px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 16px;
  border-bottom: 1px solid var(--border);
}

.glass-panel {
  background: rgba(16, 24, 40, 0.75);
  backdrop-filter: blur(18px);
}

.nav-title {
  font-size: 18px;
  font-weight: bold;
  color: var(--gold);
  text-shadow: 0 0 10px rgba(234, 194, 110, 0.3);
}

.neon-icon {
  font-size: 22px;
  color: var(--text-light);
}
.neon-icon.filter-icon {
  color: var(--blue);
  filter: drop-shadow(0 0 2px rgba(59, 167, 255, 0.5));
}

.tabs-container {
  position: sticky;
  top: 50px;
  z-index: 99;
  background: rgba(5, 7, 14, 0.9);
  backdrop-filter: blur(10px);
}

:deep(.van-tab) {
  color: var(--text-gray);
  font-size: 15px;
}
:deep(.van-tab--active) {
  color: var(--gold);
  font-weight: 600;
}
:deep(.van-tabs__line) {
  background: linear-gradient(90deg, var(--gold), #F9E6A8);
  width: 20px;
  bottom: 6px;
  box-shadow: 0 0 8px rgba(234, 194, 110, 0.5);
}

.history-list {
  padding: 16px;
  min-height: calc(100vh - 250px);
}

.loading-state {
  text-align: center;
  padding: 40px 0;
}

.history-card {
  display: flex;
  padding: 16px;
  margin-bottom: 12px;
  cursor: pointer;
  transition: all 0.3s;
  position: relative;
}

.glass-card {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 18px;
  backdrop-filter: blur(12px);
  box-shadow: 0 12px 30px rgba(0, 0, 0, 0.3);
}

.history-card:active {
  transform: translateY(-2px);
  background: rgba(255, 255, 255, 0.05);
  box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4), 0 0 15px rgba(234, 194, 110, 0.1);
}

.card-icon-wrapper {
  margin-right: 12px;
  display: flex;
  align-items: center;
}

.game-badge {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  background: rgba(255, 255, 255, 0.05);
  box-shadow: 0 0 12px rgba(255, 255, 255, 0.1);
}

.game-ssc { box-shadow: 0 0 15px rgba(124, 77, 255, 0.3); background: radial-gradient(circle, rgba(124, 77, 255, 0.2), transparent); }
.game-k3 { box-shadow: 0 0 15px rgba(52, 245, 163, 0.3); background: radial-gradient(circle, rgba(52, 245, 163, 0.2), transparent); }
.game-pk10 { box-shadow: 0 0 15px rgba(255, 77, 106, 0.3); background: radial-gradient(circle, rgba(255, 77, 106, 0.2), transparent); }
.game-default { box-shadow: 0 0 15px rgba(59, 167, 255, 0.3); background: radial-gradient(circle, rgba(59, 167, 255, 0.2), transparent); }

.card-main {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  overflow: hidden;
}

.row-1 {
  display: flex;
  align-items: center;
  gap: 8px;
}
.game-name {
  font-size: 15px;
  font-weight: bold;
  color: #fff;
}
.issue {
  font-size: 12px;
  color: var(--text-gray);
  background: rgba(255, 255, 255, 0.05);
  padding: 1px 4px;
  border-radius: 4px;
}

.row-2 {
  font-size: 12px;
  color: var(--text-gray);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.row-3 .time {
  font-size: 11px;
  color: rgba(255, 255, 255, 0.4);
}

.card-right {
  text-align: right;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: flex-end;
  gap: 4px;
  min-width: 80px;
}

.amount {
  font-family: 'Orbitron', sans-serif;
  font-size: 15px;
  font-weight: bold;
}
.amount.win { color: var(--green); text-shadow: 0 0 8px rgba(52, 245, 163, 0.4); }
.amount.lose { color: var(--red); text-shadow: 0 0 8px rgba(255, 77, 106, 0.4); }
.amount.pending { color: #fff; }

.status-tag {
  font-size: 10px;
  padding: 2px 8px;
  border-radius: 8px;
  font-weight: 600;
}
.status-tag.win { color: var(--green); background: rgba(52, 245, 163, 0.15); border: 1px solid rgba(52, 245, 163, 0.3); }
.status-tag.lose { color: var(--text-gray); background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); }
.status-tag.pending { color: var(--blue); background: rgba(59, 167, 255, 0.15); border: 1px solid rgba(59, 167, 255, 0.3); }

.animate-up {
  animation: slideUp 0.5s ease forwards;
  opacity: 0;
  transform: translateY(20px);
}
@keyframes slideUp {
  to { opacity: 1; transform: translateY(0); }
}

.empty-state {
  text-align: center;
  padding-top: 60px;
  color: rgba(255, 255, 255, 0.3);
  font-size: 14px;
}
.empty-state .van-icon { font-size: 48px; margin-bottom: 10px; }

.pagination-container {
  position: sticky;
  bottom: 0;
  background: rgba(5, 7, 14, 0.95);
  backdrop-filter: blur(10px);
  border-top: 1px solid var(--border);
  padding: 12px 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.pagination-info {
  font-size: 12px;
  color: var(--text-gray);
}

.pagination-controls {
  display: flex;
  align-items: center;
  gap: 8px;
}

.page-btn {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  color: var(--text-light);
  cursor: pointer;
  transition: all 0.2s;
}

.page-btn:active {
  background: rgba(255, 255, 255, 0.1);
}

.page-btn.disabled {
  opacity: 0.3;
  pointer-events: none;
}

.page-numbers {
  display: flex;
  gap: 4px;
}

.page-num {
  min-width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  color: var(--text-gray);
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid transparent;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
}

.page-num:active {
  background: rgba(255, 255, 255, 0.08);
}

.page-num.active {
  color: var(--gold);
  background: rgba(234, 194, 110, 0.15);
  border-color: rgba(234, 194, 110, 0.3);
  font-weight: 600;
}

.page-num.ellipsis {
  cursor: default;
  background: transparent;
  border: none;
}

.tech-popup {
  background: #121620 !important;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.popup-drag-bar {
  width: 40px;
  height: 4px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 2px;
  margin: 10px auto;
}

.detail-content {
  padding: 20px 24px 40px;
}

.detail-header {
  text-align: center;
  margin-bottom: 30px;
}

.header-top {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 8px;
  margin-bottom: 10px;
  color: var(--text-gray);
}
.game-title { font-size: 16px; color: #fff; font-weight: bold; }
.header-issue { font-size: 12px; background: rgba(255, 255, 255, 0.1); padding: 2px 6px; border-radius: 4px; }

.big-amount {
  font-family: 'Orbitron', sans-serif;
  font-size: 32px;
  font-weight: bold;
  margin-bottom: 10px;
}
.big-amount.win { color: var(--green); text-shadow: 0 0 15px rgba(52, 245, 163, 0.4); }
.big-amount.lose { color: var(--red); }
.big-amount.pending { color: #fff; }

.detail-status-badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: bold;
}
.detail-status-badge.win { background: rgba(52, 245, 163, 0.2); color: var(--green); }
.detail-status-badge.lose { background: rgba(255, 255, 255, 0.1); color: var(--text-gray); }
.detail-status-badge.pending { background: rgba(59, 167, 255, 0.2); color: var(--blue); }

.detail-list-view {
  margin-bottom: 30px;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  padding: 10px 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  font-size: 14px;
}

.detail-row .label { color: var(--text-gray); }
.detail-row .value { color: #fff; max-width: 60%; text-align: right; word-break: break-all; }
.detail-row .value.highlight { color: var(--gold); }
.detail-row .value.win-color { color: var(--green); }
.detail-row .value.lose-color { color: var(--red); }
.detail-row .value.result-nums { color: var(--blue); letter-spacing: 1px; font-weight: bold; }

.value-copy {
  display: flex;
  align-items: center;
  gap: 8px;
}
.copy-btn {
  font-size: 10px;
  color: var(--blue);
  border: 1px solid rgba(59, 167, 255, 0.3);
  padding: 1px 6px;
  border-radius: 4px;
  cursor: pointer;
}

.detail-actions {
  display: flex;
  gap: 16px;
}

.action-btn {
  flex: 1;
  height: 44px;
  border-radius: 22px;
  border: none;
  font-size: 14px;
  font-weight: bold;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  cursor: pointer;
  transition: all 0.3s;
}

.action-btn.outline {
  background: transparent;
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: #fff;
}
.action-btn.outline:active { background: rgba(255, 255, 255, 0.05); }

.action-btn.primary {
  background: linear-gradient(135deg, var(--gold), #F9E6A8);
  color: #0B0E15;
  box-shadow: 0 0 15px rgba(234, 194, 110, 0.3);
}
.action-btn.primary:active { transform: scale(0.98); box-shadow: 0 0 8px rgba(234, 194, 110, 0.2); }

.filter-popup {
  max-height: 80%;
}
.filter-container {
  padding: 20px;
}
.filter-header {
  text-align: center;
  font-size: 16px;
  font-weight: bold;
  color: #fff;
  margin-bottom: 24px;
}

.filter-group {
  margin-bottom: 24px;
}
.group-title {
  font-size: 14px;
  color: var(--gold);
  margin-bottom: 12px;
  font-weight: 600;
}

.tag-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.filter-tag {
  padding: 6px 16px;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  font-size: 12px;
  color: var(--text-gray);
  transition: all 0.3s;
  cursor: pointer;
}
.filter-tag.active {
  background: rgba(234, 194, 110, 0.15);
  border-color: var(--gold);
  color: var(--gold);
  box-shadow: 0 0 8px rgba(234, 194, 110, 0.2);
}

.custom-date-row {
  display: flex;
  align-items: center;
  margin-top: 12px;
  gap: 10px;
}
.date-input {
  flex: 1;
  height: 36px;
  background: rgba(0, 0, 0, 0.3);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  color: var(--text-gray);
}
.date-sep { color: var(--text-gray); }

.filter-actions-bar {
  display: flex;
  gap: 16px;
  margin-top: 40px;
}
.btn-reset {
  flex: 1;
  height: 44px;
  border: 1px solid rgba(255, 255, 255, 0.2);
  background: transparent;
  color: rgba(255, 255, 255, 0.8);
  border-radius: 22px;
  font-size: 14px;
}
.btn-confirm {
  flex: 2;
  height: 44px;
  border: none;
  background: linear-gradient(135deg, var(--gold), #F9E6A8);
  color: #0B0E15;
  border-radius: 22px;
  font-weight: bold;
  font-size: 14px;
  box-shadow: 0 0 15px rgba(234, 194, 110, 0.3);
}
</style>
