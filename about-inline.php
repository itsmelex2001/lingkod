<!-- Inline About Section -->
<div id="about-new" style="display: block; width: 100%; scroll-margin-top: 76px;">
<section class="about-section-inline">
  <div class="container-inline-about">
    <h2 class="about-heading-inline">
      <img src="/sdn/public/assets/about_us_brush.png?v=<?= time() ?>" alt="About Us" class="about-brush-inline max-md:h-[38px] max-md:max-w-[168px] max-md:object-contain" />
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
<section class="vision-mission-section max-md:grid max-md:grid-cols-2 max-md:items-start max-md:gap-3">
  <div class="vm-container max-md:contents">
    <article class="vm-card max-md:min-w-0" data-vm-shade data-aos="fade-up">
      <h3 class="vm-title">
        <button type="button" class="vm-shade-head" aria-expanded="false" aria-controls="vm-panel-vision">
          <img src="/sdn/public/assets/vision_brush.png?v=<?= time() ?>" alt="Vision" class="vm-brush-inline max-md:h-auto max-md:max-h-[26px] max-md:w-auto max-md:max-w-full max-md:object-contain" />
        </button>
      </h3>
      <div class="vm-shade-body" id="vm-panel-vision" aria-hidden="true">
        <div class="vm-shade-inner">
          <div class="vm-shade-content">
            <p class="vm-text max-md:text-left max-md:text-xs max-md:leading-snug max-md:break-words" data-i18n="vision_body">Mariveles Mental Wellness and General Hospital is the premier client-oriented DOH hospital, providing safe. efficient, and quality services.</p>
          </div>
        </div>
      </div>
      <div class="vm-shade-rail" aria-hidden="true">
        <span class="vm-line"></span>
        <svg class="vm-shade-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
      </div>
    </article>

    <article class="vm-card max-md:min-w-0" data-vm-shade data-aos="fade-up" data-aos-delay="100">
      <h3 class="vm-title">
        <button type="button" class="vm-shade-head" aria-expanded="false" aria-controls="vm-panel-mission">
          <img src="/sdn/public/assets/mission_brush.png?v=<?= time() ?>" alt="Mission" class="vm-brush-inline max-md:h-auto max-md:max-h-[26px] max-md:w-auto max-md:max-w-full max-md:object-contain" />
        </button>
      </h3>
      <div class="vm-shade-body" id="vm-panel-mission" aria-hidden="true">
        <div class="vm-shade-inner">
          <div class="vm-shade-content">
            <p class="vm-text max-md:text-left max-md:text-xs max-md:leading-snug max-md:break-words" data-i18n="mission_body">We are committed to delivering compassionate, safe, and high-quality healthcare in every encounter by preserving dignity, strengthening hope, and promoting holistic well-being for a healthier and more resilient community.</p>
          </div>
        </div>
      </div>
      <div class="vm-shade-rail" aria-hidden="true">
        <span class="vm-line"></span>
        <svg class="vm-shade-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
      </div>
    </article>
  </div>

  <div class="qp-container max-md:contents">
    <article class="vm-card qp-card max-md:col-span-2 max-md:min-w-0" data-vm-shade data-aos="fade-up" data-aos-delay="150">
      <h3 class="vm-title">
        <button type="button" class="vm-shade-head" aria-expanded="false" aria-controls="vm-panel-quality">
          <img src="/sdn/public/assets/quality_policy_brush.png?v=<?= time() ?>" alt="Quality Policy" class="vm-brush-inline max-md:h-auto max-md:max-h-[30px] max-md:w-auto max-md:max-w-full max-md:object-contain" />
        </button>
      </h3>
      <div class="vm-shade-body" id="vm-panel-quality" aria-hidden="true">
        <div class="vm-shade-inner">
          <div class="vm-shade-content">
            <div class="qp-text-group">
              <p class="vm-text max-md:text-left max-md:text-[13px] max-md:leading-snug max-md:break-words" data-i18n="quality_1">The Mariveles Mental Wellness and General Hospital is committed to provide affordable quality mental and medical health care with Fairness, Accountability, and Continuous improvement. We shall ensure compliance with statutory and regulatory requirements. We pledge to continually improve our Quality Management System to exceed our client's satisfaction.</p>
              <p class="vm-text max-md:text-left max-md:text-[13px] max-md:leading-snug max-md:break-words" data-i18n="quality_2">We shall ensure compliance with statutory and regulatory requirements. We pledge to continually improve our Quality Management System to exceed our client's satisfaction.</p>
              <p class="vm-text max-md:text-left max-md:text-[13px] max-md:leading-snug max-md:break-words" data-i18n="quality_3">We pledge to continually improve our Quality Management System to exceed our client's satisfaction.</p>
            </div>
          </div>
        </div>
      </div>
      <div class="vm-shade-rail" aria-hidden="true">
        <span class="vm-line"></span>
        <svg class="vm-shade-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
      </div>
    </article>
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

  /* Vision, Mission & Quality Policy Styles (Roller Shade Disclosure) */
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
    align-items: flex-start;
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
    padding: 0;
    flex: 1;
    max-width: 440px;
    border: 1px solid rgba(20, 83, 14, 0.08);
    box-shadow: 0 12px 35px rgba(0, 0, 0, 0.09);
    display: flex;
    flex-direction: column;
    align-items: stretch;
    position: relative;
    overflow: hidden;
    transition: box-shadow 0.35s ease, border-color 0.35s ease;
  }

  .vm-card:hover {
    border-color: rgba(20, 83, 14, 0.18);
    box-shadow: 0 16px 38px rgba(20, 83, 14, 0.12);
  }

  .vm-card.is-open {
    border-color: rgba(20, 83, 14, 0.22);
    box-shadow: 0 20px 44px rgba(20, 83, 14, 0.14);
  }

  .vm-card.qp-card {
    max-width: 820px;
    width: 100%;
    padding: 0;
  }

  .vm-title {
    margin: 0 !important;
    width: 100%;
    display: block;
  }

  .vm-shade-head {
    appearance: none;
    border: 0;
    background: #ffffff;
    width: 100%;
    padding: 2rem 2.25rem 1.65rem;
    display: flex;
    justify-content: center;
    align-items: center;
    cursor: pointer;
    touch-action: manipulation;
    position: relative;
    z-index: 2;
    transition: background-color 0.2s ease, box-shadow 0.35s ease;
  }

  .vm-card.qp-card .vm-shade-head {
    padding: 2.15rem 3rem 1.75rem;
  }

  .vm-shade-head:hover {
    background-color: #f7fcf8;
  }

  .vm-shade-head:focus-visible {
    outline: 3px solid #15803d;
    outline-offset: -3px;
    border-radius: 22px 22px 0 0;
  }

  .vm-card.is-open .vm-shade-head {
    box-shadow: 0 5px 12px -8px rgba(20, 83, 14, 0.28);
  }

  .vm-brush-inline {
    height: clamp(70px, 7.2vw, 86px);
    width: auto;
    object-fit: contain;
    display: inline-block;
    user-select: none;
    pointer-events: none;
  }

  .vm-shade-body {
    width: 100%;
    display: grid;
    grid-template-rows: 0fr;
    transition: grid-template-rows 0.34s cubic-bezier(0.4, 0, 0.2, 1);
  }

  .vm-card.is-open .vm-shade-body {
    grid-template-rows: 1fr;
    transition: grid-template-rows 0.52s cubic-bezier(0.22, 1, 0.36, 1);
  }

  .vm-shade-inner {
    overflow: hidden;
    min-height: 0;
    visibility: hidden;
    background:
      linear-gradient(180deg, rgba(20, 83, 14, 0.06) 0%, rgba(255, 255, 255, 0) 20px),
      repeating-linear-gradient(
        180deg,
        rgba(20, 83, 14, 0.028) 0px,
        rgba(20, 83, 14, 0.028) 1px,
        transparent 1px,
        transparent 14px
      ),
      #fcfffd;
    transition: visibility 0s linear 0.34s;
  }

  .vm-card.is-open .vm-shade-inner {
    visibility: visible;
    transition: visibility 0s linear 0s;
  }

  .vm-shade-content {
    padding: 1.5rem 2.5rem 1.85rem;
    opacity: 0;
    transform: translateY(-16px);
    transition: opacity 0.2s ease, transform 0.32s cubic-bezier(0.4, 0, 0.2, 1);
  }

  .vm-card.qp-card .vm-shade-content {
    padding: 1.6rem 3.5rem 2rem;
  }

  .vm-card.is-open .vm-shade-content {
    opacity: 1;
    transform: translateY(0);
    transition: opacity 0.36s ease 0.08s, transform 0.52s cubic-bezier(0.22, 1, 0.36, 1);
  }

  .vm-text {
    font-family: 'Inter', sans-serif;
    font-size: 0.875rem;
    line-height: 1.65;
    color: #334155;
    font-style: italic;
    font-weight: 400;
    margin: 0;
    text-align: left;
    width: 100%;
  }

  .qp-text-group {
    width: 100%;
    margin: 0;
  }

  .qp-text-group .vm-text {
    margin-bottom: 1rem;
  }

  .qp-text-group .vm-text:last-child {
    margin-bottom: 0;
  }

  .vm-shade-rail {
    width: 100%;
    min-height: 44px;
    padding: 0.5rem 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.25rem;
    cursor: pointer;
    touch-action: manipulation;
    user-select: none;
    background: linear-gradient(180deg, #f4fbf5 0%, #e8f5ea 100%);
    border-top: 1px solid rgba(20, 83, 14, 0.1);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.9);
    transition: background 0.2s ease;
  }

  .vm-shade-rail:hover {
    background: linear-gradient(180deg, #ecf8ee 0%, #ddf0e0 100%);
  }

  .vm-line {
    width: 84px;
    height: 4px;
    background-color: #14530e;
    border-radius: 999px;
    transition: width 0.25s cubic-bezier(0.22, 1, 0.36, 1), transform 0.2s ease;
  }

  .vm-card:hover .vm-line {
    width: 108px;
  }

  .vm-shade-rail:active .vm-line {
    transform: scaleX(0.92);
  }

  .vm-shade-chevron {
    width: 14px;
    height: 14px;
    color: #14530e;
    opacity: 0.72;
    transition: transform 0.42s cubic-bezier(0.22, 1, 0.36, 1), opacity 0.2s ease;
  }

  .vm-card:not(.is-open):hover .vm-shade-chevron {
    transform: translateY(2px);
    opacity: 1;
  }

  .vm-card.is-open .vm-shade-chevron {
    transform: rotate(180deg);
    opacity: 0.85;
  }

  .vm-card.is-open:hover .vm-shade-chevron {
    transform: rotate(180deg) translateY(2px);
    opacity: 1;
  }

  @media (prefers-reduced-motion: reduce) {
    .vm-card,
    .vm-shade-head,
    .vm-shade-body,
    .vm-shade-inner,
    .vm-shade-content,
    .vm-line,
    .vm-shade-chevron {
      transition: none !important;
    }
  }

  @media (max-width: 768px) {
    .about-heading-inline { margin-bottom: 0.35rem !important; }
    .about-brush-inline { height: 38px; max-width: 168px; }
    .carousel-item-inline { width: 104px; height: 104px; }
    .about-description-inline p { font-size: 0.8125rem; line-height: 1.45; padding: 0; }
    
    .vision-mission-section {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      align-items: start;
      gap: 0.75rem;
    }
    .vm-container,
    .qp-container {
      display: contents;
    }
    .vm-card.qp-card {
      grid-column: span 2;
    }
    .vm-card,
    .vm-card.qp-card {
      width: auto;
      max-width: none;
      min-width: 0;
      padding: 0;
    }
    .vm-shade-head,
    .vm-card.qp-card .vm-shade-head {
      padding: 0.85rem 0.6rem 0.7rem;
    }
    .vm-shade-content,
    .vm-card.qp-card .vm-shade-content {
      padding: 0.75rem 0.65rem 0.85rem;
    }
    .vm-text,
    .qp-text-group .vm-text {
      overflow-wrap: break-word;
      word-break: break-word;
      text-align: left;
    }
  }
</style>

<script>
  (function () {
    document.querySelectorAll('[data-vm-shade]').forEach(function (card) {
      var head = card.querySelector('.vm-shade-head');
      var body = card.querySelector('.vm-shade-body');
      var rail = card.querySelector('.vm-shade-rail');
      if (!head || !body) return;

      function toggleShade() {
        var open = card.classList.toggle('is-open');
        head.setAttribute('aria-expanded', open ? 'true' : 'false');
        body.setAttribute('aria-hidden', open ? 'false' : 'true');
      }

      head.addEventListener('click', toggleShade);
      if (rail) rail.addEventListener('click', toggleShade);
    });
  })();
</script>
