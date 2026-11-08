@extends('layouts.app')

@section('title', 'Pengguna')
@section('breadcrumb', 'Pengguna')

@section('content')
    <div class="page-head">
        <div class="page-head-text">
            <span class="eyebrow">Sistem</span>
            <h1>Manajemen Pengguna</h1>
            <p>Kelola akun dan role Owner, Admin, serta Petugas Kandang.</p>
        </div>
        <div class="page-actions">
            @can('create', App\Models\User::class)
                <button type="button" class="btn btn-primary" data-modal-open="user-create">
                    <x-icon name="plus" :size="16" /> Tambah Pengguna
                </button>
            @endcan
        </div>
    </div>

    @include('partials.errors')

    <section class="metric-grid metric-grid-3">
        <div class="metric-card">
            <span class="metric-icon metric-purple"><x-icon name="shield" :size="19" /></span>
            <div><span>Owner</span><strong>{{ \App\Support\Format::number($counts['owner']) }} akun</strong></div>
        </div>
        <div class="metric-card">
            <span class="metric-icon metric-blue"><x-icon name="users" :size="19" /></span>
            <div><span>Admin</span><strong>{{ \App\Support\Format::number($counts['admin']) }} akun</strong></div>
        </div>
        <div class="metric-card">
            <span class="metric-icon metric-green"><x-icon name="user" :size="19" /></span>
            <div><span>Petugas Kandang</span><strong>{{ \App\Support\Format::number($counts['keeper']) }} akun</strong></div>
        </div>
    </section>

    <section class="card">
        <form class="card-toolbar" method="GET" action="{{ route('users.index') }}">
            <div class="search-field">
                <x-icon name="search" :size="17" />
                <input type="search" name="q" value="{{ $search }}" placeholder="Cari nama atau email pengguna…" aria-label="Cari pengguna">
            </div>
            <div class="filter-group">
                <button type="submit" class="btn btn-outline btn-sm"><x-icon name="search" :size="15" /> Cari</button>
                @if ($search !== '')
                    <a class="btn btn-ghost btn-sm" href="{{ route('users.index') }}">Reset</a>
                @endif
                <span class="toolbar-hint">{{ $users->total() }} akun terdaftar</span>
            </div>
        </form>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Pengguna</th>
                        <th>Role</th>
                        <th>Telepon</th>
                        <th>Status</th>
                        <th>Login Terakhir</th>
                        <th class="th-actions">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr class="{{ $user->is_active ? '' : 'muted' }}">
                            <td>
                                <div class="cell-person">
                                    <span class="person-avatar avatar-sm tone-{{ $user->role === App\Enums\UserRole::Owner ? 'purple' : ($user->role === App\Enums\UserRole::Admin ? 'blue' : 'green') }}">{{ $user->initials() }}</span>
                                    <span class="cell-stack">
                                        <strong>{{ $user->name }} @if ($user->id === auth()->id())<span class="badge badge-neutral" style="margin-left: 4px;">Anda</span>@endif</strong>
                                        <span class="cell-muted">{{ $user->email }}</span>
                                    </span>
                                </div>
                            </td>
                            <td><span class="badge {{ $user->role->badge() }}">{{ $user->role->label() }}</span></td>
                            <td class="nowrap">{{ $user->phone ?: '-' }}</td>
                            <td>
                                @if ($user->is_active)
                                    <span class="badge badge-success"><span class="badge-dot"></span>Aktif</span>
                                @else
                                    <span class="badge badge-danger">Nonaktif</span>
                                @endif
                            </td>
                            <td class="nowrap">{{ $user->last_login_at ? \App\Support\Format::dateTime($user->last_login_at) : 'Belum pernah' }}</td>
                            <td class="cell-actions">
                                @can('update', $user)
                                    <button type="button" class="action-btn" data-modal-open="user-edit-{{ $user->id }}" title="Edit pengguna" aria-label="Edit {{ $user->name }}">
                                        <x-icon name="edit" :size="16" />
                                    </button>
                                @endcan
                                @can('delete', $user)
                                    @if ($user->is_active)
                                        <form method="POST" action="{{ route('users.destroy', $user) }}" class="inline-form"
                                              data-confirm="Nonaktifkan pengguna {{ $user->name }}?"
                                              data-confirm-detail="Akun tidak dihapus permanen. Pengguna tidak dapat masuk sampai diaktifkan kembali."
                                              data-confirm-label="Nonaktifkan">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="action-btn action-danger" title="Nonaktifkan pengguna" aria-label="Nonaktifkan {{ $user->name }}">
                                                <x-icon name="trash" :size="16" />
                                            </button>
                                        </form>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-row">
                                <div class="empty-state">
                                    <span class="empty-icon"><x-icon name="users" :size="30" /></span>
                                    <h3>Pengguna tidak ditemukan</h3>
                                    <p>{{ $search !== '' ? 'Coba ubah kata kunci pencarian.' : 'Tambahkan akun pengguna untuk mulai berkolaborasi.' }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.pagination', ['paginator' => $users])
    </section>

    @can('create', App\Models\User::class)
        <div class="modal-backdrop" id="user-create" hidden>
            <div class="modal-dialog modal-md" role="dialog" aria-modal="true" aria-labelledby="user-create-title">
                <div class="modal-header">
                    <div class="modal-heading">
                        <h2 id="user-create-title">Tambah Pengguna</h2>
                        <p>Akun baru dapat langsung digunakan setelah disimpan.</p>
                    </div>
                    <button type="button" class="modal-close" data-modal-close aria-label="Tutup dialog"><x-icon name="x" :size="19" /></button>
                </div>
                <form method="POST" action="{{ route('users.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="form-grid">
                            <div class="form-group full">
                                <label for="user-name">Nama lengkap <span class="req">*</span></label>
                                <input id="user-name" type="text" name="name" value="{{ old('name') }}" maxlength="120" required placeholder="Nama pengguna">
                            </div>
                            <div class="form-group">
                                <label for="user-email">Email <span class="req">*</span></label>
                                <input id="user-email" type="email" name="email" value="{{ old('email') }}" maxlength="190" required placeholder="nama@kamsya.id">
                            </div>
                            <div class="form-group">
                                <label for="user-phone">Telepon</label>
                                <input id="user-phone" type="text" name="phone" value="{{ old('phone') }}" maxlength="30" placeholder="0812-3456-7890">
                            </div>
                            <div class="form-group full">
                                <label for="user-role">Role <span class="req">*</span></label>
                                <select id="user-role" name="role" required>
                                    @foreach (App\Enums\UserRole::options() as $value => $label)
                                        <option value="{{ $value }}" @selected(old('role', 'keeper') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <p class="form-hint">Owner: akses penuh · Admin: transaksi &amp; laporan · Petugas: inventaris &amp; scan.</p>
                            </div>
                            <div class="form-group">
                                <label for="user-password">Kata sandi <span class="req">*</span></label>
                                <input id="user-password" type="password" name="password" minlength="8" required autocomplete="new-password">
                            </div>
                            <div class="form-group">
                                <label for="user-password-confirm">Konfirmasi kata sandi <span class="req">*</span></label>
                                <input id="user-password-confirm" type="password" name="password_confirmation" minlength="8" required autocomplete="new-password">
                            </div>
                            <div class="form-group full">
                                <input type="hidden" name="is_active" value="0">
                                <label class="check-row"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))> Akun aktif</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline" data-modal-close>Batal</button>
                        <button type="submit" class="btn btn-primary"><x-icon name="check" :size="16" /> Simpan Pengguna</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan

    @foreach ($users as $user)
        @can('update', $user)
            <div class="modal-backdrop" id="user-edit-{{ $user->id }}" hidden>
                <div class="modal-dialog modal-md" role="dialog" aria-modal="true" aria-labelledby="user-edit-title-{{ $user->id }}">
                    <div class="modal-header">
                        <div class="modal-heading">
                            <h2 id="user-edit-title-{{ $user->id }}">Edit Pengguna</h2>
                            <p>{{ $user->email }}</p>
                        </div>
                        <button type="button" class="modal-close" data-modal-close aria-label="Tutup dialog"><x-icon name="x" :size="19" /></button>
                    </div>
                    <form method="POST" action="{{ route('users.update', $user) }}">
                        @csrf
                        @method('PUT')
                        <div class="modal-body">
                            <div class="form-grid">
                                <div class="form-group full">
                                    <label for="user-name-{{ $user->id }}">Nama lengkap <span class="req">*</span></label>
                                    <input id="user-name-{{ $user->id }}" type="text" name="name" value="{{ $user->name }}" maxlength="120" required>
                                </div>
                                <div class="form-group">
                                    <label for="user-email-{{ $user->id }}">Email <span class="req">*</span></label>
                                    <input id="user-email-{{ $user->id }}" type="email" name="email" value="{{ $user->email }}" maxlength="190" required>
                                </div>
                                <div class="form-group">
                                    <label for="user-phone-{{ $user->id }}">Telepon</label>
                                    <input id="user-phone-{{ $user->id }}" type="text" name="phone" value="{{ $user->phone }}" maxlength="30">
                                </div>
                                <div class="form-group full">
                                    <label for="user-role-{{ $user->id }}">Role <span class="req">*</span></label>
                                    <select id="user-role-{{ $user->id }}" name="role" required @disabled($user->id === auth()->id())>
                                        @foreach (App\Enums\UserRole::options() as $value => $label)
                                            <option value="{{ $value }}" @selected($user->role->value === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @if ($user->id === auth()->id())
                                        <input type="hidden" name="role" value="{{ $user->role->value }}">
                                        <p class="form-hint">Anda tidak dapat mengubah role akun sendiri.</p>
                                    @endif
                                </div>
                                <div class="form-group">
                                    <label for="user-password-{{ $user->id }}">Kata sandi baru</label>
                                    <input id="user-password-{{ $user->id }}" type="password" name="password" minlength="8" autocomplete="new-password" placeholder="Biarkan kosong bila tidak diubah">
                                </div>
                                <div class="form-group">
                                    <label for="user-password-confirm-{{ $user->id }}">Konfirmasi kata sandi</label>
                                    <input id="user-password-confirm-{{ $user->id }}" type="password" name="password_confirmation" minlength="8" autocomplete="new-password">
                                </div>
                                <div class="form-group full">
                                    <input type="hidden" name="is_active" value="0">
                                    <label class="check-row">
                                        <input type="checkbox" name="is_active" value="1" @checked($user->is_active) @disabled($user->id === auth()->id())>
                                        Akun aktif
                                    </label>
                                    @if ($user->id === auth()->id())
                                        <input type="hidden" name="is_active" value="1">
                                    @endif
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
    @endforeach
@endsection
