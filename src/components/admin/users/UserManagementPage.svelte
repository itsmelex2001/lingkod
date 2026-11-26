<script>
  import { onMount } from 'svelte';
  import { createUserManagementService } from '../../../services/userManagementService.js';
  import { toastStore } from '../../../stores/toastStore.js';
  import Toast from '../../ui/Toast.svelte';
  import SettingsModal from '../settings/SettingsModal.svelte';
  import StaffTab from './StaffTab.svelte';
  import PatientsTab from './PatientsTab.svelte';

  export let apiBase = '/sdn/api/admin-users-router.php';

  const service = createUserManagementService(apiBase);

  let activeTab = 'staff';
  let staffList = [];
  let patientList = [];
  let initialLoading = true;
  let refreshing = false;
  let loadError = '';

  // Modal state
  let modalType = null; // 'add-staff' | 'edit-staff' | 'confirm-reset-staff' | 'confirm-temp-patient' | 'reveal-password' | 'edit-email' | 'confirm-suspend'
  let targetUser = null;
  let busy = false;
  let formError = '';

  // Form drafts
  let staffForm = { full_name: '', username: '', role: 'staff', department: 'General Medical', password: '', confirm_password: '', status: 'active' };
  let emailDraft = '';
  let revealedPassword = '';
  let revealedTitle = '';
  let revealedSubtitle = '';
  let copied = false;

  const departments = ['General Medical', 'Neurology', 'Psychiatry', 'Administration'];

  async function loadAll(silent = false) {
    const isSilent = silent === true;
    if (!isSilent) {
      loadError = '';
      refreshing = true;
    }
    try {
      const [staffRes, patientsRes] = await Promise.all([
        service.listStaff(),
        service.listPatients(),
      ]);
      staffList = Array.isArray(staffRes.data) ? staffRes.data : [];
      patientList = Array.isArray(patientsRes.data) ? patientsRes.data : [];
    } catch (e) {
      if (!isSilent) {
        loadError = e?.message || 'Unable to load user accounts.';
      }
    } finally {
      initialLoading = false;
      if (!isSilent) {
        refreshing = false;
      }
    }
  }

  function closeModal() {
    if (busy) return;
    modalType = null;
    targetUser = null;
    formError = '';
    revealedPassword = '';
    copied = false;
  }

  function openAddStaff() {
    formError = '';
    staffForm = { full_name: '', username: '', role: 'staff', department: 'General Medical', password: '', confirm_password: '', status: 'active' };
    targetUser = null;
    modalType = 'add-staff';
  }

  function openEditStaff(member) {
    formError = '';
    targetUser = member;
    staffForm = {
      full_name: member.full_name || '',
      username: member.username || '',
      role: member.role || 'staff',
      department: member.department || 'General Medical',
      password: '',
      confirm_password: '',
      status: member.status || 'active',
    };
    modalType = 'edit-staff';
  }

  function openResetStaffPassword(member) {
    formError = '';
    targetUser = member;
    modalType = 'confirm-reset-staff';
  }

  function openPatientTempPassword(patient) {
    formError = '';
    targetUser = patient;
    modalType = 'confirm-temp-patient';
  }

  function openEditPatientEmail(patient) {
    formError = '';
    targetUser = patient;
    emailDraft = patient.email || '';
    modalType = 'edit-email';
  }

  function openToggleSuspend(patient) {
    formError = '';
    targetUser = patient;
    modalType = 'confirm-suspend';
  }

  function openToggleStaffStatus(member) {
    formError = '';
    targetUser = member;
    modalType = 'confirm-staff-status';
  }

  async function submitAddStaff() {
    formError = '';
    if (!staffForm.full_name.trim() || !staffForm.username.trim() || !staffForm.password) {
      formError = 'Full name, username, and password are required.';
      return;
    }
    if (staffForm.password.length < 6) {
      formError = 'Password must be at least 6 characters.';
      return;
    }
    if (staffForm.password !== staffForm.confirm_password) {
      formError = 'Passwords do not match.';
      return;
    }
    busy = true;
    try {
      await service.createStaff({
        full_name: staffForm.full_name.trim(),
        username: staffForm.username.trim(),
        role: staffForm.role,
        department: staffForm.department,
        password: staffForm.password,
      });
      toastStore.add('Staff account created.', 'success');
      busy = false;
      closeModal();
      await loadAll();
    } catch (e) {
      formError = e?.message || 'Failed to create staff account.';
      busy = false;
    }
  }

  async function submitEditStaff() {
    if (!targetUser) return;
    formError = '';
    if (!staffForm.full_name.trim()) {
      formError = 'Full name is required.';
      return;
    }
    busy = true;
    try {
      await service.updateStaff(targetUser.id, {
        full_name: staffForm.full_name.trim(),
        role: staffForm.role,
        department: staffForm.department,
        status: staffForm.status,
      });
      toastStore.add('Staff account updated.', 'success');
      busy = false;
      closeModal();
      await loadAll();
    } catch (e) {
      formError = e?.message || 'Failed to update staff account.';
      busy = false;
    }
  }

  async function confirmResetStaffPassword() {
    if (!targetUser) return;
    busy = true;
    formError = '';
    try {
      const res = await service.resetStaffPassword(targetUser.id);
      revealedTitle = 'Staff Password Reset';
      revealedSubtitle = `Provide this one-time password to ${targetUser.full_name} (@${targetUser.username}). It will not be shown again.`;
      revealedPassword = res.temporary_password || '';
      copied = false;
      modalType = 'reveal-password';
      toastStore.add('Staff password reset successfully.', 'success');
    } catch (e) {
      formError = e?.message || 'Failed to reset password.';
    } finally {
      busy = false;
    }
  }

  async function confirmGeneratePatientTempPassword() {
    if (!targetUser) return;
    busy = true;
    formError = '';
    try {
      const res = await service.generatePatientTempPassword(targetUser.id);
      revealedTitle = 'Temporary Password Generated';
      revealedSubtitle = `Give this temporary password to ${targetUser.full_name} (@${targetUser.username}) for manual account recovery. It will not be shown again.`;
      revealedPassword = res.temporary_password || '';
      copied = false;
      modalType = 'reveal-password';
      toastStore.add('Temporary password generated.', 'success');
    } catch (e) {
      formError = e?.message || 'Failed to generate temporary password.';
    } finally {
      busy = false;
    }
  }

  async function submitPatientEmail() {
    if (!targetUser) return;
    formError = '';
    const cleaned = emailDraft.trim().toLowerCase();
    if (!cleaned || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(cleaned)) {
      formError = 'Please enter a valid email address.';
      return;
    }
    busy = true;
    try {
      const res = await service.updatePatientEmail(targetUser.id, cleaned);
      patientList = patientList.map((p) =>
        p.id === targetUser.id ? { ...p, email: res.data?.email ?? cleaned, is_verified: false } : p
      );
      toastStore.add('Email updated. Patient must re-verify via OTP on next login.', 'success');
      busy = false;
      closeModal();
    } catch (e) {
      formError = e?.message || 'Failed to update patient email.';
      busy = false;
    }
  }

  async function confirmToggleSuspend() {
    if (!targetUser) return;
    const isCurrentlySuspended = (targetUser.account_status || 'active').toLowerCase() === 'suspended';
    const nextStatus = isCurrentlySuspended ? 'active' : 'suspended';
    busy = true;
    formError = '';
    try {
      await service.togglePatientStatus(targetUser.id, nextStatus);
      patientList = patientList.map((p) =>
        p.id === targetUser.id ? { ...p, account_status: nextStatus } : p
      );
      toastStore.add(nextStatus === 'suspended' ? 'Patient account suspended.' : 'Patient account restored.', 'success');
      busy = false;
      closeModal();
    } catch (e) {
      formError = e?.message || 'Failed to update account status.';
      busy = false;
    }
  }

  async function confirmToggleStaffStatus() {
    if (!targetUser) return;
    const isInactive = (targetUser.status || 'active').toLowerCase() !== 'active';
    const nextStatus = isInactive ? 'active' : 'inactive';
    busy = true;
    formError = '';
    try {
      await service.updateStaff(targetUser.id, { status: nextStatus });
      staffList = staffList.map((s) =>
        s.id === targetUser.id ? { ...s, status: nextStatus } : s
      );
      toastStore.add(nextStatus === 'inactive' ? 'Staff account deactivated.' : 'Staff account activated.', 'success');
      busy = false;
      closeModal();
    } catch (e) {
      formError = e?.message || 'Failed to update staff status.';
      busy = false;
    }
  }

  async function copyPassword() {
    if (!revealedPassword) return;
    try {
      await navigator.clipboard.writeText(revealedPassword);
      copied = true;
      toastStore.add('Temporary password copied to clipboard.', 'info', 2500);
    } catch {
      toastStore.add('Copy failed—please highlight and copy manually.', 'error');
    }
  }

  onMount(() => {
    loadAll();
    if (typeof window !== 'undefined') {
      window.refreshAdminUsersView = loadAll;
    }
  });
</script>

<div class="space-y-5 text-gray-900">
  <Toast />

  <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <h1 class="text-2xl font-bold tracking-tight text-[#0f2e1c]">User Management</h1>
      <p class="mt-0.5 text-sm text-gray-500">
        Manage hospital staff roles and assist walk-in patients with account recovery.
      </p>
    </div>
    <div class="flex items-center gap-2">
      <button
        type="button"
        on:click={() => loadAll(false)}
        disabled={refreshing}
        class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 text-sm font-medium text-gray-700 transition hover:border-[#bbf7d0] hover:bg-[#f0fdf4] hover:text-[#166534] disabled:opacity-50"
      >
        <svg class="h-4 w-4 {refreshing ? 'animate-spin' : ''}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <polyline points="23 4 23 10 17 10" /><polyline points="1 20 1 14 7 14" /><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15" />
        </svg>
        <span>Refresh</span>
      </button>
      {#if activeTab === 'staff'}
        <button
          type="button"
          on:click={openAddStaff}
          class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-lg bg-[#166534] px-4 text-sm font-semibold text-white transition hover:bg-[#14532d]"
        >
          <span>+ Add Staff</span>
        </button>
      {/if}
    </div>
  </header>

  <div class="border-b border-gray-200" role="tablist" aria-label="User account types">
    <div class="flex gap-6">
      <button
        type="button"
        role="tab"
        aria-selected={activeTab === 'staff'}
        on:click={() => (activeTab = 'staff')}
        class="inline-flex cursor-pointer items-center gap-2 border-b-2 pb-2.5 text-sm font-medium transition {activeTab === 'staff'
          ? 'border-[#166534] font-semibold text-[#166534]'
          : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-800'}"
      >
        <span>Staff</span>
        <span class="rounded-full px-2 py-0.5 text-xs {activeTab === 'staff' ? 'bg-[#f0fdf4] text-[#166534]' : 'bg-gray-100 text-gray-600'}">
          {staffList.length}
        </span>
      </button>
      <button
        type="button"
        role="tab"
        aria-selected={activeTab === 'patients'}
        on:click={() => (activeTab = 'patients')}
        class="inline-flex cursor-pointer items-center gap-2 border-b-2 pb-2.5 text-sm font-medium transition {activeTab === 'patients'
          ? 'border-[#166534] font-semibold text-[#166534]'
          : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-800'}"
      >
        <span>Patients</span>
        <span class="rounded-full px-2 py-0.5 text-xs {activeTab === 'patients' ? 'bg-[#f0fdf4] text-[#166534]' : 'bg-gray-100 text-gray-600'}">
          {patientList.length}
        </span>
      </button>
    </div>
  </div>

  {#if loadError}
    <div class="flex items-center justify-between rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
      <span>{loadError}</span>
      <button type="button" on:click={loadAll} class="cursor-pointer rounded-md px-2.5 py-1 text-xs font-semibold text-red-800 hover:bg-red-100">
        Retry
      </button>
    </div>
  {/if}

  {#if activeTab === 'staff'}
    <StaffTab
      staff={staffList}
      loading={initialLoading && staffList.length === 0}
      on:edit={(e) => openEditStaff(e.detail)}
      on:resetPassword={(e) => openResetStaffPassword(e.detail)}
      on:toggleStatus={(e) => openToggleStaffStatus(e.detail)}
    />
  {:else}
    <PatientsTab
      patients={patientList}
      loading={initialLoading && patientList.length === 0}
      on:tempPassword={(e) => openPatientTempPassword(e.detail)}
      on:editEmail={(e) => openEditPatientEmail(e.detail)}
      on:toggleSuspend={(e) => openToggleSuspend(e.detail)}
    />
  {/if}
</div>

<!-- Add Staff Modal -->
<SettingsModal
  open={modalType === 'add-staff'}
  size="md"
  {busy}
  title="Add Staff Member"
  description="Create an account and assign a hospital role and department."
  on:close={closeModal}
>
  <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
    <div class="sm:col-span-2">
      <label for="umAddName" class="mb-1 block text-xs font-semibold text-gray-700">Full Name *</label>
      <input id="umAddName" type="text" bind:value={staffForm.full_name} placeholder="e.g. Maria C. Santos" class="h-9 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-[#166534] focus:outline-none focus:ring-2 focus:ring-[#166534]/20" />
    </div>
    <div>
      <label for="umAddUser" class="mb-1 block text-xs font-semibold text-gray-700">Username *</label>
      <input id="umAddUser" type="text" bind:value={staffForm.username} placeholder="e.g. staff_santos" class="h-9 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-[#166534] focus:outline-none focus:ring-2 focus:ring-[#166534]/20" />
    </div>
    <div>
      <label for="umAddRole" class="mb-1 block text-xs font-semibold text-gray-700">Role *</label>
      <select id="umAddRole" bind:value={staffForm.role} class="h-9 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm focus:border-[#166534] focus:outline-none focus:ring-2 focus:ring-[#166534]/20">
        <option value="staff">Medical Staff</option>
        <option value="admin">Administrator</option>
        <option value="superadmin">Super Administrator</option>
      </select>
    </div>
    <div class="sm:col-span-2">
      <label for="umAddDept" class="mb-1 block text-xs font-semibold text-gray-700">Department *</label>
      <select id="umAddDept" bind:value={staffForm.department} class="h-9 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm focus:border-[#166534] focus:outline-none focus:ring-2 focus:ring-[#166534]/20">
        {#each departments as dept}
          <option value={dept}>{dept}</option>
        {/each}
      </select>
    </div>
    <div>
      <label for="umAddPass" class="mb-1 block text-xs font-semibold text-gray-700">Password *</label>
      <input id="umAddPass" type="password" bind:value={staffForm.password} placeholder="Min. 6 characters" class="h-9 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-[#166534] focus:outline-none focus:ring-2 focus:ring-[#166534]/20" />
    </div>
    <div>
      <label for="umAddConfirm" class="mb-1 block text-xs font-semibold text-gray-700">Confirm Password *</label>
      <input id="umAddConfirm" type="password" bind:value={staffForm.confirm_password} placeholder="Re-enter password" class="h-9 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-[#166534] focus:outline-none focus:ring-2 focus:ring-[#166534]/20" />
    </div>
  </div>
  {#if formError}
    <p class="mt-3 text-xs font-medium text-red-600">{formError}</p>
  {/if}
  <svelte:fragment slot="footer">
    <button type="button" disabled={busy} on:click={closeModal} class="cursor-pointer rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-50">Cancel</button>
    <button type="button" disabled={busy} on:click={submitAddStaff} class="cursor-pointer rounded-lg bg-[#166534] px-4 py-2 text-sm font-semibold text-white hover:bg-[#14532d] disabled:opacity-50">{busy ? 'Creating…' : 'Create Account'}</button>
  </svelte:fragment>
</SettingsModal>

<!-- Edit Staff Role / Details Modal -->
<SettingsModal
  open={modalType === 'edit-staff'}
  size="md"
  {busy}
  title="Edit Staff Role & Details"
  description="Update role assignment, department, or status for @{targetUser?.username || ''}."
  on:close={closeModal}
>
  <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
    <div class="sm:col-span-2">
      <label for="umEditName" class="mb-1 block text-xs font-semibold text-gray-700">Full Name *</label>
      <input id="umEditName" type="text" bind:value={staffForm.full_name} class="h-9 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-[#166534] focus:outline-none focus:ring-2 focus:ring-[#166534]/20" />
    </div>
    <div>
      <label for="umEditRole" class="mb-1 block text-xs font-semibold text-gray-700">Role *</label>
      <select id="umEditRole" bind:value={staffForm.role} class="h-9 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm focus:border-[#166534] focus:outline-none focus:ring-2 focus:ring-[#166534]/20">
        <option value="staff">Medical Staff</option>
        <option value="admin">Administrator</option>
        <option value="superadmin">Super Administrator</option>
      </select>
    </div>
    <div>
      <label for="umEditDept" class="mb-1 block text-xs font-semibold text-gray-700">Department *</label>
      <select id="umEditDept" bind:value={staffForm.department} class="h-9 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm focus:border-[#166534] focus:outline-none focus:ring-2 focus:ring-[#166534]/20">
        {#each departments as dept}
          <option value={dept}>{dept}</option>
        {/each}
      </select>
    </div>
    <div class="sm:col-span-2">
      <label for="umEditStatus" class="mb-1 block text-xs font-semibold text-gray-700">Account Status</label>
      <select id="umEditStatus" bind:value={staffForm.status} class="h-9 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm focus:border-[#166534] focus:outline-none focus:ring-2 focus:ring-[#166534]/20">
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
      </select>
    </div>
  </div>
  {#if formError}
    <p class="mt-3 text-xs font-medium text-red-600">{formError}</p>
  {/if}
  <svelte:fragment slot="footer">
    <button type="button" disabled={busy} on:click={closeModal} class="cursor-pointer rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-50">Cancel</button>
    <button type="button" disabled={busy} on:click={submitEditStaff} class="cursor-pointer rounded-lg bg-[#166534] px-4 py-2 text-sm font-semibold text-white hover:bg-[#14532d] disabled:opacity-50">{busy ? 'Saving…' : 'Save Changes'}</button>
  </svelte:fragment>
</SettingsModal>

<!-- Confirm Staff Password Reset -->
<SettingsModal
  open={modalType === 'confirm-reset-staff'}
  size="sm"
  tone="warning"
  {busy}
  title="Reset Staff Password?"
  description="This immediately replaces the current password for {targetUser?.full_name || 'this staff member'}."
  on:close={closeModal}
>
  <p class="text-sm text-gray-600">
    A one-time temporary password will be generated for <strong class="text-gray-900">@{targetUser?.username}</strong>.
  </p>
  {#if formError}
    <p class="mt-3 text-xs font-medium text-red-600">{formError}</p>
  {/if}
  <svelte:fragment slot="footer">
    <button type="button" disabled={busy} on:click={closeModal} class="cursor-pointer rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-50">Cancel</button>
    <button type="button" disabled={busy} on:click={confirmResetStaffPassword} class="cursor-pointer rounded-lg bg-[#166534] px-4 py-2 text-sm font-semibold text-white hover:bg-[#14532d] disabled:opacity-50">{busy ? 'Resetting…' : 'Reset Password'}</button>
  </svelte:fragment>
</SettingsModal>

<!-- Confirm Patient Temp Password -->
<SettingsModal
  open={modalType === 'confirm-temp-patient'}
  size="sm"
  tone="warning"
  {busy}
  title="Generate Temporary Password?"
  description="Use this for walk-in patients who need manual account recovery."
  on:close={closeModal}
>
  <p class="text-sm text-gray-600">
    This replaces the password for <strong class="text-gray-900">{targetUser?.full_name}</strong> (<span class="font-mono text-xs">@{targetUser?.username}</span>) and revokes existing remember-me sessions.
  </p>
  {#if formError}
    <p class="mt-3 text-xs font-medium text-red-600">{formError}</p>
  {/if}
  <svelte:fragment slot="footer">
    <button type="button" disabled={busy} on:click={closeModal} class="cursor-pointer rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-50">Cancel</button>
    <button type="button" disabled={busy} on:click={confirmGeneratePatientTempPassword} class="cursor-pointer rounded-lg bg-[#166534] px-4 py-2 text-sm font-semibold text-white hover:bg-[#14532d] disabled:opacity-50">{busy ? 'Generating…' : 'Generate Password'}</button>
  </svelte:fragment>
</SettingsModal>

<!-- Reveal One-Time Password Modal -->
<SettingsModal
  open={modalType === 'reveal-password'}
  size="sm"
  title={revealedTitle}
  description={revealedSubtitle}
  on:close={closeModal}
>
  <div class="flex items-center justify-between gap-3 rounded-lg border border-[#bbf7d0] bg-[#f0fdf4] px-4 py-3">
    <code class="font-mono text-base font-bold tracking-wider text-[#166534]">{revealedPassword}</code>
    <button
      type="button"
      on:click={copyPassword}
      class="cursor-pointer rounded-md border border-[#bbf7d0] bg-white px-3 py-1.5 text-xs font-semibold text-[#166534] transition hover:bg-[#dcfce7]"
    >
      {copied ? 'Copied' : 'Copy'}
    </button>
  </div>
  <svelte:fragment slot="footer">
    <button type="button" on:click={closeModal} class="cursor-pointer rounded-lg bg-[#166534] px-4 py-2 text-sm font-semibold text-white hover:bg-[#14532d]">Done</button>
  </svelte:fragment>
</SettingsModal>

<!-- Edit Patient Email Modal -->
<SettingsModal
  open={modalType === 'edit-email'}
  size="sm"
  {busy}
  title="Edit Patient Email"
  description="Correct typos for {targetUser?.full_name || 'this patient'} (@{targetUser?.username || ''})."
  on:close={closeModal}
>
  <div class="space-y-3">
    <div>
      <label for="umPatientEmail" class="mb-1 block text-xs font-semibold text-gray-700">Email Address *</label>
      <input
        id="umPatientEmail"
        type="email"
        bind:value={emailDraft}
        placeholder="patient@example.com"
        class="h-9 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-[#166534] focus:outline-none focus:ring-2 focus:ring-[#166534]/20"
      />
    </div>
    <p class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
      Updating this email resets verification status. The patient will be required to verify via 6-digit email OTP on their next login.
    </p>
  </div>
  {#if formError}
    <p class="mt-3 text-xs font-medium text-red-600">{formError}</p>
  {/if}
  <svelte:fragment slot="footer">
    <button type="button" disabled={busy} on:click={closeModal} class="cursor-pointer rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-50">Cancel</button>
    <button type="button" disabled={busy} on:click={submitPatientEmail} class="cursor-pointer rounded-lg bg-[#166534] px-4 py-2 text-sm font-semibold text-white hover:bg-[#14532d] disabled:opacity-50">{busy ? 'Saving…' : 'Save & Require OTP'}</button>
  </svelte:fragment>
</SettingsModal>

<!-- Confirm Lock / Suspend Patient Account Modal -->
<SettingsModal
  open={modalType === 'confirm-suspend'}
  size="sm"
  tone={(targetUser?.account_status || 'active') === 'suspended' ? 'default' : 'danger'}
  {busy}
  title={(targetUser?.account_status || 'active') === 'suspended' ? 'Unlock Patient Account?' : 'Suspend Patient Account?'}
  description={(targetUser?.account_status || 'active') === 'suspended'
    ? `Restore portal login access for ${targetUser?.full_name || 'this patient'}.`
    : `Block portal login access for ${targetUser?.full_name || 'this patient'} without deleting clinical history.`}
  on:close={closeModal}
>
  <p class="text-sm text-gray-600">
    {#if (targetUser?.account_status || 'active') === 'suspended'}
      <strong class="text-gray-900">@{targetUser?.username}</strong> will be able to sign in to the Patient Portal again.
    {:else}
      <strong class="text-gray-900">@{targetUser?.username}</strong> will be immediately blocked from signing in and any saved sessions will be revoked.
    {/if}
  </p>
  {#if formError}
    <p class="mt-3 text-xs font-medium text-red-600">{formError}</p>
  {/if}
  <svelte:fragment slot="footer">
    <button type="button" disabled={busy} on:click={closeModal} class="cursor-pointer rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-50">Cancel</button>
    <button
      type="button"
      disabled={busy}
      on:click={confirmToggleSuspend}
      class="cursor-pointer rounded-lg px-4 py-2 text-sm font-semibold text-white disabled:opacity-50 {(targetUser?.account_status || 'active') === 'suspended'
        ? 'bg-[#166534] hover:bg-[#14532d]'
        : 'bg-red-600 hover:bg-red-700'}"
    >
      {busy ? 'Updating…' : (targetUser?.account_status || 'active') === 'suspended' ? 'Unlock Account' : 'Suspend Account'}
    </button>
  </svelte:fragment>
</SettingsModal>

<!-- Confirm Staff Activate / Deactivate Modal -->
<SettingsModal
  open={modalType === 'confirm-staff-status'}
  size="sm"
  tone={(targetUser?.status || 'active') !== 'active' ? 'default' : 'danger'}
  {busy}
  title={(targetUser?.status || 'active') !== 'active' ? 'Activate Staff Account?' : 'Deactivate Staff Account?'}
  description={(targetUser?.status || 'active') !== 'active'
    ? `Restore portal sign-in access for ${targetUser?.full_name || 'this staff member'}.`
    : `Prevent ${targetUser?.full_name || 'this staff member'} from signing in until reactivated.`}
  on:close={closeModal}
>
  <p class="text-sm text-gray-600">
    {#if (targetUser?.status || 'active') !== 'active'}
      <strong class="text-gray-900">@{targetUser?.username}</strong> will regain access to the Staff Portal.
    {:else}
      <strong class="text-gray-900">@{targetUser?.username}</strong> will be locked out of the portal immediately.
    {/if}
  </p>
  {#if formError}
    <p class="mt-3 text-xs font-medium text-red-600">{formError}</p>
  {/if}
  <svelte:fragment slot="footer">
    <button type="button" disabled={busy} on:click={closeModal} class="cursor-pointer rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-50">Cancel</button>
    <button
      type="button"
      disabled={busy}
      on:click={confirmToggleStaffStatus}
      class="cursor-pointer rounded-lg px-4 py-2 text-sm font-semibold text-white disabled:opacity-50 {(targetUser?.status || 'active') !== 'active'
        ? 'bg-[#166534] hover:bg-[#14532d]'
        : 'bg-red-600 hover:bg-red-700'}"
    >
      {busy ? 'Updating…' : (targetUser?.status || 'active') !== 'active' ? 'Activate Account' : 'Deactivate Account'}
    </button>
  </svelte:fragment>
</SettingsModal>
