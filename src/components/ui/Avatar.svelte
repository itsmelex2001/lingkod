<script>
  import { getInitials } from '../../utils/formatters.js';

  export let src = '';
  export let name = '';
  export let size = 'md'; // 'sm' | 'md' | 'lg' | 'xl'
  export let alt = 'Avatar';

  $: initials = getInitials(name);
</script>

<div class="avatar avatar-{size}">
  {#if src}
    <img {src} {alt} class="avatar-img" on:error={() => src = ''} />
  {:else}
    <span class="avatar-initials">{initials}</span>
  {/if}
</div>

<style>
  .avatar {
    border-radius: 50%;
    overflow: hidden;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #0f4c25;
    color: #ffffff;
    font-weight: 700;
    flex-shrink: 0;
    user-select: none;
  }

  .avatar-sm { width: 32px; height: 32px; font-size: 0.75rem; }
  .avatar-md { width: 42px; height: 42px; font-size: 0.9rem; }
  .avatar-lg { width: 56px; height: 56px; font-size: 1.15rem; }
  .avatar-xl { width: 72px; height: 72px; font-size: 1.4rem; }

  .avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }
</style>
