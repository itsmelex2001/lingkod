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
    
    <link rel="stylesheet" href="/sdn/public/css/global.css?v=<?= time() ?>" />
    <link rel="stylesheet" href="/sdn/public/css/navigation.css?v=<?= time() ?>" />
    <link rel="stylesheet" href="/sdn/public/css/schedules.css?v=<?= time() ?>" />
    
    <!-- Compiled Svelte CSS -->
    <link rel="stylesheet" crossorigin href="/sdn/dist/assets/main.css">
  </head>
  <body>
    <nav class="navbar relative z-10" id="navbar" style="border-bottom: 1px solid rgba(0,0,0,0.05); justify-content: flex-start;">
      <a class="logo" href="/sdn/index.php" title="Home">
        <img src="/sdn/public/assets/mmwgh_logo.png" alt="Hospital Logo" />
        <span class="logo-text">MMWGH LINKOD</span>
      </a>
    </nav>

    <!-- Svelte Mount Point -->
    <div id="schedules-app"></div>

    <!-- Compiled Svelte JS -->
    <script type="module" crossorigin src="/sdn/dist/assets/main.js"></script>
  </body>
</html>
