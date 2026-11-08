@extends('layouts.app')

@section('title', 'Penjualan')
@section('breadcrumb', 'Penjualan')

@section('content')
    <div class="page-head">
        <div class="page-head-text">
            <span class="eyebrow">Transaksi</span>
            <h1>Transaksi Penjualan</h1>
            <p>Catat kambing keluar, harga jual, dan keuntungan kotor setiap transaksi.</p>
        </div>
        <div class="page-actions">
            @can('create', App\Models\Sale::class)
                <a class="btn btn-primary" href="{{ route('sales.create') }}">
                    <x-icon name="plus" :size="16" /> Catat Penjualan
                </a>
            @endcan
        </div>
    </div>

    <section class="metric-grid" aria-label="Ringkasan penjualan">
        <div class="metric-card">
            <span class="metric-icon metric-blue"><x-icon name="tag" :size="19" /></span>
            <div><span>Total transaksi</span><strong>{{ \App\Support\Format::number($totals['count']) }}</strong></div>
        </div>
        <div class="metric-card">
            <span class="metric-icon metric-green"><x-icon name="wallet" :size="19" /></span>
            <div><span>Total penjualan</span><strong>{{ \App\Support\Format::rupiah($totals['total']) }}</strong></div>
        </div>
        <div class="metric-card">
            <span class="metric-icon metric-amber"><x-icon name="trending-up" :size="19" /></span>
            <div><span>Keuntungan kotor</span><strong>{{ \App\Support\Format::rupiah($totals['total'] - $totals['cogs']) }}</strong></div>
        </div>
        <div class="metric-card">
            <span class="metric-icon metric-purple"><x-icon name="goat" :size="19" /></span>
            <div><span>Kambing terjual</span><strong>{{ \App\Support\Format::number($totals['count']) }} ekor</strong></div>
        </div>
    </section>

    <section class="card">
        <form class="card-toolbar" method="GET" action="{{ route('sales.index') }}">
            <div class="search-field">
                <x-icon name="search" :size="17" />
                <input type="search" name="q" value="{{ $search }}" placeholder="     Cari nomor transaksi, customer, kambing, atau barcode…" aria-label="Cari transaksi penjualan">
            </div>
            <div class="filter-group">
                <select class="filter-select" name="status" aria-label="Filter status" data-auto-submit>
                    <option value="">Semua status</option>
                    <option value="active" @selected($filter === 'active')>Aktif</option>
                    <option value="voided" @selected($filter === 'voided')>Di-void</option>
                </select>
                <button type="submit" class="btn btn-outline btn-sm"><x-icon name="filter" :size="15" /> Terapkan</button>
                @if ($search !== '' || $filter !== '')
                    <a class="btn btn-ghost btn-sm" href="{{ route('sales.index') }}"><x-icon name="refresh" :size="15" /> Reset</a>
                @endif
            </div>
        </form>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>No. Transaksi</th>
                        <th>Tanggal</th>
                        <th>Kambing</th>
                        <th>Pembeli</th>
                        <th class="align-end">HPP</th>
                        <th class="align-end">Harga Jual</th>
                        <th class="align-end">Laba</th>
                        <th class="th-actions">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sales as $sale)
                        <tr>
                            <td>
                                <span class="barcode-text">{{ $sale->code }}</span>
                                @if ($sale->voided_at)
                                    <span class="badge badge-danger" style="margin-left: 6px;">Void</span>
                                @endif
                            </td>
                            <td class="nowrap">{{ \App\Support\Format::date($sale->date, true) }}</td>
                            <td>
                                <div class="cell-stack">
                                    @if ($sale->goat)
                                        <a class="cell-title" href="{{ route('goats.show', $sale->goat) }}">{{ $sale->goat->name }}</a>
                                        <span class="cell-muted">{{ $sale->goat->code }} · <span class="barcode-text">{{ $sale->goat->barcode }}</span></span>
                                    @else
                                        <strong class="muted">Kambing terarsip</strong>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="cell-person">
                                    <span class="person-avatar avatar-xs tone-blue">{{ $sale->customer?->initials() ?? '?' }}</span>
                                    <span class="cell-stack">
                                        <strong>{{ $sale->customer?->name ?? '-' }}</strong>
                                        <span class="cell-muted">{{ $sale->customer?->phone ?? '-' }}</span>
                                    </span>
                                </div>
                            </td>
                            <td class="align-end nowrap">{{ \App\Support\Format::rupiah($sale->cost_price) }}</td>
                            <td class="align-end nowrap"><strong>{{ \App\Support\Format::rupiah($sale->price) }}</strong></td>
                            <td class="align-end nowrap">
                                <span class="profit {{ $sale->profit() >= 0 ? 'profit-positive' : 'profit-negative' }}">
                                    {{ $sale->profit() >= 0 ? '+' : '' }}{{ \App\Support\Format::rupiah($sale->profit()) }}
                                </span>
                            </td>
                            <td class="cell-actions">
                                <a class="action-btn" href="{{ route('sales.show', $sale) }}" title="Lihat detail" aria-label="Detail {{ $sale->code }}">
                                    <x-icon name="eye" :size="16" />
                                </a>
                                @if ($sale->goat)
                                    <a class="action-btn" href="{{ route('goats.show', $sale->goat) }}" title="Buka data kambing" aria-label="Buka data kambing">
                                        <x-icon name="goat" :size="16" />
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="empty-row">
                                <div class="empty-state">
                                    <span class="empty-icon"><x-icon name="tag" :size="30" /></span>
                                    <h3>{{ $search !== '' || $filter !== '' ? 'Transaksi tidak ditemukan' : 'Belum ada transaksi penjualan' }}</h3>
                                    <p>{{ $search !== '' || $filter !== '' ? 'Coba ubah kata kunci atau reset filter.' : 'Catat penjualan kambing pertama Anda untuk mulai mencatat keuntungan.' }}</p>
                                    @can('create', App\Models\Sale::class)
                                        <div class="empty-action">
                                            <a class="btn btn-primary btn-sm" href="{{ route('sales.create') }}"><x-icon name="plus" :size="15" /> Catat Penjualan</a>
                                        </div>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.pagination', ['paginator' => $sales])
    </section>
@endsection
