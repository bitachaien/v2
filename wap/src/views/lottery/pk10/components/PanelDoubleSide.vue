<template>
  <div class="panel-double-side">
    <div v-for="(group, gIndex) in currentConfig" :key="gIndex" class="bet-group">
      <div class="group-title">{{ group.title }}</div>
      <div class="group-items" :class="{ 'grid-5': isNumberGroup(group) }">
        <div
          v-for="(item, iIndex) in group.items"
          :key="iIndex"
          class="bet-item"
          :class="{ active: isSelected(item) }"
          @click="toggleItem(item)"
        >
          <div class="item-label" :class="getClass(item.label)">{{ item.label }}</div>
          <div class="item-odds">{{ item.odds }}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { usePk10BetStore } from '@/stores/pk10Bet';

const props = defineProps({
  activeTab: {
    type: String,
    required: true
  }
});

const betStore = usePk10BetStore();

const ODDS_BSOE = 1.956; // Big/Small/Odd/Even
const ODDS_DT = 1.956;   // Dragon/Tiger
const ODDS_NUM = 9.8;    // Specific Number 1-10
const ODDS_SUM_BSOE = 1.956; // Sum Big/Small...

const generateNumItems = (prefix) => {
  return Array.from({ length: 10 }, (_, i) => ({
    id: `${prefix}_${i + 1}`,
    label: (i + 1).toString(),
    odds: ODDS_NUM,
    type: 'number'
  }));
};

const generateDoubleSideItems = (prefix, hasDragonTiger = false) => {
  const items = [
    { id: `${prefix}_da`, label: '大', odds: ODDS_BSOE },
    { id: `${prefix}_xiao`, label: '小', odds: ODDS_BSOE },
    { id: `${prefix}_dan`, label: '单', odds: ODDS_BSOE },
    { id: `${prefix}_shuang`, label: '双', odds: ODDS_BSOE },
  ];
  if (hasDragonTiger) {
    items.push(
      { id: `${prefix}_long`, label: '龙', odds: ODDS_DT },
      { id: `${prefix}_hu`, label: '虎', odds: ODDS_DT }
    );
  }
  return items;
};

const generateSumItems = () => {
  const items = [
    { id: 'gy_da', label: '和大', odds: ODDS_SUM_BSOE },
    { id: 'gy_xiao', label: '和小', odds: ODDS_SUM_BSOE },
    { id: 'gy_dan', label: '和单', odds: ODDS_SUM_BSOE },
    { id: 'gy_shuang', label: '和双', odds: ODDS_SUM_BSOE },
  ];
  return items;
};

const CONFIG = {
  'shuangmian': [
    { title: '冠、亚军和', items: generateSumItems() },
    { title: '冠军', items: generateDoubleSideItems('1', true) },
    { title: '亚军', items: generateDoubleSideItems('2', true) },
    { title: '第三名', items: generateDoubleSideItems('3', true) },
    { title: '第四名', items: generateDoubleSideItems('4', true) },
    { title: '第五名', items: generateDoubleSideItems('5', true) },
    { title: '第六名', items: generateDoubleSideItems('6', false) },
    { title: '第七名', items: generateDoubleSideItems('7', false) },
    { title: '第八名', items: generateDoubleSideItems('8', false) },
    { title: '第九名', items: generateDoubleSideItems('9', false) },
    { title: '第十名', items: generateDoubleSideItems('10', false) },
  ],
  'guanya': [

    { 
      title: '冠亚和值 (3-19)', 
      items: Array.from({length: 17}, (_, i) => ({
          id: `gy_${i+3}`,
          label: (i+3).toString(),
          odds: [3,4,18,19].includes(i+3) ? 42 : ([5,6,16,17].includes(i+3) ? 21 : 10.5) // Simulated odds
      }))
    }
  ],
  '1-5': [
    { title: '冠军', items: generateNumItems('1') },
    { title: '亚军', items: generateNumItems('2') },
    { title: '第三名', items: generateNumItems('3') },
    { title: '第四名', items: generateNumItems('4') },
    { title: '第五名', items: generateNumItems('5') },
  ],
  '6-10': [
    { title: '第六名', items: generateNumItems('6') },
    { title: '第七名', items: generateNumItems('7') },
    { title: '第八名', items: generateNumItems('8') },
    { title: '第九名', items: generateNumItems('9') },
    { title: '第十名', items: generateNumItems('10') },
  ]
};

const currentConfig = computed(() => CONFIG[props.activeTab] || []);

const isSelected = (item) => {
  return betStore.isSelected(item.id, props.activeTab);
};

const toggleItem = (item) => {
  betStore.toggleSelection(item, props.activeTab);
};

const isNumberGroup = (group) => {
    if (!group.items || group.items.length === 0) return false;

    return group.items[0].type === 'number' || /^\d+$/.test(group.items[0].label);
};

const getClass = (label) => {
    if (['大','双','龙','和大','和双'].includes(label)) return 'text-red';
    if (['小','单','虎','和小','和单'].includes(label)) return 'text-blue';

    return 'text-normal';
};
</script>

<style lang="less" scoped>
@import "@/assets/theme.less";

.panel-double-side {
  padding-bottom: 60px;
}

.bet-group {
  margin-bottom: 12px;
  background: #fff;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 4px rgba(0,0,0,0.05);
}

.group-title {
  padding: 8px 12px;
  background: #f7f8fa;
  font-weight: bold;
  font-size: 14px;
  border-bottom: 1px solid #eee;
}

.group-items {
  display: grid;
  grid-template-columns: repeat(4, 1fr); // Default 4 cols
  gap: 1px;
  background: #eee; // Border effect
  
  &.grid-5 {
      grid-template-columns: repeat(5, 1fr); // 5 cols for numbers
  }
}

.bet-item {
  background: #fff;
  padding: 12px 4px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;

  &.active {
    background: #fff5f5;
    box-shadow: inset 0 0 0 1px @primary-color;
    .item-label {
        color: @primary-color !important;
        font-weight: bold;
    }
    .item-odds {
        color: @primary-color;
    }
  }
}

.item-label {
  font-size: 16px;
  margin-bottom: 4px;
  
  &.text-red { color: #ff4d4f; }
  &.text-blue { color: #1890ff; }
  &.text-normal { 
      font-weight: bold;
      width: 24px; 
      height: 24px; 
      line-height: 24px; 
      text-align: center;
      border-radius: 50%;
      background: #f2f3f5;
      font-size: 14px;
  }
}

.active .item-label.text-normal {
    background: @primary-color;
    color: #fff !important;
}

.item-odds {
  font-size: 12px;
  color: #999;
}
</style>
