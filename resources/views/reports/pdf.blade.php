<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan {{ $tab }} — {{ $settings->farm_name }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: "DejaVu Sans", Arial, sans-serif;
            font-size: 10px;
            color: #1f2937;
            margin: 0;
            padding: 18px 22px;
        }
        h1 { font-size: 19px; margin: 0 0 2px; color: #14532d; }
        h2 { font-size: 13px; margin: 18px 0 6px; color: #14532d; }
        .muted { color: #6b7280; }
        .head {
            border-bottom: 2px solid #166534;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }
        .head-table { width: 100%; border-collapse: collapse; }
        .head-table td { vertical-align: top; padding: 0; }
        .brand { font-size: 22px; font-weight: bold; color: #166534; }
        .brand small { display: block; font-size: 9px; font-weight: normal; color: #6b7280; letter-spacing: 1px; text-transform: uppercase; }
        .meta { text-align: right; font-size: 9px; line-height: 1.55; }
        .meta strong { color: #111827; }
        .period {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 4px;
            padding: 7px 10px;
            margin-bottom: 12px;
            font-size: 9px;
        }
        .period span { display: inline-block; margin-right: 18px; }
        .period b { color: #14532d; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        table.data th {
            background: #166534;
            color: #ffffff;
            text-align: left;
            font-size: 8.5px;
            padding: 5px 6px;
            border: 1px solid #14532d;
            text-transform: uppercase;
            letter-spacing: .3px;
        }
        table.data td {
            padding: 4px 6px;
            border: 1px solid #d1d5db;
            vertical-align: top;
        }
        table.data tbody tr:nth-child(even) td { background: #f9fafb; }
        table.data tfoot td {
            background: #ecfdf5;
            font-weight: bold;
            border: 1px solid #d1d5db;
        }
        .right { text-align: right; white-space: nowrap; }
        .center { text-align: center; }
        .total-row td { background: #ecfdf5 !important; font-weight: bold; }
        .summary-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .summary-table td {
            border: 1px solid #d1d5db;
            padding: 7px 9px;
            width: 25%;
        }
        .summary-table span { display: block; font-size: 8px; color: #6b7280; text-transform: uppercase; letter-spacing: .4px; }
        .summary-table strong { display: block; font-size: 11.5px; color: #14532d; margin-top: 2px; }
        .empty { padding: 16px; text-align: center; color: #9ca3af; border: 1px dashed #d1d5db; }
        .foot {
            margin-top: 16px;
            border-top: 1px solid #e5e7eb;
            padding-top: 7px;
            font-size: 8px;
            color: #9ca3af;
            width: 100%;
        }
        .foot td { padding: 0; }
        .negative { color: #b91c1c; }
        .positive { color: #15803d; }
        .badge {
            display: inline-block;
            padding: 1px 5px;
            border-radius: 3px;
            background: #f3f4f6;
            font-size: 8px;
            color: #374151;
        }
    </style>
</head>
<body>
    @php
        $fmt = \App\Support\Format::class;
        $summary = $data['summary'];
        $tabLabels = [
            'laba-rugi' => 'Laporan Laba Rugi',
            'arus-kas' => 'Laporan Arus Kas',
            'piutang' => 'Laporan Piutang Customer',
            'utang' => 'Laporan Utang Supplier',
            'laba-kambing' => 'Laporan Laba per Kambing',
            'penjualan' => 'Laporan Penjualan',
            'pembelian' => 'Laporan Pembelian',
            'stok' => 'Laporan Valuasi Stok',
        ];
    @endphp

    <div class="head">
        <table class="head-table">
            <tr>
                <td>
                    <div class="brand">KAMSYA<small>Manajemen &amp; Jual-Beli Kambing</small></div>
                    <div class="muted" style="margin-top: 4px;">{{ $settings->farm_name }} · {{ $settings->owner_name }}</div>
                </td>
                <td class="meta">
                    <strong>{{ $tabLabels[$tab] ?? 'Laporan' }}</strong><br>
                    {{ $settings->phone ?: '-' }}<br>
                    {{ $settings->address ?: '-' }}<br>
                    Dicetak {{ $fmt::dateTime(now()) }}
                </td>
            </tr>
        </table>
    </div>

    <div class="period">
        <span>Periode: <b>{{ $fmt::date($from) }} – {{ $fmt::date($to) }}</b></span>
        <span>Pembelian: <b>{{ $fmt::rupiah($summary['purchase_total']) }}</b></span>
        <span>Penjualan: <b>{{ $fmt::rupiah($summary['sales_total']) }}</b></span>
        <span>Nilai stok: <b>{{ $fmt::rupiah($summary['inventory_value']) }}</b></span>
    </div>

    @switch($tab)
        @case('laba-rugi')
            <table class="summary-table">
                <tr>
                    <td><span>Penjualan</span><strong>{{ $fmt::rupiah($summary['sales_total']) }}</strong></td>
                    <td><span>HPP</span><strong>{{ $fmt::rupiah($summary['cogs']) }}</strong></td>
                    <td><span>Laba kotor</span><strong>{{ $fmt::rupiah($summary['gross_profit']) }}</strong></td>
                    <td><span>Laba bersih</span><strong>{{ $fmt::rupiah($summary['net_profit']) }}</strong></td>
                </tr>
            </table>

            <h2>Ringkasan Laba Rugi</h2>
            <table class="data">
                <thead><tr><th>Komponen</th><th class="right">Nilai</th></tr></thead>
                <tbody>
                    <tr><td>Penjualan</td><td class="right">{{ $fmt::rupiah($summary['sales_total']) }}</td></tr>
                    <tr><td>Harga pokok penjualan (HPP)</td><td class="right">({{ $fmt::rupiah($summary['cogs']) }})</td></tr>
                    <tr class="total-row"><td>Laba kotor</td><td class="right positive">{{ $fmt::rupiah($summary['gross_profit']) }}</td></tr>
                    <tr><td>Beban operasional</td><td class="right">({{ $fmt::rupiah($summary['expense_total']) }})</td></tr>
                    <tr class="total-row"><td>Laba bersih</td><td class="right {{ $summary['net_profit'] >= 0 ? 'positive' : 'negative' }}">{{ $fmt::rupiah($summary['net_profit']) }}</td></tr>
                </tbody>
            </table>

            <h2>Beban per Kategori</h2>
            <table class="data">
                <thead><tr><th>Kategori</th><th class="right">Jumlah</th><th class="right">Nilai</th></tr></thead>
                <tbody>
                    @forelse ($data['expenses_by_category'] as $row)
                        <tr><td>{{ $row['label'] }}</td><td class="right">{{ $row['count'] }}</td><td class="right">{{ $fmt::rupiah($row['total']) }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="center muted">Belum ada beban pada periode ini.</td></tr>
                    @endforelse
                </tbody>
                @if (! empty($data['expenses_by_category']))
                    <tfoot><tr><td colspan="2">Total beban</td><td class="right">{{ $fmt::rupiah($summary['expense_total']) }}</td></tr></tfoot>
                @endif
            </table>
            @break

        @case('arus-kas')
            <table class="summary-table">
                <tr>
                    <td><span>Kas masuk</span><strong>{{ $fmt::rupiah($summary['cash_in']) }}</strong></td>
                    <td><span>Bayar pembelian</span><strong>{{ $fmt::rupiah($summary['cash_out_purchases']) }}</strong></td>
                    <td><span>Beban operasional</span><strong>{{ $fmt::rupiah($summary['cash_out_expenses']) }}</strong></td>
                    <td><span>Arus kas bersih</span><strong>{{ $fmt::rupiah($summary['net_cash']) }}</strong></td>
                </tr>
            </table>

            <h2>Ringkasan Arus Kas</h2>
            <table class="data">
                <thead><tr><th>Keterangan</th><th class="right">Nilai</th></tr></thead>
                <tbody>
                    <tr><td>Penerimaan dari penjualan</td><td class="right">{{ $fmt::rupiah($summary['cash_in']) }}</td></tr>
                    <tr><td>Pembayaran pembelian</td><td class="right">({{ $fmt::rupiah($summary['cash_out_purchases']) }})</td></tr>
                    <tr><td>Beban operasional</td><td class="right">({{ $fmt::rupiah($summary['cash_out_expenses']) }})</td></tr>
                    <tr class="total-row"><td>Arus kas bersih</td><td class="right {{ $summary['net_cash'] >= 0 ? 'positive' : 'negative' }}">{{ $fmt::rupiah($summary['net_cash']) }}</td></tr>
                </tbody>
            </table>

            <h2>Rincian Kas Masuk</h2>
            <table class="data">
                <thead><tr><th>Kode</th><th>Tanggal</th><th>Transaksi</th><th class="right">Nominal</th></tr></thead>
                <tbody>
                    @forelse ($data['payments_in'] as $payment)
                        <tr>
                            <td>{{ $payment->code }}</td>
                            <td>{{ $fmt::date($payment->date, true) }}</td>
                            <td>{{ $payment->payable?->code ?? '-' }}</td>
                            <td class="right">{{ $fmt::rupiah($payment->amount) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="center muted">Tidak ada penerimaan penjualan.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <h2>Rincian Kas Keluar</h2>
            <table class="data">
                <thead><tr><th>Kode</th><th>Tanggal</th><th>Transaksi</th><th class="right">Nominal</th></tr></thead>
                <tbody>
                    @foreach ($data['payments_out'] as $payment)
                        <tr>
                            <td>{{ $payment->code }}</td>
                            <td>{{ $fmt::date($payment->date, true) }}</td>
                            <td>{{ $payment->payable?->code ?? '-' }}</td>
                            <td class="right">{{ $fmt::rupiah($payment->amount) }}</td>
                        </tr>
                    @endforeach
                    @foreach ($data['expenses'] as $expense)
                        <tr>
                            <td>{{ $expense->code }}</td>
                            <td>{{ $fmt::date($expense->date, true) }}</td>
                            <td>{{ $expense->category?->label() ?? 'Beban' }}</td>
                            <td class="right">{{ $fmt::rupiah($expense->amount) }}</td>
                        </tr>
                    @endforeach
                    @if ($data['payments_out']->isEmpty() && $data['expenses']->isEmpty())
                        <tr><td colspan="4" class="center muted">Tidak ada pengeluaran pada periode ini.</td></tr>
                    @endif
                </tbody>
            </table>
            @break

        @case('piutang')
        @case('utang')
            @php($aging = $data['aging'])
            <table class="summary-table">
                <tr>
                    <td><span>0–30 hari</span><strong>{{ $fmt::rupiah($aging['buckets']['0-30']) }}</strong></td>
                    <td><span>31–60 hari</span><strong>{{ $fmt::rupiah($aging['buckets']['31-60']) }}</strong></td>
                    <td><span>&gt; 60 hari</span><strong>{{ $fmt::rupiah($aging['buckets']['>60']) }}</strong></td>
                    <td><span>Total</span><strong>{{ $fmt::rupiah($aging['total']) }}</strong></td>
                </tr>
            </table>

            <h2>{{ $tab === 'piutang' ? 'Daftar Piutang Customer' : 'Daftar Utang Supplier' }}</h2>
            <table class="data">
                <thead>
                    <tr>
                        <th>Kode</th><th>Tanggal</th><th>{{ $tab === 'piutang' ? 'Customer' : 'Supplier' }}</th><th>Kambing</th>
                        <th class="right">Nilai</th><th class="right">Dibayar</th><th class="right">Sisa</th><th class="right">Umur</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($aging['rows'] as $row)
                        <tr>
                            <td>{{ $row->code }}</td>
                            <td>{{ $fmt::date($row->date, true) }}</td>
                            <td>{{ $tab === 'piutang' ? ($row->customer?->name ?? '-') : ($row->supplier?->name ?? '-') }}</td>
                            <td>{{ $row->goat?->code ?? '-' }}</td>
                            <td class="right">{{ $fmt::rupiah($tab === 'piutang' ? $row->price : $row->total) }}</td>
                            <td class="right">{{ $fmt::rupiah($row->paid_total) }}</td>
                            <td class="right negative">{{ $fmt::rupiah($row->remaining_amount) }}</td>
                            <td class="right">{{ $row->age_days }} hari</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="center muted">Semua tagihan pada periode ini sudah lunas.</td></tr>
                    @endforelse
                </tbody>
                @if ($aging['rows']->isNotEmpty())
                    <tfoot><tr><td colspan="6">Total {{ $tab === 'piutang' ? 'piutang' : 'utang' }}</td><td class="right">{{ $fmt::rupiah($aging['total']) }}</td><td></td></tr></tfoot>
                @endif
            </table>
            @break

        @case('laba-kambing')
            <table class="summary-table">
                <tr>
                    <td><span>Penjualan</span><strong>{{ $fmt::rupiah($summary['sales_total']) }}</strong></td>
                    <td><span>HPP</span><strong>{{ $fmt::rupiah($summary['cogs']) }}</strong></td>
                    <td><span>Laba kotor</span><strong>{{ $fmt::rupiah($summary['gross_profit']) }}</strong></td>
                    <td><span>Margin</span><strong>{{ number_format($summary['margin'], 1, ',', '.') }}%</strong></td>
                </tr>
            </table>

            <h2>Laba per Kambing</h2>
            <table class="data">
                <thead><tr><th>Kode Penjualan</th><th>Tanggal</th><th>Kambing</th><th>Customer</th><th class="right">Harga Jual</th><th class="right">HPP</th><th class="right">Laba</th><th class="right">Margin</th></tr></thead>
                <tbody>
                    @forelse ($data['profits'] as $row)
                        <tr>
                            <td>{{ $row['sale']->code }}</td>
                            <td>{{ $fmt::date($row['sale']->date, true) }}</td>
                            <td>{{ $row['goat']?->code }} · {{ $row['goat']?->name }}</td>
                            <td>{{ $row['sale']->customer?->name ?? '-' }}</td>
                            <td class="right">{{ $fmt::rupiah($row['price']) }}</td>
                            <td class="right">{{ $fmt::rupiah($row['cost_price']) }}</td>
                            <td class="right {{ $row['profit'] >= 0 ? 'positive' : 'negative' }}">{{ $fmt::rupiah($row['profit']) }}</td>
                            <td class="right">{{ $row['price'] > 0 ? number_format(($row['profit'] / $row['price']) * 100, 1, ',', '.') : '0,0' }}%</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="center muted">Belum ada penjualan pada periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @break

        @case('penjualan')
            <table class="summary-table">
                <tr>
                    <td><span>Transaksi</span><strong>{{ $fmt::number($summary['sales_count']) }}</strong></td>
                    <td><span>Total penjualan</span><strong>{{ $fmt::rupiah($summary['sales_total']) }}</strong></td>
                    <td><span>Laba kotor</span><strong>{{ $fmt::rupiah($summary['gross_profit']) }}</strong></td>
                    <td><span>Margin</span><strong>{{ number_format($summary['margin'], 1, ',', '.') }}%</strong></td>
                </tr>
            </table>

            <h2>Penjualan per Customer</h2>
            <table class="data">
                <thead><tr><th>Customer</th><th class="right">Transaksi</th><th class="right">Total</th><th class="right">Laba</th></tr></thead>
                <tbody>
                    @forelse ($data['by_customer'] as $row)
                        <tr><td>{{ $row['name'] }}</td><td class="right">{{ $row['count'] }}</td><td class="right">{{ $fmt::rupiah($row['total']) }}</td><td class="right positive">{{ $fmt::rupiah($row['profit']) }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="center muted">Belum ada penjualan.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <h2>Rekap Penjualan</h2>
            <table class="data">
                <thead><tr><th>Kode</th><th>Tanggal</th><th>Kambing</th><th>Customer</th><th>Jenis</th><th class="right">HPP</th><th class="right">Harga Jual</th><th class="right">Laba</th></tr></thead>
                <tbody>
                    @forelse ($data['sales'] as $sale)
                        <tr>
                            <td>{{ $sale->code }}</td>
                            <td>{{ $fmt::date($sale->date, true) }}</td>
                            <td>{{ $sale->goat?->code }}</td>
                            <td>{{ $sale->customer?->name ?? '-' }}</td>
                            <td>{{ $sale->goat?->type ?? '-' }}</td>
                            <td class="right">{{ $fmt::rupiah($sale->cost_price) }}</td>
                            <td class="right">{{ $fmt::rupiah($sale->price) }}</td>
                            <td class="right positive">{{ $fmt::rupiah($sale->profit()) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="center muted">Tidak ada transaksi penjualan pada periode ini.</td></tr>
                    @endforelse
                </tbody>
                @if ($data['sales']->isNotEmpty())
                    <tfoot><tr><td colspan="6">Total</td><td class="right">{{ $fmt::rupiah($summary['sales_total']) }}</td><td class="right">{{ $fmt::rupiah($summary['gross_profit']) }}</td></tr></tfoot>
                @endif
            </table>
            @break

        @case('pembelian')
            <table class="summary-table">
                <tr>
                    <td><span>Transaksi</span><strong>{{ $fmt::number($summary['purchase_count']) }}</strong></td>
                    <td><span>Total pembelian</span><strong>{{ $fmt::rupiah($summary['purchase_total']) }}</strong></td>
                    <td><span>Kas keluar pembelian</span><strong>{{ $fmt::rupiah($summary['cash_out_purchases']) }}</strong></td>
                    <td><span>Utang supplier</span><strong>{{ $fmt::rupiah($summary['payables']) }}</strong></td>
                </tr>
            </table>

            <h2>Pembelian per Supplier</h2>
            <table class="data">
                <thead><tr><th>Supplier</th><th class="right">Transaksi</th><th class="right">Total</th><th class="right">Biaya Tambahan</th></tr></thead>
                <tbody>
                    @forelse ($data['by_supplier'] as $row)
                        <tr><td>{{ $row['name'] }}</td><td class="right">{{ $row['count'] }}</td><td class="right">{{ $fmt::rupiah($row['total']) }}</td><td class="right">{{ $fmt::rupiah($row['additional']) }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="center muted">Belum ada pembelian.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <h2>Rekap Pembelian</h2>
            <table class="data">
                <thead><tr><th>Kode</th><th>Tanggal</th><th>Kambing</th><th>Supplier</th><th class="right">Harga Beli</th><th class="right">Biaya Tambahan</th><th class="right">Total</th></tr></thead>
                <tbody>
                    @forelse ($data['purchases'] as $purchase)
                        <tr>
                            <td>{{ $purchase->code }}</td>
                            <td>{{ $fmt::date($purchase->date, true) }}</td>
                            <td>{{ $purchase->goat?->code }}</td>
                            <td>{{ $purchase->supplier?->name ?? '-' }}</td>
                            <td class="right">{{ $fmt::rupiah($purchase->price) }}</td>
                            <td class="right">{{ $fmt::rupiah($purchase->additional_cost) }}</td>
                            <td class="right">{{ $fmt::rupiah($purchase->total) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="center muted">Tidak ada transaksi pembelian pada periode ini.</td></tr>
                    @endforelse
                </tbody>
                @if ($data['purchases']->isNotEmpty())
                    <tfoot><tr><td colspan="6">Total pembelian</td><td class="right">{{ $fmt::rupiah($summary['purchase_total']) }}</td></tr></tfoot>
                @endif
            </table>
            @break

        @case('stok')
            <table class="summary-table">
                <tr>
                    <td><span>Kambing tersedia</span><strong>{{ $fmt::number($data['inventory']->count()) }} ekor</strong></td>
                    <td><span>Nilai stok</span><strong>{{ $fmt::rupiah($summary['inventory_value']) }}</strong></td>
                    <td><span>Rata-rata HPP</span><strong>{{ $fmt::rupiah($data['inventory']->count() ? $summary['inventory_value'] / $data['inventory']->count() : 0) }}</strong></td>
                    <td><span>Per tanggal</span><strong>{{ $fmt::date($to) }}</strong></td>
                </tr>
            </table>

            <h2>Valuasi Stok Tersedia</h2>
            <table class="data">
                <thead><tr><th>Kode</th><th>Barcode</th><th>Nama</th><th>Jenis</th><th>Gender</th><th>Asal</th><th class="right">Bobot</th><th class="right">Biaya Perolehan</th></tr></thead>
                <tbody>
                    @forelse ($data['inventory'] as $goat)
                        <tr>
                            <td>{{ $goat->code }}</td>
                            <td>{{ $goat->barcode }}</td>
                            <td>{{ $goat->name }}</td>
                            <td>{{ $goat->type }}</td>
                            <td>{{ $goat->gender->label() }}</td>
                            <td>{{ $goat->origin->label() }}</td>
                            <td class="right">{{ $fmt::weight($goat->weight) }}</td>
                            <td class="right">{{ $fmt::rupiah($goat->acquisitionCost()) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="center muted">Tidak ada kambing tersedia saat ini.</td></tr>
                    @endforelse
                </tbody>
                @if ($data['inventory']->isNotEmpty())
                    <tfoot><tr><td colspan="7">Total nilai stok</td><td class="right">{{ $fmt::rupiah($summary['inventory_value']) }}</td></tr></tfoot>
                @endif
            </table>
            @break
    @endswitch

    <table class="foot">
        <tr>
            <td>Kamsya · {{ $settings->farm_name }} · Laporan digenerate otomatis oleh sistem.</td>
            <td style="text-align: right;">{{ $fmt::dateTime(now()) }}</td>
        </tr>
    </table>
</body>
</html>
