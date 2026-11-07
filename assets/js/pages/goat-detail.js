/**
 * Kamsya - Halaman Detail Kambing
 */
(function () {
  'use strict';

  const K = (window.Kamsya = window.Kamsya || {});
  const U = K.Utils;
  const UI = K.UI;
  const S = K.Store;
  const B = K.Barcode;

  function infoItem(label, value, extraClass) {
    return `<div class="info-item${extraClass ? ` ${extraClass}` : ''}"><span>${U.escapeHtml(label)}</span><strong>${value}</strong></div>`;
  }

  function supplierBlock(goat, purchase) {
    if (goat.origin !== 'pembelian') {
      const mother = S.getMother(goat);
      return `
        <div class="origin-panel origin-offspring">
          <div class="origin-panel-head">
            <span class="panel-icon">${UI.icon('goat', 20)}</span>
            <div>
              <strong>Kambing peranakan</strong>
              <span>Lahir dan tercatat di peternakan</span>
            </div>
          </div>
          <div class="info-grid">
            ${infoItem('Induk betina', mother ? `<a class="inline-link" href="#/kambing/${encodeURIComponent(mother.id)}">${U.escapeHtml(mother.name)}</a><span class="cell-muted block">${U.escapeHtml(mother.id)}</span>` : '<span class="muted">Tidak ditemukan</span>')}
            ${infoItem('Tanggal lahir', U.formatDate(goat.birthDate))}
            ${infoItem('Tanggal masuk', U.formatDate(goat.entryDate))}
            ${infoItem('Harga beli', '<span class="muted">Tidak ada (peranakan)</span>')}
          </div>
          ${mother ? `<a class="btn btn-outline btn-sm" href="#/kambing/${encodeURIComponent(mother.id)}">${UI.icon('eye', 15)} Lihat data induk</a>` : ''}
        </div>
      `;
    }

    const supplier = S.getSupplier(goat.supplierId);
    return `
      <div class="origin-panel origin-purchase">
        <div class="origin-panel-head">
          <span class="panel-icon">${UI.icon('truck', 20)}</span>
          <div>
            <strong>Kambing hasil pembelian</strong>
            <span>${purchase ? `Transaksi ${U.escapeHtml(purchase.id)}` : 'Transaksi pembelian tercatat'}</span>
          </div>
        </div>
        <div class="supplier-card">
          ${UI.personAvatar(supplier ? supplier.name : 'Supplier', 'green', 'md')}
          <div class="supplier-info">
            <strong>${U.escapeHtml(supplier ? supplier.name : '-')}</strong>
            <span>${UI.icon('phone', 13)} ${U.escapeHtml(supplier ? supplier.phone : '-')}</span>
            <span>${UI.icon('map-pin', 13)} ${U.escapeHtml(supplier ? supplier.address : '-')}</span>
          </div>
        </div>
        <div class="info-grid">
          ${infoItem('Tanggal pembelian', U.formatDate(goat.purchaseDate || (purchase && purchase.date)))}
          ${infoItem('Harga beli', U.formatRupiah(goat.purchasePrice))}
          ${infoItem('Biaya tambahan', purchase ? U.formatRupiah(purchase.additionalCost) : 'Rp0')}
          ${infoItem('Total pembelian', purchase ? U.formatRupiah(purchase.total) : U.formatRupiah(goat.purchasePrice), 'info-highlight')}
        </div>
        ${purchase && purchase.notes ? `<p class="panel-note">${UI.icon('file-text', 14)} ${U.escapeHtml(purchase.notes)}</p>` : ''}
      </div>
    `;
  }

  function childrenBlock(goat) {
    const children = S.getChildren(goat.id);
    if (!children.length) return '';
    return `
      <section class="card">
        <div class="card-header">
          <div>
            <h2>Keturunan</h2>
            <p>Kambing yang tercatat berasal dari induk ini</p>
          </div>
          <span class="badge badge-neutral">${children.length} ekor</span>
        </div>
        <div class="card-body">
          <div class="children-list">
            ${children.map((child) => `
              <a class="child-card" href="#/kambing/${encodeURIComponent(child.id)}">
                ${UI.goatAvatar(child, 'sm')}
                <span class="child-info">
                  <strong>${U.escapeHtml(child.name)}</strong>
                  <small>${U.escapeHtml(child.id)} · ${U.escapeHtml(child.type)} · ${U.ageText(child.birthDate)}</small>
                </span>
                ${UI.statusBadge(child.status)}
              </a>
            `).join('')}
          </div>
        </div>
      </section>
    `;
  }

  function transactionHistory(goat) {
    const transactions = S.goatTransactions(goat.id);
    if (!transactions.length) {
      return `<div class="card-body">${UI.emptyState({ icon: 'file-text', title: 'Belum ada transaksi', message: goat.origin === 'peranakan' ? 'Kambing peranakan tidak memiliki transaksi pembelian. Riwayat penjualan akan muncul setelah kambing dijual.' : 'Riwayat pembelian atau penjualan kambing akan tampil di sini.' })}</div>`;
    }
    return `
      <div class="transaction-timeline">
        ${transactions.map((entry) => {
          if (entry.type === 'pembelian') {
            const trx = entry.data;
            const supplier = S.getSupplier(trx.supplierId);
            return `
              <div class="timeline-item">
                <span class="timeline-icon timeline-purchase">${UI.icon('cart', 17)}</span>
                <div class="timeline-body">
                  <div class="timeline-head">
                    <strong>Pembelian kambing</strong>
                    <span class="timeline-amount">${U.formatRupiah(trx.total)}</span>
                  </div>
                  <div class="timeline-meta">
                    <span>${UI.icon('hash', 13)} ${U.escapeHtml(trx.id)}</span>
                    <span>${UI.icon('calendar', 13)} ${U.formatDate(trx.date)}</span>
                    <span>${UI.icon('truck', 13)} ${U.escapeHtml(supplier ? supplier.name : '-')}</span>
                  </div>
                  <div class="timeline-detail">
                    <span>Harga beli <strong>${U.formatRupiah(trx.price)}</strong></span>
                    <span>Biaya tambahan <strong>${U.formatRupiah(trx.additionalCost)}</strong></span>
                  </div>
                  ${trx.notes ? `<p class="timeline-note">${U.escapeHtml(trx.notes)}</p>` : ''}
                </div>
              </div>
            `;
          }
          const sale = entry.data;
          const customer = S.getCustomer(sale.customerId);
          const profit = (Number(sale.price) || 0) - (Number(sale.costPrice) || 0);
          return `
            <div class="timeline-item">
              <span class="timeline-icon timeline-sale">${UI.icon('tag', 17)}</span>
              <div class="timeline-body">
                <div class="timeline-head">
                  <strong>Penjualan kambing</strong>
                  <span class="timeline-amount">${U.formatRupiah(sale.price)}</span>
                </div>
                <div class="timeline-meta">
                  <span>${UI.icon('hash', 13)} ${U.escapeHtml(sale.id)}</span>
                  <span>${UI.icon('calendar', 13)} ${U.formatDate(sale.date)}</span>
                  <span>${UI.icon('user', 13)} ${U.escapeHtml(customer ? customer.name : '-')}</span>
                </div>
                <div class="timeline-detail">
                  <span>Harga beli <strong>${U.formatRupiah(sale.costPrice)}</strong></span>
                  <span>Keuntungan <strong class="${profit >= 0 ? 'text-success' : 'text-danger'}">${profit >= 0 ? '+' : ''}${U.formatRupiah(profit)}</strong></span>
                </div>
                ${sale.notes ? `<p class="timeline-note">${U.escapeHtml(sale.notes)}</p>` : ''}
              </div>
            </div>
          `;
        }).join('')}
      </div>
    `;
  }

  K.Pages = K.Pages || {};
  K.Pages.goatDetail = {
    title: 'Detail Kambing',
    nav: '/kambing',
    breadcrumb: 'Detail Kambing',

    render(ctx) {
      const goatId = ctx.params && ctx.params[0];
      const goat = goatId ? S.getGoat(goatId) : null;
      const actions = `
        <a class="btn btn-outline" href="#/kambing">${UI.icon('arrow-left', 16)} Kembali</a>
      `;

      if (!goat) {
        return `
          ${UI.pageHead({ eyebrow: 'Inventaris', title: 'Kambing Tidak Ditemukan', subtitle: 'Data kambing yang Anda cari tidak tersedia atau telah dihapus.', actions })}
          <section class="card">${UI.emptyState({ icon: 'alert-circle', title: 'Data tidak ditemukan', message: 'Periksa kembali barcode atau pilih kambing dari daftar inventaris.', action: '<a class="btn btn-primary btn-sm" href="#/kambing">Buka Data Kambing</a>' })}</section>
        `;
      }

      const supplier = S.getSupplier(goat.supplierId);
      const mother = S.getMother(goat);
      const purchase = S.getPurchaseForGoat(goat.id);
      const children = S.getChildren(goat.id);

      return `
        <div class="back-row">
          <a class="back-link" href="#/kambing">${UI.icon('arrow-left', 16)} Data Kambing</a>
          <span class="back-sep">/</span>
          <span>${U.escapeHtml(goat.id)}</span>
        </div>

        ${UI.pageHead({
          eyebrow: 'Detail Kambing',
          title: goat.name,
          subtitle: `${goat.id} · ${goat.type} · ${goat.gender} · ${U.formatWeight(goat.weight)}`,
          actions: `
            <button type="button" class="btn btn-outline" data-edit-goat>${UI.icon('edit', 16)} Edit Data</button>
            <a class="btn btn-outline" href="#/cetak?goat=${encodeURIComponent(goat.id)}">${UI.icon('printer', 16)} Cetak Barcode</a>
            ${goat.status === 'tersedia'
              ? `<button type="button" class="btn btn-primary" data-sell-goat>${UI.icon('tag', 16)} Catat Penjualan</button>`
              : `<span class="sold-flag">${UI.icon('check-circle', 16)} Kambing sudah terjual</span>`}
          `,
        })}

        <div class="detail-layout">
          <aside class="detail-side">
            <section class="card profile-card">
              <div class="profile-cover tone-${U.typeTone(goat.type)}">
                <span class="profile-pattern"></span>
                <span class="profile-avatar">🐐</span>
                <span class="profile-status">${UI.statusBadge(goat.status)}</span>
              </div>
              <div class="profile-body">
                <h2>${U.escapeHtml(goat.name)}</h2>
                <div class="profile-id">${U.escapeHtml(goat.id)} · <span class="barcode-text">${U.escapeHtml(goat.barcode)}</span></div>
                <div class="profile-chips">
                  ${UI.typeBadge(goat.type)}
                  <span class="badge badge-neutral">${U.genderIcon(goat.gender)} ${U.escapeHtml(goat.gender)}</span>
                  ${UI.originBadge(goat.origin)}
                </div>
                <div class="profile-stats">
                  <div><span>Berat</span><strong>${U.formatWeight(goat.weight)}</strong></div>
                  <div><span>Usia</span><strong>${U.escapeHtml(U.ageText(goat.birthDate))}</strong></div>
                  <div><span>Warna</span><strong>${U.escapeHtml(goat.color || '-')}</strong></div>
                  <div><span>Masuk</span><strong>${U.formatDate(goat.entryDate, 'short')}</strong></div>
                </div>
              </div>
            </section>

            <section class="card barcode-card">
              <div class="card-header">
                <div>
                  <h2>Barcode</h2>
                  <p>Identitas permanen kambing</p>
                </div>
                ${UI.icon('barcode', 19)}
              </div>
              <div class="card-body">
                <div class="barcode-box">${B.figure(goat.barcode, { height: 66 })}</div>
                <p class="barcode-help">Pindai atau masukkan nomor ini pada halaman Scan Barcode.</p>
                <a class="btn btn-outline btn-block" href="#/cetak?goat=${encodeURIComponent(goat.id)}">${UI.icon('printer', 16)} Cetak Label Barcode</a>
              </div>
            </section>

            <section class="card">
              <div class="card-header">
                <div>
                  <h2>Catatan</h2>
                  <p>Informasi tambahan</p>
                </div>
              </div>
              <div class="card-body">
                <p class="notes-text">${goat.notes ? U.escapeHtml(goat.notes) : '<span class="muted">Belum ada catatan untuk kambing ini.</span>'}</p>
              </div>
            </section>
          </aside>

          <div class="detail-main">
            <section class="card">
              <div class="card-header">
                <div>
                  <h2>Informasi Kambing</h2>
                  <p>Data lengkap identitas dan fisik kambing</p>
                </div>
                ${UI.statusBadge(goat.status)}
              </div>
              <div class="card-body">
                <div class="info-grid info-grid-detail">
                  ${infoItem('ID Kambing', `<span class="barcode-text">${U.escapeHtml(goat.id)}</span>`)}
                  ${infoItem('Barcode', `<span class="barcode-text">${U.escapeHtml(goat.barcode)}</span>`)}
                  ${infoItem('Jenis', U.escapeHtml(goat.type))}
                  ${infoItem('Jenis kelamin', `${U.genderIcon(goat.gender)} ${U.escapeHtml(goat.gender)}`)}
                  ${infoItem('Tanggal lahir', U.formatDate(goat.birthDate))}
                  ${infoItem('Usia', U.ageText(goat.birthDate))}
                  ${infoItem('Berat', U.formatWeight(goat.weight))}
                  ${infoItem('Warna', U.escapeHtml(goat.color || '-'))}
                  ${infoItem('Tanggal masuk', U.formatDate(goat.entryDate))}
                  ${infoItem('Status', UI.statusBadge(goat.status))}
                  ${infoItem('Asal kambing', UI.originBadge(goat.origin))}
                  ${infoItem('Supplier / induk', goat.origin === 'pembelian'
                    ? U.escapeHtml(supplier ? supplier.name : '-')
                    : (mother ? `<a class="inline-link" href="#/kambing/${encodeURIComponent(mother.id)}">${U.escapeHtml(mother.name)}</a>` : '-'))}
                </div>
              </div>
            </section>

            <section class="card">
              <div class="card-header">
                <div>
                  <h2>Asal-usul Kambing</h2>
                  <p>${goat.origin === 'pembelian' ? 'Informasi pembelian dan supplier' : 'Hubungan induk dan riwayat kelahiran'}</p>
                </div>
                ${children.length ? `<span class="badge badge-neutral">${children.length} keturunan</span>` : ''}
              </div>
              <div class="card-body">${supplierBlock(goat, purchase)}</div>
            </section>

            <section class="card">
              <div class="card-header">
                <div>
                  <h2>Riwayat Transaksi</h2>
                  <p>Pembelian dan penjualan yang terkait dengan kambing ini</p>
                </div>
                <a class="btn btn-ghost btn-sm" href="#/laporan">Lihat laporan ${UI.icon('arrow-right', 15)}</a>
              </div>
              ${transactionHistory(goat)}
            </section>

            ${childrenBlock(goat)}
          </div>
        </div>
      `;
    },

    mount(ctx) {
      const goatId = ctx.params && ctx.params[0];
      const goat = S.getGoat(goatId);
      if (!goat) return;
      const content = document.getElementById('page-content');
      const editButton = content.querySelector('[data-edit-goat]');
      if (editButton) {
        editButton.addEventListener('click', () => {
          if (K.Pages.goats && K.Pages.goats.openForm) K.Pages.goats.openForm(goat.id);
        });
      }
      const sellButton = content.querySelector('[data-sell-goat]');
      if (sellButton) {
        sellButton.addEventListener('click', () => {
          if (K.Pages.sales && K.Pages.sales.openForm) {
            K.Pages.sales.openForm(goat.id);
          } else {
            UI.toast('Form penjualan belum tersedia.', 'error');
          }
        });
      }
    },
  };
})();
