<?php

namespace app\service;

use support\Db;
use support\Log;

/**
 * GSC+ Transaction Service
 * Manages transactions with database locking for atomic operations
 * Handles balance updates and transaction logging
 */
class GscPlusTransactionService
{
    /**
     * Create a new transaction record
     * 
     * @param array $transactionData Transaction data
     * @return int Transaction ID
     * @throws \Exception
     */
    public static function createTransaction(array $transactionData): int
    {
        try {
            $now = time();
            
            $data = [
                'transaction_id' => $transactionData['transaction_id'],
                'uid' => $transactionData['uid'],
                'action' => $transactionData['action'], // 'withdraw' or 'deposit'
                'amount' => $transactionData['amount'],
                'before_balance' => $transactionData['before_balance'] ?? 0,
                'after_balance' => $transactionData['after_balance'] ?? 0,
                'wager_code' => $transactionData['wager_code'] ?? null,
                'status' => $transactionData['status'] ?? 'completed',
                'payload' => json_encode($transactionData['payload'] ?? [], JSON_UNESCAPED_UNICODE),
                'created_at' => $now,
                'updated_at' => $now
            ];
            
            $id = Db::table('caipiao_gscplus_transactions')->insertGetId($data);
            
            Log::info('GSC+ transaction created', [
                'id' => $id,
                'transaction_id' => $data['transaction_id'],
                'uid' => $data['uid'],
                'action' => $data['action'],
                'amount' => $data['amount']
            ]);
            
            return $id;
            
        } catch (\Exception $e) {
            Log::error('Failed to create GSC+ transaction: ' . $e->getMessage(), [
                'data' => $transactionData
            ]);
            throw $e;
        }
    }
    
    /**
     * Get transaction by transaction_id (for idempotency check)
     * 
     * @param string $transactionId GSC+ transaction ID
     * @return object|null Transaction record or null
     */
    public static function getTransactionByTransactionId(string $transactionId): ?object
    {
        try {
            return Db::table('caipiao_gscplus_transactions')
                ->where('transaction_id', $transactionId)
                ->first();
                
        } catch (\Exception $e) {
            Log::error('Failed to get transaction by ID: ' . $e->getMessage(), [
                'transaction_id' => $transactionId
            ]);
            return null;
        }
    }
    
    /**
     * Update member balance with database lock (FOR UPDATE)
     * This ensures atomic balance updates in concurrent scenarios
     * 
     * @param int $uid User ID
     * @param float $amount Amount to add (positive) or subtract (negative)
     * @param string $action Action type ('withdraw' or 'deposit')
     * @return array ['success' => bool, 'before_balance' => float, 'after_balance' => float, 'error' => string|null]
     */
    public static function updateMemberBalance(int $uid, float $amount, string $action): array
    {
        Db::beginTransaction();
        
        try {
            // Lock the user row for update
            $user = Db::table('caipiao_member')
                ->where('uid', $uid)
                ->lockForUpdate()
                ->first();
            
            if (!$user) {
                Db::rollBack();
                return [
                    'success' => false,
                    'error' => 'User not found',
                    'before_balance' => 0,
                    'after_balance' => 0
                ];
            }
            
            $beforeBalance = (float)($user->balance ?? 0);
            
            // Calculate new balance
            if ($action === 'withdraw') {
                // GSC+ withdraws from our system (user plays game)
                $afterBalance = $beforeBalance - abs($amount);
                
                // Check sufficient balance
                if ($afterBalance < 0) {
                    Db::rollBack();
                    return [
                        'success' => false,
                        'error' => 'Insufficient balance',
                        'before_balance' => $beforeBalance,
                        'after_balance' => $beforeBalance
                    ];
                }
            } else {
                // GSC+ deposits to our system (user wins or refund)
                $afterBalance = $beforeBalance + abs($amount);
            }
            
            // Update balance
            Db::table('caipiao_member')
                ->where('uid', $uid)
                ->update([
                    'balance' => $afterBalance,
                    'updated_at' => time()
                ]);
            
            Db::commit();
            
            Log::info('GSC+ member balance updated', [
                'uid' => $uid,
                'action' => $action,
                'amount' => $amount,
                'before_balance' => $beforeBalance,
                'after_balance' => $afterBalance
            ]);
            
            return [
                'success' => true,
                'before_balance' => $beforeBalance,
                'after_balance' => $afterBalance,
                'error' => null
            ];
            
        } catch (\Exception $e) {
            Db::rollBack();
            Log::error('Failed to update member balance: ' . $e->getMessage(), [
                'uid' => $uid,
                'amount' => $amount,
                'action' => $action
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'before_balance' => 0,
                'after_balance' => 0
            ];
        }
    }
    
    /**
     * Get member current balance
     * 
     * @param int $uid User ID
     * @return float Current balance
     */
    public static function getMemberBalance(int $uid): float
    {
        try {
            $user = Db::table('caipiao_member')
                ->where('uid', $uid)
                ->first();
            
            return (float)($user->balance ?? 0);
            
        } catch (\Exception $e) {
            Log::error('Failed to get member balance: ' . $e->getMessage(), [
                'uid' => $uid
            ]);
            return 0;
        }
    }
    
    /**
     * Log transaction to money log table
     * 
     * @param array $transactionData Transaction data
     * @return bool Success status
     */
    public static function logTransaction(array $transactionData): bool
    {
        try {
            $now = time();
            
            // Get username
            $user = Db::table('caipiao_member')
                ->where('uid', $transactionData['uid'])
                ->first();
            
            $username = $user->username ?? 'unknown';
            
            // Determine type and typename
            $type = $transactionData['action'] === 'withdraw' ? 11 : 12; // 11=GSC+ withdraw, 12=GSC+ deposit
            $typename = $transactionData['action'] === 'withdraw' ? 'GSC+ Rút tiền' : 'GSC+ Nạp tiền';
            
            $logData = [
                'uid' => $transactionData['uid'],
                'username' => $username,
                'type' => $type,
                'typename' => $typename,
                'trano' => $transactionData['transaction_id'],
                'amount' => $transactionData['action'] === 'withdraw' ? -abs($transactionData['amount']) : abs($transactionData['amount']),
                'before' => $transactionData['before_balance'],
                'after' => $transactionData['after_balance'],
                'remark' => $transactionData['remark'] ?? "GSC+ {$transactionData['action']} - {$transactionData['transaction_id']}",
                'addtime' => $now,
                'ip' => $transactionData['ip'] ?? ''
            ];
            
            Db::table('caipiao_moneylog')->insert($logData);
            
            Log::info('GSC+ transaction logged to moneylog', [
                'uid' => $transactionData['uid'],
                'transaction_id' => $transactionData['transaction_id']
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to log GSC+ transaction: ' . $e->getMessage(), [
                'data' => $transactionData
            ]);
            return false;
        }
    }
    
    /**
     * Process complete transaction (update balance + create record + log)
     * This is the main method for handling GSC+ callbacks
     * 
     * @param array $params Transaction parameters
     * @return array ['success' => bool, 'transaction_id' => int|null, 'balance' => float, 'error' => string|null]
     */
    public static function processTransaction(array $params): array
    {
        $transactionId = $params['transaction_id'];
        $uid = $params['uid'];
        $amount = $params['amount'];
        $action = $params['action'];
        
        try {
            // Check for duplicate transaction (idempotency)
            $existing = self::getTransactionByTransactionId($transactionId);
            if ($existing) {
                Log::warning('Duplicate GSC+ transaction detected', [
                    'transaction_id' => $transactionId,
                    'uid' => $uid
                ]);
                
                return [
                    'success' => true,
                    'transaction_id' => $existing->id,
                    'balance' => (float)$existing->after_balance,
                    'error' => null,
                    'duplicate' => true
                ];
            }
            
            // Update member balance with lock
            $balanceResult = self::updateMemberBalance($uid, $amount, $action);
            
            if (!$balanceResult['success']) {
                return [
                    'success' => false,
                    'transaction_id' => null,
                    'balance' => $balanceResult['before_balance'],
                    'error' => $balanceResult['error']
                ];
            }
            
            // Create transaction record
            $transactionData = [
                'transaction_id' => $transactionId,
                'uid' => $uid,
                'action' => $action,
                'amount' => $amount,
                'before_balance' => $balanceResult['before_balance'],
                'after_balance' => $balanceResult['after_balance'],
                'wager_code' => $params['wager_code'] ?? null,
                'status' => 'completed',
                'payload' => $params
            ];
            
            $recordId = self::createTransaction($transactionData);
            
            // Log to money log
            $transactionData['ip'] = $params['ip'] ?? '';
            $transactionData['remark'] = $params['remark'] ?? null;
            self::logTransaction($transactionData);
            
            return [
                'success' => true,
                'transaction_id' => $recordId,
                'balance' => $balanceResult['after_balance'],
                'error' => null
            ];
            
        } catch (\Exception $e) {
            Log::error('Failed to process GSC+ transaction: ' . $e->getMessage(), [
                'params' => $params
            ]);
            
            return [
                'success' => false,
                'transaction_id' => null,
                'balance' => self::getMemberBalance($uid),
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get transaction history for a user
     * 
     * @param int $uid User ID
     * @param array $filters Optional filters (start_time, end_time, action, limit, offset)
     * @return array Transaction records
     */
    public static function getTransactionHistory(int $uid, array $filters = []): array
    {
        try {
            $query = Db::table('caipiao_gscplus_transactions')
                ->where('uid', $uid);
            
            if (isset($filters['action'])) {
                $query->where('action', $filters['action']);
            }
            
            if (isset($filters['start_time'])) {
                $query->where('created_at', '>=', $filters['start_time']);
            }
            
            if (isset($filters['end_time'])) {
                $query->where('created_at', '<=', $filters['end_time']);
            }
            
            $query->orderBy('created_at', 'desc');
            
            if (isset($filters['limit'])) {
                $query->limit($filters['limit']);
            }
            
            if (isset($filters['offset'])) {
                $query->offset($filters['offset']);
            }
            
            return $query->get()->toArray();
            
        } catch (\Exception $e) {
            Log::error('Failed to get transaction history: ' . $e->getMessage(), [
                'uid' => $uid,
                'filters' => $filters
            ]);
            return [];
        }
    }
}