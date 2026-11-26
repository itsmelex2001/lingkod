<script>
  export let data;
  export let errors = {};

  function err(key) {
    const list = errors?.[key];
    return Array.isArray(list) ? list[0] : list || '';
  }
</script>

<div class="space-y-6">
  <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-xs">
    <div class="mb-5 border-b border-gray-100 pb-4">
      <h2 class="text-base font-bold text-gray-900">QR Appointment Slip Expiry</h2>
      <p class="mt-0.5 text-sm text-gray-500">
        Default validity window before an unused appointment QR code expires.
      </p>
    </div>

    <div class="max-w-xs">
      <label for="cfg-qr-expiry" class="block text-sm font-semibold text-gray-700">
        Default QR Expiry (Hours)
      </label>
      <input
        id="cfg-qr-expiry"
        type="number"
        min="1"
        max="720"
        bind:value={data.qr_code_expiry_hours}
        class="mt-1.5 w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 focus:border-[#166534] focus:outline-none focus:ring-2 focus:ring-[#166534]/20"
      />
      <p class="mt-1 text-xs text-gray-500">Allowed range: 1 to 720 hours (30 days).</p>
      {#if err('qr_code_expiry_hours')}
        <p class="mt-1 text-xs text-red-600">{err('qr_code_expiry_hours')}</p>
      {/if}
    </div>
  </section>

  <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-xs">
    <div class="mb-5 border-b border-gray-100 pb-4">
      <h2 class="text-base font-bold text-gray-900">Department Capacity Limits</h2>
      <p class="mt-0.5 text-sm text-gray-500">
        Configure daily booking caps, cut-off times, and per-department QR expiry overrides.
      </p>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full min-w-[640px] border-collapse text-left text-sm">
        <thead>
          <tr class="border-b border-gray-200 text-xs font-semibold uppercase tracking-wide text-gray-500">
            <th class="pb-3 pr-4">Department</th>
            <th class="pb-3 px-2">Daily Cap</th>
            <th class="pb-3 px-2">Cut-off</th>
            <th class="pb-3 px-2">QR Override (h)</th>
            <th class="pb-3 pl-2 text-center">Same-Day</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          {#each data.departments as dept, i (dept.department_code)}
            <tr>
              <td class="py-3.5 pr-4">
                <div class="font-semibold text-gray-900">{dept.display_name}</div>
                <div class="text-xs text-gray-500 uppercase">{dept.department_code}</div>
              </td>
              <td class="py-3.5 px-2">
                <input
                  type="number"
                  min="1"
                  max="500"
                  aria-label="{dept.display_name} daily capacity"
                  bind:value={dept.daily_capacity_limit}
                  class="w-24 rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-[#166534] focus:outline-none focus:ring-2 focus:ring-[#166534]/20"
                />
                {#if err(`departments.${i}.daily_capacity_limit`)}
                  <p class="mt-1 text-xs text-red-600">{err(`departments.${i}.daily_capacity_limit`)}</p>
                {/if}
              </td>
              <td class="py-3.5 px-2">
                <input
                  type="time"
                  aria-label="{dept.display_name} cut-off time"
                  bind:value={dept.booking_cutoff_time}
                  class="rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-[#166534] focus:outline-none focus:ring-2 focus:ring-[#166534]/20"
                />
              </td>
              <td class="py-3.5 px-2">
                <input
                  type="number"
                  min="1"
                  max="720"
                  placeholder="Default"
                  aria-label="{dept.display_name} QR expiry override"
                  bind:value={dept.qr_expiry_hours_override}
                  class="w-24 rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-[#166534] focus:outline-none focus:ring-2 focus:ring-[#166534]/20"
                />
              </td>
              <td class="py-3.5 pl-2 text-center">
                <input
                  type="checkbox"
                  aria-label="Allow same-day booking for {dept.display_name}"
                  bind:checked={dept.allow_same_day_booking}
                  class="h-4 w-4 rounded border-gray-300 text-[#166534] focus:ring-[#166534]"
                />
              </td>
            </tr>
          {/each}
        </tbody>
      </table>
    </div>
  </section>
</div>
