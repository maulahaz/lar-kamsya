@extends('layouts.app')

@section('title', 'Profil Saya')
@section('breadcrumb', 'Profil Saya')

@section('content')
    <div class="page-head">
        <div class="page-head-text">
            <span class="eyebrow">Akun</span>
            <h1>Profil Saya</h1>
            <p>Perbarui informasi akun dan kata sandi Anda.</p>
        </div>
        <div class="page-actions">
            <a class="btn btn-outline" href="{{ route('dashboard') }}">
                <x-icon name="arrow-left" :size="16" /> Dashboard
            </a>
        </div>
    </div>

    @include('partials.errors')

    @php
        $roleTone = match ($user->role) {
            \App\Enums\UserRole::Owner => 'purple',
            \App\Enums\UserRole::Admin => 'blue',
            default => 'green',
        };
    @endphp

    <div class="settings-layout">
        <div class="settings-main">            <form method="POST" action="{{ route('profile.update') }}" class="form">
                @csrf
                @method('PUT')

                <section class="card">
                    <div class="card-header">
                        <div>
                            <h2>Informasi Akun</h2>
                            <p>Nama dan kontak yang digunakan pada aplikasi.</p>
                        </div>
                        <x-icon name="user" :size="19" />
                    </div>
                    <div class="card-body">
                        <div class="form-grid">
                            <div class="form-group full">
                                <label for="profile-name">Nama lengkap <span class="req">*</span></label>
                                <input id="profile-name" type="text" name="name" value="{{ old('name', $user->name) }}" maxlength="120" required>
                            </div>
                            <div class="form-group">
                                <label for="profile-email">Email <span class="req">*</span></label>
                                <div class="input-icon">
                                    <x-icon name="mail" :size="16" />
                                    <input id="profile-email" type="email" name="email" value="{{ old('email', $user->email) }}" maxlength="190" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="profile-phone">Telepon</label>
                                <div class="input-icon">
                                    <x-icon name="phone" :size="16" />
                                    <input id="profile-phone" type="text" name="phone" value="{{ old('phone', $user->phone) }}" maxlength="30" placeholder="0812-3456-7890">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top: 1px solid var(--line);">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <x-icon name="check" :size="15" /> Simpan Profil
                        </button>
                    </div>
                </section>
            </form>

            <form method="POST" action="{{ route('profile.password') }}" class="form">
                @csrf
                @method('PUT')

                <section class="card">
                    <div class="card-header">
                        <div>
                            <h2>Kata Sandi</h2>
                            <p>Gunakan minimal 8 karakter yang sulit ditebak.</p>
                        </div>
                        <x-icon name="shield" :size="19" />
                    </div>
                    <div class="card-body">
                        <div class="form-grid">
                            <div class="form-group full">
                                <label for="current-password">Kata sandi saat ini <span class="req">*</span></label>
                                <input id="current-password" type="password" name="current_password" required autocomplete="current-password">
                            </div>
                            <div class="form-group">
                                <label for="new-password">Kata sandi baru <span class="req">*</span></label>
                                <input id="new-password" type="password" name="password" minlength="8" required autocomplete="new-password">
                            </div>
                            <div class="form-group">
                                <label for="new-password-confirm">Konfirmasi kata sandi baru <span class="req">*</span></label>
                                <input id="new-password-confirm" type="password" name="password_confirmation" minlength="8" required autocomplete="new-password">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top: 1px solid var(--line);">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <x-icon name="check" :size="15" /> Perbarui Kata Sandi
                        </button>
                    </div>
                </section>
            </form>
        </div>

        <aside class="settings-side">
            <section class="card profile-card">
                <div class="profile-cover tone-{{ $roleTone }}">
                    <span class="profile-pattern"></span>
                    <span class="profile-avatar">{{ $user->initials() }}</span>
                    <span class="profile-status"><span class="badge {{ $user->role->badge() }}">{{ $user->role->label() }}</span></span>
                </div>
                <div class="profile-body">
                    <h2>{{ $user->name }}</h2>
                    <div class="profile-id">{{ $user->email }}</div>
                    <div class="profile-chips">
                        @if ($user->is_active)
                            <span class="badge badge-success"><span class="badge-dot"></span>Aktif</span>
                        @else
                            <span class="badge badge-danger">Nonaktif</span>
                        @endif
                        <span class="badge badge-neutral">{{ $user->phone ?: 'Tanpa telepon' }}</span>
                    </div>
                    <div class="profile-stats">
                        <div><span>Role</span><strong>{{ $user->role->label() }}</strong></div>
                        <div><span>Login terakhir</span><strong>{{ $user->last_login_at ? \App\Support\Format::dateTime($user->last_login_at) : 'Belum pernah' }}</strong></div>
                        <div><span>Terdaftar</span><strong>{{ \App\Support\Format::date($user->created_at, true) }}</strong></div>
                        <div><span>Peternakan</span><strong>{{ $settings->farm_name }}</strong></div>
                    </div>
                </div>
            </section>

            <section class="card">
                <div class="card-header">
                    <div>
                        <h2>Akses Role</h2>
                        <p>{{ $user->role->label() }}</p>
                    </div>
                    <x-icon name="shield" :size="19" />
                </div>
                <div class="card-body">
                    <div class="inline-alert inline-alert-info">
                        <x-icon name="info" :size="16" />
                        <span>{{ $user->role->description() }}</span>
                    </div>
                </div>
            </section>
        </aside>
    </div>
@endsection
