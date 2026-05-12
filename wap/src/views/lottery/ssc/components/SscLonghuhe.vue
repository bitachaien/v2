<template>
  <div class="longhuhe">
    <div class="hint">龙虎和玩法占位（后续可按需求补充）</div>
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

.longhuhe {
  background: rgba(23, 30, 46, 0.6);
  border-radius: 12px;
  padding: 16px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  backdrop-filter: blur(10px);
}

.hint { 
  padding: 16px; 
  color: rgba(255, 255, 255, 0.5); 
  font-size: 14px;
  text-align: center;
}
</style>



