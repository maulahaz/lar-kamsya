/**
 * Kamsya - Application shell
 * Merender sidebar, topbar, dan menginisialisasi router.
 */
(function () {
  'use strict';

  const K = (window.Kamsya = window.Kamsya || {});
  const U = K.Utils;
  const UI = K.UI;
  const S = K.Store;

  const NAV = [
    {
      label: '',
      items: [{ path: '/dashboard', label: 'Dashboard', icon: 'dashboard' }],
    },
    {
      label: 'Inventaris',
      items: [
        { path: '/kambing', label: 'Data Kambing', icon: 'package', badge: 'available' },
        { path: '/scan', label: 'Scan Barcode', icon: 'scan' },
        { path: '/cetak', label: 'Cetak Barcode', icon: 'printer' },
      ],
    },
    {
      label: 'Transaksi',
      items: [
        { path: '/pembelian', label: 'Pembelian', icon: 'cart' },
        { path: '/penjualan', label: 'Penjualan', icon: 'tag' },
      ],
    },
    {
      label: 'Data Pendukung',
      items: [
        { path: '/supplier', label: 'Supplier', icon: 'truck' },
        { path: '/customer', label: 'Customer', icon: 'users' },
        { path: '/laporan', label: 'Laporan', icon: 'chart' },
      ],
    },
    {
      label: 'Pengaturan',
      items: [{ path: '/pengaturan', label: 'Pengaturan', icon: 'settings' }],
    },
  ];

  let activeNav = '/dashboard';
  let currentPage = null;
  let currentRoute = null;

  function renderSidebar() {
    const container = document.getElementById('sidebar-content');
    if (!container) return;
    const state = S.getState();
    const stats = S.getStats();
    const settings = state.settings;
    const available = stats.availableCount;

    container.innerHTML = `
      <div class="sidebar-brand">
        <span class="brand-mark">🐐</span>
        <div class="brand-copy">
          <strong>Kamsya</strong>
          <span>Livestock Manager</span>
        </div>
        <button type="button" class="sidebar-close" data-sidebar-close aria-label="Tutup menu">${UI.icon('x', 19)}</button>
      </div>

      <nav class="sidebar-nav" aria-label="Menu aplikasi">
        ${NAV.map((group) => `
          <div class="nav-group">
            ${group.label ? `<span class="nav-label">${U.escapeHtml(group.label)}</span>` : ''}
            ${group.items.map((item) => {
              const active = activeNav === item.path;
              const badge = item.badge === 'available' ? available : null;
              return `
                <a href="#${item.path}" class="nav-link ${active ? 'is-active' : ''}" ${active ? 'aria-current="page"' : ''} data-nav-path="${item.path}">
                  <span class="nav-icon">${UI.icon(item.icon, 18)}</span>
                  <span class="nav-text">${U.escapeHtml(item.label)}</span>
                  ${badge != null ? `<span class="nav-badge">${badge}</span>` : ''}
                </a>
              `;
            }).join('')}
          </div>
        `).join('')}
      </nav>

      <div class="sidebar-foot">
        <a class="sidebar-farm" href="#/pengaturan">
          <span class="farm-dot"></span>
          <span class="farm-copy">
            <strong>${U.escapeHtml(settings.farmName)}</strong>
            <small>${U.escapeHtml(settings.ownerName)}</small>
          </span>
          ${UI.icon('chevron-right', 15)}
        </a>
        <div class="sidebar-meta">
          <span class="demo-pill"><span></span> Mode Demo</span>
          <span>MVP Phase 1</span>
        </div>
      </div>
    `;
  }

  function renderTopbar() {
    const container = document.getElementById('topbar');
    if (!container) return;
    const settings = S.getState().settings;
    const breadcrumb = currentPage ? currentPage.breadcrumb || currentPage.title || 'Dashboard' : 'Dashboard';
    const today = U.formatDate(new Date(), 'long');

    container.innerHTML = `
      <div class="topbar-left">
        <button type="button" class="icon-btn menu-btn" data-sidebar-toggle aria-label="Buka menu" aria-controls="sidebar">${UI.icon('menu', 20)}</button>
        <div class="breadcrumb">
          <span class="crumb-muted">Kamsya</span>
          ${UI.icon('chevron-right', 14)}
          <strong>${U.escapeHtml(breadcrumb)}</strong>
        </div>
      </div>
      <div class="topbar-right">
        <span class="topbar-date">${UI.icon('calendar', 15)} ${U.escapeHtml(today)}</span>
        <a class="btn btn-outline btn-sm topbar-scan" href="#/scan">${UI.icon('scan', 16)} <span>Scan Barcode</span></a>
        <a class="icon-btn" href="#/pengaturan" title="Pengaturan" aria-label="Buka pengaturan">${UI.icon('settings', 18)}</a>
        <span class="topbar-divider" aria-hidden="true"></span>
        <div class="user-chip">
          <span class="user-avatar">${U.escapeHtml(U.initials(settings.ownerName || 'Admin'))}</span>
          <span class="user-copy">
            <strong>${U.escapeHtml(settings.ownerName || 'Admin')}</strong>
            <small>Administrator</small>
          </span>
        </div>
      </div>
    `;
  }

  function updateChrome(route, page) {
    currentRoute = route;
    currentPage = page;
    activeNav = page && page.nav ? page.nav : '';
    renderSidebar();
    renderTopbar();
    closeSidebar();
  }

  function refreshShell() {
    renderSidebar();
    renderTopbar();
    const year = document.getElementById('footer-year');
    if (year) year.textContent = String(new Date().getFullYear());
  }

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

  function bindGlobalEvents() {
    document.addEventListener('click', (event) => {
      if (event.target.closest('[data-sidebar-toggle]')) {
        openSidebar();
        return;
      }
      if (event.target.closest('[data-sidebar-close]')) {
        closeSidebar();
        return;
      }
      if (event.target.closest('.nav-link')) {
        closeSidebar();
      }
    });

    const overlay = document.getElementById('sidebar-overlay');
    if (overlay) overlay.addEventListener('click', closeSidebar);

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') closeSidebar();
    });

    window.addEventListener('resize', () => {
      if (window.innerWidth > 1024) closeSidebar();
    });
  }

  function init() {
    refreshShell();
    bindGlobalEvents();
    K.Router.init();
    if (!S.isPersistent()) {
      window.setTimeout(() => {
        UI.toast('Penyimpanan lokal tidak tersedia. Perubahan hanya tersimpan selama halaman terbuka.', 'warning', 'Mode Sementara');
      }, 900);
    }
  }

  K.App = {
    updateChrome,
    refreshShell,
    openSidebar,
    closeSidebar,
    getState: () => ({ route: currentRoute, page: currentPage }),
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
