<template>
  <div class="history-draws">
    <van-nav-bar title="最近开奖" />
    <van-pull-refresh v-model="refreshing" @refresh="onRefresh">
      <van-list
        v-model:loading="loading"
        :finished="finished"
        finished-text="没有更多了"
        @load="loadMore"
      >
        <van-cell v-for="draw in history" :key="draw.issue">
          <div class="draw-item">
            <div class="issue-info">
              <span>第 {{ draw.issue.slice(-4) }} 期</span>
              <span class="draw-time">{{ draw.opentime }}</span>
            </div>
            <div class="winning-numbers">
              <div v-for="(num, index) in draw.numbers" :key="index" :class="['ball', `ball-${parseInt(num)}`]">{{ formatNum(num) }}</div>
            </div>
          </div>
        </van-cell>
      </van-list>
    </van-pull-refresh>
  </div>
</template>

<script setup>
import { ref, inject, onMounted } from 'vue';
import { NavBar as VanNavBar, List as VanList, Cell as VanCell, PullRefresh as VanPullRefresh } from 'vant';
import { pk10Api } from '@/api';

const lotteryCode = inject('lotteryCode', ref('bjpk10'));

const history = ref([]);
const loading = ref(false);
const finished = ref(false);
const refreshing = ref(false);
const page = ref(1);

const formatNum = (num) => {
  const n = parseInt(num);
  return n < 10 ? '0' + n : n;
};

const fetchHistory = async (isRefresh = false) => {
  try {
    if (isRefresh) {
      page.value = 1;
      finished.value = false;
    }
    
    const res = await pk10Api.getHistory(lotteryCode.value, { page: page.value, pageSize: 20 });
    
    if (res.code === 0 && res.data) {
      const list = Array.isArray(res.data) ? res.data : (res.data.list || []);
      const formatted = list.map(item => ({
        issue: item.expect || item.qihao || '',
        numbers: parseOpencode(item.opencode || item.openCode || item.kjhm),
        opentime: item.opentime_format || item.opentime || ''
      }));
      
      if (isRefresh) {
        history.value = formatted;
      } else {
        history.value.push(...formatted);
      }
      
      if (formatted.length < 20) {
        finished.value = true;
      }
      page.value++;
    } else {
      finished.value = true;
    }
  } catch (e) {
    console.error('获取历史记录失败:', e);
    finished.value = true;
  } finally {
    loading.value = false;
    refreshing.value = false;
  }
};

const parseOpencode = (code) => {
  if (!code) return [];
  if (Array.isArray(code)) return code.map(n => parseInt(n, 10));
  return code.split(',').map(n => parseInt(n, 10));
};

const loadMore = () => {
  fetchHistory(false);
};

const onRefresh = () => {
  fetchHistory(true);
};

onMounted(() => {
  fetchHistory(true);
});
</script>

<style lang="less" scoped>
.history-draws {
  height: 100%;
  display: flex;
  flex-direction: column;
}
.van-list {
  flex-grow: 1;
  overflow-y: auto;
  padding-bottom: 20px;
}
.draw-item {
  display: flex;
  flex-direction: column; // Stack issue and balls vertically for PK10
  gap: 8px;
  font-size: 14px;
}
.issue-info {
    color: #666;
    font-size: 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 4px;
}
.draw-time {
    color: #999;
    font-size: 11px;
}
.winning-numbers {
  display: flex;
  gap: 4px;
  flex-wrap: wrap; // Wrap if needed
}

.ball {
  width: 20px;
  height: 20px;
  line-height: 20px;
  text-align: center;
  border-radius: 4px;
  font-size: 12px;
  color: white;
  font-weight: bold;
}

.ball-1 { background-color: #E6DE00; color: #333; }
.ball-2 { background-color: #0092DD; }
.ball-3 { background-color: #4B4B4B; }
.ball-4 { background-color: #FF7600; }
.ball-5 { background-color: #17E2E5; color: #333; }
.ball-6 { background-color: #5234FF; }
.ball-7 { background-color: #BFBFBF; color: #333; }
.ball-8 { background-color: #FF2600; }
.ball-9 { background-color: #780B00; }
.ball-10 { background-color: #07BF00; }
</style>
