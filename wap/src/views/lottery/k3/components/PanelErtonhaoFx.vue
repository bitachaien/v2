<template>
  <div class="bet-panel-erthfx">
    <div class="play-intro">
      <span class="highlight">二同号通选</span>：所有对子号一键投注，开出任意对子即中奖。
    </div>
    <div class="grid-container">
      <div class="grid-item" @click="toggleSelection(bettingOption)">
        <div :class="['bet-btn', { 'selected': isSelected(bettingOption), 'disabled': !bettingOption.playid }]">
          <div class="btn-content">
            <span class="main-text">{{ bettingOption.name }}</span>
            <span class="odds-text">赔率 {{ bettingOption.rate }}</span>
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
    return oddsData.value.groups.find(g => g.groupId === 'eth');
  }
  return null;
});

const bettingOption = computed(() => {
  if (playGroup.value?.plays) {
    const play = playGroup.value.plays.find(p => p.playId === 'k3ethfx');
    if (play) {
      return { playid: play.playId, name: play.name, rate: play.odds };
    }
  }
  return { playid: '', name: '二同号通选', rate: '--' };
});

const isSelected = (item) => {
  if (!item.playid) return false;
  return betStore.selections.some(s => s.playId === 'k3ethfx' && s.items.some(i => i.playid === item.playid));
};

const toggleSelection = (item) => {
  if (!item.playid) return;
  betStore.toggleSelection(item, 'k3ethfx');
};
</script>

<style lang="less" scoped>
.bet-panel-erthfx {
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
}

.highlight {
  color: #00FF9A;
  font-weight: bold;
  margin-right: 4px;
}

.grid-container {
  padding: 0 20px;
}

.grid-item {
  width: 100%;
  height: 80px;
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
      font-size: 20px;
      font-weight: bold;
      color: rgba(255, 255, 255, 0.9);
      text-shadow: 0 2px 10px rgba(0,0,0,0.3);
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
