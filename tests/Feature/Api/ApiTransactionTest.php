<?php

namespace Tests\Feature\Api;

use App\Enums\GoatStatus;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Goat;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiTransactionTest extends TestCase
{
    private function user(string $email): User
    {
        return User::query()->where('email', $email)->firstOrFail();
    }

    public function test_can_create_purchase_with_goat_and_partial_payment(): void
    {
        Sanctum::actingAs($this->user('owner@kamsya.id'));

        $supplier = Supplier::query()->where('code', 'SUP-003')->firstOrFail();

        $response = $this->postJson('/api/v1/purchases', [
            'date' => '2026-11-05',
            'supplier_id' => $supplier->id,
            'price' => '1000000',
            'additional_cost' => '100000',
            'paid_amount' => '500000',
            'payment_method' => 'tunai',
            'notes' => 'Pembelian via API',
            'goat' => [
                'name' => 'Kambing API',
                'type' => 'Boer',
                'gender' => 'jantan',
                'birth_date' => '2025-01-01',
                'weight' => '30',
                'color' => 'Cokelat',
            ],
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.code', 'TRX-B-2026-011');
        $response->assertJsonPath('data.total', 1100000);
        $response->assertJsonPath('data.paid_amount', 500000);
        $response->assertJsonPath('data.remaining_amount', 600000);

        $purchase = Purchase::query()->where('code', 'TRX-B-2026-011')->firstOrFail();
        $this->assertSame(1100000.0, (float) $purchase->total);
        $this->assertSame(500000.0, $purchase->paidAmount());
        $this->assertSame(GoatStatus::Tersedia, $purchase->goat->status);
    }

    public function test_can_create_sale_with_cost_price_snapshot(): void
    {
        Sanctum::actingAs($this->user('owner@kamsya.id'));

        $goat = Goat::query()->where('code', 'KMB-0001')->firstOrFail();
        $customer = Customer::query()->where('code', 'CUS-001')->firstOrFail();

        $response = $this->postJson('/api/v1/sales', [
            'date' => '2026-11-05',
            'goat_id' => $goat->id,
            'customer_id' => $customer->id,
            'price' => '3000000',
            'paid_amount' => '1500000',
            'payment_method' => 'transfer',
            'notes' => 'Penjualan via API',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.code', 'TRX-J-2026-006');
        $response->assertJsonPath('data.price', 3000000);
        $response->assertJsonPath('data.cost_price', 2625000);
        $response->assertJsonPath('data.profit', 375000);

        $this->assertSame(GoatStatus::Terjual, $goat->fresh()->status);
    }

    public function test_can_record_payment_for_outstanding_purchase(): void
    {
        Sanctum::actingAs($this->user('owner@kamsya.id'));

        $purchase = Purchase::query()->where('code', 'TRX-B-2026-003')->firstOrFail();
        $this->assertSame(725000.0, $purchase->remainingAmount());

        $response = $this->postJson('/api/v1/payments', [
            'date' => '2026-11-05',
            'payable_type' => 'purchase',
            'payable_id' => $purchase->id,
            'amount' => '725000',
            'method' => 'transfer',
            'notes' => 'Pelunasan via API',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.code', 'PAY-2026-016');
        $response->assertJsonPath('data.payable_code', 'TRX-B-2026-003');
        $this->assertSame(0.0, $purchase->fresh()->remainingAmount());
    }

    public function test_overpayment_is_rejected(): void
    {
        Sanctum::actingAs($this->user('owner@kamsya.id'));

        $purchase = Purchase::query()->where('code', 'TRX-B-2026-001')->firstOrFail();

        $response = $this->postJson('/api/v1/payments', [
            'date' => '2026-11-05',
            'payable_type' => 'purchase',
            'payable_id' => $purchase->id,
            'amount' => '100000',
            'method' => 'tunai',
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors' => ['amount']]);
    }

    public function test_can_create_expense(): void
    {
        Sanctum::actingAs($this->user('admin@kamsya.id'));

        $response = $this->postJson('/api/v1/expenses', [
            'date' => '2026-11-05',
            'category' => 'pakan',
            'amount' => '75000',
            'notes' => 'Pakan tambahan via API',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.code', 'BBN-2026-006');
        $response->assertJsonPath('data.category', 'pakan');
        $this->assertSame(75000.0, (float) Expense::query()->where('code', 'BBN-2026-006')->value('amount'));
    }

    public function test_voiding_sale_restores_goat_and_payments(): void
    {
        Sanctum::actingAs($this->user('owner@kamsya.id'));

        $sale = Sale::query()->where('code', 'TRX-J-2026-001')->firstOrFail();
        $goat = $sale->goat;

        $response = $this->postJson('/api/v1/sales/'.$sale->code.'/void', [
            'reason' => 'Salah input via API',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.void_reason', 'Salah input via API');

        $this->assertNotNull($sale->fresh()->voided_at);
        $this->assertSame(GoatStatus::Tersedia, $goat->fresh()->status);

        foreach ($sale->payments as $payment) {
            $this->assertNotNull($payment->fresh()->voided_at);
        }
    }

    public function test_cannot_void_purchase_with_active_sale(): void
    {
        Sanctum::actingAs($this->user('owner@kamsya.id'));

        $purchase = Purchase::query()->where('code', 'TRX-B-2026-003')->firstOrFail();

        $response = $this->postJson('/api/v1/purchases/'.$purchase->code.'/void', [
            'reason' => 'Coba void',
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors' => ['purchase']]);
        $this->assertNull($purchase->fresh()->voided_at);
    }

    public function test_can_void_purchase_without_active_sale(): void
    {
        Sanctum::actingAs($this->user('owner@kamsya.id'));

        $purchase = Purchase::query()->where('code', 'TRX-B-2026-002')->firstOrFail();
        $goatId = $purchase->goat_id;

        $response = $this->postJson('/api/v1/purchases/'.$purchase->code.'/void', [
            'reason' => 'Pembelian dibatalkan',
        ]);

        $response->assertStatus(200);
        $this->assertNotNull($purchase->fresh()->voided_at);
        $this->assertNull(Goat::query()->find($goatId));
        $this->assertNotNull(Goat::query()->withTrashed()->find($goatId)->deleted_at);
    }

    public function test_keeper_cannot_create_transactions(): void
    {
        Sanctum::actingAs($this->user('petugas@kamsya.id'));

        $response = $this->postJson('/api/v1/expenses', [
            'date' => '2026-11-05',
            'category' => 'pakan',
            'amount' => '50000',
        ]);

        $response->assertStatus(403);
        $response->assertJsonPath('message', 'Anda tidak memiliki akses untuk tindakan ini.');
    }

    public function test_transaction_validation_messages_use_indonesian(): void
    {
        Sanctum::actingAs($this->user('owner@kamsya.id'));

        $response = $this->postJson('/api/v1/purchases', []);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Data yang dikirim tidak valid.');
        $response->assertJsonPath('errors.date.0', 'Tanggal pembelian wajib diisi.');
    }
}
