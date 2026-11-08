<!-- Inline Services Section -->
<section id="services-new" style="display: block; scroll-margin-top: 76px;">
  <!-- How to Use LINGKOD Section (Matching Image 2) -->
  <section class="howto-section-inline" data-aos="fade-up">
    <div class="container-inline-services">
      <div class="howto-header-inline">
        <h1 class="howto-heading-inline" data-i18n="howto_title">HOW TO USE LINGKOD?</h1>
        <p class="howto-sub-inline" data-i18n="howto_sub">Step-by-Step Guide on How to Book an Appointment.</p>
      </div>

      <!-- Stage Area below 'HOW TO USE LINGKOD?' (Only this area blurs when a guide is selected) -->
      <div id="howto-stage-area" class="howto-stage-area-inline">
        <div class="howto-grid-inline">
          <?php
            $howtoSteps = [
              ['num' => '1', 'text' => 'Click "Book<br>Appointment."'],
              ['num' => '2', 'text' => 'Select a Service.'],
              ['num' => '3', 'text' => 'Select Your Patient<br>Type.'],
              ['num' => '4', 'text' => 'Proceed to<br>Registration or Login.'],
              ['num' => '5', 'text' => 'Fill Out the Required<br>Form.'],
              ['num' => '6', 'text' => 'Confirm Your<br>Registration.'],
              ['num' => '7', 'text' => 'Proceed to the Next<br>Step.'],
              ['num' => '8', 'text' => 'Create Your<br>Account.'],
            ];
            foreach ($howtoSteps as $idx => $step):
          ?>
            <div 
              class="howto-card-inline" 
              data-aos="fade-up" 
              data-aos-delay="<?= ($idx % 4) * 70 ?>"
              onclick="openHowtoModal(<?= $idx ?>)"
              role="button"
              tabindex="0"
              onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();openHowtoModal(<?= $idx ?>);}"
            >
              <span class="howto-num-inline"><?= $step['num'] ?></span>
              <p class="howto-text-inline" data-i18n-html="howto_<?= $idx ?>"><?= $step['text'] ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </section>

  <!-- Step-by-Step Guide Pop-up UI (Viewport-Fixed below Navbar like mmwgh.gov.ph) -->
  <div id="howto-modal-backdrop" class="howto-modal-backdrop-inline" onclick="handleHowtoBackdropClick(event)" aria-hidden="true">
    <div class="howto-modal-wrapper-inline" onclick="event.stopPropagation()">
      <div class="howto-modal-card-inline">
              <!-- Close Button -->
              <button type="button" class="howto-modal-close-btn" onclick="closeHowtoModal()" aria-label="Close guide">&times;</button>

              <!-- Step Title Row -->
              <div class="howto-modal-title-row">
                <span id="howto-modal-step-num" class="howto-modal-step-badge">STEP 1</span>
                <h3 id="howto-modal-step-title" class="howto-modal-step-heading">Click "Book Appointment."</h3>
              </div>

              <!-- Single-Column Description (Steps 1, 2, 3, 5, 6, 7, 8) -->
              <p id="howto-modal-desc-single" class="howto-modal-desc-single">
                On the homepage, locate and click the Book Appointment button to begin the appointment process.
              </p>

              <!-- Two-Column Description (Step 4) -->
              <div id="howto-modal-desc-dual" class="howto-modal-desc-dual" style="display: none;">
                <p class="howto-modal-desc-col">
                  <strong>First-Time Patient:</strong> Proceed to the registration form and provide the required information.
                </p>
                <p class="howto-modal-desc-col">
                  <strong>Returning Patient:</strong> Log in using your existing account credentials.
                </p>
              </div>

              <!-- Middle Row: Prev Arrow + Visual Box(es) + Next Arrow -->
              <div class="howto-modal-stage-row">
                <button
                  type="button"
                  id="howto-prev-btn"
                  class="howto-nav-arrow-btn howto-nav-prev"
                  onclick="prevHowtoStep()"
                  aria-label="Previous step"
                >
                  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6"></polyline>
                  </svg>
                </button>

                <!-- Single Visual Frame -->
                <div id="howto-visual-single" class="howto-visual-single-wrap">
                  <div class="howto-placeholder-scene">
                    <svg viewBox="0 0 800 280" preserveAspectRatio="none" class="howto-scene-svg" xmlns="http://www.w3.org/2000/svg">
                      <defs>
                        <linearGradient id="skyGradSingle" x1="0" y1="0" x2="0" y2="1">
                          <stop offset="0%" stop-color="#bae6fd"/>
                          <stop offset="65%" stop-color="#e0f2fe"/>
                          <stop offset="100%" stop-color="#f0f9ff"/>
                        </linearGradient>
                      </defs>
                      <rect width="800" height="280" fill="url(#skyGradSingle)"/>
                      <!-- Clouds -->
                      <g fill="#ffffff">
                        <path d="M165 65h52a18 18 0 0 0 0-36 22 22 0 0 0-42-6 16 16 0 0 0-10 42z"/>
                        <path d="M360 76h85a26 26 0 0 0 0-52 34 34 0 0 0-65-10 24 24 0 0 0-20 62z"/>
                        <path d="M645 60h42a14 14 0 0 0 0-28 18 18 0 0 0-34-5 13 13 0 0 0-8 33z"/>
                      </g>
                      <!-- Back Light Green Hills -->
                      <path d="M0 205 Q60 195 120 215 Q260 165 440 210 Q640 230 800 198 L800 280 L0 280 Z" fill="#c7e576"/>
                      <!-- Front Olive Green Hills -->
                      <path d="M0 255 Q45 250 85 198 Q135 142 195 205 Q245 255 315 232 Q385 202 465 235 Q540 260 605 205 Q665 150 735 218 Q770 250 800 252 L800 280 L0 280 Z" fill="#7ca600"/>
                    </svg>
                  </div>
                </div>

                <!-- Dual Visual Frames (For Step 4) -->
                <div id="howto-visual-dual" class="howto-visual-dual-wrap" style="display: none;">
                  <div class="howto-placeholder-scene">
                    <svg viewBox="0 0 400 260" preserveAspectRatio="none" class="howto-scene-svg" xmlns="http://www.w3.org/2000/svg">
                      <rect width="400" height="260" fill="url(#skyGradSingle)"/>
                      <g fill="#ffffff">
                        <path d="M5 75h35a14 14 0 0 0 0-28 18 18 0 0 0-35 0z"/>
                        <path d="M165 82h72a22 22 0 0 0 0-44 28 28 0 0 0-54-8 20 20 0 0 0-18 52z"/>
                      </g>
                      <path d="M0 190 Q130 148 280 205 L400 220 L400 260 L0 260 Z" fill="#c7e576"/>
                      <path d="M0 168 Q35 178 70 212 Q115 230 165 205 Q225 182 290 208 Q345 225 400 175 L400 260 L0 260 Z" fill="#7ca600"/>
                    </svg>
                  </div>
                  <div class="howto-placeholder-scene">
                    <svg viewBox="0 0 400 260" preserveAspectRatio="none" class="howto-scene-svg" xmlns="http://www.w3.org/2000/svg">
                      <rect width="400" height="260" fill="url(#skyGradSingle)"/>
                      <g fill="#ffffff">
                        <path d="M5 75h35a14 14 0 0 0 0-28 18 18 0 0 0-35 0z"/>
                        <path d="M165 82h72a22 22 0 0 0 0-44 28 28 0 0 0-54-8 20 20 0 0 0-18 52z"/>
                      </g>
                      <path d="M0 190 Q130 148 280 205 L400 220 L400 260 L0 260 Z" fill="#c7e576"/>
                      <path d="M0 168 Q35 178 70 212 Q115 230 165 205 Q225 182 290 208 Q345 225 400 175 L400 260 L0 260 Z" fill="#7ca600"/>
                    </svg>
                  </div>
                </div>

                <button
                  type="button"
                  id="howto-next-btn"
                  class="howto-nav-arrow-btn howto-nav-next"
                  onclick="nextHowtoStep()"
                  aria-label="Next step"
                >
                  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 18 15 12 9 6"></polyline>
                  </svg>
                </button>
              </div>

              <!-- Bottom Reminder (Visible on Step 8) -->
              <p id="howto-modal-reminder" class="howto-modal-reminder-inline" style="display: none;"></p>

              <!-- Clickable 8-Dot Indicator Row in Lower Box UI -->
              <div class="howto-dots-bar-inline" role="tablist" aria-label="Guide Steps">
                <?php for ($d = 0; $d < 8; $d++): ?>
                  <button
                    type="button"
                    class="howto-dot-btn <?= $d === 0 ? 'active' : '' ?>"
                    onclick="goToHowtoStep(<?= $d ?>)"
                    aria-label="Go to Step <?= $d + 1 ?>"
                    title="Step <?= $d + 1 ?>"
                  ></button>
                <?php endfor; ?>
              </div>
            </div>
          </div>
        </div>

  <!-- Services Section -->
  <section id="services-specialties" class="services-section-inline" style="scroll-margin-top: 76px;">
    <!-- Top Light Green Chevron Band -->
    <div class="services-header-band-inline">
      <div class="container-inline-services">
        <div class="header-content-inline" data-aos="fade-up">
          <h2 class="script-accent-inline">
            <img src="/sdn/public/assets/our_specialties_brush.png?v=<?= time() ?>" alt="Our Specialties" class="specialties-brush-inline" />
          </h2>
          <h3 class="main-heading-inline" data-i18n="services_title">MEDICAL SERVICES</h3>
          <p class="subtitle-inline" data-i18n="services_sub">Comprehensive care across mental and physical wellness.</p>
        </div>
      </div>
    </div>

    <div class="services-cards-area-inline">
      <div class="container-inline-services">
        <!-- Tiles Grid -->
        <div id="inline-tiles-grid" class="tiles-grid-inline">
          <?php
            $departments = [
              [
                'id' => 'general',
                'name' => 'General<br>Medical',
                'subname' => '',
                'image' => '/sdn/public/assets/generalmngmnt_img(orig).jpeg',
                'link' => '/sdn/pages/regis-general.php',
                'services' => [
                  'General Medical Consultation',
                  'Physical Examination',
                  'Laboratory and Diagnostic Requests',
                  'Preventive Healthcare',
                  'Chronic Disease Management'
                ]
              ],
              [
                'id' => 'neurology',
                'name' => 'Neurology',
                'subname' => '',
                'image' => '/sdn/public/assets/neurology_img(orig).jpeg',
                'link' => '/sdn/pages/regis-neurology.php',
                'services' => [
                  'Neurology Consultation',
                  'Neurological Diagnostic Testing',
                  'Stroke Evaluation and Management',
                  'Headache and Migraine Management',
                  'Epilepsy and Seizure Management'
                ]
              ],
              [
                'id' => 'psychiatry',
                'name' => 'OPU<br>Psychiatry',
                'subname' => 'Outpatient Psychiatry Unit',
                'image' => '/sdn/public/assets/psychiatry_img(orig).jpeg',
                'link' => '/sdn/pages/regis-psychiatry.php',
                'services' => [
                  'Rehabilitation Services',
                  'Mental Health Education',
                  'Psychotherapy',
                  'Psychological Assessment',
                  'Child and Adolescent Psychology'
                ]
              ]
            ];
          ?>
          <?php foreach($departments as $i => $dept): ?>
            <div 
              class="dept-tile-inline" 
              data-aos="fade-up"
              data-aos-delay="<?= $i * 100 ?>"
            >
              <div class="tile-bg-img-inline" style="background-image: url('<?= $dept['image'] ?>');"></div>
              <div class="tile-overlay-inline"></div>

              <!-- Default Title Content (Visible when cursor is out) -->
              <div class="tile-content-inline">
                <h4 class="tile-title-inline"><?= $dept['name'] ?></h4>
                <?php if (!empty($dept['subname'])): ?>
                  <p class="tile-subtitle-inline"><?= $dept['subname'] ?></p>
                <?php endif; ?>
              </div>

              <!-- Hover Services Overlay (Visible when cursor drags/hovers onto box) -->
              <div class="tile-hover-services-inline">
                <div class="tile-hover-header-inline">
                  <span class="tile-hover-title-inline"><?= str_replace('<br>', ' ', $dept['name']) ?></span>
                  <div class="tile-hover-divider-inline"></div>
                </div>
                <ul class="tile-services-ul-inline">
                  <?php foreach($dept['services'] as $srv): ?>
                    <li class="tile-service-li-inline">
                      <span class="tile-service-dot-inline"></span>
                      <span class="tile-service-text-inline"><?= htmlspecialchars($srv, ENT_QUOTES, 'UTF-8') ?></span>
                    </li>
                  <?php endforeach; ?>
                </ul>
                <p class="tile-services-more-inline">and other more..</p>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </section>
</section>

<style>
  .howto-section-inline {
    min-height: calc(100vh - 76px);
    padding: 3rem 2rem 3.75rem;
    text-align: center;
    background: #eef8f0;
    display: flex;
    flex-direction: column;
    justify-content: center;
    box-sizing: border-box;
  }
  .howto-section-inline .container-inline-services {
    max-width: 1120px;
    width: 100%;
  }
  .howto-header-inline {
    position: relative;
    z-index: 5;
    margin-bottom: 2.5rem;
  }
  .howto-heading-inline {
    font-family: Impact, Haettenschweiler, 'Arial Narrow Bold', sans-serif;
    font-size: clamp(2.2rem, 3.8vw, 2.9rem);
    color: #082f04;
    font-weight: 400;
    letter-spacing: 1.5px;
    line-height: 1.05;
    margin: 0 0 0.45rem 0;
  }
  .howto-sub-inline {
    font-family: 'Inter', sans-serif;
    font-size: 0.94rem;
    font-weight: 400;
    font-style: italic;
    color: #1e293b;
    margin: 0;
  }
  .howto-stage-area-inline {
    position: relative;
    width: 100%;
    max-width: 1080px;
    margin: 0 auto;
  }
  .howto-grid-inline {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.9rem;
    max-width: 1080px;
    margin: 0 auto;
    padding: 0.65rem;
    transition: filter 0.3s ease, opacity 0.3s ease, transform 0.3s ease;
  }
  body.howto-modal-lock {
    overflow: hidden !important;
  }
  .howto-stage-area-inline.modal-open .howto-grid-inline {
    filter: blur(3px);
    opacity: 1;
    pointer-events: none;
    user-select: none;
  }
  .howto-card-inline {
    background: linear-gradient(165deg, rgba(255, 255, 255, 0.46) 0%, rgba(126, 217, 87, 0.36) 48%, rgba(37, 186, 80, 0.3) 100%);
    border: 1px solid rgba(255, 255, 255, 0.72);
    border-radius: 24px;
    padding: 2.45rem 1.5rem 2.3rem;
    height: 220px;
    box-sizing: border-box;
    backdrop-filter: blur(16px) saturate(150%);
    -webkit-backdrop-filter: blur(16px) saturate(150%);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.78);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-start;
    cursor: pointer;
    user-select: none;
    transition: transform 0.25s ease, background 0.25s ease;
  }
  .howto-card-inline:hover {
    transform: translateY(-4px);
    background: linear-gradient(165deg, rgba(255, 255, 255, 0.54) 0%, rgba(126, 217, 87, 0.48) 48%, rgba(37, 186, 80, 0.38) 100%);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.82);
  }
  .howto-card-inline:active {
    transform: translateY(-1px) scale(0.98);
  }

  /* Step-by-Step Guide Pop-up Modal Styles (Dark Dimmed + Blurred Backdrop like mmwgh.gov.ph) */
  .howto-modal-backdrop-inline {
    position: fixed;
    top: 86px;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 950;
    background: rgba(0, 0, 0, 0.72);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
    box-sizing: border-box;
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transition: opacity 0.28s ease, visibility 0.28s ease;
  }
  .howto-modal-backdrop-inline.open {
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
  }
  .howto-modal-wrapper-inline {
    width: 100%;
    max-width: 1020px;
    display: flex;
    flex-direction: column;
    align-items: center;
    transform: translateY(14px) scale(0.98);
    transition: transform 0.3s cubic-bezier(0.22, 1, 0.36, 1);
  }
  .howto-modal-backdrop-inline.open .howto-modal-wrapper-inline {
    transform: translateY(0) scale(1);
  }
  .howto-modal-card-inline {
    position: relative;
    width: 100%;
    height: 465px;
    display: flex;
    flex-direction: column;
    background: #ffffff;
    border-radius: 28px;
    padding: 2.35rem 3.5rem 1.65rem;
    box-shadow:
      0 22px 58px rgba(0, 0, 0, 0.15),
      0 6px 20px rgba(0, 0, 0, 0.06);
    text-align: left;
    box-sizing: border-box;
  }
  .howto-modal-close-btn {
    position: absolute;
    top: 1.2rem;
    right: 1.45rem;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: none;
    background: #f1f5f9;
    color: #475569;
    font-size: 1.4rem;
    line-height: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background-color 0.2s, color 0.2s, transform 0.2s;
  }
  .howto-modal-close-btn:hover {
    background: #e2e8f0;
    color: #0f172a;
    transform: scale(1.06);
  }
  .howto-modal-title-row {
    display: flex;
    align-items: baseline;
    gap: 1.2rem;
    margin-bottom: 0.75rem;
    flex-wrap: wrap;
    padding-right: 2.25rem;
    flex-shrink: 0;
  }
  .howto-modal-step-badge {
    font-family: Impact, Haettenschweiler, 'Arial Narrow Bold', sans-serif;
    font-size: clamp(2rem, 2.8vw, 2.45rem);
    color: #082f04;
    letter-spacing: 1px;
    line-height: 1;
  }
  .howto-modal-step-heading {
    font-family: 'Inter', sans-serif;
    font-size: clamp(1.2rem, 1.75vw, 1.45rem);
    font-weight: 700;
    color: #111827;
    margin: 0;
    line-height: 1.25;
  }
  .howto-modal-desc-single {
    font-family: 'Inter', sans-serif;
    font-size: 0.95rem;
    color: #1f2937;
    line-height: 1.55;
    margin: 0 0 1rem 0;
    min-height: 3rem;
    max-width: 860px;
    flex-shrink: 0;
  }
  .howto-modal-desc-dual {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.75rem;
    margin: 0 0 1rem 0;
    min-height: 3rem;
    flex-shrink: 0;
  }
  .howto-modal-desc-col {
    font-family: 'Inter', sans-serif;
    font-size: 0.94rem;
    color: #1f2937;
    line-height: 1.55;
    margin: 0;
  }
  .howto-modal-desc-col strong {
    font-weight: 700;
    color: #111827;
  }
  .howto-modal-stage-row {
    position: relative;
    flex: 1;
    min-height: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1.25rem;
  }
  .howto-nav-arrow-btn {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background-color: #575757;
    color: #ffffff;
    border: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    flex-shrink: 0;
    transition: background-color 0.2s ease, transform 0.2s ease, opacity 0.2s ease;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.18);
  }
  .howto-nav-arrow-btn:hover {
    background-color: #1d630b;
    transform: scale(1.08);
  }
  .howto-nav-arrow-btn:active {
    transform: scale(0.95);
  }
  .howto-nav-arrow-btn.hidden-arrow {
    visibility: hidden;
    opacity: 0;
    pointer-events: none;
  }
  .howto-visual-single-wrap {
    flex: 1;
    height: 100%;
    overflow: hidden;
  }
  .howto-visual-dual-wrap {
    flex: 1;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.4rem;
    height: 100%;
  }
  .howto-placeholder-scene {
    width: 100%;
    height: 100%;
    overflow: hidden;
    background: #e0f2fe;
  }
  .howto-scene-svg {
    width: 100%;
    height: 100%;
    display: block;
  }
  .howto-modal-reminder-inline {
    font-family: 'Inter', sans-serif;
    font-size: 0.9rem;
    color: #1f2937;
    line-height: 1.48;
    margin: 0.85rem 0 0 0;
    max-width: 860px;
    flex-shrink: 0;
  }
  .howto-modal-reminder-inline strong {
    font-weight: 700;
    color: #111827;
  }
  .howto-dots-bar-inline {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.6rem;
    margin-top: 1.15rem;
    flex-shrink: 0;
  }
  .howto-dot-btn {
    width: 11px;
    height: 11px;
    border-radius: 999px;
    border: none;
    background-color: #cbd5e1;
    padding: 0;
    cursor: pointer;
    transition: width 0.25s ease, background-color 0.25s ease, transform 0.2s ease;
  }
  .howto-dot-btn:hover {
    background-color: #94a3b8;
    transform: scale(1.15);
  }
  .howto-dot-btn.active {
    width: 28px;
    background-color: #082f04;
  }
  .howto-num-inline {
    font-family: Impact, Haettenschweiler, 'Arial Narrow Bold', sans-serif;
    font-size: clamp(2.9rem, 4.1vw, 3.6rem);
    color: #082f04;
    line-height: 1;
    margin-bottom: 1.5rem;
    display: block;
  }
  .howto-text-inline {
    font-family: 'Inter', sans-serif;
    font-size: 1rem;
    font-weight: 400;
    color: #0c2b07;
    line-height: 1.48;
    margin: 0;
    text-align: center;
  }
  .services-section-inline {
    position: relative;
    padding: 0;
    background: #ffffff;
    z-index: 2;
  }
  .services-header-band-inline {
    background: #e6fce2;
    padding: 2.25rem 2rem 2rem;
    clip-path: polygon(0 18px, 50% 0, 100% 18px, 100% calc(100% - 14px), 50% 100%, 0 calc(100% - 14px));
  }
  .services-cards-area-inline {
    padding: 0.75rem 2rem 4rem;
    background: linear-gradient(to bottom, #ffffff 0%, #ffffff 42%, #daf8d5 76%, #b2eba9 100%);
  }
  .container-inline-services {
    position: relative;
    max-width: 1020px;
    margin: 0 auto;
    z-index: 1;
  }
  .header-content-inline { text-align: center; margin-bottom: 0; }
  
  .script-accent-inline {
    margin: 0 0 0.25rem 0 !important;
    line-height: 1 !important;
    display: flex;
    justify-content: center;
    align-items: center;
  }
  .specialties-brush-inline {
    height: clamp(68px, 7.2vw, 86px);
    width: auto;
    object-fit: contain;
    display: inline-block;
    user-select: none;
    pointer-events: none;
  }
  .main-heading-inline {
    font-family: Impact, Haettenschweiler, 'Arial Narrow Bold', sans-serif !important;
    font-size: clamp(2.4rem, 4.4vw, 3.3rem) !important;
    color: #000000 !important;
    font-weight: 400 !important;
    margin-bottom: 0.65rem !important;
    letter-spacing: 2px !important;
    line-height: 1 !important;
  }
  .subtitle-inline {
    font-family: 'Inter', sans-serif !important;
    font-size: 0.92rem !important;
    font-style: italic !important;
    font-weight: 400 !important;
    color: #1e293b !important;
    margin: 0 !important;
  }
  
  .tiles-grid-inline {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.75rem;
    max-width: 920px;
    margin: 0 auto;
    transition: opacity 0.3s;
  }
  .dept-tile-inline {
    position: relative;
    border-radius: 14px;
    padding: 0;
    text-align: center;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.16);
    transition: transform 0.35s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.35s ease;
    height: 365px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    cursor: default;
    background-color: #2b3d46;
  }
  
  .tile-bg-img-inline {
    position: absolute;
    inset: 0;
    background-size: cover;
    background-position: center;
    filter: grayscale(100%) contrast(1.05) brightness(0.72);
    transition: filter 0.45s ease-in-out, transform 0.45s ease-out;
    z-index: 0;
  }

  .dept-tile-inline:hover {
    transform: translateY(-10px);
    box-shadow: 0 18px 34px rgba(0, 0, 0, 0.32);
  }

  .dept-tile-inline:hover .tile-bg-img-inline {
    filter: grayscale(0%) contrast(1.02) brightness(0.9);
    transform: scale(1.05);
  }
  
  .tile-overlay-inline {
    position: absolute;
    top: 0; left: 0; width: 100%; height: 100%;
    background: linear-gradient(to bottom, rgba(35, 58, 70, 0.38) 0%, rgba(25, 45, 56, 0.58) 100%);
    z-index: 1;
    transition: background 0.4s ease-in-out, opacity 0.4s ease-in-out;
  }
  
  .dept-tile-inline:hover .tile-overlay-inline {
    background: linear-gradient(
      to bottom,
      rgba(10, 48, 16, 0.72) 0%,
      rgba(6, 34, 11, 0.88) 100%
    );
  }
  
  .tile-content-inline {
    position: relative;
    z-index: 2;
    padding: 2.2rem 1.25rem 2.4rem;
    transition: transform 0.35s ease-out, opacity 0.3s ease-out;
  }

  .dept-tile-inline:hover .tile-content-inline {
    opacity: 0;
    transform: translateY(18px);
    pointer-events: none;
  }

  /* Hover Services Panel inside the Department Card */
  .tile-hover-services-inline {
    position: absolute;
    inset: 0;
    z-index: 3;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 1.5rem 1.35rem;
    opacity: 0;
    transform: translateY(14px);
    pointer-events: none;
    transition: opacity 0.35s ease, transform 0.35s cubic-bezier(0.22, 1, 0.36, 1);
    backdrop-filter: blur(2px);
  }

  .dept-tile-inline:hover .tile-hover-services-inline {
    opacity: 1;
    transform: translateY(0);
    pointer-events: none;
  }

  .tile-hover-header-inline {
    margin-bottom: 0.9rem;
    text-align: center;
  }

  .tile-hover-title-inline {
    font-family: Impact, Haettenschweiler, 'Arial Narrow Bold', sans-serif;
    font-size: 1.45rem;
    color: #ffffff;
    letter-spacing: 1px;
    text-transform: uppercase;
    display: block;
    line-height: 1.1;
    text-shadow: 0 2px 6px rgba(0, 0, 0, 0.4);
  }

  .tile-hover-divider-inline {
    width: 48px;
    height: 3px;
    background-color: #4ade80;
    border-radius: 999px;
    margin: 0.45rem auto 0;
  }

  .tile-services-ul-inline {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 0.7rem;
    text-align: center;
  }

  .tile-service-li-inline {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.25rem 0.5rem;
    background: transparent;
    border: none;
    color: #ffffff;
    font-family: 'Inter', sans-serif;
    font-size: 0.88rem;
    font-weight: 600;
    line-height: 1.35;
    text-shadow: 0 2px 6px rgba(0, 0, 0, 0.45);
  }

  .tile-service-dot-inline {
    display: none;
  }

  .tile-service-text-inline {
    flex: 1;
  }

  .tile-services-more-inline {
    font-family: 'Inter', sans-serif;
    font-size: 0.8rem;
    font-style: italic;
    font-weight: 500;
    color: rgba(255, 255, 255, 0.85);
    text-align: center;
    margin: 0.65rem 0 0 0;
    text-shadow: 0 2px 6px rgba(0, 0, 0, 0.45);
  }

  .tile-title-inline {
    font-family: Impact, Haettenschweiler, 'Arial Narrow Bold', sans-serif;
    font-size: 2.3rem;
    font-weight: 400;
    color: #ffffff;
    text-shadow: 0 2px 6px rgba(0, 0, 0, 0.35);
    line-height: 1.05;
    margin: 0;
    letter-spacing: 0.8px;
  }
  .tile-subtitle-inline {
    font-family: 'Inter', sans-serif;
    color: #14530e;
    font-style: italic;
    font-weight: 600;
    font-size: 0.65rem;
    margin-top: 0.35rem;
  }
  
  @media (max-width: 960px) {
    .howto-grid-inline { grid-template-columns: repeat(2, 1fr); gap: 1.25rem; }
    .howto-section-inline { min-height: auto; padding: 3rem 1.5rem 2.5rem; }
    .tiles-grid-inline { grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); }
    .services-header-band-inline { padding: 2.25rem 1.5rem 2rem; }
    .services-cards-area-inline { padding: 1rem 1.5rem 4rem; }
  }
  @media (max-width: 540px) {
    .howto-grid-inline { grid-template-columns: 1fr; }
  }
</style>

<script>
  // --- HOW TO USE LINGKOD 8-STEP MODAL LOGIC ---
  const howtoGuideData = [
    {
      step: 'STEP 1',
      stepTl: 'HAKBANG 1',
      title: 'Click "Book Appointment."',
      titleTl: 'I-click ang "Magpa-appointment."',
      layout: 'single',
      desc: 'On the homepage, locate and click the Book Appointment button to begin the appointment process.',
      descTl: 'Sa homepage, hanapin at i-click ang button na Magpa-appointment upang simulan ang proseso ng appointment.'
    },
    {
      step: 'STEP 2',
      stepTl: 'HAKBANG 2',
      title: 'Select a Service.',
      titleTl: 'Pumili ng Serbisyo.',
      layout: 'single',
      desc: 'You will see three available services. Choose the service that best matches your needs or the type of appointment you want to request.',
      descTl: 'Makikita mo ang tatlong available na serbisyo. Piliin ang serbisyong tumutugma sa iyong pangangailangan o sa uri ng appointment na nais mong hilingin.'
    },
    {
      step: 'STEP 3',
      stepTl: 'HAKBANG 3',
      title: 'Select Your Patient Type.',
      titleTl: 'Piliin ang Uri ng Pasyente.',
      layout: 'single',
      desc: 'You will see three available services. Choose the service that best matches your needs or the type of appointment you want to request.',
      descTl: 'Makikita mo ang tatlong available na serbisyo. Piliin ang serbisyong tumutugma sa iyong pangangailangan o sa uri ng appointment na nais mong hilingin.'
    },
    {
      step: 'STEP 4',
      stepTl: 'HAKBANG 4',
      title: 'Proceed to Registration or Login.',
      titleTl: 'Magpatuloy sa Rehistrasyon o Pag-login.',
      layout: 'dual',
      descLeft: '<strong>First-Time Patient:</strong> Proceed to the registration form and provide the required information.',
      descLeftTl: '<strong>Unang Beses na Pasyente:</strong> Magpatuloy sa registration form at ibigay ang kinakailangang impormasyon.',
      descRight: '<strong>Returning Patient:</strong> Log in using your existing account credentials.',
      descRightTl: '<strong>Bumabalik na Pasyente:</strong> Mag-log in gamit ang iyong kasalukuyang account.'
    },
    {
      step: 'STEP 5',
      stepTl: 'HAKBANG 5',
      title: 'Fill Out the Required Form.',
      titleTl: 'Sagutan ang Kinakailangang Form.',
      layout: 'single',
      desc: 'Complete the form by providing all the necessary information. If you selected the Inquiry Only option, you will also be provided with an inquiry form. Fill it out with the required details.',
      descTl: 'Kumpletuhin ang form sa pamamagitan ng pagbibigay ng lahat ng kinakailangang impormasyon. Kung pinili mo ang Inquiry Only, bibigyan ka rin ng inquiry form. Sagutan ito ng kinakailangang detalye.'
    },
    {
      step: 'STEP 6',
      stepTl: 'HAKBANG 6',
      title: 'Confirm Your Registration.',
      titleTl: 'Kumpirmahin ang Rehistrasyon.',
      layout: 'single',
      desc: 'After completing the form, carefully review the information you provided. Once everything is correct, click the Confirm Registration button.',
      descTl: 'Pagkatapos kumpletuhin ang form, suriing mabuti ang impormasyong ibinigay. Kapag tama na ang lahat, i-click ang Confirm Registration.'
    },
    {
      step: 'STEP 7',
      stepTl: 'HAKBANG 7',
      title: 'Proceed to the Next Step.',
      titleTl: 'Magpatuloy sa Susunod na Hakbang.',
      layout: 'single',
      desc: 'After confirming your registration, click Next to continue with the process of creating your account.',
      descTl: 'Pagkatapos kumpirmahin ang rehistrasyon, i-click ang Next upang magpatuloy sa paggawa ng iyong account.'
    },
    {
      step: 'STEP 8',
      stepTl: 'HAKBANG 8',
      title: 'Confirm Your Registration.',
      titleTl: 'Kumpirmahin ang Rehistrasyon.',
      layout: 'single',
      desc: 'Provide the required account credentials, such as your email address and password. Follow the instructions displayed on the screen to complete your account creation.',
      descTl: 'Ibigay ang kinakailangang detalye ng account, tulad ng email address at password. Sundin ang mga tagubilin sa screen upang matapos ang paggawa ng account.',
      reminder: '<strong>Reminder:</strong> Make sure that all the information you provide is accurate and complete to avoid delays during the registration and appointment process.',
      reminderTl: '<strong>Paalala:</strong> Siguraduhing tama at kumpleto ang lahat ng impormasyon upang maiwasan ang pagkaantala sa rehistrasyon at appointment.'
    }
  ];

  function howtoLang() {
    return localStorage.getItem('lingkodLang') === 'tl' ? 'tl' : 'en';
  }

  function howtoField(item, field) {
    if (howtoLang() === 'tl' && item[field + 'Tl']) return item[field + 'Tl'];
    return item[field];
  }

  let currentHowtoIndex = 0;

  function renderHowtoStep(index) {
    if (index < 0) index = 0;
    if (index >= howtoGuideData.length) index = howtoGuideData.length - 1;
    currentHowtoIndex = index;

    const item = howtoGuideData[index];
    document.getElementById('howto-modal-step-num').textContent = howtoField(item, 'step');
    document.getElementById('howto-modal-step-title').textContent = howtoField(item, 'title');

    const descSingle = document.getElementById('howto-modal-desc-single');
    const descDual = document.getElementById('howto-modal-desc-dual');
    const visSingle = document.getElementById('howto-visual-single');
    const visDual = document.getElementById('howto-visual-dual');
    const reminderEl = document.getElementById('howto-modal-reminder');

    if (item.layout === 'dual') {
      descSingle.style.display = 'none';
      descDual.style.display = 'grid';
      const cols = descDual.querySelectorAll('.howto-modal-desc-col');
      if (cols[0]) cols[0].innerHTML = howtoField(item, 'descLeft');
      if (cols[1]) cols[1].innerHTML = howtoField(item, 'descRight');
      visSingle.style.display = 'none';
      visDual.style.display = 'grid';
    } else {
      descSingle.style.display = 'block';
      descSingle.textContent = howtoField(item, 'desc');
      descDual.style.display = 'none';
      visSingle.style.display = 'block';
      visDual.style.display = 'none';
    }

    if (item.reminder) {
      reminderEl.innerHTML = howtoField(item, 'reminder');
      reminderEl.style.display = 'block';
      visSingle.classList.add('with-reminder');
    } else {
      reminderEl.innerHTML = '';
      reminderEl.style.display = 'none';
      visSingle.classList.remove('with-reminder');
    }

    // Update Prev / Next arrow visibility
    const prevBtn = document.getElementById('howto-prev-btn');
    const nextBtn = document.getElementById('howto-next-btn');
    if (index === 0) {
      prevBtn.classList.add('hidden-arrow');
    } else {
      prevBtn.classList.remove('hidden-arrow');
    }
    if (index === howtoGuideData.length - 1) {
      nextBtn.classList.add('hidden-arrow');
    } else {
      nextBtn.classList.remove('hidden-arrow');
    }

    // Update clickable dot indicators
    const dots = document.querySelectorAll('#howto-modal-backdrop .howto-dot-btn');
    dots.forEach((dot, dIdx) => {
      if (dIdx === index) {
        dot.classList.add('active');
        dot.setAttribute('aria-selected', 'true');
      } else {
        dot.classList.remove('active');
        dot.setAttribute('aria-selected', 'false');
      }
    });
  }

  function preventLandingScroll(e) {
    const modal = document.getElementById('howto-modal-backdrop');
    if (modal && modal.classList.contains('open')) {
      e.preventDefault();
    }
  }

  function openHowtoModal(index) {
    renderHowtoStep(index);
    const stage = document.getElementById('howto-stage-area');
    const modal = document.getElementById('howto-modal-backdrop');
    if (stage) stage.classList.add('modal-open');
    modal.classList.add('open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('howto-modal-lock');
    window.addEventListener('wheel', preventLandingScroll, { passive: false });
    window.addEventListener('touchmove', preventLandingScroll, { passive: false });
  }

  function closeHowtoModal() {
    const stage = document.getElementById('howto-stage-area');
    const modal = document.getElementById('howto-modal-backdrop');
    if (stage) stage.classList.remove('modal-open');
    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('howto-modal-lock');
    window.removeEventListener('wheel', preventLandingScroll);
    window.removeEventListener('touchmove', preventLandingScroll);
  }

  function handleHowtoBackdropClick(e) {
    // Require clicking the 'X' button to close and re-enable scrolling
  }

  function nextHowtoStep() {
    if (currentHowtoIndex < howtoGuideData.length - 1) {
      renderHowtoStep(currentHowtoIndex + 1);
    }
  }

  function prevHowtoStep() {
    if (currentHowtoIndex > 0) {
      renderHowtoStep(currentHowtoIndex - 1);
    }
  }

  function goToHowtoStep(index) {
    renderHowtoStep(index);
  }

  document.addEventListener('lingkod-lang', () => {
    const modal = document.getElementById('howto-modal-backdrop');
    if (modal && modal.classList.contains('open')) renderHowtoStep(currentHowtoIndex);
  });

  document.addEventListener('keydown', (e) => {
    const modal = document.getElementById('howto-modal-backdrop');
    if (!modal || !modal.classList.contains('open')) return;
    if (['ArrowUp', 'ArrowDown', 'PageUp', 'PageDown', 'Home', 'End', ' '].includes(e.key)) {
      e.preventDefault();
    }
    if (e.key === 'ArrowRight') nextHowtoStep();
    if (e.key === 'ArrowLeft') prevHowtoStep();
  });
</script>
