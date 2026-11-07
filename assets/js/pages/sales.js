/**
 * Kamsya - Halaman Transaksi Penjualan
 */
(function () {
  'use strict';

  const K = (window.Kamsya = window.Kamsya || {});
  const U = K.Utils;
  const UI = K.UI;
  const S = K.Store;

  const PER_PAGE = 8;
  let pageState = { q: '', customer: '', page: 1 };
  let searchDebounce = null;

  function filteredSales() {
    const query = pageState.q.toLowerCase();
    return S.getState().sales
      .filter((sale) => {
        if (pageState.customer && sale.customerId !== pageState.customer) return false;
        if (!query) return true;
        const customer = S.getCustomer(sale.customerId);
        const goat = S.getGoat(sale.goatId);
        const haystack = `${sale.id} ${customer ? customer.name : ''} ${goat ? `${goat.name} ${goat.barcode} ${goat.id}` : ''}`.toLowerCase();
        return haystack.includes(query);
      })
      .sort((a, b) => {
        const diff = String(b.date).localeCompare(String(a.date));
        return diff !== 0 ? diff : String(b.id).localeCompare(String(a.id));
      });
  }

  function profitOf(sale) {
    return (Number(sale.price) || 0) - (Number(sale.costPrice) || 0);
  }

  function tableMarkup() {
    const list = filteredSales();
    const total = list.length;
    const pages = Math.max(1, Math.ceil(total / PER_PAGE));
    if (pageState.page > pages) pageState.page = pages;
    const start = (pageState.page - 1) * PER_PAGE;
    const visible = list.slice(start, start + PER_PAGE);

    const rows = visible.length
      ? visible.map((sale) => {
        const goat = S.getGoat(sale.goatId);
        const customer = S.getCustomer(sale.customerId);
        const profit = profitOf(sale);
        return `
          <tr>
            <td><span class="barcode-text">${U.escapeHtml(sale.id)}</span></td>
            <td class="nowrap">${U.formatDate(sale.date, 'short')}</td>
            <td>
              <div class="cell-stack">
                <a class="cell-title" href="#/kambing/${encodeURIComponent(sale.goatId)}">${U.escapeHtml(goat ? goat.name : 'Kambing terhapus')}</a>
                <span class="cell-muted">${U.escapeHtml(goat ? goat.id : '-')}</span>
              </div>
            </td>
            <td><span class="barcode-text">${U.escapeHtml(goat ? goat.barcode : '-')}</span></td>
            <td>
              <div class="cell-person">
                ${UI.personAvatar(customer ? customer.name : '-', 'blue', 'xs')}
                <span class="cell-stack">
                  <strong>${U.escapeHtml(customer ? customer.name : '-')}</strong>
                  <span class="cell-muted">${U.escapeHtml(customer ? customer.phone : '-')}</span>
                </span>
              </div>
            </td>
            <td class="nowrap">${U.formatRupiah(sale.costPrice)}</td>
            <td class="nowrap"><strong>${U.formatRupiah(sale.price)}</strong></td>
            <td class="nowrap"><span class="profit ${profit >= 0 ? 'profit-positive' : 'profit-negative'}">${profit >= 0 ? '+' : ''}${U.formatRupiah(profit)}</span></td>
            <td>
              <div class="cell-actions">
                <button type="button" class="action-btn" data-detail="${U.escapeHtml(sale.id)}" title="Lihat detail transaksi" aria-label="Detail ${U.escapeHtml(sale.id)}">${UI.icon('eye', 16)}</button>
                <a class="action-btn" href="#/kambing/${encodeURIComponent(sale.goatId)}" title="Buka data kambing" aria-label="Buka data kambing">${UI.icon('goat', 16)}</a>
              </div>
            </td>
          </tr>
        `;
      }).join('')
      : `<tr><td colspan="9">${UI.emptyState({
        icon: 'tag',
        title: total ? 'Transaksi tidak ditemukan' : 'Belum ada transaksi penjualan',
        message: total ? 'Coba ubah kata kunci atau reset filter.' : 'Catat penjualan kambing untuk melihat keuntungan usaha Anda.',
        action: `<button type="button" class="btn btn-primary btn-sm" data-open-sale>${UI.icon('plus', 15)} Catat Penjualan</button>`,
      })}</td></tr>`;

    return `
      <div class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>No. Transaksi</th>
              <th>Tanggal</th>
              <th>Kambing</th>
              <th>Barcode</th>
              <th>Pembeli</th>
              <th>Harga Beli</th>
              <th>Harga Jual</th>
              <th>Keuntungan</th>
              <th class="th-actions">Aksi</th>
            </tr>
          </thead>
          <tbody>${rows}</tbody>
        </table>
      </div>
      ${UI.pagination({ page: pageState.page, perPage: PER_PAGE, total })}
    `;
  }

  function showDetail(saleId) {
    const sale = S.getState().sales.find((item) => item.id === saleId);
    if (!sale) {
      UI.toast('Transaksi penjualan tidak ditemukan.', 'error');
      return;
    }
    const goat = S.getGoat(sale.goatId);
    const customer = S.getCustomer(sale.customerId);
    const profit = profitOf(sale);
    UI.openModal({
      title: 'Detail Penjualan',
      subtitle: sale.id,
      size: 'md',
      body: `
        <div class="modal-summary">
          <div class="summary-identity">
            <span class="summary-icon summary-sale">${UI.icon('tag', 22)}</span>
            <div>
              <strong>${U.escapeHtml(sale.id)}</strong>
              <span>${U.formatDate(sale.date)} · Penjualan kambing</span>
            </div>
          </div>
          <span class="profit ${profit >= 0 ? 'profit-positive' : 'profit-negative'}">${profit >= 0 ? '+' : ''}${U.formatRupiah(profit)}</span>
        </div>
        <div class="info-grid modal-info-grid">
          <div class="info-item"><span>Kambing</span><strong>${U.escapeHtml(goat ? goat.name : 'Kambing terhapus')}</strong></div>
          <div class="info-item"><span>Barcode</span><strong class="barcode-text">${U.escapeHtml(goat ? goat.barcode : '-')}</strong></div>
          <div class="info-item"><span>Pembeli</span><strong>${U.escapeHtml(customer ? customer.name : '-')}</strong></div>
          <div class="info-item"><span>Telepon pembeli</span><strong>${U.escapeHtml(customer ? customer.phone : '-')}</strong></div>
          <div class="info-item"><span>Harga beli</span><strong>${U.formatRupiah(sale.costPrice)}</strong></div>
          <div class="info-item"><span>Harga jual</span><strong>${U.formatRupiah(sale.price)}</strong></div>
          <div class="info-item info-highlight"><span>Keuntungan kotor</span><strong class="${profit >= 0 ? 'text-success' : 'text-danger'}">${profit >= 0 ? '+' : ''}${U.formatRupiah(profit)}</strong></div>
          <div class="info-item"><span>Status kambing</span><strong>${goat ? UI.statusBadge(goat.status) : '-'}</strong></div>
        </div>
        <div class="modal-note-block">
          <span>Catatan</span>
          <p>${sale.notes ? U.escapeHtml(sale.notes) : '<span class="muted">Tidak ada catatan.</span>'}</p>
        </div>
        ${goat ? `<a class="btn btn-outline btn-block" href="#/kambing/${encodeURIComponent(goat.id)}" data-modal-close>${UI.icon('goat', 16)} Buka Data Kambing ${U.escapeHtml(goat.name)}</a>` : ''}
      `,
      footer: `<button type="button" class="btn btn-outline" data-modal-close>Tutup</button>`,
    });
  }

  function goatOptionLabel(goat) {
    return `${goat.name} · ${goat.barcode} · ${goat.type} (${U.formatWeight(goat.weight)})`;
  }

  function goatInfoMarkup(goat) {
    if (!goat) {
      return `<div class="sale-goat-placeholder">${UI.icon('goat', 24)}<span>Pilih kambing untuk melihat informasi.</span></div>`;
    }
    const mother = S.getMother(goat);
    const supplier = S.getSupplier(goat.supplierId);
    return `
      <div class="sale-goat-card">
        ${UI.goatAvatar(goat, 'md')}
        <div class="sale-goat-info">
          <strong>${U.escapeHtml(goat.name)} <span class="cell-muted">${U.escapeHtml(goat.id)}</span></strong>
          <span>${U.escapeHtml(goat.type)} · ${U.escapeHtml(goat.gender)} · ${U.formatWeight(goat.weight)}</span>
          <span>${UI.originBadge(goat.origin)} ${goat.origin === 'peranakan' ? `Induk: ${U.escapeHtml(mother ? mother.name : '-')}` : `Supplier: ${U.escapeHtml(supplier ? supplier.name : '-')}`}</span>
        </div>
        <div class="sale-goat-price">
          <span>Harga beli</span>
          <strong>${U.formatRupiah(goat.purchasePrice)}</strong>
        </div>
      </div>
    `;
  }

  function openForm(preselectGoatId) {
    const available = S.getState().goats
      .filter((goat) => goat.status === 'tersedia')
      .sort((a, b) => String(a.name).localeCompare(String(b.name)));
    const customers = S.getState().customers.slice().sort((a, b) => a.name.localeCompare(b.name));

    if (preselectGoatId) {
      const selectedGoat = S.getGoat(preselectGoatId);
      if (!selectedGoat || selectedGoat.status !== 'tersedia') {
        UI.toast('Kambing ini sudah terjual dan tidak dapat dijual kembali.', 'warning');
        return;
      }
    }

    UI.openModal({
      title: 'Catat Penjualan Kambing',
      subtitle: 'Status kambing akan otomatis berubah menjadi terjual.',
      size: 'lg',
      body: `
        <form id="sale-form" class="form" novalidate>
          ${!available.length ? `<div class="inline-alert inline-alert-warning">${UI.icon('alert-triangle', 16)}<span>Tidak ada kambing tersedia untuk dijual. <a class="inline-link" href="#/kambing?baru=1">Tambahkan kambing</a> terlebih dahulu.</span></div>` : ''}
          <div class="form-grid">
            <div class="form-group">
              <label for="sale-date">Tanggal Penjualan <span class="req">*</span></label>
              <input id="sale-date" type="date" name="date" value="${U.todayISO()}">
            </div>
            <div class="form-group">
              <label for="sale-goat">Kambing <span class="req">*</span></label>
              <select id="sale-goat" name="goatId" ${available.length ? '' : 'disabled'}>
                <option value="">Pilih kambing tersedia</option>
                ${available.map((goat) => `<option value="${U.escapeHtml(goat.id)}" ${preselectGoatId === goat.id ? 'selected' : ''}>${U.escapeHtml(goatOptionLabel(goat))}</option>`).join('')}
              </select>
            </div>

            <div class="form-group">
              <label for="sale-barcode">Barcode Kambing</label>
              <input id="sale-barcode" type="text" name="barcode" readonly placeholder="Terisi otomatis">
            </div>
            <div class="form-group">
              <label for="sale-customer">Pembeli / Customer <span class="req">*</span></label>
              <select id="sale-customer" name="customerId">
                <option value="">Pilih pembeli</option>
                ${customers.map((customer) => `<option value="${U.escapeHtml(customer.id)}">${U.escapeHtml(customer.name)} · ${U.escapeHtml(customer.phone)}</option>`).join('')}
              </select>
              ${customers.length ? '' : '<p class="form-hint form-hint-warning">Belum ada customer. <a class="inline-link" href="#/customer">Tambahkan customer</a> terlebih dahulu.</p>'}
            </div>

            <div class="form-group full">
              <label for="sale-price">Harga Jual <span class="req">*</span></label>
              ${UI.moneyField('price', '', { required: true })}
            </div>

            <div class="form-group full">
              <div id="sale-goat-info">${goatInfoMarkup(preselectGoatId ? S.getGoat(preselectGoatId) : null)}</div>
            </div>

            <div class="form-group full">
              <label for="sale-notes">Catatan</label>
              <textarea id="sale-notes" name="notes" rows="2" maxlength="400" placeholder="Catatan transaksi penjualan..."></textarea>
            </div>
          </div>

          <div class="total-preview sale-total-preview">
            <div><span>Harga beli</span><strong id="sale-cost-preview">Rp0</strong></div>
            <div><span>Estimasi keuntungan</span><strong id="sale-profit-preview">Rp0</strong></div>
          </div>
        </form>
      `,
      footer: `
        <button type="button" class="btn btn-outline" data-modal-close>Batal</button>
        <button type="submit" form="sale-form" class="btn btn-primary" data-save ${available.length ? '' : 'disabled'}>${UI.icon('check', 16)} Simpan Penjualan</button>
      `,
      onMount(element, close) {
        const form = element.querySelector('#sale-form');
        const saveButton = element.querySelector('[data-save]');
        const goatSelect = form.elements.goatId;
        const barcodeInput = form.elements.barcode;
        const infoContainer = element.querySelector('#sale-goat-info');
        const costPreview = element.querySelector('#sale-cost-preview');
        const profitPreview = element.querySelector('#sale-profit-preview');
        UI.Forms.clearOnInput(form);

        let costPrice = 0;

        const updateProfit = () => {
          const price = UI.Forms.number(form, 'price');
          const profit = price - costPrice;
          costPreview.textContent = U.formatRupiah(costPrice);
          profitPreview.textContent = `${profit >= 0 ? '+' : ''}${U.formatRupiah(profit)}`;
          profitPreview.classList.toggle('text-success', profit >= 0);
          profitPreview.classList.toggle('text-danger', profit < 0);
        };

        const updateGoat = () => {
          const goat = goatSelect && goatSelect.value ? S.getGoat(goatSelect.value) : null;
          costPrice = goat ? Number(goat.purchasePrice) || 0 : 0;
          if (barcodeInput) barcodeInput.value = goat ? goat.barcode : '';
          if (infoContainer) infoContainer.innerHTML = goatInfoMarkup(goat);
          updateProfit();
        };

        if (goatSelect) {
          goatSelect.addEventListener('change', updateGoat);
          if (goatSelect.value) updateGoat();
        }
        form.querySelectorAll('input[name="price"]').forEach((input) => input.addEventListener('input', updateProfit));
        updateProfit();

        form.addEventListener('submit', (event) => {
          event.preventDefault();
          UI.Forms.clearErrors(form);
          const date = UI.Forms.value(form, 'date');
          const goatId = UI.Forms.value(form, 'goatId');
          const customerId = UI.Forms.value(form, 'customerId');
          const price = UI.Forms.number(form, 'price');
          const notes = UI.Forms.value(form, 'notes');
          const errors = {};

          if (!date) errors.date = 'Tanggal penjualan wajib diisi.';
          if (date && date > U.todayISO()) errors.date = 'Tanggal penjualan tidak boleh di masa depan.';
          if (!goatId) errors.goatId = 'Pilih kambing yang akan dijual.';
          const goat = goatId ? S.getGoat(goatId) : null;
          if (goatId && (!goat || goat.status !== 'tersedia')) errors.goatId = 'Kambing yang dipilih tidak tersedia.';
          if (!customerId) errors.customerId = 'Pilih pembeli / customer.';
          if (!price || price <= 0) errors.price = 'Harga jual harus lebih dari Rp0.';

          if (Object.keys(errors).length) {
            UI.Forms.showErrors(form, errors);
            return;
          }

          const restore = UI.Forms.setLoading(saveButton, 'Menyimpan penjualan...');
          window.setTimeout(() => {
            try {
              const sale = S.addSale({ date, goatId, customerId, price, notes });
              const profit = profitOf(sale);
              UI.toast(`${sale.id} tersimpan. Keuntungan kotor ${U.formatRupiah(profit)}.`, 'success');
              close();
              if (K.Router) K.Router.refresh();
            } catch (error) {
              restore();
              UI.toast(error && error.message ? error.message : 'Gagal menyimpan penjualan.', 'error');
            }
          }, 180);
        });
      },
    });
  }

  function bindPage() {
    const content = document.getElementById('page-content');
    const container = content.querySelector('#sales-table-container');
    const searchInput = content.querySelector('#sale-search');
    const customerFilter = content.querySelector('#sale-filter-customer');

    const update = () => {
      if (container) container.innerHTML = tableMarkup();
    };

    const resetFilters = () => {
      pageState = { q: '', customer: '', page: 1 };
      if (searchInput) searchInput.value = '';
      if (customerFilter) customerFilter.value = '';
      update();
    };

    if (searchInput) {
      searchDebounce = U.debounce(() => {
        pageState.q = searchInput.value.trim();
        pageState.page = 1;
        update();
      }, 250);
      searchInput.addEventListener('input', searchDebounce);
    }
    if (customerFilter) {
      customerFilter.addEventListener('change', () => {
        pageState.customer = customerFilter.value;
        pageState.page = 1;
        update();
      });
    }

    content.querySelectorAll('[data-open-sale]').forEach((button) => {
      button.addEventListener('click', () => openForm());
    });
    content.querySelectorAll('[data-reset-filter]').forEach((button) => {
      button.addEventListener('click', resetFilters);
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
        if (event.target.closest('[data-open-sale]')) openForm();
        if (event.target.closest('[data-reset-filter]')) resetFilters();
      });
    }
  }

  K.Pages = K.Pages || {};
  K.Pages.sales = {
    title: 'Penjualan',
    nav: '/penjualan',
    breadcrumb: 'Penjualan',

    render(ctx) {
      const stats = S.getStats();
      const customers = S.getState().customers.slice().sort((a, b) => a.name.localeCompare(b.name));

      return `
        ${UI.pageHead({
          eyebrow: 'Transaksi',
          title: 'Transaksi Penjualan',
          subtitle: 'Catat kambing keluar, harga jual, dan keuntungan kotor setiap transaksi.',
          actions: `<button type="button" class="btn btn-primary" data-open-sale>${UI.icon('plus', 16)} Catat Penjualan</button>`,
        })}

        <section class="metric-grid" aria-label="Ringkasan penjualan">
          <div class="metric-card">
            <span class="metric-icon metric-blue">${UI.icon('tag', 19)}</span>
            <div><span>Total transaksi</span><strong>${stats.totalSaleCount}</strong></div>
          </div>
          <div class="metric-card">
            <span class="metric-icon metric-green">${UI.icon('wallet', 19)}</span>
            <div><span>Total penjualan</span><strong>${U.formatRupiah(stats.totalSales)}</strong></div>
          </div>
          <div class="metric-card">
            <span class="metric-icon metric-amber">${UI.icon('trending-up', 19)}</span>
            <div><span>Keuntungan kotor</span><strong>${U.formatRupiah(stats.grossProfit)}</strong></div>
          </div>
          <div class="metric-card">
            <span class="metric-icon metric-purple">${UI.icon('goat', 19)}</span>
            <div><span>Kambing terjual</span><strong>${stats.soldCount} ekor</strong></div>
          </div>
        </section>

        <section class="card">
          <div class="card-toolbar">
            <div class="search-field">
              ${UI.icon('search', 17)}
              <input id="sale-search" type="search" placeholder="Cari nomor transaksi, pembeli, kambing, atau barcode..." value="${U.escapeHtml(pageState.q)}" aria-label="Cari transaksi penjualan">
            </div>
            <div class="filter-group">
              <select id="sale-filter-customer" class="filter-select" aria-label="Filter customer">
                ${UI.options(customers.map((customer) => ({ value: customer.id, label: customer.name })), pageState.customer, 'Semua pembeli')}
              </select>
              <button type="button" class="btn btn-ghost btn-sm" data-reset-filter>${UI.icon('refresh', 15)} Reset</button>
            </div>
          </div>
          <div id="sales-table-container">${tableMarkup()}</div>
        </section>
      `;
    },

    mount(ctx) {
      bindPage();
      if (ctx.query && ctx.query.baru === '1') {
        window.history.replaceState(null, '', '#/penjualan');
        window.setTimeout(() => openForm(), 60);
      }
    },

    destroy() {
      if (searchDebounce && searchDebounce.cancel) searchDebounce.cancel();
    },

    openForm,
  };
})();
