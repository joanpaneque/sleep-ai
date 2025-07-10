<?php

namespace App\Http\Controllers;

use App\Models\DailyChannelStat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    /**
     * Get statistics for the specified timeframe
     */
    public function getDailyStats(Request $request): JsonResponse
    {
        try {
            // Si se especifica timeframe, usar getTimeframeStats, sino usar getLastDays
            $timeframe = $request->input('timeframe');
            
            $stats = $timeframe 
                ? DailyChannelStat::getTimeframeStats((int)$timeframe)
                : DailyChannelStat::getLastDays(28);

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 