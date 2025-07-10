<?php

namespace App\Http\Controllers;

use App\Models\DailyChannelStat;
use Illuminate\Http\JsonResponse;

class AnalyticsController extends Controller
{
    /**
     * Get daily statistics for the last 28 days
     */
    public function getDailyStats(): JsonResponse
    {
        try {
            $stats = DailyChannelStat::getLastDays(28);

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching daily statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 