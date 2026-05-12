<?php

namespace plugin\admin\app\controller;

use support\Request;
use support\Db;

/**
 * 玩法管理控制器
 */
class PlayController extends Base
{
    /**
     * 玩法管理首页
     */
    public function index(Request $request)
    {
        return view('play/index');
    }

    /**
     * 获取玩法列表数据
     */
    public function list(Request $request)
    {
        $typeid = $request->get('typeid', 'k3'); // 彩种类型：k3, ssc, pk10, keno, x5, dp3, lhc, xy28
        
        // 查询该类型的所有玩法
        $plays = Db::table('caipiao_wanfa')
            ->where('typeid', $typeid)
            ->orderBy('id', 'asc')
            ->get();
        
        $data = [];
        foreach ($plays as $play) {
            $data[] = [
                'id' => $play->id,
                'playid' => $play->playid,
                'typeid' => $play->typeid,
                'title' => $play->title ?? '',
                'rate' => $play->rate ?? 0,
                'maxprize' => $play->maxprize ?? 0,
                'totalzs' => $play->totalzs ?? 0,
                'maxzs' => $play->maxzs ?? 0,
                'minxf' => $play->minxf ?? 0,
                'maxxf' => $play->maxxf ?? 0,
                'isopen' => $play->isopen ?? 0,
            ];
        }
        
        return $this->json(0, 'success', $data);
    }

    /**
     * 保存玩法设置
     */
    public function save(Request $request)
    {
        $playid = $request->post('playid');
        $typeid = $request->post('typeid');
        
        \support\Log::info('玩法保存 - 接收到的POST数据: ' . json_encode($request->post(), JSON_UNESCAPED_UNICODE));
        
        $data = [
            'title' => $request->post('title', ''),
            'rate' => $request->post('rate', 0),
            'maxprize' => $request->post('maxprize', 0),
            'totalzs' => $request->post('totalzs', 0),
            'maxzs' => $request->post('maxzs', 0),
            'minxf' => $request->post('minxf', 0),
            'maxxf' => $request->post('maxxf', 0),
        ];
        
        // 如果记录存在则更新，否则插入
        $exists = Db::table('caipiao_wanfa')
            ->where('playid', $playid)
            ->where('typeid', $typeid)
            ->first();
        
        if ($exists) {
            $result = Db::table('caipiao_wanfa')
                ->where('playid', $playid)
                ->where('typeid', $typeid)
                ->update($data);
            \support\Log::info('玩法保存 - 更新结果: playid=' . $playid . ', 影响行数=' . $result);
        } else {
            $data['playid'] = $playid;
            $data['typeid'] = $typeid;
            $data['isopen'] = 1;
            $result = Db::table('caipiao_wanfa')->insert($data);
            \support\Log::info('玩法保存 - 插入结果: playid=' . $playid . ', 成功=' . ($result ? 'true' : 'false'));
        }
        
        return $this->json(0, '保存成功');
    }

    /**
     * 设置玩法状态
     */
    public function setStatus(Request $request)
    {
        $playid = $request->post('playid');
        $field = $request->post('field', 'isopen');
        $value = $request->post('value');
        
        \support\Log::info('玩法状态设置: playid=' . $playid . ', field=' . $field . ', value=' . $value);
        
        if (empty($playid)) {
            return $this->json(1, '参数错误');
        }
        
        $result = Db::table('caipiao_wanfa')
            ->where('playid', $playid)
            ->update([$field => $value]);
        
        return $this->json(0, '操作成功');
    }
}

