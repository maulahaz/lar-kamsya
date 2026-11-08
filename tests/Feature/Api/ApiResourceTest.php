<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiResourceTest extends TestCase
{
    private function user(string $email): User
    {
        return User::query()->where('email', $email)->firstOrFail();
    }

    public function test_dashboard_returns_stats_for_owner(): void
    {
        Sanctum::actingAs($this->user('owner@kamsya.id'));

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'greeting',
                'stats' => ['total_goats', 'available_count', 'sold_count', 'inventory_value'],
                'monthly_series',
                'recent_transactions',
                'summary',
            ],
        ]);
        $response->assertJsonPath('data.stats.total_goats', 15);
        $response->assertJsonPath('data.stats.available_count', 10);
        $response->assertJsonPath('data.stats.sold_count', 5);
    }

    public function test_keeper_dashboard_has_no_financial_summary(): void
    {
        Sanctum::actingAs($this->user('petugas@kamsya.id'));

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertStatus(200);
        $response->assertJsonPath('data.summary', null);
    }

    public function test_options_endpoint_returns_reference_values(): void
    {
        Sanctum::actingAs($this->user('petugas@kamsya.id'));

        $response = $this->getJson('/api/v1/options');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'user_roles',
                'goat_statuses',
                'goat_genders',
                'goat_origins',
                'expense_categories',
                'payment_methods',
                'report_tabs',
            ],
        ]);
    }

    public function test_goats_index_supports_filters_and_pagination(): void
    {
        Sanctum::actingAs($this->user('petugas@kamsya.id'));

        $response = $this->getJson('/api/v1/goats?status=tersedia&type=Boer&per_page=5');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [['id', 'code', 'barcode', 'name', 'status', 'status_label']],
            'meta' => ['current_page', 'per_page', 'total'],
            'links',
        ]);
        $this->assertLessThanOrEqual(5, count($response->json('data')));
        $this->assertSame('tersedia', $response->json('data.0.status'));
    }

    public function test_goat_detail_uses_document_code_binding(): void
    {
        Sanctum::actingAs($this->user('petugas@kamsya.id'));

        $response = $this->getJson('/api/v1/goats/KMB-0001');

        $response->assertStatus(200);
        $response->assertJsonPath('data.code', 'KMB-0001');
        $response->assertJsonPath('data.barcode', '899100001');
        $response->assertJsonStructure(['data' => ['purchase', 'mother', 'children', 'sales']]);
    }

    public function test_unknown_goat_returns_indonesian_not_found_message(): void
    {
        Sanctum::actingAs($this->user('petugas@kamsya.id'));

        $response = $this->getJson('/api/v1/goats/KMB-9999');

        $response->assertStatus(404);
        $response->assertJsonPath('message', 'Data tidak ditemukan.');
    }

    public function test_barcode_lookup_finds_a_goat(): void
    {
        Sanctum::actingAs($this->user('petugas@kamsya.id'));

        $response = $this->getJson('/api/v1/goats/barcode/899100001');

        $response->assertStatus(200);
        $response->assertJsonPath('data.code', 'KMB-0001');
    }

    public function test_barcode_lookup_returns_404_when_missing(): void
    {
        Sanctum::actingAs($this->user('petugas@kamsya.id'));

        $response = $this->getJson('/api/v1/goats/barcode/000000000');

        $response->assertStatus(404);
        $response->assertJsonPath('message', 'Kambing tidak ditemukan.');
    }

    public function test_scan_records_history(): void
    {
        Sanctum::actingAs($this->user('petugas@kamsya.id'));

        $response = $this->getJson('/api/v1/scan?code=899100001');

        $response->assertStatus(200);
        $response->assertJsonPath('data.found', true);
        $response->assertJsonPath('data.goat.code', 'KMB-0001');

        $this->assertDatabaseHas('scan_logs', [
            'barcode' => '899100001',
            'found' => true,
        ]);

        $history = $this->getJson('/api/v1/scan/history');
        $history->assertStatus(200);
        $history->assertJsonPath('data.0.barcode', '899100001');
    }

    public function test_suppliers_and_customers_can_be_listed_and_created(): void
    {
        Sanctum::actingAs($this->user('admin@kamsya.id'));

        $this->getJson('/api/v1/suppliers')
            ->assertStatus(200)
            ->assertJsonPath('data.0.code', 'SUP-001')
            ->assertJsonStructure(['data' => [['id', 'code', 'name', 'purchases_count']], 'meta']);

        $created = $this->postJson('/api/v1/suppliers', [
            'name' => 'Supplier API',
            'phone' => '0812-1234-5678',
        ]);

        $created->assertStatus(201);
        $created->assertJsonPath('data.code', 'SUP-006');

        $this->getJson('/api/v1/customers')
            ->assertStatus(200)
            ->assertJsonPath('data.0.code', 'CUS-001');

        $customer = $this->postJson('/api/v1/customers', [
            'name' => 'Customer API',
            'phone' => '0812-8765-4321',
        ]);

        $customer->assertStatus(201);
        $customer->assertJsonPath('data.code', 'CUS-009');
    }

    public function test_owner_can_read_settings_users_and_activity(): void
    {
        Sanctum::actingAs($this->user('owner@kamsya.id'));

        $this->getJson('/api/v1/settings')
            ->assertStatus(200)
            ->assertJsonPath('data.settings.farm_name', 'Kamsya Farm');

        $this->getJson('/api/v1/users')
            ->assertStatus(200)
            ->assertJsonStructure(['data' => [['id', 'name', 'email', 'role']], 'counts']);

        $this->getJson('/api/v1/activity-logs')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'meta', 'filters']);
    }

    public function test_inactive_token_is_rejected(): void
    {
        $user = $this->user('petugas@kamsya.id');
        $user->forceFill(['is_active' => false])->save();
        $token = $user->createToken('flutter-test')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/v1/auth/me');

        $response->assertStatus(403);
        $response->assertJsonPath('message', 'Akun Anda tidak aktif. Hubungi Owner untuk mengaktifkan kembali.');
    }
}
