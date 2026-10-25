<script>
  import { onMount } from 'svelte';

  // Receive props from PHP via dataset attributes
  export let error = '';
  export let input = '';
  export let role = '';

  let passwordVisible = false;

  const videos = [
    '/sdn/public/assets/generalmanagement_clip.mp4',
    '/sdn/public/assets/neurology_clip.mp4',
    '/sdn/public/assets/psychiatry_clip.mp4'
  ];
  let currentVideo = 0;

  onMount(() => {
    const interval = setInterval(() => {
      currentVideo = (currentVideo + 1) % videos.length;
    }, 5000);
    return () => clearInterval(interval);
  });

  $: welcomeTitle = role === 'admin' ? 'Administrator Portal' : (role === 'staff' ? 'Medical Staff Portal' : 'System Login');
  $: subTitle = 'Please enter your credentials to continue';

  function togglePassword() {
    passwordVisible = !passwordVisible;
  }
</script>

<div class="login-wrapper">
  <!-- Video Background -->
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

  <div class="login-overlay"></div>

  <!-- Branding Left Side -->
  <div class="brand-showcase">
    <h1>Welcome to</h1>
    <img src="/sdn/public/assets/linkod_brush.png" alt="Linkod" class="linkod-brush" />
    <p>Seamless healthcare access and delivery.</p>
  </div>

  <!-- Centered Login Form -->
  <div class="login-card-container">
    <a href="/sdn/index.php" class="btn-back">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
      Back
    </a>

    <div class="form-wrapper">
      <div class="form-header">
        <img src="/sdn/public/assets/mmwgh_logo.png" alt="Hospital Logo" class="form-logo" />
        <h2>{welcomeTitle}</h2>
        <p>{subTitle}</p>
      </div>

      {#if error}
        <div class="error-alert">
          {error}
        </div>
      {/if}

      <form method="POST" action="">
        <div class="input-group">
          <label for="loginUser">Username</label>
          <div class="input-with-icon">
            <svg class="input-icon-left" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
              <circle cx="12" cy="7" r="4"></circle>
            </svg>
            <input
              type="text"
              id="loginUser"
              name="loginUser"
              placeholder="Enter your username"
              value={input}
              required
              autocomplete="username"
            />
          </div>
        </div>

        <div class="input-group">
          <label for="loginPass">Password</label>
          <div class="input-with-icon password-wrapper">
            <svg class="input-icon-left" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
              <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
            <input
              type={passwordVisible ? "text" : "password"}
              id="loginPass"
              name="loginPass"
              placeholder="Enter your password"
              required
              autocomplete="current-password"
            />
            <button type="button" class="btn-toggle-pass" on:click={togglePassword}>
              {#if passwordVisible}
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                  <line x1="1" y1="1" x2="23" y2="23"></line>
                </svg>
              {:else}
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
              {/if}
            </button>
          </div>
          <div class="forgot-row">
            <a href="/sdn/pages/forgot-password.php?role={role}" class="forgot-link">Forgot Password?</a>
          </div>
        </div>

        <button type="submit" class="btn-submit">Sign In</button>
      </form>
    </div>
  </div>
</div>

<style>
  :global(body) {
    margin: 0;
    font-family: 'Inter', sans-serif;
    background-color: #f8faf9;
  }

  .login-wrapper {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    width: 100%;
    position: relative;
    background-color: #0c3915; /* Fallback */
    overflow: hidden;
  }

  .bg-video {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    z-index: 0;
    opacity: 0;
    transition: opacity 1s ease-in-out;
  }
  .bg-video.active {
    opacity: 1;
  }

  .login-overlay {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(30, 70, 45, 0.75);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    z-index: 1;
  }

  .brand-showcase {
    position: absolute;
    left: 10%;
    top: 50%;
    transform: translateY(-50%);
    z-index: 5;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
  }

  .brand-showcase h1 {
    font-size: clamp(3rem, 5vw, 4.5rem);
    font-weight: 800;
    color: white;
    margin: 0 0 10px 0;
    text-shadow: 0 4px 15px rgba(0,0,0,0.5);
  }

  .linkod-brush {
    width: 480px;
    max-width: 100%;
    margin-top: -15px;
    margin-bottom: 15px;
    /* Force the image to be pure white, removing the dark green color and jagged edges */
    filter: brightness(0) invert(1) drop-shadow(0 4px 15px rgba(0,0,0,0.5));
  }

  .brand-showcase p {
    font-size: 1.5rem;
    color: rgba(255, 255, 255, 0.95);
    margin: 0;
    text-shadow: 0 2px 10px rgba(0,0,0,0.5);
  }

  @media (max-width: 960px) {
    .brand-showcase {
      display: none;
    }
  }

  .login-card-container {
    position: relative;
    z-index: 10;
    background: #ffffff;
    width: 100%;
    max-width: 420px;
    padding: 3rem 2.5rem;
    border-radius: 16px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08), 0 1px 3px rgba(0, 0, 0, 0.05);
    display: flex;
    flex-direction: column;
    align-items: center;
    margin: 1rem;
    animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
    box-sizing: border-box;
  }

  .btn-back {
    position: absolute;
    top: 1.5rem;
    left: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.25rem;
    color: #6b7280;
    background: transparent;
    padding: 0;
    border-radius: 0;
    text-decoration: none;
    font-weight: 500;
    font-size: 0.9rem;
    transition: color 0.2s ease;
  }

  .btn-back:hover {
    color: #0c3915;
  }

  .form-wrapper {
    width: 100%;
  }

  @keyframes slideUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
  }

  .form-header {
    text-align: center;
    margin-bottom: 2.5rem;
  }

  .form-logo {
    width: 90px;
    height: auto;
    margin-bottom: 1.5rem;
  }

  .form-header h2 {
    font-family: 'Inter', sans-serif;
    font-size: 1.75rem;
    color: #111827;
    margin-bottom: 0.5rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    margin-top: 0;
  }

  .form-header p {
    color: #9ca3af;
    font-size: 0.9rem;
    font-weight: 400;
    margin: 0;
  }

  .error-alert {
    background-color: #fef2f2;
    border-left: 4px solid #ef4444;
    color: #991b1b;
    padding: 1rem;
    border-radius: 4px;
    margin-bottom: 1.5rem;
    font-size: 0.95rem;
  }

  .input-group {
    margin-bottom: 1.25rem;
  }

  .forgot-row {
    text-align: right;
    margin-top: 0.5rem;
  }

  label {
    display: block;
    font-weight: 500;
    color: #4b5563;
    font-size: 0.85rem;
    margin-bottom: 0.5rem;
  }

  .forgot-link {
    color: #6b7280;
    text-decoration: none;
    font-size: 0.8rem;
    font-weight: 500;
    transition: color 0.2s ease;
  }

  .forgot-link:hover {
    color: #0c3915;
    text-decoration: underline;
  }

  input[type="text"],
  input[type="password"] {
    width: 100%;
    padding: 0.875rem 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-family: 'Inter', sans-serif;
    font-size: 0.95rem;
    color: #111827;
    transition: all 0.2s ease;
    background-color: #f9fafb;
    box-sizing: border-box;
  }

  input:focus {
    outline: none;
    border-color: #0c3915;
    background-color: #ffffff;
    box-shadow: 0 0 0 4px rgba(12, 57, 21, 0.1);
  }

  .password-wrapper {
    position: relative;
    display: flex;
    align-items: center;
  }

  .btn-toggle-pass {
    position: absolute;
    right: 12px;
    background: none;
    border: none;
    color: #9ca3af;
    cursor: pointer;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: color 0.2s ease;
  }

  .btn-toggle-pass:hover {
    color: #4b5563;
  }

  .btn-submit {
    width: 100%;
    padding: 0.875rem;
    background: #0c3915;
    color: white;
    border: none;
    border-radius: 8px;
    font-family: 'Inter', sans-serif;
    font-weight: 500;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.2s ease;
    margin-top: 1rem;
    box-shadow: 0 4px 6px -1px rgba(12, 57, 21, 0.1), 0 2px 4px -1px rgba(12, 57, 21, 0.06);
  }

  .btn-submit:hover {
    background: #114d1d;
    transform: translateY(-1px);
    box-shadow: 0 10px 15px -3px rgba(12, 57, 21, 0.1), 0 4px 6px -2px rgba(12, 57, 21, 0.05);
  }
</style>
