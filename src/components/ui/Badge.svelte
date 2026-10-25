<script>
  export let variant = 'default'; // 'default' | 'success' | 'warning' | 'danger' | 'info' | 'purple' | 'pink'
  export let size = 'md'; // 'sm' | 'md'
</script>

<span class="badge badge-{variant} badge-{size}">
  <slot />
</span>

<style>
  .badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-weight: 600;
    border-radius: 9999px;
    line-height: 1;
    white-space: nowrap;
  }

  .badge-sm {
    padding: 3px 8px;
    font-size: 0.72rem;
  }
  .badge-md {
    padding: 5px 12px;
    font-size: 0.82rem;
  }

  .badge-default {
    background: #f1f5f9;
    color: #475569;
  }
  .badge-success {
    background: #ecfdf5;
    color: #065f46;
  }
  .badge-warning {
    background: #fffbeb;
    color: #92400e;
  }
  .badge-danger {
    background: #fef2f2;
    color: #991b1b;
  }
  .badge-info {
    background: #eff6ff;
    color: #1e40af;
  }
  .badge-purple {
    background: #faf5ff;
    color: #6b21a8;
  }
  .badge-pink {
    background: #fdf2f8;
    color: #9d174d;
  }
</style>
