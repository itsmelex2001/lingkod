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
  function showToast(msg, duration = 2800, tone = 'success') {
    if (!toastEl) return;
    const t = tone === 'error' || tone === 'warning' || tone === 'info' ? tone : 'success';
    toastEl.classList.remove('is-error', 'is-warning', 'is-info', 'is-success');
    toastEl.classList.add(`is-${t}`);
    const icons = {
      success: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>',
      error: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
      warning: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
      info: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>'
    };
    toastEl.innerHTML = `<span class="toast-clean-mark" aria-hidden="true">${icons[t]}</span><span>${escapeHtml(String(msg || ''))}</span>`;
    toastEl.classList.add('is-visible');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
      toastEl.classList.remove('is-visible');
    }, duration);
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

  // ── 5. KPI, Notifications & Overview Renderer ──
  function updateKPIsAndPillCounts() {
    const todayStr = new Date().toISOString().split('T')[0];

    const todayVisits = allPatients.filter(p => (p.appointment_date === todayStr) || (p.created_at && p.created_at.startsWith(todayStr))).length;
    const forApproval = allPatients.filter(p => inApprovalStatus(p.status)).length;
    const inquiries   = allPatients.filter(p => isInquiryRecord(p)).length;
    const confirmed   = allPatients.filter(p => isConfirmedStatus(p.status)).length;
    const inClinic    = allPatients.filter(p => isInClinicStatus(p.status)).length;
    const completed   = allPatients.filter(p => (p.status || '').toLowerCase() === 'completed').length;
    const transferred = allPatients.filter(p => (p.status || '').toLowerCase() === 'transferred').length;
    const cancelled   = allPatients.filter(p => isCancelledStatus(p.status)).length;

    const setElText = (id, val) => {
      const el = document.getElementById(id);
      if (el) el.textContent = val;
    };

    setElText('kpiTotal', todayVisits);
    setElText('kpiPending', forApproval);
    setElText('kpiQueue', confirmed + inClinic);
    setElText('kpiCompleted', completed);

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

    const pending = allPatients.filter(p => inApprovalStatus(p.status));
    const active = allPatients.filter(p => isConfirmedStatus(p.status) || isInClinicStatus(p.status));
    const rows = [...pending, ...active].slice(0, 8);

    if (!rows.length) {
      tbody.innerHTML = '<tr><td colspan="4" class="modern-table-empty">No pending appointment requests or active queue items right now.</td></tr>';
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

  function populateScheduleSpecializations() {
    const selectEl = document.getElementById('schedSpecFilter');
    if (!selectEl) return;
    const specs = [...new Set(
      (allDoctors || [])
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

  function renderDoctorSchedules() {
    const container = document.getElementById('doctorSchedulesContainer');
    if (!container) return;

    const docs = allDoctors || [];
    const setText = (id, val) => {
      const el = document.getElementById(id);
      if (el) el.textContent = val;
    };

    const genDocs = docs.filter(d => normalizeSchedDept(d.department) === 'general');
    const neuroDocs = docs.filter(d => normalizeSchedDept(d.department) === 'neurology');
    const psychDocs = docs.filter(d => normalizeSchedDept(d.department) === 'psychiatry');

    setText('schedCountAll', docs.length);
    setText('schedCountGeneral', genDocs.length);
    setText('schedCountNeurology', neuroDocs.length);
    setText('schedCountPsychiatry', psychDocs.length);

    const availTotal = docs.filter(d => isDocAvailable(d.status)).length;
    setText('schedSummaryAvailable', `${availTotal} Available`);
    setText('schedSummaryOut', `${docs.length - availTotal} Out`);

    populateScheduleSpecializations();

    // Enrich with today's load and normalized fields
    let filtered = docs.map(d => {
      const deptKey = normalizeSchedDept(d.department);
      const isIn = isDocAvailable(d.status);
      const totalSlots = Math.min(100, Math.max(1, parseInt(d.total_slots, 10) || 20));
      const todayLoad = getDoctorTodayLoad(d.name);
      return Object.assign({}, d, {
        _deptKey: deptKey,
        _isIn: isIn,
        _totalSlots: totalSlots,
        _todayLoad: todayLoad
      });
    });

    if (schedDept !== 'all') {
      filtered = filtered.filter(d => d._deptKey === schedDept);
    }

    if (schedSearch) {
      const q = schedSearch.toLowerCase();
      filtered = filtered.filter(d =>
        (d.name || '').toLowerCase().includes(q) ||
        (d.specialization || '').toLowerCase().includes(q) ||
        formatDeptLabel(d.department).toLowerCase().includes(q)
      );
    }

    if (schedSpec !== 'all') {
      filtered = filtered.filter(d => String(d.specialization || '').trim() === schedSpec);
    }

    if (schedStatus !== 'all') {
      filtered = filtered.filter(d => (schedStatus === 'in' ? d._isIn : !d._isIn));
    }

    filtered.sort((a, b) => {
      if (schedSort === 'name_asc') {
        return (a.name || '').localeCompare(b.name || '');
      }
      if (schedSort === 'name_desc') {
        return (b.name || '').localeCompare(a.name || '');
      }
      if (schedSort === 'status_in') {
        if (a._isIn !== b._isIn) return a._isIn ? -1 : 1;
        return (a.name || '').localeCompare(b.name || '');
      }
      if (schedSort === 'slots_desc') {
        if (b._totalSlots !== a._totalSlots) return b._totalSlots - a._totalSlots;
        return (a.name || '').localeCompare(b.name || '');
      }
      if (schedSort === 'load_desc') {
        if (b._todayLoad !== a._todayLoad) return b._todayLoad - a._todayLoad;
        return (a.name || '').localeCompare(b.name || '');
      }
      return (a.name || '').localeCompare(b.name || '');
    });

    if (!filtered.length) {
      container.innerHTML = `
        <div class="dept-schedule-card">
          <div class="modern-table-empty">
            <div>No doctor schedules match the selected department or filters.</div>
            <button type="button" class="modern-reset-btn" style="margin-top:12px;" onclick="resetScheduleFilters()">Reset Filters</button>
          </div>
        </div>
      `;
      return;
    }

    const deptOrder = [
      { key: 'general', title: 'General Medical' },
      { key: 'neurology', title: 'Neurology' },
      { key: 'psychiatry', title: 'Psychiatry' },
      { key: 'other', title: 'Other Departments' }
    ];

    const sectionsHtml = deptOrder.map(group => {
      const groupDocs = filtered.filter(d => d._deptKey === group.key);
      if (!groupDocs.length) return '';

      const availCount = groupDocs.filter(d => d._isIn).length;
      const totalSlotsSum = groupDocs.reduce((acc, d) => acc + d._totalSlots, 0);
      const totalLoadSum = groupDocs.reduce((acc, d) => acc + d._todayLoad, 0);

      const rowsHtml = groupDocs.map(d => {
        const docName = d.name && d.name.startsWith('Dr.') ? d.name : `Dr. ${d.name || 'Physician'}`;
        const spec = d.specialization || 'General Practice';
        const startFmt = d.schedule_start_fmt || formatTime12(d.schedule_start);
        const endFmt = d.schedule_end_fmt || formatTime12(d.schedule_end);
        const hours = (startFmt && endFmt) ? `${startFmt} – ${endFmt}` : '8:00 AM – 5:00 PM';
        const pct = Math.min(100, Math.round((d._todayLoad / d._totalSlots) * 100));

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
                <span class="sched-cap-text"><strong>${d._todayLoad}</strong> <span class="capacity-total">/ ${d._totalSlots}</span></span>
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

      return `
        <div class="dept-schedule-card">
          <div class="dept-card-head">
            <div class="dept-head-left">
              <h3>${group.title}</h3>
              <span class="dept-head-meta">${groupDocs.length} physician${groupDocs.length === 1 ? '' : 's'} &middot; ${availCount} available</span>
            </div>
            <span class="dept-cap-pill">${totalLoadSum} / ${totalSlotsSum} slots booked today</span>
          </div>
          <div class="modern-table-scroll sched-table-scroll">
            <table class="modern-table sched-table">
              <thead class="modern-thead">
                <tr>
                  <th>Physician</th>
                  <th>Specialization</th>
                  <th>Schedule Hours</th>
                  <th>Daily Load / Capacity</th>
                  <th>Status</th>
                  <th class="col-actions">Availability</th>
                </tr>
              </thead>
              <tbody>${rowsHtml}</tbody>
            </table>
          </div>
        </div>
      `;
    }).join('');

    container.innerHTML = sectionsHtml;
  }

  // ── 6. Consultations View Renderer ──
  function renderConsultations() {
    const tbody = document.getElementById('consultationsTableBody');
    if (!tbody) return;

    const filtered = allPatients.filter(p => {
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
      const typeTag = isInquiry
        ? '<span class="patient-type-badge" style="background:#fef3c7;color:#92400e;border:1px solid #fde68a;">Inquiry Only</span>'
        : (isOnline
          ? '<span class="patient-type-badge online">Online</span>'
          : '<span class="patient-type-badge walkin">Walk-in</span>');

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
        if (!isInquiry && isForApproval) {
          menuItems += `<button type="button" class="row-menu-item" onclick="openScheduleAssistModal('${escapeHtml(p.patient_id)}')">Call &amp; Reschedule</button>`;
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

  // ── 7. Records View Renderer (With Search & Sorting) ──
  function renderRecords() {
    const tbody = document.getElementById('recordsTableBody');
    if (!tbody) return;

    const filtered = allPatients.filter(p => {
      if ((p.status || '').toLowerCase() !== 'completed') return false;

      if (recordsSearch) {
        const q = recordsSearch.toLowerCase();
        const mName = (p.full_name || '').toLowerCase().includes(q);
        const mId = (p.patient_id || '').toLowerCase().includes(q) || (p.reference_no || '').toLowerCase().includes(q);
        const mHrn = (p.hrn || '').toLowerCase().includes(q);
        const mDoc = (p.assigned_doctor || '').toLowerCase().includes(q);
        const mComplaint = (p.chief_complaints || '').toLowerCase().includes(q);
        if (!mName && !mId && !mHrn && !mDoc && !mComplaint) return false;
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

      const prevRecBadge = p.previous_appointment_date
        ? `<div class="date-stack-sub">Previous ${escapeHtml(p.previous_appointment_date)}${p.previous_appointment_time ? ' · ' + escapeHtml(p.previous_appointment_time) : ''}</div>`
        : '';

      return `
        <tr>
          <td>
            <div class="patient-cell-name">${escapeHtml(p.full_name || 'Patient')}</div>
            <div class="patient-cell-meta">${escapeHtml(p.patient_id)} · ${escapeHtml(p.gender || '-')}</div>
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
        if (rowTrigger) {
          rowTrigger.focus();
          rowTrigger.classList.add('is-required-highlight');
          setTimeout(() => rowTrigger.classList.remove('is-required-highlight'), 2200);
          toggleDocPicker(null, rowTrigger, true);
        }
        showToast('Please assign an Attending Physician based on available time slots before approving.', 3600, 'error');
        return;
      }

      const assignedEntry = deptAvail.evaluated.find(item => item.normName === assignedNorm);
      if (assignedEntry && !assignedEntry.eval.isAvailable) {
        showToast(`${assignedEntry.eval.reason}. Call the patient to confirm an available schedule.`, 4000, 'error');
        openScheduleAssistModal(patientId, assignedEntry.doc.name);
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

    refreshScheduleAssistOptions(preselectDoctorName || p.assigned_doctor || '');
    scheduleAssistModal.classList.add('open');
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
    const parsedDate = new Date(newDate + 'T00:00:00');
    if (!isNaN(parsedDate.getTime()) && parsedDate.getDay() === 0) {
      showToast('Clinics are closed on Sundays. Please pick a Monday to Saturday date.', 3200, 'error');
      return;
    }
    if (!doctorName) {
      showToast('Please select an available attending physician for that day and time block.', 3200, 'error');
      return;
    }

    const btnSave = document.getElementById('btnSaSaveOnly');
    const btnApprove = document.getElementById('btnSaSaveApprove');
    if (btnSave) btnSave.disabled = true;
    if (btnApprove) btnApprove.disabled = true;

    try {
      const res = await fetch('/sdn/api/patients.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'reschedule_assign',
          patient_id: patientId,
          doctor: doctorName,
          appointment_date: newDate,
          preferred_time_slot: slotVal,
          approve_now: Boolean(approveNow)
        })
      }).then(r => r.json());

      if (res && res.success) {
        showToast(res.message || 'Schedule and physician updated.');
        closeScheduleAssistModal();
        loadData();
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
        { name: 'ARIPIPRAZOLE 10 mg tablet', dosage: '1 tab OD in the morning with food', quantity: '30 tabs' }
      ]
    },
    {
      diagnosis: 'Bipolar I / II Disorder',
      department: 'OPU Psychiatry',
      treatment_plan: 'Mood charting, regular sleep-wake schedule, mood stabilizer adherence, and periodic serum level / renal-thyroid monitoring.',
      medicine_name: 'VALPROIC ACID 500 mg controlled release tablet',
      dosage_instructions: '1 tab BID with meals (bipolar stabilization)',
      default_quantity: '60 tabs',
      suggested_medicines: [
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
  }

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
    const selectedMed = getSelectedMedicineMeta();
    const medicineName = selectedMed ? selectedMed.name : '';
    const dosage = doseInput ? doseInput.value.trim() : '';
    const quantity = qtyInput ? qtyInput.value.trim() : '';

    if (!diagnosis) {
      showToast('Please enter a clinical diagnosis.');
      return;
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

      await fetch('/sdn/api/patients.php', {
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
          quantity
        })
      });

      showToast('Consultation completed. Preparing summary slip...');
      closeClinicalNoteModal();

      // Trigger the official slip layout
      printConsultationSlip(patientId, {
        diagnosis,
        treatment,
        prescription
      });

      loadData();
    } catch (e) {
      showToast('Error saving consultation note.');
    }
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
    const activeMeds = p.active_medications || 'None recorded.';
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
        const dateParts = formatDateBadge(p.appointment_date || p.created_at);
        const timeLabel = p.preferred_time_slot || formatTime12(p.scheduled_time) || '8:00 AM';
        const deptDisplay = formatDeptLabel(p.department);
        const recipientLabel = p.appointment_recipient || 'Patient (Me)';
        const companionLabel = (p.with_companion == 1 || p.with_companion === true || p.with_companion === '1') ? 'Yes' : 'No';
        const scheduleDisplay = isInquiry
          ? 'No clinic slot reserved (Inquiry Only)'
          : `${dateParts.full} (${timeLabel})`;
        const completedHistory = Array.isArray(p.previous_appointments)
          ? p.previous_appointments.filter(prev => String(prev.status || '').toLowerCase().trim() === 'completed')
          : [];

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
              <div style="font-size:0.86rem; font-weight:600; color:#334155; margin-top:2px;">${escapeHtml(isInquiry ? (p.mode_of_inquiry || 'Online Portal') : (p.consultation_mode || 'In-Person'))}${isInquiry ? ' (Inquiry Only)' : ''}</div>
            </div>
            <div>
              <div style="font-size:0.72rem; color:#64748b; font-weight:600; text-transform:uppercase;">Preferred Date &amp; Time Slot</div>
              <div style="font-size:0.86rem; font-weight:600; color:#334155; margin-top:2px;">${escapeHtml(scheduleDisplay)}</div>
            </div>
            <div>
              <div style="font-size:0.72rem; color:#64748b; font-weight:600; text-transform:uppercase;">Accompanying Companion</div>
              <div style="font-size:0.86rem; font-weight:600; color:#334155; margin-top:2px;">${companionLabel}</div>
            </div>
            <div>
              <div style="font-size:0.72rem; color:#64748b; font-weight:600; text-transform:uppercase;">Attending Physician</div>
              <div style="font-size:0.86rem; font-weight:600; color:#334155; margin-top:2px;">${escapeHtml(isInquiry ? 'General Inquiry' : (p.assigned_doctor || 'Unassigned'))}</div>
            </div>
          </div>

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
                ${escapeHtml(p.current_medications || 'None')}
              </div>
            </div>
          </div>

          <div>
            <div style="font-size:0.75rem; color:#64748b; font-weight:600; text-transform:uppercase; margin-bottom:4px;">Referral Source</div>
            <div style="font-size:0.86rem; color:#0f172a;">${escapeHtml(p.referral_source || 'None')}</div>
          </div>

          ${(p.latest_progress_note && p.latest_progress_note.note) ? `
          <div>
            <div style="font-size:0.75rem; color:#64748b; font-weight:600; text-transform:uppercase; margin-bottom:4px;">Latest Progress / Resolution Note (${escapeHtml(p.latest_progress_note.noted_by || 'Staff')})</div>
            <div style="font-size:0.85rem; color:#0f172a; background:#f8fafc; border:1px solid #cbd5e1; border-radius:8px; padding:0.7rem;">
              ${escapeHtml(p.latest_progress_note.note)}
            </div>
          </div>
          ` : ''}

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
    staffQrStatusEl.style.color = isError ? '#dc2626' : '#64748b';
  }

  function renderScannedPatientCard(p) {
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

    staffQrResultEl.innerHTML = `
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
  }

  async function lookupScannedPatient(rawId) {
    const pid = String(rawId || '').trim();
    if (!pid) {
      setQrStatus('Enter or scan a valid Patient ID.', true);
      return;
    }
    setQrStatus(`Looking up ${pid}...`, false);
    try {
      const res = await fetch(`/sdn/api/patients.php?id=${encodeURIComponent(pid)}`).then(r => r.json());
      if (res && res.success && res.data) {
        renderScannedPatientCard(res.data);
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
    lookupScannedPatient(manualQrInput ? manualQrInput.value : '');
  });
  if (manualQrInput) manualQrInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      lookupScannedPatient(manualQrInput.value);
    }
  });

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
