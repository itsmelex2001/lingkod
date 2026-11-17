<?php
session_start();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/../../api/super-admin/auth-guard.php';
verifySuperAdminSession(false);

$adminName = $_SESSION['full_name'] ?? $_SESSION['user'] ?? 'Super Administrator';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MMWGH LINGKOD | Super Admin Portal</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <!-- Super Admin Isolated CSS -->
    <link rel="stylesheet" href="/sdn/public/css/super-admin/super-admin.css?v=<?= time() ?>">
</head>
<body class="sa-body">
    <div class="sa-shell">
        <!-- Collapsible Sidebar -->
        <aside class="sa-sidebar" id="saSidebar">
            <div class="sa-sidebar-header">
                <a href="#" class="sa-brand">
                    <div class="sa-brand-icon">L</div>
                    <div class="sa-brand-text">
                        <span class="sa-brand-title">MMWGH LINGKOD</span>
                        <span class="sa-brand-badge">Super Admin</span>
                    </div>
                </a>
                <button type="button" class="sa-collapse-btn" id="saCollapseBtn" title="Toggle Sidebar" aria-label="Toggle Sidebar">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                </button>
            </div>

            <!-- Navigation Links -->
            <nav class="sa-nav">
                <a href="#" class="sa-nav-item active" data-view="dashboard" data-title="System Overview" data-tooltip="Overview">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="9"></rect>
                        <rect x="14" y="3" width="7" height="5"></rect>
                        <rect x="14" y="12" width="7" height="9"></rect>
                        <rect x="3" y="16" width="7" height="5"></rect>
                    </svg>
                    <span>Overview &amp; Health</span>
                </a>
                <a href="#" class="sa-nav-item" data-view="rbac" data-title="Admin &amp; RBAC Management" data-tooltip="RBAC &amp; Admins">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    <span>Admin Management</span>
                </a>
                <a href="#" class="sa-nav-item" data-view="config" data-title="Global System Configurations" data-tooltip="Global Config">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                    </svg>
                    <span>System Settings</span>
                </a>
                <a href="#" class="sa-nav-item" data-view="audit" data-title="Master Audit Trail" data-tooltip="Audit Trail">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                    <span>Master Audit Trail</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <main class="sa-main">
            <!-- Header Topbar -->
            <header class="sa-topbar">
                <div class="sa-topbar-left">
                    <h1 class="sa-page-title" id="saPageTitle">System Overview</h1>
                </div>
                <div class="sa-topbar-right">
                    <span class="sa-role-pill">Super Admin</span>
                    <div class="sa-profile-wrap" id="saProfileWrap">
                        <button type="button" class="sa-profile-btn" id="saProfileBtn" title="<?= htmlspecialchars($adminName) ?>" aria-label="Account options">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </button>
                        <div class="sa-profile-dropdown" id="saProfileDropdown">
                            <a href="/sdn/pages/super-admin/logout.php" class="sa-profile-logout-btn">
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

            <!-- Dynamic View Panels -->
            <div class="sa-content">
                <!-- VIEW 1: Overview & System Health -->
                <section id="view-dashboard" class="sa-view-panel">
                    <!-- Metrics Row -->
                    <div class="sa-stats-grid">
                        <div class="sa-stat-card">
                            <div class="sa-stat-top">
                                <h3>Active Regular Admins</h3>
                                <svg class="sa-stat-icon-solid" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path d="M10 9a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM6 8a2 2 0 1 1-4 0 2 2 0 0 1 4 0ZM1.49 15.326a.78.78 0 0 1-.358-.442 3 3 0 0 1 4.308-3.516 6.484 6.484 0 0 0-1.905 3.959c-.023.222-.014.442.025.654a4.97 4.97 0 0 1-2.07-.655ZM16.44 15.98a4.97 4.97 0 0 0 2.07-.654.78.78 0 0 0 .357-.442 3 3 0 0 0-4.308-3.517 6.484 6.484 0 0 1 1.907 3.96 2.32 2.32 0 0 1-.026.654ZM18 8a2 2 0 1 1-4 0 2 2 0 0 1 4 0ZM5.304 16.19a.844.844 0 0 1-.277-.71 5 5 0 0 1 9.947 0 .843.843 0 0 1-.277.71A6.975 6.975 0 0 1 10 18a6.974 6.974 0 0 1-4.696-1.81Z" />
                                </svg>
                            </div>
                            <p class="sa-stat-val" id="metricAdmins">--</p>
                        </div>

                        <div class="sa-stat-card">
                            <div class="sa-stat-top">
                                <h3>Total Clinical Staff</h3>
                                <svg class="sa-stat-icon-solid" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path d="M11 5a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM2.615 16.428a1.224 1.224 0 0 1-.569-1.175 6.002 6.002 0 0 1 11.908 0c.058.467-.172.92-.57 1.174A9.953 9.953 0 0 1 8 18a9.953 9.953 0 0 1-5.385-1.572ZM16.25 5.75a.75.75 0 0 0-1.5 0v2h-2a.75.75 0 0 0 0 1.5h2v2a.75.75 0 0 0 1.5 0v-2h2a.75.75 0 0 0 0-1.5h-2v-2Z" />
                                </svg>
                            </div>
                            <p class="sa-stat-val" id="metricStaff">--</p>
                        </div>

                        <div class="sa-stat-card">
                            <div class="sa-stat-top">
                                <h3>Registered Patients</h3>
                                <svg class="sa-stat-icon-solid" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path d="M10 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM3.465 14.493a1.23 1.23 0 0 0 .41 1.412A9.957 9.957 0 0 0 10 18c2.31 0 4.438-.784 6.131-2.1.43-.333.604-.903.408-1.41a7.002 7.002 0 0 0-13.074.003Z" />
                                </svg>
                            </div>
                            <p class="sa-stat-val" id="metricPatients">--</p>
                        </div>

                        <div class="sa-stat-card">
                            <div class="sa-stat-top">
                                <h3>Today's Bookings</h3>
                                <svg class="sa-stat-icon-solid" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75Z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <p class="sa-stat-val" id="metricAppointments">--</p>
                        </div>
                    </div>

                    <!-- 2-Column: Maintenance Switch & System Health Monitor -->
                    <div class="sa-grid-2col">
                        <!-- Maintenance Mode Card -->
                        <div class="sa-card">
                            <div class="sa-card-header">
                                <div>
                                    <h2 class="sa-card-title">System Maintenance Mode</h2>
                                    <div class="sa-card-sub">Restricts access to Super Admins only when activated</div>
                                </div>
                            </div>

                            <div class="sa-toggle-row">
                                <div class="sa-toggle-text">
                                    <h4>Maintenance Switch</h4>
                                    <p id="saMaintenanceStatusText">Checking status...</p>
                                </div>
                                <label class="sa-switch">
                                    <input type="checkbox" id="saMaintenanceToggle" onchange="handleMaintenanceToggle()">
                                    <span class="sa-slider"></span>
                                </label>
                            </div>

                            <div class="sa-form-group">
                                <label for="saMaintenanceMsg">Public Maintenance Notice Message</label>
                                <input type="text" id="saMaintenanceMsg" class="sa-input sa-maintenance-input" placeholder="Message shown to regular admins, staff, and patients during lockout" />
                            </div>

                            <button type="button" class="sa-btn sa-btn-secondary" onclick="handleMaintenanceToggle()">
                                Update Notice Message
                            </button>
                        </div>

                        <!-- System Health Monitor Card -->
                        <div class="sa-card">
                            <div class="sa-card-header">
                                <div>
                                    <h2 class="sa-card-title">API &amp; System Health Monitor</h2>
                                    <div class="sa-card-sub">Live status of critical digital healthcare microservices</div>
                                </div>
                                <button type="button" class="sa-btn sa-btn-outline" id="saRefreshOverviewBtn" onclick="loadOverviewData()" title="Refresh telemetry">
                                    Refresh
                                </button>
                            </div>

                            <div class="sa-health-list">
                                <div class="sa-health-item">
                                    <div class="sa-health-left">
                                        <div class="sa-health-dot" id="healthDot_db"></div>
                                        <div>
                                            <h4 class="sa-health-title">MySQL Database Connection</h4>
                                            <div class="sa-health-meta" id="healthMeta_db">Checking latency...</div>
                                        </div>
                                    </div>
                                    <span class="sa-health-badge" id="healthBadge_db">Checking</span>
                                </div>

                                <div class="sa-health-item">
                                    <div class="sa-health-left">
                                        <div class="sa-health-dot" id="healthDot_smtp"></div>
                                        <div>
                                            <h4 class="sa-health-title">SMTP Email Gateway</h4>
                                            <div class="sa-health-meta" id="healthMeta_smtp">Verifying credentials...</div>
                                        </div>
                                    </div>
                                    <span class="sa-health-badge" id="healthBadge_smtp">Checking</span>
                                </div>

                                <div class="sa-health-item">
                                    <div class="sa-health-left">
                                        <div class="sa-health-dot" id="healthDot_disk"></div>
                                        <div>
                                            <h4 class="sa-health-title">Server Storage &amp; Logs</h4>
                                            <div class="sa-health-meta" id="healthMeta_disk">Inspecting drive capacity...</div>
                                        </div>
                                    </div>
                                    <span class="sa-health-badge" id="healthBadge_disk">Checking</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- VIEW 2: RBAC -->
                <section id="view-rbac" class="sa-view-panel" style="display:none;">
                    <div class="sa-card">
                        <div class="sa-table-header-bar">
                            <div>
                                <h2 class="sa-card-title">Regular Administrator Accounts</h2>
                                <div class="sa-card-sub">Manage system administrator roles and assign granular operation permissions</div>
                            </div>
                            <div style="display:flex; align-items:center; gap:12px;">
                                <div class="sa-search-wrap">
                                    <svg class="sa-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                    <input type="text" id="saAdminSearchInput" class="sa-search-input" placeholder="Search by name or username..." oninput="renderAdminsTable(allAdminsList)">
                                </div>
                                <button type="button" class="sa-btn sa-btn-primary" onclick="openCreateAdminModal()">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                    <span>Create Admin</span>
                                </button>
                            </div>
                        </div>

                        <div class="sa-table-wrap">
                            <table class="sa-table">
                                <thead>
                                    <tr>
                                        <th>Admin Name</th>
                                        <th>Username</th>
                                        <th>Account Status</th>
                                        <th>Granted Permissions</th>
                                        <th>Last Active</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="saAdminTableBody">
                                    <tr>
                                        <td colspan="6" style="text-align:center; padding:24px; color:var(--sa-muted);">Loading admin accounts...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- VIEW 3: Global System Configurations -->
                <section id="view-config" class="sa-view-panel" style="display:none;">
                    <div class="sa-config-grid">
                        <!-- Hospital Profile & Details -->
                        <div class="sa-card sa-config-section">
                            <div class="sa-card-header">
                                <div>
                                    <h2 class="sa-card-title">Hospital Information &amp; Branding</h2>
                                    <div class="sa-card-sub">Public facility metadata and institutional logo assets</div>
                                </div>
                            </div>

                            <!-- Logo Upload Box -->
                            <div class="sa-logo-uploader-box">
                                <div class="sa-logo-preview">
                                    <img id="cfgLogoPreviewImg" src="/sdn/public/assets/mmwgh_logo.png" alt="Hospital Logo">
                                </div>
                                <div>
                                    <h4 style="margin:0 0 4px; font-size:0.88rem; font-weight:700;">System Logo &amp; Seal</h4>
                                    <p style="margin:0 0 10px; font-size:0.78rem; color:var(--sa-muted);">Upload official high-resolution PNG, SVG, or WEBP hospital badge.</p>
                                    <label class="sa-btn sa-btn-outline sa-btn-sm" style="cursor:pointer; display:inline-flex;">
                                        <span>Choose Logo Image</span>
                                        <input type="file" accept="image/*" style="display:none;" onchange="uploadSystemLogo(this)">
                                    </label>
                                </div>
                            </div>

                            <div class="sa-form-group">
                                <label for="cfgHospitalName">Facility Name</label>
                                <input type="text" id="cfgHospitalName" class="sa-input">
                            </div>

                            <div class="sa-form-group">
                                <label for="cfgHospitalTagline">Institutional Tagline</label>
                                <input type="text" id="cfgHospitalTagline" class="sa-input">
                            </div>

                            <div class="sa-grid-2col" style="margin-bottom:0; gap:12px;">
                                <div class="sa-form-group">
                                    <label for="cfgContactPhone">Landline Phone</label>
                                    <input type="text" id="cfgContactPhone" class="sa-input">
                                </div>
                                <div class="sa-form-group">
                                    <label for="cfgEmergencyHotline">Emergency Hotline</label>
                                    <input type="text" id="cfgEmergencyHotline" class="sa-input">
                                </div>
                            </div>

                            <div class="sa-form-group">
                                <label for="cfgContactEmail">Public Contact Email</label>
                                <input type="email" id="cfgContactEmail" class="sa-input">
                            </div>

                            <div class="sa-form-group">
                                <label for="cfgHospitalAddress">Hospital Physical Address</label>
                                <textarea id="cfgHospitalAddress" class="sa-textarea" rows="2"></textarea>
                            </div>

                            <div>
                                <button type="button" class="sa-btn sa-btn-primary" onclick="saveHospitalConfig()">
                                    Save Facility Details
                                </button>
                            </div>
                        </div>

                        <!-- Email & SMTP Gateway -->
                        <div class="sa-card sa-config-section">
                            <div class="sa-card-header">
                                <div>
                                    <h2 class="sa-card-title">Email &amp; SMTP Verification Gateway</h2>
                                    <div class="sa-card-sub">Delivery channel for OTPs, appointments, and staff notices</div>
                                </div>
                            </div>

                            <div class="sa-grid-2col" style="margin-bottom:0; gap:12px;">
                                <div class="sa-form-group">
                                    <label for="cfgSmtpHost">SMTP Server Host</label>
                                    <input type="text" id="cfgSmtpHost" class="sa-input" placeholder="e.g. smtp.gmail.com">
                                </div>
                                <div class="sa-form-group">
                                    <label for="cfgSmtpPort">Port</label>
                                    <input type="number" id="cfgSmtpPort" class="sa-input" placeholder="587">
                                </div>
                            </div>

                            <div class="sa-form-group">
                                <label for="cfgSmtpEncryption">Encryption Protocol</label>
                                <select id="cfgSmtpEncryption" class="sa-input">
                                    <option value="tls">TLS (STARTTLS - Port 587)</option>
                                    <option value="ssl">SSL (Port 465)</option>
                                    <option value="none">None (Plaintext - Port 25)</option>
                                </select>
                            </div>

                            <div class="sa-form-group">
                                <label for="cfgSmtpUser">SMTP Username / Account</label>
                                <input type="text" id="cfgSmtpUser" class="sa-input" placeholder="e.g. notifications@mmwgh.gov.ph">
                            </div>

                            <div class="sa-form-group">
                                <label for="cfgSmtpPass">SMTP Password / App Key</label>
                                <input type="password" id="cfgSmtpPass" class="sa-input" placeholder="Leave empty to retain saved key">
                            </div>

                            <div class="sa-form-group">
                                <label for="cfgSmtpSender">Sender Display Name</label>
                                <input type="text" id="cfgSmtpSender" class="sa-input" placeholder="e.g. MMWGH LINGKOD Notifications">
                            </div>

                            <div>
                                <button type="button" class="sa-btn sa-btn-primary" onclick="saveSmtpConfig()">
                                    Save SMTP Gateway
                                </button>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- VIEW 4: Database Management & Backups -->
                <section id="view-backups" class="sa-view-panel" style="display:none;">
                    <div class="sa-card" style="max-width:800px;">
                        <div class="sa-card-header">
                            <div>
                                <h2 class="sa-card-title">Database Management &amp; Backups</h2>
                                <div class="sa-card-sub">Generate full sanitized SQL snapshots of all hospital records, schemas, and configurations</div>
                            </div>
                        </div>

                        <!-- Stats Row -->
                        <div class="sa-stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom:24px;">
                            <div class="sa-stat-card">
                                <div class="sa-stat-info">
                                    <h3>Target Database</h3>
                                    <p id="bkDbName" style="font-size:1.2rem;">--</p>
                                </div>
                            </div>
                            <div class="sa-stat-card">
                                <div class="sa-stat-info">
                                    <h3>Total Tables</h3>
                                    <p id="bkTablesCount" style="font-size:1.4rem;">--</p>
                                </div>
                            </div>
                            <div class="sa-stat-card">
                                <div class="sa-stat-info">
                                    <h3>Estimated Size</h3>
                                    <p id="bkDbSize" style="font-size:1.4rem;">--</p>
                                </div>
                            </div>
                            <div class="sa-stat-card">
                                <div class="sa-stat-info">
                                    <h3>Exports Created</h3>
                                    <p id="bkTotalCount" style="font-size:1.4rem;">--</p>
                                </div>
                            </div>
                        </div>

                        <!-- Backup Action Box -->
                        <div style="background:#f8fafc; border:1px solid var(--sa-border); border-radius:12px; padding:22px; display:flex; align-items:center; justify-content:space-between; gap:20px; flex-wrap:wrap;">
                            <div>
                                <h4 style="margin:0 0 4px; font-size:0.95rem; font-weight:700;">Full MySQL Database Dump</h4>
                                <p style="margin:0; font-size:0.82rem; color:var(--sa-muted);">
                                    Last generated backup: <strong id="bkLastDate" style="color:var(--sa-text);">Checking...</strong>
                                </p>
                            </div>
                            <button type="button" class="sa-btn sa-btn-primary" onclick="triggerSqlBackupDownload()">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="7 10 12 15 17 10"></polyline>
                                    <line x1="12" y1="15" x2="12" y2="3"></line>
                                </svg>
                                <span>Generate &amp; Download SQL Backup</span>
                            </button>
                        </div>
                    </div>
                </section>

                <!-- VIEW 5: Master Audit Trail -->
                <section id="view-audit" class="sa-view-panel" style="display:none;">
                    <div class="sa-card">
                        <div class="sa-card-header">
                            <div>
                                <h2 class="sa-card-title">System-Wide Master Audit Trail</h2>
                                <div class="sa-card-sub">Immutable transaction and security ledger across all user roles and IP addresses</div>
                            </div>
                        </div>

                        <!-- Filters -->
                        <div class="sa-filter-bar">
                            <div class="sa-filter-group">
                                <span class="sa-filter-label">Role:</span>
                                <select id="saAuditRoleFilter" class="sa-input" style="width:auto; padding:6px 10px;" onchange="loadAuditData(1)">
                                    <option value="all">All Roles</option>
                                    <option value="super_admin">Super Admin</option>
                                    <option value="admin">Admin</option>
                                    <option value="staff">Staff</option>
                                    <option value="patient">Patient</option>
                                    <option value="system">System</option>
                                </select>
                            </div>

                            <div class="sa-filter-group">
                                <span class="sa-filter-label">Action:</span>
                                <select id="saAuditActionFilter" class="sa-input" style="width:auto; padding:6px 10px;" onchange="loadAuditData(1)">
                                    <option value="all">All Actions</option>
                                    <option value="login">Login / Logout</option>
                                    <option value="create">Creations</option>
                                    <option value="update">Modifications</option>
                                    <option value="delete">Deletions</option>
                                    <option value="backup">Backups</option>
                                </select>
                            </div>

                            <div class="sa-filter-group">
                                <span class="sa-filter-label">From:</span>
                                <input type="date" id="saAuditDateFrom" class="sa-input" style="width:auto; padding:6px 10px;" onchange="loadAuditData(1)">
                            </div>

                            <div class="sa-filter-group">
                                <span class="sa-filter-label">To:</span>
                                <input type="date" id="saAuditDateTo" class="sa-input" style="width:auto; padding:6px 10px;" onchange="loadAuditData(1)">
                            </div>

                            <div style="flex:1; min-width:200px;">
                                <input type="text" id="saAuditSearch" class="sa-input" placeholder="Search actor, details, IP..." oninput="loadAuditData(1)">
                            </div>
                        </div>

                        <!-- Audit Table -->
                        <div class="sa-table-wrap">
                            <table class="sa-table">
                                <thead>
                                    <tr>
                                        <th>Timestamp</th>
                                        <th>Actor Role</th>
                                        <th>Action</th>
                                        <th>Username</th>
                                        <th>Operation Details</th>
                                        <th>IP Address</th>
                                    </tr>
                                </thead>
                                <tbody id="saAuditTableBody">
                                    <tr>
                                        <td colspan="6" style="text-align:center; padding:24px; color:var(--sa-muted);">Querying system audit logs...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination Footer -->
                        <div class="sa-pagination">
                            <span id="saAuditPaginationInfo">Page 1 of 1</span>
                            <div style="display:flex; gap:8px;">
                                <button type="button" class="sa-btn sa-btn-outline sa-btn-sm" id="saAuditPrevBtn" onclick="prevAuditPage()" disabled>Previous</button>
                                <button type="button" class="sa-btn sa-btn-outline sa-btn-sm" id="saAuditNextBtn" onclick="nextAuditPage()" disabled>Next</button>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- VIEW 6: Data Archiving & Retention -->
                <section id="view-archive" class="sa-view-panel" style="display:none;">
                    <div class="sa-card" style="max-width:850px;">
                        <div class="sa-card-header">
                            <div>
                                <h2 class="sa-card-title">Data Archiving &amp; Retention</h2>
                                <div class="sa-card-sub">Offload aged clinical transactions and logs to cold storage tables to keep production queries fast</div>
                            </div>
                        </div>

                        <!-- Retention Selector Row -->
                        <div style="background:#f8fafc; border:1px solid var(--sa-border); border-radius:12px; padding:18px 22px; margin-bottom:24px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap;">
                            <div>
                                <h4 style="margin:0 0 4px; font-size:0.92rem; font-weight:700;">Data Retention Threshold</h4>
                                <p id="saArchiveCutoffText" style="margin:0; font-size:0.8rem; color:var(--sa-muted);">Calculating cutoff date...</p>
                            </div>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <label for="saArchiveYearsSelect" style="font-size:0.84rem; font-weight:600; color:#334155;">Retention Period:</label>
                                <select id="saArchiveYearsSelect" class="sa-input" style="width:auto; padding:7px 12px;" onchange="loadArchiveData()">
                                    <option value="1">Older than 1 Year</option>
                                    <option value="2">Older than 2 Years</option>
                                    <option value="3">Older than 3 Years</option>
                                    <option value="5" selected>Older than 5 Years (Recommended)</option>
                                    <option value="7">Older than 7 Years</option>
                                </select>
                            </div>
                        </div>

                        <!-- 2-Column: Eligible vs Existing Archived -->
                        <div class="sa-grid-2col" style="margin-bottom:24px;">
                            <!-- Eligible Box -->
                            <div style="border:1px solid #fed7aa; background:#fff7ed; border-radius:12px; padding:18px;">
                                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
                                    <h4 style="margin:0; font-size:0.9rem; font-weight:700; color:#9a3412;">Eligible Active Records</h4>
                                    <span id="saTotalEligible" style="font-size:1.15rem; font-weight:800; color:#c2410c;">0</span>
                                </div>
                                <div style="display:flex; flex-direction:column; gap:6px; font-size:0.82rem; color:#7c2d12;">
                                    <div style="display:flex; justify-content:space-between;">
                                        <span>Completed Appointments:</span>
                                        <strong id="saEligibleAppts">0</strong>
                                    </div>
                                    <div style="display:flex; justify-content:space-between;">
                                        <span>Historical System Logs:</span>
                                        <strong id="saEligibleLogs">0</strong>
                                    </div>
                                </div>
                            </div>

                            <!-- Cold Storage Box -->
                            <div style="border:1px solid #bbf7d0; background:#f0fdf4; border-radius:12px; padding:18px;">
                                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
                                    <h4 style="margin:0; font-size:0.9rem; font-weight:700; color:#166534;">Archived Cold Storage</h4>
                                    <span style="font-size:0.75rem; font-weight:700; text-transform:uppercase; color:#15803d; background:#dcfce7; padding:3px 8px; border-radius:6px;">Secured</span>
                                </div>
                                <div style="display:flex; flex-direction:column; gap:6px; font-size:0.82rem; color:#14532d;">
                                    <div style="display:flex; justify-content:space-between;">
                                        <span>Archived Appointments:</span>
                                        <strong id="saExistingAppts">0</strong>
                                    </div>
                                    <div style="display:flex; justify-content:space-between;">
                                        <span>Archived System Logs:</span>
                                        <strong id="saExistingLogs">0</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Bar -->
                        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px;">
                            <span style="font-size:0.8rem; color:var(--sa-muted);">
                                Archived rows remain queryable in dedicated archive tables without cluttering active lookups.
                            </span>
                            <button type="button" class="sa-btn sa-btn-primary" onclick="executeArchiveProcess()">
                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="21 8 21 21 3 21 3 8"></polyline><rect x="1" y="3" width="22" height="5"></rect><line x1="10" y1="12" x2="14" y2="12"></line></svg>
                                <span>Archive Old Records</span>
                            </button>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Institutional Footer -->
            <footer class="sa-footer">
                <div>
                    <strong>MMWGH LINGKOD</strong> &bull; Localized Integrated Network for Governance, Knowledge, and Operation in Digital Healthcare
                </div>
                <div>
                    Mariveles Mental Wellness and General Hospital &copy; <?= date('Y') ?>
                </div>
            </footer>
        </main>
    </div>

    <!-- Admin Create / Edit Modal -->
    <div class="sa-modal-backdrop" id="saAdminModal">
        <div class="sa-modal">
            <div class="sa-modal-header">
                <h3 id="modalAdminTitle">Create Admin Account</h3>
                <button type="button" class="sa-modal-close" onclick="closeAdminModal()">&times;</button>
            </div>
            <div class="sa-modal-body">
                <input type="hidden" id="adminModalId">
                <div class="sa-form-group">
                    <label for="adminModalFullName">Full Name *</label>
                    <input type="text" id="adminModalFullName" class="sa-input" placeholder="e.g. Maria Santos">
                </div>
                <div class="sa-form-group">
                    <label for="adminModalUsername">Username *</label>
                    <input type="text" id="adminModalUsername" class="sa-input" placeholder="e.g. msantos_admin">
                </div>
                <div class="sa-form-group">
                    <label for="adminModalPassword">Password *</label>
                    <input type="password" id="adminModalPassword" class="sa-input" placeholder="At least 6 characters">
                </div>

                <div class="sa-form-group">
                    <label>Granular Admin Permissions</label>
                    <div class="sa-perm-checkboxes">
                        <label class="sa-checkbox-label">
                            <input type="checkbox" id="perm_doctors" checked>
                            <span>Can Manage Doctors &amp; Status</span>
                        </label>
                        <label class="sa-checkbox-label">
                            <input type="checkbox" id="perm_staff" checked>
                            <span>Can Manage Staff Accounts</span>
                        </label>
                        <label class="sa-checkbox-label">
                            <input type="checkbox" id="perm_export" checked>
                            <span>Can Export Records &amp; Analytics</span>
                        </label>
                        <label class="sa-checkbox-label">
                            <input type="checkbox" id="perm_verify" checked>
                            <span>Can Verify Patient Appointments</span>
                        </label>
                        <label class="sa-checkbox-label">
                            <input type="checkbox" id="perm_audit" checked>
                            <span>Can View Hospital Audit Trails</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="sa-modal-footer">
                <button type="button" class="sa-btn sa-btn-outline" onclick="closeAdminModal()">Cancel</button>
                <button type="button" class="sa-btn sa-btn-primary" onclick="saveAdminForm()">Save Account</button>
            </div>
        </div>
    </div>

    <!-- Super Admin Isolated JS -->
    <script src="/sdn/public/js/super-admin/super-admin.js?v=<?= time() ?>"></script>
</body>
</html>

