<template>
  <van-overlay :show="showPopup" @click="showPopup = false" teleport="body" z-index="2000">
    <div class="popup-wrapper" @click.stop>
      
      <div class="checkin-popup">
        
        <div class="popup-title">{{ activityTitle }}</div>

      
      <div class="progress-info">
        <div class="info-left">
          <div class="info-row">
            <span class="label">Đã điểm danh liên tục</span>
            <span class="value">{{ consecutiveDays }} ngày</span>
          </div>
          <div class="info-row">
            <span class="label">Yêu cầu nạp</span>
            <span class="value"><span class="red">{{ todayDeposit }}</span>/<span class="black">{{ requiredDeposit }}</span></span>
          </div>
        </div>
        <div class="info-right">
          <div class="info-row">
            <span class="label">Đã nhận</span>
            <span class="value gold">{{ totalClaimed }}</span>
            <van-icon name="replay" class="refresh-icon" @click="refreshData" :class="{ spinning: refreshing }" />
          </div>
          <div class="info-row">
            <span class="label">Yêu cầu cược</span>
            <span class="value"><span class="red">{{ todayBet }}</span>/<span class="black">{{ requiredBet }}</span></span>
          </div>
        </div>
      </div>

      
      <div class="checkin-grid">
        <div 
          v-for="(day, index) in days" 
          :key="index"
          class="day-card"
          :class="{ 
            'can-claim': day.canClaim,
            'claimed': day.claimed,
            'locked': !day.canClaim && !day.claimed
          }"
        >
          
          <div class="extra-badge" v-if="day.isExtra">Thưởng thêm</div>
          
          
          <div class="reward-icon">
            <img src="/assets/img/qd_style4_img_qdbx3.avif" alt="奖励" class="reward-img" />
          </div>
          
          
          <div class="reward-amount">{{ day.amount.toFixed(2) }}</div>
          
          
          <div class="divider"></div>
          
          
          <div class="day-label" v-if="!day.canClaim || day.claimed">
            Ngày {{ index + 1 }}
          </div>
          <van-button 
            v-else
            type="success" 
            size="small" 
            class="claim-btn"
            :loading="claiming === index"
            @click="handleClaim(index)"
          >
            Điểm danh
          </van-button>
        </div>
      </div>

      
      <div class="section-block">
        <div class="section-title">
          <span class="title-text">Thời gian sự kiện (UTC+8)</span>
        </div>
        <div class="section-content time-content">
          {{ activityTime }}
        </div>
      </div>

      
      <div class="section-block">
        <div class="section-title">
          <span class="title-text">Hướng dẫn</span>
        </div>
        <div class="section-content rules-content">
          <div class="rules-text" v-html="activityRules"></div>
        </div>
      </div>
      </div>
      
      
      <div class="close-btn" @click="showPopup = false">
        <van-icon name="cross" />
      </div>
    </div>
  </van-overlay>
</template>

<script setup>
import { ref, watch } from 'vue'
import { showToast } from 'vant'
import { activityApi } from '@/api/activity'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  activityId: {
    type: [Number, String],
    default: 0
  }
})

const emit = defineEmits(['update:show'])

const showPopup = ref(false)
const refreshing = ref(false)
const claiming = ref(-1)
const loading = ref(false)

const activityTitle = ref('Điểm danh liên tục')
const consecutiveDays = ref(0)
const totalClaimed = ref('0.00')
const todayDeposit = ref(0)
const requiredDeposit = ref(500)
const todayBet = ref(0)
const requiredBet = ref(1000)
const activityTime = ref('')
const activityRules = ref('')

const days = ref([])

watch(() => props.show, (val) => {
  showPopup.value = val
  if (val && props.activityId) {
    loadData()
  }
})

const loadData = async () => {
  if (!props.activityId) return
  loading.value = true
  
  try {

    const res = await activityApi.getActivityDetail(props.activityId)
    
    if (res.code === 0 && res.data) {
      const data = res.data
      

      activityTitle.value = data.title || 'Điểm danh liên tục'
      activityTime.value = `${data.startDate || ''} - ${data.endDate || ''}`
      activityRules.value = data.content || data.desc || ''
      

      requiredDeposit.value = data.requiredDeposit || 0
      requiredBet.value = data.requiredBet || 0
      

      if (data.rewards && data.rewards.length > 0) {
        days.value = data.rewards.map((reward, index) => ({
          amount: parseFloat(reward.rewardAmount) || 0,
          canClaim: index === (data.consecutiveDays || 0),
          claimed: index < (data.consecutiveDays || 0),
          isExtra: index >= 3 // 第4天起为额外奖励
        }))
      } else {

        days.value = [
          { amount: 1.00, canClaim: true, claimed: false, isExtra: false },
          { amount: 3.00, canClaim: false, claimed: false, isExtra: false },
          { amount: 5.00, canClaim: false, claimed: false, isExtra: false },
          { amount: 8.00, canClaim: false, claimed: false, isExtra: true },
          { amount: 12.00, canClaim: false, claimed: false, isExtra: true },
          { amount: 18.00, canClaim: false, claimed: false, isExtra: true },
          { amount: 28.00, canClaim: false, claimed: false, isExtra: true },
        ]
      }
      

      consecutiveDays.value = data.consecutiveDays || 0
      totalClaimed.value = data.totalClaimed || '0.00'
      todayDeposit.value = data.todayDeposit || 0
      todayBet.value = data.todayBet || 0
    }
  } catch (error) {
    console.error('Tải dữ liệu điểm danh thất bại:', error)
  } finally {
    loading.value = false
  }
}

const refreshData = async () => {
  if (refreshing.value) return
  refreshing.value = true
  try {
    await loadData()
    showToast({ message: 'Làm mới thành công', position: 'middle' })
  } finally {
    setTimeout(() => {
      refreshing.value = false
    }, 500)
  }
}

const handleClaim = async (index) => {

  if (todayDeposit.value < requiredDeposit.value) {
    showToast({ message: `Hôm nay cần nạp đủ ${requiredDeposit.value} đ`, position: 'middle' })
    return
  }
  if (todayBet.value < requiredBet.value) {
    showToast({ message: `Hôm nay cần cược đủ ${requiredBet.value} đ`, position: 'middle' })
    return
  }

  claiming.value = index
  try {

    

    await new Promise(resolve => setTimeout(resolve, 800))
    
    days.value[index].claimed = true
    days.value[index].canClaim = false
    consecutiveDays.value++
    totalClaimed.value = (parseFloat(totalClaimed.value) + days.value[index].amount).toFixed(2)
    

    if (index + 1 < days.value.length) {
      days.value[index + 1].canClaim = true
    }
    
    showToast({
      message: `Điểm danh thành công! Nhận ${days.value[index].amount.toFixed(2)} đ`,
      icon: 'success',
      position: 'middle'
    })
  } catch (error) {
    showToast({ message: 'Điểm danh thất bại, vui lòng thử lại', position: 'middle' })
  } finally {
    claiming.value = -1
  }
}
</script>

<style scoped>

.popup-wrapper {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 92%;
  max-width: 400px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 20px;
}

.checkin-popup {
  width: 100%;
  padding: 20px 15px;
  background: #fff;
  border-radius: 12px;
  max-height: 75vh;
  overflow-y: auto;
}

.close-btn {
  width: 44px;
  height: 44px;
  background: #fff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  flex-shrink: 0;
}

.close-btn .van-icon {
  font-size: 22px;
  color: #333;
}

.popup-title {
  text-align: center;
  font-size: 20px;
  font-weight: bold;
  color: #333;
  margin-bottom: 15px;
}

.progress-info {
  display: flex;
  justify-content: space-between;
  background: #fff;
  border: 1px solid #e8e8e8;
  border-radius: 8px;
  padding: 12px 15px;
  margin-bottom: 15px;
}

.info-left, .info-right {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.info-row {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 13px;
}

.info-row .label {
  color: #666;
}

.info-row .value {
  color: #333;
  font-weight: 500;
}

.info-row .value .red {
  color: #ff4d4f;
}

.info-row .value .black {
  color: #333;
}

.info-row .value.gold {
  color: #FFAA09;
  font-weight: bold;
}

.refresh-icon {
  color: #26A17B;
  font-size: 16px;
  cursor: pointer;
  margin-left: 3px;
}

.refresh-icon.spinning {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.checkin-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
  margin-bottom: 15px;
}

.day-card {
  background: #fff;
  border: 1px solid #e8e8e8;
  border-radius: 8px;
  padding: 10px 8px;
  text-align: center;
  position: relative;
  min-height: 100px;
}

.day-card:nth-child(7) {
  grid-column: 1;
}

.day-card.can-claim {
  
}

.day-card.claimed {
  background: #fafafa;
  opacity: 0.7;
}

.day-card.locked {
  background: #fafafa;
}

.extra-badge {
  position: absolute;
  top: -1px;
  right: -1px;
  background: #ff4d4f;
  color: #fff;
  font-size: 10px;
  padding: 2px 6px;
  border-radius: 0 8px 0 8px;
  font-weight: 500;
}

.reward-icon {
  width: 50px;
  height: 45px;
  margin: 0 auto 5px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.reward-img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.reward-amount {
  font-size: 16px;
  font-weight: bold;
  color: #FFAA09;
  margin-bottom: 8px;
}

.divider {
  height: 1px;
  background: #e8e8e8;
  margin: 0 5px 8px;
}

.day-label {
  font-size: 13px;
  color: #666;
}

.claim-btn {
  width: 70px;
  height: 28px;
  font-size: 13px;
  font-weight: bold;
  border-radius: 14px;
}

.section-block {
  background: #e6f7f1;
  border-radius: 8px;
  margin-bottom: 12px;
  overflow: hidden;
}

.section-title {
  position: relative;
  padding-left: 15px;
}

.section-title::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 4px;
  background: #26A17B;
  border-radius: 0 2px 2px 0;
}

.title-text {
  display: inline-block;
  background: #26A17B;
  color: #fff;
  font-size: 14px;
  font-weight: bold;
  padding: 6px 20px 6px 10px;
  border-radius: 0 20px 20px 0;
}

.section-content {
  padding: 12px 15px;
  background: #fff;
  margin: 0 1px 1px 1px;
  border-radius: 0 0 7px 7px;
}

.time-content {
  font-size: 14px;
  color: #333;
}

.rules-content {
  font-size: 13px;
  color: #666;
  line-height: 1.8;
}

.rules-text :deep(p) {
  margin: 0 0 5px 0;
}

.rules-text :deep(strong) {
  color: #333;
}
</style>

