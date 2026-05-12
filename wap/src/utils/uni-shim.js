
if (typeof window !== 'undefined') {
  if (!window.uni) {
    window.uni = {
      showToast({ title }) { try { alert(title) } catch(e) {} },
      getSystemInfo({ success } = {}) { success && success({ windowHeight: window.innerHeight }) },
      createSelectorQuery() {
        const api = {
          select() { return api },
          boundingClientRect(cb){ cb && cb({ height: 0, top: 0 }); return api },
          exec(){ return null }
        }
        return api
      },
      switchTab({ url }) { window.location.href = url }
    }
  }
  if (!window.$u) {
    window.$u = {
      timeFormat(ts, fmt){
        const t = typeof ts === 'number' ? (ts>1e12? new Date(ts): new Date(ts*1000)) : new Date()
        const pad = (n)=> String(n).padStart(2,'0')
        const y=t.getFullYear(), m=pad(t.getMonth()+1), d=pad(t.getDate()), h=pad(t.getHours()), mi=pad(t.getMinutes()), s=pad(t.getSeconds())
        return (fmt||'yyyy-mm-dd hh:MM:ss').replace('yyyy',y).replace('mm',m).replace('dd',d).replace('hh',h).replace('MM',mi).replace('ss',s)
      }
    }
  }
}

