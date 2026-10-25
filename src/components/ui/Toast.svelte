<script>
  import { toastStore } from '../../stores/toastStore.js';
</script>

<div class="toast-stack">
  {#each $toastStore as toast (toast.id)}
    <div class="toast-item toast-{toast.type}">
      <span class="toast-msg">{toast.message}</span>
      <button type="button" class="toast-close" on:click={() => toastStore.remove(toast.id)}>
        &times;
      </button>
    </div>
  {/each}
</div>

<style>
  .toast-stack {
    position: fixed;
    bottom: 24px;
    right: 24px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    z-index: 10000;
    max-width: 360px;
  }

  .toast-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px 18px;
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 500;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    animation: slideIn 0.25s ease-out;
  }

  .toast-success { background: #065f46; color: #ffffff; }
  .toast-error { background: #b91c1c; color: #ffffff; }
  .toast-info { background: #1e3a8a; color: #ffffff; }
  .toast-warning { background: #b45309; color: #ffffff; }

  .toast-close {
    background: none;
    border: none;
    color: currentColor;
    font-size: 1.2rem;
    cursor: pointer;
    opacity: 0.8;
  }
  .toast-close:hover { opacity: 1; }

  @keyframes slideIn {
    from { transform: translateY(20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
  }
</style>
