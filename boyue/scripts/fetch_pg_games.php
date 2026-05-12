<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$apiUrl = $_ENV['NG_API_URL'] ?? 'https://api.ng777.net';
$sn = $_ENV['NG_SN'] ?? '';
$secretKey = $_ENV['NG_SECRET_KEY'] ?? '';

if (empty($sn) || empty($secretKey)) {
    die("错误: 请在 .env 文件中配置 NG_SN 和 NG_SECRET_KEY\n");
}

function generateRandomString($length = 32) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

function callNGApi($apiUrl, $endpoint, $params, $sn, $secretKey) {
    $random = generateRandomString(32);
    $sign = strtolower(md5($random . $sn . $secretKey));
    
    $headers = [
        'Content-Type: application/json',
        'sign: ' . $sign,
        'random: ' . $random,
        'sn: ' . $sn,
    ];
    
    $url = $apiUrl . $endpoint;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($error) {
        throw new Exception("HTTP请求失败: " . $error);
    }
    
    return json_decode($response, true);
}

echo "正在获取 PG 平台游戏列表...\n";
echo "API URL: {$apiUrl}\n";
echo "商户号: {$sn}\n\n";

try {
    $result = callNGApi($apiUrl, '/api/server/gameCode', ['platType' => 'pg'], $sn, $secretKey);
    
    if ($result['code'] == '10000') {
        $games = $result['data'] ?? [];
        
        echo "获取成功! 共 " . count($games) . " 款游戏\n\n";
        echo str_repeat("=", 80) . "\n";
        
        
        $gameTypes = [
            '1' => '真人视讯',
            '2' => '老虎机/电子',
            '3' => '彩票',
            '4' => '体育',
            '5' => '电竞',
            '6' => '捕鱼',
            '7' => '棋牌',
        ];
        
        $groupedGames = [];
        foreach ($games as $game) {
            $type = $game['gameType'] ?? 'unknown';
            $groupedGames[$type][] = $game;
        }
        
        
        foreach ($groupedGames as $type => $typeGames) {
            $typeName = $gameTypes[$type] ?? "类型{$type}";
            echo "\n【{$typeName}】共 " . count($typeGames) . " 款\n";
            echo str_repeat("-", 60) . "\n";
            
            foreach ($typeGames as $game) {
                $gameCode = $game['gameCode'] ?? '';
                $gameName = $game['gameName']['zh-hans'] ?? $game['gameName']['en'] ?? $gameCode;
                $ingress = $game['ingress'] ?? '';
                
                $ingressText = match($ingress) {
                    '1' => '[PC]',
                    '2' => '[手机]',
                    '3' => '[通用]',
                    default => ''
                };
                
                echo sprintf("  %-30s %s %s\n", $gameCode, $gameName, $ingressText);
            }
        }
        
        
        $outputFile = __DIR__ . '/pg_games_list.json';
        file_put_contents($outputFile, json_encode($result['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo "\n\n游戏列表已保存到: {$outputFile}\n";
        
        
        echo "\n\n【游戏图标 URL 格式】\n";
        echo "NG API 通常提供以下图标地址格式：\n";
        echo "https://res.ng777.net/game_icon/{platType}/{gameCode}.png\n";
        echo "示例: https://res.ng777.net/game_icon/pg/fortune-tiger.png\n";
        
    } else {
        echo "获取失败: " . ($result['msg'] ?? '未知错误') . "\n";
        echo "错误码: " . ($result['code'] ?? 'N/A') . "\n";
    }
    
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
}
