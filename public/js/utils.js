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

function showSuccessToast(msg) { showToast(msg); }

// ── Auth guard: redirect to login if not authenticated ───────────────────────

async function requireLogin(expectedRole = null) {
  try {
    const res  = await fetch(`${API_BASE}/auth.php?action=check`);
    const data = await res.json();
    if (!data.loggedIn) {
      window.location.href = '/sdn/pages/login.php';
      return false;
    }
    if (expectedRole && data.role !== expectedRole) {
      alert('Access denied: insufficient permissions.');
      window.location.href = '/sdn/pages/login.php';
      return false;
    }
    return data;
  } catch {
    window.location.href = '/sdn/pages/login.php';
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
