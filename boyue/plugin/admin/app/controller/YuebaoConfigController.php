<?php

namespace plugin\admin\app\controller;

use support\Request;
use support\Response;
use app\service\YuebaoService;

/**
 * 利息宝配置管理
 */
class YuebaoConfigController extends Base
{
    /**
     * 余额宝服务实例
     */
    private YuebaoService $yuebaoService;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->yuebaoService = new YuebaoService();
    }

    /**
     * 配置页面
     * @return Response
     */
    public function index(): Response
    {
        return raw_view('yuebao-config/index');
    }

    /**
     * Lấy配置
     * @return Response
     */
    public function get(): Response
    {
        $result = $this->yuebaoService->getConfig();
        return json($result);
    }

    /**
     * Lưu配置
     * @param Request $request
     * @return Response
     */
    public function save(Request $request): Response
    {
        $data = $request->post();
        
        // 验证并格式化dữ liệu
        $config = [
            'settle_cycle'       => $data['settle_cycle'] ?? '1小时',
            'settle_cycle_hours' => (int)($data['settle_cycle_hours'] ?? 1),
            'annual_rate'        => $data['annual_rate'] ?? '4%',
            'annual_rate_value'  => (float)($data['annual_rate_value'] ?? 0.04),
            'min_amount'         => (int)($data['min_amount'] ?? 20),
            'max_interest'       => $data['max_interest'] ?? '不限制',
            'claim_time'         => $data['claim_time'] ?? '隔天领取',
            'audit_multiple'     => (int)($data['audit_multiple'] ?? 1),
        ];

        $result = $this->yuebaoService->saveConfig($config);
        return json($result);
    }
}
