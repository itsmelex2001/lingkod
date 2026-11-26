<script>
  import StatusPill from './StatusPill.svelte';

  export let appointment;
  export let isUpcomingTab = false;
  export let onView = (appt) => {};
  export let onReschedule = (appt) => {};

  $: deptDotColor = (() => {
    const dept = (appointment.department || '').toLowerCase();
    if (dept.includes('psych')) return '#7c4dcc';
    if (dept.includes('neuro')) return '#2b7fd4';
    return '#0f9d74';
  })();

  $: isFollowUp = (() => {
    const raw = (appointment.visit_type || '').toLowerCase();
    if (raw) return raw.includes('follow');
    return Boolean(appointment.previous_appointment_date);
  })();

  $: visitTypeLabel = isFollowUp ? 'Follow-up' : 'Initial';
  $: showRescheduledBadge = isUpcomingTab && Boolean(appointment.is_rescheduled || appointment.rescheduled_by_staff);
</script>

<div class="px-5 py-4 border-b border-gray-100 last:border-none flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-gray-50/70 transition-colors">
  <div class="flex items-start sm:items-center gap-4 min-w-0">
    <div class="w-16 shrink-0 text-left tabular-nums">
      <span class="block text-xs font-bold text-gray-900">{appointment.date || 'TBD'}</span>
      <span class="block text-[11px] text-gray-500">{appointment.time || '8:00 AM'}</span>
    </div>

    <div class="hidden sm:block w-px h-8 bg-gray-200 shrink-0"></div>

    <div class="space-y-1 min-w-0">
      <div class="flex items-center gap-2 flex-wrap">
        <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-900">
          <span class="w-2 h-2 rounded-full shrink-0" style="background: {deptDotColor};"></span>
          {appointment.department || 'General Medical'}
        </span>

        <span
          class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold border {isFollowUp
            ? 'bg-sky-100 text-sky-800 border-sky-200'
            : 'bg-slate-100 text-slate-700 border-slate-200'}"
        >
          {visitTypeLabel}
        </span>

        {#if showRescheduledBadge}
          <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-800 border border-amber-200">
            Rescheduled
          </span>
        {/if}

        <span class="text-xs text-gray-400" aria-hidden="true">·</span>
        <span class="text-xs text-gray-500">{appointment.mode || 'Face-to-Face'}</span>
        <StatusPill status={appointment.status} />
      </div>

      <div class="text-xs text-gray-600 truncate">
        {appointment.doctor || 'Attending Physician'}
        {#if showRescheduledBadge && appointment.rescheduled_from_date}
          <span class="text-amber-800 font-medium"> · Moved from {appointment.rescheduled_from_date}</span>
        {/if}
        {#if appointment.chief_complaints && appointment.chief_complaints !== 'General consultation'}
          <span class="text-gray-500 font-normal italic"> — "{appointment.chief_complaints}"</span>
        {/if}
      </div>
    </div>
  </div>

  <div class="flex items-center gap-2 self-end sm:self-center shrink-0">
    {#if appointment.can_reschedule}
      <button
        type="button"
        class="px-2.5 py-1.5 rounded-md text-xs font-medium text-gray-600 border border-gray-200 bg-white hover:bg-gray-50 hover:text-gray-900 transition"
        on:click={() => onReschedule(appointment)}
      >
        Reschedule
      </button>
    {/if}

    <button
      type="button"
      class="px-2.5 py-1.5 rounded-md text-xs font-semibold text-[#166534] bg-[#f0fdf4] hover:bg-[#dcfce7] border border-[#bbf7d0] transition"
      on:click={() => onView(appointment)}
    >
      Details
    </button>
  </div>
</div>
