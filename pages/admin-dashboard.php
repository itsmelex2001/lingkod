<?php
session_start();
if (empty($_SESSION['user']) || strtolower(trim($_SESSION['role'] ?? '')) !== 'admin') {
    header('Location: /sdn/pages/login.php');
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Portal | Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/sdn/public/css/global.css?v=<?= time() ?>" />
  <link rel="stylesheet" href="/sdn/public/css/notifier.css?v=<?= time() ?>" />
  <link rel="stylesheet" href="/sdn/public/css/admin-dashboard.css?v=<?= time() ?>" />
</head>
<body class="admin-layout">

  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-header">
      <svg class="sidebar-logo-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
      <h2 class="sidebar-title">Admin<br>Portal</h2>
    </div>

    <!-- User Profile Badge -->
    <div style="padding: 15px 25px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); display: flex; align-items: center; gap: 10px;">
      <div style="width: 35px; height: 35px; border-radius: 50%; background: rgba(255, 255, 255, 0.2); color: #ffffff; display: flex; align-items: center; justify-content: center; font-weight: bold;">
        A
      </div>
      <div>
        <p style="margin: 0; font-weight: 600; font-size: 0.9rem; color: #ffffff;">Admin</p>
        <p style="margin: 0; font-size: 0.75rem; color: rgba(255, 255, 255, 0.7); text-transform: capitalize;">Administrator</p>
      </div>
    </div>
    
    <nav class="sidebar-nav">
      <a data-target="overviewView" class="active-nav">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
        Admin
      </a>
      <a data-target="userStaffView">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
        User
      </a>
      <a data-target="deptView">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
        Departments
      </a>
      <a data-target="scheduleView">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
        Shift Admin
      </a>
      <a data-target="leaveView">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"></path><polyline points="12 6 12 12 16 14"></polyline></svg>
        Leave & Avail
      </a>
      <a data-target="appointmentsView">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
        Appointments
      </a>
      <a data-target="historyView">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"></path></svg>
        Patient History
      </a>
      <a data-target="reportsView">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
        Reports
      </a>
      <a data-target="auditView">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12h4l2-9 5 18 3-9h6"></path></svg>
        Audit Logs
      </a>
    </nav>
    
    <div class="sidebar-footer">
      <button class="btn-sidebar-logout" onclick="window.location.href='/sdn/pages/logout.php'">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
        Logout
      </button>
    </div>
  </aside>

  <!-- Main Content Area -->
  <main class="main-content">

    <!-- 0. Admin Overview -->
    <section id="overviewView" class="view-section active">
      <div class="page-header">
        <div>
          <h2 class="page-title">Admin Overview</h2>
          <p class="page-subtitle">Welcome back, Admin. Here is your hospital summary for today.</p>
        </div>
      </div>

      <div class="grid-3">
        <div class="stat-widget">
          <div class="stat-icon stat-blue">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
          </div>
          <div class="stat-info">
            <h4>Active Staff</h4>
            <p id="stat-active-staff">0</p>
          </div>
        </div>
        <div class="stat-widget">
          <div class="stat-icon stat-green">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
          </div>
          <div class="stat-info">
            <h4>Today's Patients</h4>
            <p id="stat-todays-patients">0</p>
          </div>
        </div>
        <div class="stat-widget">
          <div class="stat-icon stat-red">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
          </div>
          <div class="stat-info">
            <h4>Pending Actions</h4>
            <p id="stat-pending-actions">0</p>
          </div>
        </div>
      </div>

      <div class="card" style="margin-top: 24px;">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
          <h3 style="margin: 0;">Hospital Master Calendar</h3>
          <div style="display: flex; gap: 10px; align-items: center;">
            <label style="font-size: 0.9rem; color: #666; font-weight: 500;">Start Date:</label>
            <input type="date" id="calendarStartDate" class="form-control" style="width: auto; padding: 6px 12px;">
            <label style="font-size: 0.9rem; color: #666; font-weight: 500;">End Date:</label>
            <input type="date" id="calendarEndDate" class="form-control" style="width: auto; padding: 6px 12px;">
            <button class="btn-action btn-primary" onclick="filterMasterCalendar()" style="padding: 6px 16px;">Apply</button>
            <button class="btn-action" onclick="resetMasterCalendar()" style="padding: 6px 16px; background-color: #dc2626; color: white; border: none;">Reset</button>
          </div>
        </div>
        <div style="margin-top: 20px;">
          <table style="width: 100%; border-collapse: collapse;">
            <thead>
              <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                <th style="padding: 12px; text-align: left; font-weight: 600; color: #374151;">Date</th>
                <th style="padding: 12px; text-align: left; font-weight: 600; color: #374151;">Time</th>
                <th style="padding: 12px; text-align: left; font-weight: 600; color: #374151;">Department</th>
                <th style="padding: 12px; text-align: left; font-weight: 600; color: #374151;">Doctor Availability</th>
                <th style="padding: 12px; text-align: left; font-weight: 600; color: #374151;">Patient Appointments</th>
              </tr>
            </thead>
            <tbody id="overview-calendar-tbody">
              <tr style="border-bottom: 1px solid #e2e8f0;">
                <td style="padding: 12px;"><?= date('Y-m-d') ?></td>
                <td style="padding: 12px;">08:00 AM</td>
                <td style="padding: 12px;">OPU Psychiatry</td>
                <td style="padding: 12px;"><span style="color: #059669; font-weight: 500;">2 Doctors Available</span></td>
                <td style="padding: 12px;">4 Patients</td>
              </tr>
              <tr style="border-bottom: 1px solid #e2e8f0;">
                <td style="padding: 12px;"><?= date('Y-m-d') ?></td>
                <td style="padding: 12px;">09:00 AM</td>
                <td style="padding: 12px;">Neurology</td>
                <td style="padding: 12px;"><span style="color: #ea580c; font-weight: 500;">1 Doctor (Busy)</span></td>
                <td style="padding: 12px;">2 Patients</td>
              </tr>
              <tr style="border-bottom: 1px solid #e2e8f0;">
                <td style="padding: 12px;"><?= date('Y-m-d') ?></td>
                <td style="padding: 12px;">10:00 AM</td>
                <td style="padding: 12px;">General Management</td>
                <td style="padding: 12px;"><span style="color: #059669; font-weight: 500;">5 Doctors Available</span></td>
                <td style="padding: 12px;">12 Patients</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- 1. User & Staff Management -->
    <section id="userStaffView" class="view-section">
      <div class="page-header">
        <div>
          <h2 class="page-title">User & Staff Management</h2>
          <p class="page-subtitle">Create, edit, and deactivate staff accounts and assign roles.</p>
        </div>
        <button class="btn-action btn-primary" onclick="document.getElementById('addStaffModal').style.display='flex'">+ Add New User</button>
      </div>

      <div class="card">
        <div class="card-header">
          <h3>Active Personnel Directory</h3>
          <input type="text" class="form-control" placeholder="Search staff..." style="width: 250px;" onkeyup="filterStaffDirectory(this.value)">
        </div>
        <table>
          <thead>
            <tr>
              <th>Name</th>
              <th>Role</th>
              <th>Department</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="staff-directory-tbody">
            <tr><td colspan="5">Loading staff...</td></tr>
          </tbody>
        </table>
      </div>
    </section>

    <!-- 2. Department Management -->
    <section id="deptView" class="view-section">
      <div class="page-header">
        <div>
          <h2 class="page-title">Department Management</h2>
          <p class="page-subtitle">Configure operating hours, limits, and staff assignments per department.</p>
        </div>
      </div>
      
      <div class="grid-3">
        <div class="card dept-card dept-psych">
          <h3>OPU Psychiatry</h3>
          <p class="page-subtitle" style="margin-top: 10px;">Operating Hours: 08:00 AM - 05:00 PM</p>
          <p class="page-subtitle">Capacity: 45 Patients / Day</p>
          <div style="margin-top: 15px;">
            <button class="btn-action btn-outline" style="width: 100%; justify-content: center;" onclick="configureRules('Cardiology')">Configure Rules</button>
          </div>
        </div>
        <div class="card dept-card dept-neuro">
          <h3>Neurology</h3>
          <p class="page-subtitle" style="margin-top: 10px;">Operating Hours: 09:00 AM - 06:00 PM</p>
          <p class="page-subtitle">Capacity: 30 Patients / Day</p>
          <div style="margin-top: 15px;">
            <button class="btn-action btn-outline" style="width: 100%; justify-content: center;" onclick="configureRules('Cardiology')">Configure Rules</button>
          </div>
        </div>
        <div class="card dept-card dept-gen">
          <h3>General Management</h3>
          <p class="page-subtitle" style="margin-top: 10px;">Operating Hours: 24/7</p>
          <p class="page-subtitle">Capacity: Unlimited</p>
          <div style="margin-top: 15px;">
            <button class="btn-action btn-outline" style="width: 100%; justify-content: center;" onclick="configureRules('Cardiology')">Configure Rules</button>
          </div>
        </div>
      </div>
    </section>

    <!-- 3. Schedule & Shift Admin -->
    <section id="scheduleView" class="view-section">
      <div class="page-header">
        <div>
          <h2 class="page-title">Master Schedule & Shift Admin</h2>
          <p class="page-subtitle">The master view of all shifts across all departments.</p>
        </div>
        <button class="btn-action btn-primary" onclick="openScheduleModal()">+ Create Schedule</button>
      </div>

      <div class="card">
        <div class="card-header">
          <h3>Upcoming Shifts</h3>
          <div style="display:flex; gap: 10px;">
            <button class="btn-action btn-outline" onclick="filterSchedule('this_week')">This Week</button>
            <button class="btn-action btn-outline" onclick="filterSchedule('next_week')">Next Week</button>
            <button class="btn-action btn-outline" onclick="filterSchedule('all')">All</button>
          </div>
        </div>
        <table>
          <thead>
            <tr>
              <th>Date</th>
              <th>Staff Name</th>
              <th>Department</th>
              <th>Shift Time</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="admin-schedule-tbody">
            <tr><td colspan="5">Loading schedule...</td></tr>
          </tbody>
        </table>
      </div>
    </section>

    <!-- 4. Leave & Availability -->
    <section id="leaveView" class="view-section">
      <div class="page-header">
        <div>
          <h2 class="page-title">Leave & Availability Approvals</h2>
          <p class="page-subtitle">Review coverage impact and approve/reject staff leave requests.</p>
        </div>
      </div>

      <div class="card">
        <table>
          <thead>
            <tr>
              <th>Staff Name</th>
              <th>Type</th>
              <th>Dates requested</th>
              <th>Reason</th>
              <th>Impact</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="admin-leave-tbody">
            <tr><td colspan="6">Loading leave requests...</td></tr>
          </tbody>
        </table>
      </div>
    </section>



    <!-- 6. Appointment Oversight -->
    <section id="appointmentsView" class="view-section">
      <div class="page-header">
        <div>
          <h2 class="page-title">Global Appointment Oversight</h2>
          <p class="page-subtitle">View all patient queues. Reassign or cancel if a doctor is unavailable.</p>
        </div>
      </div>
      <div class="card">
        <table>
          <thead>
            <tr>
              <th>Patient ID</th>
              <th>Name</th>
              <th>Department</th>
              <th>Assigned Doctor</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="admin-appointments-tbody">
            <tr><td colspan="6">Loading appointments...</td></tr>
          </tbody>
        </table>
      </div>
    </section>

    <!-- 7. Reports -->
    <section id="reportsView" class="view-section">
      <div class="page-header">
        <div>
          <h2 class="page-title">Reports</h2>
          <p class="page-subtitle">Department utilization and workload distribution reports.</p>
        </div>
        <button class="btn-action btn-outline">Export PDF</button>
      </div>

      <div class="grid-3">
        <div class="stat-widget">
          <div class="stat-icon stat-blue">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
          </div>
          <div class="stat-info">
            <h4>Total Consults</h4>
            <p id="report-total-consults">Loading...</p>
          </div>
        </div>
        <div class="stat-widget">
          <div class="stat-icon stat-green">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
          </div>
          <div class="stat-info">
            <h4>Attendance Rate</h4>
            <p id="report-attendance-rate">Loading...</p>
          </div>
        </div>
        <div class="stat-widget">
          <div class="stat-icon stat-red">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
          </div>
          <div class="stat-info">
            <h4>No-Show Rate</h4>
            <p id="report-no-show-rate">Loading...</p>
          </div>
        </div>
      </div>

      <div class="card" style="margin-top: 30px;">
        <div class="card-header">
          <h3>Detailed Reports</h3>
          <button class="btn-action btn-outline">Filter: Last 30 Days</button>
        </div>
        <table style="margin-top: 15px;">
          <thead>
            <tr>
              <th>Date</th>
              <th>Department</th>
              <th>Patients Handled</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody id="report-details-tbody">
            <tr><td colspan="4" style="text-align: center; padding: 20px; color: #666;">No report data generated yet.</td></tr>
          </tbody>
        </table>
      </div>
    </section>



    <!-- 9. Audit Logs -->
    <section id="auditView" class="view-section">
      <div class="page-header">
        <div>
          <h2 class="page-title">System Audit Logs</h2>
          <p class="page-subtitle">Full history of who changed what and when.</p>
        </div>
      </div>
      <div class="card">
        <table>
          <thead>
            <tr>
              <th>Timestamp</th>
              <th>Action</th>
              <th>User</th>
              <th>Details</th>
            </tr>
          </thead>
          <tbody id="audit-logs-tbody">
            <tr><td colspan="4">Loading logs...</td></tr>
          </tbody>
        </table>
      </div>
    </section>

    <!-- 10. Patient History -->
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

  <script src="/sdn/public/js/admin-dashboard-inline.js?v=<?= time() ?>"></script>
  <script src="/sdn/public/js/admin-spa-v2.js?v=<?= time() ?>"></script>
  <script src="/sdn/public/js/notifier.js?v=<?= time() ?>"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

  <!-- Add User Modal -->
  <div id="addStaffModal" class="modal-box" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: #fff; padding: 40px; border-radius: 16px; width: 700px; max-width: 95%;">
      <h3 style="margin: 0 0 25px 0; color: var(--deep-green); font-size: 1.4rem;">Add New User</h3>
      
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px 25px;">
        <div class="form-group">
          <label>Full Name</label>
          <input type="text" id="newStaffFullName" class="form-control" placeholder="Enter your Full name">
        </div>
        <div class="form-group">
          <label>Username</label>
          <input type="text" id="newStaffUsername" class="form-control" placeholder="Enter username">
        </div>
        
        <div class="form-group">
          <label>Mobile Number (For Recovery)</label>
          <input type="text" id="newStaffMobile" class="form-control" placeholder="Enter mobile number">
        </div>
        <div class="form-group">
          <label>Role</label>
          <select id="newStaffRole" class="form-control">
            <option value="staff">Medical Staff</option>
            <option value="admin">Administrator (Co-Admin)</option>
          </select>
        </div>
        
        <div class="form-group" style="grid-column: 1 / -1;">
          <label>Department</label>
          <select id="newStaffDept" class="form-control">
            <option value="General Management">General Management</option>
            <option value="Neurology">Neurology</option>
            <option value="OPU Psychiatry">OPU Psychiatry</option>
          </select>
        </div>

        <div class="form-group" style="position: relative;">
          <label>Password</label>
          <input type="password" id="newStaffPassword" class="form-control" placeholder="Enter password" style="padding-right: 35px;">
          <span onclick="const input = document.getElementById('newStaffPassword'); input.type = input.type === 'password' ? 'text' : 'password';" style="position: absolute; right: 10px; top: 34px; cursor: pointer; color: #666; font-size: 1.1rem;" title="Toggle Password Visibility">
            👁️
          </span>
        </div>
        <div class="form-group" style="position: relative;">
          <label>Confirm Password</label>
          <input type="password" id="newStaffConfirmPassword" class="form-control" placeholder="Confirm password" style="padding-right: 35px;">
          <span onclick="const input = document.getElementById('newStaffConfirmPassword'); input.type = input.type === 'password' ? 'text' : 'password';" style="position: absolute; right: 10px; top: 34px; cursor: pointer; color: #666; font-size: 1.1rem;" title="Toggle Password Visibility">
            👁️
          </span>
        </div>
      </div>

      <div style="display: flex; gap: 12px; margin-top: 30px;">
        <button class="btn-action btn-primary" onclick="submitAddStaff()" style="padding: 10px 30px;">Save</button>
        <button class="btn-action btn-outline" onclick="document.getElementById('addStaffModal').style.display='none'" style="padding: 10px 30px;">Cancel</button>
      </div>
    </div>
  </div>

  <!-- Create/Edit Schedule Modal -->
  <div id="scheduleModal" class="modal-box" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: #fff; padding: 30px; border-radius: 16px; width: 400px; max-width: 90%;">
      <h3 style="margin-top: 0; color: #1b5e20;" id="scheduleModalTitle">Edit Schedule</h3>
      <input type="hidden" id="scheduleDocId">
      <div class="form-group" id="scheduleDoctorSelectGroup" style="display: none;">
        <label>Select Doctor</label>
        <select id="scheduleDoctorSelect" class="form-control"></select>
      </div>
      <div class="form-group">
        <label>Schedule Date</label>
        <input type="date" id="scheduleDate" class="form-control">
      </div>
      <div style="display: flex; gap: 15px;">
        <div class="form-group" style="flex: 1;">
          <label>Start Time</label>
          <input type="time" id="scheduleStartTime" class="form-control">
        </div>
        <div class="form-group" style="flex: 1;">
          <label>End Time</label>
          <input type="time" id="scheduleEndTime" class="form-control">
        </div>
      </div>
      <div style="display: flex; gap: 10px; margin-top: 20px;">
        <button class="btn-action btn-primary" onclick="submitSchedule()">Save Schedule</button>
        <button class="btn-action btn-outline" onclick="document.getElementById('scheduleModal').style.display='none'">Cancel</button>
      </div>
    </div>
  </div>

  <!-- Reassign Patient Modal -->
  <div id="reassignModal" class="modal-box" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: #fff; padding: 30px; border-radius: 16px; width: 400px; max-width: 90%;">
      <h3 style="margin-top: 0; color: #1b5e20;">Reassign Patient</h3>
      <input type="hidden" id="reassignPatientId">
      <div class="form-group">
        <label>Assign To Doctor (ID or Name)</label>
        <select id="reassignDoctor" class="form-control">
          <option value="">Loading doctors...</option>
        </select>
      </div>
      <div style="display: flex; gap: 10px; margin-top: 20px;">
        <button class="btn-action btn-primary" onclick="submitReassign()">Reassign</button>
        <button class="btn-action btn-outline" onclick="document.getElementById('reassignModal').style.display='none'">Cancel</button>
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
</body>
</html>
