<script>
  import { onMount } from 'svelte';
  import { statsService } from '../../services/statsService.js';

  let stats = {
    appointmentsBooked: "0",
    slotsAvailable: "0",
    registeredPatients: "0"
  };

  onMount(async () => {
    stats = await statsService.getPublicStats();
  });
</script>

<section class="hero-container">
  <!-- Gradient Overlay -->
  <div class="hero-overlay"></div>

  <!-- Main Centered Content -->
  <div class="hero-content">
    
    <div class="hero-title-row">
      <span class="mmwgh-text">MMWGH</span>
      <!-- Using the brush script image for exact match -->
      <img src="/sdn/public/assets/linkod_brush.png" alt="Linkod" class="linkod-img" />
    </div>

    <h2 class="hero-subtitle">
      Localized Network and Knowledge-base<br>for Online Service Delivery
    </h2>

    <p class="hero-description">
      Dedicated to promoting total wellness, the hospital delivers quality, compassionate, and patient-centered<br>
      healthcare services to the community and surrounding regions.
    </p>

  </div>

  <!-- Floating Stats Box at the bottom -->
  <div class="stats-box-wrapper">
    <div class="stats-box">
      <div class="stat-item">
        <h3 class="stat-number">{stats.appointmentsBooked}</h3>
        <p class="stat-label">Appointments booked</p>
      </div>
      <div class="stat-item">
        <h3 class="stat-number">{stats.slotsAvailable}</h3>
        <p class="stat-label">Slot schedules available</p>
      </div>
      <div class="stat-item">
        <h3 class="stat-number">{stats.registeredPatients}</h3>
        <p class="stat-label">Registered patients</p>
      </div>
    </div>
  </div>
</section>

<style>
  .hero-container {
    position: relative;
    width: 100%;
    /* Use the new uploaded background image */
    background-image: url('/sdn/public/assets/LINKod 2.1.png');
    background-size: cover;
    background-position: center;
    min-height: 85vh;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    /* Margin bottom to make room for the floating stats box */
    margin-bottom: 80px; 
  }

  /* Exact green gradient filter and shadowy bottom */
  .hero-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    /* Green tint matching the screenshot */
    background: linear-gradient(
      to bottom, 
      rgba(67, 184, 73, 0.45) 0%, 
      rgba(15, 115, 23, 0.75) 80%,
      rgba(5, 75, 12, 0.95) 100%
    );
    z-index: 1;
  }

  .hero-content {
    position: relative;
    z-index: 2;
    text-align: center;
    padding: 0 20px;
    margin-top: -50px; /* Shift content slightly up */
  }

  .hero-title-row {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
    margin-bottom: 10px;
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

  .linkod-img {
    height: clamp(80px, 12vw, 150px);
    width: auto;
    object-fit: contain;
    /* Drop shadow on the white brush image to match */
    filter: drop-shadow(2px 4px 10px rgba(0,0,0,0.3));
    margin-bottom: -15px; /* Adjust alignment with MMWGH */
  }

  .hero-subtitle {
    font-family: 'Inter', sans-serif;
    color: #ffffff;
    font-size: 1.1rem;
    font-weight: 700;
    line-height: 1.4;
    margin-bottom: 40px;
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
    max-width: 800px;
    margin: 0 auto;
    text-shadow: 1px 2px 5px rgba(0,0,0,0.5);
  }

  /* ─── Floating Stats Box ─── */
  .stats-box-wrapper {
    position: absolute;
    bottom: -60px; /* Floats half inside and half outside the hero */
    left: 0;
    width: 100%;
    display: flex;
    justify-content: center;
    z-index: 10;
  }

  .stats-box {
    background-color: #5cc851; /* Vibrant green from the screenshot */
    border-radius: 30px;
    display: flex;
    justify-content: space-around;
    align-items: center;
    padding: 25px 40px;
    width: 90%;
    max-width: 900px;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
  }

  .stat-item {
    text-align: center;
    color: #ffffff;
    flex: 1;
    padding: 0 15px;
  }

  .stat-number {
    font-family: 'Inter', sans-serif;
    font-size: 2.2rem;
    font-weight: 800;
    margin: 0 0 5px 0;
    line-height: 1;
    text-shadow: 1px 2px 5px rgba(0,0,0,0.1);
  }

  .stat-label {
    font-family: 'Inter', sans-serif;
    font-size: 0.75rem;
    font-weight: 600;
    margin: 0;
    opacity: 0.95;
  }

  /* Responsive Adjustments */
  @media (max-width: 768px) {
    .stats-box {
      flex-direction: column;
      gap: 20px;
      padding: 30px;
      border-radius: 20px;
      bottom: -100px;
    }
    .hero-container {
      margin-bottom: 120px;
      min-height: 90vh;
    }
    .hero-title-row {
      flex-direction: column;
      gap: 5px;
    }
    .mmwgh-text {
      font-size: 4.5rem;
    }
    .linkod-img {
      height: 90px;
      margin-bottom: 0;
    }
  }
</style>
