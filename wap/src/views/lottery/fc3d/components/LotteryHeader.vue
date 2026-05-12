<template>
  <van-nav-bar fixed placeholder z-index="100" class="lottery-header">
    <template #left>
      <div class="header-left" @click="onClickLeft">
        <van-icon name="arrow-left" size="20" />
      </div>
    </template>
    <template #title>
      <div class="header-title" @click="togglePlayMenu">
        <span class="main-title">{{ title }}</span>
        <div class="sub-title-wrapper" v-if="subtitle">
          <span class="sub-title">{{ subtitle }}</span>
          <van-icon name="arrow-down" size="12" />
        </div>
      </div>
    </template>
    <template #right>
      <div class="header-right" @click="onClickRight">
        <span class="right-text">{{ rightText }}</span>
        <van-icon name="apps-o" size="20" v-if="!rightText" />
      </div>
    </template>
  </van-nav-bar>
</template>

<script setup>
import { NavBar as VanNavBar, Icon as VanIcon } from 'vant'
import { useRouter } from 'vue-router'

const props = defineProps({
  title: {
    type: String,
    default: '福彩3D'
  },
  subtitle: {
    type: String,
    default: ''
  },
  rightText: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['toggle-menu', 'click-right'])
const router = useRouter()

const onClickLeft = () => {
  router.back()
}

const togglePlayMenu = () => {
  emit('toggle-menu')
}

const onClickRight = () => {
  emit('click-right')
}
</script>

<style lang="less" scoped>
.lottery-header {
  :deep(.van-nav-bar) {
    background-color: #e1251b;
  }

  :deep(.van-nav-bar__title) {
    color: #fff;
    max-width: 75%;
  }

  :deep(.van-icon) {
    color: #fff;
  }
  
  :deep(.van-nav-bar__text) {
    color: #fff;
  }
}

.header-left, .header-right {
  height: 100%;
  display: flex;
  align-items: center;
  padding: 0 4px;
  
  &:active {
    opacity: 0.7;
  }
}

.header-title {
  display: flex;
  flex-direction: column;
  align-items: center;
  line-height: 1.2;
  
  .main-title {
    font-size: 16px;
    font-weight: 600;
  }
  
  .sub-title-wrapper {
    display: flex;
    align-items: center;
    gap: 4px;
    opacity: 0.9;
    
    .sub-title {
      font-size: 12px;
      font-weight: normal;
    }
  }
}

.right-text {
  font-size: 14px;
  color: #fff;
}
</style>
