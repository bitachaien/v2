<?php

namespace plugin\admin\app\service;

/**
 * BBIN 平台服务类（通过 NG 聚合平台）
 */
class BbinService
{
    private $apiAccount;
    private $apiKey;
    private $apiPasswd;
    private $base_url = 'https://ap.api-bet.net';  // 新的 NG 平台地址
    private $lang = 'zh-CN';  // 语言

    public function __construct()
    {
        // 从配置文件或环境变量获取
        $this->apiAccount = env('BBIN_API_ACCOUNT', 'FNhang294');
        $this->apiKey = env('BBIN_API_KEY', 'RhE4dcEGAQK8jFyekRMEr4pnp7HF6KM3Up8wrc42umVAuEfMkpz');
        $this->apiPasswd = env('BBIN_API_PASSWD', 'ybvuvcy7');
    }

    /**
     * 生成 Salt
     */
    private function getSalt($userName)
    {
        return substr(md5($userName), 0, 5);
    }

    /**
     * 获取投注记录
     * @param string $roundDate 日期 (Y-m-d)
     * @param string $startTime 开始时间 (H:i:s)
     * @param string $endTime 结束时间 (H:i:s)
     * @param int $gameKind 游戏类型
     * @param string $subGameKind 子游戏类型
     * @param string $gameType 游戏类型
     * @param int $pageIndex 页码
     * @param int $pageSize 每页数量
     */
    public function getGameRecord($roundDate, $startTime, $endTime, $gameKind, $subGameKind = '', $gameType = '', $pageIndex = 1, $pageSize = 500)
    {
        $salt = $this->getSalt('' . time());
        $code = $salt . md5($this->apiKey . $this->apiAccount . $roundDate . $startTime . $endTime . $gameKind . $gameType . $subGameKind . $pageIndex . $pageSize . $salt);
        
        $postdata = [
            'apiAccount' => $this->apiAccount,
            'roundDate' => $roundDate,
            'startTime' => $startTime,
            'endTime' => $endTime,
            'gameKind' => $gameKind,
            'subGameKind' => $subGameKind,
            'gameType' => $gameType,
            'pageIndex' => $pageIndex,
            'pageSize' => $pageSize,
            'code' => $code
        ];
        
        $url = $this->base_url . '/api/bbin/betrecord.ashx';
        return $this->sendRequest($url, $postdata);
    }

    /**
     * 获取商户余额
     */
    public function credit()
    {
        $salt = $this->getSalt($this->apiAccount);
        $code = $salt . md5($this->apiKey . $this->apiAccount . $salt);
        
        $postdata = [
            'apiAccount' => $this->apiAccount,
            'code' => $code
        ];
        
        $url = $this->base_url . '/api/bbin/credit.ashx';
        return $this->sendRequest($url, $postdata);
    }

    /**
     * 发送请求
     */
    private function sendRequest($url, $post_data = [], $post = true)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, $post);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        // 添加请求头，绕过 Cloudflare 防护
        $headers = [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept: application/json, text/plain, */*',
            'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
            'Accept-Encoding: gzip, deflate, br',
            'Connection: keep-alive'
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        
        $res = curl_exec($ch);
        
        // 记录错误信息
        if (curl_errno($ch)) {
            \support\Log::error('BBIN CURL 错误', ['error' => curl_error($ch), 'url' => $url]);
        }
        
        curl_close($ch);
        
        return json_decode($res, true);
    }
}

