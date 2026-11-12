/**
 * public/js/staff-dashboard-clean.js
 * Minimalist Clinical Staff Portal Controller
 */

document.addEventListener('DOMContentLoaded', () => {
  let allPatients = [];
  let departmentDoctors = [];
  let currentView = 'consultation';

  // Filter & Sort State
  let consultSearch = '';
  let consultStatus = 'all';

  let recordsSearch = '';
  let recordsSort = 'date_desc';
  let recordsStatus = 'all';

  // DOM References
  const viewConsultation = document.getElementById('view-consultation');
  const viewRecords = document.getElementById('view-records');
  const viewSettings = document.getElementById('view-settings');
  const toastEl = document.getElementById('cleanToast');
  const clockEl = document.getElementById('headerClock');

  // ── 1. Live Clock Removed (Header Kept Clean) ──

  // ── 2. Toast Feedback ──
  let toastTimer = null;
  function showToast(msg, duration = 2800, tone = 'success') {
    if (!toastEl) return;
    const isError = tone === 'error';
    toastEl.classList.toggle('is-error', isError);
    toastEl.innerHTML = `<span class="toast-clean-mark" aria-hidden="true">${isError ? '!' : '✓'}</span><span>${escapeHtml(String(msg || ''))}</span>`;
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

  // ── 3. View Navigation ──
  function switchView(viewName) {
    currentView = viewName;
    document.querySelectorAll('.sidebar-nav-item').forEach(btn => {
      btn.classList.toggle('active', btn.dataset.view === viewName);
    });

    viewConsultation.classList.toggle('active', viewName === 'consultation');
    viewRecords.classList.toggle('active', viewName === 'records');
    viewSettings.classList.toggle('active', viewName === 'settings');

    if (viewName === 'consultation') renderConsultations();
    if (viewName === 'records') renderRecords();
  }
  window.switchView = switchView;

  const staffProfileBtn = document.getElementById('staffProfileBtn');
  const staffProfileDropdown = document.getElementById('staffProfileDropdown');
  const staffProfileSettings = document.getElementById('staffProfileSettings');

  function closeStaffProfile() {
    staffProfileDropdown?.classList.remove('show');
    staffProfileBtn?.setAttribute('aria-expanded', 'false');
  }

  staffProfileBtn?.addEventListener('click', (e) => {
    e.stopPropagation();
    const willOpen = !staffProfileDropdown.classList.contains('show');
    staffProfileDropdown.classList.toggle('show', willOpen);
    staffProfileBtn.setAttribute('aria-expanded', String(willOpen));
  });

  staffProfileSettings?.addEventListener('click', () => {
    closeStaffProfile();
    switchView('settings');
  });

  document.addEventListener('click', (e) => {
    if (!e.target.closest('#staffProfileContainer')) closeStaffProfile();
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeStaffProfile();
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
        const docRes = await fetch('/sdn/api/schedule.php?_=' + Date.now());
        const docData = await docRes.json();
        if (docData && docData.success && Array.isArray(docData.data)) {
          const userDept = (window.STAFF_DEPT || '').toLowerCase();
          departmentDoctors = docData.data.filter(d => {
            const dDept = (d.department || '').toLowerCase();
            return !userDept || dDept.includes(userDept) || userDept.includes(dDept);
          });
        }
      } catch (e) {
        departmentDoctors = [];
      }

      updateKPIsAndPillCounts();
      if (currentView === 'consultation') renderConsultations();
      if (currentView === 'records') renderRecords();
    } catch (err) {
      showToast('Unable to fetch clinic records.');
    }
  }

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

  // ── 5. KPI & Filter Pill Counts ──
  function updateKPIsAndPillCounts() {
    const todayStr = new Date().toISOString().split('T')[0];

    const todayVisits = allPatients.filter(p => (p.appointment_date === todayStr) || (p.created_at && p.created_at.startsWith(todayStr))).length;
    const forApproval = allPatients.filter(p => inApprovalStatus(p.status)).length;
    const confirmed   = allPatients.filter(p => isConfirmedStatus(p.status)).length;
    const inClinic    = allPatients.filter(p => isInClinicStatus(p.status)).length;
    const completed   = allPatients.filter(p => (p.status || '').toLowerCase() === 'completed').length;

    const setElText = (id, val) => {
      const el = document.getElementById(id);
      if (el) el.textContent = val;
    };

    setElText('kpiTotal', todayVisits);
    setElText('kpiPending', forApproval);
    setElText('kpiQueue', confirmed + inClinic);
    setElText('kpiCompleted', completed);

    // Filter pill labels
    setElText('pillCountAll', allPatients.length);
    setElText('pillCountPending', forApproval);
    setElText('pillCountApproved', confirmed);
    setElText('pillCountInClinic', inClinic);
    setElText('pillCountCompleted', completed);
  }

  // ── 6. Consultations View Renderer ──
  function renderConsultations() {
    const tbody = document.getElementById('consultationsTableBody');
    if (!tbody) return;

    const filtered = allPatients.filter(p => {
      if (consultSearch) {
        const q = consultSearch.toLowerCase();
        const mName = (p.full_name || '').toLowerCase().includes(q);
        const mId = (p.patient_id || '').toLowerCase().includes(q);
        const mPhone = (p.contact_number || '').includes(q);
        if (!mName && !mId && !mPhone) return false;
      }

      if (consultStatus !== 'all') {
        const s = (p.status || '').toLowerCase();
        if (consultStatus === 'for_approval' && !inApprovalStatus(s)) return false;
        if (consultStatus === 'approved' && !isConfirmedStatus(s)) return false;
        if (consultStatus === 'in_clinic' && !isInClinicStatus(s)) return false;
        if (consultStatus === 'completed' && s !== 'completed') return false;
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
      const isOnline = !p.unscheduled_walkin;
      const typeTag = isOnline
        ? '<span class="patient-type-badge online">Online</span>'
        : '<span class="patient-type-badge walkin">Walk-in</span>';

      const dateParts = formatDateBadge(p.appointment_date || p.created_at);
      const timeLabel = formatTime12(p.scheduled_time) || '8:00 AM';
      const statusNorm = (p.status || 'pending').toLowerCase();
      const statusPill = renderStatusPill(statusNorm);
      const docOptions = renderDoctorDropdownOptions(p.assigned_doctor);

      let primaryAction = '';
      let menuItems = '';
      if (inApprovalStatus(statusNorm)) {
        primaryAction = `<button type="button" class="btn-clean primary sm" onclick="handleApprove('${p.patient_id}')">Approve</button>`;
        menuItems = `<button type="button" class="row-menu-item danger" onclick="handleReject('${p.patient_id}')">Decline</button>`;
      } else if (statusNorm === 'approved' || statusNorm === 'confirmed') {
        primaryAction = `<button type="button" class="btn-clean primary sm" onclick="handleCheckIn('${p.patient_id}')">Check in</button>`;
        menuItems = `<button type="button" class="row-menu-item danger" onclick="handleReject('${p.patient_id}')">Cancel</button>`;
      } else if (statusNorm === 'arrived') {
        primaryAction = `<button type="button" class="btn-clean primary sm" onclick="handleStartConsult('${p.patient_id}')">Start Consult</button>`;
        menuItems = `<button type="button" class="row-menu-item danger" onclick="handleReject('${p.patient_id}')">Cancel</button>`;
      } else if (statusNorm === 'ongoing') {
        primaryAction = `<button type="button" class="btn-clean primary sm" onclick="openClinicalNoteModal('${p.patient_id}', '${escapeHtml(p.full_name)}')">Complete</button>`;
        menuItems = `<button type="button" class="row-menu-item danger" onclick="handleReject('${p.patient_id}')">Cancel</button>`;
      }
      menuItems += `<button type="button" class="row-menu-item" onclick="openPatientDetailsModal('${p.patient_id}')">Details</button>`;

      const isLockedDoc = statusNorm === 'completed' || isCancelledStatus(statusNorm);
      const rawDept = (p.department || '').trim();
      const isTriagePending = !rawDept || rawDept.toLowerCase().includes('triage') || rawDept.toLowerCase().includes('pending');
      const deptNorm = rawDept.toLowerCase();

      let deptCellHtml = '';
      if (isLockedDoc) {
        deptCellHtml = `<span class="badge ${isTriagePending ? 'badge-amber' : 'badge-slate'}" style="font-size:0.72rem; font-weight:700;">${escapeHtml(rawDept || 'Triage Pending')}</span>`;
      } else {
        deptCellHtml = `
          <select class="table-doc-select triage-dept-select" onchange="if(this.value==='OPEN_TRIAGE_MODAL'){ openTriageAssignModal('${p.patient_id}'); this.value='${rawDept || 'Triage Pending'}'; } else { handleAssignDepartment('${p.patient_id}', this.value); }" style="width:100%; margin-bottom:5px; font-size:0.75rem; ${isTriagePending ? 'border:1.5px solid #f59e0b; background:#fffbeb; color:#b45309; font-weight:700;' : 'border:1px solid #cbd5e1; font-weight:600; color:#0f172a;'}" title="Triage: Review complaint and assign/correct department">
            <option value="OPEN_TRIAGE_MODAL" ${isTriagePending ? 'selected' : ''}>⚠️ Assign Dept (Triage)...</option>
            <option value="General Medical" ${deptNorm.includes('general') ? 'selected' : ''}>General Medical</option>
            <option value="Neurology" ${deptNorm.includes('neuro') ? 'selected' : ''}>Neurology</option>
            <option value="OPU Psychiatry" ${deptNorm.includes('psych') || deptNorm.includes('opu') ? 'selected' : ''}>OPU Psychiatry</option>
          </select>
        `;
      }

      let docCellHtml = '';
      if (isLockedDoc) {
        const assignedName = p.assigned_doctor && p.assigned_doctor !== 'None'
          ? (p.assigned_doctor.startsWith('Dr.') ? p.assigned_doctor : 'Dr. ' + p.assigned_doctor)
          : 'None';
        docCellHtml = `<span class="table-doc-static" title="${escapeHtml(assignedName)}">${escapeHtml(assignedName)}</span>`;
      } else {
        docCellHtml = `
          <select class="table-doc-select" onchange="handleAssignDoctor('${p.patient_id}', this.value)">
            <option value="">Assign Physician...</option>
            ${docOptions}
          </select>
        `;
      }

      const prevBadge = p.previous_appointment_date
        ? `<div class="date-stack-sub" title="Previous appointment">Previous ${escapeHtml(p.previous_appointment_date)}${p.previous_appointment_time ? ' · ' + escapeHtml(p.previous_appointment_time) : ''}</div>`
        : '';

      return `
        <tr>
          <td>
            <div class="patient-cell-name">
              <span>${escapeHtml(p.full_name || 'Patient')}</span>
              ${typeTag}
            </div>
            <div class="patient-cell-meta">${escapeHtml(p.patient_id)} · ${escapeHtml(p.contact_number || 'No phone')}</div>
          </td>
          <td>
            <div class="date-stack">
              <div class="date-stack-main">${escapeHtml(dateParts.monthDay)}</div>
              <div class="date-stack-sub">${escapeHtml(dateParts.year)} · ${escapeHtml(timeLabel)}</div>
              ${prevBadge}
            </div>
          </td>
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
      if (recordsSearch) {
        const q = recordsSearch.toLowerCase();
        const mName = (p.full_name || '').toLowerCase().includes(q);
        const mId = (p.patient_id || '').toLowerCase().includes(q);
        const mHrn = (p.hrn || '').toLowerCase().includes(q);
        const mDoc = (p.assigned_doctor || '').toLowerCase().includes(q);
        const mComplaint = (p.chief_complaints || '').toLowerCase().includes(q);
        if (!mName && !mId && !mHrn && !mDoc && !mComplaint) return false;
      }

      if (recordsStatus !== 'all') {
        const s = (p.status || '').toLowerCase();
        if (recordsStatus === 'rejected') {
          if (!isCancelledStatus(s)) return false;
        } else if (s !== recordsStatus) {
          return false;
        }
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
      const timeLabel = formatTime12(p.scheduled_time) || '8:00 AM';
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
            <div style="font-size:0.75rem; font-weight:700; color:#0369a1; margin-bottom:2px;">${escapeHtml(p.department || 'Triage Pending')}</div>
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

  function renderDoctorDropdownOptions(currentAssigned) {
    const norm = (currentAssigned || '').toLowerCase().replace(/^dr\.\s*/, '');
    return departmentDoctors.map(d => {
      const cleanName = d.name.replace(/^Dr\.\s*/, '');
      const selected = norm && norm === cleanName.toLowerCase() ? 'selected' : '';
      return `<option value="${escapeHtml(d.name)}" ${selected}>Dr. ${escapeHtml(cleanName)}</option>`;
    }).join('');
  }

  // ── 9. Consultation Workflow Actions ──
  window.handleAssignDepartment = async function(patientId, newDept) {
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

  // ── 9.1 Triage Department Assignment Modal UI ──
  const triageModal = document.getElementById('triageAssignModal');

  window.openTriageAssignModal = function(patientId) {
    const p = (allPatients || []).find(item => item.patient_id === patientId);
    if (!p) return;

    const s = (p.status || '').toLowerCase();
    if (s === 'completed' || isCancelledStatus(s)) {
      showToast('Department assignment is finalized for this patient.');
      return;
    }

    const pidInput = document.getElementById('triagePatientId');
    if (pidInput) pidInput.value = p.patient_id;

    const nameEl = document.getElementById('triagePatientName');
    if (nameEl) nameEl.textContent = p.full_name || 'Patient';

    const dateParts = formatDateBadge(p.appointment_date || p.created_at);
    const timeLabel = formatTime12(p.scheduled_time) || '8:00 AM';
    const metaEl = document.getElementById('triagePatientMeta');
    if (metaEl) metaEl.textContent = `${p.patient_id} • ${dateParts.full} (${timeLabel})`;

    const complaintEl = document.getElementById('triageComplaintText');
    if (complaintEl) {
      const complaintStr = (p.chief_complaints || '').trim();
      complaintEl.textContent = (complaintStr && complaintStr.toLowerCase() !== 'none')
        ? complaintStr
        : 'General consultation / No specific complaint provided';
    }

    const deptSelect = document.getElementById('triageDepartmentSelect');
    if (deptSelect) {
      const rawDept = (p.department || '').toLowerCase();
      if (rawDept.includes('neuro')) {
        deptSelect.value = 'Neurology';
      } else if (rawDept.includes('psych') || rawDept.includes('opu')) {
        deptSelect.value = 'OPU Psychiatry';
      } else {
        deptSelect.value = 'General Medical';
      }
    }

    const currentBadge = document.getElementById('triageCurrentBadge');
    if (currentBadge) {
      const isPending = !p.department || p.department.toLowerCase().includes('triage') || p.department.toLowerCase().includes('pending');
      currentBadge.className = isPending ? 'badge badge-amber' : 'badge badge-slate';
      currentBadge.textContent = isPending ? 'Triage Pending' : p.department;
    }

    if (triageModal) triageModal.classList.add('open');
  };

  window.closeTriageAssignModal = function() {
    if (triageModal) triageModal.classList.remove('open');
  };

  if (triageModal) {
    triageModal.addEventListener('click', (e) => {
      if (e.target === triageModal) closeTriageAssignModal();
    });
  }

  window.confirmTriageAndApprove = async function() {
    const pidInput = document.getElementById('triagePatientId');
    const deptSelect = document.getElementById('triageDepartmentSelect');
    const btn = document.getElementById('btnConfirmTriageApprove');

    const patientId = pidInput ? pidInput.value : '';
    const selectedDept = deptSelect ? deptSelect.value : '';

    if (!selectedDept || selectedDept === 'Triage Pending') {
      showToast('Please select a valid hospital department.', 2800, 'error');
      return;
    }

    if (btn) {
      btn.disabled = true;
      btn.textContent = 'Confirming...';
    }

    try {
      await executeApprove(patientId, selectedDept);
      closeTriageAssignModal();
    } finally {
      if (btn) {
        btn.disabled = false;
        btn.textContent = 'Assign & Approve';
      }
    }
  };

  async function executeApprove(patientId, department) {
    try {
      const res = await fetch('/sdn/api/patients.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'approve', patient_id: patientId, department: department })
      }).then(r => r.json());

      if (res.success) {
        showToast('Appointment confirmed. The patient now sees Confirmed.');
        loadData();
      } else {
        showToast(res.message || 'Could not confirm appointment.', 2800, 'error');
      }
    } catch (e) {
      showToast('Network error.', 2800, 'error');
    }
  }

  window.handleApprove = async function(patientId) {
    const p = (allPatients || []).find(item => item.patient_id === patientId);
    let currentDept = p ? (p.department || '').trim() : '';
    const isPending = !currentDept || currentDept.toLowerCase().includes('triage') || currentDept.toLowerCase().includes('pending');

    if (isPending) {
      openTriageAssignModal(patientId);
      return;
    }

    await executeApprove(patientId, currentDept);
  };

  window.handleReject = async function(patientId) {
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

  window.handleCheckIn = async function(patientId) {
    try {
      const res = await fetch('/sdn/api/patients.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'arrived', patient_id: patientId })
      });
      const data = await res.json();

      if (data.success) {
        showToast('Patient checked in. Their portal now shows Checked In.');
        loadData();
      } else {
        showToast(data.message || 'Error checking in.', 2800, 'error');
      }
    } catch (e) {
      console.error('Check-in error:', e);
      showToast('Error connecting to server.', 2800, 'error');
    }
  };

  window.handleStartConsult = async function(patientId) {
    try {
      const res = await fetch('/sdn/api/patients.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'ongoing', patient_id: patientId })
      });
      const data = await res.json();

      if (data.success) {
        showToast('Consultation started. Their portal now shows In Consultation.');
        loadData();
      } else {
        showToast(data.message || 'Error starting consultation.', 2800, 'error');
      }
    } catch (e) {
      console.error('Start consult error:', e);
      showToast('Error starting consultation. Please try again.', 2800, 'error');
    }
  };

  window.handleAssignDoctor = async function(patientId, doctorName) {
    if (!doctorName) return;
    try {
      const res = await fetch('/sdn/api/patients.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'assign', patient_id: patientId, doctor: doctorName })
      }).then(r => r.json());

      if (res.success) {
        showToast(`Assigned to ${doctorName}`);
        loadData();
      } else {
        showToast(res.message || 'Could not assign physician.');
      }
    } catch (e) {
      showToast('Network error.');
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

  function getDepartmentDrugKey(dept) {
    const d = (dept || window.STAFF_DEPT || '').toLowerCase();
    if (d.includes('neuro')) return 'neurology';
    if (d.includes('psych') || d.includes('opu')) return 'psychiatry';
    return 'general';
  }

  function populateMedicineDropdown(dept) {
    const selectEl = document.getElementById('noteMedicineSelect');
    if (!selectEl) return;
    const key = getDepartmentDrugKey(dept);
    const list = DOH_MEDICINES[key] || DOH_MEDICINES.general;

    const noticeEl = document.getElementById('dohDeptNotice');
    if (noticeEl) {
      const labels = { general: 'General Medicine', neurology: 'Neurology', psychiatry: 'Psychiatry & Mental Health' };
      noticeEl.textContent = labels[key] || 'General Medicine';
    }

    selectEl.innerHTML = '<option value="">Select drug / medicine...</option>' +
      list.map((m, idx) => `<option value="${idx}">${escapeHtml(m.name)}</option>`).join('');
  }

  window.handleMedicineSelect = function(selectedIdx) {
    if (selectedIdx === '') return;
    const patientDept = window.activeConcludePatientDept || window.STAFF_DEPT || '';
    const key = getDepartmentDrugKey(patientDept);
    const list = DOH_MEDICINES[key] || DOH_MEDICINES.general;
    const med = list[parseInt(selectedIdx, 10)];
    if (!med) return;

    const doseInput = document.getElementById('noteDosage');
    const qtyInput = document.getElementById('noteQuantity');

    if (doseInput) {
      doseInput.value = med.defaultDose;
    }
    if (qtyInput) {
      qtyInput.value = med.defaultQty;
    }

    updatePrescriptionText();
  };

  function updatePrescriptionText() {
    const selectEl = document.getElementById('noteMedicineSelect');
    const doseInput = document.getElementById('noteDosage');
    const qtyInput = document.getElementById('noteQuantity');
    const rxText = document.getElementById('notePrescription');
    if (!rxText) return;

    const patientDept = window.activeConcludePatientDept || window.STAFF_DEPT || '';
    const key = getDepartmentDrugKey(patientDept);
    const list = DOH_MEDICINES[key] || DOH_MEDICINES.general;
    const selIdx = selectEl ? selectEl.value : '';
    const medName = (selIdx !== '' && list[selIdx]) ? list[selIdx].name : '';
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

  // ── 9.9 CDSS Cascading Autocomplete for Diagnosis ──
  const diagInput = document.getElementById('noteDiagnosis');
  const cdssDrop = document.getElementById('cdssDropdown');
  const cdssSpin = document.getElementById('noteDiagnosisSpinner');
  let cdssDebounceTimer = null;

  if (diagInput && cdssDrop) {
    diagInput.addEventListener('input', (e) => {
      const q = e.target.value.trim();
      if (cdssDebounceTimer) clearTimeout(cdssDebounceTimer);

      if (q.length < 2) {
        cdssDrop.style.display = 'none';
        cdssDrop.innerHTML = '';
        if (cdssSpin) cdssSpin.style.display = 'none';
        return;
      }

      if (cdssSpin) cdssSpin.style.display = 'block';

      cdssDebounceTimer = setTimeout(async () => {
        try {
          const dept = window.activeConcludePatientDept || window.STAFF_DEPT || '';
          const res = await fetch(`/sdn/api/guidelines.php?query=${encodeURIComponent(q)}&department=${encodeURIComponent(dept)}`).then(r => r.json());
          const list = Array.isArray(res) ? res : (res.data || []);
          if (cdssSpin) cdssSpin.style.display = 'none';

          if (!list.length) {
            cdssDrop.style.display = 'none';
            cdssDrop.innerHTML = '';
            return;
          }

          cdssDrop.innerHTML = `
            <div style="padding:6px 10px; background:#f8fafc; border-bottom:1px solid #e2e8f0; font-size:0.68rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.04em;">
              Clinical Decision Support (CDSS) Guidelines (${list.length})
            </div>
          ` + list.map((g, i) => `
            <div class="cdss-option" data-idx="${i}" style="padding:8px 12px; cursor:pointer; border-bottom:1px solid #f1f5f9; transition:background 0.15s ease;" onmouseover="this.style.background='#ecfdf5'" onmouseout="this.style.background='#ffffff'">
              <div style="display:flex; justify-content:space-between; align-items:center;">
                <span style="font-weight:700; color:#0f172a; font-size:0.86rem;">${escapeHtml(g.diagnosis)}</span>
                <span style="font-size:0.68rem; font-weight:700; color:#047857; background:#d1fae5; border:1px solid #a7f3d0; padding:1px 6px; border-radius:999px;">${escapeHtml(g.department)}</span>
              </div>
              <div style="font-size:0.75rem; color:#475569; margin-top:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                <strong>Plan:</strong> ${escapeHtml(g.treatment_plan)}
              </div>
              <div style="font-size:0.74rem; color:#059669; margin-top:2px; display:flex; align-items:center; gap:6px;">
                <span style="font-weight:700;">Rx:</span>
                <span>${escapeHtml(g.medicine_name)} &bull; ${escapeHtml(g.dosage_instructions)} (${escapeHtml(g.default_quantity)})</span>
              </div>
            </div>
          `).join('');

          cdssDrop.style.display = 'block';

          const optionEls = cdssDrop.querySelectorAll('.cdss-option');
          optionEls.forEach(el => {
            el.addEventListener('click', () => {
              const idx = parseInt(el.getAttribute('data-idx'), 10);
              const selected = list[idx];
              if (selected) {
                applyCdssGuideline(selected);
              }
            });
          });
        } catch (err) {
          if (cdssSpin) cdssSpin.style.display = 'none';
          cdssDrop.style.display = 'none';
        }
      }, 280);
    });

    document.addEventListener('click', (e) => {
      if (!diagInput.contains(e.target) && !cdssDrop.contains(e.target)) {
        cdssDrop.style.display = 'none';
      }
    });
  }

  window.applyCdssGuideline = function(g) {
    if (!g) return;
    const diagInput = document.getElementById('noteDiagnosis');
    const treatInput = document.getElementById('noteTreatment');
    const medSelect = document.getElementById('noteMedicineSelect');
    const doseInput = document.getElementById('noteDosage');
    const qtyInput = document.getElementById('noteQuantity');
    const rxText = document.getElementById('notePrescription');
    const cdssDrop = document.getElementById('cdssDropdown');

    if (diagInput) diagInput.value = g.diagnosis;
    if (treatInput) treatInput.value = g.treatment_plan;

    if (medSelect && g.medicine_name) {
      let matched = false;
      for (let i = 0; i < medSelect.options.length; i++) {
        if (medSelect.options[i].text.toLowerCase().includes(g.medicine_name.toLowerCase()) ||
            g.medicine_name.toLowerCase().includes(medSelect.options[i].text.toLowerCase())) {
          medSelect.selectedIndex = i;
          matched = true;
          break;
        }
      }
      if (!matched) {
        const opt = new Option(`${g.medicine_name} (${g.dosage_instructions})`, 'CDSS_CUSTOM_' + Date.now(), true, true);
        medSelect.add(opt);
      }
    }

    if (doseInput) doseInput.value = g.dosage_instructions;
    if (qtyInput) qtyInput.value = g.default_quantity;

    if (rxText && g.medicine_name) {
      rxText.value = `Rx: ${g.medicine_name} #${g.default_quantity}\nSig: ${g.dosage_instructions}`;
    }

    if (cdssDrop) {
      cdssDrop.style.display = 'none';
      cdssDrop.innerHTML = '';
    }
  };

  // ── 10. Clinical Note & Complete Modal ──
  const noteModal = document.getElementById('clinicalNoteModal');
  window.openClinicalNoteModal = function(patientId, patientName) {
    const patientObj = allPatients.find(x => x.patient_id === patientId);
    window.activeConcludePatientDept = (patientObj && patientObj.department) ? patientObj.department : (window.STAFF_DEPT || '');

    document.getElementById('notePatientId').value = patientId;
    document.getElementById('notePatientNameText').textContent = `${patientName} (${patientId})`;
    document.getElementById('noteDiagnosis').value = '';
    document.getElementById('noteTreatment').value = '';
    document.getElementById('notePrescription').value = '';
    if (document.getElementById('noteDosage')) document.getElementById('noteDosage').value = '';
    if (document.getElementById('noteQuantity')) document.getElementById('noteQuantity').value = '';

    const cdssDrop = document.getElementById('cdssDropdown');
    if (cdssDrop) {
      cdssDrop.style.display = 'none';
      cdssDrop.innerHTML = '';
    }

    populateMedicineDropdown(window.activeConcludePatientDept);
    noteModal.classList.add('open');
  };

  window.closeClinicalNoteModal = function() {
    const cdssDrop = document.getElementById('cdssDropdown');
    if (cdssDrop) {
      cdssDrop.style.display = 'none';
      cdssDrop.innerHTML = '';
    }
    noteModal.classList.remove('open');
  };

  window.submitClinicalNote = async function() {
    const patientId = document.getElementById('notePatientId').value;
    const diagnosis = document.getElementById('noteDiagnosis').value.trim();
    const treatment = document.getElementById('noteTreatment').value.trim();
    const prescription = document.getElementById('notePrescription').value.trim();

    const selectEl = document.getElementById('noteMedicineSelect');
    const doseInput = document.getElementById('noteDosage');
    const qtyInput = document.getElementById('noteQuantity');

    const patientDept = window.activeConcludePatientDept || window.STAFF_DEPT || '';
    const key = getDepartmentDrugKey(patientDept);
    const list = DOH_MEDICINES[key] || DOH_MEDICINES.general;
    const selIdx = selectEl ? selectEl.value : '';
    let medicineName = (selIdx !== '' && list[selIdx]) ? list[selIdx].name : '';
    if (!medicineName && selectEl && selectEl.selectedIndex > 0) {
      medicineName = selectEl.options[selectEl.selectedIndex].text.split('(')[0].trim();
    }
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
  window.printConsultationSlip = async function(patientId, overrideData = {}) {
    let p = allPatients.find(x => x.patient_id === patientId);

    try {
      const res = await fetch(`/sdn/api/patients.php?id=${encodeURIComponent(patientId)}&_=${Date.now()}`).then(r => r.json());
      if (res && res.success && res.data) {
        p = Object.assign({}, p || {}, res.data);
      }
    } catch (e) {}

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
  window.printPatientSlipFromDetails = function() {
    if (currentDetailsPatientId) {
      printConsultationSlip(currentDetailsPatientId);
    }
  };

  // ── 11. Patient Details Modal ──
  const detailsModal = document.getElementById('patientDetailsModal');
  window.openPatientDetailsModal = async function(patientId) {
    currentDetailsPatientId = patientId;
    const bodyEl = document.getElementById('patientDetailsContent');
    bodyEl.innerHTML = '<div style="text-align:center; padding:2rem; color:#64748b;">Loading patient record...</div>';
    detailsModal.classList.add('open');

    try {
      const res = await fetch(`/sdn/api/patients.php?id=${encodeURIComponent(patientId)}&_=${Date.now()}`).then(r => r.json());
      if (res && res.success && res.data) {
        const p = res.data;
        const statusNorm = (p.status || '').toLowerCase();
        const isCompleted = statusNorm === 'completed';
        const isCancelled = isCancelledStatus(statusNorm);
        const isLocked = isCompleted || isCancelled;
        const dateParts = formatDateBadge(p.appointment_date || p.created_at);
        const timeLabel = formatTime12(p.scheduled_time) || '8:00 AM';
        const rawDept = (p.department || '').trim();
        const isTriagePending = !rawDept || rawDept.toLowerCase().includes('triage') || rawDept.toLowerCase().includes('pending');
        const deptNorm = rawDept.toLowerCase();

        bodyEl.innerHTML = `
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; background:#f8fafc; padding:1.1rem; border-radius:10px; border:1px solid #e2e8f0;">
            <div>
              <div style="font-size:0.72rem; color:#64748b; font-weight:600; text-transform:uppercase;">Patient Name</div>
              <div style="font-size:0.98rem; font-weight:700; color:#0f172a; margin-top:2px;">${escapeHtml(p.full_name)}</div>
            </div>
            <div>
              <div style="font-size:0.72rem; color:#64748b; font-weight:600; text-transform:uppercase;">Reference ID</div>
              <div style="font-size:0.98rem; font-weight:700; color:#15803d; margin-top:2px;">${escapeHtml(p.patient_id)}</div>
            </div>
            <div>
              <div style="font-size:0.72rem; color:#64748b; font-weight:600; text-transform:uppercase;">Assigned Department</div>
              <div style="font-size:0.88rem; font-weight:700; color:${isTriagePending ? '#b45309' : '#0f172a'}; margin-top:2px;">
                ${isTriagePending ? '⚠️ Triage Pending (Needs Assignment)' : escapeHtml(p.department)}
              </div>
            </div>
            <div>
              <div style="font-size:0.72rem; color:#64748b; font-weight:600; text-transform:uppercase;">Gender &amp; Age</div>
              <div style="font-size:0.86rem; font-weight:600; color:#334155; margin-top:2px;">${escapeHtml(p.gender || '-')} &bull; ${escapeHtml(p.age || '-')} yrs</div>
            </div>
            <div>
              <div style="font-size:0.72rem; color:#64748b; font-weight:600; text-transform:uppercase;">Contact Number</div>
              <div style="font-size:0.86rem; font-weight:600; color:#334155; margin-top:2px;">${escapeHtml(p.contact_number || 'Not provided')}</div>
            </div>
            <div>
              <div style="font-size:0.72rem; color:#64748b; font-weight:600; text-transform:uppercase;">Scheduled Visit</div>
              <div style="font-size:0.86rem; font-weight:600; color:#334155; margin-top:2px;">${escapeHtml(dateParts.full)} (${escapeHtml(timeLabel)})</div>
            </div>
            <div>
              <div style="font-size:0.72rem; color:#64748b; font-weight:600; text-transform:uppercase;">Attending Physician</div>
              <div style="font-size:0.86rem; font-weight:600; color:#334155; margin-top:2px;">${escapeHtml(p.assigned_doctor || 'Unassigned')}</div>
            </div>
          </div>

          <div>
            <div style="font-size:0.75rem; color:#64748b; font-weight:600; text-transform:uppercase; margin-bottom:4px;">Home Address</div>
            <div style="font-size:0.86rem; color:#0f172a;">${escapeHtml(p.address || 'Not specified')}</div>
          </div>

          <div>
            <div style="font-size:0.75rem; color:#64748b; font-weight:600; text-transform:uppercase; margin-bottom:4px;">Chief Complaint</div>
            <div style="font-size:0.88rem; color:#0f172a; background:#ffffff; border:1px solid #e2e8f0; border-radius:8px; padding:0.75rem;">
              ${escapeHtml(p.chief_complaints || 'General consultation')}
            </div>
          </div>

          <div style="background:#f8fafc; border:1.5px solid ${isLocked ? '#cbd5e1' : (isTriagePending ? '#fcd34d' : '#cbd5e1')}; border-radius:8px; padding:0.85rem;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
              <span style="font-size:0.75rem; color:#0f172a; font-weight:700; text-transform:uppercase;">
                Triage Routing &amp; Department Assignment
              </span>
              ${isCompleted 
                ? '<span style="font-size:0.7rem; font-weight:700; color:#15803d; background:#dcfce7; border:1px solid #bbf7d0; padding:2px 8px; border-radius:999px;">🔒 Finalized &bull; Completed</span>'
                : (isCancelled
                  ? '<span style="font-size:0.7rem; font-weight:600; color:#64748b; background:#f1f5f9; border:1px solid #cbd5e1; padding:2px 8px; border-radius:999px;">🔒 Locked &bull; Cancelled</span>'
                  : (isTriagePending 
                    ? '<span style="font-size:0.7rem; font-weight:700; color:#b45309; background:#fef3c7; border:1px solid #fde68a; padding:2px 8px; border-radius:999px;">⚠️ Needs Assignment</span>' 
                    : '<span style="font-size:0.7rem; font-weight:600; color:#15803d; background:#dcfce7; border:1px solid #bbf7d0; padding:2px 8px; border-radius:999px;">Assigned</span>'
                  )
                )
              }
            </div>
            <div style="font-size:0.78rem; color:#64748b; margin-bottom:8px;">
              ${isCompleted
                ? 'This consultation is completed. Department assignment is locked and cannot be changed.'
                : (isCancelled
                  ? 'This appointment is cancelled. Department routing cannot be modified.'
                  : 'Verify the patient\'s chief complaint above. You can assign or correct their department at any time.'
                )
              }
            </div>
            ${isLocked ? `
              <div style="display:flex; gap:8px; align-items:center;">
                <div style="flex:1; background:#ffffff; border:1px solid #cbd5e1; border-radius:8px; padding:7px 12px; font-weight:600; font-size:0.85rem; color:#0f172a; display:flex; justify-content:space-between; align-items:center;">
                  <span>${escapeHtml(rawDept || 'General Medical')}</span>
                  <span style="font-size:0.7rem; font-weight:700; color:#64748b; background:#f1f5f9; border:1px solid #e2e8f0; padding:1px 6px; border-radius:4px;">Read Only</span>
                </div>
              </div>
            ` : `
              <div style="display:flex; gap:8px; align-items:center;">
                <select id="modalDeptSelect" class="form-control-clean" style="flex:1; font-weight:600; font-size:0.85rem; padding:6px 10px;">
                  <option value="Triage Pending" ${isTriagePending ? 'selected' : ''}>⚠️ Triage Pending (Needs Assignment)</option>
                  <option value="General Medical" ${deptNorm.includes('general') ? 'selected' : ''}>General Medical</option>
                  <option value="Neurology" ${deptNorm.includes('neuro') ? 'selected' : ''}>Neurology</option>
                  <option value="OPU Psychiatry" ${deptNorm.includes('psych') || deptNorm.includes('opu') ? 'selected' : ''}>OPU Psychiatry</option>
                </select>
                <button type="button" class="btn-clean primary sm" onclick="handleAssignDepartment('${p.patient_id}', document.getElementById('modalDeptSelect').value)">Save Department</button>
              </div>
            `}
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

          ${(Array.isArray(p.previous_appointments) && p.previous_appointments.length > 0) ? `
          <div>
            <div style="font-size:0.75rem; color:#0369a1; font-weight:700; text-transform:uppercase; margin-bottom:6px; display:flex; align-items:center; gap:6px;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
              <span>Previous Appointments History (${p.previous_appointments.length})</span>
            </div>
            <div style="display:flex; flex-direction:column; gap:6px; max-height:180px; overflow-y:auto;">
              ${p.previous_appointments.map(prev => `
                <div style="background:#f8fafc; border:1px solid #cbd5e1; border-radius:8px; padding:8px 11px; font-size:0.82rem; display:flex; justify-content:space-between; align-items:flex-start; gap:8px;">
                  <div>
                    <div style="font-weight:700; color:#0f172a;">${escapeHtml(prev.date_formatted || prev.appointment_date)} &bull; <span style="color:#0369a1;">${escapeHtml(prev.time_formatted || '8:00 AM')}</span></div>
                    <div style="color:#475569; font-size:0.78rem; margin-top:2px;">${escapeHtml(prev.department || 'Clinic')} &bull; ${escapeHtml(prev.assigned_doctor || 'Attending Physician')} (${escapeHtml(prev.patient_id)})</div>
                    ${prev.diagnosis ? `<div style="color:#15803d; font-size:0.78rem; margin-top:2px;"><strong>Dx:</strong> ${escapeHtml(prev.diagnosis)}</div>` : ''}
                  </div>
                  <span style="font-size:0.7rem; font-weight:700; text-transform:uppercase; padding:2px 7px; border-radius:999px; background:#e2e8f0; color:#334155;">${escapeHtml(prev.status || 'past')}</span>
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

  window.closePatientDetailsModal = function() {
    detailsModal.classList.remove('open');
  };

  // ── 12. Walk-in Modal ──
  const walkinModal = document.getElementById('walkinModal');
  window.openWalkinModal = function() {
    document.getElementById('walkinForm').reset();
    walkinModal.classList.add('open');
  };

  window.closeWalkinModal = function() {
    walkinModal.classList.remove('open');
  };

  window.submitWalkinPatient = async function() {
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
  window.openDoctorStatusModal = async function() {
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

  window.closeDoctorStatusModal = function() {
    doctorModal.classList.remove('open');
  };

  window.updateDocStatus = async function(docId, newStatus, selectEl) {
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
  const btnSaveProfile = document.getElementById('btnSaveProfile');
  if (btnSaveProfile) {
    btnSaveProfile.addEventListener('click', async () => {
      const name = document.getElementById('settingsFullName').value.trim();
      if (!name) {
        showToast('Full name cannot be empty.');
        return;
      }
      try {
        const res = await fetch('/sdn/api/staff.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            action: 'update_profile',
            user_id: window.STAFF_USER_ID,
            full_name: name
          })
        }).then(r => r.json());

        if (res.success) {
          showToast('Profile saved.');
          const headerName = document.getElementById('headerStaffName');
          if (headerName) headerName.textContent = name;
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

  // ── 15. CSV Export ──
  window.exportRecordsCSV = function() {
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
});

window.toggleRowMenu = function(event, btn) {
  event.preventDefault();
  event.stopPropagation();
  const pop = btn.parentElement.querySelector('.row-menu-pop');
  if (!pop) return;
  const willOpen = !pop.classList.contains('open');
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
  document.querySelectorAll('.row-menu-pop.open').forEach(el => el.classList.remove('open'));
  document.querySelectorAll('.row-menu-btn[aria-expanded="true"]').forEach(el => el.setAttribute('aria-expanded', 'false'));
});
