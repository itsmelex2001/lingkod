<script>
  import StatusPill from './StatusPill.svelte';

  export let appointment;
  export let onView = (appt) => {};
  export let onReschedule = (appt) => {};

  $: deptDotColor = (() => {
    const dept = (appointment.department || '').toLowerCase();
    if (dept.includes('psych')) return '#7c4dcc';
    if (dept.includes('neuro')) return '#2b7fd4';
    return '#0f9d74';
  })();
</script>

<div class="px-5 py-4 border-b border-[#f3f4f6] last:border-none flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-[#fafafa] transition" style="padding: 16px 20px; border-bottom: 1px solid #f3f4f6; display: flex; align-items: center; justify-content: space-between; gap: 12px;">
  <div class="flex items-start sm:items-center gap-4 min-w-0" style="display: flex; align-items: center; gap: 16px; min-width: 0;">
    <!-- Date Block -->
    <div class="w-14 shrink-0 text-left" style="width: 60px; min-width: 60px; text-align: left;">
      <span class="block text-xs font-bold text-[#111827]" style="display: block; font-size: 12px; font-weight: 700; color: #111827;">{appointment.date || 'TBD'}</span>
      <span class="block text-[11px] text-[#6b7280]" style="display: block; font-size: 11px; color: #6b7280;">{appointment.time || '8:00 AM'}</span>
    </div>

    <!-- Divider -->
    <div class="hidden sm:block w-px h-8 bg-[#e5e7eb] shrink-0" style="width: 1px; height: 32px; background: #e5e7eb; flex-shrink: 0;"></div>

    <!-- Department & Doctor -->
    <div class="space-y-1 min-w-0" style="display: flex; flex-direction: column; gap: 3px; min-width: 0;">
      <div class="flex items-center gap-2 flex-wrap" style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
        <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#111827]" style="display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; color: #111827;">
          <span class="w-2 h-2 rounded-full" style="width: 8px; height: 8px; border-radius: 50%; background: {deptDotColor}; display: inline-block;"></span>
          {appointment.department || 'General Medical'}
        </span>
        <span class="text-xs text-[#9ca3af]" style="font-size: 12px; color: #9ca3af;">·</span>
        <span class="text-xs text-[#6b7280]" style="font-size: 12px; color: #6b7280;">{appointment.mode || 'Face-to-Face'}</span>
        <StatusPill status={appointment.status} />
      </div>

      <div class="text-xs text-[#4b5563] truncate" style="font-size: 12px; color: #4b5563;">
        {appointment.doctor || 'Attending Physician'}
        {#if appointment.chief_complaints && appointment.chief_complaints !== 'General consultation'}
          <span class="text-[#6b7280] font-normal italic" style="color: #6b7280; font-style: italic;"> — "{appointment.chief_complaints}"</span>
        {/if}
      </div>
    </div>
  </div>

  <!-- Row Actions -->
  <div class="flex items-center gap-2 self-end sm:self-center shrink-0" style="display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
    {#if appointment.can_reschedule}
      <button
        type="button"
        class="px-2.5 py-1 rounded text-xs font-medium text-[#4b5563] border border-[#e5e7eb] hover:bg-white hover:text-[#111827] transition"
        style="padding: 4px 10px; border-radius: 6px; border: 1px solid #e5e7eb; background: white; font-size: 12px; font-weight: 500; color: #4b5563; cursor: pointer;"
        on:click={() => onReschedule(appointment)}
      >
        Reschedule
      </button>
    {/if}

    <button
      type="button"
      class="px-2.5 py-1 rounded text-xs font-semibold text-[#166534] bg-[#f0fdf4] hover:bg-[#dcfce7] border border-[#bbf7d0] transition"
      style="padding: 4px 10px; border-radius: 6px; border: 1px solid #bbf7d0; background: #f0fdf4; font-size: 12px; font-weight: 600; color: #166534; cursor: pointer;"
      on:click={() => onView(appointment)}
    >
      Details
    </button>
  </div>
</div>
