// Shared printable MMWGH Patient Slip component (Appointment / Follow-Up / Rescheduled)
(function () {
  const SLIP_META = {
    appointment: { title: 'APPOINTMENT SLIP', docCode: 'MMH-OPM-04-02-00' },
    followUp: { title: 'FOLLOW-UP SLIP', docCode: 'MMH-OPM-04-03-00' },
    rescheduled: { title: 'RESCHEDULED (NEW) SLIP', docCode: 'MMH-NUR-04-204-01' }
  };

  let liveSlipConfig = {
    hospitalName: 'MARIVELES MENTAL WELLNESS AND GENERAL HOSPITAL',
    hospitalAddress: 'P. Monroe Street, Poblacion, Mariveles, Bataan, Philippines, 2105',
    contactPhone: '(047) 935-4645',
    qrExpiryHours: 48,
    departments: {}
  };

  (function hydrateSlipSettings() {
    if (typeof fetch !== 'function') return;
    fetch('/sdn/api/admin-settings-router.php?route=/public/settings', { headers: { Accept: 'application/json' } })
      .then((r) => (r.ok ? r.json() : null))
      .then((json) => {
        const d = json && json.data;
        if (!d) return;
        if (d.general) {
          if (d.general.hospital_name) liveSlipConfig.hospitalName = String(d.general.hospital_name).toUpperCase();
          if (d.general.hospital_address) liveSlipConfig.hospitalAddress = String(d.general.hospital_address);
          if (d.general.contact_phone) liveSlipConfig.contactPhone = String(d.general.contact_phone);
        }
        if (d.qr_code_expiry_hours) liveSlipConfig.qrExpiryHours = Number(d.qr_code_expiry_hours) || 48;
        if (d.departments) liveSlipConfig.departments = d.departments;
      })
      .catch(() => {});
  })();

  function resolveQrExpiryHours(specialty) {
    const raw = String(specialty || '').toLowerCase();
    const code = raw.includes('neuro') ? 'neurology' : (raw.includes('psych') || raw.includes('opu') ? 'psychiatry' : (raw.includes('general') ? 'general' : ''));
    const dept = code && liveSlipConfig.departments ? liveSlipConfig.departments[code] : null;
    if (dept && dept.qr_expiry_hours_override) return Number(dept.qr_expiry_hours_override);
    return liveSlipConfig.qrExpiryHours || 48;
  }

  function esc(v) {
    return String(v ?? '').replace(/[&<>"']/g, (ch) => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#39;'
    }[ch]));
  }

  function formatPatientType(raw) {
    const s = String(raw || '').trim().toLowerCase();
    if (!s) return 'New';
    if (s.includes('first') || s === 'new') return 'New';
    if (s.includes('return') || s === 'old') return 'Old';
    return raw;
  }

  function formatDateDisplay(raw) {
    if (!raw) return '';
    const str = String(raw).trim();
    if (/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}/.test(str)) {
      const dt = new Date(str.replace(' ', 'T'));
      if (!isNaN(dt.getTime())) {
        return dt.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) +
          ' ' + dt.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
      }
    }
    if (/^\d{4}-\d{2}-\d{2}/.test(str)) {
      const dt = new Date(str.slice(0, 10) + 'T00:00:00');
      if (!isNaN(dt.getTime())) {
        return dt.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
      }
    }
    return str;
  }

  function canGenerateSlip(record) {
    const r = record || {};
    const raw = String(r.status_raw || r.status || '').trim().toLowerCase();
    return [
      'approved',
      'confirmed',
      'arrived',
      'checked in',
      'ongoing',
      'in_consultation',
      'in consultation',
      'completed',
      'rescheduled'
    ].includes(raw);
  }

  function resolveSlipType(record, explicitType) {
    if (explicitType && SLIP_META[explicitType]) return explicitType;
    const r = record || {};
    const status = String(r.status_raw || r.status || '').toLowerCase();
    const hasNextVisit = Boolean(r.next_visit_date && String(r.next_visit_date).trim() !== '');
    if (status === 'completed' || hasNextVisit) return 'followUp';
    if (r.rescheduled_by_staff || status === 'rescheduled') return 'rescheduled';
    return 'appointment';
  }

  // ISO/IEC 18004 Byte-mode QR Code matrix generator (Versions 1–4, zero dependencies)
  const QR_VERSIONS = [
    [1, 16, 10, 0],
    [2, 28, 16, 0],
    [3, 44, 26, 0],
    [4, 80, 20, 1]
  ];
  const GF_EXP = new Uint8Array(512);
  const GF_LOG = new Uint8Array(256);
  (function initGf() {
    let x = 1;
    for (let i = 0; i < 255; i++) {
      GF_EXP[i] = x;
      GF_LOG[x] = i;
      x = (x << 1) ^ (x & 0x80 ? 0x11d : 0);
    }
    for (let i = 255; i < 512; i++) GF_EXP[i] = GF_EXP[i - 255];
  })();

  function gfMul(a, b) {
    return a === 0 || b === 0 ? 0 : GF_EXP[GF_LOG[a] + GF_LOG[b]];
  }

  function rsRemainder(data, ecLen) {
    let poly = [1];
    for (let i = 0; i < ecLen; i++) {
      const next = new Array(poly.length + 1).fill(0);
      for (let j = 0; j < poly.length; j++) {
        next[j] ^= poly[j];
        next[j + 1] ^= gfMul(poly[j], GF_EXP[i]);
      }
      poly = next;
    }
    const rem = new Uint8Array(ecLen);
    for (let d = 0; d < data.length; d++) {
      const factor = data[d] ^ rem[0];
      for (let i = 0; i < ecLen - 1; i++) rem[i] = rem[i + 1] ^ gfMul(poly[i + 1], factor);
      rem[ecLen - 1] = gfMul(poly[ecLen], factor);
    }
    return rem;
  }

  function qrMaskBit(mask, r, c) {
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

  function qrWriteFormat(mat, size, ecFormatBits, mask) {
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

  function qrPenalty(mat, size) {
    let score = 0;
    for (let r = 0; r < size; r++) {
      let runH = 1, runV = 1;
      for (let c = 1; c < size; c++) {
        if (mat[r][c] === mat[r][c - 1]) { runH++; if (runH === 5) score += 3; else if (runH > 5) score += 1; } else runH = 1;
        if (mat[c][r] === mat[c - 1][r]) { runV++; if (runV === 5) score += 3; else if (runV > 5) score += 1; } else runV = 1;
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

  function buildQrMatrix(text) {
    const str = String(text || '').trim();
    if (!str) return null;
    let bytes = typeof TextEncoder !== 'undefined'
      ? Array.from(new TextEncoder().encode(str))
      : Array.from(str).map((ch) => ch.charCodeAt(0) & 0xff);
    if (bytes.length > 78) bytes = bytes.slice(0, 78);
    const spec = QR_VERSIONS.find((v) => bytes.length <= v[1] - 2) || QR_VERSIONS[QR_VERSIONS.length - 1];
    const [ver, dataCodewords, ecCodewords, ecFormatBits] = spec;
    const size = 17 + 4 * ver;

    const bits = [];
    const pushBits = (val, len) => { for (let i = len - 1; i >= 0; i--) bits.push((val >> i) & 1); };
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
    for (let i = bits.length >> 3, p = 0; i < dataCodewords; i++, p ^= 1) data[i] = pads[p];

    const ecc = rsRemainder(data, ecCodewords);
    const allBytes = new Uint8Array(data.length + ecc.length);
    allBytes.set(data, 0);
    allBytes.set(ecc, data.length);

    const base = Array.from({ length: size }, () => new Array(size).fill(false));
    const isFunc = Array.from({ length: size }, () => new Array(size).fill(false));
    const setFunc = (r, c, dark) => {
      if (r >= 0 && r < size && c >= 0 && c < size) { base[r][c] = dark; isFunc[r][c] = true; }
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
      const cr = size - 7, cc = size - 7;
      for (let dr = -2; dr <= 2; dr++) {
        for (let dc = -2; dc <= 2; dc++) setFunc(cr + dr, cc + dc, Math.max(Math.abs(dr), Math.abs(dc)) !== 1);
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

    let bitIdx = 0, upward = true;
    for (let right = size - 1; right >= 1; right -= 2) {
      if (right === 6) right = 5;
      for (let step = 0; step < size; step++) {
        const r = upward ? size - 1 - step : step;
        for (const c of [right, right - 1]) {
          if (!isFunc[r][c]) {
            base[r][c] = bitIdx < allBytes.length * 8
              ? ((allBytes[bitIdx >> 3] >> (7 - (bitIdx & 7))) & 1) === 1
              : false;
            bitIdx++;
          }
        }
      }
      upward = !upward;
    }

    let bestMat = null, bestScore = Infinity;
    for (let m = 0; m < 8; m++) {
      const cand = base.map((row, r) => row.map((val, c) => (isFunc[r][c] ? val : (qrMaskBit(m, r, c) ? !val : val))));
      qrWriteFormat(cand, size, ecFormatBits, m);
      const sc = qrPenalty(cand, size);
      if (sc < bestScore) { bestScore = sc; bestMat = cand; }
    }
    return bestMat;
  }

  function renderQrSvg(matrix, quiet) {
    const q = quiet !== undefined ? quiet : 2;
    if (!matrix || !matrix.length) return '';
    const n = matrix.length;
    const vb = n + q * 2;
    let d = '';
    for (let r = 0; r < n; r++) {
      for (let c = 0; c < n; c++) {
        if (matrix[r][c]) d += `M${c + q},${r + q}h1v1h-1z`;
      }
    }
    return `<svg viewBox="0 0 ${vb} ${vb}" width="100%" height="100%" shape-rendering="crispEdges" role="img" aria-label="Patient ID QR Code"><rect width="${vb}" height="${vb}" fill="#ffffff"/><path d="${d}" fill="#000000"/></svg>`;
  }

  function getSlipStyles() {
    return `
      .mmwgh-slip-card {
        width: 100%;
        max-width: 440px;
        margin: 0 auto;
        background: #ffffff;
        color: #000000;
        border: 1px solid #9ca3af;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
        line-height: 1.4;
        box-sizing: border-box;
      }
      .mmwgh-slip-header {
        display: grid;
        grid-template-columns: 52px 1fr 52px;
        align-items: center;
        gap: 8px;
        padding: 12px 16px;
        border-bottom: 1px solid #d1d5db;
      }
      .mmwgh-slip-logo {
        width: 48px;
        height: 48px;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        flex-shrink: 0;
        background: transparent;
      }
      .mmwgh-slip-logo img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        display: block;
      }
      .mmwgh-slip-logo-ph {
        font-size: 9px;
        font-weight: 700;
        color: #374151;
        text-align: center;
        line-height: 1.15;
      }
      .mmwgh-slip-org {
        text-align: center;
        color: #000000;
      }
      .mmwgh-slip-org p {
        margin: 0;
        line-height: 1.25;
      }
      .mmwgh-slip-org .org-l1 { font-size: 11px; font-weight: 500; }
      .mmwgh-slip-org .org-l2 { font-size: 11px; font-weight: 700; }
      .mmwgh-slip-org .org-l3 { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: -0.01em; }
      .mmwgh-slip-org .org-l4 { font-size: 10px; font-weight: 400; margin-top: 1px; }
      .mmwgh-slip-body {
        padding: 16px 20px 12px;
      }
      .mmwgh-slip-title {
        margin: 0 0 14px;
        text-align: center;
        font-size: 14px;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #000000;
      }
      .mmwgh-slip-main {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 14px;
      }
      .mmwgh-slip-fields {
        flex: 1;
        min-width: 0;
        text-align: left;
        font-size: 12.5px;
        color: #000000;
      }
      .mmwgh-slip-qr-col {
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 3px;
      }
      .mmwgh-slip-qr-box {
        width: 76px;
        height: 76px;
        border: 1px solid #d1d5db;
        background: #ffffff;
        padding: 2px;
        box-sizing: border-box;
        display: flex;
        align-items: center;
        justify-content: center;
      }
      .mmwgh-slip-qr-box img,
      .mmwgh-slip-qr-box svg {
        width: 100%;
        height: 100%;
        display: block;
        object-fit: contain;
      }
      .mmwgh-slip-qr-id {
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: 8.5px;
        font-weight: 700;
        color: #000000;
        line-height: 1;
        letter-spacing: -0.02em;
      }
      .mmwgh-slip-fields p {
        margin: 0 0 6px;
      }
      .mmwgh-slip-fields strong {
        font-weight: 600;
      }
      .mmwgh-slip-attended {
        font-size: 11px;
        margin: -2px 0 10px !important;
      }
      .mmwgh-slip-fu-item {
        margin: 10px 0;
      }
      .mmwgh-slip-fu-item p {
        margin: 0 0 4px;
      }
      .mmwgh-slip-footer {
        margin-top: 22px;
        text-align: center;
        color: #000000;
      }
      .mmwgh-slip-himu {
        font-size: 10.5px;
        font-weight: 700;
        margin: 0 0 12px;
      }
      .mmwgh-slip-remind {
        font-size: 11px;
        line-height: 1.5;
        margin: 0;
      }
      .mmwgh-slip-phone {
        font-size: 11.5px;
        font-weight: 700;
        letter-spacing: 0.02em;
        margin: 1px 0 0;
      }
      .mmwgh-slip-code-row {
        margin-top: 14px;
        padding-top: 10px;
        border-top: 1px solid #e5e7eb;
        text-align: right;
      }
      .mmwgh-slip-code {
        font-size: 10.5px;
        font-weight: 700;
        letter-spacing: -0.01em;
      }
    `;
  }

  function normalizeData(data) {
    const d = data || {};
    const slipType = resolveSlipType(d, d.slipType);
    const meta = SLIP_META[slipType] || SLIP_META.appointment;
    const specialty = d.specialty || '';
    const followUps = Array.isArray(d.followUps) && d.followUps.length
      ? d.followUps.map((f) => ({
          date: [formatDateDisplay(f.date), f.time].filter(Boolean).join(' ') || 'As advised by physician',
          specialty: f.specialty || ''
        }))
      : [{
          date: [formatDateDisplay(d.nextVisitDate || d.next_visit_date || ''), d.nextVisitTime || ''].filter(Boolean).join(' ') || 'As advised by physician',
          specialty
        }];
    const consultationMode = String(d.consultationMode || d.consultation_mode || '').trim();
    const isTeleconsult = /^teleconsultation$/i.test(consultationMode) || /^teleconsultation$/i.test(String(d.consultType || '').trim());
    const effectiveMeta = isTeleconsult && slipType !== 'followUp'
      ? {
          title: slipType === 'rescheduled' ? 'RESCHEDULED TELECONSULTATION SLIP' : 'TELECONSULTATION SLIP',
          docCode: meta.docCode
        }
      : meta;
    const rawEncodedDate = d.encodedDate || d.encoded_date || d.medical_history_date || d.attendedDate || d.attended_date || '';
    const rawAttendedDate = d.attendedDate || d.attended_date || d.encodedDate || d.encoded_date || d.medical_history_date || '';
    return {
      slipType,
      meta: effectiveMeta,
      isTeleconsult,
      consultationMode: consultationMode || (isTeleconsult ? 'Teleconsultation' : 'Face-to-Face'),
      teleconsultPlatform: String(d.teleconsultPlatform || d.teleconsult_platform || '').trim(),
      teleconsultLink: String(d.teleconsultLink || d.teleconsult_link || '').trim(),
      teleconsultNotes: String(d.teleconsultNotes || d.teleconsult_notes || '').trim(),
      dohLogoUrl: d.dohLogoUrl !== undefined ? d.dohLogoUrl : '/sdn/public/assets/logos/doh%20logo.png',
      mmwghLogoUrl: d.mmwghLogoUrl !== undefined ? d.mmwghLogoUrl : '/sdn/public/assets/mmwgh_logo.png',
      qrCodeUrl: d.qrCodeUrl || '',
      patientId: String(d.patientId || d.patient_id || d.referenceNo || d.reference_no || d.patient_code || '').trim(),
      patientName: d.patientName || '',
      patientType: formatPatientType(d.patientType),
      apptDateTime: [formatDateDisplay(d.appointmentDate), d.appointmentTime].filter(Boolean).join(' '),
      specialty,
      consultType: d.consultType || '',
      remarks: d.remarks || '',
      encodedBy: d.encodedBy || '',
      encodedDate: formatDateDisplay(rawEncodedDate),
      attendedDate: formatDateDisplay(rawAttendedDate),
      followUpNotes: String(d.followUpNotes || d.follow_up_notes || '').trim(),
      followUps
    };
  }

  function buildFilename(slipType, patientName) {
    const prefix = slipType === 'followUp'
      ? 'follow-up-slip'
      : (slipType === 'rescheduled' ? 'rescheduled-slip' : 'appointment-slip');
    const slug = String(patientName || '')
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '')
      .slice(0, 48);
    return (slug ? `${prefix}-${slug}` : prefix) + '.png';
  }

  function renderHtml(data) {
    const n = normalizeData(data);
    const { slipType, meta } = n;

    const dohLogo = n.dohLogoUrl
      ? `<img src="${esc(n.dohLogoUrl)}" alt="Department of Health Logo" />`
      : `<span class="mmwgh-slip-logo-ph">DOH<br/>LOGO</span>`;
    const mmwghLogo = n.mmwghLogoUrl
      ? `<img src="${esc(n.mmwghLogoUrl)}" alt="MMWGH Official Logo" />`
      : `<span class="mmwgh-slip-logo-ph">MMWGH<br/>LOGO</span>`;

    let bodyFields = `
      <p><strong>Name:</strong> ${esc(n.patientName)}</p>
      <p><strong>Type of Patient:</strong> ${esc(n.patientType)}</p>
    `;

    if (slipType === 'followUp') {
      bodyFields += `
        <p><strong>Follow-Up/s:</strong></p>
        <p class="mmwgh-slip-attended"><strong>Attended Date:</strong> ${esc(n.attendedDate)}</p>
        ${n.followUps.map((f) => `<div class="mmwgh-slip-fu-item">
            <p><strong>Follow-Up Date:</strong> ${esc(f.date)}</p>
            <p><strong>Specialty:</strong> ${esc(f.specialty)}</p>
          </div>`).join('')}
        ${n.followUpNotes ? `<p><strong>Instructions:</strong> ${esc(n.followUpNotes)}</p>` : ''}
        <p><strong>Encoded By:</strong> ${esc(n.encodedBy)}</p>
        ${n.encodedDate ? `<p><strong>Date Encoded:</strong> ${esc(n.encodedDate)}</p>` : ''}
      `;
    } else if (slipType === 'rescheduled') {
      bodyFields += `
        <p><strong>Appointment:</strong> ${esc(n.apptDateTime)}</p>
        <p><strong>Specialty:</strong> ${esc(n.specialty)}</p>
        <p><strong>Type of Consult:</strong> ${esc(n.consultType)}</p>
        ${n.isTeleconsult ? `<p><strong>Mode:</strong> Teleconsultation${n.teleconsultPlatform ? ' (' + esc(n.teleconsultPlatform) + ')' : ''}</p>` : ''}
        ${n.isTeleconsult && n.teleconsultLink ? `<p style="word-break:break-all;"><strong>Session Link:</strong> ${esc(n.teleconsultLink)}</p>` : ''}
        ${n.isTeleconsult && n.teleconsultNotes ? `<p><strong>Instructions:</strong> ${esc(n.teleconsultNotes)}</p>` : ''}
        <p><strong>Encoded by:</strong> ${esc(n.encodedBy)}</p>
      `;
    } else {
      bodyFields += `
        <p><strong>Appointment:</strong> ${esc(n.apptDateTime)}</p>
        <p><strong>Specialty:</strong> ${esc(n.specialty)}</p>
        <p><strong>Type of Consult:</strong> ${esc(n.consultType)}</p>
        ${n.isTeleconsult ? `<p><strong>Mode:</strong> Teleconsultation${n.teleconsultPlatform ? ' (' + esc(n.teleconsultPlatform) + ')' : ''}</p>` : ''}
        ${n.isTeleconsult && n.teleconsultLink ? `<p style="word-break:break-all;"><strong>Session Link:</strong> ${esc(n.teleconsultLink)}</p>` : ''}
        ${n.isTeleconsult && n.teleconsultNotes ? `<p><strong>Instructions:</strong> ${esc(n.teleconsultNotes)}</p>` : ''}
        <p><strong>Remarks:</strong> ${esc(n.remarks)}</p>
        <p><strong>Encoded By:</strong> ${esc(n.encodedBy)}</p>
      `;
    }

    const himuBlock = slipType === 'appointment' && !n.isTeleconsult
      ? `<p class="mmwgh-slip-himu">To HIMU for creation of chart. Please bring valid ID / Birth Certificate.</p>`
      : '';
    const remindText = n.isTeleconsult
      ? 'REMOTE TELECONSULTATION — CHECK PATIENT PORTAL FOR SESSION ACCESS'
      : 'DALHIN SA ARAW NG KONSULTA';

    const qrHours = resolveQrExpiryHours(n.specialty);
    const qrMatrix = n.qrCodeUrl ? null : buildQrMatrix(n.patientId);
    const qrBlock = (n.qrCodeUrl || qrMatrix)
      ? `<div class="mmwgh-slip-qr-col">
          <div class="mmwgh-slip-qr-box">
            ${n.qrCodeUrl ? `<img src="${esc(n.qrCodeUrl)}" alt="Patient QR Code" />` : renderQrSvg(qrMatrix, 2)}
          </div>
          ${n.patientId ? `<span class="mmwgh-slip-qr-id">${esc(n.patientId)}</span>` : ''}
          <span class="mmwgh-slip-qr-id" style="font-weight:500;opacity:.75;">Valid ${esc(qrHours)} hrs</span>
        </div>`
      : '';

    return `
      <article class="mmwgh-slip-card" aria-label="${esc(meta.title)}">
        <header class="mmwgh-slip-header">
          <div class="mmwgh-slip-logo">${dohLogo}</div>
          <div class="mmwgh-slip-org">
            <p class="org-l1">Department of Health</p>
            <p class="org-l2">Central Luzon Center for Health Development</p>
            <p class="org-l3">${esc(liveSlipConfig.hospitalName)}</p>
            <p class="org-l4">${esc(liveSlipConfig.hospitalAddress)}</p>
          </div>
          <div class="mmwgh-slip-logo">${mmwghLogo}</div>
        </header>
        <div class="mmwgh-slip-body">
          <h2 class="mmwgh-slip-title">${esc(meta.title)}</h2>
          <div class="mmwgh-slip-main">
            <div class="mmwgh-slip-fields">${bodyFields}</div>
            ${qrBlock}
          </div>
          <footer class="mmwgh-slip-footer">
            ${himuBlock}
            <p class="mmwgh-slip-remind">${esc(remindText)}<br/>OPU-MEDICAL CONTACT NUMBER:</p>
            <p class="mmwgh-slip-phone">${esc(liveSlipConfig.contactPhone)}</p>
            <div class="mmwgh-slip-code-row">
              <span class="mmwgh-slip-code">${esc(meta.docCode)}</span>
            </div>
          </footer>
        </div>
      </article>
    `;
  }

  function print(data) {
    const slipType = resolveSlipType(data, data && data.slipType);
    const meta = SLIP_META[slipType] || SLIP_META.appointment;
    const html = `<!doctype html>
      <html>
        <head>
          <meta charset="UTF-8" />
          <title>${esc(meta.title)} - MMWGH</title>
          <style>
            @page { size: auto; margin: 12mm; }
            html, body { margin: 0; padding: 0; background: #ffffff; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            ${getSlipStyles()}
            .mmwgh-slip-card { border-color: #000000; break-inside: avoid; page-break-inside: avoid; }
            .mmwgh-slip-header { border-bottom-color: #000000; }
            .mmwgh-slip-qr-box { border-color: #000000; }
          </style>
        </head>
        <body>${renderHtml(data)}</body>
      </html>`;
    const w = window.open('', '_blank', 'width=560,height=720');
    if (!w) {
      window.print();
      return;
    }
    w.document.open();
    w.document.write(html);
    w.document.close();
    w.focus();
    setTimeout(() => { try { w.print(); } catch (e) {} }, 300);
  }

  function loadLogoImage(url) {
    return new Promise((resolve) => {
      if (!url) { resolve(null); return; }
      const img = new Image();
      img.crossOrigin = 'anonymous';
      img.onload = () => resolve(img);
      img.onerror = () => resolve(null);
      img.src = url;
    });
  }

  function wrapLines(ctx, text, maxWidth) {
    const str = String(text || '').trim();
    if (!str) return [''];
    const words = str.split(/\s+/);
    const lines = [];
    let current = '';
    for (const word of words) {
      const test = current ? current + ' ' + word : word;
      if (ctx.measureText(test).width <= maxWidth) {
        current = test;
      } else {
        if (current) lines.push(current);
        current = word;
      }
    }
    if (current) lines.push(current);
    return lines;
  }

  function drawCircleLogo(ctx, cx, cy, r, img, line1, line2) {
    ctx.save();
    ctx.beginPath();
    ctx.arc(cx, cy, r, 0, Math.PI * 2);
    ctx.fillStyle = '#ffffff';
    ctx.fill();
    ctx.clip();
    if (img) {
      const ratio = Math.min((r * 2) / img.width, (r * 2) / img.height);
      const dw = img.width * ratio;
      const dh = img.height * ratio;
      ctx.drawImage(img, cx - dw / 2, cy - dh / 2, dw, dh);
    } else {
      ctx.fillStyle = '#374151';
      ctx.font = '700 9px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif';
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';
      ctx.fillText(line1, cx, cy - 5);
      ctx.fillText(line2, cx, cy + 6);
    }
    ctx.restore();
    if (!img) {
      ctx.beginPath();
      ctx.arc(cx, cy, r, 0, Math.PI * 2);
      ctx.strokeStyle = '#9ca3af';
      ctx.lineWidth = 1;
      ctx.stroke();
    }
  }

  async function download(data) {
    const n = normalizeData(data);
    const { slipType, meta } = n;
    const FONT = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif';
    const W = 460;
    const padX = 22;
    const contentW = W - padX * 2;
    const scale = 2;

    const [dohImg, mmwghImg, customQrImg] = await Promise.all([
      loadLogoImage(n.dohLogoUrl),
      loadLogoImage(n.mmwghLogoUrl),
      loadLogoImage(n.qrCodeUrl)
    ]);
    const qrMatrix = customQrImg ? null : buildQrMatrix(n.patientId);
    const hasQr = Boolean(customQrImg || qrMatrix);
    const qrBoxSize = 76;
    const qrBlockH = hasQr ? qrBoxSize + (n.patientId ? 14 : 0) : 0;
    const fieldsContentW = hasQr ? contentW - (qrBoxSize + 14) : contentW;

    const measureCanvas = document.createElement('canvas');
    const mctx = measureCanvas.getContext('2d');

    const headerMaxW = W - 156;
    const headerBlocks = [
      { text: 'Department of Health', font: `500 11px ${FONT}`, lh: 14 },
      { text: 'Central Luzon Center for Health Development', font: `700 11px ${FONT}`, lh: 14 },
      { text: 'MARIVELES MENTAL WELLNESS AND GENERAL HOSPITAL', font: `700 11px ${FONT}`, lh: 14 },
      { text: 'P. Monroe Street, Poblacion, Mariveles, Bataan, Philippines, 2105', font: `400 10px ${FONT}`, lh: 13 }
    ].map((b) => {
      mctx.font = b.font;
      return Object.assign({}, b, { lines: wrapLines(mctx, b.text, headerMaxW) });
    });
    const headerTextH = headerBlocks.reduce((acc, b) => acc + b.lines.length * b.lh, 0);
    const headerH = Math.max(76, headerTextH + 24);

    // Build structured field rows
    const rows = [
      { label: 'Name:', value: n.patientName },
      { label: 'Type of Patient:', value: n.patientType }
    ];
    if (slipType === 'followUp') {
      rows.push({ label: 'Follow-Up/s:', value: '' });
      rows.push({ label: 'Attended Date:', value: n.attendedDate, small: true, gapAfter: 8 });
      n.followUps.forEach((f, idx) => {
        rows.push({ label: 'Follow-Up Date:', value: f.date });
        rows.push({ label: 'Specialty:', value: f.specialty, gapAfter: idx === n.followUps.length - 1 ? 6 : 10 });
      });
      if (n.followUpNotes) rows.push({ label: 'Instructions:', value: n.followUpNotes });
      rows.push({ label: 'Encoded By:', value: n.encodedBy });
      if (n.encodedDate) rows.push({ label: 'Date Encoded:', value: n.encodedDate });
    } else if (slipType === 'rescheduled') {
      rows.push({ label: 'Appointment:', value: n.apptDateTime });
      rows.push({ label: 'Specialty:', value: n.specialty });
      rows.push({ label: 'Type of Consult:', value: n.consultType });
      if (n.isTeleconsult) {
        rows.push({ label: 'Mode:', value: 'Teleconsultation' + (n.teleconsultPlatform ? ` (${n.teleconsultPlatform})` : '') });
        if (n.teleconsultLink) rows.push({ label: 'Session Link:', value: n.teleconsultLink });
        if (n.teleconsultNotes) rows.push({ label: 'Instructions:', value: n.teleconsultNotes });
      }
      rows.push({ label: 'Encoded by:', value: n.encodedBy });
    } else {
      rows.push({ label: 'Appointment:', value: n.apptDateTime });
      rows.push({ label: 'Specialty:', value: n.specialty });
      rows.push({ label: 'Type of Consult:', value: n.consultType });
      if (n.isTeleconsult) {
        rows.push({ label: 'Mode:', value: 'Teleconsultation' + (n.teleconsultPlatform ? ` (${n.teleconsultPlatform})` : '') });
        if (n.teleconsultLink) rows.push({ label: 'Session Link:', value: n.teleconsultLink });
        if (n.teleconsultNotes) rows.push({ label: 'Instructions:', value: n.teleconsultNotes });
      }
      rows.push({ label: 'Remarks:', value: n.remarks });
      rows.push({ label: 'Encoded By:', value: n.encodedBy });
    }

    const laidOutRows = rows.map((row) => {
      const sz = row.small ? 11 : 12.5;
      const lh = row.small ? 15 : 18;
      const boldFont = `600 ${sz}px ${FONT}`;
      const regFont = `400 ${sz}px ${FONT}`;
      mctx.font = boldFont;
      const labelW = mctx.measureText(row.label + ' ').width;
      mctx.font = regFont;
      const valStr = String(row.value || '').trim();
      const words = valStr ? valStr.split(/\s+/) : [];
      const lines = [];
      let cur = '';
      let firstLine = true;
      for (const word of words) {
        const limit = firstLine ? Math.max(40, fieldsContentW - labelW) : fieldsContentW;
        const test = cur ? cur + ' ' + word : word;
        if (mctx.measureText(test).width <= limit) {
          cur = test;
        } else {
          if (cur) lines.push(cur);
          cur = word;
          firstLine = false;
        }
      }
      if (cur || !lines.length) lines.push(cur);
      return {
        label: row.label,
        labelW,
        boldFont,
        regFont,
        lh,
        lines,
        gapAfter: row.gapAfter !== undefined ? row.gapAfter : 5
      };
    });

    const rawFieldsH = laidOutRows.reduce((acc, r) => acc + r.lines.length * r.lh + r.gapAfter, 0);
    const fieldsH = Math.max(rawFieldsH, qrBlockH);
    let himuLines = [];
    if (slipType === 'appointment' && !n.isTeleconsult) {
      mctx.font = `700 10.5px ${FONT}`;
      himuLines = wrapLines(mctx, 'To HIMU for creation of chart. Please bring valid ID / Birth Certificate.', contentW);
    }
    const footerH = (himuLines.length ? himuLines.length * 15 + 12 : 0) + 17 * 2 + 18 + 14 + 26;
    const totalH = Math.ceil(headerH + 18 + 26 + fieldsH + 18 + footerH + 14);

    const canvas = document.createElement('canvas');
    canvas.width = W * scale;
    canvas.height = totalH * scale;
    const ctx = canvas.getContext('2d');
    ctx.scale(scale, scale);

    // Card background & border
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, W, totalH);
    ctx.strokeStyle = '#9ca3af';
    ctx.lineWidth = 1;
    ctx.strokeRect(0.5, 0.5, W - 1, totalH - 1);

    // Header logos & org text
    const logoY = headerH / 2;
    drawCircleLogo(ctx, 42, logoY, 24, dohImg, 'DOH', 'LOGO');
    drawCircleLogo(ctx, W - 42, logoY, 24, mmwghImg, 'MMWGH', 'LOGO');

    ctx.fillStyle = '#000000';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'top';
    let y = (headerH - headerTextH) / 2;
    for (const block of headerBlocks) {
      ctx.font = block.font;
      for (const ln of block.lines) {
        ctx.fillText(ln, W / 2, y);
        y += block.lh;
      }
    }

    // Header divider
    ctx.beginPath();
    ctx.moveTo(1, headerH);
    ctx.lineTo(W - 1, headerH);
    ctx.strokeStyle = '#d1d5db';
    ctx.lineWidth = 1;
    ctx.stroke();

    // Title
    y = headerH + 18;
    ctx.fillStyle = '#000000';
    ctx.font = `700 14px ${FONT}`;
    ctx.textAlign = 'center';
    ctx.fillText(meta.title, W / 2, y);
    y += 28;

    // Dynamic fields (left) + QR Code (right)
    const bodyStartY = y;
    ctx.textAlign = 'left';
    for (const row of laidOutRows) {
      ctx.font = row.boldFont;
      ctx.fillText(row.label, padX, y);
      ctx.font = row.regFont;
      row.lines.forEach((ln, idx) => {
        if (ln) ctx.fillText(ln, idx === 0 ? padX + row.labelW : padX, y);
        y += row.lh;
      });
      y += row.gapAfter;
    }

    if (hasQr) {
      const qrX = W - padX - qrBoxSize;
      const qrY = bodyStartY;
      ctx.fillStyle = '#ffffff';
      ctx.fillRect(qrX, qrY, qrBoxSize, qrBoxSize);
      ctx.strokeStyle = '#d1d5db';
      ctx.lineWidth = 1;
      ctx.strokeRect(qrX + 0.5, qrY + 0.5, qrBoxSize - 1, qrBoxSize - 1);

      if (customQrImg) {
        ctx.drawImage(customQrImg, qrX + 3, qrY + 3, qrBoxSize - 6, qrBoxSize - 6);
      } else if (qrMatrix) {
        const quiet = 2;
        const totalMods = qrMatrix.length + quiet * 2;
        const cell = (qrBoxSize - 4) / totalMods;
        const ox = qrX + 2;
        const oy = qrY + 2;
        ctx.fillStyle = '#000000';
        for (let r = 0; r < qrMatrix.length; r++) {
          for (let c = 0; c < qrMatrix.length; c++) {
            if (qrMatrix[r][c]) {
              const mx = ox + (c + quiet) * cell;
              const my = oy + (r + quiet) * cell;
              ctx.fillRect(mx, my, Math.ceil(cell * 100) / 100 + 0.15, Math.ceil(cell * 100) / 100 + 0.15);
            }
          }
        }
      }
      if (n.patientId) {
        ctx.fillStyle = '#000000';
        ctx.font = '700 8.5px ui-monospace, SFMono-Regular, Menlo, Consolas, monospace';
        ctx.textAlign = 'center';
        ctx.fillText(n.patientId, qrX + qrBoxSize / 2, qrY + qrBoxSize + 4);
      }
    }
    y = Math.max(y, bodyStartY + qrBlockH);

    // Footer reminders
    y += 16;
    ctx.textAlign = 'center';
    if (himuLines.length) {
      ctx.font = `700 10.5px ${FONT}`;
      for (const ln of himuLines) {
        ctx.fillText(ln, W / 2, y);
        y += 15;
      }
      y += 10;
    }
    ctx.font = `400 11px ${FONT}`;
    ctx.fillText(n.isTeleconsult ? 'REMOTE TELECONSULTATION — CHECK PATIENT PORTAL' : 'DALHIN SA ARAW NG KONSULTA', W / 2, y);
    y += 17;
    ctx.fillText('OPU-MEDICAL CONTACT NUMBER:', W / 2, y);
    y += 17;
    ctx.font = `700 11.5px ${FONT}`;
    ctx.fillText(liveSlipConfig.contactPhone || '(047) 935-4645', W / 2, y);
    y += 22;

    // Code divider & bottom-right document code
    ctx.beginPath();
    ctx.moveTo(padX, y);
    ctx.lineTo(W - padX, y);
    ctx.strokeStyle = '#e5e7eb';
    ctx.lineWidth = 1;
    ctx.stroke();
    y += 10;

    ctx.textAlign = 'right';
    ctx.font = `700 10.5px ${FONT}`;
    ctx.fillText(meta.docCode, W - padX, y);

    const link = document.createElement('a');
    link.href = canvas.toDataURL('image/png');
    link.download = buildFilename(slipType, n.patientName);
    document.body.appendChild(link);
    link.click();
    link.remove();
  }

  let activeData = null;

  function ensureModal() {
    let overlay = document.getElementById('mmwghSlipModal');
    if (overlay) return overlay;

    if (!document.getElementById('mmwghSlipStyles')) {
      const style = document.createElement('style');
      style.id = 'mmwghSlipStyles';
      style.textContent = `
        ${getSlipStyles()}
        #mmwghSlipModal {
          position: fixed;
          inset: 0;
          z-index: 10050;
          display: none;
          align-items: center;
          justify-content: center;
          padding: 16px;
          background: rgba(15, 23, 42, 0.55);
          backdrop-filter: blur(4px);
        }
        #mmwghSlipModal.open { display: flex; }
        .mmwgh-slip-dialog {
          background: #f8fafc;
          border-radius: 14px;
          border: 1px solid #e2e8f0;
          box-shadow: 0 24px 60px rgba(15, 23, 42, 0.28);
          width: 100%;
          max-width: 500px;
          max-height: 92vh;
          display: flex;
          flex-direction: column;
          overflow: hidden;
        }
        .mmwgh-slip-dialog-head {
          display: flex;
          align-items: center;
          justify-content: space-between;
          padding: 12px 18px;
          background: #ffffff;
          border-bottom: 1px solid #e2e8f0;
        }
        .mmwgh-slip-dialog-head h3 {
          margin: 0;
          font-size: 0.92rem;
          font-weight: 700;
          color: #0f172a;
        }
        .mmwgh-slip-dialog-close {
          border: none;
          background: transparent;
          font-size: 1.35rem;
          line-height: 1;
          color: #64748b;
          cursor: pointer;
          padding: 4px 8px;
          border-radius: 6px;
        }
        .mmwgh-slip-dialog-close:hover { background: #f1f5f9; color: #0f172a; }
        .mmwgh-slip-dialog-body {
          padding: 18px;
          overflow-y: auto;
        }
        .mmwgh-slip-dialog-foot {
          display: flex;
          align-items: center;
          justify-content: flex-end;
          gap: 10px;
          padding: 12px 18px;
          background: #ffffff;
          border-top: 1px solid #e2e8f0;
        }
        .mmwgh-slip-btn {
          display: inline-flex;
          align-items: center;
          gap: 6px;
          height: 38px;
          padding: 0 16px;
          border-radius: 8px;
          font-size: 0.83rem;
          font-weight: 600;
          cursor: pointer;
          border: 1px solid #cbd5e1;
          background: #ffffff;
          color: #334155;
        }
        .mmwgh-slip-btn:hover { background: #f8fafc; color: #0f172a; }
        .mmwgh-slip-btn.primary {
          background: #15803d;
          border-color: #15803d;
          color: #ffffff;
        }
        .mmwgh-slip-btn.primary:hover { background: #166534; }
      `;
      document.head.appendChild(style);
    }

    overlay = document.createElement('div');
    overlay.id = 'mmwghSlipModal';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.setAttribute('aria-labelledby', 'mmwghSlipModalTitle');
    overlay.innerHTML = `
      <div class="mmwgh-slip-dialog">
        <div class="mmwgh-slip-dialog-head">
          <h3 id="mmwghSlipModalTitle">Printable Patient Slip</h3>
          <button type="button" class="mmwgh-slip-dialog-close" id="mmwghSlipCloseX" aria-label="Close">&times;</button>
        </div>
        <div class="mmwgh-slip-dialog-body" id="mmwghSlipBody"></div>
        <div class="mmwgh-slip-dialog-foot">
          <button type="button" class="mmwgh-slip-btn" id="mmwghSlipCloseBtn">Close</button>
          <button type="button" class="mmwgh-slip-btn" id="mmwghSlipDownloadBtn">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            <span>Download</span>
          </button>
          <button type="button" class="mmwgh-slip-btn primary" id="mmwghSlipPrintBtn">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
            <span>Print Slip</span>
          </button>
        </div>
      </div>
    `;
    document.body.appendChild(overlay);

    const close = () => overlay.classList.remove('open');
    overlay.addEventListener('click', (e) => { if (e.target === overlay) close(); });
    overlay.querySelector('#mmwghSlipCloseX').addEventListener('click', close);
    overlay.querySelector('#mmwghSlipCloseBtn').addEventListener('click', close);
    overlay.querySelector('#mmwghSlipDownloadBtn').addEventListener('click', async () => {
      if (!activeData) return;
      const btn = overlay.querySelector('#mmwghSlipDownloadBtn');
      if (btn) btn.disabled = true;
      try {
        await download(activeData);
      } finally {
        if (btn) btn.disabled = false;
      }
    });
    overlay.querySelector('#mmwghSlipPrintBtn').addEventListener('click', () => {
      if (activeData) print(activeData);
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && overlay.classList.contains('open')) close();
    });

    return overlay;
  }

  function openModal(data) {
    activeData = data || {};
    const slipType = resolveSlipType(activeData, activeData.slipType);
    const meta = SLIP_META[slipType] || SLIP_META.appointment;
    const overlay = ensureModal();
    const titleEl = overlay.querySelector('#mmwghSlipModalTitle');
    const bodyEl = overlay.querySelector('#mmwghSlipBody');
    if (titleEl) titleEl.textContent = meta.title;
    if (bodyEl) bodyEl.innerHTML = renderHtml(Object.assign({}, activeData, { slipType }));
    overlay.classList.add('open');
  }

  window.PatientSlip = { canGenerateSlip, resolveSlipType, buildQrMatrix, renderHtml, openModal, print, download, buildFilename };
})();
