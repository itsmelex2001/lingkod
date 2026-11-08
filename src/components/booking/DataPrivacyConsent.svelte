<script>
  import { createEventDispatcher } from 'svelte';

  export let hasAgreedToPrivacy = false;

  const dispatch = createEventDispatcher();

  let hasScrolledToBottom = false;
  let isConsentChecked = false;
  let scrollContainer;

  function handleScroll() {
    if (hasScrolledToBottom || !scrollContainer) return;

    const { scrollTop, scrollHeight, clientHeight } = scrollContainer;
    // Cross-browser sub-pixel tolerance buffer (4px)
    if (scrollTop + clientHeight >= scrollHeight - 4) {
      hasScrolledToBottom = true;
    }
  }

  function handleCheckboxToggle(e) {
    // Strictly manual click only; NO auto-check
    isConsentChecked = e.target.checked;
  }

  function handleProceed() {
    if (hasScrolledToBottom && isConsentChecked) {
      hasAgreedToPrivacy = true;
      dispatch('proceed');
    }
  }
</script>

{#if !hasAgreedToPrivacy}
  <!-- Full-Screen Glassmorphism Overlay (Blurs and locks the underlying form) -->
  <div
    class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 bg-gray-900/40 backdrop-blur-sm transition-all duration-300"
    role="dialog"
    aria-modal="true"
    aria-labelledby="privacy-modal-heading"
  >
    <!-- Modal Card (Clean, modern aesthetic) -->
    <div
      class="w-full max-w-xl bg-white rounded-xl shadow-2xl border border-slate-100 flex flex-col p-6 sm:p-7 transition-all duration-200"
    >
      <!-- Modal Header -->
      <div class="flex items-center justify-between pb-3.5 border-b border-slate-100 mb-4">
        <div class="flex items-center gap-2.5">
          <div class="w-9 h-9 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center flex-shrink-0 border border-emerald-100">
            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
          </div>
          <div>
            <h2 id="privacy-modal-heading" class="text-sm sm:text-base font-bold text-slate-800 tracking-tight">
              Data Privacy Consent Notice
            </h2>
            <p class="text-[11px] text-slate-500 font-medium">Republic Act No. 10173 (Data Privacy Act of 2012)</p>
          </div>
        </div>

        {#if hasScrolledToBottom}
          <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
            <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
            Ready to Confirm
          </span>
        {:else}
          <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
            <svg class="w-3.5 h-3.5 text-amber-600 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
            Scroll Down
          </span>
        {/if}
      </div>

      <!-- Fixed-Height Scrollable Div with Customized Neat Scrollbars -->
      <div
        bind:this={scrollContainer}
        on:scroll={handleScroll}
        class="custom-scrollbar relative h-52 max-h-52 overflow-y-auto rounded-lg border border-slate-200 bg-slate-50/70 p-4 text-xs text-slate-600 leading-relaxed space-y-3"
      >
        <p class="font-bold text-slate-800">
          Mariveles Mental Wellness and General Hospital (MMWGH) — LINGKOD Patient Appointment &amp; Triage Service
        </p>

        <p>
          In strict compliance with Republic Act No. 10173, otherwise known as the <em>Data Privacy Act of 2012</em> of the Republic of the Philippines, and its Implementing Rules and Regulations, MMWGH is committed to upholding your privacy rights, clinical confidentiality, and the security of your personal health data.
        </p>

        <p class="font-bold text-slate-800">1. Collection and Scope of Personal Information</p>
        <p>
          By proceeding with this booking request, you acknowledge and authorize MMWGH to process personal information (such as your full name, contact number, birthdate, age, home address, and guardian information if applicable) and sensitive clinical data (such as your chief complaints and clinical triage routing).
        </p>

        <p class="font-bold text-slate-800">2. Medical Confidentiality &amp; Purpose of Processing</p>
        <p>
          Your information is utilized solely for clinical schedule confirmation, physician triage assignment, and logistical SMS/Email notification reminders. Specific medical diagnoses and complaints will never be published in unencrypted communications or shared with unauthorized entities.
        </p>

        <p class="font-bold text-slate-800">3. Minor Consultation Guidelines</p>
        <p>
          For patients under eighteen (18) years of age, a designated parent or legal guardian must be verified and must accompany the minor patient to the hospital Outpatient Triage desk during the clinic schedule.
        </p>

        <p class="font-bold text-slate-800">4. Data Subject Rights</p>
        <p class="pb-1">
          Under R.A. 10173, you retain the right to be informed, review your clinical records, dispute inaccuracies, and request correction in accordance with policies enforced by the MMWGH Data Protection Officer.
        </p>
      </div>

      <!-- Checkbox (Disabled until scrolled to absolute bottom) -->
      <div class="mt-4 pt-3 border-t border-slate-100">
        <label
          class="flex items-start gap-3 select-none transition-all duration-150 {hasScrolledToBottom ? 'cursor-pointer opacity-100' : 'cursor-not-allowed opacity-40'}"
        >
          <input
            type="checkbox"
            id="modalPrivacyCheckbox"
            checked={isConsentChecked}
            disabled={!hasScrolledToBottom}
            on:change={handleCheckboxToggle}
            class="mt-0.5 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 {hasScrolledToBottom ? 'cursor-pointer' : 'cursor-not-allowed'}"
          />
          <span class="text-xs font-medium text-slate-700 leading-snug">
            I have read and agree to the <span class="font-semibold text-slate-900">Data Privacy Policy</span> and consent to the processing of my information.
          </span>
        </label>

        {#if !hasScrolledToBottom}
          <p class="text-[11px] text-amber-700/90 mt-1.5 flex items-center gap-1.5 pl-7">
            <svg class="w-3.5 h-3.5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>Please scroll to the bottom of the statement to unlock this checkbox.</span>
          </p>
        {/if}
      </div>

      <!-- Action Button: Proceed (Disabled until manually checked) -->
      <div class="mt-5 flex justify-end">
        <button
          type="button"
          on:click={handleProceed}
          disabled={!isConsentChecked}
          class="inline-flex items-center justify-center px-6 py-2.5 text-xs font-bold rounded-lg shadow-sm transition-all duration-150 {isConsentChecked ? 'bg-emerald-700 hover:bg-emerald-800 active:bg-emerald-900 text-white cursor-pointer hover:shadow hover:-translate-y-0.5' : 'bg-slate-200 text-slate-400 cursor-not-allowed shadow-none'}"
        >
          Proceed
        </button>
      </div>
    </div>
  </div>
{/if}

<style>
  /* Customized, neat scrollbars */
  .custom-scrollbar::-webkit-scrollbar {
    width: 6px;
  }
  .custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 999px;
  }
  .custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 999px;
  }
  .custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
  }
</style>
