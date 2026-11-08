@extends('layouts.app')

@section('title', 'Tambah Kambing')
@section('breadcrumb', 'Tambah Kambing')

@section('content')
    <div class="back-row">
        <a class="back-link" href="{{ route('goats.index') }}"><x-icon name="arrow-left" :size="16" /> Data Kambing</a>
        <span class="back-sep">/</span>
        <span>Tambah</span>
    </div>

    <div class="page-head">
        <div class="page-head-text">
            <span class="eyebrow">Inventaris</span>
            <h1>Tambah Kambing</h1>
            <p>Catat kambing peranakan atau kambing tanpa transaksi pembelian.</p>
        </div>
    </div>

    @include('goats._form', [
        'action' => route('goats.store'),
        'method' => 'POST',
        'goat' => null,
        'mothers' => $mothers,
    ])
@endsection
