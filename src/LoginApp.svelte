<script>
  // Receive props from PHP via dataset attributes
  export let error = '';
  export let input = '';
  export let role = '';

  let passwordVisible = false;

  $: welcomeTitle = role === 'admin' ? 'Administrator Portal' : (role === 'staff' ? 'Medical Staff Portal' : 'System Login');
  $: subTitle = 'Please enter your credentials to continue';

  function togglePassword() {
    passwordVisible = !passwordVisible;
  }
</script>

<div class="login-wrapper">
  <div class="login-overlay"></div>

  <!-- Centered Login Form -->
  <div class="login-card-container">
    <a href="/index.php" class="btn-back">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
      Back
    </a>

    <div class="form-wrapper">
      <div class="form-header">
        <img src="/public/assets/mmwgh_logo.png" alt="Hospital Logo" class="form-logo" />
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

        <div class="input-group">
          <label for="loginPass">Password</label>
          <div class="password-wrapper">
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
            <a href="/pages/forgot-password.php?role={role}" class="forgot-link">Forgot Password?</a>
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
    background: url('/public/assets/bg_login.jpg') center/cover fixed;
  }

  .login-overlay {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(30, 70, 45, 0.85);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    z-index: 1;
  }

  .login-card-container {
    position: relative;
    z-index: 10;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    width: 100%;
    max-width: 450px;
    padding: 3rem 2.5rem;
    border-radius: 20px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
    display: flex;
    flex-direction: column;
    align-items: center;
    margin: 1rem;
    animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
  }

  .btn-back {
    position: absolute;
    top: 1.5rem;
    left: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.25rem;
    color: #64748b;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
    transition: color 0.3s ease;
  }

  .btn-back:hover {
    color: #1b5e20;
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
    width: 100px;
    height: auto;
    margin-bottom: 1.5rem;
  }

  .form-header h2 {
    font-family: 'Outfit', sans-serif;
    font-size: 1.7rem;
    color: #0f172a;
    margin-bottom: 0.5rem;
    font-weight: 700;
    letter-spacing: -0.01em;
  }

  .form-header p {
    color: #64748b;
    font-size: 0.95rem;
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
    margin-bottom: 1.5rem;
  }

  .forgot-row {
    text-align: right;
    margin-top: 0.5rem;
  }

  label {
    display: block;
    font-weight: 600;
    color: #334155;
    font-size: 0.95rem;
    margin-bottom: 0.5rem;
  }

  .forgot-link {
    color: #10b981;
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 600;
    transition: color 0.3s ease;
  }

  .forgot-link:hover {
    color: #059669;
  }

  input[type="text"],
  input[type="password"] {
    width: 100%;
    padding: 0.85rem 1rem;
    border: 1px solid #cbd5e1;
    border-radius: 12px;
    font-family: 'Inter', sans-serif;
    font-size: 1rem;
    color: #0f172a;
    transition: all 0.3s ease;
    background-color: #f8fafc;
    box-sizing: border-box;
  }

  input:focus {
    outline: none;
    border-color: #10b981;
    box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
    background-color: #ffffff;
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
    color: #94a3b8;
    cursor: pointer;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: color 0.3s ease;
  }

  .btn-toggle-pass:hover {
    color: #475569;
  }

  .btn-submit {
    width: 100%;
    padding: 0.9rem;
    background: #1b5e20;
    color: white;
    border: none;
    border-radius: 12px;
    font-family: 'Inter', sans-serif;
    font-weight: 600;
    font-size: 1.05rem;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 1rem;
  }

  .btn-submit:hover {
    background: #144d18;
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(27, 94, 32, 0.15);
  }
</style>
