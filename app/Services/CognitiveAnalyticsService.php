<?php

namespace App\Services;

use App\Models\UserQueryLog;
use App\Models\Benchmark;
use App\Models\NodeTelemetry;
use Illuminate\Support\Facades\DB;

class CognitiveAnalyticsService
{
    /**
     * Get statistics for a specific user or currently authenticated user.
     */
    public function getUserStatistics(?int $userId = null): array
    {
        $userId = $userId ?? (auth()->check() ? auth()->id() : null);

        if (!$userId) {
            return [
                'total_queries' => 0,
                'hit_count' => 0,
                'miss_count' => 0,
                'hit_rate' => 0.0,
                'total_watts_saved' => 0.0,
                'total_tokens_consumed' => 0,
            ];
        }

        $totalQueries = UserQueryLog::where('user_id', $userId)->count();
        $hitCount = UserQueryLog::where('user_id', $userId)->where('is_hit', 1)->count();
        $missCount = UserQueryLog::where('user_id', $userId)->where('is_hit', 0)->count();
        $hitRate = $totalQueries > 0 ? round(($hitCount / $totalQueries) * 100, 2) : 0.0;
        $totalWattsSaved = UserQueryLog::where('user_id', $userId)->sum('watts_saved');
        $totalTokens = UserQueryLog::where('user_id', $userId)->sum('tokens_consumed');

        return [
            'total_queries' => $totalQueries,
            'hit_count' => $hitCount,
            'miss_count' => $missCount,
            'hit_rate' => $hitRate,
            'total_watts_saved' => round($totalWattsSaved, 2),
            'total_tokens_consumed' => $totalTokens,
        ];
    }

    /**
     * Get global swarm statistics across all users and nodes.
     */
    public function getGlobalSwarmStatistics(): array
    {
        $totalQueries = UserQueryLog::count();
        $hitCount = UserQueryLog::where('is_hit', 1)->count();
        $missCount = UserQueryLog::where('is_hit', 0)->count();
        $hitRate = $totalQueries > 0 ? round(($hitCount / $totalQueries) * 100, 2) : 0.0;
        
        $totalWattsSaved = UserQueryLog::sum('watts_saved');
        $totalWattsConsumed = UserQueryLog::sum('watts_consumed');
        $avgExecutionMs = UserQueryLog::avg('execution_time_ms');

        $latestNodeA = NodeTelemetry::where('node_identifier', 'node-a')->latest('id')->first();
        $latestNodeB = NodeTelemetry::where('node_identifier', 'node-b')->latest('id')->first();

        return [
            'total_queries' => $totalQueries,
            'hit_count' => $hitCount,
            'miss_count' => $missCount,
            'hit_rate' => $hitRate,
            'total_watts_saved' => round($totalWattsSaved, 2),
            'total_watts_consumed' => round($totalWattsConsumed, 2),
            'avg_execution_time_ms' => round($avgExecutionMs ?? 0.0, 2),
            'nodes_status' => [
                'node_a' => $latestNodeA ? [
                    'status' => $latestNodeA->status_state,
                    'temp' => $latestNodeA->temperature_c,
                    'cpu_load' => $latestNodeA->cpu_load_percent,
                    'recorded_at' => $latestNodeA->recorded_at,
                ] : null,
                'node_b' => $latestNodeB ? [
                    'status' => $latestNodeB->status_state,
                    'temp' => $latestNodeB->temperature_c,
                    'power' => $latestNodeB->metrics_payload['power'] ?? null,
                    'recorded_at' => $latestNodeB->recorded_at,
                ] : null,
            ],
        ];
    }

    /**
     * Get aggregate energy statistics (Power savings and consumption in Watts).
     * Note: watts_saved and watts_consumed represent instantaneous power metrics (Watts),
     * therefore arbitrary conversion to Watt-hours (Wh) via division by 3600 is omitted
     * to maintain strict semantic correctness between power and energy.
     */
    public function getEnergyStatistics(): array
    {
        $totalWattsConsumed = UserQueryLog::sum('watts_consumed');
        $totalWattsSaved = UserQueryLog::sum('watts_saved');

        return [
            'total_watts_consumed' => round($totalWattsConsumed, 2),
            'total_watts_saved' => round($totalWattsSaved, 2),
        ];
    }

    /**
     * Get cache performance statistics and breakdown.
     */
    public function getCachePerformanceStatistics(): array
    {
        $totalQueries = UserQueryLog::count();
        $hitCount = UserQueryLog::where('is_hit', 1)->count();
        $missCount = UserQueryLog::where('is_hit', 0)->count();
        $bypassRate = $totalQueries > 0 ? round(($hitCount / $totalQueries) * 100, 2) : 0.0;

        $avgHitLatencyMs = UserQueryLog::where('is_hit', 1)->avg('execution_time_ms');
        $avgMissLatencyMs = UserQueryLog::where('is_hit', 0)->avg('execution_time_ms');

        $benchmarkCount = Benchmark::count();
        $avgBenchmarkLatencyMs = Benchmark::avg('execution_time_ms');

        return [
            'total_queries' => $totalQueries,
            'hit_count' => $hitCount,
            'miss_count' => $missCount,
            'bypass_rate' => $bypassRate,
            'avg_hit_latency_ms' => round($avgHitLatencyMs ?? 0.0, 2),
            'avg_miss_latency_ms' => round($avgMissLatencyMs ?? 0.0, 2),
            'benchmarks' => [
                'total_tests' => $benchmarkCount,
                'avg_execution_time_ms' => round($avgBenchmarkLatencyMs ?? 0.0, 2),
            ],
        ];
    }
}
