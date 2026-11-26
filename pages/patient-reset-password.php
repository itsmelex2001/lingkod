<?php
session_start();
require_once __DIR__ . '/../api/config/db.php';

$token = trim($_GET['token'] ?? '');
$tokenValid = false;
$patientInfo = null;
$errorMessage = '';

if ($token === '') {
    $errorMessage = 'No password reset token was provided in the link.';
} else {
    try {
        $stmt = $pdo->prepare("
            SELECT id, patient_id, full_name, email, reset_token_expires_at
            FROM patients
            WHERE reset_token = :token
            LIMIT 1
        ");
        $stmt->execute([':token' => $token]);
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$patient) {
            $errorMessage = 'This password reset link is invalid or has already been used.';
        } elseif (!empty($patient['reset_token_expires_at']) && strtotime($patient['reset_token_expires_at']) < time()) {
            $errorMessage = 'This password reset link has expired (valid for 1 hour). Please request a new one.';
        } else {
            $tokenValid = true;
            $patientInfo = $patient;
        }
    } catch (PDOException $e) {
        $errorMessage = 'A database error occurred. Please try again later.';
    }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Reset Password | MMWGH LINGKOD</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap"
      rel="stylesheet"
    />

    <style>
      :root {
        --color-primary: #0a7c29;
        --color-primary-hover: #086321;
        --color-primary-active: #064e1a;
        --color-primary-focus: rgba(10, 124, 41, 0.22);
        --color-surface-left: #04200e;
        --color-surface-right: #ffffff;
        --color-bg: #f8faf8;
        --color-text-strong: #111827;
        --color-text-body: #374151;
        --color-text-muted: #6b7280;
        --color-border: #d1d5db;
        --color-border-hover: #9ca3af;
        --color-border-focus: #0a7c29;
        --color-error: #dc2626;
        --color-error-bg: #fef2f2;
        --color-warning: #b45309;
        --color-warning-bg: #fffbeb;
        --color-success: #15803d;
        --color-success-bg: #f0fdf4;
        --radius: 8px;
        --control-height: clamp(38px, 4.4vh, 42px);
        --font-sans: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        --font-serif: 'Playfair Display', Georgia, serif;
      }

      *, *::before, *::after {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
      }

      html, body {
        width: 100%;
        height: 100%;
        margin: 0;
        overflow: hidden;
        overflow-x: hidden;
        font-family: var(--font-sans);
        background-color: var(--color-bg);
        color: var(--color-text-body);
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        scrollbar-width: none;
        -ms-overflow-style: none;
      }

      ::-webkit-scrollbar {
        display: none;
      }

      /* ── Layout Grid ── */
      .portal-layout {
        display: grid;
        grid-template-columns: 1fr 1fr;
        width: 100%;
        height: 100vh;
        height: 100dvh;
        max-height: 100dvh;
        overflow: hidden;
      }

      /* ── Left Panel (Hospital Photo & Layered Green Shadow) ── */
      .portal-left {
        position: relative;
        background-color: var(--color-surface-left);
        background-image: url('/sdn/public/assets/mmwgh_front.jpeg');
        background-size: cover;
        background-position: center;
        color: #ffffff;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: clamp(1.5rem, 2.8vw, 3rem);
        height: 100%;
        min-height: 0;
        overflow: hidden;
      }

      .portal-left::before {
        content: '';
        position: absolute;
        inset: 0;
        background:
          linear-gradient(180deg, rgba(4, 32, 14, 0.72) 0%, rgba(4, 32, 14, 0.45) 45%, rgba(4, 32, 14, 0.88) 100%),
          radial-gradient(circle at 20% 20%, rgba(10, 124, 41, 0.35) 0%, transparent 60%);
        pointer-events: none;
        z-index: 1;
      }

      .portal-left-content {
        position: relative;
        z-index: 2;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        height: 100%;
      }

      .portal-left-brand {
        display: flex;
        align-items: center;
        gap: 0.85rem;
      }

      .hospital-logo {
        width: 44px;
        height: 44px;
        object-fit: contain;
        filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.35));
      }

      .hospital-meta {
        display: flex;
        flex-direction: column;
        line-height: 1.15;
      }

      .hospital-acronym {
        font-size: 0.95rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        color: #ffffff;
      }

      .hospital-fullname {
        font-size: 0.74rem;
        color: rgba(255, 255, 255, 0.82);
        max-width: 280px;
        margin-top: 2px;
      }

      .portal-left-body {
        margin-top: auto;
        padding-bottom: clamp(0.5rem, 1.5vh, 1.5rem);
      }

      .portal-left-tagline {
        font-family: var(--font-serif);
        font-size: clamp(3rem, 4.6vw, 4.35rem);
        font-weight: 600;
        line-height: 1.15;
        color: #ffffff;
        margin-bottom: 0.35rem;
      }

      .portal-left-translation {
        display: block;
        font-family: var(--font-sans);
        font-size: clamp(1.2rem, 1.6vw, 1.5rem);
        font-weight: 400;
        color: rgba(255, 255, 255, 0.75);
        margin-top: 0.45rem;
      }

      .portal-left-divider {
        width: 40px;
        height: 2px;
        background-color: var(--color-primary);
        margin: clamp(0.6rem, 1.2vh, 1rem) 0;
      }

      .portal-bullet-list {
        list-style: none;
        display: flex;
        flex-direction: column;
        gap: clamp(0.45rem, 0.9vh, 0.75rem);
      }

      .portal-bullet-item {
        display: flex;
        align-items: flex-start;
        gap: 0.65rem;
        font-size: 0.85rem;
        line-height: 1.4;
        color: rgba(255, 255, 255, 0.88);
      }

      .portal-bullet-icon {
        flex-shrink: 0;
        width: 16px;
        height: 16px;
        margin-top: 2px;
        color: #86efac;
      }

      /* ── Right Panel (Form) ── */
      .portal-right {
        position: relative;
        background-color: var(--color-surface-right);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: clamp(1.25rem, 2.2vw, 2.5rem);
        height: 100%;
        min-height: 0;
        overflow-y: auto;
        overflow-x: hidden;
      }

      .portal-right-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 100%;
        margin-bottom: clamp(0.5rem, 1.2vh, 1rem);
        flex-shrink: 0;
      }

      .icon-btn-home {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: var(--radius);
        color: var(--color-text-muted);
        background: transparent;
        border: 1px solid transparent;
        text-decoration: none;
        transition: all 0.15s ease;
      }

      .icon-btn-home:hover {
        color: var(--color-text-strong);
        background-color: #f3f4f6;
        border-color: #e5e7eb;
      }

      .icon-btn-home svg {
        width: 18px;
        height: 18px;
      }

      .lang-toggle {
        display: inline-flex;
        align-items: center;
        background: #f3f4f6;
        border-radius: 6px;
        padding: 2px;
        border: 1px solid #e5e7eb;
      }

      .lang-btn {
        background: transparent;
        border: none;
        padding: 4px 10px;
        font-size: 0.74rem;
        font-weight: 600;
        color: var(--color-text-muted);
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.15s ease;
      }

      .lang-btn.active {
        background: #ffffff;
        color: var(--color-text-strong);
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.06);
      }

      /* ── Form Container ── */
      .portal-form-container {
        width: 100%;
        max-width: 380px;
        margin: auto;
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 0.25rem 0;
        flex-shrink: 0;
      }

      .brand-header {
        margin-bottom: clamp(0.9rem, 1.6vh, 1.35rem);
      }

      .brand-logo-img {
        width: clamp(150px, 16vw, 190px);
        height: auto;
        object-fit: contain;
        display: block;
        margin: 0 0 clamp(10px, 1.6vh, 16px) 0;
      }

      .portal-heading {
        font-size: clamp(1.35rem, 1.8vw, 1.65rem);
        font-weight: 600;
        color: var(--color-text-strong);
        letter-spacing: -0.02em;
        line-height: 1.25;
        margin-bottom: 0.25rem;
      }

      .portal-subheading {
        font-size: 0.84rem;
        color: var(--color-text-muted);
        line-height: 1.45;
      }

      /* Account Email Info Strip */
      .account-email-info {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 6px 12px;
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        font-size: 0.8rem;
        color: var(--color-text-body);
        margin-top: 0.65rem;
        margin-bottom: clamp(10px, 1.5vh, 16px);
      }

      .account-email-info svg {
        color: var(--color-text-muted);
        flex-shrink: 0;
      }

      /* ── Status Alerts ── */
      .auth-alert {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        padding: 0.6rem 0.85rem;
        border-radius: var(--radius);
        font-size: 0.8rem;
        line-height: 1.4;
        margin-bottom: clamp(0.7rem, 1.3vh, 1rem);
      }

      .auth-alert-error {
        background-color: var(--color-error-bg);
        border: 1px solid #fecaca;
        color: var(--color-error);
      }

      .auth-alert-success {
        background-color: var(--color-success-bg);
        border: 1px solid #bbf7d0;
        color: var(--color-success);
      }

      .auth-alert-icon {
        flex-shrink: 0;
        width: 16px;
        height: 16px;
      }

      /* ── Form Inputs ── */
      .form-group {
        margin-bottom: clamp(0.75rem, 1.4vh, 1.1rem);
        position: relative;
      }

      .form-label {
        display: block;
        font-size: 0.8rem;
        font-weight: 500;
        color: var(--color-text-body);
        margin-bottom: 0.35rem;
      }

      .input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
      }

      .input-icon-lead {
        position: absolute;
        left: 12px;
        width: 16px;
        height: 16px;
        color: var(--color-text-muted);
        pointer-events: none;
        display: flex;
        align-items: center;
        justify-content: center;
      }

      .form-input {
        width: 100%;
        height: var(--control-height);
        padding: 0 38px 0 38px;
        font-family: var(--font-sans);
        font-size: 0.88rem;
        color: var(--color-text-strong);
        background-color: #ffffff;
        border: 1px solid var(--color-border);
        border-radius: var(--radius);
        outline: none;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
      }

      .form-input::placeholder {
        color: #9ca3af;
      }

      .form-input:hover {
        border-color: var(--color-border-hover);
      }

      .form-input:focus {
        border-color: var(--color-border-focus);
        box-shadow: 0 0 0 3px var(--color-primary-focus);
      }

      .form-input.is-invalid {
        border-color: var(--color-error);
      }

      .form-input.is-invalid:focus {
        box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.18);
      }

      .btn-eye-toggle {
        position: absolute;
        right: 8px;
        background: transparent;
        border: none;
        padding: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--color-text-muted);
        cursor: pointer;
        border-radius: 4px;
        transition: color 0.15s ease;
      }

      .btn-eye-toggle:hover {
        color: var(--color-text-strong);
      }

      .btn-eye-toggle svg {
        width: 16px;
        height: 16px;
      }

      .field-error {
        display: none;
        font-size: 0.74rem;
        color: var(--color-error);
        margin-top: 0.3rem;
      }

      .field-error.visible {
        display: block;
      }

      /* Caps Lock Warning */
      .caps-warning {
        display: none;
        align-items: center;
        gap: 5px;
        font-size: 0.72rem;
        color: var(--color-warning);
        margin-top: 0.25rem;
      }

      .caps-warning.visible {
        display: flex;
      }

      /* Live Requirements Checklist & Strength */
      .password-requirements {
        margin-top: 0.4rem;
        font-size: 0.76rem;
        color: var(--color-text-muted);
        display: flex;
        flex-direction: column;
        gap: 3px;
      }

      .requirement-item {
        display: flex;
        align-items: center;
        gap: 5px;
        transition: color 0.15s ease;
      }

      .requirement-item.met {
        color: var(--color-success);
        font-weight: 500;
      }

      .requirement-dot {
        width: 14px;
        height: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
      }

      .strength-bar-track {
        height: 3px;
        background: #e5e7eb;
        border-radius: 2px;
        margin-top: 6px;
        overflow: hidden;
      }

      .strength-bar-fill {
        height: 100%;
        width: 0%;
        background: var(--color-text-muted);
        transition: width 0.2s ease, background-color 0.2s ease;
      }
      .strength-bar-fill.weak { width: 33%; background-color: var(--color-error); }
      .strength-bar-fill.medium { width: 66%; background-color: var(--color-warning); }
      .strength-bar-fill.strong { width: 100%; background-color: var(--color-success); }

      /* Match Indicator */
      .match-indicator {
        display: none;
        align-items: center;
        gap: 5px;
        font-size: 0.74rem;
        margin-top: 0.3rem;
      }

      .match-indicator.matching {
        display: flex;
        color: var(--color-success);
      }

      .match-indicator.mismatch {
        display: flex;
        color: var(--color-error);
      }

      /* ── Submit Button ── */
      .btn-submit {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        width: 100%;
        height: var(--control-height);
        background-color: var(--color-primary);
        color: #ffffff;
        font-family: var(--font-sans);
        font-size: 0.88rem;
        font-weight: 600;
        border: none;
        border-radius: var(--radius);
        cursor: pointer;
        transition: background-color 0.15s ease, opacity 0.15s ease;
        margin-top: clamp(0.5rem, 1vh, 0.85rem);
      }

      .btn-submit:hover:not(:disabled) {
        background-color: var(--color-primary-hover);
      }

      .btn-submit:active:not(:disabled) {
        background-color: var(--color-primary-active);
      }

      .btn-submit:disabled {
        opacity: 0.55;
        cursor: not-allowed;
      }

      .btn-submit:focus-visible {
        outline: 2px solid var(--color-primary);
        outline-offset: 2px;
      }

      /* Quiet Return Link */
      .quiet-link-row {
        margin-top: clamp(0.8rem, 1.4vh, 1.25rem);
        text-align: center;
      }

      .quiet-link {
        font-size: 0.82rem;
        color: var(--color-text-muted);
        text-decoration: none;
        transition: color 0.15s ease;
      }

      .quiet-link:hover {
        color: var(--color-text-strong);
        text-decoration: underline;
      }

      /* Notice & Success States */
      .state-tile {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 14px;
      }

      .state-tile-neutral {
        background: #f3f4f6;
        color: var(--color-text-muted);
      }

      .state-tile-success {
        background: #e8f5ec;
        color: var(--color-primary);
      }

      /* ── Footer ── */
      .portal-footer {
        margin-top: auto;
        padding-top: clamp(0.75rem, 1.4vh, 1.25rem);
        border-top: 1px solid #f1f3f2;
        font-size: 0.72rem;
        line-height: 1.5;
        color: var(--color-text-muted);
        text-align: center;
        flex-shrink: 0;
      }

      .footer-dpa {
        margin-bottom: 4px;
      }

      .footer-links {
        margin-bottom: 4px;
      }

      .footer-links a {
        color: var(--color-text-muted);
        text-decoration: none;
      }

      .footer-links a:hover {
        color: var(--color-text-strong);
        text-decoration: underline;
      }

      .footer-help {
        color: #9ca3af;
      }

      /* Shake animation for errors */
      @keyframes shake {
        0%, 100% { transform: translateX(0); }
        20%, 60% { transform: translateX(-5px); }
        40%, 80% { transform: translateX(5px); }
      }

      .shake {
        animation: shake 0.35s ease-in-out;
      }

      .spinner {
        display: inline-block;
        width: 14px;
        height: 14px;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        border-top-color: #ffffff;
        animation: spin 0.7s linear infinite;
      }

      @keyframes spin {
        to { transform: rotate(360deg); }
      }

      /* Responsive */
      @media (max-width: 820px) {
        html, body {
          overflow-y: auto;
          overflow-x: hidden;
          height: auto;
          min-height: 100%;
        }

        .portal-layout {
          grid-template-columns: 1fr;
          height: auto;
          min-height: 100dvh;
          overflow: visible;
        }

        .portal-left {
          padding: 1.25rem 1.5rem;
          min-height: auto;
          height: auto;
        }

        .portal-bullet-list,
        .portal-left-divider {
          display: none;
        }

        .portal-left-body {
          margin: 0.5rem 0 0;
          padding: 0;
        }

        .portal-left-tagline {
          font-size: 1.55rem;
        }

        .portal-left-translation {
          font-size: 1rem;
          margin-top: 0.25rem;
        }

        .portal-right {
          padding: 1.5rem 1.25rem;
          height: auto;
          min-height: calc(100dvh - 120px);
          overflow: visible;
        }

        .portal-form-container {
          margin: 1.25rem auto;
        }

        .brand-header {
          margin-bottom: 1.25rem;
        }

        .brand-logo-img {
          width: clamp(130px, 35vw, 150px);
          height: auto;
          margin: 0 0 16px 0;
        }

        .portal-heading {
          font-size: 1.4rem;
        }
      }

      @media (max-height: 700px) and (min-width: 821px) {
        .portal-right {
          overflow-y: auto;
        }
      }
    </style>
  </head>
  <body>
    <div class="portal-layout">
      <!-- ── Left Panel ── -->
      <aside class="portal-left" aria-label="Hospital Overview">
        <div class="portal-left-content">
          <header class="portal-left-brand">
            <img
              src="/sdn/public/assets/mmwgh_logo.png"
              alt="MMWGH Official Seal"
              class="hospital-logo"
            />
            <div class="hospital-meta">
              <span class="hospital-acronym">MMWGH</span>
              <span class="hospital-fullname">Mariveles Mental Wellness and General Hospital</span>
            </div>
          </header>

          <div class="portal-left-body">
            <h1 class="portal-left-tagline" id="leftTagline">
              Maaasahang Lingkod
              <span class="portal-left-translation" id="leftTranslation">Reliable Service</span>
            </h1>

            <div class="portal-left-divider" aria-hidden="true"></div>

            <ul class="portal-bullet-list" id="leftBullets">
              <li class="portal-bullet-item">
                <span class="portal-bullet-icon" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                  </svg>
                </span>
                <span class="bullet-text" data-en="Accessible digital consultations and healthcare guidance" data-fil="Madaling maabot na digital na konsultasyon at gabay pangkalusugan">
                  Accessible digital consultations and healthcare guidance
                </span>
              </li>
              <li class="portal-bullet-item">
                <span class="portal-bullet-icon" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                  </svg>
                </span>
                <span class="bullet-text" data-en="Streamlined appointment scheduling and queue status" data-fil="Mabilis na pag-iskedyul ng konsultasyon at pagsubaybay sa pila">
                  Streamlined appointment scheduling and queue status
                </span>
              </li>
              <li class="portal-bullet-item">
                <span class="portal-bullet-icon" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                  </svg>
                </span>
                <span class="bullet-text" data-en="Secure and confidential electronic health records" data-fil="Ligtas at kumpidensyal na rekord ng kalusugan">
                  Secure and confidential electronic health records
                </span>
              </li>
            </ul>
          </div>
        </div>
      </aside>

      <!-- ── Right Panel ── -->
      <main class="portal-right" aria-label="Reset Password">
        <div class="portal-right-header">
          <a href="/sdn/index.php" class="icon-btn-home" aria-label="Return to hospital homepage" title="Home">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
              <polyline points="9 22 9 12 15 12 15 22"></polyline>
            </svg>
          </a>

          <div class="lang-toggle" role="group" aria-label="Language Selector">
            <button type="button" class="lang-btn active" id="btnLangEN" onclick="setLanguage('en')">EN</button>
            <button type="button" class="lang-btn" id="btnLangFIL" onclick="setLanguage('fil')">FIL</button>
          </div>
        </div>

        <div class="portal-form-container">
          <header class="brand-header">
            <img
              src="/sdn/public/assets/v2_lingkod_logo_cropped.png"
              alt="Lingkod"
              class="brand-logo-img"
            />
            <h2 class="portal-heading" id="uiPortalHeading">Reset Password</h2>
            <p class="portal-subheading" id="uiPortalSubheading">
              Create a new secure password for your account.
            </p>
            <?php if ($tokenValid && $patientInfo): ?>
              <div class="account-email-info">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                  <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                </svg>
                <span><span id="accountPrefix">Account:</span> <strong><?= htmlspecialchars($patientInfo['email']) ?></strong></span>
              </div>
            <?php endif; ?>
          </header>

          <!-- Error Alert Banner -->
          <div id="resetErrorMessage" class="auth-alert auth-alert-error" style="display: none;" role="alert" aria-live="assertive">
            <span class="auth-alert-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
              </svg>
            </span>
            <span id="resetErrorText"></span>
          </div>

          <?php if (!$tokenValid): ?>
            <!-- Invalid or Expired Token State -->
            <div id="invalidTokenState">
              <div class="state-tile state-tile-neutral" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10"></circle>
                  <line x1="12" y1="8" x2="12" y2="12"></line>
                  <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
              </div>
              <p style="font-size: 0.88rem; line-height: 1.5; color: var(--color-text-body); margin-bottom: 1.25rem;" id="invalidTokenDesc">
                <?= htmlspecialchars($errorMessage ?: 'This reset link is invalid or has expired.') ?>
              </p>
              <a href="/sdn/pages/patient-login.php?action=forgot" class="btn-submit" style="text-decoration: none;" id="btnRequestNew">
                Request a new link
              </a>
              <div class="quiet-link-row">
                <a href="/sdn/pages/patient-login.php" class="quiet-link" id="linkBackToSignInvalid">
                  Back to Sign In
                </a>
              </div>
            </div>

          <?php else: ?>
            <!-- Active Form -->
            <form id="resetForm" novalidate onsubmit="handleResetSubmit(event)">
              <input type="hidden" id="resetTokenInput" value="<?= htmlspecialchars($token) ?>" />

              <!-- New Password -->
              <div class="form-group" id="groupNewPassword">
                <label for="newPassword" class="form-label" id="labelNewPass">New Password</label>
                <div class="input-wrapper">
                  <span class="input-icon-lead" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                      <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                  </span>
                  <input
                    type="password"
                    id="newPassword"
                    name="new_password"
                    class="form-input"
                    placeholder="Enter at least 8 characters"
                    autocomplete="new-password"
                    required
                    aria-required="true"
                    oninput="handlePasswordInputs()"
                    onkeyup="checkCapsLock(event, 'capsWarningNew')"
                    onkeydown="checkCapsLock(event, 'capsWarningNew')"
                  />
                  <button
                    type="button"
                    class="btn-eye-toggle"
                    id="toggleNewPassBtn"
                    onclick="togglePasswordVisibility('newPassword', this)"
                    aria-label="Show password"
                    title="Show password"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                      <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                  </button>
                </div>

                <div class="caps-warning" id="capsWarningNew" role="status" aria-live="polite">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m18 15-6-6-6 6"></path>
                  </svg>
                  <span id="capsWarningTextNew">Caps Lock is ON</span>
                </div>

                <!-- Requirement Checklist & Minimal Strength -->
                <div class="password-requirements">
                  <div class="requirement-item" id="reqLength">
                    <span class="requirement-dot" id="reqDotLength">
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="4"></circle>
                      </svg>
                    </span>
                    <span id="reqTextLength">At least 8 characters</span>
                  </div>
                  <div class="strength-bar-track" aria-hidden="true">
                    <div class="strength-bar-fill" id="strengthBar"></div>
                  </div>
                </div>
              </div>

              <!-- Confirm New Password -->
              <div class="form-group" id="groupConfirmPassword">
                <label for="confirmPassword" class="form-label" id="labelConfirmPass">Confirm New Password</label>
                <div class="input-wrapper">
                  <span class="input-icon-lead" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                      <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                  </span>
                  <input
                    type="password"
                    id="confirmPassword"
                    name="confirm_password"
                    class="form-input"
                    placeholder="Re-enter your new password"
                    autocomplete="new-password"
                    required
                    aria-required="true"
                    oninput="handlePasswordInputs()"
                    onblur="validateConfirmBlur()"
                    onkeyup="checkCapsLock(event, 'capsWarningConfirm')"
                    onkeydown="checkCapsLock(event, 'capsWarningConfirm')"
                  />
                  <button
                    type="button"
                    class="btn-eye-toggle"
                    id="toggleConfirmPassBtn"
                    onclick="togglePasswordVisibility('confirmPassword', this)"
                    aria-label="Show password"
                    title="Show password"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                      <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                  </button>
                </div>

                <div class="caps-warning" id="capsWarningConfirm" role="status" aria-live="polite">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m18 15-6-6-6 6"></path>
                  </svg>
                  <span id="capsWarningTextConfirm">Caps Lock is ON</span>
                </div>

                <!-- Match Feedback Indicator -->
                <div class="match-indicator" id="matchIndicator">
                  <span id="matchIcon"></span>
                  <span id="matchText"></span>
                </div>
              </div>

              <!-- Submit Button -->
              <button type="submit" id="btnSubmitReset" class="btn-submit" disabled>
                <span id="btnSubmitResetText">Update Password</span>
              </button>

              <div class="quiet-link-row">
                <a href="/sdn/pages/patient-login.php" class="quiet-link" id="linkBackToSign">
                  Back to Sign In
                </a>
              </div>
            </form>

            <!-- Success State Container (replaces form upon completion) -->
            <div id="resetSuccessState" style="display: none;">
              <div class="state-tile state-tile-success" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
              </div>
              <h3 style="font-size: 1.25rem; font-weight: 600; color: var(--color-text-strong); margin-bottom: 0.35rem;" id="successHeading">
                Password updated
              </h3>
              <p style="font-size: 0.86rem; line-height: 1.5; color: var(--color-text-muted); margin-bottom: 1.35rem;" id="successDesc">
                Your password has been reset successfully. You can now sign in to your account.
              </p>
              <a href="/sdn/pages/patient-login.php?reset_success=1" class="btn-submit" style="text-decoration: none;" id="btnGoToLogin">
                Sign In
              </a>
            </div>
          <?php endif; ?>
        </div>

        <!-- ── Footer ── -->
        <footer class="portal-footer" role="contentinfo">
          <p class="footer-dpa" id="footerDPA">
            Protected under the Data Privacy Act of 2012 (RA 10173)
          </p>
          <div class="footer-links">
            <a href="javascript:void(0)" onclick="alert('All patient data is encrypted and handled in strict accordance with Republic Act 10173.')">Privacy Policy</a>
            &middot;
            <a href="javascript:void(0)" onclick="alert('By accessing this portal, you agree to official MMWGH telehealth and patient consultation terms.')">Terms of Use</a>
          </div>
          <p class="footer-help" id="footerHelp">
            Need help? Call (047) 935-4138 &middot; Mon&ndash;Fri, 8:00 AM &ndash; 5:00 PM
          </p>
        </footer>
      </main>
    </div>

    <script>
      /* ── Localization Dictionary ── */
      let currentLang = 'en';

      const i18n = {
        en: {
          tagline: 'Maaasahang Lingkod',
          translation: 'Reliable Service',
          heading: 'Reset Password',
          subheading: 'Create a new secure password for your account.',
          accountPrefix: 'Account:',
          labelNewPass: 'New Password',
          placeholderNewPass: 'Enter at least 8 characters',
          labelConfirmPass: 'Confirm New Password',
          placeholderConfirmPass: 'Re-enter your new password',
          capsWarning: 'Caps Lock is ON',
          reqLength: 'At least 8 characters',
          matchSuccess: 'Passwords match',
          matchMismatch: 'Passwords do not match',
          btnUpdate: 'Update Password',
          btnUpdating: 'Updating…',
          backToSign: 'Back to Sign In',
          invalidDesc: 'This reset link is invalid or has expired.',
          btnRequestNew: 'Request a new link',
          successHeading: 'Password updated',
          successDesc: 'Your password has been reset successfully. You can now sign in to your account.',
          btnSignIn: 'Sign In',
          dpaText: 'Protected under the Data Privacy Act of 2012 (RA 10173)',
          helpText: 'Need help? Call (047) 935-4138 · Mon–Fri, 8:00 AM – 5:00 PM',
          serverError: 'Unable to connect to the server. Please try again.'
        },
        fil: {
          tagline: 'Maaasahang Lingkod',
          translation: 'Serbisyong Maaasahan',
          heading: 'I-reset ang Password',
          subheading: 'Gumawa ng bagong ligtas na password para sa iyong account.',
          accountPrefix: 'Account:',
          labelNewPass: 'Bagong Password',
          placeholderNewPass: 'Maglagay ng hindi bababa sa 8 karakter',
          labelConfirmPass: 'Kumpirmahin ang Bagong Password',
          placeholderConfirmPass: 'Muling ilagay ang bagong password',
          capsWarning: 'Naka-ON ang Caps Lock',
          reqLength: 'Hindi bababa sa 8 karakter',
          matchSuccess: 'Magkatugma ang password',
          matchMismatch: 'Hindi magkatugma ang password',
          btnUpdate: 'I-update ang Password',
          btnUpdating: 'Ina-update…',
          backToSign: 'Bumalik sa Sign In',
          invalidDesc: 'Ang link na ito para sa pag-reset ay hindi wasto o paso na.',
          btnRequestNew: 'Humiling ng bagong link',
          successHeading: 'Na-update na ang password',
          successDesc: 'Matagumpay na na-reset ang iyong password. Maaari ka nang mag-sign in.',
          btnSignIn: 'Mag-sign In',
          dpaText: 'Protektado sa ilalim ng Data Privacy Act ng 2012 (RA 10173)',
          helpText: 'Kailangan ng tulong? Tumawag sa (047) 935-4138 · Lunes–Biyernes, 8:00 AM – 5:00 PM',
          serverError: 'Hindi makakonekta sa server. Pakisubukan muli.'
        }
      };

      function setLanguage(lang) {
        currentLang = lang;
        const dict = i18n[lang];

        document.getElementById('btnLangEN').classList.toggle('active', lang === 'en');
        document.getElementById('btnLangFIL').classList.toggle('active', lang === 'fil');

        document.getElementById('leftTranslation').textContent = dict.translation;
        document.getElementById('uiPortalHeading').textContent = dict.heading;
        document.getElementById('uiPortalSubheading').textContent = dict.subheading;

        const accPrefixEl = document.getElementById('accountPrefix');
        if (accPrefixEl) accPrefixEl.textContent = dict.accountPrefix;

        const labelNew = document.getElementById('labelNewPass');
        if (labelNew) labelNew.textContent = dict.labelNewPass;
        const inputNew = document.getElementById('newPassword');
        if (inputNew) inputNew.placeholder = dict.placeholderNewPass;

        const labelConf = document.getElementById('labelConfirmPass');
        if (labelConf) labelConf.textContent = dict.labelConfirmPass;
        const inputConf = document.getElementById('confirmPassword');
        if (inputConf) inputConf.placeholder = dict.placeholderConfirmPass;

        const capsNew = document.getElementById('capsWarningTextNew');
        if (capsNew) capsNew.textContent = dict.capsWarning;
        const capsConf = document.getElementById('capsWarningTextConfirm');
        if (capsConf) capsConf.textContent = dict.capsWarning;

        const reqLen = document.getElementById('reqTextLength');
        if (reqLen) reqLen.textContent = dict.reqLength;

        const btnSub = document.getElementById('btnSubmitResetText');
        if (btnSub) btnSub.textContent = dict.btnUpdate;

        const backSign = document.getElementById('linkBackToSign');
        if (backSign) backSign.textContent = dict.backToSign;
        const backSignInv = document.getElementById('linkBackToSignInvalid');
        if (backSignInv) backSignInv.textContent = dict.backToSign;

        const btnReq = document.getElementById('btnRequestNew');
        if (btnReq) btnReq.textContent = dict.btnRequestNew;

        const succHead = document.getElementById('successHeading');
        if (succHead) succHead.textContent = dict.successHeading;
        const succDesc = document.getElementById('successDesc');
        if (succDesc) succDesc.textContent = dict.successDesc;
        const btnSign = document.getElementById('btnGoToLogin');
        if (btnSign) btnSign.textContent = dict.btnSignIn;

        document.getElementById('footerDPA').textContent = dict.dpaText;
        document.getElementById('footerHelp').textContent = dict.helpText;

        document.querySelectorAll('.bullet-text').forEach((el) => {
          el.textContent = el.getAttribute('data-' + lang) || el.textContent;
        });

        // Refresh match message if displayed
        handlePasswordInputs();
      }

      /* ── Input Validation & Live Checklist ── */
      const newPassInput = document.getElementById('newPassword');
      const confirmPassInput = document.getElementById('confirmPassword');
      const submitBtn = document.getElementById('btnSubmitReset');
      const resetErrorBanner = document.getElementById('resetErrorMessage');
      const resetErrorText = document.getElementById('resetErrorText');
      const matchIndicator = document.getElementById('matchIndicator');
      const matchIcon = document.getElementById('matchIcon');
      const matchText = document.getElementById('matchText');
      const strengthBar = document.getElementById('strengthBar');
      const reqLength = document.getElementById('reqLength');
      const reqDotLength = document.getElementById('reqDotLength');

      function checkCapsLock(e, targetId) {
        const capsOn = e.getModifierState && e.getModifierState('CapsLock');
        const el = document.getElementById(targetId);
        if (el) el.classList.toggle('visible', !!capsOn);
      }

      function togglePasswordVisibility(inputId, btn) {
        const input = document.getElementById(inputId);
        const svg = btn.querySelector('svg');
        const isPassword = input.type === 'password';

        input.type = isPassword ? 'text' : 'password';
        btn.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
        btn.setAttribute('title', isPassword ? 'Hide password' : 'Show password');

        if (isPassword) {
          svg.innerHTML = `
            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
            <line x1="1" y1="1" x2="23" y2="23"></line>
          `;
        } else {
          svg.innerHTML = `
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
            <circle cx="12" cy="12" r="3"></circle>
          `;
        }
      }

      function handlePasswordInputs() {
        if (!newPassInput || !confirmPassInput) return;

        if (resetErrorBanner.style.display !== 'none') {
          resetErrorBanner.style.display = 'none';
        }

        const passVal = newPassInput.value;
        const confVal = confirmPassInput.value;
        const dict = i18n[currentLang];

        // Length validation
        const hasLength = passVal.length >= 8;
        if (hasLength) {
          reqLength.classList.add('met');
          reqDotLength.innerHTML = `
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
          `;
        } else {
          reqLength.classList.remove('met');
          reqDotLength.innerHTML = `
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="4"></circle>
            </svg>
          `;
        }

        // Minimal strength meter
        strengthBar.className = 'strength-bar-fill';
        if (passVal.length === 0) {
          strengthBar.style.width = '0%';
        } else if (passVal.length < 8) {
          strengthBar.classList.add('weak');
        } else if (passVal.length >= 8 && /[A-Z]/.test(passVal) && /[0-9]/.test(passVal)) {
          strengthBar.classList.add('strong');
        } else {
          strengthBar.classList.add('medium');
        }

        // Match verification
        if (confVal.length > 0) {
          if (passVal === confVal) {
            matchIndicator.className = 'match-indicator matching';
            matchIcon.innerHTML = `
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"></polyline>
              </svg>
            `;
            matchText.textContent = dict.matchSuccess;
            confirmPassInput.classList.remove('is-invalid');
          } else {
            matchIndicator.className = 'match-indicator mismatch';
            matchIcon.innerHTML = `
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
              </svg>
            `;
            matchText.textContent = dict.matchMismatch;
          }
        } else {
          matchIndicator.className = 'match-indicator';
          confirmPassInput.classList.remove('is-invalid');
        }

        const isReady = hasLength && passVal === confVal;
        submitBtn.disabled = !isReady;
      }

      function validateConfirmBlur() {
        if (!confirmPassInput || !newPassInput) return;
        const passVal = newPassInput.value;
        const confVal = confirmPassInput.value;
        if (confVal.length > 0 && passVal !== confVal) {
          confirmPassInput.classList.add('is-invalid');
        } else {
          confirmPassInput.classList.remove('is-invalid');
        }
      }

      /* ── Submission Handler ── */
      async function handleResetSubmit(e) {
        e.preventDefault();
        const token = document.getElementById('resetTokenInput').value;
        const newPass = newPassInput.value;
        const confirmPass = confirmPassInput.value;

        if (newPass.length < 8 || newPass !== confirmPass) {
          handlePasswordInputs();
          return;
        }

        submitBtn.disabled = true;
        const originalText = document.getElementById('btnSubmitResetText').textContent;
        submitBtn.innerHTML = `
          <span class="spinner" aria-hidden="true"></span>
          <span>${i18n[currentLang].btnUpdating}</span>
        `;

        try {
          const res = await fetch('/sdn/api/patient_reset_password.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              token: token,
              new_password: newPass,
              confirm_password: confirmPass
            })
          });
          const data = await res.json();

          if (data.success) {
            document.getElementById('resetForm').style.display = 'none';
            document.getElementById('resetSuccessState').style.display = 'block';
          } else {
            showFormError(data.message || 'Failed to update password.');
          }
        } catch (err) {
          showFormError(i18n[currentLang].serverError);
        } finally {
          submitBtn.innerHTML = `<span id="btnSubmitResetText">${originalText}</span>`;
          handlePasswordInputs();
        }
      }

      function showFormError(message) {
        const formBox = document.querySelector('.portal-form-container');
        resetErrorText.textContent = message;
        resetErrorBanner.style.display = 'flex';
        formBox.classList.remove('shake');
        void formBox.offsetWidth;
        formBox.classList.add('shake');
        newPassInput.focus();
      }
    </script>
  </body>
</html>
