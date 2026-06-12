<script>
  import { onMount } from 'svelte';

  // Live Clock State
  let clockTime = '--:-- --';
  let clockDate = '--';

  onMount(() => {
    const updateTime = () => {
      const now = new Date();
      clockTime = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
      clockDate = now.toLocaleDateString('en-US', { weekday: 'long', month: 'short', day: 'numeric', year: 'numeric' });
    };
    updateTime();
    const interval = setInterval(updateTime, 1000);
    return () => clearInterval(interval);
  });

  // Tab State
  let activeTab = 'psychiatry';

  const tabs = [
    { id: 'psychiatry', label: 'OPU Psychiatry' },
    { id: 'neurology', label: 'Neurology' },
    { id: 'general', label: 'General Management' }
  ];

  // Doctors Data
  const doctors = {
    psychiatry: [
      {
        initials: 'AM', name: 'Dr. Alan Magno', spec: 'Adult Psychiatry', avatarClass: 'av-a',
        time: '08:00 AM – 12:00 PM',
        slotsFilled: 9, slotsTotal: 20, fillClass: 'fill-green',
        status: 'Available', badgeClass: 'badge-available',
        actionLink: 'regis-psychiatry.php?source=schedules', actionLabel: 'Book Now', actionDisabled: false
      },
      {
        initials: 'SR', name: 'Dr. Sarah Reyes', spec: 'Child Psychiatry', avatarClass: 'av-b',
        time: '01:00 PM – 05:00 PM',
        slotsFilled: 20, slotsTotal: 20, fillClass: 'fill-red',
        status: 'Fully Booked', badgeClass: 'badge-booked',
        actionLink: '#', actionLabel: 'Full', actionDisabled: true
      }
    ],
    neurology: [
      {
        initials: 'EC', name: 'Dr. Emilio Cruz', spec: 'Clinical Neurology', avatarClass: 'av-c',
        time: '09:00 AM – 03:00 PM',
        slotsFilled: 13, slotsTotal: 20, fillClass: 'fill-amber',
        status: 'Available', badgeClass: 'badge-available',
        actionLink: 'regis-neurology.php?source=schedules', actionLabel: 'Book Now', actionDisabled: false
      }
    ],
    general: [
      {
        initials: 'MS', name: 'Dr. Maria Santos', spec: 'Internal Medicine', avatarClass: 'av-d',
        time: '08:00 AM – 04:00 PM',
        slotsFilled: 6, slotsTotal: 20, fillClass: 'fill-green',
        status: 'Available', badgeClass: 'badge-available',
        actionLink: 'regis-general.php?source=schedules', actionLabel: 'Book Now', actionDisabled: false
      },
      {
        initials: 'JD', name: 'Dr. Juan Dela Cruz', spec: 'Family Medicine', avatarClass: 'av-e',
        time: 'Not scheduled', isMutedTime: true,
        slotsFilled: 0, slotsTotal: 0, isMutedSlots: true,
        status: 'On Leave', badgeClass: 'badge-leave',
        actionLink: '#', actionLabel: 'Unavailable', actionDisabled: true
      }
    ]
  };

  // Stats
  let stats = { total: 5, available: 3, fullyBooked: 1, onLeave: 1 };
</script>

<main class="sched-page">
  <a href="/sdn/index.php" class="btn-back">
    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
  </a>

  <!-- Premium Header -->
  <div class="sched-header">
    <div>
      <h1 class="sched-title">Doctor Availability</h1>
      <p class="sched-subtitle">Real-time schedules for our medical professionals across all departments.</p>
    </div>
    <div class="live-clock-block">
      <span class="clock-time">{clockTime}</span>
      <span class="clock-date">{clockDate}</span>
      <span class="live-label">
        <span class="live-dot"></span> Live
      </span>
    </div>
  </div>

  <!-- Summary Cards -->
  <div class="summary-grid">
    <div class="summary-card">
      <div class="summary-icon icon-green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
      <div><p class="summary-label">Total Doctors</p><p class="summary-value">{stats.total}</p></div>
    </div>
    <div class="summary-card">
      <div class="summary-icon icon-green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></div>
      <div><p class="summary-label">Available</p><p class="summary-value">{stats.available}</p></div>
    </div>
    <div class="summary-card">
      <div class="summary-icon icon-amber"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
      <div><p class="summary-label">Fully Booked</p><p class="summary-value">{stats.fullyBooked}</p></div>
    </div>
    <div class="summary-card">
      <div class="summary-icon icon-red"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div>
      <div><p class="summary-label">On Leave</p><p class="summary-value">{stats.onLeave}</p></div>
    </div>
  </div>

  <!-- Tabs -->
  <div class="tabs-row">
    <div class="tabs-group">
      {#each tabs as tab}
        <button 
          class="tab-btn {activeTab === tab.id ? 'active' : ''}" 
          on:click={() => activeTab = tab.id}
        >
          {tab.label}
        </button>
      {/each}
    </div>
    <span class="last-updated">Updated: Just now</span>
  </div>

  <!-- Tab Content -->
  <div class="tab-content active">
    <div class="table-card">
      <table class="data-table">
        <thead>
          <tr>
            <th>Doctor</th><th>Specialization</th><th>Schedule (Today)</th><th>Slots</th><th>Status</th><th>Action</th>
          </tr>
        </thead>
        <tbody>
          {#each doctors[activeTab] as doc}
            <tr>
              <td>
                <div class="doctor-cell">
                  <div class="avatar {doc.avatarClass}">{doc.initials}</div>
                  <div><p class="doctor-name">{doc.name}</p><p class="doctor-spec">{doc.spec}</p></div>
                </div>
              </td>
              <td>{doc.spec}</td>
              <td>
                {#if doc.isMutedTime}
                  <div class="time-cell muted">{doc.time}</div>
                {:else}
                  <div class="time-cell">{doc.time}</div>
                {/if}
              </td>
              <td>
                {#if doc.isMutedSlots}
                  <span class="muted">—</span>
                {:else}
                  <div class="slots-bar">
                    <div class="slots-fill {doc.fillClass}" style="width:{(doc.slotsFilled / doc.slotsTotal) * 100}%"></div>
                  </div>
                  <p class="slots-text">{doc.slotsFilled} / {doc.slotsTotal} booked</p>
                {/if}
              </td>
              <td>
                <span class="status-badge {doc.badgeClass}">
                  <span class="status-dot"></span>{doc.status}
                </span>
              </td>
              <td>
                {#if doc.actionDisabled}
                  <button class="btn-action disabled-btn" disabled>{doc.actionLabel}</button>
                {:else}
                  <a href={doc.actionLink} class="btn-action">{doc.actionLabel}</a>
                {/if}
              </td>
            </tr>
          {/each}
        </tbody>
      </table>
    </div>
  </div>
</main>
