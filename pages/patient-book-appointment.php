<?php
/**
 * pages/patient-book-appointment.php
 * One-Page Multi-Step Patient Appointment Booking:
 * - Directly accessible from Patient Dashboard
 * - Eliminates the redundant department-select intermediate page
 * - Auto-prepopulates authenticated patient's profile details
 * - Strict non-scrollable (100vh) hospital-grade layout
 * - Step 1: Personal Information
 * - Step 2: Medical Information
 * - Step 3: Appointment Details
 * - Step 4: Confirmation & Instant Queueing
 */

session_start();
require_once __DIR__ . '/../api/config/db.php';

// Auth guard - Must be signed into patient portal
if (empty($_SESSION['patient_logged_in']) || (empty($_SESSION['patient_id']) && empty($_SESSION['patient_email']))) {
    header('Location: /sdn/pages/patient-login.php?redirect=' . urlencode('/sdn/pages/patient-book-appointment.php'));
    exit;
}

$sessionPatientId = $_SESSION['patient_id'] ?? '';
$sessionEmail     = $_SESSION['patient_email'] ?? '';

// Pre-fetch logged-in patient's latest record to auto-fill personal information
$stmt = $pdo->prepare("
    SELECT id, patient_id, full_name, first_name, last_name, middle_initial,
           gender, birthdate, age, contact_number, email, address, with_companion, hrn
    FROM patients
    WHERE patient_id = :pid OR email = :pemail
    ORDER BY id DESC
    LIMIT 1
");
$stmt->execute([
    ':pid'    => $sessionPatientId,
    ':pemail' => $sessionEmail
]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

// Extract pre-fill variables
$firstName     = $patient['first_name'] ?? '';
$lastName      = $patient['last_name'] ?? '';
$middleInitial = $patient['middle_initial'] ?? '';
if ($firstName === '' && $lastName === '' && !empty($patient['full_name'])) {
    $parts = explode(' ', trim($patient['full_name']));
    if (count($parts) === 1) {
        $firstName = $parts[0];
    } elseif (count($parts) === 2) {
        $firstName = $parts[0];
        $lastName  = $parts[1];
    } elseif (count($parts) >= 3) {
        $firstName = $parts[0];
        $lastName  = end($parts);
        $middleInitial = $parts[1];
    }
}
$gender        = $patient['gender'] ?? '';
$birthdate     = $patient['birthdate'] ?? '';
$age           = $patient['age'] ?? '';
$rawContact    = $patient['contact_number'] ?? '';
$contactNumber = preg_replace('/^(\+63|63|0)/', '', $rawContact);
$email         = $patient['email'] ?? $sessionEmail;
$address       = $patient['address'] ?? '';
$withCompanion = isset($patient['with_companion']) ? (int)$patient['with_companion'] : 0;

$selectedDept = $_GET['dept'] ?? '';
if ($selectedDept === 'General Management' || strtolower($selectedDept) === 'general_medicine') {
    $selectedDept = 'General Medical';
} elseif (strtolower($selectedDept) === 'neurology') {
    $selectedDept = 'Neurology';
} elseif (strtolower($selectedDept) === 'psychiatry' || $selectedDept === 'OPU Psychiatry') {
    $selectedDept = 'OPU Psychiatry';
}

// Earliest bookable date (advance booking rule: at least 1 day in advance)
$tomorrow = date('Y-m-d', strtotime('+1 day'));
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Book an Appointment | MMWGH LINGKOD</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/sdn/public/css/global.css?v=<?= time() ?>" />

    <style>
      *, *::before, *::after {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
      }

      html, body {
        height: 100vh;
        overflow: hidden;
      }

      body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        background: #f1f6f2;
        color: #0f172a;
        display: flex;
        flex-direction: column;
        height: 100vh;
      }

      /* ── Top Navigation Bar ── */
      .book-navbar {
        height: 72px;
        background: linear-gradient(90deg, #25ba50 0%, #7ed957 50%, #047023 100%);
        box-shadow: 0 4px 14px rgba(4, 112, 35, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        position: relative;
        z-index: 20;
      }

      .nav-center-brand {
        display: flex;
        align-items: center;
        gap: 12px;
        color: #ffffff;
        text-decoration: none;
      }

      .nav-logo-img {
        height: 48px;
        width: auto;
        display: block;
        filter: drop-shadow(0 2px 4px rgba(0,0,0,0.15));
      }

      .nav-brand-text {
        display: flex;
        flex-direction: column;
        line-height: 1.15;
      }

      .brand-title-top {
        font-size: 0.78rem;
        font-weight: 600;
        letter-spacing: -0.1px;
        color: rgba(255, 255, 255, 0.95);
      }

      .brand-title-bottom {
        font-family: 'Outfit', sans-serif;
        font-size: 1.15rem;
        font-weight: 900;
        letter-spacing: 0.04em;
        color: #ffffff;
      }

      /* ── Main Booking Layout (Locked to Screen Height) ── */
      .booking-viewport {
        flex: 1;
        min-height: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0.75rem 2rem;
        overflow: hidden;
      }

      .booking-container {
        width: 100%;
        max-width: 1100px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 1.5rem;
        height: 100%;
        max-height: 560px;
      }

      /* ── Left Side: Stepper Indicator (Exact Match to Image) ── */
      .booking-stepper-panel {
        width: 250px;
        flex-shrink: 0;
      }

      .stepper-heading {
        font-family: 'Outfit', 'Inter', sans-serif;
        font-size: 1.45rem;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 2rem;
        letter-spacing: -0.02em;
      }

      .stepper-list {
        display: flex;
        flex-direction: column;
      }

      .step-row {
        display: flex;
        align-items: center;
        gap: 14px;
        position: relative;
      }

      .step-circle {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: #94a3b8;
        color: #ffffff;
        font-family: 'Outfit', 'Inter', sans-serif;
        font-weight: 800;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        transition: all 0.25s ease;
      }

      .step-row.active .step-circle {
        background: #14532d;
        color: #ffffff;
      }

      .step-row.completed .step-circle {
        background: #16a34a;
        color: #ffffff;
      }

      .step-name {
        font-size: 0.92rem;
        font-weight: 700;
        color: #0f172a;
        transition: all 0.2s ease;
      }

      .step-line {
        width: 2px;
        height: 42px;
        background: #94a3b8;
        margin-left: 17px;
        margin-top: 3px;
        margin-bottom: 3px;
        transition: background 0.25s ease;
      }

      .step-line.completed {
        background: #16a34a;
      }

      /* Vertical Divider Line */
      .stepper-vertical-divider {
        width: 1px;
        height: 75%;
        background: #e2e8f0;
        flex-shrink: 0;
        margin: 0 1rem;
      }

      /* ── Right Side: Booking Card & Action ── */
      .booking-card-panel {
        flex: 1;
        max-width: 680px;
        display: flex;
        flex-direction: column;
        align-items: center;
      }

      .booking-main-card {
        background: #ffffff;
        border: 1.5px solid #d1d5db;
        border-radius: 18px;
        padding: 1.35rem 1.65rem;
        width: 100%;
        box-shadow: none;
        position: relative;
      }

      /* Top Row: Department Dropdown + Inquiry Only Toggle */
      .card-top-row {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 0.95rem;
      }

      .dept-input-group {
        flex: 1;
        max-width: 440px;
      }

      .field-label {
        display: block;
        font-size: 0.85rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 0.35rem;
      }

      .req-mark {
        color: #ef4444;
      }

      .select-container {
        position: relative;
        width: 100%;
      }

      .select-container select {
        width: 100%;
        height: 40px;
        padding: 0.45rem 2.2rem 0.45rem 0.9rem;
        border: 1.5px solid #d1d5db;
        border-radius: 8px;
        font-family: 'Inter', sans-serif;
        font-size: 0.86rem;
        color: #334155;
        background: #ffffff;
        outline: none;
        appearance: none;
        -webkit-appearance: none;
        cursor: pointer;
        transition: border-color 0.18s;
      }

      .select-container select:focus {
        border-color: #166534;
      }

      .select-container .chevron-arrow {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        pointer-events: none;
        color: #64748b;
      }

      /* Static Department Box */
      .static-dept-box {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        height: 40px;
        padding: 0.45rem 0.95rem;
        background: #f8fafc;
        border: 1.5px solid #cbd5e1;
        border-radius: 8px;
        font-family: 'Inter', sans-serif;
        font-size: 0.88rem;
        font-weight: 700;
        color: #1e293b;
      }

      .locked-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 0.72rem;
        font-weight: 700;
        color: #475569;
        background: #e2e8f0;
        padding: 2px 8px;
        border-radius: 999px;
      }

      /* Inquiry Only Badge */
      .inquiry-badge-btn {
        background: #16a34a;
        color: #ffffff;
        border-radius: 6px;
        padding: 0.45rem 0.85rem;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 0.8rem;
        font-weight: 700;
        cursor: pointer;
        user-select: none;
        transition: background 0.18s ease;
        border: none;
      }

      .inquiry-badge-btn input[type="checkbox"] {
        accent-color: #15803d;
        width: 15px;
        height: 15px;
        cursor: pointer;
      }

      /* Inner Bordered Section Container */
      .inner-form-box {
        border: 1.5px solid #d1d5db;
        border-radius: 12px;
        padding: 1.15rem 1.45rem;
        background: #ffffff;
      }

      .inner-box-title {
        text-align: center;
        font-size: 1.1rem;
        font-weight: 800;
        color: #1e293b;
        margin-bottom: 0.95rem;
        font-family: 'Outfit', 'Inter', sans-serif;
      }

      /* Form Grids */
      .form-grid-row {
        display: grid;
        gap: 0.85rem;
        margin-bottom: 0.75rem;
      }

      .row-3-cols {
        grid-template-columns: 1fr 1fr 90px;
      }

      .row-2-cols {
        grid-template-columns: 1fr 1fr;
      }

      .row-1-col {
        grid-template-columns: 1fr;
      }

      .text-field-box {
        display: flex;
        flex-direction: column;
      }

      .text-field-box label {
        font-size: 0.8rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 0.3rem;
      }

      .text-field-box input[type="text"],
      .text-field-box input[type="email"],
      .text-field-box input[type="date"],
      .text-field-box input[type="number"],
      .text-field-box select,
      .text-field-box textarea {
        width: 100%;
        height: 38px;
        border: 1.5px solid #d1d5db;
        border-radius: 8px;
        padding: 0.45rem 0.75rem;
        font-family: 'Inter', sans-serif;
        font-size: 0.84rem;
        color: #0f172a;
        outline: none;
        background: #ffffff;
        transition: border-color 0.18s;
      }

      .text-field-box textarea {
        height: 58px;
        padding: 0.5rem 0.75rem;
        resize: none;
      }

      .text-field-box input:focus,
      .text-field-box select:focus,
      .text-field-box textarea:focus {
        border-color: #166534;
      }

      .text-field-box input[readonly] {
        background: #f8fafc;
        color: #64748b;
        cursor: not-allowed;
      }

      /* Contact Number Group (+63) */
      .contact-split-group {
        display: flex;
        align-items: center;
        border: 1.5px solid #d1d5db;
        border-radius: 8px;
        overflow: hidden;
        height: 38px;
        background: #ffffff;
      }

      .contact-split-group:focus-within {
        border-color: #166534;
      }

      .contact-prefix-tag {
        padding: 0 0.65rem;
        background: #f8fafc;
        font-size: 0.82rem;
        font-weight: 700;
        color: #64748b;
        border-right: 1.5px solid #d1d5db;
        height: 100%;
        display: flex;
        align-items: center;
        user-select: none;
      }

      .contact-split-group input {
        border: none !important;
        border-radius: 0 !important;
        height: 100% !important;
        padding-left: 0.65rem !important;
      }

      /* Companion Radio Group */
      .companion-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-top: 0.15rem;
      }

      .companion-label {
        font-size: 0.8rem;
        font-weight: 700;
        color: #0f172a;
      }

      .radio-options-wrap {
        display: flex;
        align-items: center;
        gap: 1.25rem;
      }

      .radio-pill-item {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        font-size: 0.82rem;
        font-weight: 600;
        color: #1e293b;
        cursor: pointer;
      }

      .radio-pill-item input[type="radio"] {
        accent-color: #166534;
        width: 16px;
        height: 16px;
        cursor: pointer;
      }

      /* Confirmation Review Box */
      .review-summary-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 0.85rem 1rem;
        margin-bottom: 0.85rem;
      }

      .review-item {
        display: flex;
        flex-direction: column;
        gap: 2px;
      }

      .review-lbl {
        font-size: 0.72rem;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.04em;
      }

      .review-val {
        font-size: 0.84rem;
        font-weight: 700;
        color: #0f172a;
      }

      .consent-checkbox-wrap {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        font-size: 0.76rem;
        color: #475569;
        line-height: 1.4;
      }

      .consent-checkbox-wrap input[type="checkbox"] {
        accent-color: #166534;
        width: 15px;
        height: 15px;
        margin-top: 2px;
      }

      /* ── Bottom Action Buttons ── */
      .bottom-action-bar {
        margin-top: 1.15rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.85rem;
        width: 100%;
      }

      .btn-pill-back {
        background: #ffffff;
        color: #475569;
        border: 1.5px solid #cbd5e1;
        font-family: 'Inter', sans-serif;
        font-size: 0.9rem;
        font-weight: 700;
        padding: 0.6rem 2.2rem;
        border-radius: 999px;
        cursor: pointer;
        transition: all 0.18s ease;
      }

      .btn-pill-back:hover {
        background: #f8fafc;
        border-color: #94a3b8;
        color: #0f172a;
      }

      .btn-pill-next {
        background: #14532d;
        color: #ffffff;
        border: none;
        font-family: 'Inter', sans-serif;
        font-size: 0.92rem;
        font-weight: 700;
        padding: 0.62rem 3.8rem;
        border-radius: 999px;
        cursor: pointer;
        transition: all 0.2s ease;
      }

      .btn-pill-next:hover {
        background: #0d381e;
        transform: translateY(-1px);
      }

      .btn-pill-next:disabled {
        opacity: 0.65;
        cursor: not-allowed;
        transform: none;
      }

      /* Alert message box */
      .form-alert-msg {
        display: none;
        padding: 0.65rem 1rem;
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 700;
        margin-bottom: 0.85rem;
      }

      .form-alert-msg.error {
        display: block;
        background: #fef2f2;
        color: #b91c1c;
        border: 1px solid #fee2e2;
      }

      .form-alert-msg.success {
        display: block;
        background: #ecfdf5;
        color: #047857;
        border: 1px solid #a7f3d0;
      }

      /* Success Screen Overlay inside Card */
      .booking-success-box {
        display: none;
        text-align: center;
        padding: 1.5rem 1rem;
      }

      .success-icon-ring {
        width: 58px;
        height: 58px;
        border-radius: 50%;
        background: #dcfce7;
        color: #15803d;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 0.85rem;
      }

      .success-title {
        font-family: 'Outfit', sans-serif;
        font-size: 1.35rem;
        font-weight: 800;
        color: #082f04;
        margin-bottom: 0.35rem;
      }

      .success-ref-code {
        display: inline-block;
        background: #f1f5f9;
        border: 1.5px solid #cbd5e1;
        border-radius: 8px;
        padding: 0.35rem 0.85rem;
        font-family: 'Outfit', monospace;
        font-size: 1.1rem;
        font-weight: 800;
        color: #14532d;
        margin: 0.5rem 0 0.85rem;
        letter-spacing: 0.05em;
      }

      .success-desc {
        font-size: 0.84rem;
        color: #475569;
        max-width: 440px;
        margin: 0 auto 1.25rem;
        line-height: 1.45;
      }

      .btn-success-dashboard {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #14532d;
        color: #ffffff;
        text-decoration: none;
        font-weight: 700;
        font-size: 0.9rem;
        padding: 0.65rem 2.5rem;
        border-radius: 999px;
        transition: all 0.2s ease;
      }

      .btn-success-dashboard:hover {
        background: #0d381e;
        transform: translateY(-1px);
      }

      /* Step visibility */
      .phase-step {
        display: none;
      }

      .phase-step.active {
        display: block;
      }

      /* ── Glassmorphism Data Privacy Modal Overlay ── */
      .privacy-modal-overlay {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.25rem;
        background: rgba(15, 23, 42, 0.45);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        transition: opacity 0.25s ease, visibility 0.25s ease;
      }

      .privacy-modal-overlay.hidden-overlay {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
      }

      /* When privacy overlay is active, blur background viewport */
      body.privacy-locked .booking-viewport,
      body.privacy-locked .book-navbar {
        filter: blur(2.5px);
        pointer-events: none;
        user-select: none;
      }

      .privacy-modal-card {
        background: #ffffff;
        border-radius: 18px;
        box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.35);
        border: 1px solid rgba(226, 232, 240, 0.9);
        width: 100%;
        max-width: 580px;
        padding: 1.5rem 1.85rem;
        display: flex;
        flex-direction: column;
        animation: modalScaleIn 0.22s cubic-bezier(0.16, 1, 0.3, 1) forwards;
      }

      @keyframes modalScaleIn {
        from {
          opacity: 0;
          transform: scale(0.96) translateY(8px);
        }
        to {
          opacity: 1;
          transform: scale(1) translateY(0);
        }
      }

      .privacy-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-bottom: 0.9rem;
        border-bottom: 1px solid #f1f5f9;
        margin-bottom: 1rem;
      }

      .privacy-header-left {
        display: flex;
        align-items: center;
        gap: 0.75rem;
      }

      .privacy-icon-badge {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: #ecfdf5;
        color: #059669;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        border: 1px solid #a7f3d0;
      }

      .privacy-title {
        font-size: 1.05rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.25;
      }

      .privacy-subtitle {
        font-size: 0.72rem;
        color: #64748b;
        font-weight: 600;
        margin-top: 1px;
      }

      .privacy-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 0.7rem;
        font-weight: 700;
        padding: 3px 10px;
        border-radius: 999px;
      }

      .privacy-status-pill.pending {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fde68a;
      }

      .privacy-status-pill.ready {
        background: #dcfce7;
        color: #15803d;
        border: 1px solid #bbf7d0;
      }

      .privacy-scroll-content {
        height: 185px;
        max-height: 185px;
        overflow-y: auto;
        background: #f8fafc;
        border: 1.4px solid #e2e8f0;
        border-radius: 10px;
        padding: 0.95rem 1.15rem;
        font-size: 0.78rem;
        color: #334155;
        line-height: 1.55;
      }

      .privacy-section-lead {
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 0.5rem;
      }

      .privacy-section-heading {
        font-weight: 700;
        color: #0f172a;
        margin-top: 0.65rem;
        margin-bottom: 0.2rem;
      }

      .privacy-consent-row {
        margin-top: 1rem;
        padding-top: 0.85rem;
        border-top: 1px solid #f1f5f9;
      }

      .privacy-checkbox-label {
        display: flex;
        align-items: flex-start;
        gap: 0.65rem;
        font-size: 0.78rem;
        color: #1e293b;
        line-height: 1.4;
        cursor: not-allowed;
        opacity: 0.42;
        transition: opacity 0.18s ease, cursor 0.18s ease;
        user-select: none;
      }

      .privacy-checkbox-label.unlocked {
        cursor: pointer;
        opacity: 1;
      }

      .privacy-checkbox-label input[type="checkbox"] {
        accent-color: #15803d;
        width: 17px;
        height: 17px;
        margin-top: 1px;
        cursor: inherit;
      }

      .privacy-hint-text {
        font-size: 0.72rem;
        color: #b45309;
        margin-top: 0.45rem;
        margin-left: 1.65rem;
        display: flex;
        align-items: center;
        gap: 5px;
      }

      .privacy-hint-text.hidden {
        display: none;
      }

      .privacy-modal-actions {
        margin-top: 1.25rem;
        display: flex;
        justify-content: flex-end;
      }

      .btn-proceed-privacy {
        background: #15803d;
        color: #ffffff;
        border: none;
        padding: 0.65rem 1.75rem;
        border-radius: 999px;
        font-size: 0.84rem;
        font-weight: 700;
        cursor: pointer;
        box-shadow: 0 4px 14px rgba(21, 128, 61, 0.25);
        transition: all 0.16s ease;
      }

      .btn-proceed-privacy:hover:not(:disabled) {
        background: #166534;
        transform: translateY(-1px);
        box-shadow: 0 6px 18px rgba(21, 128, 61, 0.35);
      }

      .btn-proceed-privacy:disabled {
        background: #cbd5e1;
        color: #94a3b8;
        cursor: not-allowed;
        box-shadow: none;
        transform: none;
      }

      /* Custom scrollbars */
      .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
      }
      .custom-scrollbar::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 999px;
      }
      .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 999px;
      }
      .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
      }

      /* Responsive adjustments if viewport shrinks */
      @media (max-width: 900px) {
        html, body {
          overflow-y: auto;
          height: auto;
        }
        .booking-viewport {
          height: auto;
          padding: 1.5rem 1rem;
        }
        .booking-container {
          flex-direction: column;
          height: auto;
          max-height: none;
        }
        .stepper-vertical-divider {
          display: none;
        }
        .booking-stepper-panel {
          width: 100%;
        }
        .stepper-list {
          flex-direction: row;
          justify-content: space-between;
        }
        .step-line {
          display: none;
        }
      }
    </style>
  </head>
  <body class="privacy-locked">
    <!-- ── Data Privacy Act (R.A. 10173) Modal Overlay with Glassmorphism ── -->
    <div id="privacyOverlay" class="privacy-modal-overlay">
      <div class="privacy-modal-card">
        <!-- Header -->
        <div class="privacy-modal-header">
          <div class="privacy-header-left">
            <div class="privacy-icon-badge">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
              </svg>
            </div>
            <div>
              <h2 class="privacy-title">Data Privacy Consent Notice</h2>
              <p class="privacy-subtitle">Republic Act No. 10173 (Data Privacy Act of 2012)</p>
            </div>
          </div>
          <span id="privacyScrollPill" class="privacy-status-pill pending">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
            Scroll Down
          </span>
        </div>

        <!-- Scrollable Text Box (Fixed Height, Custom Scrollbars) -->
        <div id="modalPrivacyScrollBox" class="privacy-scroll-content custom-scrollbar">
          <p class="privacy-section-lead">
            Mariveles Mental Wellness and General Hospital (MMWGH) — LINGKOD Patient Appointment &amp; Triage Service
          </p>
          <p>
            In strict compliance with Republic Act No. 10173, otherwise known as the <em>Data Privacy Act of 2012</em> of the Republic of the Philippines, and its Implementing Rules and Regulations, MMWGH is committed to upholding your privacy rights, clinical confidentiality, and the security of your personal health data.
          </p>
          <p class="privacy-section-heading">1. Collection and Scope of Personal Information</p>
          <p>
            By proceeding to fill out this consultation booking form, you acknowledge and authorize MMWGH to process personal details (such as your full name, contact number, birthdate, age, home address, and guardian information if applicable) and sensitive clinical data (such as your chief complaints and clinical triage routing).
          </p>
          <p class="privacy-section-heading">2. Medical Confidentiality &amp; Purpose of Processing</p>
          <p>
            Your information is utilized solely for clinical schedule confirmation, physician triage assignment, and logistical SMS/Email notification reminders. Specific medical diagnoses and complaints will never be published in unencrypted communications or shared with unauthorized entities.
          </p>
          <p class="privacy-section-heading">3. Minor Consultation Guidelines</p>
          <p>
            For patients under eighteen (18) years of age, a designated parent or legal guardian must be verified and must accompany the minor patient to the hospital Outpatient Triage desk during the clinic schedule.
          </p>
          <p class="privacy-section-heading">4. Data Subject Rights</p>
          <p>
            Under R.A. 10173, you retain the right to be informed, review your clinical records, dispute inaccuracies, and request correction in accordance with policies enforced by the MMWGH Data Protection Officer.
          </p>
        </div>

        <!-- Checkbox Row (Disabled until scrolled to absolute bottom) -->
        <div class="privacy-consent-row">
          <label class="privacy-checkbox-label" id="modalPrivacyLabel">
            <input type="checkbox" id="modalPrivacyConsentCheck" disabled />
            <span>I have read and agree to the <strong>Data Privacy Policy</strong> and consent to the processing of my information.</span>
          </label>
          <p id="privacyHintText" class="privacy-hint-text">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <span>Please scroll to the bottom of the statement to unlock this checkbox.</span>
          </p>
        </div>

        <!-- Action Button: Proceed (Disabled until checkbox is checked) -->
        <div class="privacy-modal-actions">
          <button type="button" id="btnProceedBooking" class="btn-proceed-privacy" disabled>
            Proceed
          </button>
        </div>
      </div>
    </div>

    <!-- Top Navigation Bar (Centered Logo Only, No Dashboard Button) -->
    <header class="book-navbar">
      <div class="nav-center-brand">
        <img src="/sdn/public/assets/v3_lingkod_logo.png" alt="Lingkod Logo" class="nav-logo-img" />
        <div class="nav-brand-text">
          <span class="brand-title-top">Mariveles Mental Wellness and General Hospital</span>
          <span class="brand-title-bottom">LINGKOD</span>
        </div>
      </div>
    </header>

    <!-- Main Viewport (Fixed Height, No Page Scroll) -->
    <main class="booking-viewport">
      <div class="booking-container">
        
        <!-- Left Stepper Column -->
        <aside class="booking-stepper-panel" aria-label="Booking Phases">
          <h1 class="stepper-heading">Booking Phase</h1>
          <div class="stepper-list">
            
            <!-- Step 1 Indicator -->
            <div class="step-row active" id="stepIndicator1">
              <div class="step-circle">1</div>
              <span class="step-name">Personal Information</span>
            </div>
            <div class="step-line" id="stepLine1"></div>

            <!-- Step 2 Indicator -->
            <div class="step-row" id="stepIndicator2">
              <div class="step-circle">2</div>
              <span class="step-name">Medical Information</span>
            </div>
            <div class="step-line" id="stepLine2"></div>

            <!-- Step 3 Indicator -->
            <div class="step-row" id="stepIndicator3">
              <div class="step-circle">3</div>
              <span class="step-name">Appointment Details</span>
            </div>
            <div class="step-line" id="stepLine3"></div>

            <!-- Step 4 Indicator -->
            <div class="step-row" id="stepIndicator4">
              <div class="step-circle">4</div>
              <span class="step-name">Confirmation</span>
            </div>

          </div>
        </aside>

        <!-- Vertical Divider -->
        <div class="stepper-vertical-divider"></div>

        <!-- Right Side: Booking Form Card -->
        <section class="booking-card-panel">
          <div class="booking-main-card">
            
            <!-- Form Alert Box -->
            <div id="bookingAlert" class="form-alert-msg" role="alert"></div>

            <!-- Normal Multi-Step Form Container -->
            <div id="bookingFormWrapper">
              
              <!-- Top Row: Department Selection + Inquiry Only Toggle -->
              <div class="card-top-row" id="cardTopRow">
                <div class="dept-input-group" id="deptInputGroup">
                  <label for="departmentSelect" class="field-label">Preferred Department (Optional)</label>
                  <div class="select-wrap select-container" id="deptSelectWrap">
                    <select id="departmentSelect" name="department">
                      <option value="" <?= empty($selectedDept) ? 'selected' : '' ?>>No preference (Staff will assign based on complaint)</option>
                      <option value="General Medical" <?= $selectedDept === 'General Medical' ? 'selected' : '' ?>>General Medical</option>
                      <option value="Neurology" <?= $selectedDept === 'Neurology' ? 'selected' : '' ?>>Neurology</option>
                      <option value="OPU Psychiatry" <?= $selectedDept === 'OPU Psychiatry' ? 'selected' : '' ?>>OPU Psychiatry</option>
                    </select>
                    <svg class="chevron-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                      <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                  </div>
                  <!-- Static Department Box for Locked / Confirmation States -->
                  <div id="staticDeptBox" class="static-dept-box" style="display: none;">
                    <span id="staticDeptText">General Medical</span>
                    <span class="locked-badge" title="Department cannot be changed once chosen">
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                      Locked
                    </span>
                  </div>
                </div>

                <label class="inquiry-badge-btn" id="inquiryBadgeBtn" for="inquiryOnlyCheckbox" title="Toggle general inquiry mode without specific consultation slot">
                  <input type="checkbox" id="inquiryOnlyCheckbox" name="inquiry_only" />
                  <span>Inquiry Only</span>
                </label>
              </div>

              <!-- ── PHASE 1: Personal Information ── -->
              <div class="phase-step active" id="phaseStep1">
                <div class="inner-form-box">
                  <h2 class="inner-box-title">Personal Information</h2>

                  <!-- Row 1: First Name, Last Name, M.I. -->
                  <div class="form-grid-row row-3-cols">
                    <div class="text-field-box">
                      <label>First Name <span class="req-mark">*</span></label>
                      <input type="text" id="inputFirstName" placeholder="Enter First Name" value="<?= htmlspecialchars($firstName) ?>" required />
                    </div>
                    <div class="text-field-box">
                      <label>Last Name <span class="req-mark">*</span></label>
                      <input type="text" id="inputLastName" placeholder="Enter Last Name" value="<?= htmlspecialchars($lastName) ?>" required />
                    </div>
                    <div class="text-field-box">
                      <label>M.I. (Optional)</label>
                      <input type="text" id="inputMiddleInitial" placeholder="e.g., B." maxlength="5" value="<?= htmlspecialchars($middleInitial) ?>" />
                    </div>
                  </div>

                  <!-- Row 2: Gender, Birthdate, Age -->
                  <div class="form-grid-row row-3-cols">
                    <div class="text-field-box">
                      <label>Gender <span class="req-mark">*</span></label>
                      <div class="select-container">
                        <select id="selectGender" required>
                          <option value="" disabled <?= empty($gender) ? 'selected' : '' ?>>Select Gender</option>
                          <option value="Male" <?= $gender === 'Male' ? 'selected' : '' ?>>Male</option>
                          <option value="Female" <?= $gender === 'Female' ? 'selected' : '' ?>>Female</option>
                          <option value="Other" <?= $gender === 'Other' ? 'selected' : '' ?>>Other</option>
                        </select>
                        <svg class="chevron-arrow" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                          <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                      </div>
                    </div>
                    <div class="text-field-box">
                      <label>Birthdate <span class="req-mark">*</span></label>
                      <input type="date" id="inputBirthdate" value="<?= htmlspecialchars($birthdate) ?>" max="<?= date('Y-m-d') ?>" required />
                    </div>
                    <div class="text-field-box">
                      <label>Age</label>
                      <input type="number" id="inputAge" placeholder="Age" value="<?= htmlspecialchars((string)$age) ?>" readonly />
                    </div>
                  </div>

                  <!-- Row 3: Contact Number (+63), Email Address -->
                  <div class="form-grid-row row-2-cols">
                    <div class="text-field-box">
                      <label>Contact Number <span class="req-mark">*</span></label>
                      <div class="contact-split-group">
                        <span class="contact-prefix-tag">+63</span>
                        <input type="text" id="inputContactNumber" placeholder="9XXXXXXXXX" maxlength="12" value="<?= htmlspecialchars($contactNumber) ?>" required />
                      </div>
                    </div>
                    <div class="text-field-box">
                      <label>Email Address <span class="req-mark">*</span></label>
                      <input type="email" id="inputEmail" placeholder="Enter Email Address" value="<?= htmlspecialchars($email) ?>" required />
                    </div>
                  </div>

                  <!-- Row 4: Home Address -->
                  <div class="form-grid-row row-1-col">
                    <div class="text-field-box">
                      <label>Home Address <span class="req-mark">*</span></label>
                      <input type="text" id="inputAddress" placeholder="Enter Home Address" value="<?= htmlspecialchars($address) ?>" required />
                    </div>
                  </div>

                  <!-- Row 5: Accompanying Companion -->
                  <div class="companion-row">
                    <span class="companion-label">Accompanying Companion? <span class="req-mark">*</span></span>
                    <div class="radio-options-wrap">
                      <label class="radio-pill-item">
                        <input type="radio" name="with_companion" value="yes" <?= $withCompanion === 1 ? 'checked' : 'checked' ?> />
                        <span>Yes</span>
                      </label>
                      <label class="radio-pill-item">
                        <input type="radio" name="with_companion" value="no" <?= $withCompanion === 0 && !empty($patient) ? 'checked' : '' ?> />
                        <span>No</span>
                      </label>
                    </div>
                  </div>

                </div>
              </div>

              <!-- ── PHASE 2: Medical Information ── -->
              <div class="phase-step" id="phaseStep2">
                <div class="inner-form-box">
                  <h2 class="inner-box-title">Medical Information</h2>

                  <div class="form-grid-row row-1-col">
                    <div class="text-field-box">
                      <label>Chief Complaint <span class="req-mark">*</span></label>
                      <textarea id="inputChiefComplaints" placeholder="Describe primary chief complaint or reason for hospital consultation..." required></textarea>
                    </div>
                  </div>

                  <div class="form-grid-row row-2-cols">
                    <div class="text-field-box">
                      <label>Consultation Nature <span class="req-mark">*</span></label>
                      <div class="select-container">
                        <select id="selectConsultationNature">
                          <option value="Routine Consultation" selected>Routine Consultation</option>
                          <option value="Follow-up Checkup">Follow-up Checkup</option>
                          <option value="Diagnostic Assessment">Diagnostic Assessment</option>
                          <option value="Specialist Referral">Specialist Referral</option>
                          <option value="Prescription Refill">Prescription Refill</option>
                        </select>
                        <svg class="chevron-arrow" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                          <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                      </div>
                    </div>
                    <div class="text-field-box">
                      <label>Referral Source</label>
                      <div class="select-container">
                        <select id="selectReferralSource">
                          <option value="Self-Referral" selected>Self-Referral</option>
                          <option value="Private Physician">Private Physician</option>
                          <option value="Government Hospital / RHU">Government Hospital / RHU</option>
                          <option value="Family / Friend">Family / Friend</option>
                        </select>
                        <svg class="chevron-arrow" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                          <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                      </div>
                    </div>
                  </div>

                  <div class="form-grid-row row-1-col">
                    <div class="text-field-box">
                      <label>Relevant Medical History / Current Medications (Optional)</label>
                      <textarea id="inputIllnessDescription" placeholder="Any existing conditions, past surgeries, allergies, or active maintenance medicines..."></textarea>
                    </div>
                  </div>
                </div>
              </div>

              <!-- ── PHASE 3: Appointment Details ── -->
              <div class="phase-step" id="phaseStep3">
                <div class="inner-form-box">
                  <h2 class="inner-box-title">Appointment Details</h2>

                  <div class="form-grid-row row-1-col" style="margin-bottom: 0.9rem;">
                    <div class="companion-row">
                      <span class="companion-label">Consultation Mode <span class="req-mark">*</span></span>
                      <div class="radio-options-wrap">
                        <label class="radio-pill-item">
                          <input type="radio" name="consultation_mode" value="Face-to-Face" checked />
                          <span>Face-to-Face (At Hospital)</span>
                        </label>
                        <label class="radio-pill-item">
                          <input type="radio" name="consultation_mode" value="Online Teleconsultation" />
                          <span>Online Teleconsultation</span>
                        </label>
                      </div>
                    </div>
                  </div>

                  <div class="form-grid-row row-2-cols">
                    <div class="text-field-box">
                      <label>Appointment Date <span class="req-mark">*</span></label>
                      <input type="date" id="inputAppointmentDate" min="<?= $tomorrow ?>" value="<?= $tomorrow ?>" required />
                      <span style="font-size: 0.72rem; color: #64748b; margin-top: 3px;">Advance booking required (Mon–Sat)</span>
                    </div>

                    <div class="text-field-box">
                      <label>Scheduled Time Block <span class="req-mark">*</span></label>
                      <div class="select-container">
                        <select id="selectScheduledTime" required>
                          <option value="08:00:00" selected>08:00 AM – 10:00 AM (Morning Session)</option>
                          <option value="10:00:00">10:00 AM – 12:00 PM (Morning Session)</option>
                          <option value="13:00:00">01:00 PM – 03:00 PM (Afternoon Session)</option>
                          <option value="15:00:00">03:00 PM – 05:00 PM (Afternoon Session)</option>
                        </select>
                        <svg class="chevron-arrow" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                          <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                      </div>
                    </div>
                  </div>

                </div>
              </div>

              <!-- ── PHASE 4: Review & Confirmation ── -->
              <div class="phase-step" id="phaseStep4">
                <div class="inner-form-box">
                  <h2 class="inner-box-title">Review & Confirmation</h2>

                  <div class="review-summary-grid">
                    <div class="review-item">
                      <span class="review-lbl">Department</span>
                      <span class="review-val" id="revDept">-</span>
                    </div>
                    <div class="review-item">
                      <span class="review-lbl">Consultation Mode</span>
                      <span class="review-val" id="revMode">Face-to-Face</span>
                    </div>
                    <div class="review-item">
                      <span class="review-lbl">Patient Full Name</span>
                      <span class="review-val" id="revName">-</span>
                    </div>
                    <div class="review-item">
                      <span class="review-lbl">Contact & Email</span>
                      <span class="review-val" id="revContact">-</span>
                    </div>
                    <div class="review-item">
                      <span class="review-lbl">Scheduled Date & Time</span>
                      <span class="review-val" id="revDateTime">-</span>
                    </div>
                    <div class="review-item">
                      <span class="review-lbl">Chief Complaint</span>
                      <span class="review-val" id="revComplaint" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 260px;">-</span>
                    </div>
                  </div>

                  <!-- Outpatient Triage & Confirmation Notice -->
                  <div style="margin-top: 12px; border-top: 1px solid #e2e8f0; padding-top: 10px;">
                    <label class="consent-checkbox-wrap" id="consentLabelWrap" style="cursor: pointer; display: flex; align-items: flex-start; gap: 8px; font-size: 0.78rem; color: #1e293b; line-height: 1.4;">
                      <input type="checkbox" id="checkConsent" checked style="accent-color: #15803d; width: 16px; height: 16px; margin-top: 1px; cursor: pointer;" />
                      <span>I confirm that all entered details and chief complaints are accurate, consented under R.A. 10173, and ready for outpatient triage review.</span>
                    </label>
                  </div>
                </div>
              </div>

            </div>

            <!-- Success Confirmation Screen (Replaces form on successful booking) -->
            <div id="bookingSuccessBox" class="booking-success-box">
              <div class="success-icon-ring">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
              </div>
              <h3 class="success-title">Appointment Successfully Booked!</h3>
              <p style="font-size: 0.82rem; color: #64748b; margin-bottom: 2px;">Your Appointment Reference Code:</p>
              <div class="success-ref-code" id="successPatientId">PT-2026-XXX</div>
              <p class="success-desc">
                Your appointment has been logged with initial status <strong style="color: #047857;">For Approval</strong>. 
                You can track real-time consultation progress and updates directly from your dashboard.
              </p>
              <a href="/sdn/pages/patient-dashboard.php" class="btn-success-dashboard">
                <span>View in Patient Dashboard</span>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="5" y1="12" x2="19" y2="12"></line>
                  <polyline points="12 5 19 12 12 19"></polyline>
                </svg>
              </a>
            </div>

          </div>

          <!-- Bottom Action Buttons -->
          <div class="bottom-action-bar" id="actionButtonBar">
            <button type="button" class="btn-pill-back" id="btnBackStep" style="display: none;">Back</button>
            <button type="button" class="btn-pill-next" id="btnNextStep">Next</button>
          </div>

        </section>

      </div>
    </main>

    <!-- Booking State & Step Controller -->
    <script>
      document.addEventListener('DOMContentLoaded', () => {
        let currentStep = 1;
        const totalSteps = 4;
        let departmentLocked = <?= !empty($selectedDept) ? 'true' : 'false' ?>;

        // Elements
        const alertBox        = document.getElementById('bookingAlert');
        const btnNext         = document.getElementById('btnNextStep');
        const btnBack         = document.getElementById('btnBackStep');
        const actionButtonBar = document.getElementById('actionButtonBar');
        const formWrapper     = document.getElementById('bookingFormWrapper');
        const successBox      = document.getElementById('bookingSuccessBox');
        const cardTopRow      = document.getElementById('cardTopRow');

        // Form Inputs
        const departmentSelect    = document.getElementById('departmentSelect');
        const deptSelectWrap      = document.getElementById('deptSelectWrap');
        const staticDeptBox       = document.getElementById('staticDeptBox');
        const staticDeptText      = document.getElementById('staticDeptText');
        const inquiryBadgeBtn     = document.getElementById('inquiryBadgeBtn');
        const inquiryOnlyCheckbox = document.getElementById('inquiryOnlyCheckbox');
        const inputFirstName      = document.getElementById('inputFirstName');
        const inputLastName       = document.getElementById('inputLastName');
        const inputMiddleInitial  = document.getElementById('inputMiddleInitial');
        const selectGender        = document.getElementById('selectGender');
        const inputBirthdate      = document.getElementById('inputBirthdate');
        const inputAge            = document.getElementById('inputAge');
        const inputContactNumber  = document.getElementById('inputContactNumber');
        const inputEmail          = document.getElementById('inputEmail');
        const inputAddress        = document.getElementById('inputAddress');

        const inputChiefComplaints    = document.getElementById('inputChiefComplaints');
        const selectConsultationNature = document.getElementById('selectConsultationNature');
        const selectReferralSource     = document.getElementById('selectReferralSource');
        const inputIllnessDescription = document.getElementById('inputIllnessDescription');

        const inputAppointmentDate    = document.getElementById('inputAppointmentDate');
        const selectScheduledTime     = document.getElementById('selectScheduledTime');
        const checkConsent            = document.getElementById('checkConsent');

        // Automatic Age Calculation from Birthdate
        inputBirthdate.addEventListener('input', () => {
          if (!inputBirthdate.value) {
            inputAge.value = '';
            return;
          }
          const birth = new Date(inputBirthdate.value);
          const today = new Date();
          let age = today.getFullYear() - birth.getFullYear();
          const m = today.getMonth() - birth.getMonth();
          if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) {
            age--;
          }
          inputAge.value = age >= 0 ? age : '';
        });

        function showAlert(msg, isError = true) {
          alertBox.textContent = msg;
          alertBox.className = isError ? 'form-alert-msg error' : 'form-alert-msg success';
          alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        function clearAlert() {
          alertBox.textContent = '';
          alertBox.className = 'form-alert-msg';
        }

        function updateStepUI() {
          clearAlert();

          // Show Department top row and Inquiry Only button strictly in Phase 1 (Personal Information)
          // Completely hide them in Phase 2, 3, and 4
          if (currentStep > 1) {
            if (cardTopRow) cardTopRow.style.display = 'none';
            if (inquiryBadgeBtn) inquiryBadgeBtn.style.display = 'none';
          } else {
            if (cardTopRow) cardTopRow.style.display = 'flex';
            if (inquiryBadgeBtn) inquiryBadgeBtn.style.display = 'inline-flex';
            if (departmentLocked) {
              deptSelectWrap.style.display = 'none';
              staticDeptBox.style.display = 'flex';
              staticDeptText.textContent = departmentSelect.value || 'No preference (Staff will assign based on complaint)';
              departmentSelect.disabled = true;
            } else {
              deptSelectWrap.style.display = 'block';
              staticDeptBox.style.display = 'none';
              departmentSelect.disabled = false;
            }
          }

          // Update active step page
          for (let i = 1; i <= totalSteps; i++) {
            const stepEl = document.getElementById(`phaseStep${i}`);
            const indEl  = document.getElementById(`stepIndicator${i}`);
            const lineEl = document.getElementById(`stepLine${i}`);

            if (stepEl) {
              stepEl.classList.toggle('active', i === currentStep);
            }
            if (indEl) {
              indEl.classList.remove('active', 'completed');
              if (i === currentStep) {
                indEl.classList.add('active');
              } else if (i < currentStep) {
                indEl.classList.add('completed');
              }
            }
            if (lineEl) {
              lineEl.classList.toggle('completed', i < currentStep);
            }
          }

          // Back button visibility
          btnBack.style.display = currentStep > 1 ? 'inline-block' : 'none';

          // Next button text & consent state
          if (currentStep === totalSteps) {
            btnNext.textContent = 'Confirm & Book Appointment';
            btnNext.disabled = !checkConsent.checked;
          } else {
            btnNext.textContent = 'Next';
            btnNext.disabled = false;
          }
        }

        // ── Data Privacy Act (R.A. 10173) Modal Overlay Logic ──
        const privacyOverlay           = document.getElementById('privacyOverlay');
        const modalPrivacyScrollBox    = document.getElementById('modalPrivacyScrollBox');
        const modalPrivacyConsentCheck = document.getElementById('modalPrivacyConsentCheck');
        const modalPrivacyLabel        = document.getElementById('modalPrivacyLabel');
        const privacyHintText          = document.getElementById('privacyHintText');
        const privacyScrollPill        = document.getElementById('privacyScrollPill');
        const btnProceedBooking        = document.getElementById('btnProceedBooking');

        let privacyModalScrolled = false;

        if (modalPrivacyScrollBox) {
          modalPrivacyScrollBox.addEventListener('scroll', () => {
            if (privacyModalScrolled) return;
            if (modalPrivacyScrollBox.scrollTop + modalPrivacyScrollBox.clientHeight >= modalPrivacyScrollBox.scrollHeight - 6) {
              privacyModalScrolled = true;
              if (modalPrivacyConsentCheck) modalPrivacyConsentCheck.disabled = false;
              if (modalPrivacyLabel) modalPrivacyLabel.classList.add('unlocked');
              if (privacyHintText) privacyHintText.classList.add('hidden');
              if (privacyScrollPill) {
                privacyScrollPill.className = 'privacy-status-pill ready';
                privacyScrollPill.innerHTML = '<svg width="12" height="12" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> Ready to Consent';
              }
            }
          });
        }

        if (modalPrivacyConsentCheck) {
          modalPrivacyConsentCheck.addEventListener('change', () => {
            if (btnProceedBooking) {
              btnProceedBooking.disabled = !modalPrivacyConsentCheck.checked;
            }
          });
        }

        if (btnProceedBooking) {
          btnProceedBooking.addEventListener('click', () => {
            if (!modalPrivacyConsentCheck.checked) return;
            if (privacyOverlay) {
              privacyOverlay.classList.add('hidden-overlay');
            }
            document.body.classList.remove('privacy-locked');
            if (inputFirstName) inputFirstName.focus();
          });
        }

        if (checkConsent) {
          checkConsent.addEventListener('change', () => {
            if (currentStep === totalSteps) {
              btnNext.disabled = !checkConsent.checked;
            }
          });
        }

        departmentSelect.addEventListener('change', () => clearAlert());

        function validateStep1() {
          if (!inputFirstName.value.trim()) {
            showAlert('Please enter your first name.');
            inputFirstName.focus();
            return false;
          }
          if (!inputLastName.value.trim()) {
            showAlert('Please enter your last name.');
            inputLastName.focus();
            return false;
          }
          if (!selectGender.value) {
            showAlert('Please select your gender.');
            selectGender.focus();
            return false;
          }
          if (!inputBirthdate.value) {
            showAlert('Please specify your birthdate.');
            inputBirthdate.focus();
            return false;
          }
          if (!inputContactNumber.value.trim()) {
            showAlert('Please enter your mobile contact number.');
            inputContactNumber.focus();
            return false;
          }
          if (!inputEmail.value.trim() || !inputEmail.value.includes('@')) {
            showAlert('Please enter a valid email address.');
            inputEmail.focus();
            return false;
          }
          if (!inputAddress.value.trim()) {
            showAlert('Please enter your home address.');
            inputAddress.focus();
            return false;
          }
          return true;
        }

        function validateStep2() {
          if (!inquiryOnlyCheckbox.checked) {
            if (!inputChiefComplaints.value.trim()) {
              showAlert('Please enter your chief complaints / reason for consultation.');
              inputChiefComplaints.focus();
              return false;
            }
          }
          return true;
        }

        function validateStep3() {
          if (!inquiryOnlyCheckbox.checked) {
            if (!inputAppointmentDate.value) {
              showAlert('Please select an appointment date.');
              inputAppointmentDate.focus();
              return false;
            }

            const chosenDate = new Date(inputAppointmentDate.value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            if (chosenDate <= today) {
              showAlert('Appointments must be scheduled at least 1 day in advance.');
              return false;
            }

            if (chosenDate.getDay() === 0) {
              showAlert('The outpatient clinic is closed on Sundays. Please choose Monday to Saturday.');
              return false;
            }
          }
          return true;
        }

        async function checkDateSlotsConflict() {
          const dt = inputAppointmentDate.value;
          if (!dt) return;
          try {
            const res = await fetch(`/sdn/api/appointments/check_conflicts.php?date=${encodeURIComponent(dt)}`);
            const data = await res.json();
            if (data.success) {
              const disabledTimes = data.disabled_times || [];
              let firstAvailable = null;
              Array.from(selectScheduledTime.options).forEach(opt => {
                const isConflict = disabledTimes.includes(opt.value);
                opt.disabled = isConflict;
                const baseText = opt.text.replace(' [Already Booked]', '');
                if (isConflict) {
                  opt.text = baseText + ' [Already Booked]';
                } else {
                  opt.text = baseText;
                  if (!firstAvailable) firstAvailable = opt.value;
                }
              });

              if (selectScheduledTime.selectedOptions[0]?.disabled && firstAvailable) {
                selectScheduledTime.value = firstAvailable;
              }

              if (data.disabled_periods && data.disabled_periods.length === 1) {
                showAlert(data.notice);
              } else if (data.disabled_periods && data.disabled_periods.length >= 2) {
                showAlert('You have reached the maximum bookings for this date (both Morning and Afternoon). Please choose another date.');
              }
            }
          } catch (e) {
            console.warn('Conflict check error:', e);
          }
        }

        inputAppointmentDate.addEventListener('change', checkDateSlotsConflict);

        function populateReviewStep() {
          const companionVal = document.querySelector('input[name="with_companion"]:checked')?.value === 'yes' ? 'Yes' : 'No';
          const modeVal      = document.querySelector('input[name="consultation_mode"]:checked')?.value || 'Face-to-Face';

          document.getElementById('revDept').textContent = departmentSelect.value || 'No preference (Staff will assign based on complaint)';
          document.getElementById('revMode').textContent = modeVal;
          document.getElementById('revName').textContent = `${inputFirstName.value.trim()} ${inputMiddleInitial.value.trim()} ${inputLastName.value.trim()}`.replace(/\s+/g, ' ');
          document.getElementById('revContact').textContent = `+63 ${inputContactNumber.value.trim()} | ${inputEmail.value.trim()}`;
          
          const timeText = selectScheduledTime.options[selectScheduledTime.selectedIndex]?.text || '';
          document.getElementById('revDateTime').textContent = `${inputAppointmentDate.value} (${timeText.split('(')[0].trim()})`;
          
          document.getElementById('revComplaint').textContent = inputChiefComplaints.value.trim() || 'General Consultation';
        }

        // Next / Submit Handler
        btnNext.addEventListener('click', async () => {
          clearAlert();

          if (currentStep === 1) {
            if (!validateStep1()) return;
            departmentLocked = true;
            currentStep = 2;
            updateStepUI();
          } else if (currentStep === 2) {
            if (!validateStep2()) return;
            currentStep = 3;
            updateStepUI();
            checkDateSlotsConflict();
          } else if (currentStep === 3) {
            if (!validateStep3()) return;
            populateReviewStep();
            currentStep = 4;
            updateStepUI();
          } else if (currentStep === 4) {
            // Final submission
            if (!checkConsent.checked) {
              showAlert('Please check the confirmation box to proceed.');
              return;
            }

            btnNext.disabled = true;
            btnNext.textContent = 'Booking Appointment...';

            const payload = {
              department: departmentSelect.value || (staticDeptText ? staticDeptText.textContent.trim() : ''),
              inquiry_only: inquiryOnlyCheckbox.checked ? 1 : 0,
              first_name: inputFirstName.value.trim(),
              last_name: inputLastName.value.trim(),
              middle_initial: inputMiddleInitial.value.trim(),
              gender: selectGender.value,
              birthdate: inputBirthdate.value,
              age: inputAge.value,
              contact_number: '+63' + inputContactNumber.value.trim().replace(/^\+63/, ''),
              email: inputEmail.value.trim(),
              address: inputAddress.value.trim(),
              with_companion: document.querySelector('input[name="with_companion"]:checked')?.value || 'yes',
              chief_complaints: inputChiefComplaints.value.trim(),
              consultation_nature: selectConsultationNature.value,
              referral_source: selectReferralSource.value,
              illness_description: inputIllnessDescription.value.trim(),
              consultation_mode: document.querySelector('input[name="consultation_mode"]:checked')?.value || 'Face-to-Face',
              appointment_date: inputAppointmentDate.value,
              scheduled_time: selectScheduledTime.value
            };

            try {
              const res = await fetch('/sdn/api/patient_book_appointment.php', {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
              });

              const result = await res.json();

              if (!res.ok || !result.success) {
                showAlert(result.message || 'Failed to submit appointment. Please check your entries.');
                btnNext.disabled = false;
                btnNext.textContent = 'Confirm & Book Appointment';
                return;
              }

              // Success! Show confirmation card
              formWrapper.style.display = 'none';
              actionButtonBar.style.display = 'none';
              document.getElementById('successPatientId').textContent = result.patient_id || 'PT-2026-CONFIRMED';
              successBox.style.display = 'block';

              // Mark all stepper steps as completed
              for (let i = 1; i <= totalSteps; i++) {
                const indEl = document.getElementById(`stepIndicator${i}`);
                const lineEl = document.getElementById(`stepLine${i}`);
                if (indEl) indEl.className = 'step-row completed';
                if (lineEl) lineEl.className = 'step-line completed';
              }

            } catch (err) {
              console.error('Booking submission error:', err);
              showAlert('A network connection error occurred. Please try again.');
              btnNext.disabled = false;
              btnNext.textContent = 'Confirm & Book Appointment';
            }
          }
        });

        // Back button handler
        btnBack.addEventListener('click', () => {
          if (currentStep > 1) {
            currentStep--;
            updateStepUI();
          }
        });

        // Toggle Inquiry Mode notice
        inquiryOnlyCheckbox.addEventListener('change', () => {
          if (inquiryOnlyCheckbox.checked) {
            showAlert('Inquiry Mode enabled: You can submit general clinical inquiries without reserving a strict time slot.', false);
          } else {
            clearAlert();
          }
        });

        // Initialize UI
        updateStepUI();
      });
    </script>
  </body>
</html>

