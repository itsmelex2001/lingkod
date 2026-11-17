<!-- Patient Booking Confirmation Modal -->
<div id="patientBookingConfirmModal" class="pbc-modal-overlay" aria-hidden="true">
  <div class="pbc-modal-backdrop" onclick="closeBookingConfirmModal()"></div>
  <div class="pbc-modal-container" role="dialog" aria-modal="true" aria-labelledby="pbcModalTitle">
    
    <!-- Top Close Button -->
    <button type="button" class="pbc-btn-close" onclick="closeBookingConfirmModal()" aria-label="Close dialog" title="Close">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="18" y1="6" x2="6" y2="18"></line>
        <line x1="6" y1="6" x2="18" y2="18"></line>
      </svg>
    </button>

    <!-- Header -->
    <div class="pbc-header">
      <div class="pbc-badge-icon" aria-hidden="true">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
          <line x1="16" y1="2" x2="16" y2="6"></line>
          <line x1="8" y1="2" x2="8" y2="6"></line>
          <line x1="3" y1="10" x2="21" y2="10"></line>
        </svg>
      </div>
      <h2 id="pbcModalTitle" class="pbc-title">Book an Appointment</h2>
      <p class="pbc-subtitle">Choose how you'd like to continue.</p>
    </div>

    <!-- Option Cards -->
    <div class="pbc-options-list">
      
      <!-- Option 1: Primary (Already Have an Account) -->
      <a href="/sdn/pages/patient-login.php" class="pbc-option-card option-account" id="pbcBtnHaveAccount" onclick="closeBookingConfirmModal()">
        <div class="pbc-card-icon-tile icon-primary">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
          </svg>
        </div>
        <div class="pbc-card-text">
          <span class="pbc-card-title">I have an account</span>
          <span class="pbc-card-desc">Sign in to book faster and see your records.</span>
        </div>
        <svg class="pbc-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <polyline points="9 18 15 12 9 6"></polyline>
        </svg>
      </a>

      <!-- Option 2: Secondary (New Patient) -->
      <a href="/sdn/pages/register.php" class="pbc-option-card option-new" id="pbcBtnNewPatient" onclick="closeBookingConfirmModal()">
        <div class="pbc-card-icon-tile icon-secondary">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
            <circle cx="8.5" cy="7" r="4"></circle>
            <line x1="20" y1="8" x2="20" y2="14"></line>
            <line x1="23" y1="11" x2="17" y2="11"></line>
          </svg>
        </div>
        <div class="pbc-card-text">
          <span class="pbc-card-title">I'm a new patient</span>
          <span class="pbc-card-desc">Choose a department and book your first visit.</span>
        </div>
        <svg class="pbc-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <polyline points="9 18 15 12 9 6"></polyline>
        </svg>
      </a>

    </div>

    <!-- Emergency Note -->
    <div class="pbc-emergency-note">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="pbc-emergency-icon" aria-hidden="true">
        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
      </svg>
      <span>In an emergency, go to the MMWGH Emergency Department or call (047) 935-4138.</span>
    </div>

  </div>
</div>

<style>
:root {
  --pbc-primary: #0a7c29;
  --pbc-primary-hover: #086321;
  --pbc-primary-light: #e8f5ec;
  --pbc-primary-tint: #f0fdf4;
  --pbc-text-strong: #111827;
  --pbc-text-body: #374151;
  --pbc-text-muted: #6b7280;
  --pbc-border: #e5e7eb;
  --pbc-border-hover: #d1d5db;
  --pbc-surface: #ffffff;
  --pbc-radius: 16px;
  --pbc-font: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Overlay */
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
  transition: opacity 180ms ease-out, visibility 180ms ease-out;
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
  background: rgba(10, 25, 15, 0.55);
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
}

/* Modal Card */
.pbc-modal-container {
  position: relative;
  z-index: 2;
  width: 100%;
  max-width: 440px;
  background: var(--pbc-surface);
  border: 1px solid rgba(0, 0, 0, 0.05);
  border-radius: var(--pbc-radius);
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.12);
  padding: clamp(24px, 4vw, 30px);
  box-sizing: border-box;
  transform: translateY(8px);
  transition: transform 180ms ease-out;
  overflow: hidden;
}

.pbc-modal-overlay.active .pbc-modal-container {
  transform: translateY(0);
}

/* Close button */
.pbc-btn-close {
  position: absolute;
  top: 14px;
  right: 14px;
  width: 40px;
  height: 40px;
  border-radius: 8px;
  border: none;
  background: transparent;
  color: var(--pbc-text-muted);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background-color 0.15s ease, color 0.15s ease;
  z-index: 10;
}

.pbc-btn-close:hover {
  background-color: #f3f4f6;
  color: var(--pbc-text-strong);
}

.pbc-btn-close:focus-visible {
  outline: 2px solid var(--pbc-primary);
  outline-offset: 2px;
}

/* Header */
.pbc-header {
  text-align: left;
  margin-bottom: 20px;
}

.pbc-badge-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: var(--pbc-primary-light);
  color: var(--pbc-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 12px;
}

.pbc-title {
  margin: 0;
  font-size: clamp(22px, 3vw, 24px);
  font-weight: 600;
  color: var(--pbc-text-strong);
  letter-spacing: -0.01em;
  line-height: 1.25;
}

.pbc-subtitle {
  margin: 6px 0 0 0;
  font-size: 14px;
  color: var(--pbc-text-muted);
  line-height: 1.45;
}

/* Options List */
.pbc-options-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.pbc-option-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 16px;
  border-radius: 12px;
  text-decoration: none;
  color: inherit;
  cursor: pointer;
  min-height: 64px;
  box-sizing: border-box;
  transition: background-color 0.15s ease, border-color 0.15s ease, transform 0.05s ease;
  outline: none;
}

.pbc-option-card:active {
  transform: scale(0.99);
}

.pbc-option-card:focus-visible {
  outline: 2px solid var(--pbc-primary);
  outline-offset: 2px;
}

/* Card 1: Primary ("Already have an account") */
.pbc-option-card.option-account {
  border: 1px solid var(--pbc-border);
  background: #ffffff;
}

.pbc-option-card.option-account:hover {
  background: var(--pbc-primary-tint);
  border-color: var(--pbc-primary-hover);
}

/* Card 2: Secondary ("New patient") */
.pbc-option-card.option-new {
  border: 1px solid var(--pbc-border);
  background: #ffffff;
}

.pbc-option-card.option-new:hover {
  background: #f9fafb;
  border-color: var(--pbc-border-hover);
}

/* Icon tiles */
.pbc-card-icon-tile {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.pbc-card-icon-tile.icon-primary {
  background: var(--pbc-primary-light);
  color: var(--pbc-primary);
}

.pbc-card-icon-tile.icon-secondary {
  background: #f3f4f6;
  color: #4b5563;
}

/* Card text */
.pbc-card-text {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.pbc-card-title {
  font-size: 16px;
  font-weight: 600;
  color: var(--pbc-text-strong);
  line-height: 1.25;
}

.pbc-card-desc {
  font-size: 13px;
  color: var(--pbc-text-muted);
  line-height: 1.35;
  margin-top: 3px;
}

.pbc-chevron {
  color: #9ca3af;
  flex-shrink: 0;
  margin-left: 8px;
  transition: transform 0.15s ease, color 0.15s ease;
}

.pbc-option-card:hover .pbc-chevron {
  color: var(--pbc-primary);
  transform: translateX(2px);
}

/* Emergency Note */
.pbc-emergency-note {
  margin-top: 20px;
  padding-top: 14px;
  border-top: 1px solid #f3f4f6;
  display: flex;
  align-items: flex-start;
  gap: 8px;
  font-size: 13px;
  color: #4b5563;
  line-height: 1.4;
}

.pbc-emergency-icon {
  flex-shrink: 0;
  color: #dc2626;
  margin-top: 2px;
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
    border-radius: 20px 20px 0 0;
    padding: 24px 20px 28px 20px;
    transform: translateY(100%);
  }

  .pbc-modal-overlay.active .pbc-modal-container {
    transform: translateY(0);
  }

  .pbc-title {
    font-size: 20px;
  }

  .pbc-option-card {
    min-height: 64px;
    padding: 14px 16px;
  }
}

/* Reduced Motion */
@media (prefers-reduced-motion: reduce) {
  .pbc-modal-overlay,
  .pbc-modal-container,
  .pbc-option-card,
  .pbc-chevron {
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

    // Hide chatbot mascot
    document.querySelectorAll('.chibi-widget-container, #chibiChatbotContainer, .chibi-ask-me-bubble').forEach(el => {
      el.classList.add('chatbot-hidden');
    });

    // Focus primary option card
    setTimeout(() => {
      const primaryCard = document.getElementById('pbcBtnHaveAccount');
      if (primaryCard) primaryCard.focus();
    }, 60);
  };

  window.closeBookingConfirmModal = function() {
    const modal = document.getElementById('patientBookingConfirmModal');
    if (!modal) return;

    modal.classList.remove('active');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('pbc-modal-open');
    document.body.style.overflow = '';

    // Restore chatbot mascot
    document.querySelectorAll('.chibi-widget-container, #chibiChatbotContainer, .chibi-ask-me-bubble').forEach(el => {
      el.classList.remove('chatbot-hidden');
    });

    // Restore focus
    if (lastActiveTrigger && typeof lastActiveTrigger.focus === 'function') {
      lastActiveTrigger.focus();
    }
  };

  // Keyboard navigation: Escape key & focus trapping
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

  // Auto-hook elements that open the modal
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
