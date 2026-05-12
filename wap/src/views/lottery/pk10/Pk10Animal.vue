<template>
  <div class="pk10-animal-page">
    
    <div class="animal-header">
      <van-icon name="arrow-left" class="back-icon" @click="onClickLeft" />
      <div class="header-title" @click="showGamePicker = true">
        {{ lotteryTitle }} <van-icon name="arrow-down" />
      </div>
      <div class="header-right"></div>
    </div>

    
    <div class="race-track-area">
       <div class="video-bg">
           <iframe 
             :src="videoUrl" 
             frameborder="0"
             allow="autoplay"
           ></iframe>
       </div>

       <div class="track-top-btns">
           <div class="btn-rule" @click="showRules = true">规则介绍</div>
       </div>
       <div class="btn-official">
           <van-icon name="play-circle-o" /> 官方开奖
       </div>
       
       <div class="btn-history" @click="showHistory = true">
           往期记录 >
       </div>
    </div>

    
    <div class="play-tabs-container">
        <div 
          v-for="tab in playTabs" 
          :key="tab.id" 
          class="play-tab-item"
          :class="{ active: activeSubPlay === tab.id }"
          @click="activeSubPlay = tab.id"
        >
           {{ tab.title }}
        </div>
    </div>

    
    <div class="betting-content">
        
        <div class="play-info-bar">
            <div class="play-title-wrap">
                <div class="icon-shield">
                   <img src="@/assets/png/DM_20251120055056_114.svg" />
                </div>
                <span class="main-text">{{ getPlayTitle(activeSubPlay) }}</span>
                <span class="sub-text">① 玩法范例</span>
            </div>
            <van-icon name="arrow-up" color="#666" />
        </div>

        
        <div class="bet-groups">
            <div v-for="rank in currentRows" :key="rank.id" class="rank-group">
                <div class="rank-title">{{ rank.title }}</div>
                
                <div class="animal-grid">
                    <div 
                      v-for="animal in animals" 
                      :key="animal.id" 
                      class="animal-item"
                      :class="{ active: isSelected(rank.id, animal.id) }"
                      @click="toggleBall(rank.id, animal.id)"
                    >
                       <div class="animal-head">
                           <img :src="getAnimalImg(animal.id)" />
                       </div>
                       <div class="animal-name-wrap">
                           <div class="name-cn">{{ animal.name }}</div>
                           <div class="name-en">{{ animal.en }}</div>
                       </div>
                       <div class="check-badge" v-if="isSelected(rank.id, animal.id)">
                           <van-icon name="success" />
                       </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    
    <div class="footer-bar">
        <div class="bet-info-text">
            已选{{ totalBets }}注, 共{{ (totalBets * (Number(perBetMoney)||0)).toFixed(2) }}元, 余额0元
        </div>
        
        <div class="quick-amounts">
            <div 
              v-for="amt in [10, 100, 1000, 5000, 10000, 50000]" 
              :key="amt"
              class="amt-chip"
              @click="perBetMoney = amt"
            >
                {{ amt }}
            </div>
        </div>

        <div class="footer-actions">
            <div class="action-left">
                <div class="btn-clear" @click="betStore.clearSelection()">清空</div>
                <div class="money-input">
                    <span class="label">金额</span>
                    <div class="stepper-wrap">
                        <button @click="perBetMoney = Math.max(0, (Number(perBetMoney)||0) - 1)">-</button>
                        <input type="number" v-model="perBetMoney" />
                        <button @click="perBetMoney = (Number(perBetMoney)||0) + 1">+</button>
                    </div>
                </div>
            </div>
            <div class="btn-submit" @click="handleBet">
                <div class="main-t">立即竞猜</div>
                <div class="sub-t">第{{ currentIssue.slice(-9) }}期</div>
            </div>
        </div>
    </div>

    
    <van-popup 
      v-model:show="showHistory" 
      class="custom-history-popup"
      closeable
      close-icon="cross"
      close-icon-position="top-right"
    >
        <div class="history-content">
            <div class="h-title">往期数据</div>
            
            
            <div class="stats-section">
                <div class="section-header">
                    <span class="line"></span>
                    近100期冠军数据统计
                    <span class="line"></span>
                </div>
                <div class="stats-row">
                    <div class="stat-col" v-for="a in topAnimals" :key="a.id">
                        <div class="stat-img">
                            <img :src="getAnimalImg(a.id)" />
                            <div class="stat-cup">
                                <img src="@/assets/png/DM_20251120055056_116.svg" /> 
                            </div>
                        </div>
                        <div class="stat-count">{{ a.count }}</div>
                    </div>
                </div>
            </div>

            
            <div class="table-section">
                <div class="section-header">
                    <span class="line"></span>
                    近10场比赛名次
                    <span class="line"></span>
                </div>
                <div class="table-wrapper">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>期号</th>
                                <th v-for="a in animals" :key="a.id">
                                    <div class="th-img"><img :src="getAnimalImg(a.id)" /></div>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="historyList.length === 0">
                                <td :colspan="animals.length + 1" style="text-align: center; color: #999; padding: 20px;">
                                    暂无历史数据
                                </td>
                            </tr>
                            <tr v-for="(row, idx) in historyList" :key="row.issue || row.issueNo || idx">
                                <td class="td-issue">{{ getIssueDisplay(row) }}</td>
                                <td v-for="a in animals" :key="a.id">
                                    <div v-if="getRankFromHistory(row, a.id) === 1" class="rank-1">
                                        <div class="crown-icon">
                                            <img src="@/assets/png/DM_20251120055056_115.svg" /> 
                                        </div>
                                        <div class="winner-text">NO.1</div>
                                    </div>
                                    <span v-else class="rank-num">{{ getRankFromHistory(row, a.id) }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="history-footer-btn">
                查看更多
            </div>
        </div>
    </van-popup>

    
    <van-popup 
      v-model:show="showGamePicker" 
      position="bottom"
      round
    >
        <div class="game-picker-content">
            <div class="picker-header">
                <span class="picker-title">选择彩种</span>
                <van-icon name="cross" class="picker-close" @click="showGamePicker = false" />
            </div>
            <div class="picker-list">
                <div 
                  v-for="(config, code) in lotteryConfigs" 
                  :key="code"
                  class="picker-item"
                  :class="{ active: lotteryCode == code }"
                  @click="switchLottery(code)"
                >
                    <span class="item-title">{{ config.title }}</span>
                    <van-icon v-if="lotteryCode == code" name="success" class="item-check" />
                </div>
            </div>
        </div>
    </van-popup>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { Icon as VanIcon, Popup as VanPopup, showToast, showSuccessToast, showFailToast } from 'vant';
import { usePk10BetStore } from '@/stores/pk10Bet';
import { animalApi } from '@/api';
import { lotteryWS } from '@/utils/websocket';
import dayjs from 'dayjs';

const animalsImgs = import.meta.glob('@/assets/png/*.png', { eager: true });

const getAnimalImg = (id) => {
    const fileNum = 25 + parseInt(id - 1); 
    const fileName = `DM_20251120055056_${String(fileNum).padStart(3, '0')}.png`;
    const key = Object.keys(animalsImgs).find(k => k.endsWith(fileName));
    return key ? animalsImgs[key].default : '';
};

const router = useRouter();
const route = useRoute();
const onClickLeft = () => router.back();

const lotteryCode = ref(route.params.code || 'yfdwc');

const switchLottery = (code) => {
    if (code !== lotteryCode.value) {
        lotteryCode.value = code;

        router.replace({ params: { code } });
    }
    showGamePicker.value = false;
};

let betStore;
try {
    betStore = usePk10BetStore();
} catch (e) {
    betStore = {
        selectedItems: [],
        isSelected: () => false,
        toggleSelection: () => {},
        clearSelection: () => {},
        totalBetCount: 0
    };
}

const activeSubPlay = ref('dwd');
const showHistory = ref(false);
const showRules = ref(false);
const perBetMoney = ref('');
const currentIssue = ref('---');
const historyList = ref([]);
const championStats = ref({});
const remainTime = ref(0);
const isSealed = ref(false);
const userBalance = ref(0);
const loading = ref(false);
const showGamePicker = ref(false);

const lotteryConfigs = {
    'yfdwc': { title: '一分动物彩', gameId: 'OG1ANL' },
    'sfdwc': { title: '三分动物彩', gameId: 'OG3ANL' },
    'wfdwc': { title: '五分动物彩', gameId: 'OG5ANL' },
};

const lotteryTitle = computed(() => {
    return lotteryConfigs[lotteryCode.value]?.title || '动物彩';
});

const videoUrl = computed(() => {
    const gameId = lotteryConfigs[lotteryCode.value]?.gameId || 'OG1ANL';
    return `https://txk3fgy1.srjby9kysmjx.space/?origin=https://sdjwchartjbda.t4nmvxp8jsgq.net&path=${gameId}`;
});

const animals = [
  { id: 1, name: '饿小宝', en: 'Exiaobao' },
  { id: 2, name: '盒马', en: 'Freshippo' },
  { id: 3, name: '票票', en: 'Piaopao' },
  { id: 4, name: '虾仔', en: 'Xiazai' },
  { id: 5, name: '支小宝', en: 'Zhixiaobao' },
  { id: 6, name: '欢猩', en: 'Huanxing' }
];

const playTabs = [
    { id: 'dwd', title: '定位胆' },
    { id: 'qian2', title: '猜冠亚军' },
    { id: 'qian3', title: '猜前三名' },
];

const allRanks = [
  { id: 'rank1', title: '冠军' },
  { id: 'rank2', title: '亚军' },
  { id: 'rank3', title: '季军' },
  { id: 'rank4', title: '第四' },
  { id: 'rank5', title: '第五' },
  { id: 'rank6', title: '第六' }
];

const playConfig = {
    'dwd': [0, 1, 2, 3, 4, 5], 
    'qian2': [0, 1],
    'qian3': [0, 1, 2],
};

const currentRows = computed(() => {
    const indices = playConfig[activeSubPlay.value] || [];
    return indices.map(i => allRanks[i]);
});

const topAnimals = computed(() => {
    if (!championStats.value || Object.keys(championStats.value).length === 0) {
        return animals.map(a => ({ ...a, count: 0 }));
    }
    const stats = animals.map(a => ({
        ...a,
        count: championStats.value[a.id] || 0
    }));
    return stats.sort((a, b) => b.count - a.count);
});

const totalBets = computed(() => betStore.totalBetCount || 0);

const isSelected = (rankId, val) => {
  const itemId = `${rankId}_${val}`;
  return betStore.isSelected(itemId, `animal_${activeSubPlay.value}`);
};

const toggleBall = (rankId, val) => {
  const item = {
    id: `${rankId}_${val}`,
    label: animals.find(a => a.id === val)?.name || val,
    odds: 9.9, 
    type: 'animal_ball',
    rankId,
    value: val
  };
  betStore.toggleSelection(item, `animal_${activeSubPlay.value}`);
};

const getPlayTitle = (id) => playTabs.find(t => t.id === id)?.title || '';

const getRankFromHistory = (row, animalId) => {

    const resultStr = row.result || row.lotteryOpen || '';
    if (!resultStr) return '-';
    const results = resultStr.split(',').map(Number);
    const idx = results.indexOf(animalId);
    return idx === -1 ? '-' : idx + 1;
};

const getIssueDisplay = (row) => {
    const issue = row.issue || row.issueNo || '';
    return issue ? issue.slice(-3) + '期' : '-';
};

const handleBet = async () => {
    if (totalBets.value === 0) {
        return showToast('请至少选择一注');
    }
    
    const money = Number(perBetMoney.value) || 0;
    if (money <= 0) {
        return showToast('请输入投注金额');
    }
    
    if (isSealed.value) {
        return showToast('当前已封盘，请等待下一期');
    }
    

    const bets = [];
    const playId = `animal_${activeSubPlay.value}`;
    const group = betStore.selections.find(s => s.playId === playId);
    
    if (group && group.items) {
        group.items.forEach(item => {
            bets.push({
                rankId: item.rankId,
                animalId: item.value,
                amount: money
            });
        });
    }
    
    if (bets.length === 0) {
        return showToast('请选择投注内容');
    }
    
    loading.value = true;
    
    try {
        const res = await animalApi.submitBet({
            name: lotteryCode.value,
            issueNo: currentIssue.value,
            playType: activeSubPlay.value,
            bets: bets
        });
        
        if (res.code === 0) {
            showSuccessToast('投注成功');
            betStore.clearSelection();
            perBetMoney.value = '';

            if (res.data?.balance) {
                userBalance.value = parseFloat(res.data.balance);
            }
        } else {
            showFailToast(res.message || '投注失败');
        }
    } catch (e) {
        console.error('Bet Error:', e);
        showFailToast('投注失败，请稍后重试');
    } finally {
        loading.value = false;
    }
};

const fetchGameInfo = async () => {
    try {
        const res = await animalApi.getInfo(lotteryCode.value);
        if (res.code === 0 && res.data) {
            currentIssue.value = res.data.issueNo || '';
            remainTime.value = res.data.guessRemainTime || 0;
            isSealed.value = res.data.isSealed || false;
            

            if (res.data.championStats && Array.isArray(res.data.championStats)) {
                const statsObj = {};
                res.data.championStats.forEach(item => {
                    statsObj[item.id] = item.count;
                });
                championStats.value = statsObj;
            }
        }
    } catch (e) {
        console.error('Fetch Game Info Error', e);
    }
};

const fetchHistory = async () => {
    try {
        const dateStr = dayjs().format('YYYY-MM-DD');
        const res = await animalApi.getHistory(lotteryCode.value, dateStr, 1, 20);
        if (res.code === 0 && res.data?.records) {
            historyList.value = res.data.records;
        }
    } catch (e) {
        console.error('Fetch History Error', e);
    }
};

let countdownTimer = null;
const startCountdown = () => {
    countdownTimer = setInterval(() => {
        if (remainTime.value > 0) {
            remainTime.value--;
            if (remainTime.value <= 0) {
                isSealed.value = true;
            }
        }
    }, 1000);
};

watch(lotteryCode, (newCode, oldCode) => {
    if (newCode !== oldCode) {
        betStore.clearSelection();

        if (oldCode) lotteryWS.unsubscribe(oldCode);
        lotteryWS.subscribe(newCode);
        fetchHistory();
    }
}, { immediate: false });

let unsubCountdown = null;
let unsubDrawResult = null;

const setupWebSocket = () => {

    lotteryWS.connect().then(() => {
        lotteryWS.subscribe(lotteryCode.value);
    }).catch(e => {
        console.error('WebSocket connect failed:', e);
    });
    

    unsubCountdown = lotteryWS.on('countdown', (data) => {
        if (data.lotteryCode === lotteryCode.value) {
            currentIssue.value = data.currentIssue || '';
            remainTime.value = data.guessRemainTime ?? data.countdown ?? 0;
            isSealed.value = data.status === 2;
        }
    });
    

    unsubDrawResult = lotteryWS.on('draw_result', (data) => {
        if (data.lotteryCode === lotteryCode.value) {
            fetchHistory();
        }
    });
};

onMounted(() => {
    setupWebSocket();
    fetchHistory();
    startCountdown();
});

onUnmounted(() => {

    lotteryWS.unsubscribe(lotteryCode.value);
    if (unsubCountdown) unsubCountdown();
    if (unsubDrawResult) unsubDrawResult();
    if (countdownTimer) clearInterval(countdownTimer);
});
</script>

<style lang="less" scoped>

.pk10-animal-page {
    min-height: 100vh;
    background: #fffbf5; 
    padding-bottom: 140px; 
    font-family: 'Noto Sans SC', sans-serif;
    color: #5d4037;
}

.animal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 46px;
    padding: 0 16px;
    background: transparent;
    position: absolute;
    top: 0; left: 0; right: 0;
    z-index: 50;
    
    .back-icon {
        font-size: 20px;
        color: #5d4037;
    }
    .header-title {
        font-size: 18px;
        font-weight: bold;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .header-right {
        width: 20px;
    }
}

.race-track-area {
    height: 260px; 
    background: #000;
    position: relative;
    padding-top: 50px;
    overflow: hidden;
}

.video-bg {
    position: absolute;
    top: 0; left: 0;
    width: 100%; height: 100%;
    z-index: 1;
    
    iframe {
        width: 100%;
        height: 100%;
        border: none;
    }
}

.track-top-btns {
    position: absolute;
    top: 50px;
    left: 0;
    z-index: 10;
}

.btn-rule {
    background: #fff;
    color: #ff7043;
    font-size: 12px;
    padding: 4px 10px 4px 14px;
    border-radius: 0 15px 15px 0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.btn-official {
    position: absolute;
    top: 50px;
    left: 80px; 
    background: rgba(0,0,0,0.2);
    color: #fff;
    font-size: 12px;
    padding: 4px 12px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    gap: 4px;
    z-index: 10;
}

.btn-history {
    position: absolute;
    bottom: 20px;
    right: 0;
    background: rgba(255,255,255,0.3);
    color: #fff;
    padding: 4px 12px 4px 16px;
    border-radius: 15px 0 0 15px;
    font-size: 12px;
    cursor: pointer;
    z-index: 10;
}

.play-tabs-container {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 16px;
    background: #fffbf5;
    gap: 10px;
    
    .play-tab-item {
        flex: 1;
        text-align: center;
        padding: 8px 0;
        border-radius: 20px;
        font-size: 14px;
        color: #8d6e63;
        background: #fff;
        border: 1px solid #e0e0e0;
        white-space: nowrap;
        
        &.active {
            background: #5d4037;
            color: #ffd54f;
            border-color: #5d4037;
            font-weight: bold;
        }
    }
}

.betting-content {
    padding: 0 10px;
}

.play-info-bar {
    display: flex;
    align-items: center;
    background: #fff;
    border-radius: 12px 12px 0 0;
    padding: 12px;
    margin-bottom: -2px; 
    position: relative;
    z-index: 2;
    
    .play-title-wrap {
        display: flex;
        align-items: center;
        gap: 6px;
        flex: 1;
        
        .icon-shield {
            width: 20px; height: 20px;
            display: flex; align-items: center; justify-content: center;
            img { width: 100%; height: 100%; }
        }
        .main-text {
            font-size: 15px;
            font-weight: bold;
            color: #5d4037;
        }
        .sub-text {
            font-size: 12px;
            color: #a1887f;
        }
    }
}

.bet-groups {
    background: #fffbf5;
}

.rank-group {
    background: #fff;
    border-radius: 0 0 12px 12px;
    padding: 10px;
    margin-bottom: 12px;
    
    .rank-title {
        text-align: center;
        color: #8d6e63;
        font-size: 14px;
        margin-bottom: 10px;
        position: relative;
        
        &::before, &::after {
            content: '';
            display: inline-block;
            width: 30px;
            height: 1px;
            background: #eee;
            vertical-align: middle;
            margin: 0 10px;
        }
    }
}

.animal-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
}

.animal-item {
    background: #fff8e1;
    border: 1px solid #ffe0b2;
    border-radius: 8px;
    padding: 6px 4px;
    display: flex;
    align-items: center;
    position: relative;
    cursor: pointer;
    transition: all 0.2s;
    
    &.active {
        background: #ffe0b2;
        border-color: #ffa726;
    }
    
    .animal-head {
        width: 32px;
        height: 32px;
        margin-right: 4px;
        flex-shrink: 0;
        img { width: 100%; height: 100%; object-fit: contain; }
    }
    
    .animal-name-wrap {
        flex: 1;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        justify-content: center;
        
        .name-cn {
            font-size: 13px;
            font-weight: bold;
            color: #5d4037;
            line-height: 1.2;
        }
        .name-en {
            font-size: 9px;
            color: #a1887f;
            zoom: 0.9; 
            white-space: nowrap;
        }
    }
    
    .check-badge {
        position: absolute;
        top: -1px; right: -1px;
        background: #ff9800;
        color: #fff;
        width: 16px; height: 16px;
        border-radius: 0 8px 0 8px;
        display: flex; align-items: center; justify-content: center;
        font-size: 10px;
    }
}

.floating-helper {
    position: fixed;
    right: 16px;
    bottom: 160px;
    width: 50px;
    height: 50px;
    background: #e53935;
    border-radius: 50%;
    color: #fff;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 10px rgba(229, 57, 53, 0.4);
    z-index: 80;
    border: 2px solid #fff;
    
    .helper-badge {
        position: absolute;
        top: -5px; right: -5px;
        background: #ffca28;
        color: #d32f2f;
        font-size: 10px;
        padding: 1px 4px;
        border-radius: 8px;
        font-weight: bold;
    }
    
    .helper-text { font-size: 10px; line-height: 1; }
    .helper-btn { font-size: 10px; line-height: 1; margin-top: 2px; }
}

.footer-bar {
    position: fixed;
    bottom: 0; left: 0; right: 0;
    background: #fff;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
    padding: 10px 16px 20px;
    z-index: 90;
}

.bet-info-text {
    text-align: center;
    font-size: 12px;
    color: #8d6e63;
    margin-bottom: 10px;
}

.quick-amounts {
    display: flex;
    justify-content: space-between;
    margin-bottom: 12px;
    gap: 6px;
    
    .amt-chip {
        background: #8d6e63;
        color: #fff;
        font-size: 11px;
        border-radius: 12px;
        padding: 4px 0;
        flex: 1;
        text-align: center;
    }
}

.footer-actions {
    display: flex;
    gap: 12px;
    height: 44px;
}

.action-left {
    flex: 1;
    display: flex;
    gap: 8px;
    
    .btn-clear {
        width: 50px;
        display: flex; align-items: center; justify-content: center;
        background: #fff;
        color: #ff7043;
        border: 1px solid #ffccbc;
        border-radius: 6px;
        font-size: 14px;
    }
    
    .money-input {
        flex: 1;
        display: flex;
        align-items: center;
        background: #fff8e1;
        border-radius: 6px;
        padding: 0 4px;
        border: 1px solid #ffe0b2;
        
        .label {
            font-size: 12px;
            color: #8d6e63;
            margin-right: 4px;
            white-space: nowrap;
        }
        
        .stepper-wrap {
            flex: 1;
            display: flex;
            align-items: center;
            height: 32px;
            
            button {
                width: 24px;
                height: 100%;
                border: none;
                background: transparent;
                font-weight: bold;
                color: #5d4037;
            }
            input {
                width: 100%;
                border: none;
                background: transparent;
                text-align: center;
                font-weight: bold;
                color: #5d4037;
                font-size: 14px;
            }
        }
    }
}

.btn-submit {
    width: 120px;
    background: linear-gradient(to right, #ffab91, #ef5350);
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #fff;
    box-shadow: 0 4px 10px rgba(239, 83, 80, 0.3);
    
    .main-t { font-size: 16px; font-weight: bold; }
    .sub-t { font-size: 10px; opacity: 0.9; }
}

.custom-history-popup {
    width: 90%;
    max-width: 360px;
    border-radius: 16px;
    overflow: hidden;
}

.history-content {
    background: #fff;
    padding: 20px 16px;
    
    .h-title {
        text-align: center;
        font-size: 18px;
        color: #29b6f6;
        font-weight: bold;
        margin-bottom: 20px;
    }
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: bold;
    color: #333;
    margin-bottom: 16px;
    
    .line {
        width: 40px;
        height: 1px;
        background: #ddd;
        margin: 0 10px;
    }
}

.stats-row {
    display: flex;
    justify-content: space-around;
    margin-bottom: 24px;
    
    .stat-col {
        display: flex;
        flex-direction: column;
        align-items: center;
        
        .stat-img {
            width: 36px; height: 36px;
            border-radius: 50%;
            position: relative;
            margin-bottom: 4px;
            
            img { width: 100%; height: 100%; object-fit: contain; }
            .stat-cup {
                position: absolute;
                bottom: -6px; left: 50%;
                transform: translateX(-50%);
                width: 16px; height: 16px;
                img { width: 100%; height: 100%; }
            }
        }
        .stat-count {
            font-weight: bold;
            font-size: 13px;
        }
    }
}

.table-wrapper {
    border: 1px solid #eee;
    border-radius: 8px;
    overflow: hidden;
}

.custom-table {
    width: 100%;
    border-collapse: collapse;
    text-align: center;
    
    th {
        background: #fff;
        padding: 8px 4px;
        border-bottom: 1px solid #eee;
        
        .th-img {
            width: 24px; height: 24px; margin: 0 auto;
            img { width: 100%; height: 100%; }
        }
    }
    
    td {
        padding: 8px 4px;
        border-bottom: 1px solid #eee;
        border-right: 1px solid #eee;
        font-size: 13px;
        font-weight: bold;
        color: #333;
        
        &:last-child { border-right: none; }
    }
    
    .td-issue {
        font-size: 12px;
        color: #666;
        font-weight: normal;
        background: #fafafa;
    }
    
    .rank-1 {
        display: flex;
        flex-direction: column;
        align-items: center;
        line-height: 1;
        
        .crown-icon {
            width: 20px; height: 20px;
            margin-bottom: 2px;
            img { width: 100%; height: 100%; }
        }
        .winner-text {
            font-size: 9px;
            color: #ffc107;
            background: #fff8e1;
            padding: 1px 4px;
            border-radius: 4px;
            border: 1px solid #ffe0b2;
        }
    }
}

.history-footer-btn {
    margin: 20px auto 0;
    width: 140px;
    height: 36px;
    background: #29b6f6;
    color: #fff;
    border-radius: 18px;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px;
    cursor: pointer;
}

.game-picker-content {
    padding: 16px;
    
    .picker-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
        
        .picker-title {
            font-size: 16px;
            font-weight: bold;
            color: #333;
        }
        .picker-close {
            font-size: 20px;
            color: #999;
        }
    }
    
    .picker-list {
        .picker-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 12px;
            border-radius: 8px;
            margin-bottom: 8px;
            background: #f5f5f5;
            cursor: pointer;
            
            &.active {
                background: #fff8e1;
                border: 1px solid #ffa726;
            }
            
            .item-title {
                font-size: 15px;
                color: #333;
            }
            
            .item-check {
                color: #ffa726;
                font-size: 18px;
            }
        }
    }
}
</style>
