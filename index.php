<?php
session_start();
$avallonDest = __DIR__ . '/public/assets/Avallon.ttf';
$avallonSrc  = 'C:/Users/andreaanque/.gemini/antigravity-ide/brain/40036de9-516b-4211-8b9d-07e615a3b4e8/scratch/avallon_online.ttf';
if (!file_exists($avallonDest) && file_exists($avallonSrc)) {
    @copy($avallonSrc, $avallonDest);
}
$splashLogoDest = __DIR__ . '/public/assets/v2_lingkod_logo_splash.png';
$splashLogoSrc  = 'C:/Users/andreaanque/Downloads/v2_lingkod_logo_splash.png';
if (!file_exists($splashLogoDest) && file_exists($splashLogoSrc)) {
    @copy($splashLogoSrc, $splashLogoDest);
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Homepage | LINGKOD</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;500;600;700;800;900&family=Montserrat:wght@400;500;600;700;800;900&family=Kaushan+Script&display=swap" rel="stylesheet">
    <style>
      @font-face {
        font-family: 'Avallon Alt';
        src: url('/sdn/public/assets/Avallon.ttf') format('truetype'),
             url('https://db.onlinewebfonts.com/t/471b859643b4c1b42b7e07e5c50becbb.woff2') format('woff2'),
             url('https://db.onlinewebfonts.com/t/471b859643b4c1b42b7e07e5c50becbb.woff') format('woff'),
             url('https://db.onlinewebfonts.com/t/471b859643b4c1b42b7e07e5c50becbb.ttf') format('truetype'),
             local('Avallon Alt'), local('Avallon-Alt'), local('AvallonAlt'), local('Avallon');
        font-weight: normal;
        font-style: normal;
        font-display: swap;
      }
      @font-face {
        font-family: 'Avallon';
        src: url('/sdn/public/assets/Avallon.ttf') format('truetype'),
             url('https://db.onlinewebfonts.com/t/471b859643b4c1b42b7e07e5c50becbb.woff2') format('woff2'),
             url('https://db.onlinewebfonts.com/t/471b859643b4c1b42b7e07e5c50becbb.woff') format('woff'),
             url('https://db.onlinewebfonts.com/t/471b859643b4c1b42b7e07e5c50becbb.ttf') format('truetype'),
             local('Avallon'), local('Avallon Regular'), local('Avallon-Regular');
        font-weight: normal;
        font-style: normal;
        font-display: swap;
      }
    </style>
    <link rel="stylesheet" href="/sdn/public/css/global.css?v=<?= time() ?>" />
    <link rel="stylesheet" href="/sdn/public/css/navigation.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/sdn/public/css/landing.css?v=<?= time() ?>">
    <!-- Load Svelte App CSS -->
    <link rel="stylesheet" href="/sdn/dist/assets/main.css?v=<?= time() ?>">
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <!-- AOS Library for Scroll Animations -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <!-- Injected Mobile Fixes to force override -->
    <style>
      @media (max-width: 768px) {
        html, body {
          overflow-x: hidden !important;
          width: 100vw !important;
          max-width: 100vw !important;
        }
        .hospital-name, [class*="hospital-name"], .hospital-heading {
          white-space: normal !important;
          word-wrap: break-word !important;
          word-break: break-word !important;
          text-align: left !important;
          font-size: 1.5rem !important;
          line-height: 1.2 !important;
        }
        .hero-actions, [class*="hero-actions"] {
          margin-left: 0 !important;
          width: 100% !important;
          display: flex !important;
          flex-direction: column !important;
          align-items: center !important;
        }
        .btn-action, [class*="btn-action"] {
          width: 100% !important;
          max-width: 100% !important;
          margin-left: 0 !important;
        }
        .hero-content-wrapper, [class*="hero-content-wrapper"] {
          padding-left: 1rem !important;
          padding-right: 1rem !important;
          width: 100% !important;
          overflow: hidden !important;
        }
        .fab-container, [class*="fab-container"] {
          right: 1rem !important;
          bottom: 1rem !important;
          transform: scale(0.85) !important;
        }
      }
      /* Hide wayfinding grid on Location page based on user request */
      .wayfinding-grid, [class*="wayfinding-grid"] {
        display: none !important;
      }
      /* Hide old Svelte containers before inline replacement */
      .hero-container, #services, #about, #location, footer.premium-footer, .fab-container, .chat-window {
        display: none !important;
      }
      /* Hide browser scrollbar to eliminate right-side white line while keeping scrolling active */
      html, body {
        scrollbar-width: none !important;
        -ms-overflow-style: none !important;
      }
      html::-webkit-scrollbar, body::-webkit-scrollbar, ::-webkit-scrollbar {
        display: none !important;
        width: 0 !important;
        height: 0 !important;
        background: transparent !important;
      }
    </style>
  </head>
  <body class="splash-active">

    <!-- Splash Screen (Logo-Only Cinematic Entrance) -->
    <div id="splash-screen">
      <div class="splash-stage">
        <!-- Main Logo Card with Smooth Animated Stethoscope -> Original Logo Transition -->
        <div class="splash-logo-card">
          <div class="splash-logo-art-wrap">
            <img
              src="/sdn/public/assets/v2_lingkod_logo_splash.png"
              alt=""
              aria-hidden="true"
              class="splash-logo-img splash-layer-steth"
            />
            <img
              src="/sdn/public/assets/v2_lingkod_logo_splash.png"
              alt=""
              aria-hidden="true"
              class="splash-logo-img splash-layer-ingk"
            />
            <img
              src="/sdn/public/assets/v2_lingkod_logo_splash.png"
              alt=""
              aria-hidden="true"
              class="splash-logo-img splash-layer-d"
            />
            <img
              src="/sdn/public/assets/v2_lingkod_logo_splash.png"
              alt="MMWGH Lingkod Logo"
              class="splash-logo-img splash-layer-original"
            />
            <div class="splash-sheen-mask">
              <div class="splash-sheen-beam"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <script src="/sdn/public/js/splash-inline.js?v=<?= time() ?>"></script>

    <!-- NAVIGATION -->
    <nav class="navbar" id="navbar">
      <div class="navbar-inner">
        <a class="logo" href="/sdn/index.php" title="Home">
          <img src="/sdn/public/assets/v3_lingkod_logo.png" alt="Lingkod Logo" class="logo-ver2-img" />
          <span class="logo-text">
            <span class="logo-title-top">Mariveles Mental Wellness and General Hospital</span>
            <span class="logo-title-bottom">LINGKOD</span>
          </span>
        </a>

        <ul class="nav-links" id="navLinks">
          <li><a href="/sdn/index.php" class="active-nav"><span data-i18n="nav_home">Home</span></a></li>
          <li class="nav-dropdown">
            <a href="#services-new" class="nav-dropdown-toggle" style="cursor: default;">
              <span data-i18n="nav_services">Services</span>
              <svg class="nav-chevron" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="6 9 12 15 18 9"></polyline>
              </svg>
            </a>
            <div class="dropdown-content">
              <a href="/sdn/pages/department-select.php" style="color: #047023;" data-i18n="nav_book">Book Appointment</a>
              <a href="/sdn/pages/schedules.php" target="_blank" rel="noopener noreferrer" style="color: #047023;" data-i18n="nav_schedule">View Schedule</a>
            </div>
          </li>
          <li><a href="#about-new"><span data-i18n="nav_about">About</span></a></li>
          <li><a href="#location-new"><span data-i18n="nav_location">Location</span></a></li>
          <li><a href="#footer-new"><span data-i18n="nav_contact">Contact Us</span></a></li>
          <li>
            <button class="btn-login btn-patient-portal" onclick="window.open('/sdn/pages/patient-login.php', '_blank', 'noopener,noreferrer')"><span data-i18n="nav_portal">Patient Portal</span></button>
          </li>
          <li class="lang-switch">
            <button type="button" class="lang-switch-btn" id="langSwitchBtn" aria-haspopup="listbox" aria-expanded="false" aria-label="Language">
              <span id="langSwitchCode">EN</span>
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
            <div class="lang-switch-menu" id="langSwitchMenu" role="listbox" hidden>
              <button type="button" class="lang-switch-option" data-lang="tl" role="option">Tagalog</button>
              <button type="button" class="lang-switch-option" data-lang="en" role="option">English</button>
            </div>
          </li>
        </ul>

        <!-- Mobile Hamburger Menu -->
        <div class="hamburger" id="hamburger">
          <span></span>
          <span></span>
          <span></span>
        </div>
      </div>
    </nav>

    <!-- Injected Pure HTML Overrides -->
    <?php include 'hero-inline.php'; ?>
    <?php include 'services-inline.php'; ?>
    <?php include 'about-inline.php'; ?>
    <?php include 'location-inline.php'; ?>
    <?php include 'footer-inline.php'; ?>
    <?php include 'chatbot-inline.php'; ?>
    <?php include __DIR__ . '/pages/patient-booking-modal.php'; ?>
    <link rel="stylesheet" href="/sdn/public/css/mobile-refine.css?v=<?= time() ?>">

    <!-- Svelte Component Mount Point -->
    <div id="app"></div>

    <!-- Compiled Svelte Assets with Cache-busting -->
    <script type="module" crossorigin src="/sdn/dist/assets/main.js?v=<?= time() ?>"></script>
    <link rel="stylesheet" crossorigin href="/sdn/dist/assets/main.css?v=<?= time() ?>">



    <script src="/sdn/public/js/utils.js"></script>
    <script src="/sdn/public/js/navigation.js?v=<?= time() ?>"></script>
    <script src="/sdn/public/js/landing.js?v=<?= time() ?>"></script>
    <script src="/sdn/public/js/landing-lang.js?v=<?= time() ?>"></script>
    <!-- AOS Library JS and Init -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        AOS.init({
          duration: 1000,
          easing: 'ease-in-out',
          once: false,
          mirror: true,
          offset: 50
        });
      });
    </script>
  </body>
</html>
