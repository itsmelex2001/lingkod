<script>
  let {
    apiBase = '/sdn/api',
    useLaravelApi = false,
    onAuthenticated = (data) => {
      if (data.token) {
        sessionStorage.setItem('patient_token', data.token);
      }
      window.location.href = data.redirect || '/sdn/pages/patient-dashboard.php';
    }
  } = $props();

  let step = $state('credentials');
  let email = $state('');
  let password = $state('');
  let showPassword = $state(false);

  let otpDigits = $state(['', '', '', '', '', '']);
  let otpToken = $state('');
  let maskedEmail = $state('');

  let loading = $state(false);
  let resending = $state(false);
  let error = $state('');
  let statusMessage = $state('');
  let cooldown = $state(0);
  let expiresLeft = $state(0);

  let otpInputs = $state([]);

  let otp = $derived(otpDigits.join(''));
  let isExpired = $derived(step === 'otp' && expiresLeft <= 0);
  let canSubmitOtp = $derived(otp.length === 6 && !loading && !isExpired);
  let canResend = $derived(cooldown === 0 && !resending && !loading);
  let formattedExpiry = $derived(
    `${Math.floor(Math.max(0, expiresLeft) / 60)}:${String(Math.max(0, expiresLeft) % 60).padStart(2, '0')}`
  );

  $effect(() => {
    if (cooldown <= 0) return;
    const id = setInterval(() => {
      cooldown = Math.max(0, cooldown - 1);
    }, 1000);
    return () => clearInterval(id);
  });

  $effect(() => {
    if (step !== 'otp' || expiresLeft <= 0) return;
    const id = setInterval(() => {
      expiresLeft = Math.max(0, expiresLeft - 1);
      if (expiresLeft === 0) {
        otpDigits = ['', '', '', '', '', ''];
        error = 'Your verification code has expired. Please click Resend Code to request a new one.';
      }
    }, 1000);
    return () => clearInterval(id);
  });

  $effect(() => {
    if (step === 'otp' && otpInputs[0]) {
      otpInputs[0].focus();
    }
  });

  function clearOtpDigits(focusFirst = false) {
    otpDigits = ['', '', '', '', '', ''];
    if (focusFirst) otpInputs[0]?.focus();
  }

  function fillOtpFrom(startIndex, rawText) {
    const clean = (rawText || '').replace(/\D/g, '').slice(0, 6 - startIndex);
    if (!clean) return;
    const next = [...otpDigits];
    for (let i = 0; i < clean.length; i++) {
      next[startIndex + i] = clean[i];
    }
    otpDigits = next;
    if (error) error = '';
    const focusIdx = Math.min(startIndex + clean.length, 5);
    otpInputs[focusIdx]?.focus();
    otpInputs[focusIdx]?.select();
  }

  function handleDigitInput(index, e) {
    const clean = e.currentTarget.value.replace(/\D/g, '');
    if (error) error = '';
    if (clean.length > 1) {
      fillOtpFrom(index, clean);
      return;
    }
    otpDigits[index] = clean;
    e.currentTarget.value = clean;
    if (clean && index < 5) {
      otpInputs[index + 1]?.focus();
    }
  }

  function handleDigitKeydown(index, e) {
    if (e.key === 'Backspace') {
      if (error) error = '';
      if (otpDigits[index]) {
        otpDigits[index] = '';
      } else if (index > 0) {
        otpDigits[index - 1] = '';
        otpInputs[index - 1]?.focus();
      }
      e.preventDefault();
    } else if (e.key === 'ArrowLeft' && index > 0) {
      e.preventDefault();
      otpInputs[index - 1]?.focus();
    } else if (e.key === 'ArrowRight' && index < 5) {
      e.preventDefault();
      otpInputs[index + 1]?.focus();
    } else if (e.key === 'Home') {
      e.preventDefault();
      otpInputs[0]?.focus();
    } else if (e.key === 'End') {
      e.preventDefault();
      otpInputs[5]?.focus();
    }
  }

  function handleDigitPaste(index, e) {
    e.preventDefault();
    const pasted = (e.clipboardData || window.clipboardData)?.getData('text') || '';
    const digits = pasted.replace(/\D/g, '');
    if (!digits) return;
    fillOtpFrom(digits.length >= 6 ? 0 : index, digits);
  }

  async function postJson(url, body) {
    const res = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json'
      },
      body: JSON.stringify(body)
    });
    const data = await res.json().catch(() => ({}));
    return { ok: res.ok, data };
  }

  async function handleLogin(e) {
    e.preventDefault();
    error = '';
    statusMessage = '';
    loading = true;

    const url = useLaravelApi ? `${apiBase}/patient/login` : `${apiBase}/patient_auth.php`;
    const payload = useLaravelApi
      ? { email: email.trim(), password }
      : { username: email.trim(), email: email.trim(), password };

    try {
      const { ok, data } = await postJson(url, payload);

      if (data.otp_required) {
        otpToken = data.otp_token || '';
        maskedEmail = data.masked_email || email.trim();
        clearOtpDigits(false);
        expiresLeft = data.expires_in || 300;
        cooldown = data.resend_cooldown || 60;
        step = 'otp';
        return;
      }

      if (!ok || data.success === false) {
        error = data.message || 'Invalid email or password.';
        return;
      }

      onAuthenticated(data);
    } catch {
      error = 'Unable to connect to the server. Please try again.';
    } finally {
      loading = false;
    }
  }

  async function handleVerifyOtp(e) {
    e.preventDefault();
    if (!canSubmitOtp) return;

    error = '';
    statusMessage = '';
    loading = true;

    const url = useLaravelApi ? `${apiBase}/patient/verify-otp` : `${apiBase}/patient_verify_otp.php`;
    const payload = useLaravelApi
      ? { otp_token: otpToken, otp }
      : { action: 'verify', otp };

    try {
      const { ok, data } = await postJson(url, payload);
      if (!ok || data.success === false) {
        error = data.message || 'Invalid or expired verification code.';
        if (data.expired) {
          expiresLeft = 0;
          clearOtpDigits(false);
        } else {
          clearOtpDigits(true);
        }
        return;
      }
      onAuthenticated(data);
    } catch {
      error = 'Unable to verify code. Please try again.';
    } finally {
      loading = false;
    }
  }

  async function handleResend() {
    if (!canResend) return;

    error = '';
    statusMessage = '';
    resending = true;

    const url = useLaravelApi ? `${apiBase}/patient/resend-otp` : `${apiBase}/patient_verify_otp.php`;
    const payload = useLaravelApi
      ? { otp_token: otpToken }
      : { action: 'resend' };

    try {
      const { ok, data } = await postJson(url, payload);
      if (!ok || data.success === false) {
        error = data.message || 'Could not resend code.';
        if (data.retry_after) cooldown = data.retry_after;
        return;
      }
      expiresLeft = data.expires_in || 300;
      cooldown = data.resend_cooldown || 60;
      statusMessage = data.message || 'A new verification code has been sent.';
      clearOtpDigits(true);
    } catch {
      error = 'Network error while resending code.';
    } finally {
      resending = false;
    }
  }

  function backToLogin() {
    step = 'credentials';
    clearOtpDigits(false);
    otpToken = '';
    cooldown = 0;
    expiresLeft = 0;
    error = '';
    statusMessage = '';
  }
</script>

<main class="min-h-dvh bg-[#f5f8f6] flex items-center justify-center px-4 py-10 text-neutral-900">
  <section class="w-full max-w-md bg-white border border-neutral-200/80 rounded-2xl shadow-sm p-8 sm:p-10">
    <header class="text-center mb-8">
      <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-green-950/5 text-green-900">
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          {#if step === 'otp'}
            <rect x="3" y="11" width="18" height="11" rx="2" />
            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
          {:else}
            <path d="M12 2v20M2 12h20" />
          {/if}
        </svg>
      </div>
      <p class="text-xs font-semibold uppercase tracking-[0.14em] text-green-900">Lingkod</p>
      <h1 class="mt-1 text-2xl font-semibold tracking-tight text-neutral-900">
        {step === 'otp' ? 'Verify your identity' : 'Patient Portal'}
      </h1>
      <p class="mt-2 text-sm text-neutral-600 leading-relaxed">
        {#if step === 'otp'}
          Enter the 6-digit code sent to <span class="font-medium text-neutral-900">{maskedEmail}</span>
        {:else}
          Sign in to manage your appointments and clinical records.
        {/if}
      </p>
    </header>

    {#if error}
      <div
        id="auth-error"
        role="alert"
        class="mb-6 flex items-start gap-2.5 rounded-lg border border-red-200 bg-red-50 px-3.5 py-3 text-sm text-red-800"
      >
        <svg class="mt-0.5 h-4 w-4 shrink-0 text-red-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
          <circle cx="12" cy="12" r="10" />
          <path d="M12 8v4m0 4h.01" />
        </svg>
        <span>{error}</span>
      </div>
    {/if}

    {#if statusMessage}
      <div
        aria-live="polite"
        class="mb-6 rounded-lg border border-green-200 bg-green-50 px-3.5 py-3 text-sm text-green-900"
      >
        {statusMessage}
      </div>
    {/if}

    {#if step === 'credentials'}
      <form onsubmit={handleLogin} class="space-y-5" novalidate>
        <div>
          <label for="patient-email" class="block text-sm font-medium text-neutral-700 mb-1.5">
            Email address
          </label>
          <input
            id="patient-email"
            type="email"
            bind:value={email}
            required
            autocomplete="email"
            placeholder="you@example.com"
            aria-invalid={Boolean(error)}
            aria-describedby={error ? 'auth-error' : undefined}
            class="block w-full min-h-11 rounded-lg border border-neutral-300 bg-white px-3.5 py-2.5 text-base text-neutral-900 placeholder:text-neutral-400 transition-colors motion-reduce:transition-none focus:border-green-900 focus:outline-none focus:ring-2 focus:ring-green-900/20"
          />
        </div>

        <div>
          <label for="patient-password" class="block text-sm font-medium text-neutral-700 mb-1.5">
            Password
          </label>
          <div class="relative">
            <input
              id="patient-password"
              type={showPassword ? 'text' : 'password'}
              bind:value={password}
              required
              autocomplete="current-password"
              placeholder="Enter your password"
              class="block w-full min-h-11 rounded-lg border border-neutral-300 bg-white pl-3.5 pr-11 py-2.5 text-base text-neutral-900 placeholder:text-neutral-400 transition-colors motion-reduce:transition-none focus:border-green-900 focus:outline-none focus:ring-2 focus:ring-green-900/20"
            />
            <button
              type="button"
              onclick={() => (showPassword = !showPassword)}
              aria-label={showPassword ? 'Hide password' : 'Show password'}
              aria-pressed={showPassword}
              class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-neutral-500 hover:text-neutral-800 cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-green-900 rounded-r-lg"
            >
              {#if showPassword}
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                  <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" />
                  <line x1="1" y1="1" x2="23" y2="23" />
                </svg>
              {:else}
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                  <circle cx="12" cy="12" r="3" />
                </svg>
              {/if}
            </button>
          </div>
        </div>

        <button
          type="submit"
          disabled={loading}
          class="w-full min-h-11 inline-flex items-center justify-center gap-2 rounded-lg bg-green-900 px-4 py-2.5 text-sm font-medium text-white transition-colors motion-reduce:transition-none hover:bg-green-950 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-green-900 focus-visible:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
        >
          {#if loading}
            <span class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white" aria-hidden="true"></span>
            Signing in...
          {:else}
            Sign in
          {/if}
        </button>
      </form>
    {:else}
      <form onsubmit={handleVerifyOtp} class="space-y-6">
        <div>
          <label for="otp-digit-0" id="otp-group-label" class="block text-center text-sm font-medium text-neutral-700 mb-3">
            Verification code
          </label>
          <div
            role="group"
            aria-labelledby="otp-group-label"
            aria-describedby={error ? 'otp-hint auth-error' : 'otp-hint'}
            class="grid grid-cols-6 gap-2 sm:gap-2.5"
          >
            {#each otpDigits as digit, i (i)}
              <input
                id="otp-digit-{i}"
                bind:this={otpInputs[i]}
                type="text"
                inputmode="numeric"
                pattern="[0-9]*"
                autocomplete={i === 0 ? 'one-time-code' : 'off'}
                maxlength="1"
                value={digit}
                disabled={isExpired}
                aria-label="Digit {i + 1} of 6"
                aria-invalid={Boolean(error)}
                onfocus={(e) => e.currentTarget.select()}
                oninput={(e) => handleDigitInput(i, e)}
                onkeydown={(e) => handleDigitKeydown(i, e)}
                onpaste={(e) => handleDigitPaste(i, e)}
                class="w-full h-13 sm:h-14 rounded-xl border text-center font-mono text-xl sm:text-2xl font-bold tabular-nums text-neutral-900 caret-transparent transition-colors motion-reduce:transition-none focus:bg-white focus:border-green-900 focus:outline-none focus:ring-2 focus:ring-green-900/20 disabled:bg-neutral-100 disabled:border-neutral-200 disabled:text-neutral-400 disabled:cursor-not-allowed {error
                  ? 'border-red-500 bg-red-50/70'
                  : digit
                    ? 'border-green-800/60 bg-green-50/40'
                    : 'border-neutral-300 bg-neutral-50/70 hover:border-neutral-400'}"
              />
            {/each}
          </div>
          <p
            id="otp-hint"
            class="mt-2.5 text-center text-xs tabular-nums {isExpired
              ? 'font-semibold text-red-600'
              : expiresLeft <= 30
                ? 'font-medium text-amber-700'
                : 'text-neutral-500'}"
          >
            {#if isExpired}
              Verification code expired. Click Resend Code to get a new code.
            {:else}
              Code expires in {formattedExpiry}. You can paste all 6 digits at once.
            {/if}
          </p>
        </div>

        <button
          type="submit"
          disabled={!canSubmitOtp}
          class="w-full min-h-11 inline-flex items-center justify-center gap-2 rounded-lg bg-green-900 px-4 py-2.5 text-sm font-medium text-white transition-colors motion-reduce:transition-none hover:bg-green-950 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-green-900 focus-visible:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
        >
          {#if loading}
            <span class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white" aria-hidden="true"></span>
            Verifying...
          {:else}
            Verify &amp; Sign In
          {/if}
        </button>

        <div class="flex flex-col items-center gap-2 pt-1 text-sm">
          <button
            type="button"
            onclick={handleResend}
            disabled={!canResend}
            class="min-h-11 px-3 font-medium text-green-900 transition-colors motion-reduce:transition-none hover:text-green-950 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-green-900 rounded-md disabled:text-neutral-400 disabled:no-underline disabled:cursor-not-allowed cursor-pointer tabular-nums"
          >
            {#if resending}
              Sending new code...
            {:else if cooldown > 0}
              Resend code in {cooldown}s
            {:else}
              Resend code
            {/if}
          </button>

          <button
            type="button"
            onclick={backToLogin}
            class="min-h-11 px-3 text-xs font-medium text-neutral-500 hover:text-neutral-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-green-900 rounded-md cursor-pointer"
          >
            Back to Sign In
          </button>
        </div>
      </form>
    {/if}
  </section>
</main>
