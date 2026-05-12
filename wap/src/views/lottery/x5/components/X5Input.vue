<template>
  <div class="x5-input">
    <div class="input-box">
      <div class="box-header">
        <div class="title">单式录入</div>
        <div class="desc">每注号码之间请用空格、逗号或分号隔开 <span class="odds">赔率: 19.6</span></div>
      </div>
      
      <div class="helper-bar">
        <span>格式示例:</span>
        <span class="code-sample">01 02 03 04 05</span>
        <span class="code-sample">06,07,08,09,10</span>
        <a class="use-sample" @click="useSample">试一试</a>
      </div>

      <textarea
        v-model="content"
        class="number-input"
        :placeholder="placeholderText"
        @input="onInput"
      ></textarea>
      
      <div class="input-footer">
        <div class="info" v-if="errorMsg">{{ errorMsg }}</div>
        <div class="info" v-else>已识别 {{ validBets }} 注</div>
        <div class="clean-btn" @click="clear">清空文本</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({

  playType: { type: String, default: 'rx5' },
  len: { type: Number, default: 5 },
  isZux: { type: Boolean, default: false }
})

const emit = defineEmits(['update:betCount', 'update:selectedData'])

const content = ref('')
const validBets = ref(0)
const errorMsg = ref('')

const placeholderText = computed(() => {
    return `请输入号码，每注${props.len}个号码。\n支持空格、逗号、分号分隔。\n例如：\n01 02 03 04 05\n06 07 08 09 10`
})

function useSample() {
    const samples = []
    for(let i=0; i<3; i++) {
        const arr = []
        while(arr.length < props.len) {
            const n = Math.floor(Math.random() * 11) + 1
            if (!arr.includes(n)) arr.push(n)
        }
        arr.sort((a,b) => a-b)
        samples.push(arr.map(n => n<10?'0'+n:''+n).join(' '))
    }
    content.value = samples.join('\n')
    calc()
}

function onInput() {
  calc()
}

function calc() {
  if (!content.value.trim()) {
    validBets.value = 0
    errorMsg.value = ''
    emit('update:betCount', 0)
    emit('update:selectedData', '')
    return
  }

  
  const rawText = content.value
  const allNums = []

  const matches = rawText.match(/\b(0?[1-9]|1[0-1])\b/g) || []
  
  for (const m of matches) {
      const n = parseInt(m)
      allNums.push(n < 10 ? '0'+n : ''+n)
  }
  
  const bets = []
  let err = ''
  
  for (let i = 0; i < allNums.length; i += props.len) {
    if (i + props.len <= allNums.length) {
      const chunk = allNums.slice(i, i + props.len)
      

      const unique = new Set(chunk)
      if (unique.size !== props.len) {

          continue
      }
      
      if (props.isZux) {
         chunk.sort()
      }
      bets.push(chunk.join(','))
    }
  }
  
  if (bets.length === 0 && content.value.trim().length > 0) {
      err = '格式不正确或号码重复'
  }
  
  validBets.value = bets.length
  errorMsg.value = err
  
  emit('update:betCount', bets.length)
  emit('update:selectedData', bets.join(';'))
}

function clear() {
  content.value = ''
  calc()
}

defineExpose({ clear })
</script>

<style lang="less" scoped>
@import "@/assets/theme.less";

.x5-input {
  padding: 12px 16px;
}

.input-box {
  background: #fff;
  border-radius: 12px;
  padding: 16px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

.box-header {
  margin-bottom: 12px;
  .title { font-size: 14px; font-weight: 600; color: #333; margin-bottom: 4px; }
  .desc { 
      font-size: 12px; color: #999; 
      .odds { color: #ff976a; margin-left: 6px; font-weight: 500; }
  }
}

.helper-bar {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 10px;
    font-size: 12px;
    color: #666;
    
    .code-sample {
        background: #f5f6f7;
        padding: 2px 6px;
        border-radius: 4px;
        font-family: monospace;
        color: #333;
    }
    
    .use-sample {
        color: @primary-color;
        cursor: pointer;
        margin-left: auto;
    }
}

.number-input {
  width: 100%;
  height: 140px;
  border: 1px solid #ebedf0;
  border-radius: 8px;
  padding: 12px;
  font-size: 14px;
  line-height: 1.5;
  font-family: monospace;
  resize: none;
  background: #f9f9f9;
  outline: none;
  transition: all 0.2s;
  
  &:focus {
    border-color: @primary-color;
    background: #fff;
  }
}

.input-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 12px;
  
  .info {
      font-size: 12px;
      color: @primary-color;
      font-weight: 500;
  }
  
  .clean-btn {
    font-size: 13px;
    color: #666;
    cursor: pointer;
    padding: 4px 8px;
    background: #f5f6f7;
    border-radius: 4px;
    &:hover { background: #ebedf0; }
  }
}
</style>
