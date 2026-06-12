<?php session_start(); ?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Homepage | LINKOD</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;500;600;700;800&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/sdn/public/css/global.css?v=9" />
    <link rel="stylesheet" href="/sdn/public/css/navigation.css?v=9" />
    <link rel="stylesheet" href="/sdn/public/css/landing.css?v=16" />
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
  </head>
  <body>

    <!-- Splash Screen -->
    <div id="splash-screen">
      <svg class="splash-logo-svg" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
        <!-- Dark Green Link (Top & Left) -->
        <path class="dark-green" d="M 60 52 L 60 16 Q 60 10 54 10 L 46 10 Q 40 10 40 16 L 40 40 L 16 40 Q 10 40 10 46 L 10 54 Q 10 60 16 60 L 52 60" stroke="#256c1a" stroke-width="3" />
        <!-- Light Green Link (Bottom & Right) -->
        <path class="light-green" d="M 40 48 L 40 84 Q 40 90 46 90 L 54 90 Q 60 90 60 84 L 60 60 L 84 60 Q 90 60 90 54 L 90 46 Q 90 40 84 40 L 48 40" stroke="#8bc34a" stroke-width="3" />
      </svg>
    </div>

    <script src="/sdn/public/js/splash-inline.js"></script>

    <!-- NAVIGATION -->
    <nav class="navbar" id="navbar">
      <div style="display: flex; align-items: center; justify-content: space-between; width: 100%; padding: 0 2rem; margin: 0 auto;">
        <a class="logo" href="/sdn/index.php" title="Home">
          <img src="/sdn/public/assets/mmwgh_logo.png" alt="Hospital Logo" />
          <span class="logo-text">Mariveles Mental Wellness and General Hospital LINKOD</span>
        </a>

        <ul class="nav-links" id="navLinks">
          <li><a href="/sdn/index.php" class="active-nav">Home</a></li>
          <li><a href="#services">Services</a></li>
          <li><a href="#about">About</a></li>
          <li><a href="#location">Location</a></li>
          <li>
            <button class="btn-nav-secondary" onclick="window.location.href = '/sdn/pages/schedules.php'">View Schedule</button>
          </li>
          <li>
            <button class="btn-nav-primary" onclick="window.location.href = '/sdn/pages/department-select.php'">Book Appointment</button>
          </li>
          <li class="nav-dropdown">
            <button class="btn-signin">Sign In</button>
            <div class="dropdown-content">
              <a href="/sdn/pages/login.php?role=staff">as Medical Staff</a>
              <a href="/sdn/pages/login.php?role=admin">as Administrator</a>
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

    <!-- Svelte Component Mount Point -->
    <div id="app"></div>
    <!-- Compiled Svelte Assets -->
    <script type="module" crossorigin src="/sdn/dist/assets/main.js"></script>
    <link rel="stylesheet" crossorigin href="/sdn/dist/assets/main.css">



    <script src="/sdn/public/js/utils.js"></script>
    <script src="/sdn/public/js/navigation.js"></script>
    <script src="/sdn/public/js/landing.js"></script>
  </body>
</html>
