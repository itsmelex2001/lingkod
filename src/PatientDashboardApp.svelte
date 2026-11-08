<script>
  import { onMount } from 'svelte';

  export let patientName = 'Maria Santos';
  export let patientId = 'PT-2026-082';

  let activeTab = 'dashboard';
  let apptFilter = 'upcoming';
  let showNotifs = false;

  // Settings State
  let userPhone = '0917 123 4567';
  let userLanguage = (typeof localStorage !== 'undefined' && localStorage.getItem('sdn_patient_lang')) || 'en';
  let saveStatus = 'Save changes';
  let appointmentReminders = true;

  const i18n = {
    en: {
      dashboard: "Dashboard",
      appointments: "Appointments",
      medications: "Medications",
      activeMeds: "Active medications",
      medsSub: "Active maintenance prescriptions and current supply",
      supplyRemaining: "days supply remaining",
      settings: "Settings",
      greeting: "Good morning",
      greetingSub: "Appointments and scheduling",
      nextAppt: "Next appointment",
      scheduledVisits: "Scheduled visits",
      activeBookings: "Active bookings",
      bookAppointment: "Book appointment",
      viewSchedule: "View schedule",
      bookDeptSub: "Schedule an in-person or telemedicine visit and choose your department",
      upcomingAppts: "Upcoming appointments",
      viewAll: "View all",
      quickActions: "Quick actions",
      bookVisitBtn: "Book visit",
      viewSchedulesBtn: "View doctor schedules",
      upcoming: "Upcoming",
      past: "Past",
      cancelled: "Cancelled",
      reschedule: "Reschedule",
      profile: "Profile",
      fullName: "Full name",
      email: "Email",
      phone: "Phone",
      language: "Language",
      saveChanges: "Save changes",
      notifications: "Notifications",
      apptReminders: "Appointment reminders",
      apptRemindersSub: "Get a reminder the day before your visit",
      security: "Security",
      password: "Password",
      passwordSub: "Last changed 3 months ago",
      change: "Change",
      twoFactor: "Two-factor authentication",
      twoFactorSub: "Add a second step when you sign in",
      enable: "Enable",
      signOut: "Sign out",
      signOutSub: "End your active patient session"
    },
    tl: {
      dashboard: "Dashboard",
      appointments: "Mga Appointment",
      medications: "Mga Gamot",
      activeMeds: "Mga aktibong gamot",
      medsSub: "Mga aktibong reseta at kasalukuyang supply",
      supplyRemaining: "araw na natitirang gamot",
      settings: "Mga Setting",
      greeting: "Magandang araw",
      greetingSub: "Mga appointment at pag-iskedyul",
      nextAppt: "Susunod na appointment",
      scheduledVisits: "Nakatakdang pagbisita",
      activeBookings: "Mga aktibong appointment",
      bookAppointment: "Magpa-appointment",
      viewSchedule: "Tingnan ang iskedyul",
      bookDeptSub: "Magpa-iskedyul ng personal o telemedicine na pagbisita at pumili ng departamento",
      upcomingAppts: "Paparating na appointment",
      viewAll: "Tingnan lahat",
      quickActions: "Mabilisang aksyon",
      bookVisitBtn: "Magpa-appointment",
      viewSchedulesBtn: "Tingnan ang mga iskedyul ng doktor",
      upcoming: "Paparating",
      past: "Nakalipas",
      cancelled: "Kinansela",
      reschedule: "I-reschedule",
      profile: "Profile",
      fullName: "Buong pangalan",
      email: "Email",
      phone: "Telepono",
      language: "Wika",
      saveChanges: "I-save ang mga pagbabago",
      notifications: "Mga Abiso",
      apptReminders: "Mga paalala sa appointment",
      apptRemindersSub: "Makatanggap ng paalala isang araw bago ang pagbisita",
      security: "Seguridad",
      password: "Password",
      passwordSub: "Huling pinalitan 3 buwan na ang nakalipas",
      change: "Palitan",
      twoFactor: "Dalawang-hakbang na pag-verify",
      twoFactorSub: "Magdagdag ng karagdagang proteksyon sa pag-login",
      enable: "I-on",
      signOut: "Mag-sign out",
      signOutSub: "Tapusin ang aktibong sesyon ng pasyente"
    }
  };

  $: t = i18n[userLanguage] || i18n.en;

  $: if (typeof localStorage !== 'undefined') {
    localStorage.setItem('sdn_patient_lang', userLanguage);
  }

  let notifs = [
    { id: 1, title: 'Follow-up Visit Confirmed', text: 'Dr. Ana Reyes, Psychiatry on Nov 6 at 10:30 AM is confirmed.', time: '15 mins ago', read: false },
    { id: 2, title: 'Consultation Scheduled', text: 'General Medical visit scheduled for Dec 4 at 2:00 PM.', time: '1 day ago', read: false }
  ];

  $: unreadCount = notifs.filter(n => !n.read).length;

  function toggleNotifs(e) {
    e.stopPropagation();
    showNotifs = !showNotifs;
  }

  function handleNotifClick(notif) {
    notif.read = true;
    notifs = [...notifs];
    showNotifs = false;
    activeTab = 'appointments';
  }

  function markAllRead() {
    notifs = notifs.map(n => ({ ...n, read: true }));
  }

  function clearAllNotifs() {
    notifs = [];
    showNotifs = false;
  }

  function handleSaveSettings() {
    saveStatus = 'Saving...';
    setTimeout(() => {
      saveStatus = 'Saved!';
      setTimeout(() => { saveStatus = 'Save changes'; }, 1800);
    }, 600);
  }

  let data = {
    profile: { full_name: patientName, patient_id: patientId, email: 'maria.santos@gmail.com' },
    stats: { upcoming_count: 3, past_count: 3, cancelled_count: 1 },
    departments: [
      { id: 'general_medicine', name: 'General Medical', action: 'Book a visit' },
      { id: 'neurology', name: 'Neurology', action: 'Book a visit' },
      { id: 'psychiatry', name: 'Psychiatry', action: 'Book a visit' }
    ],
    upcoming: [
      { date: 'Nov 6', title: 'Follow-up visit', detail: 'Psychiatry, Dr. Ana Reyes, 10:30 AM', status: 'Confirmed' },
      { date: 'Nov 12', title: 'Neurology consultation', detail: 'Neurology, Dr. Antonio Ramos, 1:00 PM', status: 'For approval' },
      { date: 'Dec 4', title: 'General check-up', detail: 'General Medical, Dr. Miguel Cruz, 2:00 PM', status: 'Confirmed' }
    ],
    past: [
      { date: 'Oct 15', title: 'Routine examination', detail: 'General Medical, Dr. Miguel Cruz', status: 'Completed' },
      { date: 'Sep 22', title: 'Psychiatric evaluation', detail: 'Psychiatry, Dr. Ana Reyes', status: 'Completed' },
      { date: 'Aug 10', title: 'Neurological assessment', detail: 'Neurology, Dr. Antonio Ramos', status: 'Completed' }
    ],
    cancelled: [
      { date: 'Sep 5', title: 'Follow-up review', detail: 'General Medical, Cancelled by patient', status: 'Cancelled' }
    ],
    medications: [
      { id: 1, name: 'Metformin 500 mg', dosage: '500 mg', frequency: 'Twice daily with meals', doctor: 'Dr. Maria Santos (General Medical)', amount: '36 tablets remaining', days_left: 18, total_days: 30, pct: 60, status: 'Active' },
      { id: 2, name: 'Lisinopril 10 mg', dosage: '10 mg', frequency: 'Once daily in the morning', doctor: 'Dr. Maria Santos (General Medical)', amount: '6 tablets remaining', days_left: 6, total_days: 30, pct: 20, status: 'Active' },
      { id: 3, name: 'Vitamin D3 1000 IU', dosage: '1000 IU', frequency: 'Once daily', doctor: 'Dr. Emilio Cruz (Neurology)', amount: '24 softgels remaining', days_left: 24, total_days: 30, pct: 80, status: 'Active' }
    ]
  };

  onMount(async () => {
    try {
      const res = await fetch('/sdn/api/patient_dashboard_data.php');
      if (res.ok) {
        const json = await res.json();
        if (json.success) {
          if (json.profile?.full_name) data.profile = json.profile;
          if (json.stats) data.stats = json.stats;
          if (json.departments?.length) data.departments = json.departments;
          if (json.upcoming?.length) data.upcoming = json.upcoming;
          if (json.past?.length) data.past = json.past;
          if (json.cancelled?.length) data.cancelled = json.cancelled;
          if (json.medications?.length) data.medications = json.medications;
        }
      }
    } catch (e) {
      // Keep defaults
    }

    const closeHandler = () => { showNotifs = false; };
    window.addEventListener('click', closeHandler);
    return () => window.removeEventListener('click', closeHandler);
  });

  $: firstName = data.profile.full_name ? data.profile.full_name.trim().split(' ')[0] : 'Maria';
  $: initials = data.profile.full_name ? data.profile.full_name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase() : 'MS';

  $: filteredAppointments = apptFilter === 'upcoming' ? data.upcoming : (apptFilter === 'past' ? data.past : data.cancelled);
</script>

<div class="flex min-h-screen bg-[#f1f5f9] text-[#1e293b] font-['Inter',sans-serif]">
  <!-- Fixed Viewport Left Sidebar -->
  <aside class="w-[260px] h-screen sticky top-0 bg-white border-r border-[#cbd5e1] shadow-[2px_0_8px_-2px_rgba(15,23,42,0.05)] flex flex-col justify-between p-6 shrink-0 z-50 overflow-hidden">
    <div>
      <!-- Hospital Brand with Logo -->
      <div class="flex items-center gap-3 pb-6 border-b border-[#eaedf1]">
        <div class="w-14 h-14 flex items-center justify-center shrink-0">
          <img src="/sdn/public/assets/v3_lingkod_logo_green.png" alt="LINGKOD" class="w-full h-full object-contain block" />
        </div>
        <div class="flex flex-col leading-tight min-w-0">
          <span class="text-[10px] text-[#64748b] font-medium leading-tight">Mariveles Mental Wellness and General Hospital</span>
          <span class="text-[15px] font-extrabold text-[#15803d] tracking-wide mt-0.5">LINGKOD</span>
        </div>
      </div>

      <!-- Navigation Links -->
      <nav class="flex flex-col gap-1.5 mt-6">
        <button
          class="flex items-center gap-3.5 px-3.5 py-2.5 rounded-lg text-sm font-medium transition text-left {activeTab === 'dashboard' ? 'bg-[#e8f5e9] text-[#15803d] font-bold' : 'text-[#64748b] hover:bg-[#f1f5f9] hover:text-[#1e293b]'}"
          on:click={() => activeTab = 'dashboard'}
        >
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
          <span>{t.dashboard}</span>
        </button>

        <button
          class="flex items-center gap-3.5 px-3.5 py-2.5 rounded-lg text-sm font-medium transition text-left {activeTab === 'appointments' ? 'bg-[#e8f5e9] text-[#15803d] font-bold' : 'text-[#64748b] hover:bg-[#f1f5f9] hover:text-[#1e293b]'}"
          on:click={() => activeTab = 'appointments'}
        >
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
          <span>{t.appointments}</span>
        </button>

        <button
          class="flex items-center gap-3.5 px-3.5 py-2.5 rounded-lg text-sm font-medium transition text-left {activeTab === 'medications' ? 'bg-[#e8f5e9] text-[#15803d] font-bold' : 'text-[#64748b] hover:bg-[#f1f5f9] hover:text-[#1e293b]'}"
          on:click={() => activeTab = 'medications'}
        >
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m10.5 20.5 10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7Z"></path><path d="m8.5 8.5 7 7"></path></svg>
          <span>{t.medications}</span>
        </button>
      </nav>
    </div>

    <!-- Pinned Bottom Settings Tab -->
    <div class="pt-4 border-t border-[#eaedf1]">
      <button
        class="flex items-center gap-3.5 px-3.5 py-2.5 rounded-lg text-sm font-medium transition text-left w-full {activeTab === 'settings' ? 'bg-[#e8f5e9] text-[#15803d] font-bold' : 'text-[#64748b] hover:bg-[#f1f5f9] hover:text-[#1e293b]'}"
        on:click={() => activeTab = 'settings'}
      >
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
        <span>{t.settings}</span>
      </button>
    </div>
  </aside>

  <!-- Right Main Area -->
  <main class="flex-1 flex flex-col min-w-0 min-h-screen">
    <!-- Top Header -->
    <header class="h-[70px] bg-white border-b border-[#cbd5e1] shadow-sm flex items-center justify-between px-10 shrink-0 sticky top-0 z-40">
      <div class="relative">
        <button
          type="button"
          class="w-10 h-10 rounded-full border border-[#e2e8f0] flex items-center justify-center text-[#64748b] hover:text-[#1e293b] hover:border-[#94a3b8] transition relative"
          on:click={toggleNotifs}
        >
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
          {#if unreadCount > 0}
            <span class="w-2 h-2 rounded-full bg-[#15803d] absolute top-2 right-2 ring-2 ring-white"></span>
          {/if}
        </button>

        {#if showNotifs}
          <div class="absolute left-0 mt-2 w-80 bg-white border border-[#cbd5e1] rounded-xl shadow-lg z-50 overflow-hidden">
            <div class="p-3.5 border-b border-[#eaedf1] flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span class="font-bold text-sm text-[#1e293b]">Notifications</span>
                <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-[#e8f5e9] text-[#15803d]">
                  {unreadCount} new
                </span>
              </div>
              <button class="text-xs font-semibold text-[#15803d] hover:underline" on:click={markAllRead}>
                Mark all as read
              </button>
            </div>

            <div class="max-h-[300px] overflow-y-auto divide-y divide-[#eaedf1]">
              {#if notifs.length === 0}
                <div class="p-6 text-center text-xs text-[#94a3b8]">No notifications</div>
              {:else}
                {#each notifs as n}
                  <div
                    class="p-3.5 flex gap-3 cursor-pointer hover:bg-[#f8fafc] transition text-left {n.read ? 'bg-white' : 'bg-[#fcfefe]'}"
                    on:click={() => handleNotifClick(n)}
                  >
                    <span class="w-2 h-2 rounded-full mt-1.5 shrink-0 {n.read ? 'bg-transparent' : 'bg-[#15803d]'}"></span>
                    <div>
                      <div class="text-xs font-semibold text-[#1e293b] leading-tight">{n.title}</div>
                      <div class="text-[11px] text-[#64748b] mt-0.5 leading-snug">{n.text}</div>
                      <div class="text-[10px] text-[#94a3b8] mt-1">{n.time}</div>
                    </div>
                  </div>
                {/each}
              {/if}
            </div>

            <div class="p-2.5 bg-[#f8fafc] border-t border-[#eaedf1] text-center">
              <button class="text-xs text-[#64748b] hover:text-[#1e293b] font-medium" on:click={clearAllNotifs}>
                Clear all notifications
              </button>
            </div>
          </div>
        {/if}
      </div>

      <div class="w-9 h-9 rounded-full bg-[#e8f5e9] border border-[#c8e6c9] flex items-center justify-center font-bold text-xs text-[#15803d]">
        {initials}
      </div>
    </header>

    <div class="p-10 max-w-[1200px] w-full mx-auto flex flex-col gap-6">

      <!-- ═════════ 1. DASHBOARD VIEW ═════════ -->
      {#if activeTab === 'dashboard'}
        <div class="flex flex-col gap-6">
          <!-- Greeting Header -->
          <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
              <h1 class="text-[26px] font-bold text-[#1e293b]">{t.greeting}, {firstName}</h1>
              <p class="text-sm text-[#64748b] mt-0.5">{t.greetingSub}</p>
            </div>
            <div class="flex items-center gap-3">
              <a
                href="/sdn/pages/schedules.php"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-semibold text-[#1e293b] bg-white border border-[#cbd5e1] hover:bg-[#f8fafc] transition shadow-sm"
              >
                <span>{t.viewSchedule}</span>
              </a>
            </div>
          </div>

          <!-- Metrics Row (Strictly Scheduling & Appointments) -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="bg-white border border-[#e2e8f0] rounded-xl p-5 shadow-sm flex flex-col justify-between min-h-[110px]">
              <span class="text-xs text-[#64748b] font-medium">{t.nextAppt}</span>
              <span class="text-2xl font-bold text-[#15803d] mt-1">Nov 6</span>
              <span class="text-xs text-[#64748b]">10:30 AM, Psychiatry</span>
            </div>

            <div class="bg-white border border-[#e2e8f0] rounded-xl p-5 shadow-sm flex flex-col justify-between min-h-[110px]">
              <span class="text-xs text-[#64748b] font-medium">{t.scheduledVisits}</span>
              <span class="text-2xl font-bold text-[#15803d] mt-1">{data.stats.upcoming_count}</span>
              <span class="text-xs text-[#64748b]">{t.activeBookings}</span>
            </div>
          </div>

          <!-- Book appointment Action Card -->
          <div class="bg-white border border-[#e2e8f0] rounded-xl p-5 shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
              <div class="w-10 h-10 rounded-lg bg-[#e8f5e9] border border-[#c8e6c9] flex items-center justify-center text-[#15803d] shrink-0">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
              </div>
              <div>
                <div class="text-[15px] font-bold text-[#1e293b]">{t.bookAppointment}</div>
                <div class="text-xs text-[#64748b] mt-0.5">{t.bookDeptSub}</div>
              </div>
            </div>
            <a
              href="/sdn/pages/patient-book-appointment.php"
              class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-semibold text-white bg-[#15803d] hover:bg-[#166534] transition shrink-0 shadow-sm"
            >
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
              <span>{t.bookAppointment}</span>
            </a>
          </div>

          <!-- Upcoming Appointments List -->
          <div class="bg-white border border-[#e2e8f0] rounded-xl p-6 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between mb-3">
              <span class="font-bold text-[15px] text-[#1e293b]">{t.upcomingAppts}</span>
              <button class="text-xs font-semibold text-[#15803d] hover:underline" on:click={() => activeTab = 'appointments'}>{t.viewAll}</button>
            </div>
            <div class="flex flex-col divide-y divide-[#eaedf1]">
              {#each data.upcoming as appt}
                <div class="py-3.5 first:pt-0 last:pb-0 flex items-center justify-between cursor-pointer" on:click={() => activeTab = 'appointments'}>
                  <div class="flex items-center gap-4">
                    <div class="text-center w-10">
                      <div class="text-[10px] uppercase font-bold text-[#64748b]">{appt.date.split(' ')[0]}</div>
                      <div class="text-lg font-bold text-[#15803d] leading-none">{appt.date.split(' ')[1] || ''}</div>
                    </div>
                    <div>
                      <div class="text-sm font-semibold text-[#1e293b]">{appt.title}</div>
                      <div class="text-xs text-[#64748b] mt-0.5">{appt.detail}</div>
                    </div>
                  </div>
                  <div class="flex items-center gap-2">
                    <span class="text-xs font-medium px-2.5 py-0.5 rounded-full {appt.status === 'Confirmed' ? 'bg-[#e8f5e9] text-[#15803d]' : 'bg-[#fffbeb] text-[#b45309]'}">{appt.status}</span>
                    <span class="text-base text-[#94a3b8] font-normal leading-none">&rsaquo;</span>
                  </div>
                </div>
              {/each}
            </div>
          </div>

          <!-- Quick Actions -->
          <div class="bg-white border border-[#e2e8f0] rounded-xl p-6 shadow-sm">
            <span class="font-bold text-[15px] text-[#1e293b] block mb-3">{t.quickActions}</span>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <a
                href="/sdn/pages/patient-book-appointment.php"
                class="bg-[#15803d] hover:bg-[#166534] text-white p-3.5 rounded-lg text-xs font-semibold flex items-center justify-center gap-2.5 transition"
              >
                <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line><line x1="12" y1="14" x2="12" y2="18"></line><line x1="10" y1="16" x2="14" y2="16"></line></svg>
                <span>{t.bookVisitBtn}</span>
              </a>

              <a
                href="/sdn/pages/schedules.php"
                target="_blank"
                rel="noopener noreferrer"
                class="bg-white border border-[#e2e8f0] hover:border-[#94a3b8] text-[#1e293b] p-3.5 rounded-lg text-xs font-semibold flex items-center justify-center gap-2.5 transition"
              >
                <svg class="w-4 h-4 text-[#64748b] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                <span>{t.viewSchedulesBtn}</span>
              </a>
            </div>
          </div>
        </div>

      <!-- ═════════ 2. APPOINTMENTS VIEW ═════════ -->
      {:else if activeTab === 'appointments'}
        <div class="flex flex-col gap-6">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-[26px] font-bold text-[#1e293b]">Appointments</h1>
              <p class="text-sm text-[#64748b] mt-0.5">Book and manage visits in General Medical, Neurology, and Psychiatry</p>
            </div>
            <a href="/sdn/pages/patient-book-appointment.php" class="text-xs text-[#15803d] font-semibold hover:underline flex items-center gap-1.5">
              <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
              <span>{t.bookAppointment}</span>
            </a>
          </div>

          <!-- Filter Pills with Dynamic Counts -->
          <div class="flex items-center gap-2">
            <button
              class="px-4 py-1.5 rounded-full text-xs font-semibold border transition {apptFilter === 'upcoming' ? 'bg-[#e8f5e9] border-[#c8e6c9] text-[#15803d]' : 'bg-white border-[#e2e8f0] text-[#64748b]'}"
              on:click={() => apptFilter = 'upcoming'}
            >
              Upcoming {data.stats.upcoming_count}
            </button>
            <button
              class="px-4 py-1.5 rounded-full text-xs font-semibold border transition {apptFilter === 'past' ? 'bg-[#e8f5e9] border-[#c8e6c9] text-[#15803d]' : 'bg-white border-[#e2e8f0] text-[#64748b]'}"
              on:click={() => apptFilter = 'past'}
            >
              Past {data.stats.past_count}
            </button>
            <button
              class="px-4 py-1.5 rounded-full text-xs font-semibold border transition {apptFilter === 'cancelled' ? 'bg-[#e8f5e9] border-[#c8e6c9] text-[#15803d]' : 'bg-white border-[#e2e8f0] text-[#64748b]'}"
              on:click={() => apptFilter = 'cancelled'}
            >
              Cancelled {data.stats.cancelled_count}
            </button>
          </div>

          <!-- Appointments List -->
          <div class="bg-white border border-[#e2e8f0] rounded-xl p-6 shadow-sm flex flex-col divide-y divide-[#eaedf1]">
            {#each filteredAppointments as item}
              <div class="py-4 first:pt-0 last:pb-0 flex items-center justify-between">
                <div class="flex items-center gap-5">
                  <div class="text-center w-10">
                    <div class="text-[11px] uppercase font-bold text-[#64748b]">{item.date.split(' ')[0]}</div>
                    <div class="text-xl font-bold text-[#15803d] leading-tight">{item.date.split(' ')[1] || ''}</div>
                  </div>
                  <div>
                    <div class="text-sm font-semibold text-[#1e293b]">{item.title}</div>
                    <div class="text-xs text-[#64748b] mt-0.5">{item.detail}</div>
                    <div class="flex items-center gap-1.5 text-xs mt-1.5 font-medium">
                      <span class="w-2 h-2 rounded-full {item.status === 'Confirmed' ? 'bg-[#22c55e]' : 'bg-[#f59e0b]'}"></span>
                      <span>{item.status}</span>
                    </div>
                  </div>
                </div>
                <div class="flex items-center gap-3">
                  <button class="text-xs text-[#15803d] hover:underline font-medium" on:click={() => window.location.href = '/sdn/pages/patient-book-appointment.php'}>
                    Reschedule
                  </button>
                  <span class="text-base text-[#94a3b8] font-normal leading-none">&rsaquo;</span>
                </div>
              </div>
            {/each}
          </div>
        </div>

      <!-- ═════════ 3. MEDICATIONS VIEW (AMOUNT ONLY, NO REFILL) ═════════ -->
      {:else if activeTab === 'medications'}
        <div class="flex flex-col gap-6">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-[26px] font-bold text-[#1e293b]">{t.medications}</h1>
              <p class="text-sm text-[#64748b] mt-0.5">{t.medsSub}</p>
            </div>
          </div>

          <div class="bg-white border border-[#e2e8f0] rounded-xl p-6 shadow-sm flex flex-col gap-4">
            <div class="flex items-center justify-between pb-3 border-b border-[#eaedf1]">
              <span class="font-bold text-[15px] text-[#1e293b]">{t.activeMeds}</span>
              <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-[#e8f5e9] text-[#15803d]">
                {data.medications.length} active
              </span>
            </div>

            <div class="divide-y divide-[#eaedf1]">
              {#each data.medications as med}
                <div class="py-4 first:pt-0 last:pb-0 flex items-start justify-between gap-4">
                  <div class="flex items-start gap-4 flex-1">
                    <div class="w-10 h-10 rounded-lg bg-[#f0fdf4] border border-[#bbf7d0] text-[#15803d] flex items-center justify-center shrink-0 mt-0.5">
                      <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m10.5 20.5 10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7Z"></path><path d="m8.5 8.5 7 7"></path></svg>
                    </div>
                    <div class="flex-1">
                      <div class="flex items-center justify-between gap-3">
                        <span class="text-sm font-bold text-[#1e293b]">{med.name}</span>
                        <span class="text-xs font-bold text-[#15803d] bg-[#dcfce7] px-2.5 py-0.5 rounded-full">{med.amount}</span>
                      </div>
                      <div class="text-xs text-[#64748b] mt-0.5">{med.frequency} &bull; {med.doctor}</div>
                      <div class="mt-2.5 flex items-center gap-3">
                        <div class="flex-1 h-1.5 bg-[#e2e8f0] rounded-full overflow-hidden">
                          <div class="h-full rounded-full transition-all {med.days_left <= 7 ? 'bg-[#f59e0b]' : 'bg-[#15803d]'}" style="width: {Math.min(100, med.pct || 50)}%"></div>
                        </div>
                        <span class="text-[11px] text-[#64748b] whitespace-nowrap">{med.days_left} {t.supplyRemaining}</span>
                      </div>
                    </div>
                  </div>
                </div>
              {/each}
            </div>
          </div>
        </div>

      <!-- ═════════ 4. SETTINGS VIEW ═════════ -->
      {:else if activeTab === 'settings'}
        <div class="flex flex-col gap-6">
          <div>
            <h1 class="text-[26px] font-bold text-[#1e293b]">{t.settings}</h1>
            <p class="text-sm text-[#64748b] mt-0.5">Manage your account and preferences</p>
          </div>

          <!-- Profile Card -->
          <div class="bg-white border border-[#e2e8f0] rounded-xl p-6 shadow-sm flex flex-col gap-5">
            <span class="font-bold text-[15px] text-[#1e293b]">{t.profile}</span>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <div class="flex flex-col gap-1.5">
                <label for="pFullName" class="text-xs font-medium text-[#64748b]">{t.fullName}</label>
                <input
                  id="pFullName"
                  type="text"
                  bind:value={data.profile.full_name}
                  class="w-full bg-[#f8fafc] border border-[#cbd5e1] text-[#1e293b] rounded-lg px-3.5 py-2.5 text-sm outline-none focus:border-[#15803d] focus:bg-white focus:ring-2 focus:ring-[#15803d]/15 transition"
                />
              </div>

              <div class="flex flex-col gap-1.5">
                <label for="pEmail" class="text-xs font-medium text-[#64748b]">{t.email}</label>
                <input
                  id="pEmail"
                  type="email"
                  bind:value={data.profile.email}
                  class="w-full bg-[#f8fafc] border border-[#cbd5e1] text-[#1e293b] rounded-lg px-3.5 py-2.5 text-sm outline-none focus:border-[#15803d] focus:bg-white focus:ring-2 focus:ring-[#15803d]/15 transition"
                />
              </div>

              <div class="flex flex-col gap-1.5">
                <label for="pPhone" class="text-xs font-medium text-[#64748b]">{t.phone}</label>
                <input
                  id="pPhone"
                  type="text"
                  bind:value={userPhone}
                  class="w-full bg-[#f8fafc] border border-[#cbd5e1] text-[#1e293b] rounded-lg px-3.5 py-2.5 text-sm outline-none focus:border-[#15803d] focus:bg-white focus:ring-2 focus:ring-[#15803d]/15 transition"
                />
              </div>

              <div class="flex flex-col gap-1.5">
                <label for="pLang" class="text-xs font-medium text-[#64748b]">{t.language}</label>
                <select
                  id="pLang"
                  bind:value={userLanguage}
                  class="w-full bg-[#f8fafc] border border-[#cbd5e1] text-[#1e293b] rounded-lg px-3.5 py-2.5 text-sm outline-none cursor-pointer focus:border-[#15803d] focus:bg-white focus:ring-2 focus:ring-[#15803d]/15 transition"
                >
                  <option value="en">English</option>
                  <option value="tl">Filipino (Tagalog)</option>
                </select>
              </div>
            </div>

            <div>
              <button
                type="button"
                class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg text-sm font-semibold text-white bg-[#15803d] hover:bg-[#166534] active:scale-[0.99] transition shadow-sm"
                on:click={handleSaveSettings}
              >
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                <span>{saveStatus}</span>
              </button>
            </div>
          </div>

          <!-- Notifications Card (Strictly Appointment Reminders) -->
          <div class="bg-white border border-[#e2e8f0] rounded-xl p-6 shadow-sm flex flex-col gap-4">
            <span class="font-bold text-[15px] text-[#1e293b]">{t.notifications}</span>
            <div>
              <div class="py-2 flex items-center justify-between">
                <div>
                  <div class="text-sm font-semibold text-[#1e293b]">{t.apptReminders}</div>
                  <div class="text-xs text-[#64748b] mt-0.5">{t.apptRemindersSub}</div>
                </div>
                <button
                  type="button"
                  class="w-11 h-6 rounded-full transition relative shrink-0 {appointmentReminders ? 'bg-[#15803d]' : 'bg-[#cbd5e1]'}"
                  on:click={() => appointmentReminders = !appointmentReminders}
                >
                  <span class="w-4 h-4 rounded-full bg-white absolute top-1 transition shadow-sm {appointmentReminders ? 'right-1' : 'left-1'}"></span>
                </button>
              </div>
            </div>
          </div>

          <!-- Security Card -->
          <div class="bg-white border border-[#e2e8f0] rounded-xl p-6 shadow-sm flex flex-col gap-4">
            <span class="font-bold text-[15px] text-[#1e293b]">{t.security}</span>
            <div class="divide-y divide-[#eaedf1]">
              <div class="py-3 first:pt-0 flex items-center justify-between">
                <div>
                  <div class="text-sm font-semibold text-[#1e293b]">{t.password}</div>
                  <div class="text-xs text-[#64748b] mt-0.5">{t.passwordSub}</div>
                </div>
                <button class="text-xs font-medium text-[#94a3b8] hover:text-[#1e293b]" on:click={() => alert('Password reset email sent.')}>
                  {t.change}
                </button>
              </div>

              <div class="py-3 flex items-center justify-between">
                <div>
                  <div class="text-sm font-semibold text-[#1e293b]">{t.twoFactor}</div>
                  <div class="text-xs text-[#64748b] mt-0.5">{t.twoFactorSub}</div>
                </div>
                <button class="text-xs font-medium text-[#94a3b8] hover:text-[#1e293b]" on:click={() => alert('Two-factor setup is enabled.')}>
                  {t.enable}
                </button>
              </div>

              <div class="pt-3 flex items-center justify-between">
                <div>
                  <div class="text-sm font-semibold text-[#dc2626]">{t.signOut}</div>
                  <div class="text-xs text-[#64748b] mt-0.5">{t.signOutSub}</div>
                </div>
                <a href="/sdn/api/patient_logout.php" class="text-xs font-semibold text-[#dc2626] hover:underline">
                  {t.signOut}
                </a>
              </div>
            </div>
          </div>
        </div>
      {/if}

    </div>
  </main>
</div>
