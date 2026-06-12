// regis-psychiatry.js — Psychiatry Registration form (posts to PHP API)

document.addEventListener("DOMContentLoaded", () => {
  const regForm = document.getElementById("regForm");

  // ── Form submission ─────────────────────────────────────────────────────
  if (regForm) {
    regForm.addEventListener("submit", async (e) => {
      e.preventDefault();

      const submitBtn = regForm.querySelector('button[type="submit"]');
      submitBtn.disabled = true;
      submitBtn.textContent = "Submitting…";

      const consultationModeRadio = document.querySelector('input[name="consultationMode"]:checked');

      const payload = {
        department: getVal("department"),
        appointment_date: getVal("appointmentDate"),
        scheduled_time: getVal("appointmentTime"),
        receiver: getVal("receiver"),
        appointment_type: getVal("appointmentType"),
        referral_source: getVal("referralSource"),
        full_name: getVal("fullName"),
        hrn: getVal("hrn"),
        patient_type: getVal("patientType"),
        contact_number: getVal("contactNumber"),
        informant: getVal("informant"),
        chief_complaints: getVal("chiefComplaints"),
        recommendation_remarks: getVal("recommendationRemarks"),
        consultation_mode: consultationModeRadio ? consultationModeRadio.value : "",
        unscheduled_walkin: document.getElementById("unscheduledWalkin")?.checked ? true : false,
        inquiry_only: document.getElementById("inquiryOnly")?.checked ? true : false
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
