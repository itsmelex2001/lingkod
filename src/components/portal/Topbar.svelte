<script>
  export let patientName = 'Alexu Gallardo';
  export let patientId = 'PT-2026-082';
  export let notifications = [];
  export let onToggleMobile = () => {};
  export let onSelectSettings = () => {};

  let showUserMenu = false;
  let showNotifications = false;

  $: initials = (() => {
    if (!patientName) return 'PT';
    const parts = patientName.trim().split(/\s+/).filter(Boolean);
    if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
  })();

  $: unreadCount = notifications.length;

  function handleLogout() {
    window.location.href = '/sdn/api/patient_logout.php';
  }

  function handleWindowClick() {
    showUserMenu = false;
    showNotifications = false;
  }
</script>

<svelte:window on:click={handleWindowClick} />

<header class="h-16 bg-white border-b border-[#e5e7eb] px-6 flex items-center justify-between sticky top-0 z-30" style="height: 64px; background: #ffffff; border-bottom: 1px solid #e5e7eb; padding: 0 24px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 30;">
  <!-- Left Side: Mobile toggle + Patient Identification Chip -->
  <div class="flex items-center gap-3" style="display: flex; align-items: center; gap: 12px;">
    <button
      type="button"
      class="p-2 -ml-2 rounded-lg text-[#4b5563] hover:bg-[#f3f4f6] md:hidden"
      aria-label="Toggle navigation"
      style="border: none; background: transparent; cursor: pointer; padding: 8px;"
      on:click|stopPropagation={onToggleMobile}
    >
      <svg width="20" height="20" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 20px; height: 20px;">
        <line x1="3" y1="12" x2="21" y2="12"></line>
        <line x1="3" y1="6" x2="21" y2="6"></line>
        <line x1="3" y1="18" x2="21" y2="18"></line>
      </svg>
    </button>

    <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-md bg-[#f9fafb] border border-[#e5e7eb] text-xs font-mono text-[#374151]" style="display: inline-flex; align-items: center; gap: 8px; padding: 4px 10px; border-radius: 6px; background: #f9fafb; border: 1px solid #e5e7eb; font-size: 12px; font-family: monospace;">
      <span class="w-1.5 h-1.5 rounded-full bg-[#166534]" style="width: 6px; height: 6px; border-radius: 50%; background: #166534; display: inline-block;"></span>
      <span class="text-[#6b7280] font-sans font-medium" style="color: #6b7280; font-family: sans-serif; font-weight: 500;">Patient ID</span>
      <span class="font-semibold text-[#111827]" style="font-weight: 700; color: #111827;">{patientId || 'PT-2026-082'}</span>
    </div>
  </div>

  <!-- Right Side: Notification Bell + User Avatar Menu -->
  <div class="flex items-center gap-3" style="display: flex; align-items: center; gap: 12px;">
    <!-- Notification Bell -->
    <div class="relative" style="position: relative;">
      <button
        type="button"
        class="relative p-2 rounded-lg text-[#4b5563] hover:bg-[#f3f4f6] transition"
        style="position: relative; border: none; background: transparent; cursor: pointer; padding: 8px; border-radius: 8px; display: flex; align-items: center; justify-content: center;"
        aria-label="View notifications"
        on:click|stopPropagation={() => {
          showNotifications = !showNotifications;
          showUserMenu = false;
        }}
      >
        <svg width="20" height="20" class="w-5 h-5 text-[#4b5563]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 20px; height: 20px; color: #4b5563;">
          <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
          <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
        </svg>
        {#if unreadCount > 0}
          <span class="absolute top-1.5 right-1.5 flex h-2 w-2" style="position: absolute; top: 6px; right: 6px; width: 8px; height: 8px;">
            <span class="relative inline-flex rounded-full h-2 w-2 bg-[#dc2626]" style="width: 8px; height: 8px; border-radius: 50%; background: #dc2626; display: inline-block;"></span>
          </span>
        {/if}
      </button>

      {#if showNotifications}
        <div
          class="absolute right-0 mt-2 w-80 bg-white rounded-lg border border-[#e5e7eb] shadow-lg py-2 z-50 text-left"
          role="dialog"
          aria-label="Notifications"
          tabindex="-1"
          style="position: absolute; right: 0; top: 100%; margin-top: 8px; width: 320px; background: white; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); padding: 8px 0; z-index: 50; text-align: left;"
          on:click|stopPropagation
          on:keydown|stopPropagation
        >
          <div class="px-4 py-2 border-b border-[#f3f4f6] flex items-center justify-between" style="padding: 8px 16px; border-bottom: 1px solid #f3f4f6; display: flex; justify-content: space-between;">
            <span class="text-xs font-semibold text-[#111827]" style="font-size: 12px; font-weight: 700; color: #111827;">Notifications</span>
            <span class="text-[11px] text-[#6b7280]" style="font-size: 11px; color: #6b7280;">{unreadCount} active</span>
          </div>

          <div class="max-h-72 overflow-y-auto" style="max-height: 280px; overflow-y: auto;">
            {#if notifications.length === 0}
              <div class="py-6 px-4 text-center text-xs text-[#6b7280]" style="padding: 24px 16px; text-align: center; font-size: 12px; color: #6b7280;">
                No new notifications
              </div>
            {:else}
              {#each notifications as notif}
                <div class="px-4 py-3 hover:bg-[#f9fafb] border-b border-[#f3f4f6] last:border-none transition" style="padding: 12px 16px; border-bottom: 1px solid #f3f4f6;">
                  <p class="text-xs font-semibold text-[#111827]" style="font-size: 12px; font-weight: 700; margin: 0; color: #111827;">{notif.title}</p>
                  <p class="text-[11px] text-[#4b5563] mt-0.5 leading-snug" style="font-size: 11px; color: #4b5563; margin-top: 2px; margin-bottom: 0;">{notif.text}</p>
                  {#if notif.time}
                    <span class="text-[10px] text-[#9ca3af] mt-1 block" style="font-size: 10px; color: #9ca3af; margin-top: 4px; display: block;">{notif.time}</span>
                  {/if}
                </div>
              {/each}
            {/if}
          </div>
        </div>
      {/if}
    </div>

    <!-- User Profile Dropdown -->
    <div class="relative" style="position: relative;">
      <button
        type="button"
        class="flex items-center gap-2.5 p-1 rounded-lg hover:bg-[#f9fafb] transition"
        style="display: flex; align-items: center; gap: 10px; border: none; background: transparent; cursor: pointer; padding: 4px 8px; border-radius: 8px;"
        aria-label="User menu"
        on:click|stopPropagation={() => {
          showUserMenu = !showUserMenu;
          showNotifications = false;
        }}
      >
        <div class="w-8 h-8 rounded-full bg-[#166534] text-white flex items-center justify-center text-xs font-bold tracking-tight" style="width: 32px; height: 32px; border-radius: 50%; background: #166534; color: white; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; flex-shrink: 0;">
          {initials}
        </div>
        <div class="hidden sm:flex flex-col text-left leading-tight" style="display: flex; flex-direction: column; text-align: left; line-height: 1.2;">
          <span class="text-xs font-medium text-[#111827]" style="font-size: 12px; font-weight: 600; color: #111827;">{patientName}</span>
          <span class="text-[10px] text-[#6b7280]" style="font-size: 10px; color: #6b7280;">Patient</span>
        </div>
        <svg width="16" height="16" class="w-4 h-4 text-[#9ca3af] hidden sm:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 16px; height: 16px; color: #9ca3af; flex-shrink: 0;">
          <polyline points="6 9 12 15 18 9"></polyline>
        </svg>
      </button>

      {#if showUserMenu}
        <div
          class="absolute right-0 mt-2 w-64 bg-white rounded-lg border border-[#e5e7eb] shadow-lg py-2 z-50 text-left"
          role="menu"
          aria-label="User menu"
          tabindex="-1"
          style="position: absolute; right: 0; top: 100%; margin-top: 8px; width: 256px; background: white; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); padding: 8px 0; z-index: 50; text-align: left;"
          on:click|stopPropagation
          on:keydown|stopPropagation
        >
          <div class="px-4 py-2.5 border-b border-[#f3f4f6]" style="padding: 10px 16px; border-bottom: 1px solid #f3f4f6;">
            <p class="text-xs font-semibold text-[#111827] truncate" style="font-size: 12px; font-weight: 700; margin: 0; color: #111827;">{patientName}</p>
            <p class="text-[11px] text-[#6b7280] font-mono mt-0.5" style="font-size: 11px; font-family: monospace; color: #6b7280; margin-top: 2px; margin-bottom: 0;">{patientId}</p>
          </div>

          <div class="py-1" style="padding: 4px 0;">
            <button
              type="button"
              class="w-full px-4 py-2 text-xs font-medium text-[#374151] hover:bg-[#f9fafb] flex items-center gap-2.5 text-left"
              style="width: 100%; padding: 8px 16px; font-size: 12px; color: #374151; border: none; background: transparent; cursor: pointer; display: flex; align-items: center; gap: 10px; text-align: left;"
              on:click={() => {
                showUserMenu = false;
                onSelectSettings();
              }}
            >
              <svg width="16" height="16" class="w-4 h-4 text-[#6b7280]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 16px; height: 16px; color: #6b7280; flex-shrink: 0;">
                <circle cx="12" cy="12" r="3"></circle>
                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
              </svg>
              Account Settings
            </button>

            <button
              type="button"
              class="w-full px-4 py-2 text-xs font-medium text-[#dc2626] hover:bg-[#fef2f2] flex items-center gap-2.5 text-left border-t border-[#f3f4f6]"
              style="width: 100%; padding: 8px 16px; font-size: 12px; color: #dc2626; border: none; border-top: 1px solid #f3f4f6; background: transparent; cursor: pointer; display: flex; align-items: center; gap: 10px; text-align: left;"
              on:click={handleLogout}
            >
              <svg width="16" height="16" class="w-4 h-4 text-[#dc2626]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 16px; height: 16px; color: #dc2626; flex-shrink: 0;">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
              </svg>
              Sign Out
            </button>
          </div>
        </div>
      {/if}
    </div>
  </div>
</header>
