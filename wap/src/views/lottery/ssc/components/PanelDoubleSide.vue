<template>
  <div class="panel-double-side">
    
    <div class="sticky-header">
      <div class="mode-bar">
        <div class="title">双面玩法</div>
        <div class="switch-btn" @click="switchToStandard">
          <span class="text">切换标准</span>
          <van-icon name="exchange" />
        </div>
      </div>
      
      <van-tabs v-model:active="activeTab" shrink animated :border="false" line-width="20px" color="#00FF9A" title-active-color="#00FF9A" title-inactive-color="rgba(255,255,255,0.6)" background="transparent">
        <van-tab title="两面" name="liangmian"></van-tab>
        <van-tab title="1-5球" name="1-5ball"></van-tab>
        <van-tab title="前中后" name="qzh"></van-tab>
      </van-tabs>
    </div>

    
    <div class="play-content">
      
      <div v-if="activeTab === 'liangmian'" class="group-container">
        <div class="play-group">
          <div class="group-title">总和</div>
          <div class="grid-options col-4">
            <div 
              v-for="item in sumOptions" 
              :key="item.id" 
              class="play-item" 
              :class="{ active: isSelected(item.id) }"
              @click="toggleItem(item)"
            >
              <span class="label">{{ item.label }}</span>
              <span class="odds">{{ item.odds }}</span>
            </div>
          </div>
        </div>
        
        <div class="play-group">
          <div class="group-title">龙虎</div>
          <div class="grid-options col-3">
            <div 
              v-for="item in lhOptions" 
              :key="item.id" 
              class="play-item" 
              :class="{ active: isSelected(item.id) }"
              @click="toggleItem(item)"
            >
              <span class="label">{{ item.label }}</span>
              <span class="odds">{{ item.odds }}</span>
            </div>
          </div>
        </div>
      </div>

      
      <div v-else-if="activeTab === '1-5ball'" class="group-container">
        <div v-for="(pos, idx) in positions" :key="idx" class="play-group">
          <div class="group-title">{{ pos.name }}</div>
          
          <div class="grid-options col-4 mb-2">
            <div 
              v-for="type in ['大','小','单','双']" 
              :key="`${idx}-${type}`" 
              class="play-item" 
              :class="{ active: isSelected(`${pos.prefix}_${dxdsMap[type]}`) }"
              @click="toggleBallDxds(pos.prefix, type, 1.98)"
            >
              <span class="label">{{ type }}</span>
              <span class="odds">1.98</span>
            </div>
          </div>
          
          <div class="ball-options">
            <div 
              v-for="num in 10" 
              :key="`${idx}-${num-1}`"
              class="ball-wrapper"
              :class="{ active: isSelected(`${pos.prefix}h_${num-1}`) }"
              @click="toggleBallNum(pos.prefix, num-1, 9.8)"
            >
              <div class="ball">{{ num-1 }}</div>
              <div class="odds">9.8</div>
            </div>
          </div>
        </div>
      </div>

      
      <div v-else-if="activeTab === 'qzh'" class="group-container">
        <div v-for="g in qzhGroups" :key="g.name" class="play-group">
          <div class="group-title">{{ g.name }}</div>
          <div class="grid-options col-3">
            <div 
              v-for="opt in g.options" 
              :key="opt" 
              class="play-item"
              :class="{ active: isSelected(`${g.code}_${opt}`) }"
              @click="toggleQzh(g.code, opt, 3.2)"
            >
              <span class="label">{{ opt }}</span>
              <span class="odds">3.2</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { Tab as VanTab, Tabs as VanTabs, Icon as VanIcon } from 'vant'

const router = useRouter()
const route = useRoute()
const emit = defineEmits(['update:betCount', 'update:selectedData', 'clear'])

const activeTab = ref('liangmian')

const sumOptions = [
  { id: 'sum_big', playId: 'zhlhzhd', label: '总和大', odds: 1.98 },
  { id: 'sum_small', playId: 'zhlhzhx', label: '总和小', odds: 1.98 },
  { id: 'sum_odd', playId: 'zhlhzhdd', label: '总和单', odds: 1.98 },
  { id: 'sum_even', playId: 'zhlhzhss', label: '总和双', odds: 1.98 },
]
const lhOptions = [
  { id: 'lh_long', playId: 'zhlhl', label: '龙', odds: 1.98 },
  { id: 'lh_hu', playId: 'zhlhh', label: '虎', odds: 1.98 },
  { id: 'lh_he', playId: 'zhlhhe', label: '和', odds: 9.8 },
]

const positions = [
  { name: '第一球', prefix: 'sscww' },
  { name: '第二球', prefix: 'sscqw' },
  { name: '第三球', prefix: 'sscbw' },
  { name: '第四球', prefix: 'sscsw' },
  { name: '第五球', prefix: 'sscgw' }
]

const dxdsMap = { '大': 'd', '小': 'x', '单': 'dd', '双': 'ss' }

const numMap = { 0: 'n', 1: 'n', 2: 'n', 3: 'n', 4: 'n', 5: 'n', 6: 'n', 7: 'n', 8: 'n', 9: 'n' }
const qzhGroups = [
  { name: '前三', code: 'q3', options: ['豹子','顺子','对子','半顺','杂六'] },
  { name: '中三', code: 'z3', options: ['豹子','顺子','对子','半顺','杂六'] },
  { name: '后三', code: 'h3', options: ['豹子','顺子','对子','半顺','杂六'] },
]

const selectedMap = ref(new Map())

function switchToStandard() {
  router.replace({ name: 'LotterySsc', params: route.params, query: { mode: '' } })
}

function isSelected(id) {
  return selectedMap.value.has(id)
}

function toggleItem(item) {
  if (selectedMap.value.has(item.id)) {
    selectedMap.value.delete(item.id)
  } else {
    selectedMap.value.set(item.id, {
      playId: item.playId,
      value: item.label,
      odds: item.odds
    })
  }
  sync()
}

function toggleBallDxds(prefix, type, odds) {
  const playId = prefix + dxdsMap[type]
  const id = `${prefix}_${dxdsMap[type]}`
  if (selectedMap.value.has(id)) {
    selectedMap.value.delete(id)
  } else {
    selectedMap.value.set(id, { playId, value: type, odds })
  }
  sync()
}

function toggleBallNum(prefix, num, odds) {
  const playId = prefix + 'h'  // 如 sscwwh 万位号码
  const id = `${prefix}h_${num}`
  if (selectedMap.value.has(id)) {
    selectedMap.value.delete(id)
  } else {
    selectedMap.value.set(id, { playId: playId + '_' + num, value: String(num), odds })
  }
  sync()
}

function toggleQzh(groupCode, opt, odds) {
  const id = `${groupCode}_${opt}`
  if (selectedMap.value.has(id)) {
    selectedMap.value.delete(id)
  } else {
    selectedMap.value.set(id, { playId: `ssc_${groupCode}_${opt}`, value: opt, odds })
  }
  sync()
}

function sync() {
  const selections = Array.from(selectedMap.value.values())
  emit('update:betCount', selections.length)
  emit('update:selectedData', { selections })
}

function clear() {
  selectedMap.value.clear()
  sync()
}

defineExpose({ clear })
</script>

<style lang="less" scoped>
@import "@/assets/theme.less";

.panel-double-side {
  background-color: transparent;
  display: flex;
  flex-direction: column;
  height: calc(100vh - 280px); // 减去顶部信息区和底部footer
  overflow: hidden;
}

.sticky-header {
  flex-shrink: 0;
  z-index: 99;
  background: rgba(5, 8, 15, 0.9);
  backdrop-filter: blur(16px);
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  margin: -16px -16px 0 -16px;
}

.mode-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 16px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  
  .title { 
    font-weight: bold; font-size: 15px; color: #00FF9A; 
    border-left: 3px solid #00FF9A; padding-left: 8px; 
  }
  .switch-btn {
    font-size: 12px; color: #00FF9A; 
    background: rgba(0, 255, 154, 0.1);
    border: 1px solid rgba(0, 255, 154, 0.2);
    padding: 4px 10px; border-radius: 12px; display: flex; align-items: center; gap: 4px;
  }
}

.play-content {
  flex: 1;
  overflow-y: auto;
  padding: 12px;
  -webkit-overflow-scrolling: touch;
  
  &::-webkit-scrollbar {
    width: 4px;
  }
  &::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 2px;
  }
  &::-webkit-scrollbar-track {
    background: transparent;
  }
}

.group-container {
  display: flex; flex-direction: column; gap: 12px;
}

.play-group {
  background: rgba(23, 30, 46, 0.6);
  border-radius: 10px;
  padding: 12px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  backdrop-filter: blur(10px);
  
  .group-title {
    font-size: 13px; font-weight: bold; color: rgba(255, 255, 255, 0.8); margin-bottom: 12px;
    display: flex; align-items: center;
    &::before { content:''; width: 4px; height: 4px; background: #00FF9A; border-radius: 50%; margin-right: 6px; box-shadow: 0 0 6px #00FF9A; }
  }
}

.grid-options {
  display: grid; gap: 8px;
  &.col-4 { grid-template-columns: repeat(4, 1fr); }
  &.col-3 { grid-template-columns: repeat(3, 1fr); }
}

.mb-2 { margin-bottom: 10px; }

.play-item {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 6px;
  padding: 10px 4px;
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  cursor: pointer; transition: all 0.2s;
  
  .label { font-size: 13px; color: rgba(255, 255, 255, 0.9); margin-bottom: 4px; }
  .odds { font-size: 11px; color: rgba(255, 255, 255, 0.4); }
  
  &.active {
    background: rgba(0, 255, 154, 0.15);
    border-color: #00FF9A;
    box-shadow: 0 0 12px rgba(0, 255, 154, 0.2);
    .label { color: #00FF9A; font-weight: bold; }
    .odds { color: #00FF9A; }
  }
}

.ball-options {
  display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px;
}

.ball-wrapper {
  display: flex; flex-direction: column; align-items: center; gap: 4px;
  background: rgba(255, 255, 255, 0.03); border-radius: 8px; padding: 8px 0;
  border: 1px solid rgba(255, 255, 255, 0.05);
  transition: all 0.2s;
  
  .ball {
    width: 34px; height: 34px; border-radius: 50%;
    background: rgba(255, 255, 255, 0.1); 
    border: 1px solid rgba(255, 255, 255, 0.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; color: #fff; font-weight: bold;
    transition: all 0.2s;
  }
  .odds { font-size: 11px; color: rgba(255, 255, 255, 0.4); }
  
  &.active {
    background: rgba(0, 255, 154, 0.1); 
    border-color: rgba(0, 255, 154, 0.3);
    .ball { 
      background: linear-gradient(135deg, #00FF9A, #00cc7a); 
      color: #000; 
      border-color: transparent;
      box-shadow: 0 2px 10px rgba(0, 255, 154, 0.4);
    }
    .odds { color: #00FF9A; }
  }
}
</style>


