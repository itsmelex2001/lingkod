<script>
  import { slide, fade, fly } from 'svelte/transition';
  import { onMount, onDestroy } from 'svelte';
  
  const departments = [
    {
      id: 'general',
      name: 'General<br>Management',
      subname: 'General Management unit',
      image: '/sdn/public/assets/6214996979771708504.jpg',
      link: '/sdn/pages/regis-general.php',
      services: [
        'Routine Check-ups & Preventative Care',
        'Internal Medicine',
        'Family Medicine & General Practice',
        'Chronic Disease Management',
        'Pediatric Care',
        'General Surgery & Minor Procedures'
      ]
    },
    {
      id: 'neurology',
      name: 'Neurology',
      subname: 'Neurology unit',
      image: '/sdn/public/assets/6214996979771708505.jpg',
      link: '/sdn/pages/regis-neurology.php',
      services: ['Headache & Migraine Management', 'Stroke Rehabilitation', 'Epilepsy & Seizure Disorders', 'Movement Disorders']
    },
    {
      id: 'psychiatry',
      name: 'OPU<br>Psychiatry',
      subname: 'Outpatient Psychiatry unit',
      image: '/sdn/public/assets/6214996979771708507.jpg',
      link: '/sdn/pages/regis-psychiatry.php',
      services: ['Adult Psychiatry', 'Child & Adolescent Psychiatry', 'Substance Abuse Treatment', 'Anxiety & Depression Management']
    }
  ];

  let selectedDept = null;
  let sectionVisible = false;
  let sectionRef;

  const bgClips = [
    '/sdn/public/assets/generalmanagement_clip.mp4',
    '/sdn/public/assets/neurology_clip.mp4',
    '/sdn/public/assets/psychiatry_clip.mp4'
  ];
  let activeClipIndex = 0;
  let clipInterval;

  onMount(() => {
    clipInterval = setInterval(() => {
      activeClipIndex = (activeClipIndex + 1) % bgClips.length;
    }, 5000);
  });

  onDestroy(() => {
    if (clipInterval) clearInterval(clipInterval);
  });
</script>

<!-- Intro Section (White background) -->
<section class="intro-section" data-aos="fade-up">
  <div class="container">
    <h1 class="hospital-heading">MARIVELES MENTAL WELLNESS AND GENERAL HOSPITAL</h1>
    <p class="hospital-sub">Dedicated to your health, driven by excellence. Your seamless journey to professional care starts here.</p>
    <p class="text-make-appt">MAKE AN APPOINTMENT</p>
  </div>
</section>

<!-- Services Section (Green chevron shape) -->
<section id="services" class="services-section">
  <div class="container">
    <div class="header-content" data-aos="fade-up">
      <h2 class="script-accent">Our Specialties</h2>
      <h3 class="main-heading">MEDICAL SERVICES</h3>
      <p class="subtitle">Comprehensive care across mental and physical wellness.</p>
    </div>

    {#if !selectedDept}
      <div class="tiles-grid" in:fade>
        {#each departments as dept, i}
          <div 
            class="dept-tile" 
            data-aos="fade-up"
            data-aos-delay={i * 100}
            style="background-image: url('{dept.image}');"
            on:click={() => selectedDept = dept}
          >
            <div class="tile-overlay"></div>
            <div class="tile-content">
              <h4 class="tile-title">{@html dept.name}</h4>
              {#if dept.subname}
                <p class="tile-subtitle">{dept.subname}</p>
              {/if}
            </div>
          </div>
        {/each}
      </div>
    {:else}
      <div class="services-detail" in:fly={{ y: 30, duration: 600, opacity: 0 }} out:fade>
        <button class="back-btn" on:click={() => selectedDept = null}>
          &larr; Back
        </button>
        
        <div class="detail-header">
          <div class="header-image" style="background-image: url('{selectedDept.image}')"></div>
          <h4>{@html selectedDept.name.replace('<br>', ' ')} Department</h4>
        </div>
        
        <div class="services-list">
          {#each selectedDept.services as service, idx}
            <div class="service-item" in:fly={{ y: 20, duration: 400, delay: 150 + (idx * 100), opacity: 0 }}>
              <span class="service-name">{service}</span>
              <button class="book-btn" on:click={() => window.location.href=`${selectedDept.link}?service=${encodeURIComponent(service)}&spec=${encodeURIComponent(service)}&source=dept`}>Book this Service</button>
            </div>
          {/each}
        </div>
      </div>
    {/if}
  </div>
</section>

<style>
  .intro-section {
    padding: 6rem 2rem 3rem;
    text-align: center;
    background-color: #ffffff;
  }

  .services-section {
    position: relative;
    padding: 6rem 2rem 8rem;
    background: linear-gradient(to bottom, #f0fdf4 0%, #dcfce7 100%);
    clip-path: polygon(0 40px, 50% 0, 100% 40px, 100% calc(100% - 40px), 50% 100%, 0 calc(100% - 40px));
    margin-top: -20px;
    z-index: 2;
  }
  .container {
    position: relative;
    max-width: 1200px;
    margin: 0 auto;
    z-index: 1;
  }
  .header-content {
    text-align: center;
    margin-bottom: 4rem;
  }
  .hospital-heading {
    font-family: Impact, Haettenschweiler, 'Arial Narrow Bold', sans-serif;
    font-size: clamp(1.5rem, 3.5vw, 2.8rem);
    color: var(--deep-green);
    letter-spacing: 1px;
    margin-bottom: 0.5rem;
    white-space: nowrap;
  }
  @media (max-width: 960px) {
    .hospital-heading {
      white-space: normal;
    }
  }
  .hospital-sub {
    font-family: 'Inter', sans-serif;
    font-size: 1.25rem;
    font-weight: 500;
    font-style: italic;
    color: #64748b;
    margin-bottom: 2rem;
  }
  .text-make-appt {
    color: #166534;
    font-family: 'Inter', sans-serif;
    font-weight: 500;
    font-size: 1.25rem;
    text-transform: uppercase;
    margin-top: 3.5rem;
    letter-spacing: 1px;
  }
  .script-accent {
    font-family: 'Kaushan Script', cursive;
    color: var(--primary-green);
    font-size: 2.2rem;
    margin-bottom: 1.5rem;
  }
  .main-heading {
    font-size: 2.5rem;
    color: var(--deep-green);
    font-weight: 700;
  }
  .subtitle {
    color: var(--muted);
    font-size: 1.1rem;
    margin-top: 0.5rem;
  }
  
  .tiles-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 2rem;
  }
  .dept-tile {
    position: relative;
    border-radius: var(--radius-xl);
    padding: 0;
    text-align: center;
    cursor: pointer;
    box-shadow: var(--shadow-sm);
    transition: transform 0.3s ease-out, box-shadow 0.3s ease-out;
    background-size: cover;
    background-position: center;
    height: 380px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
  }
  .dept-tile:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.5);
  }
  .tile-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(
      to bottom, 
      rgba(40, 60, 75, 0.4) 0%, 
      rgba(20, 40, 55, 0.85) 100%
    );
    z-index: 1;
    transition: background 0.3s ease-out;
  }
  .dept-tile:hover .tile-overlay {
    background: linear-gradient(
      to bottom, 
      rgba(10, 30, 45, 0.7) 0%, 
      rgba(5, 15, 25, 0.98) 100%
    );
  }
  .tile-content {
    position: relative;
    z-index: 2;
    padding: 2rem 1.5rem;
  }
  .tile-title {
    font-family: Impact, Haettenschweiler, 'Arial Narrow Bold', sans-serif;
    font-size: 2.2rem;
    color: #ffffff;
    text-shadow: 2px 2px 5px rgba(0,0,0,0.8);
    line-height: 1.1;
    margin: 0;
    letter-spacing: 1px;
  }
  .tile-subtitle {
    color: #4CAF50;
    font-size: 0.75rem;
    margin-top: 0.3rem;
    font-style: italic;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.9);
  }

  .services-detail {
    background: rgba(255, 255, 255, 0.95);
    border-radius: var(--radius-xl);
    padding: 3rem;
    box-shadow: var(--shadow-md);
    max-width: 800px;
    margin: 0 auto;
  }
  .detail-header {
    text-align: center;
    margin-bottom: 3rem;
  }
  .header-image {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background-size: cover;
    background-position: center;
    margin: 0 auto 1rem;
    border: 4px solid var(--primary-green);
    box-shadow: 0 8px 15px rgba(0,0,0,0.2);
  }
  .back-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: #f1f5f9;
    border: none;
    color: #475569;
    cursor: pointer;
    font-weight: 600;
    font-size: 0.95rem;
    padding: 0.5rem 1rem;
    border-radius: 30px;
    margin-bottom: 2rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }
  .back-btn:hover { 
    background: #e2e8f0;
    color: var(--primary-green); 
    transform: translateX(-5px);
  }
  
  .detail-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 2rem;
    border-bottom: 2px solid var(--border-light);
    padding-bottom: 1.5rem;
  }
  .detail-header h4 { font-size: 2rem; color: var(--deep-green); margin: 0; }

  .services-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }
  .service-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    background: #ffffff;
    border-radius: var(--radius-md);
    border: 1px solid #e2e8f0;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 5px rgba(0,0,0,0.02);
  }
  .service-item:hover {
    transform: translateX(8px);
    border-color: var(--primary-green);
    box-shadow: 0 8px 15px rgba(0,0,0,0.08);
    background: #f8fafc;
  }
  .service-name {
    font-weight: 500;
    font-size: 1.1rem;
    color: var(--text);
  }
  .book-btn {
    background: var(--primary-green);
    color: white;
    border: none;
    padding: 0.6rem 1.2rem;
    border-radius: var(--radius-sm);
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s, transform 0.2s;
  }
  .book-btn:hover {
    background: var(--dark-green);
    transform: translateY(-2px);
  }
</style>
