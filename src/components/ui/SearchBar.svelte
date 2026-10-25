<script>
  import { createEventDispatcher } from 'svelte';
  export let value = '';
  export let placeholder = 'Search...';

  const dispatch = createEventDispatcher();

  function onInput(e) {
    value = e.target.value;
    dispatch('input', value);
  }

  function clear() {
    value = '';
    dispatch('input', '');
  }
</script>

<div class="search-bar">
  <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
    <circle cx="11" cy="11" r="8"></circle>
    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
  </svg>
  <input
    type="text"
    {placeholder}
    {value}
    on:input={onInput}
    class="search-input"
  />
  {#if value}
    <button type="button" class="clear-btn" on:click={clear} title="Clear search">
      &times;
    </button>
  {/if}
</div>

<style>
  .search-bar {
    position: relative;
    display: flex;
    align-items: center;
    width: 100%;
  }

  .search-icon {
    position: absolute;
    left: 12px;
    color: #94a3b8;
    pointer-events: none;
  }

  .search-input {
    width: 100%;
    padding: 10px 36px 10px 38px;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 9999px;
    font-size: 0.9rem;
    font-family: inherit;
    color: #0f172a;
    transition: all 0.2s;
    box-sizing: border-box;
  }

  .search-input:focus {
    outline: none;
    border-color: #0f4c25;
    box-shadow: 0 0 0 3px rgba(15, 76, 37, 0.12);
  }

  .clear-btn {
    position: absolute;
    right: 12px;
    background: none;
    border: none;
    color: #94a3b8;
    font-size: 1.2rem;
    cursor: pointer;
    line-height: 1;
    padding: 0;
  }
  .clear-btn:hover {
    color: #475569;
  }
</style>
