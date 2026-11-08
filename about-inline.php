<!-- Inline About Section -->
<div id="about-new" style="display: block; width: 100%; scroll-margin-top: 76px;">
<section class="about-section-inline">
  <div class="container-inline-about">
    <h2 class="about-heading-inline" data-aos="fade-up">
      <img src="/sdn/public/assets/about_us_brush.png?v=<?= time() ?>" alt="About Us" class="about-brush-inline" />
    </h2>
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
    <div class="about-description-inline" data-aos="fade-up" data-aos-delay="150" data-aos-offset="0">
      <p data-i18n="about_body">
        The Mariveles Mental Wellness and General Hospital (MMWGH) serves as a Department of Health hospital, catering to the mental health needs of Northern and Central Luzon. Currently operating under a Level 1 license (LTO No. 03-0171-24-H1-1), with 18 beds, MMWGH also offers essential add-on services including a 500-bed custodial psychiatric care unit, type-1 ambulance service, secondary clinical laboratory, HIV-testing laboratory, and pharmacy.
      </p>
    </div>
  </div>
</section>

<!-- Vision & Mission & Quality Policy Section -->
<section class="vision-mission-section">
  <div class="vm-container">
    <div class="vm-card" data-aos="fade-up">
      <h3 class="vm-title">
        <img src="/sdn/public/assets/vision_brush.png?v=<?= time() ?>" alt="Vision" class="vm-brush-inline" />
      </h3>
      <p class="vm-text" data-i18n="vision_body">Mariveles Mental Wellness and General Hospital is the premier client-oriented DOH hospital, providing safe. efficient, and quality services.</p>
      <div class="vm-line"></div>
    </div>
    <div class="vm-card" data-aos="fade-up" data-aos-delay="100">
      <h3 class="vm-title">
        <img src="/sdn/public/assets/mission_brush.png?v=<?= time() ?>" alt="Mission" class="vm-brush-inline" />
      </h3>
      <p class="vm-text" data-i18n="mission_body">We are committed to delivering compassionate, safe, and high-quality healthcare in every encounter by preserving dignity, strengthening hope, and promoting holistic well-being for a healthier and more resilient community.</p>
      <div class="vm-line"></div>
    </div>
  </div>

  <div class="qp-container">
    <div class="vm-card qp-card" data-aos="fade-up" data-aos-delay="150">
      <h3 class="vm-title">
        <img src="/sdn/public/assets/quality_policy_brush.png?v=<?= time() ?>" alt="Quality Policy" class="vm-brush-inline" />
      </h3>
      <div class="qp-text-group">
        <p class="vm-text" data-i18n="quality_1">The Mariveles Mental Wellness and General Hospital is committed to provide affordable quality mental and medical health care with Fairness, Accountability, and Continuous improvement. We shall ensure compliance with statutory and regulatory requirements. We pledge to continually improve our Quality Management System to exceed our client's satisfaction.</p>
        <p class="vm-text" data-i18n="quality_2">We shall ensure compliance with statutory and regulatory requirements. We pledge to continually improve our Quality Management System to exceed our client's satisfaction.</p>
        <p class="vm-text" data-i18n="quality_3">We pledge to continually improve our Quality Management System to exceed our client's satisfaction.</p>
      </div>
      <div class="vm-line"></div>
    </div>
  </div>
</section>
</div>

<style>
  .about-section-inline {
    min-height: calc(100vh - 76px);
    padding: 2.5rem 2rem 3rem;
    background: radial-gradient(ellipse at 50% 45%, #155608 0%, #0d4204 55%, #093502 100%);
    color: white;
    overflow: hidden;
    width: 100%;
    display: flex;
    flex-direction: column;
    justify-content: center;
    box-sizing: border-box;
  }
  
  .container-inline-about { 
    max-width: 1400px; 
    margin: 0 auto; 
    padding: 0 2rem;
  }
  
  .about-heading-inline { 
    margin: 0 0 1.75rem 0 !important; 
    text-align: center;
    display: flex;
    justify-content: center;
    align-items: center;
  }

  .about-brush-inline {
    height: clamp(82px, 8.5vw, 105px);
    width: auto;
    object-fit: contain;
    display: inline-block;
    user-select: none;
    pointer-events: none;
  }
  
  .carousel-wrapper-inline {
    position: relative;
    width: 100%;
    max-width: 1240px;
    margin: 0 auto 1.75rem;
    overflow: hidden;
    padding: 1.25rem 0;
    -webkit-mask-image: linear-gradient(
      to right,
      transparent 0%,
      rgba(0, 0, 0, 0.06) 5%,
      rgba(0, 0, 0, 0.25) 12%,
      rgba(0, 0, 0, 0.6) 20%,
      rgba(0, 0, 0, 0.9) 27%,
      #000000 33%,
      #000000 67%,
      rgba(0, 0, 0, 0.9) 73%,
      rgba(0, 0, 0, 0.6) 80%,
      rgba(0, 0, 0, 0.25) 88%,
      rgba(0, 0, 0, 0.06) 95%,
      transparent 100%
    );
    mask-image: linear-gradient(
      to right,
      transparent 0%,
      rgba(0, 0, 0, 0.06) 5%,
      rgba(0, 0, 0, 0.25) 12%,
      rgba(0, 0, 0, 0.6) 20%,
      rgba(0, 0, 0, 0.9) 27%,
      #000000 33%,
      #000000 67%,
      rgba(0, 0, 0, 0.9) 73%,
      rgba(0, 0, 0, 0.6) 80%,
      rgba(0, 0, 0, 0.25) 88%,
      rgba(0, 0, 0, 0.06) 95%,
      transparent 100%
    );
  }
  
  .fade-left-inline, .fade-right-inline {
    position: absolute;
    top: 0;
    bottom: 0;
    width: 30%;
    z-index: 2;
    pointer-events: none;
  }
  .fade-left-inline {
    left: 0;
    background: radial-gradient(
      ellipse 100% 70% at 0% 50%,
      rgba(4, 26, 1, 0.68) 0%,
      rgba(6, 36, 2, 0.35) 50%,
      rgba(9, 48, 3, 0) 100%
    );
  }
  .fade-right-inline {
    right: 0;
    background: radial-gradient(
      ellipse 100% 70% at 100% 50%,
      rgba(4, 26, 1, 0.68) 0%,
      rgba(6, 36, 2, 0.35) 50%,
      rgba(9, 48, 3, 0) 100%
    );
  }
  
  .carousel-track-inline {
    display: flex;
    gap: 1.5rem;
    width: max-content;
    animation: scroll-left-inline 35s linear infinite;
  }

  .carousel-wrapper-inline:hover .carousel-track-inline,
  .carousel-track-inline:hover {
    animation-play-state: paused;
  }
  
  @keyframes scroll-left-inline {
    0% { transform: translateX(0); }
    100% { transform: translateX(calc(-50% - 0.75rem)); }
  }
  
  .carousel-item-inline {
    flex: 0 0 auto;
    width: clamp(210px, 28vh, 260px);
    height: clamp(210px, 28vh, 260px);
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 10px 25px rgba(0,0,0,0.35);
    background-color: #f0fdf4;
    position: relative; /* important for overlay */
  }
  
  .carousel-item-inline img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
  }
  
  /* Subtle green vignette overlay */
  .image-overlay-inline {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(11, 61, 2, 0.12);
    box-shadow: inset 0 0 24px rgba(9, 53, 2, 0.35);
    pointer-events: none;
    z-index: 1;
  }
  
  .about-description-inline {
    max-width: 820px;
    margin: 0 auto;
    text-align: center;
  }
  
  .about-description-inline p {
    font-family: 'Inter', sans-serif;
    color: #e2e8f0;
    font-size: 0.9rem;
    line-height: 1.75;
    font-style: italic;
    font-weight: 300;
  }

  /* Vision, Mission & Quality Policy Styles */
  .vision-mission-section {
    padding: 5.5rem 2rem 6.5rem;
    background: linear-gradient(to bottom, #98e698 0%, #d4f7d4 22%, #ffffff 45%, #ffffff 100%);
    width: 100%;
  }

  .vm-container {
    max-width: 920px;
    margin: 0 auto;
    display: flex;
    gap: 2.5rem;
    justify-content: center;
    align-items: stretch;
  }

  .qp-container {
    max-width: 820px;
    margin: 2.5rem auto 0;
    display: flex;
    justify-content: center;
  }

  .vm-card {
    background-color: #ffffff;
    border-radius: 22px;
    padding: 2.25rem 2.5rem 0;
    flex: 1;
    max-width: 440px;
    box-shadow: 0 12px 35px rgba(0, 0, 0, 0.09);
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    overflow: hidden;
  }

  .vm-card.qp-card {
    max-width: 820px;
    width: 100%;
    padding: 2.5rem 3.5rem 0;
  }

  .vm-title {
    margin: 0 0 1.25rem 0 !important;
    text-align: center;
    display: flex;
    justify-content: center;
    align-items: center;
  }

  .vm-brush-inline {
    height: clamp(70px, 7.2vw, 86px);
    width: auto;
    object-fit: contain;
    display: inline-block;
    user-select: none;
    pointer-events: none;
  }

  .vm-text {
    font-family: 'Inter', sans-serif;
    font-size: 0.85rem;
    line-height: 1.65;
    color: #334155;
    font-style: italic;
    font-weight: 400;
    margin-bottom: 2rem;
    flex-grow: 1;
    text-align: left;
    width: 100%;
  }

  .qp-text-group {
    width: 100%;
    margin-bottom: 1.5rem;
  }

  .qp-text-group .vm-text {
    margin-bottom: 1rem;
    flex-grow: 0;
  }

  .vm-line {
    width: 130px;
    height: 3.5px;
    background-color: #14530e;
    border-radius: 2px;
    margin-top: auto;
  }

  @media (max-width: 768px) {
    .about-heading-inline { font-size: 3rem; margin-bottom: 1.5rem; }
    .carousel-item-inline { width: 220px; height: 220px; }
    .about-description-inline p { font-size: 0.85rem; padding: 0 1rem; }
    
    .vm-container {
      flex-direction: column;
      align-items: center;
    }
    .vm-card,
    .vm-card.qp-card {
      width: 100%;
      max-width: 100%;
      padding: 2rem 1.75rem 0;
    }
  }
</style>

<script>
</script>
