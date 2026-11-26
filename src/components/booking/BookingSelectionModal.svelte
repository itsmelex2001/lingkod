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
    const bookButtons = document.querySelectorAll('.hero-btn-book-inline, a[href*="department-select.php"], [data-trigger="booking-modal"]');
    bookButtons.forEach(btn => {
      btn.addEventListener('click', (e) => {
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
  <div class="modal-wrapper" role="dialog" aria-modal="true" aria-labelledby="modalTitle" aria-describedby="modalSubtitle">
    <div class="modal-card">
      <div class="top-accent" aria-hidden="true"></div>

      <button type="button" class="btn-close" on:click={close} aria-label="Close dialog">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>

      <div class="modal-header">
        <div class="header-top">
          <div class="header-icon" aria-hidden="true">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="4" width="18" height="18" rx="2.5"></rect>
              <line x1="16" y1="2" x2="16" y2="6"></line>
              <line x1="8" y1="2" x2="8" y2="6"></line>
              <line x1="3" y1="10" x2="21" y2="10"></line>
              <path d="M9.5 15.5l1.7 1.7 3.8-3.8"></path>
            </svg>
          </div>
          <span class="eyebrow">MMWGH &bull; LINGKOD</span>
        </div>
        <h2 id="modalTitle" class="modal-title">Book an Appointment</h2>
        <p id="modalSubtitle" class="modal-subtitle">Select your patient status to continue.</p>
      </div>

      <div class="modal-body">
        <div class="options-container">
          <a href="/sdn/pages/patient-login.php" target="_blank" rel="noopener noreferrer" on:click={close} class="option-card option-login">
            <div class="option-icon-box icon-primary" aria-hidden="true">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
              </svg>
            </div>
            <div class="option-text">
              <div class="option-title-row">
                <h3>I have an account</h3>
                <span class="pill pill-primary">Returning</span>
              </div>
              <p>Sign in to book faster and view your visit records.</p>
            </div>
            <span class="arrow-chip" aria-hidden="true">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="9 18 15 12 9 6"></polyline>
              </svg>
            </span>
          </a>

          <a href="/sdn/pages/register.php" target="_blank" rel="noopener noreferrer" on:click={close} class="option-card option-new">
            <div class="option-icon-box icon-secondary" aria-hidden="true">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="8.5" cy="7" r="4"></circle>
                <line x1="20" y1="8" x2="20" y2="14"></line>
                <line x1="23" y1="11" x2="17" y2="11"></line>
              </svg>
            </div>
            <div class="option-text">
              <div class="option-title-row">
                <h3>I'm a new patient</h3>
                <span class="pill pill-muted">First visit</span>
              </div>
              <p>Create your patient profile and book your first consultation.</p>
            </div>
            <span class="arrow-chip" aria-hidden="true">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="9 18 15 12 9 6"></polyline>
              </svg>
            </span>
          </a>
        </div>

        <div class="emergency-note" role="note">
          <span class="emergency-icon" aria-hidden="true">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
            </svg>
          </span>
          <p>
            In an emergency, go to the MMWGH Emergency Department or call
            <a href="tel:0479354138">(047) 935-4138</a>.
          </p>
        </div>
      </div>
    </div>
  </div>
{/if}

<style>
  .modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(8, 28, 15, 0.56);
    backdrop-filter: blur(6px);
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
    border-radius: 20px;
    max-width: 468px;
    width: 100%;
    box-shadow: 0 24px 56px -12px rgba(8, 30, 16, 0.28);
    position: relative;
    border: 1px solid rgba(22, 101, 52, 0.14);
    overflow: hidden;
  }

  .top-accent {
    height: 4px;
    width: 100%;
    background: linear-gradient(90deg, #14532d 0%, #16a34a 55%, #4ade80 100%);
  }

  .btn-close {
    position: absolute;
    top: 18px;
    right: 18px;
    width: 38px;
    height: 38px;
    border-radius: 10px;
    border: 1px solid rgba(22, 101, 52, 0.12);
    background: rgba(255, 255, 255, 0.85);
    color: #52635a;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background-color 150ms ease, color 150ms ease, border-color 150ms ease;
  }

  .btn-close:hover {
    background: #f0fdf4;
    border-color: rgba(22, 101, 52, 0.3);
    color: #0f2e1c;
  }

  .btn-close:focus-visible,
  .option-card:focus-visible {
    outline: 2px solid #166534;
    outline-offset: 2px;
  }

  .modal-header {
    padding: 24px 28px 18px;
    background: linear-gradient(180deg, #f1fbf3 0%, #ffffff 100%);
    border-bottom: 1px solid #edf5ef;
  }

  .header-top {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
    padding-right: 44px;
  }

  .header-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: #e7f6ea;
    border: 1px solid #bbf7d0;
    color: #166534;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .eyebrow {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.09em;
    text-transform: uppercase;
    color: #166534;
  }

  .modal-title {
    font-size: 1.45rem;
    font-weight: 700;
    color: #0f2e1c;
    margin: 0;
  }

  .modal-subtitle {
    font-size: 0.88rem;
    color: #52635a;
    margin: 5px 0 0;
  }

  .modal-body {
    padding: 20px 28px 26px;
  }

  .options-container {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }

  .option-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px 16px 16px 18px;
    border-radius: 14px;
    border: 1.5px solid #dce7df;
    background: #ffffff;
    text-decoration: none;
    transition: background-color 160ms ease, border-color 160ms ease, transform 160ms ease, box-shadow 160ms ease;
  }

  .option-card:hover,
  .option-card:focus-visible {
    border-color: #166534;
    background: #f3fbf5;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px -8px rgba(22, 101, 52, 0.18);
  }

  .option-icon-box {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: background-color 160ms ease, color 160ms ease, box-shadow 160ms ease;
  }

  .icon-primary,
  .icon-secondary {
    background: #e7f6ea;
    color: #166534;
  }

  .option-card:hover .option-icon-box,
  .option-card:focus-visible .option-icon-box {
    background: #166534;
    color: #ffffff;
    box-shadow: 0 4px 10px -2px rgba(22, 101, 52, 0.32);
  }

  .option-text {
    flex: 1;
    min-width: 0;
  }

  .option-title-row {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
  }

  .option-text h3 {
    margin: 0;
    font-size: 0.97rem;
    font-weight: 700;
    color: #112418;
  }

  .option-text p {
    margin: 4px 0 0;
    font-size: 0.81rem;
    color: #52635a;
    line-height: 1.4;
  }

  .pill {
    padding: 2px 8px;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 600;
    white-space: nowrap;
  }

  .pill-primary,
  .pill-muted {
    background: #f1f5f9;
    color: #475569;
    transition: background-color 160ms ease, color 160ms ease;
  }

  .option-card:hover .pill,
  .option-card:focus-visible .pill {
    background: #dcfce7;
    color: #14532d;
  }

  .arrow-chip {
    width: 30px;
    height: 30px;
    border-radius: 9999px;
    background: #ffffff;
    border: 1px solid #d7e7dc;
    color: #64748b;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: background-color 160ms ease, border-color 160ms ease, color 160ms ease, transform 160ms ease;
  }

  .option-card:hover .arrow-chip {
    background: #166534;
    border-color: #166534;
    color: #ffffff;
    transform: translateX(2px);
  }

  .emergency-note {
    margin-top: 18px;
    padding: 12px 14px;
    border-radius: 12px;
    background: #fef2f2;
    border: 1px solid #fecaca;
    display: flex;
    align-items: flex-start;
    gap: 10px;
  }

  .emergency-icon {
    width: 26px;
    height: 26px;
    border-radius: 8px;
    background: #fee2e2;
    color: #b91c1c;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  .emergency-note p {
    margin: 0;
    font-size: 12.5px;
    color: #7f1d1d;
    line-height: 1.45;
  }

  .emergency-note a {
    font-weight: 700;
    color: #991b1b;
    text-decoration: underline;
    white-space: nowrap;
  }

  @media (prefers-reduced-motion: reduce) {
    .option-card,
    .arrow-chip,
    .btn-close {
      transition: none !important;
      transform: none !important;
    }
  }
</style>
