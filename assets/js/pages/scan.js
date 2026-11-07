/**
 * Kamsya - Halaman Scan Barcode (simulasi)
 */
(function () {
  'use strict';

  const K = (window.Kamsya = window.Kamsya || {});
  const U = K.Utils;
  const UI = K.UI;
  const S = K.Store;

  let state = { resultId: null, notFound: '', scanning: false };
  let scanTimer = null;

  function previewGoat() {
    return state.resultId ? S.getGoat(state.resultId) : null;
  }

  function resultMarkup() {
    if (state.scanning) {
      return `
        <div class="scan-loading">
          <span class="scanner-loader"></span>
          <strong>Memindai barcode...</strong>
          <p>Mencocokkan nomor dengan data inventaris.</p>
        </div>
      `;
    }

    if (state.notFound) {
      return `
        <div class="scan-error">
          <span class="scan-error-icon">${UI.icon('alert-circle', 32)}</span>
          <h3>Barcode tidak ditemukan</h3>
          <p>Nomor <strong>${U.escapeHtml(state.notFound)}</strong> tidak cocok dengan kambing mana pun. Periksa kembali nomor barcode atau daftarkan kambing baru.</p>
          <div class="scan-error-actions">
            <button type="button" class="btn btn-outline btn-sm" data-focus-scan>${UI.icon('scan', 15)} Coba lagi</button>
            <a class="btn btn-primary btn-sm" href="#/kambing?baru=1">${UI.icon('plus', 15)} Tambah Kambing</a>
          </div>
        </div>
      `;
    }

    const goat = previewGoat();
    if (!goat) {
      return `
        <div class="scan-idle">
          <span class="scan-idle-art">${UI.icon('barcode', 46)}</span>
          <h3>Hasil scan akan muncul di sini</h3>
          <p>Masukkan nomor barcode secara manual atau pilih salah satu contoh barcode di samping untuk mencoba simulasi pemindaian.</p>
          <div class="scan-idle-tips">
            <span>${UI.icon('check-circle', 14)} Data kambing & status</span>
            <span>${UI.icon('check-circle', 14)} Asal-usul & induk</span>
            <span>${UI.icon('check-circle', 14)} Riwayat transaksi</span>
          </div>
        </div>
      `;
    }

    const mother = S.getMother(goat);
    const supplier = S.getSupplier(goat.supplierId);
    const purchase = S.getPurchaseForGoat(goat.id);

    return `
      <div class="scan-result-found">
        <div class="scan-result-banner">
          <span class="scan-success-badge">${UI.icon('check-circle', 15)} Barcode ditemukan</span>
          ${UI.statusBadge(goat.status)}
        </div>
        <div class="scan-result-profile">
          ${UI.goatAvatar(goat, 'lg')}
          <div class="scan-result-title">
            <h3>${U.escapeHtml(goat.name)}</h3>
            <span>${U.escapeHtml(goat.id)} · ${U.escapeHtml(goat.type)} · ${U.escapeHtml(goat.gender)}</span>
            <span class="barcode-text">${U.escapeHtml(goat.barcode)}</span>
          </div>
        </div>
        <div class="scan-detail-grid">
          <div><span>Berat</span><strong>${U.formatWeight(goat.weight)}</strong></div>
          <div><span>Usia</span><strong>${U.ageText(goat.birthDate)}</strong></div>
          <div><span>Warna</span><strong>${U.escapeHtml(goat.color || '-')}</strong></div>
          <div><span>Status</span><strong>${goat.status === 'tersedia' ? 'Tersedia' : 'Terjual'}</strong></div>
        </div>
        <div class="scan-origin">
          <div class="scan-origin-head">
            ${UI.originBadge(goat.origin)}
            <span>${goat.origin === 'pembelian' ? 'Kambing hasil pembelian' : 'Kambing hasil peranakan'}</span>
          </div>
          ${goat.origin === 'pembelian'
            ? `<div class="scan-origin-grid">
                <div><span>Dibeli dari</span><strong>${U.escapeHtml(supplier ? supplier.name : '-')}</strong></div>
                <div><span>Tanggal beli</span><strong>${U.formatDate(goat.purchaseDate || (purchase && purchase.date), 'short')}</strong></div>
                <div><span>Harga beli</span><strong>${U.formatRupiah(goat.purchasePrice)}</strong></div>
                <div><span>Total transaksi</span><strong>${U.formatRupiah(purchase ? purchase.total : goat.purchasePrice)}</strong></div>
              </div>`
            : `<div class="scan-origin-grid">
                <div><span>Induk betina</span><strong>${mother ? U.escapeHtml(mother.name) : '-'}</strong></div>
                <div><span>ID induk</span><strong>${U.escapeHtml(goat.motherId || '-')}</strong></div>
                <div><span>Tanggal lahir</span><strong>${U.formatDate(goat.birthDate, 'short')}</strong></div>
                <div><span>Harga beli</span><strong class="muted">Tidak ada</strong></div>
              </div>`}
        </div>
        <div class="scan-result-actions">
          <a class="btn btn-primary" href="#/kambing/${encodeURIComponent(goat.id)}">${UI.icon('eye', 16)} Lihat Detail Kambing</a>
          <a class="btn btn-outline" href="#/cetak?goat=${encodeURIComponent(goat.id)}">${UI.icon('printer', 16)} Cetak Barcode</a>
          ${goat.status === 'tersedia' ? `<button type="button" class="btn btn-outline" data-sell="${U.escapeHtml(goat.id)}">${UI.icon('tag', 16)} Catat Penjualan</button>` : ''}
        </div>
      </div>
    `;
  }

  function historyMarkup() {
    const history = S.getState().scanHistory;
    if (!history.length) {
      return UI.emptyState({ icon: 'clock', title: 'Belum ada riwayat scan', message: 'Riwayat pemindaian barcode akan tampil di sini.' });
    }
    return `
      <div class="scan-history-list">
        ${history.map((entry) => {
          const goat = entry.goatId ? S.getGoat(entry.goatId) : null;
          const date = U.parseDate(entry.at);
          const time = date ? `${U.pad(date.getHours(), 2)}.${U.pad(date.getMinutes(), 2)}` : '-';
          const dateText = date ? `${U.formatDate(entry.at, 'short')} · ${time}` : '-';
          return `
            <button type="button" class="history-item" data-rescan="${U.escapeHtml(entry.barcode)}">
              <span class="history-icon ${goat ? 'history-found' : 'history-missing'}">${UI.icon(goat ? 'check-circle' : 'alert-circle', 16)}</span>
              <span class="history-info">
                <strong class="barcode-text">${U.escapeHtml(entry.barcode)}</strong>
                <small>${goat ? `${U.escapeHtml(goat.name)} · ${U.escapeHtml(goat.id)}` : 'Tidak ditemukan'} · ${U.escapeHtml(dateText)}</small>
              </span>
              ${UI.icon('chevron-right', 16)}
            </button>
          `;
        }).join('')}
      </div>
    `;
  }

  function updateResult() {
    const container = document.getElementById('scan-result');
    if (container) container.innerHTML = resultMarkup();
    const history = document.getElementById('scan-history');
    if (history) history.innerHTML = historyMarkup();
    const status = document.getElementById('scanner-status');
    if (status) {
      if (state.scanning) status.textContent = 'Memindai...';
      else if (state.resultId) status.textContent = 'Barcode berhasil dipindai';
      else if (state.notFound) status.textContent = 'Barcode tidak ditemukan';
      else status.textContent = 'Siap memindai';
    }
  }

  function focusScanInput() {
    const input = document.getElementById('scan-input');
    if (input) {
      input.focus();
      input.select();
    }
  }

  function performScan(rawValue) {
    const input = document.getElementById('scan-input');
    const form = document.getElementById('scan-form');
    const value = String(rawValue == null ? (input ? input.value : '') : rawValue).trim();
    if (!value) {
      if (form) {
        form.classList.add('has-error');
        let error = form.querySelector('.field-error');
        if (!error) {
          error = document.createElement('p');
          error.className = 'field-error';
          form.appendChild(error);
        }
        error.textContent = 'Masukkan nomor barcode terlebih dahulu.';
        if (input) {
          input.setAttribute('aria-invalid', 'true');
          input.focus();
        }
      }
      return;
    }
    if (form) {
      form.classList.remove('has-error');
      const error = form.querySelector('.field-error');
      if (error) error.remove();
    }
    if (input) {
      input.value = value;
      input.removeAttribute('aria-invalid');
    }

    window.clearTimeout(scanTimer);
    state = { resultId: null, notFound: '', scanning: true };
    updateResult();

    scanTimer = window.setTimeout(() => {
      const goat = S.findByBarcode(value);
      S.recordScan(value);
      state = goat
        ? { resultId: goat.id, notFound: '', scanning: false }
        : { resultId: null, notFound: value, scanning: false };
      updateResult();
      if (goat) {
        UI.toast(`Barcode ${value} cocok dengan ${goat.name} (${goat.id}).`, 'success');
      } else {
        UI.toast(`Barcode ${value} tidak ditemukan pada data kambing.`, 'error');
      }
    }, 520);
  }

  K.Pages = K.Pages || {};
  K.Pages.scan = {
    title: 'Scan Barcode',
    nav: '/scan',
    breadcrumb: 'Scan Barcode',

    render(ctx) {
      const stateData = S.getState();
      const samples = stateData.goats
        .filter((goat) => goat.status === 'tersedia')
        .slice(0, 3);
      const lastBarcode = ctx.query && ctx.query.barcode ? ctx.query.barcode : '';

      return `
        ${UI.pageHead({
          eyebrow: 'Inventaris',
          title: 'Scan Barcode',
          subtitle: 'Cari data kambing secara instan melalui barcode atau ID kambing.',
          actions: `<a class="btn btn-outline" href="#/kambing">${UI.icon('package', 16)} Data Kambing</a>`,
        })}

        <div class="scan-layout">
          <section class="card scanner-card">
            <div class="scanner-visual">
              <div class="scanner-frame">
                <span class="scan-corner scan-corner-tl"></span>
                <span class="scan-corner scan-corner-tr"></span>
                <span class="scan-corner scan-corner-bl"></span>
                <span class="scan-corner scan-corner-br"></span>
                <span class="scanner-barcode">${UI.icon('barcode', 52)}</span>
                <span class="scanner-laser"></span>
              </div>
              <span class="scanner-status" id="scanner-status">Siap memindai</span>
            </div>

            <form id="scan-form" class="scan-form" novalidate>
              <label for="scan-input">Nomor Barcode</label>
              <div class="scan-input-row">
                <div class="search-field scan-input-field">
                  ${UI.icon('barcode', 19)}
                  <input id="scan-input" type="text" inputmode="numeric" autocomplete="off" maxlength="24" placeholder="Contoh: 899100001" value="${U.escapeHtml(lastBarcode)}" autofocus aria-label="Nomor barcode kambing">
                </div>
                <button type="submit" class="btn btn-primary">${UI.icon('scan', 17)} Scan</button>
              </div>
              <p class="form-hint">Tekan <kbd>Enter</kbd> untuk memindai. Anda juga dapat memasukkan ID kambing seperti KMB-0001.</p>
            </form>

            <div class="scan-samples">
              <span>Contoh barcode:</span>
              ${samples.map((goat) => `<button type="button" class="sample-chip" data-sample="${U.escapeHtml(goat.barcode)}">${U.escapeHtml(goat.barcode)} <small>${U.escapeHtml(goat.name)}</small></button>`).join('')}
            </div>

            <div class="scanner-note">${UI.icon('info', 15)} Simulasi pemindaian menggunakan input manual. Integrasi kamera atau scanner fisik belum termasuk dalam MVP.</div>
          </section>

          <section class="card result-card">
            <div class="card-header">
              <div>
                <h2>Hasil Pencarian</h2>
                <p>Pratinjau informasi kambing dari barcode</p>
              </div>
              ${UI.icon('scan', 19)}
            </div>
            <div class="card-body" id="scan-result">${resultMarkup()}</div>
          </section>
        </div>

        <section class="card">
          <div class="card-header">
            <div>
              <h2>Riwayat Scan</h2>
              <p>8 pemindaian terakhir pada perangkat ini</p>
            </div>
            <button type="button" class="btn btn-ghost btn-sm" data-clear-history>${UI.icon('trash', 15)} Hapus riwayat</button>
          </div>
          <div class="card-body" id="scan-history">${historyMarkup()}</div>
        </section>
      `;
    },

    mount(ctx) {
      const content = document.getElementById('page-content');
      const form = content.querySelector('#scan-form');
      const input = content.querySelector('#scan-input');
      const result = content.querySelector('#scan-result');
      const history = content.querySelector('#scan-history');

      if (input) {
        input.addEventListener('input', () => {
          if (form) {
            form.classList.remove('has-error');
            const error = form.querySelector('.field-error');
            if (error) error.remove();
          }
          input.removeAttribute('aria-invalid');
        });
      }

      if (form) {
        form.addEventListener('submit', (event) => {
          event.preventDefault();
          performScan();
        });
      }

      content.querySelectorAll('[data-sample]').forEach((button) => {
        button.addEventListener('click', () => performScan(button.dataset.sample));
      });

      if (result) {
        result.addEventListener('click', (event) => {
          if (event.target.closest('[data-focus-scan]')) {
            focusScanInput();
            return;
          }
          const sellButton = event.target.closest('[data-sell]');
          if (sellButton && K.Pages.sales && K.Pages.sales.openForm) {
            K.Pages.sales.openForm(sellButton.dataset.sell);
          }
        });
      }

      if (history) {
        history.addEventListener('click', (event) => {
          const rescan = event.target.closest('[data-rescan]');
          if (rescan) {
            performScan(rescan.dataset.rescan);
          }
        });
      }

      const clearButton = content.querySelector('[data-clear-history]');
      if (clearButton) {
        clearButton.addEventListener('click', () => {
          if (!S.getState().scanHistory.length) {
            UI.toast('Riwayat scan sudah kosong.', 'info');
            return;
          }
          UI.confirm({
            title: 'Hapus riwayat scan?',
            message: 'Seluruh riwayat pemindaian barcode akan dihapus dari perangkat ini.',
            confirmText: 'Ya, Hapus',
            onConfirm() {
              S.clearScanHistory();
              updateResult();
              UI.toast('Riwayat scan berhasil dihapus.', 'success');
            },
          });
        });
      }

      if (ctx.query && ctx.query.barcode) {
        window.setTimeout(() => performScan(ctx.query.barcode), 260);
      }
    },

    destroy() {
      window.clearTimeout(scanTimer);
      scanTimer = null;
    },
  };
})();
