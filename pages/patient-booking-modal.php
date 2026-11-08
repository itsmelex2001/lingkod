<!-- Patient Booking Confirmation & Retrieval Modal -->
<div id="patientBookingConfirmModal" class="pbc-modal-overlay" aria-hidden="true">
  <div class="pbc-modal-backdrop" onclick="closeBookingConfirmModal()"></div>
  <div class="pbc-modal-container" role="dialog" aria-modal="true" aria-labelledby="pbcModalTitle">
    
    <!-- Top Close Button -->
    <button type="button" class="pbc-btn-close" onclick="closeBookingConfirmModal()" aria-label="Close modal" title="Close">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <line x1="18" y1="6" x2="6" y2="18"></line>
        <line x1="6" y1="6" x2="18" y2="18"></line>
      </svg>
    </button>

    <!-- VIEW 1: Patient Status Confirmation Options -->
    <div id="pbcViewOptions" class="pbc-view active">
      <div class="pbc-header">
        <div class="pbc-badge-icon">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
            <line x1="16" y1="2" x2="16" y2="6"></line>
            <line x1="8" y1="2" x2="8" y2="6"></line>
            <line x1="3" y1="10" x2="21" y2="10"></line>
          </svg>
        </div>
        <h2 id="pbcModalTitle" class="pbc-title">Book an Appointment</h2>
        <p class="pbc-subtitle">Welcome to MMWGH SDN. Please select your patient status to continue.</p>
      </div>

      <div class="pbc-options-grid">
        
        <!-- Option 1: Already Have an Account -->
        <a href="/sdn/pages/patient-login.php" target="_blank" rel="noopener noreferrer" class="pbc-option-card option-account" id="pbcBtnHaveAccount" onclick="closeBookingConfirmModal()">
          <div class="pbc-card-accent"></div>
          <div class="pbc-card-left">
            <div class="pbc-icon-pill icon-account">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
              </svg>
            </div>
            <div class="pbc-card-info">
              <h3 class="pbc-card-title">Already Have an Account</h3>
              <p class="pbc-card-desc">Sign in to access your medical history and speed up appointment booking.</p>
            </div>
          </div>
          <div class="pbc-card-action">
            <span class="pbc-action-label">Sign In</span>
            <div class="pbc-arrow-circle">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="9 18 15 12 9 6"></polyline>
              </svg>
            </div>
          </div>
        </a>

        <!-- Option 2: New Patient -->
        <a href="/sdn/pages/register.php" target="_blank" rel="noopener noreferrer" class="pbc-option-card option-new" id="pbcBtnNewPatient" onclick="closeBookingConfirmModal()">
          <div class="pbc-card-accent"></div>
          <div class="pbc-card-left">
            <div class="pbc-icon-pill icon-new">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="8.5" cy="7" r="4"></circle>
                <line x1="20" y1="8" x2="20" y2="14"></line>
                <line x1="23" y1="11" x2="17" y2="11"></line>
              </svg>
            </div>
            <div class="pbc-card-info">
              <h3 class="pbc-card-title">New Patient</h3>
              <p class="pbc-card-desc">First-time consultation at MMWGH? Proceed directly to department registration.</p>
            </div>
          </div>
          <div class="pbc-card-action">
            <span class="pbc-action-label">Get Started</span>
            <div class="pbc-arrow-circle">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="9 18 15 12 9 6"></polyline>
              </svg>
            </div>
          </div>
        </a>

      </div>

      <div class="pbc-footer-note">
        <span>Need urgent medical attention? Please proceed immediately to the MMWGH Emergency Department.</span>
      </div>
    </div>

  </div>
</div>

<style>
/* ── Minimal, Animated, Glassmorphic Modal Styles ── */
.pbc-modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 99999;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.25rem;
  opacity: 0;
  visibility: hidden;
  pointer-events: none;
  transition: opacity 0.28s cubic-bezier(0.16, 1, 0.3, 1), visibility 0.28s cubic-bezier(0.16, 1, 0.3, 1);
}

.pbc-modal-overlay.active {
  opacity: 1;
  visibility: visible;
  pointer-events: auto;
}

.pbc-modal-backdrop {
  position: absolute;
  inset: 0;
  background: rgba(10, 25, 18, 0.62);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
}

.pbc-modal-container {
  position: relative;
  z-index: 2;
  width: 100%;
  max-width: 580px;
  background: #ffffff;
  border: 1px solid rgba(16, 185, 129, 0.22);
  border-radius: 20px;
  box-shadow:
    0 24px 60px -12px rgba(6, 78, 59, 0.28),
    0 8px 24px -4px rgba(0, 0, 0, 0.08);
  padding: 28px 26px 24px 26px;
  box-sizing: border-box;
  transform: scale(0.94) translateY(12px);
  transition: transform 0.28s cubic-bezier(0.16, 1, 0.3, 1);
  overflow: hidden;
}

.pbc-modal-overlay.active .pbc-modal-container {
  transform: scale(1) translateY(0);
}

/* Close button */
.pbc-btn-close {
  position: absolute;
  top: 18px;
  right: 18px;
  width: 34px;
  height: 34px;
  border-radius: 50%;
  border: 1px solid #e2e8f0;
  background: #f8fafc;
  color: #64748b;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.18s ease;
  z-index: 10;
}

.pbc-btn-close:hover {
  background: #fee2e2;
  border-color: #fca5a5;
  color: #dc2626;
  transform: rotate(90deg);
}

/* Header */
.pbc-header {
  text-align: center;
  margin-bottom: 20px;
}

.pbc-badge-icon {
  width: 44px;
  height: 44px;
  margin: 0 auto 10px auto;
  border-radius: 12px;
  background: linear-gradient(135deg, #059669 0%, #10b981 100%);
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3);
}

.pbc-title {
  margin: 0;
  font-size: 1.42rem;
  font-weight: 800;
  color: #064e3b;
  letter-spacing: -0.3px;
  font-family: 'Inter', system-ui, sans-serif;
}

.pbc-subtitle {
  margin: 5px auto 0 auto;
  font-size: 0.85rem;
  color: #64748b;
  max-width: 420px;
  line-height: 1.4;
}

/* Options Grid */
.pbc-options-grid {
  display: flex;
  flex-direction: column;
  gap: 11px;
}

.pbc-option-card {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  padding: 14px 18px;
  background: #f8fafc;
  border: 1.5px solid #e2e8f0;
  border-radius: 14px;
  text-decoration: none;
  color: inherit;
  cursor: pointer;
  transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
  box-sizing: border-box;
  text-align: left;
  overflow: hidden;
}

.pbc-card-accent {
  position: absolute;
  top: 0;
  bottom: 0;
  left: 0;
  width: 4px;
  background: transparent;
  transition: background 0.2s ease;
}

.pbc-option-card:hover {
  transform: translateY(-2px);
  background: #ffffff;
  box-shadow: 0 8px 20px -4px rgba(16, 185, 129, 0.14);
}

.pbc-option-card.option-account:hover {
  border-color: #10b981;
}
.pbc-option-card.option-account:hover .pbc-card-accent {
  background: #059669;
}

.pbc-option-card.option-new:hover {
  border-color: #059669;
}
.pbc-option-card.option-new:hover .pbc-card-accent {
  background: #10b981;
}

.pbc-option-card.option-retrieve:hover {
  border-color: #0d9488;
}
.pbc-option-card.option-retrieve:hover .pbc-card-accent {
  background: #0d9488;
}

.pbc-card-left {
  display: flex;
  align-items: center;
  gap: 14px;
  flex: 1;
}

.pbc-icon-pill {
  width: 42px;
  height: 42px;
  border-radius: 11px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: transform 0.2s ease;
}

.pbc-option-card:hover .pbc-icon-pill {
  transform: scale(1.08);
}

.icon-account {
  background: #dcfce7;
  color: #15803d;
}

.icon-new {
  background: #ecfdf5;
  color: #059669;
}

.pbc-card-info {
  flex: 1;
}

.pbc-card-title {
  margin: 0;
  font-size: 0.96rem;
  font-weight: 700;
  color: #0f172a;
}

.pbc-card-desc {
  margin: 2px 0 0 0;
  font-size: 0.77rem;
  color: #64748b;
  line-height: 1.35;
}

.pbc-card-action {
  display: flex;
  align-items: center;
  gap: 8px;
}

.pbc-action-label {
  font-size: 0.8rem;
  font-weight: 600;
  color: #059669;
  white-space: nowrap;
}

.pbc-arrow-circle {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: #f1f5f9;
  color: #475569;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.pbc-option-card:hover .pbc-arrow-circle {
  background: #059669;
  color: #ffffff;
  transform: translateX(3px);
}

.pbc-footer-note {
  margin-top: 16px;
  padding-top: 12px;
  border-top: 1px solid #f1f5f9;
  text-align: center;
  font-size: 0.72rem;
  color: #94a3b8;
  line-height: 1.4;
}

@media (max-width: 600px) {
  .pbc-modal-container {
    padding: 22px 18px 18px 18px;
  }
  .pbc-title {
    font-size: 1.25rem;
  }
  .pbc-option-card {
    padding: 12px 14px;
    gap: 10px;
  }
}
</style>

<script>
// Patient Booking Modal Controller
(function() {
  window.openBookingConfirmModal = function(e) {
    if (e && e.preventDefault) e.preventDefault();
    const modal = document.getElementById('patientBookingConfirmModal');
    if (!modal) return;
    modal.classList.add('active');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  };

  window.closeBookingConfirmModal = function() {
    const modal = document.getElementById('patientBookingConfirmModal');
    if (!modal) return;
    modal.classList.remove('active');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  };

  // Keyboard escape listener
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      const modal = document.getElementById('patientBookingConfirmModal');
      if (modal && modal.classList.contains('active')) {
        closeBookingConfirmModal();
      }
    }
  });

  // Auto-hook elements with .hero-btn-book-inline or similar links
  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.hero-btn-book-inline, a[href*="department-select.php"]').forEach(btn => {
      // Don't intercept back buttons or direct links if marked
      if (btn.classList.contains('no-pbc-intercept') || btn.closest('nav') && btn.classList.contains('btn-back')) return;
      btn.addEventListener('click', (e) => {
        openBookingConfirmModal(e);
      });
    });
  });
})();
</script>

