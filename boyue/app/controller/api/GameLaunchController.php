<?php

namespace app\controller\api;

use app\constants\GameCategoryMapping;
use app\service\GscPlusGameLaunchService;
use support\Request;
use support\Response;
use support\Log;
use support\Db;

/**
 * Game Launch Controller
 * Handles game launching requests
 */
class GameLaunchController
{
    /**
     * Launch game
     * GET /game/launch?category=NO_HU&platform=JDB&game_code=xxx
     * 
     * @param Request $request
     * @return Response
     */
    public function launch(Request $request): Response
    {
        try {
            // Get parameters
            $category = $request->get('category');
            $platform = $request->get('platform');
            $gameCode = $request->get('game_code');
            $platformType = $request->get('platform_type', 'WEB'); // WEB, MOBILE, DESKTOP
            
            // Validate required parameters
            if (!$category || !$platform) {
                return json([
                    'code' => 400,
                    'message' => 'Missing required parameters: category and platform',
                    'data' => null
                ], 400);
            }

            // Map category to game type
            $gameType = GameCategoryMapping::getGameType($category);
            if (!$gameType) {
                return json([
                    'code' => 400,
                    'message' => "Invalid category: {$category}",
                    'available_categories' => GameCategoryMapping::getAllCategories(),
                    'data' => null
                ], 400);
            }

            // Check if platform is supported
            if (!GameCategoryMapping::isPlatformSupported($gameType, $platform)) {
                return json([
                    'code' => 400,
                    'message' => "Platform {$platform} not supported for category {$category}",
                    'supported_platforms' => GameCategoryMapping::getSupportedPlatforms($gameType),
                    'data' => null
                ], 400);
            }

            // Get user info from session/token
            $user = $this->getCurrentUser($request);
            if (!$user) {
                return json([
                    'code' => 401,
                    'message' => 'User not authenticated',
                    'data' => null
                ], 401);
            }

            // Prepare launch parameters
            $launchParams = [
                'member_account' => $user['username'],
                'password' => $user['password'] ?? $user['username'], // Use hashed password or username
                'nickname' => $user['nickname'] ?? $user['username'],
                'currency' => $user['currency'] ?? 'VND2',
                'game_type' => $gameType,
                'platform' => $platform,
                'game_code' => $gameCode,
                'platform_type' => strtoupper($platformType),
                'language_code' => $this->getLanguageCode($request),
                'ip' => $request->getRealIp(),
                'operator_lobby_url' => $this->getOperatorLobbyUrl($request)
            ];

            // Launch game via GSC+ API
            $launchService = new GscPlusGameLaunchService();
            $result = $launchService->launchGame($launchParams);

            // Check response
            if (isset($result['code']) && $result['code'] == 200) {
                return json([
                    'code' => 200,
                    'message' => 'Game launched successfully',
                    'data' => [
                        'game_url' => $result['url'] ?? null,
                        'content' => $result['content'] ?? null,
                        'platform' => GameCategoryMapping::getPlatformName($platform),
                        'game_type' => $gameType,
                        'game_code' => $gameCode
                    ]
                ]);
            } else {
                return json([
                    'code' => $result['code'] ?? 500,
                    'message' => $result['message'] ?? 'Failed to launch game',
                    'data' => null
                ], $result['code'] ?? 500);
            }

        } catch (\Exception $e) {
            Log::error('Game launch failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return json([
                'code' => 500,
                'message' => 'Game launch failed: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Get games list for a platform
     * GET /game/list?category=NO_HU&platform=JDB
     * 
     * @param Request $request
     * @return Response
     */
    public function list(Request $request): Response
    {
        try {
            $category = $request->get('category');
            $platform = $request->get('platform');

            if (!$category || !$platform) {
                return json([
                    'code' => 400,
                    'message' => 'Missing required parameters: category and platform',
                    'data' => null
                ], 400);
            }

            // Map category to game type
            $gameType = GameCategoryMapping::getGameType($category);
            if (!$gameType) {
                return json([
                    'code' => 400,
                    'message' => "Invalid category: {$category}",
                    'data' => null
                ], 400);
            }

            // Get games list
            $launchService = new GscPlusGameLaunchService();
            $result = $launchService->getGamesList($platform, $gameType);

            return json([
                'code' => 200,
                'message' => 'Success',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Get games list failed', [
                'error' => $e->getMessage()
            ]);

            return json([
                'code' => 500,
                'message' => 'Failed to get games list: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Get available platforms for a category
     * GET /game/platforms?category=NO_HU
     * 
     * @param Request $request
     * @return Response
     */
    public function platforms(Request $request): Response
    {
        try {
            $category = $request->get('category');

            // Query database for GSC+ platforms (api_provider = 'GSC')
            $query = Db::table('caipiao_game_platform')
                ->where('api_provider', 'GSC')
                ->where('status', 'online');

            if ($category) {
                // Get game type for category
                $gameType = GameCategoryMapping::getGameType($category);
                if (!$gameType) {
                    return json([
                        'code' => 400,
                        'message' => "Invalid category: {$category}",
                        'data' => null
                    ], 400);
                }

                // Filter platforms by category type
                $query->where('type', $category);
            }

            $platforms = $query->orderBy('sort', 'asc')->get();

            $platformsData = [];
            foreach ($platforms as $platform) {
                $platformsData[] = [
                    'id' => $platform->id,
                    'name' => $platform->name,
                    'code' => $platform->code,
                    'product_code' => $platform->product_code,
                    'image' => $platform->icon ?? $platform->mobile_icon,
                    'status' => $platform->status === 'online' ? 1 : 0,
                    'pt_percent' => $platform->pt_percent,
                    'is_gscplus' => 1
                ];
            }

            return json([
                'code' => 0,
                'msg' => 'success',
                'data' => [
                    'platforms' => $platformsData,
                    'total' => count($platformsData),
                    'category' => $category ?? 'all'
                ]
            ]);

        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => 'Failed to get platforms: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Get all available categories
     * GET /game/categories
     * 
     * @param Request $request
     * @return Response
     */
    public function categories(Request $request): Response
    {
        try {
            // Query database for categories
            $categories = Db::table('caipiao_game_category')
                ->where('status', 1)
                ->orderBy('sort', 'asc')
                ->get();

            $categoriesData = [];
            foreach ($categories as $category) {
                $categoriesData[] = [
                    'id' => $category->id,
                    'name' => $category->name,
                    'code' => $category->code,
                    'gsc_type' => $category->gsc_type,
                    'image' => $category->icon ?? $category->icon_img,
                    'position' => $category->sort,
                    'status' => $category->status
                ];
            }

            return json([
                'code' => 0,
                'msg' => 'success',
                'data' => [
                    'categories' => $categoriesData,
                    'total' => count($categoriesData)
                ]
            ]);

        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => 'Failed to get categories: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Get current authenticated user
     * This should integrate with your authentication system
     */
    private function getCurrentUser(Request $request): ?array
    {
        // TODO: Implement proper authentication
        // For now, return mock user for testing
        
        // Try to get user from JWT token or session
        $token = $request->header('Authorization') ?? $request->get('token');
        
        if ($token) {
            // Decode JWT token and get user info
            // This is a placeholder - implement actual JWT decoding
            return [
                'username' => 'test_user',
                'nickname' => 'Test User',
                'currency' => 'VND2',
                'password' => 'test_user' // Should be hashed
            ];
        }

        // For testing without authentication
        return [
            'username' => 'guest_' . time(),
            'nickname' => 'Guest User',
            'currency' => 'VND2',
            'password' => 'guest'
        ];
    }

    /**
     * Get language code from request
     */
    private function getLanguageCode(Request $request): int
    {
        $lang = $request->header('Accept-Language') ?? $request->get('lang', 'vi');
        
        // Map language to GSC+ language code
        $langMap = [
            'en' => 0,
            'zh-CN' => 1,
            'zh-TW' => 2,
            'vi' => 12,
            'th' => 3,
            'id' => 4,
            'ja' => 5,
            'ko' => 6,
            'es' => 7,
            'pt' => 9
        ];

        return $langMap[$lang] ?? 12; // Default to Vietnamese
    }

    /**
     * Get operator lobby URL
     */
    private function getOperatorLobbyUrl(Request $request): string
    {
        // Use 0.0.0.0 for local development
        return 'http://0.0.0.0:8788';
    }
}