/**
 * Kamsya - UI toolkit
 * Komponen antarmuka yang dipakai lintas halaman: ikon, toast, modal,
 * badge, pagination, grafik sederhana, dan helper form.
 */
(function () {
  'use strict';

  const K = (window.Kamsya = window.Kamsya || {});
  const U = K.Utils;
  const esc = U.escapeHtml;

  const ICONS = {
    dashboard: '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',
    goat: '<path d="M7 9C5.5 7.4 5.2 4.5 7 4.5c1.4 0 2 1.4 2.3 2.5"/><path d="M17 9c1.5-1.6 1.8-4.5 0-4.5-1.4 0-2 1.4-2.3 2.5"/><path d="M8 7.5h8a5 5 0 0 1-2.4 7.8V19h-3.2v-3.7A5 5 0 0 1 8 7.5Z"/><path d="M10.4 12.4h.01M13.6 12.4h.01"/>',
    package: '<path d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Z"/><path d="m4.3 7.6 7.7 4.3 7.7-4.3M12 21v-9.1"/>',
    barcode: '<path d="M4 6v12M7 6v12M10 6v8M13 6v12M17 6v8M20 6v12"/>',
    scan: '<path d="M4 8V6a2 2 0 0 1 2-2h2M16 4h2a2 2 0 0 1 2 2v2M20 16v2a2 2 0 0 1-2 2h-2M8 20H6a2 2 0 0 1-2-2v-2M4 12h16"/>',
    printer: '<path d="M6 9V4h12v5"/><path d="M6 18H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-1"/><rect x="6" y="14" width="12" height="7" rx="1"/>',
    cart: '<circle cx="9" cy="19" r="1.4"/><circle cx="17" cy="19" r="1.4"/><path d="M3 4h2l2.3 10.2a2 2 0 0 0 2 1.6h7.8a2 2 0 0 0 2-1.6L21 7H6"/>',
    tag: '<path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0l-7-7A2 2 0 0 1 3 12.2V5a2 2 0 0 1 2-2h7.2a2 2 0 0 1 1.4.6l7 7a2 2 0 0 1 0 2.8Z"/><circle cx="8" cy="8" r="1.3"/>',
    truck: '<path d="M3 6h11v10H3zM14 10h4l3 3v3h-7z"/><circle cx="7" cy="18" r="1.7"/><circle cx="17" cy="18" r="1.7"/>',
    users: '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="3.5"/><path d="M22 21v-2a4 4 0 0 0-3-3.8M16 3.2a3.5 3.5 0 0 1 0 6.6"/>',
    chart: '<path d="M4 20V10M10 20V4M16 20v-7M22 20V7"/>',
    settings: '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.9.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.9l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.9.3h.1a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.9-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.9v.1a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z"/>',
    menu: '<path d="M4 6h16M4 12h16M4 18h16"/>',
    plus: '<path d="M12 5v14M5 12h14"/>',
    search: '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
    filter: '<path d="M3 5h18l-7 8v6l-4 2v-8L3 5Z"/>',
    edit: '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
    trash: '<path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v6M14 11v6"/>',
    eye: '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>',
    'arrow-left': '<path d="M19 12H5M12 19l-7-7 7-7"/>',
    'arrow-right': '<path d="M5 12h14M12 5l7 7-7 7"/>',
    'chevron-left': '<path d="m15 18-6-6 6-6"/>',
    'chevron-right': '<path d="m9 18 6-6-6-6"/>',
    x: '<path d="M18 6 6 18M6 6l12 12"/>',
    check: '<path d="m20 6-11 11-5-5"/>',
    'check-circle': '<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>',
    'alert-triangle': '<path d="M10.3 3.7 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.7a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4M12 17h.01"/>',
    'alert-circle': '<circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/>',
    info: '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
    calendar: '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18"/>',
    phone: '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.4 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2Z"/>',
    'map-pin': '<path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
    scale: '<path d="M12 4v16M7 20h10M5 7h14M5 7l-3 7h6L5 7ZM19 7l-3 7h6l-3-7Z"/>',
    wallet: '<path d="M20 7V5a2 2 0 0 0-2-2H5a3 3 0 0 0 0 6h16v10a2 2 0 0 1-2 2H5a3 3 0 0 1-3-3V6"/><path d="M16 13h.01"/>',
    'trending-up': '<path d="m3 17 6-6 4 4 8-8M15 7h6v6"/>',
    'file-text': '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6M8 13h8M8 17h8"/>',
    refresh: '<path d="M21 12a9 9 0 1 1-3-6.7L21 8M21 3v5h-5"/>',
    download: '<path d="M12 3v12M7 10l5 5 5-5M5 21h14"/>',
    upload: '<path d="M12 21V9M7 14l5-5 5 5M5 3h14"/>',
    hash: '<path d="M4 9h16M4 15h16M10 3 8 21M16 3l-2 18"/>',
    clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    user: '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
    'map': '<path d="m3 6 6-3 6 3 6-3v15l-6 3-6-3-6 3V6ZM9 3v15M15 6v15"/>',
    layers: '<path d="m12 2 9 5-9 5-9-5 9-5Z"/><path d="m3 12 9 5 9-5M3 17l9 5 9-5"/>',
    'shopping-bag': '<path d="M6 8h12l1 13H5L6 8Z"/><path d="M9 10V6a3 3 0 0 1 6 0v4"/>',
    sparkles: '<path d="m12 3 1.3 4.2L17 9l-3.7 1.8L12 15l-1.3-4.2L7 9l3.7-1.8L12 3ZM19 15l.7 2.3L22 18l-2.3.7L19 21l-.7-2.3L16 18l2.3-.7L19 15ZM5 14l.6 1.9L8 16l-2.4.6L5 19l-.6-2.4L2 16l2.4-.1L5 14Z"/>',
  };

  function icon(name, size, className) {
    const content = ICONS[name] || ICONS.info;
    const dimension = size || 18;
    return `<svg class="icon${className ? ` ${className}` : ''}" width="${dimension}" height="${dimension}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${content}</svg>`;
  }

  /* ---------------------------------- Toast --------------------------------- */

  function toast(message, type, title) {
    const root = document.getElementById('toast-root');
    if (!root) return;
    const tone = type || 'success';
    const titles = {
      success: 'Berhasil',
      error: 'Gagal',
      warning: 'Perhatian',
      info: 'Informasi',
    };
    const icons = {
      success: 'check-circle',
      error: 'alert-circle',
      warning: 'alert-triangle',
      info: 'info',
    };
    const element = document.createElement('div');
    element.className = `toast toast-${tone}`;
    element.setAttribute('role', 'status');
    element.innerHTML = `
      <span class="toast-icon">${icon(icons[tone] || 'info', 19)}</span>
      <span class="toast-body">
        <strong>${esc(title || titles[tone] || 'Informasi')}</strong>
        <span>${esc(message)}</span>
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
    window.setTimeout(remove, 4800);
  }

  /* ---------------------------------- Modal --------------------------------- */

  let activeModal = null;
  let lastFocused = null;

  function closeModal() {
    if (!activeModal) return;
    const current = activeModal;
    activeModal = null;
    document.removeEventListener('keydown', current.onKeydown);
    current.backdrop.classList.remove('is-visible');
    window.setTimeout(() => {
      current.backdrop.remove();
      if (!document.querySelector('.modal-backdrop')) {
        document.body.classList.remove('modal-open');
      }
    }, 180);
    if (lastFocused && typeof lastFocused.focus === 'function') {
      window.setTimeout(() => lastFocused.focus(), 200);
    }
    if (typeof current.onClose === 'function') current.onClose();
  }

  function openModal(options) {
    const opts = options || {};
    closeModal();
    lastFocused = document.activeElement;
    const root = document.getElementById('modal-root');
    if (!root) return { close: closeModal, element: null };

    const backdrop = document.createElement('div');
    backdrop.className = 'modal-backdrop';
    backdrop.innerHTML = `
      <div class="modal-dialog modal-${opts.size || 'md'}" role="dialog" aria-modal="true" aria-labelledby="modal-title">
        <div class="modal-header">
          <div class="modal-heading">
            <h2 id="modal-title">${esc(opts.title || '')}</h2>
            ${opts.subtitle ? `<p>${esc(opts.subtitle)}</p>` : ''}
          </div>
          <button type="button" class="modal-close" data-modal-close aria-label="Tutup dialog">${icon('x', 19)}</button>
        </div>
        <div class="modal-body">${opts.body || ''}</div>
        ${opts.footer ? `<div class="modal-footer">${opts.footer}</div>` : ''}
      </div>
    `;
    root.appendChild(backdrop);
    document.body.classList.add('modal-open');

    const onKeydown = (event) => {
      if (event.key === 'Escape') closeModal();
    };

    activeModal = { backdrop, onKeydown, onClose: opts.onClose };

    backdrop.querySelectorAll('[data-modal-close]').forEach((button) => {
      button.addEventListener('click', closeModal);
    });
    if (opts.closeOnBackdrop !== false) {
      backdrop.addEventListener('mousedown', (event) => {
        if (event.target === backdrop) closeModal();
      });
    }
    document.addEventListener('keydown', onKeydown);

    window.requestAnimationFrame(() => {
      backdrop.classList.add('is-visible');
      const body = backdrop.querySelector('.modal-body');
      const target =
        (body && body.querySelector('[autofocus]')) ||
        (body && body.querySelector('input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled])')) ||
        backdrop.querySelector('.modal-footer .btn');
      if (target) window.setTimeout(() => target.focus(), 80);
    });

    if (typeof opts.onMount === 'function') {
      opts.onMount(backdrop, closeModal);
    }

    return { close: closeModal, element: backdrop };
  }

  function confirm(options) {
    const opts = options || {};
    const tone = opts.tone || 'danger';
    const component = openModal({
      title: opts.title || 'Konfirmasi',
      size: 'sm',
      closeOnBackdrop: false,
      body: `
        <div class="confirm-content confirm-${tone}">
          <span class="confirm-icon">${icon(tone === 'danger' ? 'trash' : 'alert-triangle', 24)}</span>
          <div>
            <p class="confirm-message">${esc(opts.message || 'Apakah Anda yakin?')}</p>
            ${opts.detail ? `<p class="confirm-detail">${esc(opts.detail)}</p>` : ''}
          </div>
        </div>
      `,
      footer: `
        <button type="button" class="btn btn-outline" data-cancel>${esc(opts.cancelText || 'Batal')}</button>
        <button type="button" class="btn btn-${tone}" data-confirm>${esc(opts.confirmText || 'Hapus')}</button>
      `,
      onMount(element, close) {
        element.querySelector('[data-cancel]').addEventListener('click', close);
        const confirmButton = element.querySelector('[data-confirm]');
        confirmButton.addEventListener('click', async () => {
          const restore = Forms.setLoading(confirmButton, opts.loadingText || 'Memproses...');
          try {
            if (typeof opts.onConfirm === 'function') await opts.onConfirm();
            close();
          } catch (error) {
            close();
            toast(error && error.message ? error.message : 'Terjadi kesalahan.', 'error');
          } finally {
            restore();
          }
        });
      },
    });
    return component;
  }

  /* -------------------------------- Components ------------------------------ */

  function pageHead(opts) {
    const data = opts || {};
    return `
      <div class="page-head">
        <div class="page-head-text">
          ${data.eyebrow ? `<span class="eyebrow">${esc(data.eyebrow)}</span>` : ''}
          <h1>${esc(data.title || '')}</h1>
          ${data.subtitle ? `<p>${esc(data.subtitle)}</p>` : ''}
        </div>
        ${data.actions ? `<div class="page-actions">${data.actions}</div>` : ''}
      </div>
    `;
  }

  function statCard(opts) {
    const data = opts || {};
    const tag = data.href ? 'a' : 'div';
    const attributes = data.href ? ` href="${esc(data.href)}"` : '';
    return `
      <${tag} class="stat-card stat-${data.tone || 'green'}"${attributes}>
        <span class="stat-icon">${icon(data.icon || 'chart', 21)}</span>
        <span class="stat-content">
          <span class="stat-label">${esc(data.label || '')}</span>
          <strong class="stat-value">${esc(data.value || '0')}</strong>
          ${data.meta ? `<span class="stat-meta">${data.meta}</span>` : ''}
        </span>
      </${tag}>
    `;
  }

  function emptyState(opts) {
    const data = opts || {};
    return `
      <div class="empty-state">
        <span class="empty-icon">${icon(data.icon || 'package', 30)}</span>
        <h3>${esc(data.title || 'Belum ada data')}</h3>
        <p>${esc(data.message || 'Data akan muncul di sini.')}</p>
        ${data.action ? `<div class="empty-action">${data.action}</div>` : ''}
      </div>
    `;
  }

  function statusBadge(status) {
    const sold = status === 'terjual';
    return `<span class="badge ${sold ? 'badge-danger' : 'badge-success'}"><span class="badge-dot"></span>${sold ? 'Terjual' : 'Tersedia'}</span>`;
  }

  function originBadge(origin) {
    return origin === 'peranakan'
      ? `<span class="badge badge-purple">${icon('goat', 13)} Peranakan</span>`
      : `<span class="badge badge-info">${icon('cart', 13)} Pembelian</span>`;
  }

  function typeBadge(type) {
    return `<span class="badge badge-neutral">${esc(type || '-')}</span>`;
  }

  function goatAvatar(goat, size) {
    const tone = U.typeTone(goat && goat.type);
    const name = goat && goat.name ? goat.name : 'Kambing';
    return `<span class="goat-avatar avatar-${size || 'md'} tone-${tone}" title="${esc(name)}" aria-hidden="true">🐐</span>`;
  }

  function personAvatar(name, tone, size) {
    return `<span class="person-avatar avatar-${size || 'md'} tone-${tone || 'green'}">${esc(U.initials(name))}</span>`;
  }

  function pagination(opts) {
    const data = opts || {};
    const total = Number(data.total) || 0;
    const perPage = Number(data.perPage) || 8;
    const pages = Math.max(1, Math.ceil(total / perPage));
    const page = Math.min(Math.max(1, Number(data.page) || 1), pages);
    const start = total ? (page - 1) * perPage + 1 : 0;
    const end = Math.min(page * perPage, total);

    let buttons = '';
    if (pages > 1) {
      const numbers = [];
      const from = Math.max(1, page - 2);
      const to = Math.min(pages, page + 2);
      for (let index = from; index <= to; index += 1) numbers.push(index);
      buttons = `
        <button type="button" class="page-btn" data-page="${page - 1}" ${page === 1 ? 'disabled' : ''} aria-label="Halaman sebelumnya">${icon('chevron-left', 16)}</button>
        ${from > 1 ? `<button type="button" class="page-btn" data-page="1">1</button>${from > 2 ? '<span class="page-ellipsis">…</span>' : ''}` : ''}
        ${numbers.map((number) => `<button type="button" class="page-btn ${number === page ? 'is-active' : ''}" data-page="${number}" ${number === page ? 'aria-current="page"' : ''}>${number}</button>`).join('')}
        ${to < pages ? `${to < pages - 1 ? '<span class="page-ellipsis">…</span>' : ''}<button type="button" class="page-btn" data-page="${pages}">${pages}</button>` : ''}
        <button type="button" class="page-btn" data-page="${page + 1}" ${page === pages ? 'disabled' : ''} aria-label="Halaman berikutnya">${icon('chevron-right', 16)}</button>
      `;
    }

    return `
      <div class="table-footer">
        <span class="table-info">${total ? `Menampilkan <strong>${start}–${end}</strong> dari <strong>${total}</strong> data` : 'Tidak ada data'}</span>
        ${buttons ? `<div class="pagination">${buttons}</div>` : ''}
      </div>
    `;
  }

  /* --------------------------------- Charts --------------------------------- */

  function barChart(points, options) {
    const opts = options || {};
    const data = Array.isArray(points) ? points : [];
    const values = data.map((point) => Number(point.value) || 0);
    const max = Math.max.apply(null, values.concat([1]));
    const color = opts.color || 'green';
    const format = opts.format || U.formatCompactRupiah;
    return `
      <div class="bar-chart chart-${color}">
        <div class="bar-chart-grid" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
        <div class="bar-chart-bars">
          ${data.map((point) => {
            const value = Number(point.value) || 0;
            const height = value > 0 ? Math.max(6, (value / max) * 100) : 0;
            return `
              <div class="bar-col" title="${esc(`${point.full || point.label}: ${format(value)}`)}">
                <span class="bar-value">${value > 0 ? esc(format(value)) : ''}</span>
                <div class="bar-track"><span class="bar-fill" style="height:${height}%"></span></div>
                <span class="bar-label">${esc(point.label || '')}</span>
              </div>
            `;
          }).join('')}
        </div>
      </div>
    `;
  }

  function groupedBarChart(points, options) {
    const opts = options || {};
    const data = Array.isArray(points) ? points : [];
    const max = Math.max.apply(null, data.reduce((values, point) => values.concat([Number(point.a) || 0, Number(point.b) || 0]), [1]));
    const format = opts.format || U.formatCompactRupiah;
    return `
      <div class="bar-chart grouped-chart">
        <div class="bar-chart-grid" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
        <div class="bar-chart-bars">
          ${data.map((point) => {
            const a = Number(point.a) || 0;
            const b = Number(point.b) || 0;
            const ha = a > 0 ? Math.max(5, (a / max) * 100) : 0;
            const hb = b > 0 ? Math.max(5, (b / max) * 100) : 0;
            return `
              <div class="bar-col" title="${esc(`${point.full || point.label} — Pembelian: ${format(a)}, Penjualan: ${format(b)}`)}">
                <div class="bar-pair">
                  <span class="bar-fill bar-purchase" style="height:${ha}%"></span>
                  <span class="bar-fill bar-sale" style="height:${hb}%"></span>
                </div>
                <span class="bar-label">${esc(point.label || '')}</span>
              </div>
            `;
          }).join('')}
        </div>
      </div>
    `;
  }

  /* ---------------------------------- Forms --------------------------------- */

  const Forms = {
    value(form, name) {
      if (!form) return '';
      const element = form.elements[name];
      if (!element) return '';
      if (typeof element.value === 'string') return element.value.trim();
      return '';
    },
    number(form, name) {
      const value = this.value(form, name);
      return Number(String(value).replace(/\./g, '').replace(',', '.')) || 0;
    },
    clearErrors(form) {
      if (!form) return;
      form.querySelectorAll('.has-error').forEach((element) => element.classList.remove('has-error'));
      form.querySelectorAll('.field-error').forEach((element) => element.remove());
      form.querySelectorAll('[aria-invalid="true"]').forEach((element) => element.removeAttribute('aria-invalid'));
    },
    showErrors(form, errors) {
      this.clearErrors(form);
      if (!form || !errors) return;
      let first = null;
      Object.keys(errors).forEach((name) => {
        const message = errors[name];
        if (!message) return;
        const nodes = form.querySelectorAll(`[name="${name.replace(/"/g, '\\"')}"]`);
        if (!nodes.length) return;
        const node = nodes[0];
        const group = node.closest('.form-group') || node.parentElement;
        if (!group) return;
        group.classList.add('has-error');
        nodes.forEach((input) => input.setAttribute('aria-invalid', 'true'));
        const error = document.createElement('p');
        error.className = 'field-error';
        error.textContent = message;
        group.appendChild(error);
        if (!first) first = node;
      });
      if (first) {
        first.focus({ preventScroll: true });
        const group = first.closest('.form-group') || first;
        group.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    },
    clearOnInput(form) {
      if (!form) return;
      const handler = (event) => {
        const group = event.target.closest('.form-group');
        if (!group) return;
        group.classList.remove('has-error');
        const error = group.querySelector('.field-error');
        if (error) error.remove();
        event.target.removeAttribute('aria-invalid');
      };
      form.addEventListener('input', handler);
      form.addEventListener('change', handler);
    },
    setLoading(button, text) {
      if (!button) return () => {};
      const original = button.innerHTML;
      button.disabled = true;
      button.classList.add('is-loading');
      button.innerHTML = `<span class="spinner"></span><span>${esc(text || 'Menyimpan...')}</span>`;
      return () => {
        button.disabled = false;
        button.classList.remove('is-loading');
        button.innerHTML = original;
      };
    },
  };

  function printPage(bodyClass) {
    const className = bodyClass || 'printing-page';
    document.body.classList.add(className);
    const cleanup = () => {
      document.body.classList.remove(className);
      window.removeEventListener('afterprint', cleanup);
    };
    window.addEventListener('afterprint', cleanup);
    window.setTimeout(() => {
      window.print();
      window.setTimeout(cleanup, 1500);
    }, 120);
  }

  function options(items, selected, placeholder) {
    const list = Array.isArray(items) ? items : [];
    return `${placeholder ? `<option value="">${esc(placeholder)}</option>` : ''}${list.map((item) => {
      const value = typeof item === 'string' ? item : item.value;
      const label = typeof item === 'string' ? item : item.label;
      const disabled = typeof item === 'object' && item.disabled;
      return `<option value="${esc(value)}" ${String(value) === String(selected == null ? '' : selected) ? 'selected' : ''} ${disabled ? 'disabled' : ''}>${esc(label)}</option>`;
    }).join('')}`;
  }

  function moneyField(name, value, options) {
    const opts = options || {};
    return `
      <div class="input-prefix">
        <span>Rp</span>
        <input type="number" name="${esc(name)}" min="0" step="1000" inputmode="numeric" placeholder="${esc(opts.placeholder || '0')}" value="${esc(value == null ? '' : value)}" ${opts.required ? 'required' : ''}>
      </div>
    `;
  }

  K.UI = {
    icon,
    toast,
    openModal,
    closeModal,
    confirm,
    pageHead,
    statCard,
    emptyState,
    statusBadge,
    originBadge,
    typeBadge,
    goatAvatar,
    personAvatar,
    pagination,
    barChart,
    groupedBarChart,
    Forms,
    printPage,
    options,
    moneyField,
  };
})();
