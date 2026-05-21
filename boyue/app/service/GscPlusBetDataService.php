<?php

namespace app\service;

use support\Db;
use support\Log;

/**
 * GSC+ Bet Data Service
 * Manages bet data from pushbetdata callback
 * Handles bet creation, updates, and history queries
 */
class GscPlusBetDataService
{
    /**
     * Save bet data from pushbetdata callback
     * 
     * @param array $betData Bet data from GSC+
     * @return int Bet record ID
     * @throws \Exception
     */
    public static function saveBetData(array $betData): int
    {
        try {
            $now = time();
            
            $data = [
                'wager_code' => $betData['wagerCode'],
                'uid' => $betData['uid'],
                'operator_code' => $betData['operatorCode'] ?? null,
                'product_code' => $betData['productCode'] ?? null,
                'game_code' => $betData['gameCode'] ?? null,
                'game_name' => $betData['gameName'] ?? null,
                'bet_amount' => $betData['betAmount'] ?? 0,
                'valid_bet_amount' => $betData['validBetAmount'] ?? 0,
                'prize_amount' => $betData['prizeAmount'] ?? 0,
                'wager_status' => $betData['wagerStatus'] ?? 'pending',
                'bet_time' => isset($betData['betTime']) ? (int)($betData['betTime'] / 1000) : $now,
                'settled_at' => isset($betData['settledTime']) && $betData['settledTime'] > 0 ? (int)($betData['settledTime'] / 1000) : null,
                'payload' => json_encode($betData, JSON_UNESCAPED_UNICODE),
                'created_at' => $now,
                'updated_at' => $now
            ];
            
            $id = Db::table('caipiao_gscplus_bet_data')->insertGetId($data);
            
            Log::info('GSC+ bet data saved', [
                'id' => $id,
                'wager_code' => $data['wager_code'],
                'uid' => $data['uid'],
                'bet_amount' => $data['bet_amount'],
                'status' => $data['wager_status']
            ]);
            
            return $id;
            
        } catch (\Exception $e) {
            Log::error('Failed to save GSC+ bet data: ' . $e->getMessage(), [
                'data' => $betData
            ]);
            throw $e;
        }
    }
    
    /**
     * Update bet data (for settlement or status changes)
     * 
     * @param string $wagerCode Wager code to update
     * @param array $updateData Data to update
     * @return bool Success status
     */
    public static function updateBetData(string $wagerCode, array $updateData): bool
    {
        try {
            $data = [];
            
            // Map update fields
            if (isset($updateData['prizeAmount'])) {
                $data['prize_amount'] = $updateData['prizeAmount'];
            }
            
            if (isset($updateData['wagerStatus'])) {
                $data['wager_status'] = $updateData['wagerStatus'];
            }
            
            if (isset($updateData['settledTime']) && $updateData['settledTime'] > 0) {
                $data['settled_at'] = (int)($updateData['settledTime'] / 1000);
            }
            
            if (isset($updateData['validBetAmount'])) {
                $data['valid_bet_amount'] = $updateData['validBetAmount'];
            }
            
            // Update payload if provided
            if (isset($updateData['payload'])) {
                $data['payload'] = json_encode($updateData['payload'], JSON_UNESCAPED_UNICODE);
            }
            
            $data['updated_at'] = time();
            
            $affected = Db::table('caipiao_gscplus_bet_data')
                ->where('wager_code', $wagerCode)
                ->update($data);
            
            Log::info('GSC+ bet data updated', [
                'wager_code' => $wagerCode,
                'affected_rows' => $affected,
                'update_data' => $data
            ]);
            
            return $affected > 0;
            
        } catch (\Exception $e) {
            Log::error('Failed to update GSC+ bet data: ' . $e->getMessage(), [
                'wager_code' => $wagerCode,
                'update_data' => $updateData
            ]);
            return false;
        }
    }
    
    /**
     * Get bet by wager code
     * 
     * @param string $wagerCode Wager code
     * @return object|null Bet record or null
     */
    public static function getBetByWagerCode(string $wagerCode): ?object
    {
        try {
            return Db::table('caipiao_gscplus_bet_data')
                ->where('wager_code', $wagerCode)
                ->first();
                
        } catch (\Exception $e) {
            Log::error('Failed to get bet by wager code: ' . $e->getMessage(), [
                'wager_code' => $wagerCode
            ]);
            return null;
        }
    }
    
    /**
     * Get bet history for a user
     * 
     * @param int $uid User ID
     * @param array $filters Optional filters
     * @return array Bet records
     */
    public static function getBetHistory(int $uid, array $filters = []): array
    {
        try {
            $query = Db::table('caipiao_gscplus_bet_data')
                ->where('uid', $uid);
            
            // Filter by product code
            if (isset($filters['product_code'])) {
                $query->where('product_code', $filters['product_code']);
            }
            
            // Filter by game code
            if (isset($filters['game_code'])) {
                $query->where('game_code', $filters['game_code']);
            }
            
            // Filter by wager status
            if (isset($filters['wager_status'])) {
                $query->where('wager_status', $filters['wager_status']);
            }
            
            // Filter by bet time range
            if (isset($filters['start_time'])) {
                $query->where('bet_time', '>=', $filters['start_time']);
            }
            
            if (isset($filters['end_time'])) {
                $query->where('bet_time', '<=', $filters['end_time']);
            }
            
            // Filter by settled time range
            if (isset($filters['settled_start'])) {
                $query->where('settled_at', '>=', $filters['settled_start']);
            }
            
            if (isset($filters['settled_end'])) {
                $query->where('settled_at', '<=', $filters['settled_end']);
            }
            
            // Order by
            $orderBy = $filters['order_by'] ?? 'bet_time';
            $orderDir = $filters['order_dir'] ?? 'desc';
            $query->orderBy($orderBy, $orderDir);
            
            // Pagination
            if (isset($filters['limit'])) {
                $query->limit($filters['limit']);
            }
            
            if (isset($filters['offset'])) {
                $query->offset($filters['offset']);
            }
            
            return $query->get()->toArray();
            
        } catch (\Exception $e) {
            Log::error('Failed to get bet history: ' . $e->getMessage(), [
                'uid' => $uid,
                'filters' => $filters
            ]);
            return [];
        }
    }
    
    /**
     * Get bet statistics for a user
     * 
     * @param int $uid User ID
     * @param array $filters Optional filters (start_time, end_time, product_code)
     * @return array Statistics data
     */
    public static function getBetStatistics(int $uid, array $filters = []): array
    {
        try {
            $query = Db::table('caipiao_gscplus_bet_data')
                ->where('uid', $uid);
            
            if (isset($filters['product_code'])) {
                $query->where('product_code', $filters['product_code']);
            }
            
            if (isset($filters['start_time'])) {
                $query->where('bet_time', '>=', $filters['start_time']);
            }
            
            if (isset($filters['end_time'])) {
                $query->where('bet_time', '<=', $filters['end_time']);
            }
            
            $stats = $query->selectRaw('
                COUNT(*) as total_bets,
                SUM(bet_amount) as total_bet_amount,
                SUM(valid_bet_amount) as total_valid_bet_amount,
                SUM(prize_amount) as total_prize_amount,
                SUM(CASE WHEN wager_status = "settled" THEN 1 ELSE 0 END) as settled_bets,
                SUM(CASE WHEN wager_status = "pending" THEN 1 ELSE 0 END) as pending_bets,
                SUM(CASE WHEN prize_amount > 0 THEN 1 ELSE 0 END) as win_bets
            ')->first();
            
            if (!$stats) {
                return [
                    'total_bets' => 0,
                    'total_bet_amount' => 0,
                    'total_valid_bet_amount' => 0,
                    'total_prize_amount' => 0,
                    'settled_bets' => 0,
                    'pending_bets' => 0,
                    'win_bets' => 0,
                    'net_profit' => 0,
                    'win_rate' => 0
                ];
            }
            
            $netProfit = (float)$stats->total_prize_amount - (float)$stats->total_bet_amount;
            $winRate = $stats->settled_bets > 0 ? ($stats->win_bets / $stats->settled_bets) * 100 : 0;
            
            return [
                'total_bets' => (int)$stats->total_bets,
                'total_bet_amount' => (float)$stats->total_bet_amount,
                'total_valid_bet_amount' => (float)$stats->total_valid_bet_amount,
                'total_prize_amount' => (float)$stats->total_prize_amount,
                'settled_bets' => (int)$stats->settled_bets,
                'pending_bets' => (int)$stats->pending_bets,
                'win_bets' => (int)$stats->win_bets,
                'net_profit' => round($netProfit, 2),
                'win_rate' => round($winRate, 2)
            ];
            
        } catch (\Exception $e) {
            Log::error('Failed to get bet statistics: ' . $e->getMessage(), [
                'uid' => $uid,
                'filters' => $filters
            ]);
            return [];
        }
    }
    
    /**
     * Process bet data from pushbetdata callback
     * Handles both new bets and updates
     * 
     * @param array $betData Bet data from GSC+
     * @return array ['success' => bool, 'bet_id' => int|null, 'error' => string|null]
     */
    public static function processBetData(array $betData): array
    {
        try {
            $wagerCode = $betData['wagerCode'] ?? null;
            
            if (!$wagerCode) {
                return [
                    'success' => false,
                    'bet_id' => null,
                    'error' => 'Missing wagerCode'
                ];
            }
            
            // Check if bet already exists
            $existing = self::getBetByWagerCode($wagerCode);
            
            if ($existing) {
                // Update existing bet
                $success = self::updateBetData($wagerCode, $betData);
                
                return [
                    'success' => $success,
                    'bet_id' => $existing->id,
                    'error' => $success ? null : 'Failed to update bet',
                    'action' => 'updated'
                ];
            } else {
                // Create new bet
                $betId = self::saveBetData($betData);
                
                return [
                    'success' => true,
                    'bet_id' => $betId,
                    'error' => null,
                    'action' => 'created'
                ];
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to process bet data: ' . $e->getMessage(), [
                'bet_data' => $betData
            ]);
            
            return [
                'success' => false,
                'bet_id' => null,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get bets by product code
     * 
     * @param string $productCode Product code
     * @param array $filters Optional filters
     * @return array Bet records
     */
    public static function getBetsByProduct(string $productCode, array $filters = []): array
    {
        try {
            $query = Db::table('caipiao_gscplus_bet_data')
                ->where('product_code', $productCode);
            
            if (isset($filters['uid'])) {
                $query->where('uid', $filters['uid']);
            }
            
            if (isset($filters['wager_status'])) {
                $query->where('wager_status', $filters['wager_status']);
            }
            
            if (isset($filters['start_time'])) {
                $query->where('bet_time', '>=', $filters['start_time']);
            }
            
            if (isset($filters['end_time'])) {
                $query->where('bet_time', '<=', $filters['end_time']);
            }
            
            $query->orderBy('bet_time', 'desc');
            
            if (isset($filters['limit'])) {
                $query->limit($filters['limit']);
            }
            
            return $query->get()->toArray();
            
        } catch (\Exception $e) {
            Log::error('Failed to get bets by product: ' . $e->getMessage(), [
                'product_code' => $productCode,
                'filters' => $filters
            ]);
            return [];
        }
    }
    
    /**
     * Get unsettled bets for a user
     * 
     * @param int $uid User ID
     * @return array Unsettled bet records
     */
    public static function getUnsettledBets(int $uid): array
    {
        try {
            return Db::table('caipiao_gscplus_bet_data')
                ->where('uid', $uid)
                ->whereIn('wager_status', ['pending', 'running'])
                ->orderBy('bet_time', 'desc')
                ->get()
                ->toArray();
                
        } catch (\Exception $e) {
            Log::error('Failed to get unsettled bets: ' . $e->getMessage(), [
                'uid' => $uid
            ]);
            return [];
        }
    }
}