<script>
  import { createEventDispatcher } from 'svelte';

  export let searchQuery = '';
  export let showOnlyAvailable = false;

  const dispatch = createEventDispatcher();
</script>

<div class="nav-filters">
  <div class="search-wrapper">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#999" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input
      type="text"
      bind:value={searchQuery}
      on:input={() => dispatch('search', searchQuery)}
      placeholder="Search doctors..."
      class="input-search"
    />
  </div>
  <label class="toggle-minimal">
    <input
      type="checkbox"
      bind:checked={showOnlyAvailable}
      on:change={() => dispatch('toggleAvail', showOnlyAvailable)}
    />
    <span class="toggle-box"></span>
    <span class="toggle-txt">Open Schedules Only</span>
  </label>
</div>

<style>
  .nav-filters {
    display: flex;
    align-items: center;
    gap: 1.5rem;
  }
  
  .search-wrapper {
    position: relative;
    display: flex;
    align-items: center;
  }
  .search-wrapper svg {
    position: absolute;
    left: 12px;
    pointer-events: none;
  }
  .input-search {
    background: #fff;
    border: 1px solid #eaeaea;
    border-radius: 20px;
    padding: 0.5rem 1rem 0.5rem 2.2rem;
    font-size: 0.9rem;
    font-family: inherit;
    outline: none;
    transition: all 0.2s ease;
    width: 200px;
  }
  .input-search:focus {
    border-color: #111;
    width: 250px;
  }
  
  .toggle-minimal {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    font-size: 0.85rem;
    font-weight: 500;
    user-select: none;
  }
  .toggle-minimal input { display: none; }
  .toggle-box {
    width: 14px; height: 14px;
    border: 1px solid #ccc;
    border-radius: 3px;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.2s ease;
  }
  .toggle-minimal input:checked + .toggle-box {
    background: #111;
    border-color: #111;
  }
  .toggle-minimal input:checked + .toggle-box::after {
    content: '';
    width: 4px; height: 8px;
    border: solid #fff;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
    margin-bottom: 2px;
  }
  
  @media (max-width: 768px) {
    .nav-filters {
      flex-direction: column;
      align-items: flex-start;
      gap: 1rem;
      width: 100%;
    }
    .input-search { width: 100%; }
    .input-search:focus { width: 100%; }
  }
</style>
