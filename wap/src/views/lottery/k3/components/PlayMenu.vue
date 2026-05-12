<template>
  <div class="play-menu-panel">
    <van-cell
      is-link
      title="标准玩法"
      :value="currentPlay.name"
      @click="showPlaySelect = true"
    />
    <van-action-sheet
      v-model:show="showPlaySelect"
      :actions="playModes"
      cancel-text="取消"
      close-on-click-action
      @select="onSelectPlayMode"
      title="选择玩法"
    />
    <div class="play-tips">
      <van-notice-bar
        left-icon="volume-o"
        :text="currentPlay.tips"
      />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Cell as VanCell, ActionSheet as VanActionSheet, NoticeBar as VanNoticeBar } from 'vant';

const props = defineProps({
  playModes: {
    type: Array,
    default: () => [],
  },
  currentPlay: {
    type: Object,
    default: () => ({}),
  },
});

const emit = defineEmits(['play-change']);

const showPlaySelect = ref(false);

const onSelectPlayMode = (item) => {
  emit('play-change', item.id);
};
</script>

<style scoped>
.play-menu-panel {
  margin: 16px;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
}

.play-tips {
  margin-top: 12px;
}
</style>
