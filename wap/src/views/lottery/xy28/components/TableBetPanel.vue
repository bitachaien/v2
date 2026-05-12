<template>
    <transition name="slide-up">
      <div class="table-bet-panel" v-show="show">
        
        <div class="table-header">
          <div class="collapse-btn" @click="$emit('update:show', false)">
            <van-icon name="arrow-down" color="#1e88e5" />
            <span>收起</span>
          </div>
          <div class="table-tabs">
            <div class="tab-item" :class="{ active: tableTab === 'hot' }" @click="tableTab = 'hot'">热门玩法</div>
            <div class="tab-item" :class="{ active: tableTab === 'number' }" @click="tableTab = 'number'">数字玩法</div>
          </div>
          <div class="help-btn" @click="$emit('help')">
            <van-icon name="question-o" color="#1e88e5" />
            <span>桌投说明</span>
          </div>
        </div>
        
        
        <div class="table-content" v-if="tableTab === 'hot'">
          <div class="hot-grid">
            
            <div class="bet-card large" v-for="item in hotBets.daxiao" :key="item.value"
                 :class="{ selected: tableBetSelected[item.value] }"
                 @click.stop="toggleTableBet(item)">
              <div class="card-main">
                <span class="label big" :class="{ highlight: tableBetSelected[item.value] }">{{ item.label }}</span>
                <span class="bet-amount-tag" v-if="tableBetSelected[item.value]">¥ {{ tableBetSelected[item.value] }}</span>
                <span class="odds" v-else>1:{{ getOdds(item.value) }}</span>
              </div>
              <div class="card-footer">
                <span class="users"><van-icon name="friends-o" size="14" /> {{ getPlayStats(item.value).users }}</span>
                <span class="amount"><van-icon name="gold-coin-o" size="14" color="#ff9800" /> {{ getPlayStats(item.value).amount }}</span>
              </div>
            </div>
            
            <div class="bet-card large" v-for="item in hotBets.danshuang" :key="item.value"
                 :class="{ selected: tableBetSelected[item.value] }"
                 @click.stop="toggleTableBet(item)">
              <div class="card-main">
                <span class="label big" :class="{ highlight: tableBetSelected[item.value] }">{{ item.label }}</span>
                <span class="bet-amount-tag" v-if="tableBetSelected[item.value]">¥ {{ tableBetSelected[item.value] }}</span>
                <span class="odds" v-else>1:{{ getOdds(item.value) }}</span>
              </div>
              <div class="card-footer">
                <span class="users"><van-icon name="friends-o" size="14" /> {{ getPlayStats(item.value).users }}</span>
                <span class="amount"><van-icon name="gold-coin-o" size="14" color="#ff9800" /> {{ getPlayStats(item.value).amount }}</span>
              </div>
            </div>
            
            <div class="bet-card small" v-for="item in hotBets.combo" :key="item.value"
                 :class="{ selected: tableBetSelected[item.value] }"
                 @click.stop="toggleTableBet(item)">
              <div class="card-main">
                <span class="label">{{ item.label }}</span>
                <span class="odds">1:{{ getOdds(item.value) }}</span>
              </div>
              <div class="card-footer">
                <span class="users"><van-icon name="friends-o" size="13" /> {{ getPlayStats(item.value).users }}</span>
                <span class="amount"><van-icon name="gold-coin-o" size="13" color="#ff9800" /> {{ getPlayStats(item.value).amount }}</span>
              </div>
            </div>
            
            <div class="bet-card small" v-for="item in hotBets.special" :key="item.value"
                 :class="{ selected: tableBetSelected[item.value] }"
                 @click.stop="toggleTableBet(item)">
              <div class="card-main">
                <span class="label">{{ item.label }}</span>
                <span class="odds">1:{{ getOdds(item.value) }}</span>
              </div>
              <div class="card-footer">
                <span class="users"><van-icon name="friends-o" size="13" /> {{ getPlayStats(item.value).users }}</span>
                <span class="amount"><van-icon name="gold-coin-o" size="13" color="#ff9800" /> {{ getPlayStats(item.value).amount }}</span>
              </div>
            </div>
            
            <div class="bet-card small" v-for="item in hotBets.bottom" :key="item.value"
                 :class="{ selected: tableBetSelected[item.value] }"
                 @click.stop="toggleTableBet(item)">
              <div class="card-main">
                <span class="label">{{ item.label }}</span>
                <span class="odds">1:{{ getOdds(item.value) }}</span>
              </div>
              <div class="card-footer">
                <span class="users"><van-icon name="friends-o" size="13" /> {{ getPlayStats(item.value).users }}</span>
                <span class="amount"><van-icon name="gold-coin-o" size="13" color="#ff9800" /> {{ getPlayStats(item.value).amount }}</span>
              </div>
            </div>
          </div>
        </div>
        
        
        <div class="table-content" v-if="tableTab === 'number'">
          <div class="number-grid">
            <div class="bet-card num-card" v-for="n in 28" :key="n-1"
                 :class="{ selected: tableBetSelected['tm_' + (n-1)] }"
                 @click.stop="toggleTableBet({ value: 'tm_' + (n-1), label: String(n-1), odds: getNumberOdds(n-1) })">
              <div class="card-main">
                <span class="label num">{{ n - 1 }}</span>
                <span class="odds">1:{{ getNumberOdds(n-1) }}</span>
              </div>
              <div class="card-footer">
                <span class="users"><van-icon name="friends-o" size="13" /> {{ getNumberStats(n-1).users }}</span>
                <span class="amount"><van-icon name="gold-coin-o" size="13" color="#ff9800" /> {{ getNumberStats(n-1).amount }}</span>
              </div>
            </div>
          </div>
        </div>
        
        
        <div class="chip-bar">
          <div class="chip" v-for="chip in chips" :key="chip"
               :class="{ active: selectedChip === chip }"
               @click="selectedChip = chip">
            ¥{{ chip }}
          </div>
          <div class="chip setting" @click="showChipSetting = true">
            <van-icon name="setting-o" />
          </div>
        </div>
        
        
        <div class="table-footer">
          <div class="bet-summary" @click="openBetDetail">
            <span>已投<em>{{ issueBetCount }}</em>单,共<em>{{ issueBetTotal.toFixed(2) }}</em>元</span>
            <van-icon name="arrow" size="12" />
          </div>
          <div class="btn-clear" @click="clearTableBets">清空</div>
          <div class="btn-confirm" :class="{ disabled: isSealed }" @click="submitTableBet">
            {{ isSealed ? '封盘中' : '确认投注' }}
          </div>
        </div>
        
        
        <van-popup v-model:show="showBetDetail" position="bottom" round :style="{ height: '55vh' }">
          <div class="bet-detail-panel">
            <div class="detail-header">
              <span class="detail-title">本期下注详情</span>
              <van-icon name="cross" class="close-btn" @click="showBetDetail = false" />
            </div>
            <div class="detail-loading" v-if="detailLoading">
              <van-loading size="24px">加载中...</van-loading>
            </div>
            <div class="detail-list" v-else-if="issueBetList.length > 0">
              <div class="detail-item" v-for="bet in issueBetList" :key="bet.id">
                <div class="item-info">
                  <span class="item-label">{{ bet.label }}</span>
                  <span class="item-meta">
                    <span v-if="editingBetId !== bet.id">
                      单注 ¥{{ getPerBetAmount(bet) }}
                      <van-icon v-if="!isSealed" name="edit" size="12" class="edit-icon" @click="startEditAmount(bet)" />
                    </span>
                    <span v-else class="edit-inline">
                      单注 ¥<input type="number" v-model="editAmount" class="amount-input" />
                      <span class="save-btn" @click="saveAmount(bet)">确定</span>
                      <span class="cancel-edit-btn" @click="cancelEdit">取消</span>
                    </span>
                    <span class="divider">|</span>
                    <span>{{ bet.betCount }}注</span>
                    <span class="divider">|</span>
                    <span>合计 ¥{{ editingBetId === bet.id ? (editAmount * bet.betCount).toFixed(2) : bet.totalAmount }}</span>
                  </span>
                </div>
                <div class="item-action" v-if="!isSealed">
                  <span class="cancel-btn" @click="cancelBet(bet)">撤单</span>
                </div>
              </div>
            </div>
            <div class="detail-empty" v-else>
              <van-icon name="notes-o" size="48" color="#ddd" />
              <span>本期暂无下注</span>
            </div>
            <div class="detail-footer" v-if="issueBetList.length > 0">
              <span class="total-info">共 {{ issueBetList.length }} 注，合计 ¥{{ issueBetTotal.toFixed(2) }}</span>
            </div>
          </div>
        </van-popup>
      </div>
    </transition>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { showToast, showLoadingToast, closeToast } from 'vant'
import { xy28Api } from '@/api'

const props = defineProps({
  show: Boolean,
  lotteryCode: {
    type: String,
    default: 'yfxy28'
  },
  currentIssue: {
    type: String,
    default: ''
  },
  isSealed: {
    type: Boolean,
    default: false
  },

  wsBetStats: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['update:show', 'submit', 'help', 'detail'])

const tableTab = ref('hot')
const tableBetSelected = ref({})
const selectedChip = ref(10)
const chips = [1, 5, 10, 50, 100, 500]
const showBetDetail = ref(false)
const detailLoading = ref(false)
const issueBetList = ref([])

const issueBetCount = computed(() => issueBetList.value.reduce((sum, bet) => sum + (bet.betCount || 1), 0))
const issueBetTotal = computed(() => issueBetList.value.reduce((sum, bet) => sum + parseFloat(bet.totalAmount || bet.amount || 0), 0))

const labelMap = {
  big: '大', small: '小', odd: '单', even: '双',
  big_odd: '大单', big_even: '大双', small_odd: '小单', small_even: '小双',
  jida: '极大', jixiao: '极小', long: '龙', hu: '虎', bao: '豹',
  duizi: '对子', shunzi: '顺子', baozi: '豹子'
}

function getBetLabel(key) {
  if (labelMap[key]) return labelMap[key]
  if (key.startsWith('tm_')) return key.replace('tm_', '') + '点'
  return key
}

async function openBetDetail() {
  showBetDetail.value = true
  await loadIssueBets()
}

async function loadIssueBets() {
  if (!props.currentIssue) return
  detailLoading.value = true
  try {
    const res = await xy28Api.getIssueBets(props.lotteryCode, props.currentIssue)
    if (res.code === 0) {
      issueBetList.value = res.data || []
    }
  } catch (e) {
    console.error('加载下注记录失败:', e)
  } finally {
    detailLoading.value = false
  }
}

watch(() => props.currentIssue, (newIssue, oldIssue) => {
  if (newIssue && newIssue !== oldIssue) {
    loadIssueBets()
  } else if (!newIssue) {
    issueBetList.value = []
  }
})

watch(() => props.show, (visible) => {
  if (visible && props.currentIssue) {
    loadIssueBets()
  }
})

async function cancelBet(bet) {
  try {
    showLoadingToast({ message: '撤单中...', forbidClick: true })
    const res = await xy28Api.cancelBet(bet.id)
    closeToast()
    if (res.code === 0) {
      showToast('撤单成功')
      await loadIssueBets() // 刷新列表
    } else {
      showToast(res.message || '撤单失败')
    }
  } catch (e) {
    closeToast()
    showToast('撤单失败')
  }
}

const editingBetId = ref(null)
const editAmount = ref(0)

function getPerBetAmount(bet) {
  const total = parseFloat(bet.totalAmount || bet.amount || 0)
  const count = bet.betCount || 1
  return (total / count).toFixed(2)
}

function startEditAmount(bet) {
  editingBetId.value = bet.id

  editAmount.value = parseFloat(getPerBetAmount(bet))
}

function cancelEdit() {
  editingBetId.value = null
  editAmount.value = 0
}

async function saveAmount(bet) {
  const perAmount = parseFloat(editAmount.value)
  if (!perAmount || perAmount <= 0) {
    showToast('请输入有效金额')
    return
  }
  

  const newTotalAmount = perAmount * (bet.betCount || 1)
  const oldTotalAmount = parseFloat(bet.totalAmount || bet.amount || 0)
  
  if (newTotalAmount === oldTotalAmount) {
    cancelEdit()
    return
  }
  
  try {
    showLoadingToast({ message: '修改中...', forbidClick: true })

    const res = await xy28Api.modifyBet(bet.id, newTotalAmount, perAmount)
    closeToast()
    if (res.code === 0) {
      showToast('修改成功')
      cancelEdit()
      await loadIssueBets() // 刷新列表
    } else {
      showToast(res.message || '修改失败')
    }
  } catch (e) {
    closeToast()
    showToast('修改失败')
  }
}
const showChipSetting = ref(false)

const betStats = ref({})

watch(() => props.wsBetStats, (newStats) => {
  if (newStats && Object.keys(newStats).length > 0) {
    betStats.value = newStats
  }
}, { deep: true })

const hotBets = reactive({
  daxiao: [
    { value: 'big', label: '大' },
    { value: 'small', label: '小' }
  ],
  danshuang: [
    { value: 'odd', label: '单' },
    { value: 'even', label: '双' }
  ],
  combo: [
    { value: 'big_odd', label: '大单' },
    { value: 'big_even', label: '大双' },
    { value: 'small_odd', label: '小单' },
    { value: 'small_even', label: '小双' }
  ],
  special: [
    { value: 'jida', label: '极大' },
    { value: 'long', label: '龙' },
    { value: 'hu', label: '虎' },
    { value: 'bao', label: '豹' }
  ],
  bottom: [
    { value: 'jixiao', label: '极小' },
    { value: 'duizi', label: '对子' },
    { value: 'shunzi', label: '顺子' },
    { value: 'baozi', label: '豹子' }
  ]
})

function getPlayStats(playKey) {
  return betStats.value[playKey] || { users: 0, amount: 0 }
}

const apiOddsMap = ref({})

function getOdds(playid) {
  return apiOddsMap.value[playid]?.toFixed(2) || '--'
}

async function loadPlayTypes() {
  try {
    const res = await xy28Api.getPlayTypes(props.lotteryCode)
    if (res.code === 0 && res.data?.playTypes) {
      const oddsData = {}
      res.data.playTypes.forEach(play => {
        oddsData[play.playType] = play.odds
      })
      apiOddsMap.value = oddsData
    }
  } catch (e) {
    console.error('[TableBetPanel] 加载玩法赔率失败:', e)
  }
}

async function loadBetStats() {
  try {
    const res = await xy28Api.getIssueBetStats(props.lotteryCode, props.currentIssue)
    if (res.code === 0 && res.data?.stats) {
      betStats.value = res.data.stats
    }
  } catch (e) {
    console.error('[TableBetPanel] 加载投注统计失败:', e)
  }
}

onMounted(() => {
  loadPlayTypes()
  loadBetStats()
})

watch(() => props.currentIssue, () => {
  loadBetStats()
})

watch(() => props.show, (show) => {
  if (show) {
    loadBetStats()
  }
})

function getNumberOdds(n) {
  return getOdds(`tm_${n}`)
}

function getNumberStats(n) {
  const key = `tm_${n}`
  return betStats.value[key] || { users: 0, amount: 0 }
}

const tableBetCount = computed(() => Object.keys(tableBetSelected.value).length)
const tableBetTotal = computed(() => {
  return Object.values(tableBetSelected.value).reduce((sum, val) => sum + val, 0)
})

function toggleTableBet(item) {
  const key = item.value
  if (tableBetSelected.value[key]) {
    tableBetSelected.value[key] += selectedChip.value
  } else {
    tableBetSelected.value[key] = selectedChip.value
  }
}

function clearTableBets() {
  tableBetSelected.value = {}
}

const submitTableBet = async () => {

  if (props.isSealed) {
    showToast('封盘中，暂停投注')
    return
  }
  
  if (tableBetCount.value === 0) {
    showToast('请选择投注项')
    return
  }
  

  const labelMap = {
    big: '大', small: '小', odd: '单', even: '双',
    big_odd: '大单', big_even: '大双', small_odd: '小单', small_even: '小双',
    jida: '极大', jixiao: '极小', long: '龙', hu: '虎', bao: '豹',
    duizi: '对子', shunzi: '顺子', baozi: '豹子'
  }
  
  const betParts = []
  const selections = []
  
  for (const [key, amount] of Object.entries(tableBetSelected.value)) {
    let label = labelMap[key]
    let isNumber = false
    if (!label && key.startsWith('tm_')) {
      label = key.replace('tm_', '') + '点'  // 数字玩法加"点"后缀
      isNumber = true
    }
    
    if (label) {
      betParts.push(`${label}:${amount}`)
      selections.push({
        type: isNumber ? 'number' : 'combo',
        value: key,
        label: label,
        amount: amount
      })
    }
  }
  
  const betText = betParts.join('|')
  

  clearTableBets()
  emit('update:show', false)
  emit('submit', betText)
}

defineExpose({ clearTableBets, loadIssueBets })

</script>

<style lang="less" scoped>
.table-bet-panel {
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  z-index: 1001;
  background: #ebecf5;
  max-height: 55vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  
  .table-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 12px;
    background: #ebecf5;
    
    .collapse-btn {
      display: flex;
      align-items: center;
      color: #1e88e5;
      font-size: 14px;
    }
    
    .table-tabs {
      display: flex;
      background: #fff;
      border-radius: 4px;
      padding: 2px;
      
      .tab-item {
        padding: 4px 16px;
        font-size: 13px;
        color: #666;
        border-radius: 2px;
        
        &.active {
          background: #e1f0ff;
          color: #1e88e5;
          font-weight: 500;
        }
      }
    }
    
    .help-btn {
      display: flex;
      align-items: center;
      gap: 2px;
      color: #1e88e5;
      font-size: 14px;
    }
  }
  
  .table-content {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 5px 8px;
    background: #ebecf5;
    -webkit-overflow-scrolling: touch; 
    
    .hot-grid, .number-grid {
      display: flex;
      flex-wrap: wrap;
      margin: 0 -3px; 
      padding-bottom: 10px;
    }
    

    .number-grid {
      .bet-card.num-card {
        flex: 0 0 calc(25% - 6px); 
        width: calc(25% - 6px);
        margin: 3px;
        height: auto;
        min-height: 85px; 
        
        .card-main {
            .label.num {
                font-size: 24px;
            }
        }
      }
    }
    
    .bet-card {
      box-sizing: border-box; 
      background: linear-gradient(180deg, #fefefe, #e8e9f0);
      border: 1px solid #fff;
      border-radius: 6px;
      padding: 8px 6px 8px; 
      box-shadow: 0 2px 4px rgba(162, 166, 186, 0.4);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      position: relative;
      

      &.large {
        flex: 0 0 calc(50% - 6px);
        width: calc(50% - 6px);
        margin: 3px;
        min-height: 95px; 
      }
      

      &.small {
        flex: 0 0 calc(25% - 6px);
        width: calc(25% - 6px);
        margin: 3px;
        min-height: 80px; 
      }
      
      &.no-footer {
        min-height: 60px;
        justify-content: center;
      }
      
      &.selected {
        border-color: #5691fe;

        
        .card-main {
            .label {
                color: #5691fe;
            }
        }
      }
      
      .card-main {
        text-align: center;
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        
        &.center-only {
          justify-content: center;
        }
        
        .label {
          display: block;
          font-size: 15px;
          font-weight: 600;
          color: #333;
          line-height: 1.2;
          
          &.big {
            font-size: 22px;
            font-weight: 700;
          }
          
          &.num {
            font-size: 24px;
            font-weight: bold;
            color: #444;
            font-family: 'Din', sans-serif;
            text-shadow: 0 1px 1px #fff;
          }
          
          &.highlight {
            color: #5691fe;
          }
        }
        
        .odds {
          display: block;
          font-size: 12px;
          color: #666;
          margin-top: 4px;
          font-family: Arial, sans-serif;
        }
        
        .bet-amount-tag {
          display: inline-block;
          background: #5691fe;
          color: #fff;
          font-size: 11px;
          padding: 2px 8px;
          border-radius: 4px;
          font-weight: 500;
          margin-top: 4px;
          min-width: 40px;
        }
      }
      
      .card-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 100%;
        margin-top: 6px;
        padding-top: 6px;
        border-top: 1px solid rgba(0,0,0,0.03);
        
        .users, .amount {
          font-size: 12px; 
          color: #999;
          display: flex;
          align-items: center;
          gap: 2px;
        }
      }
    }
  }
  
  .chip-bar {
    padding: 10px 12px;
    background: #fff;
    display: flex;
    gap: 8px;
    align-items: center;
    border-top: 1px solid #f5f6f7;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    
    &::-webkit-scrollbar { display: none; }
    
    .chip {
      flex-shrink: 0;
      width: 55px !important;
      height: 34.39px !important;
      box-sizing: border-box;
      border-radius: 4px;
      background: #f7f8fa;
      border: 1px solid #ebedf0;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 14px;
      font-weight: 500;
      color: #333;
      transition: all 0.2s;
      
      &.active {
        color: #fff;
        background: #5691fe;
        border-color: #5691fe;
        box-shadow: 0 2px 6px rgba(86, 145, 254, 0.3);
      }
      
      &.setting {
        width: 40px;
        position: sticky;
        right: 0;
        z-index: 2;
        margin-left: auto;
        background: #f7f8fa;
        font-size: 18px;
        color: #999;
        box-shadow: -4px 0 8px -2px rgba(0,0,0,0.1);
      }
    }
  }
  
  .table-footer {
    padding: 8px 12px;
    padding-bottom: calc(8px + env(safe-area-inset-bottom));
    background: #fff;
    display: flex;
    align-items: center;
    gap: 8px;
    border-top: 1px solid #f5f6f7;
    
    .bet-summary {
      flex: 1;
      height: 36px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      font-size: 13px;
      color: #333;
      padding: 0 12px;
      background: #f7f8fa;
      border: 1px solid #ebedf0;
      border-radius: 4px;
      
      span {
        display: flex;
        align-items: center;
      }
      
      em {
        color: #f5222d;
        font-weight: 600;
        margin: 0 2px;
        font-style: normal;
      }
      
      .van-icon {
        color: #999;
        margin-left: 4px;
      }
    }
    
    .btn-clear {
      height: 36px;
      padding: 0 12px;
      background: #fff;
      color: #666;
      border: 1px solid #ebedf0;
      border-radius: 4px;
      font-size: 13px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    .btn-confirm {
      height: 36px;
      padding: 0 20px;
      background: #5691fe;
      color: #fff;
      border-radius: 4px;
      font-size: 13px;
      font-weight: 500;
      box-shadow: 0 2px 4px rgba(86, 145, 254, 0.3);
      display: flex;
      align-items: center;
      justify-content: center;
      
      &.disabled {
        background: #999;
        box-shadow: none;
        cursor: not-allowed;
        pointer-events: none;
      }
    }
  }
}

.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.3s ease;
}

.slide-up-enter-from,
.slide-up-leave-to {
  transform: translateY(100%);
}

.bet-detail-panel {
  display: flex;
  flex-direction: column;
  height: 100%;
  background: #fff;
  
  .detail-header {
    flex-shrink: 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px;
    border-bottom: 1px solid #eee;
    
    .detail-title {
      font-size: 17px;
      font-weight: 600;
      color: #333;
    }
    
    .close-btn {
      font-size: 22px;
      color: #999;
      cursor: pointer;
    }
  }
  
  .detail-loading {
    flex: 1;
    display: flex;
    justify-content: center;
    align-items: center;
  }
  
  .detail-list {
    flex: 1;
    overflow-y: auto;
    padding: 0 16px;
    
    .detail-item {
      display: flex;
      align-items: center;
      padding: 16px 0;
      border-bottom: 1px solid #f5f5f5;
      
      &:last-child {
        border-bottom: none;
      }
      
      .item-info {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 6px;
        
        .item-label {
          font-size: 14px;
          color: #333;
          font-weight: 500;
          word-break: break-all;
          line-height: 1.4;
        }
        
        .item-meta {
          font-size: 12px;
          color: #999;
          display: flex;
          align-items: center;
          gap: 4px;
          
          .divider {
            color: #ddd;
          }
          
          .edit-icon {
            color: #5691fe;
            margin-left: 4px;
            cursor: pointer;
          }
          
          .edit-inline {
            display: flex;
            align-items: center;
            gap: 6px;
            
            .amount-input {
              width: 60px;
              height: 24px;
              border: 1px solid #5691fe;
              border-radius: 4px;
              text-align: center;
              font-size: 12px;
              color: #f5222d;
              font-weight: 600;
              outline: none;
              
              &::-webkit-inner-spin-button,
              &::-webkit-outer-spin-button {
                -webkit-appearance: none;
              }
            }
            
            .save-btn {
              padding: 2px 8px;
              font-size: 11px;
              color: #fff;
              background: #5691fe;
              border-radius: 3px;
              cursor: pointer;
            }
            
            .cancel-edit-btn {
              padding: 2px 8px;
              font-size: 11px;
              color: #666;
              background: #f0f0f0;
              border-radius: 3px;
              cursor: pointer;
            }
          }
        }
      }
      
      .item-amount {
        margin-right: 12px;
        
        .amount-control {
          display: flex;
          align-items: center;
          gap: 6px;
          cursor: pointer;
          
          .amount {
            font-size: 16px;
            color: #f5222d;
            font-weight: 600;
          }
          
          .edit-icon {
            color: #999;
          }
        }
        
        .amount-edit {
          display: flex;
          align-items: center;
          gap: 8px;
          
          .amount-input {
            width: 70px;
            height: 32px;
            border: 1px solid #5691fe;
            border-radius: 4px;
            text-align: center;
            font-size: 14px;
            color: #f5222d;
            font-weight: 600;
            outline: none;
            
            &::-webkit-inner-spin-button,
            &::-webkit-outer-spin-button {
              -webkit-appearance: none;
            }
          }
          
          .save-btn {
            padding: 4px 10px;
            font-size: 12px;
            color: #fff;
            background: #5691fe;
            border-radius: 4px;
            cursor: pointer;
          }
          
          .cancel-edit-btn {
            padding: 4px 10px;
            font-size: 12px;
            color: #666;
            background: #f5f5f5;
            border-radius: 4px;
            cursor: pointer;
          }
        }
        
        .amount {
          font-size: 16px;
          color: #f5222d;
          font-weight: 600;
        }
      }
      
      .item-action {
        .cancel-btn {
          padding: 6px 14px;
          font-size: 13px;
          color: #ff4d4f;
          border: 1px solid #ff4d4f;
          border-radius: 4px;
          background: #fff;
          cursor: pointer;
          
          &:active {
            background: #fff1f0;
          }
        }
      }
    }
  }
  
  .detail-empty {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #999;
    font-size: 14px;
    gap: 12px;
  }
  
  .detail-footer {
    flex-shrink: 0;
    padding: 16px;
    border-top: 1px solid #eee;
    text-align: center;
    background: #fafafa;
    
    .total-info {
      font-size: 15px;
      color: #666;
    }
  }
}
</style>
