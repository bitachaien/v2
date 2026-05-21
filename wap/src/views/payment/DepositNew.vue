<template>
  <div class="deposit-new-page">
    
    <div class="bg-layer"></div>

    
    <van-nav-bar
      title="Nạp tiền"
      left-arrow
      @click-left="$router.go(-1)"
      class="nav-header"
      fixed
      placeholder
    >
      <template #right>
        <van-icon name="service-o" size="20" color="#fff" @click="toService" />
      </template>
    </van-nav-bar>

    
    <div class="main-content">
      <div class="deposit-entry" @click="showDeposit = true">
        <div class="entry-icon">
          <van-icon name="gold-coin-o" size="48" color="#04BE02" />
        </div>
        <div class="entry-text">Nhấn để nạp tiền</div>
        <div class="entry-sub">An toàn nhanh chóng · Lên tài khoản tức thì</div>
      </div>

      
      <div class="quick-links">
        <div class="link-item" @click="showDeposit = true; goToHistory()">
          <van-icon name="notes-o" size="24" />
          <span>Lịch sử nạp</span>
        </div>
      </div>
    </div>

    
    <DepositPopup v-model:show="showDeposit" ref="depositPopupRef" />
  </div>
</template>

<script setup>
import { ref, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import DepositPopup from '@/components/deposit/DepositPopup.vue'

const router = useRouter()
const showDeposit = ref(false)
const depositPopupRef = ref(null)

const toService = () => {
  router.push('/service')
}

const goToHistory = () => {

  nextTick(() => {
    if (depositPopupRef.value) {

    }
  })
}
</script>

<style scoped>
.deposit-new-page {
  min-height: 100vh;
  background: #0f1419;
  position: relative;
}

.bg-layer {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  height: 300px;
  background: linear-gradient(180deg, rgba(4, 190, 2, 0.15) 0%, transparent 100%);
  pointer-events: none;
}

.nav-header {
  --van-nav-bar-background: transparent;
  --van-nav-bar-title-text-color: #fff;
  --van-nav-bar-icon-color: #fff;
  --van-border-color: transparent;
}

.main-content {
  padding: 60px 20px 40px;
  position: relative;
  z-index: 1;
}

.deposit-entry {
  background: linear-gradient(145deg, #1a1f26, #252b35);
  border: 1px solid rgba(4, 190, 2, 0.2);
  border-radius: 16px;
  padding: 50px 20px;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
}

.deposit-entry:active {
  transform: scale(0.98);
  border-color: rgba(4, 190, 2, 0.5);
}

.entry-icon {
  margin-bottom: 20px;
}

.entry-text {
  font-size: 20px;
  color: #fff;
  font-weight: 600;
  margin-bottom: 8px;
}

.entry-sub {
  font-size: 14px;
  color: #666;
}

.quick-links {
  display: flex;
  justify-content: center;
  gap: 40px;
  margin-top: 40px;
}

.link-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  color: #888;
  cursor: pointer;
  transition: color 0.2s;
}

.link-item:active {
  color: #04BE02;
}

.link-item span {
  font-size: 12px;
}
</style>
