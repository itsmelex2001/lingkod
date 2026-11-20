<?php
session_start();
require_once __DIR__ . '/../api/config/db.php';
require_once __DIR__ . '/../api/config/patient_remember.php';

if (!empty($_SESSION['patient_logged_in']) && (!empty($_SESSION['patient_id']) || !empty($_SESSION['patient_email']))) {
    header('Location: /sdn/pages/patient-dashboard.php');
    exit;
}
if (isset($pdo) && tryAutoLoginPatientFromCookie($pdo)) {
    header('Location: /sdn/pages/patient-dashboard.php');
    exit;
}
$rememberedEmail = $_COOKIE[LINGKOD_REMEMBER_EMAIL_COOKIE] ?? '';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Patient Portal | MMWGH LINGKOD</title>
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
        caret-color: transparent;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        scrollbar-width: none;
        -ms-overflow-style: none;
      }

      input, textarea {
        caret-color: auto;
      }

      .brand-header,
      .portal-left,
      .portal-right-header,
      .portal-right-footer {
        user-select: none;
        -webkit-user-select: none;
      }

      ::-webkit-scrollbar {
        display: none;
      }

      /* ── Layout Grid ── */
      .portal-layout {
        display: grid;
        grid-template-columns: 1fr clamp(380px, 30vw, 460px);
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
        z-index: 1;
        background:
          linear-gradient(to top, rgba(4,32,14,0.95) 0%, rgba(4,32,14,0.70) 30%, rgba(4,32,14,0.30) 65%, rgba(4,32,14,0.45) 100%),
          linear-gradient(to right, rgba(4,32,14,0.65) 0%, rgba(4,32,14,0) 70%),
          rgba(10,70,30,0.30);
        box-shadow: inset 0 0 160px rgba(0, 0, 0, 0.35);
        pointer-events: none;
      }

      .portal-left-content {
        position: relative;
        z-index: 2;
        display: flex;
        flex-direction: column;
        height: 100%;
        min-height: 0;
        justify-content: space-between;
      }

      .portal-left-brand {
        display: flex;
        align-items: center;
        gap: 0.875rem;
        flex-shrink: 0;
      }

      .hospital-logo {
        width: 44px;
        height: 44px;
        object-fit: contain;
        background: transparent;
        padding: 0;
        flex-shrink: 0;
      }

      .hospital-meta {
        display: flex;
        flex-direction: column;
      }

      .hospital-acronym {
        font-size: 0.95rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        color: #ffffff;
        line-height: 1.15;
      }

      .hospital-fullname {
        font-size: 0.75rem;
        font-weight: 400;
        color: #e5e7eb;
        line-height: 1.35;
        max-width: 320px;
        margin-top: 2px;
      }

      .portal-left-body {
        margin-top: auto;
        margin-bottom: 0;
        padding: 0 0 clamp(0.5rem, 1vh, 1rem) 0;
        min-height: 0;
      }

      .portal-left-tagline {
        font-family: var(--font-sans);
        font-size: clamp(2.5rem, 3.8vw, 3.5rem);
        font-weight: 700;
        line-height: 1.12;
        color: #ffffff;
        letter-spacing: -0.025em;
      }

      .portal-left-translation {
        display: block;
        font-family: var(--font-sans);
        font-size: 0.9rem;
        font-weight: 400;
        color: #e5e7eb;
        margin-top: 0.65rem;
        letter-spacing: 0;
      }

      .portal-left-divider {
        width: 40px;
        height: 2px;
        background: rgba(255, 255, 255, 0.35);
        margin: 1.5rem 0;
      }

      .portal-bullet-list {
        list-style: none;
        display: flex;
        flex-direction: column;
        gap: clamp(0.65rem, 1.1vh, 0.9rem);
      }

      .portal-bullet-item {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        font-size: 0.875rem;
        line-height: 1.45;
        color: #e5e7eb;
      }

      .portal-bullet-icon {
        display: none;
      }

      /* ── Right Panel (Form) ── */
      .portal-right {
        position: relative;
        background-color: var(--color-surface-right);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        height: 100%;
        min-height: 0;
        padding: clamp(0.85rem, 1.8vh, 1.5rem) clamp(1.25rem, 3vw, 2.5rem);
        overflow-y: auto;
        overflow-x: hidden;
        scrollbar-width: none;
        -ms-overflow-style: none;
      }

      .portal-right::-webkit-scrollbar {
        display: none;
      }

      /* Top navigation row: Home link & Language switch */
      .portal-right-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        flex-shrink: 0;
      }

      .icon-btn-home {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.25rem 0.5rem;
        border-radius: 0.375rem;
        border: none;
        background: transparent;
        color: #6b7280;
        text-decoration: none;
        transition: background-color 0.15s ease, color 0.15s ease;
      }

      .icon-btn-home:hover {
        background-color: #f9fafb;
        color: #111827;
      }

      .icon-btn-home:focus-visible {
        outline: 2px solid #16a34a;
        outline-offset: 2px;
      }

      .icon-btn-home svg {
        width: 18px;
        height: 18px;
      }

      /* Language Toggle */
      .lang-toggle {
        display: inline-flex;
        align-items: center;
        gap: 2px;
      }

      .lang-btn {
        background: transparent;
        border: none;
        font-family: var(--font-sans);
        font-size: 0.75rem;
        font-weight: 500;
        color: #6b7280;
        padding: 0.25rem 0.5rem;
        border-radius: 0.375rem;
        cursor: pointer;
        transition: background-color 0.15s ease, color 0.15s ease;
      }

      .lang-btn:hover {
        background-color: #f9fafb;
        color: #111827;
      }

      .lang-btn.active {
        background-color: #f3f4f6;
        color: #15803d;
        font-weight: 600;
      }

      .lang-btn:focus-visible {
        outline: 2px solid #16a34a;
        outline-offset: 1px;
      }

      /* Form Container */
      .portal-form-container {
        width: 100%;
        max-width: 400px;
        margin: auto auto;
        min-height: 0;
        display: flex;
        flex-direction: column;
        justify-content: center;
      }

      .brand-header {
        margin-bottom: clamp(20px, 3vh, 28px);
        text-align: left;
      }

      .brand-logo-img {
        width: clamp(140px, 15vw, 180px);
        height: auto;
        display: block;
        object-fit: contain;
        margin: 0 0 clamp(16px, 2.2vh, 22px) 0;
        padding: 0;
      }

      .portal-heading {
        font-family: var(--font-sans);
        font-size: 1.5rem;
        font-weight: 600;
        color: #111827;
        letter-spacing: -0.025em;
        line-height: 2rem;
        margin: 0 0 4px 0;
      }

      .portal-subheading {
        font-size: 0.8rem;
        color: var(--color-text-muted);
        margin: 0;
        line-height: 1.5;
      }

      /* Banner Messages */
      .auth-alert {
        display: flex;
        align-items: flex-start;
        gap: 0.65rem;
        padding: clamp(0.4rem, 0.8vh, 0.6rem) 0.75rem;
        border-radius: var(--radius);
        font-size: 0.78rem;
        line-height: 1.35;
        margin-bottom: 14px;
      }

      .auth-alert-icon {
        flex-shrink: 0;
        width: 16px;
        height: 16px;
        margin-top: 1px;
      }

      .auth-alert-icon svg {
        width: 100%;
        height: 100%;
      }

      .auth-alert-error {
        background: var(--color-error-bg);
        border: 1px solid #fecaca;
        color: var(--color-error);
      }

      .auth-alert-success {
        background: var(--color-success-bg);
        border: 1px solid #bbf7d0;
        color: var(--color-success);
      }

      .auth-alert-warning {
        background: var(--color-warning-bg);
        border: 1px solid #fde68a;
        color: var(--color-warning);
      }

      /* Form Elements */
      #patientSignInForm {
        display: flex;
        flex-direction: column;
        gap: 18px;
      }

      .form-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-bottom: 0;
      }

      .form-label {
        display: block;
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--color-text-body);
        margin-bottom: 0;
        line-height: 1.2;
      }

      .input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
      }

      .input-icon-lead {
        position: absolute;
        left: 13px;
        width: 16px;
        height: 16px;
        color: #9ca3af;
        pointer-events: none;
        transition: color 0.15s ease;
      }

      .input-icon-lead svg {
        width: 100%;
        height: 100%;
      }

      .form-input {
        width: 100%;
        height: var(--control-height);
        padding: 0 40px 0 40px;
        border: none;
        border-radius: 0.5rem;
        background: #ffffff;
        box-shadow: inset 0 0 0 1px #d1d5db;
        font-family: var(--font-sans);
        font-size: 0.875rem;
        color: var(--color-text-strong);
        outline: none;
        transition: box-shadow 0.15s ease;
      }

      .form-input::placeholder {
        color: #9ca3af;
        font-weight: 400;
      }

      .form-input:hover {
        box-shadow: inset 0 0 0 1px #9ca3af;
      }

      .form-input:focus {
        box-shadow: inset 0 0 0 2px #16a34a;
      }

      .form-input.is-invalid {
        box-shadow: inset 0 0 0 1px var(--color-error);
        background-color: #fffbfb;
      }

      .form-input.is-invalid:focus {
        box-shadow: inset 0 0 0 2px var(--color-error);
      }

      .toggle-pass-btn {
        position: absolute;
        right: 11px;
        width: 26px;
        height: 26px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: transparent;
        border: none;
        color: #6b7280;
        cursor: pointer;
        padding: 0;
        border-radius: 4px;
        transition: color 0.15s ease;
      }

      .toggle-pass-btn:hover {
        color: var(--color-text-strong);
      }

      .toggle-pass-btn:focus-visible {
        outline: 2px solid var(--color-primary);
      }

      .toggle-pass-btn svg {
        width: 16px;
        height: 16px;
      }

      /* Helper & Validation Messages */
      .field-error-msg {
        display: none;
        font-size: 0.74rem;
        color: var(--color-error);
        margin: 0;
        line-height: 1.25;
        min-height: 0;
        transition: opacity 0.15s ease;
      }

      .field-error-msg.visible {
        display: block;
      }

      .caps-lock-warning {
        display: none;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.74rem;
        color: var(--color-warning);
        margin: 0;
        font-weight: 500;
      }

      .caps-lock-warning.visible {
        display: flex;
      }

      .caps-lock-warning svg {
        width: 13px;
        height: 13px;
        flex-shrink: 0;
      }

      /* Options Row (Remember Me & Forgot Password) */
      .form-options-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0;
        font-size: 0.78rem;
      }

      .remember-label {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        cursor: pointer;
        user-select: none;
        color: var(--color-text-body);
      }

      .remember-checkbox {
        width: 15px;
        height: 15px;
        accent-color: var(--color-primary);
        cursor: pointer;
        border-radius: 3px;
      }

      .forgot-link {
        color: #15803d;
        font-size: 0.875rem;
        line-height: 1.25rem;
        font-weight: 500;
        text-decoration: none;
        transition: color 0.15s ease;
      }

      .forgot-link:hover {
        color: #166534;
        text-decoration: underline;
      }

      .forgot-link:focus-visible {
        outline: 2px solid #16a34a;
        outline-offset: 2px;
      }

      /* Primary Sign In Button */
      .btn-submit {
        width: 100%;
        height: var(--control-height);
        border: none;
        border-radius: 0.5rem;
        background-color: #15803d;
        color: #ffffff;
        font-family: var(--font-sans);
        font-size: 0.875rem;
        font-weight: 500;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        transition: background-color 0.15s ease, opacity 0.15s ease;
        margin-top: 0;
      }

      .btn-submit:hover:not(:disabled) {
        background-color: #166534;
      }

      .btn-submit:active:not(:disabled) {
        background-color: #14532d;
      }

      .btn-submit:focus-visible {
        outline: 2px solid #16a34a;
        outline-offset: 2px;
      }

      .btn-submit:disabled {
        opacity: 0.65;
        cursor: not-allowed;
      }

      /* Secondary Registration Prompt */
      .new-patient-prompt {
        text-align: center;
        font-size: 0.875rem;
        line-height: 1.25rem;
        color: var(--color-text-muted);
        margin-top: 0;
      }

      .create-account-link {
        color: #15803d;
        font-size: 0.875rem;
        line-height: 1.25rem;
        font-weight: 500;
        text-decoration: none;
        margin-left: 0.25rem;
        transition: color 0.15s ease;
      }

      .create-account-link:hover {
        text-decoration: underline;
        color: #166534;
      }

      .create-account-link:focus-visible {
        outline: 2px solid var(--color-primary);
        outline-offset: 2px;
      }

      /* Right Footer */
      .portal-right-footer {
        width: 100%;
        max-width: 440px;
        margin: auto auto 0;
        text-align: center;
        font-size: 0.7rem;
        color: var(--color-text-muted);
        line-height: 1.4;
        border-top: 1px solid #f3f4f6;
        padding-top: clamp(0.5rem, 1vh, 0.85rem);
        flex-shrink: 0;
      }

      .footer-compliance {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        margin-bottom: 0.15rem;
      }

      .footer-compliance svg {
        width: 12px;
        height: 12px;
        color: var(--color-primary);
      }

      .footer-links {
        margin: 0.15rem 0;
      }

      .footer-links a {
        color: var(--color-text-body);
        text-decoration: underline;
        text-underline-offset: 2px;
      }

      .footer-links a:hover {
        color: var(--color-primary);
      }

      .footer-help {
        margin-top: 0.2rem;
        font-size: 0.68rem;
        color: #9ca3af;
      }

      /* ── Functional Animation (Error Shake) ── */
      @keyframes inputShake {
        0%, 100% { transform: translateX(0); }
        20%, 60% { transform: translateX(-6px); }
        40%, 80% { transform: translateX(6px); }
      }

      .shake {
        animation: inputShake 0.35s ease-in-out;
      }

      /* ── Spinner ── */
      .spinner {
        width: 16px;
        height: 16px;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-top-color: #ffffff;
        border-radius: 50%;
        animation: spin 0.7s linear infinite;
      }

      @keyframes spin {
        to { transform: rotate(360deg); }
      }

      /* ── Forgot Password Modal (matches Book Appointment style) ── */
      .modal-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(10, 25, 15, 0.55);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        padding: 1.25rem;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: opacity 180ms ease-out, visibility 180ms ease-out;
      }

      .modal-backdrop.is-active {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
      }

      .modal-card {
        position: relative;
        background: #ffffff;
        width: 100%;
        max-width: 420px;
        border-radius: 16px;
        border: 1px solid rgba(0, 0, 0, 0.05);
        box-shadow: 0 12px 32px rgba(0, 0, 0, 0.12);
        padding: 28px;
        transform: translateY(8px);
        transition: transform 180ms ease-out;
        overflow: hidden;
      }

      .modal-backdrop.is-active .modal-card {
        transform: translateY(0);
      }

      .modal-close-btn {
        position: absolute;
        top: 14px;
        right: 14px;
        width: 40px;
        height: 40px;
        border-radius: 8px;
        border: none;
        background: transparent;
        color: var(--color-text-muted);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background-color 0.15s ease, color 0.15s ease;
      }

      .modal-close-btn:hover {
        background-color: #f3f4f6;
        color: var(--color-text-strong);
      }

      .modal-close-btn:focus-visible {
        outline: 2px solid var(--color-primary);
        outline-offset: 2px;
      }

      .modal-icon-tile {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: #e8f5ec;
        color: var(--color-primary);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 12px;
      }

      .modal-title {
        font-size: 1.35rem;
        font-weight: 600;
        color: var(--color-text-strong);
        letter-spacing: -0.01em;
        line-height: 1.25;
        margin: 0;
      }

      .modal-desc {
        font-size: 0.84rem;
        color: var(--color-text-muted);
        line-height: 1.45;
        margin-top: 6px;
        margin-bottom: 1.25rem;
      }

      .modal-resend-row {
        margin-top: 1.25rem;
        text-align: center;
        display: flex;
        flex-direction: column;
        gap: 0.65rem;
        align-items: center;
      }

      .btn-resend-link {
        background: transparent;
        border: none;
        color: var(--color-primary);
        font-family: var(--font-sans);
        font-size: 0.82rem;
        font-weight: 600;
        cursor: pointer;
        text-decoration: underline;
        text-underline-offset: 2px;
        padding: 4px;
      }

      .btn-resend-link:hover:not(:disabled) {
        color: var(--color-primary-hover);
      }

      .btn-resend-link:disabled {
        color: var(--color-text-muted);
        text-decoration: none;
        cursor: not-allowed;
      }

      .btn-modal-back {
        font-size: 0.8rem;
        color: var(--color-text-muted);
        text-decoration: none;
        cursor: pointer;
      }

      .btn-modal-back:hover {
        color: var(--color-text-strong);
        text-decoration: underline;
      }

      @media (max-width: 600px) {
        .modal-backdrop {
          align-items: flex-end;
          padding: 0;
        }

        .modal-card {
          max-width: 100%;
          width: 100%;
          border-radius: 20px 20px 0 0;
          padding: 24px 20px 28px 20px;
          transform: translateY(100%);
        }

        .modal-backdrop.is-active .modal-card {
          transform: translateY(0);
        }
      }

      /* ── Responsive: Mobile / Tablet (< 820px) or Very Short Screens ── */
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
          font-size: 1.15rem;
        }

        .portal-left-translation {
          font-size: 0.8rem;
          margin-top: 0.2rem;
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
          margin: 0 0 20px 0;
        }

        .portal-heading {
          font-size: 1.4rem;
          margin-bottom: 7px;
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

      <!-- ── Right Panel (Form) ── -->
      <main class="portal-right" aria-label="Patient Sign In">
        <div class="portal-right-header">
          <a href="/sdn/index.php" class="icon-btn-home" aria-label="Return to hospital homepage" title="Home" onclick="try{sessionStorage.setItem('linkod_splash_shown','true')}catch(e){}">
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
            <h2 class="portal-heading" id="uiPortalHeading">Patient Portal</h2>
            <p class="portal-subheading" id="uiPortalSubheading">
              Sign in to manage your appointments and clinical records.
            </p>
          </header>

          <!-- System Notification Banner (URL alerts) -->
          <div id="systemAlertBanner" class="auth-alert" style="display: none;" role="status" aria-live="polite">
            <span class="auth-alert-icon" id="systemAlertIcon"></span>
            <span id="systemAlertText"></span>
          </div>

          <!-- Wrong Login / Generic Error Message -->
          <div id="formErrorMessage" class="auth-alert auth-alert-error" style="display: none;" role="alert" aria-live="assertive">
            <span class="auth-alert-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
              </svg>
            </span>
            <span id="formErrorText">Incorrect username, email, or password.</span>
          </div>

          <!-- Sign In Form -->
          <form id="patientSignInForm" novalidate onsubmit="handlePatientLogin(event)">
            <!-- Username Field -->
            <div class="form-group" id="groupEmail">
              <label for="patientEmail" class="form-label" id="labelEmail">Username or email</label>
              <div class="input-wrapper">
                <span class="input-icon-lead" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                  </svg>
                </span>
                <input
                  type="text"
                  id="patientEmail"
                  name="username"
                  class="form-input"
                  placeholder="Enter your username or email"
                  value="<?= htmlspecialchars($rememberedEmail) ?>"
                  autocomplete="username"
                  inputmode="text"
                  autocapitalize="none"
                  autocorrect="off"
                  spellcheck="false"
                  required
                  aria-required="true"
                  aria-describedby="emailError"
                  onblur="validateEmailField(true)"
                  oninput="handleFieldInput()"
                />
              </div>
              <p class="field-error-msg" id="emailError" role="alert" aria-live="polite">
                Enter your username or email.
              </p>
            </div>

            <!-- Password Field -->
            <div class="form-group" id="groupPassword">
              <label for="patientPassword" class="form-label" id="labelPassword">Password</label>
              <div class="input-wrapper">
                <span class="input-icon-lead" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                  </svg>
                </span>
                <input
                  type="password"
                  id="patientPassword"
                  name="password"
                  class="form-input"
                  placeholder="Enter your password"
                  autocomplete="current-password"
                  required
                  aria-required="true"
                  aria-describedby="passwordError capsWarning"
                  onblur="validatePasswordField(true)"
                  oninput="handleFieldInput()"
                  onkeyup="checkCapsLock(event)"
                  onkeydown="checkCapsLock(event)"
                />
                <button
                  type="button"
                  id="togglePasswordBtn"
                  class="toggle-pass-btn"
                  onclick="togglePasswordVisibility()"
                  aria-label="Show password"
                  title="Show password"
                >
                  <svg id="eyeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                  </svg>
                </button>
              </div>

              <!-- Caps Lock warning -->
              <p class="caps-lock-warning" id="capsWarning" role="status" aria-live="polite">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M12 9v4"></path>
                  <path d="M12 17h.01"></path>
                  <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                </svg>
                <span id="capsWarningText">Caps Lock is ON</span>
              </p>

              <!-- Inline validation error -->
              <p class="field-error-msg" id="passwordError" role="alert" aria-live="polite">
                Please enter your password.
              </p>
            </div>

            <!-- Options Row (Remember Me & Forgot Password) -->
            <div class="form-options-row">
              <label class="remember-label">
                <input type="checkbox" id="rememberMe" name="remember" class="remember-checkbox" <?= $rememberedEmail !== '' ? 'checked' : '' ?> />
                <span id="labelRemember">Remember me</span>
              </label>

              <a href="javascript:void(0)" class="forgot-link" id="linkForgot" onclick="openForgotModal()">
                Forgot password?
              </a>
            </div>

            <!-- Sign In Button -->
            <button type="submit" id="btnSubmitLogin" class="btn-submit">
              <span id="btnSubmitText">Sign In</span>
            </button>

            <!-- New Patient Link -->
            <div class="new-patient-prompt">
              <span id="textNewPatient">New patient?</span>
              <a href="/sdn/pages/register.php" class="create-account-link" id="linkCreateAccount">
                Create an account
              </a>
            </div>
          </form>

          <!-- Email OTP Verification Step (First Sign-In) -->
          <form id="otpVerifyView" style="display: none;" novalidate onsubmit="handleOtpVerify(event)">
            <p id="otpNoticeText" style="margin: 0 0 14px; font-size: 0.88rem; color: #374151; line-height: 1.5;"></p>
            <div class="form-group">
              <label for="patientOtpInput" class="form-label">6-Digit Verification Code</label>
              <input
                type="text"
                id="patientOtpInput"
                class="form-input"
                style="padding-left: 14px; letter-spacing: 0.35em; font-weight: 700; font-size: 1.15rem; text-align: center;"
                placeholder="000000"
                maxlength="6"
                inputmode="numeric"
                autocomplete="one-time-code"
                required
              />
              <p class="field-error-msg" id="otpErrorMsg" role="alert" aria-live="polite"></p>
            </div>
            <button type="submit" id="btnVerifyOtp" class="btn-submit" style="margin-bottom: 10px;">Verify &amp; Sign In</button>
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px; font-size: 0.84rem;">
              <button type="button" id="btnResendOtp" class="forgot-link" style="background: none; border: 0; padding: 6px 0; cursor: pointer;" onclick="resendPatientOtp()">Resend Code</button>
              <button type="button" class="forgot-link" style="background: none; border: 0; padding: 6px 0; cursor: pointer; color: #4b5563;" onclick="backToPasswordLogin()">Back to Sign In</button>
            </div>
          </form>
        </div>

        <!-- Footer -->
        <footer class="portal-right-footer">
          <div class="footer-compliance">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <rect x="3" y="11" width="18" height="11" rx="2"></rect>
              <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
            <span id="footerDPA">Protected under the Data Privacy Act of 2012 (RA 10173)</span>
          </div>

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

    <!-- ── Minimal Password Reset Modal ── -->
    <div id="forgotModal" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="modalForgotTitle">
      <div class="modal-card">
        <button type="button" class="modal-close-btn" onclick="closeForgotModal()" aria-label="Close dialog">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>

        <!-- View 1: Email Form -->
        <div id="forgotFormView">
          <div class="modal-icon-tile" aria-hidden="true">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
              <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
          </div>
          <h3 class="modal-title" id="modalForgotTitle">Reset Password</h3>
          <p class="modal-desc" id="modalForgotDesc">
            Enter your registered email and we'll send you a link to reset your password.
          </p>

          <form id="forgotPasswordForm" novalidate onsubmit="handleForgotSubmit(event)">
            <div class="form-group" style="margin-bottom: 1.25rem;">
              <label for="forgotEmailInput" class="form-label" id="labelForgotEmail">Registered Email</label>
              <div class="input-wrapper">
                <span class="input-icon-lead" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                    <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                  </svg>
                </span>
                <input
                  type="email"
                  id="forgotEmailInput"
                  class="form-input"
                  placeholder="Enter your email"
                  value="<?= htmlspecialchars($rememberedEmail) ?>"
                  autocomplete="email"
                  inputmode="email"
                  autocapitalize="none"
                  autocorrect="off"
                  spellcheck="false"
                  required
                  oninput="handleForgotInput()"
                />
              </div>
              <p class="field-error" id="forgotEmailError">Please enter a valid email address.</p>
            </div>

            <button type="submit" id="btnForgotSubmit" class="btn-submit">
              <span id="btnForgotSubmitText">Send reset link</span>
            </button>
          </form>
        </div>

        <!-- View 2: Success Confirmation with Resend Countdown -->
        <div id="forgotSuccessView" style="display: none;">
          <div class="modal-icon-tile" style="background: #e8f5ec; color: var(--color-primary);" aria-hidden="true">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
          </div>
          <h3 class="modal-title" id="forgotSuccessTitle">Check your email</h3>
          <p class="modal-desc" id="forgotSuccessDesc" style="margin-bottom: 1.5rem;">
            We sent a reset link to <strong id="sentEmailDisplay" style="color: var(--color-text-strong); font-weight: 600;"></strong>. If you don't see it, check your spam folder.
          </p>

          <div class="modal-resend-row">
            <button type="button" id="btnResendReset" class="btn-resend-link" disabled onclick="handleResendForgot()">
              Resend link in <span id="resendTimerCountdown">30</span>s
            </button>
            <a href="javascript:void(0)" class="btn-modal-back" onclick="closeForgotModal()" id="linkForgotBackToSign">
              Back to Sign In
            </a>
          </div>
        </div>
      </div>
    </div>

    <script>
      /* ── Localization Dictionary ── */
      let currentLang = 'en';

      const i18n = {
        en: {
          tagline: 'Maaasahang Lingkod',
          translation: 'Reliable Service',
          portalHeading: 'Patient Portal',
          portalSubheading: 'Sign in to manage your appointments and clinical records.',
          emailLabel: 'Username or email',
          emailPlaceholder: 'Enter your username or email',
          emailError: 'Enter your username or email.',
          passLabel: 'Password',
          passPlaceholder: 'Enter your password',
          passError: 'Please enter your password.',
          capsWarning: 'Caps Lock is ON',
          remember: 'Remember me',
          forgot: 'Forgot password?',
          signIn: 'Sign In',
          signingIn: 'Signing in…',
          newPatient: 'New patient?',
          createAccount: 'Create an account',
          incorrectAuth: 'Incorrect username, email, or password.',
          serverError: 'Unable to connect to the authentication server. Please try again.',
          unverified: 'Account not yet verified. Please check your email inbox.',
          dpaText: 'Protected under the Data Privacy Act of 2012 (RA 10173)',
          helpText: 'Need help? Call (047) 935-4138 · Mon–Fri, 8:00 AM – 5:00 PM',
          modalTitle: 'Reset Password',
          modalDesc: "Enter your registered email and we'll send you a link to reset your password.",
          modalSend: 'Send reset link',
          modalSending: 'Sending…',
          modalEmailError: 'Please enter a valid registered email.',
          modalSuccessTitle: 'Check your email',
          modalSuccessDescPrefix: 'We sent a reset link to ',
          modalSuccessDescSuffix: ". If you don't see it, check your spam folder.",
          modalResendPrefix: 'Resend link in ',
          modalResendReady: 'Resend link',
          modalBackToSign: 'Back to Sign In'
        },
        fil: {
          tagline: 'Maaasahang Lingkod',
          translation: 'Serbisyong Maaasahan',
          portalHeading: 'Patient Portal',
          portalSubheading: 'Mag-sign in upang pamahalaan ang iyong appointment at talaan sa ospital.',
          emailLabel: 'Username o email',
          emailPlaceholder: 'Ilagay ang username o email',
          emailError: 'Ilagay ang iyong username o email.',
          passLabel: 'Password',
          passPlaceholder: 'Ilagay ang iyong password',
          passError: 'Pakilagay ang iyong password.',
          capsWarning: 'Naka-ON ang Caps Lock',
          remember: 'Tandaan ako',
          forgot: 'Nakalimutan ang password?',
          signIn: 'Mag-sign In',
          signingIn: 'Nagla-log in…',
          newPatient: 'Bagong pasyente?',
          createAccount: 'Gumawa ng account',
          incorrectAuth: 'Maling username, email, o password.',
          serverError: 'Hindi makakonekta sa authentication server. Pakisubukan muli.',
          unverified: 'Hindi pa beripikado ang account. Pakitingnan ang iyong email.',
          dpaText: 'Protektado sa ilalim ng Data Privacy Act ng 2012 (RA 10173)',
          helpText: 'Kailangan ng tulong? Tumawag sa (047) 935-4138 · Lunes–Biyernes, 8:00 AM – 5:00 PM',
          modalTitle: 'I-reset ang Password',
          modalDesc: 'Ilagay ang iyong rehistradong email at magpapadala kami ng link para ma-reset ang password.',
          modalSend: 'Ipadala ang reset link',
          modalSending: 'Ipinapadala…',
          modalEmailError: 'Maglagay ng wastong rehistradong email.',
          modalSuccessTitle: 'Suriin ang iyong email',
          modalSuccessDescPrefix: 'Nagpadala kami ng reset link sa ',
          modalSuccessDescSuffix: '. Kung hindi mo ito makita, suriin ang spam folder.',
          modalResendPrefix: 'Muling ipadala sa ',
          modalResendReady: 'Muling ipadala ang link',
          modalBackToSign: 'Bumalik sa Sign In'
        }
      };

      function setLanguage(lang) {
        currentLang = lang;
        const dict = i18n[lang];

        document.getElementById('btnLangEN').classList.toggle('active', lang === 'en');
        document.getElementById('btnLangFIL').classList.toggle('active', lang === 'fil');

        document.getElementById('leftTranslation').textContent = dict.translation;
        document.getElementById('uiPortalHeading').textContent = dict.portalHeading;
        document.getElementById('uiPortalSubheading').textContent = dict.portalSubheading;
        document.getElementById('labelEmail').textContent = dict.emailLabel;
        document.getElementById('patientEmail').placeholder = dict.emailPlaceholder;
        document.getElementById('emailError').textContent = dict.emailError;
        document.getElementById('labelPassword').textContent = dict.passLabel;
        document.getElementById('patientPassword').placeholder = dict.passPlaceholder;
        document.getElementById('passwordError').textContent = dict.passError;
        document.getElementById('capsWarningText').textContent = dict.capsWarning;
        document.getElementById('labelRemember').textContent = dict.remember;
        document.getElementById('linkForgot').textContent = dict.forgot;
        document.getElementById('btnSubmitText').textContent = dict.signIn;
        document.getElementById('textNewPatient').textContent = dict.newPatient;
        document.getElementById('linkCreateAccount').textContent = dict.createAccount;
        document.getElementById('formErrorText').textContent = dict.incorrectAuth;
        document.getElementById('footerDPA').textContent = dict.dpaText;
        document.getElementById('footerHelp').textContent = dict.helpText;
        document.getElementById('modalForgotTitle').textContent = dict.modalTitle;
        document.getElementById('modalForgotDesc').textContent = dict.modalDesc;
        document.getElementById('btnForgotSubmitText').textContent = dict.modalSend;
        const succTitle = document.getElementById('forgotSuccessTitle');
        if (succTitle) succTitle.textContent = dict.modalSuccessTitle;
        const backSign = document.getElementById('linkForgotBackToSign');
        if (backSign) backSign.textContent = dict.modalBackToSign;
        updateModalSuccessDesc();
        updateResendButtonText();

        document.querySelectorAll('.bullet-text').forEach((el) => {
          el.textContent = el.getAttribute('data-' + lang) || el.textContent;
        });
      }

      /* ── Input Validation & Button Readiness ── */
      const emailInput = document.getElementById('patientEmail');
      const passwordInput = document.getElementById('patientPassword');
      const submitBtn = document.getElementById('btnSubmitLogin');
      const emailError = document.getElementById('emailError');
      const passwordError = document.getElementById('passwordError');
      const formError = document.getElementById('formErrorMessage');

      function isValidEmail(val) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val.trim());
      }

      function validateEmailField(showError) {
        const val = emailInput.value.trim();
        const valid = val.length >= 3;

        if (!valid && showError) {
          emailInput.classList.add('is-invalid');
          emailError.classList.add('visible');
        } else {
          emailInput.classList.remove('is-invalid');
          emailError.classList.remove('visible');
        }
        updateSubmitState();
        return valid;
      }

      function validatePasswordField(showError) {
        const val = passwordInput.value;
        const valid = val.length > 0;

        if (!valid && showError) {
          passwordInput.classList.add('is-invalid');
          passwordError.classList.add('visible');
        } else {
          passwordInput.classList.remove('is-invalid');
          passwordError.classList.remove('visible');
        }
        updateSubmitState();
        return valid;
      }

      function handleFieldInput() {
        if (formError.style.display !== 'none') {
          formError.style.display = 'none';
        }
        if (emailInput.classList.contains('is-invalid') && emailInput.value.trim().length >= 3) {
          emailInput.classList.remove('is-invalid');
          emailError.classList.remove('visible');
        }
        if (passwordInput.classList.contains('is-invalid') && passwordInput.value.length > 0) {
          passwordInput.classList.remove('is-invalid');
          passwordError.classList.remove('visible');
        }
      }

      function updateSubmitState() {
        submitBtn.disabled = false;
      }

      function checkCapsLock(e) {
        const capsOn = e.getModifierState && e.getModifierState('CapsLock');
        document.getElementById('capsWarning').classList.toggle('visible', !!capsOn);
      }

      function togglePasswordVisibility() {
        const btn = document.getElementById('togglePasswordBtn');
        const eyeIcon = document.getElementById('eyeIcon');
        const isPassword = passwordInput.type === 'password';

        passwordInput.type = isPassword ? 'text' : 'password';
        btn.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
        btn.setAttribute('title', isPassword ? 'Hide password' : 'Show password');

        if (isPassword) {
          eyeIcon.innerHTML = `
            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
            <line x1="1" y1="1" x2="23" y2="23"></line>
          `;
        } else {
          eyeIcon.innerHTML = `
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
            <circle cx="12" cy="12" r="3"></circle>
          `;
        }
      }

      /* ── Form Submission ── */
      async function handlePatientLogin(e) {
        e.preventDefault();

        const emailValid = validateEmailField(true);
        const passValid = validatePasswordField(true);
        if (!emailValid || !passValid) return;

        const email = emailInput.value.trim();
        const password = passwordInput.value;
        const remember = document.getElementById('rememberMe').checked;

        // Loading state
        submitBtn.disabled = true;
        const originalText = document.getElementById('btnSubmitText').textContent;
        submitBtn.innerHTML = `
          <span class="spinner" aria-hidden="true"></span>
          <span>${i18n[currentLang].signingIn}</span>
        `;

        try {
          const res = await fetch('/sdn/api/patient_auth.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username: email, password, remember })
          });
          const data = await res.json();

          if (data.success) {
            window.location.href = '/sdn/pages/patient-dashboard.php';
          } else if (data.otp_required) {
            window.location.href = data.redirect || '/sdn/pages/verify-otp.php';
            return;
          } else {
            // Trigger failure shake & inline feedback
            triggerLoginError(data.message || (data.unverified ? i18n[currentLang].unverified : i18n[currentLang].incorrectAuth));
          }
        } catch (err) {
          triggerLoginError(i18n[currentLang].serverError);
        } finally {
          submitBtn.innerHTML = `<span id="btnSubmitText">${originalText}</span>`;
          updateSubmitState();
        }
      }

      function backToPasswordLogin() {
        document.getElementById('otpVerifyView').style.display = 'none';
        document.getElementById('patientSignInForm').style.display = 'block';
        passwordInput.focus();
      }

      async function handleOtpVerify(e) {
        e.preventDefault();
        const otpInp = document.getElementById('patientOtpInput');
        const otpErr = document.getElementById('otpErrorMsg');
        const btn = document.getElementById('btnVerifyOtp');
        const otp = otpInp.value.replace(/\D/g, '');
        if (otp.length !== 6) {
          otpErr.textContent = 'Enter the 6-digit verification code.';
          otpErr.style.display = 'block';
          otpInp.focus();
          return;
        }
        otpErr.style.display = 'none';
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner" aria-hidden="true"></span><span>Verifying...</span>';
        try {
          const res = await fetch('/sdn/api/patient_verify_otp.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'verify', otp })
          });
          const data = await res.json();
          if (data.success) {
            window.location.href = data.redirect || '/sdn/pages/patient-dashboard.php';
          } else {
            otpErr.textContent = data.message || 'Invalid verification code.';
            otpErr.style.display = 'block';
            otpInp.focus();
          }
        } catch (err) {
          otpErr.textContent = 'Unable to verify code. Please try again.';
          otpErr.style.display = 'block';
        } finally {
          btn.disabled = false;
          btn.textContent = 'Verify & Sign In';
        }
      }

      async function resendPatientOtp() {
        const otpErr = document.getElementById('otpErrorMsg');
        const notice = document.getElementById('otpNoticeText');
        const btn = document.getElementById('btnResendOtp');
        btn.disabled = true;
        try {
          const res = await fetch('/sdn/api/patient_verify_otp.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'resend' })
          });
          const data = await res.json();
          if (data.success) {
            otpErr.style.display = 'none';
            notice.textContent = data.message;
          } else {
            otpErr.textContent = data.message || 'Could not resend code.';
            otpErr.style.display = 'block';
          }
        } catch (err) {
          otpErr.textContent = 'Network error while resending code.';
          otpErr.style.display = 'block';
        } finally {
          setTimeout(() => { btn.disabled = false; }, 3000);
        }
      }

      function triggerLoginError(message) {
        const formBox = document.querySelector('.portal-form-container');
        document.getElementById('formErrorText').textContent = message;
        formError.style.display = 'flex';

        emailInput.classList.add('is-invalid');
        passwordInput.classList.add('is-invalid');

        formBox.classList.remove('shake');
        void formBox.offsetWidth; // re-trigger animation
        formBox.classList.add('shake');
        passwordInput.focus();
      }

      /* ── Minimal Forgot Password Modal with Accessible Trap & Timer ── */
      let lastFocusedElement = null;
      let resendTimer = null;
      let resendCountdown = 30;
      let lastSentEmail = '';

      function openForgotModal() {
        lastFocusedElement = document.activeElement;
        const modal = document.getElementById('forgotModal');
        const input = document.getElementById('forgotEmailInput');

        // Reset to form view
        document.getElementById('forgotFormView').style.display = 'block';
        document.getElementById('forgotSuccessView').style.display = 'none';
        input.classList.remove('is-invalid');
        document.getElementById('forgotEmailError').classList.remove('visible');

        if (emailInput.value && !input.value) {
          input.value = emailInput.value.trim();
        }

        modal.classList.add('is-active');
        document.body.style.overflow = 'hidden';
        setTimeout(() => input.focus(), 80);
      }

      function closeForgotModal() {
        const modal = document.getElementById('forgotModal');
        modal.classList.remove('is-active');
        document.body.style.overflow = '';
        if (resendTimer) {
          clearInterval(resendTimer);
          resendTimer = null;
        }
        if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') {
          lastFocusedElement.focus();
        }
      }

      function handleForgotInput() {
        const input = document.getElementById('forgotEmailInput');
        const err = document.getElementById('forgotEmailError');
        if (input.classList.contains('is-invalid') && isValidEmail(input.value)) {
          input.classList.remove('is-invalid');
          err.classList.remove('visible');
        }
      }

      function updateModalSuccessDesc() {
        const descEl = document.getElementById('forgotSuccessDesc');
        if (!descEl) return;
        const dict = i18n[currentLang];
        descEl.innerHTML = `${dict.modalSuccessDescPrefix}<strong id="sentEmailDisplay" style="color: var(--color-text-strong); font-weight: 600;">${escapeHtml(lastSentEmail)}</strong>${dict.modalSuccessDescSuffix}`;
      }

      function updateResendButtonText() {
        const btn = document.getElementById('btnResendReset');
        if (!btn) return;
        const dict = i18n[currentLang];
        if (btn.disabled) {
          btn.textContent = `${dict.modalResendPrefix}${resendCountdown}s`;
        } else {
          btn.textContent = dict.modalResendReady;
        }
      }

      function startResendCountdown() {
        if (resendTimer) clearInterval(resendTimer);
        resendCountdown = 30;
        const btn = document.getElementById('btnResendReset');
        btn.disabled = true;
        updateResendButtonText();

        resendTimer = setInterval(() => {
          resendCountdown--;
          if (resendCountdown <= 0) {
            clearInterval(resendTimer);
            resendTimer = null;
            btn.disabled = false;
            updateResendButtonText();
          } else {
            updateResendButtonText();
          }
        }, 1000);
      }

      function escapeHtml(str) {
        return (str || '').replace(/[&<>"']/g, (m) => ({
          '&': '&amp;',
          '<': '&lt;',
          '>': '&gt;',
          '"': '&quot;',
          "'": '&#039;'
        })[m]);
      }

      async function executeForgotRequest(email) {
        const btn = document.getElementById('btnForgotSubmit');
        const input = document.getElementById('forgotEmailInput');
        const err = document.getElementById('forgotEmailError');

        btn.disabled = true;
        btn.innerHTML = `<span class="spinner" aria-hidden="true"></span> <span>${i18n[currentLang].modalSending}</span>`;

        try {
          const res = await fetch('/sdn/api/patient_forgot_password.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email })
          });
          const data = await res.json();

          if (data.success) {
            lastSentEmail = email;
            document.getElementById('forgotFormView').style.display = 'none';
            document.getElementById('forgotSuccessView').style.display = 'block';
            updateModalSuccessDesc();
            startResendCountdown();
          } else {
            input.classList.add('is-invalid');
            err.textContent = data.message || i18n[currentLang].modalEmailError;
            err.classList.add('visible');
            input.focus();
          }
        } catch (error) {
          input.classList.add('is-invalid');
          err.textContent = i18n[currentLang].serverError;
          err.classList.add('visible');
        } finally {
          btn.disabled = false;
          btn.innerHTML = `<span id="btnForgotSubmitText">${i18n[currentLang].modalSend}</span>`;
        }
      }

      function handleForgotSubmit(e) {
        e.preventDefault();
        const input = document.getElementById('forgotEmailInput');
        const err = document.getElementById('forgotEmailError');
        const val = input.value.trim();

        if (!isValidEmail(val)) {
          input.classList.add('is-invalid');
          err.textContent = i18n[currentLang].modalEmailError;
          err.classList.add('visible');
          input.focus();
          return;
        }

        executeForgotRequest(val);
      }

      function handleResendForgot() {
        if (!lastSentEmail) return;
        executeForgotRequest(lastSentEmail);
      }

      // Keyboard Trap & Escape Handling
      document.addEventListener('keydown', (e) => {
        const modal = document.getElementById('forgotModal');
        if (!modal.classList.contains('is-active')) return;

        if (e.key === 'Escape') {
          e.preventDefault();
          closeForgotModal();
          return;
        }

        if (e.key === 'Tab') {
          const focusables = modal.querySelectorAll('button:not([disabled]), [href], input:not([disabled]), [tabindex]:not([tabindex="-1"])');
          if (!focusables.length) return;
          const first = focusables[0];
          const last = focusables[focusables.length - 1];

          if (e.shiftKey) {
            if (document.activeElement === first) {
              e.preventDefault();
              last.focus();
            }
          } else {
            if (document.activeElement === last) {
              e.preventDefault();
              first.focus();
            }
          }
        }
      });

      /* ── URL Parameter State Handlers ── */
      window.addEventListener('DOMContentLoaded', () => {
        const params = new URLSearchParams(window.location.search);
        const banner = document.getElementById('systemAlertBanner');
        const text = document.getElementById('systemAlertText');
        const icon = document.getElementById('systemAlertIcon');

        if (params.get('verified') === '1') {
          banner.className = 'auth-alert auth-alert-success';
          icon.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>`;
          text.textContent = 'Your account is verified! Please sign in with your credentials.';
          banner.style.display = 'flex';
        } else if (params.get('registered') === '1') {
          banner.className = 'auth-alert auth-alert-success';
          icon.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>`;
          text.textContent = 'Registration submitted! Please verify your email before signing in.';
          banner.style.display = 'flex';
        } else if (params.get('verify_error')) {
          banner.className = 'auth-alert auth-alert-warning';
          icon.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>`;
          text.textContent = 'This verification link is invalid or has expired.';
          banner.style.display = 'flex';
        } else if (params.get('reset_success') === '1') {
          banner.className = 'auth-alert auth-alert-success';
          icon.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>`;
          text.textContent = 'Your password has been updated. Please sign in with your new password.';
          banner.style.display = 'flex';
        }

        if (params.get('action') === 'forgot') {
          openForgotModal();
        }
      });

      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
          closeForgotModal();
        }
      });
    </script>
  </body>
</html>
