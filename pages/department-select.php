<?php session_start(); ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Select Department | Registration</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/sdn/public/css/global.css?v=9" />
  <link rel="stylesheet" href="/sdn/public/css/navigation.css?v=9" />
  <link rel="stylesheet" href="/sdn/public/css/department-select.css?v=11" />
</head>
<body>
  
  <nav class="navbar" style="border-bottom: 1px solid rgba(255,255,255,0.1); justify-content: flex-start;">
    <a href="/sdn/index.php" class="btn-back" style="margin: 0; gap: 8px;">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
      <span style="font-size: 1rem; font-weight: 500;">Back</span>
    </a>
  </nav>

  <div class="select-container">

    <div class="select-header">
      <h1>Book an Appointment</h1>
      <p>Please select the department you wish to register for.</p>
    </div>

    <div class="dept-grid">
      <!-- General Management -->
      <a href="regis-general.php" class="dept-card">
        <div class="icon-wrapper">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <!-- Medical Cross/Activity -->
            <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
          </svg>
        </div>
        <h3>General Management</h3>
        <p>Comprehensive care for common illnesses and overall health management.</p>
      </a>

      <!-- Neurology -->
      <a href="regis-neurology.php" class="dept-card">
        <div class="icon-wrapper">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <!-- Brain -->
            <path d="M9.5 2A2.5 2.5 0 0 1 12 4.5v15a2.5 2.5 0 0 1-4.96.44 2.5 2.5 0 0 1-2.96-3.08 3 3 0 0 1-.34-5.58 2.5 2.5 0 0 1 1.32-4.24 2.5 2.5 0 0 1 1.98-3A2.5 2.5 0 0 1 9.5 2Z"/>
            <path d="M14.5 2A2.5 2.5 0 0 0 12 4.5v15a2.5 2.5 0 0 0 4.96.44 2.5 2.5 0 0 0 2.96-3.08 3 3 0 0 0 .34-5.58 2.5 2.5 0 0 0-1.32-4.24 2.5 2.5 0 0 0-1.98-3A2.5 2.5 0 0 0 14.5 2Z"/>
          </svg>
        </div>
        <h3>Neurology</h3>
        <p>Expert diagnostic and therapeutic services for neurological conditions.</p>
      </a>

      <!-- OPU Psychiatry -->
      <a href="regis-psychiatry.php" class="dept-card">
        <div class="icon-wrapper">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <!-- Mental Health / Heart Handshake / Sparkles -->
            <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
            <path d="M12 5 9.04 9.53a11.28 11.28 0 0 0-1.96 3.97"/>
          </svg>
        </div>
        <h3>OPU Psychiatry</h3>
        <p>Compassionate outpatient mental health care and psychiatric evaluations.</p>
      </a>
    </div>
  </div>

  
</body>
</html>
