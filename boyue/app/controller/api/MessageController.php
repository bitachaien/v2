<?php

namespace app\controller\api;

use support\Request;
use support\Db;


class MessageController
{
    
    private function isUserInUsers($users, $userId)
    {
        if (empty($users) || $users === '0') {
            return false; 
        }
        $userIds = array_map('trim', explode(',', $users));
        return in_array((string)$userId, $userIds);
    }
    
    
    private function isMessageRead($noticeId, $userId)
    {
        return Db::table('caipiao_notice_read')
            ->where('notice_id', $noticeId)
            ->where('uid', $userId)
            ->exists();
    }
    
    
    public function list(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json([
                'code' => 401,
                'message' => 'Vui lòng đăng nhập',
                'data' => null
            ]);
        }
        
        try {
            $page = (int)$request->get('page', 1);
            $pageSize = (int)$request->get('pageSize', 20);
            
            
            
            $messages = Db::table('caipiao_notice')
                ->where(function($query) use ($userId) {
                    $query->where('users', $userId)
                          ->orWhereRaw("FIND_IN_SET(?, users)", [$userId]);
                })
                ->orderBy('id', 'desc')
                ->get();
            
            $total = count($messages);
            
            
            $readIds = Db::table('caipiao_notice_read')
                ->where('uid', $userId)
                ->pluck('notice_id')
                ->toArray();
            
            
            $unreadCount = 0;
            foreach ($messages as $msg) {
                if (!in_array($msg->id, $readIds)) {
                    $unreadCount++;
                }
            }
            
            
            $offset = ($page - 1) * $pageSize;
            $pagedMessages = array_slice($messages->toArray(), $offset, $pageSize);
            
            $data = [];
            foreach ($pagedMessages as $msg) {
                $msg = (object)$msg;
                $isRead = in_array($msg->id, $readIds);
                $data[] = [
                    'id' => $msg->id,
                    'title' => $msg->title,
                    'content' => $msg->content,
                    'summary' => mb_substr(strip_tags($msg->content), 0, 50, 'UTF-8') . '...',
                    'senderName' => $msg->admin_user ?: '系统',
                    'isRead' => $isRead,
                    'sentTime' => $msg->add_time * 1000,
                    'createdAt' => $msg->add_time * 1000
                ];
            }
            
            return json([
                'code' => 0,
                'message' => 'Lấy dữ liệu thành công',
                'data' => [
                    'total' => $total,
                    'page' => $page,
                    'pageSize' => $pageSize,
                    'unreadCount' => $unreadCount,
                    'list' => $data
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('Lấy站内信列表Thất bại: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => 'Lấy dữ liệuThất bại: ' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    
    public function detail(Request $request, $id)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json([
                'code' => 401,
                'message' => 'Vui lòng đăng nhập',
                'data' => null
            ]);
        }
        
        try {
            $msg = Db::table('caipiao_notice')
                ->where('id', $id)
                ->first();
            
            if (!$msg) {
                return json([
                    'code' => 404,
                    'message' => 'Tin nhắnkhông tồn tại',
                    'data' => null
                ]);
            }
            
            
            if (!$this->isUserInUsers($msg->users, $userId)) {
                return json([
                    'code' => 403,
                    'message' => '无权查看此Tin nhắn',
                    'data' => null
                ]);
            }
            
            
            if (!$this->isMessageRead($id, $userId)) {
                Db::table('caipiao_notice_read')->insert([
                    'notice_id' => $id,
                    'uid' => $userId,
                    'read_time' => time()
                ]);
            }
            
            return json([
                'code' => 0,
                'message' => 'Lấy dữ liệu thành công',
                'data' => [
                    'id' => $msg->id,
                    'title' => $msg->title,
                    'content' => $msg->content,
                    'senderName' => $msg->admin_user ?: '系统',
                    'isRead' => true,
                    'sentTime' => $msg->add_time * 1000,
                    'createdAt' => $msg->add_time * 1000
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('Lấy站内信Chi tiếtThất bại: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => 'Lấy dữ liệuThất bại',
                'data' => null
            ]);
        }
    }
    
    
    public function markRead(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json([
                'code' => 401,
                'message' => 'Vui lòng đăng nhập',
                'data' => null
            ]);
        }
        
        try {
            $id = $request->post('id');
            $ids = $request->post('ids');
            $all = $request->post('all', false);
            
            if ($all) {
                
                $messages = Db::table('caipiao_notice')
                    ->where(function($query) use ($userId) {
                        $query->where('users', $userId)
                              ->orWhereRaw("FIND_IN_SET(?, users)", [$userId]);
                    })
                    ->pluck('id')
                    ->toArray();
                $ids = $messages;
            } else {
                if ($id) {
                    $ids = [$id];
                }
            }
            
            if (empty($ids)) {
                return json(['code' => 400, 'message' => 'Tham số không hợp lệ', 'data' => null]);
            }
            
            
            foreach ($ids as $noticeId) {
                if (!$this->isMessageRead($noticeId, $userId)) {
                    Db::table('caipiao_notice_read')->insert([
                        'notice_id' => $noticeId,
                        'uid' => $userId,
                        'read_time' => time()
                    ]);
                }
            }
            
            return json([
                'code' => 0,
                'message' => '标记Thành công',
                'data' => null
            ]);
            
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => 'Thao tác thất bại',
                'data' => null
            ]);
        }
    }
    
    
    public function delete(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json([
                'code' => 401,
                'message' => 'Vui lòng đăng nhập',
                'data' => null
            ]);
        }
        
        try {
            $id = $request->post('id');
            $ids = $request->post('ids');
            
            if ($id) {
                $ids = [$id];
            }
            
            if (empty($ids)) {
                return json(['code' => 400, 'message' => 'Tham số không hợp lệ', 'data' => null]);
            }
            
            
            
            foreach ($ids as $noticeId) {
                if (!$this->isMessageRead($noticeId, $userId)) {
                    Db::table('caipiao_notice_read')->insert([
                        'notice_id' => $noticeId,
                        'uid' => $userId,
                        'read_time' => time()
                    ]);
                }
            }
            
            return json([
                'code' => 0,
                'message' => 'XóaThành công',
                'data' => null
            ]);
            
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => 'Thao tác thất bại',
                'data' => null
            ]);
        }
    }
    
    
    public function unreadCount(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json([
                'code' => 0,
                'message' => 'Lấy dữ liệu thành công',
                'data' => ['unreadCount' => 0]
            ]);
        }
        
        try {
            
            $messages = Db::table('caipiao_notice')
                ->where(function($query) use ($userId) {
                    $query->where('users', $userId)
                          ->orWhereRaw("FIND_IN_SET(?, users)", [$userId]);
                })
                ->pluck('id')
                ->toArray();
            
            if (empty($messages)) {
                return json([
                    'code' => 0,
                    'message' => 'Lấy dữ liệu thành công',
                    'data' => ['unreadCount' => 0]
                ]);
            }
            
            
            $readIds = Db::table('caipiao_notice_read')
                ->where('uid', $userId)
                ->whereIn('notice_id', $messages)
                ->pluck('notice_id')
                ->toArray();
            
            $unreadCount = count(array_diff($messages, $readIds));
            
            return json([
                'code' => 0,
                'message' => 'Lấy dữ liệu thành công',
                'data' => ['unreadCount' => $unreadCount]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('Lấy未读站内信数量Thất bại: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => 'Lấy dữ liệuThất bại',
                'data' => null
            ]);
        }
    }
}
