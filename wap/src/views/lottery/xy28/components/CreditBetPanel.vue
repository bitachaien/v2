<template>
  <transition name="slide-up">
    <div class="credit-bet-panel" v-if="show">
      
      <div class="credit-header">
        <div class="collapse-btn" @click="close">
          <van-icon name="play" color="#5691fe" />
          <span>收起</span>
        </div>
        <div class="credit-title">信用投注</div>
        <div class="placeholder"></div>
      </div>
      
      
      <div class="credit-body">
        
        <div class="credit-sidebar">
          <div class="sidebar-item" 
               v-for="cat in creditCategories" 
               :key="cat.key"
               :class="{ active: creditActiveCategory === cat.key }"
               @click="creditActiveCategory = cat.key">
            {{ cat.label }}
          </div>
        </div>
        
        
        <div class="credit-content">
          
          <div class="content-toolbar">
            <div class="toolbar-left" @click="showPlayRules = true">
              <span>玩法说明</span>
              <van-icon name="question-o" size="14" color="#5691fe" />
            </div>
            <div class="toolbar-right">
              <div class="tool-btn" :class="{ active: showHot }" @click="showHot = !showHot">冷热</div>
              <div class="tool-btn" :class="{ active: showMiss }" @click="showMiss = !showMiss">遗漏</div>
            </div>
          </div>
          
          
          <div class="play-title">{{ currentCreditCategory.label }}</div>
          
          
          <div class="credit-bets-area">
            
            <template v-if="creditActiveCategory === 'liangmian'">
              <div class="bet-grid-4">
                <div class="credit-bet-btn" v-for="item in creditBets.liangmian.all" :key="item.value"
                     :class="{ selected: tempSelected[item.value] }"
                     @click="toggleCreditBet(item)">
                  <span class="btn-label">{{ item.label }}</span>
                  <span class="btn-odds">1:{{ getOdds(item.value) }}</span>
                  <span class="badge hot-badge" v-if="showHot" :class="getHotColor(hotColdStats[item.value]?.count)">{{ hotColdStats[item.value]?.count || 0 }}</span>
                  <span class="badge miss-badge" v-if="showMiss">{{ hotColdStats[item.value]?.miss || 0 }}</span>
                </div>
              </div>
            </template>
            
            
            <template v-if="creditActiveCategory === 'longhu'">
              <div class="bet-grid-4">
                <div class="credit-bet-btn" v-for="item in creditBets.longhu" :key="item.value"
                     :class="{ selected: tempSelected[item.value] }"
                     @click="toggleCreditBet(item)">
                  <span class="btn-label">{{ item.label }}</span>
                  <span class="btn-odds">1:{{ getOdds(item.value) }}</span>
                  <span class="badge hot-badge" v-if="showHot" :class="getHotColor(hotColdStats[item.value]?.count)">{{ hotColdStats[item.value]?.count || 0 }}</span>
                  <span class="badge miss-badge" v-if="showMiss">{{ hotColdStats[item.value]?.miss || 0 }}</span>
                </div>
              </div>
            </template>
            
            
            <template v-if="creditActiveCategory === 'quwei'">
              <div class="bet-grid-4">
                <div class="credit-bet-btn" v-for="item in creditBets.quwei" :key="item.value"
                     :class="{ selected: tempSelected[item.value] }"
                     @click="toggleCreditBet(item)">
                  <span class="btn-label">{{ item.label }}</span>
                  <span class="btn-odds">1:{{ getOdds(item.value) }}</span>
                  <span class="badge hot-badge" v-if="showHot" :class="getHotColor(hotColdStats[item.value]?.count)">{{ hotColdStats[item.value]?.count || 0 }}</span>
                  <span class="badge miss-badge" v-if="showMiss">{{ hotColdStats[item.value]?.miss || 0 }}</span>
                </div>
              </div>
            </template>
            
            
            <template v-if="creditActiveCategory === 'hezhi'">
              <div class="bet-grid-28">
                <div class="credit-bet-btn num" v-for="n in 28" :key="n-1"
                     :class="{ selected: tempSelected['tm_' + (n-1)] }"
                     @click="toggleCreditBet({ value: 'tm_' + (n-1), label: String(n-1) })">
                  <span class="btn-label">{{ n - 1 }}</span>
                  <span class="btn-odds">1:{{ getNumberOdds(n-1) }}</span>
                  <span class="badge hot-badge" v-if="showHot" :class="getHotColor(hotColdStats.numbers?.[n-1]?.count)">{{ hotColdStats.numbers?.[n-1]?.count || 0 }}</span>
                  <span class="badge miss-badge" v-if="showMiss">{{ hotColdStats.numbers?.[n-1]?.miss || 0 }}</span>
                </div>
              </div>
            </template>
          </div>
          
          
          <div class="credit-selection-tip" v-if="tempSelectedCount > 0">
            共 {{ tempSelectedCount }} 单, {{ tempSelectedTotal.toFixed(2) }}元
          </div>
        </div>
      </div>
      
      
      <div class="credit-footer">
        
        <div class="footer-row chips-row">
          <div class="chip-delete" @click="clearCreditBets">
            <van-icon name="delete-o" size="20" />
          </div>
          <div class="chip-quick">
            <van-icon name="edit" />
            <span>快捷</span>
          </div>
          <div class="chips-scroll">
              <div class="chip-item" v-for="chip in creditChips" :key="chip"
                  :class="{ active: creditSelectedChip === chip }"
                  @click="creditSelectedChip = chip">
              {{ chip }}
              </div>
          </div>
          <div class="chip-custom">{{ creditSelectedChip }}</div>
        </div>
        
        
        <div class="footer-row actions-row">
          <div class="action-item" @click="showRecentBets = true; loadRecentBets()">
            <div class="icon-box"><van-icon name="clock-o" /></div>
            <span>近期投注</span>
          </div>
          <div class="action-item cart-btn" @click="showCartPanel = true">
            <div class="icon-box"><van-icon name="shopping-cart-o" /></div>
            <span>购彩篮</span>
            <div class="badge" v-if="creditBetCount > 0">{{ creditBetCount }}</div>
          </div>
          <div class="action-item" @click="addToCart">
            <div class="icon-box"><van-icon name="add-o" /></div>
            <span>添加选号</span>
            <div class="badge temp-badge" v-if="tempSelectedCount > 0">{{ tempSelectedCount }}</div>
          </div>
          <div class="submit-btn" :class="{ disabled: (creditBetCount === 0 && tempSelectedCount === 0) || isSealed }" @click="submitCreditBet">
            <span class="btn-text">{{ isSealed ? '封盘中' : '立即投注' }}</span>
            <span class="btn-amount" v-if="(creditBetCount > 0 || tempSelectedCount > 0) && !isSealed">¥{{ (creditBetTotal + tempSelectedTotal).toFixed(2) }}</span>
            <span class="btn-amount" v-else-if="!isSealed">¥0.00</span>
          </div>
          <div class="action-item" @click="openChasePopup">
            <div class="icon-box"><van-icon name="aim" /></div>
            <span>追号</span>
          </div>
        </div>
      </div>
      
      
      <ChaseNumberPopup 
        v-model:show="showChasePopup"
        :currentIssue="currentIssue"
        :betAmount="creditSelectedChip"
        :balance="balance"
        :lotteryCode="lotteryCode"
        :betData="chaseBetData"
        @confirm="onChaseConfirm"
        @success="onChaseSuccess"
      />
      
      
      <van-popup v-model:show="showPlayRules" position="center" round class="play-rules-popup" :overlay-style="{ background: 'rgba(0,0,0,0.3)' }">
        <div class="popup-indicator"></div>
        <div class="rules-content">
          <div class="rules-title">玩法说明：</div>
          <div class="rules-body">
            <template v-for="(rule, idx) in currentRules" :key="idx">
              <p><strong>{{ rule.title }}</strong></p>
              <p v-for="(line, lineIdx) in rule.content.split('\n')" :key="lineIdx">{{ line }}</p>
            </template>
          </div>
          <div class="rules-footer" @click="showPlayRules = false">我知道了</div>
        </div>
      </van-popup>
      
      
      <van-popup v-model:show="showCartPanel" position="bottom" round teleport="body" :style="{ height: '65vh' }">
        <div class="credit-cart-panel">
          <div class="cart-header">
            <div class="collapse-btn" @click="showCartPanel = false">
              <van-icon name="arrow-down" color="#5691fe" />
              <span>收起</span>
            </div>
            <span class="cart-title">购彩篮</span>
            <span class="clear-btn" @click="clearCreditBets">清空</span>
          </div>
          
          <div class="cart-list">
            <div class="cart-item" v-for="(item, index) in creditBetList" :key="item.id">
              <span class="item-label">{{ item.label }}</span>
              <div class="item-amount">
                <input 
                  type="number" 
                  class="amount-input" 
                  :value="item.amount" 
                  @input="updateCartAmount(index, $event.target.value)"
                />
              </div>
              <van-icon name="delete-o" class="item-delete" @click="removeCartItem(index)" />
            </div>
            <div class="cart-empty" v-if="creditBetCount === 0">
              <p>购彩篮为空</p>
            </div>
          </div>
          
          <div class="cart-footer">
            <div class="cart-summary">
              <div class="summary-row">
                <span>共 <strong>{{ creditBetCount }}</strong> 单，合计 <strong class="total-amount">{{ creditBetTotal.toFixed(0) }}</strong> 元</span>
              </div>
              <div class="summary-row">
                <span>余额: <strong class="balance">{{ balance }}</strong></span>
              </div>
            </div>
            <div class="cart-submit-btn" :class="{ disabled: creditBetCount === 0 }" @click="submitFromCart">
              立即投注
            </div>
          </div>
        </div>
      </van-popup>
      
      
      <van-popup v-model:show="showRecentBets" position="bottom" round teleport="body" :style="{ height: '70vh' }">
        <div class="recent-bets-panel">
          <div class="recent-header">
            <span class="recent-title">下注记录</span>
            <van-icon name="cross" class="close-btn" @click="showRecentBets = false" />
          </div>
          
          
          <div class="time-tabs">
            <div class="tab-item" :class="{ active: recentTimeTab === 'today' }" @click="recentTimeTab = 'today'; loadRecentBets()">今日</div>
            <div class="tab-item" :class="{ active: recentTimeTab === 'yesterday' }" @click="recentTimeTab = 'yesterday'; loadRecentBets()">昨日</div>
            <div class="tab-item" :class="{ active: recentTimeTab === 'week' }" @click="recentTimeTab = 'week'; loadRecentBets()">一周内</div>
          </div>
          
          
          <div class="recent-list">
            <div class="recent-item" v-for="bet in recentBetList" :key="bet.id">
              <div class="bet-header">
                <span class="lottery-info">{{ bet.cptitle }} 第 {{ bet.expect }} 期</span>
                <span class="bet-time">{{ formatBetTime(bet.oddtime) }}</span>
              </div>
              
              <div class="bet-content">{{ bet.playtitle }}</div>
              
              <div class="bet-result" v-if="bet.opencode">
                <span class="label">开奖号码</span>
                <div class="balls">
                  <span class="ball" :class="getBallColor(bet.opencode.split(',')[0])">{{ bet.opencode.split(',')[0] }}</span>
                  <span class="plus">+</span>
                  <span class="ball" :class="getBallColor(bet.opencode.split(',')[1])">{{ bet.opencode.split(',')[1] }}</span>
                  <span class="plus">+</span>
                  <span class="ball" :class="getBallColor(bet.opencode.split(',')[2])">{{ bet.opencode.split(',')[2] }}</span>
                  <span class="equals">=</span>
                  <span class="ball sum" :class="getSumColor(bet.opencode)">{{ getSum(bet.opencode) }}</span>
                  <span class="sum-type">{{ getSumType(bet.opencode) }}</span>
                </div>
              </div>
              <div class="bet-result" v-else>
                <span class="label">开奖号码</span>
                <span class="waiting">等待开奖</span>
              </div>
              
              <div class="bet-footer">
                <span class="balance-after">下注后积分: ¥{{ parseFloat(bet.amountafter || 0).toFixed(2) }}</span>
                <span class="settle-amount" :class="getSettleClass(bet)">
                  {{ getSettleText(bet) }}
                </span>
              </div>
            </div>
            
            <div class="empty-tip" v-if="recentBetList.length === 0 && !recentLoading">
              没有更多了
            </div>
            <div class="loading-tip" v-if="recentLoading">
              加载中...
            </div>
          </div>
        </div>
      </van-popup>
    </div>
  </transition>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { showToast, showLoadingToast, closeToast } from 'vant'
import { xy28Api } from '@/api'
import ChaseNumberPopup from './ChaseNumberPopup.vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  currentIssue: {
    type: String,
    default: ''
  },
  balance: {
    type: [String, Number],
    default: '0.00'
  },
  lotteryCode: {
    type: String,
    default: 'yfxy28'
  },
  isSealed: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:show', 'submit'])

const showPlayRules = ref(false)
const showHot = ref(false)
const showMiss = ref(false)
const hotColdStats = ref({ numbers: {} })

const showCartPanel = ref(false)

const playRulesContent = {
  liangmian: [
    { title: '大小：', content: '开出的三个号码的和值为游戏结果，三个号码和值0-13为小，14-27为大。' },
    { title: '单双：', content: '和值为偶数，则为双，奇数为单。' },
    { title: '大单/大双', content: '大单：开出的三个号码的和值为开奖结果和值为15、17、19、21、23、25、27即赢\n大双：开出三个号码的和值为开奖结果和值为14、16、18、20、22、24、26即赢' },
    { title: '小单/小双', content: '小单：开出的三个号码的和值为开奖结果\n和值为：1、3、5、7、9、11、13即赢\n小双：开出的三个号码的和值为开奖结果\n和值为：0、2、4、6、8、10、12即赢' },
    { title: '极大/极小', content: '极大：开出的三个号码的和值为游戏结果\n三个号码的和值22-27为极大\n极小：开出的三个号码的和值为游戏结果\n三个号码的和值0-5为极小' }
  ],
  longhu: [
    { title: '龙虎说明：', content: '龙：第一个号码大于第三个号码即为龙\n虎：第三个号码大于第一个号码即为虎\n豹：三个号码相同即为豹（通杀）' }
  ],
  quwei: [
    { title: '对子：', content: '开出的三个号码中任意两个号码相同即为对子。' },
    { title: '顺子：', content: '开出的三个号码为连续数字即为顺子（如：1,2,3 或 7,8,9）。' },
    { title: '豹子：', content: '开出的三个号码全部相同即为豹子（如：1,1,1 或 5,5,5）。' }
  ],
  hezhi: [
    { title: '和值说明：', content: '开出的三个号码相加的总和为和值，范围0-27。\n投注对应和值号码，开奖结果与投注号码相同即中奖。' }
  ]
}

const currentRules = computed(() => playRulesContent[creditActiveCategory.value] || playRulesContent.liangmian)

const close = () => {
  emit('update:show', false)
}

const showRecentBets = ref(false)
const recentTimeTab = ref('today')
const recentBetList = ref([])
const recentLoading = ref(false)

async function loadRecentBets() {
  recentLoading.value = true
  try {
    const res = await xy28Api.getBetHistory({
      lotteryCode: props.lotteryCode,
      timeRange: recentTimeTab.value,
      page: 1,
      pageSize: 50
    })
    if (res.code === 0) {
      recentBetList.value = res.data?.list || []
    }
  } catch (e) {
    console.error('加载近期投注失败:', e)
  } finally {
    recentLoading.value = false
  }
}

function formatBetTime(timestamp) {
  if (!timestamp) return ''
  const date = new Date(timestamp * 1000)
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  const hours = String(date.getHours()).padStart(2, '0')
  const minutes = String(date.getMinutes()).padStart(2, '0')
  const seconds = String(date.getSeconds()).padStart(2, '0')
  return `${month}-${day} ${hours}:${minutes}:${seconds}`
}

function getBallColor(num) {
  const n = parseInt(num)
  if (n >= 0 && n <= 4) return 'blue'
  if (n >= 5 && n <= 9) return 'red'
  return 'blue'
}

function getSum(opencode) {
  if (!opencode) return ''
  const nums = opencode.split(',').map(Number)
  return nums.reduce((a, b) => a + b, 0)
}

function getSumColor(opencode) {
  const sum = getSum(opencode)
  if (sum >= 0 && sum <= 4) return 'green'
  if (sum >= 5 && sum <= 9) return 'blue'
  if (sum >= 10 && sum <= 17) return 'red'
  if (sum >= 18 && sum <= 22) return 'blue'
  if (sum >= 23 && sum <= 27) return 'green'
  return 'blue'
}

function getSumType(opencode) {
  const sum = getSum(opencode)
  const isBig = sum >= 14
  const isOdd = sum % 2 === 1
  return (isBig ? '大' : '小') + (isOdd ? '单' : '双')
}

function getSettleText(bet) {
  if (!bet) return ''
  const profit = parseFloat(bet.okamount || 0) - parseFloat(bet.amount || 0)

  if (bet.isdraw == 1 || bet.isdraw == -1) {
    return `已结算 ¥${profit >= 0 ? '+' : ''}${profit.toFixed(2)}`
  } else if (bet.isdraw == -2) {
    return '已撤单'
  }
  return '未结算'
}

function getSettleClass(bet) {
  if (!bet) return {}
  const profit = parseFloat(bet.okamount || 0) - parseFloat(bet.amount || 0)
  if (bet.isdraw == 1 || bet.isdraw == -1) {
    return { win: profit > 0, lose: profit < 0 }
  }
  return {}
}

const creditActiveCategory = ref('liangmian')
const creditSelectedChip = ref(10)
const creditChips = [10, 50, 100, 500]
const creditBetList = ref([]) // 购彩篮列表，每条是独立记录 { id, key, label, amount }
const tempSelected = ref({}) // 临时选中（未添加）

const creditCategories = [
  { key: 'liangmian', label: '两面' },
  { key: 'longhu', label: '龙虎' },
  { key: 'quwei', label: '趣味' },
  { key: 'hezhi', label: '和值' }
]

const currentCreditCategory = computed(() => {
  return creditCategories.find(c => c.key === creditActiveCategory.value) || creditCategories[0]
})

const creditBets = {
  liangmian: {
    all: [
      { value: 'big', label: '大' },
      { value: 'small', label: '小' },
      { value: 'odd', label: '单' },
      { value: 'even', label: '双' },
      { value: 'big_odd', label: '大单' },
      { value: 'big_even', label: '大双' },
      { value: 'small_odd', label: '小单' },
      { value: 'small_even', label: '小双' },
      { value: 'jida', label: '极大' },
      { value: 'jixiao', label: '极小' }
    ]
  },
  longhu: [
    { value: 'long', label: '龙' },
    { value: 'hu', label: '虎' },
    { value: 'bao', label: '豹' }
  ],
  quwei: [
    { value: 'duizi', label: '对子' },
    { value: 'shunzi', label: '顺子' },
    { value: 'baozi', label: '豹子' }
  ]
}

const creditBetCount = computed(() => creditBetList.value.length)
const creditBetTotal = computed(() => {
  return creditBetList.value.reduce((sum, item) => sum + item.amount, 0)
})
const tempSelectedCount = computed(() => Object.keys(tempSelected.value).length)
const tempSelectedTotal = computed(() => {
  return Object.values(tempSelected.value).reduce((sum, val) => sum + val, 0)
})

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
    console.error('[CreditBetPanel] 加载玩法赔率失败:', e)
  }
}

function getNumberOdds(num) {
  return getOdds(`tm_${num}`)
}

async function loadHotColdStats() {
  if (!props.lotteryCode) return
  try {
    const res = await xy28Api.getHotCold(props.lotteryCode, 100)
    if (res.code === 0 && res.data) {
      hotColdStats.value = {
        ...res.data.stats,
        numbers: res.data.numbers || {}
      }
    }
  } catch (e) {
    console.error('[CreditBetPanel] 加载冷热遗漏失败:', e)
  }
}

function getHotColor(count) {
  if (!count) return 'cold'
  return count >= 50 ? 'hot' : count >= 20 ? 'warm' : 'cold'
}

onMounted(() => {
  loadPlayTypes()
  loadHotColdStats()
})

function toggleCreditBet(item) {
  const key = item.value
  if (tempSelected.value[key]) {
    delete tempSelected.value[key]
  } else {
    tempSelected.value[key] = creditSelectedChip.value
  }
}

function addToCart() {
  const keys = Object.keys(tempSelected.value)
  if (keys.length === 0) {
    showToast('请先选择号码')
    return
  }
  

  keys.forEach(key => {
    creditBetList.value.push({
      id: Date.now() + Math.random(),
      key: key,
      label: getBetLabel(key),
      amount: tempSelected.value[key]
    })
  })
  
  tempSelected.value = {}
  showToast(`已添加${keys.length}个选号到购彩篮`)
}

function clearCreditBets() {
  creditBetList.value = []
}

function removeCartItem(index) {
  creditBetList.value.splice(index, 1)
}

function updateCartAmount(index, value) {
  const num = parseInt(value) || 0
  if (num > 0 && creditBetList.value[index]) {
    creditBetList.value[index].amount = num
  }
}

function getBetLabel(key) {
  const labelMap = {
    'big': '大', 'small': '小', 'odd': '单', 'even': '双',
    'big_odd': '大单', 'big_even': '大双', 'small_odd': '小单', 'small_even': '小双',
    'jida': '极大', 'jixiao': '极小',
    'long': '龙', 'hu': '虎', 'bao': '豹',
    'duizi': '对子', 'shunzi': '顺子', 'baozi': '豹子'
  }
  if (labelMap[key]) return labelMap[key]
  if (key.startsWith('tm_')) return key.replace('tm_', '') + '点'
  return key
}

function submitFromCart() {
  if (creditBetCount.value === 0) {
    showToast('购彩篮为空')
    return
  }
  submitCreditBet()
  showCartPanel.value = false
}

const showChasePopup = ref(false)

const chaseBetData = computed(() => {
  const allBets = []
  

  creditBetList.value.forEach(item => {
    allBets.push({
      key: item.key,
      label: item.label,
      amount: item.amount
    })
  })
  

  Object.keys(tempSelected.value).forEach(key => {
    allBets.push({
      key: key,
      label: getBetLabel(key),
      amount: tempSelected.value[key]
    })
  })
  
  if (allBets.length === 0) return {}
  
  return {
    playType: allBets.length > 1 ? 'xy28_combined' : allBets[0]?.key,
    selections: allBets.map(item => ({
      type: item.key.startsWith('tm_') ? 'number' : 'combo',
      value: item.key,
      label: item.label,
      amount: item.amount
    }))
  }
})

function openChasePopup() {

  if (creditBetCount.value === 0 && tempSelectedCount.value === 0) {
    showToast('请先选择投注项')
    return
  }
  showChasePopup.value = true
}

function onChaseConfirm(data) {
  showToast(`追号${data.period}期，共${data.total}元`)
  showChasePopup.value = false
}

function onChaseSuccess(data) {
  showToast({ type: 'success', message: '追号成功' })
  clearCreditBets()
  tempSelected.value = {} // 清空临时选中
  showChasePopup.value = false
  emit('update:show', false)
}

async function submitCreditBet() {

  if (props.isSealed) {
    showToast('封盘中，暂停投注')
    return
  }
  

  const allBets = []
  

  creditBetList.value.forEach(item => {
    allBets.push({
      key: item.key,
      label: item.label,
      amount: item.amount
    })
  })
  

  Object.keys(tempSelected.value).forEach(key => {
    allBets.push({
      key: key,
      label: getBetLabel(key),
      amount: tempSelected.value[key]
    })
  })
  
  if (allBets.length === 0) {
    showToast('请选择投注项')
    return
  }
  

  const betParts = allBets.map(item => {
    const prefix = /^\d+$/.test(item.label) ? '点' : ''
    return `${item.label}${prefix}:${item.amount}`
  })
  const betText = betParts.join('|')
  

  clearCreditBets()
  tempSelected.value = {}
  emit('update:show', false)
  emit('submit', betText)
}

defineExpose({
  clearCreditBets,
  toggleCreditBet
})
</script>

<style lang="less" scoped>

.credit-bet-panel {
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  z-index: 1001;
  background: #fff;
  max-height: 80vh;
  display: flex;
  flex-direction: column;
  
  .credit-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 14px;
    background: #fff;
    border-bottom: 1px solid #eee;
    
    .collapse-btn {
      display: flex;
      align-items: center;
      gap: 4px;
      color: #5691fe;
      font-size: 14px;
      font-weight: 500;
      min-width: 60px;
      
      :deep(.van-icon) {
        transform: rotate(90deg); 
        font-size: 12px; 
      }
    }
    
    .credit-title {
      font-size: 16px;
      font-weight: 600;
      color: #333;
    }
    
    .placeholder {
      min-width: 60px;
    }
  }
  
  .credit-body {
    display: flex;
    flex: 1;
    overflow: hidden;
    background: #f7f8fa; 
    
    .credit-sidebar {
      width: 86px; 
      background: #f7f8fa; 
      border-right: none;
      flex-shrink: 0;
      
      .sidebar-item {
        padding: 16px 0;
        text-align: center;
        font-size: 14px;
        color: #666;
        position: relative;
        background: #f7f8fa;
        transition: all 0.2s;
        
        &.active {
          color: #333; 
          font-weight: 600;
          background: #fff; 
          font-size: 15px;
          
          &::before {
            content: '';
            position: absolute;
            left: 0;
            top: 18px;
            bottom: 18px;
            width: 4px;
            background: #5691fe;
            border-radius: 0 4px 4px 0;
          }
        }
      }
    }
    
    .credit-content {
      flex: 1;
      display: flex;
      flex-direction: column;
      overflow: hidden;
      background: #fff; 
      margin: 0;
      border-radius: 0;
      position: relative; 
      
      .content-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 8px 12px; 
        border-bottom: none; 
        
        .toolbar-left {
          display: flex;
          align-items: center;
          gap: 4px;
          font-size: 13px;
          color: #999; 
        }
        
        .toolbar-right {
          display: flex;
          gap: 8px;
          
          .tool-btn {
            padding: 3px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 12px;
            color: #5691fe;
            border-color: #5691fe;
            background: #fff;
            cursor: pointer;
            
            &.active {
              background: #5691fe;
              color: #fff;
            }
          }
        }
      }
      
      .play-title {
        padding: 4px 12px 10px; 
        font-size: 14px;
        font-weight: 500;
        color: #333;
      }
      
      .credit-bets-area {
        height: 240px; 
        overflow-y: auto;
        padding: 0 10px 10px;
        -webkit-overflow-scrolling: touch;
        
        .bet-row {
          display: flex;
          gap: 8px;
          margin-bottom: 8px;
          
          &.half .credit-bet-btn {
            flex: 0 0 calc(50% - 4px);
          }
          
          &.three .credit-bet-btn {
            flex: 0 0 calc(33.33% - 6px);
          }
          
          .credit-bet-btn {
            flex: 1;
          }
        }
        
        .bet-grid-4 {
          display: grid;
          grid-template-columns: repeat(4, 1fr);
          gap: 8px;
        }
        
        .bet-grid-28 {
          display: grid;
          grid-template-columns: repeat(4, 1fr);
          gap: 6px;
        }
        
        .credit-bet-btn {
          display: flex;
          flex-direction: column;
          align-items: center;
          justify-content: center;
          padding: 10px 4px;
          background: #fff;
          border: 1px solid #ddd;
          border-radius: 6px;
          box-shadow: none;
          position: relative;
          
          &.selected {
            border-color: #5691fe;
            background: #e8f4ff;
            
            &::after {
              content: '';
              position: absolute;
              top: 0;
              right: 0;
              border-width: 0 20px 20px 0;
              border-style: solid;
              border-color: transparent #5691fe transparent transparent;
            }
          }
          
          &.num {
            padding: 10px 6px;
          }
          
          .btn-label {
            font-size: 16px;
            font-weight: 500;
            color: #333;
          }
          
          .btn-odds {
            font-size: 11px;
            color: #999;
            margin-top: 4px;
          }
          

          .badge {
            position: absolute;
            font-size: 10px;
            padding: 1px 4px;
            border-radius: 3px;
            color: #fff;
            min-width: 16px;
            text-align: center;
            
            &.hot-badge {
              top: 2px;
              right: 2px;
              
              &.hot { background: #ff4757; }
              &.warm { background: #ffa502; }
              &.cold { background: #3498db; }
            }
            
            &.miss-badge {
              bottom: 2px;
              left: 2px;
              background: #999db2;
            }
          }
        }
      }
      
      .credit-selection-tip {
        position: absolute;
        bottom: 15px; 
        left: 50%;
        transform: translateX(-50%);
        padding: 6px 24px;
        background: #ff5e5e; 
        border-radius: 20px;
        color: #fff;
        font-size: 13px;
        white-space: nowrap;
        box-shadow: 0 2px 8px rgba(255, 94, 94, 0.4);
        z-index: 10;
      }
    }
  }
  
    .credit-footer {
    background: #fff;
    border-top: 1px solid #f0f0f0;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.03);
    
    .footer-row {
      display: flex;
      align-items: center;
      padding: 8px 10px;
      
      &.chips-row {
        gap: 8px;
        padding-top: 10px;
        padding-bottom: 10px;
        border-bottom: 1px solid #f5f5f5;
        

        .chip-delete {
          width: 44px;
          height: 36px;
          display: flex;
          align-items: center;
          justify-content: center;
          background: #f2f3f5;
          border-radius: 4px;
          color: #999;
          
          &:active {
            background: #e5e6eb;
          }
        }
        

        .chip-quick {
          height: 36px;
          padding: 0 10px;
          display: flex;
          align-items: center;
          gap: 4px;
          background: #fff;
          border: 1px solid #ddd;
          border-radius: 4px;
          font-size: 13px;
          color: #666;
          
          .van-icon {
            font-size: 14px;
            color: #999;
          }
        }
        

        .chips-scroll {
          flex: 1;
          display: flex;
          gap: 6px;
          overflow-x: auto;
          -webkit-overflow-scrolling: touch;
          
          &::-webkit-scrollbar { display: none; }
          
          .chip-item {
            flex-shrink: 0;
            width: 48px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            color: #333;
            font-weight: 500;
            
            &.active {
              border-color: #5691fe;
              color: #5691fe;
              background: #ecf5ff;
            }
          }
        }
        

        .chip-custom {
          width: 60px;
          height: 36px;
          display: flex;
          align-items: center;
          justify-content: center;
          background: #fff;
          border: 1px solid #5691fe;
          border-radius: 4px;
          font-size: 14px;
          color: #5691fe;
          font-weight: 600;
        }
      }
      
      &.actions-row {
        gap: 0; 
        justify-content: space-between;
        padding-top: 8px;
        padding-bottom: max(8px, env(safe-area-inset-bottom));
        
        .action-item {
          display: flex;
          flex-direction: column;
          align-items: center;
          justify-content: center;
          min-width: 50px;
          gap: 2px;
          
          .icon-box {
            font-size: 20px;
            color: #666;
            margin-bottom: 2px;
          }
          
          span {
            font-size: 11px;
            color: #666;
            line-height: 1;
          }
          

          &.cart-btn {
            position: relative;
            .badge {
              position: absolute;
              top: -4px;
              right: 8px;
              background: #ff3b30;
              color: #fff;
              font-size: 10px;
              padding: 1px 4px;
              border-radius: 10px;
              min-width: 14px;
              text-align: center;
            }
          }
          

          position: relative;
          .temp-badge {
            position: absolute;
            top: -4px;
            right: 8px;
            background: #5691fe;
            color: #fff;
            font-size: 10px;
            padding: 1px 4px;
            border-radius: 10px;
            min-width: 14px;
            text-align: center;
          }
        }
        

        .submit-btn {
          flex: 1;
          margin: 0 10px;
          height: 44px;
          background: linear-gradient(90deg, #5691fe, #4378e8);
          border-radius: 6px;
          display: flex;
          flex-direction: column;
          align-items: center;
          justify-content: center;
          color: #fff;
          box-shadow: 0 2px 6px rgba(86, 145, 254, 0.3);
          
          .btn-text {
            font-size: 15px;
            font-weight: 500;
          }
          
          .btn-amount {
            font-size: 12px;
            opacity: 0.9;
            margin-top: 1px;
          }
          
          &.disabled {
            background: #ebecf5;
            color: #999;
            box-shadow: none;
            pointer-events: none;
            cursor: not-allowed;
            
            .btn-text { color: #999; }
            .btn-amount { color: #ccc; }
          }
          
          &:active {
            transform: scale(0.98);
          }
        }
      }
    }
  }
}

.play-rules-popup {
  width: 75%;
  max-width: 300px;
  height: 380px;
  overflow: hidden;
  top: 50% !important;
  left: 50% !important;
  transform: translate(-50%, -50%) !important;
  
  .popup-indicator {
    width: 140px;
    height: 6px;
    background: linear-gradient(90deg, #01d1ff, #0aabff);
    border-radius: 0 0 4px 4px;
    margin: 0 auto;
  }
  
  .rules-content {
    padding: 15px 16px 0;
    height: calc(100% - 6px);
    display: flex;
    flex-direction: column;
    
    .rules-title {
      font-size: 15px;
      font-weight: 600;
      color: #333;
      margin-bottom: 12px;
      flex-shrink: 0;
    }
    
    .rules-body {
      flex: 1;
      overflow-y: auto;
      font-size: 13.76px;
      color: #666;
      line-height: 1.7;
      -webkit-overflow-scrolling: touch;
      
      p {
        margin: 0 0 6px;
        
        strong {
          color: #333;
        }
      }
    }
    
    .rules-footer {
      flex-shrink: 0;
      margin: 15px -16px 0;
      padding: 15px;
      text-align: center;
      font-size: 16px;
      font-weight: 500;
      color: #333;
      border-top: 1px solid #eee;
      cursor: pointer;
      
      &:active {
        background: #f9f9f9;
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
</style>

<style lang="less">
.credit-cart-panel {
  display: flex;
  flex-direction: column;
  height: 100%;
  
  .cart-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 14px;
    border-bottom: 1px solid #f0f0f0;
    
    .collapse-btn {
      display: flex;
      align-items: center;
      color: #5691fe;
      font-size: 14px;
      font-weight: 500;
    }
    
    .cart-title {
      font-size: 16px;
      font-weight: 600;
      color: #333;
    }
    
    .clear-btn {
      color: #999;
      font-size: 14px;
    }
  }
  
  .cart-list {
    flex: 1;
    overflow-y: auto;
    padding: 10px 14px;
    
    .cart-item {
      display: flex;
      align-items: center;
      padding: 12px 0;
      border-bottom: 1px solid #f5f5f5;
      
      .item-label {
        width: 80px;
        font-size: 15px;
        color: #333;
        font-weight: 500;
        text-align: left;
      }
      
      .item-amount {
        flex: 1;
        display: flex;
        justify-content: center;
        
        .amount-input {
          width: 80px;
          height: 32px;
          padding: 0 12px;
          border: 1px solid #ddd;
          border-radius: 4px;
          font-size: 14px;
          color: #333;
          text-align: center;
          outline: none;
          box-sizing: border-box;
          -moz-appearance: textfield;
          
          &::-webkit-outer-spin-button,
          &::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
          }
          
          &:focus {
            border-color: #5691fe;
          }
        }
      }
      
      .item-delete {
        width: 50px;
        text-align: right;
        font-size: 20px;
        color: #999;
      }
    }
    
    .cart-empty {
      display: flex;
      align-items: center;
      justify-content: center;
      height: 150px;
      color: #999;
      font-size: 14px;
    }
  }
  
  .cart-footer {
    display: flex;
    align-items: center;
    padding: 12px 14px;
    border-top: 1px solid #f0f0f0;
    background: #fff;
    
    .cart-summary {
      flex: 1;
      
      .summary-row {
        font-size: 13px;
        color: #666;
        line-height: 1.6;
        
        strong {
          color: #333;
        }
        
        .total-amount {
          color: #f5222d;
          font-size: 15px;
        }
        
        .balance {
          color: #f5222d;
        }
      }
    }
    
    .cart-submit-btn {
      padding: 12px 30px;
      background: linear-gradient(90deg, #5691fe, #4378e8);
      border-radius: 6px;
      color: #fff;
      font-size: 15px;
      font-weight: 600;
      
      &.disabled {
        background: #ccc;
      }
    }
  }
}

.recent-bets-panel {
  display: flex;
  flex-direction: column;
  height: 100%;
  background: #f5f7fa;
  
  .recent-header {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 14px;
    background: #fff;
    border-bottom: 1px solid #eee;
    position: relative;
    
    .recent-title {
      font-size: 16px;
      font-weight: 600;
      color: #333;
    }
    
    .close-btn {
      position: absolute;
      right: 14px;
      font-size: 20px;
      color: #999;
    }
  }
  
  .time-tabs {
    display: flex;
    background: #fff;
    border-bottom: 1px solid #eee;
    
    .tab-item {
      flex: 1;
      padding: 12px 0;
      text-align: center;
      font-size: 14px;
      color: #666;
      position: relative;
      
      &.active {
        color: #fff;
        background: #5691fe;
      }
    }
  }
  
  .recent-list {
    flex: 1;
    overflow-y: auto;
    padding: 10px;
    -webkit-overflow-scrolling: touch;
    
    .recent-item {
      background: #fff;
      border-radius: 8px;
      padding: 12px;
      margin-bottom: 10px;
      border-left: 4px solid #ff9500;
      
      .bet-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
        
        .lottery-info {
          font-size: 14px;
          font-weight: 500;
          color: #ff9500;
        }
        
        .bet-time {
          font-size: 12px;
          color: #999;
        }
      }
      
      .bet-content {
        font-size: 13px;
        color: #333;
        line-height: 1.6;
        margin-bottom: 10px;
        word-break: break-all;
      }
      
      .bet-result {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
        
        .label {
          font-size: 13px;
          color: #666;
          margin-right: 10px;
          flex-shrink: 0;
        }
        
        .waiting {
          font-size: 13px;
          color: #999;
        }
        
        .balls {
          display: flex;
          align-items: center;
          gap: 4px;
          
          .ball {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
            color: #fff;
            
            &.red { background: #ff5252; }
            &.blue { background: #5691fe; }
            &.green { background: #4caf50; }
            
            &.sum {
              width: 28px;
              height: 28px;
              font-size: 13px;
            }
          }
          
          .plus, .equals {
            font-size: 14px;
            color: #999;
          }
          
          .sum-type {
            font-size: 13px;
            color: #333;
            font-weight: 500;
            margin-left: 6px;
          }
        }
      }
      
      .bet-footer {
        display: flex;
        justify-content: space-between;
        padding-top: 10px;
        border-top: 1px dashed #eee;
        
        .balance-after {
          font-size: 13px;
          color: #666;
        }
        
        .settle-amount {
          font-size: 13px;
          font-weight: 500;
          color: #999;
          
          &.win {
            color: #ff5252;
          }
          
          &.lose {
            color: #999;
          }
        }
      }
    }
    
    .empty-tip, .loading-tip {
      text-align: center;
      padding: 30px;
      color: #999;
      font-size: 14px;
    }
  }
}
</style>
