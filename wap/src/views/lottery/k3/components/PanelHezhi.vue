<template>
  <div class="betting-panel-hezhi">
    
    <div v-if="isLoading" class="loading-state">
      <van-loading type="spinner" color="#00FF9A" />
      <span>加载玩法中...</span>
    </div>
    
    <template v-else>
    
    <div class="section-chips">
      <div class="chip-grid">
        <div 
          v-for="item in attributeOptions" 
          :key="item.name" 
          class="chip-item"
          @click="toggleSelection(item)"
        >
          <div :class="['bet-chip', { 'selected': isSelected(item), 'disabled': !item.playid }]">
            <span class="chip-text">{{ item.name }}</span>
            <div class="glow-effect"></div>
          </div>
        </div>
      </div>
    </div>

    
    <div class="section-points">
      <div class="point-grid">
        <div 
          v-for="item in pointOptions" 
          :key="item.name" 
          class="point-item"
          @click="toggleSelection(item)"
        >
          <div :class="['bet-card', { 'selected': isSelected(item), 'disabled': !item.playid }]">
            <div class="card-content">
              <span class="main-number">{{ item.name }}</span>
              <span class="sub-odds">{{ item.odds }}</span>
            </div>
            <div class="glow-effect"></div>
          </div>
        </div>
      </div>
    </div>
    </template>
  </div>
</template>

<script setup>
import { ref, inject, computed } from 'vue';
import { Loading as VanLoading } from 'vant';
import { useK3BetStore } from '@/stores/k3Bet';

const betStore = useK3BetStore();

const oddsData = inject('oddsData', ref(null));

const isLoading = computed(() => !oddsData.value);

const hezhiGroup = computed(() => {
  if (oddsData.value && oddsData.value.groups) {
    return oddsData.value.groups.find(g => 
      g.groupId === 'hz' || g.groupId === 'hezhi' || g.title?.includes('和值')
    );
  }
  return null;
});

const attributeOptions = computed(() => {
  if (hezhiGroup.value && hezhiGroup.value.plays) {
    return hezhiGroup.value.plays
      .filter(p => !/^和值\d+$/.test(p.name))
      .map(p => ({ playid: p.playId, name: p.name, odds: p.odds }));
  }
  return [];
});

const pointOptions = computed(() => {
  if (hezhiGroup.value && hezhiGroup.value.plays) {
    return hezhiGroup.value.plays
      .filter(p => /^和值\d+$/.test(p.name))
      .map(p => ({ playid: p.playId, name: p.name, odds: p.odds }));
  }
  return [];
});

const toggleSelection = (item) => {
  if (!item.playid) return;
  betStore.toggleSelection(item, item.playid);
};

const isSelected = (item) => {
  if (!item.playid) return false;
  return betStore.selections.some(s => s.items.some(i => i.playid === item.playid && i.name === item.name));
};
</script>

<style lang="less" scoped>
.betting-panel-hezhi {
  padding-top: 16px;
  padding-bottom: 20px;
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  color: rgba(255, 255, 255, 0.5);
  font-size: 14px;
  gap: 12px;
}

.section-chips {
  margin-bottom: 16px;
}

.chip-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 10px;
}

.bet-chip {
  position: relative;
  height: 40px;
  background: rgba(23, 30, 46, 0.6);
  border-radius: 8px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
  overflow: hidden;
  backdrop-filter: blur(4px);
  
  .chip-text {
    font-size: 14px;
    color: rgba(255, 255, 255, 0.7);
    font-weight: 600;
    z-index: 2;
    transition: color 0.3s;
  }
  
  .glow-effect {
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(0, 255, 154, 0.2), rgba(0, 255, 154, 0));
    opacity: 0;
    transition: opacity 0.3s;
    z-index: 1;
  }
  
  
  &.selected {
    border-color: #00FF9A;
    background: rgba(0, 255, 154, 0.1);
    box-shadow: 0 0 12px rgba(0, 255, 154, 0.25);
    transform: translateY(-1px);
    
    .chip-text {
      color: #00FF9A;
      text-shadow: 0 0 5px rgba(0, 255, 154, 0.5);
    }
    
    .glow-effect {
      opacity: 1;
    }
  }
  
  &:active:not(.disabled) {
    transform: scale(0.96);
  }
  
  &.disabled {
    opacity: 0.4;
    cursor: not-allowed;
  }
}

.point-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 10px;
}

.bet-card {
  position: relative;
  aspect-ratio: 1;
  background: rgba(23, 30, 46, 0.6);
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
  overflow: hidden;
  backdrop-filter: blur(4px);
  
  .card-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    z-index: 2;
    
    .main-number {
      font-family: 'DIN Alternate', 'Roboto', sans-serif;
      font-size: 22px;
      font-weight: bold;
      color: #fff;
      text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
      transition: all 0.3s;
    }
    
    .sub-odds {
      font-size: 12px;
      color: rgba(255, 255, 255, 0.4);
      font-family: monospace;
      transition: color 0.3s;
    }
  }
  
  .glow-effect {
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 50% 0%, rgba(0, 255, 154, 0.15), transparent 70%);
    opacity: 0;
    transition: opacity 0.3s;
    z-index: 1;
  }
  
  
  &.selected {
    border-color: #00FF9A;
    background: rgba(0, 255, 154, 0.08);
    transform: translateY(-2px);
    box-shadow: 
      0 4px 12px rgba(0, 0, 0, 0.2),
      0 0 0 1px rgba(0, 255, 154, 0.3),
      inset 0 0 12px rgba(0, 255, 154, 0.1);
    
    .main-number {
      color: #00FF9A;
      text-shadow: 0 0 8px rgba(0, 255, 154, 0.6);
      transform: scale(1.1);
    }
    
    .sub-odds {
      color: rgba(255, 255, 255, 0.9);
    }
    
    .glow-effect {
      opacity: 1;
    }
  }
  
  &:active:not(.disabled) {
    transform: scale(0.96);
  }
  
  &.disabled {
    opacity: 0.4;
    cursor: not-allowed;
  }
}
</style>
