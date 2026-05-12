<template>
  <div class="dice-animation-overlay">
    <div class="dice-container">
      <div class="rolling-dice" v-for="n in 3" :key="n"></div>
    </div>
    <p class="status-text">开奖中…</p>
  </div>
</template>

<script setup>
</script>

<style lang="less" scoped>
@import "@/assets/theme.less";

.dice-animation-overlay {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  gap: 16px;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(6px);
  z-index: 10;
  color: #fff;
}

.dice-container {
  display: flex;
  gap: 18px;
}

.rolling-dice {
  width: 56px;
  height: 56px;
  background-image: url('@/assets/images/dice_1.png');
  background-size: cover;
  border-radius: 12px;
  animation: rollDice 0.8s infinite steps(1);
  filter: drop-shadow(0 6px 12px rgba(0, 0, 0, 0.4));
}

.rolling-dice:nth-child(2) {
  animation-delay: 0.15s;
}

.rolling-dice:nth-child(3) {
  animation-delay: 0.3s;
}

.status-text {
  font-size: 16px;
  letter-spacing: 2px;
}

@keyframes rollDice {
  0% { background-image: url('@/assets/images/dice_1.png'); transform: rotate(0deg) scale(1); }
  20% { background-image: url('@/assets/images/dice_2.png'); transform: rotate(90deg) scale(1.05); }
  40% { background-image: url('@/assets/images/dice_3.png'); transform: rotate(180deg) scale(0.95); }
  60% { background-image: url('@/assets/images/dice_4.png'); transform: rotate(270deg) scale(1.08); }
  80% { background-image: url('@/assets/images/dice_5.png'); transform: rotate(360deg) scale(0.98); }
  100% { background-image: url('@/assets/images/dice_6.png'); transform: rotate(450deg) scale(1); }
}
</style>
