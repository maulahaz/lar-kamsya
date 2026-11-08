@extends('layouts.app')

@section('title', 'Dashboard')
@section('breadcrumb', 'Dashboard')

@section('content')
    <div class="page-head">
        <div class="page-head-text">
            <span class="eyebrow">Dashboard</span>
            <h1>{{ $greeting }}, {{ auth()->user()->name }} 👋</h1>
            <p>Pantau stok kambing, transaksi, dan performa usaha Anda hari ini.</p>
        </div>
        <div class="page-actions">
            @can('view-reports')
                <a class="btn btn-outline" href="{{ route('reports.index') }}">
                    <x-icon name="chart" :size="16" /> Buka Laporan
                </a>
            @endcan
            <a class="btn btn-primary" href="{{ route('scan') }}">
                <x-icon name="scan" :size="16" /> Scan Barcode
            </a>
        </div>
    </div>

    <section class="stats-grid {{ auth()->user()->canManageFinance() ? '' : 'stats-grid-4' }}" aria-label="Statistik utama">
        <a class="stat-card stat-green" href="{{ route('goats.index') }}">
            <span class="stat-icon"><x-icon name="goat" :size="21" /></span>
            <span class="stat-content">
                <span class="stat-label">Total Kambing</span>
                <strong class="stat-value">{{ \App\Support\Format::number($stats['total_goats']) }}</strong>
                <span class="stat-meta"><span class="stat-hint">{{ $stats['available_count'] }} tersedia · {{ $stats['sold_count'] }} terjual</span></span>
            </span>
        </a>
        <a class="stat-card stat-blue" href="{{ route('goats.index', ['status' => 'tersedia']) }}">
            <span class="stat-icon"><x-icon name="package" :size="21" /></span>
            <span class="stat-content">
                <span class="stat-label">Kambing Tersedia</span>
                <strong class="stat-value">{{ \App\Support\Format::number($stats['available_count']) }}</strong>
                <span class="stat-meta"><span class="stat-hint">{{ $stats['available_purchased'] }} pembelian · {{ $stats['available_offspring'] }} peranakan</span></span>
            </span>
        </a>
        <a class="stat-card stat-rose" href="{{ route('goats.index', ['status' => 'terjual']) }}">
            <span class="stat-icon"><x-icon name="tag" :size="21" /></span>
            <span class="stat-content">
                <span class="stat-label">Kambing Terjual</span>
                <strong class="stat-value">{{ \App\Support\Format::number($stats['sold_count']) }}</strong>
                <span class="stat-meta"><span class="stat-hint">{{ $stats['total_sale_count'] }} transaksi tercatat</span></span>
            </span>
        </a>

        @if (auth()->user()->canManageFinance())
            <a class="stat-card stat-indigo" href="{{ route('purchases.index') }}">
                <span class="stat-icon"><x-icon name="cart" :size="21" /></span>
                <span class="stat-content">
                    <span class="stat-label">Total Pembelian</span>
                    <strong class="stat-value">{{ \App\Support\Format::rupiah($stats['total_purchases']) }}</strong>
                    <span class="stat-meta"><span class="stat-hint">{{ $stats['total_purchase_count'] }} transaksi · {{ \App\Support\Format::rupiahShort($stats['total_additional']) }} biaya tambahan</span></span>
                </span>
            </a>
            <a class="stat-card stat-teal" href="{{ route('sales.index') }}">
                <span class="stat-icon"><x-icon name="wallet" :size="21" /></span>
                <span class="stat-content">
                    <span class="stat-label">Total Penjualan</span>
                    <strong class="stat-value">{{ \App\Support\Format::rupiah($stats['total_sales']) }}</strong>
                    <span class="stat-meta"><span class="stat-hint">{{ $stats['total_sale_count'] }} transaksi penjualan</span></span>
                </span>
            </a>
            <a class="stat-card stat-amber" href="{{ route('reports.index', ['tab' => 'laba-rugi']) }}">
                <span class="stat-icon"><x-icon name="trending-up" :size="21" /></span>
                <span class="stat-content">
                    <span class="stat-label">Laba Kotor</span>
                    <strong class="stat-value">{{ \App\Support\Format::rupiah($stats['gross_profit']) }}</strong>
                    <span class="stat-meta">
                        <span class="stat-hint">
                            <span class="trend-up"><x-icon name="trending-up" :size="13" /> {{ \App\Support\Format::number($stats['margin'], 1) }}%</span>
                            margin kotor
                        </span>
                    </span>
                </span>
            </a>
        @endif
    </section>

    <section class="quick-actions-card" aria-label="Aksi cepat">
        <div class="quick-actions-title">
            <span class="quick-icon"><x-icon name="sparkles" :size="17" /></span>
            <div>
                <strong>Aksi Cepat</strong>
                <span>Mulai pekerjaan harian Anda</span>
            </div>
        </div>
        <div class="quick-actions">
            @can('create', App\Models\Goat::class)
                <a class="quick-action" href="{{ route('goats.create') }}">
                    <span class="quick-action-icon"><x-icon name="plus" :size="18" /></span>
                    <span><strong>Tambah Kambing</strong><small>Catat kambing peranakan</small></span>
                </a>
            @endcan
            <a class="quick-action" href="{{ route('scan') }}">
                <span class="quick-action-icon"><x-icon name="scan" :size="18" /></span>
                <span><strong>Scan Barcode</strong><small>Cari data kambing</small></span>
            </a>
            @can('manage-transactions')
                <a class="quick-action" href="{{ route('purchases.create') }}">
                    <span class="quick-action-icon"><x-icon name="cart" :size="18" /></span>
                    <span><strong>Catat Pembelian</strong><small>Kambing masuk</small></span>
                </a>
                <a class="quick-action" href="{{ route('sales.create') }}">
                    <span class="quick-action-icon"><x-icon name="tag" :size="18" /></span>
                    <span><strong>Catat Penjualan</strong><small>Kambing keluar</small></span>
                </a>
            @endcan
        </div>
    </section>

    <div class="dashboard-grid">
        @if (auth()->user()->canManageFinance())
            <section class="card chart-card">
                <div class="card-header">
                    <div>
                        <h2>Arus Transaksi</h2>
                        <p>Nilai pembelian dan penjualan 6 bulan terakhir</p>
                    </div>
                    <a class="btn btn-ghost btn-sm" href="{{ route('reports.index') }}">Lihat laporan <x-icon name="arrow-right" :size="15" /></a>
                </div>
                <div class="card-body">
                    <div class="chart-split">
                        <div class="chart-block">
                            <div class="chart-block-head">
                                <span><span class="legend-dot legend-purchase"></span>Pembelian</span>
                                <strong>{{ \App\Support\Format::rupiahShort($months->sum('purchases')) }}</strong>
                            </div>
                            @include('partials.bar-chart', ['points' => $months->map(fn ($m) => ['label' => $m['label'], 'full' => $m['full'], 'value' => $m['purchases']]), 'color' => 'green'])
                        </div>
                        <div class="chart-block">
                            <div class="chart-block-head">
                                <span><span class="legend-dot legend-sale"></span>Penjualan</span>
                                <strong>{{ \App\Support\Format::rupiahShort($months->sum('sales')) }}</strong>
                            </div>
                            @include('partials.bar-chart', ['points' => $months->map(fn ($m) => ['label' => $m['label'], 'full' => $m['full'], 'value' => $m['sales']]), 'color' => 'blue'])
                        </div>
                    </div>
                </div>
            </section>
        @endif

        <section class="card stock-card">
            <div class="card-header">
                <div>
                    <h2>Ringkasan Stok</h2>
                    <p>Komposisi kambing saat ini</p>
                </div>
                <a class="icon-link" href="{{ route('goats.index') }}" title="Buka data kambing" aria-label="Buka data kambing">
                    <x-icon name="arrow-right" :size="17" />
                </a>
            </div>
            <div class="card-body">
                <div class="stock-overview">
                    <div class="donut" style="--value: {{ $availableRatio }}%">
                        <div class="donut-center">
                            <strong>{{ $stats['available_count'] }}</strong>
                            <span>tersedia</span>
                        </div>
                    </div>
                    <div class="stock-legend">
                        <div><span class="legend-dot legend-available"></span><span>Tersedia</span><strong>{{ $stats['available_count'] }}</strong></div>
                        <div><span class="legend-dot legend-sold"></span><span>Terjual</span><strong>{{ $stats['sold_count'] }}</strong></div>
                        <div><span class="legend-dot legend-total"></span><span>Total</span><strong>{{ $stats['total_goats'] }}</strong></div>
                    </div>
                </div>

                @if (auth()->user()->canManageFinance() && $stats['available_count'] <= ($settings->low_stock_limit ?? 5))
                    <div class="inline-alert inline-alert-warning">
                        <x-icon name="alert-triangle" :size="16" />
                        <span>Stok tersedia tinggal {{ $stats['available_count'] }} ekor. Pertimbangkan menambah stok.</span>
                    </div>
                @endif

                <div class="type-list">
                    <p class="section-label">Stok per jenis</p>
                    @forelse ($stats['by_type'] as $item)
                        <div class="type-row">
                            <div class="type-row-head">
                                <span>{{ $item['type'] }}</span>
                                <span class="type-row-value">{{ $item['available'] }}<span class="muted">/{{ $item['total'] }}</span></span>
                            </div>
                            <div class="progress"><span style="width: {{ $item['total'] > 0 ? round(($item['available'] / $item['total']) * 100) : 0 }}%"></span></div>
                        </div>
                    @empty
                        <p class="muted small">Belum ada data jenis kambing.</p>
                    @endforelse
                </div>

                @can('view-reports')
                    <div class="stock-value">
                        <span>Nilai stok tersedia</span>
                        <strong>{{ \App\Support\Format::rupiah($stats['inventory_value']) }}</strong>
                    </div>
                @endcan
            </div>
        </section>
    </div>

    @if (auth()->user()->canManageFinance())
        <section class="card" style="margin-top: 18px;">
            <div class="card-header">
                <div>
                    <h2>Transaksi Terbaru</h2>
                    <p>Pembelian dan penjualan terakhir yang tercatat</p>
                </div>
                <a class="btn btn-outline btn-sm" href="{{ route('reports.index', ['tab' => 'penjualan']) }}">Semua transaksi</a>
            </div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Jenis</th>
                            <th>Kambing</th>
                            <th>Supplier / Pembeli</th>
                            <th>Tanggal</th>
                            <th class="align-end">Nominal</th>
                            <th class="th-actions">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recent as $row)
                            <tr>
                                <td>
                                    <span class="txn-type {{ $row['type'] === 'pembelian' ? 'txn-purchase' : 'txn-sale' }}">
                                        <x-icon :name="$row['type'] === 'pembelian' ? 'cart' : 'tag'" :size="14" />
                                        {{ $row['type'] === 'pembelian' ? 'Pembelian' : 'Penjualan' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="cell-stack">
                                        <strong>{{ $row['goat']?->name ?? '-' }}</strong>
                                        <span class="cell-muted">{{ $row['goat']?->barcode ?? '-' }}</span>
                                    </div>
                                </td>
                                <td>{{ $row['party'] ?? '-' }}</td>
                                <td class="nowrap">{{ \App\Support\Format::date($row['date'], true) }}</td>
                                <td class="nowrap">
                                    <div class="cell-stack align-end">
                                        <strong>{{ \App\Support\Format::rupiah($row['amount']) }}</strong>
                                        @if ($row['profit'] !== null)
                                            <span class="profit {{ $row['profit'] >= 0 ? 'profit-positive' : 'profit-negative' }}">
                                                {{ $row['profit'] >= 0 ? '+' : '' }}{{ \App\Support\Format::rupiah($row['profit']) }}
                                            </span>
                                        @else
                                            <span class="cell-muted">Total transaksi</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="cell-actions">
                                    <a class="action-btn" href="{{ $row['route'] }}" title="Lihat transaksi" aria-label="Lihat transaksi">
                                        <x-icon name="eye" :size="16" />
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="empty-row">
                                    <div class="empty-state">
                                        <span class="empty-icon"><x-icon name="file-text" :size="30" /></span>
                                        <h3>Belum ada transaksi</h3>
                                        <p>Transaksi pembelian dan penjualan akan tampil di sini.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif
@endsection
