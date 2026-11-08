<script>
  import { onMount } from 'svelte';

  export let isOpen = false;

  export function open() {
    isOpen = true;
    document.body.style.overflow = 'hidden';
  }

  export function close() {
    isOpen = false;
    document.body.style.overflow = '';
  }

  function handleKeydown(e) {
    if (e.key === 'Escape' && isOpen) {
      close();
    }
  }

  onMount(() => {
    // Intercept all 'Book an Appointment' buttons on the page
    const bookButtons = document.querySelectorAll('.hero-btn-book-inline, a[href*="department-select.php"], [data-trigger="booking-modal"]');
    bookButtons.forEach(btn => {
      btn.addEventListener('click', (e) => {
        // Prevent default navigation to show the 3 options modal
        e.preventDefault();
        open();
      });
    });

    window.addEventListener('keydown', handleKeydown);
    return () => {
      window.removeEventListener('keydown', handleKeydown);
    };
  });
</script>

{#if isOpen}
  <div class="modal-backdrop" on:click={close} role="presentation"></div>
  <div class="modal-wrapper" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div class="modal-card">
      <!-- Close Button -->
      <button type="button" class="btn-close" on:click={close} aria-label="Close">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>

      <!-- Header -->
      <div class="modal-header">
        <div class="header-icon">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
            <line x1="16" y1="2" x2="16" y2="6"></line>
            <line x1="8" y1="2" x2="8" y2="6"></line>
            <line x1="3" y1="10" x2="21" y2="10"></line>
          </svg>
        </div>
        <h2 id="modalTitle" class="modal-title">Book an Appointment</h2>
        <p class="modal-subtitle">Welcome to MMWGH SDN. Please select your patient status to continue.</p>
      </div>

      <!-- Options Grid -->
      <div class="options-container">
        <!-- Option 1: Already have an account -->
        <a href="/sdn/pages/patient-login.php" target="_blank" rel="noopener noreferrer" on:click={close} class="option-card option-login">
          <div class="option-icon-box">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
              <circle cx="12" cy="7" r="4"></circle>
            </svg>
          </div>
          <div class="option-text">
            <h3>Already Have an Account</h3>
            <p>Sign in to access your medical history and speed up appointment booking.</p>
          </div>
          <div class="option-action">
            <span>Sign In</span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
          </div>
        </a>

        <!-- Option 2: New Patient -->
        <a href="/sdn/pages/register.php" target="_blank" rel="noopener noreferrer" on:click={close} class="option-card option-new">
          <div class="option-icon-box">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
              <circle cx="8.5" cy="7" r="4"></circle>
              <line x1="20" y1="8" x2="20" y2="14"></line>
              <line x1="23" y1="11" x2="17" y2="11"></line>
            </svg>
          </div>
          <div class="option-text">
            <h3>New Patient</h3>
            <p>First-time consultation at MMWGH? Register your record and define credentials.</p>
          </div>
          <div class="option-action">
            <span>Get Started</span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
          </div>
        </a>
      </div>

      <div class="modal-footer">
        Need urgent medical attention? Please proceed immediately to the MMWGH Emergency Department.
      </div>
    </div>
  </div>
{/if}

<style>
  .modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    z-index: 9998;
  }

  .modal-wrapper {
    position: fixed;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
    z-index: 9999;
  }

  .modal-card {
    background: #ffffff;
    border-radius: 24px;
    max-width: 520px;
    width: 100%;
    padding: 32px 28px;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.18);
    position: relative;
    border: 1px solid rgba(22, 163, 74, 0.15);
  }

  .btn-close {
    position: absolute;
    top: 20px;
    right: 20px;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    color: #64748b;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.15s ease;
  }

  .btn-close:hover {
    background: #f1f5f9;
    color: #0f172a;
  }

  .modal-header {
    text-align: center;
    margin-bottom: 24px;
  }

  .header-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    background: #ecfdf5;
    color: #059669;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px;
  }

  .modal-title {
    font-size: 1.45rem;
    font-weight: 700;
    color: #0f5132;
    margin: 0 0 6px;
  }

  .modal-subtitle {
    font-size: 0.88rem;
    color: #64748b;
    line-height: 1.4;
  }

  .options-container {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }

  .option-card {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    text-decoration: none;
    transition: all 0.2s ease;
  }

  .option-card:hover {
    border-color: #10b981;
    background: #f0fdf4;
    transform: translateY(-1px);
    box-shadow: 0 4px 14px rgba(16, 185, 129, 0.12);
  }

  .option-icon-box {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: #ecfdf5;
    color: #059669;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  .option-text {
    flex: 1;
    min-width: 0;
  }

  .option-text h3 {
    margin: 0 0 3px;
    font-size: 0.98rem;
    font-weight: 700;
    color: #1e293b;
  }

  .option-text p {
    margin: 0;
    font-size: 0.8rem;
    color: #64748b;
    line-height: 1.35;
  }

  .option-action {
    display: flex;
    align-items: center;
    gap: 4px;
    color: #059669;
    font-size: 0.88rem;
    font-weight: 600;
    flex-shrink: 0;
  }

  .modal-footer {
    text-align: center;
    font-size: 0.78rem;
    color: #94a3b8;
    margin-top: 20px;
    line-height: 1.4;
  }
</style>

