<?php
/**
 * pages/patient-book-appointment.php
 * 4-Step Patient Appointment Booking Flow
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../api/config/db.php';

$isLoggedIn       = !empty($_SESSION['patient_logged_in']) && (!empty($_SESSION['patient_id']) || !empty($_SESSION['patient_email']));
$sessionPatientId = $_SESSION['patient_id'] ?? '';
$sessionEmail     = $_SESSION['patient_email'] ?? '';

$patient = [];
if ($isLoggedIn && isset($pdo)) {
    try {
        $sessionUserId = $_SESSION['user_id'] ?? $_SESSION['patient_user_id'] ?? null;
        if ($sessionUserId) {
            $uOwnerStmt = $pdo->prepare("SELECT patient_id FROM users WHERE id = ? LIMIT 1");
            $uOwnerStmt->execute([$sessionUserId]);
            $ownerRowId = $uOwnerStmt->fetchColumn();
            if ($ownerRowId && is_numeric($ownerRowId)) {
                $stmtOwner = $pdo->prepare("
                    SELECT id, patient_id, full_name, first_name, last_name, middle_initial,
                           gender, birthdate, age, contact_number, email, address, with_companion, hrn
                    FROM patients
                    WHERE id = ?
                    LIMIT 1
                ");
                $stmtOwner->execute([(int)$ownerRowId]);
                $patient = $stmtOwner->fetch(PDO::FETCH_ASSOC) ?: [];
            }
        }
        if (empty($patient)) {
            $stmt = $pdo->prepare("
                SELECT id, patient_id, full_name, first_name, last_name, middle_initial,
                       gender, birthdate, age, contact_number, email, address, with_companion, hrn
                FROM patients
                WHERE ((patient_id != '' AND patient_id = :pid) OR (email != '' AND email = :pemail))
                  AND COALESCE(appointment_recipient, 'Patient (Me)') NOT IN ('Other Patient', 'Someone Else / Dependent')
                ORDER BY (password IS NOT NULL AND password <> '') DESC, id ASC
                LIMIT 1
            ");
            $stmt->execute([
                ':pid'    => $sessionPatientId,
                ':pemail' => $sessionEmail
            ]);
            $patient = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        }
    } catch (Throwable $e) {
        $patient = [];
    }
}

$firstName     = $patient['first_name'] ?? '';
$lastName      = $patient['last_name'] ?? '';
$middleInitial = $patient['middle_initial'] ?? '';
if ($firstName === '' && $lastName === '' && !empty($patient['full_name'])) {
    $parts = preg_split('/\s+/', trim($patient['full_name'])) ?: [];
    if (count($parts) === 1) {
        $firstName = $parts[0];
    } elseif (count($parts) === 2) {
        $firstName = $parts[0];
        $lastName  = $parts[1];
    } elseif (count($parts) >= 3) {
        $firstName     = $parts[0];
        $lastName      = end($parts);
        $middleInitial = $parts[1];
    }
}
$gender        = $patient['gender'] ?? '';
$birthdate     = $patient['birthdate'] ?? '';
$age           = $patient['age'] ?? '';
$rawContact    = $patient['contact_number'] ?? '';
$contactNumber = preg_replace('/^(\+63|63|0)/', '', preg_replace('/\D/', '', $rawContact));
if (strlen($contactNumber) > 10) {
    $contactNumber = substr($contactNumber, -10);
}
$email         = $patient['email'] ?? ($isLoggedIn ? $sessionEmail : '');
$address       = $patient['address'] ?? '';
$withCompanion = isset($patient['with_companion']) ? (int)$patient['with_companion'] : 1;

$selectedDept = $_GET['dept'] ?? '';
if ($selectedDept === 'General Management' || strtolower($selectedDept) === 'general_medicine') {
    $selectedDept = 'General Medical';
} elseif (strtolower($selectedDept) === 'neurology') {
    $selectedDept = 'Neurology';
} elseif (strtolower($selectedDept) === 'psychiatry' || $selectedDept === 'OPU Psychiatry') {
    $selectedDept = 'OPU Psychiatry';
}

$tomorrow       = date('Y-m-d', strtotime('+1 day'));
$maxAdvanceDate = date('Y-m-d', strtotime('+1 year'));

$isFirstTimePatient = true;
if ($isLoggedIn && isset($pdo)) {
    try {
        $stmtPrior = $pdo->prepare("
            SELECT COUNT(*)
            FROM patients
            WHERE COALESCE(inquiry_only, 0) = 0
              AND appointment_date IS NOT NULL
              AND LOWER(COALESCE(status, '')) NOT IN ('cancelled', 'rejected', 'cancelled_by_patient', 'no-show')
              AND COALESCE(appointment_recipient, 'Patient (Me)') NOT IN ('Other Patient', 'Someone Else / Dependent')
              AND ((? != '' AND patient_id = ?) OR (? != '' AND LOWER(TRIM(email)) = LOWER(?)))
        ");
        $stmtPrior->execute([
            $sessionPatientId,
            $sessionPatientId,
            strtolower(trim($sessionEmail ?: $email)),
            strtolower(trim($sessionEmail ?: $email)),
        ]);
        $isFirstTimePatient = ((int)$stmtPrior->fetchColumn() === 0);
    } catch (Throwable $e) {
        $isFirstTimePatient = true;
    }
}

$hrn = trim((string)($patient['hrn'] ?? ''));
$hasCompleteIdentity = $isLoggedIn
    && $firstName !== ''
    && $lastName !== ''
    && $gender !== ''
    && $birthdate !== ''
    && $email !== '';

$displayFullName = trim($firstName . ($middleInitial !== '' ? ' ' . rtrim($middleInitial, '.') . '.' : '') . ' ' . $lastName);
$displayBirthdate = ($birthdate !== '' && strtotime($birthdate) !== false)
    ? date('M j, Y', strtotime($birthdate))
    : $birthdate;

$profileBootstrap = [
    'isLoggedIn'          => $isLoggedIn,
    'hasCompleteIdentity' => $hasCompleteIdentity,
    'isFirstTime'         => $isFirstTimePatient,
    'firstName'           => $firstName,
    'lastName'            => $lastName,
    'middleInitial'       => $middleInitial,
    'gender'              => $gender,
    'birthdate'           => $birthdate,
    'age'                 => (string)$age,
    'contactNumber'       => $contactNumber,
    'email'               => $email,
    'address'             => $address,
    'hrn'                 => $hrn,
    'withCompanion'       => $withCompanion === 1 ? 'yes' : 'no',
];
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Book an Appointment | MMWGH LINGKOD</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="/sdn/assets/css/book-appointment-v2.css?v=20261128" />
  </head>
  <body class="privacy-locked">
    <!-- Data Privacy Act (R.A. 10173) Modal Overlay -->
    <div id="privacyOverlay" class="privacy-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="privacyModalTitle">
      <div class="privacy-modal-card">
        <div class="privacy-modal-header">
          <div class="privacy-header-left">
            <div class="privacy-icon-badge" aria-hidden="true">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                <polyline points="9 12 11 14 15 10"></polyline>
              </svg>
            </div>
            <div>
              <h2 id="privacyModalTitle" class="privacy-title">Data Privacy Consent Notice</h2>
              <p class="privacy-subtitle">Republic Act No. 10173 &middot; Data Privacy Act of 2012</p>
            </div>
          </div>
          <span id="privacyScrollPill" class="privacy-status-pill pending">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
            <span>Scroll to read</span>
          </span>
        </div>

        <div class="privacy-progress-track" aria-hidden="true">
          <div id="privacyReadProgress" class="privacy-progress-fill"></div>
        </div>

        <div id="modalPrivacyScrollBox" class="privacy-scroll-content custom-scrollbar">
          <div class="privacy-doc-intro">
            <div class="privacy-section-lead">Mariveles Mental Wellness and General Hospital (MMWGH)</div>
            <div class="privacy-section-sublead">LINGKOD Patient Appointment &amp; Triage Service</div>
            <p class="privacy-intro-copy">
              In strict compliance with Republic Act No. 10173 (<em>Data Privacy Act of 2012</em>) and its Implementing Rules and Regulations, MMWGH is committed to upholding your privacy rights, clinical confidentiality, and the security of your personal health data.
            </p>
          </div>

          <div class="privacy-clauses">
            <div class="privacy-clause">
              <span class="privacy-clause-num" aria-hidden="true">01</span>
              <div class="privacy-clause-body">
                <p class="privacy-section-heading">Collection and Scope of Personal Information</p>
                <p>By proceeding with this consultation booking form, you authorize MMWGH to process personal details (full name, contact number, birthdate, age, home address, and companion information if applicable) and sensitive clinical data (chief complaints and triage routing).</p>
              </div>
            </div>

            <div class="privacy-clause">
              <span class="privacy-clause-num" aria-hidden="true">02</span>
              <div class="privacy-clause-body">
                <p class="privacy-section-heading">Medical Confidentiality &amp; Purpose of Processing</p>
                <p>Your information is used solely for clinical schedule confirmation, physician triage assignment, and SMS/Email appointment reminders. Medical diagnoses and complaints are never published in unencrypted communications or shared with unauthorized parties.</p>
              </div>
            </div>

            <div class="privacy-clause">
              <span class="privacy-clause-num" aria-hidden="true">03</span>
              <div class="privacy-clause-body">
                <p class="privacy-section-heading">Minor Consultation Guidelines</p>
                <p>For patients under eighteen (18) years of age, a designated parent or legal guardian must be verified and must accompany the minor patient to the hospital Outpatient Triage desk during the clinic schedule.</p>
              </div>
            </div>

            <div class="privacy-clause">
              <span class="privacy-clause-num" aria-hidden="true">04</span>
              <div class="privacy-clause-body">
                <p class="privacy-section-heading">Data Subject Rights</p>
                <p>Under R.A. 10173, you retain the right to be informed, review your clinical records, dispute inaccuracies, and request corrections in accordance with policies enforced by the MMWGH Data Protection Officer.</p>
              </div>
            </div>
          </div>
        </div>

        <div class="privacy-modal-footer">
          <div class="privacy-consent-row">
            <label class="privacy-checkbox-label" id="modalPrivacyLabel">
              <input type="checkbox" id="modalPrivacyConsentCheck" disabled />
              <span>I have read and agree to the <strong>Data Privacy Policy</strong> and consent to the processing of my information.</span>
            </label>
            <p id="privacyHintText" class="privacy-hint-text">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
              <span>Please scroll to the bottom of the notice to enable consent.</span>
            </p>
          </div>

          <div class="privacy-modal-actions">
            <a href="/sdn/pages/patient-dashboard.php" class="btn-cancel-privacy">Back to Portal</a>
            <button type="button" id="btnProceedBooking" class="btn-proceed-privacy" disabled>
              <span>Agree &amp; Proceed</span>
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Top Green Gradient Navbar -->
    <header class="book-navbar">
      <a href="/sdn/index.php" class="nav-left-brand" title="Back to Home">
        <img src="/sdn/public/assets/v3_lingkod_logo.png" alt="Lingkod Logo" class="nav-logo-img" />
        <div class="nav-brand-text">
          <span class="brand-title-top">Mariveles Mental Wellness and General Hospital</span>
        </div>
      </a>

      <a href="/sdn/index.php#footer-new" class="nav-contact-link" aria-label="Contact Us">
        <svg class="nav-contact-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
        </svg>
        <span>Contact Us</span>
      </a>
    </header>

    <!-- Main Two-Column Workspace -->
    <div class="ba-workspace">
      <!-- Left Vertical Stepper Sidebar (Desktop) / Compact Percentage Progress (Mobile) -->
      <aside class="ba-sidebar" aria-label="Booking Progress">
        <div class="ba-mobile-progress" id="baMobileProgress" role="progressbar" aria-label="Booking progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="25">
          <div class="ba-mobile-progress-head">
            <span class="ba-mobile-progress-step" id="baMobileProgressLabel">Step 1 of 4 &middot; Personal Information</span>
            <span class="ba-mobile-progress-pct" id="baMobileProgressPct">25%</span>
          </div>
          <div class="ba-mobile-progress-track">
            <div class="ba-mobile-progress-fill" id="baMobileProgressFill" style="width: 25%;"></div>
          </div>
        </div>
        <nav class="ba-stepper-list">
          <div class="ba-step-item is-active" id="stepIndicator1" data-step="1" role="button" tabindex="0" aria-current="step">
            <div class="ba-step-circle">1</div>
            <span class="ba-step-name">Personal Information</span>
          </div>

          <div class="ba-step-item" id="stepIndicator2" data-step="2" role="button" tabindex="0">
            <div class="ba-step-circle">2</div>
            <span class="ba-step-name" id="step2SidebarLabel">Medical Information</span>
          </div>

          <div class="ba-step-item" id="stepIndicator3" data-step="3" role="button" tabindex="0">
            <div class="ba-step-circle">3</div>
            <span class="ba-step-name" id="step3SidebarLabel">Appointment Details</span>
          </div>

          <div class="ba-step-item" id="stepIndicator4" data-step="4" role="button" tabindex="0">
            <div class="ba-step-circle">4</div>
            <span class="ba-step-name">Confirmation</span>
          </div>
        </nav>
      </aside>

      <!-- Right Form Content Panel -->
      <main class="ba-content">
        <div class="ba-content-inner">
          <div id="bookingAlert" class="ba-alert" role="alert" aria-live="polite">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="flex-shrink:0;margin-top:2px;">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="8" x2="12" y2="12"></line>
              <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <span id="bookingAlertText"></span>
          </div>

          <div id="bookingFormWrapper">
            <!-- ── STEP 1: PERSONAL INFORMATION ── -->
            <section class="phase-step active" id="phaseStep1" aria-labelledby="step1Heading">
              <h1 id="step1Heading" class="ba-step-title">PERSONAL INFORMATION</h1>
              <p class="ba-step-subtitle">Please provide your basic details to start the registration.</p>

              <div class="ba-verification-note" role="note">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                </svg>
                <span id="signedInBannerText">
                  <?php if ($isLoggedIn): ?>
                    <strong>Signed-in booking:</strong> Your saved profile is pre-filled. Clinic staff will call your active mobile number to confirm before placing you in the queue.
                  <?php else: ?>
                    <strong>Guest or account booking:</strong> No login is required to submit. Clinic staff will call your active mobile number to confirm before placing you in the queue.
                  <?php endif; ?>
                </span>
              </div>

              <!-- Inquiry Only Box -->
              <label class="ba-inquiry-box" id="inquiryBadgeBtn" for="inquiryOnlyCheckbox">
                <input type="checkbox" id="inquiryOnlyCheckbox" name="inquiry_only" />
                <span class="ba-inquiry-copy">
                  <span class="ba-inquiry-title">Inquiry only</span>
                  <span class="ba-inquiry-desc">Check this box if you are only making an inquiry and not booking a consultation.</span>
                </span>
              </label>

              <!-- Appointment Recipient -->
              <div>
                <p class="ba-section-label" id="recipientSectionLabel">APPOINTMENT RECIPIENT <span class="req-mark">*</span></p>
                <div class="ba-recipient-grid" role="radiogroup" aria-label="Appointment Recipient">
                  <label id="pillBookingMyself" class="ba-recipient-card is-selected">
                    <input type="radio" name="booking_for" value="Patient (Me)" checked />
                    <span class="ba-recipient-icon" aria-hidden="true">
                      <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                      </svg>
                    </span>
                    <span class="ba-recipient-text">
                      <span class="ba-recipient-title">Patient (Me)</span>
                      <span class="ba-recipient-sub" id="recipientMyselfSub">I am booking for myself</span>
                    </span>
                  </label>

                  <label id="pillBookingDependent" class="ba-recipient-card">
                    <input type="radio" name="booking_for" value="Other Patient" />
                    <span class="ba-recipient-icon" aria-hidden="true">
                      <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                      </svg>
                    </span>
                    <span class="ba-recipient-text">
                      <span class="ba-recipient-title">Other Patient</span>
                      <span class="ba-recipient-sub" id="recipientOtherSub">Booking for someone else</span>
                    </span>
                  </label>
                </div>
              </div>

              <?php if ($hasCompleteIdentity): ?>
              <!-- Compact Verified Profile Summary Card (Signed-in Patient (Me)) -->
              <section id="signedInIdentityCard" class="ba-identity-card" aria-label="Verified Patient Profile">
                <div class="ba-identity-card-head">
                  <div class="ba-identity-title-wrap">
                    <span class="ba-identity-badge">
                      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                        <polyline points="9 12 11 14 15 10"></polyline>
                      </svg>
                      Verified Profile
                    </span>
                    <?php if ($hrn !== ''): ?>
                    <span class="ba-identity-hrn"><?= htmlspecialchars($hrn) ?></span>
                    <?php endif; ?>
                  </div>
                  <a href="/sdn/index.php#footer-new" class="ba-identity-help-link">Need a correction?</a>
                </div>

                <div class="ba-identity-grid">
                  <div class="ba-identity-item">
                    <span class="ba-identity-lbl">Full Name</span>
                    <span class="ba-identity-val"><?= htmlspecialchars($displayFullName) ?></span>
                  </div>
                  <div class="ba-identity-item">
                    <span class="ba-identity-lbl">Gender</span>
                    <span class="ba-identity-val"><?= htmlspecialchars($gender) ?></span>
                  </div>
                  <div class="ba-identity-item">
                    <span class="ba-identity-lbl">Birthdate &amp; Age</span>
                    <span class="ba-identity-val"><?= htmlspecialchars($displayBirthdate) ?><?= $age !== '' ? ' &middot; ' . htmlspecialchars((string)$age) . ' yrs' : '' ?></span>
                  </div>
                  <div class="ba-identity-item">
                    <span class="ba-identity-lbl">Email Address</span>
                    <span class="ba-identity-val ba-identity-email"><?= htmlspecialchars($email) ?></span>
                  </div>
                </div>
              </section>
              <?php endif; ?>

              <div id="baIdentityInputsWrap" <?= $hasCompleteIdentity ? 'hidden' : '' ?>>
                <!-- Row 1: First Name, Last Name, M.I. -->
                <div class="ba-row-3">
                  <div class="ba-field">
                    <label for="inputFirstName" class="ba-label">First Name <span class="req-mark">*</span></label>
                    <input type="text" id="inputFirstName" class="ba-input" placeholder="Enter First Name" value="<?= htmlspecialchars($firstName) ?>" required />
                    <div class="ba-error-msg" id="errFirstName" role="alert"><span class="ba-error-text"></span></div>
                  </div>

                  <div class="ba-field">
                    <label for="inputLastName" class="ba-label">Last Name <span class="req-mark">*</span></label>
                    <input type="text" id="inputLastName" class="ba-input" placeholder="Enter Last Name" value="<?= htmlspecialchars($lastName) ?>" required />
                    <div class="ba-error-msg" id="errLastName" role="alert"><span class="ba-error-text"></span></div>
                  </div>

                  <div class="ba-field">
                    <label for="inputMiddleInitial" class="ba-label">M.I. (Optional)</label>
                    <input type="text" id="inputMiddleInitial" class="ba-input" placeholder="e.g., B." maxlength="5" value="<?= htmlspecialchars($middleInitial) ?>" />
                  </div>
                </div>

                <!-- Row 2: Gender, Birthdate, Age -->
                <div class="ba-row-3">
                  <div class="ba-field">
                    <label for="selectGender" class="ba-label">Gender <span class="req-mark">*</span></label>
                    <div class="ba-select-wrap">
                      <select id="selectGender" class="ba-select <?= empty($gender) ? 'is-placeholder' : '' ?>" required>
                        <option value="" disabled <?= empty($gender) ? 'selected' : '' ?>>Select Gender</option>
                        <option value="Male" <?= $gender === 'Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= $gender === 'Female' ? 'selected' : '' ?>>Female</option>
                        <option value="Other" <?= $gender === 'Other' ? 'selected' : '' ?>>Other</option>
                      </select>
                      <svg class="chevron-arrow" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="6 9 12 15 18 9"></polyline>
                      </svg>
                    </div>
                    <div class="ba-error-msg" id="errGender" role="alert"><span class="ba-error-text"></span></div>
                  </div>

                  <div class="ba-field">
                    <label for="inputBirthdate" class="ba-label">Birthdate <span class="req-mark">*</span></label>
                    <input type="date" id="inputBirthdate" class="ba-input" value="<?= htmlspecialchars($birthdate) ?>" max="<?= date('Y-m-d') ?>" required />
                    <div class="ba-error-msg" id="errBirthdate" role="alert"><span class="ba-error-text"></span></div>
                  </div>

                  <div class="ba-field">
                    <label for="inputAge" class="ba-label">Age</label>
                    <input type="number" id="inputAge" class="ba-input" value="<?= htmlspecialchars((string)$age) ?>" readonly />
                  </div>
                </div>
              </div>

              <!-- Row 3: Contact Number & Email Address -->
              <div class="ba-row-contact <?= $hasCompleteIdentity ? 'is-contact-only' : '' ?>" id="baContactRow">
                <div class="ba-field">
                  <label for="inputContactNumber" class="ba-label">Active Mobile Number <span class="req-mark">*</span></label>
                  <div class="ba-phone-split">
                    <span class="ba-phone-code">+63</span>
                    <input type="text" id="inputContactNumber" class="ba-input" placeholder="9XXXXXXXXX" maxlength="10" inputmode="numeric" value="<?= htmlspecialchars($contactNumber) ?>" required />
                  </div>
                  <div class="ba-error-msg" id="errContact" role="alert"><span class="ba-error-text"></span></div>
                </div>

                <div class="ba-field" id="baEmailFieldWrap" <?= $hasCompleteIdentity ? 'hidden' : '' ?>>
                  <label for="inputEmail" class="ba-label">Email Address <span class="req-mark">*</span></label>
                  <input type="email" id="inputEmail" class="ba-input" placeholder="Enter Email Address" value="<?= htmlspecialchars($email) ?>" required />
                  <div class="ba-error-msg" id="errEmail" role="alert"><span class="ba-error-text"></span></div>
                </div>
              </div>

              <!-- Row 4: Home Address -->
              <div class="ba-field">
                <label for="inputAddress" class="ba-label">Home Address <span class="req-mark">*</span></label>
                <textarea id="inputAddress" class="ba-textarea" placeholder="Enter home address" required><?= htmlspecialchars($address) ?></textarea>
                <div class="ba-error-msg" id="errAddress" role="alert"><span class="ba-error-text"></span></div>
              </div>

              <!-- Row 5: Accompanying Companion -->
              <div class="ba-companion-block">
                <span class="ba-label">Accompanying Companion? <span class="req-mark">*</span></span>
                <div class="ba-inline-radios">
                  <label class="ba-radio-inline">
                    <input type="radio" name="with_companion" id="radioCompanionYes" value="yes" <?= $withCompanion === 1 ? 'checked' : '' ?> />
                    <span>Yes</span>
                  </label>
                  <label class="ba-radio-inline">
                    <input type="radio" name="with_companion" id="radioCompanionNo" value="no" <?= $withCompanion === 0 ? 'checked' : '' ?> />
                    <span>No</span>
                  </label>
                </div>
              </div>

              <!-- Conditional Relationship to Patient (when Other Patient is selected) -->
              <div id="boxRelationshipToPatient" class="ba-relationship-panel">
                <div class="ba-field">
                  <label for="selectRelationshipToPatient" class="ba-label">Relationship to Patient <span class="ba-optional-muted">(Optional)</span></label>
                  <div class="ba-select-wrap">
                    <select id="selectRelationshipToPatient" class="ba-select is-placeholder">
                      <option value="" selected>Select relationship</option>
                      <option value="Parent">Parent</option>
                      <option value="Child">Child</option>
                      <option value="Spouse">Spouse</option>
                      <option value="Sibling">Sibling</option>
                      <option value="Guardian">Guardian</option>
                      <option value="Other">Other</option>
                    </select>
                    <svg class="chevron-arrow" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                      <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                  </div>
                </div>
              </div>

              <!-- Row 6: Department -->
              <div class="ba-field ba-dept-field">
                <label for="departmentSelect" class="ba-label">Department <span class="req-mark">*</span></label>
                <div class="ba-select-wrap">
                  <select id="departmentSelect" name="department" class="ba-select <?= empty($selectedDept) ? 'is-placeholder' : '' ?>" required>
                    <option value="" disabled <?= empty($selectedDept) ? 'selected' : '' ?>>Select an option</option>
                    <option value="General Medical" <?= $selectedDept === 'General Medical' ? 'selected' : '' ?>>General Medical</option>
                    <option value="Neurology" <?= $selectedDept === 'Neurology' ? 'selected' : '' ?>>Neurology</option>
                    <option value="OPU Psychiatry" <?= $selectedDept === 'OPU Psychiatry' ? 'selected' : '' ?>>OPU Psychiatry</option>
                  </select>
                  <svg class="chevron-arrow" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polyline points="6 9 12 15 18 9"></polyline>
                  </svg>
                </div>
                <div class="ba-error-msg" id="errDepartment" role="alert"><span class="ba-error-text"></span></div>
              </div>
            </section>

            <!-- ── STEP 2: MEDICAL INFORMATION ── -->
            <section class="phase-step" id="phaseStep2" aria-labelledby="step2Heading">
              <h1 id="step2Heading" class="ba-step-title">MEDICAL INFORMATION</h1>
              <p class="ba-step-subtitle" id="step2Subtitle">Help our assessment team identify the services you need.</p>

              <div style="margin-top: 28px;">
                <div class="ba-field">
                  <label for="inputChiefComplaints" class="ba-label-upper" id="lblChiefComplaints">
                    CHIEF COMPLAINT / REASON FOR CONSULTATION <span class="req-mark">*</span>
                  </label>
                  <textarea id="inputChiefComplaints" class="ba-textarea ba-textarea-lg" maxlength="1000" placeholder="Describe your symptoms or reason for visit..." required></textarea>
                  <div class="ba-error-msg" id="errComplaint" role="alert"><span class="ba-error-text"></span></div>
                </div>

                <div class="ba-row-2-med">
                  <div class="ba-field">
                    <label for="inputExistingConditions" class="ba-label-upper">
                      EXISTING MEDICAL CONDITIONS <span class="ba-optional-muted">(OPTIONAL)</span>
                    </label>
                    <textarea id="inputExistingConditions" class="ba-textarea ba-textarea-med"></textarea>
                  </div>

                  <div class="ba-field">
                    <label for="inputCurrentMedications" class="ba-label-upper">
                      CURRENT MEDICATIONS <span class="ba-optional-muted">(OPTIONAL)</span>
                    </label>
                    <textarea id="inputCurrentMedications" class="ba-textarea ba-textarea-med"></textarea>
                  </div>
                </div>

                <div class="ba-field ba-referral-field">
                  <label for="inputReferralSource" class="ba-label-upper">REFERRAL SOURCE</label>
                  <input type="text" id="inputReferralSource" class="ba-input" placeholder="(OPTIONAL)" maxlength="150" />
                </div>
              </div>
            </section>

            <!-- ── STEP 3: APPOINTMENT DETAILS ── -->
            <section class="phase-step" id="phaseStep3" aria-labelledby="step3Heading">
              <h1 id="step3Heading" class="ba-step-title">APPOINTMENT DETAILS</h1>
              <p class="ba-step-subtitle">Select your consultation mode, preferred date and time for the consultation.</p>

              <div id="inquiryStep3Notice" class="ba-inquiry-notice">
                <strong>Inquiry Only Mode:</strong> Date and time slot selection are optional. Hospital staff will review your inquiry and contact you.
              </div>

              <div style="margin-top: 28px;">
                <!-- Consultation Mode -->
                <div class="ba-field">
                  <span class="ba-label">Consultation Mode <span class="req-mark">*</span></span>
                  <div class="ba-mode-row" role="radiogroup" aria-label="Consultation Mode">
                    <label id="cardModeF2F" class="ba-mode-pill">
                      <input type="radio" id="radioModeF2F" name="consultation_mode" value="Face-to-Face" />
                      <span>Face-to-Face</span>
                      <span class="ba-mode-circle" aria-hidden="true"></span>
                    </label>

                    <label id="cardModeTele" class="ba-mode-pill">
                      <input type="radio" id="radioModeTele" name="consultation_mode" value="Teleconsultation" />
                      <span>Teleconsultation <span id="teleModeLockTag" class="ba-mode-lock-tag" style="display: none;">(Returning Only)</span></span>
                      <span class="ba-mode-circle" aria-hidden="true"></span>
                    </label>
                  </div>
                  <p id="firstTimeModeNotice" class="ba-mode-policy-notice" style="display: none;">
                    * Hospital Policy: First-time patients are required to have an initial Face-to-Face consultation. Teleconsultation is only available for returning patients with a completed prior visit.
                  </p>
                  <p id="teleconsultModeInfo" class="ba-mode-policy-notice" style="display: none; color: #1e40af; background: #eff6ff; border-color: #bfdbfe;">
                    Once approved by clinic staff, your Teleconsultation meeting link or call instructions will appear inside your Patient Portal.
                  </p>
                  <div class="ba-error-msg" id="errConsultationMode" role="alert"><span class="ba-error-text"></span></div>
                </div>

                <div id="step3ScheduleSection">
                  <!-- Preferred Date -->
                  <div class="ba-field ba-date-field-wrap">
                    <label for="inputAppointmentDate" class="ba-label-upper">
                      PREFERRED DATE <span class="req-mark">*</span>
                    </label>
                    <input type="date" id="inputAppointmentDate" class="ba-input" min="<?= $tomorrow ?>" max="<?= $maxAdvanceDate ?>" required />
                    <div class="ba-error-msg" id="errAppDate" role="alert"><span class="ba-error-text"></span></div>
                  </div>

                  <!-- Preferred Time Slot -->
                  <div class="ba-field">
                    <span class="ba-label-upper">
                      PREFERRED TIME SLOT <span class="req-mark">*</span>
                    </span>
                    <div class="ba-slots-grid" role="radiogroup" aria-label="Preferred Time Slot">
                      <label id="slotMorningCard" class="ba-slot-card">
                        <input type="radio" name="preferred_time_slot" value="Morning (8:00 AM - 12:00 PM)" />
                        <span class="ba-slot-icon" aria-hidden="true">
                          <svg width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2v2"></path>
                            <path d="m4.93 4.93 1.41 1.41"></path>
                            <path d="M20 12h2"></path>
                            <path d="m19.07 4.93-1.41 1.41"></path>
                            <path d="M15.947 12.65a4 4 0 0 0-5.925-4.128"></path>
                            <path d="M13 22H7a5 5 0 1 1 4.9-6H13a3 3 0 0 1 0 6Z"></path>
                          </svg>
                        </span>
                        <span class="ba-slot-copy">
                          <span class="ba-slot-title">MORNING</span>
                          <span class="ba-slot-time">8:00 AM - 12:00 PM</span>
                        </span>
                      </label>

                      <label id="slotAfternoonCard" class="ba-slot-card">
                        <input type="radio" name="preferred_time_slot" value="Afternoon (1:00 PM - 5:00 PM)" />
                        <span class="ba-slot-icon" aria-hidden="true">
                          <svg width="44" height="38" viewBox="0 0 64 52" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="30" y1="4.5" x2="30" y2="11" />
                            <line x1="10" y1="26.5" x2="16" y2="26.5" />
                            <line x1="44" y1="26.5" x2="50" y2="26.5" />
                            <line x1="20.5" y1="36.5" x2="16" y2="41" />
                            <line x1="39.5" y1="36.5" x2="44" y2="41" />
                            <line x1="30" y1="40" x2="30" y2="46.5" />
                            <path d="M 33.8 17.5 A 4.2 4.2 0 0 1 37.2 11.8 A 6.2 6.2 0 0 1 48.5 11.2 A 4.9 4.9 0 0 1 49 21 L 36.2 21" fill="#ffffff" />
                            <line x1="44" y1="11.5" x2="40" y2="15.5" />
                            <circle cx="30" cy="25" r="10" fill="#ffffff" />
                            <path d="M 11 23 A 5.5 5.5 0 0 1 11.2 12.2 A 7.2 7.2 0 0 1 24.5 11.5 A 5.8 5.8 0 0 1 24.5 23 Z" fill="#ffffff" />
                          </svg>
                        </span>
                        <span class="ba-slot-copy">
                          <span class="ba-slot-title">AFTERNOON</span>
                          <span class="ba-slot-time">1:00 PM - 5:00 PM</span>
                        </span>
                      </label>
                    </div>
                    <div class="ba-error-msg" id="errTimeSlot" role="alert"><span class="ba-error-text"></span></div>
                  </div>
                </div>
              </div>
            </section>

            <!-- ── STEP 4: REVIEW & CONFIRM ── -->
            <section class="phase-step" id="phaseStep4" aria-labelledby="step4Heading">
              <h1 id="step4Heading" class="ba-step-title">REVIEW &amp; CONFIRM</h1>
              <p class="ba-step-subtitle">Please double-check your information before submitting.</p>

              <div class="ba-review-card">
                <h2 class="ba-review-heading">PATIENT DETAILS</h2>

                <div class="ba-review-section">
                  <div class="ba-review-sec-title">Personal Information</div>
                  <div class="ba-review-grid">
                    <div class="ba-review-item">
                      <span class="ba-review-lbl">Appointment Recipient</span>
                      <span class="ba-review-val" id="revRecipient">-</span>
                    </div>
                    <div class="ba-review-item">
                      <span class="ba-review-lbl">Full Name</span>
                      <span class="ba-review-val" id="revName">-</span>
                    </div>
                    <div class="ba-review-item">
                      <span class="ba-review-lbl">Gender</span>
                      <span class="ba-review-val" id="revGender">-</span>
                    </div>
                    <div class="ba-review-item">
                      <span class="ba-review-lbl">Birthdate &amp; Age</span>
                      <span class="ba-review-val" id="revBirthAge">-</span>
                    </div>
                    <div class="ba-review-item">
                      <span class="ba-review-lbl">Contact Number</span>
                      <span class="ba-review-val" id="revPhone">-</span>
                    </div>
                    <div class="ba-review-item">
                      <span class="ba-review-lbl">Email Address</span>
                      <span class="ba-review-val" id="revEmail">-</span>
                    </div>
                    <div class="ba-review-item full">
                      <span class="ba-review-lbl">Home Address</span>
                      <span class="ba-review-val" id="revAddress">-</span>
                    </div>
                    <div class="ba-review-item">
                      <span class="ba-review-lbl">Accompanying Companion</span>
                      <span class="ba-review-val" id="revCompanion">-</span>
                    </div>
                    <div class="ba-review-item">
                      <span class="ba-review-lbl">Department</span>
                      <span class="ba-review-val" id="revDept">-</span>
                    </div>
                    <div class="ba-review-item full" id="revRelationshipRow" style="display: none;">
                      <span class="ba-review-lbl">Relationship to Patient</span>
                      <span class="ba-review-val" id="revRelationship">-</span>
                    </div>
                  </div>
                </div>

                <div class="ba-review-section">
                  <div class="ba-review-sec-title" id="revMedicalSecTitle">Medical Details</div>
                  <div class="ba-review-grid">
                    <div class="ba-review-item full">
                      <span class="ba-review-lbl" id="revComplaintLabel">Chief Complaint / Reason for Consultation</span>
                      <span class="ba-review-val" id="revComplaint">-</span>
                    </div>
                    <div class="ba-review-item">
                      <span class="ba-review-lbl">Existing Medical Conditions</span>
                      <span class="ba-review-val" id="revConditions">None</span>
                    </div>
                    <div class="ba-review-item">
                      <span class="ba-review-lbl">Current Medications</span>
                      <span class="ba-review-val" id="revMedications">None</span>
                    </div>
                    <div class="ba-review-item full">
                      <span class="ba-review-lbl">Referral Source</span>
                      <span class="ba-review-val" id="revReferral">None</span>
                    </div>
                  </div>
                </div>

                <div class="ba-review-section">
                  <div class="ba-review-sec-title">Appointment Details</div>
                  <div class="ba-review-grid">
                    <div class="ba-review-item">
                      <span class="ba-review-lbl">Consultation Mode</span>
                      <span class="ba-review-val" id="revMode">-</span>
                    </div>
                    <div class="ba-review-item">
                      <span class="ba-review-lbl">Preferred Date</span>
                      <span class="ba-review-val" id="revDate">-</span>
                    </div>
                    <div class="ba-review-item full">
                      <span class="ba-review-lbl">Preferred Time Slot</span>
                      <span class="ba-review-val" id="revTimeSlot">-</span>
                    </div>
                  </div>
                </div>
              </div>
            </section>

            <!-- Action Footer -->
            <div class="ba-actions-footer step1-only" id="actionButtonBar">
              <button type="button" class="ba-btn ba-btn-back" id="btnBackStep" style="display: none;">Back</button>
              <button type="button" class="ba-btn ba-btn-next" id="btnNextStep">
                <span class="ba-spinner" id="btnNextSpinner" style="display: none;" aria-hidden="true"></span>
                <span id="btnNextLabel">Next</span>
              </button>
            </div>
          </div>
        </div>
      </main>
    </div>

    <!-- Submission Confirmation Modal -->
    <div id="bookingSuccessModal" class="ba-submit-modal-overlay hidden-overlay" role="dialog" aria-modal="true" aria-labelledby="successTitle">
      <div class="ba-submit-modal-card">
        <div class="ba-success-ring" aria-hidden="true">
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"></polyline>
          </svg>
        </div>
        <h2 class="ba-success-title" id="successTitle">Appointment Submitted</h2>
        <div class="ba-success-actions" style="display:flex; gap:10px; justify-content:center; flex-wrap:wrap;">
          <a href="/sdn/pages/patient-dashboard.php" class="ba-btn ba-btn-next">Go to Dashboard</a>
        </div>
      </div>
    </div>

    <script>
      window.BA_V2_PROFILE = <?= json_encode($profileBootstrap, JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <script src="/sdn/public/js/patient-slip.js?v=20261128"></script>
    <script src="/sdn/assets/js/book-appointment-v2.js?v=20261128"></script>
    <script>
      document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        const modal = document.getElementById('bookingSuccessModal');
        if (modal && !modal.classList.contains('hidden-overlay')) {
          modal.classList.add('hidden-overlay');
          const nextBtn = document.getElementById('btnNextStep');
          if (nextBtn) nextBtn.focus();
        }
      });
    </script>
  </body>
</html>
