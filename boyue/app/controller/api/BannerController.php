<?php

declare(strict_types=1);

namespace app\controller\api;

use support\Request;
use support\Response;
use app\service\BannerService;
use app\constant\BannerConstant;

class BannerController
{
    
    private BannerService $bannerService;

    
    public function __construct()
    {
        $this->bannerService = new BannerService();
    }

    
    public function list(Request $request): Response
    {
        $platform = (int)$request->get('platform', BannerConstant::PLATFORM_ALL);
        $limit    = (int)$request->get('limit', BannerConstant::DEFAULT_API_LIMIT);

        $result = $this->bannerService->getApiList($platform, $limit);

        return json($result);
    }

    
    public function detail(Request $request): Response
    {
        $id = (int)$request->get('id', 0);

        $result = $this->bannerService->getApiDetail($id);

        return json($result);
    }

    
    public function pc(Request $request): Response
    {
        $limit = (int)$request->get('limit', BannerConstant::DEFAULT_API_LIMIT);

        $result = $this->bannerService->getApiList(BannerConstant::PLATFORM_PC, $limit);

        return json($result);
    }

    
    public function mobile(Request $request): Response
    {
        $limit = (int)$request->get('limit', BannerConstant::DEFAULT_API_LIMIT);

        $result = $this->bannerService->getApiList(BannerConstant::PLATFORM_MOBILE, $limit);

        return json($result);
    }
}

