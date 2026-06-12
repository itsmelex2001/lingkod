/**
 * Custom Notifier to replace native alert() and confirm() dialogs
 */

class Notifier {
  static init() {
    if (!document.getElementById('custom-notifier-container')) {
      const container = document.createElement('div');
      container.id = 'custom-notifier-container';
      document.body.appendChild(container);
    }
  }

  static show(message, type = 'info') {
    this.init();
    const container = document.getElementById('custom-notifier-container');

    const toast = document.createElement('div');
    toast.className = 'custom-toast';
    if (type === 'error') toast.style.borderLeftColor = '#d32f2f';

    const isError = type === 'error' || message.toLowerCase().includes('error') || message.toLowerCase().includes('fail');
    const iconClass = isError ? 'error' : '';
    const title = isError ? 'System Alert' : 'Notification';
    
    // Update border color if it's an error string even if type was info
    if (isError) {
      toast.style.borderLeftColor = '#d32f2f';
    }

    const svgIcon = isError 
      ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>'
      : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>';

    toast.innerHTML = `
      <div class="custom-toast-icon ${iconClass}">
        ${svgIcon}
      </div>
      <div class="custom-toast-content">
        <h4 class="custom-toast-title">${title}</h4>
        <p class="custom-toast-msg">${message}</p>
      </div>
      <button class="custom-toast-close" onclick="this.parentElement.classList.replace('show', 'hide'); setTimeout(() => this.parentElement.remove(), 400)">&times;</button>
    `;

    container.appendChild(toast);

    // Trigger reflow for animation
    void toast.offsetWidth;
    toast.classList.add('show');

    // Auto dismiss
    setTimeout(() => {
      if (toast.parentElement) {
        toast.classList.replace('show', 'hide');
        setTimeout(() => {
          if (toast.parentElement) toast.remove();
        }, 400);
      }
    }, 4000);
  }
}

// Override native alert globally
window.alert = function(message) {
  Notifier.show(message);
};

window.showToast = function(message, type = 'info') {
  Notifier.show(message, type);
};
