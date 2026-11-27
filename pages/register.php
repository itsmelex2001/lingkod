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
  <link rel="stylesheet" href="/sdn/public/css/global.css?v=20261128">
  <link rel="stylesheet" href="/sdn/public/css/navigation.css?v=20261128">
  <style>
    :root {
      --bg-canvas: #f8fafc;
      --surface: #ffffff;
      --surface-subtle: #f8fafc;
      --border: #e2e8f0;
      --border-strong: #cbd5e1;
      --text-primary: #0f172a;
      --text-secondary: #475569;
      --text-muted: #64748b;
      --brand: #15803d;
      --brand-hover: #166534;
      --brand-accent: #16a34a;
      --brand-soft: #f0fdf4;
      --brand-ring: rgba(22, 163, 74, 0.16);
      --danger: #dc2626;
      --danger-soft: #fef2f2;
      --danger-border: #fecaca;
      --amber: #b45309;
      --amber-soft: #fffbeb;
      --amber-border: #fde68a;
      --radius-sm: 8px;
      --radius-md: 12px;
      --radius-lg: 16px;
      --shadow-card: 0 1px 3px rgba(15, 23, 42, 0.04), 0 12px 32px -12px rgba(15, 23, 42, 0.08);
    }

    *, *::before, *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    html, body {
      min-height: 100dvh;
      background-color: var(--bg-canvas);
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      color: var(--text-primary);
      display: flex;
      flex-direction: column;
      -webkit-font-smoothing: antialiased;
    }

    #new-patient-registration-app {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 28px 16px 40px;
    }

    .main-wrapper {
      width: 100%;
      max-width: 780px;
      margin: 0 auto;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    /* ── 3-Step Stepper ── */
    .stepper-container {
      width: 100%;
      max-width: 640px;
      margin-bottom: 22px;
    }

    .stepper-track {
      position: relative;
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      align-items: start;
    }

    .stepper-line {
      position: absolute;
      top: 17px;
      left: 16.666%;
      right: 16.666%;
      height: 2px;
      background-color: var(--border);
      border-radius: 999px;
      z-index: 1;
      overflow: hidden;
    }

    .stepper-line-fill {
      height: 100%;
      width: 0%;
      background-color: var(--brand-accent);
      border-radius: 999px;
      transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .step-node {
      position: relative;
      z-index: 2;
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
      background: transparent;
      border: 0;
      padding: 0 6px;
      font-family: inherit;
      cursor: default;
    }

    .step-node.completed {
      cursor: pointer;
    }

    .step-circle {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background-color: var(--surface);
      border: 1.5px solid var(--border-strong);
      color: var(--text-muted);
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 0.875rem;
      transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease, box-shadow 0.2s ease, transform 0.15s ease;
    }

    .step-node.current .step-circle {
      background-color: var(--surface);
      border: 2px solid var(--brand-accent);
      color: var(--brand);
      box-shadow: 0 0 0 4px var(--brand-ring);
    }

    .step-node.completed .step-circle {
      background-color: var(--brand);
      border-color: var(--brand);
      color: #ffffff;
    }

    .step-node.completed:hover .step-circle,
    .step-node.completed:focus-visible .step-circle {
      background-color: var(--brand-hover);
      transform: scale(1.06);
    }

    .step-node:focus-visible {
      outline: 2px solid var(--brand);
      outline-offset: 4px;
      border-radius: 10px;
    }

    .step-text {
      margin-top: 8px;
      font-size: 0.78rem;
      font-weight: 500;
      color: var(--text-muted);
      line-height: 1.25;
      transition: color 0.2s ease;
    }

    .step-node.current .step-text,
    .step-node.completed .step-text {
      color: var(--text-primary);
      font-weight: 600;
    }

    /* Mobile Percentage Status */
    .mobile-progress {
      display: none;
      flex-direction: column;
      gap: 8px;
      width: 100%;
    }

    .mobile-progress-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
    }

    .mobile-progress-meta {
      display: flex;
      flex-direction: column;
      gap: 2px;
      min-width: 0;
    }

    .mobile-progress-step {
      font-size: 0.8125rem;
      font-weight: 700;
      color: var(--text-primary);
      line-height: 1.25;
    }

    .mobile-progress-next {
      font-size: 0.75rem;
      font-weight: 500;
      color: var(--text-secondary);
      line-height: 1.25;
    }

    .mobile-progress-pct {
      flex-shrink: 0;
      padding: 3px 10px;
      border-radius: 999px;
      background: var(--brand-soft);
      border: 1px solid #bbf7d0;
      color: var(--brand);
      font-size: 0.8125rem;
      font-weight: 700;
      font-variant-numeric: tabular-nums;
      line-height: 1.3;
    }

    .mobile-progress-bar {
      width: 100%;
      height: 8px;
      background: var(--border);
      border-radius: 999px;
      overflow: hidden;
    }

    .mobile-progress-fill {
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, var(--brand-accent), var(--brand));
      border-radius: 999px;
      transform-origin: left center;
      transform: scaleX(0.3333);
      transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* ── Main Wizard Card ── */
    .card-wizard {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 28px 32px 24px;
      box-shadow: var(--shadow-card);
      width: 100%;
    }

    .phase-header {
      margin-bottom: 20px;
      padding-bottom: 14px;
      border-bottom: 1px solid #f1f5f9;
    }

    .card-heading {
      font-family: 'Outfit', 'Inter', sans-serif;
      font-size: 1.35rem;
      font-weight: 700;
      color: var(--text-primary);
      letter-spacing: -0.02em;
      line-height: 1.25;
    }

    .card-subheading {
      margin-top: 4px;
      font-size: 0.85rem;
      color: var(--text-secondary);
      line-height: 1.45;
    }

    .wizard-phase {
      display: none;
    }

    .wizard-phase.active {
      display: block;
      animation: phaseIn 0.22s cubic-bezier(0.16, 1, 0.3, 1);
    }

    @keyframes phaseIn {
      from { opacity: 0; transform: translateY(6px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @media (prefers-reduced-motion: reduce) {
      .wizard-phase.active { animation: none; }
      .stepper-line-fill, .mobile-progress-fill, .step-circle, .input-ctrl, .btn-pill-next, .btn-pill-back { transition: none; }
    }

    /* ── Form Layout & Controls ── */
    .form-stack {
      display: flex;
      flex-direction: column;
      gap: 14px;
    }

    .grid-3 {
      display: grid;
      grid-template-columns: 1fr 1fr 112px;
      gap: 14px;
    }

    .grid-2 {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 14px;
    }

    .grid-contact {
      display: grid;
      grid-template-columns: 1fr 1.25fr;
      gap: 14px;
    }

    .field-group {
      display: flex;
      flex-direction: column;
      gap: 5px;
      min-width: 0;
    }

    .field-group label {
      font-size: 0.8125rem;
      font-weight: 600;
      color: #334155;
      line-height: 1.2;
    }

    .req-star {
      color: var(--danger);
      margin-left: 2px;
    }

    .input-ctrl {
      width: 100%;
      height: 40px;
      padding: 0 12px;
      border: 1px solid var(--border-strong);
      border-radius: var(--radius-sm);
      font-size: 0.875rem;
      font-family: inherit;
      color: var(--text-primary);
      background: var(--surface);
      outline: none;
      transition: border-color 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease;
    }

    .input-ctrl::placeholder {
      color: #94a3b8;
    }

    .input-ctrl:hover:not(:disabled):not([readonly]) {
      border-color: #94a3b8;
    }

    .input-ctrl:focus {
      border-color: var(--brand-accent);
      box-shadow: 0 0 0 3px var(--brand-ring);
    }

    .input-readonly {
      background-color: #f8fafc;
      color: var(--text-secondary);
      border-color: var(--border);
      cursor: default;
    }

    .input-readonly:focus {
      border-color: var(--border-strong);
      box-shadow: none;
    }

    .input-ctrl.invalid,
    .contact-wrapper.invalid {
      border-color: var(--danger) !important;
      background-color: #fffafa;
    }

    .input-ctrl.invalid:focus,
    .contact-wrapper.invalid:focus-within {
      box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.14);
    }

    .field-hint {
      font-size: 0.75rem;
      color: var(--text-muted);
      line-height: 1.35;
    }

    .error-msg {
      font-size: 0.75rem;
      color: var(--danger);
      font-weight: 500;
      display: none;
      line-height: 1.3;
    }

    /* Contact Input */
    .contact-wrapper {
      display: flex;
      align-items: center;
      height: 40px;
      border: 1px solid var(--border-strong);
      border-radius: var(--radius-sm);
      overflow: hidden;
      background: var(--surface);
      transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .contact-wrapper:hover {
      border-color: #94a3b8;
    }

    .contact-wrapper:focus-within {
      border-color: var(--brand-accent);
      box-shadow: 0 0 0 3px var(--brand-ring);
    }

    .prefix-badge {
      padding: 0 12px;
      background: #f8fafc;
      font-weight: 600;
      font-size: 0.875rem;
      color: #334155;
      border-right: 1px solid var(--border);
      user-select: none;
      height: 100%;
      display: flex;
      align-items: center;
    }

    .contact-wrapper input {
      border: 0;
      background: transparent;
      flex: 1;
      min-width: 0;
      padding: 0 12px;
      outline: none;
      font-size: 0.875rem;
      height: 100%;
      color: var(--text-primary);
      font-family: inherit;
    }

    /* Guardian Banner */
    .guardian-box {
      display: none;
      background: var(--amber-soft);
      border: 1px solid var(--amber-border);
      border-radius: var(--radius-md);
      padding: 14px 16px;
    }

    .guardian-head {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 0.8rem;
      font-weight: 600;
      color: #92400e;
      margin-bottom: 10px;
      line-height: 1.35;
    }

    /* Radio Group */
    .radio-row {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
      margin-top: 2px;
    }

    .radio-lbl {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 8px 16px;
      border: 1px solid var(--border-strong);
      border-radius: var(--radius-sm);
      cursor: pointer;
      font-size: 0.875rem;
      font-weight: 500;
      color: var(--text-primary);
      background: var(--surface);
      transition: border-color 0.15s ease, background-color 0.15s ease;
    }

    .radio-lbl:has(input:checked) {
      border-color: var(--brand-accent);
      background: var(--brand-soft);
      color: var(--brand-hover);
      font-weight: 600;
    }

    .radio-lbl input[type="radio"] {
      accent-color: var(--brand);
      width: 16px;
      height: 16px;
      cursor: pointer;
    }

    /* ── Step 2: Account Registration Controls ── */
    .email-ref-banner {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      padding: 11px 14px;
      background: var(--surface-subtle);
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      font-size: 0.8125rem;
      color: var(--text-secondary);
    }

    .email-ref-left {
      display: flex;
      align-items: center;
      gap: 10px;
      min-width: 0;
    }

    .email-ref-icon {
      width: 32px;
      height: 32px;
      border-radius: 8px;
      background: var(--brand-soft);
      color: var(--brand);
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .email-ref-addr {
      font-weight: 600;
      color: var(--text-primary);
      overflow-wrap: anywhere;
    }

    .password-wrapper {
      position: relative;
      display: flex;
      align-items: center;
    }

    .password-wrapper .input-ctrl {
      padding-right: 42px;
    }

    .btn-eye {
      position: absolute;
      right: 5px;
      top: 50%;
      transform: translateY(-50%);
      width: 32px;
      height: 32px;
      background: transparent;
      border: 0;
      color: var(--text-muted);
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 6px;
      transition: color 0.15s ease, background-color 0.15s ease;
    }

    .btn-eye:hover {
      color: var(--brand);
      background-color: var(--brand-soft);
    }

    .btn-eye:focus-visible {
      outline: 2px solid var(--brand);
      outline-offset: 1px;
    }

    /* Password Strength Meter */
    .strength-wrap {
      margin-top: 6px;
      display: flex;
      flex-direction: column;
      gap: 6px;
    }

    .strength-bars {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 5px;
      height: 4px;
    }

    .strength-bar {
      border-radius: 999px;
      background: var(--border);
      transition: background-color 0.2s ease;
    }

    .strength-meta {
      display: flex;
      align-items: center;
      justify-content: space-between;
      font-size: 0.75rem;
      color: var(--text-muted);
    }

    .strength-label {
      font-weight: 600;
    }

    .pass-checks {
      display: flex;
      flex-wrap: wrap;
      gap: 8px 16px;
      margin-top: 2px;
    }

    .pass-check-item {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      font-size: 0.75rem;
      color: var(--text-muted);
      transition: color 0.15s ease;
    }

    .pass-check-item.met {
      color: var(--brand);
      font-weight: 500;
    }

    .match-indicator {
      font-size: 0.75rem;
      font-weight: 500;
      display: none;
      align-items: center;
      gap: 5px;
      margin-top: 2px;
    }

    .match-indicator.ok {
      display: inline-flex;
      color: var(--brand);
    }

    /* ── Step 3: Confirmation & Review ── */
    .review-section {
      background: var(--surface-subtle);
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      padding: 16px 18px;
    }

    .review-section-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
      padding-bottom: 10px;
      margin-bottom: 12px;
      border-bottom: 1px solid var(--border);
    }

    .summary-heading {
      font-size: 0.8125rem;
      font-weight: 700;
      color: var(--brand);
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }

    .btn-edit-step {
      background: transparent;
      border: 0;
      color: var(--brand);
      font-size: 0.78rem;
      font-weight: 600;
      font-family: inherit;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 4px 8px;
      border-radius: 6px;
      transition: background-color 0.15s ease;
    }

    .btn-edit-step:hover {
      background: var(--brand-soft);
    }

    .btn-edit-step:focus-visible {
      outline: 2px solid var(--brand);
      outline-offset: 2px;
    }

    .summary-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 12px 24px;
    }

    .summary-item {
      display: flex;
      flex-direction: column;
      gap: 2px;
      min-width: 0;
    }

    .summary-label {
      font-size: 0.7rem;
      color: var(--text-muted);
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }

    .summary-val {
      font-size: 0.875rem;
      font-weight: 600;
      color: var(--text-primary);
      overflow-wrap: anywhere;
    }

    .status-pill {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      width: fit-content;
      background: var(--amber-soft);
      color: var(--amber);
      border: 1px solid var(--amber-border);
      padding: 3px 9px;
      border-radius: 999px;
      font-size: 0.75rem;
      font-weight: 600;
    }

    .terms-box {
      padding: 12px 14px;
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      background: var(--surface);
    }

    .terms-label {
      display: flex;
      gap: 10px;
      align-items: flex-start;
      cursor: pointer;
    }

    .terms-label input[type="checkbox"] {
      accent-color: var(--brand);
      width: 17px;
      height: 17px;
      margin-top: 2px;
      flex-shrink: 0;
      cursor: pointer;
    }

    .terms-text {
      font-size: 0.8125rem;
      color: var(--text-secondary);
      line-height: 1.5;
    }

    .notice-box {
      background: var(--brand-soft);
      border: 1px solid #bbf7d0;
      border-radius: var(--radius-md);
      padding: 12px 14px;
      display: flex;
      gap: 10px;
      align-items: flex-start;
      color: #14532d;
      font-size: 0.8125rem;
      line-height: 1.45;
    }

    .notice-box svg {
      flex-shrink: 0;
      margin-top: 2px;
      color: var(--brand);
    }

    .error-banner {
      display: none;
      background: var(--danger-soft);
      border: 1px solid var(--danger-border);
      color: #b91c1c;
      border-radius: var(--radius-sm);
      padding: 10px 14px;
      font-size: 0.8125rem;
      line-height: 1.45;
    }

    .error-banner-link {
      background: transparent;
      border: 0;
      color: #991b1b;
      font-weight: 700;
      text-decoration: underline;
      cursor: pointer;
      font-family: inherit;
      font-size: inherit;
      margin-left: 6px;
    }

    /* ── Actions Row ── */
    .actions-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 12px;
      margin-top: 24px;
      padding-top: 18px;
      border-top: 1px solid #f1f5f9;
    }

    .btn-pill-next {
      background-color: var(--brand);
      color: #ffffff;
      border: 0;
      border-radius: var(--radius-sm);
      padding: 0 24px;
      height: 42px;
      font-size: 0.875rem;
      font-weight: 600;
      font-family: inherit;
      cursor: pointer;
      margin-left: auto;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: background-color 0.15s ease, transform 0.1s ease;
    }

    .btn-pill-next:hover:not(:disabled) {
      background-color: var(--brand-hover);
    }

    .btn-pill-next:active:not(:disabled) {
      transform: scale(0.99);
    }

    .btn-pill-next:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }

    .btn-pill-next:focus-visible,
    .btn-pill-back:focus-visible {
      outline: 2px solid var(--brand);
      outline-offset: 2px;
    }

    .btn-pill-back {
      background: var(--surface);
      color: #334155;
      border: 1px solid var(--border-strong);
      border-radius: var(--radius-sm);
      padding: 0 20px;
      height: 42px;
      font-size: 0.875rem;
      font-weight: 600;
      font-family: inherit;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      transition: background-color 0.15s ease, border-color 0.15s ease;
    }

    .btn-pill-back:hover:not(:disabled) {
      background: #f8fafc;
      border-color: #94a3b8;
    }

    .btn-pill-back:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }

    .login-footer-hint {
      margin-top: 18px;
      font-size: 0.8125rem;
      color: var(--text-muted);
      text-align: center;
    }

    .login-footer-hint a {
      color: var(--brand);
      font-weight: 600;
      text-decoration: none;
    }

    .login-footer-hint a:hover {
      text-decoration: underline;
    }

    /* ── Registration Success Modal ── */
    .reg-modal-backdrop {
      position: fixed;
      inset: 0;
      background: rgba(15, 23, 42, 0.52);
      backdrop-filter: blur(4px);
      -webkit-backdrop-filter: blur(4px);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 9999;
      padding: 1.25rem;
    }

    .reg-modal-card {
      background: var(--surface);
      border-radius: var(--radius-lg);
      padding: 28px 24px;
      max-width: 420px;
      width: 100%;
      text-align: center;
      box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.25);
      animation: modalFadeIn 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    @keyframes modalFadeIn {
      from { opacity: 0; transform: scale(0.96) translateY(8px); }
      to { opacity: 1; transform: scale(1) translateY(0); }
    }

    .reg-modal-icon {
      width: 52px;
      height: 52px;
      border-radius: 50%;
      background: var(--brand-soft);
      color: var(--brand-accent);
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 14px;
      border: 1px solid #bbf7d0;
    }

    .reg-modal-title {
      font-family: 'Outfit', 'Inter', sans-serif;
      font-size: 1.25rem;
      font-weight: 700;
      color: var(--text-primary);
      margin-bottom: 6px;
    }

    .reg-modal-desc {
      font-size: 0.875rem;
      color: var(--text-secondary);
      margin-bottom: 12px;
      line-height: 1.45;
    }

    .reg-email-pill {
      display: inline-flex;
      align-items: center;
      background: var(--brand-soft);
      color: var(--brand);
      font-weight: 600;
      font-size: 0.875rem;
      padding: 5px 12px;
      border-radius: 8px;
      border: 1px solid #bbf7d0;
      margin-bottom: 12px;
      overflow-wrap: anywhere;
    }

    .reg-modal-subdesc {
      font-size: 0.8125rem;
      color: var(--text-muted);
      line-height: 1.5;
      margin-bottom: 20px;
    }

    .reg-modal-btns {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .btn-modal-open-email {
      background: var(--brand);
      color: #ffffff !important;
      text-decoration: none;
      font-weight: 600;
      font-size: 0.875rem;
      padding: 10px 16px;
      min-height: 44px;
      border-radius: var(--radius-sm);
      transition: background-color 0.15s ease;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }

    .btn-modal-open-email:hover {
      background: var(--brand-hover);
    }

    .btn-modal-to-login {
      background: var(--surface);
      color: #334155 !important;
      border: 1px solid var(--border-strong);
      text-decoration: none;
      font-weight: 600;
      font-size: 0.875rem;
      padding: 10px 16px;
      min-height: 44px;
      border-radius: var(--radius-sm);
      transition: background-color 0.15s ease;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }

    .btn-modal-to-login:hover {
      background: #f8fafc;
    }

    @keyframes spin {
      to { transform: rotate(360deg); }
    }

    .spinner {
      width: 16px;
      height: 16px;
      animation: spin 0.8s linear infinite;
    }

    /* ── Responsive ── */
    @media (max-width: 768px) {
      html {
        scroll-padding-top: 152px;
      }

      #new-patient-registration-app {
        justify-content: flex-start;
        padding: 150px 14px 32px;
      }

      .card-wizard {
        padding: 20px 16px;
        border-radius: 14px;
      }

      .grid-3, .grid-2, .grid-contact, .summary-grid {
        grid-template-columns: 1fr;
      }

      .input-ctrl,
      .contact-wrapper {
        height: 44px;
      }

      .input-ctrl,
      .contact-wrapper input {
        font-size: 1rem;
      }

      .stepper-container {
        position: fixed;
        top: 64px;
        left: 0;
        right: 0;
        max-width: none;
        margin-bottom: 0;
        padding: 10px 14px 12px;
        background: rgba(255, 255, 255, 0.96);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        border-bottom: 1px solid var(--border);
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.05);
        z-index: 150;
      }

      .stepper-track {
        display: none;
      }

      .mobile-progress {
        display: flex;
      }

      .actions-row {
        flex-direction: column-reverse;
        align-items: stretch;
      }

      .btn-pill-next,
      .btn-pill-back {
        width: 100%;
        height: 44px;
        margin-left: 0;
      }

      .email-ref-banner {
        flex-direction: column;
        align-items: flex-start;
      }
    }
  </style>
</head>
<body>

  <!-- Top Navigation Bar -->
  <nav class="navbar" id="navbar">
    <div class="navbar-inner">
      <a class="logo" href="/sdn/index.php" title="Home">
        <img src="/sdn/public/assets/v3_lingkod_logo.png" alt="Lingkod Logo" class="logo-ver2-img" />
        <span class="logo-text">
          <span class="logo-title-top">Mariveles Mental Wellness and General Hospital</span>
        </span>
      </a>
    </div>
  </nav>

  <div id="new-patient-registration-app">
    <main class="main-wrapper">

      <!-- 3-Step Stepper -->
      <nav class="stepper-container" aria-label="Registration progress">
        <div class="stepper-track">
          <div class="stepper-line" aria-hidden="true">
            <div id="stepLineFill" class="stepper-line-fill"></div>
          </div>

          <button type="button" id="nodeStep1" class="step-node current" aria-current="step" onclick="goToStep(1)">
            <span class="step-circle" id="circleStep1">1</span>
            <span class="step-text">Personal Information</span>
          </button>

          <button type="button" id="nodeStep2" class="step-node" onclick="goToStep(2)">
            <span class="step-circle" id="circleStep2">2</span>
            <span class="step-text">Register an Account</span>
          </button>

          <button type="button" id="nodeStep3" class="step-node" onclick="goToStep(3)">
            <span class="step-circle" id="circleStep3">3</span>
            <span class="step-text">Confirmation &amp; Review</span>
          </button>
        </div>

        <div class="mobile-progress">
          <div class="mobile-progress-head">
            <div class="mobile-progress-meta">
              <span id="mobileProgressStep" class="mobile-progress-step">Step 1 of 3</span>
              <span id="mobileProgressNext" class="mobile-progress-next">Next: Register an Account</span>
            </div>
            <span id="mobileProgressPct" class="mobile-progress-pct">33%</span>
          </div>
          <div
            id="mobileProgressBar"
            class="mobile-progress-bar"
            role="progressbar"
            aria-label="Registration progress"
            aria-valuemin="0"
            aria-valuemax="100"
            aria-valuenow="33"
            aria-valuetext="33% complete — Step 1 of 3: Personal Information"
          >
            <div id="mobileProgressFill" class="mobile-progress-fill"></div>
          </div>
        </div>
      </nav>

      <!-- Wizard Card -->
      <div class="card-wizard">
        <form id="newPatientForm" novalidate onsubmit="handleFormSubmit(event)">

          <!-- STEP 1: PERSONAL INFORMATION -->
          <section id="phase1" class="wizard-phase active" aria-labelledby="headingStep1">
            <div class="phase-header">
              <h1 id="headingStep1" class="card-heading">Personal Information</h1>
              <p class="card-subheading">Provide the patient's accurate details for hospital records.</p>
            </div>

            <div class="form-stack">
              <div class="grid-3">
                <div class="field-group">
                  <label for="firstName">First Name <span class="req-star" aria-hidden="true">*</span></label>
                  <input type="text" id="firstName" class="input-ctrl" placeholder="e.g., Juan" autocomplete="given-name" required aria-describedby="errFirstName" />
                  <span id="errFirstName" class="error-msg" role="alert">First name is required.</span>
                </div>

                <div class="field-group">
                  <label for="lastName">Last Name <span class="req-star" aria-hidden="true">*</span></label>
                  <input type="text" id="lastName" class="input-ctrl" placeholder="e.g., Dela Cruz" autocomplete="family-name" required aria-describedby="errLastName" />
                  <span id="errLastName" class="error-msg" role="alert">Last name is required.</span>
                </div>

                <div class="field-group">
                  <label for="middleInitial">M.I. <span class="req-star" aria-hidden="true">*</span></label>
                  <input type="text" id="middleInitial" class="input-ctrl" placeholder="e.g., B" maxlength="1" required aria-describedby="errMiddleInitial" oninput="this.value = this.value.replace(/[^a-zA-Z]/g, '').toUpperCase().slice(0, 1);" />
                  <span id="errMiddleInitial" class="error-msg" role="alert">Required.</span>
                </div>
              </div>

              <div class="grid-3">
                <div class="field-group">
                  <label for="gender">Gender <span class="req-star" aria-hidden="true">*</span></label>
                  <select id="gender" class="input-ctrl" required aria-describedby="errGender">
                    <option value="" disabled selected>Select gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                  </select>
                  <span id="errGender" class="error-msg" role="alert">Please select a gender.</span>
                </div>

                <div class="field-group">
                  <label for="birthdate">Birthdate <span class="req-star" aria-hidden="true">*</span></label>
                  <input type="date" id="birthdate" class="input-ctrl" required aria-describedby="errBirthdate" onchange="calculateAge()" />
                  <span id="errBirthdate" class="error-msg" role="alert">A valid birthdate is required.</span>
                </div>

                <div class="field-group">
                  <label for="age">Age</label>
                  <input type="text" id="age" class="input-ctrl input-readonly" placeholder="Auto" readonly tabindex="-1" />
                </div>
              </div>

              <!-- Minor / Guardian Fields -->
              <div id="guardianSection" class="guardian-box">
                <div class="guardian-head">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2.2" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                  </svg>
                  <span>Minors must be accompanied by a parent or guardian during the hospital visit.</span>
                </div>
                <div class="grid-contact">
                  <div class="field-group">
                    <label for="guardianName">Parent / Guardian Full Name <span class="req-star" aria-hidden="true">*</span></label>
                    <input type="text" id="guardianName" class="input-ctrl" placeholder="e.g., Maria Santos" aria-describedby="errGuardianName" />
                    <span id="errGuardianName" class="error-msg" role="alert">Parent or guardian name is required.</span>
                  </div>
                  <div class="field-group">
                    <label for="guardianContactNumber">Guardian Contact Number <span class="req-star" aria-hidden="true">*</span></label>
                    <div id="guardianContactWrap" class="contact-wrapper">
                      <span class="prefix-badge">+63</span>
                      <input type="tel" id="guardianContactNumber" inputmode="numeric" placeholder="9XXXXXXXXX" maxlength="11" aria-describedby="errGuardianContact" />
                    </div>
                    <span id="errGuardianContact" class="error-msg" role="alert">Enter a valid 10-digit mobile number.</span>
                  </div>
                </div>
              </div>

              <div class="grid-contact">
                <div class="field-group">
                  <label for="contactNumber">Contact Number <span class="req-star" aria-hidden="true">*</span></label>
                  <div id="contactWrap" class="contact-wrapper">
                    <span class="prefix-badge">+63</span>
                    <input type="tel" id="contactNumber" inputmode="numeric" placeholder="9XXXXXXXXX" maxlength="11" autocomplete="tel-national" required aria-describedby="errContact" />
                  </div>
                  <span id="errContact" class="error-msg" role="alert">Enter a valid 10-digit mobile number.</span>
                </div>

                <div class="field-group">
                  <label for="email">Email Address <span class="req-star" aria-hidden="true">*</span></label>
                  <input type="email" id="email" class="input-ctrl" placeholder="name@example.com" autocomplete="email" required aria-describedby="errEmail" />
                  <span id="errEmail" class="error-msg" role="alert">Enter a valid email address.</span>
                </div>
              </div>

              <div class="field-group">
                <label for="address">Home Address <span class="req-star" aria-hidden="true">*</span></label>
                <input type="text" id="address" class="input-ctrl" placeholder="House No., Street, Barangay, Municipality/City" autocomplete="street-address" required aria-describedby="errAddress" />
                <span id="errAddress" class="error-msg" role="alert">Home address is required.</span>
              </div>
            </div>
          </section>

          <!-- STEP 2: REGISTER AN ACCOUNT -->
          <section id="phase2" class="wizard-phase" aria-labelledby="headingStep2">
            <div class="phase-header">
              <h1 id="headingStep2" class="card-heading">Register an Account</h1>
              <p class="card-subheading">Create the username and password you will use to sign in to the patient portal.</p>
            </div>

            <div class="form-stack">
              <div class="email-ref-banner">
                <div class="email-ref-left">
                  <span class="email-ref-icon" aria-hidden="true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                      <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                    </svg>
                  </span>
                  <div>
                    <div>Verification OTP will be sent to</div>
                    <div id="accountRefEmail" class="email-ref-addr"></div>
                  </div>
                </div>
                <button type="button" class="btn-edit-step" onclick="goToStep(1)">Change email</button>
              </div>

              <div class="field-group">
                <label for="regUsername">Username <span class="req-star" aria-hidden="true">*</span></label>
                <input
                  type="text"
                  id="regUsername"
                  class="input-ctrl"
                  placeholder="e.g., juan.delacruz"
                  autocomplete="username"
                  autocapitalize="none"
                  autocorrect="off"
                  spellcheck="false"
                  minlength="3"
                  maxlength="30"
                  required
                  aria-describedby="usernameHint errUsername"
                />
                <span id="usernameHint" class="field-hint">3–30 characters. Letters, numbers, dots (.), underscores (_), or hyphens (-) only.</span>
                <span id="errUsername" class="error-msg" role="alert">Username must be 3–30 characters (letters, numbers, ., _, -).</span>
              </div>

              <div class="grid-2">
                <div class="field-group">
                  <label for="regPassword">Password <span class="req-star" aria-hidden="true">*</span></label>
                  <div class="password-wrapper">
                    <input
                      type="password"
                      id="regPassword"
                      class="input-ctrl"
                      placeholder="Minimum 8 characters"
                      autocomplete="new-password"
                      minlength="8"
                      required
                      aria-describedby="passChecks errPassword"
                      oninput="updatePasswordFeedback()"
                    />
                    <button type="button" class="btn-eye" aria-label="Show password" aria-pressed="false" title="Show password" onclick="togglePassVisibility('regPassword', this)">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                      </svg>
                    </button>
                  </div>
                  <span id="errPassword" class="error-msg" role="alert">Password must be at least 8 characters.</span>
                </div>

                <div class="field-group">
                  <label for="regConfirmPassword">Confirm Password <span class="req-star" aria-hidden="true">*</span></label>
                  <div class="password-wrapper">
                    <input
                      type="password"
                      id="regConfirmPassword"
                      class="input-ctrl"
                      placeholder="Re-enter your password"
                      autocomplete="new-password"
                      required
                      aria-describedby="errConfirmPassword"
                      oninput="updatePasswordFeedback()"
                    />
                    <button type="button" class="btn-eye" aria-label="Show confirm password" aria-pressed="false" title="Show password" onclick="togglePassVisibility('regConfirmPassword', this)">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                      </svg>
                    </button>
                  </div>
                  <span id="errConfirmPassword" class="error-msg" role="alert">Passwords do not match.</span>
                  <span id="passMatchOk" class="match-indicator" aria-live="polite">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    Passwords match
                  </span>
                </div>
              </div>

              <!-- Live Password Strength & Requirements -->
              <div class="strength-wrap" aria-live="polite">
                <div class="strength-bars" aria-hidden="true">
                  <div id="strBar1" class="strength-bar"></div>
                  <div id="strBar2" class="strength-bar"></div>
                  <div id="strBar3" class="strength-bar"></div>
                  <div id="strBar4" class="strength-bar"></div>
                </div>
                <div class="strength-meta">
                  <div id="passChecks" class="pass-checks">
                    <span id="chkLen" class="pass-check-item">
                      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle></svg>
                      8+ characters (required)
                    </span>
                    <span id="chkMix" class="pass-check-item">
                      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle></svg>
                      Upper &amp; lowercase
                    </span>
                    <span id="chkNum" class="pass-check-item">
                      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle></svg>
                      Number or symbol
                    </span>
                  </div>
                  <span id="strengthText" class="strength-label"></span>
                </div>
              </div>
            </div>
          </section>

          <!-- STEP 3: CONFIRMATION & REVIEW -->
          <section id="phase3" class="wizard-phase" aria-labelledby="headingStep3">
            <div class="phase-header">
              <h1 id="headingStep3" class="card-heading">Confirmation &amp; Review</h1>
              <p class="card-subheading">Verify your personal information and account details before submitting.</p>
            </div>

            <div class="form-stack">
              <div class="review-section">
                <div class="review-section-head">
                  <span class="summary-heading">Personal Information</span>
                  <button type="button" class="btn-edit-step" onclick="goToStep(1)">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M12 20h9"></path><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path></svg>
                    Edit
                  </button>
                </div>
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
                  <div class="summary-item" id="revGuardianRow" style="display: none;">
                    <span class="summary-label">Parent / Guardian</span>
                    <span id="revGuardian" class="summary-val"></span>
                  </div>
                </div>
              </div>

              <div class="review-section">
                <div class="review-section-head">
                  <span class="summary-heading">Account Credentials</span>
                  <button type="button" class="btn-edit-step" onclick="goToStep(2)">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M12 20h9"></path><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path></svg>
                    Edit
                  </button>
                </div>
                <div class="summary-grid">
                  <div class="summary-item">
                    <span class="summary-label">Username</span>
                    <span id="revUsername" class="summary-val"></span>
                  </div>
                  <div class="summary-item">
                    <span class="summary-label">Password &amp; Verification</span>
                    <span class="status-pill">
                      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                      Pending Email Verification
                    </span>
                  </div>
                </div>
              </div>

              <div class="terms-box">
                <label class="terms-label">
                  <input type="checkbox" id="chkTerms" aria-describedby="errTerms" />
                  <span class="terms-text">
                    I certify that all details provided are true and correct, and I agree to MMWGH's Data Privacy Policy regarding healthcare information.
                  </span>
                </label>
                <span id="errTerms" class="error-msg" role="alert" style="margin-top: 6px;">You must accept the data privacy statement to submit.</span>
              </div>

              <div class="notice-box">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <circle cx="12" cy="12" r="10"></circle>
                  <line x1="12" y1="16" x2="12" y2="12"></line>
                  <line x1="12" y1="8" x2="12.01" y2="8"></line>
                </svg>
                <p>
                  <strong>Email Verification Required:</strong> A 6-digit verification code and activation link will be sent to <strong id="revNoticeEmail">your email</strong> upon submission. Verify your account before signing in with your chosen username and password.
                </p>
              </div>

              <div id="submitErrorBanner" class="error-banner" role="alert"></div>
            </div>
          </section>

        </form>

        <!-- Actions Footer -->
        <div id="wizardActions" class="actions-row">
          <button type="button" id="btnBack" class="btn-pill-back" onclick="handleBackStep()" style="display: none;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"></polyline></svg>
            Back
          </button>
          <button type="button" id="btnNext" class="btn-pill-next" onclick="handleNextStep()">
            Continue
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"></polyline></svg>
          </button>
          <button type="submit" form="newPatientForm" id="btnSubmit" class="btn-pill-next" style="display: none;">
            Submit Registration
          </button>
        </div>
      </div>

      <p class="login-footer-hint">
        Already have a patient account? <a href="/sdn/pages/patient-login.php">Sign in</a>
      </p>
    </main>
  </div>

  <!-- Registration Success Modal -->
  <div id="regSuccessModal" class="reg-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="regModalTitle">
    <div class="reg-modal-card">
      <div class="reg-modal-icon" aria-hidden="true">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
      </div>
      <h2 id="regModalTitle" class="reg-modal-title">Account Registered!</h2>
      <p class="reg-modal-desc">
        A 6-digit verification code and activation link have been sent to:
      </p>
      <div id="regModalEmailBadge" class="reg-email-pill"></div>
      <p class="reg-modal-subdesc">
        Verify your email first, then sign in using the username and password you created.
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

  <script>
    let currentStep = 1;
    const TOTAL_STEPS = 3;
    const STEP_META = [
      { title: 'Personal Information', next: 'Next: Register an Account' },
      { title: 'Register an Account', next: 'Next: Confirmation & Review' },
      { title: 'Confirmation & Review', next: 'Final step' }
    ];

    const SVG_CHECK = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"></polyline></svg>`;
    const SVG_EYE_OPEN = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>`;
    const SVG_EYE_SLASH = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>`;
    const SVG_DOT = `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle></svg>`;
    const SVG_TICK = `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" aria-hidden="true"><polyline points="20 6 9 17 4 12"></polyline></svg>`;

    // Prevent future dates in native date picker
    document.getElementById('birthdate').max = new Date().toISOString().split('T')[0];

    function setFieldError(inputId, errId, show, wrapId) {
      const inp = document.getElementById(inputId);
      const err = document.getElementById(errId);
      const target = wrapId ? document.getElementById(wrapId) : inp;
      if (err) err.style.display = show ? 'block' : 'none';
      if (target) target.classList.toggle('invalid', show);
      if (inp) inp.setAttribute('aria-invalid', show ? 'true' : 'false');
    }

    function clearPhaseErrors(phaseId) {
      const scope = document.getElementById(phaseId);
      if (!scope) return;
      scope.querySelectorAll('.error-msg').forEach(el => el.style.display = 'none');
      scope.querySelectorAll('.invalid').forEach(el => el.classList.remove('invalid'));
      scope.querySelectorAll('[aria-invalid="true"]').forEach(el => el.setAttribute('aria-invalid', 'false'));
    }

    function calculateAge() {
      const bInput = document.getElementById('birthdate').value;
      const gSec = document.getElementById('guardianSection');
      if (!bInput) {
        document.getElementById('age').value = '';
        gSec.style.display = 'none';
        return;
      }
      const b = new Date(bInput + 'T00:00:00');
      const today = new Date();
      if (!isNaN(b.getTime()) && b <= today) {
        let age = today.getFullYear() - b.getFullYear();
        const m = today.getMonth() - b.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < b.getDate())) age--;
        document.getElementById('age').value = age >= 0 ? age : '';
        gSec.style.display = (age >= 0 && age < 18) ? 'block' : 'none';
      } else {
        document.getElementById('age').value = '';
        gSec.style.display = 'none';
      }
    }

    function togglePassVisibility(inputId, btn) {
      const inp = document.getElementById(inputId);
      const reveal = inp.type === 'password';
      inp.type = reveal ? 'text' : 'password';
      btn.innerHTML = reveal ? SVG_EYE_SLASH : SVG_EYE_OPEN;
      btn.setAttribute('aria-label', reveal ? 'Hide password' : 'Show password');
      btn.setAttribute('aria-pressed', reveal ? 'true' : 'false');
      btn.title = reveal ? 'Hide password' : 'Show password';
    }

    function updatePasswordFeedback() {
      const pass = document.getElementById('regPassword').value;
      const confirm = document.getElementById('regConfirmPassword').value;

      const hasLen = pass.length >= 8;
      const hasMix = /[a-z]/.test(pass) && /[A-Z]/.test(pass);
      const hasNumOrSym = /[0-9]/.test(pass) || /[^a-zA-Z0-9]/.test(pass);
      const hasLong = pass.length >= 12;

      const toggleCheck = (id, met) => {
        const el = document.getElementById(id);
        el.classList.toggle('met', met);
        el.querySelector('svg').outerHTML = met ? SVG_TICK : SVG_DOT;
      };
      toggleCheck('chkLen', hasLen);
      toggleCheck('chkMix', hasMix);
      toggleCheck('chkNum', hasNumOrSym);

      let score = 0;
      if (pass.length > 0) {
        if (hasLen) score++;
        if (hasMix) score++;
        if (hasNumOrSym) score++;
        if (hasLong && score >= 2) score++;
        if (score === 0) score = 1;
      }

      const palette = ['', '#dc2626', '#f59e0b', '#16a34a', '#15803d'];
      const labels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
      for (let i = 1; i <= 4; i++) {
        document.getElementById(`strBar${i}`).style.backgroundColor = i <= score ? palette[score] : 'var(--border)';
      }
      const strText = document.getElementById('strengthText');
      strText.textContent = labels[score];
      strText.style.color = palette[score] || 'var(--text-muted)';

      if (hasLen) setFieldError('regPassword', 'errPassword', false);

      const matchOk = document.getElementById('passMatchOk');
      if (confirm.length > 0 && pass === confirm) {
        setFieldError('regConfirmPassword', 'errConfirmPassword', false);
        matchOk.classList.add('ok');
      } else {
        matchOk.classList.remove('ok');
      }
    }

    function validatePhase1() {
      clearPhaseErrors('phase1');
      let firstInvalid = null;
      const fail = (id, errId, wrapId) => {
        setFieldError(id, errId, true, wrapId);
        if (!firstInvalid) firstInvalid = document.getElementById(id);
      };

      const fn = document.getElementById('firstName').value.trim();
      const ln = document.getElementById('lastName').value.trim();
      const mi = document.getElementById('middleInitial').value.trim();
      const gender = document.getElementById('gender').value;
      const bd = document.getElementById('birthdate').value;
      const contact = document.getElementById('contactNumber').value.replace(/\D/g, '');
      const email = document.getElementById('email').value.trim();
      const addr = document.getElementById('address').value.trim();

      if (!fn) fail('firstName', 'errFirstName');
      if (!ln) fail('lastName', 'errLastName');
      if (!mi) fail('middleInitial', 'errMiddleInitial');
      if (!gender) fail('gender', 'errGender');
      if (!bd || new Date(bd + 'T00:00:00') > new Date()) fail('birthdate', 'errBirthdate');
      if (contact.length < 9 || contact.length > 11) fail('contactNumber', 'errContact', 'contactWrap');
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) fail('email', 'errEmail');
      if (!addr) fail('address', 'errAddress');

      const ageVal = parseInt(document.getElementById('age').value, 10);
      if (!isNaN(ageVal) && ageVal < 18) {
        const gn = document.getElementById('guardianName').value.trim();
        const gc = document.getElementById('guardianContactNumber').value.replace(/\D/g, '');
        if (!gn) fail('guardianName', 'errGuardianName');
        if (gc.length < 9 || gc.length > 11) fail('guardianContactNumber', 'errGuardianContact', 'guardianContactWrap');
      }

      if (firstInvalid) {
        firstInvalid.focus();
        return false;
      }
      return true;
    }

    function validatePhase2() {
      clearPhaseErrors('phase2');
      let firstInvalid = null;
      const fail = (id, errId) => {
        setFieldError(id, errId, true);
        if (!firstInvalid) firstInvalid = document.getElementById(id);
      };

      const username = document.getElementById('regUsername').value.trim();
      const password = document.getElementById('regPassword').value;
      const confirm = document.getElementById('regConfirmPassword').value;

      if (!/^[a-zA-Z0-9._-]{3,30}$/.test(username)) {
        fail('regUsername', 'errUsername');
      }
      if (password.length < 8) {
        fail('regPassword', 'errPassword');
      }
      if (!confirm || password !== confirm) {
        fail('regConfirmPassword', 'errConfirmPassword');
      }

      if (firstInvalid) {
        firstInvalid.focus();
        return false;
      }
      return true;
    }

    function populateReview() {
      const fn = document.getElementById('firstName').value.trim();
      const mi = document.getElementById('middleInitial').value.replace(/[^a-zA-Z]/g, '').toUpperCase().slice(0, 1);
      const ln = document.getElementById('lastName').value.trim();
      const rawEmail = document.getElementById('email').value.trim();
      const chosenUsername = document.getElementById('regUsername').value.trim();

      document.getElementById('revFullName').textContent = mi ? `${fn} ${mi}. ${ln}` : `${fn} ${ln}`;
      document.getElementById('revGenderAge').textContent = `${document.getElementById('gender').value}, ${document.getElementById('age').value} yrs old`;
      document.getElementById('revBirthdate').textContent = document.getElementById('birthdate').value;
      document.getElementById('revContact').textContent = `+63 ${document.getElementById('contactNumber').value.trim().replace(/^0/, '')}`;
      document.getElementById('revEmail').textContent = rawEmail;
      document.getElementById('revNoticeEmail').textContent = rawEmail;
      document.getElementById('revAddress').textContent = document.getElementById('address').value.trim();
      document.getElementById('revUsername').textContent = chosenUsername;

      const ageVal = parseInt(document.getElementById('age').value, 10);
      const revGuardRow = document.getElementById('revGuardianRow');
      if (!isNaN(ageVal) && ageVal < 18) {
        revGuardRow.style.display = 'flex';
        const gn = document.getElementById('guardianName').value.trim();
        const gc = document.getElementById('guardianContactNumber').value.trim().replace(/^0/, '');
        document.getElementById('revGuardian').textContent = `${gn} (+63 ${gc})`;
      } else {
        revGuardRow.style.display = 'none';
      }
    }

    function updateWizardUI() {
      for (let i = 1; i <= TOTAL_STEPS; i++) {
        document.getElementById(`phase${i}`).classList.toggle('active', i === currentStep);

        const node = document.getElementById(`nodeStep${i}`);
        const circle = document.getElementById(`circleStep${i}`);
        const isCurrent = i === currentStep;
        const isCompleted = i < currentStep;

        node.classList.toggle('current', isCurrent);
        node.classList.toggle('completed', isCompleted);
        if (isCurrent) {
          node.setAttribute('aria-current', 'step');
        } else {
          node.removeAttribute('aria-current');
        }
        node.disabled = i > currentStep;
        circle.innerHTML = isCompleted ? SVG_CHECK : String(i);
      }

      const progressPct = ((currentStep - 1) / (TOTAL_STEPS - 1)) * 100;
      document.getElementById('stepLineFill').style.width = `${progressPct}%`;

      const pctRatio = currentStep / TOTAL_STEPS;
      const mobilePct = Math.round(pctRatio * 100);
      const stepInfo = STEP_META[currentStep - 1];
      document.getElementById('mobileProgressStep').textContent = `Step ${currentStep} of ${TOTAL_STEPS}`;
      document.getElementById('mobileProgressNext').textContent = stepInfo.next;
      document.getElementById('mobileProgressPct').textContent = `${mobilePct}%`;
      document.getElementById('mobileProgressFill').style.transform = `scaleX(${pctRatio})`;
      const mobileBar = document.getElementById('mobileProgressBar');
      mobileBar.setAttribute('aria-valuenow', String(mobilePct));
      mobileBar.setAttribute('aria-valuetext', `${mobilePct}% complete — Step ${currentStep} of ${TOTAL_STEPS}: ${stepInfo.title}`);

      document.getElementById('btnBack').style.display = currentStep > 1 ? 'inline-flex' : 'none';
      document.getElementById('btnNext').style.display = currentStep < TOTAL_STEPS ? 'inline-flex' : 'none';
      document.getElementById('btnSubmit').style.display = currentStep === TOTAL_STEPS ? 'inline-flex' : 'none';

      if (currentStep === 2) {
        document.getElementById('accountRefEmail').textContent = document.getElementById('email').value.trim();
      }
    }

    function handleNextStep() {
      if (currentStep === 1) {
        if (!validatePhase1()) return;
        currentStep = 2;
        updateWizardUI();
        document.getElementById('regUsername').focus();
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
      if (step >= 1 && step < currentStep) {
        currentStep = step;
        updateWizardUI();
      }
    }

    // Inline blur validation on Step 2 fields
    document.getElementById('regUsername').addEventListener('blur', function () {
      const val = this.value.trim();
      if (val !== '') {
        setFieldError('regUsername', 'errUsername', !/^[a-zA-Z0-9._-]{3,30}$/.test(val));
      }
    });

    document.getElementById('regConfirmPassword').addEventListener('blur', function () {
      const pass = document.getElementById('regPassword').value;
      if (this.value !== '') {
        setFieldError('regConfirmPassword', 'errConfirmPassword', pass !== this.value);
      }
    });

    async function handleFormSubmit(e) {
      e.preventDefault();
      if (currentStep < TOTAL_STEPS) {
        handleNextStep();
        return;
      }

      const errTerms = document.getElementById('errTerms');
      errTerms.style.display = 'none';
      if (!document.getElementById('chkTerms').checked) {
        errTerms.style.display = 'block';
        document.getElementById('chkTerms').focus();
        return;
      }

      const submitBtn = document.getElementById('btnSubmit');
      const backBtn = document.getElementById('btnBack');
      const errBanner = document.getElementById('submitErrorBanner');
      errBanner.style.display = 'none';
      errBanner.innerHTML = '';

      submitBtn.disabled = true;
      backBtn.disabled = true;
      submitBtn.innerHTML = `<svg class="spinner" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke-opacity="0.25"></circle><path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path></svg> Submitting…`;

      const subAge = parseInt(document.getElementById('age').value, 10) || 0;
      const isSubMinor = subAge < 18;
      const subGuardName = document.getElementById('guardianName').value.trim();
      const subGuardContact = document.getElementById('guardianContactNumber').value.trim();
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
          document.getElementById('regModalEmailBadge').textContent = userEmail;

          const emailBtn = document.getElementById('regModalEmailBtn');
          if (userEmail.toLowerCase().includes('@gmail.com')) {
            emailBtn.href = 'https://mail.google.com';
            emailBtn.innerHTML = 'Open Gmail &rarr;';
          } else if (userEmail.toLowerCase().includes('@yahoo.')) {
            emailBtn.href = 'https://mail.yahoo.com';
            emailBtn.innerHTML = 'Open Yahoo Mail &rarr;';
          } else {
            emailBtn.href = 'https://outlook.live.com';
            emailBtn.innerHTML = 'Open Outlook &rarr;';
          }

          document.getElementById('regSuccessModal').style.display = 'flex';
        } else {
          errBanner.textContent = data.message || 'Registration failed. Please check your information.';
          if (data.code === 'USERNAME_TAKEN') {
            const jumpBtn = document.createElement('button');
            jumpBtn.type = 'button';
            jumpBtn.className = 'error-banner-link';
            jumpBtn.textContent = 'Edit username';
            jumpBtn.onclick = () => {
              goToStep(2);
              document.getElementById('regUsername').focus();
            };
            errBanner.appendChild(jumpBtn);
          }
          errBanner.style.display = 'block';
        }
      } catch (err) {
        errBanner.textContent = 'Unable to connect to the server. Please try again.';
        errBanner.style.display = 'block';
      } finally {
        submitBtn.disabled = false;
        backBtn.disabled = false;
        submitBtn.textContent = 'Submit Registration';
      }
    }

    updateWizardUI();
  </script>
</body>
</html>
