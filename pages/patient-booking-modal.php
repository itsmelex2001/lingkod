<?php
$pbcHotline = '(047) 935-4138';
try {
    if (!isset($pdo) && file_exists(__DIR__ . '/../api/config/db.php')) {
        require_once __DIR__ . '/../api/config/db.php';
    }
    if (file_exists(__DIR__ . '/../api/config/settings_helper.php')) {
        require_once __DIR__ . '/../api/config/settings_helper.php';
        $pbcHotline = (string)getSettingValue($pdo ?? null, 'emergency_hotline', $pbcHotline);
    }
} catch (Throwable $ignore) {}
$pbcTelDigits = preg_replace('/[^0-9+]/', '', $pbcHotline) ?: '0479354138';
?>
<!-- Patient Booking Confirmation Modal -->
<div id="patientBookingConfirmModal" class="pbc-modal-overlay" aria-hidden="true">
  <div class="pbc-modal-backdrop" onclick="closeBookingConfirmModal()"></div>
  <div class="pbc-modal-container" role="dialog" aria-modal="true" aria-labelledby="pbcModalTitle" aria-describedby="pbcModalSubtitle" tabindex="-1">
    <div class="pbc-sheet-handle" aria-hidden="true"></div>

    <!-- Top Close Button -->
    <button type="button" class="pbc-btn-close" onclick="closeBookingConfirmModal()" aria-label="Close dialog" title="Close">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <line x1="18" y1="6" x2="6" y2="18"></line>
        <line x1="6" y1="6" x2="18" y2="18"></line>
      </svg>
    </button>

    <!-- Header -->
    <div class="pbc-header">
      <div class="pbc-header-top">
        <div class="pbc-badge-icon" aria-hidden="true">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="4" width="18" height="18" rx="2.5"></rect>
            <line x1="16" y1="2" x2="16" y2="6"></line>
            <line x1="8" y1="2" x2="8" y2="6"></line>
            <line x1="3" y1="10" x2="21" y2="10"></line>
            <path d="M9.5 15.5l1.7 1.7 3.8-3.8"></path>
          </svg>
        </div>
        <span class="pbc-eyebrow" data-i18n="pbc_eyebrow">MMWGH &bull; LINGKOD</span>
      </div>
      <h2 id="pbcModalTitle" class="pbc-title" data-i18n="pbc_title">Book an Appointment</h2>
      <p id="pbcModalSubtitle" class="pbc-subtitle" data-i18n="pbc_subtitle">Select your patient status to continue.</p>
    </div>

    <!-- Body -->
    <div class="pbc-body">
      <div class="pbc-options-list">
        <!-- Option 1: Returning Patient (Primary) -->
        <a href="/sdn/pages/patient-login.php" target="_blank" rel="noopener noreferrer" class="pbc-option-card option-account" id="pbcBtnHaveAccount" onclick="closeBookingConfirmModal()">
          <div class="pbc-card-icon-tile icon-primary" aria-hidden="true">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
              <circle cx="12" cy="7" r="4"></circle>
            </svg>
          </div>
          <div class="pbc-card-text">
            <div class="pbc-card-title-row">
              <span class="pbc-card-title" data-i18n="pbc_have_title">I have an account</span>
              <span class="pbc-pill pbc-pill-primary" data-i18n="pbc_have_badge">Returning</span>
            </div>
            <span class="pbc-card-desc" data-i18n="pbc_have_desc">Sign in to book faster and view your visit records.</span>
          </div>
          <span class="pbc-arrow-chip" aria-hidden="true">
            <svg class="pbc-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
          </span>
        </a>

        <!-- Option 2: New Patient -->
        <a href="/sdn/pages/register.php" target="_blank" rel="noopener noreferrer" class="pbc-option-card option-new" id="pbcBtnNewPatient" onclick="closeBookingConfirmModal()">
          <div class="pbc-card-icon-tile icon-secondary" aria-hidden="true">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
              <circle cx="8.5" cy="7" r="4"></circle>
              <line x1="20" y1="8" x2="20" y2="14"></line>
              <line x1="23" y1="11" x2="17" y2="11"></line>
            </svg>
          </div>
          <div class="pbc-card-text">
            <div class="pbc-card-title-row">
              <span class="pbc-card-title" data-i18n="pbc_new_title">I'm a new patient</span>
              <span class="pbc-pill pbc-pill-muted" data-i18n="pbc_new_badge">First visit</span>
            </div>
            <span class="pbc-card-desc" data-i18n="pbc_new_desc">Create your patient profile and book your first consultation.</span>
          </div>
          <span class="pbc-arrow-chip" aria-hidden="true">
            <svg class="pbc-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
          </span>
        </a>
      </div>

      <!-- Emergency Callout -->
      <div class="pbc-emergency-note" role="note">
        <span class="pbc-emergency-icon-wrap" aria-hidden="true">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
          </svg>
        </span>
        <p class="pbc-emergency-text">
          <span data-i18n="pbc_emergency_prefix">In an emergency, go to the MMWGH Emergency Department or call</span>
          <a href="tel:<?= htmlspecialchars($pbcTelDigits, ENT_QUOTES, 'UTF-8') ?>" class="pbc-emergency-tel"><?= htmlspecialchars($pbcHotline, ENT_QUOTES, 'UTF-8') ?></a>.
        </p>
      </div>
    </div>
  </div>
</div>

<style>
:root {
  --pbc-primary: #166534;
  --pbc-primary-dark: #14532d;
  --pbc-primary-deep: #0f2e1c;
  --pbc-primary-light: #e7f6ea;
  --pbc-primary-tint: #f3fbf5;
  --pbc-primary-border: #bbf7d0;
  --pbc-text-strong: #112418;
  --pbc-text-body: #334155;
  --pbc-text-muted: #52635a;
  --pbc-border: #dce7df;
  --pbc-surface: #ffffff;
  --pbc-radius: 20px;
  --pbc-font: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.pbc-modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 999999;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.25rem;
  opacity: 0;
  visibility: hidden;
  pointer-events: none;
  transition: opacity 200ms ease-out, visibility 200ms ease-out;
  font-family: var(--pbc-font);
}

.pbc-modal-overlay.active {
  opacity: 1;
  visibility: visible;
  pointer-events: auto;
}

.pbc-modal-backdrop {
  position: absolute;
  inset: 0;
  background: rgba(8, 28, 15, 0.56);
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
}

.pbc-modal-container {
  position: relative;
  z-index: 2;
  width: 100%;
  max-width: 468px;
  background: var(--pbc-surface);
  border: 1px solid rgba(22, 101, 52, 0.14);
  border-radius: var(--pbc-radius);
  box-shadow:
    0 24px 56px -12px rgba(8, 30, 16, 0.28),
    0 8px 20px -6px rgba(8, 30, 16, 0.12);
  box-sizing: border-box;
  transform: translateY(12px) scale(0.97);
  transition: transform 220ms cubic-bezier(0.22, 1, 0.36, 1);
  overflow: hidden;
}

.pbc-modal-container:focus {
  outline: none;
}

.pbc-modal-overlay.active .pbc-modal-container {
  transform: translateY(0) scale(1);
}

.pbc-sheet-handle {
  display: none;
}

.pbc-btn-close {
  position: absolute;
  top: 18px;
  right: 18px;
  width: 38px;
  height: 38px;
  border-radius: 10px;
  border: 1px solid rgba(22, 101, 52, 0.12);
  background: rgba(255, 255, 255, 0.85);
  color: var(--pbc-text-muted);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  touch-action: manipulation;
  transition: background-color 150ms ease, color 150ms ease, border-color 150ms ease;
  z-index: 10;
}

.pbc-btn-close:hover {
  background-color: #f0fdf4;
  border-color: rgba(22, 101, 52, 0.3);
  color: var(--pbc-primary-deep);
}

.pbc-btn-close:focus-visible {
  outline: 2px solid var(--pbc-primary);
  outline-offset: 2px;
}

/* Header with subtle sage wash */
.pbc-header {
  padding: 24px 28px 18px;
  background: linear-gradient(180deg, #f1fbf3 0%, #ffffff 100%);
  border-bottom: 1px solid #edf5ef;
  text-align: left;
}

.pbc-header-top {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 12px;
  padding-right: 44px;
}

.pbc-badge-icon {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  background: var(--pbc-primary-light);
  border: 1px solid var(--pbc-primary-border);
  color: var(--pbc-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.pbc-eyebrow {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.09em;
  text-transform: uppercase;
  color: var(--pbc-primary);
  white-space: nowrap;
}

.pbc-title {
  margin: 0;
  font-family: var(--pbc-font);
  font-size: clamp(21px, 3vw, 24px);
  font-weight: 700;
  color: var(--pbc-primary-deep);
  letter-spacing: -0.02em;
  line-height: 1.22;
}

.pbc-subtitle {
  margin: 5px 0 0;
  font-size: 14px;
  color: var(--pbc-text-muted);
  line-height: 1.45;
}

/* Body */
.pbc-body {
  padding: 20px 28px 26px;
}

.pbc-options-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.pbc-option-card {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px 16px 16px 18px;
  border-radius: 14px;
  text-decoration: none;
  color: inherit;
  cursor: pointer;
  min-height: 76px;
  box-sizing: border-box;
  touch-action: manipulation;
  transition: background-color 160ms ease, border-color 160ms ease, transform 160ms ease, box-shadow 160ms ease;
  outline: none;
}

.pbc-option-card:active {
  transform: scale(0.99);
}

.pbc-option-card:focus-visible {
  outline: 2px solid var(--pbc-primary);
  outline-offset: 2px;
}

/* Option Cards: Balanced neutral state at rest, forest-green highlight on hover/focus */
.pbc-option-card.option-account,
.pbc-option-card.option-new {
  border: 1.5px solid var(--pbc-border);
  background: #ffffff;
}

.pbc-option-card.option-account:hover,
.pbc-option-card.option-account:focus-visible,
.pbc-option-card.option-new:hover,
.pbc-option-card.option-new:focus-visible {
  background: var(--pbc-primary-tint);
  border-color: var(--pbc-primary);
  transform: translateY(-2px);
  box-shadow: 0 8px 20px -8px rgba(22, 101, 52, 0.18);
}

.pbc-card-icon-tile {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: transform 160ms ease, background-color 160ms ease, color 160ms ease, box-shadow 160ms ease;
}

.pbc-card-icon-tile.icon-primary,
.pbc-card-icon-tile.icon-secondary {
  background: var(--pbc-primary-light);
  color: var(--pbc-primary);
}

.pbc-option-card:hover .pbc-card-icon-tile,
.pbc-option-card:focus-visible .pbc-card-icon-tile {
  background: var(--pbc-primary);
  color: #ffffff;
  box-shadow: 0 4px 10px -2px rgba(22, 101, 52, 0.32);
}

.pbc-card-text {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.pbc-card-title-row {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.pbc-card-title {
  font-size: 15.5px;
  font-weight: 700;
  color: var(--pbc-text-strong);
  line-height: 1.25;
}

.pbc-pill {
  display: inline-flex;
  align-items: center;
  padding: 2px 8px;
  border-radius: 9999px;
  font-size: 11px;
  font-weight: 600;
  line-height: 1.3;
  white-space: nowrap;
}

.pbc-pill-primary,
.pbc-pill-muted {
  background: #f1f5f9;
  color: #475569;
  transition: background-color 160ms ease, color 160ms ease;
}

.pbc-option-card:hover .pbc-pill,
.pbc-option-card:focus-visible .pbc-pill {
  background: #dcfce7;
  color: #14532d;
}

.pbc-card-desc {
  font-size: 13px;
  color: var(--pbc-text-muted);
  line-height: 1.4;
  margin-top: 4px;
}

.pbc-arrow-chip {
  width: 30px;
  height: 30px;
  border-radius: 9999px;
  background: #ffffff;
  border: 1px solid #d7e7dc;
  color: #64748b;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: background-color 160ms ease, border-color 160ms ease, color 160ms ease, transform 160ms ease;
}

.pbc-option-card:hover .pbc-arrow-chip {
  background: var(--pbc-primary);
  border-color: var(--pbc-primary);
  color: #ffffff;
  transform: translateX(2px);
}

/* Emergency Callout */
.pbc-emergency-note {
  margin-top: 18px;
  padding: 12px 14px;
  border-radius: 12px;
  background: #fef2f2;
  border: 1px solid #fecaca;
  display: flex;
  align-items: flex-start;
  gap: 10px;
}

.pbc-emergency-icon-wrap {
  width: 26px;
  height: 26px;
  border-radius: 8px;
  background: #fee2e2;
  color: #b91c1c;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  margin-top: 1px;
}

.pbc-emergency-text {
  margin: 0;
  font-size: 12.5px;
  color: #7f1d1d;
  line-height: 1.45;
}

.pbc-emergency-tel {
  font-weight: 700;
  color: #991b1b;
  text-decoration: underline;
  text-underline-offset: 2px;
  white-space: nowrap;
}

.pbc-emergency-tel:hover {
  color: #7f1d1d;
}

.pbc-emergency-tel:focus-visible {
  outline: 2px solid #b91c1c;
  outline-offset: 2px;
  border-radius: 4px;
}

/* Hide Chatbot Mascot when Modal is Open */
body.pbc-modal-open .chibi-widget-container,
body.pbc-modal-open #chibiChatbotContainer,
body.pbc-modal-open .chibi-ask-me-bubble {
  opacity: 0 !important;
  visibility: hidden !important;
  pointer-events: none !important;
  transform: translateY(20px) scale(0.9) !important;
}

/* Responsive: Bottom sheet on mobile (<600px) */
@media (max-width: 600px) {
  .pbc-modal-overlay {
    align-items: flex-end;
    padding: 0;
  }

  .pbc-modal-container {
    max-width: 100%;
    width: 100%;
    border-radius: 24px 24px 0 0;
    transform: translateY(100%);
  }

  .pbc-modal-container:focus {
  outline: none;
}

.pbc-modal-overlay.active .pbc-modal-container {
    transform: translateY(0);
  }

  .pbc-sheet-handle {
    display: block;
    width: 40px;
    height: 4px;
    border-radius: 9999px;
    background: #cbd5e1;
    margin: 10px auto 0;
  }

  .pbc-btn-close {
    top: 14px;
    right: 14px;
    width: 42px;
    height: 42px;
  }

  .pbc-header {
    padding: 16px 20px 16px;
  }

  .pbc-body {
    padding: 16px 20px 26px;
  }

  .pbc-title {
    font-size: 20px;
  }

  .pbc-option-card {
    padding: 14px;
  }
}

/* Reduced Motion */
@media (prefers-reduced-motion: reduce) {
  .pbc-modal-overlay,
  .pbc-modal-container,
  .pbc-option-card,
  .pbc-card-icon-tile,
  .pbc-arrow-chip {
    transition: none !important;
    transform: none !important;
  }
}
</style>

<script>
(function() {
  let lastActiveTrigger = null;

  window.openBookingConfirmModal = function(e) {
    if (e) {
      if (e.preventDefault) e.preventDefault();
      lastActiveTrigger = e.currentTarget || document.activeElement;
    } else {
      lastActiveTrigger = document.activeElement;
    }

    const modal = document.getElementById('patientBookingConfirmModal');
    if (!modal) return;

    modal.classList.add('active');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('pbc-modal-open');
    document.body.style.overflow = 'hidden';

    document.querySelectorAll('.chibi-widget-container, #chibiChatbotContainer, .chibi-ask-me-bubble').forEach(el => {
      el.classList.add('chatbot-hidden');
    });

    setTimeout(() => {
      const dialog = modal.querySelector('.pbc-modal-container');
      if (dialog) dialog.focus({ preventScroll: true });
    }, 60);
  };

  window.closeBookingConfirmModal = function() {
    const modal = document.getElementById('patientBookingConfirmModal');
    if (!modal) return;

    modal.classList.remove('active');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('pbc-modal-open');
    document.body.style.overflow = '';

    document.querySelectorAll('.chibi-widget-container, #chibiChatbotContainer, .chibi-ask-me-bubble').forEach(el => {
      el.classList.remove('chatbot-hidden');
    });

    if (lastActiveTrigger && typeof lastActiveTrigger.focus === 'function') {
      lastActiveTrigger.focus();
    }
  };

  document.addEventListener('keydown', function(e) {
    const modal = document.getElementById('patientBookingConfirmModal');
    if (!modal || !modal.classList.contains('active')) return;

    if (e.key === 'Escape') {
      e.preventDefault();
      closeBookingConfirmModal();
      return;
    }

    if (e.key === 'Tab') {
      const focusables = modal.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
      const focusableArr = Array.prototype.filter.call(focusables, el => !el.disabled && el.offsetParent !== null);
      if (focusableArr.length === 0) return;

      const firstEl = focusableArr[0];
      const lastEl = focusableArr[focusableArr.length - 1];

      if (e.shiftKey) {
        if (document.activeElement === firstEl) {
          e.preventDefault();
          lastEl.focus();
        }
      } else {
        if (document.activeElement === lastEl) {
          e.preventDefault();
          firstEl.focus();
        }
      }
    }
  });

  document.addEventListener('DOMContentLoaded', function() {
    const selector = '.hero-btn-book-inline, a[href*="department-select.php"], [data-open-booking-modal]';
    document.querySelectorAll(selector).forEach(btn => {
      if (btn.classList.contains('no-pbc-intercept') || (btn.closest('nav') && btn.classList.contains('btn-back'))) return;
      btn.addEventListener('click', function(e) {
        openBookingConfirmModal(e);
      });
    });
  });
})();
</script>
