<script>
  export let status = 'available'; // 'available' | 'inclinic' | 'onbreak' | 'emergency' | 'offduty'
  export let pulse = false;
</script>

<span class="status-dot status-{status} {pulse ? 'pulse' : ''}"></span>

<style>
  .status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
    flex-shrink: 0;
  }

  .status-available, .status-inclinic { background: #10b981; }
  .status-onbreak { background: #f59e0b; }
  .status-emergency { background: #ef4444; }
  .status-offduty { background: #64748b; }

  .pulse {
    animation: dotPulse 1.8s infinite;
  }

  @keyframes dotPulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.3); opacity: 0.6; }
  }
</style>
