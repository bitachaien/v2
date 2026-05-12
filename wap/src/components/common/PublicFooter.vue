<template>
  <div class="end">
    <ul>
      <li>
        <router-link to="/" class="index" :class="{ active: isActive('/') }">
          <i class="iconfont icon-7">&#xe620;</i>
          <span>首页</span>
        </router-link>
      </li>
      <li>
        <router-link to="/lotteryMore" class="games" :class="{ active: isActive('/lotteryMore') }">
          <i class="iconfont icon-8">&#xea76;</i>
          <span>大厅</span>
        </router-link>
      </li>
      <li>
        <router-link to="/activity" class="activity" :class="{ active: isActive('/activity') }">
          <i class="iconfont icon-9">&#xe616;</i>
          <span>活动</span>
        </router-link>
      </li>
      <li>
        <router-link to="/member" class="myhome" :class="{ active: isActive('/member') }">
          <i class="iconfont icon-10">&#xe625;</i>
          <span>我的</span>
        </router-link>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()

const isActive = (path) => {
  return route.path === path || route.path.startsWith(path)
}
</script>

<style scoped>

.public-footer {
  padding: 20px;
  text-align: center;
  background: #f5f5f5;
  color: #666;
  font-size: 12px;
  line-height: 1.8;
}

.public-footer a {
  color: #1989fa;
  text-decoration: none;
}

.public-footer a:hover {
  text-decoration: underline;
}
</style>

