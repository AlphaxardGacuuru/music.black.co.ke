<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Audio;
use App\Models\BoughtAudio;
use App\Models\BoughtVideo;
use App\Models\User;
use App\Models\Video;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class AdminDashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $totals = [
            'totalUsers' => (int) User::query()->count(),
            'totalMusicians' => (int) User::query()->where('account_type', 'musician')->count(),
            'totalVideos' => (int) Video::query()->count(),
            'totalAudios' => (int) Audio::query()->count(),
            'totalSales' => (int) BoughtVideo::query()->count() + BoughtAudio::query()->count(),
        ];

        $days = collect(range(13, 0))
            ->map(fn(int $offset) => now()->subDays($offset)->toDateString());

        $videoSales = $this->dailyCounts(BoughtVideo::query());
        $audioSales = $this->dailyCounts(BoughtAudio::query());

        $dailyVolume = $days->map(fn(string $date) => [
            'date' => $date,
            'sold' => (int) ($videoSales[$date] ?? 0) + (int) ($audioSales[$date] ?? 0),
        ])->values();

        return response()->json([
            'data' => [
                'totals' => $totals,
                'dailyVolume' => $dailyVolume,
            ],
        ]);
    }

    /**
     * Rows created per day over the last two weeks, keyed by date.
     *
     * @return Collection<string, int>
     */
    private function dailyCounts($query): Collection
    {
        return $query
            ->where('created_at', '>=', now()->subDays(13)->startOfDay())
            ->selectRaw('DATE(created_at) as date, count(*) as aggregate')
            ->groupBy('date')
            ->pluck('aggregate', 'date');
    }
}
