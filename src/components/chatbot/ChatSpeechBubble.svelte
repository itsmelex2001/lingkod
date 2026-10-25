<script>
  import { createEventDispatcher } from 'svelte';

  export let text = 'ASK ME';
  export let visible = true;

  const dispatch = createEventDispatcher();
</script>

{#if visible}
  <!-- svelte-ignore a11y-click-events-have-key-events -->
  <!-- svelte-ignore a11y-no-static-element-interactions -->
  <div class="speech-bubble" on:click={() => dispatch('click')}>
    <span class="bubble-text">{text}</span>
    <span class="bubble-arrow"></span>
  </div>
{/if}

<style>
  .speech-bubble {
    position: absolute;
    bottom: 120px;
    right: 20px;
    background: #0f4c25;
    color: #ffffff;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 700;
    font-size: 0.8rem;
    letter-spacing: 0.5px;
    box-shadow: 0 8px 20px rgba(15, 76, 37, 0.3);
    cursor: pointer;
    user-select: none;
    z-index: 100;
    animation: bounce 2s infinite ease-in-out;
    white-space: nowrap;
  }

  .bubble-arrow {
    position: absolute;
    bottom: -6px;
    right: 30px;
    width: 0;
    height: 0;
    border-left: 6px solid transparent;
    border-right: 6px solid transparent;
    border-top: 6px solid #0f4c25;
  }

  @keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-8px); }
  }
</style>
