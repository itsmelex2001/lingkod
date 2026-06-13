<?php session_start(); ?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>View Schedules | SDN</title>
    <!-- Preconnect to Google Fonts for performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Outfit:wght@400;500;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="/public/css/global.css?v=9" />
    <link rel="stylesheet" href="/public/css/navigation.css?v=9" />
    <link rel="stylesheet" href="/public/css/schedules.css?v=9" />
    
    <!-- Compiled Svelte CSS -->
    <link rel="stylesheet" crossorigin href="/dist/assets/main.css">
  </head>
  <body>
    <nav class="navbar" id="navbar">
      <a class="logo" href="/index.php" title="Home">
        <img src="/public/assets/mmwgh_logo.png" alt="Hospital Logo" />
        <span class="logo-text">MMWGH LINKOD</span>
      </a>
    </nav>

    <!-- Svelte Mount Point -->
    <div id="schedules-app"></div>

    <!-- Compiled Svelte JS -->
    <script type="module" crossorigin src="/dist/assets/main.js"></script>
  </body>
</html>
