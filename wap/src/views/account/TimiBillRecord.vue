<template>
  <div class="bill-record-page">
    <van-nav-bar title="交易记录" left-arrow @click-left="$router.back()" />
    <div class="content">
      <van-empty description="页面正在维护中" />
    </div>
  </div>
</template>

<script setup>

</script>

<style scoped>
.bill-record-page {
  min-height: 100vh;
  background: #f5f5f5;
}
.content {
  padding: 20px;
}
</style>
