<?php
session_start();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
if (empty($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header('Location: /sdn/pages/portal@admin$staff_2026.php');
    exit;
}
try {
    require_once __DIR__ . '/../api/config/db.php';
    require_once __DIR__ . '/../api/config/settings_helper.php';
    if (enforcePortalSessionTimeout($pdo ?? null, 'admin')) {
        header('Location: /sdn/pages/portal@admin$staff_2026.php?timeout=1');
        exit;
    }
} catch (Throwable $ignore) {}
$adminUsername    = $_SESSION['user'] ?? 'admin';
$adminDisplayName = (!empty($_SESSION['full_name']) && $_SESSION['full_name'] !== 'null')
    ? $_SESSION['full_name']
    : 'Administrator';
$adminParts = preg_split('/\s+/', trim($adminDisplayName)) ?: [];
$adminInitials = '';
foreach (array_slice($adminParts, 0, 2) as $part) {
    if ($part !== '') $adminInitials .= strtoupper(substr($part, 0, 1));
}
if ($adminInitials === '') $adminInitials = 'AD';
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];
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
    <title>Admin Portal | LINGKOD</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/sdn/public/css/global.css?v=20261128" />
    <link rel="stylesheet" href="/sdn/public/css/admin-portal.css?v=20261128" />
    <link rel="stylesheet" href="/sdn/public/css/portal-chrome.css?v=20261128" />
    <link rel="stylesheet" href="/sdn/public/css/enterprise-portal.css?v=20261128" />
    <link rel="stylesheet" href="/sdn/dist/assets/main.css?v=20261128" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style>
        body, body * {
            caret-color: transparent;
            user-select: none;
            -webkit-user-select: none;
        }
        input:not([type="date"]):not([type="checkbox"]):not([type="radio"]),
        textarea {
            caret-color: auto;
            user-select: text;
            -webkit-user-select: text;
        }
        input[type="date"] {
            caret-color: transparent;
            cursor: pointer;
        }
    </style>
</head>
<body class="portal-layout admin-portal-v2">
    <div class="admin-sidebar-backdrop" id="adminSidebarBackdrop" aria-hidden="true"></div>
    <aside class="sidebar admin-sidebar" id="adminSidebar" aria-label="Admin navigation">
        <div class="sidebar-header admin-sidebar-header">
            <div class="admin-header-top">
                <div class="admin-brand-logos">
                    <img src="/sdn/public/assets/mmwgh_logo.png" alt="MMWGH Seal" class="admin-seal-logo">
                    <span class="admin-brand-divider" aria-hidden="true"></span>
                    <img src="/sdn/public/assets/v2_lingkod_logo_cropped.png" alt="LINGKOD" class="admin-lingkod-logo">
                </div>
                <button type="button" class="sidebar-toggle-btn" id="adminCollapseBtn" aria-label="Collapse sidebar" aria-controls="adminSidebar" aria-expanded="true" title="Collapse sidebar">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                        <line x1="9" y1="3" x2="9" y2="21"></line>
                        <polyline points="15 9 12 12 15 15"></polyline>
                    </svg>
                </button>
                <button type="button" class="admin-sidebar-close-btn" id="adminSidebarCloseBtn" aria-label="Close navigation menu" title="Close menu">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="admin-brand-hosp">Mariveles Mental Wellness<br>&amp; General Hospital</div>
        </div>
        <nav class="sidebar-nav admin-sidebar-nav">
            <div class="nav-group">
                <div class="nav-group-label">MAIN</div>
                <a href="#" class="nav-link active" data-view="dashboard" data-tooltip="Overview" aria-label="Overview">
                    <svg width="19" height="19" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><rect width="7" height="9" x="3" y="3" rx="1.5"></rect><rect width="7" height="5" x="14" y="3" rx="1.5"></rect><rect width="7" height="9" x="14" y="12" rx="1.5"></rect><rect width="7" height="5" x="3" y="16" rx="1.5"></rect></svg>
                    <span>Overview</span>
                </a>
            </div>
            <div class="nav-group">
                <div class="nav-group-label">DIRECTORY</div>
                <a href="#" class="nav-link" data-view="rbac" data-tooltip="User Management" aria-label="User Management">
                    <svg width="19" height="19" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    <span>User Management</span>
                </a>
                <a href="#" class="nav-link" data-view="doctors" data-tooltip="Doctors" aria-label="Doctors">
                    <svg width="19" height="19" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M4.8 2.3A.3.3 0 1 0 5 2H4a2 2 0 0 0-2 2v5a6 6 0 0 0 6 6v0a6 6 0 0 0 6-6V4a2 2 0 0 0-2-2h-1a.2.2 0 1 0 .3.3"></path><path d="M8 15v1a6 6 0 0 0 6 6v0a6 6 0 0 0 6-6v-4"></path><circle cx="20" cy="10" r="2"></circle></svg>
                    <span>Doctors</span>
                </a>
                <a href="#" class="nav-link" data-view="pmi" data-tooltip="Patient Master Index" aria-label="Patient Master Index">
                    <svg width="19" height="19" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><rect width="20" height="16" x="2" y="4" rx="2"></rect><circle cx="8.5" cy="10.5" r="2"></circle><path d="M5.5 16a3.5 3.5 0 0 1 6 0"></path><path d="M14 9.5h4"></path><path d="M14 13.5h3"></path></svg>
                    <span>Patient Master Index</span>
                </a>
            </div>
            <div class="nav-group">
                <div class="nav-group-label">REPORTS AND LOGS</div>
                <a href="#" class="nav-link" data-view="reports" data-tooltip="Reports" aria-label="Reports">
                    <svg width="19" height="19" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><line x1="10" y1="9" x2="8" y2="9"></line></svg>
                    <span>Reports</span>
                </a>
                <a href="#" class="nav-link" data-view="audit" data-tooltip="Audit Log" aria-label="Audit Log">
                    <svg width="19" height="19" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M16 22h2a2 2 0 0 0 2-2V7l-5-5H6a2 2 0 0 0-2 2v3"></path><path d="M14 2v4a2 2 0 0 0 2 2h4"></path><circle cx="8" cy="16" r="5"></circle><path d="M8 14v2l1.5 1"></path></svg>
                    <span>Audit Log</span>
                </a>
            </div>
            <div class="nav-group">
                <div class="nav-group-label">SYSTEM</div>
                <a href="#" class="nav-link" data-view="settings" data-tooltip="System Settings" aria-label="System Settings">
                    <svg width="19" height="19" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                    <span>System Settings</span>
                </a>
            </div>
        </nav>
    </aside>
    <script>
      (function () {
        const sidebar = document.getElementById('adminSidebar');
        const collapseBtn = document.getElementById('adminCollapseBtn');
        if (!sidebar || !collapseBtn) return;
        const applyCollapsed = (collapsed) => {
          sidebar.classList.toggle('is-collapsed', collapsed);
          collapseBtn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
          const lbl = collapsed ? 'Expand sidebar' : 'Collapse sidebar';
          collapseBtn.setAttribute('aria-label', lbl);
          collapseBtn.setAttribute('title', lbl);
        };
        try {
          if (localStorage.getItem('lingkod_admin_sidebar_collapsed') === '1') {
            applyCollapsed(true);
          }
        } catch (e) {}
        collapseBtn.addEventListener('click', () => {
          const next = !sidebar.classList.contains('is-collapsed');
          applyCollapsed(next);
          try { localStorage.setItem('lingkod_admin_sidebar_collapsed', next ? '1' : '0'); } catch (e) {}
        });
      })();
    </script>
    <main class="main-content">
        <header class="admin-topbar">
            <div class="admin-mobile-header-left">
                <button type="button" class="admin-sidebar-toggle" id="adminSidebarToggle" aria-label="Toggle navigation menu" aria-expanded="false" aria-controls="adminSidebar">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>
                <div class="admin-mobile-brand">
                    <span class="admin-mobile-brand-name">LINGKOD</span>
                    <span class="admin-mobile-brand-sub">Admin Portal</span>
                </div>
            </div>
            <div class="admin-topbar-right">
                <div class="admin-notif-wrap" id="adminNotifWrap">
                    <button type="button" class="admin-notif-btn" id="adminNotifBtn" onclick="toggleAdminNotifications(event)" title="Notifications" aria-label="Notifications">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                        </svg>
                        <span class="admin-notif-badge" id="adminNotifBadge" style="display:none;">0</span>
                    </button>
                    <div class="admin-notif-dropdown" id="adminNotifDropdown">
                        <div class="admin-notif-header">
                            <span class="admin-notif-title">Notifications</span>
                            <button type="button" class="admin-notif-mark-read" onclick="markAllAdminNotificationsRead(event)">Mark all read</button>
                        </div>
                        <div class="admin-notif-list" id="adminNotifList">
                            <div class="admin-notif-empty">Loading notifications...</div>
                        </div>
                    </div>
                </div>
                <div class="admin-profile-wrap" id="adminProfileWrap">
                    <button type="button" class="user-avatar-btn admin-avatar-btn" id="adminProfileBtn" onclick="toggleAdminProfileMenu(event)" title="<?= htmlspecialchars($adminDisplayName) ?>" aria-label="Account menu for <?= htmlspecialchars($adminDisplayName) ?>" aria-haspopup="true" aria-expanded="false" aria-controls="adminProfileDropdown">
                        <span class="user-avatar-circle" aria-hidden="true">
                            <span class="user-avatar-initials"><?= htmlspecialchars($adminInitials) ?></span>
                            <span class="user-avatar-status"></span>
                        </span>
                        <span class="admin-avatar-meta">
                            <span class="user-avatar-name"><?= htmlspecialchars($adminDisplayName) ?></span>
                            <span class="admin-avatar-sub">@<?= htmlspecialchars($adminUsername) ?></span>
                        </span>
                        <svg class="admin-avatar-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>
                    <div class="admin-profile-dropdown" id="adminProfileDropdown">
                        <div class="admin-profile-dd-head">
                            <div class="admin-profile-dd-avatar"><?= htmlspecialchars($adminInitials) ?></div>
                            <div class="admin-profile-dd-meta">
                                <div class="admin-profile-dd-name"><?= htmlspecialchars($adminDisplayName) ?></div>
                                <div class="admin-profile-dd-role">System Administrator &bull; MMWGH</div>
                            </div>
                        </div>
                        <div class="admin-profile-dd-foot">
                            <a href="/sdn/pages/logout.php?portal=admin" class="admin-profile-logout-btn">
                                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                    <polyline points="16 17 21 12 16 7"></polyline>
                                    <line x1="21" y1="12" x2="9" y2="12"></line>
                                </svg>
                                <span>Sign out</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="admin-canvas">
        <!-- Overview / Hospital Command Center -->
        <section id="view-dashboard" class="view-section active">
            <div class="admin-ov-header">
                <div class="admin-ov-overline">Overview &bull; <?= date('l, F j, Y') ?></div>
                <h1>Hospital Overview</h1>
                <p>Real-time outpatient volume, department capacity, and pending approvals.</p>
            </div>

            <div class="adm-ov-scroll-body">
            <!-- Row 1: 4 Executive KPI Cards -->
            <div class="admin-ov-kpi-grid" id="dashboard-stats">
                <button type="button" class="admin-ov-kpi-card adm-ov-kpi-clickable" onclick="navigateToAdminView('pmi')">
                    <div class="admin-ov-kpi-top">
                        <div class="admin-ov-kpi-label">TODAY&#8217;S APPOINTMENTS</div>
                        <svg class="admin-ov-kpi-icon-sm" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75Z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="admin-ov-kpi-value" id="admKpiAppointments">0</div>
                    <div class="adm-ov-kpi-sub" id="admKpiTodaySub">0 waiting &bull; 0 ongoing &bull; 0 done</div>
                </button>

                <button type="button" class="admin-ov-kpi-card adm-ov-kpi-clickable" id="admKpiPendingCard" onclick="jumpToPendingPMI()">
                    <div class="admin-ov-kpi-top">
                        <div class="admin-ov-kpi-label">PENDING APPROVALS</div>
                        <svg class="admin-ov-kpi-icon-sm" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-13a.75.75 0 0 0-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 0 0 0-1.5h-3.25V5Z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="admin-ov-kpi-value" id="admKpiPending">0</div>
                    <div class="adm-ov-kpi-sub" id="admKpiPendingSub">0 appointments &bull; 0 inquiries</div>
                </button>

                <button type="button" class="admin-ov-kpi-card adm-ov-kpi-clickable" onclick="navigateToAdminView('doctors')">
                    <div class="admin-ov-kpi-top">
                        <div class="admin-ov-kpi-label">DOCTORS ON DUTY</div>
                        <svg class="admin-ov-kpi-icon-sm" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M7 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM14.5 9a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5ZM1.615 16.428a1.224 1.224 0 0 1-.569-1.175 6.002 6.002 0 0 1 11.908 0c.058.467-.172.92-.57 1.174A9.953 9.953 0 0 1 7 18a9.953 9.953 0 0 1-5.385-1.572ZM14.5 16h-.106c.07-.297.088-.611.048-.933a7.47 7.47 0 0 0-1.588-3.755 4.502 4.502 0 0 1 5.874 2.636.818.818 0 0 1-.36.98A7.465 7.465 0 0 1 14.5 16Z" />
                        </svg>
                    </div>
                    <div class="admin-ov-kpi-value" id="admKpiDoctors">0</div>
                    <div class="adm-ov-kpi-sub" id="admKpiDoctorsSub">of 0 doctors available (IN)</div>
                </button>

                <button type="button" class="admin-ov-kpi-card adm-ov-kpi-clickable" onclick="navigateToAdminView('settings')">
                    <div class="admin-ov-kpi-top">
                        <div class="admin-ov-kpi-label">OPD CAPACITY TODAY</div>
                        <svg class="admin-ov-kpi-icon-sm" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="admin-ov-kpi-value" id="admKpiCapacity">0%</div>
                    <div class="adm-ov-kpi-sub" id="admKpiCapacitySub">0 / 0 slots &bull; 0 active staff</div>
                </button>
            </div>

            <!-- Row 2: Volume Trend Chart + Live Department Capacity -->
            <div id="adminOverviewExecPanel" class="adm-exec-row-2">
                <div class="adm-exec-card">
                    <div class="adm-exec-card-head">
                        <div>
                            <h2>Appointment Volume Trend</h2>
                            <p>Patient intake across General Medical, Neurology, and Psychiatry.</p>
                        </div>
                        <div class="adm-ov-pills" role="tablist" aria-label="Chart range">
                            <button type="button" class="adm-ov-pill" data-range="week" onclick="setOverviewChartRange('week')">Week</button>
                            <button type="button" class="adm-ov-pill" data-range="month" onclick="setOverviewChartRange('month')">Month</button>
                            <button type="button" class="adm-ov-pill active" data-range="year" onclick="setOverviewChartRange('year')">Year</button>
                        </div>
                        <select id="chart-filter" hidden aria-hidden="true">
                            <option value="week">Week</option>
                            <option value="month">Month</option>
                            <option value="year" selected>Year</option>
                        </select>
                    </div>
                    <div class="adm-chart-wrap">
                        <canvas id="patientsChart"></canvas>
                    </div>
                </div>

                <div class="adm-exec-card">
                    <div class="adm-exec-card-head">
                        <div>
                            <h2>Department Capacity</h2>
                            <p>Today&#8217;s bookings vs. daily limits.</p>
                        </div>
                        <button type="button" class="adm-exec-link" onclick="navigateToAdminView('settings')">Adjust &rarr;</button>
                    </div>
                    <div id="admDeptCapacityList" class="adm-cap-list">
                        <div class="adm-exec-empty">Loading department capacity...</div>
                    </div>
                </div>
            </div>
            </div>
        </section>

        <!-- User Management (Svelte Staff & Patient Accounts) -->
        <section id="view-rbac" class="view-section">
            <div id="admin-users-app" data-api-base="/sdn/api/admin-users-router.php"></div>
        </section>

        <!-- Doctors Management -->
        <section id="view-doctors" class="view-section">
            <div class="header-flex">
                <div>
                    <h1>Doctor Management</h1>
                    <p>Manage hospital physicians, credentials, daily slot capacity, and duty availability.</p>
                </div>
                <button class="btn btn-primary" onclick="openAddDoctorModal()">+ Add Doctor</button>
            </div>

            <div class="doc-stats-grid">
                <div class="admin-ov-kpi-card">
                    <div class="admin-ov-kpi-top">
                        <div class="admin-ov-kpi-label">TOTAL PHYSICIANS</div>
                        <svg class="admin-ov-kpi-icon-sm" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M7 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM14.5 9a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5ZM1.615 16.428a1.224 1.224 0 0 1-.569-1.175 6.002 6.002 0 0 1 11.908 0c.058.467-.172.92-.57 1.174A9.953 9.953 0 0 1 7 18a9.953 9.953 0 0 1-5.385-1.572ZM14.5 16h-.106c.07-.297.088-.611.048-.933a7.47 7.47 0 0 0-1.588-3.755 4.502 4.502 0 0 1 5.874 2.636.818.818 0 0 1-.36.98A7.465 7.465 0 0 1 14.5 16Z" />
                        </svg>
                    </div>
                    <div class="admin-ov-kpi-value" id="docStatTotal">0</div>
                </div>
                <div class="admin-ov-kpi-card">
                    <div class="admin-ov-kpi-top">
                        <div class="admin-ov-kpi-label">ON DUTY (IN)</div>
                        <svg class="admin-ov-kpi-icon-sm" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="admin-ov-kpi-value" id="docStatIn">0</div>
                </div>
                <div class="admin-ov-kpi-card">
                    <div class="admin-ov-kpi-top">
                        <div class="admin-ov-kpi-label">OFF DUTY (OUT)</div>
                        <svg class="admin-ov-kpi-icon-sm" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-13a.75.75 0 0 0-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 0 0 0-1.5h-3.25V5Z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="admin-ov-kpi-value" id="docStatOut">0</div>
                </div>
                <div class="admin-ov-kpi-card">
                    <div class="admin-ov-kpi-top">
                        <div class="admin-ov-kpi-label">TODAY'S SLOTS</div>
                        <svg class="admin-ov-kpi-icon-sm" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75Z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="admin-ov-kpi-value" id="docStatCapacity">0 / 0</div>
                </div>
            </div>

            <div class="modern-table-shell">
                <div class="modern-table-toolbar">
                    <div class="dept-category-tabs" role="tablist" aria-label="Filter doctors by department">
                        <button type="button" class="dept-tab active" data-dept="all" onclick="setDocDeptCategory('all')">
                            All <span class="dept-tab-count" id="docCountAll">0</span>
                        </button>
                        <button type="button" class="dept-tab" data-dept="general" onclick="setDocDeptCategory('general')">
                            General Medical <span class="dept-tab-count" id="docCountGeneral">0</span>
                        </button>
                        <button type="button" class="dept-tab" data-dept="neurology" onclick="setDocDeptCategory('neurology')">
                            Neurology <span class="dept-tab-count" id="docCountNeurology">0</span>
                        </button>
                        <button type="button" class="dept-tab" data-dept="psychiatry" onclick="setDocDeptCategory('psychiatry')">
                            Psychiatry <span class="dept-tab-count" id="docCountPsychiatry">0</span>
                        </button>
                    </div>
                    <select id="docDeptFilter" onchange="filterDoctorsTable()" hidden aria-hidden="true">
                        <option value="all">All Departments</option>
                        <option value="psychiatry">Psychiatry</option>
                        <option value="neurology">Neurology</option>
                        <option value="general">General Medical</option>
                    </select>
                    <div class="modern-table-controls">
                        <div class="modern-search-box">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            <input type="search" id="docSearchInput" name="doc_search_q" placeholder="Search doctor, PRC, or specialization..." oninput="filterDoctorsTable()" autocomplete="new-password" data-lpignore="true" spellcheck="false">
                        </div>
                        <select id="docStatusFilter" class="modern-select" onchange="filterDoctorsTable()" aria-label="Filter by status">
                            <option value="all">All Statuses</option>
                            <option value="in">On Duty (IN)</option>
                            <option value="out">Off Duty (OUT)</option>
                        </select>
                        <button type="button" class="modern-reset-btn" onclick="resetDoctorFilters()">Reset</button>
                    </div>
                </div>
                <div class="modern-table-scroll">
                    <table class="modern-table">
                        <thead class="modern-thead">
                            <tr>
                                <th>Doctor</th>
                                <th>Specialization</th>
                                <th>Department</th>
                                <th>Today's Load</th>
                                <th>Status</th>
                                <th class="col-actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="doctors-tbody">
                            <tr><td colspan="6" class="modern-table-empty">Loading doctors...</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="modern-table-footer">
                    <span id="doctorsTableCount">Loading doctors...</span>
                </div>
            </div>
        </section>

        <!-- PMI -->
        <section id="view-pmi" class="view-section">
            <div class="header-flex">
                <div>
                    <h1>Patient Master Index</h1>
                    <p>Global registry of all patients.</p>
                </div>
            </div>
            <!-- Real-Time Stats Aligned with PMI Workflow -->
            <div class="pmi-stats-grid">
                <div class="admin-ov-kpi-card">
                    <div class="admin-ov-kpi-top">
                        <div class="admin-ov-kpi-label">REGISTERED PATIENTS</div>
                        <svg class="admin-ov-kpi-icon-sm" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M7 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM14.5 9a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5ZM1.615 16.428a1.224 1.224 0 0 1-.569-1.175 6.002 6.002 0 0 1 11.908 0c.058.467-.172.92-.57 1.174A9.953 9.953 0 0 1 7 18a9.953 9.953 0 0 1-5.385-1.572ZM14.5 16h-.106c.07-.297.088-.611.048-.933a7.47 7.47 0 0 0-1.588-3.755 4.502 4.502 0 0 1 5.874 2.636.818.818 0 0 1-.36.98A7.465 7.465 0 0 1 14.5 16Z" />
                        </svg>
                    </div>
                    <div class="admin-ov-kpi-value" id="pmi-stat-patients">0</div>
                </div>

                <div class="admin-ov-kpi-card">
                    <div class="admin-ov-kpi-top">
                        <div class="admin-ov-kpi-label">RETURNING PATIENTS</div>
                        <svg class="admin-ov-kpi-icon-sm" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 0 1-9.201 2.466l-.312-.311h2.433a.75.75 0 0 0 0-1.5H3.989a.75.75 0 0 0-.75.75v4.242a.75.75 0 0 0 1.5 0v-2.43l.31.31a7 7 0 0 0 11.712-3.138.75.75 0 0 0-1.449-.39Zm1.23-3.723a.75.75 0 0 0 .219-.53V2.929a.75.75 0 0 0-1.5 0V5.36l-.31-.31A7 7 0 0 0 3.239 8.188a.75.75 0 1 0 1.448.389A5.5 5.5 0 0 1 13.89 6.11l.311.31h-2.432a.75.75 0 0 0 0 1.5h4.243a.75.75 0 0 0 .53-.219Z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="admin-ov-kpi-value" id="pmi-stat-returning">0</div>
                </div>

                <div class="admin-ov-kpi-card">
                    <div class="admin-ov-kpi-top">
                        <div class="admin-ov-kpi-label">TOTAL APPOINTMENTS</div>
                        <svg class="admin-ov-kpi-icon-sm" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75Z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="admin-ov-kpi-value" id="pmi-stat-appointments">0</div>
                </div>
            </div>

            <!-- PMI Filter & Search Toolbar -->
            <div class="pmi-action-toolbar">
                <div class="pmi-search-wrap">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <input type="search" id="pmiSearchInput" name="pmi_search_q" class="form-control pmi-search-input" placeholder="Search name, Patient ID, or phone..." oninput="filterPMITable()" autocomplete="new-password" data-lpignore="true" spellcheck="false">
                </div>
                <div class="pmi-filters-wrap">
                    <div class="filter-item">
                        <label for="pmiDeptFilter" class="toolbar-label">Department:</label>
                        <select id="pmiDeptFilter" class="form-control pmi-select" onchange="filterPMITable()">
                            <option value="all">All Departments</option>
                            <option value="general">General Medical</option>
                            <option value="neurology">Neurology</option>
                            <option value="psychiatry">Psychiatry</option>
                        </select>
                    </div>
                    <div class="filter-item">
                        <label for="pmiStatusFilter" class="toolbar-label">Status:</label>
                        <select id="pmiStatusFilter" class="form-control pmi-select" onchange="filterPMITable()">
                            <option value="all">All Statuses</option>
                            <option value="for_approval">For Approval</option>
                            <option value="inquiry">Inquiries</option>
                            <option value="approved">Confirmed</option>
                            <option value="in_clinic">In Clinic</option>
                            <option value="completed">Completed</option>
                            <option value="transferred">Transferred</option>
                            <option value="cancelled">Cancelled / No-Show</option>
                        </select>
                    </div>
                    <div class="filter-item">
                        <label for="pmi-filter" class="toolbar-label">Period:</label>
                        <select id="pmi-filter" class="form-control pmi-select" onchange="loadPMI()">
                            <option value="all">All Time</option>
                            <option value="week">This Week</option>
                            <option value="month">This Month</option>
                            <option value="year">This Year</option>
                        </select>
                    </div>
                    <button class="btn btn-outline btn-reset-filters" onclick="resetPMIFilter()" title="Refresh Filters" aria-label="Refresh Filters">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2.5 2v6h6M21.5 22v-6h-6"/><path d="M22 11.5A10 10 0 0 0 3.2 7.2M2 12.5a10 10 0 0 0 18.8 4.3"/></svg>
                        <span>Refresh</span>
                    </button>
                </div>
            </div>

            <div class="custom-table-card">
                <div class="custom-table-header">
                    <table class="data-table">
                        <colgroup>
                            <col style="width: 18%;">
                            <col style="width: 28%;">
                            <col style="width: 24%;">
                            <col style="width: 16%;">
                            <col style="width: 14%;">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Patient ID</th>
                                <th>Patient</th>
                                <th>Visits &amp; Last Visit</th>
                                <th>Latest Status</th>
                                <th style="text-align: center;">Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
                <div class="custom-table-body" id="pmi-scroll-body">
                    <table class="data-table">
                        <colgroup>
                            <col style="width: 18%;">
                            <col style="width: 28%;">
                            <col style="width: 24%;">
                            <col style="width: 16%;">
                            <col style="width: 14%;">
                        </colgroup>
                        <tbody id="pmi-tbody">
                            <tr><td colspan="5" style="text-align: center; padding: 30px;">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- Reports & Analytics (Streamlined Report Generation Dashboard) -->
        <section id="view-reports" class="view-section">
            <div class="repgen-shell">
                <div class="header-flex repgen-head">
                    <div>
                        <h1>Report Generation</h1>
                        <p>Filter hospital records by category and date range.</p>
                    </div>
                </div>

                <!-- Inline Horizontal Category Tabs -->
                <nav class="repgen-tabs" id="repGenTabs" role="tablist" aria-label="Report categories">
                    <button type="button" role="tab" aria-selected="true" class="repgen-tab active" data-category="appointments" onclick="selectReportCategory('appointments')">Appointments</button>
                    <button type="button" role="tab" aria-selected="false" class="repgen-tab" data-category="patients" onclick="selectReportCategory('patients')">Patients</button>
                    <button type="button" role="tab" aria-selected="false" class="repgen-tab" data-category="doctors" onclick="selectReportCategory('doctors')">Doctors</button>
                    <button type="button" role="tab" aria-selected="false" class="repgen-tab" data-category="medication" onclick="selectReportCategory('medication')">Medication</button>
                    <button type="button" role="tab" aria-selected="false" class="repgen-tab" data-category="diagnosis" onclick="selectReportCategory('diagnosis')">Diagnosis</button>
                </nav>

                <!-- Single-Row Flex Filter Bar -->
                <form id="repGenForm" class="repgen-filter-bar" onsubmit="generateCategoryReport(event)">
                    <div class="repgen-field">
                        <label for="repGenFrom">From</label>
                        <input type="date" id="repGenFrom" onchange="generateCategoryReport()">
                    </div>
                    <div class="repgen-field">
                        <label for="repGenTo">To</label>
                        <input type="date" id="repGenTo" onchange="generateCategoryReport()">
                    </div>
                    <div class="repgen-field repgen-field-select">
                        <label for="repGenDept">Department (Optional)</label>
                        <select id="repGenDept" onchange="generateCategoryReport()">
                            <option value="">All Departments</option>
                            <option value="General Medical">General Medical</option>
                            <option value="Neurology">Neurology</option>
                            <option value="Psychiatry">Psychiatry</option>
                        </select>
                    </div>
                    <div class="repgen-filter-actions">
                        <button type="button" id="btnRefreshReport" class="repgen-btn-refresh" onclick="generateCategoryReport()" title="Refresh report data" aria-label="Refresh report data">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <polyline points="23 4 23 10 17 10"></polyline>
                                <polyline points="1 20 1 14 7 14"></polyline>
                                <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                            </svg>
                            <span>Refresh</span>
                        </button>
                        <button type="submit" id="btnGenerateReport" class="repgen-btn-primary">Generate Report</button>
                    </div>
                </form>

                <div id="repGenError" class="repgen-error" role="alert" hidden></div>

                <!-- Dynamic Zebra-Striped Report Table Card -->
                <div class="repgen-table-card">
                    <div class="repgen-table-head">
                        <div>
                            <h2 id="repGenTableTitle">Appointments Report</h2>
                            <p id="repGenTableMeta">Filter by date range or department, or click Generate Report.</p>
                        </div>
                        <div class="repgen-actions">
                            <button type="button" id="btnRepGenPrint" class="repgen-btn-outline" onclick="printCategoryReport()" disabled>
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                                    <rect x="6" y="14" width="12" height="8"></rect>
                                </svg>
                                <span>Print</span>
                            </button>
                            <button type="button" id="btnRepGenExport" class="repgen-btn-outline" onclick="exportCategoryExcel()" disabled>
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="7 10 12 15 17 10"></polyline>
                                    <line x1="12" y1="15" x2="12" y2="3"></line>
                                </svg>
                                <span>Export to Excel</span>
                            </button>
                        </div>
                    </div>

                    <div class="repgen-table-scroll">
                        <table class="repgen-table" id="repGenTable">
                            <thead id="repGenThead">
                                <tr>
                                    <th scope="col">Date</th>
                                    <th scope="col">Patient</th>
                                    <th scope="col">Department</th>
                                    <th scope="col">Diagnosis</th>
                                    <th scope="col">Attending Physician</th>
                                </tr>
                            </thead>
                            <tbody id="repGenTbody">
                                <tr>
                                    <td colspan="5" class="repgen-empty">Loading diagnosis records…</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        <!-- Audit Log -->
        <section id="view-audit" class="view-section">
            <div class="header-flex">
                <div>
                    <h1>Audit Trail Log</h1>
                    <p>System actions, security events, and administrative modifications.</p>
                </div>
            </div>

            <!-- Audit Log Category & Sort Toolbar -->
            <div class="rbac-action-toolbar pmi-action-toolbar" style="margin-bottom: 16px; flex-shrink: 0;">
                <div class="pmi-search-wrap">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <input type="search" id="auditSearchInput" name="audit_search_q" class="form-control pmi-search-input" placeholder="Search action, user, or details..." oninput="filterAndSortAuditTable()" autocomplete="new-password" data-lpignore="true" spellcheck="false">
                </div>
                <div class="pmi-filters-wrap">
                    <div class="filter-item">
                        <label for="auditCategoryFilter" class="toolbar-label">Category:</label>
                        <select id="auditCategoryFilter" class="form-control pmi-select" onchange="filterAndSortAuditTable()">
                            <option value="all">All Categories</option>
                            <option value="patient">Patient &amp; Appointments</option>
                            <option value="staff">User Management</option>
                            <option value="doctor">Doctors</option>
                            <option value="auth">Authentication &amp; Security</option>
                            <option value="system">System &amp; General</option>
                        </select>
                    </div>
                    <div class="filter-item">
                        <label for="auditSortOrder" class="toolbar-label">Sort By:</label>
                        <select id="auditSortOrder" class="form-control pmi-select" onchange="filterAndSortAuditTable()">
                            <option value="newest">Newest First</option>
                            <option value="oldest">Oldest First</option>
                            <option value="category">Category (A–Z)</option>
                            <option value="action">Action (A–Z)</option>
                            <option value="user">User (A–Z)</option>
                        </select>
                    </div>
                    <button class="btn btn-outline btn-reset-filters" onclick="resetAuditFilters()" title="Refresh Audit Log" aria-label="Refresh Audit Log">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2.5 2v6h6M21.5 22v-6h-6"/><path d="M22 11.5A10 10 0 0 0 3.2 7.2M2 12.5a10 10 0 0 0 18.8 4.3"/></svg>
                        <span>Refresh</span>
                    </button>
                </div>
            </div>

            <div class="custom-table-card audit-table-card">
                <div class="custom-table-header">
                    <table class="data-table">
                        <colgroup>
                            <col style="width: 16%;">
                            <col style="width: 16%;">
                            <col style="width: 20%;">
                            <col style="width: 14%;">
                            <col style="width: 34%;">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Category</th>
                                <th>Action</th>
                                <th>User</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                    </table>
                </div>
                <div class="custom-table-body" id="audit-scroll-body">
                    <table class="data-table">
                        <colgroup>
                            <col style="width: 16%;">
                            <col style="width: 16%;">
                            <col style="width: 20%;">
                            <col style="width: 14%;">
                            <col style="width: 34%;">
                        </colgroup>
                        <tbody id="audit-tbody">
                            <tr><td colspan="5" style="text-align:center; padding:30px;">Loading audit logs...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- System Settings (Svelte Admin Settings) -->
        <section id="view-settings" class="view-section">
            <div id="admin-settings-app" data-api-base="/sdn/api/admin-settings-router.php"></div>
        </section>
        </div>
    </main>
    
    <!-- Modals -->
    <div id="staffModal" class="modal">
        <div class="modal-content staff-modal-card">
            <div class="staff-modal-head">
                <div>
                    <h2>Add Staff Member</h2>
                    <p>Create medical staff credentials and assign a clinical department.</p>
                </div>
                <button type="button" class="staff-modal-close" onclick="closeStaffModal()" aria-label="Close modal">&times;</button>
            </div>
            <div class="staff-modal-body">
                <div class="staff-identity-bar">
                    <div class="staff-identity-left">
                        <div class="modal-avatar-preview staff-avatar-sm" id="staffAvatarPreview">
                            <span class="modal-avatar-placeholder">ST</span>
                        </div>
                        <div class="staff-avatar-meta">
                            <div class="staff-avatar-label">Profile Photo <span>(Optional)</span></div>
                            <div class="modal-avatar-btns">
                                <input type="file" id="staffAvatarInput" accept="image/png,image/jpeg,image/webp" hidden onchange="handleAvatarFileChange(this, 'staffAdd')">
                                <button type="button" class="btn btn-outline btn-avatar-sm" onclick="document.getElementById('staffAvatarInput').click()">Upload Photo</button>
                                <button type="button" class="btn btn-outline btn-avatar-sm is-remove" id="staffAvatarRemoveBtn" style="display:none;" onclick="clearAvatarSelection('staffAdd')">Remove</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="staff-form-grid">
                    <div class="form-group staff-col-span-2">
                        <label for="staffName">Full Name <span class="required">*</span></label>
                        <input type="text" id="staffName" class="form-control" placeholder="e.g. Maria Santos, RN" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="staffUsername">Username (System ID) <span class="required">*</span></label>
                        <input type="text" id="staffUsername" class="form-control" placeholder="e.g. msantos_staff" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="staffDept">Department <span class="required">*</span></label>
                        <select id="staffDept" class="form-control">
                            <option value="General Medical">General Medical</option>
                            <option value="Neurology">Neurology</option>
                            <option value="Psychiatry">Psychiatry</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="staffPass">Password <span class="required">*</span></label>
                        <div class="staff-pass-wrap">
                            <input type="password" id="staffPass" class="form-control" placeholder="Min. 6 characters">
                            <button type="button" class="staff-eye-btn" onclick="togglePasswordVisibility('staffPass')" aria-label="Toggle password visibility">
                                <svg id="eyeIcon-staffPass" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="staffPassConfirm">Confirm Password <span class="required">*</span></label>
                        <div class="staff-pass-wrap">
                            <input type="password" id="staffPassConfirm" class="form-control" placeholder="Re-enter password">
                            <button type="button" class="staff-eye-btn" onclick="togglePasswordVisibility('staffPassConfirm')" aria-label="Toggle confirm password visibility">
                                <svg id="eyeIcon-staffPassConfirm" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="staff-modal-foot">
                <div></div>
                <div class="staff-foot-right">
                    <button type="button" class="btn btn-outline" onclick="closeStaffModal()">Cancel</button>
                    <button type="button" class="staff-btn-save" onclick="submitStaff()">Create Staff</button>
                </div>
            </div>
        </div>
    </div>

    <div id="editStaffModal" class="modal">
        <div class="modal-content staff-modal-card">
            <div class="staff-modal-head">
                <div>
                    <h2>Edit Staff Member</h2>
                    <p>Update staff details, department, status, or reset password.</p>
                </div>
                <button type="button" class="staff-modal-close" onclick="closeEditStaffModal()" aria-label="Close modal">&times;</button>
            </div>
            <input type="hidden" id="editStaffId">
            <div class="staff-modal-body">
                <div class="staff-identity-bar">
                    <div class="staff-identity-left">
                        <div class="modal-avatar-preview staff-avatar-sm" id="editStaffAvatarPreview">
                            <span class="modal-avatar-placeholder">ST</span>
                        </div>
                        <div class="staff-avatar-meta">
                            <div class="staff-avatar-label">Profile Photo</div>
                            <div class="modal-avatar-btns">
                                <input type="file" id="editStaffAvatarInput" accept="image/png,image/jpeg,image/webp" hidden onchange="handleAvatarFileChange(this, 'staffEdit')">
                                <button type="button" class="btn btn-outline btn-avatar-sm" onclick="document.getElementById('editStaffAvatarInput').click()">Change</button>
                                <button type="button" class="btn btn-outline btn-avatar-sm is-remove" id="editStaffAvatarRemoveBtn" style="display:none;" onclick="clearAvatarSelection('staffEdit')">Remove</button>
                            </div>
                        </div>
                    </div>
                    <div class="staff-status-box">
                        <span class="staff-status-pill" id="editStaffStatusPill">
                            <span class="staff-status-dot" id="editStaffStatusDot"></span>
                            <span id="editStaffStatusText">Active</span>
                        </span>
                        <button type="button" id="editStaffStatusBtn" class="staff-status-toggle-btn">Disable</button>
                    </div>
                </div>

                <div class="staff-form-grid">
                    <div class="form-group staff-col-span-2">
                        <label for="editStaffName">Full Name <span class="required">*</span></label>
                        <input type="text" id="editStaffName" class="form-control" placeholder="Staff full name">
                    </div>
                    <div class="form-group">
                        <label for="editStaffUsername">Username (System ID)</label>
                        <input type="text" id="editStaffUsername" class="form-control staff-readonly-input" disabled>
                    </div>
                    <div class="form-group">
                        <label for="editStaffDept">Department <span class="required">*</span></label>
                        <select id="editStaffDept" class="form-control">
                            <option value="General Medical">General Medical</option>
                            <option value="Neurology">Neurology</option>
                            <option value="Psychiatry">Psychiatry</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="editStaffPass">New Password <span class="staff-opt-hint">(optional)</span></label>
                        <div class="staff-pass-wrap">
                            <input type="password" id="editStaffPass" class="form-control" placeholder="Leave blank to keep">
                            <button type="button" class="staff-eye-btn" onclick="togglePasswordVisibility('editStaffPass')" aria-label="Toggle new password visibility">
                                <svg id="eyeIcon-editStaffPass" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="editStaffPassConfirm">Confirm Password <span class="staff-opt-hint">(optional)</span></label>
                        <div class="staff-pass-wrap">
                            <input type="password" id="editStaffPassConfirm" class="form-control" placeholder="Confirm new password">
                            <button type="button" class="staff-eye-btn" onclick="togglePasswordVisibility('editStaffPassConfirm')" aria-label="Toggle confirm password visibility">
                                <svg id="eyeIcon-editStaffPassConfirm" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="staff-modal-foot">
                <button type="button" class="staff-btn-danger-ghost" onclick="deleteStaff(document.getElementById('editStaffId').value)">Delete Staff</button>
                <div class="staff-foot-right">
                    <button type="button" class="btn btn-outline" onclick="closeEditStaffModal()">Cancel</button>
                    <button type="button" class="staff-btn-save" onclick="submitEditStaff()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom Confirm Modal -->
    <div id="confirmModal" class="modal" style="z-index: 1050;">
        <div class="modal-content" style="max-width: 400px; text-align: center; padding: 2rem;">
            <div style="margin-bottom: 1.5rem;">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto;">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                    <line x1="12" y1="9" x2="12" y2="13"></line>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
            </div>
            <h2 id="confirmTitle" style="color: #111827; font-weight: 700; margin-bottom: 0.5rem; font-size:1.4rem;">Confirm Action</h2>
            <p id="confirmMessage" style="color: #4b5563; margin-bottom: 2rem; font-size:0.95rem; line-height:1.5;"></p>
            <div style="display:flex; justify-content:center; gap:15px;">
                <button class="btn btn-outline" onclick="closeConfirmModal()" style="min-width:100px;">Cancel</button>
                <button class="btn btn-primary" id="confirmBtn" style="min-width:100px;">Confirm</button>
            </div>
        </div>
    </div>

    <div id="doctorModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="doctorModalTitle">
        <div class="modal-content staff-modal-card">
            <div class="staff-modal-head">
                <div>
                    <h2 id="doctorModalTitle">Add Doctor</h2>
                    <p>Configure physician credentials, specialization, and daily slot capacity.</p>
                </div>
                <button type="button" class="staff-modal-close" onclick="closeDoctorModal()" aria-label="Close modal">&times;</button>
            </div>
            <input type="hidden" id="editDoctorId">
            <div class="staff-modal-body">
                <div class="staff-identity-bar">
                    <div class="staff-identity-left">
                        <div class="modal-avatar-preview staff-avatar-sm" id="doctorAvatarPreview">
                            <span class="modal-avatar-placeholder">DR</span>
                        </div>
                        <div>
                            <div class="staff-avatar-label">Profile Photo <span>(optional)</span></div>
                            <div class="modal-avatar-btns">
                                <input type="file" id="doctorAvatarInput" accept="image/png,image/jpeg,image/webp" hidden onchange="handleAvatarFileChange(this, 'doctor')">
                                <button type="button" class="btn btn-outline btn-avatar-sm" onclick="document.getElementById('doctorAvatarInput').click()">Upload</button>
                                <button type="button" class="btn btn-outline btn-avatar-sm is-remove" id="doctorAvatarRemoveBtn" style="display:none;" onclick="clearAvatarSelection('doctor')">Remove</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="staff-form-grid">
                    <div class="form-group staff-col-span-2">
                        <label for="docName">Doctor Name <span class="required">*</span></label>
                        <input type="text" id="docName" class="form-control" placeholder="e.g. Dr. Maria Santos" autocomplete="off" oninput="this.classList.remove('input-error')">
                    </div>
                    <div class="form-group">
                        <label for="docTitle">Title / Credentials</label>
                        <input type="text" id="docTitle" class="form-control" placeholder="e.g. MD, FPPA, FPNA" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="docLicense">PRC License Number</label>
                        <input type="text" id="docLicense" class="form-control" placeholder="e.g. 0123456" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="docDept">Department <span class="required">*</span></label>
                        <select id="docDept" class="form-control" onchange="this.classList.remove('input-error')">
                            <option value="general">General Medical</option>
                            <option value="neurology">Neurology</option>
                            <option value="psychiatry">Psychiatry</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="docStatus">Duty Status <span class="required">*</span></label>
                        <select id="docStatus" class="form-control" onchange="this.classList.remove('input-error')">
                            <option value="in">On Duty (IN)</option>
                            <option value="out">Off Duty (OUT)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="docSpec">Specialization <span class="required">*</span></label>
                        <input type="text" id="docSpec" class="form-control" placeholder="e.g. Adult Psychiatry" autocomplete="off" oninput="this.classList.remove('input-error')">
                    </div>
                    <div class="form-group">
                        <label for="docSlots">Daily Slot Limit (Max 100) <span class="required">*</span></label>
                        <input type="number" id="docSlots" class="form-control" value="20" min="1" max="100" oninput="this.classList.remove('input-error')">
                    </div>
                </div>
            </div>
            <div class="staff-modal-foot">
                <button class="staff-btn-danger-ghost" type="button" id="btnDeleteDoctorModal" style="display:none;" onclick="handleModalDeleteDoctor()">Delete Doctor</button>
                <div class="staff-foot-right">
                    <button class="btn btn-outline" type="button" onclick="closeDoctorModal()">Cancel</button>
                    <button class="staff-btn-save" type="button" onclick="submitDoctorForm()">Save Doctor</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Patient Appointment Verification Modal -->
    <div id="patientDetailsModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="patientDetailsModalTitle">
        <div class="modal-content pv-modal-shell">
            <div class="modal-header pv-modal-header">
                <div class="pv-header-copy">
                    <h2 id="patientDetailsModalTitle">Patient Appointment Verification</h2>
                    <p>Review appointment information and verify patient authenticity via phone call.</p>
                </div>
                <button type="button" class="close pv-close-btn" aria-label="Close modal" onclick="closePatientDetailsModal()">&times;</button>
            </div>
            <div id="patientDetailsBody" class="pv-modal-body">
                <p class="pv-loading-state">Loading appointment information...</p>
            </div>
            <div id="patientDetailsActions" class="patient-modal-actions-bar pv-modal-footer">
                <!-- Populated dynamically based on patient status -->
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="/sdn/public/js/patient-slip.js?v=20261128"></script>
    <script src="/sdn/public/js/admin-portal.js?v=20261128"></script>
    <script type="module" src="/sdn/dist/assets/main.js?v=20261128"></script>
    <script>
      (function () {
        const toggleBtn = document.getElementById('adminSidebarToggle');
        const closeBtn = document.getElementById('adminSidebarCloseBtn');
        const sidebar = document.getElementById('adminSidebar');
        const backdrop = document.getElementById('adminSidebarBackdrop');

        function setSidebarOpen(open) {
          if (!sidebar || !toggleBtn || !backdrop) return;
          sidebar.classList.toggle('is-open', open);
          backdrop.classList.toggle('is-open', open);
          toggleBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
          if (open) {
            const firstLink = sidebar.querySelector('.nav-link');
            if (firstLink) firstLink.focus();
          } else {
            toggleBtn.focus();
          }
        }

        if (toggleBtn && sidebar && backdrop) {
          toggleBtn.addEventListener('click', () => setSidebarOpen(!sidebar.classList.contains('is-open')));
          if (closeBtn) closeBtn.addEventListener('click', () => setSidebarOpen(false));
          backdrop.addEventListener('click', () => setSidebarOpen(false));
          sidebar.querySelectorAll('.nav-link').forEach((link) => {
            link.addEventListener('click', () => {
              if (window.innerWidth <= 960) setSidebarOpen(false);
            });
          });
          document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && sidebar.classList.contains('is-open')) setSidebarOpen(false);
          });
          window.addEventListener('resize', () => {
            if (window.innerWidth > 960 && sidebar.classList.contains('is-open')) {
              sidebar.classList.remove('is-open');
              backdrop.classList.remove('is-open');
              toggleBtn.setAttribute('aria-expanded', 'false');
            }
          });
        }

        document.querySelectorAll('[role="tablist"]').forEach((tablist) => {
          tablist.addEventListener('keydown', (e) => {
            if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(e.key)) return;
            const tabs = Array.from(tablist.querySelectorAll('button, [role="tab"]')).filter((el) => !el.disabled);
            if (!tabs.length) return;
            const idx = tabs.indexOf(document.activeElement);
            if (idx === -1) return;
            e.preventDefault();
            let next = idx;
            if (e.key === 'ArrowRight') next = (idx + 1) % tabs.length;
            else if (e.key === 'ArrowLeft') next = (idx - 1 + tabs.length) % tabs.length;
            else if (e.key === 'Home') next = 0;
            else if (e.key === 'End') next = tabs.length - 1;
            tabs[next].focus();
            tabs[next].click();
          });
        });
      })();
    </script>
</body>
</html>
