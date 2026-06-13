// modal-export.js

// 1. Populate the summary modal with form data safely
function populateSummary() {
  const now = new Date();
  const datePart = now.toISOString().slice(0, 10).replace(/-/g, "");
  const randPart = Math.floor(1000 + Math.random() * 9000);

  const refEl = document.getElementById("refNumber");
  if (refEl) refEl.textContent = `REG-${datePart}-${randPart}`;

  const dateEl = document.getElementById("submittedDate");
  if (dateEl) {
    dateEl.textContent = now.toLocaleString("en-PH", {
      year: "numeric", month: "long", day: "numeric", hour: "2-digit", minute: "2-digit"
    });
  }

  const deptVal = getVal("department");
  const deptMap = { psychiatry: "OPU Psychiatry", general: "General Management", neurology: "Neurology" };
  setText("p-department", deptMap[deptVal] || deptVal || "—");

  if (deptVal === "neurology") {
    setText("p-fullName", getVal("fullName") || "—");
    setText("p-gender", getVal("gender") || "—");
    setText("p-birthdate", formatDate(getVal("birthdate")) || "—");
    setText("p-age", getVal("age") || "—");
    setText("p-contact", getVal("contactNumber") || "—");
    setText("p-email", getVal("emailAddress") || "N/A");
    setText("p-address", getVal("address") || "—");

    const companionRadio = document.querySelector('input[name="companion"]:checked');
    setText("p-companion", companionRadio ? (companionRadio.value === "yes" ? "Yes" : "No") : "—");

    setText("p-complaints", getVal("chiefComplaints") || "—");
    setText("p-consultation", getVal("consultationAbout") || "—");
    setText("p-illness", getVal("illnessDescription") || "N/A");

    const ptMap = { "first-time": "First Time Patient", returning: "Returning Patient" };
    const atMap = { "walk-in": "Walk-in", online: "Online" };
    setText("p-patientType", ptMap[getVal("patientType")] || "—");
    setText("p-appointmentType", atMap[getVal("appointmentType")] || "—");
    setText("p-mode", getVal("consultationMode") || "—");
    setText("p-appointmentDate", formatDate(getVal("appointmentDate")) || "—");
    setText("p-referral", getVal("referralSource") || "N/A");
  } 
  else if (deptVal === "psychiatry") {
    setText("p-appointmentDate", getVal("appointmentDate") ? new Date(getVal("appointmentDate")).toLocaleString() : "—");
    setText("p-receiver", getVal("receiver") || "—");
    setText("p-appointmentType", getVal("appointmentType") || "—");
    setText("p-referralSource", getVal("referralSource") || "—");
    setText("p-fullName", getVal("fullName") || "—");
    
    const ptMap = { "first-time": "New", returning: "Old" };
    setText("p-patientType", ptMap[getVal("patientType")] || "—");
    setText("p-contactNumber", getVal("contactNumber") || "—");
    setText("p-informant", getVal("informant") || "—");
    setText("p-chiefComplaints", getVal("chiefComplaints") || "—");
    setText("p-recommendationRemarks", getVal("recommendationRemarks") || "—");
    
    const modeRadio = document.querySelector('input[name="consultationMode"]:checked');
    setText("p-consultationMode", modeRadio ? modeRadio.value : "—");
    
    setText("p-unscheduledWalkin", document.getElementById("unscheduledWalkin")?.checked ? "Yes" : "No");
    setText("p-inquiryOnly", document.getElementById("inquiryOnly")?.checked ? "Yes" : "No");
  }
  else if (deptVal === "general") {
    setText("p-inquiryOnly", document.getElementById("inquiryOnly")?.checked ? "Yes" : "No");
    setText("p-modeOfInquiry", getVal("modeOfInquiry") || "—");
    setText("p-employeeType", getVal("employeeType") || "—");
    setText("p-selectType", getVal("selectType") || "—");
    setText("p-fullName", getVal("fullName") || "—");
    setText("p-hrn", getVal("hrn") || "—");
    setText("p-birthdate", formatDate(getVal("birthdate")) || "—");
    setText("p-age", getVal("age") || "—");
    setText("p-gender", getVal("gender") || "—");
    setText("p-contactNumber", getVal("contactNumber") || "—");
    setText("p-complaint", getVal("complaint") || "—");
    setText("p-remarks", getVal("remarks") || "—");
    setText("p-specialty", getVal("specialty") || "—");
    setText("p-scheduledDate", formatDate(getVal("scheduledDate")) || "—");
    setText("p-scheduledTime", getVal("scheduledTime") || "—");
  }
}

// 2. Set textContent of a pdf-* element safely
function setPdf(id, value) {
  const el = document.getElementById(id);
  if (el) el.textContent = value;
}

// 3. Populate the hidden #pdfContainer
function populatePdfContainer() {
  const pdfBody = document.querySelector('.pdf-body-padding');
  const printBody = document.querySelector('.print-body');
  
  if (pdfBody && printBody) {
    // Dynamically clone the print body from the modal to ensure the PDF exactly matches what they see
    pdfBody.innerHTML = printBody.innerHTML;
  }

  const refEl = document.getElementById("refNumber");
  const dateEl = document.getElementById("submittedDate");
  setPdf("pdf-refNumber", refEl ? refEl.textContent : "");
  setPdf("pdf-submittedDate", dateEl ? dateEl.textContent : "");
}

// 4. Close and Confirm Logic
function closeAndConfirm() {
  const modal = document.getElementById("summaryModal");
  if (modal) {
    modal.classList.remove("show");
    setTimeout(() => {
      modal.style.display = "none";
      document.body.style.overflow = "";
    }, 250);
  }

  if (typeof showSuccessToast === "function") showSuccessToast();

  const regForm = document.getElementById("regForm");
  if (regForm) regForm.reset();

  const ageInput = document.getElementById("age");
  if (ageInput) ageInput.value = "";

  setTimeout(() => {
    window.location.href = "/index.php";
  }, 1200);
}

// 5. Attach Event Listeners
document.addEventListener("DOMContentLoaded", () => {
  const btnClose = document.getElementById("btnClose");
  if (btnClose) {
    btnClose.onclick = function (e) {
      e.preventDefault();
      closeAndConfirm();
    };
  }

  const summaryModal = document.getElementById("summaryModal");
  if (summaryModal) {
    summaryModal.addEventListener("click", (e) => {
      if (e.target === summaryModal) {
        closeAndConfirm();
      }
    });
  }

  const btnPrint = document.getElementById("btnPrint");
  if (btnPrint) {
    btnPrint.addEventListener("click", (e) => {
      e.preventDefault();
      window.print();
    });
  }

  const btnDownloadPDF = document.getElementById("btnDownloadPDF");
  if (btnDownloadPDF) {
    btnDownloadPDF.addEventListener("click", (e) => {
      e.preventDefault();
      const fullNameInput = document.getElementById("fullName");
      const patientName =
        fullNameInput && fullNameInput.value
          ? fullNameInput.value.replace(/\s+/g, "_")
          : "Patient";

      // Target the exact visible element in the modal to guarantee 100% accurate layout
      const element = document.getElementById("printArea");

      const opt = {
        margin: [10, 10, 10, 10],
        filename: `Registration_${patientName}.pdf`,
        image: { type: "jpeg", quality: 0.98 },
        html2canvas: {
          scale: 2,
          useCORS: true,
          logging: false,
          width: 794,
          windowWidth: 794,
          scrollX: 0,
          scrollY: 0,
          x: 0,
          y: 0,
        },
        jsPDF: { unit: "mm", format: "a4", orientation: "portrait" },
        pagebreak: { mode: "avoid-all" },
      };

      const originalText = btnDownloadPDF.innerHTML;
      btnDownloadPDF.innerHTML = "⏳ Generating...";
      btnDownloadPDF.disabled = true;

      html2pdf()
        .set(opt)
        .from(element)
        .save()
        .then(() => {
          btnDownloadPDF.innerHTML = originalText;
          btnDownloadPDF.disabled = false;
        })
        .catch((err) => {
          console.error("PDF Generation failed", err);
          btnDownloadPDF.innerHTML = originalText;
          btnDownloadPDF.disabled = false;
        });
    });
  }
});
