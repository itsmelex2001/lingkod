<script>
  export let data;
  export let errors = {};

  const days = [
    ['monday', 'Monday'],
    ['tuesday', 'Tuesday'],
    ['wednesday', 'Wednesday'],
    ['thursday', 'Thursday'],
    ['friday', 'Friday'],
    ['saturday', 'Saturday'],
    ['sunday', 'Sunday'],
  ];

  let newHolidayDate = '';
  let newHolidayLabel = '';
  let holidayError = '';

  $: holidays = Array.isArray(data.hospital_holidays) ? data.hospital_holidays : [];

  function err(key) {
    const list = errors?.[key];
    return Array.isArray(list) ? list[0] : list || '';
  }

  function addHoliday() {
    holidayError = '';
    const date = newHolidayDate.trim();
    const label = newHolidayLabel.trim();

    if (!/^\d{4}-\d{2}-\d{2}$/.test(date)) {
      holidayError = 'Select a valid date.';
      return;
    }
    if (!label) {
      holidayError = 'Enter a holiday or closure label.';
      return;
    }
    if (holidays.some((h) => h.date === date)) {
      holidayError = 'This date is already listed.';
      return;
    }

    data.hospital_holidays = [...holidays, { date, label }].sort((a, b) =>
      a.date.localeCompare(b.date)
    );
    newHolidayDate = '';
    newHolidayLabel = '';
  }

  function removeHoliday(date) {
    data.hospital_holidays = holidays.filter((h) => h.date !== date);
  }

  function formatDate(iso) {
    const dt = new Date(`${iso}T00:00:00`);
    if (Number.isNaN(dt.getTime())) return iso;
    return dt.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
  }
</script>

<div class="space-y-6">
  <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-xs">
    <div class="mb-5 border-b border-gray-100 pb-4">
      <h2 class="text-base font-bold text-gray-900">Hospital Contact Details</h2>
      <p class="mt-0.5 text-sm text-gray-500">
        Displayed across the landing page, patient slips, and emergency callouts.
      </p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <div class="sm:col-span-2">
        <label for="cfg-hosp-name" class="block text-sm font-semibold text-gray-700">
          Hospital Name
        </label>
        <input
          id="cfg-hosp-name"
          type="text"
          bind:value={data.hospital_name}
          class="mt-1.5 w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 focus:border-[#166534] focus:outline-none focus:ring-2 focus:ring-[#166534]/20"
        />
        {#if err('hospital_name')}
          <p class="mt-1 text-xs text-red-600">{err('hospital_name')}</p>
        {/if}
      </div>

      <div>
        <label for="cfg-phone" class="block text-sm font-semibold text-gray-700">
          Main Landline
        </label>
        <input
          id="cfg-phone"
          type="text"
          bind:value={data.contact_phone}
          placeholder="(047) 935-4645"
          class="mt-1.5 w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 focus:border-[#166534] focus:outline-none focus:ring-2 focus:ring-[#166534]/20"
        />
        {#if err('contact_phone')}
          <p class="mt-1 text-xs text-red-600">{err('contact_phone')}</p>
        {/if}
      </div>

      <div>
        <label for="cfg-hotline" class="block text-sm font-semibold text-gray-700">
          Emergency Hotline
        </label>
        <input
          id="cfg-hotline"
          type="text"
          bind:value={data.emergency_hotline}
          placeholder="(047) 935-4138"
          class="mt-1.5 w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 focus:border-[#166534] focus:outline-none focus:ring-2 focus:ring-[#166534]/20"
        />
        {#if err('emergency_hotline')}
          <p class="mt-1 text-xs text-red-600">{err('emergency_hotline')}</p>
        {/if}
      </div>

      <div class="sm:col-span-2">
        <label for="cfg-email" class="block text-sm font-semibold text-gray-700">
          Public Contact Email
        </label>
        <input
          id="cfg-email"
          type="email"
          bind:value={data.contact_email}
          placeholder="support@mmwgh.gov.ph"
          class="mt-1.5 w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 focus:border-[#166534] focus:outline-none focus:ring-2 focus:ring-[#166534]/20"
        />
        {#if err('contact_email')}
          <p class="mt-1 text-xs text-red-600">{err('contact_email')}</p>
        {/if}
      </div>

      <div class="sm:col-span-2">
        <label for="cfg-address" class="block text-sm font-semibold text-gray-700">
          Physical Address
        </label>
        <textarea
          id="cfg-address"
          rows="2"
          bind:value={data.hospital_address}
          class="mt-1.5 w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 focus:border-[#166534] focus:outline-none focus:ring-2 focus:ring-[#166534]/20"
        ></textarea>
        {#if err('hospital_address')}
          <p class="mt-1 text-xs text-red-600">{err('hospital_address')}</p>
        {/if}
      </div>
    </div>
  </section>

  <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-xs">
    <div class="mb-5 border-b border-gray-100 pb-4">
      <h2 class="text-base font-bold text-gray-900">Outpatient Clinic Hours</h2>
      <p class="mt-0.5 text-sm text-gray-500">
        Define standard weekly operating hours for OPD consultations. Closed days block online bookings.
      </p>
    </div>

    <div class="divide-y divide-gray-100">
      {#each days as [key, label] (key)}
        {#if data.clinic_hours?.[key]}
          <div class="flex flex-col gap-3 py-3 sm:flex-row sm:items-center sm:justify-between">
            <label class="inline-flex min-w-[140px] cursor-pointer items-center gap-3">
              <input
                type="checkbox"
                bind:checked={data.clinic_hours[key].is_open}
                class="h-4 w-4 rounded border-gray-300 text-[#166534] focus:ring-[#166534]"
              />
              <span class="text-sm font-semibold text-gray-800">{label}</span>
            </label>

            {#if data.clinic_hours[key].is_open}
              <div class="flex flex-wrap items-center gap-2">
                <input
                  type="time"
                  aria-label="{label} opening time"
                  bind:value={data.clinic_hours[key].open}
                  class="rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-[#166534] focus:outline-none focus:ring-2 focus:ring-[#166534]/20"
                />
                <span class="text-xs font-medium text-gray-400">to</span>
                <input
                  type="time"
                  aria-label="{label} closing time"
                  bind:value={data.clinic_hours[key].close}
                  class="rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-[#166534] focus:outline-none focus:ring-2 focus:ring-[#166534]/20"
                />
              </div>
            {:else}
              <span class="inline-flex w-fit rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-500">
                Closed
              </span>
            {/if}
          </div>
          {#if err(`clinic_hours.${key}.close`)}
            <p class="pb-2 text-xs text-red-600">{err(`clinic_hours.${key}.close`)}</p>
          {/if}
        {/if}
      {/each}
    </div>
  </section>

  <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-xs">
    <div class="mb-5 border-b border-gray-100 pb-4">
      <h2 class="text-base font-bold text-gray-900">Hospital Holidays &amp; Closed Dates</h2>
      <p class="mt-0.5 text-sm text-gray-500">
        Block online OPD booking on public holidays or scheduled hospital closures.
      </p>
    </div>

    <div class="flex flex-col gap-2.5 sm:flex-row sm:items-end">
      <div class="sm:w-44">
        <label for="cfg-holiday-date" class="block text-xs font-semibold text-gray-700">Date</label>
        <input
          id="cfg-holiday-date"
          type="date"
          bind:value={newHolidayDate}
          class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-[#166534] focus:outline-none focus:ring-2 focus:ring-[#166534]/20"
        />
      </div>
      <div class="flex-1">
        <label for="cfg-holiday-label" class="block text-xs font-semibold text-gray-700">Holiday / Closure Name</label>
        <input
          id="cfg-holiday-label"
          type="text"
          maxlength="120"
          placeholder="e.g. Christmas Day"
          bind:value={newHolidayLabel}
          on:keydown={(e) => { if (e.key === 'Enter') { e.preventDefault(); addHoliday(); } }}
          class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-[#166534] focus:outline-none focus:ring-2 focus:ring-[#166534]/20"
        />
      </div>
      <button
        type="button"
        on:click={addHoliday}
        class="inline-flex shrink-0 cursor-pointer items-center justify-center rounded-lg border border-[#166534] bg-[#f0fdf4] px-4 py-2 text-sm font-semibold text-[#166534] transition hover:bg-[#166534] hover:text-white active:scale-[0.98]"
      >
        + Add Date
      </button>
    </div>

    {#if holidayError}
      <p class="mt-2 text-xs text-red-600" role="alert">{holidayError}</p>
    {/if}

    {#if holidays.length === 0}
      <p class="mt-4 rounded-lg border border-dashed border-gray-200 py-5 text-center text-xs text-gray-500">
        No holidays or special closures scheduled.
      </p>
    {:else}
      <ul class="mt-4 divide-y divide-gray-100 rounded-lg border border-gray-100">
        {#each holidays as item (item.date)}
          <li class="flex items-center justify-between gap-3 px-3.5 py-2.5 text-sm">
            <div class="flex min-w-0 items-center gap-3">
              <span class="shrink-0 rounded-md bg-[#f0fdf4] px-2.5 py-1 font-mono text-xs font-semibold text-[#166534]">
                {formatDate(item.date)}
              </span>
              <span class="truncate font-medium text-gray-800">{item.label}</span>
            </div>
            <button
              type="button"
              on:click={() => removeHoliday(item.date)}
              aria-label="Remove {item.label}"
              class="shrink-0 cursor-pointer rounded-md px-2.5 py-1 text-xs font-semibold text-red-600 transition hover:bg-red-50"
            >
              Remove
            </button>
          </li>
        {/each}
      </ul>
    {/if}
  </section>
</div>
