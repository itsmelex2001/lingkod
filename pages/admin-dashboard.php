<?php
session_start();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
if (empty($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header('Location: /sdn/pages/portal@admin$staff_2026.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Portal | LINGKOD</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/sdn/public/css/global.css?v=<?= time() ?>" />
    <link rel="stylesheet" href="/sdn/public/css/admin-portal.css?v=<?= time() ?>" />
    <link rel="stylesheet" href="/sdn/public/css/portal-chrome.css?v=<?= time() ?>" />
</head>
<body class="portal-layout">
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-brand">
                <div class="sidebar-brand-box">
                    <img src="/sdn/public/assets/v3_lingkod_logo_green.png" alt="LINGKOD">
                </div>
                <div class="brand-text-block">
                    <span class="brand-sub">Mariveles Mental Wellness and General Hospital</span>
                    <span class="brand-title">LINGKOD</span>
                    <span class="brand-role">System Administrator</span>
                </div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="#" class="nav-link active" data-view="dashboard">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="9"></rect><rect x="14" y="3" width="7" height="5"></rect><rect x="14" y="12" width="7" height="9"></rect><rect x="3" y="16" width="7" height="5"></rect></svg>
                Overview
            </a>
            <a href="#" class="nav-link" data-view="rbac">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                User Management
            </a>
            <a href="#" class="nav-link" data-view="doctors">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 12h-4l-3 9L9 3l-3 9H2"></path></svg>
                Doctors
            </a>
            <a href="#" class="nav-link" data-view="pmi">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                Patient Master Index
            </a>
            <a href="#" class="nav-link" data-view="reports">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                Reports & Analytics
            </a>
            <a href="#" class="nav-link" data-view="audit">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                Audit Log
            </a>
        </nav>
    </aside>
    <main class="main-content">
        <header class="admin-topbar">
            <div class="admin-topbar-left">
                <span class="admin-portal-label" id="adminTopbarTitle">Admin Portal</span>
            </div>
            <div class="admin-topbar-right">
                <div class="admin-notif-wrap" id="adminNotifWrap">
                    <button type="button" class="admin-notif-btn" id="adminNotifBtn" onclick="toggleAdminNotifications(event)" title="Notifications" aria-label="Notifications">
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
                    <button type="button" class="admin-profile-circle" id="adminProfileBtn" onclick="toggleAdminProfileMenu(event)" title="<?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['user'] ?? 'Admin') ?>" aria-label="Account menu">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </button>
                    <div class="admin-profile-dropdown" id="adminProfileDropdown">
                        <a href="/sdn/pages/logout.php" class="admin-profile-logout-btn">
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                                <polyline points="10 17 5 12 10 7"></polyline>
                                <line x1="15" y1="12" x2="5" y2="12"></line>
                            </svg>
                            <span>Logout</span>
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <div class="admin-canvas">
        <!-- Dashboard -->
        <!-- Overall Patients Statistics Section -->
        <section id="view-dashboard" class="view-section active">
            <div class="header-flex">
                <div>
                    <h1>Cross-Department Dashboard</h1>
                    <p>Real-time overview of clinical operations and department activity.</p>
                </div>
            </div>
            <div class="stats-grid" id="dashboard-stats">
                <div class="stat-card">Loading stats...</div>
            </div>

            <div class="dashboard-section" style="margin-top: 24px;">
                <div class="section-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                    <div>
                        <h2>Department Patient Statistics</h2>
                        <p style="margin-bottom:0;">Comparative patient registration trends across General Medical, Neurology, and Psychiatry.</p>
                    </div>
                    <div style="display:flex; gap:10px; align-items:center;">
                        <select id="chart-filter" class="form-control pmi-select" style="width: 150px;" onchange="loadChartData()">
                            <option value="year">This Year</option>
                            <option value="month">This Month</option>
                            <option value="week">This Week</option>
                        </select>
                        <button class="btn btn-outline btn-reset-filters" onclick="resetChartFilter()">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.5 2v6h6M21.5 22v-6h-6"/><path d="M22 11.5A10 10 0 0 0 3.2 7.2M2 12.5a10 10 0 0 0 18.8 4.3"/></svg>
                            <span>Refresh</span>
                        </button>
                    </div>
                </div>
                <div style="width:100%; height:320px; margin-top:18px;">
                    <canvas id="patientsChart"></canvas>
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
                <button class="btn btn-primary" onclick="openStaffModal()">+ Add Staff</button>
            </div>

            <!-- RBAC Search & Department Filter Toolbar -->
            <div class="rbac-action-toolbar pmi-action-toolbar">
                <div class="pmi-search-wrap">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <input type="text" id="rbacSearchInput" class="form-control pmi-search-input" placeholder="Search staff name..." oninput="filterRBACTable()">
                </div>
                <div class="pmi-filters-wrap">
                    <div class="filter-item">
                        <label for="rbacDeptFilter" class="toolbar-label">Department:</label>
                        <select id="rbacDeptFilter" class="form-control pmi-select" onchange="filterRBACTable()">
                            <option value="all">All Departments</option>
                            <option value="psychiatry">Psychiatry</option>
                            <option value="neurology">Neurology</option>
                            <option value="general">General Medical</option>
                        </select>
                    </div>
                    <div class="filter-item">
                        <label for="rbacStatusFilter" class="toolbar-label">Status:</label>
                        <select id="rbacStatusFilter" class="form-control pmi-select" onchange="filterRBACTable()">
                            <option value="all">All Statuses</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <button class="btn btn-outline btn-reset-filters" onclick="resetRBACFilters()" title="Refresh Filters">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.5 2v6h6M21.5 22v-6h-6"/><path d="M22 11.5A10 10 0 0 0 3.2 7.2M2 12.5a10 10 0 0 0 18.8 4.3"/></svg>
                        <span>Refresh</span>
                    </button>
                </div>
            </div>

            <div class="custom-table-card">
                <div class="custom-table-header">
                    <table class="data-table">
                        <colgroup>
                            <col style="width: 25%;">
                            <col style="width: 22%;">
                            <col style="width: 21%;">
                            <col style="width: 14%;">
                            <col style="width: 18%;">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Department</th>
                                <th>Status</th>
                                <th style="text-align: center;">Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
                <div class="custom-table-body" id="rbac-scroll-body">
                    <table class="data-table">
                        <colgroup>
                            <col style="width: 25%;">
                            <col style="width: 22%;">
                            <col style="width: 21%;">
                            <col style="width: 14%;">
                            <col style="width: 18%;">
                        </colgroup>
                        <tbody id="rbac-tbody">
                            <tr><td colspan="5" style="text-align: center; padding: 30px;">Loading...</td></tr>
                        </tbody>
                    </table>
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
                <button class="btn btn-primary" onclick="openAddDoctorModal()">+ Add Doctor</button>
            </div>

            <!-- Doctor Search & Department Filter Toolbar -->
            <div class="doctor-action-toolbar pmi-action-toolbar">
                <div class="pmi-search-wrap">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <input type="text" id="docSearchInput" class="form-control pmi-search-input" placeholder="Search doctor name or specialization..." oninput="filterDoctorsTable()">
                </div>
                <div class="pmi-filters-wrap">
                    <div class="filter-item">
                        <label for="docDeptFilter" class="toolbar-label">Department:</label>
                        <select id="docDeptFilter" class="form-control pmi-select" onchange="filterDoctorsTable()">
                            <option value="all">All Departments</option>
                            <option value="psychiatry">Psychiatry</option>
                            <option value="neurology">Neurology</option>
                            <option value="general">General Medical</option>
                        </select>
                    </div>
                    <div class="filter-item">
                        <label for="docStatusFilter" class="toolbar-label">Status:</label>
                        <select id="docStatusFilter" class="form-control pmi-select" onchange="filterDoctorsTable()">
                            <option value="all">All Statuses</option>
                            <option value="in">IN</option>
                            <option value="out">OUT</option>
                        </select>
                    </div>
                    <button class="btn btn-outline btn-reset-filters" onclick="resetDoctorFilters()" title="Refresh Filters">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.5 2v6h6M21.5 22v-6h-6"/><path d="M22 11.5A10 10 0 0 0 3.2 7.2M2 12.5a10 10 0 0 0 18.8 4.3"/></svg>
                        <span>Refresh</span>
                    </button>
                </div>
            </div>

            <div class="custom-table-card">
                <div class="custom-table-header">
                    <table class="data-table">
                        <colgroup>
                            <col style="width: 24%;">
                            <col style="width: 24%;">
                            <col style="width: 16%;">
                            <col style="width: 12%;">
                            <col style="width: 12%;">
                            <col style="width: 12%;">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Doctor</th>
                                <th>Specialization</th>
                                <th>Department</th>
                                <th>Daily Slots</th>
                                <th>Status</th>
                                <th style="text-align: center;">Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
                <div class="custom-table-body" id="doctors-scroll-body">
                    <table class="data-table">
                        <colgroup>
                            <col style="width: 24%;">
                            <col style="width: 24%;">
                            <col style="width: 16%;">
                            <col style="width: 12%;">
                            <col style="width: 12%;">
                            <col style="width: 12%;">
                        </colgroup>
                        <tbody id="doctors-tbody">
                            <tr><td colspan="6" style="text-align: center; padding: 30px;">Loading doctors...</td></tr>
                        </tbody>
                    </table>
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
                <div class="pmi-stat-card">
                    <div class="pmi-stat-icon-wrap icon-appointments">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    </div>
                    <div class="pmi-stat-details">
                        <h3 id="pmi-stat-appointments">0</h3>
                        <p>Appointments booked</p>
                    </div>
                </div>
                <div class="pmi-stat-card">
                    <div class="pmi-stat-icon-wrap icon-slots">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    </div>
                    <div class="pmi-stat-details">
                        <h3 id="pmi-stat-slots">0</h3>
                        <p>Slot schedules available</p>
                    </div>
                </div>
                <div class="pmi-stat-card">
                    <div class="pmi-stat-icon-wrap icon-patients">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    </div>
                    <div class="pmi-stat-details">
                        <h3 id="pmi-stat-patients">0</h3>
                        <p>Registered patients</p>
                    </div>
                </div>
            </div>

            <!-- PMI Filter & Search Toolbar -->
            <div class="pmi-action-toolbar">
                <div class="pmi-search-wrap">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <input type="text" id="pmiSearchInput" class="form-control pmi-search-input" placeholder="Search patient name or ID..." oninput="filterPMITable()">
                </div>
                <div class="pmi-filters-wrap">
                    <div class="filter-item">
                        <label for="pmiStatusFilter" class="toolbar-label">Status:</label>
                        <select id="pmiStatusFilter" class="form-control pmi-select" onchange="filterPMITable()">
                            <option value="all">All Statuses</option>
                            <option value="inquiry">Inquiries Only</option>
                            <option value="for_approval">For Approval / Processing</option>
                            <option value="approved">Approved</option>
                            <option value="arrived">Arrived (Triage)</option>
                            <option value="ongoing">Ongoing Consultation</option>
                            <option value="completed">Completed &amp; Signed Off</option>
                            <option value="no-show">No-Show / Rejected</option>
                            <option value="transferred">Transferred</option>
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
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.5 2v6h6M21.5 22v-6h-6"/><path d="M22 11.5A10 10 0 0 0 3.2 7.2M2 12.5a10 10 0 0 0 18.8 4.3"/></svg>
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

        <!-- Reports & Analytics Module (All Time Multi-Department Hub) -->
        <section id="view-reports" class="view-section">
            <div class="header-flex queue-header-flex">
                <div>
                    <h1>All Time Reports & Analytics</h1>
                    <p>Central oversight across General Medical, Neurology, and Psychiatry staff portals.</p>
                </div>
                <div style="display:flex; gap:10px;">
                    <button class="btn btn-outline" id="btnReportPrint" onclick="printReports()" title="Print executive clinical statistical report">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                        <span>Print Report</span>
                    </button>
                    <button class="btn btn-primary" id="btnReportExport" onclick="exportReportsCsv()" title="Download all time patient dataset as CSV">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        <span>Export CSV</span>
                    </button>
                </div>
            </div>

            <!-- Report Filter Toolbar -->
            <div class="rbac-action-toolbar pmi-action-toolbar" style="margin-bottom:20px;">
                <div class="pmi-filters-wrap" style="width:100%; justify-content:flex-start;">
                    <div class="filter-item">
                        <label for="reportPeriodFilter" class="toolbar-label">Period / Range:</label>
                        <select id="reportPeriodFilter" class="form-control pmi-select" onchange="loadReports()">
                            <option value="all">All Time</option>
                            <option value="daily">Today (Daily Aggregate)</option>
                            <option value="monthly">This Month (Monthly Aggregate)</option>
                        </select>
                    </div>
                    <div class="filter-item">
                        <label for="reportDeptFilter" class="toolbar-label">Department Hub:</label>
                        <select id="reportDeptFilter" class="form-control pmi-select" onchange="loadReports()">
                            <option value="all">All Departments (All Time)</option>
                            <option value="general">General Medical</option>
                            <option value="neurology">Neurology</option>
                            <option value="psychiatry">Psychiatry</option>
                        </select>
                    </div>
                    <button class="btn btn-outline btn-reset-filters" onclick="resetReportFilters()" title="Refresh Report Filters">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.5 2v6h6M21.5 22v-6h-6"/><path d="M22 11.5A10 10 0 0 0 3.2 7.2M2 12.5a10 10 0 0 0 18.8 4.3"/></svg>
                        <span>Refresh</span>
                    </button>
                    <span id="reportLastUpdated" style="margin-left:auto; font-size:0.8rem; color:#64748b; align-self:center;"></span>
                </div>
            </div>

            <!-- All Time KPI Overview -->
            <div class="reports-kpi-grid">
                <div class="report-kpi-card">
                    <div class="report-kpi-label">All Time Patients</div>
                    <div class="report-kpi-val" id="repKpiTotal">0</div>
                    <div class="report-kpi-sub" id="repKpiSubTotal">Loading records...</div>
                </div>
                <div class="report-kpi-card kpi-blue">
                    <div class="report-kpi-label">Completed Services</div>
                    <div class="report-kpi-val" id="repKpiCompleted">0</div>
                    <div class="report-kpi-sub" id="repKpiSubCompleted">0% completion rate</div>
                </div>
                <div class="report-kpi-card kpi-purple">
                    <div class="report-kpi-label">Active / Ongoing Care</div>
                    <div class="report-kpi-val" id="repKpiOngoing">0</div>
                    <div class="report-kpi-sub" id="repKpiSubOngoing">In triage & consultation</div>
                </div>
                <div class="report-kpi-card kpi-rose">
                    <div class="report-kpi-label">No-Show Non-Attendance</div>
                    <div class="report-kpi-val" id="repKpiNoshow">0</div>
                    <div class="report-kpi-sub" id="repKpiSubNoshow">0% no-show rate</div>
                </div>
            </div>

            <!-- Statistical Reports & Analytics Graph Summary -->
            <div class="dashboard-section" style="margin: 20px 0 24px 0;">
                <div class="section-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                    <div>
                        <h2 style="font-size:1.15rem; font-weight:700; color:#0f172a; margin:0 0 4px 0;">Statistical Reports & Analytics Summary</h2>
                        <p style="margin:0; font-size:0.88rem; color:#64748b;">Departmental comparison of total visits, completed consultations, walk-ins, online bookings, and no-shows.</p>
                    </div>
                </div>
                <div style="width:100%; height:310px; margin-top:16px;">
                    <canvas id="reportsAnalyticsChart"></canvas>
                </div>
            </div>

            <!-- Report Tab Navigation -->
            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:24px; margin-bottom:14px;">
                <div class="report-view-nav">
                    <button type="button" class="report-tab-btn active" id="tabBtnDaily" onclick="switchReportTab('daily')">Daily Statistical Report</button>
                    <button type="button" class="report-tab-btn" id="tabBtnMonthly" onclick="switchReportTab('monthly')">Monthly Statistical Report</button>
                    <button type="button" class="report-tab-btn" id="tabBtnRoster" onclick="switchReportTab('roster')">All Time Patient Records</button>
                </div>
            </div>

            <!-- Daily Table Card -->
            <div id="reportSectionDaily" class="custom-table-card">
                <div class="custom-table-header">
                    <table class="data-table">
                        <colgroup>
                            <col style="width: 15%;">
                            <col style="width: 23%;">
                            <col style="width: 12%;">
                            <col style="width: 12%;">
                            <col style="width: 13%;">
                            <col style="width: 12%;">
                            <col style="width: 13%;">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Department</th>
                                <th>Total Visits</th>
                                <th>Walk-ins</th>
                                <th>Online Appts</th>
                                <th>Completed</th>
                                <th>Success Rate</th>
                            </tr>
                        </thead>
                    </table>
                </div>
                <div class="custom-table-body" id="repDaily-scroll-body" style="max-height:360px;">
                    <table class="data-table">
                        <colgroup>
                            <col style="width: 15%;">
                            <col style="width: 23%;">
                            <col style="width: 12%;">
                            <col style="width: 12%;">
                            <col style="width: 13%;">
                            <col style="width: 12%;">
                            <col style="width: 13%;">
                        </colgroup>
                        <tbody id="reportDailyTbody">
                            <tr><td colspan="7" style="text-align:center; padding:30px;">Loading daily report...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Monthly Table Card -->
            <div id="reportSectionMonthly" class="custom-table-card" style="display:none;">
                <div class="custom-table-header">
                    <table class="data-table">
                        <colgroup>
                            <col style="width: 16%;">
                            <col style="width: 22%;">
                            <col style="width: 12%;">
                            <col style="width: 13%;">
                            <col style="width: 13%;">
                            <col style="width: 12%;">
                            <col style="width: 12%;">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Department</th>
                                <th>Total Volume</th>
                                <th>Walk-in Queue</th>
                                <th>Online Appts</th>
                                <th>Completed</th>
                                <th>Completion Rate</th>
                            </tr>
                        </thead>
                    </table>
                </div>
                <div class="custom-table-body" id="repMonthly-scroll-body" style="max-height:360px;">
                    <table class="data-table">
                        <colgroup>
                            <col style="width: 16%;">
                            <col style="width: 22%;">
                            <col style="width: 12%;">
                            <col style="width: 13%;">
                            <col style="width: 13%;">
                            <col style="width: 12%;">
                            <col style="width: 12%;">
                        </colgroup>
                        <tbody id="reportMonthlyTbody">
                            <tr><td colspan="7" style="text-align:center; padding:30px;">Loading monthly report...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Consolidated Roster Table Card -->
            <div id="reportSectionRoster" class="custom-table-card" style="display:none;">
                <div class="custom-table-header">
                    <table class="data-table">
                        <colgroup>
                            <col style="width: 14%;">
                            <col style="width: 20%;">
                            <col style="width: 18%;">
                            <col style="width: 14%;">
                            <col style="width: 14%;">
                            <col style="width: 20%;">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Patient ID</th>
                                <th>Full Name</th>
                                <th>Department</th>
                                <th>Visit Type</th>
                                <th>Status</th>
                                <th>Doctor / Diagnosis</th>
                            </tr>
                        </thead>
                    </table>
                </div>
                <div class="custom-table-body" id="repRoster-scroll-body" style="max-height:360px;">
                    <table class="data-table">
                        <colgroup>
                            <col style="width: 14%;">
                            <col style="width: 20%;">
                            <col style="width: 18%;">
                            <col style="width: 14%;">
                            <col style="width: 14%;">
                            <col style="width: 20%;">
                        </colgroup>
                        <tbody id="reportRosterTbody">
                            <tr><td colspan="6" style="text-align:center; padding:30px;">Loading all time records...</td></tr>
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
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <input type="text" id="auditSearchInput" class="form-control pmi-search-input" placeholder="Search action, user, or details..." oninput="filterAndSortAuditTable()">
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
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.5 2v6h6M21.5 22v-6h-6"/><path d="M22 11.5A10 10 0 0 0 3.2 7.2M2 12.5a10 10 0 0 0 18.8 4.3"/></svg>
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
                        <svg id="eyeIcon-staffPass" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
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
                <button type="button" class="btn btn-primary" onclick="submitStaff()">Save Staff</button>
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
                        <svg id="eyeIcon-editStaffPass" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
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
                    <button type="button" class="btn btn-primary" onclick="submitEditStaff()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom Confirm Modal -->
    <div id="confirmModal" class="modal" style="z-index: 1050;">
        <div class="modal-content" style="max-width: 400px; text-align: center; padding: 2rem;">
            <div style="margin-bottom: 1.5rem;">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto;">
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
            <div class="form-group">
                <label>Doctor Name <span class="required">*</span></label>
                <input type="text" id="docName" class="form-control" placeholder="e.g. Dr. Maria Santos" autocomplete="off" oninput="this.classList.remove('input-error')">
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
                    <button class="btn btn-primary" type="button" onclick="submitDoctorForm()">Save Doctor</button>
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
</body>
</html>
