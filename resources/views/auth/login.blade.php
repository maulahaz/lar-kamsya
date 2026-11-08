@extends('layouts.guest')

@section('title', 'Masuk')

@section('content')
    <div class="auth-shell">
        <aside class="auth-brand">
            <div class="auth-brand-top">
                <span class="auth-logo">🐐</span>
                <div>
                    <strong>Kamsya</strong>
                    <span>Livestock Manager</span>
                </div>
            </div>

            <div class="auth-brand-copy">
                <h1>Kelola peternakan kambing dengan rapi.</h1>
                <p>Inventaris, barcode, pembelian, penjualan, dan laporan keuangan dalam satu aplikasi.</p>
            </div>

            <ul class="auth-features">
                <li><x-icon name="barcode" :size="18" /> Barcode unik untuk setiap kambing</li>
                <li><x-icon name="chart" :size="18" /> Laba rugi, arus kas, piutang, dan utang</li>
                <li><x-icon name="shield" :size="18" /> Hak akses Owner, Admin, dan Petugas Kandang</li>
            </ul>

            <div class="auth-brand-foot">
                <span class="demo-pill"><span></span> MySQL</span>
                <span>{{ $settings->farm_name ?? config('app.name') }}</span>
            </div>
        </aside>

        <main class="auth-panel">
            <div class="auth-card">
                <div class="auth-heading">
                    <span class="eyebrow">Selamat datang kembali</span>
                    <h2>Masuk ke akun Anda</h2>
                    <p>Gunakan akun yang diberikan Owner untuk mengakses aplikasi.</p>
                </div>

                @if ($errors->any())
                    <div class="inline-alert inline-alert-warning">
                        <x-icon name="alert-circle" :size="16" />
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('login.attempt') }}" class="form auth-form">
                    @csrf

                    <div class="form-group {{ $errors->has('email') ? 'has-error' : '' }}">
                        <label for="email">Email</label>
                        <div class="input-icon">
                            <x-icon name="user" :size="17" />
                            <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="username" autofocus required placeholder="nama@kamsya.id">
                        </div>
                    </div>

                    <div class="form-group {{ $errors->has('password') ? 'has-error' : '' }}">
                        <label for="password">Kata sandi</label>
                        <div class="input-icon">
                            <x-icon name="shield" :size="17" />
                            <input id="password" type="password" name="password" autocomplete="current-password" required placeholder="Masukkan kata sandi">
                        </div>
                    </div>

                    <label class="auth-remember">
                        <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                        <span>Ingat saya di perangkat ini</span>
                    </label>

                    <button type="submit" class="btn btn-primary btn-block">Masuk ke Dashboard</button>
                </form>

                <div class="auth-demo">
                    <span class="section-label">Akun demo</span>
                    <div class="auth-demo-grid">
                        <div><strong>Owner</strong><span>owner@kamsya.id</span></div>
                        <div><strong>Admin</strong><span>admin@kamsya.id</span></div>
                        <div><strong>Petugas</strong><span>petugas@kamsya.id</span></div>
                    </div>
                    <p class="small muted">Kata sandi semua akun demo: <strong>kamsya123</strong></p>
                </div>
            </div>

            <p class="auth-foot">&copy; {{ now()->year }} Kamsya · {{ $settings->farm_name ?? config('app.name') }}</p>
        </main>
    </div>
@endsection
