<script>
  import { createEventDispatcher, onMount, onDestroy } from 'svelte';

  export let isOpen = false;
  export let patient = {
    patient_id: '',
    full_name: '',
    department: 'General Medical'
  };

  const dispatch = createEventDispatcher();

  // 1. Reactive State
  let diagnosis = '';
  let treatmentPlan = '';
  let medicineName = '';
  let dosageInstructions = '';
  let quantity = '';
  let fullPrescription = '';

  let suggestedGuidelines = [];
  let isLoading = false;
  let isOpenDropdown = false;
  let selectedIndex = -1;
  let debounceTimer = null;
  let comboboxWrapper;

  // Auto-format full prescription text if fields are filled and user hasn't heavily customized it
  function updateFullPrescriptionPreview() {
    if (medicineName && (dosageInstructions || quantity)) {
      fullPrescription = `${medicineName} — ${dosageInstructions || 'As directed'} (${quantity ? 'Qty: ' + quantity : ''})`.trim();
    }
  }

  // 2. Debounced Fetching to CDSS API
  function handleDiagnosisInput(event) {
    diagnosis = event.target.value;
    selectedIndex = -1;

    if (debounceTimer) {
      clearTimeout(debounceTimer);
    }

    const trimmedQuery = diagnosis.trim();
    if (trimmedQuery.length < 2) {
      suggestedGuidelines = [];
      isOpenDropdown = false;
      isLoading = false;
      return;
    }

    isLoading = true;
    debounceTimer = setTimeout(async () => {
      try {
        const deptParam = patient.department ? `&department=${encodeURIComponent(patient.department)}` : '';
        const apiBase = window.location.pathname.startsWith('/sdn') ? '/sdn/api' : '/api';
        const res = await fetch(`${apiBase}/guidelines.php?query=${encodeURIComponent(trimmedQuery)}${deptParam}`);
        
        if (res.ok) {
          const data = await res.json();
          suggestedGuidelines = Array.isArray(data) ? data : (data.data || []);
          isOpenDropdown = suggestedGuidelines.length > 0;
        } else {
          suggestedGuidelines = [];
          isOpenDropdown = false;
        }
      } catch (err) {
        console.error('CDSS Guideline query failed:', err);
        suggestedGuidelines = [];
        isOpenDropdown = false;
      } finally {
        isLoading = false;
      }
    }, 280);
  }

  // 3. Cascading Auto-Populate Logic
  function selectGuideline(guideline) {
    if (!guideline) return;

    // Fill all fields from selected CDSS guideline
    diagnosis = guideline.diagnosis;
    treatmentPlan = guideline.treatment_plan || '';
    medicineName = guideline.medicine_name || '';
    dosageInstructions = guideline.dosage_instructions || '';
    quantity = guideline.default_quantity || '';
    
    updateFullPrescriptionPreview();

    // Immediately close dropdown & reset suggestions
    isOpenDropdown = false;
    suggestedGuidelines = [];
    selectedIndex = -1;
  }

  // Keyboard navigation for combobox
  function handleKeyDown(event) {
    if (!isOpenDropdown || suggestedGuidelines.length === 0) return;

    if (event.key === 'ArrowDown') {
      event.preventDefault();
      selectedIndex = (selectedIndex + 1) % suggestedGuidelines.length;
    } else if (event.key === 'ArrowUp') {
      event.preventDefault();
      selectedIndex = (selectedIndex - 1 + suggestedGuidelines.length) % suggestedGuidelines.length;
    } else if (event.key === 'Enter') {
      if (selectedIndex >= 0 && selectedIndex < suggestedGuidelines.length) {
        event.preventDefault();
        selectGuideline(suggestedGuidelines[selectedIndex]);
      }
    } else if (event.key === 'Escape') {
      isOpenDropdown = false;
    }
  }

  // Click outside listener to dismiss dropdown
  function handleClickOutside(event) {
    if (comboboxWrapper && !comboboxWrapper.contains(event.target)) {
      isOpenDropdown = false;
    }
  }

  onMount(() => {
    document.addEventListener('click', handleClickOutside);
  });

  onDestroy(() => {
    if (debounceTimer) clearTimeout(debounceTimer);
    document.removeEventListener('click', handleClickOutside);
  });

  function handleClose() {
    isOpenDropdown = false;
    dispatch('close');
  }

  function handleSubmit() {
    if (!diagnosis.trim()) {
      alert('Please enter or select a clinical diagnosis.');
      return;
    }

    dispatch('complete', {
      patient_id: patient.patient_id,
      diagnosis: diagnosis.trim(),
      treatment_plan: treatmentPlan.trim(),
      medicine_name: medicineName.trim(),
      dosage_instructions: dosageInstructions.trim(),
      quantity: quantity.trim(),
      prescription: fullPrescription.trim()
    });
  }
</script>

{#if isOpen}
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 overflow-y-auto bg-slate-900/60 backdrop-blur-sm transition-opacity" role="dialog" aria-modal="true">
    <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden transform transition-all">
      
      <!-- Modal Header -->
      <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100 bg-slate-50/70">
        <div>
          <div class="flex items-center gap-2">
            <h3 class="text-lg font-bold text-slate-800 tracking-tight">Conclude Consultation</h3>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
              CDSS Assisted
            </span>
          </div>
          <p class="text-xs text-slate-500 mt-0.5">
            {patient.full_name || 'Patient'} ({patient.patient_id || 'ID Pending'}) &bull; <span class="font-medium text-slate-700">{patient.department || 'Outpatient Clinic'}</span>
          </p>
        </div>
        <button
          type="button"
          on:click={handleClose}
          class="rounded-lg p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-200/60 transition-colors"
          aria-label="Close modal"
        >
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Modal Body -->
      <div class="p-6 space-y-5 max-h-[75vh] overflow-y-auto">
        
        <!-- 1. Diagnosis Input with CDSS Autocomplete Combobox -->
        <div class="relative" bind:this={comboboxWrapper}>
          <div class="flex items-center justify-between mb-1.5">
            <label for="diagnosisInput" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
              Clinical Diagnosis <span class="text-rose-500">*</span>
            </label>
            <span class="text-[11px] font-medium text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200/80">
              ⚡ Smart CDSS Autocomplete
            </span>
          </div>

          <div class="relative">
            <input
              id="diagnosisInput"
              type="text"
              value={diagnosis}
              on:input={handleDiagnosisInput}
              on:keydown={handleKeyDown}
              on:focus={() => { if (suggestedGuidelines.length > 0) isOpenDropdown = true; }}
              placeholder="Type diagnosis (e.g. Hypertension, Migraine, Anxiety)..."
              autocomplete="off"
              class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-medium text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 shadow-sm transition-all"
            />

            {#if isLoading}
              <div class="absolute right-3.5 top-1/2 -translate-y-1/2 flex items-center gap-1.5 text-xs text-emerald-600">
                <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span class="text-[11px]">Searching CDSS...</span>
              </div>
            {/if}
          </div>

          <!-- Autocomplete Floating Dropdown -->
          {#if isOpenDropdown && suggestedGuidelines.length > 0}
            <div
              class="absolute left-0 right-0 top-full mt-1.5 max-h-64 overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-2xl z-50 divide-y divide-slate-100"
              role="listbox"
            >
              <div class="px-3 py-1.5 bg-slate-50 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                Recommended Guidelines ({suggestedGuidelines.length})
              </div>
              {#each suggestedGuidelines as item, idx}
                <div
                  role="option"
                  aria-selected={selectedIndex === idx}
                  tabindex="0"
                  on:click={() => selectGuideline(item)}
                  on:keydown={(e) => { if (e.key === 'Enter') selectGuideline(item); }}
                  class="cursor-pointer p-3 transition-colors hover:bg-emerald-50/80 {selectedIndex === idx ? 'bg-emerald-50 text-emerald-950' : 'text-slate-800'}"
                >
                  <div class="flex items-center justify-between gap-2">
                    <span class="text-sm font-bold text-slate-900">{item.diagnosis}</span>
                    <span class="shrink-0 text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200">
                      {item.department}
                    </span>
                  </div>
                  <p class="text-xs text-slate-600 mt-1 line-clamp-1">
                    <strong class="text-slate-700">Plan:</strong> {item.treatment_plan}
                  </p>
                  <div class="mt-1.5 flex items-center gap-2 text-xs text-emerald-700">
                    <span class="font-bold bg-emerald-100/60 px-1.5 py-0.5 rounded text-[11px]">Rx</span>
                    <span class="font-semibold text-slate-800">{item.medicine_name}</span>
                    <span class="text-slate-400">&bull;</span>
                    <span class="text-slate-600">{item.dosage_instructions}</span>
                    <span class="text-slate-400">&bull;</span>
                    <span class="text-slate-500 font-medium">{item.default_quantity}</span>
                  </div>
                </div>
              {/each}
            </div>
          {/if}
        </div>

        <!-- 2. Treatment Plan Textarea -->
        <div>
          <label for="treatmentPlanInput" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
            Treatment Plan &amp; Clinical Management
          </label>
          <textarea
            id="treatmentPlanInput"
            bind:value={treatmentPlan}
            rows="3"
            placeholder="Recommended clinical regimen, lifestyle modifications, or follow-up instructions..."
            class="w-full rounded-xl border border-slate-300 bg-white p-3 text-sm text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 shadow-sm leading-relaxed"
          ></textarea>
        </div>

        <!-- 3. Formulary Prescription Section (Auto-populated & Fully Editable) -->
        <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-4 space-y-3.5">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-emerald-800 flex items-center gap-1.5">
              <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
              </svg>
              Formulary Medicine &amp; Prescription
            </span>
            <span class="text-[11px] font-semibold text-slate-500 bg-white px-2 py-0.5 rounded-full border border-slate-200">
              Editable Regimen
            </span>
          </div>

          <div>
            <label for="medicineNameInput" class="block text-xs font-semibold text-slate-700 mb-1">
              Select Medicine / Drug
            </label>
            <input
              id="medicineNameInput"
              type="text"
              bind:value={medicineName}
              on:input={updateFullPrescriptionPreview}
              placeholder="e.g. Amlodipine 5mg tablet"
              class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 shadow-sm"
            />
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label for="dosageInput" class="block text-xs font-semibold text-slate-700 mb-1">
                Dosage / Instructions
              </label>
              <input
                id="dosageInput"
                type="text"
                bind:value={dosageInstructions}
                on:input={updateFullPrescriptionPreview}
                placeholder="e.g. 1 tablet once daily"
                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 shadow-sm"
              />
            </div>
            <div>
              <label for="quantityInput" class="block text-xs font-semibold text-slate-700 mb-1">
                Default Quantity
              </label>
              <input
                id="quantityInput"
                type="text"
                bind:value={quantity}
                on:input={updateFullPrescriptionPreview}
                placeholder="e.g. 30 tablets"
                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 shadow-sm"
              />
            </div>
          </div>

          <div>
            <label for="fullPrescriptionInput" class="block text-xs font-semibold text-slate-700 mb-1">
              Full Prescription Summary (Slip Output)
            </label>
            <textarea
              id="fullPrescriptionInput"
              bind:value={fullPrescription}
              rows="2"
              placeholder="Summary automatically formatted or manually edited by physician..."
              class="w-full rounded-lg border border-slate-300 bg-white p-2.5 text-xs text-slate-800 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 shadow-sm"
            ></textarea>
          </div>
        </div>

      </div>

      <!-- Modal Footer -->
      <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100 bg-slate-50/70">
        <button
          type="button"
          on:click={handleClose}
          class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors shadow-sm"
        >
          Cancel
        </button>
        <button
          type="button"
          on:click={handleSubmit}
          class="rounded-xl bg-emerald-600 px-5 py-2 text-sm font-bold text-white hover:bg-emerald-700 active:bg-emerald-800 transition-colors shadow-sm inline-flex items-center gap-2"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
          </svg>
          Save &amp; Complete Consultation
        </button>
      </div>

    </div>
  </div>
{/if}

