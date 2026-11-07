/**
 * Kamsya - Halaman Laporan
 */
(function () {
  'use strict';

  const K = (window.Kamsya = window.Kamsya || {});
  const U = K.Utils;
  const UI = K.UI;
  const S = K.Store;

  function allDates() {
    const state = S.getState();
    return state.purchases.map((item) => item.date)
      .concat(state.sales.map((item) => item.date))
      .filter(Boolean)
      .sort();
  }

  function defaultRange() {
    const dates = allDates();
    const start = dates.length ? dates[0] : U.toISO(new Date(new Date().getFullYear(), 0, 1));
    return { start, end: U.todayISO() };
  }

  const initial = defaultRange();
  let pageState = { start: initial.start, end: initial.end };

  function monthsInRange(start, end) {
    const from = U.parseDate(start);
    const to = U.parseDate(end);
    if (!from || !to || from > to) return [];
    const months = [];
    let cursor = new Date(from.getFullYear(), from.getMonth(), 1);
    let guard = 0;
    while (cursor <= to && guard < 60) {
      months.push({
        key: U.monthKey(cursor),
        label: U.MONTHS_SHORT[cursor.getMonth()],
        full: `${U.MONTHS[cursor.getMonth()]} ${cursor.getFullYear()}`,
      });
      cursor = new Date(cursor.getFullYear(), cursor.getMonth() + 1, 1);
      guard += 1;
    }
    return months.length > 12 ? months.slice(months.length - 12) : months;
  }

  function filteredData() {
    const state = S.getState();
    return {
      purchases: state.purchases.filter((item) => U.isDateInRange(item.date, pageState.start, pageState.end)),
      sales: state.sales.filter((item) => U.isDateInRange(item.date, pageState.start, pageState.end)),
    };
  }

  function reportMarkup() {
    const data = filteredData();
    const stats = S.getStats();
    const totalPurchases = U.sum(data.purchases, (item) => item.total);
    const totalSales = U.sum(data.sales, (item) => item.price);
    const grossProfit = U.sum(data.sales, (item) => (Number(item.price) || 0) - (Number(item.costPrice) || 0));
    const additional = U.sum(data.purchases, (item) => item.additionalCost);
    const margin = totalSales ? (grossProfit / totalSales) * 100 : 0;
    const months = monthsInRange(pageState.start, pageState.end);
    const state = S.getState();
    const settings = state.settings;

    const monthly = months.map((month) => ({
      label: month.label,
      full: month.full,
      purchases: U.sum(data.purchases.filter((item) => U.monthKey(item.date) === month.key), (item) => item.total),
      sales: U.sum(data.sales.filter((item) => U.monthKey(item.date) === month.key), (item) => item.price),
      profit: U.sum(data.sales.filter((item) => U.monthKey(item.date) === month.key), (item) => (Number(item.price) || 0) - (Number(item.costPrice) || 0)),
    }));

    const purchaseRows = data.purchases.length
      ? data.purchases.slice().sort((a, b) => String(b.date).localeCompare(String(a.date))).map((purchase) => {
        const supplier = S.getSupplier(purchase.supplierId);
        const goat = S.getGoat(purchase.goatId);
        return `<tr>
          <td><span class="barcode-text">${U.escapeHtml(purchase.id)}</span></td>
          <td class="nowrap">${U.formatDate(purchase.date, 'short')}</td>
          <td>${U.escapeHtml(supplier ? supplier.name : '-')}</td>
          <td><a class="cell-title" href="#/kambing/${encodeURIComponent(purchase.goatId)}">${U.escapeHtml(goat ? goat.name : 'Kambing terhapus')}</a><span class="cell-muted block">${U.escapeHtml(goat ? goat.barcode : '-')}</span></td>
          <td class="nowrap">${U.formatRupiah(purchase.price)}</td>
          <td class="nowrap">${U.formatRupiah(purchase.additionalCost)}</td>
          <td class="nowrap"><strong>${U.formatRupiah(purchase.total)}</strong></td>
        </tr>`;
      }).join('')
      : `<tr><td colspan="7">${UI.emptyState({ icon: 'cart', title: 'Tidak ada pembelian', message: 'Tidak ada transaksi pembelian pada periode yang dipilih.' })}</td></tr>`;

    const salesRows = data.sales.length
      ? data.sales.slice().sort((a, b) => String(b.date).localeCompare(String(a.date))).map((sale) => {
        const goat = S.getGoat(sale.goatId);
        const customer = S.getCustomer(sale.customerId);
        const profit = (Number(sale.price) || 0) - (Number(sale.costPrice) || 0);
        return `<tr>
          <td><span class="barcode-text">${U.escapeHtml(sale.id)}</span></td>
          <td class="nowrap">${U.formatDate(sale.date, 'short')}</td>
          <td><a class="cell-title" href="#/kambing/${encodeURIComponent(sale.goatId)}">${U.escapeHtml(goat ? goat.name : 'Kambing terhapus')}</a><span class="cell-muted block">${U.escapeHtml(goat ? goat.barcode : '-')}</span></td>
          <td>${U.escapeHtml(customer ? customer.name : '-')}</td>
          <td class="nowrap">${U.formatRupiah(sale.costPrice)}</td>
          <td class="nowrap"><strong>${U.formatRupiah(sale.price)}</strong></td>
          <td class="nowrap"><span class="profit ${profit >= 0 ? 'profit-positive' : 'profit-negative'}">${profit >= 0 ? '+' : ''}${U.formatRupiah(profit)}</span></td>
        </tr>`;
      }).join('')
      : `<tr><td colspan="7">${UI.emptyState({ icon: 'tag', title: 'Tidak ada penjualan', message: 'Tidak ada transaksi penjualan pada periode yang dipilih.' })}</td></tr>`;

    const allPurchasesTotal = U.sum(data.purchases, (item) => item.total);
    const allSalesTotal = U.sum(data.sales, (item) => item.price);
    const allProfit = U.sum(data.sales, (item) => (Number(item.price) || 0) - (Number(item.costPrice) || 0));

    return `
      <div class="report-print-heading print-only">
        <div>
          <span class="print-brand">🐐 KAMSYA</span>
          <h2>Laporan Usaha</h2>
          <p>${U.escapeHtml(settings.farmName)} · ${U.escapeHtml(settings.ownerName)}</p>
          <p>${U.escapeHtml(settings.address)} · ${U.escapeHtml(settings.phone)}</p>
        </div>
        <div class="print-meta">
          <span>Periode</span>
          <strong>${U.formatDate(pageState.start)} – ${U.formatDate(pageState.end)}</strong>
          <span>Dicetak ${U.formatDate(U.todayISO())}</span>
        </div>
      </div>

      <section class="stats-grid stats-grid-4" aria-label="Statistik laporan">
        ${UI.statCard({ label: 'Total Pembelian', value: U.formatRupiah(totalPurchases), icon: 'cart', tone: 'indigo', meta: `${data.purchases.length} transaksi · ${U.formatRupiah(additional)} biaya tambahan` })}
        ${UI.statCard({ label: 'Total Penjualan', value: U.formatRupiah(totalSales), icon: 'wallet', tone: 'teal', meta: `${data.sales.length} transaksi penjualan` })}
        ${UI.statCard({ label: 'Keuntungan Kotor', value: U.formatRupiah(grossProfit), icon: 'trending-up', tone: 'amber', meta: `<span class="trend-up">${UI.icon('trending-up', 13)} ${margin.toFixed(1)}%</span> margin kotor` })}
        ${UI.statCard({ label: 'Stok Kambing Saat Ini', value: `${stats.availableCount} ekor`, icon: 'goat', tone: 'green', meta: `${stats.soldCount} terjual · ${stats.totalGoats} total kambing` })}
      </section>

      <div class="report-summary-strip">
        <span>${UI.icon('cart', 15)} <strong>${data.purchases.length}</strong> ekor dibeli</span>
        <span>${UI.icon('tag', 15)} <strong>${data.sales.length}</strong> ekor terjual</span>
        <span>${UI.icon('wallet', 15)} Nilai stok saat ini <strong>${U.formatRupiah(stats.inventoryValue)}</strong></span>
        <span>${UI.icon('calendar', 15)} Periode ${U.formatDate(pageState.start, 'short')} – ${U.formatDate(pageState.end, 'short')}</span>
      </div>

      <div class="report-charts">
        <section class="card">
          <div class="card-header">
            <div>
              <h2>Nilai Transaksi per Bulan</h2>
              <p>Perbandingan pembelian dan penjualan</p>
            </div>
            <div class="chart-legend">
              <span><span class="legend-dot legend-purchase"></span>Pembelian</span>
              <span><span class="legend-dot legend-sale"></span>Penjualan</span>
            </div>
          </div>
          <div class="card-body">
            ${monthly.length
              ? UI.groupedBarChart(monthly.map((month) => ({ label: month.label, full: month.full, a: month.purchases, b: month.sales })))
              : UI.emptyState({ icon: 'chart', title: 'Belum ada data', message: 'Tidak ada transaksi pada periode ini.' })}
          </div>
        </section>
        <section class="card">
          <div class="card-header">
            <div>
              <h2>Keuntungan per Bulan</h2>
              <p>Estimasi keuntungan kotor dari penjualan</p>
            </div>
            <span class="badge badge-success">${U.formatCompactRupiah(grossProfit)}</span>
          </div>
          <div class="card-body">
            ${monthly.some((month) => month.profit !== 0)
              ? UI.barChart(monthly.map((month) => ({ label: month.label, full: month.full, value: month.profit })), { color: 'amber' })
              : UI.emptyState({ icon: 'trending-up', title: 'Belum ada keuntungan', message: 'Keuntungan akan muncul setelah ada transaksi penjualan pada periode ini.' })}
          </div>
        </section>
      </div>

      <section class="card report-table-card">
        <div class="card-header">
          <div>
            <h2>Rekap Pembelian</h2>
            <p>${data.purchases.length} transaksi pembelian pada periode ini</p>
          </div>
          <span class="badge badge-info">${U.formatRupiah(allPurchasesTotal)}</span>
        </div>
        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr><th>No. Transaksi</th><th>Tanggal</th><th>Supplier</th><th>Kambing</th><th>Harga Beli</th><th>Biaya</th><th>Total</th></tr>
            </thead>
            <tbody>${purchaseRows}</tbody>
            ${data.purchases.length ? `<tfoot><tr><td colspan="6">Total pembelian</td><td><strong>${U.formatRupiah(allPurchasesTotal)}</strong></td></tr></tfoot>` : ''}
          </table>
        </div>
      </section>

      <section class="card report-table-card">
        <div class="card-header">
          <div>
            <h2>Rekap Penjualan</h2>
            <p>${data.sales.length} transaksi penjualan pada periode ini</p>
          </div>
          <span class="badge badge-success">${U.formatRupiah(allSalesTotal)}</span>
        </div>
        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr><th>No. Transaksi</th><th>Tanggal</th><th>Kambing</th><th>Pembeli</th><th>Harga Beli</th><th>Harga Jual</th><th>Keuntungan</th></tr>
            </thead>
            <tbody>${salesRows}</tbody>
            ${data.sales.length ? `<tfoot><tr><td colspan="5">Total penjualan</td><td><strong>${U.formatRupiah(allSalesTotal)}</strong></td><td><strong class="${allProfit >= 0 ? 'text-success' : 'text-danger'}">${U.formatRupiah(allProfit)}</strong></td></tr></tfoot>` : ''}
          </table>
        </div>
      </section>

      <div class="report-footnote">
        <span>Laporan dihitung dari data lokal Kamsya. Keuntungan kotor = harga jual − harga beli.</span>
      </div>
    `;
  }

  function updateReport() {
    const content = document.getElementById('report-content');
    if (content) content.innerHTML = reportMarkup();
  }

  function applyPreset(preset) {
    const today = new Date();
    const todayIso = U.toISO(today);
    if (preset === 'month') {
      pageState.start = U.toISO(new Date(today.getFullYear(), today.getMonth(), 1));
      pageState.end = todayIso;
    } else if (preset === 'quarter') {
      pageState.start = U.toISO(new Date(today.getFullYear(), today.getMonth() - 2, 1));
      pageState.end = todayIso;
    } else if (preset === 'year') {
      pageState.start = U.toISO(new Date(today.getFullYear(), 0, 1));
      pageState.end = todayIso;
    } else if (preset === 'all') {
      const range = defaultRange();
      pageState.start = range.start;
      pageState.end = U.todayISO();
    }
    const startInput = document.getElementById('report-start');
    const endInput = document.getElementById('report-end');
    if (startInput) startInput.value = pageState.start;
    if (endInput) endInput.value = pageState.end;
    updateReport();
  }

  K.Pages = K.Pages || {};
  K.Pages.reports = {
    title: 'Laporan',
    nav: '/laporan',
    breadcrumb: 'Laporan',

    render() {
      return `
        ${UI.pageHead({
          eyebrow: 'Laporan',
          title: 'Laporan Usaha',
          subtitle: 'Ringkasan pembelian, penjualan, keuntungan, dan stok kambing berdasarkan periode.',
          actions: `<button type="button" class="btn btn-primary" data-print-report>${UI.icon('printer', 16)} Cetak Laporan</button>`,
        })}

        <section class="card report-filter-card no-print">
          <form id="report-filter" class="report-filter">
            <div class="filter-title">${UI.icon('calendar', 17)} <span>Periode Laporan</span></div>
            <div class="filter-fields">
              <div class="form-group">
                <label for="report-start">Dari Tanggal</label>
                <input id="report-start" type="date" name="start" value="${U.escapeHtml(pageState.start)}">
              </div>
              <div class="form-group">
                <label for="report-end">Sampai Tanggal</label>
                <input id="report-end" type="date" name="end" value="${U.escapeHtml(pageState.end)}">
              </div>
              <button type="submit" class="btn btn-primary">${UI.icon('filter', 16)} Terapkan</button>
            </div>
            <div class="preset-group">
              <span>Pilih cepat:</span>
              <button type="button" class="preset-chip" data-preset="month">Bulan ini</button>
              <button type="button" class="preset-chip" data-preset="quarter">3 bulan terakhir</button>
              <button type="button" class="preset-chip" data-preset="year">Tahun ini</button>
              <button type="button" class="preset-chip" data-preset="all">Semua data</button>
            </div>
          </form>
        </section>

        <div id="report-content">${reportMarkup()}</div>
      `;
    },

    mount() {
      const content = document.getElementById('page-content');
      const form = content.querySelector('#report-filter');
      if (form) {
        form.addEventListener('submit', (event) => {
          event.preventDefault();
          const start = form.elements.start.value;
          const end = form.elements.end.value;
          if (!start || !end) {
            UI.toast('Tanggal awal dan akhir wajib diisi.', 'warning');
            return;
          }
          if (start > end) {
            UI.toast('Tanggal awal tidak boleh setelah tanggal akhir.', 'warning');
            return;
          }
          pageState.start = start;
          pageState.end = end;
          updateReport();
          UI.toast('Filter periode laporan diterapkan.', 'info');
        });
      }

      content.querySelectorAll('[data-preset]').forEach((button) => {
        button.addEventListener('click', () => {
          applyPreset(button.dataset.preset);
          content.querySelectorAll('[data-preset]').forEach((item) => item.classList.remove('is-active'));
          button.classList.add('is-active');
        });
      });

      const printButton = content.querySelector('[data-print-report]');
      if (printButton) {
        printButton.addEventListener('click', () => UI.printPage('printing-report'));
      }
    },
  };
})();
