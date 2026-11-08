<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\GoatSummaryResource;
use App\Models\Goat;
use App\Services\ReportService;
use App\Support\Format;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends BaseController
{
    public function __invoke(Request $request, ReportService $reports): JsonResponse
    {
        $this->authorize('viewAny', Goat::class);

        $stats = $reports->dashboardStats();
        $months = $reports->monthlySeries(6);

        $recent = $reports->recentTransactions(6)->map(function (array $row) {
            return [
                'type' => $row['type'],
                'date' => $row['date']?->toDateString(),
                'goat' => $row['goat'] ? new GoatSummaryResource($row['goat']) : null,
                'party' => $row['party'],
                'amount' => (float) $row['amount'],
                'profit' => $row['profit'] !== null ? (float) $row['profit'] : null,
            ];
        })->values();

        $summary = null;
        if ($request->user()->canManageFinance()) {
            $period = $reports->period(now()->startOfYear()->toDateString(), now()->toDateString());
            $summary = $reports->summary($period['from'], $period['to']);
        }

        return response()->json([
            'data' => [
                'greeting' => Format::greeting(),
                'today' => Format::todayLong(),
                'stats' => $stats,
                'monthly_series' => $months,
                'recent_transactions' => $recent,
                'summary' => $summary,
            ],
        ]);
    }
}
