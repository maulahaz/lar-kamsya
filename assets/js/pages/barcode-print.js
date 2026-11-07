/**
 * Kamsya - Halaman Cetak Barcode
 */
(function () {
  'use strict';

  const K = (window.Kamsya = window.Kamsya || {});
  const U = K.Utils;
  const UI = K.UI;
  const S = K.Store;
  const B = K.Barcode;

  let selected = new Set();
  let initialized = false;
  let pickerQuery = '';
  let searchDebounce = null;

  function initSelection(ctx) {
    const query = ctx.query || {};
    if (query.goat) {
      selected = new Set([query.goat]);
      initialized = true;
    } else if (query.goats) {
      selected = new Set(String(query.goats).split(',').filter(Boolean));
      initialized = true;
    } else if (!initialized) {
      const available = S.getState().goats.filter((goat) => goat.status === 'tersedia').slice(0, 3);
      selected = new Set(available.map((goat) => goat.id));
      initialized = true;
    }
  }

  function selectedGoats() {
    return S.getState().goats
      .filter((goat) => selected.has(goat.id))
      .sort((a, b) => String(a.id).localeCompare(String(b.id)));
  }

  function pickerGoats() {
    const query = pickerQuery.toLowerCase();
    return S.getState().goats
      .filter((goat) => {
        if (!query) return true;
        return `${goat.name} ${goat.id} ${goat.barcode}`.toLowerCase().includes(query);
      })
      .sort((a, b) => {
        if (a.status !== b.status) return a.status === 'tersedia' ? -1 : 1;
        return String(a.id).localeCompare(String(b.id));
      });
  }

  function pickerMarkup() {
    const goats = pickerGoats();
    if (!goats.length) {
      return UI.emptyState({ icon: 'search', title: 'Kambing tidak ditemukan', message: 'Coba kata kunci lain untuk memilih kambing.' });
    }
    return goats.map((goat) => `
      <label class="picker-item ${selected.has(goat.id) ? 'is-selected' : ''}">
        <input type="checkbox" data-goat="${U.escapeHtml(goat.id)}" ${selected.has(goat.id) ? 'checked' : ''}>
        ${UI.goatAvatar(goat, 'sm')}
        <span class="picker-info">
          <strong>${U.escapeHtml(goat.name)}</strong>
          <small>${U.escapeHtml(goat.id)} · ${U.escapeHtml(goat.type)}</small>
          <span class="barcode-text">${U.escapeHtml(goat.barcode)}</span>
        </span>
        ${UI.statusBadge(goat.status)}
      </label>
    `).join('');
  }

  function labelMarkup(goat, settings) {
    return `
      <article class="barcode-label">
        <div class="label-brand">
          <span class="label-logo">🐐</span>
          <span class="label-brand-text"><strong>KAMSYA</strong><small>${U.escapeHtml(settings.farmName)}</small></span>
        </div>
        <div class="label-goat">
          <strong>${U.escapeHtml(goat.name)}</strong>
          <span>${U.escapeHtml(goat.id)} · ${U.escapeHtml(goat.type)} · ${U.escapeHtml(goat.gender)}</span>
        </div>
        <div class="label-barcode">${B.svg(goat.barcode, { height: 54, moduleWidth: 2.2, quietZone: 10 })}</div>
        <div class="label-code">${U.escapeHtml(goat.barcode)}</div>
        <div class="label-foot">${U.escapeHtml(settings.phone)} · ${U.escapeHtml(settings.address)}</div>
      </article>
    `;
  }

  function labelsMarkup() {
    const goats = selectedGoats();
    const settings = S.getState().settings;
    if (!goats.length) {
      return `<div class="label-empty">${UI.emptyState({
        icon: 'barcode',
        title: 'Belum ada label dipilih',
        message: 'Pilih kambing dari daftar di samping untuk melihat pratinjau dan mencetak label barcode.',
      })}</div>`;
    }
    return `<div class="label-grid">${goats.map((goat) => labelMarkup(goat, settings)).join('')}</div>`;
  }

  function updatePicker() {
    const container = document.getElementById('barcode-picker');
    if (container) container.innerHTML = pickerMarkup();
    const info = document.getElementById('picker-info');
    if (info) info.textContent = `${selected.size} kambing dipilih`;
    const count = document.getElementById('label-count');
    if (count) count.textContent = `${selected.size} label`;
    const printButtons = document.querySelectorAll('[data-print-labels]');
    printButtons.forEach((button) => {
      button.disabled = selected.size === 0;
    });
  }

  function updateLabels() {
    const area = document.getElementById('label-preview-area');
    if (area) area.innerHTML = labelsMarkup();
    updatePicker();
  }

  K.Pages = K.Pages || {};
  K.Pages.barcodePrint = {
    title: 'Cetak Barcode',
    nav: '/cetak',
    breadcrumb: 'Cetak Barcode',

    render(ctx) {
      initSelection(ctx);
      const settings = S.getState().settings;
      const goats = selectedGoats();
      if (ctx.query && ctx.query.goat && !S.getGoat(ctx.query.goat)) {
        selected.delete(ctx.query.goat);
      }

      return `
        ${UI.pageHead({
          eyebrow: 'Inventaris',
          title: 'Cetak Barcode',
          subtitle: 'Pilih kambing, pratinjau label, lalu cetak menggunakan browser.',
          actions: `
            <a class="btn btn-outline" href="#/kambing">${UI.icon('arrow-left', 16)} Data Kambing</a>
            <button type="button" class="btn btn-primary" data-print-labels ${selected.size ? '' : 'disabled'}>${UI.icon('printer', 16)} Cetak Label</button>
          `,
        })}

        <div class="print-layout">
          <section class="card print-controls no-print">
            <div class="card-header">
              <div>
                <h2>Pilih Kambing</h2>
                <p id="picker-info">${selected.size} kambing dipilih</p>
              </div>
              <span class="badge badge-neutral">${S.getState().goats.length} kambing</span>
            </div>
            <div class="picker-tools">
              <div class="search-field">
                ${UI.icon('search', 17)}
                <input id="picker-search" type="search" placeholder="Cari nama, ID, barcode..." value="${U.escapeHtml(pickerQuery)}" aria-label="Cari kambing untuk dicetak">
              </div>
              <div class="picker-actions">
                <button type="button" class="btn btn-ghost btn-sm" data-select-all>${UI.icon('check', 15)} Pilih semua</button>
                <button type="button" class="btn btn-ghost btn-sm" data-clear-all>${UI.icon('x', 15)} Kosongkan</button>
              </div>
            </div>
            <div class="picker-list" id="barcode-picker">${pickerMarkup()}</div>
            <div class="picker-note">${UI.icon('info', 15)} Label dirancang untuk kertas A4 dengan 2 label per baris.</div>
          </section>

          <section class="card label-preview-card">
            <div class="card-header no-print">
              <div>
                <h2>Pratinjau Label</h2>
                <p>Label siap dicetak dengan barcode unik setiap kambing</p>
              </div>
              <div class="preview-head-actions">
                <span class="badge badge-success" id="label-count">${goats.length} label</span>
                <button type="button" class="btn btn-primary btn-sm" data-print-labels ${goats.length ? '' : 'disabled'}>${UI.icon('printer', 15)} Cetak</button>
              </div>
            </div>
            <div class="label-preview-body">
              <div class="label-print-area" id="label-preview-area">${labelsMarkup()}</div>
            </div>
            <div class="print-footer-note no-print">
              ${UI.icon('info', 15)}
              <span>Pastikan ukuran kertas A4 dan opsi "Background graphics" aktif pada dialog cetak untuk hasil terbaik. Nama usaha: <strong>${U.escapeHtml(settings.farmName)}</strong>.</span>
            </div>
          </section>
        </div>
      `;
    },

    mount(ctx) {
      const content = document.getElementById('page-content');
      const picker = content.querySelector('#barcode-picker');
      const searchInput = content.querySelector('#picker-search');

      if (ctx.query && ctx.query.goat && !S.getGoat(ctx.query.goat)) {
        UI.toast('Kambing yang dipilih tidak ditemukan.', 'error');
      }

      if (searchInput) {
        searchDebounce = U.debounce(() => {
          pickerQuery = searchInput.value.trim();
          if (picker) picker.innerHTML = pickerMarkup();
        }, 250);
        searchInput.addEventListener('input', searchDebounce);
      }

      if (picker) {
        picker.addEventListener('change', (event) => {
          const checkbox = event.target.closest('[data-goat]');
          if (!checkbox) return;
          const id = checkbox.dataset.goat;
          if (checkbox.checked) selected.add(id);
          else selected.delete(id);
          checkbox.closest('.picker-item').classList.toggle('is-selected', checkbox.checked);
          updateLabels();
        });
      }

      const selectAll = content.querySelector('[data-select-all]');
      if (selectAll) {
        selectAll.addEventListener('click', () => {
          pickerGoats().forEach((goat) => selected.add(goat.id));
          if (picker) picker.innerHTML = pickerMarkup();
          updateLabels();
          UI.toast(`${selected.size} kambing dipilih untuk dicetak.`, 'info');
        });
      }

      const clearAll = content.querySelector('[data-clear-all]');
      if (clearAll) {
        clearAll.addEventListener('click', () => {
          selected.clear();
          if (picker) picker.innerHTML = pickerMarkup();
          updateLabels();
        });
      }

      content.querySelectorAll('[data-print-labels]').forEach((button) => {
        button.addEventListener('click', () => {
          if (!selected.size) {
            UI.toast('Pilih minimal satu kambing untuk dicetak.', 'warning');
            return;
          }
          UI.printPage('printing-labels');
        });
      });

      if (ctx.query && (ctx.query.goat || ctx.query.goats)) {
        window.history.replaceState(null, '', '#/cetak');
      }
    },

    destroy() {
      if (searchDebounce && searchDebounce.cancel) searchDebounce.cancel();
    },
  };
})();
