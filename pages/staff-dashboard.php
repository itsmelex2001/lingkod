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

$userFullName = 'Medical Staff';
$deptLabel    = !empty($_SESSION['department']) ? $_SESSION['department'] : 'Medical Staff';
$userId       = $_SESSION['user_id'] ?? null;
$username     = $_SESSION['user'] ?? '';

try {
    $stmtUser = $pdo->prepare("SELECT id, username, full_name, department, role, status FROM users WHERE username = ? LIMIT 1");
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
    }
} catch (Exception $e) {
    if (!empty($_SESSION['full_name']) && $_SESSION['full_name'] !== 'null') {
        $userFullName = $_SESSION['full_name'];
    }
}

$_SESSION['last_activity'] = time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Portal | LINGKOD</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/sdn/public/css/staff-dashboard-clean.css?v=<?= time() ?>">
</head>
<body>
    <div class="staff-app-container">
        <!-- ── Left Sidebar (Retained Logo & Proportions) ── -->
        <aside class="staff-sidebar">
            <div>
                <div class="sidebar-brand">
                    <div class="sidebar-brand-box">
                        <img src="/sdn/public/assets/v3_lingkod_logo_green.png" alt="LINGKOD">
                    </div>
                    <div class="brand-text-block">
                        <span class="brand-sub">Mariveles Mental Wellness and General Hospital</span>
                        <span class="brand-title">LINGKOD</span>
                        <span class="brand-role"><?= htmlspecialchars(ucwords(strtolower($deptLabel))) ?></span>
                    </div>
                </div>

                <nav class="sidebar-menu">
                    <button type="button" class="sidebar-nav-item active" data-view="consultation">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        <span>Consultation</span>
                    </button>
                    <button type="button" class="sidebar-nav-item" data-view="records">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                        <span>Records</span>
                    </button>
                    <button type="button" class="sidebar-nav-item" data-view="settings">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                        <span>Account Settings</span>
                    </button>
                </nav>
            </div>

            <div class="sidebar-footer">
                <a href="/sdn/pages/logout.php" class="sidebar-logout-btn">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 5 12 10 7"></polyline><line x1="15" y1="12" x2="5" y2="12"></line></svg>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <!-- ── Main Content Workspace ── -->
        <main class="staff-main-wrap">
            <!-- Top Header Bar (Clean Minimal, No Profile Image) -->
            <header class="staff-header">
                <div class="header-breadcrumb">
                    <span class="crumb-title">Clinical Staff Portal</span>
                </div>
                <div class="header-right-meta">
                    <div class="staff-identity-badge" onclick="switchView('settings')" title="Account Settings">
                        <span class="status-dot-online"></span>
                        <span class="identity-name" id="headerStaffName"><?= htmlspecialchars($userFullName) ?></span>
                        <span class="identity-role-tag">Medical Staff</span>
                    </div>
                </div>
            </header>

            <div class="dashboard-canvas">
                <!-- ═════════ VIEW 1: CONSULTATION ═════════ -->
                <section id="view-consultation" class="view-panel active">
                    <div class="view-header">
                        <div>
                            <h1>Consultation</h1>
                            <p>Review patient bookings, confirm schedules, and manage clinic arrivals.</p>
                        </div>
                        <div class="view-header-actions">
                            <button type="button" class="btn-clean outline" onclick="openDoctorStatusModal()">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                                <span>Doctors Status</span>
                            </button>
                            <button type="button" class="btn-clean primary" onclick="openWalkinModal()">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                <span>Add Walk-in</span>
                            </button>
                        </div>
                    </div>

                    <!-- KPI Metrics Row -->
                    <div class="metrics-grid">
                        <div class="metric-card">
                            <div class="metric-top">
                                <span class="metric-label">Today's Visits</span>
                                <span class="metric-dot green"></span>
                            </div>
                            <div class="metric-value" id="kpiTotal">0</div>
                            <div class="metric-sub">Scheduled for today</div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-top">
                                <span class="metric-label">For Approval</span>
                                <span class="metric-dot amber"></span>
                            </div>
                            <div class="metric-value" id="kpiPending">0</div>
                            <div class="metric-sub">Pending portal requests</div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-top">
                                <span class="metric-label">Confirmed &amp; Active</span>
                                <span class="metric-dot sky"></span>
                            </div>
                            <div class="metric-value" id="kpiQueue">0</div>
                            <div class="metric-sub">Approved or checked in</div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-top">
                                <span class="metric-label">Completed</span>
                                <span class="metric-dot emerald"></span>
                            </div>
                            <div class="metric-value" id="kpiCompleted">0</div>
                            <div class="metric-sub">Concluded consultations</div>
                        </div>
                    </div>

                    <!-- Consultation List Card -->
                    <div class="content-card">
                        <div class="card-toolbar">
                            <div class="toolbar-left">
                                <div class="filter-pills-group">
                                    <button type="button" class="filter-pill-btn consult-pill-filter active" data-status="all">
                                        All <span id="pillCountAll">0</span>
                                    </button>
                                    <button type="button" class="filter-pill-btn consult-pill-filter" data-status="for_approval">
                                        For Approval <span id="pillCountPending">0</span>
                                    </button>
                                    <button type="button" class="filter-pill-btn consult-pill-filter" data-status="approved">
                                        Confirmed <span id="pillCountApproved">0</span>
                                    </button>
                                    <button type="button" class="filter-pill-btn consult-pill-filter" data-status="in_clinic">
                                        In Clinic <span id="pillCountInClinic">0</span>
                                    </button>
                                    <button type="button" class="filter-pill-btn consult-pill-filter" data-status="completed">
                                        Completed <span id="pillCountCompleted">0</span>
                                    </button>
                                </div>
                            </div>
                            <div class="toolbar-right">
                                <div class="search-input-box">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                    <input type="text" id="consultSearchInput" placeholder="Search patient name or ID...">
                                </div>
                                <button type="button" class="btn-clean outline btn-icon-only" id="btnRefreshConsult" title="Refresh queue">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
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

                <!-- ═════════ VIEW 2: RECORDS (With Search & Sorting) ═════════ -->
                <section id="view-records" class="view-panel">
                    <div class="view-header">
                        <div>
                            <h1>Records</h1>
                            <p>Search, sort, and inspect patient consultation histories and clinical records.</p>
                        </div>
                        <div class="view-header-actions">
                            <button type="button" class="btn-clean outline" onclick="exportRecordsCSV()">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                <span>Export CSV</span>
                            </button>
                        </div>
                    </div>

                    <div class="content-card">
                        <div class="card-toolbar">
                            <div class="toolbar-left">
                                <div class="search-input-box" style="width: 300px;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                    <input type="text" id="recordsSearchInput" placeholder="Search patient name, ID, physician, complaint...">
                                </div>
                            </div>
                            <div class="toolbar-right">
                                <select id="recordsSortSelect" class="select-filter" aria-label="Sort records">
                                    <option value="date_desc">Sort: Newest First</option>
                                    <option value="date_asc">Sort: Oldest First</option>
                                    <option value="name_asc">Sort: Name (A – Z)</option>
                                    <option value="name_desc">Sort: Name (Z – A)</option>
                                    <option value="status">Sort: Status</option>
                                </select>
                                <select id="recordsStatusSelect" class="select-filter" aria-label="Filter status">
                                    <option value="all">All Statuses</option>
                                    <option value="completed">Completed</option>
                                    <option value="approved">Confirmed</option>
                                    <option value="transferred">Transferred</option>
                                    <option value="rejected">Cancelled</option>
                                </select>
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
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
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
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                                    <span>Update password</span>
                                </button>
                            </div>
                        </div>

                        <!-- Session Card -->
                        <div class="settings-card">
                            <div class="settings-row-item">
                                <div>
                                    <div class="settings-card-title">Active Session</div>
                                    <div class="settings-card-sub">Signed in as <?= htmlspecialchars($userFullName) ?> (<?= htmlspecialchars($deptLabel) ?>)</div>
                                </div>
                                <a href="/sdn/pages/logout.php" class="btn-clean danger-outline">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 5 12 10 7"></polyline><line x1="15" y1="12" x2="5" y2="12"></line></svg>
                                    <span>Sign out</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <!-- ── MODAL 1: Patient Details ── -->
    <div id="patientDetailsModal" class="clean-modal-backdrop">
        <div class="clean-modal-box">
            <div class="clean-modal-header">
                <h3>Patient Consultation Record</h3>
                <button type="button" class="clean-modal-close" onclick="closePatientDetailsModal()">&times;</button>
            </div>
            <div class="clean-modal-body" id="patientDetailsContent"></div>
            <div class="clean-modal-footer">
                <button type="button" class="btn-clean outline" onclick="printPatientSlipFromDetails()">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                    <span>Print Summary Slip</span>
                </button>
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
            <div class="clean-modal-body" style="display:flex; flex-direction:column; gap:1.15rem;">
                <input type="hidden" id="notePatientId">
                <div class="form-group-clean">
                    <label for="noteDiagnosis">Diagnosis <span style="color:#e11d48;">*</span></label>
                    <input type="text" id="noteDiagnosis" class="form-control-clean" placeholder="Clinical diagnosis">
                </div>
                <div class="form-group-clean">
                    <label for="noteTreatment">Treatment Plan</label>
                    <textarea id="noteTreatment" class="form-control-clean" rows="2" placeholder="Recommended treatment regimen"></textarea>
                </div>

                <!-- Drug Prescription Section -->
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:1rem; display:flex; flex-direction:column; gap:0.85rem;">
                    <div style="display:flex; align-items:center; justify-content:space-between;">
                        <span style="font-size:0.8rem; font-weight:700; color:#15803d; text-transform:uppercase; letter-spacing:0.03em;">Formulary Medicine &amp; Prescription</span>
                        <span style="font-size:0.72rem; color:#64748b;" id="dohDeptNotice">General Medicine</span>
                    </div>

                    <div class="form-group-clean">
                        <label for="noteMedicineSelect">Select Medicine / Drug</label>
                        <select id="noteMedicineSelect" class="form-control-clean" onchange="handleMedicineSelect(this.value)">
                            <option value="">Select drug / medicine...</option>
                        </select>
                    </div>

                    <div class="settings-grid-2col">
                        <div class="form-group-clean">
                            <label for="noteDosage">Dosage / Instructions</label>
                            <input type="text" id="noteDosage" class="form-control-clean" placeholder="e.g. 500mg, 1 tab TID after meals">
                        </div>
                        <div class="form-group-clean">
                            <label for="noteQuantity">Quantity</label>
                            <input type="text" id="noteQuantity" class="form-control-clean" placeholder="e.g. 14 tabs / 1 bottle">
                        </div>
                    </div>

                    <div class="form-group-clean">
                        <label for="notePrescription">Full Prescription Text</label>
                        <textarea id="notePrescription" class="form-control-clean" rows="2" placeholder="Prescription summary automatically formatted or manually edited"></textarea>
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
                    <h3>Register Walk-in Patient</h3>
                    <p style="font-size:0.78rem; color:#64748b; margin-top:2px;">Admit in-person arrival to <?= htmlspecialchars($deptLabel) ?></p>
                </div>
                <button type="button" class="clean-modal-close" onclick="closeWalkinModal()">&times;</button>
            </div>
            <div class="clean-modal-body">
                <form id="walkinForm" onsubmit="event.preventDefault(); submitWalkinPatient();" style="display:flex; flex-direction:column; gap:1rem;">
                    <div class="form-group-clean">
                        <label for="walkinFullName">Full Name <span style="color:#e11d48;">*</span></label>
                        <input type="text" id="walkinFullName" class="form-control-clean" placeholder="e.g. Juan Dela Cruz" required>
                    </div>
                    <div class="settings-grid-2col">
                        <div class="form-group-clean">
                            <label for="walkinGender">Gender <span style="color:#e11d48;">*</span></label>
                            <select id="walkinGender" class="form-control-clean" required>
                                <option value="">Select</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group-clean">
                            <label for="walkinBirthdate">Birthdate</label>
                            <input type="date" id="walkinBirthdate" class="form-control-clean">
                        </div>
                    </div>
                    <div class="settings-grid-2col">
                        <div class="form-group-clean">
                            <label for="walkinContact">Contact Number</label>
                            <input type="text" id="walkinContact" class="form-control-clean" placeholder="09171234567">
                        </div>
                        <div class="form-group-clean">
                            <label for="walkinAddress">City / Address</label>
                            <input type="text" id="walkinAddress" class="form-control-clean" placeholder="Mariveles, Bataan">
                        </div>
                    </div>
                    <div class="form-group-clean">
                        <label for="walkinComplaint">Chief Complaint <span style="color:#e11d48;">*</span></label>
                        <textarea id="walkinComplaint" class="form-control-clean" rows="2" placeholder="Describe chief complaint..." required></textarea>
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

    <!-- ── MODAL 5: Triage & Department Assignment Modal ── -->
    <div id="triageAssignModal" class="clean-modal-backdrop">
        <div class="clean-modal-box" style="max-width: 520px;">
            <div class="clean-modal-header">
                <div>
                    <h3>Triage Department Routing</h3>
                    <p style="font-size:0.78rem; color:#64748b; margin-top:2px;">Assign or confirm clinic department based on complaint</p>
                </div>
                <button type="button" class="clean-modal-close" onclick="closeTriageAssignModal()">&times;</button>
            </div>
            <div class="clean-modal-body" style="display:flex; flex-direction:column; gap:1.05rem;">
                <input type="hidden" id="triagePatientId">

                <!-- Patient Snapshot Card -->
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:0.85rem 1rem; display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <div id="triagePatientName" style="font-weight:700; color:#0f172a; font-size:0.95rem;">Patient Name</div>
                        <div id="triagePatientMeta" style="font-size:0.78rem; color:#64748b; margin-top:2px;">PT-2026-XXX &bull; Oct 31, 2026</div>
                    </div>
                    <span id="triageCurrentBadge" class="badge badge-amber" style="font-size:0.72rem; font-weight:700;">Triage Pending</span>
                </div>

                <!-- Chief Complaint Display -->
                <div class="form-group-clean">
                    <label style="font-size:0.76rem; font-weight:700; color:#475569; text-transform:uppercase; margin-bottom:4px; display:block;">
                        Reported Chief Complaint
                    </label>
                    <div id="triageComplaintText" style="background:#ffffff; border:1.5px solid #e2e8f0; border-radius:8px; padding:0.85rem 1rem; font-size:0.88rem; color:#0f172a; line-height:1.45; min-height:52px; white-space:pre-wrap;">
                        No complaint specified.
                    </div>
                </div>

                <!-- Department Selector -->
                <div class="form-group-clean">
                    <label for="triageDepartmentSelect" style="font-size:0.76rem; font-weight:700; color:#475569; text-transform:uppercase; margin-bottom:4px; display:block;">
                        Assign Clinic Department <span style="color:#e11d48;">*</span>
                    </label>
                    <select id="triageDepartmentSelect" class="form-control-clean" style="font-size:0.88rem; font-weight:600; padding:8px 12px; height:42px; border-radius:8px; border:1.5px solid #cbd5e1; width:100%; background:#ffffff;">
                        <option value="General Medical">General Medical</option>
                        <option value="Neurology">Neurology</option>
                        <option value="OPU Psychiatry">OPU Psychiatry</option>
                    </select>
                    <p style="font-size:0.74rem; color:#64748b; margin-top:5px; line-height:1.35;">
                        Review the patient's symptoms above to route them to the proper clinical department. The appointment will be confirmed immediately.
                    </p>
                </div>
            </div>
            <div class="clean-modal-footer">
                <button type="button" class="btn-clean outline" onclick="closeTriageAssignModal()">Cancel</button>
                <button type="button" class="btn-clean primary" id="btnConfirmTriageApprove" onclick="confirmTriageAndApprove()">
                    Assign &amp; Approve
                </button>
            </div>
        </div>
    </div>

    <div id="cleanToast" class="toast-clean"></div>

    <script>
        window.STAFF_DEPT = <?= json_encode($deptLabel) ?>;
        window.STAFF_USER_ID = <?= json_encode($userId) ?>;
    </script>
    <script src="/sdn/public/js/staff-dashboard-clean.js?v=<?= time() ?>"></script>
</body>
</html>
