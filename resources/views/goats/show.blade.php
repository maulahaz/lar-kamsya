@extends('layouts.app')

@section('title', $goat->name)
@section('breadcrumb', 'Detail Kambing')

@section('content')
    <div class="back-row">
        <a class="back-link" href="{{ route('goats.index') }}"><x-icon name="arrow-left" :size="16" /> Data Kambing</a>
        <span class="back-sep">/</span>
        <span>{{ $goat->code }}</span>
    </div>

    <div class="page-head">
        <div class="page-head-text">
            <span class="eyebrow">Detail Kambing</span>
            <h1>{{ $goat->name }}</h1>
            <p>{{ $goat->code }} · {{ $goat->type }} · {{ $goat->gender->label() }} · {{ \App\Support\Format::weight($goat->weight) }}</p>
        </div>
        <div class="page-actions">
            @can('update', $goat)
                <a class="btn btn-outline" href="{{ route('goats.edit', $goat) }}"><x-icon name="edit" :size="16" /> Edit Data</a>
            @endcan
            <a class="btn btn-outline" href="{{ route('goats.labels', ['goats' => [$goat->id]]) }}"><x-icon name="printer" :size="16" /> Cetak Barcode</a>
            @if ($goat->isAvailable())
                @can('manage-transactions')
                    <a class="btn btn-primary" href="{{ route('sales.create', ['goat' => $goat->id]) }}"><x-icon name="tag" :size="16" /> Catat Penjualan</a>
                @endcan
            @else
                <span class="sold-flag"><x-icon name="check-circle" :size="16" /> Kambing sudah terjual</span>
            @endif
        </div>
    </div>

    <div class="detail-layout">
        <aside class="detail-side">
            <section class="card profile-card">
                <div class="profile-cover tone-{{ \App\Support\Format::typeTone($goat->type) }}">
                    <span class="profile-pattern"></span>
                    <span class="profile-avatar">🐐</span>
                    <span class="profile-status">
                        <span class="badge {{ $goat->status->badge() }}"><span class="badge-dot"></span>{{ $goat->status->label() }}</span>
                    </span>
                </div>
                <div class="profile-body">
                    <h2>{{ $goat->name }}</h2>
                    <div class="profile-id">{{ $goat->code }} · <span class="barcode-text">{{ $goat->barcode }}</span></div>
                    <div class="profile-chips">
                        <span class="badge badge-neutral">{{ $goat->type }}</span>
                        <span class="badge badge-neutral">{{ $goat->gender->label() }}</span>
                        <span class="badge {{ $goat->origin === App\Enums\GoatOrigin::Peranakan ? 'badge-purple' : 'badge-info' }}">
                            {{ $goat->origin->label() }}
                        </span>
                    </div>
                    <div class="profile-stats">
                        <div><span>Berat</span><strong>{{ \App\Support\Format::weight($goat->weight) }}</strong></div>
                        <div><span>Usia</span><strong>{{ $goat->ageText() }}</strong></div>
                        <div><span>Warna</span><strong>{{ $goat->color ?: '-' }}</strong></div>
                        <div><span>Masuk</span><strong>{{ \App\Support\Format::date($goat->entry_date, true) }}</strong></div>
                    </div>
                </div>
            </section>

            <section class="card barcode-card">
                <div class="card-header">
                    <div>
                        <h2>Barcode</h2>
                        <p>Identitas permanen kambing</p>
                    </div>
                    <x-icon name="barcode" :size="19" />
                </div>
                <div class="card-body">
                    <div class="barcode-box">
                        <div class="barcode-svg" data-barcode="{{ $goat->barcode }}" data-barcode-height="66"></div>
                    </div>
                    <p class="barcode-help">Pindai atau masukkan nomor ini pada halaman Scan Barcode.</p>
                    <a class="btn btn-outline btn-block" href="{{ route('goats.labels', ['goats' => [$goat->id]]) }}">
                        <x-icon name="printer" :size="16" /> Cetak Label Barcode
                    </a>
                </div>
            </section>

            <section class="card">
                <div class="card-header">
                    <div>
                        <h2>Catatan</h2>
                        <p>Informasi tambahan</p>
                    </div>
                </div>
                <div class="card-body">
                    <p class="notes-text">{{ $goat->notes ?: 'Belum ada catatan untuk kambing ini.' }}</p>
                </div>
            </section>
        </aside>

        <div class="detail-main detail-stack">
            @if ($goat->purchase)
                @php($purchase = $goat->purchase)
                <div class="origin-panel origin-purchase">
                    <div class="origin-panel-head">
                        <span class="panel-icon"><x-icon name="truck" :size="20" /></span>
                        <div>
                            <strong>Kambing hasil pembelian</strong>
                            <span>Transaksi <a class="inline-link" href="{{ route('purchases.show', $purchase) }}">{{ $purchase->code }}</a></span>
                        </div>
                    </div>
                    <div class="supplier-card">
                        <span class="person-avatar avatar-md tone-green">{{ $purchase->supplier?->initials() ?? '?' }}</span>
                        <div class="supplier-info">
                            <strong>{{ $purchase->supplier?->name ?? '-' }}</strong>
                            <span><x-icon name="phone" :size="13" /> {{ $purchase->supplier?->phone ?? '-' }}</span>
                            <span><x-icon name="map-pin" :size="13" /> {{ $purchase->supplier?->address ?? '-' }}</span>
                        </div>
                    </div>
                    <div class="info-grid">
                        <div class="info-item"><span>Tanggal pembelian</span><strong>{{ \App\Support\Format::date($purchase->date) }}</strong></div>
                        <div class="info-item"><span>Harga beli</span><strong>{{ \App\Support\Format::rupiah($purchase->price) }}</strong></div>
                        <div class="info-item"><span>Biaya tambahan</span><strong>{{ \App\Support\Format::rupiah($purchase->additional_cost) }}</strong></div>
                        <div class="info-item info-highlight"><span>Biaya perolehan</span><strong>{{ \App\Support\Format::rupiah($purchase->total) }}</strong></div>
                    </div>
                    @if ($purchase->voided_at)
                        <p class="void-note">Pembelian di-void: {{ $purchase->void_reason }}</p>
                    @endif
                </div>
            @else
                <div class="origin-panel origin-offspring">
                    <div class="origin-panel-head">
                        <span class="panel-icon"><x-icon name="goat" :size="20" /></span>
                        <div>
                            <strong>Kambing peranakan</strong>
                            <span>Lahir dan tercatat di peternakan</span>
                        </div>
                    </div>
                    <div class="info-grid">
                        <div class="info-item"><span>Induk</span><strong>
                            @if ($goat->mother)
                                <a class="inline-link" href="{{ route('goats.show', $goat->mother) }}">{{ $goat->mother->code }} — {{ $goat->mother->name }}</a>
                            @else
                                Tidak diketahui
                            @endif
                        </strong></div>
                        <div class="info-item"><span>Tanggal lahir</span><strong>{{ \App\Support\Format::date($goat->birth_date) }}</strong></div>
                        <div class="info-item"><span>Tanggal masuk</span><strong>{{ \App\Support\Format::date($goat->entry_date) }}</strong></div>
                        <div class="info-item"><span>Biaya perolehan</span><strong>Rp0</strong></div>
                    </div>
                </div>
            @endif

            <section class="card">
                <div class="card-header">
                    <div>
                        <h2>Riwayat Transaksi</h2>
                        <p>Pembelian dan penjualan yang tercatat untuk kambing ini</p>
                    </div>
                    <span class="badge badge-neutral">{{ ($goat->purchase ? 1 : 0) + $goat->sales->count() }} transaksi</span>
                </div>
                @if ($goat->purchase || $goat->sales->isNotEmpty())
                    <div class="transaction-timeline">
                        @if ($goat->purchase)
                            <div class="timeline-item">
                                <span class="timeline-icon timeline-purchase"><x-icon name="cart" :size="17" /></span>
                                <div class="timeline-body">
                                    <div class="timeline-head">
                                        <strong>Pembelian kambing</strong>
                                        <span class="timeline-amount">{{ \App\Support\Format::rupiah($goat->purchase->total) }}</span>
                                    </div>
                                    <div class="timeline-meta">
                                        <span><x-icon name="hash" :size="13" /> {{ $goat->purchase->code }}</span>
                                        <span><x-icon name="calendar" :size="13" /> {{ \App\Support\Format::date($goat->purchase->date) }}</span>
                                        <span><x-icon name="truck" :size="13" /> {{ $goat->purchase->supplier?->name ?? '-' }}</span>
                                    </div>
                                    <div class="timeline-detail">
                                        <span>Harga beli <strong>{{ \App\Support\Format::rupiah($goat->purchase->price) }}</strong></span>
                                        <span>Biaya tambahan <strong>{{ \App\Support\Format::rupiah($goat->purchase->additional_cost) }}</strong></span>
                                    </div>
                                    @if ($goat->purchase->notes)
                                        <p class="timeline-note">{{ $goat->purchase->notes }}</p>
                                    @endif
                                </div>
                            </div>
                        @endif

                        @foreach ($goat->sales as $item)
                            <div class="timeline-item">
                                <span class="timeline-icon timeline-sale"><x-icon name="tag" :size="17" /></span>
                                <div class="timeline-body">
                                    <div class="timeline-head">
                                        <strong>Penjualan kambing {{ $item->voided_at ? '(void)' : '' }}</strong>
                                        <span class="timeline-amount">{{ \App\Support\Format::rupiah($item->price) }}</span>
                                    </div>
                                    <div class="timeline-meta">
                                        <span><x-icon name="hash" :size="13" /> <a class="inline-link" href="{{ route('sales.show', $item) }}">{{ $item->code }}</a></span>
                                        <span><x-icon name="calendar" :size="13" /> {{ \App\Support\Format::date($item->date) }}</span>
                                        <span><x-icon name="user" :size="13" /> {{ $item->customer?->name ?? '-' }}</span>
                                    </div>
                                    <div class="timeline-detail">
                                        <span>HPP <strong>{{ \App\Support\Format::rupiah($item->cost_price) }}</strong></span>
                                        <span>Keuntungan <strong class="{{ $item->profit() >= 0 ? 'text-success' : 'text-danger' }}">{{ $item->profit() >= 0 ? '+' : '' }}{{ \App\Support\Format::rupiah($item->profit()) }}</strong></span>
                                    </div>
                                    @if ($item->voided_at)
                                        <p class="void-note">Void: {{ $item->void_reason }}</p>
                                    @endif
                                    @if ($item->notes)
                                        <p class="timeline-note">{{ $item->notes }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="card-body">
                        <div class="empty-state">
                            <span class="empty-icon"><x-icon name="file-text" :size="30" /></span>
                            <h3>Belum ada transaksi</h3>
                            <p>Riwayat pembelian atau penjualan kambing akan tampil di sini.</p>
                        </div>
                    </div>
                @endif
            </section>

            @if ($goat->children->isNotEmpty())
                <section class="card">
                    <div class="card-header">
                        <div>
                            <h2>Keturunan</h2>
                            <p>Kambing yang tercatat berasal dari induk ini</p>
                        </div>
                        <span class="badge badge-neutral">{{ $goat->children->count() }} ekor</span>
                    </div>
                    <div class="card-body">
                        <div class="children-list">
                            @foreach ($goat->children as $child)
                                <a class="child-card" href="{{ route('goats.show', $child) }}">
                                    <span class="goat-avatar avatar-sm tone-{{ \App\Support\Format::typeTone($child->type) }}">🐐</span>
                                    <span class="child-info">
                                        <strong>{{ $child->name }}</strong>
                                        <small>{{ $child->code }} · {{ $child->type }} · {{ $child->ageText() }}</small>
                                    </span>
                                    <span class="badge {{ $child->status->badge() }}">{{ $child->status->label() }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif

            @if ($goat->scanLogs->isNotEmpty())
                <section class="card">
                    <div class="card-header">
                        <div>
                            <h2>Riwayat Scan Terakhir</h2>
                            <p>Aktivitas pemindaian barcode kambing ini</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="history-list">
                            @foreach ($goat->scanLogs as $log)
                                <div class="history-card">
                                    <span class="history-icon history-found"><x-icon name="scan" :size="16" /></span>
                                    <div class="history-main">
                                        <strong>{{ $log->user?->name ?? 'Sistem' }}</strong>
                                        <small>{{ \App\Support\Format::dateTime($log->created_at) }} · {{ $log->barcode }}</small>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif

            @can('delete', $goat)
                <section class="card">
                    <div class="card-header">
                        <div>
                            <h2>Arsipkan Kambing</h2>
                            <p>Data tidak dihapus permanen; riwayat transaksi tetap tersimpan.</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('goats.destroy', $goat) }}" data-confirm="Arsipkan kambing {{ $goat->name }}?" data-confirm-label="Arsipkan" data-confirm-tone="danger">
                            @csrf
                            @method('DELETE')
                            <div class="form-grid">
                                <div class="form-group full">
                                    <label for="reason">Alasan arsip</label>
                                    <input id="reason" type="text" name="reason" maxlength="255" placeholder="Opsional, misalnya data duplikat">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-danger-soft">
                                <x-icon name="trash" :size="16" /> Arsipkan Kambing
                            </button>
                        </form>
                    </div>
                </section>
            @endcan
        </div>
    </div>
@endsection
