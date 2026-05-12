<template>
  <div class="liangmian-select">
    <div 
      v-for="(item, index) in options" 
      :key="index"
      class="option-item"
      :class="[
        item.colorClass, 
        { selected: selectedValues.includes(item.value) }
      ]"
      @click="$emit('toggle', item.value)"
    >
      <div class="label">{{ item.label }}</div>
      <div class="odds" v-if="item.rate">{{ item.rate }}</div>
      
      <div class="check-mark" v-if="selectedValues.includes(item.value)">
        <van-icon name="success" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { Icon as VanIcon } from 'vant'

const props = defineProps({
  options: {
    type: Array,
    required: true
  },
  selectedValues: {
    type: Array,
    default: () => []
  },
  playId: {
    type: String,
    required: true
  }
})

defineEmits(['toggle'])
</script>

<style lang="less" scoped>
.liangmian-select {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
  padding: 8px 0;
  
  @media (min-width: 375px) {
    grid-template-columns: repeat(3, 1fr);
  }
}

.option-item {
  position: relative;
  background: #fff;
  border: 1px solid #eee;
  border-radius: 8px;
  padding: 12px 8px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: 0 2px 4px rgba(0,0,0,0.02);
  overflow: hidden;
  
  .label {
    font-size: 15px;
    font-weight: 500;
    color: #333;
    margin-bottom: 4px;
  }
  
  .odds {
    font-size: 12px;
    color: #999;
  }
  

  &.ssc-fang-hong {
    .label { color: #e1251b; }
  }
  &.ssc-fang-lan {
    .label { color: #1890ff; }
  }
  &.ssc-fang-lv {
    .label { color: #52c41a; }
  }
  
  &.selected {
    border-color: #e1251b;
    background-color: #fff1f0;
    box-shadow: 0 4px 8px rgba(225, 37, 27, 0.15);
    transform: translateY(-2px);
    
    .label {
      color: #e1251b;
      font-weight: bold;
    }
    
    .odds {
      color: #e1251b;
    }
  }
  
  .check-mark {
    position: absolute;
    top: 0;
    right: 0;
    width: 16px;
    height: 16px;
    background: #e1251b;
    color: #fff;
    border-bottom-left-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
  }
}
</style>


