/**
 * public/js/super-admin/super-admin.js
 * Base interactivity for Super Admin Portal:
 * - Sidebar collapse toggle & local storage persistence
 * - Profile dropdown menu
 * - SPA view navigation router
 */

document.addEventListener('DOMContentLoaded', () => {
    initSidebar();
    initProfileDropdown();
    initViewRouter();
    loadOverviewData();
});

// ── OVERVIEW & HEALTH MONITORING ──────────────────────────────────────────
async function loadOverviewData() {
    const refreshBtn = document.getElementById('saRefreshOverviewBtn');
    if (refreshBtn) refreshBtn.classList.add('loading');

    try {
        const res = await fetch('/sdn/api/super-admin/overview.php?_=' + Date.now()).then(r => r.json());
        if (!res.success) return;

        // Render Telemetry Cards
        if (res.metrics) {
            document.getElementById('metricAdmins').textContent = res.metrics.total_admins ?? '0';
            document.getElementById('metricStaff').textContent = res.metrics.total_staff ?? '0';
            document.getElementById('metricPatients').textContent = res.metrics.total_patients ?? '0';
            document.getElementById('metricAppointments').textContent = res.metrics.today_appointments ?? '0';
        }

        // Render Maintenance State
        if (res.maintenance) {
            const toggle = document.getElementById('saMaintenanceToggle');
            const statusText = document.getElementById('saMaintenanceStatusText');
            const msgInput = document.getElementById('saMaintenanceMsg');
            const isMaint = !!res.maintenance.maintenance_mode;

            if (toggle) toggle.checked = isMaint;
            if (statusText) {
                statusText.textContent = isMaint ? 'Active (All non-super admin users locked out)' : 'Disabled (Normal Operations)';
                statusText.style.color = isMaint ? '#dc2626' : '#15803d';
            }
            if (msgInput && res.maintenance.maintenance_message) {
                msgInput.value = res.maintenance.maintenance_message;
            }
        }

        // Render Health Indicators
        if (res.health) {
            renderHealthItem('db', res.health.database);
            renderHealthItem('smtp', res.health.smtp);
            renderHealthItem('disk', res.health.disk);
        }

    } catch (e) {
        console.error('Error loading Super Admin overview:', e);
    } finally {
        if (refreshBtn) refreshBtn.classList.remove('loading');
    }
}

function renderHealthItem(type, data) {
    if (!data) return;
    const dot = document.getElementById(`healthDot_${type}`);
    const meta = document.getElementById(`healthMeta_${type}`);
    const badge = document.getElementById(`healthBadge_${type}`);

    const status = data.status || 'unknown';
    if (dot) {
        dot.className = `sa-health-dot sa-dot-${status}`;
    }
    if (badge) {
        badge.className = `sa-health-badge sa-badge-${status}`;
        badge.textContent = status.toUpperCase();
    }
    if (meta) {
        if (type === 'db') {
            meta.textContent = status === 'online' ? `Operational &bull; ${data.latency_ms} ms ping` : `Service Error`;
        } else if (type === 'smtp') {
            meta.textContent = data.message || 'Config status';
        } else if (type === 'disk') {
            meta.textContent = `${data.free_gb} GB free drive capacity`;
        }
    }
}

// Maintenance Mode Toggle Handler
async function handleMaintenanceToggle() {
    const toggle = document.getElementById('saMaintenanceToggle');
    const msgInput = document.getElementById('saMaintenanceMsg');
    if (!toggle) return;

    const enable = toggle.checked;
    const message = msgInput ? msgInput.value.trim() : '';

    if (enable) {
        const confirmed = confirm('WARNING: Enabling Maintenance Mode will immediately lock out regular Admins, Staff, and Patients from the portal. Only Super Admins will retain access. Continue?');
        if (!confirmed) {
            toggle.checked = false;
            return;
        }
    }

    try {
        const res = await fetch('/sdn/api/super-admin/overview.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'toggle_maintenance',
                enable,
                message
            })
        }).then(r => r.json());

        if (res.success) {
            loadOverviewData();
        } else {
            alert(res.message || 'Failed to update maintenance mode.');
            toggle.checked = !enable;
        }
    } catch (e) {
        alert('Network error updating maintenance mode.');
        toggle.checked = !enable;
    }
}

// ── RBAC & ADMIN USER MANAGEMENT ──────────────────────────────────────────
let allAdminsList = [];

async function loadAdminsData() {
    const tbody = document.getElementById('saAdminTableBody');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:24px; color:var(--sa-muted);">Loading admin accounts...</td></tr>';

    try {
        const res = await fetch('/sdn/api/super-admin/admins.php?_=' + Date.now()).then(r => r.json());
        if (res.success && Array.isArray(res.data)) {
            allAdminsList = res.data;
            renderAdminsTable(allAdminsList);
        } else {
            tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:24px; color:#dc2626;">${res.message || 'Error loading admins'}</td></tr>`;
        }
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:24px; color:#dc2626;">Network error loading admins.</td></tr>';
    }
}

function renderAdminsTable(list) {
    const tbody = document.getElementById('saAdminTableBody');
    if (!tbody) return;

    const search = (document.getElementById('saAdminSearchInput')?.value || '').toLowerCase().trim();
    const filtered = search
        ? list.filter(a => (a.username || '').toLowerCase().includes(search) || (a.full_name || '').toLowerCase().includes(search))
        : list;

    if (!filtered.length) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:24px; color:var(--sa-muted);">No admin accounts found.</td></tr>';
        return;
    }

    tbody.innerHTML = filtered.map(a => {
        const p = a.permissions || {};
        const permTags = [
            p.can_manage_doctors ? '<span class="sa-perm-tag">Doctors</span>' : '',
            p.can_manage_staff ? '<span class="sa-perm-tag">Staff</span>' : '',
            p.can_export_data ? '<span class="sa-perm-tag">Export</span>' : '',
            p.can_verify_patients ? '<span class="sa-perm-tag">Verify</span>' : '',
            p.can_view_audit ? '<span class="sa-perm-tag">Audit</span>' : ''
        ].filter(Boolean).join('');

        const isActive = (a.status || 'active').toLowerCase() === 'active';

        return `
            <tr>
                <td>
                    <div style="font-weight:700; color:var(--sa-text);">${escapeHtml(a.full_name)}</div>
                    <div style="font-size:0.75rem; color:var(--sa-muted);">${escapeHtml(a.created_at || '')}</div>
                </td>
                <td><code style="font-size:0.84rem; color:var(--sa-primary); font-weight:600;">@${escapeHtml(a.username)}</code></td>
                <td>
                    <label class="sa-switch" style="transform:scale(0.8); transform-origin:left center;">
                        <input type="checkbox" ${isActive ? 'checked' : ''} onchange="toggleAdminStatus(${a.id}, this.checked)">
                        <span class="sa-slider"></span>
                    </label>
                </td>
                <td>
                    <div class="sa-perm-badges">${permTags || '<span style="color:var(--sa-muted); font-size:0.75rem;">None</span>'}</div>
                </td>
                <td style="font-size:0.8rem; color:var(--sa-muted);">${escapeHtml(a.last_login || 'Never')}</td>
                <td>
                    <div class="sa-actions-col">
                        <button type="button" class="sa-btn sa-btn-outline sa-btn-sm" onclick="openEditAdminModal(${a.id})">Edit</button>
                        <button type="button" class="sa-btn sa-btn-danger sa-btn-sm" onclick="deleteAdminAccount(${a.id}, '${escapeHtml(a.username)}')">Delete</button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function openCreateAdminModal() {
    document.getElementById('modalAdminTitle').textContent = 'Create New Regular Admin';
    document.getElementById('adminModalId').value = '';
    document.getElementById('adminModalUsername').value = '';
    document.getElementById('adminModalUsername').disabled = false;
    document.getElementById('adminModalFullName').value = '';
    document.getElementById('adminModalPassword').value = '';
    document.getElementById('adminModalPassword').placeholder = 'At least 6 characters';

    // Reset permissions
    document.getElementById('perm_doctors').checked = true;
    document.getElementById('perm_staff').checked = true;
    document.getElementById('perm_export').checked = true;
    document.getElementById('perm_verify').checked = true;
    document.getElementById('perm_audit').checked = true;

    document.getElementById('saAdminModal').classList.add('open');
}

function openEditAdminModal(adminId) {
    const admin = allAdminsList.find(a => a.id == adminId);
    if (!admin) return;

    document.getElementById('modalAdminTitle').textContent = 'Edit Admin Permissions';
    document.getElementById('adminModalId').value = admin.id;
    document.getElementById('adminModalUsername').value = admin.username;
    document.getElementById('adminModalUsername').disabled = true; // username locked
    document.getElementById('adminModalFullName').value = admin.full_name;
    document.getElementById('adminModalPassword').value = '';
    document.getElementById('adminModalPassword').placeholder = 'Leave blank to retain current password';

    const p = admin.permissions || {};
    document.getElementById('perm_doctors').checked = !!p.can_manage_doctors;
    document.getElementById('perm_staff').checked = !!p.can_manage_staff;
    document.getElementById('perm_export').checked = !!p.can_export_data;
    document.getElementById('perm_verify').checked = !!p.can_verify_patients;
    document.getElementById('perm_audit').checked = !!p.can_view_audit;

    document.getElementById('saAdminModal').classList.add('open');
}

function closeAdminModal() {
    document.getElementById('saAdminModal').classList.remove('open');
}

async function saveAdminForm() {
    const id = document.getElementById('adminModalId').value;
    const username = document.getElementById('adminModalUsername').value.trim();
    const fullName = document.getElementById('adminModalFullName').value.trim();
    const password = document.getElementById('adminModalPassword').value;

    const permissions = {
        can_manage_doctors: document.getElementById('perm_doctors').checked,
        can_manage_staff: document.getElementById('perm_staff').checked,
        can_export_data: document.getElementById('perm_export').checked,
        can_verify_patients: document.getElementById('perm_verify').checked,
        can_view_audit: document.getElementById('perm_audit').checked
    };

    if (!fullName || (!id && (!username || !password))) {
        alert('Please fill in required fields.');
        return;
    }

    const payload = id
        ? { action: 'update', id, full_name: fullName, password, permissions }
        : { action: 'create', username, full_name: fullName, password, permissions };

    try {
        const res = await fetch('/sdn/api/super-admin/admins.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        }).then(r => r.json());

        if (res.success) {
            closeAdminModal();
            loadAdminsData();
        } else {
            alert(res.message || 'Error saving admin account.');
        }
    } catch (e) {
        alert('Network error.');
    }
}

async function toggleAdminStatus(id, isActive) {
    try {
        const res = await fetch('/sdn/api/super-admin/admins.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'toggle_status',
                id,
                status: isActive ? 'active' : 'inactive'
            })
        }).then(r => r.json());

        if (!res.success) {
            alert(res.message || 'Failed to update status');
            loadAdminsData();
        }
    } catch (e) {
        alert('Network error.');
        loadAdminsData();
    }
}

async function deleteAdminAccount(id, username) {
    const confirmed = confirm(`Are you sure you want to permanently delete admin account @${username}? This action is irreversible.`);
    if (!confirmed) return;

    try {
        const res = await fetch('/sdn/api/super-admin/admins.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id })
        }).then(r => r.json());

        if (res.success) {
            loadAdminsData();
        } else {
            alert(res.message || 'Error deleting admin.');
        }
    } catch (e) {
        alert('Network error.');
    }
}

function escapeHtml(str) {
    if (!str && str !== 0) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// ── GLOBAL SYSTEM CONFIGURATIONS ──────────────────────────────────────────
async function loadConfigData() {
    try {
        const res = await fetch('/sdn/api/super-admin/config.php?_=' + Date.now()).then(r => r.json());
        if (!res.success || !res.data) return;
        const c = res.data;

        // Hospital Details
        document.getElementById('cfgHospitalName').value = c.hospital_name || '';
        document.getElementById('cfgHospitalTagline').value = c.hospital_tagline || '';
        document.getElementById('cfgContactPhone').value = c.contact_phone || '';
        document.getElementById('cfgEmergencyHotline').value = c.emergency_hotline || '';
        document.getElementById('cfgContactEmail').value = c.contact_email || '';
        document.getElementById('cfgHospitalAddress').value = c.hospital_address || '';

        // SMTP Details
        document.getElementById('cfgSmtpHost').value = c.smtp_host || '';
        document.getElementById('cfgSmtpPort').value = c.smtp_port || '587';
        document.getElementById('cfgSmtpEncryption').value = c.smtp_encryption || 'tls';
        document.getElementById('cfgSmtpUser').value = c.smtp_username || '';
        document.getElementById('cfgSmtpPass').value = c.smtp_password || '';
        document.getElementById('cfgSmtpSender').value = c.smtp_sender_name || '';

        // Logo
        if (c.system_logo_url) {
            document.getElementById('cfgLogoPreviewImg').src = c.system_logo_url;
        }
    } catch (e) {
        console.error('Error loading configurations:', e);
    }
}

async function saveHospitalConfig() {
    const payload = {
        section: 'hospital',
        hospital_name: document.getElementById('cfgHospitalName').value.trim(),
        hospital_tagline: document.getElementById('cfgHospitalTagline').value.trim(),
        contact_phone: document.getElementById('cfgContactPhone').value.trim(),
        emergency_hotline: document.getElementById('cfgEmergencyHotline').value.trim(),
        contact_email: document.getElementById('cfgContactEmail').value.trim(),
        hospital_address: document.getElementById('cfgHospitalAddress').value.trim()
    };

    try {
        const res = await fetch('/sdn/api/super-admin/config.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        }).then(r => r.json());

        alert(res.message || (res.success ? 'Hospital details updated.' : 'Failed to update.'));
    } catch (e) {
        alert('Network error.');
    }
}

async function saveSmtpConfig() {
    const payload = {
        section: 'smtp',
        smtp_host: document.getElementById('cfgSmtpHost').value.trim(),
        smtp_port: parseInt(document.getElementById('cfgSmtpPort').value.trim(), 10) || 587,
        smtp_encryption: document.getElementById('cfgSmtpEncryption').value,
        smtp_username: document.getElementById('cfgSmtpUser').value.trim(),
        smtp_password: document.getElementById('cfgSmtpPass').value.trim(),
        smtp_sender_name: document.getElementById('cfgSmtpSender').value.trim()
    };

    try {
        const res = await fetch('/sdn/api/super-admin/config.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        }).then(r => r.json());

        alert(res.message || (res.success ? 'SMTP settings saved.' : 'Failed to save SMTP.'));
        loadConfigData();
    } catch (e) {
        alert('Network error.');
    }
}

async function uploadSystemLogo(fileInput) {
    if (!fileInput.files || !fileInput.files[0]) return;
    const formData = new FormData();
    formData.append('system_logo', fileInput.files[0]);

    try {
        const res = await fetch('/sdn/api/super-admin/config.php', {
            method: 'POST',
            body: formData
        }).then(r => r.json());

        if (res.success && res.logo_url) {
            document.getElementById('cfgLogoPreviewImg').src = res.logo_url;
            alert('System logo updated successfully.');
        } else {
            alert(res.message || 'Failed to upload logo.');
        }
    } catch (e) {
        alert('Upload failed.');
    }
}

// ── DATABASE MANAGEMENT & BACKUPS ──────────────────────────────────────────
async function loadBackupData() {
    try {
        const res = await fetch('/sdn/api/super-admin/backup.php?action=info&_=' + Date.now()).then(r => r.json());
        if (!res.success) return;

        document.getElementById('bkDbName').textContent = res.database || 'scheduling_db';
        document.getElementById('bkTablesCount').textContent = res.tables_count ?? '--';
        document.getElementById('bkDbSize').textContent = (res.database_size_mb ?? '--') + ' MB';
        document.getElementById('bkLastDate').textContent = res.last_backup || 'No backups generated yet';
        document.getElementById('bkTotalCount').textContent = res.total_backups ?? '0';
    } catch (e) {
        console.error('Error loading backup meta:', e);
    }
}

function triggerSqlBackupDownload() {
    window.location.href = '/sdn/api/super-admin/backup.php?action=download';
    setTimeout(loadBackupData, 3000);
}

// ── MASTER AUDIT TRAIL ──────────────────────────────────────────
let currentAuditPage = 1;

async function loadAuditData(page = 1) {
    currentAuditPage = page;
    const tbody = document.getElementById('saAuditTableBody');
    if (!tbody) return;

    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:24px; color:var(--sa-muted);">Querying system audit logs...</td></tr>';

    const role = document.getElementById('saAuditRoleFilter')?.value || 'all';
    const actionType = document.getElementById('saAuditActionFilter')?.value || 'all';
    const dateFrom = document.getElementById('saAuditDateFrom')?.value || '';
    const dateTo = document.getElementById('saAuditDateTo')?.value || '';
    const search = document.getElementById('saAuditSearch')?.value.trim() || '';

    const params = new URLSearchParams({
        page,
        limit: 15,
        role,
        action_type: actionType,
        date_from: dateFrom,
        date_to: dateTo,
        search,
        _: Date.now()
    });

    try {
        const res = await fetch('/sdn/api/super-admin/audit.php?' + params.toString()).then(r => r.json());
        if (!res.success) {
            tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:24px; color:#dc2626;">${res.message || 'Error'}</td></tr>`;
            return;
        }

        const list = res.data || [];
        if (!list.length) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:24px; color:var(--sa-muted);">No logs match criteria.</td></tr>';
            document.getElementById('saAuditPaginationInfo').textContent = 'Showing 0 records';
            return;
        }

        tbody.innerHTML = list.map(l => {
            const roleBadgeClass = l.role === 'admin' ? 'sa-badge-warning' : (l.role === 'staff' ? 'sa-badge-online' : 'sa-perm-tag');
            return `
                <tr>
                    <td style="font-size:0.8rem; color:var(--sa-muted); white-space:nowrap;">${escapeHtml(l.created_at)}</td>
                    <td>
                        <span class="sa-health-badge ${roleBadgeClass}" style="font-size:0.7rem;">${escapeHtml((l.role || 'system').toUpperCase())}</span>
                    </td>
                    <td><strong style="color:var(--sa-text); font-size:0.85rem;">${escapeHtml(l.action)}</strong></td>
                    <td><code style="color:var(--sa-primary); font-size:0.82rem;">${escapeHtml(l.user)}</code></td>
                    <td style="font-size:0.84rem; color:#334155;">${escapeHtml(l.details || '')}</td>
                    <td><code style="font-size:0.75rem; color:var(--sa-muted);">${escapeHtml(l.ip_address || '-')}</code></td>
                </tr>
            `;
        }).join('');

        // Pagination info
        const pag = res.pagination;
        document.getElementById('saAuditPaginationInfo').textContent = `Page ${pag.page} of ${pag.total_pages || 1} (${pag.total} total logs)`;
        document.getElementById('saAuditPrevBtn').disabled = pag.page <= 1;
        document.getElementById('saAuditNextBtn').disabled = pag.page >= pag.total_pages;

    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:24px; color:#dc2626;">Network error.</td></tr>';
    }
}

function prevAuditPage() {
    if (currentAuditPage > 1) {
        loadAuditData(currentAuditPage - 1);
    }
}

function nextAuditPage() {
    loadAuditData(currentAuditPage + 1);
}

// ── DATA ARCHIVING & RETENTION ──────────────────────────────────────────
async function loadArchiveData() {
    const years = document.getElementById('saArchiveYearsSelect')?.value || '5';
    try {
        const res = await fetch(`/sdn/api/super-admin/archive.php?years=${years}&_=` + Date.now()).then(r => r.json());
        if (!res.success) return;

        document.getElementById('saArchiveCutoffText').textContent = `Records older than: ${res.cutoff_date}`;
        document.getElementById('saEligibleAppts').textContent = res.eligible?.appointments ?? '0';
        document.getElementById('saEligibleLogs').textContent = res.eligible?.logs ?? '0';
        document.getElementById('saTotalEligible').textContent = res.eligible?.total ?? '0';

        document.getElementById('saExistingAppts').textContent = res.existing_archived?.appointments ?? '0';
        document.getElementById('saExistingLogs').textContent = res.existing_archived?.logs ?? '0';
    } catch (e) {
        console.error('Error loading archive data:', e);
    }
}

async function executeArchiveProcess() {
    const years = document.getElementById('saArchiveYearsSelect')?.value || '5';
    const total = document.getElementById('saTotalEligible')?.textContent || '0';

    if (total === '0') {
        alert('There are no eligible historical records matching the selected retention period.');
        return;
    }

    const confirmed = confirm(`STRICT CONFIRMATION REQUIRED:\n\nYou are about to move all records older than ${years} years from active production tables to the secure archive tables.\n\nThis will optimize active system queries and database performance. Proceed?`);
    if (!confirmed) return;

    try {
        const res = await fetch('/sdn/api/super-admin/archive.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ years })
        }).then(r => r.json());

        alert(res.message || (res.success ? 'Archive completed.' : 'Archive failed.'));
        loadArchiveData();
    } catch (e) {
        alert('Network error during archive execution.');
    }
}

window.onInitView_archive = loadArchiveData;







function initSidebar() {
    const sidebar = document.getElementById('saSidebar');
    const collapseBtn = document.getElementById('saCollapseBtn');
    if (!sidebar || !collapseBtn) return;

    // Restore state from localStorage
    const isCollapsed = localStorage.getItem('sa_sidebar_collapsed') === 'true';
    if (isCollapsed) {
        sidebar.classList.add('collapsed');
    }

    collapseBtn.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        localStorage.setItem('sa_sidebar_collapsed', sidebar.classList.contains('collapsed'));
    });
}

function initProfileDropdown() {
    const wrap = document.getElementById('saProfileWrap');
    const btn = document.getElementById('saProfileBtn');
    const dd = document.getElementById('saProfileDropdown');
    if (!wrap || !btn || !dd) return;

    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        dd.classList.toggle('open');
    });

    document.addEventListener('click', (e) => {
        if (!wrap.contains(e.target)) {
            dd.classList.remove('open');
        }
    });
}

function initViewRouter() {
    const navLinks = document.querySelectorAll('.sa-nav-item[data-view]');
    const views = document.querySelectorAll('.sa-view-panel');
    const titleEl = document.getElementById('saPageTitle');

    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const targetViewId = link.dataset.view;
            const targetView = document.getElementById(`view-${targetViewId}`);
            if (!targetView) return;

            navLinks.forEach(l => l.classList.remove('active'));
            link.classList.add('active');

            views.forEach(v => {
                v.style.display = 'none';
            });
            targetView.style.display = 'block';

            if (titleEl && link.dataset.title) {
                titleEl.textContent = link.dataset.title;
            }

            // Optional hook for view-specific initialization
            if (typeof window[`onInitView_${targetViewId}`] === 'function') {
                window[`onInitView_${targetViewId}`]();
            }
        });
    });
}

