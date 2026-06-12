/**
 * print-pdf-setup.js
 *
 * Verification and enhancement for Print/PDF functionality
 * This file ensures all print and PDF features work correctly
 */

// Verify that all required libraries and functions exist
document.addEventListener("DOMContentLoaded", () => {
  console.log("🔍 Verifying Print & PDF Setup...");

  // Check for html2pdf library
  if (typeof html2pdf === "undefined") {
    console.error(
      '❌ html2pdf library not loaded. Make sure <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script> is in <head>',
    );
  } else {
    console.log("✅ html2pdf library loaded");
  }

  // Check for required utility functions
  const requiredFunctions = [
    "getVal",
    "setText",
    "formatDate",
    "showSuccessToast",
  ];
  requiredFunctions.forEach((fn) => {
    if (typeof window[fn] !== "function") {
      console.error(
        `❌ Missing function: ${fn}. Make sure utils.js is loaded before this script.`,
      );
    } else {
      console.log(`✅ Function available: ${fn}`);
    }
  });

  // Check for required DOM elements
  const requiredElements = [
    "regForm",
    "summaryModal",
    "printArea",
    "pdfContainer",
    "btnPrint",
    "btnDownloadPDF",
    "btnClose",
    "successToast",
  ];

  requiredElements.forEach((id) => {
    const el = document.getElementById(id);
    if (!el) {
      console.warn(`⚠️  Element not found: #${id}`);
    } else {
      console.log(`✅ Element found: #${id}`);
    }
  });

  // Verify modal styling
  const modal = document.getElementById("summaryModal");
  if (modal) {
    const styles = window.getComputedStyle(modal);
    console.log(`📋 Modal z-index: ${styles.zIndex}`);
    console.log(`📋 Modal display on hidden: ${styles.display}`);
  }

  console.log("✅ Print & PDF Setup Verification Complete");
});

/**
 * Enhanced print function with better error handling
 */
window.enhancedPrint = function () {
  const printArea = document.getElementById("printArea");
  if (!printArea) {
    alert("Error: Print area not found. Please try again.");
    return;
  }

  try {
    // Ensure modal is visible before printing
    const modal = document.getElementById("summaryModal");
    if (modal && modal.style.display !== "flex") {
      alert(
        "Error: Summary modal is not visible. Please confirm your registration first.",
      );
      return;
    }

    // Trigger print dialog
    window.print();
  } catch (err) {
    console.error("Print error:", err);
    alert(
      "Print failed. Please try again or use browser print shortcut (Ctrl+P / Cmd+P).",
    );
  }
};

/**
 * Enhanced PDF download with better error handling
 */
window.enhancedPdfDownload = function () {
  const pdfContainer = document.getElementById("pdfContainer");
  if (!pdfContainer) {
    alert("Error: PDF container not found.");
    return;
  }

  if (typeof html2pdf === "undefined") {
    alert("Error: PDF library not loaded. Please refresh the page.");
    return;
  }

  const fullNameInput = document.getElementById("fullName");
  const patientName =
    fullNameInput && fullNameInput.value
      ? fullNameInput.value.replace(/\s+/g, "_").substring(0, 30)
      : "Patient";

  const fileName = `Registration_${patientName}_${new Date().getTime()}.pdf`;

  const opt = {
    margin: [10, 10, 10, 10],
    filename: fileName,
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

  const btnDownloadPDF = document.getElementById("btnDownloadPDF");
  const originalHTML = btnDownloadPDF?.innerHTML || "Download PDF";

  if (btnDownloadPDF) {
    btnDownloadPDF.disabled = true;
    btnDownloadPDF.innerHTML = "⏳ Generating...";
  }

  // Ensure container is positioned off-screen
  pdfContainer.style.left = "-9999px";
  pdfContainer.style.top = "0";
  pdfContainer.style.position = "absolute";

  html2pdf()
    .set(opt)
    .from(pdfContainer)
    .save()
    .then(() => {
      console.log("✅ PDF downloaded successfully:", fileName);
      if (btnDownloadPDF) {
        btnDownloadPDF.innerHTML = originalHTML;
        btnDownloadPDF.disabled = false;
      }
    })
    .catch((err) => {
      console.error("❌ PDF generation failed:", err);
      alert("PDF generation failed. Please try again.");
      if (btnDownloadPDF) {
        btnDownloadPDF.innerHTML = originalHTML;
        btnDownloadPDF.disabled = false;
      }
    });
};

/**
 * Test function to verify all systems
 */
window.testPrintPdfSystem = function () {
  console.log("🧪 Testing Print/PDF System...");

  // Test 1: Check form data
  const formData = {
    department: getVal("department"),
    fullName: getVal("fullName"),
    gender: getVal("gender"),
    birthdate: getVal("birthdate"),
    contactNumber: getVal("contactNumber"),
  };

  console.log("📝 Form Data Sample:", formData);

  // Test 2: Check modal visibility
  const modal = document.getElementById("summaryModal");
  console.log("👁️  Modal Display:", modal?.style.display || "not displayed");

  // Test 3: Check PDF container
  const pdfContainer = document.getElementById("pdfContainer");
  console.log("📄 PDF Container Position:", {
    left: pdfContainer?.style.left,
    top: pdfContainer?.style.top,
    position: pdfContainer?.style.position,
  });

  console.log("✅ System test complete. Check console for details.");
};

console.log("✅ Print & PDF enhancement module loaded");
