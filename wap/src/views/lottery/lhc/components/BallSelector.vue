<template>
  <div class="ball-selector">
    <div 
      v-for="num in numbers" 
      :key="num"
      class="ball-item"
      :class="{ selected: isSelected(num) }"
      @click="toggle(num)"
    >
      <div class="ball-content">
        <div class="ball-circle" :class="getColorClass(num)">
          {{ String(num).padStart(2, '0') }}
        </div>
        <span class="odds" v-if="odds">{{ odds }}</span>
      </div>
      <div class="check-mark" v-if="isSelected(num)">
        <van-icon name="success" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { Icon as VanIcon } from 'vant'
import { getNumberColorClass } from '@/utils/lhcData'

const props = defineProps({
  numbers: {
    type: Array,
    default: () => Array.from({ length: 49 }, (_, i) => i + 1)
  },
  selected: {
    type: Array,
    default: () => []
  },
  odds: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['update:selected', 'change'])

const isSelected = (num) => props.selected.includes(num)

const getColorClass = (num) => getNumberColorClass(num)

const toggle = (num) => {
  const newSelected = [...props.selected]
  const index = newSelected.indexOf(num)
  if (index > -1) {
    newSelected.splice(index, 1)
  } else {
    newSelected.push(num)
  }
  emit('update:selected', newSelected)
  emit('change', newSelected)
}
</script>

<style lang="less" scoped>
.ball-selector {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
  padding: 12px;
  
  @media (min-width: 375px) {
      grid-template-columns: repeat(5, 1fr);
  }
}

.ball-item {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  cursor: pointer;
}

.ball-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 100%;
}

.ball-circle {
  width: 44px;
  height: 44px;
  line-height: 42px; // Adjust for border
  text-align: center;
  border-radius: 50%;
  font-size: 18px;
  font-weight: bold;
  transition: all 0.2s ease;
  background: #fff;
  border: 1px solid #e0e0e0;
  color: #333;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);

  &.ssc-fang-hong {
    color: #e1251b;
    border-color: #ffccc7;
    background: #fff1f0;
  }
  &.ssc-fang-lan {
    color: #1890ff;
    border-color: #bae7ff;
    background: #e6f7ff;
  }
  &.ssc-fang-lv {
    color: #52c41a;
    border-color: #d9f7be;
    background: #f6ffed;
  }
}

.ball-item.selected .ball-circle {
  color: #fff;
  border: none;
  line-height: 44px; // No border
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.2);

  &.ssc-fang-hong {
    background: radial-gradient(circle at 30% 30%, #ff6b6b, #e1251b);
    box-shadow: 0 4px 8px rgba(225, 37, 27, 0.3);
  }
  &.ssc-fang-lan {
    background: radial-gradient(circle at 30% 30%, #4dabf7, #1971c2);
    box-shadow: 0 4px 8px rgba(25, 113, 194, 0.3);
  }
  &.ssc-fang-lv {
    background: radial-gradient(circle at 30% 30%, #69db7c, #2f9e44);
    box-shadow: 0 4px 8px rgba(47, 158, 68, 0.3);
  }
}

.odds {
  font-size: 11px;
  color: #999;
  margin-top: 4px;
  transition: color 0.2s;
}

.ball-item.selected .odds {
  color: #e1251b;
  font-weight: bold;
}

.check-mark {
  position: absolute;
  top: 0;
  right: 0;
  width: 16px;
  height: 16px;
  background: #e1251b;
  color: #fff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  transform: translate(25%, -25%);
  border: 2px solid #fff;
  z-index: 2;
}
</style>
