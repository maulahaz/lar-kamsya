@extends('layouts.app')

@section('title', 'Catat Penjualan')
@section('breadcrumb', 'Catat Penjualan')

@section('content')
    <div class="back-row">
        <a class="back-link" href="{{ route('sales.index') }}"><x-icon name="arrow-left" :size="16" /> Penjualan</a>
        <span class="back-sep">/</span>
        <span>Baru</span>
    </div>

    <div class="page-head">
        <div class="page-head-text">
            <span class="eyebrow">Transaksi</span>
            <h1>Catat Penjualan Kambing</h1>
            <p>HPP diambil otomatis dari biaya perolehan kambing dan disimpan permanen pada transaksi.</p>
        </div>
    </div>

    @include('partials.errors')

    <form method="POST" action="{{ route('sales.store') }}" class="form">
        @csrf

        <div class="settings-layout">
            <div class="settings-main">
                <section class="card">
                    <div class="card-header">
                        <div>
                            <h2>Data Penjualan</h2>
                            <p>Kambing, pembeli, dan harga jual.</p>
                        </div>
                        <x-icon name="tag" :size="19" />
                    </div>
                    <div class="card-body">
                        <div class="form-grid">
                            <div class="form-group {{ $errors->has('date') ? 'has-error' : '' }}">
                                <label for="date">Tanggal penjualan <span class="req">*</span></label>
                                <input id="date" type="date" name="date" value="{{ old('date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
                                @error('date')<span class="field-error">{{ $message }}</span>@enderror
                            </div>

                            <div class="form-group {{ $errors->has('customer_id') ? 'has-error' : '' }}">
                                <label for="customer_id">Pembeli / customer <span class="req">*</span></label>
                                <select id="customer_id" name="customer_id" required>
                                    <option value="">Pilih customer</option>
                                    @foreach ($customers as $customer)
                                        <option value="{{ $customer->id }}" @selected((string) old('customer_id') === (string) $customer->id)>
                                            {{ $customer->name }} · {{ $customer->phone }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('customer_id')<span class="field-error">{{ $message }}</span>@enderror
                                @if ($customers->isEmpty())
                                    <p class="form-hint form-hint-warning">Belum ada customer. <a class="inline-link" href="{{ route('customers.index') }}">Tambahkan customer</a> terlebih dahulu.</p>
                                @endif
                            </div>

                            <div class="form-group full {{ $errors->has('goat_id') ? 'has-error' : '' }}">
                                <label for="goat_id">Kambing yang dijual <span class="req">*</span></label>
                                <select id="goat_id" name="goat_id" required data-sale-goat>
                                    <option value="">Pilih kambing tersedia</option>
                                    @foreach ($goats as $goat)
                                        <option value="{{ $goat->id }}"
                                                data-cost="{{ (int) $goat->acquisitionCost() }}"
                                                data-barcode="{{ $goat->barcode }}"
                                                data-weight="{{ (int) $goat->weight }}"
                                                @selected((string) old('goat_id', $selectedGoat) === (string) $goat->id)>
                                            {{ $goat->code }} · {{ $goat->name }} · {{ $goat->type }} · {{ \App\Support\Format::weight($goat->weight) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('goat_id')<span class="field-error">{{ $message }}</span>@enderror
                                @if ($goats->isEmpty())
                                    <p class="form-hint form-hint-warning">Tidak ada kambing tersedia untuk dijual. <a class="inline-link" href="{{ route('purchases.create') }}">Catat pembelian</a> terlebih dahulu.</p>
                                @endif
                            </div>

                            <div class="form-group {{ $errors->has('price') ? 'has-error' : '' }}">
                                <label for="price">Harga jual <span class="req">*</span></label>
                                <div class="input-suffix">
                                    <input id="price" type="number" name="price" value="{{ old('price') }}" min="1" step="1000" inputmode="numeric" placeholder="3200000" required data-sale-price>
                                    <span>Rp</span>
                                </div>
                                @error('price')<span class="field-error">{{ $message }}</span>@enderror
                            </div>

                            <div class="form-group full {{ $errors->has('notes') ? 'has-error' : '' }}">
                                <label for="notes">Catatan</label>
                                <textarea id="notes" name="notes" rows="2" maxlength="1000" placeholder="Catatan transaksi penjualan…">{{ old('notes') }}</textarea>
                                @error('notes')<span class="field-error">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>
                </section>

                <section class="card">
                    <div class="card-header">
                        <div>
                            <h2>Pembayaran Awal</h2>
                            <p>Opsional. Sisa tagihan otomatis menjadi piutang customer.</p>
                        </div>
                        <x-icon name="wallet" :size="19" />
                    </div>
                    <div class="card-body">
                        <div class="form-grid">
                            <div class="form-group {{ $errors->has('paid_amount') ? 'has-error' : '' }}">
                                <label for="paid_amount">Nominal dibayar sekarang</label>
                                <div class="input-suffix">
                                    <input id="paid_amount" type="number" name="paid_amount" value="{{ old('paid_amount', 0) }}" min="0" step="1000" inputmode="numeric" placeholder="0" data-sale-paid>
                                    <span>Rp</span>
                                </div>
                                <p class="form-hint">Maksimal <span data-sale-max>Rp0</span>. Biarkan 0 bila pembayaran dilakukan kemudian.</p>
                                @error('paid_amount')<span class="field-error">{{ $message }}</span>@enderror
                            </div>

                            <div class="form-group {{ $errors->has('payment_method') ? 'has-error' : '' }}">
                                <label for="payment_method">Metode pembayaran</label>
                                <select id="payment_method" name="payment_method">
                                    @foreach (App\Enums\PaymentMethod::options() as $value => $label)
                                        <option value="{{ $value }}" @selected(old('payment_method', 'tunai') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('payment_method')<span class="field-error">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>
                </section>

                <div class="form-actions" style="display: flex; gap: 10px; justify-content: flex-end;">
                    <a class="btn btn-outline" href="{{ route('sales.index') }}">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <x-icon name="check" :size="16" /> Simpan Penjualan
                    </button>
                </div>
            </div>

            <aside class="settings-side">
                <section class="card">
                    <div class="card-header">
                        <div>
                            <h2>Kambing Terpilih</h2>
                            <p>Ringkasan biaya perolehan</p>
                        </div>
                        <x-icon name="goat" :size="19" />
                    </div>
                    <div class="card-body">
                        <div class="info-grid" style="grid-template-columns: minmax(0, 1fr);">
                            <div class="info-item"><span>HPP / biaya perolehan</span><strong data-sale-cost>Rp0</strong></div>
                            <div class="info-item"><span>Barcode</span><strong class="barcode-text" data-sale-barcode>-</strong></div>
                            <div class="info-item"><span>Bobot</span><strong data-sale-weight>-</strong></div>
                            <div class="info-item"><span>Sisa tagihan</span><strong data-sale-remaining>Rp0</strong></div>
                        </div>
                        <div class="inline-alert inline-alert-info" style="margin-top: 14px;">
                            <x-icon name="info" :size="16" />
                            <span>Laba dihitung dari harga jual dikurangi HPP yang tersimpan saat transaksi dibuat.</span>
                        </div>
                    </div>
                </section>

                <section class="card">
                    <div class="card-header">
                        <div>
                            <h2>Alur Penjualan</h2>
                            <p>Setelah disimpan</p>
                        </div>
                        <x-icon name="sparkles" :size="19" />
                    </div>
                    <div class="card-body">
                        <ol class="workflow-list">
                            <li><span>1</span><div><strong>Transaksi tersimpan</strong><small>Kode TRX-J dibuat otomatis</small></div></li>
                            <li><span>2</span><div><strong>HPP dikunci</strong><small>Nilai perolehan tidak berubah</small></div></li>
                            <li><span>3</span><div><strong>Status kambing</strong><small>Berubah menjadi terjual</small></div></li>
                            <li><span>4</span><div><strong>Piutang tercatat</strong><small>Bila belum dibayar penuh</small></div></li>
                        </ol>
                    </div>
                </section>
            </aside>
        </div>
    </form>
@endsection
