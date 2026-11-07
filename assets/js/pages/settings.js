/**
 * Kamsya - Halaman Pengaturan
 */
(function () {
  'use strict';

  const K = (window.Kamsya = window.Kamsya || {});
  const U = K.Utils;
  const UI = K.UI;
  const S = K.Store;
  const B = K.Barcode;

  function sampleBarcode(prefix) {
    const counters = S.getState().counters;
    const value = String(prefix || '8991').replace(/\D/g, '').slice(0, 4) || '8991';
    return `${value}${U.pad((Number(counters.barcode) || 0) + 1, 5)}`;
  }

  K.Pages = K.Pages || {};
  K.Pages.settings = {
    title: 'Pengaturan',
    nav: '/pengaturan',
    breadcrumb: 'Pengaturan',

    render() {
      const state = S.getState();
      const settings = state.settings;
      const barcodeSample = sampleBarcode(settings.barcodePrefix);
      const persistent = S.isPersistent();

      return `
        ${UI.pageHead({
          eyebrow: 'Pengaturan',
          title: 'Pengaturan Aplikasi',
          subtitle: 'Atur profil usaha, preferensi barcode, dan kelola data demo.',
          actions: `<button type="submit" form="settings-form" class="btn btn-primary">${UI.icon('check', 16)} Simpan Pengaturan</button>`,
        })}

        <div class="settings-layout">
          <div class="settings-main">
            <form id="settings-form" class="form" novalidate>
              <section class="card">
                <div class="card-header">
                  <div>
                    <h2>Profil Usaha</h2>
                    <p>Informasi ini ditampilkan pada label barcode dan laporan.</p>
                  </div>
                  ${UI.icon('user', 19)}
                </div>
                <div class="card-body">
                  <div class="form-grid">
                    <div class="form-group">
                      <label for="settings-farm">Nama Usaha / Peternakan <span class="req">*</span></label>
                      <input id="settings-farm" type="text" name="farmName" maxlength="80" value="${U.escapeHtml(settings.farmName)}" placeholder="Contoh: Kamsya Farm">
                    </div>
                    <div class="form-group">
                      <label for="settings-owner">Nama Pemilik / Admin <span class="req">*</span></label>
                      <input id="settings-owner" type="text" name="ownerName" maxlength="80" value="${U.escapeHtml(settings.ownerName)}" placeholder="Nama pengelola">
                    </div>
                    <div class="form-group">
                      <label for="settings-phone">Nomor Telepon</label>
                      <div class="input-icon">
                        ${UI.icon('phone', 16)}
                        <input id="settings-phone" type="tel" name="phone" maxlength="30" value="${U.escapeHtml(settings.phone)}" placeholder="0812-9000-1234">
                      </div>
                    </div>
                    <div class="form-group">
                      <label for="settings-address">Alamat Usaha</label>
                      <div class="input-icon">
                        ${UI.icon('map-pin', 16)}
                        <input id="settings-address" type="text" name="address" maxlength="180" value="${U.escapeHtml(settings.address)}" placeholder="Alamat lengkap peternakan">
                      </div>
                    </div>
                  </div>
                </div>
              </section>

              <section class="card">
                <div class="card-header">
                  <div>
                    <h2>Barcode & Stok</h2>
                    <p>Preferensi pembuatan barcode otomatis dan peringatan stok.</p>
                  </div>
                  ${UI.icon('barcode', 19)}
                </div>
                <div class="card-body">
                  <div class="form-grid">
                    <div class="form-group">
                      <label for="settings-prefix">Prefix Barcode <span class="req">*</span></label>
                      <input id="settings-prefix" type="text" name="barcodePrefix" inputmode="numeric" maxlength="4" value="${U.escapeHtml(settings.barcodePrefix)}" placeholder="8991">
                      <p class="form-hint">4 digit angka. Barcode baru dibentuk dari prefix + nomor urut 5 digit.</p>
                    </div>
                    <div class="form-group">
                      <label for="settings-lowstock">Batas Peringatan Stok</label>
                      <div class="input-suffix">
                        <input id="settings-lowstock" type="number" name="lowStockLimit" min="0" max="999" step="1" value="${U.escapeHtml(settings.lowStockLimit)}">
                        <span>ekor</span>
                      </div>
                      <p class="form-hint">Dashboard menampilkan peringatan jika stok tersedia mencapai batas ini.</p>
                    </div>
                  </div>
                  <div class="settings-preview">
                    <div class="settings-preview-label">
                      <span>Contoh barcode berikutnya</span>
                      <strong class="barcode-text" id="barcode-sample-text">${U.escapeHtml(barcodeSample)}</strong>
                    </div>
                    <div class="settings-preview-svg" id="barcode-sample">${B.svg(barcodeSample, { height: 46, moduleWidth: 1.6 })}</div>
                  </div>
                </div>
              </section>
            </form>

            <section class="card">
              <div class="card-header">
                <div>
                  <h2>Cadangan & Data Demo</h2>
                  <p>Ekspor, impor, atau reset seluruh data aplikasi.</p>
                </div>
                ${UI.icon('download', 19)}
              </div>
              <div class="card-body">
                <div class="data-actions">
                  <div class="data-action">
                    <span class="data-action-icon metric-green">${UI.icon('download', 19)}</span>
                    <div><strong>Ekspor Data</strong><span>Unduh seluruh data sebagai file JSON.</span></div>
                    <button type="button" class="btn btn-outline btn-sm" data-export>${UI.icon('download', 15)} Ekspor</button>
                  </div>
                  <div class="data-action">
                    <span class="data-action-icon metric-blue">${UI.icon('upload', 19)}</span>
                    <div><strong>Impor Data</strong><span>Pulihkan data dari file backup Kamsya.</span></div>
                    <button type="button" class="btn btn-outline btn-sm" data-import>${UI.icon('upload', 15)} Impor</button>
                    <input type="file" id="import-file" accept=".json,application/json" hidden>
                  </div>
                  <div class="data-action data-action-danger">
                    <span class="data-action-icon metric-red">${UI.icon('refresh', 19)}</span>
                    <div><strong>Reset Data Demo</strong><span>Kembalikan seluruh data ke kondisi awal.</span></div>
                    <button type="button" class="btn btn-danger-soft btn-sm" data-reset>${UI.icon('refresh', 15)} Reset</button>
                  </div>
                </div>
                ${persistent ? '' : `<div class="inline-alert inline-alert-warning">${UI.icon('alert-triangle', 16)}<span>Penyimpanan lokal tidak tersedia. Data akan hilang saat halaman dimuat ulang.</span></div>`}
              </div>
            </section>
          </div>

          <aside class="settings-side">
            <section class="card">
              <div class="card-header">
                <div>
                  <h2>Informasi Aplikasi</h2>
                  <p>Kamsya MVP Phase 1</p>
                </div>
                <span class="brand-mark brand-mark-sm">🐐</span>
              </div>
              <div class="card-body">
                <div class="app-info-list">
                  <div><span>Versi</span><strong>1.0.0 · MVP</strong></div>
                  <div><span>Mode</span><strong>Prototype Frontend</strong></div>
                  <div><span>Penyimpanan</span><strong>${persistent ? 'localStorage aktif' : 'Memori sementara'}</strong></div>
                  <div><span>Data kambing</span><strong>${state.goats.length} ekor</strong></div>
                  <div><span>Transaksi</span><strong>${state.purchases.length + state.sales.length} transaksi</strong></div>
                  <div><span>Supplier / Customer</span><strong>${state.suppliers.length} / ${state.customers.length}</strong></div>
                </div>
                <div class="inline-alert inline-alert-info app-info-note">
                  ${UI.icon('info', 16)}
                  <span>Prototype ini belum terhubung ke backend Laravel atau database MySQL. Seluruh data disimpan di perangkat Anda.</span>
                </div>
              </div>
            </section>

            <section class="card">
              <div class="card-header">
                <div>
                  <h2>Alur Kerja Kamsya</h2>
                  <p>Fondasi utama aplikasi</p>
                </div>
                ${UI.icon('sparkles', 19)}
              </div>
              <div class="card-body">
                <ol class="workflow-list">
                  <li><span>1</span><div><strong>Kambing masuk</strong><small>Pembelian atau peranakan</small></div></li>
                  <li><span>2</span><div><strong>Barcode otomatis</strong><small>Identitas permanen kambing</small></div></li>
                  <li><span>3</span><div><strong>Tercatat di inventaris</strong><small>Stok dan asal-usul terpusat</small></div></li>
                  <li><span>4</span><div><strong>Barcode dipindai</strong><small>Riwayat langsung terlihat</small></div></li>
                  <li><span>5</span><div><strong>Kambing dijual</strong><small>Keuntungan tercatat</small></div></li>
                </ol>
              </div>
            </section>
          </aside>
        </div>
      `;
    },

    mount() {
      const content = document.getElementById('page-content');
      const form = content.querySelector('#settings-form');
      const saveButton = content.querySelector('[form="settings-form"]');
      const prefixInput = content.querySelector('#settings-prefix');
      const sampleText = content.querySelector('#barcode-sample-text');
      const sampleSvg = content.querySelector('#barcode-sample');
      const importInput = content.querySelector('#import-file');

      const updateSample = () => {
        const sample = sampleBarcode(prefixInput ? prefixInput.value : '8991');
        if (sampleText) sampleText.textContent = sample;
        if (sampleSvg) sampleSvg.innerHTML = B.svg(sample, { height: 46, moduleWidth: 1.6 });
      };
      if (prefixInput) prefixInput.addEventListener('input', updateSample);

      form.querySelectorAll('input').forEach((input) => input.addEventListener('input', () => {
        const group = input.closest('.form-group');
        if (group) {
          group.classList.remove('has-error');
          const error = group.querySelector('.field-error');
          if (error) error.remove();
        }
      }));

      form.addEventListener('submit', (event) => {
        event.preventDefault();
        UI.Forms.clearErrors(form);
        const farmName = UI.Forms.value(form, 'farmName');
        const ownerName = UI.Forms.value(form, 'ownerName');
        const phone = UI.Forms.value(form, 'phone');
        const address = UI.Forms.value(form, 'address');
        const barcodePrefix = UI.Forms.value(form, 'barcodePrefix');
        const lowStockLimit = UI.Forms.number(form, 'lowStockLimit');
        const errors = {};

        if (!farmName) errors.farmName = 'Nama usaha wajib diisi.';
        if (!ownerName) errors.ownerName = 'Nama pemilik wajib diisi.';
        if (phone && !/^[0-9+()\-\s]{6,30}$/.test(phone)) errors.phone = 'Format nomor telepon tidak valid.';
        if (!/^\d{4}$/.test(barcodePrefix)) errors.barcodePrefix = 'Prefix barcode harus terdiri dari 4 digit angka.';

        if (Object.keys(errors).length) {
          UI.Forms.showErrors(form, errors);
          return;
        }

        const restore = UI.Forms.setLoading(saveButton, 'Menyimpan...');
        window.setTimeout(() => {
          try {
            S.updateSettings({ farmName, ownerName, phone, address, barcodePrefix, lowStockLimit });
            UI.toast('Pengaturan berhasil disimpan.', 'success');
            if (K.App && K.App.refreshShell) K.App.refreshShell();
            if (K.Router) K.Router.refresh();
          } catch (error) {
            restore();
            UI.toast(error && error.message ? error.message : 'Gagal menyimpan pengaturan.', 'error');
          }
        }, 180);
      });

      const exportButton = content.querySelector('[data-export]');
      if (exportButton) {
        exportButton.addEventListener('click', () => {
          const filename = `kamsya-backup-${U.todayISO()}.json`;
          U.downloadFile(filename, S.exportJSON());
          UI.toast(`Data berhasil diekspor ke ${filename}.`, 'success');
        });
      }

      const importButton = content.querySelector('[data-import]');
      if (importButton && importInput) {
        importButton.addEventListener('click', () => importInput.click());
        importInput.addEventListener('change', async () => {
          const file = importInput.files && importInput.files[0];
          if (!file) return;
          try {
            const text = await U.readFileAsText(file);
            UI.confirm({
              title: 'Impor data dari backup?',
              message: `File "${file.name}" akan mengganti seluruh data saat ini.`,
              detail: 'Pastikan file berasal dari Kamsya. Tindakan ini tidak dapat dibatalkan.',
              confirmText: 'Ya, Impor',
              tone: 'warning',
              onConfirm() {
                S.importJSON(text);
                UI.toast('Data berhasil diimpor dari backup.', 'success');
                if (K.App && K.App.refreshShell) K.App.refreshShell();
                if (K.Router) K.Router.refresh();
              },
            });
          } catch (error) {
            UI.toast(error && error.message ? error.message : 'Gagal membaca file backup.', 'error');
          } finally {
            importInput.value = '';
          }
        });
      }

      const resetButton = content.querySelector('[data-reset]');
      if (resetButton) {
        resetButton.addEventListener('click', () => {
          UI.confirm({
            title: 'Reset data demo?',
            message: 'Seluruh perubahan yang Anda buat akan dihapus dan data dikembalikan ke kondisi awal.',
            detail: 'Data kambing, transaksi, supplier, customer, pengaturan, dan riwayat scan akan direset.',
            confirmText: 'Ya, Reset Data',
            onConfirm() {
              S.reset();
              UI.toast('Data demo berhasil direset ke kondisi awal.', 'success');
              if (K.App && K.App.refreshShell) K.App.refreshShell();
              if (K.Router) K.Router.navigate('/dashboard');
            },
          });
        });
      }
    },
  };
})();
