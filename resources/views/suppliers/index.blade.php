@extends('layouts.app')

@section('title', 'Supplier')
@section('breadcrumb', 'Supplier')

@section('content')
    <div class="page-head">
        <div class="page-head-text">
            <span class="eyebrow">Data Pendukung</span>
            <h1>Supplier</h1>
            <p>Kelola data supplier dan penjual kambing untuk transaksi pembelian.</p>
        </div>
        <div class="page-actions">
            @can('create', App\Models\Supplier::class)
                <button type="button" class="btn btn-primary" data-modal-open="supplier-create">
                    <x-icon name="plus" :size="16" /> Tambah Supplier
                </button>
            @endcan
        </div>
    </div>

    @include('partials.errors')

    <section class="metric-grid metric-grid-3">
        <div class="metric-card">
            <span class="metric-icon metric-green"><x-icon name="truck" :size="19" /></span>
            <div><span>Total supplier</span><strong>{{ \App\Support\Format::number($stats['total']) }} supplier</strong></div>
        </div>
        <div class="metric-card">
            <span class="metric-icon metric-blue"><x-icon name="check-circle" :size="19" /></span>
            <div><span>Supplier aktif</span><strong>{{ \App\Support\Format::number($stats['used']) }} supplier</strong></div>
        </div>
        <div class="metric-card">
            <span class="metric-icon metric-amber"><x-icon name="wallet" :size="19" /></span>
            <div><span>Total pembelian</span><strong>{{ \App\Support\Format::rupiah($stats['purchase_value']) }}</strong></div>
        </div>
    </section>

    <section class="card">
        <form class="card-toolbar" method="GET" action="{{ route('suppliers.index') }}">
            <div class="search-field">
                <x-icon name="search" :size="17" />
                <input type="search" name="q" value="{{ $search }}" placeholder="     Cari nama, kode, atau telepon supplier…" aria-label="Cari supplier">
            </div>
            <div class="filter-group">
                <button type="submit" class="btn btn-outline btn-sm"><x-icon name="search" :size="15" /> Cari</button>
                @if ($search !== '')
                    <a class="btn btn-ghost btn-sm" href="{{ route('suppliers.index') }}">Reset</a>
                @endif
                <span class="toolbar-hint">{{ $suppliers->total() }} supplier terdaftar</span>
            </div>
        </form>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Supplier</th>
                        <th>Telepon</th>
                        <th>Alamat</th>
                        <th class="align-end">Transaksi</th>
                        <th class="align-end">Total Pembelian</th>
                        <th class="th-actions">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($suppliers as $supplier)
                        <tr>
                            <td>
                                <div class="cell-person">
                                    <span class="person-avatar avatar-sm tone-green">{{ $supplier->initials() }}</span>
                                    <span class="cell-stack">
                                        <strong>{{ $supplier->name }}</strong>
                                        <span class="cell-muted">{{ $supplier->code }}</span>
                                    </span>
                                </div>
                            </td>
                            <td class="nowrap">{{ $supplier->phone ?: '-' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($supplier->address, 48) ?: '-' }}</td>
                            <td class="align-end">{{ \App\Support\Format::number($supplier->purchases_count) }}</td>
                            <td class="align-end nowrap"><strong>{{ \App\Support\Format::rupiah($supplier->purchases_sum_total ?? 0) }}</strong></td>
                            <td class="cell-actions">
                                @if ($supplier->purchases->isNotEmpty())
                                    <button type="button" class="action-btn" data-modal-open="supplier-history-{{ $supplier->id }}" title="Riwayat pembelian" aria-label="Riwayat {{ $supplier->name }}">
                                        <x-icon name="clock" :size="16" />
                                    </button>
                                @endif
                                @can('update', $supplier)
                                    <button type="button" class="action-btn" data-modal-open="supplier-edit-{{ $supplier->id }}" title="Edit supplier" aria-label="Edit {{ $supplier->name }}">
                                        <x-icon name="edit" :size="16" />
                                    </button>
                                @endcan
                                @can('delete', $supplier)
                                    <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}" class="inline-form"
                                          data-confirm="Arsipkan supplier {{ $supplier->name }}?"
                                          data-confirm-detail="Supplier tidak dihapus permanen. Riwayat transaksi tetap tersimpan."
                                          data-confirm-label="Arsipkan">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="action-btn action-danger" title="Arsipkan supplier" aria-label="Arsipkan {{ $supplier->name }}">
                                            <x-icon name="trash" :size="16" />
                                        </button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-row">
                                <div class="empty-state">
                                    <span class="empty-icon"><x-icon name="truck" :size="30" /></span>
                                    <h3>Belum ada supplier</h3>
                                    <p>Tambahkan supplier untuk mencatat pembelian kambing.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.pagination', ['paginator' => $suppliers])
    </section>

    @can('create', App\Models\Supplier::class)
        <div class="modal-backdrop" id="supplier-create" hidden>
            <div class="modal-dialog modal-md" role="dialog" aria-modal="true" aria-labelledby="supplier-create-title">
                <div class="modal-header">
                    <div class="modal-heading">
                        <h2 id="supplier-create-title">Tambah Supplier</h2>
                        <p>Kode supplier dibuat otomatis.</p>
                    </div>
                    <button type="button" class="modal-close" data-modal-close aria-label="Tutup dialog"><x-icon name="x" :size="19" /></button>
                </div>
                <form method="POST" action="{{ route('suppliers.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="form-grid">
                            <div class="form-group full">
                                <label for="supplier-name">Nama supplier <span class="req">*</span></label>
                                <input id="supplier-name" type="text" name="name" value="{{ old('name') }}" maxlength="120" required placeholder="Contoh: Pak Hadi">
                            </div>
                            <div class="form-group">
                                <label for="supplier-phone">Telepon</label>
                                <input id="supplier-phone" type="text" name="phone" value="{{ old('phone') }}" maxlength="30" placeholder="0812-3456-7890">
                            </div>
                            <div class="form-group">
                                <label for="supplier-address">Alamat</label>
                                <input id="supplier-address" type="text" name="address" value="{{ old('address') }}" maxlength="500" placeholder="Alamat supplier">
                            </div>
                            <div class="form-group full">
                                <label for="supplier-notes">Catatan</label>
                                <textarea id="supplier-notes" name="notes" rows="2" maxlength="1000" placeholder="Informasi tambahan…">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline" data-modal-close>Batal</button>
                        <button type="submit" class="btn btn-primary"><x-icon name="check" :size="16" /> Simpan Supplier</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan

    @foreach ($suppliers as $supplier)
        @can('update', $supplier)
            <div class="modal-backdrop" id="supplier-edit-{{ $supplier->id }}" hidden>
                <div class="modal-dialog modal-md" role="dialog" aria-modal="true" aria-labelledby="supplier-edit-title-{{ $supplier->id }}">
                    <div class="modal-header">
                        <div class="modal-heading">
                            <h2 id="supplier-edit-title-{{ $supplier->id }}">Edit Supplier</h2>
                            <p>{{ $supplier->code }}</p>
                        </div>
                        <button type="button" class="modal-close" data-modal-close aria-label="Tutup dialog"><x-icon name="x" :size="19" /></button>
                    </div>
                    <form method="POST" action="{{ route('suppliers.update', $supplier) }}">
                        @csrf
                        @method('PUT')
                        <div class="modal-body">
                            <div class="form-grid">
                                <div class="form-group full">
                                    <label for="supplier-name-{{ $supplier->id }}">Nama supplier <span class="req">*</span></label>
                                    <input id="supplier-name-{{ $supplier->id }}" type="text" name="name" value="{{ $supplier->name }}" maxlength="120" required>
                                </div>
                                <div class="form-group">
                                    <label for="supplier-phone-{{ $supplier->id }}">Telepon</label>
                                    <input id="supplier-phone-{{ $supplier->id }}" type="text" name="phone" value="{{ $supplier->phone }}" maxlength="30">
                                </div>
                                <div class="form-group">
                                    <label for="supplier-address-{{ $supplier->id }}">Alamat</label>
                                    <input id="supplier-address-{{ $supplier->id }}" type="text" name="address" value="{{ $supplier->address }}" maxlength="500">
                                </div>
                                <div class="form-group full">
                                    <label for="supplier-notes-{{ $supplier->id }}">Catatan</label>
                                    <textarea id="supplier-notes-{{ $supplier->id }}" name="notes" rows="2" maxlength="1000">{{ $supplier->notes }}</textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline" data-modal-close>Batal</button>
                            <button type="submit" class="btn btn-primary"><x-icon name="check" :size="16" /> Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        @endcan

        @if ($supplier->purchases->isNotEmpty())
            <div class="modal-backdrop" id="supplier-history-{{ $supplier->id }}" hidden>
                <div class="modal-dialog modal-md" role="dialog" aria-modal="true" aria-labelledby="supplier-history-title-{{ $supplier->id }}">
                    <div class="modal-header">
                        <div class="modal-heading">
                            <h2 id="supplier-history-title-{{ $supplier->id }}">Riwayat Pembelian</h2>
                            <p>{{ $supplier->name }} · {{ $supplier->phone ?: '-' }}</p>
                        </div>
                        <button type="button" class="modal-close" data-modal-close aria-label="Tutup dialog"><x-icon name="x" :size="19" /></button>
                    </div>
                    <div class="modal-body">
                        <div class="history-summary-grid">
                            <div><span>Total transaksi</span><strong>{{ $supplier->purchases_count }}</strong></div>
                            <div><span>Total pembelian</span><strong>{{ \App\Support\Format::rupiah($supplier->purchases_sum_total ?? 0) }}</strong></div>
                            <div><span>Supplier sejak</span><strong>{{ \App\Support\Format::date($supplier->created_at, true) }}</strong></div>
                        </div>
                        <div class="history-list">
                            @foreach ($supplier->purchases as $purchase)
                                <a class="history-card" href="{{ route('purchases.show', $purchase) }}">
                                    <span class="history-date">{{ \App\Support\Format::date($purchase->date, true) }}</span>
                                    <span class="history-main">
                                        <strong>{{ $purchase->goat?->name ?? 'Kambing terarsip' }}</strong>
                                        <small>{{ $purchase->code }} · {{ $purchase->goat?->barcode ?? '-' }}</small>
                                    </span>
                                    <strong class="history-amount">{{ \App\Support\Format::rupiah($purchase->total) }}</strong>
                                    <x-icon name="chevron-right" :size="15" />
                                </a>
                            @endforeach
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline" data-modal-close>Tutup</button>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
@endsection
