<?php
session_start();
if (empty($_SESSION['user'])) {
    header('Location: /pages/login.php');
    exit;
}

// Fetch user data dynamically
require_once __DIR__ . '/../api/config/db.php';
$stmtUser = $pdo->prepare("SELECT department, full_name, role FROM users WHERE username = ?");
$stmtUser->execute([$_SESSION['user']]);
$userData = $stmtUser->fetch();
$staffDept = $userData['department'] ?? $_SESSION['department'] ?? '';
$fullName = $userData['full_name'] ?? $_SESSION['full_name'] ?? $_SESSION['user'];
$staffRole = $userData['role'] ?? $_SESSION['role'] ?? 'Staff';

// Prevent cache
header('Cache-Control: no-cache, no-store, must-revalidate, private');
header('Pragma: no-cache');
header('Expires: 0');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Staff Portal | SDN</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/public/css/global.css?v=<?= time() ?>" />
  <link rel="stylesheet" href="/public/css/notifier.css?v=<?= time() ?>" />
  <link rel="stylesheet" href="/public/css/staff-dashboard.css?v=<?= time() ?>" />
</head>
<body class="staff-layout">

  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <svg class="sidebar-logo-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"></path>
        <path d="M12 5 9.04 7.96a2.1 2.1 0 0 0 0 2.97l.03.03c.82.81 2.13.81 2.94 0L15 8"></path>
      </svg>
      <h2 class="sidebar-title">Medical<br>Staff Portal</h2>
    </div>

    <!-- User Profile Badge -->
    <div style="padding: 15px 25px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); display: flex; align-items: center; gap: 10px;">
      <div style="width: 35px; height: 35px; border-radius: 50%; background: rgba(255, 255, 255, 0.2); color: #ffffff; display: flex; align-items: center; justify-content: center; font-weight: bold;">
        <?= strtoupper(substr($_SESSION['full_name'] ?? $_SESSION['user'], 0, 1)) ?>
      </div>
      <div>
        <?php if(!empty($staffDept)): ?>
          <p style="margin: 0; font-size: 0.7rem; color: rgba(255, 255, 255, 0.85); text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px; margin-bottom: 2px;"><?= htmlspecialchars($staffDept) ?></p>
        <?php endif; ?>
        <p style="margin: 0; font-weight: 600; font-size: 0.9rem; color: #ffffff;"><?= htmlspecialchars($fullName) ?></p>
        <p style="margin: 0; font-size: 0.75rem; color: rgba(255, 255, 255, 0.7); text-transform: capitalize;"><?= htmlspecialchars($staffRole) ?></p>
      </div>
    </div>

    <nav class="sidebar-nav">
      <a class="nav-link active-nav" data-target="dashboardView">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
        Overview
      </a>
      <a class="nav-link" data-target="patientQueueView">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
        Patient Queue
      </a>
      <a class="nav-link" data-target="leaveView">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
        Availability & Leave
      </a>
      <a class="nav-link" data-target="reportsView">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
        Reports
      </a>
      <a class="nav-link" data-target="historyView">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"></path></svg>
        Patient History
      </a>
    </nav>

    <div class="sidebar-footer">
      <button class="btn-sidebar-logout" onclick="window.location.href='/pages/logout.php'">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
        Logout
      </button>
    </div>
  </aside>

  <!-- Main Content Area -->
  <main class="main-content">

    <!-- 1. Dashboard View (Overview & Hub) -->
    <section id="dashboardView" class="view-section active">

      <div class="page-header">
        <div>
          <h2 class="page-title">Overview & Scheduling Hub</h2>
          <p class="page-subtitle">Your personal schedule and department-wide shifts at a glance.</p>
        </div>
      </div>

        <div class="calendar-toolbar" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
          <div class="dept-filters">
            <?php if(empty($staffDept)): ?>
              <button class="filter-btn active" onclick="filterHub('all', this)">All Departments</button>
              <button class="filter-btn" onclick="filterHub('psychiatry', this)">Psychiatry</button>
              <button class="filter-btn" onclick="filterHub('neurology', this)">Neurology</button>
              <button class="filter-btn" onclick="filterHub('general', this)">General</button>
            <?php else: ?>
              <button class="filter-btn active" onclick="filterHub('all', this)"><?= htmlspecialchars($staffDept) ?></button>
            <?php endif; ?>
          </div>
          <div class="date-filter" style="display: flex; align-items: center; gap: 10px;">
            <label style="font-size: 0.9rem; color: #666; font-weight: 500;">Start Date:</label>
            <input type="date" id="calendarStartDate" class="form-control" style="width: auto; padding: 5px 10px;">
            <label style="font-size: 0.9rem; color: #666; font-weight: 500;">End Date:</label>
            <input type="date" id="calendarEndDate" class="form-control" style="width: auto; padding: 5px 10px;">
            <button class="btn-action btn-primary" onclick="alert('Filter applied!')" style="padding: 6px 16px;">Apply</button>
            <button class="btn-action" onclick="document.getElementById('calendarStartDate').value=''; document.getElementById('calendarEndDate').value=''; alert('Filter reset!');" style="padding: 6px 16px; background-color: #dc2626; color: white; border: none;">Reset</button>
          </div>
        </div>

      <div class="card">
        <div class="calendar-grid full-month-grid" id="staff-calendar-grid">
          <div class="cal-header">Sun</div><div class="cal-header">Mon</div><div class="cal-header">Tue</div><div class="cal-header">Wed</div><div class="cal-header">Thu</div><div class="cal-header">Fri</div><div class="cal-header">Sat</div>
          <div style="grid-column: 1 / -1; text-align: center; padding: 20px;">Loading calendar...</div>
        </div>
      </div>
    </section>

    <!-- 2. Patient Queue View -->
    <section id="patientQueueView" class="view-section">
      <div class="page-header">
        <div>
          <h2 class="page-title">Patient Queue & Management</h2>
          <p class="page-subtitle">Manage upcoming appointments, statuses, and clinical notes.</p>
        </div>
      </div>

      <div class="card">
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>Time</th>
                <th>Patient Name</th>
                <th>Department</th>
                <th>Status</th>
                <th>Assigned Doctor</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="patient-queue-tbody">
              <tr><td colspan="5">Loading patients...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- 3. Leave Management View -->
    <section id="leaveView" class="view-section">
      <div class="page-header">
        <div>
          <h2 class="page-title">Availability & Leave Management</h2>
          <p class="page-subtitle">Submit time-off requests and manage your availability.</p>
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px;">
        <div class="card">
          <h3 style="margin-top:0;">Request Time Off</h3>
          <form id="leave-request-form">
            <div class="form-group">
              <label>Start Date</label>
              <input type="date" id="leaveStartDate" class="form-control" required />
            </div>
            <div class="form-group">
              <label>End Date</label>
              <input type="date" id="leaveEndDate" class="form-control" required />
            </div>
            <div class="form-group">
              <label>Reason</label>
              <textarea id="leaveReason" class="form-control" rows="3" placeholder="Brief reason..." required></textarea>
            </div>
            <button type="submit" class="btn-submit" style="width: 100%;">Submit Request</button>
          </form>
        </div>
        <div class="card">
          <h3 style="margin-top:0;">Past Requests</h3>
          <table class="data-table">
            <thead>
              <tr>
                <th>Date Range</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody id="staff-leave-tbody">
              <tr><td colspan="2">Loading requests...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- 5. Reports & Analytics -->
    <section id="reportsView" class="view-section">
      <div class="page-header">
        <div>
          <h2 class="page-title">Reports</h2>
          <p class="page-subtitle">Simple metrics for patient loads and shift coverage.</p>
        </div>
      </div>

      <div class="stat-grid">
        <div class="stat-card">
          <div class="stat-icon icon-blue"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg></div>
          <div class="stat-info">
            <h4>Total Patients Today</h4>
            <p id="report-total-patients-today">Loading...</p>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon icon-red"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path></svg></div>
          <div class="stat-info">
            <h4>No-Show Rate</h4>
            <p id="report-no-show-rate-staff">Loading...</p>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon icon-green"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect></svg></div>
          <div class="stat-info">
            <h4>Shift Coverage</h4>
            <p id="report-shift-coverage">Loading...</p>
          </div>
        </div>
      </div>

      <div class="card">
        <h3>Department Load</h3>
        <table class="data-table">
            <thead>
              <tr><th>Department</th><th>Active Patients</th><th>Staff On-Duty</th></tr>
            </thead>
            <tbody>
              <?php if(empty($staffDept) || strtolower($staffDept) === 'opu psychiatry'): ?>
                <tr><td>Psychiatry</td><td>45</td><td>2</td></tr>
              <?php endif; ?>
              <?php if(empty($staffDept) || strtolower($staffDept) === 'neurology'): ?>
                <tr><td>Neurology</td><td>32</td><td>1</td></tr>
              <?php endif; ?>
              <?php if(empty($staffDept) || strtolower($staffDept) === 'general management'): ?>
                <tr><td>General</td><td>120</td><td>4</td></tr>
              <?php endif; ?>
            </tbody>
        </table>
      </div>
    </section>

    <!-- Patient History View -->
    <section id="historyView" class="view-section">
      <div class="page-header">
        <div>
          <h2 class="page-title">Patient Medical History</h2>
          <p class="page-subtitle">View and manage medical history records for all patients.</p>
        </div>
      </div>
      <div class="card" style="margin-top: 30px;">
        <div class="card-header" style="justify-content: space-between; display: flex; align-items: center;">
          <h3 style="display:flex; align-items:center; gap:8px; margin:0; font-size:0.9rem; color:#666; text-transform:uppercase; letter-spacing:1px;">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"></path></svg>
            MEDICAL HISTORY RECORDS
          </h3>
        </div>
        <table style="margin-top: 10px; table-layout: fixed; width: 100%;">
          <thead>
            <tr>
              <th style="width: 10%; white-space: nowrap;">Date</th>
              <th style="width: 15%; white-space: nowrap;">Patient</th>
              <th style="width: 18%; white-space: nowrap;">Diagnosis</th>
              <th style="width: 18%; white-space: nowrap;">Treatment</th>
              <th style="width: 12%; white-space: nowrap;">Prescription</th>
              <th style="width: 10%; white-space: nowrap;">Recorded By</th>
              <th style="width: 17%; white-space: nowrap;">Actions</th>
            </tr>
          </thead>
          <tbody id="history-records-tbody">
            <tr><td colspan="7" style="text-align: center; padding: 40px; color: #666;">
              <div style="font-size: 2rem; color: #10b981; margin-bottom: 10px;">+</div>
              <strong style="display:block; color:#333; margin-bottom:5px;">No history records</strong>
              Add the first medical history record above.
            </td></tr>
          </tbody>
        </table>
      </div>
    </section>

  </main>

  <script src="/public/js/staff-dashboard-inline.js"></script>
  <script src="/public/js/staff-spa-v2.js?v=9?v=<?= time() ?>"></script>
  <script src="/public/js/notifier.js?v=<?= time() ?>"></script>
  <!-- Assign Doctor Modal -->
  <div id="assignModal" class="modal-box" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: #fff; padding: 30px; border-radius: 16px; width: 400px; max-width: 90%;">
      <h3 style="margin-top: 0; color: #1b5e20;">Assign Doctor</h3>
      <input type="hidden" id="assignPatientId">
      <div class="form-group">
        <label>Assign To Doctor (ID or Name)</label>
        <select id="assignDoctor" class="form-control">
          <option value="">Loading doctors...</option>
        </select>
      </div>
      <div style="display: flex; gap: 10px; margin-top: 20px;">
        <button class="btn-action btn-primary" onclick="submitAssign()">Assign</button>
        <button class="btn-action btn-outline" onclick="document.getElementById('assignModal').style.display='none'">Cancel</button>
      </div>
    </div>
  </div>

  <!-- Add/Edit Medical History Modal -->
  <div id="historyModal" class="modal-box" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: 20px;">
    <div style="background: #fff; padding: 30px; border-radius: 16px; width: 500px; max-width: 100%; max-height: 90vh; overflow-y: auto;">
      <h3 style="margin-top: 0; color: #1b5e20; text-align: center;" id="historyModalTitle">Add Medical History</h3>
      <input type="hidden" id="historyPatientId">
      <div class="form-group">
        <label>Diagnosis</label>
        <input type="text" id="historyDiagnosis" class="form-control" placeholder="Enter diagnosis">
      </div>
      <div class="form-group" style="margin-top: 15px;">
        <label>Treatment</label>
        <textarea id="historyTreatment" class="form-control" rows="3" placeholder="Describe the treatment plan" style="resize:vertical;"></textarea>
      </div>
      <div class="form-group" style="margin-top: 15px;">
        <label>Prescription (Optional)</label>
        <textarea id="historyPrescription" class="form-control" rows="2" placeholder="Medication, dosage, frequency..." style="resize:vertical;"></textarea>
      </div>
      <div style="display: flex; gap: 10px; margin-top: 20px; justify-content: flex-end;">
        <button class="btn-action btn-outline" onclick="document.getElementById('historyModal').style.display='none'" style="padding: 8px 20px;">Cancel</button>
        <button class="btn-action btn-primary" onclick="submitHistory()" style="padding: 8px 20px;">Save Record</button>
      </div>
    </div>
  </div>

  <!-- View Medical History Modal -->
  <div id="viewHistoryModal" class="modal-box" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: 20px;">
    <div style="background: #fff; padding: 30px; border-radius: 16px; width: 600px; max-width: 100%; max-height: 90vh; overflow-y: auto;">
      <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
        <h3 style="margin-top: 0; color: #1b5e20;">Patient Medical History</h3>
        <button onclick="document.getElementById('viewHistoryModal').style.display='none'" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #666; margin-top: -5px; padding: 0;">&times;</button>
      </div>
      
      <div id="printHistoryContent" style="padding: 20px; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 20px; font-family: sans-serif;">
        <div style="text-align: center; margin-bottom: 20px; border-bottom: 1px solid #e5e7eb; padding-bottom: 15px;">
          <h2 style="margin: 0; color: #111;">SDN Hospital</h2>
          <p style="margin: 5px 0 0; color: #666;">Medical History Report</p>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
          <div>
            <span style="color: #666; font-size: 0.85rem;">Patient Name</span>
            <div id="viewHistoryName" style="font-weight: 600; font-size: 1.1rem; color: #111;"></div>
          </div>
          <div>
            <span style="color: #666; font-size: 0.85rem;">Patient ID</span>
            <div id="viewHistoryId" style="font-weight: 600; color: #111;"></div>
          </div>
          <div>
            <span style="color: #666; font-size: 0.85rem;">Record Date</span>
            <div id="viewHistoryDate" style="font-weight: 600; color: #111;"></div>
          </div>
          <div>
            <span style="color: #666; font-size: 0.85rem;">Attending / Recorded By</span>
            <div id="viewHistoryRecordedBy" style="font-weight: 600; color: #111;"></div>
          </div>
        </div>
        
        <div style="margin-bottom: 15px;">
          <span style="color: #666; font-size: 0.85rem; font-weight: 600; text-transform: uppercase;">Diagnosis</span>
          <div id="viewHistoryDiagnosis" style="background: #f9fafb; padding: 12px; border-radius: 6px; margin-top: 5px; color: #333; line-height: 1.5; word-wrap: break-word;"></div>
        </div>
        
        <div style="margin-bottom: 15px;">
          <span style="color: #666; font-size: 0.85rem; font-weight: 600; text-transform: uppercase;">Treatment Plan</span>
          <div id="viewHistoryTreatment" style="background: #f9fafb; padding: 12px; border-radius: 6px; margin-top: 5px; color: #333; line-height: 1.5; white-space: pre-wrap; word-wrap: break-word;"></div>
        </div>
        
        <div style="margin-bottom: 10px;">
          <span style="color: #666; font-size: 0.85rem; font-weight: 600; text-transform: uppercase;">Prescription</span>
          <div id="viewHistoryPrescription" style="background: #f9fafb; padding: 12px; border-radius: 6px; margin-top: 5px; color: #333; line-height: 1.5; white-space: pre-wrap; word-wrap: break-word;"></div>
        </div>
      </div>

      <div style="display: flex; gap: 10px; justify-content: flex-end;">
         <button class="btn-action btn-outline" onclick="printPatientHistory()" style="display:flex; align-items:center; gap:5px;">
           <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
           Print
         </button>
         <button class="btn-action btn-outline" onclick="downloadPatientHistoryPDF()" style="display:flex; align-items:center; gap:5px;">
           <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
           Download PDF
         </button>
      </div>
    </div>
  </div>
  
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</body>
</html>
