@extends('layouts.app')

@section('title', 'Pengaturan')
@section('breadcrumb', 'Pengaturan')

@section('content')
    <div class="page-head">
        <div class="page-head-text">
            <span class="eyebrow">Sistem</span>
            <h1>Pengaturan Aplikasi</h1>
            <p>Atur profil usaha, preferensi barcode, dan cadangan data.</p>
        </div>
        <div class="page-actions">
            <button type="submit" form="settings-form" class="btn btn-primary">
                <x-icon name="check" :size="16" /> Simpan Pengaturan
            </button>
        </div>
    </div>

    @include('partials.errors')

    <div class="settings-layout">
        <div class="settings-main">
            <form id="settings-form" method="POST" action="{{ route('settings.update') }}" class="form">
                @csrf
                @method('PUT')

                <section class="card">
                    <div class="card-header">
                        <div>
                            <h2>Profil Usaha</h2>
                            <p>Informasi ini ditampilkan pada label barcode dan laporan.</p>
                        </div>
                        <x-icon name="user" :size="19" />
                    </div>
                    <div class="card-body">
                        <div class="form-grid">
                            <div class="form-group {{ $errors->has('farm_name') ? 'has-error' : '' }}">
                                <label for="settings-farm">Nama Usaha / Peternakan <span class="req">*</span></label>
                                <input id="settings-farm" type="text" name="farm_name" maxlength="120" value="{{ old('farm_name', $settings->farm_name) }}" placeholder="Contoh: Kamsya Farm" required>
                                @error('farm_name')<span class="field-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="form-group {{ $errors->has('owner_name') ? 'has-error' : '' }}">
                                <label for="settings-owner">Nama Pemilik / Admin <span class="req">*</span></label>
                                <input id="settings-owner" type="text" name="owner_name" maxlength="120" value="{{ old('owner_name', $settings->owner_name) }}" placeholder="Nama pengelola" required>
                                @error('owner_name')<span class="field-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="form-group {{ $errors->has('phone') ? 'has-error' : '' }}">
                                <label for="settings-phone">Nomor Telepon</label>
                                <div class="input-icon">
                                    <x-icon name="phone" :size="16" />
                                    <input id="settings-phone" type="tel" name="phone" maxlength="30" value="{{ old('phone', $settings->phone) }}" placeholder="0812-9000-1234">
                                </div>
                                @error('phone')<span class="field-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="form-group {{ $errors->has('address') ? 'has-error' : '' }}">
                                <label for="settings-address">Alamat Usaha</label>
                                <div class="input-icon">
                                    <x-icon name="map-pin" :size="16" />
                                    <input id="settings-address" type="text" name="address" maxlength="500" value="{{ old('address', $settings->address) }}" placeholder="Alamat lengkap peternakan">
                                </div>
                                @error('address')<span class="field-error">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>
                </section>

                <section class="card">
                    <div class="card-header">
                        <div>
                            <h2>Barcode &amp; Stok</h2>
                            <p>Preferensi pembuatan barcode otomatis dan peringatan stok.</p>
                        </div>
                        <x-icon name="barcode" :size="19" />
                    </div>
                    <div class="card-body">
                        <div class="form-grid">
                            <div class="form-group {{ $errors->has('barcode_prefix') ? 'has-error' : '' }}">
                                <label for="settings-prefix">Prefix Barcode <span class="req">*</span></label>
                                <input id="settings-prefix" type="text" name="barcode_prefix" inputmode="numeric" maxlength="6" value="{{ old('barcode_prefix', $settings->barcode_prefix) }}" placeholder="8991" required data-barcode-prefix>
                                <p class="form-hint">2–6 digit angka. Barcode dibentuk dari prefix + nomor urut 5 digit.</p>
                                @error('barcode_prefix')<span class="field-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="form-group {{ $errors->has('low_stock_limit') ? 'has-error' : '' }}">
                                <label for="settings-lowstock">Batas Peringatan Stok</label>
                                <div class="input-suffix">
                                    <input id="settings-lowstock" type="number" name="low_stock_limit" min="0" max="1000" step="1" value="{{ old('low_stock_limit', $settings->low_stock_limit) }}" required>
                                    <span>ekor</span>
                                </div>
                                <p class="form-hint">Dashboard menampilkan peringatan jika stok tersedia mencapai batas ini.</p>
                                @error('low_stock_limit')<span class="field-error">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="settings-preview">
                            <div class="settings-preview-label">
                                <span>Contoh barcode berikutnya</span>
                                <strong class="barcode-text" data-barcode-sample-text>{{ $nextBarcode }}</strong>
                            </div>
                            <div class="settings-preview-svg">
                                <div data-barcode="{{ $nextBarcode }}" data-barcode-height="46" data-barcode-module="1.6" data-barcode-sample></div>
                            </div>
                        </div>
                    </div>
                </section>
            </form>

            <section class="card">
                <div class="card-header">
                    <div>
                        <h2>Cadangan &amp; Pemulihan Data</h2>
                        <p>Ekspor seluruh data ke JSON atau pulihkan dari berkas cadangan Kamsya.</p>
                    </div>
                    <x-icon name="download" :size="19" />
                </div>
                <div class="card-body">
                    <div class="data-actions">
                        <div class="data-action">
                            <span class="data-action-icon metric-green"><x-icon name="download" :size="19" /></span>
                            <div>
                                <strong>Ekspor Data</strong>
                                <span>Unduh seluruh data sebagai berkas JSON.</span>
                            </div>
                            <a class="btn btn-outline btn-sm" href="{{ route('settings.export') }}">
                                <x-icon name="download" :size="15" /> Ekspor
                            </a>
                        </div>

                        <div class="data-action">
                            <span class="data-action-icon metric-blue"><x-icon name="upload" :size="19" /></span>
                            <div>
                                <strong>Impor Data</strong>
                                <span>Pulihkan data dari berkas backup Kamsya (mengganti data saat ini).</span>
                            </div>
                            <form method="POST" action="{{ route('settings.import') }}" enctype="multipart/form-data" class="inline-form"
                                  data-confirm="Impor cadangan dan ganti seluruh data?"
                                  data-confirm-detail="Seluruh data transaksi, kambing, dan master data saat ini akan diganti dengan isi berkas. Akun pengguna tidak berubah."
                                  data-confirm-label="Impor Data"
                                  data-confirm-tone="warning">
                                @csrf
                                <label class="btn btn-outline btn-sm" style="cursor: pointer;">
                                    <x-icon name="upload" :size="15" /> <span data-import-label>Pilih Berkas</span>
                                    <input type="file" name="backup" accept=".json,application/json" hidden data-import-input>
                                </label>
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <x-icon name="check" :size="15" /> Impor
                                </button>
                            </form>
                        </div>

                        <div class="data-action data-action-danger">
                            <span class="data-action-icon metric-red"><x-icon name="alert-triangle" :size="19" /></span>
                            <div>
                                <strong>Catatan Keamanan</strong>
                                <span>Transaksi tidak dihapus permanen: pembelian, penjualan, pembayaran, dan beban memakai mekanisme void.</span>
                            </div>
                        </div>
                    </div>
                    <div class="inline-alert inline-alert-info" style="margin-top: 6px;">
                        <x-icon name="info" :size="16" />
                        <span>Berkas cadangan berisi data usaha, supplier, customer, kambing, transaksi, pembayaran, beban, dan penghitung kode. Akun pengguna tidak termasuk.</span>
                    </div>
                </div>
            </section>
        </div>

        <aside class="settings-side">
            <section class="card">
                <div class="card-header">
                    <div>
                        <h2>Informasi Aplikasi</h2>
                        <p>Kamsya — Phase 2</p>
                    </div>
                    <span class="brand-mark brand-mark-sm">🐐</span>
                </div>
                <div class="card-body">
                    <div class="app-info-list">
                        <div><span>Versi</span><strong>2.0.0 · Laravel</strong></div>
                        <div><span>Framework</span><strong>Laravel {{ app()->version() }}</strong></div>
                        <div><span>Basis data</span><strong>{{ strtoupper(config('database.default')) }} · {{ config('database.connections.mysql.database') }}</strong></div>
                        <div><span>Data kambing</span><strong>{{ \App\Support\Format::number($stats['goats']) }} ekor</strong></div>
                        <div><span>Stok tersedia</span><strong>{{ \App\Support\Format::number($stats['available']) }} ekor</strong></div>
                        <div><span>Pembelian / penjualan</span><strong>{{ $stats['purchases'] }} / {{ $stats['sales'] }} transaksi</strong></div>
                        <div><span>Beban / pembayaran</span><strong>{{ $stats['expenses'] }} / {{ $stats['payments'] }} catatan</strong></div>
                        <div><span>Supplier / customer</span><strong>{{ $stats['suppliers'] }} / {{ $stats['customers'] }}</strong></div>
                        <div><span>Pengguna</span><strong>{{ $stats['users'] }} akun</strong></div>
                    </div>
                    <div class="inline-alert inline-alert-info app-info-note">
                        <x-icon name="shield" :size="16" />
                        <span>Seluruh data tersimpan di MySQL dan akses diatur berdasarkan role Owner, Admin, dan Petugas Kandang.</span>
                    </div>
                </div>
            </section>

            <section class="card">
                <div class="card-header">
                    <div>
                        <h2>Hak Akses</h2>
                        <p>Role tetap aplikasi</p>
                    </div>
                    <x-icon name="shield" :size="19" />
                </div>
                <div class="card-body">
                    <ol class="workflow-list">
                        <li><span>O</span><div><strong>Owner</strong><small>Akses penuh: keuangan, pengguna, pengaturan.</small></div></li>
                        <li><span>A</span><div><strong>Admin</strong><small>Inventaris, transaksi, dan laporan.</small></div></li>
                        <li><span>P</span><div><strong>Petugas Kandang</strong><small>Inventaris, scan, dan cetak barcode.</small></div></li>
                    </ol>
                </div>
            </section>
        </aside>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const prefix = document.querySelector('[data-barcode-prefix]');
            const sample = document.querySelector('[data-barcode-sample]');
            const label = document.querySelector('[data-barcode-sample-text]');
            const nextSequence = {{ (int) substr($nextBarcode, -5) }};

            if (prefix && sample && label) {
                prefix.addEventListener('input', function () {
                    const digits = prefix.value.replace(/\D/g, '').slice(0, 6) || '8991';
                    const value = digits + String(nextSequence).padStart(5, '0');
                    label.textContent = value;
                    if (window.Kamsya && window.Kamsya.Barcode) {
                        sample.innerHTML = window.Kamsya.Barcode.svg(value, { height: 46, moduleWidth: 1.6 });
                    }
                });
            }

            const importInput = document.querySelector('[data-import-input]');
            const importLabel = document.querySelector('[data-import-label]');
            if (importInput && importLabel) {
                importInput.addEventListener('change', function () {
                    importLabel.textContent = importInput.files && importInput.files[0]
                        ? importInput.files[0].name.slice(0, 24)
                        : 'Pilih Berkas';
                });
            }
        })();
    </script>
@endpush
