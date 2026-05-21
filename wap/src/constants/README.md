# Game Constants

## Game Categories

Use these constants instead of hardcoding category strings in your code.

### Import

```javascript
import { GAME_CATEGORIES, getCategoryName } from '@/constants/gameCategories'
```

### Usage Examples

#### 1. Get Platforms by Category
```javascript
import { gameApi } from '@/api/game'
import { GAME_CATEGORIES } from '@/constants/gameCategories'

// ✅ Good - Using constants
const platforms = await gameApi.getGscPlusPlatforms({ 
  category: GAME_CATEGORIES.SLOT 
})

// ❌ Bad - Hardcoded string
const platforms = await gameApi.getGscPlusPlatforms({ 
  category: 'NO_HU' 
})
```

#### 2. Get Game List
```javascript
import { GAME_CATEGORIES } from '@/constants/gameCategories'

const games = await gameApi.getGscPlusGameList({
  category: GAME_CATEGORIES.LIVE_CASINO,
  platform: 'PG',
  page: 1,
  limit: 20
})
```

#### 3. Display Category Name
```javascript
import { getCategoryName } from '@/constants/gameCategories'

const categoryCode = 'NO_HU'
const displayName = getCategoryName(categoryCode) // Returns: "Nổ Hũ"
```

#### 4. Get All Categories
```javascript
import { getAllCategories } from '@/constants/gameCategories'

const categories = getAllCategories()
// Returns:
// [
//   { key: 'SLOT', code: 'NO_HU', name: 'Nổ Hũ', gscType: 'SLOT' },
//   { key: 'LIVE_CASINO', code: 'CASINO_TRUC_TUYEN', name: 'Casino Trực Tuyến', gscType: 'LIVE_CASINO' },
//   ...
// ]
```

#### 5. Loop Through Categories
```javascript
import { GAME_CATEGORIES, CATEGORY_NAMES } from '@/constants/gameCategories'

Object.entries(GAME_CATEGORIES).forEach(([key, code]) => {
  console.log(`${key}: ${code} - ${CATEGORY_NAMES[code]}`)
})
```

### Available Categories

| Constant | Code | Name | GSC Type |
|----------|------|------|----------|
| `GAME_CATEGORIES.SLOT` | NO_HU | Nổ Hũ | SLOT |
| `GAME_CATEGORIES.LIVE_CASINO` | CASINO_TRUC_TUYEN | Casino Trực Tuyến | LIVE_CASINO |
| `GAME_CATEGORIES.FISHING` | BAN_CA | Bắn Cá | FISHING |
| `GAME_CATEGORIES.SPORTS` | THE_THAO | Thể Thao | SPORTS |
| `GAME_CATEGORIES.CARD` | BAI | Bài | CARD_GAME |
| `GAME_CATEGORIES.LOTTERY` | XO_SO | Xổ Số | LOTTERY |
| `GAME_CATEGORIES.ESPORTS` | E_SPORTS | E-Sports | E_SPORTS |
| `GAME_CATEGORIES.TABLE` | BAN | Bàn | TABLE_GAME |

### Complete Vue Component Example

```vue
<template>
  <div class="game-categories">
    <div 
      v-for="category in categories" 
      :key="category.code"
      @click="selectCategory(category.code)"
      :class="{ active: selectedCategory === category.code }"
    >
      {{ category.name }}
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { gameApi } from '@/api/game'
import { GAME_CATEGORIES, getAllCategories } from '@/constants/gameCategories'

const categories = ref([])
const selectedCategory = ref(GAME_CATEGORIES.SLOT)
const platforms = ref([])

onMounted(async () => {
  // Load all categories
  categories.value = getAllCategories()
  
  // Load platforms for default category
  await loadPlatforms(selectedCategory.value)
})

const selectCategory = async (categoryCode) => {
  selectedCategory.value = categoryCode
  await loadPlatforms(categoryCode)
}

const loadPlatforms = async (categoryCode) => {
  const res = await gameApi.getGscPlusPlatforms({ 
    category: categoryCode 
  })
  if (res.code === 0) {
    platforms.value = res.data
  }
}
</script>
```

### Benefits of Using Constants

1. **Type Safety** - Autocomplete in IDE
2. **Refactoring** - Easy to update all usages
3. **Consistency** - Same values across codebase
4. **Documentation** - Clear what values are valid
5. **Maintainability** - Single source of truth

### Migration Guide

If you have existing code with hardcoded strings:

```javascript
// Before
const platforms = await gameApi.getGscPlusPlatforms({ category: 'NO_HU' })

// After
import { GAME_CATEGORIES } from '@/constants/gameCategories'
const platforms = await gameApi.getGscPlusPlatforms({ 
  category: GAME_CATEGORIES.SLOT 
})
```

### API Reference

See `gameCategories.js` for full API documentation.