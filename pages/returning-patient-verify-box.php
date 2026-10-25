<?php
/**
 * pages/returning-patient-verify-box.php
 *
 * Reusable Returning Patient Verification Box.
 * Allows returning patients to look up their existing record via Patient ID/HRN or Name+Birthdate,
 * automatically syncing their check-ups and pre-filling their demographic profile without login credentials.
 */
?>
<div id="returningPatientWidget" class="returning-patient-widget">
  <div class="returning-widget-header">
    <div class="returning-widget-title-area">
      <div class="returning-icon-badge">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
          <circle cx="9" cy="7" r="4"></circle>
          <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
          <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
        </svg>
      </div>
      <div>
        <h4 class="returning-title">Returning Patient? Quick Verification</h4>
        <p class="returning-desc">Already registered at MMWGH? Verify your record to automatically link your medical history and pre-fill your profile.</p>
      </div>
    </div>
    <button type="button" id="btnToggleReturningBox" class="btn-toggle-returning">
      <span>Verify My Record</span>
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="6 9 12 15 18 9"></polyline>
      </svg>
    </button>
  </div>

  <!-- Collapsible Verification Panel -->
  <div id="returningVerifyPanel" class="returning-verify-panel" style="display: none;">
    <div class="verify-tabs-strip">
      <button type="button" class="verify-tab-btn active" data-tab="tabId">Search by Patient ID / HRN</button>
      <button type="button" class="verify-tab-btn" data-tab="tabName">Search by Name &amp; Birthdate</button>
    </div>

    <!-- Tab 1: Patient ID / HRN -->
    <div id="tabId" class="verify-tab-content active">
      <div class="verify-input-row">
        <div class="verify-input-group" style="flex: 1;">
          <label for="lookupPatientId">Patient ID or Hospital Record Number (HRN)</label>
          <input type="text" id="lookupPatientId" placeholder="e.g. PT-2026-001 or HRN-2026-001" autocomplete="off" />
        </div>
        <button type="button" id="btnLookupById" class="btn-verify-action">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
          <span>Verify Record</span>
        </button>
      </div>
    </div>

    <!-- Tab 2: Name + Birthdate -->
    <div id="tabName" class="verify-tab-content">
      <div class="verify-grid-3">
        <div class="verify-input-group">
          <label for="lookupLastName">Last Name <span class="asterisk">*</span></label>
          <input type="text" id="lookupLastName" placeholder="Last Name" autocomplete="off" />
        </div>
        <div class="verify-input-group">
          <label for="lookupFirstName">First Name <span class="asterisk">*</span></label>
          <input type="text" id="lookupFirstName" placeholder="First Name" autocomplete="off" />
        </div>
        <div class="verify-input-group">
          <label for="lookupBirthdate">Birthdate <span class="asterisk">*</span></label>
          <input type="date" id="lookupBirthdate" />
        </div>
      </div>
      <div style="display: flex; justify-content: flex-end; margin-top: 10px;">
        <button type="button" id="btnLookupByName" class="btn-verify-action">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
          <span>Verify Record</span>
        </button>
      </div>
    </div>

    <!-- Status / Message Display -->
    <div id="verifyStatusBox" class="verify-status-box" style="display: none;"></div>
  </div>

  <!-- Verified Badge Card (Appears after successful lookup) -->
  <div id="verifiedPatientCard" class="verified-patient-card" style="display: none;">
    <div class="verified-card-inner">
      <div class="verified-icon-circle">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
      </div>
      <div class="verified-meta">
        <div class="verified-title-row">
          <strong id="verifiedFullName">Patient Name</strong>
          <span class="verified-pill">✓ Linked Returning Record</span>
        </div>
        <p class="verified-sub-text">
          <span id="verifiedIdDisplay">Patient ID: PT-0000-000</span> • 
          <span id="verifiedHrnDisplay">HRN: HRN-0000-000</span> • 
          <span id="verifiedVisitCount">Visit #1</span>
        </p>
        <p class="verified-sync-note">
          ⚡ <em>Your demographic profile has been pre-filled automatically. Your new check-up will sync directly to your hospital record history.</em>
        </p>
      </div>
      <button type="button" id="btnResetLookup" class="btn-reset-lookup" title="Unlink or verify a different record">
        ✕ Unlink
      </button>
    </div>
  </div>
</div>

<style>
.returning-patient-widget {
  background: #f0fdf4;
  border: 1.5px solid #86efac;
  border-radius: 14px;
  padding: 14px 18px;
  margin-bottom: 20px;
  box-shadow: 0 2px 8px rgba(22, 101, 52, 0.05);
  transition: all 0.25s ease;
}

.returning-widget-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
}

.returning-widget-title-area {
  display: flex;
  align-items: center;
  gap: 12px;
}

.returning-icon-badge {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: #15803d;
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 2px 6px rgba(21, 128, 61, 0.2);
}

.returning-title {
  margin: 0;
  font-size: 0.98rem;
  font-weight: 700;
  color: #14532d;
}

.returning-desc {
  margin: 2px 0 0;
  font-size: 0.82rem;
  color: #166534;
  line-height: 1.35;
}

.btn-toggle-returning {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: #ffffff;
  border: 1.5px solid #16a34a;
  color: #15803d;
  padding: 7px 14px;
  border-radius: 9px;
  font-size: 0.84rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-toggle-returning:hover {
  background: #dcfce7;
  border-color: #15803d;
}

.returning-verify-panel {
  margin-top: 14px;
  padding-top: 14px;
  border-top: 1px dashed #86efac;
}

.verify-tabs-strip {
  display: flex;
  gap: 8px;
  margin-bottom: 12px;
}

.verify-tab-btn {
  background: #e2e8f0;
  border: none;
  padding: 6px 14px;
  border-radius: 8px;
  font-size: 0.8rem;
  font-weight: 600;
  color: #475569;
  cursor: pointer;
  transition: all 0.18s ease;
}

.verify-tab-btn.active {
  background: #15803d;
  color: #ffffff;
}

.verify-tab-content {
  display: none;
}

.verify-tab-content.active {
  display: block;
}

.verify-input-row {
  display: flex;
  align-items: flex-end;
  gap: 12px;
  flex-wrap: wrap;
}

.verify-grid-3 {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 12px;
}

@media (max-width: 768px) {
  .verify-grid-3 {
    grid-template-columns: 1fr;
  }
}

.verify-input-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.verify-input-group label {
  font-size: 0.78rem;
  font-weight: 700;
  color: #166534;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.verify-input-group input {
  padding: 8px 12px;
  border-radius: 8px;
  border: 1.5px solid #cbd5e1;
  font-size: 0.9rem;
  outline: none;
  transition: border-color 0.2s ease;
  background: #ffffff;
}

.verify-input-group input:focus {
  border-color: #16a34a;
  box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.15);
}

.btn-verify-action {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  background: #15803d;
  color: #ffffff;
  border: none;
  padding: 9px 18px;
  border-radius: 8px;
  font-size: 0.86rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
  height: 39px;
}

.btn-verify-action:hover {
  background: #14532d;
}

.verify-status-box {
  margin-top: 10px;
  padding: 8px 12px;
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 600;
}

.verify-status-box.error {
  background: #fee2e2;
  border: 1px solid #f87171;
  color: #991b1b;
}

.verify-status-box.loading {
  background: #e0f2fe;
  border: 1px solid #7dd3fc;
  color: #0369a1;
}

/* Verified Result Card */
.verified-patient-card {
  margin-top: 10px;
  background: #ffffff;
  border: 1.5px solid #16a34a;
  border-radius: 12px;
  padding: 12px 16px;
  box-shadow: 0 4px 12px rgba(22, 163, 74, 0.12);
  animation: slideDown 0.25s ease;
}

@keyframes slideDown {
  from { opacity: 0; transform: translateY(-8px); }
  to { opacity: 1; transform: translateY(0); }
}

.verified-card-inner {
  display: flex;
  align-items: flex-start;
  gap: 14px;
}

.verified-icon-circle {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #16a34a;
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.verified-meta {
  flex: 1;
}

.verified-title-row {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 2px;
}

.verified-title-row strong {
  font-size: 1.05rem;
  color: #0f172a;
}

.verified-pill {
  font-size: 0.72rem;
  font-weight: 700;
  color: #15803d;
  background: #dcfce7;
  padding: 2px 8px;
  border-radius: 12px;
  border: 1px solid #86efac;
}

.verified-sub-text {
  margin: 2px 0 0;
  font-size: 0.82rem;
  color: #475569;
  font-weight: 600;
}

.verified-sync-note {
  margin: 6px 0 0;
  font-size: 0.8rem;
  color: #047857;
}

.btn-reset-lookup {
  background: none;
  border: 1px solid #cbd5e1;
  color: #64748b;
  border-radius: 6px;
  padding: 4px 8px;
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-reset-lookup:hover {
  background: #fee2e2;
  border-color: #fca5a5;
  color: #dc2626;
}
</style>

<script>
(function() {
  const toggleBtn = document.getElementById('btnToggleReturningBox');
  const panel = document.getElementById('returningVerifyPanel');
  const statusBox = document.getElementById('verifyStatusBox');
  const verifiedCard = document.getElementById('verifiedPatientCard');
  const resetBtn = document.getElementById('btnResetLookup');

  if (!toggleBtn || !panel) return;

  // Toggle panel display
  toggleBtn.addEventListener('click', () => {
    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
  });

  // Tab switching
  const tabs = document.querySelectorAll('.verify-tab-btn');
  tabs.forEach(tab => {
    tab.addEventListener('click', function() {
      tabs.forEach(t => t.classList.remove('active'));
      document.querySelectorAll('.verify-tab-content').forEach(c => c.classList.remove('active'));
      this.classList.add('active');
      const target = document.getElementById(this.dataset.tab);
      if (target) target.classList.add('active');
    });
  });

  function showStatus(msg, type = 'error') {
    if (!statusBox) return;
    statusBox.className = 'verify-status-box ' + type;
    statusBox.textContent = msg;
    statusBox.style.display = 'block';
  }

  function hideStatus() {
    if (statusBox) statusBox.style.display = 'none';
  }

  // Auto-fill form fields with verified patient data
  function applyPatientData(p, visitCount = 1) {
    if (!p) return;

    // First Name, Last Name, M.I.
    const fnInput = document.getElementById('firstName');
    const lnInput = document.getElementById('lastName');
    const miInput = document.getElementById('middleInitial');
    if (fnInput && p.first_name) { fnInput.value = p.first_name; fnInput.readOnly = true; }
    if (lnInput && p.last_name) { lnInput.value = p.last_name; lnInput.readOnly = true; }
    if (miInput && p.middle_initial) { miInput.value = p.middle_initial; miInput.readOnly = true; }

    // HRN
    const hrnInput = document.getElementById('hrn');
    if (hrnInput && p.hrn) { hrnInput.value = p.hrn; }

    // Gender
    const genderInput = document.getElementById('gender');
    if (genderInput && p.gender) {
      genderInput.value = p.gender;
      genderInput.style.pointerEvents = 'none';
    }

    // Birthdate & Age
    const bdayInput = document.getElementById('birthdate');
    const ageInput = document.getElementById('age');
    if (bdayInput && p.birthdate) {
      bdayInput.value = p.birthdate;
      bdayInput.readOnly = true;
      bdayInput.dispatchEvent(new Event('change'));
    }
    if (ageInput && p.age) {
      ageInput.value = p.age;
    }

    // Contact Number & Email & Address
    const phoneInput = document.getElementById('contactNumber');
    if (phoneInput && p.contact_number) { phoneInput.value = p.contact_number; }

    const emailInput = document.getElementById('emailAddress') || document.getElementById('email');
    if (emailInput && p.email) { emailInput.value = p.email; }

    const addrInput = document.getElementById('address');
    if (addrInput && p.address) { addrInput.value = p.address; }

    // Set Patient Type dropdown if available
    const patientTypeSelect = document.getElementById('patientType');
    if (patientTypeSelect) {
      for (let opt of patientTypeSelect.options) {
        if (opt.value === 'returning' || opt.text.toLowerCase().includes('old') || opt.text.toLowerCase().includes('returning')) {
          opt.selected = true;
          patientTypeSelect.value = opt.value;
          break;
        }
      }
      patientTypeSelect.dispatchEvent(new Event('change'));
    }

    // Update verified badge card
    document.getElementById('verifiedFullName').textContent = p.full_name;
    document.getElementById('verifiedIdDisplay').textContent = 'Patient ID: ' + p.patient_id;
    document.getElementById('verifiedHrnDisplay').textContent = 'HRN: ' + p.hrn;
    document.getElementById('verifiedVisitCount').textContent = 'Previous Visits: ' + visitCount;

    // Show verified card and hide search panel
    if (verifiedCard) verifiedCard.style.display = 'block';
    panel.style.display = 'none';
    toggleBtn.style.display = 'none';
    hideStatus();

    // Store verified patient ID in a hidden input if present or create one
    let verifiedHidden = document.getElementById('verifiedPatientId');
    if (!verifiedHidden) {
      verifiedHidden = document.createElement('input');
      verifiedHidden.type = 'hidden';
      verifiedHidden.id = 'verifiedPatientId';
      verifiedHidden.name = 'verified_patient_id';
      const form = document.getElementById('regForm');
      if (form) form.appendChild(verifiedHidden);
    }
    verifiedHidden.value = p.patient_id;

    if (typeof showToast === 'function') {
      showToast('Verified: Record linked for ' + p.full_name, 'success');
    }
  }

  // Lookup by ID
  const btnLookupById = document.getElementById('btnLookupById');
  if (btnLookupById) {
    btnLookupById.addEventListener('click', async () => {
      const idVal = (document.getElementById('lookupPatientId')?.value || '').trim();
      if (!idVal) {
        showStatus('Please enter your Patient ID or HRN.');
        return;
      }

      showStatus('Verifying record with MMWGH SDN database...', 'loading');
      try {
        const res = await fetch('/sdn/api/patient_verify.php?patient_id=' + encodeURIComponent(idVal))
          .then(r => r.json());
        if (res.success && res.found) {
          applyPatientData(res.patient, res.visit_count);
        } else {
          showStatus(res.message || 'No existing patient record found with ID ' + idVal);
        }
      } catch (e) {
        showStatus('Network error while verifying patient record.');
      }
    });
  }

  // Lookup by Name + Birthdate
  const btnLookupByName = document.getElementById('btnLookupByName');
  if (btnLookupByName) {
    btnLookupByName.addEventListener('click', async () => {
      const ln = (document.getElementById('lookupLastName')?.value || '').trim();
      const fn = (document.getElementById('lookupFirstName')?.value || '').trim();
      const bd = (document.getElementById('lookupBirthdate')?.value || '').trim();

      if (!ln || !fn || !bd) {
        showStatus('Please enter Last Name, First Name, and Birthdate.');
        return;
      }

      showStatus('Verifying record with MMWGH SDN database...', 'loading');
      try {
        const params = new URLSearchParams({
          last_name: ln,
          first_name: fn,
          birthdate: bd
        });
        const res = await fetch('/sdn/api/patient_verify.php?' + params.toString())
          .then(r => r.json());
        if (res.success && res.found) {
          applyPatientData(res.patient, res.visit_count);
        } else {
          showStatus(res.message || 'No existing patient record matched your name and birthdate.');
        }
      } catch (e) {
        showStatus('Network error while verifying patient record.');
      }
    });
  }

  // Unlink / Reset
  if (resetBtn) {
    resetBtn.addEventListener('click', () => {
      const verifiedHidden = document.getElementById('verifiedPatientId');
      if (verifiedHidden) verifiedHidden.value = '';

      // Make name and bday editable again
      const fnInput = document.getElementById('firstName');
      const lnInput = document.getElementById('lastName');
      const miInput = document.getElementById('middleInitial');
      const bdayInput = document.getElementById('birthdate');
      const genderInput = document.getElementById('gender');

      if (fnInput) fnInput.readOnly = false;
      if (lnInput) lnInput.readOnly = false;
      if (miInput) miInput.readOnly = false;
      if (bdayInput) bdayInput.readOnly = false;
      if (genderInput) genderInput.style.pointerEvents = '';

      if (verifiedCard) verifiedCard.style.display = 'none';
      toggleBtn.style.display = 'inline-flex';
      hideStatus();
    });
  }
})();
</script>
