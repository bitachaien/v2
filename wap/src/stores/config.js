
import { defineStore } from 'pinia'
import { ref } from 'vue'
import { homeApi } from '@/api/home'

export const useConfigStore = defineStore('config', () => {

  const siteName = ref('CỔNG OKWINK6 HOT NHẤT')

  const siteLogo = ref('')

  const serviceUrl = ref('')

  const loaded = ref(false)

  const loading = ref(false)

  const fetchConfig = async (force = false) => {

    if (loaded.value && !force) return

    if (loading.value) return
    loading.value = true

    try {

      const cached = localStorage.getItem('siteConfig')
      if (cached) {
        const cachedData = JSON.parse(cached)
        applySiteConfig(cachedData)
      }

      const res = await homeApi.getConfig()
      if (res.code === 0 && res.data) {
        applySiteConfig(res.data)

        localStorage.setItem('siteConfig', JSON.stringify(res.data))
      }
      loaded.value = true
    } catch (e) {
      console.error('获取站点配置失败:', e)
    } finally {
      loading.value = false
    }
  }

  const applySiteConfig = (data) => {

    if (data.webtitle || data.siteName || data.site_name || data.name) {
      siteName.value = data.webtitle || data.siteName || data.site_name || data.name
    }
    if (data.siteLogo || data.site_logo || data.logo) {
      siteLogo.value = data.siteLogo || data.site_logo || data.logo
    }
    if (data.kefuthree || data.kefuqq || data.serviceUrl || data.service_url) {
      serviceUrl.value = data.kefuthree || data.kefuqq || data.serviceUrl || data.service_url
    }
  }

  return {
    siteName,
    siteLogo,
    serviceUrl,
    loaded,
    loading,
    fetchConfig
  }
})
