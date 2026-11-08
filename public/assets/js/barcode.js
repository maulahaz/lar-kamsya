/**
 * Kamsya - Barcode renderer
 * Generator Code 39 mandiri (tanpa library/CDN) agar demo tetap berjalan offline.
 * Setiap karakter direpresentasikan sebagai rangkaian bar dan spasi.
 */
(function () {
  'use strict';

  const K = (window.Kamsya = window.Kamsya || {});
  const esc = (value) => (K.Utils ? K.Utils.escapeHtml(value) : String(value == null ? '' : value));

  const PATTERNS = {
    '0': '101001101101',
    '1': '110100101011',
    '2': '101100101011',
    '3': '110110010101',
    '4': '101001101011',
    '5': '110100110101',
    '6': '101100110101',
    '7': '101001011011',
    '8': '110100101101',
    '9': '101100101101',
    A: '110101001011',
    B: '101101001011',
    C: '110110100101',
    D: '101011001011',
    E: '110101100101',
    F: '101101100101',
    G: '101010011011',
    H: '110101001101',
    I: '101101001101',
    J: '101011001101',
    K: '110101010011',
    L: '101101010011',
    M: '110110101001',
    N: '101011010011',
    O: '110101101001',
    P: '101101101001',
    Q: '101010110011',
    R: '110101011001',
    S: '101101011001',
    T: '101011011001',
    U: '110010101011',
    V: '100110101011',
    W: '110011010101',
    X: '100101101011',
    Y: '110010110101',
    Z: '100110110101',
    '-': '100101011011',
    '.': '110010101101',
    ' ': '100110101101',
    '$': '100100100101',
    '/': '100100101001',
    '+': '100101001001',
    '%': '101001001001',
    '*': '100101101101',
  };

  function normalize(value) {
    return String(value == null ? '' : value)
      .toUpperCase()
      .replace(/[^0-9A-Z\-. $/+%]/g, '');
  }

  function encode(value) {
    const clean = normalize(value);
    if (!clean) return '';
    const characters = `*${clean}*`.split('');
    let bits = '';
    characters.forEach((character, index) => {
      const pattern = PATTERNS[character];
      if (!pattern) return;
      if (index > 0) bits += '0';
      bits += pattern;
    });
    return bits;
  }

  function svg(value, options) {
    const opts = options || {};
    const height = Number(opts.height) || 64;
    const moduleWidth = Number(opts.moduleWidth) || 2;
    const quietZone = opts.quietZone == null ? 12 : Number(opts.quietZone);
    const bits = encode(value);
    if (!bits) {
      return `<div class="barcode-empty">${esc('Barcode tidak valid')}</div>`;
    }

    const totalModules = bits.length + quietZone * 2;
    const viewWidth = totalModules * moduleWidth;
    let x = quietZone * moduleWidth;
    let rects = '';

    for (let index = 0; index < bits.length;) {
      if (bits.charAt(index) === '1') {
        let run = 1;
        while (bits.charAt(index + run) === '1') run += 1;
        const width = run * moduleWidth;
        rects += `<rect x="${x}" y="0" width="${width}" height="${height}"/>`;
        x += width;
        index += run;
      } else {
        x += moduleWidth;
        index += 1;
      }
    }

    return `<svg class="barcode-svg ${opts.className || ''}" viewBox="0 0 ${viewWidth} ${height}" width="100%" height="${height}" preserveAspectRatio="none" role="img" aria-label="${esc(`Barcode ${value}`)}" shape-rendering="crispEdges">${rects}</svg>`;
  }

  function figure(value, options) {
    const opts = options || {};
    return `<figure class="barcode-figure${opts.className ? ` ${opts.className}` : ''}">
      ${svg(value, { height: opts.height || 52, moduleWidth: opts.moduleWidth || 2, quietZone: opts.quietZone })}
      <figcaption class="barcode-caption">${esc(value)}</figcaption>
    </figure>`;
  }

  K.Barcode = { normalize, encode, svg, figure };
})();
