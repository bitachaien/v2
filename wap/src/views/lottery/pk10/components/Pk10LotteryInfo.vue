<template>
  <div class="lottery-info-card">
    <div class="card-content">
      
      <div class="info-flex-container">
        
        
        <div class="info-left" @click="$emit('show-history')">
          <div class="last-issue-header">
             <span class="issue-text">{{ showExpect.lastFullExpect }}期开奖号码</span>
             <van-icon name="arrow" size="12" class="more-icon" />
          </div>
          
          <div v-if="!isDrawing" class="result-area">
             <div class="ball-row">
                <div v-for="(num, index) in lastOpenCode" :key="`${showExpect.lastFullExpect}-${index}`" :class="['ball', `ball-${parseInt(num)}`]">{{ parseInt(num) < 10 ? '0'+parseInt(num) : parseInt(num) }}</div>
             </div>
             <div class="stats-row">
                <div class="stat-box box-sum">{{ sumValue }}</div>
                <div class="stat-box box-lh">{{ dragonTiger[0] }}</div>
                <div class="stat-box box-lh">{{ dragonTiger[1] }}</div>
                <div class="stat-box box-lh">{{ dragonTiger[2] }}</div>
                <div class="stat-box box-lh">{{ dragonTiger[3] }}</div>
                <div class="stat-box box-lh">{{ dragonTiger[4] }}</div>
             </div>
          </div>
          <div v-else class="drawing-state">
            <div class="loading-text">正在开奖...</div>
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
import { computed } from 'vue';
import { CountDown as VanCountDown, Icon as VanIcon } from 'vant';

const props = defineProps({
  showExpect: Object,
  lastOpenCode: Array,
  gametimes: Object,
  isDrawing: Boolean,
});

defineEmits(['show-history']);

const sumValue = computed(() => {
    if (!props.lastOpenCode || props.lastOpenCode.length < 2) return '-';
    return parseInt(props.lastOpenCode[0]) + parseInt(props.lastOpenCode[1]);
});

const dragonTiger = computed(() => {
    if (!props.lastOpenCode || props.lastOpenCode.length < 10) return ['-','-','-','-','-'];
    const res = [];
    for(let i=0; i<5; i++) {
        const v1 = parseInt(props.lastOpenCode[i]);
        const v2 = parseInt(props.lastOpenCode[9-i]);
        res.push(v1 > v2 ? '龙' : '虎');
    }
    return res;
});

</script>

<style lang="less" scoped>
@import "@/assets/theme.less";

.lottery-info-card {
  background-color: #fdfdf9; // Slightly warmer background like the reference
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
    
    &:active {
        opacity: 0.7;
    }
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

.result-area {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.ball-row {
    display: flex;
    gap: 2px;
    flex-wrap: wrap;
}

.ball {
  width: 18px;
  height: 18px;
  line-height: 18px;
  text-align: center;
  border-radius: 4px; // Square with rounded corners as per PK10 standard
  font-size: 11px;
  color: white;
  font-weight: bold;
  box-shadow: 0 1px 2px rgba(0,0,0,0.2);
  font-family: Arial, sans-serif;
}

.ball-1 { background-color: #e6de00; color: #333; }
.ball-2 { background-color: #0092dd; }
.ball-3 { background-color: #4b4b4b; }
.ball-4 { background-color: #ff7600; }
.ball-5 { background-color: #17e2e5; color: #333; }
.ball-6 { background-color: #5234ff; }
.ball-7 { background-color: #bfbfbf; color: #333; }
.ball-8 { background-color: #ff2600; }
.ball-9 { background-color: #780b00; }
.ball-10 { background-color: #07bf00; }

.stats-row {
    display: flex;
    gap: 2px;
}

.stat-box {
    width: 18px;
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

.box-lh {
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
    color: #07c160; // Green color for timer
    line-height: 1.2;
}

.state-text {
    font-size: 11px;
    color: #999;
}

.drawing-state {
    padding: 10px 0;
    .loading-text {
        color: @primary-color;
        font-size: 12px;
    }
}
</style>
