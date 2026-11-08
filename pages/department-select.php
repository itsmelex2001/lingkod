<?php 
session_start(); 
if (!empty($_SESSION['patient_logged_in'])) {
    header('Location: /sdn/pages/patient-book-appointment.php');
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Book an Appointment | MMWGH LINGKOD</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/sdn/public/css/global.css?v=<?= time() ?>" />
  <link rel="stylesheet" href="/sdn/public/css/navigation.css?v=<?= time() ?>" />
  
  <style>
    html, body {
      height: 100vh;
      overflow: hidden !important;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Inter', sans-serif;
      height: 100vh;
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
      max-width: 1360px;
      height: calc(100vh - 76px);
      margin: 0 auto;
      padding: 1.25rem 1.5rem 1.75rem;
      flex: 1;
      width: 95%;
      display: flex;
      flex-direction: column;
      justify-content: center;
      overflow: hidden;
    }

    /* ── Executive Page Header ── */
    .page-header {
      text-align: center;
      margin-bottom: 1.75rem;
      position: relative;
      flex-shrink: 0;
    }

    .page-header h1 {
      font-family: 'Poppins', sans-serif;
      font-size: 2.65rem;
      font-weight: 800;
      color: #064e3b;
      margin: 0 0 0.45rem 0;
      letter-spacing: -0.6px;
      line-height: 1.15;
    }

    .page-header p {
      font-size: 1rem;
      color: #475569;
      font-weight: 500;
      max-width: 660px;
      margin: 0 auto;
      line-height: 1.5;
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
      height: 185px;
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

    .dept-card-body {
      padding: 1.35rem 1.45rem;
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

    .dept-card-desc {
      font-size: 0.88rem;
      color: #64748b;
      line-height: 1.5;
      margin: 0 0 1.1rem 0;
      flex: 1;
    }

    .dept-card-footer {
      padding-top: 12px;
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
      padding: 1.85rem 2.2rem;
      max-height: calc(100vh - 115px);
      overflow-y: auto;
      scrollbar-width: none;
      box-shadow: 0 15px 45px -10px rgba(0, 0, 0, 0.07), 0 2px 8px rgba(0, 0, 0, 0.02);
      border: 1.5px solid #e2e8f0;
      animation: fadeIn 0.3s ease;
    }
    .services-detail::-webkit-scrollbar {
      display: none;
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
    <a href="/sdn/index.php" id="nav-back-link" class="btn-back" aria-label="Back" title="Back" style="margin: 0; width: 42px; height: 42px; border-radius: 50%; background: rgba(255, 255, 255, 0.18); border: 1px solid rgba(255, 255, 255, 0.35); color: white; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; cursor: pointer; transition: all 0.2s ease;">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
    </a>
  </nav>

  <div class="main-container relative z-10">

    <div class="page-header" id="page-header">
      <h1>Book an Appointment</h1>
      <p>Select a specialized clinical department below to book your appointment directly.</p>
    </div>

    <!-- Department Cards Grid -->
    <div id="tiles-grid" class="tiles-grid">
      <?php
        $departments = [
          [
            'id' => 'general',
            'name' => 'General Medical',
            'desc' => 'Comprehensive preventative health check-ups, family medicine, pediatric care, internal medicine, and minor surgical procedures.',
            'image' => '/sdn/public/assets/generalmngmnt_img(orig).jpeg',
            'link' => '/sdn/pages/regis-general.php?source=dept'
          ],
          [
            'id' => 'neurology',
            'name' => 'Neurology',
            'desc' => 'Specialized neurological clinical care, headache and migraine treatment, stroke rehabilitation, movement disorders, and diagnostic consultations.',
            'image' => '/sdn/public/assets/neurology_img(orig).jpeg',
            'link' => '/sdn/pages/regis-neurology.php?source=dept'
          ],
          [
            'id' => 'psychiatry',
            'name' => 'OPU Psychiatry',
            'desc' => 'Expert psychiatric consultations, adult and adolescent mental health, therapy, anxiety and depression management, and addiction care.',
            'image' => '/sdn/public/assets/psychiatry_img(orig).jpeg',
            'link' => '/sdn/pages/regis-psychiatry.php?source=dept'
          ]
        ];
      ?>
      <?php foreach($departments as $dept): ?>
        <div 
          class="dept-card" 
          onclick="handleDeptSelection('<?= htmlspecialchars($dept['link']) ?>')"
        >
          <div class="dept-card-media">
            <div class="dept-card-img" style="background-image: url('<?= $dept['image'] ?>');"></div>
            <div class="dept-card-media-overlay"></div>
          </div>
          <div class="dept-card-body">
            <div class="dept-card-title-row">
              <h3 class="dept-card-title"><?= $dept['name'] ?></h3>
            </div>
            <p class="dept-card-desc"><?= $dept['desc'] ?></p>
            <div class="dept-card-footer">
              <div class="dept-card-cta-btn">
                <span>Book Appointment</span>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

  </div>

  <?php include __DIR__ . '/patient-booking-modal.php'; ?>
  <script>
    function handleDeptSelection(deptUrl) {
      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.get('patient') === 'new') {
        window.location.href = deptUrl;
        return;
      }
      const newBtn = document.getElementById('pbcBtnNewPatient');
      if (newBtn) {
        newBtn.href = deptUrl;
      }
      openBookingConfirmModal();
    }
  </script>
</body>
</html>
