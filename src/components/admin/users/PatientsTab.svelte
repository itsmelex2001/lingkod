<script>
  import { createEventDispatcher } from 'svelte';
  import { toastStore } from '../../../stores/toastStore.js';

  export let patients = [];
  export let loading = false;

  const dispatch = createEventDispatcher();

  let query = '';
  let statusFilter = 'all';

  $: filtered = patients.filter((p) => {
    const status = (p.account_status || 'active').toLowerCase();
    if (statusFilter !== 'all' && status !== statusFilter) return false;
    const q = query.trim().toLowerCase();
    if (!q) return true;
    return (
      (p.full_name || '').toLowerCase().includes(q) ||
      (p.username || '').toLowerCase().includes(q)
    );
  });

  function resetFilters() {
    query = '';
    statusFilter = 'all';
  }

  function initialsOf(name) {
    const parts = String(name || 'P').trim().split(/\s+/).filter(Boolean);
    return parts.length > 1
      ? (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
      : (parts[0]?.[0] || 'P').toUpperCase();
  }

  async function copyUsername(username) {
    if (!username) return;
    try {
      await navigator.clipboard.writeText(username);
      toastStore.add(`Copied username "${username}"`, 'info', 2000);
    } catch {
      // Ignore clipboard permission denial
    }
  }
</script>

<div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
  <div class="um-toolbar">
    <div class="um-patient-search">
      <svg class="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
        <circle cx="11" cy="11" r="8" /><line x1="21" y1="21" x2="16.65" y2="16.65" />
      </svg>
      <input
        type="search"
        bind:value={query}
        placeholder="Search walk-in patient by real name or username…"
        class="h-9 w-full rounded-lg border border-gray-200 bg-white pl-8 pr-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-[#166534] focus:outline-none focus:ring-2 focus:ring-[#166534]/15"
      />
    </div>

    <div class="um-toolbar-controls">
      <select
        bind:value={statusFilter}
        aria-label="Filter patient account status"
        class="h-9 shrink-0 rounded-lg border border-gray-200 bg-white px-2.5 text-sm text-gray-700 focus:border-[#166534] focus:outline-none focus:ring-2 focus:ring-[#166534]/15"
      >
        <option value="all">All Statuses</option>
        <option value="active">Active</option>
        <option value="suspended">Suspended</option>
      </select>
      {#if query.trim() !== '' || statusFilter !== 'all'}
        <button
          type="button"
          on:click={resetFilters}
          class="h-9 shrink-0 cursor-pointer rounded-lg border border-gray-200 bg-white px-3 text-xs font-medium text-gray-600 transition hover:bg-gray-50 hover:text-gray-900"
        >
          Reset
        </button>
      {/if}
    </div>
  </div>

  <div class="overflow-x-auto">
    <table class="w-full border-collapse text-left text-sm">
      <thead class="border-b border-gray-200 bg-gray-50/90 text-[11px] font-semibold uppercase tracking-wider text-gray-500">
        <tr>
          <th class="whitespace-nowrap px-5 py-3">Patient</th>
          <th class="whitespace-nowrap px-5 py-3">Username</th>
          <th class="whitespace-nowrap px-5 py-3">Email &amp; OTP</th>
          <th class="whitespace-nowrap px-5 py-3">Status</th>
          <th class="whitespace-nowrap px-5 py-3 text-right">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100">
        {#if loading}
          <tr>
            <td colspan="5" class="px-5 py-10 text-center text-sm text-gray-500">Loading patient accounts…</td>
          </tr>
        {:else if filtered.length === 0}
          <tr>
            <td colspan="5" class="px-5 py-10 text-center text-sm text-gray-500">No patient portal accounts found.</td>
          </tr>
        {:else}
          {#each filtered as patient (patient.id)}
            {@const isSuspended = (patient.account_status || 'active').toLowerCase() === 'suspended'}
            <tr class="transition-colors hover:bg-[#f0fdf4]/45">
              <td class="whitespace-nowrap px-5 py-3.5">
                <div class="flex items-center gap-3">
                  <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-[#bbf7d0] bg-[#f0fdf4] text-xs font-semibold text-[#166534]">
                    {initialsOf(patient.full_name)}
                  </span>
                  <div>
                    <div class="font-semibold text-gray-900">{patient.full_name}</div>
                    <div class="font-mono text-[11px] text-gray-400">{patient.patient_id}</div>
                  </div>
                </div>
              </td>
              <td class="whitespace-nowrap px-5 py-3.5">
                <button
                  type="button"
                  on:click={() => copyUsername(patient.username)}
                  title="Copy username"
                  class="group inline-flex cursor-pointer items-center gap-1.5 rounded-md border border-gray-200 bg-gray-50/80 px-2 py-1 font-mono text-xs text-gray-700 transition hover:border-[#bbf7d0] hover:bg-[#f0fdf4] hover:text-[#166534]"
                >
                  <span>{patient.username}</span>
                  <svg class="h-3 w-3 text-gray-400 transition group-hover:text-[#166534]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2" />
                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
                  </svg>
                </button>
              </td>
              <td class="px-5 py-3.5">
                <div class="max-w-[240px] truncate text-sm text-gray-800" title={patient.email || ''}>
                  {patient.email || '—'}
                </div>
                <div class="mt-0.5 flex items-center gap-1 text-[11px] font-medium {patient.is_verified ? 'text-[#166534]' : 'text-amber-600'}">
                  {#if patient.is_verified}
                    <svg class="h-3 w-3 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                      <polyline points="20 6 9 17 4 12" />
                    </svg>
                    <span>OTP Verified</span>
                  {:else}
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                    <span>OTP Re-verification Pending</span>
                  {/if}
                </div>
              </td>
              <td class="whitespace-nowrap px-5 py-3.5">
                <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium {isSuspended
                  ? 'border-red-200 bg-red-50 text-red-700'
                  : 'border-[#bbf7d0] bg-[#f0fdf4] text-[#166534]'}">
                  <span class="h-1.5 w-1.5 rounded-full {isSuspended ? 'bg-red-600' : 'bg-[#16a34a]'}"></span>
                  {isSuspended ? 'Suspended' : 'Active'}
                </span>
              </td>
              <td class="whitespace-nowrap px-5 py-3.5 text-right">
                <div class="inline-flex items-center justify-end gap-1.5">
                  <button
                    type="button"
                    on:click={() => dispatch('tempPassword', patient)}
                    title="Generate temporary password"
                    aria-label="Generate temporary password for {patient.full_name}"
                    class="inline-flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg border border-[#bbf7d0] bg-[#f0fdf4] text-[#166534] transition hover:bg-[#dcfce7] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#166534]"
                  >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                      <circle cx="7.5" cy="15.5" r="4.5" />
                      <path d="m21 2-9.6 9.6" />
                      <path d="m15.5 7.5 3 3L22 7l-3-3" />
                    </svg>
                  </button>
                  <button
                    type="button"
                    on:click={() => dispatch('editEmail', patient)}
                    title="Edit email (requires OTP re-verification)"
                    aria-label="Edit email for {patient.full_name}"
                    class="inline-flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#166534]"
                  >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                      <rect x="2" y="4" width="20" height="16" rx="2" />
                      <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
                    </svg>
                  </button>
                  <button
                    type="button"
                    on:click={() => dispatch('toggleSuspend', patient)}
                    title={isSuspended ? 'Unlock patient account' : 'Suspend patient account'}
                    aria-label={isSuspended ? `Unlock ${patient.full_name}` : `Suspend ${patient.full_name}`}
                    class="inline-flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg border transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#166534] {isSuspended
                      ? 'border-[#bbf7d0] bg-white text-[#166534] hover:bg-[#f0fdf4]'
                      : 'border-gray-200 bg-white text-gray-500 hover:border-red-200 hover:bg-red-50 hover:text-red-600'}"
                  >
                    {#if isSuspended}
                      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                        <path d="M7 11V7a5 5 0 0 1 9.9-1" />
                      </svg>
                    {:else}
                      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                        <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                      </svg>
                    {/if}
                  </button>
                </div>
              </td>
            </tr>
          {/each}
        {/if}
      </tbody>
    </table>
  </div>

  <div class="flex items-center justify-between border-t border-gray-100 bg-gray-50/70 px-5 py-2.5 text-xs text-gray-500">
    <span>Showing {filtered.length} of {patients.length} patient {patients.length === 1 ? 'account' : 'accounts'}</span>
    <span class="hidden items-center gap-3 text-[11px] text-gray-400 sm:inline-flex">
      <span>Temp Password</span>
      <span>&middot;</span>
      <span>Edit Email</span>
      <span>&middot;</span>
      <span>Suspend / Unlock</span>
    </span>
  </div>
</div>

<style>
  .um-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px 18px;
    border-bottom: 1px solid #e5e7eb;
  }

  .um-patient-search {
    position: relative;
    width: 340px;
    max-width: 100%;
  }

  .um-toolbar-controls {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-left: auto;
  }

  @media (max-width: 640px) {
    .um-patient-search {
      width: 100%;
    }

    .um-toolbar-controls {
      width: 100%;
      margin-left: 0;
    }
  }
</style>
