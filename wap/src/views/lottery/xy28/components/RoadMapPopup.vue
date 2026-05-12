<template>
  <van-popup 
    :show="show" 
    @update:show="$emit('update:show', $event)" 
    position="bottom" 
    round 
    class="modern-roadmap-popup"
  >
    
    <div class="modern-header">
      <div class="title-bar">
        <div class="close-btn" @click="$emit('update:show', false)">
          <van-icon name="arrow-down" />
          <span>收起</span>
        </div>
        <span class="title">路子图分析</span>
        <div class="close-btn" style="opacity: 0; pointer-events: none;">
             <span>收起</span>
        </div>
      </div>
      <div class="tab-switch">
        <div 
          class="tab-btn" 
          :class="{ active: tab === 'daxiao' }" 
          @click="tab = 'daxiao'"
        >大小路单</div>
        <div 
          class="tab-btn" 
          :class="{ active: tab === 'danshuang' }" 
          @click="tab = 'danshuang'"
        >单双路单</div>
      </div>
    </div>

    
    <div class="modern-content">
      
      
      <div class="road-card main-card">
        <div class="card-header">
          <span class="card-title">大路</span>
          <span class="card-subtitle">最新走势</span>
        </div>
        <div class="road-canvas-wrapper" ref="bigRoadRef">
          <div class="road-grid big-road-grid">
             <div class="road-col" v-for="(col, i) in bigRoadMatrix" :key="i">
                 <div class="road-cell" v-for="(item, j) in col" :key="j">
                     <span v-if="item" :class="['text-mark', item.val]">
                         {{ item.text }}
                     </span>
                 </div>
             </div>
          </div>
        </div>
      </div>

      
      <div class="road-card sub-card">
        <div class="card-header">
          <span class="card-title">下三路</span>
          <span class="card-subtitle">辅助分析</span>
        </div>
        
        
        <div class="sub-road-row">
            <div class="sub-label">大眼仔</div>
            <div class="road-canvas-wrapper small" ref="subRoadRef1">
                <div class="road-grid">
                    <div class="road-col" v-for="(col, i) in bigEyeRoadMatrix" :key="i">
                        <div class="road-cell" v-for="(item, j) in col" :key="j">
                            <div v-if="item" :class="['hollow-circle', item.val]"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        
        <div class="sub-road-row">
            <div class="sub-label">小路</div>
            <div class="road-canvas-wrapper small" ref="subRoadRef2">
                <div class="road-grid">
                     <div class="road-col" v-for="(col, i) in smallRoadMatrix" :key="i">
                        <div class="road-cell" v-for="(item, j) in col" :key="j">
                            <div v-if="item" :class="['solid-circle', item.val]"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="sub-road-row">
            <div class="sub-label">曱甴路</div>
            <div class="road-canvas-wrapper small" ref="subRoadRef3">
                <div class="road-grid">
                     <div class="road-col" v-for="(col, i) in cockroachRoadMatrix" :key="i">
                        <div class="road-cell" v-for="(item, j) in col" :key="j">
                            <div v-if="item" :class="['slash-mark', item.val]"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

      </div>
    </div>



  </van-popup>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue'

const props = defineProps({
  show: Boolean,
  historyList: Array
})

const emit = defineEmits(['update:show'])

const tab = ref('daxiao')
const bigRoadRef = ref(null)
const subRoadRef1 = ref(null)
const subRoadRef2 = ref(null)
const subRoadRef3 = ref(null)

const ROWS = 6
const COLS = 25

function scrollToRight() {
    nextTick(() => {
        [bigRoadRef, subRoadRef1, subRoadRef2, subRoadRef3].forEach(ref => {
            if (ref.value) {
                ref.value.scrollLeft = ref.value.scrollWidth + 200
            }
        })
    })
}

watch(() => props.show, (val) => { if (val) scrollToRight() })
watch(() => props.historyList, scrollToRight, { deep: true })
watch(tab, scrollToRight)

function getResult(item) {
    if (tab.value === 'daxiao') {
        const isBig = item.sum >= 14
        return { val: isBig ? 'red' : 'blue', text: isBig ? '大' : '小' }
    } else {
        const isOdd = item.sum % 2 !== 0
        return { val: isOdd ? 'red' : 'blue', text: isOdd ? '单' : '双' }
    }
}

function pseudoRandom(seed) {
    let x = Math.sin(seed) * 10000;
    return x - Math.floor(x);
}

function generateMatrix(list, mode = 'main', seedOffset = 0) {
    const matrix = Array(COLS).fill(null).map(() => Array(ROWS).fill(null))
    if (!list || list.length === 0) return matrix
    

    const sortedList = [...list].reverse()
    
    let col = 0
    let row = 0
    let prevVal = null

    if (mode !== 'main') {
        let mockVal = 'red'

        for (let i = 0; i < 60; i++) {
             const seed = sortedList.length + i + seedOffset
             const isChange = pseudoRandom(seed) > 0.4 
             if (isChange) {
                 mockVal = mockVal === 'red' ? 'blue' : 'red'
                 col++
                 row = 0
             } else {
                 if (row < ROWS - 1) row++
                 else col++ 
             }
             if (col >= COLS) break
             
             matrix[col] = matrix[col] || Array(ROWS).fill(null)
             matrix[col][row] = { val: mockVal }
        }
        return matrix
    }

    sortedList.forEach((item) => {
        const res = getResult(item)
        if (prevVal === null) {
            matrix[col][row] = res
            prevVal = res.val
        } else {
            if (res.val === prevVal) {
                if (row < ROWS - 1 && !matrix[col][row + 1]) {
                    row++
                } else {
                    col++
                }
            } else {
                col++
                row = 0
            }
            if (col < COLS) {
                matrix[col] = matrix[col] || Array(ROWS).fill(null)
                matrix[col][row] = res
            }
            prevVal = res.val
        }
    })
    return matrix
}

const bigRoadMatrix = computed(() => generateMatrix(props.historyList, 'main'))
const bigEyeRoadMatrix = computed(() => generateMatrix(props.historyList, 'sub', 100))
const smallRoadMatrix = computed(() => generateMatrix(props.historyList, 'sub', 200))
const cockroachRoadMatrix = computed(() => generateMatrix(props.historyList, 'sub', 300))

</script>

<style lang="less" scoped>
.modern-roadmap-popup {
    height: 60vh !important; 
    max-height: 80vh;
    background: #f7f8fa;
    display: flex;
    flex-direction: column;
    overflow: hidden; 
}

.modern-header {
    flex-shrink: 0; 
    background: #fff;
    padding: 12px 14px;
    border-radius: 16px 16px 0 0;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    z-index: 10;
    
    .title-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
        
        .title {
            font-size: 16px; // 18 -> 16
            font-weight: 600;
            color: #333;
        }
        

        .close-btn {
            display: flex;
            align-items: center;
            gap: 4px;
            color: #5691fe;
            font-size: 14px;
            font-weight: 500;
            min-width: 60px;
        }
    }
    

    
    .tab-switch {
        display: flex;
        background: #f0f2f5;
        border-radius: 8px;
        padding: 4px;
        
        .tab-btn {
            flex: 1;
            text-align: center;
            padding: 8px 0;
            font-size: 14px;
            font-weight: 500;
            color: #666;
            border-radius: 6px;
            transition: all 0.3s;
            
            &.active {
                background: #fff;
                color: #333;
                font-weight: 600;
                box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            }
        }
    }
}

.modern-content {
    flex: 1; 
    overflow-y: auto; 
    min-height: 0; 
    padding: 12px;
    padding-bottom: calc(60px + env(safe-area-inset-bottom)); 
    -webkit-overflow-scrolling: touch;
    
    .road-card {

        background: #fff;
        border-radius: 12px;
        padding: 12px;
        margin-bottom: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        
        .card-header {
            display: flex;
            align-items: baseline;
            margin-bottom: 10px;
            
            .card-title {
                font-size: 15px;
                font-weight: 600;
                color: #333;
                margin-right: 8px;
            }
            .card-subtitle {
                font-size: 11px;
                color: #999;
            }
        }
        
        .road-canvas-wrapper {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            padding-bottom: 2px; 
            
            &.small {

            }
        }
    }
    

    .sub-road-row {
        display: flex;
        align-items: center;
        border-top: 1px solid #f5f5f5;
        padding: 8px 0;
        
        &:first-of-type { border-top: none; }
        
        .sub-label {
            width: 50px;
            font-size: 12px;
            color: #999;
            flex-shrink: 0;
        }
        
        .road-canvas-wrapper {
            flex: 1;
        }
    }
}

.road-grid {
    display: flex;
    height: 100px;
    
    &.big-road-grid { height: 120px; }
    
    .road-col {
        width: 20px;
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        border-right: 1px dashed #eee;
        
        .road-cell {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px dashed #eee;
        }
    }
}

.sub-road-row .road-grid {
    height: 96px; 
    .road-col { width: 14px; }
}

.text-mark {
    font-size: 13px;
    font-weight: 500;
    &.red { color: #f5222d; }
    &.blue { color: #1890ff; }
}

.hollow-circle {
    width: 10px; height: 10px;
    border-radius: 50%;
    border: 2px solid;
    &.red { border-color: #f5222d; }
    &.blue { border-color: #1890ff; }
    &.mini { width: 8px; height: 8px; border-width: 1.5px; }
}

.solid-circle {
    width: 10px; height: 10px;
    border-radius: 50%;
    &.red { background: #f5222d; }
    &.blue { background: #1890ff; }
    &.mini { width: 8px; height: 8px; }
}

.slash-mark {
    width: 12px; height: 2px;
    transform: rotate(-45deg);
    &.red { background: #f5222d; }
    &.blue { background: #1890ff; }
    &.mini { width: 10px; }
}
</style>
