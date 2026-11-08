@extends('layouts.app')

@section('title', 'Laporan')
@section('breadcrumb', 'Laporan')

@php
    $fmt = \App\Support\Format::class;
    $tabLabels = [
        'laba-rugi' => 'Laba Rugi',
        'arus-kas' => 'Arus Kas',
        'piutang' => 'Piutang',
        'utang' => 'Utang',
        'laba-kambing' => 'Laba per Kambing',
        'penjualan' => 'Penjualan',
        'pembelian' => 'Pembelian',
        'stok' => 'Valuasi Stok',
    ];
    $summary = $data['summary'];
    $queryBase = ['tab' => $tab, 'from' => $from->toDateString(), 'to' => $to->toDateString()];
@endphp

@section('content')
    <div class="page-head">
        <div class="page-head-text">
            <span class="eyebrow">Analitik</span>
            <h1>Laporan Keuangan</h1>
            <p>Laba rugi, arus kas, piutang, utang, dan valuasi stok dalam satu tempat.</p>
        </div>
        <div class="page-actions">
            <a class="btn btn-outline" href="{{ route('reports.csv', $queryBase) }}">
                <x-icon name="download" :size="16" /> Ekspor CSV
            </a>
            <a class="btn btn-outline" href="{{ route('reports.pdf', $queryBase) }}">
                <x-icon name="file-text" :size="16" /> Unduh PDF
            </a>
            <button type="button" class="btn btn-primary" data-print="report">
                <x-icon name="printer" :size="16" /> Cetak
            </button>
        </div>
    </div>

    <div class="tab-nav" role="tablist">
        @foreach ($tabs as $item)
            <a class="tab-link {{ $tab === $item ? 'is-active' : '' }}"
               href="{{ route('reports.index', ['tab' => $item, 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}"
               role="tab" @if ($tab === $item) aria-selected="true" @endif>
                {{ $tabLabels[$item] ?? $item }}
            </a>
        @endforeach
    </div>

    <section class="card report-filter-card">
        <form method="GET" action="{{ route('reports.index') }}" class="report-filter">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="form-group">
                <label for="from">Dari tanggal</label>
                <input id="from" type="date" name="from" value="{{ $from->toDateString() }}">
            </div>
            <div class="form-group">
                <label for="to">Sampai tanggal</label>
                <input id="to" type="date" name="to" value="{{ $to->toDateString() }}">
            </div>
            <button type="submit" class="btn btn-primary"><x-icon name="filter" :size="15" /> Terapkan</button>
            <div class="filter-group">
                <a class="btn btn-ghost btn-sm" href="{{ route('reports.index', ['tab' => $tab, 'from' => now()->startOfMonth()->toDateString(), 'to' => now()->toDateString()]) }}">Bulan ini</a>
                <a class="btn btn-ghost btn-sm" href="{{ route('reports.index', ['tab' => $tab, 'from' => now()->startOfYear()->toDateString(), 'to' => now()->toDateString()]) }}">Tahun ini</a>
            </div>
        </form>
    </section>

    <div class="report-summary-strip">
        <span><x-icon name="calendar" :size="15" /> Periode <strong>{{ $fmt::date($from, true) }} – {{ $fmt::date($to, true) }}</strong></span>
        <span><x-icon name="cart" :size="15" /> Pembelian <strong>{{ $fmt::rupiah($summary['purchase_total']) }}</strong></span>
        <span><x-icon name="tag" :size="15" /> Penjualan <strong>{{ $fmt::rupiah($summary['sales_total']) }}</strong></span>
        <span><x-icon name="wallet" :size="15" /> Nilai stok <strong>{{ $fmt::rupiah($summary['inventory_value']) }}</strong></span>
    </div>

    @switch($tab)
        @case('laba-rugi')
            <section class="metric-grid" aria-label="Ringkasan laba rugi">
                <div class="metric-card">
                    <span class="metric-icon metric-green"><x-icon name="wallet" :size="19" /></span>
                    <div><span>Penjualan</span><strong>{{ $fmt::rupiah($summary['sales_total']) }}</strong></div>
                </div>
                <div class="metric-card">
                    <span class="metric-icon metric-blue"><x-icon name="goat" :size="19" /></span>
                    <div><span>HPP</span><strong>{{ $fmt::rupiah($summary['cogs']) }}</strong></div>
                </div>
                <div class="metric-card">
                    <span class="metric-icon metric-amber"><x-icon name="trending-up" :size="19" /></span>
                    <div><span>Laba kotor</span><strong>{{ $fmt::rupiah($summary['gross_profit']) }}</strong></div>
                </div>
                <div class="metric-card">
                    <span class="metric-icon metric-purple"><x-icon name="file-text" :size="19" /></span>
                    <div><span>Laba bersih</span><strong>{{ $fmt::rupiah($summary['net_profit']) }}</strong></div>
                </div>
            </section>

            <div class="chart-grid-2">
                <section class="card">
                    <div class="card-header">
                        <div>
                            <h2>Laporan Laba Rugi</h2>
                            <p>Ringkasan pendapatan dan beban</p>
                        </div>
                        <span class="badge badge-success">{{ number_format($summary['margin'], 1) }}% margin</span>
                    </div>
                    <div class="table-wrap">
                        <table class="data-table">
                            <tbody>
                                <tr><td>Penjualan</td><td class="align-end nowrap"><strong>{{ $fmt::rupiah($summary['sales_total']) }}</strong></td></tr>
                                <tr><td>Harga pokok penjualan (HPP)</td><td class="align-end nowrap">({{ $fmt::rupiah($summary['cogs']) }})</td></tr>
                                <tr><td><strong>Laba kotor</strong></td><td class="align-end nowrap"><strong class="profit-positive">{{ $fmt::rupiah($summary['gross_profit']) }}</strong></td></tr>
                                <tr><td>Beban operasional</td><td class="align-end nowrap">({{ $fmt::rupiah($summary['expense_total']) }})</td></tr>
                                <tr><td><strong>Laba bersih</strong></td><td class="align-end nowrap"><strong class="{{ $summary['net_profit'] >= 0 ? 'profit-positive' : 'profit-negative' }}">{{ $fmt::rupiah($summary['net_profit']) }}</strong></td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="card">
                    <div class="card-header">
                        <div>
                            <h2>Beban per Kategori</h2>
                            <p>Total {{ $fmt::rupiah($summary['expense_total']) }}</p>
                        </div>
                        <x-icon name="wallet" :size="19" />
                    </div>
                    @if (empty($data['expenses_by_category']))
                        <div class="card-body">
                            <div class="empty-state">
                                <span class="empty-icon"><x-icon name="wallet" :size="30" /></span>
                                <h3>Belum ada beban</h3>
                                <p>Beban operasional pada periode ini belum tercatat.</p>
                            </div>
                        </div>
                    @else
                        <div class="table-wrap">
                            <table class="data-table">
                                <thead><tr><th>Kategori</th><th class="align-end">Jumlah</th><th class="align-end">Nilai</th></tr></thead>
                                <tbody>
                                    @foreach ($data['expenses_by_category'] as $row)
                                        <tr>
                                            <td>{{ $row['label'] }}</td>
                                            <td class="align-end">{{ $row['count'] }}</td>
                                            <td class="align-end nowrap"><strong>{{ $fmt::rupiah($row['total']) }}</strong></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>
            </div>
            @break

        @case('arus-kas')
            <section class="metric-grid" aria-label="Ringkasan arus kas">
                <div class="metric-card">
                    <span class="metric-icon metric-green"><x-icon name="wallet-in" :size="19" /></span>
                    <div><span>Kas masuk</span><strong>{{ $fmt::rupiah($summary['cash_in']) }}</strong></div>
                </div>
                <div class="metric-card">
                    <span class="metric-icon metric-blue"><x-icon name="cart" :size="19" /></span>
                    <div><span>Bayar pembelian</span><strong>{{ $fmt::rupiah($summary['cash_out_purchases']) }}</strong></div>
                </div>
                <div class="metric-card">
                    <span class="metric-icon metric-amber"><x-icon name="wallet" :size="19" /></span>
                    <div><span>Beban operasional</span><strong>{{ $fmt::rupiah($summary['cash_out_expenses']) }}</strong></div>
                </div>
                <div class="metric-card">
                    <span class="metric-icon {{ $summary['net_cash'] >= 0 ? 'metric-purple' : 'metric-rose' }}"><x-icon name="trending-up" :size="19" /></span>
                    <div><span>Arus kas bersih</span><strong>{{ $fmt::rupiah($summary['net_cash']) }}</strong></div>
                </div>
            </section>

            <div class="chart-grid-2">
                <section class="card">
                    <div class="card-header">
                        <div><h2>Kas Masuk</h2><p>Pembayaran dari penjualan</p></div>
                        <span class="badge badge-success">{{ $fmt::rupiah($summary['cash_in']) }}</span>
                    </div>
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead><tr><th>Kode</th><th>Tanggal</th><th>Transaksi</th><th class="align-end">Nominal</th></tr></thead>
                            <tbody>
                                @forelse ($data['payments_in'] as $payment)
                                    <tr>
                                        <td><span class="barcode-text">{{ $payment->code }}</span></td>
                                        <td class="nowrap">{{ $fmt::date($payment->date, true) }}</td>
                                        <td>{{ $payment->payable?->code ?? '-' }}</td>
                                        <td class="align-end nowrap"><strong>{{ $fmt::rupiah($payment->amount) }}</strong></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="empty-row"><div class="empty-state"><h3>Tidak ada kas masuk</h3><p>Belum ada pembayaran penjualan pada periode ini.</p></div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="card">
                    <div class="card-header">
                        <div><h2>Kas Keluar</h2><p>Pembelian dan beban operasional</p></div>
                        <span class="badge badge-danger">{{ $fmt::rupiah($summary['cash_out_purchases'] + $summary['cash_out_expenses']) }}</span>
                    </div>
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead><tr><th>Kode</th><th>Tanggal</th><th>Transaksi</th><th class="align-end">Nominal</th></tr></thead>
                            <tbody>
                                @foreach ($data['payments_out'] as $payment)
                                    <tr>
                                        <td><span class="barcode-text">{{ $payment->code }}</span></td>
                                        <td class="nowrap">{{ $fmt::date($payment->date, true) }}</td>
                                        <td>{{ $payment->payable?->code ?? '-' }}</td>
                                        <td class="align-end nowrap"><strong>{{ $fmt::rupiah($payment->amount) }}</strong></td>
                                    </tr>
                                @endforeach
                                @foreach ($data['expenses'] as $expense)
                                    <tr>
                                        <td><span class="barcode-text">{{ $expense->code }}</span></td>
                                        <td class="nowrap">{{ $fmt::date($expense->date, true) }}</td>
                                        <td>{{ $expense->category?->label() ?? 'Beban' }}</td>
                                        <td class="align-end nowrap"><strong>{{ $fmt::rupiah($expense->amount) }}</strong></td>
                                    </tr>
                                @endforeach
                                @if ($data['payments_out']->isEmpty() && $data['expenses']->isEmpty())
                                    <tr><td colspan="4" class="empty-row"><div class="empty-state"><h3>Tidak ada kas keluar</h3><p>Belum ada pengeluaran pada periode ini.</p></div></td></tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
            @break

        @case('piutang')
        @case('utang')
            @php($aging = $data['aging'])
            <section class="metric-grid metric-grid-3" aria-label="Ringkasan umur tagihan">
                <div class="metric-card">
                    <span class="metric-icon metric-green"><x-icon name="clock" :size="19" /></span>
                    <div><span>0–30 hari</span><strong>{{ $fmt::rupiah($aging['buckets']['0-30']) }}</strong></div>
                </div>
                <div class="metric-card">
                    <span class="metric-icon metric-amber"><x-icon name="clock" :size="19" /></span>
                    <div><span>31–60 hari</span><strong>{{ $fmt::rupiah($aging['buckets']['31-60']) }}</strong></div>
                </div>
                <div class="metric-card">
                    <span class="metric-icon metric-rose"><x-icon name="alert-triangle" :size="19" /></span>
                    <div><span>&gt; 60 hari</span><strong>{{ $fmt::rupiah($aging['buckets']['>60']) }}</strong></div>
                </div>
            </section>

            <section class="card report-table-card">
                <div class="card-header">
                    <div>
                        <h2>{{ $tab === 'piutang' ? 'Daftar Piutang Customer' : 'Daftar Utang Supplier' }}</h2>
                        <p>{{ $tab === 'piutang' ? 'Tagihan penjualan yang belum dibayar penuh' : 'Tagihan pembelian yang belum dibayar penuh' }}</p>
                    </div>
                    <span class="badge badge-warning">{{ $fmt::rupiah($aging['total']) }}</span>
                </div>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Kode</th><th>Tanggal</th><th>{{ $tab === 'piutang' ? 'Customer' : 'Supplier' }}</th><th>Kambing</th>
                                <th class="align-end">Nilai</th><th class="align-end">Dibayar</th><th class="align-end">Sisa</th><th class="align-end">Umur</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($aging['rows'] as $row)
                                <tr>
                                    <td><span class="barcode-text">{{ $row->code }}</span></td>
                                    <td class="nowrap">{{ $fmt::date($row->date, true) }}</td>
                                    <td>{{ $tab === 'piutang' ? ($row->customer?->name ?? '-') : ($row->supplier?->name ?? '-') }}</td>
                                    <td>{{ $row->goat?->code ?? '-' }}</td>
                                    <td class="align-end nowrap">{{ $fmt::rupiah($tab === 'piutang' ? $row->price : $row->total) }}</td>
                                    <td class="align-end nowrap">{{ $fmt::rupiah($row->paid_total) }}</td>
                                    <td class="align-end nowrap"><strong class="text-danger">{{ $fmt::rupiah($row->remaining_amount) }}</strong></td>
                                    <td class="align-end">{{ $row->age_days }} hari</td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="empty-row"><div class="empty-state"><span class="empty-icon"><x-icon name="check-circle" :size="30" /></span><h3>Tidak ada tagihan</h3><p>Semua tagihan pada periode ini sudah lunas.</p></div></td></tr>
                            @endforelse
                        </tbody>
                        @if ($aging['rows']->isNotEmpty())
                            <tfoot>
                                <tr><td colspan="6">Total</td><td class="align-end"><strong>{{ $fmt::rupiah($aging['total']) }}</strong></td><td></td></tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </section>
            @break

        @case('laba-kambing')
            <section class="card report-table-card">
                <div class="card-header">
                    <div><h2>Laba per Kambing</h2><p>Harga jual, HPP snapshot, dan laba setiap penjualan</p></div>
                    <span class="badge badge-success">{{ $fmt::rupiah($summary['gross_profit']) }}</span>
                </div>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr><th>Kode Penjualan</th><th>Tanggal</th><th>Kambing</th><th>Customer</th><th class="align-end">Harga Jual</th><th class="align-end">HPP</th><th class="align-end">Laba</th><th class="align-end">Margin</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($data['profits'] as $row)
                                <tr>
                                    <td><a class="cell-title" href="{{ route('sales.show', $row['sale']) }}"><span class="barcode-text">{{ $row['sale']->code }}</span></a></td>
                                    <td class="nowrap">{{ $fmt::date($row['sale']->date, true) }}</td>
                                    <td>{{ $row['goat']?->code }} · {{ $row['goat']?->name }}</td>
                                    <td>{{ $row['sale']->customer?->name ?? '-' }}</td>
                                    <td class="align-end nowrap">{{ $fmt::rupiah($row['price']) }}</td>
                                    <td class="align-end nowrap">{{ $fmt::rupiah($row['cost_price']) }}</td>
                                    <td class="align-end nowrap"><strong class="{{ $row['profit'] >= 0 ? 'profit-positive' : 'profit-negative' }}">{{ $row['profit'] >= 0 ? '+' : '' }}{{ $fmt::rupiah($row['profit']) }}</strong></td>
                                    <td class="align-end">{{ $row['price'] > 0 ? number_format(($row['profit'] / $row['price']) * 100, 1) : '0,0' }}%</td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="empty-row"><div class="empty-state"><h3>Belum ada penjualan</h3><p>Laba per kambing muncul setelah ada transaksi penjualan.</p></div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
            @break

        @case('penjualan')
            <div class="chart-grid-2">
                <section class="card">
                    <div class="card-header"><div><h2>Penjualan per Customer</h2><p>Kontribusi pembeli terbesar</p></div><x-icon name="users" :size="19" /></div>
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead><tr><th>Customer</th><th class="align-end">Transaksi</th><th class="align-end">Total</th><th class="align-end">Laba</th></tr></thead>
                            <tbody>
                                @forelse ($data['by_customer'] as $row)
                                    <tr><td>{{ $row['name'] }}</td><td class="align-end">{{ $row['count'] }}</td><td class="align-end nowrap">{{ $fmt::rupiah($row['total']) }}</td><td class="align-end nowrap"><span class="profit-positive">{{ $fmt::rupiah($row['profit']) }}</span></td></tr>
                                @empty
                                    <tr><td colspan="4" class="empty-row"><div class="empty-state"><h3>Tidak ada penjualan</h3></div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="card">
                    <div class="card-header"><div><h2>Penjualan per Jenis</h2><p>Distribusi jenis kambing terjual</p></div><x-icon name="goat" :size="19" /></div>
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead><tr><th>Jenis</th><th class="align-end">Ekor</th><th class="align-end">Total</th><th class="align-end">Laba</th></tr></thead>
                            <tbody>
                                @forelse ($data['by_type'] as $row)
                                    <tr><td>{{ $row['type'] }}</td><td class="align-end">{{ $row['count'] }}</td><td class="align-end nowrap">{{ $fmt::rupiah($row['total']) }}</td><td class="align-end nowrap"><span class="profit-positive">{{ $fmt::rupiah($row['profit']) }}</span></td></tr>
                                @empty
                                    <tr><td colspan="4" class="empty-row"><div class="empty-state"><h3>Tidak ada penjualan</h3></div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <section class="card report-table-card">
                <div class="card-header">
                    <div><h2>Rekap Penjualan</h2><p>{{ $data['sales']->count() }} transaksi pada periode ini</p></div>
                    <span class="badge badge-success">{{ $fmt::rupiah($summary['sales_total']) }}</span>
                </div>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead><tr><th>Kode</th><th>Tanggal</th><th>Kambing</th><th>Customer</th><th class="align-end">HPP</th><th class="align-end">Harga Jual</th><th class="align-end">Laba</th></tr></thead>
                        <tbody>
                            @forelse ($data['sales'] as $sale)
                                <tr>
                                    <td><a class="cell-title" href="{{ route('sales.show', $sale) }}"><span class="barcode-text">{{ $sale->code }}</span></a></td>
                                    <td class="nowrap">{{ $fmt::date($sale->date, true) }}</td>
                                    <td>{{ $sale->goat?->code }}</td>
                                    <td>{{ $sale->customer?->name ?? '-' }}</td>
                                    <td class="align-end nowrap">{{ $fmt::rupiah($sale->cost_price) }}</td>
                                    <td class="align-end nowrap"><strong>{{ $fmt::rupiah($sale->price) }}</strong></td>
                                    <td class="align-end nowrap"><span class="profit-positive">{{ $fmt::rupiah($sale->profit()) }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="empty-row"><div class="empty-state"><h3>Tidak ada penjualan</h3><p>Tidak ada transaksi penjualan pada periode yang dipilih.</p></div></td></tr>
                            @endforelse
                        </tbody>
                        @if ($data['sales']->isNotEmpty())
                            <tfoot><tr><td colspan="5">Total penjualan</td><td class="align-end"><strong>{{ $fmt::rupiah($summary['sales_total']) }}</strong></td><td class="align-end"><strong class="profit-positive">{{ $fmt::rupiah($summary['gross_profit']) }}</strong></td></tr></tfoot>
                        @endif
                    </table>
                </div>
            </section>
            @break

        @case('pembelian')
            <div class="chart-grid-2">
                <section class="card">
                    <div class="card-header"><div><h2>Pembelian per Supplier</h2><p>Kontribusi supplier terbesar</p></div><x-icon name="truck" :size="19" /></div>
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead><tr><th>Supplier</th><th class="align-end">Transaksi</th><th class="align-end">Total</th><th class="align-end">Biaya Tambahan</th></tr></thead>
                            <tbody>
                                @forelse ($data['by_supplier'] as $row)
                                    <tr><td>{{ $row['name'] }}</td><td class="align-end">{{ $row['count'] }}</td><td class="align-end nowrap">{{ $fmt::rupiah($row['total']) }}</td><td class="align-end nowrap">{{ $fmt::rupiah($row['additional']) }}</td></tr>
                                @empty
                                    <tr><td colspan="4" class="empty-row"><div class="empty-state"><h3>Tidak ada pembelian</h3></div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="card">
                    <div class="card-header"><div><h2>Pembelian per Bulan</h2><p>Nilai pembelian bulanan</p></div><x-icon name="calendar" :size="19" /></div>
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead><tr><th>Bulan</th><th class="align-end">Transaksi</th><th class="align-end">Total</th></tr></thead>
                            <tbody>
                                @forelse ($data['by_month'] as $row)
                                    <tr><td>{{ $row['label'] }}</td><td class="align-end">{{ $row['count'] }}</td><td class="align-end nowrap">{{ $fmt::rupiah($row['total']) }}</td></tr>
                                @empty
                                    <tr><td colspan="3" class="empty-row"><div class="empty-state"><h3>Tidak ada pembelian</h3></div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <section class="card report-table-card">
                <div class="card-header">
                    <div><h2>Rekap Pembelian</h2><p>{{ $data['purchases']->count() }} transaksi pada periode ini</p></div>
                    <span class="badge badge-info">{{ $fmt::rupiah($summary['purchase_total']) }}</span>
                </div>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead><tr><th>Kode</th><th>Tanggal</th><th>Supplier</th><th>Kambing</th><th class="align-end">Harga Beli</th><th class="align-end">Biaya</th><th class="align-end">Total</th></tr></thead>
                        <tbody>
                            @forelse ($data['purchases'] as $purchase)
                                <tr>
                                    <td><a class="cell-title" href="{{ route('purchases.show', $purchase) }}"><span class="barcode-text">{{ $purchase->code }}</span></a></td>
                                    <td class="nowrap">{{ $fmt::date($purchase->date, true) }}</td>
                                    <td>{{ $purchase->supplier?->name ?? '-' }}</td>
                                    <td>{{ $purchase->goat?->code }}</td>
                                    <td class="align-end nowrap">{{ $fmt::rupiah($purchase->price) }}</td>
                                    <td class="align-end nowrap">{{ $fmt::rupiah($purchase->additional_cost) }}</td>
                                    <td class="align-end nowrap"><strong>{{ $fmt::rupiah($purchase->total) }}</strong></td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="empty-row"><div class="empty-state"><h3>Tidak ada pembelian</h3><p>Tidak ada transaksi pembelian pada periode yang dipilih.</p></div></td></tr>
                            @endforelse
                        </tbody>
                        @if ($data['purchases']->isNotEmpty())
                            <tfoot><tr><td colspan="6">Total pembelian</td><td class="align-end"><strong>{{ $fmt::rupiah($summary['purchase_total']) }}</strong></td></tr></tfoot>
                        @endif
                    </table>
                </div>
            </section>
            @break

        @case('stok')
            <section class="card report-table-card">
                <div class="card-header">
                    <div><h2>Valuasi Stok Tersedia</h2><p>{{ $data['inventory']->count() }} kambing tersedia · HPP penuh</p></div>
                    <span class="badge badge-success">{{ $fmt::rupiah($summary['inventory_value']) }}</span>
                </div>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead><tr><th>Kode</th><th>Barcode</th><th>Nama</th><th>Jenis</th><th>Gender</th><th class="align-end">Bobot</th><th class="align-end">Biaya Perolehan</th></tr></thead>
                        <tbody>
                            @forelse ($data['inventory'] as $goat)
                                <tr>
                                    <td><a class="cell-title" href="{{ route('goats.show', $goat) }}">{{ $goat->code }}</a></td>
                                    <td><span class="barcode-text">{{ $goat->barcode }}</span></td>
                                    <td>{{ $goat->name }}</td>
                                    <td>{{ $goat->type }}</td>
                                    <td>{{ $goat->gender->label() }}</td>
                                    <td class="align-end nowrap">{{ $fmt::weight($goat->weight) }}</td>
                                    <td class="align-end nowrap"><strong>{{ $fmt::rupiah($goat->acquisitionCost()) }}</strong></td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="empty-row"><div class="empty-state"><h3>Stok kosong</h3><p>Tidak ada kambing tersedia saat ini.</p></div></td></tr>
                            @endforelse
                        </tbody>
                        @if ($data['inventory']->isNotEmpty())
                            <tfoot><tr><td colspan="6">Total nilai stok</td><td class="align-end"><strong>{{ $fmt::rupiah($summary['inventory_value']) }}</strong></td></tr></tfoot>
                        @endif
                    </table>
                </div>
            </section>
            @break
    @endswitch
@endsection
