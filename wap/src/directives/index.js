

const loading = {
  mounted(el, binding) {
    if (binding.value) {
      el.classList.add('loading')
    }
  },
  updated(el, binding) {
    if (binding.value) {
      el.classList.add('loading')
    } else {
      el.classList.remove('loading')
    }
  }
}

const permission = {
  mounted(el, binding) {
    const { value } = binding

  }
}

const lazyload = {
  mounted(el, binding) {
    const observer = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          el.src = binding.value
          observer.unobserve(el)
        }
      })
    })
    observer.observe(el)
  }
}

export default {
  loading,
  permission,
  lazyload
}