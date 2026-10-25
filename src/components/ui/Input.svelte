<script>
  export let value = '';
  export let type = 'text';
  export let placeholder = '';
  export let label = '';
  export let error = '';
  export let disabled = false;
  export let required = false;
  export let id = 'input-' + Math.random().toString(36).substring(2, 7);
</script>

<div class="input-wrapper">
  {#if label}
    <label for={id} class="input-label">
      {label}
      {#if required}<span class="req-star">*</span>{/if}
    </label>
  {/if}

  <div class="input-container">
    {#if $$slots.prefix}
      <span class="input-prefix"><slot name="prefix" /></span>
    {/if}

    <input
      {id}
      {type}
      {placeholder}
      {disabled}
      {required}
      bind:value
      on:input
      on:change
      on:focus
      on:blur
      class="form-input {error ? 'has-error' : ''}"
      {...$$restProps}
    />

    {#if $$slots.suffix}
      <span class="input-suffix"><slot name="suffix" /></span>
    {/if}
  </div>

  {#if error}
    <span class="input-error-msg">{error}</span>
  {/if}
</div>

<style>
  .input-wrapper {
    display: flex;
    flex-direction: column;
    gap: 6px;
    width: 100%;
  }

  .input-label {
    font-size: 0.85rem;
    font-weight: 600;
    color: #334155;
  }
  .req-star {
    color: #ef4444;
    margin-left: 2px;
  }

  .input-container {
    position: relative;
    display: flex;
    align-items: center;
    width: 100%;
  }

  .form-input {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 0.92rem;
    font-family: inherit;
    color: #0f172a;
    background: #ffffff;
    transition: all 0.2s ease;
    box-sizing: border-box;
  }

  .form-input:focus {
    outline: none;
    border-color: #0f4c25;
    box-shadow: 0 0 0 3px rgba(15, 76, 37, 0.12);
  }

  .form-input.has-error {
    border-color: #ef4444;
  }
  .form-input.has-error:focus {
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15);
  }

  .input-prefix, .input-suffix {
    position: absolute;
    display: flex;
    align-items: center;
    color: #94a3b8;
  }
  .input-prefix { left: 12px; }
  .input-suffix { right: 12px; }

  .input-error-msg {
    font-size: 0.78rem;
    color: #ef4444;
    font-weight: 500;
  }
</style>
