document.addEventListener('DOMContentLoaded', async () => {
  const urlParams = new URLSearchParams(window.location.search);
  const patientId = urlParams.get('id');

  if (!patientId) {
    document.getElementById('patientSubText').textContent = 'Error: No patient ID provided.';
    return;
  }

  const API_BASE = '/sdn/api';

  function escHtml(str) {
    if (!str) return '';
    return str.toString().replace(/[&<>'"]/g,
      tag => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        "'": '&#39;',
        '"': '&quot;'
      }[tag] || tag)
    );
  }

  function formatDateNice(iso) {
    if (!iso) return '—';
    const [y, m, d] = iso.split('-');
    if (!y || !m || !d) return iso;
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    return `${months[parseInt(m, 10) - 1]} ${parseInt(d, 10)}, ${y}`;
  }

  function showToast(msg, type = 'success') {
    if (typeof window.showToast === 'function') {
      window.showToast(msg, type);
    } else {
      alert(msg);
    }
  }

  // Fetch Patient Details for Header
  async function loadPatientDetails() {
    try {
      const res = await fetch(`${API_BASE}/patients.php?id=${encodeURIComponent(patientId)}`);
      const data = await res.json();
      if (data.success && data.data) {
        document.getElementById('patientSubText').textContent = `${data.data.full_name} • ${data.data.patient_id} • ${data.data.department}`;
      } else {
        document.getElementById('patientSubText').textContent = 'Patient details not found.';
      }
    } catch (e) {
      console.error(e);
      document.getElementById('patientSubText').textContent = 'Error loading patient details.';
    }
  }

  // Fetch Medical History
  async function loadHistory() {
    try {
      const res = await fetch(`${API_BASE}/patient_history.php?patient_id=${encodeURIComponent(patientId)}`);
      const data = await res.json();
      if (!data.success) return;

      const tbody = document.getElementById('historyList');
      const empty = document.getElementById('historyEmpty');

      if (!tbody) return;
      tbody.innerHTML = '';
      if (!data.data || !data.data.length) {
        empty.hidden = false;
        return;
      }
      empty.hidden = true;

      data.data.forEach(h => {
        const tr = document.createElement('tr');
        tr.style.borderBottom = '1px solid #f1f5f9';
        tr.style.fontSize = '0.9rem';
        tr.innerHTML = `
          <td style="padding:12px 10px;">${escHtml(formatDateNice(h.record_date))}</td>
          <td style="padding:12px 10px; font-weight:600; color:#1e293b;">${escHtml(h.diagnosis)}</td>
          <td style="padding:12px 10px;">${escHtml(h.treatment) || '-'}</td>
          <td style="padding:12px 10px;">${escHtml(h.prescription) || '-'}</td>
          <td style="padding:12px 10px;">${escHtml(h.recorded_by)}</td>
        `;
        tbody.appendChild(tr);
      });
    } catch (e) { console.error('Error loading history:', e); }
  }

  // Modal Handlers
  window.openHistoryModal = () => {
    document.getElementById('historyPatientId').value = patientId;
    document.getElementById('historyModal').hidden = false;
  };

  window.closeHistoryModal = () => {
    document.getElementById('historyModal').hidden = true;
    document.getElementById('addHistoryForm').reset();
  };

  // Add Record Submission
  document.getElementById('addHistoryForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = e.target.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" style="vertical-align:middle;margin-right:6px;" aria-hidden="true"><path d="M12 2a10 10 0 0 1 10 10" opacity="0.9"/><circle cx="12" cy="12" r="10" opacity="0.25"/><animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="0.75s" repeatCount="indefinite"/></svg>Saving...';

    // Get today's date for record_date since I removed it from the simpler form
    const today = new Date().toISOString().split('T')[0];

    const payload = {
      patient_id: document.getElementById('historyPatientId').value,
      record_date: today,
      diagnosis: document.getElementById('historyDiagnosis').value,
      treatment: document.getElementById('historyTreatment').value,
      prescription: document.getElementById('historyPrescription').value,
      remarks: '' // Omitted for minimalist UI
    };

    try {
      const res = await fetch(`${API_BASE}/patient_history.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (!data.success) throw new Error(data.error);

      showToast('Record saved successfully!');
      closeHistoryModal();
      loadHistory();

      // We don't load notes here because notes are on the profile page
    } catch (err) {
      showToast(err.message || 'Error saving record', 'error');
    } finally {
      btn.disabled = false;
      btn.textContent = 'Save Record';
    }
  });

  window.downloadHistoryPDF = () => {
    const el = document.getElementById('historyCard');
    const opt = {
      margin: 0.5,
      filename: `Medical_History_${patientId}.pdf`,
      image: { type: 'jpeg', quality: 0.98 },
      html2canvas: { scale: 2 },
      jsPDF: { unit: 'in', format: 'letter', orientation: 'landscape' }
    };
    html2pdf().set(opt).from(el).save();
  };

  // Init
  await loadPatientDetails();
  await loadHistory();
});
