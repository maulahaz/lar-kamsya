/**
 * Kamsya - Halaman Customer
 */
(function () {
  'use strict';

  const K = (window.Kamsya = window.Kamsya || {});
  const U = K.Utils;
  const UI = K.UI;
  const S = K.Store;

  const PER_PAGE = 8;
  let pageState = { q: '', page: 1 };
  let searchDebounce = null;

  function filteredCustomers() {
    const query = pageState.q.toLowerCase();
    return S.getState().customers
      .filter((customer) => {
        if (!query) return true;
        return `${customer.name} ${customer.phone} ${customer.address}`.toLowerCase().includes(query);
      })
      .sort((a, b) => a.name.localeCompare(b.name));
  }

  function tableMarkup() {
    const list = filteredCustomers();
    const total = list.length;
    const pages = Math.max(1, Math.ceil(total / PER_PAGE));
    if (pageState.page > pages) pageState.page = pages;
    const start = (pageState.page - 1) * PER_PAGE;
    const visible = list.slice(start, start + PER_PAGE);

    const rows = visible.length
      ? visible.map((customer) => {
        const stats = S.customerStats(customer.id);
        return `
          <tr>
            <td>
              <div class="cell-person">
                ${UI.personAvatar(customer.name, 'blue', 'sm')}
                <span class="cell-stack">
                  <strong>${U.escapeHtml(customer.name)}</strong>
                  <span class="cell-muted">${U.escapeHtml(customer.notes || 'Customer kambing')}</span>
                </span>
              </div>
            </td>
            <td>
              <div class="cell-stack">
                <span>${U.escapeHtml(customer.phone)}</span>
                <span class="cell-muted">${UI.icon('phone', 12)} Terhubung</span>
              </div>
            </td>
            <td class="wrap-cell">${U.escapeHtml(customer.address || '-')}</td>
            <td>
              <div class="cell-stack">
                <strong>${stats.count} transaksi</strong>
                <span class="cell-muted">${U.formatRupiah(stats.total)}</span>
              </div>
            </td>
            <td>
              <div class="cell-actions">
                <button type="button" class="action-btn" data-history="${U.escapeHtml(customer.id)}" title="Riwayat transaksi" aria-label="Riwayat transaksi ${U.escapeHtml(customer.name)}">${UI.icon('clock', 16)}</button>
                <button type="button" class="action-btn" data-edit="${U.escapeHtml(customer.id)}" title="Edit customer" aria-label="Edit ${U.escapeHtml(customer.name)}">${UI.icon('edit', 16)}</button>
                <button type="button" class="action-btn action-danger" data-delete="${U.escapeHtml(customer.id)}" title="Hapus customer" aria-label="Hapus ${U.escapeHtml(customer.name)}">${UI.icon('trash', 16)}</button>
              </div>
            </td>
          </tr>
        `;
      }).join('')
      : `<tr><td colspan="5">${UI.emptyState({
        icon: 'users',
        title: total ? 'Customer tidak ditemukan' : 'Belum ada customer',
        message: total ? 'Coba kata kunci lain.' : 'Tambahkan customer untuk mendukung pencatatan transaksi penjualan.',
        action: `<button type="button" class="btn btn-primary btn-sm" data-add-customer>${UI.icon('plus', 15)} Tambah Customer</button>`,
      })}</td></tr>`;

    return `
      <div class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>Customer</th>
              <th>Kontak</th>
              <th>Alamat</th>
              <th>Transaksi</th>
              <th class="th-actions">Aksi</th>
            </tr>
          </thead>
          <tbody>${rows}</tbody>
        </table>
      </div>
      ${UI.pagination({ page: pageState.page, perPage: PER_PAGE, total })}
    `;
  }

  function openForm(customerId) {
    const customer = customerId ? S.getCustomer(customerId) : null;
    if (customerId && !customer) {
      UI.toast('Customer tidak ditemukan.', 'error');
      return;
    }
    const isEdit = Boolean(customer);

    UI.openModal({
      title: isEdit ? 'Edit Customer' : 'Tambah Customer',
      subtitle: isEdit ? customer.id : 'Lengkapi data pembeli kambing.',
      size: 'md',
      body: `
        <form id="customer-form" class="form" novalidate>
          <div class="form-grid">
            <div class="form-group full">
              <label for="customer-name">Nama Customer <span class="req">*</span></label>
              <input id="customer-name" type="text" name="name" maxlength="80" placeholder="Contoh: Haji Umar" value="${U.escapeHtml(customer ? customer.name : '')}" autofocus>
            </div>
            <div class="form-group full">
              <label for="customer-phone">Nomor Telepon <span class="req">*</span></label>
              <div class="input-icon">
                ${UI.icon('phone', 16)}
                <input id="customer-phone" type="tel" name="phone" maxlength="30" placeholder="0812-1111-2222" value="${U.escapeHtml(customer ? customer.phone : '')}">
              </div>
            </div>
            <div class="form-group full">
              <label for="customer-address">Alamat</label>
              <div class="input-icon">
                ${UI.icon('map-pin', 16)}
                <input id="customer-address" type="text" name="address" maxlength="160" placeholder="Alamat lengkap customer" value="${U.escapeHtml(customer ? customer.address : '')}">
              </div>
            </div>
            <div class="form-group full">
              <label for="customer-notes">Catatan</label>
              <textarea id="customer-notes" name="notes" rows="3" maxlength="400" placeholder="Informasi tambahan tentang customer...">${U.escapeHtml(customer ? customer.notes : '')}</textarea>
            </div>
          </div>
        </form>
      `,
      footer: `
        <button type="button" class="btn btn-outline" data-modal-close>Batal</button>
        <button type="submit" form="customer-form" class="btn btn-primary" data-save>${UI.icon('check', 16)} ${isEdit ? 'Simpan Perubahan' : 'Simpan Customer'}</button>
      `,
      onMount(element, close) {
        const form = element.querySelector('#customer-form');
        const saveButton = element.querySelector('[data-save]');
        UI.Forms.clearOnInput(form);
        form.addEventListener('submit', (event) => {
          event.preventDefault();
          UI.Forms.clearErrors(form);
          const name = UI.Forms.value(form, 'name');
          const phone = UI.Forms.value(form, 'phone');
          const address = UI.Forms.value(form, 'address');
          const notes = UI.Forms.value(form, 'notes');
          const errors = {};
          if (!name) errors.name = 'Nama customer wajib diisi.';
          if (!phone) errors.phone = 'Nomor telepon wajib diisi.';
          if (phone && !/^[0-9+()\-\s]{6,30}$/.test(phone)) errors.phone = 'Format nomor telepon tidak valid.';
          if (Object.keys(errors).length) {
            UI.Forms.showErrors(form, errors);
            return;
          }
          const restore = UI.Forms.setLoading(saveButton, 'Menyimpan...');
          window.setTimeout(() => {
            try {
              if (isEdit) {
                S.updateCustomer(customer.id, { name, phone, address, notes });
                UI.toast(`Data customer ${name} berhasil diperbarui.`, 'success');
              } else {
                const created = S.addCustomer({ name, phone, address, notes });
                UI.toast(`Customer ${created.name} berhasil ditambahkan.`, 'success');
              }
              close();
              pageState.page = 1;
              if (K.Router) K.Router.refresh();
            } catch (error) {
              restore();
              UI.toast(error && error.message ? error.message : 'Gagal menyimpan customer.', 'error');
            }
          }, 160);
        });
      },
    });
  }

  function showHistory(customerId) {
    const customer = S.getCustomer(customerId);
    if (!customer) {
      UI.toast('Customer tidak ditemukan.', 'error');
      return;
    }
    const stats = S.customerStats(customerId);
    const sales = stats.transactions.slice().sort((a, b) => String(b.date).localeCompare(String(a.date)));
    UI.openModal({
      title: 'Riwayat Customer',
      subtitle: `${customer.name} · ${customer.id}`,
      size: 'md',
      body: `
        <div class="modal-summary">
          <div class="summary-identity">
            ${UI.personAvatar(customer.name, 'blue', 'md')}
            <div>
              <strong>${U.escapeHtml(customer.name)}</strong>
              <span>${U.escapeHtml(customer.phone)} · ${U.escapeHtml(customer.address || 'Alamat belum diisi')}</span>
            </div>
          </div>
          <span class="badge badge-info">${stats.count} transaksi</span>
        </div>
        <div class="history-summary-grid">
          <div><span>Total transaksi</span><strong>${stats.count}</strong></div>
          <div><span>Total nilai</span><strong>${U.formatRupiah(stats.total)}</strong></div>
          <div><span>Keuntungan</span><strong>${U.formatRupiah(stats.profit)}</strong></div>
        </div>
        ${sales.length ? `<div class="history-list">${sales.map((sale) => {
          const goat = S.getGoat(sale.goatId);
          return `
            <a class="history-card" href="#/kambing/${encodeURIComponent(sale.goatId)}" data-modal-close>
              <span class="history-date">${U.formatDate(sale.date, 'short')}</span>
              <span class="history-main">
                <strong>${U.escapeHtml(goat ? goat.name : 'Kambing terhapus')}</strong>
                <small>${U.escapeHtml(sale.id)} · ${U.escapeHtml(goat ? goat.barcode : '-')}</small>
              </span>
              <strong class="history-amount">${U.formatRupiah(sale.price)}</strong>
              ${UI.icon('chevron-right', 15)}
            </a>
          `;
        }).join('')}</div>` : UI.emptyState({ icon: 'tag', title: 'Belum ada transaksi', message: 'Customer ini belum memiliki transaksi penjualan.' })}
      `,
      footer: `<button type="button" class="btn btn-outline" data-modal-close>Tutup</button>`,
    });
  }

  function confirmDelete(customerId) {
    const customer = S.getCustomer(customerId);
    if (!customer) {
      UI.toast('Customer tidak ditemukan.', 'error');
      return;
    }
    const stats = S.customerStats(customerId);
    if (stats.count > 0) {
      UI.toast(`Customer masih digunakan pada ${stats.count} transaksi penjualan dan tidak dapat dihapus.`, 'warning');
      return;
    }
    UI.confirm({
      title: 'Hapus customer?',
      message: `Hapus customer "${customer.name}"?`,
      detail: 'Data customer akan dihapus permanen. Riwayat transaksi yang sudah ada tidak terpengaruh karena customer ini belum memiliki transaksi.',
      confirmText: 'Ya, Hapus',
      onConfirm() {
        S.deleteCustomer(customerId);
        UI.toast(`Customer ${customer.name} berhasil dihapus.`, 'success');
        if (K.Router) K.Router.refresh();
      },
    });
  }

  function bindPage() {
    const content = document.getElementById('page-content');
    const container = content.querySelector('#customers-table-container');
    const searchInput = content.querySelector('#customer-search');

    if (searchInput) {
      searchDebounce = U.debounce(() => {
        pageState.q = searchInput.value.trim();
        pageState.page = 1;
        if (container) container.innerHTML = tableMarkup();
      }, 250);
      searchInput.addEventListener('input', searchDebounce);
    }

    content.querySelectorAll('[data-add-customer]').forEach((button) => {
      button.addEventListener('click', () => openForm());
    });

    if (container) {
      container.addEventListener('click', (event) => {
        const pageButton = event.target.closest('[data-page]');
        if (pageButton && !pageButton.disabled) {
          pageState.page = Number(pageButton.dataset.page) || 1;
          container.innerHTML = tableMarkup();
          return;
        }
        const historyButton = event.target.closest('[data-history]');
        if (historyButton) {
          showHistory(historyButton.dataset.history);
          return;
        }
        const editButton = event.target.closest('[data-edit]');
        if (editButton) {
          openForm(editButton.dataset.edit);
          return;
        }
        const deleteButton = event.target.closest('[data-delete]');
        if (deleteButton) {
          confirmDelete(deleteButton.dataset.delete);
          return;
        }
        if (event.target.closest('[data-add-customer]')) openForm();
      });
    }
  }

  K.Pages = K.Pages || {};
  K.Pages.customers = {
    title: 'Customer',
    nav: '/customer',
    breadcrumb: 'Customer',

    render() {
      const state = S.getState();
      const stats = S.getStats();
      const used = state.customers.filter((customer) => S.customerStats(customer.id).count > 0).length;

      return `
        ${UI.pageHead({
          eyebrow: 'Data Pendukung',
          title: 'Customer',
          subtitle: 'Kelola data pembeli kambing dan riwayat transaksi mereka.',
          actions: `<button type="button" class="btn btn-primary" data-add-customer>${UI.icon('plus', 16)} Tambah Customer</button>`,
        })}

        <section class="metric-grid metric-grid-3">
          <div class="metric-card">
            <span class="metric-icon metric-blue">${UI.icon('users', 19)}</span>
            <div><span>Total customer</span><strong>${state.customers.length} customer</strong></div>
          </div>
          <div class="metric-card">
            <span class="metric-icon metric-green">${UI.icon('check-circle', 19)}</span>
            <div><span>Customer aktif</span><strong>${used} customer</strong></div>
          </div>
          <div class="metric-card">
            <span class="metric-icon metric-amber">${UI.icon('wallet', 19)}</span>
            <div><span>Total penjualan</span><strong>${U.formatRupiah(stats.totalSales)}</strong></div>
          </div>
        </section>

        <section class="card">
          <div class="card-toolbar">
            <div class="search-field">
              ${UI.icon('search', 17)}
              <input id="customer-search" type="search" placeholder="Cari nama, telepon, atau alamat customer..." value="${U.escapeHtml(pageState.q)}" aria-label="Cari customer">
            </div>
            <span class="toolbar-hint">${state.customers.length} customer terdaftar</span>
          </div>
          <div id="customers-table-container">${tableMarkup()}</div>
        </section>
      `;
    },

    mount() {
      bindPage();
    },

    destroy() {
      if (searchDebounce && searchDebounce.cancel) searchDebounce.cancel();
    },
  };
})();
