<template>
  <div class="trend-page">
    <div class="trend-header">
      <van-icon name="arrow-left" class="back-icon" @click="$router.back()" />
      <span class="page-title">Xu hướng đặt cược</span>
      <van-icon name="filter-o" class="filter-icon" @click="showFilter = true" />
    </div>

    <div class="filter-bar">
      <div class="filter-item active">
        <span>{{ currentLotteryName }}</span>
        <van-icon name="arrow-down" />
      </div>
      <div class="filter-group">
        <span class="f-tag active">今天</span>
        <span class="f-tag">24h</span>
      </div>
    </div>

    <div class="scroll-content">
      <div class="chart-card">
        <div class="card-title">
          <span class="t-icon">🔥</span> Xếp hạng xổ số phổ biến
        </div>
        <div class="chart-box" ref="lotteryRankChart"></div>
      </div>

      <div class="chart-card">
        <div class="card-title">
          <span class="t-icon">🎱</span> Số hot kỳ này
        </div>
        <div class="hot-balls-grid">
          <div class="hot-ball-item" v-for="(item, i) in hotNumbers" :key="i">
            <div class="ball-glow">{{ item.number }}</div>
            <div class="ball-meta">
              <span class="b-ratio">{{ item.ratio }}%</span>
              <span class="b-amt">¥{{ formatAmt(item.amount) }}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="chart-row">
        <div class="chart-card half">
          <div class="card-title small">Phân bố Lớn/Nhỏ</div>
          <div class="chart-box-sm" ref="sizeRatioChart"></div>
          <div class="ratio-legend">
            <span class="l-item big">Lớn 63%</span>
            <span class="l-item small">Nhỏ 37%</span>
          </div>
        </div>
        <div class="chart-card half">
          <div class="card-title small">Phân bố Lẻ/Chẵn</div>
          <div class="chart-box-sm" ref="oddEvenChart"></div>
          <div class="ratio-legend">
            <span class="l-item odd">Lẻ 45%</span>
            <span class="l-item even">Chẵn 55%</span>
          </div>
        </div>
      </div>

      <div class="chart-card">
        <div class="card-title">
          <span class="t-icon">📈</span> Xu hướng số tiền đặt cược
        </div>
        <div class="chart-box" ref="amountTrendChart"></div>
      </div>

      <div class="chart-card">
        <div class="card-title">
          <span class="t-icon">🏆</span> Độ hot số kỳ này
        </div>
        <div class="issue-heat-list">
          <div class="heat-row" v-for="(item, i) in issueHeat" :key="i">
            <div class="h-ball" :class="{ top3: i < 3 }">{{ item.number }}</div>
            <div class="h-bar-track">
              <div class="h-bar-fill" :style="{ width: item.ratio + '%' }"></div>
            </div>
            <div class="h-val">{{ item.amount }}</div>
          </div>
        </div>
      </div>

      <div class="chart-card">
        <div class="card-title">
          <span class="t-icon">🎮</span> Cách chơi hot (10 phút)
        </div>
        <div class="play-list">
          <div class="play-item" v-for="(p, i) in hotPlays" :key="i">
            <div class="p-rank">{{ i + 1 }}</div>
            <div class="p-info">
              <div class="p-name">{{ p.name }} <span class="tag-hot" v-if="i===0">HOT</span></div>
              <div class="p-sub">Số lượt {{ p.betCount }}</div>
            </div>
            <div class="p-amt">¥{{ p.amount }}</div>
          </div>
        </div>
      </div>

      <div class="live-ticker-bar">
        <div class="ticker-icon">🔔</div>
        <div class="ticker-wrapper">
          <div class="ticker-content">
            <span v-for="(msg, i) in liveTicker" :key="i" class="ticker-item">
              <span class="time">{{ msg.time }}</span>
              <span class="user">{{ msg.userMask }}</span>
              <span class="action" :class="msg.actionType">{{ actionMap[msg.actionType] }}</span>
              <span class="lottery">{{ msg.lotteryName }}</span>
              <span class="money">¥{{ msg.amount }}</span>
            </span>
          </div>
        </div>
      </div>
    </div>

    <van-popup v-model:show="showFilter" position="bottom" round>
      <div style="padding: 20px; text-align: center; color: #000;">Chức năng lọc đang phát triển...</div>
    </van-popup>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import * as echarts from 'echarts'

const route = useRoute()
const type = route.params.type || 'ssc'
const showFilter = ref(false)
const currentLotteryName = ref('Thời thời thái Trùng Khánh')

const lotteryRankChart = ref(null)
const sizeRatioChart = ref(null)
const oddEvenChart = ref(null)
const amountTrendChart = ref(null)

const hotNumbers = [
  { number: 5, ratio: 22, amount: 12580 },
  { number: 8, ratio: 18, amount: 9800 },
  { number: 2, ratio: 15, amount: 8500 },
  { number: 0, ratio: 12, amount: 6200 },
  { number: 9, ratio: 10, amount: 5400 },
  { number: 6, ratio: 8, amount: 4100 },
]

const issueHeat = [
  { number: 5, ratio: 85, amount: 12580 },
  { number: 8, ratio: 70, amount: 9800 },
  { number: 2, ratio: 60, amount: 8500 },
  { number: 0, ratio: 45, amount: 6200 },
  { number: 9, ratio: 35, amount: 5400 },
]

const hotPlays = [
  { name: 'Định vị đơn - Hàng đơn vị', betCount: 452, amount: 56000 },
  { name: 'Ba số đầu chọn thẳng', betCount: 320, amount: 42000 },
  { name: 'Ba số cuối chọn thẳng', betCount: 280, amount: 38000 },
  { name: 'Lớn/Nhỏ Lẻ/Chẵn', betCount: 210, amount: 25000 },
  { name: 'Rồng Hổ Hòa', betCount: 150, amount: 18000 },
]

const liveTicker = [
  { time: '10:02', userMask: 'Nguyễn**', actionType: 'bet', lotteryName: 'Thời thời thái CK', amount: 300 },
  { time: '10:03', userMask: 'Trần**', actionType: 'win', lotteryName: 'Đua xe tốc độ', amount: 1200 },
  { time: '10:03', userMask: 'Lê**', actionType: 'chase', lotteryName: 'Kuai 3', amount: 500 },
  { time: '10:04', userMask: 'Phạm**', actionType: 'bet', lotteryName: 'Lục hợp', amount: 2000 },
]

const actionMap = { bet: 'Đặt cược', win: 'Trúng thưởng', chase: 'Đuổi số' }

const formatAmt = (n) => (n > 10000 ? (n/10000).toFixed(1)+'w' : n)

const initCharts = () => {
  const rankChart = echarts.init(lotteryRankChart.value)
  rankChart.setOption({
    grid: { top: 10, bottom: 20, left: 80, right: 40 },
    xAxis: { show: false },
    yAxis: { 
      type: 'category', 
      data: ['Đua xe tốc độ', 'Úc may mắn 5', 'Thời thời thái CK', 'Kuai 3 1 phút', 'Lục hợp HK'],
      axisLine: { show: false },
      axisTick: { show: false },
      axisLabel: { color: '#fff' }
    },
    series: [{
      type: 'bar',
      data: [3200, 4500, 5800, 7200, 8900],
      itemStyle: {
        color: new echarts.graphic.LinearGradient(0, 0, 1, 0, [
          { offset: 0, color: '#2563eb' },
          { offset: 1, color: '#06b6d4' }
        ]),
        borderRadius: [0, 10, 10, 0]
      },
      label: { show: true, position: 'right', color: '#EAC26E', formatter: '¥{c}' }
    }]
  })

  const sizeChart = echarts.init(sizeRatioChart.value)
  sizeChart.setOption({
    color: ['#ef4444', '#3b82f6'],
    series: [{
      type: 'pie',
      radius: ['60%', '80%'],
      avoidLabelOverlap: false,
      label: { show: false },
      data: [
        { value: 630, name: 'Lớn' },
        { value: 370, name: 'Nhỏ' }
      ]
    }]
  })

  const oeChart = echarts.init(oddEvenChart.value)
  oeChart.setOption({
    color: ['#8b5cf6', '#10b981'],
    series: [{
      type: 'pie',
      radius: ['60%', '80%'],
      avoidLabelOverlap: false,
      label: { show: false },
      data: [
        { value: 450, name: 'Lẻ' },
        { value: 550, name: 'Chẵn' }
      ]
    }]
  })

  const lineChart = echarts.init(amountTrendChart.value)
  lineChart.setOption({
    grid: { top: 30, bottom: 30, left: 40, right: 20 },
    tooltip: { trigger: 'axis' },
    xAxis: {
      type: 'category',
      data: ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30'],
      axisLabel: { color: '#64748b' },
      axisLine: { lineStyle: { color: '#334155' } }
    },
    yAxis: {
      type: 'value',
      splitLine: { lineStyle: { color: '#1e293b' } },
      axisLabel: { color: '#64748b' }
    },
    series: [{
      data: [12000, 13200, 10100, 15400, 18000, 16500],
      type: 'line',
      smooth: true,
      lineStyle: { color: '#EAC26E' },
      areaStyle: {
        color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
          { offset: 0, color: 'rgba(234, 194, 110, 0.5)' },
          { offset: 1, color: 'rgba(234, 194, 110, 0)' }
        ])
      }
    }]
  })

  window.addEventListener('resize', () => {
    rankChart.resize()
    sizeChart.resize()
    oeChart.resize()
    lineChart.resize()
  })
}

onMounted(() => {
  nextTick(() => {
    initCharts()
  })
})
</script>

<style scoped>

.trend-page {
  min-height: 100vh;
  background: #0B0E15;
  color: #fff;
  font-family: 'PingFang SC', sans-serif;
  padding-bottom: 40px;
}

.trend-header {
  height: 44px; display: flex; align-items: center; justify-content: space-between;
  padding: 0 15px; background: rgba(11, 14, 21, 0.9);
  position: sticky; top: 0; z-index: 50; backdrop-filter: blur(10px);
  border-bottom: 1px solid rgba(255,255,255,0.05);
}
.page-title { font-size: 16px; font-weight: bold; color: #EAC26E; }
.back-icon, .filter-icon { font-size: 20px; color: #fff; cursor: pointer; }

.filter-bar {
  display: flex; align-items: center; justify-content: space-between;
  padding: 10px 15px; background: #0f172a;
}
.filter-item {
  display: flex; align-items: center; gap: 4px;
  font-size: 14px; color: #fff; background: rgba(255,255,255,0.1);
  padding: 4px 12px; border-radius: 15px;
}
.filter-group { display: flex; gap: 8px; }
.f-tag {
  font-size: 12px; padding: 4px 10px; border-radius: 12px;
  color: #64748b; background: rgba(255,255,255,0.05);
}
.f-tag.active { color: #000; background: #EAC26E; font-weight: bold; }

.scroll-content { padding: 15px; display: flex; flex-direction: column; gap: 15px; }

.chart-card {
  background: rgba(255,255,255,0.03); border-radius: 16px; padding: 15px;
  border: 1px solid rgba(255,255,255,0.05);
  box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}
.chart-row { display: flex; gap: 15px; }
.chart-card.half { flex: 1; padding: 10px; }

.card-title {
  font-size: 14px; font-weight: bold; margin-bottom: 15px;
  display: flex; align-items: center; gap: 6px;
  color: #cbd5e1;
}
.card-title.small { font-size: 12px; margin-bottom: 10px; justify-content: center; }
.t-icon { font-size: 16px; }

.chart-box { height: 200px; width: 100%; }
.chart-box-sm { height: 100px; width: 100%; }

.hot-balls-grid {
  display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;
}
.hot-ball-item {
  display: flex; flex-direction: column; align-items: center; gap: 5px;
}
.ball-glow {
  width: 40px; height: 40px; border-radius: 50%;
  background: radial-gradient(circle at 30% 30%, #EAC26E, #b45309);
  color: #fff; font-weight: bold; font-size: 16px;
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 0 10px rgba(234, 194, 110, 0.5);
  text-shadow: 0 1px 2px rgba(0,0,0,0.5);
}
.ball-meta { display: flex; flex-direction: column; align-items: center; }
.b-ratio { font-size: 10px; color: #EAC26E; }
.b-amt { font-size: 10px; color: #64748b; }

.ratio-legend { display: flex; justify-content: center; gap: 10px; margin-top: 5px; }
.l-item { font-size: 10px; position: relative; padding-left: 12px; }
.l-item::before {
  content: ''; position: absolute; left: 0; top: 50%; transform: translateY(-50%);
  width: 8px; height: 8px; border-radius: 50%;
}
.l-item.big::before { background: #ef4444; }
.l-item.small::before { background: #3b82f6; }
.l-item.odd::before { background: #8b5cf6; }
.l-item.even::before { background: #10b981; }

.issue-heat-list { display: flex; flex-direction: column; gap: 10px; }
.heat-row { display: flex; align-items: center; gap: 10px; font-size: 12px; }
.h-ball {
  width: 24px; height: 24px; background: rgba(255,255,255,0.1);
  border-radius: 50%; display: flex; align-items: center; justify-content: center;
  color: #94a3b8; font-weight: bold;
}
.h-ball.top3 { background: #ef4444; color: #fff; }
.h-bar-track { flex: 1; height: 6px; background: rgba(255,255,255,0.1); border-radius: 3px; overflow: hidden; }
.h-bar-fill { height: 100%; background: linear-gradient(90deg, #EAC26E, #f59e0b); border-radius: 3px; }
.h-val { width: 50px; text-align: right; color: #EAC26E; font-family: monospace; }

.play-item {
  display: flex; align-items: center; padding: 10px 0;
  border-bottom: 1px solid rgba(255,255,255,0.05);
}
.play-item:last-child { border-bottom: none; }
.p-rank { width: 24px; font-weight: bold; color: #64748b; font-style: italic; }
.play-item:nth-child(-n+3) .p-rank { color: #EAC26E; }
.p-info { flex: 1; }
.p-name { font-size: 13px; color: #fff; display: flex; align-items: center; gap: 4px; }
.tag-hot { font-size: 9px; background: #ef4444; padding: 1px 3px; border-radius: 2px; }
.p-sub { font-size: 10px; color: #64748b; margin-top: 2px; }
.p-amt { font-weight: bold; color: #EAC26E; }

.live-ticker-bar {
  position: fixed; bottom: 0; left: 0; right: 0;
  height: 36px; background: rgba(0,0,0,0.8);
  display: flex; align-items: center; padding: 0 10px;
  border-top: 1px solid rgba(255,255,255,0.1);
  z-index: 40;
}
.ticker-icon { margin-right: 8px; font-size: 14px; }
.ticker-wrapper { flex: 1; overflow: hidden; white-space: nowrap; }
.ticker-content { display: inline-block; animation: marquee 15s linear infinite; }
.ticker-item { margin-right: 20px; font-size: 11px; color: #cbd5e1; }
.action.bet { color: #3b82f6; }
.action.win { color: #ef4444; }
.action.chase { color: #EAC26E; }

@keyframes marquee {
  0% { transform: translateX(100%); }
  100% { transform: translateX(-100%); }
}
</style>
