<?php
$heroBgVer = @filemtime(__DIR__ . '/public/assets/hero-bg.webp') ?: '1';
$lingkodBrushVer = @filemtime(__DIR__ . '/public/assets/lingkod_brush.png') ?: '1';
?>
<!-- Inline Hero Section -->
<section id="hero-new" class="hero-container-inline" style="display: flex;" aria-labelledby="hero-heading">
  <!-- Gradient Overlay -->
  <div class="hero-overlay-inline" aria-hidden="true"></div>

  <!-- Main Centered Content -->
  <div class="hero-content-inline">

    <div class="hero-brand-group">
      <div class="hero-left-col">
        <h1 id="hero-heading" class="mmwgh-text-inline">MMWGH<span class="sr-only"> Lingkod</span></h1>
        <p class="hero-subtitle-inline" data-i18n-html="hero_sub">
          Localized Integrated Network for Governance,<br>Knowledge, and Operation in Digital Healthcare
        </p>
      </div>

      <img
        src="/sdn/public/assets/lingkod_brush.png?v=<?= $lingkodBrushVer ?>"
        alt=""
        aria-hidden="true"
        class="lingkod-brush-inline"
        width="726"
        height="362"
      />
    </div>

    <p class="hero-description-inline" data-i18n-html="hero_desc">
      Dedicated to promoting total wellness, the hospital delivers quality, compassionate, and patient-centered healthcare services to the community and surrounding regions.
    </p>

    <!-- Hero Action Buttons -->
    <div class="hero-cta-group-inline">
      <a href="/sdn/pages/department-select.php" class="hero-btn-inline hero-btn-book-inline" onclick="if(typeof openBookingConfirmModal === 'function') openBookingConfirmModal(event);">
        <span data-i18n="hero_book">Book an Appointment</span>
      </a>
      <a href="/sdn/pages/schedules.php" target="_blank" rel="noopener noreferrer" class="hero-btn-inline hero-btn-schedule-inline">
        <span data-i18n="hero_schedule">View Schedule</span>
        <svg class="hero-btn-arrow-inline" width="24" height="18" viewBox="0 0 26 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <path d="M1 10H24M24 10L16 2M24 10L16 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </a>
    </div>

  </div>
</section>

<style>
  .hero-container-inline {
    position: relative;
    width: 100%;
    background-image: url('/sdn/public/assets/hero-bg.webp?v=<?= $heroBgVer ?>');
    background-size: cover;
    background-position: center;
    min-height: calc(100vh - 86px);
    min-height: calc(100dvh - 86px);
    display: flex !important;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 72px 20px 60px;
    margin-bottom: 0;
  }

  .hero-overlay-inline {
    position: absolute;
    inset: 0;
    background: linear-gradient(
      to bottom,
      rgba(4, 24, 10, 0.28) 0%,
      rgba(14, 78, 28, 0.72) 55%,
      rgba(4, 32, 14, 0.96) 100%
    );
    z-index: 1;
    pointer-events: none;
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
    margin-bottom: 24px;
  }

  .hero-left-col {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    text-align: left;
  }

  .mmwgh-text-inline {
    margin: 0 0 4px;
    font-family: Impact, 'Anton', Haettenschweiler, 'Arial Narrow Bold', sans-serif;
    font-size: clamp(5rem, 8vw, 7.5rem);
    font-weight: 400;
    color: #ffffff;
    letter-spacing: 2px;
    line-height: 1;
    text-shadow: 2px 4px 15px rgba(0, 0, 0, 0.42);
  }

  .hero-subtitle-inline {
    font-family: 'Inter', sans-serif;
    color: #ffffff;
    font-size: clamp(0.9rem, 1.18vw, 1.02rem);
    font-weight: 600;
    line-height: 1.4;
    margin: 4px 0 0 2px;
    letter-spacing: 0.15px;
    text-shadow: 0 2px 8px rgba(0, 0, 0, 0.65);
  }

  .lingkod-brush-inline {
    height: clamp(160px, 19vw, 245px);
    width: auto;
    object-fit: contain;
    filter: drop-shadow(2px 4px 10px rgba(0, 0, 0, 0.45));
    user-select: none;
    pointer-events: none;
    display: inline-block;
  }

  .hero-description-inline {
    font-family: 'Inter', sans-serif;
    color: #f1f8f1;
    font-size: 0.98rem;
    font-weight: 500;
    font-style: italic;
    line-height: 1.6;
    max-width: 780px;
    margin: 30px auto 0;
    text-shadow: 1px 2px 6px rgba(0, 0, 0, 0.55);
    text-align: center;
  }

  .hero-cta-group-inline {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 20px;
    margin-top: 34px;
    flex-wrap: wrap;
  }

  .hero-btn-inline {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 256px;
    height: 54px;
    padding: 0 28px;
    border-radius: 12px;
    font-family: 'Inter', sans-serif;
    font-size: 1rem;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
    user-select: none;
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.24);
    transition:
      transform 0.18s ease,
      box-shadow 0.18s ease,
      background-color 0.18s ease,
      border-color 0.18s ease,
      color 0.18s ease;
    will-change: transform, box-shadow;
  }

  .hero-btn-inline:focus-visible {
    outline: 3px solid #ffffff;
    outline-offset: 3px;
  }

  .hero-btn-book-inline {
    background-color: #04561e;
    color: #ffffff;
    letter-spacing: 0.1px;
    border: 2px solid rgba(255, 255, 255, 0.65);
  }

  .hero-btn-book-inline:hover {
    background-color: #034818;
    color: #ffffff;
    border-color: #ffffff;
    transform: translateY(-1px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
  }

  .hero-btn-book-inline:active {
    transform: translateY(0);
    background-color: #023812;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.22);
  }

  .hero-btn-schedule-inline {
    background-color: #ffffff;
    color: #15803d;
    gap: 14px;
    border: 2px solid transparent;
  }

  .hero-btn-arrow-inline {
    display: inline-block;
    transition: transform 0.28s cubic-bezier(0.22, 1, 0.36, 1);
    flex-shrink: 0;
    will-change: transform;
  }

  .hero-btn-schedule-inline:hover {
    background-color: #f8fff9;
    color: #14532d;
    transform: translateY(-1px);
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.3);
  }

  .hero-btn-schedule-inline:hover .hero-btn-arrow-inline {
    transform: translateX(8px);
  }

  .hero-btn-schedule-inline:active {
    transform: translateY(0);
    background-color: #ecfccb;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.22);
  }

  .sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
  }

  @media (max-width: 768px) {
    .hero-container-inline {
      margin-bottom: 0;
      box-sizing: border-box;
      min-height: 100vh;
      min-height: 100dvh;
      padding: 84px 15px 50px;
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
      margin-top: 24px;
      font-size: 0.9rem;
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
    if (!window.location.hash) {
      window.scrollTo({ top: 0, behavior: 'instant' });
    }
  });
</script>
