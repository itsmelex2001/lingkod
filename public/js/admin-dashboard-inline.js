// admin-dashboard-inline.js
document.addEventListener('DOMContentLoaded', () => {
  const navLinks = document.querySelectorAll('.sidebar-nav a[data-target]');
  const sections = document.querySelectorAll('.view-section');

  navLinks.forEach(link => {
    link.addEventListener('click', () => {
      // Remove active from all links
      navLinks.forEach(l => l.classList.remove('active-nav'));
      // Add active to clicked link
      link.classList.add('active-nav');

      // Hide all sections
      sections.forEach(sec => sec.classList.remove('active'));
      // Show target section
      const targetId = link.getAttribute('data-target');
      document.getElementById(targetId).classList.add('active');

      // Dynamically refresh data for the clicked tab
      switch (targetId) {
        case 'overviewView': if (typeof fetchAdminStats === 'function') fetchAdminStats(); break;
        case 'staffView': if (typeof fetchStaffDirectory === 'function') fetchStaffDirectory(); break;
        case 'shiftView': if (typeof fetchAdminSchedule === 'function') fetchAdminSchedule(); break;
        case 'leaveView': if (typeof fetchAdminLeave === 'function') fetchAdminLeave(); break;
        case 'swapsView': if (typeof fetchAdminSwaps === 'function') fetchAdminSwaps(); break;
        case 'appointmentsView': if (typeof fetchAdminAppointments === 'function') fetchAdminAppointments(); break;
        case 'historyView': if (typeof fetchPatientHistory === 'function') fetchPatientHistory(); break;
        case 'reportsView': if (typeof fetchReports === 'function') fetchReports(); break;
        case 'settingsView': if (typeof fetchSettings === 'function') fetchSettings(); break;
        case 'auditView': if (typeof fetchAuditLogs === 'function') fetchAuditLogs(); break;
      }
    });
  });
});

function filterMasterCalendar() {
  const start = document.getElementById('calendarStartDate').value;
  const end = document.getElementById('calendarEndDate').value;
  const rows = document.querySelectorAll('#overview-calendar-tbody tr');

  if (!start && !end) {
    alert('Please select at least one date.');
    return;
  }

  const startDate = start ? new Date(start) : new Date('1970-01-01');
  const endDate = end ? new Date(end) : new Date('2099-12-31');

  rows.forEach(row => {
    const dateCell = row.cells[0].textContent.trim();
    if (dateCell) {
      const rowDate = new Date(dateCell);
      if (rowDate >= startDate && rowDate <= endDate) {
        row.style.display = '';
      } else {
        row.style.display = 'none';
      }
    }
  });
}

function resetMasterCalendar() {
  document.getElementById('calendarStartDate').value = '';
  document.getElementById('calendarEndDate').value = '';
  const rows = document.querySelectorAll('#overview-calendar-tbody tr');
  rows.forEach(row => row.style.display = '');
}

function filterReports() {
  const start = document.getElementById('reportStartDate').value;
  const end = document.getElementById('reportEndDate').value;
  const rows = document.querySelectorAll('#report-details-tbody tr');

  if (!start && !end) {
    alert('Please select at least one date.');
    return;
  }

  const startDate = start ? new Date(start) : new Date('1970-01-01');
  const endDate = end ? new Date(end) : new Date('2099-12-31');

  rows.forEach(row => {
    if(row.cells.length > 1) {
      const dateCell = row.cells[0].textContent.trim();
      if (dateCell) {
        const rowDate = new Date(dateCell);
        if (rowDate >= startDate && rowDate <= endDate) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
      }
    }
  });
}

function resetReports() {
  document.getElementById('reportStartDate').value = '';
  document.getElementById('reportEndDate').value = '';
  const rows = document.querySelectorAll('#report-details-tbody tr');
  rows.forEach(row => row.style.display = '');
}
