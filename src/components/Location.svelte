<script>
  import { onMount } from 'svelte';

  let mapContainer;
  let map;
  let locations = [];
  let filteredLocations = [];
  let searchQuery = '';
  let activeFilter = 'all';

  // Navigation State
  let activeDestination = null;
  let currentStartPoint = 'you_are_here';
  let routeLayers = [];
  let startMarker = null;
  let destMarker = null;
  let routeMeters = 0;
  let routeMinutes = 0;

  // Walkway graph strictly on outdoor pathways & roads
  const nodes = {
    // Entrance / South Main Road
    'gate':           { y: 102, x: 205 },
    'road_entry':     { y: 155, x: 205 },
    'canteen_spur':   { y: 160, x: 220 },
    'main_road_east': { y: 140, x: 370 },
    'main_road_west': { y: 155, x: 155 },

    // South Plaza / You Are Here Hub
    'plaza_south':    { y: 240, x: 205 },
    'plaza_east':     { y: 240, x: 280 },
    'you_are_here':   { y: 320, x: 200 },
    'yah_east':       { y: 320, x: 240 },
    'yah_west':       { y: 320, x: 140 },

    // Central Walkway Promenade
    'corridor_s1':    { y: 380, x: 240 },
    'corridor_s2':    { y: 450, x: 250 },
    'corridor_cross': { y: 495, x: 275 },
    'corridor_mid':   { y: 570, x: 275 },
    'corridor_ot':    { y: 660, x: 275 },
    'corridor_n1':    { y: 735, x: 275 },

    // East Breezeway
    'breeze_mid':     { y: 495, x: 375 },
    'breeze_east':    { y: 495, x: 475 },

    // West Road System
    'west_opu':       { y: 235, x: 155 },
    'west_1':         { y: 320, x: 140 },
    'west_2':         { y: 422, x: 140 },
    'west_3':         { y: 476, x: 140 },
    'west_cross':     { y: 495, x: 155 },
    'west_4':         { y: 580, x: 155 },
    'west_5':         { y: 660, x: 155 },
    'west_6':         { y: 702, x: 175 },
    'west_7':         { y: 735, x: 175 },
    'west_north':     { y: 830, x: 180 },
    'west_top':       { y: 900, x: 180 },

    // East Road System
    'east_entry':     { y: 200, x: 425 },
    'east_1':         { y: 240, x: 445 },
    'east_2':         { y: 280, x: 475 },
    'east_3':         { y: 335, x: 460 },
    'east_4':         { y: 375, x: 460 },
    'east_5':         { y: 415, x: 455 },
    'east_6':         { y: 450, x: 455 },
    'east_crossway':  { y: 535, x: 475 },
    'east_stp':       { y: 585, x: 475 },
    'east_pav_entry': { y: 620, x: 475 },
    'east_pav_mid':   { y: 685, x: 475 },
    'east_pav_upper': { y: 735, x: 475 },
    'east_pav_top':   { y: 780, x: 475 },

    // Northern Campus Loop & Facilities
    'north_junc_1':   { y: 765, x: 260 },
    'north_junc_2':   { y: 815, x: 260 },
    'north_tb_dots':  { y: 870, x: 235 },
    'north_waste':    { y: 910, x: 255 },
    'north_top_road': { y: 930, x: 255 },
    'north_end':      { y: 952, x: 215 }
  };

  const bldgAttach = {
    1: 'gate', 2: 'east_1', 3: 'yah_east', 4: 'corridor_s1', 5: 'corridor_s2',
    6: 'corridor_mid', 7: 'east_pav_entry', 8: 'east_pav_mid', 9: 'east_pav_mid', 10: 'east_pav_upper',
    11: 'north_junc_1', 12: 'east_pav_top', 13: 'north_junc_2', 14: 'north_tb_dots',
    15: 'north_end', 16: 'east_entry', 17: 'west_north', 18: 'east_2', 19: 'east_3',
    20: 'east_3', 21: 'east_4', 22: 'east_5', 23: 'east_5', 24: 'breeze_mid',
    25: 'east_6', 26: 'east_crossway', 27: 'east_stp', 28: 'west_opu', 30: 'west_2',
    31: 'west_3', 32: 'corridor_mid', 33: 'west_6', 34: 'corridor_ot', 35: 'west_7',
    36: 'west_4', 37: 'canteen_spur', 38: 'north_waste', 39: 'corridor_ot', 40: 'corridor_s1',
    41: 'west_2'
  };

  const edges = [
    // South / Entrance
    ['gate', 'road_entry'], ['road_entry', 'main_road_east'], ['road_entry', 'main_road_west'],
    ['road_entry', 'plaza_south'], ['road_entry', 'canteen_spur'],

    // South Plaza
    ['plaza_south', 'road_entry'], ['plaza_south', 'you_are_here'], ['plaza_south', 'yah_east'],

    // East Road
    ['main_road_east', 'east_entry'], ['east_entry', 'east_1'], ['east_1', 'east_2'],
    ['east_2', 'east_3'], ['east_3', 'east_4'], ['east_4', 'east_5'], ['east_5', 'east_6'],
    ['east_6', 'breeze_east'], ['breeze_east', 'east_crossway'], ['east_crossway', 'east_stp'],
    ['east_crossway', 'east_pav_entry'], ['east_pav_entry', 'east_pav_mid'],
    ['east_pav_mid', 'east_pav_upper'], ['east_pav_upper', 'east_pav_top'],

    // East Breezeway
    ['breeze_east', 'breeze_mid'], ['breeze_mid', 'corridor_cross'],

    // West Road
    ['main_road_west', 'west_opu'], ['west_opu', 'west_1'], ['west_1', 'west_2'],
    ['west_2', 'west_3'], ['west_3', 'west_cross'], ['west_cross', 'west_4'],
    ['west_4', 'west_5'], ['west_5', 'west_6'], ['west_6', 'west_7'],
    ['west_7', 'west_north'], ['west_north', 'west_top'], ['west_top', 'north_end'],

    // Cross Links
    ['west_1', 'you_are_here'], ['west_cross', 'corridor_s2'], ['west_7', 'corridor_n1'],

    // Central Walkway Corridor
    ['you_are_here', 'yah_east'], ['yah_east', 'corridor_s1'], ['corridor_s1', 'corridor_s2'],
    ['corridor_s2', 'corridor_cross'], ['corridor_cross', 'corridor_mid'],
    ['corridor_mid', 'corridor_ot'], ['corridor_ot', 'corridor_n1'],

    // North Campus Network
    ['corridor_n1', 'north_junc_1'], ['north_junc_1', 'north_junc_2'],
    ['north_junc_2', 'north_tb_dots'], ['north_tb_dots', 'north_waste'],
    ['north_waste', 'north_top_road'], ['north_top_road', 'north_end']
  ];

  const adj = {};
  Object.keys(nodes).forEach(k => adj[k] = []);
  edges.forEach(([u, v]) => {
    const p1 = nodes[u];
    const p2 = nodes[v];
    const w = Math.hypot(p1.x - p2.x, p1.y - p2.y);
    adj[u].push({ node: v, weight: w });
    adj[v].push({ node: u, weight: w });
  });

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

  const bounds = [[0, 0], [1000, 700]];

  onMount(async () => {
    if (!window.L) {
      const link = document.createElement('link');
      link.rel = 'stylesheet';
      link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
      document.head.appendChild(link);

      const script = document.createElement('script');
      script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
      script.onload = initMap;
      document.head.appendChild(script);
    } else {
      initMap();
    }
  });

  async function initMap() {
    map = window.L.map(mapContainer, {
      crs: window.L.CRS.Simple,
      zoomControl: true,
      scrollWheelZoom: true,
      maxBounds: [[-200, -200], [1200, 900]],
      maxBoundsViscosity: 0.9,
      maxZoom: 3
    });

    const mapImage = window.L.imageOverlay('/sdn/public/assets/MMWGH-site-map.png', bounds).addTo(map);
    map.fitBounds(bounds);

    setTimeout(() => {
      map.setMinZoom(map.getZoom());
    }, 100);

    try {
      const response = await fetch('/sdn/api/locations.php');
      const json = await response.json();
      if (json.success && json.data) {
        locations = json.data;
        filteredLocations = locations;
        plotMarkers();
      }
    } catch (error) {
      console.error("Failed to load map data:", error);
    }
  }

  function plotMarkers() {
    locations.forEach(loc => {
      const latLng = [loc.y_coordinate, loc.x_coordinate];
      const customIcon = window.L.divIcon({
        className: 'custom-number-marker',
        html: `<div style="background-color: transparent; width: 28px; height: 28px; border-radius: 50%; cursor: pointer;" title="${loc.name}"></div>`,
        iconSize: [28, 28],
        iconAnchor: [14, 14]
      });

      const marker = window.L.marker(latLng, { icon: customIcon, draggable: false }).addTo(map);
      marker.on('click', () => {
        drawRouteTo(loc);
      });
      loc.marker = marker;
      loc.latLng = latLng;
    });
  }

  function clearRouteLayers() {
    if (map) {
      routeLayers.forEach(l => map.removeLayer(l));
      if (startMarker) map.removeLayer(startMarker);
      if (destMarker) map.removeLayer(destMarker);
    }
    routeLayers = [];
    startMarker = null;
    destMarker = null;
  }

  function drawRouteTo(loc) {
    if (!loc || !map) return;
    activeDestination = loc;
    clearRouteLayers();

    const startKey = currentStartPoint;
    const targetNode = bldgAttach[loc.id] || 'corridor_cross';
    const pathKeys = dijkstra(startKey, targetNode);

    const latLngs = [];
    pathKeys.forEach(k => {
      latLngs.push([nodes[k].y, nodes[k].x]);
    });
    latLngs.push([loc.y_coordinate, loc.x_coordinate]);

    let totalUnits = 0;
    for (let i = 0; i < latLngs.length - 1; i++) {
      totalUnits += Math.hypot(latLngs[i+1][1] - latLngs[i][1], latLngs[i+1][0] - latLngs[i][0]);
    }
    routeMeters = Math.round(totalUnits * 0.65);
    routeMinutes = Math.max(1, Math.round(routeMeters / 65));

    // Multi-layer Google Maps Route Line
    const shadow = window.L.polyline(latLngs, {
      color: '#1e3a8a', weight: 8, opacity: 0.35, lineCap: 'round', lineJoin: 'round'
    }).addTo(map);

    const main = window.L.polyline(latLngs, {
      color: '#2563eb', weight: 5, opacity: 0.95, lineCap: 'round', lineJoin: 'round'
    }).addTo(map);

    const flow = window.L.polyline(latLngs, {
      color: '#dbeafe', weight: 2.2, opacity: 0.95, dashArray: '6, 12', className: 'route-flow-line'
    }).addTo(map);

    routeLayers = [shadow, main, flow];

    // Start Beacon Marker
    const startPos = nodes[startKey];
    startMarker = window.L.marker([startPos.y, startPos.x], {
      icon: window.L.divIcon({
        className: 'custom-start-marker',
        html: `
          <div class="you-are-here-marker">
            <div class="you-are-here-pulse"></div>
            <div class="you-are-here-dot"></div>
          </div>
        `,
        iconSize: [32, 32],
        iconAnchor: [16, 16]
      }),
      zIndexOffset: 1000
    }).addTo(map);

    // Destination Pin Marker
    destMarker = window.L.marker([loc.y_coordinate, loc.x_coordinate], {
      icon: window.L.divIcon({
        className: 'custom-dest-pin',
        html: `
          <div class="dest-pin-marker">
            <div style="background:#15803d; color:white; font-size:11px; font-weight:800; padding:3px 8px; border-radius:12px; box-shadow:0 3px 8px rgba(0,0,0,0.3); white-space:nowrap; border:1.5px solid #ffffff; display:flex; align-items:center; gap:3px;">
              <span>📍</span>
              <span>${loc.id > 99 ? '' : loc.id + ' • '}${loc.name.length > 20 ? loc.name.substring(0, 18) + '...' : loc.name}</span>
            </div>
            <div style="width:0; height:0; border-left:5px solid transparent; border-right:5px solid transparent; border-top:6px solid #15803d; margin-top:-1px;"></div>
          </div>
        `,
        iconSize: [140, 36],
        iconAnchor: [70, 36]
      }),
      zIndexOffset: 1100
    }).addTo(map);

    // Overhauled zoom: gently fit route bounds without extreme zoom
    map.fitBounds(main.getBounds(), {
      padding: [60, 60],
      maxZoom: 0.65,
      animate: true,
      duration: 0.8
    });
  }

  function clearRoute() {
    clearRouteLayers();
    activeDestination = null;
    if (map) {
      map.fitBounds(bounds, { animate: true, duration: 0.6 });
    }
  }

  function handleSearch(e) {
    searchQuery = e.target.value.toLowerCase();
    filterLocations();
  }

  function setFilter(filter) {
    activeFilter = filter;
    filterLocations();
  }

  const mostVisitedIds = [16, 24, 37, 5, 1, 14, 25];

  function filterLocations() {
    filteredLocations = locations.filter(loc => {
      const matchesSearch = loc.name.toLowerCase().includes(searchQuery) || (loc.description && loc.description.toLowerCase().includes(searchQuery));
      if (activeFilter === 'most-visited') {
        return matchesSearch && mostVisitedIds.includes(loc.id);
      }
      return matchesSearch;
    });
  }
</script>

<section id="location" class="location-section">
  <div class="split-container">
    
    <!-- Left Column: Controls & List -->
    <div class="left-col">
      <!-- Search Bar -->
      <div class="search-bar">
        <input type="text" placeholder="Search departments, wards, buildings..." on:input={handleSearch} />
      </div>
      
      <!-- Filter Buttons -->
      <div class="filter-buttons">
        <button class="filter-btn {activeFilter === 'all' ? 'active' : ''}" on:click={() => setFilter('all')}>All</button>
        <button class="filter-btn {activeFilter === 'most-visited' ? 'active' : ''}" on:click={() => setFilter('most-visited')}>Most Visited</button>
      </div>

      <!-- List Container -->
      <div class="list-container sidebar-scroll">
        {#if filteredLocations.length === 0}
          <div class="empty-state">No locations found.</div>
        {/if}
        {#each filteredLocations as loc}
          <!-- svelte-ignore a11y-click-events-have-key-events -->
          <!-- svelte-ignore a11y-no-static-element-interactions -->
          <div class="list-item {activeDestination && activeDestination.id === loc.id ? 'active-item' : ''}" on:click={() => drawRouteTo(loc)}>
            <div class="item-id">{loc.id > 99 ? '•' : loc.id}</div>
            <div class="item-text">
              <h4>{loc.name}</h4>
              <p>Click for walking route</p>
            </div>
            <span class="arrow-indicator">➔</span>
          </div>
        {/each}
      </div>
    </div>

    <!-- Right Column: Map Card -->
    <div class="right-col">
      <div class="map-card">
        
        <!-- Google Maps Navigation HUD -->
        {#if activeDestination}
          <div class="nav-hud-card">
            <div class="nav-hud-header">
              <div class="walk-badge">
                <svg class="walk-svg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M13 7a2 2 0 11-4 0 2 2 0 014 0zM9 13a4 4 0 00-4 4v4h3v-3.5l1.5-1.5 2 3H15v-4.5l-2-2.5 1-2.5 2 1.5V11h2V9l-3-2.5-2.5 1.5L9.5 9A4 4 0 009 13z" />
                </svg>
              </div>
              <div class="nav-hud-details">
                <div class="nav-chip">Walking Route • Bldg {activeDestination.id}</div>
                <h4 class="nav-dest-title">{activeDestination.name}</h4>
                <div class="nav-meta">
                  <span class="nav-time">~{routeMinutes} min</span>
                  <span>•</span>
                  <span>{routeMeters} meters</span>
                  <span>•</span>
                  <span>Via Walkway</span>
                </div>
              </div>
              <button class="nav-clear-btn" on:click={clearRoute} title="Clear route">✕</button>
            </div>
            <div class="nav-hud-footer">
              <div class="origin-selector">
                <span>From:</span>
                <select bind:value={currentStartPoint} on:change={() => drawRouteTo(activeDestination)}>
                  <option value="you_are_here">📍 You Are Here</option>
                  <option value="gate">🚪 Main Entrance (Gate 2)</option>
                </select>
              </div>
              <button class="btn-fit" on:click={() => { if (routeLayers.length > 1) map.fitBounds(routeLayers[1].getBounds(), { padding: [60, 60], maxZoom: 0.65, animate: true }); }}>Fit Route</button>
            </div>
          </div>
        {/if}

        <!-- Map Container -->
        <div class="map-wrapper" bind:this={mapContainer}></div>
        
      </div>
    </div>
    
  </div>
</section>

<style>
  .location-section {
    padding: 3rem 1.5rem;
    background: #f4f7f4;
    display: flex;
    justify-content: center;
    align-items: center;
  }

  .split-container {
    display: grid;
    grid-template-columns: 320px 1fr;
    gap: 1.5rem;
    width: 100%;
    max-width: 1200px;
    height: 600px;
  }

  .left-col {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    height: 100%;
    background: #ffffff;
    border-radius: 12px;
    padding: 1.2rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.04);
    border: 1px solid #e5e7eb;
  }

  .search-bar input {
    width: 100%;
    padding: 0.6rem 1.2rem;
    border-radius: 20px;
    border: 1px solid #d1d5db;
    font-size: 0.85rem;
    outline: none;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
  }
  .search-bar input:focus {
    border-color: #15803d;
  }

  .filter-buttons {
    display: flex;
    gap: 0.5rem;
  }

  .filter-btn {
    padding: 0.4rem 1rem;
    border-radius: 15px;
    border: 1px solid #e5e7eb;
    font-size: 0.75rem;
    font-weight: 700;
    cursor: pointer;
    background: #ffffff;
    color: #6b7280;
  }

  .filter-btn.active {
    background: #1e5a32;
    color: #ffffff;
    border-color: transparent;
  }

  .list-container {
    flex: 1;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
  }

  .list-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.6rem 0.75rem;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.15s;
    border-left: 3px solid transparent;
  }
  .list-item:hover {
    background: #f0fdf4;
    border-left-color: #16a34a;
  }
  .list-item.active-item {
    background: #f0fdf4;
    border-left-color: #16a34a;
    font-weight: 700;
  }

  .item-id {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    background: #f3f4f6;
    color: #166534;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 800;
    flex-shrink: 0;
  }

  .item-text {
    flex: 1;
    overflow: hidden;
  }
  .item-text h4 {
    margin: 0;
    font-size: 0.82rem;
    color: #1f2937;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .item-text p {
    margin: 0;
    font-size: 0.7rem;
    color: #9ca3af;
  }

  .arrow-indicator {
    font-size: 0.75rem;
    color: #2563eb;
    opacity: 0.6;
  }

  .right-col {
    height: 100%;
    position: relative;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.06);
    border: 1px solid #e5e7eb;
  }

  .map-card {
    height: 100%;
    width: 100%;
    position: relative;
  }

  .map-wrapper {
    height: 100%;
    width: 100%;
    background: #f8faf9;
  }

  /* Nav HUD */
  .nav-hud-card {
    position: absolute;
    top: 12px;
    left: 12px;
    right: auto;
    z-index: 500;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(8px);
    border-radius: 12px;
    padding: 0.8rem;
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    border: 1px solid #e5e7eb;
    width: 280px;
    max-width: calc(100% - 6.5rem);
  }

  .nav-hud-header {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
  }

  .walk-badge {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #2563eb;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 2px 6px rgba(37,99,235,0.3);
  }
  .walk-svg {
    width: 20px;
    height: 20px;
  }

  .nav-hud-details {
    flex: 1;
  }

  .nav-chip {
    font-size: 0.65rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #1d4ed8;
    background: #eff6ff;
    padding: 2px 6px;
    border-radius: 10px;
    display: inline-block;
  }

  .nav-dest-title {
    margin: 2px 0 0 0;
    font-size: 0.88rem;
    font-weight: 800;
    color: #111827;
  }

  .nav-meta {
    font-size: 0.75rem;
    color: #4b5563;
    display: flex;
    align-items: center;
    gap: 0.4rem;
    margin-top: 2px;
  }
  .nav-time {
    color: #15803d;
    font-weight: 700;
  }

  .nav-clear-btn {
    background: none;
    border: none;
    font-size: 1rem;
    color: #9ca3af;
    cursor: pointer;
    padding: 2px 6px;
    border-radius: 6px;
  }
  .nav-clear-btn:hover {
    background: #f3f4f6;
    color: #374151;
  }

  .nav-hud-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 0.6rem;
    padding-top: 0.5rem;
    border-top: 1px solid #f3f4f6;
    font-size: 0.72rem;
  }

  .origin-selector select {
    font-size: 0.72rem;
    border-radius: 4px;
    border: 1px solid #d1d5db;
    padding: 1px 4px;
    background: #f9fafb;
    cursor: pointer;
  }

  .btn-fit {
    background: none;
    border: none;
    color: #2563eb;
    font-weight: 700;
    cursor: pointer;
  }

  :global(.route-flow-line) {
    animation: routeDashFlow 1.2s linear infinite;
  }
  @keyframes routeDashFlow {
    from { stroke-dashoffset: 24; }
    to { stroke-dashoffset: 0; }
  }

  :global(.you-are-here-marker) {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
  }
  :global(.you-are-here-dot) {
    width: 14px;
    height: 14px;
    background-color: #2563eb;
    border: 2.5px solid #ffffff;
    border-radius: 50%;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.4);
    z-index: 2;
  }
  :global(.you-are-here-pulse) {
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

  :global(.dest-pin-marker) {
    display: flex;
    flex-direction: column;
    align-items: center;
    animation: pinDrop 0.35s cubic-bezier(0.34, 1.56, 0.64, 1) both;
  }
  @keyframes pinDrop {
    0% { transform: translateY(-14px) scale(0.7); opacity: 0; }
    100% { transform: translateY(0) scale(1); opacity: 1; }
  }
</style>
