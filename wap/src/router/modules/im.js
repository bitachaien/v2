

export default [
  {
    path: '/im',
    component: () => import('@/views/im/ImLayout.vue'),
    redirect: '/im/chats',
    children: [
      {
        path: 'chats',
        name: 'ImChats',
        component: () => import('@/views/im/ConversationList.vue'),
        meta: { title: '消息', requiresAuth: true }
      },
      {
        path: 'contacts',
        name: 'ImContacts',
        component: () => import('@/views/im/Contacts.vue'),
        meta: { title: '通讯录', requiresAuth: true }
      },
      {
        path: 'mine',
        name: 'ImMine',
        component: () => import('@/views/im/Mine.vue'),
        meta: { title: '我的', requiresAuth: true }
      }
    ]
  },
  {
    path: '/im/friend-requests',
    name: 'FriendRequests',
    component: () => import('@/views/im/FriendRequests.vue'),
    meta: { 
      title: '新朋友',
      requiresAuth: true
    }
  },
  {
    path: '/im/add-friend',
    name: 'AddFriend',
    component: () => import('@/views/im/AddFriend.vue'),
    meta: { 
      title: '添加朋友',
      requiresAuth: true
    }
  },
  {
    path: '/im/chat/:chatId',
    name: 'ImChat',
    component: () => import('@/views/im/Chat.vue'),
    meta: { 
      title: '聊天',
      requiresAuth: true
    }
  },
  {
    path: '/im/group/:groupId/members',
    name: 'GroupMembers',
    component: () => import('@/views/im/GroupMembers.vue'),
    meta: { 
      title: '群成员',
      requiresAuth: true
    }
  },
  {
    path: '/im/user/:userId',
    name: 'UserProfile',
    component: () => import('@/views/im/UserProfile.vue'),
    meta: { 
      title: '用户资料',
      requiresAuth: true
    }
  }
]
