<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Sale;
use App\Services\ReportService;
use App\Support\Format;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    private const TABS = [
        'laba-rugi', 'arus-kas', 'piutang', 'utang', 'laba-kambing', 'penjualan', 'pembelian', 'stok',
    ];

    public function index(Request $request, ReportService $reports)
    {
        Gate::authorize('view-reports');

        $tab = in_array($request->query('tab'), self::TABS, true) ? $request->query('tab') : 'laba-rugi';
        $period = $reports->period($request->query('from'), $request->query('to'));
        $data = $this->tabData($tab, $period['from'], $period['to'], $reports);

        return view('reports.index', [
            'tab' => $tab,
            'tabs' => self::TABS,
            'from' => $period['from'],
            'to' => $period['to'],
            'data' => $data,
        ]);
    }

    public function csv(Request $request, ReportService $reports): StreamedResponse
    {
        Gate::authorize('view-reports');

        $tab = in_array($request->query('tab'), self::TABS, true) ? $request->query('tab') : 'laba-rugi';
        $period = $reports->period($request->query('from'), $request->query('to'));
        $data = $this->tabData($tab, $period['from'], $period['to'], $reports);
        $rows = $this->exportRows($tab, $data);

        $filename = 'kamsya-'.$tab.'-'.$period['from']->format('Ymd').'-'.$period['to']->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function pdf(Request $request, ReportService $reports)
    {
        Gate::authorize('view-reports');

        $tab = in_array($request->query('tab'), self::TABS, true) ? $request->query('tab') : 'laba-rugi';
        $period = $reports->period($request->query('from'), $request->query('to'));
        $data = $this->tabData($tab, $period['from'], $period['to'], $reports);

        $pdf = Pdf::loadView('reports.pdf', [
            'tab' => $tab,
            'from' => $period['from'],
            'to' => $period['to'],
            'data' => $data,
            'settings' => \App\Models\Setting::current(),
        ])->setPaper('a4', 'landscape');

        $filename = 'kamsya-'.$tab.'-'.$period['from']->format('Ymd').'-'.$period['to']->format('Ymd').'.pdf';

        return $pdf->download($filename);
    }

    private function tabData(string $tab, $from, $to, ReportService $reports): array
    {
        $base = [
            'summary' => $reports->summary($from, $to),
            'from' => $from,
            'to' => $to,
        ];

        return match ($tab) {
            'laba-rugi' => $base + [
                'sales' => $reports->salesQuery($from, $to)->get(),
                'expenses_by_category' => $reports->expensesByCategory($from, $to),
                'expenses' => $reports->expensesQuery($from, $to)->get(),
            ],
            'arus-kas' => $base + [
                'payments_in' => Payment::query()->notVoided()->where('payable_type', 'sale')
                    ->with('payable')->whereBetween('date', [$from->toDateString(), $to->toDateString()])
                    ->orderBy('date')->get(),
                'payments_out' => Payment::query()->notVoided()->where('payable_type', 'purchase')
                    ->with('payable')->whereBetween('date', [$from->toDateString(), $to->toDateString()])
                    ->orderBy('date')->get(),
                'expenses' => $reports->expensesQuery($from, $to)->get(),
            ],
            'piutang' => $base + ['aging' => $reports->receivablesAging($to)],
            'utang' => $base + ['aging' => $reports->payablesAging($to)],
            'laba-kambing' => $base + ['profits' => $reports->profitPerGoat($from, $to)],
            'penjualan' => $base + [
                'sales' => $reports->salesQuery($from, $to)->get(),
                'by_customer' => $reports->salesByCustomer($from, $to),
                'by_type' => $reports->salesByType($from, $to),
                'by_month' => $reports->salesByMonth($from, $to),
            ],
            'pembelian' => $base + [
                'purchases' => $reports->purchasesQuery($from, $to)->get(),
                'by_supplier' => $reports->purchasesBySupplier($from, $to),
                'by_month' => $reports->purchasesByMonth($from, $to),
            ],
            'stok' => $base + [
                'inventory' => $reports->inventory(),
            ],
            default => $base,
        };
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function exportRows(string $tab, array $data): array
    {
        $summary = $data['summary'];

        return match ($tab) {
            'laba-rugi' => [
                ['Laporan Laba Rugi'],
                ['Periode', Format::date($data['from']).' - '.Format::date($data['to'])],
                [],
                ['Komponen', 'Nilai'],
                ['Penjualan', $summary['sales_total']],
                ['HPP', $summary['cogs']],
                ['Laba Kotor', $summary['gross_profit']],
                ['Beban Operasional', $summary['expense_total']],
                ['Laba Bersih', $summary['net_profit']],
                [],
                ['Beban per Kategori', 'Jumlah', 'Nilai'],
                ...collect($data['expenses_by_category'])->map(fn ($row) => [$row['label'], $row['count'], $row['total']])->all(),
            ],
            'arus-kas' => [
                ['Laporan Arus Kas'],
                ['Periode', Format::date($data['from']).' - '.Format::date($data['to'])],
                [],
                ['Keterangan', 'Nilai'],
                ['Penerimaan Penjualan', $summary['cash_in']],
                ['Pembayaran Pembelian', -$summary['cash_out_purchases']],
                ['Beban Operasional', -$summary['cash_out_expenses']],
                ['Arus Kas Bersih', $summary['net_cash']],
            ],
            'piutang' => [
                ['Laporan Piutang Customer'],
                ['Periode', Format::date($data['from']).' - '.Format::date($data['to'])],
                [],
                ['Kode', 'Tanggal', 'Customer', 'Kambing', 'Nilai', 'Dibayar', 'Sisa', 'Umur (hari)'],
                ...$data['aging']['rows']->map(fn ($sale) => [
                    $sale->code,
                    $sale->date->format('Y-m-d'),
                    $sale->customer?->name,
                    $sale->goat?->code,
                    (float) $sale->price,
                    (float) $sale->paid_total,
                    $sale->remaining_amount,
                    $sale->age_days,
                ])->all(),
                [],
                ['Total Piutang', $data['aging']['total']],
            ],
            'utang' => [
                ['Laporan Utang Supplier'],
                ['Periode', Format::date($data['from']).' - '.Format::date($data['to'])],
                [],
                ['Kode', 'Tanggal', 'Supplier', 'Kambing', 'Nilai', 'Dibayar', 'Sisa', 'Umur (hari)'],
                ...$data['aging']['rows']->map(fn ($purchase) => [
                    $purchase->code,
                    $purchase->date->format('Y-m-d'),
                    $purchase->supplier?->name,
                    $purchase->goat?->code,
                    (float) $purchase->total,
                    (float) $purchase->paid_total,
                    $purchase->remaining_amount,
                    $purchase->age_days,
                ])->all(),
                [],
                ['Total Utang', $data['aging']['total']],
            ],
            'laba-kambing' => [
                ['Laporan Laba per Kambing'],
                ['Periode', Format::date($data['from']).' - '.Format::date($data['to'])],
                [],
                ['Kode Penjualan', 'Tanggal', 'Kambing', 'Harga Jual', 'HPP', 'Laba'],
                ...$data['profits']->map(fn ($row) => [
                    $row['sale']->code,
                    $row['sale']->date->format('Y-m-d'),
                    $row['goat']?->code.' - '.$row['goat']?->name,
                    $row['price'],
                    $row['cost_price'],
                    $row['profit'],
                ])->all(),
            ],
            'penjualan' => [
                ['Laporan Penjualan'],
                ['Periode', Format::date($data['from']).' - '.Format::date($data['to'])],
                [],
                ['Kode', 'Tanggal', 'Kambing', 'Customer', 'Harga Jual', 'HPP', 'Laba'],
                ...$data['sales']->map(fn (Sale $sale) => [
                    $sale->code,
                    $sale->date->format('Y-m-d'),
                    $sale->goat?->code,
                    $sale->customer?->name,
                    (float) $sale->price,
                    (float) $sale->cost_price,
                    $sale->profit(),
                ])->all(),
            ],
            'pembelian' => [
                ['Laporan Pembelian'],
                ['Periode', Format::date($data['from']).' - '.Format::date($data['to'])],
                [],
                ['Kode', 'Tanggal', 'Kambing', 'Supplier', 'Harga Beli', 'Biaya Tambahan', 'Total'],
                ...$data['purchases']->map(fn (Purchase $purchase) => [
                    $purchase->code,
                    $purchase->date->format('Y-m-d'),
                    $purchase->goat?->code,
                    $purchase->supplier?->name,
                    (float) $purchase->price,
                    (float) $purchase->additional_cost,
                    (float) $purchase->total,
                ])->all(),
            ],
            'stok' => [
                ['Laporan Valuasi Stok'],
                ['Tanggal', Format::date($data['to'])],
                [],
                ['Kode', 'Barcode', 'Nama', 'Jenis', 'Gender', 'Bobot', 'Biaya Perolehan'],
                ...$data['inventory']->map(fn ($goat) => [
                    $goat->code,
                    $goat->barcode,
                    $goat->name,
                    $goat->type,
                    $goat->gender?->label(),
                    (float) $goat->weight,
                    $goat->acquisitionCost(),
                ])->all(),
                [],
                ['Total Nilai Stok', $summary['inventory_value']],
            ],
            default => [],
        };
    }
}
