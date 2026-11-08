/**
 * Kamsya — application shell behaviours
 * Sidebar, flash toasts, modals, barcode rendering, and small form helpers.
 */
(function () {
  'use strict';

  const K = (window.Kamsya = window.Kamsya || {});

  const ICONS = {
    success: '<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>',
    error: '<circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/>',
    warning: '<path d="M10.3 3.7 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.7a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4M12 17h.01"/>',
    info: '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
  };

  function icon(name, size) {
    return `<svg class="icon" width="${size || 18}" height="${size || 18}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${ICONS[name] || ICONS.info}</svg>`;
  }

  function escapeHtml(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  /* ---------------------------------- Toast --------------------------------- */

  const TOAST_TITLES = { success: 'Berhasil', error: 'Gagal', warning: 'Perhatian', info: 'Informasi' };
  const TOAST_ICONS = { success: 'check-circle', error: 'alert-circle', warning: 'alert-triangle', info: 'info' };

  function toast(message, type, title) {
    const root = document.getElementById('toast-root');
    if (!root || !message) return;
    const tone = type || 'success';
    const element = document.createElement('div');
    element.className = `toast toast-${tone}`;
    element.setAttribute('role', 'status');
    element.innerHTML = `
      <span class="toast-icon">${icon(TOAST_ICONS[tone] || 'info', 19)}</span>
      <span class="toast-body">
        <strong>${escapeHtml(title || TOAST_TITLES[tone] || 'Informasi')}</strong>
        <span>${escapeHtml(message)}</span>
      </span>
      <button type="button" class="toast-close" aria-label="Tutup notifikasi">${icon('x', 16)}</button>
    `;
    root.appendChild(element);
    window.requestAnimationFrame(() => element.classList.add('is-visible'));

    const remove = () => {
      element.classList.remove('is-visible');
      window.setTimeout(() => element.remove(), 220);
    };

    element.querySelector('.toast-close').addEventListener('click', remove);
    window.setTimeout(remove, 5200);
  }

  K.toast = toast;

  /* ------------------------------ Flash messages ---------------------------- */

  function initFlash() {
    document.querySelectorAll('[data-flash]').forEach((node) => {
      const type = node.getAttribute('data-flash') || 'info';
      const message = node.getAttribute('data-message') || '';
      if (message) {
        window.setTimeout(() => toast(message, type), 80);
      }
      node.remove();
    });
  }

  /* --------------------------------- Sidebar -------------------------------- */

  function openSidebar() {
    document.body.classList.add('sidebar-open');
    const overlay = document.getElementById('sidebar-overlay');
    if (overlay) overlay.hidden = false;
  }

  function closeSidebar() {
    document.body.classList.remove('sidebar-open');
    const overlay = document.getElementById('sidebar-overlay');
    if (overlay) overlay.hidden = true;
  }

  function initSidebar() {
    document.addEventListener('click', (event) => {
      if (event.target.closest('[data-sidebar-toggle]')) {
        openSidebar();
        return;
      }
      if (event.target.closest('[data-sidebar-close]') || event.target.closest('.nav-link')) {
        closeSidebar();
      }
    });

    const overlay = document.getElementById('sidebar-overlay');
    if (overlay) overlay.addEventListener('click', closeSidebar);

    window.addEventListener('resize', () => {
      if (window.innerWidth > 1024) closeSidebar();
    });
  }

  /* ---------------------------------- Modals -------------------------------- */

  function closeModal(backdrop) {
    if (!backdrop) return;
    backdrop.classList.remove('is-visible');
    window.setTimeout(() => {
      backdrop.hidden = true;
      if (!document.querySelector('.modal-backdrop.is-visible')) {
        document.body.classList.remove('modal-open');
      }
    }, 180);
  }

  function openModal(id) {
    const backdrop = document.getElementById(id);
    if (!backdrop) return;
    backdrop.hidden = false;
    document.body.classList.add('modal-open');
    window.requestAnimationFrame(() => {
      backdrop.classList.add('is-visible');
      const target = backdrop.querySelector('[autofocus]')
        || backdrop.querySelector('input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled])')
        || backdrop.querySelector('.btn');
      if (target) window.setTimeout(() => target.focus(), 80);
    });
  }

  function initModals() {
    document.addEventListener('click', (event) => {
      const opener = event.target.closest('[data-modal-open]');
      if (opener) {
        event.preventDefault();
        openModal(opener.getAttribute('data-modal-open'));
        return;
      }

      const closer = event.target.closest('[data-modal-close]');
      if (closer) {
        event.preventDefault();
        closeModal(closer.closest('.modal-backdrop'));
        return;
      }

      if (event.target.classList.contains('modal-backdrop')) {
        closeModal(event.target);
      }
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        closeSidebar();
        document.querySelectorAll('.modal-backdrop.is-visible').forEach(closeModal);
      }
    });
  }

  /* -------------------------------- Confirm -------------------------------- */

  function confirmAction(message, detail, confirmLabel, tone) {
    return new Promise((resolve) => {
      const root = document.getElementById('modal-root');
      if (!root) {
        resolve(window.confirm(message));
        return;
      }

      const backdrop = document.createElement('div');
      backdrop.className = 'modal-backdrop';
      backdrop.innerHTML = `
        <div class="modal-dialog modal-sm" role="dialog" aria-modal="true" aria-labelledby="confirm-title">
          <div class="modal-header">
            <div class="modal-heading">
              <h2 id="confirm-title">Konfirmasi</h2>
            </div>
            <button type="button" class="modal-close" data-cancel aria-label="Tutup dialog">${icon('x', 19)}</button>
          </div>
          <div class="modal-body">
            <div class="confirm-content confirm-${tone || 'danger'}">
              <span class="confirm-icon">${icon(tone === 'warning' ? 'alert-triangle' : 'trash', 24)}</span>
              <div>
                <p class="confirm-message">${escapeHtml(message)}</p>
                ${detail ? `<p class="confirm-detail">${escapeHtml(detail)}</p>` : ''}
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline" data-cancel>Batal</button>
            <button type="button" class="btn btn-${tone || 'danger'}" data-confirm>${escapeHtml(confirmLabel || 'Lanjutkan')}</button>
          </div>
        </div>
      `;

      root.appendChild(backdrop);
      document.body.classList.add('modal-open');
      window.requestAnimationFrame(() => backdrop.classList.add('is-visible'));

      const finish = (value) => {
        backdrop.classList.remove('is-visible');
        window.setTimeout(() => {
          backdrop.remove();
          if (!document.querySelector('.modal-backdrop.is-visible')) {
            document.body.classList.remove('modal-open');
          }
        }, 180);
        resolve(value);
      };

      backdrop.querySelectorAll('[data-cancel]').forEach((button) => {
        button.addEventListener('click', () => finish(false));
      });

      backdrop.querySelector('[data-confirm]').addEventListener('click', () => finish(true));

      backdrop.addEventListener('mousedown', (event) => {
        if (event.target === backdrop) finish(false);
      });

      document.addEventListener('keydown', function onKey(event) {
        if (event.key === 'Escape') {
          document.removeEventListener('keydown', onKey);
          finish(false);
        }
      });
    });
  }

  function initConfirmForms() {
    document.addEventListener('submit', (event) => {
      const form = event.target;
      if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-confirm')) return;
      if (form.dataset.confirmed === '1') return;

      event.preventDefault();
      const message = form.getAttribute('data-confirm') || 'Apakah Anda yakin?';
      const detail = form.getAttribute('data-confirm-detail') || 'Tindakan ini akan tercatat di log aktivitas.';
      const label = form.getAttribute('data-confirm-label') || 'Lanjutkan';
      const tone = form.getAttribute('data-confirm-tone') || 'danger';

      confirmAction(message, detail, label, tone).then((ok) => {
        if (ok) {
          form.dataset.confirmed = '1';
          form.submit();
        }
      });
    });
  }

  /* -------------------------------- Barcodes -------------------------------- */

  function initBarcodes() {
    document.querySelectorAll('[data-barcode]').forEach((node) => {
      const value = node.getAttribute('data-barcode');
      if (!value || !K.Barcode) return;
      const height = Number(node.getAttribute('data-barcode-height')) || 52;
      const moduleWidth = Number(node.getAttribute('data-barcode-module')) || 2;
      node.innerHTML = K.Barcode.svg(value, { height, moduleWidth });
    });
  }

  /* --------------------------------- QR codes -------------------------------- */

  function renderQrCodes() {
    document.querySelectorAll('[data-qr]').forEach((node) => {
      if (node.dataset.qrRendered === '1') return;

      const value = node.getAttribute('data-qr');
      if (!value || typeof QRCode === 'undefined') return;

      const size = Number(node.getAttribute('data-qr-size')) || 120;
      node.innerHTML = '';
      new QRCode(node, {
        text: value,
        width: size,
        height: size,
        colorDark: '#111827',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.M,
      });
      node.dataset.qrRendered = '1';
    });
  }

  function initLabelFormat() {
    const form = document.querySelector('[data-label-format-form]');
    if (!form) return;

    const badge = document.querySelector('[data-label-format-badge]');
    const inputs = form.querySelectorAll('input[name="format"]');

    const apply = (format) => {
      const isQr = format === 'qr';
      form.classList.toggle('label-format-qr', isQr);
      form.classList.toggle('label-format-barcode', !isQr);
      if (badge) badge.textContent = isQr ? 'QR Code' : 'Barcode';
      if (isQr) renderQrCodes();
    };

    inputs.forEach((input) => {
      input.addEventListener('change', () => apply(input.value));
    });

    const checked = form.querySelector('input[name="format"]:checked');
    apply(checked ? checked.value : 'barcode');
  }

  /* ---------------------------------- Print --------------------------------- */

  function initPrint() {
    document.querySelectorAll('[data-print]').forEach((button) => {
      button.addEventListener('click', (event) => {
        event.preventDefault();
        const mode = button.getAttribute('data-print');
        if (mode) document.body.classList.add(`printing-${mode}`);
        window.print();
        window.setTimeout(() => document.body.classList.remove(`printing-${mode}`), 500);
      });
    });
  }

  /* --------------------------------- Helpers -------------------------------- */

  function initCheckAll() {
    document.querySelectorAll('[data-check-all]').forEach((master) => {
      const target = master.getAttribute('data-check-all');
      master.addEventListener('change', () => {
        document.querySelectorAll(target).forEach((box) => {
          box.checked = master.checked;
        });
      });
    });
  }

  function initLabelFilter() {
    const input = document.querySelector('[data-filter-labels]');
    if (!input) return;
    input.addEventListener('input', () => {
      const term = input.value.trim().toLowerCase();
      document.querySelectorAll('[data-label-item]').forEach((item) => {
        const text = item.textContent.toLowerCase();
        item.hidden = term !== '' && !text.includes(term);
      });
    });
  }

  function formatRupiah(value) {
    const number = Number(value) || 0;
    return 'Rp' + number.toLocaleString('id-ID', { maximumFractionDigits: 0 });
  }

  function initPurchaseTotals() {
    const form = document.querySelector('[data-total-form]');
    if (!form) return;
    const price = form.querySelector('[name="price"]');
    const additional = form.querySelector('[name="additional_cost"]');
    const output = form.querySelector('[data-total-output]');
    if (!price || !output) return;

    const update = () => {
      const total = (Number(price.value) || 0) + (Number(additional && additional.value) || 0);
      output.textContent = formatRupiah(total);
    };

    price.addEventListener('input', update);
    if (additional) additional.addEventListener('input', update);
    update();
  }

  function initSaleForm() {
    const select = document.querySelector('[data-sale-goat]');
    if (!select) return;

    const cost = document.querySelector('[data-sale-cost]');
    const barcode = document.querySelector('[data-sale-barcode]');
    const weight = document.querySelector('[data-sale-weight]');
    const price = document.querySelector('[data-sale-price]');
    const paid = document.querySelector('[data-sale-paid]');
    const remaining = document.querySelector('[data-sale-remaining]');
    const maxPaid = document.querySelector('[data-sale-max]');

    const update = () => {
      const option = select.options[select.selectedIndex];
      const costValue = option ? Number(option.getAttribute('data-cost')) || 0 : 0;
      if (cost) cost.textContent = formatRupiah(costValue);
      if (barcode) barcode.textContent = option ? option.getAttribute('data-barcode') || '-' : '-';
      if (weight) weight.textContent = option ? (option.getAttribute('data-weight') || '0') + ' kg' : '-';
      if (paid && price) {
        const priceValue = Number(price.value) || 0;
        const paidValue = Number(paid.value) || 0;
        if (maxPaid) maxPaid.textContent = formatRupiah(priceValue);
        if (remaining) remaining.textContent = formatRupiah(Math.max(0, priceValue - paidValue));
      }
    };

    select.addEventListener('change', update);
    if (price) price.addEventListener('input', update);
    if (paid) paid.addEventListener('input', update);
    update();
  }

  function initSaleDefaultPrice() {
    const select = document.querySelector('[data-sale-goat]');
    const price = document.querySelector('[data-sale-price]');
    if (!select || !price || price.value) return;
    const option = select.options[select.selectedIndex];
    if (option && option.getAttribute('data-price')) {
      price.value = option.getAttribute('data-price');
      price.dispatchEvent(new Event('input'));
    }
  }

  function initAutoSubmit() {
    document.querySelectorAll('[data-auto-submit]').forEach((field) => {
      field.addEventListener('change', () => field.form && field.form.submit());
    });
  }

  function init() {
    initFlash();
    initSidebar();
    initModals();
    initConfirmForms();
    initBarcodes();
    initLabelFormat();
    initPrint();
    initCheckAll();
    initLabelFilter();
    initPurchaseTotals();
    initSaleForm();
    initSaleDefaultPrice();
    initAutoSubmit();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
