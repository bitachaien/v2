<template>
  <div class="betting-panel-ethdx">
    <div class="play-intro">
      选择1个对子和1个不同号码，开奖号码与所选一致即中奖。赔率：{{ getPlayByPair('11')?.odds || '--' }}
    </div>
    
    
    <div class="play-section">
      <div class="section-header">
        <span class="title-text">选择对子</span>
      </div>
      <div class="grid-container">
        <div 
          v-for="item in pairOptions" 
          :key="item.name" 
          class="grid-item"
          @click="togglePair(item)"
        >
        <div :class="['bet-btn', { 'selected': selectedPairs.includes(item.name) }]">
            <div class="btn-content">
              <span class="main-text number">{{ item.name }}</span>
            </div>
            <div class="glow-effect"></div>
          </div>
        </div>
      </div>
    </div>

    
    <div class="play-section">
      <div class="section-header">
        <span class="title-text">选择单号</span>
      </div>
      <div class="grid-container">
        <div 
          v-for="item in singleOptions" 
          :key="item.name" 
          class="grid-item"
          @click="toggleSingle(item)"
        >
          <div :class="['bet-btn', { 'selected': selectedSingles.includes(item.name), 'disabled': isDisabledSingle(item.name) }]">
            <div class="btn-content">
              <span class="main-text number">{{ item.name }}</span>
            </div>
            <div class="glow-effect"></div>
          </div>
        </div>
      </div>
    </div>

    
    <div v-if="selectedPairs.length > 0 || selectedSingles.length > 0" class="selection-info">
      <div v-if="selectedPairs.length > 0">对子：{{ selectedPairs.join(', ') }}</div>
      <div v-if="selectedSingles.length > 0">单号：{{ selectedSingles.join(', ') }}</div>
      <div v-if="validCombinations.length > 0" class="hint">
        有效组合 {{ validCombinations.length }} 注
      </div>
      <div v-else-if="selectedPairs.length > 0 && selectedSingles.length > 0" class="hint warning">
        无有效组合（单号不能与对子相同）
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

const getPlayByPair = (pairName) => {
  if (playGroup.value?.plays) {
    const playId = `k3eth${pairName}`;
    return playGroup.value.plays.find(p => p.playId === playId);
  }
  return null;
};

const pairNames = ['11', '22', '33', '44', '55', '66'];

const singleNames = ['1', '2', '3', '4', '5', '6'];

const pairOptions = computed(() => {
  return pairNames.map(name => ({ name, value: parseInt(name[0]) }));
});

const singleOptions = computed(() => {
  return singleNames.map(name => ({ name, value: parseInt(name) }));
});

const selectedPairs = ref([]);
const selectedSingles = ref([]);

const togglePair = (item) => {
  const index = selectedPairs.value.indexOf(item.name);
  if (index === -1) {
    selectedPairs.value.push(item.name);
  } else {
    selectedPairs.value.splice(index, 1);
  }

  const pairNums = selectedPairs.value.map(p => p[0]);
  selectedSingles.value = selectedSingles.value.filter(s => !pairNums.includes(s));
  syncToStore();
};

const isDisabledSingle = (singleName) => {

  if (selectedPairs.value.length === 0) return false;

  const pairNums = selectedPairs.value.map(p => p[0]);
  return pairNums.includes(singleName);
};

const toggleSingle = (item) => {
  if (isDisabledSingle(item.name)) return;
  const index = selectedSingles.value.indexOf(item.name);
  if (index === -1) {
    selectedSingles.value.push(item.name);
  } else {
    selectedSingles.value.splice(index, 1);
  }
  syncToStore();
};

const validCombinations = computed(() => {
  const result = [];
  for (const pair of selectedPairs.value) {
    const pairNum = pair[0]; // "11" -> "1"
    for (const single of selectedSingles.value) {
      if (single !== pairNum) {
        result.push(`${pairNum},${pairNum},${single}`);
      }
    }
  }
  return result;
});

const syncToStore = () => {

  pairNames.forEach(pair => {
    betStore.clearBet(`k3eth${pair}`);
  });
  

  for (const pair of selectedPairs.value) {
    const play = getPlayByPair(pair);
    if (!play) continue;
    
    const pairNum = pair[0];
    for (const single of selectedSingles.value) {
      if (single !== pairNum) {
        const combo = `${pairNum},${pairNum},${single}`;
        betStore.toggleSelection({
          playid: play.playId,
          name: combo,
          rate: play.odds
        }, play.playId);
      }
    }
  }
};

</script>

<style lang="less" scoped>
.betting-panel-ethdx {
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

.play-section {
  margin-bottom: 24px;
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
        font-size: 20px;
        font-weight: bold;
        color: rgba(255, 255, 255, 0.9);
        text-shadow: 0 2px 10px rgba(0,0,0,0.3);
      }
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
    
    .glow-effect {
      opacity: 1;
    }
  }

  &.disabled {
    opacity: 0.3;
    cursor: not-allowed;
    border-color: transparent;
    
    .main-text {
        color: rgba(255,255,255,0.3);
    }
  }
  
  &:active:not(.disabled) {
    transform: scale(0.98);
  }
}

.selection-info {
  margin-top: 16px;
  padding: 12px;
  background: rgba(0, 255, 154, 0.05);
  border-radius: 8px;
  font-size: 14px;
  color: #00FF9A;
  
  > div {
    margin-bottom: 4px;
    &:last-child {
      margin-bottom: 0;
    }
  }
  
  .hint {
    color: rgba(255, 255, 255, 0.5);
    font-size: 12px;
    margin-top: 8px;
    
    &.warning {
      color: #FF9A00;
    }
  }
}
</style>
