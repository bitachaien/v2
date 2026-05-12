<?php

namespace plugin\admin\app\controller;

use support\Request;
use support\Db;

/**
 * 真人视讯管理
 */
class LiveController
{
    /**
     * 商户信息页面
     */
    public function merchant(Request $request)
    {
        // 调用 AG 服务获取所有平台余额
        $agService = new \plugin\admin\app\service\AgService();
        $result = $agService->getAllCredit();
        
        $balances = [];
        $errorMsg = '';
        
        if ($result['code'] == 1) {
            $balances = $result['data'];
        } else {
            $errorMsg = $result['msg'] ?? '获取余额失败';
            \support\Log::error('商户余额获取失败: ' . $errorMsg);
        }
        
        // 平台列表
        $platforms = [
            'tyscore' => '通用额度',
            'ag' => 'AG',
            'ags' => 'AGS',
            'allbet' => 'ALLBET',
            'bbin' => 'BBIN',
            'cs' => 'CS',
            'csk' => 'CSK',
            'bg' => 'BG',
            'og' => 'OG',
            'mg' => 'MG',
            'pt' => 'PT',
            'lebo' => 'LEBO',
            'gd' => 'GD',
            'dg' => 'DG',
            'gpi' => 'GPI',
            'sg' => 'SG',
            'pp' => 'PP',
            'ttg' => 'TTG',
            'qt' => 'QT',
            'sunbet' => 'SUNBET',
            'sunbets' => 'SUNBETS',
            'sa' => 'SA',
            'ibc' => 'IBC',
            'ss' => 'SS',
            'ky' => '开元棋牌',
            'mw' => 'MW',
            'cq9' => 'CQ9',
            'vr' => 'VR',
            'eg' => 'EG',
            'gj' => 'GJ',
            'ig' => 'IG',
            'mt' => 'MT',
            'jdb' => 'JDB',
            'esb' => 'ESB',
            'vg' => 'VG',
            'newbb' => 'NEWBB',
            'fg' => 'FG',
            'hc' => 'HC',
            'avia' => 'AVIA',
            'leg' => 'LEG',
            'sw' => 'SW',
            'bng' => 'BNG',
            'dt' => 'DT',
            'pg' => 'PG',
            'png' => 'PNG',
            'gti' => 'GTI',
            'ap' => 'AP',
            'sgl' => 'SGL',
            'sgp' => 'SGP',
            'ga' => 'GA',
            'ebet' => 'EBET',
            'gns' => 'GNS',
            'hb' => 'HB',
            'rt' => 'RT',
            'gg' => 'GG',
            'bl' => 'BL',
            'isb' => 'ISB',
            'pgs' => 'PGS',
            'im' => 'IM',
            'sexy' => 'SEXY',
            'fh' => 'FH',
            'nw' => 'NW',
            'rmg' => 'RMG',
            'tcg' => 'TCG',
            's128' => 'S128'
        ];

        return view('live/merchant', [
            'platforms' => $platforms, 
            'balances' => $balances,
            'errorMsg' => $errorMsg
        ]);
    }

    /**
     * 额度转让页面
     */
    public function transfer(Request $request)
    {
        return view('live/transfer');
    }

    /**
     * 获取额度转让列表数据
     */
    public function transferList(Request $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        
        // 搜索条件
        $sDate = $request->get('sDate', '');
        $eDate = $request->get('eDate', '');
        $username = $request->get('username', '');

        $query = Db::table('caipiao_transrecord as t')
            ->leftJoin('caipiao_member as m', 't.uid', '=', 'm.id')
            ->select('t.*', 'm.username');

        // 时间筛选
        if ($sDate) {
            $query->where('t.transTime', '>=', $sDate . ' 00:00:00');
        }
        if ($eDate) {
            $query->where('t.transTime', '<=', $eDate . ' 23:59:59');
        }

        // 用户名筛选
        if ($username) {
            $query->where('m.username', 'like', "%{$username}%");
        }

        // 获取总数
        $count = $query->count();

        // 分页查询
        $list = $query->orderBy('t.transID', 'desc')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get();

        // 处理数据
        $data = [];
        foreach ($list as $item) {
            $row = (array)$item;
            // 格式化时间
            if ($row['transTime']) {
                $row['transTime'] = date('Y-m-d H:i:s', strtotime($row['transTime']));
            }
            $data[] = $row;
        }

        return json([
            'code' => 0,
            'msg' => '',
            'count' => $count,
            'data' => $data
        ]);
    }

    /**
     * 投注记录页面
     */
    public function betrecord(Request $request)
    {
        return view('live/betrecord');
    }

    /**
     * 获取投注记录列表数据
     */
    public function betrecordList(Request $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        
        // 平台类型：ag 或 bbin，默认 ag
        $platform = $request->get('platform', 'ag');
        
        // 搜索条件
        $sDate = $request->get('sDate', '');
        $eDate = $request->get('eDate', '');
        $username = $request->get('username', '');

        if ($platform === 'bbin') {
            // BBIN 投注记录
            $query = Db::table('caipiao_bbbetrecord');
            
            // 时间筛选
            if ($sDate) {
                $query->where('WagersDate', '>=', $sDate . ' 00:00:00');
            }
            if ($eDate) {
                $query->where('WagersDate', '<=', $eDate . ' 23:59:59');
            }
            
            // 用户名筛选
            if ($username) {
                $query->where('UserName', 'like', "%{$username}%");
            }
            
            // 获取总数
            $count = $query->count();
            
            // 分页查询
            $list = $query->orderBy('bbId', 'desc')
                ->offset(($page - 1) * $limit)
                ->limit($limit)
                ->get();
            
            // 处理数据
            $data = [];
            foreach ($list as $item) {
                $row = (array)$item;
                // 格式化时间
                if ($row['WagersDate']) {
                    $row['WagersDate'] = date('Y-m-d H:i:s', strtotime($row['WagersDate']));
                }
                $data[] = $row;
            }
        } else {
            // AG 投注记录
            $query = Db::table('caipiao_agbetrecord');
            
            // 时间筛选
            if ($sDate) {
                $query->where('betTime', '>=', $sDate . ' 00:00:00');
            }
            if ($eDate) {
                $query->where('betTime', '<=', $eDate . ' 23:59:59');
            }
            
            // 用户名筛选
            if ($username) {
                $query->where('username', 'like', "%{$username}%");
            }
            
            // 获取总数
            $count = $query->count();
            
            // 分页查询
            $list = $query->orderBy('id', 'desc')
                ->offset(($page - 1) * $limit)
                ->limit($limit)
                ->get();
            
            // 处理数据
            $data = [];
            foreach ($list as $item) {
                $row = (array)$item;
                // 格式化时间
                if ($row['betTime']) {
                    $row['betTime'] = date('Y-m-d H:i:s', strtotime($row['betTime']));
                }
                if ($row['lastUpdateTime']) {
                    $row['lastUpdateTime'] = date('Y-m-d H:i:s', strtotime($row['lastUpdateTime']));
                }
                $data[] = $row;
            }
        }

        return json([
            'code' => 0,
            'msg' => '',
            'count' => $count,
            'data' => $data
        ]);
    }

    /**
     * 真人采集页面
     */
    public function collect(Request $request)
    {
        return view('live/collect');
    }

    /**
     * 获取 AG 投注记录（采集数据）
     */
    public function getAgRecord(Request $request)
    {
        try {
            date_default_timezone_set("UTC");
            $n = 0;
            
            // 设置查询时间范围（当天数据）
            $startDate = date("Y-m-d 00:00:00");
            $endDate = date("Y-m-d H:i:s");
            
            // 调用 AG 服务获取投注记录
            $agService = new \plugin\admin\app\service\AgService();
            $recordList = $agService->getBetRecord($startDate, $endDate, 1, 500);
            
            if (!$recordList || !isset($recordList['data']['record'])) {
                return json([
                    'code' => 0,
                    'nums' => 0,
                    'ascn' => '获取记录失败'
                ]);
            }
            
            $zxrecordList = $recordList['data']['record'];
            $dataList = [];
            
            // 遍历记录，检查是否已存在
            foreach ($zxrecordList as $record) {
                $betId = $record['betId'];
                
                // 判断记录是否存在
                $exists = Db::table('caipiao_agbetrecord')
                    ->where('betId', $betId)
                    ->count();
                
                if (empty($exists)) {
                    $n++;
                    $dataList[] = [
                        'betId' => $record['betId'],
                        'username' => $record['username'],
                        'platType' => $record['platType'],
                        'gameType' => $record['gameType'],
                        'betAmount' => $record['betAmount'],
                        'validAmount' => $record['validAmount'],
                        'winLoss' => $record['winLoss'],
                        'gameName' => $record['gameName'],
                        'betContent' => $record['betContent'],
                        'awardResult' => $record['awardResult'],
                        'betTime' => $record['betTime'],
                        'lastUpdateTime' => $record['lastUpdateTime'],
                        'status' => $record['status']
                    ];
                }
            }
            
            // 批量插入数据
            if (!empty($dataList)) {
                Db::table('caipiao_agbetrecord')->insert($dataList);
            }
            
            return json([
                'code' => 1,
                'nums' => $n,
                'ascn' => '成功'
            ]);
        } catch (\Exception $e) {
            \support\Log::error('AG采集失败: ' . $e->getMessage());
            return json([
                'code' => 0,
                'nums' => 0,
                'ascn' => '失败：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 获取 BBIN 投注记录（采集数据）
     */
    public function getBbinRecord(Request $request)
    {
        try {
            date_default_timezone_set("UTC");
            $n = 0;
            
            // 设置查询时间（当天数据）
            $nDate = date("Y-m-d");
            $nTime = date("H:i:s");
            
            // 调用 BBIN 服务获取投注记录
            $bbinService = new \plugin\admin\app\service\BbinService();
            $recordList = $bbinService->getGameRecord($nDate, "00:00:00", $nTime, 3, '', '', 1, 500);
            
            if (!$recordList || !isset($recordList['Data'])) {
                return json([
                    'code' => 0,
                    'nums' => 0,
                    'ascn' => '获取记录失败'
                ]);
            }
            
            $zxrecordList = $recordList['Data'];
            $dataList = [];
            
            // 遍历记录，检查是否已存在
            foreach ($zxrecordList as $record) {
                $WagersID = $record['WagersID'];
                
                // 判断记录是否存在
                $exists = Db::table('caipiao_bbbetrecord')
                    ->where('WagersID', $WagersID)
                    ->count();
                
                if (empty($exists)) {
                    $n++;
                    $dataList[] = [
                        'UserName' => $record['UserName'],
                        'WagersID' => $record['WagersID'],
                        'WagersDate' => $record['WagersDate'],
                        'SerialID' => $record['SerialID'],
                        'RoundNo' => $record['RoundNo'],
                        'GameType' => $record['GameType'],
                        'WagerDetail' => $record['WagerDetail'],
                        'GameCode' => $record['GameCode'],
                        'Result' => $record['Result'],
                        'Card' => $record['Card'],
                        'BetAmount' => $record['BetAmount'],
                        'Origin' => $record['Origin'],
                        'Commissionable' => $record['Commissionable'],
                        'Payoff' => $record['Payoff'],
                        'ExchangeRate' => $record['ExchangeRate']
                    ];
                }
            }
            
            // 批量插入数据
            if (!empty($dataList)) {
                Db::table('caipiao_bbbetrecord')->insert($dataList);
            }
            
            return json([
                'code' => 1,
                'nums' => $n,
                'ascn' => '成功'
            ]);
        } catch (\Exception $e) {
            \support\Log::error('BBIN采集失败: ' . $e->getMessage());
            return json([
                'code' => 0,
                'nums' => 0,
                'ascn' => '失败：' . $e->getMessage()
            ]);
        }
    }
}

