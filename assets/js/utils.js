/**
 * Kamsya - Shared utilities
 */
(function () {
  'use strict';

  const K = (window.Kamsya = window.Kamsya || {});

  const MONTHS = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
  ];
  const MONTHS_SHORT = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
  const DAYS = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

  function pad(value, length) {
    return String(value).padStart(length, '0');
  }

  function parseDate(value) {
    if (!value) return null;
    if (value instanceof Date) return isNaN(value.getTime()) ? null : value;
    const text = String(value).slice(0, 10);
    const parts = text.split('-');
    if (parts.length === 3) {
      const date = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
      return isNaN(date.getTime()) ? null : date;
    }
    const date = new Date(value);
    return isNaN(date.getTime()) ? null : date;
  }

  function toISO(date) {
    const d = date instanceof Date ? date : parseDate(date);
    if (!d) return '';
    return `${d.getFullYear()}-${pad(d.getMonth() + 1, 2)}-${pad(d.getDate(), 2)}`;
  }

  function todayISO() {
    return toISO(new Date());
  }

  function formatDate(value, style) {
    const date = parseDate(value);
    if (!date) return '-';
    if (style === 'short') {
      return `${date.getDate()} ${MONTHS_SHORT[date.getMonth()]} ${date.getFullYear()}`;
    }
    if (style === 'long') {
      return `${DAYS[date.getDay()]}, ${date.getDate()} ${MONTHS[date.getMonth()]} ${date.getFullYear()}`;
    }
    return `${date.getDate()} ${MONTHS[date.getMonth()]} ${date.getFullYear()}`;
  }

  function formatDateTime(value) {
    const date = parseDate(value);
    if (!date) return '-';
    if (String(value).length <= 10) return formatDate(value);
    return `${formatDate(value)}, ${pad(date.getHours(), 2)}.${pad(date.getMinutes(), 2)}`;
  }

  function formatRupiah(value) {
    const number = Number(value);
    if (!isFinite(number) || number === 0) return 'Rp0';
    const sign = number < 0 ? '-' : '';
    return `${sign}Rp${Math.abs(Math.round(number)).toLocaleString('id-ID')}`;
  }

  function formatNumber(value) {
    const number = Number(value);
    if (!isFinite(number)) return '0';
    return number.toLocaleString('id-ID');
  }

  function formatCompactRupiah(value) {
    const number = Number(value) || 0;
    const abs = Math.abs(number);
    const sign = number < 0 ? '-' : '';
    if (abs >= 1000000000) return `${sign}Rp${(abs / 1000000000).toLocaleString('id-ID', { maximumFractionDigits: 2 })} M`;
    if (abs >= 1000000) return `${sign}Rp${(abs / 1000000).toLocaleString('id-ID', { maximumFractionDigits: 1 })} jt`;
    if (abs >= 1000) return `${sign}Rp${(abs / 1000).toLocaleString('id-ID', { maximumFractionDigits: 0 })} rb`;
    return `${sign}Rp${formatNumber(abs)}`;
  }

  function formatWeight(value) {
    return `${formatNumber(Number(value) || 0)} kg`;
  }

  function ageText(birthDate, reference) {
    const birth = parseDate(birthDate);
    if (!birth) return '-';
    const end = parseDate(reference) || new Date();
    if (birth > end) return '-';
    let months = (end.getFullYear() - birth.getFullYear()) * 12 + (end.getMonth() - birth.getMonth());
    if (end.getDate() < birth.getDate()) months -= 1;
    months = Math.max(0, months);
    const years = Math.floor(months / 12);
    const rest = months % 12;
    if (years > 0) return `${years} tahun${rest ? ` ${rest} bulan` : ''}`;
    if (rest > 0) return `${rest} bulan`;
    const days = Math.max(0, Math.floor((end - birth) / 86400000));
    return `${days} hari`;
  }

  function escapeHtml(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function initials(name) {
    return String(name || '?')
      .trim()
      .split(/\s+/)
      .slice(0, 2)
      .map((part) => part.charAt(0).toUpperCase())
      .join('');
  }

  function greeting() {
    const hour = new Date().getHours();
    if (hour < 11) return 'Selamat pagi';
    if (hour < 15) return 'Selamat siang';
    if (hour < 18) return 'Selamat sore';
    return 'Selamat malam';
  }

  function sum(list, selector) {
    return (Array.isArray(list) ? list : []).reduce((total, item) => {
      const value = typeof selector === 'function' ? selector(item) : item;
      return total + (Number(value) || 0);
    }, 0);
  }

  function percent(part, total) {
    if (!total) return 0;
    return Math.max(0, Math.min(100, (Number(part) || 0) / Number(total) * 100));
  }

  function monthKey(value) {
    const date = parseDate(value);
    if (!date) return '';
    return `${date.getFullYear()}-${pad(date.getMonth() + 1, 2)}`;
  }

  function monthLabel(key, style) {
    const parts = String(key).split('-');
    const month = Number(parts[1]) - 1;
    const year = parts[0];
    if (style === 'full') return `${MONTHS[month]} ${year}`;
    if (style === 'long') return `${MONTHS_SHORT[month]} ${year}`;
    return MONTHS_SHORT[month] || '-';
  }

  function lastMonths(count, endDate) {
    const end = parseDate(endDate) || new Date();
    const result = [];
    for (let index = count - 1; index >= 0; index -= 1) {
      const date = new Date(end.getFullYear(), end.getMonth() - index, 1);
      result.push({
        key: monthKey(date),
        label: MONTHS_SHORT[date.getMonth()],
        full: `${MONTHS[date.getMonth()]} ${date.getFullYear()}`,
        year: date.getFullYear(),
        month: date.getMonth(),
      });
    }
    return result;
  }

  function debounce(fn, wait) {
    let timer = null;
    const debounced = function debounced(...args) {
      clearTimeout(timer);
      timer = setTimeout(() => fn.apply(this, args), wait);
    };
    debounced.cancel = () => clearTimeout(timer);
    return debounced;
  }

  function downloadFile(filename, content, mime) {
    const blob = new Blob([content], { type: mime || 'application/json;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    link.remove();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
  }

  function readFileAsText(file) {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onload = () => resolve(String(reader.result || ''));
      reader.onerror = () => reject(reader.error || new Error('Gagal membaca file'));
      reader.readAsText(file);
    });
  }

  function isDateInRange(value, start, end) {
    const date = parseDate(value);
    if (!date) return false;
    const from = parseDate(start);
    const to = parseDate(end);
    if (from && date < from) return false;
    if (to && date > to) return false;
    return true;
  }

  function genderIcon(gender) {
    return gender === 'Betina' ? '♀' : '♂';
  }

  function typeTone(type) {
    const text = String(type || '').toLowerCase();
    if (text.includes('boer')) return 'amber';
    if (text.includes('etawa')) return 'blue';
    if (text.includes('jawa')) return 'slate';
    if (text.includes('kacang')) return 'rose';
    return 'green';
  }

  K.Utils = {
    MONTHS,
    MONTHS_SHORT,
    DAYS,
    pad,
    parseDate,
    toISO,
    todayISO,
    formatDate,
    formatDateTime,
    formatRupiah,
    formatNumber,
    formatCompactRupiah,
    formatWeight,
    ageText,
    escapeHtml,
    initials,
    greeting,
    sum,
    percent,
    monthKey,
    monthLabel,
    lastMonths,
    debounce,
    downloadFile,
    readFileAsText,
    isDateInRange,
    genderIcon,
    typeTone,
  };
})();
