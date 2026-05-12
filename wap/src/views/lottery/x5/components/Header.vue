<template>
  <van-nav-bar :title="cptitle" @click-left="onClickLeft" @click-right="onClickRight">
    <template #left>
      <van-icon name="arrow-left" size="18" />
    </template>
    <template #title>
      <div class="game-title">
        <span>{{ cptitle }}</span>
        <van-icon name="arrow-down" />
      </div>
    </template>
    <template #right>
      <van-icon name="wap-nav" size="18" />
    </template>
  </van-nav-bar>
</template>

<script setup>
import { ref } from 'vue';
import { NavBar as VanNavBar, Icon as VanIcon } from 'vant';

const props = defineProps({
  cptitle: {
    type: String,
    default: '11选5',
  },
});

const showGameMenu = ref(false);

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
  gap: 4px;
  cursor: pointer;
  color: @header-text-color;
  font-size: 16px;
  font-weight: bold;
}

:deep(.van-nav-bar) {
  background-color: @header-background-color;
}

:deep(.van-nav-bar__title) {
  font-weight: bold;
  color: @header-text-color;
}

:deep(.van-nav-bar .van-icon) {
  color: @header-icon-color;
}
</style>
