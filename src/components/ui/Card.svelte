<script>
  export let hoverable = false;
  export let padding = '20px';
</script>

<div class="ui-card {hoverable ? 'hoverable' : ''}" style="padding: {padding};">
  {#if $$slots.header}
    <div class="card-header">
      <slot name="header" />
    </div>
  {/if}

  <div class="card-body">
    <slot />
  </div>

  {#if $$slots.footer}
    <div class="card-footer">
      <slot name="footer" />
    </div>
  {/if}
</div>

<style>
  .ui-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    transition: all 0.2s ease;
    display: flex;
    flex-direction: column;
  }

  .ui-card.hoverable:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
    border-color: #cbd5e1;
  }

  .card-header {
    border-bottom: 1px solid #f1f5f9;
    padding-bottom: 12px;
    margin-bottom: 14px;
  }

  .card-footer {
    border-top: 1px solid #f1f5f9;
    padding-top: 12px;
    margin-top: 14px;
  }
</style>
