<?php session_start(); ?>
<!doctype html>
<html lang="en" class="regis-page">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>General Medical Registration | LINGKOD</title>
    <link rel="stylesheet" href="/sdn/public/css/global.css?v=9" />
    <link rel="stylesheet" href="/sdn/public/css/navigation.css?v=9" />
    <link rel="stylesheet" href="/sdn/public/css/forms.css?v=20261128" />
    <link rel="stylesheet" href="/sdn/public/css/modal-export.css?v=20261128" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <link rel="stylesheet" href="/sdn/public/css/regis-general.css?v=20261128" />
  </head>
  <body>
    <?php require_once __DIR__ . '/privacy-consent-modal.php'; ?>
    <?php
    $selectedService = trim($_GET['service'] ?? $_GET['spec'] ?? '');
    $selectedDoctor = trim($_GET['doctor'] ?? '');
    if (isset($_GET['source']) && $_GET['source'] === 'dept') {
        $backUrl = '/sdn/pages/register.php';
    } elseif (isset($_GET['source']) && $_GET['source'] === 'schedules') {
        $backUrl = '/sdn/pages/schedules.php';
    } else {
        $backUrl = '/sdn/pages/register.php';
    }

    $generalSpecialties = [
      'Family Medicine & General Practice',
      'Routine Check-ups & Preventative Care',
      'Internal Medicine',
      'Chronic Disease Management',
      'Pediatric Care',
      'General Surgery & Minor Procedures'
    ];
    $matchedSpecialty = '';
    if ($selectedService) {
      $sLower = strtolower($selectedService);
      foreach ($generalSpecialties as $gs) {
        if (strcasecmp($gs, $selectedService) === 0) {
          $matchedSpecialty = $gs;
          break;
        }
      }
      if (!$matchedSpecialty) {
        foreach ($generalSpecialties as $gs) {
          $gsLower = strtolower($gs);
          if (strpos($gsLower, $sLower) !== false || strpos($sLower, $gsLower) !== false ||
              (strpos($sLower, 'internal') !== false && strpos($gsLower, 'internal') !== false) ||
              (strpos($sLower, 'family') !== false && strpos($gsLower, 'family') !== false) ||
              (strpos($sLower, 'pediatric') !== false && strpos($gsLower, 'pediatric') !== false) ||
              (strpos($sLower, 'surgery') !== false && strpos($gsLower, 'surgery') !== false) ||
              (strpos($sLower, 'chronic') !== false && strpos($gsLower, 'chronic') !== false) ||
              (strpos($sLower, 'routine') !== false && strpos($gsLower, 'routine') !== false) ||
              (strpos($sLower, 'check-up') !== false && strpos($gsLower, 'check-up') !== false)) {
            $matchedSpecialty = $gs;
            break;
          }
        }
      }
      if (!$matchedSpecialty) {
        $matchedSpecialty = $selectedService;
      }
    }
    ?>
    <div class="registration-container">
      <div class="reg-header-bar">
        <div class="reg-header-left">
          <a href="<?= htmlspecialchars($backUrl) ?>" class="btn-back" title="Back">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
          </a>
          <div>
            <h2>General Medical Registration</h2>
            <p style="margin:2px 0 0 0; font-size:0.83rem; color:#64748b;">Schedule your appointment with General Healthcare & Routine Medical Services.</p>
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
        <input type="hidden" id="department" value="general" />
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

            <div class="form-group">
              <label>Contact No/s. <span class="asterisk">*</span></label>
              <input type="tel" id="contactNumber" placeholder="09XXXXXXXXX" pattern="^(09|\+639)\d{9}$" maxlength="13" title="Format: 09XXXXXXXXX or +639XXXXXXXXX" required />
            </div>
          </div>

          <!-- Phase 2: Medical Information -->
          <div class="form-phase" id="phase2" data-phase="2" style="display: none;">
            <div class="phase-header">
              <h3><span class="sec-num">2</span> Medical Information</h3>
              <p class="phase-subtitle">Step 2 of 3 • Chief Complaint & Illness Description</p>
            </div>

            <div class="form-group" id="groupComplaint">
              <label id="lblComplaint">Chief Complaint <span class="asterisk">*</span></label>
              <select id="complaint" required>
                <option value="" disabled selected>Select primary illness or chief complaint...</option>
                <option value="Routine Health Screening / Preventative Check-up">Routine Health Screening / Preventative Check-up</option>
                <option value="Hypertension / High Blood Pressure Follow-up">Hypertension / High Blood Pressure Follow-up</option>
                <option value="Diabetes Mellitus Management & Blood Sugar Monitoring">Diabetes Mellitus Management &amp; Blood Sugar Monitoring</option>
                <option value="Acute Fever, Cough, Colds & Flu Symptoms">Acute Fever, Cough, Colds &amp; Flu Symptoms</option>
                <option value="Upper Respiratory Tract Infection (URTI)">Upper Respiratory Tract Infection (URTI)</option>
                <option value="Gastrointestinal Distress / Abdominal Pain / Gastritis">Gastrointestinal Distress / Abdominal Pain / Gastritis</option>
                <option value="Pediatric Well-Baby / Childhood Immunization & Illness">Pediatric Well-Baby / Childhood Immunization &amp; Illness</option>
                <option value="Minor Injury, Wound Care & Suture Dressing">Minor Injury, Wound Care &amp; Suture Dressing</option>
                <option value="General Physical Examination / Medical Clearance">General Physical Examination / Medical Clearance</option>
                <option value="Chronic Pain / Musculoskeletal Discomfort">Chronic Pain / Musculoskeletal Discomfort</option>
                <option value="Other">Other General Medical Concern (specify below)</option>
              </select>
            </div>

            <div class="form-group">
              <label>Additional Observations / Remarks (Optional)</label>
              <textarea id="remarks" rows="3" placeholder="Additional observations, chief complaint, or inquiry details (optional)..." style="min-height: 60px; height: 68px;"></textarea>
            </div>
          </div>

          <!-- Phase 3: Appointment Details -->
          <div class="form-phase" id="phase3" data-phase="3" style="display: none;">
            <div class="phase-header">
              <h3><span class="sec-num">3</span> Appointment Details</h3>
              <p class="phase-subtitle">Step 3 of 3 • Inquiry, Specialty & Preferred Schedule</p>
            </div>

            <div class="grid-row">
              <div class="form-group" id="groupModeOfInquiry">
                <label>Mode of Inquiry <span class="asterisk">*</span></label>
                <select id="modeOfInquiry" required>
                  <option value="" disabled selected>Select mode...</option>
                  <option value="Direct">Direct</option>
                  <option value="Call">Call</option>
                  <option value="Referral">Referral</option>
                </select>
              </div>
              <div class="form-group" id="groupEmployeeType">
                <label>Employee Type <span class="asterisk" id="employeeTypeAsterisk">*</span></label>
                <select id="employeeType" required>
                  <option value="" disabled selected>Select employee type...</option>
                  <option value="Non-Employee">Non-Employee</option>
                  <option value="Employee">Employee</option>
                  <option value="Dependent">Dependent</option>
                </select>
              </div>
            </div>

            <div class="grid-row">
              <div class="form-group" id="groupSelectType">
                <label>Select Type <span class="asterisk">*</span></label>
                <select id="selectType" required>
                  <option value="Scheduled" selected>Scheduled Online Appointment</option>
                </select>
              </div>
              <div class="form-group">
                <label for="specialty">Specialty <?php if ($selectedService): ?><span class="badge-locked" title="Assigned from selected clinic service"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg> Locked</span><?php endif; ?></label>
                <select id="specialty" <?= $selectedService ? 'disabled class="locked-select"' : '' ?>>
                  <option value="" disabled <?= empty($matchedSpecialty) ? 'selected' : '' ?>>Select Specialty</option>
                  <?php
                    $foundMatch = false;
                    foreach ($generalSpecialties as $gs) {
                      $isSel = ($matchedSpecialty && strcasecmp($matchedSpecialty, $gs) === 0);
                      if ($isSel) $foundMatch = true;
                      echo '<option value="' . htmlspecialchars($gs) . '"' . ($isSel ? ' selected' : '') . '>' . htmlspecialchars($gs) . '</option>';
                    }
                    if ($matchedSpecialty && !$foundMatch) {
                      echo '<option value="' . htmlspecialchars($matchedSpecialty) . '" selected>' . htmlspecialchars($matchedSpecialty) . '</option>';
                    }
                  ?>
                </select>
                <input type="hidden" id="lockedSpecialtyInput" name="locked_specialty" value="<?= htmlspecialchars($matchedSpecialty ?: $selectedService) ?>" />
              </div>
            </div>

            <div class="grid-row">
              <div class="form-group">
                <label>Scheduled Date <span class="asterisk">*</span></label>
                <input type="date" id="scheduledDate" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" max="<?= date('Y-m-d', strtotime('+1 year')) ?>" required />
              </div>
              <div class="form-group">
                <label>Scheduled Time <span class="asterisk">*</span></label>
                <input type="time" id="scheduledTime" required />
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
                <h1 class="print-clinic-name">General Medical Registration</h1>
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
                  <span class="print-label">Inquiry Only</span>
                  <span class="print-value" id="p-inquiryOnly"></span>
                </div>
                <div class="print-field">
                  <span class="print-label">Mode of Inquiry</span>
                  <span class="print-value" id="p-modeOfInquiry"></span>
                </div>
              </div>
              <div class="print-row">
                <div class="print-field">
                  <span class="print-label">Employee Type</span>
                  <span class="print-value" id="p-employeeType"></span>
                </div>
                <div class="print-field">
                  <span class="print-label">Select Type</span>
                  <span class="print-value" id="p-selectType"></span>
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
                <div class="print-field full">
                  <span class="print-label">Gender</span>
                  <span class="print-value" id="p-gender"></span>
                </div>
              </div>
              <div class="print-row">
                <div class="print-field full">
                  <span class="print-label">Contact No/s.</span>
                  <span class="print-value" id="p-contactNumber"></span>
                </div>
              </div>
              <div class="print-row">
                <div class="print-field full">
                  <span class="print-label">Chief Complaint</span>
                  <span class="print-value" id="p-complaint"></span>
                </div>
              </div>
              <div class="print-row">
                <div class="print-field full">
                  <span class="print-label">Remarks</span>
                  <span class="print-value" id="p-remarks"></span>
                </div>
              </div>
            </div>

            <div class="print-section">
              <div class="print-section-title">Schedule Details</div>
              <div class="print-row">
                <div class="print-field full">
                  <span class="print-label">Specialty</span>
                  <span class="print-value" id="p-specialty"></span>
                </div>
              </div>
              <div class="print-row">
                <div class="print-field">
                  <span class="print-label">Scheduled Date</span>
                  <span class="print-value" id="p-scheduledDate"></span>
                </div>
                <div class="print-field">
                  <span class="print-label">Scheduled Time</span>
                  <span class="print-value" id="p-scheduledTime"></span>
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
          <div class="pdf-header-title">General Medical Registration</div>
          <div class="pdf-header-meta">Reference No: <strong id="pdf-refNumber"></strong></div>
          <div class="pdf-header-meta">Date Submitted: <strong id="pdf-submittedDate"></strong></div>
        </div>
      </div>
      <div class="pdf-body-padding">
         <!-- The modal-export.js will clone the print-body contents here when printing -->
      </div>
    </div>

    <script src="/sdn/public/js/utils.js?v=20261128"></script>
    <script src="/sdn/public/js/navigation.js?v=20261128"></script>
    <script src="/sdn/public/js/print-pdf-setup.js?v=20261128"></script>
    <script src="/sdn/public/js/regis-stepper.js?v=20261128"></script>
    <script src="/sdn/public/js/regis-general.js?v=20261128"></script>
    <script src="/sdn/public/js/modal-export.js?v=20261128"></script>
  </body>
</html>
