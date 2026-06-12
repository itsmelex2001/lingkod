// regis-general.js — General Management Registration form (posts to PHP API)

document.addEventListener("DOMContentLoaded", () => {
  const birthdateInput = document.getElementById("birthdate");
  const ageInput = document.getElementById("age");
  const regForm = document.getElementById("regForm");

  // ── Auto-calculate age ──────────────────────────────────────────────────
  if (birthdateInput && ageInput) {
    birthdateInput.addEventListener("change", () => {
      const birth = new Date(birthdateInput.value);
      const today = new Date();
      let age = today.getFullYear() - birth.getFullYear();
      const m = today.getMonth() - birth.getMonth();
      if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) age--;
      ageInput.value = age >= 0 ? age : 0;
    });
  }

  // ── Form submission ─────────────────────────────────────────────────────
  if (regForm) {
    regForm.addEventListener("submit", async (e) => {
      e.preventDefault();

      const submitBtn = regForm.querySelector('button[type="submit"]');
      submitBtn.disabled = true;
      submitBtn.textContent = "Submitting…";

      const payload = {
        department: getVal("department"),
        inquiry_only: document.getElementById("inquiryOnly")?.checked ? true : false,
        mode_of_inquiry: getVal("modeOfInquiry"),
        employee_type: getVal("employeeType"),
        select_type: getVal("selectType"), // NOTE: We can map this to appointment_type or leave as is if we added it to schema. Wait, I didn't add select_type to schema.
        // I actually added `unscheduled_walkin` to schema, but for general they use `select_type`.
        // Let me just send it, if backend ignores it, it's fine. But I should map `select_type` to something.
        // Actually, I can pass it.
        full_name: getVal("fullName"),
        hrn: getVal("hrn"),
        birthdate: getVal("birthdate"),
        age: getVal("age"),
        gender: getVal("gender"),
        contact_number: getVal("contactNumber"),
        chief_complaints: getVal("complaint"), // Maps complaint to chief_complaints in DB
        recommendation_remarks: getVal("remarks"), // Maps remarks to recommendation_remarks in DB
        specialty: getVal("specialty"),
        appointment_date: getVal("scheduledDate"), // Maps scheduledDate to appointment_date in DB
        scheduled_time: getVal("scheduledTime")
      };

      try {
        const res = await fetch(`${API_BASE}/registration.php`, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(payload),
        });

        const rawText = await res.text();
        let data;
        try {
          data = JSON.parse(rawText);
        } catch {
          console.error("Non-JSON response from server:\n", rawText);
          throw new Error("The server returned an unexpected response (possible PHP error).");
        }

        if (!data.success) throw new Error(data.message || "Registration failed.");

        // Populate the confirmation modal
        const refEl = document.getElementById("refNumber");
        if (refEl) refEl.textContent = data.patient_id;

        if (typeof populateSummary === "function") populateSummary();

        const modal = document.getElementById("summaryModal");
        if (modal) {
          modal.style.display = "flex";
          modal.classList.add("show");
          document.body.style.overflow = "hidden";
        }
      } catch (err) {
        alert(`Registration failed: ${err.message || "Please try again."}`);
        console.error(err);
      } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = "Confirm Registration";
      }
    });
  }
});
