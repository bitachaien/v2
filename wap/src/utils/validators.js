

export function validatePhone(phone) {
  if (!phone || typeof phone !== 'string') return false
  return /^1[3-9]\d{9}$/.test(phone.trim())
}

export function validateEmail(email) {
  if (!email || typeof email !== 'string') return false
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.trim())
}

export function validateBankCard(cardNo) {
  if (!cardNo || typeof cardNo !== 'string') return false
  
  const cleaned = cardNo.replace(/\s|-/g, '')
  

  if (!/^\d{16,19}$/.test(cleaned)) return false
  

  let sum = 0
  let isEven = false
  
  for (let i = cleaned.length - 1; i >= 0; i--) {
    let digit = parseInt(cleaned[i], 10)
    
    if (isEven) {
      digit *= 2
      if (digit > 9) digit -= 9
    }
    
    sum += digit
    isEven = !isEven
  }
  
  return sum % 10 === 0
}

export function validateFundPassword(password) {
  if (!password || typeof password !== 'string') {
    return { valid: false, message: '请输入资金密码' }
  }
  
  if (password.length !== 6) {
    return { valid: false, message: '资金密码必须为6位' }
  }
  
  if (!/^\d{6}$/.test(password)) {
    return { valid: false, message: '资金密码必须全为数字' }
  }
  

  const simplePatterns = ['123456', '654321', '111111', '000000', '888888', '666666']
  if (simplePatterns.includes(password)) {
    return { valid: false, message: '资金密码过于简单，请重新设置' }
  }
  

  const digits = password.split('').map(Number)
  let isSequential = true
  for (let i = 1; i < digits.length; i++) {
    if (Math.abs(digits[i] - digits[i - 1]) !== 1) {
      isSequential = false
      break
    }
  }
  if (isSequential) {
    return { valid: false, message: '资金密码不能为连续数字' }
  }
  
  return { valid: true, message: '' }
}

export function validateLoginPassword(password) {
  if (!password || typeof password !== 'string') {
    return { valid: false, strength: 'none', message: '请输入密码' }
  }
  
  if (password.length < 6) {
    return { valid: false, strength: 'weak', message: '密码长度至少6位' }
  }
  
  if (password.length > 20) {
    return { valid: false, strength: 'invalid', message: '密码长度不能超过20位' }
  }
  

  let score = 0
  if (password.length >= 8) score++
  if (/[a-z]/.test(password)) score++
  if (/[A-Z]/.test(password)) score++
  if (/\d/.test(password)) score++
  if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) score++
  
  const strengthMap = {
    0: 'weak',
    1: 'weak',
    2: 'medium',
    3: 'medium',
    4: 'strong',
    5: 'strong'
  }
  
  return {
    valid: true,
    strength: strengthMap[score],
    message: score < 2 ? '建议使用更复杂的密码' : ''
  }
}

export function validateUsername(username) {
  if (!username || typeof username !== 'string') {
    return { valid: false, message: '请输入用户名' }
  }
  
  const trimmed = username.trim()
  
  if (trimmed.length < 4 || trimmed.length > 12) {
    return { valid: false, message: '用户名长度为4-12位' }
  }
  
  if (!/^[a-zA-Z0-9_]+$/.test(trimmed)) {
    return { valid: false, message: '用户名只能包含字母、数字和下划线' }
  }
  
  if (/^\d+$/.test(trimmed)) {
    return { valid: false, message: '用户名不能为纯数字' }
  }
  
  return { valid: true, message: '' }
}

export function validateWithdrawAmount(amount, min = 50, max = 2000000, balance = Infinity) {
  const num = parseFloat(amount)
  
  if (isNaN(num) || num <= 0) {
    return { valid: false, message: '请输入有效金额' }
  }
  
  if (num < min) {
    return { valid: false, message: `最低提现金额为${min}元` }
  }
  
  if (num > max) {
    return { valid: false, message: `最高提现金额为${max}元` }
  }
  
  if (num > balance) {
    return { valid: false, message: '提现金额超过可用余额' }
  }
  

  if (!Number.isInteger(num)) {
    return { valid: false, message: '提现金额必须为整数' }
  }
  
  return { valid: true, message: '' }
}

export function validateUsdtAddress(address, network = 'TRC20') {
  if (!address || typeof address !== 'string') {
    return { valid: false, message: '请输入USDT地址' }
  }
  
  const trimmed = address.trim()
  
  const patterns = {
    TRC20: /^T[a-zA-Z0-9]{33}$/,
    ERC20: /^0x[a-fA-F0-9]{40}$/,
    BEP20: /^0x[a-fA-F0-9]{40}$/
  }
  
  const pattern = patterns[network]
  if (!pattern) {
    return { valid: false, message: '不支持的网络类型' }
  }
  
  if (!pattern.test(trimmed)) {
    return { valid: false, message: `无效的${network}地址格式` }
  }
  
  return { valid: true, message: '' }
}

export default {
  validatePhone,
  validateEmail,
  validateBankCard,
  validateFundPassword,
  validateLoginPassword,
  validateUsername,
  validateWithdrawAmount,
  validateUsdtAddress
}
