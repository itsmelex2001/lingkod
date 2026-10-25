<?php session_start(); ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Book an Appointment | MMWGH LINKod</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/sdn/public/css/global.css?v=<?= time() ?>" />
  <link rel="stylesheet" href="/sdn/public/css/navigation.css?v=<?= time() ?>" />
  
  <style>
    body {
      font-family: 'Inter', sans-serif;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      position: relative;
      background: #f4fbf7;
      z-index: 0;
      color: #1e293b;
    }
    
    .page-bg-overlay {
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: url('/sdn/public/assets/bookanappointment_bg.png') center bottom / cover no-repeat;
      opacity: 0.85;
      z-index: -1;
      mask-image: linear-gradient(to bottom, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0.5) 40%, black 100%);
      -webkit-mask-image: linear-gradient(to bottom, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0.5) 40%, black 100%);
    }

    .main-container {
      max-width: 1400px;
      margin: 1.5rem auto 4rem;
      padding: 0 1.5rem;
      flex: 1;
      width: 95%;
    }

    /* ── Executive Page Header ── */
    .page-header {
      text-align: center;
      margin-bottom: 2.75rem;
      position: relative;
    }
    
    .header-badge-pill {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: #e6f7ef;
      border: 1px solid #a7f3d0;
      color: #065f46;
      padding: 5px 14px;
      border-radius: 30px;
      font-size: 0.78rem;
      font-weight: 700;
      letter-spacing: 0.6px;
      text-transform: uppercase;
      margin-bottom: 12px;
      box-shadow: 0 2px 6px rgba(16, 185, 129, 0.1);
    }

    .header-badge-pill .pulse-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: #10b981;
      box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.25);
      animation: pulseAnim 2s infinite ease-in-out;
    }

    @keyframes pulseAnim {
      0%, 100% { transform: scale(1); opacity: 1; }
      50% { transform: scale(1.3); opacity: 0.7; }
    }

    .page-header h1 {
      font-family: 'Poppins', sans-serif;
      font-size: 2.85rem;
      font-weight: 800;
      color: #064e3b;
      margin: 0 0 0.5rem 0;
      letter-spacing: -0.6px;
      line-height: 1.15;
    }

    .page-header p {
      font-size: 1.05rem;
      color: #475569;
      font-weight: 500;
      max-width: 680px;
      margin: 0 auto 1.25rem;
      line-height: 1.5;
    }

    .header-features-strip {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 16px;
      flex-wrap: wrap;
    }

    .feat-chip {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: rgba(255, 255, 255, 0.85);
      backdrop-filter: blur(8px);
      border: 1px solid #cbd5e1;
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 0.82rem;
      font-weight: 600;
      color: #334155;
      box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    }

    /* ── Professional Department Cards Grid ── */
    .tiles-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 2rem;
    }

    .dept-card {
      background: #ffffff;
      border-radius: 20px;
      border: 1.5px solid #e2e8f0;
      overflow: hidden;
      cursor: pointer;
      display: flex;
      flex-direction: column;
      box-shadow: 0 8px 25px -5px rgba(0, 0, 0, 0.05), 0 2px 6px rgba(0, 0, 0, 0.02);
      transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
      position: relative;
    }

    .dept-card:hover {
      transform: translateY(-8px);
      border-color: #10b981;
      box-shadow: 0 20px 40px -10px rgba(16, 185, 129, 0.22), 0 4px 12px rgba(0, 0, 0, 0.04);
    }

    .dept-card-media {
      position: relative;
      height: 200px;
      overflow: hidden;
      background: #0f172a;
    }

    .dept-card-img {
      position: absolute;
      inset: 0;
      background-size: cover;
      background-position: center;
      transition: transform 0.4s ease;
    }

    .dept-card:hover .dept-card-img {
      transform: scale(1.06);
    }

    .dept-card-media-overlay {
      position: absolute;
      inset: 0;
      background: linear-gradient(180deg, rgba(6, 78, 59, 0.2) 0%, rgba(6, 78, 59, 0.75) 100%);
      transition: background 0.3s ease;
    }

    .dept-card:hover .dept-card-media-overlay {
      background: linear-gradient(180deg, rgba(6, 78, 59, 0.1) 0%, rgba(6, 78, 59, 0.65) 100%);
    }

    .dept-card-media .dept-tagline-badge {
      position: absolute;
      top: 14px;
      left: 14px;
      background: rgba(255, 255, 255, 0.92);
      backdrop-filter: blur(6px);
      color: #065f46;
      font-size: 0.72rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      padding: 4px 10px;
      border-radius: 20px;
      border: 1px solid rgba(255, 255, 255, 0.6);
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }

    .dept-card-media .dept-count-badge {
      position: absolute;
      bottom: 12px;
      right: 14px;
      background: rgba(16, 185, 129, 0.9);
      color: #ffffff;
      font-size: 0.75rem;
      font-weight: 700;
      padding: 4px 10px;
      border-radius: 20px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.15);
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }

    .dept-card-body {
      padding: 1.5rem;
      display: flex;
      flex-direction: column;
      flex: 1;
      background: #ffffff;
    }

    .dept-card-title-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      margin-bottom: 8px;
    }

    .dept-card-title {
      font-family: 'Poppins', sans-serif;
      font-size: 1.4rem;
      font-weight: 700;
      color: #0f172a;
      margin: 0;
      line-height: 1.25;
    }

    .dept-status-pill {
      font-size: 0.7rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.4px;
      background: #dcfce7;
      color: #166534;
      padding: 3px 8px;
      border-radius: 12px;
      border: 1px solid #bbf7d0;
      white-space: nowrap;
    }

    .dept-card-desc {
      font-size: 0.88rem;
      color: #64748b;
      line-height: 1.5;
      margin: 0 0 1.25rem 0;
      flex: 1;
    }

    .dept-card-footer {
      padding-top: 14px;
      border-top: 1px solid #f1f5f9;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .dept-card-cta-btn {
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 10px 16px;
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      color: #0f172a;
      font-size: 0.88rem;
      font-weight: 600;
      transition: all 0.2s ease;
    }

    .dept-card:hover .dept-card-cta-btn {
      background: linear-gradient(135deg, #059669 0%, #10b981 100%);
      color: #ffffff;
      border-color: #059669;
      box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }

    .dept-card-cta-btn svg {
      transition: transform 0.2s ease;
    }

    .dept-card:hover .dept-card-cta-btn svg {
      transform: translateX(4px);
    }

    /* ── Details View ── */
    .services-detail {
      background: #ffffff;
      border-radius: 22px;
      padding: 2.2rem 2.6rem;
      box-shadow: 0 15px 45px -10px rgba(0, 0, 0, 0.07), 0 2px 8px rgba(0, 0, 0, 0.02);
      border: 1.5px solid #e2e8f0;
      animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(8px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .dept-count-chip {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: #ecfdf5;
      border: 1px solid #a7f3d0;
      color: #065f46;
      font-size: 0.78rem;
      font-weight: 700;
      padding: 5px 12px;
      border-radius: 20px;
      letter-spacing: 0.4px;
      text-transform: uppercase;
    }

    .dept-count-chip .pulse-dot-sm {
      width: 7px;
      height: 7px;
      border-radius: 50%;
      background: #10b981;
    }

    .detail-header {
      display: flex;
      align-items: center;
      gap: 1.75rem;
      margin-bottom: 2rem;
      padding-bottom: 1.75rem;
      border-bottom: 2px solid #edf4f0;
    }

    .header-image {
      width: 96px;
      height: 96px;
      border-radius: 20px;
      background-size: cover;
      background-position: center;
      border: 2.5px solid #10b981;
      box-shadow: 0 6px 18px rgba(16, 185, 129, 0.22);
      flex-shrink: 0;
    }

    .dept-eyebrow-pill {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      font-size: 0.72rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.6px;
      color: #059669;
      background: #f0fdf4;
      border: 1px solid #bbf7d0;
      padding: 3px 8px;
      border-radius: 6px;
      margin-bottom: 4px;
    }

    .detail-header-text h2 {
      font-family: 'Poppins', sans-serif;
      font-size: 2.15rem;
      color: #064e3b;
      font-weight: 800;
      margin: 4px 0 6px 0;
      line-height: 1.2;
      letter-spacing: -0.4px;
    }

    .detail-header-sub {
      color: #64748b;
      font-size: 0.95rem;
      margin: 0;
      font-weight: 500;
      line-height: 1.45;
    }

    /* ── Services Grid: Modern, Minimalist & Patient-Friendly ── */
    .services-list {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 1.5rem;
    }

    .service-item {
      background: #ffffff;
      border: 1.5px solid #e2e8f0;
      border-radius: 20px;
      padding: 1.6rem 1.65rem;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      gap: 1.25rem;
      box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.04), 0 2px 4px rgba(0, 0, 0, 0.02);
      transition: all 0.28s cubic-bezier(0.16, 1, 0.3, 1);
      position: relative;
      overflow: hidden;
    }

    .service-item:hover {
      transform: translateY(-5px);
      border-color: #cbd5e1;
      box-shadow: 0 20px 35px -10px rgba(15, 23, 42, 0.09), 0 4px 12px rgba(16, 185, 129, 0.06);
    }

    /* Top Row: Service Icon & Title */
    .service-top-wrap {
      display: flex;
      align-items: flex-start;
      gap: 14px;
    }

    .service-icon-box {
      width: 44px;
      height: 44px;
      border-radius: 12px;
      background: #f0fdf4;
      border: 1px solid #bbf7d0;
      color: #059669;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      transition: transform 0.25s ease, background 0.25s ease;
    }

    .service-item:hover .service-icon-box {
      transform: scale(1.06);
      background: #dcfce7;
      color: #047857;
    }

    .service-title-area {
      flex: 1;
      min-width: 0;
    }

    .service-title-row {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 10px;
      margin-bottom: 3px;
    }

    .service-name {
      font-family: 'Poppins', sans-serif;
      font-size: 1.15rem;
      font-weight: 700;
      color: #0f172a;
      line-height: 1.35;
      margin: 0;
    }

    .service-status-pill {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      font-size: 0.7rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      background: #f0fdf4;
      color: #15803d;
      border: 1px solid #bbf7d0;
      padding: 3px 9px;
      border-radius: 20px;
      white-space: nowrap;
      flex-shrink: 0;
    }

    .status-live-dot {
      width: 6px;
      height: 6px;
      border-radius: 50%;
      background: #10b981;
      box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.3);
    }

    .service-dept-tag {
      font-size: 0.78rem;
      color: #64748b;
      margin: 0;
      font-weight: 500;
    }

    /* Doctor Card */
    .service-doctor-card {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      padding: 10px 14px;
      display: flex;
      align-items: center;
      gap: 12px;
      transition: background 0.2s ease, border-color 0.2s ease;
    }

    .service-item:hover .service-doctor-card {
      background: #f1f5f9;
      border-color: #cbd5e1;
    }

    .doc-avatar-chip {
      width: 34px;
      height: 34px;
      border-radius: 50%;
      background: #ffffff;
      border: 1.5px solid #cbd5e1;
      color: #059669;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      box-shadow: 0 2px 4px rgba(0,0,0,0.03);
    }

    .doc-info-col {
      flex: 1;
      min-width: 0;
      display: flex;
      flex-direction: column;
      gap: 2px;
    }

    .doc-label-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 6px;
    }

    .doc-label-tag {
      font-size: 0.68rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: #64748b;
    }

    .doc-badge-verified {
      font-size: 0.68rem;
      font-weight: 600;
      color: #047857;
      background: #d1fae5;
      padding: 1px 6px;
      border-radius: 6px;
    }

    .doc-name-text {
      font-size: 0.9rem;
      font-weight: 700;
      color: #0f172a;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .doc-spec-text {
      font-size: 0.78rem;
      color: #64748b;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    /* Bottom Row: Schedule & Action */
    .service-bottom-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      padding-top: 10px;
      border-top: 1px solid #f1f5f9;
      flex-wrap: wrap;
    }

    .service-sched-chip {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      font-size: 0.8rem;
      color: #475569;
      font-weight: 600;
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      padding: 6px 12px;
      border-radius: 10px;
    }

    .service-sched-chip svg {
      color: #059669;
      flex-shrink: 0;
    }

    .book-btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: #0f172a;
      color: #ffffff;
      border: none;
      padding: 9px 18px;
      border-radius: 10px;
      font-size: 0.88rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
      box-shadow: 0 2px 8px rgba(15, 23, 42, 0.15);
    }

    .book-btn:hover {
      background: #059669;
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(5, 150, 105, 0.35);
    }

    .book-btn svg {
      transition: transform 0.2s ease;
    }

    .book-btn:hover svg {
      transform: translateX(3px);
    }

    @media (max-width: 1100px) {
      .tiles-grid { grid-template-columns: 1fr; }
      .services-list { grid-template-columns: 1fr; }
    }

    @media (max-width: 768px) {
      .services-detail { padding: 1.5rem; }
      .detail-header { flex-direction: column; text-align: center; gap: 1rem; }
      .detail-header-text h4 { font-size: 1.8rem; }
      .service-bottom-row { flex-direction: column; align-items: stretch; gap: 10px; }
      .book-btn { justify-content: center; width: 100%; }
      .page-header h1 { font-size: 2.2rem; }
    }
  </style>
</head>
<body>
  
  <div class="page-bg-overlay"></div>

  <nav class="navbar relative z-10" style="border-bottom: 1px solid rgba(0,0,0,0.05); justify-content: flex-start;">
    <a href="/sdn/index.php" id="nav-back-link" class="btn-back" style="margin: 0; gap: 8px; color: white; display:flex; align-items:center; text-decoration:none; cursor: pointer;">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
      <span style="font-size: 1rem; font-weight: 600;">Back</span>
    </a>
  </nav>

  <div class="main-container relative z-10">

    <div class="page-header" id="page-header">
      <div class="header-badge-pill">
        <span class="pulse-dot"></span>
        MMWGH LINKod Appointment System
      </div>
      <h1>Book an Appointment</h1>
      <p>Select a specialized clinical department below to view available medical services, attending doctors, and consultation schedules.</p>
      <div class="header-features-strip">
        <span class="feat-chip">⚡ Instant Pre-Registration</span>
        <span class="feat-chip">🩺 Board-Certified Specialists</span>
        <span class="feat-chip">📋 Walk-in & Scheduled Consultations</span>
      </div>
    </div>

    <!-- Department Cards Grid -->
    <div id="tiles-grid" class="tiles-grid">
      <?php
        $departments = [
          [
            'id' => 'general',
            'name' => 'General Management',
            'tagline' => 'Primary Care & Outpatient',
            'desc' => 'Comprehensive preventative health check-ups, family medicine, pediatric care, internal medicine, and minor surgical procedures.',
            'image' => '/sdn/public/assets/generalmngmnt_img(orig).jpeg',
            'link' => '/sdn/pages/regis-general.php',
            'services' => [
              [
                'title' => 'Routine Check-ups & Preventative Care',
                'doctor' => 'Dr. Antonio Ramos',
                'spec' => 'Routine Check-ups & Preventative Care',
                'sched' => '08:30 AM - 04:00 PM'
              ],
              [
                'title' => 'Internal Medicine',
                'doctor' => 'Dr. Maria Santos',
                'spec' => 'Internal Medicine',
                'sched' => '08:00 AM - 04:00 PM'
              ],
              [
                'title' => 'Family Medicine & General Practice',
                'doctor' => 'Dr. Juan Dela Cruz',
                'spec' => 'Family Medicine & General Practice',
                'sched' => '08:00 AM - 04:00 PM'
              ],
              [
                'title' => 'Chronic Disease Management',
                'doctor' => 'Dr. Elena Fernandez',
                'spec' => 'Chronic Disease Management',
                'sched' => '09:00 AM - 04:30 PM'
              ],
              [
                'title' => 'Pediatric Care',
                'doctor' => 'Dr. Benjamin Cruz',
                'spec' => 'Pediatric Care',
                'sched' => '08:30 AM - 03:30 PM'
              ],
              [
                'title' => 'General Surgery & Minor Procedures',
                'doctor' => 'Dr. Ricardo De Leon',
                'spec' => 'General Surgery & Minor Procedures',
                'sched' => '10:00 AM - 04:00 PM'
              ]
            ]
          ],
          [
            'id' => 'neurology',
            'name' => 'Neurology',
            'tagline' => 'Neurological Sciences',
            'desc' => 'Specialized neurological clinical care, headache and migraine treatment, stroke rehabilitation, movement disorders, and diagnostic consultations.',
            'image' => '/sdn/public/assets/neurology_img(orig).jpeg',
            'link' => '/sdn/pages/regis-neurology.php',
            'services' => [
              [
                'title' => 'Headache & Migraine Management',
                'doctor' => 'Dr. Robert Tan',
                'spec' => 'Headache & Migraine Management',
                'sched' => '08:00 AM - 02:00 PM'
              ],
              [
                'title' => 'Stroke Rehabilitation',
                'doctor' => 'Dr. Catherine Gomez',
                'spec' => 'Stroke Rehabilitation',
                'sched' => '09:00 AM - 03:30 PM'
              ],
              [
                'title' => 'Epilepsy & Seizure Disorders',
                'doctor' => 'Dr. Dominic Bautista',
                'spec' => 'Epilepsy & Seizure Disorders',
                'sched' => '10:00 AM - 04:00 PM'
              ],
              [
                'title' => 'Movement Disorders',
                'doctor' => 'Dr. Karen Villanueva',
                'spec' => 'Movement Disorders',
                'sched' => '08:30 AM - 02:30 PM'
              ],
              [
                'title' => 'Clinical Neurology Consultation',
                'doctor' => 'Dr. Emilio Cruz',
                'spec' => 'Clinical Neurology',
                'sched' => '09:00 AM - 04:00 PM'
              ]
            ]
          ],
          [
            'id' => 'psychiatry',
            'name' => 'OPU Psychiatry',
            'tagline' => 'Outpatient Psychiatry',
            'desc' => 'Expert psychiatric consultations, adult and adolescent mental health, therapy, anxiety and depression management, and addiction care.',
            'image' => '/sdn/public/assets/psychiatry_img(orig).jpeg',
            'link' => '/sdn/pages/regis-psychiatry.php',
            'services' => [
              [
                'title' => 'Adult Psychiatry',
                'doctor' => 'Dr. Alan Magno',
                'spec' => 'Adult Psychiatry',
                'sched' => '08:00 AM - 02:30 PM'
              ],
              [
                'title' => 'Child & Adolescent Psychiatry',
                'doctor' => 'Dr. Sarah Reyes',
                'spec' => 'Child & Adolescent Psychiatry',
                'sched' => '01:00 PM - 05:00 PM'
              ],
              [
                'title' => 'Substance Abuse & Addiction Treatment',
                'doctor' => 'Dr. Gabriel Mendoza',
                'spec' => 'Substance Abuse & Addiction Treatment',
                'sched' => '08:00 AM - 03:00 PM'
              ],
              [
                'title' => 'Anxiety & Depression Management',
                'doctor' => 'Dr. Patricia Lim',
                'spec' => 'Anxiety & Depression Management',
                'sched' => '09:00 AM - 04:00 PM'
              ]
            ]
          ]
        ];
      ?>
      <?php foreach($departments as $dept): ?>
        <div 
          class="dept-card" 
          onclick="showDepartmentDetail(<?= htmlspecialchars(json_encode($dept)) ?>)"
        >
          <div class="dept-card-media">
            <div class="dept-card-img" style="background-image: url('<?= $dept['image'] ?>');"></div>
            <div class="dept-card-media-overlay"></div>
            <span class="dept-tagline-badge"><?= $dept['tagline'] ?></span>
            <span class="dept-count-badge">🩺 <?= count($dept['services']) ?> Services</span>
          </div>
          <div class="dept-card-body">
            <div class="dept-card-title-row">
              <h3 class="dept-card-title"><?= $dept['name'] ?></h3>
              <span class="dept-status-pill">Available</span>
            </div>
            <p class="dept-card-desc"><?= $dept['desc'] ?></p>
            <div class="dept-card-footer">
              <div class="dept-card-cta-btn">
                <span>View Services & Doctors</span>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Detail View (Hidden by default) -->
    <div id="services-detail" class="services-detail" style="display: none;">
      <div class="detail-header">
        <div id="detail-header-image" class="header-image"></div>
        <div class="detail-header-text" style="flex: 1;">
          <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 6px;">
            <div class="dept-eyebrow-pill" style="margin-bottom: 0;">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
              Specialized Clinical Care
            </div>
            <span id="detail-count-chip" class="dept-count-chip">
              <span class="pulse-dot-sm"></span>
              <span id="detail-count-text"></span>
            </span>
          </div>
          <h2 id="detail-header-title"></h2>
          <p class="detail-header-sub">Specialized clinical consultations, attending physicians, and appointment schedules.</p>
        </div>
      </div>
      
      <div id="detail-services-list" class="services-list">
        <!-- Populated by JS -->
      </div>
    </div>

  </div>

  <script>
    function escapeHtml(str) {
      if (!str) return '';
      return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    function getServiceIcon(title) {
      const t = (title || '').toLowerCase();
      if (t.includes('pediatric') || t.includes('child')) {
        return `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18z"></path><path d="M8 14s1.5 2 4 2 4-2 4-2"></path><line x1="9" y1="9" x2="9.01" y2="9"></line><line x1="15" y1="9" x2="15.01" y2="9"></line></svg>`;
      }
      if (t.includes('surgery') || t.includes('procedure')) {
        return `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="12" y1="18" x2="12" y2="12"></line><line x1="9" y1="15" x2="15" y2="15"></line></svg>`;
      }
      if (t.includes('brain') || t.includes('neuro') || t.includes('headache') || t.includes('stroke') || t.includes('epilepsy') || t.includes('movement')) {
        return `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.5 2A2.5 2.5 0 0 1 12 4.5v15a2.5 2.5 0 0 1-4.96.44 2.5 2.5 0 0 1-2.96-3.08 3 3 0 0 1-.34-5.58 2.5 2.5 0 0 1 1.32-4.24 2.5 2.5 0 0 1 4.44-5.04z"></path><path d="M14.5 2A2.5 2.5 0 0 0 12 4.5v15a2.5 2.5 0 0 0 4.96.44 2.5 2.5 0 0 0 2.96-3.08 3 3 0 0 0 .34-5.58 2.5 2.5 0 0 0-1.32-4.24 2.5 2.5 0 0 0-4.44-5.04z"></path></svg>`;
      }
      if (t.includes('psychiatry') || t.includes('counseling') || t.includes('therapy') || t.includes('mental') || t.includes('anxiety') || t.includes('depression') || t.includes('crisis') || t.includes('substance') || t.includes('addiction')) {
        return `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M12 6a6 6 0 0 1 6 6c0 3.3-2.7 6-6 6"></path><circle cx="12" cy="2" r="2"></circle></svg>`;
      }
      if (t.includes('chronic') || t.includes('internal') || t.includes('heart') || t.includes('pulse')) {
        return `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"></path></svg>`;
      }
      if (t.includes('preventative') || t.includes('check-up') || t.includes('routine')) {
        return `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><polyline points="9 12 11 14 15 10"></polyline></svg>`;
      }
      // General practice / clinic icon
      return `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4.8 2.3A.3.3 0 1 0 5 2H4a2 2 0 0 0-2 2v5a6 6 0 0 0 6 6v0a6 6 0 0 0 6-6V4a2 2 0 0 0-2-2h-1a.2.2 0 1 0 .3.3"></path><path d="M8 15v1a6 6 0 0 0 6 6v0a6 6 0 0 0 6-6v-4"></path><circle cx="20" cy="10" r="2"></circle></svg>`;
    }

    function showDepartmentDetail(dept) {
      document.getElementById('tiles-grid').style.display = 'none';
      document.getElementById('page-header').style.display = 'none';
      
      const detailView = document.getElementById('services-detail');
      
      document.getElementById('detail-header-image').style.backgroundImage = `url('${dept.image}')`;
      document.getElementById('detail-header-title').innerText = dept.name + ' Department';
      document.getElementById('detail-count-text').innerText = dept.services.length + ' Available Services';
      
      const list = document.getElementById('detail-services-list');
      list.innerHTML = '';
      
      dept.services.forEach(item => {
        const title = typeof item === 'string' ? item : item.title;
        const doctor = item.doctor || '';
        const spec = item.spec || '';
        const sched = item.sched || '';

        const divItem = document.createElement('div');
        divItem.className = 'service-item';

        divItem.innerHTML = `
          <!-- Top Row: Icon + Title + Live Status -->
          <div class="service-top-wrap">
            <div class="service-icon-box" title="${escapeHtml(title)}">
              ${getServiceIcon(title)}
            </div>
            <div class="service-title-area">
              <div class="service-title-row">
                <h3 class="service-name">${escapeHtml(title)}</h3>
                <span class="service-status-pill"><span class="status-live-dot"></span> Available</span>
              </div>
              <p class="service-dept-tag">MMWGH Clinical Care • ${escapeHtml(dept.name)}</p>
            </div>
          </div>

          <!-- Middle Row: Attending Physician Card -->
          <div class="service-doctor-card">
            <div class="doc-avatar-chip">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            </div>
            <div class="doc-info-col">
              <div class="doc-label-row">
                <span class="doc-label-tag">Attending Physician</span>
                <span class="doc-badge-verified">Specialist</span>
              </div>
              <div class="doc-name-text">${escapeHtml(doctor || 'Department Specialist')}</div>
              ${spec ? `<div class="doc-spec-text">${escapeHtml(spec)}</div>` : ''}
            </div>
          </div>

          <!-- Bottom Row: Consultation Schedule & Book CTA -->
          <div class="service-bottom-row">
            <div class="service-sched-chip">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
              <span>Consultation: <strong>${escapeHtml(sched || 'Regular Clinic Hours')}</strong></span>
            </div>
            <button class="book-btn" type="button">
              <span>Book Appointment</span>
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
            </button>
          </div>
        `;

        const btn = divItem.querySelector('.book-btn');
        btn.onclick = () => {
          const queryParams = new URLSearchParams({
            service: title,
            spec: spec || title,
            doctor: doctor,
            source: 'dept'
          });
          window.location.href = dept.link + '?' + queryParams.toString();
        };

        list.appendChild(divItem);
      });
      
      detailView.style.display = 'block';
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    
    function hideDepartmentDetail() {
      document.getElementById('services-detail').style.display = 'none';
      document.getElementById('tiles-grid').style.display = 'grid';
      document.getElementById('page-header').style.display = 'block';
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Nav Back Link handles detail view closure or home redirection
    const navBackLink = document.getElementById('nav-back-link');
    if (navBackLink) {
      navBackLink.addEventListener('click', function(e) {
        const detailView = document.getElementById('services-detail');
        if (detailView && detailView.style.display !== 'none') {
          e.preventDefault();
          hideDepartmentDetail();
        }
      });
    }
  </script>
</body>
</html>
