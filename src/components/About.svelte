<section class="about-section" id="about">
  <div class="container">
    <div class="section-header">
      <h2>About Our Hospital</h2>
      <p>
        The Mariveles Mental Wellness and General Hospital (MMWGH) serves as a
        Department of Health hospital, catering to the mental health needs of
        Northern and Central Luzon.
      </p>
    </div>

    <div class="cards-grid">
      <div class="info-card">
        <div class="card-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
        </div>
        <h3>Vision</h3>
        <p>
          Mariveles Mental Wellness and General Hospital is the premier
          client-oriented DOH hospital, providing safe, efficient, and quality
          services.
        </p>
      </div>
      
      <div class="info-card">
        <div class="card-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" /></svg>
        </div>
        <h3>Mission</h3>
        <p>
          To provide and advocate for quality mental and medical health care
          through promotive, preventive, curative, and rehabilitative services
          with equitable access to ensure the highest client satisfaction.
        </p>
      </div>

      <div class="info-card">
        <div class="card-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" /></svg>
        </div>
        <h3>Core Values</h3>
        <ul>
          <li>Fairness</li>
          <li>Accountability</li>
          <li>Continuous Improvement</li>
        </ul>
      </div>
    </div>
  </div>
</section>

<style>
  .about-section {
    padding: 4rem 2rem;
    position: relative;
    background: url('/sdn/public/assets/hospital_hero_bg.jpg') center/cover fixed;
  }

  .about-section::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(30, 70, 45, 0.85);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    z-index: 1;
  }

  .container {
    max-width: 1200px;
    margin: 0 auto;
    position: relative;
    z-index: 2;
  }

  .section-header {
    text-align: center;
    margin-bottom: 3rem;
  }

  .section-header h2 {
    font-family: 'Outfit', sans-serif;
    font-size: 2.2rem;
    color: #ffffff;
    margin-bottom: 1rem;
    font-weight: 700;
    text-shadow: 0 2px 8px rgba(0,0,0,0.3);
  }

  .section-header p {
    font-family: 'Inter', sans-serif;
    color: #e2e8f0;
    font-size: 1.1rem;
    max-width: 700px;
    margin: 0 auto;
    line-height: 1.6;
    text-shadow: 0 1px 4px rgba(0,0,0,0.3);
  }

  .cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 2rem;
  }

  .info-card {
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.4);
    border-radius: 24px;
    padding: 2rem 1.5rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
  }

  .info-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.25);
    border-color: #10b981;
    background: rgba(255, 255, 255, 0.95);
  }

  .card-icon {
    width: 50px;
    height: 50px;
    background: #f0fdf4;
    color: #1b5e20;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1.2rem;
  }

  .card-icon svg {
    width: 24px;
    height: 24px;
  }

  .info-card h3 {
    font-family: 'Outfit', sans-serif;
    font-size: 1.4rem;
    color: #0f172a;
    margin-bottom: 1rem;
    font-weight: 700;
  }

  .info-card p, .info-card ul {
    font-family: 'Inter', sans-serif;
    color: #475569;
    line-height: 1.6;
    font-size: 0.95rem;
    margin: 0;
  }

  .info-card ul {
    padding-left: 0;
    list-style: none;
    text-align: left;
    display: inline-block;
  }

  .info-card li {
    margin-bottom: 0.8rem;
    position: relative;
    padding-left: 1.2rem;
  }

  .info-card li::before {
    content: "•";
    color: #10b981;
    font-weight: bold;
    font-size: 1.2rem;
    position: absolute;
    left: 0;
    top: -2px;
  }
</style>
