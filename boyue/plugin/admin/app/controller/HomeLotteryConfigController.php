<?php

namespace plugin\admin\app\controller;

use support\Request;
use support\Db;

/**
 * 首页开奖配置控制器
 */
class HomeLotteryConfigController extends Base
{
    /**
     * 不需要鉴权的方法
     */
    protected $noNeedAuth = [];

    /**
     * 获取所有可选彩种列表
     */
    public function lotteries(Request $request)
    {
        try {
            // Lấy所有彩种
            $lotteries = Db::table('caipiao_caipiao')
                ->select('id', 'name', 'title', 'typeid', 'isopen', 'allsort')
                ->orderBy('allsort', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            $list = [];
            foreach ($lotteries as $lottery) {
                $list[] = [
                    'id' => $lottery->id,
                    'name' => $lottery->name,
                    'title' => $lottery->title,
                    'typeid' => $lottery->typeid,
                    'isopen' => $lottery->isopen,
                    'sort' => $lottery->allsort
                ];
            }

            return $this->json(0, 'ok', ['list' => $list]);
        } catch (\Exception $e) {
            return $this->json(1, 'Lấy彩种列表Thất bại: ' . $e->getMessage());
        }
    }

    /**
     * 获取首页开奖配置
     */
    public function getData(Request $request)
    {
        try {
            // Lấy配置
            $config = Db::table('caipiao_setting')
                ->where('name', 'home_lottery_config')
                ->first();

            $data = [
                'enabled_lotteries' => [],
                'max_display' => 8,
            ];

            if ($config && $config->value) {
                $decoded = json_decode($config->value, true);
                if ($decoded) {
                    $data = array_merge($data, $decoded);
                }
            }

            // Lấy所有彩种
            $lotteries = Db::table('caipiao_caipiao')
                ->select('id', 'name', 'title', 'typeid', 'isopen', 'allsort')
                ->orderBy('allsort', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            $lotteryList = [];
            foreach ($lotteries as $lottery) {
                $lotteryList[] = [
                    'id' => $lottery->id,
                    'name' => $lottery->name,
                    'title' => $lottery->title,
                    'typeid' => $lottery->typeid,
                    'isopen' => $lottery->isopen,
                    'sort' => $lottery->allsort,
                    // 是否已选中
                    'selected' => in_array($lottery->name, $data['enabled_lotteries'])
                ];
            }

            return $this->json(0, 'ok', [
                'config' => $data,
                'lotteries' => $lotteryList
            ]);
        } catch (\Exception $e) {
            return $this->json(1, 'Lấycấu hình thất bại: ' . $e->getMessage());
        }
    }

    /**
     * 保存首页开奖配置
     */
    public function save(Request $request)
    {
        try {
            // 尝试从 JSON body Lấy dữ liệu
            $data = $request->all();
            \support\Log::info('Home lottery config save request: ' . json_encode($data));
            
            $enabledLotteries = $data['enabled_lotteries'] ?? [];
            $maxDisplay = $data['max_display'] ?? 8;

            // 处理字符串格式（逗号分隔）
            if (is_string($enabledLotteries)) {
                $enabledLotteries = $enabledLotteries ? explode(',', $enabledLotteries) : [];
            }
            if (!is_array($enabledLotteries)) {
                $enabledLotteries = [];
            }

            $maxDisplay = intval($maxDisplay);
            if ($maxDisplay < 1) {
                $maxDisplay = 1;
            }
            if ($maxDisplay > 20) {
                $maxDisplay = 20;
            }

            $configValue = json_encode([
                'enabled_lotteries' => $enabledLotteries,
                'max_display' => $maxDisplay,
            ], JSON_UNESCAPED_UNICODE);

            // 检查配置是否存在
            $exists = Db::table('caipiao_setting')
                ->where('name', 'home_lottery_config')
                ->first();

            if ($exists) {
                Db::table('caipiao_setting')
                    ->where('name', 'home_lottery_config')
                    ->update(['value' => $configValue]);
            } else {
                Db::table('caipiao_setting')->insert([
                    'name' => 'home_lottery_config',
                    'value' => $configValue
                ]);
            }

            return $this->json(0, 'LưuThành công');
        } catch (\Exception $e) {
            return $this->json(1, 'LưuThất bại: ' . $e->getMessage());
        }
    }

    /**
     * 更新彩种排序
     */
    public function updateSort(Request $request)
    {
        try {
            $sortData = $request->post('sort_data', []);

            if (!is_array($sortData) || empty($sortData)) {
                return $this->json(1, '排序dữ liệu为空');
            }

            foreach ($sortData as $item) {
                if (isset($item['name']) && isset($item['sort'])) {
                    Db::table('caipiao_caipiao')
                        ->where('name', $item['name'])
                        ->update(['allsort' => intval($item['sort'])]);
                }
            }

            return $this->json(0, '排序LưuThành công');
        } catch (\Exception $e) {
            return $this->json(1, '排序LưuThất bại: ' . $e->getMessage());
        }
    }
}
