<script>
  export let variant = 'primary'; // 'primary' | 'secondary' | 'outline' | 'danger' | 'ghost'
  export let size = 'md'; // 'sm' | 'md' | 'lg'
  export let type = 'button';
  export let disabled = false;
  export let loading = false;
  export let fullWidth = false;
</script>

<button
  {type}
  class="btn btn-{variant} btn-{size} {fullWidth ? 'w-full' : ''}"
  {disabled}
  on:click
  {...$$restProps}
>
  {#if loading}
    <span class="btn-spinner"></span>
  {/if}
  <slot />
</button>

<style>
  .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-family: inherit;
    font-weight: 600;
    border-radius: 8px;
    border: 1px solid transparent;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    text-decoration: none;
    line-height: 1;
    white-space: nowrap;
  }

  .btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    pointer-events: none;
  }

  /* Sizes */
  .btn-sm {
    padding: 6px 12px;
    font-size: 0.8rem;
  }
  .btn-md {
    padding: 10px 18px;
    font-size: 0.9rem;
  }
  .btn-lg {
    padding: 14px 24px;
    font-size: 1rem;
  }
  .w-full {
    width: 100%;
  }

  /* Variants */
  .btn-primary {
    background: #0f4c25;
    color: #ffffff;
    border-color: #0f4c25;
  }
  .btn-primary:hover:not(:disabled) {
    background: #146331;
    box-shadow: 0 4px 12px rgba(15, 76, 37, 0.25);
  }

  .btn-secondary {
    background: #e2e8f0;
    color: #1e293b;
  }
  .btn-secondary:hover:not(:disabled) {
    background: #cbd5e1;
  }

  .btn-outline {
    background: transparent;
    border-color: #cbd5e1;
    color: #334155;
  }
  .btn-outline:hover:not(:disabled) {
    background: #f8fafc;
    border-color: #94a3b8;
  }

  .btn-danger {
    background: #ef4444;
    color: #ffffff;
  }
  .btn-danger:hover:not(:disabled) {
    background: #dc2626;
  }

  .btn-ghost {
    background: transparent;
    color: inherit;
  }
  .btn-ghost:hover:not(:disabled) {
    background: rgba(0, 0, 0, 0.05);
  }

  .btn-spinner {
    width: 14px;
    height: 14px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: currentColor;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
  }

  @keyframes spin {
    to { transform: rotate(360deg); }
  }
</style>
