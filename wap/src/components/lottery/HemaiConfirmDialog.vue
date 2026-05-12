<template>
  <teleport to="body">
    <div v-if="show" class="hsycms-model-mask" @click="$emit('close')"></div>
    <div v-if="show" class="hsycms-model hsycms-model-confirm" id="confirm">
      <div class="hscysm-model-title">确认投注</div>
      <div class="hsycms-model-text1" v-safe-html="message"></div>
      <div class="hsycms-model-btn confirm">
        <button type="button" @click="$emit('close')">取消</button>
        <button type="button" @click="$emit('confirm')">确定</button>
      </div>
    </div>
  </teleport>
</template>

<script setup>
import { computed } from 'vue'
import { sanitizeHtml } from '@/utils/sanitize'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  message: {
    type: String,
    default: ''
  }
})

defineEmits(['close', 'confirm'])
</script>

<style scoped>

.hemai-confirm-dialog {
  padding: 20px;
}

.hemai-confirm-dialog h3 {
  margin-bottom: 16px;
  font-size: 18px;
  font-weight: 600;
}

.hemai-confirm-dialog p {
  margin-bottom: 12px;
  line-height: 1.6;
  color: #666;
}
</style>

