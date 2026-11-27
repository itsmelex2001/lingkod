<?php
session_start();
$assetVer = static function (string $relPath): string {
    $full = __DIR__ . $relPath;
    return is_file($full) ? (string)filemtime($full) : '1';
};
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Homepage | LINGKOD</title>
    <link rel="preload" as="image" href="/sdn/public/assets/hero-bg.webp?v=<?= $assetVer('/public/assets/hero-bg.webp') ?>" fetchpriority="high">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Inter:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&family=Outfit:wght@400;500;600;700;800;900&family=Montserrat:wght@400;500;600;700;800;900&family=Kaushan+Script&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/sdn/public/css/global.css?v=<?= $assetVer('/public/css/global.css') ?>" />
    <link rel="stylesheet" href="/sdn/public/css/navigation.css?v=<?= $assetVer('/public/css/navigation.css') ?>">
    <link rel="stylesheet" href="/sdn/public/css/landing.css?v=<?= $assetVer('/public/css/landing.css') ?>">
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <!-- AOS Library for Scroll Animations -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
      @media (max-width: 768px) {
        html, body {
          overflow-x: hidden;
          max-width: 100vw;
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
      /* Hide browser scrollbar while keeping scrolling active */
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
    <a href="#main-content" class="skip-to-content">Skip to main content</a>

    <!-- Splash Screen (Logo-Only Entrance) -->
    <div id="splash-screen" aria-hidden="true">
      <div class="splash-stage">
        <div class="splash-logo-card">
          <div class="splash-logo-art-wrap">
            <img
              src="/sdn/public/assets/v2_lingkod_logo_splash_opt.webp?v=<?= $assetVer('/public/assets/v2_lingkod_logo_splash_opt.webp') ?>"
              alt=""
              aria-hidden="true"
              class="splash-logo-img splash-layer-steth"
              width="640"
              height="640"
            />
            <img
              src="/sdn/public/assets/v2_lingkod_logo_splash_opt.webp?v=<?= $assetVer('/public/assets/v2_lingkod_logo_splash_opt.webp') ?>"
              alt=""
              aria-hidden="true"
              class="splash-logo-img splash-layer-ingk"
              width="640"
              height="640"
            />
            <img
              src="/sdn/public/assets/v2_lingkod_logo_splash_opt.webp?v=<?= $assetVer('/public/assets/v2_lingkod_logo_splash_opt.webp') ?>"
              alt=""
              aria-hidden="true"
              class="splash-logo-img splash-layer-d"
              width="640"
              height="640"
            />
            <img
              src="/sdn/public/assets/v2_lingkod_logo_splash_opt.webp?v=<?= $assetVer('/public/assets/v2_lingkod_logo_splash_opt.webp') ?>"
              alt="MMWGH Lingkod Logo"
              class="splash-logo-img splash-layer-original"
              width="640"
              height="640"
            />
            <div class="splash-sheen-mask">
              <div class="splash-sheen-beam"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <script src="/sdn/public/js/splash-inline.js?v=<?= $assetVer('/public/js/splash-inline.js') ?>"></script>

    <!-- NAVIGATION -->
    <nav class="navbar navbar-hero-solid" id="navbar" aria-label="Primary navigation">
      <div class="navbar-inner">
        <a class="logo" href="/sdn/index.php" title="Home">
          <img
            src="/sdn/public/assets/v3_lingkod_logo_nav.webp?v=<?= $assetVer('/public/assets/v3_lingkod_logo_nav.webp') ?>"
            alt="Lingkod Logo"
            class="logo-ver2-img"
            width="98"
            height="98"
          />
          <span class="logo-text">
            <span class="logo-title-top">Mariveles Mental Wellness and General Hospital</span>
            <span class="logo-title-bottom">LINGKOD</span>
          </span>
        </a>

        <ul class="nav-links" id="navLinks">
          <li><a href="/sdn/index.php" class="active-nav"><span data-i18n="nav_home">Home</span></a></li>
          <li><a href="#services-new"><span data-i18n="nav_services">Services</span></a></li>
          <li><a href="#about-new"><span data-i18n="nav_about">About</span></a></li>
          <li><a href="#location-new"><span data-i18n="nav_location">Location</span></a></li>
          <li><a href="#footer-new"><span data-i18n="nav_contact">Contact Us</span></a></li>
          <li>
            <a href="/sdn/pages/patient-login.php" target="_blank" rel="noopener noreferrer" class="btn-login btn-patient-portal"><span data-i18n="nav_portal">Patient Portal</span></a>
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
        <button type="button" class="hamburger" id="hamburger" aria-label="Toggle navigation menu" aria-expanded="false" aria-controls="navLinks">
          <span></span>
          <span></span>
          <span></span>
        </button>
      </div>
    </nav>

    <main id="main-content">
      <?php include 'hero-inline.php'; ?>
      <?php include 'services-inline.php'; ?>
      <?php include 'about-inline.php'; ?>
      <?php include 'location-inline.php'; ?>
    </main>
    <?php include 'footer-inline.php'; ?>
    <?php include 'chatbot-inline.php'; ?>
    <?php include __DIR__ . '/pages/patient-booking-modal.php'; ?>
    <link rel="stylesheet" href="/sdn/public/css/mobile-refine.css?v=<?= $assetVer('/public/css/mobile-refine.css') ?>">

    <script src="/sdn/public/js/utils.js?v=<?= $assetVer('/public/js/utils.js') ?>"></script>
    <script src="/sdn/public/js/navigation.js?v=<?= $assetVer('/public/js/navigation.js') ?>"></script>
    <script src="/sdn/public/js/landing.js?v=<?= $assetVer('/public/js/landing.js') ?>"></script>
    <script src="/sdn/public/js/landing-lang.js?v=<?= $assetVer('/public/js/landing-lang.js') ?>"></script>
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
