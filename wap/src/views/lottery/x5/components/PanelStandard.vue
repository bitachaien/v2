<template>
  <div class="panel-standard">
    
    <div class="sticky-header">
      <div class="play-select-bar" @click="showPlayPicker = true">
        <div class="current-play">
           <span class="label">当前玩法</span>
           <div class="value-wrap">
             <span class="value">{{ currentPlayTypeName }}</span>
             <div class="dropdown-badge">
               <span class="text">切换</span>
               <van-icon name="arrow-down" size="12" />
             </div>
           </div>
        </div>
      </div>
      
      
      <div class="tips-bar" v-if="playTips">
        <van-icon name="info-o" />
        {{ playTips }}
      </div>
    </div>

    
    <div class="bet-area">
      <component
        v-if="currentComponent"
        :is="currentComponent"
        ref="betRef"
        :playType="selectedSubType"
        v-bind="componentProps"
        @update:betCount="onBetCount"
        @update:selectedData="onSelectedData"
      />
      <div v-else class="error-placeholder">
        <p>加载组件失败，请刷新重试</p>
        <p style="font-size:12px;color:#999;">playType: {{ selectedSubType }}</p>
      </div>
    </div>

    
    <van-action-sheet v-model:show="showPlayPicker" title="选择玩法" class="play-action-sheet">
      <div class="play-picker">
        <div class="play-tabs">
           <div 
             v-for="(cat, key) in menuSchema" 
             :key="key" 
             class="play-tab-item"
             :class="{ active: currentTab === key }"
             @click="currentTab = key"
           >
             {{ cat.title }}
           </div>
        </div>
        <div class="play-content">
           <div class="menu-section" v-for="section in currentSection.sections" :key="section.title">
              <div class="section-title">{{ section.title }}</div>
              <div class="chips">
                <a 
                  class="chip" 
                  :class="{ active: item.code === selectedSubType }" 
                  v-for="item in section.items" 
                  :key="item.code" 
                  @click="selectPlay(item.code, item.label)"
                >
                  {{ item.label }}
                </a>
              </div>
           </div>
        </div>
      </div>
    </van-action-sheet>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { Icon as VanIcon, ActionSheet as VanActionSheet } from 'vant'

import X5Renxuan from './X5Renxuan.vue'
import X5Zhixuan from './X5Zhixuan.vue'
import X5Zuxuan from './X5Zuxuan.vue'
import X5Dantuo from './X5Dantuo.vue'
import X5Hezhi from './X5Hezhi.vue'
import X5Input from './X5Input.vue'
import X5Fun from './X5Fun.vue'

const router = useRouter()
const route = useRoute()
const emit = defineEmits(['update:betCount', 'update:selectedData', 'clear'])

const selectedSubType = ref('rx5')
const currentPlayTypeName = ref('任选五复式')
const showPlayPicker = ref(false)
const betRef = ref(null)
const currentTab = ref('renxuan')

const clear = () => {
    if (betRef.value?.clear) betRef.value.clear()
}
defineExpose({ clear })

const menuSchema = ref({
  'fun': {
    title: '趣味',
    sections: [
      { title: '趣味玩法', items: [
        { code: 'niuniu', label: '牛牛' },
        { code: 'dds', label: '定单双' },
        { code: 'czw', label: '猜中位' },
        { code: 'bbc', label: '猜必不出' }
      ]},
      { title: '龙虎斗', items: [
        { code: 'lh_touwei', label: '头尾龙虎' }, // 1 vs 5
        { code: 'lh_q2', label: '前二龙虎' }, // 1 vs 2
        { code: 'lh_h2', label: '后二龙虎' }  // 4 vs 5
      ]}
    ]
  },
  'hezhi': {
    title: '和值',
    sections: [
      { title: '和值', items: [
        { code: 'q2_zhix_hz', label: '前二直选和值' },
        { code: 'q2_zux_hz', label: '前二组选和值' },
        { code: 'q3_zhix_hz', label: '前三直选和值' },
        { code: 'q3_zux_hz', label: '前三组选和值' }
      ]},
      { title: '和尾', items: [
        { code: 'q2_zhix_hw', label: '前二直选和尾' },
        { code: 'q2_zux_hw', label: '前二组选和尾' },
        { code: 'q3_zhix_hw', label: '前三直选和尾' },
        { code: 'q3_zux_hw', label: '前三组选和尾' }
      ]}
    ]
  },
  'renxuan': {
    title: '任选',
    sections: [
      { title: '任选复式', items: [
        { code: 'rx1', label: '一中一复式' },
        { code: 'rx2', label: '二中二复式' },
        { code: 'rx3', label: '三中三复式' },
        { code: 'rx4', label: '四中四复式' },
        { code: 'rx5', label: '五中五复式' },
        { code: 'rx6', label: '六中五复式' },
        { code: 'rx7', label: '七中五复式' },
        { code: 'rx8', label: '八中五复式' }
      ]},
      { title: '任选单式', items: [
        { code: 'rx1_ds', label: '一中一单式' },
        { code: 'rx2_ds', label: '二中二单式' },
        { code: 'rx3_ds', label: '三中三单式' },
        { code: 'rx4_ds', label: '四中四单式' },
        { code: 'rx5_ds', label: '五中五单式' },
        { code: 'rx6_ds', label: '六中五单式' },
        { code: 'rx7_ds', label: '七中五单式' },
        { code: 'rx8_ds', label: '八中五单式' }
      ]},
      { title: '任选胆拖', items: [
        { code: 'rx2_dt', label: '二中二胆拖' },
        { code: 'rx3_dt', label: '三中三胆拖' },
        { code: 'rx4_dt', label: '四中四胆拖' },
        { code: 'rx5_dt', label: '五中五胆拖' },
        { code: 'rx6_dt', label: '六中五胆拖' },
        { code: 'rx7_dt', label: '七中五胆拖' },
        { code: 'rx8_dt', label: '八中五胆拖' }
      ]}
    ]
  },
  'sanma': {
    title: '三码',
    sections: [
      { title: '前三', items: [
        { code: 'q3_zhix', label: '前三直选复式' },
        { code: 'q3_zhix_ds', label: '前三直选单式' },
        { code: 'q3_zux', label: '前三组选复式' },
        { code: 'q3_zux_ds', label: '前三组选单式' },
        { code: 'q3_zux_dt', label: '前三组选胆拖' },
        { code: 'q3_zhix_hz', label: '前三直选和值' },
        { code: 'q3_zux_hz', label: '前三组选和值' }
      ]},
      { title: '中三', items: [
        { code: 'z3_zhix', label: '中三直选复式' },
        { code: 'z3_zhix_ds', label: '中三直选单式' },
        { code: 'z3_zux', label: '中三组选复式' },
        { code: 'z3_zux_ds', label: '中三组选单式' },
        { code: 'z3_zux_dt', label: '中三组选胆拖' },
        { code: 'z3_zhix_hz', label: '中三直选和值' },
        { code: 'z3_zux_hz', label: '中三组选和值' }
      ]},
      { title: '后三', items: [
        { code: 'h3_zhix', label: '后三直选复式' },
        { code: 'h3_zhix_ds', label: '后三直选单式' },
        { code: 'h3_zux', label: '后三组选复式' },
        { code: 'h3_zux_ds', label: '后三组选单式' },
        { code: 'h3_zux_dt', label: '后三组选胆拖' },
        { code: 'h3_zhix_hz', label: '后三直选和值' },
        { code: 'h3_zux_hz', label: '后三组选和值' }
      ]}
    ]
  },
  'erma': {
    title: '二码',
    sections: [
      { title: '前二', items: [
        { code: 'q2_zhix', label: '前二直选复式' },
        { code: 'q2_zhix_ds', label: '前二直选单式' },
        { code: 'q2_zux', label: '前二组选复式' },
        { code: 'q2_zux_ds', label: '前二组选单式' },
        { code: 'q2_zux_dt', label: '前二组选胆拖' },
        { code: 'q2_zhix_hz', label: '前二直选和值' },
        { code: 'q2_zux_hz', label: '前二组选和值' }
      ]},
      { title: '后二', items: [
        { code: 'h2_zhix', label: '后二直选复式' },
        { code: 'h2_zhix_ds', label: '后二直选单式' },
        { code: 'h2_zux', label: '后二组选复式' },
        { code: 'h2_zux_ds', label: '后二组选单式' },
        { code: 'h2_zux_dt', label: '后二组选胆拖' },
        { code: 'h2_zhix_hz', label: '后二直选和值' },
        { code: 'h2_zux_hz', label: '后二组选和值' }
      ]}
    ]
  },
  'dingwei': {
    title: '定位胆',
    sections: [
      { title: '定位胆', items: [{ code: 'dwd', label: '定位胆复式' }] }
    ]
  },
  'buding': {
    title: '不定位',
    sections: [
      { title: '不定位', items: [{ code: 'bdw', label: '前三不定位' }] }
    ]
  }
})

const currentSection = computed(() => menuSchema.value[currentTab.value] || {})

const playTips = computed(() => {
  const map = {
    niuniu: '选出牛几，开奖号5个数字之和尾数为牛数',
    dds: '猜开奖号码中单双数的个数',
    czw: '猜中开奖号码的中间位（第3位）',
    bbc: '选择1个必不出号码',
    lh_touwei: '第一球 vs 第五球 (龙>虎, 虎>龙)',
    lh_q2: '第一球 vs 第二球',
    lh_h2: '第四球 vs 第五球',
    
    rx1: '从01-11中至少选择1个号码，中第1位即中奖',
    rx2: '从01-11中至少选择2个号码，任意中2个即中奖',
    rx3: '从01-11中至少选择3个号码，任意中3个即中奖',
    rx4: '从01-11中至少选择4个号码，任意中4个即中奖',
    rx5: '从01-11中至少选择5个号码，任意中5个即中奖',
    rx6: '从01-11中至少选择6个号码，选号中包含5个开奖号即中奖',
    rx7: '从01-11中至少选择7个号码，选号中包含5个开奖号即中奖',
    rx8: '从01-11中至少选择8个号码，选号中包含5个开奖号即中奖',
    
    rx1_ds: '手动输入号码，至少输入1个',
    rx2_ds: '手动输入号码，至少输入2个',
    rx3_ds: '手动输入号码，至少输入3个',
    rx4_ds: '手动输入号码，至少输入4个',
    rx5_ds: '手动输入号码，至少输入5个',
    rx6_ds: '手动输入号码，至少输入6个',
    rx7_ds: '手动输入号码，至少输入7个',
    rx8_ds: '手动输入号码，至少输入8个',
    
    rx2_dt: '胆码可选1个，胆码+拖码≥2个',
    rx3_dt: '胆码可选1-2个，胆码+拖码≥3个',
    rx4_dt: '胆码可选1-3个，胆码+拖码≥4个',
    rx5_dt: '胆码可选1-4个，胆码+拖码≥5个',
    rx6_dt: '胆码可选1-5个，胆码+拖码≥6个',
    rx7_dt: '胆码可选1-6个，胆码+拖码≥7个',
    rx8_dt: '胆码可选1-7个，胆码+拖码≥8个',
    
    q2_zhix: '每位至少选1个号码，按位与开奖号前2位一致',
    q2_zhix_ds: '手动输入前2位号码，按位一致',
    q2_zux: '至少选2个号码，与开奖号前2位一致(顺序不限)',
    q2_zux_ds: '手动输入前2位组选号码',
    q2_zux_dt: '胆码可选1个，胆码+拖码≥2个',
    
    q2_zhix_hz: '选择和值，猜中开奖号前2位之和即中奖',
    q2_zux_hz: '选择和值，猜中开奖号前2位之和(组选)即中奖',
    q2_zhix_hw: '选择和尾，猜中开奖号前2位和值的个位',
    q2_zux_hw: '选择和尾，猜中开奖号前2位和值(组选)的个位',
    
    q3_zhix: '每位至少选1个号码，按位与开奖号前3位一致',
    q3_zhix_ds: '手动输入前3位号码，按位一致',
    q3_zux: '至少选3个号码，与开奖号前3位一致(顺序不限)',
    q3_zux_ds: '手动输入前3位组选号码',
    q3_zux_dt: '胆码可选1-2个，胆码+拖码≥3个',
    
    q3_zhix_hz: '选择和值，猜中开奖号前3位之和即中奖',
    q3_zux_hz: '选择和值，猜中开奖号前3位之和(组选)即中奖',
    q3_zhix_hw: '选择和尾，猜中开奖号前3位和值的个位',
    q3_zux_hw: '选择和尾，猜中开奖号前3位和值(组选)的个位',
    
    z3_zhix: '每位至少选1个号码，按位与开奖号中3位一致',
    z3_zhix_ds: '手动输入中3位号码，按位一致',
    z3_zux: '至少选3个号码，与开奖号中3位一致(顺序不限)',
    z3_zux_ds: '手动输入中3位组选号码',
    z3_zux_dt: '胆码可选1-2个，胆码+拖码≥3个',
    
    z3_zhix_hz: '选择和值，猜中开奖号中3位之和即中奖',
    z3_zux_hz: '选择和值，猜中开奖号中3位之和(组选)即中奖',
    
    h3_zhix: '每位至少选1个号码，按位与开奖号后3位一致',
    h3_zhix_ds: '手动输入后3位号码，按位一致',
    h3_zux: '至少选3个号码，与开奖号后3位一致(顺序不限)',
    h3_zux_ds: '手动输入后3位组选号码',
    h3_zux_dt: '胆码可选1-2个，胆码+拖码≥3个',
    
    h3_zhix_hz: '选择和值，猜中开奖号后3位之和即中奖',
    h3_zux_hz: '选择和值，猜中开奖号后3位之和(组选)即中奖',
    
    h2_zhix: '每位至少选1个号码，按位与开奖号后2位一致',
    h2_zhix_ds: '手动输入后2位号码，按位一致',
    h2_zux: '至少选2个号码，与开奖号后2位一致(顺序不限)',
    h2_zux_ds: '手动输入后2位组选号码',
    h2_zux_dt: '胆码可选1个，胆码+拖码≥2个',
    
    h2_zhix_hz: '选择和值，猜中开奖号后2位之和即中奖',
    h2_zux_hz: '选择和值，猜中开奖号后2位之和(组选)即中奖',
    
    dwd: '在万千百十个位选择号码，与开奖号码对应位置一致',
    bdw: '从01-11中至少选择1个号码，开奖号前3位中包含该号'
  }
  return map[selectedSubType.value] || ''
})

const componentProps = computed(() => {
  const cfgs = {
    rx1: { min: 1 },
    rx2: { min: 2 },
    rx3: { min: 3 },
    rx4: { min: 4 },
    rx5: { min: 5 },
    rx6: { min: 6 },
    rx7: { min: 7 },
    rx8: { min: 8 },
    
    rx1_ds: { len: 1 },
    rx2_ds: { len: 2, isZux: true }, // Renxuan single input treated as group
    rx3_ds: { len: 3, isZux: true },
    rx4_ds: { len: 4, isZux: true },
    rx5_ds: { len: 5, isZux: true },
    rx6_ds: { len: 6, isZux: true },
    rx7_ds: { len: 7, isZux: true },
    rx8_ds: { len: 8, isZux: true },
    
    rx2_dt: { minTotal: 2, maxDan: 1 },
    rx3_dt: { minTotal: 3, maxDan: 2 },
    rx4_dt: { minTotal: 4, maxDan: 3 },
    rx5_dt: { minTotal: 5, maxDan: 4 },
    rx6_dt: { minTotal: 6, maxDan: 5 },
    rx7_dt: { minTotal: 7, maxDan: 6 },
    rx8_dt: { minTotal: 8, maxDan: 7 },
    
    q2_zux: { min: 2, type: 'zux2' },
    q2_zux_dt: { minTotal: 2, maxDan: 1 },
    q2_zhix_ds: { len: 2, isZux: false },
    q2_zux_ds: { len: 2, isZux: true },
    q2_zhix_hz: { min: 3, max: 21 },
    q2_zux_hz: { min: 3, max: 21 },
    q2_zhix_hw: { min: 0, max: 9 },
    q2_zux_hw: { min: 0, max: 9 },
    
    h2_zux: { min: 2, type: 'zux2' },
    h2_zux_dt: { minTotal: 2, maxDan: 1 },
    h2_zhix_ds: { len: 2, isZux: false },
    h2_zux_ds: { len: 2, isZux: true },
    h2_zhix_hz: { min: 3, max: 21 },
    h2_zux_hz: { min: 3, max: 21 },
    
    q3_zux: { min: 3, type: 'zux3' },
    q3_zux_dt: { minTotal: 3, maxDan: 2 },
    q3_zhix_ds: { len: 3, isZux: false },
    q3_zux_ds: { len: 3, isZux: true },
    q3_zhix_hz: { min: 6, max: 30 },
    q3_zux_hz: { min: 6, max: 30 },
    q3_zhix_hw: { min: 0, max: 9 },
    q3_zux_hw: { min: 0, max: 9 },
    
    z3_zux: { min: 3, type: 'zux3' },
    z3_zux_dt: { minTotal: 3, maxDan: 2 },
    z3_zhix_ds: { len: 3, isZux: false },
    z3_zux_ds: { len: 3, isZux: true },
    z3_zhix_hz: { min: 6, max: 30 },
    z3_zux_hz: { min: 6, max: 30 },
    
    h3_zux: { min: 3, type: 'zux3' },
    h3_zux_dt: { minTotal: 3, maxDan: 2 },
    h3_zhix_ds: { len: 3, isZux: false },
    h3_zux_ds: { len: 3, isZux: true },
    h3_zhix_hz: { min: 6, max: 30 },
    h3_zux_hz: { min: 6, max: 30 },
    
    bdw: { min: 1, type: 'bdw' },
    q2_zhix: { rows: ['万位','千位'] },
    h2_zhix: { rows: ['十位','个位'] },
    
    q3_zhix: { rows: ['万位','千位','百位'] },
    z3_zhix: { rows: ['千位','百位','十位'] },
    h3_zhix: { rows: ['百位','十位','个位'] },
    
    dwd: { rows: ['万位','千位','百位','十位','个位'], isSum: true },
  }
  return cfgs[selectedSubType.value] || {}
})

const currentComponent = computed(() => {
  const type = selectedSubType.value
  if (type === 'niuniu' || type === 'dds' || type === 'czw' || type === 'bbc' || type.startsWith('lh_')) return X5Fun
  if (type.endsWith('_ds')) return X5Input
  if (type.endsWith('_dt')) return X5Dantuo
  if (type.includes('_hz') || type.includes('_hw')) return X5Hezhi
  if (type.startsWith('rx')) return X5Renxuan
  if (type.includes('zhix') || type === 'dwd') return X5Zhixuan
  if (type.includes('zux') || type === 'bdw') return X5Zuxuan
  return X5Renxuan
})

function onBetCount(n) { emit('update:betCount', n) }
function onSelectedData(rawData) {

  if (!rawData) {
    emit('update:selectedData', null)
    return
  }
  
  const betData = {
    playId: selectedSubType.value,     // 玩法ID
    playName: currentPlayTypeName.value, // 玩法名称
    tzcode: typeof rawData === 'string' ? rawData : (rawData.tzcode || rawData.numbers?.join(',') || ''),
    numbers: typeof rawData === 'string' ? rawData.split(',') : (rawData.numbers || []),
    dan: rawData?.dan || [],           // 胆码(胆拖玩法)
    tuo: rawData?.tuo || []            // 拖码(胆拖玩法)
  }
  
  emit('update:selectedData', betData)
}

function selectPlay(type, name) {
  selectedSubType.value = type
  currentPlayTypeName.value = name
  showPlayPicker.value = false
  if (betRef.value?.clear) betRef.value.clear()
  emit('update:betCount', 0)
  emit('update:selectedData', null)
  emit('clear')
}
</script>

<style lang="less" scoped>
@import "@/assets/theme.less";

.panel-standard {
  background-color: #f7f8fa;
  min-height: 60vh;
}

.sticky-header {
  position: sticky;
  top: 46px; 
  z-index: 99;
  background-color: rgba(255, 255, 255, 0.98);
  backdrop-filter: blur(10px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.play-select-bar {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 12px 16px;
    
    .current-play {
        display: flex;
        flex-direction: column;
        align-items: center;
        background: #f0f2f5;
        padding: 6px 24px;
        border-radius: 20px;
        cursor: pointer;
        transition: background 0.2s;
        
        &:active {
            background: #ebedf0;
        }

        .label { font-size: 11px; color: #969799; margin-bottom: 2px; }
        .value-wrap {
            display: flex; align-items: center; gap: 6px;
            .value { font-size: 16px; font-weight: 700; color: #323233; }
            
            .dropdown-badge {
              display: flex; align-items: center; gap: 2px;
              background: #fff;
              padding: 2px 6px;
              border-radius: 10px;
              box-shadow: 0 1px 2px rgba(0,0,0,0.05);
              
              .text { font-size: 10px; color: #666; }
            }
        }
    }
}

.tips-bar {
    background: #fffbe8;
    color: #ed6a0c;
    padding: 10px 16px;
    font-size: 13px;
    display: flex; align-items: center; gap: 8px;
    margin: 0 16px 12px;
    border-radius: 8px;
}

.bet-area {
    padding-bottom: 20px;
    min-height: 200px;
}

.error-placeholder {
    padding: 40px 20px;
    text-align: center;
    color: #969799;
    font-size: 14px;
}

.play-picker {
    display: flex;
    height: 60vh;
    background: #f7f8fa;
    
    .play-tabs {
        width: 100px;
        background: #fff;
        overflow-y: auto;
        
        .play-tab-item {
            padding: 18px 0;
            text-align: center;
            font-size: 14px;
            color: #646566;
            position: relative;
            transition: all 0.2s;
            
            &.active {
                background: #f7f8fa;
                color: @primary-color;
                font-weight: 600;
                &::before {
                    content: '';
                    position: absolute; left: 0; top: 18px; bottom: 18px; width: 3px; background: @primary-color;
                    border-radius: 0 2px 2px 0;
                }
            }
        }
    }
    
    .play-content {
        flex: 1;
        padding: 16px;
        overflow-y: auto;
        
        .menu-section {
            margin-bottom: 20px;
            .section-title {
                font-size: 14px; font-weight: 600; color: #323233; margin-bottom: 12px; padding-left: 4px;
            }
            .chips {
                display: flex; flex-wrap: wrap; gap: 10px;
                .chip {
                    padding: 8px 16px;
                    background: #fff;
                    border: 1px solid transparent;
                    border-radius: 6px;
                    font-size: 13px;
                    color: #323233;
                    min-width: 84px;
                    text-align: center;
                    box-shadow: 0 1px 2px rgba(0,0,0,0.02);
                    transition: all 0.2s;
                    
                    &.active {
                        background: @primary-color;
                        color: #fff;
                        box-shadow: 0 4px 8px rgba(25, 137, 250, 0.3);
                        transform: translateY(-1px);
                    }
                }
            }
        }
    }
}
</style>
