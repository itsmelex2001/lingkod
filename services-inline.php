<!-- Inline Services Section -->
<section id="services-new" style="display: block;">
  <!-- Intro Section (White background) -->
  <section class="intro-section-inline" data-aos="fade-up">
    <div class="container-inline-services">
      <h1 class="hospital-heading-inline">MARIVELES MENTAL WELLNESS AND GENERAL HOSPITAL</h1>
      <p class="hospital-sub-inline">Dedicated to your health, driven by excellence. Your seamless journey to professional care starts here.</p>
      <p class="text-make-appt-inline">MAKE AN APPOINTMENT</p>
    </div>
  </section>

  <!-- Services Section (Green chevron shape) -->
  <section class="services-section-inline">
    <div class="container-inline-services">
      <div class="header-content-inline" data-aos="fade-up">
        <h2 class="script-accent-inline">Our Specialties</h2>
        <h3 class="main-heading-inline">MEDICAL SERVICES</h3>
        <p class="subtitle-inline">Comprehensive care across mental and physical wellness.</p>
      </div>

      <!-- Tiles Grid -->
      <div id="inline-tiles-grid" class="tiles-grid-inline">
        <?php
          $departments = [
            [
              'id' => 'general',
              'name' => 'General<br>Management',
              'subname' => 'General Management unit',
              'image' => '/sdn/public/assets/generalmngmnt_img(orig).jpeg',
              'link' => '/sdn/pages/regis-general.php',
              'services' => [
                'Routine Check-ups & Preventative Care',
                'Internal Medicine',
                'Family Medicine & General Practice',
                'Chronic Disease Management',
                'Pediatric Care',
                'General Surgery & Minor Procedures'
              ]
            ],
            [
              'id' => 'neurology',
              'name' => 'Neurology',
              'subname' => 'Neurology unit',
              'image' => '/sdn/public/assets/neurology_img(orig).jpeg',
              'link' => '/sdn/pages/regis-neurology.php',
              'services' => ['Headache & Migraine Management', 'Stroke Rehabilitation', 'Epilepsy & Seizure Disorders', 'Movement Disorders']
            ],
            [
              'id' => 'psychiatry',
              'name' => 'OPU<br>Psychiatry',
              'subname' => 'Outpatient Psychiatry unit',
              'image' => '/sdn/public/assets/psychiatry_img(orig).jpeg',
              'link' => '/sdn/pages/regis-psychiatry.php',
              'services' => ['Adult Psychiatry', 'Child & Adolescent Psychiatry', 'Substance Abuse Treatment', 'Anxiety & Depression Management']
            ]
          ];
        ?>
        <?php foreach($departments as $i => $dept): ?>
          <div 
            class="dept-tile-inline" 
            data-aos="fade-up"
            data-aos-delay="<?= $i * 100 ?>"
            style="background-image: url('<?= $dept['image'] ?>');"
          >
            <div class="tile-overlay-inline"></div>
            <div class="tile-content-inline">
              <h4 class="tile-title-inline"><?= $dept['name'] ?></h4>
              <p class="tile-subtitle-inline"><?= $dept['subname'] ?></p>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Detail View (Hidden by default) -->
      <div id="inline-services-detail" class="services-detail-inline" style="display: none;">
        <button class="back-btn-inline" onclick="hideDepartmentDetail()">
          &larr; Back
        </button>
        
        <div class="detail-header-inline">
          <div id="detail-header-image" class="header-image-inline"></div>
          <h4 id="detail-header-title"></h4>
        </div>
        
        <div id="detail-services-list" class="services-list-inline">
          <!-- Populated by JS -->
        </div>
      </div>
    </div>
  </section>
</section>

<style>
  .intro-section-inline {
    padding: 6rem 2rem 3rem;
    text-align: center;
    background-color: #ffffff;
  }
  .services-section-inline {
    position: relative;
    padding: 6rem 2rem 8rem;
    background: linear-gradient(to bottom, #f0fdf4 0%, #dcfce7 100%);
    clip-path: polygon(0 40px, 50% 0, 100% 40px, 100% calc(100% - 40px), 50% 100%, 0 calc(100% - 40px));
    margin-top: -20px;
    z-index: 2;
  }
  .container-inline-services {
    position: relative;
    max-width: 1200px;
    margin: 0 auto;
    z-index: 1;
  }
  .header-content-inline { text-align: center; margin-bottom: 4rem; }
  
  .hospital-heading-inline {
    font-family: Impact, Haettenschweiler, 'Arial Narrow Bold', sans-serif;
    font-size: clamp(1.5rem, 3.5vw, 2.8rem);
    color: var(--deep-green);
    letter-spacing: 1px;
    margin-bottom: 0.5rem;
    white-space: nowrap;
  }
  .hospital-sub-inline {
    font-family: 'Inter', sans-serif;
    font-size: 1.25rem;
    font-weight: 500;
    font-style: italic;
    color: #64748b;
    margin-bottom: 2rem;
  }
  .text-make-appt-inline {
    color: #166534;
    font-family: 'Inter', sans-serif;
    font-weight: 500;
    font-size: 1.25rem;
    text-transform: uppercase;
    margin-top: 3.5rem;
    letter-spacing: 1px;
  }
  
  .script-accent-inline {
    font-family: 'Kaushan Script', cursive;
    color: var(--primary-green);
    font-size: 2.2rem;
    margin-bottom: 1.5rem;
  }
  .main-heading-inline {
    font-size: 2.5rem;
    color: var(--deep-green);
    font-weight: 900;
    margin-bottom: 1rem;
    letter-spacing: 1px;
  }
  .subtitle-inline { font-size: 1.1rem; color: #64748b; }
  
  .tiles-grid-inline {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 2rem;
    transition: opacity 0.3s;
  }
  .dept-tile-inline {
    position: relative;
    border-radius: var(--radius-xl);
    padding: 0;
    text-align: center;
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
  
  .dept-tile-inline:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.5);
  }
  
  .tile-overlay-inline {
    position: absolute;
    top: 0; left: 0; width: 100%; height: 100%;
    background: linear-gradient(to bottom, rgba(10, 30, 45, 0.7) 0%, rgba(5, 15, 25, 0.98) 100%);
    z-index: 1;
    transition: opacity 0.4s ease-in-out;
  }
  
  /* The requested hover effect: original color revealed when dragging cursor, dark shade when out */
  .dept-tile-inline:hover .tile-overlay-inline {
    opacity: 0;
  }
  /* Optional: we can fade out the text as well to see the image clearly, but keeping it is usually preferred. */
  
  .tile-content-inline {
    position: relative;
    z-index: 2;
    padding: 2rem 1.5rem;
    transition: transform 0.4s ease-out, opacity 0.4s ease-out;
  }
  /* Hide the text so the image is fully visible when hovered */
  .dept-tile-inline:hover .tile-content-inline {
    opacity: 0;
    transform: translateY(20px);
  }

  .tile-title-inline {
    font-family: Impact, Haettenschweiler, 'Arial Narrow Bold', sans-serif;
    font-size: 2.2rem;
    color: #ffffff;
    text-shadow: 2px 2px 5px rgba(0,0,0,0.8);
    line-height: 1.1;
    margin: 0;
    letter-spacing: 1px;
  }
  .tile-subtitle-inline {
    font-family: 'Inter', sans-serif;
    color: var(--primary-green);
    font-weight: 700;
    font-size: 0.95rem;
    margin-top: 0.75rem;
    text-shadow: 1px 1px 3px rgba(0,0,0,0.8);
  }
  
  /* Details view */
  .services-detail-inline {
    background: white;
    border-radius: var(--radius-lg);
    padding: 3rem;
    box-shadow: var(--shadow-md);
  }
  .back-btn-inline {
    background: none;
    border: none;
    color: var(--primary-green);
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    margin-bottom: 2rem;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    transition: background-color 0.2s, transform 0.2s;
  }
  .back-btn-inline:hover {
    background-color: #f0fdf4;
    transform: translateX(-5px);
  }
  .detail-header-inline {
    display: flex;
    align-items: center;
    gap: 2rem;
    margin-bottom: 3rem;
    border-bottom: 2px solid var(--light-bg);
    padding-bottom: 2rem;
  }
  .header-image-inline {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background-size: cover;
    background-position: center;
    border: 4px solid var(--primary-green);
    box-shadow: var(--shadow-sm);
  }
  .detail-header-inline h4 {
    font-size: 2.2rem;
    color: var(--deep-green);
    font-weight: 800;
    margin: 0;
  }
  .services-list-inline {
    display: grid;
    gap: 1.5rem;
  }
  .service-item-inline {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    background: var(--light-bg);
    border-radius: var(--radius-md);
    transition: transform 0.2s, box-shadow 0.2s;
  }
  .service-item-inline:hover {
    transform: translateX(10px);
    box-shadow: var(--shadow-sm);
    background: white;
  }
  .service-name-inline {
    font-size: 1.15rem;
    color: var(--text-color);
    font-weight: 600;
  }
  .book-btn-inline {
    background: var(--primary-green);
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: var(--radius-sm);
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
  }
  .book-btn-inline:hover {
    background: var(--deep-green);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(22, 163, 74, 0.3);
  }
  @media (max-width: 960px) {
    .hospital-heading-inline { white-space: normal; font-size: 2.2rem; }
    .detail-header-inline { flex-direction: column; text-align: center; }
    .service-item-inline { flex-direction: column; gap: 1rem; text-align: center; }
    .intro-section-inline { padding: 3rem 1.5rem 2rem; }
    .services-section-inline { padding: 4rem 1.5rem 6rem; }
  }
</style>

<script>
  function showDepartmentDetail(dept) {
    document.getElementById('inline-tiles-grid').style.display = 'none';
    const detailView = document.getElementById('inline-services-detail');
    
    document.getElementById('detail-header-image').style.backgroundImage = `url('${dept.image}')`;
    document.getElementById('detail-header-title').innerHTML = dept.name.replace('<br>', ' ') + ' Department';
    
    const list = document.getElementById('detail-services-list');
    list.innerHTML = '';
    
    dept.services.forEach(service => {
      const item = document.createElement('div');
      item.className = 'service-item-inline';
      
      const span = document.createElement('span');
      span.className = 'service-name-inline';
      span.innerText = service;
      
      const btn = document.createElement('button');
      btn.className = 'book-btn-inline';
      btn.innerText = 'Book this Service';
      btn.onclick = () => window.location.href = dept.link + '?service=' + encodeURIComponent(service) + '&spec=' + encodeURIComponent(service) + '&source=dept';
      
      item.appendChild(span);
      item.appendChild(btn);
      list.appendChild(item);
    });
    
    detailView.style.display = 'block';
  }
  
  function hideDepartmentDetail() {
    document.getElementById('inline-services-detail').style.display = 'none';
    document.getElementById('inline-tiles-grid').style.display = 'grid';
  }

</script>
