<script>
  import { onMount } from 'svelte';
  import { getLiveClockString } from '../../utils/formatters.js';

  let clockTime = getLiveClockString();

  onMount(() => {
    const interval = setInterval(() => {
      clockTime = getLiveClockString();
    }, 1000);
    return () => clearInterval(interval);
  });
</script>

<span class="live-clock">
  <span class="live-dot"></span>
  <span class="clock-text">{clockTime}</span>
</span>

<style>
  .live-clock {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    font-size: 0.85rem;
    font-weight: 600;
    color: #475569;
    background: #f1f5f9;
    padding: 4px 10px;
    border-radius: 9999px;
  }

  .live-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #10b981;
    box-shadow: 0 0 6px rgba(16, 185, 129, 0.6);
    animation: pulse 1.8s infinite;
  }

  @keyframes pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.4; transform: scale(0.85); }
  }
</style>
