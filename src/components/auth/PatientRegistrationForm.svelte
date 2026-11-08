<script>
  import { createEventDispatcher } from 'svelte';

  const dispatch = createEventDispatcher();

  // Form Fields
  export let firstName = '';
  export let lastName = '';
  export let middleInitial = '';
  export let gender = '';
  export let birthdate = '';
  export let contactNumber = '';
  export let email = '';
  export let address = '';
  export let guardianName = '';
  export let guardianContactNumber = '';
  export let username = '';
  export let password = '';
  export let confirmPassword = '';

  // Reactive State
  export let age = null;
  export let isMinor = false;
  let errors = {};
  let isSubmitting = false;
  let serverError = '';
  let serverSuccess = '';

  // Reactive logic: Dynamically calculate age and minor status from birthday input
  $: if (birthdate) {
    const b = new Date(birthdate);
    const today = new Date();
    if (!isNaN(b.getTime()) && b <= today) {
      let calculatedAge = today.getFullYear() - b.getFullYear();
      const monthDiff = today.getMonth() - b.getMonth();
      if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < b.getDate())) {
        calculatedAge--;
      }
      age = calculatedAge >= 0 ? calculatedAge : 0;
      isMinor = age < 18;
    } else {
      age = null;
      isMinor = false;
    }
  } else {
    age = null;
    isMinor = false;
    guardianName = '';
    guardianContactNumber = '';
  }

  function validate() {
    errors = {};

    if (!firstName.trim()) errors.firstName = 'First name is required.';
    if (!lastName.trim()) errors.lastName = 'Last name is required.';
    if (!gender) errors.gender = 'Please select a gender.';
    
    if (!birthdate) {
      errors.birthdate = 'Birthdate is required.';
    } else if (new Date(birthdate) > new Date()) {
      errors.birthdate = 'Birthdate cannot be in the future.';
    }

    if (!contactNumber.trim()) {
      errors.contactNumber = 'Contact number is required.';
    } else if (!/^[0-9]{9,11}$/.test(contactNumber.replace(/[\s-]/g, ''))) {
      errors.contactNumber = 'Please enter a valid 10-digit mobile number.';
    }

    // Minor / Guardian Validation: Mandatory ONLY if age is below 18
    if (isMinor) {
      if (!guardianName.trim()) {
        errors.guardianName = 'Parent / Guardian full name is required for minors.';
      }
      if (!guardianContactNumber.trim()) {
        errors.guardianContactNumber = 'Guardian contact number is required.';
      } else if (!/^[0-9]{9,11}$/.test(guardianContactNumber.replace(/[\s-]/g, ''))) {
        errors.guardianContactNumber = 'Please enter a valid 10-digit mobile number.';
      }
    }

    if (!email.trim() || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.trim())) {
      errors.email = 'A valid email address is required.';
    }

    if (!address.trim()) errors.address = 'Address is required.';

    if (!username.trim() || username.trim().length < 3) {
      errors.username = 'Username must be at least 3 characters.';
    }

    if (!password || password.length < 8) {
      errors.password = 'Password must be at least 8 characters.';
    }

    if (password !== confirmPassword) {
      errors.confirmPassword = 'Passwords do not match.';
    }

    return Object.keys(errors).length === 0;
  }

  async function handleRegister() {
    serverError = '';
    serverSuccess = '';

    if (!validate()) return;

    isSubmitting = true;

    const payload = {
      first_name: firstName.trim(),
      last_name: lastName.trim(),
      middle_initial: middleInitial.trim(),
      gender,
      birthdate,
      age,
      contact_number: contactNumber.startsWith('+63') ? contactNumber : '+63 ' + contactNumber.replace(/^0/, ''),
      guardian_name: isMinor ? guardianName.trim() : null,
      guardian_contact_number: isMinor
        ? (guardianContactNumber.startsWith('+63') ? guardianContactNumber : '+63 ' + guardianContactNumber.replace(/^0/, ''))
        : null,
      email: email.trim().toLowerCase(),
      address: address.trim(),
      username: username.trim(),
      password,
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
        serverSuccess = data.message || 'Registration successful! Verification email sent.';
        dispatch('registered', { ...payload, patient_id: data.patient_id });
      } else {
        serverError = data.message || 'Registration failed. Please check your inputs.';
      }
    } catch (err) {
      serverError = 'Unable to connect to the server. Please check your network connection.';
    } finally {
      isSubmitting = false;
    }
  }
</script>

<div class="w-full max-w-2xl mx-auto bg-white rounded-2xl shadow-sm border border-slate-200/80 p-6 md:p-8">
  <div class="mb-6 border-b border-slate-100 pb-4">
    <h2 class="text-xl font-bold text-slate-800">Patient Account Registration</h2>
    <p class="text-xs text-slate-500 mt-1">Create an account to schedule consultations and access outpatient triage services.</p>
  </div>

  {#if serverError}
    <div class="mb-5 p-3.5 bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-xl flex items-center gap-2">
      <svg class="w-4 h-4 flex-shrink-0 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      <span>{serverError}</span>
    </div>
  {/if}

  {#if serverSuccess}
    <div class="mb-5 p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs rounded-xl flex items-center gap-2">
      <svg class="w-4 h-4 flex-shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
      <span>{serverSuccess}</span>
    </div>
  {/if}

  <form on:submit|preventDefault={handleRegister} class="space-y-4">
    <!-- Row 1: Name -->
    <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
      <div class="md:col-span-5">
        <label for="firstName" class="block text-xs font-semibold text-slate-700 mb-1">First Name <span class="text-rose-500">*</span></label>
        <input
          type="text"
          id="firstName"
          bind:value={firstName}
          placeholder="First Name"
          class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors {errors.firstName ? 'border-rose-400 bg-rose-50/30' : ''}"
        />
        {#if errors.firstName}<p class="text-[11px] text-rose-500 mt-1">{errors.firstName}</p>{/if}
      </div>

      <div class="md:col-span-5">
        <label for="lastName" class="block text-xs font-semibold text-slate-700 mb-1">Last Name <span class="text-rose-500">*</span></label>
        <input
          type="text"
          id="lastName"
          bind:value={lastName}
          placeholder="Last Name"
          class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors {errors.lastName ? 'border-rose-400 bg-rose-50/30' : ''}"
        />
        {#if errors.lastName}<p class="text-[11px] text-rose-500 mt-1">{errors.lastName}</p>{/if}
      </div>

      <div class="md:col-span-2">
        <label for="middleInitial" class="block text-xs font-semibold text-slate-700 mb-1">M.I.</label>
        <input
          type="text"
          id="middleInitial"
          bind:value={middleInitial}
          maxlength="5"
          placeholder="Optional"
          class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-center uppercase"
        />
      </div>
    </div>

    <!-- Row 2: Gender, Birthday & Calculated Age -->
    <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
      <div class="md:col-span-4">
        <label for="gender" class="block text-xs font-semibold text-slate-700 mb-1">Gender <span class="text-rose-500">*</span></label>
        <select
          id="gender"
          bind:value={gender}
          class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 bg-white {errors.gender ? 'border-rose-400 bg-rose-50/30' : ''}"
        >
          <option value="" disabled selected>Select Gender</option>
          <option value="Male">Male</option>
          <option value="Female">Female</option>
          <option value="Other">Other</option>
        </select>
        {#if errors.gender}<p class="text-[11px] text-rose-500 mt-1">{errors.gender}</p>{/if}
      </div>

      <div class="md:col-span-5">
        <label for="birthdate" class="block text-xs font-semibold text-slate-700 mb-1">Birthday <span class="text-rose-500">*</span></label>
        <input
          type="date"
          id="birthdate"
          bind:value={birthdate}
          class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 {errors.birthdate ? 'border-rose-400 bg-rose-50/30' : ''}"
        />
        {#if errors.birthdate}<p class="text-[11px] text-rose-500 mt-1">{errors.birthdate}</p>{/if}
      </div>

      <div class="md:col-span-3">
        <label for="age" class="block text-xs font-semibold text-slate-700 mb-1">Age</label>
        <input
          type="text"
          id="age"
          value={age !== null ? `${age} yrs` : 'Auto-calc'}
          readonly
          class="w-full px-3 py-2 text-sm rounded-lg border border-slate-200 bg-slate-50 font-medium text-slate-600 cursor-not-allowed text-center"
        />
      </div>
    </div>

    <!-- Conditional Minor / Guardian Fields (Triggered when age < 18) -->
    {#if isMinor}
      <div class="mt-3 p-4 rounded-xl border border-amber-200/90 bg-amber-50/60 transition-all duration-200">
        <div class="flex items-center gap-2 mb-1.5">
          <svg class="w-4 h-4 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
          <span class="text-xs font-bold text-amber-900 uppercase tracking-wide">Parent / Guardian Verification</span>
          <span class="ml-auto text-[10px] font-semibold bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full border border-amber-200">Minor Patient ({age} y/o)</span>
        </div>
        
        <p class="text-xs text-amber-800 font-medium mb-3 leading-relaxed">
          Minors must be accompanied by a parent/guardian during the hospital visit.
        </p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label for="guardianName" class="block text-xs font-semibold text-slate-700 mb-1">
              Parent / Guardian Full Name <span class="text-rose-500">*</span>
            </label>
            <input
              type="text"
              id="guardianName"
              bind:value={guardianName}
              placeholder="e.g., Maria Santos"
              class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 bg-white {errors.guardianName ? 'border-rose-400 bg-rose-50/30' : ''}"
            />
            {#if errors.guardianName}<p class="text-[11px] text-rose-500 mt-1">{errors.guardianName}</p>{/if}
          </div>

          <div>
            <label for="guardianContactNumber" class="block text-xs font-semibold text-slate-700 mb-1">
              Guardian Contact Number <span class="text-rose-500">*</span>
            </label>
            <div class="relative flex items-center">
              <span class="absolute left-3 text-xs font-bold text-slate-500 select-none">+63</span>
              <input
                type="tel"
                id="guardianContactNumber"
                bind:value={guardianContactNumber}
                placeholder="912 345 6789"
                maxlength="11"
                class="w-full pl-11 pr-3 py-2 text-sm rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 bg-white {errors.guardianContactNumber ? 'border-rose-400 bg-rose-50/30' : ''}"
              />
            </div>
            {#if errors.guardianContactNumber}<p class="text-[11px] text-rose-500 mt-1">{errors.guardianContactNumber}</p>{/if}
          </div>
        </div>
      </div>
    {/if}

    <!-- Row 3: Contact & Email -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <div>
        <label for="contactNumber" class="block text-xs font-semibold text-slate-700 mb-1">Patient Mobile Number <span class="text-rose-500">*</span></label>
        <div class="relative flex items-center">
          <span class="absolute left-3 text-xs font-bold text-slate-500 select-none">+63</span>
          <input
            type="tel"
            id="contactNumber"
            bind:value={contactNumber}
            placeholder="9XXXXXXXXX"
            maxlength="11"
            class="w-full pl-11 pr-3 py-2 text-sm rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 {errors.contactNumber ? 'border-rose-400 bg-rose-50/30' : ''}"
          />
        </div>
        {#if errors.contactNumber}<p class="text-[11px] text-rose-500 mt-1">{errors.contactNumber}</p>{/if}
      </div>

      <div>
        <label for="email" class="block text-xs font-semibold text-slate-700 mb-1">Email Address <span class="text-rose-500">*</span></label>
        <input
          type="email"
          id="email"
          bind:value={email}
          placeholder="patient@example.com"
          class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 {errors.email ? 'border-rose-400 bg-rose-50/30' : ''}"
        />
        {#if errors.email}<p class="text-[11px] text-rose-500 mt-1">{errors.email}</p>{/if}
      </div>
    </div>

    <!-- Row 4: Address -->
    <div>
      <label for="address" class="block text-xs font-semibold text-slate-700 mb-1">Home Address <span class="text-rose-500">*</span></label>
      <input
        type="text"
        id="address"
        bind:value={address}
        placeholder="Barangay, Municipality / City, Province"
        class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 {errors.address ? 'border-rose-400 bg-rose-50/30' : ''}"
      />
      {#if errors.address}<p class="text-[11px] text-rose-500 mt-1">{errors.address}</p>{/if}
    </div>

    <!-- Row 5: Account Credentials -->
    <div class="pt-3 border-t border-slate-100">
      <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wide mb-3">Login Credentials</h3>
      
      <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div>
          <label for="username" class="block text-xs font-semibold text-slate-700 mb-1">Username <span class="text-rose-500">*</span></label>
          <input
            type="text"
            id="username"
            bind:value={username}
            placeholder="Username"
            class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 {errors.username ? 'border-rose-400 bg-rose-50/30' : ''}"
          />
          {#if errors.username}<p class="text-[11px] text-rose-500 mt-1">{errors.username}</p>{/if}
        </div>

        <div>
          <label for="password" class="block text-xs font-semibold text-slate-700 mb-1">Password <span class="text-rose-500">*</span></label>
          <input
            type="password"
            id="password"
            bind:value={password}
            placeholder="At least 8 characters"
            class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 {errors.password ? 'border-rose-400 bg-rose-50/30' : ''}"
          />
          {#if errors.password}<p class="text-[11px] text-rose-500 mt-1">{errors.password}</p>{/if}
        </div>

        <div>
          <label for="confirmPassword" class="block text-xs font-semibold text-slate-700 mb-1">Confirm Password <span class="text-rose-500">*</span></label>
          <input
            type="password"
            id="confirmPassword"
            bind:value={confirmPassword}
            placeholder="Re-enter password"
            class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 {errors.confirmPassword ? 'border-rose-400 bg-rose-50/30' : ''}"
          />
          {#if errors.confirmPassword}<p class="text-[11px] text-rose-500 mt-1">{errors.confirmPassword}</p>{/if}
        </div>
      </div>
    </div>

    <!-- Submit Button -->
    <div class="pt-4 flex justify-end">
      <button
        type="submit"
        disabled={isSubmitting}
        class="inline-flex items-center justify-center px-6 py-2.5 bg-emerald-700 hover:bg-emerald-800 disabled:bg-emerald-400 text-white text-sm font-semibold rounded-full shadow-sm hover:shadow transition-all duration-150 cursor-pointer disabled:cursor-not-allowed"
      >
        {#if isSubmitting}
          <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
          Processing...
        {:else}
          Register Account
        {/if}
      </button>
    </div>
  </form>
</div>

