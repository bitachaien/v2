
<template>
  <div>
    <header class="bar bar-nav theme-red" style="background:#000;text-align:center;">
      <template v-if="userinfo && userinfo.islogin == 1">
        <template v-if="isIndexPage">
          <a class="button button-link button-nav pull-left bar-nav-top-left" href="/">
            <span class="icon icon-home"></span>
          </a>
        </template>
        <template v-else>
          <a class="button button-link button-nav pull-left bar-nav-top-left" href="/">
            <span class="iconfont icon-shouyeshouye1"></span>
          </a>
        </template>

        <template v-if="isGameK3Page && nowcpinfo">
          <h1 class="title" @click="GamePageSwitchToggle">
            {{ nowcpinfo.title }}<i class="iconfont icon-sanjiaoxing"></i>
          </h1>
        </template>
        <template v-else>
          <h1 class="title">{{ webheadertitle }}</h1>
        </template>
      </template>

      <template v-else>
        <template v-if="isIndexPage">
          <a class="button button-link button-nav pull-left bar-nav-top-left" href="/">
            <span class="iconfont icon-shouyeshouye1"></span>
          </a>
        </template>
        <template v-else>
          <a class="button button-link button-nav pull-left bar-nav-top-left" href="/">
            <span class="icon icon-left"></span>
          </a>
        </template>

        <a class="button button-link button-nav pull-right" @click="lianxikefu(kefuUrl)">
          <span class="icon icon-message"></span>
          Liên hệ CSKH
        </a>

        <template v-if="isGameK3Page && nowcpinfo">
          <h1 class="title" @click="GamePageSwitchToggle">
            {{ nowcpinfo.title }} <span class="icon icon-down" style="font-size:0.8rem;"></span>
          </h1>
        </template>
        <template v-else>
          <h1 class="title">{{ webheadertitle }}</h1>
        </template>
      </template>

      
      <em class="gameInfo" style="font-size: 12px;display: inline-block;line-height: 13px;text-align: left;margin-top: 13px;">
        Cách<br>chơi
      </em>
      <div class="choice_lottery_playdetail_left">
        <a class="choice_playName" href="#">Tổng điểm</a>
        <i class="iconfont icon-sanjiaoxing" style="color: #f0c930;transform: rotate(180deg);transition: .6s;"></i>
      </div>
      
      <div data-v-048b2a82="" id="choseTab" class="choseTab forCtcpRecord" style="line-height: 2.45em;height: 2.45em;box-sizing: border-box;z-index:5;">
        <div data-v-048b2a82="" class="tabInner tabActive">
          <em data-v-048b2a82="">Đặt cược</em>
        </div>
        <a href="/userCenter/betRecord" data-v-048b2a82="" class="tabInner">
          <em data-v-048b2a82="">Lịch sử cược</em>
        </a>
        <span data-v-048b2a82="" class="wrapRefreshEye" style="color: #fff;">
          ￥<font class="wrapRefreshShow">{{ balance }}</font>
          <i style="padding-left: 5%;" class="iconfont refresh_money" @click="refreshMoney">&#xe602;</i>
        </span>
      </div>
    </header>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'

const router = useRouter()
const route = useRoute()

const props = defineProps({
  webheadertitle: {
    type: String,
    default: '游戏页面'
  },
  nowcpinfo: {
    type: Object,
    default: null
  },
  userinfo: {
    type: Object,
    default: () => ({ islogin: 0 })
  }
})

const balance = ref('0')
const kefuUrl = ref('#')

const isIndexPage = computed(() => {
  return route.name === 'Home' || route.path === '/'
})

const isGameK3Page = computed(() => {
  return route.name?.includes('GameK3') || route.path?.includes('/game/k3')
})

const GamePageSwitchToggle = () => {

}

const lianxikefu = (url) => {
  window.open(url, '_blank')
}

const refreshMoney = async () => {
  try {
    const response = await fetch('/api/Account/refreshmoney', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      }
    })
    const data = await response.text()
    balance.value = data
  } catch (error) {
    console.error('Làm mới số dư thất bại:', error)
  }
}

onMounted(() => {
  balance.value = props.userinfo?.balance || '0'
})
</script>

<style scoped>
.theme-red .icon-sanjiaoxing {
  display: inline-block;
  transform: rotate(180deg);
  transition: .6s;
  font-size: 22px;
}

.bar-nav {
  background: #000;
  text-align: center;
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  width: 100%;
  z-index: 1000;
  height: 50px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.button-link {
  color: #fff;
  text-decoration: none;
  padding: 10px 15px;
  cursor: pointer;
}

.title {
  flex: 1;
  text-align: center;
  font-size: 18px;
  font-weight: normal;
  margin: 0;
  color: #fff;
  cursor: pointer;
}

.iconfont {
  font-family: "iconfont" !important;
  font-style: normal;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

.g_Time_Section {
  margin-top: 21px;
}

.choseTab .wrapRefreshEye[data-v-048b2a82] {
  position: absolute;
  right: 14px;
  font-size: 14.5px;
}

.choseTab[data-v-048b2a82] {
  background: #000;
  font-size: .7em;
  text-align: left;
  position: relative;
  height: 1.65em;
  overflow: hidden;
}

.choseTab .tabInner[data-v-048b2a82] {
  display: inline-block;
  margin-left: 20px;
  position: relative;
  color: hsla(0,0%,100%,.6);
  border-bottom: 4px solid #000;
}

.choseTab .tabInner.tabActive[data-v-048b2a82] {
  border-color: #fff;
}

.choseTab .tabInner.tabActive em[data-v-048b2a82] {
  color: #fff;
}

.yGame_list {
  width: 96px;
  background: #fff;
  position: absolute;
  right: 0;
  top: 50px;
  box-shadow: 0 2px 10px rgba(41,41,41,.08);
  display: none;
  z-index: 5;
}

.yGame_list li {
  text-align: center;
  height: 45px;
  line-height: 45px;
  padding: 0;
}

.yGame_list li:first-child:before {
  position: absolute;
  content: "";
  display: block;
  width: 0;
  height: 0;
  border-bottom: 1em solid hsla(0,0%,100%,.96);
  border-left: 1em solid transparent;
  right: 0;
  top: -.96em;
}

.choseTab[data-v-048b2a82] {
  background: #000;
  font-size: .7em;
  text-align: left;
  position: absolute;
  height: 1.65em;
  overflow: hidden;
  line-height: 1.45em;
  box-sizing: border-box;
  margin: 0;
  left: 0;
  top: 50px;
  width: 100%;
}

.refresh_money {
  cursor: pointer;
}
</style>

