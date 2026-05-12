<?php

namespace app\controller\api;

use support\Request;
use support\Db;
use support\Log;
use app\service\HemaiService;

class HemaiController
{
    
    private $hemaiService;
    
    public function __construct()
    {
        $this->hemaiService = new HemaiService();
    }
    
    
    public function list(Request $request)
    {
        try {
            $params = [
                'status' => $request->get('status', 'all'),
                'lottery' => $request->get('lottery', 'all'),
                'page' => $request->get('page', 1),
                'pageSize' => $request->get('pageSize', 10),
                'uid' => $request->userId ?? 0,
            ];
            
            $result = $this->hemaiService->getList($params);
            
            return json([
                'code' => 0,
                'message' => '获取成功',
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            Log::error('获取合买列表失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => '获取数据失败：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    
    public function detail(Request $request, $id)
    {
        try {
            $detail = $this->hemaiService->getDetail($id);
            
            if (!$detail) {
                return json([
                    'code' => 404,
                    'message' => '合买方案不存在',
                    'data' => null
                ]);
            }
            
            return json([
                'code' => 0,
                'message' => '获取成功',
                'data' => $detail
            ]);
            
        } catch (\Exception $e) {
            Log::error('获取合买详情失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => '获取数据失败：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    
    public function users(Request $request, $id)
    {
        try {
            $result = $this->hemaiService->getUsers($id);
            
            return json([
                'code' => 0,
                'message' => '获取成功',
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            Log::error('获取参与用户列表失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => '获取数据失败：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    
    public function buy(Request $request)
    {
        $userId = $request->userId ?? 0;
        $username = $request->username ?? '';
        
        if (!$userId) {
            return json([
                'code' => 401,
                'message' => '请先登录',
                'data' => null
            ]);
        }
        
        try {
            $post = $request->post();
            $id = $post['id'] ?? 0;
            $num = intval($post['num'] ?? 0);
            
            if (!$id) {
                return json([
                    'code' => 400,
                    'message' => '缺少必填参数：id',
                    'data' => null
                ]);
            }
            
            if ($num <= 0) {
                return json([
                    'code' => 400,
                    'message' => '认购份数必须大于0',
                    'data' => null
                ]);
            }
            
            
            $ip = $request->getRealIp();
            
            $result = $this->hemaiService->buy($id, $userId, $username, $num, $ip);
            
            return json([
                'code' => 0,
                'message' => '认购成功',
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            Log::error('认购合买失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    
    public function lotteryList(Request $request)
    {
        try {
            
            $list = Db::table('caipiao_caipiao')
                ->orderBy('listorder', 'asc')
                ->select('name', 'title', 'typeid')
                ->get();
            
            $data = [];
            foreach ($list as $item) {
                $data[] = [
                    'name' => $item->name,
                    'title' => $item->title,
                    'typeid' => $item->typeid,
                ];
            }
            
            return json([
                'code' => 0,
                'message' => '获取成功',
                'data' => [
                    'list' => $data
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('获取彩种列表失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => '获取数据失败：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    
    public function create(Request $request)
    {
        $userId = $request->userId ?? 0;
        $username = $request->username ?? '';
        
        if (!$userId) {
            return json([
                'code' => 401,
                'message' => '请先登录',
                'data' => null
            ]);
        }
        
        try {
            $post = $request->post();
            
            
            $required = ['lottery', 'expect', 'playtitle', 'totalAmount', 'perShare', 'totalShares', 'tzcode'];
            foreach ($required as $field) {
                if (!isset($post[$field]) || $post[$field] === '') {
                    return json([
                        'code' => 400,
                        'message' => "缺少必填参数：{$field}",
                        'data' => null
                    ]);
                }
            }
            
            
            $data = [
                'uid' => $userId,
                'username' => $username,
                'lottery' => $post['lottery'],
                'expect' => $post['expect'],
                'playid' => $post['playid'] ?? '',
                'playtitle' => $post['playtitle'],
                'totalAmount' => floatval($post['totalAmount']),
                'perShare' => floatval($post['perShare']),
                'totalShares' => intval($post['totalShares']),
                'baodi' => intval($post['baodi'] ?? 0),
                'tzcode' => $post['tzcode'],
                'content' => $post['content'] ?? '',
                'ip' => $request->getRealIp(),
            ];
            
            $hemaiId = $this->hemaiService->create($data);
            
            return json([
                'code' => 0,
                'message' => '发起成功',
                'data' => [
                    'id' => $hemaiId
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('发起合买失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    
    public function myRecords(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json([
                'code' => 401,
                'message' => '请先登录',
                'data' => null
            ]);
        }
        
        try {
            $type = $request->get('type', 'create'); 
            $page = $request->get('page', 1);
            $pageSize = $request->get('pageSize', 10);
            
            $result = $this->hemaiService->getMyRecords($userId, $type, $page, $pageSize);
            
            return json([
                'code' => 0,
                'message' => '获取成功',
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            Log::error('获取我的合买记录失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => '获取数据失败：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    
    public function cancel(Request $request, $id)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json([
                'code' => 401,
                'message' => '请先登录',
                'data' => null
            ]);
        }
        
        try {
            $this->hemaiService->cancel($id, $userId);
            
            return json([
                'code' => 0,
                'message' => '取消成功',
                'data' => null
            ]);
            
        } catch (\Exception $e) {
            Log::error('取消合买失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    
    public function cancelJoin(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json([
                'code' => 401,
                'message' => '请先登录',
                'data' => null
            ]);
        }
        
        try {
            $post = $request->post();
            $id = $post['id'] ?? 0;
            
            if (!$id) {
                return json([
                    'code' => 400,
                    'message' => '缺少必填参数：id',
                    'data' => null
                ]);
            }
            
            $this->hemaiService->cancelJoin($id, $userId);
            
            return json([
                'code' => 0,
                'message' => '撤资成功',
                'data' => null
            ]);
            
        } catch (\Exception $e) {
            Log::error('撤资失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    
    public function share(Request $request, $id)
    {
        try {
            
            $detail = $this->hemaiService->getDetail($id);
            
            if (!$detail) {
                return json([
                    'code' => 404,
                    'message' => '合买方案不存在',
                    'data' => null
                ]);
            }
            
            
            $baseUrl = $request->header('origin', '');
            $shareUrl = $baseUrl . '/lottery/hall/hemai/' . $id;
            
            
            $shareText = "【合买推荐】{$detail['cptitle']} {$detail['expect']}期\n" .
                        "{$detail['playtitle']} 总金额{$detail['amount']}元\n" .
                        "进度{$detail['progress']}% 快来跟单吧！";
            
            return json([
                'code' => 0,
                'message' => '获取成功',
                'data' => [
                    'shareUrl' => $shareUrl,
                    'shareText' => $shareText,
                    'qrCode' => '', 
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('获取分享信息失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => '获取数据失败：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
}
