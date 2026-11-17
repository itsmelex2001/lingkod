<script>
  import StatusPill from './StatusPill.svelte';

  export let show = false;
  export let appointment = null;
  export let onClose = () => {};

  function handleKeydown(e) {
    if (e.key === 'Escape' && show) {
      onClose();
    }
  }
</script>

<svelte:window on:keydown={handleKeydown} />

{#if show && appointment}
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
    <button
      type="button"
      class="fixed inset-0 bg-transparent cursor-default border-0"
      aria-label="Close dialog"
      on:click={onClose}
    ></button>

    <div
      class="relative w-full max-w-lg bg-white rounded-xl shadow-xl border border-[#e5e7eb] p-6 text-left z-10 max-h-[90vh] overflow-y-auto animate-in fade-in zoom-in-95 duration-150"
      role="dialog"
      aria-modal="true"
      aria-labelledby="appt-detail-title"
    >
      <!-- Header -->
      <div class="flex items-start justify-between pb-4 border-b border-[#f3f4f6]">
        <div>
          <div class="flex items-center gap-2 mb-1">
            <span class="text-xs font-semibold text-[#166534] uppercase tracking-wider">
              {appointment.department || 'General Medical'}
            </span>
            <StatusPill status={appointment.status} />
          </div>
          <h2 id="appt-detail-title" class="text-base font-bold text-[#111827]">
            {appointment.title || (appointment.department + ' Consultation')}
          </h2>
          <p class="text-xs text-[#6b7280] font-mono mt-0.5">
            Ref: {appointment.patient_code || ('PT-' + appointment.id)}
          </p>
        </div>

        <button
          type="button"
          class="p-1 rounded-lg text-[#9ca3af] hover:text-[#111827] hover:bg-[#f3f4f6] transition"
          aria-label="Close modal"
          on:click={onClose}
        >
          <svg width="20" height="20" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 20px; height: 20px;">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>

      <!-- Schedule Details Grid -->
      <div class="grid grid-cols-2 gap-4 py-4 border-b border-[#f3f4f6] text-xs">
        <div>
          <span class="block text-[#6b7280] font-medium">Date & Time</span>
          <span class="font-semibold text-[#111827] mt-0.5 block">
            {appointment.date || 'TBD'} · {appointment.time || '8:00 AM'}
          </span>
        </div>

        <div>
          <span class="block text-[#6b7280] font-medium">Attending Physician</span>
          <span class="font-semibold text-[#111827] mt-0.5 block">
            {appointment.doctor || 'Attending Physician'}
          </span>
        </div>

        <div>
          <span class="block text-[#6b7280] font-medium">Consultation Mode</span>
          <span class="font-semibold text-[#111827] mt-0.5 block">
            {appointment.mode || 'Face-to-Face'}
          </span>
        </div>

        <div>
          <span class="block text-[#6b7280] font-medium">Hospital Facility</span>
          <span class="font-semibold text-[#111827] mt-0.5 block">
            MMWGH Outpatient Department
          </span>
        </div>
      </div>

      <!-- Chief Complaint -->
      <div class="py-4 border-b border-[#f3f4f6]">
        <h4 class="text-xs font-semibold text-[#374151] mb-1">Reason for Visit / Chief Complaint</h4>
        <p class="text-xs text-[#4b5563] bg-[#f9fafb] p-3 rounded-lg border border-[#e5e7eb] leading-relaxed">
          {appointment.chief_complaints || 'Routine outpatient follow-up consultation.'}
        </p>
      </div>

      <!-- Clinical Findings (if any) -->
      {#if appointment.diagnosis || appointment.treatment || appointment.prescription}
        <div class="py-4 border-b border-[#f3f4f6] space-y-3">
          <h4 class="text-xs font-semibold text-[#374151]">Clinical Summary & Prescriptions</h4>
          
          {#if appointment.diagnosis}
            <div>
              <span class="text-[11px] font-medium text-[#6b7280] block">Diagnosis:</span>
              <p class="text-xs text-[#111827] mt-0.5">{appointment.diagnosis}</p>
            </div>
          {/if}

          {#if appointment.treatment}
            <div>
              <span class="text-[11px] font-medium text-[#6b7280] block">Recommended Treatment:</span>
              <p class="text-xs text-[#111827] mt-0.5">{appointment.treatment}</p>
            </div>
          {/if}

          {#if appointment.prescription}
            <div class="bg-[#f0fdf4] border border-[#bbf7d0] p-3 rounded-lg">
              <span class="text-[11px] font-bold text-[#166534] block">Prescription:</span>
              <p class="text-xs text-[#166534] font-mono mt-0.5 whitespace-pre-wrap">{appointment.prescription}</p>
            </div>
          {/if}
        </div>
      {/if}

      <!-- Previous Appointment Reference -->
      {#if appointment.previous_appointment_date}
        <div class="py-3 text-[11px] text-[#6b7280]">
          Follow-up to previous visit on {appointment.previous_appointment_date} ({appointment.previous_department || 'General Medical'}).
        </div>
      {/if}

      <!-- Close Button -->
      <div class="pt-4 flex justify-end">
        <button
          type="button"
          class="px-4 py-1.5 rounded-lg bg-[#166534] hover:bg-[#14532d] text-white text-xs font-semibold transition"
          on:click={onClose}
        >
          Close
        </button>
      </div>
    </div>
  </div>
{/if}

