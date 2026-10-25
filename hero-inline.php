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
          Localized Network and Knowledge-base<br>for Online Service Delivery
        </h2>
      </div>
      
      <!-- Authentic Avallon Alt brush script -->
      <img src="/sdn/public/assets/lingkod_brush.png?v=<?= time() ?>" alt="Lingkod" class="lingkod-brush-inline" />
    </div>

    <p class="hero-description-inline">
      Dedicated to promoting total wellness, the hospital delivers quality, compassionate, and patient-centered<br>
      healthcare services to the community and surrounding regions.
    </p>

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
    min-height: 82vh;
    display: flex !important;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 80px 20px 60px 20px;
    margin-bottom: 0; 
  }

  /* Exact green gradient filter and shadowy bottom */
  .hero-overlay-inline {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(
      to bottom, 
      rgba(0, 0, 0, 0.1) 0%,      /* Light shadow at top */
      rgba(20, 100, 30, 0.7) 60%, /* Transition to green shadow */
      rgba(10, 130, 35, 0.95) 100% /* Pure shadow green at bottom */
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
    font-size: 1.05rem;
    font-weight: 800;
    line-height: 1.4;
    margin: 0;
    letter-spacing: 0.5px;
    /* Exact dark shadowing below the text */
    text-shadow: 
      2px 2px 0px rgba(0, 40, 10, 0.9), 
      -1px -1px 0px rgba(0, 40, 10, 0.9), 
      1px -1px 0px rgba(0, 40, 10, 0.9), 
      -1px 1px 0px rgba(0, 40, 10, 0.9), 
      1px 1px 0px rgba(0, 40, 10, 0.9),
      3px 5px 12px rgba(0, 0, 0, 0.8);
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
    max-width: 800px;
    margin: 35px auto 0;
    text-shadow: 1px 2px 5px rgba(0,0,0,0.5);
    text-align: center;
  }

  /* Responsive Adjustments */
  @media (max-width: 768px) {
    .hero-container-inline {
      margin-bottom: 0;
      min-height: auto;
      padding: 90px 15px 50px 15px;
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
