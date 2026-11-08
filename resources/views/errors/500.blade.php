@extends('layouts.app')

@section('title', 'Terjadi Kesalahan')

@section('breadcrumb', '500')

@section('content')
    <div class="not-found">
        <span class="not-found-code">500</span>
        <h1>Terjadi kesalahan pada server</h1>
        <p>Silakan coba lagi. Bila masalah berlanjut, hubungi administrator.</p>
        <a class="btn btn-primary" href="{{ route('dashboard') }}">Kembali ke Dashboard</a>
    </div>
@endsection
