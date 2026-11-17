// splash-inline.js
try {
  var isReload = (window.performance && performance.getEntriesByType && performance.getEntriesByType('navigation')[0]?.type === 'reload');
  var isInternalReferrer = false;
  if (document.referrer) {
    var refUrl = new URL(document.referrer);
    isInternalReferrer = refUrl.origin === window.location.origin && refUrl.pathname.indexOf('/sdn/') === 0 && refUrl.pathname !== window.location.pathname;
  }
  if ((sessionStorage.getItem('linkod_splash_shown') || isInternalReferrer) && !isReload && !window.location.search.includes('splash=1')) {
    sessionStorage.setItem('linkod_splash_shown', 'true');
    var splash = document.getElementById('splash-screen');
    if (splash) splash.style.display = 'none';
    if (document.body) document.body.classList.remove('splash-active');
  }
} catch (e) {}
