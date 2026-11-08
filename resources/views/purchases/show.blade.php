@extends('layouts.app')

@section('title', 'Detail Pembelian')
@section('breadcrumb', 'Detail Pembelian')

@section('content')
    @php
        $paid = $purchase->paidAmount();
        $remaining = $purchase->remainingAmount();
    @endphp

    <div class="back-row">
        <a class="back-link" href="{{ route('purchases.index') }}"><x-icon name="arrow-left" :size="16" /> Pembelian</a>
        <span class="back-sep">/</span>
        <span>{{ $purchase->code }}</span>
    </div>

    <div class="page-head">
        <div class="page-head-text">
            <span class="eyebrow">Detail Pembelian</span>
            <h1>{{ $purchase->code }}</h1>
            <p>{{ \App\Support\Format::date($purchase->date) }} · {{ $purchase->supplier?->name ?? '-' }}</p>
        </div>
        <div class="page-actions">
            @if ($purchase->isVoided())
                <span class="badge badge-danger">Transaksi di-void</span>
            @elseif ($remaining > 0)
                <span class="badge badge-warning">Sisa utang {{ \App\Support\Format::rupiah($remaining) }}</span>
            @else
                <span class="badge badge-success"><x-icon name="check-circle" :size="13" /> Lunas</span>
            @endif
            @can('void', $purchase)
                @unless ($purchase->isVoided())
                    <button type="button" class="btn btn-danger-soft" data-modal-open="void-purchase">
                        <x-icon name="trash" :size="16" /> Void Pembelian
                    </button>
                @endunless
            @endcan
        </div>
    </div>

    @include('partials.errors')

    @if ($purchase->isVoided())
        <div class="inline-alert inline-alert-warning" style="margin-bottom: 16px;">
            <x-icon name="alert-triangle" :size="16" />
            <span>Transaksi ini telah di-void pada {{ \App\Support\Format::dateTime($purchase->voided_at) }}. Alasan: {{ $purchase->void_reason ?: '-' }}. Data tetap tersimpan untuk audit.</span>
        </div>
    @endif

    <div class="detail-layout">
        <aside class="detail-side">
            <section class="card">
                <div class="card-header">
                    <div>
                        <h2>Ringkasan Nilai</h2>
                        <p>Rincian biaya pembelian</p>
                    </div>
                    <x-icon name="cart" :size="19" />
                </div>
                <div class="card-body">
                    <div class="info-grid" style="grid-template-columns: minmax(0, 1fr);">
                        <div class="info-item"><span>Harga beli</span><strong>{{ \App\Support\Format::rupiah($purchase->price) }}</strong></div>
                        <div class="info-item"><span>Biaya tambahan</span><strong>{{ \App\Support\Format::rupiah($purchase->additional_cost) }}</strong></div>
                        <div class="info-item info-highlight"><span>Total pembelian</span><strong>{{ \App\Support\Format::rupiah($purchase->total) }}</strong></div>
                        <div class="info-item"><span>Sudah dibayar</span><strong class="text-success">{{ \App\Support\Format::rupiah($paid) }}</strong></div>
                        <div class="info-item"><span>Sisa tagihan</span><strong class="{{ $remaining > 0 ? 'text-danger' : 'text-success' }}">{{ \App\Support\Format::rupiah($remaining) }}</strong></div>
                    </div>
                </div>
            </section>

            @if ($purchase->goat)
                <section class="card">
                    <div class="card-header">
                        <div>
                            <h2>Kambing</h2>
                            <p>Hasil transaksi ini</p>
                        </div>
                        <x-icon name="goat" :size="19" />
                    </div>
                    <div class="card-body">
                        <div class="cell-goat" style="margin-bottom: 12px;">
                            <span class="goat-avatar avatar-md tone-{{ \App\Support\Format::typeTone($purchase->goat->type) }}">🐐</span>
                            <div class="cell-stack">
                                <a class="cell-title" href="{{ route('goats.show', $purchase->goat) }}">{{ $purchase->goat->name }}</a>
                                <span class="cell-muted">{{ $purchase->goat->code }} · {{ $purchase->goat->type }}</span>
                            </div>
                        </div>
                        <div class="info-grid" style="grid-template-columns: minmax(0, 1fr);">
                            <div class="info-item"><span>Barcode</span><strong class="barcode-text">{{ $purchase->goat->barcode }}</strong></div>
                            <div class="info-item"><span>Status</span><strong>{{ $purchase->goat->status->label() }}</strong></div>
                            <div class="info-item"><span>Bobot</span><strong>{{ \App\Support\Format::weight($purchase->goat->weight) }}</strong></div>
                        </div>
                        <a class="btn btn-outline btn-block" style="margin-top: 12px;" href="{{ route('goats.labels', ['goats' => [$purchase->goat->id]]) }}">
                            <x-icon name="printer" :size="16" /> Cetak Barcode
                        </a>
                    </div>
                </section>
            @endif

            <section class="card">
                <div class="card-header">
                    <div>
                        <h2>Supplier</h2>
                        <p>Pihak penjual</p>
                    </div>
                    <x-icon name="truck" :size="19" />
                </div>
                <div class="card-body">
                    <div class="supplier-card">
                        <span class="person-avatar avatar-md tone-green">{{ $purchase->supplier?->initials() ?? '?' }}</span>
                        <div class="supplier-info">
                            <strong>{{ $purchase->supplier?->name ?? '-' }}</strong>
                            <span><x-icon name="phone" :size="13" /> {{ $purchase->supplier?->phone ?? '-' }}</span>
                            <span><x-icon name="map-pin" :size="13" /> {{ $purchase->supplier?->address ?? '-' }}</span>
                        </div>
                    </div>
                </div>
            </section>
        </aside>

        <div class="detail-main detail-stack">
            @if ($purchase->notes)
                <section class="card">
                    <div class="card-header">
                        <div>
                            <h2>Catatan</h2>
                            <p>Informasi tambahan transaksi</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="notes-text">{{ $purchase->notes }}</p>
                    </div>
                </section>
            @endif

            <section class="card">
                <div class="card-header">
                    <div>
                        <h2>Riwayat Pembayaran</h2>
                        <p>Pembayaran ke supplier untuk transaksi ini</p>
                    </div>
                    <span class="badge badge-neutral">{{ $purchase->payments->whereNull('voided_at')->count() }} pembayaran</span>
                </div>
                @if ($purchase->payments->isNotEmpty())
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
                                @foreach ($purchase->payments->sortBy('date') as $payment)
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
                                                          data-confirm-detail="Sisa tagihan akan dihitung ulang. Data pembayaran tetap tersimpan."
                                                          data-confirm-label="Void Pembayaran">
                                                        @csrf
                                                        <input type="hidden" name="reason" value="Void dari detail pembelian">
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
                            <p>Pembayaran ke supplier akan tampil di sini.</p>
                        </div>
                    </div>
                @endif
            </section>

            @if (! $purchase->isVoided() && $remaining > 0)
                @can('create', App\Models\Payment::class)
                    <section class="card">
                        <div class="card-header">
                            <div>
                                <h2>Catat Pembayaran</h2>
                                <p>Sisa tagihan {{ \App\Support\Format::rupiah($remaining) }}</p>
                            </div>
                            <x-icon name="wallet" :size="19" />
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('payments.store') }}" class="form">
                                @csrf
                                <input type="hidden" name="payable_type" value="purchase">
                                <input type="hidden" name="payable_id" value="{{ $purchase->id }}">
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

    @can('void', $purchase)
        @unless ($purchase->isVoided())
            <div class="modal-backdrop" id="void-purchase" hidden>
                <div class="modal-dialog modal-sm" role="dialog" aria-modal="true" aria-labelledby="void-purchase-title">
                    <div class="modal-header">
                        <div class="modal-heading">
                            <h2 id="void-purchase-title">Void Pembelian</h2>
                            <p>{{ $purchase->code }}</p>
                        </div>
                        <button type="button" class="modal-close" data-modal-close aria-label="Tutup dialog"><x-icon name="x" :size="19" /></button>
                    </div>
                    <form method="POST" action="{{ route('purchases.void', $purchase) }}">
                        @csrf
                        <div class="modal-body">
                            <div class="inline-alert inline-alert-warning">
                                <x-icon name="alert-triangle" :size="16" />
                                <span>Pembelian tidak dihapus permanen. Kambing akan diarsipkan dan pembayaran terkait ikut di-void.</span>
                            </div>
                            <div class="form-group" style="margin-top: 14px;">
                                <label for="void-reason">Alasan void <span class="req">*</span></label>
                                <input id="void-reason" type="text" name="reason" maxlength="255" required placeholder="Misalnya salah input supplier">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline" data-modal-close>Batal</button>
                            <button type="submit" class="btn btn-danger">Void Pembelian</button>
                        </div>
                    </form>
                </div>
            </div>
        @endunless
    @endcan
@endsection
