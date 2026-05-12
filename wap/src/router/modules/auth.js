export default [
  {
    path: '/login',
    name: 'Login',
    redirect: { name: 'Home', query: { auth: 'login' } },
    meta: { 
      title: '登录',
      guest: true
    }
  },
  {
    path: '/register',
    name: 'Register',
    redirect: { name: 'Home', query: { auth: 'register' } },
    meta: { 
      title: '注册',
      guest: true
    }
  }
]
