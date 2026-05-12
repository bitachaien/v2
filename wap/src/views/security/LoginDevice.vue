<template>
  <div class="login-device">
    <van-nav-bar
      title="登录设备"
      left-arrow
      @click-left="onClickLeft"
      class="custom-nav"
    />

    <div class="user-info-card">
      <div class="logo-area">
        <img src="/assets/images/common/logo.png" alt="logo" class="site-logo" />
      </div>
      <div class="user-details">
        <div class="user-id">
          <span>ID: {{ userInfo.id }}</span>
          <van-icon name="description" class="copy-icon" @click="copyText(userInfo.id)" />
        </div>
        <div class="user-account">账号: {{ userInfo.username }}</div>
      </div>
    </div>

    <div class="auto-logout-setting" @click="showTimeoutPicker = true">
      <span class="label">自动退出登录<span class="sub-label">(闲置时长)</span></span>
      <span class="value">{{ timeoutText }} <van-icon name="arrow" /></span>
    </div>

    <div class="device-list">
      <div v-for="device in deviceList" :key="device.id" class="device-card">
        <div class="device-header">
          <van-icon :name="device.os_type === 'Windows' ? 'desktop-o' : 'phone-o'" class="device-type-icon" />
          <span class="device-label">{{ device.is_current ? '当前设备' : '历史设备' }}</span>
          <van-icon v-if="device.is_current" name="passed" class="check-icon" />
        </div>

        <div class="device-info">
          <div class="info-row">
            <span class="info-label">客户端</span>
            <span class="info-value">{{ device.client_version || 'H5' }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">浏览器类型</span>
            <span class="info-value">{{ device.browser_type || '未知' }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">操作系统</span>
            <span class="info-value">{{ device.os_type || '未知' }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">系统版本</span>
            <span class="info-value">{{ device.os_version || '未知' }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">设备品牌</span>
            <span class="info-value">{{ device.device_brand || '未知' }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">设备型号</span>
            <span class="info-value">{{ device.device_model || '未知' }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">IP地区</span>
            <span class="info-value ip-value">{{ device.ip_region || device.ip || '未知' }}</span>
          </div>
          <div class="info-row" v-if="device.last_login_at">
            <span class="info-label">登录时间</span>
            <span class="info-value">{{ formatTime(device.last_login_at) }}</span>
          </div>
        </div>
      </div>

      <van-empty v-if="!loading && deviceList.length === 0" description="暂无登录设备" />
    </div>

    <van-action-sheet
      v-model:show="showTimeoutPicker"
      :actions="timeoutOptions"
      cancel-text="取消"
      @select="onSelectTimeout"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { showToast } from 'vant'
import request from '@/api/request'

const router = useRouter()
const loading = ref(false)
const showTimeoutPicker = ref(false)
const selectedTimeout = ref(365)

const userInfo = ref({ id: '', username: '' })
const deviceList = ref([])

const timeoutOptions = [
  { name: '30分钟', value: 0.02 },
  { name: '1小时', value: 0.04 },
  { name: '1天', value: 1 },
  { name: '7天', value: 7 },
  { name: '30天', value: 30 },
  { name: '1年', value: 365 }
]

const timeoutText = computed(() => {
  const opt = timeoutOptions.find(o => o.value === selectedTimeout.value)
  return opt ? opt.name : '1年'
})

const loadUserInfo = () => {
  const cached = localStorage.getItem('userInfo')
  if (cached) {
    try {
      const info = JSON.parse(cached)
      userInfo.value.id = info.id || info.userId || ''
      userInfo.value.username = info.username || info.account || ''
    } catch (e) {}
  }
}

const loadSettings = async () => {
  try {
    const res = await request.get('/v1/user/settings')
    if (res.code === 0 && res.data?.autoLogoutDays) {
      selectedTimeout.value = res.data.autoLogoutDays
    }
  } catch (e) {}
}

const loadDeviceList = async () => {
  loading.value = true
  try {
    const res = await request.get('/v1/user/devices')
    if (res.code === 0 && res.data) {
      deviceList.value = res.data.list || []
    }
  } catch (e) {}
  loading.value = false
}

const formatTime = (timestamp) => {
  if (!timestamp) return ''
  const date = new Date(timestamp * 1000)
  const y = date.getFullYear()
  const m = String(date.getMonth() + 1).padStart(2, '0')
  const d = String(date.getDate()).padStart(2, '0')
  const h = String(date.getHours()).padStart(2, '0')
  const min = String(date.getMinutes()).padStart(2, '0')
  const s = String(date.getSeconds()).padStart(2, '0')
  return `${y}/${m}/${d} ${h}:${min}:${s}`
}

const onClickLeft = () => router.go(-1)

const copyText = (text) => {
  if (!text) return
  navigator.clipboard.writeText(String(text)).then(() => {
    showToast('复制成功')
  }).catch(() => {
    showToast('复制失败')
  })
}

const onSelectTimeout = async (action) => {
  selectedTimeout.value = action.value
  showTimeoutPicker.value = false
  try {
    await request.post('/v1/user/settings', { key: 'autoLogoutDays', value: action.value })
    showToast('设置已保存')
  } catch (e) {
    showToast('保存失败')
  }
}

onMounted(() => {
  loadUserInfo()
  loadSettings()
  loadDeviceList()
})
</script>

<style scoped>
.login-device {
  min-height: 100vh;
  background: #f5f5f5;
  padding-bottom: 20px;
}
.custom-nav { background: #fff; }
:deep(.van-nav-bar__title) { font-weight: 500; font-size: 17px; }
.user-info-card {
  display: flex;
  align-items: center;
  padding: 16px;
  background: #fff;
  margin-bottom: 1px;
}
.logo-area { margin-right: 12px; }
.site-logo { width: 50px; height: 50px; object-fit: contain; }
.user-details { flex: 1; }
.user-id { display: flex; align-items: center; font-size: 15px; color: #333; margin-bottom: 4px; }
.copy-icon { margin-left: 6px; color: #26A17B; font-size: 16px; cursor: pointer; }
.user-account { font-size: 14px; color: #666; }
.auto-logout-setting {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 14px 16px;
  background: #fff;
  margin-bottom: 12px;
}
.label { font-size: 15px; color: #26A17B; font-weight: 500; }
.sub-label { color: #999; font-weight: normal; }
.value { font-size: 14px; color: #26A17B; display: flex; align-items: center; gap: 4px; }
.device-list { padding: 0 12px; }
.device-card { background: #fff; border-radius: 12px; margin-bottom: 12px; overflow: hidden; }
.device-header { display: flex; align-items: center; padding: 12px 16px; border-bottom: 1px solid #f5f5f5; }
.device-type-icon { font-size: 20px; color: #26A17B; margin-right: 8px; }
.device-label { font-size: 15px; color: #333; font-weight: 500; }
.check-icon { margin-left: 8px; color: #26A17B; font-size: 18px; }
.device-info { padding: 12px 16px; }
.info-row { display: flex; justify-content: space-between; align-items: flex-start; padding: 8px 0; border-bottom: 1px solid #f9f9f9; }
.info-row:last-child { border-bottom: none; }
.info-label { font-size: 14px; color: #666; flex-shrink: 0; }
.info-value { font-size: 14px; color: #333; text-align: right; word-break: break-all; max-width: 60%; }
.ip-value { font-size: 12px; line-height: 1.4; }
</style>
