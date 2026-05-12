<template>
  <div class="danshi">
    <div class="danshi-container">
      <div class="input-area">
        <van-field
          v-model="text"
          rows="8"
          autosize
          type="textarea"
          :placeholder="`每注${numberLength}位数字，空格或换行分隔`"
          show-word-limit
        />
      </div>
      <div class="btn-row">
        <van-button size="small" type="primary" @click="check">检查格式</van-button>
        <van-button size="small" type="warning" @click="removeErrors">删除错误</van-button>
        <van-button size="small" @click="clear">清空</van-button>
      </div>
      <div class="result">格式正确：<b>{{ validList.length }}</b> 注</div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({ 
  numberLength: { type: Number, default: 5 },
  playType: { type: String, default: '' }
})
const emits = defineEmits(['update:betCount', 'update:selectedData'])

const text = ref('')
const validList = ref([])

function check() {
  const len = props.numberLength || 5
  const content = text.value || ''

  const tokens = content.trim().split(/[\s,;]+/).filter(Boolean)
  
  const list = []
  const seen = new Set()
  
  const isZ3 = ['qszsds','zszsds','hszsds'].includes(props.playType) // Zu 3
  const isZ6 = ['qszlds','zszlds','hszlds'].includes(props.playType) // Zu 6
  const isG2 = ['exzuxdsq','exzuxdsh'].includes(props.playType)     // Group 2
  
  try {
    const regex = new RegExp(`^\\d{${len}}$`)
    
    for (const t of tokens) {
       if (!regex.test(t)) continue
       

       if (isZ3) {

         const s = new Set(t.split(''))
         if (s.size !== 2) continue
       } else if (isZ6) {

         const s = new Set(t.split(''))
         if (s.size !== 3) continue
       } else if (isG2) {

         const s = new Set(t.split(''))
         if (s.size !== 2) continue
       }
       

       let key = t
       if (isZ3 || isZ6 || isG2) {
         key = t.split('').sort().join('')
       }
       
       if (!seen.has(key)) {
         seen.add(key)
         list.push(t) // Keep original input or normalized? Usually keep original or normalized. Let's keep normalized for group to avoid confusion.
       }
    }
    validList.value = list
  } catch (e) {
    console.error('Invalid regex or length:', len, e)
    validList.value = []
  }
  sync()
}
function removeErrors() { check() }
function clear() { text.value = ''; validList.value = []; sync() }

function sync() {
  emits('update:betCount', validList.value.length)
  emits('update:selectedData', [...validList.value])
}

defineExpose({ clear })

watch(() => props.numberLength, () => check())
</script>

<style lang="less" scoped>
@import "@/assets/theme.less";

.danshi {
  padding: 0;
}

.danshi-container {
  background: rgba(23, 30, 46, 0.6);
  border-radius: 12px;
  padding: 16px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  backdrop-filter: blur(10px);
}

.input-area {
  margin-bottom: 16px;
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 8px;
  overflow: hidden;
  background: rgba(0, 0, 0, 0.2);
  
  :deep(.van-field) {
    background: transparent;
    .van-field__control {
      color: #fff;
      &::placeholder { color: rgba(255, 255, 255, 0.3); }
    }
  }
}

.btn-row {
  display: flex;
  gap: 12px;
  margin-bottom: 16px;
  
  :deep(.van-button) {
    flex: 1;
    border-radius: 8px;
    font-weight: 500;
    background: rgba(0, 255, 154, 0.15);
    border-color: rgba(0, 255, 154, 0.3);
    color: #00FF9A;
    
    &--primary {
      background: linear-gradient(135deg, #00FF9A, #00cc7a);
      color: #000;
      border: none;
    }
    &--warning {
      background: rgba(255, 193, 7, 0.15);
      border-color: rgba(255, 193, 7, 0.3);
      color: #FFC107;
    }
    &--default {
      background: rgba(255, 255, 255, 0.1);
      border-color: rgba(255, 255, 255, 0.2);
      color: rgba(255, 255, 255, 0.8);
    }
  }
}

.result {
  background: rgba(0, 255, 154, 0.05);
  border: 1px solid rgba(0, 255, 154, 0.1);
  padding: 12px;
  border-radius: 8px;
  font-size: 13px;
  color: rgba(255, 255, 255, 0.6);
  display: flex;
  align-items: center;
  justify-content: space-between;
  
  b {
    color: #00FF9A;
    font-size: 16px;
    margin: 0 4px;
  }
}
</style>



