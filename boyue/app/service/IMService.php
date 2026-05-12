<?php

namespace app\service;

use support\Db;
use support\Log;
use support\Redis;

class IMService
{
    
    const MSG_TEXT = 1;
    const MSG_IMAGE = 2;
    const MSG_VOICE = 3;
    const MSG_SYSTEM = 4;
    
    
    const CONV_PRIVATE = 1;
    const CONV_GROUP = 2;
    const CONV_SYSTEM = 3;
    
    
    public static function sendPrivateMessage(int $fromUid, int $toUid, string $content, int $msgType = 1, array $extra = []): array
    {
        
        $targetUser = Db::table('caipiao_member')->where('id', $toUid)->first();
        if (!$targetUser) {
            return ['success' => false, 'error' => '用户不存在'];
        }
        
        
        $blocked = Db::table('im_friends')
            ->where('uid', $toUid)
            ->where('friend_uid', $fromUid)
            ->where('is_blocked', 1)
            ->exists();
        if ($blocked) {
            return ['success' => false, 'error' => '消息发送失败'];
        }
        
        $now = time();
        
        
        $msgId = Db::table('im_messages')->insertGetId([
            'from_uid' => $fromUid,
            'to_uid' => $toUid,
            'msg_type' => $msgType,
            'content' => $content,
            'extra' => json_encode($extra),
            'created_at' => $now,
        ]);
        
        
        self::updateConversation($fromUid, self::CONV_PRIVATE, $toUid, $msgId, $content, $now, false);
        
        
        self::updateConversation($toUid, self::CONV_PRIVATE, $fromUid, $msgId, $content, $now, true);
        
        
        $senderInfo = Db::table('caipiao_member')->where('id', $fromUid)->first();
        $senderName = $senderInfo->nickname ?: $senderInfo->username ?: '用户'.$fromUid;
        
        $message = [
            'msgId' => $msgId,
            'fromUid' => $fromUid,
            'fromName' => $senderName,
            'fromAvatar' => $senderInfo->face ?? '',
            'toUid' => $toUid,
            'msgType' => $msgType,
            'content' => $content,
            'extra' => $extra,
            'createdAt' => $now,
            
            'sender' => [
                'userId' => $fromUid,
                'nickname' => $senderName,
                'avatar' => $senderInfo->face ?? '',
            ],
        ];
        
        
        self::pushToUser($toUid, 'im_message', $message);
        
        
        self::pushUnreadUpdate($toUid);
        
        
        self::pushConversationUpdate($toUid, [
            'targetType' => self::CONV_PRIVATE,
            'targetId' => $fromUid,
            'targetName' => $senderName,
            'targetAvatar' => $senderInfo->face ?? '',
            'lastContent' => mb_substr($content, 0, 50),
            'lastTime' => $now,
            'unreadCount' => 1,  
        ]);
        
        return ['success' => true, 'data' => $message];
    }
    
    
    public static function sendGroupMessage(int $fromUid, int $groupId, string $content, int $msgType = 1, array $extra = []): array
    {
        $now = time();
        
        
        $member = Db::table('im_group_members')
            ->where('group_id', $groupId)
            ->where('uid', $fromUid)
            ->first();
        
        if (!$member) {
            return ['success' => false, 'error' => '非群成员'];
        }
        
        
        if ($member->is_mute) {
            return ['success' => false, 'error' => '您已被禁言'];
        }
        
        
        $msgId = Db::table('im_messages')->insertGetId([
            'from_uid' => $fromUid,
            'to_group' => $groupId,
            'msg_type' => $msgType,
            'content' => $content,
            'extra' => json_encode($extra),
            'created_at' => $now,
        ]);
        
        
        $senderInfo = Db::table('caipiao_member')->where('id', $fromUid)->first();
        $group = Db::table('im_groups')->where('id', $groupId)->first();
        
        $senderName = $member->nickname ?: $senderInfo->nickname ?: $senderInfo->username ?: '用户'.$fromUid;
        $message = [
            'msgId' => $msgId,
            'fromUid' => $fromUid,
            'fromName' => $senderName,
            'fromAvatar' => $senderInfo->face ?? '',
            'groupId' => $groupId,
            'groupName' => $group->name ?? '',
            'msgType' => $msgType,
            'content' => $content,
            'extra' => $extra,
            'createdAt' => $now,
            
            'sender' => [
                'userId' => $fromUid,
                'nickname' => $senderName,
                'avatar' => $senderInfo->face ?? '',
            ],
        ];
        
        
        $members = Db::table('im_group_members')
            ->where('group_id', $groupId)
            ->where('uid', '!=', $fromUid)
            ->pluck('uid');
        
        foreach ($members as $uid) {
            
            self::updateConversation($uid, self::CONV_GROUP, $groupId, $msgId, $content, $now, true);
            
            self::pushToUser($uid, 'im_message', $message);
            
            self::pushUnreadUpdate($uid);
            
            self::pushConversationUpdate($uid, [
                'targetType' => self::CONV_GROUP,
                'targetId' => $groupId,
                'targetName' => $group->name ?? '',
                'targetAvatar' => $group->avatar ?? '',
                'lastContent' => mb_substr($content, 0, 50),
                'lastTime' => $now,
            ]);
        }
        
        
        self::updateConversation($fromUid, self::CONV_GROUP, $groupId, $msgId, $content, $now, false);
        
        return ['success' => true, 'data' => $message];
    }
    
    
    public static function sendSystemNotice(int $toUid, string $content, string $title = '系统通知', array $extra = []): array
    {
        $now = time();
        
        $msgId = Db::table('im_messages')->insertGetId([
            'from_uid' => 0,
            'to_uid' => $toUid,
            'msg_type' => self::MSG_SYSTEM,
            'content' => $content,
            'extra' => json_encode(array_merge(['title' => $title], $extra)),
            'created_at' => $now,
        ]);
        
        self::updateConversation($toUid, self::CONV_SYSTEM, 0, $msgId, $content, $now, true);
        
        $message = [
            'msgId' => $msgId,
            'msgType' => self::MSG_SYSTEM,
            'title' => $title,
            'content' => $content,
            'extra' => $extra,
            'createdAt' => $now,
        ];
        
        self::pushToUser($toUid, 'im_notice', $message);
        
        return ['success' => true, 'data' => $message];
    }
    
    
    private static function updateConversation(int $uid, int $targetType, int $targetId, int $msgId, string $content, int $time, bool $addUnread): void
    {
        $preview = mb_substr($content, 0, 50);
        
        $exists = Db::table('im_conversations')
            ->where('uid', $uid)
            ->where('target_type', $targetType)
            ->where('target_id', $targetId)
            ->exists();
        
        if ($exists) {
            $update = [
                'last_msg_id' => $msgId,
                'last_content' => $preview,
                'last_time' => $time,
                'updated_at' => $time,
            ];
            
            if ($addUnread) {
                Db::table('im_conversations')
                    ->where('uid', $uid)
                    ->where('target_type', $targetType)
                    ->where('target_id', $targetId)
                    ->update($update);
                
                Db::table('im_conversations')
                    ->where('uid', $uid)
                    ->where('target_type', $targetType)
                    ->where('target_id', $targetId)
                    ->increment('unread_count');
            } else {
                $update['unread_count'] = 0;
                Db::table('im_conversations')
                    ->where('uid', $uid)
                    ->where('target_type', $targetType)
                    ->where('target_id', $targetId)
                    ->update($update);
            }
        } else {
            Db::table('im_conversations')->insert([
                'uid' => $uid,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'last_msg_id' => $msgId,
                'last_content' => $preview,
                'last_time' => $time,
                'unread_count' => $addUnread ? 1 : 0,
                'updated_at' => $time,
            ]);
        }
    }
    
    
    public static function getConversations(int $uid): array
    {
        $conversations = Db::table('im_conversations')
            ->where('uid', $uid)
            ->orderBy('is_top', 'desc')
            ->orderBy('updated_at', 'desc')
            ->get();
        
        $result = [];
        foreach ($conversations as $conv) {
            $item = [
                'id' => $conv->id,
                'targetType' => $conv->target_type,
                'targetId' => $conv->target_id,
                'lastContent' => $conv->last_content,
                'lastTime' => $conv->last_time,
                'unreadCount' => $conv->unread_count,
                'isTop' => $conv->is_top,
                'isMute' => $conv->is_mute,
            ];
            
            
            if ($conv->target_type == self::CONV_PRIVATE) {
                $target = Db::table('caipiao_member')->where('id', $conv->target_id)->first();
                $item['targetName'] = $target->nickname ?? $target->username ?? '';
                $item['targetAvatar'] = $target->face ?? '';
            } elseif ($conv->target_type == self::CONV_GROUP) {
                $group = Db::table('im_groups')->where('id', $conv->target_id)->first();
                $item['targetName'] = $group->name ?? '';
                $item['targetAvatar'] = $group->avatar ?? '';
            } else {
                $item['targetName'] = '系统通知';
                $item['targetAvatar'] = '';
            }
            
            $result[] = $item;
        }
        
        return $result;
    }
    
    
    public static function getMessages(int $uid, int $targetType, int $targetId, int $lastMsgId = 0, int $limit = 20): array
    {
        $query = Db::table('im_messages');
        
        if ($targetType == self::CONV_PRIVATE) {
            $query->where(function($q) use ($uid, $targetId) {
                $q->where(function($q2) use ($uid, $targetId) {
                    $q2->where('from_uid', $uid)->where('to_uid', $targetId);
                })->orWhere(function($q2) use ($uid, $targetId) {
                    $q2->where('from_uid', $targetId)->where('to_uid', $uid);
                });
            });
        } elseif ($targetType == self::CONV_GROUP) {
            $query->where('to_group', $targetId);
        } else {
            $query->where('to_uid', $uid)->where('msg_type', self::MSG_SYSTEM);
        }
        
        if ($lastMsgId > 0) {
            $query->where('id', '<', $lastMsgId);
        }
        
        $messages = $query->orderBy('id', 'desc')->limit($limit)->get();
        
        
        $senderIds = array_unique(array_filter($messages->pluck('from_uid')->toArray()));
        $senders = [];
        if (!empty($senderIds)) {
            $senderList = Db::table('caipiao_member')
                ->whereIn('id', $senderIds)
                ->get(['id', 'username', 'nickname', 'face']);
            foreach ($senderList as $s) {
                $senders[$s->id] = $s;
            }
        }
        
        $result = [];
        foreach ($messages as $msg) {
            $sender = $senders[$msg->from_uid] ?? null;
            $senderName = $sender ? ($sender->nickname ?: $sender->username ?: '用户'.$msg->from_uid) : '系统';
            
            $result[] = [
                'msgId' => $msg->id,
                'fromUid' => $msg->from_uid,
                'fromName' => $senderName,
                'fromAvatar' => $sender->face ?? '',
                'msgType' => $msg->msg_type,
                'content' => $msg->content,
                'extra' => json_decode($msg->extra, true) ?? [],
                'isRead' => $msg->is_read ?? 0,
                'isRecall' => $msg->is_recall ?? 0,
                'createdAt' => $msg->created_at,
                'isSelf' => $msg->from_uid == $uid,
                
                'sender' => [
                    'userId' => $msg->from_uid,
                    'nickname' => $senderName,
                    'avatar' => $sender->face ?? '',
                ],
            ];
        }
        
        return array_reverse($result);
    }
    
    
    public static function markAsRead(int $uid, int $targetType, int $targetId): void
    {
        
        Db::table('im_conversations')
            ->where('uid', $uid)
            ->where('target_type', $targetType)
            ->where('target_id', $targetId)
            ->update(['unread_count' => 0]);
        
        
        if ($targetType == self::CONV_PRIVATE) {
            Db::table('im_messages')
                ->where('from_uid', $targetId)
                ->where('to_uid', $uid)
                ->where('is_read', 0)
                ->update(['is_read' => 1, 'read_at' => time()]);
        }
    }
    
    
    public static function getUnreadCount(int $uid): int
    {
        return (int) Db::table('im_conversations')
            ->where('uid', $uid)
            ->sum('unread_count');
    }
    
    
    private static function pushToUser(int $userId, string $type, array $data): void
    {
        try {
            WebSocketPusher::pushToUser($userId, $type, $data);
        } catch (\Exception $e) {
            Log::warning('[IM] 推送失败: ' . $e->getMessage());
        }
    }
    
    
    public static function pushConversationUpdate(int $userId, array $conversation): void
    {
        self::pushToUser($userId, 'im_conversation_update', $conversation);
    }
    
    
    public static function pushUnreadUpdate(int $userId): void
    {
        $count = self::getUnreadCount($userId);
        self::pushToUser($userId, 'im_unread_update', ['total' => $count]);
    }
    
    
    public static function pushGroupNotice(int $userId, string $action, array $data): void
    {
        self::pushToUser($userId, 'im_group_notice', [
            'action' => $action,  
            'data' => $data,
        ]);
    }
    
    
    public static function pushPresence(int $userId, int $targetUserId, bool $isOnline): void
    {
        self::pushToUser($userId, 'im_presence', [
            'userId' => $targetUserId,
            'isOnline' => $isOnline,
        ]);
    }
    
    
    public static function createGroup(int $ownerUid, string $name, array $memberUids = []): array
    {
        $now = time();
        
        $groupId = Db::table('im_groups')->insertGetId([
            'name' => $name,
            'owner_uid' => $ownerUid,
            'member_count' => 1 + count($memberUids),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        
        
        Db::table('im_group_members')->insert([
            'group_id' => $groupId,
            'uid' => $ownerUid,
            'role' => 2,
            'joined_at' => $now,
        ]);
        
        
        $group = Db::table('im_groups')->where('id', $groupId)->first();
        foreach ($memberUids as $uid) {
            Db::table('im_group_members')->insert([
                'group_id' => $groupId,
                'uid' => $uid,
                'role' => 0,
                'joined_at' => $now,
            ]);
            
            
            self::pushGroupNotice($uid, 'invited', [
                'groupId' => $groupId,
                'groupName' => $group->name ?? '',
            ]);
        }
        
        return ['success' => true, 'groupId' => $groupId];
    }
    
    
    
    
    public static function getContacts(int $uid): array
    {
        $friends = Db::table('im_friends as f')
            ->leftJoin('caipiao_member as m', 'f.friend_uid', '=', 'm.id')
            ->where('f.uid', $uid)
            ->where('f.is_blocked', 0)
            ->select('f.friend_uid as uid', 'f.remark', 'm.nickname', 'm.username', 'm.face')
            ->get();
        
        $result = [];
        foreach ($friends as $f) {
            $result[] = [
                'userId' => $f->uid,
                'nickname' => $f->remark ?: $f->nickname ?: $f->username,
                'avatar' => $f->face ?? '',
                'remark' => $f->remark,
            ];
        }
        return $result;
    }
    
    
    public static function getFriendRequests(int $uid): array
    {
        
        $requests = Db::table('im_friend_requests as r')
            ->leftJoin('caipiao_member as m', 'r.from_uid', '=', 'm.id')
            ->where('r.to_uid', $uid)
            ->where('r.created_at', '>', time() - 86400 * 30)
            ->select('r.*', 'm.nickname', 'm.username', 'm.face')
            ->orderBy('r.created_at', 'desc')
            ->limit(50)
            ->get();
        
        $result = [];
        foreach ($requests as $r) {
            
            $statusMap = [0 => 'pending', 1 => 'accepted', 2 => 'rejected'];
            $result[] = [
                'id' => $r->id,
                'fromUid' => $r->from_uid,
                'nickname' => $r->nickname ?: $r->username,
                'avatar' => $r->face ?? '',
                'remark' => $r->message,
                'message' => $r->message,
                'status' => $statusMap[$r->status] ?? 'pending',
                'createdAt' => $r->created_at,
            ];
        }
        return $result;
    }
    
    
    public static function sendFriendRequest(int $fromUid, int $toUid, string $message = ''): array
    {
        if ($fromUid == $toUid) {
            return ['success' => false, 'error' => '不能添加自己'];
        }
        
        
        $exists = Db::table('im_friends')
            ->where('uid', $fromUid)
            ->where('friend_uid', $toUid)
            ->exists();
        if ($exists) {
            return ['success' => false, 'error' => '已经是好友'];
        }
        
        
        $pending = Db::table('im_friend_requests')
            ->where('from_uid', $fromUid)
            ->where('to_uid', $toUid)
            ->where('status', 0)
            ->exists();
        if ($pending) {
            return ['success' => false, 'error' => '请求已发送，等待对方处理'];
        }
        
        $now = time();
        Db::table('im_friend_requests')->insert([
            'from_uid' => $fromUid,
            'to_uid' => $toUid,
            'message' => $message,
            'status' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        
        
        self::pushToUser($toUid, 'im_friend_request', [
            'fromUid' => $fromUid,
            'message' => $message,
        ]);
        
        return ['success' => true];
    }
    
    
    public static function handleFriendRequest(int $uid, int $requestId, bool $accept): array
    {
        $request = Db::table('im_friend_requests')
            ->where('id', $requestId)
            ->where('to_uid', $uid)
            ->where('status', 0)
            ->first();
        
        if (!$request) {
            return ['success' => false, 'error' => '请求不存在'];
        }
        
        $now = time();
        Db::table('im_friend_requests')
            ->where('id', $requestId)
            ->update(['status' => $accept ? 1 : 2, 'updated_at' => $now]);
        
        if ($accept) {
            
            Db::table('im_friends')->insertOrIgnore([
                ['uid' => $uid, 'friend_uid' => $request->from_uid, 'created_at' => $now],
                ['uid' => $request->from_uid, 'friend_uid' => $uid, 'created_at' => $now],
            ]);
            
            
            self::pushToUser($request->from_uid, 'im_friend_accepted', ['userId' => $uid]);
        }
        
        return ['success' => true];
    }
    
    
    public static function setFriendRemark(int $uid, int $friendUid, string $remark): bool
    {
        return Db::table('im_friends')
            ->where('uid', $uid)
            ->where('friend_uid', $friendUid)
            ->update(['remark' => $remark]) > 0;
    }
    
    
    public static function blockFriend(int $uid, int $friendUid, bool $block): bool
    {
        return Db::table('im_friends')
            ->where('uid', $uid)
            ->where('friend_uid', $friendUid)
            ->update(['is_blocked' => $block ? 1 : 0]) > 0;
    }
    
    
    public static function deleteFriend(int $uid, int $friendUid): bool
    {
        
        Db::table('im_friends')
            ->where(function($q) use ($uid, $friendUid) {
                $q->where('uid', $uid)->where('friend_uid', $friendUid);
            })
            ->orWhere(function($q) use ($uid, $friendUid) {
                $q->where('uid', $friendUid)->where('friend_uid', $uid);
            })
            ->delete();
        return true;
    }
    
    
    
    
    public static function getGroups(int $uid): array
    {
        $groups = Db::table('im_group_members as gm')
            ->leftJoin('im_groups as g', 'gm.group_id', '=', 'g.id')
            ->where('gm.uid', $uid)
            ->select('g.*', 'gm.role', 'gm.nickname as my_nickname')
            ->get();
        
        $result = [];
        foreach ($groups as $g) {
            $result[] = [
                'id' => $g->id,
                'name' => $g->name,
                'avatar' => $g->avatar ?? '',
                'memberCount' => $g->member_count,
                'role' => $g->role,  
            ];
        }
        return $result;
    }
    
    
    public static function getGroupMembers(int $groupId): array
    {
        $members = Db::table('im_group_members as gm')
            ->leftJoin('caipiao_member as m', 'gm.uid', '=', 'm.id')
            ->where('gm.group_id', $groupId)
            ->select('gm.*', 'm.nickname', 'm.username', 'm.face')
            ->orderBy('gm.role', 'desc')
            ->get();
        
        $result = [];
        foreach ($members as $m) {
            $result[] = [
                'userId' => $m->uid,
                'nickname' => $m->nickname ?: $m->username,
                'groupNickname' => $m->nickname,
                'avatar' => $m->face ?? '',
                'role' => $m->role,
                'isMute' => $m->is_mute,
                'joinedAt' => $m->joined_at,
            ];
        }
        return $result;
    }
    
    
    public static function inviteToGroup(int $operatorUid, int $groupId, array $uids): array
    {
        
        $operator = Db::table('im_group_members')
            ->where('group_id', $groupId)
            ->where('uid', $operatorUid)
            ->first();
        
        if (!$operator) {
            return ['success' => false, 'error' => '非群成员'];
        }
        
        $now = time();
        $added = 0;
        foreach ($uids as $uid) {
            $exists = Db::table('im_group_members')
                ->where('group_id', $groupId)
                ->where('uid', $uid)
                ->exists();
            
            if (!$exists) {
                Db::table('im_group_members')->insert([
                    'group_id' => $groupId,
                    'uid' => $uid,
                    'role' => 0,
                    'joined_at' => $now,
                ]);
                $added++;
            }
        }
        
        
        Db::table('im_groups')
            ->where('id', $groupId)
            ->increment('member_count', $added);
        
        return ['success' => true, 'added' => $added];
    }
    
    
    public static function kickFromGroup(int $operatorUid, int $groupId, int $targetUid): array
    {
        
        $operator = Db::table('im_group_members')
            ->where('group_id', $groupId)
            ->where('uid', $operatorUid)
            ->first();
        
        if (!$operator || $operator->role < 1) {
            return ['success' => false, 'error' => '权限不足'];
        }
        
        $target = Db::table('im_group_members')
            ->where('group_id', $groupId)
            ->where('uid', $targetUid)
            ->first();
        
        if (!$target) {
            return ['success' => false, 'error' => '用户不在群中'];
        }
        
        if ($target->role >= $operator->role) {
            return ['success' => false, 'error' => '无法踢出同级或更高权限成员'];
        }
        
        Db::table('im_group_members')
            ->where('group_id', $groupId)
            ->where('uid', $targetUid)
            ->delete();
        
        Db::table('im_groups')->where('id', $groupId)->decrement('member_count');
        
        
        $group = Db::table('im_groups')->where('id', $groupId)->first();
        self::pushGroupNotice($targetUid, 'kicked', [
            'groupId' => $groupId,
            'groupName' => $group->name ?? '',
        ]);
        
        return ['success' => true];
    }
    
    
    public static function setGroupAdmin(int $operatorUid, int $groupId, int $targetUid, bool $isAdmin): array
    {
        
        $operator = Db::table('im_group_members')
            ->where('group_id', $groupId)
            ->where('uid', $operatorUid)
            ->first();
        
        if (!$operator || $operator->role != 2) {
            return ['success' => false, 'error' => '只有群主可以设置管理员'];
        }
        
        Db::table('im_group_members')
            ->where('group_id', $groupId)
            ->where('uid', $targetUid)
            ->update(['role' => $isAdmin ? 1 : 0]);
        
        return ['success' => true];
    }
    
    
    public static function quitGroup(int $uid, int $groupId): array
    {
        $member = Db::table('im_group_members')
            ->where('group_id', $groupId)
            ->where('uid', $uid)
            ->first();
        
        if (!$member) {
            return ['success' => false, 'error' => '不在群中'];
        }
        
        if ($member->role == 2) {
            return ['success' => false, 'error' => '群主不能退出，请先转让群'];
        }
        
        Db::table('im_group_members')
            ->where('group_id', $groupId)
            ->where('uid', $uid)
            ->delete();
        
        Db::table('im_groups')->where('id', $groupId)->decrement('member_count');
        
        return ['success' => true];
    }
    
    
    
    
    public static function toggleConversationTop(int $uid, int $targetType, int $targetId): bool
    {
        $conv = Db::table('im_conversations')
            ->where('uid', $uid)
            ->where('target_type', $targetType)
            ->where('target_id', $targetId)
            ->first();
        
        if ($conv) {
            Db::table('im_conversations')
                ->where('id', $conv->id)
                ->update(['is_top' => $conv->is_top ? 0 : 1]);
        }
        return true;
    }
    
    
    public static function deleteConversation(int $uid, int $targetType, int $targetId): bool
    {
        Db::table('im_conversations')
            ->where('uid', $uid)
            ->where('target_type', $targetType)
            ->where('target_id', $targetId)
            ->delete();
        return true;
    }
    
    
    public static function toggleConversationMute(int $uid, int $targetType, int $targetId): bool
    {
        $conv = Db::table('im_conversations')
            ->where('uid', $uid)
            ->where('target_type', $targetType)
            ->where('target_id', $targetId)
            ->first();
        
        if ($conv) {
            Db::table('im_conversations')
                ->where('id', $conv->id)
                ->update(['is_mute' => $conv->is_mute ? 0 : 1]);
        }
        return true;
    }
    
    
    public static function setConversationTop(int $uid, int $targetType, int $targetId, bool $isTop): bool
    {
        Db::table('im_conversations')
            ->where('uid', $uid)
            ->where('target_type', $targetType)
            ->where('target_id', $targetId)
            ->update(['is_top' => $isTop ? 1 : 0]);
        return true;
    }
    
    
    public static function setConversationMute(int $uid, int $targetType, int $targetId, bool $isMuted): bool
    {
        Db::table('im_conversations')
            ->where('uid', $uid)
            ->where('target_type', $targetType)
            ->where('target_id', $targetId)
            ->update(['is_mute' => $isMuted ? 1 : 0]);
        return true;
    }
    
    
    public static function searchUser(int $uid, string $keyword, int $limit = 20): array
    {
        
        $users = Db::table('caipiao_member')
            ->where('id', '!=', $uid)
            ->where(function($query) use ($keyword) {
                $query->where('id', $keyword)
                    ->orWhere('username', 'like', "%{$keyword}%")
                    ->orWhere('nickname', 'like', "%{$keyword}%");
            })
            ->limit($limit)
            ->get(['id', 'username', 'nickname', 'face']);
        
        $result = [];
        foreach ($users as $user) {
            
            $isFriend = Db::table('im_friends')
                ->where('uid', $uid)
                ->where('friend_uid', $user->id)
                ->exists();
            
            $result[] = [
                'userId' => $user->id,
                'username' => $user->username,
                'nickname' => $user->nickname ?: $user->username,
                'avatar' => $user->face ?? '',
                'isFriend' => $isFriend,
            ];
        }
        
        return $result;
    }
}
