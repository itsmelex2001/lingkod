<script>
</script>

<section class="hero-container">
  <!-- Gradient Overlay -->
  <div class="hero-overlay"></div>

  <!-- Main Centered Content -->
  <div class="hero-content">
    
    <div class="hero-title-row">
      <span class="mmwgh-text">MMWGH</span>
      <!-- Authentic Avallon Alt brush script -->
      <img src="/sdn/public/assets/lingkod_brush.png" alt="Lingkod" class="lingkod-brush" />
    </div>

    <h2 class="hero-subtitle">
      Localized Integrated Network for Governance,<br>Knowledge, and Operation in Digital Healthcare
    </h2>

    <p class="hero-description">
      Dedicated to promoting total wellness, the hospital delivers quality, compassionate, and patient-centered<br>
      healthcare services to the community and surrounding regions.
    </p>

  </div>
</section>

<style>
  .hero-container {
    position: relative;
    width: 100%;
    /* Use uploaded background image */
    background-image: url('/sdn/public/assets/LINKod 2.1.png');
    background-size: cover;
    background-position: center;
    min-height: 80vh;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 80px 20px 60px 20px;
    margin-bottom: 0; 
  }

  /* Original natural top tone from previous version with dark green shadow on the bottom */
  .hero-overlay {
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

  .hero-content {
    position: relative;
    z-index: 2;
    text-align: center;
    padding: 0 20px;
    margin-top: 0;
    width: 100%;
    max-width: 1200px;
  }

  .hero-title-row {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 20px;
    margin-bottom: 15px;
  }

  .mmwgh-text {
    font-family: Impact, Haettenschweiler, 'Arial Narrow Bold', sans-serif;
    font-size: clamp(5rem, 8vw, 7.5rem);
    color: #ffffff;
    letter-spacing: 2px;
    line-height: 1;
    /* Shadow for depth */
    text-shadow: 2px 4px 15px rgba(0,0,0,0.4);
  }

  /* Authentic Avallon Alt brush script rendering */
  .lingkod-brush {
    height: clamp(160px, 19vw, 245px);
    width: auto;
    object-fit: contain;
    filter: drop-shadow(2px 4px 10px rgba(0, 0, 0, 0.45));
    user-select: none;
    pointer-events: none;
    transform: rotate(-1deg);
  }

  .hero-subtitle {
    font-family: 'Inter', sans-serif;
    color: #ffffff;
    font-size: 1.1rem;
    font-weight: 700;
    line-height: 1.4;
    margin-bottom: 30px;
    text-shadow: 1px 2px 8px rgba(0,0,0,0.5);
    letter-spacing: 0.5px;
  }

  .hero-description {
    font-family: 'Inter', sans-serif;
    color: #f1f8f1;
    font-size: 0.95rem;
    font-weight: 500;
    font-style: italic;
    line-height: 1.6;
    max-width: 1050px;
    margin: 0 auto;
    text-shadow: 1px 2px 5px rgba(0,0,0,0.5);
  }

  /* Responsive Adjustments */
  @media (max-width: 768px) {
    .hero-container {
      margin-bottom: 0;
      min-height: auto;
      padding: 90px 15px 50px 15px;
    }
    .hero-title-row {
      flex-direction: column;
      gap: 10px;
    }
    .mmwgh-text {
      font-size: 4rem;
    }
    .lingkod-brush {
      height: 145px;
      transform: none;
    }
  }
</style>
