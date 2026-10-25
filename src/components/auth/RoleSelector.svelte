<script>
  import { createEventDispatcher } from 'svelte';
  import { ROLES, ROLE_LABELS } from '../../types/roles.js';

  export let selectedRole = 'staff';

  const dispatch = createEventDispatcher();

  function selectRole(r) {
    selectedRole = r;
    dispatch('change', r);
  }
</script>

<div class="role-selector">
  <button
    type="button"
    class="role-btn {selectedRole === ROLES.STAFF ? 'active' : ''}"
    on:click={() => selectRole(ROLES.STAFF)}
  >
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
    <span>{ROLE_LABELS[ROLES.STAFF]}</span>
  </button>
  <button
    type="button"
    class="role-btn {selectedRole === ROLES.ADMIN ? 'active' : ''}"
    on:click={() => selectRole(ROLES.ADMIN)}
  >
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
    <span>{ROLE_LABELS[ROLES.ADMIN]}</span>
  </button>
</div>

<style>
  .role-selector {
    display: flex;
    background: #f1f5f9;
    border-radius: 10px;
    padding: 4px;
    gap: 4px;
    margin-bottom: 20px;
  }

  .role-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 8px 14px;
    border: none;
    background: transparent;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    color: #64748b;
    cursor: pointer;
    transition: all 0.2s;
  }

  .role-btn.active {
    background: #ffffff;
    color: #0f4c25;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
  }
</style>
