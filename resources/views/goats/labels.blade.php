@extends('layouts.app')

@section('title', 'Cetak Barcode')
@section('breadcrumb', 'Cetak Barcode')

@section('content')
    <div class="page-head">
        <div class="page-head-text">
            <span class="eyebrow">Inventaris</span>
            <h1>Cetak Barcode</h1>
            <p>Pilih kambing, pratinjau label, lalu cetak menggunakan browser.</p>
        </div>
        <div class="page-actions">
            <a class="btn btn-outline" href="{{ route('goats.index') }}">
                <x-icon name="arrow-left" :size="16" /> Data Kambing
            </a>
            <button type="button" class="btn btn-primary" data-print="labels" @disabled($goats->isEmpty())>
                <x-icon name="printer" :size="16" /> Cetak Label
            </button>
        </div>
    </div>

    <form method="GET" action="{{ route('goats.labels') }}" class="print-layout label-format-{{ $format }}" data-label-format-form>
        <section class="card print-controls no-print">
            <div class="card-header">
                <div>
                    <h2>Pilih Kambing</h2>
                    <p>{{ count($selected) }} kambing dipilih</p>
                </div>
                <span class="badge badge-neutral">{{ $allGoats->count() }} kambing</span>
            </div>
            <div class="label-format-row">
                <div class="label-format-copy">
                    <strong>Format label</strong>
                    <small>Pilih jenis kode yang akan dicetak pada label.</small>
                </div>
                <div class="segmented" role="group" aria-label="Format label">
                    <label>
                        <input type="radio" name="format" value="barcode" @checked($format === 'barcode')>
                        <span><x-icon name="barcode" :size="14" /> Barcode</span>
                    </label>
                    <label>
                        <input type="radio" name="format" value="qr" @checked($format === 'qr')>
                        <span><x-icon name="qr" :size="14" /> QR Code</span>
                    </label>
                </div>
            </div>
            <div class="picker-tools">
                <div class="search-field">
                    <x-icon name="search" :size="17" />
                    <input type="search" placeholder="     Cari nama, kode, barcode…" aria-label="Cari kambing untuk dicetak" data-filter-labels>
                </div>
                <div class="picker-actions">
                    <button type="button" class="btn btn-ghost btn-sm" data-check-all='[name="goats[]"]'>
                        <x-icon name="check" :size="15" /> Pilih semua
                    </button>
                    <a class="btn btn-ghost btn-sm" href="{{ route('goats.labels', ['format' => $format]) }}">
                        <x-icon name="x" :size="15" /> Kosongkan
                    </a>
                </div>
            </div>
            <div class="picker-list">
                @foreach ($allGoats as $item)
                    <label class="picker-item {{ in_array($item->id, $selected, true) ? 'is-selected' : '' }}" data-label-item>
                        <input type="checkbox" name="goats[]" value="{{ $item->id }}" @checked(in_array($item->id, $selected, true))>
                        <span class="goat-avatar avatar-sm tone-{{ \App\Support\Format::typeTone($item->type) }}">🐐</span>
                        <span class="picker-info">
                            <strong>{{ $item->name }}</strong>
                            <small>{{ $item->code }} · {{ $item->type }}</small>
                            <span class="barcode-text">{{ $item->barcode }}</span>
                        </span>
                        <span class="badge {{ $item->status->badge() }}">{{ $item->status->label() }}</span>
                    </label>
                @endforeach
            </div>
            <div class="picker-note">
                <x-icon name="info" :size="15" />
                <span>Label dirancang untuk kertas A4 dengan 2 label per baris. Tekan “Terapkan” setelah memilih.</span>
            </div>
            <div class="card-footer" style="display: flex; justify-content: flex-end; gap: 8px; padding: 12px 16px; border-top: 1px solid var(--line);">
                <button type="submit" class="btn btn-primary btn-sm">
                    <x-icon name="refresh" :size="15" /> Terapkan Pilihan
                </button>
            </div>
        </section>

        <section class="card label-preview-card">
            <div class="card-header no-print">
                <div>
                    <h2>Pratinjau Label</h2>
                    <p>Label siap dicetak dengan barcode unik setiap kambing</p>
                </div>
                <div class="preview-head-actions" style="display: flex; align-items: center; gap: 8px;">
                    <span class="badge badge-neutral" data-label-format-badge>{{ $format === 'qr' ? 'QR Code' : 'Barcode' }}</span>
                    <span class="badge badge-success">{{ $goats->count() }} label</span>
                    <button type="button" class="btn btn-primary btn-sm" data-print="labels" @disabled($goats->isEmpty())>
                        <x-icon name="printer" :size="15" /> Cetak
                    </button>
                </div>
            </div>
            <div class="label-preview-body">
                @if ($goats->isEmpty())
                    <div class="label-empty">
                        <div class="empty-state">
                            <span class="empty-icon"><x-icon name="barcode" :size="30" /></span>
                            <h3>Belum ada label dipilih</h3>
                            <p>Pilih kambing dari daftar di samping untuk melihat pratinjau dan mencetak label barcode.</p>
                        </div>
                    </div>
                @else
                    <div class="label-grid">
                        @foreach ($goats as $item)
                            <article class="barcode-label">
                                <div class="label-brand">
                                    <span class="label-logo">🐐</span>
                                    <span class="label-brand-text">
                                        <strong>KAMSYA</strong>
                                        <small>{{ $settings->farm_name }}</small>
                                    </span>
                                </div>
                                <div class="label-goat">
                                    <strong>{{ $item->name }}</strong>
                                    <span>{{ $item->code }} · {{ $item->type }} · {{ $item->gender->label() }}</span>
                                </div>
                                <div class="label-barcode">
                                    <div class="barcode-svg" data-barcode="{{ $item->barcode }}" data-barcode-height="54" data-barcode-module="2.2"></div>
                                </div>
                                <div class="label-qr">
                                    <div class="qr-code" data-qr="{{ $item->barcode }}" data-qr-size="160"></div>
                                </div>
                                <div class="label-code">{{ $item->barcode }}</div>
                                <div class="label-foot">{{ $settings->phone }} · {{ $settings->address }}</div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="print-footer-note no-print">
                <x-icon name="info" :size="15" />
                <span>Pastikan ukuran kertas A4 dan opsi “Background graphics” aktif pada dialog cetak. Nama usaha: <strong>{{ $settings->farm_name }}</strong>.</span>
            </div>
        </section>
    </form>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/qrcode.min.js') }}"></script>
@endpush
