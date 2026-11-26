<script>
  import { createEventDispatcher } from 'svelte';

  export let staff = [];
  export let loading = false;

  const dispatch = createEventDispatcher();

  let deptFilter = 'all';
  let statusFilter = 'all';
  let query = '';

  const roleLabels = {
    superadmin: 'Super Admin',
    admin: 'Administrator',
    staff: 'Medical Staff',
  };

  const deptTabs = [
    { key: 'all', label: 'All' },
    { key: 'general', label: 'General Medical' },
    { key: 'neurology', label: 'Neurology' },
    { key: 'psychiatry', label: 'Psychiatry' },
  ];

  function matchesDept(department, key) {
    if (key === 'all') return true;
    const d = (department || '').toLowerCase();
    if (key === 'general') return d.includes('general') || d.includes('medical');
    if (key === 'neurology') return d.includes('neuro');
    if (key === 'psychiatry') return d.includes('psych') || d.includes('opu');
    return true;
  }

  $: deptCounts = {
    all: staff.length,
    general: staff.filter((s) => matchesDept(s.department, 'general')).length,
    neurology: staff.filter((s) => matchesDept(s.department, 'neurology')).length,
    psychiatry: staff.filter((s) => matchesDept(s.department, 'psychiatry')).length,
  };

  $: filtered = staff.filter((s) => {
    if (!matchesDept(s.department, deptFilter)) return false;
    if (statusFilter !== 'all' && (s.status || 'active').toLowerCase() !== statusFilter) return false;
    const q = query.trim().toLowerCase();
    if (!q) return true;
    return (
      (s.full_name || '').toLowerCase().includes(q) ||
      (s.username || '').toLowerCase().includes(q) ||
      (s.department || '').toLowerCase().includes(q)
    );
  });

  function resetFilters() {
    deptFilter = 'all';
    statusFilter = 'all';
    query = '';
  }

  function initialsOf(name) {
    const parts = String(name || 'S').trim().split(/\s+/).filter(Boolean);
    return parts.length > 1
      ? (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
      : (parts[0]?.[0] || 'S').toUpperCase();
  }
</script>

<div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
  <div class="um-toolbar">
    <div class="um-dept-tabs" role="tablist" aria-label="Filter staff by department">
      {#each deptTabs as tab (tab.key)}
        {@const active = deptFilter === tab.key}
        <button
          type="button"
          role="tab"
          aria-selected={active}
          on:click={() => (deptFilter = tab.key)}
          class="inline-flex cursor-pointer items-center gap-1.5 whitespace-nowrap rounded-md px-2.5 py-1.5 text-xs font-medium transition {active
            ? 'bg-white font-semibold text-[#166534] ring-1 ring-gray-200/90'
            : 'text-gray-600 hover:text-gray-900'}"
        >
          <span>{tab.label}</span>
          <span class="rounded-full px-1.5 py-0.25 text-[11px] tabular-nums {active ? 'bg-[#f0fdf4] text-[#166534]' : 'bg-gray-200/80 text-gray-600'}">
            {deptCounts[tab.key]}
          </span>
        </button>
      {/each}
    </div>

    <div class="um-toolbar-controls">
      <div class="um-search-wrap">
        <svg class="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
          <circle cx="11" cy="11" r="8" /><line x1="21" y1="21" x2="16.65" y2="16.65" />
        </svg>
        <input
          type="search"
          bind:value={query}
          placeholder="Search name or username…"
          class="h-9 w-full rounded-lg border border-gray-200 bg-white pl-8 pr-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-[#166534] focus:outline-none focus:ring-2 focus:ring-[#166534]/15"
        />
      </div>
      <select
        bind:value={statusFilter}
        aria-label="Filter staff status"
        class="h-9 shrink-0 rounded-lg border border-gray-200 bg-white px-2.5 text-sm text-gray-700 focus:border-[#166534] focus:outline-none focus:ring-2 focus:ring-[#166534]/15"
      >
        <option value="all">All Statuses</option>
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
      </select>
      {#if deptFilter !== 'all' || statusFilter !== 'all' || query.trim() !== ''}
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
          <th class="whitespace-nowrap px-5 py-3">Staff Member</th>
          <th class="whitespace-nowrap px-5 py-3">Role</th>
          <th class="whitespace-nowrap px-5 py-3">Department</th>
          <th class="whitespace-nowrap px-5 py-3">Status</th>
          <th class="whitespace-nowrap px-5 py-3 text-right">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100">
        {#if loading}
          <tr>
            <td colspan="5" class="px-5 py-10 text-center text-sm text-gray-500">Loading staff accounts…</td>
          </tr>
        {:else if filtered.length === 0}
          <tr>
            <td colspan="5" class="px-5 py-10 text-center text-sm text-gray-500">No staff accounts match the current filters.</td>
          </tr>
        {:else}
          {#each filtered as member (member.id)}
            {@const isInactive = (member.status || 'active').toLowerCase() !== 'active'}
            <tr class="transition-colors hover:bg-[#f0fdf4]/45">
              <td class="px-5 py-3.5">
                <div class="flex items-center gap-3">
                  {#if member.profile_image}
                    <img src={member.profile_image} alt="" class="h-9 w-9 shrink-0 rounded-full border border-gray-200 object-cover" />
                  {:else}
                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#166534] text-xs font-semibold text-white">
                      {initialsOf(member.full_name)}
                    </span>
                  {/if}
                  <div class="min-w-0">
                    <div class="truncate font-semibold text-gray-900">{member.full_name}</div>
                    <div class="font-mono text-xs text-gray-500">@{member.username}</div>
                  </div>
                </div>
              </td>
              <td class="whitespace-nowrap px-5 py-3.5">
                <span class="inline-flex items-center rounded-md border px-2.5 py-0.5 text-xs font-medium {member.role === 'superadmin'
                  ? 'border-[#166534] bg-[#166534] text-white'
                  : member.role === 'admin'
                    ? 'border-[#bbf7d0] bg-[#f0fdf4] text-[#166534]'
                    : 'border-gray-200 bg-gray-50 text-gray-700'}">
                  {roleLabels[member.role] || member.role}
                </span>
              </td>
              <td class="whitespace-nowrap px-5 py-3.5 text-sm text-gray-700">{member.department || 'Unassigned'}</td>
              <td class="whitespace-nowrap px-5 py-3.5">
                <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium {isInactive
                  ? 'border-red-200 bg-red-50 text-red-700'
                  : 'border-[#bbf7d0] bg-[#f0fdf4] text-[#166534]'}">
                  <span class="h-1.5 w-1.5 rounded-full {isInactive ? 'bg-red-500' : 'bg-[#16a34a]'}"></span>
                  {isInactive ? 'Inactive' : 'Active'}
                </span>
              </td>
              <td class="whitespace-nowrap px-5 py-3.5 text-right">
                <div class="inline-flex items-center justify-end gap-1.5">
                  <button
                    type="button"
                    on:click={() => dispatch('edit', member)}
                    title="Edit staff role & details"
                    aria-label="Edit {member.full_name}"
                    class="inline-flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#166534]"
                  >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                      <path d="M12 20h9" />
                      <path d="M16.5 3.5a2.121 2.121 0 1 1 3 3L7 19l-4 1 1-4L16.5 3.5z" />
                    </svg>
                  </button>
                  <button
                    type="button"
                    on:click={() => dispatch('resetPassword', member)}
                    title="Reset password"
                    aria-label="Reset password for {member.full_name}"
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
                    on:click={() => dispatch('toggleStatus', member)}
                    title={isInactive ? 'Activate staff account' : 'Deactivate staff account'}
                    aria-label={isInactive ? `Activate ${member.full_name}` : `Deactivate ${member.full_name}`}
                    class="inline-flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg border transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#166534] {isInactive
                      ? 'border-[#bbf7d0] bg-white text-[#166534] hover:bg-[#f0fdf4]'
                      : 'border-gray-200 bg-white text-gray-500 hover:border-red-200 hover:bg-red-50 hover:text-red-600'}"
                  >
                    {#if isInactive}
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
    <span>Showing {filtered.length} of {staff.length} staff {staff.length === 1 ? 'member' : 'members'}</span>
    <span class="hidden items-center gap-3 text-[11px] text-gray-400 sm:inline-flex">
      <span>Edit</span>
      <span>&middot;</span>
      <span>Reset Password</span>
      <span>&middot;</span>
      <span>Lock / Unlock</span>
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

  .um-dept-tabs {
    display: inline-flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 4px;
    border-radius: 8px;
    background: #f3f4f6;
    padding: 4px;
  }

  .um-toolbar-controls {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-left: auto;
  }

  .um-search-wrap {
    position: relative;
    width: 220px;
  }

  @media (max-width: 768px) {
    .um-toolbar-controls {
      width: 100%;
      margin-left: 0;
    }

    .um-search-wrap {
      flex: 1 1 auto;
      width: auto;
    }
  }
</style>
