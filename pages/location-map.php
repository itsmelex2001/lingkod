<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Directory & Site Map | LINGKOD</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <style>
        body, html { margin: 0; padding: 0; height: 100%; overflow: hidden; }
        body, body * { user-select: none; -webkit-user-select: none; caret-color: transparent; }
        input, textarea, select { user-select: text; -webkit-user-select: text; caret-color: auto; }
        #map-container { height: 100%; width: 100%; background: #f8faf9; }
        
        /* Leaflet popup styling */
        .leaflet-popup-content-wrapper { border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.12); }
        .leaflet-popup-content h3 { font-weight: bold; font-size: 1rem; color: #166534; margin: 0 0 4px 0; }
        .leaflet-popup-content p { margin: 0; color: #4b5563; font-size: 0.8rem; }
        
        /* Fullscreen Map Mode */
        .is-fullscreen {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            z-index: 9999 !important;
        }

        /* Sidebar scrollbar */
        .sidebar-scroll::-webkit-scrollbar { width: 6px; }
        .sidebar-scroll::-webkit-scrollbar-track { background: #f1f1f1; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .sidebar-scroll::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

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

        /* Active Directory List Item */
        .active-dir-item {
            background-color: #f0fdf4 !important;
            border-left-color: #16a34a !important;
        }
    </style>
</head>
<body class="bg-gray-50 flex flex-col h-screen font-sans">

    <!-- Header / Navbar -->
    <header class="bg-green-800 text-white shadow-md p-4 flex justify-between items-center z-20 shrink-0">
        <div class="flex items-center gap-3">
            <a href="/sdn/index.php" class="text-white hover:text-green-200 transition-colors" title="Back to Homepage">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
            <div>
                <h1 class="text-xl font-bold tracking-wide flex items-center gap-2">
                    <span>Interactive Hospital Site Map</span>
                </h1>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <button id="btn-fullscreen" class="bg-green-700 hover:bg-green-600 px-4 py-2 rounded-lg text-sm font-semibold transition shadow flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                <span>Fullscreen</span>
            </button>
        </div>
    </header>

    <!-- Main Split Screen Layout -->
    <main id="main-layout" class="flex flex-1 overflow-hidden relative">
        
        <!-- Left Sidebar: Directory List -->
        <aside class="w-full md:w-80 lg:w-96 bg-white border-r border-gray-200 flex flex-col shrink-0 z-10 shadow-lg relative">
            <div class="p-4 border-b border-gray-100 bg-gray-50">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-bold text-gray-800 text-lg">Hospital Directory</h2>
                    <span class="text-xs text-green-700 font-semibold bg-green-50 px-2 py-0.5 rounded-full border border-green-200">Interactive Guide</span>
                </div>
                <div class="relative mb-3">
                    <input type="text" id="search-input" placeholder="Search departments, wards, clinics..." 
                           class="w-full pl-10 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <!-- Quick Filter Buttons -->
                <div class="flex items-center gap-2">
                    <button id="btn-filter-all" class="px-3.5 py-1 rounded-full text-xs font-bold bg-green-800 text-white shadow-sm transition-colors border border-transparent">All</button>
                    <button id="btn-filter-visited" class="px-3.5 py-1 rounded-full text-xs font-bold bg-white text-gray-500 border border-gray-200 shadow-sm transition-colors hover:text-gray-700">Most Visited</button>
                </div>
            </div>
            
            <!-- Dynamic List Container -->
            <ul id="directory-list" class="flex-1 overflow-y-auto sidebar-scroll p-2 divide-y divide-gray-100">
                <li class="p-4 text-center text-gray-500 animate-pulse">Loading directory...</li>
            </ul>
        </aside>

        <!-- Right Area: Leaflet Map -->
        <section id="map-wrapper" class="flex-1 relative transition-all duration-300 bg-gray-100">
            <!-- Google Maps Navigation Floating HUD -->
            <div id="map-nav-hud" class="absolute top-4 left-4 right-4 md:right-auto md:max-w-md bg-white/95 backdrop-blur-md rounded-xl p-3.5 shadow-xl border border-gray-200 transition-all duration-300 pointer-events-auto z-[500]" style="display: none;">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center shrink-0 shadow-md">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M13 7a2 2 0 11-4 0 2 2 0 014 0zM9 13a4 4 0 00-4 4v4h3v-3.5l1.5-1.5 2 3H15v-4.5l-2-2.5 1-2.5 2 1.5V11h2V9l-3-2.5-2.5 1.5L9.5 9A4 4 0 009 13z" />
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-blue-700 bg-blue-50 px-2 py-0.5 rounded-full border border-blue-100">Walking Route</span>
                                <span id="nav-badge" class="text-[10px] font-bold text-gray-600 bg-gray-100 px-1.5 py-0.5 rounded">Bldg</span>
                            </div>
                            <h4 id="nav-destination-title" class="font-bold text-gray-900 text-sm leading-tight mt-1">Destination</h4>
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
                <div class="mt-3 pt-2.5 border-t border-gray-100 flex items-center justify-between text-xs">
                    <div class="flex items-center gap-1 text-gray-500">
                        <span class="text-gray-400">From:</span>
                        <select id="select-route-start" class="bg-gray-50 border border-gray-200 rounded px-2 py-1 text-gray-700 font-medium text-xs focus:outline-none focus:border-blue-500 cursor-pointer">
                            <option value="you_are_here">📍 You Are Here (Campus Path)</option>
                            <option value="gate">🚪 Main Entrance (Gate 2)</option>
                        </select>
                    </div>
                    <button id="btn-fit-route" class="text-xs text-blue-600 font-semibold hover:text-blue-800 transition-colors">Fit Route</button>
                </div>
            </div>

            <div id="map-container"></div>
        </section>
        
    </main>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <!-- Application Logic -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const imageBounds = [[0, 0], [1000, 700]];
            const paddedMaxBounds = [[-200, -200], [1200, 900]];

            const map = L.map('map-container', {
                crs: L.CRS.Simple,
                zoomControl: true,
                scrollWheelZoom: true,
                maxBounds: paddedMaxBounds,
                maxBoundsViscosity: 0.9,
                maxZoom: 3
            });

            const mapImage = L.imageOverlay('/sdn/public/assets/MMWGH-site-map.png', imageBounds).addTo(map);
            map.fitBounds(imageBounds);

            setTimeout(() => {
                map.setMinZoom(map.getZoom());
            }, 100);

            let markers = [];
            let locationsData = [];
            let currentFilter = 'ALL';
            let activeDestinationId = null;
            let currentStartPointKey = 'you_are_here';
            let currentRouteLayers = [];
            let currentStartMarker = null;
            let currentDestMarker = null;
            const mostVisitedIds = [16, 24, 37, 5, 1, 14, 25];

            // --- HOSPITAL WALKWAY NAVIGATION GRAPH (STRICTLY ON OUTDOOR PATHWAYS & ROADS) ---
            const nodes = {
                // Entrance / South Main Road (strictly on road)
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

                // Central Walkway Promenade (Bypasses Bldg 40, Bldg 3, Bldg 4, Bldg 5, Bldg 32, Bldg 6)
                'corridor_s1':    { y: 380, x: 240 }, // east of Bldg 40, west of Bldg 3/4
                'corridor_s2':    { y: 450, x: 250 }, // south of Covered Court 32, west of Bldg 5
                'corridor_cross': { y: 495, x: 275 }, // south of Covered Court, open breezeway junction
                'corridor_mid':   { y: 570, x: 275 }, // between Covered Court 32 and CCU 6
                'corridor_ot':    { y: 660, x: 275 }, // between OT 34 and CCU 6
                'corridor_n1':    { y: 735, x: 275 }, // north of CCU 6 and OT 34

                // East Breezeway (between Bldg 5 and Bldg 6, connecting to East Road)
                'breeze_mid':     { y: 495, x: 375 }, // open outdoor area between Bldg 5, 24 and 6
                'breeze_east':    { y: 495, x: 475 }, // connects to east road

                // West Road System (along western boundary past 28, 29-A, 30, 41, 31, 36, 35)
                'west_opu':       { y: 235, x: 155 }, // near OPU 28
                'west_1':         { y: 320, x: 140 }, // junction near You Are Here
                'west_2':         { y: 422, x: 140 }, // near Lazareto 30 / Malasakit 41
                'west_3':         { y: 476, x: 140 }, // near Chapel 31
                'west_cross':     { y: 495, x: 155 }, // crossway to central corridor
                'west_4':         { y: 580, x: 155 }, // between Bldg 36 and Covered Court 32
                'west_5':         { y: 660, x: 155 }, // west of OT 34
                'west_6':         { y: 702, x: 175 }, // east of Isolation 33 / Male Dorm 35
                'west_7':         { y: 735, x: 175 }, // east of Male Dorm 35
                'west_north':     { y: 830, x: 180 }, // east of Power House 17
                'west_top':       { y: 900, x: 180 }, // approaches Linen & Laundry 15

                // East Road System (past General Hospital 16, Lab 18, NDU 19, MMU 21, HOPS 24, COA 25, Dorm 26)
                'east_entry':     { y: 200, x: 425 }, // entrance to General Hospital / Radiology
                'east_1':         { y: 240, x: 445 }, // between Radiology 2 and Gen Hospital 16
                'east_2':         { y: 280, x: 475 }, // in front of Lab 18
                'east_3':         { y: 335, x: 460 }, // in front of NDU 19 / Dorm 20
                'east_4':         { y: 375, x: 460 }, // in front of MMU 21
                'east_5':         { y: 415, x: 455 }, // in front of Warehouse 22 / MRF 23
                'east_6':         { y: 450, x: 455 }, // in front of HOPS 24 / COA 25
                'east_crossway':  { y: 535, x: 475 }, // in front of Male Dorm 26
                'east_stp':       { y: 585, x: 475 }, // STP 27
                'east_pav_entry': { y: 620, x: 475 }, // entrance to Male Ward 7
                'east_pav_mid':   { y: 685, x: 475 }, // near Obs Unit 9 / Nurse Station 8
                'east_pav_upper': { y: 735, x: 475 }, // near Female Ward 10
                'east_pav_top':   { y: 780, x: 475 }, // near Old Infirmary 12

                // Northern Campus Loop & Facilities (Bldgs 11, 13, 14, 38, 15)
                'north_junc_1':   { y: 765, x: 260 }, // north junction connecting central, west and pavilions
                'north_junc_2':   { y: 815, x: 260 }, // past Old CCU 11 & Cadaver 13
                'north_tb_dots':  { y: 870, x: 235 }, // in front of TB-Dots 14
                'north_waste':    { y: 910, x: 255 }, // road in front of Waste Autoclave 38
                'north_top_road': { y: 930, x: 255 }, // road above Bldg 38
                'north_end':      { y: 952, x: 215 }  // Linen and Laundry 15
            };

            const bldgAttach = {
                1:  'gate',           // Guard House Gate 2
                2:  'east_1',         // Radiology Complex
                3:  'yah_east',       // EFMU Building
                4:  'corridor_s1',    // HIMU
                5:  'corridor_s2',    // Finance Management
                6:  'corridor_mid',   // Custodial Care Unit (west main entrance on promenade)
                7:  'east_pav_entry', // Male Ward Pavilion
                8:  'east_pav_mid',   // Nurse Station
                9:  'east_pav_mid',   // Observation Unit
                10: 'east_pav_upper', // Female Ward Pavilion
                11: 'north_junc_1',   // Old CCU
                12: 'east_pav_top',   // Old Infirmary Unit
                13: 'north_junc_2',   // Cadaver Holding Area
                14: 'north_tb_dots',  // TB-Dots
                15: 'north_end',      // Linen and Laundry
                16: 'east_entry',     // General Hospital
                17: 'west_north',     // Power House
                18: 'east_2',         // Laboratory Complex
                19: 'east_3',         // Nutrition and Dietetics (NDU)
                20: 'east_3',         // Staff Dormitory
                21: 'east_4',         // Procurement and Materials (MMU)
                22: 'east_5',         // Warehouse
                23: 'east_5',         // Material Recovery Facility
                24: 'breeze_mid',     // HOPS
                25: 'east_6',         // COA Building
                26: 'east_crossway',  // Male Dorm
                27: 'east_stp',       // Sewerage Treatment Plant
                28: 'west_opu',       // OPU Building
                30: 'west_2',         // Lazareto Treatment Hub
                31: 'west_3',         // Chapel
                32: 'corridor_mid',   // Covered Court
                33: 'west_6',         // Isolation Building-1
                34: 'corridor_ot',    // Occupational Therapy Building
                35: 'west_7',         // Male Dorm A&B
                36: 'west_4',         // Isolation Building-2 (Bayanihan Bldg)
                37: 'canteen_spur',   // Canteen (PACD)
                38: 'north_waste',    // Integrated Medical Waste Autoclave Machine House
                39: 'corridor_ot',    // Nurse Station (Male Ward)
                40: 'corridor_s1',    // Medical Ward
                41: 'west_2'          // Malasakit Center Building
            };

            const edges = [
                // South / Entrance
                ['gate', 'road_entry'],
                ['road_entry', 'main_road_east'],
                ['road_entry', 'main_road_west'],
                ['road_entry', 'plaza_south'],
                ['road_entry', 'canteen_spur'],

                // South Plaza & Connections
                ['plaza_south', 'road_entry'],
                ['plaza_south', 'you_are_here'],
                ['plaza_south', 'yah_east'],

                // Main Road East & East Entry
                ['main_road_east', 'east_entry'],
                ['east_entry', 'east_1'],
                ['east_1', 'east_2'],
                ['east_2', 'east_3'],
                ['east_3', 'east_4'],
                ['east_4', 'east_5'],
                ['east_5', 'east_6'],
                ['east_6', 'breeze_east'],
                ['breeze_east', 'east_crossway'],
                ['east_crossway', 'east_stp'],
                ['east_crossway', 'east_pav_entry'],
                ['east_pav_entry', 'east_pav_mid'],
                ['east_pav_mid', 'east_pav_upper'],
                ['east_pav_upper', 'east_pav_top'],

                // Breezeway between Central and East Road
                ['breeze_east', 'breeze_mid'],
                ['breeze_mid', 'corridor_cross'],

                // West Road
                ['main_road_west', 'west_opu'],
                ['west_opu', 'west_1'],
                ['west_1', 'west_2'],
                ['west_2', 'west_3'],
                ['west_3', 'west_cross'],
                ['west_cross', 'west_4'],
                ['west_4', 'west_5'],
                ['west_5', 'west_6'],
                ['west_6', 'west_7'],
                ['west_7', 'west_north'],
                ['west_north', 'west_top'],
                ['west_top', 'north_end'],

                // West Road <-> Central Connections
                ['west_1', 'you_are_here'],
                ['west_cross', 'corridor_s2'],
                ['west_7', 'corridor_n1'],

                // South Plaza <-> You Are Here <-> Central Corridor
                ['you_are_here', 'yah_east'],
                ['yah_east', 'corridor_s1'],
                ['corridor_s1', 'corridor_s2'],
                ['corridor_s2', 'corridor_cross'],
                ['corridor_cross', 'corridor_mid'],
                ['corridor_mid', 'corridor_ot'],
                ['corridor_ot', 'corridor_n1'],

                // North Campus Network
                ['corridor_n1', 'north_junc_1'],
                ['north_junc_1', 'north_junc_2'],
                ['north_junc_2', 'north_tb_dots'],
                ['north_tb_dots', 'north_waste'],
                ['north_waste', 'north_top_road'],
                ['north_top_road', 'north_end']
            ];

            const adj = {};
            Object.keys(nodes).forEach(k => adj[k] = []);
            edges.forEach(([u, v]) => {
                const p1 = nodes[u];
                const p2 = nodes[v];
                const weight = Math.hypot(p1.x - p2.x, p1.y - p2.y);
                adj[u].push({ node: v, weight });
                adj[v].push({ node: u, weight });
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

            function drawRouteTo(loc) {
                if (!loc) return;
                activeDestinationId = loc.id;
                clearRouteLayers();

                const startKey = currentStartPointKey;
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
                const meters = Math.round(totalUnits * 0.65);
                const minutes = Math.max(1, Math.round(meters / 65));

                const routeShadow = L.polyline(latLngs, {
                    color: '#1e3a8a',
                    weight: 8,
                    opacity: 0.35,
                    lineCap: 'round',
                    lineJoin: 'round'
                }).addTo(map);

                const routeMain = L.polyline(latLngs, {
                    color: '#2563eb',
                    weight: 5,
                    opacity: 0.95,
                    lineCap: 'round',
                    lineJoin: 'round'
                }).addTo(map);

                const routeFlow = L.polyline(latLngs, {
                    color: '#dbeafe',
                    weight: 2.2,
                    opacity: 0.95,
                    dashArray: '6, 12',
                    className: 'route-flow-line'
                }).addTo(map);

                currentRouteLayers = [routeShadow, routeMain, routeFlow];

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

                const hud = document.getElementById('map-nav-hud');
                document.getElementById('nav-badge').textContent = `Bldg ${loc.id > 99 ? '•' : loc.id}`;
                document.getElementById('nav-destination-title').textContent = loc.name;
                document.getElementById('nav-time').textContent = `~${minutes} min`;
                document.getElementById('nav-dist').textContent = `${meters} meters`;
                hud.style.display = 'block';

                highlightSidebarItem(loc.id);

                // Overhauled zoom: gently fit route bounds without extreme close-up zoom
                const bounds = routeMain.getBounds();
                map.fitBounds(bounds, {
                    padding: [60, 60],
                    maxZoom: 0.65,
                    animate: true,
                    duration: 0.8
                });
            }

            function clearRoute() {
                clearRouteLayers();
                activeDestinationId = null;
                document.getElementById('map-nav-hud').style.display = 'none';
                document.querySelectorAll('#directory-list li').forEach(li => {
                    li.classList.remove('active-dir-item');
                });
                map.fitBounds(imageBounds, { animate: true, duration: 0.6 });
            }

            function highlightSidebarItem(id) {
                document.querySelectorAll('#directory-list li').forEach(li => {
                    if (parseInt(li.dataset.locId) === id) {
                        li.classList.add('active-dir-item');
                        li.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    } else {
                        li.classList.remove('active-dir-item');
                    }
                });
            }

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
                const listEl = document.getElementById('directory-list');
                listEl.innerHTML = '';
                if (data.length === 0) {
                    listEl.innerHTML = '<li class="p-4 text-center text-gray-500">No locations found.</li>';
                    return;
                }

                data.forEach(loc => {
                    const li = document.createElement('li');
                    li.dataset.locId = loc.id;
                    li.className = 'p-3 hover:bg-green-50 rounded cursor-pointer transition-colors border-l-4 border-transparent hover:border-green-600 flex items-center justify-between gap-2';
                    if (activeDestinationId === loc.id) {
                        li.classList.add('active-dir-item');
                    }
                    li.innerHTML = `
                        <div class="flex items-center gap-2.5 overflow-hidden">
                            <div class="bg-gray-100 text-green-800 font-bold rounded-full w-7 h-7 flex items-center justify-center shrink-0 text-xs shadow-sm">${loc.id > 99 ? '•' : loc.id}</div>
                            <div class="truncate">
                                <h4 class="font-semibold text-gray-800 text-[13px] leading-tight truncate">${loc.name}</h4>
                                <span class="text-[11px] text-gray-400">Click for directions</span>
                            </div>
                        </div>
                        <div class="text-blue-600 text-xs shrink-0 flex items-center gap-0.5 font-semibold opacity-70 hover:opacity-100">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </div>
                    `;
                    li.addEventListener('click', () => {
                        drawRouteTo(loc);
                    });
                    listEl.appendChild(li);
                });
            }

            // Search
            document.getElementById('search-input').addEventListener('input', (e) => {
                const term = e.target.value.toLowerCase();
                let filtered = locationsData.filter(loc => 
                    loc.name.toLowerCase().includes(term) || (loc.description && loc.description.toLowerCase().includes(term))
                );
                if (currentFilter === 'VISITED') {
                    filtered = filtered.filter(loc => mostVisitedIds.includes(loc.id));
                }
                renderSidebar(filtered);
            });

            // Filters
            document.getElementById('btn-filter-all').addEventListener('click', () => {
                currentFilter = 'ALL';
                const btnAll = document.getElementById('btn-filter-all');
                const btnVisited = document.getElementById('btn-filter-visited');
                btnAll.className = 'px-3.5 py-1 rounded-full text-xs font-bold bg-green-800 text-white shadow-sm transition-colors border border-transparent';
                btnVisited.className = 'px-3.5 py-1 rounded-full text-xs font-bold bg-white text-gray-500 border border-gray-200 shadow-sm transition-colors hover:text-gray-700';
                document.getElementById('search-input').dispatchEvent(new Event('input'));
            });

            document.getElementById('btn-filter-visited').addEventListener('click', () => {
                currentFilter = 'VISITED';
                const btnAll = document.getElementById('btn-filter-all');
                const btnVisited = document.getElementById('btn-filter-visited');
                btnVisited.className = 'px-3.5 py-1 rounded-full text-xs font-bold bg-green-800 text-white shadow-sm transition-colors border border-transparent';
                btnAll.className = 'px-3.5 py-1 rounded-full text-xs font-bold bg-white text-gray-500 border border-gray-200 shadow-sm transition-colors hover:text-gray-700';
                document.getElementById('search-input').dispatchEvent(new Event('input'));
            });

            // Fullscreen API
            const btnFullscreen = document.getElementById('btn-fullscreen');
            const mapWrapper = document.getElementById('map-wrapper');

            btnFullscreen.addEventListener('click', () => {
                if (!document.fullscreenElement) {
                    if (mapWrapper.requestFullscreen) {
                        mapWrapper.requestFullscreen().catch(err => {
                            console.error(`Error attempting fullscreen: ${err.message}`);
                        });
                    }
                } else {
                    if (document.exitFullscreen) {
                        document.exitFullscreen();
                    }
                }
            });

            document.addEventListener('fullscreenchange', () => {
                setTimeout(() => {
                    map.invalidateSize();
                }, 200);
            });

            fetchLocations();
        });
    </script>
</body>
</html>
