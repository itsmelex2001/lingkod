<script>
  import { createEventDispatcher } from 'svelte';
  import { fade, scale } from 'svelte/transition';
  import { cubicOut } from 'svelte/easing';

  export let open = false;
  export let title = '';
  export let description = '';
  export let size = 'md'; // 'sm' | 'md' | 'lg'
  export let tone = 'primary'; // 'primary' | 'warning' | 'danger'
  export let busy = false;

  const dispatch = createEventDispatcher();

  const widths = {
    sm: 'max-w-sm',
    md: 'max-w-md',
    lg: 'max-w-2xl',
  };

  function portal(node) {
    document.body.appendChild(node);
    return {
      destroy() {
        node.remove();
      },
    };
  }

  function close() {
    if (busy) return;
    open = false;
    dispatch('close');
  }

  function onKeydown(e) {
    if (open && e.key === 'Escape') {
      e.preventDefault();
      close();
    }
  }
</script>

<svelte:window on:keydown={onKeydown} />

{#if open}
  <div
    use:portal
    class="fixed inset-0 z-[10050] flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-[2px]"
    transition:fade={{ duration: 150 }}
    on:click|self={close}
    role="presentation"
  >
    <div
      class="flex max-h-[calc(100vh-2rem)] w-full {widths[size] || widths.md} flex-col overflow-hidden rounded-2xl border border-gray-200/90 bg-white shadow-2xl"
      role="dialog"
      aria-modal="true"
      aria-labelledby="settings-modal-title"
      in:scale={{ start: 0.96, duration: 180, easing: cubicOut }}
      out:scale={{ start: 0.97, duration: 120, easing: cubicOut }}
    >
      <div class="flex shrink-0 items-start justify-between gap-4 border-b border-gray-100 px-6 py-4">
        <div class="flex items-start gap-3">
          {#if tone === 'danger'}
            <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-red-50 text-red-600 ring-1 ring-red-100" aria-hidden="true">
              <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 6h18" /><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" /><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
              </svg>
            </span>
          {:else if tone === 'warning'}
            <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600 ring-1 ring-amber-100" aria-hidden="true">
              <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" /><line x1="12" y1="9" x2="12" y2="13" /><line x1="12" y1="17" x2="12.01" y2="17" />
              </svg>
            </span>
          {:else}
            <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#f0fdf4] text-[#166534] ring-1 ring-[#bbf7d0]" aria-hidden="true">
              <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="9" /><path d="m9 12 2 2 4-4" />
              </svg>
            </span>
          {/if}

          <div>
            <h3 id="settings-modal-title" class="text-base font-bold text-gray-900">{title}</h3>
            {#if description}
              <p class="mt-0.5 text-xs leading-relaxed text-gray-500">{description}</p>
            {/if}
          </div>
        </div>

        <button
          type="button"
          on:click={close}
          disabled={busy}
          aria-label="Close modal"
          class="flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#166534] disabled:opacity-40"
        >
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" />
          </svg>
        </button>
      </div>

      <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
        <slot />
      </div>

      {#if $$slots.footer}
        <div class="flex shrink-0 items-center justify-end gap-2.5 border-t border-gray-100 bg-gray-50/70 px-6 py-3.5">
          <slot name="footer" />
        </div>
      {/if}
    </div>
  </div>
{/if}
