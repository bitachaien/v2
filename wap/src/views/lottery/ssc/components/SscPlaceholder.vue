<template>
  <div class="placeholder">
    <div class="hint">该玩法暂未实现注数计算，已为您占位显示。</div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
const emits = defineEmits(['update:betCount', 'update:selectedData'])
function clear() { emits('update:betCount', 0); emits('update:selectedData', null) }
defineExpose({ clear })
onMounted(() => { clear() })
</script>

<style lang="less" scoped>
@import "@/assets/theme.less";

.placeholder { 
  background: rgba(23, 30, 46, 0.6);
  border-radius: 12px;
  padding: 24px 16px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  backdrop-filter: blur(10px);
}

.hint { 
  color: rgba(255, 255, 255, 0.5); 
  font-size: 14px;
  text-align: center;
}
</style>










