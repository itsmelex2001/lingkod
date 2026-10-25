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
      
      <!-- Using the brush script image for exact match -->
      <img src="/sdn/public/assets/linkod_brush.png" alt="Linkod" class="linkod-img-inline" />
    </div>

    <p class="hero-description-inline">
      Dedicated to promoting total wellness, the hospital delivers quality, compassionate, and patient-centered<br>
      healthcare services to the community and surrounding regions.
    </p>

  </div>

  <!-- Floating Stats Box at the bottom -->
  <div class="stats-box-wrapper-inline">
    <div class="stats-box-inline">
      <div class="stat-item-inline">
        <h3 class="stat-number-inline" id="stat-appointments">0</h3>
        <p class="stat-label-inline">Appointments booked</p>
      </div>
      <div class="stat-item-inline">
        <h3 class="stat-number-inline" id="stat-slots">0</h3>
        <p class="stat-label-inline">Slot schedules available</p>
      </div>
      <div class="stat-item-inline">
        <h3 class="stat-number-inline" id="stat-patients">0</h3>
        <p class="stat-label-inline">Registered patients</p>
      </div>
    </div>
  </div>
</section>

<script>
  document.addEventListener("DOMContentLoaded", () => {
    async function updateLiveStats() {
      try {
        const res = await fetch('/sdn/api/public-stats.php?_=' + Date.now());
        if (res.ok) {
          const data = await res.json();
          if (data.status === 'success') {
            const appEl = document.getElementById('stat-appointments');
            const slotEl = document.getElementById('stat-slots');
            const patEl = document.getElementById('stat-patients');

            if (appEl && appEl.innerText !== String(data.appointmentsBooked)) {
              appEl.innerText = data.appointmentsBooked;
            }
            if (slotEl && slotEl.innerText !== String(data.slotsAvailable)) {
              slotEl.innerText = data.slotsAvailable;
            }
            if (patEl && patEl.innerText !== String(data.registeredPatients)) {
              patEl.innerText = data.registeredPatients;
            }
          }
        }
      } catch (err) {
        console.error('Failed to load live stats:', err);
      }
    }

    // Initial fetch
    updateLiveStats();

    // Real-time polling every 5 seconds
    setInterval(updateLiveStats, 5000);

    // Refresh immediately on tab focus or visibility
    document.addEventListener('visibilitychange', () => {
      if (!document.hidden) updateLiveStats();
    });
    window.addEventListener('focus', updateLiveStats);
  });
</script>

<style>
  .hero-container-inline {
    position: relative;
    width: 100%;
    /* Use the new uploaded background image */
    background-image: url('/sdn/public/assets/LINKod 2.1.png');
    background-size: cover;
    background-position: center;
    min-height: 85vh;
    display: flex !important;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    /* Margin bottom to make room for the floating stats box */
    margin-bottom: 80px; 
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
    margin-top: -75px; /* Shift content slightly higher up per user request, but not too much */
    display: flex;
    flex-direction: column;
    align-items: center;
  }

  .hero-brand-group {
    display: flex;
    align-items: flex-end; /* Align bottom of Linkod with subtitle */
    justify-content: center;
    gap: 15px;
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

  .linkod-img-inline {
    height: clamp(150px, 22vw, 300px); /* Increased size */
    width: auto;
    object-fit: contain;
    image-rendering: high-quality;
    /* Soften edges by adding a tiny black shadow before inverting to white, then add main drop shadow */
    filter: drop-shadow(0 0 0.8px black) drop-shadow(0 0 0.8px black) brightness(0) invert(1) drop-shadow(2px 4px 8px rgba(0,0,0,0.5));
    margin-bottom: -15px; /* Adjust alignment */
  }

  .hero-description-inline {
    font-family: 'Inter', sans-serif;
    color: #f1f8f1;
    font-size: 0.95rem;
    font-weight: 500;
    font-style: italic;
    line-height: 1.6;
    max-width: 800px;
    margin: 60px auto 0; /* Add margin-top to push it closer to the stats box */
    text-shadow: 1px 2px 5px rgba(0,0,0,0.5);
    text-align: center;
  }

  /* ─── Floating Stats Box ─── */
  .stats-box-wrapper-inline {
    position: absolute;
    bottom: -25px; /* Floats just slightly outside the hero border */
    left: 0;
    width: 100%;
    display: flex;
    justify-content: center;
    z-index: 10;
  }

  .stats-box-inline {
    background-color: rgba(92, 200, 81, 0.75); /* Vibrant green with transparency */
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 30px;
    display: flex;
    justify-content: space-around;
    align-items: center;
    padding: 25px 40px;
    width: 90%;
    max-width: 900px;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
  }

  .stat-item-inline {
    text-align: center;
    color: #ffffff;
    flex: 1;
    padding: 0 15px;
  }

  .stat-number-inline {
    font-family: 'Inter', sans-serif;
    font-size: 2.2rem;
    font-weight: 800;
    margin: 0 0 5px 0;
    line-height: 1;
    color: #ffffff !important;
    text-shadow: 1px 2px 5px rgba(0,0,0,0.1);
  }

  .stat-label-inline {
    font-family: 'Inter', sans-serif;
    font-size: 0.75rem;
    font-weight: 600;
    margin: 0;
    opacity: 0.95;
  }

  /* Responsive Adjustments */
  @media (max-width: 768px) {
    .stats-box-wrapper-inline {
      bottom: 0;
      transform: translateY(50%);
    }
    .stats-box-inline {
      flex-direction: column;
      gap: 15px;
      padding: 25px;
      border-radius: 20px;
    }
    .hero-container-inline {
      margin-bottom: 180px; /* Extra room for the taller stacked stats box */
      min-height: auto;
      padding: 100px 0 60px 0;
    }
    .hero-brand-group {
      flex-direction: column;
      align-items: center;
      gap: 5px;
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
    .linkod-img-inline {
      height: 90px;
      margin-bottom: 0;
    }
    .hero-description-inline {
      margin-top: 30px;
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
