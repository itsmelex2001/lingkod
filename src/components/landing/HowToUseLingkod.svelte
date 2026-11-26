<script>
  const steps = [
    {
      id: "book",
      number: "01",
      title: "Book Appointment",
      description:
        "Fill out the required form and click 'Book Appointment' to start.",
    },
    {
      id: "verify",
      number: "02",
      title: "Verify Your Email",
      description:
        "Confirm your email to receive your secure login credentials.",
    },
    {
      id: "review",
      number: "03",
      title: "Await Review",
      description: "Our medical staff will review your request shortly.",
    },
    {
      id: "confirmed",
      number: "04",
      title: "Got It!",
      description:
        "Check your inbox for your official appointment confirmation.",
    },
  ];

  let activeStepId = null;

  function setActive(id) {
    activeStepId = id;
  }

  function clearActive(id) {
    if (activeStepId === id) activeStepId = null;
  }
</script>

<section
  class="howto-stepper-section relative w-full overflow-hidden py-12 pb-20 px-4 sm:px-6 lg:px-8"
  aria-labelledby="how-to-use-heading"
>
  <div class="mx-auto max-w-[1340px]">
    <header class="mx-auto mb-12 max-w-2xl text-center">
      <h2
        id="how-to-use-heading"
        class="text-4xl font-black uppercase tracking-wide text-[#143318] sm:text-5xl"
      >
        HOW TO USE LINGKOD?
      </h2>
      <p class="mt-2.5 text-base text-slate-700 sm:text-lg">
        A simple step-by-step guide on how to book an appointment.
      </p>
    </header>

    <div class="relative">
      <ol
        class="grid grid-cols-1 gap-10 sm:grid-cols-2 lg:grid-cols-4 lg:gap-7"
      >
        {#each steps as step, index (step.id)}
          {@const isActive = activeStepId === step.id}
          <li class="relative flex flex-col items-center">
            <!-- Horizontal dashed stepper connector (desktop) -->
            {#if index < steps.length - 1}
              <span
                aria-hidden="true"
                class="pointer-events-none absolute top-[106px] left-[calc(50%+106px)] hidden h-0 w-[calc(100%-196px)] border-t-2 border-dashed border-green-700/35 lg:block"
              ></span>
            {/if}

            <button
              type="button"
              class="step-item step-{step.id} group flex w-full max-w-[310px] flex-col items-center rounded-2xl p-2 text-center touch-manipulation focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-green-800 focus-visible:ring-offset-4"
              class:is-active={isActive}
              aria-pressed={isActive}
              on:mouseenter={() => setActive(step.id)}
              on:mouseleave={() => clearActive(step.id)}
              on:focus={() => setActive(step.id)}
              on:blur={() => clearActive(step.id)}
            >
              <!-- Circular soft mint-green icon badge -->
              <div
                class="relative mb-6 flex h-48 w-48 items-center justify-center rounded-full bg-[#e5f5e5] transition-colors duration-200 group-hover:bg-[#d8f0d8]"
              >
                <span
                  class="absolute top-1.5 right-1.5 z-10 flex h-10 w-10 items-center justify-center rounded-full bg-[#7db574] text-sm font-bold tracking-tight text-white shadow-sm"
                >
                  {step.number}
                </span>

                {#if step.id === "book"}
                  <!-- STEP 1: Calendar + Book Button + Pixel Cursor -->
                  <svg
                    viewBox="0 0 64 64"
                    class="h-40 w-40 overflow-visible"
                    shape-rendering="crispEdges"
                    role="img"
                    aria-label="Pixel calendar with Book button and cursor"
                  >
                    <g class="calendar-body">
                      <!-- Drop in custom calendar vector paths here -->
                      <rect
                        x="10"
                        y="14"
                        width="40"
                        height="38"
                        fill="#18271b"
                      />
                      <rect
                        x="12"
                        y="16"
                        width="36"
                        height="8"
                        fill="#4f8f4e"
                      />
                      <rect
                        x="12"
                        y="24"
                        width="36"
                        height="26"
                        fill="#edf7e8"
                      />
                      <!-- Spiral rings -->
                      <rect x="16" y="10" width="4" height="8" fill="#18271b" />
                      <rect x="17" y="11" width="2" height="6" fill="#9bc992" />
                      <rect x="24" y="10" width="4" height="8" fill="#18271b" />
                      <rect x="25" y="11" width="2" height="6" fill="#9bc992" />
                      <rect x="32" y="10" width="4" height="8" fill="#18271b" />
                      <rect x="33" y="11" width="2" height="6" fill="#9bc992" />
                      <rect x="40" y="10" width="4" height="8" fill="#18271b" />
                      <rect x="41" y="11" width="2" height="6" fill="#9bc992" />
                      <!-- Date grid cells -->
                      <rect x="15" y="27" width="5" height="5" fill="#9fc498" />
                      <rect x="22" y="27" width="5" height="5" fill="#9fc498" />
                      <rect x="29" y="27" width="5" height="5" fill="#9fc498" />
                      <rect x="36" y="27" width="5" height="5" fill="#9fc498" />
                      <rect x="15" y="34" width="5" height="5" fill="#9fc498" />
                      <rect x="22" y="34" width="5" height="5" fill="#4f8f4e" />
                      <rect x="15" y="41" width="5" height="5" fill="#9fc498" />
                      <rect x="22" y="41" width="5" height="5" fill="#9fc498" />
                    </g>

                    <!-- Targetable Book button -->
                    <g class="book-btn">
                      <rect
                        x="28"
                        y="34"
                        width="24"
                        height="11"
                        fill="#18271b"
                      />
                      <rect
                        x="30"
                        y="36"
                        width="20"
                        height="7"
                        fill="#3a7d3c"
                      />
                      <rect
                        x="32"
                        y="38"
                        width="16"
                        height="3"
                        fill="#e9f7e7"
                      />
                    </g>

                    <!-- Targetable pixel cursor -->
                    <g class="cursor-icon">
                      <path
                        d="M44 41h4v6h4v2h4v8h-2v2h-8v-2h-2v-4h-2v-4h2v-8z"
                        fill="#18271b"
                      />
                      <path
                        d="M45 42h2v7h4v2h3v6h-8v-4h-2v-3h1v-8z"
                        fill="#ffffff"
                      />
                    </g>
                  </svg>
                {:else if step.id === "verify"}
                  <!-- STEP 2: Open Envelope + Sliding Letter Paper + Shield -->
                  <svg
                    viewBox="0 0 64 64"
                    class="h-40 w-40 overflow-visible"
                    shape-rendering="crispEdges"
                    role="img"
                    aria-label="Pixel open envelope with verification letter"
                  >
                    <g class="envelope-back">
                      <!-- Back flap & interior -->
                      <path d="M10 26L32 10l22 16v26H10V26z" fill="#18271b" />
                      <path d="M12 26L32 12l20 14v24H12V26z" fill="#c9ae8b" />
                    </g>

                    <!-- Targetable sliding letter paper -->
                    <g class="letter-paper">
                      <rect
                        x="17"
                        y="18"
                        width="30"
                        height="26"
                        fill="#18271b"
                      />
                      <rect
                        x="19"
                        y="20"
                        width="26"
                        height="22"
                        fill="#e6f5df"
                      />
                      <!-- Pixel checkmark -->
                      <path
                        d="M25 30h3v3h3v-3h3v-3h3v-3h3v3h-3v3h-3v3h-3v3h-3v-3h-3v-3z"
                        fill="#18271b"
                      />
                      <path
                        d="M26 30h2v3h3v-3h3v-3h3v-2h2v2h-3v3h-3v3h-3v2h-2v-2h-2v-3z"
                        fill="#4f994a"
                      />
                    </g>

                    <g class="envelope-front">
                      <!-- Front pocket masking bottom of paper -->
                      <path d="M10 28l22 14 22-14v24H10V28z" fill="#18271b" />
                      <path d="M12 31l20 12 20-12v19H12V31z" fill="#f2e4cc" />
                      <path d="M12 50l14-11h12l14 11H12z" fill="#dec8a6" />
                    </g>

                    <g class="security-shield">
                      <path d="M44 40h12v9l-6 6-6-6v-9z" fill="#18271b" />
                      <path d="M46 42h8v6l-4 4-4-4v-6z" fill="#4f994a" />
                      <path d="M50 42h4v6l-4 4V42z" fill="#78bc6e" />
                    </g>
                  </svg>
                {:else if step.id === "review"}
                  <!-- STEP 3: Patient Form Clipboard + Rotating Clock -->
                  <svg
                    viewBox="0 0 64 64"
                    class="h-40 w-40 overflow-visible"
                    shape-rendering="crispEdges"
                    role="img"
                    aria-label="Pixel patient form and running clock"
                  >
                    <!-- Targetable patient form -->
                    <g class="patient-form">
                      <rect
                        x="11"
                        y="11"
                        width="34"
                        height="43"
                        fill="#18271b"
                      />
                      <rect
                        x="13"
                        y="13"
                        width="30"
                        height="39"
                        fill="#8b6343"
                      />
                      <rect
                        x="16"
                        y="16"
                        width="24"
                        height="33"
                        fill="#f7f2e3"
                      />
                      <!-- Clipboard clip -->
                      <rect x="22" y="8" width="12" height="7" fill="#18271b" />
                      <rect x="24" y="10" width="8" height="4" fill="#c7d1c6" />
                      <!-- Form header & check rows -->
                      <rect
                        x="19"
                        y="20"
                        width="18"
                        height="2"
                        fill="#4f6152"
                      />
                      <rect x="19" y="26" width="4" height="4" fill="#4f994a" />
                      <rect
                        x="25"
                        y="27"
                        width="12"
                        height="2"
                        fill="#a8b8a4"
                      />
                      <rect x="19" y="33" width="4" height="4" fill="#4f994a" />
                      <rect
                        x="25"
                        y="34"
                        width="10"
                        height="2"
                        fill="#a8b8a4"
                      />
                      <rect x="19" y="40" width="4" height="4" fill="#4f994a" />
                      <rect x="25" y="41" width="8" height="2" fill="#a8b8a4" />
                    </g>

                    <!-- Clock bezel + face centered at (45, 44) -->
                    <g class="clock-face">
                      <circle cx="45" cy="44" r="13" fill="#18271b" />
                      <circle cx="45" cy="44" r="11" fill="#4f8f4e" />
                      <circle cx="45" cy="44" r="8.5" fill="#f4faef" />
                      <rect x="44" y="36" width="2" height="2" fill="#18271b" />
                      <rect x="44" y="50" width="2" height="2" fill="#18271b" />
                      <rect x="37" y="43" width="2" height="2" fill="#18271b" />
                      <rect x="51" y="43" width="2" height="2" fill="#18271b" />
                    </g>

                    <!-- Targetable clock hands rotating around exact pivot (45, 44) -->
                    <g class="clock-hands">
                      <g class="clock-hour-hand">
                        <rect
                          x="44"
                          y="39"
                          width="2"
                          height="6"
                          fill="#18271b"
                        />
                      </g>
                      <g class="clock-minute-hand">
                        <rect
                          x="44"
                          y="37"
                          width="2"
                          height="8"
                          fill="#2e6b34"
                        />
                      </g>
                      <rect x="44" y="43" width="2" height="2" fill="#18271b" />
                    </g>
                  </svg>
                {:else}
                  <!-- STEP 4: Exact Pixel Character with Layered Double Thumbs-Up -->
                  <div
                    class="relative h-[162px] w-[174px] pointer-events-none"
                    role="img"
                    aria-label="Pixel person giving double thumbs up"
                  >
                    <img
                      src="/sdn/public/assets/howto/step4-sparkles.png"
                      alt=""
                      aria-hidden="true"
                      draggable="false"
                      class="step-sprite-layer thumbs-sparkle absolute inset-0 h-full w-full select-none object-contain [image-rendering:pixelated]"
                    />
                    <img
                      src="/sdn/public/assets/howto/step4-body.png"
                      alt=""
                      aria-hidden="true"
                      draggable="false"
                      class="step-sprite-layer person-body absolute inset-0 h-full w-full select-none object-contain [image-rendering:pixelated]"
                    />
                    <img
                      src="/sdn/public/assets/howto/step4-thumb-left.png"
                      alt=""
                      aria-hidden="true"
                      draggable="false"
                      class="step-sprite-layer thumb-left absolute inset-0 h-full w-full select-none object-contain [image-rendering:pixelated]"
                    />
                    <img
                      src="/sdn/public/assets/howto/step4-thumb-right.png"
                      alt=""
                      aria-hidden="true"
                      draggable="false"
                      class="step-sprite-layer thumb-right absolute inset-0 h-full w-full select-none object-contain [image-rendering:pixelated]"
                    />
                  </div>
                {/if}
              </div>

              <h3 class="text-xl font-bold tracking-tight text-[#142617]">
                {step.title}
              </h3>
              <p class="mt-2 text-base leading-relaxed text-slate-600">
                {step.description}
              </p>
            </button>
          </li>
        {/each}
      </ol>
    </div>
  </div>
</section>

<style>
  /* Crisp pixel rendering for embedded SVGs or swapped raster layers */
  .step-item svg {
    image-rendering: pixelated;
  }

  /* ----------------------------------------------------------------
     STEP 1: Cursor moves onto "Book" button and performs click press
     ---------------------------------------------------------------- */
  .cursor-icon {
    transform-box: view-box;
    transform-origin: 46px 46px;
  }

  .book-btn {
    transform-box: view-box;
    transform-origin: 40px 40px;
  }

  .step-book:hover .cursor-icon,
  .step-book:focus-visible .cursor-icon,
  .step-book.is-active .cursor-icon {
    animation: cursor-click 0.95s steps(4, end) infinite;
  }

  .step-book:hover .book-btn,
  .step-book:focus-visible .book-btn,
  .step-book.is-active .book-btn {
    animation: book-btn-press 0.95s steps(2, end) infinite;
  }

  @keyframes cursor-click {
    0% {
      transform: translate(5px, 5px) scale(1);
    }
    35% {
      transform: translate(-3px, -2px) scale(1);
    }
    55% {
      transform: translate(-3px, 0px) scale(0.9);
    }
    72% {
      transform: translate(-3px, -2px) scale(1);
    }
    100% {
      transform: translate(5px, 5px) scale(1);
    }
  }

  @keyframes book-btn-press {
    0%,
    48% {
      transform: translateY(0);
      filter: brightness(1);
    }
    55%,
    68% {
      transform: translateY(1.5px);
      filter: brightness(1.15);
    }
    75%,
    100% {
      transform: translateY(0);
      filter: brightness(1);
    }
  }

  /* ----------------------------------------------------------------
     STEP 2: Paper slides smoothly up and down inside the envelope
     ---------------------------------------------------------------- */
  .letter-paper {
    transition: transform 180ms ease-out;
  }

  .step-verify:hover .letter-paper,
  .step-verify:focus-visible .letter-paper,
  .step-verify.is-active .letter-paper {
    animation: paper-slide 1.1s ease-in-out infinite;
  }

  @keyframes paper-slide {
    0%,
    100% {
      transform: translateY(0);
    }
    50% {
      transform: translateY(-6px);
    }
  }

  /* ----------------------------------------------------------------
     STEP 3: Minimal patient form motion + rotating clock hands
     ---------------------------------------------------------------- */
  .patient-form {
    transform-box: view-box;
    transform-origin: 28px 32px;
  }

  .clock-hour-hand,
  .clock-minute-hand {
    transform-box: view-box;
    transform-origin: 45px 44px;
  }

  .step-review:hover .patient-form,
  .step-review:focus-visible .patient-form,
  .step-review.is-active .patient-form {
    animation: form-nudge 1.4s ease-in-out infinite;
  }

  .step-review:hover .clock-minute-hand,
  .step-review:focus-visible .clock-minute-hand,
  .step-review.is-active .clock-minute-hand {
    animation: clock-spin 1.6s linear infinite;
  }

  .step-review:hover .clock-hour-hand,
  .step-review:focus-visible .clock-hour-hand,
  .step-review.is-active .clock-hour-hand {
    animation: clock-spin 8s linear infinite;
  }

  @keyframes form-nudge {
    0%,
    100% {
      transform: translateY(0);
    }
    50% {
      transform: translateY(-2.5px);
    }
  }

  @keyframes clock-spin {
    from {
      transform: rotate(0deg);
    }
    to {
      transform: rotate(360deg);
    }
  }

  /* ----------------------------------------------------------------
     STEP 4: Minimal double thumbs-up movement + accent pop
     ---------------------------------------------------------------- */
  .step-sprite-layer {
    image-rendering: -moz-crisp-edges;
    image-rendering: crisp-edges;
    image-rendering: pixelated;
  }

  .thumb-left {
    transform-origin: 18% 92%;
  }

  .thumb-right {
    transform-origin: 82% 92%;
  }

  .step-confirmed:hover .thumb-left,
  .step-confirmed:focus-visible .thumb-left,
  .step-confirmed.is-active .thumb-left {
    animation: thumb-left-bob 0.34s ease-in-out infinite;
  }

  .step-confirmed:hover .thumb-right,
  .step-confirmed:focus-visible .thumb-right,
  .step-confirmed.is-active .thumb-right {
    animation: thumb-right-bob 0.34s ease-in-out infinite;
  }

  .step-confirmed:hover .thumbs-sparkle,
  .step-confirmed:focus-visible .thumbs-sparkle,
  .step-confirmed.is-active .thumbs-sparkle {
    animation: sparkle-pop 0.34s ease-in-out infinite;
  }

  @keyframes thumb-left-bob {
    0%,
    100% {
      transform: translateY(0) rotate(0deg);
    }
    50% {
      transform: translateY(-3px) rotate(-2deg);
    }
  }

  @keyframes thumb-right-bob {
    0%,
    100% {
      transform: translateY(0) rotate(0deg);
    }
    50% {
      transform: translateY(-3px) rotate(2deg);
    }
  }

  @keyframes sparkle-pop {
    0%,
    100% {
      transform: translateY(0);
      opacity: 0.75;
    }
    50% {
      transform: translateY(-2px);
      opacity: 1;
    }
  }

  .howto-stepper-section {
    background: linear-gradient(
      180deg,
      #ffffff 0%,
      #ffffff 56%,
      #f2fdf1 78%,
      #e6fce2 100%
    );
  }

  /* Accessibility: honor reduced-motion preference */
  @media (prefers-reduced-motion: reduce) {
    .step-item * {
      animation: none !important;
      transition: none !important;
    }
  }
</style>
