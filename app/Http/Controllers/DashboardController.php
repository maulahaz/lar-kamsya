<?php

namespace App\Http\Controllers;

use App\Models\Goat;
use App\Services\ReportService;
use App\Support\Format;

class DashboardController extends Controller
{
    public function __invoke(ReportService $reports)
    {
        $this->authorize('viewAny', Goat::class);

        $stats = $reports->dashboardStats();
        $months = $reports->monthlySeries(6);
        $recent = $reports->recentTransactions(6);
        $availableRatio = $stats['total_goats'] > 0
            ? round(($stats['available_count'] / $stats['total_goats']) * 100)
            : 0;

        $summary = null;
        if (auth()->user()->canManageFinance()) {
            $period = $reports->period(now()->startOfYear()->toDateString(), now()->toDateString());
            $summary = $reports->summary($period['from'], $period['to']);
        }

        return view('dashboard', [
            'stats' => $stats,
            'months' => $months,
            'recent' => $recent,
            'availableRatio' => $availableRatio,
            'summary' => $summary,
            'greeting' => Format::greeting(),
        ]);
    }
}
