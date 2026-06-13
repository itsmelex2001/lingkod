// public/js/staff-spa.js

let currentDeptFilter = 'all';
let currentMonth = new Date();

function changeMonth(offset) {
    currentMonth.setMonth(currentMonth.getMonth() + offset);
    const monthInput = document.getElementById('calendarMonthFilter');
    if (monthInput) {
        const y = currentMonth.getFullYear();
        const m = String(currentMonth.getMonth() + 1).padStart(2, '0');
        monthInput.value = `${y}-${m}`;
    }
    fetchCalendar(currentDeptFilter);
}

async function fetchCalendar(deptFilter = currentDeptFilter) {
    currentDeptFilter = deptFilter;
    try {
        const docRes = await fetch('/api/schedule.php');
        const docResult = await docRes.json();
        const docMap = {};
        const docList = [];
        if (docResult.success) {
            docResult.data.forEach(d => {
                docMap[d.name] = (d.department || '').toLowerCase();
                docList.push(d);
            });
        }

        const res = await fetch('/api/patients.php'); 
        const result = await res.json();
        
        if (result.success) {
            let appointments = result.data.filter(a => a.status === 'approved' || a.status === 'assigned');
            
            if (deptFilter !== 'all') {
                appointments = appointments.filter(app => {
                    const docDept = docMap[app.assigned_doctor] || 'general';
                    return docDept.includes(deptFilter);
                });
            }

            const monthInput = document.getElementById('calendarMonthFilter');
            if (monthInput && monthInput.value) {
                const [y, m] = monthInput.value.split('-');
                currentMonth.setFullYear(parseInt(y, 10));
                currentMonth.setMonth(parseInt(m, 10) - 1);
            } else if (monthInput) {
                const y = currentMonth.getFullYear();
                const m = String(currentMonth.getMonth() + 1).padStart(2, '0');
                monthInput.value = `${y}-${m}`;
            }

            const grid = document.getElementById('staff-calendar-grid');
            if (!grid) return;
            
            grid.innerHTML = '<div class="cal-header">Sun</div><div class="cal-header">Mon</div><div class="cal-header">Tue</div><div class="cal-header">Wed</div><div class="cal-header">Thu</div><div class="cal-header">Fri</div><div class="cal-header">Sat</div>';
            
            const year = currentMonth.getFullYear();
            const month = currentMonth.getMonth();
            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();

            for (let i = 0; i < firstDay; i++) {
                grid.innerHTML += `<div class="cal-day cal-day-empty"></div>`;
            }

            for (let day = 1; day <= daysInMonth; day++) {
                const targetDate = new Date(year, month, day);
                const dateStr = targetDate.getFullYear() + '-' + String(targetDate.getMonth()+1).padStart(2, '0') + '-' + String(targetDate.getDate()).padStart(2, '0');
                
                const dayAppointments = appointments.filter(app => {
                    const appDate = (app.appointment_date || app.created_at || '').split(' ')[0];
                    return appDate === dateStr;
                });
                
                const dayDocsUnavailable = docList.filter(d => {
                    if (d.status !== 'leave' && d.status !== 'booked') return false;
                    return d.schedule_date === dateStr;
                });

                let eventsHtml = '';
                dayAppointments.forEach(app => {
                    const docName = app.assigned_doctor || 'Unassigned';
                    eventsHtml += `<div class="cal-event" onclick="alert('Patient: ${app.full_name}\\nAssigned: ${docName}')">${app.full_name}<br><small style="opacity:0.8">${docName}</small></div>`;
                });

                dayDocsUnavailable.forEach(d => {
                    if (deptFilter !== 'all' && !(d.department || '').toLowerCase().includes(deptFilter)) return;
                    eventsHtml += `<div class="cal-event cal-event-leave" style="background: #fee2e2; color: #b91c1c; border-left-color: #ef4444;" onclick="alert('Dr. ${d.name} is ${d.status} on this date.')">Dr. ${d.name} (${d.status})</div>`;
                });
                
                const isToday = new Date().toDateString() === targetDate.toDateString();
                const todayClass = isToday ? 'cal-day-today' : '';

                grid.innerHTML += `
                  <div class="cal-day ${todayClass}">
                    <span class="cal-date">${day}</span>
                    ${eventsHtml}
                  </div>
                `;
            }
            
            const totalCells = firstDay + daysInMonth;
            const remaining = (totalCells % 7 === 0) ? 0 : 7 - (totalCells % 7);
            for(let i = 0; i < remaining; i++){
                grid.innerHTML += `<div class="cal-day cal-day-empty"></div>`;
            }
        }
    } catch (e) {
        console.error('Failed to fetch calendar:', e);
    }
}

function filterHub(dept, btnElement) {
    const btns = document.querySelectorAll('.calendar-toolbar .filter-btn');
    btns.forEach(b => b.classList.remove('active'));
    btnElement.classList.add('active');
    fetchCalendar(dept);
}

document.addEventListener('DOMContentLoaded', () => {
    fetchPatientQueue();
    fetchCalendar();
});


async function fetchPatientQueue() {
    try {
        const res = await fetch('/api/patients.php');
        const result = await res.json();
        
        if (result.success) {
            const patients = result.data;
            const tbody = document.getElementById('patient-queue-tbody');
            
            if (patients && patients.length > 0) {
                tbody.innerHTML = '';
                patients.forEach(p => {
                    const tr = document.createElement('tr');
                    
                    let badgeClass = 'badge-warning';
                    if (p.status === 'approved') badgeClass = 'badge-success';
                    if (p.status === 'rejected') badgeClass = 'badge-danger';
                    
                    // The API returns appointment_date (e.g. 2026-10-24)
                    const dateObj = new Date(p.appointment_date || p.created_at);
                    const dateString = dateObj.toLocaleDateString();
                    
                    let assignedDoctorHtml = '<span style="color:#9ca3af; font-style:italic;">Not Assigned</span>';
                    let actionHtml = '';
                    if (p.status === 'pending') {
                        actionHtml += `
                            <button class="btn-action" style="background:#dcfce7; color:#166534;" onclick="updatePatientStatus('${p.patient_id}', 'approve')">Approve</button>
                            <button class="btn-action" style="background:#fee2e2; color:#991b1b;" onclick="updatePatientStatus('${p.patient_id}', 'reject')">Reject</button>
                        `;
                    } else if (p.status === 'approved') {
                        if (p.assigned_doctor) {
                            assignedDoctorHtml = `<span style="color:#15803d; font-weight:600;">${p.assigned_doctor}</span>`;
                            actionHtml += `<button class="btn-action btn-outline" style="font-size: 0.75rem; padding: 4px 8px;" onclick="openAssignModal('${p.patient_id}')">Reassign</button>`;
                        } else {
                            actionHtml += `<button class="btn-action" onclick="openAssignModal('${p.patient_id}')">Assign Doctor</button>`;
                        }
                    }
                    
                    tr.innerHTML = `
                        <td>${dateString}</td>
                        <td>
                            <a href="/pages/patient-profile.php?id=${p.patient_id}" style="color:var(--deep-green); text-decoration:none;"><strong>${p.full_name}</strong></a><br>
                            <span style="font-size: 0.8rem; color:#666;">ID: ${p.patient_id}</span>
                        </td>
                        <td>${p.department}</td>
                        <td><span class="badge ${badgeClass}">${p.status.toUpperCase()}</span></td>
                        <td>${assignedDoctorHtml}</td>
                        <td>${actionHtml}</td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="5">No patients in queue.</td></tr>';
            }
        }
    } catch (e) {
        console.error('Failed to fetch patient queue:', e);
    }
}

async function fetchStaffLeave() {
    try {
        const res = await fetch('/api/leave.php');
        const result = await res.json();
        if (result.success) {
            const leaves = result.data;
            const tbody = document.getElementById('staff-leave-tbody');
            if (leaves && leaves.length > 0) {
                tbody.innerHTML = '';
                leaves.forEach(l => {
                    const tr = document.createElement('tr');
                    
                    let badge = '';
                    if (l.status === 'pending') badge = '<span class="badge badge-warning">Pending</span>';
                    else if (l.status === 'approved') badge = '<span class="badge badge-success">Approved</span>';
                    else badge = '<span class="badge badge-danger">Rejected</span>';
                    
                    tr.innerHTML = `
                        <td>${l.start_date} to ${l.end_date}</td>
                        <td>${badge}</td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="2">No past requests.</td></tr>';
            }
        }
    } catch(e) { console.error(e); }
}

async function updatePatientStatus(patientId, action) {
    if (!confirm(`Are you sure you want to ${action} this patient?`)) return;
    try {
        const res = await fetch('/api/patients.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: action, patient_id: patientId })
        });
        const result = await res.json();
        if (result.success) {
            fetchPatientQueue();
            fetchCalendar(); // Refresh calendar if approved
        } else {
            alert(result.message || 'Failed to update status.');
        }
    } catch (e) {
        alert('Error updating status.');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    fetchPatientQueue();
    fetchCalendar();
    fetchStaffLeave();
    
    // Bind Leave Form
    const leaveForm = document.getElementById('leave-request-form');
    if (leaveForm) {
        leaveForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const start = document.getElementById('leaveStartDate').value;
            const end = document.getElementById('leaveEndDate').value;
            const reason = document.getElementById('leaveReason').value;
            
            try {
                const res = await fetch('/api/leave.php', {
                    method: 'POST',
                    headers: {'Content-Type':'application/json'},
                    body: JSON.stringify({action: 'create', start_date: start, end_date: end, reason: reason})
                });
                const result = await res.json();
                if (result.success) {
                    alert('Leave request submitted successfully.');
                    leaveForm.reset();
                    fetchStaffLeave();
                } else {
                    alert(result.message || 'Error submitting request.');
                }
            } catch(err) {
                alert('Network error.');
            }
        });
    }

    // Fetch reports for Staff Dashboard
    fetchReports();
    
    // Fetch patient history for History view
    fetchPatientHistory();
});

async function openAssignModal(patientId) {
    document.getElementById('assignPatientId').value = patientId;
    document.getElementById('assignModal').style.display = 'flex';
    
    const select = document.getElementById('assignDoctor');
    select.innerHTML = '<option value="">Loading doctors...</option>';
    
    try {
        const res = await fetch('/api/schedule.php');
        const result = await res.json();
        if (result.success) {
            select.innerHTML = '<option value="">-- Select Available Doctor --</option>';
            result.data.forEach(doc => {
                if (doc.status === 'available') {
                    select.innerHTML += `<option value="${doc.name}">${doc.name} (${doc.department})</option>`;
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

async function submitAssign() {
    const pId = document.getElementById('assignPatientId').value;
    const doc = document.getElementById('assignDoctor').value;
    
    try {
        const res = await fetch('/api/patients.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'assign', patient_id: pId, doctor: doc})
        });
        const result = await res.json();
        if (result.success) {
            document.getElementById('assignModal').style.display = 'none';
            fetchPatientQueue();
            fetchCalendar();
        } else {
            alert(result.message || 'Error assigning doctor.');
        }
    } catch(e) {
        alert('Network error.');
    }
}

async function fetchReports() {
    try {
        const res = await fetch('/api/reports.php');
        const result = await res.json();
        if (result.success) {
            if(document.getElementById('report-total-patients-today')) {
                document.getElementById('report-total-patients-today').textContent = result.total_patients_today;
            }
            if(document.getElementById('report-no-show-rate-staff')) {
                document.getElementById('report-no-show-rate-staff').textContent = result.no_show_rate;
            }
            if(document.getElementById('report-shift-coverage')) {
                document.getElementById('report-shift-coverage').textContent = result.shift_coverage;
            }
        }
    } catch(e) {
        console.error('Failed to fetch reports', e);
    }
}

function fetchPatientHistory() {
    fetch(`/api/history.php?_=${new Date().getTime()}`)
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

    fetch('/api/history.php', {
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

    fetch('/api/history.php', {
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
