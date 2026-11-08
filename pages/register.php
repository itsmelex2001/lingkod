<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>New Patient Registration | MMWGH LINGKOD</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/sdn/public/css/global.css?v=<?= time() ?>">
  <link rel="stylesheet" href="/sdn/public/css/navigation.css?v=<?= time() ?>">
  <link rel="stylesheet" href="/sdn/dist/assets/main.css?v=<?= time() ?>">
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    html, body {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      background-color: #f1f5f9;
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      color: #1f2937;
      min-height: 100vh;
    }

    /* ── Main Viewport Container (Generous & Beautifully Proportioned) ── */
    #new-patient-registration-app {
      min-height: calc(100vh - 76px);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 24px 20px 32px;
      box-sizing: border-box;
    }

    .main-wrapper {
      width: 100%;
      max-width: 820px;
      margin: 0 auto;
      display: flex;
      flex-direction: column;
      align-items: center;
      box-sizing: border-box;
    }

    /* ── Stepper (Matching Inspo Image 2) ── */
    .stepper-container {
      width: 100%;
      max-width: 640px;
      margin-bottom: 24px;
      flex-shrink: 0;
    }

    .stepper-track {
      position: relative;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .stepper-line {
      position: absolute;
      top: 22px;
      left: 55px;
      right: 55px;
      height: 8px;
      background-color: #5d7563;
      border-radius: 999px;
      z-index: 1;
    }

    .stepper-line-fill {
      height: 100%;
      width: 0%;
      background-color: #1b6e1b;
      border-radius: 9999px;
      transition: width 0.35s ease;
    }

    .step-node {
      position: relative;
      z-index: 2;
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
      width: 160px;
      cursor: pointer;
    }

    .step-circle {
      width: 44px;
      height: 44px;
      border-radius: 50%;
      background-color: #5d7563;
      color: #ffffff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 800;
      font-size: 1.15rem;
      transition: all 0.25s ease;
    }

    .step-node.active .step-circle {
      background-color: #1b6e1b;
    }

    .step-node.current .step-circle {
      background-color: #1b6e1b;
      box-shadow: 0 0 0 4px rgba(27, 110, 27, 0.22);
    }

    .step-text {
      margin-top: 8px;
      font-size: 0.88rem;
      font-weight: 700;
      color: #1e293b;
      white-space: nowrap;
    }

    /* ── Form Card (Copied Exactly from Image 2 Inspo) ── */
    .card-wizard {
      background: #ffffff;
      border-radius: 20px;
      padding: 28px 36px 32px;
      box-shadow: 0 4px 24px rgba(0, 0, 0, 0.05);
      width: 100%;
      max-width: 820px;
      display: flex;
      flex-direction: column;
      box-sizing: border-box;
    }

    .card-heading {
      font-size: 1.55rem;
      font-weight: 800;
      color: #1e293b;
      text-align: center;
      margin: 0 0 20px 0;
      letter-spacing: -0.2px;
    }

    .inner-panel {
      border: 1.5px solid #cbd5e1;
      border-radius: 14px;
      padding: 22px 24px;
      display: flex;
      flex-direction: column;
      gap: 14px;
      background: #ffffff;
    }

    .grid-3 {
      display: grid;
      grid-template-columns: 1fr 1fr 130px;
      gap: 14px;
    }

    .grid-contact {
      display: grid;
      grid-template-columns: 1fr 1.35fr;
      gap: 14px;
    }

    .field-group {
      display: flex;
      flex-direction: column;
      gap: 5px;
    }

    .field-group label {
      font-size: 0.88rem;
      font-weight: 700;
      color: #1e293b;
      line-height: 1.2;
    }

    .req-star {
      color: #dc2626;
      margin-left: 2px;
    }

    .input-ctrl {
      width: 100%;
      padding: 0 14px;
      height: 44px;
      border: 1.5px solid #cbd5e1;
      border-radius: 10px;
      font-size: 0.92rem;
      font-family: inherit;
      color: #1e293b;
      outline: none;
      background: #ffffff;
      box-sizing: border-box;
      transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .input-ctrl::placeholder {
      color: #94a3b8;
    }

    .input-ctrl:focus {
      border-color: #1b6e1b;
      box-shadow: 0 0 0 3px rgba(27, 110, 27, 0.16);
    }

    .input-readonly {
      background-color: #f8fafc !important;
      color: #64748b !important;
      cursor: default;
    }

    .error-msg {
      font-size: 0.72rem;
      color: #dc2626;
      font-weight: 600;
      display: none;
      line-height: 1.2;
      margin-top: 2px;
    }

    .input-ctrl.invalid {
      border-color: #dc2626 !important;
    }

    /* Contact group */
    .contact-wrapper {
      display: flex;
      align-items: center;
      border: 1.5px solid #cbd5e1;
      border-radius: 10px;
      overflow: hidden;
      background: #ffffff;
      height: 44px;
      transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .contact-wrapper:focus-within {
      border-color: #1b6e1b;
      box-shadow: 0 0 0 3px rgba(27, 110, 27, 0.16);
    }

    .prefix-badge {
      padding: 0 14px;
      background: #ffffff;
      font-weight: 700;
      font-size: 0.92rem;
      color: #1e293b;
      border-right: 1.5px solid #cbd5e1;
      user-select: none;
      height: 100%;
      display: flex;
      align-items: center;
    }

    .contact-wrapper input {
      border: none !important;
      border-radius: 0 !important;
      flex: 1;
      padding: 0 12px;
      outline: none;
      font-size: 0.92rem;
      height: 100%;
      box-sizing: border-box;
      color: #1e293b;
      font-family: inherit;
    }

    /* Password Input Wrapper */
    .password-wrapper {
      position: relative;
      display: flex;
      align-items: center;
    }

    .password-wrapper input {
      padding-right: 44px;
    }

    .btn-eye {
      position: absolute;
      right: 8px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: #64748b;
      cursor: pointer;
      padding: 6px 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 6px;
      transition: color 0.15s ease, background-color 0.15s ease;
    }

    .btn-eye:hover {
      color: #1b6e1b;
      background-color: rgba(27, 110, 27, 0.08);
    }

    .btn-eye svg {
      width: 18px;
      height: 18px;
      display: block;
    }

    /* Radio Group */
    .radio-row {
      display: flex;
      gap: 24px;
      align-items: center;
      margin-top: 4px;
    }

    .radio-lbl {
      display: flex;
      align-items: center;
      gap: 8px;
      cursor: pointer;
      font-size: 0.92rem;
      font-weight: 600;
      color: #1f2937;
    }

    .radio-lbl input[type="radio"] {
      accent-color: #1b6e1b;
      width: 18px;
      height: 18px;
      cursor: pointer;
    }

    /* Phase Display */
    .wizard-phase {
      display: none;
    }

    .wizard-phase.active {
      display: block;
    }

    /* Actions Row (Positioned Directly Below Card) */
    .actions-row {
      display: flex;
      justify-content: center;
      gap: 16px;
      margin-top: 22px;
      width: 100%;
    }

    .btn-pill-next {
      background-color: #1b5e20;
      color: #ffffff;
      border: none;
      border-radius: 9999px;
      padding: 12px 68px;
      font-size: 1.02rem;
      font-weight: 700;
      cursor: pointer;
      box-shadow: 0 4px 12px rgba(27, 94, 32, 0.28);
      transition: background-color 0.2s ease, transform 0.15s ease;
      text-decoration: none;
      height: 44px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }

    .btn-pill-next:hover:not(:disabled) {
      background-color: #145314;
      transform: translateY(-1px);
    }

    .btn-pill-back {
      background: #ffffff;
      color: #374151;
      border: 1.5px solid #d1d5db;
      border-radius: 9999px;
      padding: 10px 42px;
      font-size: 0.95rem;
      font-weight: 700;
      cursor: pointer;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
      transition: background-color 0.2s ease, transform 0.15s ease;
      text-decoration: none;
      height: 44px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }

    .btn-pill-back:hover {
      background: #f8fafc;
    }

    /* Confirmation Summary */
    .summary-heading {
      font-size: 0.88rem;
      font-weight: 700;
      color: #1b6e1b;
      border-bottom: 1px solid #e5e7eb;
      padding-bottom: 4px;
      margin-bottom: 6px;
    }

    .summary-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 6px 14px;
      font-size: 0.84rem;
    }

    .summary-item {
      display: flex;
      flex-direction: column;
      gap: 2px;
    }

    .summary-label {
      font-size: 0.72rem;
      color: #6b7280;
      font-weight: 700;
      text-transform: uppercase;
    }

    .summary-val {
      font-weight: 600;
      color: #111827;
      word-break: break-word;
    }

    .notice-box {
      margin-top: 6px;
      background: #ecfdf5;
      border: 1px solid #a7f3d0;
      border-radius: 6px;
      padding: 6px 10px;
      display: flex;
      gap: 6px;
      align-items: flex-start;
      color: #065f46;
      font-size: 0.72rem;
      line-height: 1.35;
    }

    .error-banner {
      display: none;
      margin-top: 6px;
      background: #fef2f2;
      border: 1px solid #fecaca;
      color: #b91c1c;
      border-radius: 6px;
      padding: 6px 10px;
      font-size: 0.76rem;
    }

    /* Success Screen */
    .success-container {
      display: none;
      text-align: center;
      padding: 8px 0;
    }

    .success-icon-wrap {
      width: 52px;
      height: 52px;
      background: #eff6ff;
      color: #2563eb;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 10px;
      box-shadow: 0 0 0 8px rgba(37, 99, 235, 0.1);
    }

    /* ── Registration Success Modal UI ── */
    .reg-modal-backdrop {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.55);
      backdrop-filter: blur(6px);
      -webkit-backdrop-filter: blur(6px);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 9999;
      padding: 1.25rem;
    }

    .reg-modal-card {
      background: #ffffff;
      border-radius: 20px;
      padding: 30px 26px;
      max-width: 440px;
      width: 100%;
      text-align: center;
      box-shadow: 0 20px 45px rgba(0, 0, 0, 0.22);
      animation: modalFadeIn 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards;
      border: 1px solid rgba(27, 110, 27, 0.15);
    }

    @keyframes modalFadeIn {
      from { opacity: 0; transform: scale(0.92) translateY(12px); }
      to { opacity: 1; transform: scale(1) translateY(0); }
    }

    .reg-modal-icon {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      background: #ecfdf5;
      color: #059669;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 16px;
      box-shadow: 0 0 0 8px rgba(16, 185, 129, 0.12);
    }

    .reg-modal-title {
      font-size: 1.35rem;
      font-weight: 800;
      color: #111827;
      margin-bottom: 6px;
    }

    .reg-modal-desc {
      font-size: 0.88rem;
      color: #4b5563;
      margin-bottom: 8px;
    }

    .reg-email-pill {
      display: inline-block;
      background: #f0fdf4;
      color: #166534;
      font-weight: 700;
      font-size: 0.86rem;
      padding: 5px 14px;
      border-radius: 999px;
      border: 1px solid #bbf7d0;
      margin-bottom: 12px;
      word-break: break-all;
    }

    .reg-modal-subdesc {
      font-size: 0.8rem;
      color: #6b7280;
      line-height: 1.45;
      margin-bottom: 22px;
    }

    .reg-modal-btns {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .btn-modal-open-email {
      background: #1b6e1b;
      color: #ffffff !important;
      text-decoration: none;
      font-weight: 700;
      font-size: 0.92rem;
      padding: 10px 18px;
      border-radius: 9999px;
      box-shadow: 0 4px 12px rgba(27, 110, 27, 0.28);
      transition: background 0.15s ease, transform 0.15s ease;
      display: block;
    }

    .btn-modal-open-email:hover {
      background: #145314;
      transform: translateY(-1px);
    }

    .btn-modal-to-login {
      background: #f8fafc;
      color: #374151 !important;
      border: 1px solid #cbd5e1;
      text-decoration: none;
      font-weight: 600;
      font-size: 0.88rem;
      padding: 9px 18px;
      border-radius: 9999px;
      transition: background 0.15s ease;
      display: block;
    }

    .btn-modal-to-login:hover {
      background: #e2e8f0;
    }

    @media (max-width: 768px) {
      html, body { overflow: auto !important; height: auto; max-height: none; }
      #new-patient-registration-app { height: auto; max-height: none; overflow: visible !important; }
      .main-wrapper { height: auto; max-height: none; overflow: visible !important; padding: 16px 14px; }
      .card-wizard { padding: 16px 14px; }
      .inner-panel { padding: 14px 10px; }
      .grid-3, .grid-contact, .summary-grid { grid-template-columns: 1fr; }
      .step-node { width: 85px; }
      .step-text { font-size: 0.68rem; white-space: normal; }
      .btn-pill-next { padding: 8px 30px; width: 100%; }
    }
  </style>
</head>
<body>

  <!-- Top Navigation Bar (Identical to Landing Page) -->
  <nav class="navbar" id="navbar">
    <div style="display: flex; align-items: center; justify-content: space-between; width: 100%; padding: 0 2rem; margin: 0 auto;">
      <a class="logo" href="/sdn/index.php" title="Home">
        <img src="/sdn/public/assets/v3_lingkod_logo.png" alt="Lingkod Logo" class="logo-ver2-img" />
        <span class="logo-text">
          <span class="logo-title-top">Mariveles Mental Wellness and General Hospital</span>
          <span class="logo-title-bottom">LINGKOD</span>
        </span>
      </a>
    </div>
  </nav>

  <!-- Mount Point for Svelte Component -->
  <div id="new-patient-registration-app">

    <!-- High-Fidelity Interactive Wizard (Matches Svelte Component & Screenshot) -->
    <main class="main-wrapper">

      <!-- Stepper -->
      <div class="stepper-container">
        <div class="stepper-track">
          <div class="stepper-line">
            <div id="stepLineFill" class="stepper-line-fill"></div>
          </div>

          <div id="nodeStep1" class="step-node active current" onclick="goToStep(1)">
            <div class="step-circle">1</div>
            <span class="step-text">Personal Information</span>
          </div>

          <div id="nodeStep2" class="step-node" onclick="goToStep(2)">
            <div class="step-circle">2</div>
            <span class="step-text">Account Registration</span>
          </div>

          <div id="nodeStep3" class="step-node" onclick="goToStep(3)">
            <div class="step-circle">3</div>
            <span class="step-text">Confirmation</span>
          </div>
        </div>
      </div>

      <!-- Card -->
      <div class="card-wizard">

        <!-- FORM WRAPPER -->
        <form id="newPatientForm" onsubmit="handleFormSubmit(event)">

          <!-- PHASE 1: PERSONAL INFORMATION -->
          <div id="phase1" class="wizard-phase active">
            <h2 class="card-heading">Personal Information</h2>

            <div class="inner-panel">
              <!-- Row 1 -->
              <div class="grid-3">
                <div class="field-group">
                  <label for="firstName">First Name <span class="req-star">*</span></label>
                  <input type="text" id="firstName" class="input-ctrl" placeholder="Enter your First Name" required />
                  <span id="errFirstName" class="error-msg">First name is required.</span>
                </div>

                <div class="field-group">
                  <label for="lastName">Last Name <span class="req-star">*</span></label>
                  <input type="text" id="lastName" class="input-ctrl" placeholder="Enter your Last Name" required />
                  <span id="errLastName" class="error-msg">Last name is required.</span>
                </div>

                <div class="field-group">
                  <label for="middleInitial">M.I. (Optional)</label>
                  <input type="text" id="middleInitial" class="input-ctrl" placeholder="e.g., B." maxlength="5" />
                </div>
              </div>

              <!-- Row 2 -->
              <div class="grid-3">
                <div class="field-group">
                  <label for="gender">Gender <span class="req-star">*</span></label>
                  <select id="gender" class="input-ctrl" required>
                    <option value="" disabled selected>Select Gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                  </select>
                  <span id="errGender" class="error-msg">Please select gender.</span>
                </div>

                <div class="field-group">
                  <label for="birthdate">Birthdate <span class="req-star">*</span></label>
                  <input type="date" id="birthdate" class="input-ctrl" required onchange="calculateAge()" />
                  <span id="errBirthdate" class="error-msg">Valid birthdate is required.</span>
                </div>

                <div class="field-group">
                  <label for="age">Age</label>
                  <input type="text" id="age" class="input-ctrl input-readonly" placeholder="Age" readonly />
                </div>
              </div>

              <!-- Conditional Minor / Guardian Validation -->
              <div id="guardianSection" style="display:none; background:#fffbeb; border:1.2px solid #fde68a; border-radius:10px; padding:10px 12px; margin-bottom:10px;">
                <div style="display:flex; align-items:center; gap:6px; font-size:0.76rem; font-weight:600; color:#92400e; margin-bottom:6px; line-height:1.3;">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2.3"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                  <span>Minors must be accompanied by a parent/guardian during the hospital visit.</span>
                </div>
                <div class="grid-contact">
                  <div class="field-group">
                    <label for="guardianName">Parent / Guardian Full Name <span class="req-star">*</span></label>
                    <input type="text" id="guardianName" class="input-ctrl" placeholder="e.g. Maria Santos" />
                    <span id="errGuardianName" class="error-msg">Parent / Guardian name is required.</span>
                  </div>
                  <div class="field-group">
                    <label for="guardianContactNumber">Guardian Contact Number <span class="req-star">*</span></label>
                    <div class="contact-wrapper">
                      <span class="prefix-badge">+63</span>
                      <input type="tel" id="guardianContactNumber" placeholder="9XXXXXXXXX" maxlength="11" />
                    </div>
                    <span id="errGuardianContact" class="error-msg">Valid 10-digit number required.</span>
                  </div>
                </div>
              </div>

              <!-- Row 3 -->
              <div class="grid-contact">
                <div class="field-group">
                  <label for="contactNumber">Contact Number <span class="req-star">*</span></label>
                  <div class="contact-wrapper">
                    <span class="prefix-badge">+63</span>
                    <input type="tel" id="contactNumber" placeholder="9XXXXXXXXX" maxlength="11" required />
                  </div>
                  <span id="errContact" class="error-msg">Valid 10-digit number required.</span>
                </div>

                <div class="field-group">
                  <label for="email">Email Address <span class="req-star">*</span></label>
                  <input type="email" id="email" class="input-ctrl" placeholder="Enter your Address" required />
                  <span id="errEmail" class="error-msg">Valid email address required.</span>
                </div>
              </div>

              <!-- Row 4 -->
              <div class="field-group">
                <label for="address">Home Address <span class="req-star">*</span></label>
                <input type="text" id="address" class="input-ctrl" placeholder="Enter your Home Address" required />
                <span id="errAddress" class="error-msg">Home address is required.</span>
              </div>

              <!-- Row 5 -->
              <div class="field-group">
                <label>Accompanying Companion? <span class="req-star">*</span></label>
                <div class="radio-row">
                  <label class="radio-lbl">
                    <input type="radio" name="withCompanion" value="Yes" checked />
                    <span>Yes</span>
                  </label>
                  <label class="radio-lbl">
                    <input type="radio" name="withCompanion" value="No" />
                    <span>No</span>
                  </label>
                </div>
              </div>
            </div>
          </div>

          <!-- PHASE 2: ACCOUNT REGISTRATION -->
          <div id="phase2" class="wizard-phase">
            <h2 class="card-heading">Account Registration</h2>

            <div class="inner-panel">
              <div class="field-group">
                <label for="regUsername">Username <span class="req-star">*</span></label>
                <input type="text" id="regUsername" class="input-ctrl" placeholder="Choose a username" required />
                <span id="errUsername" class="error-msg">Username must be at least 3 characters.</span>
              </div>

              <div class="field-group">
                <label for="regEmailDisplay">Login Email Address</label>
                <input type="email" id="regEmailDisplay" class="input-ctrl input-readonly" readonly />
                <span style="font-size: 0.78rem; color: #64748b;">Verification link will be delivered to this email.</span>
              </div>

              <div class="field-group">
                <label for="regPassword">Password <span class="req-star">*</span></label>
                <div class="password-wrapper">
                  <input type="password" id="regPassword" class="input-ctrl" placeholder="Create password (minimum 8 characters)" required />
                  <button type="button" class="btn-eye" onclick="togglePassVisibility('regPassword', this)" aria-label="Show password" title="Show password">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                      <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                  </button>
                </div>
                <span id="errPassword" class="error-msg">Password must be at least 8 characters.</span>
              </div>

              <div class="field-group">
                <label for="regConfirmPassword">Confirm Password <span class="req-star">*</span></label>
                <div class="password-wrapper">
                  <input type="password" id="regConfirmPassword" class="input-ctrl" placeholder="Re-enter your password" required />
                  <button type="button" class="btn-eye" onclick="togglePassVisibility('regConfirmPassword', this)" aria-label="Show password" title="Show password">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                      <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                  </button>
                </div>
                <span id="errConfirmPassword" class="error-msg">Passwords do not match.</span>
              </div>

              <div style="margin-top: 8px;">
                <label style="display: flex; gap: 10px; align-items: flex-start; cursor: pointer;">
                  <input type="checkbox" id="chkTerms" style="accent-color: #1b6e1b; width: 18px; height: 18px; margin-top: 2px;" />
                  <span style="font-size: 0.84rem; color: #4b5563; line-height: 1.45;">
                    I certify that all details provided are true and correct, and I agree to MMWGH's Data Privacy Policy regarding healthcare information.
                  </span>
                </label>
                <span id="errTerms" class="error-msg">You must accept the data privacy statement.</span>
              </div>
            </div>
          </div>

          <!-- PHASE 3: CONFIRMATION -->
          <div id="phase3" class="wizard-phase">
            <h2 class="card-heading">Confirmation &amp; Review</h2>

            <div class="inner-panel">
              <div class="summary-heading">Personal Information Summary</div>
              <div class="summary-grid">
                <div class="summary-item">
                  <span class="summary-label">Full Name</span>
                  <span id="revFullName" class="summary-val"></span>
                </div>
                <div class="summary-item">
                  <span class="summary-label">Gender &amp; Age</span>
                  <span id="revGenderAge" class="summary-val"></span>
                </div>
                <div class="summary-item">
                  <span class="summary-label">Birthdate</span>
                  <span id="revBirthdate" class="summary-val"></span>
                </div>
                <div class="summary-item">
                  <span class="summary-label">Contact Number</span>
                  <span id="revContact" class="summary-val"></span>
                </div>
                <div class="summary-item">
                  <span class="summary-label">Email Address</span>
                  <span id="revEmail" class="summary-val"></span>
                </div>
                <div class="summary-item">
                  <span class="summary-label">Home Address</span>
                  <span id="revAddress" class="summary-val"></span>
                </div>
                <div class="summary-item">
                  <span class="summary-label">Accompanying Companion</span>
                  <span id="revCompanion" class="summary-val"></span>
                </div>
                <div class="summary-item" id="revGuardianRow" style="display:none;">
                  <span class="summary-label">Parent / Guardian</span>
                  <span id="revGuardian" class="summary-val"></span>
                </div>
              </div>

              <div class="summary-heading" style="margin-top: 1.5rem;">Account Credentials</div>
              <div class="summary-grid">
                <div class="summary-item">
                  <span class="summary-label">Username</span>
                  <span id="revUsername" class="summary-val"></span>
                </div>
                <div class="summary-item">
                  <span class="summary-label">Account Status</span>
                  <span class="summary-val" style="color: #b45309; background: #fef3c7; padding: 2px 8px; border-radius: 6px; display: inline-block; font-size: 0.82rem;">
                    Unverified (Pending Activation)
                  </span>
                </div>
              </div>

              <div class="notice-box">
                <div style="font-weight: bold; color: #059669; font-size: 1.1rem;">&#9432;</div>
                <p>
                  <strong>Email Verification Required:</strong> By clicking Submit, your record is created with <code>is_verified = 0</code>. A verification link will be sent to your email. You must click that link within 24 hours to activate your account for the Patient Portal.
                </p>
              </div>

              <div id="submitErrorBanner" class="error-banner"></div>
            </div>
          </div>

        </form>

      </div>

      <!-- ACTIONS ROW (Centered Below Card, matching reference design) -->
      <div id="wizardActions" class="actions-row">
        <button type="button" id="btnBack" class="btn-pill-back" onclick="handleBackStep()" style="display: none;">Back</button>
        <button type="button" id="btnNext" class="btn-pill-next" onclick="handleNextStep()">Next</button>
        <button type="submit" form="newPatientForm" id="btnSubmit" class="btn-pill-next" style="display: none;" onclick="document.getElementById('newPatientForm').requestSubmit()">Submit Registration</button>
      </div>
    </main>

  </div>

  <!-- Registration Success UI Modal -->
  <div id="regSuccessModal" class="reg-modal-backdrop" style="display: none;">
    <div class="reg-modal-card">
      <div class="reg-modal-icon">
        <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
      </div>
      <h3 class="reg-modal-title">Registration Submitted!</h3>
      <p class="reg-modal-desc">
        A verification email has been sent to:
      </p>
      <div id="regModalEmailBadge" class="reg-email-pill"></div>
      <p class="reg-modal-subdesc">
        Please check your Gmail inbox and click the <strong>"Verify My Account"</strong> button to activate your account.
      </p>
      <div class="reg-modal-btns">
        <a id="regModalEmailBtn" href="https://mail.google.com" target="_blank" rel="noopener noreferrer" class="btn-modal-open-email">
          Open Email Inbox &rarr;
        </a>
        <a href="/sdn/pages/patient-login.php?registered=1" target="_blank" rel="noopener noreferrer" class="btn-modal-to-login">
          Proceed to Patient Portal Sign In
        </a>
      </div>
    </div>
  </div>

  <!-- Load Compiled Svelte Bundle -->
  <script type="module" crossorigin src="/sdn/dist/assets/main.js?v=<?= time() ?>"></script>

  <!-- Interactive Client-side Controller -->
  <script>
    let currentStep = 1;

    function calculateAge() {
      const bInput = document.getElementById('birthdate').value;
      const gSec = document.getElementById('guardianSection');
      if (!bInput) {
        if (gSec) gSec.style.display = 'none';
        return;
      }
      const b = new Date(bInput);
      const today = new Date();
      if (!isNaN(b.getTime()) && b <= today) {
        let age = today.getFullYear() - b.getFullYear();
        const m = today.getMonth() - b.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < b.getDate())) age--;
        document.getElementById('age').value = age >= 0 ? age : '';
        if (gSec) {
          gSec.style.display = (age >= 0 && age < 18) ? 'block' : 'none';
        }
      } else {
        if (gSec) gSec.style.display = 'none';
      }
    }

    const SVG_EYE_OPEN = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>`;
    const SVG_EYE_SLASH = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>`;

    function togglePassVisibility(inputId, btn) {
      const inp = document.getElementById(inputId);
      const isPass = (inp.type === 'password');
      inp.type = isPass ? 'text' : 'password';
      btn.innerHTML = isPass ? SVG_EYE_SLASH : SVG_EYE_OPEN;
      btn.setAttribute('aria-label', isPass ? 'Hide password' : 'Show password');
      btn.title = isPass ? 'Hide password' : 'Show password';
    }

    function validatePhase1() {
      let valid = true;
      const fn = document.getElementById('firstName').value.trim();
      const ln = document.getElementById('lastName').value.trim();
      const mi = document.getElementById('middleInitial').value.trim(); // Optional
      const gender = document.getElementById('gender').value;
      const bd = document.getElementById('birthdate').value;
      const contact = document.getElementById('contactNumber').value.trim();
      const email = document.getElementById('email').value.trim();
      const addr = document.getElementById('address').value.trim();

      // Clear errors
      document.querySelectorAll('.error-msg').forEach(el => el.style.display = 'none');
      document.querySelectorAll('.input-ctrl').forEach(el => el.classList.remove('invalid'));

      if (!fn) { document.getElementById('errFirstName').style.display = 'block'; valid = false; }
      if (!ln) { document.getElementById('errLastName').style.display = 'block'; valid = false; }
      if (!gender) { document.getElementById('errGender').style.display = 'block'; valid = false; }
      if (!bd) { document.getElementById('errBirthdate').style.display = 'block'; valid = false; }
      if (!contact || contact.length < 9) { document.getElementById('errContact').style.display = 'block'; valid = false; }
      if (!email || !email.includes('@')) { document.getElementById('errEmail').style.display = 'block'; valid = false; }
      if (!addr) { document.getElementById('errAddress').style.display = 'block'; valid = false; }

      // Minor / Guardian Validation: Mandatory if age is below 18
      const ageVal = parseInt(document.getElementById('age').value, 10);
      if (!isNaN(ageVal) && ageVal < 18) {
        const gn = document.getElementById('guardianName').value.trim();
        const gc = document.getElementById('guardianContactNumber').value.trim();
        if (!gn) { document.getElementById('errGuardianName').style.display = 'block'; valid = false; }
        if (!gc || gc.length < 9) { document.getElementById('errGuardianContact').style.display = 'block'; valid = false; }
      }

      return valid;
    }

    function validatePhase2() {
      let valid = true;
      const user = document.getElementById('regUsername').value.trim();
      const pass = document.getElementById('regPassword').value;
      const cpass = document.getElementById('regConfirmPassword').value;
      const terms = document.getElementById('chkTerms').checked;

      document.querySelectorAll('.error-msg').forEach(el => el.style.display = 'none');

      if (!user || user.length < 3) { document.getElementById('errUsername').style.display = 'block'; valid = false; }
      if (!pass || pass.length < 8) { document.getElementById('errPassword').style.display = 'block'; valid = false; }
      if (pass !== cpass) { document.getElementById('errConfirmPassword').style.display = 'block'; valid = false; }
      if (!terms) { document.getElementById('errTerms').style.display = 'block'; valid = false; }

      return valid;
    }

    function populateReview() {
      const fn = document.getElementById('firstName').value.trim();
      const mi = document.getElementById('middleInitial').value.trim();
      const ln = document.getElementById('lastName').value.trim();
      document.getElementById('revFullName').innerText = mi ? `${fn} ${mi}. ${ln}` : `${fn} ${ln}`;
      document.getElementById('revGenderAge').innerText = `${document.getElementById('gender').value}, ${document.getElementById('age').value} yrs old`;
      document.getElementById('revBirthdate').innerText = document.getElementById('birthdate').value;
      document.getElementById('revContact').innerText = `+63 ${document.getElementById('contactNumber').value.trim().replace(/^0/, '')}`;
      document.getElementById('revEmail').innerText = document.getElementById('email').value.trim();
      document.getElementById('revAddress').innerText = document.getElementById('address').value.trim();
      
      const comp = document.querySelector('input[name="withCompanion"]:checked')?.value || 'No';
      document.getElementById('revCompanion').innerText = comp;

      const ageVal = parseInt(document.getElementById('age').value, 10);
      const revGuardRow = document.getElementById('revGuardianRow');
      if (!isNaN(ageVal) && ageVal < 18) {
        if (revGuardRow) revGuardRow.style.display = 'block';
        const gn = document.getElementById('guardianName').value.trim();
        const gc = document.getElementById('guardianContactNumber').value.trim().replace(/^0/, '');
        document.getElementById('revGuardian').innerText = `${gn} (+63 ${gc})`;
      } else {
        if (revGuardRow) revGuardRow.style.display = 'none';
      }

      document.getElementById('revUsername').innerText = document.getElementById('regUsername').value.trim();
    }

    function updateWizardUI() {
      document.querySelectorAll('.wizard-phase').forEach(p => p.classList.remove('active'));
      document.getElementById(`phase${currentStep}`).classList.add('active');

      // Update stepper nodes
      document.getElementById('nodeStep1').classList.toggle('active', currentStep >= 1);
      document.getElementById('nodeStep1').classList.toggle('current', currentStep === 1);
      document.getElementById('nodeStep2').classList.toggle('active', currentStep >= 2);
      document.getElementById('nodeStep2').classList.toggle('current', currentStep === 2);
      document.getElementById('nodeStep3').classList.toggle('active', currentStep >= 3);
      document.getElementById('nodeStep3').classList.toggle('current', currentStep === 3);

      const fillPct = currentStep === 1 ? '0%' : (currentStep === 2 ? '50%' : '100%');
      document.getElementById('stepLineFill').style.width = fillPct;

      // Buttons
      document.getElementById('btnBack').style.display = currentStep > 1 ? 'inline-block' : 'none';
      if (currentStep < 3) {
        document.getElementById('btnNext').style.display = 'inline-block';
        document.getElementById('btnSubmit').style.display = 'none';
      } else {
        document.getElementById('btnNext').style.display = 'none';
        document.getElementById('btnSubmit').style.display = 'inline-block';
      }

      window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function handleNextStep() {
      if (currentStep === 1) {
        if (!validatePhase1()) return;
        // Prefill email & username
        const email = document.getElementById('email').value.trim();
        document.getElementById('regEmailDisplay').value = email;
        if (!document.getElementById('regUsername').value) {
          document.getElementById('regUsername').value = email.split('@')[0].replace(/[^a-zA-Z0-9_.]/g, '');
        }
        currentStep = 2;
        updateWizardUI();
      } else if (currentStep === 2) {
        if (!validatePhase2()) return;
        populateReview();
        currentStep = 3;
        updateWizardUI();
      }
    }

    function handleBackStep() {
      if (currentStep > 1) {
        currentStep--;
        updateWizardUI();
      }
    }

    function goToStep(step) {
      if (step < currentStep) {
        currentStep = step;
        updateWizardUI();
      }
    }

    async function handleFormSubmit(e) {
      e.preventDefault();
      const submitBtn = document.getElementById('btnSubmit');
      const errBanner = document.getElementById('submitErrorBanner');
      errBanner.style.display = 'none';

      submitBtn.disabled = true;
      submitBtn.innerText = 'Submitting Registration...';

      const subAge = parseInt(document.getElementById('age').value, 10) || 0;
      const isSubMinor = subAge < 18;
      const subGuardName = document.getElementById('guardianName') ? document.getElementById('guardianName').value.trim() : '';
      const subGuardContact = document.getElementById('guardianContactNumber') ? document.getElementById('guardianContactNumber').value.trim() : '';

      const payload = {
        first_name: document.getElementById('firstName').value.trim(),
        last_name: document.getElementById('lastName').value.trim(),
        middle_initial: document.getElementById('middleInitial').value.trim(),
        gender: document.getElementById('gender').value,
        birthdate: document.getElementById('birthdate').value,
        age: subAge,
        contact_number: '+63 ' + document.getElementById('contactNumber').value.trim().replace(/^0/, ''),
        guardian_name: isSubMinor ? subGuardName : null,
        guardian_contact_number: isSubMinor && subGuardContact ? '+63 ' + subGuardContact.replace(/^0/, '') : null,
        email: document.getElementById('email').value.trim(),
        address: document.getElementById('address').value.trim(),
        with_companion: document.querySelector('input[name="withCompanion"]:checked')?.value || 'No',
        username: document.getElementById('regUsername').value.trim(),
        password: document.getElementById('regPassword').value,
        confirm_password: document.getElementById('regConfirmPassword').value
      };

      try {
        const res = await fetch('/sdn/api/patient_register.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const data = await res.json();

        if (res.ok && data.success) {
          const userEmail = data.email || payload.email;
          document.getElementById('regModalEmailBadge').innerText = userEmail;

          const emailBtn = document.getElementById('regModalEmailBtn');
          if (userEmail.toLowerCase().includes('@gmail.com')) {
            emailBtn.href = 'https://mail.google.com';
            emailBtn.innerHTML = 'Open Gmail (gmail.com) &rarr;';
          } else if (userEmail.toLowerCase().includes('@yahoo.')) {
            emailBtn.href = 'https://mail.yahoo.com';
            emailBtn.innerHTML = 'Open Yahoo Mail &rarr;';
          } else {
            emailBtn.href = 'https://outlook.live.com';
            emailBtn.innerHTML = 'Open Outlook &rarr;';
          }

          document.getElementById('regSuccessModal').style.display = 'flex';
        } else {
          errBanner.innerText = data.message || 'Registration failed. Please check your information.';
          errBanner.style.display = 'block';
        }
      } catch (err) {
        errBanner.innerText = 'Server error. Please ensure Apache/PHP is running.';
        errBanner.style.display = 'block';
      } finally {
        submitBtn.disabled = false;
        submitBtn.innerText = 'Submit Registration';
      }
    }
  </script>
</body>
</html>

