@extends('layouts.app')

@section('title', 'Halaman Tidak Ditemukan')

@section('breadcrumb', '404')

@section('content')
    <div class="not-found">
        <span class="not-found-code">404</span>
        <h1>Halaman tidak ditemukan</h1>
        <p>Alamat yang Anda tuju tidak tersedia atau sudah dipindahkan.</p>
        <a class="btn btn-primary" href="{{ route('dashboard') }}">Kembali ke Dashboard</a>
    </div>
@endsection
