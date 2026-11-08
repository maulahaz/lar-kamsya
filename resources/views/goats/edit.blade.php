@extends('layouts.app')

@section('title', 'Ubah Kambing')
@section('breadcrumb', 'Ubah Kambing')

@section('content')
    <div class="back-row">
        <a class="back-link" href="{{ route('goats.show', $goat) }}"><x-icon name="arrow-left" :size="16" /> {{ $goat->code }}</a>
        <span class="back-sep">/</span>
        <span>Ubah</span>
    </div>

    <div class="page-head">
        <div class="page-head-text">
            <span class="eyebrow">Inventaris</span>
            <h1>Ubah {{ $goat->name }}</h1>
            <p>{{ $goat->code }} · {{ $goat->barcode }}</p>
        </div>
    </div>

    @include('goats._form', [
        'action' => route('goats.update', $goat),
        'method' => 'PUT',
        'goat' => $goat,
        'mothers' => $mothers,
    ])
@endsection
