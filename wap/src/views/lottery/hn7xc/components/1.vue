<template>
  <div class="hn7xc-wrapper">
    
    <div class="nav-header">
      <div class="nav-left" @click="goBack">
        <van-icon name="arrow-left" size="20" color="#fff" />
      </div>
      <div class="nav-center">
        <div class="main-title">海南七星彩</div>
        <div class="sub-info">第 20231121-01 期</div>
      </div>
      <div class="nav-right">
        <van-icon name="ellipsis" size="20" color="#fff" />
      </div>
    </div>

    
    <div class="info-card-container">
      <div class="info-card">
        <div class="info-row">
          <div class="left-part">
            <div class="label">本期截止</div>
            <div class="countdown">
              <span class="time-box">02</span>:
              <span class="time-box">15</span>:
              <span class="time-box">43</span>
            </div>
          </div>
          <div class="divider-v"></div>
          <div class="right-part">
            <div class="label">上期开奖 20231120-05</div>
            <div class="mini-balls">
              <span v-for="(n, i) in lastOpenCode" :key="i" class="m-ball">{{ n }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    
    <div class="play-tabs-sticky">
      <div class="play-tabs">
        <div 
          v-for="tab in tabs" 
          :key="tab.value"
          class="tab-item"
          :class="{ active: activePlay === tab.value }"
          @click="activePlay = tab.value"
        >
          {{ tab.label }}
        </div>
        <div class="tab-indicator" :style="indicatorStyle"></div>
      </div>
    </div>

    
    <div class="scroll-content">
      
      
      <transition name="fade-slide" mode="out-in">
        <div v-if="activePlay === 'zhixuan'" key="zhixuan" class="betting-container">
          <div class="helper-tip">
            <van-icon name="bulb-o" />
            <span>每位至少选择1个号码，奖金高达 5,000,000 元</span>
          </div>

          <div v-for="(pos, pIndex) in positions" :key="pos.key" class="pos-group">
            <div class="pos-header">
              <div class="pos-title">
                <span class="name">{{ pos.name }}</span>
                <span class="tag">选号</span>
              </div>
              <div class="pos-tools">
                <span 
                  v-for="tool in tools" 
                  :key="tool.key"
                  @click="quickSelect(pIndex, tool.key)"
                >
                  {{ tool.label }}
                </span>
              </div>
            </div>
            
            <div class="ball-grid">
              <div 
                v-for="n in 10" 
                :key="n-1"
                class="ball-wrapper"
              >
                <div 
                  class="lottery-ball"
                  :class="{ selected: isSelected(pIndex, n-1) }"
                  @click="toggleNumber(pIndex, n-1)"
                >
                  {{ n-1 }}
                  <div class="ball-shadow"></div>
                  <div class="ball-highlight"></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        
        <div v-else-if="activePlay === 'danshi'" key="danshi" class="betting-container">
          <div class="danshi-card">
             <div class="danshi-header">
               <div class="title">手动输入号码</div>
               <div class="subtitle">支持 粘贴/上传 (.txt)</div>
             </div>
             <div class="input-wrapper">
               <van-field
                v-model="danshiInput"
                type="textarea"
                rows="8"
                placeholder="请输入号码，每行一注，如：&#10;1 2 3 4 5 6 7&#10;1234567"
                class="custom-textarea"
               />
             </div>
             <div class="danshi-actions">
               <div class="count-tag">共 <span class="num">{{ danshiBetCount }}</span> 注</div>
               <van-button size="small" plain type="primary" @click="danshiInput = ''">清空内容</van-button>
             </div>
          </div>
          <div class="example-card">
            <div class="ex-title">格式说明</div>
            <p>1. 格式支持：1234567 或 1 2 3 4 5 6 7</p>
            <p>2. 每行一注，支持复制粘贴大量号码</p>
          </div>
        </div>

        
        <div v-else key="other" class="betting-container empty-state">
          <van-empty description="该玩法暂未开放，敬请期待" image="search" />
        </div>
      </transition>
      
      
      <div class="bottom-spacer"></div>
    </div>

    
    <div class="footer-bar">
      <div class="footer-top">
         <div class="balance-info">余额: <span class="money">¥ {{ userBalance }}</span></div>
         <div class="settings">
            <van-stepper v-model="multiple" min="1" max="100" button-size="22px" disable-input />
            <span class="unit">倍</span>
         </div>
      </div>
      <div class="footer-main">
        <div class="cart-icon" @click="showCart">
           <van-icon name="shopping-cart-o" size="24" />
           <span class="badge" v-if="cartCount > 0">{{ cartCount }}</span>
        </div>
        <div class="summary">
           <div class="text">
             共 <span class="hl">{{ totalBets }}</span> 注，
             <span class="hl">{{ totalAmount.toFixed(2) }}</span> 元
           </div>
           <div class="desc">若中奖，单注最高可中 500万</div>
        </div>
        <button class="submit-btn" @click="submitBet" :disabled="totalBets === 0">
           立即投注
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Toast, Dialog } from 'vant'

const userBalance = ref('8,888.00')
const lastOpenCode = [5, 2, 9, 1, 6, 8, 3] // 上期号码示例

const activePlay = ref('zhixuan')
const tabs = [
  { label: '直选复式', value: 'zhixuan' },
  { label: '单式录入', value: 'danshi' },
  { label: '胆拖组合', value: 'zuxuan' },
  { label: '幸运选号', value: 'lucky' }
]
const indicatorStyle = computed(() => {
  const idx = tabs.findIndex(t => t.value === activePlay.value)
  return {
    transform: `translateX(${idx * 100}%)`,
    width: `${100 / tabs.length}%`
  }
})

const positions = [
  { key: 'w1', name: '第一位' },
  { key: 'w2', name: '第二位' },
  { key: 'w3', name: '第三位' },
  { key: 'w4', name: '第四位' },
  { key: 'w5', name: '第五位' },
  { key: 'w6', name: '第六位' },
  { key: 'w7', name: '第七位' }
]

const tools = [
  { label: '全', key: 'all' },
  { label: '大', key: 'big' },
  { label: '小', key: 'small' },
  { label: '奇', key: 'odd' },
  { label: '偶', key: 'even' },
  { label: '清', key: 'clear' }
]

const selectedNumbers = ref(positions.map(() => []))

const danshiInput = ref('')

const multiple = ref(1)
const unitAmount = 2

const cartCount = ref(0)

const goBack = () => {
  history.back()
}

const isSelected = (pIndex, num) => {
  return selectedNumbers.value[pIndex].includes(num)
}

const toggleNumber = (pIndex, num) => {
  const list = selectedNumbers.value[pIndex]
  const idx = list.indexOf(num)
  if (idx > -1) {
    list.splice(idx, 1)
  } else {
    list.push(num)
  }
}

const quickSelect = (pIndex, type) => {
  let nums = []
  switch (type) {
    case 'all': nums = [0,1,2,3,4,5,6,7,8,9]; break;
    case 'big': nums = [5,6,7,8,9]; break;
    case 'small': nums = [0,1,2,3,4]; break;
    case 'odd': nums = [1,3,5,7,9]; break;
    case 'even': nums = [0,2,4,6,8]; break;
    case 'clear': nums = []; break;
  }
  selectedNumbers.value[pIndex] = nums
}

const zhixuanBetCount = computed(() => {
  if (activePlay.value !== 'zhixuan') return 0
  return selectedNumbers.value.reduce((acc, list) => {
    return acc * (list.length || 0)
  }, 1)
})

const danshiBetCount = computed(() => {
  if (activePlay.value !== 'danshi') return 0
  const lines = danshiInput.value.split(/[\n;]/)
  let count = 0
  lines.forEach(line => {

     const clean = line.replace(/[^0-9]/g, '')
     if (clean.length === 7) count++
  })
  return count
})

const totalBets = computed(() => {
  if (activePlay.value === 'zhixuan') {

    const isValid = selectedNumbers.value.every(list => list.length > 0)
    return isValid ? zhixuanBetCount.value : 0
  }
  if (activePlay.value === 'danshi') return danshiBetCount.value
  return 0
})

const totalAmount = computed(() => {
  return totalBets.value * multiple.value * unitAmount
})

const submitBet = () => {
  if (totalBets.value === 0) {
    Toast('请完成选号')
    return
  }
  
  Dialog.confirm({
    title: '确认投注',
    message: `共 ${totalBets.value} 注，${multiple.value} 倍\n总金额：${totalAmount.value.toFixed(2)} 元`
  }).then(() => {
    Toast.loading({ message: '投注中...', forbidClick: true })
    setTimeout(() => {
      Toast.success('投注成功！')

      if (activePlay.value === 'zhixuan') {
        selectedNumbers.value = positions.map(() => [])
      } else {
        danshiInput.value = ''
      }
      cartCount.value++ // 模拟加购物车效果
    }, 800)
  }).catch(() => {

  })
}

const showCart = () => {
  Toast('打开购物车详情')
}

</script>

<style scoped lang="less">

@primary-color: #e60012; // 鲜艳红
@primary-gradient: linear-gradient(135deg, #ff5a5f 0%, #e60012 100%);
@header-bg: linear-gradient(180deg, #3a1c71 0%, #d76d77 100%, #ffaf7b 100%); // 梦幻深紫渐变
@bg-color: #f2f4f8;
@card-bg: #ffffff;

.hn7xc-wrapper {
  min-height: 100vh;
  background: @bg-color;
  font-family: -apple-system, BlinkMacSystemFont, 'Helvetica Neue', Helvetica, Segoe UI, Arial, Roboto, 'PingFang SC', 'miui', 'Hiragino Sans GB', 'Microsoft Yahei', sans-serif;
  padding-bottom: 0; 
}

.nav-header {
  position: sticky;
  top: 0;
  z-index: 100;
  height: 48px;
  background: #3a1c71; 
  background: linear-gradient(90deg, #2c3e50, #3a1c71);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 12px;
  color: #fff;
  
  .nav-center {
    text-align: center;
    .main-title {
      font-size: 18px;
      font-weight: 600;
    }
    .sub-info {
      font-size: 10px;
      opacity: 0.8;
      margin-top: -2px;
    }
  }
}

.info-card-container {
  background: linear-gradient(180deg, #3a1c71 0%, #5a3f96 60%, @bg-color 90%);
  padding: 12px 12px 20px;
}

.info-card {
  background: rgba(255,255,255,0.1);
  backdrop-filter: blur(10px);
  border-radius: 12px;
  padding: 12px;
  border: 1px solid rgba(255,255,255,0.2);
  color: #fff;
  
  .info-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  
  .label {
    font-size: 12px;
    color: rgba(255,255,255,0.8);
    margin-bottom: 6px;
  }
  
  .divider-v {
    width: 1px;
    height: 30px;
    background: rgba(255,255,255,0.2);
    margin: 0 12px;
  }
  
  .countdown {
    display: flex;
    align-items: center;
    gap: 4px;
    font-weight: bold;
    .time-box {
      background: #fff;
      color: #3a1c71;
      padding: 2px 4px;
      border-radius: 4px;
      min-width: 18px;
      text-align: center;
    }
  }
  
  .mini-balls {
    display: flex;
    gap: 4px;
    .m-ball {
      width: 20px;
      height: 20px;
      background: linear-gradient(135deg, #fff 0%, #f0f0f0 100%);
      border-radius: 50%;
      color: #333;
      font-size: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
  }
}

.play-tabs-sticky {
  position: sticky;
  top: 48px;
  z-index: 90;
  background: @bg-color;
  padding: 0 12px 8px;
  margin-top: -12px; 
}

.play-tabs {
  background: #fff;
  border-radius: 20px;
  display: flex;
  position: relative;
  padding: 4px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
  
  .tab-item {
    flex: 1;
    text-align: center;
    padding: 8px 0;
    font-size: 14px;
    color: #666;
    position: relative;
    z-index: 2;
    transition: all 0.3s;
    
    &.active {
      color: #3a1c71;
      font-weight: 600;
    }
  }
  
  .tab-indicator {
    position: absolute;
    left: 0;
    top: 4px;
    bottom: 4px;
    width: 25%; 
    background: rgba(58, 28, 113, 0.1);
    border-radius: 16px;
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 1;
  }
}

.scroll-content {
  padding: 0 12px;
}

.helper-tip {
  background: #fff8e6;
  color: #d48806;
  padding: 10px 12px;
  border-radius: 8px;
  font-size: 12px;
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 12px;
  border: 1px solid #ffe58f;
}

.pos-group {
  background: @card-bg;
  border-radius: 12px;
  padding: 12px;
  margin-bottom: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.02);
  
  .pos-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
    
    .pos-title {
      display: flex;
      align-items: center;
      gap: 8px;
      .name {
        font-weight: bold;
        font-size: 15px;
        color: #333;
      }
      .tag {
        font-size: 10px;
        background: #f0f2f5;
        color: #999;
        padding: 1px 4px;
        border-radius: 4px;
      }
    }
    
    .pos-tools {
      display: flex;
      gap: 6px;
      span {
        font-size: 11px;
        padding: 2px 8px;
        background: #f5f5f5;
        color: #666;
        border-radius: 12px;
        cursor: pointer;
        transition: background 0.2s;
        
        &:active {
          background: #e0e0e0;
        }
      }
    }
  }
  
  .ball-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 12px 8px;
  }
}

.ball-wrapper {
  display: flex;
  justify-content: center;
}

.lottery-ball {
  width: 42px;
  height: 42px;
  border-radius: 50%;
  background: linear-gradient(145deg, #ffffff, #f0f0f0);
  border: 1px solid #e8e8e8;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  font-weight: 600;
  color: #333;
  position: relative;
  box-shadow: 3px 3px 6px #d1d1d1, -3px -3px 6px #ffffff;
  transition: all 0.2s ease;
  cursor: pointer;
  user-select: none;

  
  &.selected {
    background: @primary-gradient;
    border-color: transparent;
    color: #fff;
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(230, 0, 18, 0.4);
    
    .ball-shadow {
      opacity: 0; 
    }
    .ball-highlight {
      position: absolute;
      top: 4px;
      left: 8px;
      width: 12px;
      height: 8px;
      background: rgba(255,255,255,0.4);
      border-radius: 50%;
      transform: rotate(-45deg);
    }
  }
}

.danshi-card {
  background: #fff;
  border-radius: 12px;
  padding: 16px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.02);
  margin-bottom: 12px;
  
  .danshi-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
    .title { font-weight: bold; font-size: 15px; }
    .subtitle { font-size: 12px; color: #999; }
  }
  
  .custom-textarea {
    background: #f9f9f9;
    border-radius: 8px;
    padding: 10px;
    border: 1px solid #eee;
  }
  
  .danshi-actions {
    margin-top: 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    
    .count-tag {
      font-size: 13px;
      .num {
        color: @primary-color;
        font-weight: bold;
        font-size: 16px;
      }
    }
  }
}

.example-card {
  padding: 12px;
  font-size: 12px;
  color: #666;
  line-height: 1.6;
  .ex-title {
    font-weight: bold;
    margin-bottom: 4px;
    color: #333;
  }
}

.bottom-spacer {
  height: 120px;
}

.footer-bar {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: #fff;
  box-shadow: 0 -4px 16px rgba(0,0,0,0.08);
  z-index: 200;
  border-radius: 20px 20px 0 0;
  padding-bottom: constant(safe-area-inset-bottom);
  padding-bottom: env(safe-area-inset-bottom);
  
  .footer-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 16px;
    border-bottom: 1px solid #f5f5f5;
    
    .balance-info {
      font-size: 12px;
      color: #666;
      .money {
        color: #333;
        font-weight: bold;
      }
    }
    
    .settings {
      display: flex;
      align-items: center;
      gap: 4px;
      .unit {
        font-size: 12px;
        color: #999;
      }
    }
  }
  
  .footer-main {
    padding: 8px 16px 12px;
    display: flex;
    align-items: center;
    gap: 12px;
    
    .cart-icon {
      position: relative;
      width: 40px;
      height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #f5f5f5;
      border-radius: 12px;
      color: #333;
      
      .badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background: @primary-color;
        color: #fff;
        font-size: 10px;
        padding: 2px 6px;
        border-radius: 10px;
        border: 2px solid #fff;
      }
    }
    
    .summary {
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: center;
      
      .text {
        font-size: 14px;
        color: #333;
        .hl {
          color: @primary-color;
          font-weight: bold;
        }
      }
      .desc {
        font-size: 10px;
        color: #999;
      }
    }
    
    .submit-btn {
      background: @primary-gradient;
      color: #fff;
      border: none;
      padding: 0 24px;
      height: 44px;
      border-radius: 22px;
      font-size: 16px;
      font-weight: bold;
      box-shadow: 0 4px 12px rgba(230, 0, 18, 0.3);
      
      &:disabled {
        background: #ccc;
        box-shadow: none;
      }
      
      &:active {
        transform: scale(0.98);
      }
    }
  }
}

.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all 0.3s ease;
}

.fade-slide-enter-from {
  opacity: 0;
  transform: translateX(20px);
}

.fade-slide-leave-to {
  opacity: 0;
  transform: translateX(-20px);
}
</style>
