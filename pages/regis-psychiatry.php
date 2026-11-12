<?php session_start(); ?>
<!doctype html>
<html lang="en" class="regis-page">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>OPU Psychiatry Registration | LINGKOD</title>
    <link rel="stylesheet" href="/sdn/public/css/global.css?v=9" />
    <link rel="stylesheet" href="/sdn/public/css/navigation.css?v=9" />
    <link rel="stylesheet" href="/sdn/public/css/forms.css?v=<?= time() ?>" />
    <link rel="stylesheet" href="/sdn/public/css/modal-export.css?v=<?= time() ?>" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <link rel="stylesheet" href="/sdn/public/css/regis-psychiatry.css?v=<?= time() ?>" />
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
            <h2>OPU Psychiatry Registration</h2>
            <p style="margin:2px 0 0 0; font-size:0.83rem; color:#64748b;">Comprehensive psychiatric consultation, evaluation, and therapy scheduling.</p>
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
        <input type="hidden" id="department" value="psychiatry" />
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
                <input type="text" id="firstName" placeholder="First Name" maxlength="60" required />
              </div>
              <div class="form-group">
                <label>Last Name <span class="asterisk">*</span></label>
                <input type="text" id="lastName" placeholder="Last Name" maxlength="60" required />
              </div>
              <div class="form-group">
                <label>M.I. <span style="font-weight:400; font-size:0.75rem; color:#94a3b8;">(Opt)</span></label>
                <input type="text" id="middleInitial" placeholder="e.g. B" maxlength="5" />
              </div>
            </div>
            <input type="hidden" id="fullName" />

            <div class="grid-row" style="grid-template-columns: 1.1fr 65px 1.1fr;">
              <div class="form-group">
                <label>Birthdate <span class="asterisk">*</span></label>
                <input type="date" id="birthdate" min="<?= date('Y-m-d', strtotime('-120 years')) ?>" max="<?= date('Y-m-d') ?>" required />
              </div>
              <div class="form-group">
                <label>Age</label>
                <input type="number" id="age" placeholder="Age" readonly tabindex="-1" />
              </div>
              <div class="form-group">
                <label>Informant</label>
                <input type="text" id="informant" placeholder="Informant (if any)" maxlength="100" />
              </div>
            </div>

            <div class="grid-row">
              <div class="form-group">
                <label>New / Old Patient <span class="asterisk">*</span></label>
                <select id="patientType" required>
                  <option value="" disabled selected>Select...</option>
                  <option value="first-time">New Patient</option>
                  <option value="returning">Old / Returning</option>
                </select>
              </div>
              <div class="form-group">
                <label>Contact No <span class="asterisk">*</span></label>
                <input type="tel" id="contactNumber" placeholder="09XXXXXXXXX" pattern="^(09|\+639)\d{9}$" maxlength="13" title="Format: 09XXXXXXXXX or +639XXXXXXXXX" required />
              </div>
            </div>
          </div>

          <!-- Phase 2: Medical Information -->
          <div class="form-phase" id="phase2" data-phase="2" style="display: none;">
            <div class="phase-header">
              <h3><span class="sec-num">2</span> Medical Information</h3>
              <p class="phase-subtitle">Step 2 of 3 • Clinical Concerns & Mental Wellness Assessment</p>
            </div>

            <div class="form-group" id="groupChiefComplaints">
              <label id="lblChiefComplaints">Chief Complaint <span class="asterisk">*</span></label>
              <select id="chiefComplaints" required>
                <option value="" disabled selected>Select primary psychiatric / mental health chief complaint...</option>
                <option value="Generalized Anxiety, Panic Attacks & Phobias">Generalized Anxiety, Panic Attacks &amp; Phobias</option>
                <option value="Major Depressive Disorder & Persistent Low Mood">Major Depressive Disorder &amp; Persistent Low Mood</option>
                <option value="Bipolar Disorder & Severe Mood Swings">Bipolar Disorder &amp; Severe Mood Swings</option>
                <option value="Schizophrenia & Hallucinations / Delusions">Schizophrenia &amp; Hallucinations / Delusions</option>
                <option value="Sleep Disturbances & Chronic Insomnia">Sleep Disturbances &amp; Chronic Insomnia</option>
                <option value="Substance Abuse, Addiction Assessment & Recovery">Substance Abuse, Addiction Assessment &amp; Recovery</option>
                <option value="Trauma, Post-Traumatic Stress (PTSD) & Acute Stress">Trauma, Post-Traumatic Stress (PTSD) &amp; Acute Stress</option>
                <option value="Child & Adolescent Behavioral / ADHD Concerns">Child &amp; Adolescent Behavioral / ADHD Concerns</option>
                <option value="Psychiatric Medication Review & Refill Consultation">Psychiatric Medication Review &amp; Refill Consultation</option>
                <option value="Other">Other Psychiatric / Mental Wellness Evaluation (specify below)</option>
              </select>
            </div>

            <div class="form-group">
              <label>Recommendation / Remarks</label>
              <textarea id="recommendationRemarks" rows="3" maxlength="1000" placeholder="Additional details, recommendations, or chief complaint..." style="min-height: 60px; height: 68px;"></textarea>
            </div>
          </div>

          <!-- Phase 3: Appointment Details -->
          <div class="form-phase" id="phase3" data-phase="3" style="display: none;">
            <div class="phase-header">
              <h3><span class="sec-num">3</span> Appointment Details</h3>
              <p class="phase-subtitle">Step 3 of 3 • Schedule, Mode & Referral Source</p>
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

            <div class="form-section" id="secQuickSchedule" style="margin-bottom: 6px; padding: 8px 12px; border: 1.5px solid #e2e8f0; border-radius: 8px;">
              <label style="font-size: 0.72rem; font-weight: 700; color: #166534; text-transform: uppercase; margin-bottom: 4px; display: block;">Consultation Mode <span class="asterisk">*</span></label>
              <div class="quick-schedule-options" style="margin-bottom: 0; display: flex; gap: 14px;">
                <label><input type="radio" name="consultationMode" value="Face-to-Face" required checked> Face to Face</label>
                <label><input type="radio" name="consultationMode" value="Teleconsultation" required> Teleconsultation</label>
                <label><input type="radio" name="consultationMode" value="Admission" required> Admission</label>
              </div>
            </div>

            <div class="grid-row" style="grid-template-columns: 1fr 1fr 1fr;">
              <div class="form-group">
                <label>Type <span class="asterisk">*</span></label>
                <select id="appointmentType" required>
                  <option value="Online Booking" selected>Online Scheduled Appointment</option>
                  <option value="Referral">Referral</option>
                </select>
              </div>
              <div class="form-group">
                <label>Referral Source</label>
                <input type="text" id="referralSource" placeholder="e.g. OPU Med" maxlength="100" />
              </div>
              <div class="form-group">
                <label>Receiver</label>
                <input type="text" id="receiver" placeholder="Staff Name" maxlength="100" />
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
                <h1 class="print-clinic-name">OPU Psychiatry Registration</h1>
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
              <div class="print-section-title">Inquiry Details</div>
              <div class="print-row">
                <div class="print-field">
                  <span class="print-label">Date / Time</span>
                  <span class="print-value" id="p-appointmentDate"></span>
                </div>
                <div class="print-field">
                  <span class="print-label">Receiver</span>
                  <span class="print-value" id="p-receiver"></span>
                </div>
              </div>
              <div class="print-row">
                <div class="print-field">
                  <span class="print-label">Type</span>
                  <span class="print-value" id="p-appointmentType"></span>
                </div>
                <div class="print-field">
                  <span class="print-label">Referral Source</span>
                  <span class="print-value" id="p-referralSource"></span>
                </div>
              </div>
            </div>

            <div class="print-section">
              <div class="print-section-title">Patient Information</div>
              <div class="print-row">
                <div class="print-field full">
                  <span class="print-label">Name</span>
                  <span class="print-value" id="p-fullName"></span>
                </div>
              </div>
              <div class="print-row">
                <div class="print-field">
                  <span class="print-label">Middle Initial</span>
                  <span class="print-value" id="p-middleInitial"></span>
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
                  <span class="print-label">New / Old</span>
                  <span class="print-value" id="p-patientType"></span>
                </div>
                <div class="print-field">
                  <span class="print-label">Contact No</span>
                  <span class="print-value" id="p-contactNumber"></span>
                </div>
              </div>
              <div class="print-row">
                <div class="print-field full">
                  <span class="print-label">Informant</span>
                  <span class="print-value" id="p-informant"></span>
                </div>
              </div>
              <div class="print-row">
                <div class="print-field full">
                  <span class="print-label">Chief Complaint</span>
                  <span class="print-value" id="p-chiefComplaints"></span>
                </div>
              </div>
              <div class="print-row">
                <div class="print-field full">
                  <span class="print-label">Recommendation / Remarks</span>
                  <span class="print-value" id="p-recommendationRemarks"></span>
                </div>
              </div>
            </div>

            <div class="print-section">
              <div class="print-section-title">Schedule Options</div>
              <div class="print-row">
                <div class="print-field">
                  <span class="print-label">Consultation Mode</span>
                  <span class="print-value" id="p-consultationMode"></span>
                </div>
                <div class="print-field">
                  <span class="print-label">Walk-in</span>
                  <span class="print-value" id="p-unscheduledWalkin"></span>
                </div>
              </div>
              <div class="print-row">
                <div class="print-field full">
                  <span class="print-label">Inquiry Only</span>
                  <span class="print-value" id="p-inquiryOnly"></span>
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
              <button id="btnDownloadPDF" class="btn-export">Download PDF</button>
              <button id="btnPrint" class="btn-export">Print</button>
            </div>
          </div>

          <button id="btnClose" class="btn-confirm-close">
            Confirm
          </button>
        </div>
      </div>
    </div>

    <div id="successToast" class="success-toast">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="20 6 9 17 4 12"/></svg>
      <div>
        <strong>Registration Confirmed!</strong>
        <span>Your registration has been successfully submitted.</span>
      </div>
    </div>

    <!-- PDF EXPORT CONTAINER -->
    <div id="pdfContainer">
      <div class="print-watermark" aria-hidden="true">
        <img src="/sdn/public/assets/mmwgh_logo.png" alt="MMWGH Logo Watermark" />
      </div>
      <div id="pdf-header">
        <div class="pdf-header-logo">
          <img src="/sdn/public/assets/mmwgh_logo.png" alt="MMWGH Logo" class="print-header-logo-img" />
        </div>
        <div>
          <div class="pdf-header-title">OPU Psychiatry Registration</div>
          <div class="pdf-header-meta">Reference No: <strong id="pdf-refNumber"></strong></div>
          <div class="pdf-header-meta">Date Submitted: <strong id="pdf-submittedDate"></strong></div>
        </div>
      </div>
      <div class="pdf-body-padding">
         <!-- The modal-export.js will clone the print-body contents here when printing -->
      </div>
    </div>

    <script src="/sdn/public/js/utils.js?v=<?= time() ?>"></script>
    <script src="/sdn/public/js/navigation.js?v=<?= time() ?>"></script>
    <script src="/sdn/public/js/print-pdf-setup.js?v=<?= time() ?>"></script>
    <script src="/sdn/public/js/regis-stepper.js?v=<?= time() ?>"></script>
    <script src="/sdn/public/js/regis-psychiatry.js?v=<?= time() ?>"></script>
    <script src="/sdn/public/js/modal-export.js?v=<?= time() ?>"></script>
  </body>
</html>
