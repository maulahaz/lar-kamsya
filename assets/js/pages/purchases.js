/**
 * Kamsya - Halaman Transaksi Pembelian
 */
(function () {
  'use strict';

  const K = (window.Kamsya = window.Kamsya || {});
  const U = K.Utils;
  const UI = K.UI;
  const S = K.Store;
  const B = K.Barcode;

  const PER_PAGE = 8;
  const TYPES = ['Boer', 'Etawa', 'Jawa', 'Boer Cross', 'Kacang', 'Saanen', 'Lainnya'];

  let pageState = { q: '', supplier: '', page: 1 };
  let searchDebounce = null;

  function filteredPurchases() {
    const query = pageState.q.toLowerCase();
    return S.getState().purchases
      .filter((purchase) => {
        if (pageState.supplier && purchase.supplierId !== pageState.supplier) return false;
        if (!query) return true;
        const supplier = S.getSupplier(purchase.supplierId);
        const goat = S.getGoat(purchase.goatId);
        const haystack = `${purchase.id} ${supplier ? supplier.name : ''} ${goat ? `${goat.name} ${goat.barcode} ${goat.id}` : ''}`.toLowerCase();
        return haystack.includes(query);
      })
      .sort((a, b) => {
        const diff = String(b.date).localeCompare(String(a.date));
        return diff !== 0 ? diff : String(b.id).localeCompare(String(a.id));
      });
  }

  function tableMarkup() {
    const list = filteredPurchases();
    const total = list.length;
    const pages = Math.max(1, Math.ceil(total / PER_PAGE));
    if (pageState.page > pages) pageState.page = pages;
    const start = (pageState.page - 1) * PER_PAGE;
    const visible = list.slice(start, start + PER_PAGE);

    const rows = visible.length
      ? visible.map((purchase) => {
        const supplier = S.getSupplier(purchase.supplierId);
        const goat = S.getGoat(purchase.goatId);
        return `
          <tr>
            <td><span class="barcode-text">${U.escapeHtml(purchase.id)}</span></td>
            <td class="nowrap">${U.formatDate(purchase.date, 'short')}</td>
            <td>
              <div class="cell-person">
                ${UI.personAvatar(supplier ? supplier.name : '-', 'green', 'xs')}
                <span class="cell-stack">
                  <strong>${U.escapeHtml(supplier ? supplier.name : '-')}</strong>
                  <span class="cell-muted">${U.escapeHtml(supplier ? supplier.phone : '-')}</span>
                </span>
              </div>
            </td>
            <td>
              <div class="cell-stack">
                <a class="cell-title" href="#/kambing/${encodeURIComponent(purchase.goatId)}">${U.escapeHtml(goat ? goat.name : 'Kambing terhapus')}</a>
                <span class="cell-muted">${U.escapeHtml(goat ? goat.id : '-')}</span>
              </div>
            </td>
            <td><span class="barcode-text">${U.escapeHtml(goat ? goat.barcode : '-')}</span></td>
            <td class="nowrap">${U.formatRupiah(purchase.price)}</td>
            <td class="nowrap">${U.formatRupiah(purchase.additionalCost)}</td>
            <td class="nowrap"><strong>${U.formatRupiah(purchase.total)}</strong></td>
            <td>
              <div class="cell-actions">
                <button type="button" class="action-btn" data-detail="${U.escapeHtml(purchase.id)}" title="Lihat detail transaksi" aria-label="Detail ${U.escapeHtml(purchase.id)}">${UI.icon('eye', 16)}</button>
                <a class="action-btn" href="#/kambing/${encodeURIComponent(purchase.goatId)}" title="Buka data kambing" aria-label="Buka data kambing">${UI.icon('goat', 16)}</a>
                <a class="action-btn" href="#/cetak?goat=${encodeURIComponent(purchase.goatId)}" title="Cetak barcode" aria-label="Cetak barcode">${UI.icon('printer', 16)}</a>
              </div>
            </td>
          </tr>
        `;
      }).join('')
      : `<tr><td colspan="9">${UI.emptyState({
        icon: 'cart',
        title: total ? 'Transaksi tidak ditemukan' : 'Belum ada transaksi pembelian',
        message: total ? 'Coba ubah kata kunci atau reset filter.' : 'Catat pembelian kambing pertama Anda untuk menambah stok inventaris.',
        action: `<button type="button" class="btn btn-primary btn-sm" data-open-purchase>${UI.icon('plus', 15)} Catat Pembelian</button>`,
      })}</td></tr>`;

    return `
      <div class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>No. Transaksi</th>
              <th>Tanggal</th>
              <th>Supplier</th>
              <th>Kambing</th>
              <th>Barcode</th>
              <th>Harga Beli</th>
              <th>Biaya Tambahan</th>
              <th>Total</th>
              <th class="th-actions">Aksi</th>
            </tr>
          </thead>
          <tbody>${rows}</tbody>
        </table>
      </div>
      ${UI.pagination({ page: pageState.page, perPage: PER_PAGE, total })}
    `;
  }

  function showDetail(purchaseId) {
    const purchase = S.getState().purchases.find((item) => item.id === purchaseId);
    if (!purchase) {
      UI.toast('Transaksi pembelian tidak ditemukan.', 'error');
      return;
    }
    const supplier = S.getSupplier(purchase.supplierId);
    const goat = S.getGoat(purchase.goatId);
    UI.openModal({
      title: 'Detail Pembelian',
      subtitle: purchase.id,
      size: 'md',
      body: `
        <div class="modal-summary">
          <div class="summary-identity">
            <span class="summary-icon summary-purchase">${UI.icon('cart', 22)}</span>
            <div>
              <strong>${U.escapeHtml(purchase.id)}</strong>
              <span>${U.formatDate(purchase.date)} · Pembelian kambing</span>
            </div>
          </div>
          <span class="badge badge-info">${UI.icon('check-circle', 13)} Tercatat</span>
        </div>
        <div class="info-grid modal-info-grid">
          <div class="info-item"><span>Supplier</span><strong>${U.escapeHtml(supplier ? supplier.name : '-')}</strong></div>
          <div class="info-item"><span>Telepon supplier</span><strong>${U.escapeHtml(supplier ? supplier.phone : '-')}</strong></div>
          <div class="info-item"><span>Kambing</span><strong>${U.escapeHtml(goat ? goat.name : 'Kambing terhapus')}</strong></div>
          <div class="info-item"><span>Barcode</span><strong class="barcode-text">${U.escapeHtml(goat ? goat.barcode : '-')}</strong></div>
          <div class="info-item"><span>Harga beli</span><strong>${U.formatRupiah(purchase.price)}</strong></div>
          <div class="info-item"><span>Biaya tambahan</span><strong>${U.formatRupiah(purchase.additionalCost)}</strong></div>
          <div class="info-item info-highlight"><span>Total pembelian</span><strong>${U.formatRupiah(purchase.total)}</strong></div>
          <div class="info-item"><span>Asal kambing</span><strong>${goat ? UI.originBadge(goat.origin) : '-'}</strong></div>
        </div>
        <div class="modal-note-block">
          <span>Catatan</span>
          <p>${purchase.notes ? U.escapeHtml(purchase.notes) : '<span class="muted">Tidak ada catatan.</span>'}</p>
        </div>
        ${goat ? `<a class="btn btn-outline btn-block" href="#/kambing/${encodeURIComponent(goat.id)}" data-modal-close>${UI.icon('goat', 16)} Buka Data Kambing ${U.escapeHtml(goat.name)}</a>` : ''}
      `,
      footer: `<button type="button" class="btn btn-outline" data-modal-close>Tutup</button>`,
    });
  }

  function openForm() {
    const suppliers = S.getState().suppliers.slice().sort((a, b) => a.name.localeCompare(b.name));
    const barcode = S.peekNextBarcode();
    const typeOptions = Array.from(new Set(TYPES.concat(S.getState().goats.map((goat) => goat.type))));

    UI.openModal({
      title: 'Catat Pembelian Kambing',
      subtitle: 'Kambing yang disimpan akan otomatis masuk ke inventaris.',
      size: 'lg',
      body: `
        <form id="purchase-form" class="form" novalidate>
          <div class="barcode-preview">
            <div class="barcode-preview-meta">
              <span class="preview-label">Barcode otomatis</span>
              <strong class="barcode-text">${U.escapeHtml(barcode)}</strong>
              <small>Barcode unik dibuat saat transaksi disimpan.</small>
            </div>
            <div class="barcode-preview-svg">${B.svg(barcode, { height: 42, moduleWidth: 1.5 })}</div>
          </div>

          <div class="form-grid">
            <div class="form-group">
              <label for="purchase-date">Tanggal Pembelian <span class="req">*</span></label>
              <input id="purchase-date" type="date" name="date" value="${U.todayISO()}">
            </div>
            <div class="form-group">
              <label for="purchase-supplier">Supplier / Penjual <span class="req">*</span></label>
              <select id="purchase-supplier" name="supplierId">
                <option value="">Pilih supplier</option>
                ${suppliers.map((supplier) => `<option value="${U.escapeHtml(supplier.id)}">${U.escapeHtml(supplier.name)} · ${U.escapeHtml(supplier.phone)}</option>`).join('')}
              </select>
              ${suppliers.length ? '' : '<p class="form-hint form-hint-warning">Belum ada supplier. <a class="inline-link" href="#/supplier">Tambahkan supplier</a> terlebih dahulu.</p>'}
            </div>

            <div class="form-group full">
              <label for="purchase-goat-name">Nama / Label Kambing <span class="req">*</span></label>
              <input id="purchase-goat-name" type="text" name="goatName" maxlength="60" placeholder="Contoh: Si Jago" autofocus>
            </div>

            <div class="form-group">
              <label for="purchase-type">Jenis Kambing <span class="req">*</span></label>
              <select id="purchase-type" name="type">
                <option value="">Pilih jenis</option>
                ${UI.options(typeOptions, '', '')}
              </select>
            </div>
            <div class="form-group">
              <label>Jenis Kelamin <span class="req">*</span></label>
              <div class="segmented" role="radiogroup" aria-label="Jenis kelamin">
                <label><input type="radio" name="gender" value="Jantan" checked><span>♂ Jantan</span></label>
                <label><input type="radio" name="gender" value="Betina"><span>♀ Betina</span></label>
              </div>
            </div>

            <div class="form-group">
              <label for="purchase-birth">Tanggal Lahir</label>
              <input id="purchase-birth" type="date" name="birthDate">
            </div>
            <div class="form-group">
              <label for="purchase-weight">Berat (kg) <span class="req">*</span></label>
              <div class="input-suffix">
                <input id="purchase-weight" type="number" name="weight" min="0.1" max="500" step="0.1" inputmode="decimal" placeholder="35">
                <span>kg</span>
              </div>
            </div>
            <div class="form-group">
              <label for="purchase-color">Warna</label>
              <input id="purchase-color" type="text" name="color" maxlength="40" placeholder="Contoh: Cokelat">
            </div>
            <div class="form-group">
              <label for="purchase-price">Harga Beli <span class="req">*</span></label>
              ${UI.moneyField('price', '', { required: true })}
            </div>
            <div class="form-group">
              <label for="purchase-additional">Biaya Tambahan</label>
              ${UI.moneyField('additionalCost', '', { placeholder: 'Opsional' })}
              <p class="form-hint">Transportasi, karantina, atau biaya lain.</p>
            </div>

            <div class="form-group full">
              <label for="purchase-notes">Catatan</label>
              <textarea id="purchase-notes" name="notes" rows="2" maxlength="400" placeholder="Catatan transaksi pembelian..."></textarea>
            </div>
          </div>

          <div class="total-preview">
            <span>Total pembelian</span>
            <strong id="purchase-total-preview">Rp0</strong>
          </div>
        </form>
      `,
      footer: `
        <button type="button" class="btn btn-outline" data-modal-close>Batal</button>
        <button type="submit" form="purchase-form" class="btn btn-primary" data-save>${UI.icon('check', 16)} Simpan Pembelian</button>
      `,
      onMount(element, close) {
        const form = element.querySelector('#purchase-form');
        const saveButton = element.querySelector('[data-save]');
        UI.Forms.clearOnInput(form);

        const updateTotal = () => {
          const price = UI.Forms.number(form, 'price');
          const additional = UI.Forms.number(form, 'additionalCost');
          const preview = element.querySelector('#purchase-total-preview');
          if (preview) preview.textContent = U.formatRupiah(price + additional);
        };
        form.querySelectorAll('input[name="price"], input[name="additionalCost"]').forEach((input) => {
          input.addEventListener('input', updateTotal);
        });
        updateTotal();

        form.addEventListener('submit', (event) => {
          event.preventDefault();
          UI.Forms.clearErrors(form);
          const date = UI.Forms.value(form, 'date');
          const supplierId = UI.Forms.value(form, 'supplierId');
          const goatName = UI.Forms.value(form, 'goatName');
          const type = UI.Forms.value(form, 'type');
          const gender = UI.Forms.value(form, 'gender');
          const birthDate = UI.Forms.value(form, 'birthDate');
          const weight = UI.Forms.number(form, 'weight');
          const color = UI.Forms.value(form, 'color');
          const price = UI.Forms.number(form, 'price');
          const additionalCost = UI.Forms.number(form, 'additionalCost');
          const notes = UI.Forms.value(form, 'notes');
          const errors = {};

          if (!date) errors.date = 'Tanggal pembelian wajib diisi.';
          if (date && date > U.todayISO()) errors.date = 'Tanggal pembelian tidak boleh di masa depan.';
          if (!supplierId) errors.supplierId = 'Pilih supplier / penjual.';
          if (!goatName) errors.goatName = 'Nama kambing wajib diisi.';
          if (!type) errors.type = 'Pilih jenis kambing.';
          if (!gender) errors.gender = 'Pilih jenis kelamin.';
          if (!weight || weight <= 0) errors.weight = 'Berat harus lebih dari 0 kg.';
          if (weight > 500) errors.weight = 'Berat maksimal 500 kg.';
          if (birthDate && birthDate > U.todayISO()) errors.birthDate = 'Tanggal lahir tidak boleh di masa depan.';
          if (birthDate && date && birthDate > date) errors.birthDate = 'Tanggal lahir tidak boleh setelah tanggal pembelian.';
          if (!price || price <= 0) errors.price = 'Harga beli harus lebih dari Rp0.';
          if (additionalCost < 0) errors.additionalCost = 'Biaya tambahan tidak boleh negatif.';

          if (Object.keys(errors).length) {
            UI.Forms.showErrors(form, errors);
            return;
          }

          const restore = UI.Forms.setLoading(saveButton, 'Menyimpan pembelian...');
          window.setTimeout(() => {
            try {
              const result = S.createPurchase({
                date,
                supplierId,
                price,
                additionalCost,
                notes,
                goat: { name: goatName, type, gender, birthDate: birthDate || null, weight, color, notes },
              });
              UI.toast(`${result.purchase.id} tersimpan. ${result.goat.name} masuk inventaris dengan barcode ${result.goat.barcode}.`, 'success');
              close();
              if (K.Router) K.Router.refresh();
            } catch (error) {
              restore();
              UI.toast(error && error.message ? error.message : 'Gagal menyimpan pembelian.', 'error');
            }
          }, 180);
        });
      },
    });
  }

  function bindPage() {
    const content = document.getElementById('page-content');
    const container = content.querySelector('#purchases-table-container');
    const searchInput = content.querySelector('#purchase-search');
    const supplierFilter = content.querySelector('#purchase-filter-supplier');

    const update = () => {
      if (container) container.innerHTML = tableMarkup();
    };

    if (searchInput) {
      searchDebounce = U.debounce(() => {
        pageState.q = searchInput.value.trim();
        pageState.page = 1;
        update();
      }, 250);
      searchInput.addEventListener('input', searchDebounce);
    }
    if (supplierFilter) {
      supplierFilter.addEventListener('change', () => {
        pageState.supplier = supplierFilter.value;
        pageState.page = 1;
        update();
      });
    }

    content.querySelectorAll('[data-open-purchase], [data-reset-filter]').forEach((button) => {
      button.addEventListener('click', () => {
        if (button.hasAttribute('data-reset-filter')) {
          pageState = { q: '', supplier: '', page: 1 };
          if (searchInput) searchInput.value = '';
          if (supplierFilter) supplierFilter.value = '';
          update();
        } else {
          openForm();
        }
      });
    });

    if (container) {
      container.addEventListener('click', (event) => {
        const pageButton = event.target.closest('[data-page]');
        if (pageButton && !pageButton.disabled) {
          pageState.page = Number(pageButton.dataset.page) || 1;
          update();
          return;
        }
        const detailButton = event.target.closest('[data-detail]');
        if (detailButton) {
          showDetail(detailButton.dataset.detail);
          return;
        }
        if (event.target.closest('[data-open-purchase]')) openForm();
        if (event.target.closest('[data-reset-filter]')) {
          pageState = { q: '', supplier: '', page: 1 };
          if (searchInput) searchInput.value = '';
          if (supplierFilter) supplierFilter.value = '';
          update();
        }
      });
    }
  }

  K.Pages = K.Pages || {};
  K.Pages.purchases = {
    title: 'Pembelian',
    nav: '/pembelian',
    breadcrumb: 'Pembelian',

    render(ctx) {
      const stats = S.getStats();
      const suppliers = S.getState().suppliers.slice().sort((a, b) => a.name.localeCompare(b.name));

      return `
        ${UI.pageHead({
          eyebrow: 'Transaksi',
          title: 'Transaksi Pembelian',
          subtitle: 'Catat kambing masuk, harga beli, biaya tambahan, dan supplier.',
          actions: `<button type="button" class="btn btn-primary" data-open-purchase>${UI.icon('plus', 16)} Catat Pembelian</button>`,
        })}

        <section class="metric-grid" aria-label="Ringkasan pembelian">
          <div class="metric-card">
            <span class="metric-icon metric-green">${UI.icon('cart', 19)}</span>
            <div><span>Total transaksi</span><strong>${stats.totalPurchaseCount}</strong></div>
          </div>
          <div class="metric-card">
            <span class="metric-icon metric-blue">${UI.icon('goat', 19)}</span>
            <div><span>Kambing dibeli</span><strong>${stats.totalPurchaseCount} ekor</strong></div>
          </div>
          <div class="metric-card">
            <span class="metric-icon metric-amber">${UI.icon('wallet', 19)}</span>
            <div><span>Total pengeluaran</span><strong>${U.formatRupiah(stats.totalPurchases)}</strong></div>
          </div>
          <div class="metric-card">
            <span class="metric-icon metric-purple">${UI.icon('file-text', 19)}</span>
            <div><span>Biaya tambahan</span><strong>${U.formatRupiah(stats.totalAdditional)}</strong></div>
          </div>
        </section>

        <section class="card">
          <div class="card-toolbar">
            <div class="search-field">
              ${UI.icon('search', 17)}
              <input id="purchase-search" type="search" placeholder="Cari nomor transaksi, supplier, kambing, atau barcode..." value="${U.escapeHtml(pageState.q)}" aria-label="Cari transaksi pembelian">
            </div>
            <div class="filter-group">
              <select id="purchase-filter-supplier" class="filter-select" aria-label="Filter supplier">
                ${UI.options(suppliers.map((supplier) => ({ value: supplier.id, label: supplier.name })), pageState.supplier, 'Semua supplier')}
              </select>
              <button type="button" class="btn btn-ghost btn-sm" data-reset-filter>${UI.icon('refresh', 15)} Reset</button>
            </div>
          </div>
          <div id="purchases-table-container">${tableMarkup()}</div>
        </section>
      `;
    },

    mount(ctx) {
      bindPage();
      if (ctx.query && ctx.query.baru === '1') {
        window.history.replaceState(null, '', '#/pembelian');
        window.setTimeout(() => openForm(), 60);
      }
    },

    destroy() {
      if (searchDebounce && searchDebounce.cancel) searchDebounce.cancel();
    },

    openForm,
  };
})();
