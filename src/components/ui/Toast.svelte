<script>
  import { fly, fade } from 'svelte/transition';
  import { cubicOut } from 'svelte/easing';
  import { toastStore } from '../../stores/toastStore.js';

  const titles = {
    success: 'Saved',
    error: 'Action Failed',
    warning: 'Attention',
    info: 'Notice',
  };

  function portal(node) {
    document.body.appendChild(node);
    return {
      destroy() {
        node.remove();
      },
    };
  }
</script>

<div use:portal class="toast-stack" role="region" aria-label="Notifications" aria-live="polite">
  {#each $toastStore as toast (toast.id)}
    <div
      class="toast-card toast-{toast.type}"
      role={toast.type === 'error' ? 'alert' : 'status'}
      in:fly={{ y: 12, duration: 190, easing: cubicOut }}
      out:fade={{ duration: 130 }}
    >
      <div class="toast-icon" aria-hidden="true">
        {#if toast.type === 'success'}
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 6 9 17l-5-5" />
          </svg>
        {:else if toast.type === 'error'}
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="9" /><line x1="12" y1="8" x2="12" y2="12" /><line x1="12" y1="16" x2="12.01" y2="16" />
          </svg>
        {:else if toast.type === 'warning'}
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" /><line x1="12" y1="9" x2="12" y2="13" /><line x1="12" y1="17" x2="12.01" y2="17" />
          </svg>
        {:else}
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="9" /><line x1="12" y1="16" x2="12" y2="12" /><line x1="12" y1="8" x2="12.01" y2="8" />
          </svg>
        {/if}
      </div>

      <div class="toast-body">
        <div class="toast-title">{titles[toast.type] || 'Notice'}</div>
        <div class="toast-msg">{toast.message}</div>
      </div>

      <button
        type="button"
        class="toast-close"
        aria-label="Dismiss notification"
        on:click={() => toastStore.remove(toast.id)}
      >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" />
        </svg>
      </button>

      {#if toast.duration > 0}
        <span class="toast-bar" style="animation-duration: {toast.duration}ms;"></span>
      {/if}
    </div>
  {/each}
</div>

<style>
  .toast-stack {
    position: fixed;
    top: 20px;
    right: 24px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    z-index: 10060;
    width: min(380px, calc(100vw - 32px));
    pointer-events: none;
  }

  .toast-card {
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 13px 14px 15px;
    border-radius: 12px;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    box-shadow: 0 10px 28px rgba(15, 23, 42, 0.1), 0 2px 6px rgba(15, 23, 42, 0.04);
    pointer-events: auto;
  }

  .toast-icon {
    width: 30px;
    height: 30px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  .toast-icon svg {
    width: 16px;
    height: 16px;
  }

  .toast-success .toast-icon { background: #f0fdf4; color: #166534; }
  .toast-error .toast-icon   { background: #fef2f2; color: #dc2626; }
  .toast-warning .toast-icon { background: #fffbeb; color: #d97706; }
  .toast-info .toast-icon    { background: #eff6ff; color: #2563eb; }

  .toast-body {
    flex: 1;
    min-width: 0;
    padding-top: 1px;
  }

  .toast-title {
    font-size: 0.78rem;
    font-weight: 700;
    color: #111827;
    line-height: 1.25;
  }

  .toast-msg {
    margin-top: 2px;
    font-size: 0.8125rem;
    color: #4b5563;
    line-height: 1.4;
    word-wrap: break-word;
  }

  .toast-close {
    width: 26px;
    height: 26px;
    border: none;
    border-radius: 7px;
    background: transparent;
    color: #9ca3af;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    flex-shrink: 0;
    transition: background-color 0.15s ease, color 0.15s ease;
  }

  .toast-close:hover {
    background: #f3f4f6;
    color: #374151;
  }

  .toast-close svg {
    width: 14px;
    height: 14px;
  }

  .toast-bar {
    position: absolute;
    left: 0;
    bottom: 0;
    height: 2.5px;
    width: 100%;
    transform-origin: left center;
    animation-name: toastShrink;
    animation-timing-function: linear;
    animation-fill-mode: forwards;
  }

  .toast-success .toast-bar { background: #166534; }
  .toast-error .toast-bar   { background: #dc2626; }
  .toast-warning .toast-bar { background: #d97706; }
  .toast-info .toast-bar    { background: #2563eb; }

  @keyframes toastShrink {
    from { transform: scaleX(1); }
    to   { transform: scaleX(0); }
  }

  @media (prefers-reduced-motion: reduce) {
    .toast-bar {
      animation: none;
    }
  }
</style>
