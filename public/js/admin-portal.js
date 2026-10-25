function escapeHtml(str) {
    if (!str && str !== 0) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

document.addEventListener('DOMContentLoaded', () => {
    // Navigation setup
    document.querySelectorAll('.sidebar-nav .nav-link').forEach(link => {
        link.addEventListener('click', e => {
            const targetView = e.currentTarget.dataset.view;
            if(!targetView) return;
            e.preventDefault();
            document.querySelectorAll('.sidebar-nav .nav-link').forEach(l => l.classList.remove('active'));
            document.querySelectorAll('.view-section').forEach(v => v.classList.remove('active'));
            e.currentTarget.classList.add('active');
            document.getElementById('view-' + targetView).classList.add('active');
            syncTableScrollbars();

            if (targetView === 'reports') {
                loadReports();
            } else if (targetView === 'audit') {
                loadAudit();
            }
        });
    });

    // Close Modals
    document.querySelectorAll('.close, .btn-modal-cancel').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.modal').forEach(m => m.classList.remove('show'));
        });
    });

    // Load initial data
    loadDashboard();
    loadRBAC();
    loadDoctors();
    loadPMI();
    loadAudit();
    loadReports();

    // Auto real-time refresh every 6 seconds
    setInterval(() => {
        const activeSection = document.querySelector('.view-section.active');
        if (activeSection) {
            if (activeSection.id === 'view-overview' || activeSection.id === 'view-dashboard') {
                loadDashboard();
            } else if (activeSection.id === 'view-pmi') {
                loadPMI();
            } else if (activeSection.id === 'view-doctors') {
                loadDoctors();
            } else if (activeSection.id === 'view-rbac') {
                loadRBAC();
            } else if (activeSection.id === 'view-reports') {
                loadReports();
            } else if (activeSection.id === 'view-audit') {
                loadAudit();
            }
        }
    }, 6000);

    // Refresh immediately when returning to tab
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            loadDashboard();
            loadPMI();
        }
    });
    window.addEventListener('focus', () => {
        loadDashboard();
        loadPMI();
    });
});

function showToast(msg, type='success') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.style.cssText = 'position: fixed; bottom: 30px; right: 30px; z-index: 9999; display: flex; flex-direction: column; gap: 12px; pointer-events: none;';
        document.body.appendChild(container);
    }
    
    const toast = document.createElement('div');
    const isError = type === 'error' || type === 'danger';
    
    const bg = isError ? '#fef2f2' : '#ebfbf1';
    const border = isError ? '#ef4444' : '#22c55e';
    const textColor = isError ? '#991b1b' : '#065f46';
    const iconBg = isError ? '#f87171' : '#4ade80'; // bright green for icon
    
    const svgIcon = isError 
        ? `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>`
        : `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>`;

    toast.style.cssText = `
        background: ${bg};
        color: ${textColor};
        border-left: 6px solid ${border};
        padding: 14px 24px 14px 16px;
        border-radius: 8px;
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.05);
        font-family: 'Inter', 'Poppins', sans-serif;
        font-size: 0.95rem;
        font-weight: 500;
        opacity: 0;
        transform: translateX(30px);
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        display: flex;
        align-items: center;
        gap: 14px;
        pointer-events: auto;
    `;
    
    const iconWrapper = document.createElement('div');
    iconWrapper.style.cssText = `
        background: ${iconBg};
        min-width: 24px;
        height: 24px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: inset 0 -2px 0 rgba(0,0,0,0.1);
    `;
    iconWrapper.innerHTML = svgIcon;
    
    const text = document.createElement('span');
    text.innerText = msg;
    
    toast.appendChild(iconWrapper);
    toast.appendChild(text);
    container.appendChild(toast);
    
    requestAnimationFrame(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateX(0)';
    });
    
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(30px)';
        toast.style.pointerEvents = 'none';
        setTimeout(() => toast.remove(), 400);
    }, 4000);
}

function customConfirm(title, message, callback) {
    document.getElementById('confirmTitle').innerText = title;
    document.getElementById('confirmMessage').innerText = message;
    
    const confirmBtn = document.getElementById('confirmBtn');
    const newConfirmBtn = confirmBtn.cloneNode(true);
    confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
    
    newConfirmBtn.addEventListener('click', () => {
        closeConfirmModal();
        callback();
    });
    
    document.getElementById('confirmModal').classList.add('show');
}

function closeConfirmModal() {
    document.getElementById('confirmModal').classList.remove('show');
}

async function loadDashboard() {
    try {
        const res = await fetch('/sdn/api/admin-stats.php').then(r=>r.json());
        if(res.success) {
            document.getElementById('dashboard-stats').innerHTML = `
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Active Staff</h3>
                        <p>${res.active_staff || 0}</p>
                    </div>
                    <div class="stat-icon icon-blue">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Today's Patients</h3>
                        <p>${res.todays_patients || 0}</p>
                    </div>
                    <div class="stat-icon icon-green">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Pending Actions</h3>
                        <p>${res.pending_actions || 0}</p>
                    </div>
                    <div class="stat-icon icon-orange">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    </div>
                </div>
            `;
        }
    } catch(e) { console.error(e); }
    
    // Initial chart load
    loadChartData();
    // Load dashboard recent admissions table
    loadDashboardTable();
}

async function loadDashboardTable() {
    const tbody = document.getElementById('dashboard-patients-tbody');
    if (!tbody) return;
    try {
        const res = await fetch('/sdn/api/patients.php?_=' + Date.now()).then(r => r.json());
        if (res.success && res.data) {
            if (res.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:30px; color:var(--muted)">No patient records found.</td></tr>';
                return;
            }
            const recent = res.data.slice(0, 10);
            tbody.innerHTML = recent.map(p => {
                let badge = 'badge-yellow';
                if (p.status === 'approved') badge = 'badge-green';
                if (p.status === 'completed') badge = 'badge-blue';
                if (p.status === 'no-show' || p.status === 'rejected') badge = 'badge-red';
                if (p.status === 'transferred') badge = 'badge-purple';

                const isInquiry = p.inquiry_only == 1 || p.inquiry_only === true || p.inquiry_only === '1';
                const inquiryTag = isInquiry
                    ? `<span style="display:inline-block; margin-left:6px; padding:2px 7px; background:#fef3c7; color:#92400e; border:1px solid #fcd34d; border-radius:999px; font-size:0.68rem; font-weight:700; text-transform:uppercase; vertical-align:middle;">Inquiry</span>`
                    : '';

                let safeId = escapeHtml(p.patient_id);
                let safeName = escapeHtml(p.full_name);
                let safeDept = escapeHtml(p.department || 'N/A');
                let safeAppDate = isInquiry 
                    ? `<span style="color:#d97706; font-weight:600;">Inquiry (${escapeHtml(p.mode_of_inquiry || 'Online')})</span>`
                    : (escapeHtml(p.appointment_date || 'N/A') + (p.scheduled_time ? ` <small>(${escapeHtml(p.scheduled_time)})</small>` : ''));
                let safeStatus = escapeHtml(p.status ? p.status.toUpperCase() : '');

                return `
                <tr class="clickable-row" onclick="openPatientDetailsModal('${safeId}')" title="Click to view full patient profile">
                    <td><small style="font-weight:600; color:var(--deep-green);">${safeId}</small></td>
                    <td><strong>${safeName}</strong>${inquiryTag}</td>
                    <td>${safeDept}</td>
                    <td>${safeAppDate}</td>
                    <td><span class="badge ${badge}">${safeStatus}</span></td>
                    <td>
                        <button type="button" class="btn-table-action btn-table-details" onclick="event.stopPropagation(); openPatientDetailsModal('${safeId}')" title="View Details">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            <span>View</span>
                        </button>
                    </td>
                </tr>
                `;
            }).join('');
        }
    } catch(e) {
        console.error(e);
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; color:#ef4444;">Error loading patient records.</td></tr>';
    }
}

let patientsChartInstance = null;

function resetChartFilter() {
    const filterEl = document.getElementById('chart-filter');
    if(filterEl) filterEl.value = 'year';
    loadChartData();
}

async function loadChartData() {
    try {
        const filterEl = document.getElementById('chart-filter');
        const filterVal = filterEl ? filterEl.value : 'year';
        
        const res = await fetch(`/sdn/api/admin-stats.php?chart=${filterVal}`).then(r=>r.json());
        if(res.success && res.chart_labels && res.chart_data) {
            
            const ctx = document.getElementById('patientsChart').getContext('2d');
            
            if (patientsChartInstance) {
                patientsChartInstance.destroy();
            }
            
            patientsChartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: res.chart_labels,
                    datasets: [{
                        label: 'Registered Patients',
                        data: res.chart_data,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 3,
                        pointBackgroundColor: '#0c3915',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0 }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        }
    } catch(e) { console.error(e); }
}

// ── RBAC / STAFF MANAGEMENT ───────────────────────────────────────────────────
let allStaffList = [];

function renderRBACTable(list) {
    const tbody = document.getElementById('rbac-tbody');
    if(!tbody) return;

    if(!list || list.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:35px; color:var(--muted)">No staff members found matching filters.</td></tr>';
        syncTableScrollbars();
        return;
    }

    tbody.innerHTML = list.map(s => {
        let safeName = escapeHtml(s.full_name || s.username);
        let safeUser = escapeHtml(s.username);
        let safeDept = escapeHtml(s.department || 'N/A');
        let safeStatus = s.status === 'active' ? 'active' : 'inactive';
        let rawId = parseInt(s.id, 10) || 0;
        let rawNameAttr = encodeURIComponent(s.full_name || s.username);
        let rawUserAttr = encodeURIComponent(s.username);
        let rawDeptAttr = encodeURIComponent(s.department || '');

        return `
        <tr>
            <td><strong>${safeName}</strong></td>
            <td>${safeUser}</td>
            <td><span class="dept-badge" style="font-size:0.75rem; padding:4px 8px;">${safeDept}</span></td>
            <td><span class="badge ${safeStatus === 'active' ? 'badge-green' : 'badge-red'}">${escapeHtml(safeStatus.toUpperCase())}</span></td>
            <td style="text-align:center;">
                <button class="btn btn-outline" style="padding: 4px 10px; margin-right: 6px;" onclick="openEditStaffModal(${rawId}, decodeURIComponent('${rawNameAttr}'), decodeURIComponent('${rawUserAttr}'), decodeURIComponent('${rawDeptAttr}'), '${safeStatus}')">Edit</button>
                <button class="btn btn-danger" style="padding: 4px 10px;" onclick="deleteStaff(${rawId})">Delete</button>
            </td>
        </tr>
    `}).join('');

    syncTableScrollbars();
}

function filterRBACTable() {
    const searchVal = (document.getElementById('rbacSearchInput')?.value || '').toLowerCase().trim();
    const deptVal = (document.getElementById('rbacDeptFilter')?.value || 'all').toLowerCase();
    const statusVal = (document.getElementById('rbacStatusFilter')?.value || 'all').toLowerCase();

    let list = allStaffList || [];

    // Filter by department
    if (deptVal !== 'all') {
        list = list.filter(s => (s.department || '').toLowerCase().includes(deptVal));
    }

    // Filter by status
    if (statusVal !== 'all') {
        list = list.filter(s => (s.status || '').toLowerCase() === statusVal);
    }

    // Filter by search query (name, username, department)
    if (searchVal) {
        list = list.filter(s => 
            (s.full_name || '').toLowerCase().includes(searchVal) ||
            (s.username || '').toLowerCase().includes(searchVal) ||
            (s.department || '').toLowerCase().includes(searchVal)
        );
    }

    renderRBACTable(list);
}

function resetRBACFilters() {
    const searchEl = document.getElementById('rbacSearchInput');
    if (searchEl) searchEl.value = '';
    const deptEl = document.getElementById('rbacDeptFilter');
    if (deptEl) deptEl.value = 'all';
    const statusEl = document.getElementById('rbacStatusFilter');
    if (statusEl) statusEl.value = 'all';
    filterRBACTable();
}

async function loadRBAC() {
    try {
        const res = await fetch('/sdn/api/staff.php').then(r=>r.json());
        if(res.success && res.data) {
            allStaffList = res.data;
            filterRBACTable();
        }
    } catch(e) { console.error('Failed to load RBAC staff:', e); }
}

async function toggleStaffStatus(id, status) {
    customConfirm('Change Status', `Mark staff as ${status}?`, async () => {
        try {
            await fetch('/sdn/api/staff.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action: 'toggle', user_id: id, status: status})
            });
            loadRBAC();
            // Also update the modal button if it is open for this same user
            if(document.getElementById('editStaffId').value == id) {
                updateEditStaffStatusBtn(status);
            }
        } catch(e) { console.error(e); }
    });
}

function openStaffModal() { 
    document.getElementById('staffName').value = '';
    document.getElementById('staffUsername').value = '';
    document.getElementById('staffPass').value = '';
    document.getElementById('staffDept').value = 'OPU Psychiatry';
    document.getElementById('staffModal').classList.add('show'); 
}

function closeStaffModal() {
    const modal = document.getElementById('staffModal');
    if(modal) modal.classList.remove('show');
}

async function submitStaff() {
    const full_name = document.getElementById('staffName').value.trim();
    const username = document.getElementById('staffUsername').value.trim();
    const department = document.getElementById('staffDept').value;
    const password = document.getElementById('staffPass').value;
    
    if(!full_name || !username || !password || !department) return showToast('All fields required', 'error');
    
    // Validate username complexity
    const usernameRegex = /^[a-zA-Z0-9_]{5,20}$/;
    if (!usernameRegex.test(username)) {
        return showToast('Username must be 5-20 characters long (letters, numbers, underscores only).', 'error');
    }

    if (password.length < 6) {
        return showToast('Password must be at least 6 characters long.', 'error');
    }
    
    try {
        const res = await fetch('/sdn/api/staff.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'create', full_name, username, department, password})
        }).then(r=>r.json());
        
        if(res.success) {
            document.getElementById('staffModal').classList.remove('show');
            loadRBAC();
            showToast('Staff created successfully');
        } else {
            showToast(res.message, 'error');
        }
    } catch(e) { console.error(e); }
}

function openEditStaffModal(id, fullName, username, department, status) {
    document.getElementById('editStaffId').value = id;
    document.getElementById('editStaffName').value = fullName || '';
    document.getElementById('editStaffUsername').value = username || '';
    if(document.getElementById('editStaffDept')) {
        document.getElementById('editStaffDept').value = department || 'OPU Psychiatry';
    }
    document.getElementById('editStaffPass').value = ''; // clear password
    updateEditStaffStatusBtn(status);
    document.getElementById('editStaffModal').classList.add('show');
}

function closeEditStaffModal() {
    const modal = document.getElementById('editStaffModal');
    if(modal) modal.classList.remove('show');
}

function updateEditStaffStatusBtn(status) {
    const textEl = document.getElementById('editStaffStatusText');
    const btn = document.getElementById('editStaffStatusBtn');
    if(status === 'active') {
        textEl.innerText = 'Active';
        textEl.style.color = '#10b981';
        btn.innerText = 'Disable Account';
        btn.className = 'btn btn-outline';
        btn.onclick = () => toggleStaffStatus(document.getElementById('editStaffId').value, 'inactive');
    } else {
        textEl.innerText = 'Inactive';
        textEl.style.color = '#ef4444';
        btn.innerText = 'Activate Account';
        btn.className = 'btn btn-primary';
        btn.onclick = () => toggleStaffStatus(document.getElementById('editStaffId').value, 'active');
    }
}

async function submitEditStaff() {
    const user_id = document.getElementById('editStaffId').value;
    const full_name = document.getElementById('editStaffName').value.trim();
    const department = document.getElementById('editStaffDept').value;
    const password = document.getElementById('editStaffPass').value;
    
    if(!full_name) {
        return showToast('Full name is required.', 'error');
    }

    if(password && password.length < 6) {
        return showToast('Password must be at least 6 characters long.', 'error');
    }

    try {
        const payload = { action: 'update_staff', user_id, full_name, department };
        if(password) {
            payload.password = password;
        }

        const res = await fetch('/sdn/api/staff.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        }).then(r=>r.json());
        
        if(res.success) {
            closeEditStaffModal();
            loadRBAC();
            showToast('Staff member updated successfully');
        } else {
            showToast(res.message || 'Update failed', 'error');
        }
    } catch(e) { 
        console.error(e); 
        showToast('Error updating staff member', 'error');
    }
}

async function deleteStaff(user_id) {
    customConfirm('Delete Staff', 'Are you sure you want to delete this staff account permanently?', async () => {
        try {
            const res = await fetch('/sdn/api/staff.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action: 'delete', user_id})
            }).then(r=>r.json());
            
            if(res.success) {
                document.getElementById('editStaffModal').classList.remove('show');
                loadRBAC();
                showToast('Staff account deleted');
            } else {
                showToast(res.message, 'error');
            }
        } catch(e) { console.error(e); }
    });
}

// ── DOCTOR MANAGEMENT (CRUD) ─────────────────────────────────────────────────
let allDoctorsList = [];

function syncTableScrollbars() {
    requestAnimationFrame(() => {
        document.querySelectorAll('.custom-table-card .custom-table-header').forEach(header => {
            header.style.paddingRight = '0px';
        });
    });
}
window.addEventListener('resize', syncTableScrollbars);

function formatDoctorStatusBadge(status) {
    const s = String(status || '').toLowerCase().trim();
    let label = 'Available';
    let pillClass = 'status-pill-available';

    if (s === 'available') {
        label = 'Available';
        pillClass = 'status-pill-available';
    } else if (s === 'in_clinic' || s === 'in-clinic' || s === 'in clinic') {
        label = 'In Clinic';
        pillClass = 'status-pill-inclinic';
    } else if (s === 'booked' || s === 'fully booked') {
        label = 'Fully Booked';
        pillClass = 'status-pill-booked';
    } else if (s === 'leave' || s === 'on_leave' || s === 'on leave') {
        label = 'On Leave';
        pillClass = 'status-pill-leave';
    } else if (s === 'emergency') {
        label = 'Emergency';
        pillClass = 'status-pill-emergency';
    } else if (s) {
        label = s.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
        pillClass = 'status-pill-gray';
    }

    return `<span class="status-pill ${pillClass}"><span class="status-indicator"></span>${escapeHtml(label)}</span>`;
}

function renderDoctorsTable(list) {
    const tbody = document.getElementById('doctors-tbody');
    if(!tbody) return;

    if(!list || list.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:35px; color:var(--muted)">No doctors found matching filters.</td></tr>';
        syncTableScrollbars();
        return;
    }

    tbody.innerHTML = list.map(d => {
        let safeName = escapeHtml(d.name);
        let safeSpec = escapeHtml(d.specialization || 'General');
        let safeDept = escapeHtml(d.department ? d.department.toUpperCase() : 'N/A');
        let sched = (d.schedule_start && d.schedule_end) 
            ? `${escapeHtml(d.schedule_start)} - ${escapeHtml(d.schedule_end)}` 
            : '<span style="color:var(--muted)">Not Set</span>';
        let booked = parseInt(d.booked_slots, 10) || 0;
        let total = parseInt(d.total_slots, 10) || 0;
        let fillPct = total > 0 ? Math.round((booked / total) * 100) : 0;
        let safeId = parseInt(d.id, 10) || 0;

        return `
        <tr>
            <td><strong>${safeName}</strong></td>
            <td>${safeSpec}</td>
            <td><span class="dept-badge" style="font-size:0.75rem; padding:4px 8px;">${safeDept}</span></td>
            <td>${sched}</td>
            <td>
                <span style="font-size:0.85rem; color:var(--muted);">${booked}/${total} (${fillPct}%)</span>
            </td>
            <td>${formatDoctorStatusBadge(d.status)}</td>
            <td style="text-align:center;">
                <button type="button" class="btn-table-action btn-table-edit" onclick="openEditDoctorModal(${safeId})" title="Edit Doctor">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                    <span>Edit</span>
                </button>
            </td>
        </tr>
        `;
    }).join('');

    syncTableScrollbars();
}

function filterDoctorsTable() {
    const searchVal = (document.getElementById('docSearchInput')?.value || '').toLowerCase().trim();
    const deptVal = (document.getElementById('docDeptFilter')?.value || 'all').toLowerCase();
    const statusVal = (document.getElementById('docStatusFilter')?.value || 'all').toLowerCase();

    let list = allDoctorsList || [];

    // Filter by department
    if (deptVal !== 'all') {
        list = list.filter(d => (d.department || '').toLowerCase().includes(deptVal));
    }

    // Filter by status
    if (statusVal !== 'all') {
        list = list.filter(d => (d.status || '').toLowerCase() === statusVal);
    }

    // Filter by search query (name, specialization, department)
    if (searchVal) {
        list = list.filter(d => 
            (d.name || '').toLowerCase().includes(searchVal) ||
            (d.specialization || '').toLowerCase().includes(searchVal) ||
            (d.department || '').toLowerCase().includes(searchVal)
        );
    }

    renderDoctorsTable(list);
}

function resetDoctorFilters() {
    const searchEl = document.getElementById('docSearchInput');
    if (searchEl) searchEl.value = '';
    const deptEl = document.getElementById('docDeptFilter');
    if (deptEl) deptEl.value = 'all';
    const statusEl = document.getElementById('docStatusFilter');
    if (statusEl) statusEl.value = 'all';
    filterDoctorsTable();
}

async function loadDoctors() {
    try {
        const res = await fetch('/sdn/api/doctors.php?mode=manage').then(r=>r.json());
        if(res.success && res.data) {
            allDoctorsList = res.data;
            filterDoctorsTable();
        }
    } catch(e) { console.error('Failed to load doctors:', e); }
}

const DEPARTMENT_SPECIALIZATIONS = {
    general: [
        'Internal Medicine',
        'Family Medicine & General Practice',
        'Preventative Care & Routine Check-ups',
        'Chronic Disease Management',
        'Pediatric Care',
        'General Surgery & Minor Procedures'
    ],
    neurology: [
        'Clinical Neurology',
        'Headache & Migraine Management',
        'Stroke Rehabilitation',
        'Epilepsy & Seizure Disorders',
        'Movement Disorders',
        'Neuromuscular Medicine'
    ],
    psychiatry: [
        'Adult Psychiatry',
        'Child & Adolescent Psychiatry',
        'Substance Abuse & Addiction Treatment',
        'Anxiety & Depression Management',
        'Geriatric Psychiatry',
        'Clinical Neuropsychiatry'
    ]
};

function updateDoctorSpecializationOptions(selectedSpec = '') {
    const dept = (document.getElementById('docDept')?.value || 'general').toLowerCase();
    const specSelect = document.getElementById('docSpec');
    if (!specSelect) return;

    const specs = DEPARTMENT_SPECIALIZATIONS[dept] || DEPARTMENT_SPECIALIZATIONS.general;
    specSelect.innerHTML = '<option value="" disabled selected>Select authorized clinical specialization...</option>';

    specs.forEach(s => {
        const opt = document.createElement('option');
        opt.value = s;
        opt.textContent = s;
        if (selectedSpec && (s.toLowerCase() === selectedSpec.toLowerCase() || s.toLowerCase().includes(selectedSpec.toLowerCase()))) {
            opt.selected = true;
        }
        specSelect.appendChild(opt);
    });
}

function openAddDoctorModal() {
    document.getElementById('doctorModalTitle').innerText = 'Add Doctor';
    document.getElementById('editDoctorId').value = '';
    document.getElementById('docName').value = '';
    document.getElementById('docDept').value = 'general';
    updateDoctorSpecializationOptions();
    document.getElementById('docStartTime').value = '08:00';
    document.getElementById('docEndTime').value = '17:00';
    document.getElementById('docSlots').value = '20';
    document.getElementById('docStatus').value = 'available';
    const delBtn = document.getElementById('btnDeleteDoctorModal');
    if(delBtn) delBtn.style.display = 'none';
    document.getElementById('doctorModal').classList.add('show');
}

function openEditDoctorModal(id) {
    const doc = allDoctorsList.find(d => d.id == id);
    if(!doc) return;

    document.getElementById('doctorModalTitle').innerText = 'Edit Doctor: ' + doc.name;
    document.getElementById('editDoctorId').value = doc.id;
    document.getElementById('docName').value = doc.name || '';
    const deptVal = (doc.department || 'general').toLowerCase();
    document.getElementById('docDept').value = deptVal;
    updateDoctorSpecializationOptions(doc.specialization || '');
    document.getElementById('docStartTime').value = doc.schedule_start || '';
    document.getElementById('docEndTime').value = doc.schedule_end || '';
    document.getElementById('docSlots').value = doc.total_slots || 20;
    document.getElementById('docStatus').value = doc.status || 'available';
    const delBtn = document.getElementById('btnDeleteDoctorModal');
    if(delBtn) delBtn.style.display = 'inline-block';
    document.getElementById('doctorModal').classList.add('show');
}

function handleModalDeleteDoctor() {
    const id = document.getElementById('editDoctorId').value;
    if(id) {
        closeDoctorModal();
        deleteDoctor(id);
    }
}

function closeDoctorModal() {
    const modal = document.getElementById('doctorModal');
    if(modal) modal.classList.remove('show');
}

async function submitDoctorForm() {
    const id = document.getElementById('editDoctorId').value;
    const name = document.getElementById('docName').value.trim();
    const specialization = document.getElementById('docSpec').value.trim();
    const department = document.getElementById('docDept').value;
    const schedule_start = document.getElementById('docStartTime').value;
    const schedule_end = document.getElementById('docEndTime').value;
    const total_slots = parseInt(document.getElementById('docSlots').value, 10) || 20;
    const status = document.getElementById('docStatus').value;

    if(!name || !specialization || !department) {
        return showToast('Doctor Name, Specialization, and Department are required.', 'error');
    }

    if(total_slots < 1 || total_slots > 100) {
        return showToast('Total slots must be between 1 and 100.', 'error');
    }

    if(schedule_start && schedule_end && schedule_start >= schedule_end) {
        return showToast('Schedule Start Time must be earlier than End Time.', 'error');
    }

    const isEdit = !!id;
    const payload = isEdit ? {
        action: 'update',
        id: id,
        name,
        specialization,
        department,
        schedule_start,
        schedule_end,
        total_slots,
        status
    } : {
        action: 'create',
        name,
        specialization,
        department,
        schedule_start,
        schedule_end,
        total_slots
    };

    try {
        const res = await fetch('/sdn/api/doctors.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        }).then(r=>r.json());

        if(res.success) {
            closeDoctorModal();
            loadDoctors();
            showToast(isEdit ? 'Doctor updated successfully!' : 'Doctor added successfully!');
        } else {
            showToast(res.message || 'Operation failed', 'error');
        }
    } catch(e) {
        console.error(e);
        showToast('Error saving doctor.', 'error');
    }
}

async function deleteDoctor(doctorId) {
    const doc = allDoctorsList.find(d => d.id == doctorId);
    const docName = doc ? doc.name : 'this doctor';
    customConfirm('Delete Doctor', `Are you sure you want to permanently remove ${docName}?`, async () => {
        try {
            const res = await fetch(`/sdn/api/doctors.php?doctor_id=${doctorId}`, {
                method: 'DELETE'
            }).then(r=>r.json());

            if(res.success) {
                loadDoctors();
                showToast('Doctor deleted successfully.');
            } else {
                showToast(res.message || 'Failed to delete doctor', 'error');
            }
        } catch(e) {
            console.error(e);
            showToast('Error deleting doctor.', 'error');
        }
    });
}

let cachedPMIPatients = [];

function filterPMITable() {
    const tbody = document.getElementById('pmi-tbody');
    if (!tbody) return;

    const statusFilter = document.getElementById('pmiStatusFilter')?.value || 'all';
    const searchQuery = (document.getElementById('pmiSearchInput')?.value || '').toLowerCase().trim();

    let list = cachedPMIPatients || [];

    // 1. Filter by status
    if (statusFilter !== 'all') {
        if (statusFilter === 'inquiry') {
            list = list.filter(p => p.inquiry_only == 1 || p.inquiry_only === true || p.inquiry_only === '1');
        } else if (statusFilter === 'no-show') {
            list = list.filter(p => p.status === 'no-show' || p.status === 'rejected');
        } else if (statusFilter === 'for_approval') {
            list = list.filter(p => p.status === 'for_approval' || p.status === 'for_processing' || p.status === 'pending');
        } else {
            list = list.filter(p => (p.status || '').toLowerCase() === statusFilter.toLowerCase());
        }
    }

    // 2. Filter by patient name or patient ID
    if (searchQuery) {
        list = list.filter(p => 
            ((p.full_name || '').toLowerCase().includes(searchQuery)) ||
            ((p.patient_id || '').toLowerCase().includes(searchQuery))
        );
    }

    if (list.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:35px; color:var(--muted);">No matching patients found.</td></tr>';
        return;
    }

    tbody.innerHTML = list.map(p => {
        let b = 'badge-yellow';
        let statusLabel = (p.status || '').replace(/_/g, ' ').toUpperCase();
        if (p.status === 'approved') b = 'badge-green';
        else if (p.status === 'arrived') b = 'badge-teal';
        else if (p.status === 'ongoing') b = 'badge-pink';
        else if (p.status === 'completed') b = 'badge-blue';
        else if (p.status === 'no-show' || p.status === 'rejected') b = 'badge-red';
        else if (p.status === 'transferred') b = 'badge-purple';
        else if (p.status === 'for_approval' || p.status === 'for_processing' || p.status === 'pending') {
            b = 'badge-yellow';
            statusLabel = 'FOR APPROVAL';
        }

        const isInquiry = p.inquiry_only == 1 || p.inquiry_only === true || p.inquiry_only === '1';
        const inquiryTag = isInquiry
            ? `<span style="display:inline-block; margin-left:6px; padding:2px 7px; background:#fef3c7; color:#92400e; border:1px solid #fcd34d; border-radius:999px; font-size:0.68rem; font-weight:700; text-transform:uppercase; vertical-align:middle;">Inquiry</span>`
            : '';

        let safeId = escapeHtml(p.patient_id);
        let safeName = escapeHtml(p.full_name);
        let safeDept = escapeHtml(p.department);
        let safeStatus = escapeHtml(statusLabel);

        return `
        <tr class="clickable-row" onclick="openPatientDetailsModal('${safeId}')" title="Click to view full patient profile">
            <td><small style="font-weight:600; color:var(--deep-green);">${safeId}</small></td>
            <td><strong>${safeName}</strong>${inquiryTag}</td>
            <td>${safeDept}</td>
            <td><span class="badge ${b}">${safeStatus}</span></td>
            <td>
                <button type="button" class="btn-table-action btn-table-details" onclick="event.stopPropagation(); openPatientDetailsModal('${safeId}')" title="View Details">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    <span>View</span>
                </button>
            </td>
        </tr>
        `;
    }).join('');

    syncTableScrollbars();
}

function resetPMIFilter() {
    const filterEl = document.getElementById('pmi-filter');
    if (filterEl) filterEl.value = 'all';
    const statusEl = document.getElementById('pmiStatusFilter');
    if (statusEl) statusEl.value = 'all';
    const searchEl = document.getElementById('pmiSearchInput');
    if (searchEl) searchEl.value = '';
    loadPMI();
}

async function loadPMI() {
    try {
        const filterEl = document.getElementById('pmi-filter');
        const filterVal = filterEl ? filterEl.value : 'all';

        const res = await fetch(`/sdn/api/patients.php?filter=${filterVal}`).then(r=>r.json());
        if(res.success && res.data) {
            cachedPMIPatients = res.data;
            filterPMITable();

            const elApp = document.getElementById('pmi-stat-appointments');
            const elSlots = document.getElementById('pmi-stat-slots');
            const elPts = document.getElementById('pmi-stat-patients');
            if(elApp) elApp.innerText = cachedPMIPatients.length;
            if(elSlots) elSlots.innerText = "-";
            const uniquePatients = new Set(cachedPMIPatients.map(p => p.full_name || p.patient_id)).size;
            if(elPts) elPts.innerText = uniquePatients;
        }
    } catch(e) { console.error(e); }
}

async function openPatientDetailsModal(patient_id) {
    const modal = document.getElementById('patientDetailsModal');
    const body = document.getElementById('patientDetailsBody');
    const actions = document.getElementById('patientDetailsActions');
    if (!modal || !body) return;

    modal.classList.add('show');
    body.innerHTML = '<p style="text-align:center; padding:20px; color:#64748b;">Loading registration details...</p>';
    if (actions) actions.innerHTML = '';

    try {
        const res = await fetch(`/sdn/api/patients.php?id=${encodeURIComponent(patient_id)}`).then(r => r.json());
        if (!res.success || !res.data) {
            body.innerHTML = '<p style="text-align:center; color:#ef4444;">Failed to load patient details.</p>';
            return;
        }

        const p = res.data;
        const phone = p.contact_number || 'None';
        const isPending = (p.status === 'pending' || p.status === 'for_approval' || p.status === 'for_processing');
        const showVerificationProtocol = isPending && p.status !== 'completed';
        const isInquiry = p.inquiry_only == 1 || p.inquiry_only === true || p.inquiry_only === '1';

        const modalTitle = document.querySelector('#patientDetailsModal .modal-header h2');
        const modalSub = document.querySelector('#patientDetailsModal .modal-header p');
        if (modalTitle && modalSub) {
            if (isInquiry) {
                if (p.status === 'completed') {
                    modalTitle.innerText = 'Patient Inquiry — Resolved';
                    modalSub.innerText = 'Review patient inquiry details, contact logs, and resolution notes.';
                } else {
                    modalTitle.innerText = 'Patient General Inquiry';
                    modalSub.innerText = 'Review inquiry details and follow up with inquirer via phone.';
                }
            } else {
                if (p.status === 'completed') {
                    modalTitle.innerText = 'Patient Registration & Consultation Details';
                    modalSub.innerText = 'Review patient profile, clinical diagnosis, treatment plan, and consultation history.';
                } else {
                    modalTitle.innerText = 'Patient Registration Verification';
                    modalSub.innerText = 'Review full registration information and verify patient authenticity via phone call.';
                }
            }
        }

        if (isInquiry) {
            body.innerHTML = `
                ${showVerificationProtocol ? `
                <div class="verification-callout" style="background:#fffbeb; border-color:#fde68a;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                    <div>
                        <strong>Inquiry Follow-up Protocol:</strong> Contact inquirer at <strong>${escapeHtml(phone)}</strong> to address their general inquiry or guide them to appropriate clinic services.
                    </div>
                </div>` : ''}

                <div class="patient-verify-grid">
                    <div class="patient-verify-card">
                        <h3>
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--dark-green);"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            <span>Inquirer Information</span>
                        </h3>
                        <div class="verify-item">
                            <label>Inquiry Reference ID</label>
                            <span><strong>${escapeHtml(p.patient_id)}</strong> ${p.hrn ? `&bull; HRN: <strong>${escapeHtml(p.hrn)}</strong>` : ''}</span>
                        </div>
                        <div class="verify-item">
                            <label>Inquirer Name</label>
                            <span>${escapeHtml(p.full_name)}</span>
                        </div>
                        <div class="verify-item">
                            <label>Contact Number</label>
                            <div>
                                ${phone !== 'None' ? `<a href="tel:${escapeHtml(phone)}" class="call-badge"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg> Call: ${escapeHtml(phone)}</a>` : '<span>None provided</span>'}
                            </div>
                        </div>
                        <div class="verify-item">
                            <label>Email Address</label>
                            <span>${escapeHtml(p.email || 'None')}</span>
                        </div>
                        <div class="verify-item">
                            <label>Gender / Age</label>
                            <span>${escapeHtml(p.gender || 'N/A')} &bull; ${escapeHtml(p.age || 'N/A')} yrs old (DOB: ${escapeHtml(p.birthdate || 'N/A')})</span>
                        </div>
                        <div class="verify-item">
                            <label>Complete Address</label>
                            <span>${escapeHtml(p.address || 'None provided')}</span>
                        </div>
                    </div>

                    <div class="patient-verify-card">
                        <h3>
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--dark-green);"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                            <span>Inquiry &amp; Service Details</span>
                        </h3>
                        <div class="verify-item">
                            <label>Department Target</label>
                            <span><strong>${escapeHtml(p.department || 'N/A')}</strong></span>
                        </div>
                        <div class="verify-item">
                            <label>Classification</label>
                            <span class="badge" style="background:#fef3c7; color:#92400e; font-weight:600; display:inline-block; margin-top:2px; border:1px solid #fde68a;">Patient General Inquiry</span>
                        </div>
                        <div class="verify-item">
                            <label>Mode of Inquiry</label>
                            <span><strong>${escapeHtml(p.mode_of_inquiry || 'Online Portal')}</strong></span>
                        </div>
                        <div class="verify-item">
                            <label>Submitted Date</label>
                            <span>${escapeHtml(p.created_at || 'Today')}</span>
                        </div>
                        <div class="verify-item">
                            <label>Clinic Schedule Requirement</label>
                            <span style="color:#059669; font-weight:600;">No clinic slot reserved (Inquiry Only)</span>
                        </div>
                    </div>
                </div>

                <div class="patient-verify-card" style="margin-bottom:0;">
                    <h3>
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--dark-green);"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                        <span>Inquiry Concern &amp; Detailed Message</span>
                    </h3>
                    <div class="verify-item" style="margin-bottom:12px;">
                        <label>Inquiry Subject / Concern</label>
                        <p style="background:#ffffff; border:1px solid #e2e8f0; padding:10px 14px; border-radius:8px; font-weight:600; color:#0f2e1b;">
                            ${escapeHtml(p.chief_complaints || 'General Inquiry')}
                        </p>
                    </div>
                    ${(p.illness_description || p.recommendation_remarks) ? `
                    <div class="verify-item">
                        <label>Inquiry Message / Additional Details</label>
                        <p style="background:#ffffff; border:1px solid #e2e8f0; padding:10px 14px; border-radius:8px; font-size:0.9rem; line-height:1.5;">
                            ${escapeHtml(p.illness_description || p.recommendation_remarks)}
                        </p>
                    </div>` : ''}
                </div>

                ${p.latest_progress_note ? `
                <div class="patient-verify-card" style="margin-top:16px; margin-bottom:0;">
                    <h3>
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--dark-green);"><polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                        <span>Latest Update / Note (by ${escapeHtml(p.latest_progress_note.noted_by || 'Staff')})</span>
                    </h3>
                    <p style="background:#ffffff; border:1px solid #e2e8f0; padding:8px 12px; border-radius:8px; font-size:0.88rem;">
                        ${escapeHtml(p.latest_progress_note.note)}
                    </p>
                </div>` : ''}
            `;

            if (actions) {
                if (isPending) {
                    actions.innerHTML = `
                        <div class="patient-modal-status-col">
                            <span class="badge badge-yellow">Status: FOR REVIEW</span>
                        </div>
                        <div class="patient-modal-btn-group">
                            <button type="button" class="btn btn-outline" onclick="closePatientDetailsModal()">Close</button>
                            <button type="button" class="btn-verify-action btn-verify-noshow" onclick="promptPatientVerification('${p.patient_id}', 'no-show', '${escapeHtml(p.full_name)}')">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                <span>Mark as Invalid</span>
                            </button>
                            <button type="button" class="btn-verify-action btn-verify-accept" onclick="promptPatientVerification('${p.patient_id}', 'approve', '${escapeHtml(p.full_name)}')">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                <span>Accept Inquiry</span>
                            </button>
                            <button type="button" class="btn-verify-action" style="background:#059669; color:#fff;" onclick="promptResolveInquiry('${p.patient_id}', '${escapeHtml(p.full_name)}')">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                <span>Resolve Inquiry</span>
                            </button>
                        </div>
                    `;
                } else if (p.status === 'approved') {
                    actions.innerHTML = `
                        <div class="patient-modal-status-col">
                            <span class="badge badge-green">Status: UNDER REVIEW / ACCEPTED</span>
                        </div>
                        <div class="patient-modal-btn-group">
                            <button type="button" class="btn btn-outline" onclick="closePatientDetailsModal()">Close</button>
                            <button type="button" class="btn-verify-action btn-verify-noshow" onclick="promptPatientVerification('${p.patient_id}', 'no-show', '${escapeHtml(p.full_name)}')">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                <span>Mark as Invalid</span>
                            </button>
                            <button type="button" class="btn-verify-action" style="background:#059669; color:#fff;" onclick="promptResolveInquiry('${p.patient_id}', '${escapeHtml(p.full_name)}')">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                <span>Resolve Inquiry</span>
                            </button>
                        </div>
                    `;
                } else {
                    let statusBadge = (p.status === 'no-show' || p.status === 'rejected') ? 'badge-red' : (p.status === 'completed' ? 'badge-blue' : 'badge-purple');
                    let statusText = (p.status === 'completed') ? 'COMPLETED / RESOLVED' : (p.status === 'no-show' || p.status === 'rejected' ? 'INVALID / REJECTED' : (p.status || '').toUpperCase());
                    actions.innerHTML = `
                        <div class="patient-modal-status-col">
                            <span class="badge ${statusBadge}">Status: ${statusText}</span>
                        </div>
                        <div class="patient-modal-btn-group">
                            <button type="button" class="btn btn-outline" onclick="closePatientDetailsModal()">Close</button>
                        </div>
                    `;
                }
            }
            return;
        }

        body.innerHTML = `
            ${showVerificationProtocol ? `
            <div class="verification-callout">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                <div>
                    <strong>Verification Protocol:</strong> Call patient at <strong>${escapeHtml(phone)}</strong> to confirm appointment validity. If unreachable, fake, or unverified, mark as No-Show.
                </div>
            </div>` : ''}

            <div class="patient-verify-grid">
                <div class="patient-verify-card">
                    <h3>
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--dark-green);"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        <span>Personal Information</span>
                    </h3>
                    <div class="verify-item">
                        <label>Patient ID</label>
                        <span><strong>${escapeHtml(p.patient_id)}</strong> ${p.hrn ? `&bull; HRN: <strong>${escapeHtml(p.hrn)}</strong>` : ''}</span>
                    </div>
                    <div class="verify-item">
                        <label>Full Name</label>
                        <span>${escapeHtml(p.full_name)}</span>
                    </div>
                    <div class="verify-item">
                        <label>Contact Number</label>
                        <div>
                            ${phone !== 'None' ? `<a href="tel:${escapeHtml(phone)}" class="call-badge"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg> Call: ${escapeHtml(phone)}</a>` : '<span>None provided</span>'}
                        </div>
                    </div>
                    <div class="verify-item">
                        <label>Email Address</label>
                        <span>${escapeHtml(p.email || 'None')}</span>
                    </div>
                    <div class="verify-item">
                        <label>Gender / Age</label>
                        <span>${escapeHtml(p.gender || 'N/A')} &bull; ${escapeHtml(p.age || 'N/A')} yrs old (DOB: ${escapeHtml(p.birthdate || 'N/A')})</span>
                    </div>
                    <div class="verify-item">
                        <label>Complete Address</label>
                        <span>${escapeHtml(p.address || 'None')}</span>
                    </div>
                    <div class="verify-item">
                        <label>Companion</label>
                        <span>${p.with_companion ? 'Accompanied' : 'Alone / Unaccompanied'}</span>
                    </div>
                </div>

                <div class="patient-verify-card">
                    <h3>
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--dark-green);"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        <span>Appointment & Consultation</span>
                    </h3>
                    <div class="verify-item">
                        <label>Department Assigned</label>
                        <span><strong>${escapeHtml(p.department || 'N/A')}</strong></span>
                    </div>
                    ${p.referral_source ? `
                    <div class="verify-item">
                        <label>Referral / Transferred From</label>
                        <span style="color:#6b21a8; font-weight:600;">${escapeHtml(p.referral_source)}</span>
                    </div>` : ''}
                    <div class="verify-item">
                        <label>Appointment Date & Time</label>
                        <span>${escapeHtml(p.appointment_date || 'N/A')} ${p.scheduled_time ? '(' + escapeHtml(p.scheduled_time) + ')' : ''}</span>
                    </div>
                    <div class="verify-item">
                        <label>Patient Type / Booking</label>
                        <span>${escapeHtml(p.patient_type || 'N/A')} &bull; ${p.unscheduled_walkin ? 'Walk-in' : 'Online Booker'}</span>
                    </div>
                    <div class="verify-item">
                        <label>Consultation Nature & Mode</label>
                        <span>${escapeHtml(p.consultation_nature || 'General')} (${escapeHtml(p.consultation_mode || 'Face-to-Face')})</span>
                    </div>
                    <div class="verify-item">
                        <label>Assigned Doctor</label>
                        <span>${escapeHtml(p.assigned_doctor || 'None assigned yet')}</span>
                    </div>
                </div>
            </div>

            <div class="patient-verify-card" style="margin-bottom:0;">
                <h3>
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--dark-green);"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    <span>Clinical Complaints & Symptoms</span>
                </h3>
                <div class="verify-item" style="margin-bottom:12px;">
                    <label>Chief Complaints</label>
                    <p style="background:#ffffff; border:1px solid #e2e8f0; padding:10px 14px; border-radius:8px; font-weight:500;">
                        ${escapeHtml(p.chief_complaints || 'None listed')}
                    </p>
                </div>
                ${p.illness_description ? `
                <div class="verify-item" style="margin-bottom:12px;">
                    <label>History of Present Illness / Details</label>
                    <p style="background:#ffffff; border:1px solid #e2e8f0; padding:10px 14px; border-radius:8px; font-size:0.88rem;">
                        ${escapeHtml(p.illness_description)}
                    </p>
                </div>` : ''}
                ${p.recommendation_remarks ? `
                <div class="verify-item">
                    <label>Recommendation Remarks</label>
                    <p style="background:#ffffff; border:1px solid #e2e8f0; padding:10px 14px; border-radius:8px; font-size:0.88rem;">
                        ${escapeHtml(p.recommendation_remarks)}
                    </p>
                </div>` : ''}
            </div>

            ${(p.medical_history_diagnosis || p.medical_history_treatment || p.medical_history_prescription || p.active_medications || p.latest_progress_note) ? `
            <div class="patient-verify-card" style="margin-top:16px; margin-bottom:0;">
                <h3>
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--dark-green);"><path d="M22 12h-4l-3 9L9 3l-3 9H2"></path></svg>
                    <span>Clinical History & Medical Notes</span>
                </h3>
                ${p.medical_history_diagnosis ? `
                <div class="verify-item" style="margin-bottom:10px;">
                    <label>Diagnosis</label>
                    <p style="background:#ffffff; border:1px solid #e2e8f0; padding:8px 12px; border-radius:8px; font-weight:600; color:#0f2e1b;">
                        ${escapeHtml(p.medical_history_diagnosis)}
                    </p>
                </div>` : ''}
                ${p.medical_history_treatment ? `
                <div class="verify-item" style="margin-bottom:10px;">
                    <label>Treatment Plan</label>
                    <p style="background:#ffffff; border:1px solid #e2e8f0; padding:8px 12px; border-radius:8px; font-size:0.88rem;">
                        ${escapeHtml(p.medical_history_treatment)}
                    </p>
                </div>` : ''}
                ${p.medical_history_prescription ? `
                <div class="verify-item" style="margin-bottom:10px;">
                    <label>Prescriptions / Medications</label>
                    <p style="background:#ffffff; border:1px solid #e2e8f0; padding:8px 12px; border-radius:8px; font-size:0.88rem;">
                        ${escapeHtml(p.medical_history_prescription)}
                    </p>
                </div>` : ''}
                ${p.next_visit_date ? `
                <div class="verify-item" style="margin-bottom:10px;">
                    <label>Next Follow-Up Visit Date</label>
                    <span><strong>${escapeHtml(p.next_visit_date)}</strong></span>
                </div>` : ''}
                ${p.latest_progress_note ? `
                <div class="verify-item">
                    <label>Latest Progress Note / Vitals (by ${escapeHtml(p.latest_progress_note.noted_by || 'Staff')})</label>
                    <p style="background:#ffffff; border:1px solid #e2e8f0; padding:8px 12px; border-radius:8px; font-size:0.88rem;">
                        ${escapeHtml(p.latest_progress_note.note)}
                    </p>
                </div>` : ''}
            </div>` : ''}
        `;

        if (actions) {
            const isWalkin = !!(p.unscheduled_walkin || p.appointment_type === 'walk-in');
            const noShowBtnModal = isWalkin ? '' : `
                <button type="button" class="btn-verify-action btn-verify-noshow" onclick="promptPatientVerification('${p.patient_id}', 'no-show', '${escapeHtml(p.full_name)}')">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    <span>Mark as No-Show</span>
                </button>
            `;

            if (isPending) {
                actions.innerHTML = `
                    <div class="patient-modal-status-col">
                        <span class="badge badge-yellow">Current Status: FOR APPROVAL</span>
                    </div>
                    <div class="patient-modal-btn-group">
                        <button type="button" class="btn btn-outline" onclick="closePatientDetailsModal()">Close</button>
                        ${noShowBtnModal}
                        <button type="button" class="btn-verify-action btn-verify-accept" onclick="promptPatientVerification('${p.patient_id}', 'approve', '${escapeHtml(p.full_name)}')">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <span>Accept Patient</span>
                        </button>
                    </div>
                `;
            } else if (p.status === 'approved') {
                actions.innerHTML = `
                    <div class="patient-modal-status-col">
                        <span class="badge badge-green">Current Status: APPROVED</span>
                    </div>
                    <div class="patient-modal-btn-group">
                        <button type="button" class="btn btn-outline" onclick="closePatientDetailsModal()">Close</button>
                        ${noShowBtnModal}
                        <button type="button" class="btn-verify-action btn-table-arrived" onclick="closePatientDetailsModal(); promptPatientVerification('${p.patient_id}', 'arrived', '${escapeHtml(p.full_name)}')">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><polyline points="17 11 19 13 23 9"></polyline></svg>
                            <span>Check In</span>
                        </button>
                    </div>
                `;
            } else if (p.status === 'arrived') {
                actions.innerHTML = `
                    <div class="patient-modal-status-col">
                        <span class="badge badge-teal">Current Status: IN TRIAGE</span>
                    </div>
                    <div class="patient-modal-btn-group">
                        <button type="button" class="btn btn-outline" onclick="closePatientDetailsModal()">Close</button>
                        ${noShowBtnModal}
                        <button type="button" class="btn-verify-action btn-table-ongoing" onclick="closePatientDetailsModal(); promptPatientVerification('${p.patient_id}', 'ongoing', '${escapeHtml(p.full_name)}')">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                            <span>Start Consultation</span>
                        </button>
                    </div>
                `;
            } else if (p.status === 'ongoing') {
                actions.innerHTML = `
                    <div class="patient-modal-status-col">
                        <span class="badge badge-pink">Current Status: ONGOING CONSULTATION</span>
                    </div>
                    <div class="patient-modal-btn-group">
                        <button type="button" class="btn btn-outline" onclick="closePatientDetailsModal()">Close</button>
                        <button type="button" class="btn-verify-action btn-verify-accept" style="background:#2563eb; color:#fff;" onclick="closePatientDetailsModal(); promptDoctorSignOff('${p.patient_id}', '${escapeHtml(p.full_name)}')">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                            <span>Sign Off & Complete</span>
                        </button>
                    </div>
                `;
            } else {
                let statusBadge = (p.status === 'no-show' || p.status === 'rejected') ? 'badge-red' : (p.status === 'transferred' ? 'badge-purple' : 'badge-blue');
                actions.innerHTML = `
                    <div class="patient-modal-status-col">
                        <span class="badge ${statusBadge}">Current Status: ${escapeHtml((p.status || '').replace(/_/g, ' ').toUpperCase())}</span>
                    </div>
                    <div class="patient-modal-btn-group">
                        <button type="button" class="btn btn-outline" onclick="closePatientDetailsModal()">Close</button>
                    </div>
                `;
            }
        }

    } catch(err) {
        console.error(err);
        body.innerHTML = '<p style="text-align:center; color:#ef4444;">Error fetching patient details.</p>';
    }
}

function closePatientDetailsModal() {
    const modal = document.getElementById('patientDetailsModal');
    if (modal) modal.classList.remove('show');
}

function showStaffConfirmDialog({
    title,
    tag,
    message,
    type = 'accept', // 'accept' | 'noshow' | 'completed' | 'arrived' | 'ongoing'
    patientName = '',
    patientId = '',
    patientSub = '',
    confirmText,
    onConfirm
}) {
    let overlay = document.getElementById('staffConfirmOverlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'staffConfirmOverlay';
        overlay.className = 'staff-confirm-overlay';
        document.body.appendChild(overlay);
    }

    let iconSvg = '';
    let btnIconSvg = '';
    let defaultTag = '';
    let defaultConfirmText = '';

    if (type === 'noshow') {
        defaultTag = 'VERIFICATION & ATTENDANCE';
        defaultConfirmText = 'Mark as No-Show';
        iconSvg = `<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>`;
        btnIconSvg = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>`;
    } else if (type === 'arrived') {
        defaultTag = 'FACILITY ARRIVAL & TRIAGE';
        defaultConfirmText = 'Check-In Patient';
        iconSvg = `<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><polyline points="17 11 19 13 23 9"></polyline></svg>`;
        btnIconSvg = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>`;
    } else if (type === 'ongoing') {
        defaultTag = 'START MEDICAL CONSULTATION';
        defaultConfirmText = 'Start Consultation';
        iconSvg = `<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polygon points="10 8 16 12 10 16 10 8"></polygon></svg>`;
        btnIconSvg = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>`;
    } else if (type === 'completed') {
        defaultTag = 'CONSULTATION COMPLETE';
        defaultConfirmText = 'Mark as Completed';
        iconSvg = `<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>`;
        btnIconSvg = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>`;
    } else { // accept / approve
        defaultTag = 'PATIENT ADMISSION';
        defaultConfirmText = 'Accept Patient';
        iconSvg = `<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>`;
        btnIconSvg = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>`;
    }

    const patientCardHtml = (patientName || patientId) ? `
        <div class="staff-confirm-patient-card">
            <div class="staff-confirm-patient-avatar">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            </div>
            <div class="staff-confirm-patient-meta">
                <div class="staff-confirm-patient-name">${escapeHtml(patientName || 'Patient Record')}</div>
                <div class="staff-confirm-patient-sub">${escapeHtml(patientSub || (patientId ? `ID: ${patientId}` : 'Patient Record'))}</div>
            </div>
        </div>
    ` : '';

    overlay.innerHTML = `
        <div class="staff-confirm-box" role="dialog" aria-modal="true" aria-labelledby="staffConfirmTitle">
            <div class="staff-confirm-banner ${type}"></div>
            <button type="button" class="staff-confirm-close" id="staffConfirmCloseBtn" aria-label="Close dialog">&times;</button>
            <div class="staff-confirm-body">
                <div class="staff-confirm-header-row">
                    <div class="staff-confirm-icon-circle ${type}">
                        ${iconSvg}
                    </div>
                    <div class="staff-confirm-heading">
                        <span class="staff-confirm-tag ${type}">${escapeHtml(tag || defaultTag)}</span>
                        <h3 class="staff-confirm-title" id="staffConfirmTitle">${escapeHtml(title)}</h3>
                    </div>
                </div>
                ${patientCardHtml}
                <p class="staff-confirm-message">${escapeHtml(message)}</p>
            </div>
            <div class="staff-confirm-actions">
                <button type="button" class="staff-confirm-btn-cancel" id="staffConfirmCancel">Cancel</button>
                <button type="button" class="staff-confirm-btn-submit ${type}" id="staffConfirmOk">
                    ${btnIconSvg}
                    <span>${escapeHtml(confirmText || defaultConfirmText)}</span>
                </button>
            </div>
        </div>
    `;

    const closeDialog = () => {
        overlay.classList.remove('show');
        document.removeEventListener('keydown', handleKeyDown);
    };

    const handleKeyDown = (e) => {
        if (e.key === 'Escape') closeDialog();
    };

    document.addEventListener('keydown', handleKeyDown);

    overlay.querySelector('#staffConfirmCancel').addEventListener('click', closeDialog);
    overlay.querySelector('#staffConfirmCloseBtn').addEventListener('click', closeDialog);
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) closeDialog();
    });

    overlay.querySelector('#staffConfirmOk').addEventListener('click', () => {
        closeDialog();
        if (typeof onConfirm === 'function') onConfirm();
    });

    requestAnimationFrame(() => {
        overlay.classList.add('show');
        const okBtn = overlay.querySelector('#staffConfirmOk');
        if (okBtn) okBtn.focus();
    });
}

function promptPatientVerification(patient_id, action, patientName) {
    const patient = (cachedPMIPatients || []).find(p => String(p.patient_id) === String(patient_id));
    const pName = patient ? patient.full_name : (patientName || `Patient #${patient_id}`);
    const pType = patient ? (patient.unscheduled_walkin ? 'Walk-in' : (patient.appointment_type || 'Online Booker')) : 'Patient Record';
    const pDoctor = (patient && patient.assigned_doctor && patient.assigned_doctor !== 'None') ? ` • Dr. ${patient.assigned_doctor}` : '';
    const pSub = `ID: ${patient_id} • ${pType}${pDoctor}`;

    if (action === 'no-show') {
        showStaffConfirmDialog({
            title: 'Mark Patient as No-Show?',
            tag: 'ATTENDANCE / VERIFICATION',
            message: `Are you sure you want to mark ${pName} as No-Show? Use this if the registration is unverified, the patient cannot be reached by phone, or is identified as fake/trolling.`,
            type: 'noshow',
            patientName: pName,
            patientId: patient_id,
            patientSub: pSub,
            confirmText: 'Mark as No-Show',
            onConfirm: () => executeVerificationStatus(patient_id, 'no-show')
        });
    } else if (action === 'arrived') {
        showStaffConfirmDialog({
            title: 'Check-In Patient at Facility?',
            tag: 'FACILITY ARRIVAL & TRIAGE',
            message: `Confirm that ${pName} has arrived at MMWGH reception/triage. Their status will change to Arrived, queuing them for vital signs and consultation.`,
            type: 'arrived',
            patientName: pName,
            patientId: patient_id,
            patientSub: pSub,
            confirmText: 'Check-In Patient',
            onConfirm: () => executeVerificationStatus(patient_id, 'arrived')
        });
    } else if (action === 'ongoing') {
        showStaffConfirmDialog({
            title: 'Start Consultation Session?',
            tag: 'CLINICAL CONSULTATION',
            message: `Confirm that ${pName} is entering the consultation room with the physician. Status will update to Ongoing.`,
            type: 'ongoing',
            patientName: pName,
            patientId: patient_id,
            patientSub: pSub,
            confirmText: 'Begin Consultation',
            onConfirm: () => executeVerificationStatus(patient_id, 'ongoing')
        });
    } else if (action === 'completed') {
        promptDoctorSignOff(patient_id, pName);
    } else { // approve
        showStaffConfirmDialog({
            title: 'Accept & Verify Patient?',
            tag: 'PATIENT ADMISSION',
            message: `Confirm that patient ${pName} has been verified and contacted. They will be marked as Approved and admitted into the active queue.`,
            type: 'accept',
            patientName: pName,
            patientId: patient_id,
            patientSub: pSub,
            confirmText: 'Accept Patient',
            onConfirm: () => executeVerificationStatus(patient_id, 'approve')
        });
    }
}

function promptDoctorSignOff(patient_id, patientName) {
    const patient = (cachedPMIPatients || []).find(p => String(p.patient_id) === String(patient_id));
    const pName = patient ? patient.full_name : (patientName || `Patient #${patient_id}`);
    const pDoctor = (patient && patient.assigned_doctor && patient.assigned_doctor !== 'None') ? ` • Dr. ${patient.assigned_doctor}` : '';
    const pSub = `ID: ${patient_id} • In Consultation${pDoctor}`;

    let overlay = document.getElementById('staffConfirmOverlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'staffConfirmOverlay';
        overlay.className = 'staff-confirm-overlay';
        document.body.appendChild(overlay);
    }

    overlay.innerHTML = `
        <div class="staff-confirm-box" style="max-width:540px;" role="dialog" aria-modal="true" aria-labelledby="signoffTitleAdmin">
            <div class="staff-confirm-banner completed"></div>
            <button type="button" class="staff-confirm-close" id="signoffCloseBtnAdmin" aria-label="Close dialog">&times;</button>
            <div class="staff-confirm-body">
                <div class="staff-confirm-header-row">
                    <div class="staff-confirm-icon-circle completed">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    </div>
                    <div class="staff-confirm-heading">
                        <span class="staff-confirm-tag completed">OFFICIAL DOCTOR SIGN-OFF</span>
                        <h3 class="staff-confirm-title" id="signoffTitleAdmin">Complete Consultation Visit</h3>
                    </div>
                </div>
                <div class="staff-confirm-patient-card">
                    <div class="staff-confirm-patient-avatar">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    </div>
                    <div class="staff-confirm-patient-meta">
                        <div class="staff-confirm-patient-name">${escapeHtml(pName)}</div>
                        <div class="staff-confirm-patient-sub">${escapeHtml(pSub)}</div>
                    </div>
                </div>
                <p class="staff-confirm-message" style="margin-bottom:12px;">Under MMWGH regulations, an appointment transitions to <strong>Completed</strong> only once the medical service is officially signed off by the attending physician.</p>
                
                <div class="signoff-field">
                    <label for="adminSignoffDiag">Clinical Diagnosis *</label>
                    <input type="text" id="adminSignoffDiag" placeholder="e.g. Acute upper respiratory tract infection, resolved" value="${escapeHtml(patient?.medical_history_diagnosis || '')}">
                </div>
                <div class="signoff-field">
                    <label for="adminSignoffTreat">Treatment Plan & Recommendations</label>
                    <textarea id="adminSignoffTreat" rows="2" placeholder="e.g. Oral hydration, voice rest, warm saline gargle...">${escapeHtml(patient?.medical_history_treatment || '')}</textarea>
                </div>
                <div class="signoff-field">
                    <label for="adminSignoffRx">Prescriptions / Rx</label>
                    <textarea id="adminSignoffRx" rows="2" placeholder="e.g. Paracetamol 500mg TID PRN for fever...">${escapeHtml(patient?.medical_history_prescription || '')}</textarea>
                </div>
            </div>
            <div class="staff-confirm-actions">
                <button type="button" class="staff-confirm-btn-cancel" id="adminSignoffCancel">Cancel</button>
                <button type="button" class="staff-confirm-btn-submit completed" id="adminSignoffSubmit">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    <span>Sign Off & Complete Visit</span>
                </button>
            </div>
        </div>
    `;

    const closeDialog = () => {
        overlay.classList.remove('show');
    };

    overlay.querySelector('#adminSignoffCancel').addEventListener('click', closeDialog);
    overlay.querySelector('#signoffCloseBtnAdmin').addEventListener('click', closeDialog);

    overlay.querySelector('#adminSignoffSubmit').addEventListener('click', () => {
        const diag = overlay.querySelector('#adminSignoffDiag').value.trim();
        const treatment = overlay.querySelector('#adminSignoffTreat').value.trim();
        const rx = overlay.querySelector('#adminSignoffRx').value.trim();

        closeDialog();
        executeVerificationStatus(patient_id, 'completed', {
            diagnosis: diag,
            treatment_plan: treatment,
            prescription: rx
        });
    });

    requestAnimationFrame(() => {
        overlay.classList.add('show');
        const diagInput = overlay.querySelector('#adminSignoffDiag');
        if (diagInput) diagInput.focus();
    });
}

async function executeVerificationStatus(patient_id, action, extraData = {}) {
    try {
        const payload = Object.assign({ action, patient_id }, extraData);
        const res = await fetch('/sdn/api/patients.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        }).then(r => r.json());

        if (res.success) {
            closePatientDetailsModal();
            loadPMI();
            if (typeof loadDashboardTable === 'function') loadDashboardTable();
            let msg = 'Patient status updated successfully!';
            if (action === 'no-show') msg = 'Patient marked as No-Show';
            else if (action === 'arrived') msg = 'Patient checked in at facility (Arrived)';
            else if (action === 'ongoing') msg = 'Consultation started (Ongoing)';
            else if (action === 'completed') msg = 'Consultation completed and officially signed off!';
            else if (action === 'approve') msg = 'Patient verified and appointment accepted!';
            showToast(msg);
        } else {
            showToast(res.message || 'Operation failed', 'error');
        }
    } catch(e) { 
        console.error(e); 
        showToast('Error updating patient status', 'error');
    }
}

function promptResolveInquiry(patient_id, patientName) {
    const modalHtml = `
        <div id="resolveInquiryModal" class="modal show" style="display:flex; position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:99999; align-items:center; justify-content:center;">
            <div class="modal-content" style="max-width:500px; width:92%; background:#fff; border-radius:12px; box-shadow:0 12px 32px rgba(0,0,0,0.18); overflow:hidden;">
                <div class="modal-header" style="display:flex; justify-content:space-between; align-items:center; padding:16px 20px; border-bottom:1px solid #e2e8f0;">
                    <h2 style="font-size:1.15rem; margin:0; color:#0f2e1b;">Resolve Patient Inquiry</h2>
                    <span class="close" style="font-size:1.5rem; cursor:pointer;" onclick="document.getElementById('resolveInquiryModal').remove()">&times;</span>
                </div>
                <div style="padding:18px 20px;">
                    <p style="margin-bottom:12px; color:#334155;">Mark inquiry for <strong>${escapeHtml(patientName || patient_id)}</strong> as answered and completed.</p>
                    <label style="display:block; font-size:0.85rem; font-weight:600; color:#475569; margin-bottom:6px;">Resolution Remarks / Response Note (Optional):</label>
                    <textarea id="inquiryResolutionNote" class="form-control" rows="3" placeholder="e.g. Inquirer contacted via phone; questions addressed regarding clinic hours and consultation requirements." style="width:100%; border-radius:8px; padding:10px; border:1px solid #cbd5e1; box-sizing:border-box;"></textarea>
                </div>
                <div class="patient-modal-actions-bar" style="display:flex; justify-content:flex-end; gap:8px; padding:14px 20px; background:#f8fafc; border-top:1px solid #e2e8f0;">
                    <button type="button" class="btn btn-outline" onclick="document.getElementById('resolveInquiryModal').remove()">Cancel</button>
                    <button type="button" class="btn btn-primary" id="btnConfirmResolveInquiry" style="background:#059669; border-color:#059669; color:#fff; font-weight:600; padding:8px 16px; border-radius:8px; cursor:pointer;">
                        <span>Confirm Resolved</span>
                    </button>
                </div>
            </div>
        </div>
    `;
    const existing = document.getElementById('resolveInquiryModal');
    if (existing) existing.remove();
    document.body.insertAdjacentHTML('beforeend', modalHtml);

    document.getElementById('btnConfirmResolveInquiry').onclick = async () => {
        const btn = document.getElementById('btnConfirmResolveInquiry');
        const note = (document.getElementById('inquiryResolutionNote')?.value || '').trim();
        btn.disabled = true;
        btn.textContent = 'Resolving...';

        try {
            const res = await fetch('/sdn/api/patients.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'completed',
                    patient_id: patient_id,
                    note: note
                })
            }).then(r => r.json());

            if (res.success) {
                document.getElementById('resolveInquiryModal')?.remove();
                closePatientDetailsModal();
                showToast('Inquiry resolved and completed successfully!');
                loadPMI();
                if (typeof loadDashboardTable === 'function') loadDashboardTable();
            } else {
                showToast(res.message || 'Error resolving inquiry.', 'error');
                btn.disabled = false;
                btn.textContent = 'Confirm Resolved';
            }
        } catch(e) {
            console.error(e);
            showToast('Server error while resolving inquiry.', 'error');
            btn.disabled = false;
            btn.textContent = 'Confirm Resolved';
        }
    };
}



async function loadAudit() {
    try {
        const tbody = document.getElementById('audit-tbody');
        if (!tbody) return;
        const res = await fetch('/sdn/api/logs.php?_=' + Date.now()).then(r => r.json());
        if (res.success && Array.isArray(res.data)) {
            if (res.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:30px; color:var(--muted);">No audit logs recorded yet.</td></tr>';
                return;
            }
            tbody.innerHTML = res.data.map(l => `
                <tr>
                    <td><small style="color:var(--muted); font-size: 0.88rem;">${escapeHtml(l.timestamp)}</small></td>
                    <td><strong style="color: #0f172a;">${escapeHtml(l.action)}</strong></td>
                    <td style="color: #334155;">${escapeHtml(l.user)}</td>
                    <td style="color: #334155;">${escapeHtml(l.details)}</td>
                </tr>
            `).join('');
            syncTableScrollbars();
        }
    } catch(e) { console.error('Error loading audit trail:', e); }
}
function togglePasswordVisibility(inputId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById('eyeIcon-' + inputId);
    if (!input || !icon) return;
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
    } else {
        input.type = 'password';
        icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// PHASE 4: ADMIN CONSOLIDATED REPORT MODULES & MULTI-DEPARTMENT ANALYTICS
// ══════════════════════════════════════════════════════════════════════════════
let currentReportTab = 'daily'; // 'daily' | 'monthly' | 'roster'
let cachedReportsData = null;

function switchReportTab(tab) {
    currentReportTab = tab;
    ['daily', 'monthly', 'roster'].forEach(t => {
        const btn = document.getElementById('tabBtn' + t.charAt(0).toUpperCase() + t.slice(1));
        const sec = document.getElementById('reportSection' + t.charAt(0).toUpperCase() + t.slice(1));
        if (btn) btn.classList.toggle('active', t === tab);
        if (sec) sec.style.display = (t === tab) ? 'block' : 'none';
    });
    syncTableScrollbars();
}

function resetReportFilters() {
    const periodEl = document.getElementById('reportPeriodFilter');
    if (periodEl) periodEl.value = 'all';
    const deptEl = document.getElementById('reportDeptFilter');
    if (deptEl) deptEl.value = 'all';
    loadReports();
}

function exportReportsCsv() {
    const period = document.getElementById('reportPeriodFilter')?.value || 'all';
    const dept = document.getElementById('reportDeptFilter')?.value || 'all';
    const exportUrl = `/sdn/api/reports.php?export_csv=1&range=${encodeURIComponent(period)}&department=${encodeURIComponent(dept)}`;
    const a = document.createElement('a');
    a.href = exportUrl;
    a.download = '';
    document.body.appendChild(a);
    a.click();
    a.remove();
    showToast('Exporting consolidated CSV report...');
}

function printReports() {
    window.print();
}

async function loadReports() {
    const period = document.getElementById('reportPeriodFilter')?.value || 'all';
    const dept = document.getElementById('reportDeptFilter')?.value || 'all';

    try {
        const url = `/sdn/api/reports.php?range=${encodeURIComponent(period)}&department=${encodeURIComponent(dept)}&_=${Date.now()}`;
        const res = await fetch(url).then(r => r.json());

        const repData = res.data || res;
        if (!res.success || !repData || (!repData.summary && !res.summary)) {
            console.error('Failed to load reports data', res);
            const tbody = document.getElementById('reportDailyTbody');
            if (tbody) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:35px; color:#ef4444;">Failed to load consolidated reports data.</td></tr>';
            }
            return;
        }

        cachedReportsData = repData;
        const summary = repData.summary || {};
        const depts = repData.departments || [];
        const dailyReports = repData.daily_reports || [];
        const monthlyReports = repData.monthly_reports || [];
        const roster = repData.recent_records || [];

        // 1. Update KPI Overview Cards
        const repKpiTotal = document.getElementById('repKpiTotal');
        if (repKpiTotal) repKpiTotal.textContent = Number(summary.total_patients || 0).toLocaleString();
        
        const repKpiSubTotal = document.getElementById('repKpiSubTotal');
        if (repKpiSubTotal) {
            repKpiSubTotal.innerHTML = `<span><strong>${summary.total_walkin || 0}</strong> walk-in</span> &bull; <span><strong>${summary.total_online || 0}</strong> online</span>`;
        }

        const repKpiCompleted = document.getElementById('repKpiCompleted');
        if (repKpiCompleted) repKpiCompleted.textContent = Number(summary.total_completed || 0).toLocaleString();

        const repKpiSubCompleted = document.getElementById('repKpiSubCompleted');
        if (repKpiSubCompleted) {
            repKpiSubCompleted.textContent = `${summary.completion_rate_text || '0%'} overall completion rate`;
        }

        const repKpiOngoing = document.getElementById('repKpiOngoing');
        if (repKpiOngoing) repKpiOngoing.textContent = Number(summary.total_ongoing || 0).toLocaleString();

        const repKpiSubOngoing = document.getElementById('repKpiSubOngoing');
        if (repKpiSubOngoing) {
            repKpiSubOngoing.textContent = `${summary.total_for_approval || 0} awaiting verification / check-in`;
        }

        const repKpiNoshow = document.getElementById('repKpiNoshow');
        if (repKpiNoshow) repKpiNoshow.textContent = Number(summary.total_noshow || 0).toLocaleString();

        const repKpiSubNoshow = document.getElementById('repKpiSubNoshow');
        if (repKpiSubNoshow) {
            repKpiSubNoshow.textContent = `${summary.noshow_rate_text || '0%'} isolated no-show rate`;
        }

        const updatedEl = document.getElementById('reportLastUpdated');
        if (updatedEl) {
            updatedEl.textContent = `Updated: ${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' })}`;
        }

        // 2. Render Tri-Department Oversight Cards
        renderDepartmentCards(depts);

        // 3. Render Daily Aggregation Table
        renderDailyReportsTable(dailyReports);

        // 4. Render Monthly Aggregation Table
        renderMonthlyReportsTable(monthlyReports);

        // 5. Render Consolidated Patient Roster Table
        renderRosterReportsTable(roster);

        syncTableScrollbars();
    } catch (e) {
        console.error('Error fetching consolidated reports:', e);
        const tbody = document.getElementById('reportDailyTbody');
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:35px; color:#ef4444;">Error connecting to reports server.</td></tr>';
        }
    }
}

function renderDepartmentCards(departments) {
    const grid = document.getElementById('reportDeptsGrid');
    if (!grid) return;

    if (!departments || departments.length === 0) {
        grid.innerHTML = '<p style="color:#64748b; padding:20px;">No department summary data available.</p>';
        return;
    }

    grid.innerHTML = departments.map(d => {
        let tagClass = 'tag-general';
        let deptShort = 'General Care';
        const dName = d.name || '';
        if (dName.toLowerCase().includes('psych')) {
            tagClass = 'tag-psych';
            deptShort = 'OPU Behavioral';
        } else if (dName.toLowerCase().includes('neuro')) {
            tagClass = 'tag-neuro';
            deptShort = 'Neurosciences';
        }

        const compRate = Math.min(100, Math.max(0, d.completion_rate || 0));

        return `
            <div class="dept-overview-card">
                <div>
                    <div class="dept-card-header">
                        <div>
                            <h3 class="dept-card-title">${escapeHtml(d.name)}</h3>
                            <span class="dept-card-tag ${tagClass}">${escapeHtml(deptShort)}</span>
                        </div>
                        <div style="text-align:right;">
                            <span style="font-size:1.4rem; font-weight:700; color:#0f172a;">${d.total}</span>
                            <span style="display:block; font-size:0.7rem; color:#64748b; text-transform:uppercase;">Total Visits</span>
                        </div>
                    </div>

                    <div class="dept-card-stats">
                        <div class="dept-stat-box">
                            <span class="val">${d.walkin}</span>
                            <span class="lbl">Walk-in Queue</span>
                        </div>
                        <div class="dept-stat-box">
                            <span class="val">${d.online}</span>
                            <span class="lbl">Online Appts</span>
                        </div>
                        <div class="dept-stat-box">
                            <span class="val" style="color:#059669;">${d.completed}</span>
                            <span class="lbl">Completed</span>
                        </div>
                        <div class="dept-stat-box">
                            <span class="val" style="color:#e11d48;">${d.noshow}</span>
                            <span class="lbl">No-Shows</span>
                        </div>
                    </div>
                </div>

                <div class="dept-progress-wrap">
                    <div class="dept-progress-label">
                        <span>Clinical Sign-Off Rate</span>
                        <span>${compRate}%</span>
                    </div>
                    <div class="dept-progress-bar-bg">
                        <div class="dept-progress-bar-fill" style="width: ${compRate}%;"></div>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function renderDailyReportsTable(dailyList) {
    const tbody = document.getElementById('reportDailyTbody');
    if (!tbody) return;

    if (!dailyList || dailyList.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:35px; color:#64748b;">No daily records found for the selected filter range.</td></tr>';
        return;
    }

    tbody.innerHTML = dailyList.map(item => {
        let tagClass = 'tag-general';
        const dName = item.department || '';
        if (dName.toLowerCase().includes('psych')) tagClass = 'tag-psych';
        else if (dName.toLowerCase().includes('neuro')) tagClass = 'tag-neuro';

        return `
            <tr>
                <td><strong>${escapeHtml(item.formatted_date || item.date)}</strong></td>
                <td><span class="dept-card-tag ${tagClass}">${escapeHtml(item.department)}</span></td>
                <td><strong style="color:#0f172a;">${item.total}</strong></td>
                <td><span class="badge" style="background:#f1f5f9; color:#475569;">${item.walkin}</span></td>
                <td><span class="badge" style="background:#eff6ff; color:#1d4ed8;">${item.online}</span></td>
                <td><span class="badge" style="background:#ecfdf5; color:#047857; font-weight:600;">${item.completed}</span></td>
                <td>
                    <span class="badge" style="background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0;">
                        ${item.completion_rate}%
                    </span>
                </td>
            </tr>
        `;
    }).join('');
}

function renderMonthlyReportsTable(monthlyList) {
    const tbody = document.getElementById('reportMonthlyTbody');
    if (!tbody) return;

    if (!monthlyList || monthlyList.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:35px; color:#64748b;">No monthly records found for the selected filter range.</td></tr>';
        return;
    }

    tbody.innerHTML = monthlyList.map(item => {
        let tagClass = 'tag-general';
        const dName = item.department || '';
        if (dName.toLowerCase().includes('psych')) tagClass = 'tag-psych';
        else if (dName.toLowerCase().includes('neuro')) tagClass = 'tag-neuro';

        return `
            <tr>
                <td><strong>${escapeHtml(item.month_label || item.month)}</strong></td>
                <td><span class="dept-card-tag ${tagClass}">${escapeHtml(item.department)}</span></td>
                <td><strong style="color:#0f172a;">${item.total}</strong></td>
                <td><span class="badge" style="background:#f1f5f9; color:#475569;">${item.walkin}</span></td>
                <td><span class="badge" style="background:#eff6ff; color:#1d4ed8;">${item.online}</span></td>
                <td><span class="badge" style="background:#ecfdf5; color:#047857; font-weight:600;">${item.completed}</span></td>
                <td>
                    <span class="badge" style="background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0;">
                        ${item.completion_rate}%
                    </span>
                </td>
            </tr>
        `;
    }).join('');
}

function renderRosterReportsTable(roster) {
    const tbody = document.getElementById('reportRosterTbody');
    if (!tbody) return;

    if (!roster || roster.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:35px; color:#64748b;">No patient records match the selected criteria.</td></tr>';
        return;
    }

    tbody.innerHTML = roster.map(p => {
        let tagClass = 'tag-general';
        const dName = p.normalized_department || p.department || '';
        if (dName.toLowerCase().includes('psych')) tagClass = 'tag-psych';
        else if (dName.toLowerCase().includes('neuro')) tagClass = 'tag-neuro';

        const isInquiry = p.inquiry_only == 1 || p.inquiry_only === true || p.inquiry_only === '1';
        let typeBadge = '';
        if (isInquiry) {
            typeBadge = '<span class="badge" style="background:#fef3c7; color:#92400e; border:1px solid #fcd34d;">Inquiry</span>';
        } else if (p.is_walkin) {
            typeBadge = '<span class="badge" style="background:#fef3c7; color:#92400e;">Walk-in</span>';
        } else {
            typeBadge = '<span class="badge" style="background:#eff6ff; color:#1d4ed8;">Online</span>';
        }

        const inquiryTag = isInquiry
            ? `<span style="display:inline-block; margin-left:6px; padding:2px 7px; background:#fef3c7; color:#92400e; border:1px solid #fcd34d; border-radius:999px; font-size:0.68rem; font-weight:700; text-transform:uppercase; vertical-align:middle;">Inquiry</span>`
            : '';

        const st = (p.status || 'pending').toLowerCase();
        let statusBadge = `<span class="badge status-badge-${st}">${escapeHtml(st.toUpperCase())}</span>`;
        if (st === 'completed') {
            statusBadge = '<span class="badge" style="background:#ecfdf5; color:#047857; font-weight:700;">COMPLETED</span>';
        } else if (st === 'no-show') {
            statusBadge = '<span class="badge" style="background:#fef2f2; color:#b91c1c;">NO-SHOW</span>';
        } else if (st === 'ongoing') {
            statusBadge = '<span class="badge" style="background:#ede9fe; color:#6d28d9;">ONGOING</span>';
        }

        const docDiag = isInquiry
            ? `<span style="color:#d97706; font-weight:500;">${escapeHtml(p.mode_of_inquiry || 'Inquiry Only')}</span>`
            : (p.assigned_doctor 
                ? `${escapeHtml(p.assigned_doctor)}${p.medical_history_diagnosis ? ` &bull; <em>${escapeHtml(p.medical_history_diagnosis)}</em>` : ''}`
                : (p.medical_history_diagnosis ? escapeHtml(p.medical_history_diagnosis) : '<span style="color:#94a3b8;">Unassigned</span>'));

        const safeId = escapeHtml(p.patient_id);
        return `
            <tr class="clickable-row" onclick="openPatientDetailsModal('${safeId}')" title="Click to view full patient profile">
                <td><strong style="color:var(--deep-green); cursor:pointer;">${safeId}</strong></td>
                <td><strong>${escapeHtml(p.full_name)}</strong>${inquiryTag}</td>
                <td><span class="dept-card-tag ${tagClass}">${escapeHtml(dName)}</span></td>
                <td>${typeBadge}</td>
                <td>${statusBadge}</td>
                <td style="font-size:0.85rem;">${docDiag}</td>
            </tr>
        `;
    }).join('');
}

