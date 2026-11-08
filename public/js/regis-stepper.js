// regis-stepper.js — 3-Phase Multi-Step Proceeding Controller for Patient Registration

(function () {
  let currentPhase = 1;
  const totalPhases = 3;

  function initStepper() {
    const regForm = document.getElementById("regForm");
    if (!regForm) return;

    // Use JS validation instead of default browser popup on hidden elements
    regForm.setAttribute("novalidate", "true");

    const btnNext = document.getElementById("btnNextPhase");
    const btnPrev = document.getElementById("btnPrevPhase");
    const btnSubmit = document.getElementById("btnSubmitPhase") || regForm.querySelector('button[type="submit"]');
    const stepperFill = document.getElementById("stepperLineFill");
    const stepperNodes = document.querySelectorAll(".stepper-node");
    const phasePanels = document.querySelectorAll(".form-phase");

    if (!btnNext || phasePanels.length === 0) return;

    function updateStepperUI(phase) {
      currentPhase = Math.max(1, Math.min(phase, totalPhases));

      // Update Phase Panels
      phasePanels.forEach((panel) => {
        const pNum = parseInt(panel.dataset.phase, 10);
        if (pNum === currentPhase) {
          panel.classList.add("active");
          panel.style.display = "block";
        } else {
          panel.classList.remove("active");
          panel.style.display = "none";
        }
      });

      // Update Stepper Dots & Labels
      stepperNodes.forEach((node) => {
        const sNum = parseInt(node.dataset.step, 10);
        node.classList.remove("active", "completed", "clickable");

        if (sNum === currentPhase) {
          node.classList.add("active");
        } else if (sNum < currentPhase) {
          node.classList.add("completed", "clickable");
        }
      });

      // Update connecting line fill
      if (stepperFill) {
        if (currentPhase === 1) stepperFill.style.width = "0%";
        else if (currentPhase === 2) stepperFill.style.width = "50%";
        else if (currentPhase === 3) stepperFill.style.width = "100%";
      }

      // Update Previous Button
      if (btnPrev) {
        btnPrev.style.display = currentPhase > 1 ? "inline-flex" : "none";
      }

      // Update Next / Submit Buttons
      if (btnNext && btnSubmit) {
        if (currentPhase < totalPhases) {
          btnNext.style.display = "inline-flex";
          btnSubmit.style.display = "none";

          const nextLabel = btnNext.querySelector("span");
          if (nextLabel) {
            if (currentPhase === 1) {
              nextLabel.textContent = "Continue to Medical Information";
            } else if (currentPhase === 2) {
              nextLabel.textContent = "Continue to Appointment Details";
            }
          }
        } else {
          btnNext.style.display = "none";
          btnSubmit.style.display = "inline-flex";
        }
      }
    }

    // Helper: Mark field error
    function markFieldErrorLocal(el, msg) {
      if (typeof window.markFieldError === "function") {
        window.markFieldError(el, msg);
        return;
      }
      el.classList.add("is-invalid");
      const parent = el.closest(".form-group");
      if (parent) {
        parent.classList.add("has-error");
        let msgSpan = parent.querySelector(".field-error-msg");
        if (!msgSpan) {
          msgSpan = document.createElement("span");
          msgSpan.className = "field-error-msg";
          parent.appendChild(msgSpan);
        }
        msgSpan.textContent = msg;
      }
    }

    // Helper: Clear field error
    function clearFieldErrorLocal(el) {
      if (typeof window.clearFieldError === "function") {
        window.clearFieldError(el);
        return;
      }
      el.classList.remove("is-invalid");
      const parent = el.closest(".form-group");
      if (parent) {
        parent.classList.remove("has-error");
        const msgSpan = parent.querySelector(".field-error-msg");
        if (msgSpan) msgSpan.remove();
      }
    }

    // Validate a specific phase panel
    function validatePhase(phaseNum) {
      const panel = document.getElementById("phase" + phaseNum);
      if (!panel) return true;

      let isValid = true;
      let firstInvalid = null;

      // Validate required inputs & selects
      const requiredFields = panel.querySelectorAll("input[required], select[required], textarea[required]");
      requiredFields.forEach((field) => {
        if (field.offsetParent === null) return; // skip if invisible
        const val = field.value ? field.value.trim() : "";
        if (!val) {
          isValid = false;
          markFieldErrorLocal(field, "This field is required.");
          if (!firstInvalid) firstInvalid = field;
        } else {
          clearFieldErrorLocal(field);
        }
      });

      // Special contact number check
      const contactInput = panel.querySelector("#contactNumber");
      if (contactInput && contactInput.value.trim()) {
        const val = contactInput.value.trim();
        const pattern = /^(09|\+639)\d{9}$/;
        if (!pattern.test(val)) {
          isValid = false;
          markFieldErrorLocal(contactInput, "Format: 09XXXXXXXXX or +639XXXXXXXXX");
          if (!firstInvalid) firstInvalid = contactInput;
        }
      }

      if (!isValid && firstInvalid) {
        firstInvalid.focus();
      }
      return isValid;
    }

    // Next button click
    btnNext.addEventListener("click", () => {
      if (validatePhase(currentPhase)) {
        updateStepperUI(currentPhase + 1);
      }
    });

    // Prev button click
    if (btnPrev) {
      btnPrev.addEventListener("click", () => {
        if (currentPhase > 1) {
          updateStepperUI(currentPhase - 1);
        }
      });
    }

    // Direct click on completed stepper nodes
    stepperNodes.forEach((node) => {
      node.addEventListener("click", () => {
        const targetPhase = parseInt(node.dataset.step, 10);
        if (targetPhase < currentPhase) {
          updateStepperUI(targetPhase);
        } else if (targetPhase === currentPhase + 1 && validatePhase(currentPhase)) {
          updateStepperUI(targetPhase);
        }
      });
    });

    // Clear error on input
    regForm.querySelectorAll("input, select, textarea").forEach((field) => {
      field.addEventListener("input", () => clearFieldErrorLocal(field));
      field.addEventListener("change", () => clearFieldErrorLocal(field));
    });

    // If submission fails due to an invalid field, auto-switch to that field's phase
    window.addEventListener("formValidationFailed", (e) => {
      if (e.detail && e.detail.field) {
        const parentPhase = e.detail.field.closest(".form-phase");
        if (parentPhase) {
          const pNum = parseInt(parentPhase.dataset.phase, 10);
          if (pNum) updateStepperUI(pNum);
        }
      }
    });

    window.jumpToPhase = updateStepperUI;

    // Start on Phase 1
    updateStepperUI(1);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initStepper);
  } else {
    initStepper();
  }
})();

