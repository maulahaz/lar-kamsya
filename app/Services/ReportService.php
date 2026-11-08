<?php

namespace App\Services;

use App\Enums\ExpenseCategory;
use App\Enums\GoatStatus;
use App\Models\Expense;
use App\Models\Goat;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Sale;
use App\Support\Format;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * Build the date range from request strings (defaults to the current year).
     *
     * @return array{from: Carbon, to: Carbon}
     */
    public function period(?string $from = null, ?string $to = null): array
    {
        $start = $from ? Carbon::parse($from)->startOfDay() : Carbon::now()->startOfYear();
        $end = $to ? Carbon::parse($to)->endOfDay() : Carbon::now()->endOfDay();

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return ['from' => $start, 'to' => $end];
    }

    public function summary(Carbon $from, Carbon $to): array
    {
        $sales = $this->salesQuery($from, $to)->get();
        $purchases = $this->purchasesQuery($from, $to)->get();
        $expenses = $this->expensesQuery($from, $to)->get();

        $salesTotal = (float) $sales->sum('price');
        $cogs = (float) $sales->sum('cost_price');
        $expenseTotal = (float) $expenses->sum('amount');
        $purchaseTotal = (float) $purchases->sum('total');

        $cashIn = (float) Payment::query()
            ->notVoided()
            ->where('payable_type', 'sale')
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->sum('amount');

        $cashOutPurchases = (float) Payment::query()
            ->notVoided()
            ->where('payable_type', 'purchase')
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->sum('amount');

        return [
            'sales_total' => $salesTotal,
            'sales_count' => $sales->count(),
            'cogs' => $cogs,
            'gross_profit' => $salesTotal - $cogs,
            'margin' => $salesTotal > 0 ? (($salesTotal - $cogs) / $salesTotal) * 100 : 0,
            'expense_total' => $expenseTotal,
            'expense_count' => $expenses->count(),
            'net_profit' => $salesTotal - $cogs - $expenseTotal,
            'purchase_total' => $purchaseTotal,
            'purchase_count' => $purchases->count(),
            'cash_in' => $cashIn,
            'cash_out_purchases' => $cashOutPurchases,
            'cash_out_expenses' => $expenseTotal,
            'net_cash' => $cashIn - $cashOutPurchases - $expenseTotal,
            'receivables' => $this->receivablesAging($to)['total'],
            'payables' => $this->payablesAging($to)['total'],
            'inventory_value' => $this->inventoryValue(),
        ];
    }

    public function salesQuery(Carbon $from, Carbon $to)
    {
        return Sale::query()
            ->notVoided()
            ->with(['goat', 'customer'])
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('date')
            ->orderBy('id');
    }

    public function purchasesQuery(Carbon $from, Carbon $to)
    {
        return Purchase::query()
            ->notVoided()
            ->with(['goat', 'supplier'])
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('date')
            ->orderBy('id');
    }

    public function expensesQuery(Carbon $from, Carbon $to)
    {
        return Expense::query()
            ->notVoided()
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('date')
            ->orderBy('id');
    }

    /**
     * @return array<string, array{label: string, total: float, count: int}>
     */
    public function expensesByCategory(Carbon $from, Carbon $to): array
    {
        $expenses = $this->expensesQuery($from, $to)->get();

        $result = [];
        foreach (ExpenseCategory::cases() as $category) {
            $items = $expenses->where('category', $category);
            $result[$category->value] = [
                'label' => $category->label(),
                'total' => (float) $items->sum('amount'),
                'count' => $items->count(),
            ];
        }

        foreach ($expenses as $expense) {
            $key = $expense->category?->value ?? 'lainnya';
            if (! isset($result[$key])) {
                $result[$key] = [
                    'label' => $expense->category?->label() ?? 'Lainnya',
                    'total' => 0,
                    'count' => 0,
                ];
            }
        }

        return array_filter($result, fn (array $row) => $row['count'] > 0);
    }

    /**
     * @return array{rows: Collection, buckets: array<string, float>, total: float}
     */
    public function receivablesAging(?Carbon $asOf = null): array
    {
        $asOf = $asOf ?? Carbon::now();

        $sales = Sale::query()
            ->notVoided()
            ->with(['goat', 'customer'])
            ->withSum(['activePayments as paid_total' => function ($query) use ($asOf) {
                $query->whereDate('date', '<=', $asOf->toDateString());
            }], 'amount')
            ->whereDate('date', '<=', $asOf->toDateString())
            ->orderBy('date')
            ->get()
            ->filter(fn (Sale $sale) => (float) $sale->price - (float) ($sale->paid_total ?? 0) > 0.009)
            ->values();

        return $this->agingResult($sales, 'price', $asOf);
    }

    /**
     * @return array{rows: Collection, buckets: array<string, float>, total: float}
     */
    public function payablesAging(?Carbon $asOf = null): array
    {
        $asOf = $asOf ?? Carbon::now();

        $purchases = Purchase::query()
            ->notVoided()
            ->with(['goat', 'supplier'])
            ->withSum(['activePayments as paid_total' => function ($query) use ($asOf) {
                $query->whereDate('date', '<=', $asOf->toDateString());
            }], 'amount')
            ->whereDate('date', '<=', $asOf->toDateString())
            ->orderBy('date')
            ->get()
            ->filter(fn (Purchase $purchase) => (float) $purchase->total - (float) ($purchase->paid_total ?? 0) > 0.009)
            ->values();

        return $this->agingResult($purchases, 'total', $asOf);
    }

    private function agingResult(Collection $rows, string $totalField, Carbon $asOf): array
    {
        $buckets = [
            '0-30' => 0.0,
            '31-60' => 0.0,
            '>60' => 0.0,
        ];

        $rows->each(function ($row) use (&$buckets, $totalField, $asOf) {
            $remaining = (float) $row->{$totalField} - (float) ($row->paid_total ?? 0);
            $days = (int) Carbon::parse($row->date)->startOfDay()->diffInDays($asOf->copy()->startOfDay(), false);

            if ($days <= 30) {
                $buckets['0-30'] += $remaining;
            } elseif ($days <= 60) {
                $buckets['31-60'] += $remaining;
            } else {
                $buckets['>60'] += $remaining;
            }

            $row->setAttribute('remaining_amount', $remaining);
            $row->setAttribute('age_days', max(0, $days));
        });

        return [
            'rows' => $rows,
            'buckets' => $buckets,
            'total' => array_sum($buckets),
        ];
    }

    public function inventoryValue(): float
    {
        return (float) Goat::query()
            ->available()
            ->with('purchase')
            ->get()
            ->sum(fn (Goat $goat) => $goat->acquisitionCost());
    }

    /**
     * @return Collection<int, array{label: string, full: string, purchases: float, sales: float, expenses: float}>
     */
    public function monthlySeries(int $months = 6): Collection
    {
        $series = collect();

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = Carbon::now()->startOfMonth()->subMonths($i);
            $start = $date->copy()->startOfMonth();
            $end = $date->copy()->endOfMonth();

            $series->push([
                'label' => Format::monthLabel($date),
                'full' => Format::MONTHS[$date->month].' '.$date->year,
                'purchases' => (float) $this->purchasesQuery($start, $end)->sum('total'),
                'sales' => (float) $this->salesQuery($start, $end)->sum('price'),
                'expenses' => (float) $this->expensesQuery($start, $end)->sum('amount'),
            ]);
        }

        return $series;
    }

    public function profitPerGoat(Carbon $from, Carbon $to): Collection
    {
        return $this->salesQuery($from, $to)->get()->map(fn (Sale $sale) => [
            'sale' => $sale,
            'goat' => $sale->goat,
            'price' => (float) $sale->price,
            'cost_price' => (float) $sale->cost_price,
            'profit' => $sale->profit(),
        ]);
    }

    public function salesByCustomer(Carbon $from, Carbon $to): Collection
    {
        return $this->salesQuery($from, $to)->get()
            ->groupBy(fn (Sale $sale) => $sale->customer?->name ?? 'Tanpa customer')
            ->map(fn (Collection $group, string $name) => [
                'name' => $name,
                'count' => $group->count(),
                'total' => (float) $group->sum('price'),
                'profit' => (float) $group->sum(fn (Sale $sale) => $sale->profit()),
            ])
            ->sortByDesc('total')
            ->values();
    }

    public function salesByType(Carbon $from, Carbon $to): Collection
    {
        return $this->salesQuery($from, $to)->get()
            ->groupBy(fn (Sale $sale) => $sale->goat?->type ?? 'Lainnya')
            ->map(fn (Collection $group, string $type) => [
                'type' => $type,
                'count' => $group->count(),
                'total' => (float) $group->sum('price'),
                'profit' => (float) $group->sum(fn (Sale $sale) => $sale->profit()),
            ])
            ->sortByDesc('total')
            ->values();
    }

    public function salesByMonth(Carbon $from, Carbon $to): Collection
    {
        return $this->salesQuery($from, $to)->get()
            ->groupBy(fn (Sale $sale) => Carbon::parse($sale->date)->format('Y-m'))
            ->map(fn (Collection $group, string $key) => [
                'key' => $key,
                'label' => Format::MONTHS[(int) substr($key, 5, 2)].' '.substr($key, 0, 4),
                'count' => $group->count(),
                'total' => (float) $group->sum('price'),
                'profit' => (float) $group->sum(fn (Sale $sale) => $sale->profit()),
            ])
            ->sortKeys()
            ->values();
    }

    public function purchasesBySupplier(Carbon $from, Carbon $to): Collection
    {
        return $this->purchasesQuery($from, $to)->get()
            ->groupBy(fn (Purchase $purchase) => $purchase->supplier?->name ?? 'Tanpa supplier')
            ->map(fn (Collection $group, string $name) => [
                'name' => $name,
                'count' => $group->count(),
                'total' => (float) $group->sum('total'),
                'additional' => (float) $group->sum('additional_cost'),
            ])
            ->sortByDesc('total')
            ->values();
    }

    public function purchasesByMonth(Carbon $from, Carbon $to): Collection
    {
        return $this->purchasesQuery($from, $to)->get()
            ->groupBy(fn (Purchase $purchase) => Carbon::parse($purchase->date)->format('Y-m'))
            ->map(fn (Collection $group, string $key) => [
                'key' => $key,
                'label' => Format::MONTHS[(int) substr($key, 5, 2)].' '.substr($key, 0, 4),
                'count' => $group->count(),
                'total' => (float) $group->sum('total'),
            ])
            ->sortKeys()
            ->values();
    }

    public function inventory(): Collection
    {
        return Goat::query()
            ->available()
            ->with('purchase.supplier')
            ->orderBy('code')
            ->get();
    }

    public function dashboardStats(): array
    {
        $goats = Goat::query()->with('purchase')->get();
        $available = $goats->where('status', GoatStatus::Tersedia);
        $sold = $goats->where('status', GoatStatus::Terjual);

        $sales = Sale::query()->notVoided()->get();
        $purchases = Purchase::query()->notVoided()->get();

        $salesTotal = (float) $sales->sum('price');
        $cogs = (float) $sales->sum('cost_price');

        $byType = $goats->groupBy('type')->map(fn (Collection $group, string $type) => [
            'type' => $type,
            'total' => $group->count(),
            'available' => $group->where('status', GoatStatus::Tersedia)->count(),
        ])->values();

        return [
            'total_goats' => $goats->count(),
            'available_count' => $available->count(),
            'sold_count' => $sold->count(),
            'available_purchased' => $available->where('origin', 'pembelian')->count(),
            'available_offspring' => $available->where('origin', 'peranakan')->count(),
            'total_purchases' => (float) $purchases->sum('total'),
            'total_purchase_count' => $purchases->count(),
            'total_additional' => (float) $purchases->sum('additional_cost'),
            'total_sales' => $salesTotal,
            'total_sale_count' => $sales->count(),
            'gross_profit' => $salesTotal - $cogs,
            'margin' => $salesTotal > 0 ? (($salesTotal - $cogs) / $salesTotal) * 100 : 0,
            'inventory_value' => (float) $available->sum(fn (Goat $goat) => $goat->acquisitionCost()),
            'by_type' => $byType,
            'sales_total' => $salesTotal,
        ];
    }

    public function recentTransactions(int $limit = 6): Collection
    {
        $purchases = Purchase::query()
            ->notVoided()
            ->with(['goat', 'supplier'])
            ->latest('date')
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (Purchase $purchase) => [
                'type' => 'pembelian',
                'date' => $purchase->date,
                'goat' => $purchase->goat,
                'party' => $purchase->supplier?->name,
                'amount' => (float) $purchase->total,
                'profit' => null,
                'route' => route('purchases.show', $purchase),
            ]);

        $sales = Sale::query()
            ->notVoided()
            ->with(['goat', 'customer'])
            ->latest('date')
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (Sale $sale) => [
                'type' => 'penjualan',
                'date' => $sale->date,
                'goat' => $sale->goat,
                'party' => $sale->customer?->name,
                'amount' => (float) $sale->price,
                'profit' => $sale->profit(),
                'route' => route('sales.show', $sale),
            ]);

        return $purchases->concat($sales)
            ->sortByDesc(fn (array $row) => $row['date']->toDateString())
            ->take($limit)
            ->values();
    }
}
