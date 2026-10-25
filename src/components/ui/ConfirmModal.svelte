<script>
  import { createEventDispatcher } from 'svelte';
  import Modal from './Modal.svelte';
  import Button from './Button.svelte';

  export let isOpen = false;
  export let title = 'Confirm Action';
  export let message = 'Are you sure you want to proceed with this action?';
  export let confirmText = 'Confirm';
  export let cancelText = 'Cancel';
  export let variant = 'danger'; // 'danger' | 'primary'

  const dispatch = createEventDispatcher();

  function onConfirm() {
    dispatch('confirm');
  }

  function onCancel() {
    dispatch('cancel');
  }
</script>

<Modal {isOpen} {title} maxWidth="480px" on:close={onCancel}>
  <p class="confirm-message">{message}</p>
  <slot />

  <svelte:fragment slot="footer">
    <Button variant="outline" on:click={onCancel}>{cancelText}</Button>
    <Button {variant} on:click={onConfirm}>{confirmText}</Button>
  </svelte:fragment>
</Modal>

<style>
  .confirm-message {
    color: #475569;
    font-size: 0.95rem;
    line-height: 1.6;
    margin: 0;
  }
</style>
