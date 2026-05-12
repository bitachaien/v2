import CryptoJS from 'crypto-js'

const SECRET_KEY = 'QD2024SecretKey1234567890ABCDEF0' // 32字节
const SECRET_IV = 'QD2024IV12345678' // 16字节

export function decrypt(encryptedData) {
  try {
    const key = CryptoJS.enc.Utf8.parse(SECRET_KEY)
    const iv = CryptoJS.enc.Utf8.parse(SECRET_IV)
    

    const ciphertext = CryptoJS.enc.Base64.parse(encryptedData)
    

    const cipherParams = CryptoJS.lib.CipherParams.create({
      ciphertext: ciphertext
    })
    
    const decrypted = CryptoJS.AES.decrypt(cipherParams, key, {
      iv: iv,
      mode: CryptoJS.mode.CBC,
      padding: CryptoJS.pad.Pkcs7
    })
    
    const decryptedStr = decrypted.toString(CryptoJS.enc.Utf8)
    if (!decryptedStr) {
      throw new Error('解密结果为空')
    }
    return JSON.parse(decryptedStr)
  } catch (e) {
    console.error('解密失败:', e)
    return null
  }
}

export function encrypt(data) {
  try {
    const key = CryptoJS.enc.Utf8.parse(SECRET_KEY)
    const iv = CryptoJS.enc.Utf8.parse(SECRET_IV)
    
    const encrypted = CryptoJS.AES.encrypt(JSON.stringify(data), key, {
      iv: iv,
      mode: CryptoJS.mode.CBC,
      padding: CryptoJS.pad.Pkcs7
    })
    
    return encrypted.ciphertext.toString(CryptoJS.enc.Base64)
  } catch (e) {
    console.error('加密失败:', e)
    return null
  }
}

export function isEncrypted(data) {
  if (typeof data !== 'string') return false

  return /^[A-Za-z0-9+/=]+$/.test(data) && data.length > 20
}
