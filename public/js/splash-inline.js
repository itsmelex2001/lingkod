// splash-inline.js
try {
  var isReload = (window.performance && performance.getEntriesByType && performance.getEntriesByType('navigation')[0]?.type === 'reload');
  if (sessionStorage.getItem('linkod_splash_shown') && !isReload && !window.location.search.includes('splash=1')) {
    var splash = document.getElementById('splash-screen');
    if (splash) splash.style.display = 'none';
    if (document.body) document.body.classList.remove('splash-active');
  }
} catch (e) {}
