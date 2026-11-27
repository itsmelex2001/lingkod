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

      /* ── Top Header (Two-tier: Emerald brand strip + crisp light filter bar) ── */
      .sched-header {
        position: sticky;
        top: 0;
        z-index: 30;
        box-shadow: 0 4px 18px rgba(15, 36, 23, 0.08);
      }

      .sched-header-top {
        background: linear-gradient(90deg, #0d7a31 0%, #15803d 50%, #04561e 100%);
      }

      .sched-header-top-inner {
        max-width: 1240px;
        margin: 0 auto;
        padding: 15px 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
      }

      .sched-header-left {
        display: flex;
        align-items: center;
        gap: 14px;
        min-width: 0;
      }

      .btn-home {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 42px;
        height: 42px;
        border-radius: 11px;
        border: 1px solid rgba(255, 255, 255, 0.28);
        background: rgba(255, 255, 255, 0.14);
        color: #ffffff;
        text-decoration: none;
        transition: background-color 0.15s ease, border-color 0.15s ease, transform 0.12s ease;
        flex-shrink: 0;
      }

      .btn-home:hover {
        background-color: rgba(255, 255, 255, 0.24);
        border-color: rgba(255, 255, 255, 0.5);
      }

      .btn-home:focus-visible {
        outline: 2px solid #ffffff;
        outline-offset: 2px;
      }

      .sched-title {
        font-family: var(--font-heading);
        font-size: 1.4rem;
        font-weight: 800;
        color: #ffffff;
        line-height: 1.18;
        letter-spacing: -0.025em;
      }

      .sched-subtitle {
        font-size: 0.85rem;
        color: rgba(255, 255, 255, 0.88);
        margin-top: 2px;
        line-height: 1.35;
      }

      /* ── Filter Control Bar (Segmented Clinic Tabs + Search) ── */
      .sched-filter-bar {
        background: #ffffff;
        border-bottom: 1px solid #d9e5dd;
      }

      .sched-filter-inner {
        max-width: 1240px;
        margin: 0 auto;
        padding: 10px 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
      }

      .dept-tabs-nav {
        min-width: 0;
        max-width: 100%;
      }

      .dept-tabs-track {
        display: inline-flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 4px;
        padding: 4px;
        background: #f1f6f3;
        border: 1px solid #dde8e0;
        border-radius: 12px;
      }

      .dept-tab {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        min-height: 36px;
        padding: 7px 14px;
        border: 1px solid transparent;
        border-radius: 9px;
        background: transparent;
        font-family: var(--font-sans);
        font-size: 0.84rem;
        font-weight: 600;
        color: #42564b;
        cursor: pointer;
        white-space: nowrap;
        transition: background-color 0.15s ease, color 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
      }

      .dept-tab:hover {
        color: #0f2417;
        background: rgba(255, 255, 255, 0.72);
      }

      .dept-tab:focus-visible {
        outline: 2px solid var(--color-accent);
        outline-offset: 2px;
      }

      .dept-tab.active {
        color: #0f2417;
        background: #ffffff;
        border-color: #d6e4da;
        font-weight: 700;
        box-shadow: 0 1px 3px rgba(15, 36, 23, 0.08), 0 1px 2px rgba(15, 36, 23, 0.04);
      }

      .dept-tab.active[data-dept="General Medical"] {
        color: var(--dept-general);
      }

      .dept-tab.active[data-dept="Neurology"] {
        color: var(--dept-neuro);
      }

      .dept-tab.active[data-dept="Psychiatry"] {
        color: var(--dept-psych);
      }

      .dept-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        flex-shrink: 0;
      }

      .dot-general { background-color: var(--dept-general); }
      .dot-neuro { background-color: var(--dept-neuro); }
      .dot-psych { background-color: var(--dept-psych); }
      .dot-gray { background-color: #9ca3af; }

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
        color: #64748b;
        pointer-events: none;
      }

      .sched-search-input {
        width: 280px;
        height: 44px;
        padding: 0 14px 0 38px;
        font-family: var(--font-sans);
        font-size: 0.875rem;
        color: var(--color-text);
        background: #f8fbf9;
        border: 1px solid #d5e2d9;
        border-radius: 11px;
        outline: none;
        transition: border-color 0.15s ease, background-color 0.15s ease, box-shadow 0.15s ease;
      }

      .sched-search-input:hover {
        border-color: #b7ccb9;
      }

      .sched-search-input:focus {
        border-color: var(--color-accent);
        background: #ffffff;
        box-shadow: 0 0 0 3px rgba(21, 128, 61, 0.14);
      }

      .sched-search-input::placeholder {
        color: #6b7d72;
      }

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
        background: rgba(15, 23, 42, 0.52);
        backdrop-filter: blur(5px);
        -webkit-backdrop-filter: blur(5px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 16px;
        z-index: 60;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.14s ease-in;
      }

      .modal-backdrop.is-open {
        opacity: 1;
        pointer-events: auto;
        transition: opacity 0.2s cubic-bezier(0.22, 1, 0.36, 1);
      }

      .modal-card {
        width: 100%;
        max-width: 580px;
        max-height: min(86dvh, 720px);
        background: var(--color-surface);
        border: 1px solid #dce6df;
        border-radius: 20px;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.2), 0 4px 14px rgba(15, 36, 23, 0.06);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        transform: translateY(10px) scale(0.985);
        transition: transform 0.14s ease-in;
      }

      .modal-backdrop.is-open .modal-card {
        transform: translateY(0) scale(1);
        transition: transform 0.22s cubic-bezier(0.22, 1, 0.36, 1);
      }

      .modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 20px 24px 18px;
        background: #ffffff;
        border-bottom: 1px solid #e6eee8;
      }

      .modal-header-main {
        display: flex;
        align-items: center;
        gap: 14px;
        min-width: 0;
      }

      .modal-date-tile {
        width: 50px;
        height: 52px;
        border-radius: 13px;
        background: #eef7f1;
        border: 1px solid #cfe4d6;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        line-height: 1;
      }

      .modal-date-tile-dow {
        font-family: var(--font-heading);
        font-size: 0.64rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #15803d;
      }

      .modal-date-tile-day {
        font-family: var(--font-heading);
        font-size: 1.32rem;
        font-weight: 800;
        color: #0f2417;
        margin-top: 2px;
        font-variant-numeric: tabular-nums;
      }

      .modal-header-copy {
        min-width: 0;
      }

      .modal-eyebrow {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #15803d;
        margin-bottom: 2px;
      }

      .modal-title {
        font-family: var(--font-heading);
        font-size: 1.28rem;
        font-weight: 800;
        color: #0f2417;
        line-height: 1.22;
        letter-spacing: -0.02em;
        text-wrap: balance;
      }

      .modal-sub {
        font-size: 0.82rem;
        font-weight: 500;
        color: #52645a;
        margin-top: 3px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
      }

      .modal-sub-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #16a34a;
        flex-shrink: 0;
      }

      .modal-close-btn {
        width: 40px;
        height: 40px;
        border-radius: 11px;
        border: 1px solid #dfe8e2;
        background: #ffffff;
        color: #52645a;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        flex-shrink: 0;
        touch-action: manipulation;
        transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease;
      }

      .modal-close-btn:hover {
        background: #f2f7f4;
        border-color: #c2d6c8;
        color: #0f2417;
      }

      .modal-close-btn:focus-visible {
        outline: 2px solid var(--color-accent);
        outline-offset: 2px;
      }

      .modal-body {
        padding: 20px 24px 22px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 20px;
        background: #f7faf8;
      }

      .modal-session-group {
        display: flex;
        flex-direction: column;
        gap: 10px;
      }

      .modal-session-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        padding: 0 2px;
      }

      .modal-session-label {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        font-family: var(--font-heading);
        font-size: 0.72rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #47584e;
      }

      .modal-session-icon {
        width: 14px;
        height: 14px;
        color: #15803d;
        flex-shrink: 0;
      }

      .modal-session-count {
        font-size: 0.72rem;
        font-weight: 600;
        color: #5c6f64;
        padding: 2px 9px;
        border-radius: 999px;
        background: #eaf2ed;
      }

      .modal-slot-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
      }

      /* Clean uniform card — no colored side stripe */
      .modal-slot-item {
        display: flex;
        flex-direction: column;
        gap: 10px;
        padding: 16px 18px;
        background: #ffffff;
        border: 1px solid #e1eae4;
        border-radius: 14px;
        box-shadow: 0 1px 2px rgba(15, 36, 23, 0.03);
        transition: border-color 0.16s ease, box-shadow 0.16s ease, background-color 0.16s ease;
      }

      .modal-slot-item:hover {
        border-color: #c1d6c8;
        box-shadow: 0 6px 16px rgba(15, 36, 23, 0.06);
      }

      .modal-slot-item.is-general { --slot-color: var(--dept-general); --slot-soft: #ecfdf5; --slot-border: #bbf7d0; }
      .modal-slot-item.is-neuro { --slot-color: var(--dept-neuro); --slot-soft: #eff6ff; --slot-border: #bfdbfe; }
      .modal-slot-item.is-psych { --slot-color: var(--dept-psych); --slot-soft: #f5f3ff; --slot-border: #ddd6fe; }

      .modal-slot-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        flex-wrap: wrap;
        padding-bottom: 10px;
        border-bottom: 1px solid #f0f5f2;
      }

      .modal-slot-time-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-variant-numeric: tabular-nums;
      }

      .modal-slot-clock-icon {
        width: 14px;
        height: 14px;
        color: #64748b;
        flex-shrink: 0;
      }

      .modal-slot-range {
        font-family: var(--font-heading);
        font-size: 0.86rem;
        font-weight: 700;
        color: #0f2417;
      }

      .modal-slot-duration {
        display: inline-flex;
        align-items: center;
        padding: 2px 8px;
        border-radius: 999px;
        background: #f1f5f9;
        color: #475569;
        font-size: 0.7rem;
        font-weight: 600;
      }

      .modal-slot-dept {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 3px 10px;
        border-radius: 999px;
        background: var(--slot-soft);
        border: 1px solid var(--slot-border);
        color: var(--slot-color);
        font-size: 0.72rem;
        font-weight: 700;
        white-space: nowrap;
      }

      .modal-slot-dept-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: var(--slot-color);
        flex-shrink: 0;
      }

      .modal-slot-content {
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 4px;
      }

      .modal-slot-service {
        font-family: var(--font-heading);
        font-size: 0.98rem;
        font-weight: 700;
        color: #0f2417;
        line-height: 1.35;
        text-wrap: balance;
      }

      .modal-slot-desc {
        font-size: 0.84rem;
        color: #52645a;
        line-height: 1.52;
      }

      .modal-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 15px 24px;
        background: #ffffff;
        border-top: 1px solid #e6eee8;
        flex-wrap: wrap;
      }

      .modal-footer-note {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        font-size: 0.8rem;
        color: #52645a;
      }

      .modal-footer-note svg {
        width: 15px;
        height: 15px;
        color: #15803d;
        flex-shrink: 0;
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
        gap: 8px;
        min-height: 44px;
        padding: 0 20px;
        border-radius: 11px;
        background: #15803d;
        color: #ffffff;
        font-family: var(--font-heading);
        font-size: 0.88rem;
        font-weight: 700;
        text-decoration: none;
        touch-action: manipulation;
        box-shadow: 0 2px 8px rgba(4, 86, 30, 0.22);
        transition: background-color 0.15s ease, transform 0.12s ease, box-shadow 0.15s ease;
      }

      .modal-btn-primary:hover {
        background: #0d6a2d;
        box-shadow: 0 4px 12px rgba(4, 86, 30, 0.28);
      }

      .modal-btn-primary:active {
        transform: scale(0.98);
      }

      .modal-btn-primary:focus-visible {
        outline: 2px solid #15803d;
        outline-offset: 2px;
      }

      @media (max-width: 560px) {
        .modal-backdrop {
          padding: 10px;
          align-items: flex-end;
        }
        .modal-card {
          max-height: 90dvh;
          border-radius: 18px;
        }
        .modal-header,
        .modal-body,
        .modal-footer {
          padding-left: 16px;
          padding-right: 16px;
        }
        .modal-date-tile {
          width: 46px;
          height: 48px;
        }
        .modal-slot-item {
          padding: 14px;
          gap: 8px;
        }
        .modal-footer-actions,
        .modal-btn-primary {
          width: 100%;
        }
      }

      /* ── Responsive ── */
      @media (max-width: 900px) {
        .sched-header-top-inner,
        .sched-filter-inner,
        .sched-main {
          padding-left: 14px;
          padding-right: 14px;
        }

        .sched-filter-inner {
          flex-direction: column;
          align-items: stretch;
          gap: 10px;
        }

        .dept-tabs-track {
          width: 100%;
          justify-content: flex-start;
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

      @media (max-width: 640px) {
        .sched-header {
          position: static;
        }
        .sched-header-top-inner {
          padding: 10px 12px;
        }
        .sched-header-left {
          gap: 10px;
        }
        .btn-home {
          width: 34px;
          height: 34px;
          border-radius: 9px;
        }
        .btn-home svg {
          width: 17px;
          height: 17px;
        }
        .sched-title {
          font-size: 1.05rem;
        }
        .sched-subtitle {
          font-size: 0.73rem;
          line-height: 1.3;
        }
        .sched-filter-inner {
          padding: 8px 12px;
          gap: 8px;
        }
        .dept-tabs-track {
          display: grid;
          grid-template-columns: repeat(2, minmax(0, 1fr));
          gap: 4px;
          padding: 3px;
          border-radius: 10px;
        }
        .dept-tab {
          min-height: 32px;
          padding: 5px 8px;
          font-size: 0.74rem;
          justify-content: center;
          gap: 5px;
          border-radius: 7px;
        }
        .dept-dot {
          width: 7px;
          height: 7px;
        }
        .sched-search-input {
          height: 36px;
          font-size: 0.8rem;
          padding: 0 10px 0 32px;
          border-radius: 9px;
        }
        .sched-search-icon {
          left: 10px;
          width: 14px;
          height: 14px;
        }
        .sched-main {
          padding: 10px 10px 24px;
        }
        .calendar-board {
          border-radius: 12px;
        }
        .cal-toolbar {
          padding: 10px 12px;
          gap: 8px;
        }
        .cal-month-title {
          font-size: 0.95rem;
        }
        .cal-btn {
          height: 32px;
          min-width: 32px;
          padding: 0 10px;
          font-size: 0.75rem;
          border-radius: 8px;
        }
        .cal-legend {
          padding: 8px 12px;
          gap: 6px 10px;
          font-size: 0.7rem;
        }
        .cal-legend-items {
          gap: 6px 10px;
        }
        .legend-swatch {
          width: 9px;
          height: 9px;
        }
        .cal-grid-frame {
          min-width: 0;
          width: 100%;
        }
        .cal-weekday {
          font-size: 0.64rem;
          padding: 7px 2px;
          letter-spacing: 0.02em;
        }
        .cal-cell {
          min-height: 62px;
          padding: 5px 3px 6px;
          gap: 3px;
        }
        .cell-head {
          gap: 3px;
          flex-wrap: wrap;
        }
        .cell-day-num {
          font-size: 0.78rem;
        }
        .cell-today-pill {
          font-size: 0.52rem;
          padding: 1px 4px;
        }
        .cell-status-note {
          font-size: 0.58rem;
        }
        .cell-bars {
          gap: 3px;
        }
        .spec-bar {
          padding: 3px 4px;
          font-size: 0.58rem;
          border-radius: 4px;
          overflow: hidden;
          text-overflow: ellipsis;
          white-space: nowrap;
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
      <div class="sched-header-top">
        <div class="sched-header-top-inner">
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
        </div>
      </div>

      <div class="sched-filter-bar">
        <div class="sched-filter-inner">
          <nav class="dept-tabs-nav" aria-label="Clinic department">
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
    <div class="modal-backdrop" id="dayModal" role="dialog" aria-modal="true" aria-labelledby="dayModalTitle" aria-describedby="dayModalSub" hidden>
      <div class="modal-card">
        <div class="modal-header">
          <div class="modal-header-main">
            <div class="modal-date-tile" aria-hidden="true">
              <span class="modal-date-tile-dow" id="dayModalTileDow">SAT</span>
              <span class="modal-date-tile-day" id="dayModalTileDay">28</span>
            </div>
            <div class="modal-header-copy">
              <div class="modal-eyebrow" id="dayModalEyebrow">Clinic Schedule</div>
              <h3 class="modal-title" id="dayModalTitle">Date</h3>
              <p class="modal-sub" id="dayModalSub">Available specializations</p>
            </div>
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
          <span class="modal-footer-note">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="16" x2="12" y2="12"></line>
              <line x1="12" y1="8" x2="12.01" y2="8"></line>
            </svg>
            Online schedules require staff approval.
          </span>
          <div class="modal-footer-actions">
            <a href="/sdn/pages/patient-login.php" class="modal-btn-primary">
              Book Appointment
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <line x1="5" y1="12" x2="19" y2="12"></line>
                <polyline points="12 5 19 12 12 19"></polyline>
              </svg>
            </a>
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

        let lastFocusedEl = null;

        function formatDuration(startTime, endTime) {
          if (!startTime || !endTime) return '';
          const [sh, sm] = startTime.split(':').map(Number);
          const [eh, em] = endTime.split(':').map(Number);
          const mins = (eh * 60 + em) - (sh * 60 + sm);
          if (mins <= 0) return '';
          const hrs = Math.floor(mins / 60);
          const rem = mins % 60;
          if (hrs && rem) return `${hrs}h ${rem}m`;
          if (hrs) return `${hrs} ${hrs === 1 ? 'hr' : 'hrs'}`;
          return `${rem} mins`;
        }

        function openDayModal(dateObj, slots) {
          lastFocusedEl = document.activeElement;
          const eyebrowEl = document.getElementById('dayModalEyebrow');
          const tileDowEl = document.getElementById('dayModalTileDow');
          const tileDayEl = document.getElementById('dayModalTileDay');
          const weekdayLabel = dateObj.toLocaleDateString('en-US', { weekday: 'long' });
          const shortWeekday = dateObj.toLocaleDateString('en-US', { weekday: 'short' });
          const dateLabel = dateObj.toLocaleDateString('en-US', {
            month: 'long',
            day: 'numeric',
            year: 'numeric'
          });
          if (tileDowEl) tileDowEl.textContent = shortWeekday;
          if (tileDayEl) tileDayEl.textContent = String(dateObj.getDate());
          if (eyebrowEl) {
            eyebrowEl.textContent = activeDept === 'ALL' ? weekdayLabel : `${weekdayLabel} · ${activeDept}`;
          }
          modalTitleEl.textContent = dateLabel;
          modalSubEl.innerHTML = `<span class="modal-sub-dot" aria-hidden="true"></span>${slots.length} open consultation ${slots.length === 1 ? 'block' : 'blocks'}`;

          const clockIcon = '<svg class="modal-slot-clock-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><polyline points="12 7 12 12 15.5 14"></polyline></svg>';

          const renderSlotRow = (slot) => {
            const tone = deptToneClass(slot.dept);
            const showDeptTag = activeDept === 'ALL';
            const duration = formatDuration(slot.startTime, slot.endTime);
            return `
              <article class="modal-slot-item ${tone}">
                <div class="modal-slot-top">
                  <div class="modal-slot-time-pill">
                    ${clockIcon}
                    <span class="modal-slot-range">${escapeHtml(formatTime12(slot.startTime))} &ndash; ${escapeHtml(formatTime12(slot.endTime))}</span>
                    ${duration ? `<span class="modal-slot-duration">${escapeHtml(duration)}</span>` : ''}
                  </div>
                  ${showDeptTag ? `<span class="modal-slot-dept"><span class="modal-slot-dept-dot" aria-hidden="true"></span>${escapeHtml(slot.dept)}</span>` : ''}
                </div>
                <div class="modal-slot-content">
                  <div class="modal-slot-service">${escapeHtml(slot.service)}</div>
                  ${slot.desc ? `<p class="modal-slot-desc">${escapeHtml(slot.desc)}</p>` : ''}
                </div>
              </article>
            `;
          };

          const morningSlots = slots.filter(s => parseInt((s.startTime || '08').split(':')[0], 10) < 12);
          const afternoonSlots = slots.filter(s => parseInt((s.startTime || '08').split(':')[0], 10) >= 12);
          const sunIcon = '<svg class="modal-session-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"></path></svg>';
          const pmIcon = '<svg class="modal-session-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><polyline points="12 7 12 12 15.5 14"></polyline></svg>';

          let bodyHtml = '';
          if (morningSlots.length) {
            bodyHtml += `
              <section class="modal-session-group">
                <div class="modal-session-header">
                  <div class="modal-session-label">${sunIcon}Morning</div>
                  <span class="modal-session-count">${morningSlots.length} ${morningSlots.length === 1 ? 'block' : 'blocks'}</span>
                </div>
                <div class="modal-slot-list">${morningSlots.map(renderSlotRow).join('')}</div>
              </section>
            `;
          }
          if (afternoonSlots.length) {
            bodyHtml += `
              <section class="modal-session-group">
                <div class="modal-session-header">
                  <div class="modal-session-label">${pmIcon}Afternoon</div>
                  <span class="modal-session-count">${afternoonSlots.length} ${afternoonSlots.length === 1 ? 'block' : 'blocks'}</span>
                </div>
                <div class="modal-slot-list">${afternoonSlots.map(renderSlotRow).join('')}</div>
              </section>
            `;
          }
          modalBodyEl.innerHTML = bodyHtml;
          modalBodyEl.scrollTop = 0;

          document.body.style.overflow = 'hidden';
          modalEl.hidden = false;
          requestAnimationFrame(() => modalEl.classList.add('is-open'));
          modalCloseBtn.focus();
        }

        function closeDayModal() {
          modalEl.classList.remove('is-open');
          document.body.style.overflow = '';
          setTimeout(() => {
            if (!modalEl.classList.contains('is-open')) modalEl.hidden = true;
          }, 150);
          if (lastFocusedEl && typeof lastFocusedEl.focus === 'function') {
            lastFocusedEl.focus();
            lastFocusedEl = null;
          }
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
          if (modalEl.hidden) return;
          if (e.key === 'Escape') {
            closeDayModal();
            return;
          }
          if (e.key === 'Tab') {
            const focusables = modalEl.querySelectorAll('a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])');
            if (!focusables.length) return;
            const first = focusables[0];
            const last = focusables[focusables.length - 1];
            if (e.shiftKey && document.activeElement === first) {
              e.preventDefault();
              last.focus();
            } else if (!e.shiftKey && document.activeElement === last) {
              e.preventDefault();
              first.focus();
            }
          }
        });

        fetchApiSchedule();
      });
    </script>
  </body>
</html>
