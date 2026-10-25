<?php session_start(); ?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Homepage | LINKOD</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;500;600;700;800;900&family=Montserrat:wght@400;500;600;700;800;900&family=Kaushan+Script&family=Caveat:wght@700&display=swap" rel="stylesheet">
    <style>
      @font-face {
        font-family: 'Avallon Alt';
        src: local('Avallon Alt'), local('Avallon-Alt'), local('AvallonAlt'), local('Avallon');
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
    </style>
  </head>
  <body class="splash-active">

    <!-- Splash Screen -->
    <div id="splash-screen">
      <div class="splash-typewriter">
        <span class="letter">L</span>
        <span class="letter">I</span>
        <span class="letter">N</span>
        <span class="letter">K</span>
        <span class="letter">o</span>
        <span class="letter">d</span>
        <span class="cursor">|</span>
      </div>
      <div class="splash-subtitle">Mariveles Mental Wellness and General Hospital</div>
    </div>

    <script src="/sdn/public/js/splash-inline.js?v=<?= time() ?>"></script>

    <!-- NAVIGATION -->
    <nav class="navbar" id="navbar">
      <div style="display: flex; align-items: center; justify-content: space-between; width: 100%; padding: 0 2rem; margin: 0 auto;">
        <a class="logo" href="/sdn/index.php" title="Home">
          <img src="/sdn/public/assets/mmwgh_logo.png" alt="Hospital Logo" />
          <span class="logo-text">Mariveles Mental Wellness<br>and General Hospital</span>
        </a>

        <ul class="nav-links" id="navLinks">
          <li><a href="/sdn/index.php" class="active-nav">Home</a></li>
          <li class="nav-dropdown">
            <a href="#services-new" class="nav-dropdown-toggle" style="cursor: default;">
              Services
              <svg class="nav-chevron" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="6 9 12 15 18 9"></polyline>
              </svg>
            </a>
            <div class="dropdown-content">
              <a href="/sdn/pages/department-select.php" style="color: #047023;">Book Appointment</a>
              <a href="/sdn/pages/schedules.php" style="color: #047023;">View Schedule</a>
            </div>
          </li>
          <li class="nav-dropdown">
            <a href="#about-new" class="nav-dropdown-toggle" style="cursor: default;">
              About
              <svg class="nav-chevron" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="6 9 12 15 18 9"></polyline>
              </svg>
            </a>
            <div class="dropdown-content">
              <a href="#footer-new" style="color: #047023;">Contact Us</a>
            </div>
          </li>
          <li><a href="#location-new">Location</a></li>
          <li>
            <button class="btn-login" onclick="window.location.href = '/sdn/pages/login.php'">Login</button>
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

    <!-- Svelte Component Mount Point -->
    <div id="app"></div>

    <!-- Compiled Svelte Assets with Cache-busting -->
    <script type="module" crossorigin src="/sdn/dist/assets/main.js?v=<?= time() ?>"></script>
    <link rel="stylesheet" crossorigin href="/sdn/dist/assets/main.css?v=<?= time() ?>">



    <script src="/sdn/public/js/utils.js"></script>
    <script src="/sdn/public/js/navigation.js"></script>
    <script src="/sdn/public/js/landing.js?v=<?= time() ?>"></script>
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
