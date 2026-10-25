<script>
  import { createEventDispatcher } from 'svelte';
  import { DEPARTMENT_LIST } from '../../types/departments.js';

  export let selectedDept = 'All';
  export let depts = DEPARTMENT_LIST;

  const dispatch = createEventDispatcher();

  function select(dept) {
    selectedDept = dept;
    dispatch('change', dept);
  }
</script>

<nav class="dept-tabs">
  {#each depts as d}
    <button
      type="button"
      class="tab {selectedDept === d ? 'active' : ''}"
      on:click={() => select(d)}
    >
      {d}
    </button>
  {/each}
</nav>

<style>
  .dept-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 2.5rem;
    overflow-x: auto;
    padding-bottom: 0.5rem;
  }
  
  .tab {
    background: none;
    border: none;
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
    font-weight: 500;
    color: #666;
    cursor: pointer;
    border-radius: 20px;
    transition: all 0.2s ease;
    white-space: nowrap;
  }
  .tab:hover { color: #111; background: #eee; }
  .tab.active { background: #111; color: #fff; }
</style>
