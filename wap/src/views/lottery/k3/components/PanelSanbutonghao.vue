<template>
  <div class="betting-panel-sbth">
    <div class="play-intro">
      <span class="highlight">三不同号</span>：从1-6中选择3个不同号码，所选号码与开奖号码一致即中奖。
    </div>
    
    
    <div v-if="selectedNumbers.length > 0" class="selected-display">
      <span class="label">已选：</span>
      <span class="numbers">{{ selectedNumbers.sort((a,b) => a-b).join(', ') }}</span>
      <span class="odds">赔率 {{ standardPlay?.odds || '--' }}</span>
    </div>
    
    <div class="grid-container">
      <div 
        v-for="num in [1, 2, 3, 4, 5, 6]"
        :key="num"
        class="grid-item"
        @click="toggleNumber(num)"
      >
        <div :class="['bet-btn', { 'selected': selectedNumbers.includes(num), 'disabled': !standardPlay }]">
          <div class="btn-content">
            <span class="main-text number">{{ num }}</span>
          </div>
          <div class="glow-effect"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, inject, computed, watch } from 'vue';
import { useK3BetStore } from '@/stores/k3Bet';
import { showToast } from 'vant';

const betStore = useK3BetStore();

const oddsData = inject('oddsData', ref(null));

const playGroup = computed(() => {
  if (oddsData.value && oddsData.value.groups) {
    return oddsData.value.groups.find(g => 
      g.groupId === 'sbth' || g.title?.includes('三不同号')
    );
  }
  return null;
});

const standardPlay = computed(() => {
  if (!playGroup.value?.plays) return null;

  return playGroup.value.plays.find(p => 
    p.playId === 'k3sbthbz' || 
    p.display === 'number-picker' ||
    p.name?.includes('标准')
  ) || playGroup.value.plays[0]; // 如果找不到就用第一个
});

const selectedNumbers = ref([]);

const toggleNumber = (num) => {
  if (!standardPlay.value) return;
  
  const idx = selectedNumbers.value.indexOf(num);
  if (idx > -1) {

    selectedNumbers.value.splice(idx, 1);
  } else if (selectedNumbers.value.length < 3) {

    selectedNumbers.value.push(num);
  } else {
    showToast('最多选择3个号码');
    return;
  }
  

  syncToStore();
};

const syncToStore = () => {

  if (standardPlay.value) {
    betStore.clearBet(standardPlay.value.playId);
  }
  

  if (selectedNumbers.value.length === 3 && standardPlay.value) {
    const content = selectedNumbers.value.sort((a, b) => a - b).join(',');
    betStore.toggleSelection({
      playid: standardPlay.value.playId,
      name: content,
      rate: standardPlay.value.odds
    }, standardPlay.value.playId);
  }
};

watch(() => betStore.selections, (newVal) => {
  if (!standardPlay.value) return;
  const current = newVal.find(s => s.playId === standardPlay.value.playId);
  if (!current || current.items.length === 0) {
    selectedNumbers.value = [];
  }
}, { deep: true });
</script>

<style lang="less" scoped>
.betting-panel-sbth {
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
    margin-right: 4px;
  }
}

.selected-display {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 16px;
  padding: 10px 12px;
  background: rgba(0, 255, 154, 0.1);
  border-radius: 8px;
  border: 1px solid rgba(0, 255, 154, 0.3);
  
  .label {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.6);
  }
  
  .numbers {
    font-size: 18px;
    font-weight: bold;
    color: #00FF9A;
  }
  
  .odds {
    margin-left: auto;
    font-size: 12px;
    color: rgba(255, 255, 255, 0.5);
  }
}

.grid-container {
  display: grid;
  grid-template-columns: repeat(3, 1fr); // 3 columns for better touch target size
  gap: 12px;
}

.grid-item {
  aspect-ratio: 1.2; // Slightly wider than square
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
        font-size: 32px; // Large number
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
