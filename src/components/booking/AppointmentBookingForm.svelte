<script>
  import { createEventDispatcher } from 'svelte';
  import DataPrivacyConsent from './DataPrivacyConsent.svelte';

  export let patientId = '';
  export let email = '';

  const dispatch = createEventDispatcher();

  // Privacy Agreement Modal State (Blocks interactions until agreed)
  let hasAgreedToPrivacy = false;

  const tomorrow = new Date();
  tomorrow.setDate(tomorrow.getDate() + 1);
  const minDate = tomorrow.toISOString().slice(0, 10);

  let department = '';
  let chiefComplaint = '';
  let consultationMode = 'Face-to-Face';
  let appointmentDate = minDate;
  let scheduledTime = '';

  let timeBlocks = [];
  let slotNotice = '';
  let isLoadingSlots = false;
  let isSubmitting = false;
  let lastFetchedDate = '';
  let slotsRequestId = 0;

  let errorMessage = '';
  let successMessage = '';
  let bookedRefId = '';

  function isValidAppointmentDate(dateStr) {
    return /^\d{4}-\d{2}-\d{2}$/.test(dateStr || '');
  }

  function normalizeTimeBlock(block) {
    const value = block.time_block_value || block.value || block.id || block.start_time || '';
    const baseLabel = block.label
      || `${block.start_time || value}${block.end_time ? ` - ${block.end_time}` : ''}${block.session ? ` (${block.session})` : ''}`;
    const alreadyBooked = Boolean(
      block.already_booked || block.alreadyBooked || block.booked || block.conflict || block.is_available === false
    );
    return { value, label: baseLabel, alreadyBooked };
  }

  function extractTimeBlocks(data) {
    if (Array.isArray(data)) return data;
    if (Array.isArray(data?.time_blocks)) return data.time_blocks;
    if (Array.isArray(data?.slots)) return data.slots;
    if (Array.isArray(data?.data)) return data.data;
    return [];
  }

  async function fetchTimeBlocks(dateStr) {
    const requestId = ++slotsRequestId;
    isLoadingSlots = true;
    scheduledTime = '';
    errorMessage = '';
    slotNotice = '';

    try {
      const q = new URLSearchParams({
        target_date: dateStr,
        ...(department ? { department } : {}),
        ...(patientId ? { patient_id: patientId } : {}),
        ...(email ? { email } : {})
      });
      const res = await fetch(`/sdn/api/time-blocks.php?${q.toString()}`);
      const data = await res.json();
      if (requestId !== slotsRequestId) return;

      const blocks = extractTimeBlocks(data).map(normalizeTimeBlock).filter((block) => block.value);
      timeBlocks = blocks;

      const bookedCount = blocks.filter((block) => block.alreadyBooked).length;
      if (bookedCount > 0 && bookedCount < blocks.length) {
        slotNotice = 'One session on this date is already booked. Choose an open time block to continue.';
      } else if (blocks.length > 0 && bookedCount === blocks.length) {
        slotNotice = 'You already have bookings for every time block on this date. Please choose another date.';
      }

      const current = blocks.find((block) => block.value === scheduledTime && !block.alreadyBooked);
      if (!current) {
        const firstOpen = blocks.find((block) => !block.alreadyBooked);
        scheduledTime = firstOpen ? firstOpen.value : '';
      }
    } catch (e) {
      if (requestId !== slotsRequestId) return;
      console.warn('Time block fetch error:', e);
      timeBlocks = [];
      scheduledTime = '';
    } finally {
      if (requestId === slotsRequestId) isLoadingSlots = false;
    }
  }

  $: if (isValidAppointmentDate(appointmentDate)) {
    if (appointmentDate !== lastFetchedDate) {
      lastFetchedDate = appointmentDate;
      fetchTimeBlocks(appointmentDate);
    }
  } else if (lastFetchedDate || timeBlocks.length || scheduledTime || isLoadingSlots) {
    lastFetchedDate = '';
    timeBlocks = [];
    scheduledTime = '';
    isLoadingSlots = false;
  }

  function timeBlockOptionLabel(block) {
    const clean = String(block.label || block.value).replace(/\s*\(Already Booked\)\s*$/i, '');
    return block.alreadyBooked ? `${clean} (Already Booked)` : clean;
  }

  $: selectedBlock = timeBlocks.find((block) => block.value === scheduledTime);
  $: hasValidTimeBlock = Boolean(selectedBlock && !selectedBlock.alreadyBooked && !isLoadingSlots);
  $: noSlotsForDate = !isLoadingSlots && isValidAppointmentDate(appointmentDate) && timeBlocks.length === 0;

  async function handleSubmit() {
    errorMessage = '';
    successMessage = '';

    if (!chiefComplaint.trim()) {
      errorMessage = 'Chief complaint is mandatory. Please describe your symptoms or reason for visit.';
      return;
    }

    if (!appointmentDate) {
      errorMessage = 'Please choose an appointment date.';
      return;
    }

    if (!hasValidTimeBlock) {
      errorMessage = noSlotsForDate
        ? 'No available slots for this date. Please choose another appointment date.'
        : 'Please select an available time block.';
      return;
    }

    isSubmitting = true;

    try {
      const payload = {
        patient_id: patientId,
        email: email,
        department_id: department || null,
        department: department || null,
        chief_complaint: chiefComplaint.trim(),
        consultation_mode: consultationMode,
        scheduled_date: appointmentDate,
        scheduled_time: scheduledTime
      };

      const res = await fetch('/sdn/api/appointments/create.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });

      const data = await res.json();

      if (res.status === 409) {
        // Anti-double booking conflict error from backend
        errorMessage = data.message || 'You already have an appointment scheduled for this time. Please select a different time (e.g., Afternoon) for your second consultation.';
        if (data.suggested_slot) {
          const altSlot = timeBlocks.find((block) => {
            if (block.alreadyBooked) return false;
            const period = (block.label || '').toLowerCase();
            return period.includes(String(data.suggested_slot).toLowerCase());
          });
          if (altSlot) scheduledTime = altSlot.value;
        }
        return;
      }

      if (!res.ok || !data.success) {
        errorMessage = data.message || 'Failed to book appointment. Please check your entries.';
        return;
      }

      bookedRefId = data.reference_id || data.patient_id;
      successMessage = data.message || 'Appointment booked successfully and queued for staff review.';
      dispatch('booked', data);
    } catch (e) {
      errorMessage = 'Network connection error. Please try again.';
    } finally {
      isSubmitting = false;
    }
  }
</script>

<!-- Data Privacy Consent Modal Overlay (Displayed on top while hasAgreedToPrivacy is false) -->
<DataPrivacyConsent bind:hasAgreedToPrivacy />

<!-- Booking Appointment Form (Rendered and visible immediately; blurred & unclickable until consented) -->
<div class="relative w-full max-w-2xl mx-auto bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-8 font-sans transition-all duration-300 {!hasAgreedToPrivacy ? 'filter blur-[1.5px] pointer-events-none select-none opacity-85' : ''}">
  <div class="mb-6">
    <h2 class="text-xl font-bold text-slate-800 tracking-tight">Book an Outpatient Consultation</h2>
    <p class="text-xs text-slate-500 mt-1">Select your preferred schedule and describe your chief complaint.</p>
  </div>

  {#if errorMessage}
    <div class="mb-5 p-3.5 bg-red-50 border border-red-200 rounded-xl text-red-700 text-xs flex items-start gap-2.5" role="alert">
      <svg class="w-4 h-4 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      <span class="flex-1 leading-relaxed">{errorMessage}</span>
    </div>
  {/if}

  {#if slotNotice}
    <div class="mb-5 p-3 bg-amber-50 border border-amber-200 rounded-xl text-amber-800 text-xs flex items-start gap-2">
      <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      <span class="leading-relaxed">{slotNotice}</span>
    </div>
  {/if}

  {#if successMessage}
    <div class="mb-6 p-5 bg-emerald-50 border border-emerald-200 rounded-2xl text-emerald-900 text-center">
      <div class="w-10 h-10 mx-auto mb-2 bg-emerald-100 text-emerald-700 rounded-full flex items-center justify-center font-bold">✓</div>
      <h3 class="text-sm font-bold text-emerald-800">{successMessage}</h3>
      <p class="text-xs text-emerald-600 mt-1">Reference ID: <strong class="font-mono">{bookedRefId}</strong></p>
      <button
        type="button"
        class="mt-4 px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-semibold rounded-lg transition"
        on:click={() => { successMessage = ''; chiefComplaint = ''; }}
      >
        Book Another Appointment
      </button>
    </div>
  {:else}
    <form on:submit|preventDefault={handleSubmit} class="space-y-5">
      <!-- 1. Preferred Department (Optional) -->
      <div>
        <label for="deptSelect" class="block text-xs font-semibold text-slate-700 mb-1.5">
          Preferred Department (Optional)
        </label>
        <select
          id="deptSelect"
          bind:value={department}
          class="w-full max-w-full min-h-12 sm:min-h-0 px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm sm:text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 transition"
        >
          <option value="">No preference (Staff will assign based on complaint)</option>
          <option value="General Medical">General Medical</option>
          <option value="Neurology">Neurology</option>
          <option value="OPU Psychiatry">OPU Psychiatry</option>
        </select>
        <p class="text-[11px] text-slate-400 mt-1">Leave blank if unsure. Our triage nurse will review your complaint.</p>
      </div>

      <!-- 2. Chief Complaint (Mandatory Textarea) -->
      <div>
        <label for="chiefComplaint" class="block text-xs font-semibold text-slate-700 mb-1.5">
          Chief Complaint / Reason for Consultation <span class="text-red-500">*</span>
        </label>
        <textarea
          id="chiefComplaint"
          rows="3"
          bind:value={chiefComplaint}
          placeholder="Describe your primary symptoms, duration, or medical reason for this consultation..."
          required
          class="w-full max-w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm sm:text-xs text-slate-800 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 transition resize-none"
        ></textarea>
      </div>

      <!-- 3. Consultation Mode -->
      <div>
        <span class="block text-xs font-semibold text-slate-700 mb-1.5">Consultation Mode</span>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
          <label class="flex items-center gap-2 min-h-12 sm:min-h-0 p-2.5 border rounded-xl cursor-pointer text-sm sm:text-xs transition {consultationMode === 'Face-to-Face' ? 'border-emerald-600 bg-emerald-50/50 text-emerald-900 font-medium' : 'border-slate-200 text-slate-600 hover:bg-slate-50'}">
            <input type="radio" bind:group={consultationMode} value="Face-to-Face" class="text-emerald-600 focus:ring-emerald-500" />
            <span>Face-to-Face (Hospital)</span>
          </label>
          <label class="flex items-center gap-2 min-h-12 sm:min-h-0 p-2.5 border rounded-xl cursor-pointer text-sm sm:text-xs transition {consultationMode === 'Online Teleconsultation' ? 'border-emerald-600 bg-emerald-50/50 text-emerald-900 font-medium' : 'border-slate-200 text-slate-600 hover:bg-slate-50'}">
            <input type="radio" bind:group={consultationMode} value="Online Teleconsultation" class="text-emerald-600 focus:ring-emerald-500" />
            <span>Teleconsultation</span>
          </label>
        </div>
      </div>

      <!-- 4. Date & Time Selection (With Anti-Double Booking Conflict Detection) -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label for="apptDate" class="block text-xs font-semibold text-emerald-700 mb-1.5">
            Appointment Date <span class="text-red-500">*</span>
          </label>
          <input
            type="date"
            id="apptDate"
            min={minDate}
            bind:value={appointmentDate}
            required
            class="w-full max-w-full min-h-12 sm:min-h-0 px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm sm:text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 transition"
          />
        </div>

        <div>
          <label for="apptTime" class="flex items-center gap-1.5 text-xs font-semibold text-emerald-700 mb-1.5">
            <span>Scheduled Time Block <span class="text-red-500">*</span></span>
            {#if isLoadingSlots}
              <svg class="animate-spin h-3 w-3 text-emerald-600" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
              </svg>
            {/if}
          </label>
          <div>
            <select
              id="apptTime"
              bind:value={scheduledTime}
              required
              disabled={isLoadingSlots || noSlotsForDate || !hasAgreedToPrivacy}
              class="w-full max-w-full min-h-12 sm:min-h-0 px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm sm:text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 transition disabled:bg-slate-100 disabled:text-slate-500 disabled:cursor-not-allowed"
            >
              {#if isLoadingSlots}
                <option value="">Loading available time blocks...</option>
              {:else if noSlotsForDate}
                <option value="">No available slots for this date</option>
              {:else}
                <option value="" disabled>Select a time block</option>
                {#each timeBlocks as block}
                  <option value={block.value} disabled={block.alreadyBooked}>
                    {timeBlockOptionLabel(block)}
                  </option>
                {/each}
              {/if}
            </select>
          </div>
        </div>
      </div>

      <!-- Final Submission Action Button -->
      <div class="pt-3 border-t border-slate-100 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3">
        <button
          type="button"
          class="w-full sm:w-auto min-h-12 sm:min-h-0 px-5 py-3 border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm sm:text-xs font-bold rounded-xl transition"
          on:click={() => dispatch('back')}
          disabled={isSubmitting}
        >
          Back
        </button>
        <button
          type="submit"
          disabled={isSubmitting || !hasValidTimeBlock || !hasAgreedToPrivacy}
          class="w-full sm:w-auto min-h-12 sm:min-h-0 px-6 py-3 bg-emerald-700 hover:bg-emerald-800 disabled:bg-slate-300 disabled:cursor-not-allowed text-white text-sm sm:text-xs font-bold rounded-xl transition shadow-sm active:scale-[0.99] flex items-center justify-center gap-2 cursor-pointer"
        >
          {#if isSubmitting}
            <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
            <span>Verifying &amp; Booking...</span>
          {:else}
            <span>Next</span>
          {/if}
        </button>
      </div>
    </form>
  {/if}
</div>

