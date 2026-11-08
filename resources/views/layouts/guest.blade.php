<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#166534">
    <title>@yield('title', 'Masuk') — {{ $settings->farm_name ?? config('app.name') }}</title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' rx='22' fill='%23166534'/%3E%3Ctext x='50' y='72' font-size='58' text-anchor='middle'%3E%F0%9F%90%90%3C/text%3E%3C/svg%3E">
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">
</head>
<body class="auth-body">
    @yield('content')

    <div id="toast-root" class="toast-root" aria-live="polite" aria-atomic="false"></div>
    <script src="{{ asset('assets/js/app.js') }}" defer></script>
</body>
</html>
