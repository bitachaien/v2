

export function escapeHtml(str) {
  if (!str || typeof str !== 'string') return ''
  const map = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#x27;',
    '/': '&#x2F;'
  }
  return str.replace(/[&<>"'/]/g, char => map[char])
}

const ALLOWED_TAGS = [
  'p', 'br', 'b', 'i', 'u', 'strong', 'em', 'span', 'div',
  'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
  'ul', 'ol', 'li', 'a', 'img',
  'table', 'thead', 'tbody', 'tr', 'th', 'td',
  'blockquote', 'pre', 'code'
]

const ALLOWED_ATTRS = ['href', 'src', 'alt', 'title', 'class', 'style', 'target', 'rel']

const DANGEROUS_ATTRS = /^on\w+|javascript:|data:/i

export function sanitizeHtml(html, options = {}) {
  if (!html || typeof html !== 'string') return ''
  
  const {
    allowedTags = ALLOWED_TAGS,
    allowedAttrs = ALLOWED_ATTRS,
    allowLinks = true
  } = options
  

  const parser = new DOMParser()
  const doc = parser.parseFromString(html, 'text/html')
  

  function cleanNode(node) {
    if (node.nodeType === Node.ELEMENT_NODE) {
      const tagName = node.tagName.toLowerCase()
      

      if (!allowedTags.includes(tagName)) {
        const fragment = document.createDocumentFragment()
        while (node.firstChild) {
          cleanNode(node.firstChild)
          fragment.appendChild(node.firstChild)
        }
        node.parentNode?.replaceChild(fragment, node)
        return
      }
      

      if (tagName === 'script' || tagName === 'style') {
        node.parentNode?.removeChild(node)
        return
      }
      

      const attrs = Array.from(node.attributes)
      for (const attr of attrs) {
        const attrName = attr.name.toLowerCase()
        const attrValue = attr.value
        

        if (!allowedAttrs.includes(attrName) || DANGEROUS_ATTRS.test(attrName) || DANGEROUS_ATTRS.test(attrValue)) {
          node.removeAttribute(attr.name)
          continue
        }
        

        if ((attrName === 'href' || attrName === 'src')) {
          if (/^javascript:/i.test(attrValue) || /^data:/i.test(attrValue)) {
            node.removeAttribute(attr.name)
          }
        }
        

        if (attrName === 'href' && allowLinks) {
          node.setAttribute('rel', 'noopener noreferrer')
          if (!attrValue.startsWith('/') && !attrValue.startsWith('#')) {
            node.setAttribute('target', '_blank')
          }
        }
      }
      

      Array.from(node.childNodes).forEach(cleanNode)
    }
  }
  

  Array.from(doc.body.childNodes).forEach(cleanNode)
  
  return doc.body.innerHTML
}

export function stripHtml(html) {
  if (!html || typeof html !== 'string') return ''
  const parser = new DOMParser()
  const doc = parser.parseFromString(html, 'text/html')
  return doc.body.textContent || ''
}

export const safeHtmlDirective = {
  mounted(el, binding) {
    el.innerHTML = sanitizeHtml(binding.value)
  },
  updated(el, binding) {
    if (binding.value !== binding.oldValue) {
      el.innerHTML = sanitizeHtml(binding.value)
    }
  }
}

export default {
  escapeHtml,
  sanitizeHtml,
  stripHtml,
  safeHtmlDirective
}
