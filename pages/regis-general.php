<?php session_start(); ?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>General Management Registration</title>
    <link rel="stylesheet" href="/sdn/public/css/global.css?v=9" />
    <link rel="stylesheet" href="/sdn/public/css/navigation.css?v=9" />
    <link rel="stylesheet" href="/sdn/public/css/forms.css" />
    <link rel="stylesheet" href="/sdn/public/css/modal-export.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <link rel="stylesheet" href="/sdn/public/css/regis-general.css" />
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
      <h2>General Management Registration</h2>

      <form id="regForm">
        <input type="hidden" id="department" value="general" />

        <div class="checkbox-right">
          <label><input type="checkbox" id="inquiryOnly" /> Inquiry Only</label>
        </div>

        <div class="form-section">
          <h3>Inquiry Details</h3>
          
          <div class="grid-row">
            <div class="form-group">
              <label>Mode of Inquiry <span class="asterisk">*</span></label>
              <select id="modeOfInquiry" required>
                <option value="" disabled selected>Select mode...</option>
                <option>Direct</option>
                <option>Call</option>
                <option>Referral</option>
              </select>
            </div>
            <div class="form-group">
              <label>Employee Type <span class="asterisk">*</span></label>
              <select id="employeeType" required>
                <option value="" disabled selected>Select employee type...</option>
                <option>Non-Employee</option>
                <option>Employee</option>
                <option>Dependent</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label>Select Type <span class="asterisk">*</span></label>
            <select id="selectType" required>
              <option value="" disabled selected>Select...</option>
              <option>Unschedule/Walk In</option>
              <option>Scheduled</option>
            </select>
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
              <label>Birthdate <span class="asterisk">*</span></label>
              <input type="date" id="birthdate" max="<?= date('Y-m-d') ?>" required />
            </div>
            <div class="form-group">
              <label>Age</label>
              <input type="number" id="age" placeholder="Age" readonly />
            </div>
          </div>

          <div class="grid-row">
            <div class="form-group">
              <label>Gender <span class="asterisk">*</span></label>
              <select id="gender" required>
                <option value="" disabled selected>Select gender...</option>
                <option>Male</option>
                <option>Female</option>
                <option>Other</option>
              </select>
            </div>
            <div class="form-group">
              <label>Contact No/s. <span class="asterisk">*</span></label>
              <input type="tel" id="contactNumber" placeholder="09XXXXXXXXX" pattern="^(09|\+639)\d{9}$" maxlength="13" title="Format: 09XXXXXXXXX or +639XXXXXXXXX" required />
            </div>
          </div>

          <div class="form-group">
            <label>Health Concerns <span class="asterisk">*</span></label>
            <textarea id="complaint" rows="3" maxlength="1000" required></textarea>
          </div>

          <div class="form-group">
            <label>Remarks</label>
            <textarea id="remarks" rows="2"></textarea>
          </div>
        </div>

        <div class="form-section">
          <h3>Schedule Details</h3>
          
          <div class="form-group">
            <label>Specialty</label>
            <select id="specialty">
              <option value="" disabled selected>Select Specialty</option>
              <option>Internal Medicine</option>
              <option>Family Medicine</option>
              <option>Pediatrics</option>
              <option>Surgery</option>
            </select>
          </div>

          <div class="grid-row">
            <div class="form-group">
              <label>Scheduled Date <span class="asterisk">*</span></label>
              <input type="date" id="scheduledDate" min="<?= date('Y-m-d') ?>" required />
            </div>
            <div class="form-group">
              <label>Scheduled Time <span class="asterisk">*</span></label>
              <input type="time" id="scheduledTime" required />
            </div>
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
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 12h-4l-3 9L9 3l-3 9H2"></path></svg>
              </div>
              <div class="print-header-content">
                <h1 class="print-clinic-name">General Management Registration</h1>
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
                  <span class="print-label">Inquiry Only</span>
                  <span class="print-value" id="p-inquiryOnly"></span>
                </div>
                <div class="print-field">
                  <span class="print-label">Mode of Inquiry</span>
                  <span class="print-value" id="p-modeOfInquiry"></span>
                </div>
              </div>
              <div class="print-row">
                <div class="print-field">
                  <span class="print-label">Employee Type</span>
                  <span class="print-value" id="p-employeeType"></span>
                </div>
                <div class="print-field">
                  <span class="print-label">Select Type</span>
                  <span class="print-value" id="p-selectType"></span>
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
                  <span class="print-label">HRN</span>
                  <span class="print-value" id="p-hrn"></span>
                </div>
                <div class="print-field">
                  <span class="print-label">Birthdate</span>
                  <span class="print-value" id="p-birthdate"></span>
                </div>
              </div>
              <div class="print-row">
                <div class="print-field">
                  <span class="print-label">Age</span>
                  <span class="print-value" id="p-age"></span>
                </div>
                <div class="print-field">
                  <span class="print-label">Gender</span>
                  <span class="print-value" id="p-gender"></span>
                </div>
              </div>
              <div class="print-row">
                <div class="print-field">
                  <span class="print-label">Contact No/s.</span>
                  <span class="print-value" id="p-contactNumber"></span>
                </div>
              </div>
              <div class="print-row">
                <div class="print-field full">
                  <span class="print-label">Health Concerns</span>
                  <span class="print-value" id="p-complaint"></span>
                </div>
              </div>
              <div class="print-row">
                <div class="print-field full">
                  <span class="print-label">Remarks</span>
                  <span class="print-value" id="p-remarks"></span>
                </div>
              </div>
            </div>

            <div class="print-section">
              <div class="print-section-title">Schedule Details</div>
              <div class="print-row">
                <div class="print-field full">
                  <span class="print-label">Specialty</span>
                  <span class="print-value" id="p-specialty"></span>
                </div>
              </div>
              <div class="print-row">
                <div class="print-field">
                  <span class="print-label">Scheduled Date</span>
                  <span class="print-value" id="p-scheduledDate"></span>
                </div>
                <div class="print-field">
                  <span class="print-label">Scheduled Time</span>
                  <span class="print-value" id="p-scheduledTime"></span>
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
          <div class="pdf-header-title">General Management Registration</div>
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
    <script src="/sdn/public/js/regis-general.js"></script>
    <script src="/sdn/public/js/modal-export.js"></script>
  </body>
</html>
