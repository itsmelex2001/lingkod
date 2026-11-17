<!-- Inline Hero Section -->
<section id="hero-new" class="hero-container-inline" style="display: flex;">
  <!-- Gradient Overlay -->
  <div class="hero-overlay-inline"></div>

  <!-- Main Centered Content -->
  <div class="hero-content-inline">
    
    <div class="hero-brand-group">
      <div class="hero-left-col">
        <span class="mmwgh-text-inline">MMWGH</span>
        <h2 class="hero-subtitle-inline">
          Localized Integrated Network for Governance,<br> Knowledge, and Operation in Digital Healthcare
        </h2>
      </div>
      
      <!-- Authentic Avallon Alt brush script -->
      <img src="/sdn/public/assets/lingkod_brush.png?v=<?= time() ?>" alt="Lingkod" class="lingkod-brush-inline" />
    </div>

    <p class="hero-description-inline" data-i18n-html="hero_desc">
      Dedicated to promoting total wellness, the hospital delivers quality, compassionate, and patient-centered<br>
      healthcare services to the community and surrounding regions.
    </p>

    <!-- Hero Action Buttons -->
    <div class="hero-cta-group-inline">
      <a href="/sdn/pages/department-select.php" class="hero-btn-inline hero-btn-book-inline" onclick="if(typeof openBookingConfirmModal === 'function') openBookingConfirmModal(event);">
        <span data-i18n="hero_book">Book an Appointment</span>
      </a>
      <a href="/sdn/pages/schedules.php" target="_blank" rel="noopener noreferrer" class="hero-btn-inline hero-btn-schedule-inline">
        <span data-i18n="hero_schedule">View Schedule</span>
        <svg class="hero-btn-arrow-inline" width="26" height="20" viewBox="0 0 26 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <path d="M1 10H24M24 10L16 2M24 10L16 18" stroke="#15803d" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </a>
    </div>

  </div>
</section>

<style>
  .hero-container-inline {
    position: relative;
    width: 100%;
    /* Use uploaded background image */
    background-image: url('/sdn/public/assets/LINKod 2.1.png');
    background-size: cover;
    background-position: center;
    min-height: calc(100vh - 76px);
    display: flex !important;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 80px 20px 60px 20px;
    margin-bottom: 0; 
  }

  /* Original natural top tone from previous version with dark green shadow on the bottom */
  .hero-overlay-inline {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(
      to bottom, 
      rgba(0, 0, 0, 0.1) 0%,      /* Light natural tone at top, exactly like previous */
      rgba(20, 100, 30, 0.7) 60%, /* Transition to green shadow */
      rgba(4, 32, 14, 0.98) 100%  /* Dark shade shadow of green on the bottom part only */
    );
    z-index: 1;
  }

  .hero-content-inline {
    position: relative;
    z-index: 2;
    text-align: center;
    padding: 0 20px;
    margin-top: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 100%;
    max-width: 1200px;
  }

  .hero-brand-group {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 20px;
    margin-bottom: 25px;
  }

  .hero-left-col {
    display: flex;
    flex-direction: column;
    align-items: flex-start; /* Left align subtitle under MMWGH */
    text-align: left;
  }

  .mmwgh-text-inline {
    font-family: Impact, Haettenschweiler, 'Arial Narrow Bold', sans-serif;
    font-size: clamp(5rem, 8vw, 7.5rem);
    color: #ffffff;
    letter-spacing: 2px;
    line-height: 1;
    /* Shadow for depth */
    text-shadow: 2px 4px 15px rgba(0,0,0,0.4);
    margin-bottom: 2px;
  }
  
  .hero-subtitle-inline {
    font-family: 'Inter', sans-serif;
    color: #ffffff;
    font-size: clamp(0.78rem, 1.12vw, 0.92rem);
    font-weight: 700;
    line-height: 1.35;
    margin: 4px 0 0 2px;
    letter-spacing: 0.2px;
    text-shadow: 0 2px 6px rgba(0, 0, 0, 0.65), 1px 2px 3px rgba(0, 0, 0, 0.5);
  }

  /* Authentic Avallon Alt brush script rendering */
  .lingkod-brush-inline {
    height: clamp(160px, 19vw, 245px);
    width: auto;
    object-fit: contain;
    filter: drop-shadow(2px 4px 10px rgba(0, 0, 0, 0.45));
    user-select: none;
    pointer-events: none;
    transform: rotate(-1deg);
    display: inline-block;
  }

  .hero-description-inline {
    font-family: 'Inter', sans-serif;
    color: #f1f8f1;
    font-size: 0.95rem;
    font-weight: 500;
    font-style: italic;
    line-height: 1.6;
    max-width: 1050px;
    margin: 35px auto 0;
    text-shadow: 1px 2px 5px rgba(0,0,0,0.5);
    text-align: center;
  }

  .hero-cta-group-inline {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 24px;
    margin-top: 36px;
    flex-wrap: wrap;
  }

  .hero-btn-inline {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 245px;
    height: 54px;
    padding: 0 28px;
    border-radius: 10px;
    font-family: 'Inter', sans-serif;
    font-size: 1rem;
    text-decoration: none;
    cursor: pointer;
    user-select: none;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.28);
    transition:
      transform 0.18s ease,
      box-shadow 0.18s ease,
      background-color 0.18s ease,
      color 0.18s ease;
    will-change: transform, box-shadow;
  }

  .hero-btn-book-inline {
    background-color: #009a17;
    color: #ffffff;
    font-weight: 700;
    letter-spacing: 0.2px;
    border: 2px solid transparent;
  }

  .hero-btn-book-inline:hover {
    background-color: #008814;
    color: #ffffff;
    transform: translateY(-1px);
    box-shadow: 0 8px 18px rgba(0, 0, 0, 0.28);
  }

  .hero-btn-book-inline:active,
  .hero-btn-book-inline:focus {
    transform: translateY(0);
    background-color: #007511;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.24);
    outline: none;
  }

  .hero-btn-schedule-inline {
    background-color: #ffffff;
    color: #4d7c2b;
    font-weight: 500;
    gap: 18px;
    border: 2px solid transparent;
  }

  .hero-btn-arrow-inline {
    display: inline-block;
    transition: transform 0.32s cubic-bezier(0.22, 1, 0.36, 1);
    flex-shrink: 0;
    will-change: transform;
  }

  .hero-btn-schedule-inline:hover {
    background-color: #ffffff;
    color: #15803d;
    box-shadow: 0 12px 26px rgba(0, 0, 0, 0.32);
  }

  .hero-btn-schedule-inline:hover .hero-btn-arrow-inline {
    transform: translateX(14px);
  }

  .hero-btn-schedule-inline:active {
    transform: translateY(1px) scale(0.97);
    background-color: #ecfccb;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.25);
  }

  /* Responsive Adjustments */
  @media (max-width: 768px) {
    .hero-container-inline {
      margin-bottom: 0;
      box-sizing: border-box;
      min-height: 100vh;
      min-height: 100dvh;
      padding: 80px 15px 50px 15px;
      background-position: center top;
    }
    .hero-brand-group {
      flex-direction: column;
      align-items: center;
      gap: 10px;
    }
    .hero-left-col {
      align-items: center;
      text-align: center;
    }
    .mmwgh-text-inline {
      font-size: 4rem;
    }
    .hero-subtitle-inline {
      font-size: 0.9rem;
    }
    .lingkod-brush-inline {
      height: 145px;
      margin-bottom: 0;
      transform: none;
    }
    .hero-description-inline {
      margin-top: 25px;
      font-size: 0.85rem;
    }
    .hero-cta-group-inline {
      flex-direction: column;
      width: 100%;
      gap: 14px;
      margin-top: 26px;
    }
    .hero-btn-inline {
      width: 100%;
      max-width: 320px;
      min-width: 0;
    }
  }
</style>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    // Force browser to scroll to top on fresh load
    if (window.location.hash !== '#location') {
      window.scrollTo({ top: 0, behavior: 'instant' });
      setTimeout(() => window.scrollTo(0, 0), 100);
    }
  });
</script>
