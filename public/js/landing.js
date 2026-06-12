// landing.js
// Splash Screen Logic
window.addEventListener('load', () => {
  const splash = document.getElementById('splash-screen');

  // Check if splash screen was already shown in this session
  if (sessionStorage.getItem('splashShown')) {
    splash.style.display = 'none';
  } else {
    // Mark as shown for the rest of the session
    sessionStorage.setItem('splashShown', 'true');

    setTimeout(() => {
      splash.style.opacity = '0';
      setTimeout(() => {
        splash.style.display = 'none';
      }, 500); // Wait for transition to finish
    }, 2500); // Show splash for 2.5 seconds
  }
});

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
