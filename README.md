# Kamsya — Manajemen Peternakan & Jual-Beli Kambing

Aplikasi monolit **Laravel 13 + MySQL** untuk mengelola peternakan kambing: inventaris, barcode, pembelian, penjualan, pembayaran, beban, laporan keuangan, dan pengaturan. Antarmuka sepenuhnya server-side (Blade) dengan Bahasa Indonesia, format Rupiah, dan tanggal Indonesia.

> Prototipe frontend awal diarsipkan pada tag git `prototype-v1` (tidak lagi berada di working tree).

## Fitur

- **Dashboard** — ringkasan stok, penjualan, laba, piutang, utang, grafik bulanan, dan peringatan stok rendah.
- **Inventaris kambing** — data kambing, pencarian/filter, detail riwayat, arsip (soft delete), dan peranakan.
- **Scan barcode** — pencarian cepat berdasarkan barcode, dengan pencatatan log scan.
- **Cetak barcode** — pilih kambing, pilih format **Barcode atau QR Code**, pratinjau label A4, dan cetak langsung dari browser.
- **Pembelian** — pembuatan pembelian sekaligus kambing baru, biaya tambahan, pembayaran penuh/sebagian, dan void.
- **Penjualan** — penjualan kambing tersedia, snapshot HPP permanen, pembayaran penuh/sebagian, dan void.
- **Pembayaran** — pelunasan piutang/utang dengan validasi overpayment dan void.
- **Beban operasional** — kategori beban, pencatatan, dan void.
- **Supplier & customer** — master data dengan riwayat transaksi.
- **Laporan** — laba rugi, arus kas, piutang, utang, laba per kambing, penjualan, pembelian, dan valuasi stok.
  - Ekspor **CSV** dan **PDF** untuk semua tab laporan.
- **Pengaturan** — profil peternakan, ekspor/impor cadangan JSON, dan statistik data.
- **Pengguna & peran** — Owner, Admin, Petugas Kandang dengan otorisasi server-side.
- **Log aktivitas** — jejak audit aksi penting (khusus Owner).

## Peran & Hak Akses

| Peran | Dashboard | Inventaris & Scan | Cetak Barcode | Transaksi & Laporan | Pengguna/Pengaturan/Aktivitas |
| --- | :---: | :---: | :---: | :---: | :---: |
| Owner | ✅ | ✅ | ✅ | ✅ | ✅ |
| Admin | ✅ | ✅ | ✅ | ✅ | ❌ |
| Petugas Kandang | ✅ | ✅ | ✅ | ❌ | ❌ |

## Kebutuhan Sistem

- PHP **8.3+** dengan ekstensi `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `gd`/`dom` untuk PDF.
- MySQL **8.x** (nama basis data: `kamsya_db`).
- Composer 2.x.
- Tidak diperlukan Node.js/Vite; aset CSS/JS disajikan langsung dari `public/assets`.

## Instalasi

```bash
# 1. Dependensi
composer install

# 2. Konfigurasi lingkungan
copy .env.example .env        # Windows
# cp .env.example .env        # Linux/macOS
php artisan key:generate

# 3. Buat database
mysql -u root -e "CREATE DATABASE kamsya_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 4. Migrasi + data demo
php artisan migrate:fresh --seed

# 5. Jalankan
php artisan serve
```

Buka `http://localhost:8000`.

### Akun Demo

| Peran | Email | Kata sandi |
| --- | --- | --- |
| Owner | `owner@kamsya.id` | `kamsya123` |
| Admin | `admin@kamsya.id` | `kamsya123` |
| Petugas Kandang | `petugas@kamsya.id` | `kamsya123` |

> Ganti kata sandi akun demo sebelum digunakan di lingkungan nyata.

## Data Demo

`DemoDataSeeder` mengisi 5 supplier, 8 customer, 15 kambing, 10 pembelian, 5 penjualan, 15 pembayaran, dan 5 beban. Angka acuan laporan:

| Metrik | Nilai |
| --- | ---: |
| Penjualan | Rp12.400.000 |
| HPP | Rp7.550.000 |
| Laba kotor | Rp4.850.000 |
| Beban operasional | Rp1.150.000 |
| Laba bersih | Rp3.700.000 |
| Nilai stok | Rp19.575.000 |
| Piutang | Rp1.400.000 |
| Utang | Rp2.400.000 |

## API untuk Aplikasi Flutter

Selain antarmuka Blade, tersedia REST API JSON di prefix `/api/v1` untuk aplikasi Android (Flutter).
API bersifat **additive**: tidak mengubah struktur web dan memakai ulang model, service, policy,
serta gate yang sama, sehingga aturan stok, pembayaran, dan angka laporan tetap identik.

- Autentikasi token **Laravel Sanctum** (Bearer), satu token per perangkat, berlaku 30 hari.
- Semua pesan validasi/error dalam Bahasa Indonesia.
- Binding resource memakai kode dokumen (`KMB-0001`, `TRX-B-2026-001`), kecuali pembayaran dan pengguna yang memakai `id`.
- Laporan tersedia sebagai JSON (tanpa unduhan PDF/CSV).

```bash
# Login
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"owner@kamsya.id","password":"kamsya123","device_name":"flutter"}'

# Contoh request terautentikasi
curl http://localhost:8000/api/v1/dashboard -H "Authorization: Bearer <token>"
```

Dokumentasi lengkap (endpoint, payload, hak akses, catatan integrasi Flutter): [`docs/API.md`](docs/API.md).
Masa berlaku token dapat diubah lewat `SANCTUM_EXPIRATION` (menit; default `43200` = 30 hari).

## Pengujian

```bash
php artisan test
```

Pengujian menggunakan SQLite in-memory (`phpunit.xml`) dengan `RefreshDatabase`, sehingga tidak menyentuh basis data MySQL pengembangan. Cakupan tes mencakup autentikasi, otorisasi per peran, alur pembelian/penjualan/pembayaran/void, total laporan, ekspor CSV/PDF, ekspor/impor cadangan, serta seluruh endpoint API (`tests/Feature/Api`).

## Struktur Penting

```
app/
├── Enums/                 # UserRole, GoatStatus, PaymentMethod, dll.
├── Http/Controllers/      # Controller per modul (Blade)
├── Http/Controllers/Api/V1/ # Controller JSON API v1
├── Http/Middleware/       # Middleware web & API
├── Http/Requests/         # Validasi form (dipakai web & API)
├── Http/Resources/        # Serialisasi JSON API
├── Models/                # Eloquent model
├── Policies/              # Otorisasi per peran
└── Services/              # Logika transaksi, laporan, cadangan, sequence
resources/views/           # Blade: dashboard, kambing, transaksi, laporan, dll.
public/assets/             # CSS & JS tanpa build step
routes/web.php             # Route Blade
routes/api.php             # Route JSON API /api/v1
lang/id/                   # Pesan validasi Bahasa Indonesia
docs/API.md                # Dokumentasi API untuk klien Flutter
database/seeders/          # UserSeeder & DemoDataSeeder
```

## Catatan Implementasi

- **HPP** memakai biaya perolehan penuh: harga beli + biaya tambahan pembelian.
- Nilai `sales.cost_price` adalah **snapshot permanen** saat penjualan dibuat dan tidak berubah meski pembelian diubah.
- Riwayat transaksi tidak dihapus permanen: kambing/master data diarsipkan (`deleted_at`), transaksi keuangan di-void (`voided_at`).
- Kode dokumen dibentuk dari sequence (`KMB-0001`, `TRX-B-2026-001`, `TRX-J-2026-001`) dengan PK numerik internal.
- Transaksi memakai `DB::transaction` dan penguncian baris untuk mencegah benturan stok/pembayaran.
