<template>
  <van-nav-bar 
    @click-left="onClickLeft" 
    @click-right="onClickRight"
    :border="false"
  >
    <template #left>
      <van-icon name="arrow-left" size="20" color="#fff" />
    </template>
    <template #title>
      <div class="game-title">
        <span class="title-text">{{ cptitle }}</span>
        <van-icon name="arrow-down" size="12" color="#fff" />
      </div>
    </template>
    <template #right>
      <van-icon name="wap-nav" size="20" color="#fff" />
    </template>
  </van-nav-bar>
</template>

<script setup>
import { NavBar as VanNavBar, Icon as VanIcon } from 'vant';

defineProps({
  cptitle: {
    type: String,
    default: '时时彩',
  },
});

const onClickLeft = () => {
  history.back(-1);
};

const onClickRight = () => {
};
</script>

<style lang="less" scoped>
@import "@/assets/theme.less";

.game-title {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  cursor: pointer;
  
  .title-text {
    font-size: 17px;
    font-weight: 600;
    color: #fff;
    letter-spacing: 1px;
  }
}

:deep(.van-nav-bar) {
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
  height: 46px;
}

:deep(.van-nav-bar__content) {
  height: 46px;
}

:deep(.van-nav-bar__title) {
  max-width: none;
  overflow: visible;
}
</style>


