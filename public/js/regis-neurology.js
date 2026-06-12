// registration.js — Patient Registration form (posts to PHP API)

document.addEventListener("DOMContentLoaded", () => {
  const birthdateInput = document.getElementById("birthdate");
  const ageInput = document.getElementById("age");
  const patientTypeSelect = document.getElementById("patientType");
  const appointmentType = document.getElementById("appointmentType");
  const consultationMode = document.getElementById("consultationMode");
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

  // ── Enforce first-time patient restrictions ─────────────────────────────
  if (patientTypeSelect && appointmentType && consultationMode) {
    patientTypeSelect.addEventListener("change", () => {
      if (patientTypeSelect.value === "first-time") {
        appointmentType.value = "walk-in";
        Array.from(appointmentType.options).forEach((o) => {
          if (o.value === "online") o.disabled = true;
        });
        consultationMode.value = "Face-to-Face";
        Array.from(consultationMode.options).forEach((o) => {
          if (o.text === "E-consultation") o.disabled = true;
        });
        alert(
          "Note: First-time patients must register as Walk-ins with Face-to-Face consultations.",
        );
      } else {
        Array.from(appointmentType.options).forEach(
          (o) => (o.disabled = false),
        );
        Array.from(consultationMode.options).forEach(
          (o) => (o.disabled = false),
        );
      }
    });
  }

  // ── Form submission ─────────────────────────────────────────────────────
  if (regForm) {
    regForm.addEventListener("submit", async (e) => {
      e.preventDefault();

      const submitBtn = regForm.querySelector('button[type="submit"]');
      submitBtn.disabled = true;
      submitBtn.textContent = "Submitting…";

      const companionRadio = document.querySelector(
        'input[name="companion"]:checked',
      );

      const payload = {
        department: getVal("department"),
        full_name: getVal("fullName"),
        hrn: getVal("hrn"),
        gender: getVal("gender"),
        birthdate: getVal("birthdate"),
        age: getVal("age"),
        contact_number: getVal("contactNumber"),
        email: getVal("emailAddress"),
        address: getVal("address"),
        with_companion: companionRadio?.value ?? "no",
        chief_complaints: getVal("chiefComplaints"),
        consultation_nature: getVal("consultationAbout"),
        illness_description: getVal("illnessDescription"),
        patient_type: getVal("patientType"),
        appointment_type: getVal("appointmentType"),
        consultation_mode: getVal("consultationMode"),
        appointment_date: getVal("appointmentDate"),
        scheduled_time: getVal("appointmentTime"),
        referral_source: getVal("referralSource"),
      };

      try {
        const res = await fetch(`${API_BASE}/registration.php`, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(payload),
        });

        // ── Safe JSON parsing ─────────────────────────────────────────────
        // If PHP crashes it returns HTML instead of JSON, which breaks res.json().
        // We read the raw text first and attempt to parse it manually.
        const rawText = await res.text();
        let data;
        try {
          data = JSON.parse(rawText);
        } catch {
          // PHP returned HTML (error page). Surface a helpful message.
          console.error("Non-JSON response from server:\n", rawText);
          throw new Error(
            "The server returned an unexpected response (possible PHP error). " +
              "Please check the XAMPP PHP error log or enable error reporting in api/registration.php.",
          );
        }

        if (!data.success)
          throw new Error(data.message || "Registration failed.");

        // Populate the confirmation modal with API-generated patient ID
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
