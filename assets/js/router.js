/**
 * Kamsya - Hash router
 * Routing sederhana tanpa reload halaman. Setiap halaman memiliki siklus
 * render/mount/destroy agar mudah dipindahkan ke Blade component nantinya.
 */
(function () {
  'use strict';

  const K = (window.Kamsya = window.Kamsya || {});
  const pages = K.Pages || {};

  let current = { route: null, page: null };
  let currentSignature = '';

  function parseHash() {
    let raw = window.location.hash ? window.location.hash.slice(1) : '';
    if (!raw) raw = '/dashboard';
    if (raw.charAt(0) !== '/') raw = `/${raw}`;
    const [pathPart, queryPart] = raw.split('?');
    const parts = pathPart.split('/').filter(Boolean).map((part) => {
      try {
        return decodeURIComponent(part);
      } catch (error) {
        return part;
      }
    });
    const query = {};
    const search = new URLSearchParams(queryPart || '');
    search.forEach((value, key) => {
      query[key] = value;
    });
    return {
      path: `/${parts.join('/')}` || '/',
      parts,
      query,
      signature: raw,
    };
  }

  function resolve(route) {
    const parts = route.parts;
    const head = parts[0] || 'dashboard';

    if (!parts.length || head === 'dashboard') return { page: pages.dashboard, params: [], nav: '/dashboard' };
    if (head === 'kambing') {
      if (parts.length > 1) return { page: pages.goatDetail, params: [parts[1]], nav: '/kambing' };
      return { page: pages.goats, params: [], nav: '/kambing' };
    }
    if (head === 'scan') return { page: pages.scan, params: [], nav: '/scan' };
    if (head === 'cetak') return { page: pages.barcodePrint, params: [], nav: '/cetak' };
    if (head === 'pembelian') return { page: pages.purchases, params: [], nav: '/pembelian' };
    if (head === 'penjualan') return { page: pages.sales, params: [], nav: '/penjualan' };
    if (head === 'supplier') return { page: pages.suppliers, params: [], nav: '/supplier' };
    if (head === 'customer') return { page: pages.customers, params: [], nav: '/customer' };
    if (head === 'laporan') return { page: pages.reports, params: [], nav: '/laporan' };
    if (head === 'pengaturan') return { page: pages.settings, params: [], nav: '/pengaturan' };
    return { page: null, params: [], nav: '' };
  }

  function notFoundPage(route) {
    return {
      title: 'Halaman Tidak Ditemukan',
      nav: '',
      breadcrumb: '404',
      render() {
        return `
          <div class="not-found">
            <span class="not-found-code">404</span>
            <h1>Halaman tidak ditemukan</h1>
            <p>Alamat <strong>#${K.Utils.escapeHtml(route.path)}</strong> tidak tersedia. Kembali ke dashboard untuk melanjutkan.</p>
            <a class="btn btn-primary" href="#/dashboard">${K.UI.icon('dashboard', 16)} Kembali ke Dashboard</a>
          </div>
        `;
      },
      mount() {},
      destroy() {},
    };
  }

  function renderCurrent(options) {
    const opts = options || {};
    const route = parseHash();
    const resolved = resolve(route);
    const page = resolved.page || notFoundPage(route);
    const content = document.getElementById('page-content');
    if (!content) return;

    if (current.page && typeof current.page.destroy === 'function') {
      try {
        current.page.destroy();
      } catch (error) {
        // Halaman tetap dirender meskipun cleanup gagal.
      }
    }

    if (K.UI) K.UI.closeModal();

    const ctx = {
      route,
      params: resolved.params,
      query: route.query,
    };

    content.innerHTML = page.render(ctx);
    content.classList.remove('page-enter');
    // Trigger ulang animasi masuk.
    void content.offsetWidth;
    content.classList.add('page-enter');

    if (K.App && K.App.updateChrome) K.App.updateChrome(route, page);
    document.title = `${page.title || 'Kamsya'} · Kamsya`;

    if (typeof page.mount === 'function') {
      page.mount(ctx);
    }

    current = { route, page };
    const signatureChanged = route.signature !== currentSignature;
    currentSignature = route.signature;
    if (signatureChanged && !opts.keepScroll) {
      window.scrollTo({ top: 0, behavior: 'auto' });
    }
  }

  function refresh() {
    renderCurrent({ keepScroll: true });
  }

  function navigate(path) {
    const target = String(path || '/dashboard');
    const hash = target.charAt(0) === '#' ? target : `#${target.charAt(0) === '/' ? '' : '/'}${target}`;
    if (window.location.hash === hash) {
      renderCurrent({ keepScroll: true });
    } else {
      window.location.hash = hash;
    }
  }

  function init() {
    if (!window.location.hash) {
      window.history.replaceState(null, '', '#/dashboard');
    }
    window.addEventListener('hashchange', () => renderCurrent());
    renderCurrent();
  }

  K.Router = {
    init,
    refresh,
    navigate,
    render: renderCurrent,
    parseHash,
    getCurrent: () => current,
  };
})();
