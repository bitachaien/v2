<?php

namespace app\controller\api;

use support\Request;
use support\Response;
use support\Db;
use support\Log;
use app\service\GscPlusConfigService;
use app\service\GscPlusSignatureService;
use app\service\GscPlusTransactionService;
use app\service\GscPlusBetDataService;

/**
 * GSC+ Seamless Wallet Callback Controller
 * Handles 4 callback endpoints from GSC+ API:
 * - balance: Query player balance
 * - withdraw: Deduct balance for bets/tips
 * - deposit: Add balance for wins/bonuses
 * - pushBetData: Receive bet data (no balance change)
 */
class GscPlusCallbackController
{
    /**
     * Balance callback - Query player balance
     * POST /api/gscplus/seamless/balance
     * 
     * @param Request $request
     * @return Response
     */
    public function balance(Request $request): Response
    {
        $startTime = microtime(true);
        
        try {
            // Get request data
            $data = $request->post();
            
            Log::info('GSC+ balance callback received', [
                'data' => $data,
                'ip' => $request->getRealIp()
            ]);
            
            // Validate signature
            $secretKey = GscPlusConfigService::getSecretKey();
            if (!$secretKey) {
                return $this->errorResponse(9999, 'System configuration error');
            }
            
            $verification = GscPlusSignatureService::verifyCallback($data, $secretKey);
            if (!$verification['valid']) {
                Log::warning('GSC+ balance signature verification failed', [
                    'error' => $verification['error'],
                    'data' => $data
                ]);
                return $this->errorResponse(1001, 'Invalid signature');
            }
            
            // Validate required parameters
            $operatorCode = $data['operator_code'] ?? null;
            $memberAccounts = $data['member_accounts'] ?? [];
            
            if (!$operatorCode || empty($memberAccounts) || !is_array($memberAccounts)) {
                return $this->errorResponse(1002, 'Invalid parameters');
            }
            
            // Verify operator code
            $configOperatorCode = GscPlusConfigService::getOperatorCode();
            if ($operatorCode !== $configOperatorCode) {
                return $this->errorResponse(1002, 'Invalid operator code');
            }
            
            // Get balances for all member accounts
            $balances = [];
            $currency = GscPlusConfigService::getCurrency();
            
            foreach ($memberAccounts as $memberAccount) {
                // Get user by username
                $user = Db::table('caipiao_member')
                    ->where('username', $memberAccount)
                    ->first();
                
                if (!$user) {
                    // Member not found - return 0 balance
                    $balances[] = [
                        'member_account' => $memberAccount,
                        'balance' => 0,
                        'currency' => $currency
                    ];
                    continue;
                }
                
                $balance = (float)($user->balance ?? 0);
                
                $balances[] = [
                    'member_account' => $memberAccount,
                    'balance' => $balance,
                    'currency' => $currency
                ];
            }
            
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::info('GSC+ balance callback completed', [
                'member_accounts' => $memberAccounts,
                'balances_count' => count($balances),
                'duration_ms' => $duration
            ]);
            
            return $this->successResponse([
                'balances' => $balances
            ]);
            
        } catch (\Exception $e) {
            Log::error('GSC+ balance callback error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return $this->errorResponse(9999, 'System error');
        }
    }
    
    /**
     * Withdraw callback - Deduct balance for bets/tips
     * POST /api/gscplus/seamless/withdraw
     * 
     * @param Request $request
     * @return Response
     */
    public function withdraw(Request $request): Response
    {
        $startTime = microtime(true);
        
        try {
            // Get request data
            $data = $request->post();
            
            Log::info('GSC+ withdraw callback received', [
                'data' => $data,
                'ip' => $request->getRealIp()
            ]);
            
            // Validate signature
            $secretKey = GscPlusConfigService::getSecretKey();
            if (!$secretKey) {
                return $this->errorResponse(9999, 'System configuration error');
            }
            
            $verification = GscPlusSignatureService::verifyCallback($data, $secretKey);
            if (!$verification['valid']) {
                Log::warning('GSC+ withdraw signature verification failed', [
                    'error' => $verification['error'],
                    'data' => $data
                ]);
                return $this->errorResponse(1001, 'Invalid signature');
            }
            
            // Validate required parameters
            $operatorCode = $data['operator_code'] ?? null;
            $transactions = $data['transactions'] ?? [];
            
            if (!$operatorCode || empty($transactions) || !is_array($transactions)) {
                return $this->errorResponse(1002, 'Invalid parameters');
            }
            
            // Verify operator code
            $configOperatorCode = GscPlusConfigService::getOperatorCode();
            if ($operatorCode !== $configOperatorCode) {
                return $this->errorResponse(1002, 'Invalid operator code');
            }
            
            // Process each transaction
            $results = [];
            $currency = GscPlusConfigService::getCurrency();
            
            foreach ($transactions as $transaction) {
                $transactionId = $transaction['transaction_id'] ?? null;
                $memberAccount = $transaction['member_account'] ?? null;
                $amount = $transaction['amount'] ?? 0;
                $wagerCode = $transaction['wager_code'] ?? null;
                
                if (!$transactionId || !$memberAccount || $amount <= 0) {
                    $results[] = [
                        'transaction_id' => $transactionId,
                        'member_account' => $memberAccount,
                        'error_code' => 1002,
                        'message' => 'Invalid transaction parameters'
                    ];
                    continue;
                }
                
                // Get user by username
                $user = Db::table('caipiao_member')
                    ->where('username', $memberAccount)
                    ->first();
                
                if (!$user) {
                    $results[] = [
                        'transaction_id' => $transactionId,
                        'member_account' => $memberAccount,
                        'error_code' => 1003,
                        'message' => 'Member not found'
                    ];
                    continue;
                }
                
                // Process transaction
                $transactionResult = GscPlusTransactionService::processTransaction([
                    'transaction_id' => $transactionId,
                    'uid' => $user->uid,
                    'amount' => $amount,
                    'action' => 'withdraw',
                    'wager_code' => $wagerCode,
                    'ip' => $request->getRealIp(),
                    'payload' => $transaction
                ]);
                
                if (!$transactionResult['success']) {
                    // Check if it's a duplicate transaction
                    if (isset($transactionResult['duplicate']) && $transactionResult['duplicate']) {
                        // Return success for duplicate (idempotency)
                        $results[] = [
                            'transaction_id' => $transactionId,
                            'member_account' => $memberAccount,
                            'balance' => $transactionResult['balance'],
                            'currency' => $currency
                        ];
                    } else {
                        // Determine error code
                        $errorCode = 9999;
                        if (strpos($transactionResult['error'], 'Insufficient balance') !== false) {
                            $errorCode = 1004;
                        }
                        
                        $results[] = [
                            'transaction_id' => $transactionId,
                            'member_account' => $memberAccount,
                            'error_code' => $errorCode,
                            'message' => $transactionResult['error']
                        ];
                    }
                    continue;
                }
                
                $results[] = [
                    'transaction_id' => $transactionId,
                    'member_account' => $memberAccount,
                    'balance' => $transactionResult['balance'],
                    'currency' => $currency
                ];
            }
            
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::info('GSC+ withdraw callback completed', [
                'transactions_count' => count($transactions),
                'results_count' => count($results),
                'duration_ms' => $duration
            ]);
            
            return $this->successResponse([
                'transactions' => $results
            ]);
            
        } catch (\Exception $e) {
            Log::error('GSC+ withdraw callback error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return $this->errorResponse(9999, 'System error');
        }
    }
    
    /**
     * Deposit callback - Add balance for wins/bonuses
     * POST /api/gscplus/seamless/deposit
     * 
     * @param Request $request
     * @return Response
     */
    public function deposit(Request $request): Response
    {
        $startTime = microtime(true);
        
        try {
            // Get request data
            $data = $request->post();
            
            Log::info('GSC+ deposit callback received', [
                'data' => $data,
                'ip' => $request->getRealIp()
            ]);
            
            // Validate signature
            $secretKey = GscPlusConfigService::getSecretKey();
            if (!$secretKey) {
                return $this->errorResponse(9999, 'System configuration error');
            }
            
            $verification = GscPlusSignatureService::verifyCallback($data, $secretKey);
            if (!$verification['valid']) {
                Log::warning('GSC+ deposit signature verification failed', [
                    'error' => $verification['error'],
                    'data' => $data
                ]);
                return $this->errorResponse(1001, 'Invalid signature');
            }
            
            // Validate required parameters
            $operatorCode = $data['operator_code'] ?? null;
            $transactions = $data['transactions'] ?? [];
            
            if (!$operatorCode || empty($transactions) || !is_array($transactions)) {
                return $this->errorResponse(1002, 'Invalid parameters');
            }
            
            // Verify operator code
            $configOperatorCode = GscPlusConfigService::getOperatorCode();
            if ($operatorCode !== $configOperatorCode) {
                return $this->errorResponse(1002, 'Invalid operator code');
            }
            
            // Process each transaction
            $results = [];
            $currency = GscPlusConfigService::getCurrency();
            
            foreach ($transactions as $transaction) {
                $transactionId = $transaction['transaction_id'] ?? null;
                $memberAccount = $transaction['member_account'] ?? null;
                $amount = $transaction['amount'] ?? 0;
                $wagerCode = $transaction['wager_code'] ?? null;
                $productCode = $transaction['product_code'] ?? null;
                
                if (!$transactionId || !$memberAccount || $amount < 0) {
                    $results[] = [
                        'transaction_id' => $transactionId,
                        'member_account' => $memberAccount,
                        'error_code' => 1002,
                        'message' => 'Invalid transaction parameters'
                    ];
                    continue;
                }
                
                // Get user by username
                $user = Db::table('caipiao_member')
                    ->where('username', $memberAccount)
                    ->first();
                
                if (!$user) {
                    $results[] = [
                        'transaction_id' => $transactionId,
                        'member_account' => $memberAccount,
                        'error_code' => 1003,
                        'message' => 'Member not found'
                    ];
                    continue;
                }
                
                // Special handling for WBET product (manual settlement)
                // For WBET, amount might be 0 for initial deposit, actual prize comes later
                if ($productCode === 'WBET' && $amount == 0) {
                    Log::info('GSC+ WBET manual settlement - zero amount deposit', [
                        'transaction_id' => $transactionId,
                        'member_account' => $memberAccount,
                        'wager_code' => $wagerCode
                    ]);
                }
                
                // Process transaction
                $transactionResult = GscPlusTransactionService::processTransaction([
                    'transaction_id' => $transactionId,
                    'uid' => $user->uid,
                    'amount' => $amount,
                    'action' => 'deposit',
                    'wager_code' => $wagerCode,
                    'ip' => $request->getRealIp(),
                    'payload' => $transaction
                ]);
                
                if (!$transactionResult['success']) {
                    // Check if it's a duplicate transaction
                    if (isset($transactionResult['duplicate']) && $transactionResult['duplicate']) {
                        // Return success for duplicate (idempotency)
                        $results[] = [
                            'transaction_id' => $transactionId,
                            'member_account' => $memberAccount,
                            'balance' => $transactionResult['balance'],
                            'currency' => $currency
                        ];
                    } else {
                        $results[] = [
                            'transaction_id' => $transactionId,
                            'member_account' => $memberAccount,
                            'error_code' => 9999,
                            'message' => $transactionResult['error']
                        ];
                    }
                    continue;
                }
                
                $results[] = [
                    'transaction_id' => $transactionId,
                    'member_account' => $memberAccount,
                    'balance' => $transactionResult['balance'],
                    'currency' => $currency
                ];
            }
            
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::info('GSC+ deposit callback completed', [
                'transactions_count' => count($transactions),
                'results_count' => count($results),
                'duration_ms' => $duration
            ]);
            
            return $this->successResponse([
                'transactions' => $results
            ]);
            
        } catch (\Exception $e) {
            Log::error('GSC+ deposit callback error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return $this->errorResponse(9999, 'System error');
        }
    }
    
    /**
     * Push bet data callback - Receive bet data (no balance change)
     * POST /api/gscplus/seamless/pushbetdata
     * 
     * @param Request $request
     * @return Response
     */
    public function pushBetData(Request $request): Response
    {
        $startTime = microtime(true);
        
        try {
            // Get request data
            $data = $request->post();
            
            Log::info('GSC+ pushBetData callback received', [
                'data' => $data,
                'ip' => $request->getRealIp()
            ]);
            
            // Validate signature
            $secretKey = GscPlusConfigService::getSecretKey();
            if (!$secretKey) {
                return $this->errorResponse(9999, 'System configuration error');
            }
            
            $verification = GscPlusSignatureService::verifyCallback($data, $secretKey);
            if (!$verification['valid']) {
                Log::warning('GSC+ pushBetData signature verification failed', [
                    'error' => $verification['error'],
                    'data' => $data
                ]);
                return $this->errorResponse(1001, 'Invalid signature');
            }
            
            // Validate required parameters
            $operatorCode = $data['operator_code'] ?? null;
            $wagers = $data['wagers'] ?? [];
            
            if (!$operatorCode || empty($wagers) || !is_array($wagers)) {
                return $this->errorResponse(1002, 'Invalid parameters');
            }
            
            // Verify operator code
            $configOperatorCode = GscPlusConfigService::getOperatorCode();
            if ($operatorCode !== $configOperatorCode) {
                return $this->errorResponse(1002, 'Invalid operator code');
            }
            
            // Process each wager
            $successCount = 0;
            $failCount = 0;
            
            foreach ($wagers as $wager) {
                $wagerCode = $wager['wager_code'] ?? null;
                $memberAccount = $wager['member_account'] ?? null;
                
                if (!$wagerCode || !$memberAccount) {
                    $failCount++;
                    Log::warning('GSC+ pushBetData invalid wager data', [
                        'wager' => $wager
                    ]);
                    continue;
                }
                
                // Get user by username
                $user = Db::table('caipiao_member')
                    ->where('username', $memberAccount)
                    ->first();
                
                if (!$user) {
                    $failCount++;
                    Log::warning('GSC+ pushBetData member not found', [
                        'member_account' => $memberAccount,
                        'wager_code' => $wagerCode
                    ]);
                    continue;
                }
                
                // Prepare bet data
                $betData = [
                    'wagerCode' => $wagerCode,
                    'uid' => $user->uid,
                    'operatorCode' => $wager['operator_code'] ?? $operatorCode,
                    'productCode' => $wager['product_code'] ?? null,
                    'gameCode' => $wager['game_code'] ?? null,
                    'gameName' => $wager['game_name'] ?? null,
                    'betAmount' => $wager['bet_amount'] ?? 0,
                    'validBetAmount' => $wager['valid_bet_amount'] ?? 0,
                    'prizeAmount' => $wager['prize_amount'] ?? 0,
                    'wagerStatus' => $wager['wager_status'] ?? 'pending',
                    'betTime' => $wager['bet_time'] ?? null,
                    'settledTime' => $wager['settled_time'] ?? null,
                ];
                
                // Process bet data
                $result = GscPlusBetDataService::processBetData($betData);
                
                if ($result['success']) {
                    $successCount++;
                } else {
                    $failCount++;
                    Log::error('GSC+ pushBetData failed to process bet', [
                        'wager_code' => $wagerCode,
                        'error' => $result['error']
                    ]);
                }
            }
            
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::info('GSC+ pushBetData callback completed', [
                'wagers_count' => count($wagers),
                'success_count' => $successCount,
                'fail_count' => $failCount,
                'duration_ms' => $duration
            ]);
            
            return $this->successResponse();
            
        } catch (\Exception $e) {
            Log::error('GSC+ pushBetData callback error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return $this->errorResponse(9999, 'System error');
        }
    }
    
    /**
     * Health check endpoint - Check API service status
     * GET /api/v1/gscplus/health
     *
     * @param Request $request
     * @return Response
     */
    public function health(Request $request): Response
    {
        return json([
            'status' => 'ok',
            'service' => 'GSC+ Seamless Wallet API',
            'version' => '1.0.0',
            'timestamp' => time(),
            'server_time' => date('Y-m-d H:i:s'),
            'endpoints' => [
                'balance' => '/api/v1/gscplus/seamless/balance',
                'withdraw' => '/api/v1/gscplus/seamless/withdraw',
                'deposit' => '/api/v1/gscplus/seamless/deposit',
                'pushbetdata' => '/api/v1/gscplus/seamless/pushbetdata'
            ]
        ]);
    }
    
    /**
     * Test endpoint - Test signature generation and validation
     * GET /api/v1/gscplus/test
     *
     * @param Request $request
     * @return Response
     */
    public function test(Request $request): Response
    {
        try {
            $configService = new GscPlusConfigService();
            $config = $configService->getConfig();
            
            if (!$config) {
                return json([
                    'error' => 'GSC+ configuration not found',
                    'message' => 'Please run database migration and configure GSC+ settings',
                    'steps' => [
                        '1. Run: ./deploy_gscplus.sh',
                        '2. Update caipiao_gscplus_config table',
                        '3. Restart: cd boyue && php start.php restart'
                    ]
                ], 500);
            }
            
            // Validate config has required fields
            if (!$config->operator_code || !$config->secret_key) {
                return json([
                    'error' => 'Invalid configuration',
                    'message' => 'operator_code and secret_key are required',
                    'current_config' => [
                        'has_operator_code' => !empty($config->operator_code),
                        'has_secret_key' => !empty($config->secret_key),
                        'has_api_url' => !empty($config->api_url),
                        'has_callback_url' => !empty($config->callback_url)
                    ],
                    'fix' => 'Run: ./update_gscplus_config.sh'
                ], 500);
            }
            
            $secretKey = $config->secret_key;
            $operatorCode = $config->operator_code;
            $currency = $config->currency ?? 'VND2';
            
            // Sample balance request
            $balanceRequest = [
                'operator_code' => $operatorCode,
                'member_accounts' => ['testuser123'],
                'timestamp' => time()
            ];
            
            // Generate signature for balance request
            $balanceSignature = GscPlusSignatureService::generateSignature($balanceRequest, $secretKey);
            $balanceRequest['sign'] = $balanceSignature;
            
            // Sample withdraw transaction
            $withdrawRequest = [
                'operator_code' => $operatorCode,
                'transactions' => [
                    [
                        'transaction_id' => 'TEST_' . time() . '_001',
                        'member_account' => 'testuser123',
                        'amount' => 100.00,
                        'wager_code' => 'WAGER_' . time(),
                        'product_code' => 'SLOT',
                        'game_code' => 'SG001'
                    ]
                ],
                'timestamp' => time()
            ];
            
            // Generate signature for withdraw request
            $withdrawSignature = GscPlusSignatureService::generateSignature($withdrawRequest, $secretKey);
            $withdrawRequest['sign'] = $withdrawSignature;
            
            return json([
                'status' => 'ok',
                'message' => 'Test data generated successfully',
                'config' => [
                    'operator_code' => $operatorCode,
                    'currency' => $currency,
                    'secret_key_length' => strlen($secretKey),
                    'api_url' => $config->api_url ?? null,
                    'callback_url' => $config->callback_url ?? null
                ],
                'sample_requests' => [
                    'balance' => $balanceRequest,
                    'withdraw' => $withdrawRequest
                ],
                'notes' => [
                    'Use these sample requests to test the API endpoints',
                    'Replace member_account with actual username',
                    'Signatures are pre-generated and valid',
                    'POST to respective endpoints with these payloads'
                ]
            ]);
            
        } catch (\Exception $e) {
            return json([
                'error' => 'Configuration error',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
    
    /**
     * Config endpoint - Check GSC+ configuration status
     * GET /api/v1/gscplus/config
     *
     * @param Request $request
     * @return Response
     */
    public function config(Request $request): Response
    {
        try {
            $configService = new GscPlusConfigService();
            $config = $configService->getConfig();
            
            return json([
                'configured' => $config ? true : false,
                'operator_code' => $config ? $config->operator_code : null,
                'api_url' => $config ? $config->api_url : null,
                'callback_url' => $config ? $config->callback_url : null,
                'currency' => $config ? $config->currency : null,
                'status' => $config ? ($config->status == 'active' ? 'active' : 'inactive') : 'not_configured',
                'has_secret_key' => $config && $config->secret_key ? true : false,
                'validation' => $config ? GscPlusConfigService::validateConfig() : ['valid' => false, 'errors' => ['Configuration not found']]
            ]);
        } catch (\Exception $e) {
            return json([
                'error' => 'Failed to load configuration',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Return success response
     * 
     * @param array|null $data Response data
     * @return Response
     */
    private function successResponse(?array $data = null): Response
    {
        $response = [
            'error_code' => 0,
            'message' => 'Success'
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        return json($response);
    }
    
    /**
     * Return error response
     * 
     * @param int $errorCode Error code
     * @param string $message Error message
     * @return Response
     */
    private function errorResponse(int $errorCode, string $message): Response
    {
        return json([
            'error_code' => $errorCode,
            'message' => $message
        ]);
    }
}