<template>
  <van-popup 
    :show="show" 
    position="bottom" 
    round 
    :style="{ height: 'auto', maxHeight: '80%' }"
    @update:show="$emit('update:show', $event)"
    @click-overlay="close"
  >
    <div class="hemai-dialog">
      <div class="dialog-header">
        发起合买
        <van-icon name="cross" class="close-icon" @click="close" />
      </div>
      
      <div class="dialog-content">
        <div class="info-row">
          <span class="label">当前彩种：</span>
          <span class="value">11选5</span>
        </div>
        <div class="info-row">
          <span class="label">方案金额：</span>
          <span class="value money">{{ totalMoney.toFixed(2) }} 元</span>
        </div>

        <div class="form-group">
          <div class="form-item">
            <div class="item-label">认购份数</div>
            <van-field v-model.number="formData.buyCount" type="digit" placeholder="至少1份" class="input-field">
              <template #extra>份</template>
            </van-field>
          </div>
          <div class="item-desc">您至少需要认购 1 份，共 {{ (formData.buyCount * sharePrice).toFixed(2) }} 元</div>
        </div>

        <div class="form-group">
          <div class="form-item">
            <div class="item-label">保底份数</div>
            <van-field v-model.number="formData.baodiCount" type="digit" placeholder="可选" class="input-field">
              <template #extra>份</template>
            </van-field>
          </div>
          <div class="item-desc">保底金额将在合买截止时扣除</div>
        </div>

        <div class="form-group">
          <div class="item-label">提成比例</div>
          <div class="chips">
            <div 
              v-for="rate in [0, 1, 2, 3, 4, 5]" 
              :key="rate" 
              class="chip"
              :class="{ active: formData.commission === rate }"
              @click="formData.commission = rate"
            >
              {{ rate }}%
            </div>
          </div>
        </div>

        <div class="form-group">
          <div class="item-label">公开程度</div>
          <div class="chips">
            <div class="chip" :class="{ active: formData.publicType === 0 }" @click="formData.publicType = 0">完全公开</div>
            <div class="chip" :class="{ active: formData.publicType === 1 }" @click="formData.publicType = 1">跟单公开</div>
            <div class="chip" :class="{ active: formData.publicType === 2 }" @click="formData.publicType = 2">截止公开</div>
          </div>
        </div>
      </div>

      <div class="dialog-footer">
        <van-button block type="primary" class="submit-btn" @click="submit">
          立即发起
        </van-button>
      </div>
    </div>
  </van-popup>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { Popup as VanPopup, Icon as VanIcon, Field as VanField, Button as VanButton, Toast } from 'vant'

const props = defineProps({
  show: Boolean,
  totalMoney: { type: Number, default: 0 }
})
const emit = defineEmits(['update:show', 'submit'])

const formData = ref({
  buyCount: 1,
  baodiCount: 0,
  commission: 0,
  publicType: 0
})

const totalShares = computed(() => Math.ceil(props.totalMoney))
const sharePrice = computed(() => props.totalMoney / totalShares.value || 1)

function close() {
  emit('update:show', false)
}

function submit() {
  if (formData.value.buyCount < 1) {
    return Toast('认购份数不能少于1份')
  }
  if (formData.value.buyCount > totalShares.value) {
    return Toast('认购份数不能超过总份数')
  }
  
  emit('submit', {
    ...formData.value,
    totalMoney: props.totalMoney,
    totalShares: totalShares.value
  })
}

watch(() => props.show, (v) => {
  if (v) {

    formData.value.buyCount = Math.ceil(totalShares.value * 0.1) || 1 // Default 10%
    formData.value.baodiCount = 0
  }
})
</script>

<style lang="less" scoped>
@import "@/assets/theme.less";

.hemai-dialog {
  background: #fff;
  display: flex;
  flex-direction: column;
  max-height: 80vh;
}

.dialog-header {
  padding: 16px;
  text-align: center;
  font-size: 16px;
  font-weight: bold;
  border-bottom: 1px solid #f5f5f5;
  position: relative;
  .close-icon {
    position: absolute; right: 16px; top: 16px; color: #999; font-size: 20px;
  }
}

.dialog-content {
  padding: 16px;
  flex: 1;
  overflow-y: auto;
}

.info-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 12px;
  font-size: 14px;
  .label { color: #666; }
  .value { color: #333; font-weight: 500; }
  .money { color: @primary-color; font-weight: bold; font-size: 16px; }
}

.form-group {
  margin-top: 20px;
  .item-label {
    font-size: 14px; color: #333; margin-bottom: 8px; font-weight: 500;
  }
  .item-desc {
    font-size: 12px; color: #999; margin-top: 6px;
  }
}

.input-field {
  background: #f7f8fa;
  border-radius: 4px;
  padding: 8px 12px;
  :deep(.van-field__control) { font-weight: bold; }
}

.chips {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  
  .chip {
    padding: 6px 16px;
    background: #f5f5f5;
    border-radius: 4px;
    font-size: 13px;
    color: #666;
    border: 1px solid transparent;
    cursor: pointer;
    
    &.active {
      background: #fff5f5;
      color: @primary-color;
      border-color: @primary-color;
    }
  }
}

.dialog-footer {
  padding: 16px;
  border-top: 1px solid #f5f5f5;
  .submit-btn {
    background: linear-gradient(to right, #ff6034, #ee0a24);
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: bold;
  }
}
</style>
