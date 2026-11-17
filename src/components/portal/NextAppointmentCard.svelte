<script>
  import StatusPill from './StatusPill.svelte';

  export let appointment = null;
  export let onBook = () => {};
  export let onViewDetails = (appt) => {};
  export let onReschedule = (appt) => {};

  $: relativeLabel = (() => {
    if (!appointment?.date_raw) return '';
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const apptDate = new Date(appointment.date_raw + 'T00:00:00');
    apptDate.setHours(0, 0, 0, 0);

    const diffDays = Math.round((apptDate - today) / (1000 * 60 * 60 * 24));
    if (diffDays === 0) return 'Today';
    if (diffDays === 1) return 'Tomorrow';
    if (diffDays > 1 && diffDays <= 7) return `In ${diffDays} days`;
    return appointment.date || '';
  })();
</script>

{#if appointment}
  <div class="bg-white border border-[#e5e7eb] rounded-xl p-6 relative overflow-hidden transition-all hover:border-[#cbd5e1]" style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; position: relative; overflow: hidden;">
    <!-- Green Accent Indicator Strip -->
    <div class="absolute top-0 left-0 right-0 h-1 bg-[#166534]" style="position: absolute; top: 0; left: 0; right: 0; height: 4px; background: #166534;"></div>

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px;">
      <div class="flex items-start gap-4" style="display: flex; align-items: flex-start; gap: 16px;">
        <!-- Date Badge -->
        <div class="w-16 h-16 rounded-lg bg-[#f0fdf4] border border-[#bbf7d0] flex flex-col items-center justify-center shrink-0" style="width: 64px; height: 64px; min-width: 64px; border-radius: 8px; background: #f0fdf4; border: 1px solid #bbf7d0; display: flex; flex-direction: column; align-items: center; justify-content: center; flex-shrink: 0;">
          <span class="text-[11px] font-bold text-[#166534] uppercase tracking-wider" style="font-size: 11px; font-weight: 700; color: #166534; text-transform: uppercase;">{appointment.month || 'NOV'}</span>
          <span class="text-xl font-extrabold text-[#111827] leading-none mt-0.5" style="font-size: 22px; font-weight: 800; color: #111827; line-height: 1; margin-top: 2px;">{appointment.day || '16'}</span>
        </div>

        <div class="space-y-1" style="display: flex; flex-direction: column; gap: 4px;">
          <div class="flex items-center gap-2 flex-wrap" style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            <span class="text-xs font-semibold uppercase tracking-wider text-[#166534]" style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #166534; letter-spacing: 0.05em;">Next Appointment</span>
            {#if relativeLabel}
              <span class="text-xs text-[#6b7280]" style="font-size: 12px; color: #6b7280;">· {relativeLabel}</span>
            {/if}
            <StatusPill status={appointment.status} />
          </div>

          <h3 class="text-lg font-bold text-[#111827]" style="font-size: 18px; font-weight: 700; color: #111827; margin: 0;">
            {appointment.title || (appointment.department + ' Consultation')}
          </h3>

          <div class="flex items-center gap-3 text-xs text-[#4b5563] flex-wrap" style="display: flex; align-items: center; gap: 12px; font-size: 12px; color: #4b5563; flex-wrap: wrap;">
            <span class="flex items-center gap-1" style="display: flex; align-items: center; gap: 4px;">
              <svg width="14" height="14" class="w-3.5 h-3.5 text-[#6b7280]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 14px; height: 14px; color: #6b7280;">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
              </svg>
              {appointment.time || appointment.time_label || '8:00 AM'}
            </span>
            <span>·</span>
            <span class="flex items-center gap-1" style="display: flex; align-items: center; gap: 4px;">
              <svg width="14" height="14" class="w-3.5 h-3.5 text-[#6b7280]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 14px; height: 14px; color: #6b7280;">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
              </svg>
              {appointment.doctor || 'Attending Physician'}
            </span>
            <span>·</span>
            <span class="flex items-center gap-1" style="display: flex; align-items: center; gap: 4px;">
              <svg width="14" height="14" class="w-3.5 h-3.5 text-[#6b7280]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 14px; height: 14px; color: #6b7280;">
                <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                <line x1="8" y1="21" x2="16" y2="21"></line>
                <line x1="12" y1="17" x2="12" y2="21"></line>
              </svg>
              {appointment.mode || 'Face-to-Face'}
            </span>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex items-center gap-2 self-end md:self-center shrink-0" style="display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
        {#if appointment.can_reschedule}
          <button
            type="button"
            class="px-3 py-1.5 rounded-lg border border-[#e5e7eb] text-xs font-semibold text-[#374151] hover:bg-[#f9fafb] transition"
            style="padding: 6px 12px; border-radius: 8px; border: 1px solid #e5e7eb; background: white; color: #374151; font-size: 12px; font-weight: 600; cursor: pointer;"
            on:click={() => onReschedule(appointment)}
          >
            Reschedule
          </button>
        {/if}

        <button
          type="button"
          class="px-3.5 py-1.5 rounded-lg bg-[#166534] hover:bg-[#14532d] text-white text-xs font-semibold transition"
          style="padding: 6px 14px; border-radius: 8px; border: none; background: #166534; color: white; font-size: 12px; font-weight: 600; cursor: pointer;"
          on:click={() => onViewDetails(appointment)}
        >
          View Details
        </button>
      </div>
    </div>
  </div>
{:else}
  <div class="bg-white border border-[#e5e7eb] rounded-xl p-6 text-center" style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 28px 24px; text-align: center;">
    <div class="w-12 h-12 rounded-full bg-[#f0fdf4] text-[#166534] flex items-center justify-center mx-auto mb-3" style="width: 48px; height: 48px; border-radius: 50%; background: #f0fdf4; color: #166534; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px;">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 24px; height: 24px;">
        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
        <line x1="16" y1="2" x2="16" y2="6"></line>
        <line x1="8" y1="2" x2="8" y2="6"></line>
        <line x1="3" y1="10" x2="21" y2="10"></line>
      </svg>
    </div>
    <h3 class="text-sm font-semibold text-[#111827]" style="font-size: 15px; font-weight: 700; color: #111827; margin: 0;">No upcoming appointments</h3>
    <p class="text-xs text-[#6b7280] max-w-sm mx-auto mt-1 mb-4" style="font-size: 13px; color: #6b7280; max-width: 360px; margin: 4px auto 16px;">
      You have no active consultations scheduled. Book a visit with general medical, neurology, or psychiatry.
    </p>
    <button
      type="button"
      class="px-4 py-2 rounded-lg bg-[#166534] hover:bg-[#14532d] text-white text-xs font-semibold transition inline-flex items-center gap-1.5"
      style="padding: 8px 16px; border-radius: 8px; border: none; background: #166534; color: white; font-size: 12px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;"
      on:click={onBook}
    >
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 14px; height: 14px;">
        <line x1="12" y1="5" x2="12" y2="19"></line>
        <line x1="5" y1="12" x2="19" y2="12"></line>
      </svg>
      Book an Appointment
    </button>
  </div>
{/if}
