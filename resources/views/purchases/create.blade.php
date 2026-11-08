@extends('layouts.app')

@section('title', 'Catat Pembelian')
@section('breadcrumb', 'Catat Pembelian')

@section('content')
    <div class="back-row">
        <a class="back-link" href="{{ route('purchases.index') }}"><x-icon name="arrow-left" :size="16" /> Pembelian</a>
        <span class="back-sep">/</span>
        <span>Baru</span>
    </div>

    <div class="page-head">
        <div class="page-head-text">
            <span class="eyebrow">Transaksi</span>
            <h1>Catat Pembelian Kambing</h1>
            <p>Kambing yang disimpan akan otomatis masuk ke inventaris dengan barcode unik.</p>
        </div>
    </div>

    @include('partials.errors')

    <form method="POST" action="{{ route('purchases.store') }}" class="form" data-total-form>
        @csrf

        <div class="settings-layout">
            <div class="settings-main">
                <section class="card">
                    <div class="card-header">
                        <div>
                            <h2>Data Pembelian</h2>
                            <p>Tanggal transaksi, supplier, dan nilai pembelian.</p>
                        </div>
                        <x-icon name="cart" :size="19" />
                    </div>
                    <div class="card-body">
                        <div class="form-grid">
                            <div class="form-group {{ $errors->has('date') ? 'has-error' : '' }}">
                                <label for="date">Tanggal pembelian <span class="req">*</span></label>
                                <input id="date" type="date" name="date" value="{{ old('date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
                                @error('date')<span class="field-error">{{ $message }}</span>@enderror
                            </div>

                            <div class="form-group {{ $errors->has('supplier_id') ? 'has-error' : '' }}">
                                <label for="supplier_id">Supplier / penjual <span class="req">*</span></label>
                                <select id="supplier_id" name="supplier_id" required>
                                    <option value="">Pilih supplier</option>
                                    @foreach ($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" @selected((string) old('supplier_id') === (string) $supplier->id)>
                                            {{ $supplier->name }} · {{ $supplier->phone }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('supplier_id')<span class="field-error">{{ $message }}</span>@enderror
                                @if ($suppliers->isEmpty())
                                    <p class="form-hint form-hint-warning">Belum ada supplier. <a class="inline-link" href="{{ route('suppliers.index') }}">Tambahkan supplier</a> terlebih dahulu.</p>
                                @endif
                            </div>

                            <div class="form-group {{ $errors->has('price') ? 'has-error' : '' }}">
                                <label for="price">Harga beli <span class="req">*</span></label>
                                <div class="input-suffix">
                                    <input id="price" type="number" name="price" value="{{ old('price') }}" min="0" step="1000" inputmode="numeric" placeholder="2500000" required>
                                    <span>Rp</span>
                                </div>
                                @error('price')<span class="field-error">{{ $message }}</span>@enderror
                            </div>

                            <div class="form-group {{ $errors->has('additional_cost') ? 'has-error' : '' }}">
                                <label for="additional_cost">Biaya tambahan</label>
                                <div class="input-suffix">
                                    <input id="additional_cost" type="number" name="additional_cost" value="{{ old('additional_cost', 0) }}" min="0" step="1000" inputmode="numeric" placeholder="Opsional">
                                    <span>Rp</span>
                                </div>
                                <p class="form-hint">Transportasi, karantina, atau biaya lain yang menambah HPP.</p>
                                @error('additional_cost')<span class="field-error">{{ $message }}</span>@enderror
                            </div>

                            <div class="form-group full {{ $errors->has('notes') ? 'has-error' : '' }}">
                                <label for="notes">Catatan</label>
                                <textarea id="notes" name="notes" rows="2" maxlength="1000" placeholder="Catatan transaksi pembelian…">{{ old('notes') }}</textarea>
                                @error('notes')<span class="field-error">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>
                </section>

                <section class="card">
                    <div class="card-header">
                        <div>
                            <h2>Data Kambing</h2>
                            <p>Identitas kambing yang masuk ke inventaris.</p>
                        </div>
                        <x-icon name="goat" :size="19" />
                    </div>
                    <div class="card-body">
                        <div class="form-grid">
                            <div class="form-group full {{ $errors->has('goat.name') ? 'has-error' : '' }}">
                                <label for="goat-name">Nama / label kambing <span class="req">*</span></label>
                                <input id="goat-name" type="text" name="goat[name]" value="{{ old('goat.name') }}" maxlength="100" placeholder="Contoh: Si Jago" required>
                                @error('goat.name')<span class="field-error">{{ $message }}</span>@enderror
                            </div>

                            <div class="form-group {{ $errors->has('goat.type') ? 'has-error' : '' }}">
                                <label for="goat-type">Jenis kambing <span class="req">*</span></label>
                                <input id="goat-type" type="text" name="goat[type]" list="goat-types" value="{{ old('goat.type') }}" maxlength="40" required>
                                <datalist id="goat-types">
                                    <option value="Boer"></option>
                                    <option value="Boer Cross"></option>
                                    <option value="Etawa"></option>
                                    <option value="Jawa"></option>
                                    <option value="Kacang"></option>
                                    <option value="Saanen"></option>
                                </datalist>
                                @error('goat.type')<span class="field-error">{{ $message }}</span>@enderror
                            </div>

                            <div class="form-group {{ $errors->has('goat.gender') ? 'has-error' : '' }}">
                                <label>Jenis kelamin <span class="req">*</span></label>
                                <div class="segmented">
                                    <label>
                                        <input type="radio" name="goat[gender]" value="jantan" @checked(old('goat.gender', 'jantan') === 'jantan')>
                                        <span>♂ Jantan</span>
                                    </label>
                                    <label>
                                        <input type="radio" name="goat[gender]" value="betina" @checked(old('goat.gender') === 'betina')>
                                        <span>♀ Betina</span>
                                    </label>
                                </div>
                                @error('goat.gender')<span class="field-error">{{ $message }}</span>@enderror
                            </div>

                            <div class="form-group {{ $errors->has('goat.birth_date') ? 'has-error' : '' }}">
                                <label for="goat-birth">Tanggal lahir</label>
                                <input id="goat-birth" type="date" name="goat[birth_date]" value="{{ old('goat.birth_date') }}" max="{{ now()->toDateString() }}">
                                @error('goat.birth_date')<span class="field-error">{{ $message }}</span>@enderror
                            </div>

                            <div class="form-group {{ $errors->has('goat.weight') ? 'has-error' : '' }}">
                                <label for="goat-weight">Bobot <span class="req">*</span></label>
                                <div class="input-suffix">
                                    <input id="goat-weight" type="number" name="goat[weight]" value="{{ old('goat.weight') }}" min="0.1" max="500" step="0.1" inputmode="decimal" placeholder="35" required>
                                    <span>kg</span>
                                </div>
                                @error('goat.weight')<span class="field-error">{{ $message }}</span>@enderror
                            </div>

                            <div class="form-group {{ $errors->has('goat.color') ? 'has-error' : '' }}">
                                <label for="goat-color">Warna</label>
                                <input id="goat-color" type="text" name="goat[color]" value="{{ old('goat.color') }}" maxlength="40" placeholder="Contoh: Cokelat">
                                @error('goat.color')<span class="field-error">{{ $message }}</span>@enderror
                            </div>

                            <div class="form-group full {{ $errors->has('goat.notes') ? 'has-error' : '' }}">
                                <label for="goat-notes">Catatan kambing</label>
                                <textarea id="goat-notes" name="goat[notes]" rows="2" maxlength="1000" placeholder="Kondisi kesehatan, vaksinasi, dan lain-lain…">{{ old('goat.notes') }}</textarea>
                                @error('goat.notes')<span class="field-error">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>
                </section>

                <section class="card">
                    <div class="card-header">
                        <div>
                            <h2>Pembayaran Awal</h2>
                            <p>Opsional. Sisa tagihan otomatis menjadi utang supplier.</p>
                        </div>
                        <x-icon name="wallet" :size="19" />
                    </div>
                    <div class="card-body">
                        <div class="form-grid">
                            <div class="form-group {{ $errors->has('paid_amount') ? 'has-error' : '' }}">
                                <label for="paid_amount">Nominal dibayar sekarang</label>
                                <div class="input-suffix">
                                    <input id="paid_amount" type="number" name="paid_amount" value="{{ old('paid_amount', 0) }}" min="0" step="1000" inputmode="numeric" placeholder="0">
                                    <span>Rp</span>
                                </div>
                                <p class="form-hint">Biarkan 0 bila pembelian dilakukan secara tempo.</p>
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
                    <a class="btn btn-outline" href="{{ route('purchases.index') }}">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <x-icon name="check" :size="16" /> Simpan Pembelian
                    </button>
                </div>
            </div>

            <aside class="settings-side">
                <section class="card">
                    <div class="card-header">
                        <div>
                            <h2>Ringkasan</h2>
                            <p>Perhitungan otomatis</p>
                        </div>
                        <x-icon name="wallet" :size="19" />
                    </div>
                    <div class="card-body">
                        <div class="total-preview">
                            <span>Total pembelian</span>
                            <strong data-total-output>Rp0</strong>
                        </div>
                        <div class="inline-alert inline-alert-info" style="margin-top: 14px;">
                            <x-icon name="info" :size="16" />
                            <span>Total pembelian menjadi <strong>HPP</strong> kambing dan dipakai saat menghitung laba penjualan.</span>
                        </div>
                    </div>
                </section>

                <section class="card">
                    <div class="card-header">
                        <div>
                            <h2>Identitas Otomatis</h2>
                            <p>Dibuat setelah disimpan</p>
                        </div>
                        <x-icon name="barcode" :size="19" />
                    </div>
                    <div class="card-body">
                        <div class="barcode-empty">
                            <x-icon name="barcode" :size="26" />
                            <p>Kode <strong>KMB-xxxx</strong>, barcode unik, dan nomor transaksi <strong>TRX-B-xxxx-xxx</strong> dibuat otomatis oleh server.</p>
                        </div>
                    </div>
                </section>
            </aside>
        </div>
    </form>
@endsection
