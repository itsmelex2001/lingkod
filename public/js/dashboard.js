// dashboard.js — Admin Patient Management (fetches from PHP API)

document.addEventListener("DOMContentLoaded", async () => {
  // ── Auth guard (admin only) ─────────────────────────────────────────────
  const session = await requireLogin("admin");
  if (!session) return;

  // ── DOM refs ────────────────────────────────────────────────────────────
  const patientBody = document.getElementById("patientBody");
  const searchInput = document.getElementById("searchInput");
  const filterBtns = document.querySelectorAll(".filter-btn");
  const rowCountEl = document.getElementById("rowCount");
  const emptyState = document.getElementById("emptyState");
  const exportBtn = document.getElementById("exportBtn");
  const confirmModal = document.getElementById("confirmModal");
  const modalTitle = document.getElementById("modalTitle");
  const modalBody = document.getElementById("modalBody");
  const modalIcon = document.getElementById("modalIcon");
  const modalConfirm = document.getElementById("modalConfirm");
  const modalCancel = document.getElementById("modalCancel");

  let currentFilter = "all";
  let pendingAction = null;
  let allPatients = [];

  // ── Load patients from API ──────────────────────────────────────────────
  async function loadPatients() {
    try {
      const res = await fetch(`${API_BASE}/patients.php`);
      const data = await res.json();
      if (!data.success) throw new Error(data.message);
      allPatients = data.data;
      renderPatients(allPatients);
      setUpdatedTime();
    } catch (err) {
      console.error("Failed to load patients:", err);
      showToast("Could not load patients. Check your connection.", "error");
    }
  }

  // ── Render rows ─────────────────────────────────────────────────────────
  function renderPatients(patients) {
    if (!patientBody) return;
    patientBody.innerHTML = "";

    if (!patients.length) {
      if (emptyState) emptyState.hidden = false;
      updateRowCount(0);
      refreshStats([]);
      return;
    }

    patients.forEach((p) => {
      const initials = p.full_name
        .split(" ")
        .map((w) => w[0])
        .slice(0, 2)
        .join("")
        .toUpperCase();
      const avatarClass = `av-${["a", "b", "c", "d", "e"][Math.floor(Math.random() * 5)]}`;

      const typeBadge =
        p.patient_type === "first-time"
          ? '<span class="type-badge first-time">First Time</span>'
          : '<span class="type-badge returning">Returning</span>';

      const statusBadge = `<span class="status-badge ${p.status}">
        <span class="status-dot"></span>${capitalize(p.status)}
      </span>`;

      let actionBtns = "";
      if (p.status === "pending") {
        actionBtns = `<button class="btn-action approve-btn" data-id="${p.patient_id}" data-name="${escHtml(p.full_name)}">Approve</button>
                      <button class="btn-action reject-btn"  data-id="${p.patient_id}" data-name="${escHtml(p.full_name)}">Reject</button>`;
      } else if (p.status === "approved") {
        actionBtns = `<button class="btn-action manage-btn" data-id="${p.patient_id}" data-name="${escHtml(p.full_name)}">Manage</button>`;
      } else {
        actionBtns = `<span style="font-size:.8rem;color:var(--muted);font-style:italic;">—</span>`;
      }

      const tr = document.createElement("tr");
      tr.dataset.id = p.patient_id;
      tr.dataset.status = p.status;
      tr.innerHTML = `
        <td data-label="Patient ID"><span class="patient-id">${p.patient_id}</span></td>
        <td data-label="Name">
          <div class="patient-cell">
            <div class="avatar ${avatarClass}">${initials}</div>
            <span class="patient-name">${escHtml(p.full_name)}</span>
          </div>
        </td>
        <td data-label="Department"><span class="dept-tag">${escHtml(p.department || "—")}</span></td>
        <td data-label="Type">${typeBadge}</td>
        <td data-label="Status">${statusBadge}</td>
        <td data-label="Action"><div class="action-group">${actionBtns}</div></td>`;
      patientBody.appendChild(tr);
    });

    if (emptyState) emptyState.hidden = true;
    applyFilters();
    refreshStats(patients);
  }

  // ── Refresh stat cards ──────────────────────────────────────────────────
  function refreshStats(patients) {
    animateStatTo("statTotal", patients.length);
    animateStatTo(
      "statPending",
      patients.filter((p) => p.status === "pending").length,
    );
    animateStatTo(
      "statApproved",
      patients.filter((p) => p.status === "approved").length,
    );
    animateStatTo(
      "statRejected",
      patients.filter((p) => p.status === "rejected").length,
    );
  }

  // ── Filter & search ─────────────────────────────────────────────────────
  function applyFilters() {
    const query = searchInput ? searchInput.value.toLowerCase().trim() : "";
    const rows = patientBody
      ? Array.from(patientBody.querySelectorAll("tr"))
      : [];
    let visible = 0;

    rows.forEach((row) => {
      const matchFilter =
        currentFilter === "all" || row.dataset.status === currentFilter;
      const matchSearch = !query || row.innerText.toLowerCase().includes(query);
      row.classList.toggle("row-hidden", !(matchFilter && matchSearch));
      if (matchFilter && matchSearch) visible++;
    });

    if (emptyState) emptyState.hidden = visible > 0;
    updateRowCount(visible);
  }

  function updateRowCount(n) {
    if (rowCountEl)
      rowCountEl.innerHTML = `Showing <strong>${n}</strong> patient${n !== 1 ? "s" : ""}`;
  }

  // ── Event: search ───────────────────────────────────────────────────────
  if (searchInput) searchInput.addEventListener("input", applyFilters);

  // ── Event: filter tabs ──────────────────────────────────────────────────
  filterBtns.forEach((btn) => {
    btn.addEventListener("click", () => {
      filterBtns.forEach((b) => b.classList.remove("active"));
      btn.classList.add("active");
      currentFilter = btn.dataset.filter;
      applyFilters();
    });
  });

  // ── Event: export ───────────────────────────────────────────────────────
  if (exportBtn) {
    exportBtn.addEventListener("click", () => {
      const table = document.getElementById("patientTable");
      if (table) {
        exportTableToCSV(table, "patients.csv");
        showToast("Exported as CSV.");
      }
    });
  }

  // ── Event: table action buttons (delegation) ────────────────────────────
  if (patientBody) {
    patientBody.addEventListener("click", (e) => {
      const btn = e.target.closest(".btn-action");
      if (!btn) return;
      const id = btn.dataset.id;
      const name = btn.dataset.name;

      if (btn.classList.contains("approve-btn")) {
        openModal({
          type: "approve",
          id,
          name,
          title: "Approve Patient",
          body: `Approve registration for <strong>${name}</strong> (${id})?`,
          confirmLabel: "Approve",
          confirmClass: "",
        });
      } else if (btn.classList.contains("reject-btn")) {
        openModal({
          type: "reject",
          id,
          name,
          title: "Reject Registration",
          body: `Reject <strong>${name}</strong>? This cannot be undone.`,
          confirmLabel: "Reject",
          confirmClass: "danger",
        });
      } else if (btn.classList.contains("manage-btn")) {
        window.location.href = `/sdn/pages/patient-profile.php?id=${encodeURIComponent(id)}`;
      }
    });
  }

  // ── Modal ────────────────────────────────────────────────────────────────
  function openModal({
    type,
    id,
    name,
    title,
    body,
    confirmLabel,
    confirmClass,
  }) {
    pendingAction = { type, id, name };
    if (modalTitle) modalTitle.textContent = title;
    if (modalBody) modalBody.innerHTML = body;
    if (modalConfirm) {
      modalConfirm.textContent = confirmLabel;
      modalConfirm.className =
        "btn-modal-confirm" + (confirmClass ? ` ${confirmClass}` : "");
    }
    if (modalIcon) {
      modalIcon.className =
        "modal-icon" + (type === "reject" ? " danger-icon" : "");
      modalIcon.innerHTML =
        type === "reject"
          ? `<svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/><path d="M9 9l6 6m0-6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>`
          : `<svg viewBox="0 0 24 24" fill="none"><path d="M20 7L9 18l-5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>`;
    }
    if (confirmModal) {
      confirmModal.hidden = false;
      modalConfirm?.focus();
    }
  }

  function closeModal() {
    if (confirmModal) confirmModal.hidden = true;
    pendingAction = null;
  }

  if (modalCancel) modalCancel.addEventListener("click", closeModal);
  if (confirmModal)
    confirmModal.addEventListener("click", (e) => {
      if (e.target === confirmModal) closeModal();
    });
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") closeModal();
  });

  // ── Confirm modal action ────────────────────────────────────────────────
  if (modalConfirm) {
    modalConfirm.addEventListener("click", async () => {
      if (!pendingAction) return;
      const { type, id, name } = pendingAction;
      modalConfirm.disabled = true;
      modalConfirm.textContent = "Processing…";

      try {
        const res = await fetch(`${API_BASE}/patients.php`, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ action: type, patient_id: id }),
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);

        // Update the row in-place
        const row = patientBody?.querySelector(`tr[data-id="${id}"]`);
        if (row) {
          const newStatus = type === "approve" ? "approved" : "rejected";
          row.dataset.status = newStatus;

          const badge = row.querySelector(".status-badge");
          if (badge) {
            badge.className = `status-badge ${newStatus}`;
            badge.innerHTML = `<span class="status-dot"></span>${capitalize(newStatus)}`;
          }

          const actionGroup = row.querySelector(".action-group");
          if (actionGroup) {
            actionGroup.innerHTML =
              type === "approve"
                ? `<button class="btn-action manage-btn" data-id="${id}" data-name="${escHtml(name)}">Manage</button>`
                : `<span style="font-size:.8rem;color:var(--muted);font-style:italic;">—</span>`;
          }
        }

        // Update local cache
        const cached = allPatients.find((p) => p.patient_id === id);
        if (cached)
          cached.status = type === "approve" ? "approved" : "rejected";

        closeModal();
        showToast(data.message, "success");
        applyFilters();
        refreshStats(allPatients);
        setUpdatedTime();
      } catch (err) {
        showToast(err.message || "Action failed.", "error");
        modalConfirm.disabled = false;
        modalConfirm.textContent =
          pendingAction?.type === "approve" ? "Approve" : "Reject";
      }
    });
  }

  // ── Helpers ─────────────────────────────────────────────────────────────
  function capitalize(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
  }
  function escHtml(str) {
    return String(str)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  // ── Init ─────────────────────────────────────────────────────────────────
  await loadPatients();
});
