<script>
  import LiveClock from '../ui/LiveClock.svelte';

  export let title = '';
  export let backUrl = '/sdn/index.php';
  export let showClock = true;
</script>

<header class="top-nav">
  <div class="nav-brand">
    {#if backUrl}
      <a href={backUrl} class="btn-back" title="Go back">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
      </a>
    {/if}
    <div class="brand-text">
      {#if title}
        <h1>{title}</h1>
      {/if}
      {#if showClock}
        <LiveClock />
      {/if}
    </div>
  </div>
  <div class="nav-actions">
    <slot name="actions" />
  </div>
</header>

<style>
  .top-nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 24px;
    background: #ffffff;
    border-bottom: 1px solid #e2e8f0;
    position: sticky;
    top: 0;
    z-index: 50;
  }

  .nav-brand {
    display: flex;
    align-items: center;
    gap: 16px;
  }

  .btn-back {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    color: #475569;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    transition: all 0.2s;
    text-decoration: none;
  }

  .btn-back:hover {
    background: #e2e8f0;
    color: #0f172a;
  }

  .brand-text {
    display: flex;
    align-items: center;
    gap: 14px;
  }

  .brand-text h1 {
    font-size: 1.25rem;
    font-weight: 700;
    color: #0f2e1b;
    margin: 0;
  }

  .nav-actions {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  @media (max-width: 767px) {
    .top-nav {
      padding: 12px 16px;
      gap: 8px;
    }

    .nav-brand,
    .brand-text,
    .nav-actions {
      min-width: 0;
    }

    .brand-text {
      flex-direction: column;
      align-items: flex-start;
      gap: 2px;
    }

    .brand-text h1 {
      font-size: 1.05rem;
    }

    .btn-back {
      width: 40px;
      height: 40px;
      flex-shrink: 0;
    }
  }
</style>
