// utils.js — shared utility functions
// ── CONFIGURE THIS to match your XAMPP project folder name ──────────────────
//    If your project is at: http://localhost/sdn/  → keep '/sdn/api'
//    If renamed to 'myproject':                   → change to '/myproject/api'
const API_BASE = '/sdn/api';

// ── Date & value helpers ─────────────────────────────────────────────────────

function formatDate(isoDate) {
  if (!isoDate) return '';
  const [year, month, day] = isoDate.split('-');
  const months = [
    'January','February','March','April','May','June',
    'July','August','September','October','November','December'
  ];
  return `${months[parseInt(month, 10) - 1]} ${parseInt(day, 10)}, ${year}`;
}

function getVal(id) {
  const el = document.getElementById(id);
  return el ? el.value.trim() : '';
}

function setText(id, value) {
  const el = document.getElementById(id);
  if (el) el.textContent = value;
}

// ── Toast notification ───────────────────────────────────────────────────────

function showToast(message = 'Action completed successfully.', type = 'success') {
  const toast  = document.getElementById('successToast');
  const msgEl  = document.getElementById('toastMsg');
  const iconEl = toast?.querySelector('.toast-icon');
  if (!toast) return;

  if (msgEl)  msgEl.textContent  = message;
  if (iconEl) iconEl.textContent = type === 'error' ? '✕' : type === 'info' ? 'ℹ' : '✓';

  toast.className = 'toast';
  toast.classList.add(`toast-${type}`);
  toast.style.display = 'flex';

  requestAnimationFrame(() => requestAnimationFrame(() => toast.classList.add('show')));

  clearTimeout(toast._hideTimer);
  toast._hideTimer = setTimeout(() => {
    toast.classList.remove('show');
    setTimeout(() => { toast.style.display = 'none'; }, 350);
  }, 4000);
}

// ── Custom Notice Modal UI (replaces browser alert) ──────────────────────────
function showNoticeModal(message, title = 'Registration Notice', type = 'info') {
  let modal = document.getElementById('customNoticeModal');
  if (!modal) {
    modal = document.createElement('div');
    modal.id = 'customNoticeModal';
    modal.className = 'custom-notice-modal';
    modal.innerHTML = `
      <div class="custom-notice-box">
        <div class="custom-notice-header">
          <div class="custom-notice-icon ${type}">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          </div>
          <div class="custom-notice-text">
            <h4 class="custom-notice-title" id="noticeTitle">${title}</h4>
            <p class="custom-notice-msg" id="noticeMsg">${message}</p>
          </div>
        </div>
        <div class="custom-notice-actions">
          <button type="button" class="custom-notice-btn" id="noticeBtnOk">Understood</button>
        </div>
      </div>
    `;
    document.body.appendChild(modal);

    const btn = modal.querySelector('#noticeBtnOk');
    const closeNotice = () => {
      modal.classList.remove('show');
    };
    btn.addEventListener('click', closeNotice);
    modal.addEventListener('click', (e) => {
      if (e.target === modal) closeNotice();
    });
  } else {
    const titleEl = modal.querySelector('#noticeTitle');
    const msgEl = modal.querySelector('#noticeMsg');
    const iconEl = modal.querySelector('.custom-notice-icon');
    if (titleEl) titleEl.textContent = title;
    if (msgEl) msgEl.textContent = message;
    if (iconEl) iconEl.className = `custom-notice-icon ${type}`;
  }

  requestAnimationFrame(() => {
    modal.classList.add('show');
    const btn = modal.querySelector('#noticeBtnOk');
    if (btn) btn.focus();
  });
}

// ── Executive First-Time Patient Notice Modal UI ─────────────────────────────
function showFirstTimePatientNotice(onClose) {
  let modal = document.getElementById('firstTimePatientModal');
  if (!modal) {
    modal = document.createElement('div');
    modal.id = 'firstTimePatientModal';
    modal.className = 'ftp-modal-overlay';
    modal.innerHTML = `
      <div class="ftp-modal-card" role="dialog" aria-modal="true" aria-labelledby="ftpTitle">
        <div class="ftp-modal-header">
          <div class="ftp-badge-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
              <circle cx="9" cy="7" r="4"/>
              <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
          </div>
          <div class="ftp-header-text">
            <span class="ftp-tag">Hospital Registration Policy</span>
            <h3 class="ftp-title" id="ftpTitle">First-Time Patient Consultation</h3>
          </div>
          <button type="button" class="ftp-btn-close" id="ftpCloseBtn" aria-label="Close">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>

        <div class="ftp-modal-body">
          <p class="ftp-notice-desc">
            Welcome to <strong>MMWGH LINKod</strong>. In accordance with clinical assessment protocols, all <strong>first-time patients</strong> must undergo an initial on-site physical evaluation to establish an official hospital medical record.
          </p>

          <div class="ftp-policy-card">
            <div class="ftp-policy-header">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
              <span>Automatic Presets Applied:</span>
            </div>
            <div class="ftp-policy-grid">
              <div class="ftp-policy-item">
                <span class="ftp-policy-label">Appointment Type</span>
                <span class="ftp-policy-value"><span class="ftp-dot"></span> Appointment Only</span>
              </div>
              <div class="ftp-policy-item">
                <span class="ftp-policy-label">Consultation Mode</span>
                <span class="ftp-policy-value"><span class="ftp-dot"></span> Face-to-Face</span>
              </div>
            </div>
          </div>

          <div class="ftp-policy-note">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            <span>Online consultations and teleconsultation slots will become unlocked for your follow-up visits once your hospital record is active.</span>
          </div>
        </div>

        <div class="ftp-modal-footer">
          <button type="button" class="ftp-btn-primary" id="ftpAcknowledgeBtn">
            <span>Understood &amp; Proceed</span>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
          </button>
        </div>
      </div>
    `;
    document.body.appendChild(modal);

    const closeModal = () => {
      modal.classList.remove('show');
      setTimeout(() => { modal.style.display = 'none'; }, 280);
      if (typeof onClose === 'function') onClose();
    };

    modal.querySelector('#ftpCloseBtn').addEventListener('click', closeModal);
    modal.querySelector('#ftpAcknowledgeBtn').addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => {
      if (e.target === modal) closeModal();
    });
  }

  modal.style.display = 'flex';
  requestAnimationFrame(() => {
    modal.classList.add('show');
    const ackBtn = modal.querySelector('#ftpAcknowledgeBtn');
    if (ackBtn) ackBtn.focus();
  });
}

function showSuccessToast(msg) { showToast(msg); }

// ── Auth guard: redirect to login if not authenticated ───────────────────────

async function requireLogin(expectedRole = null) {
  try {
    const res  = await fetch(`${API_BASE}/auth.php?action=check`);
    const data = await res.json();
    if (!data.loggedIn) {
      window.location.href = (expectedRole === 'admin' || expectedRole === 'staff')
        ? '/sdn/pages/portal@admin$staff_2026.php'
        : '/sdn/pages/login.php';
      return false;
    }
    if (data.csrf_token) {
      window.CSRF_TOKEN = data.csrf_token;
    }
    if (expectedRole && data.role !== expectedRole) {
      alert('Access denied: insufficient permissions.');
      window.location.href = (expectedRole === 'admin' || expectedRole === 'staff')
        ? '/sdn/pages/portal@admin$staff_2026.php'
        : '/sdn/pages/login.php';
      return false;
    }
    return data;
  } catch {
    window.location.href = (expectedRole === 'admin' || expectedRole === 'staff')
      ? '/sdn/pages/portal@admin$staff_2026.php'
      : '/sdn/pages/login.php';
    return false;
  }
}

// ── Stat card counter animation ──────────────────────────────────────────────

function animateStatTo(id, target) {
  const el = document.getElementById(id);
  if (!el) return;
  const start  = parseInt(el.textContent, 10) || 0;
  const diff   = target - start;
  const dur    = 400;
  const startT = performance.now();
  function step(now) {
    const p = Math.min((now - startT) / dur, 1);
    el.textContent = Math.round(start + diff * p);
    if (p < 1) requestAnimationFrame(step);
  }
  requestAnimationFrame(step);
}

// ── CSV export ───────────────────────────────────────────────────────────────

function exportTableToCSV(table, filename = 'export.csv') {
  const rows = Array.from(table.querySelectorAll('tr'));
  const csv  = rows.map(row =>
    Array.from(row.querySelectorAll('th, td'))
      .map(cell => `"${cell.innerText.trim().replace(/"/g, '""')}"`)
      .join(',')
  ).join('\n');
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const url  = URL.createObjectURL(blob);
  const a    = document.createElement('a');
  a.href = url; a.download = filename; a.click();
  URL.revokeObjectURL(url);
}

// ── Timestamp helpers ────────────────────────────────────────────────────────

function currentTimeString() {
  const now  = new Date();
  const h    = ((now.getHours() % 12) || 12).toString().padStart(2, '0');
  const m    = now.getMinutes().toString().padStart(2, '0');
  const s    = now.getSeconds().toString().padStart(2, '0');
  const ampm = now.getHours() >= 12 ? 'PM' : 'AM';
  return `${h}:${m}:${s} ${ampm}`;
}

function setUpdatedTime() { setText('updatedTime', currentTimeString()); }

function updateClock() {
  const now   = new Date();
  const hours = now.getHours();
  const mins  = now.getMinutes().toString().padStart(2, '0');
  const secs  = now.getSeconds().toString().padStart(2, '0');
  const ampm  = hours >= 12 ? 'PM' : 'AM';
  const h12   = ((hours % 12) || 12).toString().padStart(2, '0');
  setText('clockTime', `${h12}:${mins}:${secs} ${ampm}`);

  const days   = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
  const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
  const clockDate = document.getElementById('clockDate');
  if (clockDate) {
    clockDate.textContent =
      `${days[now.getDay()]}, ${months[now.getMonth()]} ${now.getDate()}, ${now.getFullYear()}`;
  }
}

// ── Schedule page helpers ────────────────────────────────────────────────────

function calcScheduleStats() {
  const badges = document.querySelectorAll('.status-badge');
  if (!badges.length) return;
  let avail = 0, booked = 0, leave = 0;
  badges.forEach(b => {
    if (b.classList.contains('badge-available'))   avail++;
    else if (b.classList.contains('badge-booked')) booked++;
    else if (b.classList.contains('badge-leave'))  leave++;
  });
  animateStatTo('statTotal',  avail + booked + leave);
  animateStatTo('statAvail',  avail);
  animateStatTo('statBooked', booked);
  animateStatTo('statLeave',  leave);
}

function showRefreshBanner() {
  const banner = document.getElementById('refreshBanner');
  if (!banner) return;
  banner.classList.add('show');
  setTimeout(() => banner.classList.remove('show'), 3500);
}

function refreshSchedules() {
  setUpdatedTime();
  calcScheduleStats();
  showRefreshBanner();
}

document.addEventListener("DOMContentLoaded", () => {
  const hrnEl = document.getElementById("hrn");
  if (hrnEl && !hrnEl.value) {
    const randomNum = Math.floor(1000000 + Math.random() * 9000000);
    hrnEl.value = `HRN-${new Date().getFullYear()}-${randomNum}`;
  }
});
