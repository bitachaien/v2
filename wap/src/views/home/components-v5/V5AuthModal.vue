<template>
  <van-popup 
    :show="show" 
    @update:show="onPopupUpdate"
    round 
    position="center"
    class="v5-auth-modal"
    :close-on-click-overlay="false"
    overlay
    :overlay-style="{ backgroundColor: 'rgba(0, 0, 0, 0.7)' }"
    teleport="body"
  >
    <div class="auth-content">
      <div class="auth-header">
        <div class="logo-row">
          <img src="/assets/images/user/avatars/logo.png" class="logo-img" />
          <span class="brand-name" v-html="formattedBrandName"></span>
        </div>
      </div>

      <div class="promo-text">
        <template v-if="siteConfig.promoTexts.length > 0">
          <p v-for="(text, idx) in siteConfig.promoTexts" :key="idx">{{ text }}</p>
        </template>
        <template v-else>
          <p>🥇全球游戏爱好者的最佳选择🥇</p>
          <p>🌈畅享多元化游戏娱乐体验🌈</p>
          <p>❣️安全、诚信、高效的游戏平台，信誉保障，大额无忧❣️</p>
          <p>👉USDT存取 匿名安全无风险👈</p>
        </template>
        <p class="site-url">易记网址: <span class="url-highlight">{{ siteConfig.siteDomain || '2015.com' }}</span></p>
      </div>

      <div class="auth-tabs">
        <div 
          class="tab-item" 
          :class="{ active: activeTab === 'register' }"
          @click="activeTab = 'register'"
        >
          注册
        </div>
        <div 
          class="tab-item" 
          :class="{ active: activeTab === 'login' }"
          @click="activeTab = 'login'"
        >
          登录
        </div>
      </div>

      <div v-if="activeTab === 'register'" class="auth-form">
        <div class="input-label">仅支持账号注册</div>
        
        <div class="tip-box" v-if="defaultTjcode && !tgid">
          <van-icon name="info-o" />
          无邀请码请填 <span class="code-highlight">{{ defaultTjcode }}</span>
        </div>
        
        <div class="form-item">
          <van-icon name="qr" class="input-icon" />
          <input 
            type="text" 
            v-model="regForm.reccode" 
            :placeholder="tgid ? '邀请码 (已锁定)' : '请输入邀请码 (选填)'" 
            class="custom-input" 
            :readonly="!!tgid"
          />
        </div>

        <div class="form-item">
          <van-icon name="manager" class="input-icon" />
          <input type="text" v-model="regForm.username" placeholder="请输入账号 (2-12位)" class="custom-input" />
          <span class="required">*</span>
        </div>

        <div class="form-item">
          <van-icon name="lock" class="input-icon" />
          <input :type="showPwd ? 'text' : 'password'" v-model="regForm.password" placeholder="请输入登录密码" class="custom-input" />
          <van-icon :name="showPwd ? 'eye-o' : 'closed-eye'" class="eye-icon" @click="showPwd = !showPwd" />
          <span class="required">*</span>
        </div>

        <div class="pwd-strength">
          <span>密码强度</span>
          <div class="strength-bars">
            <div class="bar" :class="{ filled: pwdStrength >= 1 }"></div>
            <div class="bar" :class="{ filled: pwdStrength >= 2 }"></div>
            <div class="bar" :class="{ filled: pwdStrength >= 3 }"></div>
          </div>
          <span class="strength-text" v-if="regForm.password">{{ strengthText }}</span>
        </div>
        <div class="pwd-tips">
          <span :class="{ valid: hasUpper }"><van-icon :name="hasUpper ? 'checked' : 'clear'" /> 大写</span>
          <span :class="{ valid: hasLower }"><van-icon :name="hasLower ? 'checked' : 'clear'" /> 小写</span>
          <span :class="{ valid: hasNumber }"><van-icon :name="hasNumber ? 'checked' : 'clear'" /> 数字</span>
          <span :class="{ valid: hasSymbol }"><van-icon :name="hasSymbol ? 'checked' : 'clear'" /> 符号</span>
        </div>

        <div class="form-item">
          <van-icon name="lock" class="input-icon" />
          <input type="password" v-model="regForm.cpassword" placeholder="请再次输入密码" class="custom-input" />
          <van-icon name="closed-eye" class="eye-icon" style="opacity: 0" />
          <span class="required">*</span>
        </div>

        <div class="agreement-row">
          <van-checkbox v-model="agree" checked-color="#009688" icon-size="16px">
            我已满18岁,已阅读且同意 <span class="link">《用户协议》</span>
          </van-checkbox>
        </div>

        <van-button block color="#009688" class="submit-btn" @click="handleRegister" :loading="loading">注册</van-button>
      </div>

      <div v-else class="auth-form">
        <div class="input-label">仅支持账号登录</div>
        
        <div class="form-item">
          <van-icon name="manager" class="input-icon" />
          <input type="text" v-model="loginForm.username" placeholder="请输入账号" class="custom-input" />
          <span class="required">*</span>
        </div>

        <div class="form-item">
          <van-icon name="lock" class="input-icon" />
          <input :type="showPwd ? 'text' : 'password'" v-model="loginForm.password" placeholder="请输入登录密码" class="custom-input" />
          <van-icon :name="showPwd ? 'eye-o' : 'closed-eye'" class="eye-icon" @click="showPwd = !showPwd" />
          <span class="required">*</span>
        </div>

        <div class="agreement-row">
          <van-checkbox v-model="remember" checked-color="#009688" icon-size="16px">
            记住账号密码
          </van-checkbox>
        </div>

        <van-button block color="#009688" class="submit-btn" @click="handleLogin" :loading="loading">登录</van-button>
      </div>

      <div class="auth-footer">
        <div class="link-item" @click="goService">联系客服</div>
        <div class="link-item" @click="goTrial">免费试玩</div>
        <div class="link-item" v-if="activeTab === 'login'" @click="goForget">忘记密码</div>
      </div>

    </div>

    <div class="bottom-close" @click="close">
      <div class="close-circle">
        <van-icon name="cross" size="20" color="#fff" />
      </div>
    </div>
  </van-popup>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { showToast } from 'vant'
import { authApi } from '@/api/auth'
import { homeApi } from '@/api/home'
import { setCookie, getCookie, delCookie } from '@/utils/cookie'
import { handleLoginSuccess } from '@/utils/auth'
import { resetAuthState } from '@/api/request'
import { heartbeatService } from '@/utils/heartbeat'

const props = defineProps({
  modelValue: Boolean,
  initialTab: { type: String, default: 'login' }
})

const emit = defineEmits(['update:modelValue', 'success'])

const router = useRouter()
const show = ref(false)
const activeTab = ref('login')
const loading = ref(false)
const showPwd = ref(false)
const agree = ref(true)
const remember = ref(true)
const tgid = ref('')
const defaultTjcode = ref('')
const siteConfig = ref({
  brandName: '',
  siteDomain: '',
  promoTexts: []
})

const loginForm = ref({ username: '', password: '' })
const regForm = ref({ 
  username: '', 
  password: '', 
  cpassword: '', 
  reccode: ''
})

const hasUpper = computed(() => /[A-Z]/.test(regForm.value.password))
const hasLower = computed(() => /[a-z]/.test(regForm.value.password))
const hasNumber = computed(() => /\d/.test(regForm.value.password))
const hasSymbol = computed(() => /[^A-Za-z0-9]/.test(regForm.value.password))

const pwdStrength = computed(() => {
  let score = 0
  if (regForm.value.password.length >= 8) score++
  if (hasUpper.value || hasLower.value) score++
  if (hasNumber.value && hasSymbol.value) score++
  return score
})

const strengthText = computed(() => {
  if (pwdStrength.value <= 1) return '弱'
  if (pwdStrength.value === 2) return '中'
  return '强'
})

const formattedBrandName = computed(() => {
  const name = siteConfig.value.brandName || '博悦娱乐'
  const domain = siteConfig.value.siteDomain || '2015.com'
  return `${name}<span class="highlight">${domain}</span>`
})

watch(() => props.modelValue, (val) => {
  show.value = val
  if (val) {
    activeTab.value = props.initialTab || 'login'
    loadCookies()
    loadFromUrl()
  }
}, { immediate: true })

onMounted(() => {
  fetchDefaultTjcode()
  loadFromUrl()
})

const onPopupUpdate = (val) => {
  show.value = val
  emit('update:modelValue', val)
}

const close = () => {
  show.value = false
  emit('update:modelValue', false)
}

const loadFromUrl = () => {
  const params = new URLSearchParams(window.location.search)
  const id = params.get('tgid')
  if (id) {
    tgid.value = id
    regForm.value.reccode = id
  }
}

const fetchDefaultTjcode = async () => {
  try {
    const res = await homeApi.getConfig()
    if (res.code === 0 && res.data) {
      defaultTjcode.value = res.data.defaulttjcode || ''
      siteConfig.value = {
        brandName: res.data.webtitle || '博悦娱乐',
        siteDomain: res.data.sitedomain || '2015.com',
        promoTexts: res.data.promo_texts || []
      }
    }
  } catch (e) {}
}

const loadCookies = () => {
  const acc = getCookie('account')
  if (acc) {
    loginForm.value.username = acc
    remember.value = true
  }
}

const handleLogin = async () => {
  if (!loginForm.value.username || !loginForm.value.password) return showToast('请输入账号和密码')
  
  loading.value = true
  try {
    const res = await authApi.login(loginForm.value)
    if (res.code === 0 || res.code === 200) {
      if (remember.value) {
        setCookie('account', loginForm.value.username, 7)
      } else {
        delCookie('account')
      }

      const loginData = {
        token: res.data?.token,
        refreshToken: res.data?.refreshToken,
        user: res.data?.user || res.data?.userInfo,
        expiresIn: res.data?.expiresIn || 7200
      }
      
      if (handleLoginSuccess(loginData)) {
        resetAuthState()
        heartbeatService.restart()
        showToast({ type: 'success', message: '登录成功' })
        emit('success')
        close()
        setTimeout(() => window.location.reload(), 500)
      }
    } else {
      showToast(res.message || '登录失败')
    }
  } catch (e) {
    showToast(e.message || '网络错误')
  } finally {
    loading.value = false
  }
}

const handleRegister = async () => {
  if (!agree.value) return showToast('请同意用户协议')
  if (!regForm.value.username || !regForm.value.password) return showToast('请完善信息')
  if (regForm.value.password !== regForm.value.cpassword) return showToast('两次密码不一致')
  
  loading.value = true
  try {
    const res = await authApi.register(regForm.value)
    if (res.code === 0 || res.code === 200) {
      const loginData = {
        token: res.data?.token,
        refreshToken: res.data?.refreshToken,
        user: res.data?.user,
        expiresIn: res.data?.expiresIn || 7200
      }
      
      if (loginData.token && handleLoginSuccess(loginData)) {
        resetAuthState()
        heartbeatService.restart()
        showToast({ type: 'success', message: '注册成功' })
        emit('success')
        close()
        setTimeout(() => window.location.reload(), 500)
      } else {
        showToast({ type: 'success', message: '注册成功，请登录' })
        activeTab.value = 'login'
        loginForm.value.username = regForm.value.username
        loginForm.value.password = regForm.value.password
      }
    } else {
      showToast(res.message || '注册失败')
    }
  } catch (e) {
    showToast(e.message || '网络错误')
  } finally {
    loading.value = false
  }
}

const goService = () => {
  close()
  router.push('/service')
}
const goTrial = () => {
  showToast('功能开发中')
}
const goForget = () => {
  close()
  router.push('/service')
}
</script>

<style lang="scss" scoped>
.v5-auth-modal {
  width: 92%;
  max-width: 400px;
  overflow: visible;
  background-color: transparent !important;
  margin-top: 0;
}

.auth-content {
  background: #fff;
  border-radius: 12px;
  padding: 15px 12px 12px;
  position: relative;
}

.bottom-close {
  display: flex;
  justify-content: center;
  margin-top: 20px;
}

.close-circle {
  width: 32px;
  height: 32px;
  border: 1px solid #fff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0,0,0,0.2);
  cursor: pointer;
}

.auth-header {
  text-align: center;
  margin-bottom: 8px;
}

.logo-row {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.logo-img {
  width: 32px;
  height: 32px;
}

.brand-name {
  font-size: 18px;
  font-weight: 700;
  color: #333;
}

:deep(.highlight) {
  color: #E60012;
}

.promo-text {
  text-align: center;
  margin-bottom: 12px;
}

.promo-text p {
  font-size: 13px;
  margin: 2px 0;
  color: #666;
  white-space: nowrap;
  transform: scale(0.98);
}

.site-url {
  font-weight: 700;
  color: #26A17B;
  margin-top: 8px;
  font-size: 14px;
}

.url-highlight {
  font-size: 16px;
}

.auth-tabs {
  display: flex;
  border-bottom: 1px solid #f5f5f5;
  margin-bottom: 12px;
  justify-content: space-around;
}

.tab-item {
  font-size: 18px;
  color: #999;
  padding: 8px 0;
  position: relative;
  cursor: pointer;
  flex: 1;
  text-align: center;
  transition: color 0.2s ease;
}

.tab-item.active {
  color: #26A17B;
  font-weight: 700;
  font-size: 20px;
}

.tab-item.active::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 28px;
  height: 3px;
  background: #26A17B;
  border-radius: 2px;
}

.auth-form {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.input-label {
  font-size: 14px;
  color: #999;
  margin-bottom: -6px;
  text-align: center;
}

.form-item {
  display: flex;
  align-items: center;
  border: 1px solid #eaeaea;
  border-radius: 8px;
  padding: 0 12px;
  height: 48px;
  transition: border-color 0.2s ease;
}

.form-item:focus-within {
  border-color: #26A17B;
}

.input-icon {
  font-size: 22px;
  color: #bbb;
  margin-right: 12px;
}

.tip-box {
  background: rgba(255,215,0,0.15);
  color: #e6a700;
  padding: 8px 12px;
  border-radius: 8px;
  font-size: 13px;
  text-align: center;
  border: 1px solid rgba(255,215,0,0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.code-highlight {
  font-weight: 700;
  font-size: 14px;
  color: #26A17B;
}

.custom-input {
  flex: 1;
  border: none;
  outline: none;
  font-size: 16px;
  color: #333;
  background: transparent;
}

.custom-input::placeholder {
  color: #bbb;
}

.eye-icon {
  font-size: 22px;
  color: #bbb;
  padding: 4px;
  cursor: pointer;
}

.required {
  color: #ff4d4f;
  margin-left: 4px;
  font-size: 14px;
}

.pwd-strength {
  display: flex;
  align-items: center;
  font-size: 13px;
  color: #666;
  gap: 8px;
  margin-top: -8px;
}

.strength-bars {
  display: flex;
  gap: 4px;
  flex: 1;
}

.bar {
  height: 5px;
  flex: 1;
  background: #f5f5f5;
  border-radius: 2px;
  transition: background 0.2s ease;
}

.bar.filled {
  background: #26A17B;
}

.strength-text {
  font-weight: 700;
  color: #26A17B;
  font-size: 13px;
}

.pwd-tips {
  display: flex;
  gap: 12px;
  font-size: 12px;
  color: #999;
  flex-wrap: wrap;
  margin-top: -8px;
}

.pwd-tips span {
  display: flex;
  align-items: center;
  gap: 2px;
}

.pwd-tips span.valid {
  color: #26A17B;
}

.agreement-row {
  font-size: 13px;
}

.link {
  color: #26A17B;
}

.auth-footer {
  display: flex;
  justify-content: space-around;
  margin-top: 12px;
  padding-top: 12px;
  padding-bottom: 5px;
  border-top: 1px solid #f5f5f5;
}

.link-item {
  font-size: 14px;
  color: #26A17B;
  cursor: pointer;
  font-weight: 500;
}

.link-item:active {
  opacity: 0.8;
}

.submit-btn {
  height: 46px;
  font-size: 18px;
  letter-spacing: 1px;
  border-radius: 8px;
}
</style>
