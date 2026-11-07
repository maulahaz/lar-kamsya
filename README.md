# Kamsya — Prototype Manajemen & Jual-Beli Kambing

Prototype aplikasi web **frontend-only** untuk mengelola inventaris kambing, transaksi pembelian/penjualan, supplier, customer, barcode, dan laporan usaha peternakan.

Aplikasi ini dibuat berdasarkan PRD `mhz/lar-kamsya-PRD.md` sebagai gambaran alur dan tampilan MVP. Seluruh data disimpan di **localStorage** browser, tanpa backend, database, autentikasi, API, atau integrasi pihak ketiga.

## Cara Menjalankan

### 1. Langsung buka file

Buka `index.html` di browser modern (Chrome, Edge, Firefox, atau Safari). Aplikasi dapat berjalan langsung dari filesystem dan tetap bekerja setelah seluruh aset dimuat.

### 2. Lewat server statis lokal (disarankan)

Menjalankan lewat server statis memberi perilaku yang lebih konsisten, terutama untuk unduh/impor backup.

```bash
# Node.js
npx serve .

# atau Python
python -m http.server 8080
```

Lalu buka `http://localhost:8080` (atau port yang ditampilkan).

> Tidak perlu `npm install`, build step, atau koneksi internet. Semua CSS, JavaScript, ikon, dan generator barcode dibuat lokal.

## Halaman & Fitur

| Halaman | Fungsi |
| --- | --- |
| **Dashboard** | Ringkasan stok, transaksi, estimasi keuntungan, grafik arus transaksi, dan transaksi terbaru. |
| **Data Kambing** | CRUD kambing, pencarian, filter, pagination, detail kambing, riwayat transaksi, dan status kepemilikan. |
| **Scan Barcode** | Simulasi pemindaian dengan input manual nomor barcode/ID, riwayat scan, dan tautan ke detail kambing. |
| **Cetak Barcode** | Pilih kambing, pratinjau label, dan cetak barcode Code 39 (ukuran label dapat dipilih). |
| **Pembelian** | Catat pembelian kambing baru beserta supplier, harga, dan biaya tambahan; otomatis membuat data kambing. |
| **Penjualan** | Jual kambing berstatus tersedia, hitung estimasi keuntungan, dan ubah status menjadi `Terjual`. |
| **Supplier** | CRUD supplier dengan validasi penghapusan jika masih dipakai transaksi. |
| **Customer** | CRUD customer dengan validasi penghapusan jika masih dipakai transaksi. |
| **Laporan** | Filter rentang tanggal, ringkasan pembelian/penjualan/laba kotor, rincian transaksi, dan cetak laporan. |
| **Pengaturan** | Identitas peternakan, prefix barcode, batas stok rendah, ekspor/impor JSON, dan reset data demo. |

### Aturan bisnis utama

- Setiap kambing memiliki **barcode unik** yang dibuat otomatis dan tidak berubah saat data diedit.
- Asal kambing terdiri dari **Pembelian** dan **Peranakan**.
  - Kambing pembelian otomatis memiliki transaksi pembelian.
  - Kambing peranakan wajib memilih induk betina terdaftar dan **tidak** membuat transaksi pembelian.
- Kambing yang sudah dijual berstatus **Terjual**, tetap tersimpan sebagai riwayat, dan tidak dapat dijual ulang.
- Laba kotor dihitung dari `harga jual − harga beli` (harga beli disimpan sebagai snapshot pada transaksi penjualan).
- Kambing yang menjadi induk tidak dapat dihapus sebelum relasi induk pada anaknya diubah.
- Supplier/customer yang masih terhubung ke transaksi tidak dapat dihapus.

## Data Demo

Saat pertama dibuka, aplikasi mengisi data contoh:

- 15 kambing (pembelian dan peranakan)
- 5 supplier
- 8 customer
- 10 transaksi pembelian
- 5 transaksi penjualan

Data disimpan pada key localStorage `kamsya.state.v1`. Jika localStorage tidak tersedia, aplikasi memakai penyimpanan sementara di memori.

### Mengembalikan data demo

Buka **Pengaturan → Data & Cadangan → Reset Data Demo**. Tombol ini menghapus perubahan lokal dan memuat ulang data contoh.

### Ekspor / impor

- **Ekspor JSON** mengunduh seluruh state aplikasi sebagai file backup.
- **Impor JSON** memulihkan state dari file backup Kamsya yang valid.
- **Reset** mengembalikan data demo awal.

## Catatan Prototype

- **Frontend-only:** tidak ada backend Laravel, database, API, login, atau sinkronisasi antarperangkat.
- **Scan barcode disimulasikan:** kamera perangkat tidak digunakan. Masukkan nomor barcode secara manual (contoh: `899100001`) atau klik contoh barcode yang tersedia.
- **Barcode Code 39** digambar sebagai SVG lokal di `assets/js/barcode.js`, sehingga tetap dapat dicetak tanpa internet.
- Format tanggal pada tabel/laporan menggunakan Bahasa Indonesia (`07 November 2026`), sedangkan tampilan input tanggal mengikuti locale browser.
- Data hanya tersimpan di browser yang dipakai. Menghapus data situs/browser akan menghapus data aplikasi.
- Prototype ini mengutamakan alur dan tampilan MVP; aturan produksi seperti hak akses pengguna, audit trail, dan validasi server belum termasuk.

## Struktur Proyek

```text
.
├── index.html
├── README.md
└── assets
    ├── css
    │   └── app.css          # Design system, layout, responsive, dan print styles
    └── js
        ├── utils.js         # Format Rupiah/tanggal, helper umum
        ├── seed.js          # Data demo dan skema awal
        ├── barcode.js       # Generator barcode Code 39 (SVG)
        ├── store.js         # State, CRUD, transaksi, statistik, ekspor/impor
        ├── ui.js            # Ikon, modal, toast, badge, chart, pagination, print
        ├── router.js        # Hash router dan lifecycle halaman
        ├── app.js           # Shell sidebar/topbar dan inisialisasi
        └── pages/           # Modul per halaman (render/mount/destroy)
```

## Teknologi

- HTML5, CSS3, dan JavaScript vanilla (ES2017+).
- Hash routing tanpa framework.
- Data awal dan persistensi melalui localStorage.
- Ikon SVG inline dan barcode Code 39 yang dirender sebagai SVG.
- Tanpa CDN, font eksternal, atau dependensi runtime.

## Lisensi

Prototype internal untuk keperluan demo dan validasi alur bisnis Kamsya.
