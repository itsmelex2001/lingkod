<?php
session_start();
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Patient Portal | LINGKOD</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/sdn/public/css/global.css?v=<?= time() ?>" />

    <style>
      /* ── Reset & Root ── */
      *, *::before, *::after {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
      }

      /* Fixed, non-scrollable viewport */
      html, body {
        width: 100%;
        height: 100%;
        overflow: hidden !important;
        scrollbar-width: none !important;
        -ms-overflow-style: none !important;
      }
      html::-webkit-scrollbar, body::-webkit-scrollbar {
        display: none !important;
        width: 0 !important;
        height: 0 !important;
      }

      body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        background-color: #071a10;
        color: #ffffff;
        position: fixed;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
      }

      /* ── Background Image (Cropped to exclude right-side scaffolding, perfectly framing MMWGH pillars) ── */
      .portal-bg-canvas {
        position: fixed;
        inset: 0;
        z-index: 0;
        background-image: url('/sdn/public/assets/mmwghicon_bg.jpg');
        background-size: cover;
        background-position: left center;
        background-repeat: no-repeat;
        transform-origin: 8% 44%;
        transform: scale(1.30);
        pointer-events: none;
      }

      /* ── Background Shading Overlay (layered green depth, photo still readable) ── */
      .portal-bg-overlay {
        position: fixed;
        inset: 0;
        z-index: 1;
        background:
          /* Even dark-green wash so the pale sky and concrete sit a step darker */
          linear-gradient(180deg, rgba(5, 16, 10, 0.34) 0%, rgba(5, 20, 12, 0.22) 46%, rgba(4, 28, 14, 0.28) 100%),
          /* Soft center veil so the card sits on even contrast */
          radial-gradient(ellipse 46% 58% at 50% 46%, rgba(4, 16, 10, 0.52) 0%, rgba(4, 16, 10, 0.22) 48%, transparent 74%),
          /* Top sky shade */
          linear-gradient(to bottom, rgba(6, 16, 10, 0.62) 0%, rgba(6, 18, 12, 0.28) 28%, transparent 52%),
          /* Bottom green floor — translucent so the building still shows */
          linear-gradient(to top, rgba(6, 72, 28, 0.86) 0%, rgba(8, 88, 34, 0.54) 26%, rgba(8, 88, 34, 0.18) 52%, transparent 70%),
          /* Bottom-left green flare */
          radial-gradient(ellipse 68% 62% at 0% 100%, rgba(8, 96, 36, 0.8) 0%, rgba(8, 86, 32, 0.46) 42%, transparent 72%),
          /* Bottom-right green flare */
          radial-gradient(ellipse 68% 62% at 100% 100%, rgba(8, 96, 36, 0.8) 0%, rgba(8, 86, 32, 0.46) 42%, transparent 72%),
          /* Edge vignette */
          radial-gradient(ellipse 130% 100% at 50% 42%, transparent 42%, rgba(3, 10, 6, 0.5) 100%);
        pointer-events: none;
      }

      /* ── Main Login Container (dark-green glass so white logo and labels stay clear) ── */
      .login-container {
        position: relative;
        z-index: 5;
        width: 100%;
        max-width: 440px;
        border-radius: 28px;
        border: 1.5px solid rgba(255, 255, 255, 0.46);
        background: linear-gradient(
          165deg,
          rgba(10, 48, 26, 0.62) 0%,
          rgba(8, 36, 20, 0.48) 48%,
          rgba(6, 32, 18, 0.66) 100%
        );
        backdrop-filter: blur(18px) saturate(140%);
        -webkit-backdrop-filter: blur(18px) saturate(140%);
        box-shadow:
          0 24px 56px rgba(0, 0, 0, 0.38),
          inset 0 1px 0 rgba(255, 255, 255, 0.38),
          inset 0 -1px 0 rgba(0, 0, 0, 0.18);
        padding: 2.15rem 2.35rem 1.9rem;
        box-sizing: border-box;
      }
      .login-container::before {
        content: "";
        position: absolute;
        inset: 0;
        border-radius: inherit;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.14) 0%, transparent 32%);
        pointer-events: none;
        z-index: 0;
      }

      .login-card {
        position: relative;
        z-index: 1;
        width: 100%;
      }

      /* ── Card Header ── */
      .card-header {
        text-align: center;
        margin-bottom: 1.5rem;
      }

      .logo-emblem {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 0.65rem;
      }
      .logo-emblem img {
        width: 168px;
        height: auto;
        max-height: 118px;
        object-fit: contain;
        display: block;
        filter:
          brightness(1.35)
          drop-shadow(0 2px 6px rgba(0, 0, 0, 0.55))
          drop-shadow(0 0 12px rgba(0, 0, 0, 0.28));
      }

      .portal-title {
        font-family: 'Outfit', sans-serif;
        font-size: 1.58rem;
        font-weight: 800;
        letter-spacing: 0.045em;
        color: #ffffff;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.55), 0 2px 10px rgba(0, 0, 0, 0.35);
        margin: 0 0 6px;
        line-height: 1.15;
      }

      .portal-subtitle {
        font-size: 0.86rem;
        color: #f4fff8;
        text-shadow: 0 1px 3px rgba(0, 0, 0, 0.55);
        font-weight: 600;
        letter-spacing: 0.01em;
        margin-bottom: 0.85rem;
        line-height: 1.4;
      }

      /* ── Form Styles ── */
      .form-group {
        margin-bottom: 1.15rem;
      }

      .form-label {
        display: block;
        font-size: 0.84rem;
        font-weight: 700;
        color: #ffffff;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
        margin-bottom: 0.42rem;
        letter-spacing: 0.01em;
      }

      .input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
      }

      .input-icon {
        position: absolute;
        left: 14px;
        width: 18px;
        height: 18px;
        color: #1f2937;
        pointer-events: none;
        transition: color 0.2s ease;
        z-index: 1;
      }

      .form-control {
        width: 100%;
        height: 48px;
        padding: 0 2.7rem 0 2.75rem;
        background: rgba(255, 255, 255, 0.94);
        border: 1px solid rgba(255, 255, 255, 0.85);
        border-radius: 12px;
        color: #0f172a;
        font-family: inherit;
        font-size: 0.94rem;
        font-weight: 500;
        outline: none;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.08);
        transition: all 0.2s ease;
      }
      .form-control::placeholder {
        color: #4b5563;
        font-size: 0.9rem;
        font-weight: 500;
      }
      .form-control:focus {
        background: #ffffff;
        border-color: #16a34a;
        box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.28);
      }
      .input-wrapper:focus-within .input-icon {
        color: #047857;
      }

      .btn-toggle-eye {
        position: absolute;
        right: 12px;
        background: transparent;
        border: none;
        color: #1f2937;
        cursor: pointer;
        padding: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: color 0.2s ease;
        z-index: 1;
      }
      .btn-toggle-eye:hover {
        color: #047857;
      }

      /* ── Forgot Password Row ── */
      .options-row {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        margin-top: 0.45rem;
        margin-bottom: 1.4rem;
      }

      .forgot-link {
        color: #ffffff;
        text-decoration: none;
        font-size: 0.82rem;
        font-weight: 700;
        text-shadow: 0 1px 3px rgba(0, 0, 0, 0.55);
        transition: opacity 0.2s ease;
      }
      .forgot-link:hover {
        opacity: 0.88;
        text-decoration: underline;
      }

      /* ── Submit Button (Vibrant Green matching reference) ── */
      .btn-portal-submit {
        width: 100%;
        height: 48px;
        border-radius: 14px;
        border: none;
        background: #0ea43f;
        color: #ffffff;
        font-family: inherit;
        font-size: 1.05rem;
        font-weight: 700;
        letter-spacing: 0.01em;
        cursor: pointer;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.28);
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
      }
      .btn-portal-submit:hover {
        background: #0d9639;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.35);
        transform: translateY(-1px);
      }
      .btn-portal-submit:active {
        transform: translateY(0);
      }

      /* ── Modal ── */
      .demo-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 100;
        padding: 1.25rem;
      }
      .demo-modal-overlay.active {
        display: flex;
      }

      .demo-modal {
        background: rgba(15, 40, 24, 0.88);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.22);
        border-radius: 20px;
        padding: 2.2rem 2rem;
        max-width: 400px;
        width: 100%;
        color: white;
        text-align: center;
        box-shadow: 0 24px 48px rgba(0, 0, 0, 0.55);
        animation: modalPop 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards;
      }

      @keyframes modalPop {
        from { opacity: 0; transform: scale(0.94) translateY(10px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
      }

      .demo-modal-icon {
        width: 54px;
        height: 54px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.1);
        color: #86efac;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
      }

      .demo-modal h3 {
        font-family: 'Outfit', sans-serif;
        font-size: 1.35rem;
        font-weight: 700;
        margin-bottom: 0.4rem;
        color: #ffffff;
      }

      .demo-modal p {
        font-size: 0.88rem;
        color: rgba(255, 255, 255, 0.78);
        line-height: 1.5;
        margin-bottom: 1.5rem;
      }

      .btn-close-modal {
        width: 100%;
        padding: 0.82rem;
        border-radius: 10px;
        border: 1px solid rgba(255, 255, 255, 0.18);
        background: #14532d;
        color: white;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: background 0.2s ease;
      }
      .btn-close-modal:hover {
        background: #166534;
      }

      /* ── In-card Status Notification Banner ── */
      .portal-status-banner {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 12px 14px;
        border-radius: 12px;
        margin-bottom: 1.25rem;
        font-size: 0.84rem;
        line-height: 1.45;
        text-align: left;
        animation: bannerSlideDown 0.3s ease-out;
      }

      @keyframes bannerSlideDown {
        from { opacity: 0; transform: translateY(-8px); }
        to { opacity: 1; transform: translateY(0); }
      }

      .portal-status-banner.banner-success {
        background: rgba(236, 253, 245, 0.96);
        border: 1.5px solid #10b981;
        color: #064e3b;
      }

      .portal-status-banner.banner-info {
        background: rgba(240, 253, 244, 0.96);
        border: 1.5px solid #22c55e;
        color: #14532d;
      }

      .portal-status-banner.banner-warning {
        background: rgba(254, 252, 232, 0.96);
        border: 1.5px solid #eab308;
        color: #713f12;
      }

      .banner-icon-col {
        flex-shrink: 0;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-top: 1px;
      }

      .banner-success .banner-icon-col {
        background: #10b981;
        color: #ffffff;
      }

      .banner-info .banner-icon-col {
        background: #16a34a;
        color: #ffffff;
      }

      .banner-warning .banner-icon-col {
        background: #ca8a04;
        color: #ffffff;
      }

      .banner-text-col h4 {
        font-size: 0.88rem;
        font-weight: 700;
        margin-bottom: 2px;
        line-height: 1.2;
      }

      .banner-text-col p {
        font-size: 0.8rem;
        opacity: 0.92;
        margin: 0;
      }

      /* ── Status Box inside modals ── */
      .status-box {
        padding: 10px 14px;
        border-radius: 10px;
        margin-bottom: 1rem;
        font-size: 0.84rem;
        line-height: 1.45;
        text-align: left;
      }
      .status-box.error {
        background: rgba(239, 68, 68, 0.22);
        border: 1px solid rgba(248, 113, 113, 0.45);
        color: #fecaca;
      }
      .status-box.success {
        background: rgba(16, 185, 129, 0.22);
        border: 1px solid rgba(52, 211, 153, 0.45);
        color: #a7f3d0;
      }
      .btn-secondary-link {
        display: block;
        text-align: center;
        color: #ffffff;
        text-decoration: none;
        font-size: 0.84rem;
        font-weight: 600;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
      }
      .btn-secondary-link:hover {
        text-decoration: underline;
      }

      @media (max-width: 640px) {
        .portal-top-bar { padding: 1.1rem 1.25rem; }
        .login-container { border-radius: 22px; padding: 1.8rem 1.4rem; }
        .portal-title { font-size: 1.45rem; }
      }
    </style>
  </head>
  <body>
    <!-- Background Image & Subtle Tint Overlay -->
    <div class="portal-bg-canvas"></div>
    <div class="portal-bg-overlay"></div>

    <!-- Main Login Card -->
    <main class="login-container">
      <div class="login-card">

        <!-- Header -->
        <div class="card-header">
          <div class="logo-emblem">
            <img src="/sdn/public/assets/v3_lingkod_logo_clean.png" alt="LINGKOD Logo" />
          </div>
          <h1 class="portal-title">PATIENT PORTAL</h1>
          <p class="portal-subtitle">Mariveles Mental Wellness and General Hospital</p>
        </div>

        <!-- Status Notification Banner -->
        <div id="loginStatusBanner" class="portal-status-banner" style="display: none;">
          <div class="banner-icon-col" id="bannerIcon"></div>
          <div class="banner-text-col">
            <h4 id="bannerTitle"></h4>
            <p id="bannerMsg"></p>
          </div>
        </div>

        <!-- Login Form -->
        <form id="patientLoginForm" onsubmit="handleSampleLogin(event)">

          <!-- Email Field -->
          <div class="form-group">
            <label class="form-label" for="patientEmailInput">Email</label>
            <div class="input-wrapper">
              <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
              </svg>
              <input
                type="email"
                id="patientEmailInput"
                class="form-control"
                placeholder="Enter your email"
                required
                autocomplete="email"
              />
            </div>
          </div>

          <!-- Password Field -->
          <div class="form-group">
            <label class="form-label" for="patientPassInput">Password</label>
            <div class="input-wrapper">
              <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
              </svg>
              <input
                type="password"
                id="patientPassInput"
                class="form-control"
                placeholder="Enter your password"
                required
                autocomplete="current-password"
              />
              <button type="button" class="btn-toggle-eye" onclick="togglePassVisibility()" title="Show/Hide Password">
                <svg id="eyeIcon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
              </button>
            </div>
          </div>

          <!-- Forgot Password -->
          <div class="options-row">
            <a href="javascript:void(0)" class="forgot-link" onclick="openForgotPasswordModal()">Forgot password?</a>
          </div>

          <!-- Submit Button -->
          <button type="submit" class="btn-portal-submit">Sign In</button>

        </form>

      </div>
    </main>

    <!-- Forgot Password Modal -->
    <div id="forgotPasswordModal" class="demo-modal-overlay">
      <div class="demo-modal" style="max-width: 410px; text-align: left;">
        <div class="demo-modal-icon" style="background: rgba(14, 164, 63, 0.2); color: #4ade80;">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
          </svg>
        </div>
        <h3 style="text-align: center; margin-bottom: 0.35rem; font-size: 1.35rem;">Reset Password</h3>
        <p style="text-align: center; font-size: 0.86rem; color: rgba(255, 255, 255, 0.82); line-height: 1.45; margin-bottom: 1.25rem;">
          Enter your registered Gmail or patient email address. We'll send you instructions to reset your password.
        </p>

        <div id="forgotStatusBox" style="display: none;" class="status-box"></div>

        <form id="forgotPasswordForm" onsubmit="handleForgotPassword(event)">
          <div class="form-group" style="margin-bottom: 1.15rem;">
            <label class="form-label" for="forgotEmailInput">Registered Email</label>
            <div class="input-wrapper">
              <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
              </svg>
              <input
                type="email"
                id="forgotEmailInput"
                class="form-control"
                placeholder="e.g. yourname@gmail.com"
                required
                autocomplete="email"
              />
            </div>
          </div>

          <button type="submit" id="btnForgotSubmit" class="btn-portal-submit" style="height: 44px; font-size: 0.98rem; margin-top: 0.5rem;">
            Send Reset Instructions
          </button>

          <button type="button" class="btn-secondary-link" style="background: none; border: none; width: 100%; cursor: pointer; margin-top: 0.9rem;" onclick="closeForgotPasswordModal()">
            Cancel & Return to Sign In
          </button>
        </form>
      </div>
    </div>

    <!-- Authenticated Modal -->
    <div id="demoModal" class="demo-modal-overlay">
      <div class="demo-modal">
        <div class="demo-modal-icon">
          <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
          </svg>
        </div>
        <h3>Welcome Back</h3>
        <p>You have successfully signed in to the MMWGH Patient Portal.</p>
        <button type="button" class="btn-close-modal" onclick="proceedToBooking()">
          Continue
        </button>
      </div>
    </div>

    <script>
      function togglePassVisibility() {
        const passInput = document.getElementById('patientPassInput');
        const eyeIcon = document.getElementById('eyeIcon');
        if (passInput.type === 'password') {
          passInput.type = 'text';
          eyeIcon.innerHTML = `
            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
            <line x1="1" y1="1" x2="23" y2="23"></line>
          `;
        } else {
          passInput.type = 'password';
          eyeIcon.innerHTML = `
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
            <circle cx="12" cy="12" r="3"></circle>
          `;
        }
      }

      async function handleSampleLogin(e) {
        e.preventDefault();
        const email = document.getElementById('patientEmailInput').value.trim();
        const password = document.getElementById('patientPassInput').value;
        const submitBtn = document.querySelector('.btn-portal-submit');

        submitBtn.disabled = true;
        submitBtn.innerText = 'Signing In...';

        try {
          const res = await fetch('/sdn/api/patient_auth.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password })
          });
          const data = await res.json();

          if (data.success) {
            document.querySelector('#demoModal h3').innerText = 'Welcome Back, ' + (data.name || 'Patient');
            document.querySelector('#demoModal p').innerText = 'You have successfully signed in to the MMWGH Patient Portal.';
            document.getElementById('demoModal').classList.add('active');
          } else if (data.unverified) {
            alert('Account Not Verified: ' + data.message + '\n\nPlease check your email for the verification link.');
          } else {
            alert(data.message || 'Invalid credentials. Please verify your email and password.');
          }
        } catch (err) {
          alert('Login server connection failed. Please try again.');
        } finally {
          submitBtn.disabled = false;
          submitBtn.innerText = 'Sign In';
        }
      }

      function proceedToBooking() {
        window.location.href = '/sdn/pages/patient-dashboard.php';
      }

      /* ── Forgot Password Modal Handlers ── */
      function openForgotPasswordModal() {
        const loginEmail = document.getElementById('patientEmailInput').value.trim();
        const forgotInput = document.getElementById('forgotEmailInput');
        if (loginEmail && !forgotInput.value) {
          forgotInput.value = loginEmail;
        }
        const statusBox = document.getElementById('forgotStatusBox');
        statusBox.style.display = 'none';
        document.getElementById('forgotPasswordModal').classList.add('active');
        setTimeout(() => forgotInput.focus(), 120);
      }

      function closeForgotPasswordModal() {
        document.getElementById('forgotPasswordModal').classList.remove('active');
      }

      async function handleForgotPassword(e) {
        e.preventDefault();
        const email = document.getElementById('forgotEmailInput').value.trim();
        const btn = document.getElementById('btnForgotSubmit');
        const statusBox = document.getElementById('forgotStatusBox');

        statusBox.style.display = 'none';
        btn.disabled = true;
        btn.textContent = 'Sending Instructions...';

        try {
          const res = await fetch('/sdn/api/patient_forgot_password.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: email })
          });
          const data = await res.json();

          if (data.success) {
            statusBox.className = 'status-box success';
            let msg = '<strong>Success!</strong> ' + data.message;
            if (data.dev_link) {
              msg += `<div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid rgba(255,255,255,0.2);"><small>Testing Direct Link:</small><br><a href="${data.dev_link}" style="color: #6ee7b7; font-weight: 600; text-decoration: underline;">Open Password Reset Form</a></div>`;
            }
            statusBox.innerHTML = msg;
            statusBox.style.display = 'block';
            document.getElementById('forgotPasswordForm').reset();
          } else {
            statusBox.className = 'status-box error';
            statusBox.innerHTML = data.message || 'Unable to send reset instructions. Please check your email.';
            statusBox.style.display = 'block';
          }
        } catch (err) {
          statusBox.className = 'status-box error';
          statusBox.innerHTML = 'A network connection error occurred. Please try again.';
          statusBox.style.display = 'block';
        } finally {
          btn.disabled = false;
          btn.textContent = 'Send Reset Instructions';
        }
      }

      window.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        const banner = document.getElementById('loginStatusBanner');
        const icon = document.getElementById('bannerIcon');
        const title = document.getElementById('bannerTitle');
        const msg = document.getElementById('bannerMsg');

        if (urlParams.get('registered') === '1') {
          banner.className = 'portal-status-banner banner-info';
          icon.innerHTML = `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"></rect><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path></svg>`;
          title.innerText = 'Registration Submitted!';
          msg.innerText = 'Please check your email inbox to verify your account before signing in.';
          banner.style.display = 'flex';
        } else if (urlParams.get('verify_error')) {
          banner.className = 'portal-status-banner banner-warning';
          icon.innerHTML = `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>`;
          title.innerText = 'Verification Notice';
          msg.innerText = 'This verification link is invalid or has expired. If your account is already active, please sign in.';
          banner.style.display = 'flex';
        } else if (urlParams.get('reset_success') === '1') {
          banner.className = 'portal-status-banner banner-success';
          icon.innerHTML = `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>`;
          title.innerText = 'Password Updated!';
          msg.innerText = 'Your password has been reset successfully. Please sign in with your new password.';
          banner.style.display = 'flex';
        }

        if (urlParams.get('action') === 'forgot') {
          openForgotPasswordModal();
        }
      });

      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
          document.getElementById('demoModal').classList.remove('active');
          document.getElementById('forgotPasswordModal').classList.remove('active');
        }
      });
    </script>
  </body>
</html>
