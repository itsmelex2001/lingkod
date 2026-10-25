<?php
session_start();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
if (empty($_SESSION['user']) || $_SESSION['role'] !== 'staff') {
    header('Location: /sdn/pages/login.php');
    exit;
}

require_once __DIR__ . '/../api/config/db.php';

// Live database data-binding for staff identity
$userFullName = 'Medical Staff';
$deptLabel = !empty($_SESSION['department']) ? $_SESSION['department'] : 'Medical Staff';
$userProfileImage = null;

try {
    $stmtUser = $pdo->prepare("SELECT id, username, full_name, department, role, status, profile_image FROM users WHERE username = ? LIMIT 1");
    $stmtUser->execute([$_SESSION['user']]);
    $uRow = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if ($uRow) {
        if (!empty($uRow['full_name'])) {
            $userFullName = $uRow['full_name'];
            $_SESSION['full_name'] = $uRow['full_name'];
        } else {
            $userFullName = ucwords(str_replace(['.', '_'], ' ', $_SESSION['user']));
        }

        if (!empty($uRow['department'])) {
            $deptLabel = $uRow['department'];
            $_SESSION['department'] = $uRow['department'];
        }

        $userProfileImage = $uRow['profile_image'] ?: null;
        $_SESSION['profile_image'] = $userProfileImage;
    }
} catch (Exception $e) {
    if (!empty($_SESSION['full_name']) && $_SESSION['full_name'] !== 'null') {
        $userFullName = $_SESSION['full_name'];
    }
}

$nameParts = preg_split('/\s+/', trim($userFullName));
$initials = '';
if (count($nameParts) >= 2) {
    $initials = strtoupper(mb_substr($nameParts[0], 0, 1) . mb_substr(end($nameParts), 0, 1));
} else {
    $initials = strtoupper(mb_substr($userFullName, 0, 2));
}
$_SESSION['last_activity'] = time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff Portal | SDN</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/sdn/public/css/global.css?v=<?= time() ?>" />
    <link rel="stylesheet" href="/sdn/public/css/staff-portal.css?v=<?= time() ?>" />
</head>
<body class="portal-layout">
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-brand">
                <img src="/sdn/public/assets/mmwgh_logo.png" alt="MMWGH Logo" class="sidebar-brand-logo">
                <div class="sidebar-brand-text">
                    <span class="brand-title">Medical Staff</span>
                    <span class="brand-subtitle"><?= htmlspecialchars(ucwords(strtolower($_SESSION['department'] ?? 'General'))) ?></span>
                </div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="#" class="nav-link active" data-view="queue">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                Department Queue
            </a>
            <a href="#" class="nav-link" data-view="emr">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"></path></svg>
                EMR Tracker
            </a>
            <a href="#" class="nav-link" data-view="profile" title="Manage your account settings and credentials">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                Account Settings
            </a>
        </nav>
        <div class="sidebar-footer">
            <a href="/sdn/pages/logout.php" class="sidebar-logout-btn" title="Sign out of staff portal">
                <svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                    <polyline points="10 17 5 12 10 7"></polyline>
                    <line x1="15" y1="12" x2="5" y2="12"></line>
                </svg>
                <span>Logout</span>
            </a>
        </div>
    </aside>
    <main class="main-content">
        <!-- Queue -->
        <section id="view-queue" class="view-section active">
            <div class="header-flex queue-header-flex">
                <div>
                    <h1>Patient Queue</h1>
                    <p>Manage appointments and walk-ins for your department.</p>
                </div>
                <div class="staff-profile-badge" onclick="openStaffProfilePage()" title="Account Settings" style="cursor: pointer;">
                    <div class="staff-avatar-circle staff-avatar-sync" title="<?= htmlspecialchars($userFullName) ?>">
                        <?php if (!empty($userProfileImage)): ?>
                            <img src="<?= htmlspecialchars($userProfileImage) ?>" alt="Avatar" class="avatar-img-elem">
                        <?php else: ?>
                            <span class="avatar-initials-elem"><?= htmlspecialchars($initials) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="staff-profile-details">
                        <span class="staff-profile-name header-profile-name"><?= htmlspecialchars($userFullName) ?></span>
                    </div>
                </div>
            </div>

            <!-- Clinical Arrival & Workflow Pipeline Tracker -->
            <div class="arrival-workflow-banner">
                <div class="workflow-steps-track">
                    <div class="workflow-step-pill is-current" title="Step 1: Patient arrives at MMWGH facility">
                        <span class="workflow-step-num">1</span>
                        <span>Arrival (Check-In)</span>
                    </div>
                    <div class="workflow-step-arrow">→</div>
                    <div class="workflow-step-pill" title="Step 2: Nursing triage vital signs">
                        <span class="workflow-step-num">2</span>
                        <span>Triage Vitals</span>
                    </div>
                    <div class="workflow-step-arrow">→</div>
                    <div class="workflow-step-pill" title="Step 3: Clinical consultation with physician">
                        <span class="workflow-step-num">3</span>
                        <span>Ongoing Consult</span>
                    </div>
                    <div class="workflow-step-arrow">→</div>
                    <div class="workflow-step-pill" title="Step 4: Official doctor clinical sign-off & Rx">
                        <span class="workflow-step-num">4</span>
                        <span>Doctor Sign-Off</span>
                    </div>
                </div>
                <button type="button" class="btn-protocol-alert" onclick="openDoubleBookingProtocolModal()">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    <span>Double Booking Protocol</span>
                </button>
            </div>

            <!-- Module Segmented Tabs: Online Appointments vs Walk-In Queue vs No-Show Registry -->
            <div class="module-segmented-bar">
                <button type="button" class="module-tab-btn active" id="tabModuleOnline" onclick="switchQueueModule('online')">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                    <span>Online Appointments</span>
                    <span class="module-tab-count" id="countOnlineBooker">0</span>
                </button>
                <button type="button" class="module-tab-btn" id="tabModuleInquiry" onclick="switchQueueModule('inquiry')">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    <span>Inquiries</span>
                    <span class="module-tab-count module-tab-count-inquiry" id="countInquiryQueue">0</span>
                </button>
                <button type="button" class="module-tab-btn" id="tabModuleWalkin" onclick="switchQueueModule('walkin')">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 4v16M17 8l-4-4-4 4M7 20h10"></path></svg>
                    <span>Walk-In Queue</span>
                    <span class="module-tab-count" id="countWalkinQueue">0</span>
                </button>
                <button type="button" class="module-tab-btn" id="tabModuleNoshow" onclick="switchQueueModule('noshow')">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                    <span>No-Show Registry</span>
                    <span class="module-tab-count module-tab-count-danger" id="countNoshowRegistry">0</span>
                </button>
            </div>

            <!-- Queue Action Toolbar -->
            <div class="queue-action-toolbar">
                <div class="toolbar-filters">
                    <div class="filter-item search-filter-item">
                        <div class="search-input-wrap">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            <input type="text" id="queueSearchInput" class="form-control queue-search-input" placeholder="Search patient name or ID..." oninput="filterQueueBySearch(this.value)">
                        </div>
                    </div>
                    <div class="filter-item">
                        <label for="queueStatusFilter" class="toolbar-label">Queue Status:</label>
                        <select id="queueStatusFilter" class="form-control queue-filter-select" onchange="filterQueueByStatus(this.value)">
                            <option value="all">All Statuses</option>
                            <option value="for_approval">For Approval / Processing</option>
                            <option value="approved">Approved</option>
                            <option value="arrived">Arrived (Triage)</option>
                            <option value="ongoing">Ongoing Consultation</option>
                            <option value="completed">Completed &amp; Signed Off</option>
                            <option value="transferred">Transferred</option>
                        </select>
                    </div>
                </div>
                <div class="toolbar-actions">
                    <button type="button" class="btn btn-secondary btn-manage-doc-status" onclick="openDoctorStatusModal()" title="View and mark status of department doctors">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        <span>Doctors Status</span>
                    </button>
                    <button class="btn btn-primary btn-quick-walkin" onclick="openWalkinModal()">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        <span>Quick Add Walk-in</span>
                    </button>
                </div>
            </div>

            <!-- Custom Table Card with Fixed Header and Scrollable Patient List -->
            <div class="custom-table-card queue-table-card">
                <div class="custom-table-header">
                    <table class="data-table">
                        <colgroup>
                            <col style="width: 23%;">
                            <col style="width: 20%;">
                            <col style="width: 19%;">
                            <col style="width: 38%;">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Patient Details</th>
                                <th>Status / Assigned</th>
                                <th>Automated SMS</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
                <div class="custom-table-body" id="queue-scroll-body">
                    <table class="data-table">
                        <colgroup>
                            <col style="width: 23%;">
                            <col style="width: 20%;">
                            <col style="width: 19%;">
                            <col style="width: 38%;">
                        </colgroup>
                        <tbody id="queue-tbody">
                            <tr><td colspan="4">Loading queue...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- EMR -->
        <section id="view-emr" class="view-section">
            <div class="header-flex queue-header-flex">
                <div>
                    <h1>EMR Tracker</h1>
                    <p>Electronic Medical Records & Patient History.</p>
                </div>
                <div class="staff-profile-badge" onclick="openStaffProfilePage()" title="Account Settings" style="cursor: pointer;">
                    <div class="staff-avatar-circle staff-avatar-sync" title="<?= htmlspecialchars($userFullName) ?>">
                        <?php if (!empty($userProfileImage)): ?>
                            <img src="<?= htmlspecialchars($userProfileImage) ?>" alt="Avatar" class="avatar-img-elem">
                        <?php else: ?>
                            <span class="avatar-initials-elem"><?= htmlspecialchars($initials) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="staff-profile-details">
                        <span class="staff-profile-name header-profile-name"><?= htmlspecialchars($userFullName) ?></span>
                    </div>
                </div>
            </div>
            <div class="custom-table-card emr-table-card">
                <div class="custom-table-header">
                    <table class="data-table">
                        <colgroup>
                            <col style="width: 24%;">
                            <col style="width: 12%;">
                            <col style="width: 36%;">
                            <col style="width: 28%;">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Patient Details</th>
                                <th>Last Visit</th>
                                <th>Diagnosis / Note</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
                <div class="custom-table-body" id="emr-scroll-body">
                    <table class="data-table">
                        <colgroup>
                            <col style="width: 24%;">
                            <col style="width: 12%;">
                            <col style="width: 36%;">
                            <col style="width: 28%;">
                        </colgroup>
                        <tbody id="emr-tbody">
                            <tr><td colspan="4">Loading records...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- Account Settings (In-Page View) -->
        <section id="view-profile" class="view-section">
            <div class="header-flex queue-header-flex profile-view-header">
                <div>
                    <h1>Account Settings</h1>
                    <p>Manage your staff profile credentials, photo, and account security.</p>
                </div>
            </div>

            <!-- Profile Hero Identity Banner -->
            <div class="profile-hero-banner">
                <div class="profile-hero-left">
                    <div class="profile-avatar-wrapper" id="profileAvatarWrapper">
                        <div class="profile-avatar-large" id="profileAvatarLarge" onclick="document.getElementById('staffAvatarInput').click()" title="Click to upload new photo">
                            <?php if (!empty($userProfileImage)): ?>
                                <img src="<?= htmlspecialchars($userProfileImage) ?>" alt="Avatar" id="profileLargeImg" class="profile-large-img">
                            <?php else: ?>
                                <span id="profileLargeInitials" class="profile-large-initials"><?= htmlspecialchars($initials) ?></span>
                            <?php endif; ?>
                            <div class="avatar-hover-overlay">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                                <span>Change</span>
                            </div>
                        </div>
                        <label for="staffAvatarInput" class="avatar-upload-badge" title="Upload new photo">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                        </label>
                        <input type="file" id="staffAvatarInput" accept="image/png, image/jpeg, image/webp" style="display:none;" onchange="handleStaffAvatarSelect(event)">
                    </div>

                    <div class="profile-hero-details">
                        <div class="profile-hero-heading-row">
                            <h2 class="profile-hero-name" id="profileCardName"><?= htmlspecialchars($userFullName) ?></h2>
                            <span class="profile-chip chip-status"><span class="status-pulse-dot"></span> Active Duty</span>
                        </div>
                        <div class="profile-pills-row">
                            <span class="profile-chip chip-dept">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 21h18M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16"></path><path d="M9 9h1M9 13h1M9 17h1M14 9h1M14 13h1M14 17h1"></path></svg>
                                <span id="profileHeroDept"><?= htmlspecialchars($deptLabel) ?></span>
                            </span>
                            <span class="profile-chip chip-role">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                <span>Medical Staff</span>
                            </span>
                            <span class="profile-chip chip-user">
                                <span>@</span><span id="profileCardUsername"><?= htmlspecialchars($_SESSION['user'] ?? '') ?></span>
                            </span>
                        </div>
                        <div class="profile-hero-photo-actions">
                            <button type="button" class="btn-hero-action btn-hero-upload" onclick="document.getElementById('staffAvatarInput').click()">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                                <span>Upload Photo</span>
                            </button>
                            <button type="button" class="btn-hero-action btn-hero-remove" id="btnRemoveAvatar" onclick="removeStaffAvatar()" style="<?= empty($userProfileImage) ? 'display:none;' : '' ?>">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                <span>Remove</span>
                            </button>
                            <span class="profile-hero-photo-hint">JPG, PNG, WebP (Max 5MB) &bull; Auto-saves photo instantly</span>
                        </div>
                    </div>
                </div>

                <div class="profile-hero-badges-strip">
                    <div class="hero-strip-item">
                        <div class="strip-label-col">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                            <span class="strip-label">Access Level</span>
                        </div>
                        <div class="strip-val-col">
                            <span class="strip-val">Staff Portal &bull; Full Access</span>
                        </div>
                    </div>
                    <div class="hero-strip-item">
                        <div class="strip-label-col">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                            <span class="strip-label">Hospital Routing</span>
                        </div>
                        <div class="strip-val-col">
                            <span class="strip-val"><?= htmlspecialchars($deptLabel) ?> Queue</span>
                        </div>
                    </div>
                    <div class="hero-strip-item">
                        <div class="strip-label-col">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                            <span class="strip-label">Account Security</span>
                        </div>
                        <div class="strip-val-col">
                            <span class="strip-val strip-val-verified">
                                <span class="status-pulse-dot"></span>
                                <span>Verified Active Staff</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Inline Alert Notification -->
            <div id="profilePageAlert" style="display:none;"></div>

            <!-- Settings Form Grid -->
            <form id="staffProfilePageForm" onsubmit="submitStaffProfilePage(event)">
                <div class="profile-columns-grid">
                    <!-- Left Column: Personal Information -->
                    <div class="profile-section-card">
                        <div class="profile-section-header">
                            <div class="section-icon-badge">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            </div>
                            <div>
                                <h3 class="profile-section-title">Personal Information</h3>
                                <p class="profile-section-desc">Manage your full display name and clinical account credentials.</p>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="profilePageFullName">Full Legal Display Name <span class="required-star">*</span></label>
                            <input type="text" id="profilePageFullName" class="form-control" value="<?= htmlspecialchars($userFullName) ?>" placeholder="Enter your full legal name" autocomplete="name">
                            <span class="field-error-feedback" id="err-profilePageFullName" style="display:none;"></span>
                            <small class="form-text-hint">Displayed on patient queue notices, EMR clinical records, and doctor consultations.</small>
                        </div>

                        <div class="form-row-2">
                            <div class="form-group">
                                <label for="profilePageUsername">System Username <span class="badge-locked-pill"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg> Managed</span></label>
                                <input type="text" id="profilePageUsername" class="form-control field-locked" value="<?= htmlspecialchars($_SESSION['user'] ?? '') ?>" disabled>
                                <small class="form-text-hint">Assigned hospital staff identifier.</small>
                            </div>
                            <div class="form-group">
                                <label for="profilePageDept">Department <span class="badge-locked-pill"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg> Sync</span></label>
                                <input type="text" id="profilePageDept" class="form-control field-locked" value="<?= htmlspecialchars($deptLabel) ?>" disabled>
                                <small class="form-text-hint">Routing linked to department queue.</small>
                            </div>
                        </div>

                        <div class="profile-card-callout">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M12 16v-4"></path><path d="M12 8h.01"></path></svg>
                            <div>
                                <strong>Staff Permissions</strong>
                                <p>You have full clinical staff duty access to patient queues, walk-in registration, triage vital signs, and EMR documentation for <strong><?= htmlspecialchars($deptLabel) ?></strong>.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Account Security -->
                    <div class="profile-section-card">
                        <div class="profile-section-header">
                            <div class="section-icon-badge badge-security">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                            </div>
                            <div>
                                <h3 class="profile-section-title">Account Security</h3>
                                <p class="profile-section-desc">Update your password to keep your staff account secure.</p>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="profilePageNewPass">New Password</label>
                            <div class="password-input-wrap">
                                <input type="password" id="profilePageNewPass" class="form-control" placeholder="Min. 6 characters (leave blank to keep current)" autocomplete="new-password">
                                <button type="button" class="btn-toggle-pass" onclick="togglePassView('profilePageNewPass', this)" title="Show/Hide Password">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                </button>
                            </div>
                            <span class="field-error-feedback" id="err-profilePageNewPass" style="display:none;"></span>
                        </div>

                        <div class="form-group">
                            <label for="profilePageConfirmPass">Confirm New Password</label>
                            <div class="password-input-wrap">
                                <input type="password" id="profilePageConfirmPass" class="form-control" placeholder="Repeat new password" autocomplete="new-password">
                                <button type="button" class="btn-toggle-pass" onclick="togglePassView('profilePageConfirmPass', this)" title="Show/Hide Password">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                </button>
                            </div>
                            <span class="field-error-feedback" id="err-profilePageConfirmPass" style="display:none;"></span>
                        </div>

                        <div class="security-guide-box">
                            <div class="security-guide-title">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                                <span>Security Recommendations</span>
                            </div>
                            <ul class="security-guide-list">
                                <li>Leave password fields blank if you do not wish to change your password.</li>
                                <li>New passwords must be at least 6 characters in length.</li>
                                <li>Never share your staff credentials or login details with anyone.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Bottom Action Bar -->
                <div class="profile-bottom-bar">
                    <button type="button" class="btn btn-outline btn-reset-profile" onclick="resetStaffProfileForm()" title="Reset all unsaved changes">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path><path d="M3 3v5h5"></path></svg>
                        <span>Reset</span>
                    </button>
                    <button type="submit" class="btn btn-primary btn-save-profile" id="btnSaveProfileBottom">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                        <span>Save Changes</span>
                    </button>
                </div>
            </form>
        </section>
    </main>
    
    <!-- Walk-in Modal with Centralized Returning Patient Verification -->
    <div id="walkinModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <div>
                    <h2>Register Walk-in Patient</h2>
                    <p style="margin:4px 0 0 0; font-size:0.88rem; color:#64748b;">Lookup returning patients to link medical history or register new arrivals.</p>
                </div>
                <span class="close">&times;</span>
            </div>

            <!-- Centralized Returning Patient Lookup Widget -->
            <div class="returning-lookup-panel">
                <div class="returning-lookup-header">
                    <h4>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        <span>Returning Patient Quick Lookup</span>
                    </h4>
                    <span style="font-size:0.75rem; color:#64748b;">(Avoids duplicate profiles)</span>
                </div>
                <div class="returning-lookup-input-group">
                    <input type="text" id="walkinLookupQuery" class="form-control" placeholder="Search by Patient ID / HRN or Full Name..." onkeydown="if(event.key==='Enter'){event.preventDefault();searchReturningWalkinPatient();}">
                    <button type="button" class="btn btn-outline" id="btnWalkinLookup" onclick="searchReturningWalkinPatient()" style="white-space:nowrap; padding:8px 14px;">
                        <span>Lookup Record</span>
                    </button>
                </div>
                <!-- Lookup Match Notification Card -->
                <div id="walkinMatchCard" class="returning-match-card" style="display:none;">
                    <div class="returning-match-info">
                        <div><strong id="walkinMatchName">—</strong> <span id="walkinMatchId" style="font-size:0.8rem; background:#d1fae5; padding:2px 6px; border-radius:4px; font-weight:700;"></span></div>
                        <div style="font-size:0.78rem; margin-top:2px;" id="walkinMatchMeta">—</div>
                    </div>
                    <button type="button" class="btn btn-outline" onclick="clearWalkinReturningMatch()" style="padding:4px 10px; font-size:0.78rem; background:#ffffff;">
                        Clear / New
                    </button>
                </div>
            </div>

            <input type="hidden" id="walkinPatientId" value="">
            <input type="hidden" id="walkinIsReturning" value="0">
            <input type="hidden" id="walkinHrn" value="">

            <div class="form-group">
                <label for="walkinName">Full Name <span style="color:#ef4444;">*</span></label>
                <input type="text" id="walkinName" class="form-control" placeholder="e.g. Juan Dela Cruz">
                <span class="field-error-feedback" id="err-walkinName" style="display:none;"></span>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                <div class="form-group">
                    <label for="walkinDob">Date of Birth</label>
                    <input type="date" id="walkinDob" class="form-control">
                    <span class="field-error-feedback" id="err-walkinDob" style="display:none;"></span>
                </div>
                <div class="form-group">
                    <label for="walkinGender">Gender</label>
                    <select id="walkinGender" class="form-control">
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                    <span class="field-error-feedback" id="err-walkinGender" style="display:none;"></span>
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                <div class="form-group">
                    <label for="walkinPhone">Contact Number</label>
                    <input type="text" id="walkinPhone" class="form-control" placeholder="e.g. 09171234567">
                    <span class="field-error-feedback" id="err-walkinPhone" style="display:none;"></span>
                </div>
                <div class="form-group">
                    <label for="walkinAddress">City / Address</label>
                    <input type="text" id="walkinAddress" class="form-control" placeholder="e.g. Manila">
                    <span class="field-error-feedback" id="err-walkinAddress" style="display:none;"></span>
                </div>
            </div>

            <div class="form-group">
                <label for="walkinComplaints">Chief Complaints <span style="color:#ef4444;">*</span></label>
                <textarea id="walkinComplaints" class="form-control" rows="3" placeholder="Describe symptoms or reasons for clinic visit..."></textarea>
                <span class="field-error-feedback" id="err-walkinComplaints" style="display:none;"></span>
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:20px; padding-top:14px; border-top:1px solid #f1f5f9;">
                <div id="walkinRecordTypeTag" style="font-size:0.83rem; font-weight:600; color:#059669;">
                    ● New Patient Registration
                </div>
                <div style="display:flex; gap:10px;">
                    <button type="button" class="btn btn-outline btn-modal-cancel">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitWalkin()">Register &amp; Admit</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Referral Modal (Self-Department Transfer Protected) -->
    <div id="referralModal" class="modal">
        <div class="modal-content" style="max-width: 520px;">
            <div class="modal-header">
                <div>
                    <h2>Internal Department Referral</h2>
                    <p style="margin:4px 0 0 0; font-size:0.88rem; color:#64748b;">Transfer patient record to another clinical department.</p>
                </div>
                <span class="close">&times;</span>
            </div>
            <input type="hidden" id="referralPatientId">
            <div id="referralCurrentDeptNotice" style="background:#f5f3ff; border:1px solid #ddd6fe; border-radius:8px; padding:10px 14px; margin-bottom:14px; font-size:0.85rem; color:#5b21b6;">
                <!-- Populated dynamically -->
            </div>
            <div class="form-group">
                <label for="referralDept">Select Receiving Department <span style="color:#ef4444;">*</span></label>
                <select id="referralDept" class="form-control">
                    <option value="OPU Psychiatry">OPU Psychiatry</option>
                    <option value="Neurology">Neurology</option>
                    <option value="General Management">General Management</option>
                </select>
                <span class="field-error-feedback" id="err-referralDept" style="display:none;"></span>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <button type="button" class="btn btn-outline btn-modal-cancel">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitReferral()">Confirm Transfer</button>
            </div>
        </div>
    </div>

    <!-- Double Booking Protocol Modal (Phase 1) -->
    <div id="doubleBookingModal" class="modal">
        <div class="modal-content" style="max-width: 580px;">
            <div class="modal-header">
                <div>
                    <h2 style="display:flex; align-items:center; gap:8px;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                        <span>Double Booking Clinical Protocols</span>
                    </h2>
                    <p style="margin:4px 0 0 0; font-size:0.88rem; color:#64748b;">Standard operating guidelines when overlapping slots or concurrent bookings occur.</p>
                </div>
                <span class="close" onclick="closeDoubleBookingProtocolModal()">&times;</span>
            </div>
            <div style="padding: 10px 0;">
                <div style="background:#fffbeb; border:1px solid #fde68a; border-radius:10px; padding:14px; margin-bottom:14px;">
                    <strong style="color:#92400e; display:block; margin-bottom:6px;">Standard Operating Procedure (SOP) for Appointed Arrival Conflicts:</strong>
                    <ol style="margin:0; padding-left:20px; color:#78350f; font-size:0.88rem; line-height:1.6;">
                        <li><strong>Primary Booking Verification:</strong> Check the registration timestamp in the Details view. The patient who booked earliest holds primary entitlement to the designated time window.</li>
                        <li><strong>Arrival Sequence Check:</strong> If the secondary patient arrives earlier physically at reception, perform vital signs triage first to prevent queue delays.</li>
                        <li><strong>Standby Fast-Track Allocation:</strong> Assign the secondary patient to the nearest available doctor in clinic, or place on immediate standby for the next consultation slot.</li>
                        <li><strong>Physician Reschedule / Transfer:</strong> If the attending doctor exceeds capacity, initiate an internal transfer or coordinate mutual doctor swap in the Doctor Status menu.</li>
                    </ol>
                </div>
                <p style="font-size:0.82rem; color:#64748b; margin:0;">
                    <em>Note: The MMWGH SDN platform automatically enforces concurrency locking during online bookings. Walk-in queue admissions should always verify current doctor status.</em>
                </p>
            </div>
            <div style="display:flex; justify-content:flex-end; margin-top:18px; padding-top:12px; border-top:1px solid #f1f5f9;">
                <button type="button" class="btn btn-primary" onclick="closeDoubleBookingProtocolModal()">Acknowledge &amp; Return to Queue</button>
            </div>
        </div>
    </div>

    <!-- EMR Note Modal -->
    <div id="noteModal" class="modal">
        <div class="modal-content" style="max-width:680px;">
            <div class="modal-header">
                <div>
                    <h2>Add / Edit Clinical Note & Vitals</h2>
                    <p style="margin:4px 0 0 0; font-size:0.88rem; color:#64748b;">Record triage vitals, medical diagnosis, treatment plan, and follow-up.</p>
                </div>
                <span class="close" onclick="closeNoteModal()">&times;</span>
            </div>
            <input type="hidden" id="notePatientId">
            <input type="hidden" id="notePatientName">
            
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:15px;">
                <div class="form-group"><label>BP (mmHg)</label><input type="text" id="triageBp" class="form-control" placeholder="120/80"></div>
                <div class="form-group"><label>Temp (°C)</label><input type="number" step="0.1" id="triageTemp" class="form-control" placeholder="36.5"></div>
                <div class="form-group"><label>HR (bpm)</label><input type="number" id="triageHr" class="form-control" placeholder="80"></div>
                <div class="form-group"><label>Weight (kg)</label><input type="number" step="0.1" id="triageWeight" class="form-control" placeholder="65"></div>
            </div>

            <div class="form-group">
                <label>Diagnosis <span style="color:#ef4444;">*</span></label>
                <input type="text" id="noteDiag" class="form-control" placeholder="Clinical diagnosis">
            </div>
            <div class="form-group">
                <label>Treatment Plan <span style="color:#ef4444;">*</span></label>
                <textarea id="noteTreat" class="form-control" rows="2" placeholder="Recommended treatment regimen"></textarea>
            </div>
            <div class="form-group">
                <label>Prescription</label>
                <textarea id="notePresc" class="form-control" rows="2" placeholder="Prescribed medicines, dosage, and frequency"></textarea>
            </div>
            <div class="form-group">
                <label>Active Medications (Current)</label>
                <textarea id="activeMeds" class="form-control" rows="2" placeholder="List current meds here..."></textarea>
            </div>
            <div class="form-group">
                <label>Auto Follow-Up (Next Visit Date)</label>
                <input type="date" id="nextVisit" class="form-control">
            </div>
            
            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:24px; padding-top:16px; border-top:1px solid #f1f5f9;">
                <button type="button" class="btn btn-outline" onclick="printClinicalNote()" style="display:inline-flex; align-items:center; gap:6px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                    Print Note
                </button>
                <div style="display:flex; gap:10px;">
                    <button type="button" class="btn btn-outline" onclick="closeNoteModal()">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitNote()">Save All & Book Follow-up</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Patient Registration Details Verification Modal -->
    <div id="patientDetailsModal" class="modal">
        <div class="modal-content" style="max-width:780px; width:92%;">
            <div class="modal-header">
                <div>
                    <h2>Patient Registration Verification</h2>
                    <p style="margin:4px 0 0 0; font-size:0.88rem; color:#64748b;">Review full registration information and verify patient authenticity via phone call.</p>
                </div>
                <span class="close" onclick="closePatientDetailsModal()">&times;</span>
            </div>
            <div id="patientDetailsBody" style="max-height:60vh; overflow-y:auto; padding-right:6px;">
                <p style="text-align:center; color:#64748b;">Loading patient information...</p>
            </div>
            <div id="patientDetailsActions" class="patient-modal-actions-bar">
                <!-- Populated dynamically based on patient status -->
            </div>
        </div>
    </div>

    <!-- Doctor Status Management Modal -->
    <div id="doctorStatusModal" class="modal">
        <div class="modal-content" style="max-width: 640px;">
            <div class="modal-header">
                <div>
                    <h2>Department Doctors Status</h2>
                    <p style="margin:4px 0 0 0; font-size:0.88rem; color:#64748b;">Mark and update real-time doctor availability (In Clinic, On Break, Emergency, Off Duty) for <?= htmlspecialchars($deptLabel) ?>.</p>
                </div>
                <span class="close" onclick="closeDoctorStatusModal()">&times;</span>
            </div>
            <div id="doctorStatusListBody" style="max-height: 60vh; overflow-y: auto; padding: 12px 2px;">
                <p style="text-align:center; padding: 25px; color:#64748b;">Loading doctors...</p>
            </div>
            <div style="display:flex; justify-content:flex-end; margin-top:20px; padding-top:14px; border-top:1px solid #f1f5f9;">
                <button type="button" class="btn btn-outline" onclick="closeDoctorStatusModal()">Close</button>
            </div>
        </div>
    </div>

    <script>
        const STAFF_DEPT = <?= json_encode($_SESSION['department']) ?>;
        window.STAFF_DEPT = STAFF_DEPT;
    </script>
    <script src="/sdn/public/js/staff-portal.js?v=<?= time() ?>"></script>
</body>
</html>
