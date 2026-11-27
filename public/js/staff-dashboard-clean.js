/**
 * public/js/staff-dashboard-clean.js
 * Minimalist Clinical Staff Portal Controller
 */

document.addEventListener('DOMContentLoaded', () => {
  let allPatients = [];
  let allDoctors = [];
  let departmentDoctors = [];
  let currentView = 'overview';

  // Filter & Sort State
  let consultSearch = '';
  let consultStatus = 'all';
  let consultDate = '';
  let staffCalCursor = new Date();
  staffCalCursor.setDate(1);

  let recordsSearch = '';
  let recordsSort = 'date_desc';
  let recordsStatus = 'all';

  let schedDept = 'all';
  let schedSearch = '';
  let schedSpec = 'all';
  let schedStatus = 'all';
  let schedSort = 'dept_name';

  // DOM References
  const viewOverview = document.getElementById('view-overview');
  const viewConsultation = document.getElementById('view-consultation');
  const viewRecords = document.getElementById('view-records');
  const viewSchedules = document.getElementById('view-schedules');
  const viewSettings = document.getElementById('view-settings');
  const toastEl = document.getElementById('cleanToast');

  // Clear browser-autofilled credentials (e.g. "admin") from search inputs
  ['globalStaffSearchInput', 'consultSearchInput', 'recordsSearchInput', 'schedSearchInput', 'manualQrPatientId'].forEach(id => {
    const el = document.getElementById(id);
    if (el && el.value) el.value = '';
  });
  setTimeout(() => {
    ['globalStaffSearchInput', 'consultSearchInput', 'recordsSearchInput', 'schedSearchInput'].forEach(id => {
      const el = document.getElementById(id);
      if (el && el.value && el.value.toLowerCase() === (window.STAFF_USERNAME || 'admin').toLowerCase()) {
        el.value = '';
      }
    });
  }, 150);

  function formatMedicationsReadable(raw) {
    if (!raw) return '';
    if (Array.isArray(raw)) {
      return raw.map(item => {
        if (!item) return '';
        if (typeof item === 'string') return item;
        const name = item.name || item.medicine || item.drug || '';
        const dosage = item.dosage || item.instructions || '';
        const qty = item.quantity ? ` (Qty: ${item.quantity})` : '';
        return [name, dosage].filter(Boolean).join(' — ') + qty;
      }).filter(Boolean).join('; ');
    }
    if (typeof raw === 'object') {
      return formatMedicationsReadable([raw]);
    }
    const str = String(raw).trim();
    if (!str) return '';
    if (str.startsWith('[') || str.startsWith('{')) {
      try {
        return formatMedicationsReadable(JSON.parse(str));
      } catch (e) {
        return str;
      }
    }
    return str;
  }

    // ── Dashboard Quick QR & Name Lookup ──
    window.openDashboardQrScanner = function () {
      switchView('settings');
      setTimeout(() => {
        const startBtn = document.getElementById('btnStartQrScan');
        if (startBtn) {
          startBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
          startBtn.click();
        }
      }, 150);
    };

    function closeOverviewLookupResults() {
      const box = document.getElementById('overviewLookupResults');
      if (box) box.style.display = 'none';
    }

    document.addEventListener('click', (e) => {
      if (!e.target.closest('.ov-quick-lookup-search')) {
        closeOverviewLookupResults();
      }
    });

    window.runOverviewPatientLookup = function () {
      const inp = document.getElementById('overviewQuickLookup');
      const box = document.getElementById('overviewLookupResults');
      const q = (inp ? inp.value : '').trim().toLowerCase();
      if (!q) {
        closeOverviewLookupResults();
        showToast('Enter a patient name or ID to search.', 2200, 'info');
        return;
      }

      const pool = (allPatients || []).filter(p => String(p.status || '').toLowerCase() !== 'registered');
      const exactIdMatch = pool.find(p =>
        String(p.patient_id || '').toLowerCase() === q ||
        String(p.reference_no || '').toLowerCase() === q
      );
      if (exactIdMatch) {
        closeOverviewLookupResults();
        openPatientDetailsModal(exactIdMatch.patient_id);
        return;
      }

      const matches = pool.filter(p =>
        String(p.patient_id || '').toLowerCase().includes(q) ||
        String(p.reference_no || '').toLowerCase().includes(q) ||
        String(p.hrn || '').toLowerCase().includes(q) ||
        String(p.full_name || '').toLowerCase().includes(q)
      );

      if (!matches.length) {
        closeOverviewLookupResults();
        showToast('No matching patient found in this department.', 2600, 'warning');
        return;
      }

      if (matches.length === 1 || !box) {
        closeOverviewLookupResults();
        openPatientDetailsModal(matches[0].patient_id);
        return;
      }

      box.innerHTML = `
        <div style="padding:8px 12px; font-size:0.72rem; font-weight:700; color:#64748b; text-transform:uppercase; border-bottom:1px solid #f1f5f9;">
          ${matches.length} Matching Records — Select one
        </div>
        ${matches.slice(0, 10).map(p => {
          const dt = formatDateBadge(p.appointment_date || p.created_at);
          const slot = p.preferred_time_slot || formatTime12(p.scheduled_time) || '8:00 AM';
          const ref = p.reference_no || p.patient_id || '';
          const modeTag = String(p.consultation_mode || '').trim().toLowerCase() === 'teleconsultation' ? ' · Teleconsult' : '';
          return `
            <button type="button" role="option"
                    onclick="document.getElementById('overviewLookupResults').style.display='none'; openPatientDetailsModal('${escapeHtml(p.patient_id)}')"
                    style="width:100%; text-align:left; background:#ffffff; border:0; border-bottom:1px solid #f1f5f9; padding:9px 12px; cursor:pointer; display:flex; align-items:center; justify-content:space-between; gap:10px;"
                    onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='#ffffff'">
              <div style="min-width:0;">
                <div style="font-size:0.84rem; font-weight:700; color:#0f172a;">${escapeHtml(p.full_name || 'Patient')} <span style="font-weight:600; color:#15803d; font-size:0.76rem;">(${escapeHtml(ref)})</span></div>
                <div style="font-size:0.74rem; color:#64748b; margin-top:2px;">${escapeHtml(dt.monthDay)} &middot; ${escapeHtml(slot)}${escapeHtml(modeTag)}</div>
              </div>
              <div style="flex-shrink:0;">${renderOverviewStatusPill(p.status || 'pending')}</div>
            </button>
          `;
        }).join('')}
      `;
      box.style.display = 'block';
    };

    // ── Staff Patient Count Calendar & Date Filter ──
    window.setConsultDateFilter = function (isoDate) {
      consultDate = (isoDate || '').trim();
      const inp = document.getElementById('consultDateFilter');
      if (inp) inp.value = consultDate;
      const clr = document.getElementById('btnClearConsultDate');
      if (clr) clr.style.display = consultDate ? 'inline-flex' : 'none';
      if (consultDate && /^\d{4}-\d{2}-\d{2}$/.test(consultDate)) {
        const [y, m] = consultDate.split('-').map(Number);
        staffCalCursor = new Date(y, m - 1, 1);
      }
      renderStaffQueueCalendar();
      renderConsultations();
    };

    window.setConsultDateFilterToday = function () {
      const now = new Date();
      const todayIso = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
      window.setConsultDateFilter(todayIso);
    };

    window.shiftStaffCalMonth = function (delta) {
      staffCalCursor = new Date(staffCalCursor.getFullYear(), staffCalCursor.getMonth() + delta, 1);
      renderStaffQueueCalendar();
    };

    function renderStaffQueueCalendar() {
      const grid = document.getElementById('staffCalGrid');
      const title = document.getElementById('staffCalMonthTitle');
      if (!grid || !title) return;

      const year = staffCalCursor.getFullYear();
      const month = staffCalCursor.getMonth();
      title.textContent = staffCalCursor.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

      const countsByDate = {};
      (allPatients || []).forEach(p => {
        const d = String(p.appointment_date || p.created_at || '').split(' ')[0];
        if (/^\d{4}-\d{2}-\d{2}$/.test(d)) countsByDate[d] = (countsByDate[d] || 0) + 1;
      });

      const firstDow = new Date(year, month, 1).getDay();
      const daysInMonth = new Date(year, month + 1, 0).getDate();
      const today = new Date();
      const todayIso = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;

      const headers = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
        .map(h => `<div style="text-align:center; font-size:0.7rem; font-weight:700; color:#64748b; padding:4px 0;">${h}</div>`).join('');

      let cells = '';
      for (let i = 0; i < firstDow; i++) {
        cells += '<div></div>';
      }
      for (let day = 1; day <= daysInMonth; day++) {
        const iso = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const cnt = countsByDate[iso] || 0;
        const isSel = consultDate === iso;
        const isToday = todayIso === iso;
        const border = isSel ? '2px solid #15803d' : (isToday ? '1.5px solid #16a34a' : '1px solid #e2e8f0');
        const bg = isSel ? '#dcfce7' : (cnt > 0 ? '#f8fafc' : '#ffffff');
        cells += `
          <button type="button" onclick="setConsultDateFilter('${iso}')" style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding:6px 4px; min-height:46px; border:${border}; border-radius:8px; background:${bg}; cursor:pointer; font-family:inherit;">
            <span style="font-size:0.78rem; font-weight:${isSel || isToday ? '700' : '600'}; color:#0f172a;">${day}</span>
            ${cnt > 0 ? `<span style="margin-top:2px; font-size:0.66rem; font-weight:700; color:#15803d; background:#dcfce7; padding:1px 6px; border-radius:999px;">${cnt} pt</span>` : '<span style="height:14px;"></span>'}
          </button>`;
      }
      grid.innerHTML = headers + cells;
    }

  // ── 1. Time-of-Day Greeting ──
  function updateGreeting() {
    const greetEl = document.getElementById('staffGreetingTitle');
    if (!greetEl) return;
    const hour = new Date().getHours();
    const prefix = hour < 12 ? 'Good Morning' : (hour < 18 ? 'Good Afternoon' : 'Good Evening');
    const firstName = window.STAFF_FIRST_NAME || 'Staff';
    greetEl.textContent = `${prefix}, ${firstName}!`;
  }
  updateGreeting();

  // ── 2. Toast Feedback ──
  let toastTimer = null;
  let toastHovered = false;
  function dismissToast() {
    clearTimeout(toastTimer);
    if (toastEl) toastEl.classList.remove('is-visible');
  }

  function showToast(msg, duration = 3600, tone = 'success') {
    if (!toastEl) return;
    if (typeof duration === 'string') {
      tone = duration;
      duration = 3600;
    }
    const ms = Math.max(1800, Number(duration) || 3600);
    const t = tone === 'error' || tone === 'warning' || tone === 'info' ? tone : 'success';
    const titles = {
      success: 'Success',
      error: 'Action Failed',
      warning: 'Attention',
      info: 'Notice'
    };
    const icons = {
      success: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>',
      error: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
      warning: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
      info: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>'
    };

    if (!toastEl.dataset.hoverBound) {
      toastEl.dataset.hoverBound = '1';
      toastEl.addEventListener('mouseenter', () => {
        toastHovered = true;
        clearTimeout(toastTimer);
      });
      toastEl.addEventListener('mouseleave', () => {
        toastHovered = false;
        if (toastEl.classList.contains('is-visible')) {
          toastTimer = setTimeout(dismissToast, 1800);
        }
      });
    }

    clearTimeout(toastTimer);
    toastEl.classList.remove('is-error', 'is-warning', 'is-info', 'is-success', 'is-visible');
    toastEl.classList.add(`is-${t}`);
    toastEl.style.setProperty('--toast-duration', `${ms}ms`);
    toastEl.innerHTML = `
      <span class="toast-clean-mark" aria-hidden="true">${icons[t]}</span>
      <div class="toast-clean-body">
        <span class="toast-clean-title">${titles[t]}</span>
        <span class="toast-clean-desc">${escapeHtml(String(msg || ''))}</span>
      </div>
      <button type="button" class="toast-clean-close" aria-label="Dismiss notification">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
      <div class="toast-clean-progress" aria-hidden="true"></div>
    `;
    toastEl.querySelector('.toast-clean-close')?.addEventListener('click', dismissToast);

    void toastEl.offsetWidth;
    toastEl.classList.add('is-visible');
    if (!toastHovered) {
      toastTimer = setTimeout(dismissToast, ms);
    }
  }
  window.showToast = showToast;

  function showConfirm({ title, message, confirmText, cancelText, danger }) {
    const modal = document.getElementById('staffConfirmModal');
    const titleEl = document.getElementById('staffConfirmTitle');
    const messageEl = document.getElementById('staffConfirmMessage');
    const okBtn = document.getElementById('staffConfirmOk');
    const cancelBtn = document.getElementById('staffConfirmCancel');
    const closeBtn = document.getElementById('staffConfirmClose');
    if (!modal || !okBtn || !cancelBtn) {
      return Promise.resolve(false);
    }

    titleEl.textContent = title || 'Confirm';
    messageEl.textContent = message || '';
    okBtn.textContent = confirmText || 'Confirm';
    cancelBtn.textContent = cancelText || 'Go back';
    okBtn.className = danger ? 'btn-clean danger-outline' : 'btn-clean primary';
    modal.classList.add('open');

    return new Promise((resolve) => {
      const finish = (accepted) => {
        modal.classList.remove('open');
        okBtn.removeEventListener('click', onOk);
        cancelBtn.removeEventListener('click', onCancel);
        closeBtn.removeEventListener('click', onCancel);
        modal.removeEventListener('click', onBackdrop);
        document.removeEventListener('keydown', onKey);
        resolve(accepted);
      };
      const onOk = () => finish(true);
      const onCancel = () => finish(false);
      const onBackdrop = (e) => {
        if (e.target === modal) finish(false);
      };
      const onKey = (e) => {
        if (e.key === 'Escape') finish(false);
      };
      okBtn.addEventListener('click', onOk);
      cancelBtn.addEventListener('click', onCancel);
      closeBtn.addEventListener('click', onCancel);
      modal.addEventListener('click', onBackdrop);
      document.addEventListener('keydown', onKey);
    });
  }

  // ── 3. View Navigation & Header Menus ──
  function switchView(viewName) {
    currentView = viewName;
    document.querySelectorAll('.sidebar-nav-item').forEach(btn => {
      btn.classList.toggle('active', btn.dataset.view === viewName);
    });

    if (viewOverview) viewOverview.classList.toggle('active', viewName === 'overview');
    if (viewConsultation) viewConsultation.classList.toggle('active', viewName === 'consultation');
    if (viewRecords) viewRecords.classList.toggle('active', viewName === 'records');
    if (viewSchedules) viewSchedules.classList.toggle('active', viewName === 'schedules');
    if (viewSettings) viewSettings.classList.toggle('active', viewName === 'settings');

    closeStaffNotif();

    const headerSearchWrap = document.querySelector('.staff-header .header-search-wrap');
    if (headerSearchWrap) {
      headerSearchWrap.style.display = (viewName === 'overview') ? '' : 'none';
    }

    if (viewName === 'overview') renderOverview();
    if (viewName === 'consultation') renderConsultations();
    if (viewName === 'records') renderRecords();
    if (viewName === 'schedules') renderDoctorSchedules();
    if (viewName !== 'settings' && typeof stopStaffQrScanner === 'function') stopStaffQrScanner();
  }
  window.switchView = switchView;

  const staffProfileBtn = document.getElementById('staffProfileBtn');
  const staffProfileDropdown = document.getElementById('staffProfileDropdown');
  const staffProfileSettings = document.getElementById('staffProfileSettings');
  const staffNotifBtn = document.getElementById('staffNotifBtn');
  const staffNotifDropdown = document.getElementById('staffNotifDropdown');

  function closeStaffProfile() {
    staffProfileDropdown?.classList.remove('show');
    staffProfileBtn?.setAttribute('aria-expanded', 'false');
  }

  function closeStaffNotif() {
    staffNotifDropdown?.classList.remove('show');
    staffNotifBtn?.setAttribute('aria-expanded', 'false');
  }

  staffProfileBtn?.addEventListener('click', (e) => {
    e.stopPropagation();
    closeStaffNotif();
    const willOpen = !staffProfileDropdown.classList.contains('show');
    staffProfileDropdown.classList.toggle('show', willOpen);
    staffProfileBtn.setAttribute('aria-expanded', String(willOpen));
  });

  staffNotifBtn?.addEventListener('click', (e) => {
    e.stopPropagation();
    closeStaffProfile();
    const willOpen = !staffNotifDropdown.classList.contains('show');
    staffNotifDropdown.classList.toggle('show', willOpen);
    staffNotifBtn.setAttribute('aria-expanded', String(willOpen));
  });

  staffProfileSettings?.addEventListener('click', () => {
    closeStaffProfile();
    switchView('settings');
  });

  document.addEventListener('click', (e) => {
    if (!e.target.closest('#staffProfileContainer')) closeStaffProfile();
    if (!e.target.closest('#staffNotifContainer')) closeStaffNotif();
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      closeStaffProfile();
      closeStaffNotif();
    }
  });

  document.querySelectorAll('.sidebar-nav-item').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      switchView(btn.dataset.view);
    });
  });

  // ── 4. Data Fetching ──
  async function loadData() {
    try {
      const res = await fetch('/sdn/api/patients.php?_=' + Date.now());
      const data = await res.json();
      allPatients = (data && data.success && Array.isArray(data.data)) ? data.data : [];

      try {
        const docRes = await fetch('/sdn/api/schedule.php?all=1&_=' + Date.now());
        const docData = await docRes.json();
        if (docData && docData.success && Array.isArray(docData.data)) {
          allDoctors = docData.data;
          const userDept = (window.STAFF_DEPT || '').toLowerCase();
          departmentDoctors = allDoctors.filter(d => {
            const dDept = (d.department || '').toLowerCase();
            return !userDept || dDept.includes(userDept) || userDept.includes(dDept);
          });
        }
      } catch (e) {
        allDoctors = [];
        departmentDoctors = [];
      }

      updateKPIsAndPillCounts();
      updateNotifications();
      renderOverview();
      if (currentView === 'consultation') renderConsultations();
      if (currentView === 'records') renderRecords();
      if (currentView === 'schedules') renderDoctorSchedules();
    } catch (err) {
      showToast('Unable to fetch clinic records.');
    }
  }
  window.loadData = loadData;

  function inApprovalStatus(st) {
    const s = (st || '').toLowerCase().trim();
    return s === 'for_approval' || s === 'pending' || s === 'for approval';
  }

  function isConfirmedStatus(st) {
    const s = (st || '').toLowerCase().trim();
    return s === 'approved' || s === 'confirmed';
  }

  function isInClinicStatus(st) {
    const s = (st || '').toLowerCase().trim();
    return s === 'arrived' || s === 'ongoing';
  }

  function isCancelledStatus(st) {
    const s = (st || '').toLowerCase().trim();
    return s === 'rejected' || s === 'cancelled' || s === 'no-show';
  }

  function isInquiryRecord(p) {
    return Boolean(p && (p.inquiry_only == 1 || p.inquiry_only === true || p.inquiry_only === '1' || p.consultation_nature === 'General Inquiry'));
  }

  function getManilaTodayStr() {
    try {
      return new Intl.DateTimeFormat('en-CA', { timeZone: 'Asia/Manila' }).format(new Date());
    } catch (e) {
      return new Date().toISOString().split('T')[0];
    }
  }

  // ── 5. KPI, Notifications & Overview Renderer ──
  function updateKPIsAndPillCounts() {
    const todayStr = getManilaTodayStr();

    const todayVisits = allPatients.filter(p => {
      const st = (p.status || '').toLowerCase();
      if (st === 'registered' || isCancelledStatus(st)) return false;
      const apptDate = p.appointment_date ? String(p.appointment_date).split(' ')[0] : '';
      if (apptDate) return apptDate === todayStr;
      return Boolean(p.unscheduled_walkin && p.created_at && String(p.created_at).startsWith(todayStr));
    }).length;
    const forApproval = allPatients.filter(p => inApprovalStatus(p.status)).length;
    const inquiries   = allPatients.filter(p => isInquiryRecord(p)).length;
    const confirmed   = allPatients.filter(p => isConfirmedStatus(p.status)).length;
    const inClinic    = allPatients.filter(p => isInClinicStatus(p.status)).length;
    const completed   = allPatients.filter(p => (p.status || '').toLowerCase() === 'completed').length;
    const completedToday = allPatients.filter(p => {
      if ((p.status || '').toLowerCase() !== 'completed') return false;
      const doneDate = (p.medical_history_date || p.appointment_date || p.updated_at || '').toString().split(' ')[0];
      return doneDate === todayStr;
    }).length;
    const todayQueue = allPatients.filter(p => {
      if (isInClinicStatus(p.status)) return true;
      if (!isConfirmedStatus(p.status)) return false;
      const apptDate = p.appointment_date ? String(p.appointment_date).split(' ')[0] : '';
      return apptDate === todayStr;
    }).length;
    const transferred = allPatients.filter(p => (p.status || '').toLowerCase() === 'transferred').length;
    const cancelled   = allPatients.filter(p => isCancelledStatus(p.status)).length;

    const setElText = (id, val) => {
      const el = document.getElementById(id);
      if (el) el.textContent = val;
    };

    setElText('kpiTotal', todayVisits);
    setElText('kpiPending', forApproval);
    setElText('kpiQueue', todayQueue);
    setElText('kpiCompleted', completedToday);

    setElText('kpiTotalSub', forApproval > 0 ? `${forApproval} awaiting confirmation` : 'Scheduled for today');
    setElText('kpiCompletedSub', 'Diagnosed and recorded');
    setElText('kpiQueueSub', 'Across all hospital wings');

    // Filter pill labels
    setElText('pillCountAll', allPatients.length);
    setElText('pillCountPending', forApproval);
    setElText('pillCountInquiry', inquiries);
    setElText('pillCountApproved', confirmed);
    setElText('pillCountInClinic', inClinic);
    setElText('pillCountCompleted', completed);
    setElText('pillCountTransferred', transferred);
    setElText('pillCountCancelled', cancelled);
  }

  function updateNotifications() {
    const pending = allPatients.filter(p => inApprovalStatus(p.status));
    const dotEl = document.getElementById('staffNotifDot');
    const badgeEl = document.getElementById('navQueueBadge');
    const listEl = document.getElementById('staffNotifList');

    if (dotEl) dotEl.style.display = pending.length > 0 ? 'block' : 'none';
    if (badgeEl) {
      badgeEl.style.display = pending.length > 0 ? 'inline-flex' : 'none';
      badgeEl.textContent = pending.length;
    }

    if (!listEl) return;
    if (!pending.length) {
      listEl.innerHTML = '<div class="staff-notif-empty">All appointment requests are caught up.</div>';
      return;
    }

    listEl.innerHTML = pending.slice(0, 6).map(p => {
      const timeText = isInquiryRecord(p)
        ? 'Inquiry Only · Online Portal'
        : formatRelativeRequestTime(p.appointment_date || p.created_at, p.scheduled_time, p.preferred_time_slot);
      const dept = formatDeptLabel(p.department);
      return `
        <button type="button" class="staff-notif-item" onclick="openPatientDetailsModal('${escapeHtml(p.patient_id)}'); document.getElementById('staffNotifDropdown')?.classList.remove('show');">
          <div class="staff-notif-item-title">${escapeHtml(p.full_name || 'Patient')}</div>
          <div class="staff-notif-item-meta">${escapeHtml(dept)} &middot; ${escapeHtml(timeText)}</div>
        </button>
      `;
    }).join('');
  }

  function formatDeptLabel(raw) {
    const s = String(raw || '').replace(/\bOPU\s+/gi, '').replace(/\s*\(OPU\)/gi, '').trim();
    if (!s || s.toLowerCase().includes('triage') || s.toLowerCase().includes('pending')) return 'General Medical';
    const low = s.toLowerCase();
    if (low === 'general' || low === 'general medical' || low === 'general medicine') return 'General Medical';
    if (low === 'neurology' || low === 'neuro') return 'Neurology';
    if (low === 'psychiatry' || low.includes('psych')) return 'Psychiatry';
    return s.replace(/\w\S*/g, w => w.charAt(0).toUpperCase() + w.slice(1).toLowerCase());
  }

  function formatRelativeRequestTime(dateStr, timeStr, preferredSlot) {
    const todayStr = new Date().toISOString().split('T')[0];
    const cleanDate = dateStr ? String(dateStr).split(' ')[0] : '';
    const timeLabel = preferredSlot || formatTime12(timeStr) || '8:00 AM';
    if (!cleanDate || cleanDate === todayStr) {
      return `Today, ${timeLabel}`;
    }
    const parts = formatDateBadge(cleanDate);
    return `${parts.monthDay}, ${timeLabel}`;
  }

  function renderOverviewStatusPill(st) {
    const s = (st || 'pending').toLowerCase();
    if (inApprovalStatus(s)) return '<span class="ov-status-pill is-pending">Pending</span>';
    if (isConfirmedStatus(s)) return '<span class="ov-status-pill is-confirmed">Confirmed</span>';
    if (s === 'arrived') return '<span class="ov-status-pill is-clinic">Checked In</span>';
    if (s === 'ongoing') return '<span class="ov-status-pill is-clinic">In Consultation</span>';
    return renderStatusPill(s);
  }

  function renderOverview() {
    const tbody = document.getElementById('overviewRequestsTableBody');
    if (!tbody) return;

    const rows = allPatients.filter(p => inApprovalStatus(p.status)).slice(0, 8);

    if (!rows.length) {
      tbody.innerHTML = '<tr><td colspan="4" class="modern-table-empty">No pending appointment requests awaiting confirmation right now.</td></tr>';
      return;
    }

    tbody.innerHTML = rows.map(p => {
      const s = (p.status || 'pending').toLowerCase();
      const isPending = inApprovalStatus(s);
      const isInquiry = isInquiryRecord(p);
      const timeText = isInquiry
        ? 'Inquiry Only · Online Portal'
        : formatRelativeRequestTime(p.appointment_date || p.created_at, p.scheduled_time, p.preferred_time_slot);
      const pid = escapeHtml(p.patient_id);
      const inquiryBadge = isInquiry
        ? '<span class="patient-type-badge" style="margin-left:6px;background:#fef3c7;color:#92400e;border:1px solid #fde68a;">Inquiry</span>'
        : '';

      const actionBtn = isPending
        ? `<button type="button" class="ov-action-link" onclick="openPatientDetailsModal('${pid}')">Review</button>`
        : `<button type="button" class="ov-action-link" onclick="openPatientDetailsModal('${pid}')">Manage</button>`;

      return `
        <tr>
          <td class="cell-primary">
            <div>${escapeHtml(p.full_name || 'Patient')}${inquiryBadge}</div>
            <div class="ov-row-meta">${escapeHtml(p.reference_no || p.patient_id || '')}</div>
          </td>
          <td>${escapeHtml(timeText)}</td>
          <td>${renderOverviewStatusPill(s)}</td>
          <td class="col-actions">${actionBtn}</td>
        </tr>
      `;
    }).join('');
  }

  // ── 5B. Doctor Schedules (Organized per Department) ──
  function normalizeSchedDept(raw) {
    const s = String(raw || '').toLowerCase();
    if (s.includes('psych') || s.includes('opu')) return 'psychiatry';
    if (s.includes('neuro')) return 'neurology';
    if (s.includes('general')) return 'general';
    return 'other';
  }

  function isDocAvailable(status) {
    const s = String(status || '').toLowerCase().trim();
    return s === 'in' || s === 'in_clinic' || s === 'available' || s === 'on duty';
  }

  function normalizeDoctorName(name) {
    return String(name || '')
      .toLowerCase()
      .replace(/^dr\.?\s*/i, '')
      .replace(/,?\s*md$/i, '')
      .replace(/\s+/g, ' ')
      .trim();
  }

  function getDoctorTodayLoad(docName) {
    const todayStr = new Date().toISOString().split('T')[0];
    const cleanTarget = normalizeDoctorName(docName);
    if (!cleanTarget) return 0;
    return allPatients.filter(p => {
      const st = (p.status || '').toLowerCase();
      if (isCancelledStatus(st) || st === 'transferred') return false;
      const pDate = (p.appointment_date || p.created_at || '').split(' ')[0];
      if (pDate !== todayStr) return false;
      const assigned = normalizeDoctorName(p.assigned_doctor);
      return assigned && assigned === cleanTarget;
    }).length;
  }

  window.setSchedDeptCategory = function (dept) {
    schedDept = dept || 'all';
    document.querySelectorAll('#view-schedules .dept-category-tabs .dept-tab').forEach(btn => {
      btn.classList.toggle('active', btn.dataset.dept === schedDept);
    });
    renderDoctorSchedules();
  };

  window.resetScheduleFilters = function () {
    schedDept = 'all';
    schedSearch = '';
    schedSpec = 'all';
    schedStatus = 'all';
    schedSort = 'dept_name';

    const sInput = document.getElementById('schedSearchInput');
    if (sInput) sInput.value = '';
    const spSelect = document.getElementById('schedSpecFilter');
    if (spSelect) spSelect.value = 'all';
    const stSelect = document.getElementById('schedStatusFilter');
    if (stSelect) stSelect.value = 'all';
    const soSelect = document.getElementById('schedSortSelect');
    if (soSelect) soSelect.value = 'dept_name';

    document.querySelectorAll('#view-schedules .dept-category-tabs .dept-tab').forEach(btn => {
      btn.classList.toggle('active', btn.dataset.dept === 'all');
    });
    renderDoctorSchedules();
  };

  function getStaffDeptKey() {
    return normalizeSchedDept(window.STAFF_DEPT || 'General Medical');
  }

  function getStaffDeptDoctors() {
    const deptKey = getStaffDeptKey();
    const matched = (allDoctors || []).filter(d => normalizeSchedDept(d.department) === deptKey);
    return matched.length ? matched : (departmentDoctors || []);
  }

  function populateScheduleSpecializations() {
    const selectEl = document.getElementById('schedSpecFilter');
    if (!selectEl) return;
    const specs = [...new Set(
      getStaffDeptDoctors()
        .map(d => String(d.specialization || '').trim())
        .filter(Boolean)
    )].sort((a, b) => a.localeCompare(b));

    const prev = schedSpec;
    selectEl.innerHTML = '<option value="all">All Specializations</option>' +
      specs.map(sp => `<option value="${escapeHtml(sp)}">${escapeHtml(sp)}</option>`).join('');
    if (specs.includes(prev)) {
      selectEl.value = prev;
    } else {
      schedSpec = 'all';
      selectEl.value = 'all';
    }
  }

  let staffCalYear = new Date().getFullYear();
  let staffCalMonth = new Date().getMonth();
  let staffCalActiveYmd = null;

  function toLocalYmd(dateObj) {
    const y = dateObj.getFullYear();
    const m = String(dateObj.getMonth() + 1).padStart(2, '0');
    const d = String(dateObj.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
  }

  function getPatientsForDateInDept(ymd) {
    const deptKey = getStaffDeptKey();
    return (allPatients || []).filter(p => {
      if (!p.appointment_date || String(p.appointment_date).slice(0, 10) !== ymd) return false;
      const st = String(p.status || '').toLowerCase();
      if (st === 'cancelled' || st === 'rejected' || st === 'no-show') return false;
      return normalizeSchedDept(p.department) === deptKey;
    });
  }

  function getDoctorLoadForDate(docName, ymd) {
    const normDoc = normalizeDoctorName(docName);
    if (!normDoc) return 0;
    return getPatientsForDateInDept(ymd).filter(p => normalizeDoctorName(p.assigned_doctor) === normDoc).length;
  }

  // Canonical clinic schedule templates synced with pages/schedules.php
  const CLINIC_SCHEDULE_TEMPLATES = {
    general: [
      {
        id: 'gen-consult',
        label: 'General Medical Consultation',
        shortLabel: 'General Medical',
        days: [1, 2, 3, 4, 5, 6],
        hours: '8:00 AM – 10:00 AM',
        matchDoc: (spec) => !spec.includes('chronic') && !spec.includes('prevent') && !spec.includes('physical')
      },
      {
        id: 'gen-preventive',
        label: 'Physical Examination & Preventive Healthcare',
        shortLabel: 'Physical Exam & Preventive',
        days: [1, 2, 3, 4, 5],
        hours: '10:00 AM – 12:00 PM',
        matchDoc: (spec) => spec.includes('prevent') || spec.includes('physical') || spec.includes('routine')
      },
      {
        id: 'gen-chronic',
        label: 'Chronic Disease Management & Lab Requests',
        shortLabel: 'Chronic Disease Mgmt',
        days: [1, 3, 5],
        hours: '1:00 PM – 4:00 PM',
        matchDoc: (spec) => spec.includes('chronic')
      }
    ],
    neurology: [
      {
        id: 'neuro-consult',
        label: 'Neurology Consultation & Headache Management',
        shortLabel: 'Neurology & Headache',
        days: [1, 2, 4, 5],
        hours: '8:30 AM – 11:30 AM',
        matchDoc: (spec) => !spec.includes('stroke') && !spec.includes('epilepsy') && !spec.includes('diagnostic')
      },
      {
        id: 'neuro-stroke',
        label: 'Stroke, Epilepsy & Neurological Diagnostic Testing',
        shortLabel: 'Stroke & Diagnostics',
        days: [2, 3, 4, 6],
        hours: '1:30 PM – 4:30 PM',
        matchDoc: (spec) => spec.includes('stroke') || spec.includes('epilepsy') || spec.includes('diagnostic') || spec.includes('dementia')
      }
    ],
    psychiatry: [
      {
        id: 'psych-adult',
        label: 'Psychotherapy & Psychological Assessment',
        shortLabel: 'Psychotherapy & Assessment',
        days: [1, 2, 3, 4, 5],
        hours: '8:00 AM – 11:30 AM',
        matchDoc: (spec) => !spec.includes('child') && !spec.includes('adolescent') && !spec.includes('substance') && !spec.includes('rehab')
      },
      {
        id: 'psych-child',
        label: 'Child & Adolescent Psychology / Rehabilitation',
        shortLabel: 'Child & Rehab Psych',
        days: [1, 2, 3, 4, 5, 6],
        hours: '1:00 PM – 5:00 PM',
        matchDoc: (spec) => spec.includes('child') || spec.includes('adolescent') || spec.includes('substance') || spec.includes('rehab')
      }
    ]
  };

  function getClinicBlocksForDate(filteredDocs, dateObj, todayDate, todayYmd, dayPatients) {
    const dow = dateObj.getDay();
    if (dow === 0) return []; // Sundays closed
    const ymd = toLocalYmd(dateObj);

    const maxHorizonDate = new Date(todayDate);
    maxHorizonDate.setDate(maxHorizonDate.getDate() + 60);
    const withinHorizon = dateObj <= maxHorizonDate;
    const hasBookedPatients = (dayPatients || []).length > 0;
    const explicitFutureDocs = filteredDocs.filter(d => d.schedule_date && String(d.schedule_date).slice(0, 10) === ymd);

    if (!withinHorizon && !hasBookedPatients && !explicitFutureDocs.length) {
      return [];
    }

    const deptKey = getStaffDeptKey();
    const templates = CLINIC_SCHEDULE_TEMPLATES[deptKey] || CLINIC_SCHEDULE_TEMPLATES.general;

    return templates
      .filter(tpl => tpl.days.includes(dow))
      .map(tpl => {
        const matched = filteredDocs.filter(d => {
          const sDate = d.schedule_date ? String(d.schedule_date).slice(0, 10) : '';
          if (sDate && sDate > todayYmd && sDate !== ymd) return false;
          const spec = String(d.specialization || '').toLowerCase();
          return tpl.matchDoc(spec);
        });
        return {
          spec: tpl.label,
          shortLabel: tpl.shortLabel,
          clinicHours: tpl.hours,
          docs: matched
        };
      })
      .filter(block => block.docs.length > 0);
  }

  window.changeStaffCalMonth = function (delta) {
    staffCalMonth += delta;
    if (staffCalMonth < 0) {
      staffCalMonth = 11;
      staffCalYear--;
    } else if (staffCalMonth > 11) {
      staffCalMonth = 0;
      staffCalYear++;
    }
    renderDoctorSchedules();
  };

  window.jumpStaffCalToday = function () {
    const now = new Date();
    staffCalYear = now.getFullYear();
    staffCalMonth = now.getMonth();
    renderDoctorSchedules();
  };

  window.closeStaffCalDayModal = function () {
    const m = document.getElementById('staffCalDayModal');
    if (m) m.classList.remove('active');
    staffCalActiveYmd = null;
  };

  function openStaffCalDayModal(dateObj, dayDocs, dayPatients) {
    const modal = document.getElementById('staffCalDayModal');
    const titleEl = document.getElementById('staffCalModalTitle');
    const subEl = document.getElementById('staffCalModalSub');
    const bodyEl = document.getElementById('staffCalModalBody');
    if (!modal || !bodyEl) return;

    const ymd = toLocalYmd(dateObj);
    staffCalActiveYmd = ymd;
    const formatted = dateObj.toLocaleDateString('en-US', {
      weekday: 'long',
      month: 'long',
      day: 'numeric',
      year: 'numeric'
    });
    const availCount = dayDocs.filter(d => d._isIn).length;
    const deptTitle = window.STAFF_DEPT || 'General Medical';
    if (titleEl) titleEl.textContent = formatted;
    if (subEl) {
      subEl.textContent = `${deptTitle} · ${dayDocs.length} physician${dayDocs.length === 1 ? '' : 's'} (${availCount} available) · ${dayPatients.length} booked`;
    }

    const rowsHtml = dayDocs.map(d => {
      const docName = d.name && d.name.startsWith('Dr.') ? d.name : `Dr. ${d.name || 'Physician'}`;
      const spec = d.specialization || 'General Practice';
      const startFmt = d.schedule_start_fmt || formatTime12(d.schedule_start);
      const endFmt = d.schedule_end_fmt || formatTime12(d.schedule_end);
      const hours = (startFmt && endFmt) ? `${startFmt} – ${endFmt}` : '8:00 AM – 5:00 PM';
      const dayLoad = getDoctorLoadForDate(d.name, ymd);
      const pct = Math.min(100, Math.round((dayLoad / d._totalSlots) * 100));

      const cleanDocName = String(docName || '').replace(/^dr\.?\s*/i, '').replace(/,?\s*md$/i, '').trim();
      const docParts = cleanDocName.split(/\s+/).filter(Boolean);
      const docInit = docParts.length > 1
        ? (docParts[0][0] + docParts[docParts.length - 1][0]).toUpperCase()
        : (cleanDocName.slice(0, 2).toUpperCase() || 'DR');
      const avatarThumb = d.profile_image
        ? `<span class="entity-avatar-sm"><img src="${escapeHtml(d.profile_image)}" alt="${escapeHtml(docName)}" onerror="this.style.display='none';this.nextElementSibling.style.display='inline';"><span style="display:none;">${escapeHtml(docInit)}</span></span>`
        : `<span class="entity-avatar-sm">${escapeHtml(docInit)}</span>`;

      return `
        <tr>
          <td class="cell-primary">
            <div class="table-person-cell">
              ${avatarThumb}
              <span>${escapeHtml(docName)}</span>
            </div>
          </td>
          <td class="cell-secondary">${escapeHtml(spec)}</td>
          <td class="cell-tabular">${escapeHtml(hours)}</td>
          <td class="cell-tabular">
            <div class="sched-cap-wrap">
              <span class="sched-cap-text"><strong>${dayLoad}</strong> <span class="capacity-total">/ ${d._totalSlots}</span></span>
              <div class="sched-cap-bar"><div class="sched-cap-fill" style="width:${pct}%"></div></div>
            </div>
          </td>
          <td>
            <span class="table-status-indicator ${d._isIn ? 'is-active' : 'is-inactive'}">
              <span class="status-dot"></span>${d._isIn ? 'Available' : 'Out'}
            </span>
          </td>
          <td class="col-actions">
            <select class="select-filter doc-status-select ${d._isIn ? 'status-in' : 'status-out'}" onchange="updateDocStatus(${Number(d.id)}, this.value, this)" aria-label="Availability for ${escapeHtml(docName)}">
              <option value="in" ${d._isIn ? 'selected' : ''}>IN</option>
              <option value="out" ${!d._isIn ? 'selected' : ''}>OUT</option>
            </select>
          </td>
        </tr>
      `;
    }).join('');

    const doctorsSection = dayDocs.length ? `
      <div class="dept-schedule-card" style="margin-bottom:14px;">
        <div class="table-wrap">
          <table class="clean-table">
            <thead>
              <tr>
                <th>Physician</th>
                <th>Specialization</th>
                <th>Schedule Hours</th>
                <th>Booked / Cap</th>
                <th>Status</th>
                <th>Availability</th>
              </tr>
            </thead>
            <tbody>${rowsHtml}</tbody>
          </table>
        </div>
      </div>
    ` : '';

    const patientsSection = dayPatients.length ? `
      <div class="dept-schedule-card">
        <div style="padding:10px 14px;border-bottom:1px solid #e2e8f0;font-size:0.82rem;font-weight:700;color:#082f04;">
          Booked Patients (${dayPatients.length})
        </div>
        <div class="table-wrap">
          <table class="clean-table">
            <thead>
              <tr>
                <th>Patient</th>
                <th>Assigned Physician</th>
                <th>Time</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              ${dayPatients.map(p => `
                <tr>
                  <td class="cell-primary">${escapeHtml(p.full_name || 'Patient')}</td>
                  <td class="cell-secondary">${escapeHtml(p.assigned_doctor || 'Unassigned')}</td>
                  <td class="cell-tabular">${escapeHtml(p.scheduled_time ? formatTime12(p.scheduled_time) : '8:00 AM')}</td>
                  <td>${renderOverviewStatusPill(p.status || 'pending')}</td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      </div>
    ` : '';

    bodyEl.innerHTML = (doctorsSection + patientsSection) || '<div style="padding:24px;text-align:center;color:#64748b;">No doctor schedules for this date.</div>';
    modal.classList.add('active');
  }

  let staffCalPopoverEl = null;
  let staffCalPopoverHideTimer = null;

  function cancelHideStaffCalPopover() {
    if (staffCalPopoverHideTimer) {
      clearTimeout(staffCalPopoverHideTimer);
      staffCalPopoverHideTimer = null;
    }
  }

  function scheduleHideStaffCalPopover() {
    cancelHideStaffCalPopover();
    staffCalPopoverHideTimer = setTimeout(() => {
      hideStaffCalPopover();
    }, 180);
  }

  function getStaffCalPopover() {
    if (!staffCalPopoverEl) {
      staffCalPopoverEl = document.createElement('div');
      staffCalPopoverEl.id = 'staffCalHoverPopover';
      staffCalPopoverEl.className = 'staff-cal-popover';
      staffCalPopoverEl.setAttribute('role', 'tooltip');
      document.body.appendChild(staffCalPopoverEl);
      staffCalPopoverEl.addEventListener('mouseenter', cancelHideStaffCalPopover);
      staffCalPopoverEl.addEventListener('mouseleave', scheduleHideStaffCalPopover);
      window.addEventListener('scroll', (e) => {
        if (staffCalPopoverEl && staffCalPopoverEl.contains(e.target)) return;
        hideStaffCalPopover();
      }, true);
    }
    return staffCalPopoverEl;
  }

  function hideStaffCalPopover() {
    cancelHideStaffCalPopover();
    if (staffCalPopoverEl) {
      staffCalPopoverEl.classList.remove('is-visible');
    }
  }

  function showStaffCalPopover(anchorEl, title, dateStr, docs, showSpecLine = false) {
    const pop = getStaffCalPopover();
    const inCount = docs.filter(d => d._isIn).length;
    const rowsHtml = docs.map(d => {
      const docName = d.name && d.name.startsWith('Dr.') ? d.name : `Dr. ${d.name || 'Physician'}`;
      const startFmt = d.schedule_start_fmt || formatTime12(d.schedule_start);
      const endFmt = d.schedule_end_fmt || formatTime12(d.schedule_end);
      const hours = (startFmt && endFmt) ? `${startFmt} – ${endFmt}` : '8:00 AM – 5:00 PM';
      const cleanName = String(docName).replace(/^dr\.?\s*/i, '').replace(/,?\s*md$/i, '').trim();
      const parts = cleanName.split(/\s+/).filter(Boolean);
      const initials = parts.length > 1 ? (parts[0][0] + parts[parts.length - 1][0]).toUpperCase() : (cleanName.slice(0, 2).toUpperCase() || 'DR');
      const avatarHtml = d.profile_image
        ? `<img src="${escapeHtml(d.profile_image)}" alt="" onerror="this.remove();">`
        : escapeHtml(initials);

      return `
        <div class="staff-cal-pop-row">
          <div class="staff-cal-pop-doc">
            <span class="staff-cal-pop-avatar">${avatarHtml}</span>
            <div class="staff-cal-pop-info">
              <div class="staff-cal-pop-name">${escapeHtml(docName)}</div>
              ${showSpecLine ? `<div class="staff-cal-pop-spec-tag">${escapeHtml(d.specialization || 'General Practice')}</div>` : ''}
              <div class="staff-cal-pop-time">${escapeHtml(hours)}</div>
            </div>
          </div>
          <span class="staff-cal-pop-status ${d._isIn ? 'is-in' : 'is-out'}">${d._isIn ? 'IN' : 'OUT'}</span>
        </div>
      `;
    }).join('');

    pop.innerHTML = `
      <div class="staff-cal-pop-head">
        <div class="staff-cal-pop-title">${escapeHtml(title)}</div>
        <div class="staff-cal-pop-sub">${escapeHtml(dateStr)} &middot; ${inCount} of ${docs.length} available</div>
      </div>
      <div class="staff-cal-pop-list">${rowsHtml}</div>
      <div class="staff-cal-pop-foot">Click to view full schedule &amp; toggle IN/OUT</div>
    `;

    // Position smartly within viewport bounds
    const rect = anchorEl.getBoundingClientRect();
    pop.style.left = '-9999px';
    pop.style.top = '-9999px';
    pop.classList.add('is-visible');

    const popRect = pop.getBoundingClientRect();
    const pad = 12;
    let left = rect.right + 10;
    if (left + popRect.width > window.innerWidth - pad) {
      left = rect.left - popRect.width - 10;
    }
    if (left < pad) {
      left = Math.max(pad, Math.min(rect.left, window.innerWidth - popRect.width - pad));
    }

    let top = rect.top - 8;
    if (top + popRect.height > window.innerHeight - pad) {
      top = Math.max(pad, window.innerHeight - popRect.height - pad);
    }
    if (top < pad) top = pad;

    pop.style.left = `${Math.round(left)}px`;
    pop.style.top = `${Math.round(top)}px`;
  }

  function groupDoctorsBySpecialization(docs) {
    const map = new Map();
    docs.forEach(d => {
      const spec = String(d.specialization || 'General Practice').trim() || 'General Practice';
      if (!map.has(spec)) map.set(spec, []);
      map.get(spec).push(d);
    });
    return Array.from(map.entries()).map(([spec, list]) => ({ spec, docs: list }));
  }

  function renderDoctorSchedules() {
    const container = document.getElementById('doctorSchedulesContainer');
    if (!container) return;
    hideStaffCalPopover();

    const docs = getStaffDeptDoctors();
    const setText = (id, val) => {
      const el = document.getElementById(id);
      if (el) el.textContent = val;
    };

    const availTotal = docs.filter(d => isDocAvailable(d.status)).length;
    setText('schedSummaryAvailable', `${availTotal} Available`);
    setText('schedSummaryOut', `${docs.length - availTotal} Out`);

    populateScheduleSpecializations();

    let filtered = docs.map(d => {
      const isIn = isDocAvailable(d.status);
      const totalSlots = Math.min(100, Math.max(1, parseInt(d.total_slots, 10) || 20));
      return Object.assign({}, d, {
        _isIn: isIn,
        _totalSlots: totalSlots
      });
    });

    if (schedSearch) {
      const q = schedSearch.toLowerCase();
      filtered = filtered.filter(d =>
        (d.name || '').toLowerCase().includes(q) ||
        (d.specialization || '').toLowerCase().includes(q)
      );
    }

    if (schedSpec !== 'all') {
      filtered = filtered.filter(d => String(d.specialization || '').trim() === schedSpec);
    }

    if (schedStatus !== 'all') {
      filtered = filtered.filter(d => (schedStatus === 'in' ? d._isIn : !d._isIn));
    }

    filtered.sort((a, b) => (a.specialization || '').localeCompare(b.specialization || '') || (a.name || '').localeCompare(b.name || ''));

    const monthNames = [
      'January', 'February', 'March', 'April', 'May', 'June',
      'July', 'August', 'September', 'October', 'November', 'December'
    ];
    const todayDate = new Date();
    todayDate.setHours(0, 0, 0, 0);
    const todayYmd = toLocalYmd(todayDate);

    const firstDay = new Date(staffCalYear, staffCalMonth, 1).getDay();
    const daysInMonth = new Date(staffCalYear, staffCalMonth + 1, 0).getDate();
    const daysInPrev = new Date(staffCalYear, staffCalMonth, 0).getDate();

    let cellsHtml = '';
    const dayLookupMap = new Map();
    const specLookupMap = new Map();

    // Leading out-of-month gray cells
    for (let i = firstDay - 1; i >= 0; i--) {
      cellsHtml += `<div class="staff-cal-cell is-outside" aria-hidden="true"><div class="staff-cal-cell-head"><span class="staff-cal-day-num">${daysInPrev - i}</span></div></div>`;
    }

    // Current month day cells
    for (let day = 1; day <= daysInMonth; day++) {
      const cellDate = new Date(staffCalYear, staffCalMonth, day);
      cellDate.setHours(0, 0, 0, 0);
      const ymd = toLocalYmd(cellDate);
      const isPast = cellDate < todayDate;
      const isToday = ymd === todayYmd;
      const isSunday = cellDate.getDay() === 0;
      const dayPatients = getPatientsForDateInDept(ymd);
      const clinicBlocks = isPast ? [] : getClinicBlocksForDate(filtered, cellDate, todayDate, todayYmd, dayPatients);
      const dayDocsMap = new Map();
      clinicBlocks.forEach(b => b.docs.forEach(d => dayDocsMap.set(d.id, d)));
      const dayDocs = Array.from(dayDocsMap.values());
      const isOpen = !isPast && !isSunday && clinicBlocks.length > 0;

      const headHtml = `
        <div class="staff-cal-cell-head">
          <span class="staff-cal-day-num">${day}</span>
          ${isToday ? '<span class="staff-cal-today-tag">Today</span>' : ''}
        </div>
      `;

      if (!isOpen) {
        const hasHistory = isPast && dayPatients.length > 0;
        if (hasHistory) {
          dayLookupMap.set(ymd, { dateObj: cellDate, docs: [], patients: dayPatients });
        }
        const closedContent = hasHistory
          ? `<div class="staff-cal-metrics"><span class="staff-cal-metric is-history">${dayPatients.length} visit${dayPatients.length === 1 ? '' : 's'}</span></div>`
          : (!isPast && isSunday ? '<div class="staff-cal-closed-note">Closed</div>' : (!isPast ? '<div class="staff-cal-closed-note">No schedules</div>' : ''));
        cellsHtml += `<div class="staff-cal-cell is-closed${hasHistory ? ' has-history' : ''}"${hasHistory ? ` data-cal-ymd="${ymd}" tabindex="0" role="button"` : ''}>${headHtml}${closedContent}</div>`;
        continue;
      }

      dayLookupMap.set(ymd, { dateObj: cellDate, docs: dayDocs, patients: dayPatients });

      const shortDateStr = cellDate.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
      const chipsHtml = clinicBlocks.map((block, idx) => {
        const key = `${ymd}|${idx}`;
        specLookupMap.set(key, {
          title: block.spec,
          dateStr: `${shortDateStr} · ${block.clinicHours}`,
          docs: block.docs,
          showSpecLine: true
        });
        const hasAvailable = block.docs.some(d => d._isIn);
        return `
          <button type="button" class="staff-cal-spec-chip${hasAvailable ? '' : ' is-out'}" data-spec-key="${escapeHtml(key)}" data-cal-ymd="${ymd}">
            <span class="staff-cal-spec-left">
              <span class="staff-cal-doc-dot" aria-hidden="true"></span>
              <span class="staff-cal-spec-name">${escapeHtml(block.shortLabel)}</span>
            </span>
          </button>
        `;
      }).join('');

      cellsHtml += `
        <div class="staff-cal-cell is-open${isToday ? ' is-today' : ''}" data-cal-ymd="${ymd}" tabindex="0" role="button" aria-label="${monthNames[staffCalMonth]} ${day}, ${staffCalYear} — ${clinicBlocks.length} clinic schedules">
          ${headHtml}
          ${dayPatients.length > 0 ? `<div class="staff-cal-metrics"><span class="staff-cal-metric is-booked">Booked: ${dayPatients.length}</span></div>` : ''}
          <div class="staff-cal-pills">
            ${chipsHtml}
          </div>
        </div>
      `;
    }

    // Trailing out-of-month gray cells
    const totalCells = firstDay + daysInMonth;
    const trailing = (7 - (totalCells % 7)) % 7;
    for (let d = 1; d <= trailing; d++) {
      cellsHtml += `<div class="staff-cal-cell is-outside" aria-hidden="true"><div class="staff-cal-cell-head"><span class="staff-cal-day-num">${d}</span></div></div>`;
    }

    container.innerHTML = `
      <div class="staff-cal-board">
        <div class="staff-cal-toolbar">
          <div class="staff-cal-nav-left">
            <button type="button" class="staff-cal-nav-link" id="staffCalPrevBtn">&larr; Previous Month</button>
          </div>
          <div class="staff-cal-month-title">${monthNames[staffCalMonth]} ${staffCalYear}</div>
          <div class="staff-cal-nav-right">
            <button type="button" class="staff-cal-nav-link is-subtle" id="staffCalTodayBtn">Today</button>
            <button type="button" class="staff-cal-nav-link" id="staffCalNextBtn">Next Month &rarr;</button>
          </div>
        </div>
        <div class="staff-cal-legend">
          <div class="staff-cal-legend-items">
            <span class="staff-cal-legend-item"><span class="staff-cal-swatch is-general"></span>${escapeHtml(window.STAFF_DEPT || 'General Medical')} Specializations</span>
            <span class="staff-cal-legend-item"><span class="staff-cal-swatch is-psych"></span>Booked Patients</span>
            <span class="staff-cal-legend-item"><span class="staff-cal-swatch is-closed"></span>Past / Closed</span>
          </div>
          <span>Hover a specialization to see assigned doctors &amp; hours</span>
        </div>
        <div class="staff-cal-scroll">
          <div class="staff-cal-frame">
            <div class="staff-cal-weekdays">
              <div class="staff-cal-weekday">Sun</div>
              <div class="staff-cal-weekday">Mon</div>
              <div class="staff-cal-weekday">Tue</div>
              <div class="staff-cal-weekday">Wed</div>
              <div class="staff-cal-weekday">Thu</div>
              <div class="staff-cal-weekday">Fri</div>
              <div class="staff-cal-weekday">Sat</div>
            </div>
            <div class="staff-cal-grid">${cellsHtml}</div>
          </div>
        </div>
      </div>
    `;

    document.getElementById('staffCalPrevBtn')?.addEventListener('click', () => window.changeStaffCalMonth(-1));
    document.getElementById('staffCalNextBtn')?.addEventListener('click', () => window.changeStaffCalMonth(1));
    document.getElementById('staffCalTodayBtn')?.addEventListener('click', () => window.jumpStaffCalToday());

    // Hover & keyboard focus popover on specialization chips and "+N more"
    container.querySelectorAll('[data-spec-key]').forEach(chip => {
      const key = chip.getAttribute('data-spec-key');
      const show = () => {
        cancelHideStaffCalPopover();
        const info = specLookupMap.get(key);
        if (info) showStaffCalPopover(chip, info.title, info.dateStr, info.docs, info.showSpecLine);
      };
      chip.addEventListener('mouseenter', show);
      chip.addEventListener('focus', show);
      chip.addEventListener('mouseleave', scheduleHideStaffCalPopover);
      chip.addEventListener('blur', scheduleHideStaffCalPopover);
    });

    container.querySelectorAll('.staff-cal-cell[data-cal-ymd]').forEach(cell => {
      const ymd = cell.getAttribute('data-cal-ymd');
      const openHandler = () => {
        hideStaffCalPopover();
        const entry = dayLookupMap.get(ymd);
        if (entry) openStaffCalDayModal(entry.dateObj, entry.docs, entry.patients);
      };
      cell.addEventListener('click', openHandler);
      cell.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          openHandler();
        }
      });
    });

    if (staffCalActiveYmd && dayLookupMap.has(staffCalActiveYmd)) {
      const modal = document.getElementById('staffCalDayModal');
      if (modal && modal.classList.contains('active')) {
        const entry = dayLookupMap.get(staffCalActiveYmd);
        openStaffCalDayModal(entry.dateObj, entry.docs, entry.patients);
      }
    }
  }

  document.getElementById('staffCalDayModal')?.addEventListener('click', (e) => {
    if (e.target && e.target.id === 'staffCalDayModal') closeStaffCalDayModal();
  });

  // ── 6. Consultations View Renderer ──
  function renderConsultations() {
    renderStaffQueueCalendar();
    const tbody = document.getElementById('consultationsTableBody');
    if (!tbody) return;

    const filtered = allPatients.filter(p => {
      if (consultDate) {
        const pDate = String(p.appointment_date || p.created_at || '').split(' ')[0];
        if (pDate !== consultDate) return false;
      }

      if (consultSearch) {
        const q = consultSearch.toLowerCase();
        const mName = (p.full_name || '').toLowerCase().includes(q);
        const mId = (p.patient_id || '').toLowerCase().includes(q) || (p.reference_no || '').toLowerCase().includes(q);
        const mPhone = (p.contact_number || '').includes(q);
        if (!mName && !mId && !mPhone) return false;
      }

      if (consultStatus !== 'all') {
        const s = (p.status || '').toLowerCase();
        if (consultStatus === 'inquiry' && !isInquiryRecord(p)) return false;
        if (consultStatus === 'for_approval' && !inApprovalStatus(s)) return false;
        if (consultStatus === 'approved' && !isConfirmedStatus(s)) return false;
        if (consultStatus === 'in_clinic' && !isInClinicStatus(s)) return false;
        if (consultStatus === 'completed' && s !== 'completed') return false;
        if (consultStatus === 'transferred' && s !== 'transferred') return false;
        if (consultStatus === 'cancelled' && !isCancelledStatus(s)) return false;
      }
      return true;
    });

    if (!filtered.length) {
      tbody.innerHTML = `
        <tr>
          <td colspan="6" class="table-empty-row">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            <div>No consultations matching current filter criteria.</div>
          </td>
        </tr>
      `;
      return;
    }

    tbody.innerHTML = filtered.map(p => {
      const isInquiry = isInquiryRecord(p);
      const isOnline = !p.unscheduled_walkin;
      const isTeleconsult = String(p.consultation_mode || '').trim().toLowerCase() === 'teleconsultation';
      const teleBadge = isTeleconsult
        ? ' <span class="patient-type-badge" style="background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;">Teleconsult</span>'
        : '';
      const typeTag = isInquiry
        ? '<span class="patient-type-badge" style="background:#fef3c7;color:#92400e;border:1px solid #fde68a;">Inquiry Only</span>'
        : (isOnline
          ? `<span class="patient-type-badge online">Online</span>${teleBadge}`
          : `<span class="patient-type-badge walkin">Walk-in / Unscheduled</span>${teleBadge}`);

      const dateParts = formatDateBadge(p.appointment_date || p.created_at);
      const timeLabel = p.preferred_time_slot || formatTime12(p.scheduled_time) || '8:00 AM';
      const statusNorm = (p.status || 'pending').toLowerCase();
      const statusPill = renderStatusPill(statusNorm);
      const isForApproval = inApprovalStatus(statusNorm);

      let primaryAction = '';
      let menuItems = '';
      const isLockedDoc = statusNorm === 'completed' || statusNorm === 'transferred' || isCancelledStatus(statusNorm);

      if (isInquiry && !isLockedDoc) {
        primaryAction = `<button type="button" class="btn-clean primary sm" onclick="openResolveInquiryModal('${escapeHtml(p.patient_id)}', '${escapeHtml(p.full_name)}')">Resolve</button>`;
        menuItems = `<button type="button" class="row-menu-item danger" onclick="handleReject('${escapeHtml(p.patient_id)}')">${isForApproval ? 'Decline' : 'Cancel'}</button>`;
      } else if (isForApproval) {
        primaryAction = `<button type="button" class="btn-clean primary sm" onclick="handleApprove('${escapeHtml(p.patient_id)}', this)">Approve</button>`;
        menuItems = `<button type="button" class="row-menu-item danger" onclick="handleReject('${escapeHtml(p.patient_id)}')">Decline</button>`;
      } else if (statusNorm === 'approved' || statusNorm === 'confirmed') {
        primaryAction = `<button type="button" class="btn-clean primary sm" onclick="handleCheckIn('${escapeHtml(p.patient_id)}', this)">Check in</button>`;
        menuItems = `<button type="button" class="row-menu-item danger" onclick="handleReject('${escapeHtml(p.patient_id)}')">Cancel</button>`;
      } else if (statusNorm === 'arrived') {
        primaryAction = `<button type="button" class="btn-clean primary sm" onclick="handleStartConsult('${escapeHtml(p.patient_id)}', this)">Start Consult</button>`;
        menuItems = `<button type="button" class="row-menu-item danger" onclick="handleReject('${escapeHtml(p.patient_id)}')">Cancel</button>`;
      } else if (statusNorm === 'ongoing') {
        primaryAction = `<button type="button" class="btn-clean primary sm" onclick="openClinicalNoteModal('${escapeHtml(p.patient_id)}', '${escapeHtml(p.full_name)}')">Complete</button>`;
        menuItems = `<button type="button" class="row-menu-item danger" onclick="handleReject('${escapeHtml(p.patient_id)}')">Cancel</button>`;
      }
      if (!isLockedDoc) {
        if (!isInquiry && (isForApproval || isTeleconsult)) {
          menuItems += `<button type="button" class="row-menu-item" onclick="openScheduleAssistModal('${escapeHtml(p.patient_id)}')">${isTeleconsult && !isForApproval ? 'Edit Teleconsult Link' : 'Call &amp; Reschedule'}</button>`;
        }
        menuItems += `<button type="button" class="row-menu-item" onclick="openTransferModal('${escapeHtml(p.patient_id)}')">Transfer Department</button>`;
      }
      menuItems += `<button type="button" class="row-menu-item" onclick="openPatientDetailsModal('${escapeHtml(p.patient_id)}')">Details</button>`;

      const deptLabel = formatDeptLabel(p.department);
      const deptCellHtml = `<span class="table-dept-tag" style="margin-bottom:4px;">${escapeHtml(deptLabel)}</span>`;

      let docCellHtml = '';
      if (isInquiry) {
        docCellHtml = '<span class="table-doc-static" style="color:#64748b;">General Inquiry</span>';
      } else if (!isForApproval || isLockedDoc) {
        const assignedName = p.assigned_doctor && p.assigned_doctor !== 'None'
          ? (p.assigned_doctor.startsWith('Dr.') ? p.assigned_doctor : 'Dr. ' + p.assigned_doctor)
          : 'Unassigned';
        docCellHtml = `<span class="table-doc-static" title="${escapeHtml(assignedName)}">${escapeHtml(assignedName)}</span>`;
      } else {
        docCellHtml = renderDocPickerCell(p);
      }

      const prevBadge = p.previous_appointment_date
        ? `<div class="date-stack-sub" title="Previous appointment">Previous ${escapeHtml(p.previous_appointment_date)}${p.previous_appointment_time ? ' · ' + escapeHtml(p.previous_appointment_time) : ''}</div>`
        : '';

      const scheduleCellHtml = isInquiry
        ? `<div class="date-stack"><div class="date-stack-main" style="color:#92400e;">Inquiry Only</div><div class="date-stack-sub">No clinic slot</div></div>`
        : `<div class="date-stack"><div class="date-stack-main">${escapeHtml(dateParts.monthDay)}</div><div class="date-stack-sub">${escapeHtml(dateParts.year)} · ${escapeHtml(timeLabel)}</div>${prevBadge}</div>`;

      return `
        <tr>
          <td>
            <div class="patient-cell-name">
              <span>${escapeHtml(p.full_name || 'Patient')}</span>
              ${typeTag}
            </div>
            <div class="patient-cell-meta">${escapeHtml(p.reference_no || p.patient_id)} · ${escapeHtml(p.contact_number || 'No phone')}</div>
          </td>
          <td>${scheduleCellHtml}</td>
          <td>
            <div>${deptCellHtml}</div>
            <div style="max-width:220px; font-size:0.82rem; color:#334155; line-height:1.35; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="${escapeHtml(p.chief_complaints || 'General consultation')}">
              ${escapeHtml(p.chief_complaints || 'General consultation')}
            </div>
          </td>
          <td>${docCellHtml}</td>
          <td>${statusPill}</td>
          <td>
            <div class="row-actions">
              ${primaryAction}
              <div class="row-menu">
                <button type="button" class="row-menu-btn" aria-label="More actions" onclick="toggleRowMenu(event, this)">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="5" cy="12" r="1.6"></circle><circle cx="12" cy="12" r="1.6"></circle><circle cx="19" cy="12" r="1.6"></circle></svg>
                </button>
                <div class="row-menu-pop">${menuItems}</div>
              </div>
            </div>
          </td>
        </tr>
      `;
    }).join('');
  }

  // ── 7. Records View Renderer (Grouped by Unique Patient, With Search & Sorting) ──
  function renderRecords() {
    const tbody = document.getElementById('recordsTableBody');
    if (!tbody) return;

    const completedRows = allPatients.filter(p => (p.status || '').toLowerCase() === 'completed');

    // Deduplicate by person-scoped identity (never merge different people on shared email/user_id)
    const groupedMap = new Map();
    const aliasMap = new Map();
    for (const row of completedRows) {
      const nameNorm = String(row.full_name || '').trim().toLowerCase().replace(/\s+/g, ' ');
      const birthVal = String(row.birthdate || '').trim();
      const emailNorm = String(row.email || '').trim().toLowerCase();
      const genderNorm = String(row.gender || '').trim().toLowerCase();

      const candidates = [];
      if (nameNorm && birthVal && birthVal !== '0000-00-00') candidates.push(`nb:${nameNorm}|${birthVal}`);
      if (nameNorm && emailNorm) candidates.push(`ne:${nameNorm}|${emailNorm}`);
      if (!candidates.length) {
        if (nameNorm && genderNorm) candidates.push(`ng:${nameNorm}|${genderNorm}`);
        else if (nameNorm) candidates.push(`n:${nameNorm}`);
        else if (row.hrn) candidates.push(`hrn:${String(row.hrn).trim().toLowerCase()}`);
        else candidates.push(`id:${row.id || row.patient_id}`);
      }

      let identityKey = null;
      for (const c of candidates) {
        if (aliasMap.has(c)) { identityKey = aliasMap.get(c); break; }
      }
      if (!identityKey) identityKey = candidates[0];
      for (const c of candidates) aliasMap.set(c, identityKey);

      const rowTs = new Date(row.appointment_date || row.created_at || 0).getTime() || 0;
      const existing = groupedMap.get(identityKey);

      if (!existing) {
        groupedMap.set(identityKey, {
          ...row,
          _latestTs: rowTs,
          _earliestId: Number(row.id) || Number.MAX_SAFE_INTEGER,
          _displayPatientId: row.canonical_patient_id || row.patient_id,
          _completedCount: 1,
          _allIds: [row.patient_id, row.reference_no].filter(Boolean)
        });
      } else {
        existing._completedCount += 1;
        if (row.patient_id) existing._allIds.push(row.patient_id);
        if (row.reference_no) existing._allIds.push(row.reference_no);

        // Preserve earliest original PT- ID
        const rowNumId = Number(row.id) || Number.MAX_SAFE_INTEGER;
        if (rowNumId < existing._earliestId) {
          existing._earliestId = rowNumId;
          existing._displayPatientId = row.canonical_patient_id || row.patient_id || existing._displayPatientId;
        }

        // Keep the most recent completed visit as the primary row summary
        if (rowTs > existing._latestTs || (rowTs === existing._latestTs && rowNumId > (Number(existing.id) || 0))) {
          const prevCount = existing._completedCount;
          const prevEarliest = existing._earliestId;
          const prevDisplayPid = existing._displayPatientId;
          const prevAllIds = existing._allIds;
          groupedMap.set(identityKey, {
            ...row,
            _latestTs: rowTs,
            _earliestId: prevEarliest,
            _displayPatientId: prevDisplayPid,
            _completedCount: prevCount,
            _allIds: prevAllIds
          });
        }
      }
    }

    const filtered = Array.from(groupedMap.values()).filter(p => {
      if (recordsSearch) {
        const q = recordsSearch.toLowerCase();
        const mName = (p.full_name || '').toLowerCase().includes(q);
        const mEmail = (p.email || '').toLowerCase().includes(q);
        const mId = (p._displayPatientId || '').toLowerCase().includes(q)
          || (p._allIds || []).some(id => String(id).toLowerCase().includes(q));
        const mHrn = (p.hrn || '').toLowerCase().includes(q);
        const mDoc = (p.assigned_doctor || '').toLowerCase().includes(q);
        const mComplaint = (p.chief_complaints || '').toLowerCase().includes(q);
        const mRecipient = (p.appointment_recipient || '').toLowerCase().includes(q)
          || (p.relationship_to_patient || '').toLowerCase().includes(q);
        if (!mName && !mEmail && !mId && !mHrn && !mDoc && !mComplaint && !mRecipient) return false;
      }

      return true;
    });

    filtered.sort((a, b) => {
      if (recordsSort === 'date_desc') {
        return new Date(b.appointment_date || b.created_at || 0) - new Date(a.appointment_date || a.created_at || 0);
      } else if (recordsSort === 'date_asc') {
        return new Date(a.appointment_date || a.created_at || 0) - new Date(b.appointment_date || b.created_at || 0);
      } else if (recordsSort === 'name_asc') {
        return (a.full_name || '').localeCompare(b.full_name || '');
      } else if (recordsSort === 'name_desc') {
        return (b.full_name || '').localeCompare(a.full_name || '');
      } else if (recordsSort === 'status') {
        return (a.status || '').localeCompare(b.status || '');
      }
      return 0;
    });

    if (!filtered.length) {
      tbody.innerHTML = `
        <tr>
          <td colspan="6" class="table-empty-row">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
            <div>No patient records match your search or sort filter.</div>
          </td>
        </tr>
      `;
      return;
    }

    tbody.innerHTML = filtered.map(p => {
      const dateParts = formatDateBadge(p.appointment_date || p.created_at);
      const timeLabel = p.preferred_time_slot || formatTime12(p.scheduled_time) || '8:00 AM';
      const statusPill = renderStatusPill(p.status);
      const docName = p.assigned_doctor && p.assigned_doctor !== 'None'
        ? (p.assigned_doctor.startsWith('Dr.') ? p.assigned_doctor : 'Dr. ' + p.assigned_doctor)
        : 'Attending Physician';

      const visitCount = Math.max(p._completedCount || 1, Number(p.completed_visit_count) || 1);
      const visitMeta = visitCount > 1 ? ` · ${visitCount} visits` : '';
      const displayPid = p._displayPatientId || p.canonical_patient_id || p.patient_id;
      const prevRecBadge = p.previous_appointment_date
        ? `<div class="date-stack-sub">Previous ${escapeHtml(p.previous_appointment_date)}${p.previous_appointment_time ? ' · ' + escapeHtml(p.previous_appointment_time) : ''}</div>`
        : '';

      const isOtherPatient = ['other patient', 'someone else / dependent'].includes(
        String(p.appointment_recipient || '').trim().toLowerCase()
      );
      const otherRel = p.relationship_to_patient ? ` · ${p.relationship_to_patient}` : '';
      const otherBadge = isOtherPatient
        ? `<span class="other-patient-pill">Other Patient${escapeHtml(otherRel)}</span>`
        : '';

      return `
        <tr>
          <td>
            <div class="patient-cell-name"><span>${escapeHtml(p.full_name || 'Patient')}</span>${otherBadge}</div>
            <div class="patient-cell-meta">${escapeHtml(displayPid)} · ${escapeHtml(p.gender || '-')}${escapeHtml(visitMeta)}</div>
          </td>
          <td>
            <div class="date-stack">
              <div class="date-stack-main">${escapeHtml(dateParts.monthDay)}</div>
              <div class="date-stack-sub">${escapeHtml(dateParts.year)} · ${escapeHtml(timeLabel)}</div>
              ${prevRecBadge}
            </div>
          </td>
          <td>
            <div style="font-size:0.75rem; font-weight:700; color:#0369a1; margin-bottom:2px;">${escapeHtml(formatDeptLabel(p.department))}</div>
            <div style="max-width:240px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; color:#334155;">
              ${escapeHtml(p.chief_complaints || 'Routine Consultation')}
            </div>
          </td>
          <td>
            <div style="font-weight:500; color:#0f172a;">${escapeHtml(docName)}</div>
          </td>
          <td>${statusPill}</td>
          <td>
            <div class="table-action-group">
              <button type="button" class="btn-clean outline sm" onclick="openPatientDetailsModal('${p.patient_id}')">View Record</button>
            </div>
          </td>
        </tr>
      `;
    }).join('');
  }

  // ── 8. Formatting Helpers ──
  function formatDateBadge(rawDate) {
    if (!rawDate) return { month: 'NOW', day: '--', full: 'Today', monthDay: 'Today', year: '' };
    const clean = String(rawDate).split(' ')[0];
    const d = new Date(clean + 'T00:00:00');
    if (isNaN(d.getTime())) return { month: 'TBD', day: '--', full: rawDate, monthDay: String(rawDate), year: '' };
    const month = d.toLocaleDateString('en-US', { month: 'short' }).toUpperCase();
    const day = String(d.getDate());
    const monthDay = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    const year = String(d.getFullYear());
    const full = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    return { month, day, monthDay, year, full };
  }

  function formatTime12(t) {
    if (!t) return '';
    const parts = String(t).split(':');
    if (parts.length < 2) return t;
    let h = parseInt(parts[0], 10);
    const m = parts[1];
    if (isNaN(h)) return t;
    const ampm = h >= 12 ? 'PM' : 'AM';
    h = h % 12 || 12;
    return `${h}:${m} ${ampm}`;
  }

  function renderStatusPill(st) {
    const s = (st || 'pending').toLowerCase();
    if (inApprovalStatus(s)) return '<span class="status-pill for_approval">For Approval</span>';
    if (isConfirmedStatus(s)) return '<span class="status-pill approved">Confirmed</span>';
    if (s === 'arrived') return '<span class="status-pill arrived">Checked In</span>';
    if (s === 'ongoing') return '<span class="status-pill ongoing">In Consultation</span>';
    if (s === 'completed') return '<span class="status-pill completed">Completed</span>';
    if (isCancelledStatus(s)) return '<span class="status-pill rejected">Cancelled</span>';
    return `<span class="status-pill transferred">${escapeHtml(ucFirst(s))}</span>`;
  }

  function parseTimeMinutes(t, fallbackMinutes = 480) {
    if (!t) return fallbackMinutes;
    const raw = String(t).trim().toUpperCase();
    const ampmMatch = raw.match(/^(\d{1,2}):(\d{2})(?::\d{2})?\s*(AM|PM)?$/);
    if (!ampmMatch) return fallbackMinutes;
    let h = parseInt(ampmMatch[1], 10);
    const m = parseInt(ampmMatch[2], 10);
    const mer = ampmMatch[3];
    if (isNaN(h) || isNaN(m)) return fallbackMinutes;
    if (mer === 'PM' && h < 12) h += 12;
    if (mer === 'AM' && h === 12) h = 0;
    return h * 60 + m;
  }

  function formatCompactHour(t, fallback) {
    const mins = parseTimeMinutes(t, fallback);
    const h24 = Math.floor(mins / 60);
    const m = mins % 60;
    const ampm = h24 >= 12 ? 'PM' : 'AM';
    const h12 = h24 % 12 || 12;
    return m === 0 ? `${h12}${ampm}` : `${h12}:${String(m).padStart(2, '0')}${ampm}`;
  }

  function getPatientPreferredSlot(p) {
    const pref = String(p?.preferred_time_slot || '').toLowerCase();
    if (pref.includes('morning')) return 'Morning';
    if (pref.includes('afternoon')) return 'Afternoon';
    const sched = String(p?.scheduled_time || '').trim();
    if (sched) {
      return parseTimeMinutes(sched, 480) >= 720 ? 'Afternoon' : 'Morning';
    }
    const leadingTimeMatch = pref.match(/\b(\d{1,2}:\d{2}\s*(?:am|pm)?)/i);
    if (leadingTimeMatch) {
      return parseTimeMinutes(leadingTimeMatch[1], 480) >= 720 ? 'Afternoon' : 'Morning';
    }
    return 'Morning';
  }

  function getDoctorsForDept(dept) {
    const key = normalizeSchedDept(dept);
    const all = allDoctors || [];
    if (key !== 'other') {
      const matched = all.filter(d => normalizeSchedDept(d.department) === key);
      if (matched.length) return matched;
    }
    if (departmentDoctors && departmentDoctors.length) return departmentDoctors;
    return all;
  }

  function getDoctorLoadOnDate(docName, dateStr, excludePatientId) {
    const cleanTarget = normalizeDoctorName(docName);
    const cleanDate = String(dateStr || '').split(' ')[0];
    if (!cleanTarget || !cleanDate) return 0;
    return (allPatients || []).filter(item => {
      if (excludePatientId && item.patient_id === excludePatientId) return false;
      const st = (item.status || '').toLowerCase();
      if (isCancelledStatus(st) || st === 'transferred') return false;
      const itemDate = String(item.appointment_date || item.created_at || '').split(' ')[0];
      if (itemDate !== cleanDate) return false;
      const assigned = normalizeDoctorName(item.assigned_doctor);
      return assigned && assigned === cleanTarget;
    }).length;
  }

  function evaluateDoctorSlotForPatient(doc, dateStr, slotBlock, excludePatientId) {
    const isIn = isDocAvailable(doc.status);
    const startMin = parseTimeMinutes(doc.schedule_start, 480);
    const endMin = parseTimeMinutes(doc.schedule_end, 1020);
    const coversMorning = startMin < 720 && endMin > 480;
    const coversAfternoon = startMin < 1020 && endMin > 780;
    const coversSlot = slotBlock === 'Afternoon' ? coversAfternoon : coversMorning;

    const cleanDate = String(dateStr || '').split(' ')[0];
    let isOpenDay = true;
    if (cleanDate) {
      const parsed = new Date(cleanDate + 'T00:00:00');
      if (!isNaN(parsed.getTime()) && parsed.getDay() === 0) {
        isOpenDay = false;
      }
    }

    const totalSlots = Math.min(100, Math.max(1, parseInt(doc.total_slots, 10) || 20));
    const bookedCount = cleanDate
      ? getDoctorLoadOnDate(doc.name, cleanDate, excludePatientId)
      : getDoctorTodayLoad(doc.name);
    const remainingSlots = Math.max(0, totalSlots - bookedCount);

    const startFmt = doc.schedule_start_fmt || formatTime12(doc.schedule_start) || '8:00 AM';
    const endFmt = doc.schedule_end_fmt || formatTime12(doc.schedule_end) || '5:00 PM';
    const hoursFull = `${startFmt} – ${endFmt}`;
    const hoursCompact = `${formatCompactHour(doc.schedule_start, 480)}-${formatCompactHour(doc.schedule_end, 1020)}`;
    const cleanName = String(doc.name || '').replace(/^Dr\.\s*/, '').trim();
    const docLabel = cleanName ? `Dr. ${cleanName}` : 'Physician';

    let shortReason = '';
    let reason = '';
    if (!isIn) {
      shortReason = 'Doctor Out';
      reason = `${docLabel} is currently Out / Unavailable`;
    } else if (!isOpenDay) {
      shortReason = 'Sunday Closed';
      reason = 'Hospital clinics are closed on Sundays (Mon–Sat only)';
    } else if (!coversSlot) {
      shortReason = 'Outside Hours';
      reason = `${docLabel} (${hoursFull}) does not cover the ${slotBlock} time block`;
    } else if (remainingSlots <= 0) {
      shortReason = 'Slots Full';
      reason = `${docLabel} has 0 remaining slots on ${cleanDate}`;
    }

    return {
      isIn,
      isOpenDay,
      coversMorning,
      coversAfternoon,
      coversSlot,
      totalSlots,
      bookedCount,
      remainingSlots,
      hoursFull,
      hoursCompact,
      isAvailable: isIn && isOpenDay && coversSlot && remainingSlots > 0,
      shortReason,
      reason
    };
  }

  function evaluateDepartmentAvailabilityForPatient(p) {
    const deptDocs = getDoctorsForDept(p?.department);
    const slotBlock = getPatientPreferredSlot(p);
    const cleanDate = String(p?.appointment_date || '').split(' ')[0];
    const evaluated = deptDocs.map(doc => {
      const cleanName = String(doc.name || '').replace(/^Dr\.\s*/, '').trim();
      const normName = normalizeDoctorName(doc.name);
      return {
        doc,
        cleanName,
        normName,
        eval: evaluateDoctorSlotForPatient(doc, cleanDate, slotBlock, p?.patient_id)
      };
    });
    const hasAnyAvailable = evaluated.some(item => item.eval.isAvailable);
    return { deptDocs, evaluated, hasAnyAvailable, slotBlock, cleanDate };
  }

  function renderDocPickerCell(p) {
    const deptAvail = evaluateDepartmentAvailabilityForPatient(p);
    const assignedRaw = String(p.assigned_doctor || '').trim();
    const assignedNorm = normalizeDoctorName(assignedRaw);
    const hasAssigned = Boolean(assignedNorm && assignedNorm !== 'none');
    const assignedEntry = hasAssigned
      ? deptAvail.evaluated.find(item => item.normName === assignedNorm)
      : null;
    const assignedHasIssue = Boolean(assignedEntry && !assignedEntry.eval.isAvailable);
    const pidEsc = escapeHtml(p.patient_id);

    let triggerCls = 'doc-picker-trigger';
    let dotHtml = '';
    let labelText = 'Assign Physician';

    if (hasAssigned) {
      const displayDoc = assignedRaw.startsWith('Dr.') ? assignedRaw : `Dr. ${assignedRaw}`;
      labelText = displayDoc;
      if (assignedHasIssue) {
        triggerCls += ' is-warn';
        dotHtml = '<span class="doc-picker-dot is-warn" aria-hidden="true"></span>';
      } else {
        triggerCls += ' is-assigned';
        dotHtml = '<span class="doc-picker-dot is-ok" aria-hidden="true"></span>';
      }
    } else if (!deptAvail.hasAnyAvailable) {
      triggerCls += ' is-warn';
      dotHtml = '<span class="doc-picker-dot is-warn" aria-hidden="true"></span>';
      labelText = 'No Slot · Reschedule';
    } else {
      triggerCls += ' is-unassigned';
    }

    const itemsHtml = deptAvail.evaluated.map(entry => {
      const { doc, cleanName, normName, eval: ev } = entry;
      const isSelected = hasAssigned && normName === assignedNorm;
      const badgeText = ev.isAvailable ? `${ev.remainingSlots} slots` : ev.shortReason;
      return `
        <button type="button"
                class="doc-picker-item${isSelected ? ' is-selected' : ''}${ev.isAvailable ? '' : ' is-unavail'}"
                onclick="closeAllDocPickers(); handleAssignDoctor('${pidEsc}', '${escapeHtml(doc.name)}')">
          <div class="doc-picker-item-info">
            <div class="doc-picker-item-name">Dr. ${escapeHtml(cleanName)}</div>
            <div class="doc-picker-item-hours">${escapeHtml(ev.hoursFull)}</div>
          </div>
          <span class="doc-picker-badge ${ev.isAvailable ? 'is-open' : 'is-unavail'}">${escapeHtml(badgeText)}</span>
        </button>
      `;
    }).join('');

    return `
      <div class="doc-picker-wrap">
        <button type="button"
                class="${triggerCls}"
                data-patient-id="${pidEsc}"
                aria-expanded="false"
                onclick="toggleDocPicker(event, this)"
                title="${escapeHtml(labelText)}">
          <span class="doc-picker-trigger-label">${dotHtml}<span class="doc-picker-trigger-text">${escapeHtml(labelText)}</span></span>
          <svg class="doc-picker-caret" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"></polyline></svg>
        </button>
        <div class="doc-picker-pop" role="listbox" onclick="event.stopPropagation()">
          <div class="doc-picker-head">
            <span>Select Physician</span>
            <span class="doc-picker-slot-tag">${escapeHtml(deptAvail.slotBlock)}</span>
          </div>
          <div class="doc-picker-list">${itemsHtml || '<div style="padding:12px;font-size:0.78rem;color:#64748b;">No physicians found for this department.</div>'}</div>
          <div class="doc-picker-foot">
            <button type="button" class="doc-picker-resched-btn" onclick="closeAllDocPickers(); openScheduleAssistModal('${pidEsc}')">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
              <span>Call &amp; Reschedule Patient</span>
            </button>
          </div>
        </div>
      </div>
    `;
  }

  // ── 9. Consultation Workflow Actions ──
  window.handleAssignDepartment = async function (patientId, newDept) {
    const p = (allPatients || []).find(item => item.patient_id === patientId);
    if (p) {
      const s = (p.status || '').toLowerCase();
      if (s === 'completed' || isCancelledStatus(s)) {
        showToast('Department cannot be changed for completed or cancelled appointments.');
        return;
      }
    }
    if (!newDept || newDept === 'Triage Pending') {
      showToast('Please select a valid department to assign.');
      return;
    }
    try {
      const res = await fetch('/sdn/api/patients.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'assign_department', patient_id: patientId, department: newDept })
      }).then(r => r.json());

      if (res.success) {
        showToast(res.message || `Assigned to ${newDept}`);
        loadData();
        if (detailsModal && detailsModal.classList.contains('open') && currentDetailsPatientId === patientId) {
          openPatientDetailsModal(patientId);
        }
      } else {
        showToast(res.message || 'Could not update department.', 2800, 'error');
      }
    } catch (e) {
      showToast('Network error.', 2800, 'error');
    }
  };

  // ── 9.1 Transfer Patient Department Modal UI ──
  const transferModal = document.getElementById('transferPatientModal');

  window.openTransferModal = function (patientId) {
    const p = (allPatients || []).find(item => item.patient_id === patientId);
    if (!p) return;

    const s = (p.status || '').toLowerCase();
    if (s === 'completed' || s === 'transferred' || isCancelledStatus(s)) {
      showToast('This patient appointment cannot be transferred.', 2800, 'error');
      return;
    }

    const pidInput = document.getElementById('transferPatientId');
    if (pidInput) pidInput.value = p.patient_id;

    const nameEl = document.getElementById('transferPatientName');
    if (nameEl) nameEl.textContent = p.full_name || 'Patient';

    const dateParts = formatDateBadge(p.appointment_date || p.created_at);
    const timeLabel = formatTime12(p.scheduled_time) || '8:00 AM';
    const metaEl = document.getElementById('transferPatientMeta');
    if (metaEl) metaEl.textContent = `${p.patient_id} • ${dateParts.full} (${timeLabel})`;

    const complaintEl = document.getElementById('transferComplaintText');
    if (complaintEl) {
      const complaintStr = (p.chief_complaints || '').trim();
      complaintEl.textContent = (complaintStr && complaintStr.toLowerCase() !== 'none')
        ? complaintStr
        : 'General consultation / No specific complaint provided';
    }

    const currentDeptKey = normalizeSchedDept(p.department);
    const currentBadge = document.getElementById('transferCurrentDeptBadge');
    if (currentBadge) {
      currentBadge.textContent = formatDeptLabel(p.department);
    }

    const deptSelect = document.getElementById('transferDepartmentSelect');
    if (deptSelect) {
      let firstAvailable = '';
      Array.from(deptSelect.options).forEach(opt => {
        const optKey = normalizeSchedDept(opt.value);
        const isSame = currentDeptKey !== 'other' && optKey === currentDeptKey;
        opt.disabled = isSame;
        if (!isSame && !firstAvailable) {
          firstAvailable = opt.value;
        }
      });
      if (firstAvailable) {
        deptSelect.value = firstAvailable;
      }
    }

    if (transferModal) transferModal.classList.add('open');
  };

  window.closeTransferModal = function () {
    if (transferModal) transferModal.classList.remove('open');
  };

  if (transferModal) {
    transferModal.addEventListener('click', (e) => {
      if (e.target === transferModal) closeTransferModal();
    });
  }

  window.submitTransferPatient = async function () {
    const pidInput = document.getElementById('transferPatientId');
    const deptSelect = document.getElementById('transferDepartmentSelect');
    const btn = document.getElementById('btnConfirmTransfer');

    const patientId = pidInput ? pidInput.value : '';
    const selectedDept = deptSelect ? deptSelect.value : '';

    if (!patientId || !selectedDept) {
      showToast('Please select a receiving department.', 2800, 'error');
      return;
    }

    if (btn) {
      btn.disabled = true;
      btn.textContent = 'Transferring...';
    }

    try {
      const res = await fetch('/sdn/api/patients.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'referral', patient_id: patientId, department: selectedDept })
      }).then(r => r.json());

      if (res.success) {
        showToast(res.message || `Patient transferred to ${selectedDept}.`);
        closeTransferModal();
        loadData();
      } else {
        showToast(res.message || 'Could not transfer patient.', 3200, 'error');
      }
    } catch (e) {
      showToast('Network error transferring patient.', 2800, 'error');
    } finally {
      if (btn) {
        btn.disabled = false;
        btn.textContent = 'Transfer Patient';
      }
    }
  };

  const pendingAssignPromises = new Map();

  async function executeApprove(patientId, department, assignedDoctor, triggerBtn) {
    if (triggerBtn) {
      triggerBtn.disabled = true;
    }
    try {
      const payload = { action: 'approve', patient_id: patientId, department: department };
      if (assignedDoctor && assignedDoctor !== 'None') {
        payload.assigned_doctor = assignedDoctor;
      }
      const res = await fetch('/sdn/api/patients.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      }).then(r => r.json());

      if (res.success) {
        const local = (allPatients || []).find(item => item.patient_id === patientId);
        if (local) {
          local.status = 'approved';
          if (department) local.department = department;
          if (assignedDoctor && assignedDoctor !== 'None') local.assigned_doctor = assignedDoctor;
        }
        renderOverview();
        renderConsultations();
        renderRecords();
        showToast('Appointment confirmed. The patient now sees Confirmed.');
        loadData();
      } else {
        showToast(res.message || 'Could not confirm appointment.', 3200, 'error');
      }
    } catch (e) {
      showToast('Network error.', 2800, 'error');
    } finally {
      if (triggerBtn && triggerBtn.isConnected) {
        triggerBtn.disabled = false;
      }
    }
  }

  function restoreDocSelectValue(selectEl, prevDoctor) {
    if (!selectEl) return;
    const normPrev = normalizeDoctorName(prevDoctor);
    if (!normPrev || normPrev === 'none') {
      selectEl.value = '';
      return;
    }
    const matchingOpt = Array.from(selectEl.options).find(o => {
      const valNorm = normalizeDoctorName(o.value);
      return valNorm && valNorm === normPrev;
    });
    selectEl.value = matchingOpt ? matchingOpt.value : '';
  }

  window.handleApprove = async function (patientId, triggerBtn) {
    if (pendingAssignPromises.has(patientId)) {
      try {
        await pendingAssignPromises.get(patientId);
      } catch (e) {}
    }

    const p = (allPatients || []).find(item => item.patient_id === patientId);
    if (!p) {
      await executeApprove(patientId, window.STAFF_DEPT || 'General Medical', '', triggerBtn);
      return;
    }

    if (!isInquiryRecord(p)) {
      const assignedNorm = normalizeDoctorName(p.assigned_doctor);
      const deptAvail = evaluateDepartmentAvailabilityForPatient(p);

      if (!assignedNorm || assignedNorm === 'none') {
        if (!deptAvail.hasAnyAvailable) {
          showToast('No physician is available on the patient\'s preferred schedule. Call the patient to offer available clinic days and time blocks.', 4200, 'error');
          openScheduleAssistModal(patientId);
          return;
        }
        const rowTrigger = Array.from(document.querySelectorAll('#consultationsTableBody .doc-picker-trigger'))
          .find(el => el.dataset.patientId === patientId);
        if (rowTrigger && rowTrigger.offsetParent !== null) {
          rowTrigger.focus();
          rowTrigger.classList.add('is-required-highlight');
          setTimeout(() => rowTrigger.classList.remove('is-required-highlight'), 2200);
          toggleDocPicker(null, rowTrigger, true);
          showToast('Please select an Attending Physician for this time slot before approving.', 3600, 'warning');
          return;
        }
        openScheduleAssistModal(patientId);
        showToast('Assign an available Attending Physician, then click Save & Approve.', 3600);
        return;
      }

      const assignedEntry = deptAvail.evaluated.find(item => item.normName === assignedNorm);
      if (assignedEntry && !assignedEntry.eval.isAvailable) {
        showToast(`${assignedEntry.eval.reason}. Call the patient to confirm an available schedule.`, 4000, 'error');
        openScheduleAssistModal(patientId, assignedEntry.doc.name);
        return;
      }

      const isTele = String(p.consultation_mode || '').trim().toLowerCase() === 'teleconsultation';
      if (isTele && !p.teleconsult_platform && !p.teleconsult_link) {
        showToast('Set the Teleconsultation channel or meeting link before approving.', 3400);
        openScheduleAssistModal(patientId, p.assigned_doctor || '');
        return;
      }
    }

    const currentDept = (p.department || '').trim();
    await executeApprove(patientId, currentDept || window.STAFF_DEPT || 'General Medical', p.assigned_doctor || '', triggerBtn);
  };

  window.handleReject = async function (patientId) {
    const p = (allPatients || []).find(item => item.patient_id === patientId);
    const waiting = inApprovalStatus(p?.status);
    const patientName = p?.full_name || 'this patient';
    const accepted = await showConfirm({
      title: waiting ? 'Decline this request?' : 'Cancel this visit?',
      message: waiting
        ? `${patientName} is still waiting for approval. Declining it marks the visit as cancelled in the patient portal.`
        : `${patientName} will see this visit as cancelled in the patient portal.`,
      confirmText: waiting ? 'Decline request' : 'Cancel visit',
      cancelText: 'Keep visit',
      danger: true
    });
    if (!accepted) return;
    try {
      const res = await fetch('/sdn/api/patients.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'cancel', patient_id: patientId })
      });
      const data = await res.json();

      if (data.success) {
        showToast(waiting ? 'Request declined. The patient will see it as cancelled.' : 'Visit cancelled.');
        loadData();
      } else {
        showToast(data.message || 'Could not cancel appointment.', 2800, 'error');
      }
    } catch (e) {
      console.error('Cancel error:', e);
      showToast('Error connecting to server.', 2800, 'error');
    }
  };

  window.handleCheckIn = async function (patientId, triggerBtn) {
    const origText = triggerBtn ? triggerBtn.textContent : '';
    if (triggerBtn) {
      triggerBtn.disabled = true;
      triggerBtn.textContent = 'Checking in...';
    }
    try {
      const res = await fetch('/sdn/api/patients.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'arrived', patient_id: patientId })
      });
      const data = await res.json();

      if (data.success) {
        showToast('Patient checked in. Their portal now shows Checked In.');
        await loadData();
      } else {
        showToast(data.message || 'Error checking in.', 2800, 'error');
      }
    } catch (e) {
      console.error('Check-in error:', e);
      showToast('Error connecting to server.', 2800, 'error');
    } finally {
      if (triggerBtn && triggerBtn.isConnected) {
        triggerBtn.disabled = false;
        triggerBtn.textContent = origText;
      }
    }
  };

  window.handleStartConsult = async function (patientId, triggerBtn) {
    const origText = triggerBtn ? triggerBtn.textContent : '';
    if (triggerBtn) {
      triggerBtn.disabled = true;
      triggerBtn.textContent = 'Starting...';
    }
    try {
      const res = await fetch('/sdn/api/patients.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'ongoing', patient_id: patientId })
      });
      const data = await res.json();

      if (data.success) {
        showToast('Consultation started. Their portal now shows In Consultation.');
        await loadData();
      } else {
        showToast(data.message || 'Error starting consultation.', 2800, 'error');
      }
    } catch (e) {
      console.error('Start consult error:', e);
      showToast('Error starting consultation. Please try again.', 2800, 'error');
    } finally {
      if (triggerBtn && triggerBtn.isConnected) {
        triggerBtn.disabled = false;
        triggerBtn.textContent = origText;
      }
    }
  };

  window.handleAssignDoctor = async function (patientId, doctorName, selectEl) {
    const p = (allPatients || []).find(item => item.patient_id === patientId);
    const prevDoc = p ? (p.assigned_doctor || '') : '';

    if (doctorName === '__reschedule__') {
      restoreDocSelectValue(selectEl, prevDoc);
      openScheduleAssistModal(patientId);
      return;
    }

    if (!doctorName) return;

    if (p && inApprovalStatus(p.status)) {
      const targetNorm = normalizeDoctorName(doctorName);
      const docObj = (allDoctors || []).find(d => normalizeDoctorName(d.name) === targetNorm);
      if (docObj) {
        const evalRes = evaluateDoctorSlotForPatient(docObj, p.appointment_date, getPatientPreferredSlot(p), patientId);
        if (!evalRes.isAvailable) {
          restoreDocSelectValue(selectEl, prevDoc);
          showToast(`${evalRes.reason}. Call the patient to inform them and offer an available schedule.`, 4200, 'error');
          openScheduleAssistModal(patientId, docObj.name);
          return;
        }
      }
    }

    if (p) {
      p.assigned_doctor = doctorName;
      if (currentView === 'consultation') renderConsultations();
    }

    const assignPromise = (async () => {
      try {
        const res = await fetch('/sdn/api/patients.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'assign', patient_id: patientId, doctor: doctorName })
        }).then(r => r.json());

        if (res.success) {
          if (p) p.assigned_doctor = res.assigned_doctor || doctorName;
          showToast(`Assigned ${res.assigned_doctor || doctorName}`);
          await loadData();
        } else {
          if (p) {
            p.assigned_doctor = prevDoc;
            if (currentView === 'consultation') renderConsultations();
          }
          restoreDocSelectValue(selectEl, prevDoc);
          showToast(res.message || 'Could not assign physician.', 3200, 'error');
        }
      } catch (e) {
        if (p) {
          p.assigned_doctor = prevDoc;
          if (currentView === 'consultation') renderConsultations();
        }
        restoreDocSelectValue(selectEl, prevDoc);
        showToast('Network error.', 2800, 'error');
      } finally {
        pendingAssignPromises.delete(patientId);
      }
    })();

    pendingAssignPromises.set(patientId, assignPromise);
    await assignPromise;
  };

  // ── 9.2 Schedule Availability & Patient Call Assist Modal ──
  const scheduleAssistModal = document.getElementById('scheduleAssistModal');

  function getNextClinicDateStr(rawDate) {
    const clean = String(rawDate || '').split(' ')[0];
    if (/^\d{4}-\d{2}-\d{2}$/.test(clean)) return clean;
    const d = new Date();
    d.setDate(d.getDate() + 1);
    if (d.getDay() === 0) d.setDate(d.getDate() + 1);
    return d.toISOString().split('T')[0];
  }

  window.openScheduleAssistModal = function (patientId, preselectDoctorName) {
    const p = (allPatients || []).find(item => item.patient_id === patientId);
    if (!p || !scheduleAssistModal) return;

    const pidInput = document.getElementById('saPatientId');
    if (pidInput) pidInput.value = p.patient_id;

    const subtitleEl = document.getElementById('saModalSubtitle');
    if (subtitleEl) {
      subtitleEl.textContent = `${p.full_name || 'Patient'} (${p.reference_no || p.patient_id}) · ${formatDeptLabel(p.department)}`;
    }

    const prefDate = p.appointment_date ? formatDateBadge(p.appointment_date).full : 'No date specified';
    const prefSlot = p.preferred_time_slot || (getPatientPreferredSlot(p) === 'Afternoon' ? 'Afternoon (1:00 PM - 5:00 PM)' : 'Morning (8:00 AM - 12:00 PM)');
    const deptAvail = evaluateDepartmentAvailabilityForPatient(p);

    const alertTitle = document.getElementById('saAlertTitle');
    const alertCopy = document.getElementById('saAlertCopy');
    if (alertTitle && alertCopy) {
      if (!deptAvail.hasAnyAvailable) {
        alertTitle.textContent = 'Preferred Schedule Unavailable — Call Patient Required';
        alertCopy.textContent = `No ${formatDeptLabel(p.department)} physician is available for the patient's preferred schedule (${prefDate} · ${prefSlot}). Please call ${p.full_name || 'the patient'} to inform them and offer one of the available department days and time blocks below.`;
      } else {
        alertTitle.textContent = 'Confirm Schedule or Coordinate Reschedule with Patient';
        alertCopy.textContent = `Patient requested ${prefDate} (${prefSlot}). If the chosen physician's time slot does not match, call ${p.full_name || 'the patient'} to agree on an available day and time block below.`;
      }
    }

    const phoneLink = document.getElementById('saPatientPhone');
    const phoneText = document.getElementById('saPatientPhoneText');
    const rawPhone = String(p.contact_number || '').trim();
    if (phoneLink && phoneText) {
      if (rawPhone && rawPhone.toLowerCase() !== 'none') {
        phoneLink.href = `tel:${rawPhone.replace(/[^+\d]/g, '')}`;
        phoneLink.classList.remove('is-disabled');
        phoneText.textContent = `Call ${rawPhone}`;
      } else {
        phoneLink.href = '#';
        phoneLink.classList.add('is-disabled');
        phoneText.textContent = 'No phone number';
      }
    }

    const deptHead = document.getElementById('saDeptHeading');
    if (deptHead) {
      deptHead.textContent = `${formatDeptLabel(p.department)} — Available Time Blocks`;
    }

    const dateInput = document.getElementById('saApptDate');
    if (dateInput) {
      dateInput.value = getNextClinicDateStr(p.appointment_date);
    }

    const slotSelect = document.getElementById('saTimeSlot');
    if (slotSelect) {
      slotSelect.value = getPatientPreferredSlot(p) === 'Afternoon'
        ? 'Afternoon (1:00 PM - 5:00 PM)'
        : 'Morning (8:00 AM - 12:00 PM)';
    }

    const canApproveNow = inApprovalStatus(p.status);
    const approveBtn = document.getElementById('btnSaSaveApprove');
    const saveOnlyBtn = document.getElementById('btnSaSaveOnly');
    if (approveBtn) {
      approveBtn.style.display = canApproveNow ? 'inline-flex' : 'none';
    }
    if (saveOnlyBtn) {
      saveOnlyBtn.className = canApproveNow ? 'btn-clean outline' : 'btn-clean primary';
    }

    const isTele = String(p.consultation_mode || '').trim().toLowerCase() === 'teleconsultation';
    const tcSection = document.getElementById('saTeleconsultSection');
    const tcPlatform = document.getElementById('saTeleconsultPlatform');
    const tcLink = document.getElementById('saTeleconsultLink');
    const tcNotes = document.getElementById('saTeleconsultNotes');
    if (tcSection) {
      tcSection.style.display = isTele ? 'block' : 'none';
      if (isTele) {
        if (tcPlatform) tcPlatform.value = p.teleconsult_platform || 'Google Meet';
        if (tcLink) tcLink.value = p.teleconsult_link || '';
        if (tcNotes) tcNotes.value = p.teleconsult_notes || '';
        toggleSaTeleconsultLinkField();
      }
    }

    refreshScheduleAssistOptions(preselectDoctorName || p.assigned_doctor || '');
    scheduleAssistModal.classList.add('open');
  };

  window.toggleSaTeleconsultLinkField = function () {
    const platform = document.getElementById('saTeleconsultPlatform')?.value || '';
    const linkWrap = document.getElementById('saTeleconsultLinkWrap');
    if (!linkWrap) return;
    const isPhoneOnly = platform === 'Phone / Viber Call';
    linkWrap.style.display = isPhoneOnly ? 'none' : '';
  };

  window.closeScheduleAssistModal = function () {
    if (scheduleAssistModal) scheduleAssistModal.classList.remove('open');
  };

  if (scheduleAssistModal) {
    scheduleAssistModal.addEventListener('click', (e) => {
      if (e.target === scheduleAssistModal) closeScheduleAssistModal();
    });
  }

  window.pickDeptDoctorBlock = function (doctorName, slotBlock) {
    const slotSelect = document.getElementById('saTimeSlot');
    if (slotSelect) {
      slotSelect.value = slotBlock === 'Afternoon'
        ? 'Afternoon (1:00 PM - 5:00 PM)'
        : 'Morning (8:00 AM - 12:00 PM)';
    }
    refreshScheduleAssistOptions(doctorName);
  };

  window.refreshScheduleAssistOptions = function (preferredDocName) {
    const patientId = document.getElementById('saPatientId')?.value || '';
    const p = (allPatients || []).find(item => item.patient_id === patientId);
    const selectedDate = document.getElementById('saApptDate')?.value || '';
    const slotVal = document.getElementById('saTimeSlot')?.value || '';
    const slotBlock = slotVal.toLowerCase().includes('afternoon') ? 'Afternoon' : 'Morning';

    const listEl = document.getElementById('saDeptAvailabilityList');
    const docSelect = document.getElementById('saDoctorSelect');
    const hintEl = document.getElementById('saDoctorSlotHint');

    const docs = getDoctorsForDept(p?.department);
    if (listEl) {
      if (!docs.length) {
        listEl.innerHTML = '<div style="font-size:0.8rem;color:#64748b;padding:6px 0;">No physicians configured for this department.</div>';
      } else {
        listEl.innerHTML = docs.map(d => {
          const cleanName = String(d.name || '').replace(/^Dr\.\s*/, '').trim();
          const mEval = evaluateDoctorSlotForPatient(d, selectedDate, 'Morning', patientId);
          const aEval = evaluateDoctorSlotForPatient(d, selectedDate, 'Afternoon', patientId);
          const isIn = isDocAvailable(d.status);

          const mBtn = mEval.coversMorning
            ? `<button type="button" class="sa-block-btn ${mEval.isAvailable ? 'is-open' : 'is-closed'}" onclick="pickDeptDoctorBlock('${escapeHtml(d.name)}', 'Morning')">Morning · ${mEval.isAvailable ? mEval.remainingSlots + ' slots' : mEval.shortReason}</button>`
            : '<span class="sa-block-none">No Morning</span>';

          const aBtn = aEval.coversAfternoon
            ? `<button type="button" class="sa-block-btn ${aEval.isAvailable ? 'is-open' : 'is-closed'}" onclick="pickDeptDoctorBlock('${escapeHtml(d.name)}', 'Afternoon')">Afternoon · ${aEval.isAvailable ? aEval.remainingSlots + ' slots' : aEval.shortReason}</button>`
            : '<span class="sa-block-none">No Afternoon</span>';

          return `
            <div class="sa-dept-doc-row">
              <div class="sa-dept-doc-info">
                <div class="sa-dept-doc-name">
                  <span>Dr. ${escapeHtml(cleanName)}</span>
                  <span class="sa-doc-status-badge ${isIn ? 'is-in' : 'is-out'}">${isIn ? 'Available' : 'Out'}</span>
                </div>
                <div class="sa-dept-doc-meta">${escapeHtml(d.specialization || 'General Practice')} &middot; ${escapeHtml(mEval.hoursFull)}</div>
              </div>
              <div class="sa-dept-doc-blocks">
                ${mBtn}
                ${aBtn}
              </div>
            </div>
          `;
        }).join('');
      }
    }

    if (docSelect) {
      const currentChoice = preferredDocName !== undefined ? preferredDocName : docSelect.value;
      const currentNorm = normalizeDoctorName(currentChoice);
      let firstAvailableVal = '';
      let matchedVal = '';

      const opts = ['<option value="">Select available physician...</option>'];
      docs.forEach(d => {
        const cleanName = String(d.name || '').replace(/^Dr\.\s*/, '').trim();
        const ev = evaluateDoctorSlotForPatient(d, selectedDate, slotBlock, patientId);
        if (ev.isAvailable && !firstAvailableVal) firstAvailableVal = d.name;
        if (currentNorm && currentNorm === normalizeDoctorName(d.name)) matchedVal = d.name;
        const suffix = ev.isAvailable ? `${ev.remainingSlots} slots available` : ev.shortReason;
        opts.push(`<option value="${escapeHtml(d.name)}" ${ev.isAvailable ? '' : 'disabled'}>Dr. ${escapeHtml(cleanName)} (${escapeHtml(ev.hoursCompact)} · ${escapeHtml(suffix)})</option>`);
      });
      docSelect.innerHTML = opts.join('');

      if (matchedVal) {
        const matchedOpt = Array.from(docSelect.options).find(o => o.value === matchedVal && !o.disabled);
        docSelect.value = matchedOpt ? matchedVal : (firstAvailableVal || '');
      } else {
        docSelect.value = firstAvailableVal || '';
      }

      if (hintEl) {
        const parsed = selectedDate ? new Date(selectedDate + 'T00:00:00') : null;
        if (parsed && !isNaN(parsed.getTime()) && parsed.getDay() === 0) {
          hintEl.style.color = '#b91c1c';
          hintEl.textContent = 'Sundays are closed. Please choose a Monday–Saturday date while speaking with the patient.';
        } else if (!firstAvailableVal) {
          hintEl.style.color = '#b45309';
          hintEl.textContent = `No physician is available for ${slotBlock} on ${selectedDate || 'this date'}. Pick another day or time block above.`;
        } else {
          hintEl.style.color = '#15803d';
          hintEl.textContent = `Available physician slots found for ${slotBlock} on ${selectedDate}.`;
        }
      }
    }
  };

  window.submitScheduleAssist = async function (approveNow) {
    const patientId = document.getElementById('saPatientId')?.value || '';
    const newDate = document.getElementById('saApptDate')?.value || '';
    const slotVal = document.getElementById('saTimeSlot')?.value || '';
    const doctorName = document.getElementById('saDoctorSelect')?.value || '';

    if (!patientId) return;
    if (!newDate) {
      showToast('Please select an appointment date.', 2800, 'error');
      return;
    }
    const todayNow = new Date();
    const todayStr = `${todayNow.getFullYear()}-${String(todayNow.getMonth() + 1).padStart(2, '0')}-${String(todayNow.getDate()).padStart(2, '0')}`;
    if (newDate < todayStr) {
      showToast('Appointment date cannot be in the past.', 3200, 'error');
      return;
    }
    const parsedDate = new Date(newDate + 'T00:00:00');
    if (!isNaN(parsedDate.getTime()) && parsedDate.getDay() === 0) {
      showToast('Clinics are closed on Sundays. Please pick a Monday to Saturday date.', 3200, 'error');
      return;
    }
    if (!doctorName) {
      showToast('Please select an available attending physician for that day and time block.', 3200, 'error');
      return;
    }

    const tcSection = document.getElementById('saTeleconsultSection');
    const isTeleVisible = tcSection && tcSection.style.display !== 'none';
    const tcPlatform = isTeleVisible ? (document.getElementById('saTeleconsultPlatform')?.value || '').trim() : '';
    const tcLinkRaw = isTeleVisible ? (document.getElementById('saTeleconsultLink')?.value || '').trim() : '';
    const tcLink = tcPlatform === 'Phone / Viber Call' ? '' : tcLinkRaw;
    const tcNotes = isTeleVisible ? (document.getElementById('saTeleconsultNotes')?.value || '').trim() : '';
    if (isTeleVisible && tcLink && !/^https?:\/\/\S+$/i.test(tcLink)) {
      showToast('Please enter a valid https:// teleconsultation meeting link.', 3200, 'error');
      return;
    }

    const btnSave = document.getElementById('btnSaSaveOnly');
    const btnApprove = document.getElementById('btnSaSaveApprove');
    if (btnSave) btnSave.disabled = true;
    if (btnApprove) btnApprove.disabled = true;

    try {
      const reqPayload = {
        action: 'reschedule_assign',
        patient_id: patientId,
        doctor: doctorName,
        appointment_date: newDate,
        preferred_time_slot: slotVal,
        approve_now: Boolean(approveNow)
      };
      if (isTeleVisible) {
        reqPayload.teleconsult_platform = tcPlatform;
        reqPayload.teleconsult_link = tcLink;
        reqPayload.teleconsult_notes = tcNotes;
      }
      const res = await fetch('/sdn/api/patients.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(reqPayload)
      }).then(r => r.json());

      if (res && res.success) {
        showToast(res.message || 'Schedule and physician updated.');
        closeScheduleAssistModal();
        await loadData();
        const updatedP = allPatients.find(x => x.patient_id === patientId);
        if (updatedP && window.PatientSlip && window.PatientSlip.canGenerateSlip(updatedP)) {
          window.PatientSlip.openModal({
            slipType: window.PatientSlip.resolveSlipType(updatedP),
            patientId: updatedP.patient_id || patientId || '',
            patientName: updatedP.full_name || '',
            patientType: updatedP.patient_type || 'Old',
            appointmentDate: res.appointment_date || newDate,
            appointmentTime: res.preferred_time_slot || slotVal || '',
            specialty: updatedP.department || window.STAFF_DEPT || '',
            consultType: updatedP.consultation_nature || updatedP.consultation_mode || 'Face-to-Face',
            consultationMode: updatedP.consultation_mode || 'Face-to-Face',
            teleconsultPlatform: updatedP.teleconsult_platform || '',
            teleconsultLink: updatedP.teleconsult_link || '',
            teleconsultNotes: updatedP.teleconsult_notes || '',
            encodedBy: window.STAFF_FIRST_NAME || 'Medical Staff'
          });
        }
      } else {
        showToast(res?.message || 'Could not update schedule.', 3200, 'error');
      }
    } catch (e) {
      showToast('Network error updating schedule.', 2800, 'error');
    } finally {
      if (btnSave) btnSave.disabled = false;
      if (btnApprove) btnApprove.disabled = false;
    }
  };

  // ── DOH DPRI 2026 Formulary Lists by Department ──
  const DOH_MEDICINES = {
    general: [
      { name: "AMOXICILLIN 500 mg capsule (as trihydrate)", defaultDose: "1 cap TID x 7 days", defaultQty: "21 caps" },
      { name: "CO-AMOXICLAV 500 mg amoxicillin + 125 mg potassium clavulanate tablet", defaultDose: "1 tab BID after meals x 7 days", defaultQty: "14 tabs" },
      { name: "CEFALEXIN (CEPHALEXIN) 500 mg capsule (as monohydrate)", defaultDose: "1 cap QID x 7 days", defaultQty: "28 caps" },
      { name: "CEFUROXIME 500 mg tablet (as axetil)", defaultDose: "1 tab BID x 7 days", defaultQty: "14 tabs" },
      { name: "AZITHROMYCIN 500 mg tablet", defaultDose: "1 tab OD 1 hr before meals x 3-5 days", defaultQty: "3-5 tabs" },
      { name: "CIPROFLOXACIN 500 mg tablet (as hydrochloride)", defaultDose: "1 tab BID x 7 days", defaultQty: "14 tabs" },
      { name: "PARACETAMOL 500 mg tablet", defaultDose: "1-2 tabs q4-6h PRN for fever/pain", defaultQty: "20 tabs" },
      { name: "IBUPROFEN 400 mg tablet", defaultDose: "1 tab TID after meals PRN pain", defaultQty: "15 tabs" },
      { name: "MEFENAMIC ACID 500 mg capsule", defaultDose: "1 cap TID after meals PRN pain", defaultQty: "15 caps" },
      { name: "TRAMADOL 50 mg capsule (as hydrochloride)", defaultDose: "1 cap BID-TID PRN moderate-severe pain", defaultQty: "10 caps" },
      { name: "AMLODIPINE 5 mg tablet (as besilate / camsylate)", defaultDose: "1 tab OD in the morning", defaultQty: "30 tabs" },
      { name: "AMLODIPINE 10 mg tablet (as besilate / camsylate)", defaultDose: "1 tab OD in the morning", defaultQty: "30 tabs" },
      { name: "LOSARTAN 50 mg tablet (as potassium salt)", defaultDose: "1 tab OD in the morning", defaultQty: "30 tabs" },
      { name: "LOSARTAN 100 mg tablet (as potassium salt)", defaultDose: "1 tab OD in the morning", defaultQty: "30 tabs" },
      { name: "METFORMIN 500 mg tablet (as hydrochloride)", defaultDose: "1 tab BID with or after meals", defaultQty: "60 tabs" },
      { name: "METFORMIN 850 mg tablet (as hydrochloride)", defaultDose: "1 tab OD-BID with meals", defaultQty: "30-60 tabs" },
      { name: "GLICLAZIDE 30 mg MR tablet", defaultDose: "1-2 tabs OD with breakfast", defaultQty: "30 tabs" },
      { name: "ATORVASTATIN 20 mg tablet (as calcium)", defaultDose: "1 tab OD at bedtime", defaultQty: "30 tabs" },
      { name: "ROSUVASTATIN 10 mg tablet (as calcium salt)", defaultDose: "1 tab OD at bedtime", defaultQty: "30 tabs" },
      { name: "OMEPRAZOLE 20 mg capsule", defaultDose: "1 cap OD 30 mins before breakfast", defaultQty: "14-30 caps" },
      { name: "OMEPRAZOLE 40 mg capsule", defaultDose: "1 cap OD 30 mins before breakfast", defaultQty: "14-30 caps" },
      { name: "PANTOPRAZOLE 40 mg Enteric coated tablet", defaultDose: "1 tab OD before breakfast", defaultQty: "14-30 tabs" },
      { name: "SALBUTAMOL 2 mg/5 mL syrup, 60 mL", defaultDose: "5-10 mL TID-QID PRN", defaultQty: "1 bottle" },
      { name: "SALBUTAMOL MDI: 100 mcg per dose x 200 doses", defaultDose: "1-2 puffs q4-6h PRN bronchospasm", defaultQty: "1 canister" },
      { name: "CETIRIZINE 10 mg tablet (as dihydrochloride)", defaultDose: "1 tab OD at bedtime", defaultQty: "10-20 tabs" },
      { name: "METOCLOPRAMIDE 10 mg tablet", defaultDose: "1 tab TID 30 mins before meals", defaultQty: "10 tabs" },
      { name: "HYOSCINE 10 mg tablet", defaultDose: "1-2 tabs TID-QID PRN abdominal cramps", defaultQty: "12 tabs" },
      { name: "ORAL REHYDRATION SALT (ORS 75) sachet", defaultDose: "Dissolve 1 sachet in 1L clean water, drink as needed", defaultQty: "5 sachets" },
      { name: "ASCORBIC ACID (VITAMIN C) 500 mg tablet", defaultDose: "1 tab OD daily", defaultQty: "30 tabs" },
      { name: "MULTIVITAMINS, Capsule", defaultDose: "1 cap OD after meals", defaultQty: "30 caps" }
    ],
    neurology: [
      { name: "GABAPENTIN 100 mg capsule", defaultDose: "1 cap TID, titrate as directed", defaultQty: "30-60 caps" },
      { name: "GABAPENTIN 300 mg capsule", defaultDose: "1 cap TID for neuropathic pain", defaultQty: "30-90 caps" },
      { name: "PREGABALIN 75 mg capsule", defaultDose: "1 cap BID at bedtime", defaultQty: "30 caps" },
      { name: "CARBAMAZEPINE 200 mg tablet", defaultDose: "1 tab BID, titrate as directed", defaultQty: "60 tabs" },
      { name: "VALPROATE DISODIUM 500 mg tablet", defaultDose: "1 tab BID with meals", defaultQty: "60 tabs" },
      { name: "VALPROATE DISODIUM 250 mg tablet", defaultDose: "1 tab BID with meals", defaultQty: "60 tabs" },
      { name: "VALPROIC ACID 500 mg extended release tablet (as divalproex sodium)", defaultDose: "1 tab BID with meals", defaultQty: "60 tabs" },
      { name: "LEVETIRACETAM 500 mg Film-coated tablet", defaultDose: "1 tab BID for seizure prophylaxis", defaultQty: "60 tabs" },
      { name: "LEVETIRACETAM 1 g Film-coated tablet", defaultDose: "1 tab BID as directed", defaultQty: "60 tabs" },
      { name: "LAMOTRIGINE 50 mg tablet", defaultDose: "1 tab OD-BID as titrated", defaultQty: "30-60 tabs" },
      { name: "LAMOTRIGINE 100 mg tablet", defaultDose: "1 tab BID as directed", defaultQty: "60 tabs" },
      { name: "PHENYTOIN 100 mg capsule (as sodium salt)", defaultDose: "1 cap TID or 300mg at bedtime", defaultQty: "90 caps" },
      { name: "TOPIRAMATE 25 mg Tablet", defaultDose: "1 tab OD at bedtime x 1 week then BID", defaultQty: "30 tabs" },
      { name: "TOPIRAMATE 50 mg Tablet", defaultDose: "1 tab BID as directed", defaultQty: "60 tabs" },
      { name: "LEVODOPA + CARBIDOPA 100 mg / 25 mg tablet", defaultDose: "1 tab TID 30 mins before meals", defaultQty: "90 tabs" },
      { name: "LEVODOPA + CARBIDOPA 250 mg / 25 mg tablet", defaultDose: "1 tab TID-QID as directed", defaultQty: "90 tabs" },
      { name: "DONEPEZIL 5 mg Tablet", defaultDose: "1 tab OD at bedtime", defaultQty: "30 tabs" },
      { name: "DONEPEZIL 10 mg Tablet", defaultDose: "1 tab OD at bedtime", defaultQty: "30 tabs" },
      { name: "MEMANTINE 10 mg Film coated tablet", defaultDose: "1 tab BID with or without food", defaultQty: "60 tabs" },
      { name: "BIPERIDEN 2 mg tablet (as hydrochloride)", defaultDose: "1 tab BID for extrapyramidal symptoms", defaultQty: "30-60 tabs" },
      { name: "BETAHISTINE 16 mg Tablet", defaultDose: "1 tab TID with food", defaultQty: "30 tabs" },
      { name: "BETAHISTINE 24 mg Tablet", defaultDose: "1 tab BID with meals", defaultQty: "30 tabs" },
      { name: "CINNARIZINE 25 mg Tablet", defaultDose: "1 tab TID after meals for vertigo", defaultQty: "30 tabs" },
      { name: "BACLOFEN 10 mg tablet", defaultDose: "1 tab TID with meals for spasticity", defaultQty: "30 tabs" },
      { name: "EPERISONE 50 mg tablet (as hydrochloride)", defaultDose: "1 tab TID after meals", defaultQty: "21-30 tabs" },
      { name: "CLONAZEPAM 2 mg tablet", defaultDose: "0.5mg-1mg at bedtime as directed", defaultQty: "30 tabs" },
      { name: "CLOPIDOGREL 75 mg tablet", defaultDose: "1 tab OD daily after meals", defaultQty: "30 tabs" },
      { name: "ASPIRIN 80 mg / 100 mg tablet", defaultDose: "1 tab OD after lunch", defaultQty: "30 tabs" },
      { name: "CILOSTAZOL 100 mg tablet", defaultDose: "1 tab BID 30 mins before breakfast/dinner", defaultQty: "60 tabs" },
      { name: "CITICOLINE 500 mg tablet", defaultDose: "1 tab BID after meals", defaultQty: "60 tabs" },
      { name: "MECOBALAMIN 500 mcg tablet", defaultDose: "1 tab TID for peripheral neuropathy", defaultQty: "90 tabs" },
      { name: "VITAMIN B1 B6 B12 tablet", defaultDose: "1 tab OD-BID daily", defaultQty: "30 tabs" }
    ],
    psychiatry: [
      { name: "RISPERIDONE 1 mg tablet", defaultDose: "1 tab OD at bedtime, titrate as directed", defaultQty: "30 tabs" },
      { name: "RISPERIDONE 2 mg tablet", defaultDose: "1 tab OD at bedtime or BID", defaultQty: "30-60 tabs" },
      { name: "OLANZAPINE 5 mg tablet", defaultDose: "1 tab OD at bedtime", defaultQty: "30 tabs" },
      { name: "OLANZAPINE 10 mg tablet", defaultDose: "1 tab OD at bedtime", defaultQty: "30 tabs" },
      { name: "QUETIAPINE 25 mg tablet (as fumarate)", defaultDose: "1 tab at bedtime for sleep/anxiety", defaultQty: "30 tabs" },
      { name: "QUETIAPINE 100 mg tablet (as fumarate)", defaultDose: "1 tab BID or at bedtime", defaultQty: "60 tabs" },
      { name: "QUETIAPINE 200 mg tablet (as fumarate)", defaultDose: "1-2 tabs at bedtime as directed", defaultQty: "30-60 tabs" },
      { name: "ARIPIPRAZOLE 10 mg tablet", defaultDose: "1 tab OD in the morning with food", defaultQty: "30 tabs" },
      { name: "ARIPIPRAZOLE 15 mg tablet", defaultDose: "1 tab OD in the morning", defaultQty: "30 tabs" },
      { name: "CLOZAPINE 25 mg tablet", defaultDose: "Initial 25-50mg at bedtime, titrate with CBC monitoring", defaultQty: "30 tabs" },
      { name: "CLOZAPINE 100 mg tablet", defaultDose: "As directed, strict CBC monitoring", defaultQty: "60 tabs" },
      { name: "HALOPERIDOL 5 mg tablet", defaultDose: "1 tab OD-BID as directed", defaultQty: "30-60 tabs" },
      { name: "CHLORPROMAZINE 100 mg tablet (as hydrochloride)", defaultDose: "1 tab at bedtime or BID", defaultQty: "30-60 tabs" },
      { name: "FLUOXETINE 20 mg dispersible capsule", defaultDose: "1 cap OD in the morning after breakfast", defaultQty: "30 caps" },
      { name: "SERTRALINE 50 mg tablet (as Hydrochloride)", defaultDose: "1 tab OD in the morning or bedtime", defaultQty: "30 tabs" },
      { name: "ESCITALOPRAM 10 mg tablet (as Oxalate)", defaultDose: "1 tab OD morning or bedtime", defaultQty: "30 tabs" },
      { name: "LITHIUM CARBONATE 450 mg MR tablet", defaultDose: "1 tab BID with meals, monitor serum level", defaultQty: "60 tabs" },
      { name: "VALPROATE DISODIUM 500 mg tablet", defaultDose: "1 tab BID with meals (bipolar / mood stabilization)", defaultQty: "60 tabs" },
      { name: "VALPROATE DISODIUM 250 mg tablet", defaultDose: "1 tab BID with meals", defaultQty: "60 tabs" },
      { name: "VALPROIC ACID 500 mg controlled release tablet", defaultDose: "1 tab BID with meals (bipolar stabilization)", defaultQty: "60 tabs" },
      { name: "LAMOTRIGINE 50 mg tablet", defaultDose: "1 tab OD-BID (bipolar depression)", defaultQty: "30-60 tabs" },
      { name: "CLONAZEPAM 2 mg tablet", defaultDose: "0.5mg-1mg at bedtime PRN panic/anxiety", defaultQty: "30 tabs" },
      { name: "ALPRAZOLAM 250 mcg tablet", defaultDose: "1 tab BID-TID PRN acute anxiety", defaultQty: "20-30 tabs" },
      { name: "ALPRAZOLAM 500 mcg tablet", defaultDose: "1 tab BID-TID PRN acute panic/anxiety", defaultQty: "20-30 tabs" },
      { name: "DIAZEPAM 5 mg tablet", defaultDose: "1 tab OD at bedtime PRN acute agitation", defaultQty: "10-20 tabs" },
      { name: "BIPERIDEN 2 mg tablet (as hydrochloride)", defaultDose: "1 tab BID for drug-induced parkinsonism", defaultQty: "30-60 tabs" },
      { name: "DIPHENHYDRAMINE 50 mg capsule", defaultDose: "1 cap at bedtime PRN insomnia/EPS", defaultQty: "14 caps" },
      { name: "ZOLPIDEM 10 mg tablet", defaultDose: "1 tab at bedtime PRN severe insomnia", defaultQty: "14 tabs" }
    ]
  };

  const CLINICAL_DIAGNOSIS_GUIDELINES = [
    // General Medical
    {
      diagnosis: 'Essential Hypertension',
      department: 'General Medical',
      treatment_plan: 'Low-sodium DASH diet, daily home blood pressure monitoring, stress reduction, regular aerobic exercise, and follow-up BP recheck after 2-4 weeks.',
      medicine_name: 'LOSARTAN 50 mg tablet (as potassium salt)',
      dosage_instructions: '1 tab OD in the morning',
      default_quantity: '30 tabs',
      suggested_medicines: [
        { name: 'LOSARTAN 50 mg tablet (as potassium salt)', dosage: '1 tab OD in the morning', quantity: '30 tabs' },
        { name: 'AMLODIPINE 5 mg tablet (as besilate / camsylate)', dosage: '1 tab OD in the morning', quantity: '30 tabs' },
        { name: 'LOSARTAN 100 mg tablet (as potassium salt)', dosage: '1 tab OD in the morning', quantity: '30 tabs' }
      ]
    },
    {
      diagnosis: 'Stage 2 Hypertension',
      department: 'General Medical',
      treatment_plan: 'Strict low-salt diet, daily BP log, cardiovascular risk screening, and outpatient BP review after 2 weeks.',
      medicine_name: 'AMLODIPINE 10 mg tablet (as besilate / camsylate)',
      dosage_instructions: '1 tab OD in the morning',
      default_quantity: '30 tabs',
      suggested_medicines: [
        { name: 'AMLODIPINE 10 mg tablet (as besilate / camsylate)', dosage: '1 tab OD in the morning', quantity: '30 tabs' },
        { name: 'LOSARTAN 100 mg tablet (as potassium salt)', dosage: '1 tab OD in the morning', quantity: '30 tabs' },
        { name: 'AMLODIPINE 5 mg tablet (as besilate / camsylate)', dosage: '1 tab OD in the morning', quantity: '30 tabs' }
      ]
    },
    {
      diagnosis: 'Type 2 Diabetes Mellitus',
      department: 'General Medical',
      treatment_plan: 'Medical nutrition therapy (low glycemic index diet), 150 mins/week moderate exercise, foot care education, and fasting blood sugar / HbA1c monitoring in 3 months.',
      medicine_name: 'METFORMIN 500 mg tablet (as hydrochloride)',
      dosage_instructions: '1 tab BID with or after meals',
      default_quantity: '60 tabs',
      suggested_medicines: [
        { name: 'METFORMIN 500 mg tablet (as hydrochloride)', dosage: '1 tab BID with or after meals', quantity: '60 tabs' },
        { name: 'METFORMIN 850 mg tablet (as hydrochloride)', dosage: '1 tab OD-BID with meals', quantity: '30-60 tabs' },
        { name: 'GLICLAZIDE 30 mg MR tablet', dosage: '1-2 tabs OD with breakfast', quantity: '30 tabs' }
      ]
    },
    {
      diagnosis: 'Acute Upper Respiratory Tract Infection (URTI)',
      department: 'General Medical',
      treatment_plan: 'Adequate oral hydration, warm saline gargle, rest, fever/pain control, and return precautions for persistent fever >3 days or shortness of breath.',
      medicine_name: 'PARACETAMOL 500 mg tablet',
      dosage_instructions: '1-2 tabs q4-6h PRN for fever/pain',
      default_quantity: '20 tabs',
      suggested_medicines: [
        { name: 'PARACETAMOL 500 mg tablet', dosage: '1-2 tabs q4-6h PRN for fever/pain', quantity: '20 tabs' },
        { name: 'CETIRIZINE 10 mg tablet (as dihydrochloride)', dosage: '1 tab OD at bedtime', quantity: '10-20 tabs' },
        { name: 'ASCORBIC ACID (VITAMIN C) 500 mg tablet', dosage: '1 tab OD daily', quantity: '30 tabs' }
      ]
    },
    {
      diagnosis: 'Community-Acquired Pneumonia (Mild-Moderate)',
      department: 'General Medical',
      treatment_plan: 'Complete full antibiotic course, increase oral fluid intake, rest, monitor temperature and respiratory status; return immediately for dyspnea.',
      medicine_name: 'AMOXICILLIN 500 mg capsule (as trihydrate)',
      dosage_instructions: '1 cap TID x 7 days',
      default_quantity: '21 caps',
      suggested_medicines: [
        { name: 'AMOXICILLIN 500 mg capsule (as trihydrate)', dosage: '1 cap TID x 7 days', quantity: '21 caps' },
        { name: 'CO-AMOXICLAV 500 mg amoxicillin + 125 mg potassium clavulanate tablet', dosage: '1 tab BID after meals x 7 days', quantity: '14 tabs' },
        { name: 'AZITHROMYCIN 500 mg tablet', dosage: '1 tab OD 1 hr before meals x 3-5 days', quantity: '3-5 tabs' },
        { name: 'PARACETAMOL 500 mg tablet', dosage: '1-2 tabs q4-6h PRN for fever/pain', quantity: '20 tabs' }
      ]
    },
    {
      diagnosis: 'Acute Bronchitis / Bronchial Asthma',
      department: 'General Medical',
      treatment_plan: 'Avoid airway triggers and smoke exposure, oral hydration, bronchodilator relief as needed, and return if wheezing worsens.',
      medicine_name: 'SALBUTAMOL MDI: 100 mcg per dose x 200 doses',
      dosage_instructions: '1-2 puffs q4-6h PRN bronchospasm',
      default_quantity: '1 canister',
      suggested_medicines: [
        { name: 'SALBUTAMOL MDI: 100 mcg per dose x 200 doses', dosage: '1-2 puffs q4-6h PRN bronchospasm', quantity: '1 canister' },
        { name: 'SALBUTAMOL 2 mg/5 mL syrup, 60 mL', dosage: '5-10 mL TID-QID PRN', quantity: '1 bottle' },
        { name: 'CETIRIZINE 10 mg tablet (as dihydrochloride)', dosage: '1 tab OD at bedtime', quantity: '10-20 tabs' }
      ]
    },
    {
      diagnosis: 'Acute Gastroenteritis',
      department: 'General Medical',
      treatment_plan: 'Aggressive oral rehydration solution after every loose stool, bland BRAT diet, hand hygiene education, and red-flag precautions for dehydration.',
      medicine_name: 'ORAL REHYDRATION SALT (ORS 75) sachet',
      dosage_instructions: 'Dissolve 1 sachet in 1L clean water, drink as needed',
      default_quantity: '5 sachets',
      suggested_medicines: [
        { name: 'ORAL REHYDRATION SALT (ORS 75) sachet', dosage: 'Dissolve 1 sachet in 1L clean water, drink as needed', quantity: '5 sachets' },
        { name: 'HYOSCINE 10 mg tablet', dosage: '1-2 tabs TID-QID PRN abdominal cramps', quantity: '12 tabs' },
        { name: 'METOCLOPRAMIDE 10 mg tablet', dosage: '1 tab TID 30 mins before meals', quantity: '10 tabs' }
      ]
    },
    {
      diagnosis: 'Uncomplicated Urinary Tract Infection (UTI)',
      department: 'General Medical',
      treatment_plan: 'Complete full antimicrobial regimen, increase water intake (2-3L/day), avoid holding urine, and follow-up if dysuria persists.',
      medicine_name: 'CIPROFLOXACIN 500 mg tablet (as hydrochloride)',
      dosage_instructions: '1 tab BID x 7 days',
      default_quantity: '14 tabs',
      suggested_medicines: [
        { name: 'CIPROFLOXACIN 500 mg tablet (as hydrochloride)', dosage: '1 tab BID x 7 days', quantity: '14 tabs' },
        { name: 'CEFUROXIME 500 mg tablet (as axetil)', dosage: '1 tab BID x 7 days', quantity: '14 tabs' },
        { name: 'CEFALEXIN (CEPHALEXIN) 500 mg capsule (as monohydrate)', dosage: '1 cap QID x 7 days', quantity: '28 caps' }
      ]
    },
    {
      diagnosis: 'Gastroesophageal Reflux Disease (GERD) / Gastritis',
      department: 'General Medical',
      treatment_plan: 'Avoid spicy, fatty, acidic foods, coffee, and late-night meals; elevate head of bed and avoid lying down within 3 hours after eating.',
      medicine_name: 'OMEPRAZOLE 20 mg capsule',
      dosage_instructions: '1 cap OD 30 mins before breakfast',
      default_quantity: '14-30 caps',
      suggested_medicines: [
        { name: 'OMEPRAZOLE 20 mg capsule', dosage: '1 cap OD 30 mins before breakfast', quantity: '14-30 caps' },
        { name: 'OMEPRAZOLE 40 mg capsule', dosage: '1 cap OD 30 mins before breakfast', quantity: '14-30 caps' },
        { name: 'PANTOPRAZOLE 40 mg Enteric coated tablet', dosage: '1 tab OD before breakfast', quantity: '14-30 tabs' }
      ]
    },
    {
      diagnosis: 'Allergic Rhinitis',
      department: 'General Medical',
      treatment_plan: 'Allergen and dust avoidance, saline nasal hygiene, adequate hydration, and bedtime antihistamine therapy.',
      medicine_name: 'CETIRIZINE 10 mg tablet (as dihydrochloride)',
      dosage_instructions: '1 tab OD at bedtime',
      default_quantity: '10-20 tabs',
      suggested_medicines: [
        { name: 'CETIRIZINE 10 mg tablet (as dihydrochloride)', dosage: '1 tab OD at bedtime', quantity: '10-20 tabs' },
        { name: 'ASCORBIC ACID (VITAMIN C) 500 mg tablet', dosage: '1 tab OD daily', quantity: '30 tabs' }
      ]
    },
    {
      diagnosis: 'Dyslipidemia',
      department: 'General Medical',
      treatment_plan: 'Low-saturated-fat high-fiber diet, regular aerobic exercise, tobacco cessation, and repeat lipid profile after 8-12 weeks.',
      medicine_name: 'ATORVASTATIN 20 mg tablet (as calcium)',
      dosage_instructions: '1 tab OD at bedtime',
      default_quantity: '30 tabs',
      suggested_medicines: [
        { name: 'ATORVASTATIN 20 mg tablet (as calcium)', dosage: '1 tab OD at bedtime', quantity: '30 tabs' },
        { name: 'ROSUVASTATIN 10 mg tablet (as calcium salt)', dosage: '1 tab OD at bedtime', quantity: '30 tabs' }
      ]
    },
    {
      diagnosis: 'Musculoskeletal Strain / Osteoarthritis',
      department: 'General Medical',
      treatment_plan: 'Rest affected joint/muscle, warm/cold compress therapy, gentle stretching, and short-term analgesic after meals.',
      medicine_name: 'IBUPROFEN 400 mg tablet',
      dosage_instructions: '1 tab TID after meals PRN pain',
      default_quantity: '15 tabs',
      suggested_medicines: [
        { name: 'IBUPROFEN 400 mg tablet', dosage: '1 tab TID after meals PRN pain', quantity: '15 tabs' },
        { name: 'MEFENAMIC ACID 500 mg capsule', dosage: '1 cap TID after meals PRN pain', quantity: '15 caps' },
        { name: 'PARACETAMOL 500 mg tablet', dosage: '1-2 tabs q4-6h PRN for fever/pain', quantity: '20 tabs' }
      ]
    },

    // Neurology
    {
      diagnosis: 'Migraine without Aura',
      department: 'Neurology',
      treatment_plan: 'Maintain headache diary, identify and avoid triggers (sleep deprivation, dehydration, bright glare), adequate hydration, and prophylactic/abortive regimen.',
      medicine_name: 'TOPIRAMATE 25 mg Tablet',
      dosage_instructions: '1 tab OD at bedtime x 1 week then BID',
      default_quantity: '30 tabs',
      suggested_medicines: [
        { name: 'TOPIRAMATE 25 mg Tablet', dosage: '1 tab OD at bedtime x 1 week then BID', quantity: '30 tabs' },
        { name: 'TOPIRAMATE 50 mg Tablet', dosage: '1 tab BID as directed', quantity: '60 tabs' },
        { name: 'PARACETAMOL 500 mg tablet', dosage: '1-2 tabs q4-6h PRN for fever/pain', quantity: '20 tabs' }
      ]
    },
    {
      diagnosis: 'Tension-Type Headache',
      department: 'Neurology',
      treatment_plan: 'Stress management, ergonomic posture correction, adequate sleep hygiene, hydration, and non-narcotic acute pain relief.',
      medicine_name: 'PARACETAMOL 500 mg tablet',
      dosage_instructions: '1-2 tabs q4-6h PRN for fever/pain',
      default_quantity: '20 tabs',
      suggested_medicines: [
        { name: 'PARACETAMOL 500 mg tablet', dosage: '1-2 tabs q4-6h PRN for fever/pain', quantity: '20 tabs' },
        { name: 'IBUPROFEN 400 mg tablet', dosage: '1 tab TID after meals PRN pain', quantity: '15 tabs' },
        { name: 'EPERISONE 50 mg tablet (as hydrochloride)', dosage: '1 tab TID after meals', quantity: '21-30 tabs' }
      ]
    },
    {
      diagnosis: 'Epilepsy / Seizure Disorder',
      department: 'Neurology',
      treatment_plan: 'Strict anti-epileptic medication adherence without missed doses, seizure precautions (avoid swimming alone/heights/driving), sleep hygiene, and neuro follow-up.',
      medicine_name: 'LEVETIRACETAM 500 mg Film-coated tablet',
      dosage_instructions: '1 tab BID for seizure prophylaxis',
      default_quantity: '60 tabs',
      suggested_medicines: [
        { name: 'LEVETIRACETAM 500 mg Film-coated tablet', dosage: '1 tab BID for seizure prophylaxis', quantity: '60 tabs' },
        { name: 'VALPROIC ACID 500 mg extended release tablet (as divalproex sodium)', dosage: '1 tab BID with meals', quantity: '60 tabs' },
        { name: 'CARBAMAZEPINE 200 mg tablet', dosage: '1 tab BID, titrate as directed', quantity: '60 tabs' },
        { name: 'LAMOTRIGINE 50 mg tablet', dosage: '1 tab OD-BID as titrated', quantity: '30-60 tabs' }
      ]
    },
    {
      diagnosis: 'Peripheral Neuropathy',
      department: 'Neurology',
      treatment_plan: 'Nerve protection care, glycemic/vitamin optimization, daily foot inspection for sensory loss, and neuropathic agent titration.',
      medicine_name: 'GABAPENTIN 300 mg capsule',
      dosage_instructions: '1 cap TID for neuropathic pain',
      default_quantity: '30-90 caps',
      suggested_medicines: [
        { name: 'GABAPENTIN 300 mg capsule', dosage: '1 cap TID for neuropathic pain', quantity: '30-90 caps' },
        { name: 'PREGABALIN 75 mg capsule', dosage: '1 cap BID at bedtime', quantity: '30 caps' },
        { name: 'MECOBALAMIN 500 mcg tablet', dosage: '1 tab TID for peripheral neuropathy', quantity: '90 tabs' },
        { name: 'VITAMIN B1 B6 B12 tablet', dosage: '1 tab OD-BID daily', quantity: '30 tabs' }
      ]
    },
    {
      diagnosis: 'Trigeminal / Neuropathic Neuralgia',
      department: 'Neurology',
      treatment_plan: 'Avoid facial cold/touch triggers, adhere strictly to neuropathic stabilizer schedule, and review liver/hematologic labs on follow-up.',
      medicine_name: 'CARBAMAZEPINE 200 mg tablet',
      dosage_instructions: '1 tab BID, titrate as directed',
      default_quantity: '60 tabs',
      suggested_medicines: [
        { name: 'CARBAMAZEPINE 200 mg tablet', dosage: '1 tab BID, titrate as directed', quantity: '60 tabs' },
        { name: 'GABAPENTIN 100 mg capsule', dosage: '1 cap TID, titrate as directed', quantity: '30-60 caps' },
        { name: 'PREGABALIN 75 mg capsule', dosage: '1 cap BID at bedtime', quantity: '30 caps' }
      ]
    },
    {
      diagnosis: 'Post-Ischemic Stroke (CVA) Secondary Prevention',
      department: 'Neurology',
      treatment_plan: 'Strict blood pressure and lipid control, daily antiplatelet adherence, physical/occupational rehabilitation, and aspiration/fall precautions.',
      medicine_name: 'CLOPIDOGREL 75 mg tablet',
      dosage_instructions: '1 tab OD daily after meals',
      default_quantity: '30 tabs',
      suggested_medicines: [
        { name: 'CLOPIDOGREL 75 mg tablet', dosage: '1 tab OD daily after meals', quantity: '30 tabs' },
        { name: 'ASPIRIN 80 mg / 100 mg tablet', dosage: '1 tab OD after lunch', quantity: '30 tabs' },
        { name: 'CITICOLINE 500 mg tablet', dosage: '1 tab BID after meals', quantity: '60 tabs' }
      ]
    },
    {
      diagnosis: 'Benign Paroxysmal Positional Vertigo (BPPV)',
      department: 'Neurology',
      treatment_plan: 'Slow head position changes, fall precautions, vestibular habituation (Brandt-Daroff/Epley maneuvers), and short-term vestibular suppressant.',
      medicine_name: 'BETAHISTINE 16 mg Tablet',
      dosage_instructions: '1 tab TID with food',
      default_quantity: '30 tabs',
      suggested_medicines: [
        { name: 'BETAHISTINE 16 mg Tablet', dosage: '1 tab TID with food', quantity: '30 tabs' },
        { name: 'BETAHISTINE 24 mg Tablet', dosage: '1 tab BID with meals', quantity: '30 tabs' },
        { name: 'CINNARIZINE 25 mg Tablet', dosage: '1 tab TID after meals for vertigo', quantity: '30 tabs' }
      ]
    },
    {
      diagnosis: "Parkinson's Disease",
      department: 'Neurology',
      treatment_plan: 'Administer dopaminergic therapy 30 minutes before meals for optimal absorption, physical therapy gait/balance training, and fall prevention.',
      medicine_name: 'LEVODOPA + CARBIDOPA 100 mg / 25 mg tablet',
      dosage_instructions: '1 tab TID 30 mins before meals',
      default_quantity: '90 tabs',
      suggested_medicines: [
        { name: 'LEVODOPA + CARBIDOPA 100 mg / 25 mg tablet', dosage: '1 tab TID 30 mins before meals', quantity: '90 tabs' },
        { name: 'LEVODOPA + CARBIDOPA 250 mg / 25 mg tablet', dosage: '1 tab TID-QID as directed', quantity: '90 tabs' },
        { name: 'BIPERIDEN 2 mg tablet (as hydrochloride)', dosage: '1 tab BID for extrapyramidal symptoms', quantity: '30-60 tabs' }
      ]
    },
    {
      diagnosis: "Alzheimer's Disease / Mild Cognitive Impairment",
      department: 'Neurology',
      treatment_plan: 'Structured daily cognitive routine, caregiver supervision for medications and safety, hydration, and neurocognitive follow-up.',
      medicine_name: 'DONEPEZIL 5 mg Tablet',
      dosage_instructions: '1 tab OD at bedtime',
      default_quantity: '30 tabs',
      suggested_medicines: [
        { name: 'DONEPEZIL 5 mg Tablet', dosage: '1 tab OD at bedtime', quantity: '30 tabs' },
        { name: 'DONEPEZIL 10 mg Tablet', dosage: '1 tab OD at bedtime', quantity: '30 tabs' },
        { name: 'MEMANTINE 10 mg Film coated tablet', dosage: '1 tab BID with or without food', quantity: '60 tabs' }
      ]
    },

    // OPU Psychiatry
    {
      diagnosis: 'Generalized Anxiety Disorder (GAD)',
      department: 'OPU Psychiatry',
      treatment_plan: 'Cognitive behavioral coping techniques, structured sleep and breathing hygiene, caffeine reduction, and daily SSRI maintenance with 4-week reassessment.',
      medicine_name: 'ESCITALOPRAM 10 mg tablet (as Oxalate)',
      dosage_instructions: '1 tab OD morning or bedtime',
      default_quantity: '30 tabs',
      suggested_medicines: [
        { name: 'ESCITALOPRAM 10 mg tablet (as Oxalate)', dosage: '1 tab OD morning or bedtime', quantity: '30 tabs' },
        { name: 'SERTRALINE 50 mg tablet (as Hydrochloride)', dosage: '1 tab OD in the morning or bedtime', quantity: '30 tabs' },
        { name: 'ALPRAZOLAM 250 mcg tablet', dosage: '1 tab BID-TID PRN acute anxiety', quantity: '20-30 tabs' }
      ]
    },
    {
      diagnosis: 'Major Depressive Disorder (MDD)',
      department: 'OPU Psychiatry',
      treatment_plan: 'Supportive psychotherapy, behavioral activation and daily routine building, suicide safety check, and continuous SSRI adherence.',
      medicine_name: 'SERTRALINE 50 mg tablet (as Hydrochloride)',
      dosage_instructions: '1 tab OD in the morning or bedtime',
      default_quantity: '30 tabs',
      suggested_medicines: [
        { name: 'SERTRALINE 50 mg tablet (as Hydrochloride)', dosage: '1 tab OD in the morning or bedtime', quantity: '30 tabs' },
        { name: 'ESCITALOPRAM 10 mg tablet (as Oxalate)', dosage: '1 tab OD morning or bedtime', quantity: '30 tabs' },
        { name: 'FLUOXETINE 20 mg dispersible capsule', dosage: '1 cap OD in the morning after breakfast', quantity: '30 caps' }
      ]
    },
    {
      diagnosis: 'Panic Disorder',
      department: 'OPU Psychiatry',
      treatment_plan: 'Psychoeducation on panic cycle, diaphragmatic breathing during attacks, SSRI maintenance, and short-term PRN acute anxiolytic only if severe.',
      medicine_name: 'FLUOXETINE 20 mg dispersible capsule',
      dosage_instructions: '1 cap OD in the morning after breakfast',
      default_quantity: '30 caps',
      suggested_medicines: [
        { name: 'FLUOXETINE 20 mg dispersible capsule', dosage: '1 cap OD in the morning after breakfast', quantity: '30 caps' },
        { name: 'ALPRAZOLAM 250 mcg tablet', dosage: '1 tab BID-TID PRN acute anxiety', quantity: '20-30 tabs' },
        { name: 'CLONAZEPAM 2 mg tablet', dosage: '0.5mg-1mg at bedtime PRN panic/anxiety', quantity: '30 tabs' }
      ]
    },
    {
      diagnosis: 'Schizophrenia / Psychotic Spectrum Disorder',
      department: 'OPU Psychiatry',
      treatment_plan: 'Long-term antipsychotic adherence, family psychoeducation, metabolic/weight monitoring, and outpatient psychiatric follow-up in 2-4 weeks.',
      medicine_name: 'RISPERIDONE 2 mg tablet',
      dosage_instructions: '1 tab OD at bedtime or BID',
      default_quantity: '30-60 tabs',
      suggested_medicines: [
        { name: 'RISPERIDONE 2 mg tablet', dosage: '1 tab OD at bedtime or BID', quantity: '30-60 tabs' },
        { name: 'OLANZAPINE 5 mg tablet', dosage: '1 tab OD at bedtime', quantity: '30 tabs' },
        { name: 'ARIPIPRAZOLE 10 mg tablet', dosage: '1 tab OD in the morning with food', quantity: '30 tabs' },
        { name: 'VALPROATE DISODIUM 500 mg tablet', dosage: '1 tab BID with meals (adjunct mood stabilization)', quantity: '60 tabs' }
      ]
    },
    {
      diagnosis: 'Bipolar I / II Disorder',
      department: 'OPU Psychiatry',
      treatment_plan: 'Mood charting, regular sleep-wake schedule, mood stabilizer adherence, and periodic serum level / renal-thyroid monitoring.',
      medicine_name: 'VALPROATE DISODIUM 500 mg tablet',
      dosage_instructions: '1 tab BID with meals (bipolar stabilization)',
      default_quantity: '60 tabs',
      suggested_medicines: [
        { name: 'VALPROATE DISODIUM 500 mg tablet', dosage: '1 tab BID with meals (bipolar stabilization)', quantity: '60 tabs' },
        { name: 'VALPROATE DISODIUM 250 mg tablet', dosage: '1 tab BID with meals', quantity: '60 tabs' },
        { name: 'VALPROIC ACID 500 mg controlled release tablet', dosage: '1 tab BID with meals (bipolar stabilization)', quantity: '60 tabs' },
        { name: 'LITHIUM CARBONATE 450 mg MR tablet', dosage: '1 tab BID with meals, monitor serum level', quantity: '60 tabs' },
        { name: 'QUETIAPINE 100 mg tablet (as fumarate)', dosage: '1 tab BID or at bedtime', quantity: '60 tabs' }
      ]
    },
    {
      diagnosis: 'Insomnia Disorder',
      department: 'OPU Psychiatry',
      treatment_plan: 'Strict sleep hygiene (fixed wake time, dark quiet bedroom, avoid screens/caffeine before bed) alongside short-term sleep-onset support.',
      medicine_name: 'QUETIAPINE 25 mg tablet (as fumarate)',
      dosage_instructions: '1 tab at bedtime for sleep/anxiety',
      default_quantity: '30 tabs',
      suggested_medicines: [
        { name: 'QUETIAPINE 25 mg tablet (as fumarate)', dosage: '1 tab at bedtime for sleep/anxiety', quantity: '30 tabs' },
        { name: 'ZOLPIDEM 10 mg tablet', dosage: '1 tab at bedtime PRN severe insomnia', quantity: '14 tabs' },
        { name: 'DIPHENHYDRAMINE 50 mg capsule', dosage: '1 cap at bedtime PRN insomnia/EPS', quantity: '14 caps' }
      ]
    },
    {
      diagnosis: 'Drug-Induced Extrapyramidal Symptoms (EPS)',
      department: 'OPU Psychiatry',
      treatment_plan: 'Administer anticholinergic therapy, review offending antipsychotic dose with psychiatrist, and monitor resolution of stiffness/tremor.',
      medicine_name: 'BIPERIDEN 2 mg tablet (as hydrochloride)',
      dosage_instructions: '1 tab BID for drug-induced parkinsonism',
      default_quantity: '30-60 tabs',
      suggested_medicines: [
        { name: 'BIPERIDEN 2 mg tablet (as hydrochloride)', dosage: '1 tab BID for drug-induced parkinsonism', quantity: '30-60 tabs' },
        { name: 'DIPHENHYDRAMINE 50 mg capsule', dosage: '1 cap at bedtime PRN insomnia/EPS', quantity: '14 caps' }
      ]
    }
  ];

  let activeSuggestedMedicines = [];

  function getDepartmentDrugKey(dept) {
    const d = (dept || window.STAFF_DEPT || '').toLowerCase();
    if (d.includes('neuro')) return 'neurology';
    if (d.includes('psych') || d.includes('opu')) return 'psychiatry';
    return 'general';
  }

  function findMedFromAllLists(medName) {
    const target = String(medName || '').toLowerCase().trim();
    if (!target) return null;
    for (const bucket of Object.values(DOH_MEDICINES)) {
      const exact = bucket.find(m => m.name.toLowerCase() === target);
      if (exact) return exact;
    }
    for (const bucket of Object.values(DOH_MEDICINES)) {
      const partial = bucket.find(m => m.name.toLowerCase().includes(target) || target.includes(m.name.toLowerCase()));
      if (partial) return partial;
    }
    return null;
  }

  function renderSuggestedMedChips(meds, activeName) {
    const wrap = document.getElementById('noteSuggestedMedsWrap');
    const listEl = document.getElementById('noteSuggestedMedsList');
    if (!wrap || !listEl) return;

    if (!meds || !meds.length) {
      wrap.style.display = 'none';
      listEl.innerHTML = '';
      return;
    }

    const activeLower = String(activeName || '').toLowerCase().trim();
    listEl.innerHTML = meds.map((m, idx) => {
      const isSelected = activeLower && m.name.toLowerCase() === activeLower;
      return `
        <button type="button"
                class="diag-med-chip${isSelected ? ' is-selected' : ''}"
                data-sug-idx="${idx}"
                style="display:inline-flex;align-items:center;gap:5px;padding:4px 9px;border-radius:999px;font-size:0.74rem;font-weight:600;cursor:pointer;border:1px solid ${isSelected ? '#15803d' : '#cbd5e1'};background:${isSelected ? '#15803d' : '#f8fafc'};color:${isSelected ? '#ffffff' : '#334155'};transition:all 0.14s ease;">
          <span>${escapeHtml(m.name)}</span>
        </button>
      `;
    }).join('');

    listEl.querySelectorAll('.diag-med-chip').forEach(btn => {
      btn.addEventListener('click', () => {
        const idx = parseInt(btn.getAttribute('data-sug-idx'), 10);
        const med = activeSuggestedMedicines[idx];
        if (!med) return;
        applySuggestedMedication(med);
      });
    });

    wrap.style.display = 'flex';
  }

  function applySuggestedMedication(med) {
    if (!med) return;
    const selectEl = document.getElementById('noteMedicineSelect');
    const doseInput = document.getElementById('noteDosage');
    const qtyInput = document.getElementById('noteQuantity');
    const rxText = document.getElementById('notePrescription');

    if (selectEl) {
      const targetName = String(med.name || '').toLowerCase().trim();
      let foundIdx = -1;
      for (let i = 0; i < selectEl.options.length; i++) {
        const optName = String(selectEl.options[i].dataset.medName || selectEl.options[i].text || '').toLowerCase().trim();
        if (optName === targetName || (targetName && optName.includes(targetName))) {
          foundIdx = i;
          break;
        }
      }
      if (foundIdx >= 0) {
        selectEl.selectedIndex = foundIdx;
      } else {
        const opt = new Option(med.name, 'SUGGESTED_' + Date.now(), true, true);
        opt.dataset.medName = med.name;
        opt.dataset.medDose = med.dosage || '';
        opt.dataset.medQty = med.quantity || '';
        selectEl.add(opt);
      }
    }

    if (doseInput) doseInput.value = med.dosage || '';
    if (qtyInput) qtyInput.value = med.quantity || '';
    if (rxText && med.name) {
      let line = `Rx: ${med.name}`;
      if (med.quantity) line += ` #${med.quantity}`;
      if (med.dosage) line += `\nSig: ${med.dosage}`;
      rxText.value = line;
    }

    renderSuggestedMedChips(activeSuggestedMedicines, med.name);
  }

  function populateMedicineDropdown(dept, suggestedMeds = []) {
    const selectEl = document.getElementById('noteMedicineSelect');
    if (!selectEl) return;
    const key = getDepartmentDrugKey(dept);
    const list = DOH_MEDICINES[key] || DOH_MEDICINES.general;

    const noticeEl = document.getElementById('dohDeptNotice');
    if (noticeEl) {
      const labels = { general: 'General Medicine', neurology: 'Neurology', psychiatry: 'Psychiatry & Mental Health' };
      noticeEl.textContent = labels[key] || 'General Medicine';
    }

    let html = '<option value="">Select drug / medicine...</option>';

    if (Array.isArray(suggestedMeds) && suggestedMeds.length) {
      html += '<optgroup label="Suggested Medications for Diagnosis">';
      suggestedMeds.forEach((m, idx) => {
        const listIdx = list.findIndex(item => item.name.toLowerCase() === String(m.name || '').toLowerCase());
        const val = listIdx >= 0 ? String(listIdx) : `SUG_${idx}`;
        html += `<option value="${escapeHtml(val)}" data-med-name="${escapeHtml(m.name)}" data-med-dose="${escapeHtml(m.dosage || '')}" data-med-qty="${escapeHtml(m.quantity || '')}">${escapeHtml(m.name)}</option>`;
      });
      html += '</optgroup>';
    }

    html += `<optgroup label="${escapeHtml((noticeEl ? noticeEl.textContent : 'Department') + ' Formulary')}">`;
    html += list.map((m, idx) =>
      `<option value="${idx}" data-med-name="${escapeHtml(m.name)}" data-med-dose="${escapeHtml(m.defaultDose || '')}" data-med-qty="${escapeHtml(m.defaultQty || '')}">${escapeHtml(m.name)}</option>`
    ).join('');
    html += '</optgroup>';

    selectEl.innerHTML = html;

    // Populate multi-select checkbox list (#noteMedicineCheckboxes)
    const cbWrap = document.getElementById('noteMedicineCheckboxes');
    if (cbWrap) {
      const seen = new Set();
      const combined = [];
      if (Array.isArray(suggestedMeds)) {
        suggestedMeds.forEach(m => {
          const n = String(m.name || '').trim();
          if (n && !seen.has(n.toLowerCase())) {
            seen.add(n.toLowerCase());
            combined.push({ name: n, dose: m.dosage || '', qty: m.quantity || '', suggested: true });
          }
        });
      }
      list.forEach(m => {
        const n = String(m.name || '').trim();
        if (n && !seen.has(n.toLowerCase())) {
          seen.add(n.toLowerCase());
          combined.push({ name: n, dose: m.defaultDose || '', qty: m.defaultQty || '', suggested: false });
        }
      });

      cbWrap.innerHTML = combined.map((m, i) => `
        <label style="display:flex; align-items:flex-start; gap:8px; font-size:0.8rem; color:#1e293b; cursor:pointer; padding:3px 2px;">
          <input type="checkbox" class="note-med-checkbox" data-med-name="${escapeHtml(m.name)}" data-med-dose="${escapeHtml(m.dose)}" data-med-qty="${escapeHtml(m.qty)}" style="accent-color:#15803d; margin-top:2px; flex-shrink:0;" onchange="syncCheckedMedicinesToPrescription()">
          <span>
            <strong style="font-weight:600;">${escapeHtml(m.name)}</strong>
            ${m.suggested ? '<span style="margin-left:5px; font-size:0.68rem; font-weight:700; color:#15803d; background:#dcfce7; padding:1px 6px; border-radius:999px;">Suggested</span>' : ''}
            ${m.dose ? `<div style="font-size:0.72rem; color:#64748b;">${escapeHtml(m.dose)}${m.qty ? ' · #' + escapeHtml(m.qty) : ''}</div>` : ''}
          </span>
        </label>
      `).join('');
    }
  }

  function getCheckedMedicines() {
    return Array.from(document.querySelectorAll('#noteMedicineCheckboxes .note-med-checkbox:checked')).map(cb => ({
      medicine_name: cb.dataset.medName || '',
      dosage: cb.dataset.medDose || '',
      quantity: cb.dataset.medQty || ''
    })).filter(m => m.medicine_name);
  }

  window.syncCheckedMedicinesToPrescription = function () {
    const checked = getCheckedMedicines();
    const doseInput = document.getElementById('noteDosage');
    const qtyInput = document.getElementById('noteQuantity');
    const rxText = document.getElementById('notePrescription');

    if (checked.length === 1) {
      if (doseInput) doseInput.value = checked[0].dosage || '';
      if (qtyInput) qtyInput.value = checked[0].quantity || '';
    }
    if (rxText) {
      if (checked.length === 0) {
        rxText.value = '';
      } else {
        rxText.value = checked.map((m, i) => {
          const dose = (checked.length === 1 && doseInput && doseInput.value.trim()) ? doseInput.value.trim() : m.dosage;
          const qty = (checked.length === 1 && qtyInput && qtyInput.value.trim()) ? qtyInput.value.trim() : m.quantity;
          let line = `${checked.length > 1 ? (i + 1) + '. ' : ''}Rx: ${m.medicine_name}`;
          if (qty) line += ` #${qty}`;
          if (dose) line += `\n   Sig: ${dose}`;
          return line;
        }).join('\n');
      }
    }
  };

  function getSelectedMedicineMeta() {
    const selectEl = document.getElementById('noteMedicineSelect');
    if (!selectEl || selectEl.selectedIndex < 0) return null;
    const opt = selectEl.options[selectEl.selectedIndex];
    if (!opt || !opt.value) return null;

    if (opt.dataset && opt.dataset.medName) {
      return {
        name: opt.dataset.medName,
        defaultDose: opt.dataset.medDose || '',
        defaultQty: opt.dataset.medQty || ''
      };
    }

    const patientDept = window.activeConcludePatientDept || window.STAFF_DEPT || '';
    const key = getDepartmentDrugKey(patientDept);
    const list = DOH_MEDICINES[key] || DOH_MEDICINES.general;
    return list[parseInt(opt.value, 10)] || null;
  }

  window.handleMedicineSelect = function (selectedVal) {
    if (selectedVal === '') {
      renderSuggestedMedChips(activeSuggestedMedicines, '');
      return;
    }
    const med = getSelectedMedicineMeta();
    if (!med) return;

    const doseInput = document.getElementById('noteDosage');
    const qtyInput = document.getElementById('noteQuantity');

    if (doseInput) doseInput.value = med.defaultDose || '';
    if (qtyInput) qtyInput.value = med.defaultQty || '';

    renderSuggestedMedChips(activeSuggestedMedicines, med.name);
    updatePrescriptionText();
  };

  function updatePrescriptionText() {
    const doseInput = document.getElementById('noteDosage');
    const qtyInput = document.getElementById('noteQuantity');
    const rxText = document.getElementById('notePrescription');
    if (!rxText) return;

    const med = getSelectedMedicineMeta();
    const medName = med ? med.name : '';
    const dose = doseInput ? doseInput.value.trim() : '';
    const qty = qtyInput ? qtyInput.value.trim() : '';

    if (medName) {
      let line = `Rx: ${medName}`;
      if (qty) line += ` #${qty}`;
      if (dose) line += `\nSig: ${dose}`;
      rxText.value = line;
    }
  }

  // Bind live updates on dose & qty inputs
  const doseInp = document.getElementById('noteDosage');
  const qtyInp = document.getElementById('noteQuantity');
  if (doseInp) doseInp.addEventListener('input', updatePrescriptionText);
  if (qtyInp) qtyInp.addEventListener('input', updatePrescriptionText);

  // ── 9.9 Diagnosis Autocomplete, Treatment Plan & Formulary Suggestion ──
  const diagInput = document.getElementById('noteDiagnosis');
  const diagDrop = document.getElementById('cdssDropdown');
  const diagSpin = document.getElementById('noteDiagnosisSpinner');
  let diagDebounceTimer = null;
  let activeDiagSuggestions = [];
  let highlightedDiagIdx = -1;

  function buildRecordedDiagnosesFromPatients() {
    const recorded = [];
    const seen = new Set();
    (allPatients || []).forEach(p => {
      const diag = String(p.medical_history_diagnosis || '').trim();
      if (!diag) return;
      const key = diag.toLowerCase();
      if (seen.has(key)) return;
      seen.add(key);

      let medName = '';
      let dose = '';
      let qty = '';
      if (p.active_medications) {
        try {
          const parsed = typeof p.active_medications === 'string'
            ? JSON.parse(p.active_medications)
            : p.active_medications;
          if (Array.isArray(parsed) && parsed[0]) {
            medName = parsed[0].medicine_name || '';
            dose = parsed[0].dosage || '';
            qty = parsed[0].quantity || '';
          }
        } catch (e) { }
      }
      if (!medName && p.medical_history_prescription) {
        const firstLine = String(p.medical_history_prescription).split('\n')[0] || '';
        medName = firstLine.replace(/^Rx:\s*/i, '').split('#')[0].trim();
      }
      const catalogMed = medName ? findMedFromAllLists(medName) : null;
      const resolvedName = catalogMed ? catalogMed.name : medName;
      const resolvedDose = dose || (catalogMed ? catalogMed.defaultDose : '');
      const resolvedQty = qty || (catalogMed ? catalogMed.defaultQty : '');

      recorded.push({
        diagnosis: diag,
        department: formatDeptLabel(p.department),
        treatment_plan: String(p.medical_history_treatment || '').trim(),
        medicine_name: resolvedName,
        dosage_instructions: resolvedDose,
        default_quantity: resolvedQty,
        suggested_medicines: resolvedName ? [{ name: resolvedName, dosage: resolvedDose, quantity: resolvedQty }] : [],
        source: 'recorded'
      });
    });
    return recorded;
  }

  function normalizeGuidelineEntry(g) {
    const baseName = String(g.medicine_name || '').trim();
    const baseDose = String(g.dosage_instructions || '').trim();
    const baseQty = String(g.default_quantity || '').trim();

    let meds = Array.isArray(g.suggested_medicines) && g.suggested_medicines.length
      ? g.suggested_medicines.map(m => ({ ...m }))
      : [];

    if (!meds.length && g.diagnosis) {
      const localMatch = CLINICAL_DIAGNOSIS_GUIDELINES.find(item =>
        item.diagnosis.toLowerCase() === String(g.diagnosis).toLowerCase().trim()
      );
      if (localMatch && Array.isArray(localMatch.suggested_medicines)) {
        meds = localMatch.suggested_medicines.map(m => ({ ...m }));
      }
    }

    if (!meds.length && baseName) {
      const cat = findMedFromAllLists(baseName);
      meds = [{
        name: cat ? cat.name : baseName,
        dosage: baseDose || (cat ? cat.defaultDose : ''),
        quantity: baseQty || (cat ? cat.defaultQty : '')
      }];
    }

    return {
      ...g,
      medicine_name: meds[0]?.name || baseName,
      dosage_instructions: meds[0]?.dosage || baseDose,
      default_quantity: meds[0]?.quantity || baseQty,
      suggested_medicines: meds
    };
  }

  function getLocalDiagnosisMatches(query, dept) {
    const q = String(query || '').toLowerCase().trim();
    const deptKey = getDepartmentDrugKey(dept);
    const pool = [...CLINICAL_DIAGNOSIS_GUIDELINES, ...buildRecordedDiagnosesFromPatients()];
    const seen = new Set();
    const matches = [];

    pool.forEach(item => {
      const dLow = item.diagnosis.toLowerCase();
      if (seen.has(dLow)) return;
      const medStr = (item.medicine_name || '').toLowerCase();
      const planStr = (item.treatment_plan || '').toLowerCase();
      if (q && !dLow.includes(q) && !medStr.includes(q) && !planStr.includes(q)) return;

      seen.add(dLow);
      const itemKey = getDepartmentDrugKey(item.department);
      let score = 10;
      if (q && dLow.startsWith(q)) score -= 5;
      else if (q && dLow.includes(q)) score -= 3;
      if (itemKey === deptKey) score -= 4;
      else if (itemKey === 'general') score -= 1;

      matches.push({ score, item: normalizeGuidelineEntry(item) });
    });

    matches.sort((a, b) => a.score - b.score || a.item.diagnosis.localeCompare(b.item.diagnosis));
    return matches.map(m => m.item).slice(0, 14);
  }

  function renderDiagnosisSuggestions(list) {
    if (!diagDrop) return;
    activeDiagSuggestions = list || [];
    highlightedDiagIdx = -1;

    if (!activeDiagSuggestions.length) {
      diagDrop.style.display = 'none';
      diagDrop.innerHTML = '';
      return;
    }

    diagDrop.innerHTML = `
      <div style="padding:6px 10px; background:#f8fafc; border-bottom:1px solid #e2e8f0; font-size:0.68rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.04em;">
        Suggested Diagnoses (${activeDiagSuggestions.length})
      </div>
    ` + activeDiagSuggestions.map((g, i) => `
      <div class="diag-suggestion-option" data-idx="${i}" style="padding:8px 12px; cursor:pointer; border-bottom:1px solid #f1f5f9; background:#ffffff; transition:background 0.14s ease;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:8px;">
          <span style="font-weight:700; color:#0f172a; font-size:0.86rem;">${escapeHtml(g.diagnosis)}</span>
          <span style="font-size:0.68rem; font-weight:700; color:#047857; background:#d1fae5; border:1px solid #a7f3d0; padding:1px 7px; border-radius:999px; white-space:nowrap;">${escapeHtml(formatDeptLabel(g.department))}</span>
        </div>
        ${g.treatment_plan ? `
        <div style="font-size:0.75rem; color:#475569; margin-top:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
          <strong>Plan:</strong> ${escapeHtml(g.treatment_plan)}
        </div>` : ''}
        ${g.medicine_name ? `
        <div style="font-size:0.74rem; color:#059669; margin-top:2px; display:flex; align-items:center; gap:6px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
          <span style="font-weight:700;">Rx:</span>
          <span>${escapeHtml(g.medicine_name)}${g.dosage_instructions ? ' &bull; ' + escapeHtml(g.dosage_instructions) : ''}${g.default_quantity ? ' (' + escapeHtml(g.default_quantity) + ')' : ''}</span>
        </div>` : ''}
      </div>
    `).join('');

    diagDrop.style.display = 'block';

    diagDrop.querySelectorAll('.diag-suggestion-option').forEach(el => {
      el.addEventListener('mouseenter', () => {
        highlightedDiagIdx = parseInt(el.getAttribute('data-idx'), 10);
        updateDiagOptionHighlight();
      });
      el.addEventListener('mouseleave', () => {
        el.style.background = '#ffffff';
      });
      el.addEventListener('mousedown', (e) => {
        e.preventDefault();
        const idx = parseInt(el.getAttribute('data-idx'), 10);
        const selected = activeDiagSuggestions[idx];
        if (selected) applyDiagnosisSuggestion(selected);
      });
    });
  }

  function updateDiagOptionHighlight() {
    if (!diagDrop) return;
    diagDrop.querySelectorAll('.diag-suggestion-option').forEach((el, idx) => {
      el.style.background = idx === highlightedDiagIdx ? '#ecfdf5' : '#ffffff';
    });
  }

  async function queryDiagnosisSuggestions(rawQuery) {
    const dept = window.activeConcludePatientDept || window.STAFF_DEPT || '';
    const localList = getLocalDiagnosisMatches(rawQuery, dept);
    renderDiagnosisSuggestions(localList);

    if (diagSpin) diagSpin.style.display = 'block';
    try {
      const res = await fetch(`/sdn/api/guidelines.php?query=${encodeURIComponent(rawQuery)}&department=${encodeURIComponent(dept)}`).then(r => r.json());
      if (diagSpin) diagSpin.style.display = 'none';
      const serverList = Array.isArray(res) ? res : (res.data || []);
      if (!diagInput || diagInput.value.trim() !== rawQuery.trim()) return;

      const mergedMap = new Map();
      localList.forEach(item => mergedMap.set(item.diagnosis.toLowerCase().trim(), item));
      serverList.forEach(srv => {
        if (!srv || !srv.diagnosis) return;
        const key = String(srv.diagnosis).toLowerCase().trim();
        if (!mergedMap.has(key)) {
          mergedMap.set(key, normalizeGuidelineEntry(srv));
        }
      });
      renderDiagnosisSuggestions(Array.from(mergedMap.values()).slice(0, 15));
    } catch (err) {
      if (diagSpin) diagSpin.style.display = 'none';
    }
  }

  if (diagInput && diagDrop) {
    diagInput.addEventListener('focus', () => {
      queryDiagnosisSuggestions(diagInput.value.trim());
    });

    diagInput.addEventListener('input', (e) => {
      const q = e.target.value.trim();
      if (diagDebounceTimer) clearTimeout(diagDebounceTimer);
      const dept = window.activeConcludePatientDept || window.STAFF_DEPT || '';
      renderDiagnosisSuggestions(getLocalDiagnosisMatches(q, dept));
      diagDebounceTimer = setTimeout(() => {
        queryDiagnosisSuggestions(q);
      }, 220);
    });

    diagInput.addEventListener('keydown', (e) => {
      if (diagDrop.style.display === 'none' || !activeDiagSuggestions.length) return;
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        highlightedDiagIdx = (highlightedDiagIdx + 1) % activeDiagSuggestions.length;
        updateDiagOptionHighlight();
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        highlightedDiagIdx = highlightedDiagIdx <= 0 ? activeDiagSuggestions.length - 1 : highlightedDiagIdx - 1;
        updateDiagOptionHighlight();
      } else if (e.key === 'Enter' && highlightedDiagIdx >= 0) {
        e.preventDefault();
        const selected = activeDiagSuggestions[highlightedDiagIdx];
        if (selected) applyDiagnosisSuggestion(selected);
      } else if (e.key === 'Escape') {
        diagDrop.style.display = 'none';
      }
    });

    document.addEventListener('click', (e) => {
      if (!diagInput.contains(e.target) && !diagDrop.contains(e.target)) {
        diagDrop.style.display = 'none';
      }
    });
  }

  window.applyDiagnosisSuggestion = function (g) {
    if (!g) return;
    const normalized = normalizeGuidelineEntry(g);
    const diagEl = document.getElementById('noteDiagnosis');
    const treatInput = document.getElementById('noteTreatment');
    const patientDept = window.activeConcludePatientDept || window.STAFF_DEPT || '';

    if (diagEl) diagEl.value = normalized.diagnosis;
    if (treatInput) {
      treatInput.value = normalized.treatment_plan || '';
    }

    activeSuggestedMedicines = normalized.suggested_medicines || [];
    populateMedicineDropdown(patientDept, activeSuggestedMedicines);

    if (activeSuggestedMedicines.length) {
      applySuggestedMedication(activeSuggestedMedicines[0]);
    } else {
      renderSuggestedMedChips([], '');
    }

    if (diagDrop) {
      diagDrop.style.display = 'none';
      diagDrop.innerHTML = '';
    }
  };
  window.applyCdssGuideline = window.applyDiagnosisSuggestion;

  // ── 10. Clinical Note & Complete Modal ──
  const noteModal = document.getElementById('clinicalNoteModal');
  window.openClinicalNoteModal = function (patientId, patientName) {
    const patientObj = allPatients.find(x => x.patient_id === patientId);
    window.activeConcludePatientDept = (patientObj && patientObj.department) ? patientObj.department : (window.STAFF_DEPT || '');

    document.getElementById('notePatientId').value = patientId;
    document.getElementById('notePatientNameText').textContent = `${patientName} (${patientId})`;
    document.getElementById('noteDiagnosis').value = '';
    document.getElementById('noteTreatment').value = '';
    document.getElementById('notePrescription').value = '';
    if (document.getElementById('noteDosage')) document.getElementById('noteDosage').value = '';
    if (document.getElementById('noteQuantity')) document.getElementById('noteQuantity').value = '';
    if (document.getElementById('noteFollowUpDate')) {
      const tomorrowLocal = new Date();
      tomorrowLocal.setDate(tomorrowLocal.getDate() + 1);
      const minFollowUp = `${tomorrowLocal.getFullYear()}-${String(tomorrowLocal.getMonth() + 1).padStart(2, '0')}-${String(tomorrowLocal.getDate()).padStart(2, '0')}`;
      const fuEl = document.getElementById('noteFollowUpDate');
      fuEl.min = minFollowUp;
      const existingNext = String(patientObj?.next_visit_date || '').trim();
      fuEl.value = (existingNext && existingNext >= minFollowUp) ? existingNext : '';
    }
    if (document.getElementById('noteFollowUpNotes')) document.getElementById('noteFollowUpNotes').value = patientObj?.follow_up_notes || '';

    // Pre-fill recorded vitals if present
    let existingVitals = {};
    if (patientObj && patientObj.vitals_json) {
      try { existingVitals = typeof patientObj.vitals_json === 'string' ? JSON.parse(patientObj.vitals_json) : patientObj.vitals_json; } catch (e) {}
    }
    const vMap = { vitalBp: 'bp', vitalTemp: 'temp', vitalPulse: 'pulse', vitalRr: 'rr', vitalSpo2: 'spo2', vitalWeight: 'weight' };
    Object.entries(vMap).forEach(([elId, key]) => {
      const el = document.getElementById(elId);
      if (el) el.value = existingVitals[key] || '';
    });

    activeSuggestedMedicines = [];
    renderSuggestedMedChips([], '');

    if (diagDrop) {
      diagDrop.style.display = 'none';
      diagDrop.innerHTML = '';
    }

    populateMedicineDropdown(window.activeConcludePatientDept, []);
    noteModal.classList.add('open');
  };

  window.closeClinicalNoteModal = function () {
    if (diagDrop) {
      diagDrop.style.display = 'none';
      diagDrop.innerHTML = '';
    }
    noteModal.classList.remove('open');
  };

  window.submitClinicalNote = async function () {
    const patientId = document.getElementById('notePatientId').value;
    const diagnosis = document.getElementById('noteDiagnosis').value.trim();
    const treatment = document.getElementById('noteTreatment').value.trim();
    const prescription = document.getElementById('notePrescription').value.trim();

    const doseInput = document.getElementById('noteDosage');
    const qtyInput = document.getElementById('noteQuantity');
    const checkedMeds = getCheckedMedicines();
    const selectedMed = getSelectedMedicineMeta();
    const medicineName = checkedMeds.length ? checkedMeds[0].medicine_name : (selectedMed ? selectedMed.name : '');
    const dosage = doseInput ? doseInput.value.trim() : '';
    const quantity = qtyInput ? qtyInput.value.trim() : '';
    const nextVisitDate = document.getElementById('noteFollowUpDate') ? document.getElementById('noteFollowUpDate').value.trim() : '';
    const followUpNotes = document.getElementById('noteFollowUpNotes') ? document.getElementById('noteFollowUpNotes').value.trim() : '';
    const vitals = {
      bp: (document.getElementById('vitalBp')?.value || '').trim(),
      temp: (document.getElementById('vitalTemp')?.value || '').trim(),
      pulse: (document.getElementById('vitalPulse')?.value || '').trim(),
      rr: (document.getElementById('vitalRr')?.value || '').trim(),
      spo2: (document.getElementById('vitalSpo2')?.value || '').trim(),
      weight: (document.getElementById('vitalWeight')?.value || '').trim()
    };

    if (!diagnosis) {
      showToast('Please enter a clinical diagnosis.');
      return;
    }

    if (nextVisitDate) {
      const nowCheck = new Date();
      const todayCheck = `${nowCheck.getFullYear()}-${String(nowCheck.getMonth() + 1).padStart(2, '0')}-${String(nowCheck.getDate()).padStart(2, '0')}`;
      if (!/^\d{4}-\d{2}-\d{2}$/.test(nextVisitDate) || nextVisitDate <= todayCheck) {
        showToast('Follow-up date must be a future date (starting tomorrow).', 3400, 'error');
        document.getElementById('noteFollowUpDate')?.focus();
        return;
      }
      const fuParsed = new Date(nextVisitDate + 'T00:00:00');
      if (!isNaN(fuParsed.getTime()) && fuParsed.getDay() === 0) {
        showToast('Clinics are closed on Sundays. Please pick a Monday to Saturday follow-up date.', 3400, 'error');
        document.getElementById('noteFollowUpDate')?.focus();
        return;
      }
    }

    try {
      await fetch('/sdn/api/staff.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'update',
          patient_id: patientId,
          diagnosis,
          treatment,
          prescription,
          note: diagnosis + (treatment ? ' — ' + treatment : '') + (medicineName ? ` | Rx: ${medicineName} (${dosage})` : '')
        })
      });

      const completeResp = await fetch('/sdn/api/patients.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'completed',
          patient_id: patientId,
          diagnosis,
          treatment_plan: treatment,
          prescription,
          medicine_name: medicineName,
          dosage,
          quantity,
          medicines: checkedMeds,
          next_visit_date: nextVisitDate,
          follow_up_notes: followUpNotes,
          vitals
        })
      }).then(r => r.json()).catch(() => null);

      if (!completeResp || !completeResp.success) {
        showToast((completeResp && completeResp.message) || 'Could not save consultation.', 3400, 'error');
        return;
      }

      const savedMeta = (completeResp && completeResp.data) ? completeResp.data : {};
      const nowLocal = new Date();
      const localTimestamp = `${nowLocal.getFullYear()}-${String(nowLocal.getMonth() + 1).padStart(2, '0')}-${String(nowLocal.getDate()).padStart(2, '0')} ${String(nowLocal.getHours()).padStart(2, '0')}:${String(nowLocal.getMinutes()).padStart(2, '0')}:${String(nowLocal.getSeconds()).padStart(2, '0')}`;

      showToast((nextVisitDate || followUpNotes) ? 'Consultation completed. Follow-Up Slip is ready to print.' : 'Consultation completed.');
      closeClinicalNoteModal();

      await loadData();
      const completedP = allPatients.find(x => x.patient_id === patientId);
      const exactEncodedAt = savedMeta.medical_history_date || completedP?.medical_history_date || localTimestamp;
      const exactFollowUpDate = savedMeta.next_visit_date || nextVisitDate || completedP?.next_visit_date || '';
      const exactFollowUpNotes = savedMeta.follow_up_notes || followUpNotes || completedP?.follow_up_notes || '';
      if ((exactFollowUpDate || exactFollowUpNotes) && window.PatientSlip) {
        window.PatientSlip.openModal({
          slipType: 'followUp',
          patientId: completedP?.patient_id || patientId || '',
          patientName: completedP?.full_name || '',
          patientType: completedP?.patient_type || 'Old',
          specialty: completedP?.department || window.STAFF_DEPT || '',
          consultationMode: completedP?.consultation_mode || 'Face-to-Face',
          attendedDate: exactEncodedAt,
          encodedDate: exactEncodedAt,
          nextVisitDate: exactFollowUpDate,
          followUpNotes: exactFollowUpNotes,
          encodedBy: savedMeta.medical_history_recorded_by || completedP?.medical_history_recorded_by || window.STAFF_FIRST_NAME || 'Medical Staff'
        });
      } else if (prescription) {
        printPrescriptionSlip(patientId, {
          diagnosis,
          treatment,
          prescription,
          next_visit_date: nextVisitDate
        });
      }
    } catch (e) {
      showToast('Error saving consultation note.');
    }
  };

  // ── Dedicated Printable Medical Certificate & Prescription (Rx) Generators ──
  async function fetchFreshPatientForPrint(patientId) {
    let p = allPatients.find(x => x.patient_id === patientId);
    try {
      const res = await fetch(`/sdn/api/patients.php?id=${encodeURIComponent(patientId)}&_=${Date.now()}`).then(r => r.json());
      if (res && res.success && res.data) p = Object.assign({}, p || {}, res.data);
    } catch (e) {}
    return p;
  }

  function resolveDoctorPrintBlock(p) {
    const rawName = (p.assigned_doctor && p.assigned_doctor !== 'None') ? p.assigned_doctor : 'Attending Physician';
    const docObj = (allDoctors || []).find(d => d.name && rawName.toLowerCase().includes(d.name.replace(/^Dr\.?\s*/i, '').toLowerCase()));
    const title = p.doctor_title || (docObj && docObj.title) || 'MD';
    const license = p.doctor_license || (docObj && docObj.license_number) || '________________';
    const displayName = rawName.startsWith('Dr.') ? rawName : (rawName === 'Attending Physician' ? rawName : 'Dr. ' + rawName);
    return { displayName: title && !displayName.includes(title) ? `${displayName}, ${title}` : displayName, license };
  }

  function openPrintWindow(html) {
    const w = window.open('', '_blank', 'width=860,height=920');
    if (!w) { showToast('Please allow pop-ups to print.', 3000, 'warning'); return; }
    w.document.open();
    w.document.write(html);
    w.document.close();
    w.focus();
    let printed = false;
    const triggerPrint = () => {
      if (printed) return;
      printed = true;
      try { w.print(); } catch (e) {}
    };
    w.onload = () => setTimeout(triggerPrint, 180);
    setTimeout(triggerPrint, 650);
  }

  function formatPrintDate(raw, fallback) {
    if (!raw) return fallback || '';
    const str = String(raw).trim();
    const dt = new Date(/^\d{4}-\d{2}-\d{2}$/.test(str) ? str + 'T00:00:00' : str.replace(' ', 'T'));
    if (isNaN(dt.getTime())) return str;
    return dt.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
  }

  function buildOfficialLetterheadHtml(deptSubtitle) {
    const origin = window.location.origin || '';
    const dohLogo = `${origin}/sdn/public/assets/logos/doh%20logo.png`;
    const mmwghLogo = `${origin}/sdn/public/assets/mmwgh_logo.png`;
    return `
      <header class="letterhead">
        <div class="lh-logo"><img src="${escapeHtml(dohLogo)}" alt="DOH Logo" /></div>
        <div class="lh-center">
          <div class="lh-republic">Republic of the Philippines &middot; Department of Health</div>
          <div class="lh-region">Central Luzon Center for Health Development</div>
          <h2>Mariveles Mental Wellness and General Hospital</h2>
          <div class="lh-addr">P. Monroe St., Poblacion, Mariveles, Bataan &middot; Tel. (047) 935-4645</div>
          <div class="lh-dept">${escapeHtml(deptSubtitle)}</div>
        </div>
        <div class="lh-logo"><img src="${escapeHtml(mmwghLogo)}" alt="MMWGH Logo" /></div>
      </header>`;
  }

  window.printMedicalCertificate = async function (patientId, overrideData = {}) {
    const p = await fetchFreshPatientForPrint(patientId);
    if (!p) { showToast('Patient record not found for printing.'); return; }
    const todayDate = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    const examDate = formatPrintDate(p.medical_history_date || p.appointment_date, todayDate);
    const diagnosis = overrideData.diagnosis || p.medical_history_diagnosis || 'Clinical Evaluation';
    const treatment = overrideData.treatment || p.medical_history_treatment || 'Advised appropriate rest and outpatient follow-up.';
    const nextVisitRaw = overrideData.next_visit_date || p.next_visit_date || '';
    const nextVisit = nextVisitRaw ? formatPrintDate(nextVisitRaw, nextVisitRaw) : '';
    const deptName = p.department || window.STAFF_DEPT || 'General Medical';
    const doc = resolveDoctorPrintBlock(p);

    openPrintWindow(`<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Medical Certificate - ${escapeHtml(p.patient_id)}</title>
      <style>
        @page { size: letter portrait; margin: 14mm 16mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; color: #0f172a; margin: 0; line-height: 1.6; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .letterhead { display: grid; grid-template-columns: 68px 1fr 68px; align-items: center; gap: 12px; border-bottom: 2.5px solid #15803d; padding-bottom: 12px; margin-bottom: 20px; }
        .lh-logo { width: 64px; height: 64px; display: flex; align-items: center; justify-content: center; }
        .lh-logo img { width: 100%; height: 100%; object-fit: contain; display: block; }
        .lh-center { text-align: center; }
        .lh-republic { font-size: 10.5px; color: #475569; font-weight: 500; }
        .lh-region { font-size: 11px; color: #334155; font-weight: 700; }
        .lh-center h2 { margin: 2px 0; font-size: 15.5px; color: #14532d; text-transform: uppercase; letter-spacing: 0.01em; }
        .lh-addr { font-size: 10.5px; color: #475569; }
        .lh-dept { display: inline-block; margin-top: 4px; padding: 2px 10px; border-radius: 999px; background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; }
        .doc-title { text-align: center; font-size: 19px; font-weight: 800; letter-spacing: 0.1em; margin: 16px 0 18px; text-transform: uppercase; color: #0f172a; }
        .meta-bar { display: grid; grid-template-columns: 1fr 1fr; gap: 6px 18px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 14px; font-size: 12px; color: #334155; margin-bottom: 20px; }
        .meta-bar strong { color: #0f172a; }
        .body-text { font-size: 14px; margin: 0 0 14px; text-align: justify; color: #0f172a; }
        .clinical-box { border: 1px solid #cbd5e1; border-left: 4px solid #15803d; border-radius: 6px; padding: 14px 16px; margin: 16px 0 18px; background: #ffffff; }
        .clinical-row { margin-bottom: 12px; }
        .clinical-row:last-child { margin-bottom: 0; }
        .clinical-lbl { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #475569; margin-bottom: 3px; }
        .clinical-val { font-size: 14px; color: #0f172a; font-weight: 600; white-space: pre-wrap; }
        .sig-wrap { display: flex; justify-content: flex-end; margin-top: 48px; }
        .sig { text-align: center; min-width: 260px; }
        .sig-line { border-top: 1.5px solid #0f172a; padding-top: 7px; font-weight: 700; font-size: 14px; color: #0f172a; }
        .sig-sub { font-size: 12px; color: #475569; margin-top: 2px; }
        .doc-footer { margin-top: 44px; padding-top: 10px; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; font-size: 10.5px; color: #64748b; }
      </style></head><body>
      ${buildOfficialLetterheadHtml(`Outpatient Department — ${deptName}`)}
      <div class="doc-title">Medical Certificate</div>
      <div class="meta-bar">
        <div><strong>Patient ID:</strong> ${escapeHtml(p.patient_id)}</div>
        <div><strong>Date Issued:</strong> ${escapeHtml(todayDate)}</div>
        <div><strong>Department:</strong> ${escapeHtml(deptName)}</div>
        <div><strong>Date Examined:</strong> ${escapeHtml(examDate)}</div>
      </div>
      <p class="body-text">To Whom It May Concern:</p>
      <p class="body-text">This is to certify that <strong>${escapeHtml(p.full_name || 'Patient')}</strong>, ${escapeHtml(p.age ? p.age + ' years old' : '—')}${p.gender ? ', ' + escapeHtml(p.gender) : ''}, residing at <strong>${escapeHtml(p.address || 'Mariveles, Bataan')}</strong>, was examined and managed at this hospital on <strong>${escapeHtml(examDate)}</strong>.</p>
      <div class="clinical-box">
        <div class="clinical-row">
          <div class="clinical-lbl">Clinical Diagnosis / Impression</div>
          <div class="clinical-val">${escapeHtml(diagnosis)}</div>
        </div>
        <div class="clinical-row">
          <div class="clinical-lbl">Remarks / Recommendation</div>
          <div class="clinical-val" style="font-weight:500;">${escapeHtml(treatment)}</div>
        </div>
        ${nextVisit ? `<div class="clinical-row"><div class="clinical-lbl">Recommended Follow-Up Date</div><div class="clinical-val">${escapeHtml(nextVisit)}${p.follow_up_notes ? ' — ' + escapeHtml(p.follow_up_notes) : ''}</div></div>` : ''}
      </div>
      <p class="body-text">This certification is issued upon the request of the patient for whatever legal or medical purpose it may serve.</p>
      <div class="sig-wrap">
        <div class="sig">
          <div class="sig-line">${escapeHtml(doc.displayName)}</div>
          <div class="sig-sub">Attending Physician &middot; ${escapeHtml(deptName)}</div>
          <div class="sig-sub">PRC License No.: ${escapeHtml(doc.license)}</div>
          <div class="sig-sub">PTR No.: ________________</div>
        </div>
      </div>
      <footer class="doc-footer">
        <span>Not valid without physician signature and official hospital seal.</span>
        <span>MMWGH OPU-Medical &middot; (047) 935-4645</span>
      </footer>
    </body></html>`);
  };

  window.printPrescriptionSlip = async function (patientId, overrideData = {}) {
    const p = await fetchFreshPatientForPrint(patientId);
    if (!p) { showToast('Patient record not found for printing.'); return; }
    const todayDate = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    const prescription = overrideData.prescription || p.medical_history_prescription || formatMedicationsReadable(p.active_medications) || 'None prescribed.';
    const nextVisitRaw = overrideData.next_visit_date || p.next_visit_date || '';
    const nextVisit = nextVisitRaw ? formatPrintDate(nextVisitRaw, nextVisitRaw) : '';
    const deptName = p.department || window.STAFF_DEPT || 'Outpatient Clinic';
    const doc = resolveDoctorPrintBlock(p);

    openPrintWindow(`<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Prescription (Rx) - ${escapeHtml(p.patient_id)}</title>
      <style>
        @page { size: half-letter portrait; margin: 11mm 12mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; color: #0f172a; margin: 0; line-height: 1.5; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .letterhead { display: grid; grid-template-columns: 54px 1fr 54px; align-items: center; gap: 10px; border-bottom: 2.5px solid #15803d; padding-bottom: 10px; margin-bottom: 14px; }
        .lh-logo { width: 52px; height: 52px; display: flex; align-items: center; justify-content: center; }
        .lh-logo img { width: 100%; height: 100%; object-fit: contain; display: block; }
        .lh-center { text-align: center; }
        .lh-republic { font-size: 9.5px; color: #475569; }
        .lh-region { font-size: 10px; color: #334155; font-weight: 700; }
        .lh-center h2 { margin: 2px 0; font-size: 13.5px; color: #14532d; text-transform: uppercase; letter-spacing: 0.01em; }
        .lh-addr { font-size: 9.5px; color: #475569; }
        .lh-dept { display: inline-block; margin-top: 3px; padding: 1px 8px; border-radius: 999px; background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; font-size: 9.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; }
        .pt-grid { display: grid; grid-template-columns: 1.25fr 1fr; gap: 5px 14px; font-size: 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 12px; margin-bottom: 16px; color: #334155; }
        .pt-grid strong { color: #0f172a; }
        .rx-symbol { font-size: 46px; font-weight: 800; font-family: Georgia, "Times New Roman", serif; color: #14532d; line-height: 1; margin: 2px 0 10px; }
        .rx-body { white-space: pre-wrap; font-size: 23px; font-weight: 700; line-height: 1.75; min-height: 210px; padding: 10px 8px 18px; color: #0f172a; letter-spacing: -0.01em; }
        .followup { margin-top: 10px; padding: 8px 12px; border-radius: 6px; background: #f0fdf4; border: 1px solid #bbf7d0; font-size: 12.5px; color: #14532d; }
        .sig-wrap { display: flex; justify-content: flex-end; margin-top: 34px; }
        .sig { text-align: center; min-width: 240px; }
        .sig-line { border-top: 1.5px solid #0f172a; padding-top: 6px; font-weight: 700; font-size: 13.5px; color: #0f172a; }
        .sig-sub { font-size: 11.5px; color: #475569; margin-top: 2px; }
        .doc-footer { margin-top: 24px; padding-top: 8px; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; font-size: 10px; color: #64748b; }
      </style></head><body>
      ${buildOfficialLetterheadHtml(`${deptName} — Outpatient Prescription`)}
      <div class="pt-grid">
        <div><strong>Patient:</strong> ${escapeHtml(p.full_name || 'Patient')}</div>
        <div><strong>Date:</strong> ${escapeHtml(todayDate)}</div>
        <div><strong>Age / Sex:</strong> ${escapeHtml(p.age ? p.age + ' y/o' : '—')} / ${escapeHtml(p.gender || '—')}</div>
        <div><strong>Patient ID:</strong> ${escapeHtml(p.patient_id)}</div>
        <div style="grid-column: 1 / -1;"><strong>Address:</strong> ${escapeHtml(p.address || 'Mariveles, Bataan')}</div>
      </div>
      <div class="rx-symbol">&#8478;</div>
      <div class="rx-body">${escapeHtml(prescription)}</div>
      ${nextVisit ? `<div class="followup"><strong>Follow-Up Visit:</strong> ${escapeHtml(nextVisit)}${p.follow_up_notes ? ' — ' + escapeHtml(p.follow_up_notes) : ''}</div>` : ''}
      <div class="sig-wrap">
        <div class="sig">
          <div class="sig-line">${escapeHtml(doc.displayName)}</div>
          <div class="sig-sub">Attending Physician &middot; ${escapeHtml(deptName)}</div>
          <div class="sig-sub">PRC Lic. No.: ${escapeHtml(doc.license)}</div>
          <div class="sig-sub">PTR No.: ________________ &nbsp;&middot;&nbsp; S2 No.: ____________</div>
        </div>
      </div>
      <footer class="doc-footer">
        <span>Please present this prescription at the pharmacy.</span>
        <span>MMWGH OPU-Medical &middot; (047) 935-4645</span>
      </footer>
    </body></html>`);
  };

  window.printMedicalCertificateFromDetails = function () {
    if (window.currentDetailsPatientId) printMedicalCertificate(window.currentDetailsPatientId);
  };

  window.printPrescriptionFromDetails = function () {
    if (window.currentDetailsPatientId) printPrescriptionSlip(window.currentDetailsPatientId);
  };

  window.openPatientSlipFromDetails = async function () {
    if (!window.currentDetailsPatientId || !window.PatientSlip) return;
    const p = await fetchFreshPatientForPrint(window.currentDetailsPatientId);
    if (!p) { showToast('Patient record not found.'); return; }
    if (!window.PatientSlip.canGenerateSlip(p)) {
      showToast('Slip is only available for confirmed appointments.');
      return;
    }
    window.PatientSlip.openModal({
      slipType: window.PatientSlip.resolveSlipType(p),
      patientId: p.patient_id || p.reference_no || '',
      patientName: p.full_name || '',
      patientType: p.patient_type || 'New',
      appointmentDate: p.appointment_date || '',
      appointmentTime: p.preferred_time_slot || p.scheduled_time || '',
      specialty: p.department || window.STAFF_DEPT || '',
      consultType: p.consultation_nature || p.consultation_mode || 'Face-to-Face',
      consultationMode: p.consultation_mode || 'Face-to-Face',
      teleconsultPlatform: p.teleconsult_platform || '',
      teleconsultLink: p.teleconsult_link || '',
      teleconsultNotes: p.teleconsult_notes || '',
      remarks: p.chief_complaints || '',
      encodedBy: p.medical_history_recorded_by || p.receiver || window.STAFF_FIRST_NAME || 'Medical Staff',
      encodedDate: p.medical_history_date || p.updated_at || '',
      attendedDate: p.medical_history_date || p.appointment_date || '',
      nextVisitDate: p.next_visit_date || '',
      followUpNotes: p.follow_up_notes || ''
    });
  };

  // ── Print Slip Generator (Exact Government MMWGH Slip Layout) ──
  window.printConsultationSlip = async function (patientId, overrideData = {}) {
    let p = allPatients.find(x => x.patient_id === patientId);

    try {
      const res = await fetch(`/sdn/api/patients.php?id=${encodeURIComponent(patientId)}&_=${Date.now()}`).then(r => r.json());
      if (res && res.success && res.data) {
        p = Object.assign({}, p || {}, res.data);
      }
    } catch (e) { }

    if (!p) {
      showToast('Patient record not found for printing.');
      return;
    }

    const todayDate = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    const todayTime = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });

    const diagnosis = overrideData.diagnosis || p.medical_history_diagnosis || 'No clinical diagnosis recorded.';
    const treatment = overrideData.treatment || p.medical_history_treatment || 'No treatment regimen recorded.';
    const prescription = overrideData.prescription || p.medical_history_prescription || 'None prescribed.';
    const activeMeds = formatMedicationsReadable(p.active_medications) || 'None recorded.';
    const nextVisit = p.next_visit_date || 'None scheduled.';
    const deptName = p.department || window.STAFF_DEPT || 'General Medical';
    const patientName = p.full_name || 'Patient';
    const refId = p.patient_id || patientId;

    const assignedDoctorRaw = p.assigned_doctor && p.assigned_doctor !== 'None' ? p.assigned_doctor : '';
    const assignedDoctor = assignedDoctorRaw
      ? (assignedDoctorRaw.startsWith('Dr.') ? assignedDoctorRaw : 'Dr. ' + assignedDoctorRaw)
      : 'Attending Physician';

    const slipHtml = `<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Clinical Consultation Summary - ${escapeHtml(refId)}</title>
  <style>
    @page {
      size: letter portrait;
      margin: 8mm 12mm 8mm 12mm;
    }
    * {
      box-sizing: border-box;
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
    }
    html, body {
      margin: 0;
      padding: 0;
      height: 100%;
      background: #ffffff;
      color: #0f172a;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
    }
    .slip-page-wrapper {
      position: relative;
      width: 100%;
      height: 100%;
      max-height: 100vh;
      overflow: hidden;
      padding: 10px 14px;
      page-break-inside: avoid;
      break-inside: avoid;
    }
    .watermark-bg {
      position: absolute;
      top: 54%;
      left: 50%;
      transform: translate(-50%, -50%);
      width: 480px;
      max-width: 75%;
      opacity: 0.18;
      z-index: 0;
      pointer-events: none;
      display: flex;
      align-items: center;
      justify-content: center;
      user-select: none;
    }
    .watermark-bg img {
      width: 100%;
      height: auto;
      max-height: 520px;
      object-fit: contain;
      display: block;
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
    }
    .slip-container {
      position: relative;
      z-index: 2;
      display: flex;
      flex-direction: column;
      gap: 12px;
    }
    .slip-header {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 12px;
      padding-bottom: 8px;
      border-bottom: 3px solid #16a34a;
    }
    .slip-header-left {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .slip-header-logo {
      width: 58px;
      height: 58px;
      object-fit: contain;
      flex-shrink: 0;
    }
    .slip-hospital-title {
      font-size: 1.08rem;
      font-weight: 900;
      color: #15803d;
      letter-spacing: 0.02em;
      line-height: 1.15;
      margin: 0;
      text-transform: uppercase;
    }
    .slip-sub-title {
      font-size: 0.78rem;
      font-weight: 700;
      color: #0f172a;
      margin: 2px 0 0;
    }
    .slip-dept-text {
      font-size: 0.74rem;
      color: #334155;
      margin: 1px 0 0;
    }
    .slip-header-meta {
      text-align: right;
      font-size: 0.72rem;
      color: #0f172a;
      line-height: 1.3;
      font-weight: 500;
    }
    .patient-banner {
      margin-top: 2px;
      background: rgba(241, 245, 249, 0.65);
      border: 1px solid #cbd5e1;
      border-radius: 8px;
      padding: 8px 14px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .patient-banner-col .label {
      font-size: 0.7rem;
      color: #64748b;
      font-weight: 600;
    }
    .patient-banner-col .val-name {
      font-size: 1rem;
      font-weight: 800;
      color: #0f172a;
      margin-top: 1px;
    }
    .patient-banner-col .val-id {
      font-size: 1.05rem;
      font-weight: 800;
      color: #15803d;
      margin-top: 1px;
      text-align: right;
    }
    .section-block {
      margin-top: 2px;
    }
    .section-heading {
      font-size: 0.78rem;
      font-weight: 800;
      color: #15803d;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      margin-bottom: 5px;
    }
    .vitals-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 8px;
    }
    .vital-box {
      background: rgba(255, 255, 255, 0.55);
      border: 1px solid #cbd5e1;
      border-radius: 8px;
      padding: 6px 6px;
      text-align: center;
    }
    .vital-box .v-lbl {
      font-size: 0.64rem;
      font-weight: 700;
      color: #64748b;
      text-transform: uppercase;
    }
    .vital-box .v-val {
      font-size: 0.9rem;
      font-weight: 800;
      color: #0f172a;
      margin-top: 2px;
    }
    .rounded-outline-box {
      background: rgba(255, 255, 255, 0.55);
      border: 1px solid #cbd5e1;
      border-radius: 8px;
      padding: 8px 14px;
      font-size: 0.82rem;
      color: #0f172a;
      line-height: 1.4;
    }
    .rx-item-title {
      font-size: 0.78rem;
      font-weight: 800;
      color: #0f172a;
      margin-bottom: 2px;
    }
    .rx-item-val {
      font-size: 0.8rem;
      color: #1e293b;
      margin-bottom: 4px;
      white-space: pre-wrap;
    }
    .slip-footer {
      margin-top: 14px;
      display: flex;
      align-items: flex-end;
      justify-content: space-between;
      gap: 16px;
      padding-top: 6px;
    }
    .footer-left {
      font-size: 0.74rem;
    }
    .footer-left .fl-title {
      font-weight: 800;
      color: #0f172a;
      text-transform: uppercase;
    }
    .footer-left .fl-val {
      font-weight: 800;
      color: #15803d;
      margin-top: 2px;
    }
    .footer-sign-col {
      text-align: center;
      min-width: 230px;
    }
    .footer-sign-doctor {
      font-size: 0.86rem;
      font-weight: 800;
      color: #0f172a;
      margin-bottom: 2px;
      line-height: 1.2;
    }
    .footer-sign-line {
      border-bottom: 1.6px solid #0f172a;
      width: 100%;
      margin-bottom: 4px;
    }
    .footer-sign-role {
      font-size: 0.76rem;
      font-weight: 700;
      color: #0f172a;
    }
    .footer-sign-dept {
      font-size: 0.72rem;
      color: #64748b;
      margin-top: 1px;
    }
  </style>
</head>
<body>
  <div class="slip-page-wrapper">
    <div class="watermark-bg" aria-hidden="true">
      <img src="/sdn/public/assets/mmwgh_logo.png" alt="MMWGH Seal Watermark">
    </div>

    <div class="slip-container">
    <div class="slip-header">
      <div class="slip-header-left">
        <img src="/sdn/public/assets/mmwgh_logo.png" alt="MMWGH Logo" class="slip-header-logo">
        <div>
          <h1 class="slip-hospital-title">Mariveles Mental Wellness<br>and General Hospital</h1>
          <div class="slip-sub-title">Clinical Consultation and Medical Triage Summary</div>
          <div class="slip-dept-text">Department: ${escapeHtml(deptName)}</div>
        </div>
      </div>
      <div class="slip-header-meta">
        <div>Generated: ${todayDate}</div>
        <div>${todayTime}</div>
      </div>
    </div>

    <div class="patient-banner">
      <div class="patient-banner-col">
        <div class="label">Patient Name</div>
        <div class="val-name">${escapeHtml(patientName)}</div>
      </div>
      <div class="patient-banner-col" style="text-align:right;">
        <div class="label">Patient ID</div>
        <div class="val-id">${escapeHtml(refId)}</div>
      </div>
    </div>

    <!-- 1. Triage Vital Signs -->
    <div class="section-block">
      <div class="section-heading">1. Triage Vital Signs</div>
      <div class="vitals-grid">
        <div class="vital-box">
          <div class="v-lbl">Blood Pressure</div>
          <div class="v-val">${escapeHtml(p.blood_pressure || 'N/A mmHg')}</div>
        </div>
        <div class="vital-box">
          <div class="v-lbl">Temperature</div>
          <div class="v-val">${escapeHtml(p.temperature || 'N/A °C')}</div>
        </div>
        <div class="vital-box">
          <div class="v-lbl">Heart Rate</div>
          <div class="v-val">${escapeHtml(p.heart_rate || 'N/A bpm')}</div>
        </div>
        <div class="vital-box">
          <div class="v-lbl">Weight</div>
          <div class="v-val">${escapeHtml(p.weight || 'N/A kg')}</div>
        </div>
      </div>
    </div>

    <!-- 2. Diagnosis & Clinical Assessment -->
    <div class="section-block">
      <div class="section-heading">2. Diagnosis &amp; Clinical Assesment</div>
      <div class="rounded-outline-box">
        <div style="font-weight:700; color:#0f172a;">${escapeHtml(diagnosis)}</div>
      </div>
    </div>

    <!-- 3. Treatment Plan and Management -->
    <div class="section-block">
      <div class="section-heading">3. Treatment Plan and Management</div>
      <div class="rounded-outline-box">
        <div>${escapeHtml(treatment)}</div>
      </div>
    </div>

    <!-- 4. Medical Prescription -->
    <div class="section-block">
      <div class="section-heading">4. Medical Prescription</div>
      <div class="rounded-outline-box">
        <div class="rx-item-title">Prescription:</div>
        <div class="rx-item-val">${escapeHtml(prescription)}</div>
        <div class="rx-item-title">Medications:</div>
        <div class="rx-item-val" style="margin-bottom:0;">${escapeHtml(activeMeds)}</div>
      </div>
    </div>

    <div class="slip-footer">
      <div class="footer-left">
        <div class="fl-title">Next Scheduled Visit / Follow-up:</div>
        <div class="fl-val">${escapeHtml(nextVisit)}</div>
      </div>
      <div class="footer-sign-col">
        <div class="footer-sign-doctor">${escapeHtml(assignedDoctor)}</div>
        <div class="footer-sign-line"></div>
        <div class="footer-sign-role">Attending Physician</div>
        <div class="footer-sign-dept">${escapeHtml(deptName)}</div>
      </div>
    </div>
  </div>
  </div>
</body>
</html>`;

    let iframe = document.getElementById('consultPrintIframe');
    if (!iframe) {
      iframe = document.createElement('iframe');
      iframe.id = 'consultPrintIframe';
      iframe.style.position = 'fixed';
      iframe.style.right = '0';
      iframe.style.bottom = '0';
      iframe.style.width = '0';
      iframe.style.height = '0';
      iframe.style.border = '0';
      document.body.appendChild(iframe);
    }

    const doc = iframe.contentWindow.document;
    doc.open();
    doc.write(slipHtml);
    doc.close();

    setTimeout(() => {
      iframe.contentWindow.focus();
      iframe.contentWindow.print();
    }, 450);
  };

  // Details Modal Printable Action
  let currentDetailsPatientId = null;
  window.printPatientSlipFromDetails = function () {
    if (currentDetailsPatientId) {
      printConsultationSlip(currentDetailsPatientId);
    }
  };

  // ── 11. Patient Details Modal ──
  const detailsModal = document.getElementById('patientDetailsModal');
  window.openPatientDetailsModal = async function (patientId) {
    currentDetailsPatientId = patientId;
    window.currentDetailsPatientId = patientId;
    const prePrimaryBtn = document.getElementById('staffDetailsPrimaryBtn');
    const preSlipBtn = document.getElementById('staffDetailsSlipBtn');
    const preMedCertBtn = document.getElementById('staffDetailsMedCertBtn');
    const preRxBtn = document.getElementById('staffDetailsRxBtn');
    const statusBadgeEl = document.getElementById('staffDetailsStatusBadge');
    if (prePrimaryBtn) { prePrimaryBtn.style.display = 'none'; prePrimaryBtn.onclick = null; }
    if (preSlipBtn) preSlipBtn.style.display = 'none';
    if (preMedCertBtn) preMedCertBtn.style.display = 'none';
    if (preRxBtn) preRxBtn.style.display = 'none';
    if (statusBadgeEl) statusBadgeEl.innerHTML = '';
    const bodyEl = document.getElementById('patientDetailsContent');
    bodyEl.innerHTML = '<div style="text-align:center; padding:2rem; color:#64748b;">Loading patient record...</div>';
    detailsModal.classList.add('open');

    try {
      const res = await fetch(`/sdn/api/patients.php?id=${encodeURIComponent(patientId)}&_=${Date.now()}`).then(r => r.json());
      if (res && res.success && res.data) {
        const p = res.data;
        const statusNorm = (p.status || '').toLowerCase();
        const isInquiry = isInquiryRecord(p);
        const isCompleted = statusNorm === 'completed';
        const isCancelled = isCancelledStatus(statusNorm);
        const isTransferred = statusNorm === 'transferred';
        const isLocked = isCompleted || isCancelled || isTransferred;
        const isTele = !isInquiry && String(p.consultation_mode || '').trim().toLowerCase() === 'teleconsultation';

        if (statusBadgeEl) {
          statusBadgeEl.innerHTML = renderOverviewStatusPill(statusNorm);
        }

        const slipBtnEl = document.getElementById('staffDetailsSlipBtn');
        const slipLabelEl = document.getElementById('staffDetailsSlipBtnLabel');
        const allowSlip = Boolean(window.PatientSlip && window.PatientSlip.canGenerateSlip(p));
        if (slipBtnEl) slipBtnEl.style.display = allowSlip ? '' : 'none';
        if (allowSlip && slipLabelEl) {
          const st = window.PatientSlip.resolveSlipType(p);
          if (st === 'followUp') {
            slipLabelEl.textContent = 'Print Follow-Up Slip';
          } else if (st === 'rescheduled') {
            slipLabelEl.textContent = isTele ? 'Print Rescheduled Teleconsult Slip' : 'Print Rescheduled Slip';
          } else {
            slipLabelEl.textContent = isTele ? 'Print Teleconsultation Slip' : 'Print Appointment Slip';
          }
        }

        const hasDiagnosis = Boolean((p.medical_history_diagnosis || '').trim());
        const hasPrescription = Boolean((p.medical_history_prescription || '').trim() || formatMedicationsReadable(p.active_medications));
        if (preMedCertBtn) preMedCertBtn.style.display = (isCompleted || hasDiagnosis) ? '' : 'none';
        if (preRxBtn) preRxBtn.style.display = hasPrescription ? '' : 'none';

        if (prePrimaryBtn && !isLocked) {
          if (isInquiry) {
            prePrimaryBtn.textContent = 'Resolve Inquiry';
            prePrimaryBtn.style.display = '';
            prePrimaryBtn.onclick = () => { closePatientDetailsModal(); openResolveInquiryModal(p.patient_id, p.full_name || ''); };
          } else if (inApprovalStatus(statusNorm)) {
            prePrimaryBtn.textContent = 'Approve';
            prePrimaryBtn.style.display = '';
            prePrimaryBtn.onclick = () => { closePatientDetailsModal(); handleApprove(p.patient_id); };
          } else if (isConfirmedStatus(statusNorm)) {
            prePrimaryBtn.textContent = 'Check In';
            prePrimaryBtn.style.display = '';
            prePrimaryBtn.onclick = () => { closePatientDetailsModal(); handleCheckIn(p.patient_id); };
          } else if (statusNorm === 'arrived') {
            prePrimaryBtn.textContent = 'Start Consult';
            prePrimaryBtn.style.display = '';
            prePrimaryBtn.onclick = () => { closePatientDetailsModal(); handleStartConsult(p.patient_id); };
          } else if (statusNorm === 'ongoing') {
            prePrimaryBtn.textContent = 'Complete Consult';
            prePrimaryBtn.style.display = '';
            prePrimaryBtn.onclick = () => { closePatientDetailsModal(); openClinicalNoteModal(p.patient_id, p.full_name || ''); };
          }
        }

        const dateParts = formatDateBadge(p.appointment_date || p.created_at);
        const timeLabel = p.preferred_time_slot || formatTime12(p.scheduled_time) || '8:00 AM';
        const deptDisplay = formatDeptLabel(p.department);
        const recipientLabel = p.appointment_recipient || 'Patient (Me)';
        const companionLabel = (p.with_companion == 1 || p.with_companion === true || p.with_companion === '1') ? 'Yes' : 'No';
        const scheduleDisplay = isInquiry
          ? 'No clinic slot reserved (Inquiry Only)'
          : `${dateParts.full} · ${timeLabel}`;
        const completedHistory = Array.isArray(p.previous_appointments)
          ? p.previous_appointments.filter(prev => String(prev.status || '').toLowerCase().trim() === 'completed')
          : [];
        const rawTeleLink = String(p.teleconsult_link || '').trim();
        const safeTeleLink = /^https?:\/\/\S+$/i.test(rawTeleLink) ? rawTeleLink : '';
        const telePlatform = String(p.teleconsult_platform || '').trim();
        const teleNotes = String(p.teleconsult_notes || '').trim();

        bodyEl.innerHTML = `
          ${isInquiry ? `
          <div style="display:flex; align-items:flex-start; gap:10px; background:#fffbeb; border:1px solid #fde68a; border-radius:10px; padding:12px 14px; font-size:0.84rem; color:#78350f;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0; margin-top:1px;"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
            <div>
              <strong>Inquiry Follow-up:</strong> Contact ${escapeHtml(p.full_name || 'inquirer')} via
              ${p.contact_number ? `<a href="tel:${escapeHtml(p.contact_number)}" style="color:#92400e; font-weight:700; text-decoration:underline;">${escapeHtml(p.contact_number)}</a>` : 'phone'}
              ${p.email ? ` or <a href="mailto:${escapeHtml(p.email)}" style="color:#92400e; font-weight:700; text-decoration:underline;">${escapeHtml(p.email)}</a>` : ''}.
            </div>
          </div>
          ` : ''}
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; background:#f8fafc; padding:1.1rem; border-radius:10px; border:1px solid #e2e8f0;">
            <div>
              <div style="font-size:0.72rem; color:#64748b; font-weight:600; text-transform:uppercase;">${isInquiry ? 'Inquirer Name' : 'Patient Name'}</div>
              <div style="font-size:0.98rem; font-weight:700; color:#0f172a; margin-top:2px;">${escapeHtml(p.full_name)}</div>
            </div>
            <div>
              <div style="font-size:0.72rem; color:#64748b; font-weight:600; text-transform:uppercase;">Reference ID</div>
              <div style="font-size:0.98rem; font-weight:700; color:#15803d; margin-top:2px;">${escapeHtml(p.reference_no || p.patient_id)}</div>
            </div>
            <div>
              <div style="font-size:0.72rem; color:#64748b; font-weight:600; text-transform:uppercase;">Appointment Recipient</div>
              <div style="font-size:0.86rem; font-weight:600; color:#334155; margin-top:2px;">${escapeHtml(recipientLabel)}${p.relationship_to_patient ? ' (' + escapeHtml(p.relationship_to_patient) + ')' : ''}</div>
            </div>
            <div>
              <div style="font-size:0.72rem; color:#64748b; font-weight:600; text-transform:uppercase;">Department</div>
              <div style="font-size:0.88rem; font-weight:700; color:#0f172a; margin-top:2px;">
                ${escapeHtml(deptDisplay)}
              </div>
            </div>
            <div>
              <div style="font-size:0.72rem; color:#64748b; font-weight:600; text-transform:uppercase;">Gender &amp; Age</div>
              <div style="font-size:0.86rem; font-weight:600; color:#334155; margin-top:2px;">${escapeHtml(p.gender || '-')} &bull; ${escapeHtml(p.age ?? '-')} yrs${p.birthdate ? ' (' + escapeHtml(p.birthdate) + ')' : ''}</div>
            </div>
            <div>
              <div style="font-size:0.72rem; color:#64748b; font-weight:600; text-transform:uppercase;">Contact &amp; Email</div>
              <div style="font-size:0.86rem; font-weight:600; color:#334155; margin-top:2px;">${escapeHtml(p.contact_number || 'Not provided')}${p.email ? ' &bull; ' + escapeHtml(p.email) : ''}</div>
            </div>
            <div>
              <div style="font-size:0.72rem; color:#64748b; font-weight:600; text-transform:uppercase;">Consultation Mode</div>
              <div style="font-size:0.86rem; font-weight:600; color:#334155; margin-top:2px;">${escapeHtml(isInquiry ? (p.mode_of_inquiry || 'Online Portal') : ((!p.consultation_mode || /^in-person$/i.test(p.consultation_mode)) ? 'Face-to-Face' : p.consultation_mode))}${isInquiry ? ' (Inquiry Only)' : ''}</div>
            </div>
            <div>
              <div style="font-size:0.72rem; color:#64748b; font-weight:600; text-transform:uppercase;">Preferred Date &amp; Time Slot</div>
              <div style="font-size:0.86rem; font-weight:600; color:#334155; margin-top:2px;">${escapeHtml(scheduleDisplay)}</div>
            </div>
            ${isTele ? '' : `
            <div>
              <div style="font-size:0.72rem; color:#64748b; font-weight:600; text-transform:uppercase;">Accompanying Companion</div>
              <div style="font-size:0.86rem; font-weight:600; color:#334155; margin-top:2px;">${companionLabel}</div>
            </div>
            `}
            <div>
              <div style="font-size:0.72rem; color:#64748b; font-weight:600; text-transform:uppercase;">Attending Physician</div>
              <div style="font-size:0.86rem; font-weight:600; color:#334155; margin-top:2px;">${escapeHtml(isInquiry ? 'General Inquiry' : (p.assigned_doctor || 'Unassigned'))}</div>
            </div>
          </div>

          ${isTele ? `
          <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:10px; padding:12px 14px; display:flex; flex-direction:column; gap:6px;">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; flex-wrap:wrap;">
              <span style="font-size:0.74rem; font-weight:700; color:#1e40af; text-transform:uppercase;">Teleconsultation Session</span>
              ${isLocked ? '' : `<button type="button" class="btn-clean outline sm" onclick="closePatientDetailsModal(); openScheduleAssistModal('${escapeHtml(p.patient_id)}')">${(telePlatform || safeTeleLink) ? 'Edit Teleconsult Link' : 'Set Teleconsult Link'}</button>`}
            </div>
            <div style="font-size:0.84rem; color:#0f172a;">
              <strong>Channel:</strong> ${escapeHtml(telePlatform || 'Not configured yet')}
            </div>
            ${safeTeleLink ? `<div style="font-size:0.82rem; word-break:break-all;"><strong>Link:</strong> <a href="${escapeHtml(safeTeleLink)}" target="_blank" rel="noopener noreferrer" style="color:#15803d; font-weight:600; text-decoration:underline;">${escapeHtml(safeTeleLink)}</a></div>` : ''}
            ${teleNotes ? `<div style="font-size:0.82rem; color:#334155;"><strong>Instructions:</strong> ${escapeHtml(teleNotes)}</div>` : ''}
          </div>
          ` : ''}

          <div>
            <div style="font-size:0.75rem; color:#64748b; font-weight:600; text-transform:uppercase; margin-bottom:4px;">Home Address</div>
            <div style="font-size:0.86rem; color:#0f172a;">${escapeHtml(p.address || 'Not specified')}</div>
          </div>

          <div>
            <div style="font-size:0.75rem; color:#64748b; font-weight:600; text-transform:uppercase; margin-bottom:4px;">${isInquiry ? 'Inquiry Question / Concern' : 'Chief Complaint / Reason for Consultation'}</div>
            <div style="font-size:0.88rem; color:#0f172a; background:#ffffff; border:1px solid #e2e8f0; border-radius:8px; padding:0.75rem;">
              ${escapeHtml(p.chief_complaints || 'General consultation')}
            </div>
          </div>

          <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.85rem;">
            <div>
              <div style="font-size:0.75rem; color:#64748b; font-weight:600; text-transform:uppercase; margin-bottom:4px;">Existing Medical Conditions</div>
              <div style="font-size:0.84rem; color:#0f172a; background:#ffffff; border:1px solid #e2e8f0; border-radius:8px; padding:0.65rem;">
                ${escapeHtml(p.existing_medical_conditions || 'None')}
              </div>
            </div>
            <div>
              <div style="font-size:0.75rem; color:#64748b; font-weight:600; text-transform:uppercase; margin-bottom:4px;">Current Medications</div>
              <div style="font-size:0.84rem; color:#0f172a; background:#ffffff; border:1px solid #e2e8f0; border-radius:8px; padding:0.65rem;">
                ${escapeHtml(formatMedicationsReadable(p.current_medications) || formatMedicationsReadable(p.active_medications) || 'None')}
              </div>
            </div>
          </div>

          ${(() => {
            let vitals = null;
            if (p.vitals_json) {
              try { vitals = typeof p.vitals_json === 'string' ? JSON.parse(p.vitals_json) : p.vitals_json; } catch (e) {}
            }
            const vItems = vitals ? [
              vitals.bp ? `BP: ${vitals.bp}` : '',
              vitals.temp ? `Temp: ${vitals.temp}°C` : '',
              vitals.pulse ? `PR: ${vitals.pulse} bpm` : '',
              vitals.rr ? `RR: ${vitals.rr}/min` : '',
              vitals.spo2 ? `SpO2: ${vitals.spo2}%` : '',
              vitals.weight ? `Wt: ${vitals.weight} kg` : ''
            ].filter(Boolean) : [];
            return vItems.length ? `
          <div>
            <div style="font-size:0.75rem; color:#64748b; font-weight:600; text-transform:uppercase; margin-bottom:4px;">Recorded Vital Signs</div>
            <div style="font-size:0.84rem; color:#0f172a; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:0.65rem 0.75rem; font-weight:600;">
              ${escapeHtml(vItems.join('  •  '))}
            </div>
          </div>` : '';
          })()}

          ${p.next_visit_date ? `
          <div>
            <div style="font-size:0.75rem; color:#64748b; font-weight:600; text-transform:uppercase; margin-bottom:4px;">Scheduled Follow-Up</div>
            <div style="font-size:0.85rem; color:#14532d; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:0.65rem 0.75rem;">
              <strong>${escapeHtml(p.next_visit_date)}</strong>${p.follow_up_notes ? ' — ' + escapeHtml(p.follow_up_notes) : ''}
            </div>
          </div>` : ''}

          <div>
            <div style="font-size:0.75rem; color:#64748b; font-weight:600; text-transform:uppercase; margin-bottom:4px;">Referral Source</div>
            <div style="font-size:0.86rem; color:#0f172a;">${escapeHtml(p.referral_source || 'None')}</div>
          </div>

          ${(() => {
            const rawNote = String(p.latest_progress_note?.note || '').trim();
            if (!rawNote) return '';
            const isInitialBookingLog = /^Online Appointment Booked\b/i.test(rawNote);
            const cleanedNote = (isInitialBookingLog && !inApprovalStatus(statusNorm))
              ? rawNote.replace(/\s*Status:\s*For Approval\.?\s*$/i, '')
              : rawNote;
            const noteHeading = isInitialBookingLog
              ? 'Initial Booking Note'
              : `Latest Progress / Resolution Note (${p.latest_progress_note.noted_by || 'Staff'})`;
            return `
          <div>
            <div style="font-size:0.75rem; color:#64748b; font-weight:600; text-transform:uppercase; margin-bottom:4px;">${escapeHtml(noteHeading)}</div>
            <div style="font-size:0.85rem; color:#0f172a; background:#f8fafc; border:1px solid #cbd5e1; border-radius:8px; padding:0.7rem;">
              ${escapeHtml(cleanedNote)}
            </div>
          </div>`;
          })()}

          <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:0.9rem 1rem;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
              <span style="font-size:0.75rem; color:#0f172a; font-weight:700; text-transform:uppercase;">
                Department &amp; Transfer
              </span>
              ${isCompleted
            ? `<span style="font-size:0.7rem; font-weight:600; color:#15803d; background:#dcfce7; border:1px solid #bbf7d0; padding:2px 8px; border-radius:999px;">${isInquiry ? 'Resolved' : 'Completed'}</span>`
            : (isCancelled
              ? '<span style="font-size:0.7rem; font-weight:600; color:#64748b; background:#f1f5f9; border:1px solid #cbd5e1; padding:2px 8px; border-radius:999px;">Cancelled</span>'
              : (isTransferred
                ? '<span style="font-size:0.7rem; font-weight:600; color:#6d28d9; background:#f5f3ff; border:1px solid #ddd6fe; padding:2px 8px; border-radius:999px;">Transferred</span>'
                : '<span style="font-size:0.7rem; font-weight:600; color:#15803d; background:#dcfce7; border:1px solid #bbf7d0; padding:2px 8px; border-radius:999px;">Active</span>'
              )
            )
          }
            </div>
            <div style="font-size:0.78rem; color:#64748b; margin-bottom:10px;">
              ${isCompleted
            ? (isInquiry ? 'This inquiry has been resolved and closed.' : 'This consultation is completed and finalized.')
            : (isCancelled
              ? 'This request is cancelled and cannot be transferred.'
              : (isTransferred
                ? 'This patient record has already been transferred to another department.'
                : 'If the patient booked under the wrong department or is unsure of their clinic, you can transfer them to another department.'
              )
            )
          }
            </div>
            <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;">
              <span class="table-dept-tag">${escapeHtml(deptDisplay)}</span>
              ${isLocked ? '' : `
              <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                ${isInquiry
              ? `<button type="button" class="btn-clean primary sm" onclick="closePatientDetailsModal(); openResolveInquiryModal('${escapeHtml(p.patient_id)}', '${escapeHtml(p.full_name)}')">Resolve Inquiry</button>`
              : (inApprovalStatus(statusNorm) ? `<button type="button" class="btn-clean primary sm" onclick="closePatientDetailsModal(); handleApprove('${escapeHtml(p.patient_id)}')">Approve</button>` : '')}
                <button type="button" class="btn-clean outline sm" onclick="closePatientDetailsModal(); openTransferModal('${escapeHtml(p.patient_id)}')">Transfer Department</button>
              </div>
              `}
            </div>
          </div>

          ${(p.medical_history_diagnosis || p.medical_history_prescription) ? `
          <div>
            <div style="font-size:0.75rem; color:#64748b; font-weight:600; text-transform:uppercase; margin-bottom:4px;">Diagnosis, Treatment &amp; Prescribed Medication</div>
            <div style="font-size:0.86rem; color:#0f172a; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:0.75rem; line-height:1.5;">
              ${p.medical_history_diagnosis ? `<div><strong>Diagnosis:</strong> ${escapeHtml(p.medical_history_diagnosis)}</div>` : ''}
              ${p.medical_history_treatment ? `<div><strong>Treatment Plan:</strong> ${escapeHtml(p.medical_history_treatment)}</div>` : ''}
              ${p.medical_history_prescription ? `<div style="margin-top:4px; white-space:pre-line;"><strong>Prescription:</strong> ${escapeHtml(p.medical_history_prescription)}</div>` : ''}
            </div>
          </div>
          ` : ''}

          ${completedHistory.length > 0 ? `
          <div>
            <div style="font-size:0.75rem; color:#0369a1; font-weight:700; text-transform:uppercase; margin-bottom:6px; display:flex; align-items:center; gap:6px;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
              <span>Previous Completed Appointments History (${completedHistory.length})</span>
            </div>
            <div style="display:flex; flex-direction:column; gap:6px; max-height:180px; overflow-y:auto;">
              ${completedHistory.map(prev => `
                <div style="background:#f8fafc; border:1px solid #cbd5e1; border-radius:8px; padding:8px 11px; font-size:0.82rem; display:flex; justify-content:space-between; align-items:flex-start; gap:8px;">
                  <div>
                    <div style="font-weight:700; color:#0f172a;">${escapeHtml(prev.date_formatted || prev.appointment_date)} &bull; <span style="color:#0369a1;">${escapeHtml(prev.time_formatted || '8:00 AM')}</span></div>
                    <div style="color:#475569; font-size:0.78rem; margin-top:2px;">${escapeHtml(prev.department || 'Clinic')} &bull; ${escapeHtml(prev.assigned_doctor || 'Attending Physician')} (${escapeHtml(prev.patient_id)})</div>
                    ${prev.diagnosis ? `<div style="color:#15803d; font-size:0.78rem; margin-top:2px;"><strong>Dx:</strong> ${escapeHtml(prev.diagnosis)}</div>` : ''}
                  </div>
                  <span style="font-size:0.7rem; font-weight:700; text-transform:uppercase; padding:2px 8px; border-radius:999px; background:#dcfce7; color:#15803d; border:1px solid #bbf7d0;">Completed</span>
                </div>
              `).join('')}
            </div>
          </div>
          ` : ''}
        `;
      } else {
        bodyEl.innerHTML = '<div style="text-align:center; color:#e11d48;">Unable to load patient details.</div>';
      }
    } catch (e) {
      bodyEl.innerHTML = '<div style="text-align:center; color:#e11d48;">Network error loading record.</div>';
    }
  };

  window.closePatientDetailsModal = function () {
    detailsModal.classList.remove('open');
  };

  // ── 12. Walk-in Modal ──
  const walkinModal = document.getElementById('walkinModal');
  window.openWalkinModal = function () {
    document.getElementById('walkinForm').reset();
    walkinModal.classList.add('open');
  };

  window.closeWalkinModal = function () {
    walkinModal.classList.remove('open');
  };

  window.submitWalkinPatient = async function () {
    const fullName = document.getElementById('walkinFullName').value.trim();
    const gender = document.getElementById('walkinGender').value;
    const birthdate = document.getElementById('walkinBirthdate').value;
    const contact = document.getElementById('walkinContact').value.trim();
    const address = document.getElementById('walkinAddress').value.trim();
    const complaint = document.getElementById('walkinComplaint').value.trim();

    if (!fullName || !gender || !complaint) {
      showToast('Please complete required fields.');
      return;
    }

    const todayStr = new Date().toISOString().split('T')[0];

    try {
      const res = await fetch('/sdn/api/registration.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          department: window.STAFF_DEPT || 'General Medical',
          full_name: fullName,
          gender,
          birthdate,
          contact_number: contact,
          address,
          chief_complaints: complaint,
          appointment_date: todayStr,
          unscheduled_walkin: 1,
          appointment_type: 'Walk-In',
          consultation_mode: 'Face-to-Face',
          inquiry_only: 0
        })
      }).then(r => r.json());

      if (res.success) {
        showToast('Walk-in patient admitted.');
        closeWalkinModal();
        loadData();
      } else {
        showToast(res.message || 'Error registering walk-in.');
      }
    } catch (e) {
      showToast('Network error.');
    }
  };

  // ── 13. Doctor Availability Modal ──
  const doctorModal = document.getElementById('doctorStatusModal');
  window.openDoctorStatusModal = async function () {
    const bodyEl = document.getElementById('doctorStatusList');
    bodyEl.innerHTML = '<div style="text-align:center; padding:1.5rem; color:#64748b;">Loading physicians...</div>';
    doctorModal.classList.add('open');

    try {
      const res = await fetch('/sdn/api/schedule.php?_=' + Date.now()).then(r => r.json());
      if (res.success && Array.isArray(res.data)) {
        const userDept = (window.STAFF_DEPT || '').toLowerCase();
        const docs = res.data.filter(d => {
          const dDept = (d.department || '').toLowerCase();
          return !userDept || dDept.includes(userDept) || userDept.includes(dDept);
        });

        if (!docs.length) {
          bodyEl.innerHTML = '<div style="text-align:center; color:#64748b;">No physicians assigned to this department.</div>';
          return;
        }

        bodyEl.innerHTML = docs.map(d => {
          const rawSt = (d.status || '').toLowerCase();
          const isIn = rawSt === 'in' || rawSt === 'in_clinic' || rawSt === 'available' || rawSt === 'on duty';
          return `
            <div style="display:flex; align-items:center; justify-content:space-between; padding:0.75rem 0; border-bottom:1px solid #f1f5f9;">
              <div>
                <div style="font-weight:650; color:#0f172a; font-size:0.9rem;">${escapeHtml(d.name)}</div>
                <div style="font-size:0.76rem; color:#64748b; margin-top:2px;">${escapeHtml(d.department || '')}</div>
              </div>
              <select class="select-filter doc-status-select ${isIn ? 'status-in' : 'status-out'}" onchange="updateDocStatus(${d.id}, this.value, this)">
                <option value="in" ${isIn ? 'selected' : ''}>IN</option>
                <option value="out" ${!isIn ? 'selected' : ''}>OUT</option>
              </select>
            </div>
          `;
        }).join('');
      }
    } catch (e) {
      bodyEl.innerHTML = '<div style="text-align:center; color:#e11d48;">Failed to load doctors.</div>';
    }
  };

  window.closeDoctorStatusModal = function () {
    doctorModal.classList.remove('open');
  };

  window.updateDocStatus = async function (docId, newStatus, selectEl) {
    if (selectEl) {
      if (newStatus === 'in') {
        selectEl.classList.remove('status-out');
        selectEl.classList.add('status-in');
      } else {
        selectEl.classList.remove('status-in');
        selectEl.classList.add('status-out');
      }
    }

    try {
      const res = await fetch('/sdn/api/schedule.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'set_status', doctor_id: docId, status: newStatus })
      }).then(r => r.json());

      if (res.success) {
        showToast(`Doctor marked as ${newStatus.toUpperCase()}.`);
        loadData();
      } else {
        showToast(res.message || 'Error updating status.');
      }
    } catch (e) {
      showToast('Error updating status.');
    }
  };

  // ── 14. Account Settings (Profile & Password) ──
  let pendingStaffAvatarData = null;
  let pendingStaffAvatarRemove = false;
  const staffAvatarInput = document.getElementById('staffSettingsAvatarInput');
  const staffAvatarPreview = document.getElementById('staffSettingsAvatarPreview');
  const staffAvatarRemoveBtn = document.getElementById('staffSettingsAvatarRemoveBtn');

  if (staffAvatarInput && staffAvatarPreview) {
    staffAvatarInput.addEventListener('change', (e) => {
      const file = e.target.files && e.target.files[0];
      if (!file) return;
      if (!['image/png', 'image/jpeg', 'image/webp'].includes(file.type)) {
        showToast('Please select a PNG, JPG, or WEBP image.');
        staffAvatarInput.value = '';
        return;
      }
      if (file.size > 5 * 1024 * 1024) {
        showToast('Image must be 5MB or less.');
        staffAvatarInput.value = '';
        return;
      }
      const reader = new FileReader();
      reader.onload = () => {
        pendingStaffAvatarData = reader.result;
        pendingStaffAvatarRemove = false;
        staffAvatarPreview.innerHTML = `<img src="${pendingStaffAvatarData}" alt="Preview" style="width:100%;height:100%;object-fit:cover;display:block;" />`;
        if (staffAvatarRemoveBtn) staffAvatarRemoveBtn.style.display = 'inline-flex';
      };
      reader.readAsDataURL(file);
    });
  }

  if (staffAvatarRemoveBtn && staffAvatarPreview) {
    staffAvatarRemoveBtn.addEventListener('click', () => {
      pendingStaffAvatarData = null;
      pendingStaffAvatarRemove = true;
      if (staffAvatarInput) staffAvatarInput.value = '';
      const nameVal = (document.getElementById('settingsFullName')?.value || 'ST').trim();
      const initials = nameVal.split(/\s+/).slice(0, 2).map(p => p[0]?.toUpperCase() || '').join('') || 'ST';
      staffAvatarPreview.textContent = initials;
      staffAvatarRemoveBtn.style.display = 'none';
    });
  }

  const btnSaveProfile = document.getElementById('btnSaveProfile');
  if (btnSaveProfile) {
    btnSaveProfile.addEventListener('click', async () => {
      const name = document.getElementById('settingsFullName').value.trim();
      if (!name) {
        showToast('Full name cannot be empty.');
        return;
      }
      try {
        const payload = {
          action: 'update_profile',
          user_id: window.STAFF_USER_ID,
          full_name: name
        };
        if (pendingStaffAvatarData) payload.image_data = pendingStaffAvatarData;
        if (pendingStaffAvatarRemove) payload.remove_image = true;

        const res = await fetch('/sdn/api/staff.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        }).then(r => r.json());

        if (res.success) {
          showToast('Profile saved.');
          pendingStaffAvatarData = null;
          pendingStaffAvatarRemove = false;
          const headerName = document.getElementById('headerStaffName');
          if (headerName) headerName.textContent = name;
          const headerCircle = document.getElementById('staffHeaderAvatarCircle');
          const imgUrl = res.profile_image || res.data?.profile_image || null;
          if (headerCircle) {
            if (imgUrl) {
              headerCircle.innerHTML = `<img src="${escapeHtml(imgUrl)}" alt="${escapeHtml(name)}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;" />`;
            } else if (payload.remove_image) {
              const initials = name.split(/\s+/).slice(0, 2).map(p => p[0]?.toUpperCase() || '').join('') || 'ST';
              headerCircle.textContent = initials;
            }
          }
        } else {
          showToast(res.message || 'Error updating profile.');
        }
      } catch (e) {
        showToast('Network error.');
      }
    });
  }

  const btnChangePassword = document.getElementById('btnChangePassword');
  if (btnChangePassword) {
    btnChangePassword.addEventListener('click', async () => {
      const pass = document.getElementById('settingsNewPassword').value;
      const confirmPass = document.getElementById('settingsConfirmPassword').value;

      if (!pass || pass.length < 6) {
        showToast('Password must be at least 6 characters.');
        return;
      }
      if (pass !== confirmPass) {
        showToast('Passwords do not match.');
        return;
      }

      try {
        const res = await fetch('/sdn/api/staff.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            action: 'update_profile',
            user_id: window.STAFF_USER_ID,
            password: pass
          })
        }).then(r => r.json());

        if (res.success) {
          showToast('Password updated.');
          document.getElementById('settingsNewPassword').value = '';
          document.getElementById('settingsConfirmPassword').value = '';
        } else {
          showToast(res.message || 'Error updating password.');
        }
      } catch (e) {
        showToast('Network error.');
      }
    });
  }

  // ── 14b. Patient QR ID Scanner ──
  let staffQrInstance = null;
  let staffQrScanning = false;

  const btnStartQrScan = document.getElementById('btnStartQrScan');
  const btnStopQrScan = document.getElementById('btnStopQrScan');
  const manualQrInput = document.getElementById('manualQrPatientId');
  const btnManualQrLookup = document.getElementById('btnManualQrLookup');
  const staffQrReaderEl = document.getElementById('staffQrReader');
  const staffQrStatusEl = document.getElementById('staffQrStatus');
  const staffQrResultEl = document.getElementById('staffQrResultCard');

  function setQrStatus(msg, isError) {
    if (!staffQrStatusEl) return;
    staffQrStatusEl.textContent = msg || '';
    staffQrStatusEl.style.display = msg ? 'block' : 'none';
    staffQrStatusEl.style.color = isError ? '#dc2626' : '#64748b';
  }

  let lastQrSearchMatches = [];

  function renderScannedPatientCard(p, allowBackToList = false) {
    if (!staffQrResultEl) return;
    const name = p.full_name || 'Unknown Patient';
    const pid = p.patient_id || p.id || 'N/A';
    const parts = name.trim().split(/\s+/);
    const initials = parts.length >= 2
      ? (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
      : name.slice(0, 2).toUpperCase();

    const avatarHtml = p.profile_image
      ? `<img src="${escapeHtml(p.profile_image)}" alt="${escapeHtml(name)}" style="width:72px;height:72px;border-radius:50%;object-fit:cover;border:2px solid #d1fae5;flex-shrink:0;" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"><div style="display:none;width:72px;height:72px;border-radius:50%;background:#10b981;color:#fff;font-weight:700;font-size:22px;align-items:center;justify-content:center;flex-shrink:0;">${escapeHtml(initials)}</div>`
      : `<div style="display:flex;width:72px;height:72px;border-radius:50%;background:#10b981;color:#fff;font-weight:700;font-size:22px;align-items:center;justify-content:center;flex-shrink:0;">${escapeHtml(initials)}</div>`;

    const genderAge = [p.gender, p.age ? `${p.age} yrs old` : ''].filter(Boolean).join(', ') || '—';
    const backBtnHtml = allowBackToList && lastQrSearchMatches.length > 1
      ? `<div style="margin-bottom:12px;"><button type="button" class="btn-clean outline" id="btnQrBackToResults" style="padding:5px 10px;font-size:12px;">&larr; Back to ${lastQrSearchMatches.length} matches</button></div>`
      : '';

    staffQrResultEl.innerHTML = `
      ${backBtnHtml}
      <div style="display:flex;align-items:center;gap:16px;padding-bottom:16px;border-bottom:1px solid #e2e8f0;margin-bottom:16px;flex-wrap:wrap;">
        ${avatarHtml}
        <div style="min-width:0;flex:1;">
          <div style="font-size:18px;font-weight:700;color:#0f172a;">${escapeHtml(name)}</div>
          <div style="display:inline-block;margin-top:4px;padding:3px 10px;border-radius:999px;background:#f1f5f9;color:#334155;font-size:12px;font-weight:600;border:1px solid #e2e8f0;">Patient ID: ${escapeHtml(String(pid))}</div>
        </div>
        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px;">
          <span style="font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.04em;">Latest Status</span>
          <div>${renderOverviewStatusPill(p.status || 'pending')}</div>
          ${p.department ? `<div style="font-size:12px;color:#475569;font-weight:500;">${escapeHtml(p.department)}</div>` : ''}
        </div>
      </div>
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px 20px;font-size:13px;">
        <div><div style="color:#64748b;font-size:11px;text-transform:uppercase;letter-spacing:0.04em;">Gender &amp; Age</div><div style="color:#0f172a;font-weight:500;margin-top:2px;">${escapeHtml(genderAge)}</div></div>
        <div><div style="color:#64748b;font-size:11px;text-transform:uppercase;letter-spacing:0.04em;">Birthdate</div><div style="color:#0f172a;font-weight:500;margin-top:2px;">${escapeHtml(p.birthdate || '—')}</div></div>
        <div><div style="color:#64748b;font-size:11px;text-transform:uppercase;letter-spacing:0.04em;">Contact Number</div><div style="color:#0f172a;font-weight:500;margin-top:2px;">${escapeHtml(p.contact_number || '—')}</div></div>
        <div><div style="color:#64748b;font-size:11px;text-transform:uppercase;letter-spacing:0.04em;">Email Address</div><div style="color:#0f172a;font-weight:500;margin-top:2px;">${escapeHtml(p.email || '—')}</div></div>
        <div style="grid-column:1 / -1;"><div style="color:#64748b;font-size:11px;text-transform:uppercase;letter-spacing:0.04em;">Home Address</div><div style="color:#0f172a;font-weight:500;margin-top:2px;">${escapeHtml(p.address || '—')}</div></div>
      </div>
    `;
    staffQrResultEl.style.display = 'block';

    const backBtn = document.getElementById('btnQrBackToResults');
    if (backBtn) {
      backBtn.addEventListener('click', () => renderPatientSearchList(lastQrSearchMatches));
    }
  }

  function renderPatientSearchList(matches) {
    if (!staffQrResultEl) return;
    const rowsHtml = matches.map((p, idx) => {
      const name = p.full_name || 'Unknown Patient';
      const pid = p.patient_id || p.id || 'N/A';
      const parts = name.trim().split(/\s+/);
      const initials = parts.length >= 2
        ? (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
        : name.slice(0, 2).toUpperCase();
      const avatarHtml = p.profile_image
        ? `<img src="${escapeHtml(p.profile_image)}" alt="${escapeHtml(name)}" style="width:40px;height:40px;border-radius:50%;object-fit:cover;border:1px solid #d1fae5;flex-shrink:0;" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"><div style="display:none;width:40px;height:40px;border-radius:50%;background:#15803d;color:#fff;font-weight:700;font-size:13px;align-items:center;justify-content:center;flex-shrink:0;">${escapeHtml(initials)}</div>`
        : `<div style="display:flex;width:40px;height:40px;border-radius:50%;background:#15803d;color:#fff;font-weight:700;font-size:13px;align-items:center;justify-content:center;flex-shrink:0;">${escapeHtml(initials)}</div>`;
      const meta = [pid, p.birthdate || '', p.department || ''].filter(Boolean).join(' · ');

      return `
        <button type="button" class="qr-match-row" data-match-idx="${idx}" style="width:100%;display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 12px;border:1px solid #e2e8f0;border-radius:10px;background:#ffffff;text-align:left;cursor:pointer;transition:border-color 0.14s ease,background-color 0.14s ease;">
          <div style="display:flex;align-items:center;gap:12px;min-width:0;">
            ${avatarHtml}
            <div style="min-width:0;">
              <div style="font-size:14px;font-weight:600;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escapeHtml(name)}</div>
              <div style="font-size:12px;color:#64748b;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escapeHtml(meta)}</div>
            </div>
          </div>
          <div style="flex-shrink:0;">${renderOverviewStatusPill(p.status || 'pending')}</div>
        </button>
      `;
    }).join('');

    staffQrResultEl.innerHTML = `
      <div style="font-size:12px;font-weight:600;color:#475569;margin-bottom:10px;">Select a patient (${matches.length} found)</div>
      <div style="display:flex;flex-direction:column;gap:8px;max-height:320px;overflow-y:auto;">${rowsHtml}</div>
    `;
    staffQrResultEl.style.display = 'block';

    staffQrResultEl.querySelectorAll('.qr-match-row').forEach(btn => {
      btn.addEventListener('click', () => {
        const idx = parseInt(btn.getAttribute('data-match-idx'), 10);
        const chosen = matches[idx];
        if (chosen) {
          renderScannedPatientCard(chosen, true);
          setQrStatus(`Selected: ${chosen.full_name || chosen.patient_id}`, false);
        }
      });
    });
  }

  async function lookupScannedPatient(rawId) {
    const pid = String(rawId || '').trim();
    if (!pid) {
      setQrStatus('Scan a valid Patient QR code.', true);
      return;
    }
    setQrStatus(`Looking up ${pid}...`, false);
    try {
      const res = await fetch(`/sdn/api/patients.php?id=${encodeURIComponent(pid)}`).then(r => r.json());
      if (res && res.success && res.data) {
        lastQrSearchMatches = [res.data];
        renderScannedPatientCard(res.data, false);
        setQrStatus(`Verified: ${res.data.full_name || pid}`, false);
        showToast('Patient record loaded.');
      } else {
        if (staffQrResultEl) staffQrResultEl.style.display = 'none';
        setQrStatus((res && res.message) || `No patient found for ID "${pid}".`, true);
        showToast('Patient ID not found.');
      }
    } catch (e) {
      setQrStatus('Network error while looking up patient.', true);
    }
  }

  async function searchPatientsByName(rawQuery) {
    const q = String(rawQuery || '').trim();
    if (!q) {
      lastQrSearchMatches = [];
      if (staffQrResultEl) staffQrResultEl.style.display = 'none';
      setQrStatus('Enter a patient name to search.', true);
      return;
    }
    setQrStatus(`Searching for "${q}"...`, false);
    try {
      const res = await fetch(`/sdn/api/patients.php?q=${encodeURIComponent(q)}`).then(r => r.json());
      const matches = (res && res.success && Array.isArray(res.data)) ? res.data : [];
      lastQrSearchMatches = matches;

      if (matches.length === 0) {
        if (staffQrResultEl) staffQrResultEl.style.display = 'none';
        setQrStatus(`No patients found matching "${q}".`, true);
      } else if (matches.length === 1) {
        renderScannedPatientCard(matches[0], false);
        setQrStatus(`Found: ${matches[0].full_name || q}`, false);
      } else {
        renderPatientSearchList(matches);
        setQrStatus(`Found ${matches.length} patients matching "${q}".`, false);
      }
    } catch (e) {
      setQrStatus('Network error while searching patients.', true);
    }
  }

  async function stopStaffQrScanner() {
    if (!staffQrInstance || !staffQrScanning) {
      if (staffQrReaderEl) staffQrReaderEl.style.display = 'none';
      if (btnStartQrScan) btnStartQrScan.style.display = '';
      if (btnStopQrScan) btnStopQrScan.style.display = 'none';
      return;
    }
    staffQrScanning = false;
    try {
      await staffQrInstance.stop();
      staffQrInstance.clear();
    } catch (_) {
      // ignore stop errors when camera already released
    }
    if (staffQrReaderEl) staffQrReaderEl.style.display = 'none';
    if (btnStartQrScan) btnStartQrScan.style.display = '';
    if (btnStopQrScan) btnStopQrScan.style.display = 'none';
  }

  async function startStaffQrScanner() {
    if (typeof Html5Qrcode === 'undefined') {
      setQrStatus('QR scanner library failed to load. Use manual lookup.', true);
      return;
    }
    if (staffQrScanning) return;
    if (staffQrReaderEl) staffQrReaderEl.style.display = 'block';
    if (btnStartQrScan) btnStartQrScan.style.display = 'none';
    if (btnStopQrScan) btnStopQrScan.style.display = '';
    setQrStatus('Starting camera...', false);

    try {
      staffQrInstance = staffQrInstance || new Html5Qrcode('staffQrReader');
      staffQrScanning = true;
      await staffQrInstance.start(
        { facingMode: 'environment' },
        { fps: 10, qrbox: { width: 220, height: 220 } },
        async (decodedText) => {
          const scanned = String(decodedText || '').trim();
          if (!scanned) return;
          await stopStaffQrScanner();
          if (manualQrInput) manualQrInput.value = scanned;
          lookupScannedPatient(scanned);
        },
        () => {}
      );
      setQrStatus('Camera active. Point at patient QR code.', false);
    } catch (err) {
      staffQrScanning = false;
      if (staffQrReaderEl) staffQrReaderEl.style.display = 'none';
      if (btnStartQrScan) btnStartQrScan.style.display = '';
      if (btnStopQrScan) btnStopQrScan.style.display = 'none';
      setQrStatus('Unable to access camera. Check permissions or use manual lookup.', true);
    }
  }

  if (btnStartQrScan) btnStartQrScan.addEventListener('click', startStaffQrScanner);
  if (btnStopQrScan) btnStopQrScan.addEventListener('click', () => {
    stopStaffQrScanner();
    setQrStatus('Camera stopped.', false);
  });
  if (btnManualQrLookup) btnManualQrLookup.addEventListener('click', () => {
    searchPatientsByName(manualQrInput ? manualQrInput.value : '');
  });
  if (manualQrInput) {
    let qrSearchTimer = null;
    manualQrInput.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        clearTimeout(qrSearchTimer);
        searchPatientsByName(manualQrInput.value);
      }
    });
    manualQrInput.addEventListener('input', () => {
      clearTimeout(qrSearchTimer);
      const val = manualQrInput.value.trim();
      if (!val) {
        lastQrSearchMatches = [];
        if (staffQrResultEl) staffQrResultEl.style.display = 'none';
        setQrStatus('', false);
        return;
      }
      if (val.length >= 2) {
        qrSearchTimer = setTimeout(() => searchPatientsByName(val), 250);
      }
    });
  }

  // ── 15. CSV Export ──
  window.exportRecordsCSV = function () {
    if (!allPatients.length) {
      showToast('No records to export.');
      return;
    }
    const headers = ['Patient ID', 'Full Name', 'Gender', 'Contact', 'Department', 'Visit Date', 'Time', 'Physician', 'Status'];
    const rows = allPatients.map(p => [
      p.patient_id,
      `"${(p.full_name || '').replace(/"/g, '""')}"`,
      p.gender || '',
      `"${p.contact_number || ''}"`,
      p.department || '',
      p.appointment_date || '',
      p.scheduled_time || '',
      `"${(p.assigned_doctor || '').replace(/"/g, '""')}"`,
      p.status || ''
    ]);

    const csv = 'data:text/csv;charset=utf-8,' + [headers.join(','), ...rows.map(r => r.join(','))].join('\n');
    const link = document.createElement('a');
    link.setAttribute('href', encodeURI(csv));
    link.setAttribute('download', `mmwgh_records_${new Date().toISOString().split('T')[0]}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  // ── 16. Filter Event Bindings ──
  document.querySelectorAll('.consult-pill-filter').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.consult-pill-filter').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      consultStatus = btn.dataset.status || 'all';
      renderConsultations();
    });
  });

  const inputConsultSearch = document.getElementById('consultSearchInput');
  if (inputConsultSearch) {
    inputConsultSearch.addEventListener('input', (e) => {
      consultSearch = e.target.value.trim();
      renderConsultations();
    });
  }

  const inputRecordsSearch = document.getElementById('recordsSearchInput');
  if (inputRecordsSearch) {
    inputRecordsSearch.addEventListener('input', (e) => {
      recordsSearch = e.target.value.trim();
      renderRecords();
    });
  }

  const selectRecordsSort = document.getElementById('recordsSortSelect');
  if (selectRecordsSort) {
    selectRecordsSort.addEventListener('change', (e) => {
      recordsSort = e.target.value;
      renderRecords();
    });
  }

  const selectRecordsStatus = document.getElementById('recordsStatusSelect');
  if (selectRecordsStatus) {
    selectRecordsStatus.addEventListener('change', (e) => {
      recordsStatus = e.target.value;
      renderRecords();
    });
  }

  const btnRefresh = document.getElementById('btnRefreshConsult');
  if (btnRefresh) {
    btnRefresh.addEventListener('click', () => {
      loadData();
      showToast('Queue refreshed.');
    });
  }

  const inputGlobalSearch = document.getElementById('globalStaffSearchInput');
  if (inputGlobalSearch) {
    inputGlobalSearch.addEventListener('input', (e) => {
      const val = e.target.value.trim();
      if (currentView === 'records') {
        recordsSearch = val;
        if (inputRecordsSearch) inputRecordsSearch.value = val;
        renderRecords();
      } else if (currentView === 'schedules') {
        schedSearch = val;
        const schedInp = document.getElementById('schedSearchInput');
        if (schedInp) schedInp.value = val;
        renderDoctorSchedules();
      } else {
        consultSearch = val;
        if (inputConsultSearch) inputConsultSearch.value = val;
        if (currentView !== 'consultation' && val.length > 0) {
          switchView('consultation');
        } else {
          renderConsultations();
        }
      }
    });
    inputGlobalSearch.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' && currentView === 'overview') {
        e.preventDefault();
        const val = e.target.value.trim();
        consultSearch = val;
        if (inputConsultSearch) inputConsultSearch.value = val;
        switchView('consultation');
      }
    });
  }

  const inputSchedSearch = document.getElementById('schedSearchInput');
  if (inputSchedSearch) {
    inputSchedSearch.addEventListener('input', (e) => {
      schedSearch = e.target.value.trim();
      renderDoctorSchedules();
    });
  }

  const selectSchedSpec = document.getElementById('schedSpecFilter');
  if (selectSchedSpec) {
    selectSchedSpec.addEventListener('change', (e) => {
      schedSpec = e.target.value;
      renderDoctorSchedules();
    });
  }

  const selectSchedStatus = document.getElementById('schedStatusFilter');
  if (selectSchedStatus) {
    selectSchedStatus.addEventListener('change', (e) => {
      schedStatus = e.target.value;
      renderDoctorSchedules();
    });
  }

  const selectSchedSort = document.getElementById('schedSortSelect');
  if (selectSchedSort) {
    selectSchedSort.addEventListener('change', (e) => {
      schedSort = e.target.value;
      renderDoctorSchedules();
    });
  }

  function escapeHtml(str) {
    if (!str) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function ucFirst(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
  }

  loadData();

  window.openResolveInquiryModal = function (patientId, patientName) {
    const existing = document.getElementById('staffResolveInquiryModal');
    if (existing) existing.remove();

    const modal = document.createElement('div');
    modal.id = 'staffResolveInquiryModal';
    modal.className = 'clean-modal-backdrop open';
    modal.style.zIndex = '99999';
    modal.innerHTML = `
      <div class="clean-modal-box" style="max-width:480px;">
        <div class="clean-modal-header">
          <div>
            <h3>Resolve Patient Inquiry</h3>
            <p style="font-size:0.78rem; color:#64748b; margin-top:2px;">Mark inquiry for ${escapeHtml(patientName || patientId)} as resolved</p>
          </div>
          <button type="button" class="clean-modal-close" id="staffResolveClose">&times;</button>
        </div>
        <div class="clean-modal-body" style="display:flex; flex-direction:column; gap:0.75rem;">
          <div class="form-group-clean">
            <label for="staffInquiryResolutionNote">Resolution Remarks (Optional)</label>
            <textarea id="staffInquiryResolutionNote" class="form-control-clean" rows="3" placeholder="e.g. Inquirer contacted; consultation schedule and clinic requirements explained."></textarea>
          </div>
        </div>
        <div class="clean-modal-footer">
          <button type="button" class="btn-clean outline" id="staffResolveCancel">Cancel</button>
          <button type="button" class="btn-clean primary" id="staffResolveConfirm">Resolve Inquiry</button>
        </div>
      </div>
    `;
    document.body.appendChild(modal);

    const cleanup = () => modal.remove();
    modal.querySelector('#staffResolveClose')?.addEventListener('click', cleanup);
    modal.querySelector('#staffResolveCancel')?.addEventListener('click', cleanup);

    const btnConfirm = modal.querySelector('#staffResolveConfirm');
    btnConfirm?.addEventListener('click', async () => {
      const note = (modal.querySelector('#staffInquiryResolutionNote')?.value || '').trim();
      btnConfirm.disabled = true;
      btnConfirm.textContent = 'Resolving...';
      try {
        const res = await fetch('/sdn/api/patients.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'completed', patient_id: patientId, note })
        }).then(r => r.json());
        if (res && res.success) {
          cleanup();
          showToast('Inquiry resolved and completed.');
          loadData();
        } else {
          showToast(res?.message || 'Could not resolve inquiry.', 2800, 'error');
          btnConfirm.disabled = false;
          btnConfirm.textContent = 'Resolve Inquiry';
        }
      } catch (e) {
        showToast('Server error resolving inquiry.', 2800, 'error');
        btnConfirm.disabled = false;
        btnConfirm.textContent = 'Resolve Inquiry';
      }
    });
  };
});

window.closeAllDocPickers = function () {
  document.querySelectorAll('.doc-picker-pop.open').forEach(el => el.classList.remove('open'));
  document.querySelectorAll('.doc-picker-trigger[aria-expanded="true"]').forEach(el => el.setAttribute('aria-expanded', 'false'));
};

window.toggleDocPicker = function (event, btn, forceOpen) {
  if (event) {
    event.preventDefault();
    event.stopPropagation();
  }
  if (!btn || !btn.parentElement) return;
  const pop = btn.parentElement.querySelector('.doc-picker-pop');
  if (!pop) return;
  const willOpen = forceOpen ? true : !pop.classList.contains('open');

  document.querySelectorAll('.row-menu-pop.open').forEach(el => el.classList.remove('open'));
  document.querySelectorAll('.row-menu-btn[aria-expanded="true"]').forEach(el => el.setAttribute('aria-expanded', 'false'));
  window.closeAllDocPickers();
  if (!willOpen) return;

  const rect = btn.getBoundingClientRect();
  const popWidth = 310;
  pop.style.position = 'fixed';
  pop.style.width = popWidth + 'px';
  pop.style.left = Math.min(Math.max(8, rect.left), window.innerWidth - popWidth - 8) + 'px';

  pop.classList.add('open');
  const popHeight = Math.min(pop.scrollHeight || 280, 340);
  if (rect.bottom + 6 + popHeight > window.innerHeight - 12 && rect.top - 6 - popHeight > 12) {
    pop.style.top = (rect.top - popHeight - 6) + 'px';
  } else {
    pop.style.top = (rect.bottom + 6) + 'px';
  }
  btn.setAttribute('aria-expanded', 'true');
};

window.toggleRowMenu = function (event, btn) {
  event.preventDefault();
  event.stopPropagation();
  const pop = btn.parentElement.querySelector('.row-menu-pop');
  if (!pop) return;
  const willOpen = !pop.classList.contains('open');
  window.closeAllDocPickers();
  document.querySelectorAll('.row-menu-pop.open').forEach(el => el.classList.remove('open'));
  document.querySelectorAll('.row-menu-btn[aria-expanded="true"]').forEach(el => el.setAttribute('aria-expanded', 'false'));
  if (!willOpen) return;
  const rect = btn.getBoundingClientRect();
  pop.style.position = 'fixed';
  pop.style.top = (rect.bottom + 6) + 'px';
  pop.style.left = Math.max(8, rect.right - 176) + 'px';
  pop.classList.add('open');
  btn.setAttribute('aria-expanded', 'true');
};

document.addEventListener('click', () => {
  window.closeAllDocPickers();
  document.querySelectorAll('.row-menu-pop.open').forEach(el => el.classList.remove('open'));
  document.querySelectorAll('.row-menu-btn[aria-expanded="true"]').forEach(el => el.setAttribute('aria-expanded', 'false'));
});
