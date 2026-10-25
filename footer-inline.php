<!-- Inline Footer Section -->
<footer id="footer-new" style="display: block; width: 100%;">

  <!-- Background Image Section -->
  <div class="footer-bg-section-inline">
    <div class="footer-bg-overlay-inline"></div>
    <div class="footer-bg-content-inline">
      <h2 class="footer-script-title-inline">Why Choose Us?</h2>
      <p class="footer-desc-inline">
        Located in Mariveles, Bataan, we are a Department of Health (DOH) specialty hospital dedicated to providing accessible, high-quality mental health and general healthcare services delivered by skilled medical professionals with compassion and integrity.
      </p>
    </div>
  </div>

  <!-- Bottom Light Green Section Wrapper -->
  <div class="footer-overlap-wrapper-inline">
    
    <!-- Dark Green Box -->
    <div class="footer-dark-box-inline">
      <!-- Col 1 -->
      <div class="footer-col-inline">
        <h2 class="footer-logo-inline">MMWGH<br>LINKod</h2>
        <div class="footer-accent-line"></div>
        <p class="footer-tagline-main-inline">A Localized Network and Knowledge-base for<br>Online Service Delivery</p>
        <p class="footer-tagline-sub-inline">Dedicated to your health, driven by excellence.</p>
      </div>

      <!-- Col 2 -->
      <div class="footer-col-inline">
        <h3 class="footer-heading-inline">Quick Links</h3>
        <div class="footer-accent-line"></div>
        <ul class="footer-links-inline">
          <li><a href="#about-new">About Us</a></li>
          <li><a href="#services-new">Services</a></li>
          <li><a href="#location-new">Location</a></li>
          <li><a href="/sdn/pages/schedules.php">Schedules</a></li>
          <li><a href="/sdn/pages/department-select.php" class="highlight-link-inline">Book Appointment</a></li>
        </ul>
      </div>

      <!-- Col 3 -->
      <div class="footer-col-inline">
        <h3 class="footer-heading-inline">Contact Information</h3>
        <div class="footer-accent-line"></div>
        <div class="contact-info-inline">
          <p>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" /></svg>
            <span>Mariveles, Bataan<br>Central Luzon, Philippines</span>
          </p>
          <p style="align-items: center;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" /></svg>
            <span>info@mmwgh.gov.ph</span>
          </p>
        </div>

        <button class="emergency-btn-inline" onclick="window.location.href='tel:09614311824'">
          <div class="emergency-icon-inline">
            <svg viewBox="0 0 24 24" fill="currentColor" style="width:20px; height:20px;"><path d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" /></svg>
          </div>
          <div class="emergency-text-inline">
            <div class="emergency-number-inline">0961-431-1824</div>
            <div class="emergency-label-inline">EMERGENCY</div>
          </div>
        </button>
      </div>
    </div>

    <!-- Copyright -->
    <div class="footer-copyright-inline">
      &copy; 2026Service Delivery Network (LINKod). All Rights Reserved.
    </div>
  </div>
</footer>

<style>
  /* Base sections */
  .footer-bg-section-inline {
    position: relative;
    width: 100%;
    min-height: 550px; /* Increased to show more of the image */
    background-image: url('/sdn/public/assets/footer_bg_img.jpg');
    background-size: cover;
    background-position: center 20%; /* Adjusted to focus on people and banner */
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    padding: 3rem 2rem 12rem 2rem; /* Significantly more bottom padding to expose the banner */
    z-index: 1;
  }
  .footer-bg-overlay-inline {
    position: absolute;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0, 0, 0, 0.7); /* Slightly darker */
    z-index: 2;
  }
  .footer-bg-content-inline {
    position: relative;
    z-index: 3;
    max-width: 900px;
  }
  
  .footer-script-title-inline {
    font-family: 'Kaushan Script', cursive;
    color: #ffffff;
    font-size: 4rem;
    margin-bottom: 1.5rem;
    font-weight: 400;
  }
  .footer-desc-inline {
    font-family: 'Inter', sans-serif;
    color: #ffffff;
    font-size: 1.1rem;
    line-height: 1.8;
    font-weight: 400;
  }

  /* Bottom light green wrapper with transparent top */
  .footer-overlap-wrapper-inline {
    width: 100%;
    margin-top: -80px; /* Pulls the entire section up over the image */
    background: linear-gradient(to bottom, transparent 80px, #a1d6a3 80px);
    position: relative;
    z-index: 10;
    padding-bottom: 1.5rem;
  }

  /* Dark Green Box */
  .footer-dark-box-inline {
    background-color: #091b05; /* Exact shade from mockup */
    width: 100%; /* No spaces on left/right */
    border-radius: 60px;
    padding: 4rem 10%; /* Percentage padding to center content */
    display: grid;
    grid-template-columns: 1.5fr 1fr 1fr;
    gap: 3rem;
    color: white;
    box-shadow: 0 15px 40px rgba(0,0,0,0.2);
    margin-bottom: 3rem;
  }

  /* Columns & Typography */
  .footer-col-inline {
    display: flex;
    flex-direction: column;
  }
  .footer-accent-line {
    width: 60px;
    height: 3px;
    background-color: #8bc34a;
    margin-bottom: 2rem;
  }
  .footer-logo-inline {
    font-family: 'Montserrat', sans-serif;
    font-size: 3rem;
    font-weight: 900;
    line-height: 1.1;
    margin-bottom: 0.5rem; /* Reduced to bring accent line closer */
    letter-spacing: 1px;
    color: #ffffff !important;
  }
  .footer-tagline-main-inline {
    font-family: 'Inter', sans-serif;
    font-size: 0.9rem;
    font-weight: 500;
    line-height: 1.6;
    color: #e2e8f0;
    margin-bottom: 1rem;
  }
  .footer-tagline-sub-inline {
    font-family: 'Inter', sans-serif;
    font-size: 0.75rem;
    font-weight: 500;
    font-style: italic;
    color: #8bc34a;
  }

  .footer-heading-inline {
    font-family: 'Montserrat', sans-serif;
    font-size: 1.5rem;
    font-weight: 800;
    margin-bottom: 0.5rem; /* Reduced to bring accent line closer */
    color: #ffffff !important;
  }

  .footer-links-inline {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 0.8rem;
  }
  .footer-links-inline a {
    color: #e2e8f0;
    text-decoration: none;
    font-family: 'Inter', sans-serif;
    font-size: 0.9rem;
    font-weight: 500;
    transition: color 0.2s;
  }
  .footer-links-inline a:hover {
    color: #8bc34a;
  }
  .highlight-link-inline {
    color: #8bc34a !important;
    font-weight: 700 !important;
  }

  .contact-info-inline p {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 1.2rem;
    color: #e2e8f0;
    line-height: 1.4;
    font-size: 0.9rem;
    font-family: 'Inter', sans-serif;
    font-weight: 500;
  }
  .contact-info-inline svg {
    width: 20px;
    height: 20px;
    color: #ffffff;
    flex-shrink: 0;
    margin-top: 2px;
  }

  /* Emergency Button */
  .emergency-btn-inline {
    background-color: #dc2626; /* bright red */
    border-radius: 40px;
    padding: 0.5rem 1.5rem 0.5rem 0.5rem;
    display: inline-flex;
    align-items: center;
    gap: 1rem;
    border: none;
    color: white;
    margin-top: 1rem;
    cursor: pointer;
    transition: transform 0.2s, background-color 0.2s;
    width: fit-content;
  }
  .emergency-btn-inline:hover {
    background-color: #b91c1c;
    transform: scale(1.05);
  }
  .emergency-icon-inline {
    background-color: #ffffff;
    color: #dc2626;
    width: 45px;
    height: 45px;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
  }
  .emergency-text-inline {
    text-align: left;
  }
  .emergency-number-inline {
    font-family: 'Montserrat', sans-serif;
    font-size: 1.2rem;
    font-weight: 900;
    line-height: 1;
    margin-bottom: 0.1rem;
  }
  .emergency-label-inline {
    font-family: 'Inter', sans-serif;
    font-size: 0.85rem;
    font-weight: 700;
    line-height: 1;
  }

  /* Copyright */
  .footer-copyright-inline {
    text-align: center;
    font-family: 'Inter', sans-serif;
    font-size: 0.85rem;
    color: #475569;
    font-weight: 500;
  }

  /* Responsive */
  @media (max-width: 960px) {
    .footer-dark-box-inline {
      grid-template-columns: 1fr;
      padding: 3rem 2rem;
      border-radius: 40px;
    }
    .footer-script-title-inline { font-size: 3rem; }
    .footer-desc-inline { font-size: 0.95rem; }
  }
</style>

</script>
