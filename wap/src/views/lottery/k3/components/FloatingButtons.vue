<template>
  <div ref="el" :style="style" class="fab-container" @click.stop="toggleMenu">
    <div class="fab-menu" :class="{ open: isOpen }">
      <div class="menu-item" style="transition-delay: 0.1s" @click="$emit('show-history')">
        <van-icon name="clock-o" />
        <span class="menu-label">历史</span>
      </div>
      <div class="menu-item" style="transition-delay: 0.05s" @click="$emit('toggle-mode')">
        <van-icon :name="toggleIcon" />
        <span class="menu-label">{{ toggleText }}</span>
      </div>
    </div>
    <button class="fab-main" :class="{ open: isOpen }">
      <van-icon name="plus" />
    </button>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useDraggable } from '@vueuse/core';
import { Icon as VanIcon } from 'vant';

const el = ref(null);
const isOpen = ref(false);

const { style } = useDraggable(el, {
  initialValue: { x: window.innerWidth - 80, y: window.innerHeight / 2 - 50 }, // Adjusted for center
});

const toggleMenu = () => {
  isOpen.value = !isOpen.value;
};

defineProps({
  toggleIcon: { type: String, default: 'exchange' },
  toggleText: { type: String, default: '切换' },
});

defineEmits(['show-history', 'toggle-mode']);
</script>

<style lang="less" scoped>
.fab-container {
  position: fixed;
  z-index: 100;
  cursor: grab;
  touch-action: none;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.fab-main {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: linear-gradient(135deg, #00FF9A, #00CC7A);
  color: #000;
  border: none;
  box-shadow: 0 6px 20px rgba(0, 255, 154, 0.4);
  display: flex;
  justify-content: center;
  align-items: center;
  font-size: 24px;
  transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
  z-index: 1;
  
  &:active {
    transform: scale(0.9);
  }

  &.open {
    transform: rotate(45deg);
    background: #FF4757;
    box-shadow: 0 6px 20px rgba(255, 71, 87, 0.4);
    color: #fff;
  }
}

.fab-menu {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;

  .menu-item {
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(23, 30, 46, 0.9);
    padding: 8px 16px;
    border-radius: 99px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(255, 255, 255, 0.1);
    opacity: 0;
    transform: translateY(20px) scale(0.9);
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    cursor: pointer;
    backdrop-filter: blur(8px);

    .van-icon {
      font-size: 18px;
      color: #00FF9A;
    }

    .menu-label {
      font-size: 13px;
      font-weight: 600;
      color: #fff;
    }
    
    &:active {
      transform: scale(0.95);
      background: rgba(255, 255, 255, 0.1);
    }
  }

  &.open .menu-item {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}
</style>
