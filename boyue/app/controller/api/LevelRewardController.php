<?php

declare(strict_types=1);

namespace app\controller\api;

use support\Request;
use support\Response;
use app\service\LevelRewardService;
use app\exception\LevelRewardException;


class LevelRewardController
{
    
    private LevelRewardService $service;

    public function __construct()
    {
        $this->service = new LevelRewardService();
    }

    
    public function getRewardInfo(Request $request): Response
    {
        $uid = $this->getUid($request);
        if (!$uid) {
            return $this->jsonResponse(LevelRewardException::notLoggedIn()->toResult()->toArray());
        }

        $result = $this->service->getRewardInfo($uid);
        return $this->jsonResponse($result);
    }

    
    public function claimReward(Request $request): Response
    {
        $uid = $this->getUid($request);
        if (!$uid) {
            return $this->jsonResponse(LevelRewardException::notLoggedIn()->toResult()->toArray());
        }

        $result = $this->service->claimReward($uid);
        return $this->jsonResponse($result);
    }

    
    public function getRecords(Request $request): Response
    {
        $uid = $this->getUid($request);
        if (!$uid) {
            return $this->jsonResponse(LevelRewardException::notLoggedIn()->toResult()->toArray());
        }

        $params = [
            'page'      => $request->get('pageIndex', 1),
            'page_size' => $request->get('pageSize', 20),
        ];

        $result = $this->service->getRecordList($uid, $params);
        return $this->jsonResponse($result);
    }

    
    public function getLevelConfigs(Request $request): Response
    {
        $result = $this->service->getLevelConfigs();
        return $this->jsonResponse($result);
    }

    

    
    private function getUid(Request $request): ?int
    {
        
        $uid = $request->userId ?? $request->uid ?? session('uid');
        return $uid ? (int)$uid : null;
    }

    
    private function jsonResponse(array $data): Response
    {
        return new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode($data, JSON_UNESCAPED_UNICODE)
        );
    }
}
