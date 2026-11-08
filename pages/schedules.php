<?php session_start(); ?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>View Schedule Calendar | MMWGH LINGKOD</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@500;600;700;800&family=Montserrat:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/sdn/public/css/global.css?v=<?= time() ?>" />

    <style>
      :root {
        --ink: #1a2420;
        --ink-soft: #3e4c45;
        --muted: #6a756f;
        --line: #e3e7e4;
        --line-strong: #d3d9d5;
        --paper: #f3f5f4;
        --card: #ffffff;
        --forest: #166534;
        --forest-mid: #15803d;
        --avail: #166534;
        --home-shade: #166534;
      }

      * {
        box-sizing: border-box;
      }

      html, body {
        height: 100vh;
        margin: 0;
        padding: 0;
        overflow: hidden;
      }

      body {
        display: flex;
        flex-direction: column;
        background: var(--paper);
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        color: var(--ink);
      }

      /* Page Container - Locked to Viewport */
      .sched-page-wrap {
        flex: 1;
        min-height: 0;
        max-width: 1440px;
        width: 100%;
        margin: 0 auto;
        padding: 0.85rem 1.5rem 1rem;
        display: flex;
        flex-direction: column;
        box-sizing: border-box;
        overflow: hidden;
      }

      /* Top Bar: Title + Status + Search */
      .sched-top-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: nowrap;
        margin-bottom: 0.5rem;
        background: linear-gradient(135deg, #14532d 0%, #166534 50%, #15803d 100%);
        padding: 0.75rem 1.25rem;
        border-radius: 14px;
        box-shadow: 0 4px 14px rgba(20, 83, 45, 0.18);
        border: 1px solid rgba(255, 255, 255, 0.15);
        flex-shrink: 0;
      }

      .sched-brand-left {
        display: flex;
        align-items: center;
        gap: 0.75rem;
      }

      .sched-top-bar .sched-title-group h1,
      .sched-title-group h1 {
        margin: 0;
        font-family: 'Outfit', 'Inter', sans-serif !important;
        font-size: 1.25rem !important;
        font-weight: 750 !important;
        color: #ffffff !important;
        letter-spacing: -0.015em !important;
        line-height: 1.2 !important;
        text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3) !important;
      }

      .sched-top-bar .sched-subtitle-row,
      .sched-subtitle-row,
      .sched-subtitle-row span {
        color: #ffffff !important;
      }

      .sched-subtitle-row {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        margin-top: 0.25rem;
        font-size: 0.8rem;
        font-weight: 500;
      }

      .sched-subtitle-row > span:not(.live-dot-badge) {
        color: rgba(255, 255, 255, 0.95) !important;
        font-size: 0.8rem !important;
        font-weight: 500 !important;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
      }

      .live-dot-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        background: rgba(0, 0, 0, 0.22);
        border: 1px solid rgba(255, 255, 255, 0.32);
        color: #ffffff;
        padding: 0.18rem 0.6rem;
        border-radius: 999px;
        font-size: 0.74rem;
        font-weight: 700;
        font-variant-numeric: tabular-nums;
        letter-spacing: 0.02em;
        box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.15);
      }

      .live-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #4ade80;
        box-shadow: 0 0 8px #4ade80;
        animation: pulseLiveDot 2s infinite ease-in-out;
      }

      @keyframes pulseLiveDot {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.6; transform: scale(0.85); }
      }

      /* Right Search */
      .sched-controls-right {
        display: flex;
        align-items: center;
        gap: 0.75rem;
      }

      .sched-search-box {
        position: relative;
        display: flex;
        align-items: center;
      }

      .sched-search-box svg {
        position: absolute;
        left: 12px;
        color: #166534;
        pointer-events: none;
      }

      .sched-search-input {
        padding: 0.48rem 0.95rem 0.48rem 2.25rem;
        border-radius: 999px;
        border: 1.5px solid rgba(255, 255, 255, 0.5);
        background: #ffffff;
        font-family: 'Inter', sans-serif;
        font-size: 0.82rem;
        font-weight: 500;
        color: #0f172a;
        width: 240px;
        outline: none;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
        transition: all 0.2s ease;
      }

      .sched-search-input::placeholder {
        color: #64748b;
      }

      .sched-search-input:focus {
        background: #ffffff;
        border-color: #ffffff;
        box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.4);
      }

      /* Department Filter Tabs */
      .sched-dept-tabs {
        display: flex;
        align-items: center;
        gap: 0.45rem;
        flex-wrap: wrap;
        margin-bottom: 0.5rem;
        flex-shrink: 0;
      }

      .sched-dept-pill {
        border: 1px solid var(--line-strong);
        background: #ffffff;
        color: var(--ink-soft);
        padding: 0.36rem 0.85rem;
        border-radius: 999px;
        font-family: 'Inter', sans-serif;
        font-size: 0.78rem;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        box-shadow: none;
      }

      .sched-dept-pill:hover {
        border-color: #b7c2bb;
        background: #f7f8f7;
        color: var(--ink);
      }

      .sched-dept-pill.active {
        background: var(--home-shade);
        color: #ffffff;
        border-color: transparent;
        box-shadow: none;
      }

      .dept-dot {
        width: 6px;
        height: 6px;
        border-radius: 1px;
      }
      .dot-all { background: #8aa394; }
      .dot-general { background: #6d8b78; }
      .dot-neuro { background: #7d8d99; }
      .dot-psych { background: #8d8494; }
      .sched-dept-pill.active .dept-dot {
        background: #ffffff;
      }

      /* Main Calendar + Day Schedule Grid */
      .sched-main-grid {
        flex: 1;
        min-height: 0;
        display: grid;
        grid-template-columns: 1.12fr 1fr;
        gap: 0.95rem;
        align-items: stretch;
        height: 100%;
      }

      /* Left Panel: Calendar Card */
      .calendar-panel-card {
        background: var(--card);
        border-radius: 12px;
        padding: 0.9rem 1.1rem 0.75rem;
        box-shadow: none;
        border: 1px solid var(--line);
        display: flex;
        flex-direction: column;
        height: 100%;
        min-height: 0;
        overflow: hidden;
      }

      .cal-header-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.55rem;
        padding-bottom: 0.55rem;
        border-bottom: 1px solid var(--line);
        flex-shrink: 0;
      }

      .cal-month-title {
        font-family: 'Outfit', 'Inter', sans-serif;
        font-size: 1.12rem;
        font-weight: 650;
        color: var(--ink);
        margin: 0;
        letter-spacing: -0.02em;
      }

      .cal-month-sub {
        font-size: 0.72rem;
        color: var(--muted);
        margin-top: 2px;
        font-weight: 500;
      }

      .cal-nav-group {
        display: flex;
        align-items: center;
        gap: 0.35rem;
      }

      .cal-nav-btn {
        height: 30px;
        padding: 0 0.65rem;
        border-radius: 8px;
        border: 1px solid var(--line-strong);
        background: #ffffff;
        color: var(--ink-soft);
        font-family: 'Inter', sans-serif;
        font-size: 0.74rem;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: background 0.15s ease, border-color 0.15s ease;
      }

      .cal-nav-btn:hover:not(:disabled) {
        background: #f4f6f5;
        border-color: #b7c0ba;
        color: var(--ink);
      }

      .cal-nav-btn:disabled {
        opacity: 0.4;
        cursor: not-allowed;
      }

      .cal-weekdays-row {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 0.3rem;
        margin-bottom: 0.3rem;
        text-align: center;
        flex-shrink: 0;
      }

      .cal-weekday-lbl {
        font-size: 0.72rem;
        font-weight: 750;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #334155;
        padding: 0.15rem 0;
      }

      .cal-weekday-lbl.sun {
        color: #dc2626;
        font-weight: 800;
      }

      .cal-days-grid {
        flex: 1;
        min-height: 0;
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        grid-auto-rows: 1fr;
        gap: 0.35rem;
      }

      .cal-day-cell {
        position: relative;
        min-height: 0;
        height: 100%;
        border-radius: 8px;
        padding: 0.35rem 0.4rem;
        border: 1px solid var(--line);
        background: #ffffff;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        align-items: flex-start;
        cursor: pointer;
        transition: background 0.15s ease, border-color 0.15s ease;
        user-select: none;
        text-align: left;
      }

      .cal-day-cell.empty-cell {
        background: transparent;
        border-color: transparent;
        cursor: default;
        pointer-events: none;
      }

      .cal-day-cell.past-cell,
      .cal-day-cell.closed-cell {
        background: #f8fafc;
        border-color: #f1f5f9;
        color: #9aa39e;
        cursor: not-allowed;
      }

      .cal-day-cell.available-cell {
        background: #ffffff;
        border-color: var(--line-strong);
        box-shadow: none;
      }

      .cal-day-cell.available-cell:hover {
        border-color: #9aaba1;
        background: #f6f8f7;
        transform: none;
        box-shadow: none;
      }

      .cal-day-cell.selected-cell {
        background: #166534 !important;
        border-color: #14532d !important;
        color: #ffffff !important;
        box-shadow: none !important;
        transform: none;
      }

      .cal-day-top {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
      }

      .cal-day-num {
        font-family: 'Outfit', 'Inter', sans-serif;
        font-size: 0.88rem;
        font-weight: 700;
        line-height: 1;
      }

      .cal-today-tag {
        font-size: 0.58rem;
        font-weight: 800;
        text-transform: uppercase;
        padding: 1px 4px;
        border-radius: 4px;
        background: #efe8d6;
        color: #6b5a38;
      }

      .cal-day-cell.selected-cell .cal-today-tag {
        background: rgba(255, 255, 255, 0.28);
        color: #ffffff;
        font-weight: 700;
      }

      .cal-day-bottom {
        width: 100%;
        display: flex;
        flex-direction: column;
        gap: 2px;
        margin-top: 2px;
      }

      .cal-slot-pill {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        font-size: 0.62rem;
        font-weight: 600;
        padding: 0;
        border-radius: 0;
        background: transparent;
        color: var(--avail);
        width: fit-content;
      }

      .cal-day-cell.selected-cell .cal-slot-pill {
        background: transparent;
        color: #ffffff !important;
        font-weight: 700;
      }

      .cal-closed-lbl {
        font-size: 0.6rem;
        font-weight: 600;
        color: #cbd5e1;
      }

      .cal-dept-dots {
        display: flex;
        align-items: center;
        gap: 3px;
        padding-left: 2px;
      }

      .cal-dept-dots span {
        width: 5px;
        height: 5px;
        border-radius: 1px;
        background: #8aa394;
      }
      .cal-dept-dots .dot-neuro { background: #7d8d99; }
      .cal-dept-dots .dot-psych { background: #8d8494; }
      .cal-day-cell.selected-cell .cal-dept-dots span {
        background: rgba(255, 255, 255, 0.8);
      }

      /* Calendar Legend */
      .cal-legend-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 0.45rem;
        padding-top: 0.45rem;
        border-top: 1px solid var(--line);
        font-size: 0.7rem;
        color: var(--muted);
        font-weight: 500;
        flex-shrink: 0;
      }

      .cal-legend-items {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        flex-wrap: wrap;
      }

      .cal-legend-item {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
      }

      .legend-swatch {
        width: 10px;
        height: 10px;
        border-radius: 3px;
      }

      .swatch-avail {
        background: #ffffff;
        border: 1px solid var(--line-strong);
      }

      .swatch-selected {
        background: var(--home-shade);
      }

      .swatch-closed {
        background: #f1f5f9;
        border: 1.5px solid #cbd5e1;
      }

      /* Right Panel: Selected Day Schedule & Department Slot Times */
      .day-schedule-card {
        background: var(--card);
        border-radius: 12px;
        padding: 0.9rem 1.1rem;
        box-shadow: none;
        border: 1px solid var(--line);
        display: flex;
        flex-direction: column;
        height: 100%;
        min-height: 0;
        overflow: hidden;
      }

      .day-selected-banner {
        background: #166534;
        color: #ffffff;
        border-radius: 10px;
        padding: 0.9rem 1.15rem;
        margin-bottom: 0.65rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        flex-shrink: 0;
        box-shadow: none;
      }

      .day-banner-left {
        display: flex;
        flex-direction: column;
        gap: 0.22rem;
      }

      .day-banner-eyebrow {
        font-size: 0.72rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #bbf7d0 !important;
      }

      .day-banner-date {
        font-family: 'Outfit', 'Inter', sans-serif;
        font-size: 1.3rem;
        font-weight: 700;
        color: #ffffff !important;
        margin: 0;
        line-height: 1.2;
        letter-spacing: -0.02em;
      }

      .day-banner-sub {
        font-size: 0.8rem;
        color: #f0fdf4 !important;
        margin: 0;
        font-weight: 500;
      }

      .day-banner-badge {
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.35);
        padding: 0.45rem 0.95rem;
        border-radius: 11px;
        text-align: center;
        flex-shrink: 0;
      }

      .day-banner-badge strong {
        display: block;
        font-size: 1.25rem;
        font-weight: 800;
        line-height: 1;
        color: #ffffff !important;
      }

      .day-banner-badge span {
        font-size: 0.68rem;
        color: #ffffff !important;
        font-weight: 700;
        letter-spacing: 0.02em;
      }

      /* Quick Time Filter Chips */
      .time-session-tabs {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        margin-bottom: 0.55rem;
        flex-wrap: wrap;
        flex-shrink: 0;
      }

      .session-chip {
        border: 1px solid var(--line-strong);
        background: #ffffff;
        color: var(--ink-soft);
        font-family: 'Inter', sans-serif;
        font-size: 0.72rem;
        font-weight: 600;
        padding: 0.32rem 0.75rem;
        border-radius: 999px;
        cursor: pointer;
        transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease;
      }

      .session-chip:hover {
        border-color: #b7c0ba;
        background: #f6f8f7;
      }

      .session-chip.active {
        background: var(--home-shade);
        color: #ffffff;
        border-color: transparent;
      }

      /* Time Slots List for Selected Date */
      .day-slots-list {
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        padding-right: 4px;
        display: flex;
        flex-direction: column;
        gap: 0.55rem;
      }

      .day-slots-list::-webkit-scrollbar {
        width: 6px;
      }
      .day-slots-list::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 999px;
      }

      .slot-item-card {
        border: 1px solid var(--line);
        border-radius: 10px;
        padding: 0.75rem 0.9rem;
        background: #ffffff;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.85rem;
        transition: border-color 0.15s ease;
        flex-shrink: 0;
      }

      .slot-item-card:hover {
        border-color: #c5cec8;
        box-shadow: none;
        transform: none;
      }

      .slot-time-col {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        min-width: 140px;
        padding-right: 0.85rem;
        border-right: 1px solid var(--line);
      }

      .slot-time-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-family: 'Outfit', 'Inter', sans-serif;
        font-size: 0.86rem;
        font-weight: 650;
        color: var(--ink);
      }

      .slot-time-badge svg {
        color: var(--muted);
        flex-shrink: 0;
      }

      .slot-session-lbl {
        font-size: 0.68rem;
        font-weight: 500;
        color: var(--muted);
        margin-top: 0.15rem;
      }

      .slot-details-col {
        flex: 1;
        min-width: 0;
      }

      .slot-dept-tag {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 0.66rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        padding: 1px 7px;
        border-radius: 999px;
        margin-bottom: 0.2rem;
      }

      .tag-general,
      .tag-neuro,
      .tag-psych {
        background: transparent;
        color: var(--muted);
        padding: 0;
        letter-spacing: 0.06em;
      }

      .slot-service-title {
        margin: 0;
        font-size: 0.9rem;
        font-weight: 650;
        color: var(--ink);
        line-height: 1.25;
      }

      .slot-capacity-bar {
        display: flex;
        align-items: center;
        gap: 0.45rem;
        margin-top: 0.35rem;
      }

      .capacity-track {
        width: 80px;
        height: 5px;
        background: #e6ebe8;
        border-radius: 999px;
        overflow: hidden;
      }

      .capacity-fill {
        height: 100%;
        background: var(--home-shade);
        border-radius: 999px;
      }

      .capacity-txt {
        font-size: 0.7rem;
        font-weight: 600;
        color: var(--ink-soft);
      }

      .slot-action-col {
        flex-shrink: 0;
      }

      /* Availability Badge (No Booking) */
      .slot-status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-family: 'Inter', sans-serif;
        font-size: 0.76rem;
        font-weight: 700;
        padding: 0.45rem 0.85rem;
        border-radius: 999px;
        white-space: nowrap;
      }

      .slot-status-badge.avail {
        background: transparent;
        color: var(--ink);
        border: 1px solid var(--line-strong);
      }

      .slot-status-badge.full {
        background: transparent;
        color: #7a4545;
        border: 1px solid #e4d4d4;
      }

      .slot-status-dot {
        width: 5px;
        height: 5px;
        border-radius: 1px;
        background: currentColor;
      }

      .empty-day-state {
        text-align: center;
        padding: 2.5rem 1.5rem;
        color: var(--muted);
      }

      .empty-day-state h4 {
        margin: 0.5rem 0 0.25rem;
        color: var(--ink);
        font-size: 1rem;
      }

      @media (max-width: 1040px) {
        html, body {
          overflow: auto;
          height: auto;
        }
        .sched-page-wrap {
          height: auto;
          overflow: visible;
        }
        .sched-main-grid {
          grid-template-columns: 1fr;
          height: auto;
        }
        .calendar-panel-card,
        .day-schedule-card {
          height: auto;
        }
        .cal-days-grid {
          grid-auto-rows: minmax(64px, auto);
        }
        .day-slots-list {
          max-height: 480px;
        }
      }

      @media (max-width: 640px) {
        .sched-page-wrap {
          padding: 0.75rem;
        }
        .sched-top-bar {
          flex-wrap: wrap;
        }
        .sched-controls-right,
        .sched-search-box,
        .sched-search-input {
          width: 100%;
        }
        .cal-day-cell {
          min-height: 52px;
          padding: 0.25rem;
        }
        .cal-slot-pill {
          font-size: 0.55rem;
          padding: 1px 3px;
        }
        .slot-item-card {
          flex-direction: column;
          align-items: flex-start;
        }
        .slot-time-col {
          border-right: none;
          border-bottom: 1px solid var(--line);
          padding-right: 0;
          padding-bottom: 0.4rem;
          width: 100%;
        }
        .slot-action-col {
          width: 100%;
        }
        .slot-status-badge {
          width: 100%;
          justify-content: center;
        }
      }
    </style>
  </head>
  <body>
    <main class="sched-page-wrap">
      <!-- Top Header Bar -->
      <div class="sched-top-bar">
        <div class="sched-brand-left">
          <div class="sched-title-group">
            <h1>Clinic Schedule Calendar</h1>
            <div class="sched-subtitle-row">
              <span class="live-dot-badge">
                <span class="live-dot"></span>
                <span id="live-clock-txt">Loading time...</span>
              </span>
              <span>Select any future clinic date to view open consultation time blocks (Online schedules require staff approval).</span>
            </div>
          </div>
        </div>

        <div class="sched-controls-right">
          <div class="sched-search-box">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input
              type="text"
              id="sched-search-input"
              class="sched-search-input"
              placeholder="Search service or department..."
            />
          </div>
        </div>
      </div>

      <!-- Department Filter Pills -->
      <div class="sched-dept-tabs" role="tablist" aria-label="Department Filter">
        <button type="button" class="sched-dept-pill active" data-dept="ALL">
          <span class="dept-dot dot-all"></span>
          All Departments
        </button>
        <button type="button" class="sched-dept-pill" data-dept="General Medical">
          <span class="dept-dot dot-general"></span>
          General Medical
        </button>
        <button type="button" class="sched-dept-pill" data-dept="Neurology">
          <span class="dept-dot dot-neuro"></span>
          Neurology
        </button>
        <button type="button" class="sched-dept-pill" data-dept="OPU Psychiatry">
          <span class="dept-dot dot-psych"></span>
          OPU Psychiatry
        </button>
      </div>

      <!-- Two-Column Calendar + Selected Date Time Schedule -->
      <div class="sched-main-grid">
        <!-- Left Column: Monthly Calendar -->
        <section class="calendar-panel-card" aria-label="Clinic Days Calendar">
          <div class="cal-header-bar">
            <div>
              <h2 class="cal-month-title" id="cal-month-label">Month Year</h2>
              <div class="cal-month-sub">Click a highlighted future date to inspect clinic schedules</div>
            </div>
            <div class="cal-nav-group">
              <button type="button" class="cal-nav-btn" id="btn-prev-month" title="Previous Month">
                &larr; Prev
              </button>
              <button type="button" class="cal-nav-btn" id="btn-today-month" title="Jump to Next Open Clinic Day">
                Next Open Day
              </button>
              <button type="button" class="cal-nav-btn" id="btn-next-month" title="Next Month">
                Next &rarr;
              </button>
            </div>
          </div>

          <!-- Weekday Headers -->
          <div class="cal-weekdays-row">
            <div class="cal-weekday-lbl sun">Sun</div>
            <div class="cal-weekday-lbl">Mon</div>
            <div class="cal-weekday-lbl">Tue</div>
            <div class="cal-weekday-lbl">Wed</div>
            <div class="cal-weekday-lbl">Thu</div>
            <div class="cal-weekday-lbl">Fri</div>
            <div class="cal-weekday-lbl">Sat</div>
          </div>

          <!-- Calendar Days Grid -->
          <div class="cal-days-grid" id="cal-days-grid"></div>

          <!-- Legend -->
          <div class="cal-legend-bar">
            <div class="cal-legend-items">
              <span class="cal-legend-item">
                <span class="legend-swatch swatch-avail"></span>
                Open Clinic Day
              </span>
              <span class="cal-legend-item">
                <span class="legend-swatch swatch-selected"></span>
                Selected Date
              </span>
              <span class="cal-legend-item">
                <span class="legend-swatch swatch-closed"></span>
                Past / Sunday Closed
              </span>
            </div>
            <span>Clinic Hours: Mon–Sat, 8:00 AM – 5:00 PM</span>
          </div>
        </section>

        <!-- Right Column: Selected Date Available Times & Services -->
        <section class="day-schedule-card" aria-label="Selected Day Time Schedule">
          <div class="day-selected-banner">
            <div class="day-banner-left">
              <span class="day-banner-eyebrow" id="selected-day-eyebrow">CLINIC DAY SCHEDULE</span>
              <h3 class="day-banner-date" id="selected-date-heading">Select a Date</h3>
              <p class="day-banner-sub" id="selected-date-sub">Showing open clinic time blocks (Subject to Staff Approval)</p>
            </div>
            <div class="day-banner-badge">
              <strong id="selected-total-slots">0</strong>
              <span>Time Blocks</span>
            </div>
          </div>

          <!-- Session Time Filter Chips -->
          <div class="time-session-tabs">
            <button type="button" class="session-chip active" data-session="ALL">All Times (8:00 AM – 5:00 PM)</button>
            <button type="button" class="session-chip" data-session="AM">Morning (AM)</button>
            <button type="button" class="session-chip" data-session="PM">Afternoon (PM)</button>
          </div>

          <!-- Time Slots Container -->
          <div class="day-slots-list" id="day-slots-list"></div>
        </section>
      </div>
    </main>

    <script>
      document.addEventListener('DOMContentLoaded', () => {
        // Live clock
        const clockEl = document.getElementById('live-clock-txt');
        function updateClock() {
          const now = new Date();
          clockEl.textContent = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
        }
        updateClock();
        setInterval(updateClock, 1000);

        // Base hospital clinic templates per weekday (1=Mon ... 6=Sat)
        const baseTemplates = [
          // General Medical
          {
            dept: 'General Medical',
            tagClass: 'tag-general',
            dotColor: 'general',
            regPage: 'regis-general.php',
            service: 'General Medical Consultation',
            defaultDoc: 'Dr. Roberto Santos, MD',
            days: [1, 2, 3, 4, 5, 6],
            startTime: '08:00',
            endTime: '10:00',
            session: 'AM',
            totalSlots: 15
          },
          {
            dept: 'General Medical',
            tagClass: 'tag-general',
            dotColor: 'general',
            regPage: 'regis-general.php',
            service: 'Physical Examination & Preventive Healthcare',
            defaultDoc: 'Dr. Maria Elena Cruz, MD',
            days: [1, 2, 3, 4, 5],
            startTime: '10:00',
            endTime: '12:00',
            session: 'AM',
            totalSlots: 15
          },
          {
            dept: 'General Medical',
            tagClass: 'tag-general',
            dotColor: 'general',
            regPage: 'regis-general.php',
            service: 'Chronic Disease Management & Lab Requests',
            defaultDoc: 'Dr. Roberto Santos, MD',
            days: [1, 3, 5],
            startTime: '13:00',
            endTime: '16:00',
            session: 'PM',
            totalSlots: 12
          },
          // Neurology
          {
            dept: 'Neurology',
            tagClass: 'tag-neuro',
            dotColor: 'neuro',
            regPage: 'regis-neurology.php',
            service: 'Neurology Consultation & Headache Management',
            defaultDoc: 'Dr. Gabriel Villanueva, MD, FPNA',
            days: [1, 2, 4, 5],
            startTime: '08:30',
            endTime: '11:30',
            session: 'AM',
            totalSlots: 10
          },
          {
            dept: 'Neurology',
            tagClass: 'tag-neuro',
            dotColor: 'neuro',
            regPage: 'regis-neurology.php',
            service: 'Stroke, Epilepsy & Neurological Diagnostic Testing',
            defaultDoc: 'Dr. Gabriel Villanueva, MD, FPNA',
            days: [2, 3, 4, 6],
            startTime: '13:30',
            endTime: '16:30',
            session: 'PM',
            totalSlots: 10
          },
          // OPU Psychiatry
          {
            dept: 'OPU Psychiatry',
            tagClass: 'tag-psych',
            dotColor: 'psych',
            regPage: 'regis-psychiatry.php',
            service: 'Psychotherapy & Psychological Assessment',
            defaultDoc: 'Dr. Angela Reyes, MD, FPAFP',
            days: [1, 2, 3, 4, 5],
            startTime: '08:00',
            endTime: '11:30',
            session: 'AM',
            totalSlots: 14
          },
          {
            dept: 'OPU Psychiatry',
            tagClass: 'tag-psych',
            dotColor: 'psych',
            regPage: 'regis-psychiatry.php',
            service: 'Child & Adolescent Psychology / Rehabilitation',
            defaultDoc: 'Dr. Marco Mendoza, MD',
            days: [1, 2, 3, 4, 5, 6],
            startTime: '13:00',
            endTime: '17:00',
            session: 'PM',
            totalSlots: 14
          }
        ];

        let apiDoctors = [];
        let activeDept = 'ALL';
        let activeSession = 'ALL';
        let searchQuery = '';

        // Initialize today & default selected date (tomorrow, or Monday if tomorrow is Sunday)
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        function getNextClinicDate(fromDate) {
          const d = new Date(fromDate);
          d.setDate(d.getDate() + 1);
          if (d.getDay() === 0) {
            d.setDate(d.getDate() + 1);
          }
          return d;
        }

        let selectedDate = getNextClinicDate(today);
        let currentMonth = selectedDate.getMonth();
        let currentYear = selectedDate.getFullYear();

        function toYMD(dateObj) {
          const y = dateObj.getFullYear();
          const m = String(dateObj.getMonth() + 1).padStart(2, '0');
          const d = String(dateObj.getDate()).padStart(2, '0');
          return `${y}-${m}-${d}`;
        }

        function formatTime12(time24) {
          if (!time24) return '';
          const [hStr, mStr] = time24.split(':');
          let h = parseInt(hStr, 10);
          const ampm = h >= 12 ? 'PM' : 'AM';
          h = h % 12 || 12;
          return `${h}:${mStr} ${ampm}`;
        }

        function normalizeDeptName(rawDept) {
          const lower = (rawDept || '').toLowerCase();
          if (lower.includes('psych') || lower.includes('opu')) return 'OPU Psychiatry';
          if (lower.includes('neuro')) return 'Neurology';
          return 'General Medical';
        }

        // Load live doctors from API to enrich schedule slots
        async function fetchApiSchedule() {
          try {
            const res = await fetch('/sdn/api/schedule.php').then(r => r.json());
            if (res && res.success && Array.isArray(res.data)) {
              apiDoctors = res.data;
            }
          } catch (err) {
            console.warn('Schedule API fallback used:', err);
          } finally {
            renderCalendar();
            renderSelectedDaySchedule();
          }
        }

        // Build schedule slots for any given future date
        function getSlotsForDate(dateObj) {
          const dow = dateObj.getDay(); // 0 = Sun
          if (dow === 0 || dateObj <= today) return [];

          const ymd = toYMD(dateObj);
          const daySeed = dateObj.getDate() + (dateObj.getMonth() + 1) * 7;

          // Combine base templates for this weekday
          const slots = baseTemplates
            .filter(tpl => tpl.days.includes(dow))
            .map((tpl, idx) => {
              // Check if an API doctor matches this department
              const matchingApiDocs = apiDoctors.filter(
                d => normalizeDeptName(d.department) === tpl.dept && (d.status || 'available').toLowerCase() !== 'leave'
              );
              const assignedDoc = matchingApiDocs.length > 0
                ? matchingApiDocs[idx % matchingApiDocs.length].name
                : tpl.defaultDoc;

              const bookedSim = (daySeed + idx * 3) % Math.max(4, Math.floor(tpl.totalSlots * 0.55));
              const openSlots = Math.max(3, tpl.totalSlots - bookedSim);

              return {
                dept: tpl.dept,
                tagClass: tpl.tagClass,
                dotColor: tpl.dotColor,
                regPage: tpl.regPage,
                service: tpl.service,
                doctor: assignedDoc,
                startTime: tpl.startTime,
                endTime: tpl.endTime,
                session: tpl.session,
                openSlots: openSlots,
                totalSlots: tpl.totalSlots,
                dateYMD: ymd
              };
            });

          // Also include any explicit date-specific API doctor schedules if set for this exact date
          apiDoctors.forEach(doc => {
            if (doc.schedule_date === ymd && doc.schedule_start && doc.schedule_end) {
              const deptName = normalizeDeptName(doc.department);
              const startHr = parseInt(doc.schedule_start.split(':')[0], 10);
              if ((doc.status || 'active').toLowerCase() !== 'leave') {
                slots.unshift({
                  dept: deptName,
                  tagClass: deptName === 'OPU Psychiatry' ? 'tag-psych' : deptName === 'Neurology' ? 'tag-neuro' : 'tag-general',
                  dotColor: deptName === 'OPU Psychiatry' ? 'psych' : deptName === 'Neurology' ? 'neuro' : 'general',
                  regPage: deptName === 'OPU Psychiatry' ? 'regis-psychiatry.php' : deptName === 'Neurology' ? 'regis-neurology.php' : 'regis-general.php',
                  service: doc.specialization || `${deptName} Consultation`,
                  doctor: doc.name,
                  startTime: doc.schedule_start.slice(0, 5),
                  endTime: doc.schedule_end.slice(0, 5),
                  session: startHr < 12 ? 'AM' : 'PM',
                  dateYMD: ymd
                });
              }
            }
          });

          // Filter by department, session, and search query
          return slots.filter(s => {
            const matchDept = activeDept === 'ALL' || s.dept === activeDept;
            const matchSession = activeSession === 'ALL' || s.session === activeSession;
            const q = searchQuery.trim().toLowerCase();
            const matchSearch = !q ||
              s.service.toLowerCase().includes(q) ||
              s.dept.toLowerCase().includes(q);
            return matchDept && matchSession && matchSearch;
          });
        }

        function renderCalendar() {
          const monthNames = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
          ];
          document.getElementById('cal-month-label').textContent = `${monthNames[currentMonth]} ${currentYear}`;

          // Disable Prev button if viewing current month & year
          const prevBtn = document.getElementById('btn-prev-month');
          prevBtn.disabled = (currentYear === today.getFullYear() && currentMonth <= today.getMonth());

          const gridEl = document.getElementById('cal-days-grid');
          gridEl.innerHTML = '';

          const firstDayOfMonth = new Date(currentYear, currentMonth, 1).getDay();
          const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
          const selectedYMD = toYMD(selectedDate);
          const todayYMD = toYMD(today);

          // Leading blank cells
          for (let i = 0; i < firstDayOfMonth; i++) {
            const empty = document.createElement('div');
            empty.className = 'cal-day-cell empty-cell';
            gridEl.appendChild(empty);
          }

          // Day cells
          for (let day = 1; day <= daysInMonth; day++) {
            const cellDate = new Date(currentYear, currentMonth, day);
            cellDate.setHours(0, 0, 0, 0);
            const cellYMD = toYMD(cellDate);
            const isToday = cellYMD === todayYMD;
            const isSelected = cellYMD === selectedYMD;
            const isPastOrToday = cellDate <= today;
            const isSunday = cellDate.getDay() === 0;

            const daySlots = getSlotsForDate(cellDate);
            const isAvailable = !isPastOrToday && !isSunday && daySlots.length > 0;

            const cell = document.createElement('button');
            cell.type = 'button';

            let cellClasses = 'cal-day-cell';
            if (isSelected) {
              cellClasses += ' selected-cell';
            } else if (isAvailable) {
              cellClasses += ' available-cell';
            } else {
              cellClasses += ' closed-cell';
            }
            cell.className = cellClasses;

            const uniqueDots = [...new Set(daySlots.map(s => s.dotColor))];
            const dotsHtml = uniqueDots.map(c => `<span class="dot-${c}"></span>`).join('');

            cell.innerHTML = `
              <div class="cal-day-top">
                <span class="cal-day-num">${day}</span>
                ${isToday ? '<span class="cal-today-tag">Today</span>' : `<div class="cal-dept-dots">${dotsHtml}</div>`}
              </div>
              <div class="cal-day-bottom">
                ${
                  isAvailable
                    ? `<span class="cal-slot-pill">${daySlots.length} ${daySlots.length === 1 ? 'Time' : 'Times'}</span>`
                    : `<span class="cal-closed-lbl">${isSunday ? 'Closed' : isToday ? 'Same-Day' : 'Past'}</span>`
                }
              </div>
            `;

            if (isAvailable) {
              cell.addEventListener('click', () => {
                selectedDate = cellDate;
                renderCalendar();
                renderSelectedDaySchedule();
              });
            } else {
              cell.disabled = true;
            }

            gridEl.appendChild(cell);
          }
        }

        function renderSelectedDaySchedule() {
          const dateHeading = document.getElementById('selected-date-heading');
          const dateSub = document.getElementById('selected-date-sub');
          const totalBadge = document.getElementById('selected-total-slots');
          const listEl = document.getElementById('day-slots-list');

          const formattedDate = selectedDate.toLocaleDateString('en-US', {
            weekday: 'long',
            month: 'long',
            day: 'numeric',
            year: 'numeric'
          });

          const slots = getSlotsForDate(selectedDate);

          dateHeading.textContent = formattedDate;
          dateSub.textContent = slots.length > 0
            ? `${slots.length} clinic time blocks open for booking request (Subject to Staff Approval)`
            : 'No matching clinic time blocks for the selected filter';
          totalBadge.textContent = slots.length;

          listEl.innerHTML = '';

          if (slots.length === 0) {
            listEl.innerHTML = `
              <div class="empty-day-state">
                <h4>No Open Time Blocks Found</h4>
                <p>Try selecting another future weekday/Saturday on the calendar or switching the department filter.</p>
              </div>
            `;
            return;
          }

          slots.forEach(slot => {
            const card = document.createElement('div');
            card.className = 'slot-item-card';
            card.innerHTML = `
              <div class="slot-time-col">
                <div class="slot-time-badge">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                  </svg>
                  <span>${formatTime12(slot.startTime)} – ${formatTime12(slot.endTime)}</span>
                </div>
                <span class="slot-session-lbl">${slot.session === 'AM' ? 'Morning Clinic Session' : 'Afternoon Clinic Session'}</span>
              </div>

              <div class="slot-details-col">
                <span class="slot-dept-tag ${slot.tagClass}">${slot.dept}</span>
                <h4 class="slot-service-title">${slot.service}</h4>
                <div class="slot-capacity-bar">
                  <span class="capacity-txt">Clinic Session &bull; Requires Staff Approval</span>
                </div>
              </div>

              <div class="slot-action-col">
                <span class="slot-status-badge avail">
                  <span class="slot-status-dot"></span>
                  Open (Requires Approval)
                </span>
              </div>
            `;
            listEl.appendChild(card);
          });
        }

        // Month navigation handlers
        document.getElementById('btn-prev-month').addEventListener('click', () => {
          if (currentYear === today.getFullYear() && currentMonth <= today.getMonth()) return;
          currentMonth--;
          if (currentMonth < 0) {
            currentMonth = 11;
            currentYear--;
          }
          renderCalendar();
        });

        document.getElementById('btn-next-month').addEventListener('click', () => {
          currentMonth++;
          if (currentMonth > 11) {
            currentMonth = 0;
            currentYear++;
          }
          renderCalendar();
        });

        document.getElementById('btn-today-month').addEventListener('click', () => {
          selectedDate = getNextClinicDate(today);
          currentMonth = selectedDate.getMonth();
          currentYear = selectedDate.getFullYear();
          renderCalendar();
          renderSelectedDaySchedule();
        });

        // Department filter pill clicks
        document.querySelectorAll('.sched-dept-pill').forEach(btn => {
          btn.addEventListener('click', () => {
            document.querySelectorAll('.sched-dept-pill').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            activeDept = btn.getAttribute('data-dept') || 'ALL';
            renderCalendar();
            renderSelectedDaySchedule();
          });
        });

        // Session filter chip clicks
        document.querySelectorAll('.session-chip').forEach(chip => {
          chip.addEventListener('click', () => {
            document.querySelectorAll('.session-chip').forEach(c => c.classList.remove('active'));
            chip.classList.add('active');
            activeSession = chip.getAttribute('data-session') || 'ALL';
            renderSelectedDaySchedule();
          });
        });

        // Search input
        document.getElementById('sched-search-input').addEventListener('input', (e) => {
          searchQuery = e.target.value || '';
          renderCalendar();
          renderSelectedDaySchedule();
        });

        fetchApiSchedule();
      });
    </script>
  </body>
</html>
