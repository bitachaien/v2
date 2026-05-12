<template>
  <div class="panel-standard">
    
    <div class="sticky-header">
      <div class="play-select-bar" @click="showPlayPicker = true">
        <div class="current-play">
           <span class="label">标准玩法</span>
           <div class="value-wrap">
             <span class="value">{{ currentPlayTypeName }}</span>
             <van-icon name="arrow-down" size="14" color="rgba(255,255,255,0.6)" />
           </div>
        </div>
        <div class="switch-btn" @click.stop="switchToShuangmian">
           <span class="text">切换双面</span>
           <van-icon name="exchange" />
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
import { ref, computed, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { Icon as VanIcon, ActionSheet as VanActionSheet } from 'vant'

import SscFushi from './SscFushi.vue'
import SscDanshi from './SscDanshi.vue'
import SscZuxuan from './SscZuxuan.vue'
import SscHezhi from './SscHezhi.vue'
import SscPlaceholder from './SscPlaceholder.vue'
import SscBudingwei from './SscBudingwei.vue'
import SscDaxiaodanshuang from './SscDaxiaodanshuang.vue'
import SscLonghuhe from './SscLonghuhe.vue'
import SscGroupFive from './SscGroupFive.vue'
import SscGroupFour from './SscGroupFour.vue'
import SscKuadu from './SscKuadu.vue'
import SscZuxHezhi from './SscZuxHezhi.vue'
import SscZuxBaodan from './SscZuxBaodan.vue'
import SscQuwei from './SscQuwei.vue'

const router = useRouter()
const route = useRoute()
const emit = defineEmits(['update:betCount', 'update:selectedData', 'clear'])

const selectedSubType = ref('wxzhixfs')
const currentPlayTypeName = ref('五星直选复式')
const showPlayPicker = ref(false)
const betRef = ref(null)
const currentTab = ref('5x')

const clear = () => {
    if (betRef.value?.clear) betRef.value.clear()
}

function switchToShuangmian(){
  router.replace({ 
      name: 'LotterySsc', 
      params: { code: route.params.code || 'cqssc' }, 
      query: { mode: 'double' } 
  })
}

const menuSchema = ref({
  '5x': {
    title: '五星',
    sections: [
      { title: '直选', items: [
        { code:'wxzhixfs', label:'复式' },
        { code:'wxzhixds', label:'单式' }
      ]},
      { title: '组选', items: [
        { code:'wxzxyel', label:'组选120' }, { code:'wxzxls', label:'组选60' }, { code:'wxzxsl', label:'组选30' },
        { code:'wxzxel', label:'组选20' }, { code:'wxzxyl', label:'组选10' }, { code:'wxzxw', label:'组选5' }
      ]},
      { title: '不定位', items: [
        { code:'bdw5x1m', label:'不定位1码' }, { code:'bdw5x2m', label:'不定位2码' }, { code:'bdw5x3m', label:'不定位3码' }
      ]},
      { title: '趣味', items: [
        { code:'qwyffs', label:'一帆风顺' }, { code:'qwhscs', label:'好事成双' }, { code:'qwsxbx', label:'三星报喜' }, { code:'qwsjfc', label:'四季发财' }
      ]}
    ]
  },
  '4x': {
    title: '四星',
    sections: [
      { title: '直选', items: [ {code:'sixzhixfsh', label:'复式'}, {code:'sixzhixdsh', label:'单式'} ]},
      { title: '组选', items: [ {code:'hsizxes', label:'组选24'}, {code:'hsizxye', label:'组选12'}, {code:'hsizxl', label:'组选6'}, {code:'hsizxs', label:'组选4'} ]},
      { title: '不定位', items: [ {code:'bdw4x1m', label:'不定位1码'}, {code:'bdw4x2m', label:'不定位2码'} ]}
    ]
  },
  'q3': {
    title: '前三',
    sections: [
      { title: '直选', items: [ {code:'sxzhixfsq', label:'复式'}, {code:'sxzhixdsq', label:'单式'}, {code:'zhixhzqs', label:'直选和值'}, {code:'kuaduqs', label:'跨度'} ]},
      { title: '组选', items: [ {code:'sxzxfsq3', label:'组三'}, {code:'sxzxfsq6', label:'组六'}, {code:'zuxhzqs', label:'组选和值'}, {code:'sxhhzxq', label:'混合组选'}, {code:'zuxcsbd', label:'组选包胆'}, {code:'qszsds', label:'组三单式'}, {code:'qszlds', label:'组六单式'} ]},
      { title: '不定位', items: [ {code:'bdwqs', label:'不定位1码'}, {code:'bdwqs2m', label:'不定位2码'} ]}
    ]
  },
  'z3': {
    title: '中三',
    sections: [
      { title: '直选', items: [ {code:'sxzhixfsz', label:'复式'}, {code:'sxzhixdsz', label:'单式'}, {code:'zhixhzzs', label:'直选和值'}, {code:'kuaduzs', label:'跨度'} ]},
      { title: '组选', items: [ {code:'sxzxfsz3', label:'组三'}, {code:'sxzxfsz6', label:'组六'}, {code:'zuxhzzs', label:'组选和值'}, {code:'sxhhzxz', label:'混合组选'}, {code:'zuxzsbd', label:'组选包胆'}, {code:'zszsds', label:'组三单式'}, {code:'zszlds', label:'组六单式'} ]},
      { title: '不定位', items: [ {code:'bdwzs', label:'不定位1码'}, {code:'bdwzs2m', label:'不定位2码'} ]}
    ]
  },
  'h3': {
    title: '后三',
    sections: [
      { title: '直选', items: [ {code:'sxzhixfsh', label:'复式'}, {code:'sxzhixdsh', label:'单式'}, {code:'zhixhzhs', label:'直选和值'}, {code:'kuaduhs', label:'跨度'} ]},
      { title: '组选', items: [ {code:'sxzxfsh3', label:'组三'}, {code:'sxzxfsh6', label:'组六'}, {code:'zuxhzhs', label:'组选和值'}, {code:'sxhhzxh', label:'混合组选'}, {code:'zuxhsbd', label:'组选包胆'}, {code:'hszsds', label:'组三单式'}, {code:'hszlds', label:'组六单式'} ]},
      { title: '不定位', items: [ {code:'bdwhs', label:'不定位1码'}, {code:'bdwhs2m', label:'不定位2码'} ]}
    ]
  },
  'ex': {
    title: '前二/后二',
    sections: [
      { title: '前二 直选', items: [ {code:'exzhixfsq', label:'复式'}, {code:'exzhixdsq', label:'单式'}, {code:'zhixhzqe', label:'直选和值'}, {code:'kuaduqe', label:'跨度'} ]},
      { title: '前二 组选', items: [ {code:'exzuxfsq', label:'复式'}, {code:'exzuxdsq', label:'单式'}, {code:'zuxhzqe', label:'组选和值'}, {code:'zuxcebd', label:'组选包胆'} ]},
      { title: '后二 直选', items: [ {code:'exzhixfsh', label:'复式'}, {code:'exzhixdsh', label:'单式'}, {code:'zhixhzhe', label:'直选和值'}, {code:'kuaduhe', label:'跨度'} ]},
      { title: '后二 组选', items: [ {code:'exzuxfsh', label:'复式'}, {code:'exzuxdsh', label:'单式'}, {code:'zuxhzhe', label:'组选和值'}, {code:'zuxhebd', label:'组选包胆'} ]}
    ]
  },
  '1x': {
    title: '一星',
    sections: [ { title: '定位胆', items: [ {code:'dweid', label:'定位胆'} ] } ]
  },
  'dxds': {
    title: '大小单双',
    sections: [ { title: '大小单双', items: [ {code:'dxdsqe', label:'前二'}, {code:'dxdshe', label:'后二'}, {code:'dxdsqs', label:'前三'}, {code:'dxdshs', label:'后三'} ] } ]
  }
})

const currentSection = computed(() => menuSchema.value[currentTab.value] || {})

const playTips = computed(() => {
  const map = {
    wxzhixfs: '每位至少选择1个号码',
    wxzhixds: '每注5位数字，例如：12345 67890',
    sixzhixfsh: '每位至少选择1个号码',
    dxdsqe: '分别选择大小单双',
    dxdshe: '分别选择大小单双',
    dweid: '任选位置选择号码',
  }
  return map[selectedSubType.value] || ''
})

const componentProps = computed(() => {
  const cfg = {
    wxzhixfs: { positions: ['万','千','百','十','个'] },
    wxzhixds: { numberLength: 5 },
    sixzhixfsh: { positions: ['千','百','十','个'] },
    sixzhixdsh: { numberLength: 4 },
    sxzhixfsq: { positions: ['万','千','百'] },
    sxzhixfsz: { positions: ['千','百','十'] },
    sxzhixfsh: { positions: ['百','十','个'] },
    sxzhixdsq: { numberLength: 3 },
    sxzhixdsz: { numberLength: 3 },
    sxzhixdsh: { numberLength: 3 },
    exzhixfsq: { positions: ['万','千'] },
    exzhixfsh: { positions: ['十','个'] },
    exzhixdsq: { numberLength: 2 },
    exzhixdsh: { numberLength: 2 },
    exzuxfsq: { type: 'z2' },
    exzuxfsh: { type: 'z2' },
    exzuxdsq: { numberLength: 2 },
    exzuxdsh: { numberLength: 2 },
    dxdsqe: { positions: ['万','千'] },
    dxdshe: { positions: ['十','个'] },
    dxdsqs: { positions: ['万','千','百'] },
    dxdshs: { positions: ['百','十','个'] },
    kuaduqs: { digits: 3 }, kuaduzs: { digits: 3 }, kuaduhs: { digits: 3 },
    kuaduqe: { digits: 2 }, kuaduhe: { digits: 2 },
    zuxhzqs: { digits: 3 }, zuxhzzs: { digits: 3 }, zuxhzhs: { digits: 3 },
    zuxhzqe: { digits: 2 }, zuxhzhe: { digits: 2 },
    sxhhzxq: { numberLength: 3 }, sxhhzxz: { numberLength: 3 }, sxhhzxh: { numberLength: 3 },
    zuxcsbd: { digits: 3 }, zuxzsbd: { digits: 3 }, zuxhsbd: { digits: 3 },
    zuxcebd: { digits: 2 }, zuxhebd: { digits: 2 },
    sxzxfsq3: { type: 'z3' }, sxzxfsq6: { type: 'z6' },
    sxzxfsz3: { type: 'z3' }, sxzxfsz6: { type: 'z6' },
    sxzxfsh3: { type: 'z3' }, sxzxfsh6: { type: 'z6' },
    zhixhzqs: { hezhiRange: [0,27] }, zhixhzzs: { hezhiRange: [0,27] }, zhixhzhs: { hezhiRange: [0,27] },
    zhixhzqe: { hezhiRange: [0,18] }, zhixhzhe: { hezhiRange: [0,18] },
    wxzxyel: { type: '120' }, wxzxls: { type: '60' }, wxzxsl: { type: '30' },
    wxzxel: { type: '20' }, wxzxyl: { type: '10' }, wxzxw: { type: '5' },
    hsizxes: { type: '24' }, hsizxye: { type: '12' }, hsizxl: { type: '6' }, hsizxs: { type: '4' },
    qszsds: { numberLength: 3 }, qszlds: { numberLength: 3 },
    zszsds: { numberLength: 3 }, zszlds: { numberLength: 3 },
    hszsds: { numberLength: 3 }, hszlds: { numberLength: 3 },
    bdw5x1m: { minSelect: 1 }, bdw5x2m: { minSelect: 2 }, bdw5x3m: { minSelect: 3 },
    bdw4x1m: { minSelect: 1 }, bdw4x2m: { minSelect: 2 },
    bdwqs: { minSelect: 1 }, bdwqs2m: { minSelect: 2 },
    bdwzs: { minSelect: 1 }, bdwzs2m: { minSelect: 2 },
    bdwhs: { minSelect: 1 }, bdwhs2m: { minSelect: 2 },
    dweid: { positions: ['万','千','百','十','个'], isSum: true }
  }
  return cfg[selectedSubType.value] || {}
})

const currentComponent = computed(() => {
  const map = {
    wxzhixfs: SscFushi,
    wxzhixds: SscDanshi,
    sixzhixfsh: SscFushi,
    sixzhixdsh: SscDanshi,
    sxzhixfsq: SscFushi,
    sxzhixfsz: SscFushi,
    sxzhixfsh: SscFushi,
    sxzhixdsq: SscDanshi,
    sxzhixdsz: SscDanshi,
    sxzhixdsh: SscDanshi,
    exzhixfsq: SscFushi,
    exzhixfsh: SscFushi,
    exzhixdsq: SscDanshi,
    exzhixdsh: SscDanshi,
    dxdsqe: SscDaxiaodanshuang,
    dxdshe: SscDaxiaodanshuang,
    dxdsqs: SscDaxiaodanshuang,
    dxdshs: SscDaxiaodanshuang,
    sxzxfsq3: SscZuxuan, sxzxfsq6: SscZuxuan,
    sxzxfsz3: SscZuxuan, sxzxfsz6: SscZuxuan,
    sxzxfsh3: SscZuxuan, sxzxfsh6: SscZuxuan,
    zhixhzqs: SscHezhi, zhixhzzs: SscHezhi, zhixhzhs: SscHezhi,
    kuaduqs: SscKuadu, kuaduzs: SscKuadu, kuaduhs: SscKuadu,
    zuxhzqs: SscZuxHezhi, zuxhzzs: SscZuxHezhi, zuxhzhs: SscZuxHezhi,
    sxhhzxq: SscDanshi, sxhhzxz: SscDanshi, sxhhzxh: SscDanshi,
    zuxcsbd: SscZuxBaodan, zuxzsbd: SscZuxBaodan, zuxhsbd: SscZuxBaodan,
    qszsds: SscDanshi, qszlds: SscDanshi,
    zszsds: SscDanshi, zszlds: SscDanshi,
    hszsds: SscDanshi, hszlds: SscDanshi,
    exzuxfsq: SscZuxuan, exzuxfsh: SscZuxuan,
    exzuxdsq: SscDanshi, exzuxdsh: SscDanshi,
    zuxhzqe: SscZuxHezhi, zuxhzhe: SscZuxHezhi,
    zuxcebd: SscZuxBaodan, zuxhebd: SscZuxBaodan,
    zhixhzqe: SscHezhi, zhixhzhe: SscHezhi,
    kuaduqe: SscKuadu, kuaduhe: SscKuadu,
    wxzxyel: SscGroupFive, wxzxls: SscGroupFive, wxzxsl: SscGroupFive,
    wxzxel: SscGroupFive, wxzxyl: SscGroupFive, wxzxw: SscGroupFive,
    hsizxes: SscGroupFour, hsizxye: SscGroupFour, hsizxl: SscGroupFour, hsizxs: SscGroupFour,
    qwyffs: SscQuwei, qwhscs: SscQuwei, qwsxbx: SscQuwei, qwsjfc: SscQuwei,
    bdw5x1m: SscBudingwei, bdw5x2m: SscBudingwei, bdw5x3m: SscBudingwei,
    bdw4x1m: SscBudingwei, bdw4x2m: SscBudingwei,
    bdwqs: SscBudingwei, bdwqs2m: SscBudingwei,
    bdwzs: SscBudingwei, bdwzs2m: SscBudingwei,
    bdwhs: SscBudingwei, bdwhs2m: SscBudingwei,
    lhwq: SscLonghuhe, dweid: SscFushi
  }
  const component = map[selectedSubType.value]
  if (!component) {
    console.warn(`未找到组件映射: ${selectedSubType.value}，使用默认组件 SscFushi`)
  }
  return component || SscFushi
})

function onBetCount(n) { emit('update:betCount', n) }
function onSelectedData(d) { 

  emit('update:selectedData', {
    playType: selectedSubType.value,
    playName: currentPlayTypeName.value,
    tzcode: d
  })
}

function getCurrentPlayType() {
  return selectedSubType.value
}
function getCurrentPlayName() {
  return currentPlayTypeName.value
}
defineExpose({ clear, getCurrentPlayType, getCurrentPlayName })

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
  background-color: transparent;
  min-height: 60vh;
}

.sticky-header {
  position: relative;
  z-index: 10;
  background: rgba(5, 8, 15, 0.95);
  backdrop-filter: blur(16px);
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  margin: -16px -16px 12px -16px;
  padding: 0;
}

.play-select-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    
    .current-play {
        display: flex;
        flex-direction: column;
        .label { font-size: 12px; color: rgba(255, 255, 255, 0.5); margin-bottom: 4px; }
        .value-wrap {
            display: flex; align-items: center; gap: 6px;
            .value { font-size: 16px; font-weight: 700; color: #00FF9A; letter-spacing: 0.5px; }
        }
    }
    .switch-btn {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        color: #00FF9A;
        background: rgba(0, 255, 154, 0.1);
        border: 1px solid rgba(0, 255, 154, 0.2);
        padding: 8px 14px;
        border-radius: 20px;
        font-weight: 600;
        transition: all 0.2s;
        
        &:active {
            background: rgba(0, 255, 154, 0.2);
            transform: scale(0.96);
        }
    }
}

.tips-bar {
    background: rgba(255, 193, 7, 0.1);
    border: 1px solid rgba(255, 193, 7, 0.2);
    color: #FFC107;
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
    color: rgba(255, 255, 255, 0.5);
    font-size: 14px;
}

:deep(.play-action-sheet) {
  background: #0C0F17;
}

.play-picker {
    display: flex;
    height: 70vh;
    max-height: 600px;
    background: #0C0F17;
    
    .play-tabs {
        width: 90px;
        flex-shrink: 0;
        background: rgba(18, 24, 37, 0.8);
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
        
        
        &::-webkit-scrollbar {
          width: 0;
          display: none;
        }
        
        .play-tab-item {
            padding: 14px 6px;
            text-align: center;
            font-size: 13px;
            color: rgba(255, 255, 255, 0.6);
            position: relative;
            transition: all 0.2s;
            white-space: nowrap;
            
            &.active {
                background: rgba(0, 255, 154, 0.05);
                color: #00FF9A;
                font-weight: 600;
                &::before {
                    content: '';
                    position: absolute; left: 0; top: 14px; bottom: 14px; width: 3px; background: #00FF9A;
                    border-radius: 0 2px 2px 0;
                    box-shadow: 0 0 8px #00FF9A;
                }
            }
        }
    }
    
    .play-content {
        flex: 1;
        padding: 12px;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
        
        .menu-section {
            margin-bottom: 16px;
            .section-title {
                font-size: 13px; font-weight: 600; color: rgba(255, 255, 255, 0.8); margin-bottom: 10px; padding-left: 4px;
            }
            .chips {
                display: flex; flex-wrap: wrap; gap: 8px;
                .chip {
                    padding: 8px 12px;
                    background: rgba(255, 255, 255, 0.05);
                    border: 1px solid rgba(255, 255, 255, 0.1);
                    border-radius: 6px;
                    font-size: 12px;
                    color: rgba(255, 255, 255, 0.8);
                    min-width: 70px;
                    text-align: center;
                    transition: all 0.2s;
                    
                    &.active {
                        background: rgba(0, 255, 154, 0.15);
                        border-color: #00FF9A;
                        color: #00FF9A;
                        box-shadow: 0 0 10px rgba(0, 255, 154, 0.2);
                        transform: translateY(-1px);
                    }
                }
            }
        }
    }
}
</style>
