<?php
session_start();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if (empty($_SESSION['user']) || $_SESSION['role'] !== 'staff') {
    header('Location: /sdn/pages/portal@admin$staff_2026.php');
    exit;
}

require_once __DIR__ . '/../api/config/db.php';

$userFullName     = 'Medical Staff';
$deptLabel        = !empty($_SESSION['department']) ? $_SESSION['department'] : 'Medical Staff';
$userId           = $_SESSION['user_id'] ?? null;
$username         = $_SESSION['user'] ?? '';
$staffProfileImg  = $_SESSION['profile_image'] ?? null;

try {
    $stmtUser = $pdo->prepare("SELECT id, username, full_name, department, role, status, profile_image FROM users WHERE username = ? LIMIT 1");
    $stmtUser->execute([$username]);
    $uRow = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if ($uRow) {
        $userId = $uRow['id'];
        $_SESSION['user_id'] = $userId;
        if (!empty($uRow['full_name'])) {
            $userFullName = $uRow['full_name'];
            $_SESSION['full_name'] = $uRow['full_name'];
        } else {
            $userFullName = ucwords(str_replace(['.', '_'], ' ', $username));
        }

        if (!empty($uRow['department'])) {
            $deptLabel = $uRow['department'];
            $_SESSION['department'] = $uRow['department'];
        }
        $staffProfileImg = $uRow['profile_image'] ?? null;
        $_SESSION['profile_image'] = $staffProfileImg;
    }
} catch (Exception $e) {
    if (!empty($_SESSION['full_name']) && $_SESSION['full_name'] !== 'null') {
        $userFullName = $_SESSION['full_name'];
    }
}

$_SESSION['last_activity'] = time();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

$nameParts = preg_split('/\s+/', trim($userFullName)) ?: [];
$staffInitials = '';
foreach (array_slice($nameParts, 0, 2) as $part) {
    if ($part !== '') {
        $staffInitials .= strtoupper(substr($part, 0, 1));
    }
}
if ($staffInitials === '') {
    $staffInitials = 'MS';
}
$firstName = explode(' ', trim($userFullName))[0] ?: 'Staff';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
    <script>
      (function () {
        const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const origFetch = window.fetch.bind(window);
        window.fetch = function (input, init) {
          const opts = init ? Object.assign({}, init) : {};
          const method = String(opts.method || 'GET').toUpperCase();
          const url = typeof input === 'string' ? input : (input && input.url ? input.url : '');
          const isSameOrigin = !/^https?:\/\//i.test(url) || url.startsWith(window.location.origin);
          if (token && isSameOrigin && ['POST', 'PUT', 'PATCH', 'DELETE'].includes(method)) {
            const headers = new Headers(opts.headers || {});
            if (!headers.has('X-CSRF-Token')) headers.set('X-CSRF-Token', token);
            opts.headers = headers;
          }
          return origFetch(input, opts);
        };
      })();
    </script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Portal | LINGKOD</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/sdn/public/css/staff-dashboard-clean.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/sdn/public/css/enterprise-portal.css?v=<?= time() ?>">
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js" defer></script>
</head>
<body>
    <div class="staff-app-container">
        <!-- Mobile Sidebar Backdrop -->
        <div class="staff-sidebar-backdrop" id="staffSidebarBackdrop" aria-hidden="true"></div>

        <!-- ── Left Sidebar ── -->
        <aside class="staff-sidebar" id="staffSidebar">
            <div class="staff-sidebar-top">
                <div class="sidebar-brand">
                    <div class="sidebar-brand-box">
                        <img src="/sdn/public/assets/v2_lingkod_logo_cropped.png" alt="LINGKOD">
                    </div>
                    <div class="brand-text-block">
                        <span class="brand-role-main">MEDICAL STAFF</span>
                        <span class="brand-dept-sub"><?= htmlspecialchars(strtoupper($deptLabel)) ?></span>
                    </div>
                    <button type="button" class="sidebar-toggle-btn" id="staffSidebarToggleBtn" aria-label="Collapse sidebar" aria-controls="staffSidebar" aria-expanded="true" title="Collapse sidebar">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                            <line x1="9" y1="3" x2="9" y2="21"></line>
                            <polyline points="15 9 12 12 15 15"></polyline>
                        </svg>
                    </button>
                    <button type="button" class="staff-sidebar-close-btn" id="staffSidebarCloseBtn" aria-label="Close navigation menu" title="Close menu">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>

                <nav class="sidebar-menu" aria-label="Staff navigation">
                    <div class="staff-nav-group">
                        <div class="staff-nav-label">DASHBOARD</div>
                        <button type="button" class="sidebar-nav-item active" data-view="overview" data-tooltip="Overview" aria-label="Overview">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="7" height="9" x="3" y="3" rx="1.5"></rect><rect width="7" height="5" x="14" y="3" rx="1.5"></rect><rect width="7" height="9" x="14" y="12" rx="1.5"></rect><rect width="7" height="5" x="3" y="16" rx="1.5"></rect></svg>
                            <span>Overview</span>
                        </button>
                    </div>

                    <div class="staff-nav-group">
                        <div class="staff-nav-label">PATIENT CARE</div>
                        <button type="button" class="sidebar-nav-item" data-view="consultation" data-tooltip="Appointment Queue" aria-label="Appointment Queue">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"></rect><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><path d="m9 14 2 2 4-4"></path></svg>
                            <span>Appointment Queue</span>
                            <span class="nav-badge" id="navQueueBadge" style="display:none;">0</span>
                        </button>
                        <button type="button" class="sidebar-nav-item" data-view="records" data-tooltip="Patient Records" aria-label="Patient Records">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path><path d="M14 2v4a2 2 0 0 0 2 2h4"></path><circle cx="10" cy="12" r="2"></circle><path d="M13.5 17a3.5 3.5 0 0 0-7 0"></path></svg>
                            <span>Patient Records</span>
                        </button>
                    </div>

                    <div class="staff-nav-group">
                        <div class="staff-nav-label">SCHEDULING</div>
                        <button type="button" class="sidebar-nav-item" data-view="schedules" data-tooltip="Calendar" aria-label="Calendar">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 2v4"></path><path d="M16 2v4"></path><rect width="18" height="18" x="3" y="4" rx="2"></rect><path d="M3 10h18"></path><path d="M8 14h.01"></path><path d="M12 14h.01"></path><path d="M16 14h.01"></path><path d="M8 18h.01"></path><path d="M12 18h.01"></path><path d="M16 18h.01"></path></svg>
                            <span>Calendar</span>
                        </button>
                    </div>
                </nav>
            </div>

            <div class="sidebar-footer">
                <button type="button" class="sidebar-nav-item" data-view="settings" data-tooltip="Settings" aria-label="Settings">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    <span>Settings</span>
                </button>
            </div>
        </aside>
        <script>
          (function () {
            const sidebar = document.getElementById('staffSidebar');
            const toggleBtn = document.getElementById('staffSidebarToggleBtn');
            if (!sidebar || !toggleBtn) return;
            const applyState = (collapsed) => {
              sidebar.classList.toggle('is-collapsed', collapsed);
              toggleBtn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
              const lbl = collapsed ? 'Expand sidebar' : 'Collapse sidebar';
              toggleBtn.setAttribute('aria-label', lbl);
              toggleBtn.setAttribute('title', lbl);
            };
            try {
              if (localStorage.getItem('lingkod_staff_sidebar_collapsed') === '1') {
                applyState(true);
              }
            } catch (e) {}
            toggleBtn.addEventListener('click', () => {
              const next = !sidebar.classList.contains('is-collapsed');
              applyState(next);
              try { localStorage.setItem('lingkod_staff_sidebar_collapsed', next ? '1' : '0'); } catch (e) {}
            });

            // Mobile off-canvas navigation drawer
            const backdrop = document.getElementById('staffSidebarBackdrop');
            const closeBtn = document.getElementById('staffSidebarCloseBtn');
            const setMobileDrawer = (open) => {
              const menuBtn = document.getElementById('staffMobileMenuBtn');
              sidebar.classList.toggle('is-mobile-open', open);
              if (backdrop) backdrop.classList.toggle('is-open', open);
              if (menuBtn) menuBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
            };
            document.addEventListener('click', (e) => {
              if (e.target.closest('#staffMobileMenuBtn')) {
                setMobileDrawer(!sidebar.classList.contains('is-mobile-open'));
                return;
              }
              if (e.target.closest('#staffSidebarCloseBtn') || e.target === backdrop) {
                setMobileDrawer(false);
                return;
              }
              if (e.target.closest('#staffSidebar .sidebar-nav-item') && window.innerWidth <= 768) {
                setMobileDrawer(false);
              }
            });
            document.addEventListener('keydown', (e) => {
              if (e.key === 'Escape' && sidebar.classList.contains('is-mobile-open')) {
                setMobileDrawer(false);
                const menuBtn = document.getElementById('staffMobileMenuBtn');
                if (menuBtn) menuBtn.focus();
              }
            });
            window.addEventListener('resize', () => {
              if (window.innerWidth > 768 && sidebar.classList.contains('is-mobile-open')) {
                setMobileDrawer(false);
              }
            });
          })();
        </script>

        <!-- ── Main Content Workspace ── -->
        <main class="staff-main-wrap">
            <header class="staff-header">
                <div class="staff-mobile-header-left">
                    <button type="button" class="staff-mobile-menu-btn" id="staffMobileMenuBtn" aria-label="Open navigation menu" aria-controls="staffSidebar" aria-expanded="false">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <line x1="3" y1="12" x2="21" y2="12"></line>
                            <line x1="3" y1="18" x2="21" y2="18"></line>
                        </svg>
                    </button>
                    <div class="staff-mobile-brand">
                        <span class="staff-mobile-brand-name">LINGKOD</span>
                        <span class="staff-mobile-brand-dept"><?= htmlspecialchars(ucwords(strtolower($deptLabel))) ?></span>
                    </div>
                </div>
                <div class="header-right-meta">
                    <div class="staff-notif-wrap" id="staffNotifContainer">
                        <button type="button" class="staff-notif-btn" id="staffNotifBtn" aria-label="Notifications" aria-haspopup="true" aria-expanded="false">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                            <span class="staff-notif-dot" id="staffNotifDot" style="display:none;"></span>
                        </button>
                        <div class="staff-notif-dropdown" id="staffNotifDropdown">
                            <div class="staff-notif-head">
                                <span>Pending Requests</span>
                                <button type="button" class="link-action-btn" onclick="switchView('consultation')">Open Queue</button>
                            </div>
                            <div class="staff-notif-list" id="staffNotifList">
                                <div class="staff-notif-empty">No pending requests.</div>
                            </div>
                        </div>
                    </div>

                    <div class="profile-container" id="staffProfileContainer">
                        <button type="button" class="user-avatar-btn staff-avatar-btn" id="staffProfileBtn" title="<?= htmlspecialchars($userFullName) ?>" aria-label="Account menu for <?= htmlspecialchars($userFullName) ?>" aria-haspopup="true" aria-expanded="false" aria-controls="staffProfileDropdown">
                            <span class="staff-avatar-wrap" aria-hidden="true">
                                <span class="user-avatar-circle" id="staffHeaderAvatarCircle">
                                    <?php if (!empty($staffProfileImg)): ?>
                                    <img src="<?= htmlspecialchars($staffProfileImg) ?>" alt="<?= htmlspecialchars($userFullName) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;">
                                    <?php else: ?>
                                    <?= htmlspecialchars($staffInitials) ?>
                                    <?php endif; ?>
                                </span>
                                <span class="user-avatar-status"></span>
                            </span>
                            <span class="staff-avatar-meta">
                                <span class="user-avatar-name" id="headerStaffName"><?= htmlspecialchars($userFullName) ?></span>
                                <span class="staff-avatar-sub"><?= htmlspecialchars(ucwords(strtolower($deptLabel))) ?></span>
                            </span>
                            <svg class="staff-avatar-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </button>
                        <div class="profile-dropdown" id="staffProfileDropdown">
                            <div class="profile-dd-head">
                                <div class="profile-dd-name"><?= htmlspecialchars($userFullName) ?></div>
                                <div class="profile-dd-sub"><?= htmlspecialchars(ucwords(strtolower($deptLabel))) ?> &bull; Medical Staff</div>
                            </div>
                            <button type="button" class="profile-dd-item" id="staffProfileSettings">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                                <span>Account Settings</span>
                            </button>
                            <a href="/sdn/pages/logout.php?portal=staff" class="profile-dd-item profile-dd-logout">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                                <span>Sign out</span>
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <div class="dashboard-canvas">
                <!-- ═════════ VIEW 1: OVERVIEW ═════════ -->
                <section id="view-overview" class="view-panel active">
                    <div class="view-header">
                        <div>
                            <h1 id="staffGreetingTitle">Good Morning, <?= htmlspecialchars($firstName) ?>!</h1>
                            <p>Here&#8217;s your patient services overview for today.</p>
                        </div>
                        <div class="view-header-actions">
                            <button type="button" class="btn-clean primary btn-new-walkin" onclick="openWalkinModal()"><span class="plus-mark">+</span> WALK-IN / UNSCHEDULED</button>
                        </div>
                    </div>

                    <!-- 3 Overview KPI Cards -->
                    <div class="overview-kpi-grid">
                        <div class="ov-kpi-card">
                            <div class="ov-kpi-content">
                                <div class="ov-kpi-label">TODAY&#8217;S APPOINTMENTS</div>
                                <div class="ov-kpi-value" id="kpiTotal">0</div>
                            </div>
                        </div>

                        <div class="ov-kpi-card">
                            <div class="ov-kpi-content">
                                <div class="ov-kpi-label">COMPLETED TODAY</div>
                                <div class="ov-kpi-value" id="kpiCompleted">0</div>
                            </div>
                        </div>

                        <div class="ov-kpi-card">
                            <div class="ov-kpi-content">
                                <div class="ov-kpi-label">CURRENTLY IN QUEUE</div>
                                <div class="ov-kpi-value" id="kpiQueue">0</div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Patient QR & Name Lookup Bar (Direct Dashboard Access) -->
                    <div class="ov-quick-lookup-card" style="background:#ffffff; border:1px solid #e5e7eb; border-radius:12px; padding:14px 20px; margin-bottom:20px; display:flex; flex-direction:row; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; box-shadow:0 1px 2px rgba(15,23,42,0.04);">
                        <div class="ov-quick-lookup-lead" style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                            <button type="button" class="btn-clean primary ov-qr-scan-btn" onclick="openDashboardQrScanner()" style="white-space:nowrap;">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 7V5a2 2 0 0 1 2-2h2"></path><path d="M17 3h2a2 2 0 0 1 2 2v2"></path><path d="M21 17v2a2 2 0 0 1-2 2h-2"></path><path d="M7 21H5a2 2 0 0 1-2-2v-2"></path><rect x="7" y="7" width="10" height="10" rx="1"></rect></svg>
                                <span>Scan Patient QR</span>
                            </button>
                            <div class="ov-quick-lookup-copy">
                                <div style="font-size:0.84rem; font-weight:600; color:#0f172a; line-height:1.25;">Quick Patient Lookup</div>
                                <div style="font-size:0.76rem; color:#64748b; line-height:1.3;">Scan a Digital ID QR code or search by patient name or ID.</div>
                            </div>
                        </div>
                        <div class="ov-quick-lookup-search" style="position:relative; display:flex; align-items:center; gap:8px; flex:0 1 360px; min-width:240px;">
                            <input type="search" id="overviewQuickLookup" name="ov_quick_lookup" class="form-control-clean" placeholder="Enter patient name or ID..." autocomplete="new-password" data-lpignore="true" onkeydown="if(event.key==='Enter'){event.preventDefault();runOverviewPatientLookup();}" style="flex:1; min-width:0; height:38px;">
                            <button type="button" class="btn-clean outline" onclick="runOverviewPatientLookup()" style="height:38px; white-space:nowrap;">Find Patient</button>
                            <div id="overviewLookupResults" role="listbox" aria-label="Matching patient records" style="display:none; position:absolute; top:calc(100% + 6px); left:0; right:0; background:#ffffff; border:1px solid #cbd5e1; border-radius:10px; box-shadow:0 12px 28px rgba(15,23,42,0.14); z-index:40; max-height:280px; overflow-y:auto;"></div>
                        </div>
                    </div>

                    <!-- Recent Appointment Requests -->
                    <div class="overview-requests-card">
                        <div class="ov-card-header">
                            <div>
                                <h2>Recent Appointment Requests</h2>
                                <p>Online requests awaiting staff confirmation.</p>
                            </div>
                        </div>
                        <div class="modern-table-scroll ov-table-scroll">
                            <table class="modern-table">
                                <thead class="modern-thead">
                                    <tr>
                                        <th>PATIENT NAME</th>
                                        <th>TIME REQUESTED</th>
                                        <th>STATUS</th>
                                        <th class="col-actions">ACTIONS</th>
                                    </tr>
                                </thead>
                                <tbody id="overviewRequestsTableBody">
                                    <tr><td colspan="4" class="modern-table-empty">Loading recent requests...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- ═════════ VIEW 2: APPOINTMENT QUEUE ═════════ -->
                <section id="view-consultation" class="view-panel">
                    <div class="view-header">
                        <div>
                            <h1>Appointment Queue</h1>
                            <p>Review patient bookings, confirm schedules, and manage clinic arrivals.</p>
                        </div>
                        <div class="view-header-actions">
                            <button type="button" class="btn-clean outline" onclick="switchView('schedules')">Doctor Schedules</button>
                            <button type="button" class="btn-clean primary" onclick="openWalkinModal()">+ Walk-in / Unscheduled</button>
                        </div>
                    </div>

                    <!-- Staff Patient Count Calendar (Click a day to filter queue) -->
                    <div class="content-card" id="staffQueueCalendarCard" style="padding:14px 18px; margin-bottom:14px;">
                        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:10px; flex-wrap:wrap;">
                            <div>
                                <h2 id="staffCalMonthTitle" style="font-size:0.95rem; font-weight:700; color:#0f172a; margin:0;">Patient Schedule Calendar</h2>
                                <p style="font-size:0.76rem; color:#64748b; margin:2px 0 0;">Click any day to view scheduled patients for that date.</p>
                            </div>
                            <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                                <button type="button" class="btn-clean outline sm" onclick="shiftStaffCalMonth(-1)">&larr; Prev</button>
                                <button type="button" class="btn-clean outline sm" onclick="setConsultDateFilterToday()">Today</button>
                                <button type="button" class="btn-clean outline sm" onclick="shiftStaffCalMonth(1)">Next &rarr;</button>
                                <input type="date" id="consultDateFilter" class="form-control-clean" style="width:155px; height:34px; font-size:0.8rem;" aria-label="Filter queue by date" onchange="setConsultDateFilter(this.value)">
                                <button type="button" class="btn-clean outline sm" id="btnClearConsultDate" style="display:none;" onclick="setConsultDateFilter('')">All Dates</button>
                            </div>
                        </div>
                        <div id="staffCalGrid" style="display:grid; grid-template-columns:repeat(7, minmax(0, 1fr)); gap:6px;"></div>
                    </div>

                    <!-- Consultation List Card -->
                    <div class="content-card">
                        <div class="card-toolbar">
                            <div class="toolbar-left">
                                <div class="filter-pills-group" role="group" aria-label="Filter queue by status">
                                    <button type="button" class="filter-pill-btn consult-pill-filter active" data-status="all">
                                        All <span id="pillCountAll" aria-live="polite" aria-atomic="true">0</span>
                                    </button>
                                    <button type="button" class="filter-pill-btn consult-pill-filter" data-status="for_approval">
                                        For Approval <span id="pillCountPending" aria-live="polite" aria-atomic="true">0</span>
                                    </button>
                                    <button type="button" class="filter-pill-btn consult-pill-filter" data-status="inquiry">
                                        Inquiries <span id="pillCountInquiry" aria-live="polite" aria-atomic="true">0</span>
                                    </button>
                                    <button type="button" class="filter-pill-btn consult-pill-filter" data-status="approved">
                                        Confirmed <span id="pillCountApproved" aria-live="polite" aria-atomic="true">0</span>
                                    </button>
                                    <button type="button" class="filter-pill-btn consult-pill-filter" data-status="in_clinic">
                                        In Clinic <span id="pillCountInClinic" aria-live="polite" aria-atomic="true">0</span>
                                    </button>
                                    <button type="button" class="filter-pill-btn consult-pill-filter" data-status="completed">
                                        Completed <span id="pillCountCompleted" aria-live="polite" aria-atomic="true">0</span>
                                    </button>
                                    <button type="button" class="filter-pill-btn consult-pill-filter" data-status="transferred">
                                        Transferred <span id="pillCountTransferred" aria-live="polite" aria-atomic="true">0</span>
                                    </button>
                                    <button type="button" class="filter-pill-btn consult-pill-filter" data-status="cancelled">
                                        Cancelled <span id="pillCountCancelled" aria-live="polite" aria-atomic="true">0</span>
                                    </button>
                                </div>
                            </div>
                            <div class="toolbar-right">
                                <div class="search-input-box">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                    <input type="search" id="consultSearchInput" name="consult_search_q" placeholder="Search patient name or ID..." aria-label="Search patient name or ID" autocomplete="new-password" data-lpignore="true" spellcheck="false">
                                </div>
                                <button type="button" class="btn-clean outline btn-icon-only" id="btnRefreshConsult" title="Refresh queue" aria-label="Refresh queue">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                                </button>
                            </div>
                        </div>

                        <div class="table-wrap">
                            <table class="clean-table consult-table">
                                <thead>
                                    <tr>
                                        <th>Patient</th>
                                        <th>Visit Schedule</th>
                                        <th>Department &amp; Complaint</th>
                                        <th>Attending Physician</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="consultationsTableBody">
                                    <tr><td colspan="6" class="table-empty-row">Loading consultations...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- ═════════ VIEW 3: PATIENT RECORDS (With Search & Sorting) ═════════ -->
                <section id="view-records" class="view-panel">
                    <div class="view-header">
                        <div>
                            <h1>Patient Records</h1>
                            <p>Search, sort, and inspect patient consultation histories and clinical records.</p>
                        </div>
                    </div>

                    <div class="content-card">
                        <div class="card-toolbar records-toolbar">
                            <div class="toolbar-left">
                                <div class="search-input-box records-search-box">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                    <input type="search" id="recordsSearchInput" name="records_search_q" placeholder="Search patient name, ID, physician, complaint..." aria-label="Search patient records" autocomplete="new-password" data-lpignore="true" spellcheck="false">
                                </div>
                            </div>
                            <div class="toolbar-right records-toolbar-actions">
                                <select id="recordsSortSelect" class="select-filter" aria-label="Sort records">
                                    <option value="date_desc">Sort: Newest First</option>
                                    <option value="date_asc">Sort: Oldest First</option>
                                    <option value="name_asc">Sort: Name (A – Z)</option>
                                    <option value="name_desc">Sort: Name (Z – A)</option>
                                </select>
                                <button type="button" class="btn-clean outline records-export-btn" onclick="exportRecordsCSV()">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                    <span>Export CSV</span>
                                </button>
                            </div>
                        </div>

                        <div class="table-wrap">
                            <table class="clean-table records-table">
                                <thead>
                                    <tr>
                                        <th>Patient</th>
                                        <th>Consult Date</th>
                                        <th>Department &amp; Complaint</th>
                                        <th>Attending Physician</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="recordsTableBody">
                                    <tr><td colspan="6" class="table-empty-row">Loading records...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- ═════════ VIEW 4: CALENDAR (Department Doctor Schedules) ═════════ -->
                <section id="view-schedules" class="view-panel">
                    <div class="view-header">
                        <div>
                            <h1>Calendar</h1>
                            <p><?= htmlspecialchars(ucwords(strtolower($deptLabel))) ?> &middot; Physician schedules and daily clinic load.</p>
                        </div>
                        <div class="view-header-actions">
                            <div class="sched-summary-pills">
                                <span class="sched-summary-pill available" id="schedSummaryAvailable">0 Available</span>
                                <span class="sched-summary-pill out" id="schedSummaryOut">0 Out</span>
                            </div>
                            <button type="button" class="btn-clean outline" onclick="loadData()">Refresh</button>
                        </div>
                    </div>

                    <div class="modern-table-shell" style="margin-bottom:14px;">
                        <div class="modern-table-toolbar" style="padding:12px 16px;">
                            <div class="modern-table-controls" style="width:100%;">
                                <div class="modern-search-box">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                    <input type="search" id="schedSearchInput" name="sched_search_q" placeholder="Search doctor or specialization..." autocomplete="new-password" data-lpignore="true" spellcheck="false">
                                </div>
                                <select id="schedSpecFilter" class="modern-select" aria-label="Filter by specialization">
                                    <option value="all">All Specializations</option>
                                </select>
                                <select id="schedStatusFilter" class="modern-select" aria-label="Filter by status">
                                    <option value="all">All Statuses</option>
                                    <option value="in">Available (IN)</option>
                                    <option value="out">Unavailable (OUT)</option>
                                </select>
                                <button type="button" class="modern-reset-btn" onclick="resetScheduleFilters()">Reset</button>
                            </div>
                        </div>
                    </div>

                    <div id="doctorSchedulesContainer" class="dept-schedules-stack">
                        <div class="dept-schedule-card">
                            <div class="modern-table-empty">Loading doctor schedules...</div>
                        </div>
                    </div>
                </section>

                <!-- ═════════ VIEW 3: ACCOUNT SETTINGS (Minimalist, No Profile Image) ═════════ -->
                <section id="view-settings" class="view-panel">
                    <div class="view-header">
                        <div>
                            <h1>Account Settings</h1>
                            <p>Manage your staff profile information and security credentials.</p>
                        </div>
                    </div>

                    <div class="settings-stack">
                        <!-- Profile Card -->
                        <div class="settings-card">
                            <div class="settings-card-header">
                                <span class="settings-card-title">Profile</span>
                                <span class="settings-card-sub">Personal identification and assigned hospital department</span>
                            </div>

                            <div style="display:flex; align-items:center; gap:16px; padding:12px 14px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:10px; margin-bottom:16px;">
                                <div id="staffSettingsAvatarPreview" style="width:56px; height:56px; border-radius:50%; background:#15803d; color:#ffffff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:1.05rem; overflow:hidden; flex-shrink:0;">
                                    <?php if (!empty($staffProfileImg)): ?>
                                    <img src="<?= htmlspecialchars($staffProfileImg) ?>" alt="<?= htmlspecialchars($userFullName) ?>" style="width:100%; height:100%; object-fit:cover; display:block;" />
                                    <?php else: ?>
                                    <?= htmlspecialchars($staffInitials ?? 'ST') ?>
                                    <?php endif; ?>
                                </div>
                                <div style="flex:1; min-width:0;">
                                    <div style="font-size:0.85rem; font-weight:600; color:#111827;">Profile Photo</div>
                                    <div style="font-size:0.75rem; color:#6b7280; margin-top:2px;">PNG, JPG, or WEBP up to 5MB.</div>
                                    <div style="display:flex; align-items:center; gap:8px; margin-top:8px;">
                                        <input type="file" id="staffSettingsAvatarInput" accept="image/png,image/jpeg,image/webp" hidden />
                                        <button type="button" class="btn-clean outline" style="padding:5px 12px; font-size:0.78rem;" onclick="document.getElementById('staffSettingsAvatarInput').click()">Upload Photo</button>
                                        <button type="button" class="btn-clean outline" id="staffSettingsAvatarRemoveBtn" style="padding:5px 12px; font-size:0.78rem; color:#b91c1c; <?= empty($staffProfileImg) ? 'display:none;' : '' ?>">Remove</button>
                                    </div>
                                </div>
                            </div>

                            <div class="settings-grid-2col">
                                <div class="form-group-clean">
                                    <label for="settingsFullName">Full name</label>
                                    <input type="text" id="settingsFullName" class="form-control-clean" value="<?= htmlspecialchars($userFullName) ?>">
                                </div>

                                <div class="form-group-clean">
                                    <label>Username</label>
                                    <input type="text" class="form-control-clean" value="<?= htmlspecialchars($username) ?>" readonly>
                                </div>

                                <div class="form-group-clean">
                                    <label>Assigned department</label>
                                    <input type="text" class="form-control-clean" value="<?= htmlspecialchars($deptLabel) ?>" readonly>
                                </div>

                                <div class="form-group-clean">
                                    <label>Account role</label>
                                    <input type="text" class="form-control-clean" value="Medical Staff" readonly>
                                </div>
                            </div>

                            <div>
                                <button type="button" class="btn-clean primary" id="btnSaveProfile">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                                    <span>Save changes</span>
                                </button>
                            </div>
                        </div>

                        <!-- Security Card -->
                        <div class="settings-card">
                            <div class="settings-card-header">
                                <span class="settings-card-title">Security</span>
                                <span class="settings-card-sub">Update your password to keep your staff account secure</span>
                            </div>

                            <div class="settings-grid-2col">
                                <div class="form-group-clean">
                                    <label for="settingsNewPassword">New password</label>
                                    <input type="password" id="settingsNewPassword" class="form-control-clean" placeholder="At least 6 characters">
                                </div>

                                <div class="form-group-clean">
                                    <label for="settingsConfirmPassword">Confirm new password</label>
                                    <input type="password" id="settingsConfirmPassword" class="form-control-clean" placeholder="Re-enter new password">
                                </div>
                            </div>

                            <div>
                                <button type="button" class="btn-clean primary" id="btnChangePassword">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                                    <span>Update password</span>
                                </button>
                            </div>
                        </div>

                        <!-- Patient QR ID Scanner Card -->
                        <div class="settings-card">
                            <div class="settings-card-header">
                                <span class="settings-card-title">Patient QR ID Scanner</span>
                                <span class="settings-card-sub">Scan a patient's Digital ID QR code with the camera or search by patient name</span>
                            </div>

                            <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:14px;">
                                <button type="button" class="btn-clean primary" id="btnStartQrScan">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 7V5a2 2 0 0 1 2-2h2"></path><path d="M17 3h2a2 2 0 0 1 2 2v2"></path><path d="M21 17v2a2 2 0 0 1-2 2h-2"></path><path d="M7 21H5a2 2 0 0 1-2-2v-2"></path><rect x="7" y="7" width="10" height="10" rx="1"></rect></svg>
                                    <span>Start Camera Scan</span>
                                </button>
                                <button type="button" class="btn-clean outline" id="btnStopQrScan" style="display:none;">
                                    <span>Stop Camera</span>
                                </button>
                                <div style="display:flex; align-items:center; gap:8px; margin-left:auto; flex-wrap:wrap;">
                                    <input type="search" id="manualQrPatientId" class="form-control-clean" placeholder="Search patient name" aria-label="Search patient name" style="width:230px;">
                                    <button type="button" class="btn-clean outline" id="btnManualQrLookup">Search</button>
                                </div>
                            </div>

                            <div id="staffQrReader" style="display:none; max-width:360px; border-radius:10px; overflow:hidden; border:1px solid #e5e7eb; margin-bottom:12px;"></div>
                            <div id="staffQrStatus" style="display:none; font-size:0.82rem; color:#4b5563; margin-bottom:10px;"></div>
                            <div id="staffQrResultCard" style="display:none; padding:16px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:10px;"></div>
                        </div>

                        <!-- Session Card -->
                        <div class="settings-card">
                            <div class="settings-row-item">
                                <div>
                                    <div class="settings-card-title">Active Session</div>
                                    <div class="settings-card-sub">Signed in as <?= htmlspecialchars($userFullName) ?> (<?= htmlspecialchars($deptLabel) ?>)</div>
                                </div>
                                <a href="/sdn/pages/logout.php?portal=staff" class="btn-clean danger-outline">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                                    <span>Sign out</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <!-- ── MODAL: Calendar Day Doctor Schedule ── -->
    <div id="staffCalDayModal" class="clean-modal-backdrop">
        <div class="clean-modal-box" style="max-width:860px; width:94%;">
            <div class="clean-modal-header">
                <div>
                    <h3 id="staffCalModalTitle">Doctor Schedule</h3>
                    <div id="staffCalModalSub" style="font-size:0.8rem; color:#64748b; margin-top:2px;"></div>
                </div>
                <button type="button" class="clean-modal-close" onclick="closeStaffCalDayModal()">&times;</button>
            </div>
            <div class="clean-modal-body" id="staffCalModalBody" style="max-height:70vh; overflow-y:auto;"></div>
        </div>
    </div>

    <!-- ── MODAL 1: Patient Details ── -->
    <div id="patientDetailsModal" class="clean-modal-backdrop">
        <div class="clean-modal-box">
            <div class="clean-modal-header">
                <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                    <h3 style="margin:0;">Patient Consultation Record</h3>
                    <span id="staffDetailsStatusBadge"></span>
                </div>
                <button type="button" class="clean-modal-close" onclick="closePatientDetailsModal()">&times;</button>
            </div>
            <div class="clean-modal-body" id="patientDetailsContent"></div>
            <div class="clean-modal-footer" style="justify-content:space-between; flex-wrap:wrap; gap:8px;">
                <div style="display:flex; align-items:center; flex-wrap:wrap; gap:8px;">
                    <button type="button" id="staffDetailsPrimaryBtn" class="btn-clean primary" style="display:none;"></button>
                    <button type="button" id="staffDetailsSlipBtn" class="btn-clean outline" onclick="openPatientSlipFromDetails()" style="display:none;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        <span id="staffDetailsSlipBtnLabel">Print Patient Slip</span>
                    </button>
                    <button type="button" id="staffDetailsMedCertBtn" class="btn-clean outline" onclick="printMedicalCertificateFromDetails()" style="display:none;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                        <span>Print Medical Certificate</span>
                    </button>
                    <button type="button" id="staffDetailsRxBtn" class="btn-clean outline" onclick="printPrescriptionFromDetails()" style="display:none;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                        <span>Print Prescription (Rx)</span>
                    </button>
                </div>
                <button type="button" class="btn-clean outline" onclick="closePatientDetailsModal()">Close</button>
            </div>
        </div>
    </div>

    <!-- ── MODAL 2: Clinical Note & Diagnosis ── -->
    <div id="clinicalNoteModal" class="clean-modal-backdrop">
        <div class="clean-modal-box" style="max-width: 580px;">
            <div class="clean-modal-header">
                <div>
                    <h3>Conclude Consultation</h3>
                    <p style="font-size:0.78rem; color:#64748b; margin-top:2px;" id="notePatientNameText"></p>
                </div>
                <button type="button" class="clean-modal-close" onclick="closeClinicalNoteModal()">&times;</button>
            </div>
            <div class="clean-modal-body" style="display:flex; flex-direction:column; gap:1rem; max-height:72vh; overflow-y:auto;">
                <input type="hidden" id="notePatientId">

                <!-- Vital Signs Section (Pre-Consultation / For Doctor) -->
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:0.85rem 1rem;">
                    <div style="font-size:0.76rem; font-weight:700; color:#0f172a; text-transform:uppercase; letter-spacing:0.03em; margin-bottom:0.6rem;">Vital Signs (Triage / For Doctor)</div>
                    <div style="display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:8px;">
                        <div class="form-group-clean" style="margin:0;">
                            <label for="vitalBp" style="font-size:0.72rem;">BP (mmHg)</label>
                            <input type="text" id="vitalBp" class="form-control-clean" placeholder="120/80">
                        </div>
                        <div class="form-group-clean" style="margin:0;">
                            <label for="vitalTemp" style="font-size:0.72rem;">Temp (°C)</label>
                            <input type="text" id="vitalTemp" class="form-control-clean" placeholder="36.5">
                        </div>
                        <div class="form-group-clean" style="margin:0;">
                            <label for="vitalPulse" style="font-size:0.72rem;">Pulse (bpm)</label>
                            <input type="text" id="vitalPulse" class="form-control-clean" placeholder="78">
                        </div>
                        <div class="form-group-clean" style="margin:0;">
                            <label for="vitalRr" style="font-size:0.72rem;">RR (/min)</label>
                            <input type="text" id="vitalRr" class="form-control-clean" placeholder="18">
                        </div>
                        <div class="form-group-clean" style="margin:0;">
                            <label for="vitalSpo2" style="font-size:0.72rem;">SpO2 (%)</label>
                            <input type="text" id="vitalSpo2" class="form-control-clean" placeholder="98">
                        </div>
                        <div class="form-group-clean" style="margin:0;">
                            <label for="vitalWeight" style="font-size:0.72rem;">Weight (kg)</label>
                            <input type="text" id="vitalWeight" class="form-control-clean" placeholder="60">
                        </div>
                    </div>
                </div>

                <div class="form-group-clean" style="position:relative;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                        <label for="noteDiagnosis" style="font-weight:700; font-size:0.78rem; text-transform:uppercase; color:#334155; margin:0;">
                            Diagnosis <span style="color:#e11d48;">*</span>
                        </label>
                    </div>
                    <div style="position:relative;">
                        <input type="text" id="noteDiagnosis" class="form-control-clean" placeholder="Type diagnosis (e.g. Schizophrenia, Hypertension, Migraine)..." autocomplete="off">
                        <div id="noteDiagnosisSpinner" style="display:none; position:absolute; right:12px; top:50%; transform:translateY(-50%); font-size:0.75rem; color:#059669; font-weight:600;">
                            Searching...
                        </div>
                    </div>
                    <div id="cdssDropdown" style="display:none; position:absolute; left:0; right:0; top:100%; margin-top:4px; max-height:220px; overflow-y:auto; background:#ffffff; border:1px solid #cbd5e1; border-radius:8px; box-shadow:0 12px 28px rgba(0,0,0,0.15); z-index:9999;"></div>
                </div>
                <div class="form-group-clean">
                    <label for="noteTreatment">Treatment Plan</label>
                    <textarea id="noteTreatment" class="form-control-clean" rows="2" placeholder="Recommended treatment regimen"></textarea>
                </div>

                <!-- Drug Prescription Section (Checkbox Multi-Select) -->
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:1rem; display:flex; flex-direction:column; gap:0.8rem;">
                    <div style="display:flex; align-items:center; justify-content:space-between;">
                        <span style="font-size:0.8rem; font-weight:700; color:#15803d; text-transform:uppercase; letter-spacing:0.03em;">Formulary Medicine &amp; Prescription</span>
                        <span style="font-size:0.72rem; color:#64748b;" id="dohDeptNotice">General Medicine</span>
                    </div>

                    <div id="noteSuggestedMedsWrap" style="display:none; flex-direction:column; gap:6px; background:#ffffff; border:1px solid #bbf7d0; border-radius:8px; padding:8px 10px;">
                        <div style="font-size:0.7rem; font-weight:700; color:#15803d; text-transform:uppercase; letter-spacing:0.03em;">Suggested Medications for Selected Diagnosis</div>
                        <div id="noteSuggestedMedsList" style="display:flex; flex-wrap:wrap; gap:6px;"></div>
                    </div>

                    <div class="form-group-clean">
                        <label>Select Medicine(s) / Drug(s)</label>
                        <div id="noteMedicineCheckboxes" style="max-height:150px; overflow-y:auto; background:#ffffff; border:1px solid #cbd5e1; border-radius:8px; padding:8px 10px; display:flex; flex-direction:column; gap:6px;"></div>
                        <select id="noteMedicineSelect" class="form-control-clean" style="display:none;" onchange="handleMedicineSelect(this.value)">
                            <option value="">Select drug / medicine...</option>
                        </select>
                    </div>

                    <div class="settings-grid-2col">
                        <div class="form-group-clean">
                            <label for="noteDosage">Dosage / Instructions</label>
                            <input type="text" id="noteDosage" class="form-control-clean" placeholder="e.g. 500mg, 1 tab BID after meals">
                        </div>
                        <div class="form-group-clean">
                            <label for="noteQuantity">Quantity</label>
                            <input type="text" id="noteQuantity" class="form-control-clean" placeholder="e.g. 30 tabs / 1 bottle">
                        </div>
                    </div>

                    <div class="form-group-clean">
                        <label for="notePrescription">Full Prescription Text</label>
                        <textarea id="notePrescription" class="form-control-clean" rows="2" placeholder="Prescription summary automatically formatted or manually edited"></textarea>
                    </div>
                </div>

                <!-- Optional Follow-Up Schedule (Visible to Patient) -->
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:0.85rem 1rem;">
                    <div style="font-size:0.76rem; font-weight:700; color:#0f172a; text-transform:uppercase; letter-spacing:0.03em; margin-bottom:0.55rem;">Follow-Up Schedule <span style="font-weight:500; color:#64748b; text-transform:none;">(Optional — visible on patient portal)</span></div>
                    <div class="settings-grid-2col">
                        <div class="form-group-clean" style="margin:0;">
                            <label for="noteFollowUpDate">Next Follow-Up Date</label>
                            <input type="date" id="noteFollowUpDate" class="form-control-clean" min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                        </div>
                        <div class="form-group-clean" style="margin:0;">
                            <label for="noteFollowUpNotes">Follow-Up Instructions</label>
                            <input type="text" id="noteFollowUpNotes" class="form-control-clean" placeholder="e.g. Return in 2 weeks for lab review">
                        </div>
                    </div>
                </div>
            </div>
            <div class="clean-modal-footer">
                <button type="button" class="btn-clean outline" onclick="closeClinicalNoteModal()">Cancel</button>
                <button type="button" class="btn-clean primary" onclick="submitClinicalNote()">Save &amp; Complete</button>
            </div>
        </div>
    </div>

    <!-- ── MODAL 3: Quick Add Walk-In ── -->
    <div id="walkinModal" class="clean-modal-backdrop">
        <div class="clean-modal-box">
            <div class="clean-modal-header">
                <div>
                    <h3>Walk-in / Unscheduled Patient</h3>
                    <p style="font-size:0.78rem; color:#64748b; margin-top:2px;">Admit face-to-face arrival to <?= htmlspecialchars($deptLabel) ?></p>
                </div>
                <button type="button" class="clean-modal-close" onclick="closeWalkinModal()">&times;</button>
            </div>
            <div class="clean-modal-body">
                <form id="walkinForm" onsubmit="event.preventDefault(); submitWalkinPatient();" style="display:flex; flex-direction:column; gap:1rem;">
                    <div class="form-group-clean">
                        <label for="walkinFullName">Full Name <span style="color:#e11d48;">*</span></label>
                        <input type="text" id="walkinFullName" class="form-control-clean" placeholder="Enter patient full name" required>
                    </div>
                    <div class="settings-grid-2col">
                        <div class="form-group-clean">
                            <label for="walkinGender">Gender <span style="color:#e11d48;">*</span></label>
                            <select id="walkinGender" class="form-control-clean" required>
                                <option value="">Select gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group-clean">
                            <label for="walkinBirthdate">Birthdate</label>
                            <input type="date" id="walkinBirthdate" class="form-control-clean" max="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                    <div class="settings-grid-2col">
                        <div class="form-group-clean">
                            <label for="walkinContact">Contact Number</label>
                            <input type="text" id="walkinContact" class="form-control-clean" placeholder="e.g., 09XX XXX XXXX">
                        </div>
                        <div class="form-group-clean">
                            <label for="walkinAddress">City / Address</label>
                            <input type="text" id="walkinAddress" class="form-control-clean" placeholder="Enter city or municipality">
                        </div>
                    </div>
                    <div class="form-group-clean">
                        <label for="walkinComplaint">Chief Complaint <span style="color:#e11d48;">*</span></label>
                        <textarea id="walkinComplaint" class="form-control-clean" rows="2" placeholder="Briefly describe symptoms or reason for visit..." required></textarea>
                    </div>
                </form>
            </div>
            <div class="clean-modal-footer">
                <button type="button" class="btn-clean outline" onclick="closeWalkinModal()">Cancel</button>
                <button type="button" class="btn-clean primary" onclick="submitWalkinPatient()">Register &amp; Admit</button>
            </div>
        </div>
    </div>

    <!-- ── MODAL 4: Doctors Status ── -->
    <div id="doctorStatusModal" class="clean-modal-backdrop">
        <div class="clean-modal-box" style="max-width: 500px;">
            <div class="clean-modal-header">
                <div>
                    <h3>Doctors Status</h3>
                    <p style="font-size:0.78rem; color:#64748b; margin-top:2px;">Update real-time physician availability</p>
                </div>
                <button type="button" class="clean-modal-close" onclick="closeDoctorStatusModal()">&times;</button>
            </div>
            <div class="clean-modal-body" id="doctorStatusList"></div>
            <div class="clean-modal-footer">
                <button type="button" class="btn-clean outline" onclick="closeDoctorStatusModal()">Done</button>
            </div>
        </div>
    </div>

    <!-- ── MODAL 5: Transfer Patient Department Modal ── -->
    <div id="transferPatientModal" class="clean-modal-backdrop">
        <div class="clean-modal-box" style="max-width: 520px;">
            <div class="clean-modal-header">
                <div>
                    <h3>Transfer Patient</h3>
                    <p style="font-size:0.78rem; color:#64748b; margin-top:2px;">Route patient appointment to another clinical department</p>
                </div>
                <button type="button" class="clean-modal-close" onclick="closeTransferModal()">&times;</button>
            </div>
            <div class="clean-modal-body" style="display:flex; flex-direction:column; gap:1.05rem;">
                <input type="hidden" id="transferPatientId">

                <!-- Patient Snapshot Card -->
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:0.85rem 1rem; display:flex; justify-content:space-between; align-items:center; gap:10px;">
                    <div>
                        <div id="transferPatientName" style="font-weight:700; color:#0f172a; font-size:0.95rem;">Patient Name</div>
                        <div id="transferPatientMeta" style="font-size:0.78rem; color:#64748b; margin-top:2px;">PT-2026-XXX</div>
                    </div>
                    <span id="transferCurrentDeptBadge" class="table-dept-tag">General Medical</span>
                </div>

                <!-- Chief Complaint Display -->
                <div class="form-group-clean">
                    <label style="font-size:0.76rem; font-weight:700; color:#475569; text-transform:uppercase; margin-bottom:4px; display:block;">
                        Reported Chief Complaint
                    </label>
                    <div id="transferComplaintText" style="background:#ffffff; border:1.5px solid #e2e8f0; border-radius:8px; padding:0.85rem 1rem; font-size:0.88rem; color:#0f172a; line-height:1.45; min-height:52px; white-space:pre-wrap;">
                        No complaint specified.
                    </div>
                </div>

                <!-- Receiving Department Selector -->
                <div class="form-group-clean">
                    <label for="transferDepartmentSelect" style="font-size:0.76rem; font-weight:700; color:#475569; text-transform:uppercase; margin-bottom:4px; display:block;">
                        Transfer to Department <span style="color:#e11d48;">*</span>
                    </label>
                    <select id="transferDepartmentSelect" class="form-control-clean" style="font-size:0.88rem; font-weight:600; padding:8px 12px; height:42px; border-radius:8px; border:1.5px solid #cbd5e1; width:100%; background:#ffffff;">
                        <option value="General Medical">General Medical</option>
                        <option value="Neurology">Neurology</option>
                        <option value="OPU Psychiatry">OPU Psychiatry</option>
                    </select>
                    <p style="font-size:0.74rem; color:#64748b; margin-top:5px; line-height:1.35;">
                        Use this when a patient selected the wrong clinic or is unsure which department fits their concern. The current record will be marked as Transferred and a new request will be routed to the selected department.
                    </p>
                </div>
            </div>
            <div class="clean-modal-footer">
                <button type="button" class="btn-clean outline" onclick="closeTransferModal()">Cancel</button>
                <button type="button" class="btn-clean primary" id="btnConfirmTransfer" onclick="submitTransferPatient()">
                    Transfer Patient
                </button>
            </div>
        </div>
    </div>

    <!-- Schedule Availability & Patient Call Assist Modal -->
    <div id="scheduleAssistModal" class="clean-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="saModalTitle">
        <div class="clean-modal-box" style="max-width: 650px;">
            <div class="clean-modal-header">
                <div>
                    <h3 id="saModalTitle">Schedule Availability &amp; Patient Call Assist</h3>
                    <p id="saModalSubtitle" style="font-size:0.78rem; color:#64748b; margin-top:2px;">Verify doctor time slots or call the patient to coordinate an available schedule</p>
                </div>
                <button type="button" class="clean-modal-close" onclick="closeScheduleAssistModal()" aria-label="Close">&times;</button>
            </div>
            <div class="clean-modal-body" style="display:flex; flex-direction:column; gap:1rem;">
                <input type="hidden" id="saPatientId">

                <!-- Call Patient Protocol Alert -->
                <div id="saCallAlertBox" class="sa-call-alert">
                    <div class="sa-call-alert-top">
                        <div class="sa-call-alert-text">
                            <div id="saAlertTitle" class="sa-call-alert-title">Call patient if preferred schedule is unavailable</div>
                            <p id="saAlertCopy" class="sa-call-alert-copy"></p>
                        </div>
                        <a id="saPatientPhone" href="#" class="btn-clean outline sm sa-call-link" style="white-space:nowrap;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            <span id="saPatientPhoneText">No phone on file</span>
                        </a>
                    </div>
                </div>

                <!-- Department Available Days & Time Blocks -->
                <div class="sa-dept-box">
                    <div class="sa-dept-box-head">
                        <span id="saDeptHeading">Department Available Time Blocks</span>
                        <span class="sa-clinic-days-pill">Clinic Days: Mon – Sat</span>
                    </div>
                    <div id="saDeptAvailabilityList" class="sa-dept-list"></div>
                </div>

                <!-- Coordinate Schedule & Attending Physician -->
                <div class="sa-form-grid">
                    <div class="form-group-clean">
                        <label for="saApptDate">Appointment Date <span style="color:#e11d48;">*</span></label>
                        <input type="date" id="saApptDate" class="form-control-clean" min="<?= date('Y-m-d') ?>" onchange="refreshScheduleAssistOptions()">
                    </div>
                    <div class="form-group-clean">
                        <label for="saTimeSlot">Time Block <span style="color:#e11d48;">*</span></label>
                        <select id="saTimeSlot" class="form-control-clean" onchange="refreshScheduleAssistOptions()">
                            <option value="Morning (8:00 AM - 12:00 PM)">Morning (8:00 AM – 12:00 PM)</option>
                            <option value="Afternoon (1:00 PM - 5:00 PM)">Afternoon (1:00 PM – 5:00 PM)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group-clean">
                    <label for="saDoctorSelect">Attending Physician <span style="color:#e11d48;">*</span></label>
                    <select id="saDoctorSelect" class="form-control-clean">
                        <option value="">Select available physician...</option>
                    </select>
                    <p id="saDoctorSlotHint" style="font-size:0.75rem; color:#64748b; margin-top:4px;"></p>
                </div>

                <!-- Teleconsultation Session Details (shown only for Teleconsultation patients) -->
                <div id="saTeleconsultSection" style="display:none; background:#f8fafc; border:1px solid #dbeafe; border-radius:10px; padding:12px 14px;">
                    <div style="font-size:0.8rem; font-weight:700; color:#1e3a8a; margin-bottom:10px;">Teleconsultation Session Details</div>
                    <div class="sa-form-grid">
                        <div class="form-group-clean">
                            <label for="saTeleconsultPlatform">Consultation Channel <span style="color:#e11d48;">*</span></label>
                            <select id="saTeleconsultPlatform" class="form-control-clean" onchange="toggleSaTeleconsultLinkField()">
                                <option value="Google Meet">Google Meet</option>
                                <option value="Zoom">Zoom</option>
                                <option value="Phone / Viber Call">Phone / Viber Call</option>
                                <option value="Facebook Messenger">Facebook Messenger</option>
                            </select>
                        </div>
                        <div class="form-group-clean" id="saTeleconsultLinkWrap">
                            <label for="saTeleconsultLink">Meeting Link <span style="color:#64748b; font-weight:400;">(https://...)</span></label>
                            <input type="url" id="saTeleconsultLink" class="form-control-clean" placeholder="https://meet.google.com/xxx-xxxx-xxx" maxlength="500">
                        </div>
                    </div>
                    <div class="form-group-clean" style="margin-top:8px;">
                        <label for="saTeleconsultNotes">Patient Instructions <span style="color:#64748b; font-weight:400;">(Optional)</span></label>
                        <input type="text" id="saTeleconsultNotes" class="form-control-clean" placeholder="e.g., Join 5 mins early and keep your previous prescription ready" maxlength="255">
                    </div>
                </div>
            </div>
            <div class="clean-modal-footer">
                <button type="button" class="btn-clean outline" onclick="closeScheduleAssistModal()">Cancel</button>
                <button type="button" class="btn-clean outline" id="btnSaSaveOnly" onclick="submitScheduleAssist(false)">Save Schedule &amp; Physician</button>
                <button type="button" class="btn-clean primary" id="btnSaSaveApprove" onclick="submitScheduleAssist(true)">Save &amp; Approve</button>
            </div>
        </div>
    </div>

    <div id="staffConfirmModal" class="clean-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="staffConfirmTitle">
        <div class="clean-modal-box confirm-modal-box">
            <div class="clean-modal-header">
                <h3 id="staffConfirmTitle">Confirm</h3>
                <button type="button" class="clean-modal-close" id="staffConfirmClose" aria-label="Close">&times;</button>
            </div>
            <div class="clean-modal-body">
                <p id="staffConfirmMessage" class="confirm-modal-copy"></p>
            </div>
            <div class="clean-modal-footer">
                <button type="button" class="btn-clean outline" id="staffConfirmCancel">Keep visit</button>
                <button type="button" class="btn-clean danger-outline" id="staffConfirmOk">Confirm</button>
            </div>
        </div>
    </div>

    <div id="cleanToast" class="toast-clean" role="status" aria-live="polite"></div>

    <script>
        window.STAFF_DEPT = <?= json_encode($deptLabel) ?>;
        window.STAFF_USER_ID = <?= json_encode($userId) ?>;
        window.STAFF_FIRST_NAME = <?= json_encode($firstName) ?>;
    </script>
    <script src="/sdn/public/js/patient-slip.js?v=<?= time() ?>"></script>
    <script src="/sdn/public/js/staff-dashboard-clean.js?v=<?= time() ?>"></script>
</body>
</html>
