<template>
  <van-nav-bar fixed placeholder z-index="100">
    <template #left>
      <van-icon name="arrow-left" size="18" @click="onClickLeft" />
    </template>
    <template #title>
      <div class="game-title" @click="togglePlayMenu">
        <span>{{ title }}</span>
        <span class="subtitle" v-if="subtitle">- {{ subtitle }}</span>
        <van-icon name="arrow-down" size="14" />
      </div>
    </template>
    <template #right>
      <van-icon name="wap-nav" size="18" @click="onClickRight" />
    </template>
  </van-nav-bar>
</template>

<script setup>
import { NavBar as VanNavBar, Icon as VanIcon } from 'vant'
import { useRouter } from 'vue-router'

const props = defineProps({
  title: {
    type: String,
    default: '六合彩'
  },
  subtitle: {
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
.game-title {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  cursor: pointer;
  
  .subtitle {
    font-size: 12px;
    font-weight: normal;
  }
}

:deep(.van-nav-bar) {
  background-color: #e1251b; // LHC typically uses red header
}

:deep(.van-nav-bar__title) {
  color: #fff;
  max-width: 70%;
}

:deep(.van-icon) {
  color: #fff;
}
</style>
