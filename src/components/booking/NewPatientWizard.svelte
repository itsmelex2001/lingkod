<script>
  import { onMount } from 'svelte';

  // Wizard state: 1 = Personal Info, 2 = Account Registration, 3 = Confirmation
  let currentStep = 1;
  let isSubmitting = false;
  let submissionSuccess = false;
  let submissionData = null;
  let submissionError = '';

  // Phase 1: Personal Information
  export let userProfile = null;

  let bookingFor = 'Myself'; // 'Myself' | 'Someone Else / Dependent'
  let relationshipToPatient = '';

  let firstName = '';
  let lastName = '';
  let middleInitial = ''; // STRICTLY MANDATORY
  let gender = '';
  let birthdate = '';
  let age = '';
  let contactNumber = '';
  let guardianName = '';
  let guardianContactNumber = '';
  let isMinor = false;
  let email = '';
  let address = '';
  let withCompanion = 'No';

  // Saved profile cache for restoration when switching back to "Myself"
  let savedProfile = {
    firstName: '',
    lastName: '',
    middleInitial: '',
    gender: '',
    birthdate: '',
    age: '',
    contactNumber: '',
    email: '',
    address: ''
  };

  onMount(() => {
    if (userProfile) {
      savedProfile = {
        firstName: userProfile.first_name || userProfile.firstName || '',
        lastName: userProfile.last_name || userProfile.lastName || '',
        middleInitial: userProfile.middle_initial || userProfile.middleInitial || '',
        gender: userProfile.gender || '',
        birthdate: userProfile.birthdate || '',
        age: userProfile.age || '',
        contactNumber: userProfile.contact_number || userProfile.contactNumber || '',
        email: userProfile.email || '',
        address: userProfile.address || ''
      };
      restoreProfile();
    } else {
      savedProfile = {
        firstName, lastName, middleInitial, gender, birthdate, age, contactNumber, email, address
      };
    }
  });

  function restoreProfile() {
    firstName = savedProfile.firstName;
    lastName = savedProfile.lastName;
    middleInitial = savedProfile.middleInitial;
    gender = savedProfile.gender;
    birthdate = savedProfile.birthdate;
    age = savedProfile.age;
    contactNumber = savedProfile.contactNumber;
    email = savedProfile.email;
    address = savedProfile.address;
  }

  function handleBookingForChange(val) {
    bookingFor = val;
    if (val === 'Myself') {
      restoreProfile();
      relationshipToPatient = '';
    } else {
      // Clear dependent personal details, keep contact number & email of account owner
      firstName = '';
      lastName = '';
      middleInitial = '';
      gender = '';
      birthdate = '';
      age = '';
      address = '';
      withCompanion = 'Yes'; // Force companion
    }
  }

  // Reactive force companion when booking for someone else
  $: if (bookingFor === 'Someone Else / Dependent') {
    withCompanion = 'Yes';
  }

  // Phase 2: Account Registration
  let username = '';
  let password = '';
  let confirmPassword = '';
  let showPassword = false;
  let showConfirmPassword = false;
  let agreedTerms = true;

  // Validation errors
  let errors = {};

  // Compute age and minor status automatically from birthdate
  $: if (birthdate) {
    const b = new Date(birthdate);
    const today = new Date();
    if (!isNaN(b.getTime()) && b <= today) {
      let calcAge = today.getFullYear() - b.getFullYear();
      const m = today.getMonth() - b.getMonth();
      if (m < 0 || (m === 0 && today.getDate() < b.getDate())) {
        calcAge--;
      }
      age = calcAge >= 0 ? calcAge : '';
      isMinor = calcAge >= 0 && calcAge < 18;
    } else {
      age = '';
      isMinor = false;
    }
  } else {
    age = '';
    isMinor = false;
  }

  // Pre-fill username from email if left blank
  $: if (email && !username && currentStep === 1) {
    const parts = email.split('@');
    if (parts[0]) username = parts[0].replace(/[^a-zA-Z0-9_.]/g, '');
  }

  // Validate Phase 1
  function validatePhase1() {
    errors = {};
    if (!firstName.trim()) errors.firstName = 'First name is required.';
    if (!lastName.trim()) errors.lastName = 'Last name is required.';
    
    // Middle Initial is optional
    if (middleInitial.trim() && middleInitial.trim().length > 5) {
      errors.middleInitial = 'Max 5 characters.';
    }

    if (!gender) errors.gender = 'Please select a gender.';
    if (!birthdate) {
      errors.birthdate = 'Birthdate is required.';
    } else {
      const b = new Date(birthdate);
      if (b > new Date()) errors.birthdate = 'Birthdate cannot be in the future.';
    }

    if (!contactNumber.trim()) {
      errors.contactNumber = 'Contact number is required.';
    } else if (!/^[0-9]{9,11}$/.test(contactNumber.replace(/[\s-]/g, ''))) {
      errors.contactNumber = 'Please enter a valid 10-digit mobile number.';
    }

    if (!email.trim()) {
      errors.email = 'Email address is required.';
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.trim())) {
      errors.email = 'Please enter a valid email address.';
    }

    if (!address.trim()) errors.address = 'Home address is required.';

    // Relationship to Patient required when booking for someone else
    if (bookingFor === 'Someone Else / Dependent') {
      if (!relationshipToPatient.trim()) {
        errors.relationshipToPatient = 'Please select your relationship to the patient.';
      }
    }

    // Minor / Guardian Validation: Mandatory if age is below 18
    if (isMinor) {
      if (!guardianName.trim()) {
        errors.guardianName = 'Parent / Guardian name is required for minors.';
      }
      if (!guardianContactNumber.trim()) {
        errors.guardianContactNumber = 'Guardian contact number is required.';
      } else if (!/^[0-9]{9,11}$/.test(guardianContactNumber.replace(/[\s-]/g, ''))) {
        errors.guardianContactNumber = 'Please enter a valid 10-digit mobile number.';
      }
    }

    return Object.keys(errors).length === 0;
  }

  // Validate Phase 2
  function validatePhase2() {
    errors = {};
    if (!username.trim()) {
      errors.username = 'Username is required.';
    } else if (username.trim().length < 3) {
      errors.username = 'Username must be at least 3 characters.';
    }

    if (!password) {
      errors.password = 'Password is required.';
    } else if (password.length < 8) {
      errors.password = 'Password must be at least 8 characters.';
    }

    if (!confirmPassword) {
      errors.confirmPassword = 'Please confirm your password.';
    } else if (password !== confirmPassword) {
      errors.confirmPassword = 'Passwords do not match.';
    }

    if (!agreedTerms) {
      errors.terms = 'You must agree to the data privacy statement.';
    }

    return Object.keys(errors).length === 0;
  }

  function handleNext() {
    if (currentStep === 1) {
      if (validatePhase1()) {
        currentStep = 2;
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
    } else if (currentStep === 2) {
      if (validatePhase2()) {
        currentStep = 3;
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
    }
  }

  function handleBack() {
    if (currentStep > 1) {
      currentStep--;
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  }

  function jumpToStep(step) {
    if (step >= 1 && step < currentStep && !isSubmitting) {
      currentStep = step;
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  }

  async function submitRegistration() {
    isSubmitting = true;
    submissionError = '';

    const payload = {
      first_name: firstName.trim(),
      last_name: lastName.trim(),
      middle_initial: middleInitial.trim(),
      gender: gender,
      birthdate: birthdate,
      age: parseInt(age, 10) || 0,
      contact_number: contactNumber.startsWith('+63') ? contactNumber : '+63 ' + contactNumber.replace(/^0/, ''),
      guardian_name: isMinor ? guardianName.trim() : null,
      guardian_contact_number: isMinor
        ? (guardianContactNumber.startsWith('+63') ? guardianContactNumber : '+63 ' + guardianContactNumber.replace(/^0/, ''))
        : null,
      email: email.trim().toLowerCase(),
      address: address.trim(),
      with_companion: withCompanion === 'Yes' ? 1 : 0,
      booking_for: bookingFor,
      relationship_to_patient: bookingFor === 'Someone Else / Dependent' ? relationshipToPatient : null,
      username: username.trim(),
      password: password,
      confirm_password: confirmPassword
    };

    try {
      const res = await fetch('/sdn/api/patient_register.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json();

      if (res.ok && data.success) {
        window.open('/sdn/pages/patient-login.php?registered=1', '_blank', 'noopener,noreferrer');
      } else {
        submissionError = data.message || 'Registration failed. Please check your details.';
      }
    } catch (err) {
      submissionError = 'Unable to reach the server. Please check your connection.';
    } finally {
      isSubmitting = false;
    }
  }
</script>

<div class="wizard-root">
  <!-- Top Navigation Bar -->
  <header class="wizard-navbar">
    <div class="navbar-content">
      <a href="/sdn/index.php" class="navbar-brand">
        <img src="/sdn/public/assets/v3_lingkod_logo.png" alt="LINGKOD Logo" class="brand-logo" />
        <div class="brand-text">
          <span class="hospital-name">Mariveles Mental Wellness and General Hospital</span>
          <span class="system-name">LINGKOD</span>
        </div>
      </a>
    </div>
  </header>

  <!-- Main Wizard Container -->
  <main class="wizard-container">

    <!-- Stepper Indicator -->
    <div class="stepper-section">
      <div class="stepper-bar-container">
        <!-- Connecting Track Line -->
        <div class="stepper-line-bg">
          <div 
            class="stepper-line-progress" 
            style="width: {currentStep === 1 ? '50%' : (currentStep === 2 ? '100%' : '100%')}"
          ></div>
        </div>

        <!-- Step 1 -->
        <div
          class="step-item"
          class:active={currentStep >= 1}
          class:current={currentStep === 1}
          role="button"
          tabindex="0"
          aria-current={currentStep === 1 ? 'step' : undefined}
          on:click={() => jumpToStep(1)}
          on:keydown={(event) => { if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); jumpToStep(1); } }}
        >
          <div class="step-circle">1</div>
          <span class="step-label">Personal Information</span>
        </div>

        <!-- Step 2 -->
        <div
          class="step-item"
          class:active={currentStep >= 2}
          class:current={currentStep === 2}
          role="button"
          tabindex="0"
          aria-current={currentStep === 2 ? 'step' : undefined}
          on:click={() => jumpToStep(2)}
          on:keydown={(event) => { if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); jumpToStep(2); } }}
        >
          <div class="step-circle">2</div>
          <span class="step-label">Account Registration</span>
        </div>

        <!-- Step 3 -->
        <div
          class="step-item"
          class:active={currentStep >= 3}
          class:current={currentStep === 3}
          role="button"
          tabindex="0"
          aria-current={currentStep === 3 ? 'step' : undefined}
          on:click={() => jumpToStep(3)}
          on:keydown={(event) => { if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); jumpToStep(3); } }}
        >
          <div class="step-circle">3</div>
          <span class="step-label">Confirmation</span>
        </div>
      </div>
    </div>

    <!-- Main Card -->
    <div class="wizard-card">

        <!-- PHASE 1: PERSONAL INFORMATION -->
        {#if currentStep === 1}
          <h2 class="card-title">Personal Information</h2>

          <!-- "Who is this appointment for?" Radio Group -->
          <div class="mb-5 p-4 rounded-xl border border-emerald-200 bg-emerald-50/60 shadow-sm">
            <span class="block text-xs font-bold uppercase tracking-wider text-emerald-900 mb-2">
              Who is this appointment for? <span class="req">*</span>
            </span>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <label
                class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-all {bookingFor === 'Myself' ? 'border-emerald-600 bg-white shadow-sm ring-1 ring-emerald-500' : 'border-slate-200 bg-white/70 hover:bg-white'}"
              >
                <input
                  type="radio"
                  name="bookingFor"
                  value="Myself"
                  checked={bookingFor === 'Myself'}
                  on:change={() => handleBookingForChange('Myself')}
                  class="w-4 h-4 text-emerald-600 focus:ring-emerald-500 accent-emerald-600"
                />
                <div>
                  <span class="block text-sm font-bold text-slate-800">Myself</span>
                  <span class="block text-xs text-slate-500">I am the patient receiving clinical care</span>
                </div>
              </label>

              <label
                class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-all {bookingFor === 'Someone Else / Dependent' ? 'border-emerald-600 bg-white shadow-sm ring-1 ring-emerald-500' : 'border-slate-200 bg-white/70 hover:bg-white'}"
              >
                <input
                  type="radio"
                  name="bookingFor"
                  value="Someone Else / Dependent"
                  checked={bookingFor === 'Someone Else / Dependent'}
                  on:change={() => handleBookingForChange('Someone Else / Dependent')}
                  class="w-4 h-4 text-emerald-600 focus:ring-emerald-500 accent-emerald-600"
                />
                <div>
                  <span class="block text-sm font-bold text-slate-800">Someone Else / Dependent</span>
                  <span class="block text-xs text-slate-500">Booking for a child, parent, spouse, or dependent</span>
                </div>
              </label>
            </div>
          </div>

          <div class="inner-form-box">
            <!-- Row 1: First Name, Last Name, M.I. -->
            <div class="form-row-3">
              <div class="form-field">
                <label for="firstName">First Name <span class="req">*</span></label>
                <input 
                  type="text" 
                  id="firstName" 
                  bind:value={firstName} 
                  placeholder="Enter your First Name"
                  class:has-error={errors.firstName}
                />
                {#if errors.firstName}<span class="field-error">{errors.firstName}</span>{/if}
              </div>

              <div class="form-field">
                <label for="lastName">Last Name <span class="req">*</span></label>
                <input 
                  type="text" 
                  id="lastName" 
                  bind:value={lastName} 
                  placeholder="Enter your Last Name"
                  class:has-error={errors.lastName}
                />
                {#if errors.lastName}<span class="field-error">{errors.lastName}</span>{/if}
              </div>

              <div class="form-field field-mi">
                <label for="middleInitial">M.I. (Optional)</label>
                <input 
                  type="text" 
                  id="middleInitial" 
                  bind:value={middleInitial} 
                  placeholder="e.g., B." 
                  maxlength="5"
                  class:has-error={errors.middleInitial}
                />
                {#if errors.middleInitial}<span class="field-error">{errors.middleInitial}</span>{/if}
              </div>
            </div>

            <!-- Row 2: Gender, Birthdate, Age -->
            <div class="form-row-3">
              <div class="form-field">
                <label for="gender">Gender <span class="req">*</span></label>
                <select id="gender" bind:value={gender} class:has-error={errors.gender}>
                  <option value="" disabled selected>Select Gender</option>
                  <option value="Male">Male</option>
                  <option value="Female">Female</option>
                  <option value="Other">Other</option>
                </select>
                {#if errors.gender}<span class="field-error">{errors.gender}</span>{/if}
              </div>

              <div class="form-field">
                <label for="birthdate">Birthdate <span class="req">*</span></label>
                <input 
                  type="date" 
                  id="birthdate" 
                  bind:value={birthdate}
                  class:has-error={errors.birthdate}
                />
                {#if errors.birthdate}<span class="field-error">{errors.birthdate}</span>{/if}
              </div>

              <div class="form-field field-age">
                <label for="age">Age</label>
                <input 
                  type="text" 
                  id="age" 
                  value={age} 
                  placeholder="Age" 
                  readonly 
                  class="input-readonly"
                />
              </div>
            </div>

            <!-- Conditional Minor/Guardian Validation -->
            {#if isMinor}
              <div class="guardian-container">
                <div class="guardian-alert-bar">
                  <svg class="alert-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                  </svg>
                  <span>Minors must be accompanied by a parent/guardian during the hospital visit.</span>
                </div>

                <div class="form-row-contact" style="margin-top: 8px;">
                  <div class="form-field">
                    <label for="guardianName">Parent / Guardian Full Name <span class="req">*</span></label>
                    <input 
                      type="text" 
                      id="guardianName" 
                      bind:value={guardianName}
                      placeholder="e.g. Maria Santos"
                      class:has-error={errors.guardianName}
                    />
                    {#if errors.guardianName}<span class="field-error">{errors.guardianName}</span>{/if}
                  </div>

                  <div class="form-field">
                    <label for="guardianContactNumber">Guardian Contact Number <span class="req">*</span></label>
                    <div class="contact-group" class:has-error={errors.guardianContactNumber}>
                      <span class="country-prefix">+63</span>
                      <input 
                        type="tel" 
                        id="guardianContactNumber" 
                        bind:value={guardianContactNumber} 
                        placeholder="9XXXXXXXXX"
                        maxlength="11"
                      />
                    </div>
                    {#if errors.guardianContactNumber}<span class="field-error">{errors.guardianContactNumber}</span>{/if}
                  </div>
                </div>
              </div>
            {/if}

            <!-- Row 3: Contact Number & Email -->
            <div class="form-row-contact">
              <div class="form-field">
                <label for="contactNumber">Contact Number <span class="req">*</span></label>
                <div class="contact-group" class:has-error={errors.contactNumber}>
                  <span class="country-prefix">+63</span>
                  <input 
                    type="tel" 
                    id="contactNumber" 
                    bind:value={contactNumber} 
                    placeholder="9XXXXXXXXX"
                    maxlength="11"
                  />
                </div>
                {#if errors.contactNumber}<span class="field-error">{errors.contactNumber}</span>{/if}
              </div>

              <div class="form-field">
                <label for="email">Email Address <span class="req">*</span></label>
                <input 
                  type="email" 
                  id="email" 
                  bind:value={email} 
                  placeholder="Enter your Email Address"
                  class:has-error={errors.email}
                />
                {#if errors.email}<span class="field-error">{errors.email}</span>{/if}
              </div>
            </div>

            <!-- Row 4: Home Address -->
            <div class="form-field">
              <label for="address">Home Address <span class="req">*</span></label>
              <textarea 
                id="address" 
                bind:value={address} 
                placeholder="Enter your Home Address (House / Bldg No., Street, Barangay, City, Province)"
                class="address-textarea"
                class:has-error={errors.address}
                rows="2"
              ></textarea>
              {#if errors.address}<span class="field-error">{errors.address}</span>{/if}
            </div>

            <!-- Row 5: Accompanying Companion -->
            <div class="form-field companion-field">
              <label class="companion-label">Accompanying Companion? <span class="req">*</span></label>
              <div class="radio-group">
                <label class="radio-item">
                  <input type="radio" bind:group={withCompanion} value="Yes" />
                  <span class="custom-radio"></span>
                  <span>Yes</span>
                </label>
                {#if bookingFor !== 'Someone Else / Dependent'}
                  <label class="radio-item">
                    <input type="radio" bind:group={withCompanion} value="No" />
                    <span class="custom-radio"></span>
                    <span>No</span>
                  </label>
                {/if}
              </div>
              {#if bookingFor === 'Someone Else / Dependent'}
                <span class="text-xs text-emerald-700 font-medium mt-1 block">
                  (Required: An accompanying companion/guardian is mandatory when booking for a dependent.)
                </span>
              {/if}
            </div>

            <!-- Relationship to Patient (Conditional for Someone Else / Dependent) -->
            {#if bookingFor === 'Someone Else / Dependent'}
              <div class="form-field mt-3 p-3.5 rounded-xl border border-emerald-200 bg-emerald-50/70">
                <label for="relationshipToPatient" class="block text-xs font-bold uppercase tracking-wider text-emerald-900 mb-1.5">
                  Relationship to Patient <span class="req">*</span>
                </label>
                <select
                  id="relationshipToPatient"
                  bind:value={relationshipToPatient}
                  class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 shadow-sm"
                  class:has-error={errors.relationshipToPatient}
                >
                  <option value="" disabled selected>Select Relationship</option>
                  <option value="Parent">Parent</option>
                  <option value="Child">Child</option>
                  <option value="Spouse">Spouse</option>
                  <option value="Sibling">Sibling</option>
                  <option value="Guardian">Guardian</option>
                  <option value="Other">Other</option>
                </select>
                {#if errors.relationshipToPatient}
                  <span class="field-error">{errors.relationshipToPatient}</span>
                {/if}
                <p class="text-[11px] text-emerald-700 mt-1.5">
                  As the primary account owner, you will be registered as the accompanying guardian/companion.
                </p>
              </div>
            {/if}
          </div>
        {/if}

        <!-- PHASE 2: ACCOUNT REGISTRATION -->
        {#if currentStep === 2}
          <h2 class="card-title">Account Registration</h2>

          <div class="inner-form-box">
            <!-- Username -->
            <div class="form-field">
              <label for="regUsername">Username <span class="req">*</span></label>
              <input 
                type="text" 
                id="regUsername" 
                bind:value={username} 
                placeholder="Choose a username"
                class:has-error={errors.username}
              />
              {#if errors.username}<span class="field-error">{errors.username}</span>{/if}
            </div>

            <!-- Registered Email (Read-only reference) -->
            <div class="form-field">
              <label for="regEmail">Login Email Address</label>
              <input 
                type="email" 
                id="regEmail" 
                value={email} 
                readonly 
                class="input-readonly"
              />
              <span class="field-hint">Verification link will be delivered to this email address.</span>
            </div>

            <!-- Password -->
            <div class="form-field">
              <label for="regPassword">Password <span class="req">*</span></label>
              <div class="password-group" class:has-error={errors.password}>
                <input 
                  type={showPassword ? 'text' : 'password'} 
                  id="regPassword" 
                  bind:value={password} 
                  placeholder="Create a strong password (minimum 8 characters)"
                />
                <button type="button" class="btn-toggle-eye" on:click={() => showPassword = !showPassword} aria-label={showPassword ? 'Hide password' : 'Show password'} title={showPassword ? 'Hide password' : 'Show password'}>
                  {#if showPassword}
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                      <line x1="1" y1="1" x2="23" y2="23"></line>
                    </svg>
                  {:else}
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                      <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                  {/if}
                </button>
              </div>
              {#if errors.password}<span class="field-error">{errors.password}</span>{/if}
            </div>

            <!-- Confirm Password -->
            <div class="form-field">
              <label for="regConfirmPassword">Confirm Password <span class="req">*</span></label>
              <div class="password-group" class:has-error={errors.confirmPassword}>
                <input 
                  type={showConfirmPassword ? 'text' : 'password'} 
                  id="regConfirmPassword" 
                  bind:value={confirmPassword} 
                  placeholder="Re-enter your password"
                />
                <button type="button" class="btn-toggle-eye" on:click={() => showConfirmPassword = !showConfirmPassword} aria-label={showConfirmPassword ? 'Hide password' : 'Show password'} title={showConfirmPassword ? 'Hide password' : 'Show password'}>
                  {#if showConfirmPassword}
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                      <line x1="1" y1="1" x2="23" y2="23"></line>
                    </svg>
                  {:else}
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                      <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                  {/if}
                </button>
              </div>
              {#if errors.confirmPassword}<span class="field-error">{errors.confirmPassword}</span>{/if}
            </div>

            <!-- Privacy Policy Checkbox -->
            <div class="terms-group">
              <label class="checkbox-item">
                <input type="checkbox" bind:checked={agreedTerms} />
                <span class="custom-checkbox"></span>
                <span class="terms-text">
                  I certify that all details provided are true and correct, and I agree to MMWGH's Data Privacy Policy regarding healthcare information.
                </span>
              </label>
              {#if errors.terms}<span class="field-error">{errors.terms}</span>{/if}
            </div>
          </div>
        {/if}

        <!-- PHASE 3: CONFIRMATION -->
        {#if currentStep === 3}
          <h2 class="card-title">Confirmation &amp; Review</h2>

          <div class="inner-form-box confirmation-box">
            <h3 class="section-heading">Personal Information Summary</h3>
            <div class="review-grid">
              <div class="review-item">
                <span class="review-label">Full Name:</span>
                <span class="review-val">{firstName} {middleInitial}. {lastName}</span>
              </div>
              <div class="review-item">
                <span class="review-label">Gender &amp; Age:</span>
                <span class="review-val">{gender}, {age} yrs old</span>
              </div>
              <div class="review-item">
                <span class="review-label">Birthdate:</span>
                <span class="review-val">{birthdate}</span>
              </div>
              <div class="review-item">
                <span class="review-label">Contact Number:</span>
                <span class="review-val">+63 {contactNumber.replace(/^0/, '')}</span>
              </div>
              <div class="review-item">
                <span class="review-label">Email:</span>
                <span class="review-val">{email}</span>
              </div>
              <div class="review-item">
                <span class="review-label">Address:</span>
                <span class="review-val">{address}</span>
              </div>
              <div class="review-item">
                <span class="review-label">With Companion:</span>
                <span class="review-val">{withCompanion}</span>
              </div>
              {#if isMinor}
                <div class="review-item">
                  <span class="review-label">Parent / Guardian:</span>
                  <span class="review-val">{guardianName} (+63 {guardianContactNumber.replace(/^0/, '')})</span>
                </div>
              {/if}
            </div>

            <h3 class="section-heading" style="margin-top: 1.5rem;">Account Credentials</h3>
            <div class="review-grid">
              <div class="review-item">
                <span class="review-label">Username:</span>
                <span class="review-val">{username}</span>
              </div>
              <div class="review-item">
                <span class="review-label">Initial Status:</span>
                <span class="review-val status-badge-pending">Unverified (Pending Email Activation)</span>
              </div>
            </div>

            <div class="notice-callout">
              <div class="notice-icon">&#9432;</div>
              <p>
                <strong>Email Verification Required:</strong> After submitting, an email containing an activation link will be sent to <strong>{email}</strong>. You must click that link to activate your portal account.
              </p>
            </div>

            {#if submissionError}
              <div class="submission-alert-error">
                {submissionError}
              </div>
            {/if}
          </div>
        {/if}

    </div>

    <!-- Wizard Navigation Button(s) Below Card -->
    <div class="wizard-nav-actions">
      {#if currentStep > 1}
        <button type="button" class="btn-wizard-back" on:click={handleBack} disabled={isSubmitting}>
          Back
        </button>
      {/if}

      {#if currentStep < 3}
        <button type="button" class="btn-wizard-main" on:click={handleNext}>
          Next
        </button>
      {:else}
        <button type="button" class="btn-wizard-main" on:click={submitRegistration} disabled={isSubmitting}>
          {isSubmitting ? 'Submitting Registration...' : 'Submit Registration'}
        </button>
      {/if}
    </div>

  </main>
</div>

<style>
  * {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
  }

  .wizard-root {
    height: 100vh;
    max-height: 100vh;
    background-color: #f0f4f7;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    color: #1f2937;
    display: flex;
    flex-direction: column;
    overflow: hidden !important;
  }

  /* ── Header (Exact Homepage Green Gradient & V3 Logo Thickness) ── */
  .wizard-navbar {
    height: 86px;
    background: linear-gradient(90deg, #25ba50 0%, #7ed957 50%, #047023 100%);
    padding: 0 2.5rem;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
    display: flex;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 200;
    flex-shrink: 0;
  }

  .navbar-content {
    max-width: 1200px;
    width: 100%;
    margin: 0 auto;
    display: flex;
    align-items: center;
    height: 100%;
  }

  .navbar-brand {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    color: #ffffff;
    height: 86px;
    overflow: hidden;
    cursor: pointer;
  }

  .navbar-brand:hover {
    opacity: 0.94;
  }

  .brand-logo {
    height: 124px;
    max-height: none;
    width: auto;
    object-fit: contain;
    flex-shrink: 0;
    margin-left: -10px;
    margin-right: -21px;
    transform: translateY(7px);
  }

  .brand-text {
    display: flex;
    flex-direction: column;
    justify-content: center;
    text-align: left;
    gap: 1px;
    transform: translateY(2px);
  }

  .hospital-name {
    font-size: 0.92rem;
    font-weight: 700;
    color: #ffffff;
    line-height: 1.16;
    letter-spacing: -0.1px;
    white-space: nowrap;
  }

  .system-name {
    font-size: 1.18rem;
    font-weight: 800;
    color: #ffffff;
    letter-spacing: 0.3px;
    line-height: 1.12;
  }

  /* ── Main Container (Centered, fits viewport without scrolling) ── */
  .wizard-container {
    flex: 1;
    max-width: 590px;
    width: 100%;
    margin: 0 auto;
    padding: 6px 16px 10px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: calc(100vh - 86px);
    max-height: calc(100vh - 86px);
    overflow: hidden !important;
    box-sizing: border-box;
  }

  /* ── Stepper ── */
  .stepper-section {
    width: 100%;
    max-width: 440px;
    margin-bottom: 10px;
    flex-shrink: 0;
  }

  .stepper-bar-container {
    position: relative;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .stepper-line-bg {
    position: absolute;
    top: 17px;
    left: 42px;
    right: 42px;
    height: 5px;
    background-color: #68826c;
    border-radius: 999px;
    z-index: 1;
  }

  .stepper-line-progress {
    height: 100%;
    background-color: #1b6e1b;
    border-radius: 999px;
    transition: width 0.35s ease;
  }

  .step-item {
    position: relative;
    z-index: 2;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    width: 120px;
  }

  .step-circle {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background-color: #68826c;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.92rem;
    transition: all 0.3s ease;
  }

  .step-item.active .step-circle {
    background-color: #1b6e1b;
  }

  .step-item.current .step-circle {
    background-color: #1b6e1b;
    position: relative;
  }

  .step-item.current .step-circle::after {
    content: "";
    position: absolute;
    inset: -7px;
    border-radius: 50%;
    border: 2px solid #1b6e1b;
    background: transparent;
    pointer-events: none;
    animation: phaseHeartbeat 1.35s ease-in-out infinite;
  }

  .step-item.active:not(.current) {
    cursor: pointer;
  }

  .step-item.active:not(.current):hover .step-circle,
  .step-item.active:not(.current):focus-visible .step-circle {
    transform: scale(1.08);
  }

  .step-item:focus-visible {
    outline: 2px solid #1b6e1b;
    outline-offset: 4px;
    border-radius: 12px;
  }

  @keyframes phaseHeartbeat {
    0%, 100% { transform: scale(1); opacity: 0.9; }
    14% { transform: scale(1.18); opacity: 1; }
    28% { transform: scale(1); opacity: 0.75; }
    42% { transform: scale(1.1); opacity: 1; }
    56% { transform: scale(1); opacity: 0.55; }
  }

  @media (prefers-reduced-motion: reduce) {
    .step-item.current .step-circle::after { animation: none; }
  }

  .step-label {
    margin-top: 5px;
    font-size: 0.76rem;
    font-weight: 700;
    color: #1f2937;
    white-space: nowrap;
  }

  /* ── Card (Minimal Landscape: Compact Horizontal, Never Stretched) ── */
  .wizard-card {
    background: #ffffff;
    border-radius: 18px;
    padding: 16px 24px 18px;
    box-shadow: 0 4px 22px rgba(0, 0, 0, 0.06);
    width: 100%;
    max-width: 590px;
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
    box-sizing: border-box;
  }

  .card-title {
    font-size: 1.24rem;
    font-weight: 800;
    color: #1f2937;
    text-align: center;
    margin: 0 0 8px 0;
    flex-shrink: 0;
  }

  /* ── Inner Form Box ── */
  .inner-form-box {
    border: 1.5px solid #d1d5db;
    border-radius: 12px;
    padding: 12px 18px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    background: #ffffff;
  }

  .form-row-3 {
    display: grid;
    grid-template-columns: 1fr 1fr 76px;
    gap: 8px;
  }

  .form-row-contact {
    display: grid;
    grid-template-columns: 1.05fr 1.25fr;
    gap: 8px;
  }

  .guardian-container {
    background: #fffbeb;
    border: 1.2px solid #fde68a;
    border-radius: 10px;
    padding: 10px 12px;
    margin-top: 2px;
  }

  .guardian-alert-bar {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.76rem;
    font-weight: 600;
    color: #92400e;
    margin-bottom: 4px;
    line-height: 1.3;
  }

  .guardian-alert-bar .alert-icon {
    flex-shrink: 0;
    color: #d97706;
  }

  .form-field {
    display: flex;
    flex-direction: column;
    gap: 3px;
  }

  .form-field label {
    font-size: 0.8rem;
    font-weight: 700;
    color: #1f2937;
    line-height: 1.2;
  }

  .req {
    color: #dc2626;
  }

  .form-field input[type="text"],
  .form-field input[type="email"],
  .form-field input[type="date"],
  .form-field input[type="password"],
  .form-field select {
    width: 100%;
    padding: 6px 12px;
    height: 38px;
    border: 1.4px solid #cbd5e1;
    border-radius: 8px;
    font-size: 0.88rem;
    font-family: inherit;
    color: #1e293b;
    outline: none;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
    background: #ffffff;
    box-sizing: border-box;
  }

  .address-textarea {
    width: 100%;
    height: 64px;
    min-height: 58px;
    max-height: 80px;
    resize: vertical;
    padding: 7px 12px;
    border: 1.4px solid #cbd5e1;
    border-radius: 8px;
    font-size: 0.88rem;
    font-family: inherit;
    color: #1e293b;
    outline: none;
    line-height: 1.4;
    background: #ffffff;
    box-sizing: border-box;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
  }

  .address-textarea:focus {
    border-color: #1b6e1b;
    box-shadow: 0 0 0 2px rgba(27, 110, 27, 0.18);
  }

  .form-field input:focus,
  .form-field select:focus {
    border-color: #1b6e1b;
    box-shadow: 0 0 0 2px rgba(27, 110, 27, 0.18);
  }

  .input-readonly {
    background-color: #f8fafc !important;
    color: #64748b !important;
    cursor: default;
  }

  .field-error {
    font-size: 0.68rem;
    color: #dc2626;
    font-weight: 500;
    line-height: 1;
  }

  .has-error {
    border-color: #dc2626 !important;
  }

  .field-hint {
    font-size: 0.72rem;
    color: #64748b;
  }

  /* Contact input group (+63) */
  .contact-group {
    display: flex;
    align-items: center;
    border: 1.2px solid #cbd5e1;
    border-radius: 8px;
    overflow: hidden;
    background: #ffffff;
    height: 32px;
  }

  .country-prefix {
    padding: 4px 10px;
    background: #ffffff;
    font-weight: 700;
    font-size: 0.82rem;
    color: #1f2937;
    border-right: 1px solid #cbd5e1;
    user-select: none;
  }

  .contact-group input {
    border: none !important;
    border-radius: 0 !important;
    flex: 1;
    padding: 4px 10px;
    outline: none;
    font-size: 0.82rem;
    height: 100%;
    box-sizing: border-box;
  }

  /* Password group with eye toggle */
  .password-group {
    display: flex;
    align-items: center;
    position: relative;
  }

  .password-group input {
    padding-right: 56px !important;
  }

  .btn-toggle-eye {
    position: absolute;
    right: 10px;
    background: none;
    border: none;
    font-size: 0.74rem;
    font-weight: 600;
    color: #059669;
    cursor: pointer;
  }

  /* Radio item styling */
  .companion-field {
    margin-top: 1px;
  }

  .radio-group {
    display: flex;
    gap: 18px;
    align-items: center;
    margin-top: 1px;
  }

  .radio-item {
    display: flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    font-size: 0.82rem;
    font-weight: 600;
    color: #1f2937;
  }

  .radio-item input[type="radio"] {
    accent-color: #1b6e1b;
    width: 16px;
    height: 16px;
    cursor: pointer;
  }

  /* Terms Checkbox */
  .terms-group {
    margin-top: 6px;
  }

  .checkbox-item {
    display: flex;
    gap: 8px;
    align-items: flex-start;
    cursor: pointer;
  }

  .checkbox-item input[type="checkbox"] {
    accent-color: #1b6e1b;
    width: 16px;
    height: 16px;
    margin-top: 1px;
  }

  .terms-text {
    font-size: 0.8rem;
    color: #4b5563;
    line-height: 1.4;
  }

  /* Confirmation view */
  .section-heading {
    font-size: 0.84rem;
    font-weight: 700;
    color: #1b6e1b;
    border-bottom: 1px solid #e5e7eb;
    padding-bottom: 3px;
    margin-bottom: 5px;
  }

  .review-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4px 16px;
    font-size: 0.78rem;
  }

  .review-item {
    display: flex;
    flex-direction: column;
    gap: 1px;
  }

  .review-label {
    font-size: 0.68rem;
    color: #6b7280;
    font-weight: 600;
    text-transform: uppercase;
  }

  .review-val {
    font-weight: 600;
    color: #111827;
  }

  .status-badge-pending {
    display: inline-block;
    background: #fef3c7;
    color: #92400e;
    padding: 1px 6px;
    border-radius: 4px;
    font-size: 0.74rem;
  }

  .notice-callout {
    margin-top: 6px;
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    border-radius: 6px;
    padding: 6px 10px;
    display: flex;
    gap: 6px;
    align-items: flex-start;
    color: #065f46;
    font-size: 0.72rem;
    line-height: 1.35;
  }

  .notice-icon {
    font-size: 0.95rem;
    font-weight: bold;
    color: #059669;
  }

  .submission-alert-error {
    margin-top: 6px;
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #b91c1c;
    border-radius: 6px;
    padding: 6px 10px;
    font-size: 0.76rem;
  }

  /* ── Action Buttons Below Card ── */
  .wizard-nav-actions {
    display: flex;
    justify-content: center;
    gap: 14px;
    margin-top: 14px;
    width: 100%;
    flex-shrink: 0;
  }

  .btn-wizard-main {
    background-color: #1b6e1b;
    color: #ffffff;
    border: none;
    border-radius: 9999px;
    padding: 8px 60px;
    font-size: 0.94rem;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(27, 110, 27, 0.3);
    transition: background-color 0.2s ease, transform 0.15s ease;
    text-decoration: none;
    height: 38px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }

  .btn-wizard-main:hover:not(:disabled) {
    background-color: #145314;
    transform: translateY(-1px);
  }

  .btn-wizard-main:disabled {
    opacity: 0.65;
    cursor: not-allowed;
  }

  .btn-wizard-back {
    background: #ffffff;
    color: #374151;
    border: 1px solid #cbd5e1;
    border-radius: 9999px;
    padding: 8px 32px;
    font-size: 0.94rem;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.15s ease;
    height: 38px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }

  .btn-wizard-back:hover {
    background: #f8fafc;
  }

  /* ── Success Screen ── */
  .success-screen {
    text-align: center;
    padding: 8px 0;
  }

  .success-badge {
    width: 52px;
    height: 52px;
    background: #eff6ff;
    color: #2563eb;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 10px;
    box-shadow: 0 0 0 8px rgba(37, 99, 235, 0.1);
  }

  .success-subtitle {
    color: #4b5563;
    font-size: 0.88rem;
    margin-bottom: 6px;
  }

  .success-email-badge {
    display: inline-block;
    background: #ecfdf5;
    color: #047857;
    border: 1px solid #a7f3d0;
    font-weight: 700;
    padding: 6px 18px;
    border-radius: 999px;
    margin-bottom: 14px;
    font-size: 0.92rem;
  }

  .success-info-panel {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 12px 16px;
    max-width: 500px;
    margin: 0 auto 16px;
    text-align: left;
    font-size: 0.84rem;
    line-height: 1.45;
  }

  .status-unverified {
    color: #d97706;
    font-weight: 700;
  }

  .success-actions {
    display: flex;
    justify-content: center;
    gap: 14px;
    margin-top: 10px;
  }

  /* Scaling for smaller display viewports to strictly prevent scrolling */
  @media (max-height: 720px) {
    .wizard-navbar { height: 50px; }
    .brand-logo { height: 42px; }
    .wizard-container { padding: 6px 18px 8px; }
    .stepper-section { margin-bottom: 6px; }
    .step-circle { width: 28px; height: 28px; font-size: 0.8rem; }
    .stepper-line-bg { top: 14px; }
    .step-label { font-size: 0.7rem; margin-top: 2px; }
    .wizard-card { padding: 10px 24px 10px; border-radius: 16px; }
    .card-title { font-size: 1.1rem; margin-bottom: 5px; }
    .inner-form-box { padding: 8px 18px; gap: 6px; border-radius: 10px; }
    .form-field input, .contact-group { height: 28px; font-size: 0.78rem; padding: 2px 8px; border-radius: 6px; }
    .address-textarea { height: 48px; min-height: 42px; }
    .wizard-nav-actions { margin-top: 8px; }
    .btn-wizard-main, .btn-wizard-back { height: 32px; padding: 4px 44px; font-size: 0.84rem; }
  }

  /* ── Responsive ── */
  @media (max-width: 768px) {
    .wizard-root { height: auto; max-height: none; overflow: visible !important; }
    .wizard-container { height: auto; max-height: none; overflow: visible !important; padding: 16px 14px; }
    .wizard-card { padding: 16px 14px; }
    .inner-form-box { padding: 14px 10px; }
    .form-row-3, .form-row-contact, .review-grid {
      grid-template-columns: 1fr;
    }
    .stepper-section {
      max-width: 100%;
    }
    .stepper-line-bg {
      left: 28px;
      right: 28px;
      top: 20px;
    }
    .step-item {
      width: auto;
      flex: 1;
      min-width: 0;
    }
    .step-circle {
      width: 40px;
      height: 40px;
    }
    .step-label {
      font-size: 0.68rem;
      line-height: 1.25;
      white-space: normal;
      max-width: 96px;
    }
    .btn-wizard-main {
      padding: 8px 30px;
      width: 100%;
    }
  }
</style>

