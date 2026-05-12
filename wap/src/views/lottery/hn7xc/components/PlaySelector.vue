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
    name: '四字定',
    code: 'hn7xc_4x',
    subs: [
      { name: '直选复式', code: 'qxc4zxfs' },
      { name: '直选单式', code: 'qxc4zxds' }
    ]
  },
  {
    name: '三字定',
    code: 'hn7xc_3x',
    subs: [
      { name: '前三(千百十)', code: 'qxc3q3' },
      { name: '中三(千百个)', code: 'qxc3qbg' },
      { name: '后三(百十个)', code: 'qxc3h3' },
      { name: '其他(千十个)', code: 'qxc3qsg' }
    ]
  },
  {
    name: '二字定',
    code: 'hn7xc_2x',
    subs: [
      { name: '千百(前二)', code: 'qxc2q2' },
      { name: '千十', code: 'qxc2qs' },
      { name: '千个', code: 'qxc2qg' },
      { name: '百十', code: 'qxc2bs' },
      { name: '百个', code: 'qxc2bg' },
      { name: '十个(后二)', code: 'qxc2h2' }
    ]
  },
  {
    name: '一字定',
    code: 'hn7xc_1x',
    subs: [
      { name: '定位', code: 'qxcdwd' }
    ]
  },
  {
    name: '不定位',
    code: 'hn7xc_bdw',
    subs: [
      { name: '二码不定位', code: 'qxc2mbdw' },
      { name: '三码不定位', code: 'qxc3mbdw' }
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
    
    &.active {
      background: #fff;
      color: #e1251b;
      font-weight: bold;
      
      &::before {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 3px;
        height: 16px;
        background: #e1251b;
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
    
    &.active {
      border-color: #e1251b;
      background: #fff1f0;
      color: #e1251b;
      font-weight: bold;
    }
    
    &:active {
      transform: scale(0.98);
    }
  }
}
</style>
