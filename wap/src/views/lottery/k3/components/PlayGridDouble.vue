<template>
  <div class="play-grid-double">
    <div 
      v-for="(group, index) in playGroups" 
      :key="group.title" 
      class="play-group"
      :class="{ 
        'is-sub-group': group.isSubGroup,
        'has-sub-group': playGroups[index + 1]?.isSubGroup 
      }"
    >
      <div class="group-header" v-if="!group.isSubGroup">
        <div class="header-decoration"></div>
        <h3 class="group-title">{{ group.title }}</h3>
        <div class="header-line"></div>
      </div>
      
      <van-grid :column-num="group.columns || 4" :gutter="8" clickable :border="false">
        <van-grid-item 
          v-for="item in group.items" 
          :key="item.playId"
          class="cyber-grid-item"
          :class="{ 'is-selected': isSelected(item.playId) }"
          @click="toggleSelection(item.playId)"
        >
          <div class="bet-item">
            <div class="item-content" v-safe-html="item.display"></div>
            <span class="item-rate">{{ item.rate }}</span>
            
            
            <div class="selection-glow" v-if="isSelected(item.playId)"></div>
            <div class="corner-accent" v-if="isSelected(item.playId)"></div>
          </div>
        </van-grid-item>
      </van-grid>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import { Grid as VanGrid, GridItem as VanGridItem } from 'vant';
import { sanitizeHtml } from '@/utils/sanitize';
import { k3Api } from '@/api';

const route = useRoute();
const selectedItems = ref(new Set());
const playGroups = ref([]);

const defaultPlayGroups = [
  {
    title: '三军/大小',
    columns: 4,
    items: [
      { playId: 'sjdx1', display: '<i class="dice dice-1"></i>', rate: '1.98' },
      { playId: 'sjdx2', display: '<i class="dice dice-2"></i>', rate: '1.98' },
      { playId: 'sjdx3', display: '<i class="dice dice-3"></i>', rate: '1.98' },
      { playId: 'sjdx4', display: '<i class="dice dice-4"></i>', rate: '1.98' },
      { playId: 'sjdx5', display: '<i class="dice dice-5"></i>', rate: '1.98' },
      { playId: 'sjdx6', display: '<i class="dice dice-6"></i>', rate: '1.98' },
      { playId: 'sjdxd', display: '大', rate: '1.98' },
      { playId: 'sjdxs', display: '小', rate: '1.98' },
    ]
  },
  {
    title: '围骰/全骰',
    columns: 3,
    items: [
      { playId: 'wsqs111', display: '<i class="dice dice-1"></i><i class="dice dice-1"></i><i class="dice dice-1"></i>', rate: '180' },
      { playId: 'wsqs222', display: '<i class="dice dice-2"></i><i class="dice dice-2"></i><i class="dice dice-2"></i>', rate: '180' },
      { playId: 'wsqs333', display: '<i class="dice dice-3"></i><i class="dice dice-3"></i><i class="dice dice-3"></i>', rate: '180' },
      { playId: 'wsqs444', display: '<i class="dice dice-4"></i><i class="dice dice-4"></i><i class="dice dice-4"></i>', rate: '180' },
      { playId: 'wsqs555', display: '<i class="dice dice-5"></i><i class="dice dice-5"></i><i class="dice dice-5"></i>', rate: '180' },
      { playId: 'wsqs666', display: '<i class="dice dice-6"></i><i class="dice dice-6"></i><i class="dice dice-6"></i>', rate: '180' },
    ]
  },
  {
    title: '全骰',
    columns: 1,
    isSubGroup: true,
    items: [
      { playId: 'wsqsqqq', display: '全骰', rate: '30' },
    ]
  },
  {
    title: '长牌',
    columns: 5,
    items: [
      { playId: 'cp12', display: '<i class="dice dice-1"></i><i class="dice dice-2"></i>', rate: '8' },
      { playId: 'cp13', display: '<i class="dice dice-1"></i><i class="dice dice-3"></i>', rate: '8' },
      { playId: 'cp14', display: '<i class="dice dice-1"></i><i class="dice dice-4"></i>', rate: '8' },
      { playId: 'cp15', display: '<i class="dice dice-1"></i><i class="dice dice-5"></i>', rate: '8' },
      { playId: 'cp16', display: '<i class="dice dice-1"></i><i class="dice dice-6"></i>', rate: '8' },
      { playId: 'cp23', display: '<i class="dice dice-2"></i><i class="dice dice-3"></i>', rate: '8' },
      { playId: 'cp24', display: '<i class="dice dice-2"></i><i class="dice dice-4"></i>', rate: '8' },
      { playId: 'cp25', display: '<i class="dice dice-2"></i><i class="dice dice-5"></i>', rate: '8' },
      { playId: 'cp26', display: '<i class="dice dice-2"></i><i class="dice dice-6"></i>', rate: '8' },
      { playId: 'cp34', display: '<i class="dice dice-3"></i><i class="dice dice-4"></i>', rate: '8' },
      { playId: 'cp35', display: '<i class="dice dice-3"></i><i class="dice dice-5"></i>', rate: '8' },
      { playId: 'cp36', display: '<i class="dice dice-3"></i><i class="dice dice-6"></i>', rate: '8' },
      { playId: 'cp45', display: '<i class="dice dice-4"></i><i class="dice dice-5"></i>', rate: '8' },
      { playId: 'cp46', display: '<i class="dice dice-4"></i><i class="dice dice-6"></i>', rate: '8' },
      { playId: 'cp56', display: '<i class="dice dice-5"></i><i class="dice dice-6"></i>', rate: '8' },
    ]
  },
  {
    title: '短牌',
    columns: 3,
    items: [
      { playId: 'dp11', display: '<i class="dice dice-1"></i><i class="dice dice-1"></i>', rate: '15' },
      { playId: 'dp22', display: '<i class="dice dice-2"></i><i class="dice dice-2"></i>', rate: '15' },
      { playId: 'dp33', display: '<i class="dice dice-3"></i><i class="dice dice-3"></i>', rate: '15' },
      { playId: 'dp44', display: '<i class="dice dice-4"></i><i class="dice dice-4"></i>', rate: '15' },
      { playId: 'dp55', display: '<i class="dice dice-5"></i><i class="dice dice-5"></i>', rate: '15' },
      { playId: 'dp66', display: '<i class="dice dice-6"></i><i class="dice dice-6"></i>', rate: '15' },
    ]
  },
  {
    title: '点数',
    columns: 5,
    items: [
      { playId: 'ds4', display: '4点', rate: '60' },
      { playId: 'ds5', display: '5点', rate: '30' },
      { playId: 'ds6', display: '6点', rate: '18' },
      { playId: 'ds7', display: '7点', rate: '12' },
      { playId: 'ds8', display: '8点', rate: '8' },
      { playId: 'ds9', display: '9点', rate: '6' },
      { playId: 'ds10', display: '10点', rate: '6' },
      { playId: 'ds11', display: '11点', rate: '6' },
      { playId: 'ds12', display: '12点', rate: '6' },
      { playId: 'ds13', display: '13点', rate: '8' },
      { playId: 'ds14', display: '14点', rate: '12' },
      { playId: 'ds15', display: '15点', rate: '18' },
      { playId: 'ds16', display: '16点', rate: '30' },
      { playId: 'ds17', display: '17点', rate: '60' },
    ]
  },
];

playGroups.value = defaultPlayGroups;

const isSelected = (playId) => selectedItems.value.has(playId);

const toggleSelection = (playId) => {
  if (selectedItems.value.has(playId)) {
    selectedItems.value.delete(playId);
  } else {
    selectedItems.value.add(playId);
  }
};

const generateDiceDisplay = (name, displayType) => {

  if (displayType === 'dice' || displayType === 'number') {
    const num = parseInt(name);
    if (num >= 1 && num <= 6) {
      return `<i class="dice dice-${num}"></i>`;
    }
  }
  if (displayType === 'triple') {
    const num = name.charAt(0);
    return `<i class="dice dice-${num}"></i><i class="dice dice-${num}"></i><i class="dice dice-${num}"></i>`;
  }
  if (displayType === 'pair' || displayType === 'double-dice') {

    const nums = name.split('');
    return nums.map(n => `<i class="dice dice-${n}"></i>`).join('');
  }
  if (displayType === 'double') {

    const num = name.charAt(0);
    return `<i class="dice dice-${num}"></i><i class="dice dice-${num}"></i>`;
  }
  

  if (/^[1-6]$/.test(name)) {

    return `<i class="dice dice-${name}"></i>`;
  }
  if (/^([1-6])\1\1$/.test(name)) {

    const num = name.charAt(0);
    return `<i class="dice dice-${num}"></i><i class="dice dice-${num}"></i><i class="dice dice-${num}"></i>`;
  }
  if (/^([1-6])\1$/.test(name)) {

    const num = name.charAt(0);
    return `<i class="dice dice-${num}"></i><i class="dice dice-${num}"></i>`;
  }
  if (/^[1-6]{2}$/.test(name)) {

    return name.split('').map(n => `<i class="dice dice-${n}"></i>`).join('');
  }
  

  return name;
};

const fetchOdds = async () => {
  const code = route.params.code || 'jsk3';
  try {
    const res = await k3Api.getOdds(code);
    if (res.code === 0 && res.data && res.data.groups) {

      playGroups.value = res.data.groups.map(group => ({
        title: group.title,
        columns: group.columns || 4,
        isSubGroup: group.isSubGroup || false,
        items: group.plays.map(play => ({
          playId: play.playId,
          display: generateDiceDisplay(play.name, play.display),
          rate: play.odds,
          name: play.name
        }))
      }));
    }
  } catch (error) {
    console.error('获取玩法赔率失败:', error);

  }
};

const getSelectedItems = () => {
  const items = [];
  playGroups.value.forEach(group => {
    group.items.forEach(item => {
      if (selectedItems.value.has(item.playId)) {
        items.push(item);
      }
    });
  });
  return items;
};

const clearSelection = () => {
  selectedItems.value.clear();
};

defineExpose({
  getSelectedItems,
  clearSelection
});

watch(() => route.params.code, () => {
  fetchOdds();
});

onMounted(() => {
  fetchOdds();
});
</script>

<style lang="less" scoped>
@import "@/assets/theme.less";

.play-grid-double {
  padding: 0 4px;

  .play-group {
    margin-bottom: 24px;

    &.has-sub-group {
      margin-bottom: 8px;
    }
    
    &.is-sub-group {
      
      margin-top: 0;
    }
  }

  .group-header {
    display: flex;
    align-items: center;
    margin-bottom: 12px;
    padding: 0 4px;
    
    .header-decoration {
      width: 4px;
      height: 16px;
      background: #00FF9A;
      margin-right: 8px;
      border-radius: 2px;
      box-shadow: 0 0 8px rgba(0, 255, 154, 0.6);
    }
    
    .group-title {
      font-size: 16px;
      font-weight: bold;
      color: #fff;
      margin: 0;
      letter-spacing: 1px;
      text-shadow: 0 0 10px rgba(255, 255, 255, 0.2);
    }
    
    .header-line {
      flex: 1;
      height: 1px;
      background: linear-gradient(90deg, rgba(255, 255, 255, 0.1), transparent);
      margin-left: 12px;
    }
  }

  .cyber-grid-item {
    height: 100%;
    
    :deep(.van-grid-item__content) {
      background-color: rgba(23, 30, 46, 0.6);
      border-radius: 8px;
      padding: 0; 
      border: 1px solid rgba(255, 255, 255, 0.05);
      transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
      backdrop-filter: blur(4px);
      height: 100%;
      overflow: hidden;
    }
    
    &.is-selected :deep(.van-grid-item__content) {
      background-color: rgba(0, 255, 154, 0.08);
      border-color: #00FF9A;
      box-shadow: 0 0 12px rgba(0, 255, 154, 0.2);
      transform: translateY(-2px);
    }
  }

  .bet-item {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    width: 100%;
    height: 100%;
    padding: 12px 4px;
    min-height: 70px;
  }

  .item-content {
    font-size: 18px;
    font-weight: bold;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    margin-bottom: 4px;
    z-index: 2;
    
    
    transition: color 0.3s;
  }

  .item-rate {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.5);
    font-family: monospace;
    z-index: 2;
    transition: color 0.3s;
  }
  
  
  .is-selected {
    .item-content {
      color: #00FF9A;
      text-shadow: 0 0 5px rgba(0, 255, 154, 0.5);
    }
    
    .item-rate {
      color: #fff;
    }
  }
  
  
  .selection-glow {
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at center, rgba(0, 255, 154, 0.15), transparent 70%);
    z-index: 1;
    animation: pulse 2s infinite;
  }
  
  .corner-accent {
    position: absolute;
    top: 0;
    right: 0;
    width: 0;
    height: 0;
    border-style: solid;
    border-width: 0 16px 16px 0;
    border-color: transparent #00FF9A transparent transparent;
    opacity: 0.8;
    z-index: 2;
  }

  
  :deep(.dice) {
    width: 22px;
    height: 22px;
    border-radius: 4px;
    background-color: #fff;
    box-shadow: inset 0 0 2px rgba(0,0,0,0.1), 0 2px 4px rgba(0,0,0,0.2);
    display: inline-block;
    margin: 0 2px;
    vertical-align: middle;
    position: relative;
  }

  :deep(.dice-1) {
    background-image: radial-gradient(circle at 50% 50%, #ff4757 24%, rgba(0,0,0,0) 25%);
  }

  :deep(.dice-2) {
    background-image: 
      radial-gradient(circle at 30% 30%, #333 18%, rgba(0,0,0,0) 19%),
      radial-gradient(circle at 70% 70%, #333 18%, rgba(0,0,0,0) 19%);
  }

  :deep(.dice-3) {
    background-image: 
      radial-gradient(circle at 25% 25%, #333 18%, rgba(0,0,0,0) 19%),
      radial-gradient(circle at 50% 50%, #333 18%, rgba(0,0,0,0) 19%),
      radial-gradient(circle at 75% 75%, #333 18%, rgba(0,0,0,0) 19%);
  }

  :deep(.dice-4) {
    background-image: 
      radial-gradient(circle at 28% 28%, #ff4757 18%, rgba(0,0,0,0) 19%),
      radial-gradient(circle at 72% 28%, #ff4757 18%, rgba(0,0,0,0) 19%),
      radial-gradient(circle at 28% 72%, #ff4757 18%, rgba(0,0,0,0) 19%),
      radial-gradient(circle at 72% 72%, #ff4757 18%, rgba(0,0,0,0) 19%);
  }

  :deep(.dice-5) {
    background-image: 
      radial-gradient(circle at 25% 25%, #333 18%, rgba(0,0,0,0) 19%),
      radial-gradient(circle at 75% 25%, #333 18%, rgba(0,0,0,0) 19%),
      radial-gradient(circle at 50% 50%, #333 18%, rgba(0,0,0,0) 19%),
      radial-gradient(circle at 25% 75%, #333 18%, rgba(0,0,0,0) 19%),
      radial-gradient(circle at 75% 75%, #333 18%, rgba(0,0,0,0) 19%);
  }

  :deep(.dice-6) {
    background-image: 
      radial-gradient(circle at 28% 25%, #333 16%, rgba(0,0,0,0) 17%),
      radial-gradient(circle at 28% 50%, #333 16%, rgba(0,0,0,0) 17%),
      radial-gradient(circle at 28% 75%, #333 16%, rgba(0,0,0,0) 17%),
      radial-gradient(circle at 72% 25%, #333 16%, rgba(0,0,0,0) 17%),
      radial-gradient(circle at 72% 50%, #333 16%, rgba(0,0,0,0) 17%),
      radial-gradient(circle at 72% 75%, #333 16%, rgba(0,0,0,0) 17%);
  }
}

@keyframes pulse {
  0% { opacity: 0.5; }
  50% { opacity: 0.8; }
  100% { opacity: 0.5; }
}
</style>
