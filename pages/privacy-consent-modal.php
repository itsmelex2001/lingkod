<?php
/**
 * pages/privacy-consent-modal.php
 *
 * Mandatory Data Privacy Consent and Intended Use Disclaimer Modal.
 * Must be explicitly accepted by the patient before accessing the appointment booking form.
 */
?>
<div id="privacyConsentOverlay" class="privacy-consent-overlay" role="dialog" aria-modal="true" aria-labelledby="consentTitle">
  <div class="privacy-consent-box">
    
    <!-- Header -->
    <div class="consent-header">
      <div class="consent-logo-strip">
        <img src="/sdn/public/assets/mmwgh_logo.png" alt="MMWGH Logo" class="consent-logo-img" />
        <div>
          <span class="consent-republic-text">Republic of the Philippines • Department of Health</span>
          <h2 id="consentTitle" class="consent-title">Data Privacy Consent &amp; Intended Use Disclaimer</h2>
          <p class="consent-sub">Mariveles Mental Wellness and General Hospital (MMWGH) • SDN Appointment System</p>
        </div>
      </div>
    </div>

    <!-- Scrollable Terms Body -->
    <div class="consent-body">
      <div class="consent-notice-banner">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
          <path d="m9 12 2 2 4-4"></path>
        </svg>
        <div>
          <strong>Mandatory Pre-Registration Requirement</strong>
          <p>In compliance with the Data Privacy Act of 2012 (R.A. 10173) and MMWGH Clinical Outpatient Regulations, you must review and explicitly agree to the following terms before proceeding with your appointment registration.</p>
        </div>
      </div>

      <div class="consent-section-item">
        <h4>1. Republic Act No. 10173 (Data Privacy Act of 2012) Consent</h4>
        <p>
          I hereby authorize the <strong>Mariveles Mental Wellness and General Hospital (MMWGH)</strong>, its authorized healthcare professionals, and administrative staff to collect, record, organize, store, update, retrieve, consult, use, and process my personal health information, demographics, contact details, and clinical consultation history. 
        </p>
        <p>
          I understand that this data is collected solely for the purposes of outpatient scheduling, patient identification, medical diagnosis, clinical treatment, continuity of care, and statutory hospital health reporting in accordance with Philippine healthcare laws. My information shall be safeguarded with strict electronic confidentiality protocols and will not be disclosed to unauthorized third parties without my prior written consent.
        </p>
      </div>

      <div class="consent-section-item">
        <h4>2. Official Intended Use &amp; Non-Emergency Disclaimer</h4>
        <div class="consent-alert-box">
          <strong>⚠️ NOT FOR MEDICAL EMERGENCIES:</strong>
          <p>
            This online appointment booking system is strictly designated for <em>non-emergency, scheduled outpatient consultations</em>. If you or the patient are experiencing a life-threatening medical emergency, acute chest pain, stroke symptoms, uncontrolled bleeding, severe trauma, or acute psychiatric crisis, <strong>do not wait for an online appointment</strong>. Immediately proceed to the nearest Emergency Room (ER) or contact emergency services at <strong>911</strong> or MMWGH Emergency Hotline.
          </p>
        </div>
      </div>

      <div class="consent-section-item">
        <h4>3. Truthfulness, Accuracy &amp; Verification Protocol</h4>
        <p>
          I certify that all personal, demographic, and medical concern details submitted through this portal are true, correct, and complete to the best of my knowledge. I understand that deliberate submission of falsified identities, dummy dates, or fraudulent contact information may result in immediate cancellation of my appointment and blacklisting from the online booking queue.
        </p>
      </div>

      <div class="consent-section-item">
        <h4>4. Advance Booking &amp; Facility Arrival Policy</h4>
        <p>
          In accordance with MMWGH outpatient regulations:
        </p>
        <ul>
          <li><strong>Advance Scheduling:</strong> Online appointments must be scheduled at least <strong>one (1) business day in advance</strong> to allow adequate clinical chart preparation. Same-day bookings must be processed in person at the hospital triage desk as Walk-ins.</li>
          <li><strong>Check-in Requirement:</strong> Appointed patients are required to arrive at the MMWGH Outpatient Reception at least <strong>30 minutes prior</strong> to their confirmed appointment slot for identity verification and vital signs triage.</li>
        </ul>
      </div>
    </div>

    <!-- Footer Agreement Controls -->
    <div class="consent-footer">
      <label class="consent-checkbox-row">
        <input type="checkbox" id="consentCheckbox" />
        <span class="consent-checkbox-label">
          <strong>I have read, understood, and voluntarily agree</strong> to the Data Privacy Consent and Official Intended Use Disclaimer of MMWGH.
        </span>
      </label>

      <div class="consent-actions">
        <a href="/sdn/index.php" class="btn-consent-decline">
          Decline &amp; Exit
        </a>
        <button type="button" id="btnConsentProceed" class="btn-consent-proceed" disabled>
          <span>Proceed to Appointment Form</span>
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="9 18 15 12 9 6"></polyline>
          </svg>
        </button>
      </div>
    </div>

  </div>
</div>

<style>
.privacy-consent-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.78);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  z-index: 99999;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.25rem;
  opacity: 1;
  transition: opacity 0.25s ease;
}

.privacy-consent-overlay.hidden-consent {
  opacity: 0;
  pointer-events: none;
  visibility: hidden;
}

.privacy-consent-box {
  background: #ffffff;
  width: 100%;
  max-width: 820px;
  max-height: 90vh;
  border-radius: 20px;
  box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.1);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  animation: modalScaleIn 0.28s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes modalScaleIn {
  from { transform: scale(0.95); opacity: 0; }
  to { transform: scale(1); opacity: 1; }
}

.consent-header {
  padding: 1.5rem 1.75rem 1.25rem;
  background: linear-gradient(135deg, #064e3b 0%, #065f46 100%);
  color: #ffffff;
  border-bottom: 3px solid #10b981;
}

.consent-logo-strip {
  display: flex;
  align-items: center;
  gap: 16px;
}

.consent-logo-img {
  width: 56px;
  height: 56px;
  object-fit: contain;
  background: #ffffff;
  padding: 4px;
  border-radius: 12px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.2);
  flex-shrink: 0;
}

.consent-republic-text {
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  color: #a7f3d0;
  display: block;
  margin-bottom: 2px;
}

.consent-title {
  margin: 0;
  font-size: 1.35rem;
  font-weight: 800;
  letter-spacing: -0.3px;
  color: #ffffff;
}

.consent-sub {
  margin: 3px 0 0;
  font-size: 0.82rem;
  color: #d1fae5;
  font-weight: 500;
}

.consent-body {
  padding: 1.5rem 1.75rem;
  overflow-y: auto;
  flex: 1;
  font-size: 0.9rem;
  color: #334155;
  line-height: 1.6;
}

.consent-notice-banner {
  background: #ecfdf5;
  border: 1.5px solid #a7f3d0;
  border-radius: 12px;
  padding: 12px 16px;
  display: flex;
  gap: 14px;
  align-items: flex-start;
  margin-bottom: 1.5rem;
  color: #065f46;
}

.consent-notice-banner svg {
  color: #059669;
  flex-shrink: 0;
  margin-top: 2px;
}

.consent-notice-banner strong {
  display: block;
  font-size: 0.95rem;
  margin-bottom: 3px;
}

.consent-notice-banner p {
  margin: 0;
  font-size: 0.85rem;
  line-height: 1.45;
}

.consent-section-item {
  margin-bottom: 1.25rem;
}

.consent-section-item h4 {
  margin: 0 0 6px 0;
  font-size: 0.98rem;
  font-weight: 700;
  color: #0f172a;
}

.consent-section-item p {
  margin: 0 0 8px 0;
  font-size: 0.88rem;
  color: #475569;
}

.consent-section-item ul {
  margin: 6px 0 8px 20px;
  padding: 0;
  font-size: 0.88rem;
  color: #475569;
}

.consent-section-item li {
  margin-bottom: 6px;
}

.consent-alert-box {
  background: #fffbeb;
  border-left: 4px solid #f59e0b;
  padding: 12px 14px;
  border-radius: 6px;
  color: #92400e;
  font-size: 0.86rem;
  margin-top: 4px;
}

.consent-alert-box strong {
  display: block;
  margin-bottom: 3px;
  font-size: 0.9rem;
}

.consent-alert-box p {
  margin: 0;
  color: #78350f;
}

.consent-footer {
  padding: 1.25rem 1.75rem;
  background: #f8fafc;
  border-top: 1.5px solid #e2e8f0;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.consent-checkbox-row {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  cursor: pointer;
  user-select: none;
}

.consent-checkbox-row input[type="checkbox"] {
  width: 20px;
  height: 20px;
  accent-color: #059669;
  cursor: pointer;
  margin-top: 2px;
  flex-shrink: 0;
}

.consent-checkbox-label {
  font-size: 0.88rem;
  color: #1e293b;
  line-height: 1.4;
}

.consent-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 12px;
  padding-top: 4px;
}

.btn-consent-decline {
  padding: 10px 18px;
  border-radius: 10px;
  background: #ffffff;
  border: 1.5px solid #cbd5e1;
  color: #64748b;
  font-size: 0.88rem;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.2s ease;
}

.btn-consent-decline:hover {
  background: #f1f5f9;
  color: #334155;
  border-color: #94a3b8;
}

.btn-consent-proceed {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 22px;
  border-radius: 10px;
  background: #059669;
  color: #ffffff;
  font-size: 0.92rem;
  font-weight: 700;
  border: none;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  box-shadow: 0 4px 14px rgba(5, 150, 105, 0.3);
}

.btn-consent-proceed:hover:not(:disabled) {
  background: #047857;
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(4, 120, 87, 0.4);
}

.btn-consent-proceed:disabled {
  background: #cbd5e1;
  color: #94a3b8;
  cursor: not-allowed;
  box-shadow: none;
  transform: none;
}
</style>

<script>
(function() {
  const overlay = document.getElementById('privacyConsentOverlay');
  const checkbox = document.getElementById('consentCheckbox');
  const proceedBtn = document.getElementById('btnConsentProceed');

  if (!overlay || !checkbox || !proceedBtn) return;

  // Check if consent was already explicitly agreed in this session
  if (sessionStorage.getItem('mmwgh_consent_agreed') === 'true') {
    overlay.classList.add('hidden-consent');
    return;
  }

  // Lock body scroll while consent modal is visible
  document.body.style.overflow = 'hidden';

  checkbox.addEventListener('change', function() {
    proceedBtn.disabled = !this.checked;
  });

  proceedBtn.addEventListener('click', function() {
    if (!checkbox.checked) return;
    sessionStorage.setItem('mmwgh_consent_agreed', 'true');
    overlay.classList.add('hidden-consent');
    document.body.style.overflow = '';
  });
})();
</script>
