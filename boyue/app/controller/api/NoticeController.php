<?php

namespace app\controller\api;

use support\Request;
use support\Db;

class NoticeController
{
    
    public function list(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        try {
            $page = $request->get('page', 1);
            $pageSize = $request->get('pageSize', 20);
            $type = $request->get('type', 'all');
            $unreadOnly = $request->get('unreadOnly', false);
            
            
            if ($unreadOnly && $userId) {
                
                $query = Db::table('caipiao_gonggao')
                    ->leftJoin('caipiao_notice_read', function($join) use ($userId) {
                        $join->on('caipiao_gonggao.id', '=', 'caipiao_notice_read.notice_id')
                             ->where('caipiao_notice_read.uid', '=', $userId);
                    })
                    ->whereNull('caipiao_notice_read.id');
            } else {
                $query = Db::table('caipiao_gonggao');
            }
            
            
            if ($type && $type !== 'all') {
                $query->where($unreadOnly && $userId ? 'caipiao_gonggao.type' : 'type', $type);
            }
            
            
            $countQuery = clone $query;
            $total = $countQuery->count($unreadOnly && $userId ? 'caipiao_gonggao.id' : '*');
            
            
            if ($unreadOnly && $userId) {
                $notices = $query->select('caipiao_gonggao.*')
                    ->orderBy('caipiao_gonggao.id', 'desc')
                    ->offset(($page - 1) * $pageSize)
                    ->limit($pageSize)
                    ->get();
            } else {
                $notices = $query->orderBy('id', 'desc')
                    ->offset(($page - 1) * $pageSize)
                    ->limit($pageSize)
                    ->get();
            }
            
            
            $readNoticeIds = [];
            $unreadCount = 0;
            
            if ($userId) {
                if (!$unreadOnly) {
                    $readNoticeIds = Db::table('caipiao_notice_read')
                        ->where('uid', $userId)
                        ->pluck('notice_id')
                        ->toArray();
                }
                
                
                $totalNotices = Db::table('caipiao_gonggao')->count();
                $readCount = Db::table('caipiao_notice_read')->where('uid', $userId)->count();
                $unreadCount = $totalNotices - $readCount;
            }
            
            $data = [];
            foreach ($notices as $notice) {
                
                $isRead = $unreadOnly ? false : in_array($notice->id, $readNoticeIds);
                
                $data[] = [
                    'id' => $notice->id,
                    'title' => $notice->title ?? '',
                    'summary' => $this->getSummary($notice->content ?? ''),
                    'content' => $notice->content ?? '',
                    'type' => $notice->type ?? 'system',
                    'isRead' => $isRead,
                    'createdAt' => isset($notice->oddtime) ? $notice->oddtime * 1000 : time() * 1000,
                    'updatedAt' => isset($notice->oddtime) ? $notice->oddtime * 1000 : time() * 1000
                ];
            }
            
            return json([
                'code' => 0,
                'message' => '获取成功',
                'data' => [
                    'total' => $total,
                    'page' => (int)$page,
                    'pageSize' => (int)$pageSize,
                    'unreadCount' => $unreadCount,
                    'list' => $data
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('获取公告列表失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => '获取数据失败：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    
    public function detail(Request $request, $id)
    {
        $userId = $request->userId ?? 0;
        
        try {
            if (empty($id)) {
                return json([
                    'code' => 400,
                    'message' => '参数错误',
                    'data' => null
                ]);
            }
            
            $notice = Db::table('caipiao_gonggao')
                ->where('id', $id)
                ->first();
            
            if (!$notice) {
                return json([
                    'code' => 404,
                    'message' => '公告不存在',
                    'data' => null
                ]);
            }
            
            
            $isRead = false;
            if ($userId) {
                $readRecord = Db::table('caipiao_notice_read')
                    ->where('uid', $userId)
                    ->where('notice_id', $notice->id)
                    ->first();
                $isRead = !empty($readRecord);
                
                
                if (!$isRead) {
                    Db::table('caipiao_notice_read')->insert([
                        'uid' => $userId,
                        'notice_id' => $notice->id,
                        'read_time' => time()
                    ]);
                }
            }
            
            
            $prevNotice = Db::table('caipiao_gonggao')
                ->where('id', '<', $id)
                ->orderBy('id', 'desc')
                ->first();
            
            $nextNotice = Db::table('caipiao_gonggao')
                ->where('id', '>', $id)
                ->orderBy('id', 'asc')
                ->first();
            
            return json([
                'code' => 0,
                'message' => '获取成功',
                'data' => [
                    'id' => $notice->id,
                    'title' => $notice->title ?? '',
                    'summary' => $this->getSummary($notice->content ?? ''),
                    'content' => $notice->content ?? '',
                    'type' => $notice->type ?? 'system',
                    'isRead' => true,  
                    'createdAt' => isset($notice->oddtime) ? $notice->oddtime * 1000 : time() * 1000,
                    'updatedAt' => isset($notice->oddtime) ? $notice->oddtime * 1000 : time() * 1000,
                    'prevId' => $prevNotice ? $prevNotice->id : null,
                    'nextId' => $nextNotice ? $nextNotice->id : null
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('获取公告详情失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => '获取数据失败：' . $e->getMessage(),
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
                'message' => '未登录或登录已过期',
                'data' => null
            ]);
        }
        
        try {
            $id = $request->post('id');
            $ids = $request->post('ids');
            
            
            if ($id) {
                $ids = [$id];
            }
            
            
            if (empty($ids) || !is_array($ids)) {
                return json([
                    'code' => 400,
                    'message' => '参数错误',
                    'data' => null
                ]);
            }
            
            
            foreach ($ids as $noticeId) {
                
                $exists = Db::table('caipiao_notice_read')
                    ->where('uid', $userId)
                    ->where('notice_id', $noticeId)
                    ->first();
                
                if (!$exists) {
                    Db::table('caipiao_notice_read')->insert([
                        'uid' => $userId,
                        'notice_id' => $noticeId,
                        'read_time' => time()
                    ]);
                }
            }
            
            return json([
                'code' => 0,
                'message' => '标记成功',
                'data' => null
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('标记已读失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => '操作失败：' . $e->getMessage(),
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
                'message' => '获取成功',
                'data' => [
                    'unreadCount' => 0
                ]
            ]);
        }
        
        try {
            
            $totalNotices = Db::table('caipiao_gonggao')->count();
            $readCount = Db::table('caipiao_notice_read')
                ->where('uid', $userId)
                ->count();
            $unreadCount = $totalNotices - $readCount;
            
            return json([
                'code' => 0,
                'message' => '获取成功',
                'data' => [
                    'unreadCount' => $unreadCount
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('获取未读数量失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => '获取数据失败：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    
    private function getSummary($content, $length = 100)
    {
        
        $text = strip_tags($content);
        
        if (mb_strlen($text, 'UTF-8') > $length) {
            $text = mb_substr($text, 0, $length, 'UTF-8') . '...';
        }
        return $text;
    }
}
