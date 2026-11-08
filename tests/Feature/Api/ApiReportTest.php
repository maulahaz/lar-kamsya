<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiReportTest extends TestCase
{
    private function owner(): User
    {
        return User::query()->where('email', 'owner@kamsya.id')->firstOrFail();
    }

    public function test_summary_matches_seed_reference_values(): void
    {
        Sanctum::actingAs($this->owner());

        $response = $this->getJson('/api/v1/reports/summary?from=2026-01-01&to=2026-12-31');

        $response->assertStatus(200);
        $response->assertJsonPath('data.from', '2026-01-01');
        $response->assertJsonPath('data.to', '2026-12-31');
        $response->assertJsonPath('data.summary.sales_total', 12400000);
        $response->assertJsonPath('data.summary.cogs', 7550000);
        $response->assertJsonPath('data.summary.gross_profit', 4850000);
        $response->assertJsonPath('data.summary.expense_total', 1150000);
        $response->assertJsonPath('data.summary.net_profit', 3700000);
        $response->assertJsonPath('data.summary.inventory_value', 19575000);
        $response->assertJsonPath('data.summary.receivables', 1400000);
        $response->assertJsonPath('data.summary.payables', 2400000);
    }

    public function test_all_report_tabs_return_json_payloads(): void
    {
        Sanctum::actingAs($this->owner());

        $tabs = [
            'laba-rugi', 'arus-kas', 'piutang', 'utang',
            'laba-kambing', 'penjualan', 'pembelian', 'stok',
        ];

        foreach ($tabs as $tab) {
            $response = $this->getJson('/api/v1/reports?tab='.$tab.'&from=2026-01-01&to=2026-12-31');
            $response->assertStatus(200, "Report tab $tab should return 200");
            $response->assertJsonPath('data.tab', $tab);
            $response->assertJsonStructure(['data' => ['tab', 'from', 'to', 'summary']]);
        }
    }

    public function test_report_tab_payloads_have_expected_keys(): void
    {
        Sanctum::actingAs($this->owner());

        $expectations = [
            'laba-rugi' => ['sales', 'expenses_by_category', 'expenses'],
            'arus-kas' => ['payments_in', 'payments_out', 'expenses'],
            'piutang' => ['aging'],
            'utang' => ['aging'],
            'laba-kambing' => ['profits'],
            'penjualan' => ['sales', 'by_customer', 'by_type', 'by_month'],
            'pembelian' => ['purchases', 'by_supplier', 'by_month'],
            'stok' => ['inventory'],
        ];

        foreach ($expectations as $tab => $keys) {
            $response = $this->getJson('/api/v1/reports?tab='.$tab.'&from=2026-01-01&to=2026-12-31');
            $response->assertStatus(200);

            foreach ($keys as $key) {
                $this->assertArrayHasKey($key, $response->json('data'), "Tab $tab should contain $key");
            }
        }
    }

    public function test_piutang_and_utang_aging_totals(): void
    {
        Sanctum::actingAs($this->owner());

        $piutang = $this->getJson('/api/v1/reports?tab=piutang&from=2026-01-01&to=2026-11-07');
        $piutang->assertStatus(200);
        $piutang->assertJsonPath('data.aging.total', 1400000);
        $piutang->assertJsonPath('data.aging.buckets.0-30', 450000);
        $piutang->assertJsonPath('data.aging.buckets.31-60', 950000);

        $utang = $this->getJson('/api/v1/reports?tab=utang&from=2026-01-01&to=2026-11-07');
        $utang->assertStatus(200);
        $utang->assertJsonPath('data.aging.total', 2400000);
    }

    public function test_unknown_tab_falls_back_to_profit_loss(): void
    {
        Sanctum::actingAs($this->owner());

        $response = $this->getJson('/api/v1/reports?tab=invalid');

        $response->assertStatus(200);
        $response->assertJsonPath('data.tab', 'laba-rugi');
    }

    public function test_keeper_cannot_access_reports(): void
    {
        Sanctum::actingAs(User::query()->where('email', 'petugas@kamsya.id')->firstOrFail());

        $this->getJson('/api/v1/reports')->assertStatus(403);
        $this->getJson('/api/v1/reports/summary')->assertStatus(403);
    }
}
