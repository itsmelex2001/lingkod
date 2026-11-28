<?php session_start(); ?>
<!doctype html>
<html lang="en" class="regis-page">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Neurology Registration | LINGKOD</title>
  <link rel="icon" type="image/png" href="/sdn/public/assets/mmwgh_logo.png" />
  <link rel="apple-touch-icon" href="/sdn/public/assets/mmwgh_logo.png" />
    <link rel="stylesheet" href="/sdn/public/css/global.css?v=9" />
    <link rel="stylesheet" href="/sdn/public/css/navigation.css?v=9" />
    <link rel="stylesheet" href="/sdn/public/css/forms.css?v=20261128" />
    <link rel="stylesheet" href="/sdn/public/css/modal-export.css?v=20261128" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
  </head>
  <body>
    <?php require_once __DIR__ . '/privacy-consent-modal.php'; ?>
    <?php
    if (isset($_GET['source']) && $_GET['source'] === 'dept') {
        $backUrl = '/sdn/pages/register.php';
    } elseif (isset($_GET['source']) && $_GET['source'] === 'schedules') {
        $backUrl = '/sdn/pages/schedules.php';
    } else {
        $backUrl = '/sdn/pages/register.php';
    }
    ?>
    <div class="registration-container">
      <div class="reg-header-bar">
        <div class="reg-header-left">
          <a href="<?= htmlspecialchars($backUrl) ?>" class="btn-back" title="Back">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
          </a>
          <div>
            <h2>Neurology Patient Registration</h2>
            <p style="margin:2px 0 0 0; font-size:0.83rem; color:#64748b;">Clinical neurological consultations, headache care, and specialized testing.</p>
          </div>
        </div>
        <div class="reg-header-right">
          <label class="checkbox-label" style="margin: 0; padding: 6px 12px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px;">
            <input type="checkbox" id="inquiryOnly" />
            <span style="font-size: 0.88rem; font-weight: 600; color: #166534;">Inquiry Only</span>
          </label>
        </div>
      </div>


      <?php if (!empty($_GET['service']) || !empty($_GET['spec'])): ?>
      <div class="service-selected-banner">
        <div class="banner-badge">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
          <span>Selected Service</span>
        </div>
        <div class="banner-text">
          <strong><?= htmlspecialchars($_GET['service'] ?? $_GET['spec']) ?></strong>
          <?php if (!empty($_GET['doctor'])): ?>
            <span class="banner-doc">• Attending Physician: <strong><?= htmlspecialchars($_GET['doctor']) ?></strong></span>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

            <!-- 3-Phase Stepper Indicator (Line & Dot) -->
      <div class="stepper-wrapper">
        <div class="stepper-line-container">
          <div class="stepper-line-bg"></div>
          <div class="stepper-line-fill" id="stepperLineFill" style="width: 0%;"></div>
        </div>
        <div class="stepper-steps">
          <div class="stepper-node active" data-step="1">
            <div class="stepper-dot"><span class="dot-inner"></span></div>
            <div class="stepper-text">
              <span class="stepper-phase-num">Phase 1</span>
              <span class="stepper-phase-name">Personal Information</span>
            </div>
          </div>
          <div class="stepper-node" data-step="2">
            <div class="stepper-dot"><span class="dot-inner"></span></div>
            <div class="stepper-text">
              <span class="stepper-phase-num">Phase 2</span>
              <span class="stepper-phase-name">Medical Information</span>
            </div>
          </div>
          <div class="stepper-node" data-step="3">
            <div class="stepper-dot"><span class="dot-inner"></span></div>
            <div class="stepper-text">
              <span class="stepper-phase-num">Phase 3</span>
              <span class="stepper-phase-name">Appointment Details</span>
            </div>
          </div>
        </div>
      </div>

      <form id="regForm" novalidate>
        <input type="hidden" id="department" value="neurology" />
        <!-- Anti-bot honeypot field -->
        <input type="text" id="website_url" name="website_url" style="display:none !important; position:absolute; left:-9999px;" tabindex="-1" autocomplete="off" />

        <div class="form-phase-container">
          <!-- Phase 1: Personal Information -->
          <div class="form-phase active" id="phase1" data-phase="1">
            <div class="phase-header">
              <h3><span class="sec-num">1</span> Personal Information</h3>
              <p class="phase-subtitle">Step 1 of 3 • Patient Demographics</p>
            </div>

            <div class="grid-row name-grid-row" style="grid-template-columns: 1fr 1fr 85px;">
              <div class="form-group">
                <label>First Name <span class="asterisk">*</span></label>
                <input id="firstName" type="text" placeholder="First Name" maxlength="60" required />
              </div>
              <div class="form-group">
                <label>Last Name <span class="asterisk">*</span></label>
                <input id="lastName" type="text" placeholder="Last Name" maxlength="60" required />
              </div>
              <div class="form-group">
                <label>M.I. <span style="font-weight:400; font-size:0.75rem; color:#94a3b8;">(Opt)</span></label>
                <input type="text" id="middleInitial" placeholder="e.g. B" maxlength="5" />
              </div>
            </div>
            <input type="hidden" id="fullName" />

            <div class="grid-row" style="grid-template-columns: 1fr 1.2fr 75px;">
              <div class="form-group">
                <label>Gender <span class="asterisk">*</span></label>
                <select id="gender" required>
                  <option value="" disabled selected>Select gender...</option>
                  <option>Male</option>
                  <option>Female</option>
                  <option>Other</option>
                </select>
              </div>
              <div class="form-group">
                <label>Birthdate <span class="asterisk">*</span></label>
                <input type="date" id="birthdate" min="<?= date('Y-m-d', strtotime('-120 years')) ?>" max="<?= date('Y-m-d') ?>" required />
              </div>
              <div class="form-group">
                <label>Age</label>
                <input type="number" id="age" placeholder="Age" readonly tabindex="-1" />
              </div>
            </div>

            <div class="grid-row">
              <div class="form-group">
                <label>Contact Number <span class="asterisk">*</span></label>
                <input id="contactNumber" type="tel" placeholder="09XXXXXXXXX" pattern="^(09|\+639)\d{9}$" maxlength="13" title="Format: 09XXXXXXXXX or +639XXXXXXXXX" required />
              </div>
              <div class="form-group">
                <label>Email Address</label>
                <input id="emailAddress" type="email" placeholder="Optional" maxlength="100" />
              </div>
            </div>

            <div class="grid-row" style="grid-template-columns: 1.4fr 1fr; align-items: flex-end;">
              <div class="form-group">
                <label>Complete Address <span class="asterisk">*</span></label>
                <input id="address" type="text" placeholder="Enter full address" maxlength="255" required />
              </div>
              <div class="form-group" id="groupCompanion">
                <label>Accompanying companion? <span class="asterisk">*</span></label>
                <div class="radio-group" style="height: 31px; display: flex; align-items: center;">
                  <label><input type="radio" name="companion" value="yes" /> Yes</label>
                  <label><input type="radio" name="companion" value="no" checked /> No</label>
                </div>
              </div>
            </div>
          </div>

          <!-- Phase 2: Medical Information -->
          <div class="form-phase" id="phase2" data-phase="2" style="display: none;">
            <div class="phase-header">
              <h3><span class="sec-num">2</span> Medical Information</h3>
              <p class="phase-subtitle">Step 2 of 3 • Clinical Assessment & Chief Complaint</p>
            </div>

            <div class="grid-row">
              <div class="form-group" id="groupChiefComplaints">
                <label id="lblChiefComplaints">Chief Complaint <span class="asterisk">*</span></label>
                <select id="chiefComplaints" required>
                  <option value="" disabled selected>Select primary neurological concern...</option>
                  <option value="Migraine, Tension & Chronic Severe Headaches">Migraine, Tension &amp; Chronic Severe Headaches</option>
                  <option value="Stroke Recovery, Hemiparesis & Post-CVA Care">Stroke Recovery, Hemiparesis &amp; Post-CVA Care</option>
                  <option value="Epilepsy, Seizures & Unexplained Loss of Consciousness">Epilepsy, Seizures &amp; Unexplained Loss of Consciousness</option>
                  <option value="Parkinson's Disease, Hand Tremors & Movement Disorders">Parkinson's Disease, Hand Tremors &amp; Movement Disorders</option>
                  <option value="Numbness, Tingling & Peripheral Neuropathy">Numbness, Tingling &amp; Peripheral Neuropathy</option>
                  <option value="Chronic Dizziness, Vertigo & Loss of Balance">Chronic Dizziness, Vertigo &amp; Loss of Balance</option>
                  <option value="Memory Decline, Cognitive Impairment & Dementia Evaluation">Memory Decline, Cognitive Impairment &amp; Dementia Evaluation</option>
                  <option value="Facial Weakness / Bell's Palsy">Facial Weakness / Bell's Palsy</option>
                  <option value="Nerve Compression / Sciatica / Radiculopathy">Nerve Compression / Sciatica / Radiculopathy</option>
                  <option value="Other">Other Neurological Chief Complaint / Concern (specify below)</option>
                </select>
              </div>

              <div class="form-group" id="groupConsultationNature">
                <label>Nature of Consultation <span class="asterisk">*</span></label>
                <select id="consultationAbout" required>
                  <option value="" disabled selected>Select nature of consultation...</option>
                  <option>Follow-up Checkup</option>
                  <option>Initial Evaluation</option>
                  <option>Laboratory Results Review</option>
                  <option>Medication Refill</option>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label>Description of disease/illness or chief complaint</label>
              <textarea id="illnessDescription" placeholder="Additional details, notes, or chief complaint description..." rows="3" style="min-height: 60px; height: 68px;"></textarea>
            </div>
          </div>

          <!-- Phase 3: Appointment Details -->
          <div class="form-phase" id="phase3" data-phase="3" style="display: none;">
            <div class="phase-header">
              <h3><span class="sec-num">3</span> Appointment Details</h3>
              <p class="phase-subtitle">Step 3 of 3 • Scheduling & Preferences</p>
            </div>

            <div class="grid-row">
              <div class="form-group">
                <label>Type of Client <span class="asterisk">*</span></label>
                <select id="patientType" required>
                  <option value="" disabled selected>Select client type...</option>
                  <option value="first-time">First Time Patient</option>
                  <option value="returning">Returning Patient</option>
                </select>
              </div>
              <div class="form-group">
                <label>Type of Appointment <span class="asterisk">*</span></label>
                <select id="appointmentType" required>
                  <option value="online" selected>Online Scheduled Appointment</option>
                </select>
              </div>
            </div>

            <div class="grid-row">
              <div class="form-group">
                <label>Consultation Mode <span class="asterisk">*</span></label>
                <select id="consultationMode" required>
                  <option value="" disabled selected>Select mode...</option>
                  <option>Face-to-Face</option>
                  <option>E-consultation</option>
                </select>
              </div>
              <div class="form-group">
                <label>Referral Source</label>
                <input id="referralSource" type="text" placeholder="Optional" />
              </div>
            </div>

            <div class="grid-row">
              <div class="form-group">
                <label>Appointment Date <span class="asterisk">*</span></label>
                <input type="date" id="appointmentDate" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" max="<?= date('Y-m-d', strtotime('+1 year')) ?>" required />
              </div>
              <div class="form-group">
                <label>Appointment Time <span class="asterisk">*</span></label>
                <input type="time" id="appointmentTime" required />
              </div>
            </div>
          </div>
        </div>

        <div class="form-phase-footer">
          <div class="footer-left">
            <button type="button" class="btn-phase-prev" id="btnPrevPhase" style="display: none;">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 18l-6-6 6-6"/></svg>
              <span>Back</span>
            </button>
            <p class="required-legend"><span class="asterisk">*</span> Indicates a required field</p>
          </div>
          <div class="footer-right">
            <button type="button" class="btn-phase-next" id="btnNextPhase">
              <span>Continue to Medical Info</span>
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 18l6-6-6-6"/></svg>
            </button>
            <button type="submit" class="btn-submit" id="btnSubmitPhase" style="display: none;">
              Confirm Registration
            </button>
          </div>
        </div>
      </form>
    </div>

    <!-- MODAL CONFIRMATION (Recreated Portrait Layout) -->
    <div id="summaryModal" class="modal-overlay">
      <div class="modal-box">
        <div id="printArea">
          <!-- MMWGH Background Watermark -->
          <div class="print-watermark" aria-hidden="true">
            <img src="/sdn/public/assets/mmwgh_logo.png" alt="MMWGH Logo Watermark" />
          </div>

          <div class="print-header">
            <div class="print-header-inner">
              <div class="print-header-badge">
                <img src="/sdn/public/assets/mmwgh_logo.png" alt="MMWGH Logo" class="print-header-logo-img" />
              </div>
              <div class="print-header-content">
                <div class="print-sub-header">MARIVELES MENTAL WELLNESS AND GENERAL HOSPITAL • SDN EMR</div>
                <h1 class="print-clinic-name">Neurology Patient Registration</h1>
                <div class="print-meta-row">
                  <p class="print-ref">Ref No: <strong id="refNumber"></strong></p>
                  <span class="print-meta-sep">·</span>
                  <p class="print-date">Submitted: <strong id="submittedDate"></strong></p>
                </div>
              </div>
            </div>
          </div>

          <div class="print-body">
            <div class="print-section">
              <div class="print-section-title">Department</div>
              <div class="print-row">
                <div class="print-field full">
                  <span class="print-label">Selected Department</span>
                  <span class="print-value" id="p-department"></span>
                </div>
              </div>
            </div>

            <div class="print-section">
              <div class="print-section-title">Personal Information</div>
              <div class="print-row">
                <div class="print-field">
                  <span class="print-label">Full Name</span>
                  <span class="print-value" id="p-fullName"></span>
                </div>
                <div class="print-field">
                  <span class="print-label">Middle Initial</span>
                  <span class="print-value" id="p-middleInitial"></span>
                </div>
              </div>
              <div class="print-row">
                <div class="print-field">
                  <span class="print-label">Gender</span>
                  <span class="print-value" id="p-gender"></span>
                </div>
              </div>
              <div class="print-row">
                <div class="print-field">
                  <span class="print-label">Birthdate</span>
                  <span class="print-value" id="p-birthdate"></span>
                </div>
                <div class="print-field">
                  <span class="print-label">Age</span>
                  <span class="print-value" id="p-age"></span>
                </div>
              </div>
              <div class="print-row">
                <div class="print-field">
                  <span class="print-label">Contact Number</span>
                  <span class="print-value" id="p-contact"></span>
                </div>
                <div class="print-field">
                  <span class="print-label">Email</span>
                  <span class="print-value" id="p-email"></span>
                </div>
              </div>
              <div class="print-row">
                <div class="print-field full">
                  <span class="print-label">Address</span>
                  <span class="print-value" id="p-address"></span>
                </div>
              </div>
              <div class="print-row">
                <div class="print-field">
                  <span class="print-label">With Companion?</span>
                  <span class="print-value" id="p-companion"></span>
                </div>
              </div>
            </div>

            <div class="print-section">
              <div class="print-section-title">Medical Information</div>
              <div class="print-row">
                <div class="print-field full">
                  <span class="print-label">Chief Complaint</span>
                  <span class="print-value" id="p-complaints"></span>
                </div>
              </div>
              <div class="print-row">
                <div class="print-field full">
                  <span class="print-label">Nature of Consultation</span>
                  <span class="print-value" id="p-consultation"></span>
                </div>
              </div>
              <div class="print-row">
                <div class="print-field full">
                  <span class="print-label">Illness Description</span>
                  <span class="print-value" id="p-illness"></span>
                </div>
              </div>
            </div>

            <div class="print-section">
              <div class="print-section-title">Appointment Details</div>
              <div class="print-row">
                <div class="print-field">
                  <span class="print-label">Type of Client</span>
                  <span class="print-value" id="p-patientType"></span>
                </div>
                <div class="print-field">
                  <span class="print-label">Type of Appointment</span>
                  <span class="print-value" id="p-appointmentType"></span>
                </div>
              </div>
              <div class="print-row">
                <div class="print-field">
                  <span class="print-label">Inquiry Only</span>
                  <span class="print-value" id="p-inquiryOnly">No</span>
                </div>
                <div class="print-field">
                  <span class="print-label">Consultation Mode</span>
                  <span class="print-value" id="p-mode"></span>
                </div>
              </div>
                <div class="print-field">
                  <span class="print-label">Appointment Date</span>
                  <span class="print-value" id="p-appointmentDate"></span>
                </div>
              </div>
              <div class="print-row">
                <div class="print-field full">
                  <span class="print-label">Referral Source</span>
                  <span class="print-value" id="p-referral"></span>
                </div>
              </div>
            </div>

            <div class="print-signature-row">
              <div class="print-sig-block">
                <div class="print-sig-line"></div>
                <p>Patient / Informant Signature</p>
              </div>
              <div class="print-sig-block">
                <div class="print-sig-line"></div>
                <p>Triage / Attending Staff</p>
              </div>
              <div class="print-sig-block">
                <div class="print-sig-line"></div>
                <p>Date & Verification Stamp</p>
              </div>
            </div>

            <div class="print-footer-note">
              Official Patient Registration Copy • SDN Integrated Healthcare System • Please present this document upon arrival.
            </div>
          </div>
        </div>

        <div class="modal-actions no-print">
          <div class="modal-optional-row">
            <span class="modal-optional-label">Optional — save a copy</span>
            <div class="modal-optional-btns">
              <button id="btnDownloadPDF" class="btn-export">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Download PDF
              </button>
              <button id="btnPrint" class="btn-export">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                Print
              </button>
            </div>
          </div>

          <button id="btnClose" class="btn-confirm-close">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            Confirm
          </button>
        </div>

      </div>
    </div>

    <div id="successToast" class="success-toast">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="20 6 9 17 4 12"/></svg>
      <div>
        <strong>Registration Confirmed!</strong>
        <span>Your registration has been successfully submitted.</span>
      </div>
    </div>

    <div id="pdfContainer">
      <div class="print-watermark" aria-hidden="true">
        <img src="/sdn/public/assets/mmwgh_logo.png" alt="MMWGH Logo Watermark" />
      </div>
      <div id="pdf-header">
        <div class="pdf-header-logo">
          <img src="/sdn/public/assets/mmwgh_logo.png" alt="MMWGH Logo" class="print-header-logo-img" />
        </div>
        <div>
          <div class="pdf-header-title">Neurology Patient Registration</div>
          <div class="pdf-header-meta">Reference No: <strong id="pdf-refNumber"></strong></div>
          <div class="pdf-header-meta">Date Submitted: <strong id="pdf-submittedDate"></strong></div>
        </div>
      </div>

      <div class="pdf-body-padding">
        <div class="pdf-section">
          <div class="pdf-section-title">Department</div>
          <div class="pdf-row">
            <div class="pdf-field full">
              <div class="pdf-label">Selected Department</div>
              <div class="pdf-value" id="pdf-department"></div>
            </div>
          </div>
        </div>

        <div class="pdf-section">
          <div class="pdf-section-title">Personal Information</div>
          <div class="pdf-row">
            <div class="pdf-field">
              <div class="pdf-label">Full Name</div>
              <div class="pdf-value" id="pdf-fullName"></div>
            </div>
            <div class="pdf-field">
              <div class="pdf-label">Gender</div>
              <div class="pdf-value" id="pdf-gender"></div>
            </div>
          </div>
          <div class="pdf-row">
            <div class="pdf-field">
              <div class="pdf-label">Birthdate</div>
              <div class="pdf-value" id="pdf-birthdate"></div>
            </div>
            <div class="pdf-field">
              <div class="pdf-label">Age</div>
              <div class="pdf-value" id="pdf-age"></div>
            </div>
          </div>
          <div class="pdf-row">
            <div class="pdf-field">
              <div class="pdf-label">Contact Number</div>
              <div class="pdf-value" id="pdf-contact"></div>
            </div>
            <div class="pdf-field">
              <div class="pdf-label">Email Address</div>
              <div class="pdf-value" id="pdf-email"></div>
            </div>
          </div>
          <div class="pdf-row">
            <div class="pdf-field full">
              <div class="pdf-label">Complete Address</div>
              <div class="pdf-value" id="pdf-address"></div>
            </div>
          </div>
          <div class="pdf-row">
            <div class="pdf-field">
              <div class="pdf-label">With Companion?</div>
              <div class="pdf-value" id="pdf-companion"></div>
            </div>
          </div>
        </div>

        <div class="pdf-section">
          <div class="pdf-section-title">Medical Information</div>
          <div class="pdf-row">
            <div class="pdf-field full">
              <div class="pdf-label">Chief Complaints</div>
              <div class="pdf-value" id="pdf-complaints"></div>
            </div>
          </div>
          <div class="pdf-row">
            <div class="pdf-field">
              <div class="pdf-label">Nature of Consultation</div>
              <div class="pdf-value" id="pdf-consultation"></div>
            </div>
          </div>
          <div class="pdf-row">
            <div class="pdf-field full">
              <div class="pdf-label">Illness Description</div>
              <div class="pdf-value" id="pdf-illness"></div>
            </div>
          </div>
        </div>

        <div class="pdf-section">
          <div class="pdf-section-title">Appointment Details</div>
          <div class="pdf-row">
            <div class="pdf-field">
              <div class="pdf-label">Type of Client</div>
              <div class="pdf-value" id="pdf-patientType"></div>
            </div>
            <div class="pdf-field">
              <div class="pdf-label">Type of Appointment</div>
              <div class="pdf-value" id="pdf-appointmentType"></div>
            </div>
          </div>
          <div class="pdf-row">
            <div class="pdf-field">
              <div class="pdf-label">Consultation Mode</div>
              <div class="pdf-value" id="pdf-mode"></div>
            </div>
            <div class="pdf-field">
              <div class="pdf-label">Appointment Date</div>
              <div class="pdf-value" id="pdf-appointmentDate"></div>
            </div>
          </div>
          <div class="pdf-row">
            <div class="pdf-field">
              <div class="pdf-label">Referral Source</div>
              <div class="pdf-value" id="pdf-referral"></div>
            </div>
          </div>
        </div>

        <div class="pdf-signature-table">
          <div class="pdf-signature-row">
            <div class="pdf-signature-cell">
              <div class="pdf-signature-line"></div>
              <div class="pdf-signature-label">Patient / Guardian Signature</div>
            </div>
            <div class="pdf-signature-cell">
              <div class="pdf-signature-line"></div>
              <div class="pdf-signature-label">Staff on Duty</div>
            </div>
            <div class="pdf-signature-cell">
              <div class="pdf-signature-line"></div>
              <div class="pdf-signature-label">Date & Time Received</div>
            </div>
          </div>
        </div>

        <div class="pdf-footer-note-text">
          This registration form is for medical facility use only. Please present this document upon arrival.
        </div>
      </div>
    </div>

    <script src="/sdn/public/js/utils.js?v=20261128"></script>
    <script src="/sdn/public/js/navigation.js?v=20261128"></script>
    <script src="/sdn/public/js/print-pdf-setup.js?v=20261128"></script>
    <script src="/sdn/public/js/regis-stepper.js?v=20261128"></script>
    <script src="/sdn/public/js/regis-neurology.js?v=20261128"></script>
    <script src="/sdn/public/js/modal-export.js?v=20261128"></script>
  </body>
</html>
