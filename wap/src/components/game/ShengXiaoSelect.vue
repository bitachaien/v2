<template>
  <div class="shengxiao-select">
    <div 
      v-for="(item, index) in options" 
      :key="index"
      class="sx-item"
      :class="{ selected: selectedValues.includes(item.value) }"
      @click="$emit('toggle', item.value)"
    >
      <div class="header">
        <span class="label">{{ item.label }}</span>
        <span class="odds" v-if="item.rate">{{ item.rate }}</span>
      </div>
      
      <div class="numbers" v-if="item.numbers">
        <span 
          v-for="num in item.numbers.split(' ')" 
          :key="num"
          class="num-tag"
          :class="getNumberColorClass(parseInt(num))"
        >
          {{ num }}
        </span>
      </div>
      
      <div class="check-mark" v-if="selectedValues.includes(item.value)">
        <van-icon name="success" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { Icon as VanIcon } from 'vant'
import { getNumberColorClass } from '@/utils/lhcData'

defineProps({
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
.shengxiao-select {
  display: grid;
  grid-template-columns: repeat(1, 1fr);
  gap: 12px;
  padding: 8px 0;
  
  @media (min-width: 375px) {
    grid-template-columns: repeat(2, 1fr);
  }
}

.sx-item {
  position: relative;
  background: #fff;
  border: 1px solid #eee;
  border-radius: 8px;
  padding: 12px;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: 0 2px 4px rgba(0,0,0,0.02);
  
  .header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
    
    .label {
      font-size: 16px;
      font-weight: bold;
      color: #333;
    }
    
    .odds {
      font-size: 12px;
      color: #e1251b;
      font-weight: 500;
    }
  }
  
  .numbers {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    
    .num-tag {
      display: inline-block;
      width: 20px;
      height: 20px;
      line-height: 20px;
      text-align: center;
      border-radius: 50%;
      font-size: 10px;
      color: #fff;
      background: #ccc;
      
      &.ssc-fang-hong { background: #ff6b6b; }
      &.ssc-fang-lan { background: #4dabf7; }
      &.ssc-fang-lv { background: #69db7c; }
    }
  }
  
  &.selected {
    border-color: #e1251b;
    background-color: #fff1f0;
    box-shadow: 0 4px 8px rgba(225, 37, 27, 0.15);
    transform: translateY(-2px);
    
    .label {
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


