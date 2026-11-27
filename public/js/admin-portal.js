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
            if (!targetView) return;
            e.preventDefault();
            document.querySelectorAll('.sidebar-nav .nav-link').forEach(l => l.classList.remove('active'));
            document.querySelectorAll('.view-section').forEach(v => v.classList.remove('active'));
            e.currentTarget.classList.add('active');
            document.getElementById('view-' + targetView).classList.add('active');
            syncTableScrollbars();

            const adminSearchWrap = document.querySelector('.admin-search-wrap');
            if (adminSearchWrap) {
                adminSearchWrap.style.display = (targetView === 'dashboard' || targetView === 'overview') ? '' : 'none';
            }

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

    // Close notification, profile & overview doctor filter dropdowns when clicking outside
    document.addEventListener('click', (e) => {
        const wrap = document.getElementById('adminNotifWrap');
        const dd = document.getElementById('adminNotifDropdown');
        if (wrap && dd && !wrap.contains(e.target)) {
            dd.classList.remove('open');
        }
        const profWrap = document.getElementById('adminProfileWrap');
        const profDd = document.getElementById('adminProfileDropdown');
        if (profWrap && profDd && !profWrap.contains(e.target)) {
            profDd.classList.remove('open');
            document.getElementById('adminProfileBtn')?.setAttribute('aria-expanded', 'false');
        }
        const docMenuWrap = document.getElementById('admDocFilterBtn')?.parentElement;
        const docMenu = document.getElementById('admDocFilterMenu');
        if (docMenuWrap && docMenu && !docMenuWrap.contains(e.target)) {
            docMenu.classList.remove('open');
        }
    });

    const globalSearchInput = document.getElementById('adminGlobalSearchInput');
    if (globalSearchInput) {
        const applyAdminGlobalSearch = (val) => {
            const activeSection = document.querySelector('.view-section.active');
            const activeId = activeSection ? activeSection.id : 'view-dashboard';

            if (activeId === 'view-rbac') {
                const el = document.getElementById('rbacSearchInput');
                if (el) el.value = val;
                filterRBACTable();
            } else if (activeId === 'view-doctors') {
                const el = document.getElementById('docSearchInput');
                if (el) el.value = val;
                filterDoctorsTable();
            } else if (activeId === 'view-pmi') {
                const el = document.getElementById('pmiSearchInput');
                if (el) el.value = val;
                filterPMITable();
            } else if (activeId === 'view-reports') {
                const el = document.getElementById('reportSearchInput');
                if (el) el.value = val;
                renderUpcomingPatientsTable();
            } else if (activeId === 'view-audit') {
                const el = document.getElementById('auditSearchInput');
                if (el) el.value = val;
                filterAndSortAuditTable();
            } else {
                adminOverviewSearch = val.toLowerCase();
                renderAdminOverviewDoctors();
                renderAdminOverviewCalendar();
            }
        };

        globalSearchInput.addEventListener('input', (e) => {
            applyAdminGlobalSearch((e.target.value || '').trim());
        });

        globalSearchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                const activeSection = document.querySelector('.view-section.active');
                const val = (e.target.value || '').trim();
                if (activeSection && (activeSection.id === 'view-dashboard' || activeSection.id === 'view-overview') && val.length > 0) {
                    e.preventDefault();
                    navigateToAdminView('pmi');
                    const el = document.getElementById('pmiSearchInput');
                    if (el) el.value = val;
                    filterPMITable();
                }
            }
        });
    }

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
                loadRBAC(true);
            } else if (activeSection.id === 'view-reports') {
                loadReports(true);
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

function showToast(msg, type = 'success', customTitle = null) {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        document.body.appendChild(container);
    }
    container.className = 'ep-toast-stack';
    container.removeAttribute('style');

    while (container.children.length >= 3) {
        container.firstElementChild.remove();
    }

    const normalizedType = (type === 'error' || type === 'danger')
        ? 'error'
        : (type === 'warning' || type === 'warn'
            ? 'warning'
            : (type === 'info' ? 'info' : 'success'));

    const titles = {
        success: 'Success',
        error: 'Action Failed',
        warning: 'Attention',
        info: 'Notice'
    };
    const icons = {
        success: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>',
        error: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
        warning: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
        info: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>'
    };
    const iconSvg = icons[normalizedType] || icons.success;

    const toast = document.createElement('div');
    toast.className = `ep-toast is-${normalizedType}`;
    toast.setAttribute('role', normalizedType === 'error' ? 'alert' : 'status');
    toast.setAttribute('aria-live', 'polite');
    toast.innerHTML = `
        <span class="ep-toast-icon" aria-hidden="true">${iconSvg}</span>
        <div class="ep-toast-body">
            <span class="ep-toast-title">${escapeHtml(customTitle || titles[normalizedType])}</span>
            <span class="ep-toast-msg">${escapeHtml(msg)}</span>
        </div>
        <button type="button" class="ep-toast-close" aria-label="Dismiss notification">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
        <div class="ep-toast-progress" aria-hidden="true"></div>
    `;

    let timer = null;
    const dismiss = () => {
        if (timer) {
            clearTimeout(timer);
            timer = null;
        }
        if (!toast.parentNode) return;
        toast.classList.remove('is-visible');
        setTimeout(() => toast.remove(), 170);
    };

    toast.querySelector('.ep-toast-close').addEventListener('click', dismiss);
    toast.addEventListener('mouseenter', () => {
        if (timer) {
            clearTimeout(timer);
            timer = null;
        }
    });
    toast.addEventListener('mouseleave', () => {
        if (toast.parentNode && toast.classList.contains('is-visible')) {
            timer = setTimeout(dismiss, 1800);
        }
    });

    container.appendChild(toast);
    requestAnimationFrame(() => toast.classList.add('is-visible'));
    timer = setTimeout(dismiss, 3800);
}

// ── SHARED AVATAR UPLOAD & DISPLAY HELPERS ───────────────────────────────────
const avatarUploadState = {
    staffAdd: { imageData: null, removeImage: false, previewId: 'staffAvatarPreview', inputId: 'staffAvatarInput', removeBtnId: 'staffAvatarRemoveBtn', fallback: 'ST' },
    staffEdit: { imageData: null, removeImage: false, previewId: 'editStaffAvatarPreview', inputId: 'editStaffAvatarInput', removeBtnId: 'editStaffAvatarRemoveBtn', fallback: 'ST' },
    doctor: { imageData: null, removeImage: false, previewId: 'doctorAvatarPreview', inputId: 'doctorAvatarInput', removeBtnId: 'doctorAvatarRemoveBtn', fallback: 'DR' }
};

function getPersonInitials(name, fallback = 'MD') {
    const clean = String(name || '').replace(/^dr\.?\s*/i, '').replace(/,?\s*md$/i, '').trim();
    if (!clean) return fallback;
    const parts = clean.split(/\s+/).filter(Boolean);
    if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
}

function renderEntityAvatarHtml(imageUrl, name, variant = 'doctor') {
    const initials = escapeHtml(getPersonInitials(name, variant === 'staff' ? 'ST' : 'DR'));
    const cls = variant === 'staff' ? 'entity-avatar-sm is-staff' : 'entity-avatar-sm';
    if (imageUrl) {
        return `<span class="${cls}"><img src="${escapeHtml(imageUrl)}" alt="${escapeHtml(name || '')}" onerror="this.style.display='none';this.nextElementSibling.style.display='inline';"><span style="display:none;">${initials}</span></span>`;
    }
    return `<span class="${cls}">${initials}</span>`;
}

function setAvatarModalState(key, existingUrl, nameHint) {
    const cfg = avatarUploadState[key];
    if (!cfg) return;
    cfg.imageData = null;
    cfg.removeImage = false;
    const inp = document.getElementById(cfg.inputId);
    if (inp) inp.value = '';
    const prev = document.getElementById(cfg.previewId);
    const remBtn = document.getElementById(cfg.removeBtnId);
    const fallback = getPersonInitials(nameHint, cfg.fallback);
    if (prev) {
        if (existingUrl) {
            prev.innerHTML = `<img src="${escapeHtml(existingUrl)}" alt="Profile preview" onerror="this.parentElement.innerHTML='<span class=\\'modal-avatar-placeholder\\'>${escapeHtml(fallback)}</span>'">`;
        } else {
            prev.innerHTML = `<span class="modal-avatar-placeholder">${escapeHtml(fallback)}</span>`;
        }
    }
    if (remBtn) {
        remBtn.style.display = existingUrl ? 'inline-flex' : 'none';
    }
}

function handleAvatarFileChange(input, key) {
    const cfg = avatarUploadState[key];
    const file = input?.files?.[0];
    if (!cfg || !file) return;
    const validTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp'];
    if (!validTypes.includes((file.type || '').toLowerCase())) {
        showToast('Please select a PNG, JPG, or WEBP image.', 'error');
        input.value = '';
        return;
    }
    if (file.size > 5 * 1024 * 1024) {
        showToast('Image file must not exceed 5MB.', 'error');
        input.value = '';
        return;
    }
    const reader = new FileReader();
    reader.onload = (ev) => {
        const dataUrl = ev.target?.result || null;
        if (!dataUrl) return;
        cfg.imageData = dataUrl;
        cfg.removeImage = false;
        const prev = document.getElementById(cfg.previewId);
        if (prev) prev.innerHTML = `<img src="${dataUrl}" alt="Profile preview">`;
        const remBtn = document.getElementById(cfg.removeBtnId);
        if (remBtn) remBtn.style.display = 'inline-flex';
    };
    reader.readAsDataURL(file);
}

function clearAvatarSelection(key) {
    const cfg = avatarUploadState[key];
    if (!cfg) return;
    cfg.imageData = null;
    cfg.removeImage = true;
    const inp = document.getElementById(cfg.inputId);
    if (inp) inp.value = '';
    const prev = document.getElementById(cfg.previewId);
    if (prev) prev.innerHTML = `<span class="modal-avatar-placeholder">${escapeHtml(cfg.fallback)}</span>`;
    const remBtn = document.getElementById(cfg.removeBtnId);
    if (remBtn) remBtn.style.display = 'none';
}

function cleanDeptLabel(raw) {
    if (!raw) return 'N/A';
    return String(raw)
        .replace(/\bOPU\s+/gi, '')
        .replace(/\s*\(OPU\)/gi, '')
        .trim() || 'Psychiatry';
}

function customConfirm(title, message, callback) {
    document.getElementById('confirmTitle').innerText = title;
    document.getElementById('confirmMessage').innerText = message;

    const confirmBtn = document.getElementById('confirmBtn');
    const newConfirmBtn = confirmBtn.cloneNode(true);
    confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

    const isDelete = /delete|remove/i.test(title) || /delete|remove/i.test(message);
    if (isDelete) {
        newConfirmBtn.className = 'btn btn-danger';
        newConfirmBtn.innerText = 'Delete';
    } else {
        newConfirmBtn.className = 'btn btn-primary';
        newConfirmBtn.innerText = 'Confirm';
    }

    newConfirmBtn.addEventListener('click', () => {
        closeConfirmModal();
        callback();
    });

    document.getElementById('confirmModal').classList.add('show');
}

function closeConfirmModal() {
    document.getElementById('confirmModal').classList.remove('show');
}

let adminOverviewSearch = '';
let adminOverviewDocFilter = 'all';
let adminSelectedOverviewDocId = null;
let adminCalendarMode = 'month';
let adminCalendarAnchor = new Date();

function normalizeDocNameKey(raw) {
    return String(raw || '')
        .toLowerCase()
        .replace(/^dr\.?\s*/i, '')
        .replace(/,?\s*md$/i, '')
        .replace(/\s+/g, ' ')
        .trim();
}

function formatAdminTime12(rawTime, fallback) {
    if (!rawTime) return fallback;
    const parts = String(rawTime).trim().split(':');
    if (parts.length < 2) return fallback;
    let h = parseInt(parts[0], 10);
    const m = parts[1];
    if (isNaN(h)) return fallback;
    const ampm = h >= 12 ? 'PM' : 'AM';
    h = h % 12 || 12;
    return `${h}:${m} ${ampm}`;
}

function formatAdminCompactHour(rawTime, fallbackHour) {
    if (!rawTime) return fallbackHour;
    const parts = String(rawTime).trim().split(':');
    if (parts.length < 2) return fallbackHour;
    let h = parseInt(parts[0], 10);
    const m = parseInt(parts[1], 10);
    if (isNaN(h)) return fallbackHour;
    const ampm = h >= 12 ? 'PM' : 'AM';
    h = h % 12 || 12;
    return (!isNaN(m) && m !== 0) ? `${h}:${String(m).padStart(2, '0')}${ampm}` : `${h}${ampm}`;
}

function formatDoctorHoursLabel(doc) {
    const start = formatAdminTime12(doc?.schedule_start, '8:00 AM');
    const end = formatAdminTime12(doc?.schedule_end, '5:00 PM');
    return `${start} – ${end}`;
}

function formatDoctorCompactHours(doc) {
    const start = formatAdminCompactHour(doc?.schedule_start, '8AM');
    const end = formatAdminCompactHour(doc?.schedule_end, '5PM');
    return `${start}–${end}`;
}

function getShortDoctorName(rawName) {
    const clean = String(rawName || 'Physician').replace(/^Dr\.?\s*/i, '').replace(/,?\s*MD$/i, '').trim();
    const parts = clean.split(/\s+/);
    const last = parts.length > 1 ? parts[parts.length - 1] : clean;
    return `Dr. ${last}`;
}

function getDoctorBookedOnDate(docName, isoDate) {
    const docKey = normalizeDocNameKey(docName);
    if (!docKey || !isoDate) return 0;
    return (cachedPMIPatients || []).filter(p => {
        const st = (p.status || '').toLowerCase().trim();
        if (['cancelled', 'rejected', 'no-show', 'transferred'].includes(st)) return false;
        const pDate = String(p.appointment_date || p.created_at || '').split(' ')[0];
        if (pDate !== isoDate) return false;
        return normalizeDocNameKey(p.assigned_doctor) === docKey;
    }).length;
}

let execAdminStatsPayload = null;

function setOverviewChartRange(range) {
    const filterEl = document.getElementById('chart-filter');
    if (filterEl) filterEl.value = range;
    document.querySelectorAll('#adminOverviewExecPanel .adm-ov-pill[data-range]').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.range === range);
    });
    loadChartData();
}

function jumpToPendingPMI() {
    navigateToAdminView('pmi');
    const statusSel = document.getElementById('pmiStatusFilter');
    if (statusSel) {
        statusSel.value = 'for_approval';
        filterPMITable();
    }
}

function renderAdminExecOverview(res) {
    if (!res) return;
    execAdminStatsPayload = res;

    const setVal = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.textContent = String(val);
    };

    const ts = res.today_summary || { total: 0, waiting: 0, ongoing: 0, completed: 0 };
    setVal('admKpiAppointments', ts.total);
    setVal('admKpiTodaySub', `${ts.waiting} waiting \u2022 ${ts.ongoing} ongoing \u2022 ${ts.completed} done`);

    const pendingTotal = Number(res.pending_actions || 0);
    setVal('admKpiPending', pendingTotal);
    setVal('admKpiPendingSub', `${res.pending_appointments || 0} appointments \u2022 ${res.pending_inquiries || 0} inquiries`);
    const pendingCard = document.getElementById('admKpiPendingCard');
    if (pendingCard) pendingCard.classList.toggle('has-attention', pendingTotal > 0);

    const ds = res.doctor_stats || {};
    const availDocs = Number(ds.available_doctors || 0);
    const totalDocs = Number(ds.total_doctors || 0);
    setVal('admKpiDoctors', availDocs);
    setVal('admKpiDoctorsSub', `of ${totalDocs} doctors available (IN)`);

    const caps = Array.isArray(res.department_capacity_today) ? res.department_capacity_today : [];
    const totalBooked = caps.reduce((a, c) => a + Number(c.booked || 0), 0);
    const totalLimit = caps.reduce((a, c) => a + Number(c.limit || 0), 0);
    const pct = totalLimit > 0 ? Math.min(100, Math.round((totalBooked / totalLimit) * 100)) : 0;
    setVal('admKpiCapacity', `${pct}%`);
    setVal('admKpiCapacitySub', `${totalBooked} / ${totalLimit} slots \u2022 ${res.active_staff || 0} active staff`);

    renderOverviewCapacity(caps);
}

function renderOverviewCapacity(caps) {
    const wrap = document.getElementById('admDeptCapacityList');
    if (!wrap) return;
    if (!caps || caps.length === 0) {
        wrap.innerHTML = '<div class="adm-exec-empty">No department limits configured.</div>';
        return;
    }
    wrap.innerHTML = caps.map(c => {
        const pct = Math.min(100, Math.max(0, Number(c.percent || 0)));
        const tone = pct >= 95 ? 'is-full' : (pct >= 75 ? 'is-warn' : '');
        return `
            <div class="adm-cap-item">
                <div class="adm-cap-row">
                    <span class="adm-cap-name">${escapeHtml(c.name)}</span>
                    <span class="adm-cap-meta">${c.booked} / ${c.limit} (${pct}%)</span>
                </div>
                <div class="adm-cap-track" role="progressbar" aria-valuenow="${pct}" aria-valuemin="0" aria-valuemax="100" aria-label="${escapeHtml(c.name)} capacity">
                    <div class="adm-cap-fill ${tone}" style="width: ${pct}%;"></div>
                </div>
            </div>
        `;
    }).join('');
}

function updateAdminOverviewKPIs() {
    if (execAdminStatsPayload) {
        renderAdminExecOverview(execAdminStatsPayload);
    }
}

function toggleAdminDocMenu(e) {
    if (e) e.stopPropagation();
    const menu = document.getElementById('admDocFilterMenu');
    if (menu) menu.classList.toggle('open');
}

function setAdminOverviewDocFilter(mode) {
    adminOverviewDocFilter = mode || 'all';
    const menu = document.getElementById('admDocFilterMenu');
    if (menu) menu.classList.remove('open');
    renderAdminOverviewDoctors();
    renderAdminOverviewCalendar();
}

function toggleOverviewDoctorSelection(docId) {
    const numId = parseInt(docId, 10) || null;
    adminSelectedOverviewDocId = (adminSelectedOverviewDocId === numId) ? null : numId;
    renderAdminOverviewDoctors();
    renderAdminOverviewCalendar();
}

function getFilteredOverviewDoctors() {
    let docs = (allDoctorsList || []).slice();
    if (adminOverviewDocFilter === 'in') {
        docs = docs.filter(d => normalizeDocStatus(d.status) === 'in');
    }
    if (adminOverviewSearch) {
        docs = docs.filter(d =>
            (d.name || '').toLowerCase().includes(adminOverviewSearch) ||
            (d.specialization || '').toLowerCase().includes(adminOverviewSearch) ||
            (d.department || '').toLowerCase().includes(adminOverviewSearch)
        );
    }
    return docs;
}

function renderAdminOverviewDoctors() {
    const container = document.getElementById('adminOverviewDoctorsList');
    if (!container) return;

    const todayStr = toIsoDate(new Date());
    const docs = getFilteredOverviewDoctors();

    if (!docs.length) {
        container.innerHTML = '<div class="admin-ov-empty">No physicians match the selected filter.</div>';
        return;
    }

    container.innerHTML = docs.map(d => {
        const rawName = String(d.name || 'Physician').trim();
        const displayName = rawName.startsWith('Dr.') ? rawName : `Dr. ${rawName}`;
        const specText = (d.specialization || formatDeptTitleCase(d.department || 'General Practice')).trim();
        const isIn = normalizeDocStatus(d.status) === 'in';
        const totalSlots = Math.min(100, Math.max(1, parseInt(d.total_slots, 10) || 20));
        const bookedToday = getDoctorBookedOnDate(rawName, todayStr);
        const remaining = Math.max(0, totalSlots - bookedToday);
        const docId = parseInt(d.id, 10) || 0;
        const isSelected = adminSelectedOverviewDocId === docId;

        const docAvatarHtml = d.profile_image
            ? `<img src="${escapeHtml(d.profile_image)}" alt="${escapeHtml(displayName)}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;" onerror="this.parentElement.innerHTML='${escapeHtml(getPersonInitials(rawName, 'DR'))}'">`
            : `<svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="8" r="3.6" fill="#ffffff"></circle>
                            <path d="M5.5 20c0-3.2 2.9-5.5 6.5-5.5s6.5 2.3 6.5 5.5H5.5z" fill="#ffffff"></path>
                            <circle cx="16.5" cy="14" r="3.2" fill="#334155"></circle>
                            <path d="M16.5 12.2v3.6M14.7 14h3.6" stroke="#ffffff" stroke-width="1.3" stroke-linecap="round"></path>
                        </svg>`;

        return `
            <div class="admin-ov-doc-row${isSelected ? ' is-selected' : ''}" onclick="toggleOverviewDoctorSelection(${docId})" title="${escapeHtml(formatDoctorHoursLabel(d))} · ${bookedToday}/${totalSlots} booked (Click to filter schedule)">
                <div class="admin-ov-doc-left">
                    <div class="admin-ov-doc-avatar" style="overflow:hidden;" aria-hidden="true">
                        ${docAvatarHtml}
                    </div>
                    <div class="admin-ov-doc-meta">
                        <div class="admin-ov-doc-name">${escapeHtml(displayName)}</div>
                        <div class="admin-ov-doc-spec">${escapeHtml(specText)}</div>
                    </div>
                </div>
                <span class="admin-ov-doc-count${isIn ? '' : ' is-out'}">${isIn ? remaining : 'OUT'}</span>
            </div>
        `;
    }).join('');
}

let adminCalDeptFilter = 'all';

function matchesCalDeptFilter(dept) {
    if (!adminCalDeptFilter || adminCalDeptFilter === 'all') return true;
    const raw = String(dept || '').toLowerCase();
    if (adminCalDeptFilter === 'neurology') return raw.includes('neuro');
    if (adminCalDeptFilter === 'psychiatry') return raw.includes('psych') || raw.includes('opu');
    return raw.includes('general') || raw.includes('medical') || (!raw.includes('neuro') && !raw.includes('psych') && !raw.includes('opu'));
}

function setAdminCalDeptFilter(val) {
    adminCalDeptFilter = val || 'all';
    renderAdminOverviewCalendar();
}

function jumpAdminCalendarToday() {
    adminCalendarAnchor = new Date();
    renderAdminOverviewCalendar();
}

function getAppointmentsForCalendarDate(isoDate) {
    if (!isoDate) return [];
    return (cachedPMIPatients || []).filter(p => {
        const st = String(p.status || '').toLowerCase().trim();
        if (['cancelled', 'rejected', 'no-show', 'transferred'].includes(st)) return false;
        const pDate = String(p.appointment_date || '').split(' ')[0];
        if (pDate !== isoDate) return false;
        return matchesCalDeptFilter(p.department);
    });
}

function shiftAdminCalendar(delta) {
    const d = new Date(adminCalendarAnchor.getTime());
    if (adminCalendarMode === 'month') {
        d.setDate(1);
        d.setMonth(d.getMonth() + delta);
    } else if (adminCalendarMode === 'week') {
        d.setDate(d.getDate() + delta * 7);
    } else {
        d.setDate(d.getDate() + delta);
    }
    adminCalendarAnchor = d;
    renderAdminOverviewCalendar();
}

function setAdminCalendarMode(mode) {
    adminCalendarMode = mode || 'month';
    document.querySelectorAll('.admin-cal-mode-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.mode === adminCalendarMode);
    });
    renderAdminOverviewCalendar();
}

function openAdminCalendarDay(isoDate) {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(String(isoDate || ''))) return;
    adminCalendarAnchor = new Date(isoDate + 'T00:00:00');
    setAdminCalendarMode('day');
}

function toIsoDate(d) {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
}

function getDoctorsForCalendarDate(dateObj, includeOut = false) {
    if (!dateObj || dateObj.getDay() === 0) return [];
    let docs = getFilteredOverviewDoctors().filter(d => matchesCalDeptFilter(d.department));
    if (adminSelectedOverviewDocId) {
        docs = docs.filter(d => (parseInt(d.id, 10) || 0) === adminSelectedOverviewDocId);
    }
    if (!includeOut) {
        docs = docs.filter(d => normalizeDocStatus(d.status) === 'in');
    } else {
        docs.sort((a, b) => {
            const aIn = normalizeDocStatus(a.status) === 'in' ? 0 : 1;
            const bIn = normalizeDocStatus(b.status) === 'in' ? 0 : 1;
            if (aIn !== bIn) return aIn - bIn;
            return String(a.name || '').localeCompare(String(b.name || ''));
        });
    }
    return docs;
}

function getDocDeptToneClass(dept) {
    const raw = String(dept || '').toLowerCase();
    if (raw.includes('neuro')) return 'is-dept-neurology';
    if (raw.includes('psych') || raw.includes('opu')) return 'is-dept-psychiatry';
    return 'is-dept-general';
}

function formatCompactSlotTag(p) {
    const slot = String(p.preferred_time_slot || '').toLowerCase();
    if (slot.includes('afternoon')) return 'PM';
    if (slot.includes('morning')) return 'AM';
    const tm = String(p.scheduled_time || '').trim();
    if (/^\d{2}:\d{2}/.test(tm)) {
        const hr = parseInt(tm.slice(0, 2), 10);
        return hr >= 12 ? 'PM' : 'AM';
    }
    return '';
}

function showAdminCalTooltip(el) {
    if (!el) return;
    let tip = document.getElementById('adminCalDocTooltip');
    if (!tip) {
        tip = document.createElement('div');
        tip.id = 'adminCalDocTooltip';
        tip.className = 'admin-cal-doc-popover';
        tip.setAttribute('role', 'tooltip');
        document.body.appendChild(tip);
    }
    const name = el.getAttribute('data-doc-name') || '';
    const date = el.getAttribute('data-doc-date') || '';
    const time = el.getAttribute('data-doc-time') || '';
    tip.innerHTML = `
        <div class="admin-cal-popover-name">${escapeHtml(name)}</div>
        <div class="admin-cal-popover-meta">${escapeHtml(date)}</div>
        <div class="admin-cal-popover-meta">${escapeHtml(time)}</div>
    `;
    tip.classList.add('is-visible');

    const rect = el.getBoundingClientRect();
    const tipRect = tip.getBoundingClientRect();
    const top = rect.top - tipRect.height - 8 > 8
        ? rect.top - tipRect.height - 8
        : rect.bottom + 8;
    const left = Math.min(
        window.innerWidth - tipRect.width - 12,
        Math.max(12, rect.left + rect.width / 2 - tipRect.width / 2)
    );
    tip.style.top = `${top}px`;
    tip.style.left = `${left}px`;
}

function hideAdminCalTooltip() {
    const tip = document.getElementById('adminCalDocTooltip');
    if (tip) tip.classList.remove('is-visible');
}

function renderAdminOverviewCalendar() {
    const wrap = document.getElementById('adminOverviewCalendarWrap');
    const titleEl = document.getElementById('admCalTitle');
    if (!wrap || !titleEl) return;
    hideAdminCalTooltip();

    const anchor = adminCalendarAnchor;
    const todayIso = toIsoDate(new Date());
    const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

    if (adminCalendarMode === 'month') {
        titleEl.textContent = anchor.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
        const year = anchor.getFullYear();
        const month = anchor.getMonth();
        const firstDay = new Date(year, month, 1);
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const startOffset = firstDay.getDay();
        const totalRows = Math.ceil((startOffset + daysInMonth) / 7);
        const gridStart = new Date(year, month, 1 - startOffset);

        let rowsHtml = '';
        for (let r = 0; r < totalRows; r++) {
            let cellsHtml = '';
            for (let c = 0; c < 7; c++) {
                const cellDate = new Date(gridStart.getFullYear(), gridStart.getMonth(), gridStart.getDate() + (r * 7 + c));
                const iso = toIsoDate(cellDate);
                const isOtherMonth = cellDate.getMonth() !== month;
                const isToday = iso === todayIso;
                const isSunday = cellDate.getDay() === 0;
                const schedDocs = (isOtherMonth || isSunday) ? [] : getDoctorsForCalendarDate(cellDate, false);
                const dayAppts = (isOtherMonth || isSunday) ? [] : getAppointmentsForCalendarDate(iso);
                const schedDateLabel = cellDate.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });

                let rightBadge = '';
                if (!isOtherMonth && isSunday) {
                    rightBadge = '<span class="admin-cal-closed">Closed</span>';
                } else if (dayAppts.length > 0) {
                    rightBadge = `<span class="admin-cal-appt-badge">${dayAppts.length} ${dayAppts.length === 1 ? 'visit' : 'visits'}</span>`;
                }

                let bodyContent = '';
                if (!isOtherMonth && !isSunday) {
                    if (dayAppts.length > 0) {
                        const apptPills = dayAppts.slice(0, 2).map(p => {
                            const tone = getDocDeptToneClass(p.department);
                            const pName = String(p.full_name || 'Patient').trim();
                            const shortParts = pName.split(/\s+/);
                            const compactName = shortParts.length > 1 ? `${shortParts[0]} ${shortParts[shortParts.length - 1][0]}.` : pName;
                            const slotTag = formatCompactSlotTag(p);
                            const deptLabel = formatDeptTitleCase(p.department || 'General Medical');
                            const statusLabel = String(p.status || 'pending').replace(/_/g, ' ').toUpperCase();
                            return `
                                <div class="admin-cal-event-chip ${tone}" onclick="event.stopPropagation(); openAdminCalendarDay('${iso}')" data-doc-name="${escapeHtml(pName)}" data-doc-date="${escapeHtml(deptLabel + ' · ' + schedDateLabel)}" data-doc-time="${escapeHtml((p.preferred_time_slot || 'Scheduled') + ' · ' + statusLabel)}" onmouseenter="showAdminCalTooltip(this)" onmouseleave="hideAdminCalTooltip()">
                                    <span class="admin-cal-event-name">${escapeHtml(compactName)}</span>
                                    ${slotTag ? `<span class="admin-cal-event-slot">${escapeHtml(slotTag)}</span>` : ''}
                                </div>
                            `;
                        }).join('');
                        const moreAppts = dayAppts.length > 2
                            ? `<span class="admin-cal-more-link">+${dayAppts.length - 2} more</span>`
                            : '';
                        bodyContent = `<div class="admin-cal-events-stack">${apptPills}${moreAppts}</div>`;
                    }

                    if (schedDocs.length > 0 && (dayAppts.length > 0 || isToday)) {
                        const hasGen = schedDocs.some(d => getDocDeptToneClass(d.department) === 'is-dept-general');
                        const hasNeu = schedDocs.some(d => getDocDeptToneClass(d.department) === 'is-dept-neurology');
                        const hasPsy = schedDocs.some(d => getDocDeptToneClass(d.department) === 'is-dept-psychiatry');
                        const dots = [
                            hasGen ? '<span class="admin-cal-mini-dot is-dept-general"></span>' : '',
                            hasNeu ? '<span class="admin-cal-mini-dot is-dept-neurology"></span>' : '',
                            hasPsy ? '<span class="admin-cal-mini-dot is-dept-psychiatry"></span>' : ''
                        ].join('');
                        bodyContent += `
                            <div class="admin-cal-duty-meta">
                                <span class="admin-cal-mini-dots" aria-hidden="true">${dots}</span>
                                <span>${schedDocs.length} MD on duty</span>
                            </div>
                        `;
                    }
                }

                const cellClick = (!isOtherMonth && !isSunday) ? `onclick="openAdminCalendarDay('${iso}')"` : '';
                const hasEventsCls = dayAppts.length > 0 ? ' has-appts' : '';

                cellsHtml += `
                    <td class="${isOtherMonth ? 'is-other-month' : ''}${isSunday && !isOtherMonth ? ' is-sunday' : ''}${isToday ? ' is-today' : ''}${hasEventsCls}" ${cellClick}>
                        <div class="admin-cal-cell-inner">
                            <div class="admin-cal-day-head-row">
                                <span class="admin-cal-day-num">${cellDate.getDate()}</span>
                                ${rightBadge}
                            </div>
                            <div class="admin-cal-cell-body">${bodyContent}</div>
                        </div>
                    </td>
                `;
            }
            rowsHtml += `<tr>${cellsHtml}</tr>`;
        }

        wrap.innerHTML = `
            <table class="admin-cal-table">
                <thead>
                    <tr>${dayNames.map((n, idx) => `<th class="${idx === 0 ? 'is-sun-head' : ''}">${n}</th>`).join('')}</tr>
                </thead>
                <tbody>${rowsHtml}</tbody>
            </table>
        `;
    } else if (adminCalendarMode === 'week') {
        const weekStart = new Date(anchor.getFullYear(), anchor.getMonth(), anchor.getDate() - anchor.getDay());
        const weekEnd = new Date(weekStart.getFullYear(), weekStart.getMonth(), weekStart.getDate() + 6);
        titleEl.textContent = `${weekStart.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} – ${weekEnd.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}`;

        let docs = getFilteredOverviewDoctors().filter(d => matchesCalDeptFilter(d.department));
        if (adminSelectedOverviewDocId) {
            docs = docs.filter(d => (parseInt(d.id, 10) || 0) === adminSelectedOverviewDocId);
        }

        const weekDays = [];
        for (let c = 0; c < 7; c++) {
            const d = new Date(weekStart.getFullYear(), weekStart.getMonth(), weekStart.getDate() + c);
            weekDays.push({
                date: d,
                iso: toIsoDate(d),
                dayName: dayNames[c],
                dayNum: d.getDate(),
                isToday: toIsoDate(d) === todayIso,
                isSunday: c === 0
            });
        }

        if (!docs.length) {
            wrap.innerHTML = '<div class="admin-ov-empty" style="padding:48px 16px;">No physician schedules match the current filter.</div>';
            return;
        }

        const headerCols = `<th>Physician</th>` + weekDays.map(wd =>
            `<th class="${wd.isToday ? 'is-today-col' : ''}" onclick="${wd.isSunday ? '' : `openAdminCalendarDay('${wd.iso}')`}" style="${wd.isSunday ? '' : 'cursor:pointer;'}">
                <span class="admin-wk-head-day">${wd.dayName}</span>
                <span class="admin-wk-head-num">${wd.dayNum}</span>
            </th>`
        ).join('');

        const bodyRows = docs.map(d => {
            const isIn = normalizeDocStatus(d.status) === 'in';
            const compactHours = formatDoctorCompactHours(d);
            const rawName = String(d.name || 'Physician').trim();
            const displayName = rawName.startsWith('Dr.') ? rawName : `Dr. ${rawName}`;

            const dayCells = weekDays.map(wd => {
                if (wd.isSunday) {
                    return `<td class="is-sunday-col">Closed</td>`;
                }
                const toneCls = isIn ? ` ${getDocDeptToneClass(d.department)}` : '';
                return `
                    <td class="${wd.isToday ? 'is-today-col' : ''}">
                        <span class="admin-cal-shift-pill${toneCls}${isIn ? '' : ' is-out'}" title="${escapeHtml(displayName + ' · ' + formatDoctorHoursLabel(d))}">${isIn ? escapeHtml(compactHours) : 'Off'}</span>
                    </td>
                `;
            }).join('');

            return `
                <tr>
                    <td class="admin-cal-week-doc-cell">
                        <div class="table-person-cell" style="gap:10px;">
                            ${renderEntityAvatarHtml(d.profile_image, rawName, 'doctor')}
                            <div style="min-width:0;">
                                <div class="admin-cal-week-doc-name">${escapeHtml(displayName)}</div>
                                <div class="admin-cal-week-doc-dept">${escapeHtml(formatDeptTitleCase(d.department))}</div>
                            </div>
                        </div>
                    </td>
                    ${dayCells}
                </tr>
            `;
        }).join('');

        wrap.innerHTML = `
            <div class="admin-cal-week-wrap">
                <table class="admin-cal-week-table">
                    <thead><tr>${headerCols}</tr></thead>
                    <tbody>${bodyRows}</tbody>
                </table>
            </div>
        `;
    } else {
        const iso = toIsoDate(anchor);
        const isSunday = anchor.getDay() === 0;
        titleEl.textContent = anchor.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });

        if (isSunday) {
            wrap.innerHTML = `
                <div class="admin-cal-day-header">
                    <div class="admin-cal-day-header-left">
                        <button type="button" class="admin-cal-back-btn" onclick="setAdminCalendarMode('month')">&larr; Month</button>
                        <span class="admin-cal-day-date">${anchor.toLocaleDateString('en-US', { weekday: 'long', month: 'short', day: 'numeric' })}</span>
                    </div>
                    <span class="admin-cal-day-pill-closed">Outpatient Clinics Closed</span>
                </div>
                <div class="admin-ov-empty" style="padding:48px 16px;">Hospital outpatient clinics operate Monday to Saturday and are closed on Sundays.</div>
            `;
            return;
        }

        const docs = getDoctorsForCalendarDate(anchor, true);
        const dayAppts = getAppointmentsForCalendarDate(iso);
        const availCount = docs.filter(d => normalizeDocStatus(d.status) === 'in').length;

        const apptsSection = dayAppts.length
            ? `
                <div class="admin-cal-day-sec-title">Scheduled Patient Visits (${dayAppts.length})</div>
                <div class="admin-cal-day-appts-grid">
                    ${dayAppts.map(p => {
                const tone = getDocDeptToneClass(p.department);
                const st = String(p.status || 'pending').replace(/_/g, ' ');
                return `
                            <div class="admin-cal-day-appt-card ${tone}" onclick="openPatientDetailsModal('${escapeHtml(p.patient_id)}')">
                                <div class="admin-cal-day-appt-top">
                                    <span class="admin-cal-day-appt-name">${escapeHtml(p.full_name || 'Patient')}</span>
                                    <span class="admin-cal-day-appt-ref">${escapeHtml(p.reference_no || p.patient_id || '')}</span>
                                </div>
                                <div class="admin-cal-day-appt-sub">
                                    <span>${escapeHtml(formatDeptTitleCase(p.department))} &middot; ${escapeHtml(p.preferred_time_slot || 'Morning')}</span>
                                    <span class="admin-cal-day-appt-st">${escapeHtml(st)}</span>
                                </div>
                            </div>
                        `;
            }).join('')}
                </div>
            `
            : '';

        const docsSection = docs.length
            ? `
                <div class="admin-cal-day-sec-title">Physician Roster (${availCount} of ${docs.length} on duty)</div>
                <div class="admin-cal-day-list">
                    ${docs.map(d => {
                const isIn = normalizeDocStatus(d.status) === 'in';
                const rawName = String(d.name || 'Physician').trim();
                const displayName = rawName.startsWith('Dr.') ? rawName : `Dr. ${rawName}`;
                const totalSlots = Math.min(100, Math.max(1, parseInt(d.total_slots, 10) || 20));
                const booked = getDoctorBookedOnDate(rawName, iso);
                const remaining = Math.max(0, totalSlots - booked);

                return `
                            <div class="admin-cal-day-item${isIn ? '' : ' is-out-row'}">
                                <div class="admin-cal-day-left">
                                    ${renderEntityAvatarHtml(d.profile_image, rawName, 'doctor')}
                                    <div>
                                        <div style="font-weight:600; font-size:0.86rem; color:#0f172a;">${escapeHtml(displayName)}</div>
                                        <div style="font-size:0.74rem; color:#64748b; margin-top:2px;">${escapeHtml(formatDeptTitleCase(d.department))} &bull; ${escapeHtml(d.specialization || 'General Practice')} &bull; ${remaining} / ${totalSlots} slots free</div>
                                    </div>
                                </div>
                                <div class="admin-cal-day-right">
                                    <span class="admin-cal-day-hours">${escapeHtml(formatDoctorHoursLabel(d))}</span>
                                    <span class="admin-cal-day-status ${isIn ? 'is-in' : 'is-out'}">${isIn ? 'On Duty' : 'Off'}</span>
                                </div>
                            </div>
                        `;
            }).join('')}
                </div>
            `
            : '<div class="admin-ov-empty" style="padding:36px 16px;">No physician schedules found for this filter.</div>';

        wrap.innerHTML = `
            <div class="admin-cal-day-header">
                <div class="admin-cal-day-header-left">
                    <button type="button" class="admin-cal-back-btn" onclick="setAdminCalendarMode('month')">&larr; Month</button>
                    <span class="admin-cal-day-date">${anchor.toLocaleDateString('en-US', { weekday: 'long', month: 'short', day: 'numeric', year: 'numeric' })}</span>
                </div>
                <span class="admin-cal-day-summary-meta">${dayAppts.length} ${dayAppts.length === 1 ? 'visit' : 'visits'} &middot; ${availCount} MD on duty</span>
            </div>
            ${apptsSection}
            ${docsSection}
        `;
    }
}

async function loadDashboard() {
    try {
        const res = await fetch('/sdn/api/admin-stats.php').then(r => r.json());
        if (res.success) {
            updateAdminNotifications(res);
            renderAdminExecOverview(res);
        }
    } catch (e) { console.error(e); }

    updateAdminOverviewKPIs();
    renderAdminOverviewDoctors();
    const calPanel = document.getElementById('adminOverviewCalPanel');
    if (calPanel && !calPanel.hidden) {
        renderAdminOverviewCalendar();
    }
    loadChartData();
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
                    ? `<span class="badge badge-yellow" style="margin-left:6px;">Inquiry</span>`
                    : '';

                let safeId = escapeHtml(p.patient_id || p.id || '');
                let safeName = escapeHtml(p.full_name);
                let safeDept = escapeHtml(p.department || 'N/A');
                let prevLine = p.previous_appointment_date
                    ? `<div class="date-stack-sub">Previous ${escapeHtml(p.previous_appointment_date)}${p.previous_appointment_time ? ' · ' + escapeHtml(p.previous_appointment_time) : ''}</div>`
                    : '';
                let safeAppDate = isInquiry
                    ? `<span class="badge badge-yellow">Inquiry · ${escapeHtml(p.mode_of_inquiry || 'Online')}</span>`
                    : (`<div class="date-stack">${formatStackedDate(p.appointment_date, p.preferred_time_slot || p.scheduled_time)}${prevLine}</div>`);
                let safeStatus = escapeHtml(titleStatus(p.status));

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
    } catch (e) {
        console.error(e);
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; color:#ef4444;">Error loading patient records.</td></tr>';
    }
}

let patientsChartInstance = null;

function resetChartFilter() {
    const filterEl = document.getElementById('chart-filter');
    if (filterEl) filterEl.value = 'year';
    loadChartData();
}

async function loadChartData() {
    try {
        const canvasEl = document.getElementById('patientsChart');
        if (!canvasEl) return;
        const filterEl = document.getElementById('chart-filter');
        const filterVal = filterEl ? filterEl.value : 'year';

        const res = await fetch(`/sdn/api/admin-stats.php?chart=${filterVal}`).then(r => r.json());
        if (res.success && res.chart_labels) {
            const ctx = canvasEl.getContext('2d');

            if (patientsChartInstance) {
                patientsChartInstance.destroy();
            }

            const ds = res.chart_datasets || {};
            const genData = ds.general || res.chart_data || [];
            const neuroData = ds.neurology || [];
            const psychData = ds.psychiatry || [];

            patientsChartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: res.chart_labels,
                    datasets: [
                        {
                            label: 'General Medical',
                            data: genData,
                            borderColor: '#15803d',
                            backgroundColor: 'rgba(21, 128, 61, 0.08)',
                            borderWidth: 2.5,
                            pointBackgroundColor: '#15803d',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            fill: true,
                            tension: 0.35
                        },
                        {
                            label: 'Neurology',
                            data: neuroData,
                            borderColor: '#0284c7',
                            backgroundColor: 'rgba(2, 132, 199, 0.08)',
                            borderWidth: 2.5,
                            pointBackgroundColor: '#0284c7',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            fill: true,
                            tension: 0.35
                        },
                        {
                            label: 'Psychiatry',
                            data: psychData,
                            borderColor: '#7c3aed',
                            backgroundColor: 'rgba(124, 58, 237, 0.08)',
                            borderWidth: 2.5,
                            pointBackgroundColor: '#7c3aed',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            fill: true,
                            tension: 0.35
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            align: 'end',
                            labels: {
                                usePointStyle: true,
                                pointStyle: 'circle',
                                padding: 18,
                                font: {
                                    family: "'Inter', sans-serif",
                                    size: 12,
                                    weight: '600'
                                },
                                color: '#334155'
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0, color: '#64748b' },
                            grid: { color: '#f1f5f9' }
                        },
                        x: {
                            ticks: { color: '#64748b' },
                            grid: { display: false }
                        }
                    }
                }
            });
        }
    } catch (e) { console.error(e); }
}

// ── RBAC / STAFF MANAGEMENT ───────────────────────────────────────────────────
let allStaffList = [];

function formatDeptTitleCase(raw) {
    const cleaned = cleanDeptLabel(raw);
    if (!cleaned || cleaned.toLowerCase() === 'n/a') return 'N/A';
    const lower = cleaned.toLowerCase();
    if (lower === 'general' || lower === 'general medical') return 'General Medical';
    if (lower === 'neurology') return 'Neurology';
    if (lower === 'psychiatry') return 'Psychiatry';
    return cleaned.replace(/\w\S*/g, w => w.charAt(0).toUpperCase() + w.slice(1).toLowerCase());
}

function setRBACDeptCategory(dept) {
    const deptEl = document.getElementById('rbacDeptFilter');
    if (deptEl) deptEl.value = dept || 'all';
    document.querySelectorAll('#view-rbac .dept-category-tabs .dept-tab').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.dept === (dept || 'all'));
    });
    filterRBACTable();
}

function updateRBACCategoryCounts() {
    const list = allStaffList || [];
    const setCount = (id, count) => {
        const el = document.getElementById(id);
        if (el) el.textContent = count;
    };
    setCount('rbacCountAll', list.length);
    setCount('rbacCountGeneral', list.filter(s => (s.department || '').toLowerCase().includes('general')).length);
    setCount('rbacCountNeurology', list.filter(s => (s.department || '').toLowerCase().includes('neurology')).length);
    setCount('rbacCountPsychiatry', list.filter(s => (s.department || '').toLowerCase().includes('psychiatry')).length);
}

function renderRBACTable(list) {
    const tbody = document.getElementById('rbac-tbody');
    if (!tbody) return;

    if (!list || list.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="modern-table-empty">No staff members found matching filters.</td></tr>';
        syncTableScrollbars();
        return;
    }

    tbody.innerHTML = list.map(s => {
        let safeName = escapeHtml(s.full_name || s.username);
        let safeUser = escapeHtml(s.username);
        let cleanedDept = formatDeptTitleCase(s.department || 'N/A');
        let safeDept = escapeHtml(cleanedDept);
        let safeStatus = s.status === 'active' ? 'active' : 'inactive';
        let rawId = parseInt(s.id, 10) || 0;
        let rawNameAttr = encodeURIComponent(s.full_name || s.username);
        let rawUserAttr = encodeURIComponent(s.username);
        let rawDeptAttr = encodeURIComponent(cleanedDept);

        return `
        <tr>
            <td class="cell-primary">
                <div class="table-person-cell">
                    ${renderEntityAvatarHtml(s.profile_image, s.full_name || s.username, 'staff')}
                    <span>${safeName}</span>
                </div>
            </td>
            <td class="cell-mono">${safeUser}</td>
            <td><span class="table-dept-tag">${safeDept}</span></td>
            <td><span class="table-status-indicator ${safeStatus === 'active' ? 'is-active' : 'is-inactive'}"><span class="status-dot"></span>${safeStatus === 'active' ? 'Active' : 'Inactive'}</span></td>
            <td class="col-actions">
                <div class="row-actions">
                    <button type="button" class="table-action-btn table-action-icon" aria-label="Edit staff" title="Edit" onclick="openEditStaffModal(${rawId}, decodeURIComponent('${rawNameAttr}'), decodeURIComponent('${rawUserAttr}'), decodeURIComponent('${rawDeptAttr}'), '${safeStatus}')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                    </button>
                    <button type="button" class="table-action-btn table-action-icon danger" aria-label="Delete staff" title="Delete" onclick="deleteStaff(${rawId})">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                    </button>
                </div>
            </td>
        </tr>
    `}).join('');

    syncTableScrollbars();
}

function filterRBACTable() {
    const searchVal = (document.getElementById('rbacSearchInput')?.value || '').toLowerCase().trim();
    const deptVal = (document.getElementById('rbacDeptFilter')?.value || 'all').toLowerCase();
    const statusVal = (document.getElementById('rbacStatusFilter')?.value || 'all').toLowerCase();

    document.querySelectorAll('#view-rbac .dept-category-tabs .dept-tab').forEach(btn => {
        btn.classList.toggle('active', (btn.dataset.dept || 'all').toLowerCase() === deptVal);
    });
    updateRBACCategoryCounts();

    const total = (allStaffList || []).length;
    let list = allStaffList || [];

    // Filter by department
    if (deptVal !== 'all') {
        list = list.filter(s => (s.department || '').toLowerCase().includes(deptVal));
    }

    // Filter by status
    if (statusVal !== 'all') {
        list = list.filter(s => (s.status || '').toLowerCase() === statusVal);
    }

    // Filter by staff name or username
    if (searchVal) {
        list = list.filter(s =>
            (s.full_name || '').toLowerCase().includes(searchVal) ||
            (s.username || '').toLowerCase().includes(searchVal)
        );
    }

    const countEl = document.getElementById('rbacTableCount');
    if (countEl) {
        countEl.textContent = list.length === total
            ? `${total} staff member${total === 1 ? '' : 's'}`
            : `Showing ${list.length} of ${total} staff members`;
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

async function loadRBAC(silent = false) {
    if (typeof window.refreshAdminUsersView === 'function') {
        await window.refreshAdminUsersView(silent);
        return;
    }
    try {
        const res = await fetch('/sdn/api/staff.php').then(r => r.json());
        if (res.success && res.data) {
            allStaffList = res.data;
            filterRBACTable();
        }
    } catch (e) { console.error('Failed to load RBAC staff:', e); }
}

async function toggleStaffStatus(id, status) {
    customConfirm('Change Status', `Mark staff as ${status}?`, async () => {
        try {
            await fetch('/sdn/api/staff.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'toggle', user_id: id, status: status })
            });
            loadRBAC();
            // Also update the modal button if it is open for this same user
            if (document.getElementById('editStaffId').value == id) {
                updateEditStaffStatusBtn(status);
            }
        } catch (e) { console.error(e); }
    });
}

function openStaffModal() {
    document.getElementById('staffName').value = '';
    document.getElementById('staffUsername').value = '';
    document.getElementById('staffPass').value = '';
    const confirmEl = document.getElementById('staffPassConfirm');
    if (confirmEl) confirmEl.value = '';
    document.getElementById('staffDept').value = 'General Medical';
    setAvatarModalState('staffAdd', null, 'ST');
    document.getElementById('staffModal').classList.add('show');
}

function closeStaffModal() {
    const modal = document.getElementById('staffModal');
    if (modal) modal.classList.remove('show');
}

async function submitStaff() {
    const full_name = document.getElementById('staffName').value.trim();
    const username = document.getElementById('staffUsername').value.trim();
    const department = document.getElementById('staffDept').value;
    const password = document.getElementById('staffPass').value;
    const confirmPassword = document.getElementById('staffPassConfirm')?.value ?? password;

    if (!full_name || !username || !password || !department) return showToast('All required fields must be filled.', 'error');

    // Validate username complexity
    const usernameRegex = /^[a-zA-Z0-9_]{5,20}$/;
    if (!usernameRegex.test(username)) {
        return showToast('Username must be 5-20 characters long (letters, numbers, underscores only).', 'error');
    }

    if (password.length < 6) {
        return showToast('Password must be at least 6 characters long.', 'error');
    }
    if (password !== confirmPassword) {
        return showToast('Passwords do not match.', 'error');
    }

    try {
        const staffPayload = { action: 'create', full_name, username, department, password };
        if (avatarUploadState.staffAdd.imageData) {
            staffPayload.image_data = avatarUploadState.staffAdd.imageData;
        }
        const res = await fetch('/sdn/api/staff.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(staffPayload)
        }).then(r => r.json());

        if (res.success) {
            document.getElementById('staffModal').classList.remove('show');
            loadRBAC();
            showToast('Staff created successfully');
        } else {
            showToast(res.message, 'error');
        }
    } catch (e) { console.error(e); }
}

function openEditStaffModal(id, fullName, username, department, status) {
    const staffRec = (allStaffList || []).find(s => String(s.id) === String(id));
    document.getElementById('editStaffId').value = id;
    document.getElementById('editStaffName').value = fullName || '';
    document.getElementById('editStaffUsername').value = username || '';
    if (document.getElementById('editStaffDept')) {
        document.getElementById('editStaffDept').value = formatDeptTitleCase(department || 'General Medical');
    }
    document.getElementById('editStaffPass').value = '';
    const confirmEl = document.getElementById('editStaffPassConfirm');
    if (confirmEl) confirmEl.value = '';
    setAvatarModalState('staffEdit', staffRec?.profile_image || null, fullName || username);
    updateEditStaffStatusBtn(status);
    document.getElementById('editStaffModal').classList.add('show');
}

function closeEditStaffModal() {
    const modal = document.getElementById('editStaffModal');
    if (modal) modal.classList.remove('show');
}

function updateEditStaffStatusBtn(status) {
    const textEl = document.getElementById('editStaffStatusText');
    const pillEl = document.getElementById('editStaffStatusPill');
    const btn = document.getElementById('editStaffStatusBtn');
    const isActive = status === 'active';
    if (textEl) textEl.innerText = isActive ? 'Active' : 'Inactive';
    if (pillEl) pillEl.classList.toggle('is-inactive', !isActive);
    if (btn) {
        btn.innerText = isActive ? 'Disable' : 'Activate';
        btn.className = isActive ? 'staff-status-toggle-btn' : 'staff-status-toggle-btn is-activate';
        btn.onclick = () => toggleStaffStatus(document.getElementById('editStaffId').value, isActive ? 'inactive' : 'active');
    }
}

async function submitEditStaff() {
    const user_id = document.getElementById('editStaffId').value;
    const full_name = document.getElementById('editStaffName').value.trim();
    const department = document.getElementById('editStaffDept').value;
    const password = document.getElementById('editStaffPass').value;
    const confirmPassword = document.getElementById('editStaffPassConfirm')?.value ?? password;

    if (!full_name) {
        return showToast('Full name is required.', 'error');
    }

    if (password && password.length < 6) {
        return showToast('Password must be at least 6 characters long.', 'error');
    }
    if ((password || confirmPassword) && password !== confirmPassword) {
        return showToast('New password and confirmation do not match.', 'error');
    }

    try {
        const payload = { action: 'update_staff', user_id, full_name, department };
        if (password) {
            payload.password = password;
        }
        if (avatarUploadState.staffEdit.imageData) {
            payload.image_data = avatarUploadState.staffEdit.imageData;
        } else if (avatarUploadState.staffEdit.removeImage) {
            payload.remove_image = true;
        }

        const res = await fetch('/sdn/api/staff.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        }).then(r => r.json());

        if (res.success) {
            closeEditStaffModal();
            loadRBAC();
            showToast('Staff member updated successfully');
        } else {
            showToast(res.message || 'Update failed', 'error');
        }
    } catch (e) {
        console.error(e);
        showToast('Error updating staff member', 'error');
    }
}

async function deleteStaff(user_id) {
    customConfirm('Delete Staff', 'Are you sure you want to delete this staff account permanently?', async () => {
        try {
            const res = await fetch('/sdn/api/staff.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete', user_id })
            }).then(r => r.json());

            if (res.success) {
                document.getElementById('editStaffModal').classList.remove('show');
                loadRBAC();
                showToast('Staff account deleted');
            } else {
                showToast(res.message, 'error');
            }
        } catch (e) { console.error(e); }
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

function normalizeDocStatus(raw) {
    const s = String(raw || '').toLowerCase().trim();
    if (s === 'out' || s === 'leave' || s === 'on_leave' || s === 'on leave' || s === 'inactive' || s === 'on_break') {
        return 'out';
    }
    return 'in';
}

function formatDoctorStatusBadge(status) {
    const norm = normalizeDocStatus(status);
    if (norm === 'out') {
        return `<span class="table-status-indicator is-inactive"><span class="status-dot"></span>Out</span>`;
    }
    return `<span class="table-status-indicator is-active"><span class="status-dot"></span>Available</span>`;
}

function setDocDeptCategory(dept) {
    const deptEl = document.getElementById('docDeptFilter');
    if (deptEl) deptEl.value = dept || 'all';
    document.querySelectorAll('#view-doctors .dept-category-tabs .dept-tab').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.dept === (dept || 'all'));
    });
    filterDoctorsTable();
}

function updateDoctorCategoryCounts() {
    const list = allDoctorsList || [];
    const setCount = (id, count) => {
        const el = document.getElementById(id);
        if (el) el.textContent = count;
    };
    setCount('docCountAll', list.length);
    setCount('docCountGeneral', list.filter(d => (d.department || '').toLowerCase().includes('general')).length);
    setCount('docCountNeurology', list.filter(d => (d.department || '').toLowerCase().includes('neurology')).length);
    setCount('docCountPsychiatry', list.filter(d => (d.department || '').toLowerCase().includes('psychiatry')).length);
}

function renderDoctorsTable(list) {
    const tbody = document.getElementById('doctors-tbody');
    if (!tbody) return;

    if (!list || list.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="modern-table-empty">No doctors found matching filters.</td></tr>';
        syncTableScrollbars();
        return;
    }

    tbody.innerHTML = list.map(d => {
        const safeName = escapeHtml(d.name || 'Unnamed Physician');
        const safeSpec = escapeHtml(d.specialization || 'General');
        const deptClean = formatDeptTitleCase(d.department || 'N/A');
        const safeDept = escapeHtml(deptClean);
        const total = Math.min(100, Math.max(1, parseInt(d.total_slots, 10) || 20));
        const booked = Math.max(0, parseInt(d.booked_today, 10) || 0);
        const pct = Math.min(100, Math.round((booked / total) * 100));
        const barTone = pct >= 100 ? 'is-full' : (pct >= 80 ? 'is-warn' : '');
        const safeId = parseInt(d.id, 10) || 0;
        const isIn = normalizeDocStatus(d.status) === 'in';
        const nextStatus = isIn ? 'out' : 'in';

        const metaParts = [];
        if (d.title && !(d.name || '').toLowerCase().includes(d.title.toLowerCase())) {
            metaParts.push(d.title);
        }
        if (d.license_number) {
            metaParts.push(`PRC #${d.license_number}`);
        }
        const subMetaHtml = metaParts.length
            ? `<div class="doc-sub-meta">${escapeHtml(metaParts.join(' · '))}</div>`
            : '';

        const statusBtnHtml = `<button type="button" class="doc-status-btn ${isIn ? 'is-in' : 'is-out'}" onclick="toggleDoctorDuty(${safeId}, '${nextStatus}')" title="Click to mark ${isIn ? 'Off Duty (OUT)' : 'On Duty (IN)'}">
            <span class="doc-status-dot"></span>
            <span>${isIn ? 'On Duty' : 'Off Duty'}</span>
        </button>`;

        const toggleIcon = isIn
            ? '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>'
            : '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>';

        return `
        <tr>
            <td class="cell-primary">
                <div class="table-person-cell">
                    ${renderEntityAvatarHtml(d.profile_image, d.name, 'doctor')}
                    <div class="doc-name-col">
                        <span class="doc-name-main">${safeName}</span>
                        ${subMetaHtml}
                    </div>
                </div>
            </td>
            <td class="cell-secondary">${safeSpec}</td>
            <td><span class="table-dept-tag">${safeDept}</span></td>
            <td class="cell-tabular">
                <div class="doc-load-wrap">
                    <div class="doc-load-nums"><span class="capacity-val">${booked}</span><span class="capacity-total"> / ${total}</span></div>
                    <div class="doc-load-track" aria-hidden="true"><div class="doc-load-fill ${barTone}" style="width:${pct}%;"></div></div>
                </div>
            </td>
            <td>${statusBtnHtml}</td>
            <td class="col-actions">
                <div class="row-actions">
                    <button type="button" class="table-action-btn table-action-icon" aria-label="Edit doctor" title="Edit doctor" onclick="openEditDoctorModal(${safeId})">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                    </button>
                    <button type="button" class="table-action-btn table-action-icon ${isIn ? '' : 'is-restore'}" aria-label="Toggle duty status" title="${isIn ? 'Mark Off Duty (OUT)' : 'Mark On Duty (IN)'}" onclick="toggleDoctorDuty(${safeId}, '${nextStatus}')">
                        ${toggleIcon}
                    </button>
                    <button type="button" class="table-action-btn table-action-icon danger" aria-label="Delete doctor" title="Delete doctor" onclick="deleteDoctor(${safeId})">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                    </button>
                </div>
            </td>
        </tr>
        `;
    }).join('');

    syncTableScrollbars();
}

async function toggleDoctorDuty(id, nextStatus) {
    try {
        const res = await fetch('/sdn/api/doctors.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'toggle_leave', id, status: nextStatus })
        }).then(r => r.json());
        if (res.success) {
            showToast(nextStatus === 'out' ? 'Doctor marked Off Duty (OUT)' : 'Doctor marked On Duty (IN)');
            await loadDoctors();
        } else {
            showToast(res.message || 'Failed to update doctor status', 'error');
        }
    } catch (e) {
        console.error('Toggle doctor duty failed:', e);
        showToast('Failed to update doctor status', 'error');
    }
}

function updateDoctorKPIs(summary) {
    const list = allDoctorsList || [];
    const total = summary?.total_doctors ?? list.length;
    const onDuty = summary?.on_duty ?? list.filter(d => normalizeDocStatus(d.status) === 'in').length;
    const offDuty = summary?.off_duty ?? Math.max(0, total - onDuty);
    const booked = summary?.booked_today ?? list.reduce((s, d) => s + (parseInt(d.booked_today, 10) || 0), 0);
    const cap = summary?.total_capacity ?? list.filter(d => normalizeDocStatus(d.status) === 'in').reduce((s, d) => s + (parseInt(d.total_slots, 10) || 20), 0);

    const elTotal = document.getElementById('docStatTotal');
    const elIn = document.getElementById('docStatIn');
    const elOut = document.getElementById('docStatOut');
    const elCap = document.getElementById('docStatCapacity');
    if (elTotal) elTotal.textContent = total;
    if (elIn) elIn.textContent = onDuty;
    if (elOut) elOut.textContent = offDuty;
    if (elCap) elCap.textContent = `${booked} / ${cap}`;
}

function filterDoctorsTable() {
    const searchVal = (document.getElementById('docSearchInput')?.value || '').toLowerCase().trim();
    const deptVal = (document.getElementById('docDeptFilter')?.value || 'all').toLowerCase();
    const statusVal = (document.getElementById('docStatusFilter')?.value || 'all').toLowerCase();

    document.querySelectorAll('#view-doctors .dept-category-tabs .dept-tab').forEach(btn => {
        btn.classList.toggle('active', (btn.dataset.dept || 'all').toLowerCase() === deptVal);
    });
    updateDoctorCategoryCounts();

    const total = (allDoctorsList || []).length;
    let list = allDoctorsList || [];

    // Filter by department
    if (deptVal !== 'all') {
        list = list.filter(d => (d.department || '').toLowerCase().includes(deptVal));
    }

    // Filter by status (IN / OUT)
    if (statusVal !== 'all') {
        list = list.filter(d => normalizeDocStatus(d.status) === statusVal);
    }

    // Filter by search query (name, PRC license, title, specialization, department)
    if (searchVal) {
        list = list.filter(d =>
            (d.name || '').toLowerCase().includes(searchVal) ||
            (d.license_number || '').toLowerCase().includes(searchVal) ||
            (d.title || '').toLowerCase().includes(searchVal) ||
            (d.specialization || '').toLowerCase().includes(searchVal) ||
            (d.department || '').toLowerCase().includes(searchVal)
        );
    }

    const countEl = document.getElementById('doctorsTableCount');
    if (countEl) {
        countEl.textContent = list.length === total
            ? `${total} doctor${total === 1 ? '' : 's'}`
            : `Showing ${list.length} of ${total} doctors`;
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
        const res = await fetch('/sdn/api/doctors.php?mode=manage').then(r => r.json());
        if (res.success && res.data) {
            allDoctorsList = res.data;
            updateDoctorKPIs(res.summary);
            filterDoctorsTable();
            renderAdminOverviewDoctors();
            renderAdminOverviewCalendar();
        }
    } catch (e) { console.error('Failed to load doctors:', e); }
}

function clearDoctorModalErrors() {
    ['docName', 'docTitle', 'docLicense', 'docDept', 'docSpec', 'docSlots', 'docStatus'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.classList.remove('input-error');
    });
}

function openAddDoctorModal() {
    clearDoctorModalErrors();
    document.getElementById('doctorModalTitle').innerText = 'Add Doctor';
    document.getElementById('editDoctorId').value = '';
    document.getElementById('docName').value = '';
    if (document.getElementById('docTitle')) document.getElementById('docTitle').value = '';
    if (document.getElementById('docLicense')) document.getElementById('docLicense').value = '';
    document.getElementById('docDept').value = 'general';
    document.getElementById('docSpec').value = '';
    document.getElementById('docSlots').value = '20';
    document.getElementById('docStatus').value = 'in';
    setAvatarModalState('doctor', null, 'DR');
    const delBtn = document.getElementById('btnDeleteDoctorModal');
    if (delBtn) delBtn.style.display = 'none';
    document.getElementById('doctorModal').classList.add('show');
}

function openEditDoctorModal(id) {
    const doc = allDoctorsList.find(d => d.id == id);
    if (!doc) return;

    clearDoctorModalErrors();
    document.getElementById('doctorModalTitle').innerText = 'Edit Doctor: ' + doc.name;
    document.getElementById('editDoctorId').value = doc.id;
    document.getElementById('docName').value = doc.name || '';
    if (document.getElementById('docTitle')) document.getElementById('docTitle').value = doc.title || '';
    if (document.getElementById('docLicense')) document.getElementById('docLicense').value = doc.license_number || '';
    const deptVal = (doc.department || 'general').toLowerCase();
    document.getElementById('docDept').value = deptVal.includes('psych') ? 'psychiatry' : (deptVal.includes('neuro') ? 'neurology' : 'general');
    document.getElementById('docSpec').value = doc.specialization || '';
    document.getElementById('docSlots').value = Math.min(100, Math.max(1, parseInt(doc.total_slots, 10) || 20));
    document.getElementById('docStatus').value = normalizeDocStatus(doc.status);
    setAvatarModalState('doctor', doc.profile_image || null, doc.name);
    const delBtn = document.getElementById('btnDeleteDoctorModal');
    if (delBtn) delBtn.style.display = 'inline-block';
    document.getElementById('doctorModal').classList.add('show');
}

function handleModalDeleteDoctor() {
    const id = document.getElementById('editDoctorId').value;
    if (id) {
        closeDoctorModal();
        deleteDoctor(id);
    }
}

function closeDoctorModal() {
    clearDoctorModalErrors();
    const modal = document.getElementById('doctorModal');
    if (modal) modal.classList.remove('show');
}

async function submitDoctorForm() {
    const id = document.getElementById('editDoctorId').value;
    const nameEl = document.getElementById('docName');
    const specEl = document.getElementById('docSpec');
    const deptEl = document.getElementById('docDept');
    const slotsEl = document.getElementById('docSlots');
    const statusEl = document.getElementById('docStatus');

    const name = (nameEl?.value || '').trim();
    const title = (document.getElementById('docTitle')?.value || '').trim();
    const license_number = (document.getElementById('docLicense')?.value || '').trim();
    const specialization = (specEl?.value || '').trim();
    const department = (deptEl?.value || '').trim();
    const slotsRaw = (slotsEl?.value || '').trim();
    const status = (statusEl?.value || 'in').trim();

    clearDoctorModalErrors();

    let hasEmptyError = false;
    if (!name) {
        if (nameEl) nameEl.classList.add('input-error');
        hasEmptyError = true;
    }
    if (!specialization) {
        if (specEl) specEl.classList.add('input-error');
        hasEmptyError = true;
    }
    if (!department) {
        if (deptEl) deptEl.classList.add('input-error');
        hasEmptyError = true;
    }
    if (!slotsRaw) {
        if (slotsEl) slotsEl.classList.add('input-error');
        hasEmptyError = true;
    }

    if (hasEmptyError) {
        return;
    }

    let total_slots = parseInt(slotsRaw, 10);
    if (isNaN(total_slots) || total_slots < 1 || total_slots > 100) {
        if (slotsEl) slotsEl.classList.add('input-error');
        return showToast('Total daily slots must be between 1 and 100.', 'error');
    }

    const isEdit = !!id;
    const payload = isEdit ? {
        action: 'update',
        id: id,
        name,
        title,
        license_number,
        specialization,
        department,
        total_slots,
        status
    } : {
        action: 'create',
        name,
        title,
        license_number,
        specialization,
        department,
        total_slots,
        status
    };

    if (avatarUploadState.doctor.imageData) {
        payload.image_data = avatarUploadState.doctor.imageData;
    } else if (isEdit && avatarUploadState.doctor.removeImage) {
        payload.remove_image = true;
    }

    try {
        const res = await fetch('/sdn/api/doctors.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        }).then(r => r.json());

        if (res.success) {
            closeDoctorModal();
            loadDoctors();
            showToast(isEdit ? 'Doctor updated successfully!' : 'Doctor added successfully!');
        } else {
            showToast(res.message || 'Operation failed', 'error');
        }
    } catch (e) {
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
            }).then(r => r.json());

            if (res.success) {
                loadDoctors();
                showToast('Doctor deleted successfully.');
            } else {
                showToast(res.message || 'Failed to delete doctor', 'error');
            }
        } catch (e) {
            console.error(e);
            showToast('Error deleting doctor.', 'error');
        }
    });
}

let cachedPMIPatients = [];

function filterPMITable() {
    const tbody = document.getElementById('pmi-tbody');
    if (!tbody) return;

    const deptFilter = (document.getElementById('pmiDeptFilter')?.value || 'all').toLowerCase();
    const statusFilter = document.getElementById('pmiStatusFilter')?.value || 'all';
    const searchQuery = (document.getElementById('pmiSearchInput')?.value || '').toLowerCase().trim();

    let list = cachedPMIPatients || [];

    // 1. Filter by department
    if (deptFilter !== 'all') {
        list = list.filter(p => {
            const d = (p.department || '').toLowerCase();
            if (deptFilter === 'general') return d.includes('general') || d.includes('medical');
            if (deptFilter === 'neurology') return d.includes('neuro');
            if (deptFilter === 'psychiatry') return d.includes('psych') || d.includes('opu');
            return d.includes(deptFilter);
        });
    }

    // 2. Filter by status (aligned with current consultation flow)
    if (statusFilter !== 'all') {
        const normSt = p => (p.status || '').toLowerCase().trim();
        if (statusFilter === 'inquiry') {
            list = list.filter(p => p.inquiry_only == 1 || p.inquiry_only === true || p.inquiry_only === '1' || p.consultation_nature === 'General Inquiry');
        } else if (statusFilter === 'for_approval') {
            list = list.filter(p => ['for_approval', 'for_processing', 'pending', 'for approval'].includes(normSt(p)) || Number(p.has_pending_approval) === 1);
        } else if (statusFilter === 'approved') {
            list = list.filter(p => ['approved', 'confirmed'].includes(normSt(p)));
        } else if (statusFilter === 'in_clinic') {
            list = list.filter(p => ['arrived', 'ongoing'].includes(normSt(p)));
        } else if (statusFilter === 'cancelled' || statusFilter === 'no-show') {
            list = list.filter(p => ['cancelled', 'rejected', 'no-show'].includes(normSt(p)));
        } else {
            list = list.filter(p => normSt(p) === statusFilter.toLowerCase());
        }
    }

    // 3. Filter by patient name, canonical ID, HRN, or phone
    if (searchQuery) {
        list = list.filter(p =>
            ((p.full_name || '').toLowerCase().includes(searchQuery)) ||
            ((p.canonical_patient_id || '').toLowerCase().includes(searchQuery)) ||
            ((p.patient_id || '').toLowerCase().includes(searchQuery)) ||
            ((p.hrn || '').toLowerCase().includes(searchQuery)) ||
            ((p.reference_no || '').toLowerCase().includes(searchQuery)) ||
            ((p.contact_number || '').toLowerCase().includes(searchQuery)) ||
            ((p.appointment_recipient || '').toLowerCase().includes(searchQuery)) ||
            ((p.relationship_to_patient || '').toLowerCase().includes(searchQuery))
        );
    }

    if (list.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:35px; color:var(--muted);">No matching patients found.</td></tr>';
        return;
    }

    tbody.innerHTML = list.map(p => {
        const approvalStatuses = ['for_approval', 'for_processing', 'pending', 'for approval'];
        const ownStatus = (p.status || '').toLowerCase().trim();
        const showPendingVisit = statusFilter === 'for_approval'
            && !approvalStatuses.includes(ownStatus)
            && Number(p.has_pending_approval) === 1;
        const st = showPendingVisit
            ? String(p.pending_status || 'for_approval').toLowerCase().trim()
            : ownStatus;
        const rowLookupId = showPendingVisit
            ? (p.pending_patient_id || p.patient_id || p.id || '')
            : (p.patient_id || p.id || '');
        const rowDepartment = showPendingVisit ? (p.pending_department || p.department) : p.department;
        const rowInquiry = showPendingVisit ? p.pending_inquiry_only : p.inquiry_only;
        let b = 'badge-yellow';
        let statusLabel = titleStatus(st);
        if (st === 'approved' || st === 'confirmed') {
            b = 'badge-green';
            statusLabel = 'Confirmed';
        } else if (st === 'arrived') {
            b = 'badge-teal';
            statusLabel = 'Checked In';
        } else if (st === 'ongoing') {
            b = 'badge-pink';
            statusLabel = 'In Consultation';
        } else if (st === 'completed') {
            b = 'badge-blue';
            statusLabel = 'Completed';
        } else if (st === 'cancelled' || st === 'no-show' || st === 'rejected') {
            b = 'badge-red';
            statusLabel = st === 'no-show' ? 'No-Show' : (st === 'rejected' ? 'Rejected' : 'Cancelled');
        } else if (st === 'transferred') {
            b = 'badge-purple';
            statusLabel = 'Transferred';
        } else if (st === 'for_approval' || st === 'for_processing' || st === 'pending' || st === 'for approval') {
            b = 'badge-yellow';
            statusLabel = 'For Approval';
        }

        const isInquiry = rowInquiry == 1 || rowInquiry === true || rowInquiry === '1';
        const inquiryTag = isInquiry
            ? `<span class="badge badge-yellow" style="margin-left:6px;">Inquiry</span>`
            : '';
        const isOtherPatient = ['other patient', 'someone else / dependent'].includes(
            String(p.appointment_recipient || '').trim().toLowerCase()
        );
        const otherRel = p.relationship_to_patient ? ` · ${p.relationship_to_patient}` : '';
        const otherPatientTag = isOtherPatient
            ? `<span style="display:inline-flex;align-items:center;margin-left:6px;padding:1px 7px;border-radius:999px;font-size:0.68rem;font-weight:700;background:#f5f3ff;color:#5b21b6;border:1px solid #ddd6fe;vertical-align:middle;">Other Patient${escapeHtml(otherRel)}</span>`
            : '';

        const lookupId = escapeHtml(rowLookupId);
        const displayId = escapeHtml(showPendingVisit ? rowLookupId : (p.canonical_patient_id || p.patient_id || p.id || ''));
        const safeName = escapeHtml(p.full_name || 'Unnamed Patient');
        const demoParts = [];
        if (p.gender) demoParts.push(p.gender);
        if (p.age) demoParts.push(`${p.age} yrs`);
        if (p.contact_number && p.contact_number !== 'None') demoParts.push(p.contact_number);
        const demoLine = demoParts.length
            ? `<div style="font-size:0.74rem; color:#64748b; margin-top:2px;">${escapeHtml(demoParts.join(' · '))}</div>`
            : '';

        const totalVisits = Number(p.total_visits || p.visit_count || 1);
        const visitsPillStyle = totalVisits >= 2
            ? 'background:#f0fdf4; color:#166534; border:1px solid #bbf7d0;'
            : 'background:#f8fafc; color:#475569; border:1px solid #e2e8f0;';
        const visitsPill = `<span style="display:inline-flex; align-items:center; padding:1px 8px; border-radius:999px; font-size:0.71rem; font-weight:600; ${visitsPillStyle}">${totalVisits} ${totalVisits === 1 ? 'visit' : 'visits'}</span>`;
        const safeDept = escapeHtml(cleanDeptLabel(rowDepartment));
        const pendingDateRaw = showPendingVisit ? String(p.pending_appointment_date || '').split(' ')[0] : '';
        const pendingDateLabel = pendingDateRaw
            ? new Date(pendingDateRaw + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
            : '';
        const lastVisitText = showPendingVisit
            ? (pendingDateLabel ? `${escapeHtml(pendingDateLabel)} · ${safeDept}` : safeDept)
            : (p.last_visit_date ? `${escapeHtml(p.last_visit_date)} · ${safeDept}` : safeDept);
        const safeStatus = escapeHtml(statusLabel);

        return `
        <tr class="clickable-row" onclick="openPatientDetailsModal('${lookupId}')" title="Click to view full patient profile and visit history">
            <td>
                <small style="font-weight:600; color:var(--deep-green);">${displayId}</small>
            </td>
            <td>
                <div><strong>${safeName}</strong>${otherPatientTag}${inquiryTag}</div>
                ${demoLine}
            </td>
            <td>
                <div>${visitsPill}</div>
                <div style="font-size:0.75rem; color:#475569; margin-top:3px;">${lastVisitText}</div>
            </td>
            <td><span class="badge ${b}">${safeStatus}</span></td>
            <td>
                <button type="button" class="btn-table-action btn-table-details" onclick="event.stopPropagation(); openPatientDetailsModal('${lookupId}')" title="View Details">
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
    const deptEl = document.getElementById('pmiDeptFilter');
    if (deptEl) deptEl.value = 'all';
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

        const res = await fetch(`/sdn/api/patients.php?filter=${encodeURIComponent(filterVal)}&deduplicate=1`).then(r => r.json());
        if (res.success && res.data) {
            cachedPMIPatients = res.data;
            filterPMITable();
            updateAdminOverviewKPIs();
            renderAdminOverviewDoctors();
            renderAdminOverviewCalendar();

            const summary = res.summary || {};
            const elPts = document.getElementById('pmi-stat-patients');
            const elRet = document.getElementById('pmi-stat-returning');
            const elApp = document.getElementById('pmi-stat-appointments');

            const uniqueCount = summary.unique_patients ?? cachedPMIPatients.length;
            const returningCount = summary.returning_patients ?? cachedPMIPatients.filter(p => Number(p.total_visits || p.visit_count || 1) >= 2).length;
            const encounterCount = summary.total_encounters ?? cachedPMIPatients.reduce((sum, p) => sum + Number(p.total_visits || 1), 0);

            if (elPts) elPts.innerText = uniqueCount;
            if (elRet) elRet.innerText = returningCount;
            if (elApp) elApp.innerText = encounterCount;
        }
    } catch (e) { console.error(e); }
}

async function openPatientDetailsModal(patient_id) {
    const modal = document.getElementById('patientDetailsModal');
    const body = document.getElementById('patientDetailsBody');
    const actions = document.getElementById('patientDetailsActions');
    if (!modal || !body) return;

    modal.classList.add('show');
    body.innerHTML = '<p style="text-align:center; padding:28px 0; color:#64748b;">Loading appointment details...</p>';
    if (actions) actions.innerHTML = '';

    try {
        let p = null;
        try {
            const res = await fetch(`/sdn/api/patients.php?id=${encodeURIComponent(patient_id)}&_=${Date.now()}`).then(r => r.json());
            if (res && res.success && res.data) {
                p = res.data;
            }
        } catch (fetchErr) {
            console.warn('API details fetch warning:', fetchErr);
        }

        // Cache fallback: if direct API query returned null or failed, use cached PMI patient data
        if (!p && Array.isArray(cachedPMIPatients)) {
            p = cachedPMIPatients.find(x => x && (x.patient_id === patient_id || String(x.id) === String(patient_id)));
        }

        if (!p) {
            body.innerHTML = '<p style="text-align:center; color:#ef4444; padding:24px 0;">Failed to load appointment details.</p>';
            return;
        }

        const phone = p.contact_number || 'None';
        const isPending = (p.status === 'pending' || p.status === 'for_approval' || p.status === 'for_processing');
        const showVerificationProtocol = isPending && p.status !== 'completed';
        const isInquiry = p.inquiry_only == 1 || p.inquiry_only === true || p.inquiry_only === '1';
        const deptFormatted = cleanDeptLabel(p.department || 'N/A');

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
                    modalTitle.innerText = 'Patient Appointment & Consultation Details';
                    modalSub.innerText = 'Review patient profile, clinical diagnosis, treatment plan, and consultation history.';
                } else {
                    modalTitle.innerText = 'Patient Appointment Verification';
                    modalSub.innerText = 'Review appointment information and verify patient authenticity via phone call.';
                }
            }
        }

        const nameParts = String(p.full_name || 'P').trim().split(/\s+/).filter(Boolean);
        const initials = nameParts.length > 1
            ? (nameParts[0].charAt(0) + nameParts[nameParts.length - 1].charAt(0)).toUpperCase()
            : (nameParts[0] || 'P').charAt(0).toUpperCase();
        const phoneIconSvg = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>';
        const summaryBarHtml = `
            <div class="pv-summary-bar">
                <div class="pv-summary-left">
                    <div class="pv-avatar" aria-hidden="true">${escapeHtml(initials)}</div>
                    <div class="pv-summary-identity">
                        <div class="pv-patient-name">${escapeHtml(p.full_name || 'Unnamed Patient')}</div>
                        <div class="pv-id-chips">
                            <span class="pv-id-chip">Ref: <strong>${escapeHtml(p.reference_no || p.patient_id)}</strong></span>
                        </div>
                    </div>
                </div>
                <div class="pv-summary-right">
                    ${phone !== 'None'
                ? `<a href="tel:${escapeHtml(phone)}" class="pv-call-pill">${phoneIconSvg}<span>${isInquiry ? escapeHtml(phone) : 'Call ' + escapeHtml(phone)}</span></a>`
                : `<span class="pv-no-phone">No phone on file</span>`}
                </div>
            </div>
        `;

        if (isInquiry) {
            body.innerHTML = `
                ${summaryBarHtml}

                ${showVerificationProtocol ? `
                <div class="pv-callout">
                    ${phoneIconSvg}
                    <span><strong>Inquiry Follow-up Protocol:</strong> Contact inquirer at <strong>${escapeHtml(phone)}</strong> to address their inquiry or guide them to clinic services.</span>
                </div>` : ''}

                <div class="pv-grid">
                    <div class="pv-card">
                        <div class="pv-card-title">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            <span>Inquirer Profile</span>
                        </div>
                        <div class="pv-kv-grid">
                            <div class="pv-kv">
                                <span class="pv-kv-label">Gender &amp; Age</span>
                                <span class="pv-kv-val">${escapeHtml(p.gender || 'N/A')} &bull; ${escapeHtml(p.age || 'N/A')} yrs</span>
                            </div>
                            <div class="pv-kv">
                                <span class="pv-kv-label">Birthdate</span>
                                <span class="pv-kv-val">${escapeHtml(p.birthdate || 'N/A')}</span>
                            </div>
                            <div class="pv-kv">
                                <span class="pv-kv-label">Recipient</span>
                                <span class="pv-kv-val">${escapeHtml(p.appointment_recipient || 'Patient (Me)')}${p.relationship_to_patient ? ' (' + escapeHtml(p.relationship_to_patient) + ')' : ''}</span>
                            </div>
                            <div class="pv-kv">
                                <span class="pv-kv-label">Companion</span>
                                <span class="pv-kv-val">${(p.with_companion == 1 || p.with_companion === true || p.with_companion === '1') ? 'Yes' : 'No'}</span>
                            </div>
                            <div class="pv-kv pv-kv-full">
                                <span class="pv-kv-label">Email Address</span>
                                <span class="pv-kv-val">${escapeHtml(p.email || 'None')}</span>
                            </div>
                            <div class="pv-kv pv-kv-full">
                                <span class="pv-kv-label">Address</span>
                                <span class="pv-kv-val">${escapeHtml(p.address || 'None provided')}</span>
                            </div>
                        </div>
                    </div>

                    <div class="pv-card">
                        <div class="pv-card-title">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                            <span>Inquiry Details</span>
                        </div>
                        <div class="pv-kv-grid">
                            <div class="pv-kv">
                                <span class="pv-kv-label">Department</span>
                                <span class="pv-kv-val pv-kv-strong">${escapeHtml(deptFormatted)}</span>
                            </div>
                            <div class="pv-kv">
                                <span class="pv-kv-label">Mode of Inquiry</span>
                                <span class="pv-kv-val">${escapeHtml(p.mode_of_inquiry || 'Online Portal')}</span>
                            </div>
                            <div class="pv-kv">
                                <span class="pv-kv-label">Referral Source</span>
                                <span class="pv-kv-val">${escapeHtml(p.referral_source || 'None')}</span>
                            </div>
                            <div class="pv-kv">
                                <span class="pv-kv-label">Submitted</span>
                                <span class="pv-kv-val">${escapeHtml(p.created_at || 'Today')}</span>
                            </div>
                            <div class="pv-kv pv-kv-full">
                                <span class="pv-kv-label">Existing Medical Conditions</span>
                                <span class="pv-kv-val">${escapeHtml(p.existing_medical_conditions || 'None')}</span>
                            </div>
                            <div class="pv-kv pv-kv-full">
                                <span class="pv-kv-label">Current Medications</span>
                                <span class="pv-kv-val">${escapeHtml(p.current_medications || 'None')}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pv-card">
                    <div class="pv-card-title">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                        <span>Inquiry Subject &amp; Details</span>
                    </div>
                    <div class="pv-complaint-box">
                        <span class="pv-kv-label">Subject</span>
                        <div class="pv-complaint-text">${escapeHtml(p.chief_complaints || 'General Inquiry')}</div>
                    </div>
                    ${(p.illness_description || p.recommendation_remarks) ? `
                    <div class="pv-note-strip">
                        <span class="pv-kv-label">Additional Details</span>
                        <div class="pv-complaint-text">${escapeHtml(p.illness_description || p.recommendation_remarks)}</div>
                    </div>` : ''}
                </div>
            `;

            if (actions) {
                let statusBadge = 'badge-purple';
                let statusText = (p.status || '').toUpperCase();
                if (isPending) {
                    statusBadge = 'badge-yellow';
                    statusText = 'FOR REVIEW';
                } else if (p.status === 'approved') {
                    statusBadge = 'badge-green';
                    statusText = 'UNDER REVIEW';
                } else if (p.status === 'no-show' || p.status === 'rejected') {
                    statusBadge = 'badge-red';
                    statusText = 'INVALID';
                } else if (p.status === 'completed') {
                    statusBadge = 'badge-blue';
                    statusText = 'RESOLVED';
                }
                const canResolveInquiry = p.status !== 'completed' && p.status !== 'rejected' && p.status !== 'no-show';
                actions.innerHTML = `
                    <div class="patient-modal-status-col">
                        <span class="badge ${statusBadge}">${escapeHtml(statusText)}</span>
                    </div>
                    <div class="patient-modal-btn-group">
                        <button type="button" class="btn btn-outline" onclick="closePatientDetailsModal()">Close</button>
                        ${canResolveInquiry ? `<button type="button" class="pv-btn-primary" onclick="promptResolveInquiry('${escapeHtml(p.patient_id)}', '${escapeHtml(p.full_name)}')"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"></polyline></svg><span>Resolve Inquiry</span></button>` : ''}
                    </div>
                `;
            }
            return;
        }

        const bookingTypeLabel = p.patient_type
            ? p.patient_type.charAt(0).toUpperCase() + p.patient_type.slice(1)
            : 'N/A';
        const consultModeLabel = (!p.consultation_mode || /^in-person$/i.test(p.consultation_mode))
            ? 'Face-to-Face'
            : p.consultation_mode;
        const scheduleSlotText = `${p.appointment_date || 'N/A'}${p.preferred_time_slot ? ' • ' + p.preferred_time_slot : (p.scheduled_time ? ' • ' + p.scheduled_time : '')}`;
        const activeMedsFormatted = p.active_medications ? (() => {
            try {
                const v = typeof p.active_medications === 'string' && (p.active_medications.trim().startsWith('[') || p.active_medications.trim().startsWith('{'))
                    ? JSON.parse(p.active_medications)
                    : p.active_medications;
                const arr = Array.isArray(v) ? v : (v && typeof v === 'object' ? [v] : null);
                return arr
                    ? arr.map(m => typeof m === 'string' ? m : ([m.name || m.medicine || '', m.dosage || ''].filter(Boolean).join(' — ') + (m.quantity ? ` (Qty: ${m.quantity})` : ''))).filter(Boolean).join('; ')
                    : String(p.active_medications);
            } catch (e) {
                return String(p.active_medications);
            }
        })() : '';
        const completedPrev = Array.isArray(p.previous_appointments)
            ? p.previous_appointments.filter(prev => (prev.status || '').toLowerCase() === 'completed')
            : [];

        body.innerHTML = `
            ${summaryBarHtml}

            ${showVerificationProtocol ? `
            <div class="pv-callout">
                ${phoneIconSvg}
                <span><strong>Verification Protocol:</strong> Call patient at <strong>${escapeHtml(phone)}</strong> to confirm appointment validity. If unreachable or unverified, mark as No-Show.</span>
            </div>` : ''}

            <div class="pv-grid">
                <div class="pv-card">
                    <div class="pv-card-title">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        <span>Personal Information</span>
                    </div>
                    <div class="pv-kv-grid">
                        <div class="pv-kv">
                            <span class="pv-kv-label">Gender &amp; Age</span>
                            <span class="pv-kv-val">${escapeHtml(p.gender || 'N/A')} &bull; ${escapeHtml(p.age || 'N/A')} yrs</span>
                        </div>
                        <div class="pv-kv">
                            <span class="pv-kv-label">Birthdate</span>
                            <span class="pv-kv-val">${escapeHtml(p.birthdate || 'N/A')}</span>
                        </div>
                        <div class="pv-kv">
                            <span class="pv-kv-label">Recipient</span>
                            <span class="pv-kv-val">${escapeHtml(p.appointment_recipient || 'Patient (Me)')}${p.relationship_to_patient ? ' (' + escapeHtml(p.relationship_to_patient) + ')' : ''}</span>
                        </div>
                        <div class="pv-kv">
                            <span class="pv-kv-label">Companion</span>
                            <span class="pv-kv-val">${p.with_companion == 1 ? 'Yes (Accompanied)' : 'No (Unaccompanied)'}</span>
                        </div>
                        <div class="pv-kv pv-kv-full">
                            <span class="pv-kv-label">Email Address</span>
                            <span class="pv-kv-val">${escapeHtml(p.email || 'None')}</span>
                        </div>
                        <div class="pv-kv pv-kv-full">
                            <span class="pv-kv-label">Home Address</span>
                            <span class="pv-kv-val">${escapeHtml(p.address || 'None')}</span>
                        </div>
                    </div>
                </div>

                <div class="pv-card">
                    <div class="pv-card-title">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        <span>Appointment Details</span>
                    </div>
                    <div class="pv-kv-grid">
                        <div class="pv-kv">
                            <span class="pv-kv-label">Department</span>
                            <span class="pv-kv-val pv-kv-strong">${escapeHtml(deptFormatted)}</span>
                        </div>
                        <div class="pv-kv">
                            <span class="pv-kv-label">Consultation Mode</span>
                            <span class="pv-kv-val">${escapeHtml(consultModeLabel)}</span>
                        </div>
                        <div class="pv-kv pv-kv-full">
                            <span class="pv-kv-label">Schedule &amp; Time Slot</span>
                            <span class="pv-kv-val pv-kv-strong">${escapeHtml(scheduleSlotText)}</span>
                        </div>
                        <div class="pv-kv pv-kv-full">
                            <span class="pv-kv-label">Attending Doctor</span>
                            <span class="pv-kv-val">${escapeHtml(p.assigned_doctor || 'Unassigned')}</span>
                        </div>
                        <div class="pv-kv">
                            <span class="pv-kv-label">Booking Type</span>
                            <span class="pv-kv-val">${escapeHtml(bookingTypeLabel)} (${p.unscheduled_walkin ? 'Walk-in' : 'Online'})</span>
                        </div>
                        <div class="pv-kv">
                            <span class="pv-kv-label">Referral Source</span>
                            <span class="pv-kv-val">${escapeHtml(p.referral_source ? cleanDeptLabel(p.referral_source) : 'None')}</span>
                        </div>
                        ${p.previous_appointment_date ? `
                        <div class="pv-kv pv-kv-full">
                            <span class="pv-kv-label">Previous Visit</span>
                            <span class="pv-kv-val pv-kv-accent">${escapeHtml(p.previous_appointment_date)}${p.previous_appointment_time ? ' &bull; ' + escapeHtml(p.previous_appointment_time) : ''}</span>
                        </div>` : ''}
                    </div>
                </div>
            </div>

            <div class="pv-card">
                <div class="pv-card-title">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                    <span>Medical Information &amp; Chief Complaint</span>
                </div>
                <div class="pv-complaint-box">
                    <span class="pv-kv-label">Chief Complaint</span>
                    <div class="pv-complaint-text">${escapeHtml(p.chief_complaints || 'None listed')}</div>
                </div>
                <div class="pv-kv-grid pv-kv-medical">
                    <div class="pv-kv">
                        <span class="pv-kv-label">Existing Medical Conditions</span>
                        <span class="pv-kv-val">${escapeHtml(p.existing_medical_conditions || 'None')}</span>
                    </div>
                    <div class="pv-kv">
                        <span class="pv-kv-label">Current Medications</span>
                        <span class="pv-kv-val">${escapeHtml(p.current_medications || 'None')}</span>
                    </div>
                    ${p.illness_description ? `
                    <div class="pv-kv pv-kv-full">
                        <span class="pv-kv-label">Illness Details</span>
                        <span class="pv-kv-val">${escapeHtml(p.illness_description)}</span>
                    </div>` : ''}
                    ${p.recommendation_remarks ? `
                    <div class="pv-kv pv-kv-full">
                        <span class="pv-kv-label">Remarks</span>
                        <span class="pv-kv-val">${escapeHtml(p.recommendation_remarks)}</span>
                    </div>` : ''}
                </div>
            </div>

            ${(p.medical_history_diagnosis || p.medical_history_treatment || p.medical_history_prescription || p.active_medications || p.latest_progress_note) ? `
            <div class="pv-card pv-card-clinical">
                <div class="pv-card-title">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 12h-4l-3 9L9 3l-3 9H2"></path></svg>
                    <span>Clinical History &amp; Medical Notes</span>
                </div>
                <div class="pv-kv-grid">
                    ${p.medical_history_diagnosis ? `
                    <div class="pv-kv${p.next_visit_date ? '' : ' pv-kv-full'}">
                        <span class="pv-kv-label">Diagnosis</span>
                        <span class="pv-kv-val pv-kv-strong">${escapeHtml(p.medical_history_diagnosis)}</span>
                    </div>` : ''}
                    ${p.next_visit_date ? `
                    <div class="pv-kv${p.medical_history_diagnosis ? '' : ' pv-kv-full'}">
                        <span class="pv-kv-label">Next Follow-Up</span>
                        <span class="pv-kv-val pv-kv-accent">${escapeHtml(p.next_visit_date)}</span>
                    </div>` : ''}
                    ${p.medical_history_treatment ? `
                    <div class="pv-kv pv-kv-full">
                        <span class="pv-kv-label">Treatment Plan</span>
                        <span class="pv-kv-val">${escapeHtml(p.medical_history_treatment)}</span>
                    </div>` : ''}
                    ${p.medical_history_prescription ? `
                    <div class="pv-kv pv-kv-full">
                        <span class="pv-kv-label">Prescription</span>
                        <span class="pv-kv-val">${escapeHtml(p.medical_history_prescription)}</span>
                    </div>` : ''}
                    ${activeMedsFormatted ? `
                    <div class="pv-kv pv-kv-full">
                        <span class="pv-kv-label">Active Medications</span>
                        <span class="pv-kv-val">${escapeHtml(activeMedsFormatted)}</span>
                    </div>` : ''}
                    ${p.latest_progress_note ? `
                    <div class="pv-kv pv-kv-full">
                        <span class="pv-kv-label">Progress Note</span>
                        <span class="pv-kv-val">${escapeHtml(p.latest_progress_note.note)}</span>
                    </div>` : ''}
                </div>
            </div>` : ''}

            ${completedPrev.length > 0 ? `
            <div class="pv-card">
                <div class="pv-card-title">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    <span>Previous Appointments History (${completedPrev.length})</span>
                </div>
                <div class="pv-history-list">
                    ${completedPrev.map(prev => `
                        <div class="pv-history-item">
                            <div class="pv-history-main">
                                <div class="pv-history-date">
                                    ${escapeHtml(prev.date_formatted || prev.appointment_date)} <span class="pv-history-time">&bull; ${escapeHtml(prev.time_formatted || '8:00 AM')}</span>
                                </div>
                                <div class="pv-history-meta">
                                    ${escapeHtml(cleanDeptLabel(prev.department || 'Clinic'))} &bull; ${escapeHtml(prev.assigned_doctor || 'Attending Physician')} (${escapeHtml(prev.patient_id)})
                                </div>
                                ${prev.diagnosis ? `<div class="pv-history-dx"><strong>Diagnosis:</strong> ${escapeHtml(prev.diagnosis)}</div>` : ''}
                            </div>
                            <span class="badge badge-blue pv-history-badge">${escapeHtml((prev.status || '').toUpperCase())}</span>
                        </div>
                    `).join('')}
                </div>
            </div>` : ''}
        `;

        if (actions) {
            let statusBadge = 'badge-blue';
            let statusText = (p.status || '').replace(/_/g, ' ').toUpperCase();
            if (isPending) {
                statusBadge = 'badge-yellow';
                statusText = 'FOR APPROVAL';
            } else if (p.status === 'approved') {
                statusBadge = 'badge-green';
                statusText = 'APPROVED';
            } else if (p.status === 'arrived') {
                statusBadge = 'badge-teal';
                statusText = 'IN TRIAGE';
            } else if (p.status === 'ongoing') {
                statusBadge = 'badge-pink';
                statusText = 'ONGOING CONSULTATION';
            } else if (p.status === 'no-show' || p.status === 'rejected') {
                statusBadge = 'badge-red';
            } else if (p.status === 'transferred') {
                statusBadge = 'badge-purple';
            }
            window._lastAdminDetailPatient = p;
            const allowSlip = Boolean(window.PatientSlip && window.PatientSlip.canGenerateSlip(p));
            const slipKind = allowSlip ? window.PatientSlip.resolveSlipType(p) : 'appointment';
            const slipBtnText = slipKind === 'followUp'
                ? 'Print Follow-Up Slip'
                : (slipKind === 'rescheduled' ? 'Print Rescheduled Slip' : 'Print Appointment Slip');
            const printIconSvg = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>';
            actions.innerHTML = `
                <div class="patient-modal-status-col">
                    <span class="badge ${statusBadge}">${escapeHtml(statusText)}</span>
                </div>
                <div class="patient-modal-btn-group">
                    <button type="button" class="btn btn-outline" onclick="closePatientDetailsModal()">Close</button>
                    ${allowSlip ? `<button type="button" class="pv-btn-primary" onclick="openAdminPatientSlip()">${printIconSvg}<span>${slipBtnText}</span></button>` : ''}
                </div>
            `;
        }

    } catch (err) {
        console.error(err);
        body.innerHTML = '<p style="text-align:center; color:#ef4444; padding:24px 0;">Error fetching appointment details.</p>';
    }
}

function closePatientDetailsModal() {
    const modal = document.getElementById('patientDetailsModal');
    if (modal) modal.classList.remove('show');
}

function openAdminPatientSlip() {
    const p = window._lastAdminDetailPatient;
    if (!p || !window.PatientSlip || !window.PatientSlip.canGenerateSlip(p)) return;
    window.PatientSlip.openModal({
        slipType: window.PatientSlip.resolveSlipType(p),
        patientId: p.patient_id || p.reference_no || '',
        patientName: p.full_name || '',
        patientType: p.patient_type || 'New',
        appointmentDate: p.appointment_date || '',
        appointmentTime: p.preferred_time_slot || p.scheduled_time || '',
        specialty: p.department || '',
        consultType: p.consultation_nature || p.consultation_mode || 'Face-to-Face',
        remarks: p.recommendation_remarks || p.chief_complaints || '',
        encodedBy: p.medical_history_recorded_by || p.receiver || 'Admin',
        encodedDate: p.medical_history_date || p.updated_at || '',
        attendedDate: p.medical_history_date || p.appointment_date || '',
        nextVisitDate: p.next_visit_date || '',
        followUpNotes: p.follow_up_notes || ''
    });
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
            headers: { 'Content-Type': 'application/json' },
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
    } catch (e) {
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
        } catch (e) {
            console.error(e);
            showToast('Server error while resolving inquiry.', 'error');
            btn.disabled = false;
            btn.textContent = 'Confirm Resolved';
        }
    };
}



// ── ADMIN HEADER NOTIFICATIONS ──────────────────────────────────────────────
let latestAdminNotifItems = [];

function toggleAdminNotifications(e) {
    if (e) e.stopPropagation();
    const profDd = document.getElementById('adminProfileDropdown');
    const profBtn = document.getElementById('adminProfileBtn');
    if (profDd) profDd.classList.remove('open');
    if (profBtn) profBtn.setAttribute('aria-expanded', 'false');
    const dd = document.getElementById('adminNotifDropdown');
    if (dd) dd.classList.toggle('open');
}

function toggleAdminProfileMenu(e) {
    if (e) e.stopPropagation();
    const notifDd = document.getElementById('adminNotifDropdown');
    if (notifDd) notifDd.classList.remove('open');
    const profDd = document.getElementById('adminProfileDropdown');
    const profBtn = document.getElementById('adminProfileBtn');
    if (profDd) {
        const isOpen = profDd.classList.toggle('open');
        if (profBtn) profBtn.setAttribute('aria-expanded', String(isOpen));
    }
}

function getReadNotifIdSet() {
    try {
        const raw = localStorage.getItem('sdn_admin_notif_read_ids');
        const arr = raw ? JSON.parse(raw) : [];
        return new Set(Array.isArray(arr) ? arr : []);
    } catch (e) {
        return new Set();
    }
}

function saveReadNotifIdSet(setObj) {
    try {
        const recent = Array.from(setObj).slice(-60);
        localStorage.setItem('sdn_admin_notif_read_ids', JSON.stringify(recent));
    } catch (e) { }
}

function markAllAdminNotificationsRead(e) {
    if (e) e.stopPropagation();
    const readSet = getReadNotifIdSet();
    latestAdminNotifItems.forEach(n => readSet.add(n.id));
    saveReadNotifIdSet(readSet);
    const readSig = latestAdminNotifItems.map(n => n.id).join('|') || 'read';
    localStorage.setItem('sdn_admin_notif_read_sig', readSig);
    const badge = document.getElementById('adminNotifBadge');
    if (badge) badge.style.display = 'none';
    document.querySelectorAll('.admin-notif-item.unread').forEach(el => el.classList.remove('unread'));
}

function handleAdminNotificationClick(notifId, viewName) {
    if (notifId) {
        const readSet = getReadNotifIdSet();
        readSet.add(notifId);
        saveReadNotifIdSet(readSet);
    }
    navigateToAdminView(viewName);
    if (typeof lastAdminStatsPayload !== 'undefined' && lastAdminStatsPayload) {
        updateAdminNotifications(lastAdminStatsPayload);
    }
}

function navigateToAdminView(viewName) {
    const dd = document.getElementById('adminNotifDropdown');
    if (dd) dd.classList.remove('open');
    const profDd = document.getElementById('adminProfileDropdown');
    if (profDd) profDd.classList.remove('open');
    const profBtn = document.getElementById('adminProfileBtn');
    if (profBtn) profBtn.setAttribute('aria-expanded', 'false');
    const link = document.querySelector(`.sidebar-nav .nav-link[data-view="${viewName}"]`);
    if (link) link.click();
}

let lastAdminStatsPayload = null;

function updateAdminNotifications(statsRes) {
    lastAdminStatsPayload = statsRes;
    const listEl = document.getElementById('adminNotifList');
    const badgeEl = document.getElementById('adminNotifBadge');
    if (!listEl || !badgeEl) return;

    const items = [];
    const pendingAppts = parseInt(statsRes.pending_appointments, 10) || 0;
    const pendingInq = parseInt(statsRes.pending_inquiries, 10) || 0;
    const pendingTotal = parseInt(statsRes.pending_actions, 10) || (pendingAppts + pendingInq);
    const unassignedUp = parseInt(statsRes.unassigned_upcoming, 10) || 0;

    if (pendingAppts > 0) {
        items.push({
            id: `pending-appt-${pendingAppts}`,
            title: `${pendingAppts} Appointment${pendingAppts > 1 ? 's' : ''} Pending Approval`,
            desc: 'Online bookings waiting for staff verification and approval.',
            time: 'Action Needed',
            view: 'pmi',
            type: 'warning'
        });
    } else if (pendingTotal > 0 && pendingInq === 0) {
        items.push({
            id: `pending-total-${pendingTotal}`,
            title: `${pendingTotal} Pending Patient Action${pendingTotal > 1 ? 's' : ''}`,
            desc: 'Appointments or inquiries awaiting staff review in PMI.',
            time: 'Action Needed',
            view: 'pmi',
            type: 'warning'
        });
    }

    if (pendingInq > 0) {
        items.push({
            id: `pending-inq-${pendingInq}`,
            title: `${pendingInq} Unresolved Patient Inquir${pendingInq > 1 ? 'ies' : 'y'}`,
            desc: 'Patient inquiries waiting for clinic coordination in PMI.',
            time: 'Inquiry Queue',
            view: 'pmi',
            type: 'warning'
        });
    }

    if (unassignedUp > 0) {
        items.push({
            id: `unassigned-up-${unassignedUp}`,
            title: `${unassignedUp} Patient${unassignedUp > 1 ? 's' : ''} Need Physician Assignment`,
            desc: 'Active appointments without an assigned attending doctor.',
            time: 'Schedule Check',
            view: 'reports',
            type: 'warning'
        });
    }

    const logs = statsRes.alerts || statsRes.audit_logs || [];
    logs.slice(0, 6).forEach((l, idx) => {
        const cat = classifyAuditCategory(l);
        let targetView = 'audit';
        if (cat.key === 'patient') targetView = 'pmi';
        else if (cat.key === 'doctor') targetView = 'doctors';
        else if (cat.key === 'staff') targetView = 'rbac';

        const logDetails = cleanDeptLabel(l.details || l.description || '');
        const actorPrefix = l.user ? `${l.user} · ` : '';
        items.push({
            id: `log-${l.id || idx}-${l.created_at || l.timestamp || ''}`,
            title: l.action || 'System Activity',
            desc: `${actorPrefix}${logDetails || 'Recorded in audit trail.'}`,
            time: l.timestamp || 'Recent',
            view: targetView,
            type: 'info'
        });
    });

    latestAdminNotifItems = items;
    const readSet = getReadNotifIdSet();
    const currentSig = items.map(n => n.id).join('|');
    const readSig = localStorage.getItem('sdn_admin_notif_read_sig') || '';
    const isAllReadBySig = Boolean(currentSig && currentSig === readSig);

    const unreadCount = isAllReadBySig
        ? 0
        : items.filter(n => !readSet.has(n.id)).length;

    if (unreadCount > 0) {
        badgeEl.textContent = unreadCount > 9 ? '9+' : String(unreadCount);
        badgeEl.style.display = 'inline-flex';
    } else {
        badgeEl.style.display = 'none';
    }

    if (items.length === 0) {
        listEl.innerHTML = '<div class="admin-notif-empty">No new notifications.</div>';
        return;
    }

    listEl.innerHTML = items.map(item => {
        const isUnread = !isAllReadBySig && !readSet.has(item.id);
        const safeIdAttr = escapeHtml(item.id).replace(/'/g, '&#39;');
        return `
            <div class="admin-notif-item ${isUnread ? 'unread' : ''}" onclick="handleAdminNotificationClick('${safeIdAttr}', '${item.view}')">
                <div class="admin-notif-dot ${item.type === 'warning' ? 'dot-warning' : 'dot-info'}"></div>
                <div class="admin-notif-content">
                    <div class="admin-notif-item-top">
                        <span class="admin-notif-item-title">${escapeHtml(item.title)}</span>
                        <span class="admin-notif-item-time">${escapeHtml(item.time)}</span>
                    </div>
                    <p class="admin-notif-item-desc">${escapeHtml(item.desc)}</p>
                </div>
            </div>
        `;
    }).join('');
}

// ── AUDIT LOG FILTERING & CATEGORY SORTING ───────────────────────────────────
let allAuditLogs = [];

function classifyAuditCategory(log) {
    const text = `${log.action || ''} ${log.details || ''}`.toLowerCase();
    if (/doctor|specialization|leave|slot/i.test(text)) {
        return { key: 'doctor', label: 'Doctors', badgeClass: 'badge-teal' };
    }
    if (/staff|rbac|account|role|user added|user updated|user deleted/i.test(text)) {
        return { key: 'staff', label: 'User Management', badgeClass: 'badge-purple' };
    }
    if (/patient|appointment|walk-in|walkin|inquiry|consultation|triage|no-show|transfer|approved|check-in|check in/i.test(text)) {
        return { key: 'patient', label: 'Patient & Appts', badgeClass: 'badge-green' };
    }
    if (/login|logout|auth|password|session|security/i.test(text)) {
        return { key: 'auth', label: 'Auth & Security', badgeClass: 'badge-orange' };
    }
    return { key: 'system', label: 'System', badgeClass: 'badge-gray' };
}

function filterAndSortAuditTable() {
    const tbody = document.getElementById('audit-tbody');
    if (!tbody) return;

    const searchVal = (document.getElementById('auditSearchInput')?.value || '').toLowerCase().trim();
    const catFilter = (document.getElementById('auditCategoryFilter')?.value || 'all').toLowerCase();
    const sortOrder = (document.getElementById('auditSortOrder')?.value || 'newest').toLowerCase();

    let list = (allAuditLogs || []).map((l, idx) => {
        const cat = classifyAuditCategory(l);
        return {
            ...l,
            _idx: idx,
            _catKey: cat.key,
            _catLabel: cat.label,
            _catBadge: cat.badgeClass,
            _cleanDetails: cleanDeptLabel(l.details || '')
        };
    });

    if (catFilter !== 'all') {
        list = list.filter(l => l._catKey === catFilter);
    }

    if (searchVal) {
        list = list.filter(l =>
            (l.action || '').toLowerCase().includes(searchVal) ||
            (l.user || '').toLowerCase().includes(searchVal) ||
            (l._cleanDetails || '').toLowerCase().includes(searchVal) ||
            (l._catLabel || '').toLowerCase().includes(searchVal)
        );
    }

    list.sort((a, b) => {
        if (sortOrder === 'oldest') {
            return (a.timestamp || '').localeCompare(b.timestamp || '') || (b._idx - a._idx);
        }
        if (sortOrder === 'category') {
            return a._catLabel.localeCompare(b._catLabel) || (b.timestamp || '').localeCompare(a.timestamp || '');
        }
        if (sortOrder === 'action') {
            return (a.action || '').localeCompare(b.action || '') || (b.timestamp || '').localeCompare(a.timestamp || '');
        }
        if (sortOrder === 'user') {
            return (a.user || '').localeCompare(b.user || '') || (b.timestamp || '').localeCompare(a.timestamp || '');
        }
        // newest first (default)
        return (b.timestamp || '').localeCompare(a.timestamp || '') || (a._idx - b._idx);
    });

    if (list.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:30px; color:var(--muted);">No audit logs match the selected filters.</td></tr>';
        syncTableScrollbars();
        return;
    }

    tbody.innerHTML = list.map(l => `
        <tr>
            <td>${formatStackedTimestamp(l.timestamp)}</td>
            <td><span class="badge ${l._catBadge}" style="font-size:0.75rem;">${escapeHtml(l._catLabel)}</span></td>
            <td><strong style="color: #0f172a;">${escapeHtml(l.action)}</strong></td>
            <td style="color: #334155;">${escapeHtml(l.user)}</td>
            <td style="color: #334155;">${escapeHtml(l._cleanDetails)}</td>
        </tr>
    `).join('');
    syncTableScrollbars();
}

function resetAuditFilters() {
    const searchEl = document.getElementById('auditSearchInput');
    if (searchEl) searchEl.value = '';
    const catEl = document.getElementById('auditCategoryFilter');
    if (catEl) catEl.value = 'all';
    const sortEl = document.getElementById('auditSortOrder');
    if (sortEl) sortEl.value = 'newest';
    loadAudit();
}

async function loadAudit() {
    try {
        const tbody = document.getElementById('audit-tbody');
        if (!tbody) return;
        const res = await fetch('/sdn/api/logs.php?_=' + Date.now()).then(r => r.json());
        if (res.success && Array.isArray(res.data)) {
            allAuditLogs = res.data;
            filterAndSortAuditTable();
        }
    } catch (e) { console.error('Error loading audit trail:', e); }
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
// PHASE 4: UPCOMING PATIENT OPERATIONS & ANALYTICS
// ══════════════════════════════════════════════════════════════════════════════
let currentReportTab = 'upcoming'; // 'upcoming' | 'depts' | 'daily' | 'monthly'
let currentReportRange = 'today';    // 'today' | 'past_7' | 'past_30' | 'past_month' | 'custom' | 'all'
let currentReportDept = 'all';       // 'all' | 'general' | 'neurology' | 'psychiatry'
let upcomingQuickFilter = 'all';     // 'all' | 'needs_action' | 'ready'
let cachedReportsData = null;
let reportsAnalyticsChartInstance = null;

function setReportRange(range) {
    currentReportRange = range || 'today';
    const startEl = document.getElementById('reportStartDate');
    const endEl = document.getElementById('reportEndDate');
    if (startEl) startEl.value = '';
    if (endEl) endEl.value = '';
    document.querySelectorAll('#reportRangePills .rep-pill-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.range === currentReportRange);
    });
    loadReports();
}

function setReportDept(dept) {
    currentReportDept = dept || 'all';
    document.querySelectorAll('#reportDeptPills .rep-pill-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.dept === currentReportDept);
    });
    loadReports();
}

function setUpcomingQuickFilter(mode) {
    upcomingQuickFilter = mode || 'all';
    document.querySelectorAll('#repUpcomingQuickFilter .rep-sub-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.qf === upcomingQuickFilter);
    });
    renderUpcomingPatientsTable();
}

function switchReportTab(tab) {
    currentReportTab = tab || 'upcoming';
    ['upcoming', 'depts', 'daily', 'monthly'].forEach(t => {
        const cap = t.charAt(0).toUpperCase() + t.slice(1);
        const btn = document.getElementById('tabBtn' + cap);
        const sec = document.getElementById('reportSection' + cap);
        if (btn) btn.classList.toggle('active', t === currentReportTab);
        if (sec) sec.style.display = (t === currentReportTab) ? 'block' : 'none';
    });
    const qfEl = document.getElementById('repUpcomingQuickFilter');
    if (qfEl) {
        qfEl.style.display = currentReportTab === 'upcoming' ? 'inline-flex' : 'none';
    }
}

function resetReportFilters() {
    currentReportRange = 'today';
    currentReportDept = 'all';
    upcomingQuickFilter = 'all';
    const searchEl = document.getElementById('reportSearchInput');
    if (searchEl) searchEl.value = '';
    const startEl = document.getElementById('reportStartDate');
    if (startEl) startEl.value = '';
    const endEl = document.getElementById('reportEndDate');
    if (endEl) endEl.value = '';

    document.querySelectorAll('#reportRangePills .rep-pill-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.range === 'today');
    });
    document.querySelectorAll('#reportDeptPills .rep-pill-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.dept === 'all');
    });
    document.querySelectorAll('#repUpcomingQuickFilter .rep-sub-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.qf === 'all');
    });
    loadReports();
}

function exportReportsCsv() {
    const q = (document.getElementById('reportSearchInput')?.value || '').trim();
    const startVal = (document.getElementById('reportStartDate')?.value || '').trim();
    const endVal = (document.getElementById('reportEndDate')?.value || '').trim();
    const qf = currentReportTab === 'upcoming' ? upcomingQuickFilter : 'all';
    let exportUrl = `/sdn/api/reports.php?export_csv=1&range=${encodeURIComponent(currentReportRange)}&department=${encodeURIComponent(currentReportDept)}&qf=${encodeURIComponent(qf)}&q=${encodeURIComponent(q)}`;
    if (startVal) exportUrl += `&start_date=${encodeURIComponent(startVal)}`;
    if (endVal) exportUrl += `&end_date=${encodeURIComponent(endVal)}`;
    const a = document.createElement('a');
    a.href = exportUrl;
    a.download = '';
    document.body.appendChild(a);
    a.click();
    a.remove();
    showToast('Exporting filtered CSV report...');
}

function getFilteredUpcomingReportRows() {
    const rawList = (cachedReportsData && Array.isArray(cachedReportsData.upcoming_patients))
        ? cachedReportsData.upcoming_patients
        : [];
    const q = (document.getElementById('reportSearchInput')?.value || '').trim().toLowerCase();
    const searched = q
        ? rawList.filter(p =>
            (p.full_name || '').toLowerCase().includes(q) ||
            (p.patient_id || '').toLowerCase().includes(q) ||
            (p.assigned_doctor || '').toLowerCase().includes(q) ||
            (p.normalized_department || p.department || '').toLowerCase().includes(q)
        )
        : rawList;

    if (upcomingQuickFilter === 'needs_action') {
        return searched.filter(p => ['needs_approval', 'needs_doctor', 'inquiry'].includes(p.readiness_state));
    }
    if (upcomingQuickFilter === 'ready') {
        return searched.filter(p => ['ready', 'in_clinic'].includes(p.readiness_state));
    }
    return searched;
}

function printReports() {
    if (!cachedReportsData || !cachedReportsData.summary) {
        showToast('Report data is still loading.', 'warning');
        return;
    }

    const summary = cachedReportsData.summary || {};
    const depts = cachedReportsData.departments || [];
    const upcomingRows = getFilteredUpcomingReportRows();

    const rangeNames = {
        today: 'Today',
        next_7: 'Next 7 Days',
        next_30: 'Next 30 Days',
        monthly: 'This Month',
        all: 'All Time'
    };
    const scopeRange = rangeNames[currentReportRange] || 'Upcoming Schedule';
    const scopeDept = currentReportDept === 'all' ? 'All Departments' : formatDeptTitleCase(currentReportDept);
    const scopeQuick = upcomingQuickFilter === 'needs_action'
        ? 'Needs Action Only'
        : (upcomingQuickFilter === 'ready' ? 'Ready / In Clinic Only' : 'All Operational Statuses');
    const generatedAt = new Date().toLocaleString('en-US', {
        month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit'
    });
    const sealUrl = window.location.origin + '/sdn/public/assets/mmwgh_logo.png';

    const totalUp = Math.max(0, Number(summary.total_upcoming || 0));
    const amPct = totalUp > 0 ? Math.round(((summary.morning_count || 0) / totalUp) * 100) : 0;
    const pmPct = totalUp > 0 ? Math.round(((summary.afternoon_count || 0) / totalUp) * 100) : 0;

    const deptRowsHtml = depts.map(d => `
        <tr>
            <td><strong>${escapeHtml(cleanDeptLabel(d.name))}</strong></td>
            <td style="text-align:center;">${Number(d.upcoming || 0)}</td>
            <td style="text-align:center;">${Number(d.needs_action || 0)}</td>
            <td style="text-align:center;">${Number(d.morning || 0)} AM / ${Number(d.afternoon || 0)} PM</td>
            <td style="text-align:center;">${Number(d.confirmed_ready || 0)}</td>
            <td style="text-align:center;">${Number(d.completed || 0)} (${d.completion_rate || 0}%)</td>
        </tr>
    `).join('');

    const patientRowsHtml = upcomingRows.length ? upcomingRows.map((p, i) => {
        const dName = cleanDeptLabel(p.normalized_department || p.department || '');
        const isInquiry = p.readiness_state === 'inquiry';
        const timeText = p.time_block === 'Afternoon' ? 'Afternoon (1PM–5PM)' : 'Morning (8AM–12PM)';
        const typeLabel = isInquiry ? 'Inquiry' : (p.is_walkin ? 'Walk-in' : 'Online');
        const docText = isInquiry
            ? 'General Inquiry'
            : (p.assigned_doctor && p.assigned_doctor !== 'None' ? p.assigned_doctor : 'Unassigned Physician');
        const dateRaw = p.record_date || p.appointment_date || '';
        const dateObj = dateRaw ? new Date(String(dateRaw).split(' ')[0] + 'T00:00:00') : null;
        const dateFmt = (dateObj && !isNaN(dateObj.getTime()))
            ? dateObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
            : (dateRaw || '—');
        const pillCls = p.readiness_state === 'ready' ? 'is-ready'
            : (p.readiness_state === 'in_clinic' ? 'is-info'
                : (p.readiness_state === 'inquiry' ? 'is-inquiry' : 'is-warn'));

        return `
            <tr>
                <td style="text-align:center;color:#64748b;">${i + 1}</td>
                <td>
                    <div style="font-weight:600;color:#0f172a;">${escapeHtml(dateFmt)}</div>
                    <div style="font-size:10px;color:#475569;">${escapeHtml(timeText)}</div>
                </td>
                <td>
                    <div style="font-weight:700;color:#0f172a;">${escapeHtml(p.full_name || 'Patient')}</div>
                    <div style="font-size:10px;color:#64748b;">${escapeHtml(p.patient_id || '')}${p.contact_number ? ' · ' + escapeHtml(p.contact_number) : ''}</div>
                </td>
                <td>${escapeHtml(dName)}</td>
                <td>${escapeHtml(docText)}</td>
                <td>${escapeHtml(typeLabel)}</td>
                <td><span class="pill ${pillCls}">${escapeHtml(p.readiness_label || 'Pending')}</span></td>
            </tr>
        `;
    }).join('') : `<tr><td colspan="7" style="text-align:center;padding:18px;color:#64748b;">No upcoming patient records match the active filter criteria.</td></tr>`;

    const printHtml = `<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>MMWGH Operational Report - ${escapeHtml(scopeRange)}</title>
<style>
  @page { size: A4 landscape; margin: 11mm 12mm; }
  * { box-sizing: border-box; }
  body {
    margin: 0;
    padding: 0;
    font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    color: #0f172a;
    background: #ffffff;
    font-size: 11.5px;
    line-height: 1.4;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
  .rep-print-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 2px solid #184201;
    padding-bottom: 10px;
    margin-bottom: 12px;
  }
  .rep-print-brand {
    display: flex;
    align-items: center;
    gap: 12px;
  }
  .rep-print-brand img {
    width: 46px;
    height: 46px;
    object-fit: contain;
  }
  .rep-print-hosp {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #15803d;
  }
  .rep-print-title {
    font-size: 17px;
    font-weight: 800;
    color: #0f172a;
    margin: 2px 0 0;
  }
  .rep-print-meta {
    text-align: right;
    font-size: 10.5px;
    color: #475569;
  }
  .rep-print-meta strong { color: #0f172a; }
  .rep-print-kpis {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    margin-bottom: 12px;
  }
  .kpi-box {
    border: 1px solid #cbd5e1;
    border-top: 3px solid #15803d;
    border-radius: 6px;
    padding: 8px 10px;
    background: #f8fafc;
  }
  .kpi-box.warn { border-top-color: #d97706; }
  .kpi-box.info { border-top-color: #0284c7; }
  .kpi-box.slate { border-top-color: #475569; }
  .kpi-lbl { font-size: 9.5px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.04em; }
  .kpi-val { font-size: 20px; font-weight: 800; color: #0f172a; margin: 2px 0; }
  .kpi-sub { font-size: 10px; color: #64748b; }
  .section-title {
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #184201;
    margin: 12px 0 6px;
  }
  .shift-note {
    display: flex;
    gap: 18px;
    padding: 7px 10px;
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 6px;
    font-size: 10.5px;
    margin-bottom: 10px;
  }
  table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 10px;
  }
  th, td {
    border: 1px solid #e2e8f0;
    padding: 6px 8px;
    text-align: left;
    vertical-align: top;
  }
  th {
    background: #f1f5f9;
    color: #334155;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.03em;
  }
  .pill {
    display: inline-block;
    padding: 2px 7px;
    border-radius: 999px;
    font-size: 9.5px;
    font-weight: 700;
    white-space: nowrap;
  }
  .pill.is-ready { background: #dcfce7; color: #166534; }
  .pill.is-warn { background: #fef3c7; color: #92400e; }
  .pill.is-info { background: #e0f2fe; color: #0369a1; }
  .pill.is-inquiry { background: #fef9c3; color: #854d0e; }
  .rep-print-foot {
    margin-top: 10px;
    padding-top: 6px;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    font-size: 9.5px;
    color: #64748b;
  }
</style>
</head>
<body>
  <div class="rep-print-header">
    <div class="rep-print-brand">
      <img src="${sealUrl}" alt="MMWGH Seal">
      <div>
        <div class="rep-print-hosp">Mariveles Mental Wellness and General Hospital</div>
        <h1 class="rep-print-title">OPD Operations &amp; Upcoming Patient Summary</h1>
      </div>
    </div>
    <div class="rep-print-meta">
      <div><strong>Period:</strong> ${escapeHtml(scopeRange)} &bull; <strong>Department:</strong> ${escapeHtml(scopeDept)}</div>
      <div><strong>Filter:</strong> ${escapeHtml(scopeQuick)}</div>
      <div><strong>Generated:</strong> ${escapeHtml(generatedAt)}</div>
    </div>
  </div>

  <div class="rep-print-kpis">
    <div class="kpi-box">
      <div class="kpi-lbl">Total Patients</div>
      <div class="kpi-val">${Number(summary.total_patients || 0)}</div>
      <div class="kpi-sub">${Number(summary.total_online || 0)} online &middot; ${Number(summary.total_walkin || 0)} walk-in / unscheduled</div>
    </div>
    <div class="kpi-box info">
      <div class="kpi-lbl">Attending Doctors</div>
      <div class="kpi-val">${Number(summary.total_doctors_active || 0)}</div>
      <div class="kpi-sub">Active doctors with consultations</div>
    </div>
    <div class="kpi-box slate">
      <div class="kpi-lbl">Completed Consultations</div>
      <div class="kpi-val">${Number(summary.total_completed || 0)}</div>
      <div class="kpi-sub">${escapeHtml(summary.completion_rate_text || '0%')} completion rate</div>
    </div>
    <div class="kpi-box">
      <div class="kpi-lbl">Medications Prescribed</div>
      <div class="kpi-val">${Number(summary.total_medications_prescribed || 0)}</div>
      <div class="kpi-sub">Total prescriptions logged</div>
    </div>
  </div>

  <div class="shift-note">
    <span><strong>Morning Block (8:00 AM – 12:00 PM):</strong> ${Number(summary.morning_count || 0)} patient(s) (${amPct}%)</span>
    <span><strong>Afternoon Block (1:00 PM – 5:00 PM):</strong> ${Number(summary.afternoon_count || 0)} patient(s) (${pmPct}%)</span>
    <span><strong>Physician Assigned:</strong> ${Number(summary.assigned_doctor_count || 0)} of ${totalUp} upcoming visits</span>
  </div>

  <div class="section-title">Department Operational Summary</div>
  <table>
    <thead>
      <tr>
        <th>Department</th>
        <th style="text-align:center;">Upcoming Visits</th>
        <th style="text-align:center;">Action Needed</th>
        <th style="text-align:center;">Morning / Afternoon</th>
        <th style="text-align:center;">Ready / In Clinic</th>
        <th style="text-align:center;">Completed</th>
      </tr>
    </thead>
    <tbody>${deptRowsHtml || '<tr><td colspan="6" style="text-align:center;color:#64748b;">No department records.</td></tr>'}</tbody>
  </table>

  <div class="section-title">Upcoming Patient Roster (${upcomingRows.length} Record${upcomingRows.length === 1 ? '' : 's'})</div>
  <table>
    <thead>
      <tr>
        <th style="width:32px;text-align:center;">#</th>
        <th>Schedule Date &amp; Shift</th>
        <th>Patient &amp; Contact</th>
        <th>Department</th>
        <th>Attending Physician</th>
        <th>Type</th>
        <th>Readiness</th>
      </tr>
    </thead>
    <tbody>${patientRowsHtml}</tbody>
  </table>

  <div class="rep-print-foot">
    <span>LINGKOD OPD Admin Portal &bull; Official Printable Operational Summary</span>
    <span>Showing ${upcomingRows.length} patient record(s) for ${escapeHtml(scopeRange)}</span>
  </div>
</body>
</html>`;

    const oldFrame = document.getElementById('repPrintFrame');
    if (oldFrame) oldFrame.remove();

    const iframe = document.createElement('iframe');
    iframe.id = 'repPrintFrame';
    iframe.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;visibility:hidden;';
    document.body.appendChild(iframe);

    const frameDoc = iframe.contentWindow?.document || iframe.contentDocument;
    if (!frameDoc) {
        window.print();
        return;
    }
    frameDoc.open();
    frameDoc.write(printHtml);
    frameDoc.close();

    setTimeout(() => {
        try {
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
        } catch (err) {
            window.print();
        }
    }, 350);
}

function applyCustomReportDateRange() {
    const startVal = document.getElementById('reportStartDate')?.value || '';
    const endVal = document.getElementById('reportEndDate')?.value || '';
    if (startVal || endVal) {
        currentReportRange = 'custom';
        document.querySelectorAll('#reportRangePills .rep-pill-btn').forEach(b => b.classList.remove('active'));
    }
    loadReports();
}

// ── Streamlined Report Generation Dashboard ─────────────────────────────────
const REPORT_GEN_SCHEMAS = {
    diagnosis: {
        label: 'Diagnosis',
        columns: [
            { key: 'date', label: 'Date', numeric: true },
            { key: 'patient_name', label: 'Patient' },
            { key: 'department', label: 'Department' },
            { key: 'diagnosis', label: 'Diagnosis' },
            { key: 'doctor_name', label: 'Attending Physician' }
        ]
    },
    appointments: {
        label: 'Appointments',
        columns: [
            { key: 'date', label: 'Date', numeric: true },
            { key: 'reference_no', label: 'Ref No.', numeric: true },
            { key: 'patient_name', label: 'Patient' },
            { key: 'department', label: 'Department' },
            { key: 'status', label: 'Status' }
        ]
    },
    patients: {
        label: 'Patients',
        columns: [
            { key: 'patient_id', label: 'Patient ID', numeric: true },
            { key: 'full_name', label: 'Full Name' },
            { key: 'gender', label: 'Gender' },
            { key: 'age', label: 'Age', numeric: true },
            { key: 'registered_at', label: 'Registered', numeric: true }
        ]
    },
    doctors: {
        label: 'Doctors',
        columns: [
            { key: 'name', label: 'Physician' },
            { key: 'department', label: 'Department' },
            { key: 'specialization', label: 'Specialization' },
            { key: 'consultations_count', label: 'Consultations', numeric: true },
            { key: 'status', label: 'Status' }
        ]
    },
    medication: {
        label: 'Medication',
        columns: [
            { key: 'date', label: 'Date', numeric: true },
            { key: 'patient_name', label: 'Patient' },
            { key: 'medication', label: 'Medication' },
            { key: 'dosage', label: 'Dosage' },
            { key: 'prescribed_by', label: 'Prescribed By' }
        ]
    }
};

let repGenActiveTab = 'appointments';
let repGenRows = [];
let repGenAbortCtrl = null;
let repGenInitialized = false;

function getLocalIsoDate(offsetDays = 0) {
    const d = new Date(Date.now() - new Date().getTimezoneOffset() * 60000 + offsetDays * 86400000);
    return d.toISOString().slice(0, 10);
}

let repGenLastQuery = null;

function formatReportDateRangeLabel(fromVal, toVal) {
    if (fromVal && toVal) return `${fromVal} to ${toVal}`;
    if (fromVal) return `From ${fromVal}`;
    if (toVal) return `Up to ${toVal}`;
    return 'All dates';
}

function initReportGenDates() {
    const fromEl = document.getElementById('repGenFrom');
    const toEl = document.getElementById('repGenTo');
    if (!fromEl || !toEl) return;
    const today = getLocalIsoDate(0);
    const allowFuture = repGenActiveTab === 'appointments';
    if (allowFuture) {
        fromEl.removeAttribute('max');
        toEl.removeAttribute('max');
    } else {
        fromEl.max = today;
        toEl.max = today;
        if (fromEl.value && fromEl.value > today) fromEl.value = today;
        if (toEl.value && toEl.value > today) toEl.value = today;
    }
    if (fromEl.value && toEl.value && fromEl.value > toEl.value) {
        fromEl.value = toEl.value;
    }
}

function renderReportGenHeaders() {
    const schema = REPORT_GEN_SCHEMAS[repGenActiveTab] || REPORT_GEN_SCHEMAS.appointments;
    const thead = document.getElementById('repGenThead');
    const titleEl = document.getElementById('repGenTableTitle');
    if (titleEl) titleEl.textContent = `${schema.label} Report`;
    if (thead) {
        thead.innerHTML = `<tr>${schema.columns.map(c => `<th scope="col">${escapeHtml(c.label)}</th>`).join('')}</tr>`;
    }
}

function renderReportGenRows(loading = false) {
    const schema = REPORT_GEN_SCHEMAS[repGenActiveTab] || REPORT_GEN_SCHEMAS.appointments;
    const tbody = document.getElementById('repGenTbody');
    const metaEl = document.getElementById('repGenTableMeta');
    const printBtn = document.getElementById('btnRepGenPrint');
    const exportBtn = document.getElementById('btnRepGenExport');
    const fromVal = repGenLastQuery ? repGenLastQuery.from : (document.getElementById('repGenFrom')?.value || '');
    const toVal = repGenLastQuery ? repGenLastQuery.to : (document.getElementById('repGenTo')?.value || '');

    if (metaEl) {
        const countText = `${repGenRows.length} ${repGenRows.length === 1 ? 'record' : 'records'}`;
        const rangeLabel = formatReportDateRangeLabel(fromVal, toVal);
        metaEl.textContent = loading ? `Loading ${schema.label.toLowerCase()} records…` : `${rangeLabel} · ${countText}`;
    }
    const hasRows = !loading && repGenRows.length > 0;
    if (printBtn) printBtn.disabled = !hasRows;
    if (exportBtn) exportBtn.disabled = !hasRows;

    if (!tbody) return;
    if (loading) {
        tbody.innerHTML = `<tr><td colspan="${schema.columns.length}" class="repgen-empty">Loading ${escapeHtml(schema.label.toLowerCase())} records…</td></tr>`;
        return;
    }
    if (!repGenRows.length) {
        tbody.innerHTML = `<tr><td colspan="${schema.columns.length}" class="repgen-empty">No ${escapeHtml(schema.label.toLowerCase())} records found for the selected filters.</td></tr>`;
        return;
    }
    tbody.innerHTML = repGenRows.map(row => `
        <tr>
            ${schema.columns.map(col => `<td class="${col.numeric ? 'is-num' : ''}">${escapeHtml(row[col.key] ?? '—')}</td>`).join('')}
        </tr>
    `).join('');
}

function setReportGenError(msg) {
    const errEl = document.getElementById('repGenError');
    if (!errEl) return;
    errEl.textContent = msg || '';
    errEl.hidden = !msg;
}

function selectReportCategory(tab) {
    if (!REPORT_GEN_SCHEMAS[tab]) return;
    repGenActiveTab = tab;
    document.querySelectorAll('#repGenTabs .repgen-tab').forEach(btn => {
        const isActive = btn.dataset.category === tab;
        btn.classList.toggle('active', isActive);
        btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
    });
    initReportGenDates();
    renderReportGenHeaders();
    generateCategoryReport();
}

async function generateCategoryReport(e, silent = false) {
    if (e && typeof e.preventDefault === 'function') e.preventDefault();
    initReportGenDates();

    const fromVal = (document.getElementById('repGenFrom')?.value || '').trim();
    const toVal = (document.getElementById('repGenTo')?.value || '').trim();
    const deptVal = (document.getElementById('repGenDept')?.value || '').trim();
    const genBtn = document.getElementById('btnGenerateReport');
    const refreshBtn = document.getElementById('btnRefreshReport');
    const today = getLocalIsoDate(0);
    const allowFuture = repGenActiveTab === 'appointments';

    if (fromVal && toVal && fromVal > toVal) {
        if (!silent) setReportGenError('From date cannot be later than To date.');
        return;
    }
    if (!allowFuture && ((fromVal && fromVal > today) || (toVal && toVal > today))) {
        if (!silent) setReportGenError('Select a valid past or current date range.');
        return;
    }

    if (!silent) setReportGenError('');
    if (repGenAbortCtrl) repGenAbortCtrl.abort();
    repGenAbortCtrl = new AbortController();

    renderReportGenHeaders();
    if (!silent) {
        renderReportGenRows(true);
        if (genBtn) {
            genBtn.disabled = true;
            genBtn.setAttribute('aria-busy', 'true');
            genBtn.textContent = 'Generating…';
        }
        if (refreshBtn) {
            refreshBtn.disabled = true;
            refreshBtn.classList.add('is-spinning');
            refreshBtn.setAttribute('aria-busy', 'true');
        }
    }

    try {
        const params = new URLSearchParams({
            category: repGenActiveTab,
            _: String(Date.now())
        });
        if (fromVal) params.set('from', fromVal);
        if (toVal) params.set('to', toVal);
        if (deptVal) params.set('department', deptVal);

        const res = await fetch(`/sdn/api/reports.php?${params.toString()}`, {
            headers: { Accept: 'application/json' },
            signal: repGenAbortCtrl.signal
        });
        const json = await res.json();
        if (!res.ok || !json.success) {
            throw new Error(json.message || `Request failed (${res.status})`);
        }
        repGenRows = Array.isArray(json.data) ? json.data : [];
        repGenLastQuery = {
            category: json.category || repGenActiveTab,
            from: json.from ?? fromVal,
            to: json.to ?? toVal,
            department: deptVal
        };
        renderReportGenRows(false);
    } catch (err) {
        if (err.name === 'AbortError') return;
        if (!silent) {
            repGenRows = [];
            renderReportGenRows(false);
            setReportGenError(err.message || 'Unable to load report data.');
        }
    } finally {
        if (!silent) {
            if (genBtn) {
                genBtn.disabled = false;
                genBtn.removeAttribute('aria-busy');
                genBtn.textContent = 'Generate Report';
            }
            if (refreshBtn) {
                refreshBtn.disabled = false;
                refreshBtn.classList.remove('is-spinning');
                refreshBtn.removeAttribute('aria-busy');
            }
        }
    }
}

function exportCategoryExcel() {
    if (!repGenRows.length) return;
    const q = repGenLastQuery || {
        category: repGenActiveTab,
        from: (document.getElementById('repGenFrom')?.value || '').trim(),
        to: (document.getElementById('repGenTo')?.value || '').trim(),
        department: (document.getElementById('repGenDept')?.value || '').trim()
    };
    const params = new URLSearchParams({
        category: q.category,
        export_excel: '1'
    });
    if (q.from) params.set('from', q.from);
    if (q.to) params.set('to', q.to);
    if (q.department) params.set('department', q.department);
    const a = document.createElement('a');
    a.href = `/sdn/api/reports.php?${params.toString()}`;
    a.download = '';
    document.body.appendChild(a);
    a.click();
    a.remove();
    showToast(`Exporting ${REPORT_GEN_SCHEMAS[q.category].label} report…`);
}

function printCategoryReport() {
    if (!repGenRows.length) return;
    const q = repGenLastQuery || {
        category: repGenActiveTab,
        from: document.getElementById('repGenFrom')?.value || '',
        to: document.getElementById('repGenTo')?.value || '',
        department: document.getElementById('repGenDept')?.value || ''
    };
    const schema = REPORT_GEN_SCHEMAS[q.category] || REPORT_GEN_SCHEMAS.appointments;
    const rangeLabel = formatReportDateRangeLabel(q.from, q.to);
    const deptVal = q.department || 'All Departments';
    const sealUrl = window.location.origin + '/sdn/public/assets/mmwgh_logo.png';
    const generatedAt = new Date().toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });

    const headCells = schema.columns.map(c => `<th>${escapeHtml(c.label)}</th>`).join('');
    const bodyRows = repGenRows.map(r =>
        `<tr>${schema.columns.map(c => `<td>${escapeHtml(r[c.key] ?? '—')}</td>`).join('')}</tr>`
    ).join('');

    const win = window.open('', '_blank', 'width=960,height=720');
    if (!win) {
        window.print();
        return;
    }
    win.document.write(`<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>${escapeHtml(schema.label)} Report | MMWGH</title>
        <style>
            body{font-family:Inter,-apple-system,sans-serif;color:#111827;margin:28px;}
            .hd{display:flex;align-items:center;gap:14px;border-bottom:2px solid #166534;padding-bottom:12px;margin-bottom:16px;}
            .hd img{width:46px;height:46px;object-fit:contain;}
            .hd h1{font-size:16px;margin:0;color:#14532d;}
            .hd p{font-size:12px;margin:3px 0 0;color:#4b5563;}
            table{width:100%;border-collapse:collapse;font-size:12px;}
            th{text-align:left;padding:8px 10px;background:#f3f4f6;border-bottom:1px solid #d1d5db;font-size:11px;text-transform:uppercase;color:#374151;}
            td{padding:8px 10px;border-bottom:1px solid #e5e7eb;}
            tbody tr:nth-child(even){background:#f9fafb;}
            @media print{body{margin:12mm;}}
        </style></head><body>
        <div class="hd">
            <img src="${sealUrl}" alt="MMWGH">
            <div>
                <h1>MARIVELES MENTAL WELLNESS AND GENERAL HOSPITAL — ${escapeHtml(schema.label.toUpperCase())} REPORT</h1>
                <p>Date Range: ${escapeHtml(rangeLabel)} &middot; Department: ${escapeHtml(deptVal)} &middot; ${repGenRows.length} records &middot; Generated ${escapeHtml(generatedAt)}</p>
            </div>
        </div>
        <table><thead><tr>${headCells}</tr></thead><tbody>${bodyRows}</tbody></table>
        <script>window.onload=function(){window.print();};<\/script>
    </body></html>`);
    win.document.close();
}

async function loadReports(silent = false) {
    if (!document.getElementById('repGenForm')) return;
    initReportGenDates();
    if (!repGenInitialized) {
        repGenInitialized = true;
        renderReportGenHeaders();
    }
    await generateCategoryReport(null, silent);
}

window.selectReportCategory = selectReportCategory;
window.generateCategoryReport = generateCategoryReport;
window.exportCategoryExcel = exportCategoryExcel;
window.printCategoryReport = printCategoryReport;

    async function _legacyLoadReports() {
        const rangeLabels = {
            today: 'Showing Consultation Report for Today',
            past_7: 'Showing Consultation Report for the Past 7 Days',
            past_30: 'Showing Consultation Report for the Past 30 Days',
            past_month: 'Showing Consultation Report for the Past 1 Month',
            custom: 'Showing Consultation Report for Selected Date Range',
            all: 'Showing All Time Consultation Report'
        };
        const startVal = document.getElementById('reportStartDate')?.value || '';
        const endVal = document.getElementById('reportEndDate')?.value || '';
        const rangeLabelEl = document.getElementById('reportRangeLabel');
        if (rangeLabelEl) {
            const deptName = currentReportDept === 'all' ? 'All Departments' : formatDeptTitleCase(currentReportDept);
            const customText = (startVal || endVal) ? ` (${startVal || '…'} to ${endVal || '…'})` : '';
            rangeLabelEl.textContent = `${rangeLabels[currentReportRange] || rangeLabels.today}${customText} · ${deptName}`;
        }

        try {
            let url = `/sdn/api/reports.php?range=${encodeURIComponent(currentReportRange)}&department=${encodeURIComponent(currentReportDept)}&_=${Date.now()}`;
            if (startVal) url += `&start_date=${encodeURIComponent(startVal)}`;
            if (endVal) url += `&end_date=${encodeURIComponent(endVal)}`;
            const res = await fetch(url).then(r => r.json());

            const repData = res.data || res;
            if (!res.success || !repData || !repData.summary) {
                const tbody = document.getElementById('reportUpcomingTbody');
                if (tbody) {
                    tbody.innerHTML = '<tr><td colspan="6" class="modern-table-empty" style="color:#ef4444;">Failed to load reports data.</td></tr>';
                }
                return;
            }

            cachedReportsData = repData;
            const summary = repData.summary || {};
            const depts = repData.departments || [];
            const chartSeries = repData.chart_series || [];

            // Update 4 Consultation Report KPI Cards (Patients, Doctors, Completed, Medications)
            const setEl = (id, text) => {
                const el = document.getElementById(id);
                if (el) el.textContent = text;
            };

            setEl('repKpiTotal', Number(summary.total_patients || 0).toLocaleString());
            setEl('repKpiSubTotal', `${summary.total_online || 0} online · ${summary.total_walkin || 0} walk-in / unscheduled`);

            setEl('repKpiReady', Number(summary.total_doctors_active || 0).toLocaleString());
            setEl('repKpiSubReady', 'Active doctors with consultations');

            setEl('repKpiCompleted', Number(summary.total_completed || 0).toLocaleString());
            setEl('repKpiSubCompleted', `${summary.completion_rate_text || '0%'} completion rate`);

            setEl('repKpiNoshow', Number(summary.total_medications_prescribed || 0).toLocaleString());
            setEl('repKpiSubNoshow', 'Total prescriptions logged');

            setEl('reportLastUpdated', `Updated: ${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' })}`);

            const chartTitleEl = document.getElementById('repChartTitle');
            if (chartTitleEl) {
                chartTitleEl.textContent = currentReportRange === 'all'
                    ? 'Appointments by Month'
                    : 'Appointments by Day';
            }

            renderReportsAnalyticsChart(chartSeries);
            renderUpcomingPatientsTable();
        } catch (e) {
            console.error('Error fetching reports:', e);
            const tbody = document.getElementById('reportUpcomingTbody');
            if (tbody) {
                tbody.innerHTML = '<tr><td colspan="6" class="modern-table-empty" style="color:#ef4444;">Error connecting to reports server.</td></tr>';
            }
        }
    }

    function renderReportsAnalyticsChart(chartSeries) {
        const canvas = document.getElementById('reportsAnalyticsChart');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');

        if (reportsAnalyticsChartInstance) {
            reportsAnalyticsChartInstance.destroy();
        }

        const series = (chartSeries && chartSeries.length > 0)
            ? chartSeries
            : [{ label: 'No Scheduled Visits', general: 0, neurology: 0, psychiatry: 0 }];

        const labels = series.map(s => s.label);
        const totalData = series.map(s => Number(s.general || 0) + Number(s.neurology || 0) + Number(s.psychiatry || 0));

        reportsAnalyticsChartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Appointments',
                        data: totalData,
                        backgroundColor: '#15803d',
                        borderRadius: 6,
                        maxBarThickness: 40
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        ticks: { color: '#475569', font: { family: "'Inter', sans-serif", size: 11, weight: '500' } },
                        grid: { display: false }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0, color: '#64748b' },
                        grid: { color: '#f1f5f9' }
                    }
                }
            }
        });
    }

    function renderReportReadinessPanel(summary) {
        const body = document.getElementById('reportReadinessBody');
        if (!body) return;

        const morning = Number(summary.morning_count || 0);
        const afternoon = Number(summary.afternoon_count || 0);
        const shiftTotal = morning + afternoon;
        const mPct = shiftTotal > 0 ? Math.round((morning / shiftTotal) * 100) : 50;
        const aPct = shiftTotal > 0 ? (100 - mPct) : 50;

        const upcomingTotal = Number(summary.total_upcoming || 0);
        const assignedCount = Number(summary.assigned_doctor_count || 0);
        const docPct = upcomingTotal > 0 ? Math.round((assignedCount / upcomingTotal) * 100) : 0;

        body.innerHTML = `
        <div class="rep-metric-block">
            <div class="rep-metric-head">
                <span>Time Block Balance</span>
                <span>${shiftTotal} scheduled</span>
            </div>
            <div class="rep-progress-track">
                <div class="rep-progress-fill is-morning" style="width:${shiftTotal > 0 ? mPct : 0}%"></div>
                <div class="rep-progress-fill is-afternoon" style="width:${shiftTotal > 0 ? aPct : 0}%"></div>
            </div>
            <div class="rep-progress-legend">
                <span>Morning (8AM–12PM): <strong>${morning}</strong></span>
                <span>Afternoon (1PM–5PM): <strong>${afternoon}</strong></span>
            </div>
        </div>

        <div class="rep-metric-block">
            <div class="rep-metric-head">
                <span>Physician Assignment Readiness</span>
                <span>${docPct}% assigned</span>
            </div>
            <div class="rep-progress-track">
                <div class="rep-progress-fill is-assigned" style="width:${docPct}%"></div>
            </div>
            <div class="rep-progress-legend">
                <span>Assigned: <strong>${assignedCount}</strong></span>
                <span>Needs Doctor: <strong>${summary.total_needs_doctor || 0}</strong></span>
            </div>
        </div>

        <div class="rep-readiness-summary-box">
            <div class="rep-mini-stat">
                <div class="rep-mini-stat-label">Pending Inquiries</div>
                <div class="rep-mini-stat-val">${Number(summary.total_inquiries || 0)}</div>
            </div>
            <div class="rep-mini-stat">
                <div class="rep-mini-stat-label">No-Show / Cancelled</div>
                <div class="rep-mini-stat-val">${Number(summary.total_noshow || 0)}</div>
            </div>
        </div>
    `;
    }

    function renderUpcomingPatientsTable() {
        const tbody = document.getElementById('reportUpcomingTbody');
        if (!tbody) return;

        const rawList = (cachedReportsData && Array.isArray(cachedReportsData.upcoming_patients))
            ? cachedReportsData.upcoming_patients
            : [];
        const q = (document.getElementById('reportSearchInput')?.value || '').trim().toLowerCase();

        const searched = q
            ? rawList.filter(p =>
                (p.full_name || '').toLowerCase().includes(q) ||
                (p.patient_id || '').toLowerCase().includes(q) ||
                (p.assigned_doctor || '').toLowerCase().includes(q) ||
                (p.normalized_department || p.department || '').toLowerCase().includes(q)
            )
            : rawList;

        const list = searched;

        if (!list.length) {
            tbody.innerHTML = '<tr><td colspan="5" class="modern-table-empty">No appointments match the selected filters.</td></tr>';
            return;
        }

        tbody.innerHTML = list.map(p => {
            const isInquiry = p.readiness_state === 'inquiry';
            const timeText = p.time_block === 'Afternoon' ? 'Afternoon (1PM–5PM)' : 'Morning (8AM–12PM)';
            const typeLabel = isInquiry ? 'Inquiry' : (p.is_walkin ? 'Walk-in' : 'Online');
            const docText = isInquiry
                ? 'General Inquiry'
                : (p.assigned_doctor && p.assigned_doctor !== 'None' ? escapeHtml(p.assigned_doctor) : '<span style="color:#b45309;font-weight:600;">Unassigned</span>');

            const pillCls = p.readiness_state === 'ready' ? 'is-ready'
                : (p.readiness_state === 'in_clinic' ? 'is-info'
                    : (p.readiness_state === 'inquiry' ? 'is-inquiry' : 'is-warn'));

            const safeId = escapeHtml(p.patient_id);
            return `
            <tr class="clickable-row" onclick="openPatientDetailsModal('${safeId}')" title="Click to view patient details">
                <td>${formatStackedDate(p.record_date || p.appointment_date, timeText)}</td>
                <td>
                    <div class="cell-primary">${escapeHtml(p.full_name || 'Patient')}</div>
                    <div class="patient-cell-meta">${safeId}</div>
                </td>
                <td>${docText}</td>
                <td>${escapeHtml(typeLabel)}</td>
                <td><span class="rep-readiness-pill ${pillCls}">${escapeHtml(p.readiness_label || 'Pending')}</span></td>
            </tr>
        `;
        }).join('');
    }

    function renderDepartmentsSummaryTable(depts) {
        const tbody = document.getElementById('reportDeptsTbody');
        if (!tbody) return;
        if (!depts || !depts.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="modern-table-empty">No department summary available.</td></tr>';
            return;
        }

        tbody.innerHTML = depts.map(d => `
        <tr>
            <td><span class="table-dept-tag">${escapeHtml(cleanDeptLabel(d.name))}</span></td>
            <td><strong>${Number(d.upcoming || 0)}</strong></td>
            <td>${Number(d.needs_action || 0) > 0 ? `<span class="rep-readiness-pill is-warn">${Number(d.needs_action)} pending</span>` : '<span class="rep-readiness-pill is-ready">All clear</span>'}</td>
            <td>${Number(d.morning || 0)} AM / ${Number(d.afternoon || 0)} PM</td>
            <td>${Number(d.confirmed_ready || 0)}</td>
            <td>${Number(d.completed || 0)} (${d.completion_rate || 0}%)</td>
        </tr>
    `).join('');
    }

    function renderDailyReportsTable(dailyList) {
        const tbody = document.getElementById('reportDailyTbody');
        if (!tbody) return;

        if (!dailyList || dailyList.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="modern-table-empty">No daily records found for the selected filter range.</td></tr>';
            return;
        }

        tbody.innerHTML = dailyList.map(item => {
            const dName = cleanDeptLabel(item.department || '');
            return `
            <tr>
                <td>${formatStackedDate(item.date || item.formatted_date)}</td>
                <td><span class="table-dept-tag">${escapeHtml(dName)}</span></td>
                <td><strong style="color:#0f172a;">${item.total}</strong></td>
                <td>${Number(item.upcoming || 0)}</td>
                <td>${Number(item.walkin || 0)} walk-in / ${Number(item.online || 0)} online</td>
                <td>${Number(item.completed || 0)}</td>
                <td><span class="rep-readiness-pill is-ready">${item.completion_rate}%</span></td>
            </tr>
        `;
        }).join('');
    }

    function renderMonthlyReportsTable(monthlyList) {
        const tbody = document.getElementById('reportMonthlyTbody');
        if (!tbody) return;

        if (!monthlyList || monthlyList.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="modern-table-empty">No monthly records found for the selected filter range.</td></tr>';
            return;
        }

        tbody.innerHTML = monthlyList.map(item => {
            const dName = cleanDeptLabel(item.department || '');
            return `
            <tr>
                <td><strong>${escapeHtml(item.month_label || item.month)}</strong></td>
                <td><span class="table-dept-tag">${escapeHtml(dName)}</span></td>
                <td><strong style="color:#0f172a;">${item.total}</strong></td>
                <td>${Number(item.upcoming || 0)}</td>
                <td>${Number(item.walkin || 0)} walk-in / ${Number(item.online || 0)} online</td>
                <td>${Number(item.completed || 0)}</td>
                <td><span class="rep-readiness-pill is-ready">${item.completion_rate}%</span></td>
            </tr>
        `;
        }).join('');
    }

    function titleStatus(value) {
        const raw = String(value || '').replace(/[_-]+/g, ' ').trim().toLowerCase();
        if (!raw) return '';
        return raw.replace(/\b\w/g, (c) => c.toUpperCase());
    }

    function formatStackedDate(dateStr, timeStr) {
        if (!dateStr) return '<div class="date-stack-sub">—</div>';
        const raw = String(dateStr).trim();
        const iso = raw.match(/^(\d{4})-(\d{2})-(\d{2})/);
        const d = iso ? new Date(`${iso[1]}-${iso[2]}-${iso[3]}T00:00:00`) : new Date(raw);
        if (isNaN(d.getTime())) {
            return `<div class="date-stack-main">${escapeHtml(raw)}</div>`;
        }
        const monthDay = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        let timeLabel = '';
        if (timeStr) {
            const parts = String(timeStr).split(':');
            const hour = parseInt(parts[0], 10);
            if (parts.length >= 2 && !isNaN(hour)) {
                const ampm = hour >= 12 ? 'PM' : 'AM';
                const h12 = hour % 12 || 12;
                timeLabel = `${h12}:${parts[1]} ${ampm}`;
            } else {
                timeLabel = String(timeStr);
            }
        }
        return `<div class="date-stack-main">${escapeHtml(monthDay)}</div><div class="date-stack-sub">${d.getFullYear()}${timeLabel ? ' · ' + escapeHtml(timeLabel) : ''}</div>`;
    }

    function formatStackedTimestamp(ts) {
        if (!ts) return '<div class="date-stack-sub">—</div>';
        const d = new Date(String(ts).replace(' ', 'T'));
        if (isNaN(d.getTime())) return `<div class="date-stack-main">${escapeHtml(String(ts))}</div>`;
        const monthDay = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        const time = d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
        return `<div class="date-stack-main">${monthDay}</div><div class="date-stack-sub">${d.getFullYear()} · ${time}</div>`;
    }

    window.toggleRowMenu = function (event, btn) {
        event.preventDefault();
        event.stopPropagation();
        const pop = btn.parentElement.querySelector('.row-menu-pop');
        if (!pop) return;
        const willOpen = !pop.classList.contains('open');
        document.querySelectorAll('.row-menu-pop.open').forEach(el => el.classList.remove('open'));
        document.querySelectorAll('.row-menu-btn[aria-expanded="true"]').forEach(el => el.setAttribute('aria-expanded', 'false'));
        if (!willOpen) return;
        const rect = btn.getBoundingClientRect();
        pop.style.position = 'fixed';
        pop.style.top = (rect.bottom + 6) + 'px';
        pop.style.left = Math.max(8, rect.right - 176) + 'px';
        pop.classList.add('open');
        btn.setAttribute('aria-expanded', 'true');
    };

    document.addEventListener('click', () => {
        document.querySelectorAll('.row-menu-pop.open').forEach(el => el.classList.remove('open'));
        document.querySelectorAll('.row-menu-btn[aria-expanded="true"]').forEach(el => el.setAttribute('aria-expanded', 'false'));
    });

