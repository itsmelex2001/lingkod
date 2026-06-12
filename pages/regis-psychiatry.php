<?php session_start(); ?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>OPU Psychiatry Registration</title>
    <link rel="stylesheet" href="/sdn/public/css/global.css?v=9" />
    <link rel="stylesheet" href="/sdn/public/css/navigation.css?v=9" />
    <link rel="stylesheet" href="/sdn/public/css/forms.css" />
    <link rel="stylesheet" href="/sdn/public/css/modal-export.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <link rel="stylesheet" href="/sdn/public/css/regis-psychiatry.css" />
  </head>
  <body>
    <div style="max-width: 1000px; width: 90%; margin: 2rem auto 0;">
      <?php
      if (isset($_GET['source']) && $_GET['source'] === 'home') {
          $backUrl = '/sdn/index.php';
      } elseif (isset($_GET['source']) && $_GET['source'] === 'schedules') {
          $backUrl = '/sdn/pages/schedules.php';
      } else {
          $backUrl = '/sdn/pages/department-select.php';
      }
      ?>
      <a href="<?= htmlspecialchars($backUrl) ?>" class="btn-back">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
      </a>
    </div>
    <div class="registration-container" style="margin-top: 1rem;">
      <h2>OPU Psychiatry Registration</h2>

      <form id="regForm">
        <input type="hidden" id="department" value="psychiatry" />

        <div class="form-section">
          <h3>Inquiry Details</h3>
          
          <div class="grid-row">
            <div class="form-group">
              <label>Date <span class="asterisk">*</span></label>
              <input type="date" id="appointmentDate" min="<?= date('Y-m-d') ?>" required />
            </div>
            <div class="form-group">
              <label>Time <span class="asterisk">*</span></label>
              <input type="time" id="appointmentTime" required />
            </div>
          </div>
          <div class="form-group">
            <label>Receiver</label>
            <input type="text" id="receiver" placeholder="Staff Name" maxlength="100" />
          </div>

          <div class="grid-row">
            <div class="form-group">
              <label>Type <span class="asterisk">*</span></label>
              <select id="appointmentType" required>
                <option value="" disabled selected>Select type...</option>
                <option>Received Call</option>
                <option>Walk-In</option>
                <option>Referral</option>
              </select>
            </div>
            <div class="form-group">
              <label>Referral Source</label>
              <input type="text" id="referralSource" placeholder="e.g. OPU Med" maxlength="100" />
            </div>
          </div>
        </div>

        <div class="form-section">
          <h3>Patient Information</h3>
          
          <div class="grid-row">
            <div class="form-group">
              <label>Name <span class="asterisk">*</span></label>
              <input type="text" id="fullName" placeholder="Enter patient name" maxlength="100" required />
            </div>
            <div class="form-group">
              <label>HRN</label>
              <input type="text" id="hrn" placeholder="Auto-generated" readonly />
            </div>
          </div>

          <div class="grid-row">
            <div class="form-group">
              <label>New / Old Patient <span class="asterisk">*</span></label>
              <select id="patientType" required>
                <option value="" disabled selected>Select...</option>
                <option value="first-time">New</option>
                <option value="returning">Old</option>
              </select>
            </div>
            <div class="form-group">
              <label>Contact No <span class="asterisk">*</span></label>
              <input type="tel" id="contactNumber" placeholder="Contact number" pattern="^(09|\+639)\d{9}$" maxlength="13" title="Format: 09XXXXXXXXX or +639XXXXXXXXX" required />
            </div>
          </div>

          <div class="form-group">
            <label>Informant</label>
            <input type="text" id="informant" placeholder="Name of informant (if any)" maxlength="100" />
          </div>

          <div class="form-group">
            <label>Health Concerns <span class="asterisk">*</span></label>
            <textarea id="chiefComplaints" rows="3" maxlength="1000" required></textarea>
          </div>

          <div class="form-group">
            <label>Recommendation / Remarks</label>
            <textarea id="recommendationRemarks" rows="3" maxlength="1000"></textarea>
          </div>

          <!-- Quick Schedule Box -->
          <div class="quick-schedule-box">
            <h4>Quick Schedule</h4>
            <div class="quick-schedule-options">
              <label><input type="radio" name="consultationMode" value="Face-to-Face" required> Face to Face</label>
              <label><input type="radio" name="consultationMode" value="Teleconsultation" required> Teleconsultation</label>
              <label><input type="radio" name="consultationMode" value="Admission" required> Admission</label>
            </div>
          </div>

          <div class="checkbox-group">
            <label><input type="checkbox" id="unscheduledWalkin" /> Unscheduled / Walk-In</label>
            <label><input type="checkbox" id="inquiryOnly" /> Inquiry Only</label>
          </div>

        </div>

        <p class="required-legend"><span class="asterisk">*</span> Indicates a required field</p>
        <button type="submit" class="btn-submit">Confirm</button>
      </form>
    </div>

    <!-- MODAL CONFIRMATION -->
    <div id="summaryModal" class="modal-overlay">
      <div class="modal-box">
        <div id="printArea">
          <div class="print-header">
            <div class="print-header-inner">
              <div class="print-header-badge">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
              </div>
              <div class="print-header-content">
                <h1 class="print-clinic-name">OPU Psychiatry Registration</h1>
                <div class="print-meta-row">
                  <p class="print-ref">Ref No: <strong id="refNumber"></strong></p>
                  <span class="print-meta-sep">·</span>
                  <p class="print-date">Submitted: <strong id="submittedDate"></strong></p>
                </div>
              </div>
            </div>
          </div>

          <div class="print-body">
            <div class="print-section">
              <div class="print-section-title">Inquiry Details</div>
              <div class="print-row">
                <div class="print-field">
                  <span class="print-label">Date / Time</span>
                  <span class="print-value" id="p-appointmentDate"></span>
                </div>
                <div class="print-field">
                  <span class="print-label">Receiver</span>
                  <span class="print-value" id="p-receiver"></span>
                </div>
              </div>
              <div class="print-row">
                <div class="print-field">
                  <span class="print-label">Type</span>
                  <span class="print-value" id="p-appointmentType"></span>
                </div>
                <div class="print-field">
                  <span class="print-label">Referral Source</span>
                  <span class="print-value" id="p-referralSource"></span>
                </div>
              </div>
            </div>

            <div class="print-section">
              <div class="print-section-title">Patient Information</div>
              <div class="print-row">
                <div class="print-field full">
                  <span class="print-label">Name</span>
                  <span class="print-value" id="p-fullName"></span>
                </div>
              </div>
              <div class="print-row">
                <div class="print-field">
                  <span class="print-label">New / Old</span>
                  <span class="print-value" id="p-patientType"></span>
                </div>
                <div class="print-field">
                  <span class="print-label">Contact No</span>
                  <span class="print-value" id="p-contactNumber"></span>
                </div>
              </div>
              <div class="print-row">
                <div class="print-field full">
                  <span class="print-label">Informant</span>
                  <span class="print-value" id="p-informant"></span>
                </div>
              </div>
              <div class="print-row">
                <div class="print-field full">
                  <span class="print-label">Health Concerns</span>
                  <span class="print-value" id="p-chiefComplaints"></span>
                </div>
              </div>
              <div class="print-row">
                <div class="print-field full">
                  <span class="print-label">Recommendation / Remarks</span>
                  <span class="print-value" id="p-recommendationRemarks"></span>
                </div>
              </div>
            </div>

            <div class="print-section">
              <div class="print-section-title">Schedule Options</div>
              <div class="print-row">
                <div class="print-field">
                  <span class="print-label">Consultation Mode</span>
                  <span class="print-value" id="p-consultationMode"></span>
                </div>
                <div class="print-field">
                  <span class="print-label">Unscheduled / Walk-in</span>
                  <span class="print-value" id="p-unscheduledWalkin"></span>
                </div>
              </div>
              <div class="print-row">
                <div class="print-field">
                  <span class="print-label">Inquiry Only</span>
                  <span class="print-value" id="p-inquiryOnly"></span>
                </div>
              </div>
            </div>
            
            <div class="print-footer-note">
              This registration form is for medical facility use only. Please present this document upon arrival.
            </div>
          </div>
        </div>

        <div class="modal-actions no-print">
          <div class="modal-optional-row">
            <span class="modal-optional-label">Optional — save a copy</span>
            <div class="modal-optional-btns">
              <button id="btnDownloadPDF" class="btn-export">Download PDF</button>
              <button id="btnPrint" class="btn-export">Print</button>
            </div>
          </div>

          <button id="btnClose" class="btn-confirm-close">
            Confirm
          </button>
        </div>
      </div>
    </div>

    <div id="successToast" class="success-toast">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="20 6 9 17 4 12"/></svg>
      <div>
        <strong>Registration Confirmed!</strong>
        <span>Your registration has been successfully submitted.</span>
      </div>
    </div>

    <!-- PDF EXPORT CONTAINER -->
    <div id="pdfContainer">
      <div id="pdf-header">
        <div class="pdf-header-logo">+</div>
        <div>
          <div class="pdf-header-title">OPU Psychiatry Registration</div>
          <div class="pdf-header-meta">Reference No: <strong id="pdf-refNumber"></strong></div>
          <div class="pdf-header-meta">Date Submitted: <strong id="pdf-submittedDate"></strong></div>
        </div>
      </div>
      <div class="pdf-body-padding">
         <!-- The modal-export.js will clone the print-body contents here when printing -->
      </div>
    </div>

    <script src="/sdn/public/js/utils.js"></script>
    <script src="/sdn/public/js/navigation.js"></script>
    <script src="/sdn/public/js/print-pdf-setup.js"></script>
    <script src="/sdn/public/js/regis-psychiatry.js"></script>
    <script src="/sdn/public/js/modal-export.js"></script>
  </body>
</html>
