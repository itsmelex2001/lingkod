<?php
session_start();
if (empty($_SESSION['user']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header('Location: /pages/login.php');
    exit;
}
$isStaff = ($_SESSION['role'] === 'staff');
$dashLink = $isStaff ? '/pages/staff-dashboard.php' : '/pages/admin-dashboard.php';
$portalName = $isStaff ? 'Staff Portal' : 'Admin Portal';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Portal | Patient Profile</title>
    <link rel="stylesheet" href="/public/css/global.css?v=9" />
    <link rel="stylesheet" href="/public/css/navigation.css?v=9" />
    <link rel="stylesheet" href="/public/css/dashboard.css" />
    <link rel="stylesheet" href="/public/css/patient-profile.css" />
    <!-- html2pdf for PDF generation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" integrity="sha512-GsLlZN/3F2ErC5ifS5QtgpiJtWd43JWSuIgh7mbzZ8zBps+dvLusV+eNQATqgA/HdeKFVgA5v3S/cIrLF7QnIg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
  </head>
  <body class="bg-light">

    <!-- ── Navbar ── -->
    <nav class="navbar" id="navbar">
      <a class="logo" href="<?php echo $dashLink; ?>">
        <svg class="logo-icon" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect width="32" height="32" rx="8" fill="#2ecc71" fill-opacity=".12"/>
          <path d="M16 7v18M7 16h18" stroke="#1b5e20" stroke-width="2.5" stroke-linecap="round"/>
          <circle cx="16" cy="16" r="4" fill="#2ecc71" fill-opacity=".3"/>
        </svg>
        <?php echo $portalName; ?>
      </a>
      <button class="hamburger" id="hamburger" aria-label="Toggle menu" aria-expanded="false">
        <span></span><span></span><span></span>
      </button>
      <ul class="nav-links" id="navLinks" role="list">
        <li><a href="<?php echo $dashLink; ?>" class="active-nav">Patient Management</a></li>
        <?php if (!$isStaff): ?>
        <li><a href="/pages/system-logs.php">System Logs</a></li>
        <?php endif; ?>
        <li>
          <button class="btn-logout">Logout</button>
        </li>
      </ul>
    </nav>

    <!-- ── Toast ── -->
    <div class="toast" id="successToast" role="alert" aria-live="assertive">
      <span class="toast-icon">✓</span>
      <span class="toast-msg" id="toastMsg">Action completed successfully.</span>
    </div>

    <!-- ── Page ── -->
    <main class="profile-page" id="profilePage">

      <!-- Back -->
      <a class="btn-back" href="<?php echo $dashLink; ?>">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
      </a>

      <!-- Skeleton / Loading -->
      <div class="profile-skeleton" id="profileSkeleton">
        <div class="skeleton-header">
          <div class="sk sk-avatar"></div>
          <div class="sk-lines">
            <div class="sk sk-line sk-lg"></div>
            <div class="sk sk-line sk-md"></div>
          </div>
        </div>
        <div class="sk sk-block"></div>
        <div class="sk sk-block"></div>
      </div>

      <!-- Content (hidden until loaded) -->
      <div class="profile-content" id="profileContent" hidden>

        <!-- ── Profile Header ── -->
        <div class="profile-header-card">
          <div class="profile-header-left">
            <div class="profile-avatar" id="profileAvatar">JD</div>
            <div class="profile-meta">
              <h1 class="profile-name" id="profileName">—</h1>
              <div class="profile-sub">
                <span class="patient-id" id="profileId">—</span>
                <span class="dot-sep">·</span>
                <span class="dept-tag" id="profileDept">—</span>
                <span class="dot-sep">·</span>
                <span class="profile-type-badge" id="profileType">—</span>
              </div>
            </div>
          </div>
          <div class="profile-header-right">
            <span class="status-badge" id="profileStatus">
              <span class="status-dot"></span><span id="profileStatusText">—</span>
            </span>
            <span class="profile-date-label">
              <svg viewBox="0 0 16 16" fill="none" width="13" height="13"><rect x="2" y="3" width="12" height="11" rx="2" stroke="currentColor" stroke-width="1.3"/><path d="M5 2v2M11 2v2M2 7h12" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
              Registered <span id="profileCreated">—</span>
            </span>
          </div>
        </div>

        <!-- ── Info Grid ── -->
        <div class="info-grid">

          <!-- Personal Information -->
          <div class="info-card">
            <div class="info-card-header">
              <svg viewBox="0 0 20 20" fill="none" width="16" height="16">
                <path d="M10 10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-7 8a7 7 0 0 1 14 0" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
              </svg>
              Personal Information
            </div>
            <div class="info-rows">
              <div class="info-row"><span class="info-label">Full Name</span><span class="info-val" id="infoName">—</span></div>
              <div class="info-row"><span class="info-label">Gender</span><span class="info-val" id="infoGender">—</span></div>
              <div class="info-row"><span class="info-label">Birthdate</span><span class="info-val" id="infoBirthdate">—</span></div>
              <div class="info-row"><span class="info-label">Age</span><span class="info-val" id="infoAge">—</span></div>
              <div class="info-row"><span class="info-label">Contact</span><span class="info-val" id="infoContact">—</span></div>
              <div class="info-row"><span class="info-label">Email</span><span class="info-val" id="infoEmail">—</span></div>
              <div class="info-row"><span class="info-label">Address</span><span class="info-val" id="infoAddress">—</span></div>
              <div class="info-row"><span class="info-label">With Companion</span><span class="info-val" id="infoCompanion">—</span></div>
            </div>
          </div>

          <!-- Appointment Information -->
          <div class="info-card">
            <div class="info-card-header">
              <svg viewBox="0 0 20 20" fill="none" width="16" height="16">
                <rect x="2" y="3" width="16" height="15" rx="2.5" stroke="currentColor" stroke-width="1.5"/>
                <path d="M6 2v2M14 2v2M2 8h16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
              </svg>
              Appointment Details
            </div>
            <div class="info-rows">
              <div class="info-row"><span class="info-label">Department</span><span class="info-val" id="apptDept">—</span></div>
              <div class="info-row"><span class="info-label">Appointment Date</span><span class="info-val" id="apptDate">—</span></div>
              <div class="info-row"><span class="info-label">Appointment Type</span><span class="info-val" id="apptType">—</span></div>
              <div class="info-row"><span class="info-label">Consultation Mode</span><span class="info-val" id="apptMode">—</span></div>
              <div class="info-row"><span class="info-label">Consultation Nature</span><span class="info-val" id="apptNature">—</span></div>
              <div class="info-row"><span class="info-label">Patient Type</span><span class="info-val" id="apptPatientType">—</span></div>
              <div class="info-row"><span class="info-label">Referral Source</span><span class="info-val" id="apptReferral">—</span></div>
              <div class="info-row"><span class="info-label">Assigned Doctor</span><span class="info-val" id="apptDoctor">—</span></div>
            </div>
          </div>

          <!-- Clinical Notes -->
          <div class="info-card info-card-wide">
            <div class="info-card-header">
              <svg viewBox="0 0 20 20" fill="none" width="16" height="16">
                <rect x="3" y="2" width="14" height="16" rx="2" stroke="currentColor" stroke-width="1.5"/>
                <path d="M7 7h6M7 11h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
              </svg>
              Clinical Information
            </div>
            <div class="info-rows">
              <div class="info-row info-row-block">
                <span class="info-label">Chief Complaints</span>
                <span class="info-val info-val-wrap" id="clinicComplaints">—</span>
              </div>
              <div class="info-row info-row-block">
                <span class="info-label">Illness Description</span>
                <span class="info-val info-val-wrap" id="clinicIllness">—</span>
              </div>
            </div>
          </div>



        </div><!-- /info-grid -->

        <!-- ── Assign Doctor ── -->
        <div class="assign-card" id="assignCard">
          <div class="info-card-header">
            <svg viewBox="0 0 20 20" fill="none" width="16" height="16">
              <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="1.5"/>
              <path d="M3 17a6 6 0 0 1 10.33-4.14M14 13v4m2-2h-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
            Assign Doctor
          </div>
          <div class="assign-row">
            <select class="assign-select" id="doctorSelect">
              <option value="">Select a doctor…</option>
            </select>
            <button class="btn-action approve-btn" id="assignBtn">Assign</button>
          </div>
        </div>


        <!-- ── Progress Notes ── -->
        <div class="notes-card">
          <div class="notes-header">
            <div class="info-card-header">
              <svg viewBox="0 0 20 20" fill="none" width="16" height="16">
                <path d="M4 6h12M4 10h8M4 14h5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
              </svg>
              Progress Notes
            </div>
            <span class="notes-count" id="notesCount">0 notes</span>
          </div>

          <!-- Add note -->
          <div class="add-note-wrap">
            <textarea class="note-textarea" id="noteInput" placeholder="Add a progress note…" rows="3"></textarea>
            <button class="btn-action approve-btn note-submit-btn" id="noteSubmitBtn">
              <svg viewBox="0 0 16 16" fill="none" width="13" height="13">
                <path d="M2 8h12M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              Add Note
            </button>
          </div>

          <!-- Notes list -->
          <div class="notes-list" id="notesList"></div>
          <div class="empty-state notes-empty" id="notesEmpty" hidden>
            <svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" width="48" height="48">
              <circle cx="32" cy="32" r="28" fill="#e8f8f0"/>
              <path d="M22 32h20M32 22v20" stroke="#2ecc71" stroke-width="2.5" stroke-linecap="round"/>
            </svg>
            <p class="empty-title">No notes yet</p>
            <p class="empty-sub">Add the first progress note above.</p>
          </div>
        </div><!-- /notes-card -->

      </div><!-- /profile-content -->

      <!-- Error state -->
      <div class="profile-error" id="profileError" hidden>
        <svg viewBox="0 0 64 64" fill="none" width="52" height="52">
          <circle cx="32" cy="32" r="28" fill="#fdecea"/>
          <path d="M32 20v14M32 40h.01" stroke="#e74c3c" stroke-width="2.5" stroke-linecap="round"/>
        </svg>
        <p class="empty-title">Patient not found</p>
        <p class="empty-sub" id="errorMsg">The requested patient could not be loaded.</p>
        <a href="/pages/admin-dashboard.php" class="btn-action approve-btn" style="margin-top:1rem;text-decoration:none;display:inline-block;">Return to Dashboard</a>
      </div>

    </main>

    <!-- Confirm Modal -->
    <div class="modal-overlay" id="confirmModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle" hidden>
      <div class="modal-card">
        <div class="modal-icon" id="modalIcon">
          <svg viewBox="0 0 24 24" fill="none"><path d="M12 9v4m0 4h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
        </div>
        <h3 class="modal-title" id="modalTitle">Confirm Action</h3>
        <p class="modal-body" id="modalBody">Are you sure?</p>
        <div class="modal-actions">
          <button class="btn-modal-cancel" id="modalCancel">Cancel</button>
          <button class="btn-modal-confirm" id="modalConfirm">Confirm</button>
        </div>
      </div>
    </div>

    <script src="/public/js/utils.js"></script>
    <script src="/public/js/navigation.js"></script>
    <script src="/public/js/patient-profile.js?v=9"></script>
  </body>
</html>
