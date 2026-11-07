/**
 * Kamsya - Data store
 * Sumber data tunggal untuk seluruh halaman. Data disimpan di localStorage
 * dengan fallback in-memory agar demo tetap berjalan.

/* eslint-disable no-use-before-define */
(function () {
  'use strict';

  const K = (window.Kamsya = window.Kamsya || {});
  const U = K.Utils;

  const STORAGE_KEY = 'kamsya.state.v1';
  const VERSION = 1;

  let state = null;
  let storageReady = false;

  function clone(value) {
    return JSON.parse(JSON.stringify(value));
  }

  function nowISO() {
    return new Date().toISOString();
  }

  function storageTest() {
    try {
      const key = '__kamsya_test__';
      window.localStorage.setItem(key, '1');
      window.localStorage.removeItem(key);
      return true;
    } catch (error) {
      return false;
    }
  }

  function maxSuffix(list, prefix) {
    return (list || []).reduce((max, item) => {
      const match = String(item.id || '').match(new RegExp(`^${prefix}-?(\\d+)$`));
      return match ? Math.max(max, Number(match[1])) : max;
    }, 0);
  }

  function normalizeState(input) {
    const seed = K.Seed ? clone(K.Seed) : {};
    const data = input && typeof input === 'object' ? input : {};
    const normalized = {
      version: VERSION,
      settings: Object.assign({}, seed.settings || {}, data.settings || {}),
      counters: Object.assign({}, seed.counters || {}, data.counters || {}),
      suppliers: Array.isArray(data.suppliers) ? data.suppliers : [],
      customers: Array.isArray(data.customers) ? data.customers : [],
      goats: Array.isArray(data.goats) ? data.goats : [],
      purchases: Array.isArray(data.purchases) ? data.purchases : [],
      sales: Array.isArray(data.sales) ? data.sales : [],
      scanHistory: Array.isArray(data.scanHistory) ? data.scanHistory : [],
    };

    normalized.counters.goat = Math.max(Number(normalized.counters.goat) || 0, maxSuffix(normalized.goats, 'KMB'));
    normalized.counters.supplier = Math.max(Number(normalized.counters.supplier) || 0, maxSuffix(normalized.suppliers, 'SUP'));
    normalized.counters.customer = Math.max(Number(normalized.counters.customer) || 0, maxSuffix(normalized.customers, 'CUS'));
    normalized.counters.purchase = Math.max(Number(normalized.counters.purchase) || 0, maxSuffix(normalized.purchases, 'TRX-B-\\d{4}'));
    normalized.counters.sale = Math.max(Number(normalized.counters.sale) || 0, maxSuffix(normalized.sales, 'TRX-J-\\d{4}'));

    const barcodeMax = normalized.goats.reduce((max, goat) => {
      const text = String(goat.barcode || '');
      const tail = Number(text.slice(-5));
      return isFinite(tail) ? Math.max(max, tail) : max;
    }, 0);
    normalized.counters.barcode = Math.max(Number(normalized.counters.barcode) || 0, barcodeMax, normalized.goats.length);

    normalized.sales = normalized.sales.map((sale) => {
      if (sale.costPrice != null) return sale;
      const goat = normalized.goats.find((item) => item.id === sale.goatId);
      return Object.assign({}, sale, { costPrice: goat ? Number(goat.purchasePrice) || 0 : 0 });
    });

    return normalized;
  }

  function loadState() {
    storageReady = storageTest();
    if (!storageReady) {
      return normalizeState(K.Seed ? clone(K.Seed) : {});
    }
    try {
      const raw = window.localStorage.getItem(STORAGE_KEY);
      if (raw) {
        const parsed = JSON.parse(raw);
        if (parsed && parsed.version === VERSION) return normalizeState(parsed);
      }
    } catch (error) {
      // Data rusak: gunakan seed baru.
    }
    const fresh = normalizeState(K.Seed ? clone(K.Seed) : {});
    try {
      window.localStorage.setItem(STORAGE_KEY, JSON.stringify(fresh));
    } catch (error) {
      storageReady = false;
    }
    return fresh;
  }

  function getState() {
    if (!state) state = loadState();
    return state;
  }

  function save() {
    if (!state) return false;
    if (!storageReady) return false;
    try {
      window.localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
      return true;
    } catch (error) {
      return false;
    }
  }

  function generateNextId(counterKey, prefix, length) {
    const counters = getState().counters;
    let id = '';
    do {
      counters[counterKey] = (Number(counters[counterKey]) || 0) + 1;
      id = `${prefix}${U.pad(counters[counterKey], length)}`;
    } while (getState().goats.some((goat) => goat.id === id));
    return id;
  }

  function barcodePrefix() {
    const prefix = String(getState().settings.barcodePrefix || '8991').replace(/\D/g, '').slice(0, 4);
    return prefix || '8991';
  }

  function peekNextBarcode() {
    const counters = getState().counters;
    let sequence = Number(counters.barcode) || 0;
    let barcode = '';
    do {
      sequence += 1;
      barcode = `${barcodePrefix()}${U.pad(sequence, 5)}`;
    } while (getState().goats.some((goat) => goat.barcode === barcode));
    return barcode;
  }

  function makeGoatRecord(raw) {
    const data = raw || {};
    const counters = getState().counters;
    const id = generateNextId('goat', 'KMB-', 4);
    let barcode = '';
    do {
      counters.barcode = (Number(counters.barcode) || 0) + 1;
      barcode = `${barcodePrefix()}${U.pad(counters.barcode, 5)}`;
    } while (getState().goats.some((goat) => goat.barcode === barcode));

    const origin = data.origin === 'peranakan' ? 'peranakan' : 'pembelian';
    const goat = {
      id,
      barcode,
      name: String(data.name || '').trim() || `Kambing ${id}`,
      type: String(data.type || 'Lainnya').trim(),
      gender: data.gender === 'Betina' ? 'Betina' : 'Jantan',
      birthDate: data.birthDate || null,
      weight: Math.max(0, Number(data.weight) || 0),
      color: String(data.color || '').trim() || '-',
      origin,
      motherId: origin === 'peranakan' ? data.motherId || null : null,
      entryDate: data.entryDate || U.todayISO(),
      purchaseDate: origin === 'pembelian' ? data.purchaseDate || data.entryDate || U.todayISO() : null,
      purchasePrice: origin === 'pembelian' ? Math.max(0, Number(data.purchasePrice) || 0) : null,
      supplierId: origin === 'pembelian' ? data.supplierId || null : null,
      purchaseId: null,
      status: data.status === 'terjual' ? 'terjual' : 'tersedia',
      notes: String(data.notes || '').trim(),
      createdAt: nowISO(),
    };
    getState().goats.push(goat);
    return goat;
  }

  function addGoat(raw) {
    const goat = makeGoatRecord(raw);
    save();
    return goat;
  }

  function updateGoat(id, patch) {
    const goat = getState().goats.find((item) => item.id === id);
    if (!goat) throw new Error('Data kambing tidak ditemukan.');
    const data = patch || {};

    const editable = ['name', 'type', 'gender', 'birthDate', 'weight', 'color', 'motherId', 'entryDate', 'purchaseDate', 'purchasePrice', 'supplierId', 'notes'];
    editable.forEach((key) => {
      if (Object.prototype.hasOwnProperty.call(data, key)) goat[key] = data[key];
    });

    if (goat.origin === 'peranakan') {
      goat.motherId = data.motherId || goat.motherId || null;
      goat.purchaseDate = null;
      goat.purchasePrice = null;
      goat.supplierId = null;
    } else {
      goat.motherId = null;
      const purchase = getState().purchases.find((item) => item.goatId === goat.id);
      if (purchase) {
        purchase.date = goat.purchaseDate || purchase.date;
        purchase.supplierId = goat.supplierId || purchase.supplierId;
        purchase.price = Math.max(0, Number(goat.purchasePrice) || 0);
        purchase.total = purchase.price + (Number(purchase.additionalCost) || 0);
      }
    }

    goat.weight = Math.max(0, Number(goat.weight) || 0);
    goat.updatedAt = nowISO();
    save();
    return goat;
  }

  function setGoatStatus(id, status) {
    const goat = getState().goats.find((item) => item.id === id);
    if (!goat) throw new Error('Data kambing tidak ditemukan.');
    goat.status = status === 'terjual' ? 'terjual' : 'tersedia';
    goat.updatedAt = nowISO();
    save();
    return goat;
  }

  function deleteGoat(id) {
    const data = getState();
    const goat = data.goats.find((item) => item.id === id);
    if (!goat) throw new Error('Data kambing tidak ditemukan.');
    const children = data.goats.filter((item) => item.motherId === id);
    if (children.length) {
      throw new Error(`Kambing ini merupakan induk dari ${children.length} kambing. Ubah relasi induk terlebih dahulu.`);
    }
    const purchaseCount = data.purchases.filter((item) => item.goatId === id).length;
    const saleCount = data.sales.filter((item) => item.goatId === id).length;
    data.goats = data.goats.filter((item) => item.id !== id);
    data.purchases = data.purchases.filter((item) => item.goatId !== id);
    data.sales = data.sales.filter((item) => item.goatId !== id);
    data.scanHistory = data.scanHistory.filter((item) => item.goatId !== id);
    save();
    return { purchaseCount, saleCount };
  }

  function createPurchase(input) {
    const data = input || {};
    const date = data.date || U.todayISO();
    const price = Math.max(0, Number(data.price) || 0);
    const additionalCost = Math.max(0, Number(data.additionalCost) || 0);
    const goatData = Object.assign({}, data.goat || {}, {
      origin: 'pembelian',
      entryDate: date,
      purchaseDate: date,
      purchasePrice: price,
      supplierId: data.supplierId,
      status: 'tersedia',
      notes: (data.goat && data.goat.notes) || data.notes || '',
    });

    const goat = makeGoatRecord(goatData);

    let purchaseId = '';
    const year = (U.parseDate(date) || new Date()).getFullYear();
    do {
      getState().counters.purchase = (Number(getState().counters.purchase) || 0) + 1;
      purchaseId = `TRX-B-${year}-${U.pad(getState().counters.purchase, 3)}`;
    } while (getState().purchases.some((item) => item.id === purchaseId));

    const purchase = {
      id: purchaseId,
      date,
      supplierId: data.supplierId || null,
      goatId: goat.id,
      price,
      additionalCost,
      total: price + additionalCost,
      notes: String(data.notes || '').trim(),
      createdAt: nowISO(),
    };
    getState().purchases.push(purchase);
    goat.purchaseId = purchaseId;
    save();
    return { goat, purchase };
  }

  function addSale(input) {
    const data = input || {};
    const goat = getState().goats.find((item) => item.id === data.goatId);
    if (!goat) throw new Error('Pilih kambing yang akan dijual.');
    if (goat.status !== 'tersedia') throw new Error('Kambing ini sudah terjual dan tidak dapat dijual kembali.');
    const price = Math.max(0, Number(data.price) || 0);
    if (!price) throw new Error('Harga jual harus lebih dari Rp0.');

    const date = data.date || U.todayISO();
    let saleId = '';
    const year = (U.parseDate(date) || new Date()).getFullYear();
    do {
      getState().counters.sale = (Number(getState().counters.sale) || 0) + 1;
      saleId = `TRX-J-${year}-${U.pad(getState().counters.sale, 3)}`;
    } while (getState().sales.some((item) => item.id === saleId));

    const sale = {
      id: saleId,
      date,
      goatId: goat.id,
      customerId: data.customerId || null,
      price,
      costPrice: Number(goat.purchasePrice) || 0,
      notes: String(data.notes || '').trim(),
      createdAt: nowISO(),
    };
    getState().sales.push(sale);
    goat.status = 'terjual';
    goat.updatedAt = nowISO();
    save();
    return sale;
  }

  function addSupplier(raw) {
    const data = raw || {};
    const supplier = {
      id: generateNextId('supplier', 'SUP-', 3),
      name: String(data.name || '').trim(),
      phone: String(data.phone || '').trim(),
      address: String(data.address || '').trim(),
      notes: String(data.notes || '').trim(),
      createdAt: nowISO(),
    };
    getState().suppliers.push(supplier);
    save();
    return supplier;
  }

  function updateSupplier(id, raw) {
    const supplier = getState().suppliers.find((item) => item.id === id);
    if (!supplier) throw new Error('Supplier tidak ditemukan.');
    const data = raw || {};
    ['name', 'phone', 'address', 'notes'].forEach((key) => {
      if (Object.prototype.hasOwnProperty.call(data, key)) supplier[key] = String(data[key] || '').trim();
    });
    save();
    return supplier;
  }

  function deleteSupplier(id) {
    const used = getState().purchases.filter((item) => item.supplierId === id).length;
    if (used > 0) throw new Error(`Supplier masih digunakan pada ${used} transaksi pembelian.`);
    getState().suppliers = getState().suppliers.filter((item) => item.id !== id);
    save();
  }

  function addCustomer(raw) {
    const data = raw || {};
    const customer = {
      id: generateNextId('customer', 'CUS-', 3),
      name: String(data.name || '').trim(),
      phone: String(data.phone || '').trim(),
      address: String(data.address || '').trim(),
      notes: String(data.notes || '').trim(),
      createdAt: nowISO(),
    };
    getState().customers.push(customer);
    save();
    return customer;
  }

  function updateCustomer(id, raw) {
    const customer = getState().customers.find((item) => item.id === id);
    if (!customer) throw new Error('Customer tidak ditemukan.');
    const data = raw || {};
    ['name', 'phone', 'address', 'notes'].forEach((key) => {
      if (Object.prototype.hasOwnProperty.call(data, key)) customer[key] = String(data[key] || '').trim();
    });
    save();
    return customer;
  }

  function deleteCustomer(id) {
    const used = getState().sales.filter((item) => item.customerId === id).length;
    if (used > 0) throw new Error(`Customer masih digunakan pada ${used} transaksi penjualan.`);
    getState().customers = getState().customers.filter((item) => item.id !== id);
    save();
  }

  function recordScan(input) {
    const value = String(input || '').trim();
    const goat = findByBarcode(value);
    const entry = {
      barcode: value,
      goatId: goat ? goat.id : null,
      found: Boolean(goat),
      at: nowISO(),
    };
    const history = getState().scanHistory.filter((item) => item.barcode.toLowerCase() !== value.toLowerCase());
    getState().scanHistory = [entry].concat(history).slice(0, 8);
    save();
    return entry;
  }

  function clearScanHistory() {
    getState().scanHistory = [];
    save();
  }

  function updateSettings(raw) {
    const data = raw || {};
    const settings = getState().settings;
    ['farmName', 'ownerName', 'phone', 'address'].forEach((key) => {
      if (Object.prototype.hasOwnProperty.call(data, key)) settings[key] = String(data[key] || '').trim();
    });
    if (Object.prototype.hasOwnProperty.call(data, 'barcodePrefix')) {
      const prefix = String(data.barcodePrefix || '').replace(/\D/g, '').slice(0, 4);
      settings.barcodePrefix = prefix || '8991';
    }
    if (Object.prototype.hasOwnProperty.call(data, 'lowStockLimit')) {
      settings.lowStockLimit = Math.max(0, Number(data.lowStockLimit) || 0);
    }
    settings.updatedAt = nowISO();
    save();
    return settings;
  }

  function exportJSON() {
    return JSON.stringify(getState(), null, 2);
  }

  function importJSON(text) {
    let parsed;
    try {
      parsed = JSON.parse(text);
    } catch (error) {
      throw new Error('File JSON tidak valid atau rusak.');
    }
    if (!parsed || !Array.isArray(parsed.goats) || !Array.isArray(parsed.purchases) || !Array.isArray(parsed.sales)) {
      throw new Error('Struktur data tidak dikenali. Gunakan file backup Kamsya.');
    }
    state = normalizeState(parsed);
    state.version = VERSION;
    save();
    return state;
  }

  function reset() {
    state = normalizeState(K.Seed ? clone(K.Seed) : {});
    try {
      if (storageReady) window.localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
    } catch (error) {
      // Abaikan, data tetap tersedia di memori.
    }
    return state;
  }

  function getGoat(id) {
    return getState().goats.find((item) => item.id === id) || null;
  }

  function findByBarcode(value) {
    const text = String(value || '').trim().toLowerCase();
    if (!text) return null;
    return getState().goats.find((goat) => String(goat.barcode).toLowerCase() === text || String(goat.id).toLowerCase() === text) || null;
  }

  function getSupplier(id) {
    return getState().suppliers.find((item) => item.id === id) || null;
  }

  function getCustomer(id) {
    return getState().customers.find((item) => item.id === id) || null;
  }

  function getMother(goat) {
    if (!goat || !goat.motherId) return null;
    return getGoat(goat.motherId);
  }

  function getChildren(goatId) {
    return getState().goats.filter((item) => item.motherId === goatId);
  }

  function getPurchaseForGoat(goatId) {
    return getState().purchases.find((item) => item.goatId === goatId) || null;
  }

  function getSalesForGoat(goatId) {
    return getState().sales.filter((item) => item.goatId === goatId);
  }

  function goatTransactions(goatId) {
    const purchases = getState().purchases
      .filter((item) => item.goatId === goatId)
      .map((item) => ({ type: 'pembelian', data: item }));
    const sales = getState().sales
      .filter((item) => item.goatId === goatId)
      .map((item) => ({ type: 'penjualan', data: item }));
    return purchases.concat(sales).sort((a, b) => {
      const dateA = U.parseDate(a.data.date);
      const dateB = U.parseDate(b.data.date);
      return (dateB ? dateB.getTime() : 0) - (dateA ? dateA.getTime() : 0);
    });
  }

  function getRecentTransactions(limit) {
    const data = getState();
    const purchases = data.purchases.map((item) => {
      const goat = getGoat(item.goatId);
      const supplier = getSupplier(item.supplierId);
      return {
        id: item.id,
        type: 'pembelian',
        date: item.date,
        goatId: item.goatId,
        goatName: goat ? goat.name : 'Kambing terhapus',
        barcode: goat ? goat.barcode : '-',
        partyName: supplier ? supplier.name : '-',
        amount: item.total,
        profit: null,
        createdAt: item.createdAt,
      };
    });
    const sales = data.sales.map((item) => {
      const goat = getGoat(item.goatId);
      const customer = getCustomer(item.customerId);
      return {
        id: item.id,
        type: 'penjualan',
        date: item.date,
        goatId: item.goatId,
        goatName: goat ? goat.name : 'Kambing terhapus',
        barcode: goat ? goat.barcode : '-',
        partyName: customer ? customer.name : '-',
        amount: item.price,
        profit: (Number(item.price) || 0) - (Number(item.costPrice) || 0),
        createdAt: item.createdAt,
      };
    });
    return purchases.concat(sales)
      .sort((a, b) => {
        const dateA = U.parseDate(a.date);
        const dateB = U.parseDate(b.date);
        const diff = (dateB ? dateB.getTime() : 0) - (dateA ? dateA.getTime() : 0);
        if (diff !== 0) return diff;
        return String(b.createdAt || '').localeCompare(String(a.createdAt || ''));
      })
      .slice(0, limit || 6);
  }

  function getStats() {
    const data = getState();
    const available = data.goats.filter((goat) => goat.status === 'tersedia');
    const sold = data.goats.filter((goat) => goat.status === 'terjual');
    const totalPurchases = U.sum(data.purchases, (item) => item.total);
    const totalSales = U.sum(data.sales, (item) => item.price);
    const totalAdditional = U.sum(data.purchases, (item) => item.additionalCost);
    const grossProfit = U.sum(data.sales, (item) => (Number(item.price) || 0) - (Number(item.costPrice) || 0));
    const inventoryValue = U.sum(available, (goat) => Number(goat.purchasePrice) || 0);
    const margin = totalSales ? (grossProfit / totalSales) * 100 : 0;
    const types = Array.from(new Set(data.goats.map((goat) => goat.type))).sort();
    const byType = types.map((type) => {
      const list = data.goats.filter((goat) => goat.type === type);
      return {
        type,
        total: list.length,
        available: list.filter((goat) => goat.status === 'tersedia').length,
        sold: list.filter((goat) => goat.status === 'terjual').length,
      };
    });
    return {
      totalGoats: data.goats.length,
      availableCount: available.length,
      soldCount: sold.length,
      totalPurchaseCount: data.purchases.length,
      totalSaleCount: data.sales.length,
      totalPurchases,
      totalSales,
      totalAdditional,
      grossProfit,
      inventoryValue,
      margin,
      byType,
      availablePurchased: available.filter((goat) => goat.origin === 'pembelian').length,
      availableOffspring: available.filter((goat) => goat.origin === 'peranakan').length,
    };
  }

  function supplierStats(id) {
    const purchases = getState().purchases.filter((item) => item.supplierId === id);
    return {
      transactions: purchases,
      count: purchases.length,
      total: U.sum(purchases, (item) => item.total),
    };
  }

  function customerStats(id) {
    const sales = getState().sales.filter((item) => item.customerId === id);
    return {
      transactions: sales,
      count: sales.length,
      total: U.sum(sales, (item) => item.price),
      profit: U.sum(sales, (item) => (Number(item.price) || 0) - (Number(item.costPrice) || 0)),
    };
  }

  K.Store = {
    STORAGE_KEY,
    VERSION,
    isPersistent: () => storageReady,
    getState,
    save,
    reset,
    getGoat,
    findByBarcode,
    getSupplier,
    getCustomer,
    getMother,
    getChildren,
    getPurchaseForGoat,
    getSalesForGoat,
    goatTransactions,
    getRecentTransactions,
    getStats,
    supplierStats,
    customerStats,
    addGoat,
    updateGoat,
    setGoatStatus,
    deleteGoat,
    createPurchase,
    addSale,
    addSupplier,
    updateSupplier,
    deleteSupplier,
    addCustomer,
    updateCustomer,
    deleteCustomer,
    recordScan,
    clearScanHistory,
    updateSettings,
    exportJSON,
    importJSON,
    peekNextBarcode,
    barcodePrefix,
  };
})();
