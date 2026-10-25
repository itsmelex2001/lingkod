<script>
  import { createEventDispatcher } from 'svelte';

  export let faqs = [];
  export let title = 'Choose a topic to ask:';

  const dispatch = createEventDispatcher();

  function selectFaq(faq) {
    dispatch('select', faq);
  }
</script>

<div class="quick-replies-wrap">
  {#if title}
    <p class="replies-title">{title}</p>
  {/if}
  <div class="replies-grid">
    {#each faqs as item}
      <button
        type="button"
        class="faq-btn"
        on:click={() => selectFaq(item)}
      >
        <span>{item.question}</span>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
      </button>
    {/each}
  </div>
</div>

<style>
  .quick-replies-wrap {
    padding: 12px 16px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
  }

  .replies-title {
    font-size: 0.78rem;
    font-weight: 600;
    color: #64748b;
    margin: 0 0 8px 0;
  }

  .replies-grid {
    display: flex;
    flex-direction: column;
    gap: 6px;
  }

  .faq-btn {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 8px 12px;
    font-size: 0.82rem;
    font-weight: 500;
    color: #1e293b;
    cursor: pointer;
    text-align: left;
    transition: all 0.15s;
  }

  .faq-btn:hover {
    background: #f0fdf4;
    border-color: #0f4c25;
    color: #0f4c25;
    transform: translateX(4px);
  }
</style>
