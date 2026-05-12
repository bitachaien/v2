<template>
  <div class="v5-security">
    <van-nav-bar
      title="安全中心"
      left-arrow
      @click-left="onClickLeft"
      class="custom-nav"
    />

    <div class="security-content">
      
      <div class="security-group">
        <van-cell-group inset>
          
          <van-cell title="会员账号" class="info-cell">
            <template #icon>
              <van-icon name="manager" class="cell-icon gray" />
            </template>
            <template #value>
              <div class="account-value">
                <span>{{ userInfo.username }}</span>
                <van-icon name="orders-o" class="copy-btn" @click.stop="copyText(userInfo.username)" />
              </div>
            </template>
          </van-cell>

          
          <van-cell title="手机" is-link :value="userInfo.phoneBind ? '已添加' : '未添加'" :value-class="{ 'green-text': userInfo.phoneBind }" @click="go('/security/phone')">
            <template #icon>
              <van-icon name="phone-o" :class="['cell-icon', userInfo.phoneBind ? 'green' : 'gray']" />
            </template>
          </van-cell>

          
          <van-cell title="邮箱" is-link :value="userInfo.emailBind ? '已添加' : '未添加'" :value-class="{ 'green-text': userInfo.emailBind }" @click="go('/security/email')">
            <template #icon>
              <van-icon name="envelop-o" :class="['cell-icon', userInfo.emailBind ? 'green' : 'gray']" />
            </template>
          </van-cell>

          
          <van-cell title="Google验证器" is-link :value="userInfo.googleBind ? '已绑定' : '未绑定'" :value-class="{ 'green-text': userInfo.googleBind }" @click="go('/security/google')">
            <template #icon>
              <van-icon name="scan" class="cell-icon colorful" />
            </template>
          </van-cell>
        </van-cell-group>
      </div>

      
      <div class="security-group">
        <van-cell-group inset>
          
          <van-cell title="登录密码" is-link @click="go('/security/login-pwd')">
            <template #icon>
              <van-icon name="lock" class="cell-icon green" />
            </template>
          </van-cell>

          
          <van-cell title="提现密码" is-link :value="userInfo.hasFundPwd ? '已设置' : '未设置'" @click="go('/security/fund-pwd')">
            <template #icon>
              <van-icon name="card" class="cell-icon red" />
            </template>
          </van-cell>

          
          <van-cell title="密保问题" is-link :value="userInfo.hasQuestion ? '已设置' : '未设置'" :value-class="{ 'green-text': userInfo.hasQuestion }" @click="go('/security/question')">
            <template #icon>
              <van-icon name="shield-o" class="cell-icon green" />
            </template>
          </van-cell>
        </van-cell-group>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { showToast } from 'vant'
import { securityApi } from '@/api/security'

const router = useRouter()
const loading = ref(false)
const userInfo = ref({
  username: '',
  phone: '',
  phoneBind: false,
  email: '',
  emailBind: false,
  googleBind: false,
  hasFundPwd: false,
  hasQuestion: false
})

const loadSecurityInfo = async () => {
  try {
    loading.value = true
    const res = await securityApi.getInfo()
    if (res.code === 0 && res.data) {
      userInfo.value = { ...userInfo.value, ...res.data }
    }
  } catch (e) {
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadSecurityInfo()
})

const onClickLeft = () => {
  router.go(-1)
}

const go = (path) => {
  router.push(path)
}

const copyText = (text) => {
  if (!text) return
  navigator.clipboard.writeText(text).then(() => {
    showToast('复制成功')
  }).catch(() => {
    showToast('复制失败')
  })
}
</script>

<style scoped>
.v5-security {
  min-height: 100vh;
  background: #f7f8fa;
  font-family: -apple-system, BlinkMacSystemFont, "PingFang SC", "Helvetica Neue", Arial, sans-serif;
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

.security-content {
  padding-top: 12px;
}

.security-group {
  margin-bottom: 12px;
}

:deep(.van-cell-group--inset) {
  margin: 0 12px;
  border-radius: 8px;
  overflow: hidden;
}

.cell-icon {
  font-size: 20px;
  margin-right: 10px;
  display: flex;
  align-items: center;
}

.cell-icon.gray { color: #999; }
.cell-icon.green { color: #009688; }
.cell-icon.red { color: #f44336; }
.cell-icon.colorful { color: #3F51B5; } 

.account-value {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 4px;
}

.copy-btn {
  color: #009688;
  font-size: 16px;
  cursor: pointer;
}

.green-text {
  color: #009688 !important;
}

:deep(.van-cell__title) {
  font-size: 15px;
  color: #333;
}
:deep(.van-cell__value) {
  font-size: 14px;
}
</style>
