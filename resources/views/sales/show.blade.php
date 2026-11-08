@extends('layouts.app')

@section('title', 'Detail Penjualan')
@section('breadcrumb', 'Detail Penjualan')

@section('content')
    @php
        $paid = $sale->paidAmount();
        $remaining = $sale->remainingAmount();
        $profit = $sale->profit();
    @endphp

    <div class="back-row">
        <a class="back-link" href="{{ route('sales.index') }}"><x-icon name="arrow-left" :size="16" /> Penjualan</a>
        <span class="back-sep">/</span>
        <span>{{ $sale->code }}</span>
    </div>

    <div class="page-head">
        <div class="page-head-text">
            <span class="eyebrow">Detail Penjualan</span>
            <h1>{{ $sale->code }}</h1>
            <p>{{ \App\Support\Format::date($sale->date) }} · {{ $sale->customer?->name ?? '-' }}</p>
        </div>
        <div class="page-actions">
            @if ($sale->isVoided())
                <span class="badge badge-danger">Transaksi di-void</span>
            @elseif ($remaining > 0)
                <span class="badge badge-warning">Piutang {{ \App\Support\Format::rupiah($remaining) }}</span>
            @else
                <span class="badge badge-success"><x-icon name="check-circle" :size="13" /> Lunas</span>
            @endif
            @can('void', $sale)
                @unless ($sale->isVoided())
                    <button type="button" class="btn btn-danger-soft" data-modal-open="void-sale">
                        <x-icon name="trash" :size="16" /> Void Penjualan
                    </button>
                @endunless
            @endcan
        </div>
    </div>

    @include('partials.errors')

    @if ($sale->isVoided())
        <div class="inline-alert inline-alert-warning" style="margin-bottom: 16px;">
            <x-icon name="alert-triangle" :size="16" />
            <span>Transaksi ini telah di-void pada {{ \App\Support\Format::dateTime($sale->voided_at) }}. Alasan: {{ $sale->void_reason ?: '-' }}. Data tetap tersimpan untuk audit.</span>
        </div>
    @endif

    <div class="detail-layout">
        <aside class="detail-side">
            <section class="card">
                <div class="card-header">
                    <div>
                        <h2>Ringkasan Nilai</h2>
                        <p>Harga jual, HPP, dan laba</p>
                    </div>
                    <x-icon name="tag" :size="19" />
                </div>
                <div class="card-body">
                    <div class="info-grid" style="grid-template-columns: minmax(0, 1fr);">
                        <div class="info-item"><span>Harga jual</span><strong>{{ \App\Support\Format::rupiah($sale->price) }}</strong></div>
                        <div class="info-item"><span>HPP (snapshot)</span><strong>{{ \App\Support\Format::rupiah($sale->cost_price) }}</strong></div>
                        <div class="info-item info-highlight"><span>Laba kotor</span><strong class="{{ $profit >= 0 ? 'text-success' : 'text-danger' }}">{{ $profit >= 0 ? '+' : '' }}{{ \App\Support\Format::rupiah($profit) }}</strong></div>
                        <div class="info-item"><span>Sudah dibayar</span><strong class="text-success">{{ \App\Support\Format::rupiah($paid) }}</strong></div>
                        <div class="info-item"><span>Sisa piutang</span><strong class="{{ $remaining > 0 ? 'text-danger' : 'text-success' }}">{{ \App\Support\Format::rupiah($remaining) }}</strong></div>
                    </div>
                </div>
            </section>

            @if ($sale->goat)
                <section class="card">
                    <div class="card-header">
                        <div>
                            <h2>Kambing</h2>
                            <p>Kambing yang dijual</p>
                        </div>
                        <x-icon name="goat" :size="19" />
                    </div>
                    <div class="card-body">
                        <div class="cell-goat" style="margin-bottom: 12px;">
                            <span class="goat-avatar avatar-md tone-{{ \App\Support\Format::typeTone($sale->goat->type) }}">🐐</span>
                            <div class="cell-stack">
                                <a class="cell-title" href="{{ route('goats.show', $sale->goat) }}">{{ $sale->goat->name }}</a>
                                <span class="cell-muted">{{ $sale->goat->code }} · {{ $sale->goat->type }}</span>
                            </div>
                        </div>
                        <div class="info-grid" style="grid-template-columns: minmax(0, 1fr);">
                            <div class="info-item"><span>Barcode</span><strong class="barcode-text">{{ $sale->goat->barcode }}</strong></div>
                            <div class="info-item"><span>Status</span><strong>{{ $sale->goat->status->label() }}</strong></div>
                            <div class="info-item"><span>Asal</span><strong>{{ $sale->goat->origin->label() }}</strong></div>
                        </div>
                    </div>
                </section>
            @endif

            <section class="card">
                <div class="card-header">
                    <div>
                        <h2>Customer</h2>
                        <p>Pihak pembeli</p>
                    </div>
                    <x-icon name="users" :size="19" />
                </div>
                <div class="card-body">
                    <div class="supplier-card">
                        <span class="person-avatar avatar-md tone-blue">{{ $sale->customer?->initials() ?? '?' }}</span>
                        <div class="supplier-info">
                            <strong>{{ $sale->customer?->name ?? '-' }}</strong>
                            <span><x-icon name="phone" :size="13" /> {{ $sale->customer?->phone ?? '-' }}</span>
                            <span><x-icon name="map-pin" :size="13" /> {{ $sale->customer?->address ?? '-' }}</span>
                        </div>
                    </div>
                </div>
            </section>
        </aside>

        <div class="detail-main detail-stack">
            @if ($sale->notes)
                <section class="card">
                    <div class="card-header">
                        <div>
                            <h2>Catatan</h2>
                            <p>Informasi tambahan transaksi</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="notes-text">{{ $sale->notes }}</p>
                    </div>
                </section>
            @endif

            <section class="card">
                <div class="card-header">
                    <div>
                        <h2>Riwayat Pembayaran</h2>
                        <p>Pembayaran dari customer untuk transaksi ini</p>
                    </div>
                    <span class="badge badge-neutral">{{ $sale->payments->whereNull('voided_at')->count() }} pembayaran</span>
                </div>
                @if ($sale->payments->isNotEmpty())
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Tanggal</th>
                                    <th>Metode</th>
                                    <th>Catatan</th>
                                    <th class="align-end">Nominal</th>
                                    <th class="th-actions">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($sale->payments->sortBy('date') as $payment)
                                    <tr class="{{ $payment->voided_at ? 'muted' : '' }}">
                                        <td>
                                            <span class="barcode-text">{{ $payment->code }}</span>
                                            @if ($payment->voided_at)
                                                <span class="badge badge-danger" style="margin-left: 6px;">Void</span>
                                            @endif
                                        </td>
                                        <td class="nowrap">{{ \App\Support\Format::date($payment->date, true) }}</td>
                                        <td>{{ $payment->method?->label() ?? '-' }}</td>
                                        <td>{{ $payment->notes ?: '-' }}</td>
                                        <td class="align-end nowrap"><strong>{{ \App\Support\Format::rupiah($payment->amount) }}</strong></td>
                                        <td class="cell-actions">
                                            @if (! $payment->voided_at)
                                                @can('void', $payment)
                                                    <form method="POST" action="{{ route('payments.void', $payment) }}" class="inline-form"
                                                          data-confirm="Void pembayaran {{ $payment->code }}?"
                                                          data-confirm-detail="Sisa piutang akan dihitung ulang. Data pembayaran tetap tersimpan."
                                                          data-confirm-label="Void Pembayaran">
                                                        @csrf
                                                        <input type="hidden" name="reason" value="Void dari detail penjualan">
                                                        <button type="submit" class="action-btn action-danger" title="Void pembayaran" aria-label="Void pembayaran {{ $payment->code }}">
                                                            <x-icon name="trash" :size="16" />
                                                        </button>
                                                    </form>
                                                @endcan
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="card-body">
                        <div class="empty-state">
                            <span class="empty-icon"><x-icon name="wallet" :size="30" /></span>
                            <h3>Belum ada pembayaran</h3>
                            <p>Pembayaran dari customer akan tampil di sini.</p>
                        </div>
                    </div>
                @endif
            </section>

            @if (! $sale->isVoided() && $remaining > 0)
                @can('create', App\Models\Payment::class)
                    <section class="card">
                        <div class="card-header">
                            <div>
                                <h2>Catat Pembayaran</h2>
                                <p>Sisa piutang {{ \App\Support\Format::rupiah($remaining) }}</p>
                            </div>
                            <x-icon name="wallet-in" :size="19" />
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('payments.store') }}" class="form">
                                @csrf
                                <input type="hidden" name="payable_type" value="sale">
                                <input type="hidden" name="payable_id" value="{{ $sale->id }}">
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="pay-date">Tanggal pembayaran <span class="req">*</span></label>
                                        <input id="pay-date" type="date" name="date" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="pay-amount">Nominal <span class="req">*</span></label>
                                        <div class="input-suffix">
                                            <input id="pay-amount" type="number" name="amount" min="1" max="{{ (int) $remaining }}" step="1000" value="{{ (int) $remaining }}" required>
                                            <span>Rp</span>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="pay-method">Metode <span class="req">*</span></label>
                                        <select id="pay-method" name="method" required>
                                            @foreach (App\Enums\PaymentMethod::options() as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="pay-notes">Catatan</label>
                                        <input id="pay-notes" type="text" name="notes" maxlength="500" placeholder="Opsional">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <x-icon name="check" :size="16" /> Simpan Pembayaran
                                </button>
                            </form>
                        </div>
                    </section>
                @endcan
            @endif
        </div>
    </div>

    @can('void', $sale)
        @unless ($sale->isVoided())
            <div class="modal-backdrop" id="void-sale" hidden>
                <div class="modal-dialog modal-sm" role="dialog" aria-modal="true" aria-labelledby="void-sale-title">
                    <div class="modal-header">
                        <div class="modal-heading">
                            <h2 id="void-sale-title">Void Penjualan</h2>
                            <p>{{ $sale->code }}</p>
                        </div>
                        <button type="button" class="modal-close" data-modal-close aria-label="Tutup dialog"><x-icon name="x" :size="19" /></button>
                    </div>
                    <form method="POST" action="{{ route('sales.void', $sale) }}">
                        @csrf
                        <div class="modal-body">
                            <div class="inline-alert inline-alert-warning">
                                <x-icon name="alert-triangle" :size="16" />
                                <span>Penjualan tidak dihapus permanen. Status kambing kembali tersedia dan pembayaran terkait ikut di-void.</span>
                            </div>
                            <div class="form-group" style="margin-top: 14px;">
                                <label for="void-reason">Alasan void <span class="req">*</span></label>
                                <input id="void-reason" type="text" name="reason" maxlength="255" required placeholder="Misalnya customer membatalkan pembelian">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline" data-modal-close>Batal</button>
                            <button type="submit" class="btn btn-danger">Void Penjualan</button>
                        </div>
                    </form>
                </div>
            </div>
        @endunless
    @endcan
@endsection
