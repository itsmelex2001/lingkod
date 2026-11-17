/* assets/js/book-appointment-v2.js
 * 4-Step Appointment Booking Controller
 */
document.addEventListener('DOMContentLoaded', () => {
  const profile = window.BA_V2_PROFILE || { isLoggedIn: false };

  let currentStep = 1;
  const totalSteps = 4;
  let isSubmitting = false;

  const alertBox = document.getElementById('bookingAlert');
  const alertText = document.getElementById('bookingAlertText');
  const btnNext = document.getElementById('btnNextStep');
  const btnNextLabel = document.getElementById('btnNextLabel');
  const btnNextSpinner = document.getElementById('btnNextSpinner');
  const btnBack = document.getElementById('btnBackStep');
  const actionButtonBar = document.getElementById('actionButtonBar');
  const formWrapper = document.getElementById('bookingFormWrapper');
  const successModal = document.getElementById('bookingSuccessModal');
  const step2SidebarLabel = document.getElementById('step2SidebarLabel');
  const contentPane = document.querySelector('.ba-content');

  let scrollHideTimer = null;
  function showScrollIndicator() {
    if (!contentPane) return;
    contentPane.classList.add('is-scrolling');
    if (scrollHideTimer) clearTimeout(scrollHideTimer);
    scrollHideTimer = setTimeout(() => {
      contentPane.classList.remove('is-scrolling');
    }, 750);
  }

  if (contentPane) {
    contentPane.addEventListener('scroll', showScrollIndicator, { passive: true });
  }
  window.addEventListener('scroll', showScrollIndicator, { passive: true });

  function scrollFormToTop() {
    if (contentPane && typeof contentPane.scrollTo === 'function') {
      contentPane.scrollTo({ top: 0, behavior: 'smooth' });
    }
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  const inquiryOnlyCheckbox = document.getElementById('inquiryOnlyCheckbox');
  const bookingForRadios = document.querySelectorAll('input[name="booking_for"]');
  const pillBookingMyself = document.getElementById('pillBookingMyself');
  const pillBookingDependent = document.getElementById('pillBookingDependent');
  const boxRelationshipToPatient = document.getElementById('boxRelationshipToPatient');
  const selectRelationshipToPatient = document.getElementById('selectRelationshipToPatient');

  const inputFirstName = document.getElementById('inputFirstName');
  const inputLastName = document.getElementById('inputLastName');
  const inputMiddleInitial = document.getElementById('inputMiddleInitial');
  const selectGender = document.getElementById('selectGender');
  const inputBirthdate = document.getElementById('inputBirthdate');
  const inputAge = document.getElementById('inputAge');
  const inputContactNumber = document.getElementById('inputContactNumber');
  const inputEmail = document.getElementById('inputEmail');
  const inputAddress = document.getElementById('inputAddress');
  const radioCompanionYes = document.getElementById('radioCompanionYes');
  const radioCompanionNo = document.getElementById('radioCompanionNo');
  const departmentSelect = document.getElementById('departmentSelect');

  const inputChiefComplaints = document.getElementById('inputChiefComplaints');
  const inputExistingConditions = document.getElementById('inputExistingConditions');
  const inputCurrentMedications = document.getElementById('inputCurrentMedications');
  const inputReferralSource = document.getElementById('inputReferralSource');

  const modeRadios = document.querySelectorAll('input[name="consultation_mode"]');
  const cardModeF2F = document.getElementById('cardModeF2F');
  const cardModeTele = document.getElementById('cardModeTele');
  const radioModeF2F = document.getElementById('radioModeF2F');
  const radioModeTele = document.getElementById('radioModeTele');
  const teleModeLockTag = document.getElementById('teleModeLockTag');
  const firstTimeModeNotice = document.getElementById('firstTimeModeNotice');
  let isFirstTimePatient = profile.isFirstTime !== undefined ? Boolean(profile.isFirstTime) : true;
  const inputAppointmentDate = document.getElementById('inputAppointmentDate');
  const timeSlotRadios = document.querySelectorAll('input[name="preferred_time_slot"]');
  const slotMorningCard = document.getElementById('slotMorningCard');
  const slotAfternoonCard = document.getElementById('slotAfternoonCard');

  function showAlert(msg, isError = true) {
    if (!alertBox) return;
    if (alertText) alertText.textContent = msg;
    alertBox.className = isError ? 'ba-alert error' : 'ba-alert success';
    alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  function clearAlert() {
    if (!alertBox) return;
    if (alertText) alertText.textContent = '';
    alertBox.className = 'ba-alert';
  }

  function setFieldError(el, message) {
    if (!el) return false;
    const fieldWrap = el.closest('.ba-field');
    if (!fieldWrap) return false;
    const errEl = fieldWrap.querySelector('.ba-error-msg');
    if (message) {
      fieldWrap.classList.add('has-error');
      el.setAttribute('aria-invalid', 'true');
      if (errEl) {
        const textNode = errEl.querySelector('.ba-error-text');
        if (textNode) textNode.textContent = message;
      }
      return false;
    }
    fieldWrap.classList.remove('has-error');
    el.removeAttribute('aria-invalid');
    return true;
  }

  function clearAllFieldErrors(container) {
    const scope = container || document;
    scope.querySelectorAll('.ba-field.has-error').forEach((f) => {
      f.classList.remove('has-error');
      const ctrl = f.querySelector('input, select, textarea');
      if (ctrl) ctrl.removeAttribute('aria-invalid');
    });
  }

  function syncSelectPlaceholder(selectEl) {
    if (!selectEl) return;
    selectEl.classList.toggle('is-placeholder', !selectEl.value);
  }

  [selectGender, departmentSelect, selectRelationshipToPatient].forEach((sel) => {
    if (!sel) return;
    syncSelectPlaceholder(sel);
    sel.addEventListener('change', () => syncSelectPlaceholder(sel));
  });

  function computeAgeFromBirthdate() {
    if (!inputBirthdate || !inputAge) return;
    if (!inputBirthdate.value) {
      inputAge.value = '';
      return;
    }
    const birth = new Date(inputBirthdate.value + 'T00:00:00');
    const today = new Date();
    if (Number.isNaN(birth.getTime()) || birth > today) {
      inputAge.value = '';
      return;
    }
    let age = today.getFullYear() - birth.getFullYear();
    const m = today.getMonth() - birth.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) {
      age--;
    }
    inputAge.value = age >= 0 ? String(age) : '';
  }

  if (inputBirthdate) {
    inputBirthdate.addEventListener('input', computeAgeFromBirthdate);
    inputBirthdate.addEventListener('change', computeAgeFromBirthdate);
    computeAgeFromBirthdate();
  }

  if (inputContactNumber) {
    inputContactNumber.addEventListener('input', () => {
      let digits = inputContactNumber.value.replace(/\D/g, '');
      if (digits.startsWith('63') && digits.length > 10) {
        digits = digits.slice(2);
      } else if (digits.startsWith('09') && digits.length === 11) {
        digits = digits.slice(1);
      }
      if (digits.length > 10) digits = digits.slice(0, 10);
      inputContactNumber.value = digits;
    });
  }

  function updateRecipientState() {
    const selected = document.querySelector('input[name="booking_for"]:checked')?.value || 'Patient (Me)';
    const isOther = selected === 'Other Patient';

    if (pillBookingMyself) pillBookingMyself.classList.toggle('is-selected', !isOther);
    if (pillBookingDependent) pillBookingDependent.classList.toggle('is-selected', isOther);
    if (boxRelationshipToPatient) boxRelationshipToPatient.classList.toggle('is-open', isOther);

    clearAllFieldErrors(document.getElementById('phaseStep1'));

    if (profile.isLoggedIn) {
      if (isOther) {
        inputFirstName.value = '';
        inputLastName.value = '';
        inputMiddleInitial.value = '';
        selectGender.value = '';
        inputBirthdate.value = '';
        inputAge.value = '';
        inputAddress.value = '';
        if (radioCompanionYes) radioCompanionYes.checked = true;
      } else {
        inputFirstName.value = profile.firstName || '';
        inputLastName.value = profile.lastName || '';
        inputMiddleInitial.value = profile.middleInitial || '';
        selectGender.value = profile.gender || '';
        inputBirthdate.value = profile.birthdate || '';
        computeAgeFromBirthdate();
        inputContactNumber.value = profile.contactNumber || '';
        inputEmail.value = profile.email || '';
        inputAddress.value = profile.address || '';
        if (selectRelationshipToPatient) {
          selectRelationshipToPatient.value = '';
          syncSelectPlaceholder(selectRelationshipToPatient);
        }
      }
      syncSelectPlaceholder(selectGender);
    }
  }

  bookingForRadios.forEach((r) => r.addEventListener('change', () => {
    updateRecipientState();
    refreshPatientFirstTimeStatus();
  }));

  function updateModeCards() {
    const val = document.querySelector('input[name="consultation_mode"]:checked')?.value || '';
    if (cardModeF2F) cardModeF2F.classList.toggle('is-selected', val === 'In-Person');
    if (cardModeTele) cardModeTele.classList.toggle('is-selected', val === 'Teleconsultation');
    const errEl = document.getElementById('errConsultationMode');
    if (errEl && val) {
      errEl.closest('.ba-field')?.classList.remove('has-error');
    }
  }

  function applyPatientTypeModeLock() {
    if (isFirstTimePatient) {
      if (radioModeF2F) radioModeF2F.checked = true;
      if (radioModeTele) radioModeTele.checked = false;
      if (cardModeTele) {
        cardModeTele.classList.add('is-disabled');
        cardModeTele.setAttribute('aria-disabled', 'true');
      }
      if (teleModeLockTag) teleModeLockTag.style.display = 'inline';
      if (firstTimeModeNotice) firstTimeModeNotice.style.display = 'block';
    } else {
      if (cardModeTele) {
        cardModeTele.classList.remove('is-disabled');
        cardModeTele.removeAttribute('aria-disabled');
      }
      if (teleModeLockTag) teleModeLockTag.style.display = 'none';
      if (firstTimeModeNotice) firstTimeModeNotice.style.display = 'none';
    }
    updateModeCards();
  }

  async function refreshPatientFirstTimeStatus() {
    const recipient = document.querySelector('input[name="booking_for"]:checked')?.value || 'Patient (Me)';
    const first = inputFirstName?.value.trim() || '';
    const last = inputLastName?.value.trim() || '';
    const birth = inputBirthdate?.value || '';
    const emailVal = inputEmail?.value.trim() || '';

    if (recipient === 'Other Patient' && (!first || !last)) {
      isFirstTimePatient = true;
      applyPatientTypeModeLock();
      return;
    }

    try {
      const qs = new URLSearchParams({
        action: 'check_patient_status',
        recipient,
        first_name: first,
        last_name: last,
        birthdate: birth,
        email: emailVal
      });
      const res = await fetch(`/sdn/api/patient_book_appointment.php?${qs.toString()}&_=${Date.now()}`);
      const data = await res.json();
      if (res.ok && data && data.success && typeof data.is_first_time === 'boolean') {
        isFirstTimePatient = data.is_first_time;
      }
    } catch (err) {
      console.warn('Could not check patient visit history:', err);
    }
    applyPatientTypeModeLock();
  }

  if (cardModeTele) {
    cardModeTele.addEventListener('click', (e) => {
      if (!isFirstTimePatient) return;
      e.preventDefault();
      if (radioModeF2F) radioModeF2F.checked = true;
      if (radioModeTele) radioModeTele.checked = false;
      updateModeCards();
      showAlert('First-time patients are required to have an initial In-Person (Face-to-Face) consultation. Teleconsultation is only available for returning patients.');
    });
  }

  modeRadios.forEach((r) => r.addEventListener('change', () => {
    if (isFirstTimePatient && r.value === 'Teleconsultation' && r.checked) {
      if (radioModeF2F) radioModeF2F.checked = true;
      r.checked = false;
      updateModeCards();
      showAlert('First-time patients are required to have an initial In-Person (Face-to-Face) consultation. Teleconsultation is only available for returning patients.');
      return;
    }
    updateModeCards();
  }));

  function updateSlotCards() {
    const val = document.querySelector('input[name="preferred_time_slot"]:checked')?.value || '';
    if (slotMorningCard) slotMorningCard.classList.toggle('is-selected', val.startsWith('Morning'));
    if (slotAfternoonCard) slotAfternoonCard.classList.toggle('is-selected', val.startsWith('Afternoon'));
    const errEl = document.getElementById('errTimeSlot');
    if (errEl && val) {
      errEl.closest('.ba-field')?.classList.remove('has-error');
    }
  }

  timeSlotRadios.forEach((r) => r.addEventListener('change', updateSlotCards));

  function isInquiryMode() {
    return Boolean(inquiryOnlyCheckbox && inquiryOnlyCheckbox.checked);
  }

  function applyInquiryModeUI() {
    const inquiry = isInquiryMode();
    const inquiryBox = document.getElementById('inquiryBadgeBtn');
    const recipientSecLbl = document.getElementById('recipientSectionLabel');
    const recipientMyselfSub = document.getElementById('recipientMyselfSub');
    const recipientOtherSub = document.getElementById('recipientOtherSub');
    const step2Heading = document.getElementById('step2Heading');
    const step2Subtitle = document.getElementById('step2Subtitle');
    const lblComplaint = document.getElementById('lblChiefComplaints');
    const stepInd3 = document.getElementById('stepIndicator3');
    const step3Lbl = document.getElementById('step3SidebarLabel');
    const step3Notice = document.getElementById('inquiryStep3Notice');
    const step3Schedule = document.getElementById('step3ScheduleSection');

    if (inquiryBox) inquiryBox.classList.toggle('is-checked', inquiry);
    if (recipientSecLbl) {
      recipientSecLbl.innerHTML = inquiry
        ? 'INQUIRY RECIPIENT <span class="req-mark">*</span>'
        : 'APPOINTMENT RECIPIENT <span class="req-mark">*</span>';
    }
    if (recipientMyselfSub) {
      recipientMyselfSub.textContent = inquiry ? 'I am inquiring for myself' : 'I am booking for myself';
    }
    if (recipientOtherSub) {
      recipientOtherSub.textContent = inquiry ? 'Inquiring for someone else' : 'Booking for someone else';
    }
    if (step2SidebarLabel) {
      step2SidebarLabel.textContent = inquiry
        ? 'Inquiry Details'
        : (currentStep === 4 ? 'Medical Details' : 'Medical Information');
    }
    if (stepInd3) stepInd3.classList.toggle('is-skipped', inquiry);
    if (step3Lbl) {
      step3Lbl.textContent = inquiry ? 'Appointment Details (Skipped)' : 'Appointment Details';
    }
    if (step2Heading) {
      step2Heading.textContent = inquiry ? 'INQUIRY DETAILS' : 'MEDICAL INFORMATION';
    }
    if (step2Subtitle) {
      step2Subtitle.textContent = inquiry
        ? 'Please write your question or concern for the selected department.'
        : 'Help our assessment team identify the services you need.';
    }
    if (lblComplaint) {
      lblComplaint.innerHTML = inquiry
        ? 'INQUIRY QUESTION / CONCERN <span class="req-mark">*</span>'
        : 'CHIEF COMPLAINT / REASON FOR CONSULTATION <span class="req-mark">*</span>';
    }
    if (inputChiefComplaints) {
      inputChiefComplaints.required = true;
      inputChiefComplaints.placeholder = inquiry
        ? 'Describe your inquiry or question for the department...'
        : 'Describe your symptoms or reason for visit...';
    }
    if (step3Notice) step3Notice.style.display = inquiry ? 'block' : 'none';
    if (step3Schedule) step3Schedule.style.display = inquiry ? 'none' : 'block';
    if (inputAppointmentDate) inputAppointmentDate.required = !inquiry;
    if (btnNextLabel && !isSubmitting) {
      btnNextLabel.textContent = currentStep === 4 ? (inquiry ? 'Submit Inquiry' : 'Confirm') : 'Next';
    }
  }

  if (inquiryOnlyCheckbox) {
    inquiryOnlyCheckbox.addEventListener('change', () => {
      applyInquiryModeUI();
      if (isInquiryMode()) {
        showAlert('Inquiry Only mode enabled: You can submit a general inquiry without reserving a date or time slot.', false);
      } else {
        clearAlert();
      }
    });
  }

  const validators = {
    inputFirstName: () => (inputFirstName.value.trim() ? '' : 'First name is required.'),
    inputLastName: () => (inputLastName.value.trim() ? '' : 'Last name is required.'),
    selectGender: () => (selectGender.value ? '' : 'Please select gender.'),
    inputBirthdate: () => {
      const val = inputBirthdate.value;
      if (!val) return 'Birthdate is required.';
      const b = new Date(val + 'T00:00:00');
      const today = new Date();
      today.setHours(23, 59, 59, 999);
      if (Number.isNaN(b.getTime()) || b > today) return 'Enter a valid birthdate not in the future.';
      return '';
    },
    inputContactNumber: () => {
      const digits = inputContactNumber.value.replace(/\D/g, '');
      if (!digits) return 'Contact number is required.';
      if (!/^9\d{9}$/.test(digits)) return 'Enter a valid 10-digit mobile number starting with 9.';
      return '';
    },
    inputEmail: () => {
      const val = inputEmail.value.trim();
      if (!val || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
        return 'Enter a valid email address.';
      }
      return '';
    },
    inputAddress: () => (inputAddress.value.trim() ? '' : 'Home address is required.'),
    departmentSelect: () => (departmentSelect.value ? '' : 'Please select a department.'),
    inputChiefComplaints: () => {
      if (inputChiefComplaints.value.trim()) return '';
      return isInquiryMode()
        ? 'Please enter your inquiry question or concern.'
        : 'Please describe your chief complaint or reason for visit.';
    }
  };

  Object.keys(validators).forEach((id) => {
    const el = document.getElementById(id);
    if (!el) return;
    const evt = el.tagName === 'SELECT' ? 'change' : 'blur';
    el.addEventListener(evt, () => setFieldError(el, validators[id]()));
    el.addEventListener('input', () => {
      if (el.closest('.ba-field')?.classList.contains('has-error')) {
        setFieldError(el, validators[id]());
      }
    });
  });

  function validateStep1() {
    const checks = [
      { el: inputFirstName, fn: validators.inputFirstName },
      { el: inputLastName, fn: validators.inputLastName },
      { el: selectGender, fn: validators.selectGender },
      { el: inputBirthdate, fn: validators.inputBirthdate },
      { el: inputContactNumber, fn: validators.inputContactNumber },
      { el: inputEmail, fn: validators.inputEmail },
      { el: inputAddress, fn: validators.inputAddress },
      { el: departmentSelect, fn: validators.departmentSelect }
    ];

    let firstInvalid = null;
    checks.forEach(({ el, fn }) => {
      const msg = fn();
      setFieldError(el, msg);
      if (msg && !firstInvalid) firstInvalid = el;
    });

    if (firstInvalid) {
      firstInvalid.focus();
      showAlert('Please complete all required fields highlighted below.');
      return false;
    }
    return true;
  }

  function validateStep2() {
    const msg = validators.inputChiefComplaints();
    setFieldError(inputChiefComplaints, msg);
    if (msg) {
      inputChiefComplaints.focus();
      showAlert(msg);
      return false;
    }
    return true;
  }

  function validateStep3() {
    if (isInquiryMode()) {
      return true;
    }

    const modeVal = document.querySelector('input[name="consultation_mode"]:checked')?.value || '';
    const modeErrEl = document.getElementById('errConsultationMode');
    if (!modeVal) {
      if (modeErrEl) {
        const t = modeErrEl.querySelector('.ba-error-text');
        if (t) t.textContent = 'Select a consultation mode.';
        modeErrEl.closest('.ba-field')?.classList.add('has-error');
      }
      showAlert('Please select a consultation mode.');
      return false;
    }
    if (isFirstTimePatient && modeVal === 'Teleconsultation') {
      applyPatientTypeModeLock();
      showAlert('First-time patients are required to have an initial In-Person (Face-to-Face) consultation. Teleconsultation is only available for returning patients.');
      return false;
    }
    if (modeErrEl) modeErrEl.closest('.ba-field')?.classList.remove('has-error');

    const dt = inputAppointmentDate.value;
    if (!dt || !/^\d{4}-\d{2}-\d{2}$/.test(dt)) {
      setFieldError(inputAppointmentDate, 'Select a preferred appointment date.');
      inputAppointmentDate.focus();
      showAlert('Please select your preferred date.');
      return false;
    }

    const minStr = inputAppointmentDate.min;
    const maxStr = inputAppointmentDate.max;
    if (minStr && dt < minStr) {
      setFieldError(inputAppointmentDate, 'Appointments must be booked at least 1 day ahead.');
      inputAppointmentDate.focus();
      showAlert('Selected date must be at least 1 day in advance.');
      return false;
    }
    if (maxStr && dt > maxStr) {
      setFieldError(inputAppointmentDate, 'Appointments cannot be scheduled more than 1 year ahead.');
      inputAppointmentDate.focus();
      showAlert('Selected date is beyond the 1-year booking limit.');
      return false;
    }

    const parts = dt.split('-');
    const chosenDate = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
    if (chosenDate.getDay() === 0) {
      setFieldError(inputAppointmentDate, 'The clinic is closed on Sundays. Please choose Monday to Saturday.');
      inputAppointmentDate.focus();
      showAlert('Outpatient clinic is closed on Sundays. Please pick Monday to Saturday.');
      return false;
    }
    setFieldError(inputAppointmentDate, '');

    const slotVal = document.querySelector('input[name="preferred_time_slot"]:checked')?.value || '';
    const slotErrEl = document.getElementById('errTimeSlot');
    if (!slotVal) {
      if (slotErrEl) {
        const t = slotErrEl.querySelector('.ba-error-text');
        if (t) t.textContent = 'Select a preferred time slot (Morning or Afternoon).';
        slotErrEl.closest('.ba-field')?.classList.add('has-error');
      }
      showAlert('Please select a preferred time slot.');
      return false;
    }
    if (slotErrEl) slotErrEl.closest('.ba-field')?.classList.remove('has-error');

    return true;
  }

  function formatReadableDate(isoDate) {
    if (!isoDate || !/^\d{4}-\d{2}-\d{2}$/.test(isoDate)) return isoDate || '-';
    const parts = isoDate.split('-');
    const dt = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
    return dt.toLocaleDateString('en-US', {
      weekday: 'short',
      month: 'short',
      day: 'numeric',
      year: 'numeric'
    });
  }

  function populateReviewStep() {
    const recipient = document.querySelector('input[name="booking_for"]:checked')?.value || 'Patient (Me)';
    const mi = inputMiddleInitial.value.trim();
    const fullName = `${inputFirstName.value.trim()} ${mi ? mi + ' ' : ''}${inputLastName.value.trim()}`.replace(/\s+/g, ' ');
    const companion = document.querySelector('input[name="with_companion"]:checked')?.value === 'no' ? 'No' : 'Yes';
    const rel = selectRelationshipToPatient?.value || '';

    document.getElementById('revRecipient').textContent = recipient;
    document.getElementById('revName').textContent = fullName || '-';
    document.getElementById('revGender').textContent = selectGender.value || '-';
    document.getElementById('revBirthAge').textContent = `${inputBirthdate.value || '-'} (${inputAge.value !== '' ? inputAge.value + ' yrs' : '-'})`;
    document.getElementById('revPhone').textContent = `+63 ${inputContactNumber.value.trim()}`;
    document.getElementById('revEmail').textContent = inputEmail.value.trim() || '-';
    document.getElementById('revAddress').textContent = inputAddress.value.trim() || '-';
    document.getElementById('revCompanion').textContent = companion;
    document.getElementById('revDept').textContent = departmentSelect.value || '-';

    const revRelRow = document.getElementById('revRelationshipRow');
    const revRelVal = document.getElementById('revRelationship');
    if (recipient === 'Other Patient' && rel) {
      if (revRelRow) revRelRow.style.display = 'flex';
      if (revRelVal) revRelVal.textContent = rel;
    } else if (revRelRow) {
      revRelRow.style.display = 'none';
    }

    const revMedicalSecTitle = document.getElementById('revMedicalSecTitle');
    const revComplaintLabel = document.getElementById('revComplaintLabel');
    const inquiry = isInquiryMode();

    if (revMedicalSecTitle) {
      revMedicalSecTitle.textContent = inquiry ? 'Inquiry Details' : 'Medical Details';
    }
    if (revComplaintLabel) {
      revComplaintLabel.textContent = inquiry ? 'Inquiry Question / Concern' : 'Chief Complaint / Reason for Consultation';
    }

    document.getElementById('revComplaint').textContent = inputChiefComplaints.value.trim() || '-';
    document.getElementById('revConditions').textContent = inputExistingConditions.value.trim() || 'None';
    document.getElementById('revMedications').textContent = inputCurrentMedications.value.trim() || 'None';
    document.getElementById('revReferral').textContent = inputReferralSource.value.trim() || 'None';

    const modeVal = document.querySelector('input[name="consultation_mode"]:checked')?.value || 'In-Person';
    const slotVal = document.querySelector('input[name="preferred_time_slot"]:checked')?.value || '';

    if (inquiry) {
      document.getElementById('revMode').textContent = 'General Inquiry (Online Portal)';
      document.getElementById('revDate').textContent = 'Not Required (Inquiry Only)';
      document.getElementById('revTimeSlot').textContent = 'No Clinic Slot Reserved — Staff will contact you';
    } else {
      document.getElementById('revMode').textContent = modeVal;
      document.getElementById('revDate').textContent = formatReadableDate(inputAppointmentDate.value);
      document.getElementById('revTimeSlot').textContent = slotVal || '-';
    }
  }

  function updateStepUI() {
    clearAlert();

    for (let i = 1; i <= totalSteps; i++) {
      const stepEl = document.getElementById(`phaseStep${i}`);
      const indEl = document.getElementById(`stepIndicator${i}`);

      if (stepEl) {
        stepEl.classList.toggle('active', i === currentStep);
      }

      if (indEl) {
        indEl.classList.remove('is-active', 'is-completed');
        if (currentStep === 4) {
          // On Review & Confirm, steps 1-3 are green and step 4 stays gray until Confirm is submitted
          if (i < 4) {
            indEl.classList.add('is-completed');
          }
          if (i === 4) {
            indEl.setAttribute('aria-current', 'step');
          } else {
            indEl.removeAttribute('aria-current');
          }
        } else {
          if (i < currentStep) {
            indEl.classList.add('is-completed');
            indEl.removeAttribute('aria-current');
          } else if (i === currentStep) {
            indEl.classList.add('is-active');
            indEl.setAttribute('aria-current', 'step');
          } else {
            indEl.removeAttribute('aria-current');
          }
        }
      }
    }

    if (step2SidebarLabel) {
      step2SidebarLabel.textContent = currentStep === 4 ? 'Medical Details' : 'Medical Information';
    }

    if (btnBack) {
      btnBack.style.display = currentStep > 1 ? 'inline-flex' : 'none';
    }

    if (actionButtonBar) {
      actionButtonBar.classList.toggle('step1-only', currentStep === 1);
    }

    if (btnNextLabel) {
      btnNextLabel.textContent = currentStep === 4 ? (isInquiryMode() ? 'Submit Inquiry' : 'Confirm') : 'Next';
    }

    if (btnNext) {
      btnNext.disabled = isSubmitting;
    }

    applyInquiryModeUI();
  }

  function setSubmitLoading(loading) {
    isSubmitting = loading;
    if (btnNextSpinner) btnNextSpinner.style.display = loading ? 'inline-block' : 'none';
    if (btnNextLabel) {
      btnNextLabel.textContent = loading ? 'Submitting...' : (currentStep === 4 ? (isInquiryMode() ? 'Submit Inquiry' : 'Confirm') : 'Next');
    }
    if (btnNext) btnNext.disabled = loading;
  }

  btnNext.addEventListener('click', async () => {
    if (isSubmitting) return;
    clearAlert();

    if (currentStep === 1) {
      if (!validateStep1()) return;
      currentStep = 2;
      updateStepUI();
      scrollFormToTop();
      return;
    }

    if (currentStep === 2) {
      if (!validateStep2()) return;
      if (isInquiryMode()) {
        populateReviewStep();
        currentStep = 4;
      } else {
        await refreshPatientFirstTimeStatus();
        currentStep = 3;
      }
      updateStepUI();
      scrollFormToTop();
      return;
    }

    if (currentStep === 3) {
      if (!validateStep3()) return;
      populateReviewStep();
      currentStep = 4;
      updateStepUI();
      scrollFormToTop();
      return;
    }

    if (currentStep === 4) {
      setSubmitLoading(true);

      const recipient = document.querySelector('input[name="booking_for"]:checked')?.value || 'Patient (Me)';
      const modeVal = document.querySelector('input[name="consultation_mode"]:checked')?.value || 'In-Person';
      const slotVal = document.querySelector('input[name="preferred_time_slot"]:checked')?.value || '';
      const scheduledTime = slotVal.startsWith('Afternoon') ? '13:00:00' : '08:00:00';

      const payload = {
        appointment_recipient: recipient,
        booking_for: recipient,
        relationship_to_patient: (selectRelationshipToPatient?.value || '').trim(),
        department: departmentSelect.value,
        inquiry_only: isInquiryMode() ? 1 : 0,
        first_name: inputFirstName.value.trim(),
        last_name: inputLastName.value.trim(),
        middle_initial: inputMiddleInitial.value.trim(),
        gender: selectGender.value,
        birthdate: inputBirthdate.value,
        age: inputAge.value,
        contact_number: '+63' + inputContactNumber.value.trim().replace(/^\+63/, ''),
        email: inputEmail.value.trim(),
        address: inputAddress.value.trim(),
        with_companion: document.querySelector('input[name="with_companion"]:checked')?.value || 'yes',
        chief_complaints: inputChiefComplaints.value.trim(),
        existing_medical_conditions: inputExistingConditions.value.trim(),
        current_medications: inputCurrentMedications.value.trim(),
        referral_source: inputReferralSource.value.trim(),
        consultation_mode: modeVal,
        appointment_date: isInquiryMode() ? '' : inputAppointmentDate.value,
        preferred_time_slot: isInquiryMode() ? '' : slotVal,
        scheduled_time: isInquiryMode() ? '08:00:00' : scheduledTime
      };

      try {
        const res = await fetch('/sdn/api/patient_book_appointment.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const result = await res.json();

        if (!res.ok || !result.success) {
          showAlert(result.message || 'Could not submit appointment. Please review your entries.');
          setSubmitLoading(false);
          return;
        }

        const successTitle = document.getElementById('successTitle');
        if (successTitle) successTitle.textContent = 'Appointment Submitted';
        if (btnNextSpinner) btnNextSpinner.style.display = 'none';
        if (btnNextLabel) btnNextLabel.textContent = 'Submitted';
        if (successModal) successModal.classList.remove('hidden-overlay');

        for (let i = 1; i <= totalSteps; i++) {
          if (i === 3 && isInquiryMode()) continue;
          const indEl = document.getElementById(`stepIndicator${i}`);
          if (indEl) {
            indEl.classList.remove('is-active');
            indEl.classList.add('is-completed');
          }
        }
      } catch (err) {
        console.error('Booking submission error:', err);
        showAlert('Unable to reach the server. Please check your connection and try again.');
        setSubmitLoading(false);
      }
    }
  });

  if (btnBack) {
    btnBack.addEventListener('click', () => {
      if (currentStep > 1) {
        currentStep = (currentStep === 4 && isInquiryMode()) ? 2 : currentStep - 1;
        updateStepUI();
        scrollFormToTop();
      }
    });
  }

  function jumpToStep(step) {
    if (step >= 1 && step < currentStep && !isSubmitting) {
      if (step === 3 && isInquiryMode()) return;
      currentStep = step;
      updateStepUI();
      scrollFormToTop();
    }
  }

  document.querySelectorAll('.ba-step-item').forEach((item) => {
    const step = Number(item.dataset.step);
    item.addEventListener('click', () => jumpToStep(step));
    item.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        jumpToStep(step);
      }
    });
  });

  const privacyOverlay = document.getElementById('privacyOverlay');
  const modalPrivacyScrollBox = document.getElementById('modalPrivacyScrollBox');
  const modalPrivacyConsentCheck = document.getElementById('modalPrivacyConsentCheck');
  const modalPrivacyLabel = document.getElementById('modalPrivacyLabel');
  const privacyHintText = document.getElementById('privacyHintText');
  const privacyScrollPill = document.getElementById('privacyScrollPill');
  const btnProceedBooking = document.getElementById('btnProceedBooking');

  let privacyModalScrolled = false;

  function unlockPrivacyConsent() {
    if (privacyModalScrolled) return;
    privacyModalScrolled = true;
    if (modalPrivacyConsentCheck) modalPrivacyConsentCheck.disabled = false;
    if (modalPrivacyLabel) modalPrivacyLabel.classList.add('unlocked');
    if (privacyHintText) privacyHintText.classList.add('hidden');
    if (privacyScrollPill) {
      privacyScrollPill.className = 'privacy-status-pill ready';
      privacyScrollPill.innerHTML = '<svg width="12" height="12" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> Ready to Consent';
    }
  }

  if (modalPrivacyScrollBox) {
    modalPrivacyScrollBox.addEventListener('scroll', () => {
      if (modalPrivacyScrollBox.scrollTop + modalPrivacyScrollBox.clientHeight >= modalPrivacyScrollBox.scrollHeight - 8) {
        unlockPrivacyConsent();
      }
    });
  }

  if (modalPrivacyConsentCheck) {
    modalPrivacyConsentCheck.addEventListener('change', () => {
      if (btnProceedBooking) {
        btnProceedBooking.disabled = !modalPrivacyConsentCheck.checked;
      }
    });
  }

  if (btnProceedBooking) {
    btnProceedBooking.addEventListener('click', () => {
      if (!modalPrivacyConsentCheck?.checked) return;
      if (privacyOverlay) privacyOverlay.classList.add('hidden-overlay');
      document.body.classList.remove('privacy-locked');
      if (inputFirstName) inputFirstName.focus();
    });
  }

  updateRecipientState();
  applyPatientTypeModeLock();
  updateSlotCards();
  updateStepUI();
});
