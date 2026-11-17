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
        <div class="howto-grid-inline max-md:grid max-md:grid-cols-4 max-md:gap-2">
          <?php
            $howtoSteps = [
              ['num' => '1', 'text' => 'Click "Book<br>Appointment."'],
              ['num' => '2', 'text' => 'Select "Already Have an Account"<br>or "New Patient".'],
              ['num' => '3', 'text' => 'Register and Verify<br>Your Account.'],
              ['num' => '4', 'text' => 'Log In to Book<br>an Appointment.'],
              ['num' => '5', 'text' => 'Fill Out the Required<br>Form.'],
              ['num' => '6', 'text' => 'Confirm Your<br>Appointment.'],
              ['num' => '7', 'text' => 'Wait for Appointment<br>Approval.'],
              ['num' => '8', 'text' => 'Confirmed<br>Appointment.'],
            ];
            foreach ($howtoSteps as $idx => $step):
          ?>
            <div 
              class="howto-card-inline max-md:box-border max-md:flex max-md:h-auto max-md:min-w-0 max-md:flex-col max-md:items-center max-md:justify-center max-md:overflow-hidden max-md:p-1.5 max-md:text-center" 
              data-aos="fade-up" 
              data-aos-delay="<?= ($idx % 4) * 70 ?>"
              onclick="openHowtoModal(<?= $idx ?>)"
              role="button"
              tabindex="0"
              onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();openHowtoModal(<?= $idx ?>);}"
            >
              <span class="howto-num-inline max-md:mb-1 max-md:text-sm max-md:leading-none"><?= $step['num'] ?></span>
              <p class="howto-text-inline max-md:text-center max-md:text-[10px] max-md:leading-tight max-md:break-words" data-i18n-html="howto_<?= $idx ?>"><?= $step['text'] ?></p>
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

                <!-- Single Visual Frame (Step Video Player) -->
                <div id="howto-visual-single" class="howto-visual-single-wrap" onclick="handleHowtoVideoSurfaceClick(event)">
                  <video
                    id="howto-step-video"
                    class="howto-step-video"
                    muted
                    playsinline
                    preload="metadata"
                  ></video>

                  <button
                    type="button"
                    id="howto-video-center-btn"
                    class="howto-video-center-btn"
                    onclick="toggleHowtoVideo(event)"
                    aria-label="Play step video"
                  >
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                      <polygon points="7 4 20 12 7 20 7 4"></polygon>
                    </svg>
                  </button>

                  <div class="howto-video-controls" onclick="event.stopPropagation()">
                    <button
                      type="button"
                      id="howto-video-toggle-btn"
                      class="howto-video-ctrl-btn"
                      onclick="toggleHowtoVideo(event)"
                      aria-label="Play or pause video"
                    >
                      <svg id="howto-ctrl-play-icon" width="15" height="15" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <polygon points="6 4 20 12 6 20 6 4"></polygon>
                      </svg>
                      <svg id="howto-ctrl-pause-icon" width="15" height="15" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" style="display: none;">
                        <rect x="6" y="4" width="4" height="16" rx="1"></rect>
                        <rect x="14" y="4" width="4" height="16" rx="1"></rect>
                      </svg>
                    </button>

                    <div
                      id="howto-video-progress-track"
                      class="howto-video-progress-track"
                      onclick="seekHowtoVideo(event)"
                      role="slider"
                      aria-label="Video progress"
                      aria-valuemin="0"
                      aria-valuemax="100"
                      aria-valuenow="0"
                      tabindex="0"
                    >
                      <div id="howto-video-progress-fill" class="howto-video-progress-fill"></div>
                    </div>

                    <span id="howto-video-time" class="howto-video-time">0s / 0s</span>

                    <button
                      type="button"
                      id="howto-video-expand-btn"
                      class="howto-video-ctrl-btn"
                      onclick="toggleHowtoMaximize(event)"
                      aria-label="Maximize video"
                      title="Maximize"
                    >
                      <svg id="howto-expand-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="15 3 21 3 21 9"></polyline>
                        <polyline points="9 21 3 21 3 15"></polyline>
                        <line x1="21" y1="3" x2="14" y2="10"></line>
                        <line x1="3" y1="21" x2="10" y2="14"></line>
                      </svg>
                      <svg id="howto-compress-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="display: none;">
                        <polyline points="4 14 10 14 10 20"></polyline>
                        <polyline points="20 10 14 10 14 4"></polyline>
                        <line x1="14" y1="10" x2="21" y2="3"></line>
                        <line x1="3" y1="21" x2="10" y2="14"></line>
                      </svg>
                    </button>
                  </div>
                </div>

                <!-- Dual Visual Frames (For Step 4) -->
                <div id="howto-visual-dual" class="howto-visual-dual-wrap" style="display: none;">
                  <div class="howto-placeholder-scene">
                    <svg viewBox="0 0 400 260" preserveAspectRatio="xMidYMid slice" class="howto-scene-svg" xmlns="http://www.w3.org/2000/svg">
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
                    <svg viewBox="0 0 400 260" preserveAspectRatio="xMidYMid slice" class="howto-scene-svg" xmlns="http://www.w3.org/2000/svg">
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

  <!-- Maximized Video Lightbox -->
  <div id="howto-video-lightbox" class="howto-video-lightbox" onclick="minimizeHowtoVideo()" aria-hidden="true">
    <div class="howto-video-lightbox-inner" onclick="event.stopPropagation()">
      <button type="button" class="howto-lightbox-close-btn" onclick="minimizeHowtoVideo()" aria-label="Minimize video">&times;</button>
      <div id="howto-video-lightbox-slot" class="howto-video-lightbox-slot"></div>
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
        <div id="inline-tiles-grid" class="tiles-grid-inline max-md:grid max-md:min-w-0 max-md:grid-cols-3 max-md:gap-2">
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
                'name' => 'Psychiatry',
                'subname' => '',
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
              class="dept-tile-inline max-md:aspect-[3/5] max-md:h-auto max-md:min-w-0 max-md:overflow-hidden" 
              data-aos="fade-up"
              data-aos-delay="<?= $i * 100 ?>"
            >
              <div class="tile-bg-img-inline max-md:bg-cover max-md:bg-center max-md:bg-no-repeat" style="background-image: url('<?= $dept['image'] ?>');"></div>
              <div class="tile-overlay-inline"></div>

              <!-- Default Title Content (Visible when cursor is out) -->
              <div class="tile-content-inline max-md:p-1.5 max-md:text-center">
                <h4 class="tile-title-inline max-md:text-[10px] max-md:leading-tight max-md:break-words"><?= $dept['name'] ?></h4>
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
    width: 100%;
  }
  .howto-nav-arrow-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background-color: #475569;
    color: #ffffff;
    border: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 15;
    transition: background-color 0.2s ease, transform 0.2s ease, opacity 0.2s ease, visibility 0.2s ease;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.22);
  }
  .howto-nav-arrow-btn:hover {
    background-color: #15803d;
    transform: translateY(-50%) scale(1.1);
  }
  .howto-nav-arrow-btn:active {
    transform: translateY(-50%) scale(0.95);
  }
  .howto-nav-prev {
    left: 0.5rem;
  }
  .howto-nav-next {
    right: 0.5rem;
  }
  .howto-nav-arrow-btn.hidden-arrow {
    visibility: hidden;
    opacity: 0;
    pointer-events: none;
  }
  .howto-visual-single-wrap {
    position: relative;
    width: 100%;
    max-width: 520px;
    height: 100%;
    max-height: 235px;
    aspect-ratio: 16 / 9;
    margin: 0 auto;
    border-radius: 18px;
    border: 2px solid #e2e8f0;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    background: #0b1320;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    user-select: none;
  }
  .howto-step-video {
    width: 100%;
    height: 100%;
    object-fit: contain;
    display: block;
    background: #0b1320;
  }
  .howto-video-center-btn {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) scale(1);
    width: 54px;
    height: 54px;
    border-radius: 50%;
    border: 2px solid rgba(255, 255, 255, 0.9);
    background: rgba(15, 23, 42, 0.72);
    backdrop-filter: blur(6px);
    color: #ffffff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 3;
    transition: opacity 0.2s ease, transform 0.2s ease, background-color 0.2s ease;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.35);
    padding-left: 3px;
  }
  .howto-visual-single-wrap:hover .howto-video-center-btn {
    background: rgba(21, 128, 61, 0.9);
    transform: translate(-50%, -50%) scale(1.06);
  }
  .howto-visual-single-wrap.is-playing .howto-video-center-btn {
    opacity: 0;
    pointer-events: none;
    transform: translate(-50%, -50%) scale(0.88);
  }
  .howto-video-controls {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    padding: 20px 12px 9px;
    background: linear-gradient(to top, rgba(15, 23, 42, 0.88) 0%, rgba(15, 23, 42, 0.45) 60%, transparent 100%);
    display: flex;
    align-items: center;
    gap: 10px;
    z-index: 4;
    cursor: default;
  }
  .howto-video-ctrl-btn {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    border: none;
    background: rgba(255, 255, 255, 0.16);
    color: #ffffff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    flex-shrink: 0;
    transition: background-color 0.15s ease;
  }
  .howto-video-ctrl-btn:hover {
    background: rgba(34, 197, 94, 0.85);
  }
  .howto-video-progress-track {
    flex: 1;
    height: 5px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.28);
    cursor: pointer;
    overflow: hidden;
    position: relative;
    transition: height 0.15s ease;
  }
  .howto-video-progress-track:hover {
    height: 7px;
  }
  .howto-video-progress-fill {
    height: 100%;
    width: 0%;
    background: #22c55e;
    border-radius: 999px;
    transition: width 0.08s linear;
  }
  .howto-video-time {
    font-family: 'Inter', sans-serif;
    font-size: 0.74rem;
    font-weight: 600;
    color: #f8fafc;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
    flex-shrink: 0;
  }
  .howto-video-lightbox {
    position: fixed;
    inset: 0;
    z-index: 10000;
    background: rgba(0, 0, 0, 0.68);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transition: opacity 0.22s ease, visibility 0.22s ease;
  }
  .howto-video-lightbox.open {
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
  }
  .howto-video-lightbox-inner {
    position: relative;
    width: min(1040px, 94vw);
    max-height: 88vh;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .howto-video-lightbox-slot {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .howto-visual-single-wrap.is-maximized {
    max-width: 100%;
    width: 100%;
    max-height: 84vh;
    height: auto;
    border-radius: 16px;
    border-color: rgba(255, 255, 255, 0.18);
    box-shadow: 0 24px 64px rgba(0, 0, 0, 0.55);
  }
  .howto-lightbox-close-btn {
    position: absolute;
    top: -14px;
    right: -14px;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    border: 1px solid rgba(255, 255, 255, 0.25);
    background: #1e293b;
    color: #ffffff;
    font-size: 1.45rem;
    line-height: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 10;
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.35);
    transition: background-color 0.2s ease, transform 0.2s ease;
  }
  .howto-lightbox-close-btn:hover {
    background: #15803d;
    transform: scale(1.08);
  }
  .howto-visual-dual-wrap {
    width: 100%;
    max-width: 640px;
    height: 100%;
    max-height: 235px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.25rem;
  }
  .howto-placeholder-scene {
    width: 100%;
    height: 100%;
    overflow: hidden;
    background: #0f172a;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 16px;
  }
  .howto-scene-svg,
  .howto-placeholder-scene video,
  .howto-placeholder-scene img,
  .howto-visual-single-wrap video,
  .howto-visual-single-wrap img {
    width: 100%;
    height: 100%;
    object-fit: contain;
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
    width: 100%;
    padding: 0;
    background: #ffffff;
    z-index: 2;
  }
  .services-header-band-inline {
    width: 100%;
    margin: 0;
    background: #e6fce2;
    padding: 2.4rem 2rem 1.7rem;
    box-sizing: border-box;
  }
  .services-cards-area-inline {
    padding: 0.75rem 2rem 4rem;
    background: #ffffff;
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
    .howto-grid-inline { grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.5rem; }
    .howto-card-inline { height: auto; min-width: 0; padding: 0.375rem; overflow: hidden; }
    .howto-num-inline { font-size: 0.875rem; margin-bottom: 0.25rem; line-height: 1; }
    .howto-text-inline { font-size: 10px; line-height: 1.15; overflow-wrap: break-word; word-break: break-word; text-align: center; }
  }

  /* Phone only: keep prev/next in the white card, outside the illustration. */
  @media (max-width: 720px) {
    .howto-modal-card-inline {
      height: auto;
      padding: 1.6rem 1rem 1.25rem;
    }
    .howto-modal-stage-row {
      gap: 0.65rem;
    }
    .howto-nav-arrow-btn {
      position: static;
      transform: none;
      flex-shrink: 0;
    }
    .howto-nav-arrow-btn:hover {
      transform: scale(1.08);
    }
    .howto-nav-arrow-btn:active {
      transform: scale(0.96);
    }
    .howto-visual-single-wrap,
    .howto-visual-dual-wrap {
      flex: 1;
      width: auto;
      min-width: 0;
      max-width: none;
      margin: 0;
    }
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
      video: '/sdn/public/assets/HOW_TO_USE_LINGKOD/STEP-1.mp4',
      desc: 'On the homepage, locate and click the Book Appointment button to begin the appointment process.',
      descTl: 'Sa homepage, hanapin at i-click ang button na Magpa-appointment upang simulan ang proseso ng appointment.'
    },
    {
      step: 'STEP 2',
      stepTl: 'HAKBANG 2',
      title: 'Select "Already Have an Account" or "New Patient".',
      titleTl: 'Piliin ang "May Account Na" o "Bagong Pasyente".',
      layout: 'single',
      video: '/sdn/public/assets/HOW_TO_USE_LINGKOD/STEP-2.mp4',
      desc: 'A prompt will appear asking for your patient status. Choose "Already Have an Account" if you are a returning patient to sign in, or select "New Patient" to begin registration.',
      descTl: 'May lalabas na opsyon para sa estado ng pasyente. Piliin ang "May Account Na" kung dati ka nang may account upang mag-sign in, o piliin ang "Bagong Pasyente" upang simulan ang rehistrasyon.'
    },
    {
      step: 'STEP 3',
      stepTl: 'HAKBANG 3',
      title: 'Register and Verify Your Account.',
      titleTl: 'Magrehistro at Mag-verify ng Account.',
      layout: 'single',
      video: '/sdn/public/assets/HOW_TO_USE_LINGKOD/STEP-3.mp4',
      desc: 'For new patients, fill out the registration form with your personal details and create your account credentials. You must then verify your account through email before you can log in.',
      descTl: 'Para sa mga bagong pasyente, sagutan ang registration form gamit ang iyong personal na detalye at lumikha ng account credentials. I-verify ang iyong account sa email bago mag-log in.'
    },
    {
      step: 'STEP 4',
      stepTl: 'HAKBANG 4',
      title: 'Log In to Book an Appointment.',
      titleTl: 'Mag-login para Magpa-appointment.',
      layout: 'single',
      video: '/sdn/public/assets/HOW_TO_USE_LINGKOD/STEP-4.mp4',
      desc: 'After successfully logging in to your patient account, navigate to the patient portal and click "Book Appointment" to begin your consultation request.',
      descTl: 'Pagkatapos mag-log in sa iyong patient account, magtungo sa patient portal at i-click ang "Book Appointment" upang simulan ang iyong kahilingan sa konsultasyon.'
    },
    {
      step: 'STEP 5',
      stepTl: 'HAKBANG 5',
      title: 'Fill Out the Required Form.',
      titleTl: 'Sagutan ang Kinakailangang Form.',
      layout: 'single',
      video: '/sdn/public/assets/HOW_TO_USE_LINGKOD/STEP-5v2.mp4',
      desc: 'First, review and accept the Data Privacy Consent (R.A. 10173) by scrolling to the bottom and confirming. Then, fill out your chief complaint and choose your consultation schedule.',
      descTl: 'Una, basahin at tanggapin ang Data Privacy Consent (R.A. 10173) sa pamamagitan ng pag-scroll pababa at pag-confirm. Pagkatapos, punan ang iyong chief complaint at piliin ang iskedyul ng konsultasyon.'
    },
    {
      step: 'STEP 6',
      stepTl: 'HAKBANG 6',
      title: 'Confirm Your Appointment.',
      titleTl: 'Kumpirmahin ang Appointment.',
      layout: 'single',
      video: '/sdn/public/assets/HOW_TO_USE_LINGKOD/STEP-6.mp4',
      desc: 'Review your entered information, clinic date, and consultation details carefully. Once confirmed, click Submit to queue your appointment request.',
      descTl: 'Suriing mabuti ang lahat ng ibinigay na impormasyon, petsa ng clinic, at detalye ng konsultasyon. Kapag tama na ang lahat, i-click ang Submit upang maipila ang appointment.'
    },
    {
      step: 'STEP 7',
      stepTl: 'HAKBANG 7',
      title: 'Wait for Appointment Approval.',
      titleTl: 'Maghintay ng Pag-apruba ng Appointment.',
      layout: 'single',
      video: '/sdn/public/assets/HOW_TO_USE_LINGKOD/STEP-7.mp4',
      desc: 'Your appointment will be queued for review. Monitor its status in your Patient Dashboard under Upcoming Appointments while our clinical staff reviews your complaints and assigns the proper department.',
      descTl: 'Ang iyong appointment ay nakapila para sa pagsusuri. Subaybayan ang estado nito sa iyong Patient Dashboard sa ilalim ng Upcoming Appointments habang sinusuri ng clinical staff ang iyong complaint at itinatalaga ang tamang departamento.'
    },
    {
      step: 'STEP 8',
      stepTl: 'HAKBANG 8',
      title: 'Confirmed Appointment.',
      titleTl: 'Kumpirmadong Appointment.',
      layout: 'single',
      video: '/sdn/public/assets/HOW_TO_USE_LINGKOD/STEP-8.mp4',
      desc: 'Once approved by the hospital staff, your appointment status will change to "Confirmed". You will receive reminder notifications with complete visit instructions before your clinic day.',
      descTl: 'Kapag naaprubahan na ng hospital staff, ang estado ng appointment ay magiging "Confirmed". Makakatanggap ka ng mga abiso ng paalala kasama ang kumpletong tagubilin bago ang araw ng iyong konsultasyon.',
      reminder: '<strong>Reminder:</strong> Please arrive on time at the Outpatient Triage desk on your confirmed appointment date with a valid ID.',
      reminderTl: '<strong>Paalala:</strong> Mangyaring pumunta sa tamang oras sa Outpatient Triage desk sa iyong kumpirmadong petsa ng appointment na may dalang valid ID.'
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

  function formatHowtoSeconds(sec) {
    if (!Number.isFinite(sec) || sec < 0) return '0s';
    const total = Math.floor(sec);
    if (total < 60) return `${total}s`;
    const mins = Math.floor(total / 60);
    const secs = String(total % 60).padStart(2, '0');
    return `${mins}:${secs}`;
  }

  function syncHowtoVideoUI() {
    const video = document.getElementById('howto-step-video');
    const wrap = document.getElementById('howto-visual-single');
    const playIcon = document.getElementById('howto-ctrl-play-icon');
    const pauseIcon = document.getElementById('howto-ctrl-pause-icon');
    const fill = document.getElementById('howto-video-progress-fill');
    const track = document.getElementById('howto-video-progress-track');
    const timeEl = document.getElementById('howto-video-time');
    if (!video || !wrap) return;

    const isPlaying = !video.paused && !video.ended;
    wrap.classList.toggle('is-playing', isPlaying);
    if (playIcon) playIcon.style.display = isPlaying ? 'none' : 'block';
    if (pauseIcon) pauseIcon.style.display = isPlaying ? 'block' : 'none';

    const dur = Number.isFinite(video.duration) && video.duration > 0 ? video.duration : 0;
    const cur = Number.isFinite(video.currentTime) ? video.currentTime : 0;
    const pct = dur > 0 ? Math.min(100, Math.max(0, (cur / dur) * 100)) : 0;

    if (fill) fill.style.width = `${pct}%`;
    if (track) track.setAttribute('aria-valuenow', String(Math.round(pct)));
    if (timeEl) {
      timeEl.textContent = dur > 0
        ? `${formatHowtoSeconds(cur)} / ${formatHowtoSeconds(dur)}`
        : '0s / --s';
    }
  }

  function loadHowtoStepVideo(src) {
    const video = document.getElementById('howto-step-video');
    if (!video) return;
    video.muted = true;
    video.pause();
    if (src && video.dataset.src !== src) {
      video.dataset.src = src;
      video.src = src;
      video.load();
    } else {
      video.currentTime = 0;
    }
    syncHowtoVideoUI();
  }

  function toggleHowtoVideo(e) {
    if (e) e.stopPropagation();
    const video = document.getElementById('howto-step-video');
    if (!video) return;
    if (video.paused || video.ended) {
      if (video.ended) video.currentTime = 0;
      video.play().catch(() => {});
    } else {
      video.pause();
    }
  }

  function seekHowtoVideo(e) {
    if (e) e.stopPropagation();
    const video = document.getElementById('howto-step-video');
    const track = document.getElementById('howto-video-progress-track');
    if (!video || !track || !Number.isFinite(video.duration) || video.duration <= 0) return;
    const rect = track.getBoundingClientRect();
    const ratio = Math.min(1, Math.max(0, (e.clientX - rect.left) / rect.width));
    video.currentTime = ratio * video.duration;
    syncHowtoVideoUI();
  }

  let isHowtoVideoMaximized = false;

  function updateHowtoExpandIcons() {
    const expandIcon = document.getElementById('howto-expand-icon');
    const compressIcon = document.getElementById('howto-compress-icon');
    const expandBtn = document.getElementById('howto-video-expand-btn');
    if (expandIcon) expandIcon.style.display = isHowtoVideoMaximized ? 'none' : 'block';
    if (compressIcon) compressIcon.style.display = isHowtoVideoMaximized ? 'block' : 'none';
    if (expandBtn) {
      expandBtn.setAttribute('aria-label', isHowtoVideoMaximized ? 'Minimize video' : 'Maximize video');
      expandBtn.setAttribute('title', isHowtoVideoMaximized ? 'Minimize' : 'Maximize');
    }
  }

  function maximizeHowtoVideo() {
    if (isHowtoVideoMaximized) return;
    const visSingle = document.getElementById('howto-visual-single');
    const lightbox = document.getElementById('howto-video-lightbox');
    const slot = document.getElementById('howto-video-lightbox-slot');
    const video = document.getElementById('howto-step-video');
    if (!visSingle || !lightbox || !slot) return;

    slot.appendChild(visSingle);
    visSingle.classList.add('is-maximized');
    lightbox.classList.add('open');
    lightbox.setAttribute('aria-hidden', 'false');
    isHowtoVideoMaximized = true;
    updateHowtoExpandIcons();

    if (video && (video.paused || video.ended)) {
      if (video.ended) video.currentTime = 0;
      video.play().catch(() => {});
    }
  }

  function minimizeHowtoVideo() {
    if (!isHowtoVideoMaximized) return;
    const visSingle = document.getElementById('howto-visual-single');
    const visDual = document.getElementById('howto-visual-dual');
    const lightbox = document.getElementById('howto-video-lightbox');
    if (!visSingle || !visDual || !lightbox) return;

    visDual.before(visSingle);
    visSingle.classList.remove('is-maximized');
    lightbox.classList.remove('open');
    lightbox.setAttribute('aria-hidden', 'true');
    isHowtoVideoMaximized = false;
    updateHowtoExpandIcons();
  }

  function toggleHowtoMaximize(e) {
    if (e) e.stopPropagation();
    if (isHowtoVideoMaximized) {
      minimizeHowtoVideo();
    } else {
      maximizeHowtoVideo();
    }
  }

  function handleHowtoVideoSurfaceClick(e) {
    if (e) e.stopPropagation();
    if (!isHowtoVideoMaximized) {
      maximizeHowtoVideo();
    } else {
      toggleHowtoVideo(e);
    }
  }

  (function bindHowtoVideoEvents() {
    const video = document.getElementById('howto-step-video');
    if (!video) return;
    ['loadedmetadata', 'timeupdate', 'play', 'pause', 'ended'].forEach((evt) => {
      video.addEventListener(evt, syncHowtoVideoUI);
    });
  })();

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
      visSingle.style.display = 'flex';
      visDual.style.display = 'none';
      loadHowtoStepVideo(item.video);
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
    minimizeHowtoVideo();
    const video = document.getElementById('howto-step-video');
    if (video) {
      video.pause();
      video.currentTime = 0;
      syncHowtoVideoUI();
    }
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
    if (e.target === e.currentTarget) closeHowtoModal();
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
    if (e.key === ' ') toggleHowtoVideo();
    if (e.key === 'Escape') {
      if (isHowtoVideoMaximized) {
        minimizeHowtoVideo();
      } else {
        closeHowtoModal();
      }
    }
    if (e.key === 'ArrowRight') nextHowtoStep();
    if (e.key === 'ArrowLeft') prevHowtoStep();
  });
</script>
