<template>
  <div class="lottery-info-card">
    <div class="card-content">
      
      <div class="info-flex-container">
        
        
        <div class="info-left" @click="$emit('show-history')">
          <div class="last-issue-header">
             <span class="issue-text">{{ showExpect.lastFullExpect }}期开奖号码</span>
             <van-icon name="arrow-down" size="12" class="more-icon" />
          </div>
          
          <div class="result-area-wrapper">
            <transition name="fade" mode="out-in">
              <div v-if="!isDrawing" key="result" class="result-area">
                 <div class="ball-row">
                    <div v-for="(num, index) in lastOpenCode" :key="`${showExpect.lastFullExpect}-${index}`" class="ball x5-ball">{{ num }}</div>
                 </div>
                 <div class="stats-row">
                    <div class="stat-box box-sum">{{ sumValue }}</div>
                    <div class="stat-box box-dx">{{ bigSmall }}</div>
                    <div class="stat-box box-ds">{{ oddEven }}</div>
                    <div class="stat-box box-lh">{{ dragonTiger }}</div>
                 </div>
              </div>
              <div v-else key="drawing" class="drawing-state">
                <div class="ball-row">
                   <div v-for="(n, i) in 5" :key="i" class="ball rolling-ball">{{ rollingNumbers[i] }}</div>
                </div>
                <div class="loading-text">正在开奖...</div>
              </div>
            </transition>
          </div>
        </div>

        
        <div class="info-divider"></div>

        
        <div class="info-right">
           <div class="next-issue-text">{{ showExpect.currFullExpect }}期</div>
           <div class="countdown-wrapper">
              <van-count-down :time="gametimes.ms" format="mm:ss" class="custom-countdown" />
           </div>
           <div class="state-text">截止</div>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onUnmounted } from 'vue';
import { CountDown as VanCountDown, Icon as VanIcon } from 'vant';

const props = defineProps({
  showExpect: Object,
  lastOpenCode: Array,
  gametimes: Object,
  isDrawing: Boolean,
});

defineEmits(['show-history']);

const rollingNumbers = ref(['01','02','03','04','05']);
let rollTimer = null;

watch(() => props.isDrawing, (val) => {
  if (val) {
    startRolling();
  } else {
    stopRolling();
  }
});

function startRolling() {
  if (rollTimer) return;
  rollTimer = setInterval(() => {

    rollingNumbers.value = rollingNumbers.value.map(() => {
        const n = Math.floor(Math.random() * 11) + 1;
        return n < 10 ? '0' + n : '' + n;
    });
  }, 100);
}

function stopRolling() {
  if (rollTimer) {
    clearInterval(rollTimer);
    rollTimer = null;
  }
}

onUnmounted(() => stopRolling());

const sumValue = computed(() => {
    if (!props.lastOpenCode || props.lastOpenCode.length < 5) return '-';
    return props.lastOpenCode.reduce((a, b) => parseInt(a) + parseInt(b), 0);
});

const bigSmall = computed(() => {
    const sum = parseInt(sumValue.value);
    if (isNaN(sum)) return '-';
    if (sum > 30) return '大';
    if (sum < 30) return '小';
    return '和';
});

const oddEven = computed(() => {
    const sum = parseInt(sumValue.value);
    if (isNaN(sum)) return '-';
    return sum % 2 === 0 ? '双' : '单';
});

const dragonTiger = computed(() => {
    if (!props.lastOpenCode || props.lastOpenCode.length < 5) return '-';
    const v1 = parseInt(props.lastOpenCode[0]);
    const v5 = parseInt(props.lastOpenCode[4]);
    if (v1 > v5) return '龙';
    if (v1 < v5) return '虎';
    return '和';
});

</script>

<style lang="less" scoped>
@import "@/assets/theme.less";

.lottery-info-card {
  background-color: #fdfdf9; 
  border-bottom: 1px solid #eee;
  margin: 0; 
  border-radius: 0;
  box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}

.card-content {
  padding: 10px 12px;
}

.info-flex-container {
    display: flex;
    align-items: stretch;
    justify-content: space-between;
}

.info-left {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 6px;
    cursor: pointer;
}

.last-issue-header {
    display: flex;
    align-items: center;
    font-size: 12px;
    color: #666;
    .more-icon {
        margin-left: 4px;
        color: #999;
    }
}

.result-area-wrapper {
    min-height: 46px; 
}

.result-area {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.ball-row {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.ball {
  width: 24px;
  height: 24px;
  line-height: 24px;
  text-align: center;
  border-radius: 50%; 
  font-size: 14px;
  color: white;
  font-weight: bold;
  box-shadow: 0 1px 2px rgba(0,0,0,0.2);
  background: linear-gradient(135deg, #ff6034, #ee0a24);
  transition: all 0.3s;
}

.rolling-ball {
    background: #ff976a;
    animation: bounce 0.5s infinite alternate;
}

@keyframes bounce {
    from { transform: translateY(0); }
    to { transform: translateY(-3px); }
}

.stats-row {
    display: flex;
    gap: 4px;
}

.stat-box {
    padding: 0 6px;
    height: 18px;
    line-height: 17px;
    text-align: center;
    font-size: 11px;
    border: 1px solid #ddd;
    background: #f5f5f5;
    color: #333;
    border-radius: 2px;
}
.box-sum {
    background: #fff3e0;
    border-color: #ffe0b2;
    color: #e65100;
}
.box-lh, .box-dx, .box-ds {
    background: #f5f5f5;
    border-color: #ddd;
    color: #666;
}

.info-divider {
    width: 1px;
    background-color: #eee;
    margin: 0 10px;
}

.info-right {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-width: 80px;
}

.next-issue-text {
    font-size: 12px;
    color: #333;
    margin-bottom: 2px;
}

.countdown-wrapper {
    margin-bottom: 2px;
}

.custom-countdown {
    font-size: 20px;
    font-weight: bold;
    color: #07c160; 
    line-height: 1.2;
}

.state-text {
    font-size: 11px;
    color: #999;
}

.drawing-state {
    display: flex;
    flex-direction: column;
    gap: 4px;
    .loading-text {
        color: @primary-color;
        font-size: 12px;
        margin-top: 2px;
    }
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
