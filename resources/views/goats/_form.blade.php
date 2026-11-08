@php
    $isEdit = isset($goat) && $goat;
    $locked = $isEdit && $goat->isSold();
    $origin = old('origin', $goat->origin->value ?? 'peranakan');
@endphp

@include('partials.errors')

<form method="POST" action="{{ $action }}" class="form">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    @if ($locked)
        <div class="inline-alert inline-alert-warning" style="margin-bottom: 18px;">
            <x-icon name="alert-triangle" :size="16" />
            <span>Kambing sudah terjual. Hanya bobot, warna, dan catatan yang dapat diubah agar riwayat transaksi tetap utuh.</span>
        </div>
        <input type="hidden" name="name" value="{{ $goat->name }}">
        <input type="hidden" name="type" value="{{ $goat->type }}">
        <input type="hidden" name="gender" value="{{ $goat->gender->value }}">
        <input type="hidden" name="birth_date" value="{{ $goat->birth_date?->toDateString() }}">
        <input type="hidden" name="entry_date" value="{{ $goat->entry_date?->toDateString() }}">
        <input type="hidden" name="mother_id" value="{{ $goat->mother_id }}">
    @endif

    <div class="settings-layout">
        <div class="settings-main">
            <section class="card">
                <div class="card-header">
                    <div>
                        <h2>Identitas Kambing</h2>
                        <p>Data utama yang tampil pada inventaris dan label barcode.</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="form-grid">
                        <div class="form-group {{ $errors->has('name') ? 'has-error' : '' }}">
                            <label for="name">Nama kambing <span class="req">*</span></label>
                            <input id="name" type="text" name="name" value="{{ old('name', $goat->name ?? '') }}" maxlength="100" required @disabled($locked)>
                            @error('name')<span class="field-error">{{ $message }}</span>@enderror
                        </div>

                        <div class="form-group {{ $errors->has('type') ? 'has-error' : '' }}">
                            <label for="type">Jenis <span class="req">*</span></label>
                            <input id="type" type="text" name="type" list="goat-types" value="{{ old('type', $goat->type ?? '') }}" maxlength="40" required @disabled($locked)>
                            <datalist id="goat-types">
                                <option value="Boer"></option>
                                <option value="Boer Cross"></option>
                                <option value="Etawa"></option>
                                <option value="Jawa"></option>
                                <option value="Kacang"></option>
                            </datalist>
                            @error('type')<span class="field-error">{{ $message }}</span>@enderror
                        </div>

                        <div class="form-group full {{ $errors->has('gender') ? 'has-error' : '' }}">
                            <label>Jenis kelamin <span class="req">*</span></label>
                            <div class="segmented">
                                @foreach (App\Enums\GoatGender::options() as $value => $label)
                                    <label>
                                        <input type="radio" name="gender" value="{{ $value }}" @checked(old('gender', $goat->gender->value ?? 'jantan') === $value) @disabled($locked)>
                                        <span>{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('gender')<span class="field-error">{{ $message }}</span>@enderror
                        </div>

                        <div class="form-group {{ $errors->has('birth_date') ? 'has-error' : '' }}">
                            <label for="birth_date">Tanggal lahir</label>
                            <input id="birth_date" type="date" name="birth_date" value="{{ old('birth_date', isset($goat) && $goat->birth_date ? $goat->birth_date->toDateString() : '') }}" max="{{ now()->toDateString() }}" @disabled($locked)>
                            @error('birth_date')<span class="field-error">{{ $message }}</span>@enderror
                        </div>

                        <div class="form-group {{ $errors->has('weight') ? 'has-error' : '' }}">
                            <label for="weight">Bobot (kg) <span class="req">*</span></label>
                            <div class="input-suffix">
                                <input id="weight" type="number" name="weight" value="{{ old('weight', $goat->weight ?? '') }}" step="0.1" min="0" max="500" required>
                                <span>kg</span>
                            </div>
                            @error('weight')<span class="field-error">{{ $message }}</span>@enderror
                        </div>

                        <div class="form-group {{ $errors->has('color') ? 'has-error' : '' }}">
                            <label for="color">Warna</label>
                            <input id="color" type="text" name="color" value="{{ old('color', $goat->color ?? '') }}" maxlength="40">
                            @error('color')<span class="field-error">{{ $message }}</span>@enderror
                        </div>

                        <div class="form-group {{ $errors->has('entry_date') ? 'has-error' : '' }}">
                            <label for="entry_date">Tanggal masuk <span class="req">*</span></label>
                            <input id="entry_date" type="date" name="entry_date" value="{{ old('entry_date', isset($goat) && $goat->entry_date ? $goat->entry_date->toDateString() : now()->toDateString()) }}" max="{{ now()->toDateString() }}" required @disabled($locked)>
                            @error('entry_date')<span class="field-error">{{ $message }}</span>@enderror
                        </div>
                    </div>
                </div>
            </section>

            <section class="card">
                <div class="card-header">
                    <div>
                        <h2>Asal &amp; Keterangan</h2>
                        <p>Kambing yang ditambahkan manual di halaman ini dicatat sebagai peranakan.</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="inline-alert inline-alert-info" style="margin-bottom: 16px;">
                        <x-icon name="info" :size="16" />
                        <span>Untuk kambing hasil pembelian, gunakan menu <a class="inline-link" href="{{ route('purchases.create') }}">Pembelian</a> agar biaya perolehan dan supplier tercatat.</span>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Asal kambing</label>
                            <input type="text" value="{{ $origin === 'pembelian' ? 'Pembelian' : 'Peranakan' }}" disabled>
                            <input type="hidden" name="origin" value="{{ $origin }}">
                        </div>

                        @if ($origin === 'peranakan')
                            <div class="form-group {{ $errors->has('mother_id') ? 'has-error' : '' }}">
                                <label for="mother_id">Induk (opsional)</label>
                                <select id="mother_id" name="mother_id" @disabled($locked)>
                                    <option value="">Tidak diketahui</option>
                                    @foreach ($mothers as $mother)
                                        <option value="{{ $mother->id }}" @selected((string) old('mother_id', $goat->mother_id ?? '') === (string) $mother->id)>
                                            {{ $mother->code }} — {{ $mother->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('mother_id')<span class="field-error">{{ $message }}</span>@enderror
                            </div>
                        @endif

                        <div class="form-group full">
                            <label for="notes">Catatan</label>
                            <textarea id="notes" name="notes" rows="4" maxlength="1000" placeholder="Kondisi kesehatan, vaksinasi, atau informasi lain">{{ old('notes', $goat->notes ?? '') }}</textarea>
                        </div>
                    </div>
                </div>
            </section>

            <div class="form-actions" style="display: flex; gap: 10px; justify-content: flex-end;">
                <a class="btn btn-outline" href="{{ $isEdit ? route('goats.show', $goat) : route('goats.index') }}">Batal</a>
                <button type="submit" class="btn btn-primary">
                    <x-icon name="check" :size="16" /> {{ $isEdit ? 'Simpan Perubahan' : 'Tambah Kambing' }}
                </button>
            </div>
        </div>

        <aside class="settings-side">
            <section class="card">
                <div class="card-header">
                    <div>
                        <h2>Identitas Sistem</h2>
                        <p>Dibuat otomatis oleh server.</p>
                    </div>
                    <x-icon name="barcode" :size="19" />
                </div>
                <div class="card-body">
                    @if ($isEdit)
                        <div class="barcode-box">
                            <div class="barcode-svg" data-barcode="{{ $goat->barcode }}" data-barcode-height="60"></div>
                        </div>
                        <div class="info-grid" style="margin-top: 16px;">
                            <div class="info-item"><span>Kode</span><strong>{{ $goat->code }}</strong></div>
                            <div class="info-item"><span>Barcode</span><strong class="barcode-text">{{ $goat->barcode }}</strong></div>
                        </div>
                    @else
                        <div class="barcode-empty">
                            <x-icon name="barcode" :size="26" />
                            <p>Kode <strong>KMB-xxxx</strong> dan barcode unik dibuat setelah data disimpan.</p>
                        </div>
                    @endif
                </div>
            </section>
        </aside>
    </div>
</form>
