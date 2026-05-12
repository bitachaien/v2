export const activities = [
  {
    path: '/activity/daily-first-deposit',
    title: '每日首存',
    images: ['/assets/images/activity/backgrounds/activity_bg7.jpg', '/assets/images/activity/2019080919123563911.jpg'],
    showTitleInBanner: true,
    content: `
      <div class="promotion_explain">
        <h2 class="promotion_h2">活动规则</h2>
        每日首存100元及以上，赠送8元彩金<br>
        每日首存300元及以上，赠送18元彩金<br>
        每日首存1000元及以上，赠送38元彩金
      </div>
      <div class="promotion_explain">
        <h2 class="promotion_h2">注意事项</h2>
        1. 每日仅限首次充值参与<br>
        2. 需联系在线客服领取<br>
        3. 活动与其它优惠可叠加
      </div>
    `,
    hasClaim: true
  },
  {
    path: '/activity/bank-transfer',
    title: '网银转账银行卡',
    images: ['/assets/images/activity/backgrounds/activity_bg5.jpg', '/assets/images/activity/201906211324465121.jpg'],
    showTitleInBanner: true,
    content: `
      <div class="promotion_explain">
        <h2 class="promotion_h2">活动时间</h2>
        2017年6月08日起长期有效
      </div>
      <div class="promotion_explain">
        <h2 class="promotion_h2">活动规则</h2>
        1. 充值100元以上且有提款记录即可参与<br>
        2. 系统自动抽奖，奖金直接打入银行卡
      </div>
    `,
    hasClaim: false
  },
  {
    path: '/activity/lucky-first',
    title: '幸运首充',
    images: ['/assets/images/activity/backgrounds/activity_bg3.jpg'],
    showTitleInBanner: false,
    content: `
      <div class="promotion_explain">
        <h2 class="promotion_h2">活动详情</h2>
        新会员首充100元送18元<br>
        首充300元送28元<br>
        首充1000元送88元
      </div>
      <div class="promotion_explain">
        <h2 class="promotion_h2">申请方式</h2>
        充值后联系客服QQ：69236869 领取
      </div>
    `,
    hasClaim: false
  },
  {
    path: '/activity/lucky-credit',
    title: '幸运额度',
    images: ['/assets/images/activity/backgrounds/activity_bg6.jpg'],
    showTitleInBanner: false,
    content: `
      <div class="promotion_explain">
        <h2 class="promotion_h2">活动规则</h2>
        当日亏损10000元 → 次日可借289元<br>
        当日亏损30000元 → 次日可借389元<br>
        当日亏损100000元 → 次日可借1888元
      </div>
      <div class="promotion_explain">
        <h2 class="promotion_h2">注意事项</h2>
        需联系客服申请，无流水要求
      </div>
    `,
    hasClaim: false
  },
  {
    path: '/activity/recommend-app',
    title: '首存送彩金，下载APP送彩金',
    images: ['/assets/images/activity/backgrounds/activity_bg4.jpg', '/assets/images/activity/2019080919154286861.jpg'],
    showTitleInBanner: true,
    content: `
      <div class="promotion_explain">
        <h2 class="promotion_h2">推荐好友</h2>
        每推荐1位好友充值100元，您得18.88元<br>
        无上限，实时到账
      </div>
      <div class="promotion_explain">
        <h2 class="promotion_h2">APP下载</h2>
        下载APP送8元彩金，扫码即领
      </div>
    `,
    hasClaim: false
  }
]