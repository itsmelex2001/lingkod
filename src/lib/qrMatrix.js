// ISO/IEC 18004 Byte-mode QR Code matrix & SVG generator (Versions 1–4, zero dependencies)

const VERSIONS = [
  [1, 16, 10, 0], // Version 1-M: up to 14 bytes
  [2, 28, 16, 0], // Version 2-M: up to 26 bytes
  [3, 44, 26, 0], // Version 3-M: up to 42 bytes
  [4, 80, 20, 1]  // Version 4-L: up to 78 bytes
];

const EXP = new Uint8Array(512);
const LOG = new Uint8Array(256);
(function initGf() {
  let x = 1;
  for (let i = 0; i < 255; i++) {
    EXP[i] = x;
    LOG[x] = i;
    x = (x << 1) ^ (x & 0x80 ? 0x11d : 0);
  }
  for (let i = 255; i < 512; i++) EXP[i] = EXP[i - 255];
})();

function gfMul(a, b) {
  return a === 0 || b === 0 ? 0 : EXP[LOG[a] + LOG[b]];
}

function reedSolomonRemainder(data, ecLen) {
  let poly = [1];
  for (let i = 0; i < ecLen; i++) {
    const next = new Array(poly.length + 1).fill(0);
    for (let j = 0; j < poly.length; j++) {
      next[j] ^= poly[j];
      next[j + 1] ^= gfMul(poly[j], EXP[i]);
    }
    poly = next;
  }
  const rem = new Uint8Array(ecLen);
  for (let d = 0; d < data.length; d++) {
    const factor = data[d] ^ rem[0];
    for (let i = 0; i < ecLen - 1; i++) {
      rem[i] = rem[i + 1] ^ gfMul(poly[i + 1], factor);
    }
    rem[ecLen - 1] = gfMul(poly[ecLen], factor);
  }
  return rem;
}

function maskBit(mask, r, c) {
  switch (mask) {
    case 0: return (r + c) % 2 === 0;
    case 1: return r % 2 === 0;
    case 2: return c % 3 === 0;
    case 3: return (r + c) % 3 === 0;
    case 4: return (Math.floor(r / 2) + Math.floor(c / 3)) % 2 === 0;
    case 5: return ((r * c) % 2) + ((r * c) % 3) === 0;
    case 6: return (((r * c) % 2) + ((r * c) % 3)) % 2 === 0;
    default: return (((r + c) % 2) + ((r * c) % 3)) % 2 === 0;
  }
}

function writeFormatBits(mat, size, ecFormatBits, mask) {
  const data5 = (ecFormatBits << 3) | mask;
  let rem10 = data5 << 10;
  for (let i = 14; i >= 10; i--) {
    if ((rem10 >> i) & 1) rem10 ^= 0x537 << (i - 10);
  }
  const bits15 = ((data5 << 10) | rem10) ^ 0x5412;
  const bit = (i) => ((bits15 >> i) & 1) === 1;

  const tlCols = [0, 1, 2, 3, 4, 5, 7, 8];
  for (let k = 0; k < 8; k++) mat[8][tlCols[k]] = bit(14 - k);
  const tlRows = [7, 5, 4, 3, 2, 1, 0];
  for (let k = 0; k < 7; k++) mat[tlRows[k]][8] = bit(6 - k);

  for (let k = 0; k < 7; k++) mat[size - 1 - k][8] = bit(14 - k);
  for (let k = 0; k < 8; k++) mat[8][size - 8 + k] = bit(7 - k);
  mat[size - 8][8] = true;
}

function scorePenalty(mat, size) {
  let score = 0;
  for (let r = 0; r < size; r++) {
    let runH = 1;
    let runV = 1;
    for (let c = 1; c < size; c++) {
      if (mat[r][c] === mat[r][c - 1]) {
        runH++;
        if (runH === 5) score += 3;
        else if (runH > 5) score += 1;
      } else runH = 1;

      if (mat[c][r] === mat[c - 1][r]) {
        runV++;
        if (runV === 5) score += 3;
        else if (runV > 5) score += 1;
      } else runV = 1;
    }
  }
  for (let r = 0; r < size - 1; r++) {
    for (let c = 0; c < size - 1; c++) {
      const v = mat[r][c];
      if (v === mat[r][c + 1] && v === mat[r + 1][c] && v === mat[r + 1][c + 1]) score += 3;
    }
  }
  return score;
}

export function buildQrMatrix(text) {
  const str = String(text || '').trim();
  if (!str) return null;
  let bytes = typeof TextEncoder !== 'undefined'
    ? Array.from(new TextEncoder().encode(str))
    : Array.from(str).map((ch) => ch.charCodeAt(0) & 0xff);
  if (bytes.length > 78) bytes = bytes.slice(0, 78);

  const spec = VERSIONS.find((v) => bytes.length <= v[1] - 2) || VERSIONS[VERSIONS.length - 1];
  const [ver, dataCodewords, ecCodewords, ecFormatBits] = spec;
  const size = 17 + 4 * ver;

  const bits = [];
  const pushBits = (val, len) => {
    for (let i = len - 1; i >= 0; i--) bits.push((val >> i) & 1);
  };
  pushBits(0b0100, 4);
  pushBits(bytes.length, 8);
  for (const b of bytes) pushBits(b, 8);
  const maxBits = dataCodewords * 8;
  const term = Math.min(4, maxBits - bits.length);
  for (let i = 0; i < term; i++) bits.push(0);
  while (bits.length % 8 !== 0) bits.push(0);

  const data = new Uint8Array(dataCodewords);
  for (let i = 0; i < bits.length >> 3; i++) {
    let v = 0;
    for (let b = 0; b < 8; b++) v = (v << 1) | bits[i * 8 + b];
    data[i] = v;
  }
  const pads = [0xec, 0x11];
  for (let i = bits.length >> 3, p = 0; i < dataCodewords; i++, p ^= 1) {
    data[i] = pads[p];
  }

  const ecc = reedSolomonRemainder(data, ecCodewords);
  const allBytes = new Uint8Array(data.length + ecc.length);
  allBytes.set(data, 0);
  allBytes.set(ecc, data.length);

  const base = Array.from({ length: size }, () => new Array(size).fill(false));
  const isFunc = Array.from({ length: size }, () => new Array(size).fill(false));
  const setFunc = (r, c, dark) => {
    if (r >= 0 && r < size && c >= 0 && c < size) {
      base[r][c] = dark;
      isFunc[r][c] = true;
    }
  };

  for (const [tr, lc] of [[0, 0], [0, size - 7], [size - 7, 0]]) {
    for (let dr = -1; dr <= 7; dr++) {
      for (let dc = -1; dc <= 7; dc++) {
        const inside = dr >= 0 && dr <= 6 && dc >= 0 && dc <= 6;
        const dark = inside && (dr === 0 || dr === 6 || dc === 0 || dc === 6 || (dr >= 2 && dr <= 4 && dc >= 2 && dc <= 4));
        setFunc(tr + dr, lc + dc, dark);
      }
    }
  }

  if (ver >= 2) {
    const cr = size - 7;
    const cc = size - 7;
    for (let dr = -2; dr <= 2; dr++) {
      for (let dc = -2; dc <= 2; dc++) {
        setFunc(cr + dr, cc + dc, Math.max(Math.abs(dr), Math.abs(dc)) !== 1);
      }
    }
  }

  for (let i = 8; i < size - 8; i++) {
    if (!isFunc[6][i]) setFunc(6, i, i % 2 === 0);
    if (!isFunc[i][6]) setFunc(i, 6, i % 2 === 0);
  }

  for (let k = 0; k <= 8; k++) {
    if (!isFunc[8][k]) setFunc(8, k, false);
    if (!isFunc[k][8]) setFunc(k, 8, false);
  }
  for (let k = 0; k < 8; k++) setFunc(8, size - 8 + k, false);
  for (let k = 0; k < 7; k++) setFunc(size - 1 - k, 8, false);
  setFunc(size - 8, 8, true);

  let bitIdx = 0;
  let upward = true;
  for (let right = size - 1; right >= 1; right -= 2) {
    if (right === 6) right = 5;
    for (let step = 0; step < size; step++) {
      const r = upward ? size - 1 - step : step;
      for (const c of [right, right - 1]) {
        if (!isFunc[r][c]) {
          const dark = bitIdx < allBytes.length * 8
            ? ((allBytes[bitIdx >> 3] >> (7 - (bitIdx & 7))) & 1) === 1
            : false;
          base[r][c] = dark;
          bitIdx++;
        }
      }
    }
    upward = !upward;
  }

  let bestMat = null;
  let bestScore = Infinity;
  for (let m = 0; m < 8; m++) {
    const cand = base.map((row, r) =>
      row.map((val, c) => (isFunc[r][c] ? val : (maskBit(m, r, c) ? !val : val)))
    );
    writeFormatBits(cand, size, ecFormatBits, m);
    const sc = scorePenalty(cand, size);
    if (sc < bestScore) {
      bestScore = sc;
      bestMat = cand;
    }
  }
  return bestMat;
}

export function buildQrSvgPath(matrix, quiet = 2) {
  if (!matrix || !matrix.length) return { viewBox: 0, path: '' };
  const n = matrix.length;
  let d = '';
  for (let r = 0; r < n; r++) {
    for (let c = 0; c < n; c++) {
      if (matrix[r][c]) d += `M${c + quiet},${r + quiet}h1v1h-1z`;
    }
  }
  return { viewBox: n + quiet * 2, path: d };
}
