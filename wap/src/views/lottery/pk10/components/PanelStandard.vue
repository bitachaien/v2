<template>
  <div class="panel-standard">
    
    <van-tabs v-model:active="mainPlay" type="card" class="main-play-tabs" color="#ee0a24">
      <van-tab title="排名复式" name="rank_compound"></van-tab>
      <van-tab title="单式录入" name="single_input"></van-tab>
      
    </van-tabs>

    
    <van-tabs 
      v-if="mainPlay === 'rank_compound'" 
      v-model:active="activeSubPlay" 
      shrink 
      animated 
      swipeable
      class="sub-play-tabs"
    >
      <van-tab title="定位胆" name="dwd"></van-tab>
      <van-tab title="猜前五" name="qian5"></van-tab>
      <van-tab title="猜前四" name="qian4"></van-tab>
      <van-tab title="猜前三" name="qian3"></van-tab>
      <van-tab title="猜前二" name="qian2"></van-tab>
      <van-tab title="猜冠军" name="guanjun"></van-tab>
      <van-tab title="龙虎" name="longhu"></van-tab>
      <van-tab title="大小单双" name="dxds"></van-tab>
      <van-tab title="冠亚和值" name="gyhz"></van-tab>
    </van-tabs>

    <van-tabs 
      v-if="mainPlay === 'single_input'" 
      v-model:active="activeSinglePlay" 
      shrink 
      animated 
      class="sub-play-tabs"
    >
      <van-tab title="前五单式" name="single_qian5"></van-tab>
      <van-tab title="前四单式" name="single_qian4"></van-tab>
      <van-tab title="前三单式" name="single_qian3"></van-tab>
      <van-tab title="前二单式" name="single_qian2"></van-tab>
    </van-tabs>

    <div class="standard-bet-container">
      
      
      <template v-if="mainPlay === 'rank_compound'">
        
        
        <div v-if="activeSubPlay === 'gyhz'" class="special-play-container">
           <div class="special-group-title">冠亚和值</div>
           <div class="special-items-grid">
              <div 
                v-for="n in 17" 
                :key="n" 
                class="special-item"
                :class="{ active: isSelected('gyhz', n+2) }" 
                @click="toggleBall('gyhz', n+2)"
              >
                 <div class="label">{{ n+2 }}</div>
                 <div class="odds">12.5</div>
              </div>
           </div>
        </div>

        <div v-else-if="activeSubPlay === 'longhu' || activeSubPlay === 'dxds'" class="special-play-container">
           <div v-for="(row, rIndex) in currentRows" :key="rIndex" class="rank-row special-row">
              <div class="rank-header">
                 <div class="rank-title">{{ row.title }}</div>
              </div>
              <div class="special-items-row">
                 <div 
                   v-for="opt in getOptionsForPlay(activeSubPlay, row.id)" 
                   :key="opt.val"
                   class="special-item text-item"
                   :class="[{ active: isSelected(row.id, opt.val) }, opt.colorClass]"
                   @click="toggleBall(row.id, opt.val)"
                 >
                    {{ opt.label }}
                 </div>
              </div>
           </div>
        </div>

        
        <div v-else v-for="(rank, rIndex) in currentRows" :key="rank.id" class="rank-row" :class="{ 'z-active': activeQuickMenu === rank.id }">
          
          <div class="rank-header">
            <div class="rank-title">{{ rank.title }}</div>
            <div class="quick-select-trigger" @click.stop="toggleQuickMenu(rank.id)">
               快选 <van-icon name="arrow-down" />
               <transition name="fade">
                 <div v-if="activeQuickMenu === rank.id" class="quick-menu">
                   <div class="quick-item" @click.stop="handleQuickSelect(rank.id, 'clear')">清</div>
                   <div class="quick-item" @click.stop="handleQuickSelect(rank.id, 'all')">全</div>
                   <div class="quick-item" @click.stop="handleQuickSelect(rank.id, 'big')">大</div>
                   <div class="quick-item" @click.stop="handleQuickSelect(rank.id, 'small')">小</div>
                   <div class="quick-item" @click.stop="handleQuickSelect(rank.id, 'odd')">奇</div>
                   <div class="quick-item" @click.stop="handleQuickSelect(rank.id, 'even')">偶</div>
                 </div>
               </transition>
            </div>
          </div>
          
          <div class="rank-balls">
             <div 
               v-for="num in 10" 
               :key="num" 
               class="ball-item"
               :class="[`pk10-ball-${num}`, { active: isSelected(rank.id, num) }]"
               @click="toggleBall(rank.id, num)"
             >
               {{ num < 10 ? '0'+num : num }}
             </div>
          </div>
        </div>
      </template>


      
      <template v-if="mainPlay === 'single_input'">
         <div class="single-input-panel">
            <div class="input-guide">
               <van-icon name="info-o" />
               <span>格式说明：号码之间用空格或逗号分隔，每注号码之间换行。例如：01 02 03</span>
            </div>
            <textarea 
               class="number-textarea" 
               placeholder="请在此输入号码..." 
               v-model="singleInputText"
               rows="8"
            ></textarea>
            <div class="input-actions">
               <button class="btn-clear" @click="singleInputText = ''">清空文本</button>
               <button class="btn-parse" @click="parseSingleInput">确认选号</button>
            </div>
         </div>
      </template>

    </div>

    
    <div v-if="activeQuickMenu !== null" class="menu-mask" @click="activeQuickMenu = null"></div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Icon as VanIcon, Tabs as VanTabs, Tab as VanTab, showToast, showSuccessToast } from 'vant';
import { usePk10BetStore } from '@/stores/pk10Bet';

const betStore = usePk10BetStore();

const mainPlay = ref('rank_compound');
const activeSubPlay = ref('dwd');
const activeSinglePlay = ref('single_qian5');
const activeQuickMenu = ref(null);
const singleInputText = ref('');

const allRanks = [
  { id: '1', title: '冠军' },
  { id: '2', title: '亚军' },
  { id: '3', title: '季军' },
  { id: '4', title: '第四' },
  { id: '5', title: '第五' },
  { id: '6', title: '第六' },
  { id: '7', title: '第七' },
  { id: '8', title: '第八' },
  { id: '9', title: '第九' },
  { id: '10', title: '第十' },
];

const playConfig = {
    'dwd': [0, 1, 2, 3, 4, 5, 6, 7, 8, 9], // All 10
    'qian5': [0, 1, 2, 3, 4], // Top 5
    'qian4': [0, 1, 2, 3],    // Top 4
    'qian3': [0, 1, 2],       // Top 3
    'qian2': [0, 1],          // Top 2
    'guanjun': [0],           // Top 1
    'longhu': [0, 1, 2, 3, 4], // 1v10, 2v9, 3v8, 4v7, 5v6
    'dxds': [0, 1, 2, 3, 4, 5, 6, 7, 8, 9], // All 10 ranks
    'gyhz': [], // Custom view
};

const dragonTigerTitles = [
    { id: '1', title: '冠军 vs 第十' },
    { id: '2', title: '亚军 vs 第九' },
    { id: '3', title: '季军 vs 第八' },
    { id: '4', title: '第四 vs 第七' },
    { id: '5', title: '第五 vs 第六' }
];

const currentRows = computed(() => {
    if (mainPlay.value !== 'rank_compound') return [];
    

    if (activeSubPlay.value === 'longhu') {
        return dragonTigerTitles;
    }

    const indices = playConfig[activeSubPlay.value] || [];
    return indices.map(i => allRanks[i]);
});

const isSelected = (rankId, val) => {
  const itemId = `${rankId}_${val}`;
  return betStore.isSelected(itemId, `standard_${activeSubPlay.value}`);
};

const toggleBall = (rankId, val) => {
  const item = {
    id: `${rankId}_${val}`,
    label: typeof val === 'number' ? (val < 10 ? '0'+val : val) : getLabelForVal(val),
    odds: 1.96, 
    type: typeof val === 'number' ? 'standard_ball' : 'standard_option',
    rankId: rankId,
    value: val
  };
  betStore.toggleSelection(item, `standard_${activeSubPlay.value}`);
};

const getLabelForVal = (val) => {
    const map = { 'long':'龙', 'hu':'虎', 'da':'大', 'xiao':'小', 'dan':'单', 'shuang':'双' };
    return map[val] || val;
};

const getOptionsForPlay = (playType, rankId) => {
    if (playType === 'longhu') {
        return [
            { label: '龙', val: 'long', colorClass: 'text-red' },
            { label: '虎', val: 'hu', colorClass: 'text-blue' }
        ];
    }
    if (playType === 'dxds') {
        return [
            { label: '大', val: 'da', colorClass: 'text-red' },
            { label: '小', val: 'xiao', colorClass: 'text-blue' },
            { label: '单', val: 'dan', colorClass: 'text-red' },
            { label: '双', val: 'shuang', colorClass: 'text-blue' }
        ];
    }
    return [];
};

const toggleQuickMenu = (rankId) => {
  if (activeQuickMenu.value === rankId) {
    activeQuickMenu.value = null;
  } else {
    activeQuickMenu.value = rankId;
  }
};

const handleQuickSelect = (rankId, type) => {
    const nums = [1,2,3,4,5,6,7,8,9,10];
    let targetNums = [];

    switch(type) {
        case 'all': targetNums = nums; break;
        case 'big': targetNums = nums.filter(n => n > 5); break;
        case 'small': targetNums = nums.filter(n => n <= 5); break;
        case 'odd': targetNums = nums.filter(n => n % 2 !== 0); break;
        case 'even': targetNums = nums.filter(n => n % 2 === 0); break;
        case 'clear': targetNums = []; break;
    }
    

    nums.forEach(n => {
        if (isSelected(rankId, n)) toggleBall(rankId, n);
    });
    targetNums.forEach(n => {
        toggleBall(rankId, n);
    });

    activeQuickMenu.value = null;
};

const parseSingleInput = () => {
    if (!singleInputText.value.trim()) {
        showToast('请输入号码');
        return;
    }
    

    const playConfig = {
        'single_qian5': { count: 5, name: '前五' },
        'single_qian4': { count: 4, name: '前四' },
        'single_qian3': { count: 3, name: '前三' },
        'single_qian2': { count: 2, name: '前二' },
    };
    
    const config = playConfig[activeSinglePlay.value];
    if (!config) {
        showToast('无效的玩法');
        return;
    }
    
    const lines = singleInputText.value.trim().split('\n').filter(l => l.trim());
    const validBets = [];
    const errors = [];
    
    lines.forEach((line, idx) => {

        const nums = line.trim().split(/[\s,]+/).map(n => parseInt(n, 10)).filter(n => !isNaN(n) && n >= 1 && n <= 10);
        
        if (nums.length !== config.count) {
            errors.push(`第${idx + 1}行: 需要${config.count}个号码，实际${nums.length}个`);
            return;
        }
        

        const uniqueNums = [...new Set(nums)];
        if (uniqueNums.length !== nums.length) {
            errors.push(`第${idx + 1}行: 号码不能重复`);
            return;
        }
        
        validBets.push({
            id: `single_${config.name}_${nums.join('')}`,
            label: nums.map(n => n < 10 ? '0'+n : n).join(','),
            nums: nums,
            type: 'single'
        });
    });
    
    if (errors.length > 0) {
        showToast(errors[0]);
        return;
    }
    
    if (validBets.length === 0) {
        showToast('没有有效的号码');
        return;
    }
    

    validBets.forEach(bet => {
        betStore.toggleSelection(bet, activeSinglePlay.value);
    });
    
    showSuccessToast(`已添加 ${validBets.length} 注`);
    singleInputText.value = '';
};

watch(activeSinglePlay, () => {
    singleInputText.value = '';
});

</script>

<style lang="less" scoped>
@import "@/assets/theme.less";

.panel-standard {
  padding-bottom: 80px;
  background: #f5f6fa;
  min-height: 100vh;
}

.main-play-tabs {
    padding: 10px 16px 0;
    margin-bottom: 10px;
    :deep(.van-tabs__nav--card) {
        margin: 0;
    }
}

.sub-play-tabs {
    border-bottom: 1px solid #eee;
    margin-bottom: 10px;
    background: #fff;
    :deep(.van-tab) {
        padding: 0 12px;
        font-size: 13px;
        color: #666;
    }
    :deep(.van-tab--active) {
        color: #ee0a24;
        font-weight: bold;
    }
}

.standard-bet-container {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding-bottom: 20px;
}

.special-play-container {
    padding: 10px;
}

.special-group-title {
    font-weight: bold;
    margin-bottom: 10px;
    text-align: center;
    color: #333;
    font-size: 14px;
}

.special-items-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
}

.special-item {
    background: #fff;
    border: 1px solid #ebedf0;
    border-radius: 8px;
    padding: 10px 6px;
    text-align: center;
    box-shadow: 0 2px 6px rgba(0,0,0,0.02);
    transition: all 0.2s;
    position: relative;
    overflow: hidden;

    &.active {
        border-color: #ee0a24;
        background: #fffbfb;
        box-shadow: 0 4px 12px rgba(238, 10, 36, 0.1);
        transform: translateY(-1px);
        
        &::after {
            content: '';
            position: absolute;
            top: 0; right: 0;
            width: 0; height: 0;
            border-style: solid;
            border-width: 0 16px 16px 0;
            border-color: transparent #ee0a24 transparent transparent;
        }
    }
}

.special-item {
    .label { font-size: 15px; font-weight: bold; margin-bottom: 4px; color: #333; }
    .odds { font-size: 11px; color: #999; font-family: monospace; }
}

.special-row {
    display: flex;
    align-items: center;
    background: #fff;
    padding: 14px 8px;
    margin-bottom: 12px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    transition: transform 0.2s;
    animation: fadeInUp 0.4s ease-out backwards;
    
    &:nth-child(1) { animation-delay: 0.05s; }
    &:nth-child(2) { animation-delay: 0.1s; }
    &:nth-child(3) { animation-delay: 0.15s; }
    &:nth-child(4) { animation-delay: 0.2s; }
    &:nth-child(5) { animation-delay: 0.25s; }
    
    &:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
}

.special-items-row {
    flex: 1;
    display: flex;
    justify-content: space-around;
    gap: 12px;
    padding: 0 4px;
    
    .text-item {
        flex: 1;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        border-radius: 8px;
        background: #f7f8fa;
        color: #666;
        font-size: 14px;
        transition: all 0.2s cubic-bezier(0.25, 0.8, 0.5, 1);
        border: 1px solid #ebedf0;
        
        &:active { transform: scale(0.96); }
    }
    
    .text-item.active {
        font-weight: bold;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    }
    
    .text-item.active.text-red { 
        background: linear-gradient(135deg, #ff4d4d, #ee0a24); 
        color: #fff; 
        border-color: #ee0a24; 
    }
    .text-item.active.text-blue { 
        background: linear-gradient(135deg, #4da6ff, #1989fa); 
        color: #fff; 
        border-color: #1989fa; 
    }
}

.single-input-panel {
    padding: 16px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.02);
    margin-top: 10px;
    
    .input-guide {
        font-size: 12px;
        color: #999;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 6px;
        background: #f9f9f9;
        padding: 8px 12px;
        border-radius: 6px;
    }
    
    .number-textarea {
        width: 100%;
        border: 1px solid #ebedf0;
        border-radius: 8px;
        padding: 12px;
        font-size: 14px;
        background: #fff;
        resize: none;
        transition: all 0.2s;
        font-family: monospace;
        
        &:focus {
            border-color: #ee0a24;
            box-shadow: 0 0 0 3px rgba(238, 10, 36, 0.1);
            outline: none;
        }
    }
    
    .input-actions {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        margin-top: 16px;
        
        button {
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 13px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
            
            &:active { transform: scale(0.95); }
        }
        
        .btn-clear {
            background: #f5f5f5;
            border: 1px solid #ebedf0;
            color: #666;
            &:hover { background: #eee; }
        }
        
        .btn-parse {
            background: linear-gradient(135deg, #ff4d4d, #ee0a24);
            border: none;
            color: white;
            box-shadow: 0 4px 12px rgba(238, 10, 36, 0.3);
            
            &:hover {
                box-shadow: 0 6px 16px rgba(238, 10, 36, 0.4);
                transform: translateY(-1px);
            }
            &:active {
                transform: translateY(0) scale(0.95);
            }
        }
    }
}

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.rank-row {
  display: flex;
  align-items: center;
  background: #fff; 
  margin-bottom: 12px;
  padding: 14px 8px;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
  transition: transform 0.2s, z-index 0s;
  animation: fadeInUp 0.4s ease-out backwards;
  
  &.z-active {
    z-index: 100;
    position: relative;
  }
  
  &:nth-child(1) { animation-delay: 0.05s; }
  &:nth-child(2) { animation-delay: 0.1s; }
  &:nth-child(3) { animation-delay: 0.15s; }
  &:nth-child(4) { animation-delay: 0.2s; }
  &:nth-child(5) { animation-delay: 0.25s; }
  &:nth-child(6) { animation-delay: 0.3s; }
  &:nth-child(7) { animation-delay: 0.35s; }
  &:nth-child(8) { animation-delay: 0.4s; }
  &:nth-child(9) { animation-delay: 0.45s; }
  &:nth-child(10) { animation-delay: 0.5s; }
  
  &:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
  }
}

.rank-header {
  width: 70px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding-right: 10px;
  border-right: 1px solid #f5f5f5;
  margin-right: 8px;
  flex-shrink: 0;
  position: relative;
}

.rank-title {
  font-size: 14px;
  color: #333;
  margin-bottom: 8px;
  font-weight: 700;
  letter-spacing: 1px;
}

.quick-select-trigger {
  font-size: 11px;
  color: #666;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f7f8fa;
  padding: 4px 8px;
  border-radius: 12px;
  border: 1px solid #ebedf0;
  transition: all 0.2s;
  
  &:active {
      background: #e8e8e8;
  }
  
  .van-icon {
      margin-left: 2px;
      transition: transform 0.2s;
  }
}

.quick-menu {
  position: absolute;
  top: 90%;
  left: 5px;
  background: white;
  border: none;
  box-shadow: 0 8px 24px rgba(0,0,0,0.12);
  z-index: 20;
  border-radius: 8px;
  overflow: hidden;
  width: 60px;
  margin-top: 8px;
  animation: slideDown 0.2s ease-out;
  
  .quick-item {
      padding: 10px 0;
      text-align: center;
      font-size: 13px;
      color: #555;
      background: #fff;
      transition: background 0.2s;
      
      &:active { background: #f2f3f5; color: #ee0a24; }
      &:not(:last-child) { border-bottom: 1px solid #f5f6fa; }
  }
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.rank-balls {
  flex: 1;
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 8px;
  padding: 0 12px;
}

.ball-item {
  height: 42px;
  width: 42px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  color: #fff;
  font-size: 18px;
  font-weight: bold;
  box-shadow: 0 2px 5px rgba(0,0,0,0.2), inset 0 2px 3px rgba(255,255,255,0.3);
  transition: all 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  background: #e0e0e0;
  margin: 0 auto;
  position: relative;
  user-select: none;
  
  &:active { transform: scale(0.9); filter: brightness(0.9); }
  &.active { 
      transform: scale(1.05) translateY(-2px); 
      box-shadow: 0 4px 8px rgba(0,0,0,0.3), inset 0 2px 3px rgba(255,255,255,0.3), 0 0 0 2px #fff, 0 0 0 4px #ee0a24;
      z-index: 2;
  }
}

.pk10-ball-1 { background: radial-gradient(circle at 30% 30%, #ffeb3b, #e6de00); color: #333; text-shadow: 0 1px 0 rgba(255,255,255,0.5); }
.pk10-ball-2 { background: radial-gradient(circle at 30% 30%, #29b6f6, #0288d1); }
.pk10-ball-3 { background: radial-gradient(circle at 30% 30%, #757575, #424242); }
.pk10-ball-4 { background: radial-gradient(circle at 30% 30%, #ff9800, #f57c00); }
.pk10-ball-5 { background: radial-gradient(circle at 30% 30%, #00e5ff, #00acc1); color: #333; text-shadow: 0 1px 0 rgba(255,255,255,0.5); }
.pk10-ball-6 { background: radial-gradient(circle at 30% 30%, #5c6bc0, #3949ab); }
.pk10-ball-7 { background: radial-gradient(circle at 30% 30%, #bdbdbd, #9e9e9e); color: #333; text-shadow: 0 1px 0 rgba(255,255,255,0.5); }
.pk10-ball-8 { background: radial-gradient(circle at 30% 30%, #ff5252, #d32f2f); }
.pk10-ball-9 { background: radial-gradient(circle at 30% 30%, #a1887f, #5d4037); }
.pk10-ball-10 { background: radial-gradient(circle at 30% 30%, #66bb6a, #388e3c); }

.menu-mask {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    z-index: 5;
}

.empty-tip {
    text-align: center;
    padding: 40px;
    color: #999;
    font-size: 14px;
}

.fade-enter-active, .fade-leave-active {
  transition: opacity 0.2s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>
