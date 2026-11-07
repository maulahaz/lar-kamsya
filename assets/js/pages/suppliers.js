/**
 * Kamsya - Halaman Supplier
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

  function filteredSuppliers() {
    const query = pageState.q.toLowerCase();
    return S.getState().suppliers
      .filter((supplier) => {
        if (!query) return true;
        return `${supplier.name} ${supplier.phone} ${supplier.address}`.toLowerCase().includes(query);
      })
      .sort((a, b) => a.name.localeCompare(b.name));
  }

  function tableMarkup() {
    const list = filteredSuppliers();
    const total = list.length;
    const pages = Math.max(1, Math.ceil(total / PER_PAGE));
    if (pageState.page > pages) pageState.page = pages;
    const start = (pageState.page - 1) * PER_PAGE;
    const visible = list.slice(start, start + PER_PAGE);

    const rows = visible.length
      ? visible.map((supplier) => {
        const stats = S.supplierStats(supplier.id);
        return `
          <tr>
            <td>
              <div class="cell-person">
                ${UI.personAvatar(supplier.name, 'green', 'sm')}
                <span class="cell-stack">
                  <strong>${U.escapeHtml(supplier.name)}</strong>
                  <span class="cell-muted">${U.escapeHtml(supplier.notes || 'Supplier kambing')}</span>
                </span>
              </div>
            </td>
            <td>
              <div class="cell-stack">
                <span>${U.escapeHtml(supplier.phone)}</span>
                <span class="cell-muted">${UI.icon('phone', 12)} Terhubung</span>
              </div>
            </td>
            <td class="wrap-cell">${U.escapeHtml(supplier.address || '-')}</td>
            <td>
              <div class="cell-stack">
                <strong>${stats.count} transaksi</strong>
                <span class="cell-muted">${U.formatRupiah(stats.total)}</span>
              </div>
            </td>
            <td>
              <div class="cell-actions">
                <button type="button" class="action-btn" data-history="${U.escapeHtml(supplier.id)}" title="Riwayat transaksi" aria-label="Riwayat transaksi ${U.escapeHtml(supplier.name)}">${UI.icon('clock', 16)}</button>
                <button type="button" class="action-btn" data-edit="${U.escapeHtml(supplier.id)}" title="Edit supplier" aria-label="Edit ${U.escapeHtml(supplier.name)}">${UI.icon('edit', 16)}</button>
                <button type="button" class="action-btn action-danger" data-delete="${U.escapeHtml(supplier.id)}" title="Hapus supplier" aria-label="Hapus ${U.escapeHtml(supplier.name)}">${UI.icon('trash', 16)}</button>
              </div>
            </td>
          </tr>
        `;
      }).join('')
      : `<tr><td colspan="5">${UI.emptyState({
        icon: 'truck',
        title: total ? 'Supplier tidak ditemukan' : 'Belum ada supplier',
        message: total ? 'Coba kata kunci lain.' : 'Tambahkan supplier untuk mendukung pencatatan transaksi pembelian.',
        action: `<button type="button" class="btn btn-primary btn-sm" data-add-supplier>${UI.icon('plus', 15)} Tambah Supplier</button>`,
      })}</td></tr>`;

    return `
      <div class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>Supplier</th>
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

  function openForm(supplierId) {
    const supplier = supplierId ? S.getSupplier(supplierId) : null;
    if (supplierId && !supplier) {
      UI.toast('Supplier tidak ditemukan.', 'error');
      return;
    }
    const isEdit = Boolean(supplier);

    UI.openModal({
      title: isEdit ? 'Edit Supplier' : 'Tambah Supplier',
      subtitle: isEdit ? supplier.id : 'Lengkapi data supplier atau penjual kambing.',
      size: 'md',
      body: `
        <form id="supplier-form" class="form" novalidate>
          <div class="form-grid">
            <div class="form-group full">
              <label for="supplier-name">Nama Supplier <span class="req">*</span></label>
              <input id="supplier-name" type="text" name="name" maxlength="80" placeholder="Contoh: Pak Hadi" value="${U.escapeHtml(supplier ? supplier.name : '')}" autofocus>
            </div>
            <div class="form-group full">
              <label for="supplier-phone">Nomor Telepon <span class="req">*</span></label>
              <div class="input-icon">
                ${UI.icon('phone', 16)}
                <input id="supplier-phone" type="tel" name="phone" maxlength="30" placeholder="0812-3456-7890" value="${U.escapeHtml(supplier ? supplier.phone : '')}">
              </div>
            </div>
            <div class="form-group full">
              <label for="supplier-address">Alamat</label>
              <div class="input-icon">
                ${UI.icon('map-pin', 16)}
                <input id="supplier-address" type="text" name="address" maxlength="160" placeholder="Alamat lengkap supplier" value="${U.escapeHtml(supplier ? supplier.address : '')}">
              </div>
            </div>
            <div class="form-group full">
              <label for="supplier-notes">Catatan</label>
              <textarea id="supplier-notes" name="notes" rows="3" maxlength="400" placeholder="Informasi tambahan tentang supplier...">${U.escapeHtml(supplier ? supplier.notes : '')}</textarea>
            </div>
          </div>
        </form>
      `,
      footer: `
        <button type="button" class="btn btn-outline" data-modal-close>Batal</button>
        <button type="submit" form="supplier-form" class="btn btn-primary" data-save>${UI.icon('check', 16)} ${isEdit ? 'Simpan Perubahan' : 'Simpan Supplier'}</button>
      `,
      onMount(element, close) {
        const form = element.querySelector('#supplier-form');
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
          if (!name) errors.name = 'Nama supplier wajib diisi.';
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
                S.updateSupplier(supplier.id, { name, phone, address, notes });
                UI.toast(`Data supplier ${name} berhasil diperbarui.`, 'success');
              } else {
                const created = S.addSupplier({ name, phone, address, notes });
                UI.toast(`Supplier ${created.name} berhasil ditambahkan.`, 'success');
              }
              close();
              pageState.page = 1;
              if (K.Router) K.Router.refresh();
            } catch (error) {
              restore();
              UI.toast(error && error.message ? error.message : 'Gagal menyimpan supplier.', 'error');
            }
          }, 160);
        });
      },
    });
  }

  function showHistory(supplierId) {
    const supplier = S.getSupplier(supplierId);
    if (!supplier) {
      UI.toast('Supplier tidak ditemukan.', 'error');
      return;
    }
    const stats = S.supplierStats(supplierId);
    const purchases = stats.transactions.slice().sort((a, b) => String(b.date).localeCompare(String(a.date)));
    UI.openModal({
      title: 'Riwayat Supplier',
      subtitle: `${supplier.name} · ${supplier.id}`,
      size: 'md',
      body: `
        <div class="modal-summary">
          <div class="summary-identity">
            ${UI.personAvatar(supplier.name, 'green', 'md')}
            <div>
              <strong>${U.escapeHtml(supplier.name)}</strong>
              <span>${U.escapeHtml(supplier.phone)} · ${U.escapeHtml(supplier.address || 'Alamat belum diisi')}</span>
            </div>
          </div>
          <span class="badge badge-info">${stats.count} transaksi</span>
        </div>
        <div class="history-summary-grid">
          <div><span>Total transaksi</span><strong>${stats.count}</strong></div>
          <div><span>Total pembelian</span><strong>${U.formatRupiah(stats.total)}</strong></div>
          <div><span>Rata-rata transaksi</span><strong>${U.formatRupiah(stats.count ? stats.total / stats.count : 0)}</strong></div>
        </div>
        ${purchases.length ? `<div class="history-list">${purchases.map((purchase) => {
          const goat = S.getGoat(purchase.goatId);
          return `
            <a class="history-card" href="#/kambing/${encodeURIComponent(purchase.goatId)}" data-modal-close>
              <span class="history-date">${U.formatDate(purchase.date, 'short')}</span>
              <span class="history-main">
                <strong>${U.escapeHtml(goat ? goat.name : 'Kambing terhapus')}</strong>
                <small>${U.escapeHtml(purchase.id)} · ${U.escapeHtml(goat ? goat.barcode : '-')}</small>
              </span>
              <strong class="history-amount">${U.formatRupiah(purchase.total)}</strong>
              ${UI.icon('chevron-right', 15)}
            </a>
          `;
        }).join('')}</div>` : UI.emptyState({ icon: 'cart', title: 'Belum ada transaksi', message: 'Supplier ini belum digunakan pada transaksi pembelian.' })}
      `,
      footer: `<button type="button" class="btn btn-outline" data-modal-close>Tutup</button>`,
    });
  }

  function confirmDelete(supplierId) {
    const supplier = S.getSupplier(supplierId);
    if (!supplier) {
      UI.toast('Supplier tidak ditemukan.', 'error');
      return;
    }
    const stats = S.supplierStats(supplierId);
    if (stats.count > 0) {
      UI.toast(`Supplier masih digunakan pada ${stats.count} transaksi pembelian dan tidak dapat dihapus.`, 'warning');
      return;
    }
    UI.confirm({
      title: 'Hapus supplier?',
      message: `Hapus supplier "${supplier.name}"?`,
      detail: 'Data supplier akan dihapus permanen. Riwayat transaksi yang sudah ada tidak terpengaruh karena supplier ini belum memiliki transaksi.',
      confirmText: 'Ya, Hapus',
      onConfirm() {
        S.deleteSupplier(supplierId);
        UI.toast(`Supplier ${supplier.name} berhasil dihapus.`, 'success');
        if (K.Router) K.Router.refresh();
      },
    });
  }

  function bindPage() {
    const content = document.getElementById('page-content');
    const container = content.querySelector('#suppliers-table-container');
    const searchInput = content.querySelector('#supplier-search');

    if (searchInput) {
      searchDebounce = U.debounce(() => {
        pageState.q = searchInput.value.trim();
        pageState.page = 1;
        if (container) container.innerHTML = tableMarkup();
      }, 250);
      searchInput.addEventListener('input', searchDebounce);
    }

    content.querySelectorAll('[data-add-supplier]').forEach((button) => {
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
        if (event.target.closest('[data-add-supplier]')) openForm();
      });
    }
  }

  K.Pages = K.Pages || {};
  K.Pages.suppliers = {
    title: 'Supplier',
    nav: '/supplier',
    breadcrumb: 'Supplier',

    render() {
      const state = S.getState();
      const stats = S.getStats();
      const used = state.suppliers.filter((supplier) => S.supplierStats(supplier.id).count > 0).length;

      return `
        ${UI.pageHead({
          eyebrow: 'Data Pendukung',
          title: 'Supplier',
          subtitle: 'Kelola data supplier dan penjual kambing untuk transaksi pembelian.',
          actions: `<button type="button" class="btn btn-primary" data-add-supplier>${UI.icon('plus', 16)} Tambah Supplier</button>`,
        })}

        <section class="metric-grid metric-grid-3">
          <div class="metric-card">
            <span class="metric-icon metric-green">${UI.icon('truck', 19)}</span>
            <div><span>Total supplier</span><strong>${state.suppliers.length} supplier</strong></div>
          </div>
          <div class="metric-card">
            <span class="metric-icon metric-blue">${UI.icon('check-circle', 19)}</span>
            <div><span>Supplier aktif</span><strong>${used} supplier</strong></div>
          </div>
          <div class="metric-card">
            <span class="metric-icon metric-amber">${UI.icon('wallet', 19)}</span>
            <div><span>Total pembelian</span><strong>${U.formatRupiah(stats.totalPurchases)}</strong></div>
          </div>
        </section>

        <section class="card">
          <div class="card-toolbar">
            <div class="search-field">
              ${UI.icon('search', 17)}
              <input id="supplier-search" type="search" placeholder="Cari nama, telepon, atau alamat supplier..." value="${U.escapeHtml(pageState.q)}" aria-label="Cari supplier">
            </div>
            <span class="toolbar-hint">${state.suppliers.length} supplier terdaftar</span>
          </div>
          <div id="suppliers-table-container">${tableMarkup()}</div>
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
