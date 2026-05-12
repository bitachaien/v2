<template>
  <div class="play-selector">
    <div class="selector-header">
      <span>选择玩法</span>
      <van-icon name="cross" @click="$emit('close')" class="close-btn" />
    </div>
    <div class="selector-body">
      <div class="main-plays">
        <div 
          v-for="play in playTypes" 
          :key="play.code"
          class="main-play-item"
          :class="{ active: currentMainPlay === play.code }"
          @click="handleMainPlayChange(play.code)"
        >
          {{ play.name }}
        </div>
      </div>
      <div class="sub-plays">
        <div class="sub-play-grid">
          <div 
            v-for="sub in currentSubPlays" 
            :key="sub.code"
            class="sub-play-item"
            :class="{ active: currentSubPlay === sub.code }"
            @click="handleSubPlayChange(sub)"
          >
            {{ sub.name }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Icon as VanIcon } from 'vant'

const props = defineProps({
  currentMainPlay: {
    type: String,
    required: true
  },
  currentSubPlay: {
    type: String,
    required: true
  }
})

const emit = defineEmits(['update:currentMainPlay', 'update:currentSubPlay', 'close', 'change'])

const playTypes = [
  {
    name: '三星',
    code: 'pl3_3x',
    subs: [
      { name: '直选复式', code: 'pl3zxfs' },
      { name: '直选单式', code: 'pl3zxds' },
      { name: '直选和值', code: 'pl3hzzx' },
      { name: '跨度', code: 'pl3kd' },
      { name: '组选和值', code: 'pl3zuxhz' },
      { name: '组3', code: 'pl3zux3' },
      { name: '组6', code: 'pl3zux6' },
      { name: '混合组选', code: 'pl3zuxhh' },
      { name: '组选包胆', code: 'pl3zuxbd' },
      { name: '组三单式', code: 'pl3zsds' },
      { name: '组六单式', code: 'pl3zlds' }
    ]
  },
  {
    name: '前二',
    code: 'pl3_q2',
    subs: [
      { name: '直选复式', code: 'pl3qx2fs' },
      { name: '直选单式', code: 'pl3qx2ds' },
      { name: '直选和值', code: 'pl3q2zxhz' },
      { name: '跨度', code: 'pl3q2kd' },
      { name: '组选复式', code: 'pl3q2zxfs' },
      { name: '组选单式', code: 'pl3q2zxds' },
      { name: '组选和值', code: 'pl3q2zuxhz' },
      { name: '组选包胆', code: 'pl3q2zxbd' }
    ]
  },
  {
    name: '后二',
    code: 'pl3_h2',
    subs: [
      { name: '直选复式', code: 'pl3hx2fs' },
      { name: '直选单式', code: 'pl3hx2ds' },
      { name: '直选和值', code: 'pl3h2zxhz' },
      { name: '跨度', code: 'pl3h2kd' },
      { name: '组选复式', code: 'pl3h2zxfs' },
      { name: '组选单式', code: 'pl3h2zxds' },
      { name: '组选和值', code: 'pl3h2zuxhz' },
      { name: '组选包胆', code: 'pl3h2zxbd' }
    ]
  },
  {
    name: '一星',
    code: 'pl3_1x',
    subs: [
      { name: '复式', code: 'pl3dwdfs' }
    ]
  },
  {
    name: '大小单双',
    code: 'pl3_dsds',
    subs: [
      { name: '前二大小单双', code: 'dxdsq2' },
      { name: '后二大小单双', code: 'dxdsh2' }
    ]
  }
]

const currentSubPlays = computed(() => {
  const main = playTypes.find(p => p.code === props.currentMainPlay)
  return main ? main.subs : []
})

const handleMainPlayChange = (code) => {
  emit('update:currentMainPlay', code)

  const main = playTypes.find(p => p.code === code)
  if (main && main.subs.length > 0) {
    emit('update:currentSubPlay', main.subs[0].code)
  }
}

const handleSubPlayChange = (sub) => {
  emit('update:currentSubPlay', sub.code)
  emit('change', { main: props.currentMainPlay, sub: sub.code, name: sub.name })
  emit('close')
}
</script>

<style lang="less" scoped>
.play-selector {
  display: flex;
  flex-direction: column;
  height: 100%;
  background: #fff;
}

.selector-header {
  padding: 16px;
  text-align: center;
  font-weight: bold;
  font-size: 16px;
  border-bottom: 1px solid #eee;
  position: relative;
  
  .close-btn {
    position: absolute;
    right: 16px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 20px;
    color: #999;
    cursor: pointer;
  }
}

.selector-body {
  flex: 1;
  display: flex;
  overflow: hidden;
}

.main-plays {
  width: 100px;
  background: #f7f8fa;
  overflow-y: auto;
  
  .main-play-item {
    padding: 16px 8px;
    text-align: center;
    font-size: 14px;
    color: #333;
    cursor: pointer;
    position: relative;
    transition: all 0.2s;
    
    &.active {
      background: #fff;
      color: #1890ff;
      font-weight: bold;
      
      &::before {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 3px;
        height: 16px;
        background: #1890ff;
        border-radius: 0 2px 2px 0;
      }
    }
  }
}

.sub-plays {
  flex: 1;
  padding: 16px;
  overflow-y: auto;
  background: #fff;
  
  .sub-play-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
  }
  
  .sub-play-item {
    padding: 10px;
    text-align: center;
    border: 1px solid #f0f0f0;
    border-radius: 8px;
    font-size: 13px;
    color: #333;
    cursor: pointer;
    transition: all 0.2s;
    background: #fff;
    
    &.active {
      border-color: #1890ff;
      background: #e6f7ff;
      color: #1890ff;
      font-weight: 500;
    }
    
    &:active {
      transform: scale(0.96);
    }
  }
}
</style>
