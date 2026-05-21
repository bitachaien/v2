<?php

namespace plugin\admin\app\service;

/**
 * NG 聚合平台服务类
 * 文档：https://doc.api-bet.com/docs/api
 */
class AgService
{
    protected $sn;          // 商户前缀 (原 api_account)
    protected $secretKey;   // 密钥 (原 sign_key)
    protected $base_url = 'https:// ap.api-bet.net';  // NG 平台地址

    public function __construct()
    {
        // 从配置文件hoặc环境变量Lấy
        $this->sn = env('NG_SN', env('NG_API_ACCOUNT', ''));
        $this->secretKey = env('NG_SECRET_KEY', env('NG_SIGN_KEY', ''));
        
        // lịch sử配置状态（仅用于调试）
        \support\Log::info('NG API 配置状态', [
            'sn' => $this->sn ? '已配置' : '未配置',
            'secretKey' => $this->secretKey ? '已配置' : '未配置',
            'base_url' => $this->base_url
        ]);
    }
    
    /**
     * 生成随机字符串
     * @param int $length 长度（16-32位）
     * @return string
     */
    private function generateRandom($length = 20)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $random = '';
        for ($i = 0; $i < $length; $i++) {
            $random .= $chars[rand(0, strlen($chars) - 1)];
        }
        return $random;
    }
    
    /**
     * 生成签名
     * sign = md5(random + sn + secretKey)
     * @param string $random
     * @return string
     */
    private function generateSign($random)
    {
        return md5($random . $this->sn . $this->secretKey);
    }
    
    /**
     * Lấy请求头
     * @return array
     */
    private function getHeaders()
    {
        $random = $this->generateRandom(20);
        $sign = $this->generateSign($random);
        
        return [
            'Content-Type: application/json',
            'sign: ' . $sign,
            'random: ' . $random,
            'sn: ' . $this->sn,
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'Accept: application/json'
        ];
    }

    /**
     * Lấy所有平台Số dư
     * 接口：/api/server/quota
     * 参数：currency（货币类型，如：CNY）
     */
    public function getAllCredit()
    {
        // 检查配置
        if (empty($this->sn) || empty($this->secretKey)) {
            return [
                'code' => 0,
                'data' => [],
                'msg' => 'NG 平台配置未Cài đặt，请在 .env 文件中Cài đặt NG_SN 和 NG_SECRET_KEY'
            ];
        }
        
        $url = $this->base_url . '/api/server/quota';
        $postData = [
            'currency' => 'CNY'  // 货币类型：人民币
        ];
        
        \support\Log::info('请求 NG 平台余额', ['url' => $url, 'params' => $postData]);
        
        $res = $this->httpPost($url, $postData);
        
        \support\Log::info('NG 平台返回', ['response' => $res]);
        
        if ($res && isset($res['code']) && $res['code'] == 10000) {
            // Thành công返回，解析dữ liệu
            // 返回格式：{"model":"1","CNY":100.100,"costRatio":1.00,"ratios":[...]}
            // CNY 是商户总Số dư，ratios 是各平台的占比
            $data = $res['data'] ?? [];
            $totalBalance = $data['CNY'] ?? 0;
            $ratios = $data['ratios'] ?? [];
            
            // 计算各平台实际Số dư
            $balances = [];
            foreach ($ratios as $item) {
                $platform = $item['platfrom'] ?? $item['platform'] ?? '';
                $ratio = $item['ratio'] ?? 0;
                if ($platform) {
                    $balances[$platform] = $totalBalance * $ratio;
                }
            }
            
            // Thêm总额度
            $balances['tyscore'] = $totalBalance;  // 通用额度 = 总Số dư
            
            return [
                'code' => 1,
                'data' => $balances
            ];
        } else {
            $errorMsg = $res['msg'] ?? 'Yêu cầu thất bại';
            return [
                'code' => 0,
                'data' => [],
                'msg' => 'API 返回Lỗi (' . ($res['code'] ?? 'unknown') . '): ' . $errorMsg
            ];
        }
    }

    /**
     * LấyĐặt cượclịch sử
     * 接口：/record/query
     * @param string $startTime 开始Thời gian
     * @param string $endTime 结束Thời gian
     * @param int $page 页码
     * @param int $limit 每页数量
     */
    public function getBetRecord($startTime, $endTime, $page = 1, $limit = 500)
    {
        $url = $this->base_url . '/record/query';
        
        $postData = [
            "startTime" => $startTime,
            "endTime" => $endTime,
            "page" => $page,
            "pageSize" => $limit
        ];
        
        $res = $this->httpPost($url, $postData);
        return $res;
    }

    /**
     * HTTP POST 请求
     */
    private function httpPost($url, $post_data = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        
        // 将dữ liệu转为 JSON 格式
        $jsonData = json_encode($post_data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        // 使用 NG API 的请求头（包含验签）
        $headers = $this->getHeaders();
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        
        \support\Log::info('NG API 请求', [
            'url' => $url,
            'headers' => $headers,
            'data' => $post_data
        ]);
        
        $contents = curl_exec($ch);
        
        // lịch sửLỗi信息
        if (curl_errno($ch)) {
            \support\Log::error('CURL 错误', [
                'error' => curl_error($ch),
                'errno' => curl_errno($ch),
                'url' => $url
            ]);
        }
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        \support\Log::info('NG API 响应', [
            'http_code' => $httpCode,
            'response' => $contents
        ]);
        
        curl_close($ch);
        return json_decode($contents, true);
    }
}

