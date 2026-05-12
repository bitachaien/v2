<template>
  <div class="rate-page">
    
    <div class="nav-header">
      <div class="back-btn" @click="goBack">
        <van-icon name="arrow-left" />
      </div>
      <div class="title">返水比例</div>
      <div class="placeholder"></div>
    </div>

    
    <div class="filter-header">
      <div class="filter-select" @click="showCategoryPicker = true">
        <span>{{ categoryName }}</span>
        <van-icon name="arrow-down" />
      </div>
      <div class="filter-select" @click="showVendorPicker = true">
        <span>{{ vendorName || 'PG' }}</span>
        <van-icon name="arrow-down" />
      </div>
      <div class="header-col bet">累计洗码</div>
      <div class="header-col rate">返水比例</div>
    </div>

    
    <div class="rate-list" v-if="rateList.length > 0">
      <div class="rate-item" v-for="(item, index) in rateList" :key="index">
        <div class="col cat">{{ categoryName }}</div>
        <div class="col vendor">{{ item.vendorName || selectedVendor || 'PG' }}</div>
        <div class="col bet">≥{{ formatBet(item.minBet) }}</div>
        <div class="col rate">{{ item.rate.toFixed(2) }}%</div>
      </div>
    </div>

    
    <div v-else-if="loading" class="loading-state">
      <van-loading type="spinner" color="#999" />
    </div>

    
    <div v-else class="empty-state">
      <van-empty description="暂无返水比例配置" />
    </div>

    
    <van-popup v-model:show="showCategoryPicker" position="bottom" round>
      <van-picker
        :columns="categoryColumns"
        @confirm="onCategoryConfirm"
        @cancel="showCategoryPicker = false"
        confirm-button-text="确定"
        cancel-button-text="取消"
      />
    </van-popup>

    
    <van-popup v-model:show="showVendorPicker" position="bottom" round>
      <van-picker
        :columns="vendorColumns"
        @confirm="onVendorConfirm"
        @cancel="showVendorPicker = false"
        confirm-button-text="确定"
        cancel-button-text="取消"
      />
    </van-popup>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import request from '@/api/request'

const route = useRoute()
const router = useRouter()

const categories = [
  { code: 'slot', name: '电子' },
  { code: 'live', name: '真人' },
  { code: 'fishing', name: '捕鱼' },
  { code: 'chess', name: '棋牌' },
  { code: 'lottery', name: '彩票' },
  { code: 'sport', name: '体育' },
]

const categoryColumns = categories.map(c => ({ text: c.name, value: c.code }))

const vendors = ref([])
const vendorColumns = computed(() => {
  return [{ text: '全部', value: '' }, ...vendors.value.map(v => ({ text: v.name, value: v.code }))]
})

const loading = ref(false)
const selectedCategory = ref('slot')
const selectedVendor = ref('')
const rateList = ref([])

const showCategoryPicker = ref(false)
const showVendorPicker = ref(false)

const categoryName = computed(() => {
  const cat = categories.find(c => c.code === selectedCategory.value)
  return cat?.name || '电子'
})

const vendorName = computed(() => {
  if (!selectedVendor.value) return ''
  const v = vendors.value.find(x => x.code === selectedVendor.value)
  return v?.name || selectedVendor.value
})

const formatBet = (amount) => {
  if (amount >= 10000000) return (amount / 10000).toLocaleString() + '万+'
  if (amount >= 10000) return amount.toLocaleString() + '+'
  return amount + '+'
}

const goBack = () => {
  router.back()
}

const onCategoryConfirm = ({ selectedOptions }) => {
  selectedCategory.value = selectedOptions[0].value
  selectedVendor.value = ''
  showCategoryPicker.value = false
  loadRates()
}

const onVendorConfirm = ({ selectedOptions }) => {
  selectedVendor.value = selectedOptions[0].value
  showVendorPicker.value = false
  loadRates()
}

const loadRates = async () => {
  loading.value = true
  try {
    const params = { category_code: selectedCategory.value }
    if (selectedVendor.value) {
      params.vendor_code = selectedVendor.value
    }
    
    const res = await request.get('/v1/rebate/tier-rates', { params })
    
    if (res.code === 0 && res.data) {
      rateList.value = res.data.list || []
      
      if (res.data.vendors && res.data.vendors.length > 0) {
        vendors.value = res.data.vendors
      }
    }
  } catch (err) {
    console.error('加载返水比例失败:', err)
    rateList.value = []
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  if (route.query.category) {
    selectedCategory.value = route.query.category
  }
  if (route.query.vendor) {
    selectedVendor.value = route.query.vendor
  }
  loadRates()
})
</script>

<style scoped>
.rate-page {
  min-height: 100vh;
  background: #f8f8f8;
}

.nav-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 15px;
  background: #fff;
}

.back-btn {
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  color: #333;
}

.title {
  font-size: 17px;
  font-weight: 600;
  color: #333;
}

.placeholder {
  width: 30px;
}

.filter-header {
  display: flex;
  align-items: center;
  padding: 12px 15px;
  background: #fff;
  border-bottom: 1px solid #f0f0f0;
}

.filter-select {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  min-width: 70px;
  padding: 6px 12px;
  background: #f5f5f5;
  border-radius: 15px;
  font-size: 13px;
  color: #333;
  margin-right: 10px;
}

.filter-select .van-icon {
  font-size: 10px;
  color: #999;
}

.header-col {
  font-size: 13px;
  color: #999;
}

.header-col.bet {
  flex: 1;
  text-align: center;
}

.header-col.rate {
  width: 80px;
  text-align: right;
}

.rate-list {
  background: #fff;
}

.rate-item {
  display: flex;
  align-items: center;
  padding: 14px 15px;
  border-bottom: 1px solid #f5f5f5;
}

.rate-item:last-child {
  border-bottom: none;
}

.rate-item .col {
  font-size: 14px;
}

.rate-item .cat {
  width: 70px;
  color: #666;
}

.rate-item .vendor {
  width: 70px;
  color: #333;
}

.rate-item .bet {
  flex: 1;
  text-align: center;
  color: #666;
}

.rate-item .rate {
  width: 80px;
  text-align: right;
  color: #f5a623;
  font-weight: 600;
}

.loading-state,
.empty-state {
  display: flex;
  justify-content: center;
  padding: 60px 0;
  background: #fff;
}
</style>
