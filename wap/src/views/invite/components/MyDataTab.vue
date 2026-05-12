<template>
  <div class="my-data-tab">
    <div class="section-title-outer">直属数据</div>
    <div class="data-grid-outer">
        <div class="data-item">
          <div class="label">新增直属</div>
          <div class="value">{{ formatInt(stats.newDirect) }}</div>
        </div>
        <div class="data-item">
          <div class="label">充值人次</div>
          <div class="value">{{ formatInt(stats.rechargeCount) }}</div>
        </div>
        <div class="data-item">
          <div class="label">首充人数</div>
          <div class="value">{{ formatInt(stats.firstRechargeCount) }}</div>
        </div>
        <div class="data-item">
          <div class="label">当天注册首充人数</div>
          <div class="value">{{ formatInt(stats.todayRegFirstRecharge) }}</div>
        </div>
        <div class="data-item">
          <div class="label">充值金额</div>
          <div class="value">{{ formatNumber(stats.rechargeAmount) }}</div>
        </div>
        <div class="data-item">
          <div class="label">首充金额</div>
          <div class="value">{{ formatNumber(stats.firstRechargeAmount) }}</div>
        </div>
        <div class="data-item">
          <div class="label">当天注册首充金额</div>
          <div class="value">{{ formatNumber(stats.todayRegFirstRechargeAmount) }}</div>
        </div>
        <div class="data-item">
          <div class="label">提现金额</div>
          <div class="value">{{ formatNumber(stats.withdrawAmount) }}</div>
        </div>
        <div class="data-item">
          <div class="label">提现次数</div>
          <div class="value">{{ formatInt(stats.withdrawCount) }}</div>
        </div>
        <div class="data-item">
          <div class="label">领取奖励</div>
          <div class="value">{{ formatNumber(stats.claimReward) }}</div>
        </div>
        <div class="data-item">
          <div class="label">有效投注</div>
          <div class="value">{{ formatNumber(stats.validBet) }}</div>
        </div>
        <div class="data-item">
          <div class="label">直属输赢</div>
          <div class="value">{{ formatNumber(stats.directWinLoss) }}</div>
        </div>
    </div>

    <div class="single-item-wrapper">
      <div class="data-item-single">
        <div class="label">直属业绩</div>
        <div class="value">{{ formatNumber(stats.directPerformance) }}</div>
      </div>
    </div>

    <div class="section-title-outer">来自直属收益</div>
    <div class="data-grid-outer">
        <div class="data-item">
          <div class="label">直属佣金</div>
          <div class="value orange">{{ formatNumber(revenue.directCommission) }}</div>
        </div>
        <div class="data-item">
          <div class="label">其他佣金</div>
          <div class="value orange">{{ formatNumber(revenue.otherCommission) }}</div>
        </div>
        <div class="data-item">
          <div class="label">总佣金</div>
          <div class="value orange">{{ formatNumber(revenue.totalCommission) }}</div>
        </div>
        <div class="data-item">
          <div class="label">获得佣金</div>
          <div class="value orange">{{ formatNumber(revenue.earnedCommission) }}</div>
        </div>
        <div class="data-item">
          <div class="label">推广活动奖励</div>
          <div class="value">{{ formatNumber(revenue.promoReward) }}</div>
        </div>
        <div class="data-item">
          <div class="label">代理活动奖励</div>
          <div class="value">{{ formatNumber(revenue.agentReward) }}</div>
        </div>
    </div>

    <div class="all-data-card">
      <div class="card-title">全部数据(含其他下级)</div>
      <div class="card-row">
        <div class="card-cell">
          <div class="label">总人数</div>
          <div class="value">{{ formatInt(allStats.totalCount) }}</div>
        </div>
        <div class="card-cell">
          <div class="label">直属人数</div>
          <div class="value">{{ formatInt(allStats.directCount) }}</div>
        </div>
        <div class="card-cell">
          <div class="label">其他人数</div>
          <div class="value">{{ formatInt(allStats.otherCount) }}</div>
        </div>
      </div>
      <div class="card-row">
        <div class="card-cell">
          <div class="label">直属业绩</div>
          <div class="value">{{ formatNumber(allStats.directPerformance) }}</div>
        </div>
        <div class="card-cell">
          <div class="label">其他业绩</div>
          <div class="value">{{ formatNumber(allStats.otherPerformance) }}</div>
        </div>
        <div class="card-cell">
          <div class="label">总业绩</div>
          <div class="value">{{ formatNumber(allStats.totalPerformance) }}</div>
        </div>
      </div>
      <div class="card-row">
        <div class="card-cell">
          <div class="label">累计直属充值</div>
          <div class="value">{{ formatNumber(allStats.directRechargeTotal) }}</div>
        </div>
        <div class="card-cell">
          <div class="label">累计直属提现</div>
          <div class="value">{{ formatNumber(allStats.directWithdrawTotal) }}</div>
        </div>
        <div class="card-cell">
          <div class="label">累计直属领取</div>
          <div class="value">{{ formatNumber(allStats.directClaimTotal) }}</div>
        </div>
      </div>
      <div class="card-row last-row">
        <div class="card-cell">
          <div class="label">累计直属有效投注</div>
          <div class="value">{{ formatNumber(allStats.directValidBetTotal) }}</div>
        </div>
        <div class="card-cell">
          <div class="label">累计直属输赢</div>
          <div class="value">{{ formatNumber(allStats.directWinLossTotal) }}</div>
        </div>
      </div>
    </div>

    <div class="all-data-card">
      <div class="card-title">我的全部收益</div>
      <div class="card-row">
        <div class="card-cell">
          <div class="label">直属佣金</div>
          <div class="value">{{ formatNumber(totalRevenue.directCommission) }}</div>
        </div>
        <div class="card-cell">
          <div class="label">其他佣金</div>
          <div class="value">{{ formatNumber(totalRevenue.otherCommission) }}</div>
        </div>
        <div class="card-cell">
          <div class="label">总佣金</div>
          <div class="value">{{ formatNumber(totalRevenue.totalCommission) }}</div>
        </div>
      </div>
      <div class="card-row">
        <div class="card-cell">
          <div class="label">待领取</div>
          <div class="value">{{ formatNumber(totalRevenue.pending) }}</div>
        </div>
        <div class="card-cell">
          <div class="label">已领取</div>
          <div class="value">{{ formatNumber(totalRevenue.claimed) }}</div>
        </div>
        <div class="card-cell">
          <div class="label">累计佣金</div>
          <div class="value">{{ formatNumber(totalRevenue.cumulativeCommission) }}</div>
        </div>
      </div>
      <div class="card-row last-row">
        <div class="card-cell">
          <div class="label">推广活动累计奖励</div>
          <div class="value">{{ formatNumber(totalRevenue.promoRewardTotal) }}</div>
        </div>
        <div class="card-cell">
          <div class="label">代理活动累计奖励</div>
          <div class="value">{{ formatNumber(totalRevenue.agentRewardTotal) }}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue'
import { agentApi } from '@/api/agent'
import { timeOptions, formatNumber, formatInt } from '../useInvite'

const currentTime = ref('today')

const stats = reactive({
  newDirect: 0,
  rechargeCount: 0,
  firstRechargeCount: 0,
  todayRegFirstRecharge: 0,
  rechargeAmount: 0,
  firstRechargeAmount: 0,
  todayRegFirstRechargeAmount: 0,
  withdrawAmount: 0,
  withdrawCount: 0,
  claimReward: 0,
  validBet: 0,
  directWinLoss: 0,
  directPerformance: 0
})

const revenue = reactive({
  directCommission: 0,
  otherCommission: 0,
  totalCommission: 0,
  earnedCommission: 0,
  promoReward: 0,
  agentReward: 0
})

const allStats = reactive({
  totalCount: 0,
  directCount: 0,
  otherCount: 0,
  directPerformance: 0,
  otherPerformance: 0,
  totalPerformance: 0,
  directRechargeTotal: 0,
  directWithdrawTotal: 0,
  directClaimTotal: 0,
  directValidBetTotal: 0,
  directWinLossTotal: 0
})

const totalRevenue = reactive({
  directCommission: 0,
  otherCommission: 0,
  totalCommission: 0,
  pending: 0,
  claimed: 0,
  cumulativeCommission: 0,
  promoRewardTotal: 0,
  agentRewardTotal: 0
})

const changeTime = (value) => {
  currentTime.value = value
}

const fetchData = async () => {
  try {
    const res = await agentApi.getMyStats({ dateType: currentTime.value })
    if (res.code === 0 && res.data) {
      Object.assign(stats, res.data.direct || {})
      Object.assign(revenue, res.data.revenue || {})
      Object.assign(allStats, res.data.all || {})
      Object.assign(totalRevenue, res.data.totalRevenue || {})
    }
  } catch (e) {
  }
}

watch(currentTime, () => {
  fetchData()
})

onMounted(() => {
  fetchData()
})

defineExpose({
  changeTime
})
</script>

<style scoped>
.my-data-tab {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding-bottom: 40px;
}

.section-title-outer {
  font-size: 16px;
  font-weight: 600;
  color: #333;
  margin-bottom: 8px;
  text-align: center;
}

.data-grid-outer {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
}

.data-grid-outer .data-item {
  width: 128px;
  height: 86px;
  background: #fff;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  text-align: center;
  padding: 10px 8px;
  margin: 0 auto;
  box-sizing: border-box;
  overflow: hidden;
}

.data-grid-outer .data-item .label {
  font-size: 14px;
  color: #999;
}

.data-grid-outer .data-item .value {
  font-size: 20px;
  font-weight: 600;
  color: #333;
}

.data-grid-outer .data-item .value.orange {
  color: #FFAA09;
}

.all-data-card {
  background: #fff;
  border-radius: 12px;
  overflow: hidden;
}

.all-data-card .card-title {
  font-size: 16px;
  font-weight: 600;
  color: #333;
  text-align: center;
  padding: 16px;
  border-bottom: 1px solid #f0f0f0;
}

.all-data-card .card-row {
  display: flex;
  border-bottom: 1px solid #f0f0f0;
}

.all-data-card .card-row.last-row {
  border-bottom: none;
}

.all-data-card .card-cell {
  flex: 1;
  text-align: center;
  padding: 15px 8px;
  border-right: 1px solid #f0f0f0;
}

.all-data-card .card-cell:last-child {
  border-right: none;
}

.all-data-card .card-cell .label {
  font-size: 13px;
  color: #999;
  margin-bottom: 8px;
}

.all-data-card .card-cell .value {
  font-size: 16px;
  font-weight: 600;
  color: #333;
}

.all-data-card .card-cell .value.orange {
  color: #FFAA09;
}

.single-item-wrapper {
  display: flex;
  justify-content: flex-start;
}

.data-item-single {
  width: 128px;
  height: 86px;
  background: #fff;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  text-align: center;
  padding: 10px 8px;
  box-sizing: border-box;
}

.data-item-single .label {
  font-size: 14px;
  color: #999;
}

.data-item-single .value {
  font-size: 20px;
  font-weight: 600;
  color: #333;
}

.data-section {
  background: #fff;
  border-radius: 12px;
  padding: 15px;
}

.section-title {
  font-size: 15px;
  font-weight: 600;
  color: #333;
  margin-bottom: 15px;
  padding-bottom: 10px;
  border-bottom: 1px solid #f0f0f0;
  text-align: center;
}

.data-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 15px;
}

.data-grid.single {
  grid-template-columns: 1fr;
}

.data-item {
  text-align: center;
}

.data-item.full {
  grid-column: 1 / -1;
}

.data-item .label {
  font-size: 12px;
  color: #999;
  margin-bottom: 6px;
}

.data-item .value {
  font-size: 14px;
  font-weight: 600;
  color: #333;
}

.data-item .value.large {
  font-size: 24px;
}

.data-section.no-title {
  padding-top: 10px;
}

.data-section.performance-single {
  padding: 12px 15px;
}

.data-section.performance-single .data-item {
  text-align: left;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.data-section.performance-single .label {
  margin-bottom: 0;
}

.data-item .value.green {
  color: #26A17B;
}

.data-item .value.orange {
  color: #FF9800;
}

.data-item.span2 {
  grid-column: span 2;
}

.data-grid-2 {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 15px;
  margin-top: 15px;
  padding-top: 15px;
  border-top: 1px solid #f0f0f0;
}
</style>
