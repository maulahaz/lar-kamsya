@extends('layouts.app')

@section('title', 'Log Aktivitas')
@section('breadcrumb', 'Log Aktivitas')

@section('content')
    <div class="page-head">
        <div class="page-head-text">
            <span class="eyebrow">Sistem</span>
            <h1>Log Aktivitas</h1>
            <p>Jejak audit seluruh perubahan data dan transaksi oleh pengguna.</p>
        </div>
        <div class="page-actions">
            <a class="btn btn-outline" href="{{ route('settings.index') }}">
                <x-icon name="settings" :size="16" /> Pengaturan
            </a>
        </div>
    </div>

    <section class="card">
        <form class="card-toolbar" method="GET" action="{{ route('activity.index') }}">
            <div class="search-field">
                <x-icon name="search" :size="17" />
                <input type="search" name="q" value="{{ $search }}" placeholder="Cari deskripsi atau subjek aktivitas…" aria-label="Cari aktivitas">
            </div>
            <div class="filter-group">
                <select class="filter-select" name="action" aria-label="Filter aksi" data-auto-submit>
                    <option value="">Semua aksi</option>
                    @foreach ($actions as $item)
                        <option value="{{ $item }}" @selected($action === $item)>{{ ucfirst($item) }}</option>
                    @endforeach
                </select>
                <select class="filter-select" name="user" aria-label="Filter pengguna" data-auto-submit>
                    <option value="">Semua pengguna</option>
                    @foreach ($users as $item)
                        <option value="{{ $item->id }}" @selected($userId === $item->id)>{{ $item->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-outline btn-sm"><x-icon name="filter" :size="15" /> Terapkan</button>
                @if ($search !== '' || $action !== '' || $userId)
                    <a class="btn btn-ghost btn-sm" href="{{ route('activity.index') }}"><x-icon name="refresh" :size="15" /> Reset</a>
                @endif
            </div>
        </form>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Pengguna</th>
                        <th>Aksi</th>
                        <th>Deskripsi</th>
                        <th>Subjek</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td class="nowrap">{{ \App\Support\Format::dateTime($log->created_at) }}</td>
                            <td>
                                <div class="cell-person">
                                    <span class="person-avatar avatar-xs tone-slate">{{ $log->user?->initials() ?? '?' }}</span>
                                    <span class="cell-stack">
                                        <strong>{{ $log->user?->name ?? 'Sistem' }}</strong>
                                        <span class="cell-muted">{{ $log->user?->role?->label() ?? '-' }}</span>
                                    </span>
                                </div>
                            </td>
                            <td><span class="badge {{ $log->actionBadge() }}">{{ $log->actionLabel() }}</span></td>
                            <td>{{ $log->description ?: '-' }}</td>
                            <td>
                                @if ($log->subject_label)
                                    <span class="cell-title">{{ $log->subject_label }}</span>
                                @else
                                    <span class="muted">-</span>
                                @endif
                            </td>
                            <td class="mono">{{ $log->ip_address ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-row">
                                <div class="empty-state">
                                    <span class="empty-icon"><x-icon name="activity" :size="30" /></span>
                                    <h3>Belum ada aktivitas</h3>
                                    <p>Log aktivitas akan tercatat otomatis saat pengguna melakukan perubahan data.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.pagination', ['paginator' => $logs])
    </section>
@endsection
