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
            
            // Auto-refresh data when switching tabs and sync fixed header scrollbars
            if (targetView === 'queue') loadQueue();
            if (targetView === 'emr') loadEMR();
            if (targetView === 'profile') loadStaffProfileView();
            setTimeout(syncTableScrollbars, 60);
        });
    });

    // Close Modals
    document.querySelectorAll('.close, .btn-modal-cancel').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.modal').forEach(m => m.classList.remove('show'));
        });
    });

    loadQueue();
    loadEMR();
    loadDepartmentDoctors();

    // Auto real-time queue refresh every 6 seconds
    setInterval(() => {
        const activeSection = document.querySelector('.view-section.active');
        if (activeSection && activeSection.id === 'view-queue') {
            loadQueue();
            loadDepartmentDoctors();
        } else if (activeSection && activeSection.id === 'view-emr') {
            loadEMR();
        }
    }, 6000);

    // Refresh immediately when returning to tab
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            loadQueue();
            loadEMR();
            loadDepartmentDoctors();
        }
    });
    window.addEventListener('focus', () => {
        loadQueue();
    });
});

// ── Scrollbar synchronization for fixed-header custom tables ────────────────
function syncTableScrollbars() {
    requestAnimationFrame(() => {
        document.querySelectorAll('.custom-table-card .custom-table-header').forEach(header => {
            header.style.paddingRight = '0px';
        });
    });
}
window.addEventListener('resize', syncTableScrollbars);

// ── Clean date formatter for SMS dispatch timestamp ────────────────────────
function formatReminderSentDate(dateStr) {
    if (!dateStr) return '';
    try {
        const parts = String(dateStr).trim().split(' ');
        const datePart = parts[0] || '';
        const timePart = parts[1] || '';
        
        const ymd = datePart.split('-');
        if (ymd.length !== 3) return dateStr;
        
        const year = ymd[0];
        const monthNum = parseInt(ymd[1], 10) - 1;
        const day = parseInt(ymd[2], 10);
        
        const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        const monthName = months[monthNum] || ymd[1];
        
        let timeFormatted = '';
        if (timePart) {
            const hms = timePart.split(':');
            let h = parseInt(hms[0], 10);
            const m = hms[1] || '00';
            const ampm = h >= 12 ? 'PM' : 'AM';
            h = h % 12 || 12;
            timeFormatted = ` • ${h}:${m} ${ampm}`;
        }
        
        return `${monthName} ${day}, ${year}${timeFormatted}`;
    } catch(e) {
        return dateStr;
    }
}

// ── Premium Modern Toast Notifier (Glassmorphism & Morphing In-Place) ───────
function showToast(opt, defaultType = 'success') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        document.body.appendChild(container);
    }

    let title = '';
    let message = '';
    let type = defaultType;

    if (typeof opt === 'string') {
        message = opt;
        type = defaultType;
    } else if (opt && typeof opt === 'object') {
        title = opt.title || '';
        message = opt.message || '';
        type = opt.type || defaultType;
    }

    if (!title) {
        if (type === 'loading') title = 'Processing Request';
        else if (type === 'error' || type === 'danger') title = 'Action Failed';
        else if (type === 'info') title = 'Notification';
        else title = 'Operation Successful';
    }

    // Automatically dismiss any orphaned loading toasts
    if (type !== 'loading') {
        container.querySelectorAll('.toast-loading').forEach(t => {
            t.classList.remove('show');
            t.classList.add('hide');
            setTimeout(() => t.remove(), 250);
        });
    }

    const toast = document.createElement('div');
    const toastClass = type === 'danger' ? 'toast-error' : `toast-${type}`;
    toast.className = `linkod-toast ${toastClass}`;

    function getIconSvg(t) {
        if (t === 'loading') {
            return `<div class="toast-spinner"></div>`;
        }
        if (t === 'error' || t === 'danger') {
            return `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>`;
        }
        if (t === 'info') {
            return `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>`;
        }
        return `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>`;
    }

    toast.innerHTML = `
        <div class="toast-icon-wrap">
            ${getIconSvg(type)}
        </div>
        <div class="toast-text-wrap">
            <span class="toast-tag">${escapeHtml(title)}</span>
            <span class="toast-message">${escapeHtml(message)}</span>
        </div>
        <button type="button" class="toast-close" aria-label="Dismiss">&times;</button>
    `;

    container.appendChild(toast);

    let dismissTimer = null;
    const dismiss = () => {
        if (dismissTimer) clearTimeout(dismissTimer);
        toast.classList.remove('show');
        toast.classList.add('hide');
        setTimeout(() => toast.remove(), 340);
    };

    toast.querySelector('.toast-close').addEventListener('click', dismiss);

    requestAnimationFrame(() => {
        toast.classList.add('show');
    });

    if (type !== 'loading') {
        dismissTimer = setTimeout(dismiss, 4200);
    }

    return {
        update: function(newOpt) {
            let nTitle = typeof newOpt === 'object' ? newOpt.title : '';
            let nMessage = typeof newOpt === 'object' ? newOpt.message : newOpt;
            let nType = typeof newOpt === 'object' ? (newOpt.type || 'success') : 'success';

            if (!nTitle) {
                if (nType === 'error') nTitle = 'Action Failed';
                else if (nType === 'info') nTitle = 'Notification';
                else nTitle = 'Operation Successful';
            }

            const nClass = nType === 'danger' ? 'toast-error' : `toast-${nType}`;
            toast.className = `linkod-toast ${nClass} show`;
            toast.querySelector('.toast-icon-wrap').innerHTML = getIconSvg(nType);
            toast.querySelector('.toast-tag').innerText = nTitle;
            toast.querySelector('.toast-message').innerText = nMessage;

            if (dismissTimer) clearTimeout(dismissTimer);
            dismissTimer = setTimeout(dismiss, 4200);
        },
        dismiss: dismiss
    };
}

let cachedQueuePatients = [];
let cachedEmrRecords = [];
let currentQueueStatusFilter = 'all';
let currentQueueSearchQuery = '';

function isSameDepartment(deptA, deptB) {
    if (!deptA || !deptB) return false;
    let a = deptA.toLowerCase().trim();
    let b = deptB.toLowerCase().trim();
    if (a === b) return true;
    if ((a.includes('general') || a.includes('general management')) && (b.includes('general') || b.includes('general management'))) return true;
    if ((a.includes('psychiatry') || a.includes('opu')) && (b.includes('psychiatry') || b.includes('opu'))) return true;
    if (a.includes('neuro') && b.includes('neuro')) return true;
    return false;
}

function filterQueueByStatus(val) {
    currentQueueStatusFilter = val;
    renderQueueTable();
}

function filterQueueBySearch(query) {
    currentQueueSearchQuery = (query || '').toLowerCase().trim();
    renderQueueTable();
}

let cachedDepartmentDoctors = [];

function normalizeDocStatus(status) {
    const s = String(status || '').toLowerCase().trim();
    if (s === 'in_clinic' || s === 'available' || s === 'in clinic' || s === 'in-clinic') return 'in_clinic';
    if (s === 'on_break' || s === 'on break') return 'on_break';
    if (s === 'emergency') return 'emergency';
    if (s === 'leave' || s === 'off_duty' || s === 'off duty' || s === 'on_leave' || s === 'on leave') return 'leave';
    return 'in_clinic';
}

function getDoctorStatusLabel(status) {
    const s = normalizeDocStatus(status);
    if (s === 'in_clinic') return 'In Clinic';
    if (s === 'on_break') return 'On Break';
    if (s === 'emergency') return 'Emergency';
    return 'Off Duty';
}

function formatDoctorStatusBadge(status) {
    const s = normalizeDocStatus(status);
    if (s === 'in_clinic') {
        return `<span class="badge-doc-status status-inclinic"><span class="doc-status-dot"></span>In Clinic</span>`;
    }
    if (s === 'on_break') {
        return `<span class="badge-doc-status status-onbreak"><span class="doc-status-dot"></span>On Break</span>`;
    }
    if (s === 'emergency') {
        return `<span class="badge-doc-status status-emergency"><span class="doc-status-dot"></span>Emergency</span>`;
    }
    return `<span class="badge-doc-status status-offduty"><span class="doc-status-dot"></span>Off Duty</span>`;
}

function getDoctorByName(docName) {
    if (!docName || docName === 'None') return null;
    const clean = docName.replace(/^Dr\.\s*/i, '').trim().toLowerCase();
    return (cachedDepartmentDoctors || []).find(d => {
        const dClean = (d.name || '').replace(/^Dr\.\s*/i, '').trim().toLowerCase();
        return dClean === clean || dClean.includes(clean) || clean.includes(dClean);
    });
}

async function loadDepartmentDoctors() {
    try {
        const res = await fetch('/sdn/api/schedule.php?_=' + Date.now()).then(r => r.json());
        if (res.success && res.data) {
            cachedDepartmentDoctors = res.data;
            if (cachedQueuePatients && cachedQueuePatients.length > 0) {
                renderQueueTable();
            }
            renderDoctorStatusModalContent();
        }
    } catch(e) {
        console.error('Error loading department doctors:', e);
    }
}

let currentModuleTab = 'online'; // 'online' | 'inquiry' | 'walkin' | 'noshow'

function switchQueueModule(tab) {
    currentModuleTab = tab;
    document.querySelectorAll('.module-tab-btn').forEach(btn => btn.classList.remove('active'));
    if (tab === 'online') document.getElementById('tabModuleOnline')?.classList.add('active');
    else if (tab === 'inquiry') document.getElementById('tabModuleInquiry')?.classList.add('active');
    else if (tab === 'walkin') document.getElementById('tabModuleWalkin')?.classList.add('active');
    else if (tab === 'noshow') document.getElementById('tabModuleNoshow')?.classList.add('active');
    renderQueueTable();
}

async function loadQueue() {
    try {
        const res = await fetch('/sdn/api/patients.php?_=' + Date.now()).then(r => r.json());
        if (res.success && res.data) {
            let deptPatients = res.data;
            if (typeof STAFF_DEPT !== 'undefined' && STAFF_DEPT) {
                const filtered = res.data.filter(p => !p.department || isSameDepartment(p.department, STAFF_DEPT));
                // Only use client filtered if it doesn't drop all records when server already scoped to staff department
                deptPatients = (filtered.length > 0 || res.data.length === 0) ? filtered : res.data;
            }
            cachedQueuePatients = deptPatients;

            // Compute and update module tab counters
            let onlineCount = 0;
            let inquiryCount = 0;
            let walkinCount = 0;
            let noshowCount = 0;
            (deptPatients || []).forEach(p => {
                const isNoshow = (p.status === 'no-show' || p.status === 'rejected');
                const isInquiry = !!(p.inquiry_only == 1 || p.inquiry_only === true || p.consultation_nature === 'General Inquiry');
                if (isNoshow) {
                    noshowCount++;
                } else if (isInquiry) {
                    inquiryCount++;
                } else if (p.unscheduled_walkin || p.appointment_type === 'walk-in') {
                    walkinCount++;
                } else {
                    onlineCount++;
                }
            });
            const elOnline = document.getElementById('countOnlineBooker');
            const elInquiry = document.getElementById('countInquiryQueue');
            const elWalkin = document.getElementById('countWalkinQueue');
            const elNoshow = document.getElementById('countNoshowRegistry');
            if (elOnline) elOnline.textContent = onlineCount;
            if (elInquiry) elInquiry.textContent = inquiryCount;
            if (elWalkin) elWalkin.textContent = walkinCount;
            if (elNoshow) elNoshow.textContent = noshowCount;

            renderQueueTable();
        } else {
            cachedQueuePatients = [];
            renderQueueTable();
        }
    } catch(e) {
        console.error('Error loading patient queue:', e);
        const tbody = document.getElementById('queue-tbody');
        if (tbody) {
            tbody.innerHTML = `<tr><td colspan="4" style="text-align:center; padding: 40px; color:#ef4444;">Error loading patient queue. Please refresh.</td></tr>`;
        }
    }
}

function renderQueueTable() {
    const tbody = document.getElementById('queue-tbody');
    if (!tbody) return;

    let list = cachedQueuePatients || [];

    // 1. Separate Module Views: Online vs Inquiry vs Walk-in vs No-Show Registry
    if (currentModuleTab === 'walkin') {
        list = list.filter(p => (p.unscheduled_walkin || p.appointment_type === 'walk-in') && p.status !== 'no-show' && p.status !== 'rejected');
    } else if (currentModuleTab === 'noshow') {
        list = list.filter(p => p.status === 'no-show' || p.status === 'rejected');
    } else if (currentModuleTab === 'inquiry') {
        list = list.filter(p => (p.inquiry_only == 1 || p.inquiry_only === true || p.consultation_nature === 'General Inquiry') && p.status !== 'no-show' && p.status !== 'rejected');
    } else {
        // 'online' - standard scheduled appointments
        list = list.filter(p => (!p.unscheduled_walkin && p.appointment_type !== 'walk-in' && !p.inquiry_only && p.consultation_nature !== 'General Inquiry') && p.status !== 'no-show' && p.status !== 'rejected');
    }

    // 2. Status Filter
    if (currentQueueStatusFilter !== 'all') {
        if (currentQueueStatusFilter === 'no-show') {
            list = list.filter(p => p.status === 'no-show' || p.status === 'rejected');
        } else if (currentQueueStatusFilter === 'for_approval') {
            list = list.filter(p => p.status === 'for_approval' || p.status === 'for_processing' || p.status === 'pending');
        } else {
            list = list.filter(p => p.status === currentQueueStatusFilter);
        }
    }

    // 3. Search Query Filter (patient name or ID)
    if (currentQueueSearchQuery) {
        list = list.filter(p => 
            ((p.full_name || '').toLowerCase().includes(currentQueueSearchQuery)) ||
            ((p.patient_id || '').toLowerCase().includes(currentQueueSearchQuery))
        );
    }

    if(list.length === 0) {
        let emptyLabel = 'No patients found';
        if (currentModuleTab === 'walkin') emptyLabel = 'No active walk-in patients in queue';
        else if (currentModuleTab === 'noshow') emptyLabel = 'No no-show records found';
        else if (currentModuleTab === 'inquiry') emptyLabel = 'No active patient inquiries found';
        else emptyLabel = 'No online appointment bookings found';

        const catLabel = currentQueueStatusFilter === 'all' ? '' : ` (${currentQueueStatusFilter.toUpperCase()})`;
        const searchNotice = currentQueueSearchQuery ? ` matching "${escapeHtml(currentQueueSearchQuery)}"` : '';
        tbody.innerHTML = `<tr><td colspan="4" style="text-align:center; padding: 40px; color:var(--muted)">${emptyLabel}${searchNotice}${catLabel}.</td></tr>`;
        syncTableScrollbars();
        return;
    }

    // Concurrency / Double-booking detection across active slots
    const slotCountMap = {};
    (cachedQueuePatients || []).forEach(p => {
        if (p.status !== 'no-show' && p.status !== 'rejected' && p.status !== 'cancelled') {
            const doc = (p.assigned_doctor || '').trim().toLowerCase();
            const date = (p.appointment_date || '').trim();
            const time = (p.scheduled_time || '').trim().toLowerCase();
            if (doc && doc !== 'none' && date && time) {
                const key = `${date}___${time}___${doc}`;
                slotCountMap[key] = (slotCountMap[key] || 0) + 1;
            }
        }
    });
    
    tbody.innerHTML = list.map(p => {
        let badge = 'badge-yellow';
        let statusLabel = (p.status || '').replace(/_/g, ' ').toUpperCase();
        if (p.status === 'approved') badge = 'badge-green';
        else if (p.status === 'arrived') badge = 'badge-teal';
        else if (p.status === 'ongoing') badge = 'badge-pink';
        else if (p.status === 'completed') badge = 'badge-blue';
        else if (p.status === 'no-show' || p.status === 'rejected') badge = 'badge-red';
        else if (p.status === 'transferred') badge = 'badge-purple';
        else if (p.status === 'for_approval' || p.status === 'for_processing' || p.status === 'pending') {
            badge = 'badge-yellow';
            statusLabel = 'FOR APPROVAL';
        }

        const isInquiry = !!(p.inquiry_only == 1 || p.inquiry_only === true || p.consultation_nature === 'General Inquiry');
        const isWalkin = !!(p.unscheduled_walkin || p.appointment_type === 'walk-in');
        let bookTypeBadge = isInquiry ? 
            '<span class="badge" style="background:#fef3c7; color:#92400e; font-weight:600; border:1px solid #fde68a;">Inquiry Only</span>' :
            (isWalkin ? 
                '<span class="badge" style="background:#f3f4f6; color:#4b5563;">Walk-In</span>' : 
                '<span class="badge" style="background:#e0f2fe; color:#0369a1;">Online Booker</span>');
        let safeName = escapeHtml(p.full_name);
        let safeId = escapeHtml(p.patient_id);
        let safeAppType = escapeHtml(p.appointment_type || (isWalkin ? 'Walk-in' : 'Scheduled'));
        let safeDoctor = escapeHtml(p.assigned_doctor || 'None');

        // Check for double-booking conflict
        const docKey = (p.assigned_doctor || '').trim().toLowerCase();
        const dateKey = (p.appointment_date || '').trim();
        const timeKey = (p.scheduled_time || '').trim().toLowerCase();
        const slotKey = `${dateKey}___${timeKey}___${docKey}`;
        const hasDoubleBooking = !isInquiry && !!(slotCountMap[slotKey] && slotCountMap[slotKey] > 1);
        const doubleBookingBadge = hasDoubleBooking ? `
            <div class="badge-double-booking" onclick="openDoubleBookingProtocolModal()" title="Concurrent booking conflict detected on this doctor & time slot. Click for protocol.">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right:3px; vertical-align:-1px;"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                <span>Double Booking</span>
            </div>
        ` : '';
        
        // ── Automated SMS Reminder Cell (Unified Professional Layout) ──
        let remStatus = p.reminder_status || 'Pending';
        let smsCellHtml = '';

        if (isInquiry) {
            smsCellHtml = `
                <div class="sms-reminder-block">
                    <div class="sms-status-line">
                        <span class="badge-reminder" style="background:#fef3c7; color:#92400e; border:1px solid #fde68a;">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                            <span>Inquiry Lead</span>
                        </span>
                        <button type="button" class="btn-sms-pill" title="Send SMS message to inquirer" onclick="promptSendSMS('${p.patient_id}')">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path><line x1="9" y1="10" x2="15" y2="10"></line><line x1="12" y1="7" x2="12" y2="13"></line></svg>
                            <span>SMS</span>
                        </button>
                    </div>
                    <div class="sms-timestamp" title="Contact: ${escapeHtml(p.contact_number || 'None')}">
                        <span>${escapeHtml(p.contact_number ? 'Tel: ' + p.contact_number : 'No phone listed')}</span>
                    </div>
                </div>
            `;
        } else if (p.status === 'completed') {
            if (remStatus === 'Sent') {
                const formattedTime = formatReminderSentDate(p.reminder_sent_at);
                smsCellHtml = `
                    <div class="sms-reminder-block">
                        <div class="sms-status-line">
                            <span class="badge-reminder badge-reminder-sent">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                <span>Sent</span>
                            </span>
                        </div>
                        <div class="sms-timestamp" title="Dispatched: ${escapeHtml(p.reminder_sent_at || '')}">
                            <span>${escapeHtml(formattedTime || 'Dispatched • Completed')}</span>
                        </div>
                    </div>
                `;
            } else {
                smsCellHtml = `
                    <div class="sms-reminder-block">
                        <div class="sms-status-line">
                            <span class="badge-reminder badge-reminder-completed">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                <span>Completed</span>
                            </span>
                        </div>
                        <div class="sms-timestamp">
                            <span>Visit concluded &bull; No reminder needed</span>
                        </div>
                    </div>
                `;
            }
        } else if (remStatus === 'Sent') {
            const formattedTime = formatReminderSentDate(p.reminder_sent_at);
            smsCellHtml = `
                <div class="sms-reminder-block">
                    <div class="sms-status-line">
                        <span class="badge-reminder badge-reminder-sent">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <span>Sent</span>
                        </span>
                        <button type="button" class="btn-sms-pill is-sent" title="Resend SMS reminder to patient" onclick="promptSendSMS('${p.patient_id}')">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
                            <span>Resend</span>
                        </button>
                    </div>
                    <div class="sms-timestamp" title="Dispatched: ${escapeHtml(p.reminder_sent_at || '')}">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        <span>${escapeHtml(formattedTime || 'Dispatched')}</span>
                    </div>
                </div>
            `;
        } else if (remStatus === 'Failed') {
            smsCellHtml = `
                <div class="sms-reminder-block">
                    <div class="sms-status-line">
                        <span class="badge-reminder badge-reminder-failed">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                            <span>Failed</span>
                        </span>
                        <button type="button" class="btn-sms-pill" title="Retry sending SMS reminder" onclick="promptSendSMS('${p.patient_id}')">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
                            <span>Retry</span>
                        </button>
                    </div>
                    <div class="sms-timestamp is-pending">
                        <span>Delivery issue &bull; Tap retry</span>
                    </div>
                </div>
            `;
        } else {
            // Pending
            smsCellHtml = `
                <div class="sms-reminder-block">
                    <div class="sms-status-line">
                        <span class="badge-reminder badge-reminder-pending">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>
                            <span>Pending</span>
                        </span>
                        <button type="button" class="btn-sms-pill" title="Manually trigger SMS reminder now" onclick="promptSendSMS('${p.patient_id}')">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path><line x1="9" y1="10" x2="15" y2="10"></line><line x1="12" y1="7" x2="12" y2="13"></line></svg>
                            <span>Send SMS</span>
                        </button>
                    </div>
                    <div class="sms-timestamp is-pending">
                        <span>Auto-reminds 2–3d prior</span>
                    </div>
                </div>
            `;
        }

        // Doctor info and status
        let docObj = getDoctorByName(p.assigned_doctor);
        let curDocStatus = normalizeDocStatus(docObj?.status || p.doctor_status);
        let docId = docObj?.id || p.doctor_id || null;
        let hasDoc = p.assigned_doctor && p.assigned_doctor !== 'None' && p.assigned_doctor.trim() !== '';

        // ── Column 4: Workflow Actions ("No Show" is completely removed for walk-ins) ──
        const noShowBtn = isWalkin ? '' : `
            <button type="button" class="btn-table-action btn-table-noshow" onclick="promptPatientVerification('${p.patient_id}', 'no-show', '${escapeHtml(p.full_name)}')">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                <span>${isInquiry ? 'Invalid' : 'No-Show'}</span>
            </button>
        `;

        let actions = '';
        if (isInquiry) {
            if (p.status === 'for_approval' || p.status === 'for_processing' || p.status === 'pending') {
                actions = `
                    <div class="queue-actions-wrap">
                        <button type="button" class="btn-table-action btn-table-details" onclick="openPatientDetailsModal('${p.patient_id}')">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            <span>Details</span>
                        </button>
                        <button type="button" class="btn-table-action btn-table-accept" title="Accept & review inquiry" onclick="promptPatientVerification('${p.patient_id}', 'approve', '${escapeHtml(p.full_name)}')">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <span>Review</span>
                        </button>
                        <button type="button" class="btn-table-action btn-table-completed" title="Mark inquiry as resolved / completed" onclick="promptResolveInquiry('${p.patient_id}', '${escapeHtml(p.full_name)}')">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <span>Resolve</span>
                        </button>
                        <button type="button" class="btn-table-action btn-table-transfer" onclick="openReferralModal('${p.patient_id}')">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="17 1 21 5 17 9"></polyline><path d="M3 11V9a4 4 0 0 1 4-4h14"></path><polyline points="7 23 3 19 7 15"></polyline><path d="M21 13v2a4 4 0 0 1-4 4H3"></path></svg>
                            <span>Transfer</span>
                        </button>
                        ${noShowBtn}
                    </div>
                `;
            } else if (p.status === 'approved') {
                actions = `
                    <div class="queue-actions-wrap">
                        <button type="button" class="btn-table-action btn-table-details" onclick="openPatientDetailsModal('${p.patient_id}')">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            <span>Details</span>
                        </button>
                        <button type="button" class="btn-table-action btn-table-completed" title="Mark inquiry as resolved / completed" onclick="promptResolveInquiry('${p.patient_id}', '${escapeHtml(p.full_name)}')">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <span>Resolve</span>
                        </button>
                        <button type="button" class="btn-table-action btn-table-transfer" onclick="openReferralModal('${p.patient_id}')">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="17 1 21 5 17 9"></polyline><path d="M3 11V9a4 4 0 0 1 4-4h14"></path><polyline points="7 23 3 19 7 15"></polyline><path d="M21 13v2a4 4 0 0 1-4 4H3"></path></svg>
                            <span>Transfer</span>
                        </button>
                        ${noShowBtn}
                    </div>
                `;
            } else {
                actions = `
                    <div class="queue-actions-wrap">
                        <button type="button" class="btn-table-action btn-table-details" onclick="openPatientDetailsModal('${p.patient_id}')">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            <span>Details</span>
                        </button>
                    </div>
                `;
            }
        } else if (p.status === 'for_approval' || p.status === 'for_processing' || p.status === 'pending') {
            actions = `
                <div class="queue-actions-wrap">
                    <button type="button" class="btn-table-action btn-table-details" onclick="openPatientDetailsModal('${p.patient_id}')">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        <span>Details</span>
                    </button>
                    <button type="button" class="btn-table-action btn-table-accept" title="Accept & approve patient booking" onclick="promptPatientVerification('${p.patient_id}', 'approve', '${escapeHtml(p.full_name)}')">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        <span>Accept</span>
                    </button>
                    <button type="button" class="btn-table-action btn-table-transfer" onclick="openReferralModal('${p.patient_id}')">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="17 1 21 5 17 9"></polyline><path d="M3 11V9a4 4 0 0 1 4-4h14"></path><polyline points="7 23 3 19 7 15"></polyline><path d="M21 13v2a4 4 0 0 1-4 4H3"></path></svg>
                        <span>Transfer</span>
                    </button>
                    ${noShowBtn}
                </div>
            `;
        } else if (p.status === 'approved') {
            actions = `
                <div class="queue-actions-wrap">
                    <button type="button" class="btn-table-action btn-table-details" onclick="openPatientDetailsModal('${p.patient_id}')">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        <span>Details</span>
                    </button>
                    <button type="button" class="btn-table-action btn-table-arrived" title="Check in patient at facility reception / triage" onclick="promptPatientVerification('${p.patient_id}', 'arrived', '${escapeHtml(p.full_name)}')">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><polyline points="17 11 19 13 23 9"></polyline></svg>
                        <span>Check In</span>
                    </button>
                    <button type="button" class="btn-table-action btn-table-transfer" onclick="openReferralModal('${p.patient_id}')">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="17 1 21 5 17 9"></polyline><path d="M3 11V9a4 4 0 0 1 4-4h14"></path><polyline points="7 23 3 19 7 15"></polyline><path d="M21 13v2a4 4 0 0 1-4 4H3"></path></svg>
                        <span>Transfer</span>
                    </button>
                    ${noShowBtn}
                </div>
            `;
        } else if (p.status === 'arrived') {
            actions = `
                <div class="queue-actions-wrap">
                    <button type="button" class="btn-table-action btn-table-details" onclick="openPatientDetailsModal('${p.patient_id}')">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        <span>Details</span>
                    </button>
                    <button type="button" class="btn-table-action btn-table-ongoing" title="Start clinical consultation with doctor" onclick="promptPatientVerification('${p.patient_id}', 'ongoing', '${escapeHtml(p.full_name)}')">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                        <span>Start Consult</span>
                    </button>
                    <button type="button" class="btn-table-action btn-table-transfer" onclick="openReferralModal('${p.patient_id}')">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="17 1 21 5 17 9"></polyline><path d="M3 11V9a4 4 0 0 1 4-4h14"></path><polyline points="7 23 3 19 7 15"></polyline><path d="M21 13v2a4 4 0 0 1-4 4H3"></path></svg>
                        <span>Transfer</span>
                    </button>
                    ${noShowBtn}
                </div>
            `;
        } else if (p.status === 'ongoing') {
            actions = `
                <div class="queue-actions-wrap">
                    <button type="button" class="btn-table-action btn-table-details" onclick="openPatientDetailsModal('${p.patient_id}')">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        <span>Details</span>
                    </button>
                    <button type="button" class="btn-table-action btn-table-completed" title="Doctor sign-off and finalize consultation" onclick="promptDoctorSignOff('${p.patient_id}', '${escapeHtml(p.full_name)}')">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                        <span>Sign Off & Complete</span>
                    </button>
                    <button type="button" class="btn-table-action btn-table-transfer" onclick="openReferralModal('${p.patient_id}')">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="17 1 21 5 17 9"></polyline><path d="M3 11V9a4 4 0 0 1 4-4h14"></path><polyline points="7 23 3 19 7 15"></polyline><path d="M21 13v2a4 4 0 0 1-4 4H3"></path></svg>
                        <span>Transfer</span>
                    </button>
                </div>
            `;
        } else {
            // completed, no-show, transferred, rejected
            actions = `
                <div class="queue-actions-wrap">
                    <button type="button" class="btn-table-action btn-table-details" onclick="openPatientDetailsModal('${p.patient_id}')">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        <span>Details</span>
                    </button>
                </div>
            `;
        }

        const pStatus = String(p.status || '').toLowerCase().replace(/_/g, '-');
        const isDocStatusLocked = (pStatus === 'no-show' || pStatus === 'completed' || pStatus === 'rejected' || pStatus === 'cancelled');

        return `
        <tr>
            <td>
                <strong style="color:var(--deep-green); font-size:1.02rem;">${safeName}</strong> ${bookTypeBadge} ${doubleBookingBadge}<br>
                <small style="color:var(--muted)">ID: ${safeId} &bull; ${isInquiry ? 'General Inquiry' : safeAppType}</small>
            </td>
            <td>
                <span class="badge ${badge}">${escapeHtml(statusLabel)}</span>
                ${isInquiry ? `
                    <div class="doc-assigned-row" style="margin-top:6px;">
                        <div style="font-size:0.83rem; color:var(--text-soft); font-weight:600;">Mode: <span style="color:var(--deep-green);">${escapeHtml(p.mode_of_inquiry || 'Online Portal')}</span></div>
                        <small style="color:var(--muted); font-size:0.75rem;">Inquiry Only (No Clinic Slot)</small>
                    </div>
                ` : `
                    <div class="doc-assigned-row" style="margin-top:6px;">
                        <div style="font-size:0.83rem; color:var(--text-soft); font-weight:600;">Assigned: <span style="color:${isDocStatusLocked ? '#64748b' : 'var(--deep-green)'};">${safeDoctor}</span></div>
                        ${hasDoc ? (isDocStatusLocked ? `
                            <div class="row-doc-status-control doc-status-pill-${curDocStatus} is-locked" title="Doctor status is locked for ${escapeHtml(statusLabel)} patient" onclick="event.stopPropagation()">
                                <span class="row-doc-status-static">● ${getDoctorStatusLabel(curDocStatus)}</span>
                            </div>
                        ` : `
                            <div class="row-doc-status-control doc-status-pill-${curDocStatus}" title="Change status for ${safeDoctor}" onclick="event.stopPropagation()">
                                <select class="row-doc-status-select doc-select-${curDocStatus}" data-doc="${safeDoctor}" onchange="updateSingleDoctorStatus(this.dataset.doc, this.value, ${docId || 'null'})">
                                    <option value="in_clinic" ${curDocStatus === 'in_clinic' ? 'selected' : ''}>● In Clinic</option>
                                    <option value="on_break" ${curDocStatus === 'on_break' ? 'selected' : ''}>● On Break</option>
                                    <option value="emergency" ${curDocStatus === 'emergency' ? 'selected' : ''}>● Emergency</option>
                                    <option value="leave" ${curDocStatus === 'leave' ? 'selected' : ''}>● Off Duty</option>
                                </select>
                            </div>
                        `) : ''}
                    </div>
                `}
            </td>
            <td>
                ${smsCellHtml}
            </td>
            <td>
                ${actions}
            </td>
        </tr>
        `;
    }).join('');

    syncTableScrollbars();
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
        const isInquiry = !!(p.inquiry_only == 1 || p.inquiry_only === true || p.consultation_nature === 'General Inquiry');

        const modalTitle = document.querySelector('#patientDetailsModal .modal-header h2');
        const modalSub = document.querySelector('#patientDetailsModal .modal-header p');
        if (modalTitle && modalSub) {
            if (isInquiry) {
                modalTitle.innerText = 'Patient General Inquiry Details';
                modalSub.innerText = 'Review patient inquiry questions, contact information, and verification status.';
            } else if (p.status === 'completed') {
                modalTitle.innerText = 'Patient Registration & Consultation Details';
                modalSub.innerText = 'Review patient profile, clinical diagnosis, treatment plan, and consultation history.';
            } else {
                modalTitle.innerText = 'Patient Registration Verification';
                modalSub.innerText = 'Review full registration information and verify patient authenticity via phone call.';
            }
        }

        if (isInquiry) {
            body.innerHTML = `
                ${showVerificationProtocol ? `
                <div class="verification-callout" style="background:#fffbeb; border-color:#fde68a;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    <div>
                        <strong>Inquiry Protocol:</strong> Contact inquirer at <strong>${escapeHtml(phone)}</strong> to address their inquiry question. Once answered or scheduled, click <em>Resolve Inquiry</em>. If invalid or spam, click <em>Mark as Invalid</em>.
                    </div>
                </div>` : ''}

                <div class="patient-verify-grid">
                    <div class="patient-verify-card">
                        <h3>
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--dark-green);"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            <span>Personal Information</span>
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
                            <span>${escapeHtml(p.gender || 'N/A')} &bull; ${escapeHtml(p.age || 'N/A')} yrs old</span>
                        </div>
                        <div class="verify-item">
                            <label>Complete Address</label>
                            <span>${escapeHtml(p.address || 'None provided')}</span>
                        </div>
                    </div>

                    <div class="patient-verify-card">
                        <h3>
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--dark-green);"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                            <span>Inquiry & Service Details</span>
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
                        <span>Inquiry Concern & Detailed Message</span>
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
                    actions.innerHTML = `
                        <div class="patient-modal-status-col">
                            <span class="badge ${statusBadge}">Status: ${escapeHtml((p.status || '').replace(/_/g, ' ').toUpperCase())}</span>
                        </div>
                        <div class="patient-modal-btn-group">
                            <button type="button" class="btn btn-outline" onclick="closePatientDetailsModal()">Close</button>
                        </div>
                    `;
                }
            }

        } else {
            // Standard clinical patient registration details
            body.innerHTML = `
                ${showVerificationProtocol ? `
                <div class="verification-callout">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                    <div>
                        <strong>Verification Protocol:</strong> Call patient at <strong>${escapeHtml(phone)}</strong> to confirm appointment validity. If unreachable, fake, or trolling ("tripping"), click <em>Mark as No-Show</em>.
                    </div>
                </div>` : ''}

                <div class="patient-verify-grid">
                    <div class="patient-verify-card">
                        <h3>
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--dark-green);"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            <span>Personal Information</span>
                        </h3>
                        <div class="verify-item">
                            <label>Hospital Record Number (HRN)</label>
                            <span><strong>${escapeHtml(p.hrn || 'HRN Pending')}</strong></span>
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
                        <div class="verify-item">
                            <label>Appointment Reminder Status</label>
                            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-top:3px;">
                                <span class="badge-reminder ${p.reminder_status === 'Sent' ? 'badge-reminder-sent' : (p.reminder_status === 'Failed' ? 'badge-reminder-failed' : 'badge-reminder-pending')}">
                                    ${p.reminder_status === 'Sent' ? '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg> Sent' : (p.reminder_status === 'Failed' ? '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg> Failed' : '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg> Pending')}
                                </span>
                                ${p.reminder_sent_at ? `<small style="color:#64748b;">(Dispatched: ${escapeHtml(p.reminder_sent_at)})</small>` : ''}
                                <button type="button" class="btn-table-action btn-table-sms ${p.reminder_status === 'Sent' ? 'is-sent' : ''}" style="padding:4px 10px; font-size:0.8rem;" onclick="promptSendSMS('${p.patient_id}')">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path><line x1="9" y1="10" x2="15" y2="10"></line><line x1="12" y1="7" x2="12" y2="13"></line></svg>
                                    <span>${p.reminder_status === 'Sent' ? 'Resend SMS' : 'Send Manual SMS'}</span>
                                </button>
                            </div>
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
        }

    } catch(err) {
        console.error(err);
        body.innerHTML = '<p style="text-align:center; color:#ef4444;">Error fetching patient details.</p>';
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
                if (typeof showActionToast === 'function') {
                    showActionToast('Inquiry resolved and completed successfully!');
                }
                loadQueue();
            } else {
                alert(res.message || 'Error resolving inquiry.');
                btn.disabled = false;
                btn.textContent = 'Confirm Resolved';
            }
        } catch(e) {
            console.error(e);
            alert('Server error while resolving inquiry.');
            btn.disabled = false;
            btn.textContent = 'Confirm Resolved';
        }
    };
}

function closePatientDetailsModal() {
    const modal = document.getElementById('patientDetailsModal');
    if (modal) modal.classList.remove('show');
}

function showStaffConfirmDialog({
    title,
    tag,
    message,
    type = 'accept', // 'accept' | 'noshow' | 'completed'
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
    } else if (type === 'sms') {
        defaultTag = 'SMS APPOINTMENT REMINDER';
        defaultConfirmText = 'Send SMS Reminder';
        iconSvg = `<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path><line x1="9" y1="10" x2="15" y2="10"></line><line x1="12" y1="7" x2="12" y2="13"></line></svg>`;
        btnIconSvg = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>`;
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
                <div class="staff-confirm-patient-sub">${escapeHtml(patientSub || (patientId ? `ID: ${patientId}` : 'Queue Record'))}</div>
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
    const patient = (cachedQueuePatients || []).find(p => String(p.patient_id) === String(patient_id));
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
    } else { // 'approve' or 'accept'
        showStaffConfirmDialog({
            title: 'Accept & Admit Patient?',
            tag: 'PATIENT ADMISSION',
            message: `Confirm that patient ${pName} has been verified and contacted. They will be marked as Approved and admitted into the active consultation queue.`,
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
    const patient = (cachedQueuePatients || []).find(p => String(p.patient_id) === String(patient_id));
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
        <div class="staff-confirm-box" style="max-width:540px;" role="dialog" aria-modal="true" aria-labelledby="signoffTitle">
            <div class="staff-confirm-banner completed"></div>
            <button type="button" class="staff-confirm-close" id="signoffCloseBtn" aria-label="Close dialog">&times;</button>
            <div class="staff-confirm-body">
                <div class="staff-confirm-header-row">
                    <div class="staff-confirm-icon-circle completed">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    </div>
                    <div class="staff-confirm-heading">
                        <span class="staff-confirm-tag completed">OFFICIAL DOCTOR SIGN-OFF</span>
                        <h3 class="staff-confirm-title" id="signoffTitle">Complete Consultation Visit</h3>
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
                    <label for="signoffDiagnosis">Clinical Diagnosis *</label>
                    <input type="text" id="signoffDiagnosis" placeholder="e.g. Acute upper respiratory tract infection, resolved" value="${escapeHtml(patient?.medical_history_diagnosis || '')}">
                </div>
                <div class="signoff-field">
                    <label for="signoffTreatment">Treatment Plan & Recommendations</label>
                    <textarea id="signoffTreatment" rows="2" placeholder="e.g. Oral hydration, voice rest, warm saline gargle...">${escapeHtml(patient?.medical_history_treatment || '')}</textarea>
                </div>
                <div class="signoff-field">
                    <label for="signoffPrescription">Prescriptions / Rx</label>
                    <textarea id="signoffPrescription" rows="2" placeholder="e.g. Paracetamol 500mg TID PRN for fever...">${escapeHtml(patient?.medical_history_prescription || '')}</textarea>
                </div>
            </div>
            <div class="staff-confirm-actions">
                <button type="button" class="staff-confirm-btn-cancel" id="signoffCancelBtn">Cancel</button>
                <button type="button" class="staff-confirm-btn-submit completed" id="signoffSubmitBtn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    <span>Sign Off & Complete Visit</span>
                </button>
            </div>
        </div>
    `;

    const closeDialog = () => {
        overlay.classList.remove('show');
    };

    overlay.querySelector('#signoffCancelBtn').addEventListener('click', closeDialog);
    overlay.querySelector('#signoffCloseBtn').addEventListener('click', closeDialog);

    overlay.querySelector('#signoffSubmitBtn').addEventListener('click', () => {
        const diagInput = overlay.querySelector('#signoffDiagnosis');
        const diag = (diagInput ? diagInput.value : '').trim();
        const treatment = overlay.querySelector('#signoffTreatment').value.trim();
        const rx = overlay.querySelector('#signoffPrescription').value.trim();

        if (!diag) {
            if (diagInput) {
                diagInput.classList.add('is-invalid');
                diagInput.focus();
            }
            return showToast('Clinical Diagnosis is required for official doctor sign-off.', 'error');
        }

        closeDialog();
        executeVerificationStatus(patient_id, 'completed', {
            diagnosis: diag,
            treatment_plan: treatment,
            prescription: rx
        });
    });

    requestAnimationFrame(() => {
        overlay.classList.add('show');
        const diagInput = overlay.querySelector('#signoffDiagnosis');
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
            loadQueue();
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

function promptSendSMS(patient_id) {
    const patient = (cachedQueuePatients || []).find(p => String(p.patient_id) === String(patient_id));
    if (!patient) {
        showToast('Patient record not found in queue', 'error');
        return;
    }

    const pName = patient.full_name || `Patient #${patient_id}`;
    const pPhone = patient.contact_number || '';
    const pDate = patient.appointment_date || 'Upcoming Date';
    const pTime = patient.scheduled_time ? ` at ${patient.scheduled_time}` : '';
    const isSent = (patient.reminder_status === 'Sent');

    if (!pPhone || pPhone === 'None') {
        showToast(`Cannot send SMS: ${pName} does not have a registered contact number.`, 'error');
        return;
    }

    const title = isSent ? 'Resend SMS Appointment Reminder?' : 'Send Manual SMS Reminder?';
    const message = isSent
        ? `A reminder was previously dispatched on ${patient.reminder_sent_at || 'record'}. Would you like to resend the SMS appointment reminder to ${pName} (${pPhone}) for ${pDate}${pTime}?`
        : `Send an official MMWGH Linkod SMS appointment reminder to ${pName} (${pPhone}) for scheduled consultation on ${pDate}${pTime}?`;

    showStaffConfirmDialog({
        title: title,
        tag: 'SMS APPOINTMENT NOTIFICATION',
        message: message,
        type: 'sms',
        patientName: pName,
        patientId: patient_id,
        patientSub: `Mobile: ${pPhone} • Scheduled: ${pDate}${pTime}`,
        confirmText: isSent ? 'Resend SMS' : 'Send SMS Now',
        onConfirm: () => executeSendSMS(patient_id, pName, pPhone)
    });
}

async function executeSendSMS(patient_id, patientName, phone) {
    const toast = showToast({
        title: 'SMS Service',
        message: `Dispatching appointment reminder to ${patientName || patient_id}...`,
        type: 'loading'
    });

    try {
        const res = await fetch('/sdn/api/patients.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'send_sms',
                patient_id: patient_id
            })
        }).then(r => r.json());

        if (res.success) {
            toast.update({
                title: 'Reminder Dispatched',
                message: res.message || `SMS reminder sent successfully to ${patientName}!`,
                type: 'success'
            });
            await loadQueue();
            const modal = document.getElementById('patientDetailsModal');
            if (modal && modal.classList.contains('show')) {
                openPatientDetailsModal(patient_id);
            }
        } else {
            toast.update({
                title: 'Dispatch Failed',
                message: res.message || 'Failed to send SMS reminder.',
                type: 'error'
            });
        }
    } catch(err) {
        console.error(err);
        toast.update({
            title: 'Connection Error',
            message: 'Error communicating with SMS service.',
            type: 'error'
        });
    }
}

function updateStatus(patient_id, action) {
    promptPatientVerification(patient_id, action);
}

// ── Phase 3: Field-Specific Validation Helpers ──────────────────────────────
function clearFieldErrors(fieldIds) {
    (fieldIds || []).forEach(id => {
        const el = document.getElementById(id);
        if (el) el.classList.remove('is-invalid');
        const errEl = document.getElementById('err-' + id);
        if (errEl) {
            errEl.textContent = '';
            errEl.style.display = 'none';
        }
    });
}

function flagFieldError(id, msg) {
    const el = document.getElementById(id);
    if (el) {
        el.classList.add('is-invalid');
        el.focus();
        el.addEventListener('input', function handler() {
            el.classList.remove('is-invalid');
            const errEl = document.getElementById('err-' + id);
            if (errEl) {
                errEl.textContent = '';
                errEl.style.display = 'none';
            }
            el.removeEventListener('input', handler);
        }, { once: true });
    }
    const errEl = document.getElementById('err-' + id);
    if (errEl) {
        errEl.textContent = msg;
        errEl.style.display = 'block';
    }
    showToast(msg, 'error');
}

// ── Phase 1: Centralized Returning Patient Verification for Walk-In ───────────
async function searchReturningWalkinPatient() {
    const query = (document.getElementById('walkinLookupQuery')?.value || '').trim();
    if (!query) {
        return showToast('Please enter Patient ID, HRN, or Name to verify.', 'error');
    }

    const btn = document.getElementById('btnWalkinLookup');
    if (btn) btn.disabled = true;

    try {
        const res = await fetch(`/sdn/api/patient_verify.php?query=${encodeURIComponent(query)}&_=${Date.now()}`).then(r => r.json());
        if (res.success && res.data && res.data.length > 0) {
            const patient = res.data[0];
            const matchCard = document.getElementById('walkinMatchCard');
            if (matchCard) matchCard.style.display = 'flex';
            const nameEl = document.getElementById('walkinMatchName');
            if (nameEl) nameEl.textContent = patient.full_name;
            const idEl = document.getElementById('walkinMatchId');
            if (idEl) idEl.textContent = patient.patient_id || patient.hrn || 'Returning Record';
            const metaEl = document.getElementById('walkinMatchMeta');
            if (metaEl) metaEl.textContent = `DOB: ${patient.birthdate || 'N/A'} • Gender: ${patient.gender || 'N/A'} • Dept: ${patient.department || 'N/A'}`;

            document.getElementById('walkinName').value = patient.full_name || '';
            if (patient.birthdate) document.getElementById('walkinDob').value = patient.birthdate;
            if (patient.gender) document.getElementById('walkinGender').value = patient.gender;
            if (patient.contact_number && patient.contact_number !== 'None' && patient.contact_number !== 'Walk-in') {
                document.getElementById('walkinPhone').value = patient.contact_number;
            }
            if (patient.address) document.getElementById('walkinAddress').value = patient.address;

            document.getElementById('walkinPatientId').value = patient.patient_id || '';
            document.getElementById('walkinIsReturning').value = '1';
            document.getElementById('walkinHrn').value = patient.hrn || '';
            const typeTag = document.getElementById('walkinRecordTypeTag');
            if (typeTag) typeTag.innerHTML = `● Returning Patient (Existing Profile Linked: <strong style="color:#059669;">${escapeHtml(patient.patient_id || patient.hrn)}</strong>)`;

            clearFieldErrors(['walkinName', 'walkinDob', 'walkinGender', 'walkinPhone', 'walkinAddress', 'walkinComplaints']);
            showToast(`Returning record matched for ${patient.full_name}! Demographics auto-filled.`);
        } else {
            showToast('No matching patient profile found. You can proceed to register as a new walk-in.', 'error');
        }
    } catch (e) {
        console.error('Error looking up returning patient:', e);
        showToast('Error looking up returning patient.', 'error');
    } finally {
        if (btn) btn.disabled = false;
    }
}

function clearWalkinReturningMatch() {
    const card = document.getElementById('walkinMatchCard');
    if (card) card.style.display = 'none';
    const queryInput = document.getElementById('walkinLookupQuery');
    if (queryInput) queryInput.value = '';

    document.getElementById('walkinPatientId').value = '';
    document.getElementById('walkinIsReturning').value = '0';
    document.getElementById('walkinHrn').value = '';
    const typeTag = document.getElementById('walkinRecordTypeTag');
    if (typeTag) typeTag.textContent = '● New Patient Registration';
}

function openWalkinModal() {
    clearFieldErrors(['walkinName', 'walkinDob', 'walkinGender', 'walkinPhone', 'walkinAddress', 'walkinComplaints']);
    document.getElementById('walkinModal').classList.add('show');
}

async function submitWalkin() {
    clearFieldErrors(['walkinName', 'walkinDob', 'walkinGender', 'walkinPhone', 'walkinAddress', 'walkinComplaints']);

    const full_name = (document.getElementById('walkinName')?.value || '').trim();
    const complaints = (document.getElementById('walkinComplaints')?.value || '').trim();
    const dob = document.getElementById('walkinDob')?.value || '';
    const gender = document.getElementById('walkinGender')?.value || '';
    const phone = (document.getElementById('walkinPhone')?.value || '').trim();
    const address = (document.getElementById('walkinAddress')?.value || '').trim();
    const isReturning = document.getElementById('walkinIsReturning')?.value === '1';
    const existingPatientId = document.getElementById('walkinPatientId')?.value || '';
    const hrn = document.getElementById('walkinHrn')?.value || '';

    if (!full_name) {
        return flagFieldError('walkinName', 'Please provide the patient Full Name.');
    }
    if (!complaints) {
        return flagFieldError('walkinComplaints', 'Please describe the Chief Complaints.');
    }

    try {
        const payload = {
            department: STAFF_DEPT.toLowerCase().replace('opu ', ''),
            full_name,
            chief_complaints: complaints,
            appointment_type: 'walk-in',
            appointment_date: new Date().toISOString().split('T')[0],
            unscheduled_walkin: true,
            birthdate: dob || null,
            gender: gender || null,
            contact_number: phone || 'Walk-in',
            address: address || null,
            patient_type: isReturning ? 'returning' : 'first-time',
            is_returning: isReturning,
            existing_patient_id: existingPatientId || null,
            hrn: hrn || null
        };

        const res = await fetch('/sdn/api/registration.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        }).then(r => r.json());

        if (res.success) {
            // Auto approve walk-in
            await fetch('/sdn/api/patients.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ action: 'approve', patient_id: res.patient_id })
            });
            document.getElementById('walkinModal').classList.remove('show');
            document.getElementById('walkinName').value = '';
            document.getElementById('walkinComplaints').value = '';
            clearWalkinReturningMatch();
            loadQueue();
            loadEMR();
            showToast(isReturning ? `Returning check-up added to existing profile (${existingPatientId || 'Record Linked'})!` : 'Walk-in patient registered and admitted to queue!');
        } else {
            showToast(res.message || 'Registration failed', 'error');
        }
    } catch(e) {
        console.error(e);
        showToast('Error registering walk-in patient', 'error');
    }
}

// ── Phase 1: Self-Department Transfer Restriction ────────────────────────────
function openReferralModal(id) {
    const patient = (cachedQueuePatients || []).find(p => String(p.patient_id) === String(id));
    document.getElementById('referralPatientId').value = id;

    const patientDept = patient ? (patient.department || STAFF_DEPT) : STAFF_DEPT;
    const cleanPatientDept = patientDept.toLowerCase().replace('opu ', '').trim();

    const noticeEl = document.getElementById('referralCurrentDeptNotice');
    if (noticeEl) {
        noticeEl.innerHTML = `Patient: <strong>${escapeHtml(patient?.full_name || id)}</strong><br>Current Department: <strong>${escapeHtml(patientDept)}</strong> <span style="font-size:0.78rem; color:#dc2626;">(Transferring to the same department is restricted to prevent logic loops)</span>`;
    }

    const select = document.getElementById('referralDept');
    if (select) {
        Array.from(select.options).forEach(opt => {
            const cleanOpt = opt.value.toLowerCase().replace('opu ', '').trim();
            if (cleanOpt === cleanPatientDept) {
                opt.disabled = true;
                opt.textContent = `${opt.value} (Current Department - Restricted)`;
            } else {
                opt.disabled = false;
                opt.textContent = opt.value.replace(' (Current Department - Restricted)', '');
            }
        });

        const firstValid = Array.from(select.options).find(opt => !opt.disabled);
        if (firstValid) select.value = firstValid.value;
    }

    clearFieldErrors(['referralDept']);
    document.getElementById('referralModal').classList.add('show');
}

async function submitReferral() {
    clearFieldErrors(['referralDept']);
    const patient_id = document.getElementById('referralPatientId').value;
    const department = document.getElementById('referralDept').value;
    const patient = (cachedQueuePatients || []).find(p => String(p.patient_id) === String(patient_id));
    const currentDept = patient ? (patient.department || STAFF_DEPT) : STAFF_DEPT;

    if (department.toLowerCase().replace('opu ', '').trim() === currentDept.toLowerCase().replace('opu ', '').trim()) {
        return flagFieldError('referralDept', 'Cannot transfer patient to their current department.');
    }

    try {
        const res = await fetch('/sdn/api/patients.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'referral', patient_id, department})
        }).then(r => r.json());

        if (res.success) {
            document.getElementById('referralModal').classList.remove('show');
            loadQueue();
            showToast(`Patient successfully transferred to ${department}.`);
        } else {
            showToast(res.message || 'Transfer failed.', 'error');
        }
    } catch(e) {
        console.error(e);
        showToast('Error transferring patient record.', 'error');
    }
}

// ── Staff Profile & Image Management (In-Page View) ──────────────────────────
let pendingAvatarBase64 = null;
let shouldRemoveAvatar = false;
let currentProfileData = null;

function openStaffProfilePage() {
    const profileLink = document.querySelector('.sidebar-nav .nav-link[data-view="profile"]');
    if (profileLink) {
        profileLink.click();
    } else {
        document.querySelectorAll('.sidebar-nav .nav-link').forEach(l => l.classList.remove('active'));
        document.querySelectorAll('.view-section').forEach(v => v.classList.remove('active'));
        document.getElementById('view-profile')?.classList.add('active');
        loadStaffProfileView();
    }
}

function openStaffProfileModal() {
    openStaffProfilePage();
}

function computeInitials(name) {
    if (!name) return 'ST';
    const parts = name.trim().split(/\s+/);
    if (parts.length >= 2) {
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
}

function updateAvatarDisplays(imageUrl, fullName) {
    const initials = computeInitials(fullName);

    // Update top header badge avatar circles
    document.querySelectorAll('.staff-avatar-sync').forEach(circle => {
        if (imageUrl) {
            circle.innerHTML = `<img src="${escapeHtml(imageUrl)}" alt="Avatar" class="avatar-img-elem">`;
        } else {
            circle.innerHTML = `<span class="avatar-initials-elem">${escapeHtml(initials)}</span>`;
        }
    });

    // Update large profile avatar
    const largeAvatar = document.getElementById('profileAvatarLarge');
    if (largeAvatar) {
        if (imageUrl) {
            largeAvatar.innerHTML = `<img src="${escapeHtml(imageUrl)}" alt="Avatar" id="profileLargeImg" class="profile-large-img">`;
        } else {
            largeAvatar.innerHTML = `<span id="profileLargeInitials" class="profile-large-initials">${escapeHtml(initials)}</span>`;
        }
    }

    // Toggle remove button visibility
    const remBtn = document.getElementById('btnRemoveAvatar');
    if (remBtn) {
        remBtn.style.display = imageUrl ? 'inline-flex' : 'none';
    }
}

function triggerProfileFormSubmit() {
    const form = document.getElementById('staffProfilePageForm');
    if (form) {
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
        }
    }
}

async function loadStaffProfileView() {
    clearFieldErrors(['profilePageFullName', 'profilePageNewPass', 'profilePageConfirmPass']);
    const alertBox = document.getElementById('profilePageAlert');
    if (alertBox) alertBox.style.display = 'none';

    try {
        const res = await fetch('/sdn/api/staff.php?action=me&_=' + Date.now()).then(r => r.json());
        if (res.success && res.data) {
            currentProfileData = res.data;
            const fullName = res.data.full_name || '';
            const username = res.data.username || '';
            const dept = res.data.department || STAFF_DEPT;
            const profileImg = res.data.profile_image || null;

            const nameInput = document.getElementById('profilePageFullName');
            if (nameInput) {
                nameInput.value = fullName;
                nameInput.defaultValue = fullName;
            }

            const userInput = document.getElementById('profilePageUsername');
            if (userInput) userInput.value = username;

            const deptInput = document.getElementById('profilePageDept');
            if (deptInput) deptInput.value = dept;

            const cardName = document.getElementById('profileCardName');
            if (cardName) cardName.textContent = fullName || 'Medical Staff';

            const cardUser = document.getElementById('profileCardUsername');
            if (cardUser) cardUser.textContent = username || '—';

            const heroDept = document.getElementById('profileHeroDept');
            if (heroDept) heroDept.textContent = dept;

            // Clear password inputs
            const passInput = document.getElementById('profilePageNewPass');
            if (passInput) passInput.value = '';
            const confirmPass = document.getElementById('profilePageConfirmPass');
            if (confirmPass) confirmPass.value = '';

            // Reset file input
            const fileInput = document.getElementById('staffAvatarInput');
            if (fileInput) fileInput.value = '';

            updateAvatarDisplays(profileImg, fullName);
        }
    } catch (e) {
        console.error('Failed to load profile details:', e);
    }
}

function compressAvatarFile(file, maxDimension = 480, quality = 0.88) {
    return new Promise((resolve) => {
        if (!file) return resolve(null);
        const img = new Image();
        const objUrl = URL.createObjectURL(file);
        img.onload = () => {
            URL.revokeObjectURL(objUrl);
            let { width, height } = img;
            if (width > height) {
                if (width > maxDimension) {
                    height = Math.round((height * maxDimension) / width);
                    width = maxDimension;
                }
            } else {
                if (height > maxDimension) {
                    width = Math.round((width * maxDimension) / height);
                    height = maxDimension;
                }
            }
            const canvas = document.createElement('canvas');
            canvas.width = Math.max(1, width);
            canvas.height = Math.max(1, height);
            const ctx = canvas.getContext('2d');
            if (!ctx) {
                const r = new FileReader();
                r.onload = ev => resolve(ev.target.result);
                r.readAsDataURL(file);
                return;
            }
            ctx.imageSmoothingEnabled = true;
            ctx.imageSmoothingQuality = 'high';
            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
            const dataUrl = canvas.toDataURL('image/jpeg', quality);
            resolve(dataUrl);
        };
        img.onerror = () => {
            URL.revokeObjectURL(objUrl);
            const r = new FileReader();
            r.onload = ev => resolve(ev.target.result);
            r.onerror = () => resolve(null);
            r.readAsDataURL(file);
        };
        img.src = objUrl;
    });
}

async function handleStaffAvatarSelect(event) {
    const file = event.target.files && event.target.files[0];
    if (!file) return;

    const validTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
    if (!validTypes.includes(file.type.toLowerCase())) {
        showToast('Please select a valid image file (JPG, PNG, or WebP).', 'error');
        event.target.value = '';
        return;
    }

    if (file.size > 8 * 1024 * 1024) {
        showToast('Image file size must not exceed 8MB.', 'error');
        event.target.value = '';
        return;
    }

    const currentName = document.getElementById('profilePageFullName')?.value || (currentProfileData && currentProfileData.full_name) || 'Staff';
    const largeAvatar = document.getElementById('profileAvatarLarge');
    if (largeAvatar) largeAvatar.classList.add('avatar-uploading');

    const toast = showToast({
        title: 'Updating Photo',
        message: 'Optimizing and saving photo...',
        type: 'loading'
    });

    try {
        // Fast client-side image optimization (resizes to max 480x480)
        const base64Data = await compressAvatarFile(file, 480, 0.88);
        if (!base64Data) {
            throw new Error('Could not process selected image file.');
        }

        // Live visual preview immediately
        updateAvatarDisplays(base64Data, currentName);

        const res = await fetch('/sdn/api/staff.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'update_profile',
                image_data: base64Data
            })
        }).then(r => r.json());

        if (res.success && res.profile_image) {
            if (currentProfileData) currentProfileData.profile_image = res.profile_image;
            updateAvatarDisplays(res.profile_image, currentName);
            toast.update({
                title: 'Photo Updated',
                message: 'Profile photo saved successfully!',
                type: 'success'
            });
        } else {
            toast.update({
                title: 'Upload Failed',
                message: res.message || 'Failed to update photo.',
                type: 'error'
            });
            updateAvatarDisplays(currentProfileData?.profile_image || null, currentName);
        }
    } catch (err) {
        console.error('Error uploading avatar:', err);
        toast.update({
            title: 'Upload Error',
            message: 'Error uploading profile photo.',
            type: 'error'
        });
        updateAvatarDisplays(currentProfileData?.profile_image || null, currentName);
    } finally {
        if (largeAvatar) largeAvatar.classList.remove('avatar-uploading');
        if (event.target) event.target.value = '';
    }
}

async function removeStaffAvatar() {
    if (!confirm('Are you sure you want to remove your profile photo?')) return;

    const currentName = document.getElementById('profilePageFullName')?.value || (currentProfileData && currentProfileData.full_name) || 'Staff';
    const largeAvatar = document.getElementById('profileAvatarLarge');
    if (largeAvatar) largeAvatar.classList.add('avatar-uploading');

    const toast = showToast({
        title: 'Removing Photo',
        message: 'Removing profile photo...',
        type: 'loading'
    });

    try {
        const res = await fetch('/sdn/api/staff.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'update_profile',
                remove_image: true
            })
        }).then(r => r.json());

        if (res.success) {
            if (currentProfileData) currentProfileData.profile_image = null;
            updateAvatarDisplays(null, currentName);
            const fileInput = document.getElementById('staffAvatarInput');
            if (fileInput) fileInput.value = '';
            toast.update({
                title: 'Photo Removed',
                message: 'Profile photo removed successfully.',
                type: 'success'
            });
        } else {
            toast.update({
                title: 'Removal Failed',
                message: res.message || 'Failed to remove photo.',
                type: 'error'
            });
        }
    } catch (err) {
        console.error('Error removing avatar:', err);
        toast.update({
            title: 'Error',
            message: 'Error removing photo.',
            type: 'error'
        });
    } finally {
        if (largeAvatar) largeAvatar.classList.remove('avatar-uploading');
    }
}

function togglePassView(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const isPass = input.type === 'password';
    input.type = isPass ? 'text' : 'password';
    if (btn) {
        btn.innerHTML = isPass
            ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>'
            : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
    }
}

function resetStaffProfileForm() {
    clearFieldErrors(['profilePageFullName', 'profilePageNewPass', 'profilePageConfirmPass']);
    const alertBox = document.getElementById('profilePageAlert');
    if (alertBox) {
        alertBox.style.display = 'none';
        alertBox.innerHTML = '';
    }
    const nameInput = document.getElementById('profilePageFullName');
    if (nameInput) {
        const resetVal = (nameInput.defaultValue !== undefined && nameInput.defaultValue !== '') 
            ? nameInput.defaultValue 
            : (currentProfileData?.full_name || '');
        nameInput.value = resetVal;
    }
    const passInput = document.getElementById('profilePageNewPass');
    if (passInput) passInput.value = '';
    const confirmPass = document.getElementById('profilePageConfirmPass');
    if (confirmPass) confirmPass.value = '';

    showToast('Profile form reset to last saved state.', 'info');
}

async function submitStaffProfilePage(event) {
    if (event) event.preventDefault();
    clearFieldErrors(['profilePageFullName', 'profilePageNewPass', 'profilePageConfirmPass']);

    const alertBox = document.getElementById('profilePageAlert');
    if (alertBox) {
        alertBox.style.display = 'none';
        alertBox.innerHTML = '';
    }

    const fullNameInput = document.getElementById('profilePageFullName');
    const newPassInput = document.getElementById('profilePageNewPass');
    const confirmPassInput = document.getElementById('profilePageConfirmPass');

    const fullName = (fullNameInput?.value || '').trim();
    const newPass = newPassInput?.value || '';
    const confirmPass = confirmPassInput?.value || '';

    // Context-Aware Form Validation:
    // 1. Display name validation:
    if (!fullName) {
        return flagFieldError('profilePageFullName', 'Please enter your Full Name.');
    }
    if (fullName.length < 2) {
        return flagFieldError('profilePageFullName', 'Full Name must be at least 2 characters.');
    }
    if (fullName.length > 100) {
        return flagFieldError('profilePageFullName', 'Full Name cannot exceed 100 characters.');
    }

    // 2. Context-aware security credentials validation:
    // If the user intentionally leaves optional security fields blank, DO NOT validate them or trigger false-positive errors.
    // Only validate password criteria if a new password or confirmation was actually provided.
    const isPasswordChangeRequested = (newPass.length > 0 || confirmPass.length > 0);
    if (isPasswordChangeRequested) {
        if (!newPass) {
            return flagFieldError('profilePageNewPass', 'Please enter a new password or leave both password fields blank.');
        }
        if (newPass.length < 6) {
            return flagFieldError('profilePageNewPass', 'New password must be at least 6 characters long.');
        }
        if (!confirmPass) {
            return flagFieldError('profilePageConfirmPass', 'Please repeat your new password to confirm.');
        }
        if (newPass !== confirmPass) {
            return flagFieldError('profilePageConfirmPass', 'Confirm password does not match new password.');
        }
    }

    // 3. Modification detection: check if display name or passwords changed
    const initialName = (fullNameInput && fullNameInput.defaultValue !== undefined && fullNameInput.defaultValue !== '')
        ? fullNameInput.defaultValue.trim()
        : ((currentProfileData && currentProfileData.full_name) ? currentProfileData.full_name.trim() : '');
    const isNameModified = (fullName !== initialName);

    if (!isNameModified && !isPasswordChangeRequested) {
        showToast('No modifications detected. Your profile is already up to date.', 'info');
        return;
    }

    const saveBtns = [
        document.getElementById('btnSaveProfileBottom')
    ].filter(Boolean);

    saveBtns.forEach(btn => {
        btn.disabled = true;
        btn.dataset.origHtml = btn.innerHTML;
        btn.innerHTML = `<span>Saving...</span>`;
    });

    try {
        const payload = {
            action: 'update_profile',
            full_name: fullName
        };
        if (isPasswordChangeRequested && newPass) {
            payload.password = newPass;
        }

        const res = await fetch('/sdn/api/staff.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        }).then(r => r.json());

        if (res.success) {
            // Robust data binding: synchronize fresh database response
            const committedName = (res.data && res.data.full_name) || res.full_name || fullName;
            const committedImage = (res.data && res.data.profile_image !== undefined)
                ? res.data.profile_image
                : (res.profile_image || (currentProfileData && currentProfileData.profile_image) || null);

            // Update current profile data cache
            if (!currentProfileData) currentProfileData = {};
            if (res.data) {
                Object.assign(currentProfileData, res.data);
            } else {
                currentProfileData.full_name = committedName;
                if (committedImage !== undefined) currentProfileData.profile_image = committedImage;
            }

            // Sync input defaultValue to commit point
            if (fullNameInput) {
                fullNameInput.value = committedName;
                fullNameInput.defaultValue = committedName;
            }

            // Update names across UI: top header profile badge, hero title, card
            document.querySelectorAll('.header-profile-name').forEach(el => {
                el.textContent = committedName;
            });
            document.querySelectorAll('.staff-avatar-sync').forEach(el => {
                el.title = committedName;
            });
            const cardName = document.getElementById('profileCardName');
            if (cardName) cardName.textContent = committedName;

            // Re-render avatar displays with committed photo and name
            updateAvatarDisplays(committedImage, committedName);

            // Clear password inputs
            if (newPassInput) newPassInput.value = '';
            if (confirmPassInput) confirmPassInput.value = '';

            const successMessage = isPasswordChangeRequested
                ? 'Profile and security credentials updated successfully!'
                : 'Profile details updated successfully!';
            showToast(successMessage, 'success');

            if (alertBox) {
                alertBox.className = 'profile-inline-alert alert-success';
                alertBox.innerHTML = `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right:6px;"><polyline points="20 6 9 17 4 12"></polyline></svg> <strong>Saved:</strong> ${escapeHtml(successMessage)}`;
                alertBox.style.display = 'flex';
                setTimeout(() => { alertBox.style.display = 'none'; }, 4500);
            }
        } else {
            showToast(res.message || 'Profile update failed', 'error');
            if (alertBox) {
                alertBox.className = 'profile-inline-alert alert-error';
                alertBox.innerHTML = `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right:6px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg> <strong>Error:</strong> ${escapeHtml(res.message || 'Profile update failed.')}`;
                alertBox.style.display = 'flex';
            }
        }
    } catch (e) {
        console.error(e);
        showToast('Error updating staff profile', 'error');
        if (alertBox) {
            alertBox.className = 'profile-inline-alert alert-error';
            alertBox.innerHTML = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right:6px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg> <strong>Error:</strong> Failed to connect to server. Please try again.';
            alertBox.style.display = 'flex';
        }
    } finally {
        saveBtns.forEach(btn => {
            btn.disabled = false;
            if (btn.dataset.origHtml) {
                btn.innerHTML = btn.dataset.origHtml;
            }
        });
    }
}

function openDoubleBookingProtocolModal() {
    document.getElementById('doubleBookingModal')?.classList.add('show');
}

function closeDoubleBookingProtocolModal() {
    document.getElementById('doubleBookingModal')?.classList.remove('show');
}

async function loadEMR() {
    try {
        const res = await fetch('/sdn/api/history.php?_='+Date.now()).then(r=>r.json());
        if(res.success && res.data) {
            cachedEmrRecords = res.data;
            if(res.data.length === 0) {
                document.getElementById('emr-tbody').innerHTML = '<tr><td colspan="4" style="text-align:center; padding: 40px; color:var(--muted)">No EMR records found.</td></tr>';
                return;
            }
            
            document.getElementById('emr-tbody').innerHTML = res.data.map(h => {
                let safeName = escapeHtml(h.full_name);
                let safeId = escapeHtml(h.patient_id);
                let safeDate = escapeHtml(h.date || 'N/A');
                let safeDiag = escapeHtml(h.diagnosis || 'None');
                let safeTreat = escapeHtml(h.treatment || 'None');

                return `
                <tr>
                    <td>
                        <strong style="color:var(--deep-green); font-size:1.05rem;">${safeName}</strong><br>
                        <small style="color:var(--muted)">ID: ${safeId}</small>
                    </td>
                    <td>${safeDate}</td>
                    <td>
                        <div style="max-width:300px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                            <strong>Diag:</strong> ${safeDiag}<br>
                            <small><strong>Treat:</strong> ${safeTreat}</small>
                        </div>
                    </td>
                    <td>
                        <div class="emr-actions-wrap">
                            <button type="button" class="btn-table-action btn-table-emr" onclick="openNoteModal('${safeId}', '${encodeURIComponent(h.full_name||'')}', '${encodeURIComponent(h.diagnosis||'')}', '${encodeURIComponent(h.treatment||'')}', '${encodeURIComponent(h.prescription||'')}', '${encodeURIComponent(h.active_medications||'')}', '${encodeURIComponent(h.next_visit_date||'')}', '${encodeURIComponent(h.blood_pressure||'')}', '${encodeURIComponent(h.temperature||'')}', '${encodeURIComponent(h.heart_rate||'')}', '${encodeURIComponent(h.weight||'')}')">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
                                    <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
                                    <path d="M9 14h6"></path>
                                    <path d="M9 18h6"></path>
                                    <path d="M9 10h1"></path>
                                </svg>
                                <span>Add / Edit Note</span>
                            </button>
                            <button type="button" class="btn-table-action btn-table-print" onclick="quickPrintEMR('${safeId}')" title="Print EMR Clinical Record">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                                    <rect x="6" y="14" width="12" height="8"></rect>
                                </svg>
                                <span>Print</span>
                            </button>
                        </div>
                    </td>
                </tr>
            `}).join('');
            syncTableScrollbars();
        }
    } catch(e) { console.error(e); }
}

function openNoteModal(id, fullName, diag, treat, presc, activeMeds, nextVisit, bp, temp, hr, wt) {
    clearFieldErrors(['noteDiag', 'noteTreat']);
    document.getElementById('notePatientId').value = id;
    document.getElementById('notePatientName').value = fullName ? decodeURIComponent(fullName) : id;
    document.getElementById('noteDiag').value = diag ? decodeURIComponent(diag) : '';
    document.getElementById('noteTreat').value = treat ? decodeURIComponent(treat) : '';
    document.getElementById('notePresc').value = presc ? decodeURIComponent(presc) : '';
    document.getElementById('activeMeds').value = activeMeds ? decodeURIComponent(activeMeds) : '';
    document.getElementById('nextVisit').value = nextVisit && nextVisit !== 'null' ? nextVisit : '';
    
    // vitals
    document.getElementById('triageBp').value = bp && bp !== 'null' ? decodeURIComponent(bp) : '';
    document.getElementById('triageTemp').value = temp && temp !== 'null' ? decodeURIComponent(temp) : '';
    document.getElementById('triageHr').value = hr && hr !== 'null' ? decodeURIComponent(hr) : '';
    document.getElementById('triageWeight').value = wt && wt !== 'null' ? decodeURIComponent(wt) : '';

    document.getElementById('noteModal').classList.add('show');
}

function closeNoteModal() {
    clearFieldErrors(['noteDiag', 'noteTreat']);
    const modal = document.getElementById('noteModal');
    if (modal) modal.classList.remove('show');
}

async function submitNote() {
    clearFieldErrors(['noteDiag', 'noteTreat']);
    const patient_id = document.getElementById('notePatientId').value;
    const diagnosis = document.getElementById('noteDiag').value.trim();
    const treatment = document.getElementById('noteTreat').value.trim();
    const prescription = document.getElementById('notePresc').value.trim();
    const active_medications = document.getElementById('activeMeds').value.trim();
    const next_visit_date = document.getElementById('nextVisit').value;
    
    const blood_pressure = document.getElementById('triageBp').value.trim();
    const temperature = document.getElementById('triageTemp').value.trim();
    const heart_rate = document.getElementById('triageHr').value.trim();
    const weight = document.getElementById('triageWeight').value.trim();
    
    if (!diagnosis) {
        return flagFieldError('noteDiag', 'Clinical Diagnosis is required.');
    }
    if (!treatment) {
        return flagFieldError('noteTreat', 'Treatment / Clinical Plan is required.');
    }
    
    try {
        const res = await fetch('/sdn/api/history.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                patient_id, diagnosis, treatment, prescription, 
                active_medications, next_visit_date,
                blood_pressure, temperature, heart_rate, weight
            })
        }).then(r => r.json());

        if (res.success) {
            closeNoteModal();
            loadEMR();
            showToast('Clinical note and vitals saved successfully.');
        } else {
            showToast(res.message || 'Error saving note', 'error');
        }
    } catch(e) { 
        console.error(e); 
        showToast('Error saving note', 'error');
    }
}

function printClinicalNote() {
    const patientId = document.getElementById('notePatientId').value;
    const patientName = document.getElementById('notePatientName').value || patientId;
    const bp = document.getElementById('triageBp').value || 'N/A';
    const temp = document.getElementById('triageTemp').value || 'N/A';
    const hr = document.getElementById('triageHr').value || 'N/A';
    const wt = document.getElementById('triageWeight').value || 'N/A';
    const diag = document.getElementById('noteDiag').value || 'None';
    const treat = document.getElementById('noteTreat').value || 'None';
    const presc = document.getElementById('notePresc').value || 'None';
    const activeMeds = document.getElementById('activeMeds').value || 'None';
    const nextVisit = document.getElementById('nextVisit').value || 'None scheduled';

    generateAndTriggerPrint({
        patient_id: patientId,
        full_name: patientName,
        blood_pressure: bp,
        temperature: temp,
        heart_rate: hr,
        weight: wt,
        diagnosis: diag,
        treatment: treat,
        prescription: presc,
        active_medications: activeMeds,
        next_visit_date: nextVisit
    });
}

function quickPrintEMR(patientId) {
    const record = cachedEmrRecords.find(r => r.patient_id === patientId);
    if (!record) {
        return showToast('Record not found', 'error');
    }
    generateAndTriggerPrint(record);
}

function generateAndTriggerPrint(data) {
    const todayDate = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    const todayTime = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });

    const htmlContent = `<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Clinical Note - ${escapeHtml(data.patient_id || '')}</title>
    <style>
        @page {
            size: portrait;
            margin: 15mm 15mm 15mm 15mm;
        }
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: #1e293b;
            background: #ffffff;
            font-size: 13px;
            line-height: 1.4;
            position: relative;
            min-height: 100vh;
        }
        .watermark-bg {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 440px;
            max-width: 72%;
            opacity: 0.11;
            z-index: 0;
            pointer-events: none;
            display: flex;
            align-items: center;
            justify-content: center;
            user-select: none;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .watermark-bg img {
            width: 100%;
            height: auto;
            display: block;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .header {
            position: relative;
            z-index: 1;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 3px solid #15803d;
            padding-bottom: 14px;
            margin-bottom: 18px;
        }
        .header h1 {
            margin: 0;
            font-size: 1.22rem;
            color: #15803d;
            font-weight: 800;
            letter-spacing: 0.3px;
        }
        .header .subtitle {
            margin: 3px 0 0 0;
            font-size: 0.88rem;
            color: #334155;
            font-weight: 600;
        }
        .header .dept {
            margin: 2px 0 0 0;
            font-size: 0.82rem;
            color: #64748b;
        }
        .header-badge {
            display: inline-block;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 700;
            color: #0f172a;
        }
        .patient-box {
            position: relative;
            z-index: 1;
            background: rgba(248, 250, 252, 0.88);
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 18px;
            display: flex;
            justify-content: space-between;
        }
        .section-title {
            position: relative;
            z-index: 1;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #15803d;
            font-weight: 700;
            margin: 0 0 8px 0;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
        }
        .vitals-grid {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 18px;
        }
        .vital-card {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px;
            text-align: center;
            background: rgba(255, 255, 255, 0.90);
        }
        .vital-card .label {
            font-size: 0.72rem;
            color: #64748b;
            font-weight: 700;
            text-transform: uppercase;
        }
        .vital-card .value {
            font-size: 1rem;
            font-weight: 700;
            color: #0f172a;
            margin-top: 2px;
        }
        .content-card {
            position: relative;
            z-index: 1;
            background: rgba(255, 255, 255, 0.90);
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 10px 14px;
            margin-bottom: 14px;
        }
        .footer {
            position: relative;
            z-index: 1;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 32px;
            padding-top: 18px;
            border-top: 1px dashed #cbd5e1;
        }
    </style>
</head>
<body>
    <div class="watermark-bg" aria-hidden="true">
        <img src="/sdn/public/assets/mmwgh_logo.png" alt="MMWGH Watermark">
    </div>

    <div class="header">
        <div style="display:flex; align-items:center; gap:12px;">
            <img src="/sdn/public/assets/mmwgh_logo.png" alt="MMWGH Logo" style="width:52px; height:52px; object-fit:contain; border-radius:8px; border:1px solid #dcfce7; background:#ffffff; padding:2px; flex-shrink:0;">
            <div>
                <h1>MARIVELES MENTAL WELLNESS AND GENERAL HOSPITAL</h1>
                <p class="subtitle">Clinical Consultation & Medical Triage Summary (SDN)</p>
                <p class="dept">Department: ${escapeHtml(STAFF_DEPT)}</p>
            </div>
        </div>
        <div style="text-align:right;">
            <span class="header-badge">OFFICIAL MEDICAL NOTE</span>
            <p style="margin:4px 0 0 0; font-size:0.75rem; color:#64748b;">Generated: ${todayDate} ${todayTime}</p>
        </div>
    </div>

    <div class="patient-box">
        <div>
            <div style="font-size:0.75rem; color:#64748b; text-transform:uppercase; font-weight:700;">Patient Name</div>
            <div style="font-size:1.15rem; color:#0f172a; font-weight:700;">${escapeHtml(data.full_name || 'N/A')}</div>
        </div>
        <div style="text-align:right;">
            <div style="font-size:0.75rem; color:#64748b; text-transform:uppercase; font-weight:700;">Patient ID</div>
            <div style="font-size:1.1rem; color:#15803d; font-weight:700;">${escapeHtml(data.patient_id || 'N/A')}</div>
        </div>
    </div>

    <div>
        <div class="section-title">1. Triage Vital Signs</div>
        <div class="vitals-grid">
            <div class="vital-card">
                <div class="label">Blood Pressure</div>
                <div class="value">${escapeHtml(data.blood_pressure || 'N/A')} mmHg</div>
            </div>
            <div class="vital-card">
                <div class="label">Temperature</div>
                <div class="value">${escapeHtml(data.temperature || 'N/A')} °C</div>
            </div>
            <div class="vital-card">
                <div class="label">Heart Rate</div>
                <div class="value">${escapeHtml(data.heart_rate || 'N/A')} bpm</div>
            </div>
            <div class="vital-card">
                <div class="label">Weight</div>
                <div class="value">${escapeHtml(data.weight || 'N/A')} kg</div>
            </div>
        </div>

        <div class="section-title">2. Diagnosis & Clinical Assessment</div>
        <div class="content-card">
            <p style="margin:0; font-weight:600; color:#0f172a;">${escapeHtml(data.diagnosis || 'No clinical diagnosis recorded.')}</p>
        </div>

        <div class="section-title">3. Treatment Plan & Management</div>
        <div class="content-card">
            <p style="margin:0; color:#1e293b;">${escapeHtml(data.treatment || 'No treatment regimen recorded.')}</p>
        </div>

        <div class="section-title">4. Medical Prescription & Active Medications</div>
        <div class="content-card">
            <strong style="display:block; font-size:0.78rem; color:#475569; margin-bottom:2px; text-transform:uppercase;">Prescription:</strong>
            <p style="margin:0 0 6px 0; color:#0f172a;">${escapeHtml(data.prescription || 'None prescribed.')}</p>
            <strong style="display:block; font-size:0.78rem; color:#475569; margin-bottom:2px; text-transform:uppercase;">Current Medications:</strong>
            <p style="margin:0; color:#0f172a;">${escapeHtml(data.active_medications || 'None recorded.')}</p>
        </div>
    </div>

    <div class="footer">
        <div>
            <span style="font-size:0.78rem; font-weight:700; color:#475569; text-transform:uppercase;">Next Scheduled Visit / Follow-up:</span>
            <p style="margin:3px 0 0 0; font-size:0.95rem; font-weight:700; color:#15803d;">${escapeHtml(data.next_visit_date || 'None scheduled')}</p>
        </div>
        <div style="text-align:center; min-width:200px;">
            <div style="border-bottom:1px solid #0f172a; width:100%; height:35px;"></div>
            <p style="margin:4px 0 0 0; font-size:0.8rem; font-weight:600; color:#0f172a;">Attending Medical Staff</p>
            <p style="margin:1px 0 0 0; font-size:0.72rem; color:#64748b;">${escapeHtml(STAFF_DEPT)}</p>
        </div>
    </div>
</body>
</html>`;

    let iframe = document.getElementById('printNoteIframe');
    if (!iframe) {
        iframe = document.createElement('iframe');
        iframe.id = 'printNoteIframe';
        iframe.style.position = 'fixed';
        iframe.style.right = '0';
        iframe.style.bottom = '0';
        iframe.style.width = '0';
        iframe.style.height = '0';
        iframe.style.border = '0';
        document.body.appendChild(iframe);
    }

    const doc = iframe.contentWindow.document;
    doc.open();
    doc.write(htmlContent);
    doc.close();

    iframe.onload = () => {
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
    };
    // Trigger in case onload already happened
    setTimeout(() => {
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
    }, 100);
}

async function updateDocStatus(status) {
    if (!status) return;
    updateSingleDoctorStatus(null, status, null);
}

async function updateSingleDoctorStatus(docName, status, docId) {
    if (!status) return;
    try {
        const payload = {
            action: 'set_status',
            status: status
        };
        if (docId) payload.doctor_id = docId;
        if (docName) payload.doctor_name = docName;

        // Optimistically update cachedDepartmentDoctors
        if (cachedDepartmentDoctors && cachedDepartmentDoctors.length > 0) {
            const d = cachedDepartmentDoctors.find(item => 
                (docId && item.id == docId) || 
                (docName && item.name && (item.name === docName || item.name.includes(docName) || docName.includes(item.name)))
            );
            if (d) d.status = status;
        }

        // Optimistically update cachedQueuePatients
        if (cachedQueuePatients && cachedQueuePatients.length > 0) {
            cachedQueuePatients.forEach(p => {
                if (p.assigned_doctor && docName && (p.assigned_doctor === docName || p.assigned_doctor.includes(docName) || docName.includes(p.assigned_doctor))) {
                    p.doctor_status = status;
                }
            });
        }

        renderQueueTable();
        renderDoctorStatusModalContent();

        const res = await fetch('/sdn/api/schedule.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        }).then(r => r.json());

        if (res.success) {
            const label = getDoctorStatusLabel(status);
            showToast(`${docName || 'Doctor'} marked as ${label}`, 'success');
        } else {
            showToast(res.error || 'Failed to update doctor status', 'error');
            loadDepartmentDoctors();
            loadQueue();
        }
    } catch(e) {
        console.error(e);
        showToast('Error updating doctor status', 'error');
    }
}

function openDoctorStatusModal() {
    const modal = document.getElementById('doctorStatusModal');
    if (!modal) return;
    modal.classList.add('show');
    renderDoctorStatusModalContent();
    loadDepartmentDoctors();
}

function closeDoctorStatusModal() {
    const modal = document.getElementById('doctorStatusModal');
    if (modal) modal.classList.remove('show');
}

function renderDoctorStatusModalContent() {
    const container = document.getElementById('doctorStatusListBody');
    if (!container) return;

    if (!cachedDepartmentDoctors || cachedDepartmentDoctors.length === 0) {
        container.innerHTML = '<p style="text-align:center; padding:35px; color:#64748b;">Loading doctors for your department...</p>';
        return;
    }

    container.innerHTML = `
        <div class="doctor-status-cards-grid">
            ${cachedDepartmentDoctors.map(doc => {
                const curStatus = normalizeDocStatus(doc.status);
                const safeName = escapeHtml(doc.name);
                const safeSpec = escapeHtml(doc.specialization || 'Clinical Specialist');
                const docId = doc.id;

                return `
                    <div class="doctor-status-card">
                        <div class="doc-card-header">
                            <div class="doc-avatar-circle">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:#0d9488;"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"></path><path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"></path><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"></path><path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"></path></svg>
                            </div>
                            <div class="doc-info-block">
                                <strong class="doc-title">${safeName}</strong>
                                <span class="doc-spec-sub">${safeSpec}</span>
                            </div>
                            <div class="doc-current-badge-wrap">
                                ${formatDoctorStatusBadge(curStatus)}
                            </div>
                        </div>
                        <div class="doc-status-buttons-row">
                            <button type="button" class="btn-status-pill status-opt-inclinic ${curStatus === 'in_clinic' ? 'is-active' : ''}" data-name="${safeName}" onclick="updateSingleDoctorStatus(this.dataset.name, 'in_clinic', ${docId})">
                                <span class="status-indicator-dot inclinic"></span> In Clinic
                            </button>
                            <button type="button" class="btn-status-pill status-opt-onbreak ${curStatus === 'on_break' ? 'is-active' : ''}" data-name="${safeName}" onclick="updateSingleDoctorStatus(this.dataset.name, 'on_break', ${docId})">
                                <span class="status-indicator-dot onbreak"></span> On Break
                            </button>
                            <button type="button" class="btn-status-pill status-opt-emergency ${curStatus === 'emergency' ? 'is-active' : ''}" data-name="${safeName}" onclick="updateSingleDoctorStatus(this.dataset.name, 'emergency', ${docId})">
                                <span class="status-indicator-dot emergency"></span> Emergency
                            </button>
                            <button type="button" class="btn-status-pill status-opt-offduty ${curStatus === 'leave' ? 'is-active' : ''}" data-name="${safeName}" onclick="updateSingleDoctorStatus(this.dataset.name, 'leave', ${docId})">
                                <span class="status-indicator-dot offduty"></span> Off Duty
                            </button>
                        </div>
                    </div>
                `;
            }).join('')}
        </div>
    `;
}
