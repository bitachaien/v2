<template>
  <div class="header-container">
  <van-nav-bar 
    :title="cptitle" 
    @click-left="onClickLeft" 
    @click-right="onClickRight"
    fixed
    placeholder
    safe-area-inset-top
    :border="false"
    class="cyber-nav-bar"
  >
    <template #left>
      <van-icon name="arrow-left" size="18" />
    </template>
    <template #title>
      <div class="game-title" @click.stop="toggleLotteryMenu">
        <span>{{ cptitle }}</span>
        <van-icon name="arrow-down" :class="{ 'is-open': showLotteryMenu }" />
        
        
        <transition name="fade">
          <div v-if="showLotteryMenu" class="lottery-dropdown">
            <div class="dropdown-header">切换彩种</div>
            <div class="dropdown-grid">
              <div 
                v-for="item in lotteryList" 
                :key="item.code" 
                class="lottery-item"
                :class="{ active: item.name === cptitle }"
                @click.stop="selectLottery(item)"
              >
                <span class="name">{{ item.name }}</span>
                <span v-if="item.hot" class="hot-tag">热</span>
                <van-icon v-if="item.name === cptitle" name="success" class="check-icon" />
              </div>
            </div>
          </div>
        </transition>
      </div>
    </template>
    <template #right>
      <van-icon name="wap-nav" size="18" />
    </template>
  </van-nav-bar>
    
    
    <div v-if="showLotteryMenu" class="menu-backdrop" @click="showLotteryMenu = false"></div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { NavBar as VanNavBar, Icon as VanIcon, showToast } from 'vant';
import { k3Api } from '@/api';

const props = defineProps({
  cptitle: {
    type: String,
    default: '快三',
  },
});

const emit = defineEmits(['switch-lottery']);

const router = useRouter();
const showLotteryMenu = ref(false);

const lotteryList = ref([]);

const fetchLotteryList = async () => {
  try {
    const res = await k3Api.getLotteryList();
    if (res.code === 0 && res.data && res.data.length > 0) {
      lotteryList.value = res.data.map((item, index) => ({
        name: item.name,
        code: item.code,
        status: item.status,
        hot: index < 2  // 前两个标记为热门
      }));
    }
  } catch (error) {
    console.error('获取彩种列表失败:', error);
  }
};

const toggleLotteryMenu = () => {
  showLotteryMenu.value = !showLotteryMenu.value;
};

const selectLottery = (item) => {

  if (item.status === 0) {
    showToast('该彩种维护中');
    return;
  }
  
  showLotteryMenu.value = false;
  if (item.name !== props.cptitle) {
    emit('switch-lottery', item);
  }
};

const onClickLeft = () => {
  history.back(-1);
};

const onClickRight = () => {

};

onMounted(() => {
  fetchLotteryList();
});
</script>

<style lang="less" scoped>
@import "@/assets/theme.less";

.header-container {
  
}

.menu-backdrop {
  position: fixed;
  inset: 0;
  z-index: 99; 
  
  background: transparent; 
}

.game-title {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  cursor: pointer;
  padding: 4px 12px;
  border-radius: 16px;
  transition: background 0.3s;
  position: relative; 
  
  &:active {
    background: rgba(255, 255, 255, 0.1);
  }
  
  .van-icon {
    font-size: 12px;
    transition: transform 0.3s;
    
    &.is-open {
      transform: rotate(180deg);
    }
  }
}

.lottery-dropdown {
  position: absolute;
  top: 100%;
  left: 50%;
  transform: translateX(-50%);
  margin-top: 16px;
  width: 260px;
  background: rgba(12, 16, 24, 0.95);
  backdrop-filter: blur(24px);
  border-radius: 12px;
  border: 1px solid rgba(0, 255, 154, 0.1);
  box-shadow: 0 12px 48px rgba(0, 0, 0, 0.6);
  padding: 12px;
  z-index: 200;
  display: flex;
  flex-direction: column;
  
  
  &::before {
    content: '';
    position: absolute;
    top: -6px;
    left: 50%;
    transform: translateX(-50%);
    border-left: 6px solid transparent;
    border-right: 6px solid transparent;
    border-bottom: 6px solid rgba(0, 255, 154, 0.1);
  }
  &::after {
    content: '';
    position: absolute;
    top: -5px;
    left: 50%;
    transform: translateX(-50%);
    border-left: 6px solid transparent;
    border-right: 6px solid transparent;
    border-bottom: 6px solid rgba(12, 16, 24, 0.95);
  }
  
  .dropdown-header {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.4);
    margin-bottom: 8px;
    text-align: center;
    letter-spacing: 1px;
    position: relative;
    
    &::before, &::after {
      content: '';
      position: absolute;
      top: 50%;
      width: 30px;
      height: 1px;
      background: rgba(255, 255, 255, 0.1);
    }
    &::before { right: 60%; }
    &::after { left: 60%; }
  }
  
  .dropdown-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
  }
  
  .lottery-item {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    height: 36px;
    border-radius: 6px;
    background: rgba(255, 255, 255, 0.02);
    border: 1px solid rgba(255, 255, 255, 0.05);
    cursor: pointer;
    transition: all 0.3s;
    position: relative;
    
    .name {
      font-size: 13px;
      color: rgba(255, 255, 255, 0.7);
      font-weight: 500;
      letter-spacing: 0.5px;
    }
    
    .hot-tag {
      font-size: 10px;
      padding: 1px 4px;
      background: linear-gradient(90deg, #FF4757, #FF6B81);
      color: #fff;
      border-radius: 4px;
      font-weight: bold;
    }
    
    .check-icon {
      font-size: 12px;
      color: #00FF9A;
      opacity: 0;
      transform: scale(0.5);
      transition: all 0.2s;
    }
    
    &:hover {
      background: rgba(255, 255, 255, 0.08);
      border-color: rgba(255, 255, 255, 0.1);
    }
    
    &.active {
      background: rgba(0, 255, 154, 0.08);
      border-color: rgba(0, 255, 154, 0.3);
      box-shadow: 0 0 12px rgba(0, 255, 154, 0.1);
      
      .name {
        color: #00FF9A;
        font-weight: bold;
      }
      
      .check-icon {
        opacity: 1;
        transform: scale(1);
      }
    }
  }
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s, transform 0.2s;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateX(-50%) translateY(-10px);
}

:deep(.van-nav-bar) {
  background-color: rgba(18, 24, 37, 0.85) !important;
  backdrop-filter: blur(10px);
  z-index: 100;
  
  
  &::after {
    display: none;
  }
}

:deep(.van-nav-bar__title) {
  font-weight: bold;
  color: #fff;
  font-size: 18px;
  letter-spacing: 1px;
  text-shadow: 0 0 10px rgba(255, 255, 255, 0.3);
  overflow: visible; 
}

:deep(.van-nav-bar .van-icon) {
  color: #fff;
  font-size: 20px;
  text-shadow: 0 0 8px rgba(255, 255, 255, 0.4);
}
</style>
