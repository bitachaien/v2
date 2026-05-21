<template>
  <div class="security-page">
    <van-nav-bar
      title="Xác thực danh tính"
      left-arrow
      @click-left="onClickLeft"
      class="tech-header"
    />

    <div class="content-body fade-up">
      <div class="glass-card form-container">
        <div class="card-header">
          <van-icon name="manager-o" class="header-icon" />
          <span class="header-title">Xác minh thông tin cá nhân</span>
        </div>
        
        <van-form @submit="onSubmit">
          
          <div class="input-group">
            <div class="label">Họ tên thật</div>
            <van-field
              v-model="formData.realName"
              name="realName"
              placeholder="Nhập họ tên thật của bạn"
              :rules="[{ required: true, message: 'Vui lòng nhập họ tên thật' }]"
              class="tech-input"
              :disabled="isVerified"
            >
              <template #left-icon>
                <van-icon name="user-o" />
              </template>
            </van-field>
          </div>

          
           <div class="input-group">
            <div class="label">Loại giấy tờ</div>
            <van-field
              v-model="formData.idType"
              is-link
              readonly
              name="idType"
              placeholder="Chọn loại giấy tờ"
              @click="showPicker = !isVerified"
              class="tech-input"
              :disabled="isVerified"
            >
             <template #left-icon>
                <van-icon name="card" />
              </template>
            </van-field>
            <van-popup v-model:show="showPicker" position="bottom" round class="tech-popup">
              <van-picker
                :columns="idTypeColumns"
                @confirm="onConfirmIdType"
                @cancel="showPicker = false"
              />
            </van-popup>
          </div>

          
          <div class="input-group">
            <div class="label">Số giấy tờ</div>
            <van-field
              v-model="formData.idNumber"
              name="idNumber"
              placeholder="Nhập số giấy tờ"
              :rules="[{ required: true, message: 'Vui lòng nhập số giấy tờ' }]"
              class="tech-input"
              :disabled="isVerified"
            >
              <template #left-icon>
                <van-icon name="idcard" />
              </template>
            </van-field>
          </div>
          
          
          <div class="input-group" v-if="!isVerified">
            <div class="label">Mật khẩu rút tiền</div>
            <van-field
              v-model="formData.fundPwd"
              type="password"
              name="fundPwd"
              placeholder="Xác minh mật khẩu rút tiền"
              :rules="[{ required: true, message: 'Vui lòng nhập mật khẩu rút tiền' }]"
              class="tech-input"
            >
              <template #left-icon>
                <van-icon name="lock" />
              </template>
            </van-field>
          </div>

          <div class="submit-area">
            <van-button 
              round 
              block 
              type="primary" 
              native-type="submit"
              class="tech-btn"
              :loading="loading"
              :disabled="isVerified"
            >
              {{ isVerified ? 'Đã xác thực' : 'Gửi xác thực' }}
            </van-button>
          </div>
        </van-form>

        <div class="privacy-tip">
          <p>Xác thực danh tính dùng để rút tiền và khôi phục tài khoản, nền tảng cam kết bảo vệ nghiêm ngặt quyền riêng tư của bạn.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import { showToast } from 'vant';

const router = useRouter();
const onClickLeft = () => router.go(-1);

const isVerified = ref(false);
const loading = ref(false);

const formData = reactive({
  realName: '',
  idType: 'CMND/CCCD',
  idNumber: '',
  fundPwd: ''
});

const showPicker = ref(false);
const idTypeColumns = [
  { text: 'CMND/CCCD', value: 'id_card' },
  { text: 'Hộ chiếu', value: 'passport' },
  { text: 'Bằng lái xe', value: 'driver_license' }
];

const onConfirmIdType = ({ selectedOptions }) => {
  formData.idType = selectedOptions[0].text;
  showPicker.value = false;
};

const onSubmit = (values) => {
  loading.value = true;
  setTimeout(() => {
    loading.value = false;
    showToast('认证提交成功');
    isVerified.value = true;
  }, 1500);
};
</script>

<style scoped lang="less">
@bg-dark: linear-gradient(135deg, #05070E 0%, #0B0E15 100%);
@card-bg: rgba(15, 20, 35, 0.78);
@primary-gold: #EAC26E;
@neon-blue: #25F3FF;
@text-white: #E7ECFF;
@text-gray: #98A0C6;
@input-bg: #111624;

.security-page {
  min-height: 100vh;
  background: @bg-dark;
  color: @text-white;
  padding-top: 46px;
}

.tech-header {
  background: rgba(11, 14, 21, 0.9);
  backdrop-filter: blur(10px);
  border-bottom: 1px solid rgba(255,255,255,0.05);
  :deep(.van-nav-bar__title) { color: @primary-gold; }
  :deep(.van-icon) { color: @text-white; }
}

.content-body {
  padding: 20px 16px;
}

.glass-card {
  background: @card-bg;
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 20px;
  padding: 24px 20px;
  box-shadow: 0 12px 30px rgba(0, 0, 0, 0.6);
}

.card-header {
  display: flex;
  align-items: center;
  margin-bottom: 24px;
  
  .header-icon {
    font-size: 24px;
    color: @primary-gold;
    margin-right: 10px;
  }
  
  .header-title {
    font-size: 18px;
    font-weight: 600;
    color: @text-white;
  }
}

.input-group {
  margin-bottom: 20px;
  
  .label {
    font-size: 14px;
    color: @text-gray;
    margin-bottom: 8px;
    padding-left: 4px;
  }
}

.tech-input {
  background: @input-bg;
  border-radius: 12px;
  border: 1px solid transparent;
  padding: 12px 16px;
  transition: all 0.3s;

  &:focus-within {
    border-color: fade(@neon-blue, 50%);
    box-shadow: 0 0 10px fade(@neon-blue, 10%);
  }

  :deep(.van-field__control) {
    color: @text-white;
    font-size: 15px;
    &::placeholder {
      color: darken(@text-gray, 20%);
    }
  }
  
  :deep(.van-icon) {
    color: @primary-gold;
    font-size: 18px;
    margin-right: 8px;
  }
}

.tech-btn {
  background: linear-gradient(90deg, #D4AF37 0%, #F6D365 100%);
  border: none;
  color: #000;
  font-weight: 600;
  font-size: 16px;
  height: 48px;
  margin-top: 20px;
  box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
  
  &:active {
    transform: scale(0.98);
  }
  
  &[disabled] {
    background: #333;
    color: #666;
    box-shadow: none;
  }
}

.privacy-tip {
  margin-top: 20px;
  text-align: center;
  
  p {
    font-size: 12px;
    color: darken(@text-gray, 10%);
    line-height: 1.6;
  }
}

.fade-up {
  animation: fadeUp 0.5s ease-out forwards;
}
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

.tech-popup {
  background: #1e2330;
  :deep(.van-picker) {
    background: transparent;
    color: white;
  }
  :deep(.van-picker__mask) {
    background-image: linear-gradient(180deg, rgba(30,35,48,0.9), rgba(30,35,48,0.4)), linear-gradient(0deg, rgba(30,35,48,0.9), rgba(30,35,48,0.4));
  }
  :deep(.van-picker-column__item) {
    color: @text-gray;
  }
  :deep(.van-picker-column__item--selected) {
    color: @primary-gold;
  }
}
</style>
