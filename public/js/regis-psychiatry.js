// regis-psychiatry.js — OPU Psychiatry Registration & Dynamic Inquiry Form

document.addEventListener("DOMContentLoaded", () => {
  const birthdateInput = document.getElementById("birthdate");
  const ageInput = document.getElementById("age");
  const appointmentDateInput = document.getElementById("appointmentDate");
  const appointmentTimeInput = document.getElementById("appointmentTime");
  const middleInitialInput = document.getElementById("middleInitial");
  const patientTypeSelect = document.getElementById("patientType");
  const inquiryOnlyCheckbox = document.getElementById("inquiryOnly");
  const secInquiryDetails = document.getElementById("secInquiryDetails");
  const secQuickSchedule = document.getElementById("secQuickSchedule");
  const chiefComplaintsSelect = document.getElementById("chiefComplaints");
  const recommendationRemarksTextarea = document.getElementById("recommendationRemarks");
  const lblChiefComplaints = document.getElementById("lblChiefComplaints");
  const regForm = document.getElementById("regForm");
  const submitBtn = regForm ? regForm.querySelector('button[type="submit"]') : null;

  // ── 1. Calculate dynamic date bounds ─────────────────────────────────────────
  const now = new Date();
  const pad = (n) => String(n).padStart(2, "0");
  const todayStr = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;

  // 120 years ago for Birthdate
  const minBirthStr = `${now.getFullYear() - 120}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;

  // Advance Booking Policy: Minimum date is tomorrow (+1 day in advance)
  const tomorrow = new Date();
  tomorrow.setDate(tomorrow.getDate() + 1);
  const minSchedStr = `${tomorrow.getFullYear()}-${pad(tomorrow.getMonth() + 1)}-${pad(tomorrow.getDate())}`;

  // 1 year into the future for Appointment Date
  const maxSchedStr = `${now.getFullYear() + 1}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;

  if (birthdateInput) {
    birthdateInput.min = minBirthStr;
    birthdateInput.max = todayStr;
  }

  if (appointmentDateInput) {
    appointmentDateInput.min = minSchedStr;
    appointmentDateInput.max = maxSchedStr;
  }

  // ── 2. Auto-calculate age strictly from Birthdate ────────────────────────────
  function calculateAge(birthdateStr) {
    if (!birthdateStr) return "";
    const parts = birthdateStr.split("-");
    if (parts.length !== 3) return "";
    const birthYear = parseInt(parts[0], 10);
    const birthMonth = parseInt(parts[1], 10) - 1;
    const birthDay = parseInt(parts[2], 10);
    const birth = new Date(birthYear, birthMonth, birthDay);
    if (isNaN(birth.getTime())) return "";

    const today = new Date();
    let age = today.getFullYear() - birth.getFullYear();
    const m = today.getMonth() - birth.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) {
      age--;
    }
    return age >= 0 && age <= 120 ? age : "";
  }

  function handleBirthdateChange() {
    if (!birthdateInput || !ageInput) return;
    const bVal = birthdateInput.value;
    if (bVal) {
      ageInput.value = calculateAge(bVal);
    } else {
      ageInput.value = "";
    }
  }

  if (birthdateInput && ageInput) {
    ageInput.readOnly = true;
    birthdateInput.addEventListener("input", handleBirthdateChange);
    birthdateInput.addEventListener("change", handleBirthdateChange);
    if (birthdateInput.value) handleBirthdateChange();
  }

  // ── 3. Dynamic "Inquire Only" Mode ──────────────────────────────────────────
  let inquiryBanner = null;

  function handleInquiryOnlyToggle() {
    const isInquiry = inquiryOnlyCheckbox ? inquiryOnlyCheckbox.checked : false;

    if (isInquiry) {
      if (!inquiryBanner && regForm) {
        inquiryBanner = document.createElement("div");
        inquiryBanner.className = "inquiry-mode-banner";
        inquiryBanner.innerHTML = `
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="16" x2="12" y2="12"></line>
            <line x1="12" y1="8" x2="12.01" y2="8"></line>
          </svg>
          <div>
            <strong>Inquiry Mode Active</strong>
            <p>You are submitting a mental wellness inquiry. Clinical appointment scheduling fields are optional and hidden.</p>
          </div>
        `;
        regForm.parentNode.insertBefore(inquiryBanner, regForm);
      } else if (inquiryBanner) {
        inquiryBanner.style.display = "flex";
      }

      if (secInquiryDetails) secInquiryDetails.style.display = "none";
      if (secQuickSchedule) secQuickSchedule.style.display = "none";

      if (appointmentDateInput) appointmentDateInput.required = false;
      if (appointmentTimeInput) appointmentTimeInput.required = false;

      if (lblChiefComplaints) {
        lblChiefComplaints.innerHTML = `Inquiry Subject / Concern <span class="asterisk">*</span>`;
      }
      if (recommendationRemarksTextarea) {
        recommendationRemarksTextarea.placeholder = "Please write your detailed mental health inquiry or question here...";
        recommendationRemarksTextarea.rows = 4;
      }
      if (submitBtn) {
        submitBtn.textContent = "Submit Psychiatry Inquiry";
      }
    } else {
      if (inquiryBanner) inquiryBanner.style.display = "none";
      if (secInquiryDetails) secInquiryDetails.style.display = "block";
      if (secQuickSchedule) secQuickSchedule.style.display = "block";

      if (appointmentDateInput) appointmentDateInput.required = true;
      if (appointmentTimeInput) appointmentTimeInput.required = true;

      if (lblChiefComplaints) {
        lblChiefComplaints.innerHTML = `Health Concerns <span class="asterisk">*</span>`;
      }
      if (recommendationRemarksTextarea) {
        recommendationRemarksTextarea.placeholder = "Additional details, recommendations, or symptoms...";
        recommendationRemarksTextarea.rows = 2;
      }
      if (submitBtn) {
        submitBtn.textContent = "Confirm Registration";
      }
    }
  }

  if (inquiryOnlyCheckbox) {
    inquiryOnlyCheckbox.addEventListener("change", handleInquiryOnlyToggle);
    handleInquiryOnlyToggle();
  }

  // ── 4. URL Params (Doctor & Service) ────────────────────────────────────────
  const urlParams = new URLSearchParams(window.location.search);
  const serviceParam = urlParams.get("service") || urlParams.get("spec");
  const doctorParam = urlParams.get("doctor");

  if (serviceParam && chiefComplaintsSelect) {
    for (let opt of chiefComplaintsSelect.options) {
      if (opt.value && (opt.value.toLowerCase().includes(serviceParam.toLowerCase()) || serviceParam.toLowerCase().includes(opt.value.toLowerCase()))) {
        opt.selected = true;
        break;
      }
    }
  }

  // ── 5. Overhauled Granular Field Validation Logic ────────────────────────────
  function markFieldError(el, message) {
    if (!el) return;
    el.classList.add("is-invalid");
    const parent = el.parentElement;
    if (parent) {
      parent.classList.add("has-error");
      let msgSpan = parent.querySelector(".field-error-msg");
      if (!msgSpan) {
        msgSpan = document.createElement("span");
        msgSpan.className = "field-error-msg";
        parent.appendChild(msgSpan);
      }
      msgSpan.innerHTML = `
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="12" y1="8" x2="12" y2="12"></line>
          <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <span>${message}</span>
      `;
    }
  }

  function clearFieldError(el) {
    if (!el) return;
    el.classList.remove("is-invalid");
    const parent = el.parentElement;
    if (parent) {
      parent.classList.remove("has-error");
      const msgSpan = parent.querySelector(".field-error-msg");
      if (msgSpan) msgSpan.remove();
    }
  }

  function clearAllErrors() {
    document.querySelectorAll(".is-invalid").forEach((el) => el.classList.remove("is-invalid"));
    document.querySelectorAll(".has-error").forEach((el) => el.classList.remove("has-error"));
    document.querySelectorAll(".field-error-msg").forEach((el) => el.remove());
  }

  if (regForm) {
    regForm.querySelectorAll("input, select, textarea").forEach((input) => {
      input.addEventListener("input", () => clearFieldError(input));
      input.addEventListener("change", () => clearFieldError(input));
    });
  }

  // ── 6. Doctor Shift End Restriction Validation ──────────────────────────────
  function validateDoctorShiftTime(timeStr, doctorName) {
    if (!timeStr) return { valid: true };

    const doctorShifts = {
      "Dr. Alan Magno": { start: "08:00", end: "14:30" },
      "Dr. Sarah Reyes": { start: "13:00", end: "17:00" },
      "Dr. Gabriel Mendoza": { start: "08:00", end: "15:00" },
      "Dr. Patricia Lim": { start: "09:00", end: "16:00" }
    };

    const doc = doctorName ? doctorShifts[doctorName] : null;
    if (!doc) {
      if (timeStr < "08:00" || timeStr > "16:30") {
        return {
          valid: false,
          msg: "Consultation time must be within clinic hours (08:00 AM - 04:30 PM) and at least 30 minutes before clinic closing."
        };
      }
      return { valid: true };
    }

    const [endH, endM] = doc.end.split(":").map(Number);
    let cutoffMinutes = endH * 60 + endM - 30;
    const cutoffH = Math.floor(cutoffMinutes / 60);
    const cutoffM = cutoffMinutes % 60;
    const cutoffTimeStr = `${pad(cutoffH)}:${pad(cutoffM)}`;

    if (timeStr < doc.start) {
      return {
        valid: false,
        msg: `${doctorName}'s shift begins at ${doc.start}. Please select a time during consultation hours.`
      };
    }
    if (timeStr > cutoffTimeStr) {
      return {
        valid: false,
        msg: `Appointments cannot be booked within 30 minutes of shift end. ${doctorName}'s shift ends at ${doc.end} (Last booking slot: ${cutoffTimeStr}).`
      };
    }

    return { valid: true };
  }

  // ── 7. Form Submission Handler ──────────────────────────────────────────────
  if (regForm) {
    regForm.addEventListener("submit", async (e) => {
      e.preventDefault();
      clearAllErrors();

      const isInquiry = inquiryOnlyCheckbox ? inquiryOnlyCheckbox.checked : false;
      let firstInvalidEl = null;

      // Appointment Details Validation (if not inquiry)
      if (!isInquiry) {
        if (!appointmentDateInput.value.trim()) {
          markFieldError(appointmentDateInput, "Appointment Date is required.");
          if (!firstInvalidEl) firstInvalidEl = appointmentDateInput;
        } else if (appointmentDateInput.value < minSchedStr) {
          markFieldError(appointmentDateInput, `MMWGH Policy: Online bookings require advance scheduling (at least 1 day in advance, starting ${minSchedStr}).`);
          if (!firstInvalidEl) firstInvalidEl = appointmentDateInput;
        } else if (appointmentDateInput.value > maxSchedStr) {
          markFieldError(appointmentDateInput, `Date cannot be more than 1 year in advance.`);
          if (!firstInvalidEl) firstInvalidEl = appointmentDateInput;
        }

        if (!appointmentTimeInput.value.trim()) {
          markFieldError(appointmentTimeInput, "Appointment Time is required.");
          if (!firstInvalidEl) firstInvalidEl = appointmentTimeInput;
        } else {
          const shiftCheck = validateDoctorShiftTime(appointmentTimeInput.value.trim(), doctorParam);
          if (!shiftCheck.valid) {
            markFieldError(appointmentTimeInput, shiftCheck.msg);
            if (!firstInvalidEl) firstInvalidEl = appointmentTimeInput;
          }
        }
      }

      // Personal Details Validation
      const firstNameInput = document.getElementById("firstName");
      const lastNameInput = document.getElementById("lastName");
      const contactInput = document.getElementById("contactNumber");

      if (!firstNameInput.value.trim()) {
        markFieldError(firstNameInput, "First Name is required.");
        if (!firstInvalidEl) firstInvalidEl = firstNameInput;
      }
      if (!lastNameInput.value.trim()) {
        markFieldError(lastNameInput, "Last Name is required.");
        if (!firstInvalidEl) firstInvalidEl = lastNameInput;
      }

      if (!isInquiry) {
        if (!birthdateInput.value.trim()) {
          markFieldError(birthdateInput, "Birthdate is required.");
          if (!firstInvalidEl) firstInvalidEl = birthdateInput;
        } else if (birthdateInput.value < minBirthStr || birthdateInput.value > todayStr) {
          markFieldError(birthdateInput, `Birthdate must be between ${minBirthStr} and ${todayStr}.`);
          if (!firstInvalidEl) firstInvalidEl = birthdateInput;
        }
        if (!patientTypeSelect.value.trim()) {
          markFieldError(patientTypeSelect, "Please select client status (New/Returning).");
          if (!firstInvalidEl) firstInvalidEl = patientTypeSelect;
        }
      }

      if (!contactInput.value.trim()) {
        markFieldError(contactInput, "Contact Number is required.");
        if (!firstInvalidEl) firstInvalidEl = contactInput;
      } else if (!/^(09|\+639)\d{9}$/.test(contactInput.value.trim())) {
        markFieldError(contactInput, "Please enter a valid Philippine mobile number (e.g. 09XXXXXXXXX).");
        if (!firstInvalidEl) firstInvalidEl = contactInput;
      }

      // Medical Information Validation
      if (!chiefComplaintsSelect.value.trim()) {
        markFieldError(chiefComplaintsSelect, isInquiry ? "Please select inquiry concern." : "Please select primary psychiatric concern.");
        if (!firstInvalidEl) firstInvalidEl = chiefComplaintsSelect;
      }

      if (firstInvalidEl) {
        firstInvalidEl.scrollIntoView({ behavior: "smooth", block: "center" });
        firstInvalidEl.focus();
        return;
      }

      // Build payload
      const miRaw = middleInitialInput ? middleInitialInput.value.trim() : "";
      const cleanMI = miRaw ? (miRaw.endsWith(".") ? miRaw.toUpperCase() : miRaw.toUpperCase() + ".") : "";
      const firstNameVal = firstNameInput.value.trim();
      const lastNameVal = lastNameInput.value.trim();
      const fullNameCombined = cleanMI ? `${firstNameVal} ${cleanMI} ${lastNameVal}` : `${firstNameVal} ${lastNameVal}`;

      const hiddenFullName = document.getElementById("fullName");
      if (hiddenFullName) hiddenFullName.value = fullNameCombined;

      const birthVal = birthdateInput ? birthdateInput.value.trim() : "";
      const calculatedAge = calculateAge(birthVal);

      const verifiedIdInput = document.getElementById("verifiedPatientId");
      const verifiedPatientId = verifiedIdInput ? verifiedIdInput.value.trim() : null;

      const selectedConcern = chiefComplaintsSelect.value.trim();
      const additionalNotes = recommendationRemarksTextarea ? recommendationRemarksTextarea.value.trim() : "";
      const fullClinicalNotes = additionalNotes ? `${selectedConcern} — Notes: ${additionalNotes}` : selectedConcern;

      const consultationModeRadio = document.querySelector('input[name="consultationMode"]:checked');

      const payload = {
        department: "psychiatry",
        inquiry_only: isInquiry ? true : false,
        mode_of_inquiry: isInquiry ? "Online Portal" : null,
        first_name: firstNameVal,
        last_name: lastNameVal,
        middle_initial: cleanMI,
        full_name: fullNameCombined,
        gender: "Other", // default if not specified in basic form
        birthdate: birthVal || null,
        age: calculatedAge !== "" ? calculatedAge : (ageInput?.value || null),
        contact_number: contactInput.value.trim(),
        chief_complaints: fullClinicalNotes,
        recommendation_remarks: additionalNotes,
        patient_type: verifiedPatientId ? "returning" : (patientTypeSelect ? patientTypeSelect.value : "first-time"),
        verified_patient_id: verifiedPatientId,
        hrn: (document.getElementById("hrn")?.value || "").trim(),
        receiver: (document.getElementById("receiver")?.value || "").trim(),
        informant: (document.getElementById("informant")?.value || "").trim(),
        appointment_type: "online",
        consultation_mode: isInquiry ? "Teleconsultation" : (consultationModeRadio ? consultationModeRadio.value : "Face-to-Face"),
        appointment_date: isInquiry ? null : appointmentDateInput.value.trim(),
        scheduled_time: isInquiry ? null : appointmentTimeInput.value.trim(),
        referral_source: (document.getElementById("referralSource")?.value || "").trim(),
        assigned_doctor: doctorParam || null,
        website_url: document.getElementById("website_url")?.value || ""
      };

      submitBtn.disabled = true;
      submitBtn.textContent = isInquiry ? "Submitting Inquiry…" : "Submitting Registration…";

      try {
        const res = await fetch("/sdn/api/registration.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(payload),
        });

        const data = await res.json();
        if (!data.success) {
          throw new Error(data.message || "Registration submission failed.");
        }

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
        if (typeof showNoticeModal === "function") {
          showNoticeModal(`Registration Notice: ${err.message || "Please check your inputs."}`, "Booking Alert", "error");
        } else {
          alert(`Registration Notice: ${err.message || "Please check your inputs."}`);
        }
      } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = isInquiry ? "Submit Inquiry" : "Confirm Registration";
      }
    });
  }
});
