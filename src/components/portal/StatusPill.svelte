<script>
  export let status = 'Confirmed';

  $: normStatus = (status || '').toLowerCase().trim();

  $: pillStyle = (() => {
    switch (normStatus) {
      case 'confirmed':
      case 'approved':
        return 'bg-[#f0fdf4] text-[#15803d] border-[#bbf7d0]';
      case 'for approval':
      case 'for_approval':
      case 'pending':
        return 'bg-[#fffbeb] text-[#b45309] border-[#fde68a]';
      case 'in consultation':
      case 'ongoing':
        return 'bg-[#f0f9ff] text-[#0369a1] border-[#bae6fd]';
      case 'checked in':
      case 'arrived':
        return 'bg-[#f5f3ff] text-[#6d28d9] border-[#ddd6fe]';
      case 'completed':
        return 'bg-[#f3f4f6] text-[#4b5563] border-[#e5e7eb]';
      case 'missed':
      case 'no-show':
      case 'no_show':
        return 'bg-[#f1f5f9] text-[#64748b] border-[#cbd5e1]';
      case 'cancelled':
      case 'rejected':
      case 'declined':
        return 'bg-[#fef2f2] text-[#b91c1c] border-[#fecaca]';
      default:
        return 'bg-[#f3f4f6] text-[#4b5563] border-[#e5e7eb]';
    }
  })();

  $: isLive = normStatus === 'in consultation' || normStatus === 'ongoing';
</script>

<span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium border {pillStyle}">
  {#if isLive}
    <span class="relative flex h-2 w-2">
      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-sky-400 opacity-75"></span>
      <span class="relative inline-flex rounded-full h-2 w-2 bg-sky-500"></span>
    </span>
  {/if}
  {status}
</span>

