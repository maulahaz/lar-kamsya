@extends('layouts.app')

@section('title', 'Scan Barcode')
@section('breadcrumb', 'Scan Barcode')

@section('content')
    <div class="page-head">
        <div class="page-head-text">
            <span class="eyebrow">Inventaris</span>
            <h1>Scan Barcode</h1>
            <p>Cari data kambing secara instan melalui barcode atau kode kambing.</p>
        </div>
        <div class="page-actions">
            <a class="btn btn-outline" href="{{ route('goats.index') }}">
                <x-icon name="package" :size="16" /> Data Kambing
            </a>
        </div>
    </div>

    <div class="scan-layout">
        <section class="card scanner-card">
            <div class="scanner-visual">
                <div class="scanner-frame">
                    <span class="scan-corner scan-corner-tl"></span>
                    <span class="scan-corner scan-corner-tr"></span>
                    <span class="scan-corner scan-corner-bl"></span>
                    <span class="scan-corner scan-corner-br"></span>
                    <span class="scanner-barcode"><x-icon name="barcode" :size="52" /></span>
                    <span class="scanner-laser"></span>
                </div>
                <span class="scanner-status">
                    @if ($found === true)
                        Barcode berhasil dipindai
                    @elseif ($found === false)
                        Barcode tidak ditemukan
                    @else
                        Siap memindai
                    @endif
                </span>
            </div>

            <form method="GET" action="{{ route('scan') }}" class="scan-form">
                <label for="scan-input">Nomor Barcode</label>
                <div class="scan-input-row">
                    <div class="search-field scan-input-field">
                        <x-icon name="barcode" :size="19" />
                        <input id="scan-input" type="text" name="code" inputmode="numeric" autocomplete="off" maxlength="24"
                               placeholder="Contoh: 899100001" value="{{ $code }}" autofocus aria-label="Nomor barcode kambing">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <x-icon name="scan" :size="17" /> Scan
                    </button>
                </div>
                <p class="form-hint">Tekan <kbd>Enter</kbd> untuk memindai. Anda juga dapat memasukkan kode seperti KMB-0001.</p>
            </form>

            @if ($samples->isNotEmpty())
                <div class="scan-samples">
                    <span>Contoh barcode:</span>
                    @foreach ($samples as $sample)
                        <a class="sample-chip" href="{{ route('scan', ['code' => $sample->barcode]) }}">
                            {{ $sample->barcode }} <small>{{ $sample->name }}</small>
                        </a>
                    @endforeach
                </div>
            @endif

            <div class="scanner-note">
                <x-icon name="info" :size="15" />
                <span>Simulasi pemindaian menggunakan input manual. Dukungan kamera atau scanner fisik dapat ditambahkan kemudian.</span>
            </div>
        </section>

        <section class="card result-card">
            <div class="card-header">
                <div>
                    <h2>Hasil Pencarian</h2>
                    <p>Pratinjau informasi kambing dari barcode</p>
                </div>
                <x-icon name="scan" :size="19" />
            </div>
            <div class="card-body">
                @if ($found === false)
                    <div class="scan-error">
                        <span class="scan-error-icon"><x-icon name="alert-circle" :size="32" /></span>
                        <h3>Barcode tidak ditemukan</h3>
                        <p>Nomor <strong>{{ $code }}</strong> tidak cocok dengan kambing mana pun. Periksa kembali nomor barcode atau daftarkan kambing baru.</p>
                        <div class="scan-error-actions">
                            <a class="btn btn-outline btn-sm" href="{{ route('scan') }}">
                                <x-icon name="scan" :size="15" /> Coba lagi
                            </a>
                            @can('create', App\Models\Goat::class)
                                <a class="btn btn-primary btn-sm" href="{{ route('goats.create') }}">
                                    <x-icon name="plus" :size="15" /> Tambah Kambing
                                </a>
                            @endcan
                        </div>
                    </div>
                @elseif ($goat)
                    @php($purchase = $goat->purchase)
                    <div class="scan-result-found">
                        <div class="scan-result-banner">
                            <span class="scan-success-badge"><x-icon name="check-circle" :size="15" /> Barcode ditemukan</span>
                            <span class="badge {{ $goat->status->badge() }}"><span class="badge-dot"></span>{{ $goat->status->label() }}</span>
                        </div>
                        <div class="scan-result-profile">
                            <span class="goat-avatar avatar-lg tone-{{ \App\Support\Format::typeTone($goat->type) }}">🐐</span>
                            <div class="scan-result-title">
                                <h3>{{ $goat->name }}</h3>
                                <span>{{ $goat->code }} · {{ $goat->type }} · {{ $goat->gender->label() }}</span>
                                <span class="barcode-text">{{ $goat->barcode }}</span>
                            </div>
                        </div>
                        <div class="scan-detail-grid">
                            <div><span>Bobot</span><strong>{{ \App\Support\Format::weight($goat->weight) }}</strong></div>
                            <div><span>Usia</span><strong>{{ $goat->ageText() }}</strong></div>
                            <div><span>Warna</span><strong>{{ $goat->color ?: '-' }}</strong></div>
                            <div><span>Status</span><strong>{{ $goat->status->label() }}</strong></div>
                        </div>
                        <div class="scan-origin">
                            <div class="scan-origin-head">
                                <span class="badge {{ $goat->origin === App\Enums\GoatOrigin::Pembelian ? 'badge-info' : 'badge-purple' }}">{{ $goat->origin->label() }}</span>
                                <span>{{ $goat->origin === App\Enums\GoatOrigin::Pembelian ? 'Kambing hasil pembelian' : 'Kambing hasil peranakan' }}</span>
                            </div>
                            @if ($goat->origin === App\Enums\GoatOrigin::Pembelian)
                                <div class="scan-origin-grid">
                                    <div><span>Dibeli dari</span><strong>{{ $purchase?->supplier?->name ?? '-' }}</strong></div>
                                    <div><span>Tanggal beli</span><strong>{{ \App\Support\Format::date($purchase?->date, true) }}</strong></div>
                                    <div><span>Harga beli</span><strong>{{ \App\Support\Format::rupiah($purchase?->price) }}</strong></div>
                                    <div><span>Total transaksi</span><strong>{{ \App\Support\Format::rupiah($purchase?->total) }}</strong></div>
                                </div>
                            @else
                                <div class="scan-origin-grid">
                                    <div><span>Induk betina</span><strong>{{ $goat->mother?->name ?? '-' }}</strong></div>
                                    <div><span>Kode induk</span><strong>{{ $goat->mother?->code ?? '-' }}</strong></div>
                                    <div><span>Tanggal lahir</span><strong>{{ \App\Support\Format::date($goat->birth_date, true) }}</strong></div>
                                    <div><span>Biaya perolehan</span><strong class="muted">Tidak ada</strong></div>
                                </div>
                            @endif
                        </div>
                        <div class="scan-result-actions">
                            <a class="btn btn-primary" href="{{ route('goats.show', $goat) }}">
                                <x-icon name="eye" :size="16" /> Lihat Detail Kambing
                            </a>
                            <a class="btn btn-outline" href="{{ route('goats.labels', ['goats' => [$goat->id]]) }}">
                                <x-icon name="printer" :size="16" /> Cetak Barcode
                            </a>
                            @if ($goat->isAvailable())
                                @can('manage-transactions')
                                    <a class="btn btn-outline" href="{{ route('sales.create', ['goat' => $goat->id]) }}">
                                        <x-icon name="tag" :size="16" /> Catat Penjualan
                                    </a>
                                @endcan
                            @endif
                        </div>
                    </div>
                @else
                    <div class="scan-idle">
                        <span class="scan-idle-art"><x-icon name="barcode" :size="46" /></span>
                        <h3>Hasil scan akan muncul di sini</h3>
                        <p>Masukkan nomor barcode secara manual atau pilih salah satu contoh barcode di samping untuk mencoba pemindaian.</p>
                        <div class="scan-idle-tips">
                            <span><x-icon name="check-circle" :size="14" /> Data kambing &amp; status</span>
                            <span><x-icon name="check-circle" :size="14" /> Asal-usul &amp; induk</span>
                            <span><x-icon name="check-circle" :size="14" /> Riwayat transaksi</span>
                        </div>
                    </div>
                @endif
            </div>
        </section>
    </div>

    <section class="card" style="margin-top: 18px;">
        <div class="card-header">
            <div>
                <h2>Riwayat Scan</h2>
                <p>8 pemindaian terakhir oleh akun Anda</p>
            </div>
            <x-icon name="clock" :size="19" />
        </div>
        <div class="card-body">
            @if ($history->isEmpty())
                <div class="empty-state">
                    <span class="empty-icon"><x-icon name="clock" :size="30" /></span>
                    <h3>Belum ada riwayat scan</h3>
                    <p>Riwayat pemindaian barcode akan tampil di sini.</p>
                </div>
            @else
                <div class="scan-history-list">
                    @foreach ($history as $entry)
                        <a class="history-item" href="{{ route('scan', ['code' => $entry->barcode]) }}">
                            <span class="history-icon {{ $entry->found ? 'history-found' : 'history-missing' }}">
                                <x-icon :name="$entry->found ? 'check-circle' : 'alert-circle'" :size="16" />
                            </span>
                            <span class="history-info">
                                <strong class="barcode-text">{{ $entry->barcode }}</strong>
                                <small>
                                    {{ $entry->goat ? $entry->goat->name.' · '.$entry->goat->code : 'Tidak ditemukan' }}
                                    · {{ \App\Support\Format::dateTime($entry->created_at) }}
                                </small>
                            </span>
                            <x-icon name="chevron-right" :size="16" />
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
