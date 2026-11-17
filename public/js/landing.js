// landing.js
// Splash Screen & Chatbot Pop-in Controller
(function() {
  function initSplashScreen() {
    const splash = document.getElementById('splash-screen');
    if (!splash) return;

    let isReload = false;
    try {
      if (window.performance && performance.getEntriesByType) {
        const navEntries = performance.getEntriesByType('navigation');
        if (navEntries.length > 0 && navEntries[0].type === 'reload') {
          isReload = true;
        }
      }
    } catch (e) {}

    const splashAlreadySeen = sessionStorage.getItem('linkod_splash_shown');
    let isInternalReferrer = false;
    try {
      if (document.referrer) {
        const refUrl = new URL(document.referrer);
        isInternalReferrer = refUrl.origin === window.location.origin && refUrl.pathname.startsWith('/sdn/') && refUrl.pathname !== window.location.pathname;
      }
    } catch (e) {}

    function triggerChatbotPop() {
      const fab = document.querySelector('.fab-container');
      if (fab) {
        fab.classList.remove('chatbot-hidden');
        fab.classList.add('chatbot-pop-in');
      } else {
        // In case Svelte component is still mounting
        const observer = new MutationObserver((mutations, obs) => {
          const fabEl = document.querySelector('.fab-container');
          if (fabEl) {
            fabEl.classList.remove('chatbot-hidden');
            fabEl.classList.add('chatbot-pop-in');
            obs.disconnect();
          }
        });
        observer.observe(document.body, { childList: true, subtree: true });
        setTimeout(() => observer.disconnect(), 6000);
      }
    }

    if ((splashAlreadySeen || isInternalReferrer) && !isReload && !window.location.search.includes('splash=1')) {
      sessionStorage.setItem('linkod_splash_shown', 'true');
      splash.style.display = 'none';
      if (document.body) {
        document.body.classList.remove('splash-active');
        document.body.classList.add('splash-done');
      }
      const fab = document.querySelector('.fab-container');
      if (fab) fab.classList.remove('chatbot-hidden');
    } else {
      // Show splash screen, mark session
      sessionStorage.setItem('linkod_splash_shown', 'true');
      if (document.body) document.body.classList.add('splash-active');

      const fab = document.querySelector('.fab-container');
      if (fab) fab.classList.add('chatbot-hidden');

      setTimeout(() => {
        splash.style.transition = 'opacity 0.75s cubic-bezier(0.22, 1, 0.36, 1), transform 0.75s cubic-bezier(0.22, 1, 0.36, 1), filter 0.75s cubic-bezier(0.22, 1, 0.36, 1)';
        splash.style.opacity = '0';
        splash.style.transform = 'scale3d(1.04, 1.04, 1)';
        splash.style.filter = 'blur(8px)';

        setTimeout(() => {
          splash.style.display = 'none';
          if (document.body) {
            document.body.classList.remove('splash-active');
            document.body.classList.add('splash-done');
          }
          // Trigger the chatbot pop-up entrance animation!
          triggerChatbotPop();
        }, 700);
      }, 3100);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSplashScreen);
  } else {
    initSplashScreen();
  }
})();

function toggleServiceDetails(card) {
  // Remove flipped class from all other cards
  document.querySelectorAll('.service-card').forEach(c => {
    if (c !== card) c.classList.remove('flipped');
  });
  // Toggle flipped class on clicked card
  card.classList.toggle('flipped');
}

function toggleFaqModal() {
  const modal = document.getElementById('faqModal');
  modal.classList.toggle('active');
}

function toggleFaqAnswer(btn) {
  const item = btn.closest('.faq-item');

  // Close other open FAQs
  document.querySelectorAll('.faq-item.active').forEach(openItem => {
    if (openItem !== item) {
      openItem.classList.remove('active');
    }
  });

  // Toggle current FAQ
  item.classList.toggle('active');
}
