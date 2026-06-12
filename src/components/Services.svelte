<script>
  let expandedCard = null;

  const services = [
    {
      id: 'general',
      title: 'General Management',
      shortDesc: 'Focus on the diagnosis, treatment, and prevention of common illnesses.',
      longDesc: 'Our general medicine department provides comprehensive care for a wide range of adult illnesses. From routine check-ups to complex diagnostics, our expert physicians ensure your overall health and well-being.',
      link: '/sdn/pages/regis-general.php?source=home',
      icon: '<path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />'
    },
    {
      id: 'neurology',
      title: 'Neurology',
      shortDesc: 'Focuses on disorders affecting the brain, spinal cord, nerves, and muscles.',
      longDesc: 'Advanced diagnostic and therapeutic services for neurological conditions. We specialize in managing headaches, epilepsy, stroke recovery, and neuromuscular disorders with state-of-the-art facilities.',
      link: '/sdn/pages/regis-neurology.php?source=home',
      icon: '<path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.516 0c.85.493 1.509 1.333 1.509 2.316V18" />'
    },
    {
      id: 'psychiatry',
      title: 'OPU Psychiatry',
      shortDesc: 'Focus on mental health, emotional well-being, and psychiatric conditions.',
      longDesc: 'Compassionate mental health care without requiring hospital admission. We offer counseling, psychiatric evaluations, and personalized treatment plans for anxiety, depression, and other emotional conditions.',
      link: '/sdn/pages/regis-psychiatry.php?source=home',
      icon: '<path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.601a8.983 8.983 0 0 1 3.361-6.867 8.21 8.21 0 0 0 3 2.48Z" />'
    }
  ];

  function toggleCard(id) {
    expandedCard = expandedCard === id ? null : id;
  }
</script>

<section class="services-section" id="services">
  <div class="container">
    <div class="section-header">
      <h2>Hospital Services</h2>
      <p>Explore our specialized departments. Click on any service to learn more.</p>
    </div>

    <div class="services-grid">
      {#each services as service}
        <button 
          class="service-card {expandedCard === service.id ? 'expanded' : ''}" 
          on:click={() => toggleCard(service.id)}
          type="button"
          aria-expanded={expandedCard === service.id}
        >
          <div class="card-inner">
            <!-- FRONT OF CARD -->
            <div class="card-front">
              <div class="icon-wrapper">
                <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                  {@html service.icon}
                </svg>
              </div>
              <h3>{service.title}</h3>
              <p>{service.shortDesc}</p>
              <span class="indicator">Click to expand</span>
            </div>

            <!-- BACK OF CARD -->
            <div class="card-back">
              <h3>{service.title}</h3>
              <p>{service.longDesc}</p>
              <a href={service.link} class="btn-book" on:click|stopPropagation>Book Appointment</a>
            </div>
          </div>
        </button>
      {/each}
    </div>
  </div>
</section>

<style>
  .services-section {
    padding: 4rem 2rem;
    background-color: #f8faf9; /* Very soft mint off-white */
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

  .services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 2rem;
    perspective: 1000px;
  }

  .service-card {
    background: transparent;
    border: none;
    padding: 0;
    height: 380px;
    cursor: pointer;
    border-radius: 24px;
    outline: none;
  }

  .card-inner {
    position: relative;
    width: 100%;
    height: 100%;
    text-align: center;
    transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    transform-style: preserve-3d;
  }

  .service-card.expanded .card-inner {
    transform: rotateY(180deg);
  }

  .card-front, .card-back {
    position: absolute;
    width: 100%;
    height: 100%;
    backface-visibility: hidden;
    -webkit-backface-visibility: hidden;
    border-radius: 24px;
    padding: 2rem 1.5rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
  }

  .card-front {
    background: #ffffff;
    border: 1px solid #f1f5f9;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
    transition: box-shadow 0.3s ease, transform 0.3s ease;
  }

  .service-card:hover:not(.expanded) .card-front {
    box-shadow: 0 20px 40px rgba(27, 94, 32, 0.08);
    transform: translateY(-5px);
  }

  .card-back {
    background: #1b5e20;
    color: white;
    transform: rotateY(180deg);
    box-shadow: 0 20px 40px rgba(27, 94, 32, 0.2);
  }

  .icon-wrapper {
    width: 60px;
    height: 60px;
    background: #ecfdf5; /* Emerald 50 */
    color: #10b981; /* Emerald 500 */
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1.5rem;
    transform: rotate(45deg);
    transition: transform 0.3s ease;
  }

  .service-card:hover .icon-wrapper {
    transform: rotate(0deg);
  }

  .icon-wrapper svg {
    width: 30px;
    height: 30px;
    transform: rotate(-45deg);
    transition: transform 0.3s ease;
  }

  .service-card:hover .icon-wrapper svg {
    transform: rotate(0deg);
  }

  .card-front h3 {
    font-family: 'Outfit', sans-serif;
    font-size: 1.3rem;
    color: #0f172a;
    margin-bottom: 0.8rem;
    font-weight: 700;
  }

  .card-front p {
    font-family: 'Inter', sans-serif;
    color: #64748b;
    line-height: 1.6;
    margin-bottom: 2rem;
  }

  .indicator {
    margin-top: auto;
    font-family: 'Inter', sans-serif;
    font-size: 0.85rem;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    font-weight: 600;
  }

  .card-back h3 {
    font-family: 'Outfit', sans-serif;
    font-size: 1.5rem;
    margin-bottom: 1.2rem;
    color: #ffffff;
    font-weight: 700;
  }

  .card-back p {
    font-family: 'Inter', sans-serif;
    color: #d1fae5; /* Emerald 100 */
    line-height: 1.6;
    margin-bottom: 2rem;
    font-size: 0.95rem;
  }

  .btn-book {
    background: white;
    color: #1b5e20;
    padding: 1rem 2rem;
    border-radius: 999px;
    font-family: 'Inter', sans-serif;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
  }

  .btn-book:hover {
    background: #10b981;
    color: white;
    transform: translateY(-2px);
  }
</style>
