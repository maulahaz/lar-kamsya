@extends('layouts.app')

@section('title', 'Data Kambing')
@section('breadcrumb', 'Data Kambing')

@section('content')
    <div class="page-head">
        <div class="page-head-text">
            <span class="eyebrow">Inventaris</span>
            <h1>Data Kambing</h1>
            <p>Kelola seluruh kambing, status, dan identitas barcode.</p>
        </div>
        <div class="page-actions">
            <a class="btn btn-outline" href="{{ route('goats.labels') }}">
                <x-icon name="printer" :size="16" /> Cetak Barcode
            </a>
            @can('create', App\Models\Goat::class)
                <a class="btn btn-primary" href="{{ route('goats.create') }}">
                    <x-icon name="plus" :size="16" /> Tambah Kambing
                </a>
            @endcan
        </div>
    </div>

    <section class="card">
        <form class="card-toolbar" method="GET" action="{{ route('goats.index') }}">
            <div class="search-field">
                <x-icon name="search" :size="17" />
                <input type="search" name="q" value="{{ $search }}" placeholder="     Cari nama, kode, barcode, jenis…" aria-label="Cari kambing">
            </div>
            <div class="filter-group">
                <select class="filter-select" name="status" aria-label="Filter status" data-auto-submit>
                    <option value="">Semua status</option>
                    @foreach (App\Enums\GoatStatus::options() as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <select class="filter-select" name="type" aria-label="Filter jenis" data-auto-submit>
                    <option value="">Semua jenis</option>
                    @foreach ($types as $item)
                        <option value="{{ $item }}" @selected($type === $item)>{{ $item }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-outline btn-sm">
                    <x-icon name="filter" :size="15" /> Terapkan
                </button>
                @if ($search !== '' || $status !== '' || $type !== '')
                    <a class="btn btn-ghost btn-sm" href="{{ route('goats.index') }}">Reset</a>
                @endif
            </div>
        </form>

        <div class="table-wrap">
            <table class="data-table goats-table">
                <thead>
                    <tr>
                        <th>Kambing</th>
                        <th>Jenis</th>
                        <th>Gender</th>
                        <th>Bobot</th>
                        <th>Asal</th>
                        <th>Status</th>
                        @can('view-reports')
                            <th class="align-end">Biaya Perolehan</th>
                        @endcan
                        <th class="th-actions">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($goats as $goat)
                        <tr>
                            <td>
                                <div class="cell-goat">
                                    <span class="goat-avatar avatar-sm tone-{{ \App\Support\Format::typeTone($goat->type) }}">🐐</span>
                                    <div class="cell-stack">
                                        <a class="cell-title" href="{{ route('goats.show', $goat) }}">{{ $goat->name }}</a>
                                        <span class="cell-muted">{{ $goat->code }} · <span class="barcode-text">{{ $goat->barcode }}</span></span>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge badge-neutral">{{ $goat->type }}</span></td>
                            <td>{{ $goat->gender->label() }}</td>
                            <td class="nowrap">{{ \App\Support\Format::weight($goat->weight) }}</td>
                            <td>{{ $goat->origin->label() }}</td>
                            <td>
                                <span class="badge {{ $goat->status->badge() }}">
                                    <span class="badge-dot"></span>{{ $goat->status->label() }}
                                </span>
                            </td>
                            @can('view-reports')
                                <td class="align-end nowrap">
                                    {{ $goat->purchase ? \App\Support\Format::rupiah($goat->purchase->total) : '—' }}
                                </td>
                            @endcan
                            <td class="cell-actions">
                                <a class="action-btn" href="{{ route('goats.show', $goat) }}" title="Lihat detail" aria-label="Lihat detail {{ $goat->name }}">
                                    <x-icon name="eye" :size="16" />
                                </a>
                                @can('update', $goat)
                                    <a class="action-btn" href="{{ route('goats.edit', $goat) }}" title="Edit data" aria-label="Edit {{ $goat->name }}">
                                        <x-icon name="edit" :size="16" />
                                    </a>
                                @endcan
                                @can('delete', $goat)
                                    <button type="button" class="action-btn action-danger" title="Arsipkan" aria-label="Arsipkan {{ $goat->name }}" data-modal-open="archive-goat-{{ $goat->id }}">
                                        <x-icon name="trash" :size="16" />
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="empty-row">
                                <div class="empty-state">
                                    <span class="empty-icon"><x-icon name="package" :size="30" /></span>
                                    <h3>Belum ada kambing</h3>
                                    <p>Mulai dengan mencatat kambing peranakan atau pembelian.</p>
                                    @can('create', App\Models\Goat::class)
                                        <div class="empty-action">
                                            <a class="btn btn-primary btn-sm" href="{{ route('goats.create') }}">Tambah Kambing</a>
                                        </div>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.pagination', ['paginator' => $goats])
    </section>

    @can('manage-transactions')
        @foreach ($goats as $goat)
            @can('delete', $goat)
                <div class="modal-backdrop" id="archive-goat-{{ $goat->id }}" hidden>
                    <div class="modal-dialog modal-sm" role="dialog" aria-modal="true" aria-labelledby="archive-title-{{ $goat->id }}">
                        <div class="modal-header">
                            <div class="modal-heading">
                                <h2 id="archive-title-{{ $goat->id }}">Arsipkan Kambing</h2>
                                <p>{{ $goat->code }} · {{ $goat->name }}</p>
                            </div>
                            <button type="button" class="modal-close" data-modal-close aria-label="Tutup dialog"><x-icon name="x" :size="19" /></button>
                        </div>
                        <form method="POST" action="{{ route('goats.destroy', $goat) }}" data-confirm="Arsipkan kambing {{ $goat->name }}?" data-confirm-label="Arsipkan" data-confirm-tone="danger">
                            @csrf
                            @method('DELETE')
                            <div class="modal-body">
                                <div class="inline-alert inline-alert-warning">
                                    <x-icon name="alert-triangle" :size="16" />
                                    <span>Kambing tidak dihapus permanen. Riwayat transaksi tetap tersimpan untuk laporan.</span>
                                </div>
                                <div class="form-group" style="margin-top: 14px;">
                                    <label for="reason-{{ $goat->id }}">Alasan arsip <span class="muted small">(opsional)</span></label>
                                    <input id="reason-{{ $goat->id }}" type="text" name="reason" maxlength="255" placeholder="Misalnya: data duplikat">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline" data-modal-close>Batal</button>
                                <button type="submit" class="btn btn-danger">Arsipkan</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endcan
        @endforeach
    @endcan
@endsection
