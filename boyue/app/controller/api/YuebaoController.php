<?php

declare(strict_types=1);

namespace app\controller\api;

use support\Request;
use support\Response;
use app\service\YuebaoService;
use app\exception\YuebaoException;

class YuebaoController
{
    
    private YuebaoService $yuebaoService;

    
    public function __construct()
    {
        $this->yuebaoService = new YuebaoService();
    }

    
    public function info(Request $request): Response
    {
        $userId = (int)($request->userId ?? 0);

        $result = $this->yuebaoService->getInfo($userId);

        return json($result);
    }

    
    public function products(Request $request): Response
    {
        $result = $this->yuebaoService->getProducts();

        return json($result);
    }

    
    public function transferIn(Request $request): Response
    {
        $userId = (int)($request->userId ?? 0);
        $data = $request->post();

        $result = $this->yuebaoService->transferIn($userId, $data);

        return json($result);
    }

    
    public function transferOut(Request $request): Response
    {
        $userId = (int)($request->userId ?? 0);
        $data = $request->post();

        $result = $this->yuebaoService->transferOut($userId, $data);

        return json($result);
    }

    
    public function records(Request $request): Response
    {
        $userId = (int)($request->userId ?? 0);
        $params = [
            'page'         => $request->get('page', 1),
            'page_size'    => $request->get('page_size', $request->get('pageSize', 20)),
            'type'         => $request->get('type', 'all'),
            'product_type' => $request->get('product_type', ''),
            'date_range'   => $request->get('date_range', $request->get('dateRange', '')),
        ];

        $result = $this->yuebaoService->getRecords($userId, $params);

        return json($result);
    }

    
    public function analysis(Request $request): Response
    {
        $userId = (int)($request->userId ?? 0);
        $days = $request->get('days', 7);

        $result = $this->yuebaoService->getAnalysis($userId, $days);

        return json($result);
    }

    
    public function export(Request $request): Response
    {
        $userId = (int)($request->userId ?? 0);
        $data = $request->post();

        $result = $this->yuebaoService->exportBill($userId, $data);

        return json($result);
    }

    
    public function holdings(Request $request): Response
    {
        $userId = (int)($request->userId ?? 0);
        $params = [
            'status' => $request->get('status', 'active'),
        ];

        $result = $this->yuebaoService->getHoldings($userId, $params);

        return json($result);
    }

    
    public function config(Request $request): Response
    {
        $result = $this->yuebaoService->getConfig();

        return json($result);
    }

    
    public function claim(Request $request): Response
    {
        $userId = (int)($request->userId ?? 0);

        $result = $this->yuebaoService->claimInterest($userId);

        return json($result);
    }
}
