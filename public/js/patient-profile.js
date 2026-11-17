// patient-profile.js — Admin Patient Profile Page

document.addEventListener('DOMContentLoaded', async () => {

  // ── Auth guard ──────────────────────────────────────────────────────────
  const session = await requireLogin();
  if (!session) return;

  // ── Read patient ID from URL ?id=PT-2026-XXX ───────────────────────────
  const params    = new URLSearchParams(window.location.search);
  const patientId = params.get('id')?.trim();

  if (!patientId) {
    showError('No patient ID was provided in the URL.');
    return;
  }

  // ── DOM refs ────────────────────────────────────────────────────────────
  const skeleton     = document.getElementById('profileSkeleton');
  const content      = document.getElementById('profileContent');
  const errorDiv     = document.getElementById('profileError');
  const confirmModal = document.getElementById('confirmModal');
  const modalTitle   = document.getElementById('modalTitle');
  const modalBody    = document.getElementById('modalBody');
  const modalIcon    = document.getElementById('modalIcon');
  const modalConfirm = document.getElementById('modalConfirm');
  const modalCancel  = document.getElementById('modalCancel');

  let pendingAction = null;
  let currentPatient = null;

  // ── Avatar colour palette ───────────────────────────────────────────────
  const avPalette = [
    'linear-gradient(135deg,var(--primary-green),#1a9c52)',
    'linear-gradient(135deg,#3498db,#1a5fa5)',
    'linear-gradient(135deg,#e67e22,#c0550b)',
    'linear-gradient(135deg,#9b59b6,#7039a0)',
    'linear-gradient(135deg,#e74c3c,#b03028)',
    'linear-gradient(135deg,#1abc9c,#0e8c72)',
  ];

  function avatarGradient(name) {
    let hash = 0;
    for (const c of name) hash = (hash * 31 + c.charCodeAt(0)) & 0xffff;
    return avPalette[hash % avPalette.length];
  }

  // ── Helpers ─────────────────────────────────────────────────────────────
  function cap(str) { return str ? str.charAt(0).toUpperCase() + str.slice(1) : '—'; }
  function orDash(v) { return v ?? '—'; }
  function yesNo(v) { return v ? 'Yes' : 'No'; }

  function initials(name = '') {
    return name.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase() || '?';
  }

  function formatDateNice(iso) {
    if (!iso) return '—';
    const [y, m, d] = iso.split('-');
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return `${months[parseInt(m) - 1]} ${parseInt(d)}, ${y}`;
  }

  function formatDateTime(iso) {
    if (!iso) return '—';
    const dt = new Date(iso);
    if (isNaN(dt)) return '—';
    const h  = ((dt.getHours() % 12) || 12).toString().padStart(2, '0');
    const mi = dt.getMinutes().toString().padStart(2, '0');
    const ap = dt.getHours() >= 12 ? 'PM' : 'AM';
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return `${months[dt.getMonth()]} ${dt.getDate()}, ${dt.getFullYear()} · ${h}:${mi} ${ap}`;
  }

  function setText(id, val) {
    const el = document.getElementById(id);
    if (el) el.textContent = val ?? '—';
  }

  function showError(msg = '') {
    if (skeleton) skeleton.hidden = true;
    if (content)  content.hidden  = true;
    if (errorDiv) errorDiv.hidden = false;
    const errMsg = document.getElementById('errorMsg');
    if (errMsg && msg) errMsg.textContent = msg;
  }

  // ── Load patient ─────────────────────────────────────────────────────────
  async function loadPatient() {
    try {
      const res  = await fetch(`${API_BASE}/patients.php?id=${encodeURIComponent(patientId)}`);
      const data = await res.json();

      if (!data.success || !data.data) {
        showError(data.message || 'Patient not found.');
        return;
      }

      currentPatient = data.data;
      renderProfile(currentPatient);

      if (skeleton) skeleton.hidden = true;
      if (content)  content.hidden  = false;

      document.title = `Admin Portal | ${currentPatient.full_name}`;
    } catch (err) {
      console.error(err);
      showError('Failed to load patient. Check your connection.');
    }
  }

  // ── Render profile ────────────────────────────────────────────────────────
  function renderProfile(p) {
    // Avatar
    const avatarEl = document.getElementById('profileAvatar');
    if (avatarEl) {
      avatarEl.textContent = initials(p.full_name);
      avatarEl.style.background = avatarGradient(p.full_name);
    }

    // Header
    setText('profileName', p.full_name);
    setText('profileId',   p.patient_id);
    setText('profileDept', p.department || '—');
    setText('profileCreated', formatDateNice(p.created_at?.split(' ')[0]));

    // Type badge
    const typeBadge = document.getElementById('profileType');
    if (typeBadge) {
      typeBadge.textContent = p.patient_type === 'first-time' ? 'First Time' : 'Returning';
      typeBadge.className   = `profile-type-badge ${p.patient_type === 'first-time' ? 'first-time' : 'returning'}`;
    }

    // Status badge
    const statusBadge = document.getElementById('profileStatus');
    if (statusBadge) {
      statusBadge.className = `status-badge ${p.status}`;
    }
    setText('profileStatusText', cap(p.status));

    // Personal
    setText('infoName',      p.full_name);
    setText('infoGender',    cap(p.gender));
    setText('infoBirthdate', formatDateNice(p.birthdate));
    setText('infoAge',       p.age ? `${p.age} yrs` : '—');
    setText('infoContact',   orDash(p.contact_number));
    setText('infoEmail',     orDash(p.email));
    setText('infoAddress',   orDash(p.address));
    setText('infoCompanion', yesNo(p.with_companion));

    // Appointment
    setText('apptDept',       orDash(p.department));
    setText('apptDate',       formatDateNice(p.appointment_date));
    setText('apptType',       cap(p.appointment_type?.replace('-', ' ')));
    setText('apptMode',       orDash(p.consultation_mode));
    setText('apptNature',     orDash(p.consultation_nature));
    setText('apptPatientType',p.patient_type === 'first-time' ? 'First Time' : 'Returning');
    setText('apptReferral',   orDash(p.referral_source));
    setText('apptDoctor',     orDash(p.assigned_doctor));

    // Clinical
    setText('clinicComplaints', orDash(p.chief_complaints));
    setText('clinicIllness',    orDash(p.illness_description));

    // Show/hide assign doctor card (only for approved patients)
    const assignCard = document.getElementById('assignCard');
    if (assignCard) {
      assignCard.hidden = p.status !== 'approved';
    }

    // Load doctors and notes
    if (p.status === 'approved') loadDoctors();
    loadNotes();
  }

  // ── Load doctors for assignment ───────────────────────────────────────────
  async function loadDoctors() {
    try {
      const res  = await fetch(`${API_BASE}/doctors.php?mode=assign`);
      const data = await res.json();
      if (!data.success) return;

      const select = document.getElementById('doctorSelect');
      if (!select) return;

      // Clear & re-populate
      select.innerHTML = '<option value="">Select a doctor…</option>';
      data.data.forEach(d => {
        const opt = document.createElement('option');
        opt.value = d.name;
        opt.textContent = `${d.name} — ${d.specialization} (${cap(d.status)})`;
        if (d.status === 'leave') opt.disabled = true;
        if (currentPatient?.assigned_doctor === d.name) opt.selected = true;
        select.appendChild(opt);
      });
    } catch (err) {
      console.error('Failed to load doctors:', err);
    }
  }

  // ── Assign doctor ─────────────────────────────────────────────────────────
  const assignBtn = document.getElementById('assignBtn');
  if (assignBtn) {
    assignBtn.addEventListener('click', () => {
      const sel = document.getElementById('doctorSelect');
      const doctorName = sel?.value;
      if (!doctorName) { showToast('Please select a doctor first.', 'error'); return; }

      openModal({
        iconType: 'approve',
        title: 'Assign Doctor',
        body:  `Assign <strong>${doctorName}</strong> to <strong>${currentPatient.full_name}</strong>?`,
        confirmLabel: 'Assign',
        confirmClass: '',
        onConfirm: async () => {
          await doPatientAction('assign', { doctor: doctorName });
        },
      });
    });
  }

  // ── Load progress notes ──────────────────────────────────────────────────
  async function loadNotes() {
    try {
      const res  = await fetch(`${API_BASE}/progress_notes.php?patient_id=${encodeURIComponent(patientId)}`);
      const data = await res.json();
      if (!data.success) return;
      renderNotes(data.data);
    } catch (err) {
      console.error('Failed to load notes:', err);
    }
  }

  function renderNotes(notes) {
    const list   = document.getElementById('notesList');
    const empty  = document.getElementById('notesEmpty');
    const count  = document.getElementById('notesCount');

    if (!list) return;

    list.innerHTML = '';

    if (count) count.textContent = `${notes.length} note${notes.length !== 1 ? 's' : ''}`;

    if (!notes.length) {
      if (empty) empty.hidden = false;
      return;
    }

    if (empty) empty.hidden = true;

    notes.forEach(n => {
      const item = document.createElement('div');
      item.className = 'note-item';
      item.innerHTML = `
        <div class="note-item-header">
          <span class="note-by">${escHtml(n.noted_by)}</span>
          <span class="note-time">${formatDateTime(n.created_at)}</span>
        </div>
        <p class="note-text">${escHtml(n.note)}</p>
      `;
      list.appendChild(item);
    });
  }

  // ── Submit note ───────────────────────────────────────────────────────────
  const noteSubmitBtn = document.getElementById('noteSubmitBtn');
  const noteInput     = document.getElementById('noteInput');

  if (noteSubmitBtn && noteInput) {
    noteSubmitBtn.addEventListener('click', async () => {
      const text = noteInput.value.trim();
      if (!text) { showToast('Note cannot be empty.', 'error'); return; }

      noteSubmitBtn.disabled    = true;
      noteSubmitBtn.textContent = 'Saving…';

      try {
        const res  = await fetch(`${API_BASE}/progress_notes.php`, {
          method:  'POST',
          headers: { 'Content-Type': 'application/json' },
          body:    JSON.stringify({ patient_id: patientId, note: text, noted_by: session.username || 'Admin' }),
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);

        noteInput.value = '';
        showToast('Note added successfully.');
        loadNotes();
      } catch (err) {
        showToast(err.message || 'Failed to save note.', 'error');
      } finally {
        noteSubmitBtn.disabled = false;
        noteSubmitBtn.innerHTML = `
          <svg viewBox="0 0 16 16" fill="none" width="13" height="13">
            <path d="M2 8h12M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          Add Note`;
      }
    });
  }

  // ── Patient action (assign doctor) ────────────────────────────────────────
  async function doPatientAction(action, extra = {}) {
    try {
      const res  = await fetch(`${API_BASE}/patients.php`, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ action, patient_id: patientId, ...extra }),
      });
      const data = await res.json();
      if (!data.success) throw new Error(data.message);

      closeModal();
      showToast(data.message, 'success');
      await loadPatient(); // refresh
    } catch (err) {
      showToast(err.message || 'Action failed.', 'error');
      closeModal();
    }
  }

  // ── Modal helpers ─────────────────────────────────────────────────────────
  function openModal({ iconType, title, body, confirmLabel, confirmClass, onConfirm }) {
    pendingAction = onConfirm;

    if (modalTitle)   modalTitle.textContent = title;
    if (modalBody)    modalBody.innerHTML    = body;
    if (modalConfirm) {
      modalConfirm.textContent = confirmLabel;
      modalConfirm.className   = 'btn-modal-confirm' + (confirmClass ? ` ${confirmClass}` : '');
    }
    if (modalIcon) {
      modalIcon.className = 'modal-icon' + (iconType === 'danger' ? ' danger-icon' : '');
      modalIcon.innerHTML = iconType === 'danger'
        ? `<svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/><path d="M9 9l6 6m0-6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>`
        : `<svg viewBox="0 0 24 24" fill="none"><path d="M20 7L9 18l-5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>`;
    }
    if (confirmModal) { confirmModal.hidden = false; modalConfirm?.focus(); }
  }

  function closeModal() {
    if (confirmModal) confirmModal.hidden = true;
    pendingAction = null;
    if (modalConfirm) { modalConfirm.disabled = false; }
  }

  if (modalCancel)  modalCancel.addEventListener('click', closeModal);
  if (confirmModal) confirmModal.addEventListener('click', e => { if (e.target === confirmModal) closeModal(); });
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

  if (modalConfirm) {
    modalConfirm.addEventListener('click', async () => {
      if (!pendingAction) return;
      modalConfirm.disabled    = true;
      modalConfirm.textContent = 'Processing…';
      await pendingAction();
    });
  }

  // ── Escape helper ────────────────────────────────────────────────────────
  function escHtml(str) {
    return String(str ?? '')
      .replace(/&/g,'&amp;').replace(/</g,'&lt;')
      .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  // ── Patient History ────────────────────────────────────────────────────────
  window.openHistoryModal = () => { document.getElementById('historyModal').hidden = false; };
  window.closeHistoryModal = () => { document.getElementById('historyModal').hidden = true; };
  
  window.submitHistoryRecord = async () => {
    const btn = document.querySelector('#historyModal .btn-modal-confirm');
    btn.disabled = true;
    btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" style="vertical-align:middle;margin-right:6px;" aria-hidden="true"><path d="M12 2a10 10 0 0 1 10 10" opacity="0.9"/><circle cx="12" cy="12" r="10" opacity="0.25"/><animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="0.75s" repeatCount="indefinite"/></svg>Saving...';
    
    const payload = {
      patient_id: patientId,
      record_date: document.getElementById('histDate').value,
      diagnosis: document.getElementById('histDiagnosis').value,
      treatment: document.getElementById('histTreatment').value,
      prescription: document.getElementById('histPrescription').value,
      remarks: document.getElementById('histRemarks').value
    };

    try {
      const res = await fetch(`${API_BASE}/patient_history.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (!data.success) throw new Error(data.error);

      showToast('Record saved successfully!');
      closeHistoryModal();
      
      // Clear inputs
      document.getElementById('histDiagnosis').value = '';
      document.getElementById('histTreatment').value = '';
      document.getElementById('histPrescription').value = '';
      document.getElementById('histRemarks').value = '';

      loadHistory();
      loadNotes(); // Reload notes since history addition drops a note
    } catch (e) {
      showToast(e.message || 'Error saving record', 'error');
    } finally {
      btn.disabled = false; btn.textContent = 'Save Record';
    }
  };

  async function loadHistory() {
    try {
      const res = await fetch(`${API_BASE}/patient_history.php?patient_id=${encodeURIComponent(patientId)}`);
      const data = await res.json();
      if (!data.success) return;
      
      const tbody = document.getElementById('historyList');
      const empty = document.getElementById('historyEmpty');
      
      if (!tbody) return;
      tbody.innerHTML = '';
      if (!data.data.length) {
        empty.hidden = false;
        return;
      }
      empty.hidden = true;
      
      data.data.forEach(h => {
        const tr = document.createElement('tr');
        tr.style.borderBottom = '1px solid #f1f5f9';
        tr.style.fontSize = '0.9rem';
        tr.innerHTML = `
          <td style="padding:12px 10px;">${escHtml(formatDateNice(h.record_date))}</td>
          <td style="padding:12px 10px; font-weight:600; color:#1e293b;">${escHtml(h.diagnosis)}</td>
          <td style="padding:12px 10px;">${escHtml(h.treatment) || '-'}</td>
          <td style="padding:12px 10px;">${escHtml(h.prescription) || '-'}</td>
          <td style="padding:12px 10px;">${escHtml(h.recorded_by)}</td>
        `;
        tbody.appendChild(tr);
      });
    } catch(e) { console.error('Error loading history'); }
  }

  window.downloadHistoryPDF = () => {
    const el = document.getElementById('historyCard');
    const opt = {
      margin:       0.5,
      filename:     `Medical_History_${patientId}.pdf`,
      image:        { type: 'jpeg', quality: 0.98 },
      html2canvas:  { scale: 2 },
      jsPDF:        { unit: 'in', format: 'letter', orientation: 'landscape' }
    };
    html2pdf().set(opt).from(el).save();
  };

  // ── Init ─────────────────────────────────────────────────────────────────
  await loadPatient();
  
});
