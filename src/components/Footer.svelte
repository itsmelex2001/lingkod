<footer class="premium-footer">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-col brand-col">
        <h4>MMWGH LINKOD</h4>
        <p>A Localized Network and Knowledge-base for Online Service Delivery.</p>
        <p class="tagline">Dedicated to your health, driven by excellence.</p>
      </div>
      
      <div class="footer-col">
        <h4>Quick Links</h4>
        <ul class="footer-links">
          <li><a href="#about">About Us</a></li>
          <li><a href="#services">Services</a></li>
          <li><a href="#location">Location</a></li>
          <li><a href="/pages/schedules.php">Schedules</a></li>
          <li><a href="/pages/department-select.php" class="highlight-link">Book Appointment</a></li>
        </ul>
      </div>
      
      <div class="footer-col">
        <h4>Contact Information</h4>
        <div class="contact-info">
          <p>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" /></svg>
            Mariveles, Bataan<br>Central Luzon, Philippines
          </p>
          <p>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" /></svg>
            info@mmwgh.gov.ph
          </p>
        </div>
      </div>
    </div>
    
    <div class="footer-bottom">
      <p>&copy; {new Date().getFullYear()} Service Delivery Network (LINKod). All Rights Reserved.</p>
    </div>
  </div>
</footer>

<style>
  .premium-footer {
    background-color: #0f291e; /* Extremely dark green */
    color: #e2e8f0;
    padding: 5rem 2rem 2rem 2rem;
    font-family: 'Inter', sans-serif;
  }

  .container {
    max-width: 1200px;
    margin: 0 auto;
  }

  .footer-grid {
    display: grid;
    grid-template-columns: 2fr 1fr 1.5fr;
    gap: 4rem;
    margin-bottom: 4rem;
  }

  @media (max-width: 900px) {
    .footer-grid {
      grid-template-columns: 1fr;
      gap: 3rem;
    }
  }

  h4 {
    font-family: 'Outfit', sans-serif;
    font-size: 1.3rem;
    color: #ffffff;
    margin-bottom: 1.5rem;
    font-weight: 600;
    letter-spacing: 0.05em;
  }

  .brand-col p {
    color: #cbd5e1;
    line-height: 1.6;
    margin-bottom: 1rem;
    font-size: 1.05rem;
  }

  .brand-col .tagline {
    color: #10b981;
    font-weight: 500;
    font-style: italic;
  }

  .footer-links {
    list-style: none;
    padding: 0;
    margin: 0;
  }

  .footer-links li {
    margin-bottom: 0.8rem;
  }

  .footer-links a {
    color: #cbd5e1;
    text-decoration: none;
    transition: all 0.2s ease;
    display: inline-block;
  }

  .footer-links a:hover {
    color: #10b981;
    transform: translateX(5px);
  }

  .highlight-link {
    color: #34d399 !important;
    font-weight: 500;
  }

  .contact-info p {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1.2rem;
    color: #cbd5e1;
    line-height: 1.5;
  }

  .contact-info svg {
    width: 20px;
    height: 20px;
    color: #10b981;
    flex-shrink: 0;
    margin-top: 2px;
  }

  .footer-bottom {
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    padding-top: 2rem;
    text-align: center;
  }

  .footer-bottom p {
    color: #64748b;
    font-size: 0.9rem;
    margin: 0;
  }
</style>
