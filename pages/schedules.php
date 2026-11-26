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
      href="https://fonts.googleapis.com/css2?family=Figtree:wght@500;600;700;800&family=Noto+Sans:wght@400;500;600;700&display=swap"
      rel="stylesheet"
    />

    <style>
      :root {
        --color-bg: #eef5f0;
        --color-surface: #ffffff;
        --color-text: #0f2417;
        --color-text-muted: #47584e;
        --color-text-faint: #64748b;
        --color-hairline: #d4e2d8;
        --color-grid-line: #cbd5e1;
        --color-accent: #15803d;
        --color-accent-dark: #166534;
        --color-accent-soft: #e6f4ea;
        --color-hover: rgba(21, 128, 61, 0.08);

        /* Unavailable / Past gray tones */
        --color-gray-cell: #e5e7eb;
        --color-gray-outside: #f1f5f9;
        --color-gray-text: #94a3b8;

        /* Department solid bar colors (WCAG AA >= 4.5:1 with #fff) */
        --dept-general: #0f7a58;
        --dept-general-hover: #0c6347;
        --dept-neuro: #1d5fa7;
        --dept-neuro-hover: #174c86;
        --dept-psych: #6d3ab8;
        --dept-psych-hover: #582e96;

        --font-heading: 'Figtree', 'Segoe UI', sans-serif;
        --font-sans: 'Noto Sans', 'Segoe UI', sans-serif;
        --shadow-card: 0 2px 6px rgba(15, 36, 23, 0.05), 0 12px 32px rgba(15, 36, 23, 0.07);
        --radius: 14px;
      }

      *, *::before, *::after {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
      }

      body, body * { caret-color: transparent; }
      input, textarea, select, [contenteditable="true"] { caret-color: auto; }

      html, body {
        min-height: 100%;
        background-color: var(--color-bg);
        color: var(--color-text);
        font-family: var(--font-sans);
        font-size: 16px;
        line-height: 1.5;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
      }

      body {
        display: flex;
        flex-direction: column;
        min-height: 100dvh;
      }

      /* ── Top Header (matches homepage navbar gradient) ── */
      .sched-header {
        background: linear-gradient(90deg, #25ba50 0%, #7ed957 50%, #047023 100%);
        border-bottom: none;
        box-shadow: 0 6px 20px rgba(4, 112, 35, 0.24);
        position: sticky;
        top: 0;
        z-index: 30;
      }

      .sched-header-inner {
        max-width: 1240px;
        margin: 0 auto;
        padding: 14px 24px 0;
        display: flex;
        flex-direction: column;
        gap: 12px;
      }

      .sched-header-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
      }

      .sched-header-left {
        display: flex;
        align-items: center;
        gap: 12px;
      }

      .btn-home {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 44px;
        height: 44px;
        border-radius: 10px;
        border: 1px solid rgba(255, 255, 255, 0.42);
        background: rgba(255, 255, 255, 0.16);
        color: #ffffff;
        text-decoration: none;
        transition: background-color 0.15s ease, border-color 0.15s ease;
        flex-shrink: 0;
      }

      .btn-home:hover {
        background-color: rgba(255, 255, 255, 0.28);
        border-color: rgba(255, 255, 255, 0.65);
      }

      .btn-home:focus-visible {
        outline: 2px solid #ffffff;
        outline-offset: 2px;
      }

      .sched-title {
        font-family: var(--font-heading);
        font-size: 1.45rem;
        font-weight: 700;
        color: #ffffff;
        line-height: 1.2;
        letter-spacing: -0.02em;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.16);
      }

      .sched-subtitle {
        font-size: 0.875rem;
        color: rgba(255, 255, 255, 0.95);
        margin-top: 2px;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.14);
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
        height: 44px;
        padding: 0 14px 0 38px;
        font-family: var(--font-sans);
        font-size: 0.9375rem;
        color: var(--color-text);
        background: rgba(255, 255, 255, 0.96);
        border: 1px solid rgba(255, 255, 255, 0.7);
        border-radius: 10px;
        outline: none;
        transition: border-color 0.15s ease, background-color 0.15s ease, box-shadow 0.15s ease;
      }

      .sched-search-input:focus {
        border-color: #ffffff;
        background: #ffffff;
        box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.3);
      }

      .sched-search-input::placeholder {
        color: var(--color-text-faint);
      }

      /* ── Filter Tabs ── */
      .dept-tabs-track {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
        padding-bottom: 12px;
      }

      .dept-tab {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 40px;
        padding: 8px 14px;
        border: 1px solid rgba(255, 255, 255, 0.32);
        border-radius: 999px;
        background: rgba(0, 0, 0, 0.14);
        font-family: var(--font-sans);
        font-size: 0.875rem;
        font-weight: 600;
        color: #ffffff;
        cursor: pointer;
        white-space: nowrap;
        transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease;
      }

      .dept-tab:hover {
        color: #ffffff;
        background: rgba(0, 0, 0, 0.24);
        border-color: rgba(255, 255, 255, 0.55);
      }

      .dept-tab:focus-visible {
        outline: 2px solid #ffffff;
        outline-offset: 2px;
      }

      .dept-tab.active {
        color: #0b5324;
        background: #ffffff;
        border-color: #ffffff;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.14);
      }

      .dept-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        flex-shrink: 0;
      }

      .dept-tab:not(.active) .dept-dot {
        box-shadow: 0 0 0 1.5px rgba(255, 255, 255, 0.85);
      }

      .dot-general { background-color: var(--dept-general); }
      .dot-neuro { background-color: var(--dept-neuro); }
      .dot-psych { background-color: var(--dept-psych); }
      .dot-gray { background-color: #9ca3af; }

      /* ── Centered Calendar Workspace ── */
      .sched-main {
        flex: 1 1 auto;
        width: 100%;
        max-width: 1240px;
        margin: 0 auto;
        padding: 24px 24px 36px;
        display: flex;
        flex-direction: column;
        align-items: center;
      }

      .calendar-board {
        width: 100%;
        background: var(--color-surface);
        border: 1px solid var(--color-hairline);
        border-radius: var(--radius);
        box-shadow: var(--shadow-card);
        overflow: hidden;
      }

      /* Calendar Top Toolbar */
      .cal-toolbar {
        display: grid;
        grid-template-columns: 1fr auto 1fr;
        align-items: center;
        gap: 12px;
        padding: 16px 20px;
        background: var(--color-surface);
        border-bottom: 1px solid var(--color-hairline);
      }

      .cal-toolbar-left {
        display: flex;
        align-items: center;
        gap: 8px;
      }

      .cal-month-title {
        font-family: var(--font-heading);
        font-size: 1.35rem;
        font-weight: 700;
        color: var(--color-text);
        letter-spacing: -0.02em;
        text-align: center;
      }

      .cal-toolbar-right {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 8px;
      }

      .cal-btn {
        height: 40px;
        min-width: 40px;
        padding: 0 12px;
        border: 1px solid var(--color-hairline);
        border-radius: 10px;
        background: var(--color-surface);
        color: var(--color-text);
        font-family: var(--font-sans);
        font-size: 0.875rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        cursor: pointer;
        transition: background-color 0.15s ease, border-color 0.15s ease;
      }

      .cal-btn:hover:not(:disabled) {
        background: var(--color-hover);
        border-color: #b7dcc4;
      }

      .cal-btn:disabled {
        opacity: 0.4;
        cursor: not-allowed;
      }

      .cal-btn:focus-visible {
        outline: 2px solid var(--color-accent);
        outline-offset: 2px;
      }

      .cal-btn-today {
        color: var(--color-accent-dark);
        border-color: #b7dcc4;
        background: var(--color-accent-soft);
      }

      .cal-btn-today:hover {
        background: #d5eedd;
      }

      /* Legend Bar */
      .cal-legend {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        padding: 10px 20px;
        background: #f8fbf9;
        border-bottom: 1px solid var(--color-hairline);
        font-size: 0.8rem;
        color: var(--color-text-muted);
      }

      .cal-legend-items {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
      }

      .legend-item {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-weight: 600;
      }

      .legend-swatch {
        width: 12px;
        height: 12px;
        border-radius: 3px;
        flex-shrink: 0;
      }

      .swatch-general { background: var(--dept-general); }
      .swatch-neuro { background: var(--dept-neuro); }
      .swatch-psych { background: var(--dept-psych); }
      .swatch-unavailable {
        background: var(--color-gray-cell);
        border: 1px solid #cbd5e1;
      }

      /* Scroll wrapper for mobile */
      .cal-scroll-wrap {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
      }

      .cal-grid-frame {
        min-width: 780px;
      }

      /* Weekday Header Bar (inspired by concept 2) */
      .cal-weekdays {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        background: var(--color-accent);
        color: #ffffff;
      }

      .cal-weekday {
        text-align: center;
        font-family: var(--font-heading);
        font-size: 0.8125rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        padding: 11px 6px;
        border-right: 1px solid rgba(255, 255, 255, 0.16);
      }

      .cal-weekday:last-child {
        border-right: none;
      }

      /* Monthly Day Grid */
      .cal-grid {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        background: var(--color-grid-line);
        gap: 1px;
      }

      .cal-cell {
        min-height: 132px;
        background: var(--color-surface);
        padding: 8px 8px 10px;
        display: flex;
        flex-direction: column;
        gap: 6px;
        text-align: left;
        position: relative;
        transition: background-color 0.15s ease;
      }

      /* Past & Unavailable Days (Gray) */
      .cal-cell.is-unavailable {
        background: var(--color-gray-cell);
        color: var(--color-gray-text);
      }

      .cal-cell.is-outside {
        background: var(--color-gray-outside);
        color: #cbd5e1;
      }

      .cal-cell.is-open {
        cursor: pointer;
      }

      .cal-cell.is-open:hover {
        background: #f6fcf8;
      }

      .cal-cell.is-open:focus-visible {
        outline: 2px solid var(--color-accent);
        outline-offset: -2px;
        z-index: 2;
      }

      /* Cell Day Number Header */
      .cell-head {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        margin-bottom: 2px;
      }

      .cell-day-num {
        font-family: var(--font-heading);
        font-size: 0.95rem;
        font-weight: 700;
        line-height: 1;
        color: var(--color-text);
      }

      .cal-cell.is-unavailable .cell-day-num {
        color: #6b7280;
        font-weight: 600;
      }

      .cal-cell.is-outside .cell-day-num {
        color: #9ca3af;
        font-weight: 500;
      }

      .cell-today-pill {
        font-size: 0.65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        padding: 2px 6px;
        border-radius: 999px;
        background: var(--color-accent);
        color: #ffffff;
      }

      .cell-status-note {
        margin-top: auto;
        margin-bottom: auto;
        text-align: center;
        font-size: 0.72rem;
        font-weight: 600;
        color: #6b7280;
        letter-spacing: 0.01em;
      }

      /* Specialization Bars inside each day cell */
      .cell-bars {
        display: flex;
        flex-direction: column;
        gap: 5px;
        width: 100%;
      }

      .spec-bar {
        width: 100%;
        border: none;
        border-radius: 6px;
        padding: 5px 8px;
        color: #ffffff;
        font-family: var(--font-sans);
        font-size: 0.72rem;
        font-weight: 600;
        line-height: 1.25;
        text-align: left;
        display: flex;
        flex-direction: column;
        gap: 1px;
        cursor: pointer;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.12);
        transition: filter 0.14s ease, transform 0.14s ease;
      }

      .spec-bar:hover {
        filter: brightness(1.07);
        transform: translateY(-1px);
      }

      .spec-bar:focus-visible {
        outline: 2px solid #0f172a;
        outline-offset: 1px;
      }

      .spec-bar.is-general { background-color: var(--dept-general); }
      .spec-bar.is-neuro { background-color: var(--dept-neuro); }
      .spec-bar.is-psych { background-color: var(--dept-psych); }

      .spec-bar-title {
        display: block;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }

      .spec-bar-meta {
        font-size: 0.65rem;
        font-weight: 500;
        opacity: 0.92;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }

      .spec-more-btn {
        width: 100%;
        border: 1px dashed #94a3b8;
        background: #f8fafc;
        color: #334155;
        border-radius: 6px;
        padding: 4px 6px;
        font-size: 0.7rem;
        font-weight: 700;
        text-align: center;
        cursor: pointer;
        transition: background-color 0.14s ease;
      }

      .spec-more-btn:hover {
        background: var(--color-accent-soft);
        border-color: var(--color-accent);
        color: var(--color-accent-dark);
      }

      /* Footer Strip */
      .cal-board-footer {
        background: var(--color-accent-dark);
        color: rgba(255, 255, 255, 0.94);
        padding: 10px 20px;
        font-size: 0.8rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 8px;
      }

      /* ── Day Details Modal ── */
      .modal-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.48);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 16px;
        z-index: 60;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.16s ease;
      }

      .modal-backdrop.is-open {
        opacity: 1;
        pointer-events: auto;
      }

      .modal-card {
        width: 100%;
        max-width: 560px;
        max-height: 85vh;
        background: var(--color-surface);
        border: 1px solid rgba(15, 36, 23, 0.08);
        border-radius: 18px;
        box-shadow: 0 24px 56px rgba(15, 23, 42, 0.22);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        transform: translateY(8px) scale(0.99);
        transition: transform 0.18s cubic-bezier(0.2, 0.8, 0.2, 1);
      }

      .modal-backdrop.is-open .modal-card {
        transform: translateY(0) scale(1);
      }

      .modal-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        padding: 20px 24px 16px;
        background: #f8fbf9;
        border-bottom: 1px solid var(--color-hairline);
      }

      .modal-eyebrow {
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        color: var(--color-accent);
        margin-bottom: 2px;
      }

      .modal-title {
        font-family: var(--font-heading);
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--color-text);
        line-height: 1.25;
        letter-spacing: -0.02em;
      }

      .modal-sub {
        font-size: 0.82rem;
        color: var(--color-text-muted);
        margin-top: 3px;
      }

      .modal-close-btn {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        border: 1px solid var(--color-hairline);
        background: var(--color-surface);
        color: var(--color-text-muted);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        flex-shrink: 0;
        transition: background-color 0.15s ease, color 0.15s ease;
      }

      .modal-close-btn:hover {
        background: var(--color-hover);
        color: var(--color-text);
      }

      .modal-close-btn:focus-visible {
        outline: 2px solid var(--color-accent);
        outline-offset: 2px;
      }

      .modal-body {
        padding: 16px 24px 20px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 18px;
      }

      .modal-session-group {
        display: flex;
        flex-direction: column;
      }

      .modal-session-label {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--color-text-faint);
        padding-bottom: 8px;
        border-bottom: 1px solid var(--color-hairline);
      }

      .modal-slot-item {
        display: grid;
        grid-template-columns: 118px minmax(0, 1fr);
        gap: 14px;
        padding: 14px 0;
        border-bottom: 1px solid #eef3f0;
        align-items: start;
      }

      .modal-slot-item:last-child {
        border-bottom: none;
        padding-bottom: 2px;
      }

      .modal-slot-item.is-general { --slot-color: var(--dept-general); --slot-soft: #ecfdf5; }
      .modal-slot-item.is-neuro { --slot-color: var(--dept-neuro); --slot-soft: #eff6ff; }
      .modal-slot-item.is-psych { --slot-color: var(--dept-psych); --slot-soft: #f5f3ff; }

      .modal-slot-time-col {
        display: flex;
        flex-direction: column;
        gap: 2px;
        padding-left: 10px;
        border-left: 3px solid var(--slot-color);
        font-variant-numeric: tabular-nums;
      }

      .modal-slot-start {
        font-family: var(--font-heading);
        font-size: 0.9rem;
        font-weight: 700;
        color: var(--color-text);
        line-height: 1.2;
      }

      .modal-slot-end {
        font-size: 0.78rem;
        font-weight: 500;
        color: var(--color-text-faint);
        line-height: 1.25;
      }

      .modal-slot-content {
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 4px;
      }

      .modal-slot-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 8px;
        flex-wrap: wrap;
      }

      .modal-slot-service {
        font-family: var(--font-heading);
        font-size: 0.96rem;
        font-weight: 700;
        color: var(--color-text);
        line-height: 1.3;
      }

      .modal-slot-dept {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 2px 8px;
        border-radius: 999px;
        background: var(--slot-soft);
        color: var(--slot-color);
        font-size: 0.7rem;
        font-weight: 700;
        white-space: nowrap;
      }

      .modal-slot-desc {
        font-size: 0.82rem;
        color: var(--color-text-muted);
        line-height: 1.45;
      }

      .modal-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 12px 24px;
        background: #f8fbf9;
        border-top: 1px solid var(--color-hairline);
        flex-wrap: wrap;
      }

      .modal-footer-note {
        font-size: 0.78rem;
        color: var(--color-text-muted);
      }

      .modal-footer-actions {
        display: flex;
        align-items: center;
        gap: 8px;
      }

      .modal-btn-primary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 40px;
        padding: 0 16px;
        border-radius: 10px;
        background: var(--color-accent);
        color: #ffffff;
        font-size: 0.84rem;
        font-weight: 600;
        text-decoration: none;
        transition: background-color 0.15s ease;
      }

      .modal-btn-primary:hover {
        background: var(--color-accent-dark);
      }

      .modal-btn-primary:focus-visible {
        outline: 2px solid var(--color-accent);
        outline-offset: 2px;
      }

      @media (max-width: 560px) {
        .modal-header,
        .modal-body,
        .modal-footer {
          padding-left: 16px;
          padding-right: 16px;
        }
        .modal-slot-item {
          grid-template-columns: 1fr;
          gap: 8px;
        }
      }

      /* ── Responsive ── */
      @media (max-width: 900px) {
        .sched-header-inner,
        .sched-main {
          padding-left: 14px;
          padding-right: 14px;
        }

        .sched-header-row {
          flex-direction: column;
          align-items: stretch;
          gap: 12px;
        }

        .sched-search-wrap,
        .sched-search-input {
          width: 100%;
        }

        .cal-toolbar {
          grid-template-columns: auto 1fr auto;
          padding: 12px 14px;
        }

        .cal-month-title {
          font-size: 1.1rem;
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
    <header class="sched-header">
      <div class="sched-header-inner">
        <div class="sched-header-row">
          <div class="sched-header-left">
            <a href="/sdn/index.php" class="btn-home" aria-label="Back to Homepage" title="Home">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                <polyline points="9 22 9 12 15 12 15 22"></polyline>
              </svg>
            </a>
            <div>
              <h1 class="sched-title">Clinic Schedule</h1>
              <p class="sched-subtitle">Monthly calendar of available specializations per department. Select any open day to view full hours.</p>
            </div>
          </div>

          <div class="sched-search-wrap">
            <svg class="sched-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input
              type="search"
              id="sched-search-input"
              class="sched-search-input"
              placeholder="Search specialization or clinic"
              aria-label="Search specialization or clinic"
            />
          </div>
        </div>

        <nav aria-label="Clinic department">
          <div class="dept-tabs-track" role="group">
            <button type="button" class="dept-tab active" data-dept="ALL" aria-pressed="true">All clinics</button>
            <button type="button" class="dept-tab" data-dept="General Medical" aria-pressed="false">
              <span class="dept-dot dot-general" aria-hidden="true"></span>General Medical
            </button>
            <button type="button" class="dept-tab" data-dept="Neurology" aria-pressed="false">
              <span class="dept-dot dot-neuro" aria-hidden="true"></span>Neurology
            </button>
            <button type="button" class="dept-tab" data-dept="Psychiatry" aria-pressed="false">
              <span class="dept-dot dot-psych" aria-hidden="true"></span>Psychiatry
            </button>
          </div>
        </nav>
      </div>
    </header>

    <main class="sched-main">
      <section class="calendar-board" aria-label="Monthly Clinic Schedule Calendar">
        <div class="cal-toolbar">
          <div class="cal-toolbar-left">
            <button type="button" class="cal-btn cal-btn-today" id="btn-today">Today</button>
          </div>

          <h2 class="cal-month-title" id="cal-month-label">November 2026</h2>

          <div class="cal-toolbar-right">
            <button type="button" class="cal-btn" id="btn-prev-month" aria-label="Previous Month" title="Previous Month">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="15 18 9 12 15 6"></polyline>
              </svg>
            </button>
            <button type="button" class="cal-btn" id="btn-next-month" aria-label="Next Month" title="Next Month">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="9 18 15 12 9 6"></polyline>
              </svg>
            </button>
          </div>
        </div>

        <div class="cal-legend">
          <div class="cal-legend-items">
            <span class="legend-item"><span class="legend-swatch swatch-general" aria-hidden="true"></span>General Medical</span>
            <span class="legend-item"><span class="legend-swatch swatch-neuro" aria-hidden="true"></span>Neurology</span>
            <span class="legend-item"><span class="legend-swatch swatch-psych" aria-hidden="true"></span>Psychiatry</span>
            <span class="legend-item"><span class="legend-swatch swatch-unavailable" aria-hidden="true"></span>Past / Not Available</span>
          </div>
          <span>Online schedules require staff approval</span>
        </div>

        <div class="cal-scroll-wrap">
          <div class="cal-grid-frame">
            <div class="cal-weekdays" aria-hidden="true">
              <div class="cal-weekday">Sun</div>
              <div class="cal-weekday">Mon</div>
              <div class="cal-weekday">Tue</div>
              <div class="cal-weekday">Wed</div>
              <div class="cal-weekday">Thu</div>
              <div class="cal-weekday">Fri</div>
              <div class="cal-weekday">Sat</div>
            </div>
            <div class="cal-grid" id="cal-days-grid" role="grid" aria-label="Monthly clinic schedule"></div>
          </div>
        </div>

        <footer class="cal-board-footer">
          <span>Mariveles Mental Wellness and General Hospital &mdash; Clinic Schedule</span>
          <span>Mon&ndash;Sat &bull; Click any open date for full time blocks</span>
        </footer>
      </section>
    </main>

    <!-- Day Details Modal -->
    <div class="modal-backdrop" id="dayModal" role="dialog" aria-modal="true" aria-labelledby="dayModalTitle" hidden>
      <div class="modal-card">
        <div class="modal-header">
          <div>
            <div class="modal-eyebrow" id="dayModalEyebrow">Clinic Schedule</div>
            <h3 class="modal-title" id="dayModalTitle">Date</h3>
            <p class="modal-sub" id="dayModalSub">Available specializations</p>
          </div>
          <button type="button" class="modal-close-btn" id="dayModalClose" aria-label="Close day schedule">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>
        <div class="modal-body" id="dayModalBody"></div>
        <div class="modal-footer">
          <span class="modal-footer-note">Online schedules require staff approval.</span>
          <div class="modal-footer-actions">
            <a href="/sdn/pages/patient-login.php" class="modal-btn-primary">Book Appointment</a>
          </div>
        </div>
      </div>
    </div>

    <script>
      document.addEventListener('DOMContentLoaded', () => {
        const baseTemplates = [
          // General Medical
          {
            dept: 'General Medical',
            service: 'General Medical Consultation',
            shortLabel: 'General Medical',
            desc: 'Comprehensive outpatient diagnosis, preventive health check-up, and family medicine guidance.',
            days: [1, 2, 3, 4, 5, 6],
            startTime: '08:00',
            endTime: '10:00'
          },
          {
            dept: 'General Medical',
            service: 'Physical Examination & Preventive Healthcare',
            shortLabel: 'Physical Exam',
            desc: 'Medical clearance, diagnostic physical evaluation, and routine adult wellness screening.',
            days: [1, 2, 3, 4, 5],
            startTime: '10:00',
            endTime: '12:00'
          },
          {
            dept: 'General Medical',
            service: 'Chronic Disease Management & Lab Requests',
            shortLabel: 'Chronic Care',
            desc: 'Follow-up for hypertension, diabetes, routine maintenance prescriptions, and laboratory follow-ups.',
            days: [1, 3, 5],
            startTime: '13:00',
            endTime: '16:00'
          },
          // Neurology
          {
            dept: 'Neurology',
            service: 'Neurology Consultation & Headache Management',
            shortLabel: 'Neurology Consult',
            desc: 'Specialized diagnosis of chronic migraine, neuropathic pain, seizure disorders, and vertigo.',
            days: [1, 2, 4, 5],
            startTime: '08:30',
            endTime: '11:30'
          },
          {
            dept: 'Neurology',
            service: 'Stroke, Epilepsy & Neurological Diagnostic Testing',
            shortLabel: 'Stroke & Epilepsy',
            desc: 'Post-stroke motor rehabilitation consultation, EEG evaluation, and neuro-diagnostic assessment.',
            days: [2, 3, 4, 6],
            startTime: '13:30',
            endTime: '16:30'
          },
          // Psychiatry
          {
            dept: 'Psychiatry',
            service: 'Psychotherapy & Psychological Assessment',
            shortLabel: 'Psychotherapy',
            desc: 'Adult psychiatric evaluation, mood disorder management, anxiety counseling, and mental wellness therapy.',
            days: [1, 2, 3, 4, 5],
            startTime: '08:00',
            endTime: '11:30'
          },
          {
            dept: 'Psychiatry',
            service: 'Child & Adolescent Psychology / Rehabilitation',
            shortLabel: 'Child & Rehab Psych',
            desc: 'Behavioral assessments, youth developmental support, addiction counseling, and psychosocial rehabilitation.',
            days: [1, 2, 3, 4, 5, 6],
            startTime: '13:00',
            endTime: '17:00'
          }
        ];

        const monthNames = [
          'January', 'February', 'March', 'April', 'May', 'June',
          'July', 'August', 'September', 'October', 'November', 'December'
        ];

        let apiSchedules = [];
        let activeDept = 'ALL';
        let searchQuery = '';

        const today = new Date();
        today.setHours(0, 0, 0, 0);

        let currentMonth = today.getMonth();
        let currentYear = today.getFullYear();

        const gridEl = document.getElementById('cal-days-grid');
        const monthLabelEl = document.getElementById('cal-month-label');
        const prevBtn = document.getElementById('btn-prev-month');
        const nextBtn = document.getElementById('btn-next-month');
        const todayBtn = document.getElementById('btn-today');
        const modalEl = document.getElementById('dayModal');
        const modalTitleEl = document.getElementById('dayModalTitle');
        const modalSubEl = document.getElementById('dayModalSub');
        const modalBodyEl = document.getElementById('dayModalBody');
        const modalCloseBtn = document.getElementById('dayModalClose');

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
          if (lower.includes('psych') || lower.includes('opu')) return 'Psychiatry';
          if (lower.includes('neuro')) return 'Neurology';
          return 'General Medical';
        }

        function deptToneClass(dept) {
          if (dept === 'Psychiatry') return 'is-psych';
          if (dept === 'Neurology') return 'is-neuro';
          return 'is-general';
        }

        function escapeHtml(str) {
          return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
        }

        function getSlotsForDate(dateObj) {
          const dow = dateObj.getDay();
          if (dow === 0 || dateObj <= today) return [];

          const ymd = toYMD(dateObj);
          const slots = baseTemplates
            .filter(tpl => tpl.days.includes(dow))
            .map(tpl => ({
              dept: tpl.dept,
              service: tpl.service,
              shortLabel: tpl.shortLabel,
              desc: tpl.desc,
              startTime: tpl.startTime,
              endTime: tpl.endTime
            }));

          apiSchedules.forEach(item => {
            if (item.schedule_date === ymd && item.schedule_start && item.schedule_end) {
              if ((item.status || 'active').toLowerCase() === 'leave') return;
              const deptName = normalizeDeptName(item.department);
              const spec = item.specialization || `${deptName} Consultation`;
              slots.push({
                dept: deptName,
                service: spec,
                shortLabel: spec,
                desc: item.name ? `Attending: ${item.name}` : 'Hospital outpatient consultation session.',
                startTime: item.schedule_start.slice(0, 5),
                endTime: item.schedule_end.slice(0, 5)
              });
            }
          });

          slots.sort((a, b) => a.startTime.localeCompare(b.startTime));

          const q = searchQuery.trim().toLowerCase();
          return slots.filter(s => {
            const matchDept = activeDept === 'ALL' || s.dept === activeDept;
            const timeLabel = `${formatTime12(s.startTime)} ${formatTime12(s.endTime)}`.toLowerCase();
            const matchSearch = !q ||
              s.service.toLowerCase().includes(q) ||
              s.dept.toLowerCase().includes(q) ||
              timeLabel.includes(q);
            return matchDept && matchSearch;
          });
        }

        // Summarize specializations grouped per department for compact day bars
        function groupSpecializationsByDept(slots) {
          const deptOrder = ['General Medical', 'Neurology', 'Psychiatry'];
          const groups = new Map();

          slots.forEach(slot => {
            if (!groups.has(slot.dept)) {
              groups.set(slot.dept, []);
            }
            groups.get(slot.dept).push(slot);
          });

          const result = [];
          deptOrder.forEach(dept => {
            if (groups.has(dept)) {
              result.push({ dept, items: groups.get(dept) });
            }
          });
          return result;
        }

        function openDayModal(dateObj, slots) {
          const eyebrowEl = document.getElementById('dayModalEyebrow');
          const weekdayLabel = dateObj.toLocaleDateString('en-US', { weekday: 'long' });
          const dateLabel = dateObj.toLocaleDateString('en-US', {
            month: 'long',
            day: 'numeric',
            year: 'numeric'
          });
          if (eyebrowEl) {
            eyebrowEl.textContent = activeDept === 'ALL' ? weekdayLabel : `${weekdayLabel} · ${activeDept}`;
          }
          modalTitleEl.textContent = dateLabel;
          modalSubEl.textContent = `${slots.length} open consultation ${slots.length === 1 ? 'block' : 'blocks'}`;

          const renderSlotRow = (slot) => {
            const tone = deptToneClass(slot.dept);
            const showDeptTag = activeDept === 'ALL';
            return `
              <article class="modal-slot-item ${tone}">
                <div class="modal-slot-time-col">
                  <span class="modal-slot-start">${escapeHtml(formatTime12(slot.startTime))}</span>
                  <span class="modal-slot-end">to ${escapeHtml(formatTime12(slot.endTime))}</span>
                </div>
                <div class="modal-slot-content">
                  <div class="modal-slot-head">
                    <div class="modal-slot-service">${escapeHtml(slot.service)}</div>
                    ${showDeptTag ? `<span class="modal-slot-dept">${escapeHtml(slot.dept)}</span>` : ''}
                  </div>
                  ${slot.desc ? `<p class="modal-slot-desc">${escapeHtml(slot.desc)}</p>` : ''}
                </div>
              </article>
            `;
          };

          const morningSlots = slots.filter(s => parseInt((s.startTime || '08').split(':')[0], 10) < 12);
          const afternoonSlots = slots.filter(s => parseInt((s.startTime || '08').split(':')[0], 10) >= 12);

          let bodyHtml = '';
          if (morningSlots.length) {
            bodyHtml += `<section class="modal-session-group"><div class="modal-session-label">Morning</div>${morningSlots.map(renderSlotRow).join('')}</section>`;
          }
          if (afternoonSlots.length) {
            bodyHtml += `<section class="modal-session-group"><div class="modal-session-label">Afternoon</div>${afternoonSlots.map(renderSlotRow).join('')}</section>`;
          }
          modalBodyEl.innerHTML = bodyHtml;

          modalEl.hidden = false;
          requestAnimationFrame(() => modalEl.classList.add('is-open'));
          modalCloseBtn.focus();
        }

        function closeDayModal() {
          modalEl.classList.remove('is-open');
          setTimeout(() => {
            if (!modalEl.classList.contains('is-open')) modalEl.hidden = true;
          }, 160);
        }

        function renderCalendar() {
          monthLabelEl.textContent = `${monthNames[currentMonth]} ${currentYear}`;
          prevBtn.disabled = (currentYear === today.getFullYear() && currentMonth <= today.getMonth());

          gridEl.innerHTML = '';

          const firstDayOfMonth = new Date(currentYear, currentMonth, 1).getDay();
          const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
          const daysInPrevMonth = new Date(currentYear, currentMonth, 0).getDate();
          const todayYMD = toYMD(today);

          // Leading out-of-month gray cells
          for (let i = firstDayOfMonth - 1; i >= 0; i--) {
            const cell = document.createElement('div');
            cell.className = 'cal-cell is-outside';
            cell.setAttribute('aria-hidden', 'true');
            cell.innerHTML = `<div class="cell-head"><span class="cell-day-num">${daysInPrevMonth - i}</span></div>`;
            gridEl.appendChild(cell);
          }

          // Current month days
          for (let day = 1; day <= daysInMonth; day++) {
            const cellDate = new Date(currentYear, currentMonth, day);
            cellDate.setHours(0, 0, 0, 0);
            const cellYMD = toYMD(cellDate);
            const isToday = cellYMD === todayYMD;
            const isPast = cellDate < today;
            const isSunday = cellDate.getDay() === 0;
            const slots = getSlotsForDate(cellDate);
            const isOpen = slots.length > 0;

            const cell = document.createElement('div');
            cell.className = `cal-cell ${isOpen ? 'is-open' : 'is-unavailable'}`;
            cell.setAttribute('role', 'gridcell');

            const headHtml = `
              <div class="cell-head">
                <span class="cell-day-num">${day}</span>
                ${isToday ? '<span class="cell-today-pill">Today</span>' : ''}
              </div>
            `;

            if (!isOpen) {
              const reason = isPast ? '' : isToday ? 'Same-day closed' : isSunday ? 'Closed' : 'Not available';
              cell.setAttribute('aria-disabled', 'true');
              cell.innerHTML = `${headHtml}${reason ? `<div class="cell-status-note">${reason}</div>` : ''}`;
              gridEl.appendChild(cell);
              continue;
            }

            cell.setAttribute('tabindex', '0');
            cell.setAttribute('aria-label', `${monthNames[currentMonth]} ${day}, ${currentYear}, ${slots.length} available specializations`);

            const grouped = groupSpecializationsByDept(slots);
            let barsHtml = '';

            if (activeDept === 'ALL') {
              // Show one bar per available department listing its specializations
              barsHtml = grouped.map(group => {
                const tone = deptToneClass(group.dept);
                const specNames = group.items.map(s => s.shortLabel).join(', ');
                const titleAttr = `${group.dept}: ${group.items.map(s => `${s.service} (${formatTime12(s.startTime)}–${formatTime12(s.endTime)})`).join(' | ')}`;
                return `
                  <button type="button" class="spec-bar ${tone}" title="${escapeHtml(titleAttr)}">
                    <span class="spec-bar-title">${escapeHtml(group.dept)} (${group.items.length})</span>
                    <span class="spec-bar-meta">${escapeHtml(specNames)}</span>
                  </button>
                `;
              }).join('');
            } else {
              // Filtered to a single department: show each specialization directly as a bar
              const maxVisible = 3;
              const visible = slots.slice(0, maxVisible);
              const extra = slots.length - visible.length;
              barsHtml = visible.map(slot => {
                const tone = deptToneClass(slot.dept);
                const timeRange = `${formatTime12(slot.startTime)}–${formatTime12(slot.endTime)}`;
                return `
                  <button type="button" class="spec-bar ${tone}" title="${escapeHtml(slot.service)} (${escapeHtml(timeRange)})">
                    <span class="spec-bar-title">${escapeHtml(slot.shortLabel)}</span>
                    <span class="spec-bar-meta">${escapeHtml(timeRange)}</span>
                  </button>
                `;
              }).join('');
              if (extra > 0) {
                barsHtml += `<button type="button" class="spec-more-btn">+${extra} more</button>`;
              }
            }

            cell.innerHTML = `${headHtml}<div class="cell-bars">${barsHtml}</div>`;

            cell.addEventListener('click', () => openDayModal(cellDate, slots));
            cell.addEventListener('keydown', (e) => {
              if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                openDayModal(cellDate, slots);
              }
            });

            gridEl.appendChild(cell);
          }

          // Trailing out-of-month gray cells to complete the last week row
          const totalCells = firstDayOfMonth + daysInMonth;
          const trailingCount = (7 - (totalCells % 7)) % 7;
          for (let d = 1; d <= trailingCount; d++) {
            const cell = document.createElement('div');
            cell.className = 'cal-cell is-outside';
            cell.setAttribute('aria-hidden', 'true');
            cell.innerHTML = `<div class="cell-head"><span class="cell-day-num">${d}</span></div>`;
            gridEl.appendChild(cell);
          }
        }

        async function fetchApiSchedule() {
          try {
            const res = await fetch('/sdn/api/schedule.php').then(r => r.json());
            if (res && res.success && Array.isArray(res.data)) {
              apiSchedules = res.data;
            }
          } catch (err) {
            // Keep base clinic templates active
          } finally {
            renderCalendar();
          }
        }

        prevBtn.addEventListener('click', () => {
          if (currentYear === today.getFullYear() && currentMonth <= today.getMonth()) return;
          currentMonth--;
          if (currentMonth < 0) {
            currentMonth = 11;
            currentYear--;
          }
          renderCalendar();
        });

        nextBtn.addEventListener('click', () => {
          currentMonth++;
          if (currentMonth > 11) {
            currentMonth = 0;
            currentYear++;
          }
          renderCalendar();
        });

        todayBtn.addEventListener('click', () => {
          currentMonth = today.getMonth();
          currentYear = today.getFullYear();
          renderCalendar();
        });

        document.querySelectorAll('.dept-tab').forEach(btn => {
          btn.addEventListener('click', () => {
            document.querySelectorAll('.dept-tab').forEach(b => {
              b.classList.remove('active');
              b.setAttribute('aria-pressed', 'false');
            });
            btn.classList.add('active');
            btn.setAttribute('aria-pressed', 'true');
            activeDept = btn.getAttribute('data-dept') || 'ALL';
            renderCalendar();
          });
        });

        let searchTimeout = null;
        document.getElementById('sched-search-input').addEventListener('input', (e) => {
          clearTimeout(searchTimeout);
          searchTimeout = setTimeout(() => {
            searchQuery = e.target.value || '';
            renderCalendar();
          }, 120);
        });

        modalCloseBtn.addEventListener('click', closeDayModal);
        modalEl.addEventListener('click', (e) => {
          if (e.target === modalEl) closeDayModal();
        });
        document.addEventListener('keydown', (e) => {
          if (e.key === 'Escape' && !modalEl.hidden) closeDayModal();
        });

        fetchApiSchedule();
      });
    </script>
  </body>
</html>
