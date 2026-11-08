<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Goat;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use App\Enums\GoatStatus;
use App\Enums\PaymentMethod;
use App\Enums\UserRole;
use Tests\TestCase;

class TransactionFlowTest extends TestCase
{
    protected function assertSameWithFloat(float $expected, mixed $actual): void
    {
        $this->assertSame($expected, (float) $actual);
    }

    private function owner(): User
    {
        return User::query()->where('email', 'owner@kamsya.id')->first();
    }

    public function test_can_create_purchase_with_goat_and_partial_payment(): void
    {
        $supplier = Supplier::query()->first();

        $response = $this->actingAs($this->owner())->post(route('purchases.store'), [
            'date' => '2026-11-05',
            'supplier_id' => $supplier->id,
            'price' => '1000000',
            'additional_cost' => '100000',
            'paid_amount' => '500000',
            'payment_method' => PaymentMethod::Tunai->value,
            'notes' => 'Test pembelian',
            'goat' => [
                'name' => 'Kambing Uji',
                'type' => 'Boer',
                'gender' => 'jantan',
                'birth_date' => '2025-01-01',
                'weight' => '30',
                'color' => 'Cokelat',
                'notes' => 'Test goat',
            ],
        ]);

        $response->assertRedirect(route('purchases.show', \App\Models\Purchase::query()->latest()->first()));
        $response->assertSessionHas('success');

        $purchase = Purchase::query()->latest()->first();
        $this->assertNotNull($purchase);
        $this->assertSameWithFloat(1000000.0, $purchase->price);
        $this->assertSameWithFloat(100000.0, $purchase->additional_cost);
        $this->assertSameWithFloat(1100000.0, $purchase->total);
        $this->assertSameWithFloat(500000.0, $purchase->paidAmount());
        $this->assertSameWithFloat(600000.0, $purchase->remainingAmount());

        $goat = Goat::query()->where('name', 'Kambing Uji')->first();
        $this->assertNotNull($goat);
        $this->assertSame(GoatStatus::Tersedia, $goat->status);
        $this->assertSame($purchase->id, $goat->purchase->id);

        // Payment should exist
        $payment = Payment::query()->where('payable_type', 'purchase')->where('payable_id', $purchase->id)->first();
        $this->assertNotNull($payment);
        $this->assertSameWithFloat(500000.0, $payment->amount);
    }

    public function test_cannot_pay_more_than_purchase_total(): void
    {
        $supplier = Supplier::query()->first();

        $response = $this->actingAs($this->owner())->from(route('purchases.create'))->post(route('purchases.store'), [
            'date' => '2026-11-05',
            'supplier_id' => $supplier->id,
            'price' => '1000000',
            'additional_cost' => '0',
            'paid_amount' => '2000000',
            'payment_method' => PaymentMethod::Tunai->value,
            'goat' => [
                'name' => 'Overpaid Goat',
                'type' => 'Boer',
                'gender' => 'betina',
                'weight' => '25',
            ],
        ]);

        $response->assertRedirect(route('purchases.create'));
        $response->assertSessionHasErrors('paid_amount');
    }

    public function test_can_create_sale_with_cost_price_snapshot(): void
    {
        $goat = Goat::query()->where('status', GoatStatus::Tersedia->value)->where('origin', 'pembelian')->first();
        $customer = Customer::query()->first();

        $response = $this->actingAs($this->owner())->post(route('sales.store'), [
            'date' => '2026-11-05',
            'goat_id' => $goat->id,
            'customer_id' => $customer->id,
            'price' => '3000000',
            'paid_amount' => '1500000',
            'payment_method' => PaymentMethod::Transfer->value,
            'notes' => 'Test penjualan',
        ]);

        $response->assertRedirect(route('sales.show', Sale::query()->latest()->first()));
        $response->assertSessionHas('success');

        $sale = Sale::query()->latest()->first();
        $this->assertNotNull($sale);
        $this->assertSameWithFloat(3000000.0, $sale->price);
        // Cost price should be snapshot from purchase total
        $this->assertSame((float) $goat->purchase->total, (float) $sale->cost_price);

        // Goat should be marked as sold
        $goat->refresh();
        $this->assertSame(GoatStatus::Terjual, $goat->status);

        // Payment should exist for partial payment
        $payment = Payment::query()->where('payable_type', 'sale')->where('payable_id', $sale->id)->first();
        $this->assertNotNull($payment);
        $this->assertSameWithFloat(1500000.0, $payment->amount);
    }

    public function test_cannot_sell_sold_goat(): void
    {
        $goat = Goat::query()->where('status', GoatStatus::Terjual->value)->first();
        $customer = Customer::query()->first();

        $this->assertNotNull($goat);

        $response = $this->actingAs($this->owner())->from(route('sales.create'))->post(route('sales.store'), [
            'date' => '2026-11-05',
            'goat_id' => $goat->id,
            'customer_id' => $customer->id,
            'price' => '3000000',
            'notes' => 'Attempt to resell',
        ]);

        $response->assertRedirect(route('sales.create'));
        $response->assertSessionHasErrors('goat_id');
    }

    public function test_voiding_sale_restores_goat_to_available(): void
    {
        $sale = Sale::query()->first();
        $goat = $sale->goat;

        $this->assertSame(GoatStatus::Terjual, $goat->status);

        $response = $this->actingAs($this->owner())->post(route('sales.void', $sale), [
            'reason' => 'Test void',
        ]);

        $response->assertRedirect(route('sales.show', $sale));
        $response->assertSessionHas('success');

        $sale->refresh();
        $this->assertNotNull($sale->voided_at);

        $goat->refresh();
        $this->assertSame(GoatStatus::Tersedia, $goat->status);
    }

    public function test_voiding_sale_also_voids_its_payments(): void
    {
        $sale = Sale::query()->whereHas('activePayments')->first();

        $this->assertNotNull($sale);
        $this->assertGreaterThan(0, $sale->activePayments->count());

        $response = $this->actingAs($this->owner())->post(route('sales.void', $sale), [
            'reason' => 'Test void with payments',
        ]);

        $response->assertRedirect(route('sales.show', $sale));

        foreach ($sale->payments as $payment) {
            $this->assertNotNull($payment->fresh()->voided_at);
        }
    }

    public function test_cannot_void_already_voided_sale(): void
    {
        $sale = Sale::query()->first();

        // First void
        $this->actingAs($this->owner())->post(route('sales.void', $sale), [
            'reason' => 'First void',
        ]);

        // Second void should fail
        $response = $this->actingAs($this->owner())->from(route('sales.show', $sale))->post(route('sales.void', $sale), [
            'reason' => 'Second void',
        ]);

        $response->assertRedirect(route('sales.show', $sale));
        $response->assertSessionHasErrors('sale');
    }

    public function test_can_create_payment_for_outstanding_purchase(): void
    {
        // TRX-B-2026-003 has remaining balance 725000
        $purchase = Purchase::query()->where('code', 'TRX-B-2026-003')->first();
        $remaining = $purchase->remainingAmount();

        $this->assertGreaterThan(0, $remaining);

        $response = $this->actingAs($this->owner())->from(route('purchases.show', $purchase))->post(route('payments.store'), [
            'date' => '2026-11-05',
            'payable_type' => 'purchase',
            'payable_id' => $purchase->id,
            'amount' => $remaining,
            'method' => PaymentMethod::Transfer->value,
            'notes' => 'Test payment',
        ]);

        $response->assertRedirect(route('purchases.show', $purchase));
        $response->assertSessionHas('success');

        $purchase->refresh();
        $this->assertSameWithFloat(0.0, $purchase->remainingAmount());
    }

    public function test_cannot_create_payment_exceeding_remaining_amount(): void
    {
        // TRX-B-2026-001 is fully paid (remaining 0)
        $purchase = Purchase::query()->where('code', 'TRX-B-2026-001')->first();
        $remaining = $purchase->remainingAmount();

        $this->assertSameWithFloat(0.0, $remaining);

        $response = $this->actingAs($this->owner())->from(route('purchases.show', $purchase))->post(route('payments.store'), [
            'date' => '2026-11-05',
            'payable_type' => 'purchase',
            'payable_id' => $purchase->id,
            'amount' => 100000,
            'method' => PaymentMethod::Tunai->value,
            'notes' => 'Overpayment',
        ]);

        $response->assertRedirect(route('purchases.show', $purchase));
        $response->assertSessionHasErrors('amount');
    }

    public function test_can_void_purchase_when_no_active_sales(): void
    {
        // Find a purchased goat that hasn't been sold and has no children
        // TRX-B-2026-002: KMB-0007 Si Putih, no children, tersedia, no active sales
        $purchase = Purchase::query()->whereNull('voided_at')
            ->whereHas('goat', fn ($q) => $q->whereNull('deleted_at')->where('status', 'tersedia'))
            ->whereDoesntHave('goat.sales', fn ($q) => $q->whereNull('voided_at'))
            ->whereDoesntHave('goat.children')
            ->first();

        $this->assertNotNull($purchase, 'No purchasable goat found without sales or children');

        $response = $this->actingAs($this->owner())->from(route('purchases.show', $purchase))->post(route('purchases.void', $purchase), [
            'reason' => 'Test void purchase',
        ]);

        $response->assertRedirect(route('purchases.show', $purchase));
        $response->assertSessionHas('success');

        $purchase->refresh();
        $this->assertNotNull($purchase->voided_at);

        // Goat should be soft-deleted (archived)
        $this->assertNull(Goat::query()->where('id', $purchase->goat_id)->first());
        $this->assertNotNull(Goat::query()->withTrashed()->where('id', $purchase->goat_id)->first()->deleted_at);
    }

    public function test_cannot_void_purchase_with_active_sale(): void
    {
        // Find a purchase whose goat has been sold
        // TRX-B-2026-003: KMB-0003 Si Kecil, sold
        $purchase = Purchase::query()->whereNull('voided_at')
            ->whereHas('goat.sales', fn ($q) => $q->whereNull('voided_at'))
            ->first();

        $this->assertNotNull($purchase, 'No purchased goat with active sale found');

        $response = $this->actingAs($this->owner())->from(route('purchases.show', $purchase))->post(route('purchases.void', $purchase), [
            'reason' => 'Attempt void with active sale',
        ]);

        $response->assertRedirect(route('purchases.show', $purchase));
        $response->assertSessionHasErrors('purchase');

        $purchase->refresh();
        $this->assertNull($purchase->voided_at);
    }

    public function test_keeper_can_create_purchase(): void
    {
        // Keeper should NOT be able to create purchases (manage-transactions gate)
        $keeper = User::query()->where('email', 'petugas@kamsya.id')->first();
        $supplier = Supplier::query()->first();

        $response = $this->actingAs($keeper)->post(route('purchases.store'), [
            'date' => '2026-11-05',
            'supplier_id' => $supplier->id,
            'price' => '1000000',
            'goat' => [
                'name' => 'Keeper Goat',
                'type' => 'Boer',
                'gender' => 'jantan',
                'weight' => '30',
            ],
        ]);

        $response->assertStatus(403);
    }

    public function test_payment_voided_when_sale_voided(): void
    {
        $sale = Sale::query()->whereHas('activePayments')->first();
        $payment = $sale->activePayments->first();

        $this->assertNotNull($payment);
        $this->assertNull($payment->voided_at);

        $this->actingAs($this->owner())->post(route('sales.void', $sale), [
            'reason' => 'Void sale with payment',
        ]);

        $this->assertNotNull($payment->fresh()->voided_at);
    }
}
