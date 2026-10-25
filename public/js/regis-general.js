// regis-general.js — General Management Registration & Dynamic Inquiry Form

document.addEventListener("DOMContentLoaded", () => {
  const birthdateInput = document.getElementById("birthdate");
  const ageInput = document.getElementById("age");
  const scheduledDateInput = document.getElementById("scheduledDate");
  const scheduledTimeInput = document.getElementById("scheduledTime");
  const middleInitialInput = document.getElementById("middleInitial");
  const modeOfInquirySelect = document.getElementById("modeOfInquiry");
  const employeeTypeSelect = document.getElementById("employeeType");
  const groupEmployeeType = document.getElementById("groupEmployeeType");
  const employeeTypeAsterisk = document.getElementById("employeeTypeAsterisk");
  const inquiryOnlyCheckbox = document.getElementById("inquiryOnly");
  const secScheduleDetails = document.getElementById("secScheduleDetails");
  const complaintSelect = document.getElementById("complaint");
  const remarksTextarea = document.getElementById("remarks");
  const lblComplaint = document.getElementById("lblComplaint");
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

  // 1 year into the future for Scheduled Date
  const maxSchedStr = `${now.getFullYear() + 1}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;

  if (birthdateInput) {
    birthdateInput.min = minBirthStr;
    birthdateInput.max = todayStr;
  }

  if (scheduledDateInput) {
    scheduledDateInput.min = minSchedStr;
    scheduledDateInput.max = maxSchedStr;
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

  // ── 3. Mode of Inquiry: "Direct" Classification Logic ────────────────────────
  // If patient is marked as "Direct", automatically disable or remove employee/non-employee selection
  function handleModeOfInquiry() {
    if (!modeOfInquirySelect || !employeeTypeSelect) return;
    const isDirect = modeOfInquirySelect.value.trim().toLowerCase() === "direct";

    if (isDirect) {
      employeeTypeSelect.value = "";
      employeeTypeSelect.disabled = true;
      employeeTypeSelect.required = false;
      if (groupEmployeeType) groupEmployeeType.classList.add("form-group-disabled");
      if (employeeTypeAsterisk) employeeTypeAsterisk.style.display = "none";
      clearFieldError(employeeTypeSelect);
    } else {
      employeeTypeSelect.disabled = false;
      employeeTypeSelect.required = true;
      if (groupEmployeeType) groupEmployeeType.classList.remove("form-group-disabled");
      if (employeeTypeAsterisk) employeeTypeAsterisk.style.display = "inline";
    }
  }

  if (modeOfInquirySelect) {
    modeOfInquirySelect.addEventListener("change", handleModeOfInquiry);
    handleModeOfInquiry();
  }

  // ── 4. Dynamic "Inquire Only" Mode ──────────────────────────────────────────
  let inquiryBanner = null;

  function handleInquiryOnlyToggle() {
    const isInquiry = inquiryOnlyCheckbox ? inquiryOnlyCheckbox.checked : false;

    if (isInquiry) {
      // Create or show banner
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
            <p>You are submitting a general health inquiry. Clinical appointment scheduling fields are hidden and not required.</p>
          </div>
        `;
        regForm.parentNode.insertBefore(inquiryBanner, regForm);
      } else if (inquiryBanner) {
        inquiryBanner.style.display = "flex";
      }

      // Hide and unrequire schedule section
      if (secScheduleDetails) {
        secScheduleDetails.style.display = "none";
      }
      if (scheduledDateInput) scheduledDateInput.required = false;
      if (scheduledTimeInput) scheduledTimeInput.required = false;

      // Relabel concerns to inquiry
      if (lblComplaint) {
        lblComplaint.innerHTML = `Inquiry Subject / Concern <span class="asterisk">*</span>`;
      }
      if (remarksTextarea) {
        remarksTextarea.placeholder = "Please write your detailed inquiry question or message here...";
        remarksTextarea.rows = 4;
      }
      if (submitBtn) {
        submitBtn.textContent = "Submit Inquiry";
      }
    } else {
      // Restore standard appointment registration
      if (inquiryBanner) inquiryBanner.style.display = "none";
      if (secScheduleDetails) secScheduleDetails.style.display = "block";
      if (scheduledDateInput) scheduledDateInput.required = true;
      if (scheduledTimeInput) scheduledTimeInput.required = true;

      if (lblComplaint) {
        lblComplaint.innerHTML = `Health Concerns <span class="asterisk">*</span>`;
      }
      if (remarksTextarea) {
        remarksTextarea.placeholder = "Additional observations, symptoms, or inquiry details (optional)...";
        remarksTextarea.rows = 2;
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

  // ── 5. Auto-fill & lock service from URL query ─────────────────────────────
  const urlParams = new URLSearchParams(window.location.search);
  const serviceParam = urlParams.get("service") || urlParams.get("spec");
  const doctorParam = urlParams.get("doctor");
  const specialtySelect = document.getElementById("specialty");
  const lockedSpecialtyInput = document.getElementById("lockedSpecialtyInput");

  const effectiveService = serviceParam || (lockedSpecialtyInput ? lockedSpecialtyInput.value.trim() : "");

  if (effectiveService) {
    if (specialtySelect) {
      const sLower = effectiveService.toLowerCase().trim();
      let matched = false;
      for (let opt of specialtySelect.options) {
        if (!opt.value) continue;
        if (opt.value.toLowerCase().trim() === sLower) {
          opt.selected = true;
          specialtySelect.value = opt.value;
          matched = true;
          break;
        }
      }
      if (!matched) {
        for (let opt of specialtySelect.options) {
          if (!opt.value) continue;
          if (opt.value.toLowerCase().includes(sLower) || sLower.includes(opt.value.toLowerCase())) {
            opt.selected = true;
            specialtySelect.value = opt.value;
            matched = true;
            break;
          }
        }
      }
      specialtySelect.disabled = true;
      specialtySelect.classList.add("locked-select");
      if (lockedSpecialtyInput) lockedSpecialtyInput.value = specialtySelect.value || effectiveService;
    }

    if (doctorParam && remarksTextarea && !remarksTextarea.value) {
      remarksTextarea.value = `Attending Physician: ${doctorParam}`;
    }
  }

  // ── 6. Overhauled Granular Field Validation Logic ────────────────────────────
  // Flags ONLY the specific empty or invalid field with inline message, without falsely flagging filled fields
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

  // Clear error automatically when user interacts with field
  if (regForm) {
    regForm.querySelectorAll("input, select, textarea").forEach((input) => {
      input.addEventListener("input", () => clearFieldError(input));
      input.addEventListener("change", () => clearFieldError(input));
    });
  }

  // ── 7. Doctor Shift End Restriction Validation ──────────────────────────────
  // Prevent appointment booking within 30 minutes of doctor shift end or outside shift
  function validateDoctorShiftTime(timeStr, doctorName) {
    if (!timeStr) return { valid: true };

    // Standard clinical shift mapping if doctor is assigned
    const doctorShifts = {
      "Dr. Maria Santos": { start: "08:00", end: "16:00" },
      "Dr. Juan Dela Cruz": { start: "08:00", end: "16:00" },
      "Dr. Antonio Ramos": { start: "08:30", end: "16:00" },
      "Dr. Elena Fernandez": { start: "09:00", end: "16:30" },
      "Dr. Benjamin Cruz": { start: "08:30", end: "15:30" },
      "Dr. Ricardo De Leon": { start: "10:00", end: "16:00" }
    };

    const doc = doctorName ? doctorShifts[doctorName] : null;
    if (!doc) {
      // General clinic hours: 08:00 to 16:30 (Cutoff: 16:00)
      if (timeStr < "08:00" || timeStr > "16:00") {
        return {
          valid: false,
          msg: "Consultation time must be within clinic hours (08:00 AM - 04:00 PM) and at least 30 minutes before clinic closing."
        };
      }
      return { valid: true };
    }

    // Calculate cutoff: 30 minutes before shift end
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

  // ── 8. Form Submission Handler ──────────────────────────────────────────────
  if (regForm) {
    regForm.addEventListener("submit", async (e) => {
      e.preventDefault();
      clearAllErrors();

      const isInquiry = inquiryOnlyCheckbox ? inquiryOnlyCheckbox.checked : false;
      let firstInvalidEl = null;

      // ── Step A: Mode of Inquiry validation
      if (!modeOfInquirySelect.value.trim()) {
        markFieldError(modeOfInquirySelect, "Please select Mode of Inquiry.");
        if (!firstInvalidEl) firstInvalidEl = modeOfInquirySelect;
      }

      // ── Step B: Employee Type validation (only if not Direct)
      const isDirect = modeOfInquirySelect.value.trim().toLowerCase() === "direct";
      if (!isDirect && !employeeTypeSelect.value.trim()) {
        markFieldError(employeeTypeSelect, "Please select Employee Type.");
        if (!firstInvalidEl) firstInvalidEl = employeeTypeSelect;
      }

      // ── Step C: Schedule Details (Required only if NOT Inquiry Only)
      if (!isInquiry) {
        if (!scheduledDateInput.value.trim()) {
          markFieldError(scheduledDateInput, "Scheduled Date is required.");
          if (!firstInvalidEl) firstInvalidEl = scheduledDateInput;
        } else if (scheduledDateInput.value < minSchedStr) {
          markFieldError(scheduledDateInput, `MMWGH Policy: Online bookings require advance scheduling (at least 1 day in advance, starting ${minSchedStr}).`);
          if (!firstInvalidEl) firstInvalidEl = scheduledDateInput;
        } else if (scheduledDateInput.value > maxSchedStr) {
          markFieldError(scheduledDateInput, `Date cannot be more than 1 year in advance.`);
          if (!firstInvalidEl) firstInvalidEl = scheduledDateInput;
        }

        if (!scheduledTimeInput.value.trim()) {
          markFieldError(scheduledTimeInput, "Scheduled Time is required.");
          if (!firstInvalidEl) firstInvalidEl = scheduledTimeInput;
        } else {
          // Check shift end restriction
          const shiftCheck = validateDoctorShiftTime(scheduledTimeInput.value.trim(), doctorParam);
          if (!shiftCheck.valid) {
            markFieldError(scheduledTimeInput, shiftCheck.msg);
            if (!firstInvalidEl) firstInvalidEl = scheduledTimeInput;
          }
        }
      }

      // ── Step D: Patient Name validation
      const firstNameInput = document.getElementById("firstName");
      const lastNameInput = document.getElementById("lastName");
      if (!firstNameInput.value.trim()) {
        markFieldError(firstNameInput, "First Name is required.");
        if (!firstInvalidEl) firstInvalidEl = firstNameInput;
      }
      if (!lastNameInput.value.trim()) {
        markFieldError(lastNameInput, "Last Name is required.");
        if (!firstInvalidEl) firstInvalidEl = lastNameInput;
      }

      // ── Step E: Birthdate & Gender (For full registration)
      const genderSelect = document.getElementById("gender");
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
      }

      // ── Step F: Contact Number
      const contactInput = document.getElementById("contactNumber");
      if (!contactInput.value.trim()) {
        markFieldError(contactInput, "Contact Number is required.");
        if (!firstInvalidEl) firstInvalidEl = contactInput;
      } else if (!/^(09|\+639)\d{9}$/.test(contactInput.value.trim())) {
        markFieldError(contactInput, "Please enter a valid Philippine mobile number (e.g. 09XXXXXXXXX).");
        if (!firstInvalidEl) firstInvalidEl = contactInput;
      }

      // ── Step G: Health Concern / Inquiry Question
      if (!complaintSelect.value.trim()) {
        markFieldError(complaintSelect, isInquiry ? "Please select inquiry subject." : "Please select primary health concern.");
        if (!firstInvalidEl) firstInvalidEl = complaintSelect;
      }

      // If any field failed validation, scroll to and focus the FIRST invalid field only
      if (firstInvalidEl) {
        firstInvalidEl.scrollIntoView({ behavior: "smooth", block: "center" });
        firstInvalidEl.focus();
        return;
      }

      // ── If validation passed cleanly, proceed to submission ────────────────
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

      // Combine primary concern with optional notes
      const selectedConcern = complaintSelect.value.trim();
      const additionalNotes = remarksTextarea ? remarksTextarea.value.trim() : "";
      const fullClinicalNotes = additionalNotes ? `${selectedConcern} — Notes: ${additionalNotes}` : selectedConcern;

      const payload = {
        department: "general",
        inquiry_only: isInquiry ? true : false,
        mode_of_inquiry: modeOfInquirySelect.value.trim(),
        employee_type: isDirect ? "N/A" : employeeTypeSelect.value.trim(),
        select_type: "Scheduled",
        first_name: firstNameVal,
        last_name: lastNameVal,
        middle_initial: cleanMI,
        full_name: fullNameCombined,
        patient_type: verifiedPatientId ? "returning" : "first-time",
        verified_patient_id: verifiedPatientId,
        hrn: (document.getElementById("hrn")?.value || "").trim(),
        birthdate: birthVal || null,
        age: calculatedAge !== "" ? calculatedAge : (ageInput?.value || null),
        gender: genderSelect ? genderSelect.value : null,
        contact_number: contactInput.value.trim(),
        chief_complaints: fullClinicalNotes,
        recommendation_remarks: additionalNotes,
        specialty: (lockedSpecialtyInput && lockedSpecialtyInput.value.trim()) || (specialtySelect ? specialtySelect.value.trim() : "") || effectiveService || "",
        appointment_date: isInquiry ? null : scheduledDateInput.value.trim(),
        scheduled_time: isInquiry ? null : scheduledTimeInput.value.trim(),
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

        // Set reference number on modal
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
