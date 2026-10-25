<script>
  import { createEventDispatcher } from 'svelte';

  export let promptText = 'Please rate your experience:';
  export let maxStars = 5;

  let selectedStars = 0;
  let hoveredStars = 0;

  const dispatch = createEventDispatcher();

  function rate(stars) {
    selectedStars = stars;
    dispatch('rate', stars);
  }
</script>

<div class="chat-rating-card">
  <p class="rating-prompt">{promptText}</p>
  <div class="stars-row">
    {#each Array(maxStars) as _, i}
      {@const starVal = i + 1}
      <button
        type="button"
        class="star-btn {starVal <= (hoveredStars || selectedStars) ? 'filled' : ''}"
        on:mouseenter={() => hoveredStars = starVal}
        on:mouseleave={() => hoveredStars = 0}
        on:click={() => rate(starVal)}
        aria-label="Rate {starVal} out of {maxStars}"
      >
        ★
      </button>
    {/each}
  </div>
</div>

<style>
  .chat-rating-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 14px;
    text-align: center;
    margin: 10px 0;
  }

  .rating-prompt {
    font-size: 0.85rem;
    font-weight: 600;
    color: #334155;
    margin: 0 0 10px 0;
  }

  .stars-row {
    display: flex;
    justify-content: center;
    gap: 8px;
  }

  .star-btn {
    background: none;
    border: none;
    font-size: 1.6rem;
    color: #cbd5e1;
    cursor: pointer;
    line-height: 1;
    padding: 0;
    transition: transform 0.15s, color 0.15s;
  }

  .star-btn:hover {
    transform: scale(1.2);
  }

  .star-btn.filled {
    color: #f59e0b;
  }
</style>
