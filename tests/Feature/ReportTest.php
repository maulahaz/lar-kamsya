<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\ReportService;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReportTest extends TestCase
{
    private function reportService(): ReportService
    {
        return app(ReportService::class);
    }

    public function test_reports_index_loads_for_owner(): void
    {
        $owner = \App\Models\User::query()->where('email', 'owner@kamsya.id')->first();

        $response = $this->actingAs($owner)->get(route('reports.index'));

        $response->assertStatus(200);
        $response->assertViewIs('reports.index');
    }

    public function test_keeper_cannot_access_reports(): void
    {
        $keeper = \App\Models\User::query()->where('email', 'petugas@kamsya.id')->first();

        $response = $this->actingAs($keeper)->get(route('reports.index'));

        $response->assertStatus(403);
    }

    public function test_report_summary_matches_seed_reference_values(): void
    {
        $from = Carbon::parse('2026-01-01');
        $to = Carbon::parse('2026-12-31');
        $summary = $this->reportService()->summary($from, $to);

        $this->assertSameWithFloat(12400000.0, $summary['sales_total']);
        $this->assertSameWithFloat(7550000.0, $summary['cogs']);
        $this->assertSameWithFloat(4850000.0, $summary['gross_profit']);
        $this->assertSameWithFloat(1150000.0, $summary['expense_total']);
        $this->assertSameWithFloat(3700000.0, $summary['net_profit']);
        $this->assertSameWithFloat(27125000.0, $summary['purchase_total']);
        $this->assertSameWithFloat(19575000.0, $summary['inventory_value']);
        $this->assertSameWithFloat(1400000.0, $summary['receivables']);
        $this->assertSameWithFloat(2400000.0, $summary['payables']);
        $this->assertSameWithFloat(11000000.0, $summary['cash_in']);
        $this->assertSameWithFloat(24725000.0, $summary['cash_out_purchases']);
        $this->assertSameWithFloat(-14875000.0, $summary['net_cash']);
    }

    protected function assertSameWithFloat(float $expected, mixed $actual): void
    {
        $this->assertSame($expected, (float) $actual);
    }

    public function test_report_index_displays_summary_values(): void
    {
        $owner = \App\Models\User::query()->where('email', 'owner@kamsya.id')->first();

        $response = $this->actingAs($owner)->get(route('reports.index', [
            'tab' => 'laba-rugi',
            'from' => '2026-01-01',
            'to' => '2026-12-31',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Rp12.400.000');
        $response->assertSee('Rp7.550.000');
        $response->assertSee('Rp4.850.000');
        $response->assertSee('Rp3.700.000');
    }

    public function test_csv_export_works_for_all_tabs(): void
    {
        $owner = \App\Models\User::query()->where('email', 'owner@kamsya.id')->first();

        $tabs = [
            'laba-rugi', 'arus-kas', 'piutang', 'utang',
            'laba-kambing', 'penjualan', 'pembelian', 'stok',
        ];

        foreach ($tabs as $tab) {
            $response = $this->actingAs($owner)->get(route('reports.csv', [
                'tab' => $tab,
                'from' => '2026-01-01',
                'to' => '2026-12-31',
            ]));

            $response->assertStatus(200);
            $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
            $response->assertHeader('Content-Disposition', "attachment; filename=kamsya-{$tab}-20260101-20261231.csv");
        }
    }

    public function test_csv_export_returns_correct_content(): void
    {
        $owner = \App\Models\User::query()->where('email', 'owner@kamsya.id')->first();

        $response = $this->actingAs($owner)->get(route('reports.csv', [
            'tab' => 'laba-rugi',
            'from' => '2026-01-01',
            'to' => '2026-12-31',
        ]));

        $content = $response->streamedContent();
        $this->assertStringContainsString('Laporan Laba Rugi', $content);
        $this->assertStringContainsString('Penjualan', $content);
        $this->assertStringContainsString('HPP', $content);
        $this->assertStringContainsString('Laba Bersih', $content);
        $this->assertStringContainsString('12400000', $content);
        $this->assertStringContainsString('3700000', $content);
    }

    public function test_pdf_export_works_for_all_tabs(): void
    {
        $owner = \App\Models\User::query()->where('email', 'owner@kamsya.id')->first();

        $tabs = [
            'laba-rugi', 'arus-kas', 'piutang', 'utang',
            'laba-kambing', 'penjualan', 'pembelian', 'stok',
        ];

        foreach ($tabs as $tab) {
            $response = $this->actingAs($owner)->get(route('reports.pdf', [
                'tab' => $tab,
                'from' => '2026-01-01',
                'to' => '2026-12-31',
            ]));

            $response->assertStatus(200);
            $response->assertHeader('Content-Type', 'application/pdf');
            $response->assertHeader('Content-Disposition', "attachment; filename=kamsya-{$tab}-20260101-20261231.pdf");
            $this->assertStringStartsWith('%PDF', (string) $response->getContent());
        }
    }

    public function test_piutang_aging_has_correct_totals(): void
    {
        $to = Carbon::parse('2026-11-07');
        $aging = $this->reportService()->receivablesAging($to);

        // TRX-J-2026-002: price 2350000, paid 1400000, remaining 950000, date 2026-10-02, days from 2026-11-07 = 36 days -> 31-60 bucket
        // TRX-J-2026-005: price 2400000, paid 1950000, remaining 450000, date 2026-11-04, days from 2026-11-07 = 3 days -> 0-30 bucket
        $this->assertSame(1400000.0, $aging['total']);
        $this->assertSame(450000.0, $aging['buckets']['0-30']);
        $this->assertSame(950000.0, $aging['buckets']['31-60']);
        $this->assertSame(0.0, $aging['buckets']['>60']);
    }

    public function test_utang_aging_has_correct_totals(): void
    {
        $to = Carbon::parse('2026-11-07');
        $aging = $this->reportService()->payablesAging($to);

        // Remaining payables: TRX-B-2026-003 (725000), TRX-B-2026-007 (1300000), TRX-B-2026-010 (375000)
        $this->assertSameWithFloat(2400000.0, $aging['total']);
    }

    public function test_inventory_value_matches_reference(): void
    {
        $value = $this->reportService()->inventoryValue();

        $this->assertSame(19575000.0, $value);
    }

    public function test_expenses_by_category(): void
    {
        $from = Carbon::parse('2026-01-01');
        $to = Carbon::parse('2026-12-31');
        $byCategory = $this->reportService()->expensesByCategory($from, $to);

        $this->assertArrayHasKey('pakan', $byCategory);
        $this->assertSame(450000.0, $byCategory['pakan']['total']);
        $this->assertSame(1, $byCategory['pakan']['count']);
        $this->assertSame(200000.0, $byCategory['obat']['total']);
        $this->assertSame(300000.0, $byCategory['tenaga_kerja']['total']);
        $this->assertSame(100000.0, $byCategory['transportasi']['total']);
        $this->assertSame(100000.0, $byCategory['listrik']['total']);
    }

    public function test_monthly_series_returns_data(): void
    {
        $series = $this->reportService()->monthlySeries(6);

        $this->assertSame(6, $series->count());
        $this->assertArrayHasKey('label', $series->first());
        $this->assertArrayHasKey('purchases', $series->first());
        $this->assertArrayHasKey('sales', $series->first());
        $this->assertArrayHasKey('expenses', $series->first());
    }

    public function test_profit_per_goat_returns_sales_with_snapshots(): void
    {
        $from = Carbon::parse('2026-01-01');
        $to = Carbon::parse('2026-12-31');
        $profits = $this->reportService()->profitPerGoat($from, $to);

        $this->assertSame(5, $profits->count());

        // TRX-J-2026-005 has cost_price 0 (peranakan)
        $peranakanSale = $profits->firstWhere('sale.code', 'TRX-J-2026-005');
        $this->assertSame(0.0, $peranakanSale['cost_price']);
        $this->assertSame(2400000.0, $peranakanSale['price']);
        $this->assertSame(2400000.0, $peranakanSale['profit']);
    }

    public function test_dashboard_stats_for_owner(): void
    {
        $owner = \App\Models\User::query()->where('email', 'owner@kamsya.id')->first();

        $response = $this->actingAs($owner)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('stats');
        $response->assertSee('15');
        $response->assertSee('10');
        $response->assertSee('5');
    }
}
