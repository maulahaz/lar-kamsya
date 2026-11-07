/**
 * Kamsya - Halaman Dashboard
 */
(function () {
  'use strict';

  const K = (window.Kamsya = window.Kamsya || {});
  const U = K.Utils;
  const UI = K.UI;
  const S = K.Store;

  function monthlySeries() {
    const state = S.getState();
    const months = U.lastMonths(6, new Date());
    return months.map((month) => ({
      label: month.label,
      full: month.full,
      purchases: U.sum(state.purchases.filter((item) => U.monthKey(item.date) === month.key), (item) => item.total),
      sales: U.sum(state.sales.filter((item) => U.monthKey(item.date) === month.key), (item) => item.price),
    }));
  }

  function recentRows() {
    const transactions = S.getRecentTransactions(6);
    if (!transactions.length) {
      return `<tr><td colspan="6">${UI.emptyState({ icon: 'file-text', title: 'Belum ada transaksi', message: 'Transaksi pembelian dan penjualan akan tampil di sini.', action: '<a class="btn btn-primary btn-sm" href="#/pembelian?baru=1">Catat Pembelian</a>' })}</td></tr>`;
    }
    return transactions.map((trx) => `
      <tr>
        <td>
          <span class="txn-type ${trx.type === 'pembelian' ? 'txn-purchase' : 'txn-sale'}">
            ${UI.icon(trx.type === 'pembelian' ? 'cart' : 'tag', 14)}
            ${trx.type === 'pembelian' ? 'Pembelian' : 'Penjualan'}
          </span>
        </td>
        <td>
          <div class="cell-stack">
            <strong>${U.escapeHtml(trx.goatName)}</strong>
            <span class="cell-muted">${U.escapeHtml(trx.barcode)}</span>
          </div>
        </td>
        <td>${U.escapeHtml(trx.partyName)}</td>
        <td class="nowrap">${U.formatDate(trx.date, 'short')}</td>
        <td class="nowrap">
          <div class="cell-stack align-end">
            <strong>${U.formatRupiah(trx.amount)}</strong>
            ${trx.type === 'penjualan' ? `<span class="profit ${trx.profit >= 0 ? 'profit-positive' : 'profit-negative'}">${trx.profit >= 0 ? '+' : ''}${U.formatRupiah(trx.profit)}</span>` : `<span class="cell-muted">Total transaksi</span>`}
          </div>
        </td>
        <td class="cell-actions">
          <a class="action-btn" href="#/kambing/${encodeURIComponent(trx.goatId)}" title="Lihat kambing" aria-label="Lihat kambing ${U.escapeHtml(trx.goatName)}">${UI.icon('eye', 16)}</a>
        </td>
      </tr>
    `).join('');
  }

  function typeRows(stats) {
    if (!stats.byType.length) {
      return `<p class="muted small">Belum ada data jenis kambing.</p>`;
    }
    return stats.byType.map((item) => `
      <div class="type-row">
        <div class="type-row-head">
          <span>${U.escapeHtml(item.type)}</span>
          <span class="type-row-value">${item.available}<span class="muted">/${item.total}</span></span>
        </div>
        <div class="progress"><span style="width:${U.percent(item.available, item.total)}%"></span></div>
      </div>
    `).join('');
  }

  K.Pages = K.Pages || {};
  K.Pages.dashboard = {
    title: 'Dashboard',
    nav: '/dashboard',
    breadcrumb: 'Dashboard',

    render() {
      const state = S.getState();
      const stats = S.getStats();
      const settings = state.settings;
      const months = monthlySeries();
      const availableRatio = U.percent(stats.availableCount, stats.totalGoats);
      const purchaseTotal = U.sum(months, (month) => month.purchases);
      const salesTotal = U.sum(months, (month) => month.sales);
      const lowStock = stats.availableCount <= (Number(settings.lowStockLimit) || 0);

      const statsHtml = [
        UI.statCard({
          label: 'Total Kambing',
          value: U.formatNumber(stats.totalGoats),
          icon: 'goat',
          tone: 'green',
          href: '#/kambing',
          meta: `<span class="stat-hint">${stats.availableCount} tersedia · ${stats.soldCount} terjual</span>`,
        }),
        UI.statCard({
          label: 'Kambing Tersedia',
          value: U.formatNumber(stats.availableCount),
          icon: 'package',
          tone: 'blue',
          href: '#/kambing?status=tersedia',
          meta: `<span class="stat-hint">${stats.availablePurchased} pembelian · ${stats.availableOffspring} peranakan</span>`,
        }),
        UI.statCard({
          label: 'Kambing Terjual',
          value: U.formatNumber(stats.soldCount),
          icon: 'tag',
          tone: 'rose',
          href: '#/kambing?status=terjual',
          meta: `<span class="stat-hint">${stats.totalSaleCount} transaksi tercatat</span>`,
        }),
        UI.statCard({
          label: 'Total Pembelian',
          value: U.formatRupiah(stats.totalPurchases),
          icon: 'cart',
          tone: 'indigo',
          href: '#/pembelian',
          meta: `<span class="stat-hint">${stats.totalPurchaseCount} transaksi · ${U.formatCompactRupiah(stats.totalAdditional)} biaya tambahan</span>`,
        }),
        UI.statCard({
          label: 'Total Penjualan',
          value: U.formatRupiah(stats.totalSales),
          icon: 'wallet',
          tone: 'teal',
          href: '#/penjualan',
          meta: `<span class="stat-hint">${stats.totalSaleCount} transaksi penjualan</span>`,
        }),
        UI.statCard({
          label: 'Estimasi Keuntungan',
          value: U.formatRupiah(stats.grossProfit),
          icon: 'trending-up',
          tone: 'amber',
          href: '#/laporan',
          meta: `<span class="stat-hint"><span class="trend-up">${UI.icon('trending-up', 13)} ${stats.margin.toFixed(1)}%</span> margin kotor</span>`,
        }),
      ].join('');

      return `
        ${UI.pageHead({
          eyebrow: 'Dashboard',
          title: `${U.greeting()}, ${settings.ownerName || 'Admin'} 👋`,
          subtitle: 'Pantau stok kambing, transaksi, dan performa usaha Anda hari ini.',
        })}

        <section class="stats-grid" aria-label="Statistik utama">
          ${statsHtml}
        </section>

        <section class="quick-actions-card" aria-label="Aksi cepat">
          <div class="quick-actions-title">
            <span class="quick-icon">${UI.icon('sparkles', 17)}</span>
            <div>
              <strong>Aksi Cepat</strong>
              <span>Mulai pekerjaan harian Anda</span>
            </div>
          </div>
          <div class="quick-actions">
            <a class="quick-action" href="#/kambing?baru=1">
              <span class="quick-action-icon">${UI.icon('plus', 18)}</span>
              <span><strong>Tambah Kambing</strong><small>Catat kambing baru</small></span>
            </a>
            <a class="quick-action" href="#/scan">
              <span class="quick-action-icon">${UI.icon('scan', 18)}</span>
              <span><strong>Scan Barcode</strong><small>Cari data kambing</small></span>
            </a>
            <a class="quick-action" href="#/pembelian?baru=1">
              <span class="quick-action-icon">${UI.icon('cart', 18)}</span>
              <span><strong>Catat Pembelian</strong><small>Kambing masuk</small></span>
            </a>
            <a class="quick-action" href="#/penjualan?baru=1">
              <span class="quick-action-icon">${UI.icon('tag', 18)}</span>
              <span><strong>Catat Penjualan</strong><small>Kambing keluar</small></span>
            </a>
          </div>
        </section>

        <div class="dashboard-grid">
          <section class="card chart-card">
            <div class="card-header">
              <div>
                <h2>Arus Transaksi</h2>
                <p>Nilai pembelian dan penjualan 6 bulan terakhir</p>
              </div>
              <a class="btn btn-ghost btn-sm" href="#/laporan">Lihat laporan ${UI.icon('arrow-right', 15)}</a>
            </div>
            <div class="card-body">
              <div class="chart-split">
                <div class="chart-block">
                  <div class="chart-block-head">
                    <span><span class="legend-dot legend-purchase"></span>Pembelian</span>
                    <strong>${U.formatCompactRupiah(purchaseTotal)}</strong>
                  </div>
                  ${UI.barChart(months.map((month) => ({ label: month.label, full: month.full, value: month.purchases })), { color: 'green' })}
                </div>
                <div class="chart-block">
                  <div class="chart-block-head">
                    <span><span class="legend-dot legend-sale"></span>Penjualan</span>
                    <strong>${U.formatCompactRupiah(salesTotal)}</strong>
                  </div>
                  ${UI.barChart(months.map((month) => ({ label: month.label, full: month.full, value: month.sales })), { color: 'blue' })}
                </div>
              </div>
            </div>
          </section>

          <section class="card stock-card">
            <div class="card-header">
              <div>
                <h2>Ringkasan Stok</h2>
                <p>Komposisi kambing saat ini</p>
              </div>
              <a class="icon-link" href="#/kambing" title="Buka data kambing" aria-label="Buka data kambing">${UI.icon('arrow-right', 17)}</a>
            </div>
            <div class="card-body">
              <div class="stock-overview">
                <div class="donut" style="--value:${availableRatio}%">
                  <div class="donut-center">
                    <strong>${stats.availableCount}</strong>
                    <span>tersedia</span>
                  </div>
                </div>
                <div class="stock-legend">
                  <div><span class="legend-dot legend-available"></span><span>Tersedia</span><strong>${stats.availableCount}</strong></div>
                  <div><span class="legend-dot legend-sold"></span><span>Terjual</span><strong>${stats.soldCount}</strong></div>
                  <div><span class="legend-dot legend-total"></span><span>Total</span><strong>${stats.totalGoats}</strong></div>
                </div>
              </div>
              ${lowStock ? `<div class="inline-alert inline-alert-warning">${UI.icon('alert-triangle', 16)}<span>Stok tersedia tinggal ${stats.availableCount} ekor. Pertimbangkan menambah stok.</span></div>` : ''}
              <div class="type-list">
                <p class="section-label">Stok per jenis</p>
                ${typeRows(stats)}
              </div>
              <div class="stock-value">
                <span>Nilai stok tersedia</span>
                <strong>${U.formatRupiah(stats.inventoryValue)}</strong>
              </div>
            </div>
          </section>
        </div>

        <section class="card">
          <div class="card-header">
            <div>
              <h2>Transaksi Terbaru</h2>
              <p>Pembelian dan penjualan terakhir yang tercatat</p>
            </div>
            <a class="btn btn-outline btn-sm" href="#/laporan">Semua transaksi</a>
          </div>
          <div class="table-wrap">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Jenis</th>
                  <th>Kambing</th>
                  <th>Supplier / Pembeli</th>
                  <th>Tanggal</th>
                  <th class="align-end">Nominal</th>
                  <th class="th-actions">Aksi</th>
                </tr>
              </thead>
              <tbody>${recentRows()}</tbody>
            </table>
          </div>
        </section>
      `;
    },

    mount() {
      // Seluruh aksi dashboard menggunakan tautan hash, tidak perlu binding tambahan.
    },
  };
})();
