/* assets/js/dashboard-mobile.js
 * Mobile navigation, tap-to-copy ID, encoding cleanup, and badge syncing for MMWGH LINGKOD.
 */
document.addEventListener('DOMContentLoaded', () => {
  // 1. Tap-to-copy Patient ID under hero greeting
  const idChip = document.getElementById('mobileHeroIdChip');
  const idTextEl = document.getElementById('mobileHeroIdText');
  const idCopyFeedback = document.getElementById('mobileHeroIdCopyLabel');

  if (idChip && idTextEl) {
    idChip.addEventListener('click', async () => {
      const raw = (idTextEl.textContent || '').replace(/^ID:\s*/i, '').trim();
      if (!raw || raw === 'Verified') return;

      try {
        await navigator.clipboard.writeText(raw);
      } catch {
        const temp = document.createElement('textarea');
        temp.value = raw;
        document.body.appendChild(temp);
        temp.select();
        document.execCommand('copy');
        document.body.removeChild(temp);
      }

      if (idCopyFeedback) {
        const prev = idCopyFeedback.textContent;
        idCopyFeedback.textContent = 'Copied';
        setTimeout(() => {
          idCopyFeedback.textContent = prev;
        }, 1800);
      }
    });
  }

  // Sync hero ID chip if dynamic profile data updates #topPatientIdChip
  const topChip = document.getElementById('topPatientIdChip');
  if (topChip && idTextEl) {
    const syncTopId = () => {
      const val = (topChip.textContent || '').trim();
      if (val) idTextEl.textContent = val;
    };
    syncTopId();
    new MutationObserver(syncTopId).observe(topChip, {
      childList: true,
      characterData: true,
      subtree: true
    });
  }

  // 2. Sanitize mojibake separators (â€¢ / •) to clean middot (·)
  function cleanSeparators(el) {
    if (!el) return;
    const text = el.textContent || '';
    if (text.includes('â€¢') || text.includes('\u2022')) {
      el.textContent = text.replace(/\s*(â€¢|\u2022)\s*/g, ' \u00B7 ');
    }
  }

  const nextApptTime = document.getElementById('nextApptTime');
  if (nextApptTime) {
    cleanSeparators(nextApptTime);
    new MutationObserver(() => cleanSeparators(nextApptTime)).observe(nextApptTime, {
      childList: true,
      characterData: true,
      subtree: true
    });
  }

  document.querySelectorAll('.mobile-triage-sub').forEach((el) => {
    el.innerHTML = el.innerHTML.replace(/â€¢|\u2022|&bull;/g, '&middot;');
  });

  // 3. Sync bottom nav Appointments badge with #sideApptCount
  const sideApptCount = document.getElementById('sideApptCount');
  const mobileApptCount = document.getElementById('mobileApptCount');

  function syncApptBadge() {
    if (!sideApptCount || !mobileApptCount) return;
    const count = parseInt(sideApptCount.textContent, 10) || 0;
    mobileApptCount.textContent = count > 99 ? '99+' : String(count);
    mobileApptCount.classList.toggle('is-empty', count <= 0);
  }

  if (sideApptCount && mobileApptCount) {
    syncApptBadge();
    new MutationObserver(syncApptBadge).observe(sideApptCount, {
      childList: true,
      characterData: true,
      subtree: true
    });
  }

  // 4. Format #notifBadge as 9+ when > 9
  const notifBadge = document.getElementById('notifBadge');
  let formattingNotif = false;
  if (notifBadge) {
    const formatNotif = () => {
      if (formattingNotif) return;
      const raw = (notifBadge.textContent || '').trim();
      const num = parseInt(raw, 10);
      if (!Number.isNaN(num) && num > 9 && raw !== '9+') {
        formattingNotif = true;
        notifBadge.textContent = '9+';
        formattingNotif = false;
      }
    };
    formatNotif();
    new MutationObserver(formatNotif).observe(notifBadge, {
      childList: true,
      characterData: true,
      subtree: true
    });
  }

  // 5. Compact "Past / Completed" tab label on mobile so 3 segments fit at 320px
  const btnApptPast = document.getElementById('btnApptPast');
  let updatingPastLabel = false;
  function compactPastTabLabel() {
    if (!btnApptPast || updatingPastLabel || window.innerWidth > 767) return;
    const raw = btnApptPast.textContent || '';
    if (raw.includes('/ Completed')) {
      updatingPastLabel = true;
      btnApptPast.textContent = raw.replace(/\s*\/\s*Completed/i, '');
      updatingPastLabel = false;
    }
  }
  if (btnApptPast) {
    compactPastTabLabel();
    new MutationObserver(compactPastTabLabel).observe(btnApptPast, {
      childList: true,
      characterData: true,
      subtree: true
    });
    window.addEventListener('resize', compactPastTabLabel);
  }

  // 6. Mirror Next Appointment status pill & clean row separators
  const dashUpcomingList = document.getElementById('dashUpcomingList');
  const fullApptsList = document.getElementById('fullApptsList');
  const mobileNextStatus = document.getElementById('mobileNextApptStatus');

  function syncDashboardLists() {
    if (dashUpcomingList) {
      dashUpcomingList.querySelectorAll('.appt-doc').forEach(cleanSeparators);
      if (mobileNextStatus) {
        const firstPill = dashUpcomingList.querySelector('.item-row .status-pill');
        mobileNextStatus.innerHTML = firstPill ? firstPill.outerHTML : '';
      }
    }
  }

  if (dashUpcomingList) {
    syncDashboardLists();
    new MutationObserver(syncDashboardLists).observe(dashUpcomingList, {
      childList: true,
      subtree: true
    });
  }

  if (fullApptsList) {
    const cleanFullList = () => {
      fullApptsList.querySelectorAll('.appt-doc').forEach(cleanSeparators);
    };
    cleanFullList();
    new MutationObserver(cleanFullList).observe(fullApptsList, {
      childList: true,
      subtree: true
    });
  }

  // 7. Sync mobile bottom nav active state & aria-current with switchView
  const mobileNavItems = document.querySelectorAll('.mobile-bottom-nav .mobile-nav-item');

  function updateMobileNavActive(viewName) {
    mobileNavItems.forEach((btn) => {
      const isMatch = btn.dataset.view === viewName;
      btn.classList.toggle('active', isMatch);
      if (isMatch) {
        btn.setAttribute('aria-current', 'page');
      } else {
        btn.removeAttribute('aria-current');
      }
    });
  }

  // 8. Mobile Sidebar Drawer Toggle
  const mobileMenuBtn = document.getElementById('mobileMenuBtn');
  const mobileSidebarBackdrop = document.getElementById('mobileSidebarBackdrop');

  function setMobileSidebarOpen(open) {
    document.body.classList.toggle('mobile-sidebar-open', open);
    if (mobileMenuBtn) {
      mobileMenuBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
  }

  if (mobileMenuBtn) {
    mobileMenuBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      setMobileSidebarOpen(!document.body.classList.contains('mobile-sidebar-open'));
    });
  }

  if (mobileSidebarBackdrop) {
    mobileSidebarBackdrop.addEventListener('click', () => setMobileSidebarOpen(false));
  }

  document.querySelectorAll('.sidebar .nav-link').forEach((link) => {
    link.addEventListener('click', () => {
      if (window.innerWidth <= 767) {
        setMobileSidebarOpen(false);
      }
    });
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && document.body.classList.contains('mobile-sidebar-open')) {
      setMobileSidebarOpen(false);
    }
  });

  window.addEventListener('resize', () => {
    if (window.innerWidth > 767 && document.body.classList.contains('mobile-sidebar-open')) {
      setMobileSidebarOpen(false);
    }
  });

  if (typeof window.switchView === 'function') {
    const originalSwitchView = window.switchView;
    window.switchView = function (viewName, element) {
      originalSwitchView(viewName, element);
      const resolved = viewName === 'medications' ? 'dashboard' : viewName;
      updateMobileNavActive(resolved);
      if (window.innerWidth <= 767) {
        setMobileSidebarOpen(false);
        window.scrollTo({ top: 0, behavior: 'auto' });
      }
    };
  }

  mobileNavItems.forEach((btn) => {
    btn.addEventListener('click', () => {
      const view = btn.dataset.view;
      if (typeof window.switchView === 'function') {
        window.switchView(view);
      }
      btn.blur();
    });
  });
});
