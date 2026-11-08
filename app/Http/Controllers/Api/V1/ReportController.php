<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Expense;
use App\Models\Goat;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Sale;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class ReportController extends BaseController
{
    public const TABS = [
        'laba-rugi', 'arus-kas', 'piutang', 'utang', 'laba-kambing', 'penjualan', 'pembelian', 'stok',
    ];

    public function index(Request $request, ReportService $reports): JsonResponse
    {
        Gate::authorize('view-reports');

        $tab = in_array($request->query('tab'), self::TABS, true) ? $request->query('tab') : 'laba-rugi';
        $period = $reports->period($request->query('from'), $request->query('to'));

        return response()->json([
            'data' => [
                'tab' => $tab,
                'from' => $period['from']->toDateString(),
                'to' => $period['to']->toDateString(),
                ...$this->tabData($tab, $period['from'], $period['to'], $reports),
            ],
        ]);
    }

    public function summary(Request $request, ReportService $reports): JsonResponse
    {
        Gate::authorize('view-reports');

        $period = $reports->period($request->query('from'), $request->query('to'));

        return response()->json([
            'data' => [
                'from' => $period['from']->toDateString(),
                'to' => $period['to']->toDateString(),
                'summary' => $reports->summary($period['from'], $period['to']),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function tabData(string $tab, Carbon $from, Carbon $to, ReportService $reports): array
    {
        $base = ['summary' => $reports->summary($from, $to)];

        return match ($tab) {
            'laba-rugi' => $base + [
                'sales' => $reports->salesQuery($from, $to)->get()->map(fn (Sale $sale) => $this->saleRow($sale))->values(),
                'expenses_by_category' => $reports->expensesByCategory($from, $to),
                'expenses' => $reports->expensesQuery($from, $to)->get()->map(fn (Expense $expense) => $this->expenseRow($expense))->values(),
            ],
            'arus-kas' => $base + [
                'payments_in' => Payment::query()->notVoided()->where('payable_type', 'sale')
                    ->with('payable')->whereBetween('date', [$from->toDateString(), $to->toDateString()])
                    ->orderBy('date')->get()->map(fn (Payment $payment) => $this->paymentRow($payment))->values(),
                'payments_out' => Payment::query()->notVoided()->where('payable_type', 'purchase')
                    ->with('payable')->whereBetween('date', [$from->toDateString(), $to->toDateString()])
                    ->orderBy('date')->get()->map(fn (Payment $payment) => $this->paymentRow($payment))->values(),
                'expenses' => $reports->expensesQuery($from, $to)->get()->map(fn (Expense $expense) => $this->expenseRow($expense))->values(),
            ],
            'piutang' => $base + ['aging' => $this->aging($reports->receivablesAging($to), 'sale')],
            'utang' => $base + ['aging' => $this->aging($reports->payablesAging($to), 'purchase')],
            'laba-kambing' => $base + [
                'profits' => $reports->profitPerGoat($from, $to)->map(fn (array $row) => [
                    'sale_code' => $row['sale']->code,
                    'date' => $row['sale']->date?->toDateString(),
                    'goat' => $row['goat'] ? $this->goatRow($row['goat']) : null,
                    'price' => (float) $row['price'],
                    'cost_price' => (float) $row['cost_price'],
                    'profit' => (float) $row['profit'],
                ])->values(),
            ],
            'penjualan' => $base + [
                'sales' => $reports->salesQuery($from, $to)->get()->map(fn (Sale $sale) => $this->saleRow($sale))->values(),
                'by_customer' => $reports->salesByCustomer($from, $to),
                'by_type' => $reports->salesByType($from, $to),
                'by_month' => $reports->salesByMonth($from, $to),
            ],
            'pembelian' => $base + [
                'purchases' => $reports->purchasesQuery($from, $to)->get()->map(fn (Purchase $purchase) => $this->purchaseRow($purchase))->values(),
                'by_supplier' => $reports->purchasesBySupplier($from, $to),
                'by_month' => $reports->purchasesByMonth($from, $to),
            ],
            'stok' => $base + [
                'inventory' => $reports->inventory()->map(fn (Goat $goat) => $this->goatRow($goat) + [
                    'acquisition_cost' => $goat->acquisitionCost(),
                ])->values(),
            ],
            default => $base,
        };
    }

    private function aging(array $aging, string $type): array
    {
        return [
            'buckets' => $aging['buckets'],
            'total' => (float) $aging['total'],
            'rows' => $aging['rows']->map(function ($row) use ($type) {
                return [
                    'code' => $row->code,
                    'date' => $row->date?->toDateString(),
                    'party' => $type === 'sale' ? $row->customer?->name : $row->supplier?->name,
                    'goat' => $row->goat ? $this->goatRow($row->goat) : null,
                    'total' => (float) ($type === 'sale' ? $row->price : $row->total),
                    'paid' => (float) ($row->paid_total ?? 0),
                    'remaining' => (float) $row->remaining_amount,
                    'age_days' => (int) $row->age_days,
                ];
            })->values(),
        ];
    }

    private function saleRow(Sale $sale): array
    {
        return [
            'id' => $sale->id,
            'code' => $sale->code,
            'date' => $sale->date?->toDateString(),
            'goat' => $sale->goat ? $this->goatRow($sale->goat) : null,
            'customer' => $sale->customer?->name,
            'customer_code' => $sale->customer?->code,
            'price' => (float) $sale->price,
            'cost_price' => (float) $sale->cost_price,
            'profit' => $sale->profit(),
            'notes' => $sale->notes,
            'voided_at' => $sale->voided_at?->toIso8601String(),
        ];
    }

    private function purchaseRow(Purchase $purchase): array
    {
        return [
            'id' => $purchase->id,
            'code' => $purchase->code,
            'date' => $purchase->date?->toDateString(),
            'goat' => $purchase->goat ? $this->goatRow($purchase->goat) : null,
            'supplier' => $purchase->supplier?->name,
            'supplier_code' => $purchase->supplier?->code,
            'price' => (float) $purchase->price,
            'additional_cost' => (float) $purchase->additional_cost,
            'total' => (float) $purchase->total,
            'notes' => $purchase->notes,
            'voided_at' => $purchase->voided_at?->toIso8601String(),
        ];
    }

    private function expenseRow(Expense $expense): array
    {
        return [
            'id' => $expense->id,
            'code' => $expense->code,
            'date' => $expense->date?->toDateString(),
            'category' => $expense->category?->value,
            'category_label' => $expense->category?->label(),
            'amount' => (float) $expense->amount,
            'notes' => $expense->notes,
            'voided_at' => $expense->voided_at?->toIso8601String(),
        ];
    }

    private function paymentRow(Payment $payment): array
    {
        return [
            'id' => $payment->id,
            'code' => $payment->code,
            'date' => $payment->date?->toDateString(),
            'payable_type' => $payment->payable_type,
            'payable_code' => $payment->payable?->code,
            'amount' => (float) $payment->amount,
            'method' => $payment->method?->value,
            'method_label' => $payment->method?->label(),
            'notes' => $payment->notes,
        ];
    }

    private function goatRow(Goat $goat): array
    {
        return [
            'id' => $goat->id,
            'code' => $goat->code,
            'barcode' => $goat->barcode,
            'name' => $goat->name,
            'type' => $goat->type,
            'gender' => $goat->gender?->value,
            'gender_label' => $goat->gender?->label(),
            'weight' => (float) $goat->weight,
            'status' => $goat->status?->value,
            'status_label' => $goat->status?->label(),
        ];
    }
}
