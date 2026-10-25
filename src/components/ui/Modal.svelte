<script>
  import { createEventDispatcher } from 'svelte';
  export let isOpen = false;
  export let title = '';
  export let maxWidth = '600px';

  const dispatch = createEventDispatcher();

  function close() {
    dispatch('close');
  }

  function handleKeydown(e) {
    if (e.key === 'Escape' && isOpen) {
      close();
    }
  }
</script>

<svelte:window on:keydown={handleKeydown} />

{#if isOpen}
  <!-- svelte-ignore a11y-click-events-have-key-events -->
  <!-- svelte-ignore a11y-no-static-element-interactions -->
  <div class="modal-overlay" on:click={close}>
    <div
      class="modal-card"
      style="max-width: {maxWidth};"
      on:click|stopPropagation
    >
      <div class="modal-header">
        <slot name="header">
          <h3>{title}</h3>
        </slot>
        <button type="button" class="btn-close" on:click={close} aria-label="Close modal">
          &times;
        </button>
      </div>

      <div class="modal-body">
        <slot />
      </div>

      {#if $$slots.footer}
        <div class="modal-footer">
          <slot name="footer" />
        </div>
      {/if}
    </div>
  </div>
{/if}

<style>
  .modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(15, 23, 42, 0.65);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 20px;
    animation: fadeIn 0.2s ease-out;
  }

  .modal-card {
    background: #ffffff;
    border-radius: 16px;
    width: 100%;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
    overflow: hidden;
    animation: scaleUp 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  }

  .modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 24px;
    border-bottom: 1px solid #e2e8f0;
  }

  .modal-header h3 {
    margin: 0;
    font-size: 1.2rem;
    font-weight: 700;
    color: #0f2e1b;
  }

  .btn-close {
    background: transparent;
    border: none;
    font-size: 1.6rem;
    line-height: 1;
    color: #94a3b8;
    cursor: pointer;
    border-radius: 6px;
    padding: 4px 8px;
    transition: color 0.15s;
  }
  .btn-close:hover {
    color: #0f172a;
  }

  .modal-body {
    padding: 24px;
    overflow-y: auto;
  }

  .modal-footer {
    padding: 16px 24px;
    border-top: 1px solid #f1f5f9;
    background: #f8fafc;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
  }

  @keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
  }

  @keyframes scaleUp {
    from { transform: scale(0.96); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
  }
</style>
