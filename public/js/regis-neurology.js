// regis-neurology.js — Neurology Patient Registration & Dynamic Inquiry Form

document.addEventListener("DOMContentLoaded", () => {
  const birthdateInput = document.getElementById("birthdate");
  const ageInput = document.getElementById("age");
  const appointmentDateInput = document.getElementById("appointmentDate");
  const appointmentTimeInput = document.getElementById("appointmentTime");
  const middleInitialInput = document.getElementById("middleInitial");
  const patientTypeSelect = document.getElementById("patientType");
  const appointmentType = document.getElementById("appointmentType");
  const consultationMode = document.getElementById("consultationMode");
  const inquiryOnlyCheckbox = document.getElementById("inquiryOnly");
  const secAppointmentDetails = document.getElementById("secAppointmentDetails");
  const groupCompanion = document.getElementById("groupCompanion");
  const groupConsultationNature = document.getElementById("groupConsultationNature");
  const consultationAboutSelect = document.getElementById("consultationAbout");
  const chiefComplaintsSelect = document.getElementById("chiefComplaints");
  const illnessDescriptionTextarea = document.getElementById("illnessDescription");
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
            <p>You are submitting a neurology inquiry. Clinical appointment scheduling fields are optional and hidden.</p>
          </div>
        `;
        regForm.parentNode.insertBefore(inquiryBanner, regForm);
      } else if (inquiryBanner) {
        inquiryBanner.style.display = "flex";
      }

      if (secAppointmentDetails) secAppointmentDetails.style.display = "none";
      if (groupCompanion) groupCompanion.style.display = "none";
      if (groupConsultationNature) groupConsultationNature.style.display = "none";

      if (appointmentDateInput) appointmentDateInput.required = false;
      if (appointmentTimeInput) appointmentTimeInput.required = false;
      if (consultationAboutSelect) consultationAboutSelect.required = false;

      if (lblChiefComplaints) {
        lblChiefComplaints.innerHTML = `Inquiry Subject / Concern <span class="asterisk">*</span>`;
      }
      if (illnessDescriptionTextarea) {
        illnessDescriptionTextarea.placeholder = "Please write your detailed neurological inquiry or question here...";
        illnessDescriptionTextarea.rows = 4;
      }
      if (submitBtn) {
        submitBtn.textContent = "Submit Neurology Inquiry";
      }
    } else {
      if (inquiryBanner) inquiryBanner.style.display = "none";
      if (secAppointmentDetails) secAppointmentDetails.style.display = "block";
      if (groupCompanion) groupCompanion.style.display = "block";
      if (groupConsultationNature) groupConsultationNature.style.display = "block";

      if (appointmentDateInput) appointmentDateInput.required = true;
      if (appointmentTimeInput) appointmentTimeInput.required = true;
      if (consultationAboutSelect) consultationAboutSelect.required = true;

      if (lblChiefComplaints) {
        lblChiefComplaints.innerHTML = `Chief Complaint <span class="asterisk">*</span>`;
      }
      if (illnessDescriptionTextarea) {
        illnessDescriptionTextarea.placeholder = "Additional details, notes, or chief complaint description...";
        illnessDescriptionTextarea.rows = 3;
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
      "Dr. Emilio Cruz": { start: "09:00", end: "16:00" },
      "Dr. Robert Tan": { start: "08:00", end: "14:00" },
      "Dr. Catherine Gomez": { start: "09:00", end: "15:30" },
      "Dr. Dominic Bautista": { start: "10:00", end: "16:00" },
      "Dr. Karen Villanueva": { start: "08:30", end: "14:30" }
    };

    const doc = doctorName ? doctorShifts[doctorName] : null;
    if (!doc) {
      if (timeStr < "08:00" || timeStr > "16:00") {
        return {
          valid: false,
          msg: "Consultation time must be within clinic hours (08:00 AM - 04:00 PM) and at least 30 minutes before clinic closing."
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

      // Personal Details Validation
      const firstNameInput = document.getElementById("firstName");
      const lastNameInput = document.getElementById("lastName");
      const genderSelect = document.getElementById("gender");
      const contactInput = document.getElementById("contactNumber");
      const addressInput = document.getElementById("address");

      if (!firstNameInput.value.trim()) {
        markFieldError(firstNameInput, "First Name is required.");
        if (!firstInvalidEl) firstInvalidEl = firstNameInput;
      }
      if (!lastNameInput.value.trim()) {
        markFieldError(lastNameInput, "Last Name is required.");
        if (!firstInvalidEl) firstInvalidEl = lastNameInput;
      }

      if (!isInquiry) {
        if (!genderSelect.value.trim()) {
          markFieldError(genderSelect, "Please select Gender.");
          if (!firstInvalidEl) firstInvalidEl = genderSelect;
        }
        if (!birthdateInput.value.trim()) {
          markFieldError(birthdateInput, "Birthdate is required.");
          if (!firstInvalidEl) firstInvalidEl = birthdateInput;
        } else if (birthdateInput.value < minBirthStr || birthdateInput.value > todayStr) {
          markFieldError(birthdateInput, `Birthdate must be between ${minBirthStr} and ${todayStr}.`);
          if (!firstInvalidEl) firstInvalidEl = birthdateInput;
        }
        if (!addressInput.value.trim()) {
          markFieldError(addressInput, "Complete Address is required.");
          if (!firstInvalidEl) firstInvalidEl = addressInput;
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
        markFieldError(chiefComplaintsSelect, isInquiry ? "Please select inquiry concern." : "Please select primary neurological chief complaint.");
        if (!firstInvalidEl) firstInvalidEl = chiefComplaintsSelect;
      }

      if (!isInquiry) {
        if (!consultationAboutSelect.value.trim()) {
          markFieldError(consultationAboutSelect, "Please select nature of consultation.");
          if (!firstInvalidEl) firstInvalidEl = consultationAboutSelect;
        }

        // Appointment Details Validation
        if (!patientTypeSelect.value.trim()) {
          markFieldError(patientTypeSelect, "Please select client type.");
          if (!firstInvalidEl) firstInvalidEl = patientTypeSelect;
        }
        if (!consultationMode.value.trim()) {
          markFieldError(consultationMode, "Please select consultation mode.");
          if (!firstInvalidEl) firstInvalidEl = consultationMode;
        }

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

      if (firstInvalidEl) {
        const parentPhase = firstInvalidEl.closest(".form-phase");
        if (parentPhase && typeof window.jumpToPhase === "function") {
          const pNum = parseInt(parentPhase.dataset.phase, 10);
          if (pNum) window.jumpToPhase(pNum);
        }
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
      const additionalNotes = illnessDescriptionTextarea ? illnessDescriptionTextarea.value.trim() : "";
      const fullClinicalNotes = additionalNotes ? `${selectedConcern} — Notes: ${additionalNotes}` : selectedConcern;

      const companionRadio = document.querySelector('input[name="companion"]:checked');

      const payload = {
        department: "neurology",
        inquiry_only: isInquiry ? true : false,
        mode_of_inquiry: isInquiry ? "Online Portal" : null,
        first_name: firstNameVal,
        last_name: lastNameVal,
        middle_initial: cleanMI,
        full_name: fullNameCombined,
        gender: genderSelect ? genderSelect.value : null,
        birthdate: birthVal || null,
        age: calculatedAge !== "" ? calculatedAge : (ageInput?.value || null),
        contact_number: contactInput.value.trim(),
        email: (document.getElementById("emailAddress")?.value || "").trim(),
        address: addressInput ? addressInput.value.trim() : "",
        with_companion: companionRadio ? companionRadio.value : "no",
        chief_complaints: fullClinicalNotes,
        consultation_nature: isInquiry ? "General Inquiry" : consultationAboutSelect.value.trim(),
        illness_description: additionalNotes,
        patient_type: verifiedPatientId ? "returning" : (patientTypeSelect ? patientTypeSelect.value : "first-time"),
        verified_patient_id: verifiedPatientId,
        appointment_type: "online",
        consultation_mode: isInquiry ? "E-consultation" : (consultationMode ? consultationMode.value : "Face-to-Face"),
        appointment_date: isInquiry ? null : appointmentDateInput.value.trim(),
        scheduled_time: isInquiry ? null : appointmentTimeInput.value.trim(),
        referral_source: (document.getElementById("referralSource")?.value || "").trim(),
        assigned_doctor: null,
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
