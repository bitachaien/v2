
import { useLotteryStore } from './lottery'
import { useUserStore } from './user'

export {
  useLotteryStore,
  useUserStore
}

export default {
  lottery: useLotteryStore,
  user: useUserStore
}