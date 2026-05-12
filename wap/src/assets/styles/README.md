# 可复用样式系统

## 📁 文件结构

```
styles/
├── index.scss        # 入口文件（全局引入）
├── variables.scss    # CSS 变量（颜色、字体、间距等）
├── mixins.scss       # SCSS Mixins（可复用混入）
├── animations.scss   # 动画效果
├── components.scss   # 组件样式类
├── utilities.scss    # 工具类（原子级CSS）
└── README.md         # 使用说明
```

## 🚀 快速开始

### 1. 全局引入（推荐）

在 `main.js` 中引入：

```js
import '@/assets/styles/index.scss'
```

### 2. 组件中使用 Mixins

在 Vue 组件的 `<style>` 中：

```vue
<style lang="scss" scoped>
@import '@/assets/styles/mixins.scss';

.my-button {
  @include btn-primary;
}
</style>
```

### 3. 仅使用变量

```vue
<style lang="scss" scoped>
@import '@/assets/styles/variables.scss';

.my-text {
  color: $color-primary;
  font-size: $font-size-lg;
}
</style>
```

---

## 🎨 变量说明

### 颜色系统

| 变量 | 值 | 用途 |
|------|------|------|
| `$color-primary` | #26A17B | 主色调（USDT绿） |
| `$color-success` | #07c160 | 成功/在线状态 |
| `$color-danger` | #ff4d4f | 错误/警告 |
| `$color-warning` | #FFAA09 | 警告/金额高亮 |
| `$color-text-primary` | #333 | 主文字颜色 |
| `$color-text-secondary` | #666 | 次要文字 |
| `$color-text-tertiary` | #999 | 辅助文字 |
| `$color-bg-page` | #f7f8fa | 页面背景 |
| `$color-border` | #eaeaea | 边框颜色 |

### 字体大小

| 变量 | 值 | 用途 |
|------|------|------|
| `$font-size-xs` | 10px | 极小文字 |
| `$font-size-sm` | 12px | 小号文字 |
| `$font-size-md` | 14px | 正文 |
| `$font-size-lg` | 16px | 大号文字 |
| `$font-size-xl` | 17px | 标题 |
| `$font-size-display` | 24px | 大数字展示 |

### 间距

| 变量 | 值 |
|------|------|
| `$spacing-xs` | 4px |
| `$spacing-sm` | 8px |
| `$spacing-md` | 12px |
| `$spacing-lg` | 15px |
| `$spacing-xl` | 20px |

---

## 🔧 Mixins 使用示例

### 布局 Mixins

```scss
// Flex 居中
.container {
  @include flex-center;  // display: flex; align-items: center; justify-content: center;
}

// Flex 两端对齐
.header {
  @include flex-between;  // display: flex; align-items: center; justify-content: space-between;
}

// 全屏页面
.page {
  @include page-container;  // min-height: 100vh; background: #f7f8fa;
}

// 可滚动区域
.content {
  @include scroll-container;  // flex: 1; overflow-y: auto;
}

// 安全区底部
.footer {
  @include safe-area-bottom(50px);  // padding-bottom: calc(50px + env(safe-area-inset-bottom));
}
```

### 按钮 Mixins

```scss
// 主要按钮（绿色填充）
.btn-submit {
  @include btn-primary;
  // 可自定义高度：@include btn-primary(48px);
}

// 次要按钮（绿色描边）
.btn-cancel {
  @include btn-secondary;
}

// 文字按钮
.btn-link {
  @include btn-text;
}
```

### 卡片 Mixins

```scss
// 基础卡片
.card {
  @include card-base;  // 白色背景 + 圆角
}

// 带阴影卡片
.card-shadow {
  @include card-shadow;
}

// 带边框卡片
.card-bordered {
  @include card-bordered;
}
```

### 输入框 Mixins

```scss
// 输入框容器
.input-wrapper {
  @include input-container;
  
  input {
    @include input-field;
  }
}
```

### 文字处理

```scss
// 单行省略
.title {
  @include text-ellipsis;
}

// 多行省略（2行）
.desc {
  @include text-ellipsis-lines(2);
}
```

---

## 📦 组件类使用

全局引入后，可直接在模板中使用：

### 页面布局

```html
<div class="page">
  <div class="navbar">
    <div class="navbar__left">返回</div>
    <div class="navbar__title">标题</div>
  </div>
  <div class="page-content">
    <!-- 内容 -->
  </div>
</div>
```

### 按钮

```html
<button class="btn-primary">确认</button>
<button class="btn-secondary">取消</button>
<div class="btn-group">
  <button class="btn-secondary">取消</button>
  <button class="btn-primary">确认</button>
</div>
```

### 表单

```html
<div class="form-label">金额</div>
<div class="input-box">
  <span class="input-box__prefix">¥</span>
  <input class="input-box__input" placeholder="请输入金额" />
  <span class="input-box__suffix">全部</span>
</div>
<div class="form-tip">最低 20 元</div>
```

### 列表

```html
<div class="list">
  <div class="list__item list__item--clickable">
    <img class="list__icon" src="icon.png" />
    <div class="list__content">
      <div class="list__title">设置</div>
      <div class="list__desc">账户安全设置</div>
    </div>
    <span class="list__arrow">›</span>
  </div>
</div>
```

### 标签/徽章

```html
<span class="tag tag--primary">进行中</span>
<span class="tag tag--success">已完成</span>
<span class="tag tag--danger">已过期</span>
<span class="dot"></span>
<span class="badge">99+</span>
```

### 金额显示

```html
<span class="amount amount--warning amount--large">1,234.56</span>
```

---

## 🛠 工具类

支持类似 Tailwind CSS 的原子级工具类：

### 文字

```html
<span class="text-primary">主文字</span>
<span class="text-green">绿色文字</span>
<span class="text-lg font-bold">大号加粗</span>
<span class="text-center">居中</span>
<span class="text-ellipsis">超长文本会省略...</span>
```

### 间距

```html
<div class="mt-lg mb-md">上边距大，下边距中</div>
<div class="px-lg py-sm">左右大间距，上下小间距</div>
<div class="m-0 p-0">无间距</div>
```

### Flex 布局

```html
<div class="flex items-center justify-between gap-md">
  <span>左</span>
  <span>右</span>
</div>
```

### 显示/隐藏

```html
<div class="hidden">隐藏</div>
<div class="block">显示</div>
```

---

## 🎬 动画使用

### Vue Transition

```html
<transition name="slide-left">
  <div v-if="show">内容</div>
</transition>

<transition name="fade">
  <div v-if="show">淡入淡出</div>
</transition>

<transition name="dropdown-fade">
  <div v-if="show">下拉菜单</div>
</transition>
```

### 刷新动画

```html
<img 
  class="refresh-icon" 
  :class="{ spinning: isLoading }" 
  @click="refresh"
/>
```

### 点击反馈

```html
<div class="tap-scale" @click="handleClick">点击缩放</div>
```

---

## 📝 重构示例

### 重构前（单组件样式）

```vue
<style scoped>
.my-page {
  min-height: 100vh;
  background: #f7f8fa;
  font-family: -apple-system, BlinkMacSystemFont, "PingFang SC";
}
.my-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 48px;
  background: #26A17B;
  color: #fff;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 500;
}
.my-btn:active {
  transform: scale(0.98);
}
</style>
```

### 重构后（使用 Mixins）

```vue
<style lang="scss" scoped>
@import '@/assets/styles/mixins.scss';

.my-page {
  @include page-container;
}

.my-btn {
  @include btn-primary;
}
</style>
```

或者直接使用组件类：

```html
<div class="page">
  <button class="btn-primary">确认</button>
</div>
```

---

## ✅ 推荐做法

1. **优先使用工具类**：简单的间距、颜色、布局直接用工具类
2. **复用组件类**：按钮、卡片、列表等用预定义的组件类
3. **自定义样式用 Mixins**：需要扩展时使用 Mixins
4. **统一使用变量**：颜色、字号等使用变量，方便后期维护
5. **避免重复定义**：先查看是否有现成的样式可用

---

## 📊 样式文件大小对比

| 文件 | 作用 | 大小 |
|------|------|------|
| `variables.scss` | 变量定义 | ~5KB |
| `mixins.scss` | Mixins | ~14KB |
| `animations.scss` | 动画 | ~4KB |
| `components.scss` | 组件类 | ~35KB |
| `utilities.scss` | 工具类 | ~5KB |
| `index.scss` | 入口+Vant覆盖 | ~5KB |
| **总计** | | **~68KB** |

编译后 gzip 压缩约 **8-10KB**，可显著减少重复样式代码。

---

## 🆕 新增组件样式

### 筛选器

```html
<div class="filter-bar">
  <div class="filter-btn-wrap">
    <div class="filter-btn active">今日</div>
    <div class="picker-dropdown">
      <div class="picker-item active">今日</div>
      <div class="picker-item">昨日</div>
    </div>
  </div>
</div>
```

### 记录列表

```html
<div class="record-item">
  <div class="record-left">
    <div class="record-title">充值</div>
    <div class="record-time">2024-01-01 12:00</div>
  </div>
  <div class="record-right">
    <div class="record-amount plus">+100.00</div>
    <div class="record-balance">余额: 1000.00</div>
  </div>
</div>
```

### 投注记录

```html
<div class="bet-item">
  <div class="bet-header">
    <span class="bet-game">快三</span>
    <span class="bet-status green">已中奖</span>
  </div>
  <div class="bet-info">
    <div class="bet-row">
      <span>期号: 20240101001</span>
      <span class="bet-win plus">盈亏: +50.00</span>
    </div>
  </div>
</div>
```

### 统计栏

```html
<div class="footer-stats">
  <div class="stat-item">
    <span class="label">累计充值</span>
    <span class="value green">1000.00</span>
  </div>
</div>
```

### Tab 滚动

```html
<div class="tabs-scroll">
  <div class="tab-item active">首页</div>
  <div class="tab-item">推广</div>
  <div class="tab-item">数据</div>
</div>
```

### 时间筛选

```html
<div class="time-filter">
  <div class="filter-btn active">今日</div>
  <div class="filter-btn">昨日</div>
  <div class="filter-btn">本周</div>
</div>
```

### 平台网格

```html
<div class="platform-grid">
  <div class="platform-card">
    <img class="platform-card__icon" src="icon.png" />
    <span class="platform-card__name">平台名</span>
    <span class="platform-card__balance">100.00</span>
  </div>
</div>
```

### 搜索框

```html
<div class="search-box">
  <van-field placeholder="搜索" left-icon="search" />
</div>
```

---

## 🔧 新增 Mixins

```scss
@include page-header;       // 页面头部
@include header-back;       // 返回按钮
@include header-title;      // 头部标题
@include filter-btn;        // 筛选按钮
@include dropdown;          // 下拉菜单
@include dropdown-item;     // 下拉选项
@include record-item;       // 记录项
@include amount-color;      // 金额颜色（plus/minus）
@include status-tag;        // 状态标签
@include stats-bar;         // 统计栏
@include stat-item;         // 统计项
@include tab-item;          // Tab项
@include grid-2cols;        // 2列网格
@include grid-3cols;        // 3列网格
@include avatar($size);     // 头像
@include avatar-with-edit;  // 带编辑图标头像
@include option-item;       // 选项卡项
@include amount-input-container; // 金额输入容器
@include currency-symbol;   // 货币符号
@include detail-modal;      // 详情弹窗
@include detail-header;     // 详情弹窗头部
@include detail-row;        // 详情弹窗行
@include icon-filter-primary; // 主题色图标滤镜
@include gradient-primary;  // 主题色渐变
```

---

## 🆕 新增组件样式（第二批）

### 用户信息模块

```html
<div class="user-info">
  <div class="avatar-box">
    <img class="avatar-box__img" src="avatar.png" />
    <span class="avatar-box__edit"><van-icon name="edit" /></span>
  </div>
  <div class="user-details">
    <div class="user-details__name">用户名</div>
    <div class="user-details__id">ID: 123456 <van-icon name="orders-o" class="copy-icon" /></div>
  </div>
</div>
```

### 余额显示（大）

```html
<div class="balance-display">
  <img class="balance-display__icon" src="coin.png" />
  <span class="balance-display__value">1,000.00</span>
  <img class="balance-display__refresh" src="refresh.png" />
</div>
```

### 快捷操作

```html
<div class="quick-actions">
  <div class="action-item action-item--divider-right">
    <div class="action-item__icon-wrap">
      <img class="action-item__icon" src="icon.png" />
    </div>
    <span class="action-item__text">充值</span>
  </div>
</div>
```

### 支付方式选择器

```html
<div class="pay-method-list">
  <div class="pay-method-item active">
    <div class="method-icon method-icon--usdt">U</div>
    <span class="method-name">USDT</span>
  </div>
  <div class="pay-method-item">
    <div class="method-icon method-icon--alipay">A</div>
    <span class="method-name">支付宝</span>
  </div>
</div>
```

### 金额选择网格

```html
<div class="amount-grid">
  <div class="amount-item">100</div>
  <div class="amount-item active">200</div>
  <div class="amount-item">500</div>
  <div class="amount-item">1000</div>
</div>
```

### 金额输入框

```html
<div class="amount-input">
  <span class="amount-input__symbol">¥</span>
  <input class="amount-input__field" placeholder="请输入金额" />
  <span class="amount-input__all">全部</span>
</div>
```

### 下拉选择器（带图标）

```html
<div class="dropdown-selector">
  <div class="dropdown-selector__left">
    <img class="dropdown-selector__icon" src="icon.png" />
    <div class="dropdown-selector__info">
      <div class="dropdown-selector__title">USDT-TRC20</div>
      <div class="dropdown-selector__subtitle">余额: 100.00</div>
    </div>
  </div>
  <van-icon name="arrow-down" class="dropdown-selector__arrow" />
</div>
```

### 详情弹窗

```html
<div class="detail-overlay">
  <div class="detail-wrapper">
    <div class="detail-modal">
      <div class="detail-modal__header">
        <span class="detail-modal__title">订单详情</span>
      </div>
      <div class="detail-modal__body">
        <div class="detail-row">
          <span class="detail-row__label">订单号</span>
          <div class="detail-row__value-wrap">
            <span class="detail-row__value detail-row__value--mono">123456789</span>
            <van-icon name="orders-o" class="detail-row__copy" />
          </div>
        </div>
        <div class="detail-row">
          <span class="detail-row__label">金额</span>
          <span class="detail-row__value detail-row__value--green">+100.00</span>
        </div>
      </div>
    </div>
    <div class="close-btn-wrap">
      <van-icon name="cross" class="bottom-close-icon" />
    </div>
  </div>
</div>
```

### 报表样式

```html
<div class="report-container">
  <div class="report-section">
    <div class="section-title">汇总统计</div>
    <div class="summary-grid">
      <div class="summary-item">
        <span class="summary-item__label">总投注</span>
        <span class="summary-item__value">10,000.00</span>
      </div>
      <div class="summary-item">
        <span class="summary-item__label">盈亏</span>
        <span class="summary-item__value summary-item__value--green">+500.00</span>
      </div>
    </div>
  </div>
</div>
```

### 分类明细

```html
<div class="category-list">
  <div class="category-item">
    <div class="category-item__header">
      <span class="category-item__name">彩票</span>
      <span class="category-item__platform">快三</span>
    </div>
    <div class="category-item__stats">
      <span>投注: 1000</span>
      <span class="green">盈亏: +100</span>
    </div>
  </div>
</div>
```

### 每日明细

```html
<div class="daily-list">
  <div class="daily-header">
    <span>日期</span>
    <span>投注</span>
    <span>中奖</span>
    <span>盈亏</span>
  </div>
  <div class="daily-item">
    <span>01-01</span>
    <span>1000</span>
    <span>800</span>
    <span class="red">-200</span>
  </div>
</div>
```

### 状态文字颜色类

```html
<span class="green-text">成功</span>
<span class="red-text">失败</span>
<span class="blue-text">处理中</span>
<span class="orange-text">警告</span>
<span class="gray-text">已关闭</span>
```

### 单元格图标

```html
<van-icon name="user-o" class="cell-icon cell-icon--green" />
<van-icon name="lock" class="cell-icon cell-icon--red" />
```

---

## 📦 透明背景色变量

用于状态标签等需要透明背景的场景：

```scss
$color-primary-bg: rgba(38, 161, 123, 0.1);  // 主色透明背景
$color-success-bg: rgba(7, 193, 96, 0.1);    // 成功状态背景
$color-danger-bg: rgba(255, 77, 79, 0.1);    // 危险/错误背景
$color-warning-bg: rgba(255, 170, 9, 0.1);   // 警告状态背景
$color-info-bg: rgba(24, 144, 255, 0.1);     // 信息状态背景
```

**用法示例：**

```scss
.status-tag {
  &.success { color: $color-success; background: $color-success-bg; }
  &.danger { color: $color-danger; background: $color-danger-bg; }
}
```

