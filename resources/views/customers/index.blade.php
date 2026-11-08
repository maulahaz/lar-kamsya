@extends('layouts.app')

@section('title', 'Customer')
@section('breadcrumb', 'Customer')

@section('content')
    <div class="page-head">
        <div class="page-head-text">
            <span class="eyebrow">Data Pendukung</span>
            <h1>Customer</h1>
            <p>Kelola data pembeli kambing dan riwayat transaksi penjualan.</p>
        </div>
        <div class="page-actions">
            @can('create', App\Models\Customer::class)
                <button type="button" class="btn btn-primary" data-modal-open="customer-create">
                    <x-icon name="plus" :size="16" /> Tambah Customer
                </button>
            @endcan
        </div>
    </div>

    @include('partials.errors')

    <section class="metric-grid metric-grid-3">
        <div class="metric-card">
            <span class="metric-icon metric-blue"><x-icon name="users" :size="19" /></span>
            <div><span>Total customer</span><strong>{{ \App\Support\Format::number($stats['total']) }} customer</strong></div>
        </div>
        <div class="metric-card">
            <span class="metric-icon metric-green"><x-icon name="check-circle" :size="19" /></span>
            <div><span>Customer aktif</span><strong>{{ \App\Support\Format::number($stats['used']) }} customer</strong></div>
        </div>
        <div class="metric-card">
            <span class="metric-icon metric-amber"><x-icon name="wallet" :size="19" /></span>
            <div><span>Total penjualan</span><strong>{{ \App\Support\Format::rupiah($stats['sale_value']) }}</strong></div>
        </div>
    </section>

    <section class="card">
        <form class="card-toolbar" method="GET" action="{{ route('customers.index') }}">
            <div class="search-field">
                <x-icon name="search" :size="17" />
                <input type="search" name="q" value="{{ $search }}" placeholder="     Cari nama, kode, atau telepon customer…" aria-label="Cari customer">
            </div>
            <div class="filter-group">
                <button type="submit" class="btn btn-outline btn-sm"><x-icon name="search" :size="15" /> Cari</button>
                @if ($search !== '')
                    <a class="btn btn-ghost btn-sm" href="{{ route('customers.index') }}">Reset</a>
                @endif
                <span class="toolbar-hint">{{ $customers->total() }} customer terdaftar</span>
            </div>
        </form>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Telepon</th>
                        <th>Alamat</th>
                        <th class="align-end">Transaksi</th>
                        <th class="align-end">Total Pembelian</th>
                        <th class="th-actions">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customers as $customer)
                        <tr>
                            <td>
                                <div class="cell-person">
                                    <span class="person-avatar avatar-sm tone-blue">{{ $customer->initials() }}</span>
                                    <span class="cell-stack">
                                        <strong>{{ $customer->name }}</strong>
                                        <span class="cell-muted">{{ $customer->code }}</span>
                                    </span>
                                </div>
                            </td>
                            <td class="nowrap">{{ $customer->phone ?: '-' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($customer->address, 48) ?: '-' }}</td>
                            <td class="align-end">{{ \App\Support\Format::number($customer->sales_count) }}</td>
                            <td class="align-end nowrap"><strong>{{ \App\Support\Format::rupiah($customer->sales_sum_price ?? 0) }}</strong></td>
                            <td class="cell-actions">
                                @if ($customer->sales->isNotEmpty())
                                    <button type="button" class="action-btn" data-modal-open="customer-history-{{ $customer->id }}" title="Riwayat penjualan" aria-label="Riwayat {{ $customer->name }}">
                                        <x-icon name="clock" :size="16" />
                                    </button>
                                @endif
                                @can('update', $customer)
                                    <button type="button" class="action-btn" data-modal-open="customer-edit-{{ $customer->id }}" title="Edit customer" aria-label="Edit {{ $customer->name }}">
                                        <x-icon name="edit" :size="16" />
                                    </button>
                                @endcan
                                @can('delete', $customer)
                                    <form method="POST" action="{{ route('customers.destroy', $customer) }}" class="inline-form"
                                          data-confirm="Arsipkan customer {{ $customer->name }}?"
                                          data-confirm-detail="Customer tidak dihapus permanen. Riwayat transaksi tetap tersimpan."
                                          data-confirm-label="Arsipkan">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="action-btn action-danger" title="Arsipkan customer" aria-label="Arsipkan {{ $customer->name }}">
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
                                    <span class="empty-icon"><x-icon name="users" :size="30" /></span>
                                    <h3>Belum ada customer</h3>
                                    <p>Tambahkan customer untuk mencatat penjualan kambing.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.pagination', ['paginator' => $customers])
    </section>

    @can('create', App\Models\Customer::class)
        <div class="modal-backdrop" id="customer-create" hidden>
            <div class="modal-dialog modal-md" role="dialog" aria-modal="true" aria-labelledby="customer-create-title">
                <div class="modal-header">
                    <div class="modal-heading">
                        <h2 id="customer-create-title">Tambah Customer</h2>
                        <p>Kode customer dibuat otomatis.</p>
                    </div>
                    <button type="button" class="modal-close" data-modal-close aria-label="Tutup dialog"><x-icon name="x" :size="19" /></button>
                </div>
                <form method="POST" action="{{ route('customers.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="form-grid">
                            <div class="form-group full">
                                <label for="customer-name">Nama customer <span class="req">*</span></label>
                                <input id="customer-name" type="text" name="name" value="{{ old('name') }}" maxlength="120" required placeholder="Contoh: H. Ahmad">
                            </div>
                            <div class="form-group">
                                <label for="customer-phone">Telepon</label>
                                <input id="customer-phone" type="text" name="phone" value="{{ old('phone') }}" maxlength="30" placeholder="0812-3456-7890">
                            </div>
                            <div class="form-group">
                                <label for="customer-address">Alamat</label>
                                <input id="customer-address" type="text" name="address" value="{{ old('address') }}" maxlength="500" placeholder="Alamat customer">
                            </div>
                            <div class="form-group full">
                                <label for="customer-notes">Catatan</label>
                                <textarea id="customer-notes" name="notes" rows="2" maxlength="1000" placeholder="Informasi tambahan…">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline" data-modal-close>Batal</button>
                        <button type="submit" class="btn btn-primary"><x-icon name="check" :size="16" /> Simpan Customer</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan

    @foreach ($customers as $customer)
        @can('update', $customer)
            <div class="modal-backdrop" id="customer-edit-{{ $customer->id }}" hidden>
                <div class="modal-dialog modal-md" role="dialog" aria-modal="true" aria-labelledby="customer-edit-title-{{ $customer->id }}">
                    <div class="modal-header">
                        <div class="modal-heading">
                            <h2 id="customer-edit-title-{{ $customer->id }}">Edit Customer</h2>
                            <p>{{ $customer->code }}</p>
                        </div>
                        <button type="button" class="modal-close" data-modal-close aria-label="Tutup dialog"><x-icon name="x" :size="19" /></button>
                    </div>
                    <form method="POST" action="{{ route('customers.update', $customer) }}">
                        @csrf
                        @method('PUT')
                        <div class="modal-body">
                            <div class="form-grid">
                                <div class="form-group full">
                                    <label for="customer-name-{{ $customer->id }}">Nama customer <span class="req">*</span></label>
                                    <input id="customer-name-{{ $customer->id }}" type="text" name="name" value="{{ $customer->name }}" maxlength="120" required>
                                </div>
                                <div class="form-group">
                                    <label for="customer-phone-{{ $customer->id }}">Telepon</label>
                                    <input id="customer-phone-{{ $customer->id }}" type="text" name="phone" value="{{ $customer->phone }}" maxlength="30">
                                </div>
                                <div class="form-group">
                                    <label for="customer-address-{{ $customer->id }}">Alamat</label>
                                    <input id="customer-address-{{ $customer->id }}" type="text" name="address" value="{{ $customer->address }}" maxlength="500">
                                </div>
                                <div class="form-group full">
                                    <label for="customer-notes-{{ $customer->id }}">Catatan</label>
                                    <textarea id="customer-notes-{{ $customer->id }}" name="notes" rows="2" maxlength="1000">{{ $customer->notes }}</textarea>
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

        @if ($customer->sales->isNotEmpty())
            <div class="modal-backdrop" id="customer-history-{{ $customer->id }}" hidden>
                <div class="modal-dialog modal-md" role="dialog" aria-modal="true" aria-labelledby="customer-history-title-{{ $customer->id }}">
                    <div class="modal-header">
                        <div class="modal-heading">
                            <h2 id="customer-history-title-{{ $customer->id }}">Riwayat Penjualan</h2>
                            <p>{{ $customer->name }} · {{ $customer->phone ?: '-' }}</p>
                        </div>
                        <button type="button" class="modal-close" data-modal-close aria-label="Tutup dialog"><x-icon name="x" :size="19" /></button>
                    </div>
                    <div class="modal-body">
                        <div class="history-summary-grid">
                            <div><span>Total transaksi</span><strong>{{ $customer->sales_count }}</strong></div>
                            <div><span>Total penjualan</span><strong>{{ \App\Support\Format::rupiah($customer->sales_sum_price ?? 0) }}</strong></div>
                            <div><span>Customer sejak</span><strong>{{ \App\Support\Format::date($customer->created_at, true) }}</strong></div>
                        </div>
                        <div class="history-list">
                            @foreach ($customer->sales as $sale)
                                <a class="history-card" href="{{ route('sales.show', $sale) }}">
                                    <span class="history-date">{{ \App\Support\Format::date($sale->date, true) }}</span>
                                    <span class="history-main">
                                        <strong>{{ $sale->goat?->name ?? 'Kambing terarsip' }}</strong>
                                        <small>{{ $sale->code }} · {{ $sale->goat?->barcode ?? '-' }}</small>
                                    </span>
                                    <strong class="history-amount">{{ \App\Support\Format::rupiah($sale->price) }}</strong>
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
