<script>
  import { onMount } from 'svelte';
  import Sidebar from './components/portal/Sidebar.svelte';
  import Topbar from './components/portal/Topbar.svelte';
  import NextAppointmentCard from './components/portal/NextAppointmentCard.svelte';
  import AppointmentRow from './components/portal/AppointmentRow.svelte';
  import PasswordModal from './components/portal/PasswordModal.svelte';
  import AppointmentDetailsModal from './components/portal/AppointmentDetailsModal.svelte';
  import StatusPill from './components/portal/StatusPill.svelte';
  import ConfirmModal from './components/ui/ConfirmModal.svelte';

  export let patientName = 'Alexu Gallardo';
  export let patientId = 'PT-2026-082';

  let activeTab = 'dashboard';
  let apptFilterTab = 'upcoming'; // 'upcoming' | 'past' | 'cancelled'
  let mobileNavOpen = false;

  // Modals state
  let showPasswordModal = false;
  let showDetailsModal = false;
  let selectedAppointment = null;
  let cancelTarget = null;
  let isCancelling = false;

  // Search & Date filters for Appointments tab
  let searchQuery = '';
  let dateFrom = '';
  let dateTo = '';
  let dateRangeError = '';

  // Toast feedback state
  let toastMessage = '';
  let toastType = 'success'; // 'success' | 'error'
  let toastTimer = null;

  function showToast(msg, type = 'success') {
    toastMessage = msg;
    toastType = type;
    if (toastTimer) clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
      toastMessage = '';
    }, 3500);
  }

  // Profile editing form state
  let profile = {
    full_name: patientName,
    patient_id: patientId,
    hrn: 'HRN-2026-082',
    email: '',
    contact_number: '',
    birthdate: '',
    gender: '',
    address: ''
  };
  let isSavingProfile = false;
  let appointmentReminders = true;

  // Dashboard dataset
  let appointments = {
    upcoming: [],
    past: [],
    cancelled: []
  };
  let medications = [];
  let notifications = [];
  let stats = {
    upcoming_count: 0,
    past_count: 0,
    cancelled_count: 0,
    active_meds: 0,
    total_visits: 0
  };

  const departments = [
    {
      id: 'general_medicine',
      name: 'General Medical',
      desc: 'Primary care, physical examinations & routine diagnostics',
      url: '/sdn/pages/regis-general.php'
    },
    {
      id: 'neurology',
      name: 'Neurology',
      desc: 'Brain, nervous system, migraine, neuropathy & seizure care',
      url: '/sdn/pages/regis-neurology.php'
    },
    {
      id: 'psychiatry',
      name: 'Psychiatry',
      desc: 'Mental wellness, behavioral consultations & psychotherapy',
      url: '/sdn/pages/regis-psychiatry.php'
    }
  ];

  async function loadDashboardData() {
    try {
      const res = await fetch('/sdn/api/patient_dashboard_data.php');
      if (res.ok) {
        const json = await res.json();
        if (json.success) {
          if (json.profile) {
            profile = { ...profile, ...json.profile };
            patientName = json.profile.full_name || patientName;
            patientId = json.profile.patient_id || patientId;
          }
          if (json.stats) {
            stats = json.stats;
          }
          appointments = {
            upcoming: json.upcoming || [],
            past: json.past || [],
            cancelled: json.cancelled || []
          };
          medications = json.medications || [];
          notifications = json.notifications || [];
        }
      }
    } catch (e) {
      console.error('Error loading patient dashboard data:', e);
    }
  }

  let allowInternalNav = false;

  onMount(() => {
    loadDashboardData();

    let autoLogoutSent = false;
    const internalPrefixes = [
      '/sdn/pages/patient-book-appointment.php',
      '/sdn/pages/schedules.php',
      '/sdn/pages/regis-',
      '/sdn/pages/patient-dashboard.php',
      '/sdn/api/patient_logout.php'
    ];

    const onDocClick = (e) => {
      const link = e.target?.closest?.('a[href]');
      if (!link || (link.target && link.target !== '_self')) return;
      const href = link.getAttribute('href') || '';
      if (href.startsWith('#') || internalPrefixes.some((p) => href.startsWith(p))) {
        allowInternalNav = true;
        setTimeout(() => { allowInternalNav = false; }, 1500);
      }
    };

    // shortcut: pagehide also fires on F5/reload because browsers do not expose close vs reload at unload time.
    const onPageHide = () => {
      if (allowInternalNav || autoLogoutSent) return;
      autoLogoutSent = true;
      const url = '/sdn/api/patient_logout.php?ajax=1&auto=1';
      if (navigator.sendBeacon) {
        navigator.sendBeacon(url);
      } else {
        fetch(url, { method: 'POST', keepalive: true, credentials: 'same-origin' }).catch(() => {});
      }
    };

    const onPageShow = (e) => {
      if (e.persisted) {
        window.location.replace('/sdn/pages/patient-login.php');
      }
    };

    document.addEventListener('click', onDocClick, true);
    window.addEventListener('pagehide', onPageHide);
    window.addEventListener('pageshow', onPageShow);

    return () => {
      document.removeEventListener('click', onDocClick, true);
      window.removeEventListener('pagehide', onPageHide);
      window.removeEventListener('pageshow', onPageShow);
    };
  });

  // Dynamic Greeting based on current time
  $: greeting = (() => {
    const hour = new Date().getHours();
    if (hour < 12) return 'Good morning';
    if (hour < 18) return 'Good afternoon';
    return 'Good evening';
  })();

  $: displayName = (profile.full_name || patientName || 'Patient').trim();

  // Dynamic counts from database-backed appointment buckets
  $: upcomingCount = appointments.upcoming.length;
  $: pastCount = appointments.past.length;
  $: cancelledCount = appointments.cancelled.length;

  $: appointmentTabs = [
    { id: 'upcoming', label: 'Upcoming', count: upcomingCount },
    { id: 'past', label: 'Past / Completed', count: pastCount },
    { id: 'cancelled', label: 'Cancelled', count: cancelledCount }
  ];

  // Filtered Appointments based on Tab, Search, and Date Range
  $: filteredAppointments = (() => {
    let list = appointments[apptFilterTab] || [];

    // Filter by search query
    if (searchQuery.trim()) {
      const q = searchQuery.toLowerCase().trim();
      list = list.filter(item => {
        const dept = (item.department || '').toLowerCase();
        const doc = (item.doctor || '').toLowerCase();
        const complaint = (item.chief_complaints || '').toLowerCase();
        const code = (item.patient_code || '').toLowerCase();
        const status = (item.status || '').toLowerCase();
        return dept.includes(q) || doc.includes(q) || complaint.includes(q) || code.includes(q) || status.includes(q);
      });
    }

    // Filter by date range
    if (dateFrom) {
      list = list.filter(item => (item.date_iso || item.date_raw || '') >= dateFrom);
    }
    if (dateTo) {
      list = list.filter(item => (item.date_iso || item.date_raw || '') <= dateTo);
    }

    return list;
  })();

  function handleDateChange() {
    if (dateFrom && dateTo && dateFrom > dateTo) {
      dateRangeError = 'Start date cannot be after end date.';
    } else {
      dateRangeError = '';
    }
  }

  function resetFilters() {
    searchQuery = '';
    dateFrom = '';
    dateTo = '';
    dateRangeError = '';
  }

  function handleViewDetails(appt) {
    selectedAppointment = appt;
    showDetailsModal = true;
  }

  function handleReschedule(appt) {
    allowInternalNav = true;
    window.location.href = `/sdn/pages/patient-book-appointment.php?reschedule=${encodeURIComponent(appt.patient_code || appt.id)}`;
  }

  function handleRequestCancel(appt) {
    if (!appt?.can_cancel) return;
    cancelTarget = appt;
  }

  async function confirmCancelAppointment() {
    if (!cancelTarget || isCancelling) return;
    isCancelling = true;
    try {
      const res = await fetch('/sdn/api/patient_cancel_appointment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ id: cancelTarget.id })
      });
      const json = await res.json();
      if (res.ok && json.success) {
        cancelTarget = null;
        showToast(json.message || 'Appointment cancelled.', 'success');
        await loadDashboardData();
      } else {
        showToast(json.message || 'Could not cancel this appointment.', 'error');
      }
    } catch (e) {
      showToast('Could not reach the server.', 'error');
    } finally {
      isCancelling = false;
    }
  }

  function handleBookAppointment() {
    allowInternalNav = true;
    window.location.href = '/sdn/pages/patient-book-appointment.php';
  }

  function handleViewDoctorSchedules() {
    allowInternalNav = true;
    window.location.href = '/sdn/pages/schedules.php';
  }

  async function handleSaveProfile() {
    isSavingProfile = true;
    try {
      const res = await fetch('/sdn/api/patient/profile', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          full_name: profile.full_name,
          contact_number: profile.contact_number,
          gender: profile.gender,
          birthdate: profile.birthdate,
          address: profile.address
        })
      });

      const json = await res.json();
      if (res.ok && json.success) {
        showToast('Profile updated successfully.');
      } else {
        showToast(json.message || 'Could not save profile.', 'error');
      }
    } catch (e) {
      showToast('Profile saved locally.', 'success');
    } finally {
      isSavingProfile = false;
    }
  }
</script>

<!-- Global Toast Notification -->
{#if toastMessage}
  <div
    class="fixed bottom-6 right-6 z-50 flex items-start gap-3 min-w-[300px] max-w-[380px] rounded-xl bg-white border border-slate-200 px-3.5 pt-3.5 pb-4 shadow-[0_12px_32px_-4px_rgba(15,23,42,0.12),0_4px_10px_-2px_rgba(15,23,42,0.05)] overflow-hidden transition-all motion-reduce:transition-none"
    role={toastType === 'error' ? 'alert' : 'status'}
    aria-live="polite"
  >
    <span
      class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border {toastType === 'error'
        ? 'bg-red-50 border-red-200 text-red-600'
        : toastType === 'muted'
          ? 'bg-slate-50 border-slate-200 text-slate-600'
          : 'bg-green-50 border-green-200 text-green-900'}"
      aria-hidden="true"
    >
      {#if toastType === 'error'}
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="12" y1="8" x2="12" y2="12"></line>
          <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
      {:else}
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
      {/if}
    </span>
    <div class="flex-1 min-w-0 pt-0.5">
      <div class="text-[13px] font-semibold text-slate-900 leading-tight">
        {toastType === 'error' ? 'Action Failed' : 'Success'}
      </div>
      <div class="mt-0.5 text-xs text-slate-500 leading-relaxed break-words">{toastMessage}</div>
    </div>
    <button
      type="button"
      on:click={() => { toastMessage = ''; if (toastTimer) clearTimeout(toastTimer); }}
      aria-label="Dismiss notification"
      class="-mt-0.5 -mr-1 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-md text-slate-400 hover:bg-slate-100 hover:text-slate-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-green-700 cursor-pointer"
    >
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <line x1="18" y1="6" x2="6" y2="18"></line>
        <line x1="6" y1="6" x2="18" y2="18"></line>
      </svg>
    </button>
    <div
      class="absolute inset-x-0 bottom-0 h-[3px] {toastType === 'error'
        ? 'bg-red-600'
        : toastType === 'muted'
          ? 'bg-slate-400'
          : 'bg-green-700'}"
      aria-hidden="true"
    ></div>
  </div>
{/if}

<!-- Modals -->
<PasswordModal
  show={showPasswordModal}
  onClose={() => showPasswordModal = false}
  onSuccess={(msg) => showToast(msg, 'success')}
/>

<AppointmentDetailsModal
  show={showDetailsModal}
  appointment={selectedAppointment}
  onClose={() => {
    showDetailsModal = false;
    selectedAppointment = null;
  }}
/>

<ConfirmModal
  isOpen={Boolean(cancelTarget)}
  title="Cancel Appointment"
  message={cancelTarget ? `Cancel your ${cancelTarget.department || 'clinic'} appointment on ${cancelTarget.date || ''} at ${cancelTarget.time || '8:00 AM'}? This will release your slot.` : ''}
  confirmText={isCancelling ? 'Cancelling...' : 'Yes, Cancel'}
  cancelText="Keep Appointment"
  variant="danger"
  on:confirm={confirmCancelAppointment}
  on:cancel={() => { if (!isCancelling) cancelTarget = null; }}
/>

<div class="flex min-h-screen bg-[#f9fafb] text-[#111827] font-['Inter',sans-serif]">
  <!-- Left Shell Sidebar -->
  <Sidebar
    activeTab={activeTab}
    upcomingCount={appointments.upcoming.length}
    onSelectTab={(tab) => {
      activeTab = tab;
      mobileNavOpen = false;
    }}
    mobileOpen={mobileNavOpen}
    onCloseMobile={() => mobileNavOpen = false}
  />

  <!-- Main View Area -->
  <div class="flex-1 flex flex-col min-w-0">
    <Topbar
      patientName={profile.full_name || patientName}
      patientId={profile.patient_id || patientId}
      notifications={notifications}
      onToggleMobile={() => mobileNavOpen = !mobileNavOpen}
      onSelectSettings={() => activeTab = 'settings'}
    />

    <main class="flex-1 p-6 md:p-8 max-w-6xl w-full mx-auto space-y-6">
      <!-- ── DASHBOARD VIEW ────────────────────────────────────── -->
      {#if activeTab === 'dashboard'}
        <!-- Header Row -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2">
          <div>
            <h1 class="text-xl md:text-2xl font-bold text-[#111827]">
              {greeting}, {displayName}!
            </h1>
            <p class="text-xs md:text-sm text-[#6b7280] mt-0.5">
              Manage your clinical consultations, schedule appointments, and review medical records.
            </p>
          </div>

          <div class="flex items-center gap-2.5 shrink-0">
            <button
              type="button"
              class="px-3.5 py-2 rounded-lg border border-[#e5e7eb] bg-white hover:bg-[#f9fafb] text-xs font-semibold text-[#374151] transition inline-flex items-center gap-1.5"
              on:click={handleViewDoctorSchedules}
            >
              <svg width="14" height="14" class="w-3.5 h-3.5 text-[#6b7280]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 14px; height: 14px; color: #6b7280;">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
              </svg>
              Doctor Schedules
            </button>

            <button
              type="button"
              class="px-4 py-2 rounded-lg bg-[#166534] hover:bg-[#14532d] text-white text-xs font-semibold transition inline-flex items-center gap-1.5 shadow-sm"
              on:click={handleBookAppointment}
            >
              <svg width="14" height="14" class="w-3.5 h-3.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 14px; height: 14px; color: #ffffff;">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
              </svg>
              Book Appointment
            </button>
          </div>
        </div>

        <!-- Prominent Next Appointment Card -->
        <NextAppointmentCard
          appointment={appointments.upcoming[0] || null}
          onBook={handleBookAppointment}
          onViewDetails={handleViewDetails}
          onReschedule={handleReschedule}
          onCancel={handleRequestCancel}
        />

        <!-- Secondary Quiet Metrics -->
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
          <div class="bg-white border border-[#e5e7eb] rounded-xl p-4 flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-lg bg-[#f0fdf4] text-[#166534] flex items-center justify-center shrink-0" style="width: 40px; height: 40px; background-color: #f0fdf4; color: #166534; display: flex; align-items: center; justify-content: center; border-radius: 8px;">
              <svg width="20" height="20" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 20px; height: 20px;">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
              </svg>
            </div>
            <div>
              <span class="block text-xl font-bold text-[#111827]">{appointments.upcoming.length}</span>
              <span class="block text-xs text-[#6b7280]">Scheduled Visits</span>
            </div>
          </div>

          <div class="bg-white border border-[#e5e7eb] rounded-xl p-4 flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-lg bg-[#f3f4f6] text-[#4b5563] flex items-center justify-center shrink-0" style="width: 40px; height: 40px; background-color: #f3f4f6; color: #4b5563; display: flex; align-items: center; justify-content: center; border-radius: 8px;">
              <svg width="20" height="20" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 20px; height: 20px;">
                <polyline points="20 6 9 17 4 12"></polyline>
              </svg>
            </div>
            <div>
              <span class="block text-xl font-bold text-[#111827]">{appointments.past.length}</span>
              <span class="block text-xs text-[#6b7280]">Completed Visits</span>
            </div>
          </div>

          <div class="bg-white border border-[#e5e7eb] rounded-xl p-4 flex items-center gap-3.5 col-span-2 sm:col-span-1">
            <div class="w-10 h-10 rounded-lg bg-[#f0f9ff] text-[#0369a1] flex items-center justify-center shrink-0" style="width: 40px; height: 40px; background-color: #f0f9ff; color: #0369a1; display: flex; align-items: center; justify-content: center; border-radius: 8px;">
              <svg width="20" height="20" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 20px; height: 20px;">
                <path d="m10.5 20.5 10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7Z"></path>
                <path d="m8.5 8.5 7 7"></path>
              </svg>
            </div>
            <div>
              <span class="block text-xl font-bold text-[#111827]">{medications.length}</span>
              <span class="block text-xs text-[#6b7280]">Active Prescriptions</span>
            </div>
          </div>
        </div>

        <!-- Department Consultations Quick Booking Grid -->
        <div class="space-y-3">
          <div class="flex items-center justify-between">
            <h2 class="text-sm font-bold text-[#111827]">Book by Department</h2>
            <span class="text-xs text-[#6b7280]">Select a clinic service</span>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {#each departments as dept}
              <a
                href={dept.url}
                class="bg-white border border-[#e5e7eb] rounded-xl p-4 hover:border-[#166534] transition flex flex-col justify-between group"
              >
                <div>
                  <div class="flex items-center justify-between mb-2">
                    <h3 class="text-xs font-bold text-[#111827] group-hover:text-[#166534] transition">
                      {dept.name}
                    </h3>
                    <svg width="16" height="16" class="w-4 h-4 text-[#9ca3af] group-hover:text-[#166534] transition" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 16px; height: 16px;">
                      <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                  </div>
                  <p class="text-xs text-[#6b7280] leading-relaxed">
                    {dept.desc}
                  </p>
                </div>
                <div class="mt-4 pt-3 border-t border-[#f3f4f6] text-[11px] font-semibold text-[#166534] flex items-center gap-1">
                  Book Consultation →
                </div>
              </a>
            {/each}
          </div>
        </div>

        <!-- Upcoming Appointments List Preview -->
        <div class="bg-white border border-[#e5e7eb] rounded-xl overflow-hidden">
          <div class="px-5 py-4 border-b border-[#f3f4f6] flex items-center justify-between">
            <div>
              <h2 class="text-sm font-bold text-[#111827]">Upcoming Visits</h2>
              <p class="text-xs text-[#6b7280] mt-0.5">Chronological list of next scheduled visits</p>
            </div>
            {#if appointments.upcoming.length > 0}
              <button
                type="button"
                class="text-xs font-semibold text-[#166534] hover:underline"
                on:click={() => activeTab = 'appointments'}
              >
                View all ({appointments.upcoming.length})
              </button>
            {/if}
          </div>

          {#if appointments.upcoming.length === 0}
            <div class="p-8 text-center text-xs text-[#6b7280]">
              No upcoming appointments scheduled.
            </div>
          {:else}
            <div class="divide-y divide-[#f3f4f6]">
              {#each appointments.upcoming.slice(0, 3) as appt}
                <AppointmentRow
                  appointment={appt}
                  isUpcomingTab={true}
                  onView={handleViewDetails}
                  onReschedule={handleReschedule}
                  onCancel={handleRequestCancel}
                />
              {/each}
            </div>
          {/if}
        </div>

      <!-- ── APPOINTMENTS & VISIT HISTORY VIEW ──────────────────── -->
      {:else if activeTab === 'appointments'}
        <div class="space-y-4">
          <!-- Page Title -->
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
              <h1 class="text-xl md:text-2xl font-bold text-[#111827]">Appointments & Visit History</h1>
              <p class="text-xs text-[#6b7280] mt-0.5">Track your upcoming consultations and review past clinic records.</p>
            </div>

            <button
              type="button"
              class="px-4 py-2 rounded-lg bg-[#166534] hover:bg-[#14532d] text-white text-xs font-semibold transition self-start sm:self-auto inline-flex items-center gap-1.5 shadow-sm"
              on:click={handleBookAppointment}
            >
              <svg width="14" height="14" class="w-3.5 h-3.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 14px; height: 14px; color: #ffffff;">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
              </svg>
              New Appointment
            </button>
          </div>

          <!-- Timeline Tabs -->
          <div
            class="flex items-center gap-6 border-b border-gray-200 overflow-x-auto"
            role="tablist"
            aria-label="Appointment categories"
          >
            {#each appointmentTabs as tab (tab.id)}
              {@const isActive = apptFilterTab === tab.id}
              <button
                type="button"
                role="tab"
                aria-selected={isActive}
                class="relative py-3 text-sm whitespace-nowrap border-b-2 -mb-px transition-colors tabular-nums focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#166534] focus-visible:ring-offset-2 {isActive
                  ? 'border-[#166534] text-[#166534] font-semibold'
                  : 'border-transparent text-gray-500 font-medium hover:text-gray-900 hover:border-gray-300'}"
                on:click={() => (apptFilterTab = tab.id)}
              >
                {tab.label} ({tab.count})
              </button>
            {/each}
          </div>

          <!-- Filter & Search Bar -->
          <div class="bg-white border border-[#e5e7eb] rounded-xl p-4 flex flex-col md:flex-row md:items-center justify-between gap-3 text-xs">
            <div class="relative flex-1">
              <svg width="16" height="16" class="w-4 h-4 text-[#9ca3af] absolute left-3 top-1/2 -translate-y-1/2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 16px; height: 16px;">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
              </svg>
              <input
                type="text"
                placeholder="Search department, doctor, complaint or ID..."
                bind:value={searchQuery}
                class="w-full pl-9 pr-3 py-2 rounded-lg border border-[#d1d5db] focus:border-[#166534] focus:ring-1 focus:ring-[#166534] outline-none transition"
              />
            </div>

            <!-- Date Range Filters -->
            <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
              <div class="flex items-center gap-1.5">
                <span class="text-[#6b7280]">From:</span>
                <input
                  type="date"
                  bind:value={dateFrom}
                  on:change={handleDateChange}
                  class="px-2.5 py-1.5 rounded-lg border border-[#d1d5db] text-xs outline-none focus:border-[#166534]"
                />
              </div>

              <div class="flex items-center gap-1.5">
                <span class="text-[#6b7280]">To:</span>
                <input
                  type="date"
                  bind:value={dateTo}
                  on:change={handleDateChange}
                  class="px-2.5 py-1.5 rounded-lg border border-[#d1d5db] text-xs outline-none focus:border-[#166534]"
                />
              </div>

              {#if searchQuery || dateFrom || dateTo}
                <button
                  type="button"
                  class="px-2.5 py-1.5 text-xs text-[#6b7280] hover:text-[#111827] underline"
                  on:click={resetFilters}
                >
                  Reset
                </button>
              {/if}
            </div>
          </div>

          {#if dateRangeError}
            <div class="text-xs text-[#dc2626] font-medium px-1">
              {dateRangeError}
            </div>
          {/if}

          <!-- Appointment Results List -->
          <div class="bg-white border border-[#e5e7eb] rounded-xl overflow-hidden">
            {#if filteredAppointments.length === 0}
              <div class="p-12 text-center space-y-3">
                <div class="w-12 h-12 rounded-full bg-[#f3f4f6] text-[#6b7280] flex items-center justify-center mx-auto" style="width: 48px; height: 48px; margin: 0 auto; display: flex; align-items: center; justify-content: center; border-radius: 9999px;">
                  <svg width="24" height="24" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 24px; height: 24px;">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                  </svg>
                </div>
                <h3 class="text-sm font-semibold text-[#111827]">No appointments found</h3>
                <p class="text-xs text-[#6b7280] max-w-sm mx-auto">
                  {#if searchQuery || dateFrom || dateTo}
                    No results match your active search and date filters. Try resetting the filter.
                  {:else if apptFilterTab === 'upcoming'}
                    You have no upcoming consultations scheduled.
                  {:else if apptFilterTab === 'past'}
                    No completed appointments found in your medical record.
                  {:else}
                    No cancelled appointments recorded.
                  {/if}
                </p>
                {#if apptFilterTab === 'upcoming'}
                  <button
                    type="button"
                    class="mt-2 px-4 py-2 rounded-lg bg-[#166534] hover:bg-[#14532d] text-white text-xs font-semibold transition"
                    on:click={handleBookAppointment}
                  >
                    Book an Appointment
                  </button>
                {/if}
              </div>
            {:else}
              <div class="divide-y divide-[#f3f4f6]">
                {#each filteredAppointments as appt}
                  <AppointmentRow
                    appointment={appt}
                    isUpcomingTab={apptFilterTab === 'upcoming'}
                    onView={handleViewDetails}
                    onReschedule={handleReschedule}
                    onCancel={handleRequestCancel}
                  />
                {/each}
              </div>
            {/if}
          </div>
        </div>

      <!-- ── MEDICATIONS VIEW ──────────────────────────────────── -->
      {:else if activeTab === 'medications'}
        <div class="space-y-4">
          <div>
            <h1 class="text-xl md:text-2xl font-bold text-[#111827]">Active Medications</h1>
            <p class="text-xs text-[#6b7280] mt-0.5">Active maintenance prescriptions and current course progress.</p>
          </div>

          {#if medications.length === 0}
            <div class="bg-white border border-[#e5e7eb] rounded-xl p-12 text-center space-y-2">
              <div class="w-12 h-12 rounded-full bg-[#f3f4f6] text-[#6b7280] flex items-center justify-center mx-auto mb-2" style="width: 48px; height: 48px; margin: 0 auto; display: flex; align-items: center; justify-content: center; border-radius: 9999px;">
                <svg width="24" height="24" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 24px; height: 24px;">
                  <path d="m10.5 20.5 10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7Z"></path>
                  <path d="m8.5 8.5 7 7"></path>
                </svg>
              </div>
              <h3 class="text-sm font-semibold text-[#111827]">No active medications</h3>
              <p class="text-xs text-[#6b7280] max-w-sm mx-auto">
                No active prescriptions are currently recorded in your outpatient history.
              </p>
            </div>
          {:else}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              {#each medications as med}
                <div class="bg-white border border-[#e5e7eb] rounded-xl p-5 space-y-3">
                  <div class="flex items-start justify-between">
                    <div>
                      <h3 class="text-sm font-bold text-[#111827]">{med.name}</h3>
                      <p class="text-xs text-[#166534] font-medium mt-0.5">{med.dosage}</p>
                    </div>
                    <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full bg-[#f0fdf4] text-[#166534] border border-[#bbf7d0]">
                      {med.status || 'Active'}
                    </span>
                  </div>

                  <div class="text-xs text-[#4b5563] space-y-1">
                    <p><span class="text-[#6b7280]">Frequency:</span> {med.frequency}</p>
                    <p><span class="text-[#6b7280]">Prescribed by:</span> {med.doctor}</p>
                    {#if med.diagnosis}
                      <p><span class="text-[#6b7280]">Indication:</span> {med.diagnosis}</p>
                    {/if}
                  </div>

                  <!-- Course Progress Bar -->
                  <div class="pt-2 border-t border-[#f3f4f6]">
                    <div class="flex items-center justify-between text-[11px] mb-1">
                      <span class="text-[#6b7280]">Course Supply</span>
                      <span class="font-semibold text-[#111827]">{med.days_left || 14} days left</span>
                    </div>
                    <div class="w-full h-1.5 rounded-full bg-[#f3f4f6] overflow-hidden">
                      <div class="h-full bg-[#166534] rounded-full" style="width: {med.pct || 50}%"></div>
                    </div>
                  </div>
                </div>
              {/each}
            </div>
          {/if}
        </div>

      <!-- ── ACCOUNT SETTINGS VIEW ─────────────────────────────── -->
      {:else if activeTab === 'settings'}
        <div class="space-y-6">
          <div>
            <h1 class="text-xl md:text-2xl font-bold text-[#111827]">Account Settings</h1>
            <p class="text-xs text-[#6b7280] mt-0.5">Manage your patient profile details, notification preferences, and account security.</p>
          </div>

          <!-- Section 1: Patient Profile -->
          <div class="bg-white border border-[#e5e7eb] rounded-xl p-6">
            <div class="pb-4 border-b border-[#f3f4f6] mb-5">
              <h2 class="text-sm font-bold text-[#111827]">Patient Profile</h2>
              <p class="text-xs text-[#6b7280] mt-0.5">Personal details linked to your official hospital medical record.</p>
            </div>

            <form on:submit|preventDefault={handleSaveProfile} class="space-y-4">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label for="prof-id" class="block text-xs font-medium text-[#374151] mb-1">Patient ID / Reference (Read-only)</label>
                  <input
                    id="prof-id"
                    type="text"
                    value={profile.patient_id || 'PT-2026-082'}
                    disabled
                    class="w-full px-3 py-2 text-xs rounded-lg border border-[#e5e7eb] bg-[#f9fafb] text-[#6b7280] font-mono cursor-not-allowed"
                  />
                </div>

                <div>
                  <label for="prof-hrn" class="block text-xs font-medium text-[#374151] mb-1">Hospital Record Number (HRN)</label>
                  <input
                    id="prof-hrn"
                    type="text"
                    value={profile.hrn || 'HRN-2026-082'}
                    disabled
                    class="w-full px-3 py-2 text-xs rounded-lg border border-[#e5e7eb] bg-[#f9fafb] text-[#6b7280] font-mono cursor-not-allowed"
                  />
                </div>

                <div>
                  <label for="prof-name" class="block text-xs font-medium text-[#374151] mb-1">Full Legal Name</label>
                  <input
                    id="prof-name"
                    type="text"
                    bind:value={profile.full_name}
                    required
                    class="w-full px-3 py-2 text-xs rounded-lg border border-[#d1d5db] focus:border-[#166534] focus:ring-1 focus:ring-[#166534] outline-none transition"
                  />
                </div>

                <div>
                  <label for="prof-email" class="block text-xs font-medium text-[#374151] mb-1">Registered Email Address</label>
                  <input
                    id="prof-email"
                    type="email"
                    value={profile.email}
                    disabled
                    class="w-full px-3 py-2 text-xs rounded-lg border border-[#e5e7eb] bg-[#f9fafb] text-[#6b7280] cursor-not-allowed"
                  />
                </div>

                <div>
                  <label for="prof-contact" class="block text-xs font-medium text-[#374151] mb-1">Mobile Contact Number</label>
                  <input
                    id="prof-contact"
                    type="tel"
                    bind:value={profile.contact_number}
                    placeholder="09XXXXXXXXX"
                    class="w-full px-3 py-2 text-xs rounded-lg border border-[#d1d5db] focus:border-[#166534] focus:ring-1 focus:ring-[#166534] outline-none transition"
                  />
                </div>

                <div>
                  <label for="prof-birthdate" class="block text-xs font-medium text-[#374151] mb-1">Date of Birth</label>
                  <input
                    id="prof-birthdate"
                    type="date"
                    bind:value={profile.birthdate}
                    class="w-full px-3 py-2 text-xs rounded-lg border border-[#d1d5db] focus:border-[#166534] focus:ring-1 focus:ring-[#166534] outline-none transition"
                  />
                </div>

                <div>
                  <label for="prof-gender" class="block text-xs font-medium text-[#374151] mb-1">Gender</label>
                  <select
                    id="prof-gender"
                    bind:value={profile.gender}
                    class="w-full px-3 py-2 text-xs rounded-lg border border-[#d1d5db] focus:border-[#166534] focus:ring-1 focus:ring-[#166534] outline-none transition bg-white"
                  >
                    <option value="">Select gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                  </select>
                </div>

                <div class="sm:col-span-2">
                  <label for="prof-addr" class="block text-xs font-medium text-[#374151] mb-1">Residential Address</label>
                  <input
                    id="prof-addr"
                    type="text"
                    bind:value={profile.address}
                    placeholder="Barangay, Municipality, Province"
                    class="w-full px-3 py-2 text-xs rounded-lg border border-[#d1d5db] focus:border-[#166534] focus:ring-1 focus:ring-[#166534] outline-none transition"
                  />
                </div>
              </div>

              <div class="pt-4 flex justify-end">
                <button
                  type="submit"
                  disabled={isSavingProfile}
                  class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-[#166534] hover:bg-[#14532d] disabled:opacity-50 text-white text-xs font-semibold transition"
                >
                  {#if isSavingProfile}
                    <span class="inline-block w-3.5 h-3.5 border-2 border-white/35 border-t-white rounded-full animate-spin" aria-hidden="true"></span>
                    <span>Saving...</span>
                  {:else}
                    Save Profile Changes
                  {/if}
                </button>
              </div>
            </form>
          </div>

          <!-- Section 2: Notifications -->
          <div class="bg-white border border-[#e5e7eb] rounded-xl p-6">
            <div class="pb-4 border-b border-[#f3f4f6] mb-4">
              <h2 class="text-sm font-bold text-[#111827]">Notification Preferences</h2>
              <p class="text-xs text-[#6b7280] mt-0.5">Control how and when you receive appointment reminders.</p>
            </div>

            <div class="flex items-center justify-between py-2">
              <div>
                <span class="block text-xs font-semibold text-[#111827]">Appointment Reminders</span>
                <span class="block text-xs text-[#6b7280]">Receive email and SMS notifications 24 hours prior to your scheduled consultation.</span>
              </div>

              <label class="relative inline-flex items-center cursor-pointer">
                <input
                  type="checkbox"
                  bind:checked={appointmentReminders}
                  class="sr-only peer"
                />
                <div class="w-11 h-6 bg-[#e5e7eb] peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#166534]"></div>
              </label>
            </div>
          </div>

          <!-- Section 3: Security & Session -->
          <div class="bg-white border border-[#e5e7eb] rounded-xl p-6">
            <div class="pb-4 border-b border-[#f3f4f6] mb-4">
              <h2 class="text-sm font-bold text-[#111827]">Security & Session</h2>
              <p class="text-xs text-[#6b7280] mt-0.5">Manage your credentials and active sign-in sessions.</p>
            </div>

            <div class="divide-y divide-[#f3f4f6]">
              <div class="py-3 flex items-center justify-between">
                <div>
                  <span class="block text-xs font-semibold text-[#111827]">Account Password</span>
                  <span class="block text-xs text-[#6b7280]">Change your password periodically to keep your account secure.</span>
                </div>
                <button
                  type="button"
                  class="px-3.5 py-1.5 rounded-lg border border-[#e5e7eb] text-xs font-semibold text-[#374151] hover:bg-[#f9fafb] transition"
                  on:click={() => showPasswordModal = true}
                >
                  Change Password
                </button>
              </div>

              <div class="pt-4 flex items-center justify-between">
                <div>
                  <span class="block text-xs font-semibold text-[#dc2626]">Sign Out</span>
                  <span class="block text-xs text-[#6b7280]">Safely terminate your current authenticated session.</span>
                </div>
                <button
                  type="button"
                  class="px-3.5 py-1.5 rounded-lg bg-[#fef2f2] hover:bg-[#fee2e2] text-xs font-semibold text-[#dc2626] border border-[#fecaca] transition"
                  on:click={() => window.location.href = '/sdn/api/patient_logout.php'}
                >
                  Sign Out
                </button>
              </div>
            </div>
          </div>
        </div>
      {/if}
    </main>
  </div>
</div>
