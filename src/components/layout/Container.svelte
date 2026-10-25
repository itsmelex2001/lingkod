<script>
  export let maxWidth = '1200px';
  export let padding = '0 20px';
</script>

<div class="layout-container" style="max-width: {maxWidth}; padding: {padding};">
  <slot />
</div>

<style>
  .layout-container {
    width: 100%;
    margin-left: auto;
    margin-right: auto;
    box-sizing: border-box;
  }
</style>
