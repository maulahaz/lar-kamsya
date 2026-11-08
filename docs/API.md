# Kamsya API v1

RESTful JSON API untuk aplikasi Flutter. API ini **additive**: ia memakai ulang model,
service, policy, dan gate yang sama dengan aplikasi Blade, sehingga aturan bisnis,
kode dokumen, dan angka laporan selalu identik dengan web.

- Base URL: `http://localhost:8000/api/v1`
- Autentikasi: **Laravel Sanctum** personal access token (Bearer)
- Format: JSON (`Accept: application/json`, `Content-Type: application/json`)
- Bahasa: seluruh pesan validasi dan error dalam **Bahasa Indonesia**
- Tidak ada mode offline; aplikasi klien selalu online.

## Autentikasi

### Login

`POST /auth/login`

```json
{
  "email": "owner@kamsya.id",
  "password": "kamsya123",
  "device_name": "flutter-android"
}
```

Respons `200`:

```json
{
  "message": "Berhasil masuk.",
  "data": { "id": 1, "name": "Owner Kamsya", "email": "owner@kamsya.id", "role": "owner", "role_label": "Owner", "is_active": true },
  "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
  "token_type": "Bearer",
  "expires_at": "2026-12-08T10:00:00+00:00"
}
```

- `device_name` opsional; dipakai sebagai nama token (default: User-Agent atau `flutter-app`).
- Token berlaku **30 hari** (`SANCTUM_EXPIRATION`, dalam menit). Setiap login membuat token baru
  per perangkat; logout hanya mencabut token yang sedang dipakai.
- Login dibatasi 5 percobaan / menit per kombinasi email + IP. Respons `429` berisi pesan Indonesia.
- Akun nonaktif ditolak dengan `403`.

### Request terautentikasi

```http
Authorization: Bearer <token>
Accept: application/json
```

### Logout & profil

| Metode | Endpoint | Keterangan |
| --- | --- | --- |
| `POST` | `/auth/logout` | Cabut token saat ini |
| `GET` | `/auth/me` | Data pengguna yang login |
| `PUT` | `/auth/profile` | Ubah nama, email, telepon |
| `PUT` | `/auth/password` | Ubah kata sandi (`current_password`, `password`, `password_confirmation`); token lain dicabut |

## Format Respons

- Sukses tunggal: `{ "message"?: string, "data": {...} }`
- Sukses daftar (paginated): `{ "data": [...], "links": {...}, "meta": {...} }`
- Daftar tertentu menambahkan kunci ringkasan di root: `totals` (pembelian/penjualan/beban),
  `counts` (pengguna), `filters` (log aktivitas).
- Error: `{ "message": string, "errors"?: { "field": ["pesan"] } }`

| Status | Arti |
| --- | --- |
| `401` | Token tidak ada/kedaluwarsa |
| `403` | Peran tidak berwenang atau akun nonaktif |
| `404` | Data tidak ditemukan |
| `422` | Validasi gagal (`errors` berisi detail per field) |
| `429` | Terlalu banyak permintaan |

Pagination: query `per_page` (1–100, default 20) dan `page`. Respons `links`/`meta`
mengikuti format Laravel resource collection.

## Referensi Endpoint

### Umum

| Metode | Endpoint | Keterangan |
| --- | --- | --- |
| `GET` | `/dashboard` | Statistik stok, grafik 6 bulan, transaksi terbaru, ringkasan tahun berjalan (ringkasan `null` untuk Petugas) |
| `GET` | `/options` | Nilai enum untuk form: role, status/jenis kelamin/asal kambing, kategori beban, metode bayar, tab laporan |

### Inventaris & scan

| Metode | Endpoint | Keterangan |
| --- | --- | --- |
| `GET` | `/goats` | Filter `q`, `status`, `type`, `per_page` |
| `POST` | `/goats` | Tambah kambing peranakan (origin dipaksa `peranakan`) |
| `GET` | `/goats/{code}` | Detail kambing (`KMB-0001`) |
| `PUT` | `/goats/{code}` | Ubah data kambing |
| `DELETE` | `/goats/{code}` | Arsipkan (soft delete), body `reason` opsional |
| `GET` | `/goats/barcode/{barcode}` | Cari kambing lewat barcode/kode |
| `GET` | `/scan?code=899100001` | Cari + catat log scan |
| `GET` | `/scan/history` | 20 log scan terakhir milik pengguna |

Body tambah/ubah kambing:

```json
{
  "name": "Kambing A",
  "type": "Boer",
  "gender": "jantan",
  "birth_date": "2025-01-01",
  "weight": 30,
  "color": "Cokelat",
  "mother_id": null,
  "entry_date": "2026-11-08",
  "notes": null
}
```

### Master data

| Metode | Endpoint | Keterangan |
| --- | --- | --- |
| `GET/POST` | `/suppliers` | Daftar / tambah supplier; daftar mendukung `q` |
| `GET/PUT/DELETE` | `/suppliers/{code}` | Detail / ubah / arsipkan (`SUP-001`) |
| `GET/POST` | `/customers` | Daftar / tambah customer; daftar mendukung `q` |
| `GET/PUT/DELETE` | `/customers/{code}` | Detail / ubah / arsipkan (`CUS-001`) |

Supplier/customer yang masih punya transaksi aktif tidak dapat diarsipkan (`422`).

### Transaksi

| Metode | Endpoint | Keterangan |
| --- | --- | --- |
| `GET/POST` | `/purchases` | Daftar / catat pembelian; filter `q`, `status=active|voided` |
| `GET` | `/purchases/{code}` | Detail (`TRX-B-2026-001`) |
| `POST` | `/purchases/{code}/void` | Void, body `{ "reason": "..." }` |
| `GET/POST` | `/sales` | Daftar / catat penjualan; filter `q`, `status=active|voided` |
| `GET` | `/sales/{code}` | Detail (`TRX-J-2026-001`) |
| `POST` | `/sales/{code}/void` | Void, body `{ "reason": "..." }` |
| `GET/POST` | `/payments` | Daftar / catat pembayaran; filter `status`, `payable_type`, `from`, `to` |
| `GET` | `/payments/{code}` | Detail (`PAY-2026-001`) |
| `POST` | `/payments/{code}/void` | Void, body `{ "reason": "..." }` |
| `GET/POST` | `/expenses` | Daftar / catat beban; filter `q`, `category`, `status` |
| `GET` | `/expenses/{code}` | Detail (`BBN-2026-001`) |
| `POST` | `/expenses/{code}/void` | Void, body `{ "reason": "..." }` |

Pembelian (kambing baru + pembayaran awal opsional):

```json
{
  "date": "2026-11-08",
  "supplier_id": 1,
  "price": 1000000,
  "additional_cost": 100000,
  "notes": "Pembelian baru",
  "goat": {
    "name": "Kambing Baru",
    "type": "Boer",
    "gender": "jantan",
    "birth_date": "2025-01-01",
    "weight": 30,
    "color": "Cokelat",
    "notes": null
  },
  "paid_amount": 500000,
  "payment_method": "tunai"
}
```

Penjualan (kambing harus berstatus `tersedia`):

```json
{
  "date": "2026-11-08",
  "goat_id": 1,
  "customer_id": 1,
  "price": 3000000,
  "notes": "Penjualan baru",
  "paid_amount": 1500000,
  "payment_method": "transfer"
}
```

Pembayaran:

```json
{
  "date": "2026-11-08",
  "payable_type": "purchase",
  "payable_id": 1,
  "amount": 725000,
  "method": "transfer",
  "notes": "Pelunasan"
}
```

- `payable_type`: `purchase` | `sale`.
- Overpayment, pembayaran pada transaksi void, dan penjualan kambing terjual ditolak `422`.
- Void penjualan mengembalikan kambing ke `tersedia` dan mem-void pembayarannya.
- `sales.cost_price` adalah snapshot permanen (harga beli + biaya tambahan).

Nilai enum:
- `gender`: `jantan`, `betina`
- `payment_method`/`method`: `tunai`, `transfer`, `qris`, `lainnya`
- `expense.category`: lihat `/options`

### Laporan (JSON)

| Metode | Endpoint | Keterangan |
| --- | --- | --- |
| `GET` | `/reports?tab=laba-rugi&from=2026-01-01&to=2026-12-31` | Data satu tab laporan |
| `GET` | `/reports/summary?from=...&to=...` | Ringkasan keuangan |

Tab: `laba-rugi`, `arus-kas`, `piutang`, `utang`, `laba-kambing`, `penjualan`,
`pembelian`, `stok`. Tab tidak dikenal jatuh ke `laba-rugi`. Tanpa `from`/`to`,
periode default adalah awal tahun berjalan sampai hari ini. Semua nominal berupa angka
(float), bukan string terformat.

`/reports/summary` mengembalikan `sales_total`, `cogs`, `gross_profit`, `expense_total`,
`net_profit`, `inventory_value`, `receivables`, `payables`.

### Sistem (khusus Owner)

| Metode | Endpoint | Keterangan |
| --- | --- | --- |
| `GET/POST` | `/users` | Daftar / tambah pengguna; daftar mendukung `q`, root `counts` per role |
| `GET/PUT/DELETE` | `/users/{id}` | Detail / ubah / nonaktifkan pengguna |
| `GET` | `/activity-logs` | Log audit; filter `q`, `action`, `user`, root `filters` |
| `GET/PUT` | `/settings` | Profil peternakan + statistik + contoh barcode berikutnya |
| `GET` | `/settings/export` | Unduh cadangan JSON (`Content-Disposition: attachment`) |
| `POST` | `/settings/import` | Impor cadangan via `multipart/form-data` (`backup`) atau `payload` (JSON) |

Aturan pengguna: minimal satu Owner aktif harus tersisa; pengguna tidak dapat mengubah
role atau menonaktifkan akunnya sendiri.

## Matriks Hak Akses

| Kemampuan | Owner | Admin | Petugas |
| --- | :---: | :---: | :---: |
| Dashboard, inventaris, scan, opsi | ya | ya | ya |
| Pembelian, penjualan, pembayaran, beban, laporan | ya | ya | tidak |
| Supplier & customer | ya | ya | tidak |
| Pengguna, pengaturan, log aktivitas | ya | tidak | tidak |

## Catatan Integrasi Flutter

1. Simpan token di penyimpanan aman (mis. `flutter_secure_storage`), bukan SharedPreferences biasa.
2. Tambahkan interceptor `Authorization: Bearer <token>` dan `Accept: application/json`.
3. Tangani `401` dengan menghapus token lalu kembali ke layar login.
4. Gunakan `GET /options` untuk mengisi dropdown agar label Indonesia konsisten dengan web.
5. Binding resource memakai **kode dokumen**, bukan ID numerik (`KMB-0001`, `SUP-001`, `CUS-001`).
   Pengecualian: pembayaran, pengguna, dan relasi form memakai `id` numerik.
6. Nominal JSON berupa angka; format Rupiah dilakukan di sisi klien (`Rp12.400.000`).
7. Laporan tidak menyediakan unduhan PDF/CSV; gunakan data JSON dan render di aplikasi.
