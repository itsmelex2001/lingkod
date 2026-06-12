// staff-management.js

let allStaff = [];
let currentFilter = "all";
let staffModal = null;
let confirmModal = null;

document.addEventListener("DOMContentLoaded", () => {
  staffModal = document.getElementById("staffModal");
  confirmModal = document.getElementById("confirmModal");

  // ── Load staff data ──────────────────────────────────────────────────────
  loadStaff();

  // ── Event listeners ──────────────────────────────────────────────────────
  document
    .getElementById("addStaffBtn")
    .addEventListener("click", openAddStaffModal);
  document
    .getElementById("staffModalCancel")
    .addEventListener("click", closeStaffModal);
  document
    .getElementById("staffForm")
    .addEventListener("submit", handleCreateStaff);
  document
    .getElementById("confirmCancel")
    .addEventListener("click", closeConfirmModal);

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

// ── Load staff list ──────────────────────────────────────────────────────────
async function loadStaff() {
  try {
    const response = await fetch(`${API_BASE}/staff.php`);
    const data = await response.json();

    if (data.data) {
      allStaff = data.data.filter((s) => s.role === "staff");
      updateStats();
      filterAndDisplay();
      showToast("Staff list loaded.");
    }
  } catch (error) {
    console.error("Failed to load staff:", error);
  }
}

// ── Filter and display staff ─────────────────────────────────────────────────
function filterAndDisplay() {
  const searchTerm = document.getElementById("searchInput").value.toLowerCase();

  let filtered = allStaff.filter((s) => {
    const matchesSearch =
      s.username.toLowerCase().includes(searchTerm) ||
      (s.department || "").toLowerCase().includes(searchTerm);
    const matchesFilter =
      currentFilter === "all" ||
      (currentFilter === "active" && s.status !== "inactive") ||
      (currentFilter === "inactive" && s.status === "inactive");
    return matchesSearch && matchesFilter;
  });

  const tbody = document.getElementById("staffBody");
  const emptyState = document.getElementById("emptyState");

  if (filtered.length === 0) {
    tbody.innerHTML = "";
    emptyState.hidden = false;
    document.getElementById("rowCount").innerHTML =
      "Showing <strong>0</strong> staff members";
    return;
  }

  emptyState.hidden = true;
  tbody.innerHTML = filtered
    .map(
      (staff) => `
    <tr data-id="${staff.id}" data-status="${staff.status || "active"}">
      <td data-label="Username">
        <span class="patient-name" style="font-weight: 500;">${htmlEscape(staff.username)}</span>
      </td>
      <td data-label="Department(s)">
        <span class="dept-tag">${htmlEscape(staff.department || "Admin")}</span>
      </td>
      <td data-label="Status">
        <span class="status-badge ${staff.status === "inactive" ? "rejected" : "approved"}">
          <span class="status-dot"></span>${staff.status === "inactive" ? "Inactive" : "Active"}
        </span>
      </td>
      <td data-label="Created">
        <span style="color: var(--muted); font-size: 0.9rem;">${formatDate(staff.created_at)}</span>
      </td>
      <td data-label="Action">
        <div class="action-group">
          <button class="btn-action manage-btn" onclick="openEditStaffModal(${staff.id})" title="Edit staff">Edit</button>
          <button class="btn-action reject-btn" onclick="confirmDeleteStaff(${staff.id})" title="Delete staff">Delete</button>
        </div>
      </td>
    </tr>
  `,
    )
    .join("");

  document.getElementById("rowCount").innerHTML =
    `Showing <strong>${filtered.length}</strong> staff member${filtered.length !== 1 ? "s" : ""}`;
}

// ── Update statistics ────────────────────────────────────────────────────────
function updateStats() {
  const total = allStaff.length;
  const active = allStaff.filter((s) => s.status !== "inactive").length;
  const inactive = allStaff.filter((s) => s.status === "inactive").length;

  document.getElementById("statTotal").textContent = total;
  document.getElementById("statActive").textContent = active;
  document.getElementById("statInactive").textContent = inactive;
  document.getElementById("statRecent").textContent = Math.max(0, total - 2);
}

// ── Open add staff modal ─────────────────────────────────────────────────────
function openAddStaffModal() {
  document.getElementById("staffModalTitle").textContent =
    "Create Staff Account";
  document.getElementById("staffForm").reset();
  document.getElementById("staffUsername").disabled = false;
  staffModal.hidden = false;
}

// ── Open edit staff modal ────────────────────────────────────────────────────
function openEditStaffModal(staffId) {
  const staff = allStaff.find((s) => s.id == staffId);
  if (!staff) return;

  document.getElementById("staffModalTitle").textContent =
    `Edit: ${staff.username}`;
  document.getElementById("staffUsername").value = staff.username;
  document.getElementById("staffUsername").disabled = true;

  // Set department checkboxes
  document.querySelectorAll('input[name="department"]').forEach((cb) => {
    cb.checked =
      staff.department &&
      staff.department.split(",").some((d) => d.trim() === cb.value);
  });

  staffModal.hidden = false;
}

// ── Close staff modal ────────────────────────────────────────────────────────
function closeStaffModal() {
  staffModal.hidden = true;
  document.getElementById("staffForm").reset();
}

// ── Handle create/update staff ───────────────────────────────────────────────
async function handleCreateStaff(e) {
  e.preventDefault();

  const username = document.getElementById("staffUsername").value.trim();
  const depts = Array.from(
    document.querySelectorAll('input[name="department"]:checked'),
  )
    .map((cb) => cb.value)
    .join(", ");

  if (!depts) {
    alert("Please select at least one department.");
    return;
  }

  try {
    const response = await fetch(`${API_BASE}/staff.php`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        action: "create",
        username: username,
        department: depts,
      }),
    });

    const data = await response.json();

    if (data.error) {
      alert(data.error);
      return;
    }

    showToast(`Staff account "${username}" created successfully!`);
    if (data.temp_password) {
      alert(
        `Temporary password: ${data.temp_password}\n\nShare this with the staff member securely.`,
      );
    }

    closeStaffModal();
    loadStaff();
  } catch (error) {
    console.error("Error creating staff:", error);
    alert("Failed to create staff account");
  }
}

// ── Confirm delete staff ─────────────────────────────────────────────────────
function confirmDeleteStaff(staffId) {
  const staff = allStaff.find((s) => s.id == staffId);
  if (!staff) return;

  document.getElementById("confirmTitle").textContent = "Delete Staff Account";
  document.getElementById("confirmBody").textContent =
    `Are you sure you want to delete "${staff.username}"? This cannot be undone.`;
  document.getElementById("confirmIcon").innerHTML =
    '<svg viewBox="0 0 24 24" fill="none"><path d="M19 6.4L5.4 20M5 6.4L18.6 20" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>';

  confirmModal.hidden = false;

  document.getElementById("confirmBtn").onclick = async () => {
    await deleteStaff(staffId);
    closeConfirmModal();
  };
}

// ── Delete staff ─────────────────────────────────────────────────────────────
async function deleteStaff(staffId) {
  try {
    const response = await fetch(`${API_BASE}/staff.php?user_id=${staffId}`, {
      method: "DELETE",
    });

    const data = await response.json();

    if (data.error) {
      alert(data.error);
      return;
    }

    showToast("Staff account deleted.");
    loadStaff();
  } catch (error) {
    console.error("Error deleting staff:", error);
    alert("Failed to delete staff account");
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

function formatDate(dateStr) {
  if (!dateStr) return "—";
  const date = new Date(dateStr);
  return date.toLocaleDateString([], {
    month: "short",
    day: "numeric",
    year: "2-digit",
  });
}

function showToast(msg) {
  const toast = document.getElementById("successToast");
  document.getElementById("toastMsg").textContent = msg;
  toast.classList.add("show");
  setTimeout(() => toast.classList.remove("show"), 3000);
}
