<?php

namespace Database\Seeders;

use App\Enums\ExpenseCategory;
use App\Enums\GoatGender;
use App\Enums\GoatOrigin;
use App\Enums\GoatStatus;
use App\Enums\PaymentMethod;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Goat;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\User;
use App\Services\SequenceService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    public function run(SequenceService $sequences): void
    {
        $owner = User::query()->where('email', 'owner@kamsya.id')->firstOrFail();

        $this->clearData();

        Setting::query()->updateOrCreate([], [
            'farm_name' => 'Kamsya Farm',
            'owner_name' => 'Admin Kamsya',
            'phone' => '0812-9000-1234',
            'address' => 'Jl. Raya Peternakan No. 18, Bandung',
            'barcode_prefix' => '8991',
            'low_stock_limit' => 5,
        ]);

        $suppliers = $this->seedSuppliers();
        $customers = $this->seedCustomers();
        $goats = $this->seedPurchasedGoats($suppliers);
        $purchases = $this->seedPurchases($suppliers, $goats, $owner->id);
        $this->seedOffspring($goats);
        $sales = $this->seedSales($customers, $goats, $owner->id);
        $this->seedPayments($purchases, $sales, $owner->id);
        $this->seedExpenses($owner->id);

        $sequences->sync([
            'goat' => 15,
            'supplier' => 5,
            'customer' => 8,
            'purchase:2026' => 10,
            'sale:2026' => 5,
            'payment:2026' => 15,
            'expense:2026' => 5,
        ]);
    }

    private function clearData(): void
    {
        $tables = ['activity_logs', 'scan_logs', 'payments', 'expenses', 'sales', 'purchases', 'goats', 'customers', 'suppliers'];

        DB::table('goats')->update(['mother_id' => null]);

        foreach ($tables as $table) {
            DB::table($table)->delete();
        }
    }

    /**
     * @return array<string, Supplier>
     */
    private function seedSuppliers(): array
    {
        $rows = [
            'SUP-001' => ['Pak Hadi', '0812-3456-7890', 'Jl. Raya Lembang No. 45, Bandung', 'Supplier langganan kambing Boer. Pengiriman cepat dan sehat.', '2026-05-01 08:00:00'],
            'SUP-002' => ['Pak Budi', '0813-2211-4455', 'Jl. Cikajang No. 12, Garut', 'Menyediakan kambing Etawa dan Jawa.', '2026-05-02 08:00:00'],
            'SUP-003' => ['CV Ternak Sejahtera', '022-8877-6655', 'Kawasan Peternakan Cimahi, Cimahi', 'Pengiriman minimal 3 ekor. Tersedia pilihan kambing muda.', '2026-05-03 08:00:00'],
            'SUP-004' => ['Pak Ahmad', '0857-9900-1122', 'Jl. Singaparna KM 5, Tasikmalaya', 'Kambing Boer bobot besar, cocok untuk qurban.', '2026-05-04 08:00:00'],
            'SUP-005' => ['Peternakan Makmur', '0812-7788-9900', 'Jl. Pagaden Barat, Subang', 'Spesialis kambing Kacang dan peranakan lokal.', '2026-05-05 08:00:00'],
        ];

        $suppliers = [];
        foreach ($rows as $code => [$name, $phone, $address, $notes, $createdAt]) {
            $supplier = Supplier::query()->create(compact('code', 'name', 'phone', 'address', 'notes'));
            $this->stamp($supplier, $createdAt);
            $suppliers[$code] = $supplier;
        }

        return $suppliers;
    }

    /**
     * @return array<string, Customer>
     */
    private function seedCustomers(): array
    {
        $rows = [
            'CUS-001' => ['Haji Umar', '0812-1111-2222', 'Jl. Dipatiukur No. 8, Bandung', 'Pembeli rutin untuk kebutuhan qurban.', '2026-06-01 08:00:00'],
            'CUS-002' => ['Bu Rina', '0813-3333-4444', 'Jl. Sukajadi No. 21, Bandung', 'Membeli untuk acara keluarga.', '2026-06-02 08:00:00'],
            'CUS-003' => ['Warung Sate Pak Joko', '0821-5555-6666', 'Jl. Cihampelas No. 102, Bandung', 'Order rutin setiap bulan untuk kebutuhan warung.', '2026-06-03 08:00:00'],
            'CUS-004' => ['Pak Dedi', '0857-7777-8888', 'Jl. Kopo Sayati, Bandung', 'Pembeli kambing bobot sedang.', '2026-06-04 08:00:00'],
            'CUS-005' => ['Bu Siti', '0812-9999-0000', 'Jl. Melong Raya, Cimahi', 'Pelanggan baru dari rekomendasi tetangga.', '2026-06-05 08:00:00'],
            'CUS-006' => ['Mas Rudi', '0896-1212-3434', 'Jl. Cileunyi Raya, Bandung', 'Membeli untuk hajatan.', '2026-06-06 08:00:00'],
            'CUS-007' => ['Pak Tono', '0818-5656-7878', 'Perum Griya Asri, Tasikmalaya', 'Menyukai kambing peranakan yang sehat.', '2026-06-07 08:00:00'],
            'CUS-008' => ['RM Padang Sederhana', '022-4234-5678', 'Jl. Pasir Kaliki No. 150, Bandung', 'Pembelian untuk kebutuhan restoran.', '2026-06-08 08:00:00'],
        ];

        $customers = [];
        foreach ($rows as $code => [$name, $phone, $address, $notes, $createdAt]) {
            $customer = Customer::query()->create(compact('code', 'name', 'phone', 'address', 'notes'));
            $this->stamp($customer, $createdAt);
            $customers[$code] = $customer;
        }

        return $customers;
    }

    /**
     * @param  array<string, Supplier>  $suppliers
     * @return array<string, Goat>
     */
    private function seedPurchasedGoats(array $suppliers): array
    {
        $rows = [
            ['KMB-0001', '899100001', 'Si Jago', 'Boer Cross', GoatGender::Jantan, '2025-10-10', 35, 'Cokelat', '2026-10-10', GoatStatus::Tersedia, 'Kondisi sehat, sudah vaksin. Aktif dan nafsu makan baik.'],
            ['KMB-0002', '899100002', 'Mawar', 'Etawa', GoatGender::Betina, '2024-08-15', 42, 'Putih', '2026-08-24', GoatStatus::Tersedia, 'Induk produktif, pernah melahirkan tiga kali.'],
            ['KMB-0003', '899100003', 'Si Kecil', 'Jawa', GoatGender::Jantan, '2025-06-05', 28, 'Hitam', '2026-06-05', GoatStatus::Terjual, 'Terjual untuk kebutuhan warung sate.'],
            ['KMB-0004', '899100004', 'Raja', 'Boer', GoatGender::Jantan, '2025-03-18', 48, 'Cokelat tua', '2026-07-18', GoatStatus::Tersedia, 'Postur besar dan sehat. Cocok untuk qurban.'],
            ['KMB-0005', '899100005', 'Melati', 'Kacang', GoatGender::Betina, '2024-05-12', 24, 'Putih kecokelatan', '2026-05-12', GoatStatus::Tersedia, 'Induk jinak dan rajin menyusui.'],
            ['KMB-0006', '899100006', 'Bunga', 'Boer Cross', GoatGender::Betina, '2025-02-02', 38, 'Cokelat muda', '2026-09-02', GoatStatus::Terjual, 'Terjual ke Bu Rina.'],
            ['KMB-0007', '899100007', 'Si Putih', 'Etawa', GoatGender::Jantan, '2025-05-20', 31, 'Putih', '2026-05-20', GoatStatus::Tersedia, 'Telinga panjang, kondisi sehat.'],
            ['KMB-0008', '899100008', 'Lestari', 'Jawa', GoatGender::Betina, '2025-01-15', 26, 'Hitam', '2026-07-15', GoatStatus::Terjual, 'Terjual untuk qurban Haji Umar.'],
            ['KMB-0009', '899100009', 'Garuda', 'Boer', GoatGender::Jantan, '2025-01-01', 52, 'Putih kecokelatan', '2026-08-01', GoatStatus::Tersedia, 'Kambing unggulan dengan bobot terbesar di kandang.'],
            ['KMB-0010', '899100010', 'Si Hitam', 'Kacang', GoatGender::Jantan, '2025-09-09', 22, 'Hitam', '2026-09-09', GoatStatus::Terjual, 'Terjual ke Pak Dedi.'],
        ];

        $goats = [];
        foreach ($rows as [$code, $barcode, $name, $type, $gender, $birthDate, $weight, $color, $entryDate, $status, $notes]) {
            $goat = Goat::query()->create([
                'code' => $code,
                'barcode' => $barcode,
                'name' => $name,
                'type' => $type,
                'gender' => $gender,
                'birth_date' => $birthDate,
                'weight' => $weight,
                'color' => $color,
                'origin' => GoatOrigin::Pembelian,
                'entry_date' => $entryDate,
                'status' => $status,
                'notes' => $notes,
            ]);
            $this->stamp($goat, $entryDate.' 09:00:00');
            $goats[$code] = $goat;
        }

        return $goats;
    }

    /**
     * @param  array<string, Supplier>  $suppliers
     * @param  array<string, Goat>  $goats
     * @return array<string, Purchase>
     */
    private function seedPurchases(array $suppliers, array $goats, int $ownerId): array
    {
        $rows = [
            ['TRX-B-2026-001', '2026-05-12', 'SUP-005', 'KMB-0005', 1350000, 150000, 'Termasuk biaya transportasi dari Subang.'],
            ['TRX-B-2026-002', '2026-05-20', 'SUP-003', 'KMB-0007', 1900000, 100000, 'Biaya karantina dan pemeriksaan kesehatan.'],
            ['TRX-B-2026-003', '2026-06-05', 'SUP-003', 'KMB-0003', 1600000, 125000, 'Pembelian rutin dari CV Ternak Sejahtera.'],
            ['TRX-B-2026-004', '2026-07-15', 'SUP-002', 'KMB-0008', 1450000, 75000, 'Biaya transportasi Garut - Bandung.'],
            ['TRX-B-2026-005', '2026-07-18', 'SUP-004', 'KMB-0004', 4500000, 250000, 'Kambing Boer bobot besar.'],
            ['TRX-B-2026-006', '2026-08-01', 'SUP-004', 'KMB-0009', 5100000, 300000, 'Termasuk asuransi pengiriman.'],
            ['TRX-B-2026-007', '2026-08-24', 'SUP-002', 'KMB-0002', 3200000, 100000, 'Induk Etawa produktif.'],
            ['TRX-B-2026-008', '2026-09-02', 'SUP-001', 'KMB-0006', 2900000, 150000, 'Biaya pemeliharaan awal di kandang.'],
            ['TRX-B-2026-009', '2026-09-09', 'SUP-005', 'KMB-0010', 1200000, 50000, 'Pembelian kambing Kacang muda.'],
            ['TRX-B-2026-010', '2026-10-10', 'SUP-001', 'KMB-0001', 2500000, 125000, 'Sudah vaksin dan siap masuk kandang.'],
        ];

        $purchases = [];
        foreach ($rows as [$code, $date, $supplierCode, $goatCode, $price, $additional, $notes]) {
            $purchase = Purchase::query()->create([
                'code' => $code,
                'date' => $date,
                'supplier_id' => $suppliers[$supplierCode]->id,
                'goat_id' => $goats[$goatCode]->id,
                'price' => $price,
                'additional_cost' => $additional,
                'total' => $price + $additional,
                'notes' => $notes,
                'created_by' => $ownerId,
            ]);
            $this->stamp($purchase, $date.' 10:00:00');
            $purchases[$code] = $purchase;
        }

        return $purchases;
    }

    /**
     * @param  array<string, Goat>  $goats
     */
    private function seedOffspring(array &$goats): void
    {
        $rows = [
            ['KMB-0011', '899100011', 'Mutiara', 'Etawa', GoatGender::Betina, '2026-03-15', 18, 'Putih', 'KMB-0002', '2026-03-15', GoatStatus::Tersedia, 'Anak pertama dari induk Mawar. Lahir sehat di kandang.'],
            ['KMB-0012', '899100012', 'Permata', 'Etawa', GoatGender::Jantan, '2026-07-20', 9, 'Cokelat muda', 'KMB-0002', '2026-07-20', GoatStatus::Tersedia, 'Anak Mawar, aktif dan lincah.'],
            ['KMB-0013', '899100013', 'Si Manis', 'Kacang', GoatGender::Betina, '2026-04-05', 15, 'Putih', 'KMB-0005', '2026-04-05', GoatStatus::Tersedia, 'Anak Melati, sehat dan jinak.'],
            ['KMB-0014', '899100014', 'Bintang', 'Kacang', GoatGender::Jantan, '2026-09-01', 7, 'Hitam', 'KMB-0005', '2026-09-01', GoatStatus::Tersedia, 'Anak Melati yang lahir di musim kemarau.'],
            ['KMB-0015', '899100015', 'Ceria', 'Boer Cross', GoatGender::Betina, '2026-06-12', 20, 'Cokelat', 'KMB-0006', '2026-06-12', GoatStatus::Terjual, 'Anak Bunga. Terjual ke Pak Tono.'],
        ];

        foreach ($rows as [$code, $barcode, $name, $type, $gender, $birthDate, $weight, $color, $motherCode, $entryDate, $status, $notes]) {
            $goat = Goat::query()->create([
                'code' => $code,
                'barcode' => $barcode,
                'name' => $name,
                'type' => $type,
                'gender' => $gender,
                'birth_date' => $birthDate,
                'weight' => $weight,
                'color' => $color,
                'origin' => GoatOrigin::Peranakan,
                'mother_id' => $goats[$motherCode]->id,
                'entry_date' => $entryDate,
                'status' => $status,
                'notes' => $notes,
            ]);
            $this->stamp($goat, $entryDate.' 09:00:00');
            $goats[$code] = $goat;
        }
    }

    /**
     * @param  array<string, Customer>  $customers
     * @param  array<string, Goat>  $goats
     * @return array<string, Sale>
     */
    private function seedSales(array $customers, array $goats, int $ownerId): array
    {
        $rows = [
            ['TRX-J-2026-001', '2026-09-15', 'KMB-0008', 'CUS-001', 2100000, 1525000, 'Untuk kebutuhan qurban.'],
            ['TRX-J-2026-002', '2026-10-02', 'KMB-0003', 'CUS-003', 2350000, 1725000, 'Order rutin Warung Sate Pak Joko.'],
            ['TRX-J-2026-003', '2026-10-18', 'KMB-0006', 'CUS-002', 3800000, 3050000, 'Pembeli mengambil langsung di kandang.'],
            ['TRX-J-2026-004', '2026-10-28', 'KMB-0010', 'CUS-004', 1750000, 1250000, 'Pembayaran tunai.'],
            ['TRX-J-2026-005', '2026-11-04', 'KMB-0015', 'CUS-007', 2400000, 0, 'Kambing peranakan, tanpa harga beli.'],
        ];

        $sales = [];
        foreach ($rows as [$code, $date, $goatCode, $customerCode, $price, $costPrice, $notes]) {
            $sale = Sale::query()->create([
                'code' => $code,
                'date' => $date,
                'goat_id' => $goats[$goatCode]->id,
                'customer_id' => $customers[$customerCode]->id,
                'price' => $price,
                'cost_price' => $costPrice,
                'notes' => $notes,
                'created_by' => $ownerId,
            ]);
            $this->stamp($sale, $date.' 13:00:00');
            $sales[$code] = $sale;
        }

        return $sales;
    }

    /**
     * @param  array<string, Purchase>  $purchases
     * @param  array<string, Sale>  $sales
     */
    private function seedPayments(array $purchases, array $sales, int $ownerId): void
    {
        $rows = [
            ['2026-05-12', 'purchase', 'TRX-B-2026-001', 1500000, PaymentMethod::Transfer, 'Pelunasan pembelian Melati.'],
            ['2026-05-20', 'purchase', 'TRX-B-2026-002', 2000000, PaymentMethod::Transfer, 'Pelunasan pembelian Si Putih.'],
            ['2026-06-05', 'purchase', 'TRX-B-2026-003', 1000000, PaymentMethod::Tunai, 'Pembayaran sebagian.'],
            ['2026-07-15', 'purchase', 'TRX-B-2026-004', 1525000, PaymentMethod::Transfer, 'Pelunasan pembelian Lestari.'],
            ['2026-07-18', 'purchase', 'TRX-B-2026-005', 4750000, PaymentMethod::Transfer, 'Pelunasan pembelian Raja.'],
            ['2026-08-01', 'purchase', 'TRX-B-2026-006', 5400000, PaymentMethod::Transfer, 'Pelunasan pembelian Garuda.'],
            ['2026-08-24', 'purchase', 'TRX-B-2026-007', 2000000, PaymentMethod::Tunai, 'Pembayaran sebagian induk Etawa.'],
            ['2026-09-02', 'purchase', 'TRX-B-2026-008', 3050000, PaymentMethod::Transfer, 'Pelunasan pembelian Bunga.'],
            ['2026-09-09', 'purchase', 'TRX-B-2026-009', 1250000, PaymentMethod::Tunai, 'Pelunasan pembelian Si Hitam.'],
            ['2026-09-15', 'sale', 'TRX-J-2026-001', 2100000, PaymentMethod::Transfer, 'Pelunasan dari Haji Umar.'],
            ['2026-10-02', 'sale', 'TRX-J-2026-002', 1400000, PaymentMethod::Transfer, 'Pembayaran sebagian Warung Sate.'],
            ['2026-10-10', 'purchase', 'TRX-B-2026-010', 2250000, PaymentMethod::Tunai, 'Pembayaran sebagian pembelian Si Jago.'],
            ['2026-10-18', 'sale', 'TRX-J-2026-003', 3800000, PaymentMethod::Transfer, 'Pelunasan dari Bu Rina.'],
            ['2026-10-28', 'sale', 'TRX-J-2026-004', 1750000, PaymentMethod::Tunai, 'Pelunasan dari Pak Dedi.'],
            ['2026-11-04', 'sale', 'TRX-J-2026-005', 1950000, PaymentMethod::Qris, 'Pembayaran sebagian dari Pak Tono.'],
        ];

        foreach ($rows as $index => [$date, $type, $code, $amount, $method, $notes]) {
            $payable = $type === 'purchase' ? $purchases[$code] : $sales[$code];
            $prefix = 'PAY-2026-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);

            $payment = Payment::query()->create([
                'code' => $prefix,
                'date' => $date,
                'payable_type' => $type,
                'payable_id' => $payable->id,
                'amount' => $amount,
                'method' => $method,
                'notes' => $notes,
                'created_by' => $ownerId,
            ]);
            $this->stamp($payment, $date.' 14:00:00');
        }
    }

    private function seedExpenses(int $ownerId): void
    {
        $rows = [
            ['BBN-2026-001', '2026-10-05', ExpenseCategory::Pakan, 450000, 'Pakan hijauan dan konsentrat untuk seluruh kandang.'],
            ['BBN-2026-002', '2026-10-12', ExpenseCategory::Obat, 200000, 'Vitamin, obat cacing, dan desinfektan kandang.'],
            ['BBN-2026-003', '2026-10-20', ExpenseCategory::TenagaKerja, 300000, 'Upah harian petugas kandang.'],
            ['BBN-2026-004', '2026-10-26', ExpenseCategory::Transportasi, 100000, 'Transportasi pengangkutan pakan.'],
            ['BBN-2026-005', '2026-11-02', ExpenseCategory::Listrik, 100000, 'Listrik dan air kandang bulan November.'],
        ];

        foreach ($rows as [$code, $date, $category, $amount, $notes]) {
            $expense = Expense::query()->create([
                'code' => $code,
                'date' => $date,
                'category' => $category,
                'amount' => $amount,
                'notes' => $notes,
                'created_by' => $ownerId,
            ]);
            $this->stamp($expense, $date.' 16:00:00');
        }
    }

    private function stamp(Model $model, string $timestamp): void
    {
        $time = Carbon::parse($timestamp);
        $model->forceFill(['created_at' => $time, 'updated_at' => $time])->saveQuietly();
    }
}
