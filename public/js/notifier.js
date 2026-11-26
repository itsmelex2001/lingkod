/**
 * Unified Lingkod Notifier — replaces native alert() and provides window.showToast()
 */

class Notifier {
  static init() {
    let container = document.getElementById('custom-notifier-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'custom-notifier-container';
      container.setAttribute('aria-live', 'polite');
      document.body.appendChild(container);
    }
    return container;
  }

  static normalizeVariant(message, type) {
    const raw = String(type || '').toLowerCase();
    if (raw === 'error' || raw === 'danger') return 'error';
    if (raw === 'warning' || raw === 'warn') return 'warning';
    if (raw === 'info' || raw === 'muted') return 'info';
    if (raw === 'success') return 'success';
    const lower = String(message || '').toLowerCase();
    if (lower.includes('error') || lower.includes('fail') || lower.includes('invalid')) return 'error';
    return 'success';
  }

  static show(message, type = 'success', customTitle = null) {
    const container = this.init();
    while (container.children.length >= 3) {
      container.firstElementChild.remove();
    }

    const variant = this.normalizeVariant(message, type);
    const titles = {
      success: 'Success',
      error: 'Action Failed',
      warning: 'Attention',
      info: 'Notice'
    };
    const icons = {
      success: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>',
      error: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
      warning: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
      info: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>'
    };

    const toast = document.createElement('div');
    toast.className = `custom-toast is-${variant}`;
    toast.setAttribute('role', variant === 'error' ? 'alert' : 'status');

    const iconEl = document.createElement('div');
    iconEl.className = `custom-toast-icon ${variant}`;
    iconEl.setAttribute('aria-hidden', 'true');
    iconEl.innerHTML = icons[variant];

    const contentEl = document.createElement('div');
    contentEl.className = 'custom-toast-content';

    const titleEl = document.createElement('h4');
    titleEl.className = 'custom-toast-title';
    titleEl.textContent = customTitle || titles[variant];

    const msgEl = document.createElement('p');
    msgEl.className = 'custom-toast-msg';
    msgEl.textContent = String(message ?? '');

    contentEl.append(titleEl, msgEl);

    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'custom-toast-close';
    closeBtn.setAttribute('aria-label', 'Dismiss notification');
    closeBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';

    const progressEl = document.createElement('div');
    progressEl.className = 'custom-toast-progress';
    progressEl.setAttribute('aria-hidden', 'true');

    toast.append(iconEl, contentEl, closeBtn, progressEl);

    let timer = null;
    const dismiss = () => {
      if (timer) {
        clearTimeout(timer);
        timer = null;
      }
      if (!toast.parentElement) return;
      toast.classList.remove('show');
      toast.classList.add('hide');
      setTimeout(() => {
        if (toast.parentElement) toast.remove();
      }, 170);
    };

    closeBtn.addEventListener('click', dismiss);
    toast.addEventListener('mouseenter', () => {
      if (timer) {
        clearTimeout(timer);
        timer = null;
      }
    });
    toast.addEventListener('mouseleave', () => {
      if (toast.parentElement && toast.classList.contains('show')) {
        timer = setTimeout(dismiss, 1800);
      }
    });

    container.appendChild(toast);
    void toast.offsetWidth;
    toast.classList.add('show');
    timer = setTimeout(dismiss, 3800);
  }
}

window.alert = function (message) {
  Notifier.show(message);
};

window.showToast = function (message, type = 'success', title = null) {
  Notifier.show(message, type, title);
};
