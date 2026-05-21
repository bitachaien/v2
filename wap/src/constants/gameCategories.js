/**
 * GSC+ Game Category Constants
 * Use these constants instead of hardcoding category strings
 */

export const GAME_CATEGORIES = {
  // Nổ Hũ - Slot Games
  SLOT: 'NO_HU',
  
  // Casino Trực Tuyến - Live Casino
  LIVE_CASINO: 'CASINO_TRUC_TUYEN',
  
  // Bắn Cá - Fishing Games
  FISHING: 'BAN_CA',
  
  // Thể Thao - Sports Betting
  SPORTS: 'THE_THAO',
  
  // Bài - Card Games
  CARD: 'BAI',
  
  // Xổ Số - Lottery
  LOTTERY: 'XO_SO',
  
  // E-Sports
  ESPORTS: 'E_SPORTS',
  
  // Bàn - Table Games
  TABLE: 'BAN'
}

/**
 * Category Display Names (Vietnamese)
 */
export const CATEGORY_NAMES = {
  NO_HU: 'Nổ Hũ',
  CASINO_TRUC_TUYEN: 'Casino Trực Tuyến',
  BAN_CA: 'Bắn Cá',
  THE_THAO: 'Thể Thao',
  BAI: 'Bài',
  XO_SO: 'Xổ Số',
  E_SPORTS: 'E-Sports',
  BAN: 'Bàn'
}

/**
 * GSC+ Game Type Mapping
 */
export const GSC_GAME_TYPES = {
  NO_HU: 'SLOT',
  CASINO_TRUC_TUYEN: 'LIVE_CASINO',
  BAN_CA: 'FISHING',
  THE_THAO: 'SPORTS',
  BAI: 'CARD_GAME',
  XO_SO: 'LOTTERY',
  E_SPORTS: 'E_SPORTS',
  BAN: 'TABLE_GAME'
}

/**
 * Get category display name
 * @param {string} categoryCode - Category code (e.g., 'NO_HU')
 * @returns {string} Display name
 */
export function getCategoryName(categoryCode) {
  return CATEGORY_NAMES[categoryCode] || categoryCode
}

/**
 * Get GSC+ game type from category
 * @param {string} categoryCode - Category code (e.g., 'NO_HU')
 * @returns {string} GSC+ game type
 */
export function getGscGameType(categoryCode) {
  return GSC_GAME_TYPES[categoryCode] || categoryCode
}

/**
 * Get all categories as array
 * @returns {Array} Array of category objects
 */
export function getAllCategories() {
  return Object.entries(GAME_CATEGORIES).map(([key, value]) => ({
    key,
    code: value,
    name: CATEGORY_NAMES[value],
    gscType: GSC_GAME_TYPES[value]
  }))
}

// Default export
export default {
  GAME_CATEGORIES,
  CATEGORY_NAMES,
  GSC_GAME_TYPES,
  getCategoryName,
  getGscGameType,
  getAllCategories
}