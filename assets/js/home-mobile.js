/* assets/js/home-mobile.js
 * Injects mobile footer bottom bar and formats emergency label on mobile (<= 767px).
 */
document.addEventListener('DOMContentLoaded', () => {
  const footerBox = document.querySelector('#footer-new .footer-dark-box-inline');
  if (footerBox && !footerBox.querySelector('.mobile-footer-bottom-bar')) {
    const bar = document.createElement('div');
    bar.className = 'mobile-footer-bottom-bar';
    bar.textContent = '\u00A9 2026 Mariveles Mental Wellness and General Hospital. All rights reserved.';
    footerBox.appendChild(bar);
  }

  // Format emergency button label & brand spelling cleanly on mobile
  const isMobile = window.matchMedia('(max-width: 767px)').matches;
  if (isMobile) {
    const numEl = document.querySelector('#footer-new .emergency-number-inline');
    const lblEl = document.querySelector('#footer-new .emergency-label-inline');
    if (numEl && lblEl) {
      lblEl.textContent = 'Emergency Hotline:';
      lblEl.style.order = '-1';
    }

    const brandEl = document.querySelector('#footer-new .footer-logo-inline');
    if (brandEl) {
      brandEl.textContent = 'MMWGH LINGKOD';
    }
  }
});

