<?php session_start(); ?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Clinic Schedule | MMWGH</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
      rel="stylesheet"
    />

    <style>
      :root {
        --color-bg: #f7f7f5;
        --color-surface: #ffffff;
        --color-text: #1a1f1c;
        --color-text-muted: #6b726d;
        --color-text-faint: #b3b8b4;
        --color-hairline: #e6e8e5;
        --color-accent: #166534;
        --color-hover: #f0f2ef;
        --color-dot-general: #0f9d74;
        --color-dot-neuro: #2b7fd4;
        --color-dot-psych: #7c4dcc;
        --font-sans: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      }

      *, *::before, *::after {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
      }

      html, body {
        margin: 0;
        padding: 0;
        background-color: var(--color-bg);
        color: var(--color-text);
        font-family: var(--font-sans);
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        overflow-x: hidden;
      }

      /* ── Page Wrapper ── */
      .sched-app {
        max-width: 1120px;
        margin: 0 auto;
        padding: 32px 24px;
        box-sizing: border-box;
      }

      /* ── Header ── */
      .sched-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 24px;
      }

      .sched-header-left {
        display: flex;
        align-items: flex-start;
        gap: 12px;
      }

      .btn-home {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 8px;
        border: none;
        background: transparent;
        color: var(--color-text-muted);
        text-decoration: none;
        transition: background-color 0.15s ease, color 0.15s ease;
        flex-shrink: 0;
        margin-top: 2px;
      }

      .btn-home:hover {
        background-color: var(--color-hover);
        color: var(--color-text);
      }

      .btn-home:focus-visible {
        outline: 2px solid var(--color-accent);
        outline-offset: 2px;
      }

      .sched-title-wrap {
        display: flex;
        flex-direction: column;
        gap: 4px;
      }

      .sched-title {
        font-size: 24px;
        font-weight: 600;
        color: var(--color-text);
        line-height: 1.25;
        margin: 0;
      }

      .sched-subtitle {
        font-size: 14px;
        color: var(--color-text-muted);
        line-height: 1.45;
        margin: 0;
      }

      .sched-search-wrap {
        position: relative;
        display: flex;
        align-items: center;
        flex-shrink: 0;
      }

      .sched-search-icon {
        position: absolute;
        left: 12px;
        width: 16px;
        height: 16px;
        color: var(--color-text-muted);
        pointer-events: none;
      }

      .sched-search-input {
        width: 280px;
        height: 40px;
        padding: 0 12px 0 36px;
        font-family: var(--font-sans);
        font-size: 14px;
        color: var(--color-text);
        background: var(--color-surface);
        border: 1px solid var(--color-hairline);
        border-radius: 8px;
        outline: none;
        transition: border-color 0.15s ease;
      }

      .sched-search-input:hover {
        border-color: #cbd0cb;
      }

      .sched-search-input:focus {
        border-color: var(--color-accent);
      }

      .sched-search-input::placeholder {
        color: var(--color-text-faint);
      }

      /* ── Department Filter Tabs ── */
      .dept-tabs-nav {
        width: 100%;
        border-bottom: 1px solid var(--color-hairline);
        margin-top: 24px;
        margin-bottom: 24px;
      }

      .dept-tabs-track {
        display: flex;
        align-items: center;
        gap: 28px;
        overflow-x: auto;
        scrollbar-width: none;
      }

      .dept-tabs-track::-webkit-scrollbar {
        display: none;
      }

      .dept-tab {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 0 0 12px 0;
        border: none;
        background: transparent;
        font-family: var(--font-sans);
        font-size: 14px;
        font-weight: 500;
        color: var(--color-text-muted);
        cursor: pointer;
        position: relative;
        white-space: nowrap;
        transition: color 0.15s ease;
      }

      .dept-tab:hover {
        color: var(--color-text);
      }

      .dept-tab:focus-visible {
        outline: 2px solid var(--color-accent);
        outline-offset: 4px;
      }

      .dept-tab.active {
        color: var(--color-text);
        font-weight: 600;
      }

      .dept-tab.active::after {
        content: '';
        position: absolute;
        bottom: -1px;
        left: 0;
        right: 0;
        height: 2px;
        background-color: var(--color-accent);
      }

      .dept-tab-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        flex-shrink: 0;
      }

      .dot-general { background-color: var(--color-dot-general); }
      .dot-neuro { background-color: var(--color-dot-neuro); }
      .dot-psych { background-color: var(--color-dot-psych); }

      /* ── Main Schedule Card (Single White Card) ── */
      .schedule-card {
        background: var(--color-surface);
        border: 1px solid var(--color-hairline);
        border-radius: 14px;
        display: flex;
        min-height: 560px;
        overflow: hidden;
      }

      /* ── Left Column: Calendar ── */
      .calendar-col {
        width: 400px;
        flex-shrink: 0;
        padding: 28px;
        border-right: 1px solid var(--color-hairline);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
      }

      .cal-top-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
      }

      .cal-month-wrap {
        display: flex;
        align-items: baseline;
        gap: 12px;
      }

      .cal-month-title {
        font-size: 18px;
        font-weight: 600;
        color: var(--color-text);
        margin: 0;
        line-height: 1.2;
      }

      .btn-today-link {
        border: none;
        background: transparent;
        color: var(--color-accent);
        font-family: var(--font-sans);
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        padding: 0;
        text-decoration: none;
      }

      .btn-today-link:hover {
        text-decoration: underline;
      }

      .cal-nav-buttons {
        display: flex;
        align-items: center;
        gap: 6px;
      }

      .cal-nav-btn {
        width: 32px;
        height: 32px;
        border: 1px solid var(--color-hairline);
        border-radius: 8px;
        background: var(--color-surface);
        color: var(--color-text);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background-color 0.15s ease;
      }

      .cal-nav-btn:hover:not(:disabled) {
        background-color: var(--color-hover);
      }

      .cal-nav-btn:disabled {
        opacity: 0.35;
        cursor: not-allowed;
      }

      .cal-nav-btn:focus-visible {
        outline: 2px solid var(--color-accent);
        outline-offset: 1px;
      }

      /* Weekday labels */
      .cal-weekdays {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        margin-bottom: 8px;
      }

      .cal-weekday {
        text-align: center;
        font-size: 11px;
        font-weight: 600;
        color: var(--color-text-muted);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 4px 0;
      }

      /* 7-column Day Grid */
      .cal-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        grid-auto-rows: 52px;
        min-height: 0;
      }

      .cal-cell {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        background: none;
        border: none;
        padding: 0;
        cursor: default;
        outline: none;
      }

      .cal-cell.is-empty {
        pointer-events: none;
      }

      .day-circle {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        position: relative;
        transition: background-color 0.15s ease, color 0.15s ease;
      }

      .day-num {
        font-size: 14px;
        font-weight: 500;
        line-height: 1;
        color: var(--color-text-faint);
      }

      /* Open Day */
      .cal-cell.is-open {
        cursor: pointer;
      }

      .cal-cell.is-open .day-num {
        color: var(--color-text);
        font-weight: 600;
      }

      .cal-cell.is-open:hover .day-circle {
        background-color: var(--color-hover);
      }

      .cal-cell:focus-visible .day-circle {
        outline: 2px solid var(--color-accent);
        outline-offset: 2px;
      }

      /* Today: 1.5px dark ring */
      .cal-cell.is-today .day-circle {
        box-shadow: inset 0 0 0 1.5px var(--color-text);
      }

      /* Selected: filled green #166534 with white number and white dots */
      .cal-cell.is-selected .day-circle {
        background-color: var(--color-accent) !important;
        box-shadow: none !important;
      }

      .cal-cell.is-selected .day-num {
        color: #ffffff !important;
      }

      .cal-cell.is-selected .day-dot {
        background-color: #ffffff !important;
      }

      /* Department Dots */
      .day-dots {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 2.5px;
        position: absolute;
        bottom: 4px;
        height: 4px;
      }

      .day-dot {
        width: 4px;
        height: 4px;
        border-radius: 50%;
        flex-shrink: 0;
      }

      /* Disabled / Unavailable / Past */
      .cal-cell.is-disabled {
        pointer-events: none;
      }

      /* Calendar Footer */
      .cal-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 20px;
        padding-top: 16px;
        border-top: 1px solid var(--color-hairline);
        font-size: 12px;
        color: var(--color-text-muted);
      }

      .btn-next-open-link {
        border: none;
        background: transparent;
        color: var(--color-accent);
        font-family: var(--font-sans);
        font-size: 12px;
        font-weight: 500;
        cursor: pointer;
        padding: 0;
        text-decoration: none;
      }

      .btn-next-open-link:hover {
        text-decoration: underline;
      }

      .cal-hours {
        color: var(--color-text-muted);
      }

      /* ── Right Column: Agenda ── */
      .agenda-col {
        flex: 1;
        min-width: 0;
        padding: 28px;
        display: flex;
        flex-direction: column;
      }

      .agenda-header {
        margin-bottom: 16px;
      }

      .agenda-title {
        font-size: 22px;
        font-weight: 600;
        color: var(--color-text);
        line-height: 1.25;
        margin: 0 0 4px 0;
      }

      .agenda-count {
        font-size: 14px;
        color: var(--color-text-muted);
        margin: 0;
      }

      /* Time of day tabs */
      .agenda-session-tabs {
        display: inline-flex;
        align-items: center;
        gap: 20px;
        margin-bottom: 8px;
        border-bottom: 1px solid var(--color-hairline);
        width: 100%;
      }

      .session-tab {
        border: none;
        background: transparent;
        font-family: var(--font-sans);
        font-size: 13px;
        font-weight: 500;
        color: var(--color-text-muted);
        cursor: pointer;
        padding: 0 0 8px 0;
        position: relative;
        transition: color 0.15s ease;
      }

      .session-tab:hover {
        color: var(--color-text);
      }

      .session-tab:focus-visible {
        outline: 2px solid var(--color-accent);
        outline-offset: 4px;
      }

      .session-tab.active {
        color: var(--color-text);
        font-weight: 600;
      }

      .session-tab.active::after {
        content: '';
        position: absolute;
        bottom: -1px;
        left: 0;
        right: 0;
        height: 2px;
        background-color: var(--color-accent);
      }

      /* Time block list */
      .agenda-slots-scroll {
        flex: 1;
        overflow-y: auto;
        overflow-x: hidden;
        scrollbar-width: none;
        -ms-overflow-style: none;
        transition: opacity 150ms ease-out;
      }

      .agenda-slots-scroll::-webkit-scrollbar {
        display: none;
      }

      .agenda-slots-scroll.is-updating {
        opacity: 0.4;
      }

      /* Plain rows */
      .agenda-row {
        display: flex;
        align-items: flex-start;
        gap: 24px;
        padding: 20px 0;
        border-bottom: 1px solid var(--color-hairline);
      }

      .agenda-row:last-child {
        border-bottom: none;
      }

      .agenda-time-col {
        width: 140px;
        flex-shrink: 0;
      }

      .agenda-time-range {
        font-size: 15px;
        font-weight: 600;
        color: var(--color-text);
        line-height: 1.3;
        white-space: nowrap;
      }

      .agenda-duration {
        font-size: 12px;
        color: var(--color-text-muted);
        margin-top: 3px;
      }

      .agenda-details-col {
        flex: 1;
        min-width: 0;
      }

      .agenda-dept-line {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        color: var(--color-text-muted);
        margin-bottom: 3px;
      }

      .agenda-service-title {
        font-size: 16px;
        font-weight: 600;
        color: var(--color-text);
        line-height: 1.35;
        margin: 0;
      }

      .agenda-service-desc {
        font-size: 14px;
        color: var(--color-text-muted);
        line-height: 1.45;
        margin: 4px 0 0 0;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
      }

      /* Empty State */
      .agenda-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 48px 16px;
        color: var(--color-text-muted);
        text-align: center;
      }

      .agenda-empty-icon {
        width: 24px;
        height: 24px;
        margin-bottom: 8px;
        color: var(--color-text-faint);
      }

      .agenda-empty-text {
        font-size: 14px;
        margin: 0;
      }

      /* ── Responsive (< 900px) ── */
      @media (max-width: 900px) {
        .sched-app {
          padding: 24px 16px;
        }

        .schedule-card {
          flex-direction: column;
          min-height: auto;
        }

        .calendar-col {
          width: 100%;
          border-right: none;
          border-bottom: 1px solid var(--color-hairline);
          padding: 24px 20px;
        }

        .cal-grid {
          grid-auto-rows: 48px;
        }

        .agenda-col {
          padding: 24px 20px;
        }
      }

      /* ── Responsive (< 600px) ── */
      @media (max-width: 600px) {
        .sched-header {
          flex-direction: column;
          gap: 16px;
        }

        .sched-search-wrap,
        .sched-search-input {
          width: 100%;
        }

        .agenda-row {
          flex-direction: column;
          gap: 8px;
        }

        .agenda-time-col {
          width: 100%;
        }
      }

      @media (prefers-reduced-motion: reduce) {
        *, *::before, *::after {
          transition: none !important;
          animation: none !important;
        }
      }
    </style>
  </head>
  <body>
    <main class="sched-app">
      <!-- ── Plain Header Row ── -->
      <header class="sched-header">
        <div class="sched-header-left">
          <a href="/sdn/index.php" class="btn-home" aria-label="Back to Homepage" title="Home">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
              <polyline points="9 22 9 12 15 12 15 22"></polyline>
            </svg>
          </a>
          <div class="sched-title-wrap">
            <h1 class="sched-title">Clinic Schedule</h1>
            <p class="sched-subtitle">Select a date to view open consultation time blocks. Online schedules require staff approval.</p>
          </div>
        </div>

        <div class="sched-search-wrap">
          <svg class="sched-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
          <input
            type="text"
            id="sched-search-input"
            class="sched-search-input"
            placeholder="Search service or department..."
            aria-label="Search service or department"
          />
        </div>
      </header>

      <!-- ── Department Filter Tabs ── -->
      <nav class="dept-tabs-nav" role="tablist" aria-label="Department filters">
        <div class="dept-tabs-track">
          <button type="button" class="dept-tab active" data-dept="ALL" role="tab" aria-selected="true">
            All
          </button>
          <button type="button" class="dept-tab" data-dept="General Medical" role="tab" aria-selected="false">
            <span class="dept-tab-dot dot-general" aria-hidden="true"></span>
            General Medical
          </button>
          <button type="button" class="dept-tab" data-dept="Neurology" role="tab" aria-selected="false">
            <span class="dept-tab-dot dot-neuro" aria-hidden="true"></span>
            Neurology
          </button>
          <button type="button" class="dept-tab" data-dept="Psychiatry" role="tab" aria-selected="false">
            <span class="dept-tab-dot dot-psych" aria-hidden="true"></span>
            Psychiatry
          </button>
        </div>
      </nav>

      <!-- ── Main Content Card (Single White Card) ── -->
      <div class="schedule-card">
        <!-- Calendar Column (Left) -->
        <section class="calendar-col" aria-label="Clinic Days Calendar">
          <div>
            <div class="cal-top-row">
              <div class="cal-month-wrap">
                <h2 class="cal-month-title" id="cal-month-label">November 2026</h2>
                <button type="button" class="btn-today-link" id="btn-today">Today</button>
              </div>
              <div class="cal-nav-buttons">
                <button type="button" class="cal-nav-btn" id="btn-prev-month" aria-label="Previous Month" title="Previous Month">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6"></polyline>
                  </svg>
                </button>
                <button type="button" class="cal-nav-btn" id="btn-next-month" aria-label="Next Month" title="Next Month">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 18 15 12 9 6"></polyline>
                  </svg>
                </button>
              </div>
            </div>

            <!-- Weekdays -->
            <div class="cal-weekdays" aria-hidden="true">
              <div class="cal-weekday">Sun</div>
              <div class="cal-weekday">Mon</div>
              <div class="cal-weekday">Tue</div>
              <div class="cal-weekday">Wed</div>
              <div class="cal-weekday">Thu</div>
              <div class="cal-weekday">Fri</div>
              <div class="cal-weekday">Sat</div>
            </div>

            <!-- Day Grid -->
            <div class="cal-grid" id="cal-days-grid" role="grid" aria-label="Month calendar days"></div>
          </div>

          <!-- Quiet Footer -->
          <footer class="cal-footer">
            <button type="button" class="btn-next-open-link" id="btn-next-open">Next open day</button>
            <span class="cal-hours">Mon–Sat, 8:00 AM – 5:00 PM</span>
          </footer>
        </section>

        <!-- Agenda Column (Right) -->
        <section class="agenda-col" aria-label="Selected Day Schedule">
          <div class="agenda-header">
            <h3 class="agenda-title" id="selected-date-heading">Saturday, November 14, 2026</h3>
            <p class="agenda-count" id="selected-date-sub">3 time blocks available</p>
          </div>

          <!-- Time-of-day tabs -->
          <div class="agenda-session-tabs" role="group" aria-label="Time of day filter">
            <button type="button" class="session-tab active" data-session="ALL">All times</button>
            <button type="button" class="session-tab" data-session="AM">Morning</button>
            <button type="button" class="session-tab" data-session="PM">Afternoon</button>
          </div>

          <!-- Plain rows list -->
          <div class="agenda-slots-scroll" id="day-slots-list" role="region" aria-live="polite"></div>
        </section>
      </div>
    </main>

    <script>
      document.addEventListener('DOMContentLoaded', () => {
        // Clinic templates per weekday (1=Mon ... 6=Sat) - "OPU" removed everywhere
        const baseTemplates = [
          // General Medical
          {
            dept: 'General Medical',
            dotClass: 'dot-general',
            service: 'General Medical Consultation',
            desc: 'Comprehensive outpatient diagnosis, preventive health check-up, and family medicine guidance.',
            days: [1, 2, 3, 4, 5, 6],
            startTime: '08:00',
            endTime: '10:00',
            session: 'AM',
            totalSlots: 15
          },
          {
            dept: 'General Medical',
            dotClass: 'dot-general',
            service: 'Physical Examination & Preventive Healthcare',
            desc: 'Medical clearance, diagnostic physical evaluation, and routine adult wellness screening.',
            days: [1, 2, 3, 4, 5],
            startTime: '10:00',
            endTime: '12:00',
            session: 'AM',
            totalSlots: 15
          },
          {
            dept: 'General Medical',
            dotClass: 'dot-general',
            service: 'Chronic Disease Management & Lab Requests',
            desc: 'Follow-up for hypertension, diabetes, routine maintenance prescriptions, and laboratory follow-ups.',
            days: [1, 3, 5],
            startTime: '13:00',
            endTime: '16:00',
            session: 'PM',
            totalSlots: 12
          },
          // Neurology
          {
            dept: 'Neurology',
            dotClass: 'dot-neuro',
            service: 'Neurology Consultation & Headache Management',
            desc: 'Specialized diagnosis of chronic migraine, neuropathic pain, seizure disorders, and vertigo.',
            days: [1, 2, 4, 5],
            startTime: '08:30',
            endTime: '11:30',
            session: 'AM',
            totalSlots: 10
          },
          {
            dept: 'Neurology',
            dotClass: 'dot-neuro',
            service: 'Stroke, Epilepsy & Neurological Diagnostic Testing',
            desc: 'Post-stroke motor rehabilitation consultation, EEG evaluation, and neuro-diagnostic assessment.',
            days: [2, 3, 4, 6],
            startTime: '13:30',
            endTime: '16:30',
            session: 'PM',
            totalSlots: 10
          },
          // Psychiatry
          {
            dept: 'Psychiatry',
            dotClass: 'dot-psych',
            service: 'Psychotherapy & Psychological Assessment',
            desc: 'Adult psychiatric evaluation, mood disorder management, anxiety counseling, and mental wellness therapy.',
            days: [1, 2, 3, 4, 5],
            startTime: '08:00',
            endTime: '11:30',
            session: 'AM',
            totalSlots: 14
          },
          {
            dept: 'Psychiatry',
            dotClass: 'dot-psych',
            service: 'Child & Adolescent Psychology / Rehabilitation',
            desc: 'Behavioral assessments, youth developmental support, addiction counseling, and psychosocial rehabilitation.',
            days: [1, 2, 3, 4, 5, 6],
            startTime: '13:00',
            endTime: '17:00',
            session: 'PM',
            totalSlots: 14
          }
        ];

        let apiSchedules = [];
        let activeDept = 'ALL';
        let activeSession = 'ALL';
        let searchQuery = '';

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

        function formatDuration(startTime, endTime) {
          if (!startTime || !endTime) return '';
          const [sh, sm] = startTime.split(':').map(Number);
          const [eh, em] = endTime.split(':').map(Number);
          const totalMin = (eh * 60 + em) - (sh * 60 + sm);
          if (totalMin <= 0) return '';
          const hrs = totalMin / 60;
          if (hrs === 1) return '1 hour';
          if (Number.isInteger(hrs)) return `${hrs} hours`;
          return `${hrs} hours`;
        }

        function normalizeDeptName(rawDept) {
          const lower = (rawDept || '').toLowerCase();
          if (lower.includes('psych') || lower.includes('opu')) return 'Psychiatry';
          if (lower.includes('neuro')) return 'Neurology';
          return 'General Medical';
        }

        // Fetch live doctor schedules if API endpoint exists
        async function fetchApiSchedule() {
          try {
            const res = await fetch('/sdn/api/schedule.php').then(r => r.json());
            if (res && res.success && Array.isArray(res.data)) {
              apiSchedules = res.data;
            }
          } catch (err) {
            // Keep fallback templates active
          } finally {
            renderCalendar();
            renderSelectedDaySchedule();
          }
        }

        // Build & filter time slots for any date
        function getSlotsForDate(dateObj) {
          const dow = dateObj.getDay(); // 0 = Sun
          if (dow === 0 || dateObj <= today) return [];

          const ymd = toYMD(dateObj);

          // Base slots for this weekday
          const slots = baseTemplates
            .filter(tpl => tpl.days.includes(dow))
            .map(tpl => ({
              dept: tpl.dept,
              dotClass: tpl.dotClass,
              service: tpl.service,
              desc: tpl.desc,
              startTime: tpl.startTime,
              endTime: tpl.endTime,
              session: tpl.session,
              totalSlots: tpl.totalSlots
            }));

          // Merge any date-specific schedules from API
          apiSchedules.forEach(item => {
            if (item.schedule_date === ymd && item.schedule_start && item.schedule_end) {
              const deptName = normalizeDeptName(item.department);
              const startHr = parseInt(item.schedule_start.split(':')[0], 10);
              if ((item.status || 'active').toLowerCase() !== 'leave') {
                slots.push({
                  dept: deptName,
                  dotClass: deptName === 'Psychiatry' ? 'dot-psych' : deptName === 'Neurology' ? 'dot-neuro' : 'dot-general',
                  service: item.specialization || `${deptName} Consultation`,
                  desc: item.service_description || 'Hospital outpatient consultation session.',
                  startTime: item.schedule_start.slice(0, 5),
                  endTime: item.schedule_end.slice(0, 5),
                  session: startHr < 12 ? 'AM' : 'PM',
                  totalSlots: 10
                });
              }
            }
          });

          // Strict chronological sort by start time
          slots.sort((a, b) => a.startTime.localeCompare(b.startTime));

          // Filter by department, session, and search query
          return slots.filter(s => {
            const matchDept = activeDept === 'ALL' || s.dept === activeDept;
            const matchSession = activeSession === 'ALL' || s.session === activeSession;
            const q = searchQuery.trim().toLowerCase();
            const matchSearch = !q ||
              s.service.toLowerCase().includes(q) ||
              s.dept.toLowerCase().includes(q) ||
              (s.desc && s.desc.toLowerCase().includes(q));
            return matchDept && matchSession && matchSearch;
          });
        }

        // Render Calendar
        function renderCalendar() {
          const monthNames = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
          ];
          document.getElementById('cal-month-label').textContent = `${monthNames[currentMonth]} ${currentYear}`;

          // Disable previous month button if past
          const prevBtn = document.getElementById('btn-prev-month');
          prevBtn.disabled = (currentYear === today.getFullYear() && currentMonth <= today.getMonth());

          const gridEl = document.getElementById('cal-days-grid');
          gridEl.innerHTML = '';

          const firstDayOfMonth = new Date(currentYear, currentMonth, 1).getDay();
          const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
          const selectedYMD = toYMD(selectedDate);
          const todayYMD = toYMD(today);

          // Leading empty cells
          for (let i = 0; i < firstDayOfMonth; i++) {
            const empty = document.createElement('div');
            empty.className = 'cal-cell is-empty';
            empty.setAttribute('aria-hidden', 'true');
            gridEl.appendChild(empty);
          }

          // Days in month
          for (let day = 1; day <= daysInMonth; day++) {
            const cellDate = new Date(currentYear, currentMonth, day);
            cellDate.setHours(0, 0, 0, 0);
            const cellYMD = toYMD(cellDate);
            const isToday = cellYMD === todayYMD;
            const isSelected = cellYMD === selectedYMD;
            const isPastOrToday = cellDate <= today;
            const isSunday = cellDate.getDay() === 0;

            const daySlots = getSlotsForDate(cellDate);
            const isOpen = !isPastOrToday && !isSunday && daySlots.length > 0;

            const cell = document.createElement('button');
            cell.type = 'button';
            cell.setAttribute('role', 'gridcell');
            cell.setAttribute('data-ymd', cellYMD);
            cell.setAttribute('tabindex', isOpen ? (isSelected ? '0' : '-1') : '-1');

            let cellClass = 'cal-cell';
            if (isSelected) {
              cellClass += ' is-selected';
              cell.setAttribute('aria-selected', 'true');
            } else {
              cell.setAttribute('aria-selected', 'false');
            }

            if (isToday) cellClass += ' is-today';

            if (isOpen) {
              cellClass += ' is-open';
              cell.setAttribute('aria-disabled', 'false');
              cell.title = `${daySlots.length} ${daySlots.length === 1 ? 'slot' : 'slots'} available`;
            } else {
              cellClass += ' is-disabled';
              cell.setAttribute('aria-disabled', 'true');
              cell.disabled = true;
            }

            cell.className = cellClass;

            // Generate department colored dots (when filtered, shows only that department's dot)
            const uniqueDots = [...new Set(daySlots.map(s => s.dotClass))].slice(0, 3);
            const dotsHtml = isOpen && uniqueDots.length > 0
              ? `<div class="day-dots" aria-hidden="true">${uniqueDots.map(c => `<span class="day-dot ${c}"></span>`).join('')}</div>`
              : '';

            cell.innerHTML = `
              <div class="day-circle">
                <span class="day-num">${day}</span>
                ${dotsHtml}
              </div>
            `;

            if (isOpen) {
              cell.addEventListener('click', () => {
                selectDate(cellDate);
              });
            }

            // Keyboard navigation
            cell.addEventListener('keydown', (e) => {
              handleCellKeydown(e, cellDate);
            });

            gridEl.appendChild(cell);
          }
        }

        function selectDate(dateObj) {
          selectedDate = dateObj;
          currentMonth = selectedDate.getMonth();
          currentYear = selectedDate.getFullYear();

          renderCalendar();
          renderSelectedDaySchedule();

          const activeCell = document.querySelector(`.cal-cell[data-ymd="${toYMD(selectedDate)}"]`);
          if (activeCell) activeCell.focus();
        }

        function handleCellKeydown(e, dateObj) {
          let targetDate = null;
          if (e.key === 'ArrowRight') targetDate = new Date(dateObj.getFullYear(), dateObj.getMonth(), dateObj.getDate() + 1);
          else if (e.key === 'ArrowLeft') targetDate = new Date(dateObj.getFullYear(), dateObj.getMonth(), dateObj.getDate() - 1);
          else if (e.key === 'ArrowDown') targetDate = new Date(dateObj.getFullYear(), dateObj.getMonth(), dateObj.getDate() + 7);
          else if (e.key === 'ArrowUp') targetDate = new Date(dateObj.getFullYear(), dateObj.getMonth(), dateObj.getDate() - 7);
          else if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            selectDate(dateObj);
            return;
          }

          if (targetDate) {
            e.preventDefault();
            if (targetDate > today && targetDate.getDay() !== 0) {
              if (targetDate.getMonth() !== currentMonth || targetDate.getFullYear() !== currentYear) {
                currentMonth = targetDate.getMonth();
                currentYear = targetDate.getFullYear();
              }
              selectDate(targetDate);
            }
          }
        }

        // Render Agenda
        function renderSelectedDaySchedule() {
          const dateHeading = document.getElementById('selected-date-heading');
          const dateCount = document.getElementById('selected-date-sub');
          const listEl = document.getElementById('day-slots-list');

          listEl.classList.add('is-updating');

          const formattedDate = selectedDate.toLocaleDateString('en-US', {
            weekday: 'long',
            month: 'long',
            day: 'numeric',
            year: 'numeric'
          });

          const slots = getSlotsForDate(selectedDate);

          dateHeading.textContent = formattedDate;
          dateCount.textContent = `${slots.length} ${slots.length === 1 ? 'time block' : 'time blocks'} available`;

          setTimeout(() => {
            listEl.innerHTML = '';
            listEl.classList.remove('is-updating');

            if (slots.length === 0) {
              listEl.innerHTML = `
                <div class="agenda-empty">
                  <svg class="agenda-empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="8" y1="12" x2="16" y2="12"></line>
                  </svg>
                  <p class="agenda-empty-text">No time blocks for this filter.</p>
                </div>
              `;
              return;
            }

            slots.forEach(slot => {
              const row = document.createElement('article');
              row.className = 'agenda-row';
              row.innerHTML = `
                <div class="agenda-time-col">
                  <div class="agenda-time-range">${formatTime12(slot.startTime)} – ${formatTime12(slot.endTime)}</div>
                  <div class="agenda-duration">${formatDuration(slot.startTime, slot.endTime)}</div>
                </div>
                <div class="agenda-details-col">
                  <div class="agenda-dept-line">
                    <span class="dept-tab-dot ${slot.dotClass}" aria-hidden="true"></span>
                    <span>${slot.dept}</span>
                  </div>
                  <h4 class="agenda-service-title">${slot.service}</h4>
                  ${slot.desc ? `<p class="agenda-service-desc">${slot.desc}</p>` : ''}
                </div>
              `;
              listEl.appendChild(row);
            });
          }, 150);
        }

        // Navigation Handlers
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

        document.getElementById('btn-today').addEventListener('click', () => {
          currentMonth = today.getMonth();
          currentYear = today.getFullYear();
          renderCalendar();
        });

        document.getElementById('btn-next-open').addEventListener('click', () => {
          selectDate(getNextClinicDate(today));
        });

        // Department filter tabs
        document.querySelectorAll('.dept-tab').forEach(btn => {
          btn.addEventListener('click', () => {
            document.querySelectorAll('.dept-tab').forEach(b => {
              b.classList.remove('active');
              b.setAttribute('aria-selected', 'false');
            });
            btn.classList.add('active');
            btn.setAttribute('aria-selected', 'true');
            activeDept = btn.getAttribute('data-dept') || 'ALL';
            renderCalendar();
            renderSelectedDaySchedule();
          });
        });

        // Time of day tabs
        document.querySelectorAll('.session-tab').forEach(btn => {
          btn.addEventListener('click', () => {
            document.querySelectorAll('.session-tab').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            activeSession = btn.getAttribute('data-session') || 'ALL';
            renderSelectedDaySchedule();
          });
        });

        // Search input with debounce
        let searchTimeout = null;
        document.getElementById('sched-search-input').addEventListener('input', (e) => {
          clearTimeout(searchTimeout);
          searchTimeout = setTimeout(() => {
            searchQuery = e.target.value || '';
            renderCalendar();
            renderSelectedDaySchedule();
          }, 120);
        });

        // Initial load
        fetchApiSchedule();
      });
    </script>
  </body>
</html>
