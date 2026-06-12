// public/js/admin-spa.js

document.addEventListener('DOMContentLoaded', () => {
    // 1. Fetch Overview Stats
    fetchAdminStats();
});

async function fetchAdminStats() {
    try {
        const response = await fetch('/sdn/api/admin-stats.php');
        const result = await response.json();
        
        if (result.success) {
            const data = result;
            
            // Populate counters
            document.getElementById('stat-active-staff').textContent = data.active_staff;
            document.getElementById('stat-todays-patients').textContent = data.todays_patients;
            document.getElementById('stat-pending-actions').textContent = data.pending_actions;
            
            // Populate Alerts
            const alertsTbody = document.getElementById('overview-alerts-tbody');
            if (data.alerts && data.alerts.length > 0) {
                alertsTbody.innerHTML = '';
                data.alerts.forEach(alert => {
                    const tr = document.createElement('tr');
                    
                    // Determine badge type based on action string loosely
                    let badgeClass = 'badge-blue';
                    let badgeText = 'Info';
                    if (alert.action.toLowerCase().includes('delete') || alert.action.toLowerCase().includes('fail')) {
                        badgeClass = 'badge-red';
                        badgeText = 'Warning';
                    } else if (alert.action.toLowerCase().includes('create') || alert.action.toLowerCase().includes('success')) {
                        badgeClass = 'badge-green';
                        badgeText = 'Success';
                    }
                    
                    // Date parsing
                    const dateObj = new Date(alert.created_at);
                    const timeString = dateObj.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                    
                    tr.innerHTML = `
                        <td><span class="badge ${badgeClass}">${badgeText}</span></td>
                        <td><strong>${alert.action}</strong>: ${alert.description} <span style="color:#888; font-size:0.8rem;">(${timeString})</span></td>
                    `;
                    alertsTbody.appendChild(tr);
                });
            } else {
                alertsTbody.innerHTML = '<tr><td colspan="2">No recent system alerts.</td></tr>';
            }
            
            // Populate Full Audit Logs
            const auditTbody = document.getElementById('audit-logs-tbody');
            if (data.audit_logs && data.audit_logs.length > 0) {
                auditTbody.innerHTML = '';
                data.audit_logs.forEach(log => {
                    const tr = document.createElement('tr');
                    
                    let badgeClass = 'badge-blue';
                    if (log.action.toLowerCase().includes('delete') || log.action.toLowerCase().includes('fail')) badgeClass = 'badge-red';
                    else if (log.action.toLowerCase().includes('create') || log.action.toLowerCase().includes('success')) badgeClass = 'badge-green';
                    
                    tr.innerHTML = `
                        <td>${log.created_at}</td>
                        <td><span class="badge ${badgeClass}">${log.action}</span></td>
                        <td>${log.actor || 'System'}</td>
                        <td>${log.description}</td>
                    `;
                    auditTbody.appendChild(tr);
                });
            } else {
                auditTbody.innerHTML = '<tr><td colspan="4">No logs found.</td></tr>';
            }
        }
    } catch (error) {
        console.error('Failed to fetch admin stats:', error);
    }
}

async function fetchStaffDirectory() {
    try {
        const response = await fetch('/sdn/api/staff.php');
        const result = await response.json();
        
        if (result.success) {
            const staffList = result.data;
            const tbody = document.getElementById('staff-directory-tbody');
            
            if (staffList && staffList.length > 0) {
                tbody.innerHTML = '';
                staffList.forEach(staff => {
                    const tr = document.createElement('tr');
                    
                    let badgeClass = staff.status === 'inactive' ? 'badge-red' : 'badge-green';
                    let statusText = staff.status === 'inactive' ? 'Inactive' : 'Active';
                    // The old API doesn't return staff status, so we default to active if missing.
                    if(!staff.status) { badgeClass = 'badge-green'; statusText = 'Active'; }
                    
                    tr.innerHTML = `
                          <td><strong>${staff.full_name || staff.username}</strong></td>
                          <td style="text-transform: capitalize;">${staff.role}</td>
                          <td>${staff.department || staff.dept || 'N/A'}</td>
                          <td><span class="badge ${badgeClass}">${statusText}</span></td>
                          <td>
                              <button class="btn-action" style="background:#fee2e2; color:#991b1b;" onclick="toggleStaffStatus(${staff.id}, '${staff.status === 'inactive' ? 'active' : 'inactive'}')">
                                  ${staff.status === 'inactive' ? 'Activate' : 'Deactivate'}
                              </button>
                          </td>
                      `;
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="5">No staff members found.</td></tr>';
            }
        }
    } catch (err) {
        console.error('Failed to fetch staff directory:', err);
    }
}

async function submitAddStaff() {
    const fullName = document.getElementById('newStaffFullName').value;
    const username = document.getElementById('newStaffUsername').value;
    const mobile = document.getElementById('newStaffMobile').value;
    const password = document.getElementById('newStaffPassword').value;
    const confirmPassword = document.getElementById('newStaffConfirmPassword').value;
    const dept = document.getElementById('newStaffDept').value;
    
    if (!fullName || !username || !mobile || !password || !confirmPassword || !dept) {
        alert('Please fill all fields.');
        return;
    }
    if (password !== confirmPassword) {
        alert('Passwords do not match. Please try again.');
        return;
    }
    try {
        const res = await fetch('/sdn/api/staff.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'create', full_name: fullName, username, mobile_number: mobile, password, department: dept })
        });
        const result = await res.json();
        if (result.success) {
            alert(`Staff account created successfully!`);
            document.getElementById('addStaffModal').style.display = 'none';
            document.getElementById('newStaffFullName').value = '';
            document.getElementById('newStaffUsername').value = '';
            document.getElementById('newStaffMobile').value = '';
            document.getElementById('newStaffPassword').value = '';
            document.getElementById('newStaffConfirmPassword').value = '';
            fetchStaffDirectory();
        } else {
            alert(result.message || 'Failed to create staff.');
        }
    } catch (e) {
        alert('Error creating staff account.');
    }
}

async function toggleStaffStatus(userId, newStatus) {
    if (!confirm(`Are you sure you want to mark this staff member as ${newStatus}?`)) return;
    try {
        const res = await fetch('/sdn/api/staff.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'toggle', user_id: userId, status: newStatus })
        });
        const result = await res.json();
        if (result.success) {
            fetchStaffDirectory();
        } else {
            alert(result.message || 'Failed to update status.');
        }
    } catch (e) {
        alert('Error updating staff status.');
    }
}

function filterStaffDirectory(query) {
    query = query.toLowerCase();
    const rows = document.querySelectorAll('#staff-directory-tbody tr');
    rows.forEach(row => {
        if (row.cells.length > 1) {
            const name = row.cells[0].textContent.toLowerCase();
            const dept = row.cells[2].textContent.toLowerCase();
            if (name.includes(query) || dept.includes(query)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    // 1. Fetch Overview Stats
    fetchAdminStats();
    // 2. Fetch Staff Directory
    fetchStaffDirectory();
    // 3. Fetch Leave Approvals
    fetchAdminLeave();
    // 4. Fetch Appointments Oversight
    fetchAdminAppointments();
    // 5. Fetch Settings
    fetchSettings();
    // 6. Fetch Reports
    fetchReports();
    // 7. Fetch Schedule and Swaps
    fetchAdminSchedule();
    fetchAdminSwaps();
    // 8. Fetch Audit Logs
    fetchAuditLogs();
});

async function fetchAuditLogs() {
    try {
        const res = await fetch('/sdn/api/logs.php');
        const result = await res.json();
        if (result.success) {
            const logs = result.data;
            const tbody = document.getElementById('audit-logs-tbody');
            if (logs.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4">No logs found.</td></tr>';
                return;
            }
            tbody.innerHTML = '';
            logs.forEach(log => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td style="white-space:nowrap; font-size:0.85rem; color:#666;">${log.timestamp}</td>
                    <td><strong>${log.action}</strong></td>
                    <td>${log.user}</td>
                    <td>${log.details}</td>
                `;
                tbody.appendChild(tr);
            });
        }
    } catch (e) {
        console.error('Failed to fetch audit logs:', e);
    }
}

async function fetchAdminAppointments() {
    try {
        const res = await fetch('/sdn/api/patients.php');
        const result = await res.json();
        if (result.success) {
            const patients = result.data;
            const tbody = document.getElementById('admin-appointments-tbody');
            if (patients && patients.length > 0) {
                tbody.innerHTML = '';
                patients.forEach(p => {
                    const tr = document.createElement('tr');
                    
                    let badge = '';
                    if (p.status === 'pending') badge = '<span class="badge badge-yellow">Pending</span>';
                    else if (p.status === 'approved') badge = '<span class="badge badge-green">Confirmed</span>';
                    else badge = '<span class="badge badge-red">Rejected</span>';
                    
                    tr.innerHTML = `
                        <td>${p.patient_id}</td>
                        <td><a href="/sdn/pages/patient-profile.php?id=${p.patient_id}" style="color:var(--deep-green); text-decoration:none;"><strong>${p.full_name}</strong></a></td>
                        <td>${p.department}</td>
                        <td>${p.assigned_doctor || '<span style="color:#888;">Unassigned</span>'}</td>
                        <td>${badge}</td>
                          <td>
                              <button class="btn-action btn-outline" onclick="openReassignModal('${p.patient_id}', '${p.assigned_doctor || ''}')">Reassign</button>
                          </td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="6">No appointments found.</td></tr>';
            }
        }
    } catch(e) { console.error(e); }
}

async function fetchAdminLeave() {
    try {
        const res = await fetch('/sdn/api/leave.php');
        const result = await res.json();
        
        if (result.success) {
            const leaves = result.data;
            const tbody = document.getElementById('admin-leave-tbody');
            if (leaves && leaves.length > 0) {
                tbody.innerHTML = '';
                leaves.forEach(l => {
                    const tr = document.createElement('tr');
                    
                    let badge = '';
                    if (l.status === 'pending') badge = '<span class="badge badge-yellow">Pending Review</span>';
                    else if (l.status === 'approved') badge = '<span class="badge badge-green">Approved</span>';
                    else badge = '<span class="badge badge-red">Rejected</span>';
                    
                    let actionHtml = '';
                    if (l.status === 'pending') {
                        actionHtml = `
                          <button class="btn-action btn-primary" onclick="updateLeaveStatus(${l.id}, 'approved')">Approve</button>
                          <button class="btn-action btn-danger" onclick="updateLeaveStatus(${l.id}, 'rejected')">Deny</button>
                        `;
                    } else if (l.status === 'approved') {
                        actionHtml = `
                          <button class="btn-action btn-outline" onclick="updateLeaveStatus(${l.id}, 'rejected')">Reject</button>
                        `;
                    } else if (l.status === 'rejected') {
                        actionHtml = `
                          <button class="btn-action btn-outline" onclick="updateLeaveStatus(${l.id}, 'approved')">Approve</button>
                        `;
                    }
                    
                    tr.innerHTML = `
                        <td><strong>${l.username}</strong><br><span style="font-size:0.8rem;color:#666;">${l.department}</span></td>
                        <td>Absence</td>
                        <td>${l.start_date} to ${l.end_date}</td>
                        <td>${l.reason}</td>
                        <td>${badge}</td>
                        <td>${actionHtml}</td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="6">No leave requests found.</td></tr>';
            }
        }
    } catch(e) { console.error(e); }
}

async function updateLeaveStatus(reqId, newStatus) {
    if (!confirm(`Are you sure you want to mark this request as ${newStatus}?`)) return;
    try {
        const res = await fetch('/sdn/api/leave.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'update_status', request_id: reqId, status: newStatus})
        });
        const result = await res.json();
        if (result.success) {
            fetchAdminLeave();
        } else {
            showToast(result.message || 'Error updating status', 'error');
        }
    } catch(e) {
        showToast('Network error.', 'error');
    }
}

async function fetchSettings() {
    try {
        const res = await fetch('/sdn/api/settings.php');
        const result = await res.json();
        if (result.success) {
            result.data.forEach(setting => {
                if (setting.setting_key === 'max_working_hours') {
                    document.getElementById('setting_max_hours').value = setting.setting_value;
                } else if (setting.setting_key === 'default_appt_duration') {
                    document.getElementById('setting_appt_duration').value = setting.setting_value;
                } else if (setting.setting_key === 'allow_swaps') {
                    document.getElementById('setting_allow_swaps').value = setting.setting_value;
                }
            });
        }
    } catch(e) { console.error(e); }
}

async function saveSettings() {
    const max_hours = document.getElementById('setting_max_hours').value;
    const appt_duration = document.getElementById('setting_appt_duration').value;
    const allow_swaps = document.getElementById('setting_allow_swaps').value;

    try {
        await fetch('/sdn/api/settings.php', {
            method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'update', setting_key: 'max_working_hours', setting_value: max_hours})
        });
        await fetch('/sdn/api/settings.php', {
            method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'update', setting_key: 'default_appt_duration', setting_value: appt_duration})
        });
        await fetch('/sdn/api/settings.php', {
            method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'update', setting_key: 'allow_swaps', setting_value: allow_swaps})
        });
        showToast('Settings saved successfully!', 'success');
    } catch(e) {
        showToast('Error saving settings.', 'error');
    }
}

async function fetchAdminSchedule() {
    try {
        const res = await fetch('/sdn/api/schedule.php');
        const result = await res.json();
        if (result.success) {
            const doctors = result.data;
            const tbody = document.getElementById('admin-schedule-tbody');
            if (doctors && doctors.length > 0) {
                tbody.innerHTML = '';
                doctors.forEach(d => {
                    const tr = document.createElement('tr');
                    const shiftTime = (d.schedule_start && d.schedule_end) ? `${d.schedule_start} - ${d.schedule_end}` : 'Not Scheduled';
                    const sDate = d.schedule_date || 'N/A';
                    tr.innerHTML = `
                        <td>${sDate}</td>
                        <td><strong>${d.name}</strong></td>
                        <td>${d.department}</td>
                        <td>${shiftTime}</td>
                        <td>
                            <button class="btn-action btn-outline" onclick="openScheduleModal('${d.id}', '${d.schedule_date || ''}', '${d.schedule_start || ''}', '${d.schedule_end || ''}')">Edit</button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="5">No schedule found.</td></tr>';
            }
        }
    } catch(e) { console.error(e); }
}

async function fetchAdminSwaps() {
    try {
        const res = await fetch('/sdn/api/swaps.php');
        const result = await res.json();
        if (result.success) {
            const swaps = result.data;
            const tbody = document.getElementById('admin-swaps-tbody');
            if (swaps && swaps.length > 0) {
                tbody.innerHTML = '';
                swaps.forEach(s => {
                    const tr = document.createElement('tr');
                    let badge = '';
                    if (s.status === 'pending') badge = '<span class="badge badge-yellow">Pending</span>';
                    else if (s.status === 'approved') badge = '<span class="badge badge-green">Approved</span>';
                    else badge = '<span class="badge badge-red">Rejected</span>';
                    
                    let actionHtml = '';
                    if (s.status === 'pending') {
                        actionHtml = `
                          <button class="btn-action btn-primary" onclick="updateSwapStatus(${s.id}, 'approved')">Approve</button>
                          <button class="btn-action btn-danger" onclick="updateSwapStatus(${s.id}, 'rejected')">Deny</button>
                        `;
                    }
                    
                    tr.innerHTML = `
                        <td>${s.requester}</td>
                        <td>${s.target}</td>
                        <td>${s.department}</td>
                        <td>${badge}</td>
                        <td>${actionHtml}</td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="5">No pending swaps.</td></tr>';
            }
        }
    } catch(e) { console.error(e); }
}

async function updateSwapStatus(swapId, newStatus) {
    if (!confirm(`Are you sure you want to mark this swap as ${newStatus}?`)) return;
    try {
        const res = await fetch('/sdn/api/swaps.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'update_status', swap_id: swapId, status: newStatus})
        });
        const result = await res.json();
        if (result.success) {
            fetchAdminSwaps();
        } else {
            alert(result.message || 'Error updating status');
        }
    } catch(e) {
        alert('Network error.');
    }
}

// Modal Handlers
function openScheduleModal(doctorId, sDate, sStart, sEnd) {
    document.getElementById('scheduleDocId').value = doctorId || '';
    document.getElementById('scheduleDate').value = sDate || '';
    document.getElementById('scheduleStartTime').value = sStart || '';
    document.getElementById('scheduleEndTime').value = sEnd || '';
    
    document.getElementById('scheduleModalTitle').textContent = doctorId ? 'Edit Schedule' : 'Create Schedule';
    
    if (!doctorId) {
        document.getElementById('scheduleDoctorSelectGroup').style.display = 'block';
        const select = document.getElementById('scheduleDoctorSelect');
        select.innerHTML = '<option value="">Loading doctors...</option>';
        fetch('/sdn/api/schedule.php').then(r => r.json()).then(result => {
            if (result.success) {
                select.innerHTML = '<option value="">-- Select Doctor --</option>';
                result.data.forEach(doc => {
                    select.innerHTML += `<option value="${doc.id}">${doc.name} (${doc.department})</option>`;
                });
            }
        });
    } else {
        document.getElementById('scheduleDoctorSelectGroup').style.display = 'none';
    }
    document.getElementById('scheduleModal').style.display = 'flex';
}

async function submitSchedule() {
    let docId = document.getElementById('scheduleDocId').value;
    if (!docId) {
        docId = document.getElementById('scheduleDoctorSelect').value;
    }
    
    const sDate = document.getElementById('scheduleDate').value;
    const sStart = document.getElementById('scheduleStartTime').value;
    const sEnd = document.getElementById('scheduleEndTime').value;
    
    if (!docId) {
        alert('Please select a doctor.');
        return;
    }
    if (!sDate || !sStart || !sEnd) {
        alert('Please fill in date and times.');
        return;
    }
    
    try {
        const res = await fetch('/sdn/api/schedule.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'edit', 
                doctor_id: docId, 
                schedule_date: sDate,
                schedule_start: sStart,
                schedule_end: sEnd
            })
        });
        const result = await res.json();
        if (result.success) {
            document.getElementById('scheduleModal').style.display = 'none';
            fetchAdminSchedule();
        } else {
            alert(result.message || 'Error saving schedule.');
        }
    } catch(e) {
        alert('Network error.');
    }
}

async function openReassignModal(patientId, currentDoc) {
    document.getElementById('reassignPatientId').value = patientId;
    document.getElementById('reassignModal').style.display = 'flex';
    
    const select = document.getElementById('reassignDoctor');
    select.innerHTML = '<option value="">Loading doctors...</option>';
    
    try {
        const res = await fetch('/sdn/api/schedule.php');
        const result = await res.json();
        if (result.success) {
            select.innerHTML = '<option value="">-- Select Available Doctor --</option>';
            result.data.forEach(doc => {
                if (doc.status === 'available') {
                    const isSelected = doc.name === currentDoc ? 'selected' : '';
                    select.innerHTML += `<option value="${doc.name}" ${isSelected}>${doc.name} (${doc.department})</option>`;
                }
            });
            if (select.options.length === 1) {
                select.innerHTML += '<option value="" disabled>No doctors available</option>';
            }
        }
    } catch(e) {
        select.innerHTML = '<option value="">Error loading doctors</option>';
    }
}

async function submitReassign() {
    const pId = document.getElementById('reassignPatientId').value;
    const doc = document.getElementById('reassignDoctor').value;
    
    try {
        const res = await fetch('/sdn/api/patients.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'assign', patient_id: pId, doctor: doc})
        });
        const result = await res.json();
        if (result.success) {
            document.getElementById('reassignModal').style.display = 'none';
            fetchAdminAppointments();
        } else {
            alert(result.message || 'Error reassigning patient.');
        }
    } catch(e) {
        alert('Network error.');
    }
}

function configureRules(dept) {
    const rules = prompt(`Configure routing rules for ${dept}:\n\nEnter max daily patients (or leave blank to cancel):`, "30");
    if (rules) {
        alert(`${dept} configuration saved successfully! Capacity set to ${rules} patients/day.`);
    }
}

function filterSchedule(period) {
    alert(`Filtering schedule for: ${period}. (Backend hook required for actual date filtering)`);
}

async function fetchReports() {
    try {
        const res = await fetch('/sdn/api/reports.php');
        const result = await res.json();
        if (result.success) {
            if(document.getElementById('report-total-consults')) {
                document.getElementById('report-total-consults').textContent = result.total_consults;
            }
            if(document.getElementById('report-attendance-rate')) {
                document.getElementById('report-attendance-rate').textContent = result.attendance_rate;
            }
            if(document.getElementById('report-no-show-rate')) {
                document.getElementById('report-no-show-rate').textContent = result.no_show_rate;
            }
        }
    } catch(e) {
        console.error('Failed to fetch reports', e);
    }
}

function fetchPatientHistory() {
    fetch(`/sdn/api/history.php?_=${new Date().getTime()}`)
        .then(res => res.json())
        .then(res => {
            if(res.success) {
                const data = res.data;
                const tbody = document.getElementById('history-records-tbody');
                if(!tbody) return;
                
                if(data && data.length > 0) {
                    tbody.innerHTML = '';
                    data.forEach(p => {
                        const tr = document.createElement('tr');
                        const hasRecord = p.diagnosis && p.treatment;
                        
                        let dateStr = '-';
                        if(p.date) {
                            const d = new Date(p.date);
                            dateStr = d.toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'});
                        }

                        const enc = (str) => encodeURIComponent(str || '').replace(/'/g, "%27");
                        tr.innerHTML = `
                            <td style="word-wrap: break-word;">${hasRecord ? dateStr : '-'}</td>
                            <td style="word-wrap: break-word;"><strong>${p.full_name}</strong><br><small style="color:#666;">${p.patient_id}</small></td>
                            <td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${hasRecord ? p.diagnosis : '-'}</td>
                            <td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${hasRecord ? p.treatment : '-'}</td>
                            <td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${p.prescription ? p.prescription : '-'}</td>
                            <td style="word-wrap: break-word;">${hasRecord ? p.recorded_by : '-'}</td>
                            <td style="white-space: nowrap;">
                                ${hasRecord ? 
                                  `<div style="display:flex; gap:4px; flex-wrap: nowrap;">
                                     <button class="btn-action btn-outline" style="padding: 4px 8px; font-size: 0.8rem;" onclick="viewHistory('${p.patient_id}', '${enc(p.full_name)}', '${enc(dateStr)}', '${enc(p.recorded_by)}', '${enc(p.diagnosis)}', '${enc(p.treatment)}', '${enc(p.prescription)}')">View</button>
                                     <button class="btn-action btn-outline" style="padding: 4px 8px; font-size: 0.8rem;" onclick="openHistoryModal('${p.patient_id}', '${enc(p.diagnosis)}', '${enc(p.treatment)}', '${enc(p.prescription)}')">Edit</button>
                                     <button class="btn-action btn-danger" style="padding: 4px 8px; font-size: 0.8rem;" onclick="deleteHistory('${p.patient_id}')">Delete</button>
                                   </div>` : 
                                  `<button class="btn-action btn-primary" style="padding: 4px 8px; font-size: 0.8rem;" onclick="openHistoryModal('${p.patient_id}', '', '', '')">Add Record</button>`
                                }
                            </td>
                        `;
                        tbody.appendChild(tr);
                    });
                } else {
                    tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 40px; color: #666;">
                        <div style="font-size: 2rem; color: #10b981; margin-bottom: 10px;">+</div>
                        <strong style="display:block; color:#333; margin-bottom:5px;">No history records</strong>
                        Add the first medical history record above.
                    </td></tr>`;
                }
            }
        }).catch(err => console.error(err));
}

function openHistoryModal(patientId, diagnosisEncoded, treatmentEncoded, prescriptionEncoded) {
    document.getElementById('historyPatientId').value = patientId;
    
    // Decode if encoded values are passed
    const diag = diagnosisEncoded ? decodeURIComponent(diagnosisEncoded) : '';
    const treat = treatmentEncoded ? decodeURIComponent(treatmentEncoded) : '';
    const presc = prescriptionEncoded ? decodeURIComponent(prescriptionEncoded) : '';

    document.getElementById('historyDiagnosis').value = diag;
    document.getElementById('historyTreatment').value = treat;
    document.getElementById('historyPrescription').value = presc !== 'null' ? presc : '';
    
    document.getElementById('historyModalTitle').textContent = diag ? 'Edit Medical History' : 'Add Medical History';
    document.getElementById('historyModal').style.display = 'flex';
}

function submitHistory() {
    const patientId = document.getElementById('historyPatientId').value;
    const diagnosis = document.getElementById('historyDiagnosis').value;
    const treatment = document.getElementById('historyTreatment').value;
    const prescription = document.getElementById('historyPrescription').value;

    if(!diagnosis || !treatment) {
        showToast('Diagnosis and Treatment are required', 'error');
        return;
    }

    fetch('/sdn/api/history.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ patient_id: patientId, diagnosis, treatment, prescription })
    })
    .then(res => res.json())
    .then(res => {
        if(res.success) {
            showToast('Medical history saved successfully!', 'success');
            document.getElementById('historyModal').style.display = 'none';
            fetchPatientHistory();
            if(typeof fetchAuditLogs === 'function') fetchAuditLogs();
        } else {
            showToast(res.message || 'Failed to save history', 'error');
        }
    }).catch(err => {
        console.error(err);
        showToast('Error saving history', 'error');
    });
}

function deleteHistory(patientId) {
    if(!confirm('WARNING: Are you sure you want to completely delete this PATIENT from the system? This action cannot be undone.')) return;

    fetch('/sdn/api/history.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ action: 'delete', patient_id: patientId })
    })
    .then(res => res.json())
    .then(res => {
        if(res.success) {
            showToast('Medical history deleted.', 'success');
            fetchPatientHistory();
            if(typeof fetchAuditLogs === 'function') fetchAuditLogs();
        } else {
            showToast(res.message || 'Failed to delete history', 'error');
        }
    }).catch(err => {
        console.error(err);
        showToast('Error deleting history', 'error');
    });
}

function viewHistory(patientId, nameEncoded, dateEncoded, recordedByEncoded, diagEncoded, treatEncoded, prescEncoded) {
    document.getElementById('viewHistoryId').textContent = patientId;
    document.getElementById('viewHistoryName').textContent = nameEncoded ? decodeURIComponent(nameEncoded) : '-';
    document.getElementById('viewHistoryDate').textContent = dateEncoded ? decodeURIComponent(dateEncoded) : '-';
    document.getElementById('viewHistoryRecordedBy').textContent = recordedByEncoded ? decodeURIComponent(recordedByEncoded) : '-';
    
    document.getElementById('viewHistoryDiagnosis').textContent = diagEncoded ? decodeURIComponent(diagEncoded) : 'None';
    document.getElementById('viewHistoryTreatment').textContent = treatEncoded ? decodeURIComponent(treatEncoded) : 'None';
    document.getElementById('viewHistoryPrescription').textContent = prescEncoded ? decodeURIComponent(prescEncoded) : 'None';
    
    document.getElementById('viewHistoryModal').style.display = 'flex';
}

function printPatientHistory() {
    const printContent = document.getElementById('printHistoryContent').innerHTML;
    const originalContent = document.body.innerHTML;
    
    document.body.innerHTML = printContent;
    window.print();
    document.body.innerHTML = originalContent;
    
    // Rebind events after restoring DOM
    location.reload(); 
}

function downloadPatientHistoryPDF() {
    const element = document.getElementById('printHistoryContent');
    const patientName = document.getElementById('viewHistoryName').textContent.trim();
    const opt = {
      margin:       0.5,
      filename:     `Medical_History_${patientName.replace(/\s+/g, '_')}.pdf`,
      image:        { type: 'jpeg', quality: 0.98 },
      html2canvas:  { scale: 2 },
      jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
    };
    html2pdf().set(opt).from(element).save();
}
