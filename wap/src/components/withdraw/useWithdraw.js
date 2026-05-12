import { ref, computed, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { showToast, showConfirmDialog } from 'vant'
import { withdrawApi } from '@/api/withdraw'
import { securityApi } from '@/api/security'

const balance = ref(0)
const minAmount = ref(100)
const maxAmount = ref(500000)
const hasFundPassword = ref(false)

const savedAccounts = ref([])
const selectedAccount = ref(null)

const records = ref([])
const totalWithdraw = ref(0)
const recordsPage = ref(1)
const recordsTotal = ref(0)
const isRecordsLoading = ref(false)

const isRefreshing = ref(false)
const isBalanceRefreshing = ref(false)

const allAccountTypes = [
  { type: 'usdt', name: '数字货币(USDT)' },
  { type: 'bank', name: '银行卡' },
  { type: 'alipay', name: '支付宝' },
  { type: 'wechat', name: '微信' }
]

const timeFilter = ref(0)
const statusFilter = ref('')
const showAllHistoryMode = ref(false)

const timeOptions = [
  { text: '今日', value: 'today' },
  { text: '昨日', value: 'yesterday' },
  { text: '近7日', value: 'week' },
  { text: '近30日', value: 'month' },
  { text: '自定义', value: 'custom' }
]

const statusOptions = [
  { text: '全部状态', value: '' },
  { text: '出款中', value: 0 },
  { text: '提现成功', value: 1 },
  { text: '提现拒绝', value: 2 },
  { text: '提现取消', value: 3 }
]

const customStartDate = ref('')
const customEndDate = ref('')

export function useWithdraw() {
  const router = useRouter()
  const route = useRoute()

  const hasMoreRecords = computed(() => records.value.length < recordsTotal.value)

  const unboundAccountTypes = computed(() => {
    return allAccountTypes.filter(item => !hasAccountType(item.type))
  })

  const hasAccountType = (type) => {
    return savedAccounts.value.some(acc => acc.type === type)
  }

  const getAccountByType = (type) => {
    return savedAccounts.value.find(acc => acc.type === type)
  }

  const formatDate = (date) => {
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    return `${year}-${month}-${day}`
  }

  const loadConfig = async () => {
    try {
      const res = await withdrawApi.getConfig()
      if (res.code === 0) {
        balance.value = res.data.balance || 0
        minAmount.value = res.data.minAmount || 100
        maxAmount.value = res.data.maxAmount || 500000
      }
    } catch (e) {
    }
  }

  const loadSecurityInfo = async () => {
    try {
      const res = await securityApi.getInfo()
      if (res.code === 0) {
        hasFundPassword.value = res.data.hasFundPwd || false
      }
    } catch (e) {
    }
  }

  const loadAccounts = async () => {
    try {
      const res = await withdrawApi.getAccounts()
      if (res.code === 0) {
        savedAccounts.value = res.data || []
        
        if (savedAccounts.value.length === 1 && !savedAccounts.value[0].isDefault) {
          const acc = savedAccounts.value[0]
          withdrawApi.setDefaultAccount(acc.id).then(res => {
            if (res.code === 0) {
              acc.isDefault = true
            }
          }).catch(() => {})
        }
        if (!selectedAccount.value && savedAccounts.value.length > 0) {
          const defaultAcc = savedAccounts.value.find(a => a.isDefault)
          selectedAccount.value = defaultAcc || savedAccounts.value[0]
        }
      }
    } catch (e) {
    }
  }

  const buildRecordParams = (page = 1) => {
    const params = { page, pageSize: 20 }
    
    if (!showAllHistoryMode.value) {
      const timeValue = timeOptions[timeFilter.value]?.value
      const now = new Date()
      const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())
      
      if (timeValue === 'today') {
        params.startDate = formatDate(today)
        params.endDate = formatDate(today)
      } else if (timeValue === 'yesterday') {
        const yesterday = new Date(today)
        yesterday.setDate(yesterday.getDate() - 1)
        params.startDate = formatDate(yesterday)
        params.endDate = formatDate(yesterday)
      } else if (timeValue === 'week') {
        const weekAgo = new Date(today)
        weekAgo.setDate(weekAgo.getDate() - 6)
        params.startDate = formatDate(weekAgo)
        params.endDate = formatDate(today)
      } else if (timeValue === 'month') {
        const monthAgo = new Date(today)
        monthAgo.setDate(monthAgo.getDate() - 29)
        params.startDate = formatDate(monthAgo)
        params.endDate = formatDate(today)
      } else if (timeValue === 'custom' && customStartDate.value && customEndDate.value) {
        params.startDate = customStartDate.value
        params.endDate = customEndDate.value
      }
    }
    
    if (statusFilter.value !== '') {
      params.state = statusFilter.value
    }
    
    return params
  }

  const loadRecords = async (isLoadMore = false) => {
    if (isRecordsLoading.value) return
    
    try {
      isRecordsLoading.value = true
      
      if (!isLoadMore) {
        recordsPage.value = 1
        records.value.splice(0, records.value.length)
      }
      
      const params = buildRecordParams(recordsPage.value)
      const res = await withdrawApi.getRecords(params)
      
      if (res.code === 0) {
        const list = res.data.list || res.data || []
        if (isLoadMore) {
          records.value.push(...list)
        } else {
          records.value.splice(0, records.value.length, ...list)
        }
        recordsTotal.value = res.data.total || list.length
        totalWithdraw.value = res.data.totalAmount || records.value.reduce((sum, item) => sum + Number(item.amount || 0), 0)
      }
    } catch (e) {
    } finally {
      isRecordsLoading.value = false
    }
  }

  const refreshRecords = async () => {
    if (isRecordsLoading.value) return
    
    isRecordsLoading.value = true
    const startTime = Date.now()
    
    try {
      recordsPage.value = 1
      records.value.splice(0, records.value.length)
      
      const params = buildRecordParams(1)
      const res = await withdrawApi.getRecords(params)
      
      if (res.code === 0) {
        const list = res.data.list || res.data || []
        records.value.splice(0, records.value.length, ...list)
        recordsTotal.value = res.data.total || list.length
        totalWithdraw.value = res.data.totalAmount || records.value.reduce((sum, item) => sum + Number(item.amount || 0), 0)
      }
    } catch (e) {
    }
    
    const elapsed = Date.now() - startTime
    if (elapsed < 600) {
      await new Promise(r => setTimeout(r, 600 - elapsed))
    }
    isRecordsLoading.value = false
  }

  const handleBalanceRefresh = async () => {
    isBalanceRefreshing.value = true
    await loadConfig()
    setTimeout(() => {
      isBalanceRefreshing.value = false
    }, 600)
  }

  const handleRefresh = async () => {
    isRefreshing.value = true
    await loadAccounts()
    setTimeout(() => {
      isRefreshing.value = false
    }, 600)
  }

  const setDefaultAccount = async (acc) => {
    try {
      const res = await withdrawApi.setDefaultAccount(acc.id)
      if (res.code === 0) {
        showToast('设置成功')
        loadAccounts()
      } else {
        showToast(res.message || '设置失败')
      }
    } catch (e) {
      showToast('设置失败')
    }
  }

  const cancelWithdraw = async (item) => {
    if (item.state !== 0) {
      showToast('只能取消出款中的订单')
      return
    }
    
    try {
      await showConfirmDialog({
        title: '取消提现',
        message: '确定要取消这笔提现申请吗？',
        confirmButtonText: '确认',
        cancelButtonText: '取消',
        confirmButtonColor: '#26A17B',
        className: 'cancel-withdraw-dialog'
      })
      
      const res = await withdrawApi.cancel({ id: item.id })
      if (res.code === 0) {
        showToast('取消成功')
        loadRecords()
        loadConfig()
      } else {
        showToast(res.message || '取消失败')
      }
    } catch (e) {
    }
  }

  const checkHasFundPassword = () => {
    if (!hasFundPassword.value) {
      showConfirmDialog({
        title: '提示',
        message: '您还未设置资金密码，请先设置资金密码',
        confirmButtonText: '去设置',
        cancelButtonText: '取消',
        confirmButtonColor: '#26A17B'
      }).then(() => {
        router.push('/security/fund-pwd')
      }).catch(() => {})
      return false
    }
    return true
  }

  const getAccountTypeName = (type) => {
    const map = {
      usdt: 'USDT',
      bank: '银行卡',
      alipay: '支付宝',
      wechat: '微信'
    }
    return map[type] || type
  }

  const getSelectedAccountText = (acc) => {
    if (!acc) return ''
    const lastFour = acc.fullAddress ? acc.fullAddress.slice(-4) : ''
    
    if (acc.type === 'usdt') {
      let network = acc.network || 'TRC-20'
      if (network === 'TRC20') network = 'TRC-20'
      if (network === 'ERC20') network = 'ERC-20'
      return `USDT（****${lastFour}）  <span class="network-text">${network}</span>`
    }
    
    const typeName = getAccountTypeName(acc.type)
    return `${typeName}（****${lastFour}）`
  }

  const getAccountIconClass = (type) => {
    const map = {
      usdt: 'crypto',
      huiwang: 'huiwang',
      bank: 'bank',
      alipay: 'alipay',
      wechat: 'wechat'
    }
    return map[type] || 'crypto'
  }

  const getAccountAddress = (acc) => {
    return acc.fullAddress || ''
  }

  const maskAccountNumber = (cardNo) => {
    if (!cardNo) return ''
    if (cardNo.length > 4) {
      return '****' + cardNo.slice(-4)
    }
    return cardNo
  }

  const truncateAddress = (address) => {
    if (!address) return ''
    if (address.length > 16) {
      return address.slice(0, 16) + '...'
    }
    return address
  }

  const getRecordTypeName = (item) => {
    if (item.type === 'usdt') return 'USDT提现'
    if (item.type === 'wechat') return '微信提现'
    if (item.type === 'alipay') return '支付宝提现'
    if (item.type === 'bank') return '银行卡提现'
    return item.bankName || '提现'
  }

  const getRecordStatusText = (state) => {
    if (state === 0) return '出款中'
    if (state === 1) return '提现成功'
    if (state === 2) return '提现拒绝'
    if (state === 3) return '提现取消'
    return '未知'
  }

  const getStatusText = (val) => {
    const opt = statusOptions.find(o => o.value === val)
    return opt ? opt.text : '全部状态'
  }

  const getTimeFilterText = () => {
    if (showAllHistoryMode.value) return '全部时间'
    if (timeFilter.value === 4 && customStartDate.value && customEndDate.value) {
      return `${customStartDate.value.substring(5)}~${customEndDate.value.substring(5)}`
    }
    return timeOptions[timeFilter.value]?.text || '今日'
  }

  const copyAddress = async (address) => {
    if (!address) return
    try {
      await navigator.clipboard.writeText(address)
      showToast('复制成功')
    } catch (e) {
      showToast('复制失败')
    }
  }

  const showAllHistory = () => {
    showAllHistoryMode.value = true
    statusFilter.value = ''
    recordsPage.value = 1
    loadRecords(false)
  }

  const loadMoreRecords = () => {
    recordsPage.value++
    loadRecords(true)
  }

  return {
    balance,
    minAmount,
    maxAmount,
    hasFundPassword,
    savedAccounts,
    selectedAccount,
    records,
    totalWithdraw,
    recordsPage,
    recordsTotal,
    isRecordsLoading,
    isRefreshing,
    isBalanceRefreshing,
    hasMoreRecords,
    allAccountTypes,
    unboundAccountTypes,
    timeFilter,
    statusFilter,
    showAllHistoryMode,
    timeOptions,
    statusOptions,
    customStartDate,
    customEndDate,
    hasAccountType,
    getAccountByType,
    formatDate,
    loadConfig,
    loadSecurityInfo,
    loadAccounts,
    loadRecords,
    refreshRecords,
    handleBalanceRefresh,
    handleRefresh,
    setDefaultAccount,
    cancelWithdraw,
    checkHasFundPassword,
    getAccountTypeName,
    getSelectedAccountText,
    getAccountIconClass,
    getAccountAddress,
    maskAccountNumber,
    truncateAddress,
    getRecordTypeName,
    getRecordStatusText,
    getStatusText,
    getTimeFilterText,
    copyAddress,
    showAllHistory,
    loadMoreRecords
  }
}
