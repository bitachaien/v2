<template>
  <div class="room-members">
    
    <van-nav-bar
      title="房间成员"
      left-arrow
      @click-left="goBack"
    />

    
    <div class="member-group" v-if="kefuList.length > 0">
      <div class="group-header">客服（{{ kefuList.length }}人）</div>
      <div class="member-item" v-for="member in kefuList" :key="member.id">
        <img class="member-avatar" :src="member.avatar" alt="" />
        <span class="member-tag kefu">客服</span>
        <span class="member-name">{{ member.name }}</span>
      </div>
    </div>

    
    <div class="member-group" v-for="group in groupedMembers" :key="group.letter" :id="`group-${group.letter}`">
      <div class="group-header">{{ group.letter }}（{{ group.members.length }}人）</div>
      <div class="member-item" v-for="member in group.members" :key="member.id">
        <img class="member-avatar" :src="member.avatar" alt="" />
        <span class="member-tag member">成员</span>
        <span class="member-name">{{ member.name }}</span>
      </div>
    </div>

    
    <div class="alphabet-index">
      <span 
        class="index-item" 
        :class="{ active: activeIndex === '客' }"
        @click="scrollToGroup('kefu')"
      >客</span>
      <span 
        v-for="letter in alphabetList" 
        :key="letter"
        class="index-item"
        :class="{ active: activeIndex === letter }"
        @click="scrollToGroup(letter)"
      >{{ letter }}</span>
      <span class="index-item" @click="scrollToGroup('#')">#</span>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'

const router = useRouter()
const route = useRoute()

const alphabetList = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('')
const activeIndex = ref('A')

const allMembers = ref([

  { id: 1, name: '在线问题客服', avatar: '/assets/img/default-avatar.png', role: 'kefu', pinyin: '' },
  { id: 2, name: '充值/提现客服', avatar: '/assets/img/default-avatar.png', role: 'kefu', pinyin: '' },
  { id: 3, name: '代理开户', avatar: '/assets/img/default-avatar.png', role: 'kefu', pinyin: '' },

  { id: 4, name: 'Am', avatar: '/assets/img/default-avatar.png', role: 'member', pinyin: 'Am' },
  { id: 5, name: '傲晴', avatar: '/assets/img/default-avatar.png', role: 'member', pinyin: 'AoQing' },
  { id: 6, name: '阿尔瓦', avatar: '/assets/img/default-avatar.png', role: 'member', pinyin: 'AErWa' },
  { id: 7, name: '安蓝儿', avatar: '/assets/img/default-avatar.png', role: 'member', pinyin: 'AnLanEr' },
  { id: 8, name: 'Afra', avatar: '/assets/img/default-avatar.png', role: 'member', pinyin: 'Afra' },
  { id: 9, name: '奥利给', avatar: '/assets/img/default-avatar.png', role: 'member', pinyin: 'AoLiGei' },
  { id: 10, name: '澳门赌圣周星驰', avatar: '/assets/img/default-avatar.png', role: 'member', pinyin: 'AoMen' },
  { id: 11, name: '阿芙拉', avatar: '/assets/img/default-avatar.png', role: 'member', pinyin: 'AFuLa' },
  { id: 12, name: 'Bob', avatar: '/assets/img/default-avatar.png', role: 'member', pinyin: 'Bob' },
  { id: 13, name: '白富美', avatar: '/assets/img/default-avatar.png', role: 'member', pinyin: 'BaiFuMei' }
])

const kefuList = computed(() => allMembers.value.filter(m => m.role === 'kefu'))

const memberList = computed(() => allMembers.value.filter(m => m.role === 'member'))

const groupedMembers = computed(() => {
  const groups = {}
  
  memberList.value.forEach(member => {
    const firstChar = member.pinyin.charAt(0).toUpperCase()
    const letter = /[A-Z]/.test(firstChar) ? firstChar : '#'
    
    if (!groups[letter]) {
      groups[letter] = []
    }
    groups[letter].push(member)
  })
  

  return Object.keys(groups)
    .sort((a, b) => {
      if (a === '#') return 1
      if (b === '#') return -1
      return a.localeCompare(b)
    })
    .map(letter => ({
      letter,
      members: groups[letter]
    }))
})

function goBack() {
  router.back()
}

function scrollToGroup(letter) {
  if (letter === 'kefu') {
    activeIndex.value = '客'
    window.scrollTo({ top: 0, behavior: 'smooth' })
    return
  }
  
  activeIndex.value = letter
  const element = document.getElementById(`group-${letter}`)
  if (element) {
    element.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }
}
</script>

<style lang="less" scoped>
.room-members {
  min-height: 100vh;
  background: #fff;
  padding-bottom: 20px;
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
  
  .member-group {
    .group-header {
      padding: 10px 16px;
      background: #f5f5f5;
      font-size: 14px;
      color: #666;
    }
    
    .member-item {
      display: flex;
      align-items: center;
      padding: 12px 16px;
      border-bottom: 1px solid #f5f5f5;
      
      .member-avatar {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        object-fit: cover;
      }
      
      .member-tag {
        margin-left: 10px;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 11px;
        color: #fff;
        
        &.kefu {
          background: linear-gradient(90deg, #ff6b6b, #ee5a24);
        }
        
        &.member {
          background: #5691fe;
        }
      }
      
      .member-name {
        margin-left: 10px;
        font-size: 15px;
        color: #333;
      }
    }
  }
  
  .alphabet-index {
    position: fixed;
    right: 4px;
    top: 50%;
    transform: translateY(-50%);
    display: flex;
    flex-direction: column;
    align-items: center;
    z-index: 100;
    
    .index-item {
      padding: 2px 6px;
      font-size: 11px;
      color: #999;
      
      &.active {
        color: #5691fe;
        font-weight: 600;
      }
    }
  }
}
</style>
