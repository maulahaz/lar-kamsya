/**
 * Kamsya - Halaman Data Kambing (CRUD + form asal kambing)
 */
(function () {
  'use strict';

  const K = (window.Kamsya = window.Kamsya || {});
  const U = K.Utils;
  const UI = K.UI;
  const S = K.Store;
  const B = K.Barcode;

  const PER_PAGE = 8;
  const TYPE_PRESETS = ['Boer', 'Etawa', 'Jawa', 'Boer Cross', 'Kacang', 'Saanen', 'Lainnya'];

  const defaults = () => ({ q: '', type: '', status: '', origin: '', page: 1 });
  let pageState = defaults();
  let lastQuerySig = null;
  let searchDebounce = null;

  function applyQuery(query) {
    const q = query || {};
    const signature = JSON.stringify(q);
    if (signature === lastQuerySig) return;
    const hasFilterQuery = ['q', 'status', 'asal', 'jenis'].some((key) => Object.prototype.hasOwnProperty.call(q, key));
    if (hasFilterQuery || (lastQuerySig !== null && lastQuerySig !== '{}')) {
      pageState = defaults();
      if (hasFilterQuery) {
        pageState.q = q.q || '';
        pageState.status = q.status || '';
        pageState.origin = q.asal || '';
        pageState.type = q.jenis || '';
      }
    }
    lastQuerySig = signature;
  }

  function allTypes() {
    const existing = S.getState().goats.map((goat) => goat.type);
    return Array.from(new Set(TYPE_PRESETS.concat(existing))).filter(Boolean);
  }

  function filteredGoats() {
    const query = pageState.q.toLowerCase();
    return S.getState().goats
      .filter((goat) => {
        if (pageState.type && goat.type !== pageState.type) return false;
        if (pageState.status && goat.status !== pageState.status) return false;
        if (pageState.origin && goat.origin !== pageState.origin) return false;
        if (query) {
          const haystack = `${goat.name} ${goat.id} ${goat.barcode}`.toLowerCase();
          if (!haystack.includes(query)) return false;
        }
        return true;
      })
      .sort((a, b) => {
        const dateA = String(a.createdAt || a.entryDate || '');
        const dateB = String(b.createdAt || b.entryDate || '');
        const diff = dateB.localeCompare(dateA);
        return diff !== 0 ? diff : String(b.id).localeCompare(String(a.id));
      });
  }

  function motherLabel(goat) {
    if (!goat.motherId) return '<span class="muted">—</span>';
    const mother = S.getGoat(goat.motherId);
    if (!mother) return `<span class="muted">${U.escapeHtml(goat.motherId)}</span>`;
    return `<a class="inline-link" href="#/kambing/${encodeURIComponent(mother.id)}">${U.escapeHtml(mother.name)}</a><span class="cell-muted block">${U.escapeHtml(mother.id)}</span>`;
  }

  function tableMarkup() {
    const list = filteredGoats();
    const total = list.length;
    const pages = Math.max(1, Math.ceil(total / PER_PAGE));
    if (pageState.page > pages) pageState.page = pages;
    const start = (pageState.page - 1) * PER_PAGE;
    const visible = list.slice(start, start + PER_PAGE);

    const rows = visible.length
      ? visible.map((goat) => `
        <tr>
          <td>
            <div class="cell-goat">
              ${UI.goatAvatar(goat, 'sm')}
              <div class="cell-stack">
                <a class="cell-title" href="#/kambing/${encodeURIComponent(goat.id)}">${U.escapeHtml(goat.name)}</a>
                <span class="cell-muted">${U.escapeHtml(goat.id)}</span>
              </div>
            </div>
          </td>
          <td><span class="barcode-text">${U.escapeHtml(goat.barcode)}</span></td>
          <td>
            ${UI.originBadge(goat.origin)}
            ${goat.origin === 'peranakan' ? `<div class="cell-sub">Induk: ${motherLabel(goat)}</div>` : ''}
          </td>
          <td>${UI.typeBadge(goat.type)}</td>
          <td class="nowrap">${U.escapeHtml(goat.gender)}</td>
          <td class="nowrap">${U.formatWeight(goat.weight)}</td>
          <td class="nowrap">${goat.origin === 'pembelian' ? U.formatRupiah(goat.purchasePrice) : '<span class="muted">—</span>'}</td>
          <td class="nowrap">${U.formatDate(goat.entryDate, 'short')}</td>
          <td>${UI.statusBadge(goat.status)}</td>
          <td>
            <div class="cell-actions">
              <a class="action-btn" href="#/kambing/${encodeURIComponent(goat.id)}" title="Lihat detail" aria-label="Lihat detail ${U.escapeHtml(goat.name)}">${UI.icon('eye', 16)}</a>
              <button type="button" class="action-btn" data-edit="${U.escapeHtml(goat.id)}" title="Edit data" aria-label="Edit ${U.escapeHtml(goat.name)}">${UI.icon('edit', 16)}</button>
              <a class="action-btn" href="#/cetak?goat=${encodeURIComponent(goat.id)}" title="Cetak barcode" aria-label="Cetak barcode ${U.escapeHtml(goat.name)}">${UI.icon('printer', 16)}</a>
              <button type="button" class="action-btn action-danger" data-delete="${U.escapeHtml(goat.id)}" title="Hapus data" aria-label="Hapus ${U.escapeHtml(goat.name)}">${UI.icon('trash', 16)}</button>
            </div>
          </td>
        </tr>
      `).join('')
      : `<tr><td colspan="10">${UI.emptyState({
        icon: 'goat',
        title: total || pageState.q || pageState.type || pageState.status || pageState.origin ? 'Kambing tidak ditemukan' : 'Belum ada data kambing',
        message: total || pageState.q ? 'Coba ubah kata kunci atau reset filter.' : 'Tambahkan kambing pertama Anda untuk mulai mengelola inventaris.',
        action: `<button type="button" class="btn btn-primary btn-sm" data-reset-filter>${UI.icon('refresh', 15)} Reset filter</button>`,
      })}</td></tr>`;

    return `
      <div class="table-wrap">
        <table class="data-table goats-table">
          <thead>
            <tr>
              <th>Kambing</th>
              <th>Barcode</th>
              <th>Asal</th>
              <th>Jenis</th>
              <th>JK</th>
              <th>Berat</th>
              <th>Harga Beli</th>
              <th>Tanggal Masuk</th>
              <th>Status</th>
              <th class="th-actions">Aksi</th>
            </tr>
          </thead>
          <tbody>${rows}</tbody>
        </table>
      </div>
      ${UI.pagination({ page: pageState.page, perPage: PER_PAGE, total })}
    `;
  }

  function originOptions(current) {
    return `
      <div class="origin-cards">
        <label class="origin-card">
          <input type="radio" name="origin" value="pembelian" ${current !== 'peranakan' ? 'checked' : ''}>
          <span class="origin-card-inner">
            <span class="origin-card-icon">${UI.icon('cart', 19)}</span>
            <span><strong>Pembelian</strong><small>Dibeli dari supplier atau penjual</small></span>
          </span>
        </label>
        <label class="origin-card">
          <input type="radio" name="origin" value="peranakan" ${current === 'peranakan' ? 'checked' : ''}>
          <span class="origin-card-inner">
            <span class="origin-card-icon">${UI.icon('goat', 19)}</span>
            <span><strong>Peranakan</strong><small>Lahir di peternakan dari induk terdaftar</small></span>
          </span>
        </label>
      </div>
    `;
  }

  function openGoatForm(goatId, presets) {
    const goat = goatId ? S.getGoat(goatId) : null;
    if (goatId && !goat) {
      UI.toast('Data kambing tidak ditemukan.', 'error');
      return;
    }
    const isEdit = Boolean(goat);
    const settings = S.getState().settings;
    const barcode = goat ? goat.barcode : S.peekNextBarcode();
    const origin = goat ? goat.origin : (presets && presets.origin) || 'pembelian';
    const suppliers = S.getState().suppliers.slice().sort((a, b) => a.name.localeCompare(b.name));
    const mothers = S.getState().goats
      .filter((item) => item.gender === 'Betina' && item.id !== goatId)
      .sort((a, b) => String(a.id).localeCompare(String(b.id)));

    const formBody = `
      <form id="goat-form" class="form" novalidate>
        <div class="barcode-preview">
          <div class="barcode-preview-meta">
            <span class="preview-label">${isEdit ? 'Barcode kambing' : 'Barcode otomatis'}</span>
            <strong class="barcode-text">${U.escapeHtml(barcode)}</strong>
            <small>${isEdit ? 'Barcode bersifat permanen dan tidak berubah.' : 'Dibuat unik saat data disimpan.'}</small>
          </div>
          <div class="barcode-preview-svg">${B.svg(barcode, { height: 42, moduleWidth: 1.5 })}</div>
        </div>

        <div class="form-grid">
          <div class="form-group full">
            <label for="goat-name">Nama / Label <span class="req">*</span></label>
            <input id="goat-name" type="text" name="name" maxlength="60" placeholder="Contoh: Si Jago" value="${U.escapeHtml(goat ? goat.name : '')}" autofocus>
          </div>

          ${isEdit
            ? `<div class="form-group full">
                <label>Asal Kambing</label>
                <div class="locked-field">
                  ${UI.originBadge(origin)}
                  <span>${origin === 'peranakan' ? 'Kambing peranakan terhubung dengan induk terdaftar.' : 'Kambing pembelian terhubung dengan transaksi pembelian.'}</span>
                  <span class="lock-note">${UI.icon('info', 14)} Asal kambing tidak dapat diubah.</span>
                </div>
              </div>`
            : `<div class="form-group full">
                <label>Asal Kambing <span class="req">*</span></label>
                ${originOptions(origin)}
              </div>`}

          <div class="form-group">
            <label for="goat-type">Jenis Kambing <span class="req">*</span></label>
            <select id="goat-type" name="type">
              <option value="">Pilih jenis kambing</option>
              ${UI.options(allTypes(), goat ? goat.type : '', '')}
            </select>
          </div>

          <div class="form-group">
            <label>Jenis Kelamin <span class="req">*</span></label>
            <div class="segmented" role="radiogroup" aria-label="Jenis kelamin">
              <label><input type="radio" name="gender" value="Jantan" ${!goat || goat.gender === 'Jantan' ? 'checked' : ''}><span>♂ Jantan</span></label>
              <label><input type="radio" name="gender" value="Betina" ${goat && goat.gender === 'Betina' ? 'checked' : ''}><span>♀ Betina</span></label>
            </div>
          </div>

          <div class="form-group">
            <label for="goat-birth">Tanggal Lahir</label>
            <input id="goat-birth" type="date" name="birthDate" value="${U.escapeHtml(goat && goat.birthDate ? goat.birthDate : '')}">
            <p class="form-hint">Opsional, jika diketahui. Usia dihitung otomatis.</p>
          </div>

          <div class="form-group">
            <label for="goat-weight">Berat (kg) <span class="req">*</span></label>
            <div class="input-suffix">
              <input id="goat-weight" type="number" name="weight" min="0.1" max="500" step="0.1" inputmode="decimal" placeholder="35" value="${U.escapeHtml(goat ? goat.weight : '')}">
              <span>kg</span>
            </div>
          </div>

          <div class="form-group">
            <label for="goat-color">Warna</label>
            <input id="goat-color" type="text" name="color" maxlength="40" placeholder="Contoh: Cokelat" value="${U.escapeHtml(goat ? goat.color || '' : '')}">
          </div>

          <div class="form-group">
            <label for="goat-entry">Tanggal Masuk Inventaris <span class="req">*</span></label>
            <input id="goat-entry" type="date" name="entryDate" value="${U.escapeHtml(goat ? goat.entryDate : U.todayISO())}">
          </div>

          <div class="form-section full" data-origin-section="peranakan" ${origin === 'peranakan' ? '' : 'hidden'}>
            <div class="form-subgrid">
              <div class="form-group full">
                <label for="goat-mother">Induk Betina <span class="req">*</span></label>
                <select id="goat-mother" name="motherId">
                  <option value="">Pilih induk betina</option>
                  ${mothers.map((item) => `<option value="${U.escapeHtml(item.id)}" ${goat && goat.motherId === item.id ? 'selected' : ''}>${U.escapeHtml(item.id)} · ${U.escapeHtml(item.name)} (${U.escapeHtml(item.type)})</option>`).join('')}
                </select>
                ${mothers.length ? `<p class="form-hint">Hanya kambing betina yang sudah terdaftar yang dapat dipilih sebagai induk.</p>` : `<p class="form-hint form-hint-warning">Belum ada kambing betina terdaftar. Tambahkan kambing betina terlebih dahulu sebagai induk.</p>`}
              </div>
            </div>
            <div class="inline-alert inline-alert-info">${UI.icon('info', 16)}<span>Kambing peranakan langsung masuk inventaris tanpa membuat transaksi pembelian.</span></div>
          </div>

          <div class="form-section full" data-origin-section="pembelian" ${origin === 'pembelian' ? '' : 'hidden'}>
            <div class="form-subgrid">
              <div class="form-group">
                <label for="goat-purchase-date">Tanggal Pembelian <span class="req">*</span></label>
                <input id="goat-purchase-date" type="date" name="purchaseDate" value="${U.escapeHtml(goat && goat.purchaseDate ? goat.purchaseDate : U.todayISO())}">
              </div>
              <div class="form-group">
                <label for="goat-price">Harga Beli <span class="req">*</span></label>
                ${UI.moneyField('purchasePrice', goat && goat.purchasePrice ? goat.purchasePrice : '', { required: true })}
              </div>
              <div class="form-group full">
                <label for="goat-supplier">Supplier / Penjual <span class="req">*</span></label>
                <select id="goat-supplier" name="supplierId">
                  <option value="">Pilih supplier</option>
                  ${suppliers.map((supplier) => `<option value="${U.escapeHtml(supplier.id)}" ${goat && goat.supplierId === supplier.id ? 'selected' : ''}>${U.escapeHtml(supplier.name)} · ${U.escapeHtml(supplier.phone)}</option>`).join('')}
                </select>
                ${suppliers.length ? '' : `<p class="form-hint form-hint-warning">Belum ada supplier. <a class="inline-link" href="#/supplier">Tambahkan supplier</a> terlebih dahulu.</p>`}
              </div>
            </div>
            <div class="inline-alert inline-alert-info">${UI.icon('info', 16)}<span>Data pembelian ini otomatis tercatat sebagai transaksi pembelian.</span></div>
          </div>

          <div class="form-group full">
            <label for="goat-notes">Catatan</label>
            <textarea id="goat-notes" name="notes" rows="3" maxlength="400" placeholder="Informasi tambahan tentang kambing...">${U.escapeHtml(goat ? goat.notes || '' : '')}</textarea>
          </div>
        </div>
      </form>
    `;

    UI.openModal({
      title: isEdit ? 'Edit Data Kambing' : 'Tambah Kambing',
      subtitle: isEdit ? `${goat.id} · ${goat.barcode}` : 'Lengkapi data kambing untuk inventaris Anda.',
      size: 'lg',
      body: formBody,
      footer: `
        <button type="button" class="btn btn-outline" data-modal-close>Batal</button>
        <button type="submit" form="goat-form" class="btn btn-primary" data-save>${UI.icon(isEdit ? 'check' : 'plus', 16)} ${isEdit ? 'Simpan Perubahan' : 'Simpan Kambing'}</button>
      `,
      onMount(element, close) {
        const form = element.querySelector('#goat-form');
        UI.Forms.clearOnInput(form);
        const saveButton = element.querySelector('[data-save]');

        const updateSections = () => {
          if (isEdit) return;
          const checked = form.querySelector('input[name="origin"]:checked');
          const originValue = checked ? checked.value : 'pembelian';
          form.querySelectorAll('[data-origin-section]').forEach((section) => {
            section.hidden = section.dataset.originSection !== originValue;
          });
        };

        form.querySelectorAll('input[name="origin"]').forEach((radio) => {
          radio.addEventListener('change', updateSections);
        });
        updateSections();

        form.addEventListener('submit', (event) => {
          event.preventDefault();
          UI.Forms.clearErrors(form);
          const currentOrigin = isEdit
            ? goat.origin
            : (form.querySelector('input[name="origin"]:checked') || {}).value || 'pembelian';
          const name = UI.Forms.value(form, 'name');
          const type = UI.Forms.value(form, 'type');
          const gender = UI.Forms.value(form, 'gender');
          const birthDate = UI.Forms.value(form, 'birthDate');
          const weight = UI.Forms.number(form, 'weight');
          const entryDate = UI.Forms.value(form, 'entryDate');
          const motherId = UI.Forms.value(form, 'motherId');
          const purchaseDate = UI.Forms.value(form, 'purchaseDate');
          const purchasePrice = UI.Forms.number(form, 'purchasePrice');
          const supplierId = UI.Forms.value(form, 'supplierId');
          const errors = {};

          if (!name) errors.name = 'Nama kambing wajib diisi.';
          if (!type) errors.type = 'Pilih jenis kambing.';
          if (!gender) errors.gender = 'Pilih jenis kelamin.';
          if (!weight || weight <= 0) errors.weight = 'Berat harus lebih dari 0 kg.';
          if (weight > 500) errors.weight = 'Berat maksimal 500 kg.';
          if (!entryDate) errors.entryDate = 'Tanggal masuk wajib diisi.';
          if (birthDate && birthDate > U.todayISO()) errors.birthDate = 'Tanggal lahir tidak boleh di masa depan.';
          if (birthDate && entryDate && birthDate > entryDate) errors.birthDate = 'Tanggal lahir tidak boleh setelah tanggal masuk.';

          if (currentOrigin === 'peranakan') {
            if (!motherId) {
              errors.motherId = 'Pilih induk betina untuk kambing peranakan.';
            } else {
              const mother = S.getGoat(motherId);
              if (!mother || mother.gender !== 'Betina') errors.motherId = 'Induk yang dipilih tidak valid.';
            }
          } else {
            if (!purchaseDate) errors.purchaseDate = 'Tanggal pembelian wajib diisi.';
            if (purchaseDate && purchaseDate > U.todayISO()) errors.purchaseDate = 'Tanggal pembelian tidak boleh di masa depan.';
            if (!purchasePrice || purchasePrice <= 0) errors.purchasePrice = 'Harga beli harus lebih dari Rp0.';
            if (!supplierId) errors.supplierId = 'Pilih supplier / penjual.';
          }

          if (Object.keys(errors).length) {
            UI.Forms.showErrors(form, errors);
            return;
          }

          const restore = UI.Forms.setLoading(saveButton, isEdit ? 'Menyimpan...' : 'Menyimpan kambing...');
          const payload = {
            name,
            type,
            gender,
            birthDate: birthDate || null,
            weight,
            color: UI.Forms.value(form, 'color'),
            entryDate,
            notes: UI.Forms.value(form, 'notes'),
            motherId: currentOrigin === 'peranakan' ? motherId : null,
            purchaseDate: currentOrigin === 'pembelian' ? purchaseDate : null,
            purchasePrice: currentOrigin === 'pembelian' ? purchasePrice : null,
            supplierId: currentOrigin === 'pembelian' ? supplierId : null,
          };

          window.setTimeout(() => {
            try {
              if (isEdit) {
                S.updateGoat(goat.id, payload);
                UI.toast(`${name} berhasil diperbarui.`, 'success');
              } else if (currentOrigin === 'peranakan') {
                const created = S.addGoat(Object.assign({}, payload, { origin: 'peranakan' }));
                UI.toast(`${created.name} ditambahkan ke inventaris dengan barcode ${created.barcode}.`, 'success');
              } else {
                const result = S.createPurchase({
                  date: purchaseDate,
                  supplierId,
                  price: purchasePrice,
                  additionalCost: 0,
                  notes: payload.notes,
                  goat: Object.assign({}, payload, { notes: payload.notes }),
                });
                UI.toast(`${result.goat.name} ditambahkan dengan barcode ${result.goat.barcode} dan transaksi ${result.purchase.id}.`, 'success');
              }
              close();
              if (K.Router) K.Router.refresh();
            } catch (error) {
              restore();
              UI.toast(error && error.message ? error.message : 'Gagal menyimpan data kambing.', 'error');
            }
          }, 180);
        });
      },
    });
  }

  function confirmDelete(goatId) {
    const goat = S.getGoat(goatId);
    if (!goat) {
      UI.toast('Data kambing tidak ditemukan.', 'error');
      return;
    }
    const children = S.getChildren(goatId);
    if (children.length) {
      UI.toast(`Kambing ini merupakan induk dari ${children.length} kambing. Ubah relasi induk terlebih dahulu.`, 'warning');
      return;
    }
    const transactions = S.goatTransactions(goatId);
    UI.confirm({
      title: 'Hapus data kambing?',
      message: `Hapus "${goat.name}" (${goat.id}) dari inventaris?`,
      detail: transactions.length
        ? `${transactions.length} riwayat transaksi terkait juga akan dihapus. Tindakan ini tidak dapat dibatalkan.`
        : 'Data kambing dan barcode-nya akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.',
      confirmText: 'Ya, Hapus',
      onConfirm() {
        S.deleteGoat(goatId);
        UI.toast(`Data ${goat.name} berhasil dihapus.`, 'success');
        if (K.Router) K.Router.refresh();
      },
    });
  }

  function bindPage() {
    const content = document.getElementById('page-content');
    if (!content) return;
    const container = content.querySelector('#goats-table-container');
    const searchInput = content.querySelector('#goat-search');
    const typeFilter = content.querySelector('#goat-filter-type');
    const statusFilter = content.querySelector('#goat-filter-status');
    const originFilter = content.querySelector('#goat-filter-origin');

    const updateTable = () => {
      if (container) container.innerHTML = tableMarkup();
    };

    if (searchInput) {
      searchDebounce = U.debounce(() => {
        pageState.q = searchInput.value.trim();
        pageState.page = 1;
        updateTable();
      }, 250);
      searchInput.addEventListener('input', searchDebounce);
    }

    const onFilter = (key, element) => {
      if (!element) return;
      element.addEventListener('change', () => {
        pageState[key] = element.value;
        pageState.page = 1;
        updateTable();
      });
    };
    onFilter('type', typeFilter);
    onFilter('status', statusFilter);
    onFilter('origin', originFilter);

    const resetFilters = () => {
      pageState = defaults();
      if (searchInput) searchInput.value = '';
      if (typeFilter) typeFilter.value = '';
      if (statusFilter) statusFilter.value = '';
      if (originFilter) originFilter.value = '';
      updateTable();
    };

    content.querySelectorAll('[data-reset-filter], [data-reset]').forEach((button) => {
      button.addEventListener('click', resetFilters);
    });

    content.querySelectorAll('[data-add-goat]').forEach((button) => {
      button.addEventListener('click', () => openGoatForm());
    });

    if (container) {
      container.addEventListener('click', (event) => {
        const pageButton = event.target.closest('[data-page]');
        if (pageButton && !pageButton.disabled) {
          pageState.page = Number(pageButton.dataset.page) || 1;
          updateTable();
          return;
        }
        const editButton = event.target.closest('[data-edit]');
        if (editButton) {
          openGoatForm(editButton.dataset.edit);
          return;
        }
        const deleteButton = event.target.closest('[data-delete]');
        if (deleteButton) {
          confirmDelete(deleteButton.dataset.delete);
          return;
        }
        if (event.target.closest('[data-reset-filter]')) resetFilters();
      });
    }
  }

  K.Pages = K.Pages || {};
  K.Pages.goats = {
    title: 'Data Kambing',
    nav: '/kambing',
    breadcrumb: 'Data Kambing',

    render(ctx) {
      applyQuery(ctx.query);
      const state = S.getState();
      const total = state.goats.length;
      const available = state.goats.filter((goat) => goat.status === 'tersedia').length;
      const offspring = state.goats.filter((goat) => goat.origin === 'peranakan').length;

      return `
        ${UI.pageHead({
          eyebrow: 'Inventaris',
          title: 'Data Kambing',
          subtitle: `Kelola ${total} data kambing, barcode, asal-usul, dan status kepemilikan.`,
          actions: `
            <a class="btn btn-outline" href="#/cetak">${UI.icon('printer', 16)} Cetak Barcode</a>
            <button type="button" class="btn btn-primary" data-add-goat>${UI.icon('plus', 16)} Tambah Kambing</button>
          `,
        })}

        <div class="mini-stats">
          <span>${UI.icon('goat', 15)} <strong>${total}</strong> total kambing</span>
          <span>${UI.icon('check-circle', 15)} <strong>${available}</strong> tersedia</span>
          <span>${UI.icon('goat', 15)} <strong>${offspring}</strong> peranakan</span>
        </div>

        <section class="card">
          <div class="card-toolbar">
            <div class="search-field">
              ${UI.icon('search', 17)}
              <input id="goat-search" type="search" placeholder="Cari nama, ID, atau barcode..." value="${U.escapeHtml(pageState.q)}" aria-label="Cari kambing">
            </div>
            <div class="filter-group">
              <select id="goat-filter-type" class="filter-select" aria-label="Filter jenis kambing">
                ${UI.options(allTypes(), pageState.type, 'Semua jenis')}
              </select>
              <select id="goat-filter-status" class="filter-select" aria-label="Filter status">
                ${UI.options([{ value: 'tersedia', label: 'Tersedia' }, { value: 'terjual', label: 'Terjual' }], pageState.status, 'Semua status')}
              </select>
              <select id="goat-filter-origin" class="filter-select" aria-label="Filter asal kambing">
                ${UI.options([{ value: 'pembelian', label: 'Pembelian' }, { value: 'peranakan', label: 'Peranakan' }], pageState.origin, 'Semua asal')}
              </select>
              <button type="button" class="btn btn-ghost btn-sm" data-reset>${UI.icon('refresh', 15)} Reset</button>
            </div>
          </div>
          <div id="goats-table-container">${tableMarkup()}</div>
        </section>
      `;
    },

    mount(ctx) {
      bindPage();
      if (ctx.query && ctx.query.baru === '1') {
        window.history.replaceState(null, '', '#/kambing');
        lastQuerySig = '{}';
        window.setTimeout(() => openGoatForm(), 60);
      }
    },

    destroy() {
      if (searchDebounce && searchDebounce.cancel) searchDebounce.cancel();
    },

    openForm: openGoatForm,
    confirmDelete,
  };
})();
