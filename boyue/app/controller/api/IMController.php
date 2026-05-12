<?php

namespace app\controller\api;

use support\Request;
use support\Response;
use support\Log;
use app\service\IMService;

class IMController
{
    
    private function getPostData(Request $request): array
    {
        $data = $request->post();
        if (empty($data)) {
            $data = json_decode($request->rawBody(), true) ?: [];
        }
        return $data;
    }
    
    
    public function conversations(Request $request): Response
    {
        $userId = $request->userId ?? 0;
        if (!$userId) {
            return json(['code' => 2001, 'message' => '未登录']);
        }
        
        try {
            $list = IMService::getConversations($userId);
            $unreadTotal = IMService::getUnreadCount($userId);
            
            return json([
                'code' => 0,
                'data' => [
                    'list' => $list,
                    'unreadTotal' => $unreadTotal
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('[IM] 获取会话失败: ' . $e->getMessage());
            return json(['code' => 9999, 'message' => '系统错误']);
        }
    }
    
    
    public function messages(Request $request): Response
    {
        $userId = $request->userId ?? 0;
        if (!$userId) {
            return json(['code' => 2001, 'message' => '未登录']);
        }
        
        $targetType = (int)$request->get('targetType', 1);
        $targetId = (int)$request->get('targetId', 0);
        $lastMsgId = (int)$request->get('lastMsgId', 0);
        $limit = min(50, max(10, (int)$request->get('limit', 20)));
        
        if (!$targetId) {
            return json(['code' => 1001, 'message' => '参数错误']);
        }
        
        try {
            $messages = IMService::getMessages($userId, $targetType, $targetId, $lastMsgId, $limit);
            
            
            IMService::markAsRead($userId, $targetType, $targetId);
            
            return json([
                'code' => 0,
                'data' => $messages
            ]);
        } catch (\Exception $e) {
            Log::error('[IM] 获取消息失败: ' . $e->getMessage());
            return json(['code' => 9999, 'message' => '系统错误']);
        }
    }
    
    
    public function send(Request $request): Response
    {
        $userId = $request->userId ?? 0;
        if (!$userId) {
            return json(['code' => 2001, 'message' => '未登录']);
        }
        
        $data = $this->getPostData($request);
        $targetType = (int)($data['targetType'] ?? 1);
        $targetId = (int)($data['targetId'] ?? 0);
        $content = trim($data['content'] ?? '');
        $msgType = (int)($data['msgType'] ?? 1);
        
        if (!$targetId || empty($content)) {
            return json(['code' => 1001, 'message' => '参数错误']);
        }
        
        if (mb_strlen($content) > 2000) {
            return json(['code' => 1001, 'message' => '消息过长']);
        }
        
        try {
            if ($targetType == 1) {
                $result = IMService::sendPrivateMessage($userId, $targetId, $content, $msgType);
            } else {
                $result = IMService::sendGroupMessage($userId, $targetId, $content, $msgType);
            }
            
            if ($result['success']) {
                return json(['code' => 0, 'data' => $result['data']]);
            } else {
                return json(['code' => 1002, 'message' => $result['error']]);
            }
        } catch (\Exception $e) {
            Log::error('[IM] 发送失败: ' . $e->getMessage());
            return json(['code' => 9999, 'message' => '系统错误']);
        }
    }
    
    
    public function unread(Request $request): Response
    {
        $userId = $request->userId ?? 0;
        if (!$userId) {
            return json(['code' => 2001, 'message' => '未登录']);
        }
        
        $count = IMService::getUnreadCount($userId);
        
        return json(['code' => 0, 'data' => ['count' => $count]]);
    }
    
    
    public function read(Request $request): Response
    {
        $userId = $request->userId ?? 0;
        if (!$userId) {
            return json(['code' => 2001, 'message' => '未登录']);
        }
        
        $data = $this->getPostData($request);
        $targetType = (int)($data['targetType'] ?? 1);
        $targetId = (int)($data['targetId'] ?? 0);
        
        if ($targetId) {
            IMService::markAsRead($userId, $targetType, $targetId);
        }
        
        return json(['code' => 0, 'message' => 'ok']);
    }
    
    
    public function user(Request $request): Response
    {
        $userId = $request->userId ?? 0;
        if (!$userId) {
            return json(['code' => 2001, 'message' => '未登录']);
        }
        
        $targetUid = (int)$request->get('userId', 0);
        if (!$targetUid) {
            return json(['code' => 1001, 'message' => '参数错误']);
        }
        
        $user = \support\Db::table('caipiao_member')
            ->where('id', $targetUid)
            ->first();
        
        if (!$user) {
            return json(['code' => 1002, 'message' => '用户不存在']);
        }
        
        return json([
            'code' => 0,
            'data' => [
                'userId' => $user->id,
                'nickname' => $user->nickname ?: $user->username,
                'avatar' => $user->face ?? '',
            ]
        ]);
    }
    
    
    public function customerService(Request $request): Response
    {
        $userId = $request->userId ?? 0;
        if (!$userId) {
            return json(['code' => 2001, 'message' => '未登录']);
        }
        
        
        $serviceUser = \support\Db::table('caipiao_member')
            ->where('username', 'customer_service')
            ->first();
        
        if (!$serviceUser) {
            
            $serviceUser = \support\Db::table('caipiao_member')
                ->where('groupid', 1)  
                ->first();
        }
        
        if (!$serviceUser) {
            return json(['code' => 1002, 'message' => '暂无在线客服']);
        }
        
        return json([
            'code' => 0,
            'data' => [
                'userId' => $serviceUser->id,
                'nickname' => $serviceUser->nickname ?: '在线客服',
                'avatar' => $serviceUser->face ?? '',
            ]
        ]);
    }
    
    
    
    
    public function searchUser(Request $request): Response
    {
        $userId = $request->userId ?? 0;
        if (!$userId) {
            return json(['code' => 2001, 'message' => '未登录']);
        }
        
        $keyword = trim($request->get('keyword', ''));
        if (empty($keyword)) {
            return json(['code' => 0, 'data' => []]);
        }
        
        $list = IMService::searchUser($userId, $keyword);
        return json(['code' => 0, 'data' => $list]);
    }
    
    
    public function contacts(Request $request): Response
    {
        $userId = $request->userId ?? 0;
        if (!$userId) {
            return json(['code' => 2001, 'message' => '未登录']);
        }
        
        $list = IMService::getContacts($userId);
        return json(['code' => 0, 'data' => $list]);
    }
    
    
    public function friendRequests(Request $request): Response
    {
        $userId = $request->userId ?? 0;
        if (!$userId) {
            return json(['code' => 2001, 'message' => '未登录']);
        }
        
        $list = IMService::getFriendRequests($userId);
        return json(['code' => 0, 'data' => $list]);
    }
    
    
    public function sendFriendRequest(Request $request): Response
    {
        $userId = $request->userId ?? 0;
        if (!$userId) {
            return json(['code' => 2001, 'message' => '未登录']);
        }
        
        $data = $this->getPostData($request);
        $toUid = (int)($data['userId'] ?? 0);
        $remark = trim($data['remark'] ?? '');
        
        if (!$toUid) {
            return json(['code' => 1001, 'message' => '参数错误']);
        }
        
        $result = IMService::sendFriendRequest($userId, $toUid, $remark);
        
        if ($result['success']) {
            return json(['code' => 0, 'message' => '请求已发送']);
        }
        return json(['code' => 1002, 'message' => $result['error']]);
    }
    
    
    public function handleFriendRequest(Request $request): Response
    {
        $userId = $request->userId ?? 0;
        if (!$userId) {
            return json(['code' => 2001, 'message' => '未登录']);
        }
        
        $data = $this->getPostData($request);
        $requestId = (int)($data['requestId'] ?? 0);
        
        
        $action = $data['action'] ?? $data['accept'] ?? null;
        if (is_string($action)) {
            $accept = in_array(strtolower(trim($action)), ['accept', 'true', '1', 'yes']);
        } else {
            $accept = (bool)$action;
        }
        
        $result = IMService::handleFriendRequest($userId, $requestId, $accept);
        
        if ($result['success']) {
            return json(['code' => 0, 'message' => 'ok']);
        }
        return json(['code' => 1002, 'message' => $result['error']]);
    }
    
    
    public function setFriendRemark(Request $request): Response
    {
        $userId = $request->userId ?? 0;
        if (!$userId) {
            return json(['code' => 2001, 'message' => '未登录']);
        }
        
        $data = $this->getPostData($request);
        $friendUid = (int)($data['userId'] ?? 0);
        $remark = trim($data['remark'] ?? '');
        
        IMService::setFriendRemark($userId, $friendUid, $remark);
        return json(['code' => 0, 'message' => 'ok']);
    }
    
    
    public function blockFriend(Request $request): Response
    {
        $userId = $request->userId ?? 0;
        if (!$userId) {
            return json(['code' => 2001, 'message' => '未登录']);
        }
        
        $data = $this->getPostData($request);
        $friendUid = (int)($data['userId'] ?? 0);
        $block = (bool)($data['block'] ?? true);
        
        IMService::blockFriend($userId, $friendUid, $block);
        return json(['code' => 0, 'message' => 'ok']);
    }
    
    
    public function deleteFriend(Request $request): Response
    {
        $userId = $request->userId ?? 0;
        if (!$userId) {
            return json(['code' => 2001, 'message' => '未登录']);
        }
        
        $data = $this->getPostData($request);
        $friendUid = (int)($data['userId'] ?? 0);
        
        IMService::deleteFriend($userId, $friendUid);
        return json(['code' => 0, 'message' => 'ok']);
    }
    
    
    
    
    public function groups(Request $request): Response
    {
        $userId = $request->userId ?? 0;
        if (!$userId) {
            return json(['code' => 2001, 'message' => '未登录']);
        }
        
        $list = IMService::getGroups($userId);
        return json(['code' => 0, 'data' => $list]);
    }
    
    
    public function createGroup(Request $request): Response
    {
        $userId = $request->userId ?? 0;
        if (!$userId) {
            return json(['code' => 2001, 'message' => '未登录']);
        }
        
        $data = $this->getPostData($request);
        $name = trim($data['name'] ?? '');
        $memberUids = $data['members'] ?? [];
        
        if (empty($name)) {
            return json(['code' => 1001, 'message' => '请输入群名称']);
        }
        
        $result = IMService::createGroup($userId, $name, $memberUids);
        
        if ($result['success']) {
            return json(['code' => 0, 'data' => ['groupId' => $result['groupId']]]);
        }
        return json(['code' => 1002, 'message' => '创建失败']);
    }
    
    
    public function groupMembers(Request $request, int $id): Response
    {
        $userId = $request->userId ?? 0;
        if (!$userId) {
            return json(['code' => 2001, 'message' => '未登录']);
        }
        
        $members = IMService::getGroupMembers($id);
        return json(['code' => 0, 'data' => $members]);
    }
    
    
    public function inviteMembers(Request $request, int $id): Response
    {
        $userId = $request->userId ?? 0;
        if (!$userId) {
            return json(['code' => 2001, 'message' => '未登录']);
        }
        
        $data = $this->getPostData($request);
        $uids = $data['userIds'] ?? $data['uids'] ?? [];
        
        $result = IMService::inviteToGroup($userId, $id, $uids);
        
        if ($result['success']) {
            return json(['code' => 0, 'data' => ['added' => $result['added']]]);
        }
        return json(['code' => 1002, 'message' => $result['error']]);
    }
    
    
    public function kickMember(Request $request, int $id): Response
    {
        $userId = $request->userId ?? 0;
        if (!$userId) {
            return json(['code' => 2001, 'message' => '未登录']);
        }
        
        $data = $this->getPostData($request);
        $targetUid = (int)($data['userId'] ?? 0);
        
        $result = IMService::kickFromGroup($userId, $id, $targetUid);
        
        if ($result['success']) {
            return json(['code' => 0, 'message' => 'ok']);
        }
        return json(['code' => 1002, 'message' => $result['error']]);
    }
    
    
    public function setAdmin(Request $request, int $id): Response
    {
        $userId = $request->userId ?? 0;
        if (!$userId) {
            return json(['code' => 2001, 'message' => '未登录']);
        }
        
        $data = $this->getPostData($request);
        $targetUid = (int)($data['userId'] ?? 0);
        $isAdmin = (bool)($data['isAdmin'] ?? true);
        
        $result = IMService::setGroupAdmin($userId, $id, $targetUid, $isAdmin);
        
        if ($result['success']) {
            return json(['code' => 0, 'message' => 'ok']);
        }
        return json(['code' => 1002, 'message' => $result['error']]);
    }
    
    
    public function quitGroup(Request $request, int $id): Response
    {
        $userId = $request->userId ?? 0;
        if (!$userId) {
            return json(['code' => 2001, 'message' => '未登录']);
        }
        
        $result = IMService::quitGroup($userId, $id);
        
        if ($result['success']) {
            return json(['code' => 0, 'message' => 'ok']);
        }
        return json(['code' => 1002, 'message' => $result['error']]);
    }
    
    
    
    
    public function toggleTop(Request $request): Response
    {
        $userId = $request->userId ?? 0;
        if (!$userId) {
            return json(['code' => 2001, 'message' => '未登录']);
        }
        
        $data = $this->getPostData($request);
        $targetType = (int)($data['targetType'] ?? 1);
        $targetId = (int)($data['targetId'] ?? 0);
        $isTop = (bool)($data['isTop'] ?? false);
        
        IMService::setConversationTop($userId, $targetType, $targetId, $isTop);
        return json(['code' => 0, 'message' => 'ok']);
    }
    
    
    public function deleteConversation(Request $request): Response
    {
        $userId = $request->userId ?? 0;
        if (!$userId) {
            return json(['code' => 2001, 'message' => '未登录']);
        }
        
        $data = $this->getPostData($request);
        $targetType = (int)($data['targetType'] ?? 1);
        $targetId = (int)($data['targetId'] ?? 0);
        
        IMService::deleteConversation($userId, $targetType, $targetId);
        return json(['code' => 0, 'message' => 'ok']);
    }
    
    
    public function toggleMute(Request $request): Response
    {
        $userId = $request->userId ?? 0;
        if (!$userId) {
            return json(['code' => 2001, 'message' => '未登录']);
        }
        
        $data = $this->getPostData($request);
        $targetType = (int)($data['targetType'] ?? 1);
        $targetId = (int)($data['targetId'] ?? 0);
        $isMuted = (bool)($data['isMuted'] ?? false);
        
        IMService::setConversationMute($userId, $targetType, $targetId, $isMuted);
        return json(['code' => 0, 'message' => 'ok']);
    }
    
    
    
    
    public function upload(Request $request): Response
    {
        $userId = $request->userId ?? 0;
        if (!$userId) {
            return json(['code' => 2001, 'message' => '未登录']);
        }
        
        $file = $request->file('file');
        if (!$file || !$file->isValid()) {
            return json(['code' => 1001, 'message' => '请选择文件']);
        }
        
        try {
            
            $uploadName = $file->getUploadName();
            $uploadMimeType = $file->getUploadMimeType();
            $ext = $file->getUploadExtension();
            
            
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 
                             'audio/mpeg', 'audio/wav', 'video/mp4',
                             'application/pdf', 'application/msword'];
            
            if (!in_array($uploadMimeType, $allowedTypes)) {
                return json(['code' => 1003, 'message' => '不支持的文件类型']);
            }
            
            
            $filename = date('Ymd') . '/' . uniqid() . '.' . $ext;
            $savePath = public_path() . '/uploads/im/' . $filename;
            
            
            $dir = dirname($savePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            
            $file->move($savePath);
            
            
            $fileSize = filesize($savePath);
            
            
            if ($fileSize > 10 * 1024 * 1024) {
                @unlink($savePath);  
                return json(['code' => 1002, 'message' => '文件过大']);
            }
            
            $url = '/uploads/im/' . $filename;
            
            return json([
                'code' => 0,
                'data' => [
                    'url' => $url,
                    'name' => $uploadName,
                    'size' => $fileSize,
                    'type' => $uploadMimeType,
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('[IM] 文件上传失败: ' . $e->getMessage());
            return json(['code' => 9999, 'message' => '文件上传失败: ' . $e->getMessage()]);
        }
    }
}
