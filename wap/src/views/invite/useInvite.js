import { showToast } from 'vant'

export const timeOptions = [
  { label: '今日', value: 'today' },
  { label: '昨日', value: 'yesterday' },
  { label: '本周', value: 'week' },
  { label: '上周', value: 'lastWeek' },
  { label: '本月', value: 'month' },
  { label: '上月', value: 'lastMonth' }
]

export const timeOptionsWithAll = [
  ...timeOptions,
  { label: '全部', value: 'all' }
]

export const formatNumber = (num) => {
  const n = parseFloat(num) || 0
  return n.toFixed(2)
}

export const formatInt = (num) => {
  return parseInt(num) || 0
}

export const handleShare = (link, emit) => {
  if (!link) {
    showToast('邀请链接获取中...')
    return
  }

  if (navigator.share) {
    navigator.share({
      title: '邀请您加入',
      text: '快来加入我们吧！',
      url: link
    })
  } else {
    emit('copy', link)
  }
}
