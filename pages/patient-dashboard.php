<?php
/**
 * pages/patient-dashboard.php
 * MMWGH LINGKOD â€” Patient Portal
 */

session_start();
if (empty($_SESSION['patient_logged_in']) && !empty($_COOKIE['lingkod_patient_remember'])) {
    require_once __DIR__ . '/../api/config/db.php';
    require_once __DIR__ . '/../api/config/patient_remember.php';
    if (isset($pdo)) {
        tryAutoLoginPatientFromCookie($pdo);
    }
}
if (empty($_SESSION['patient_logged_in']) && empty($_SESSION['patient_id'])) {
    if (isset($_GET['demo']) || !file_exists(__DIR__ . '/patient-login.php')) {
        $_SESSION['patient_logged_in'] = true;
        $_SESSION['patient_id'] = 'PT-2026-082';
        $_SESSION['patient_name'] = 'Maria Santos';
        $_SESSION['patient_email'] = 'maria.santos@gmail.com';
    }
}

if (empty($_SESSION['patient_logged_in']) || (empty($_SESSION['patient_id']) && empty($_SESSION['patient_email']))) {
    header('Location: /sdn/pages/patient-login.php');
    exit;
}

$patientName  = $_SESSION['patient_name'] ?? 'Patient';
$patientId    = $_SESSION['patient_id'] ?? '';
$patientEmail = $_SESSION['patient_email'] ?? '';

$nameParts = explode(' ', trim($patientName));
$firstName = $nameParts[0] ?? $patientName;
$initials  = count($nameParts) >= 2
    ? strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1))
    : strtoupper(substr($patientName, 0, 2));
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Patient Portal | MMWGH LINGKOD</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">

    <style>
      body, body * {
        user-select: none;
        -webkit-user-select: none;
        caret-color: transparent;
      }
      input, textarea, select, [contenteditable="true"] {
        user-select: text;
        -webkit-user-select: text;
        caret-color: auto;
      }
      *, *::before, *::after {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
      }

      :root {
        --bg-page: #f1f5f9;
        --bg-card: #ffffff;
        --border-color: #e2e8f0;
        --border-light: #f1f5f9;
        --text-primary: #0f172a;
        --text-secondary: #475569;
        --text-muted: #94a3b8;
        --brand-green: #15803d;
        --brand-green-hover: #166534;
        --brand-green-light: #f0fdf4;
        --brand-green-border: #bbf7d0;
        --accent-green: #22c55e;
        --accent-amber: #f59e0b;
        --accent-blue: #0284c7;
        --accent-purple: #7c3aed;
      }

      html, body {
        height: 100%;
      }
      body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        background-color: var(--bg-page);
        color: var(--text-primary);
        height: 100vh;
        overflow: hidden;
        display: flex;
        -webkit-font-smoothing: antialiased;
      }

      .app-layout {
        display: flex;
        width: 100%;
        height: 100vh;
        min-height: 0;
        overflow: hidden;
      }

      /* â”€â”€ Sidebar â”€â”€ */
      .sidebar {
        width: 268px;
        height: 100vh;
        position: sticky;
        top: 0;
        background: linear-gradient(180deg, #1b9e43 0%, #15803d 48%, #0b5324 100%);
        border-right: 1px solid rgba(255, 255, 255, 0.14);
        box-shadow: 4px 0 24px rgba(6, 50, 22, 0.14);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 1.4rem 1.15rem;
        flex-shrink: 0;
        overflow: hidden;
        z-index: 50;
        color: #ffffff;
      }

      .sidebar-brand {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 0.1rem 0 1.15rem 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.18);
      }
      .sidebar-brand-box {
        width: 56px;
        height: 56px;
        min-width: 56px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
      }
      .sidebar-brand-box img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        filter: brightness(0) invert(1);
      }
      .brand-text-block {
        display: flex;
        flex-direction: column;
        line-height: 1.25;
      }
      .brand-sub {
        font-size: 0.67rem;
        color: rgba(255, 255, 255, 0.9);
        font-weight: 500;
      }
      .brand-title {
        font-family: 'Outfit', sans-serif;
        font-size: 1.18rem;
        font-weight: 800;
        letter-spacing: 0.04em;
        color: #ffffff;
        margin-top: 2px;
      }

      .sidebar-section-lbl {
        font-size: 0.68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: rgba(255, 255, 255, 0.65);
        padding: 1rem 0.75rem 0.4rem;
      }

      .sidebar-nav {
        display: flex;
        flex-direction: column;
        gap: 6px;
        flex: 1;
        overflow-y: auto;
      }
      .nav-link {
        display: flex;
        align-items: center;
        justify-content: space-between;
        min-height: 44px;
        padding: 11px 14px;
        border-radius: 11px;
        color: rgba(255, 255, 255, 0.92);
        font-size: 0.92rem;
        font-weight: 600;
        text-decoration: none;
        transition: background-color 0.16s ease, color 0.16s ease;
        cursor: pointer;
      }
      .nav-link:focus-visible {
        outline: 2px solid #ffffff;
        outline-offset: 2px;
      }
      .nav-link-left {
        display: flex;
        align-items: center;
        gap: 12px;
      }
      .nav-link svg {
        color: currentColor;
        flex-shrink: 0;
      }
      .nav-link:hover {
        background: rgba(255, 255, 255, 0.15);
        color: #ffffff;
      }
      .nav-link.active {
        color: #0b5324;
        background: #ffffff;
        font-weight: 700;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
      }
      .nav-pill-count {
        font-size: 0.72rem;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.22);
        color: #ffffff;
      }
      .nav-link.active .nav-pill-count {
        background: #dcfce7;
        color: #15803d;
      }
      .nav-pill-count[hidden] {
        display: none;
      }

      .sidebar-footer {
        padding-top: 1rem;
        border-top: 1px solid rgba(255, 255, 255, 0.18);
        display: flex;
        flex-direction: column;
        gap: 8px;
      }

      .sidebar-help-card {
        background: rgba(0, 0, 0, 0.18);
        border: 1px solid rgba(255, 255, 255, 0.14);
        border-radius: 12px;
        padding: 12px;
        margin-bottom: 4px;
      }
      .sidebar-help-title {
        font-size: 0.84rem;
        font-weight: 700;
        color: #ffffff;
        display: flex;
        align-items: center;
        gap: 6px;
      }
      .sidebar-help-sub {
        font-size: 0.8rem;
        color: rgba(255, 255, 255, 0.88);
        margin-top: 4px;
        line-height: 1.45;
      }

      /* â”€â”€ Main Content Area â”€â”€ */
      .main-content {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-width: 0;
        height: 100vh;
        overflow-x: hidden;
        overflow-y: auto;
        background-color: var(--bg-page);
      }

      /* â”€â”€ Topbar â”€â”€ */
      .topbar {
        height: 70px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 0 2.4rem;
        position: sticky;
        top: 0;
        z-index: 40;
        flex-shrink: 0;
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(16px);
        border-bottom: 1px solid var(--border-color);
      }
      .topbar-left {
        display: flex;
        align-items: center;
        gap: 12px;
      }
      .topbar-portal-tag {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 5px 12px;
        border-radius: 999px;
        background: var(--brand-green-light);
        border: 1px solid var(--brand-green-border);
        color: var(--brand-green);
        font-size: 0.76rem;
        font-weight: 700;
      }
      .topbar-portal-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #22c55e;
      }
      .topbar-id-chip {
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--text-secondary);
        background: #f8fafc;
        border: 1px solid var(--border-color);
        padding: 6px 12px;
        border-radius: 8px;
        user-select: text;
        -webkit-user-select: text;
      }
      .topbar-right {
        display: flex;
        align-items: center;
        gap: 12px;
      }

      .btn-top-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        border: 1px solid var(--border-color);
        background: #ffffff;
        color: #15803d;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.16s ease;
        position: relative;
      }
      .btn-top-icon:hover {
        background: var(--brand-green-light);
        border-color: var(--brand-green-border);
      }
      .btn-top-icon:focus-visible,
      .user-avatar-btn:focus-visible,
      .section-link:focus-visible {
        outline: 2px solid var(--brand-green);
        outline-offset: 2px;
      }

      .notif-container, .profile-container {
        position: relative;
      }
      .notif-badge {
        position: absolute;
        top: -4px;
        right: -4px;
        min-width: 18px;
        height: 18px;
        padding: 0 4px;
        border-radius: 999px;
        background: #ef4444;
        color: #ffffff;
        font-size: 10px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid #ffffff;
      }
      .notif-badge.hidden {
        display: none;
      }
      .notif-dropdown {
        position: absolute;
        top: calc(100% + 10px);
        right: 0;
        width: 360px;
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 14px;
        box-shadow: 0 14px 34px rgba(15, 23, 42, 0.12);
        display: none;
        flex-direction: column;
        z-index: 100;
        overflow: hidden;
      }
      .notif-dropdown.show {
        display: flex;
      }
      .notif-header {
        padding: 13px 16px;
        border-bottom: 1px solid var(--border-light);
        display: flex;
        align-items: center;
        justify-content: space-between;
      }
      .notif-title-wrap {
        display: flex;
        align-items: center;
        gap: 8px;
      }
      .notif-title-wrap span {
        font-size: 14px;
        font-weight: 700;
      }
      .notif-count-pill {
        font-size: 11px;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 999px;
        background: var(--brand-green-light);
        color: var(--brand-green);
      }
      .notif-mark-read {
        font-size: 11px;
        color: var(--brand-green);
        background: none;
        border: none;
        cursor: pointer;
        font-weight: 600;
      }
      .notif-list {
        max-height: 320px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
      }
      .notif-item {
        padding: 12px 16px;
        border-bottom: 1px solid var(--border-light);
        display: flex;
        gap: 12px;
        cursor: pointer;
        transition: background 0.15s;
      }
      .notif-item:hover {
        background: #f8fafc;
      }
      .notif-item.unread {
        background: #f0fdf4;
      }
      .notif-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--brand-green);
        margin-top: 5px;
        flex-shrink: 0;
      }
      .notif-dot.warn {
        background: var(--accent-amber);
      }
      .notif-dot.read {
        background: #cbd5e1;
      }
      .notif-content {
        flex: 1;
      }
      .notif-item-title {
        font-size: 13px;
        font-weight: 700;
        color: var(--text-primary);
      }
      .notif-item-text {
        font-size: 12px;
        color: var(--text-secondary);
        margin-top: 2px;
        line-height: 1.35;
      }
      .notif-item-time {
        font-size: 10px;
        color: var(--text-muted);
        margin-top: 4px;
        font-weight: 600;
      }
      .notif-footer {
        padding: 10px 16px;
        background: #f8fafc;
        border-top: 1px solid var(--border-light);
        text-align: center;
      }
      .notif-footer button {
        background: none;
        border: none;
        font-size: 12px;
        color: var(--text-secondary);
        cursor: pointer;
        font-weight: 600;
      }

      /* Profile Avatar Dropdown */
      .user-avatar-btn {
        display: flex;
        align-items: center;
        gap: 10px;
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 999px;
        padding: 4px 12px 4px 4px;
        cursor: pointer;
        transition: all 0.16s ease;
      }
      .user-avatar-btn:hover {
        border-color: var(--brand-green-border);
        background: #f8fafc;
      }
      .user-avatar-circle {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: linear-gradient(180deg, #22c55e 0%, #15803d 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Outfit', sans-serif;
        font-size: 0.82rem;
        font-weight: 800;
        color: #ffffff;
      }
      .user-avatar-name {
        font-size: 0.84rem;
        font-weight: 700;
        color: var(--text-primary);
      }
      .profile-dropdown {
        position: absolute;
        top: calc(100% + 10px);
        right: 0;
        width: 230px;
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 14px;
        box-shadow: 0 14px 32px rgba(15, 23, 42, 0.12);
        display: none;
        flex-direction: column;
        z-index: 100;
        overflow: hidden;
      }
      .profile-dropdown.show {
        display: flex;
      }
      .profile-dd-head {
        padding: 12px 16px;
        border-bottom: 1px solid var(--border-light);
      }
      .profile-dd-name {
        font-size: 0.88rem;
        font-weight: 700;
        color: var(--text-primary);
      }
      .profile-dd-sub {
        font-size: 0.74rem;
        color: var(--text-secondary);
        margin-top: 2px;
      }
      .profile-dd-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 16px;
        font-size: 0.84rem;
        font-weight: 600;
        color: var(--text-primary);
        text-decoration: none;
        background: transparent;
        border: none;
        width: 100%;
        text-align: left;
        cursor: pointer;
      }
      .profile-dd-item:hover {
        background: #f8fafc;
      }
      .profile-dd-logout {
        color: #dc2626;
        border-top: 1px solid var(--border-light);
      }
      .profile-dd-logout:hover {
        background: transparent;
        color: #b91c1c;
      }

      /* â”€â”€ Canvas â”€â”€ */
      .dashboard-canvas {
        padding: 1.8rem 2.4rem 3.2rem;
        max-width: 1240px;
        width: 100%;
        margin: 0 auto;
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
      }

      .view-section {
        display: none;
        flex-direction: column;
        gap: 1.5rem;
      }
      .view-section.active {
        display: flex;
      }

      /* â”€â”€ Hero Banner â”€â”€ */
      .hero-banner {
        background: linear-gradient(125deg, #15803d 0%, #0f6930 55%, #07471f 100%);
        border-radius: 16px;
        padding: 1.25rem 1.5rem;
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem 1.5rem;
        box-shadow: 0 8px 20px rgba(21, 128, 61, 0.14);
        position: relative;
        overflow: hidden;
      }
      .hero-banner::after {
        content: '';
        position: absolute;
        right: -40px;
        top: -40px;
        width: 220px;
        height: 220px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.06);
        pointer-events: none;
      }
      .hero-banner-left {
        display: flex;
        flex-direction: column;
        gap: 6px;
        z-index: 1;
      }
      .hero-eyebrow {
        display: inline-flex;
        align-items: center;
        font-size: 0.76rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #ffffff;
      }
      .hero-banner h1 {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        font-size: 1.45rem;
        font-weight: 600;
        letter-spacing: -0.025em;
        line-height: 1.2;
        color: #ffffff;
      }
      .hero-banner p {
        font-size: 0.94rem;
        line-height: 1.45;
        color: rgba(255, 255, 255, 0.9);
        max-width: 520px;
      }
      .hero-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        z-index: 1;
      }
      .btn-hero-white {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 44px;
        background: #ffffff;
        color: #0b5324;
        font-size: 0.88rem;
        font-weight: 700;
        padding: 0.65rem 1.2rem;
        border-radius: 10px;
        text-decoration: none;
        border: none;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        transition: background-color 0.16s ease;
      }
      .btn-hero-white:hover {
        background: #f0fdf4;
      }
      .btn-hero-white:focus-visible,
      .btn-hero-outline:focus-visible {
        outline: 2px solid #ffffff;
        outline-offset: 3px;
      }
      .btn-hero-outline {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 44px;
        background: rgba(255, 255, 255, 0.14);
        color: #ffffff;
        font-size: 0.88rem;
        font-weight: 600;
        padding: 0.65rem 1.15rem;
        border-radius: 10px;
        text-decoration: none;
        border: 1px solid rgba(255, 255, 255, 0.35);
        transition: background-color 0.16s ease;
      }
      .btn-hero-outline:hover {
        background: rgba(255, 255, 255, 0.24);
      }

      /* â”€â”€ 3 KPI Stat Cards â”€â”€ */
      .metrics-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.15rem;
      }
      .metric-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 14px;
        padding: 1rem 1.1rem;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
      }
      .metric-icon-box {
        width: 20px;
        height: 20px;
        background: transparent;
        color: #9ca3af;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
      }
      .metric-icon-box.green,
      .metric-icon-box.blue,
      .metric-icon-box.purple,
      .metric-icon-box.amber {
        background: transparent;
        color: #9ca3af;
      }
      .metric-lbl {
        font-size: 0.78rem;
        color: var(--text-secondary);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.03em;
      }
      .metric-value {
        font-family: 'Outfit', sans-serif;
        font-size: 1.35rem;
        font-weight: 800;
        color: var(--text-primary);
        line-height: 1.25;
        margin: 4px 0 2px;
        user-select: text;
        -webkit-user-select: text;
      }
      .metric-sub {
        font-size: 0.78rem;
        color: var(--text-secondary);
        font-weight: 500;
      }

      /* â”€â”€ Department Quick Booking Cards â”€â”€ */
      .dept-cards-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
      }
      .dept-card {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 14px;
        padding: 1.15rem 1.25rem;
        display: flex;
        align-items: flex-start;
        gap: 14px;
        text-decoration: none;
        color: var(--text-primary);
        transition: all 0.18s ease;
        position: relative;
      }
      .dept-card:hover {
        border-color: var(--brand-green);
        box-shadow: 0 8px 22px rgba(21, 128, 61, 0.1);
        transform: translateY(-2px);
      }
      .dept-icon-square {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
      }
      .dept-icon-square.gen {
        background: #dcfce7;
        color: #15803d;
      }
      .dept-icon-square.neuro {
        background: #e0f2fe;
        color: #0284c7;
      }
      .dept-icon-square.psych {
        background: #f3e8ff;
        color: #7c3aed;
      }
      .dept-info {
        flex: 1;
      }
      .dept-name {
        font-size: 0.96rem;
        font-weight: 700;
        color: var(--text-primary);
      }
      .dept-desc {
        font-size: 0.78rem;
        color: var(--text-secondary);
        margin-top: 3px;
        line-height: 1.35;
      }
      .dept-cta {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        margin-top: 8px;
        font-size: 0.78rem;
        font-weight: 700;
        color: var(--brand-green);
      }

      /* â”€â”€ Content Grid â”€â”€ */
      .content-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.35rem;
      }

      .section-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 1.4rem 1.55rem;
        display: flex;
        flex-direction: column;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04);
      }
      .section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1rem;
        gap: 12px;
      }
      .section-title-group {
        display: flex;
        align-items: center;
        gap: 10px;
      }
      .section-title-icon {
        width: auto;
        height: auto;
        background: transparent;
        color: #6b7280;
        display: flex;
        align-items: center;
        justify-content: center;
      }
      .section-title {
        font-size: 1.02rem;
        font-weight: 700;
        color: var(--text-primary);
      }
      .section-link {
        color: var(--brand-green);
        font-size: 0.84rem;
        font-weight: 700;
        text-decoration: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: none;
        border: none;
        font-family: inherit;
        min-height: 44px;
        padding: 0 4px;
        border-radius: 8px;
      }
      .section-link:hover {
        text-decoration: underline;
      }

      /* â”€â”€ Appointment & Medication Cards â”€â”€ */
      .card-item-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
      }
      .item-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 14px 16px;
        border: 1px solid var(--border-color);
        border-radius: 13px;
        background: #ffffff;
        transition: all 0.16s ease;
      }
      .item-row:hover {
        border-color: #cbd5e1;
        background: #fcfdfd;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
      }

      .appt-info-left {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        flex: 1;
        min-width: 0;
      }
      .date-badge {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        width: 52px;
        height: 54px;
        border-radius: 12px;
        background: var(--brand-green-light);
        border: 1px solid var(--brand-green-border);
        text-align: center;
        flex-shrink: 0;
      }
      .date-month {
        font-size: 0.68rem;
        font-weight: 800;
        color: var(--brand-green);
        text-transform: uppercase;
        letter-spacing: 0.04em;
      }
      .date-day {
        font-family: 'Outfit', sans-serif;
        font-size: 1.25rem;
        font-weight: 800;
        color: #0b5324;
        line-height: 1.1;
      }
      .appt-desc {
        display: flex;
        flex-direction: column;
        gap: 3px;
        min-width: 0;
        flex: 1;
      }
      .appt-title-row {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
      }
      .appt-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--text-primary);
      }
      .appt-code-tag {
        font-size: 0.72rem;
        font-weight: 700;
        color: #475569;
        background: #f1f5f9;
        padding: 2px 7px;
        border-radius: 6px;
      }
      .appt-doc {
        font-size: 0.82rem;
        color: var(--text-secondary);
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
      }
      .prev-visit-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        margin-top: 3px;
        font-size: 0.73rem;
        font-weight: 600;
        color: #0369a1;
        background: #e0f2fe;
        border: 1px solid #bae6fd;
        padding: 2px 8px;
        border-radius: 999px;
        width: fit-content;
      }

      /* Status Badges */
      .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.75rem;
        font-weight: 700;
        padding: 4px 10px;
        border-radius: 999px;
      }
      .status-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
      }
      .status-confirmed {
        background: #dcfce7;
        color: #15803d;
      }
      .status-confirmed .status-dot { background: #15803d; }
      .status-waiting {
        background: #fef3c7;
        color: #b45309;
      }
      .status-waiting .status-dot { background: #d97706; }
      .status-completed {
        background: #e0f2fe;
        color: #0369a1;
      }
      .status-completed .status-dot { background: #0284c7; }
      .status-cancelled {
        background: #fee2e2;
        color: #b91c1c;
      }
      .status-cancelled .status-dot { background: #dc2626; }

      .btn-outline-sm {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 8px;
        border: 1px solid var(--border-color);
        background: #ffffff;
        color: var(--text-primary);
        font-size: 0.78rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
      }
      .btn-outline-sm:hover {
        border-color: var(--brand-green);
        color: var(--brand-green);
        background: var(--brand-green-light);
      }

      /* â”€â”€ Appointments Date Range Filter Controls â”€â”€ */
      .toolbar-controls {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
      }
      .date-range-filter-wrap {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 10px;
        padding: 4px 8px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
      }
      .date-filter-field {
        display: inline-flex;
        align-items: center;
        gap: 5px;
      }
      .date-filter-label {
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--text-muted);
        letter-spacing: 0.02em;
      }
      .date-filter-field input[type="date"] {
        border: none;
        background: transparent;
        font-size: 0.82rem;
        color: var(--text-primary);
        font-family: inherit;
        outline: none;
        padding: 3px 2px;
        cursor: pointer;
      }
      .date-filter-field input[type="date"]::-webkit-calendar-picker-indicator {
        cursor: pointer;
        opacity: 0.6;
        padding: 0;
        margin-left: 2px;
      }
      .date-filter-field input[type="date"]::-webkit-calendar-picker-indicator:hover {
        opacity: 1;
      }
      .date-range-sep {
        font-size: 0.8rem;
        color: var(--text-muted);
        user-select: none;
      }
      .btn-apply-date {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: var(--brand-green);
        border: 1px solid var(--brand-green);
        border-radius: 7px;
        padding: 4px 9px;
        font-size: 0.74rem;
        font-weight: 700;
        color: #ffffff;
        cursor: pointer;
        transition: all 0.16s ease;
      }
      .btn-apply-date:hover {
        background: #15803d;
        border-color: #15803d;
      }
      .btn-refresh-date {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        border-radius: 7px;
        padding: 4px 8px;
        font-size: 0.74rem;
        font-weight: 700;
        color: #475569;
        cursor: pointer;
        transition: all 0.16s ease;
      }
      .btn-refresh-date:hover {
        background: #e2e8f0;
        color: var(--text-primary);
      }
      .btn-refresh-date.active {
        background: #dcfce7;
        border-color: #86efac;
        color: #15803d;
      }
      .btn-refresh-date.spinning svg {
        animation: spin-refresh 0.5s ease-in-out;
      }
      @keyframes spin-refresh {
        100% { transform: rotate(360deg); }
      }

      /* â”€â”€ View Header & Filter Pills â”€â”€ */
      .view-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
      }
      .view-header h1 {
        font-family: 'Outfit', sans-serif;
        font-size: 1.6rem;
        font-weight: 800;
        color: var(--text-primary);
      }
      .view-header p {
        font-size: 0.9rem;
        color: var(--text-secondary);
        margin-top: 2px;
      }
      .btn-primary-action {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: var(--brand-green);
        color: #ffffff;
        font-size: 0.86rem;
        font-weight: 700;
        padding: 0.62rem 1.25rem;
        border-radius: 10px;
        text-decoration: none;
        border: 1px solid var(--brand-green);
        cursor: pointer;
        transition: all 0.15s ease;
      }
      .btn-primary-action:hover {
        background: var(--brand-green-hover);
      }

      .toolbar-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
      }
      .filter-pills-row {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
      }
      .filter-pill {
        padding: 7px 16px;
        border-radius: 999px;
        border: 1px solid var(--border-color);
        background: #ffffff;
        font-size: 0.84rem;
        font-weight: 600;
        color: var(--text-secondary);
        cursor: pointer;
        transition: all 0.16s ease;
      }
      .filter-pill:hover {
        background: #f8fafc;
        color: var(--text-primary);
      }
      .filter-pill.active {
        background: var(--brand-green);
        border-color: var(--brand-green);
        color: #ffffff;
        font-weight: 700;
      }
      .search-input-wrap {
        position: relative;
        min-width: 250px;
      }
      .search-input-wrap svg {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted);
      }
      .search-input-wrap input {
        width: 100%;
        padding: 8px 12px 8px 34px;
        border-radius: 10px;
        border: 1px solid var(--border-color);
        background: #ffffff;
        font-size: 0.85rem;
        outline: none;
      }
      .search-input-wrap input:focus {
        border-color: var(--brand-green);
      }

      /* â”€â”€ Settings View â”€â”€ */
      .settings-card {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 14px;
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 1.2rem;
      }
      .settings-card-title {
        font-size: 0.98rem;
        font-weight: 700;
        color: var(--text-primary);
      }
      .settings-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1.15rem 1.4rem;
      }
      .settings-field {
        display: flex;
        flex-direction: column;
        gap: 6px;
      }
      .settings-field label {
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--text-secondary);
      }
      .settings-input, .settings-select {
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        color: #1e293b;
        padding: 10px 14px;
        border-radius: 9px;
        font-size: 0.9rem;
        font-family: inherit;
        outline: none;
        width: 100%;
      }
      .settings-input:focus, .settings-select:focus {
        background: #ffffff;
        border-color: var(--brand-green);
      }
      .settings-input[readonly] {
        caret-color: transparent;
        cursor: default;
        user-select: none;
        background: #f8fafc;
        color: #475569;
      }
      .settings-input[readonly]:focus {
        background: #f8fafc;
        border-color: #cbd5e1;
        box-shadow: none;
        outline: none;
      }
      .settings-save-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        background: var(--brand-green);
        color: #ffffff;
        border: 1px solid var(--brand-green);
        font-size: 0.86rem;
        font-weight: 700;
        cursor: pointer;
        padding: 0.65rem 1.4rem;
        border-radius: 9px;
        transition: all 0.16s ease;
      }
      .settings-save-btn:hover {
        background: var(--brand-green-hover);
      }
      #btnSubmitPw {
        background: #2563eb;
        border-color: #2563eb;
      }
      #btnSubmitPw:hover {
        background: #1d4ed8;
        border-color: #1d4ed8;
      }
      .settings-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.85rem 0;
        border-bottom: 1px solid var(--border-light);
      }
      .settings-row:last-child {
        border-bottom: none;
        padding-bottom: 0;
      }
      .settings-row-text {
        display: flex;
        flex-direction: column;
        gap: 2px;
      }
      .settings-row-title {
        font-size: 0.9rem;
        font-weight: 700;
        color: var(--text-primary);
      }
      .settings-row-desc {
        font-size: 0.8rem;
        color: var(--text-secondary);
      }

      .toggle-switch {
        position: relative;
        width: 44px;
        height: 24px;
        border-radius: 999px;
        background: var(--brand-green);
        cursor: pointer;
        transition: background 0.2s;
      }
      .toggle-switch.off {
        background: #cbd5e1;
      }
      .toggle-switch-circle {
        position: absolute;
        top: 3px;
        right: 3px;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: #ffffff;
        transition: transform 0.2s;
      }
      .toggle-switch.off .toggle-switch-circle {
        transform: translateX(-20px);
      }

      /* â”€â”€ Empty State Styling â”€â”€ */
      .empty-state-box {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 3rem 1.5rem;
        background: #ffffff;
        border-radius: 14px;
        border: 1px dashed #cbd5e1;
      }
      .empty-state-box.compact {
        padding: 1.75rem 1.25rem;
        border: 1px dashed #e2e8f0;
        background: #f8fafc;
      }
      .empty-state-icon {
        width: 56px;
        height: 56px;
        border-radius: 16px;
        background: var(--brand-green-light);
        border: 1px solid var(--brand-green-border);
        color: var(--brand-green);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 0.85rem;
      }
      .empty-state-icon.blue {
        background: #f0f9ff;
        border-color: #bae6fd;
        color: #0284c7;
      }
      .empty-state-icon.amber {
        background: #fefce8;
        border-color: #fef08a;
        color: #d97706;
      }
      .empty-state-title {
        font-family: 'Outfit', sans-serif;
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 5px;
      }
      .empty-state-desc {
        font-size: 0.85rem;
        color: var(--text-secondary);
        max-width: 420px;
        line-height: 1.5;
        margin-bottom: 1.15rem;
      }
      .empty-state-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        justify-content: center;
      }

      /* â”€â”€ Modals â”€â”€ */
      .modal-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.45);
        backdrop-filter: blur(4px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 150;
        padding: 1.5rem;
      }
      .modal-backdrop.active {
        display: flex;
      }
      .modal-dialog {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 16px;
        width: 100%;
        max-width: 560px;
        box-shadow: 0 22px 48px rgba(0, 0, 0, 0.16);
        overflow: hidden;
      }
      .modal-top {
        padding: 1.2rem 1.5rem;
        border-bottom: 1px solid var(--border-light);
        display: flex;
        justify-content: space-between;
        align-items: center;
      }
      .modal-top h3 {
        font-size: 1.1rem;
        font-weight: 800;
      }
      .btn-close-modal {
        background: none;
        border: none;
        color: var(--text-secondary);
        font-size: 1.4rem;
        cursor: pointer;
      }
      .modal-body {
        padding: 1.4rem 1.5rem;
        max-height: 75vh;
        overflow-y: auto;
      }
      .detail-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-bottom: 1rem;
      }
      .detail-box {
        background: #f8fafc;
        border: 1px solid var(--border-color);
        border-radius: 10px;
        padding: 10px 12px;
      }
      .detail-box-label {
        font-size: 0.68rem;
        font-weight: 700;
        color: var(--text-secondary);
        text-transform: uppercase;
        margin-bottom: 3px;
      }
      .detail-box-val {
        font-size: 0.88rem;
        font-weight: 700;
        color: var(--text-primary);
      }

      .pw-input-wrap {
        position: relative;
      }
      .pw-eye-btn {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #64748b;
        cursor: pointer;
        display: flex;
        align-items: center;
      }

      @media (min-width: 1024px) {
        #view-dashboard.active {
          display: grid;
          grid-template-columns: minmax(0, 1.7fr) minmax(260px, 0.78fr);
          gap: 1.15rem;
          align-items: start;
        }
        #view-dashboard .hero-banner {
          grid-column: 1 / -1;
        }
        #view-dashboard .content-grid {
          grid-column: 1;
          grid-row: 2;
        }
        #view-dashboard .metrics-row {
          grid-column: 2;
          grid-row: 2;
          display: flex;
          flex-direction: column;
          gap: 0.85rem;
        }
      }
      @media (max-width: 1024px) {
        .metrics-row { grid-template-columns: repeat(2, 1fr); }
        .dept-cards-grid, .content-grid { grid-template-columns: 1fr; }
      }
      @media (prefers-reduced-motion: reduce) {
        .nav-link, .btn-hero-white, .btn-hero-outline, .btn-top-icon, .metric-card {
          transition: none;
        }
      }
      @media (max-width: 768px) {
        .app-layout { flex-direction: column; }
        .sidebar {
          width: 100%;
          height: auto;
          position: relative;
          flex-direction: row;
          align-items: center;
          padding: 0.75rem 1rem;
        }
        .sidebar-brand-box { width: 44px; height: 44px; min-width: 44px; }
        .brand-text-block, .sidebar-section-lbl, .sidebar-footer { display: none; }
        .sidebar-nav {
          flex-direction: row;
          gap: 4px;
          min-width: 0;
          overflow-x: auto;
          overflow-y: hidden;
        }
        .nav-link {
          min-height: 44px;
          white-space: nowrap;
        }
        .topbar {
          height: auto;
          min-height: 60px;
          padding: 0.75rem 1rem;
          flex-wrap: wrap;
        }
        .dashboard-canvas { padding: 1rem; max-width: 100%; }
        .detail-grid { grid-template-columns: 1fr; }
        .metrics-row, .med-specs-grid, .settings-grid { grid-template-columns: 1fr; }
      }
    </style>
      <link rel="stylesheet" href="/sdn/assets/css/dashboard-mobile.css?v=<?= time() ?>" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js" defer></script>
  </head>
  <body>

    <div class="app-layout" id="standalone-dashboard">
      <div class="mobile-sidebar-backdrop" id="mobileSidebarBackdrop"></div>

      <!-- â”€â”€ Left Sidebar â”€â”€ -->
      <aside class="sidebar">
        <div>
          <div class="sidebar-brand">
            <div class="sidebar-brand-box">
              <img src="/sdn/public/assets/v3_lingkod_logo_green.png" alt="LINGKOD" />
            </div>
            <div class="brand-text-block">
              <span class="brand-sub" data-i18n="brand_sub">Mariveles Mental Wellness and General Hospital</span>
              <span class="brand-title">LINGKOD</span>
            </div>
          </div>

          <nav class="sidebar-nav">
            <a class="nav-link active" onclick="switchView('dashboard', this)">
              <span class="nav-link-left">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="14" width="7" height="7" rx="1"></rect><rect x="3" y="14" width="7" height="7" rx="1"></rect></svg>
                <span data-i18n="nav_dashboard">Dashboard</span>
              </span>
            </a>

            <a class="nav-link" onclick="switchView('appointments', this)">
              <span class="nav-link-left">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                <span data-i18n="nav_appointments">Appointments</span>
              </span>
              <span class="nav-pill-count" id="sideApptCount" hidden>0</span>
            </a>
          </nav>
        </div>

        <div class="sidebar-footer">
          <div class="sidebar-help-card">
            <div class="sidebar-help-title">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.58 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
              <span>Patient Assistance Desk</span>
            </div>
            <div class="sidebar-help-sub">Mon &ndash; Sat &bull; 8:00 AM to 5:00 PM<br>Please bring your Patient ID on your clinic visit.</div>
          </div>

          <a class="nav-link" id="nav-settings" onclick="switchView('settings', this)">
            <span class="nav-link-left">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
              <span data-i18n="nav_settings">Settings</span>
            </span>
          </a>
        </div>
      </aside>

      <!-- â”€â”€ Right Main Area â”€â”€ -->
      <main class="main-content">

        <!-- Top Bar -->
        <header class="topbar">
          <div class="topbar-left">
            <button type="button" class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Open navigation menu" aria-expanded="false"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg></button>
            <a href="#view-dashboard" class="mobile-topbar-brand" onclick="switchView('dashboard'); return false;">
              <img src="/sdn/public/assets/v3_lingkod_logo_green.png" alt="LINGKOD" class="mobile-topbar-logo" />
              <span class="mobile-topbar-title">LINGKOD</span>
            </a>
            <span class="topbar-id-chip" id="topPatientIdChip">Patient ID: <?= htmlspecialchars($patientId ?: 'Verified') ?></span>
          </div>

          <div class="topbar-right">
            <!-- Notifications -->
            <div class="notif-container" id="notifContainer">
              <button class="btn-top-icon" id="notifBellBtn" title="Notifications" aria-label="Notifications" onclick="toggleNotifDropdown(event)">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                <span class="notif-badge hidden" id="notifBadge">0</span>
              </button>

              <div class="notif-dropdown" id="notifDropdown">
                <div class="notif-header">
                  <div class="notif-title-wrap">
                    <span data-i18n="notifs_head">Notifications</span>
                    <span class="notif-count-pill" id="notifCountPill">0 new</span>
                  </div>
                  <button class="notif-mark-read" onclick="markAllNotifsRead(event)" data-i18n="notifs_mark_read">Mark all as read</button>
                </div>

                <div class="notif-list" id="notifList"></div>

                <div class="notif-footer">
                  <button onclick="clearAllNotifs(event)" data-i18n="notifs_clear">Clear all notifications</button>
                </div>
              </div>
            </div>

            <!-- Profile Menu -->
            <div class="profile-container" id="profileContainer">
              <button type="button" class="user-avatar-btn" aria-label="Account menu" onclick="toggleProfileDropdown(event)">
                <div class="user-avatar-circle" id="topAvatarInitials"><?= htmlspecialchars($initials) ?></div>
                <span class="user-avatar-name" id="topAvatarName"><?= htmlspecialchars($firstName) ?></span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2.2"><polyline points="6 9 12 15 18 9"></polyline></svg>
              </button>

              <div class="profile-dropdown" id="profileDropdown">
                <div class="profile-dd-head">
                  <div class="profile-dd-name" id="ddFullName"><?= htmlspecialchars($patientName) ?></div>
                  <div class="profile-dd-sub" id="ddPatientId"><?= htmlspecialchars($patientId ?: $patientEmail) ?></div>
                </div>
                <button type="button" class="profile-dd-item" onclick="closeProfileDropdown(); switchView('settings');">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                  <span>Account Settings</span>
                </button>
                <a href="/sdn/api/patient_logout.php" class="profile-dd-item profile-dd-logout">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                  <span>Logout</span>
                </a>
              </div>
            </div>
          </div>
        </header>

        <!-- Dynamic Views Canvas -->
        <div class="dashboard-canvas">

          <!-- â•â•â•â•â• VIEW 1: DASHBOARD â•â•â•â•â• -->
          <section id="view-dashboard" class="view-section active">

            <!-- Welcome Hero Banner -->
            <div class="hero-banner">
              <div class="hero-banner-left">
                <div class="hero-eyebrow">
                  <span>Patient Portal</span>
                </div>
                <h1 id="heroGreeting">Good morning, <?= htmlspecialchars($firstName) ?></h1>
                <p data-i18n="hero_sub">Manage your hospital appointments and book consultations across MMWGH departments.</p>
                <button type="button" class="mobile-hero-id-chip" id="mobileHeroIdChip" aria-label="Copy Patient ID">
                  <span id="mobileHeroIdText">ID: <?= htmlspecialchars($patientId ?: 'Verified') ?></span>
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                  <span id="mobileHeroIdCopyLabel" style="opacity:0.85;">Copy</span>
                </button>
              </div>
              <div class="hero-actions">
                <a href="/sdn/pages/patient-book-appointment.php" class="btn-hero-white">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                  <span data-i18n="book_appointment">Book Appointment</span>
                </a>
                <a href="/sdn/pages/schedules.php" class="btn-hero-outline">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                  <span data-i18n="view_schedule">Doctor Schedules</span>
                </a>
              </div>
            </div>

            <!-- Main Content: Upcoming Appointments -->
            <div class="content-grid">
              <div class="section-card">
                <div class="section-header">
                  <div class="section-title-group">
                    <div class="section-title-icon">
                      <svg width="18" height="18" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75Z" clip-rule="evenodd" />
                      </svg>
                    </div>
                    <div>
                      <span class="section-title" data-i18n="upcoming_appts">Upcoming Appointments</span>
                      <div style="font-size:0.8rem; color:var(--text-secondary); margin-top:2px; line-height:1.4;">Scheduled consultations and visits</div>
                    </div>
                  </div>
                  <button type="button" class="section-link" onclick="switchView('appointments')">View all</button>
                </div>
                <div class="card-item-list" id="dashUpcomingList"></div>
              </div>
            </div>

            <!-- Summary cards -->
            <div class="metrics-row">
              <div class="metric-card">
                <div>
                  <div class="metric-lbl" data-i18n="next_appt">Next Appointment</div>
                  <div class="metric-value" id="nextApptDate">None</div>
                  <div class="metric-sub" id="nextApptTime">No upcoming visit</div>
                  <div class="mobile-next-appt-status" id="mobileNextApptStatus"></div>
                </div>
                <div class="metric-icon-box green">
                  <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75Z" clip-rule="evenodd" />
                  </svg>
                </div>
              </div>

              <div class="metric-card">
                <div>
                  <div class="metric-lbl">Scheduled Visits</div>
                  <div class="metric-value" id="upcomingVisitsVal">0</div>
                  <div class="metric-sub">Active bookings</div>
                </div>
                <div class="metric-icon-box blue">
                  <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M7 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM14.5 9a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5ZM1.615 16.428a1.224 1.224 0 0 1-.569-1.175 6.002 6.002 0 0 1 11.908 0c.058.467-.172.92-.57 1.174A9.953 9.953 0 0 1 7 18a9.953 9.953 0 0 1-5.385-1.572ZM14.5 16h-.106c.07-.297.088-.611.048-.933a7.47 7.47 0 0 0-1.588-3.755 4.502 4.502 0 0 1 5.874 2.636.818.818 0 0 1-.36.98A7.465 7.465 0 0 1 14.5 16Z" />
                  </svg>
                </div>
              </div>

              <div class="metric-card">
                <div>
                  <div class="metric-lbl">Completed Visits</div>
                  <div class="metric-value" id="pastVisitsVal">0</div>
                  <div class="metric-sub" id="totalVisitsSubText">Consultation history</div>
                </div>
                <div class="metric-icon-box amber">
                  <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" />
                  </svg>
                </div>
              </div>
            </div>
          </section>

          <!-- â•â•â•â•â• VIEW 2: APPOINTMENTS â•â•â•â•â• -->
          <section id="view-appointments" class="view-section">
            <div class="view-header">
              <div>
                <h1 data-i18n="appts_title">Appointments &amp; Visit History</h1>
                <p data-i18n="appts_sub">Track your scheduled consultations, previous appointment dates, and completed clinical visits</p>
              </div>
              <a href="/sdn/pages/patient-book-appointment.php" class="btn-primary-action">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <span data-i18n="book_appointment">Book New Appointment</span>
              </a>
            </div>

            <div class="toolbar-row">
              <div class="filter-pills-row">
                <button class="filter-pill active" id="btnApptUpcoming" onclick="filterApptTab('upcoming', this)">Upcoming (0)</button>
                <button class="filter-pill" id="btnApptPast" onclick="filterApptTab('past', this)">Past / Completed (0)</button>
                <button class="filter-pill" id="btnApptCancelled" onclick="filterApptTab('cancelled', this)">Cancelled (0)</button>
              </div>
              <div class="toolbar-controls">
                <div class="date-range-filter-wrap">
                  <div class="date-filter-field">
                    <label for="apptDateFrom" class="date-filter-label">From</label>
                    <input type="date" id="apptDateFrom" title="Start date" onkeydown="if(event.key==='Enter') paintAppointmentList()" />
                  </div>
                  <span class="date-range-sep">&ndash;</span>
                  <div class="date-filter-field">
                    <label for="apptDateTo" class="date-filter-label">To</label>
                    <input type="date" id="apptDateTo" title="End date" onkeydown="if(event.key==='Enter') paintAppointmentList()" />
                  </div>
                  <button type="button" class="btn-apply-date" id="btnApplyDate" onclick="paintAppointmentList()" title="Apply date filter">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span>Apply</span>
                  </button>
                  <button type="button" class="btn-refresh-date" id="btnRefreshDate" onclick="resetApptDateFilter()" title="Refresh &amp; clear date filter">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                    <span>Refresh</span>
                  </button>
                </div>
                <div class="search-input-wrap">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                  <input type="text" id="apptSearchInput" placeholder="Search department, ID, date..." oninput="paintAppointmentList()" />
                </div>
              </div>
            </div>

            <div class="section-card">
              <div class="card-item-list" id="fullApptsList"></div>
            </div>
          </section>

          <!-- â•â•â•â•â• VIEW 3: SETTINGS â•â•â•â•â• -->
          <section id="view-settings" class="view-section">
            <div class="view-header">
              <div>
                <h1 data-i18n="settings_title">Account Settings</h1>
                <p data-i18n="settings_sub">Manage your patient profile, language preferences, and account security</p>
              </div>
            </div>

            <div class="settings-card">
              <span class="settings-card-title" data-i18n="settings_profile">Patient Profile</span>
              <div style="display:flex; align-items:center; gap:16px; padding:12px 14px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:10px; margin-bottom:16px;">
                <div id="patientSettingsAvatarPreview" style="width:56px; height:56px; border-radius:50%; background:#15803d; color:#ffffff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:1.05rem; overflow:hidden; flex-shrink:0;">
                  <?= htmlspecialchars($initials) ?>
                </div>
                <div style="flex:1; min-width:0;">
                  <div style="font-size:0.85rem; font-weight:600; color:#111827;">Profile Photo</div>
                  <div style="font-size:0.75rem; color:#6b7280; margin-top:2px;">PNG, JPG, or WEBP up to 5MB.</div>
                  <div style="display:flex; align-items:center; gap:8px; margin-top:8px;">
                    <input type="file" id="patientSettingsAvatarInput" accept="image/png,image/jpeg,image/webp" hidden />
                    <button type="button" class="btn-outline-sm" onclick="document.getElementById('patientSettingsAvatarInput').click()">Upload Photo</button>
                    <button type="button" class="btn-outline-sm" id="patientSettingsAvatarRemoveBtn" style="color:#b91c1c; display:none;" onclick="removePatientAvatar()">Remove</button>
                  </div>
                </div>
              </div>
              <div class="settings-grid">
                <div class="settings-field">
                  <label for="setFullName" data-i18n="settings_fname">Full name</label>
                  <input type="text" id="setFullName" class="settings-input" value="<?= htmlspecialchars($patientName) ?>" readonly />
                </div>

                <div class="settings-field">
                  <label for="setPatientCode">Patient ID</label>
                  <input type="text" id="setPatientCode" class="settings-input" value="<?= htmlspecialchars($patientId) ?>" readonly />
                </div>

                <div class="settings-field">
                  <label for="setEmail" data-i18n="settings_email">Email address</label>
                  <input type="email" id="setEmail" class="settings-input" value="<?= htmlspecialchars($patientEmail) ?>" readonly />
                </div>

                <div class="settings-field">
                  <label for="setPhone" data-i18n="settings_phone">Contact number</label>
                  <input type="text" id="setPhone" class="settings-input" value="" readonly />
                </div>

                <div class="settings-field">
                  <label for="setLanguage" data-i18n="settings_lang">Portal Language</label>
                  <select id="setLanguage" class="settings-select" onchange="onLanguageChange(this.value)">
                    <option value="en">English</option>
                    <option value="tl">Filipino (Tagalog)</option>
                  </select>
                </div>
              </div>

              <div>
                <button type="button" id="btnSaveSettings" class="settings-save-btn" onclick="saveSettings()">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                  <span data-i18n="settings_save">Save preferences</span>
                </button>
              </div>
            </div>

            <div class="settings-card">
              <span class="settings-card-title">Digital Patient ID (QR Code)</span>
              <div style="display:flex; align-items:center; gap:20px; flex-wrap:wrap; margin-top:10px;">
                <div id="patientIdCardPreview" style="background:linear-gradient(135deg,#166534 0%,#15803d 58%,#047857 100%); color:#ffffff; border-radius:14px; padding:18px 20px; display:flex; align-items:center; justify-content:space-between; gap:18px; flex-wrap:wrap; flex:1; min-width:290px; max-width:480px; box-shadow:0 6px 18px rgba(21,128,61,0.18);">
                  <div style="flex:1; min-width:170px;">
                    <div style="font-size:0.65rem; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; color:#bbf7d0; margin-bottom:10px;">MMWGH LINGKOD &bull; Digital Patient ID</div>
                    <div style="display:flex; align-items:center; gap:12px;">
                      <div id="qrCardAvatar" style="width:52px; height:52px; border-radius:50%; background:rgba(255,255,255,0.18); border:2px solid rgba(255,255,255,0.85); color:#ffffff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:1rem; overflow:hidden; flex-shrink:0;">
                        <?= htmlspecialchars($initials) ?>
                      </div>
                      <div style="min-width:0;">
                        <div style="font-size:1rem; font-weight:700; color:#ffffff; line-height:1.25; word-break:break-word;" id="qrCardFullName"><?= htmlspecialchars($patientName) ?></div>
                        <div style="display:inline-block; margin-top:5px; padding:2px 9px; border-radius:999px; background:rgba(255,255,255,0.16); border:1px solid rgba(255,255,255,0.3); font-size:0.78rem; font-weight:600; color:#ecfdf5;" id="qrCardPatientId"><?= htmlspecialchars($patientId) ?></div>
                      </div>
                    </div>
                  </div>
                  <div id="patientQrBox" style="padding:8px; background:#ffffff; border-radius:10px; display:inline-flex; align-items:center; justify-content:center; width:126px; height:126px; flex-shrink:0;"></div>
                </div>
                <div style="flex:1; min-width:200px;">
                  <p style="font-size:0.82rem; color:#6b7280; margin:0 0 12px; line-height:1.5;">
                    Present this Digital Patient ID at the MMWGH clinic desk so staff can scan your QR code and verify your profile.
                  </p>
                  <button type="button" class="btn-outline-sm" onclick="downloadPatientQr()">
                    Download ID Card (PNG)
                  </button>
                </div>
              </div>
            </div>

            <div class="settings-card">
              <span class="settings-card-title" data-i18n="settings_notifs">Notifications</span>
              <div>
                <div class="settings-row">
                  <div class="settings-row-text">
                    <span class="settings-row-title" data-i18n="notif_reminders_title">Appointment reminders</span>
                    <span class="settings-row-desc" data-i18n="notif_reminders_sub">Receive notifications before your scheduled clinic visit</span>
                  </div>
                  <div class="toggle-switch" id="toggleApptReminders" onclick="toggleApptReminders(this)">
                    <div class="toggle-switch-circle"></div>
                  </div>
                </div>
              </div>
            </div>

            <div class="settings-card">
              <span class="settings-card-title" data-i18n="settings_security">Security &amp; Session</span>
              <div>
                <div class="settings-row">
                  <div class="settings-row-text">
                    <span class="settings-row-title" data-i18n="sec_pw_title">Account Password</span>
                    <span class="settings-row-desc">Update your patient portal sign-in password</span>
                  </div>
                  <button type="button" class="btn-outline-sm" onclick="openPasswordModal()" data-i18n="sec_pw_btn">Change Password</button>
                </div>

                <div class="settings-row">
                  <div class="settings-row-text">
                    <span class="settings-row-title" style="color: #dc2626;" data-i18n="sec_signout_title">Sign out</span>
                    <span class="settings-row-desc" data-i18n="sec_signout_sub">End your active patient session</span>
                  </div>
                  <a href="/sdn/api/patient_logout.php" style="color: #dc2626; font-size: 0.85rem; font-weight: 700; text-decoration: none;" data-i18n="sec_signout_title">Sign out</a>
                </div>
              </div>
            </div>
          </section>

        </div>
      </main>

      <!-- Mobile Bottom Navigation -->
      <nav class="mobile-bottom-nav" aria-label="Mobile Navigation">
        <button type="button" class="mobile-nav-item active" data-view="dashboard" aria-current="page">
          <span class="mobile-nav-icon-wrap" aria-hidden="true">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="14" width="7" height="7" rx="1"></rect><rect x="3" y="14" width="7" height="7" rx="1"></rect></svg>
          </span>
          <span>Dashboard</span>
        </button>
        <button type="button" class="mobile-nav-item" data-view="appointments">
          <span class="mobile-nav-icon-wrap" aria-hidden="true">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            <span class="mobile-nav-badge is-empty" id="mobileApptCount">0</span>
          </span>
          <span>Appointments</span>
        </button>
        <button type="button" class="mobile-nav-item" data-view="settings">
          <span class="mobile-nav-icon-wrap" aria-hidden="true">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
          </span>
          <span>Settings</span>
        </button>
      </nav>
    </div>

    <!-- â”€â”€ Reschedule Modal â”€â”€ -->
    <div id="rescheduleModal" class="modal-backdrop">
      <div class="modal-dialog">
        <div class="modal-top">
          <h3>Reschedule Appointment</h3>
          <button class="btn-close-modal" onclick="closeRescheduleModal()">&times;</button>
        </div>
        <div class="modal-body">
          <p id="rescheduleSummary" style="font-size:0.88rem; color:#475569; margin-bottom:14px;"></p>
          <div class="settings-field" style="margin-bottom:12px;">
            <label for="rescheduleDate">New Date</label>
            <input type="date" id="rescheduleDate" class="settings-input" />
          </div>
          <div class="settings-field" style="margin-bottom:14px;">
            <label for="rescheduleTime">Clinic Session</label>
            <select id="rescheduleTime" class="settings-select">
              <option value="08:00:00">08:00 AM &ndash; 10:00 AM</option>
              <option value="10:00:00">10:00 AM &ndash; 12:00 PM</option>
              <option value="13:00:00">01:00 PM &ndash; 03:00 PM</option>
              <option value="15:00:00">03:00 PM &ndash; 05:00 PM</option>
            </select>
          </div>
          <p id="rescheduleError" style="display:none; color:#b91c1c; font-size:0.84rem; margin-bottom:10px;"></p>
          <button type="button" class="settings-save-btn" id="rescheduleSaveBtn" onclick="submitReschedule()">Save New Schedule</button>
        </div>
      </div>
    </div>



    <!-- â”€â”€ Change Password Modal â”€â”€ -->
    <div id="passwordModal" class="modal-backdrop">
      <div class="modal-dialog" style="max-width:440px;">
        <div class="modal-top">
          <h3>Change Account Password</h3>
          <button class="btn-close-modal" onclick="closePasswordModal()">&times;</button>
        </div>
        <div class="modal-body">
          <div class="settings-field" style="margin-bottom:12px;">
            <label for="pwCurrent">Current Password</label>
            <div class="pw-input-wrap">
              <input type="password" id="pwCurrent" class="settings-input" placeholder="Enter current password" />
              <button type="button" class="pw-eye-btn" onclick="togglePwField('pwCurrent')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
              </button>
            </div>
          </div>
          <div class="settings-field" style="margin-bottom:12px;">
            <label for="pwNew">New Password</label>
            <div class="pw-input-wrap">
              <input type="password" id="pwNew" class="settings-input" placeholder="Min. 8 chars (A-z, 0-9, symbol)" />
              <button type="button" class="pw-eye-btn" onclick="togglePwField('pwNew')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
              </button>
            </div>
          </div>
          <div class="settings-field" style="margin-bottom:14px;">
            <label for="pwConfirm">Confirm New Password</label>
            <div class="pw-input-wrap">
              <input type="password" id="pwConfirm" class="settings-input" placeholder="Re-enter new password" />
              <button type="button" class="pw-eye-btn" onclick="togglePwField('pwConfirm')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
              </button>
            </div>
          </div>
          <p id="pwMsg" style="display:none; font-size:0.84rem; margin-bottom:10px; font-weight:600;"></p>
          <div style="display:flex; justify-content:center; margin-top:4px;">
            <button type="button" class="settings-save-btn" id="btnSubmitPw" onclick="submitPasswordChange()">Update Password</button>
          </div>
        </div>
      </div>
    </div>

    <script>
      let portalData = {
        profile: {},
        stats: { upcoming_count: 0, past_count: 0, cancelled_count: 0, active_meds: 0, total_visits: 0 },
        upcoming: [],
        past: [],
        cancelled: [],
        medications: [],
        notifications: []
      };

      let currentLang = localStorage.getItem('sdn_patient_lang') || 'en';
      let currentApptTab = 'upcoming';
      let rescheduleTarget = null;

      const i18nDict = {
        en: {
          nav_dashboard: "Dashboard",
          nav_appointments: "Appointments",
          nav_settings: "Settings",
          brand_sub: "Mariveles Mental Wellness and General Hospital",
          greeting_prefix: "Good morning",
          hero_sub: "Manage your hospital appointments and book consultations across MMWGH departments.",
          next_appt: "Next Appointment",
          book_by_dept: "Book by Clinical Department",
          gen_med: "General Medical",
          neuro: "Neurology",
          psych: "Psychiatry",
          upcoming_appts: "Upcoming Appointments",
          view_all: "View all",
          book_appointment: "Book Appointment",
          view_schedule: "Doctor Schedules",
          appts_title: "Appointments & Visit History",
          appts_sub: "Track your scheduled consultations, previous appointment dates, and completed clinical visits",
          tab_upcoming: "Upcoming",
          tab_past: "Past / Completed",
          tab_cancelled: "Cancelled",
          settings_title: "Account Settings",
          settings_sub: "Manage your patient profile, language preferences, and account security",
          settings_profile: "Patient Profile",
          settings_fname: "Full name",
          settings_email: "Email address",
          settings_phone: "Contact number",
          settings_lang: "Portal Language",
          settings_save: "Save preferences",
          settings_notifs: "Notifications",
          notif_reminders_title: "Appointment reminders",
          notif_reminders_sub: "Receive notifications before your scheduled clinic visit",
          settings_security: "Security & Session",
          sec_pw_title: "Account Password",
          sec_pw_btn: "Change Password",
          sec_signout_title: "Sign out",
          sec_signout_sub: "End your active patient session",
          notifs_head: "Notifications",
          notifs_mark_read: "Mark all as read",
          notifs_clear: "Clear all notifications"
        },
        tl: {
          nav_dashboard: "Dashboard",
          nav_appointments: "Mga Appointment",
          nav_settings: "Mga Setting",
          brand_sub: "Mariveles Mental Wellness and General Hospital",
          greeting_prefix: "Magandang araw",
          hero_sub: "Pamahalaan ang iyong mga appointment at magpa-iskedyul sa mga departamento ng MMWGH.",
          next_appt: "Susunod na Appointment",
          book_by_dept: "Magpa-iskedyul ayon sa Departamento",
          gen_med: "Pangkalahatang Medisina",
          neuro: "Neurolohiya",
          psych: "Saykayatrya",
          upcoming_appts: "Paparating na Appointment",
          view_all: "Tingnan lahat",
          book_appointment: "Magpa-appointment",
          view_schedule: "Iskedyul ng Doktor",
          appts_title: "Mga Appointment at Kasaysayan ng Pagbisita",
          appts_sub: "Subaybayan ang iyong mga nakatakdang konsultasyon at nakaraang mga pagbisita",
          tab_upcoming: "Paparating",
          tab_past: "Nakalipas / Tapos Na",
          tab_cancelled: "Kinansela",
          settings_title: "Mga Setting ng Account",
          settings_sub: "Pamahalaan ang iyong profile, wika, at seguridad ng account",
          settings_profile: "Profile ng Pasyente",
          settings_fname: "Buong pangalan",
          settings_email: "Email address",
          settings_phone: "Numero ng telepono",
          settings_lang: "Wika ng Portal",
          settings_save: "I-save ang pagbabago",
          settings_notifs: "Mga Abiso",
          notif_reminders_title: "Paalala sa appointment",
          notif_reminders_sub: "Makatanggap ng paalala bago ang nakatakdang pagbisita",
          settings_security: "Seguridad at Sesyon",
          sec_pw_title: "Password ng Account",
          sec_pw_btn: "Palitan ang Password",
          sec_signout_title: "Mag-sign out",
          sec_signout_sub: "Tapusin ang aktibong sesyon ng pasyente",
          notifs_head: "Mga Abiso",
          notifs_mark_read: "Markahan lahat na nabasa",
          notifs_clear: "Burahin lahat ng abiso"
        }
      };

      function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch]));
      }

      function getGreetingPrefix() {
        const hr = new Date().getHours();
        if (currentLang === 'tl') {
          if (hr < 12) return 'Magandang umaga';
          if (hr < 18) return 'Magandang hapon';
          return 'Magandang gabi';
        }
        if (hr < 12) return 'Good morning';
        if (hr < 18) return 'Good afternoon';
        return 'Good evening';
      }

      function applyLanguage(lang) {
        currentLang = lang || 'en';
        localStorage.setItem('sdn_patient_lang', currentLang);
        const t = i18nDict[currentLang] || i18nDict.en;

        const sel = document.getElementById('setLanguage');
        if (sel) sel.value = currentLang;

        document.querySelectorAll('[data-i18n]').forEach(el => {
          const key = el.getAttribute('data-i18n');
          if (t[key]) el.textContent = t[key];
        });

        const name = (portalData.profile && portalData.profile.full_name)
          ? portalData.profile.full_name.trim().split(' ')[0]
          : '<?= htmlspecialchars($firstName) ?>';
        const heroEl = document.getElementById('heroGreeting');
        if (heroEl) heroEl.textContent = `${getGreetingPrefix()}, ${name}`;

        if (portalData.stats) {
          const up = portalData.stats.upcoming_count || 0;
          const past = portalData.stats.past_count || 0;
          const canc = portalData.stats.cancelled_count || 0;
          const btnUp = document.getElementById('btnApptUpcoming');
          const btnPast = document.getElementById('btnApptPast');
          const btnCanc = document.getElementById('btnApptCancelled');
          if (btnUp) btnUp.textContent = `${t.tab_upcoming} (${up})`;
          if (btnPast) btnPast.textContent = `${t.tab_past} (${past})`;
          if (btnCanc) btnCanc.textContent = `${t.tab_cancelled} (${canc})`;
        }
      }

      function onLanguageChange(lang) {
        applyLanguage(lang);
      }

      async function initPortal() {
        try {
          applyLanguage(currentLang);
          const res = await fetch('/sdn/api/patient_dashboard_data.php?_=' + Date.now());
          if (res.status === 401) {
            window.location.href = '/sdn/pages/patient-login.php';
            return;
          }
          const data = await res.json();
          if (data.success) {
            portalData = data;
            renderDynamicData();
            applyLanguage(currentLang);
            syncApptRemindersToggle();
          }
        } catch (e) {
          console.error("Portal loading error:", e);
        }
      }

      function switchView(viewName, element) {
        if (viewName === 'medications') viewName = 'dashboard';
        let targetView = document.getElementById('view-' + viewName);
        if (!targetView) {
          viewName = 'dashboard';
          targetView = document.getElementById('view-dashboard');
        }
        document.querySelectorAll('.view-section').forEach(sec => sec.classList.remove('active'));
        if (targetView) targetView.classList.add('active');

        document.querySelectorAll('.sidebar .nav-link').forEach(link => link.classList.remove('active'));
        if (element) {
          element.classList.add('active');
        } else {
          document.querySelectorAll('.sidebar .nav-link').forEach(link => {
            if (link.getAttribute('onclick') && link.getAttribute('onclick').includes(viewName)) {
              link.classList.add('active');
            }
          });
        }
        const scroller = document.querySelector('.main-content');
        if (scroller) scroller.scrollTo({ top: 0, behavior: 'smooth' });
      }

      function renderDynamicData() {
        const prof = portalData.profile || {};
        if (prof.full_name) {
          const setInput = document.getElementById('setFullName');
          if (setInput) setInput.value = prof.full_name;
          const phoneInput = document.getElementById('setPhone');
          if (phoneInput) phoneInput.value = prof.contact_number || 'Not provided';
          const emailInput = document.getElementById('setEmail');
          if (emailInput && prof.email) emailInput.value = prof.email;
          const codeInput = document.getElementById('setPatientCode');
          if (codeInput) codeInput.value = prof.patient_id || '';
          const topChip = document.getElementById('topPatientIdChip');
          if (topChip) topChip.textContent = `Patient ID: ${prof.patient_id || 'Verified'}`;
          syncPatientAvatarUI(prof.profile_image || null);
          renderPatientQrCode(prof.patient_id || '', prof.full_name || '');
        }

        // Sidebar counts
        const sideAppt = document.getElementById('sideApptCount');
        if (sideAppt) {
          const upcomingCount = (portalData.upcoming || []).length;
          sideAppt.textContent = upcomingCount;
          sideAppt.hidden = upcomingCount === 0;
        }

        // KPI metrics
        const upcoming = portalData.upcoming || [];
        const past = portalData.past || [];

        const next = upcoming[0];
        const nextDate = document.getElementById('nextApptDate');
        const nextTime = document.getElementById('nextApptTime');
        if (nextDate) nextDate.textContent = next ? next.date : 'No Visit';
        if (nextTime) nextTime.textContent = next ? `${next.time_label} \u00B7 ${next.department}` : 'Book a consultation below';

        const upVal = document.getElementById('upcomingVisitsVal');
        if (upVal) upVal.textContent = upcoming.length;

        const pastVal = document.getElementById('pastVisitsVal');
        if (pastVal) pastVal.textContent = past.length;
        const totalSub = document.getElementById('totalVisitsSubText');
        if (totalSub) totalSub.textContent = `${portalData.stats?.total_visits || (upcoming.length + past.length)} total bookings on file`;

        renderAppointmentViews();
        renderNotifications();
      }

      function getStatusBadgeClass(statusText) {
        const s = (statusText || '').toLowerCase();
        if (s.includes('confirm') || s.includes('consult') || s.includes('check')) return 'status-confirmed';
        if (s.includes('complete')) return 'status-completed';
        if (s.includes('cancel') || s.includes('reject') || s.includes('no-show')) return 'status-cancelled';
        return 'status-waiting';
      }

      function renderAppointmentViews() {
        const upcoming = portalData.upcoming || [];
        const dash = document.getElementById('dashUpcomingList');
        if (dash) {
          dash.innerHTML = upcoming.length
            ? upcoming.slice(0, 4).map(apptDashRow).join('')
            : `
              <div class="empty-state-box compact">
                <div class="empty-state-icon" style="width:44px; height:44px; margin-bottom:0.75rem;">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                </div>
                <div class="empty-state-title" style="font-size:0.96rem;">No Upcoming Visits</div>
                <p class="empty-state-desc" style="font-size:0.82rem; margin-bottom:1rem;">You don't have any appointments scheduled. Book a visit with our outpatient clinic.</p>
                <a href="/sdn/pages/patient-book-appointment.php" class="btn-primary-action" style="font-size:0.8rem; padding:0.5rem 1rem;">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                  <span>Book Appointment</span>
                </a>
              </div>
            `;
        }
        paintAppointmentList();
      }

      function apptDashRow(item) {
        const stClass = getStatusBadgeClass(item.status);
        return `<div class="item-row" style="cursor:default;">
          <div class="appt-info-left">
            <div class="date-badge">
              <span class="date-month">${escapeHtml(item.month)}</span>
              <span class="date-day">${escapeHtml(item.day)}</span>
            </div>
            <div class="appt-desc">
              <div class="appt-title-row">
                <span class="appt-title">${escapeHtml(item.title)}</span>
                <span class="appt-code-tag">${escapeHtml(item.patient_code)}</span>
              </div>
              <span class="appt-doc">${escapeHtml(item.date)} at ${escapeHtml(item.time_label)} &bull; ${escapeHtml(item.mode)}</span>
            </div>
          </div>
          <div style="display:flex; align-items:center; gap:10px;">
            <div class="status-pill ${stClass}">
              <span class="status-dot"></span>
              <span>${escapeHtml(item.status)}</span>
            </div>
          </div>
        </div>`;
      }

      function resetApptDateFilter() {
        const fromInp = document.getElementById('apptDateFrom');
        const toInp = document.getElementById('apptDateTo');
        const btn = document.getElementById('btnRefreshDate');
        if (fromInp) fromInp.value = '';
        if (toInp) toInp.value = '';
        if (btn) {
          btn.classList.add('spinning');
          btn.classList.remove('active');
          setTimeout(() => btn.classList.remove('spinning'), 500);
        }
        paintAppointmentList();
      }

      function clearApptFilters() {
        const s = document.getElementById('apptSearchInput');
        if (s) s.value = '';
        resetApptDateFilter();
      }

      function paintAppointmentList() {
        const list = document.getElementById('fullApptsList');
        if (!list) return;
        const q = (document.getElementById('apptSearchInput')?.value || '').toLowerCase().trim();
        const dateFrom = document.getElementById('apptDateFrom')?.value || '';
        const dateTo = document.getElementById('apptDateTo')?.value || '';
        const refreshBtn = document.getElementById('btnRefreshDate');

        if (refreshBtn) {
          if (dateFrom || dateTo) {
            refreshBtn.classList.add('active');
          } else {
            refreshBtn.classList.remove('active');
          }
        }

        const groups = {
          upcoming: portalData.upcoming || [],
          past: portalData.past || [],
          cancelled: portalData.cancelled || []
        };
        let rows = groups[currentApptTab] || [];

        if (dateFrom) {
          rows = rows.filter(r => (r.date_raw || r.date_iso || '') >= dateFrom);
        }
        if (dateTo) {
          rows = rows.filter(r => (r.date_raw || r.date_iso || '') <= dateTo);
        }

        if (q) {
          rows = rows.filter(r =>
            (r.title || '').toLowerCase().includes(q) ||
            (r.patient_code || '').toLowerCase().includes(q) ||
            (r.date || '').toLowerCase().includes(q)
          );
        }

        if (!rows.length) {
          if (q || dateFrom || dateTo) {
            const dateDesc = (dateFrom && dateTo)
              ? `dates ${escapeHtml(dateFrom)} to ${escapeHtml(dateTo)}`
              : (dateFrom ? `from ${escapeHtml(dateFrom)}` : (dateTo ? `up to ${escapeHtml(dateTo)}` : null));
            const filterTerms = [
              q ? `keyword "${escapeHtml(q)}"` : null,
              dateDesc
            ].filter(Boolean).join(' and ');
            list.innerHTML = `
              <div class="empty-state-box">
                <div class="empty-state-icon blue">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </div>
                <div class="empty-state-title">No Matching Appointments</div>
                <p class="empty-state-desc">No appointment records found for ${filterTerms} under the ${escapeHtml(currentApptTab)} category.</p>
                <button type="button" class="btn-outline-sm" onclick="clearApptFilters()">Clear Filters</button>
              </div>
            `;
            return;
          }

          if (currentApptTab === 'past') {
            list.innerHTML = `
              <div class="empty-state-box">
                <div class="empty-state-icon blue">
                  <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                </div>
                <div class="empty-state-title">No Past Visits on Record</div>
                <p class="empty-state-desc">Your completed consultations, diagnosed clinical summaries, and previous visit logs will be securely archived here.</p>
                <div class="empty-state-actions">
                  <a href="/sdn/pages/patient-book-appointment.php" class="btn-primary-action">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    <span>Book New Appointment</span>
                  </a>
                </div>
              </div>
            `;
            return;
          }

          if (currentApptTab === 'cancelled') {
            list.innerHTML = `
              <div class="empty-state-box">
                <div class="empty-state-icon amber">
                  <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                </div>
                <div class="empty-state-title">No Cancelled Appointments</div>
                <p class="empty-state-desc">You do not have any cancelled consultations or revoked appointments.</p>
              </div>
            `;
            return;
          }

          list.innerHTML = `
            <div class="empty-state-box">
              <div class="empty-state-icon">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
              </div>
              <div class="empty-state-title">No Upcoming Appointments</div>
              <p class="empty-state-desc">You do not have any upcoming clinic visits scheduled. Book an in-person or telemedicine consultation with our physicians.</p>
              <div class="empty-state-actions">
                <a href="/sdn/pages/patient-book-appointment.php" class="btn-primary-action">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                  <span>Book Appointment</span>
                </a>
                <a href="/sdn/pages/schedules.php" class="btn-outline-sm">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                  <span>Doctor Schedules</span>
                </a>
              </div>
            </div>
          `;
          return;
        }
        list.innerHTML = rows.map(appointmentRow).join('');
      }

      function appointmentRow(item) {
        const stClass = getStatusBadgeClass(item.status);
        const reschedule = item.can_reschedule
          ? `<button type="button" class="btn-outline-sm" onclick="event.stopPropagation(); openRescheduleModal(${Number(item.id)})">Reschedule</button>`
          : '';

        return `<div class="item-row" style="cursor:default;">
          <div class="appt-info-left">
            <div class="date-badge">
              <span class="date-month">${escapeHtml(item.month)}</span>
              <span class="date-day">${escapeHtml(item.day)}</span>
            </div>
            <div class="appt-desc">
              <div class="appt-title-row">
                <span class="appt-title">${escapeHtml(item.title)}</span>
                <span class="appt-code-tag">${escapeHtml(item.patient_code)}</span>
              </div>
              <span class="appt-doc">${escapeHtml(item.date)} at ${escapeHtml(item.time_label)} &bull; ${escapeHtml(item.mode)}</span>
            </div>
          </div>
          <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
            <div class="status-pill ${stClass}">
              <span class="status-dot"></span>
              <span>${escapeHtml(item.status)}</span>
            </div>
            ${reschedule}
          </div>
        </div>`;
      }

      function persistNotificationAction(action, ids) {
        if (!ids || !ids.length) return;
        fetch('/sdn/api/patient_dashboard_data.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action, ids })
        }).catch(() => {});
      }

      function renderNotifications() {
        const listEl = document.getElementById('notifList');
        if (!listEl) return;
        const items = portalData.notifications || [];
        if (!items.length) {
          listEl.innerHTML = '<div style="padding: 24px 16px; text-align: center; color: var(--text-muted); font-size: 0.85rem;">No new notifications</div>';
          updateNotifCount(0);
          return;
        }
        listEl.innerHTML = items.map(n => {
          const isRead = Boolean(n.is_read);
          return `
          <div class="notif-item ${isRead ? '' : 'unread'}" onclick="handleNotifClick('${escapeHtml(n.view || 'appointments')}', this, '${escapeHtml(n.id || '')}')">
            <div class="notif-dot ${n.warn ? 'warn' : ''} ${isRead ? 'read' : ''}"></div>
            <div class="notif-content">
              <div class="notif-item-title">${escapeHtml(n.title)}</div>
              <div class="notif-item-text">${escapeHtml(n.text)}</div>
              <div class="notif-item-time">${escapeHtml(n.time)}</div>
            </div>
          </div>
        `;
        }).join('');
        updateNotifCount(items.filter(n => !n.is_read).length);
      }

      function toggleNotifDropdown(event) {
        if (event) event.stopPropagation();
        closeProfileDropdown();
        document.getElementById('notifDropdown').classList.toggle('show');
      }

      function toggleProfileDropdown(event) {
        if (event) event.stopPropagation();
        document.getElementById('notifDropdown').classList.remove('show');
        document.getElementById('profileDropdown').classList.toggle('show');
      }

      function closeProfileDropdown() {
        const pd = document.getElementById('profileDropdown');
        if (pd) pd.classList.remove('show');
      }

      function handleNotifClick(viewName, itemEl, notifId) {
        if (itemEl && itemEl.classList.contains('unread')) {
          itemEl.classList.remove('unread');
          const dot = itemEl.querySelector('.notif-dot');
          if (dot) dot.classList.add('read');
          updateNotifCount();
        }
        if (notifId && Array.isArray(portalData.notifications)) {
          const target = portalData.notifications.find(n => n.id === notifId);
          if (target) target.is_read = true;
          persistNotificationAction('mark_notifications_read', [notifId]);
        }
        document.getElementById('notifDropdown').classList.remove('show');
        switchView(viewName);
      }

      function markAllNotifsRead(event) {
        if (event) event.stopPropagation();
        document.querySelectorAll('#notifList .notif-item').forEach(item => {
          item.classList.remove('unread');
          const dot = item.querySelector('.notif-dot');
          if (dot) dot.classList.add('read');
        });
        updateNotifCount(0);
        const ids = (portalData.notifications || []).map(n => {
          n.is_read = true;
          return n.id;
        }).filter(Boolean);
        persistNotificationAction('mark_notifications_read', ids);
      }

      function clearAllNotifs(event) {
        if (event) event.stopPropagation();
        const ids = (portalData.notifications || []).map(n => n.id).filter(Boolean);
        portalData.notifications = [];
        const list = document.getElementById('notifList');
        list.innerHTML = '<div style="padding: 24px 16px; text-align: center; color: var(--text-muted); font-size: 0.85rem;">No new notifications</div>';
        updateNotifCount(0);
        persistNotificationAction('clear_notifications', ids);
      }

      function updateNotifCount(forcedCount) {
        const unreadCount = forcedCount !== undefined ? forcedCount : document.querySelectorAll('#notifList .notif-item.unread').length;
        const badge = document.getElementById('notifBadge');
        const pill = document.getElementById('notifCountPill');
        if (badge) {
          badge.textContent = unreadCount;
          badge.classList.toggle('hidden', unreadCount <= 0);
        }
        if (pill) pill.textContent = unreadCount > 0 ? `${unreadCount} new` : 'All read';
      }

      async function initNotificationServiceWorker() {
        if ('serviceWorker' in navigator) {
          try {
            await navigator.serviceWorker.register('/sdn/sw.js', { scope: '/sdn/' });
          } catch (err) {
            console.warn('SW registration info:', err);
          }
        }
      }

      async function sendNativeNotification(title, options = {}) {
        const notifOpts = {
          icon: '/sdn/public/assets/v3_lingkod_logo_green.png',
          badge: '/sdn/public/assets/v3_lingkod_logo_green.png',
          vibrate: [200, 100, 200],
          ...options
        };

        // ServiceWorker approach (works on Chrome Android mobile and desktop)
        if ('serviceWorker' in navigator) {
          try {
            const reg = await navigator.serviceWorker.ready;
            if (reg && typeof reg.showNotification === 'function') {
              await reg.showNotification(title, notifOpts);
              return true;
            }
          } catch (e) {
            console.warn('SW notification fallback:', e);
          }
        }

        // Desktop Chrome constructor fallback
        if ('Notification' in window && Notification.permission === 'granted') {
          try {
            new Notification(title, notifOpts);
            return true;
          } catch (e) {
            console.warn('Notification constructor error:', e);
          }
        }
        return false;
      }

      async function toggleApptReminders(switchEl) {
        if (!switchEl) switchEl = document.getElementById('toggleApptReminders');
        if (!switchEl) return;

        const isCurrentlyOff = switchEl.classList.contains('off');

        if (isCurrentlyOff) {
          if (!('Notification' in window)) {
            alert('Web notifications are not supported on this browser or connection.');
            return;
          }

          let perm = Notification.permission;
          if (perm === 'default') {
            try {
              perm = await Notification.requestPermission();
            } catch (e) {
              console.warn('Notification permission request:', e);
            }
          }

          if (perm === 'granted') {
            switchEl.classList.remove('off');
            localStorage.setItem('sdn_appt_reminders_enabled', '1');

            await sendNativeNotification('LINGKOD Appointment Reminders Active', {
              body: 'Appointment reminders are active. You will receive notifications before your scheduled visits.',
              tag: 'lingkod-reminders-status'
            });

            checkAndTriggerApptReminders();
          } else {
            switchEl.classList.add('off');
            localStorage.setItem('sdn_appt_reminders_enabled', '0');
            alert('Notifications are not permitted. Please allow notifications in Chrome site settings to receive appointment reminders.');
          }
        } else {
          switchEl.classList.add('off');
          localStorage.setItem('sdn_appt_reminders_enabled', '0');
        }
      }

      function checkAndTriggerApptReminders() {
        if (localStorage.getItem('sdn_appt_reminders_enabled') === '0') return;
        if (!('Notification' in window) || Notification.permission !== 'granted') return;

        const upcoming = portalData.upcoming || [];
        if (!upcoming.length) return;

        const todayIso = new Date().toISOString().slice(0, 10);
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        const tomorrowIso = tomorrow.toISOString().slice(0, 10);

        upcoming.forEach(appt => {
          const apptDate = appt.date_raw || appt.date_iso || '';
          if (apptDate === todayIso || apptDate === tomorrowIso) {
            const storageKey = `sdn_remind_sent_${appt.id}_${todayIso}`;
            if (localStorage.getItem(storageKey)) return;

            const isToday = (apptDate === todayIso);
            const promptDay = isToday ? 'today' : 'tomorrow';

            sendNativeNotification('LINGKOD Outpatient Visit Reminder', {
              body: `You have an appointment scheduled for ${promptDay} (${appt.date}) at ${appt.time_label}. Reference: ${appt.patient_code}. Proceed to Outpatient Triage.`,
              tag: `appt-reminder-${appt.id}`
            });

            localStorage.setItem(storageKey, '1');
          }
        });
      }

      function syncApptRemindersToggle() {
        const sw = document.getElementById('toggleApptReminders');
        if (!sw) return;

        const pref = localStorage.getItem('sdn_appt_reminders_enabled');
        const hasPerm = ('Notification' in window) && (Notification.permission === 'granted');

        if (pref === '0') {
          sw.classList.add('off');
        } else if (pref === '1' && hasPerm) {
          sw.classList.remove('off');
          checkAndTriggerApptReminders();
        } else {
          sw.classList.add('off');
        }
      }

      function toggleSwitch(switchEl) {
        toggleApptReminders(switchEl);
      }

      let currentPatientAvatarUrl = null;
      function syncPatientAvatarUI(imgUrl) {
        currentPatientAvatarUrl = imgUrl || null;
        const preview = document.getElementById('patientSettingsAvatarPreview');
        const topCircle = document.getElementById('topAvatarInitials');
        const qrAvatar = document.getElementById('qrCardAvatar');
        const removeBtn = document.getElementById('patientSettingsAvatarRemoveBtn');
        const fallbackInitials = <?= json_encode($initials) ?>;

        if (imgUrl) {
          const imgHtml = `<img src="${escapeHtml(imgUrl)}" alt="Profile Photo" style="width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;" />`;
          if (preview) preview.innerHTML = imgHtml;
          if (qrAvatar) qrAvatar.innerHTML = imgHtml;
          if (topCircle) {
            topCircle.style.padding = '0';
            topCircle.style.overflow = 'hidden';
            topCircle.innerHTML = imgHtml;
          }
          if (removeBtn) removeBtn.style.display = 'inline-flex';
        } else {
          if (preview) preview.textContent = fallbackInitials;
          if (qrAvatar) qrAvatar.textContent = fallbackInitials;
          if (topCircle) topCircle.textContent = fallbackInitials;
          if (removeBtn) removeBtn.style.display = 'none';
        }
      }

      document.getElementById('patientSettingsAvatarInput')?.addEventListener('change', (e) => {
        const file = e.target.files && e.target.files[0];
        if (!file) return;
        if (!['image/png', 'image/jpeg', 'image/webp'].includes(file.type)) {
          alert('Please select a PNG, JPG, or WEBP image.');
          e.target.value = '';
          return;
        }
        if (file.size > 5 * 1024 * 1024) {
          alert('Image must be 5MB or less.');
          e.target.value = '';
          return;
        }
        const reader = new FileReader();
        reader.onload = async () => {
          try {
            const res = await fetch('/sdn/api/patient_dashboard_data.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ action: 'update_avatar', image_data: reader.result })
            }).then(r => r.json());
            if (res.success) {
              syncPatientAvatarUI(res.profile_image || reader.result);
            } else {
              alert(res.message || 'Failed to upload profile photo.');
            }
          } catch (err) {
            alert('Network error while uploading profile photo.');
          }
        };
        reader.readAsDataURL(file);
      });

      async function removePatientAvatar() {
        try {
          const res = await fetch('/sdn/api/patient_dashboard_data.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'update_avatar', remove_image: true })
          }).then(r => r.json());
          if (res.success) {
            const inp = document.getElementById('patientSettingsAvatarInput');
            if (inp) inp.value = '';
            syncPatientAvatarUI(null);
          }
        } catch (err) {
          alert('Network error while removing profile photo.');
        }
      }

      let patientQrRenderedId = '';
      function renderPatientQrCode(pid, fullName) {
        const box = document.getElementById('patientQrBox');
        const idEl = document.getElementById('qrCardPatientId');
        const nameEl = document.getElementById('qrCardFullName');
        const cleanId = String(pid || '').trim();
        if (idEl && cleanId) idEl.textContent = cleanId;
        if (nameEl && fullName) nameEl.textContent = fullName;
        if (!box || !cleanId || cleanId === patientQrRenderedId) return;
        if (typeof QRCode !== 'function') {
          setTimeout(() => renderPatientQrCode(cleanId, fullName), 200);
          return;
        }
        box.innerHTML = '';
        new QRCode(box, {
          text: cleanId,
          width: 110,
          height: 110,
          colorDark: '#111827',
          colorLight: '#ffffff',
          correctLevel: QRCode.CorrectLevel.M
        });
        patientQrRenderedId = cleanId;
      }

      async function downloadPatientQr() {
        const box = document.getElementById('patientQrBox');
        if (!box) return;
        const qrCanvas = box.querySelector('canvas');
        const qrImg = box.querySelector('img');
        const qrSrc = qrCanvas ? qrCanvas.toDataURL('image/png') : (qrImg ? qrImg.src : '');
        if (!qrSrc) return;

        const fullName = (document.getElementById('qrCardFullName')?.textContent || <?= json_encode($patientName) ?>).trim();
        const pid = (document.getElementById('qrCardPatientId')?.textContent || patientQrRenderedId || 'patient-id').trim();
        const fallbackInitials = <?= json_encode($initials) ?>;

        const loadImage = (src) => new Promise((resolve) => {
          if (!src) return resolve(null);
          const im = new Image();
          im.crossOrigin = 'anonymous';
          im.onload = () => resolve(im);
          im.onerror = () => resolve(null);
          im.src = src;
        });

        const [qrImage, avatarImage] = await Promise.all([
          loadImage(qrSrc),
          loadImage(currentPatientAvatarUrl)
        ]);

        const W = 720, H = 400;
        const out = document.createElement('canvas');
        out.width = W;
        out.height = H;
        const ctx = out.getContext('2d');

        const drawRoundRect = (x, y, w, h, r) => {
          ctx.beginPath();
          ctx.roundRect(x, y, w, h, r);
        };

        // Green gradient card background
        const grad = ctx.createLinearGradient(0, 0, W, H);
        grad.addColorStop(0, '#166534');
        grad.addColorStop(0.58, '#15803d');
        grad.addColorStop(1, '#047857');
        drawRoundRect(0, 0, W, H, 24);
        ctx.fillStyle = grad;
        ctx.fill();

        // Header text
        ctx.fillStyle = '#bbf7d0';
        ctx.font = '700 14px system-ui, -apple-system, sans-serif';
        ctx.fillText('MARIVELES MENTAL WELLNESS AND GENERAL HOSPITAL', 36, 46);
        ctx.fillStyle = '#ffffff';
        ctx.font = '800 22px system-ui, -apple-system, sans-serif';
        ctx.fillText('LINGKOD DIGITAL PATIENT ID', 36, 76);

        // Divider line
        ctx.strokeStyle = 'rgba(255,255,255,0.22)';
        ctx.lineWidth = 1.5;
        ctx.beginPath();
        ctx.moveTo(36, 98);
        ctx.lineTo(W - 36, 98);
        ctx.stroke();

        // Circular avatar on left
        const avX = 96, avY = 210, avR = 56;
        ctx.save();
        ctx.beginPath();
        ctx.arc(avX, avY, avR, 0, Math.PI * 2);
        ctx.closePath();
        ctx.clip();
        if (avatarImage) {
          const scale = Math.max((avR * 2) / avatarImage.width, (avR * 2) / avatarImage.height);
          const dw = avatarImage.width * scale;
          const dh = avatarImage.height * scale;
          ctx.drawImage(avatarImage, avX - dw / 2, avY - dh / 2, dw, dh);
        } else {
          ctx.fillStyle = 'rgba(255,255,255,0.18)';
          ctx.fillRect(avX - avR, avY - avR, avR * 2, avR * 2);
          ctx.fillStyle = '#ffffff';
          ctx.font = '700 32px system-ui, -apple-system, sans-serif';
          ctx.textAlign = 'center';
          ctx.textBaseline = 'middle';
          ctx.fillText(fallbackInitials, avX, avY);
        }
        ctx.restore();

        // Avatar white border
        ctx.beginPath();
        ctx.arc(avX, avY, avR, 0, Math.PI * 2);
        ctx.strokeStyle = 'rgba(255,255,255,0.9)';
        ctx.lineWidth = 3;
        ctx.stroke();

        // Patient Name & ID
        ctx.textAlign = 'left';
        ctx.textBaseline = 'alphabetic';
        ctx.fillStyle = '#ffffff';
        ctx.font = '700 24px system-ui, -apple-system, sans-serif';
        ctx.fillText(fullName, 174, 198, 250);

        drawRoundRect(174, 214, 196, 34, 17);
        ctx.fillStyle = 'rgba(255,255,255,0.18)';
        ctx.fill();
        ctx.strokeStyle = 'rgba(255,255,255,0.35)';
        ctx.lineWidth = 1;
        ctx.stroke();

        ctx.fillStyle = '#ecfdf5';
        ctx.font = '600 16px system-ui, -apple-system, sans-serif';
        ctx.fillText(pid, 190, 237);

        // Footer note
        ctx.fillStyle = '#d1fae5';
        ctx.font = '500 13px system-ui, -apple-system, sans-serif';
        ctx.fillText('Present this QR code at the MMWGH clinic desk.', 36, 358);

        // White QR panel on right
        const qrPanelSize = 212;
        const qrPanelX = W - qrPanelSize - 36;
        const qrPanelY = 128;
        drawRoundRect(qrPanelX, qrPanelY, qrPanelSize, qrPanelSize, 16);
        ctx.fillStyle = '#ffffff';
        ctx.fill();
        if (qrImage) {
          ctx.drawImage(qrImage, qrPanelX + 16, qrPanelY + 16, qrPanelSize - 32, qrPanelSize - 32);
        }

        const a = document.createElement('a');
        a.href = out.toDataURL('image/png');
        a.download = `${pid}-digital-id.png`;
        a.click();
      }

      function saveSettings() {
        const btn = document.getElementById('btnSaveSettings');
        if (!btn) return;
        const t = i18nDict[currentLang] || i18nDict.en;
        btn.innerHTML = `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg> <span>${currentLang === 'tl' ? 'Nai-save na!' : 'Preferences Saved!'}</span>`;
        setTimeout(() => {
          btn.innerHTML = `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg> <span data-i18n="settings_save">${t.settings_save}</span>`;
        }, 1800);
      }

      function emptyRow(text) {
        return `<div class="item-row" style="justify-content:center; padding:24px;"><span style="color:var(--text-secondary); font-size:0.88rem; text-align:center;">${escapeHtml(text)}</span></div>`;
      }

      function findAppointment(id) {
        return [...(portalData.upcoming || []), ...(portalData.past || []), ...(portalData.cancelled || [])]
          .find(item => Number(item.id) === Number(id));
      }

      function openRescheduleModal(id) {
        const item = findAppointment(id);
        if (!item || !item.can_reschedule) return;
        rescheduleTarget = item;
        document.getElementById('rescheduleSummary').textContent = `${item.title} (${item.patient_code}). Choose your new preferred date and session below.`;
        const dateInput = document.getElementById('rescheduleDate');
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        const iso = tomorrow.toISOString().slice(0, 10);
        dateInput.min = iso;
        dateInput.value = item.date_iso && item.date_iso >= iso ? item.date_iso : iso;
        document.getElementById('rescheduleTime').value = item.time_iso || '08:00:00';
        const err = document.getElementById('rescheduleError');
        err.style.display = 'none';
        err.textContent = '';
        document.getElementById('rescheduleModal').classList.add('active');
      }

      function closeRescheduleModal() {
        document.getElementById('rescheduleModal').classList.remove('active');
        rescheduleTarget = null;
      }

      async function submitReschedule() {
        if (!rescheduleTarget) return;
        const err = document.getElementById('rescheduleError');
        const btn = document.getElementById('rescheduleSaveBtn');
        err.style.display = 'none';
        btn.disabled = true;
        try {
          const res = await fetch('/sdn/api/patient_reschedule_appointment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              id: rescheduleTarget.id,
              appointment_date: document.getElementById('rescheduleDate').value,
              scheduled_time: document.getElementById('rescheduleTime').value
            })
          });
          const data = await res.json();
          if (!data.success) {
            err.textContent = data.message || 'Could not reschedule this visit.';
            err.style.display = 'block';
            return;
          }
          closeRescheduleModal();
          await initPortal();
        } catch (e) {
          err.textContent = 'Could not reach the server.';
          err.style.display = 'block';
        } finally {
          btn.disabled = false;
        }
      }

      function openPasswordModal() {
        document.getElementById('pwCurrent').value = '';
        document.getElementById('pwNew').value = '';
        document.getElementById('pwConfirm').value = '';
        const msg = document.getElementById('pwMsg');
        msg.style.display = 'none';
        document.getElementById('passwordModal').classList.add('active');
      }

      function closePasswordModal() {
        document.getElementById('passwordModal').classList.remove('active');
      }

      function togglePwField(id) {
        const el = document.getElementById(id);
        if (el) el.type = el.type === 'password' ? 'text' : 'password';
      }

      async function submitPasswordChange() {
        const current_password = document.getElementById('pwCurrent').value;
        const new_password = document.getElementById('pwNew').value;
        const confirm_password = document.getElementById('pwConfirm').value;
        const msg = document.getElementById('pwMsg');
        const btn = document.getElementById('btnSubmitPw');

        msg.style.display = 'none';
        const isStrongPw = new_password.length >= 8 && /[A-Z]/.test(new_password) && /[a-z]/.test(new_password) && /[0-9]/.test(new_password) && /[^A-Za-z0-9]/.test(new_password);
        if (!isStrongPw) {
          msg.style.display = 'block';
          msg.style.color = '#b91c1c';
          msg.textContent = 'Password must be at least 8 characters and include uppercase, lowercase, number, and special character.';
          return;
        }
        btn.disabled = true;
        try {
          const res = await fetch('/sdn/api/patient_dashboard_data.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              action: 'change_password',
              current_password,
              new_password,
              confirm_password
            })
          }).then(r => r.json());

          msg.style.display = 'block';
          msg.style.color = res.success ? '#15803d' : '#b91c1c';
          msg.textContent = res.message || (res.success ? 'Password updated.' : 'Error updating password.');
          if (res.success) {
            setTimeout(() => closePasswordModal(), 1200);
          }
        } catch (e) {
          msg.style.display = 'block';
          msg.style.color = '#b91c1c';
          msg.textContent = 'Network error while updating password.';
        } finally {
          btn.disabled = false;
        }
      }

      function filterApptTab(tab, btn) {
        currentApptTab = tab;
        document.querySelectorAll('#view-appointments .filter-pill').forEach(p => p.classList.remove('active'));
        if (btn) btn.classList.add('active');
        paintAppointmentList();
      }

      function closeModal() {
        document.getElementById('detailsModal')?.classList.remove('active');
      }

      document.addEventListener('DOMContentLoaded', () => {
        initNotificationServiceWorker();
        initPortal();
      });

      document.addEventListener('click', (e) => {
        const notifCont = document.getElementById('notifContainer');
        const notifDd = document.getElementById('notifDropdown');
        if (notifDd && notifDd.classList.contains('show') && notifCont && !notifCont.contains(e.target)) {
          notifDd.classList.remove('show');
        }
        const profCont = document.getElementById('profileContainer');
        const profDd = document.getElementById('profileDropdown');
        if (profDd && profDd.classList.contains('show') && profCont && !profCont.contains(e.target)) {
          profDd.classList.remove('show');
        }
      });

      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
          closeModal();
          closeRescheduleModal();
          closePasswordModal();
          closeProfileDropdown();
          document.getElementById('notifDropdown')?.classList.remove('show');
        }
      });
    </script>
      <script src="/sdn/assets/js/dashboard-mobile.js?v=<?= time() ?>"></script>
  </body>
</html>

