<template>
  <div class="betting-panel-hhm">
    
    
    <div class="play-section">
      <div class="grid-container col-1">
        <div class="grid-item full-width" @click="toggleSelection(generalOption)">
           <div :class="['bet-btn', 'general', { 'selected': isSelected(generalOption) }]">
             <div class="btn-content">
               <span class="main-text">{{ generalOption.name }}</span>
               <span class="sub-text">赔率 1.98</span>
             </div>
             <div class="glow-effect"></div>
           </div>
        </div>
      </div>
      
      <div class="grid-container col-2 mt-12">
        <div 
          v-for="item in basicOptions"
          :key="item.playid"
          class="grid-item"
          @click="toggleSelection(item)"
        >
          <div :class="['bet-btn', item.color, { 'selected': isSelected(item) }]">
            <div class="btn-content">
               <span class="main-text">{{ item.name }}</span>
            </div>
            <div class="glow-effect"></div>
          </div>
        </div>
      </div>
    </div>

    
    <div class="play-section">
      <div class="section-header">
        <span class="title-text">大小单双</span>
      </div>
      
      <div class="grid-container col-4">
        
        <div 
          v-for="item in redOptions"
          :key="item.playid"
          class="grid-item"
          @click="toggleSelection(item)"
        >
          <div :class="['bet-btn', 'red', 'small', { 'selected': isSelected(item) }]">
            <div class="btn-content">
              <span class="main-text">{{ item.name.replace('红码', '') }}</span>
              <span class="tag">红</span>
            </div>
            <div class="glow-effect"></div>
          </div>
        </div>
        
        
        <div 
          v-for="item in blackOptions"
          :key="item.playid"
          class="grid-item"
          @click="toggleSelection(item)"
        >
          <div :class="['bet-btn', 'black', 'small', { 'selected': isSelected(item) }]">
            <div class="btn-content">
              <span class="main-text">{{ item.name.replace('黑码', '') }}</span>
              <span class="tag">黑</span>
            </div>
            <div class="glow-effect"></div>
          </div>
        </div>
      </div>
    </div>

    
    <div class="play-section">
      <div class="section-header">
        <span class="title-text">特殊号码</span>
      </div>
      <div class="grid-container col-3">
        <div 
          v-for="item in specialOptions"
          :key="item.playid"
          class="grid-item"
          @click="toggleSelection(item)"
        >
          <div :class="['bet-btn', item.color, 'small', { 'selected': isSelected(item) }]">
            <div class="btn-content">
              <span class="main-text">{{ item.name }}</span>
            </div>
            <div class="glow-effect"></div>
          </div>
        </div>
      </div>
    </div>

    
    <div class="play-section">
      <div class="section-header">
        <span class="title-text">红黑具体号</span>
      </div>
      <div class="grid-container col-6">
        <div 
          v-for="item in numberOptions"
          :key="item.playid"
          class="grid-item"
          @click="toggleSelection(item)"
        >
          <div :class="['bet-btn', 'number', item.color, { 'selected': isSelected(item) }]">
            <div class="btn-content">
              <span class="main-text">{{ item.name }}</span>
            </div>
            <div class="glow-effect"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, inject, computed } from 'vue';
import { useK3BetStore } from '@/stores/k3Bet';

const betStore = useK3BetStore();

const oddsData = inject('oddsData', ref(null));

const getColorFromName = (name) => {
  if (name?.includes('红') || ['3', '4', '5'].includes(name)) return 'red';
  if (name?.includes('黑') || ['1', '2', '6'].includes(name)) return 'black';
  return '';
};

const defaultGeneralOption = { playid: 'k3hhm', name: '红黑码' };

const defaultBasicOptions = [
  { playid: 'hhmhong', name: '红码', color: 'red' },
  { playid: 'hhmhei', name: '黑码', color: 'black' },
];

const defaultRedOptions = [
  { playid: 'hhmhongd', name: '红码大', color: 'red' },
  { playid: 'hhmhongx', name: '红码小', color: 'red' },
  { playid: 'hhmhongdd', name: '红码单', color: 'red' },
  { playid: 'hhmhongss', name: '红码双', color: 'red' },
];

const defaultBlackOptions = [
  { playid: 'hhmheid', name: '黑码大', color: 'black' },
  { playid: 'hhmheix', name: '黑码小', color: 'black' },
  { playid: 'hhmheidd', name: '黑码单', color: 'black' },
  { playid: 'hhmheiss', name: '黑码双', color: 'black' },
];

const defaultSpecialOptions = [
  { playid: 'hhmhong4hong', name: '四码红', color: 'red' },
  { playid: 'hhmhong4hei', name: '四码黑', color: 'black' },
  { playid: 'hhmhong5hei', name: '五码黑', color: 'black' },
];

const defaultNumberOptions = [
  { playid: 'hhmhei5hei_1', name: '1', color: 'black' },
  { playid: 'hhmhei5hei_2', name: '2', color: 'black' },
  { playid: 'hhmhei5hei_3', name: '3', color: 'red' },
  { playid: 'hhmhei5hei_4', name: '4', color: 'red' },
  { playid: 'hhmhei5hei_5', name: '5', color: 'red' },
  { playid: 'hhmhei5hei_6', name: '6', color: 'black' },
];

const generalOption = computed(() => {
  if (oddsData.value && oddsData.value.groups) {
    const group = oddsData.value.groups.find(g => 
      g.groupId === 'hhm' || g.title?.includes('红黑码')
    );
    if (group && group.plays) {
      const play = group.plays.find(p => p.name === '红黑码');
      if (play) {
        return { playid: play.playId, name: play.name };
      }
    }
  }
  return defaultGeneralOption;
});

const basicOptions = computed(() => {
  if (oddsData.value && oddsData.value.groups) {
    const group = oddsData.value.groups.find(g => 
      g.groupId === 'hhm' || g.title?.includes('红黑码')
    );
    if (group && group.plays) {
      const plays = group.plays.filter(p => ['红码', '黑码'].includes(p.name));
      if (plays.length > 0) {
        return plays.map(play => ({
          playid: play.playId,
          name: play.name,
          color: getColorFromName(play.name)
        }));
      }
    }
  }
  return defaultBasicOptions;
});

const redOptions = computed(() => {
  if (oddsData.value && oddsData.value.groups) {
    const group = oddsData.value.groups.find(g => 
      g.groupId === 'hhm' || g.title?.includes('红黑码')
    );
    if (group && group.plays) {
      const plays = group.plays.filter(p => 
        ['红码大', '红码小', '红码单', '红码双'].includes(p.name)
      );
      if (plays.length > 0) {
        return plays.map(play => ({
          playid: play.playId,
          name: play.name,
          color: 'red'
        }));
      }
    }
  }
  return defaultRedOptions;
});

const blackOptions = computed(() => {
  if (oddsData.value && oddsData.value.groups) {
    const group = oddsData.value.groups.find(g => 
      g.groupId === 'hhm' || g.title?.includes('红黑码')
    );
    if (group && group.plays) {
      const plays = group.plays.filter(p => 
        ['黑码大', '黑码小', '黑码单', '黑码双'].includes(p.name)
      );
      if (plays.length > 0) {
        return plays.map(play => ({
          playid: play.playId,
          name: play.name,
          color: 'black'
        }));
      }
    }
  }
  return defaultBlackOptions;
});

const specialOptions = computed(() => {
  if (oddsData.value && oddsData.value.groups) {
    const group = oddsData.value.groups.find(g => 
      g.groupId === 'hhm' || g.title?.includes('红黑码')
    );
    if (group && group.plays) {
      const plays = group.plays.filter(p => 
        ['四码红', '四码黑', '五码黑'].includes(p.name)
      );
      if (plays.length > 0) {
        return plays.map(play => ({
          playid: play.playId,
          name: play.name,
          color: getColorFromName(play.name)
        }));
      }
    }
  }
  return defaultSpecialOptions;
});

const numberOptions = computed(() => {
  if (oddsData.value && oddsData.value.groups) {
    const group = oddsData.value.groups.find(g => 
      g.groupId === 'hhm' || g.title?.includes('红黑码')
    );
    if (group && group.plays) {
      const plays = group.plays.filter(p => /^[1-6]$/.test(p.name));
      if (plays.length > 0) {
        return plays.map(play => ({
          playid: play.playId,
          name: play.name,
          color: getColorFromName(play.name)
        }));
      }
    }
  }
  return defaultNumberOptions;
});

const isSelected = (item) => {
  return betStore.selections.some(s => s.playId === 'k3hhm' && s.items.some(i => i.playid === item.playid));
};

const toggleSelection = (item) => {
  betStore.toggleSelection(item, 'k3hhm');
};
</script>

<style lang="less" scoped>
.betting-panel-hhm {
  padding-bottom: 20px;
}

.play-section {
  margin-bottom: 24px;
  
  &:last-child {
    margin-bottom: 0;
  }
}

.section-header {
  margin-bottom: 12px;
  padding-left: 4px;
  
  .title-text {
    font-size: 14px;
    font-weight: bold;
    color: #fff;
    border-left: 3px solid #00FF9A;
    padding-left: 8px;
  }
}

.grid-container {
  display: grid;
  gap: 8px;
  
  &.col-1 { grid-template-columns: 1fr; }
  &.col-2 { grid-template-columns: repeat(2, 1fr); }
  &.col-3 { grid-template-columns: repeat(3, 1fr); }
  &.col-4 { grid-template-columns: repeat(4, 1fr); }
  &.col-6 { grid-template-columns: repeat(6, 1fr); }
}

.mt-12 { margin-top: 12px; }

.bet-btn {
  position: relative;
  width: 100%;
  background: #171E2E;
  border-radius: 8px;
  border: 1px solid rgba(255, 255, 255, 0.05);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  overflow: hidden;
  min-height: 44px;
  
  .btn-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 2px;
    z-index: 2;
    width: 100%;
    padding: 8px 0;
    
    .main-text {
      font-size: 14px;
      font-weight: bold;
      color: rgba(255, 255, 255, 0.9);
    }
    
    .sub-text {
      font-size: 10px;
      color: rgba(255, 255, 255, 0.4);
    }
    
    .tag {
      font-size: 10px;
      padding: 1px 4px;
      border-radius: 2px;
      opacity: 0.7;
    }
  }
  
  .glow-effect {
    position: absolute;
    inset: 0;
    opacity: 0;
    transition: opacity 0.3s;
    z-index: 1;
  }
  

  

  &.general {
    background: linear-gradient(90deg, rgba(255, 71, 87, 0.1), rgba(46, 204, 113, 0.1));
    
    &.selected {
      background: linear-gradient(90deg, rgba(255, 71, 87, 0.2), rgba(46, 204, 113, 0.2));
      border-color: #00FF9A;
      
      .main-text { color: #fff; }
      .glow-effect { 
        background: radial-gradient(circle at center, rgba(255, 255, 255, 0.1), transparent);
        opacity: 1;
      }
    }
  }
  

  &.red {
    .tag { background: rgba(255, 71, 87, 0.2); color: #ff4757; }
    
    &.selected {
      border-color: #ff4757;
      background: rgba(255, 71, 87, 0.1);
      box-shadow: 0 0 12px rgba(255, 71, 87, 0.2);
      
      .main-text { color: #ff4757; text-shadow: 0 0 5px rgba(255, 71, 87, 0.4); }
      .tag { background: #ff4757; color: #fff; opacity: 1; }
      
      .glow-effect {
        background: radial-gradient(circle at center, rgba(255, 71, 87, 0.2), transparent);
        opacity: 1;
      }
    }
  }
  

  &.black {
    .tag { background: rgba(255, 255, 255, 0.1); color: #ccc; }
    
    &.selected {
      border-color: #a5b1c2;
      background: rgba(255, 255, 255, 0.1);
      box-shadow: 0 0 12px rgba(255, 255, 255, 0.1);
      
      .main-text { color: #fff; text-shadow: 0 0 5px rgba(255, 255, 255, 0.4); }
      .tag { background: #777; color: #fff; opacity: 1; }
      
      .glow-effect {
        background: radial-gradient(circle at center, rgba(255, 255, 255, 0.1), transparent);
        opacity: 1;
      }
    }
  }
  

  &.number {
    min-height: auto;
    aspect-ratio: 1;
    
    .main-text {
      font-size: 18px;
      font-family: 'DIN Alternate', sans-serif;
    }
  }
  
  &:active {
    transform: scale(0.96);
  }
}
</style>
