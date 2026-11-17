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
      height: 100vh;
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }

    /* ── Main Viewport Container (Single-Viewport Non-Scrollable) ── */
    #new-patient-registration-app {
      flex: 1;
      min-height: 0;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 14px 16px;
      box-sizing: border-box;
      overflow: hidden;
    }

    .main-wrapper {
      width: 100%;
      max-width: 820px;
      max-height: 100%;
      min-height: 0;
      margin: 0 auto;
      display: flex;
      flex-direction: column;
      align-items: center;
      box-sizing: border-box;
    }

    /* ── Stepper (Compact Single-Viewport) ── */
    .stepper-container {
      width: 100%;
      max-width: 580px;
      margin-bottom: 12px;
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
      top: 16px;
      left: 55px;
      right: 55px;
      height: 2px;
      background-color: #e5e7eb;
      border-radius: 999px;
      z-index: 1;
    }

    .stepper-line-fill {
      height: 100%;
      width: 0%;
      background-color: #16a34a;
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
      width: 150px;
      cursor: pointer;
    }

    .step-circle {
      width: 34px;
      height: 34px;
      border-radius: 50%;
      background-color: #ffffff;
      border: 1.5px solid #d1d5db;
      color: #6b7280;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 0.9rem;
      transition: all 0.2s ease;
    }

    .step-node.active .step-circle,
    .step-node.current .step-circle {
      background-color: #ffffff;
      border: 2px solid #16a34a;
      color: #166534;
      box-shadow: 0 0 0 4px rgba(22, 163, 74, 0.12);
      position: relative;
    }

    .step-node.active:not(.current) {
      cursor: pointer;
    }

    .step-node.active:not(.current):hover .step-circle,
    .step-node.active:not(.current):focus-visible .step-circle {
      transform: scale(1.08);
    }

    .step-node:focus-visible {
      outline: 2px solid #1b6e1b;
      outline-offset: 4px;
      border-radius: 12px;
    }

    @keyframes phaseHeartbeat {
      0%, 100% { transform: scale(1); opacity: 0.9; }
      14% { transform: scale(1.18); opacity: 1; }
      28% { transform: scale(1); opacity: 0.75; }
      42% { transform: scale(1.1); opacity: 1; }
      56% { transform: scale(1); opacity: 0.55; }
    }

    @media (prefers-reduced-motion: reduce) {
      .step-node.current .step-circle::after { animation: none; }
    }

    .step-text {
      margin-top: 5px;
      font-size: 0.78rem;
      font-weight: 600;
      color: #1e293b;
      white-space: nowrap;
    }

    /* ── Form Card (Single-Viewport Minimalist) ── */
    .card-wizard {
      background: #ffffff;
      border: 1px solid #e5e7eb;
      border-radius: 14px;
      padding: 18px 28px 18px;
      box-shadow: 0 4px 20px rgba(17, 24, 39, 0.06);
      width: 100%;
      max-width: 820px;
      max-height: 100%;
      min-height: 0;
      display: flex;
      flex-direction: column;
      box-sizing: border-box;
    }

    #newPatientForm {
      flex: 1;
      min-height: 0;
      overflow-y: auto;
    }

    .card-heading {
      font-size: 1.25rem;
      font-weight: 800;
      color: #111827;
      text-align: center;
      margin: 0 0 12px 0;
      letter-spacing: -0.2px;
    }

    .inner-panel {
      border: 1px solid #e5e7eb;
      border-radius: 10px;
      padding: 14px 18px;
      display: flex;
      flex-direction: column;
      gap: 10px;
      background: #ffffff;
    }

    .grid-3 {
      display: grid;
      grid-template-columns: 1fr 1fr 120px;
      gap: 12px;
    }

    .grid-contact {
      display: grid;
      grid-template-columns: 1fr 1.35fr;
      gap: 12px;
    }

    .field-group {
      display: flex;
      flex-direction: column;
      gap: 3px;
    }

    .field-group label {
      font-size: 0.8rem;
      font-weight: 600;
      color: #1e293b;
      line-height: 1.2;
    }

    .req-star {
      color: #dc2626;
      margin-left: 2px;
    }

    .input-ctrl {
      width: 100%;
      padding: 6px 12px;
      height: 36px;
      border: 0;
      border-radius: 8px;
      font-size: 0.875rem;
      font-family: inherit;
      color: #111827;
      outline: none;
      background: #ffffff;
      box-shadow: 0 1px 2px rgba(17, 24, 39, 0.05), inset 0 0 0 1px #e5e7eb;
      box-sizing: border-box;
      transition: box-shadow 0.15s ease;
    }

    .input-ctrl::placeholder {
      color: #9ca3af;
    }

    .input-ctrl:focus {
      box-shadow: 0 1px 2px rgba(17, 24, 39, 0.05), inset 0 0 0 2px #16a34a;
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
      border: 1px solid #d1d5db;
      border-radius: 8px;
      overflow: hidden;
      background: #ffffff;
      height: 36px;
      box-shadow: 0 1px 2px rgba(17, 24, 39, 0.05);
      box-sizing: border-box;
      transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .contact-wrapper:focus-within {
      border-color: #16a34a;
      box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.18);
    }

    .prefix-badge {
      padding: 0 14px;
      background: #f9fafb;
      font-weight: 700;
      font-size: 0.875rem;
      color: #111827;
      border-right: 1px solid #d1d5db;
      user-select: none;
      height: 100%;
      display: flex;
      align-items: center;
    }

    .contact-wrapper input {
      border: none !important;
      border-radius: 0 !important;
      background: transparent !important;
      flex: 1;
      padding: 0 12px;
      outline: none;
      font-size: 0.875rem;
      height: 100%;
      box-sizing: border-box;
      color: #111827;
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
      width: 32px;
      height: 32px;
      background: none;
      border: none;
      color: #6b7280;
      cursor: pointer;
      padding: 0;
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

    /* Actions Row (Inside Card Footer, Permanently Visible) */
    .actions-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 12px;
      margin-top: auto;
      padding-top: 14px;
      border-top: 1px solid #f3f4f6;
      width: 100%;
      flex-shrink: 0;
    }

    .btn-pill-next {
      background-color: #166534;
      color: #ffffff;
      border: none;
      border-radius: 8px;
      padding: 0 28px;
      font-size: 0.9rem;
      font-weight: 600;
      cursor: pointer;
      box-shadow: 0 1px 2px rgba(17, 24, 39, 0.08);
      transition: background-color 0.15s ease;
      text-decoration: none;
      height: 38px;
      margin-left: auto;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }

    .btn-pill-next:hover:not(:disabled) {
      background-color: #14532d;
    }

    .btn-pill-back {
      background: #ffffff;
      color: #374151;
      border: 1px solid #e5e7eb;
      border-radius: 8px;
      padding: 0 22px;
      font-size: 0.9rem;
      font-weight: 600;
      cursor: pointer;
      box-shadow: 0 1px 2px rgba(17, 24, 39, 0.04);
      transition: background-color 0.15s ease;
      text-decoration: none;
      height: 38px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }

    .btn-pill-back:hover {
      background: #f9fafb;
    }

    /* Confirmation Summary */
    .summary-heading {
      font-size: 0.82rem;
      font-weight: 700;
      color: #166534;
      border-bottom: 1px solid #e5e7eb;
      padding-bottom: 3px;
      margin-bottom: 6px;
    }

    .summary-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 8px 32px;
      font-size: 0.84rem;
    }

    .summary-item {
      display: flex;
      flex-direction: column;
      gap: 3px;
    }

    .summary-label {
      font-size: 0.75rem;
      color: #6b7280;
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: 0.03em;
    }

    .summary-val {
      color: #111827;
      font-weight: 500;
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
      background: rgba(17, 24, 39, 0.5);
      backdrop-filter: blur(4px);
      -webkit-backdrop-filter: blur(4px);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 9999;
      padding: 1.25rem;
    }

    .reg-modal-card {
      background: #ffffff;
      border-radius: 12px;
      padding: 24px;
      max-width: 420px;
      width: 100%;
      text-align: center;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25), inset 0 0 0 1px rgba(0, 0, 0, 0.05);
      animation: modalFadeIn 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
      border: 0;
    }

    @keyframes modalFadeIn {
      from { opacity: 0; transform: scale(0.95) translateY(8px); }
      to { opacity: 1; transform: scale(1) translateY(0); }
    }

    .reg-modal-icon {
      width: 48px;
      height: 48px;
      border-radius: 9999px;
      background: #f0fdf4;
      color: #16a34a;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 14px;
      box-shadow: inset 0 0 0 1px rgba(22, 163, 74, 0.2);
    }

    .reg-modal-title {
      font-size: 1.125rem;
      font-weight: 600;
      color: #111827;
      letter-spacing: -0.015em;
      margin-bottom: 6px;
    }

    .reg-modal-desc {
      font-size: 0.875rem;
      color: #6b7280;
      margin-bottom: 10px;
    }

    .reg-email-pill {
      display: inline-flex;
      align-items: center;
      background: #f0fdf4;
      color: #15803d;
      font-weight: 500;
      font-size: 0.875rem;
      padding: 4px 10px;
      border-radius: 6px;
      border: 0;
      box-shadow: inset 0 0 0 1px rgba(22, 163, 74, 0.2);
      margin-bottom: 12px;
      word-break: break-all;
    }

    .reg-modal-subdesc {
      font-size: 0.8125rem;
      color: #6b7280;
      line-height: 1.45;
      margin-bottom: 20px;
    }

    .reg-modal-btns {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .btn-modal-open-email {
      background: #15803d;
      color: #ffffff !important;
      text-decoration: none;
      font-weight: 500;
      font-size: 0.875rem;
      padding: 10px 16px;
      min-height: 42px;
      border-radius: 8px;
      box-shadow: 0 1px 2px rgba(17, 24, 39, 0.08);
      transition: background-color 0.15s ease;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }

    .btn-modal-open-email:hover {
      background: #166534;
    }

    .btn-modal-to-login {
      background: #ffffff;
      color: #374151 !important;
      border: 0;
      box-shadow: inset 0 0 0 1px #d1d5db;
      text-decoration: none;
      font-weight: 500;
      font-size: 0.875rem;
      padding: 10px 16px;
      min-height: 42px;
      border-radius: 8px;
      transition: background-color 0.15s ease;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }

    .btn-modal-to-login:hover {
      background: #f9fafb;
    }

    @media (max-width: 768px) {
      html, body { overflow: auto !important; height: auto; max-height: none; }
      #new-patient-registration-app {
        height: auto;
        max-height: none;
        min-height: 0;
        justify-content: flex-start;
        overflow: visible !important;
        /* Fixed mobile navbar is 64px and sits on top of the page */
        padding: 80px 14px 32px;
      }
      .main-wrapper { height: auto; max-height: none; overflow: visible !important; padding: 8px 0 0; }
      .stepper-container { overflow: visible; }
      .card-wizard { padding: 16px 14px; }
      .inner-panel { padding: 14px 10px; }
      .grid-3, .grid-contact, .summary-grid { grid-template-columns: 1fr; }
      .stepper-container { width: 100%; max-width: 100%; }
      .stepper-line { left: 36px; right: 36px; }
      .step-node { width: auto; flex: 1; min-width: 0; }
      .step-circle { width: 40px; height: 40px; }
      .btn-pill-next, .btn-pill-back { min-height: 44px; }
    }
      .btn-pill-next { padding: 8px 30px; width: 100%; }
    }
    @keyframes spin { to { transform: rotate(360deg); } }
  </style>
</head>
<body>

  <!-- Top Navigation Bar (Identical to Landing Page) -->
  <nav class="navbar" id="navbar">
    <div class="navbar-inner">
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
            <span class="step-text">Confirmation &amp; Review</span>
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
                  <label for="middleInitial">M.I. <span class="req-star">*</span></label>
                  <input type="text" id="middleInitial" class="input-ctrl" placeholder="e.g., B" maxlength="1" required oninput="this.value = this.value.replace(/[^a-zA-Z]/g, '').toUpperCase().slice(0, 1);" />
                  <span id="errMiddleInitial" class="error-msg" role="alert">Middle initial is required.</span>
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

          <!-- PHASE 2: CONFIRMATION & REVIEW -->
          <div id="phase2" class="wizard-phase">
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

              <div class="summary-heading" style="margin-top: 10px;">System-Generated Credentials</div>
              <div class="summary-grid">
                <div class="summary-item">
                  <span class="summary-label">Username</span>
                  <span id="revUsername" class="summary-val"></span>
                </div>
                <div class="summary-item">
                  <span class="summary-label">Password &amp; Status</span>
                  <span class="summary-val" style="display: inline-flex; align-items: center; width: fit-content; background: #fefce8; color: #a16207; box-shadow: inset 0 0 0 1px rgba(202, 138, 4, 0.25); padding: 3px 9px; border-radius: 6px; font-size: 0.78rem; font-weight: 600;">
                    Auto-Generated &amp; Unverified
                  </span>
                </div>
              </div>

              <div style="margin-top: 6px;">
                <label style="display: flex; gap: 8px; align-items: flex-start; cursor: pointer;">
                  <input type="checkbox" id="chkTerms" style="accent-color: #166534; width: 16px; height: 16px; margin-top: 2px;" />
                  <span style="font-size: 0.78rem; color: #4b5563; line-height: 1.4;">
                    I certify that all details provided are true and correct, and I agree to MMWGH's Data Privacy Policy regarding healthcare information.
                  </span>
                </label>
                <span id="errTerms" class="error-msg" role="alert">You must accept the data privacy statement.</span>
              </div>

              <div class="notice-box" style="margin-top: 4px; padding: 8px 12px; font-size: 0.78rem;">
                <div style="font-weight: bold; color: #166534; font-size: 1rem;">&#9432;</div>
                <p style="margin: 0;">
                  <strong>Credential Delivery &amp; Email OTP:</strong> Your <code>.lingkod</code> username and temporary password will be emailed upon submission. A 6-digit email OTP will be required on your first sign-in.
                </p>
              </div>

              <div id="submitErrorBanner" class="error-banner"></div>
            </div>
          </div>

        </form>

        <!-- ACTIONS ROW (Inside Card Footer, Permanently Visible) -->
        <div id="wizardActions" class="actions-row">
          <button type="button" id="btnBack" class="btn-pill-back" onclick="handleBackStep()" style="display: none;">Back</button>
          <button type="button" id="btnNext" class="btn-pill-next" onclick="handleNextStep()">Next</button>
          <button type="submit" form="newPatientForm" id="btnSubmit" class="btn-pill-next" style="display: none;" onclick="document.getElementById('newPatientForm').requestSubmit()">Submit Registration</button>
        </div>

      </div>
    </main>

  </div>

  <!-- Registration Success UI Modal -->
  <div id="regSuccessModal" class="reg-modal-backdrop" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="regModalTitle">
    <div class="reg-modal-card">
      <div class="reg-modal-icon" aria-hidden="true">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
      </div>
      <h3 id="regModalTitle" class="reg-modal-title">Registration Submitted!</h3>
      <p class="reg-modal-desc">
        Your system-generated credentials and a verification link have been sent to:
      </p>
      <div id="regModalEmailBadge" class="reg-email-pill"></div>
      <p class="reg-modal-subdesc">
        Check your inbox for your <code>.lingkod</code> username, temporary password, and 6-digit OTP, then verify your account before signing in.
      </p>
      <div class="reg-modal-btns">
        <a id="regModalEmailBtn" href="https://mail.google.com" target="_blank" rel="noopener noreferrer" class="btn-modal-open-email">
          Open Email Inbox &rarr;
        </a>
        <a href="/sdn/pages/verify-otp.php" class="btn-modal-to-login">
          Enter 6-Digit Verification Code
        </a>
      </div>
    </div>
  </div>

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
      const mi = document.getElementById('middleInitial').value.trim();
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
      if (!mi) { document.getElementById('errMiddleInitial').style.display = 'block'; document.getElementById('middleInitial').classList.add('invalid'); valid = false; }
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

    function populateReview() {
      const fn = document.getElementById('firstName').value.trim();
      const mi = document.getElementById('middleInitial').value.replace(/[^a-zA-Z]/g, '').toUpperCase().slice(0, 1);
      const ln = document.getElementById('lastName').value.trim();
      document.getElementById('revFullName').innerText = mi ? `${fn} ${mi}. ${ln}` : `${fn} ${ln}`;
      document.getElementById('revGenderAge').innerText = `${document.getElementById('gender').value}, ${document.getElementById('age').value} yrs old`;
      document.getElementById('revBirthdate').innerText = document.getElementById('birthdate').value;
      document.getElementById('revContact').innerText = `+63 ${document.getElementById('contactNumber').value.trim().replace(/^0/, '')}`;
      const rawEmail = document.getElementById('email').value.trim();
      document.getElementById('revEmail').innerText = rawEmail;
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

      const emailPrefix = (rawEmail.split('@')[0] || 'patient').toLowerCase().replace(/[^a-z0-9._-]/g, '') || 'patient';
      document.getElementById('revUsername').innerText = `${emailPrefix}.lingkod (emailed)`;
    }

    function updateWizardUI() {
      document.querySelectorAll('.wizard-phase').forEach(p => p.classList.remove('active'));
      document.getElementById(`phase${currentStep}`).classList.add('active');

      // Update stepper nodes (2-step wizard)
      document.getElementById('nodeStep1').classList.toggle('active', currentStep >= 1);
      document.getElementById('nodeStep1').classList.toggle('current', currentStep === 1);
      document.getElementById('nodeStep2').classList.toggle('active', currentStep >= 2);
      document.getElementById('nodeStep2').classList.toggle('current', currentStep === 2);

      document.getElementById('stepLineFill').style.width = currentStep === 1 ? '0%' : '100%';

      // Buttons
      document.getElementById('btnBack').style.display = currentStep > 1 ? 'inline-flex' : 'none';
      if (currentStep < 2) {
        document.getElementById('btnNext').style.display = 'inline-flex';
        document.getElementById('btnSubmit').style.display = 'none';
      } else {
        document.getElementById('btnNext').style.display = 'none';
        document.getElementById('btnSubmit').style.display = 'inline-flex';
      }
    }

    function handleNextStep() {
      if (currentStep === 1) {
        if (!validatePhase1()) return;
        populateReview();
        currentStep = 2;
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
      const errTerms = document.getElementById('errTerms');
      if (errTerms) errTerms.style.display = 'none';
      if (!document.getElementById('chkTerms').checked) {
        if (errTerms) errTerms.style.display = 'block';
        return;
      }
      const submitBtn = document.getElementById('btnSubmit');
      const backBtn = document.getElementById('btnBack');
      const errBanner = document.getElementById('submitErrorBanner');
      errBanner.style.display = 'none';

      submitBtn.disabled = true;
      if (backBtn) backBtn.disabled = true;

      const subAge = parseInt(document.getElementById('age').value, 10) || 0;
      const isSubMinor = subAge < 18;
      const subGuardName = document.getElementById('guardianName') ? document.getElementById('guardianName').value.trim() : '';
      const subGuardContact = document.getElementById('guardianContactNumber') ? document.getElementById('guardianContactNumber').value.trim() : '';

      const rawMi = document.getElementById('middleInitial').value.replace(/[^a-zA-Z]/g, '').toUpperCase().slice(0, 1);
      const payload = {
        first_name: document.getElementById('firstName').value.trim(),
        last_name: document.getElementById('lastName').value.trim(),
        middle_initial: rawMi ? `${rawMi}.` : '',
        gender: document.getElementById('gender').value,
        birthdate: document.getElementById('birthdate').value,
        age: subAge,
        contact_number: '+63 ' + document.getElementById('contactNumber').value.trim().replace(/^0/, ''),
        guardian_name: isSubMinor ? subGuardName : null,
        guardian_contact_number: isSubMinor && subGuardContact ? '+63 ' + subGuardContact.replace(/^0/, '') : null,
        email: document.getElementById('email').value.trim(),
        address: document.getElementById('address').value.trim(),
        with_companion: document.querySelector('input[name="withCompanion"]:checked')?.value || 'No'
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
        if (backBtn) backBtn.disabled = false;
      }
    }
  </script>
</body>
</html>

