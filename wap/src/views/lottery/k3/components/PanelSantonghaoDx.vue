<template>
  <div class="betting-panel-sthdx">
    <div class="play-intro">
      对<span class="highlight">豹子</span>(111, 222, 333, 444, 555, 666)进行投注，选号与开奖号码一致即中奖。
    </div>
    <div class="grid-container">
      <div 
        v-for="item in bettingOptions"
        :key="item.name"
        class="grid-item"
        @click="toggleSelection(item)"
      >
        <div :class="['bet-btn', { 'selected': isSelected(item), 'disabled': !item.playid }]">
          <div class="btn-content">
            <span class="main-text number">{{ item.name }}</span>
            <span class="odds-text">赔率 {{ item.rate }}</span>
          </div>
          <div class="glow-effect"></div>
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

const playGroup = computed(() => {
  if (oddsData.value && oddsData.value.groups) {
    return oddsData.value.groups.find(g => 
      g.groupId === 'sth' || g.title === '三同号'
    );
  }
  return null;
});

const bettingOptions = computed(() => {
  const plays = playGroup.value?.plays || [];
  return plays
    .filter(p => {

       const hasTriple = /([1-6])\1\1/.test(p.name);
       const isNotTxOrDx = !/(通选|单选)$/.test(p.name);
       return hasTriple && isNotTxOrDx;
    })
    .map(p => {

      const match = p.name.match(/([1-6])\1\1/);
      const displayName = match ? match[0] : p.name;
      return { 
        playid: p.playId, 
        name: displayName, // 界面显示 111, 222 等
        originalName: p.name,
        rate: p.odds 
      };
    })
    .sort((a, b) => a.name.localeCompare(b.name)); // 按 111, 222... 顺序排列
});

const isSelected = (item) => {
  if (!item.playid) return false;
  return betStore.selections.some(s => s.items.some(i => i.playid === item.playid && i.name === item.name));
};

const toggleSelection = (item) => {
  if (!item.playid) return;
  betStore.toggleSelection(item, item.playid);
};
</script>

<style lang="less" scoped>
.betting-panel-sthdx {
  padding-bottom: 20px;
}

.play-intro {
  font-size: 12px;
  color: rgba(255, 255, 255, 0.5);
  margin-bottom: 16px;
  padding: 12px;
  background: rgba(255, 255, 255, 0.03);
  border-radius: 8px;
  border-left: 3px solid #00FF9A;
  line-height: 1.5;
  
  .highlight {
    color: #00FF9A;
    font-weight: bold;
    margin: 0 4px;
  }
}

.grid-container {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
}

.grid-item {
  aspect-ratio: 1.3;
}

.bet-btn {
  position: relative;
  width: 100%;
  height: 100%;
  background: #171E2E;
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.05);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  overflow: hidden;
  
  .btn-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4px;
    z-index: 2;
    
    .main-text {
      &.number {
        font-family: 'DIN Alternate', sans-serif;
        font-size: 24px; 
        font-weight: bold;
        color: rgba(255, 255, 255, 0.9);
        text-shadow: 0 2px 10px rgba(0,0,0,0.3);
      }
    }
    
    .odds-text {
      font-size: 10px;
      color: rgba(255, 255, 255, 0.4);
    }
  }
  
  .glow-effect {
    position: absolute;
    inset: 0;
    border-radius: 12px;
    opacity: 0;
    background: radial-gradient(circle at center, rgba(0, 255, 154, 0.1) 0%, transparent 70%);
    transition: opacity 0.3s;
    z-index: 1;
  }
  

  &.selected {
    background: #171E2E;
    border-color: #00FF9A;
    transform: scale(1.04);
    box-shadow: 
      0 0 0 1px rgba(0, 255, 154, 0.3),
      0 4px 12px rgba(0, 255, 154, 0.2);
    
    .main-text {
      color: #00FF9A;
      text-shadow: 0 0 8px rgba(0, 255, 154, 0.4);
    }
    
    .odds-text {
      color: rgba(255, 255, 255, 0.8);
    }
    
    .glow-effect {
      opacity: 1;
    }
  }
  
  &:active:not(.disabled) {
    transform: scale(0.98);
  }
  
  &.disabled {
    opacity: 0.4;
    cursor: not-allowed;
  }
}
</style>
