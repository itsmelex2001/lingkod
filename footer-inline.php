<!-- Inline Footer Section -->
<footer id="footer-new" style="display: block; width: 100%;">

  <!-- Background Image Section -->
  <div class="footer-bg-section-inline">
    <div class="footer-bg-overlay-inline"></div>
    <div class="footer-bg-content-inline">
      <h2 class="footer-script-title-inline">
        <img src="/sdn/public/assets/why_choose_us_brush.png?v=<?= time() ?>" alt="Why Choose Us?" class="why-choose-us-brush-inline" />
      </h2>
      <p class="footer-desc-inline" data-i18n="why_body">
        Located in Mariveles, Bataan, we are a Department of Health (DOH) specialty hospital dedicated to providing accessible, high-quality mental health and general healthcare services delivered by skilled medical professionals with compassion and integrity.
      </p>
    </div>
  </div>

  <!-- Bottom Light Green Section Wrapper -->
  <div class="footer-overlap-wrapper-inline">
    
    <!-- Dark Green Box -->
    <div class="footer-dark-box-inline">
      <!-- Col 1 -->
      <div class="footer-col-inline footer-col-brand-inline">
        <h2 class="footer-logo-inline">MMWGH<br>LINGKod</h2>
        <div class="footer-accent-line footer-accent-brand"></div>
        <p class="footer-tagline-main-inline" data-i18n-html="footer_tagline">A Localized Network and Knowledge-base for<br> Online Service Delivery</p>
        <p class="footer-tagline-sub-inline" data-i18n="footer_tagline_sub">Dedicated to your health, driven by excellence.</p>
      </div>

      <!-- Col 2 -->
      <div class="footer-col-inline footer-col-links-inline">
        <h3 class="footer-heading-inline" data-i18n="footer_links">Quick Links</h3>
        <div class="footer-accent-line footer-accent-links"></div>
        <ul class="footer-links-inline">
          <li><a href="#about-new" data-i18n="footer_about">About Us</a></li>
          <li><a href="#services-new" data-i18n="footer_services">Services</a></li>
          <li><a href="#location-new" data-i18n="footer_location">Location</a></li>
          <li><a href="/sdn/pages/schedules.php" target="_blank" rel="noopener noreferrer" data-i18n="footer_schedules">Schedules</a></li>
          <li><a href="/sdn/pages/department-select.php" class="highlight-link-inline" data-i18n="footer_book">Book Appointment</a></li>
        </ul>
      </div>

      <!-- Col 3 -->
      <div class="footer-col-inline footer-col-contact-inline">
        <h3 class="footer-heading-inline" data-i18n="footer_contact">Contact Information</h3>
        <div class="footer-accent-line footer-accent-contact"></div>
        <div class="contact-info-inline">
          <p class="contact-loc-row-inline">
            <svg viewBox="0 0 24 24" fill="currentColor" class="contact-pin-svg-inline">
              <path fill-rule="evenodd" d="M11.54 22.351l.07.04.028.016a.76.76 0 00.723 0l.028-.015.071-.041a16.975 16.975 0 001.144-.742 19.58 19.58 0 002.683-2.282c1.944-1.99 3.963-4.98 3.963-8.827a8.25 8.25 0 00-16.5 0c0 3.846 2.02 6.837 3.963 8.827a19.58 19.58 0 002.682 2.282 16.975 16.975 0 001.145.742zM12 13.5a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
            </svg>
            <span>Mariveles, Bataan<br><span class="contact-loc-indent-inline">Central Luzon, Philippines</span></span>
          </p>
          <p class="contact-mail-row-inline">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <rect x="3" y="5" width="18" height="14" rx="2" />
              <path stroke-linecap="round" stroke-linejoin="round" d="m3.5 6.5 8.5 6 8.5-6" />
            </svg>
            <span>info@mmwgh.gov.ph</span>
          </p>
        </div>

        <button class="emergency-btn-inline" onclick="window.location.href='tel:09614311824'">
          <div class="emergency-icon-inline">
            <svg viewBox="0 0 24 24" fill="currentColor" style="width:24px; height:24px;">
              <path fill-rule="evenodd" d="M1.5 4.5a3 3 0 013-3h1.372c.86 0 1.61.586 1.819 1.42l1.105 4.423a1.875 1.875 0 01-.694 1.955l-1.293.97c-.135.101-.164.249-.126.352a11.285 11.285 0 006.697 6.697c.103.038.25.009.352-.126l.97-1.293a1.875 1.875 0 011.955-.694l4.423 1.105c.834.209 1.42.959 1.42 1.82V19.5a3 3 0 01-3 3h-2.25C8.552 22.5 1.5 15.448 1.5 6.75V4.5z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="emergency-text-inline">
            <div class="emergency-number-inline">0961-431-1824</div>
            <div class="emergency-label-inline">EMERGENCY</div>
          </div>
        </button>
      </div>
    </div>
  </div>
</footer>

<style>
  @font-face {
    font-family: 'Avallon Alt';
    src: url('/sdn/public/assets/Avallon.ttf') format('truetype'),
         local('Avallon Alt'), local('Avallon-Alt'), local('AvallonAlt'), local('Avallon');
    font-weight: normal;
    font-style: normal;
    font-display: swap;
  }
  @font-face {
    font-family: 'Avallon';
    src: url('/sdn/public/assets/Avallon.ttf') format('truetype'),
         local('Avallon'), local('Avallon Regular'), local('Avallon-Regular');
    font-weight: normal;
    font-style: normal;
    font-display: swap;
  }

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
    font-family: 'Avallon Alt', 'Avallon', 'Kaushan Script', cursive !important;
    color: #ffffff !important;
    font-size: clamp(2.8rem, 5vw, 4.2rem) !important;
    margin-bottom: 0.85rem !important;
    font-weight: 400 !important;
    display: flex;
    justify-content: center;
    align-items: center;
  }

  .why-choose-us-brush-inline {
    height: clamp(80px, 8.5vw, 120px);
    width: auto;
    max-width: 90vw;
    object-fit: contain;
    display: inline-block;
    user-select: none;
    filter: drop-shadow(0 4px 14px rgba(0, 0, 0, 0.7));
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
    background: linear-gradient(to bottom, transparent 80px, #b7cfb3 80px);
    position: relative;
    z-index: 10;
    padding-bottom: 1.25rem;
  }

  /* Dark Green Box */
  .footer-dark-box-inline {
    background-color: #0e4b06; /* Rich forest green from reference */
    width: 100%;
    border-radius: 56px;
    padding: 3rem 11.5% 2.5rem;
    display: grid;
    grid-template-columns: 1.35fr 0.95fr 1.2fr;
    gap: 2.5rem;
    color: white;
    box-shadow: 0 14px 36px rgba(0,0,0,0.22);
    margin-bottom: 1.35rem;
    box-sizing: border-box;
  }

  /* Columns & Typography */
  .footer-col-inline {
    display: flex;
    flex-direction: column;
  }
  .footer-accent-line {
    height: 2.5px;
    background-color: #9ae682;
    margin-bottom: 1.65rem;
  }
  .footer-accent-brand {
    width: 96px;
    margin-left: 16px;
  }
  .footer-accent-links {
    width: 48px;
    margin-left: 6px;
  }
  .footer-accent-contact {
    width: 92px;
    margin-left: 16px;
  }
  .footer-logo-inline {
    font-family: 'Montserrat', sans-serif;
    font-size: 2.75rem;
    font-weight: 800;
    line-height: 1.08;
    margin: 0 0 0.7rem 0;
    letter-spacing: 0.5px;
    color: #ffffff !important;
  }
  .footer-tagline-main-inline {
    font-family: 'Inter', sans-serif;
    font-size: 0.78rem;
    font-weight: 400;
    line-height: 1.45;
    color: #e2e8f0;
    margin: 0 0 0.85rem 0;
  }
  .footer-tagline-sub-inline {
    font-family: 'Inter', sans-serif;
    font-size: 0.62rem;
    font-weight: 400;
    font-style: italic;
    color: #b9e6b2;
    margin: 0;
  }

  .footer-heading-inline {
    font-family: 'Montserrat', sans-serif;
    font-size: 1.45rem;
    font-weight: 700;
    margin: 0.15rem 0 0.55rem 0;
    color: #ffffff !important;
  }

  .footer-links-inline {
    list-style: none;
    padding: 0 0 0 12px;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 0.55rem;
  }
  .footer-links-inline a {
    color: #e2e8f0;
    text-decoration: none;
    font-family: 'Inter', sans-serif;
    font-size: 0.78rem;
    font-weight: 400;
    transition: color 0.2s;
  }
  .footer-links-inline a:hover {
    color: #9ae682;
  }
  .highlight-link-inline {
    color: #a3e695 !important;
    font-weight: 700 !important;
  }

  .contact-info-inline {
    padding-left: 20px;
  }
  .contact-info-inline p {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin: 0 0 0.95rem 0;
    color: #e2e8f0;
    line-height: 1.38;
    font-size: 0.78rem;
    font-family: 'Inter', sans-serif;
    font-weight: 400;
  }
  .contact-mail-row-inline {
    align-items: center !important;
  }
  .contact-loc-indent-inline {
    display: inline-block;
    padding-left: 16px;
  }
  .contact-info-inline svg {
    width: 18px;
    height: 18px;
    color: #ffffff;
    flex-shrink: 0;
    margin-top: 1px;
  }

  /* Emergency Button */
  .emergency-btn-inline {
    background-color: #d60000;
    border-radius: 999px;
    padding: 0.65rem 1.65rem 0.65rem 1.05rem;
    display: inline-flex;
    align-items: center;
    gap: 0.85rem;
    border: none;
    color: white;
    margin-top: 0.35rem;
    margin-left: 5.5rem;
    cursor: pointer;
    transition: transform 0.2s, background-color 0.2s;
    width: fit-content;
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.22);
  }
  .emergency-btn-inline:hover {
    background-color: #b91c1c;
    transform: scale(1.04);
  }
  .emergency-icon-inline {
    background-color: #ffffff;
    color: #d60000;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    flex-shrink: 0;
  }
  .emergency-text-inline {
    text-align: left;
  }
  .emergency-number-inline {
    font-family: 'Montserrat', sans-serif;
    font-size: 1.15rem;
    font-weight: 800;
    line-height: 1.1;
    margin-bottom: 0.12rem;
    letter-spacing: 0.3px;
  }
  .emergency-label-inline {
    font-family: 'Montserrat', sans-serif;
    font-size: 0.88rem;
    font-weight: 800;
    line-height: 1;
    letter-spacing: 0.4px;
  }

  /* Copyright */
  .footer-copyright-inline {
    text-align: center;
    font-family: 'Inter', sans-serif;
    font-size: 0.78rem;
    color: #4b5e48;
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
