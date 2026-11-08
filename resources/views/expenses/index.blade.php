@extends('layouts.app')

@section('title', 'Beban Operasional')
@section('breadcrumb', 'Beban Operasional')

@section('content')
    <div class="page-head">
        <div class="page-head-text">
            <span class="eyebrow">Transaksi</span>
            <h1>Beban Operasional</h1>
            <p>Catat biaya pakan, obat, tenaga kerja, dan pengeluaran lain di luar pembelian kambing.</p>
        </div>
        <div class="page-actions">
            @can('create', App\Models\Expense::class)
                <button type="button" class="btn btn-primary" data-modal-open="expense-create">
                    <x-icon name="plus" :size="16" /> Catat Beban
                </button>
            @endcan
        </div>
    </div>

    @include('partials.errors')

    <section class="metric-grid metric-grid-3">
        <div class="metric-card">
            <span class="metric-icon metric-amber"><x-icon name="wallet" :size="19" /></span>
            <div><span>Total beban (aktif)</span><strong>{{ \App\Support\Format::rupiah($total) }}</strong></div>
        </div>
        <div class="metric-card">
            <span class="metric-icon metric-blue"><x-icon name="file-text" :size="19" /></span>
            <div><span>Jumlah catatan</span><strong>{{ \App\Support\Format::number($expenses->total()) }} catatan</strong></div>
        </div>
        <div class="metric-card">
            <span class="metric-icon metric-green"><x-icon name="calendar" :size="19" /></span>
            <div><span>Beban bulan ini</span><strong>{{ \App\Support\Format::rupiah(App\Models\Expense::query()->notVoided()->whereBetween('date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])->sum('amount')) }}</strong></div>
        </div>
    </section>

    <section class="card">
        <form class="card-toolbar" method="GET" action="{{ route('expenses.index') }}">
            <div class="search-field">
                <x-icon name="search" :size="17" />
                <input type="search" name="q" value="{{ $search }}" placeholder="Cari kode atau catatan beban…" aria-label="Cari beban">
            </div>
            <div class="filter-group">
                <select class="filter-select" name="category" aria-label="Filter kategori" data-auto-submit>
                    <option value="">Semua kategori</option>
                    @foreach ($categories as $item)
                        <option value="{{ $item->value }}" @selected($category === $item->value)>{{ $item->label() }}</option>
                    @endforeach
                </select>
                <select class="filter-select" name="status" aria-label="Filter status" data-auto-submit>
                    <option value="">Semua status</option>
                    <option value="active" @selected($filter === 'active')>Aktif</option>
                    <option value="voided" @selected($filter === 'voided')>Di-void</option>
                </select>
                <button type="submit" class="btn btn-outline btn-sm"><x-icon name="filter" :size="15" /> Terapkan</button>
                @if ($search !== '' || $category !== '' || $filter !== '')
                    <a class="btn btn-ghost btn-sm" href="{{ route('expenses.index') }}"><x-icon name="refresh" :size="15" /> Reset</a>
                @endif
            </div>
        </form>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Tanggal</th>
                        <th>Kategori</th>
                        <th>Catatan</th>
                        <th class="align-end">Nominal</th>
                        <th class="th-actions">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($expenses as $expense)
                        <tr class="{{ $expense->voided_at ? 'muted' : '' }}">
                            <td>
                                <span class="barcode-text">{{ $expense->code }}</span>
                                @if ($expense->voided_at)
                                    <span class="badge badge-danger" style="margin-left: 6px;">Void</span>
                                @endif
                            </td>
                            <td class="nowrap">{{ \App\Support\Format::date($expense->date, true) }}</td>
                            <td><span class="badge badge-neutral">{{ $expense->category?->label() ?? '-' }}</span></td>
                            <td>{{ \Illuminate\Support\Str::limit($expense->notes, 60) ?: '-' }}</td>
                            <td class="align-end nowrap"><strong>{{ \App\Support\Format::rupiah($expense->amount) }}</strong></td>
                            <td class="cell-actions">
                                @if (! $expense->voided_at)
                                    @can('void', $expense)
                                        <form method="POST" action="{{ route('expenses.void', $expense) }}" class="inline-form"
                                              data-confirm="Void beban {{ $expense->code }}?"
                                              data-confirm-detail="Data beban tetap tersimpan, tetapi tidak lagi dihitung pada laporan."
                                              data-confirm-label="Void Beban">
                                            @csrf
                                            <input type="hidden" name="reason" value="Void dari daftar beban">
                                            <button type="submit" class="action-btn action-danger" title="Void beban" aria-label="Void {{ $expense->code }}">
                                                <x-icon name="trash" :size="16" />
                                            </button>
                                        </form>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-row">
                                <div class="empty-state">
                                    <span class="empty-icon"><x-icon name="wallet" :size="30" /></span>
                                    <h3>Belum ada beban operasional</h3>
                                    <p>Catat pengeluaran operasional agar laba bersih lebih akurat.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.pagination', ['paginator' => $expenses])
    </section>

    @can('create', App\Models\Expense::class)
        <div class="modal-backdrop" id="expense-create" hidden>
            <div class="modal-dialog modal-md" role="dialog" aria-modal="true" aria-labelledby="expense-create-title">
                <div class="modal-header">
                    <div class="modal-heading">
                        <h2 id="expense-create-title">Catat Beban Operasional</h2>
                        <p>Kode beban dibuat otomatis.</p>
                    </div>
                    <button type="button" class="modal-close" data-modal-close aria-label="Tutup dialog"><x-icon name="x" :size="19" /></button>
                </div>
                <form method="POST" action="{{ route('expenses.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="expense-date">Tanggal <span class="req">*</span></label>
                                <input id="expense-date" type="date" name="date" value="{{ old('date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
                            </div>
                            <div class="form-group">
                                <label for="expense-category">Kategori <span class="req">*</span></label>
                                <select id="expense-category" name="category" required>
                                    @foreach ($categories as $item)
                                        <option value="{{ $item->value }}" @selected(old('category') === $item->value)>{{ $item->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group full">
                                <label for="expense-amount">Nominal <span class="req">*</span></label>
                                <div class="input-suffix">
                                    <input id="expense-amount" type="number" name="amount" value="{{ old('amount') }}" min="1" step="1000" inputmode="numeric" placeholder="250000" required>
                                    <span>Rp</span>
                                </div>
                            </div>
                            <div class="form-group full">
                                <label for="expense-notes">Catatan</label>
                                <textarea id="expense-notes" name="notes" rows="2" maxlength="1000" placeholder="Rincian beban…">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline" data-modal-close>Batal</button>
                        <button type="submit" class="btn btn-primary"><x-icon name="check" :size="16" /> Simpan Beban</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan
@endsection
