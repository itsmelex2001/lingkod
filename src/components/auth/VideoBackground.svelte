<script>
  import { onMount } from 'svelte';

  export let videos = [
    '/sdn/public/assets/generalmanagement_clip.mp4',
    '/sdn/public/assets/neurology_clip.mp4',
    '/sdn/public/assets/psychiatry_clip.mp4'
  ];
  export let intervalMs = 5000;

  let currentVideo = 0;

  onMount(() => {
    const interval = setInterval(() => {
      currentVideo = (currentVideo + 1) % videos.length;
    }, intervalMs);
    return () => clearInterval(interval);
  });
</script>

<div class="video-bg-container">
  {#each videos as video, i}
    <video 
      src={video} 
      class="bg-video" 
      class:active={i === currentVideo}
      autoplay 
      loop 
      muted 
      playsinline 
    ></video>
  {/each}
  <div class="video-overlay"></div>
</div>

<style>
  .video-bg-container {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    overflow: hidden;
    z-index: 1;
  }

  .bg-video {
    position: absolute;
    top: 50%;
    left: 50%;
    min-width: 100%;
    min-height: 100%;
    width: auto;
    height: auto;
    transform: translate(-50%, -50%);
    object-fit: cover;
    opacity: 0;
    transition: opacity 1.5s ease-in-out;
  }

  .bg-video.active {
    opacity: 1;
  }

  .video-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(10, 35, 20, 0.65);
    backdrop-filter: blur(8px);
    z-index: 2;
  }
</style>
