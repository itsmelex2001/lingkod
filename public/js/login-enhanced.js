(function () {
  function initLoginEnhancements() {
    if (!document.body || !document.body.classList.contains('login-v2')) return;

    const rightPanel = document.querySelector('.portal-right');
    if (rightPanel && !rightPanel.querySelector('.login-bg-decor')) {
      const decor = document.createElement('div');
      decor.className = 'login-bg-decor';
      decor.setAttribute('aria-hidden', 'true');
      decor.innerHTML = [
        '<span class="login-blob login-blob-1"></span>',
        '<span class="login-blob login-blob-2"></span>',
        '<span class="login-blob login-blob-3"></span>',
        '<div class="login-ecg-wrap">',
        '  <svg class="login-ecg-svg" viewBox="0 0 600 64" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">',
        '    <path class="login-ecg-path" d="M0 36 L140 36 L154 36 L162 28 L170 36 L186 36 L194 42 L204 10 L214 54 L222 30 L230 36 L246 36 L256 26 L266 36 L380 36 L392 29 L400 36 L414 36 L422 42 L432 12 L442 52 L450 31 L458 36 L474 36 L484 27 L494 36 L600 36" />',
        '  </svg>',
        '</div>'
      ].join('');
      rightPanel.insertBefore(decor, rightPanel.firstChild);
    }

    if (typeof window.updateSubmitState === 'function') {
      window.updateSubmitState();
      setTimeout(window.updateSubmitState, 150);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLoginEnhancements);
  } else {
    initLoginEnhancements();
  }
})();
