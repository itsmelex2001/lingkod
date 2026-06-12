<section class="location-section" id="location">
  <div class="container">
    <div class="section-header">
      <h2>Find Us</h2>
      <p>Conveniently located to serve the Northern and Central Luzon communities.</p>
    </div>
    
    <div class="map-container">
      <iframe
        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3853.5188849495763!2d120.48512411536767!3d15.019483389536852!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3396f7c461327863%3A0xc66579b29db3e067!2sCentral%20Luzon!5e0!3m2!1sen!2sph!4v1683904561234!5m2!1sen!2sph"
        allowfullscreen=""
        loading="lazy"
        referrerpolicy="no-referrer-when-downgrade"
        title="Hospital Location Map"
      >
      </iframe>
    </div>
  </div>
</section>

<style>
  .location-section {
    padding: 4rem 2rem;
    background-color: #f8faf9;
  }

  .container {
    max-width: 1200px;
    margin: 0 auto;
  }

  .section-header {
    text-align: center;
    margin-bottom: 3rem;
  }

  .section-header h2 {
    font-family: 'Outfit', sans-serif;
    font-size: 2.2rem;
    color: #1b5e20;
    margin-bottom: 1rem;
    font-weight: 700;
  }

  .section-header p {
    font-family: 'Inter', sans-serif;
    color: #64748b;
    font-size: 1.1rem;
  }

  .map-container {
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 20px 40px rgba(0,0,0,0.08);
    height: 400px;
    background: #eee;
    border: 4px solid white;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
  }

  .map-container:hover {
    transform: translateY(-5px);
    box-shadow: 0 25px 50px rgba(27, 94, 32, 0.15);
  }

  .map-container iframe {
    width: 100%;
    height: 100%;
    border: none;
  }
</style>
