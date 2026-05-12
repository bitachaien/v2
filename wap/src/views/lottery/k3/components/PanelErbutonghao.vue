<template>
  <div class="betting-panel-ebth">
    <div class="play-intro">
      选择2个不同号码，开奖号码中包含所选的2个号码即中奖。赔率：{{ standardPlay?.odds || '--' }}
    </div>
    <div class="grid-container">
      <div 
        v-for="num in numberOptions"
        :key="num"
        class="grid-item"
        @click="toggleNumber(num)"
      >
        <div :class="['bet-btn', { 'selected': selectedNumbers.includes(num) }]">
          <div class="btn-content">
            <span class="main-text number">{{ num }}</span>
          </div>
          <div class="glow-effect"></div>
        </div>
      </div>
    </div>
    <div v-if="selectedNumbers.length > 0" class="selection-info">
      已选：{{ selectedNumbers.sort((a,b) => a-b).join(', ') }}
      <span v-if="selectedNumbers.length >= 2" class="hint">
        （共 {{ combinationCount }} 注）
      </span>
      <span v-else class="hint warning">（请再选 {{ 2 - selectedNumbers.length }} 个）</span>
    </div>
  </div>
</template>

<script setup>
import { ref, inject, computed, watch } from 'vue';
import { useK3BetStore } from '@/stores/k3Bet';

const betStore = useK3BetStore();

const oddsData = inject('oddsData', ref(null));

const playGroup = computed(() => {
  if (oddsData.value && oddsData.value.groups) {
    return oddsData.value.groups.find(g => g.groupId === 'ebth');
  }
  return null;
});

const standardPlay = computed(() => {
  if (!playGroup.value?.plays) return null;
  return playGroup.value.plays.find(p => 
    p.playId === 'k3ebthbz' || 
    p.display === 'number-picker' ||
    p.name?.includes('标准')
  ) || playGroup.value.plays[0];
});

const selectedNumbers = ref([]);

const numberOptions = [1, 2, 3, 4, 5, 6];

const toggleNumber = (num) => {
  const index = selectedNumbers.value.indexOf(num);
  if (index === -1) {
    selectedNumbers.value.push(num);
  } else {
    selectedNumbers.value.splice(index, 1);
  }
  syncToStore();
};

const combinationCount = computed(() => {
  const n = selectedNumbers.value.length;
  if (n < 2) return 0;
  return (n * (n - 1)) / 2;
});

const generateCombinations = (nums) => {
  const result = [];
  const sorted = [...nums].sort((a, b) => a - b);
  for (let i = 0; i < sorted.length; i++) {
    for (let j = i + 1; j < sorted.length; j++) {
      result.push(`${sorted[i]},${sorted[j]}`);
    }
  }
  return result;
};

const syncToStore = () => {
  if (!standardPlay.value) return;
  
  const playId = standardPlay.value.playId;
  

  betStore.clearBet(playId);
  

  if (selectedNumbers.value.length >= 2) {
    const combinations = generateCombinations(selectedNumbers.value);
    combinations.forEach(combo => {
      const item = {
        playid: playId,
        name: combo,
        rate: standardPlay.value.odds
      };
      betStore.toggleSelection(item, playId);
    });
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
.betting-panel-ebth {
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

.grid-container {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
}

.grid-item {
  aspect-ratio: 1.2;
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
        font-size: 32px;
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
  
  &:active:not(.disabled) {
    transform: scale(0.98);
  }
  
  &.disabled {
    opacity: 0.4;
    cursor: not-allowed;
  }
}

.selection-info {
  margin-top: 16px;
  padding: 12px;
  background: rgba(0, 255, 154, 0.05);
  border-radius: 8px;
  font-size: 14px;
  color: #00FF9A;
  
  .hint {
    color: rgba(255, 255, 255, 0.5);
    font-size: 12px;
    
    &.warning {
      color: #FF9A00;
    }
  }
}
</style>
