<script>
  import { onMount } from 'svelte';

  export let show = false;
  export let onClose = () => {};
  export let onSuccess = () => {};

  let currentPassword = '';
  let newPassword = '';
  let confirmPassword = '';
  let showCurrent = false;
  let showNew = false;
  let showConfirm = false;

  let error = '';
  let isSubmitting = false;

  function handleKeydown(e) {
    if (e.key === 'Escape' && show) {
      onClose();
    }
  }

  async function handleSubmit() {
    error = '';
    if (!currentPassword) {
      error = 'Please enter your current password.';
      return;
    }
    if (newPassword.length < 8) {
      error = 'New password must be at least 8 characters.';
      return;
    }
    if (newPassword !== confirmPassword) {
      error = 'New passwords do not match.';
      return;
    }

    isSubmitting = true;
    try {
      const res = await fetch('/sdn/api/patient/change-password', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          current_password: currentPassword,
          new_password: newPassword,
          new_password_confirmation: confirmPassword
        })
      });

      const json = await res.json();
      if (res.ok && json.success) {
        currentPassword = '';
        newPassword = '';
        confirmPassword = '';
        onSuccess(json.message || 'Password updated successfully.');
        onClose();
      } else {
        error = json.message || 'Failed to update password. Please check your current password.';
      }
    } catch (e) {
      // Fallback to legacy endpoint if backend route isn't available
      try {
        const fbRes = await fetch('/sdn/api/patient_dashboard_data.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            action: 'change_password',
            current_password: currentPassword,
            new_password: newPassword,
            confirm_password: confirmPassword
          })
        });
        const fbJson = await fbRes.json();
        if (fbJson.success) {
          currentPassword = '';
          newPassword = '';
          confirmPassword = '';
          onSuccess(fbJson.message || 'Password updated successfully.');
          onClose();
        } else {
          error = fbJson.message || 'Could not update password.';
        }
      } catch (err) {
        error = 'Server error. Please try again later.';
      }
    } finally {
      isSubmitting = false;
    }
  }
</script>

<svelte:window on:keydown={handleKeydown} />

{#if show}
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
    <button
      type="button"
      class="fixed inset-0 bg-transparent cursor-default border-0"
      aria-label="Close dialog"
      on:click={onClose}
    ></button>

    <div
      class="relative w-full max-w-md bg-white rounded-xl shadow-xl border border-[#e5e7eb] p-6 text-left z-10 animate-in fade-in zoom-in-95 duration-150"
      role="dialog"
      aria-modal="true"
      aria-labelledby="modal-title"
    >
      <!-- Modal Header -->
      <div class="flex items-center justify-between pb-4 border-b border-[#f3f4f6]">
        <div>
          <h2 id="modal-title" class="text-base font-bold text-[#111827]">Change Password</h2>
          <p class="text-xs text-[#6b7280] mt-0.5">Ensure your account remains safe with a strong password.</p>
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

      {#if error}
        <div class="mt-4 p-3 rounded-lg bg-[#fef2f2] border border-[#fecaca] text-xs text-[#b91c1c] flex items-start gap-2">
          <svg width="16" height="16" class="w-4 h-4 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 16px; height: 16px; flex-shrink: 0;">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
          </svg>
          <span>{error}</span>
        </div>
      {/if}

      <form on:submit|preventDefault={handleSubmit} class="space-y-4 mt-4">
        <!-- Current Password -->
        <div>
          <label for="current-password" class="block text-xs font-medium text-[#374151] mb-1">Current Password</label>
          <div class="relative">
            <input
              id="current-password"
              type={showCurrent ? 'text' : 'password'}
              bind:value={currentPassword}
              required
              class="w-full px-3 py-2 text-xs rounded-lg border border-[#d1d5db] focus:border-[#166534] focus:ring-1 focus:ring-[#166534] outline-none transition"
              placeholder="Enter current password"
            />
            <button
              type="button"
              class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[#9ca3af] hover:text-[#4b5563]"
              on:click={() => showCurrent = !showCurrent}
              aria-label={showCurrent ? 'Hide password' : 'Show password'}
            >
              <svg width="16" height="16" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 16px; height: 16px;">
                {#if showCurrent}
                  <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                  <line x1="1" y1="1" x2="23" y2="23"></line>
                {:else}
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                {/if}
              </svg>
            </button>
          </div>
        </div>

        <!-- New Password -->
        <div>
          <label for="new-password" class="block text-xs font-medium text-[#374151] mb-1">New Password (min 8 characters)</label>
          <div class="relative">
            <input
              id="new-password"
              type={showNew ? 'text' : 'password'}
              bind:value={newPassword}
              minlength="8"
              required
              class="w-full px-3 py-2 text-xs rounded-lg border border-[#d1d5db] focus:border-[#166534] focus:ring-1 focus:ring-[#166534] outline-none transition"
              placeholder="Enter new password"
            />
            <button
              type="button"
              class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[#9ca3af] hover:text-[#4b5563]"
              on:click={() => showNew = !showNew}
              aria-label={showNew ? 'Hide password' : 'Show password'}
            >
              <svg width="16" height="16" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 16px; height: 16px;">
                {#if showNew}
                  <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                  <line x1="1" y1="1" x2="23" y2="23"></line>
                {:else}
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                {/if}
              </svg>
            </button>
          </div>
        </div>

        <!-- Confirm Password -->
        <div>
          <label for="confirm-password" class="block text-xs font-medium text-[#374151] mb-1">Confirm New Password</label>
          <div class="relative">
            <input
              id="confirm-password"
              type={showConfirm ? 'text' : 'password'}
              bind:value={confirmPassword}
              minlength="8"
              required
              class="w-full px-3 py-2 text-xs rounded-lg border border-[#d1d5db] focus:border-[#166534] focus:ring-1 focus:ring-[#166534] outline-none transition"
              placeholder="Confirm new password"
            />
            <button
              type="button"
              class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[#9ca3af] hover:text-[#4b5563]"
              on:click={() => showConfirm = !showConfirm}
              aria-label={showConfirm ? 'Hide password' : 'Show password'}
            >
              <svg width="16" height="16" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 16px; height: 16px;">
                {#if showConfirm}
                  <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                  <line x1="1" y1="1" x2="23" y2="23"></line>
                {:else}
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                {/if}
              </svg>
            </button>
          </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-3 border-t border-[#f3f4f6]">
          <button
            type="button"
            class="px-3.5 py-1.5 rounded-lg border border-[#e5e7eb] text-xs font-semibold text-[#4b5563] hover:bg-[#f9fafb] transition"
            on:click={onClose}
          >
            Cancel
          </button>
          <button
            type="submit"
            disabled={isSubmitting}
            class="px-4 py-1.5 rounded-lg bg-[#166534] hover:bg-[#14532d] disabled:opacity-50 text-white text-xs font-semibold transition"
          >
            {isSubmitting ? 'Updating...' : 'Update Password'}
          </button>
        </div>
      </form>
    </div>
  </div>
{/if}

