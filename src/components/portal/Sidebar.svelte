<script>
  export let activeTab = 'dashboard';
  export let upcomingCount = 0;
  export let onSelectTab = (tab) => {};
  export let mobileOpen = false;
  export let onCloseMobile = () => {};
</script>

{#if mobileOpen}
  <button
    type="button"
    class="fixed inset-0 z-40 bg-slate-900/40 md:hidden border-0 cursor-default"
    aria-label="Close menu"
    on:click={onCloseMobile}
  ></button>
{/if}

<aside
  class="w-64 h-screen fixed md:sticky top-0 left-0 bg-white border-r border-[#e5e7eb] flex flex-col justify-between p-5 shrink-0 z-50 overflow-hidden transition-transform duration-200 {mobileOpen ? 'translate-x-0' : '-translate-x-full'} md:translate-x-0"
  style="width: 260px; min-width: 260px;"
>
  <div>
    <!-- Hospital Brand Header -->
    <div class="flex items-center gap-3 pb-5 border-b border-[#f3f4f6]" style="display: flex; align-items: center; gap: 12px; padding-bottom: 20px; border-bottom: 1px solid #f3f4f6;">
      <div class="w-11 h-11 flex items-center justify-center shrink-0" style="width: 44px; height: 44px; flex-shrink: 0; display: flex; align-items: center; justify-content: center;">
        <img
          src="/sdn/public/assets/v3_lingkod_logo_green.png"
          alt="MMWGH LINGKOD"
          width="44"
          height="44"
          style="width: 44px; height: 44px; max-width: 44px; max-height: 44px; object-fit: contain; display: block;"
        />
      </div>
      <div class="flex flex-col min-w-0" style="display: flex; flex-direction: column; min-width: 0;">
        <span class="text-[10px] leading-tight font-medium text-[#6b7280] truncate" style="font-size: 10px; color: #6b7280; line-height: 1.2;">
          Mariveles Mental Wellness & General Hospital
        </span>
      </div>
    </div>

    <!-- Navigation List -->
    <nav class="flex flex-col gap-1 mt-5" style="display: flex; flex-direction: column; gap: 4px; margin-top: 20px;" aria-label="Portal Navigation">
      <button
        type="button"
        class="flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium transition text-left {activeTab === 'dashboard' ? 'bg-[#f0fdf4] text-[#166534] font-semibold' : 'text-[#4b5563] hover:bg-[#f9fafb] hover:text-[#111827]'}"
        style="width: 100%; border: none; cursor: pointer;"
        on:click={() => onSelectTab('dashboard')}
      >
        <span class="flex items-center gap-3" style="display: flex; align-items: center; gap: 12px;">
          <svg width="16" height="16" class="w-4 h-4 text-current" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 16px; height: 16px; flex-shrink: 0;">
            <rect x="3" y="3" width="7" height="7"></rect>
            <rect x="14" y="3" width="7" height="7"></rect>
            <rect x="14" y="14" width="7" height="7"></rect>
            <rect x="3" y="14" width="7" height="7"></rect>
          </svg>
          Dashboard
        </span>
      </button>

      <button
        type="button"
        class="flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium transition text-left {activeTab === 'appointments' ? 'bg-[#f0fdf4] text-[#166534] font-semibold' : 'text-[#4b5563] hover:bg-[#f9fafb] hover:text-[#111827]'}"
        style="width: 100%; border: none; cursor: pointer;"
        on:click={() => onSelectTab('appointments')}
      >
        <span class="flex items-center gap-3" style="display: flex; align-items: center; gap: 12px;">
          <svg width="16" height="16" class="w-4 h-4 text-current" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 16px; height: 16px; flex-shrink: 0;">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
            <line x1="16" y1="2" x2="16" y2="6"></line>
            <line x1="8" y1="2" x2="8" y2="6"></line>
            <line x1="3" y1="10" x2="21" y2="10"></line>
          </svg>
          Appointments
        </span>
        {#if upcomingCount > 0}
          <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-[#166534] text-white leading-none" style="padding: 2px 8px; border-radius: 9999px; background: #166534; color: white; font-size: 11px;">
            {upcomingCount}
          </span>
        {/if}
      </button>

      <button
        type="button"
        class="flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium transition text-left {activeTab === 'medications' ? 'bg-[#f0fdf4] text-[#166534] font-semibold' : 'text-[#4b5563] hover:bg-[#f9fafb] hover:text-[#111827]'}"
        style="width: 100%; border: none; cursor: pointer;"
        on:click={() => onSelectTab('medications')}
      >
        <span class="flex items-center gap-3" style="display: flex; align-items: center; gap: 12px;">
          <svg width="16" height="16" class="w-4 h-4 text-current" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 16px; height: 16px; flex-shrink: 0;">
            <path d="m10.5 20.5 10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7Z"></path>
            <path d="m8.5 8.5 7 7"></path>
          </svg>
          Medications
        </span>
      </button>

      <button
        type="button"
        class="flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium transition text-left {activeTab === 'settings' ? 'bg-[#f0fdf4] text-[#166534] font-semibold' : 'text-[#4b5563] hover:bg-[#f9fafb] hover:text-[#111827]'}"
        style="width: 100%; border: none; cursor: pointer;"
        on:click={() => onSelectTab('settings')}
      >
        <span class="flex items-center gap-3" style="display: flex; align-items: center; gap: 12px;">
          <svg width="16" height="16" class="w-4 h-4 text-current" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 16px; height: 16px; flex-shrink: 0;">
            <circle cx="12" cy="12" r="3"></circle>
            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
          </svg>
          Settings
        </span>
      </button>
    </nav>
  </div>

  <!-- Clinical & Triage Assistance Footer -->
  <div class="pt-4 border-t border-[#f3f4f6] text-[11px] text-[#6b7280] leading-relaxed" style="padding-top: 16px; border-top: 1px solid #f3f4f6; font-size: 11px; color: #6b7280;">
    <div class="font-medium text-[#374151] mb-1 flex items-center gap-1.5" style="display: flex; align-items: center; gap: 6px; font-weight: 600; color: #374151; margin-bottom: 4px;">
      <span class="w-1.5 h-1.5 rounded-full bg-[#166534]" style="width: 6px; height: 6px; border-radius: 50%; background: #166534; display: inline-block;"></span>
      Outpatient Triage
    </div>
    <p style="margin: 0;">Mon–Fri, 8:00 AM – 5:00 PM</p>
    <p style="margin-top: 4px; margin-bottom: 0;">
      Hotline: <a href="tel:0479354138" class="text-[#166534] hover:underline font-medium" style="color: #166534; font-weight: 600; text-decoration: none;">(047) 935-4138</a>
    </p>
    <p class="text-[10px] text-[#9ca3af] mt-1.5" style="font-size: 10px; color: #9ca3af; margin-top: 6px; margin-bottom: 0;">For urgent medical emergencies, dial 911.</p>
  </div>
</aside>
