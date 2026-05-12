<template>
  <van-popup 
    :show="show" 
    @update:show="$emit('update:show', $event)" 
    position="bottom" 
    round 
    class="hotcold-popup"
    :style="{ height: '60%' }"
    :overlay-style="{ background: 'rgba(0,0,0,0.3)' }"
  >
      <div class="hc-container">
        
        <div class="hc-header">
            <div class="hc-header-left" @click="$emit('update:show', false)">
                <van-icon name="arrow-down" />
                <span>收起</span>
            </div>
            <div class="hc-header-center">
                <div class="toggle-btn" :class="{ active: showHot }" @click="showHot = !showHot">冷热</div>
                <div class="toggle-btn" :class="{ active: showMiss }" @click="showMiss = !showMiss">遗漏</div>
            </div>
            <div class="hc-header-right" @click="showHelpPopup = true">
                <van-icon name="question" color="#999" />
                <span>说明</span>
            </div>
        </div>
        
        
        <van-popup v-model:show="showHelpPopup" position="center" round class="help-popup" :overlay-style="{ background: 'rgba(0,0,0,0.3)' }">
          <div class="popup-indicator"></div>
          <div class="help-content">
            <div class="help-section">
              <div class="help-title">冷热说明</div>
              <div class="help-text">
                最近{{ statsPeriod }}期开奖结果中,出现每个选项的次数每个选项右上角数字为冷热统计
              </div>
              <div class="help-link">
                统计期数可以去房间设置
                <span class="link-text" @click="goSettings">前去修改</span>
              </div>
            </div>
            <div class="help-section">
              <div class="help-title">遗漏说明</div>
              <div class="help-text">
                距离上次出现后，统计有多少期再没有出现每个选项右下角数字为遗漏统计
              </div>
            </div>
            <div class="help-footer" @click="showHelpPopup = false">我知道</div>
          </div>
        </van-popup>
        
        
        <div class="hc-scroll-content">
            
            
            <div class="hc-section">
                <div class="sec-title">热门玩法</div>
                <div class="hc-grid-4">
                    <div class="hc-card" v-for="item in hotPlayItems" :key="item.key">
                        <div class="card-label">{{ item.label }}</div>
                        
                        <div class="badge miss-badge" v-if="showMiss">
                            {{ stats[item.key]?.miss || 0 }}
                        </div>
                        
                        <div class="badge hot-badge" v-if="showHot" :class="getHotColor(stats[item.key]?.count)">
                            {{ stats[item.key]?.count || 0 }}
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="hc-section">
                <div class="sec-title">数字玩法</div>
                <div class="hc-grid-5">
                    <div class="hc-card" v-for="n in 28" :key="n-1">
                        <div class="card-label num">{{ n - 1 }}</div>
                        
                        <div class="badge miss-badge" v-if="showMiss">
                            {{ stats.numbers[n-1]?.miss || 0 }}
                        </div>
                        
                        <div class="badge hot-badge" v-if="showHot" :class="getHotColor(stats.numbers[n-1]?.count)">
                            {{ stats.numbers[n-1]?.count || 0 }}
                        </div>
                    </div>
                </div>
            </div>

        </div>
      </div>
  </van-popup>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { xy28Api } from '@/api'

const router = useRouter()

const props = defineProps({
  show: Boolean,
  lotteryCode: {
    type: String,
    default: 'yfxy28'
  }
})

const emit = defineEmits(['update:show'])

const showHot = ref(true)
const showMiss = ref(true)
const loading = ref(false)
const showHelpPopup = ref(false)

const statsPeriod = ref(50)

function goSettings() {
  showHelpPopup.value = false
  emit('update:show', false)
  router.push(`/lottery/room-settings/${props.lotteryCode}`)
}

const hotPlayItems = [
    { key: 'big', label: '大' }, { key: 'small', label: '小' },
    { key: 'odd', label: '单' }, { key: 'even', label: '双' },
    { key: 'big_odd', label: '大单' }, { key: 'big_even', label: '大双' }, 
    { key: 'small_odd', label: '小单' }, { key: 'small_even', label: '小双' },
    { key: 'jida', label: '极大' }, { key: 'jixiao', label: '极小' },
    { key: 'long', label: '龙' }, { key: 'hu', label: '虎' },
    { key: 'bao', label: '豹' }, { key: 'duizi', label: '对子' },
    { key: 'shunzi', label: '顺子' }, { key: 'baozi', label: '豹子' }
]

const stats = ref({
  numbers: {}
})

function initStats() {
  const res = { numbers: {} }
  hotPlayItems.forEach(i => res[i.key] = { count: 0, miss: 0 })
  for (let i = 0; i <= 27; i++) res.numbers[i] = { count: 0, miss: 0 }
  stats.value = res
}

async function loadHotCold() {
  if (!props.lotteryCode) return
  loading.value = true
  try {

    const settingsRes = await xy28Api.getUserSettings()
    if (settingsRes.code === 0 && settingsRes.data) {
      statsPeriod.value = settingsRes.data.hot_cold_period || 50
    }
    
    const res = await xy28Api.getHotCold(props.lotteryCode, statsPeriod.value)
    if (res.code === 0 && res.data) {

      const apiStats = res.data.stats || {}
      const apiNumbers = res.data.numbers || {}
      

      hotPlayItems.forEach(item => {
        if (apiStats[item.key]) {
          stats.value[item.key] = apiStats[item.key]
        }
      })
      

      for (let i = 0; i <= 27; i++) {
        if (apiNumbers[i]) {
          stats.value.numbers[i] = apiNumbers[i]
        }
      }
    }
  } catch (e) {
    console.error('[HotColdPopup] 加载冷热遗漏失败:', e)
  } finally {
    loading.value = false
  }
}

watch(() => props.show, (show) => {
  if (show) {
    loadHotCold()
  }
})

initStats()

function getHotColor(count) {
    if (!count) return 'cold'
    return count >= 50 ? 'hot' : count >= 20 ? 'warm' : 'cold'
}

</script>

<style lang="less" scoped>
.hotcold-popup {
    background: #f7f8fa; 
}

.hc-container {
    display: flex;
    flex-direction: column;
    height: 100%;
}

.hc-header {
    flex-shrink: 0;
    height: 48px;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 16px;
    border-bottom: 1px solid #eee;
    
    .hc-header-left {
        display: flex;
        align-items: center;
        color: #5691fe;
        font-size: 14px;
        gap: 4px;
    }
    
    .hc-header-right {
        display: flex;
        align-items: center;
        color: #999;
        font-size: 12px;
        gap: 4px;
    }
    
    .hc-header-center {
        display: flex;
        gap: 12px;
        
        .toggle-btn {
            width: 56px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            font-size: 13px;
            

            background: #fff;
            border: 1px solid #5691fe;
            color: #5691fe;
            

            &.active {
                background: #5691fe;
                color: #fff;
            }
        }
    }
}

.hc-scroll-content {
    flex: 1;
    overflow-y: auto;
    padding: 16px 12px;
    padding-bottom: calc(60px + env(safe-area-inset-bottom)); 
    min-height: 0; 
    -webkit-overflow-scrolling: touch;
    
    .hc-section {
        margin-bottom: 20px;
        
        .sec-title {
            font-size: 15px;
            font-weight: 600;
            color: #333;
            margin-bottom: 12px;
        }
    }
}

.hc-card {
    background: #fff;
    border: 1px solid #ebedf0;
    border-radius: 4px;
    height: 60px;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    
    .card-label {
        font-size: 14px;
        color: #333;
        font-weight: 500;
        
        &.num { font-size: 16px; font-weight: 600; }
    }
    
    .badge {
        position: absolute;
        font-size: 10px;
        padding: 1px 4px;
        border-radius: 2px;
        line-height: 1.2;
    }
    
    .miss-badge {
        bottom: 4px;
        left: 4px;
        background: #999db2;
        color: #fff;
    }
    
    .hot-badge {
        bottom: 4px;
        right: 4px;
        color: #fff;
        
        &.cold { background: #3498db; }
        &.warm { background: #ffa502; }
        &.hot { background: #ff4757; }
    }
}

.hc-grid-4 {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    grid-gap: 8px;
}
.hc-grid-5 {
    display: grid;
    grid-template-columns: repeat(5, 1fr); 
    grid-gap: 8px;
}

.help-popup {
  width: 75%;
  max-width: 300px;
  overflow: hidden;
  
  .popup-indicator {
    width: 140px;
    height: 6px;
    background: linear-gradient(90deg, #01d1ff, #0aabff);
    border-radius: 0 0 4px 4px;
    margin: 0 auto;
  }
  
  .help-content {
    padding: 15px 16px 0;
    
    .help-section {
      margin-bottom: 16px;
      
      .help-title {
        font-size: 15px;
        font-weight: 600;
        color: #333;
        margin-bottom: 8px;
      }
      
      .help-text {
        font-size: 13px;
        color: #666;
        line-height: 1.6;
      }
      
      .help-link {
        font-size: 13px;
        color: #666;
        margin-top: 8px;
        
        .link-text {
          color: #5691fe;
          cursor: pointer;
        }
      }
    }
    
    .help-footer {
      text-align: center;
      padding: 12px 0 16px;
      font-size: 15px;
      color: #5691fe;
      border-top: 1px solid #eee;
      margin-top: 8px;
      cursor: pointer;
    }
  }
}
</style>
