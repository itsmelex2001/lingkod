<script>
  // Images to be displayed in the infinite carousel
  const images = [
    '/sdn/public/assets/aboutpage_img',
    '/sdn/public/assets/aboutpage_img1',
    '/sdn/public/assets/aboutpage_img2',
    '/sdn/public/assets/aboutpage_img3',
    '/sdn/public/assets/aboutpage_img4'
  ];
  
  // Duplicate array to create a seamless infinite loop
  const carouselImages = [...images, ...images];
</script>

<section id="about" class="about-section">
  <div class="container">
    <h2 class="about-heading" data-aos="fade-up">About Us</h2>
    
    <!-- Infinite Carousel Container -->
    <div class="carousel-wrapper" data-aos="fade-up" data-aos-delay="100">
      <!-- Gradient fades for left and right edges -->
      <div class="fade-left"></div>
      <div class="fade-right"></div>
      
      <!-- Scrolling Track -->
      <div class="carousel-track">
        {#each carouselImages as img, i}
          <div class="carousel-item">
            <img src="{img}" alt="About Us {i}" />
          </div>
        {/each}
      </div>
    </div>

    <!-- Description Text -->
    <div class="about-description" data-aos="fade-up" data-aos-delay="200">
      <p>
        The Mariveles Mental Wellness and General Hospital (MMWGH) serves as a Department of Health hospital, catering to the mental health needs of Northern and Central Luzon. Currently operating under a Level 1 license (LTO No. 03-0171-24-H1-1), with 18 beds, MMWGH also offers essential add-on services including a 500-bed custodial psychiatric care unit, type-1 ambulance service, secondary clinical laboratory, HIV-testing laboratory, and pharmacy.
      </p>
    </div>
  </div>
</section>

<style>
  .about-section {
    padding: 8rem 2rem 10rem;
    background-color: #041006;
    color: white;
    overflow: hidden;
  }
  
  .container { 
    max-width: 1400px; 
    margin: 0 auto; 
    display: flex;
    flex-direction: column;
    align-items: center;
  }
  
  .about-heading { 
    font-family: 'Kaushan Script', cursive; 
    color: #ffffff; 
    font-size: 4.5rem; 
    margin-bottom: 4rem; 
    font-weight: 400;
    text-align: center;
  }
  
  /* Carousel Layout */
  .carousel-wrapper {
    position: relative;
    width: 100vw;
    left: 50%;
    right: 50%;
    margin-left: -50vw;
    margin-right: -50vw;
    overflow: hidden;
    padding: 1rem 0;
    margin-bottom: 5rem;
  }
  
  /* Edge Fades */
  .fade-left, .fade-right {
    position: absolute;
    top: 0;
    bottom: 0;
    width: 15vw;
    z-index: 2;
    pointer-events: none;
  }
  .fade-left {
    left: 0;
    background: linear-gradient(to right, #041006 10%, rgba(4, 16, 6, 0) 100%);
  }
  .fade-right {
    right: 0;
    background: linear-gradient(to left, #041006 10%, rgba(4, 16, 6, 0) 100%);
  }
  
  /* The sliding track */
  .carousel-track {
    display: flex;
    gap: 2rem;
    width: max-content;
    animation: scroll-left 35s linear infinite;
  }
  
  @keyframes scroll-left {
    0% {
      transform: translateX(0);
    }
    100% {
      transform: translateX(calc(-50% - 1rem));
    }
  }
  
  .carousel-item {
    flex: 0 0 auto;
    width: 320px;
    height: 320px;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 10px 25px rgba(0,0,0,0.5);
    background-color: #f0fdf4;
  }
  
  .carousel-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
  }
  
  .about-description {
    max-width: 900px;
    text-align: center;
  }
  
  .about-description p {
    font-family: 'Inter', sans-serif;
    color: #e2e8f0;
    font-size: 0.95rem;
    line-height: 1.8;
    font-style: italic;
    font-weight: 300;
  }

  @media (max-width: 768px) {
    .about-heading { font-size: 3.5rem; margin-bottom: 3rem; }
    .carousel-item { width: 250px; height: 250px; }
    .about-description p { font-size: 0.85rem; padding: 0 1rem; }
  }
</style>
