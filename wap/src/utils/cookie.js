export const setCookie = (n, v, d) => {
  const date = new Date(); date.setDate(date.getDate() + d)
  document.cookie = `${n}=${v};expires=${date.toUTCString()}`
}
export const getCookie = (n) => {
  const m = document.cookie.match(new RegExp(n + '=([^;]+)'))
  return m ? m[1] : ''
}
export const delCookie = (n) => setCookie(n, '', -1)