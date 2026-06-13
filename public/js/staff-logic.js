// staff-logic.js — Patient Records & Scheduling (fetches from PHP API)

document.addEventListener("DOMContentLoaded", async () => {
  // ── Auth guard (check login only; role enforcement is server-side) ────
  const session = await requireLogin();
  if (!session) return;

  // Additional check: verify we have a valid session
  if (!session.user) {
    alert("Session error: Please log in again.");
    window.location.href = "/pages/login.php";
    return;
  }

  // ── DOM refs ────────────────────────────────────────────────────────────
  const recordsBody = document.getElementById("recordsBody");
  const modal = document.getElementById("actionModal");
  const viewSection = document.getElementById("viewSection");
  const updateSection = document.getElementById("updateSection");
  const assignSection = document.getElementById("assignSection");
  const remindSection = document.getElementById("remindSection");
  const saveBtn = document.getElementById("saveBtn");
  const mismatchBanner = document.getElementById("mismatchBanner");

  let activeRow = null;
  let activeRecord = null; // { patient_id, full_name, pref_date_raw, assigned_date }

  // ── Load records from API ───────────────────────────────────────────────
  async function loadRecords() {
    try {
      const res = await fetch(`${API_BASE}/staff.php`);
      const data = await res.json();
      if (!data.success) throw new Error(data.message);
      renderRecords(data.data);
      updateRowCount(data.data.length);
    } catch (err) {
      console.error("Failed to load records:", err);
      showToast("Could not load patient records.", "error");
    }
  }

  // ── Render rows ─────────────────────────────────────────────────────────
  function renderRecords(records) {
    if (!recordsBody) return;
    recordsBody.innerHTML = "";

    // FRONTEND GUARD: only render approved patients.
    // The backend already filters, but this acts as a safety net in case
    // a cached or unexpected response includes rejected/pending records.
    const approved = records.filter(
      (r) => !r.status || r.status === "approved",
    );

    approved.forEach((r) => {
      const tr = document.createElement("tr");
      tr.dataset.id = r.patient_id;
      tr.dataset.prefdate = r.pref_date_raw || "";
      tr.dataset.assigneddate = r.assigned_date || "";
      tr.innerHTML = `
        <td data-label="Record ID">${escHtml(r.patient_id)}</td>
        <td data-label="Name">${escHtml(r.full_name)}</td>
        <td data-label="Pref. Date">${escHtml(r.pref_date)}</td>
        <td data-label="Diagnosis">${escHtml(r.latest_note || "—")}</td>
        <td data-label="Attending">${escHtml(r.assigned_doctor)}</td>
        <td data-label="Action">
          <div class="action-group">
            <button class="btn-action view-btn">History</button>
            <button class="btn-action update-btn">Update</button>
            <button class="btn-action assign-btn">Assign</button>
            <button class="btn-action remind-btn">Remind</button>
          </div>
        </td>`;
      recordsBody.appendChild(tr);
    });

    // Update the row count to reflect only approved records shown
    updateRowCount(approved.length);
  }

  function updateRowCount(n) {
    const el = document.getElementById("rowCount");
    if (el)
      el.innerHTML = `Showing <strong>${n}</strong> record${n !== 1 ? "s" : ""}`;
  }

  // ── Helper: hide all modal sections ────────────────────────────────────
  function hideAllSections() {
    viewSection.style.display = "none";
    updateSection.style.display = "none";
    assignSection.style.display = "none";
    remindSection.style.display = "none";
    if (mismatchBanner) mismatchBanner.style.display = "none";
  }

  function openModal() {
    modal.style.display = "flex";
  }
  function closeModal() {
    modal.style.display = "none";
  }

  // ── Table button delegation ─────────────────────────────────────────────
  if (recordsBody) {
    recordsBody.addEventListener("click", async (e) => {
      const btn = e.target;
      activeRow = btn.closest("tr");
      if (!activeRow) return;

      const id = activeRow.dataset.id;
      const name = activeRow.cells[1].innerText.trim();
      activeRecord = {
        patient_id: id,
        full_name: name,
        pref_date_raw: activeRow.dataset.prefdate,
        assigned_date: activeRow.dataset.assigneddate,
      };

      document.getElementById("modalPatientInfo").innerText = `${name} | ${id}`;

      // ── HISTORY ───────────────────────────────────────────────────────
      if (btn.classList.contains("view-btn")) {
        document.getElementById("modalTitle").innerText =
          "Consultation History";
        document.getElementById("historyContent").innerHTML =
          '<em style="color:var(--muted)">Loading…</em>';
        hideAllSections();
        viewSection.style.display = "block";
        saveBtn.style.display = "none";
        openModal();

        try {
          const res = await fetch(`${API_BASE}/staff.php?patient_id=${id}`);
          const data = await res.json();
          const notes = data.data || [];
          document.getElementById("historyContent").innerHTML = notes.length
            ? notes
                .map(
                  (n, i) => `
                <div style="margin-bottom:10px;padding-bottom:10px;${i < notes.length - 1 ? "border-bottom:1px solid var(--border-color);" : ""}">
                  <small style="color:#888;font-weight:600;">${escHtml(n.created_at)}</small><br>
                  <span style="font-size:.9rem;">${escHtml(n.note)}</span>
                </div>`,
                )
                .join("")
            : '<em style="color:var(--muted)">No records found.</em>';
        } catch {
          document.getElementById("historyContent").innerHTML =
            '<em style="color:red">Failed to load notes.</em>';
        }
      }

      // ── UPDATE ────────────────────────────────────────────────────────
      if (btn.classList.contains("update-btn")) {
        document.getElementById("modalTitle").innerText =
          "Update Progress Note";
        document.getElementById("progressInput").value = "";
        hideAllSections();
        updateSection.style.display = "block";
        saveBtn.innerText = "Save Progress";
        saveBtn.dataset.action = "update";
        saveBtn.style.display = "block";
        openModal();
      }

      // ── ASSIGN ────────────────────────────────────────────────────────
      if (btn.classList.contains("assign-btn")) {
        document.getElementById("modalTitle").innerText = "Assign Doctor";
        document.getElementById("prefDate").value = activeRecord.pref_date_raw;
        document.getElementById("assignedDate").value =
          activeRecord.assigned_date;
        document.getElementById("doctorSelect").value = "";
        hideAllSections();
        assignSection.style.display = "block";
        saveBtn.innerText = "Confirm Assignment";
        saveBtn.dataset.action = "assign";
        saveBtn.style.display = "block";
        openModal();
      }

      // ── REMIND ────────────────────────────────────────────────────────
      if (btn.classList.contains("remind-btn")) {
        document.getElementById("modalTitle").innerText =
          "Set Patient Reminder";
        const dispField = document.getElementById("remindDateDisplay");
        dispField.value = activeRecord.assigned_date
          ? new Date(activeRecord.assigned_date + "T00:00").toLocaleDateString(
              "en-PH",
              { year: "numeric", month: "long", day: "numeric" },
            )
          : "Pending Doctor Assignment";
        hideAllSections();
        remindSection.style.display = "block";
        saveBtn.innerText = "Schedule Reminder";
        saveBtn.dataset.action = "remind";
        saveBtn.style.display = "block";
        openModal();
      }
    });
  }

  // ── Doctor select → auto-fill date & mismatch warning ──────────────────
  const doctorSelect = document.getElementById("doctorSelect");
  if (doctorSelect) {
    doctorSelect.addEventListener("change", (e) => {
      const opt = e.target.options[e.target.selectedIndex];
      document.getElementById("assignedDate").value = opt.dataset.avail || "";
      if (mismatchBanner && opt.dataset.avail && activeRecord) {
        const pref = activeRecord.pref_date_raw;
        const assigned = opt.dataset.avail;
        const method = document.getElementById("notifyMethod").value;
        if (pref && pref !== assigned) {
          const fmt = (d) =>
            new Date(d + "T00:00").toLocaleDateString("en-PH", {
              month: "long",
              day: "numeric",
              year: "numeric",
            });
          mismatchBanner.style.display = "block";
          mismatchBanner.innerHTML = `<strong>⚠ Schedule mismatch detected.</strong><br>
            Patient preferred <strong>${fmt(pref)}</strong> —
            doctor available on <strong>${fmt(assigned)}</strong>.<br>
            Saving will notify the patient via <em>${method}</em>.`;
        } else {
          mismatchBanner.style.display = "none";
        }
      }
    });
  }

  document.getElementById("notifyMethod")?.addEventListener("change", () => {
    doctorSelect?.dispatchEvent(new Event("change"));
  });

  // ── Save button ─────────────────────────────────────────────────────────
  saveBtn?.addEventListener("click", async () => {
    const actionType = saveBtn.dataset.action;
    const id = activeRecord?.patient_id;
    if (!id) return;

    saveBtn.disabled = true;
    saveBtn.textContent = "Saving…";

    let payload = { action: actionType, patient_id: id };

    if (actionType === "update") {
      const note = document.getElementById("progressInput").value.trim();
      if (!note) {
        showToast("Please enter a progress note.", "error");
        resetSaveBtn();
        return;
      }
      payload.note = note;
    } else if (actionType === "assign") {
      const doctor = document.getElementById("doctorSelect").value;
      const assignedDate = document.getElementById("assignedDate").value;
      const notifyMethod = document.getElementById("notifyMethod").value;
      if (!doctor || !assignedDate) {
        showToast("Please select a doctor.", "error");
        resetSaveBtn();
        return;
      }
      payload = {
        ...payload,
        doctor,
        assigned_date: assignedDate,
        notify_method: notifyMethod,
      };
    } else if (actionType === "remind") {
      payload.interval = document.getElementById("remindInterval").value;
      payload.method = document.getElementById("remindMethod").value;
    }

    try {
      const res = await fetch(`${API_BASE}/staff.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
      const data = await res.json();
      if (!data.success) throw new Error(data.message);

      // Update table row for assign action
      if (actionType === "assign" && activeRow) {
        activeRow.cells[4].innerText = payload.doctor;
        activeRow.dataset.assigneddate = payload.assigned_date;
      }
      if (actionType === "update" && activeRow) {
        activeRow.cells[3].innerText = document
          .getElementById("progressInput")
          .value.trim();
      }

      closeModal();
      showToast(data.message, "success");
    } catch (err) {
      showToast(err.message || "Action failed.", "error");
    } finally {
      resetSaveBtn();
    }
  });

  function resetSaveBtn() {
    if (!saveBtn) return;
    saveBtn.disabled = false;
    const labels = {
      update: "Save Progress",
      assign: "Confirm Assignment",
      remind: "Schedule Reminder",
    };
    saveBtn.textContent = labels[saveBtn.dataset.action] || "Save";
  }

  // ── Close modal ─────────────────────────────────────────────────────────
  document.getElementById("closeModal")?.addEventListener("click", closeModal);
  modal?.addEventListener("click", (e) => {
    if (e.target === modal) closeModal();
  });
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && modal?.style.display === "flex") closeModal();
  });

  // ── Staff search ─────────────────────────────────────────────────────────
  document
    .getElementById("staffSearch")
    ?.addEventListener("input", function () {
      const q = this.value.toLowerCase().trim();
      recordsBody?.querySelectorAll("tr").forEach((row) => {
        row.classList.toggle(
          "row-hidden",
          q !== "" && !row.innerText.toLowerCase().includes(q),
        );
      });
    });

  // ── Helpers ─────────────────────────────────────────────────────────────
  function escHtml(str) {
    return String(str ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;");
  }

  // ── Init ─────────────────────────────────────────────────────────────────
  await loadRecords();
});
