<template>
  <div class="v5-security-question">
    <van-nav-bar
      title="密保问题"
      left-arrow
      @click-left="onClickLeft"
      class="custom-nav"
    />

    <div class="content">
      <div class="label">修改密保问题</div>
      
      
      <div class="input-container select-trigger" @click="showPicker = true">
        <van-icon name="shield-o" class="prefix-icon" />
        <div class="select-text" :class="{ 'placeholder': !selectedQuestion }">
          {{ selectedQuestion || '请选择密保问题' }}
        </div>
        <van-icon name="arrow-down" class="suffix-icon" />
      </div>

      
      <div class="input-container mt-12">
        <van-icon name="key-o" class="prefix-icon" />
        <input 
          type="text" 
          v-model="answer" 
          placeholder="请输入密保答案" 
          class="custom-input"
        />
      </div>

      
      <div class="input-container mt-12">
        <van-icon name="key-o" class="prefix-icon" />
        <input 
          type="text" 
          v-model="confirmAnswer" 
          placeholder="请再次输入密保答案" 
          class="custom-input"
        />
      </div>
    </div>

    <div class="bottom-area">
      <van-button block color="#009688" class="submit-btn" @click="onSubmit" :loading="submitting">确定</van-button>
    </div>

    
    <van-popup v-model:show="showPicker" round position="bottom">
      <van-picker
        :columns="questionOptions"
        @cancel="showPicker = false"
        @confirm="onConfirmQuestion"
      />
    </van-popup>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { showToast } from 'vant'
import { securityApi } from '@/api/security'

const router = useRouter()

const answer = ref('')
const confirmAnswer = ref('')
const showPicker = ref(false)
const selectedQuestion = ref('')
const submitting = ref(false)

const questionOptions = ref([])

const loadQuestions = async () => {
  try {
    const res = await securityApi.getQuestionList()
    if (res.code === 0 && res.data) {
      questionOptions.value = res.data.map(q => ({ text: q.question, value: q.id }))
      if (questionOptions.value.length > 0) {
        selectedQuestion.value = questionOptions.value[0].text
      }
    }
  } catch (e) {
  }
}

onMounted(() => loadQuestions())

const onClickLeft = () => {
  router.back()
}

const onConfirmQuestion = ({ selectedOptions }) => {
  selectedQuestion.value = selectedOptions[0].text
  showPicker.value = false
}

const onSubmit = async () => {
  if (!selectedQuestion.value) {
    showToast('请选择密保问题')
    return
  }
  if (!answer.value) {
    showToast('请输入密保答案')
    return
  }
  if (!confirmAnswer.value) {
    showToast('请再次输入密保答案')
    return
  }
  if (answer.value !== confirmAnswer.value) {
    showToast('两次输入的答案不一致')
    return
  }

  submitting.value = true
  try {
    const res = await securityApi.setQuestion({ question: selectedQuestion.value, answer: answer.value })
    if (res.code === 0) {
      showToast('设置成功')
      setTimeout(() => router.back(), 1000)
    } else {
      showToast(res.message || '设置失败')
    }
  } catch (e) {
    showToast(e.message || '设置失败')
  } finally {
    submitting.value = false
  }
}
</script>

<style scoped>
.v5-security-question {
  min-height: 100vh;
  background: #f7f8fa;
  font-family: -apple-system, BlinkMacSystemFont, "PingFang SC", "Helvetica Neue", Arial, sans-serif;
  display: flex;
  flex-direction: column;
}

.custom-nav {
  background: #fff;
}
:deep(.van-nav-bar__title) {
  font-weight: 500;
  font-size: 17px;
}
:deep(.van-icon-arrow-left) {
  color: #333;
  font-size: 20px;
}

.content {
  padding: 20px 16px;
  flex: 1;
}

.label {
  font-size: 14px;
  color: #333;
  margin-bottom: 12px;
}

.mt-12 { margin-top: 12px; }

.input-container {
  background: #fff;
  border-radius: 8px;
  border: 1px solid #e0e0e0;
  display: flex;
  align-items: center;
  height: 48px;
  padding: 0 12px;
}

.select-trigger {
  cursor: pointer;
}

.prefix-icon {
  font-size: 20px;
  color: #999;
  margin-right: 10px;
}

.suffix-icon {
  font-size: 16px;
  color: #ccc;
}

.custom-input {
  flex: 1;
  border: none;
  outline: none;
  font-size: 15px;
  color: #333;
}
.custom-input::placeholder { color: #ccc; }

.select-text {
  flex: 1;
  font-size: 15px;
  color: #333;
}
.select-text.placeholder {
  color: #ccc;
}

.bottom-area {
  padding: 20px 16px;
  background: #fff;
  border-top: 1px solid #f5f5f5;
}

.submit-btn {
  height: 44px;
  font-size: 16px;
  border-radius: 6px;
}
</style>
