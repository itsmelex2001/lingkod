<!-- Inline Location Section -->
<section id="location-new" class="sitemap-section-inline" style="scroll-margin-top: 76px;" data-aos="fade-up" data-aos-duration="1200">

  <!-- Load Leaflet -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

  <div class="sitemap-container-inline">
    <!-- Top Centered SITE MAP Heading (Matching Image 1) -->
    <div class="sitemap-header-inline">
      <h2 class="sitemap-title-inline" aria-label="SITE MAP">
        <span class="sitemap-letter-inline">S</span>
        <span class="sitemap-pin-icon-inline" aria-hidden="true">
          <svg viewBox="0 0 56 60" fill="none" xmlns="http://www.w3.org/2000/svg">
            <!-- Perspective Map Base -->
            <path d="M11 45L4 57H52L45 45H11Z" fill="#1d630b"/>
            <!-- White Map Grid Lines on Base -->
            <path d="M7.5 51H48.5" stroke="#ffffff" stroke-width="1.6"/>
            <path d="M4 57H52" stroke="#1d630b" stroke-width="1"/>
            <path d="M6 54.5H50" stroke="#ffffff" stroke-width="1.4"/>
            <path d="M23 45L19 57" stroke="#ffffff" stroke-width="1.8"/>
            <!-- White Outline around Pin Tip to separate from Map Base -->
            <path d="M28 4C19.7157 4 13 10.7157 13 19C13 29.5 28 46 28 46C28 46 43 29.5 43 19C43 10.7157 36.2843 4 28 4Z" fill="#1d630b" stroke="#ffffff" stroke-width="2"/>
            <!-- Inner White Circle of Pin -->
            <circle cx="28" cy="18.5" r="6" fill="#ffffff"/>
          </svg>
        </span>
        <span class="sitemap-letter-inline">TE MAP</span>
      </h2>
    </div>

    <!-- Two-Column Layout (Left: Search/Filters/Directory, Right: Map Card) -->
    <div class="sitemap-grid-inline">
      
      <!-- Left Column -->
      <div class="sitemap-left-col-inline">
        <!-- Search Bar -->
        <div class="sitemap-controls-inline">
          <input
            type="text"
            id="inline-search"
            placeholder="Search"
            data-i18n-ph="map_search"
            class="sitemap-search-input-inline"
          />

          <!-- Filter Pills -->
          <div class="sitemap-pills-row-inline">
            <button id="btn-show-all" class="sitemap-pill-btn sitemap-pill-inactive" data-i18n="map_all">All</button>
            <button id="btn-show-visited" class="sitemap-pill-btn sitemap-pill-active" data-i18n="map_visited">Most Visited</button>
          </div>
        </div>

        <!-- Directory List (Scrollable) -->
        <div class="sitemap-directory-box-inline overflow-y-auto">
          <ul id="inline-directory-list" class="divide-y divide-gray-100 text-sm">
            <li class="p-4 text-center text-gray-400">Loading directory...</li>
          </ul>
        </div>
      </div>

      <!-- Right Column: Rounded White Map Frame with Soft Shadow -->
      <div id="sitemap-right-col" class="sitemap-right-col-inline">
        <div id="sitemap-card-frame" class="sitemap-card-frame-inline" title="Click to expand map">
          <!-- Close Button (Visible only when Map Modal is Open) -->
          <button
            type="button"
            id="btn-close-sitemap-modal"
            class="sitemap-modal-close-btn"
            aria-label="Close expanded map"
            title="Close map"
          >&times;</button>

          <div class="sitemap-canvas-wrapper-inline">
            
            <!-- Fixed Compass Icon on Top Right of Map Box (Image 2) -->
            <div class="sitemap-fixed-compass-inline" aria-hidden="true">
              <img src="/sdn/public/assets/map_compass.png?v=<?= time() ?>" alt="Map Compass" />
            </div>

            <!-- Google Maps Style Navigation Floating Card / HUD -->
            <div id="map-nav-hud" class="absolute top-3 left-3 right-24 md:left-4 md:right-auto md:max-w-xs bg-white/95 backdrop-blur-md rounded-xl p-3 shadow-xl border border-gray-200 transition-all duration-300 pointer-events-auto" style="z-index: 500; display: none;">
              <div class="flex items-start justify-between gap-2.5">
                <div class="flex items-start gap-2.5">
                  <div class="w-9 h-9 rounded-full bg-blue-600 text-white flex items-center justify-center shrink-0 shadow-md">
                    <!-- Walking Person Icon -->
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M13 7a2 2 0 11-4 0 2 2 0 014 0zM9 13a4 4 0 00-4 4v4h3v-3.5l1.5-1.5 2 3H15v-4.5l-2-2.5 1-2.5 2 1.5V11h2V9l-3-2.5-2.5 1.5L9.5 9A4 4 0 009 13z" />
                    </svg>
                  </div>
                  <div>
                    <div class="flex items-center gap-1.5 flex-wrap">
                      <span class="text-[10px] font-extrabold uppercase tracking-wider text-blue-700 bg-blue-50 px-2 py-0.5 rounded-full border border-blue-100">Walking Route</span>
                      <span id="nav-badge" class="text-[10px] font-bold text-gray-600 bg-gray-100 px-1.5 py-0.5 rounded">Bldg</span>
                    </div>
                    <h4 id="nav-destination-title" class="font-bold text-gray-900 text-[13px] leading-tight mt-1">Destination</h4>
                    <div class="text-xs text-gray-600 mt-1 flex items-center gap-1.5 flex-wrap">
                      <span class="text-emerald-700 font-bold" id="nav-time">~2 min</span>
                      <span class="text-gray-300">•</span>
                      <span class="font-medium text-gray-700" id="nav-dist">120 m</span>
                      <span class="text-gray-300">•</span>
                      <span class="text-gray-500 text-[11px]">Via Walkway</span>
                    </div>
                  </div>
                </div>
                <button id="btn-clear-route" class="text-gray-400 hover:text-gray-700 hover:bg-gray-100 p-1.5 rounded-lg transition-colors shrink-0" title="Clear route">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
              </div>

              <!-- Quick Start Point Selector -->
              <div class="mt-2.5 pt-2 border-t border-gray-100 flex items-center justify-between text-[11px]">
                <div class="flex items-center gap-1 text-gray-500">
                  <span class="text-gray-400">From:</span>
                  <select id="select-route-start" class="bg-gray-50 border border-gray-200 rounded px-2 py-0.5 text-gray-700 font-medium text-[11px] focus:outline-none focus:border-blue-500 cursor-pointer">
                    <option value="you_are_here">📍 You Are Here (Campus Path)</option>
                    <option value="gate">🚪 Main Entrance (Gate 2)</option>
                  </select>
                </div>
                <button id="btn-fit-route" class="text-[11px] text-blue-600 font-semibold hover:text-blue-800 transition-colors">Fit Route</button>
              </div>
            </div>

            <!-- Leaflet Map Container -->
            <div id="inline-map-container" class="absolute inset-0" style="z-index: 1;"></div>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- Pop-up Map Modal Backdrop (Outside data-aos section so position:fixed covers viewport below Navbar) -->
<div id="sitemap-modal-backdrop" class="sitemap-modal-backdrop-inline" aria-hidden="true">
  <div id="sitemap-modal-holder" class="sitemap-modal-holder-inline"></div>
</div>

<style>
  .sitemap-section-inline {
    width: 100%;
    min-height: calc(100vh - 76px);
    height: calc(100vh - 76px);
    background-color: #ffffff;
    padding: 1.5rem 2rem 2rem;
    display: flex;
    flex-direction: column;
    justify-content: center;
    box-sizing: border-box;
    overflow: hidden;
  }

  .sitemap-container-inline {
    width: 100%;
    max-width: 1120px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    height: 100%;
    max-height: 640px;
  }

  /* Centered SITE MAP Header */
  .sitemap-header-inline {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 1.35rem;
    flex-shrink: 0;
  }

  .sitemap-title-inline {
    display: inline-flex;
    align-items: center;
    font-family: 'Outfit', 'Inter', sans-serif;
    font-weight: 800;
    font-size: clamp(1.85rem, 2.8vw, 2.3rem);
    color: #000000;
    letter-spacing: 0.5px;
    line-height: 1;
    margin: 0;
  }

  .sitemap-pin-icon-inline {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 44px;
    margin: 0 -2px -5px -2px;
  }

  .sitemap-pin-icon-inline svg {
    width: 100%;
    height: 100%;
    display: block;
  }

  /* Two-Column Grid */
  .sitemap-grid-inline {
    display: grid;
    grid-template-columns: 1fr 1.05fr;
    gap: 3rem;
    align-items: stretch;
    flex: 1;
    min-height: 0;
  }

  /* Left Column */
  .sitemap-left-col-inline {
    display: flex;
    flex-direction: column;
    height: 100%;
    min-height: 0;
  }

  .sitemap-controls-inline {
    flex-shrink: 0;
    margin-bottom: 0.65rem;
  }

  .sitemap-search-input-inline {
    width: 100%;
    height: 36px;
    padding: 0 1.25rem;
    border-radius: 9999px;
    border: 1px solid #9ca3af;
    background-color: #ffffff;
    font-family: 'Inter', sans-serif;
    font-size: 0.82rem;
    color: #1f2937;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
    margin-bottom: 0.75rem;
  }

  .sitemap-search-input-inline::placeholder {
    color: #9ca3af;
    font-weight: 500;
  }

  .sitemap-search-input-inline:focus {
    border-color: #1d630b;
    box-shadow: 0 0 0 3px rgba(29, 99, 11, 0.1);
  }

  .sitemap-pills-row-inline {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    margin-bottom: 0.35rem;
  }

  .sitemap-pill-btn {
    border-radius: 9999px;
    padding: 0.4rem 1.6rem;
    font-family: 'Inter', sans-serif;
    font-size: 0.75rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;
    min-width: 82px;
    text-align: center;
  }

  .sitemap-pill-active {
    background-color: #1d630b;
    color: #ffffff;
    box-shadow: 0 6px 16px rgba(29, 99, 11, 0.28);
  }

  .sitemap-pill-inactive {
    background-color: #f9f4f4;
    color: #8c8c8c;
    box-shadow: none;
  }

  .sitemap-pill-inactive:hover {
    background-color: #f1e9e9;
    color: #4b5563;
  }

  .sitemap-directory-box-inline {
    flex: 1;
    min-height: 0;
    background-color: #ffffff;
    border-radius: 14px;
    padding: 0.25rem;
  }

  /* Right Column: Map Box / Container */
  .sitemap-right-col-inline {
    width: 100%;
    height: 100%;
    min-height: 0;
    display: flex;
  }

  .sitemap-card-frame-inline {
    position: relative;
    background-color: #ffffff;
    border-radius: 22px;
    padding: 12px;
    box-shadow: 0 12px 38px rgba(0, 0, 0, 0.14);
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    box-sizing: border-box;
    cursor: pointer;
    transition: transform 0.25s ease, box-shadow 0.25s ease;
  }

  .sitemap-right-col-inline .sitemap-card-frame-inline:hover {
    transform: translateY(-3px);
    box-shadow: 0 16px 42px rgba(0, 0, 0, 0.18);
  }

  /* Close Button inside Expanded Map Modal */
  .sitemap-modal-close-btn {
    position: absolute;
    top: 22px;
    right: 22px;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: none;
    background: #f1f5f9;
    color: #334155;
    font-size: 1.45rem;
    line-height: 1;
    display: none;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 650;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transition: background-color 0.2s, color 0.2s, transform 0.2s;
  }

  .sitemap-modal-close-btn:hover {
    background: #e2e8f0;
    color: #0f172a;
    transform: scale(1.06);
  }

  /* Pop-up Map Modal Backdrop (Same Concept as Steps/Guide UI) */
  body.sitemap-modal-lock {
    overflow: hidden !important;
  }

  body.sitemap-modal-lock #location-new {
    filter: blur(3px);
    pointer-events: none;
    user-select: none;
  }

  .sitemap-modal-backdrop-inline {
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

  .sitemap-modal-backdrop-inline.open {
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
  }

  .sitemap-modal-holder-inline {
    width: min(90vw, 1020px);
    height: calc(100vh - 125px);
    max-height: 740px;
    display: flex;
    transform: translateY(14px) scale(0.98);
    transition: transform 0.3s cubic-bezier(0.22, 1, 0.36, 1);
  }

  .sitemap-modal-backdrop-inline.open .sitemap-modal-holder-inline {
    transform: translateY(0) scale(1);
  }

  .sitemap-modal-holder-inline .sitemap-card-frame-inline {
    cursor: default;
    padding: 16px;
    border-radius: 26px;
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.35);
  }

  .sitemap-modal-holder-inline .sitemap-modal-close-btn {
    display: flex;
  }

  .sitemap-modal-holder-inline .sitemap-fixed-compass-inline {
    top: 56px;
  }

  .sitemap-canvas-wrapper-inline {
    position: relative;
    flex: 1;
    width: 100%;
    height: 100%;
    border-radius: 10px;
    overflow: hidden;
    background-color: #ebf2ed;
  }

  /* Fixed Compass on Top-Right of Map Box */
  .sitemap-fixed-compass-inline {
    position: absolute;
    top: 16px;
    right: 16px;
    width: 78px;
    height: 78px;
    z-index: 450;
    pointer-events: none;
    user-select: none;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .sitemap-fixed-compass-inline img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    display: block;
  }

  /* Make Leaflet background match the soft sage green in Image 1 */
  #inline-map-container.leaflet-container {
    background-color: #ebf2ed !important;
  }

  /* Sidebar Scrollbar */
  #location-new .overflow-y-auto::-webkit-scrollbar { width: 6px; }
  #location-new .overflow-y-auto::-webkit-scrollbar-track { background: transparent; }
  #location-new .overflow-y-auto::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
  #location-new .overflow-y-auto::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
  
  /* Leaflet tweaks */
  .leaflet-popup-content-wrapper { border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.12); }
  .leaflet-popup-content h3 { font-weight: 700; font-size: 0.95rem; color: #166534; margin: 0 0 2px 0; }
  .leaflet-popup-content p { margin: 0; color: #4b5563; font-size: 0.78rem; }

  /* Google Maps Animated Dash Flow */
  @keyframes routeDashFlow {
    from { stroke-dashoffset: 24; }
    to { stroke-dashoffset: 0; }
  }
  .leaflet-interactive.route-flow-line {
    animation: routeDashFlow 1.2s linear infinite;
  }

  /* Pulse Beacon for "You Are Here" Start Point */
  .you-are-here-marker {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
  }
  .you-are-here-dot {
    width: 14px;
    height: 14px;
    background-color: #2563eb;
    border: 2.5px solid #ffffff;
    border-radius: 50%;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.4);
    z-index: 2;
  }
  .you-are-here-pulse {
    position: absolute;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background-color: rgba(37, 99, 235, 0.45);
    animation: beaconPulse 2s ease-out infinite;
    z-index: 1;
    pointer-events: none;
  }
  @keyframes beaconPulse {
    0% { transform: scale(0.4); opacity: 1; }
    70% { transform: scale(1.35); opacity: 0; }
    100% { transform: scale(1.35); opacity: 0; }
  }

  /* Destination Teardrop Pin */
  .dest-pin-marker {
    display: flex;
    flex-direction: column;
    align-items: center;
    animation: pinDrop 0.35s cubic-bezier(0.34, 1.56, 0.64, 1) both;
  }
  @keyframes pinDrop {
    0% { transform: translateY(-14px) scale(0.7); opacity: 0; }
    100% { transform: translateY(0) scale(1); opacity: 1; }
  }

  /* Active Sidebar Item */
  .active-dir-item {
    background-color: #f0fdf4 !important;
    border-left-color: #16a34a !important;
  }

  @media (max-width: 900px) {
    .sitemap-section-inline {
      height: auto;
      min-height: auto;
      padding: 2.5rem 1.25rem 3.5rem;
    }
    .sitemap-container-inline {
      max-height: none;
    }
    .sitemap-grid-inline {
      grid-template-columns: 1fr;
      gap: 2rem;
    }
    .sitemap-left-col-inline {
      height: 360px;
    }
    .sitemap-card-frame-inline {
      height: 480px;
    }
  }
</style>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    ensureLeafletLoaded();

    function ensureLeafletLoaded() {
        const checkLeaflet = setInterval(() => {
          if (window.L) {
            clearInterval(checkLeaflet);
            initInlineMap();
          }
        }, 100);
    }

    function initInlineMap() {
      const imageBounds = [[0, 0], [1000, 700]];
      const paddedMaxBounds = [[-250, -250], [1250, 950]];
      
      const map = L.map('inline-map-container', {
          crs: L.CRS.Simple,
          zoomControl: true,
          scrollWheelZoom: true,
          zoomSnap: 0.05,
          zoomDelta: 0.25,
          minZoom: -2.5,
          maxBounds: paddedMaxBounds,
          maxBoundsViscosity: 0.9,
          maxZoom: 3
      });

      const mapImage = L.imageOverlay('/sdn/public/assets/MMWGH-site-map-clean.png?v=<?= time() ?>', imageBounds).addTo(map);
      
      mapImage.on('error', function() {
          console.warn("MMWGH-site-map-clean.png not found, falling back to MMWGH-site-map.png.");
          L.imageOverlay('/sdn/public/assets/MMWGH-site-map.png', imageBounds).addTo(map);
      });

      function fitWholeSiteMap(animate = false) {
          map.invalidateSize();
          map.fitBounds(imageBounds, { padding: [8, 8], animate: animate });
      }

      // Fit the entire site map inside the box on load, resize, and when scrolled into view
      fitWholeSiteMap(false);
      setTimeout(() => {
          fitWholeSiteMap(false);
          map.setMinZoom(map.getZoom() - 0.15);
      }, 150);
      setTimeout(() => {
          fitWholeSiteMap(false);
      }, 500);
      window.addEventListener('resize', () => fitWholeSiteMap(false));

      const locSection = document.getElementById('location-new');
      if (locSection && 'IntersectionObserver' in window) {
          const io = new IntersectionObserver((entries) => {
              entries.forEach(entry => {
                  if (entry.isIntersecting && !activeDestinationId) {
                      setTimeout(() => fitWholeSiteMap(false), 50);
                  }
              });
          }, { threshold: 0.2 });
          io.observe(locSection);
      }

      let markers = [];
      let locationsData = [];
      let currentFilter = 'ALL';
      let activeDestinationId = null;
      let currentStartPointKey = 'you_are_here';
      let currentRouteLayers = [];
      let currentStartMarker = null;
      let currentDestMarker = null;
      const mostVisitedIds = [16, 24, 37, 5, 1, 14, 25]; // Hardcoded top visited IDs

      // --- HOSPITAL WALKWAY NAVIGATION GRAPH ---
      const nodes = {
          'gate':          { y: 102, x: 238 },
          'road_entry':    { y: 170, x: 220 },
          'you_are_here':  { y: 320, x: 200 },
          'spine_south':   { y: 350, x: 315 },
          'spine_mid':     { y: 460, x: 315 },
          'crossroads':    { y: 535, x: 315 },
          'spine_north':   { y: 620, x: 315 },
          'wards_entry':   { y: 720, x: 315 },
          'north_curve_1': { y: 775, x: 300 },
          'north_curve_2': { y: 840, x: 260 },
          'north_curve_3': { y: 900, x: 230 },
          'north_end':     { y: 952, x: 185 },
          'east_entry':    { y: 200, x: 380 },
          'east_1':        { y: 250, x: 380 },
          'east_2':        { y: 277, x: 450 },
          'east_3':        { y: 331, x: 450 },
          'east_4':        { y: 378, x: 450 },
          'east_5':        { y: 416, x: 450 },
          'east_6':        { y: 446, x: 450 },
          'east_crossway': { y: 535, x: 450 },
          'east_stp':      { y: 583, x: 470 },
          'pav_lower':     { y: 620, x: 390 },
          'pav_mid':       { y: 662, x: 370 },
          'pav_upper':     { y: 715, x: 380 },
          'pav_far_east':  { y: 775, x: 412 },
          'west_opu':      { y: 245, x: 150 },
          'west_1':        { y: 390, x: 140 },
          'west_2':        { y: 422, x: 140 },
          'west_3':        { y: 476, x: 140 },
          'west_crossway': { y: 535, x: 140 },
          'west_4':        { y: 591, x: 140 },
          'west_5':        { y: 658, x: 170 },
          'west_6':        { y: 702, x: 170 },
          'west_7':        { y: 723, x: 140 },
          'west_north':    { y: 840, x: 140 }
      };

      // Building closest walkway connection mapping
      const bldgAttach = {
          1: 'gate',
          2: 'east_1',
          3: 'spine_south',
          4: 'spine_south',
          5: 'spine_mid',
          6: 'pav_lower',
          7: 'pav_lower',
          8: 'pav_mid',
          9: 'pav_mid',
          10: 'pav_upper',
          11: 'north_curve_1',
          12: 'pav_far_east',
          13: 'north_curve_1',
          14: 'north_curve_2',
          15: 'north_end',
          16: 'east_entry',
          17: 'north_curve_1',
          18: 'east_2',
          19: 'east_3',
          20: 'east_3',
          21: 'east_4',
          22: 'east_5',
          23: 'east_5',
          24: 'east_6',
          25: 'east_6',
          26: 'east_crossway',
          27: 'east_stp',
          28: 'west_opu',
          30: 'west_2',
          31: 'west_3',
          32: 'crossroads',
          33: 'west_6',
          34: 'west_5',
          35: 'west_7',
          36: 'west_4',
          37: 'road_entry',
          38: 'north_curve_3',
          39: 'pav_mid',
          40: 'west_1',
          41: 'west_2'
      };

      const edges = [
          ['gate', 'road_entry'],
          ['road_entry', 'you_are_here'],
          ['road_entry', 'west_opu'],
          ['road_entry', 'east_entry'],
          ['you_are_here', 'spine_south'],
          ['you_are_here', 'west_1'],
          ['spine_south', 'spine_mid'],
          ['spine_mid', 'crossroads'],
          ['crossroads', 'spine_north'],
          ['spine_north', 'wards_entry'],
          ['wards_entry', 'north_curve_1'],
          ['north_curve_1', 'north_curve_2'],
          ['north_curve_2', 'north_curve_3'],
          ['north_curve_3', 'north_end'],
          ['crossroads', 'east_crossway'],
          ['east_crossway', 'east_stp'],
          ['east_crossway', 'east_6'],
          ['east_6', 'east_5'],
          ['east_5', 'east_4'],
          ['east_4', 'east_3'],
          ['east_3', 'east_2'],
          ['east_2', 'east_1'],
          ['east_1', 'east_entry'],
          ['spine_south', 'east_1'],
          ['spine_north', 'pav_lower'],
          ['pav_lower', 'pav_mid'],
          ['pav_mid', 'pav_upper'],
          ['wards_entry', 'pav_upper'],
          ['pav_upper', 'pav_far_east'],
          ['west_opu', 'west_1'],
          ['west_1', 'west_2'],
          ['west_2', 'west_3'],
          ['west_3', 'west_crossway'],
          ['crossroads', 'west_crossway'],
          ['west_crossway', 'west_4'],
          ['west_4', 'west_5'],
          ['west_5', 'west_6'],
          ['west_6', 'west_7'],
          ['west_7', 'west_north'],
          ['west_north', 'north_end']
      ];

      // Build graph adjacency list
      const adj = {};
      Object.keys(nodes).forEach(k => adj[k] = []);
      edges.forEach(([u, v]) => {
          const p1 = nodes[u];
          const p2 = nodes[v];
          const weight = Math.hypot(p1.x - p2.x, p1.y - p2.y);
          adj[u].push({ node: v, weight });
          adj[v].push({ node: u, weight });
      });

      // Dijkstra shortest path
      function dijkstra(start, target) {
          const dist = {};
          const prev = {};
          const q = new Set(Object.keys(nodes));
          Object.keys(nodes).forEach(k => dist[k] = Infinity);
          dist[start] = 0;

          while (q.size > 0) {
              let minNode = null;
              let minDist = Infinity;
              q.forEach(n => {
                  if (dist[n] < minDist) {
                      minDist = dist[n];
                      minNode = n;
                  }
              });
              if (!minNode || minNode === target) break;
              q.delete(minNode);

              adj[minNode].forEach(neighbor => {
                  if (!q.has(neighbor.node)) return;
                  const alt = dist[minNode] + neighbor.weight;
                  if (alt < dist[neighbor.node]) {
                      dist[neighbor.node] = alt;
                      prev[neighbor.node] = minNode;
                  }
              });
          }

          const path = [];
          let curr = target;
          while (curr) {
              path.unshift(curr);
              curr = prev[curr];
          }
          return path;
      }

      // --- CLEAR PREVIOUS ROUTE LAYERS ---
      function clearRouteLayers() {
          currentRouteLayers.forEach(l => map.removeLayer(l));
          currentRouteLayers = [];
          if (currentStartMarker) {
              map.removeLayer(currentStartMarker);
              currentStartMarker = null;
          }
          if (currentDestMarker) {
              map.removeLayer(currentDestMarker);
              currentDestMarker = null;
          }
      }

      // --- DRAW GOOGLE MAPS STYLE DIRECTIONAL ROUTE ---
      function drawRouteTo(loc) {
          if (!loc) return;
          activeDestinationId = loc.id;
          clearRouteLayers();

          const startKey = currentStartPointKey;
          const targetNode = bldgAttach[loc.id] || 'crossroads';
          const pathKeys = dijkstra(startKey, targetNode);

          const latLngs = [];
          pathKeys.forEach(k => {
              latLngs.push([nodes[k].y, nodes[k].x]);
          });
          // Connect to the exact building location
          latLngs.push([loc.y_coordinate, loc.x_coordinate]);

          // Calculate estimated distance and walking time
          let totalUnits = 0;
          for (let i = 0; i < latLngs.length - 1; i++) {
              totalUnits += Math.hypot(latLngs[i+1][1] - latLngs[i][1], latLngs[i+1][0] - latLngs[i][0]);
          }
          const meters = Math.round(totalUnits * 0.65);
          const minutes = Math.max(1, Math.round(meters / 65));

          // 1. Shadow / Outer Border
          const routeShadow = L.polyline(latLngs, {
              color: '#1e3a8a',
              weight: 8,
              opacity: 0.35,
              lineCap: 'round',
              lineJoin: 'round'
          }).addTo(map);

          // 2. Primary Google Maps Blue Route Line
          const routeMain = L.polyline(latLngs, {
              color: '#2563eb',
              weight: 5,
              opacity: 0.95,
              lineCap: 'round',
              lineJoin: 'round'
          }).addTo(map);

          // 3. Animated Forward-Flowing Dashes
          const routeFlow = L.polyline(latLngs, {
              color: '#dbeafe',
              weight: 2.2,
              opacity: 0.95,
              dashArray: '6, 12',
              className: 'route-flow-line'
          }).addTo(map);

          currentRouteLayers = [routeShadow, routeMain, routeFlow];

          // 4. Start Marker ("You Are Here" pulsing beacon)
          const startPos = nodes[startKey];
          currentStartMarker = L.marker([startPos.y, startPos.x], {
              icon: L.divIcon({
                  className: 'custom-start-marker',
                  html: `
                    <div class="you-are-here-marker" title="Starting point: ${startKey === 'gate' ? 'Gate 2 Entrance' : 'You Are Here'}">
                      <div class="you-are-here-pulse"></div>
                      <div class="you-are-here-dot"></div>
                    </div>
                  `,
                  iconSize: [32, 32],
                  iconAnchor: [16, 16]
              }),
              zIndexOffset: 1000
          }).addTo(map);

          // 5. Destination Pin
          currentDestMarker = L.marker([loc.y_coordinate, loc.x_coordinate], {
              icon: L.divIcon({
                  className: 'custom-dest-pin',
                  html: `
                    <div class="dest-pin-marker">
                      <div style="background:#15803d; color:white; font-size:11px; font-weight:800; padding:3px 8px; border-radius:12px; box-shadow:0 3px 8px rgba(0,0,0,0.3); white-space:nowrap; border:1.5px solid #ffffff; display:flex; align-items:center; gap:3px;">
                        <span>📍</span>
                        <span>${loc.id > 99 ? '' : loc.id + ' • '}${loc.name.length > 22 ? loc.name.substring(0, 20) + '...' : loc.name}</span>
                      </div>
                      <div style="width:0; height:0; border-left:5px solid transparent; border-right:5px solid transparent; border-top:6px solid #15803d; margin-top:-1px;"></div>
                    </div>
                  `,
                  iconSize: [140, 36],
                  iconAnchor: [70, 36]
              }),
              zIndexOffset: 1100
          }).addTo(map);

          // 6. Update HUD Card
          const hud = document.getElementById('map-nav-hud');
          document.getElementById('nav-badge').textContent = `Bldg ${loc.id > 99 ? '•' : loc.id}`;
          document.getElementById('nav-destination-title').textContent = loc.name;
          document.getElementById('nav-time').textContent = `~${minutes} min`;
          document.getElementById('nav-dist').textContent = `${meters} meters`;
          hud.style.display = 'block';

          // 7. Highlight in Sidebar
          highlightSidebarItem(loc.id);

          // 8. Keep the whole site map visible so the user can see the route across the full campus
          fitWholeSiteMap(true);
      }

      function clearRoute() {
          clearRouteLayers();
          activeDestinationId = null;
          document.getElementById('map-nav-hud').style.display = 'none';
          document.querySelectorAll('#inline-directory-list li').forEach(li => {
              li.classList.remove('active-dir-item');
          });
          fitWholeSiteMap(true);
      }

      function highlightSidebarItem(id) {
          document.querySelectorAll('#inline-directory-list li').forEach(li => {
              if (parseInt(li.dataset.locId) === id) {
                  li.classList.add('active-dir-item');
                  li.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
              } else {
                  li.classList.remove('active-dir-item');
              }
          });
      }

      // HUD Button Events
      document.getElementById('btn-clear-route').addEventListener('click', clearRoute);
      document.getElementById('btn-fit-route').addEventListener('click', () => {
          if (currentRouteLayers.length > 1) {
              map.fitBounds(currentRouteLayers[1].getBounds(), { padding: [60, 60], maxZoom: 0.65, animate: true, duration: 0.6 });
          }
      });
      document.getElementById('select-route-start').addEventListener('change', (e) => {
          currentStartPointKey = e.target.value;
          if (activeDestinationId) {
              const loc = locationsData.find(l => l.id === activeDestinationId);
              if (loc) drawRouteTo(loc);
          }
      });

      // --- FETCH LOCATIONS ---
      async function fetchLocations() {
          try {
              const response = await fetch('/sdn/api/locations.php');
              const json = await response.json();
              if (json.success && json.data) {
                  locationsData = json.data;
                  renderSidebar(locationsData);
                  plotMarkers(locationsData);
              }
          } catch (error) {
              console.error("Map data error:", error);
          }
      }

      function plotMarkers(data) {
          markers.forEach(m => map.removeLayer(m.marker));
          markers = [];
          data.forEach(loc => {
              const latLng = [loc.y_coordinate, loc.x_coordinate];
              const customIcon = L.divIcon({
                  className: 'custom-number-marker',
                  html: `<div style="background-color: transparent; width: 28px; height: 28px; border-radius: 50%; cursor: pointer;" title="${loc.name} (Click for directions)"></div>`,
                  iconSize: [28, 28],
                  iconAnchor: [14, 14]
              });

              const marker = L.marker(latLng, { icon: customIcon, draggable: false }).addTo(map);
              
              marker.on('click', () => {
                  drawRouteTo(loc);
              });

              markers.push({ id: loc.id, marker: marker, latLng: latLng });
          });
      }

      function renderSidebar(data) {
          const listEl = document.getElementById('inline-directory-list');
          listEl.innerHTML = '';
          if (data.length === 0) return;
          
          data.forEach(loc => {
              const li = document.createElement('li');
              li.dataset.locId = loc.id;
              li.className = 'p-3 hover:bg-green-50 cursor-pointer transition-colors border-l-4 border-transparent hover:border-green-600 flex items-center justify-between gap-2';
              if (activeDestinationId === loc.id) {
                  li.classList.add('active-dir-item');
              }
              li.innerHTML = `
                  <div class="flex items-center gap-2.5 overflow-hidden">
                      <div class="bg-gray-100 text-green-800 font-bold rounded-full w-7 h-7 flex items-center justify-center shrink-0 text-xs shadow-sm">${loc.id > 99 ? '•' : loc.id}</div>
                      <div class="truncate">
                          <h4 class="font-bold text-gray-800 text-[13px] leading-tight truncate">${loc.name}</h4>
                          <span class="text-[11px] text-gray-400">Click for directions</span>
                      </div>
                  </div>
                  <div class="text-blue-600 text-xs shrink-0 flex items-center gap-0.5 font-semibold opacity-70 hover:opacity-100">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                  </div>`;
              li.addEventListener('click', () => {
                  drawRouteTo(loc);
              });
              listEl.appendChild(li);
          });
      }

      document.getElementById('inline-search').addEventListener('input', (e) => {
          const term = e.target.value.toLowerCase();
          let filtered = locationsData.filter(loc => 
              loc.name.toLowerCase().includes(term) || (loc.description && loc.description.toLowerCase().includes(term))
          );
          if (currentFilter === 'VISITED') {
              filtered = filtered.filter(loc => mostVisitedIds.includes(loc.id));
          }
          renderSidebar(filtered);
      });

      document.getElementById('btn-show-all').addEventListener('click', () => {
          currentFilter = 'ALL';
          const btnAll = document.getElementById('btn-show-all');
          const btnVisited = document.getElementById('btn-show-visited');
          
          btnAll.className = 'sitemap-pill-btn sitemap-pill-active';
          btnVisited.className = 'sitemap-pill-btn sitemap-pill-inactive';
          
          document.getElementById('inline-search').dispatchEvent(new Event('input'));
      });

      document.getElementById('btn-show-visited').addEventListener('click', () => {
          currentFilter = 'VISITED';
          const btnAll = document.getElementById('btn-show-all');
          const btnVisited = document.getElementById('btn-show-visited');
          
          btnVisited.className = 'sitemap-pill-btn sitemap-pill-active';
          btnAll.className = 'sitemap-pill-btn sitemap-pill-inactive';
          
          document.getElementById('inline-search').dispatchEvent(new Event('input'));
      });

      // --- CLICKABLE MAP POP-UP MODAL (Same Concept as Steps/Guide UI) ---
      let isSitemapModalOpen = false;
      const rightCol = document.getElementById('sitemap-right-col');
      const cardFrame = document.getElementById('sitemap-card-frame');
      const modalBackdrop = document.getElementById('sitemap-modal-backdrop');
      const modalHolder = document.getElementById('sitemap-modal-holder');
      const closeModalBtn = document.getElementById('btn-close-sitemap-modal');

      function preventPageScrollOnSitemapModal(e) {
          if (!isSitemapModalOpen) return;
          // Allow wheel zoom inside the Leaflet map container while blocking page scroll
          if (e.target && e.target.closest && e.target.closest('#inline-map-container')) {
              return;
          }
          e.preventDefault();
      }

      function openSitemapModal() {
          if (isSitemapModalOpen) return;
          isSitemapModalOpen = true;
          modalHolder.appendChild(cardFrame);
          cardFrame.removeAttribute('title');
          modalBackdrop.classList.add('open');
          modalBackdrop.setAttribute('aria-hidden', 'false');
          document.body.classList.add('sitemap-modal-lock');
          window.addEventListener('wheel', preventPageScrollOnSitemapModal, { passive: false });
          window.addEventListener('touchmove', preventPageScrollOnSitemapModal, { passive: false });
          setTimeout(() => {
              fitWholeSiteMap(false);
          }, 60);
          setTimeout(() => {
              fitWholeSiteMap(false);
          }, 320);
      }

      function closeSitemapModal(e) {
          if (e) e.stopPropagation();
          if (!isSitemapModalOpen) return;
          isSitemapModalOpen = false;
          modalBackdrop.classList.remove('open');
          modalBackdrop.setAttribute('aria-hidden', 'true');
          rightCol.appendChild(cardFrame);
          cardFrame.setAttribute('title', 'Click to expand map');
          document.body.classList.remove('sitemap-modal-lock');
          window.removeEventListener('wheel', preventPageScrollOnSitemapModal);
          window.removeEventListener('touchmove', preventPageScrollOnSitemapModal);
          setTimeout(() => {
              fitWholeSiteMap(false);
          }, 60);
      }

      cardFrame.addEventListener('click', (e) => {
          if (isSitemapModalOpen) return;
          // Don't trigger modal open if clicking HUD clear/fit buttons or zoom controls directly
          if (e.target && e.target.closest && e.target.closest('#map-nav-hud, .leaflet-control-zoom, #btn-close-sitemap-modal')) {
              return;
          }
          openSitemapModal();
      });

      closeModalBtn.addEventListener('click', closeSitemapModal);

      document.addEventListener('keydown', (e) => {
          if (!isSitemapModalOpen) return;
          if (['ArrowUp', 'ArrowDown', 'PageUp', 'PageDown', 'Home', 'End', ' '].includes(e.key)) {
              e.preventDefault();
          }
      });

      fetchLocations();
    }
  });
</script>
