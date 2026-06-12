// staff-dashboard-inline.js
// Navigation Logic
const navLinks = document.querySelectorAll('.nav-link');
const sections = document.querySelectorAll('.view-section');

navLinks.forEach(link => {
  link.addEventListener('click', () => {
    // Remove active class from all
    navLinks.forEach(l => l.classList.remove('active-nav'));
    sections.forEach(s => s.classList.remove('active'));

    // Add active class to clicked link and corresponding section
    link.classList.add('active-nav');
    const targetId = link.getAttribute('data-target');
    document.getElementById(targetId).classList.add('active');

    // Dynamically refresh data for the clicked tab
    switch (targetId) {
      case 'dashboardView': if (typeof fetchCalendar === 'function') fetchCalendar(); break;
      case 'patientQueueView': if (typeof fetchPatientQueue === 'function') fetchPatientQueue(); break;
      case 'leaveView': if (typeof fetchStaffLeave === 'function') fetchStaffLeave(); break;
      case 'directoryView': if (typeof fetchStaffDirectory === 'function') fetchStaffDirectory(); break;
      case 'reportsView': if (typeof fetchReports === 'function') fetchReports(); break;
      case 'historyView': if (typeof fetchPatientHistory === 'function') fetchPatientHistory(); break;
    }
  });
});

// Toolbar Filter Logic
const filterBtns = document.querySelectorAll('.filter-btn');
filterBtns.forEach(btn => {
  btn.addEventListener('click', () => {
    filterBtns.forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
  });
});
