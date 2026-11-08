@extends('layouts.app')

@section('title', 'Pembelian')
@section('breadcrumb', 'Pembelian')

@section('content')
    <div class="page-head">
        <div class="page-head-text">
            <span class="eyebrow">Transaksi</span>
            <h1>Transaksi Pembelian</h1>
            <p>Catat kambing masuk, harga beli, biaya tambahan, dan supplier.</p>
        </div>
        <div class="page-actions">
            @can('create', App\Models\Purchase::class)
                <a class="btn btn-primary" href="{{ route('purchases.create') }}">
                    <x-icon name="plus" :size="16" /> Catat Pembelian
                </a>
            @endcan
        </div>
    </div>

    <section class="metric-grid" aria-label="Ringkasan pembelian">
        <div class="metric-card">
            <span class="metric-icon metric-green"><x-icon name="cart" :size="19" /></span>
            <div><span>Total transaksi</span><strong>{{ \App\Support\Format::number($totals['count']) }}</strong></div>
        </div>
        <div class="metric-card">
            <span class="metric-icon metric-blue"><x-icon name="goat" :size="19" /></span>
            <div><span>Kambing dibeli</span><strong>{{ \App\Support\Format::number($totals['count']) }} ekor</strong></div>
        </div>
        <div class="metric-card">
            <span class="metric-icon metric-amber"><x-icon name="wallet" :size="19" /></span>
            <div><span>Total pengeluaran</span><strong>{{ \App\Support\Format::rupiah($totals['total']) }}</strong></div>
        </div>
        <div class="metric-card">
            <span class="metric-icon metric-purple"><x-icon name="file-text" :size="19" /></span>
            <div><span>Biaya tambahan</span><strong>{{ \App\Support\Format::rupiah($totals['additional']) }}</strong></div>
        </div>
    </section>

    <section class="card">
        <form class="card-toolbar" method="GET" action="{{ route('purchases.index') }}">
            <div class="search-field">
                <x-icon name="search" :size="17" />
                <input type="search" name="q" value="{{ $search }}" placeholder="Cari nomor transaksi, supplier, kambing, atau barcode…" aria-label="Cari transaksi pembelian">
            </div>
            <div class="filter-group">
                <select class="filter-select" name="status" aria-label="Filter status" data-auto-submit>
                    <option value="">Semua status</option>
                    <option value="active" @selected($filter === 'active')>Aktif</option>
                    <option value="voided" @selected($filter === 'voided')>Di-void</option>
                </select>
                <button type="submit" class="btn btn-outline btn-sm"><x-icon name="filter" :size="15" /> Terapkan</button>
                @if ($search !== '' || $filter !== '')
                    <a class="btn btn-ghost btn-sm" href="{{ route('purchases.index') }}"><x-icon name="refresh" :size="15" /> Reset</a>
                @endif
            </div>
        </form>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>No. Transaksi</th>
                        <th>Tanggal</th>
                        <th>Supplier</th>
                        <th>Kambing</th>
                        <th>Barcode</th>
                        <th class="align-end">Harga Beli</th>
                        <th class="align-end">Biaya Tambahan</th>
                        <th class="align-end">Total</th>
                        <th class="th-actions">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($purchases as $purchase)
                        <tr>
                            <td>
                                <span class="barcode-text">{{ $purchase->code }}</span>
                                @if ($purchase->voided_at)
                                    <span class="badge badge-danger" style="margin-left: 6px;">Void</span>
                                @endif
                            </td>
                            <td class="nowrap">{{ \App\Support\Format::date($purchase->date, true) }}</td>
                            <td>
                                <div class="cell-person">
                                    <span class="person-avatar avatar-xs tone-green">{{ $purchase->supplier?->initials() ?? '?' }}</span>
                                    <span class="cell-stack">
                                        <strong>{{ $purchase->supplier?->name ?? '-' }}</strong>
                                        <span class="cell-muted">{{ $purchase->supplier?->phone ?? '-' }}</span>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <div class="cell-stack">
                                    @if ($purchase->goat)
                                        <a class="cell-title" href="{{ route('goats.show', $purchase->goat) }}">{{ $purchase->goat->name }}</a>
                                        <span class="cell-muted">{{ $purchase->goat->code }}</span>
                                    @else
                                        <strong class="muted">Kambing terarsip</strong>
                                    @endif
                                </div>
                            </td>
                            <td><span class="barcode-text">{{ $purchase->goat?->barcode ?? '-' }}</span></td>
                            <td class="align-end nowrap">{{ \App\Support\Format::rupiah($purchase->price) }}</td>
                            <td class="align-end nowrap">{{ \App\Support\Format::rupiah($purchase->additional_cost) }}</td>
                            <td class="align-end nowrap"><strong>{{ \App\Support\Format::rupiah($purchase->total) }}</strong></td>
                            <td class="cell-actions">
                                <a class="action-btn" href="{{ route('purchases.show', $purchase) }}" title="Lihat detail" aria-label="Detail {{ $purchase->code }}">
                                    <x-icon name="eye" :size="16" />
                                </a>
                                @if ($purchase->goat)
                                    <a class="action-btn" href="{{ route('goats.show', $purchase->goat) }}" title="Buka data kambing" aria-label="Buka data kambing">
                                        <x-icon name="goat" :size="16" />
                                    </a>
                                    <a class="action-btn" href="{{ route('goats.labels', ['goats' => [$purchase->goat->id]]) }}" title="Cetak barcode" aria-label="Cetak barcode">
                                        <x-icon name="printer" :size="16" />
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="empty-row">
                                <div class="empty-state">
                                    <span class="empty-icon"><x-icon name="cart" :size="30" /></span>
                                    <h3>{{ $search !== '' || $filter !== '' ? 'Transaksi tidak ditemukan' : 'Belum ada transaksi pembelian' }}</h3>
                                    <p>{{ $search !== '' || $filter !== '' ? 'Coba ubah kata kunci atau reset filter.' : 'Catat pembelian kambing pertama Anda untuk menambah stok inventaris.' }}</p>
                                    @can('create', App\Models\Purchase::class)
                                        <div class="empty-action">
                                            <a class="btn btn-primary btn-sm" href="{{ route('purchases.create') }}"><x-icon name="plus" :size="15" /> Catat Pembelian</a>
                                        </div>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.pagination', ['paginator' => $purchases])
    </section>
@endsection
