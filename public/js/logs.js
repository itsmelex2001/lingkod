// logs.js — System Logs page (fetches from PHP API)

document.addEventListener('DOMContentLoaded', async () => {

  // ── Auth guard ──────────────────────────────────────────────────────────
  const session = await requireLogin('admin');
  if (!session) return;

  // ── DOM refs ────────────────────────────────────────────────────────────
  const logsBody    = document.getElementById('logsBody');
  const searchInput = document.getElementById('searchInput');
  const filterBtns  = document.querySelectorAll('.filter-btn');
  const rowCountEl  = document.getElementById('rowCount');
  const emptyState  = document.getElementById('emptyState');
  const exportBtn   = document.getElementById('exportBtn');
  const clearBtn    = document.getElementById('clearLogsBtn');

  let currentFilter = 'all';
  let allLogs       = [];

  // ── Load logs from API ──────────────────────────────────────────────────
  async function loadLogs() {
    try {
      const res  = await fetch(`${API_BASE}/logs.php`);
      const data = await res.json();
      if (!data.success) throw new Error(data.message);
      allLogs = data.data;
      renderLogs(allLogs);
      setUpdatedTime();
    } catch (err) {
      console.error('Failed to load logs:', err);
      showToast('Could not load logs.', 'error');
    }
  }

  // ── Render rows ─────────────────────────────────────────────────────────
  function renderLogs(logs) {
    if (!logsBody) return;
    logsBody.innerHTML = '';

    if (!logs.length) {
      if (emptyState) emptyState.hidden = false;
      updateRowCount(0);
      refreshStats([]);
      return;
    }

    logs.forEach(log => {
      const tr = document.createElement('tr');
      tr.dataset.level = log.level;
      tr.innerHTML = `
        <td data-label="Level"><span class="log-badge log-${log.level}">${capitalize(log.level)}</span></td>
        <td data-label="Timestamp"><span class="log-time">${escHtml(log.timestamp)}</span></td>
        <td data-label="Action">${escHtml(log.action)}</td>
        <td data-label="User"><span class="log-user">${escHtml(log.user)}</span></td>
        <td data-label="Details">${escHtml(log.details)}</td>`;
      logsBody.appendChild(tr);
    });

    if (emptyState) emptyState.hidden = true;
    applyFilters();
    refreshStats(logs);
  }

  // ── Stat cards ──────────────────────────────────────────────────────────
  function refreshStats(logs) {
    animateStatTo('statTotal',   logs.length);
    animateStatTo('statInfo',    logs.filter(l => l.level === 'info').length);
    animateStatTo('statWarning', logs.filter(l => l.level === 'warning').length);
    animateStatTo('statError',   logs.filter(l => l.level === 'error').length);
  }

  // ── Filter & search ─────────────────────────────────────────────────────
  function applyFilters() {
    const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
    const rows  = logsBody ? Array.from(logsBody.querySelectorAll('tr')) : [];
    let visible = 0;

    rows.forEach(row => {
      const matchFilter = currentFilter === 'all' || row.dataset.level === currentFilter;
      const matchSearch = !query || row.innerText.toLowerCase().includes(query);
      row.classList.toggle('row-hidden', !(matchFilter && matchSearch));
      if (matchFilter && matchSearch) visible++;
    });

    if (emptyState) emptyState.hidden = visible > 0;
    updateRowCount(visible);
  }

  function updateRowCount(n) {
    if (rowCountEl) rowCountEl.innerHTML = `Showing <strong>${n}</strong> event${n !== 1 ? 's' : ''}`;
  }

  // ── Events ──────────────────────────────────────────────────────────────
  if (searchInput) searchInput.addEventListener('input', applyFilters);

  filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      filterBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      currentFilter = btn.dataset.filter;
      applyFilters();
    });
  });

  // Export CSV (direct download via API)
  if (exportBtn) {
    exportBtn.addEventListener('click', () => {
      window.open(`${API_BASE}/logs.php?action=export`, '_blank');
    });
  }

  // Clear all logs
  if (clearBtn) {
    clearBtn.addEventListener('click', async () => {
      if (!confirm('Clear all system logs? This cannot be undone.')) return;
      try {
        const res  = await fetch(`${API_BASE}/logs.php`, {
          method:  'POST',
          headers: { 'Content-Type': 'application/json' },
          body:    JSON.stringify({ action: 'clear' }),
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        showToast('All logs cleared.', 'info');
        await loadLogs(); // Reload — API inserts one "Logs Cleared" entry
      } catch (err) {
        showToast(err.message || 'Failed to clear logs.', 'error');
      }
    });
  }

  // ── Helpers ─────────────────────────────────────────────────────────────
  function capitalize(str) { return str.charAt(0).toUpperCase() + str.slice(1); }
  function escHtml(str) {
    return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }

  // ── Init ─────────────────────────────────────────────────────────────────
  await loadLogs();
});
