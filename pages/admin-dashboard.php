<?php
session_start();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
if (empty($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header('Location: /sdn/pages/portal@admin$staff_2026.php');
    exit;
}
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal | LINGKOD</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/sdn/public/css/global.css?v=<?= time() ?>" />
    <link rel="stylesheet" href="/sdn/public/css/admin-portal.css?v=<?= time() ?>" />
    <link rel="stylesheet" href="/sdn/public/css/portal-chrome.css?v=<?= time() ?>" />
    <link rel="stylesheet" href="/sdn/public/css/enterprise-portal.css?v=<?= time() ?>" />
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
            <div class="admin-brand-logos">
                <img src="/sdn/public/assets/mmwgh_logo.png" alt="MMWGH Seal" class="admin-seal-logo">
                <span class="admin-brand-divider" aria-hidden="true"></span>
                <img src="/sdn/public/assets/v2_lingkod_logo_cropped.png" alt="LINGKOD" class="admin-lingkod-logo">
            </div>
            <div class="admin-brand-hosp">Mariveles Mental Wellness<br>&amp; General Hospital</div>
            <div class="admin-brand-role">Admin Portal</div>
        </div>
        <nav class="sidebar-nav admin-sidebar-nav">
            <div class="nav-group">
                <div class="nav-group-label">MAIN</div>
                <a href="#" class="nav-link active" data-view="dashboard">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="7" y1="13" x2="11" y2="13"></line><line x1="7" y1="16" x2="10" y2="16"></line><rect x="13" y="12.5" width="5" height="4" rx="0.5"></rect></svg>
                    <span>Overview</span>
                </a>
            </div>
            <div class="nav-group">
                <div class="nav-group-label">DIRECTORY</div>
                <a href="#" class="nav-link" data-view="rbac">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    <span>User Management</span>
                </a>
                <a href="#" class="nav-link" data-view="doctors">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="7" r="4"></circle><path d="M5.5 21v-2a5.5 5.5 0 0 1 13 0v2"></path><line x1="12" y1="14" x2="12" y2="18"></line><line x1="10" y1="16" x2="14" y2="16"></line></svg>
                    <span>Doctors</span>
                </a>
                <a href="#" class="nav-link" data-view="pmi">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1"></rect><circle cx="12" cy="11" r="2"></circle><path d="M8.5 17a3.5 3.5 0 0 1 7 0"></path></svg>
                    <span>Patient Master Index</span>
                </a>
            </div>
            <div class="nav-group">
                <div class="nav-group-label">ANALYTICS AND LOGS</div>
                <a href="#" class="nav-link" data-view="reports">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line><line x1="3" y1="20" x2="21" y2="20"></line></svg>
                    <span>Reports &amp; Analytics</span>
                </a>
                <a href="#" class="nav-link" data-view="audit">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><circle cx="11" cy="14" r="2.5"></circle><line x1="13" y1="16" x2="15.5" y2="18.5"></line></svg>
                    <span>Audit Log</span>
                </a>
            </div>
        </nav>
    </aside>
    <main class="main-content">
        <header class="admin-topbar">
            <button type="button" class="admin-sidebar-toggle" id="adminSidebarToggle" aria-label="Toggle navigation menu" aria-expanded="false" aria-controls="adminSidebar">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>
            <div class="header-search-wrap admin-search-wrap">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="search" id="adminGlobalSearchInput" name="admin_global_search" placeholder="Search Dashboard..." aria-label="Search Dashboard" autocomplete="new-password" data-lpignore="true" spellcheck="false">
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
                    <button type="button" class="user-avatar-btn admin-avatar-btn" id="adminProfileBtn" onclick="toggleAdminProfileMenu(event)" title="<?= htmlspecialchars($adminDisplayName) ?>" aria-label="Account menu" aria-haspopup="true" aria-expanded="false">
                        <span class="user-avatar-name"><?= htmlspecialchars($adminDisplayName) ?></span>
                        <div class="user-avatar-circle" aria-hidden="true">
                            <svg width="36" height="36" viewBox="0 0 44 44" fill="none">
                                <circle cx="22" cy="14.5" r="7" fill="#ffffff"></circle>
                                <path d="M6.5 38.5C8.8 29.5 14.5 25 22 25C29.5 25 35.2 29.5 37.5 38.5C33.5 41.9 28 44 22 44C16 44 10.5 41.9 6.5 38.5Z" fill="#ffffff"></path>
                            </svg>
                        </div>
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
                            <a href="/sdn/pages/logout.php" class="admin-profile-logout-btn">
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
        <!-- Overview / Today's Schedule -->
        <section id="view-dashboard" class="view-section active">
            <div class="admin-ov-header">
                <div class="admin-ov-overline">Overview</div>
                <h1>Today&#8217;s Schedule</h1>
                <p>Here&#8217;s your patient services overview for today.</p>
            </div>

            <div class="admin-ov-kpi-grid" id="dashboard-stats">
                <div class="admin-ov-kpi-card">
                    <div class="admin-ov-kpi-top">
                        <div class="admin-ov-kpi-label">APPOINTMENTS</div>
                        <svg class="admin-ov-kpi-icon-sm" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75Z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="admin-ov-kpi-value" id="admKpiAppointments">0</div>
                </div>

                <div class="admin-ov-kpi-card">
                    <div class="admin-ov-kpi-top">
                        <div class="admin-ov-kpi-label">WAITING PATIENTS</div>
                        <svg class="admin-ov-kpi-icon-sm" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M7 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM14.5 9a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5ZM1.615 16.428a1.224 1.224 0 0 1-.569-1.175 6.002 6.002 0 0 1 11.908 0c.058.467-.172.92-.57 1.174A9.953 9.953 0 0 1 7 18a9.953 9.953 0 0 1-5.385-1.572ZM14.5 16h-.106c.07-.297.088-.611.048-.933a7.47 7.47 0 0 0-1.588-3.755 4.502 4.502 0 0 1 5.874 2.636.818.818 0 0 1-.36.98A7.465 7.465 0 0 1 14.5 16Z" />
                        </svg>
                    </div>
                    <div class="admin-ov-kpi-value" id="admKpiWaiting">0</div>
                </div>

                <div class="admin-ov-kpi-card">
                    <div class="admin-ov-kpi-top">
                        <div class="admin-ov-kpi-label">IN CONSULTATION</div>
                        <svg class="admin-ov-kpi-icon-sm" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 2c-2.236 0-4.43.18-6.57.524C1.993 2.755 1 4.014 1 5.426v5.148c0 1.413.993 2.67 2.43 2.902 1.168.188 2.352.327 3.55.414.28.02.521.18.642.413l1.713 3.293a.75.75 0 0 0 1.33 0l1.713-3.293a.783.783 0 0 1 .642-.413 41.102 41.102 0 0 0 3.55-.414c1.437-.231 2.43-1.49 2.43-2.902V5.426c0-1.413-.993-2.67-2.43-2.902A41.289 41.289 0 0 0 10 2ZM6.75 6a.75.75 0 0 0 0 1.5h6.5a.75.75 0 0 0 0-1.5h-6.5Zm0 2.5a.75.75 0 0 0 0 1.5h3.5a.75.75 0 0 0 0-1.5h-3.5Z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="admin-ov-kpi-value" id="admKpiOngoing">0</div>
                </div>

                <div class="admin-ov-kpi-card">
                    <div class="admin-ov-kpi-top">
                        <div class="admin-ov-kpi-label">COMPLETED</div>
                        <svg class="admin-ov-kpi-icon-sm" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="admin-ov-kpi-value" id="admKpiCompleted">0</div>
                </div>
            </div>

            <div class="admin-ov-bottom-grid">
                <!-- Schedule Calendar -->
                <div class="admin-ov-calendar-card">
                    <div class="admin-cal-toolbar">
                        <div class="admin-cal-nav-pill">
                            <button type="button" class="admin-cal-arrow" onclick="shiftAdminCalendar(-1)" aria-label="Previous period">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                            </button>
                            <span class="admin-cal-title" id="admCalTitle">November 2026</span>
                            <button type="button" class="admin-cal-arrow" onclick="shiftAdminCalendar(1)" aria-label="Next period">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                            </button>
                        </div>
                        <div class="admin-cal-mode-tabs" role="tablist" aria-label="Calendar view mode">
                            <button type="button" class="admin-cal-mode-btn active" data-mode="month" onclick="setAdminCalendarMode('month')">Month</button>
                            <button type="button" class="admin-cal-mode-btn" data-mode="week" onclick="setAdminCalendarMode('week')">Week</button>
                            <button type="button" class="admin-cal-mode-btn" data-mode="day" onclick="setAdminCalendarMode('day')">Day</button>
                        </div>
                    </div>
                    <div class="admin-cal-grid-wrap" id="adminOverviewCalendarWrap"></div>
                </div>
            </div>
        </section>

        <!-- User Management (Access Controls) -->
        <section id="view-rbac" class="view-section">
            <div class="header-flex">
                <div>
                    <h1>User Management</h1>
                    <p>Manage medical staff accounts, permissions, and department assignments.</p>
                </div>
                <button class="btn btn-primary" onclick="openStaffModal()">Add Staff</button>
            </div>

            <div class="modern-table-shell">
                <div class="modern-table-toolbar">
                    <div class="dept-category-tabs" role="tablist" aria-label="Filter staff by department">
                        <button type="button" class="dept-tab active" data-dept="all" onclick="setRBACDeptCategory('all')">
                            All <span class="dept-tab-count" id="rbacCountAll">0</span>
                        </button>
                        <button type="button" class="dept-tab" data-dept="general" onclick="setRBACDeptCategory('general')">
                            General Medical <span class="dept-tab-count" id="rbacCountGeneral">0</span>
                        </button>
                        <button type="button" class="dept-tab" data-dept="neurology" onclick="setRBACDeptCategory('neurology')">
                            Neurology <span class="dept-tab-count" id="rbacCountNeurology">0</span>
                        </button>
                        <button type="button" class="dept-tab" data-dept="psychiatry" onclick="setRBACDeptCategory('psychiatry')">
                            Psychiatry <span class="dept-tab-count" id="rbacCountPsychiatry">0</span>
                        </button>
                    </div>
                    <select id="rbacDeptFilter" onchange="filterRBACTable()" hidden aria-hidden="true">
                        <option value="all">All Departments</option>
                        <option value="psychiatry">Psychiatry</option>
                        <option value="neurology">Neurology</option>
                        <option value="general">General Medical</option>
                    </select>
                    <div class="modern-table-controls">
                        <div class="modern-search-box">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            <input type="search" id="rbacSearchInput" name="rbac_search_q" placeholder="Search by name or username..." oninput="filterRBACTable()" autocomplete="new-password" data-lpignore="true" spellcheck="false">
                        </div>
                        <select id="rbacStatusFilter" class="modern-select" onchange="filterRBACTable()" aria-label="Filter by status">
                            <option value="all">All Statuses</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <button type="button" class="modern-reset-btn" onclick="resetRBACFilters()">Reset</button>
                    </div>
                </div>
                <div class="modern-table-scroll">
                    <table class="modern-table">
                        <thead class="modern-thead">
                            <tr>
                                <th>Staff Member</th>
                                <th>Username</th>
                                <th>Department</th>
                                <th>Status</th>
                                <th class="col-actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="rbac-tbody">
                            <tr><td colspan="5" class="modern-table-empty">Loading staff...</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="modern-table-footer">
                    <span id="rbacTableCount">Loading staff...</span>
                </div>
            </div>
        </section>

        <!-- Doctors Management -->
        <section id="view-doctors" class="view-section">
            <div class="header-flex">
                <div>
                    <h1>Doctor Management</h1>
                    <p>Add, update, and manage doctors across hospital departments.</p>
                </div>
                <button class="btn btn-primary" onclick="openAddDoctorModal()">Add Doctor</button>
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
                            <input type="search" id="docSearchInput" name="doc_search_q" placeholder="Search doctor or specialization..." oninput="filterDoctorsTable()" autocomplete="new-password" data-lpignore="true" spellcheck="false">
                        </div>
                        <select id="docStatusFilter" class="modern-select" onchange="filterDoctorsTable()" aria-label="Filter by status">
                            <option value="all">All Statuses</option>
                            <option value="in">Available (IN)</option>
                            <option value="out">Unavailable (OUT)</option>
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
                                <th>Daily Capacity</th>
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
                        <div class="admin-ov-kpi-label">APPOINTMENTS BOOKED</div>
                        <svg class="admin-ov-kpi-icon-sm" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75Z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="admin-ov-kpi-value" id="pmi-stat-appointments">0</div>
                </div>

                <div class="admin-ov-kpi-card">
                    <div class="admin-ov-kpi-top">
                        <div class="admin-ov-kpi-label">SLOT SCHEDULES AVAILABLE</div>
                        <svg class="admin-ov-kpi-icon-sm" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-13a.75.75 0 0 0-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 0 0 0-1.5h-3.25V5Z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="admin-ov-kpi-value" id="pmi-stat-slots">0</div>
                </div>

                <div class="admin-ov-kpi-card">
                    <div class="admin-ov-kpi-top">
                        <div class="admin-ov-kpi-label">REGISTERED PATIENTS</div>
                        <svg class="admin-ov-kpi-icon-sm" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M7 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM14.5 9a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5ZM1.615 16.428a1.224 1.224 0 0 1-.569-1.175 6.002 6.002 0 0 1 11.908 0c.058.467-.172.92-.57 1.174A9.953 9.953 0 0 1 7 18a9.953 9.953 0 0 1-5.385-1.572ZM14.5 16h-.106c.07-.297.088-.611.048-.933a7.47 7.47 0 0 0-1.588-3.755 4.502 4.502 0 0 1 5.874 2.636.818.818 0 0 1-.36.98A7.465 7.465 0 0 1 14.5 16Z" />
                        </svg>
                    </div>
                    <div class="admin-ov-kpi-value" id="pmi-stat-patients">0</div>
                </div>
            </div>

            <!-- PMI Filter & Search Toolbar -->
            <div class="pmi-action-toolbar">
                <div class="pmi-search-wrap">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <input type="search" id="pmiSearchInput" name="pmi_search_q" class="form-control pmi-search-input" placeholder="Search patient name or ID..." oninput="filterPMITable()" autocomplete="new-password" data-lpignore="true" spellcheck="false">
                </div>
                <div class="pmi-filters-wrap">
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
                    <button class="btn btn-outline btn-reset-filters" onclick="resetPMIFilter()" title="Refresh Filters">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2.5 2v6h6M21.5 22v-6h-6"/><path d="M22 11.5A10 10 0 0 0 3.2 7.2M2 12.5a10 10 0 0 0 18.8 4.3"/></svg>
                        <span>Refresh</span>
                    </button>
                </div>
            </div>

            <div class="custom-table-card">
                <div class="custom-table-header">
                    <table class="data-table">
                        <colgroup>
                            <col style="width: 16%;">
                            <col style="width: 28%;">
                            <col style="width: 24%;">
                            <col style="width: 16%;">
                            <col style="width: 16%;">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Patient ID</th>
                                <th>Full Name</th>
                                <th>Department</th>
                                <th>Status</th>
                                <th style="text-align: center;">Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
                <div class="custom-table-body" id="pmi-scroll-body">
                    <table class="data-table">
                        <colgroup>
                            <col style="width: 16%;">
                            <col style="width: 28%;">
                            <col style="width: 24%;">
                            <col style="width: 16%;">
                            <col style="width: 16%;">
                        </colgroup>
                        <tbody id="pmi-tbody">
                            <tr><td colspan="5" style="text-align: center; padding: 30px;">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- Reports & Analytics (Upcoming Patient Operations & Insights) -->
        <section id="view-reports" class="view-section">
            <div class="header-flex queue-header-flex">
                <div>
                    <h1>Reports &amp; Analytics</h1>
                    <p>Track consultations, attending doctors, prescribed medications, and date-filtered patient activity.</p>
                </div>
                <div class="rep-header-actions">
                    <button class="btn btn-outline" id="btnReportPrint" onclick="printReports()" title="Print consultation and analytics summary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                        <span>Print Summary</span>
                    </button>
                    <button class="btn btn-primary" id="btnReportExport" onclick="exportReportsCsv()" title="Download filtered consultation report as CSV">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        <span>Export CSV</span>
                    </button>
                </div>
            </div>

            <!-- Segmented Pill Control Bar -->
            <div class="rep-toolbar" style="display:flex; flex-direction:column; gap:12px;">
                <div class="rep-toolbar-row" style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
                    <div class="rep-pill-group" id="reportRangePills" role="tablist" aria-label="Time horizon">
                        <button type="button" class="rep-pill-btn active" data-range="today" onclick="setReportRange('today')">Today</button>
                        <button type="button" class="rep-pill-btn" data-range="past_7" onclick="setReportRange('past_7')">Past 7 Days</button>
                        <button type="button" class="rep-pill-btn" data-range="past_30" onclick="setReportRange('past_30')">Past 30 Days</button>
                        <button type="button" class="rep-pill-btn" data-range="past_month" onclick="setReportRange('past_month')">Past 1 Month</button>
                        <button type="button" class="rep-pill-btn" data-range="all" onclick="setReportRange('all')">All Time</button>
                    </div>

                    <div class="rep-toolbar-right" style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-left:auto;">
                        <div class="modern-search-box rep-search-box" style="min-width:220px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            <input type="search" id="reportSearchInput" name="report_search_q" placeholder="Search patient or physician..." oninput="renderUpcomingPatientsTable()" autocomplete="new-password" data-lpignore="true" spellcheck="false">
                        </div>
                        <button type="button" class="modern-reset-btn" onclick="resetReportFilters()" title="Reset to Today">Refresh</button>
                    </div>
                </div>

                <div class="rep-toolbar-meta" style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; padding-top:10px; border-top:1px solid #f1f5f9;">
                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                        <span style="font-size:0.76rem; font-weight:600; color:#475569;">Date Range:</span>
                        <input type="date" id="reportStartDate" aria-label="Start date" onchange="applyCustomReportDateRange()" style="height:32px; width:138px; padding:0 9px; border:1px solid #cbd5e1; border-radius:7px; font-family:inherit; font-size:0.78rem; color:#0f172a; background:#ffffff;">
                        <span style="font-size:0.76rem; color:#64748b;">to</span>
                        <input type="date" id="reportEndDate" aria-label="End date" onchange="applyCustomReportDateRange()" style="height:32px; width:138px; padding:0 9px; border:1px solid #cbd5e1; border-radius:7px; font-family:inherit; font-size:0.78rem; color:#0f172a; background:#ffffff;">
                    </div>
                    <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap; font-size:0.78rem; color:#64748b;">
                        <span id="reportRangeLabel">Showing Consultation Report for Today</span>
                        <span id="reportLastUpdated"></span>
                    </div>
                </div>
            </div>

            <!-- 4 Clear KPI Cards -->
            <div class="rep-kpi-grid">
                <div class="admin-ov-kpi-card rep-kpi-card">
                    <div class="admin-ov-kpi-content">
                        <div class="admin-ov-kpi-label">TOTAL PATIENTS</div>
                        <div class="admin-ov-kpi-value" id="repKpiTotal">0</div>
                        <div class="rep-kpi-sub" id="repKpiSubTotal">0 online &middot; 0 walk-in / unscheduled</div>
                    </div>
                    <svg class="admin-ov-kpi-icon" width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                </div>

                <div class="admin-ov-kpi-card rep-kpi-card">
                    <div class="admin-ov-kpi-content">
                        <div class="admin-ov-kpi-label">ATTENDING DOCTORS</div>
                        <div class="admin-ov-kpi-value" id="repKpiReady">0</div>
                        <div class="rep-kpi-sub" id="repKpiSubReady">Active doctors with patients</div>
                    </div>
                    <svg class="admin-ov-kpi-icon" width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>

                <div class="admin-ov-kpi-card rep-kpi-card">
                    <div class="admin-ov-kpi-content">
                        <div class="admin-ov-kpi-label">COMPLETED CONSULTATIONS</div>
                        <div class="admin-ov-kpi-value" id="repKpiCompleted">0</div>
                        <div class="rep-kpi-sub" id="repKpiSubCompleted">0% completion rate</div>
                    </div>
                    <svg class="admin-ov-kpi-icon" width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
                        <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
                        <polyline points="9 14 11 16 15 12"></polyline>
                    </svg>
                </div>

                <div class="admin-ov-kpi-card rep-kpi-card">
                    <div class="admin-ov-kpi-content">
                        <div class="admin-ov-kpi-label">MEDICATIONS PRESCRIBED</div>
                        <div class="admin-ov-kpi-value" id="repKpiNoshow">0</div>
                        <div class="rep-kpi-sub" id="repKpiSubNoshow">Total prescriptions logged</div>
                    </div>
                    <svg class="admin-ov-kpi-icon" width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m10.5 20.5 10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7Z"></path>
                        <line x1="8.5" y1="8.5" x2="15.5" y2="15.5"></line>
                    </svg>
                </div>
            </div>

            <!-- Single Full-Width Patient Volume Chart -->
            <div class="rep-panel-card" style="margin-bottom: 18px;">
                <div class="rep-panel-head">
                    <div>
                        <h2 id="repChartTitle">Appointments by Day</h2>
                        <p>Total scheduled visits across the selected period.</p>
                    </div>
                </div>
                <div class="rep-chart-box">
                    <canvas id="reportsAnalyticsChart"></canvas>
                </div>
            </div>

            <!-- Unified Appointment Records Table -->
            <div class="modern-table-shell rep-tables-shell">
                <div class="rep-tabs-bar">
                    <div style="font-size: 0.92rem; font-weight: 600; color: #111827;">Appointment Records</div>
                </div>

                <div id="reportSectionUpcoming" class="modern-table-scroll rep-table-scroll">
                    <table class="modern-table">
                        <thead class="modern-thead">
                            <tr>
                                <th>Date &amp; Time</th>
                                <th>Patient</th>
                                <th>Physician</th>
                                <th>Type</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="reportUpcomingTbody">
                            <tr><td colspan="5" class="modern-table-empty">Loading appointments...</td></tr>
                        </tbody>
                    </table>
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
                    <button class="btn btn-outline btn-reset-filters" onclick="resetAuditFilters()" title="Refresh Audit Log">
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
        </div>
    </main>
    
    <!-- Modals -->
    <div id="staffModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2>Add Staff Member</h2>
                    <p>Create new staff login credentials and assign clinical department.</p>
                </div>
                <span class="close" onclick="closeStaffModal()">&times;</span>
            </div>
            <div class="modal-avatar-uploader">
                <div class="modal-avatar-preview" id="staffAvatarPreview">
                    <span class="modal-avatar-placeholder">ST</span>
                </div>
                <div class="modal-avatar-controls">
                    <div class="modal-avatar-title">Staff Profile Photo <span class="modal-avatar-opt">(Optional)</span></div>
                    <div class="modal-avatar-btns">
                        <input type="file" id="staffAvatarInput" accept="image/png,image/jpeg,image/webp" hidden onchange="handleAvatarFileChange(this, 'staffAdd')">
                        <button type="button" class="btn btn-outline btn-avatar-sm" onclick="document.getElementById('staffAvatarInput').click()">Upload Photo</button>
                        <button type="button" class="btn btn-outline btn-avatar-sm is-remove" id="staffAvatarRemoveBtn" style="display:none;" onclick="clearAvatarSelection('staffAdd')">Remove</button>
                    </div>
                    <div class="modal-avatar-hint">PNG, JPG, or WEBP up to 5MB. Visible per department.</div>
                </div>
            </div>
            <div class="form-group">
                <label>Full Name <span class="required">*</span></label>
                <input type="text" id="staffName" class="form-control" placeholder="e.g. Maria Santos, RN" autocomplete="off">
            </div>
            <div class="form-group">
                <label>Username (System ID) <span class="required">*</span></label>
                <input type="text" id="staffUsername" class="form-control" placeholder="e.g. msantos_staff" autocomplete="off">
            </div>
            <div class="form-group">
                <label>Password <span class="required">*</span></label>
                <div style="position: relative;">
                    <input type="password" id="staffPass" class="form-control" placeholder="Min. 6 characters" style="padding-right: 42px;">
                    <button type="button" onclick="togglePasswordVisibility('staffPass')" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #64748b; padding: 0; display: flex; align-items: center;">
                        <svg id="eyeIcon-staffPass" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </button>
                </div>
            </div>
            <div class="form-group">
                <label>Department <span class="required">*</span></label>
                <select id="staffDept" class="form-control">
                    <option value="Psychiatry">Psychiatry</option>
                    <option value="Neurology">Neurology</option>
                    <option value="General Medical">General Medical</option>
                </select>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="closeStaffModal()">Cancel</button>
                <button type="button" class="btn btn-blue" onclick="submitStaff()">Save Staff</button>
            </div>
        </div>
    </div>

    <div id="editStaffModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2>Edit Staff Member</h2>
                    <p>Modify staff profile, department assignment, or reset password.</p>
                </div>
                <span class="close" onclick="closeEditStaffModal()">&times;</span>
            </div>
            <input type="hidden" id="editStaffId">
            <div class="modal-avatar-uploader">
                <div class="modal-avatar-preview" id="editStaffAvatarPreview">
                    <span class="modal-avatar-placeholder">ST</span>
                </div>
                <div class="modal-avatar-controls">
                    <div class="modal-avatar-title">Staff Profile Photo</div>
                    <div class="modal-avatar-btns">
                        <input type="file" id="editStaffAvatarInput" accept="image/png,image/jpeg,image/webp" hidden onchange="handleAvatarFileChange(this, 'staffEdit')">
                        <button type="button" class="btn btn-outline btn-avatar-sm" onclick="document.getElementById('editStaffAvatarInput').click()">Change Photo</button>
                        <button type="button" class="btn btn-outline btn-avatar-sm is-remove" id="editStaffAvatarRemoveBtn" style="display:none;" onclick="clearAvatarSelection('staffEdit')">Remove</button>
                    </div>
                    <div class="modal-avatar-hint">PNG, JPG, or WEBP up to 5MB. Synced with Staff Portal.</div>
                </div>
            </div>
            <div class="form-group">
                <label>Full Name <span class="required">*</span></label>
                <input type="text" id="editStaffName" class="form-control" placeholder="Staff full name">
            </div>
            <div class="form-group">
                <label>Username (System ID)</label>
                <input type="text" id="editStaffUsername" class="form-control" disabled style="background:#f1f5f9; color:#64748b; cursor:not-allowed;">
            </div>
            <div class="form-group">
                <label>Department <span class="required">*</span></label>
                <select id="editStaffDept" class="form-control">
                    <option value="Psychiatry">Psychiatry</option>
                    <option value="Neurology">Neurology</option>
                    <option value="General Medical">General Medical</option>
                </select>
            </div>
            <div class="form-group">
                <label>New Password <span style="font-weight:400; color:#94a3b8; font-size:0.8rem;">(leave blank to keep current)</span></label>
                <div style="position: relative;">
                    <input type="password" id="editStaffPass" class="form-control" placeholder="Min. 6 characters (optional)" style="padding-right: 42px;">
                    <button type="button" onclick="togglePasswordVisibility('editStaffPass')" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #64748b; padding: 0; display: flex; align-items: center;">
                        <svg id="eyeIcon-editStaffPass" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </button>
                </div>
            </div>
            <div class="form-group" style="display:flex; justify-content:space-between; align-items:center; background:#f8fafc; padding:14px 16px; border-radius:12px; border:1px solid #e2e8f0; margin-top:6px;">
                <div>
                    <strong style="display:block; color:#0f172a; font-size:0.9rem;">Account Status</strong>
                    <span id="editStaffStatusText" style="font-size:0.82rem; font-weight:600; color:#10b981;">Active</span>
                </div>
                <button type="button" id="editStaffStatusBtn" class="btn btn-outline" style="padding:6px 14px; font-size:0.85rem;" onclick="toggleEditStaffStatus()">Disable Account</button>
            </div>
            <div class="modal-actions-between">
                <button type="button" class="btn btn-danger" onclick="deleteStaff(document.getElementById('editStaffId').value)">Delete Staff</button>
                <div style="display:flex; gap:10px;">
                    <button type="button" class="btn btn-outline" onclick="closeEditStaffModal()">Cancel</button>
                    <button type="button" class="btn btn-blue" onclick="submitEditStaff()">Save Changes</button>
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

    <div id="doctorModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 id="doctorModalTitle">Add Doctor</h2>
                    <p>Configure doctor credentials, clinical specialization, and daily slot capacity.</p>
                </div>
                <span class="close" onclick="closeDoctorModal()">&times;</span>
            </div>
            <input type="hidden" id="editDoctorId">
            <div class="modal-avatar-uploader">
                <div class="modal-avatar-preview" id="doctorAvatarPreview">
                    <span class="modal-avatar-placeholder">DR</span>
                </div>
                <div class="modal-avatar-controls">
                    <div class="modal-avatar-title">Doctor Profile Photo <span class="modal-avatar-opt">(Optional)</span></div>
                    <div class="modal-avatar-btns">
                        <input type="file" id="doctorAvatarInput" accept="image/png,image/jpeg,image/webp" hidden onchange="handleAvatarFileChange(this, 'doctor')">
                        <button type="button" class="btn btn-outline btn-avatar-sm" onclick="document.getElementById('doctorAvatarInput').click()">Upload Photo</button>
                        <button type="button" class="btn btn-outline btn-avatar-sm is-remove" id="doctorAvatarRemoveBtn" style="display:none;" onclick="clearAvatarSelection('doctor')">Remove</button>
                    </div>
                    <div class="modal-avatar-hint">PNG, JPG, or WEBP up to 5MB. Shown across directories &amp; schedules.</div>
                </div>
            </div>
            <div class="form-group">
                <label>Doctor Name <span class="required">*</span></label>
                <input type="text" id="docName" class="form-control" placeholder="e.g. Dr. Maria Santos" autocomplete="off" oninput="this.classList.remove('input-error')">
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label>Title / Credentials</label>
                    <input type="text" id="docTitle" class="form-control" placeholder="e.g. MD, FPPA, FPNA" autocomplete="off">
                </div>
                <div class="form-group">
                    <label>PRC License Number</label>
                    <input type="text" id="docLicense" class="form-control" placeholder="e.g. 0123456" autocomplete="off">
                </div>
            </div>
            <div class="form-group">
                <label>Department <span class="required">*</span></label>
                <select id="docDept" class="form-control" onchange="this.classList.remove('input-error')">
                    <option value="general">General Medical</option>
                    <option value="neurology">Neurology</option>
                    <option value="psychiatry">Psychiatry</option>
                </select>
            </div>
            <div class="form-group">
                <label>Specialization <span class="required">*</span></label>
                <input type="text" id="docSpec" class="form-control" placeholder="Enter clinical specialization..." autocomplete="off" oninput="this.classList.remove('input-error')">
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label>Total Daily Slots (Max 100) <span class="required">*</span></label>
                    <input type="number" id="docSlots" class="form-control" value="20" min="1" max="100" oninput="this.classList.remove('input-error')">
                </div>
                <div class="form-group">
                    <label>Status <span class="required">*</span></label>
                    <select id="docStatus" class="form-control" onchange="this.classList.remove('input-error')">
                        <option value="in">IN</option>
                        <option value="out">OUT</option>
                    </select>
                </div>
            </div>
            <div class="modal-actions" style="display:flex; justify-content:space-between; align-items:center;">
                <button class="btn btn-danger" type="button" id="btnDeleteDoctorModal" style="display:none; padding:8px 16px;" onclick="handleModalDeleteDoctor()">Delete Doctor</button>
                <div style="display:flex; gap:10px; margin-left:auto;">
                    <button class="btn btn-outline" type="button" onclick="closeDoctorModal()">Cancel</button>
                    <button class="btn btn-blue" type="button" onclick="submitDoctorForm()">Save Doctor</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Patient Appointment Verification Modal -->
    <div id="patientDetailsModal" class="modal">
        <div class="modal-content pv-modal-shell">
            <div class="modal-header pv-modal-header">
                <div>
                    <h2>Patient Appointment Verification</h2>
                    <p>Review appointment information and verify patient authenticity via phone call.</p>
                </div>
                <span class="close" onclick="closePatientDetailsModal()">&times;</span>
            </div>
            <div id="patientDetailsBody" class="pv-modal-body">
                <p style="text-align:center; color:#64748b; padding: 24px 0;">Loading appointment information...</p>
            </div>
            <div id="patientDetailsActions" class="patient-modal-actions-bar pv-modal-footer">
                <!-- Populated dynamically based on patient status -->
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="/sdn/public/js/admin-portal.js?v=<?= time() ?>"></script>
    <script>
      (function () {
        const toggleBtn = document.getElementById('adminSidebarToggle');
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
          backdrop.addEventListener('click', () => setSidebarOpen(false));
          sidebar.querySelectorAll('.nav-link').forEach((link) => {
            link.addEventListener('click', () => {
              if (window.innerWidth <= 960) setSidebarOpen(false);
            });
          });
          document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && sidebar.classList.contains('is-open')) setSidebarOpen(false);
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
