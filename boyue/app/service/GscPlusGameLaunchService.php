<?php

namespace app\service;

use app\constants\GameCategoryMapping;
use support\Log;

/**
 * GSC+ Game Launch Service
 * Handles game launching via GSC+ API
 */
class GscPlusGameLaunchService
{
    private $config;
    private $apiUrl;
    private $operatorCode;
    private $secretKey;

    public function __construct()
    {
        $configService = new GscPlusConfigService();
        $this->config = $configService->getConfig();
        
        if (!$this->config) {
            throw new \Exception('GSC+ configuration not found');
        }

        $this->apiUrl = rtrim($this->config->api_url, '/');
        $this->operatorCode = $this->config->operator_code;
        $this->secretKey = $this->config->secret_key;
    }

    /**
     * Launch game and get game URL
     * 
     * @param array $params Launch parameters
     * @return array Response with game URL
     */
    public function launchGame(array $params): array
    {
        // Validate required parameters
        $this->validateLaunchParams($params);

        // Get product code from platform
        $gameType = $params['game_type'];
        $platform = $params['platform'];
        $productCode = GameCategoryMapping::getProductCode($gameType, $platform);

        if (!$productCode) {
            throw new \Exception("Platform {$platform} not supported for game type {$gameType}");
        }

        // Prepare request data
        $requestTime = time();
        $requestData = [
            'operator_code' => $this->operatorCode,
            'member_account' => $params['member_account'],
            'password' => md5($params['password'] ?? $params['member_account']), // Use MD5 hashed password
            'nickname' => $params['nickname'] ?? $params['member_account'],
            'currency' => $params['currency'] ?? $this->config->currency,
            'game_code' => $params['game_code'] ?? null,
            'product_code' => $productCode,
            'game_type' => $gameType,
            'language_code' => $params['language_code'] ?? 0,
            'ip' => $params['ip'] ?? $this->getClientIp(),
            'platform' => $params['platform_type'] ?? 'WEB',
            'request_time' => $requestTime,
            'operator_lobby_url' => $params['operator_lobby_url'] ?? $this->getOperatorLobbyUrl(),
            'sign' => $this->generateLaunchSignature($requestTime)
        ];

        // Add optional SABA sports parameters if applicable
        if (isset($params['widget_id'])) {
            $requestData['widget_id'] = $params['widget_id'];
        }
        if (isset($params['is_widget_login'])) {
            $requestData['is_widget_login'] = $params['is_widget_login'];
        }
        if (isset($params['event_id'])) {
            $requestData['event_id'] = $params['event_id'];
        }
        if (isset($params['is_streaming_login'])) {
            $requestData['is_streaming_login'] = $params['is_streaming_login'];
        }

        // Call GSC+ API
        $response = $this->callGscPlusApi('/api/operators/launch-game', $requestData);

        return $response;
    }

    /**
     * Get available games list for a platform
     * 
     * @param string $platform Platform code
     * @param string $gameType Game type
     * @return array Games list
     */
    public function getGamesList(string $platform, string $gameType): array
    {
        $productCode = GameCategoryMapping::getProductCode($gameType, $platform);
        
        if (!$productCode) {
            throw new \Exception("Platform {$platform} not supported for game type {$gameType}");
        }

        $requestTime = time();
        $sign = $this->generateSignature($requestTime, 'gamelist');

        $params = [
            'product_code' => $productCode,
            'operator_code' => $this->operatorCode,
            'game_type' => $gameType,
            'sign' => $sign,
            'request_time' => $requestTime
        ];

        $url = $this->apiUrl . '/api/operators/provider-games?' . http_build_query($params);
        
        $response = $this->makeHttpRequest('GET', $url);

        return $response;
    }

    /**
     * Get available products list
     * 
     * @return array Products list
     */
    public function getProductsList(): array
    {
        $requestTime = time();
        $sign = $this->generateSignature($requestTime, 'productlist');

        $params = [
            'operator_code' => $this->operatorCode,
            'sign' => $sign,
            'request_time' => $requestTime
        ];

        $url = $this->apiUrl . '/api/operators/available-products?' . http_build_query($params);
        
        $response = $this->makeHttpRequest('GET', $url);

        return $response;
    }

    /**
     * Validate launch parameters
     */
    private function validateLaunchParams(array $params): void
    {
        $required = ['member_account', 'game_type', 'platform'];
        
        foreach ($required as $field) {
            if (empty($params[$field])) {
                throw new \Exception("Missing required parameter: {$field}");
            }
        }

        // Validate game type
        $gameType = strtoupper($params['game_type']);
        if (!in_array($gameType, array_values(GameCategoryMapping::CATEGORY_MAPPING))) {
            throw new \Exception("Invalid game type: {$params['game_type']}");
        }

        // Validate platform support
        $platform = strtoupper($params['platform']);
        if (!GameCategoryMapping::isPlatformSupported($gameType, $platform)) {
            throw new \Exception("Platform {$platform} not supported for game type {$gameType}");
        }
    }

    /**
     * Generate signature for launch game
     */
    private function generateLaunchSignature(int $requestTime): string
    {
        return md5($requestTime . $this->secretKey . 'launchgame' . $this->operatorCode);
    }

    /**
     * Generate signature for other APIs
     */
    private function generateSignature(int $requestTime, string $action): string
    {
        return md5($requestTime . $this->secretKey . $action . $this->operatorCode);
    }

    /**
     * Call GSC+ API
     */
    private function callGscPlusApi(string $endpoint, array $data): array
    {
        $url = $this->apiUrl . $endpoint;
        
        return $this->makeHttpRequest('POST', $url, $data);
    }

    /**
     * Make HTTP request
     */
    private function makeHttpRequest(string $method, string $url, ?array $data = null): array
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        if ($method === 'POST' && $data) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json'
            ]);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            Log::error('GSC+ API request failed', [
                'url' => $url,
                'error' => $error
            ]);
            throw new \Exception("API request failed: {$error}");
        }

        if ($httpCode !== 200) {
            Log::error('GSC+ API returned error', [
                'url' => $url,
                'http_code' => $httpCode,
                'response' => $response
            ]);
            throw new \Exception("API returned error code: {$httpCode}");
        }

        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Invalid JSON response from API");
        }

        return $result;
    }

    /**
     * Get client IP address
     */
    private function getClientIp(): string
    {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ??
              $_SERVER['HTTP_X_REAL_IP'] ??
              $_SERVER['REMOTE_ADDR'] ??
              '0.0.0.0';
        
        // Get first IP if multiple IPs in X-Forwarded-For
        if (strpos($ip, ',') !== false) {
            $ip = trim(explode(',', $ip)[0]);
        }
        
        return $ip;
    }

    /**
     * Get operator lobby URL
     */
    private function getOperatorLobbyUrl(): string
    {
        // Get from config or use default
        return $this->config->callback_url ?? 'http://localhost:8788';
    }
}