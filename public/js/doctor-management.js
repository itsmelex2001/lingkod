// doctor-management.js

let allDoctors = [];
let currentFilter = "all";
let doctorModal = null;
let doctorDetailsModal = null;
let confirmModal = null;

document.addEventListener("DOMContentLoaded", () => {
  doctorModal = document.getElementById("doctorModal");
  doctorDetailsModal = document.getElementById("doctorDetailsModal");
  confirmModal = document.getElementById("confirmModal");

  // ── Load doctor data ─────────────────────────────────────────────────────
  loadDoctors();

  // ── Event listeners ──────────────────────────────────────────────────────
  document
    .getElementById("addDoctorBtn")
    .addEventListener("click", openAddDoctorModal);
  document
    .getElementById("doctorModalCancel")
    .addEventListener("click", closeDoctorModal);
  document
    .getElementById("doctorForm")
    .addEventListener("submit", handleCreateDoctor);
  document
    .getElementById("confirmCancel")
    .addEventListener("click", closeConfirmModal);
  document
    .getElementById("detailsClose")
    .addEventListener("click", closeDoctorDetailsModal);

  // Search and filter
  document
    .getElementById("searchInput")
    .addEventListener("input", filterAndDisplay);
  document.querySelectorAll(".filter-btn").forEach((btn) => {
    btn.addEventListener("click", (e) => {
      document
        .querySelectorAll(".filter-btn")
        .forEach((b) => b.classList.remove("active"));
      e.target.classList.add("active");
      currentFilter = e.target.dataset.filter;
      filterAndDisplay();
    });
  });

  // Update time
  setInterval(updateLastRefresh, 1000);
});

// ── Load doctors list ────────────────────────────────────────────────────────
async function loadDoctors() {
  try {
    const response = await fetch(`${API_BASE}/doctors.php?mode=manage`);
    const data = await response.json();

    if (data.data) {
      allDoctors = data.data;
      updateStats();
      filterAndDisplay();
      showToast("Doctor list loaded.");
    }
  } catch (error) {
    console.error("Failed to load doctors:", error);
  }
}

// ── Filter and display doctors ───────────────────────────────────────────────
function filterAndDisplay() {
  const searchTerm = document.getElementById("searchInput").value.toLowerCase();

  let filtered = allDoctors.filter((d) => {
    const matchesSearch =
      d.name.toLowerCase().includes(searchTerm) ||
      d.specialization.toLowerCase().includes(searchTerm) ||
      d.department.toLowerCase().includes(searchTerm);
    const matchesFilter = currentFilter === "all" || d.status === currentFilter;
    return matchesSearch && matchesFilter;
  });

  const tbody = document.getElementById("doctorBody");
  const emptyState = document.getElementById("emptyState");

  if (filtered.length === 0) {
    tbody.innerHTML = "";
    emptyState.hidden = false;
    document.getElementById("rowCount").innerHTML =
      "Showing <strong>0</strong> doctors";
    return;
  }

  emptyState.hidden = true;
  tbody.innerHTML = filtered
    .map(
      (doctor) => `
    <tr data-id="${doctor.id}" data-status="${doctor.status}">
      <td data-label="Doctor Name">
        <span class="patient-name" style="font-weight: 500;">${htmlEscape(doctor.name)}</span>
      </td>
      <td data-label="Specialization">
        <span style="color: var(--text-soft); font-size: 0.9rem;">${htmlEscape(doctor.specialization)}</span>
      </td>
      <td data-label="Department">
        <span class="dept-tag">${capitalizeFirst(doctor.department)}</span>
      </td>
      <td data-label="Schedule">
        <span style="color: var(--muted); font-size: 0.9rem;">
          ${doctor.schedule_start ? `${doctor.schedule_start} - ${doctor.schedule_end}` : "Not Set"}
        </span>
      </td>
      <td data-label="Slots">
        <div style="display: flex; align-items: center; gap: 8px;">
          <div style="flex: 1; background: #eef5f1; height: 6px; border-radius: 3px; overflow: hidden;">
            <div style="background: var(--primary-green); height: 100%; width: ${doctor.fill_percent}%;" title="${doctor.booked_slots}/${doctor.total_slots}"></div>
          </div>
          <span style="font-size: 0.85rem; color: var(--muted); min-width: 40px;">${doctor.booked_slots}/${doctor.total_slots}</span>
        </div>
      </td>
      <td data-label="Status">
        <span class="status-badge ${getStatusClass(doctor.status)}">
          <span class="status-dot"></span>${capitalizeFirst(doctor.status)}
        </span>
      </td>
      <td data-label="Action">
        <div class="action-group">
          <button class="btn-action manage-btn" onclick="openDoctorDetails(${doctor.id})" title="View details">Details</button>
          <button class="btn-action approve-btn" onclick="openEditDoctorModal(${doctor.id})" title="Edit doctor">Edit</button>
          <button class="btn-action reject-btn" onclick="confirmDeleteDoctor(${doctor.id})" title="Delete doctor">Delete</button>
        </div>
      </td>
    </tr>
  `,
    )
    .join("");

  document.getElementById("rowCount").innerHTML =
    `Showing <strong>${filtered.length}</strong> doctor${filtered.length !== 1 ? "s" : ""}`;
}

// ── Update statistics ────────────────────────────────────────────────────────
function updateStats() {
  const total = allDoctors.length;
  const available = allDoctors.filter((d) => d.status === "available").length;
  const booked = allDoctors.filter((d) => d.status === "booked").length;
  const onLeave = allDoctors.filter((d) => d.status === "leave").length;

  document.getElementById("statTotal").textContent = total;
  document.getElementById("statAvailable").textContent = available;
  document.getElementById("statBooked").textContent = booked;
  document.getElementById("statOnLeave").textContent = onLeave;
}

// ── Open add doctor modal ────────────────────────────────────────────────────
function openAddDoctorModal() {
  document.getElementById("doctorModalTitle").textContent = "Add Doctor";
  document.getElementById("doctorForm").reset();
  document.getElementById("doctorName").disabled = false;
  doctorModal.hidden = false;
}

// ── Open edit doctor modal ───────────────────────────────────────────────────
function openEditDoctorModal(doctorId) {
  const doctor = allDoctors.find((d) => d.id == doctorId);
  if (!doctor) return;

  document.getElementById("doctorModalTitle").textContent =
    `Edit: ${doctor.name}`;
  document.getElementById("doctorName").value = doctor.name;
  document.getElementById("doctorSpec").value = doctor.specialization;
  document.getElementById("doctorDept").value = doctor.department;
  document.getElementById("doctorStartTime").value =
    doctor.schedule_start || "";
  document.getElementById("doctorEndTime").value = doctor.schedule_end || "";
  document.getElementById("doctorSlots").value = doctor.total_slots;
  document.getElementById("doctorStatus").value = doctor.status;
  document.getElementById("doctorName").disabled = true;

  doctorModal.hidden = false;
  doctorModal.dataset.editing = doctorId;
}

// ── Close doctor modal ───────────────────────────────────────────────────────
function closeDoctorModal() {
  doctorModal.hidden = true;
  delete doctorModal.dataset.editing;
  document.getElementById("doctorForm").reset();
}

// ── Close doctor details modal ───────────────────────────────────────────────
function closeDoctorDetailsModal() {
  doctorDetailsModal.hidden = true;
}

// ── Open doctor details modal ────────────────────────────────────────────────
function openDoctorDetails(doctorId) {
  const doctor = allDoctors.find((d) => d.id == doctorId);
  if (!doctor) return;

  const schedule = doctor.schedule_start
    ? `${doctor.schedule_start} to ${doctor.schedule_end}`
    : "Not scheduled";

  const fillPercent = doctor.fill_percent || 0;
  const statusColor =
    {
      available: "#1a7a40",
      booked: "#9a5c00",
      leave: "#b03028",
    }[doctor.status] || "#6b7c6f";

  const statusBgColor =
    {
      available: "#e6f5ed",
      booked: "#fff3e6",
      leave: "#ffe6e6",
    }[doctor.status] || "#f5faf7";

  document.getElementById("detailsTitle").textContent = doctor.name;
  document.getElementById("doctorDetails").innerHTML = `
    <div style="text-align: left;">
      <div style="margin-bottom: 1.8rem; padding-bottom: 1.2rem; border-bottom: 1px solid var(--border-color);">
        <div style="margin-bottom: 1.2rem;">
          <span style="font-size: 0.75rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px;">Specialization</span>
          <div style="font-size: 0.95rem; color: var(--text); font-weight: 500; margin-top: 0.4rem;">${htmlEscape(doctor.specialization)}</div>
        </div>
        <div>
          <span style="font-size: 0.75rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px;">Department</span>
          <div style="font-size: 0.95rem; color: var(--text); font-weight: 500; margin-top: 0.4rem;">${capitalizeFirst(doctor.department)}</div>
        </div>
      </div>
      
      <div style="margin-bottom: 1.8rem; padding-bottom: 1.2rem; border-bottom: 1px solid var(--border-color);">
        <span style="font-size: 0.75rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px;">Schedule</span>
        <div style="font-size: 0.95rem; color: var(--text); font-weight: 500; margin-top: 0.4rem;">${schedule}</div>
      </div>
      
      <div style="margin-bottom: 1.8rem; padding-bottom: 1.2rem; border-bottom: 1px solid var(--border-color);">
        <span style="font-size: 0.75rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px;">Patient Load</span>
        <div style="margin-top: 0.8rem;">
          <div style="display: flex; justify-content: space-between; margin-bottom: 0.6rem;">
            <span style="color: var(--text); font-size: 0.9rem; font-weight: 500;">Booked: ${doctor.booked_slots} of ${doctor.total_slots}</span>
            <span style="color: var(--primary-green); font-size: 0.9rem; font-weight: 700;">${fillPercent}%</span>
          </div>
          <div style="background: var(--border-light); height: 6px; border-radius: 3px; overflow: hidden;">
            <div style="background: var(--primary-green); height: 100%; width: ${fillPercent}%; transition: width 0.3s ease;"></div>
          </div>
        </div>
      </div>
      
      <div>
        <span style="font-size: 0.75rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px;">Status</span>
        <div style="margin-top: 0.4rem;">
          <span style="display: inline-block; padding: 0.5rem 0.9rem; border-radius: 5px; font-size: 0.85rem; font-weight: 600; text-transform: capitalize; background: ${statusBgColor}; color: ${statusColor};">${doctor.status}</span>
        </div>
      </div>
    </div>
  `;

  doctorDetailsModal.hidden = false;
}

// ── Handle create/update doctor ──────────────────────────────────────────────
async function handleCreateDoctor(e) {
  e.preventDefault();

  const isEditing = !!doctorModal.dataset.editing;
  const doctorId = doctorModal.dataset.editing;

  const name = document.getElementById("doctorName").value.trim();
  const specialization = document.getElementById("doctorSpec").value.trim();
  const department = document.getElementById("doctorDept").value.trim();
  const schedStart = document.getElementById("doctorStartTime").value;
  const schedEnd = document.getElementById("doctorEndTime").value;
  const totalSlots = parseInt(document.getElementById("doctorSlots").value);
  const status = document.getElementById("doctorStatus").value;

  if (!name || !specialization || !department) {
    alert("Please fill in all required fields.");
    return;
  }

  try {
    const payload = isEditing
      ? {
          action: "update",
          id: doctorId,
          name,
          specialization,
          department,
          schedule_start: schedStart,
          schedule_end: schedEnd,
          total_slots: totalSlots,
          status,
        }
      : {
          action: "create",
          name,
          specialization,
          department,
          schedule_start: schedStart,
          schedule_end: schedEnd,
          total_slots: totalSlots,
        };

    const response = await fetch(`${API_BASE}/doctors.php`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });

    const data = await response.json();

    if (data.error) {
      alert(data.error);
      return;
    }

    showToast(
      isEditing ? "Doctor updated successfully!" : "Doctor added successfully!",
    );
    closeDoctorModal();
    loadDoctors();
  } catch (error) {
    console.error("Error saving doctor:", error);
    alert("Failed to save doctor");
  }
}

// ── Confirm delete doctor ────────────────────────────────────────────────────
function confirmDeleteDoctor(doctorId) {
  const doctor = allDoctors.find((d) => d.id == doctorId);
  if (!doctor) return;

  document.getElementById("confirmTitle").textContent = "Delete Doctor";
  document.getElementById("confirmBody").textContent =
    `Are you sure you want to delete Dr. ${doctor.name}? This cannot be undone.`;
  document.getElementById("confirmIcon").innerHTML =
    '<svg viewBox="0 0 24 24" fill="none"><path d="M19 6.4L5.4 20M5 6.4L18.6 20" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>';

  confirmModal.hidden = false;

  document.getElementById("confirmBtn").onclick = async () => {
    await deleteDoctor(doctorId);
    closeConfirmModal();
  };
}

// ── Delete doctor ────────────────────────────────────────────────────────────
async function deleteDoctor(doctorId) {
  try {
    const response = await fetch(
      `${API_BASE}/doctors.php?doctor_id=${doctorId}`,
      {
        method: "DELETE",
      },
    );

    const data = await response.json();

    if (data.error) {
      alert(data.error);
      return;
    }

    showToast("Doctor deleted.");
    loadDoctors();
  } catch (error) {
    console.error("Error deleting doctor:", error);
    alert("Failed to delete doctor");
  }
}

// ── Close confirm modal ──────────────────────────────────────────────────────
function closeConfirmModal() {
  confirmModal.hidden = true;
}

// ── Update last refresh time ─────────────────────────────────────────────────
function updateLastRefresh() {
  const time = new Date().toLocaleTimeString([], {
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
  });
  document.getElementById("updatedTime").textContent = time;
}

// ── Helper functions ────────────────────────────────────────────────────────
function htmlEscape(text) {
  const div = document.createElement("div");
  div.textContent = text;
  return div.innerHTML;
}

function capitalizeFirst(str) {
  return str.charAt(0).toUpperCase() + str.slice(1);
}

function getStatusClass(status) {
  return (
    {
      available: "approved",
      booked: "pending",
      leave: "rejected",
    }[status] || "pending"
  );
}

function showToast(msg) {
  const toast = document.getElementById("successToast");
  document.getElementById("toastMsg").textContent = msg;
  toast.classList.add("show");
  setTimeout(() => toast.classList.remove("show"), 3000);
}
