import { ref } from 'vue'

export const transitionDirection = ref('none')

let isInitialized = false

let isPopState = false

const tabOrder = {
  '/': 0,
  '/home-new': 0,
  '/activity': 1,
  '/vip': 1,
  '/cashback': 1,
  '/pending': 1,
  '/interest': 1,
  '/reward-record': 1,
  '/member': 2
}

if (typeof window !== 'undefined') {
  window.addEventListener('popstate', () => {
    isPopState = true

    setTimeout(() => {
      isPopState = false
    }, 0)
  })
}

export function setTransitionDirection(fromPath, toPath) {

  if (!isInitialized) {

    setTimeout(() => {
      isInitialized = true
    }, 100)
    return
  }
  

  if (isPopState) {

    transitionDirection.value = 'slide-right'
    return
  }
  

  const fromIdx = tabOrder[fromPath]
  const toIdx = tabOrder[toPath]
  
  if (fromIdx !== undefined && toIdx !== undefined) {

    if (fromIdx === toIdx) {
      transitionDirection.value = 'none'
      return
    }
    transitionDirection.value = toIdx > fromIdx ? 'slide-left' : 'slide-right'
    return
  }
  

  if (fromIdx !== undefined && toIdx === undefined) {
    transitionDirection.value = 'slide-left'
    return
  }
  

  if (fromIdx === undefined && toIdx !== undefined) {
    transitionDirection.value = 'slide-right'
    return
  }
  

  transitionDirection.value = 'slide-left'
}
