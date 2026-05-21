<template>
  <div class="v5-security-question">
    <van-nav-bar
      title="Câu hỏi bảo mật"
      left-arrow
      @click-left="onClickLeft"
      class="custom-nav"
    />

    <div class="content">
      <div class="label">Thay đổi câu hỏi bảo mật</div>
      
      
      <div class="input-container select-trigger" @click="showPicker = true">
        <van-icon name="shield-o" class="prefix-icon" />
        <div class="select-text" :class="{ 'placeholder': !selectedQuestion }">
          {{ selectedQuestion || 'Chọn câu hỏi bảo mật' }}
        </div>
        <van-icon name="arrow-down" class="suffix-icon" />
      </div>

      
      <div class="input-container mt-12">
        <van-icon name="key-o" class="prefix-icon" />
        <input 
          type="text" 
          v-model="answer" 
          placeholder="Nhập câu trả lời bảo mật"
          class="custom-input"
        />
      </div>

      
      <div class="input-container mt-12">
        <van-icon name="key-o" class="prefix-icon" />
        <input 
          type="text" 
          v-model="confirmAnswer" 
          placeholder="Nhập lại câu trả lời bảo mật"
          class="custom-input"
        />
      </div>
    </div>

    <div class="bottom-area">
      <van-button block color="#009688" class="submit-btn" @click="onSubmit" :loading="submitting">Xác nhận</van-button>
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
    showToast('Vui lòng chọn câu hỏi bảo mật')
    return
  }
  if (!answer.value) {
    showToast('Vui lòng nhập câu trả lời')
    return
  }
  if (!confirmAnswer.value) {
    showToast('Vui lòng nhập lại câu trả lời')
    return
  }
  if (answer.value !== confirmAnswer.value) {
    showToast('Hai lần nhập không khớp')
    return
  }

  submitting.value = true
  try {
    const res = await securityApi.setQuestion({ question: selectedQuestion.value, answer: answer.value })
    if (res.code === 0) {
      showToast('Thiết lập thành công')
      setTimeout(() => router.back(), 1000)
    } else {
      showToast(res.message || 'Thiết lập thất bại')
    }
  } catch (e) {
    showToast(e.message || 'Thiết lập thất bại')
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
