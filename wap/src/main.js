import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import axios from 'axios'
import 'vant/lib/index.css'
import '@/assets/css/cyber.css'
import '@/assets/styles/theme.css'
import { safeHtmlDirective } from '@/utils/sanitize'
import { reportError } from '@/utils/errorHandler'
import { initTracker } from '@/utils/tracker'

import { 
  Button, NavBar, ActionSheet, Cell, CellGroup, Icon, Collapse, CollapseItem,
  Popup, DropdownMenu, DropdownItem, Field, Row, Col, Empty, Badge,
  Grid, GridItem, Stepper, SubmitBar, Tabs, Tab, NumberKeyboard, Loading,
  Image as VanImage, Sidebar, SidebarItem, Skeleton, Swipe, SwipeItem, SwipeCell,
  Checkbox, CheckboxGroup, Radio, RadioGroup, Form, Dialog, Toast, Notify,
  PullRefresh, List, Overlay, CountDown, Picker, Switch, Pagination, Search,
  IndexBar, IndexAnchor, Tag, Tabbar, TabbarItem, DatePicker, PasswordInput, Calendar,
  Uploader
} from 'vant'

const app = createApp(App)
const pinia = createPinia()
app.use(pinia)
app.use(router)
app.config.globalProperties.$http = axios

app.use(Button)
app.use(NavBar)
app.use(ActionSheet)
app.use(Cell)
app.use(CellGroup)
app.use(Icon)
app.use(Collapse)
app.use(CollapseItem)
app.use(Popup)
app.use(DropdownMenu)
app.use(DropdownItem)
app.use(Field)
app.use(Row)
app.use(Col)
app.use(Empty)
app.use(Badge)
app.use(Grid)
app.use(GridItem)
app.use(Stepper)
app.use(SubmitBar)
app.use(Tabs)
app.use(Tab)
app.use(NumberKeyboard)
app.use(Loading)
app.use(VanImage)
app.use(Sidebar)
app.use(SidebarItem)
app.use(Skeleton)
app.use(Swipe)
app.use(SwipeItem)
app.use(Checkbox)
app.use(CheckboxGroup)
app.use(Radio)
app.use(RadioGroup)
app.use(Form)
app.use(Dialog)
app.use(Toast)
app.use(Notify)
app.use(PullRefresh)
app.use(List)
app.use(Overlay)
app.use(CountDown)
app.use(Picker)
app.use(Switch)
app.use(Pagination)
app.use(SwipeCell)
app.use(Search)
app.use(IndexBar)
app.use(IndexAnchor)
app.use(Tag)
app.use(Tabbar)
app.use(TabbarItem)
app.use(DatePicker)
app.use(PasswordInput)
app.use(Calendar)
app.use(Uploader)

app.directive('safe-html', safeHtmlDirective)

app.config.errorHandler = (err, instance, info) => {
  reportError(err, {
    component: instance?.$options?.name || instance?.type?.name || 'Unknown',
    info,
    type: 'vue_error'
  })
  if (import.meta.env.DEV) {
    console.error('[Vue Error]', err, info)
  }
}

window.addEventListener('unhandledrejection', (event) => {
  reportError(event.reason, {
    type: 'unhandled_rejection',
    extra: { promise: event.promise }
  })
  if (import.meta.env.DEV) {
    console.error('[Unhandled Promise Rejection]', event.reason)
  }
})

window.addEventListener('error', (event) => {
  if (event.message) {
    reportError(event.error || event.message, {
      type: 'global_error',
      extra: {
        filename: event.filename,
        lineno: event.lineno,
        colno: event.colno
      }
    })
  }
})

initTracker({ debug: import.meta.env.DEV })

import { useUserStore } from '@/stores/user'
const userStore = useUserStore(pinia)
userStore.initWsListeners()

import { useConfigStore } from '@/stores/config'
const configStore = useConfigStore(pinia)
configStore.fetchConfig()

app.mount('#app')

if ('serviceWorker' in navigator && import.meta.env.PROD) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js').catch(() => {})
  })
}
