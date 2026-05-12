<template>
  <div class="lhc-page">
    <Header 
      :title="lotteryTitle" 
      :subtitle="currentPlayName" 
      @toggle-menu="showPlayMenu = true"
      @click-right="showLotteryMenu = true"
    />
    
    <div class="page-content">
      <div class="fixed-info">
        <LotteryInfo 
          :showExpect="issueInfo" 
          :lastOpenCode="lastOpenCode"
          :gametimes="{ ms: remainMs }"
          :is-drawing="isDrawing"
          @show-history="showHistory = true"
        />
      </div>

      <div class="scroll-area">
        
        <div class="play-panel" :class="{ 'is-sealed': isSealed }">
          
          <transition name="fade">
            <div class="sealed-overlay" v-if="isSealed">
              <div class="sealed-content">
                <div class="lock-ring">
                  <van-icon name="lock" class="lock-icon" />
                </div>
                <span class="text">已封盘</span>
                <span class="sub-text" v-if="!isDrawing">
                  距离开奖还有 <span class="count">{{ sealCountDown }}</span> 秒
                </span>
                <span class="sub-text" v-else>正在开奖中...</span>
              </div>
            </div>
          </transition>

          
          <div class="play-tips" v-if="currentPlayTip">
            <van-icon name="info-o" />
            <span>{{ currentPlayTip }}</span>
          </div>

          
          <div class="panel-content">
            <transition name="fade" mode="out-in">
              <div :key="currentPlayType" class="play-container">
                
                <BallSelector
                  v-if="isNumberPlay"
                  :numbers="numbers"
                  :selected="selectedData"
                  odds="1.98"
                  @update:selected="val => selectedData = val"
                />

                
                <LiangMianSelect
                  v-else-if="isLiangMianPlay"
                  :options="liangMianOptions"
                  :selected-values="selectedData"
                  :play-id="currentPlayType"
                  @toggle="toggleSelection"
                />

                
                <BanBoSelect
                  v-else-if="currentPlayType === 'tmbb'"
                  :options="banBoOptions"
                  :selected-values="selectedData"
                  @toggle="toggleSelection"
                />

                
                <ShengXiaoSelect
                  v-else-if="isShengXiaoPlay"
                  :options="shengXiaoOptions"
                  :selected-values="selectedData"
                  @toggle="toggleSelection"
                />

                
                <WeiShuSelect
                  v-else-if="isWeiShuPlay"
                  :options="weiShuOptions"
                  :selected-values="selectedData"
                  @toggle="toggleSelection"
                />

                
                <LiangMianSelect
                  v-else-if="isSeBoPlay"
                  :options="seBoOptions"
                  :selected-values="selectedData"
                  :play-id="currentPlayType"
                  @toggle="toggleSelection"
                />

                
                <LiangMianSelect
                  v-else-if="isWuXingPlay"
                  :options="wuXingOptions"
                  :selected-values="selectedData"
                  :play-id="currentPlayType"
                  @toggle="toggleSelection"
                />
                
                
                <LiangMianSelect
                  v-else-if="isZongXiaoPlay"
                  :options="zongXiaoOptions"
                  :selected-values="selectedData"
                  :play-id="currentPlayType"
                  @toggle="toggleSelection"
                />
              </div>
            </transition>
          </div>
        </div>
      </div>
    </div>

    <BettingFooter 
      :bet-count="betCount"
      :balance="userBalance"
      :disabled="isSealed"
      v-model:unit-price="unitPrice"
      v-model:multiplier="multiplier"
      @clear="clearSelection"
      @submit="onSubmit"
    />

    
    <van-popup v-model:show="showPlayMenu" position="top" round :style="{ height: '70%' }">
      <div class="play-menu">
        <div class="menu-header">
          <span>选择玩法</span>
          <van-icon name="cross" class="close-icon" @click="showPlayMenu = false" />
        </div>
        <div class="menu-body">
          <div class="menu-sidebar">
             <div 
               v-for="(group, key) in playGroups" 
               :key="key"
               class="menu-item"
               :class="{ active: activeGroup === key }"
               @click="activeGroup = key"
             >
               {{ group.name }}
             </div>
          </div>
          <div class="menu-content">
             <div class="sub-play-grid">
               <div 
                 v-for="play in playGroups[activeGroup].plays" 
                 :key="play.id"
                 class="sub-play-item"
                 :class="{ active: currentPlayType === play.id }"
                 @click="selectPlay(play)"
               >
                 {{ play.name }}
               </div>
             </div>
          </div>
        </div>
      </div>
    </van-popup>

    
    <van-popup 
      v-model:show="showLotteryMenu" 
      position="top" 
      round 
      :style="{ height: 'auto', maxHeight: '50%' }"
    >
      <div class="play-menu">
        <div class="menu-header">
          <span>切换彩种</span>
          <van-icon name="cross" class="close-icon" @click="showLotteryMenu = false" />
        </div>
        <div class="menu-body" style="padding: 16px;">
          <div class="sub-play-grid">
            <div 
              v-for="(name, code) in lotteryNamesMap" 
              :key="code"
              class="sub-play-item"
              :class="{ active: lotteryCode === code }"
              @click="switchLottery(code)"
            >
              {{ name }}
            </div>
          </div>
        </div>
      </div>
    </van-popup>

    
    <van-popup v-model:show="showHistory" position="bottom" round :style="{ height: '70%' }">
      <div class="history-popup">
        <div class="history-header">
          <span class="history-title">最近开奖</span>
          <van-icon name="cross" class="close-icon" @click="showHistory = false" />
        </div>
        <van-empty v-if="historyList.length === 0" description="暂无数据" />
        <div v-else class="history-list">
           <div v-for="(item, idx) in historyList" :key="idx" class="his-item">
             <div class="his-info">
               <div class="his-expect">第 {{ item.expect }} 期</div>
               <div class="his-time">{{ item.time }}</div>
             </div>
             <div class="his-balls-wrapper">
               <div class="his-balls">
                 
                 <div v-for="(n,i) in item.codes.slice(0, 6)" :key="'n'+i" class="ball-item">
                   <span class="mini-ball" :class="getNumberColorClassLocal(n)">{{ n }}</span>
                   <span class="mini-sx">{{ getShengXiaoLocal(n) }}</span>
                 </div>
                 
                 <span class="plus">+</span>
                 
                 <div class="ball-item special">
                   <span class="mini-ball" :class="getNumberColorClassLocal(item.codes[6])">{{ item.codes[6] || '--' }}</span>
                   <span class="mini-sx">{{ getShengXiaoLocal(item.codes[6]) }}</span>
                 </div>
               </div>
             </div>
           </div>
        </div>
      </div>
    </van-popup>

  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, watch } from 'vue'
import { Popup as VanPopup, Icon as VanIcon, Empty as VanEmpty, showToast, showLoadingToast, closeToast } from 'vant'
import { useRouter, useRoute } from 'vue-router'
import { lhcApi } from '@/api'
import { lotteryWS } from '@/utils/websocket'
import Header from './components/Header.vue'
import LotteryInfo from './components/LotteryInfo.vue'
import BettingFooter from './components/BettingFooter.vue'
import BallSelector from './components/BallSelector.vue'

import LiangMianSelect from '@/components/game/LiangMianSelect.vue'
import BanBoSelect from '@/components/game/BanBoSelect.vue'
import ShengXiaoSelect from '@/components/game/ShengXiaoSelect.vue'
import WeiShuSelect from '@/components/game/WeiShuSelect.vue'

import { 
  getNumberColorClass, 
  getShengXiao,
  getLiangMianOptions, 
  getBanBoOptions, 
  getShengXiaoOptions, 
  getWeiShuOptions,
  getSeBoOptions,
  getWuXingOptions,
  getZongXiaoOptions
} from '@/utils/lhcData'

const getNumberColorClassLocal = getNumberColorClass
const getShengXiaoLocal = (num) => {
  if (!num || num === '--') return '-'
  return getShengXiao(num)
}
const route = useRoute()
const router = useRouter()

const lotteryCode = computed(() => route.params.code || 'dflhc')

const lotteryNamesMap = {
  'lhc': '香港六合彩',
  'dflhc': '大发六合彩',
  'lhc10f': '10分六合',
  'lhc1f': '1分六合',
  'lhc5f': '5分六合'
}

const lotteryTitle = computed(() => lotteryNamesMap[lotteryCode.value] || '六合彩')
const userBalance = ref('0.00')
const showPlayMenu = ref(false)
const showHistory = ref(false)
const showLotteryMenu = ref(false)
const isLoading = ref(false)

let wsCleanups = []
let countdownTimer = null

const switchLottery = (code) => {
  showLotteryMenu.value = false
  if (code === lotteryCode.value) return
  

  lotteryWS.unsubscribe(lotteryCode.value)
  
  router.push({ 
    name: 'LotteryLhc', 
    params: { code } 
  })
}

watch(lotteryCode, (newCode, oldCode) => {
  if (newCode && newCode !== oldCode) {
    clearSelection()
    if (oldCode) lotteryWS.unsubscribe(oldCode)
    initLottery()
  }
})

const issueInfo = reactive({ currFullExpect: '---', lastFullExpect: '---' })
const lastOpenCode = ref(['--', '--', '--', '--', '--', '--', '--'])
const remainMs = ref(0)
const isDrawing = ref(false)
const historyList = ref([])
const closeSeconds = ref(10) // 封盘秒数

const isSealed = computed(() => remainMs.value <= closeSeconds.value * 1000 || isDrawing.value)
const sealCountDown = computed(() => Math.ceil(remainMs.value / 1000))

const playGroups = {
  tm: {
    name: '特码',
    plays: [
      { id: 'tmzx', name: '特码直选', tip: '从1-49中任选1个或多个号码' },
      { id: 'tmlm', name: '特码两面', tip: '猜特码的大/小/单/双等' }
    ]
  },
  zm: {
    name: '正码',
    plays: [
      { id: 'zmrx', name: '正码任选', tip: '从1-49中任选1个号码，开奖号中包含即中奖' },
      { id: 'zm1t', name: '正1特', tip: '猜正码第一位' },
      { id: 'zm1lm', name: '正1两面', tip: '猜正码第一位两面' },
      { id: 'zm2t', name: '正2特', tip: '猜正码第二位' },
      { id: 'zm2lm', name: '正2两面', tip: '猜正码第二位两面' },
      { id: 'zm3t', name: '正3特', tip: '猜正码第三位' },
      { id: 'zm3lm', name: '正3两面', tip: '猜正码第三位两面' },
      { id: 'zm4t', name: '正4特', tip: '猜正码第四位' },
      { id: 'zm4lm', name: '正4两面', tip: '猜正码第四位两面' },
      { id: 'zm5t', name: '正5特', tip: '猜正码第五位' },
      { id: 'zm5lm', name: '正5两面', tip: '猜正码第五位两面' },
      { id: 'zm6t', name: '正6特', tip: '猜正码第六位' },
      { id: 'zm6lm', name: '正6两面', tip: '猜正码第六位两面' },
    ]
  },
  lm: {
    name: '连码',
    plays: [
      { id: 'lm4qz', name: '四全中', tip: '选4个号码，全中即中奖' },
      { id: 'lm3qz', name: '三全中', tip: '选3个号码，全中即中奖' },
      { id: 'lm3z2', name: '三中二', tip: '选3个号码，中2或3个即中奖' },
      { id: 'lm2qz', name: '二全中', tip: '选2个号码，全中即中奖' },
      { id: 'lm2zt', name: '二中特', tip: '选2个号码，中特码即中奖' },
      { id: 'lmtc', name: '特串', tip: '选2个号码，1个特码+1个正码' }
    ]
  },
  bb: {
    name: '半波',
    plays: [
      { id: 'tmbb', name: '特码半波', tip: '猜特码色波和大小单双' }
    ]
  },
  sx: {
    name: '生肖',
    plays: [
      { id: 'sxtx', name: '特肖', tip: '猜特码属于哪个生肖' },
      { id: 'sx1x', name: '一肖', tip: '开奖号中包含该生肖即中奖' },
      { id: 'sxzx', name: '正肖', tip: '猜正码属于哪个生肖' },
      { id: 'sxhx', name: '合肖', tip: '猜特码属于选定的生肖组' },
      { id: 'sxzxiao', name: '总肖', tip: '猜7个开奖号码中不同生肖的总数' },
      { id: 'sx2xl', name: '二肖连', tip: '选择2个生肖' },
      { id: 'sx3xl', name: '三肖连', tip: '选择3个生肖' },
      { id: 'sx4xl', name: '四肖连', tip: '选择4个生肖' }
    ]
  },
  ws: {
    name: '尾数',
    plays: [
      { id: 'wstw', name: '特码头尾', tip: '猜特码的头数或尾数' },
      { id: 'ws2wl', name: '二尾连', tip: '选择2个尾数' },
      { id: 'ws3wl', name: '三尾连', tip: '选择3个尾数' },
      { id: 'ws4wl', name: '四尾连', tip: '选择4个尾数' }
    ]
  },
  sb: {
    name: '色波',
    plays: [
      { id: 'sbsebo', name: '三色波', tip: '猜特码的颜色' }
    ]
  },
  wx: {
    name: '五行',
    plays: [
      { id: 'wxwx', name: '五行', tip: '猜特码属于金木水火土哪一行' }
    ]
  },
  bz: {
    name: '不中',
    plays: [
      { id: 'bz5bz', name: '五不中', tip: '选5个号码，开奖号中都不包含即中奖' },
      { id: 'bz6bz', name: '六不中', tip: '选6个号码，开奖号中都不包含即中奖' },
      { id: 'bz7bz', name: '七不中', tip: '选7个号码，开奖号中都不包含即中奖' },
      { id: 'bz8bz', name: '八不中', tip: '选8个号码，开奖号中都不包含即中奖' },
      { id: 'bz9bz', name: '九不中', tip: '选9个号码，开奖号中都不包含即中奖' },
      { id: 'bz10bz', name: '十不中', tip: '选10个号码，开奖号中都不包含即中奖' }
    ]
  }
}

const activeGroup = ref('tm')
const currentPlayType = ref('tmzx')
const currentPlayObj = computed(() => {
  const group = playGroups[activeGroup.value]
  return group?.plays.find(p => p.id === currentPlayType.value) || {}
})
const currentPlayName = computed(() => currentPlayObj.value.name)
const currentPlayTip = computed(() => currentPlayObj.value.tip)

const isNumberPlay = computed(() => {
  return ['tmzx', 'zmrx', 'zm1t', 'zm2t', 'zm3t', 'zm4t', 'zm5t', 'zm6t', 
          'lm4qz', 'lm3qz', 'lm3z2', 'lm2qz', 'lm2zt', 'lmtc',
          'bz5bz', 'bz6bz', 'bz7bz', 'bz8bz', 'bz9bz', 'bz10bz'].includes(currentPlayType.value)
})

const isLiangMianPlay = computed(() => {
  return currentPlayType.value === 'tmlm' || currentPlayType.value.includes('lm') && currentPlayType.value.startsWith('zm')
})

const isShengXiaoPlay = computed(() => currentPlayType.value.startsWith('sx') && currentPlayType.value !== 'sxzxiao')
const isWeiShuPlay = computed(() => currentPlayType.value.startsWith('ws'))
const isSeBoPlay = computed(() => currentPlayType.value === 'sbsebo')
const isWuXingPlay = computed(() => currentPlayType.value === 'wxwx')
const isZongXiaoPlay = computed(() => currentPlayType.value === 'sxzxiao')

const numbers = Array.from({ length: 49 }, (_, i) => i + 1)
const liangMianOptions = computed(() => {
  if (currentPlayType.value === 'tmlm') return getLiangMianOptions('tmlm')
  return getLiangMianOptions('zm') // for zm1lm, zm2lm...
})
const banBoOptions = getBanBoOptions()
const shengXiaoOptions = computed(() => getShengXiaoOptions(currentPlayType.value))
const weiShuOptions = computed(() => getWeiShuOptions(currentPlayType.value))
const seBoOptions = getSeBoOptions()
const wuXingOptions = getWuXingOptions()
const zongXiaoOptions = getZongXiaoOptions()

const selectedData = ref([])
const unitPrice = ref(1)
const multiplier = ref(1)

const getCombinations = (arr, k) => {
  if (k > arr.length) return 0
  if (k === arr.length) return 1
  
  let result = 1
  for (let i = 1; i <= k; i++) {
    result = result * (arr.length - i + 1) / i
  }
  return result
}

const betCount = computed(() => {
  const count = selectedData.value.length
  const playId = currentPlayType.value
  

  if (playId === 'lm3qz' || playId === 'lm3z2') return getCombinations(selectedData.value, 3)
  if (playId === 'lm2qz' || playId === 'lm2zt' || playId === 'lmtc') return getCombinations(selectedData.value, 2)
  if (playId === 'lm4qz') return getCombinations(selectedData.value, 4)
  

  if (playId === 'sx2xl') return getCombinations(selectedData.value, 2)
  if (playId === 'sx3xl') return getCombinations(selectedData.value, 3)
  if (playId === 'sx4xl') return getCombinations(selectedData.value, 4)
  

  if (playId === 'ws2wl') return getCombinations(selectedData.value, 2)
  if (playId === 'ws3wl') return getCombinations(selectedData.value, 3)
  if (playId === 'ws4wl') return getCombinations(selectedData.value, 4)
  

  if (playId.startsWith('bz')) {
    const min = parseInt(playId.replace('bz', '').replace('bz', '')) // bz5bz -> 5
    return getCombinations(selectedData.value, min)
  }
  

  if (playId === 'sxhx') {
    return (count >= 2 && count <= 11) ? 1 : 0
  }
  

  return count
})

const selectPlay = (play) => {
  currentPlayType.value = play.id
  showPlayMenu.value = false
  selectedData.value = [] // Reset selection
}

const toggleNumber = (num) => {
  const idx = selectedData.value.indexOf(num)
  if (idx > -1) selectedData.value.splice(idx, 1)
  else selectedData.value.push(num)
}

const toggleSelection = (val) => {
  const idx = selectedData.value.indexOf(val)
  if (idx > -1) selectedData.value.splice(idx, 1)
  else selectedData.value.push(val)
}

const clearSelection = () => {
  selectedData.value = []
}

const buildBetData = () => {
  const playType = currentPlayType.value
  const selections = selectedData.value
  

  if (['tmzx', 'zmrx', 'zm1t', 'zm2t', 'zm3t', 'zm4t', 'zm5t', 'zm6t',
       'lm4qz', 'lm3qz', 'lm3z2', 'lm2qz', 'lm2zt', 'lmtc',
       'bz5bz', 'bz6bz', 'bz7bz', 'bz8bz', 'bz9bz', 'bz10bz'].includes(playType)) {

    return [{
      playId: playType,
      tzcode: selections.map(n => String(n).padStart(2, '0')).join(','),
      betCount: betCount.value
    }]
  }
  

  if (['sx2xl', 'sx3xl', 'sx4xl', 'ws2wl', 'ws3wl', 'ws4wl'].includes(playType)) {
    return [{
      playId: playType,
      tzcode: selections.join(','),
      betCount: betCount.value
    }]
  }
  

  if (playType === 'sxhx') {
    const count = selections.length
    if (count < 2 || count > 11) {
      return []  // 合肖需要2-11个生肖
    }

    const sxMap = { '鼠': 'shu', '牛': 'niu', '虎': 'hu', '兔': 'tu', '龙': 'long', '蛇': 'she',
                   '马': 'ma', '羊': 'yang', '猴': 'hou', '鸡': 'ji', '狗': 'gou', '猪': 'zhu' }
    const sxPinyin = selections.map(s => sxMap[s] || s).join(',')
    return [{
      playId: `sxhx${count}`,
      tzcode: sxPinyin,
      betCount: 1
    }]
  }
  

  return selections.map(sel => {
    let playId = ''
    

    if (playType === 'tmlm') {
      const map = { '大': 'tmlmda', '小': 'tmlmxiao', '单': 'tmlmdan', '双': 'tmlmshuang',
                   '大单': 'tmlmdadan', '大双': 'tmlmdashuang', '小单': 'tmlmxiaodan', '小双': 'tmlmxiaoshuang',
                   '合大': 'tmlmheda', '合小': 'tmlmhexiao', '合单': 'tmlmhedan', '合双': 'tmlmheshuang',
                   '尾大': 'tmlmweida', '尾小': 'tmlmweixiao', '家禽': 'tmlmjiaqin', '野兽': 'tmlmyeshou',
                   '红波': 'tmlmhongbo', '绿波': 'tmlmlvbo', '蓝波': 'tmlmlanbo' }
      playId = map[sel] || playType
    }

    else if (playType.match(/^zm[1-6]lm$/)) {
      const prefix = playType.replace('lm', 'lm')
      const map = { '大': 'da', '小': 'xiao', '单': 'dan', '双': 'shuang',
                   '大单': 'dadan', '大双': 'dashuang', '小单': 'xiaodan', '小双': 'xiaoshuang',
                   '合大': 'heda', '合小': 'hexiao', '合单': 'hedan', '合双': 'heshuang',
                   '尾大': 'weida', '尾小': 'weixiao', '家禽': 'jiaqin', '野兽': 'yeshou',
                   '红波': 'hongbo', '绿波': 'lvbo', '蓝波': 'lanbo' }
      playId = prefix + (map[sel] || '')
    }

    else if (['sxtx', 'sx1x', 'sxzx'].includes(playType)) {
      const map = { '鼠': 'shu', '牛': 'niu', '虎': 'hu', '兔': 'tu', '龙': 'long', '蛇': 'she',
                   '马': 'ma', '羊': 'yang', '猴': 'hou', '鸡': 'ji', '狗': 'gou', '猪': 'zhu' }
      playId = playType + (map[sel] || '')
    }

    else if (playType === 'tmbb') {
      const map = { '红大': 'hongda', '红小': 'hongxiao', '红单': 'hongdan', '红双': 'hongshuang',
                   '红合单': 'honghedan', '红合双': 'hongheshuang',
                   '绿大': 'lvda', '绿小': 'lvxiao', '绿单': 'lvdan', '绿双': 'lvshuang',
                   '绿合单': 'lvhedan', '绿合双': 'lvheshuang',
                   '蓝大': 'landa', '蓝小': 'lanxiao', '蓝单': 'landan', '蓝双': 'lanshuang',
                   '蓝合单': 'lanhedan', '蓝合双': 'lanheshuang' }
      playId = map[sel] || playType
    }

    else if (playType === 'sbsebo') {
      const map = { '红波': 'hongbo', '绿波': 'lvbo', '蓝波': 'lanbo' }
      playId = map[sel] || playType
    }

    else if (playType === 'wxwx') {
      const map = { '金': 'jin', '木': 'mu', '水': 'shui', '火': 'huo', '土': 'tu' }
      playId = map[sel] || playType
    }

    else if (playType === 'sxzxiao') {
      const map = { '2肖': 'zx2xiao', '3肖': 'zx3xiao', '4肖': 'zx4xiao', '5肖': 'zx5xiao',
                   '6肖': 'zx6xiao', '7肖': 'zx7xiao', '总肖单': 'zxdan', '总肖双': 'zxshuang' }
      playId = map[sel] || playType
    }

    else if (playType === 'wstw') {
      const map = { '0头': 'lingtou', '1头': 'yitou', '2头': 'ertou', '3头': 'santou', '4头': 'sitou',
                   '0尾': 'lingwei', '1尾': 'yiwei', '2尾': 'erwei', '3尾': 'sanwei', '4尾': 'siwei',
                   '5尾': 'wuwei', '6尾': 'liuwei', '7尾': 'qiwei', '8尾': 'bawei', '9尾': 'jiuwei' }
      playId = map[sel] || playType
    }
    else {
      playId = playType
    }
    
    return {
      playId: playId,
      tzcode: sel,
      betCount: 1
    }
  })
}

const onSubmit = async (data) => {
  if (betCount.value === 0) return showToast('请至少选择一注')
  if (isSealed.value) return showToast('当前已封盘，请等待下期')
  
  const loading = showLoadingToast({ message: '投注中...', forbidClick: true })
  

  if (remainMs.value <= closeSeconds.value * 1000) {
    closeToast()
    return showToast('当前已封盘，请等待下期')
  }
  
  const betItems = buildBetData()
  const totalAmount = betCount.value * unitPrice.value * multiplier.value
  
  
  try {

    let successCount = 0
    let failMsg = ''
    
    for (const item of betItems) {
      const betData = {
        lotteryCode: lotteryCode.value,
        expect: issueInfo.currFullExpect,
        playId: item.playId,
        selections: [item.tzcode],
        unitPrice: unitPrice.value,
        multiplier: multiplier.value,
        totalAmount: unitPrice.value * multiplier.value * item.betCount,
        betCount: item.betCount
      }
      
      try {
        const res = await lhcApi.submitBet(betData)
        if (res.code === 0) {
          successCount++
        } else {
          failMsg = res.message || '投注失败'
        }
      } catch (err) {
        failMsg = err.response?.data?.message || '投注失败'
      }
    }
    
    closeToast()
    
    if (successCount === betItems.length) {
      showToast({ type: 'success', message: '投注成功' })
      clearSelection()
      fetchUserBalance()
    } else if (successCount > 0) {
      showToast({ type: 'success', message: `部分成功(${successCount}/${betItems.length})` })
      clearSelection()
      fetchUserBalance()
    } else {
      showToast({ type: 'fail', message: failMsg || '投注失败' })
    }
  } catch (err) {
    closeToast()
    console.error('投注失败:', err)
    showToast({ type: 'fail', message: err.response?.data?.message || '投注失败' })
  }
}

const fetchUserBalance = async () => {
  try {
    const res = await lhcApi.getUserBalance()
    if (res.code === 0 && res.data) {
      userBalance.value = parseFloat(res.data.balance || 0).toFixed(2)
    }
  } catch (err) {
    console.error('获取余额失败:', err)
  }
}

const fetchLotteryInfo = async () => {
  try {

    const [infoRes, lastRes] = await Promise.all([
      lhcApi.getInfo(lotteryCode.value),
      lhcApi.getLastResult(lotteryCode.value).catch(() => null)
    ])
    
    if (infoRes.code === 0 && infoRes.data) {
      const data = infoRes.data
      issueInfo.currFullExpect = data.currFullExpect || '---'
      issueInfo.lastFullExpect = data.lastFullExpect || '---'
      remainMs.value = Math.max(0, (data.remainTime || 0) * 1000)
      closeSeconds.value = data.closeSeconds || 10
    }
    

    if (lastRes && lastRes.code === 0 && lastRes.data) {
      const openCode = lastRes.data.openCode
      if (openCode && Array.isArray(openCode)) {
        lastOpenCode.value = openCode.map(n => String(n).padStart(2, '0'))
      }
    }
  } catch (err) {
    console.error('获取彩种信息失败:', err)
  }
}

const fetchHistory = async () => {
  try {
    const res = await lhcApi.getHistory(lotteryCode.value, { limit: 20 })
    if (res.code === 0 && res.data) {
      historyList.value = res.data.map(item => ({
        expect: item.expect,
        codes: parseOpenCode(item.opencode),
        time: item.opentime_format
      }))
    }
  } catch (err) {
    console.error('获取历史开奖失败:', err)
  }
}

const parseOpenCode = (code) => {
  if (!code) return ['--', '--', '--', '--', '--', '--', '--']
  if (Array.isArray(code)) return code.map(n => String(n).padStart(2, '0'))
  if (typeof code === 'string') {
    return code.split(',').map(n => String(n).padStart(2, '0'))
  }
  return ['--', '--', '--', '--', '--', '--', '--']
}

const startCountdown = () => {
  if (countdownTimer) clearInterval(countdownTimer)
  
  countdownTimer = setInterval(() => {
    if (remainMs.value > 0) {
      remainMs.value -= 1000
    } else {

      isDrawing.value = true

      setTimeout(() => {
        fetchLotteryInfo()
        fetchHistory()
        isDrawing.value = false
      }, 5000)
    }
  }, 1000)
}

const setupWebSocket = () => {

  if (lotteryWS && lotteryWS.subscribe) {
    lotteryWS.subscribe(lotteryCode.value)
    

    const onCountdown = (data) => {
      if (data.lotteryCode !== lotteryCode.value) return

      if (data.currentIssue) {
        issueInfo.currFullExpect = data.currentIssue
      }

      remainMs.value = Math.max(0, (data.countdown || 0) * 1000)

      isDrawing.value = data.status === 0 || data.status === 2

      if (data.lastIssue) {
        issueInfo.lastFullExpect = data.lastIssue
      }
      if (data.lastOpenCode) {
        lastOpenCode.value = parseOpenCode(data.lastOpenCode)
      }
    }
    

    const onDrawResult = (data) => {
      if (data.lotteryCode !== lotteryCode.value) return
      issueInfo.lastFullExpect = data.issue
      lastOpenCode.value = parseOpenCode(data.openCode)
      isDrawing.value = false
      fetchHistory()
    }
    
    lotteryWS.on('countdown', onCountdown)
    lotteryWS.on('draw_result', onDrawResult)  // 使用正确的事件名
    
    wsCleanups.push(() => {
      lotteryWS.off('countdown', onCountdown)
      lotteryWS.off('draw_result', onDrawResult)
      lotteryWS.unsubscribe(lotteryCode.value)
    })
  } else {
    console.warn('[LHC] WebSocket 不可用')
  }
}

const initLottery = async () => {
  isLoading.value = true
  
  try {
    await Promise.all([
      fetchLotteryInfo(),
      fetchHistory(),
      fetchUserBalance()
    ])
    
    startCountdown()
    setupWebSocket()
  } catch (err) {
    console.error('初始化失败:', err)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  initLottery()
})

onUnmounted(() => {

  if (countdownTimer) {
    clearInterval(countdownTimer)
    countdownTimer = null
  }
  

  wsCleanups.forEach(cleanup => cleanup())
  wsCleanups = []
})

</script>

<style lang="less" scoped>
.lhc-page {
  background: #f7f8fa;
  min-height: 100vh;
  padding-top: 46px; // Header height
  padding-bottom: 110px; // Footer height
}

.fixed-info {
  position: sticky;
  top: 46px;
  z-index: 90;
}

.scroll-area {
  padding: 12px;
}

.play-panel {
  background: #fff;
  border-radius: 12px;
  overflow: hidden;
  min-height: 300px;
  padding: 16px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.02);
  position: relative;
  
  &.is-sealed {
    pointer-events: none;
  }
}

.sealed-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.95);
  z-index: 100;
  display: flex;
  align-items: center;
  justify-content: center;

  .sealed-content {
    text-align: center;
    
    .lock-ring {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      background: linear-gradient(135deg, #ff6b6b, #e1251b);
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 16px;
      animation: pulse 2s infinite;
      
      .lock-icon {
        font-size: 28px;
        color: #fff;
      }
    }
    
    .text {
      display: block;
      font-size: 18px;
      font-weight: bold;
      color: #e1251b;
      margin-bottom: 8px;
    }
    
    .sub-text {
      font-size: 14px;
      color: #999;
      
      .count {
        color: #e1251b;
        font-weight: bold;
        font-size: 16px;
      }
    }
  }
}

@keyframes pulse {
  0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(225, 37, 27, 0.4); }
  70% { transform: scale(1.05); box-shadow: 0 0 0 10px rgba(225, 37, 27, 0); }
  100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(225, 37, 27, 0); }
}

.play-tips {
  padding: 10px 14px;
  background: #e6f7ff;
  color: #1890ff;
  font-size: 13px;
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 16px;
  border-radius: 6px;
  line-height: 1.4;
}

.panel-content {
  min-height: 200px;
  position: relative;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.play-menu {
  display: flex;
  flex-direction: column;
  height: 100%;

  .menu-header {
    text-align: center;
    padding: 16px;
    font-weight: bold;
    border-bottom: 1px solid #f5f5f5;
    display: flex;
    justify-content: space-between;
    align-items: center;
    
    span {
      flex: 1;
      font-size: 16px;
    }
    
    .close-icon {
      color: #999;
      font-size: 20px;
      padding: 4px;
      cursor: pointer;
    }
  }

  .menu-body {
    flex: 1;
    display: flex;
    overflow: hidden;
  }

  .menu-sidebar {
    width: 110px;
    background: #f7f8fa;
    overflow-y: auto;

    .menu-item {
      padding: 16px 12px;
      text-align: center;
      font-size: 14px;
      color: #666;
      cursor: pointer;
      transition: all 0.2s;
      
      &.active {
        background: #fff;
        color: #e1251b;
        font-weight: bold;
        position: relative;
        
        &::before {
            content: '';
            position: absolute;
            left: 0; top: 50%; transform: translateY(-50%);
            width: 4px; height: 18px;
            background: #e1251b;
            border-top-right-radius: 4px;
            border-bottom-right-radius: 4px;
        }
      }
    }
  }

  .menu-content {
    flex: 1;
    background: #fff;
    padding: 16px;
    overflow-y: auto;

    .sub-play-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 12px;
    }

    .sub-play-item {
      border: 1px solid #f0f0f0;
      border-radius: 6px;
      padding: 10px 8px;
      text-align: center;
      font-size: 13px;
      color: #333;
      cursor: pointer;
      transition: all 0.2s;
      
      &:active {
        background-color: #f5f5f5;
      }
      
      &.active {
        border-color: #e1251b;
        color: #e1251b;
        background: #fff1f0;
        font-weight: 500;
        box-shadow: 0 2px 4px rgba(225, 37, 27, 0.05);
      }
    }
  }
}

.history-popup {
    padding: 0;
    height: 100%;
    display: flex;
    flex-direction: column;
    
    .history-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px;
        border-bottom: 1px solid #f5f5f5;
        
        .history-title {
            font-weight: bold;
            font-size: 16px;
        }
        
        .close-icon {
            color: #999;
            font-size: 20px;
            cursor: pointer;
        }
    }
    
    .history-list {
        flex: 1;
        overflow-y: auto;
        padding: 0 12px;
    }
    
    .his-item {
        display: flex;
        flex-direction: column;
        padding: 12px 0;
        border-bottom: 1px solid #f5f5f5;
        
        &:last-child {
          border-bottom: none;
        }
    }
    
    .his-info {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
    }

    .his-expect {
      font-size: 14px;
      color: #333;
      font-weight: 500;
    }
    
    .his-time {
      font-size: 12px;
      color: #999;
    }
    
    .his-balls-wrapper {
      overflow-x: auto;
    }
    
    .his-balls {
      display: flex;
      align-items: flex-start;
      gap: 4px;
      flex-wrap: nowrap;
    }
    
    .ball-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      flex-shrink: 0;
      
      &.special {
        position: relative;
        
        &::before {
          content: '特';
          position: absolute;
          top: -4px;
          right: -4px;
          font-size: 9px;
          background: #fbbf24;
          color: #fff;
          padding: 1px 3px;
          border-radius: 4px;
          line-height: 1;
        }
      }
    }
    
    .plus {
      display: flex;
      align-items: center;
      height: 24px;
      color: #999;
      font-size: 14px;
      margin: 0 2px;
    }
    
    .mini-ball {
        display: inline-block;
        width: 26px;
        height: 26px;
        line-height: 26px;
        text-align: center;
        background: #ccc;
        color: #fff;
        border-radius: 50%;
        font-size: 12px;
        font-weight: 500;
        box-shadow: 0 1px 3px rgba(0,0,0,0.15);
        
        &.ssc-fang-hong { background: linear-gradient(135deg, #ff6b6b, #e1251b); }
        &.ssc-fang-lan { background: linear-gradient(135deg, #4dabf7, #1971c2); }
        &.ssc-fang-lv { background: linear-gradient(135deg, #69db7c, #2f9e44); }
    }
    
    .mini-sx {
        font-size: 10px;
        color: #999;
        margin-top: 2px;
        height: 14px;
        line-height: 14px;
    }
}
</style>
