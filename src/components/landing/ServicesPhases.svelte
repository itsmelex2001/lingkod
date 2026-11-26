<script>
  export let phases = [
    {
      id: "digital-id",
      step: "Phase 01",
      title: "Digital ID",
      description:
        "Register once to receive your Lingkod Patient ID and QR slip for faster hospital check-ins.",
      idleSrc: "/sdn/public/assets/chibi_girl_full_clean.png",
      activeSrc: "/sdn/public/assets/check_wave_frame_1.png",
      spriteAlt: "Pixel art nurse presenting a patient Digital ID",
    },
    {
      id: "otp-security",
      step: "Phase 02",
      title: "OTP Security",
      description:
        "Protect your account and medical records with one-time passcodes verified on sign-in.",
      idleSrc: "/sdn/public/assets/chibi_girl_full_clean.png",
      activeSrc: "/sdn/public/assets/check_wave_frame_1.png",
      spriteAlt: "Pixel art nurse verifying a one-time passcode",
    },
    {
      id: "appointment-tracking",
      step: "Phase 03",
      title: "Appointment Tracking",
      description:
        "Track consultation schedules, approval status, and follow-up visits in real time.",
      idleSrc: "/sdn/public/assets/chibi_girl_full_clean.png",
      activeSrc: "/sdn/public/assets/check_wave_frame_1.png",
      spriteAlt: "Pixel art nurse checking an appointment schedule",
    },
  ];

  let activeCardId = null;
  let pointerOffsetX = 0;

  $: isAnyActive = activeCardId !== null;

  function activateCard(id) {
    activeCardId = id;
  }

  function deactivateCard(id) {
    if (activeCardId === id) {
      activeCardId = null;
      pointerOffsetX = 0;
    }
  }

  function handleMouseMove(event, id) {
    if (activeCardId !== id) activeCardId = id;
    const rect = event.currentTarget.getBoundingClientRect();
    const ratio = (event.clientX - rect.left) / rect.width - 0.5;
    pointerOffsetX = Math.round(ratio * 6);
  }
</script>

<section
  class="w-full bg-white py-16 px-4 sm:px-6 lg:px-8"
  aria-labelledby="services-phases-heading"
>
  <div class="mx-auto max-w-6xl">
    <header class="mx-auto mb-12 max-w-2xl text-center">
      <p class="text-xs font-semibold uppercase tracking-widest text-green-800">
        How Lingkod Works
      </p>
      <h2
        id="services-phases-heading"
        class="mt-2 text-3xl font-bold tracking-tight text-green-950 sm:text-4xl"
      >
        Three Simple Phases to Care
      </h2>
      <p class="mt-3 text-base leading-relaxed text-slate-600">
        From digital registration to real-time visit updates, every step is
        designed for clarity and security.
      </p>
    </header>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-3 lg:gap-8">
      {#each phases as phase (phase.id)}
        {@const isActive = activeCardId === phase.id}
        <button
          type="button"
          class="phase-card flex flex-col overflow-hidden rounded-2xl border text-left touch-manipulation transition-all duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-green-800 focus-visible:ring-offset-2 {isActive
            ? 'border-green-700/50 bg-[#e7f9ec] shadow-md -translate-y-1'
            : 'border-green-200/90 bg-[#f0fdf4] shadow-sm hover:border-green-600/40'} {isAnyActive &&
          !isActive
            ? 'opacity-90'
            : 'opacity-100'}"
          aria-pressed={isActive}
          on:mouseenter={() => activateCard(phase.id)}
          on:mouseleave={() => deactivateCard(phase.id)}
          on:mousemove={(e) => handleMouseMove(e, phase.id)}
          on:focus={() => activateCard(phase.id)}
          on:blur={() => deactivateCard(phase.id)}
        >
          <!-- Half-body RPG nurse sprite stage -->
          <div
            class="relative flex h-44 w-full items-end justify-center overflow-hidden border-b border-green-200/80 bg-white/85 px-4 pt-3"
          >
            <div
              class="nurse-sprite-stage relative h-40 w-44 shrink-0 overflow-hidden"
              class:is-active={isActive}
              style="--shift-x: {isActive ? pointerOffsetX : 0}px;"
            >
              <img
                src={phase.idleSrc}
                alt={phase.spriteAlt}
                draggable="false"
                class="pixel-sprite sprite-layer sprite-idle select-none [image-rendering:pixelated]"
              />
              <img
                src={phase.activeSrc}
                alt=""
                aria-hidden="true"
                draggable="false"
                class="pixel-sprite sprite-layer sprite-active select-none [image-rendering:pixelated]"
              />
            </div>
          </div>

          <!-- Card content -->
          <div class="flex flex-1 flex-col p-6">
            <span
              class="inline-flex w-fit items-center rounded-full border border-green-200 bg-white px-2.5 py-0.5 text-xs font-semibold tracking-wide text-green-800"
            >
              {phase.step}
            </span>

            <h3 class="mt-3 text-xl font-bold tracking-tight text-green-950">
              {phase.title}
            </h3>

            <p class="mt-2 text-sm leading-relaxed text-slate-700">
              {phase.description}
            </p>
          </div>
        </button>
      {/each}
    </div>
  </div>
</section>

<style>
  .pixel-sprite {
    image-rendering: -moz-crisp-edges;
    image-rendering: crisp-edges;
    image-rendering: pixelated;
  }

  .nurse-sprite-stage {
    transform: translate3d(var(--shift-x, 0px), 0, 0);
    transition: transform 120ms ease-out;
  }

  /* Idle state: show idle frame only */
  /* Half-body framing: scale sprite to 205px tall inside 160px stage so legs clip cleanly at waist */
  .sprite-layer {
    position: absolute;
    top: 4px;
    left: 50%;
    height: 205px;
    width: auto;
    max-width: none;
    pointer-events: none;
  }

  .sprite-idle {
    transform: translateX(-50%);
    opacity: 1;
  }

  .sprite-active {
    transform: translateX(-37.5%);
    opacity: 0;
  }

  /* Active state: crisp 2-frame RPG sprite toggle + subtle hand/body pixel motion */
  .nurse-sprite-stage.is-active {
    animation: nurse-bob 420ms steps(2, end) infinite;
  }

  .nurse-sprite-stage.is-active .sprite-idle {
    animation: frame-idle 420ms steps(1, end) infinite;
  }

  .nurse-sprite-stage.is-active .sprite-active {
    animation: frame-active 420ms steps(1, end) infinite;
  }

  @keyframes frame-idle {
    0%,
    49.9% {
      opacity: 0;
    }
    50%,
    100% {
      opacity: 1;
    }
  }

  @keyframes frame-active {
    0%,
    49.9% {
      opacity: 1;
    }
    50%,
    100% {
      opacity: 0;
    }
  }

  @keyframes nurse-bob {
    0% {
      transform: translate3d(var(--shift-x, 0px), 0, 0);
    }
    50% {
      transform: translate3d(var(--shift-x, 0px), -3px, 0);
    }
    100% {
      transform: translate3d(var(--shift-x, 0px), 0, 0);
    }
  }

  @media (prefers-reduced-motion: reduce) {
    .nurse-sprite-stage {
      transition: none;
    }
    .nurse-sprite-stage.is-active,
    .nurse-sprite-stage.is-active .sprite-idle,
    .nurse-sprite-stage.is-active .sprite-active {
      animation: none;
    }
    .nurse-sprite-stage.is-active .sprite-idle {
      opacity: 0;
    }
    .nurse-sprite-stage.is-active .sprite-active {
      opacity: 1;
    }
  }
</style>
