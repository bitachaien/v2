<template>
  <div class="tab-vip">
    <div class="vip-header-card">
      <div class="current-level-tag" v-if="previewLevel === currentLevel">Cấp hiện tại</div>
      
      <div class="level-switcher">
        <div class="vip-badge-wrapper">
          <img :src="getBgIcon(previewLevel)" class="vip-outer-ring" />
          <div class="vip-badge-img-container">
             <img :src="getBadgeIcon(previewLevel)" class="vip-badge-img" />
             <span class="vip-level-num">{{ previewLevel }}</span>
          </div>
        </div>
      </div>
      
      <div class="vip-status">
        <div class="status-text">
          <template v-if="previewLevel === currentLevel">
            Cách <span class="next-level">VIP{{ currentLevel + 1 }}</span>
          </template>
          <template v-else>
            <span class="preview-title">Chi tiết VIP {{ previewLevel }}</span>
          </template>
        </div>
        <div class="status-sub">
          <template v-if="previewLevel === currentLevel">
            Còn cần cược <span class="amount">{{ formatAmount(needBet) }}</span>
            <i 
              class="refresh-icon" 
              :class="{ spinning: refreshing }"
              @click="handleRefresh"
              title="点击刷新"
              aria-label="刷新"
            ></i>
          </template>
          <template v-else>
            Thăng cấp cần cược <span class="amount">{{ formatAmount(getLevelData(previewLevel).upgradeBet) }}</span>
          </template>
        </div>
      </div>

      <div class="vip-actions">
        <button class="vip-claim-btn" :class="canClaim ? 'green' : 'gray'" :disabled="!canClaim" @click="handleClaim" type="button">Nhận tất cả</button>
        <button class="vip-record-btn" type="button" @click="openRecords">Lịch sử nhận</button>
      </div>
    </div>

    <div class="vip-table-section">
      <div class="section-title">Bảng Đối Chiếu Cấp VIP</div>
      
      <div class="table-body-wrapper">
        <div class="floating-arrow left" v-if="activeTableTab > 0" @click="activeTableTab--">
          <van-icon name="arrow-left" />
        </div>

        <van-tabs 
          v-model:active="activeTableTab" 
          animated 
          swipeable 
          color="#009688" 
          title-active-color="#009688"
          line-width="30px"
          line-height="3px"
        >
          <van-tab v-for="(tab, index) in tableTabs" :key="index" :title="tab">
            <div class="table-header">
              <div class="col-level">Cấp</div>
              <template v-if="index === 0">
                <div class="col-data">Tổng rút mỗi ngày<br><span class="sub">Tối đa</span></div>
                <div class="col-data">Số lần rút/ngày<br><span class="sub">Tối đa</span></div>
                <div class="col-data">Miễn phí rút/ngày<br><span class="sub">Số lần</span></div>
              </template>
              <template v-if="index === 1">
                <div class="col-data">Cược thăng cấp</div>
                <div class="col-data">Thưởng thăng cấp</div>
              </template>
              <template v-if="index === 2">
                <div class="col-data">Cược trong tuần</div>
                <div class="col-data">Lương tuần</div>
              </template>
              <template v-if="index === 3">
                <div class="col-data">Cược trong tháng</div>
                <div class="col-data">Lương tháng</div>
              </template>
            </div>

            <div class="table-body">
              <div class="table-row" v-for="(item, rIndex) in vipLevels" :key="rIndex" :ref="el => setRowRef(el, item.level)">
                <div class="col-level">
                  <div class="vip-badge-wrapper small">
                    <div v-if="currentLevel === item.level" class="current-level-mark">
                      <img src="/assets/img/img_vip_dqicon.svg" />
                    </div>
                    <img :src="getBgIcon(item.level)" class="vip-outer-ring" />
                    <div class="vip-badge-img-container">
                      <img :src="getBadgeIcon(item.level)" class="vip-badge-img" />
                      <span class="vip-level-num">{{ item.level }}</span>
                    </div>
                  </div>
                </div>

                <template v-if="index === 0">
                  <div class="col-data">{{ item.withdrawLimit }}</div>
                  <div class="col-data">{{ item.withdrawTimes }}</div>
                  <div class="col-data">{{ item.withdrawFree }}</div>
                </template>

                <template v-if="index === 1">
                  <div class="col-data w-progress" v-if="item.level === currentLevel + 1">
                    <div class="progress-bar-wrapper">
                      <div class="progress-bar" :class="{ 'no-progress': progress <= 0 }">
                         <div class="progress-fill" :style="{ width: progress + '%' }"></div>
                         <span class="progress-text">{{ formatAmount(currentBet) }}/{{ formatAmount(item.upgradeBet) }}</span>
                      </div>
                    </div>
                  </div>
                  <div class="col-data" v-else>{{ formatAmount(item.upgradeBet) }}</div>
                  <div class="col-data highlight">{{ formatAmount(item.upgradeBonus) }}</div>
                </template>

                <template v-if="index === 2">
                   <div class="col-data">{{ formatAmount(item.weeklyBet) }}</div>
                   <div class="col-data highlight">{{ formatAmount(item.weeklyBonus) }}</div>
                </template>

                <template v-if="index === 3">
                   <div class="col-data">{{ formatAmount(item.monthlyBet) }}</div>
                   <div class="col-data highlight">{{ formatAmount(item.monthlyBonus) }}</div>
                </template>
              </div>
            </div>
          </van-tab>
        </van-tabs>

        <div class="floating-arrow right" v-if="activeTableTab < tableTabs.length - 1" @click="activeTableTab++">
          <van-icon name="arrow" />
        </div>
      </div>
    </div>

     <div class="vip-rules">
       <div class="rules-title">Quy Tắc VIP</div>
       <div class="rules-content">
         <p>1. Tiêu chuẩn thăng cấp: Đáp ứng yêu cầu thăng cấp VIP (nạp tiền hoặc cược hợp lệ đủ điều kiện), sẽ thăng cấp VIP tương ứng và nhận thưởng thăng cấp. Nếu thăng nhiều cấp liên tiếp, nhận được tất cả thưởng thăng cấp, có thể nhận ngay.</p>
         <p>2. Lương tuần: Mỗi tuần nạp tiền và cược hợp lệ đủ yêu cầu lương tuần cấp hiện tại, nhận được thưởng lương tuần tương ứng. Nếu thăng nhiều cấp liên tiếp, chỉ nhận lương tuần cấp hiện tại, có thể nhận ngay.</p>
         <p>3. Lương tháng: Mỗi tháng nạp tiền và cược hợp lệ đủ yêu cầu lương tháng cấp hiện tại, nhận được thưởng lương tháng tương ứng. Nếu thăng nhiều cấp liên tiếp, chỉ nhận lương tháng cấp hiện tại, có thể nhận ngay.</p>
         <p>4. Thời hạn thưởng: Thưởng nhận được cần nhận thủ công theo yêu cầu ưu đãi</p>
         <p>5. Yêu cầu vòng cược: Thưởng VIP cần 1x vòng cược (tức cược hợp lệ) mới rút được, không giới hạn nền tảng game</p>
         <p>6. Tuyên bố hoạt động: Hoạt động này chỉ dành cho chủ tài khoản chơi game bình thường, cấm cho thuê tài khoản, cược không rủi ro (như cược 2 bên/đối xung/cược thấp), sao chép ác ý, sử dụng cheat, bot, lợi dụng giao thức, lỗ hổng, giao diện, điều khiển nhóm hoặc các thủ đoạn kỹ thuật khác. Nếu phát hiện, nền tảng có quyền chấm dứt đăng nhập, tạm ngừng sử dụng website và tịch thu thưởng cùng lợi nhuận bất hợp pháp mà không cần thông báo</p>
         <p>7. Giải thích: Khi nhận thưởng VIP, nền tảng mặc định thành viên đồng ý và tuân thủ các điều kiện liên quan. Để tránh hiểu nhầm, nền tảng giữ quyền giải thích cuối cùng hoạt động này.</p>
       </div>
     </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onActivated, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { showToast, showLoadingToast, showSuccessToast } from 'vant'
import { vipApi } from '@/api/vip'

const router = useRouter()

const currentLevel = ref(1)
const previewLevel = ref(1)
const needBet = ref(0)
const currentBet = ref(0)
const pendingReward = ref(0)
const canClaim = ref(false)
const progress = ref(0)
const loading = ref(true)
const refreshing = ref(false)

const tableTabs = ['Đặc quyền VIP', 'Thưởng thăng cấp', 'Lương tuần', 'Lương tháng']
const activeTableTab = ref(1)

const vipLevels = ref([])

const isApiSuccess = (res) => res && (res.code === 0 || res.code === 200)

const fetchLevelConfigs = async () => {
  try {
    const res = await vipApi.getLevelConfigs()
    if (isApiSuccess(res) && res.data) {
      vipLevels.value = res.data.map((item, index) => ({
        level: index + 1,
        levelId: item.levelId,
        levelName: item.levelName,
        withdrawLimit: item.withdrawLimit > 0 ? formatAmount(item.withdrawLimit) : 'Không giới hạn',
        withdrawTimes: item.withdrawTimes > 0 ? item.withdrawTimes + ' lần' : 'Không giới hạn',
        withdrawFree: item.freeWithdrawTimes || 0,
        upgradeBet: item.cumulativeRequired || 0,
        upgradeBonus: item.rewardAmount || 0,
        weeklyBet: item.weeklyBetting || 0,
        weeklyBonus: item.weeklyBonus || 0,
        monthlyBet: item.monthlyBetting || 0,
        monthlyBonus: item.monthlyBonus || 0,
      }))
    }
  } catch (e) {
  }
}

const fetchRewardInfo = async () => {
  try {
    const res = await vipApi.getRewardInfo()
    if (isApiSuccess(res) && res.data) {
      const d = res.data
      currentLevel.value = Number(d.currentLevelId) || 1
      previewLevel.value = Number(d.currentLevelId) || 1
      pendingReward.value = Number(d.rewardAmount) || 0
      canClaim.value = d.canClaim || false
      progress.value = d.progress || 0
      currentBet.value = Number(d.totalBetting) || 0
      
      const nextLevelData = vipLevels.value.find(v => v.level === currentLevel.value + 1)
      if (nextLevelData) {
        needBet.value = Math.max(0, nextLevelData.upgradeBet - currentBet.value)
      }
    }
  } catch (e) {
  }
}

const handleClaim = async () => {
  if (!canClaim.value) return
  const toast = showLoadingToast({ message: 'Đang nhận...', forbidClick: true, duration: 0 })
  try {
    const res = await vipApi.claimReward()
    toast.close()
    if (isApiSuccess(res)) {
      showSuccessToast(`Nhận thành công! ${res.data?.amount || 0}`)
      fetchRewardInfo()
    } else {
      showToast(res?.msg || res?.message || 'Nhận thất bại')
    }
  } catch (e) {
    toast.close()
    showToast('Lỗi mạng')
  }
}

const openRecords = () => {
  router.push('/reward-record')
}

const getLevelData = (level) => {
  return vipLevels.value.find(item => item.level === level) || {}
}

const handleRefresh = async () => {
  if (refreshing.value) return
  refreshing.value = true
  showToast({ message: 'Đang làm mới...', duration: 800 })
  try {
    await fetchRewardInfo()
    showSuccessToast('Làm mới thành công')
  } catch (e) {
    showToast('Làm mới thất bại')
  } finally {
    setTimeout(() => { refreshing.value = false }, 600)
  }
}

const rowRefs = ref({})
const setRowRef = (el, level) => {
  if (el) rowRefs.value[level] = el
}

const scrollToCurrentLevel = () => {
  nextTick(() => {
    const nextLevel = currentLevel.value + 1
    const row = rowRefs.value[nextLevel] || rowRefs.value[currentLevel.value]
    if (row) {
      row.scrollIntoView({ block: 'center' })
    }
  })
}

const getBgIcon = (level) => {
  if (level >= 10) return '/assets/img/color10.avif'
  return `/assets/img/color${level}.avif`
}

const getBadgeIcon = (level) => {
  if (level >= 10) return '/assets/img/img_dj10.avif'
  return `/assets/img/img_dj${level - 1}.avif`
}

const formatAmount = (num) => {
  if (!num) return '0.00'
  return Number(num).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const getProgress = (item) => {
  if (!item.upgradeBet) return 0
  return Math.min((currentBet.value / item.upgradeBet) * 100, 100)
}

const loadData = async () => {
  loading.value = true
  await fetchLevelConfigs()
  await fetchRewardInfo()
  loading.value = false
}

onMounted(() => {
  loadData()
})

onActivated(() => {
  loadData()
})
</script>

<style scoped>
.tab-vip {
  flex: 1;
  overflow-y: auto;
  background: #fff;
  padding-bottom: 20px;
}

.vip-header-card {
  margin: 15px;
  background: #fff;
  border-radius: 10px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
  padding: 15px;
  position: relative;
  border: 1px solid #f0f0f0;
  display: flex;
  align-items: center;
  gap: 10px;
}
.current-level-tag {
  position: absolute;
  top: -10px;
  left: 0;
  background: #ff4d4f;
  color: #fff;
  font-size: 10px;
  padding: 2px 6px;
  border-top-left-radius: 10px;
  border-bottom-right-radius: 10px;
  z-index: 2;
}
.level-switcher {
  display: flex;
  align-items: center;
  flex-shrink: 0;
  position: relative;
}
.switch-btn-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 20px;
}
.switch-btn {
  color: #ccc;
  font-size: 16px;
  cursor: pointer;
  background: rgba(0,0,0,0.05);
  border-radius: 50%;
  padding: 2px;
}
.vip-badge-wrapper {
  position: relative;
  width: 60px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 5px;
}
.vip-badge-wrapper.small {
  width: 40px;
  height: 40px;
  margin-left: 12px;
}

.current-level-mark {
  position: absolute;
  top: 0;
  left: -22px;
  background: #04BE02;
  border-radius: 2px;
  width: 18px;
  height: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 5;
  box-shadow: 0 1px 2px rgba(0,0,0,0.1);
}
.current-level-mark img {
  width: 10px;
  height: 10px;
  display: block;
}

.vip-outer-ring {
  position: absolute;
  width: 100%;
  height: 100%;
  top: 0;
  left: 0;
  object-fit: contain;
}

.vip-badge-img-container {
  position: relative;
  width: 75%;
  height: 75%;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2;
}

.vip-badge-img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.vip-level-num {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-size: 18px;
  font-weight: 900;
  color: #fff;
  text-shadow: 0 1px 2px rgba(0,0,0,0.3);
  font-family: 'Arial', sans-serif;
}
.vip-badge-wrapper.small .vip-level-num {
  font-size: 12px;
}

.vip-status { flex: 1; display: flex; flex-direction: column; justify-content: center; overflow: hidden; }
.status-text { font-size: 13px; color: #666; margin-bottom: 4px; white-space: nowrap; }
.next-level { color: #ff4d4f; font-weight: bold; font-style: italic; margin-left: 4px; }
.preview-title { color: #333; font-weight: bold; }
.status-sub { font-size: 12px; color: #999; display: flex; align-items: center; flex-wrap: wrap; }
.status-sub .amount { color: #333; font-weight: bold; margin: 0 4px; }
.refresh-icon { 
  width: 16px; 
  height: 16px; 
  margin-left: 5px; 
  cursor: pointer;
  transition: transform 0.3s;
  display: inline-block;
  background-color: #26A17B;
  -webkit-mask: url('/assets/img/comm_icon_retry.svg') no-repeat center / contain;
  mask: url('/assets/img/comm_icon_retry.svg') no-repeat center / contain;
}
.refresh-icon:active { transform: scale(0.9); }
.refresh-icon.spinning {
  animation: spin 0.8s linear infinite;
}
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.vip-actions {
  display: flex;
  flex-direction: column;
  gap: 8px;
  flex-shrink: 0;
  margin-left: 5px;
}
.vip-claim-btn {
  border: none;
  font-size: 11px;
  padding: 4px 12px;
  border-radius: 4px;
  text-align: center;
  cursor: pointer;
}
.vip-claim-btn.gray { background: #bdbdbd; color: #fff; pointer-events: none; cursor: not-allowed; }
.vip-claim-btn.green { background: #26A17B; color: #fff; }
.vip-record-btn {
  font-size: 11px;
  padding: 4px 12px;
  border-radius: 4px;
  text-align: center;
  cursor: pointer;
  background: #26A17B;
  color: #fff;
  border: none;
}
.vip-record-btn:hover { background: #1f8a68; }

.vip-table-section { margin-top: 20px; }
.section-title { text-align: center; font-size: 16px; color: #333; margin-bottom: 15px; }

.table-body-wrapper {
  position: relative;
}

:deep(.van-tabs__content) {
  background: #fff;
}
:deep(.van-tabs__wrap) {
  border-bottom: 1px solid #eee;
  margin-bottom: 10px;
}

.floating-arrow {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background: rgba(0, 0, 0, 0.4);
  width: 30px;
  height: 30px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #fff;
  font-size: 18px;
  z-index: 100;
  transition: background 0.3s;
}
.floating-arrow:hover { background: rgba(0, 0, 0, 0.6); }
.floating-arrow.left { left: 5px; }
.floating-arrow.right { right: 5px; }

.table-header, .table-row {
  display: flex;
  align-items: center;
  padding: 12px 0;
  text-align: center;
  font-size: 12px;
}
.table-header { background: #f9f9f9; color: #333; font-weight: 500; }
.table-header .sub { font-size: 10px; color: #999; font-weight: normal; }
.table-row { border-bottom: 1px solid #f5f5f5; height: 50px; }

.col-level { width: 80px; flex-shrink: 0; display: flex; justify-content: center; align-items: center; }

.col-data { flex: 1; color: #666; display: flex; flex-direction: column; justify-content: center; align-items: center; }
.col-data.highlight { color: #ff9800; }

.w-progress { flex: 1; }
.progress-bar-wrapper { width: 100%; max-width: 130px; }
.progress-bar {
  position: relative;
  height: 20px;
  background: #e0e0e0;
  border-radius: 10px;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
}
.progress-bar.no-progress {
  background: #d0d0d0;
}
.progress-fill {
  position: absolute;
  left: 0;
  top: 0;
  height: 100%;
  background: linear-gradient(90deg, #f7971e, #ffd200);
  border-radius: 10px;
  box-shadow: 0 2px 4px rgba(247, 151, 30, 0.4);
}
.progress-text {
  position: relative;
  z-index: 1;
  font-size: 10px;
  color: #333;
  font-weight: 600;
  white-space: nowrap;
  text-shadow: 0 0 2px rgba(255,255,255,0.8);
}

.vip-rules { padding: 20px 15px; color: #666; }
.rules-title { font-size: 14px; margin-bottom: 10px; }
.rules-content p { font-size: 12px; line-height: 1.6; margin-bottom: 8px; text-align: justify; }
</style>
