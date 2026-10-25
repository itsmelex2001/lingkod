<!-- Inline About Section -->
<div id="about-new" style="display: block; width: 100%;">
<section class="about-section-inline">
  <div class="container-inline-about">
    <h2 class="about-heading-inline" data-aos="fade-up">About Us</h2>
  </div>
  
  <!-- Infinite Carousel Container (Full Width) -->
  <div class="carousel-wrapper-inline" data-aos="fade-up" data-aos-delay="100">
    <div class="fade-left-inline"></div>
    <div class="fade-right-inline"></div>
      
      <div class="carousel-track-inline">
        <?php for($loop=0; $loop<2; $loop++): ?>
          <div class="carousel-item-inline">
            <img src="/sdn/public/assets/aboutpage_img" alt="About Us" />
            <div class="image-overlay-inline"></div>
          </div>
          <div class="carousel-item-inline">
            <img src="/sdn/public/assets/aboutpage_img1" alt="About Us" />
            <div class="image-overlay-inline"></div>
          </div>
          <div class="carousel-item-inline">
            <img src="/sdn/public/assets/aboutpage_img2" alt="About Us" />
            <div class="image-overlay-inline"></div>
          </div>
          <div class="carousel-item-inline">
            <img src="/sdn/public/assets/aboutpage_img3" alt="About Us" />
            <div class="image-overlay-inline"></div>
          </div>
          <div class="carousel-item-inline">
            <img src="/sdn/public/assets/aboutpage_img4" alt="About Us" />
            <div class="image-overlay-inline"></div>
          </div>
        <?php endfor; ?>
      </div>
    </div>

  <div class="container-inline-about">
    <!-- Description Text -->
    <div class="about-description-inline" data-aos="fade-up" data-aos-delay="200">
      <p>
        The Mariveles Mental Wellness and General Hospital (MMWGH) serves as a Department of Health hospital, catering to the mental health needs of Northern and Central Luzon. Currently operating under a Level 1 license (LTO No. 03-0171-24-H1-1), with 18 beds, MMWGH also offers essential add-on services including a 500-bed custodial psychiatric care unit, type-1 ambulance service, secondary clinical laboratory, HIV-testing laboratory, and pharmacy.
      </p>
    </div>
  </div>
</section>

<!-- Vision & Mission Section -->
<section class="vision-mission-section">
  <div class="vm-container">
    <div class="vm-card" data-aos="fade-up">
      <h3 class="vm-title">Vision</h3>
      <p class="vm-text">Mariveles Mental Wellness and General Hospital is the premier client-oriented DOH hospital, providing safe. efficient, and quality services.</p>
      <div class="vm-line"></div>
    </div>
    <div class="vm-card" data-aos="fade-up" data-aos-delay="100">
      <h3 class="vm-title">Mission</h3>
      <p class="vm-text">We are committed to delivering compassionate, safe, and high-quality healthcare in every encounter by preserving dignity, strengthening hope, and promoting holistic well-being for a healthier and more resilient community.</p>
      <div class="vm-line"></div>
    </div>
  </div>
</section>
</div>

<style>
  .about-section-inline {
    padding: 8rem 2rem 10rem;
    background-color: #041006;
    color: white;
    overflow: hidden;
    width: 100%;
  }
  
  .container-inline-about { 
    max-width: 1400px; 
    margin: 0 auto; 
    padding: 0 2rem;
  }
  
  .about-heading-inline { 
    font-family: 'Kaushan Script', cursive; 
    color: #ffffff; 
    font-size: 4.5rem; 
    margin-bottom: 4rem; 
    font-weight: 400;
    text-align: center;
  }
  
  .carousel-wrapper-inline {
    position: relative;
    width: 100%;
    overflow: hidden;
    padding: 1rem 0;
    margin-bottom: 5rem;
  }
  
  .fade-left-inline, .fade-right-inline {
    position: absolute;
    top: 0;
    bottom: 0;
    width: 15vw;
    z-index: 2;
    pointer-events: none;
  }
  .fade-left-inline {
    left: 0;
    background: linear-gradient(to right, #041006 10%, rgba(4, 16, 6, 0) 100%);
  }
  .fade-right-inline {
    right: 0;
    background: linear-gradient(to left, #041006 10%, rgba(4, 16, 6, 0) 100%);
  }
  
  .carousel-track-inline {
    display: flex;
    gap: 2rem;
    width: max-content;
    animation: scroll-left-inline 35s linear infinite;
  }
  
  @keyframes scroll-left-inline {
    0% { transform: translateX(0); }
    100% { transform: translateX(calc(-50% - 1rem)); }
  }
  
  .carousel-item-inline {
    flex: 0 0 auto;
    width: 320px;
    height: 320px;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 10px 25px rgba(0,0,0,0.5);
    background-color: #f0fdf4;
    position: relative; /* important for overlay */
  }
  
  .carousel-item-inline img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
  }
  
  /* Green shadow & blur overlay effect */
  .image-overlay-inline {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(4, 30, 10, 0.4); /* Deep green tint */
    box-shadow: inset 0 0 40px rgba(4, 20, 6, 0.8); /* Inner dark green shadow */
    pointer-events: none;
    z-index: 1;
  }
  
  .about-description-inline {
    max-width: 900px;
    margin: 0 auto;
    text-align: center;
  }
  
  .about-description-inline p {
    font-family: 'Inter', sans-serif;
    color: #e2e8f0;
    font-size: 0.95rem;
    line-height: 1.8;
    font-style: italic;
    font-weight: 300;
  }

  /* Vision & Mission Styles */
  .vision-mission-section {
    padding: 8rem 2rem;
    background: linear-gradient(to bottom, #bbf7d0 0%, #ffffff 70%); /* Light green to white fade */
    width: 100%;
  }

  .vm-container {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    gap: 3rem;
    justify-content: center;
  }

  .vm-card {
    background-color: #ffffff;
    border-radius: 24px;
    padding: 3rem 2.5rem;
    flex: 1;
    max-width: 500px;
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
  }

  .vm-title {
    font-family: 'Kaushan Script', cursive;
    font-size: 3rem;
    color: #166534; /* Dark green */
    margin-bottom: 1.5rem;
    font-weight: 400;
  }

  .vm-text {
    font-family: 'Inter', sans-serif;
    font-size: 0.95rem;
    line-height: 1.7;
    color: #475569;
    font-style: italic;
    font-weight: 400;
    margin-bottom: 2.5rem;
    flex-grow: 1;
  }

  .vm-line {
    width: 120px;
    height: 4px;
    background-color: #166534;
    border-radius: 2px;
  }

  @media (max-width: 768px) {
    .about-heading-inline { font-size: 3.5rem; margin-bottom: 3rem; }
    .carousel-item-inline { width: 250px; height: 250px; }
    .about-description-inline p { font-size: 0.85rem; padding: 0 1rem; }
    
    .vm-container {
      flex-direction: column;
      align-items: center;
    }
    .vm-card {
      width: 100%;
    }
  }
</style>

<script>
</script>
