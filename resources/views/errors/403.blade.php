@extends('layouts.app')

@section('title', 'Akses Ditolak')

@section('breadcrumb', '403')

@section('content')
    <div class="not-found">
        <span class="not-found-code">403</span>
        <h1>Akses ditolak</h1>
        <p>Role Anda tidak memiliki izin untuk membuka halaman ini. Hubungi Owner bila membutuhkan akses.</p>
        <a class="btn btn-primary" href="{{ route('dashboard') }}">Kembali ke Dashboard</a>
    </div>
@endsection
