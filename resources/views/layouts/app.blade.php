<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#166534">
    <meta name="description" content="Kamsya — aplikasi pengelolaan inventaris dan jual-beli kambing dengan barcode.">
    <title>@yield('title', 'Dashboard') — {{ $settings->farm_name ?? config('app.name') }}</title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' rx='22' fill='%23166534'/%3E%3Ctext x='50' y='72' font-size='58' text-anchor='middle'%3E%F0%9F%90%90%3C/text%3E%3C/svg%3E">
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">
    @stack('head')
</head>
<body>
    <a class="skip-link" href="#page-content">Lewati ke konten utama</a>

    <div class="app-shell">
        <aside class="sidebar" id="sidebar" aria-label="Navigasi utama">
            <div class="sidebar-inner">
                <div class="sidebar-brand">
                    <span class="brand-mark">🐐</span>
                    <div class="brand-copy">
                        <strong>Kamsya</strong>
                        <span>Livestock Manager</span>
                    </div>
                    <button type="button" class="sidebar-close" data-sidebar-close aria-label="Tutup menu">
                        <x-icon name="x" :size="19" />
                    </button>
                </div>

                <nav class="sidebar-nav" aria-label="Menu aplikasi">
                    <div class="nav-group">
                        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif>
                            <span class="nav-icon"><x-icon name="dashboard" /></span>
                            <span class="nav-text">Dashboard</span>
                        </a>
                    </div>

                    <div class="nav-group">
                        <span class="nav-label">Inventaris</span>
                        <a href="{{ route('goats.index') }}" class="nav-link {{ request()->routeIs('goats.*') ? 'is-active' : '' }}">
                            <span class="nav-icon"><x-icon name="package" /></span>
                            <span class="nav-text">Data Kambing</span>
                            <span class="nav-badge">{{ $navAvailableCount }}</span>
                        </a>
                        <a href="{{ route('scan') }}" class="nav-link {{ request()->routeIs('scan') ? 'is-active' : '' }}">
                            <span class="nav-icon"><x-icon name="scan" /></span>
                            <span class="nav-text">Scan Barcode</span>
                        </a>
                        <a href="{{ route('goats.labels') }}" class="nav-link {{ request()->routeIs('goats.labels') ? 'is-active' : '' }}">
                            <span class="nav-icon"><x-icon name="printer" /></span>
                            <span class="nav-text">Cetak Barcode</span>
                        </a>
                    </div>

                    @can('manage-transactions')
                        <div class="nav-group">
                            <span class="nav-label">Transaksi</span>
                            <a href="{{ route('purchases.index') }}" class="nav-link {{ request()->routeIs('purchases.*') ? 'is-active' : '' }}">
                                <span class="nav-icon"><x-icon name="cart" /></span>
                                <span class="nav-text">Pembelian</span>
                            </a>
                            <a href="{{ route('sales.index') }}" class="nav-link {{ request()->routeIs('sales.*') ? 'is-active' : '' }}">
                                <span class="nav-icon"><x-icon name="tag" /></span>
                                <span class="nav-text">Penjualan</span>
                            </a>
                            <a href="{{ route('expenses.index') }}" class="nav-link {{ request()->routeIs('expenses.*') ? 'is-active' : '' }}">
                                <span class="nav-icon"><x-icon name="wallet" /></span>
                                <span class="nav-text">Beban Operasional</span>
                            </a>
                        </div>

                        <div class="nav-group">
                            <span class="nav-label">Data Pendukung</span>
                            <a href="{{ route('suppliers.index') }}" class="nav-link {{ request()->routeIs('suppliers.*') ? 'is-active' : '' }}">
                                <span class="nav-icon"><x-icon name="truck" /></span>
                                <span class="nav-text">Supplier</span>
                            </a>
                            <a href="{{ route('customers.index') }}" class="nav-link {{ request()->routeIs('customers.*') ? 'is-active' : '' }}">
                                <span class="nav-icon"><x-icon name="users" /></span>
                                <span class="nav-text">Customer</span>
                            </a>
                        </div>
                    @endcan

                    @can('view-reports')
                        <div class="nav-group">
                            <span class="nav-label">Analitik</span>
                            <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'is-active' : '' }}">
                                <span class="nav-icon"><x-icon name="chart" /></span>
                                <span class="nav-text">Laporan Keuangan</span>
                            </a>
                        </div>
                    @endcan

                    @can('manage-users')
                        <div class="nav-group">
                            <span class="nav-label">Sistem</span>
                            <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'is-active' : '' }}">
                                <span class="nav-icon"><x-icon name="shield" /></span>
                                <span class="nav-text">Pengguna</span>
                            </a>
                            <a href="{{ route('activity.index') }}" class="nav-link {{ request()->routeIs('activity.*') ? 'is-active' : '' }}">
                                <span class="nav-icon"><x-icon name="activity" /></span>
                                <span class="nav-text">Log Aktivitas</span>
                            </a>
                            <a href="{{ route('settings.index') }}" class="nav-link {{ request()->routeIs('settings.*') ? 'is-active' : '' }}">
                                <span class="nav-icon"><x-icon name="settings" /></span>
                                <span class="nav-text">Pengaturan</span>
                            </a>
                        </div>
                    @endcan
                </nav>

                <div class="sidebar-foot">
                    <a class="sidebar-farm" href="{{ auth()->user()->isOwner() ? route('settings.index') : route('profile.edit') }}">
                        <span class="farm-dot"></span>
                        <span class="farm-copy">
                            <strong>{{ $settings->farm_name ?? config('app.name') }}</strong>
                            <small>{{ $settings->owner_name ?? config('app.name') }}</small>
                        </span>
                        <x-icon name="chevron-right" :size="15" />
                    </a>
                    <div class="sidebar-meta">
                        <span class="demo-pill"><span></span> MySQL</span>
                        <span>{{ auth()->user()->role->label() }}</span>
                    </div>
                </div>
            </div>
        </aside>
        <div class="sidebar-overlay" id="sidebar-overlay" hidden></div>

        <div class="app-main">
            <header class="topbar">
                <div class="topbar-left">
                    <button type="button" class="icon-btn menu-btn" data-sidebar-toggle aria-label="Buka menu" aria-controls="sidebar">
                        <x-icon name="menu" :size="20" />
                    </button>
                    <div class="breadcrumb">
                        <span class="crumb-muted">Kamsya</span>
                        <x-icon name="chevron-right" :size="14" />
                        <strong>@yield('breadcrumb', 'Dashboard')</strong>
                    </div>
                </div>
                <div class="topbar-right">
                    <span class="topbar-date"><x-icon name="calendar" :size="15" /> {{ \App\Support\Format::todayLong() }}</span>
                    <a class="btn btn-outline btn-sm topbar-scan" href="{{ route('scan') }}">
                        <x-icon name="scan" :size="16" /> <span>Scan Barcode</span>
                    </a>
                    @can('manage-settings')
                        <a class="icon-btn" href="{{ route('settings.index') }}" title="Pengaturan" aria-label="Buka pengaturan">
                            <x-icon name="settings" :size="18" />
                        </a>
                    @endcan
                    <span class="topbar-divider" aria-hidden="true"></span>
                    <a class="user-chip" href="{{ route('profile.edit') }}" title="Buka profil">
                        <span class="user-avatar">{{ auth()->user()->initials() }}</span>
                        <span class="user-copy">
                            <strong>{{ auth()->user()->name }}</strong>
                            <small>{{ auth()->user()->role->label() }}</small>
                        </span>
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="icon-btn" title="Keluar" aria-label="Keluar dari aplikasi">
                            <x-icon name="logout" :size="18" />
                        </button>
                    </form>
                </div>
            </header>

            <main class="page page-enter" id="page-content" tabindex="-1">
                @if (session('success'))
                    <div data-flash="success" data-message="{{ session('success') }}" hidden></div>
                @endif
                @if (session('status'))
                    <div data-flash="success" data-message="{{ session('status') }}" hidden></div>
                @endif
                @if (session('error'))
                    <div data-flash="error" data-message="{{ session('error') }}" hidden></div>
                @endif
                @if ($errors->any())
                    <div data-flash="error" data-message="{{ $errors->first() }}" hidden></div>
                @endif

                @yield('content')
            </main>

            <footer class="app-footer">
                <span>&copy; {{ now()->year }} Kamsya — Manajemen &amp; Jual-Beli Kambing</span>
                <span class="footer-note">Data tersimpan di MySQL · {{ $settings->farm_name }}</span>
            </footer>
        </div>
    </div>

    @stack('modals')

    <div id="modal-root"></div>
    <div id="toast-root" class="toast-root" aria-live="polite" aria-atomic="false"></div>

    <script src="{{ asset('assets/js/barcode.js') }}" defer></script>
    <script src="{{ asset('assets/js/app.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
