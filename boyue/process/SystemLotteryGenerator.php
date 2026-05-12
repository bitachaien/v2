<?php

namespace process;

use Workerman\Timer;
use support\Db;
use support\Log;

class SystemLotteryGenerator
{
    
    private $systemLotteries = [];
    
    
    public function onWorkerStart($worker)
    {
        Log::info('系统彩生成进程启动');
        
        
        $this->loadSystemLotteries();
        
        
        foreach ($this->systemLotteries as $name => $config) {
            $this->setupTimer($name, $config);
        }
        
        
        Timer::add(60, function() {
            $this->reloadSystemLotteries();
        });
    }
    
    
    private function loadSystemLotteries()
    {
        try {
            
            $lotteries = Db::table('caipiao_caipiao')
                ->where('isopen', 1)
                ->where('issys', 1)
                ->get();
            
            foreach ($lotteries as $lottery) {
                $this->systemLotteries[$lottery->name] = [
                    'type' => $lottery->typeid,
                    'title' => $lottery->title,
                    'interval' => $lottery->expecttime * 60, 
                ];
            }
            
            Log::info('已加载 ' . count($this->systemLotteries) . ' 个系统彩配置');
        } catch (\Exception $e) {
            Log::error('加载系统彩配置失败: ' . $e->getMessage());
        }
    }
    
    
    private function reloadSystemLotteries()
    {
        try {
            $lotteries = Db::table('caipiao_caipiao')
                ->where('isopen', 1)
                ->where('issys', 1)
                ->get();
            
            foreach ($lotteries as $lottery) {
                $name = $lottery->name;
                
                
                if (!isset($this->systemLotteries[$name])) {
                    $config = [
                        'type' => $lottery->typeid,
                        'title' => $lottery->title,
                        'interval' => $lottery->expecttime * 60,
                    ];
                    $this->systemLotteries[$name] = $config;
                    $this->setupTimer($name, $config);
                    Log::info("新增系统彩: {$lottery->title} ({$name})");
                }
            }
        } catch (\Exception $e) {
            Log::error('重新加载系统彩配置失败: ' . $e->getMessage());
        }
    }
    
    
    private function setupTimer($name, $config)
    {
        $interval = $config['interval'];
        
        
        $this->ensureIssuesContinuous($name, $config);
        $this->generateIssueRecord($name, $config);
        $this->generateNextIssueRecord($name, $config);
        
        
        Timer::add(1, function() use ($name, $config, $interval) {
            
            $this->ensureCurrentAndNextIssues($name, $config);
            
            
            $this->generateOpencode($name, $config);
        });
        
        Log::info("系统彩 {$config['title']} 定时器已设置，间隔: {$interval}秒");
    }
    
    
    private function ensureCurrentAndNextIssues($name, $config)
    {
        try {
            $interval = $config['interval'];
            $now = time();
            $dayStart = strtotime(date('Y-m-d 00:00:00', $now));
            $secondsInDay = $now - $dayStart;
            $date = date('Ymd', $now);
            
            
            $currentIssueNum = floor($secondsInDay / $interval) + 1;
            
            
            for ($offset = 0; $offset <= 2; $offset++) {
                $issueNum = $currentIssueNum + $offset;
                $expect = $date . str_pad($issueNum, 4, '0', STR_PAD_LEFT);
                $opentime = $dayStart + ($issueNum * $interval);
                
                $exists = Db::table('caipiao_kaijiang')
                    ->where('name', $name)
                    ->where('expect', $expect)
                    ->exists();
                
                if (!$exists) {
                    Db::table('caipiao_kaijiang')->insert([
                        'name' => $name,
                        'title' => $config['title'],
                        'expect' => $expect,
                        'opencode' => '',
                        'opentime' => $opentime,
                        'source' => '系统生成',
                        'sourcecode' => '',
                        'remarks' => '',
                        'addtime' => time(),
                        'isdraw' => 0,
                    ]);
                }
            }
        } catch (\Exception $e) {
            
            if (strpos($e->getMessage(), 'Duplicate entry') === false) {
                Log::error("确保期号失败 {$name}: " . $e->getMessage());
            }
        }
    }
    
    
    private function ensureIssuesContinuous($name, $config)
    {
        try {
            $interval = $config['interval'];
            $now = time();
            $dayStart = strtotime(date('Y-m-d 00:00:00', $now));
            $date = date('Ymd', $now);
            
            
            $maxExpect = Db::table('caipiao_kaijiang')
                ->where('name', $name)
                ->where('expect', 'like', $date . '%')
                ->max('expect');
            
            if (!$maxExpect) {
                return; 
            }
            
            $maxIssueNum = (int)substr($maxExpect, -4);
            
            
            $secondsInDay = $now - $dayStart;
            $currentIssueNum = floor($secondsInDay / $interval) + 1;
            
            
            for ($i = 1; $i <= $currentIssueNum + 1; $i++) {
                $expect = $date . str_pad($i, 4, '0', STR_PAD_LEFT);
                $opentime = $dayStart + ($i * $interval);
                
                $exists = Db::table('caipiao_kaijiang')
                    ->where('name', $name)
                    ->where('expect', $expect)
                    ->exists();
                
                if (!$exists) {
                    Db::table('caipiao_kaijiang')->insert([
                        'name' => $name,
                        'title' => $config['title'],
                        'expect' => $expect,
                        'opencode' => '',
                        'opentime' => $opentime,
                        'source' => '系统补充',
                        'sourcecode' => '',
                        'remarks' => '',
                        'addtime' => time(),
                        'isdraw' => 0,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error("补充期号失败 {$name}: " . $e->getMessage());
        }
    }
    
    
    private function generateIssueRecord($name, $config)
    {
        try {
            $lottery = Db::table('caipiao_caipiao')
                ->where('name', $name)
                ->where('isopen', 1)
                ->first();
            
            if (!$lottery) {
                return;
            }
            
            $expect = $this->generateExpect($name, $config);
            
            
            $exists = Db::table('caipiao_kaijiang')
                ->where('name', $name)
                ->where('expect', $expect)
                ->exists();
            
            if ($exists) {
                return;
            }
            
            
            $now = time();
            $interval = $config['interval'];
            $dayStart = strtotime(date('Y-m-d 00:00:00', $now));
            $secondsInDay = $now - $dayStart;
            $issueNum = floor($secondsInDay / $interval) + 1;
            $opentime = $dayStart + ($issueNum * $interval);
            
            
            Db::table('caipiao_kaijiang')->insert([
                'name' => $name,
                'title' => $config['title'],
                'expect' => $expect,
                'opencode' => '',  
                'opentime' => $opentime,
                'source' => '系统生成',
                'sourcecode' => '',
                'remarks' => '',
                'addtime' => time(),
                'isdraw' => 0,
            ]);
            
        } catch (\Exception $e) {
            Log::error("创建期号失败 {$name}: " . $e->getMessage());
        }
    }
    
    
    private function generateNextIssueRecord($name, $config)
    {
        try {
            $lottery = Db::table('caipiao_caipiao')
                ->where('name', $name)
                ->where('isopen', 1)
                ->first();
            
            if (!$lottery) {
                return;
            }
            
            $interval = $config['interval'];
            $now = time();
            $dayStart = strtotime(date('Y-m-d 00:00:00', $now));
            $secondsInDay = $now - $dayStart;
            
            
            $nextIssueNum = floor($secondsInDay / $interval) + 2;
            $date = date('Ymd', $now);
            $nextExpect = $date . str_pad($nextIssueNum, 4, '0', STR_PAD_LEFT);
            $nextOpentime = $dayStart + ($nextIssueNum * $interval);
            
            
            $exists = Db::table('caipiao_kaijiang')
                ->where('name', $name)
                ->where('expect', $nextExpect)
                ->exists();
            
            if ($exists) {
                return;
            }
            
            Db::table('caipiao_kaijiang')->insert([
                'name' => $name,
                'title' => $config['title'],
                'expect' => $nextExpect,
                'opencode' => '',
                'opentime' => $nextOpentime,
                'source' => '系统生成',
                'sourcecode' => '',
                'remarks' => '',
                'addtime' => time(),
                'isdraw' => 0,
            ]);
            
        } catch (\Exception $e) {
            Log::error("创建下一期失败 {$name}: " . $e->getMessage());
        }
    }
    
    
    private function generateOpencode($name, $config)
    {
        try {
            $now = time();
            
            
            $pendingIssue = Db::table('caipiao_kaijiang')
                ->where('name', $name)
                ->where('opentime', '<=', $now)
                ->where('opencode', '')
                ->orderBy('opentime', 'asc')
                ->first();
            
            if (!$pendingIssue) {
                return;
            }
            
            $lottery = Db::table('caipiao_caipiao')
                ->where('name', $name)
                ->where('isopen', 1)
                ->first();
            
            if (!$lottery) {
                return;
            }
            
            $expect = $pendingIssue->expect;
            $lotteryType = $lottery->typeid;
            
            
            $preset = Db::table('caipiao_yukaijiang')
                ->where('name', $name)
                ->where('expect', $expect)
                ->first();
            
            if ($preset) {
                $opencode = $preset->opencode;
                
                
                if ($lotteryType === 'xy28') {
                    $parts = explode(',', $opencode);
                    if (count($parts) === 3) {
                        
                        $sum = array_sum(array_map('intval', $parts));
                        $opencode = $opencode . ',' . $sum;
                    }
                }
                
                Log::info("{$config['title']} 期号 {$expect} 使用预设号码: {$opencode}");
            } else {
                
                $opencode = $this->generateRandomOpencode($lotteryType);
                
                
                if ($this->shouldControlProfit()) {
                    $opencode = $this->controlProfit($name, $expect, $opencode, $lotteryType);
                }
            }
            
            
            Db::table('caipiao_kaijiang')
                ->where('id', $pendingIssue->id)
                ->update(['opencode' => $opencode]);
            
            Log::info("{$config['title']} 开奖: 期号 {$expect}, 开奖号码: {$opencode}");
            
        } catch (\Exception $e) {
            Log::error("生成开奖号码失败 {$name}: " . $e->getMessage());
        }
    }
    
    
    private function generateExpect($name, $config)
    {
        $now = time();
        $interval = $config['interval'];
        
        
        
        $date = date('Ymd', $now);
        $dayStart = strtotime(date('Y-m-d 00:00:00', $now));
        $secondsInDay = $now - $dayStart;
        $issueNum = floor($secondsInDay / $interval) + 1;
        
        
        $expect = $date . str_pad($issueNum, 4, '0', STR_PAD_LEFT);
        
        return $expect;
    }
    
    
    private function generateRandomOpencode($type)
    {
        switch ($type) {
            case 'ssc':
                
                $nums = [];
                for ($i = 0; $i < 5; $i++) {
                    $nums[] = rand(0, 9);
                }
                return implode(',', $nums);
                
            case 'k3':
                
                $nums = [];
                for ($i = 0; $i < 3; $i++) {
                    $nums[] = rand(1, 6);
                }
                return implode(',', $nums);
                
            case 'pk10':
                
                $nums = range(1, 10);
                shuffle($nums);
                return implode(',', $nums);
                
            case 'lhc':
                
                $nums = range(1, 49);
                shuffle($nums);
                $selected = array_slice($nums, 0, 7);
                return implode(',', $selected);
                
            case 'x5':
                
                $nums = range(1, 11);
                shuffle($nums);
                $selected = array_slice($nums, 0, 5);
                sort($selected); 
                return implode(',', $selected);
                
            case 'xy28':
                
                $nums = [];
                for ($i = 0; $i < 3; $i++) {
                    $nums[] = rand(0, 9);
                }
                $sum = array_sum($nums);
                
                return implode(',', $nums) . ',' . $sum;
            
            case 'dwc':
                
                $nums = range(1, 6);
                shuffle($nums);
                return implode(',', $nums);
                
            default:
                
                Log::warning("未知彩种类型: {$type}");
                return '';
        }
    }
    
    
    private function shouldControlProfit()
    {
        
        $setting = Db::table('caipiao_setting')
            ->where('name', 'xtclirun')
            ->value('value');
        
        if (!$setting) {
            return false;
        }
        
        
        $probability = intval($setting); 
        $random = abs(substr(microtime(true), -2));
        
        return $random < $probability;
    }
    
    
    private function controlProfit($name, $expect, $opencode, $type)
    {
        
        $hasWinningBets = $this->checkWinningBets($name, $expect, $opencode);
        
        if (!$hasWinningBets) {
            
            return $opencode;
        }
        
        
        for ($i = 0; $i < 100; $i++) {
            $newOpencode = $this->generateRandomOpencode($type);
            
            if (!$this->checkWinningBets($name, $expect, $newOpencode)) {
                Log::info("利润控制：已为 {$name} 期号 {$expect} 重新生成号码");
                return $newOpencode;
            }
        }
        
        
        Log::warning("利润控制：无法生成不中奖号码，使用原号码 {$name} {$expect}");
        return $opencode;
    }
    
    
    private function checkWinningBets($name, $expect, $opencode)
    {
        
        $bets = Db::table('caipiao_touzhu')
            ->where('cpname', $name)
            ->where('expect', $expect)
            ->where('isdraw', 0)
            ->limit(100) 
            ->get();
        
        if ($bets->isEmpty()) {
            return false;
        }
        
        
        
        foreach ($bets as $bet) {
            
            
            if ($bet->tzmoney > 100) {
                
                
                return true;
            }
        }
        
        return false;
    }
}

